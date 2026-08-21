<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\User;
use App\Models\Department;
use App\Models\LeaveCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MonitoringController extends Controller
{
    public function index(Request $request): Response
    {
        $user = Auth::user();
        
        // Authorization: Only Superadmin, Admin, and Manager can view this
        if (!in_array($user->role, ['superadmin', 'admin', 'manager'])) {
            abort(403, 'Akses khusus Manajemen dan HRD.');
        }

        $year = $request->query('year', date('Y'));

        // 1. SLA Calculation (Average Approval Time in Hours)
        $approvedRequests = LeaveRequest::where('status', 'approved')->whereYear('created_at', $year)->get(['created_at', 'updated_at']);
        $totalHours = 0;
        $count = $approvedRequests->count();
        
        foreach ($approvedRequests as $req) {
            $totalHours += $req->created_at->diffInHours($req->updated_at);
        }
        $averageSlaHours = $count > 0 ? round($totalHours / $count, 1) : 0;

        // 2. Status Distribution
        $statusDistribution = LeaveRequest::whereYear('created_at', $year)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get();

        // 3. Monthly Trend (Current Year)
        // Note: SQLite uses strftime. MySQL uses MONTH(). Let's fetch all and process in PHP for compatibility since data isn't massive.
        $allRequests = LeaveRequest::whereYear('created_at', $year)->get(['created_at']);
        $monthlyTrendRaw = array_fill(1, 12, 0);
        foreach ($allRequests as $req) {
            $month = (int) $req->created_at->format('m');
            $monthlyTrendRaw[$month]++;
        }

        $monthlyTrend = [];
        $months = [1=>'Jan', 2=>'Feb', 3=>'Mar', 4=>'Apr', 5=>'May', 6=>'Jun', 7=>'Jul', 8=>'Aug', 9=>'Sep', 10=>'Oct', 11=>'Nov', 12=>'Dec'];
        foreach ($months as $num => $name) {
            $monthlyTrend[] = [
                'name' => $name,
                'total' => $monthlyTrendRaw[$num],
            ];
        }

        // 4. Category Breakdown
        $categoriesRaw = LeaveRequest::with('category')->whereYear('created_at', $year)
            ->select('category_id', DB::raw('count(*) as total'))
            ->groupBy('category_id')
            ->get();
            
        $categoryBreakdown = $categoriesRaw->map(function ($item) {
            return [
                'name' => $item->category ? $item->category->name : 'Lainnya',
                'value' => (int) $item->total,
            ];
        });

        // 5. Department Breakdown
        // SQLite doesn't strictly support standard JOIN group by without ONLY_FULL_GROUP_BY issues sometimes,
        // but this is standard enough. Let's do it safely.
        $deptRaw = DB::table('leave_requests')
            ->join('users', 'leave_requests.user_id', '=', 'users.id')
            ->join('departments', 'users.department_id', '=', 'departments.id')
            ->whereYear('leave_requests.created_at', $year)
            ->select('departments.name', DB::raw('count(leave_requests.id) as total'))
            ->groupBy('departments.name')
            ->get();

        $departmentBreakdown = $deptRaw->map(function ($item) {
            return [
                'name' => $item->name,
                'total' => (int) $item->total,
            ];
        });

        // 6. Active Employees vs Total
        $totalEmployees = User::count();
        $onLeaveToday = LeaveRequest::where('status', 'approved')
            ->whereDate('start_date', '<=', date('Y-m-d'))
            ->whereDate('end_date', '>=', date('Y-m-d'))
            ->count();

        return Inertia::render('Monitoring/Index', [
            'metrics' => [
                'averageSlaHours' => $averageSlaHours,
                'totalRequests' => $allRequests->count(),
                'totalEmployees' => $totalEmployees,
                'onLeaveToday' => $onLeaveToday,
            ],
            'statusDistribution' => $statusDistribution,
            'monthlyTrend' => $monthlyTrend,
            'categoryBreakdown' => $categoryBreakdown,
            'departmentBreakdown' => $departmentBreakdown,
            'currentYear' => $year,
        ]);
    }
}
