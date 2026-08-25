<?php

namespace App\Http\Controllers;

use App\Models\LeaveCategory;
use App\Models\LeaveRequest;
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
        $year = (int) $request->query('year', date('Y'));

        $filters = [
            'search' => $request->query('search'),
            'status' => $request->query('status'),
            'category_id' => $request->query('category_id'),
            'year' => $year,
        ];

        $requests = $this->leaveRequestRepo->getUserRequestsPaginated($user->id, $filters, 10);
        $quota = $this->quotaService->getOrSyncUserQuota($user->id, $year);
        $categories = LeaveCategory::all();

        // 1. All user requests in selected year
        $allYearRequests = LeaveRequest::with('category')
            ->where('user_id', $user->id)
            ->whereYear('start_date', $year)
            ->orderBy('start_date', 'desc')
            ->get();

        $approvedRequests = $allYearRequests->where('status', 'approved');

        // 2. Absence Statistics
        $totalNotWorkingDays = (float) $approvedRequests->where('unit', 'hari')->sum('amount');
        
        // Quota Deducted (Cuti Tahunan & Cuti Haid)
        $totalQuotaDeductedDays = (float) $approvedRequests->where('unit', 'hari')->filter(function ($req) {
            return $req->category && ($req->category->deducts_quota || in_array(strtolower(trim($req->category->name)), ['cuti tahunan', 'cuti haid']));
        })->sum('amount');

        // Sick leave
        $totalSickDays = (float) $approvedRequests->where('unit', 'hari')->filter(function ($req) {
            return $req->category && str_contains(strtolower($req->category->name), 'sakit');
        })->sum('amount');

        // Other permissions (without quota deduction)
        $totalPermissionDays = (float) $approvedRequests->where('unit', 'hari')->filter(function ($req) {
            $catName = strtolower($req->category?->name ?? '');
            return !in_array($catName, ['cuti tahunan', 'cuti haid']) && !str_contains($catName, 'sakit');
        })->sum('amount');

        // 3. Monthly matrix calculation (1-12)
        $monthlyDeducted = array_fill(1, 12, 0.0);
        $monthlyOther = array_fill(1, 12, 0.0);
        $monthlyTotal = array_fill(1, 12, 0.0);

        foreach ($approvedRequests as $req) {
            $month = (int) date('n', strtotime($req->start_date));
            $isDeduct = $req->category && ($req->category->deducts_quota || in_array(strtolower(trim($req->category->name)), ['cuti tahunan', 'cuti haid']));
            $amt = (float) $req->amount;

            if ($isDeduct) {
                $monthlyDeducted[$month] += $amt;
            } else {
                $monthlyOther[$month] += $amt;
            }
            $monthlyTotal[$month] += $amt;
        }

        // 4. Category breakdown summary
        $categorySummary = [];
        foreach ($categories as $cat) {
            $catReqs = $allYearRequests->where('leave_category_id', $cat->id);
            $catApprovedReqs = $catReqs->where('status', 'approved');
            $categorySummary[] = [
                'id' => $cat->id,
                'name' => $cat->name,
                'unit_type' => $cat->unit_type,
                'deducts_quota' => $cat->isQuotaDeductible(),
                'total_submissions' => $catReqs->count(),
                'approved_count' => $catApprovedReqs->count(),
                'total_amount' => (float) $catApprovedReqs->sum('amount'),
            ];
        }

        $availableYears = LeaveRequest::where('user_id', $user->id)
            ->selectRaw('DISTINCT YEAR(start_date) as yr')
            ->whereNotNull('start_date')
            ->pluck('yr')
            ->toArray();
        $availableYears = array_unique(array_merge([date('Y'), date('Y') - 1, 2025, 2026], $availableYears));
        rsort($availableYears);

        return Inertia::render('LeaveRequests/Index', [
            'requests' => $requests,
            'filters' => $filters,
            'quota' => $quota,
            'categories' => $categories,
            'currentYear' => $year,
            'availableYears' => $availableYears,
            'statistics' => [
                'total_not_working_days' => $totalNotWorkingDays,
                'total_quota_deducted_days' => $totalQuotaDeductedDays,
                'total_sick_days' => $totalSickDays,
                'total_permission_days' => $totalPermissionDays,
                'monthly_deducted' => $monthlyDeducted,
                'monthly_other' => $monthlyOther,
                'monthly_total' => $monthlyTotal,
                'category_summary' => $categorySummary,
            ],
        ]);
    }

    /**
     * Print official personal employee leave & absence report.
     */
    public function printPersonalReport(Request $request)
    {
        $user = Auth::user()->loadMissing(['department', 'manager']);
        $year = (int) $request->query('year', date('Y'));

        $quota = $this->quotaService->getOrSyncUserQuota($user->id, $year);

        $allYearRequests = LeaveRequest::with('category')
            ->where('user_id', $user->id)
            ->whereYear('start_date', $year)
            ->orderBy('start_date', 'asc')
            ->get();

        $approvedRequests = $allYearRequests->where('status', 'approved');

        $totalNotWorkingDays = (float) $approvedRequests->where('unit', 'hari')->sum('amount');
        $totalQuotaDeductedDays = (float) $approvedRequests->where('unit', 'hari')->filter(function ($req) {
            return $req->category && ($req->category->deducts_quota || in_array(strtolower(trim($req->category->name)), ['cuti tahunan', 'cuti haid']));
        })->sum('amount');

        $totalSickDays = (float) $approvedRequests->where('unit', 'hari')->filter(function ($req) {
            return $req->category && str_contains(strtolower($req->category->name), 'sakit');
        })->sum('amount');

        $totalPermissionDays = (float) $approvedRequests->where('unit', 'hari')->filter(function ($req) {
            $catName = strtolower($req->category?->name ?? '');
            return !in_array($catName, ['cuti tahunan', 'cuti haid']) && !str_contains($catName, 'sakit');
        })->sum('amount');

        $monthlyDeducted = array_fill(1, 12, 0.0);
        $monthlyOther = array_fill(1, 12, 0.0);
        $monthlyTotal = array_fill(1, 12, 0.0);

        foreach ($approvedRequests as $req) {
            $month = (int) date('n', strtotime($req->start_date));
            $isDeduct = $req->category && ($req->category->deducts_quota || in_array(strtolower(trim($req->category->name)), ['cuti tahunan', 'cuti haid']));
            $amt = (float) $req->amount;

            if ($isDeduct) {
                $monthlyDeducted[$month] += $amt;
            } else {
                $monthlyOther[$month] += $amt;
            }
            $monthlyTotal[$month] += $amt;
        }

        $settings = \App\Models\Setting::getAll();
        $companyName = $settings['company_name'] ?? 'PT. SUGIYAMA INDONESIA';
        $companyLogo = $settings['app_logo'] ?? null;

        return view('reports.personal_leave_report', [
            'user' => $user,
            'year' => $year,
            'quota' => $quota,
            'requests' => $allYearRequests,
            'stats' => [
                'total_not_working_days' => $totalNotWorkingDays,
                'total_quota_deducted_days' => $totalQuotaDeductedDays,
                'total_sick_days' => $totalSickDays,
                'total_permission_days' => $totalPermissionDays,
                'monthly_deducted' => $monthlyDeducted,
                'monthly_other' => $monthlyOther,
                'monthly_total' => $monthlyTotal,
            ],
            'companyName' => $companyName,
            'companyLogo' => $companyLogo,
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
