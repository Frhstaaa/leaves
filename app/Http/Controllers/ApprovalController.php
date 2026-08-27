<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use App\Repositories\Contracts\LeaveRequestRepositoryInterface;
use App\Services\LeaveRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ApprovalController extends Controller
{
    protected LeaveRequestService $leaveRequestService;
    protected LeaveRequestRepositoryInterface $leaveRequestRepo;
    protected DepartmentRepositoryInterface $departmentRepo;

    public function __construct(
        LeaveRequestService $leaveRequestService,
        LeaveRequestRepositoryInterface $leaveRequestRepo,
        DepartmentRepositoryInterface $departmentRepo
    ) {
        $this->leaveRequestService = $leaveRequestService;
        $this->leaveRequestRepo = $leaveRequestRepo;
        $this->departmentRepo = $departmentRepo;
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        if (!$user->isApprover() && !$user->isAdmin()) {
            return redirect()->route('dashboard')->with('error', 'Akses hanya untuk Atasan / Approver & HRD.');
        }

        $filters = [
            'status' => $request->query('status', 'pending'),
            'stage' => $request->query('stage', 'all'),
            'department_id' => $request->query('department_id', ''),
            'search' => $request->query('search', ''),
        ];

        $requests = $this->leaveRequestRepo->getPendingApprovalsForUser($user, $filters, 10);
        $departments = $user->isAdmin() ? $this->departmentRepo->getAll() : collect([]);

        return Inertia::render('Approvals/Index', [
            'requests' => $requests,
            'filters' => $filters,
            'departments' => $departments,
        ]);
    }

    public function approve(Request $request, int $id): RedirectResponse
    {
        $user = Auth::user();
        $leaveRequest = $this->leaveRequestRepo->findById($id);

        if (!$leaveRequest) {
            return back()->with('error', 'Pengajuan tidak ditemukan.');
        }

        try {
            $result = $this->leaveRequestService->approveRequest($leaveRequest, $user, $request->input('note'));
            return back()->with('success', $result['message']);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function reject(Request $request, int $id): RedirectResponse
    {
        $user = Auth::user();

        $request->validate([
            'note' => 'required|string|min:3|max:500',
        ], [
            'note.required' => 'Alasan penolakan pengajuan wajib diisi.',
            'note.min' => 'Alasan penolakan minimal 3 karakter.',
        ]);

        $leaveRequest = $this->leaveRequestRepo->findById($id);
        if (!$leaveRequest) {
            return back()->with('error', 'Pengajuan tidak ditemukan.');
        }

        try {
            $result = $this->leaveRequestService->rejectRequest($leaveRequest, $user, $request->input('note'));
            return back()->with('success', $result['message']);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
