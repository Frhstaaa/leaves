<?php

namespace App\Services;

use App\Models\Department;
use App\Models\LeaveQuota;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Repositories\Contracts\LeaveRequestRepositoryInterface;
use Illuminate\Support\Facades\DB;

class DashboardService
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
     * Get aggregated user statistics for dashboard.
     */
    public function getUserStats(User $user, ?int $year = null): array
    {
        $year = $year ?: (int) date('Y');
        $quota = $this->quotaService->getOrSyncUserQuota($user->id, $year);

        $reqStats = LeaveRequest::where('user_id', $user->id)
            ->selectRaw('
                COUNT(*) as total,
                COUNT(CASE WHEN status = "pending" THEN 1 END) as pending,
                COUNT(CASE WHEN status = "approved" THEN 1 END) as approved,
                COUNT(CASE WHEN status = "rejected" THEN 1 END) as rejected
            ')
            ->first();

        return [
            'total_requests' => (int) ($reqStats->total ?? 0),
            'pending_requests' => (int) ($reqStats->pending ?? 0),
            'approved_requests' => (int) ($reqStats->approved ?? 0),
            'rejected_requests' => (int) ($reqStats->rejected ?? 0),
            'remaining_quota' => $quota->remaining_quota,
            'total_quota' => $quota->total_quota,
            'used_quota' => $quota->used_quota,
        ];
    }

    /**
     * Get high-level metrics for HRD & Admin dashboard.
     */
    public function getHrdMetrics(): array
    {
        $today = date('Y-m-d');
        $currentYear = (int) date('Y');

        $onLeaveToday = LeaveRequest::where('status', 'approved')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->count();

        $pendingCompanyWide = LeaveRequest::where('status', 'pending')->count();
        $totalEmployees = User::count();
        $totalDepartments = Department::count();

        return [
            'total_employees' => $totalEmployees,
            'total_departments' => $totalDepartments,
            'on_leave_today' => $onLeaveToday,
            'pending_company_wide' => $pendingCompanyWide,
        ];
    }
}
