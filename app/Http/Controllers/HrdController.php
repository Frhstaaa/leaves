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

        $query = User::with(['department', 'manager.department', 'currentQuota']);

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
        $departments = Department::all();
        $managers = User::whereIn('role', ['manager', 'admin', 'superadmin'])
            ->with('department')
            ->orderBy('name', 'asc')
            ->get();

        // Overall stats
        $allUsers = User::all();
        $stats = [
            'total_employees' => $allUsers->count(),
            'total_departments' => $departments->count(),
            'total_managers' => $allUsers->whereIn('role', ['manager', 'admin', 'superadmin'])->count(),
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
            'role' => 'required|in:employee,manager,admin,superadmin',
            'department_id' => 'nullable|exists:departments,id',
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
            $avatarPath = $this->convertAvatarToWebpAndStore($request->file('avatar'));
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
            'manager_id' => 'nullable|exists:users,id',
            'total_quota' => 'required|integer|min:0|max:100',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ], [
            'nik.unique' => 'NIK sudah digunakan oleh karyawan lain.',
            'email.unique' => 'Email sudah terdaftar dalam sistem.',
        ]);

        // Prevent self-assignment as manager
        if ($validated['manager_id'] == $employee->id) {
            $validated['manager_id'] = null;
        }

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
            $updateData['avatar'] = $this->convertAvatarToWebpAndStore($request->file('avatar'));
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

        if ($userId == $user->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $employee = User::findOrFail($userId);
        $empName = $employee->name;

        // Reset subordinates' manager_id to null
        User::where('manager_id', $employee->id)->update(['manager_id' => null]);

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

        $query = LeaveRequest::with(['user.department', 'user.manager', 'category', 'approver'])
            ->orderBy('created_at', 'desc');

        if ($user->isManager() && !$user->isAdmin()) {
            $subordinateIds = User::where(function ($q) use ($user) {
                $q->where('manager_id', $user->id)
                  ->orWhere(function ($sub) use ($user) {
                      $sub->whereNull('manager_id')->where('department_id', $user->department_id);
                  });
            })->where('id', '!=', $user->id)->pluck('id');

            $query->whereIn('user_id', $subordinateIds);
        }

        $requests = $query->get();

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
                'Atasan Direct',
                'Kategori',
                'Mulai',
                'Selesai',
                'Jumlah',
                'Satuan',
                'Alasan',
                'Status',
                'Disetujui Oleh',
                'Catatan Approval',
                'Tanggal Approval',
            ]);

            foreach ($requests as $req) {
                fputcsv($file, [
                    $req->request_number,
                    $req->user->nik ?? '-',
                    $req->user->name,
                    $req->user->department->name ?? '-',
                    $req->user->manager->name ?? '-',
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

    public function downloadTemplate()
    {
        $fileName = 'Template_Import_Karyawan_SGIN.csv';

        $headers = [
            "Content-type" => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0",
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header row
            fputcsv($file, [
                'NIK',
                'Nama Karyawan',
                'Email',
                'Password',
                'Role (employee/manager/admin)',
                'Kode atau Nama Departemen',
                'NIK atau Email Atasan',
                'Kuota Cuti Tahunan',
            ]);

            // Sample row 1 (Manager)
            fputcsv($file, [
                'MGR-101',
                'Hendra Setiawan',
                'hendra@sgin.com',
                'password123',
                'manager',
                'Information Technology',
                '',
                '12',
            ]);

            // Sample row 2 (Employee with direct manager)
            fputcsv($file, [
                'EMP-201',
                'Budi Santoso',
                'budi@sgin.com',
                'password123',
                'employee',
                'Information Technology',
                'MGR-101',
                '12',
            ]);

            // Sample row 3 (HRD Admin)
            fputcsv($file, [
                'ADM-001',
                'Siti Rahmawati',
                'admin@sgin.com',
                'password123',
                'admin',
                'HR & GA',
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
        $allUsers = User::all();

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
            $managerInput = trim($row[6] ?? '');
            $quotaInput = (int) trim($row[7] ?? 12);

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

            // Find Manager
            $managerId = null;
            if (!empty($managerInput)) {
                $matchedManager = User::where(function ($q) use ($managerInput) {
                    $q->where('nik', $managerInput)
                      ->orWhere('email', $managerInput)
                      ->orWhere('name', $managerInput);
                })->first();

                if ($matchedManager) {
                    $managerId = $matchedManager->id;
                }
            }

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
