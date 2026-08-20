<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\LeaveQuota;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ApprovalController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user->isManager() && !$user->isAdmin()) {
            return redirect()->route('dashboard')->with('error', 'Akses hanya untuk Atasan / Manager & HRD.');
        }

        $rawStatus = $request->query('status');
        $stageFilter = $request->query('stage'); // 'approval_1', 'approval_2', 'hrd', 'all'
        $deptId = $request->query('department_id');
        $search = $request->query('search');

        if ($request->has('status')) {
            $status = ($rawStatus === null || $rawStatus === '' || $rawStatus === 'all') ? 'all' : $rawStatus;
        } else {
            $status = 'pending';
        }

        $query = LeaveRequest::with([
            'user.department',
            'user.manager',
            'user.approver1',
            'user.approver2',
            'category',
            'approver',
            'approver1',
            'approver2',
            'approverHrd',
        ]);

        if (!$user->isAdmin()) {
            // Atasan (Supervisor / Manager) Logic:
            // If status is pending, show items where $user is the active approver for that stage:
            // 1. Stage approval_1 & (approver_1_id == $user->id OR (approver_1_id IS NULL AND dept.approver_1_id == $user->id))
            // 2. Stage approval_2 & (approver_2_id == $user->id OR manager_id == $user->id OR (approver_2_id IS NULL AND (dept.approver_2_id == $user->id OR dept.manager_id == $user->id)))
            if ($status === 'pending') {
                $query->where('status', 'pending')
                    ->where(function ($q) use ($user) {
                        $q->where(function ($sub) use ($user) {
                            $sub->where('current_stage', 'approval_1')
                                ->whereHas('user', function ($u) use ($user) {
                                    $u->where('approver_1_id', $user->id)
                                      ->orWhere(function ($d) use ($user) {
                                          $d->whereNull('approver_1_id')
                                            ->whereHas('department', function ($dept) use ($user) {
                                                $dept->where('approver_1_id', $user->id);
                                            });
                                      });
                                });
                        })->orWhere(function ($sub) use ($user) {
                            $sub->where('current_stage', 'approval_2')
                                ->whereHas('user', function ($u) use ($user) {
                                    $u->where('approver_2_id', $user->id)
                                      ->orWhere('manager_id', $user->id)
                                      ->orWhere(function ($d) use ($user) {
                                          $d->whereNull('manager_id')
                                            ->whereNull('approver_2_id')
                                            ->whereHas('department', function ($dept) use ($user) {
                                                $dept->where('approver_2_id', $user->id)
                                                     ->orWhere('manager_id', $user->id);
                                            });
                                      });
                                });
                        });
                    });
            } else {
                // Historical / All: Show any requests belonging to user's subordinates
                $subordinateIds = User::where(function ($q) use ($user) {
                    $q->where('approver_1_id', $user->id)
                      ->orWhere('approver_2_id', $user->id)
                      ->orWhere('manager_id', $user->id)
                      ->orWhere(function ($sub) use ($user) {
                          $sub->whereNull('approver_1_id')
                              ->whereHas('department', function ($dept) use ($user) {
                                  $dept->where('approver_1_id', $user->id);
                              });
                      })
                      ->orWhere(function ($sub) use ($user) {
                          $sub->whereNull('manager_id')
                              ->whereNull('approver_2_id')
                              ->whereHas('department', function ($dept) use ($user) {
                                  $dept->where('approver_2_id', $user->id)
                                       ->orWhere('manager_id', $user->id);
                              });
                      });
                })->where('id', '!=', $user->id)->pluck('id');

                $query->whereIn('user_id', $subordinateIds);
            }
        } else {
            // HRD / Admin / Superadmin:
            if ($stageFilter && in_array($stageFilter, ['approval_1', 'approval_2', 'hrd'])) {
                $query->where('current_stage', $stageFilter);
            }

            if ($deptId) {
                $query->whereHas('user', function ($q) use ($deptId) {
                    $q->where('department_id', $deptId);
                });
            }
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('request_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($u) use ($search) {
                      $u->where('name', 'like', "%{$search}%")
                        ->orWhere('nik', 'like', "%{$search}%");
                  });
            });
        }

        if (in_array($status, ['pending', 'approved', 'rejected'])) {
            $query->where('status', $status);
        }

        $requests = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();
        $departments = $user->isAdmin() ? Department::all() : [];

        return Inertia::render('Approvals/Index', [
            'requests' => $requests,
            'departments' => $departments,
            'filters' => [
                'status' => $status,
                'stage' => $stageFilter,
                'department_id' => $deptId,
                'search' => $search,
            ],
            'isHrdAdmin' => $user->isAdmin(),
        ]);
    }

    public function approve(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user->isManager() && !$user->isAdmin()) {
            return back()->with('error', 'Anda tidak memiliki hak akses persetujuan.');
        }

        $leaveRequest = LeaveRequest::with(['category', 'user.department', 'user.approver1', 'user.approver2', 'user.manager'])->findOrFail($id);
        $requester = $leaveRequest->user;
        $note = $request->input('note');
        $currentStage = $leaveRequest->current_stage ?? 'hrd';

        if ($leaveRequest->status !== 'pending') {
            return back()->with('error', 'Pengajuan ini sudah tidak lagi berstatus pending.');
        }

        $effApprover1 = $requester->getEffectiveApprover1();
        $effApprover2 = $requester->getEffectiveApprover2();

        // TIER 1: Approval 1 (Supervisor / Atasan 1)
        if ($currentStage === 'approval_1') {
            $isApprover1 = ($effApprover1 && $effApprover1->id == $user->id);

            if (!$user->isAdmin() && !$isApprover1) {
                return back()->with('error', 'Anda bukan Atasan 1 yang berwenang untuk tahap ini.');
            }

            // Determine next stage: If employee has approver 2, go to approval_2; else go to hrd
            $nextStage = $effApprover2 ? 'approval_2' : 'hrd';

            $leaveRequest->update([
                'approved_by_1' => $user->id,
                'approval_1_note' => $note,
                'approved_1_at' => now(),
                'current_stage' => $nextStage,
            ]);

            $nextName = $effApprover2 ? $effApprover2->name : 'HRD / PGA Admin';

            return back()->with('success', "Persetujuan Tingkat 1 (Approval 1) berhasil diberikan! Permohonan kini diteruskan ke {$nextName}.");
        }

        // TIER 2: Approval 2 (Manager / Atasan 2)
        if ($currentStage === 'approval_2') {
            $isApprover2 = ($effApprover2 && $effApprover2->id == $user->id);

            if (!$user->isAdmin() && !$isApprover2) {
                return back()->with('error', 'Anda bukan Atasan 2 yang berwenang untuk tahap ini.');
            }

            $leaveRequest->update([
                'approved_by_2' => $user->id,
                'approval_2_note' => $note,
                'approved_2_at' => now(),
                'current_stage' => 'hrd',
            ]);

            return back()->with('success', 'Persetujuan Tingkat 2 (Approval 2) berhasil diberikan! Permohonan kini diteruskan ke HRD / PGA Admin.');
        }

        // TIER 3: Final Approval by HRD / PGA Admin
        if ($currentStage === 'hrd' || $user->isAdmin()) {
            if (!$user->isAdmin()) {
                return back()->with('error', 'Persetujuan akhir hanya dapat dilakukan oleh HRD / PGA Admin.');
            }

            $leaveRequest->update([
                'approved_by_hrd' => $user->id,
                'approval_hrd_note' => $note,
                'approved_hrd_at' => now(),
                'approved_by' => $user->id,
                'approval_note' => $note,
                'approved_at' => now(),
                'status' => 'approved',
                'current_stage' => 'completed',
            ]);

            // Deduct quota if Cuti Tahunan and unit = hari
            if (strtolower($leaveRequest->category->name) === 'cuti tahunan' && $leaveRequest->unit === 'hari') {
                $currentYear = date('Y');
                $quota = LeaveQuota::firstOrCreate(
                    ['user_id' => $leaveRequest->user_id, 'year' => $currentYear],
                    ['total_quota' => 12, 'used_quota' => 0, 'remaining_quota' => 12]
                );

                $newUsed = $quota->used_quota + (int) $leaveRequest->amount;
                $newRemaining = max(0, $quota->total_quota - $newUsed);

                $quota->update([
                    'used_quota' => $newUsed,
                    'remaining_quota' => $newRemaining,
                ]);
            }

            return back()->with('success', 'Persetujuan akhir oleh HRD berhasil! Pengajuan ' . $leaveRequest->request_number . ' milik ' . $requester->name . ' telah disetujui (Approved).');
        }

        return back()->with('error', 'Tahapan persetujuan tidak valid.');
    }

    public function reject(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user->isManager() && !$user->isAdmin()) {
            return back()->with('error', 'Anda tidak memiliki hak akses persetujuan.');
        }

        $request->validate([
            'note' => 'required|string|min:3|max:500',
        ], [
            'note.required' => 'Alasan penolakan pengajuan wajib diisi.',
            'note.min' => 'Alasan penolakan minimal 3 karakter.',
        ]);

        $leaveRequest = LeaveRequest::with(['user.approver1', 'user.approver2', 'user.manager'])->findOrFail($id);
        $requester = $leaveRequest->user;
        $currentStage = $leaveRequest->current_stage ?? 'hrd';

        // Authorization check
        if (!$user->isAdmin()) {
            $isAuth = false;
            $effApprover1 = $requester->getEffectiveApprover1();
            $effApprover2 = $requester->getEffectiveApprover2();

            if ($currentStage === 'approval_1' && $effApprover1 && $effApprover1->id == $user->id) {
                $isAuth = true;
            } elseif ($currentStage === 'approval_2' && $effApprover2 && $effApprover2->id == $user->id) {
                $isAuth = true;
            }

            if (!$isAuth || $leaveRequest->user_id == $user->id) {
                return back()->with('error', 'Anda tidak memiliki wewenang untuk menolak pengajuan pada tahapan ini.');
            }
        }

        if ($leaveRequest->status !== 'pending') {
            return back()->with('error', 'Pengajuan ini sudah tidak lagi berstatus pending.');
        }

        $stageName = match ($currentStage) {
            'approval_1' => 'Approval 1',
            'approval_2' => 'Approval 2',
            default => 'HRD / Admin',
        };

        $leaveRequest->update([
            'status' => 'rejected',
            'approved_by' => $user->id,
            'approval_note' => "[Ditolak pada {$stageName} oleh {$user->name}]: " . $request->input('note'),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Pengajuan cuti ' . $leaveRequest->request_number . ' telah ditolak.');
    }
}
