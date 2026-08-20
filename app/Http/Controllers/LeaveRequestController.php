<?php

namespace App\Http\Controllers;

use App\Models\LeaveCategory;
use App\Repositories\Contracts\LeaveRequestRepositoryInterface;
use App\Services\LeaveQuotaService;
use App\Services\LeaveRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LeaveRequestController extends Controller
{
    protected LeaveRequestService $leaveRequestService;
    protected LeaveRequestRepositoryInterface $leaveRequestRepo;
    protected LeaveQuotaService $quotaService;

    public function __construct(
        LeaveRequestService $leaveRequestService,
        LeaveRequestRepositoryInterface $leaveRequestRepo,
        LeaveQuotaService $quotaService
    ) {
        $this->leaveRequestService = $leaveRequestService;
        $this->leaveRequestRepo = $leaveRequestRepo;
        $this->quotaService = $quotaService;
    }

    public function index(Request $request): Response
    {
        $user = Auth::user();
        $filters = [
            'search' => $request->query('search'),
            'status' => $request->query('status'),
        ];

        $requests = $this->leaveRequestRepo->getUserRequestsPaginated($user->id, $filters, 10);
        $quota = $this->quotaService->getOrSyncUserQuota($user->id);

        return Inertia::render('LeaveRequests/Index', [
            'requests' => $requests,
            'filters' => $filters,
            'quota' => $quota,
        ]);
    }

    public function create(): Response
    {
        $user = Auth::user()->loadMissing(['department.manager', 'department.approver1', 'department.approver2', 'manager', 'approver1', 'approver2']);
        $categories = LeaveCategory::all();
        $quota = $this->quotaService->getOrSyncUserQuota($user->id);

        $effApprover1 = $user->getEffectiveApprover1();
        $effApprover2 = $user->getEffectiveApprover2();

        $approvalChain = [];
        if ($effApprover1) {
            $approvalChain[] = [
                'level' => 1,
                'role_title' => 'Approval 1 (Supervisor / Atasan 1)',
                'name' => $effApprover1->name,
                'department' => $effApprover1->department?->name ?? 'Departemen',
            ];
        }
        if ($effApprover2) {
            $approvalChain[] = [
                'level' => 2,
                'role_title' => 'Approval 2 (Manager / Atasan 2)',
                'name' => $effApprover2->name,
                'department' => $effApprover2->department?->name ?? 'Departemen',
            ];
        }
        $approvalChain[] = [
            'level' => 3,
            'role_title' => 'Approval HRD / PGA Admin',
            'name' => 'HRD / PGA Admin',
            'department' => 'Human Resources & PGA',
        ];

        return Inertia::render('LeaveRequests/Create', [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'nik' => $user->nik ?? 'EMP-' . str_pad($user->id, 3, '0', STR_PAD_LEFT),
                'department_name' => $user->department ? $user->department->name : 'General',
                'approval_chain' => $approvalChain,
            ],
            'categories' => $categories,
            'quota' => $quota,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'submission_type' => 'required|in:PEMBERITAHUAN,PERMOHONAN',
            'approval_agreed' => 'required|in:Ya,1,true',
            'leave_category_id' => 'required|exists:leave_categories,id',
            'unit' => 'required|in:hari,jam',
            'amount' => 'required|numeric|min:0.5',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|min:3|max:1000',
            'attachment' => 'nullable|file|mimes:pdf,png,jpg,jpeg|max:10240',
        ], [
            'submission_type.required' => 'Silakan pilih jenis pengajuan (PEMBERITAHUAN / PERMOHONAN).',
            'approval_agreed.required' => 'Anda harus menyetujui persetujuan kepala departemen untuk melanjutkan.',
            'leave_category_id.required' => 'Silakan pilih jenis permohonan / tidak bekerja.',
            'start_date.required' => 'Tanggal permohonan wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal akhir harus sama atau setelah tanggal mulai.',
            'reason.required' => 'Detail alasan cuti / ketidakhadiran wajib diisi.',
            'attachment.max' => 'Ukuran file lampiran tidak boleh melebihi 10 MB.',
        ]);

        $leaveRequest = $this->leaveRequestService->createRequest(
            $user,
            array_merge($validated, ['category_id' => $validated['leave_category_id']]),
            $request->file('attachment')
        );

        $stageName = strtoupper(str_replace('_', ' ', $leaveRequest->current_stage));
        return redirect()->route('leave-requests.index')
            ->with('success', "Pengajuan {$leaveRequest->request_number} berhasil dikirim! Status: Menunggu Persetujuan ({$stageName}).");
    }

    public function show(int $id): JsonResponse
    {
        $leaveRequest = $this->leaveRequestRepo->findById($id);
        if (!$leaveRequest) {
            return response()->json(['message' => 'Pengajuan tidak ditemukan.'], 404);
        }

        return response()->json($leaveRequest);
    }

    public function destroy(int $id): RedirectResponse
    {
        $user = Auth::user();
        $leaveRequest = $this->leaveRequestRepo->findById($id);

        if (!$leaveRequest) {
            return redirect()->route('leave-requests.index')->with('error', 'Pengajuan tidak ditemukan.');
        }

        try {
            $this->leaveRequestService->deleteRequest($leaveRequest, $user);
            return redirect()->route('leave-requests.index')->with('success', 'Pengajuan cuti berhasil dibatalkan dan dihapus.');
        } catch (\Exception $e) {
            return redirect()->route('leave-requests.index')->with('error', $e->getMessage());
        }
    }
}
