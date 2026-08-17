<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\LeaveQuota;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user()->load(['department', 'manager']);
        $currentYear = date('Y');

        // Fetch user quota
        $quota = LeaveQuota::firstOrCreate(
            ['user_id' => $user->id, 'year' => $currentYear],
            ['total_quota' => 12, 'used_quota' => 0, 'remaining_quota' => 12]
        );

        // Fetch stats based on role
        $stats = [
            'total_requests' => LeaveRequest::where('user_id', $user->id)->count(),
            'pending_requests' => LeaveRequest::where('user_id', $user->id)->where('status', 'pending')->count(),
            'approved_requests' => LeaveRequest::where('user_id', $user->id)->where('status', 'approved')->count(),
            'rejected_requests' => LeaveRequest::where('user_id', $user->id)->where('status', 'rejected')->count(),
            'remaining_quota' => $quota->remaining_quota,
            'total_quota' => $quota->total_quota,
            'used_quota' => $quota->used_quota,
        ];

        // Recent user requests
        $recentRequests = LeaveRequest::with(['category', 'approver'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Role-specific data
        $managerPendingCount = 0;
        $teamRequests = [];
        $hrdMetrics = [];

        if ($user->isManager()) {
            // Subordinates' pending requests
            $subordinateIds = User::where('manager_id', $user->id)->orWhere('department_id', $user->department_id)->pluck('id');
            $managerPendingCount = LeaveRequest::whereIn('user_id', $subordinateIds)
                ->where('status', 'pending')
                ->where('user_id', '!=', $user->id)
                ->count();

            $teamRequests = LeaveRequest::with(['user', 'category'])
                ->whereIn('user_id', $subordinateIds)
                ->where('user_id', '!=', $user->id)
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
        }

        if ($user->isAdmin()) {
            $hrdMetrics = [
                'total_employees' => User::count(),
                'total_departments' => Department::count(),
                'pending_company_wide' => LeaveRequest::where('status', 'pending')->count(),
                'approved_today' => LeaveRequest::where('status', 'approved')->whereDate('approved_at', today())->count(),
                'on_leave_today' => LeaveRequest::where('status', 'approved')
                    ->whereDate('start_date', '<=', today())
                    ->whereDate('end_date', '>=', today())
                    ->count(),
            ];
        }

        return Inertia::render('Dashboard', [
            'user' => $user,
            'stats' => $stats,
            'recentRequests' => $recentRequests,
            'managerPendingCount' => $managerPendingCount,
            'teamRequests' => $teamRequests,
            'hrdMetrics' => $hrdMetrics,
        ]);
    }
}
