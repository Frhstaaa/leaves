<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\LeaveCategory;
use App\Models\LeaveQuota;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\MediaOptimizer;
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

        $query = LeaveRequest::with(['user.department', 'user.manager', 'category', 'approver']);

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

        // Calculate Stats with single fast SQL aggregation
        $statRow = LeaveRequest::selectRaw('
            COUNT(*) as total,
            COUNT(CASE WHEN status = "pending" THEN 1 END) as pending,
            COUNT(CASE WHEN status = "approved" THEN 1 END) as approved,
            COUNT(CASE WHEN status = "rejected" THEN 1 END) as rejected,
            COALESCE(SUM(CASE WHEN status = "approved" THEN amount ELSE 0 END), 0) as total_days_used
        ')->first();

        $stats = [
            'total' => (int) ($statRow->total ?? 0),
            'pending' => (int) ($statRow->pending ?? 0),
            'approved' => (int) ($statRow->approved ?? 0),
            'rejected' => (int) ($statRow->rejected ?? 0),
            'total_days_used' => (float) ($statRow->total_days_used ?? 0),
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

        $query = User::with([
            'department.manager',
            'department.approver1',
            'department.approver2',
            'manager.department',
            'approver1.department',
            'approver2.department',
            'currentQuota'
        ]);

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

        if ($role && in_array($role, ['employee', 'manager', 'admin', 'superadmin'])) {
            $query->where('role', $role);
        }

        $employees = $query->orderBy('name', 'asc')->get();
        $departments = Department::with(['manager', 'approver1', 'approver2'])->get();
        $managers = User::whereIn('role', ['manager', 'admin', 'superadmin'])
            ->with('department')
            ->orderBy('name', 'asc')
            ->get();

        // Overall stats with fast query
        $userStat = User::selectRaw('
            COUNT(*) as total_employees,
            COUNT(CASE WHEN role IN ("manager", "admin", "superadmin") THEN 1 END) as total_managers
        ')->first();

        $stats = [
            'total_employees' => (int) ($userStat->total_employees ?? 0),
            'total_departments' => $departments->count(),
            'total_managers' => (int) ($userStat->total_managers ?? 0),
            'active_quotas' => (float) LeaveQuota::where('year', date('Y'))->sum('remaining_quota'),
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
            'role' => 'required|in:employee,manager,admin,superadmin',
            'department_id' => 'nullable|exists:departments,id',
            'approver_1_id' => 'nullable|exists:users,id',
            'approver_2_id' => 'nullable|exists:users,id',
            'manager_id' => 'nullable|exists:users,id',
            'total_quota' => 'required|integer|min:0|max:100',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], [
            'nik.unique' => 'NIK sudah digunakan oleh karyawan lain.',
            'email.unique' => 'Email sudah terdaftar dalam sistem.',
            'password.min' => 'Password minimal 6 karakter.',
        ]);

        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $avatarPath = MediaOptimizer::convertImageToWebp($request->file('avatar'), 'avatars', 85, 400, 400);
        }

        $approver1 = $validated['approver_1_id'] ?? null;
        $approver2 = $validated['approver_2_id'] ?? $validated['manager_id'] ?? null;
        $managerId = $approver2 ?? $approver1;

        $newEmployee = User::create([
            'name' => $validated['name'],
            'nik' => $validated['nik'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'department_id' => $validated['department_id'] ?? null,
            'approver_1_id' => $approver1,
            'approver_2_id' => $approver2,
            'manager_id' => $managerId,
            'avatar' => $avatarPath,
        ]);

        // Sync Spatie Role
        try {
            $newEmployee->syncRoles([$validated['role']]);
        } catch (\Throwable $e) {}

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
            'role' => 'required|in:employee,manager,admin,superadmin',
            'department_id' => 'nullable|exists:departments,id',
            'approver_1_id' => 'nullable|exists:users,id',
            'approver_2_id' => 'nullable|exists:users,id',
            'manager_id' => 'nullable|exists:users,id',
            'total_quota' => 'required|integer|min:0|max:100',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], [
            'nik.unique' => 'NIK sudah digunakan oleh karyawan lain.',
            'email.unique' => 'Email sudah terdaftar dalam sistem.',
        ]);

        $approver1 = $validated['approver_1_id'] ?? null;
        $approver2 = $validated['approver_2_id'] ?? $validated['manager_id'] ?? null;

        // Prevent self-assignment as approvers
        if ($approver1 == $employee->id) $approver1 = null;
        if ($approver2 == $employee->id) $approver2 = null;
        $managerId = $approver2 ?? $approver1;

        $updateData = [
            'name' => $validated['name'],
            'nik' => $validated['nik'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'department_id' => $validated['department_id'] ?? null,
            'approver_1_id' => $approver1,
            'approver_2_id' => $approver2,
            'manager_id' => $managerId,
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        if ($request->hasFile('avatar')) {
            if ($employee->avatar && Storage::disk('public')->exists($employee->avatar)) {
                Storage::disk('public')->delete($employee->avatar);
            }
            $updateData['avatar'] = MediaOptimizer::convertImageToWebp($request->file('avatar'), 'avatars', 85, 400, 400);
        }

        $employee->update($updateData);

        // Sync Spatie Role
        try {
            $employee->syncRoles([$validated['role']]);
        } catch (\Throwable $e) {}

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

        $employee = User::findOrFail($userId);

        if ($employee->id === $user->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        // Unlink references before deleting
        User::where('approver_1_id', $employee->id)->update(['approver_1_id' => null]);
        User::where('approver_2_id', $employee->id)->update(['approver_2_id' => null]);
        User::where('manager_id', $employee->id)->update(['manager_id' => null]);
        Department::where('manager_id', $employee->id)->update(['manager_id' => null]);

        // Delete related quotas
        LeaveQuota::where('user_id', $employee->id)->delete();

        // Delete avatar if exists
        if ($employee->avatar && Storage::disk('public')->exists($employee->avatar)) {
            Storage::disk('public')->delete($employee->avatar);
        }

        $employeeName = $employee->name;
        $employee->delete();

        return redirect()->back()->with('success', "Karyawan {$employeeName} berhasil dihapus dari sistem.");
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

        $employee = User::findOrFail($userId);
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

        return redirect()->back()->with('success', "Kuota cuti {$employee->name} tahun {$currentYear} berhasil diubah menjadi {$newTotal} hari (Sisa: {$newRemaining} hari).");
    }

    public function export(Request $request)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && !$user->isManager()) {
            return back()->with('error', 'Akses ditolak.');
        }

        $fileName = 'rekap_cuti_sgin_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $query = LeaveRequest::with(['user.department', 'user.manager', 'category', 'approver']);

        if ($request->has('department_id') && $request->department_id) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }

        if ($request->has('status') && in_array($request->status, ['pending', 'approved', 'rejected'])) {
            $query->where('status', $request->status);
        }

        $requests = $query->orderBy('created_at', 'desc')->get();

        $callback = function () use ($requests) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'No Request',
                'NIK Karyawan',
                'Nama Karyawan',
                'Departemen',
                'Kategori',
                'Jenis Pengajuan',
                'Tgl Mulai',
                'Tgl Selesai',
                'Jumlah',
                'Satuan',
                'Status',
                'Tahapan',
                'Approver Akhir',
                'Tgl Approve',
                'Alasan',
                'Catatan Approver',
            ]);

            foreach ($requests as $r) {
                fputcsv($file, [
                    $r->request_number,
                    $r->user->nik ?? '-',
                    $r->user->name,
                    $r->user->department->name ?? 'General',
                    $r->category->name ?? '-',
                    $r->submission_type,
                    $r->start_date,
                    $r->end_date,
                    $r->amount,
                    $r->unit,
                    strtoupper($r->status),
                    strtoupper($r->current_stage ?? 'HRD'),
                    $r->approver->name ?? '-',
                    $r->approved_at ? $r->approved_at->format('Y-m-d H:i:s') : '-',
                    $r->reason,
                    $r->approval_note ?? '-',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function downloadTemplate()
    {
        $fileName = 'template_import_karyawan_sgin.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // CSV Header with 3-tier approval columns
            fputcsv($file, [
                'NIK',
                'Nama Karyawan',
                'Email',
                'Password',
                'Role (employee/manager/admin/superadmin)',
                'Kode/Nama Departemen',
                'NIK/Email Atasan 1 (Supervisor/Lead)',
                'NIK/Email Atasan 2 (Manager/Dept Head)',
                'Jatah Kuota Cuti',
            ]);

            // Sample 1: Manager (1 Level Approval -> Direct HRD)
            fputcsv($file, [
                'MGR-101',
                'Ahmad Dahlan, S.T.',
                'ahmad.dahlan@sgin.com',
                'password123',
                'manager',
                'Information Technology',
                '',
                '',
                '12',
            ]);

            // Sample 2: Supervisor (2 Level Approval -> Manager -> HRD)
            fputcsv($file, [
                'SPV-201',
                'Budi Santoso',
                'budi.santoso@sgin.com',
                'password123',
                'employee',
                'Information Technology',
                '',
                'MGR-101',
                '12',
            ]);

            // Sample 3: Staf (3 Level Approval -> Supervisor -> Manager -> HRD)
            fputcsv($file, [
                'EMP-301',
                'Rian Pratama',
                'rian.pratama@sgin.com',
                'password123',
                'employee',
                'Information Technology',
                'SPV-201',
                'MGR-101',
                '12',
            ]);

            // Sample 4: HRD Admin
            fputcsv($file, [
                'ADM-001',
                'Citra Lestari, S.Psi',
                'citra.lestari@sgin.com',
                'password123',
                'admin',
                'Human Resources & PGA',
                '',
                '',
                '12',
            ]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function importEmployees(Request $request)
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            return back()->with('error', 'Akses ditolak. Fitur ini khusus untuk HRD / Admin.');
        }

        $request->validate([
            'file' => 'required|file|max:10240',
        ]);

        $file = $request->file('file');
        $filePath = $file->getRealPath();

        $content = file_get_contents($filePath);
        if ($content === false || empty(trim($content))) {
            return back()->with('error', 'File yang diunggah kosong atau tidak dapat dibaca.');
        }

        // Auto-detect delimiter: comma, semicolon, or tab
        $firstLine = strtok($content, "\r\n");
        $commaCount = substr_count($firstLine, ',');
        $semicolonCount = substr_count($firstLine, ';');
        $tabCount = substr_count($firstLine, "\t");

        $delimiter = ',';
        if ($semicolonCount > $commaCount && $semicolonCount > $tabCount) {
            $delimiter = ';';
        } elseif ($tabCount > $commaCount && $tabCount > $semicolonCount) {
            $delimiter = "\t";
        }

        $handle = fopen($filePath, 'r');

        // Remove UTF-8 BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $successCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $rowNumber = 0;

        $currentYear = date('Y');
        $departments = Department::all();

        while (($row = fgetcsv($handle, 2000, $delimiter)) !== false) {
            $rowNumber++;

            // Skip header row
            if ($rowNumber === 1 && (isset($row[0]) && preg_match('/(nik|nama|email)/i', $row[0]))) {
                continue;
            }

            // Skip empty rows
            if (empty(array_filter($row, fn($val) => trim($val) !== ''))) {
                continue;
            }

            $nik = trim($row[0] ?? '');
            $name = trim($row[1] ?? '');
            $email = trim($row[2] ?? '');
            $password = trim($row[3] ?? '');
            $roleInput = strtolower(trim($row[4] ?? 'employee'));
            $deptInput = trim($row[5] ?? '');
            $approver1Input = trim($row[6] ?? '');
            $approver2Input = trim($row[7] ?? '');
            $quotaInput = isset($row[8]) ? (int) trim($row[8]) : (isset($row[7]) && is_numeric(trim($row[7])) ? (int) trim($row[7]) : 12);

            if (empty($name) || empty($email)) {
                $skippedCount++;
                continue;
            }

            // Generate NIK if empty
            if (empty($nik)) {
                $nik = 'EMP-' . date('Y') . '-' . rand(100, 999);
            }

            // Normalize Role
            $role = in_array($roleInput, ['employee', 'manager', 'admin', 'superadmin']) ? $roleInput : 'employee';

            // Find Department
            $departmentId = null;
            if (!empty($deptInput)) {
                $matchedDept = $departments->first(function ($d) use ($deptInput) {
                    return strcasecmp($d->code, $deptInput) === 0 ||
                           strcasecmp($d->name, $deptInput) === 0 ||
                           $d->id == $deptInput;
                });
                $departmentId = $matchedDept ? $matchedDept->id : null;
            }

            // Find Approver 1
            $approver1Id = null;
            if (!empty($approver1Input)) {
                $m1 = User::where(function ($q) use ($approver1Input) {
                    $q->where('nik', $approver1Input)
                      ->orWhere('email', $approver1Input)
                      ->orWhere('name', $approver1Input);
                })->first();
                if ($m1) $approver1Id = $m1->id;
            }

            // Find Approver 2
            $approver2Id = null;
            if (!empty($approver2Input) && !is_numeric($approver2Input)) {
                $m2 = User::where(function ($q) use ($approver2Input) {
                    $q->where('nik', $approver2Input)
                      ->orWhere('email', $approver2Input)
                      ->orWhere('name', $approver2Input);
                })->first();
                if ($m2) $approver2Id = $m2->id;
            }

            $managerId = $approver2Id ?? $approver1Id;

            // Default password
            if (empty($password)) {
                $password = 'password123';
            }

            $totalQuota = $quotaInput > 0 ? $quotaInput : 12;

            // Check if user already exists by NIK or Email
            $existingUser = User::where('email', $email)->orWhere('nik', $nik)->first();

            if ($existingUser) {
                // Update existing user
                $updatePayload = [
                    'name' => $name,
                    'role' => $role,
                ];
                if ($departmentId) $updatePayload['department_id'] = $departmentId;
                if ($approver1Id && $approver1Id != $existingUser->id) $updatePayload['approver_1_id'] = $approver1Id;
                if ($approver2Id && $approver2Id != $existingUser->id) $updatePayload['approver_2_id'] = $approver2Id;
                if ($managerId && $managerId != $existingUser->id) $updatePayload['manager_id'] = $managerId;
                if (!empty($password) && $password !== 'password123') {
                    $updatePayload['password'] = Hash::make($password);
                }

                $existingUser->update($updatePayload);

                try {
                    $existingUser->syncRoles([$role]);
                } catch (\Throwable $e) {}

                // Update quota
                $quota = LeaveQuota::firstOrCreate(
                    ['user_id' => $existingUser->id, 'year' => $currentYear],
                    ['total_quota' => $totalQuota, 'used_quota' => 0, 'remaining_quota' => $totalQuota]
                );
                $quota->update([
                    'total_quota' => $totalQuota,
                    'remaining_quota' => max(0, $totalQuota - $quota->used_quota),
                ]);

                $updatedCount++;
            } else {
                // Create New User
                $newEmp = User::create([
                    'nik' => $nik,
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make($password),
                    'role' => $role,
                    'department_id' => $departmentId,
                    'approver_1_id' => $approver1Id,
                    'approver_2_id' => $approver2Id,
                    'manager_id' => $managerId,
                ]);

                try {
                    $newEmp->syncRoles([$role]);
                } catch (\Throwable $e) {}

                LeaveQuota::create([
                    'user_id' => $newEmp->id,
                    'year' => $currentYear,
                    'total_quota' => $totalQuota,
                    'used_quota' => 0,
                    'remaining_quota' => $totalQuota,
                ]);

                $successCount++;
            }
        }

        fclose($handle);

        $message = "Import selesai! ";
        if ($successCount > 0) $message .= "{$successCount} karyawan baru ditambahkan. ";
        if ($updatedCount > 0) $message .= "{$updatedCount} data karyawan diperbarui. ";
        if ($skippedCount > 0) $message .= "{$skippedCount} baris dilewati (data tidak lengkap).";

        return redirect()->back()->with('success', trim($message));
    }

    public function departments(Request $request)
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            return redirect()->route('dashboard')->with('error', 'Halaman ini khusus untuk HRD / PGA Admin.');
        }

        $search = $request->query('search');

        $query = Department::with(['manager', 'approver1', 'approver2'])
            ->withCount('employees');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $departments = $query->orderBy('name', 'asc')->get();

        $employees = User::select('id', 'name', 'nik', 'email', 'role', 'department_id')
            ->orderBy('name', 'asc')
            ->get();

        $stats = [
            'total_departments' => $departments->count(),
            'total_employees' => User::count(),
            'with_manager' => $departments->whereNotNull('manager_id')->count(),
            'multi_tier_count' => $departments->where('approval_type', '3_tier')->count(),
        ];

        return Inertia::render('HRD/Departments', [
            'departments' => $departments,
            'employees' => $employees,
            'stats' => $stats,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function storeDepartment(Request $request)
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            return redirect()->route('dashboard')->with('error', 'Halaman ini khusus untuk HRD / PGA Admin.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:departments,name',
            'code' => 'required|string|max:50|unique:departments,code',
            'manager_id' => 'nullable',
            'approver_1_id' => 'nullable',
            'approver_2_id' => 'nullable',
            'approval_type' => 'required|in:3_tier,2_tier,1_tier,custom',
            'description' => 'nullable|string|max:1000',
        ], [
            'name.required' => 'Nama departemen wajib diisi.',
            'name.unique' => 'Nama departemen sudah digunakan.',
            'code.required' => 'Kode departemen wajib diisi.',
            'code.unique' => 'Kode departemen sudah digunakan.',
            'approval_type.required' => 'Tipe alur persetujuan wajib dipilih.',
        ]);

        $managerId = (!empty($validated['manager_id']) && $validated['manager_id'] !== '0' && $validated['manager_id'] !== '') ? (int) $validated['manager_id'] : null;
        $approver1Id = (!empty($validated['approver_1_id']) && $validated['approver_1_id'] !== '0' && $validated['approver_1_id'] !== '') ? (int) $validated['approver_1_id'] : null;
        $approver2Id = (!empty($validated['approver_2_id']) && $validated['approver_2_id'] !== '0' && $validated['approver_2_id'] !== '') ? (int) $validated['approver_2_id'] : null;

        if ($managerId && !$approver2Id) {
            $approver2Id = $managerId;
        }

        if ($validated['approval_type'] === '2_tier') {
            $approver1Id = null;
        } elseif ($validated['approval_type'] === '1_tier') {
            $approver1Id = null;
            $approver2Id = null;
            $managerId = null;
        }

        $dept = Department::create([
            'name' => trim($validated['name']),
            'code' => strtoupper(trim($validated['code'])),
            'manager_id' => $managerId,
            'approver_1_id' => $approver1Id,
            'approver_2_id' => $approver2Id,
            'approval_type' => $validated['approval_type'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->back()->with('success', "Departemen '{$dept->name}' ({$dept->code}) berhasil ditambahkan!");
    }

    public function updateDepartment(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            return redirect()->route('dashboard')->with('error', 'Halaman ini khusus untuk HRD / PGA Admin.');
        }

        $dept = Department::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:departments,name,' . $dept->id,
            'code' => 'required|string|max:50|unique:departments,code,' . $dept->id,
            'manager_id' => 'nullable',
            'approver_1_id' => 'nullable',
            'approver_2_id' => 'nullable',
            'approval_type' => 'required|in:3_tier,2_tier,1_tier,custom',
            'description' => 'nullable|string|max:1000',
        ], [
            'name.required' => 'Nama departemen wajib diisi.',
            'name.unique' => 'Nama departemen sudah digunakan.',
            'code.required' => 'Kode departemen wajib diisi.',
            'code.unique' => 'Kode departemen sudah digunakan.',
            'approval_type.required' => 'Tipe alur persetujuan wajib dipilih.',
        ]);

        $managerId = (!empty($validated['manager_id']) && $validated['manager_id'] !== '0' && $validated['manager_id'] !== '') ? (int) $validated['manager_id'] : null;
        $approver1Id = (!empty($validated['approver_1_id']) && $validated['approver_1_id'] !== '0' && $validated['approver_1_id'] !== '') ? (int) $validated['approver_1_id'] : null;
        $approver2Id = (!empty($validated['approver_2_id']) && $validated['approver_2_id'] !== '0' && $validated['approver_2_id'] !== '') ? (int) $validated['approver_2_id'] : null;

        if ($managerId && !$approver2Id) {
            $approver2Id = $managerId;
        }

        if ($validated['approval_type'] === '2_tier') {
            $approver1Id = null;
        } elseif ($validated['approval_type'] === '1_tier') {
            $approver1Id = null;
            $approver2Id = null;
            $managerId = null;
        }

        $dept->update([
            'name' => trim($validated['name']),
            'code' => strtoupper(trim($validated['code'])),
            'manager_id' => $managerId,
            'approver_1_id' => $approver1Id,
            'approver_2_id' => $approver2Id,
            'approval_type' => $validated['approval_type'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->back()->with('success', "Data dan alur persetujuan Departemen '{$dept->name}' berhasil diperbarui!");
    }

    public function destroyDepartment($id)
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            return redirect()->route('dashboard')->with('error', 'Halaman ini khusus untuk HRD / PGA Admin.');
        }

        $dept = Department::withCount('employees')->findOrFail($id);

        if ($dept->employees_count > 0) {
            return redirect()->back()->with('error', "Departemen '{$dept->name}' memiliki {$dept->employees_count} karyawan aktif. Pindahkan karyawan terlebih dahulu sebelum menghapus departemen.");
        }

        $deptName = $dept->name;
        $dept->delete();

        return redirect()->back()->with('success', "Departemen '{$deptName}' berhasil dihapus.");
    }

    private function convertAvatarToWebpAndStore($file, string $folder = 'avatars', int $quality = 85): string
    {
        $mime = $file->getMimeType();
        $realPath = $file->getRealPath();

        $image = match ($mime) {
            'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($realPath),
            'image/png' => @imagecreatefrompng($realPath),
            'image/webp' => @imagecreatefromwebp($realPath),
            default => null,
        };

        $filename = $folder . '/' . uniqid('avatar_') . '_' . time() . '.webp';

        if ($image && function_exists('imagewebp')) {
            imagealphablending($image, true);
            imagesavealpha($image, true);

            ob_start();
            imagewebp($image, null, $quality);
            $webpContent = ob_get_clean();
            imagedestroy($image);

            Storage::disk('public')->put($filename, $webpContent);
            return $filename;
        }

        return $file->store($folder, 'public');
    }
}
