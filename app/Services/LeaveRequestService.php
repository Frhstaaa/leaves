<?php

namespace App\Services;

use App\Models\LeaveCategory;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Repositories\Contracts\LeaveRequestRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class LeaveRequestService
{
    protected LeaveRequestRepositoryInterface $leaveRequestRepo;
    protected LeaveQuotaService $quotaService;

    public function __construct(
        LeaveRequestRepositoryInterface $leaveRequestRepo,
        LeaveQuotaService $quotaService
    ) {
        $this->leaveRequestRepo = $leaveRequestRepo;
        $this->quotaService = $quotaService;
    }

    /**
     * Create a new leave request with sequential tier determination.
     */
    public function createRequest(User $user, array $data, $attachmentFile = null): LeaveRequest
    {
        return DB::transaction(function () use ($user, $data, $attachmentFile) {
            $user->loadMissing(['department.manager', 'department.approver1', 'department.approver2', 'manager', 'approver1', 'approver2']);

            $category = LeaveCategory::findOrFail($data['category_id']);
            $currentYear = (int) date('Y', strtotime($data['start_date']));

            // Validate attachment if required
            if ($category->requires_attachment && !$attachmentFile) {
                throw ValidationException::withMessages([
                    'attachment' => 'Kategori cuti ini mewajibkan Anda melampirkan dokumen/surat pendukung.',
                ]);
            }

            // Quota validation for annual leave
            if (strtolower($category->name) === 'cuti tahunan' && ($data['unit'] ?? 'hari') === 'hari') {
                $quota = $this->quotaService->getOrSyncUserQuota($user->id, $currentYear);
                if ($quota->remaining_quota < (float) $data['amount']) {
                    throw ValidationException::withMessages([
                        'amount' => "Sisa kuota cuti tahunan Anda tidak mencukupi ({$quota->remaining_quota} hari tersisa).",
                    ]);
                }
            }

            // Generate Request Number
            $prefix = 'LV-' . date('Ymd');
            $countToday = LeaveRequest::whereDate('created_at', today())->count() + 1;
            $requestNumber = $prefix . '-' . str_pad($countToday, 4, '0', STR_PAD_LEFT);

            // Handle Attachment Upload
            $attachmentPath = null;
            if ($attachmentFile) {
                $attachmentPath = $attachmentFile->store('attachments/leave_requests', 'public');
            }

            // Multi-Tier Effective Approvers
            $effApprover1 = $user->getEffectiveApprover1();
            $effApprover2 = $user->getEffectiveApprover2();

            // Determine initial stage
            $initialStage = 'hrd';
            if ($effApprover1) {
                $initialStage = 'approval_1';
            } elseif ($effApprover2) {
                $initialStage = 'approval_2';
            }

            return $this->leaveRequestRepo->create([
                'user_id' => $user->id,
                'request_number' => $requestNumber,
                'category_id' => $category->id,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'amount' => $data['amount'],
                'unit' => $data['unit'] ?? 'hari',
                'submission_type' => $data['submission_type'] ?? 'terencana',
                'reason' => $data['reason'],
                'attachment_path' => $attachmentPath,
                'status' => 'pending',
                'current_stage' => $initialStage,
                'approver_1_id' => $effApprover1 ? $effApprover1->id : null,
                'approver_2_id' => $effApprover2 ? $effApprover2->id : null,
                'manager_id' => $effApprover2 ? $effApprover2->id : ($effApprover1 ? $effApprover1->id : null),
            ]);
        });
    }

    /**
     * Approve leave request according to sequential hierarchy.
     */
    public function approveRequest(LeaveRequest $leaveRequest, User $approver, ?string $note = null): array
    {
        return DB::transaction(function () use ($leaveRequest, $approver, $note) {
            $isApprover1 = ($leaveRequest->approver_1_id && $leaveRequest->approver_1_id == $approver->id);
            $isApprover2 = ($leaveRequest->approver_2_id && $leaveRequest->approver_2_id == $approver->id);
            $isAdmin = $approver->isAdmin();

            if (!$isApprover1 && !$isApprover2 && !$isAdmin) {
                throw new \Exception('Anda tidak memiliki otoritas untuk menyetujui pengajuan ini.');
            }

            // TIER 1: Atasan 1 Approval
            if ($leaveRequest->current_stage === 'approval_1' && ($isApprover1 || $isAdmin)) {
                $nextStage = $leaveRequest->approver_2_id ? 'approval_2' : 'hrd';

                $this->leaveRequestRepo->update($leaveRequest, [
                    'approved_by_1' => true,
                    'approval_1_note' => $note,
                    'current_stage' => $nextStage,
                    'approver_id' => $approver->id,
                ]);

                return [
                    'success' => true,
                    'message' => $nextStage === 'approval_2'
                        ? 'Persetujuan Tingkat 1 berhasil. Pengajuan diteruskan ke Atasan Tingkat 2.'
                        : 'Persetujuan Tingkat 1 berhasil. Pengajuan diteruskan ke HRD untuk persetujuan akhir.',
                ];
            }

            // TIER 2: Atasan 2 Approval
            if ($leaveRequest->current_stage === 'approval_2' && ($isApprover2 || $isAdmin)) {
                $this->leaveRequestRepo->update($leaveRequest, [
                    'approved_by_2' => true,
                    'approval_2_note' => $note,
                    'current_stage' => 'hrd',
                    'approver_id' => $approver->id,
                ]);

                return [
                    'success' => true,
                    'message' => 'Persetujuan Tingkat 2 berhasil. Pengajuan diteruskan ke HRD untuk persetujuan akhir.',
                ];
            }

            // TIER 3: HRD / PGA Admin Final Approval
            if (($leaveRequest->current_stage === 'hrd' || $isAdmin) && $isAdmin) {
                $this->leaveRequestRepo->update($leaveRequest, [
                    'status' => 'approved',
                    'current_stage' => 'completed',
                    'approved_by_hrd' => true,
                    'approval_note' => $note,
                    'approver_id' => $approver->id,
                ]);

                // Deduct quota
                $this->quotaService->deductQuota($leaveRequest);

                return [
                    'success' => true,
                    'message' => 'Pengajuan cuti telah berhasil disetujui secara FINAL oleh HRD.',
                ];
            }

            throw new \Exception('Pengajuan ini belum berada pada tahap persetujuan Anda.');
        });
    }

    /**
     * Reject leave request and halt sequential chain.
     */
    public function rejectRequest(LeaveRequest $leaveRequest, User $approver, ?string $note = null): array
    {
        return DB::transaction(function () use ($leaveRequest, $approver, $note) {
            $isApprover1 = ($leaveRequest->approver_1_id && $leaveRequest->approver_1_id == $approver->id);
            $isApprover2 = ($leaveRequest->approver_2_id && $leaveRequest->approver_2_id == $approver->id);
            $isAdmin = $approver->isAdmin();

            if (!$isApprover1 && !$isApprover2 && !$isAdmin) {
                throw new \Exception('Anda tidak memiliki otoritas untuk menolak pengajuan ini.');
            }

            $updateData = [
                'status' => 'rejected',
                'approver_id' => $approver->id,
            ];

            if ($leaveRequest->current_stage === 'approval_1') {
                $updateData['approval_1_note'] = $note;
            } elseif ($leaveRequest->current_stage === 'approval_2') {
                $updateData['approval_2_note'] = $note;
            } else {
                $updateData['approval_note'] = $note;
            }

            $this->leaveRequestRepo->update($leaveRequest, $updateData);

            // Restore quota if it was previously approved
            $this->quotaService->restoreQuota($leaveRequest);

            return [
                'success' => true,
                'message' => 'Pengajuan cuti telah ditolak dan alur persetujuan dihentikan.',
            ];
        });
    }

    /**
     * Manual status override by HRD.
     */
    public function overrideStatus(LeaveRequest $leaveRequest, User $admin, string $status, ?string $stage = null, ?string $note = null): array
    {
        return DB::transaction(function () use ($leaveRequest, $admin, $status, $stage, $note) {
            if (!$admin->isAdmin()) {
                throw new \Exception('Hanya HRD atau Superadmin yang dapat melakukan override status manual.');
            }

            $oldStatus = $leaveRequest->status;

            if ($status === 'approved') {
                $this->leaveRequestRepo->update($leaveRequest, [
                    'status' => 'approved',
                    'current_stage' => 'completed',
                    'approved_by_hrd' => true,
                    'approved_by_1' => true,
                    'approved_by_2' => true,
                    'approval_note' => $note ?: 'Disetujui langsung oleh HRD (Manual Override)',
                    'approver_id' => $admin->id,
                ]);

                $this->quotaService->deductQuota($leaveRequest);
                $msg = "Pengajuan #{$leaveRequest->request_number} berhasil disetujui langsung (Approved).";
            } elseif ($status === 'rejected') {
                $this->leaveRequestRepo->update($leaveRequest, [
                    'status' => 'rejected',
                    'approval_note' => $note ?: 'Ditolak oleh HRD (Manual Override)',
                    'approver_id' => $admin->id,
                ]);

                $this->quotaService->restoreQuota($leaveRequest);
                $msg = "Pengajuan #{$leaveRequest->request_number} berhasil ditolak (Rejected).";
            } else {
                // Pending with target stage
                $targetStage = $stage ?: 'hrd';
                $this->leaveRequestRepo->update($leaveRequest, [
                    'status' => 'pending',
                    'current_stage' => $targetStage,
                    'approved_by_hrd' => false,
                    'approval_note' => $note,
                ]);

                if ($oldStatus === 'approved') {
                    $this->quotaService->restoreQuota($leaveRequest);
                }
                $msg = "Status pengajuan #{$leaveRequest->request_number} dikembalikan ke Pending ({$targetStage}).";
            }

            return ['success' => true, 'message' => $msg];
        });
    }

    /**
     * Delete / cancel leave request.
     */
    public function deleteRequest(LeaveRequest $leaveRequest, User $user): bool
    {
        if ($leaveRequest->user_id !== $user->id && !$user->isAdmin()) {
            throw new \Exception('Anda tidak berhak menghapus pengajuan ini.');
        }

        if ($leaveRequest->status !== 'pending' && !$user->isAdmin()) {
            throw new \Exception('Hanya pengajuan berstatus pending yang dapat dibatalkan.');
        }

        return DB::transaction(function () use ($leaveRequest) {
            if ($leaveRequest->attachment_path) {
                Storage::disk('public')->delete($leaveRequest->attachment_path);
            }
            if ($leaveRequest->status === 'approved') {
                $this->quotaService->restoreQuota($leaveRequest);
            }
            return $this->leaveRequestRepo->delete($leaveRequest);
        });
    }
}
