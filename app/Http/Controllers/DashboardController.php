<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\LeaveRequestRepositoryInterface;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    protected DashboardService $dashboardService;
    protected LeaveRequestRepositoryInterface $leaveRequestRepo;

    public function __construct(
        DashboardService $dashboardService,
        LeaveRequestRepositoryInterface $leaveRequestRepo
    ) {
        $this->dashboardService = $dashboardService;
        $this->leaveRequestRepo = $leaveRequestRepo;
    }

    public function index(): Response
    {
        $user = Auth::user()->loadMissing(['department', 'manager']);
        $currentYear = (int) date('Y');

        $stats = $this->dashboardService->getUserStats($user, $currentYear);
        $recentRequests = $this->leaveRequestRepo->getRecentUserRequests($user->id, 5);

        $managerPendingCount = 0;
        $teamRequests = [];
        $hrdMetrics = [];

        if ($user->isManager() && !$user->isAdmin()) {
            $managerPendingCount = $this->leaveRequestRepo->countPendingApprovalsForUser($user);
        }

        if ($user->isAdmin()) {
            $hrdMetrics = $this->dashboardService->getHrdMetrics();
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
