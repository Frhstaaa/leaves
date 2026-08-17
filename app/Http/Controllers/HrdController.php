<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\LeaveCategory;
use App\Models\LeaveQuota;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class HrdController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            return redirect()->route('dashboard')->with('error', 'Halaman ini khusus untuk HRD / PGA Admin.');
        }

        $deptId = $request->query('department_id');
        $status = $request->query('status');
        $categoryId = $request->query('category_id');
        $search = $request->query('search');

        $query = LeaveRequest::with(['user.department', 'category', 'approver']);

        if ($deptId) {
            $query->whereHas('user', function ($q) use ($deptId) {
                $q->where('department_id', $deptId);
            });
        }

        if ($status && in_array($status, ['pending', 'approved', 'rejected'])) {
            $query->where('status', $status);
        }

        if ($categoryId) {
            $query->where('leave_category_id', $categoryId);
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

        $requests = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();
        $departments = Department::all();
        $categories = LeaveCategory::all();

        // Calculate Stats
        $allRequests = LeaveRequest::all();
        $stats = [
            'total' => $allRequests->count(),
            'pending' => $allRequests->where('status', 'pending')->count(),
            'approved' => $allRequests->where('status', 'approved')->count(),
            'rejected' => $allRequests->where('status', 'rejected')->count(),
            'total_days_used' => $allRequests->where('status', 'approved')->sum('amount'),
        ];

        return Inertia::render('HRD/Index', [
            'requests' => $requests,
            'departments' => $departments,
            'categories' => $categories,
            'stats' => $stats,
            'filters' => [
                'department_id' => $deptId,
                'status' => $status,
                'category_id' => $categoryId,
                'search' => $search,
            ],
        ]);
    }

    public function employees(Request $request)
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            return redirect()->route('dashboard')->with('error', 'Akses khusus HRD.');
        }

        $search = $request->query('search');
        $deptId = $request->query('department_id');
        $role = $request->query('role');

        $query = User::with(['department', 'manager', 'currentQuota']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nik', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($deptId) {
            $query->where('department_id', $deptId);
        }

        if ($role && in_array($role, ['employee', 'manager', 'admin'])) {
            $query->where('role', $role);
        }

        $employees = $query->orderBy('name', 'asc')->get();
        $departments = Department::all();
        $managers = User::whereIn('role', ['manager', 'admin'])->get();

        // Overall stats
        $allUsers = User::all();
        $stats = [
            'total_employees' => $allUsers->count(),
            'total_departments' => $departments->count(),
            'total_managers' => $allUsers->whereIn('role', ['manager', 'admin'])->count(),
            'active_quotas' => LeaveQuota::where('year', date('Y'))->sum('remaining_quota'),
        ];

        return Inertia::render('HRD/Employees', [
            'employees' => $employees,
            'departments' => $departments,
            'managers' => $managers,
            'stats' => $stats,
            'filters' => [
                'search' => $search,
                'department_id' => $deptId,
                'role' => $role,
            ],
        ]);
    }

    public function storeEmployee(Request $request)
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            return back()->with('error', 'Akses khusus HRD.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'nik' => 'required|string|max:50|unique:users,nik',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:employee,manager,admin',
            'department_id' => 'nullable|exists:departments,id',
            'manager_id' => 'nullable|exists:users,id',
            'total_quota' => 'required|integer|min:0|max:100',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
        }

        $newEmployee = User::create([
            'name' => $validated['name'],
            'nik' => $validated['nik'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'department_id' => $validated['department_id'],
            'manager_id' => $validated['manager_id'],
            'avatar' => $avatarPath,
        ]);

        // Create Leave Quota for current year
        $currentYear = date('Y');
        $totalQuota = (int) $validated['total_quota'];
        LeaveQuota::create([
            'user_id' => $newEmployee->id,
            'year' => $currentYear,
            'total_quota' => $totalQuota,
            'used_quota' => 0,
            'remaining_quota' => $totalQuota,
        ]);

        return redirect()->back()->with('success', "Karyawan {$newEmployee->name} ({$newEmployee->nik}) berhasil ditambahkan.");
    }

    public function updateEmployee(Request $request, $userId)
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            return back()->with('error', 'Akses khusus HRD.');
        }

        $employee = User::findOrFail($userId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'nik' => 'required|string|max:50|unique:users,nik,' . $employee->id,
            'email' => 'required|email|max:255|unique:users,email,' . $employee->id,
            'password' => 'nullable|string|min:6',
            'role' => 'required|in:employee,manager,admin',
            'department_id' => 'nullable|exists:departments,id',
            'manager_id' => 'nullable|exists:users,id',
            'total_quota' => 'required|integer|min:0|max:100',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $updateData = [
            'name' => $validated['name'],
            'nik' => $validated['nik'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'department_id' => $validated['department_id'],
            'manager_id' => $validated['manager_id'],
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        if ($request->hasFile('avatar')) {
            if ($employee->avatar && Storage::disk('public')->exists($employee->avatar)) {
                Storage::disk('public')->delete($employee->avatar);
            }
            $updateData['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $employee->update($updateData);

        // Update Quota for current year
        $currentYear = date('Y');
        $quota = LeaveQuota::firstOrCreate(
            ['user_id' => $employee->id, 'year' => $currentYear],
            ['total_quota' => 12, 'used_quota' => 0, 'remaining_quota' => 12]
        );

        $newTotal = (int) $validated['total_quota'];
        $newRemaining = max(0, $newTotal - $quota->used_quota);

        $quota->update([
            'total_quota' => $newTotal,
            'remaining_quota' => $newRemaining,
        ]);

        return redirect()->back()->with('success', "Data karyawan {$employee->name} berhasil diperbarui.");
    }

    public function destroyEmployee($userId)
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            return back()->with('error', 'Akses khusus HRD.');
        }

        if ($userId == $user->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $employee = User::findOrFail($userId);
        $empName = $employee->name;

        if ($employee->avatar && Storage::disk('public')->exists($employee->avatar)) {
            Storage::disk('public')->delete($employee->avatar);
        }

        // Delete associated quotas & user record
        LeaveQuota::where('user_id', $employee->id)->delete();
        $employee->delete();

        return redirect()->back()->with('success', "Data karyawan {$empName} berhasil dihapus.");
    }

    public function updateQuota(Request $request, $userId)
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            return back()->with('error', 'Akses khusus HRD.');
        }

        $validated = $request->validate([
            'total_quota' => 'required|integer|min:0|max:100',
        ]);

        $currentYear = date('Y');
        $quota = LeaveQuota::firstOrCreate(
            ['user_id' => $userId, 'year' => $currentYear],
            ['total_quota' => 12, 'used_quota' => 0, 'remaining_quota' => 12]
        );

        $newTotal = (int) $validated['total_quota'];
        $newRemaining = max(0, $newTotal - $quota->used_quota);

        $quota->update([
            'total_quota' => $newTotal,
            'remaining_quota' => $newRemaining,
        ]);

        return back()->with('success', 'Kuota cuti berhasil diperbarui.');
    }

    public function export(Request $request)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isManager()) {
            return back()->with('error', 'Akses khusus HRD/Manager.');
        }

        $requests = LeaveRequest::with(['user.department', 'category', 'approver'])
            ->orderBy('created_at', 'desc')
            ->get();

        $fileName = 'Rekapitulasi_Pengajuan_Cuti_' . date('Ymd_His') . '.csv';

        $headers = [
            "Content-type" => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0",
        ];

        $callback = function () use ($requests) {
            $file = fopen('php://output', 'w');
            // UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, [
                'No Request',
                'NIK',
                'Nama Karyawan',
                'Departemen',
                'Kategori',
                'Mulai',
                'Selesai',
                'Jumlah',
                'Satuan',
                'Alasan',
                'Status',
                'Approver',
                'Catatan Approval',
                'Tanggal Approval',
            ]);

            foreach ($requests as $req) {
                fputcsv($file, [
                    $req->request_number,
                    $req->user->nik ?? '-',
                    $req->user->name,
                    $req->user->department->name ?? '-',
                    $req->category->name ?? '-',
                    $req->start_date ? $req->start_date->format('Y-m-d') : '-',
                    $req->end_date ? $req->end_date->format('Y-m-d') : '-',
                    $req->amount,
                    $req->unit,
                    $req->reason,
                    strtoupper($req->status),
                    $req->approver->name ?? '-',
                    $req->approval_note ?? '-',
                    $req->approved_at ? $req->approved_at->format('Y-m-d H:i') : '-',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
