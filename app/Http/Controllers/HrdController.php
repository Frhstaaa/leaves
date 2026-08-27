<?php

namespace App\Http\Controllers;

use App\Models\LeaveCategory;
use App\Models\LeaveQuota;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use App\Repositories\Contracts\LeaveRequestRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\HrdEmployeeService;
use App\Services\LeaveQuotaService;
use App\Services\LeaveRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HrdController extends Controller
{
    protected LeaveRequestRepositoryInterface $leaveRequestRepo;
    protected UserRepositoryInterface $userRepo;
    protected DepartmentRepositoryInterface $departmentRepo;
    protected LeaveRequestService $leaveRequestService;
    protected HrdEmployeeService $employeeService;
    protected LeaveQuotaService $quotaService;

    public function __construct(
        LeaveRequestRepositoryInterface $leaveRequestRepo,
        UserRepositoryInterface $userRepo,
        DepartmentRepositoryInterface $departmentRepo,
        LeaveRequestService $leaveRequestService,
        HrdEmployeeService $employeeService,
        LeaveQuotaService $quotaService
    ) {
        $this->leaveRequestRepo = $leaveRequestRepo;
        $this->userRepo = $userRepo;
        $this->departmentRepo = $departmentRepo;
        $this->leaveRequestService = $leaveRequestService;
        $this->employeeService = $employeeService;
        $this->quotaService = $quotaService;
    }

    public function index(Request $request): Response
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            abort(403, 'Halaman ini khusus untuk HRD / PGA Admin.');
        }

        $filters = [
            'department_id' => $request->query('department_id', ''),
            'status' => $request->query('status', ''),
            'category_id' => $request->query('category_id', ''),
            'search' => $request->query('search', ''),
        ];

        $requests = $this->leaveRequestRepo->getHrdRecapPaginated($filters, 15);
        $departments = $this->departmentRepo->getAll();
        $categories = LeaveCategory::all();

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
            'filters' => $filters,
        ]);
    }

    public function employees(Request $request): Response
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            abort(403, 'Akses khusus HRD.');
        }

        $filters = [
            'search' => $request->query('search', ''),
            'department_id' => $request->query('department_id', ''),
            'role' => $request->query('role', ''),
        ];

        $employees = $this->userRepo->getEmployeesAll($filters);
        $departments = $this->departmentRepo->getAll();
        $managers = $this->userRepo->getManagers();

        $roles = class_exists('\\Spatie\\Permission\\Models\\Role')
            ? \Spatie\Permission\Models\Role::orderBy('name')->get(['id', 'name', 'guard_name'])->map(function ($r) {
                return [
                    'id' => $r->id,
                    'name' => $r->name,
                    'display_name' => ucfirst(str_replace(['_', '-'], ' ', $r->name)),
                ];
            })
            : collect();

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
            'roles' => $roles,
            'stats' => $stats,
            'filters' => $filters,
        ]);
    }

    public function storeEmployee(Request $request): RedirectResponse
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
            'role' => 'required|string|max:50',
            'department_id' => 'nullable|exists:departments,id',
            'approver_1_id' => 'nullable|exists:users,id',
            'approver_2_id' => 'nullable|exists:users,id',
            'manager_id' => 'nullable|exists:users,id',
            'total_quota' => 'required|integer|min:0|max:100',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $newEmployee = $this->employeeService->createEmployee($validated, $request->file('avatar'));

        return redirect()->back()->with('success', "Karyawan {$newEmployee->name} ({$newEmployee->nik}) berhasil ditambahkan.");
    }

    public function updateEmployee(Request $request, int $userId): RedirectResponse
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            return back()->with('error', 'Akses khusus HRD.');
        }

        $employee = $this->userRepo->findById($userId);
        if (!$employee) {
            return back()->with('error', 'Karyawan tidak ditemukan.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'nik' => 'required|string|max:50|unique:users,nik,' . $employee->id,
            'email' => 'required|email|max:255|unique:users,email,' . $employee->id,
            'password' => 'nullable|string|min:6',
            'role' => 'required|string|max:50',
            'department_id' => 'nullable|exists:departments,id',
            'approver_1_id' => 'nullable|exists:users,id',
            'approver_2_id' => 'nullable|exists:users,id',
            'manager_id' => 'nullable|exists:users,id',
            'total_quota' => 'required|integer|min:0|max:100',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $this->employeeService->updateEmployee($employee, $validated, $request->file('avatar'));

        return redirect()->back()->with('success', "Data karyawan {$employee->name} berhasil diperbarui.");
    }

    public function destroyEmployee(int $userId): RedirectResponse
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            return back()->with('error', 'Akses khusus HRD.');
        }

        $employee = $this->userRepo->findById($userId);
        if (!$employee) {
            return back()->with('error', 'Karyawan tidak ditemukan.');
        }

        if ($employee->id === $user->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $name = $employee->name;
        $this->employeeService->deleteEmployee($employee);

        return redirect()->back()->with('success', "Karyawan {$name} berhasil dihapus dari sistem.");
    }

    public function updateQuota(Request $request, int $userId): RedirectResponse
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            return back()->with('error', 'Akses khusus HRD.');
        }

        $validated = $request->validate([
            'total_quota' => 'required|integer|min:0|max:100',
        ]);

        $employee = $this->userRepo->findById($userId);
        if (!$employee) {
            return back()->with('error', 'Karyawan tidak ditemukan.');
        }

        $quota = $this->quotaService->setTotalQuota($employee->id, (int) $validated['total_quota']);

        return redirect()->back()->with('success', "Kuota cuti {$employee->name} berhasil diubah menjadi {$quota->total_quota} hari (Sisa: {$quota->remaining_quota} hari).");
    }

    public function overrideStatus(Request $request, int $id): RedirectResponse
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            return redirect()->back()->with('error', 'Akses khusus HRD / Admin.');
        }

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected,pending',
            'stage' => 'nullable|in:approval_1,approval_2,hrd,completed',
            'note' => 'nullable|string|max:500',
        ]);

        $leaveRequest = $this->leaveRequestRepo->findById($id);
        if (!$leaveRequest) {
            return redirect()->back()->with('error', 'Pengajuan tidak ditemukan.');
        }

        try {
            $result = $this->leaveRequestService->overrideStatus(
                $leaveRequest,
                $user,
                $validated['status'],
                $validated['stage'] ?? null,
                $validated['note'] ?? null
            );
            return redirect()->back()->with('success', $result['message']);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function departments(Request $request): Response
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            abort(403, 'Halaman ini khusus untuk HRD / PGA Admin.');
        }

        $departments = $this->departmentRepo->getAll();
        $employees = User::select('id', 'name', 'nik', 'email', 'role', 'department_id')->orderBy('name')->get();

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
        ]);
    }

    public function storeDepartment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:departments,code',
            'manager_id' => 'nullable',
            'approver_1_id' => 'nullable',
            'approver_2_id' => 'nullable',
            'approval_type' => 'nullable|in:3_tier,2_tier,1_tier,custom',
            'description' => 'nullable|string|max:1000',
        ]);

        $sanitizer = function($val) {
            if (empty($val) || $val === '0' || $val === 'none' || !is_numeric($val)) return null;
            $intVal = (int) $val;
            return ($intVal > 0 && \App\Models\User::where('id', $intVal)->exists()) ? $intVal : null;
        };

        $managerId = $sanitizer($validated['manager_id'] ?? null);
        $approver1Id = $sanitizer($validated['approver_1_id'] ?? null);
        $approver2Id = $sanitizer($validated['approver_2_id'] ?? null);

        if ($managerId && !$approver2Id) $approver2Id = $managerId;

        $createData = [
            'name' => trim($validated['name']),
            'code' => strtoupper(trim($validated['code'])),
            'manager_id' => $managerId,
        ];

        if (\Illuminate\Support\Facades\Schema::hasColumn('departments', 'approver_1_id')) {
            $createData['approver_1_id'] = $approver1Id;
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('departments', 'approver_2_id')) {
            $createData['approver_2_id'] = $approver2Id;
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('departments', 'approval_type')) {
            $createData['approval_type'] = $validated['approval_type'] ?? '3_tier';
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('departments', 'description')) {
            $createData['description'] = $validated['description'] ?? null;
        }

        try {
            $dept = $this->departmentRepo->create($createData);
            return redirect()->back()->with('success', "Departemen '{$dept->name}' ({$dept->code}) berhasil ditambahkan!");
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Store Department Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal menambahkan departemen: ' . $e->getMessage());
        }
    }

    public function updateDepartment(Request $request, int $id): RedirectResponse
    {
        $dept = $this->departmentRepo->findById($id);
        if (!$dept) {
            return back()->with('error', 'Departemen tidak ditemukan.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:departments,code,' . $dept->id,
            'manager_id' => 'nullable',
            'approver_1_id' => 'nullable',
            'approver_2_id' => 'nullable',
            'approval_type' => 'nullable|in:3_tier,2_tier,1_tier,custom',
            'description' => 'nullable|string|max:1000',
        ]);

        $sanitizer = function($val) {
            if (empty($val) || $val === '0' || $val === 'none' || !is_numeric($val)) return null;
            $intVal = (int) $val;
            return ($intVal > 0 && \App\Models\User::where('id', $intVal)->exists()) ? $intVal : null;
        };

        $managerId = $sanitizer($validated['manager_id'] ?? null);
        $approver1Id = $sanitizer($validated['approver_1_id'] ?? null);
        $approver2Id = $sanitizer($validated['approver_2_id'] ?? null);

        if ($managerId && !$approver2Id) $approver2Id = $managerId;

        $updateData = [
            'name' => trim($validated['name']),
            'code' => strtoupper(trim($validated['code'])),
            'manager_id' => $managerId,
        ];

        if (\Illuminate\Support\Facades\Schema::hasColumn('departments', 'approver_1_id')) {
            $updateData['approver_1_id'] = $approver1Id;
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('departments', 'approver_2_id')) {
            $updateData['approver_2_id'] = $approver2Id;
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('departments', 'approval_type')) {
            $updateData['approval_type'] = $validated['approval_type'] ?? '3_tier';
        }
        if (\Illuminate\Support\Facades\Schema::hasColumn('departments', 'description')) {
            $updateData['description'] = $validated['description'] ?? null;
        }

        try {
            $this->departmentRepo->update($dept, $updateData);
            return redirect()->back()->with('success', "Departemen '{$dept->name}' berhasil diperbarui!");
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Update Department Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal memperbarui departemen: ' . $e->getMessage());
        }
    }

    public function destroyDepartment(int $id): RedirectResponse
    {
        $dept = $this->departmentRepo->findById($id);
        if (!$dept) {
            return back()->with('error', 'Departemen tidak ditemukan.');
        }

        if ($dept->users()->count() > 0) {
            return back()->with('error', "Departemen '{$dept->name}' masih memiliki anggota karyawan aktif.");
        }

        $name = $dept->name;
        $this->departmentRepo->delete($dept);

        return redirect()->back()->with('success', "Departemen '{$name}' berhasil dihapus.");
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = [
            'department_id' => $request->query('department_id'),
            'status' => $request->query('status'),
            'category_id' => $request->query('category_id'),
            'search' => $request->query('search'),
        ];

        $requests = $this->leaveRequestRepo->getHrdRecapAll($filters);
        $fileName = 'rekap_cuti_sgin_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        return response()->stream(function () use ($requests) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['No Request', 'NIK', 'Nama', 'Departemen', 'Kategori', 'Jenis', 'Mulai', 'Selesai', 'Jumlah', 'Satuan', 'Status', 'Tahapan', 'Alasan']);

            foreach ($requests as $r) {
                fputcsv($file, [
                    $r->request_number,
                    $r->user->nik ?? '-',
                    $r->user->name ?? '-',
                    $r->user->department->name ?? 'General',
                    $r->category->name ?? '-',
                    $r->submission_type,
                    $r->start_date,
                    $r->end_date,
                    $r->amount,
                    $r->unit,
                    strtoupper($r->status),
                    strtoupper($r->current_stage ?? 'HRD'),
                    $r->reason,
                ]);
            }
            fclose($file);
        }, 200, $headers);
    }

    public function downloadTemplate(): StreamedResponse
    {
        $fileName = 'template_import_karyawan_sgin.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        return response()->stream(function () {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            $departments = $this->departmentRepo->getAll();
            $deptList = $departments->map(fn($d) => "{$d->code}: {$d->name}")->implode(' | ');
            if (empty($deptList)) {
                $deptList = 'IT: Information Technology | HRD: Human Resources | PROD: Production | GA: General Affairs';
            }

            $dbRoles = class_exists('\\Spatie\\Permission\\Models\\Role') ? \Spatie\Permission\Models\Role::pluck('name')->toArray() : [];
            $roleOptions = array_unique(array_merge(['employee', 'manager', 'supervisor', 'admin', 'superadmin'], $dbRoles));
            $roleList = implode(' | ', $roleOptions);

            // =========================================================================================================
            // 1. HEADER PANDUAN & TUTORIAL PENGISIAN
            // =========================================================================================================
            fputcsv($file, ['# =========================================================================================================']);
            fputcsv($file, ['# TEMPLATE IMPORT KARYAWAN CEPAT - PT SUGIYAMA INDONESIA (SGIN LEAVES APPLICATION)']);
            fputcsv($file, ['# =========================================================================================================']);
            fputcsv($file, ['# [PANDUAN & ATURAN PENGISIAN]:']);
            fputcsv($file, ['# 1. KOLOM WAJIB DIISI : "Nama Lengkap Karyawan" dan "Email Login Akun".']);
            fputcsv($file, ['# 2. KOLOM OPSIONAL     : NIK SGIN, Password, Role, Departemen, dan Status Karyawan boleh dikosongkan.']);
            fputcsv($file, ['#    - NIK SGIN        : Kosongkan jika ingin dibuatkan nomor NIK unik otomatis oleh sistem.']);
            fputcsv($file, ['#    - Password        : Kosongkan jika ingin password default ("password123").']);
            fputcsv($file, ['#    - Role            : Kosongkan jika ingin role default ("employee").']);
            fputcsv($file, ['#    - Departemen      : Tulis Kode atau Nama Departemen sesuai list di bawah.']);
            fputcsv($file, ['#    - Status Karyawan : Pilih "Tetap", "Kontrak", "Magang", atau "Percobaan" (Default: "Tetap").']);
            fputcsv($file, ['# 3. KELENGKAPAN BIODATA :']);
            fputcsv($file, ['#    Data lanjutan (KTP, KK, BPJS, No HP, Alamat, Rekening Bank, dll.) TIDAK PERLU diisi pada saat import.']);
            fputcsv($file, ['#    Karyawan dapat melengkapi data dirinya secara mandiri melalui menu "Form Data Diri Saya" setelah login.']);
            fputcsv($file, ['#']);
            fputcsv($file, ['# [DAFTAR KODE & NAMA DEPARTEMEN AKTIF]:']);
            fputcsv($file, ["# -> {$deptList}"]);
            fputcsv($file, ['#']);
            fputcsv($file, ['# [DAFTAR ROLE YANG TERSEDIA]:']);
            fputcsv($file, ["# -> {$roleList}"]);
            fputcsv($file, ['#']);
            fputcsv($file, ['# [PENTING] Masukkan data karyawan pada baris di bawah judul kolom berikut (Jangan ubah nama kolom):']);
            fputcsv($file, ['# =========================================================================================================']);

            // =========================================================================================================
            // 2. 7 KOLOM UTAMA
            // =========================================================================================================
            fputcsv($file, [
                '[OPSIONAL] NIK SGIN',
                '[WAJIB] Nama Lengkap Karyawan',
                '[WAJIB] Email Login Akun',
                '[OPSIONAL] Password Awal',
                '[OPSIONAL] Role',
                '[OPSIONAL] Departemen',
                '[OPSIONAL] Status Karyawan'
            ]);

            // =========================================================================================================
            // 3. CONTOH DATA BARIS
            // =========================================================================================================
            fputcsv($file, [
                'SA-001',
                'Ahmad Fauzi',
                'ahmad.fauzi@sugiyama.co.id',
                'password123',
                'employee',
                'IT',
                'Tetap'
            ]);

            fputcsv($file, [
                'SA-002',
                'Budi Santoso',
                'budi.santoso@sugiyama.co.id',
                'password123',
                'supervisor',
                'HRD',
                'Tetap'
            ]);

            fputcsv($file, [
                '',
                'Dewi Lestari',
                'dewi.lestari@sugiyama.co.id',
                '',
                'employee',
                'PROD',
                'Kontrak'
            ]);

            fclose($file);
        }, 200, $headers);
    }

    public function importEmployees(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file|max:10240']);
        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');

        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $successCount = 0;
        $departments = $this->departmentRepo->getAll();
        $dbRoles = class_exists('\\Spatie\\Permission\\Models\\Role') ? \Spatie\Permission\Models\Role::pluck('name')->map(fn($r) => strtolower(trim($r)))->toArray() : [];
        $validRoles = array_unique(array_merge(['employee', 'manager', 'admin', 'superadmin', 'supervisor'], $dbRoles));

        while (($row = fgetcsv($handle, 4000, ',')) !== false) {
            $col0 = trim($row[0] ?? '');
            $col1 = trim($row[1] ?? '');
            $col2 = trim($row[2] ?? '');

            // Skip empty rows
            if (empty($col0) && empty($col1) && empty($col2)) {
                continue;
            }

            // Skip comments / tutorial rows
            if (str_starts_with($col0, '#') || 
                str_starts_with($col0, '=') || 
                str_starts_with($col0, '-') || 
                preg_match('/^(tutorial|panduan|petunjuk|daftar|catatan|kode)/i', $col0)) {
                continue;
            }

            // Skip table header row
            if (preg_match('/(nik|nama|email|wajib|opsional)/i', $col0 . $col1) && 
                preg_match('/(nama|email|login|wajib|opsional)/i', $col1 . $col2)) {
                continue;
            }

            // Check if name and email are present
            if (empty($col1) || empty($col2)) {
                continue;
            }

            // Skip if value matches header keywords
            if (preg_match('/(nama|lengkap|wajib)/i', $col1) && preg_match('/(email|login|akun)/i', $col2)) {
                continue;
            }

            $nik = $col0 ?: 'EMP-' . date('Y') . '-' . rand(100, 999);
            $name = $col1;
            $email = $col2;
            $password = trim($row[3] ?? '') ?: 'password123';
            $inputRole = strtolower(trim($row[4] ?? ''));
            $role = in_array($inputRole, $validRoles) ? $inputRole : 'employee';
            $deptInput = trim($row[5] ?? '');
            $statusInput = trim($row[6] ?? '') ?: 'Tetap';

            $deptId = null;
            if ($deptInput) {
                $matched = $departments->first(fn($d) => strcasecmp($d->code, $deptInput) === 0 || strcasecmp($d->name, $deptInput) === 0);
                $deptId = $matched?->id;
            }

            $userData = [
                'nik' => $nik,
                'name' => $name,
                'password' => Hash::make($password),
                'role' => $role,
                'department_id' => $deptId,
                'employee_status' => $statusInput,
            ];

            // Backward compatibility for legacy full templates
            if (isset($row[9]) && !empty($row[9])) $userData['join_date'] = trim($row[9]);
            if (isset($row[11]) && !empty($row[11])) $userData['position'] = trim($row[11]);
            if (isset($row[12]) && !empty($row[12])) $userData['education'] = trim($row[12]);
            if (isset($row[13]) && !empty($row[13])) $userData['ktp_number'] = trim($row[13]);
            if (isset($row[14]) && !empty($row[14])) $userData['gender'] = trim($row[14]);
            if (isset($row[15]) && !empty($row[15])) $userData['birth_place'] = trim($row[15]);
            if (isset($row[16]) && !empty($row[16])) $userData['birth_date'] = trim($row[16]);
            if (isset($row[17]) && !empty($row[17])) $userData['phone_number'] = trim($row[17]);
            if (isset($row[18]) && !empty($row[18])) $userData['ktp_address'] = trim($row[18]);
            if (isset($row[19]) && !empty($row[19])) $userData['domicile_address'] = trim($row[19]);
            if (isset($row[20]) && !empty($row[20])) $userData['marital_status'] = trim($row[20]);
            if (isset($row[21]) && !empty($row[21])) $userData['npwp'] = trim($row[21]);
            if (isset($row[22]) && !empty($row[22])) $userData['bpjs_kesehatan_number'] = trim($row[22]);
            if (isset($row[23]) && !empty($row[23])) $userData['bpjs_health_facility'] = trim($row[23]);
            if (isset($row[24]) && !empty($row[24])) $userData['bpjs_ketenagakerjaan_number'] = trim($row[24]);
            if (isset($row[25]) && !empty($row[25])) $userData['bank_name'] = trim($row[25]);
            if (isset($row[26]) && !empty($row[26])) $userData['bank_account_number'] = trim($row[26]);
            if (isset($row[27]) && !empty($row[27])) $userData['vehicle_plate_number'] = trim($row[27]);
            if (isset($row[28]) && !empty($row[28])) $userData['sim_number'] = trim($row[28]);
            if (isset($row[29]) && !empty($row[29])) $userData['sim_valid_until'] = trim($row[29]);
            if (isset($row[30]) && !empty($row[30])) $userData['shoe_size'] = trim($row[30]);
            if (isset($row[31]) && !empty($row[31])) $userData['blood_type'] = trim($row[31]);
            if (isset($row[32]) && !empty($row[32])) $userData['mother_maiden_name'] = trim($row[32]);
            if (isset($row[33]) && !empty($row[33])) $userData['kk_number'] = trim($row[33]);

            $user = User::updateOrCreate(
                ['email' => $email],
                $userData
            );

            // Sync Spatie role
            if (method_exists($user, 'syncRoles') && !empty($role)) {
                try {
                    if (class_exists('\\Spatie\\Permission\\Models\\Role')) {
                        \Spatie\Permission\Models\Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
                        $user->syncRoles([$role]);
                    }
                } catch (\Throwable $e) {}
            }

            $this->quotaService->setTotalQuota($user->id, 12);
            $successCount++;
        }

        fclose($handle);
        return back()->with('success', "Import berhasil! {$successCount} data karyawan berhasil diproses ke sistem.");
    }

    public function exportBiodataCsv(Request $request): StreamedResponse
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            abort(403, 'Akses khusus HRD.');
        }

        $employees = User::with('department')->orderBy('name')->get();
        $fileName = 'rekap_biodata_karyawan_sgin_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        return response()->stream(function () use ($employees) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, [
                'NIK SGIN', 'Nama', 'Email', 'Role', 'Departemen', 'Status Kelengkapan', 'Persentase',
                'Tanggal Bergabung', 'Status Karyawan', 'Jabatan', 'Pendidikan', 'Aktif Bekerja Sampai',
                'NIK KTP', 'Jenis Kelamin', 'Tempat Lahir', 'Tanggal Lahir', 'No HP',
                'Alamat KTP', 'Alamat Domisili', 'Status Kawin', 'Nama Ibu Kandung', 'No KK', 'Gol Darah',
                'NPWP', 'No BPJS Kesehatan', 'Faskes BPJS', 'No BPJS TKU', 'Bank', 'No Rekening',
                'No Pol Kendaraan', 'No SIM', 'Masa Berlaku SIM', 'Ukuran Sepatu',
                'Kontak Darurat - Hubungan', 'Kontak Darurat - No Telp', 'Kontak Darurat - Alamat',
                'Nama Pasangan', 'NIK Pasangan', 'TTL Pasangan', 'Anak ke 1', 'Anak ke 2', 'Anak ke 3'
            ]);

            foreach ($employees as $e) {
                fputcsv($file, [
                    $e->nik ?? '-',
                    $e->name,
                    $e->email,
                    strtoupper($e->role),
                    $e->department->name ?? 'General',
                    $e->is_profile_completed ? 'LENGKAP' : 'BELUM LENGKAP',
                    $e->profile_completeness . '%',
                    $e->join_date ? $e->join_date->format('Y-m-d') : '-',
                    $e->employee_status ?? '-',
                    $e->position ?? '-',
                    $e->education ?? '-',
                    $e->contract_end_date ? $e->contract_end_date->format('Y-m-d') : '-',
                    $e->ktp_number ?? '-',
                    $e->gender ?? '-',
                    $e->birth_place ?? '-',
                    $e->birth_date ? $e->birth_date->format('Y-m-d') : '-',
                    $e->phone_number ?? '-',
                    $e->ktp_address ?? '-',
                    $e->domicile_address ?? '-',
                    $e->marital_status ?? '-',
                    $e->mother_maiden_name ?? '-',
                    $e->kk_number ?? '-',
                    $e->blood_type ?? '-',
                    $e->npwp ?? '-',
                    $e->bpjs_kesehatan_number ?? '-',
                    $e->bpjs_health_facility ?? '-',
                    $e->bpjs_ketenagakerjaan_number ?? '-',
                    $e->bank_name ?? '-',
                    $e->bank_account_number ?? '-',
                    $e->vehicle_plate_number ?? '-',
                    $e->sim_number ?? '-',
                    $e->sim_valid_until ? $e->sim_valid_until->format('Y-m-d') : '-',
                    $e->shoe_size ?? '-',
                    $e->emergency_contact_relationship ?? '-',
                    $e->emergency_contact_phone ?? '-',
                    $e->emergency_contact_address ?? '-',
                    $e->spouse_name ?? '-',
                    $e->spouse_ktp_number ?? '-',
                    ($e->spouse_birth_place ? $e->spouse_birth_place . ', ' : '') . ($e->spouse_birth_date ? $e->spouse_birth_date->format('d/m/Y') : '-'),
                    $e->child_1_name ?? '-',
                    $e->child_2_name ?? '-',
                    $e->child_3_name ?? '-',
                ]);
            }
            fclose($file);
        }, 200, $headers);
    }

    public function employeeBiodata(int $userId): Response
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            abort(403, 'Akses khusus HRD.');
        }

        $employee = $this->userRepo->findById($userId);
        if (!$employee) {
            abort(404, 'Karyawan tidak ditemukan.');
        }

        $departments = $this->departmentRepo->getAll();

        return Inertia::render('Profile/Biodata', [
            'user' => $employee,
            'departments' => $departments,
            'isHrdView' => true,
        ]);
    }

    public function updateEmployeeBiodata(Request $request, int $userId): RedirectResponse
    {
        $admin = Auth::user();
        if (!$admin->isAdmin()) {
            return back()->with('error', 'Akses khusus HRD.');
        }

        $employee = $this->userRepo->findById($userId);
        if (!$employee) {
            return back()->with('error', 'Karyawan tidak ditemukan.');
        }

        $validated = $request->validate([
            // Data Pekerjaan (HRD can edit all)
            'join_date' => 'nullable|date',
            'employee_status' => 'nullable|string|max:50',
            'position' => 'nullable|string|max:100',
            'education' => 'nullable|string|max:100',
            'contract_end_date' => 'nullable|date',
            'department_id' => 'nullable|exists:departments,id',

            // Data Pribadi
            'ktp_number' => 'nullable|string|max:30',
            'gender' => 'nullable|in:Laki-laki,Perempuan',
            'birth_place' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date',
            'phone_number' => 'nullable|string|max:30',
            'ktp_address' => 'nullable|string|max:1000',
            'domicile_address' => 'nullable|string|max:1000',
            'marital_status' => 'nullable|string|max:50',
            'mother_maiden_name' => 'nullable|string|max:150',
            'kk_number' => 'nullable|string|max:30',
            'blood_type' => 'nullable|in:A,B,AB,O',

            // Keuangan & BPJS
            'npwp' => 'nullable|string|max:50',
            'bpjs_kesehatan_number' => 'nullable|string|max:50',
            'bpjs_health_facility' => 'nullable|string|max:150',
            'bpjs_ketenagakerjaan_number' => 'nullable|string|max:50',
            'bank_name' => 'nullable|string|max:50',
            'bank_account_number' => 'nullable|string|max:50',

            // Logistik & Operasional
            'vehicle_plate_number' => 'nullable|string|max:30',
            'sim_number' => 'nullable|string|max:50',
            'sim_valid_until' => 'nullable|date',
            'shoe_size' => 'nullable|string|max:10',

            // Kontak Darurat
            'emergency_contact_name' => 'nullable|string|max:150',
            'emergency_contact_relationship' => 'nullable|string|max:50',
            'emergency_contact_phone' => 'nullable|string|max:30',
            'emergency_contact_address' => 'nullable|string|max:1000',

            // Data Pasangan & Anak
            'spouse_name' => 'nullable|string|max:150',
            'spouse_ktp_number' => 'nullable|string|max:30',
            'spouse_birth_place' => 'nullable|string|max:100',
            'spouse_birth_date' => 'nullable|date',
            'child_1_name' => 'nullable|string|max:150',
            'child_2_name' => 'nullable|string|max:150',
            'child_3_name' => 'nullable|string|max:150',
        ]);

        $this->employeeService->updateEmployee($employee, $validated);

        return Redirect::back()->with('success', "Data diri karyawan {$employee->name} berhasil diperbarui.");
    }

    public function printEmployeeBiodata(int $userId): Response
    {
        $admin = Auth::user();
        if (!$admin->isAdmin()) {
            abort(403, 'Akses khusus HRD.');
        }

        $employee = $this->userRepo->findById($userId);
        if (!$employee) {
            abort(404, 'Karyawan tidak ditemukan.');
        }

        return Inertia::render('Profile/PrintBiodata', [
            'employee' => $employee->load('department'),
        ]);
    }

    public function exportLeaveQuotas(Request $request): StreamedResponse
    {
        $user = Auth::user();
        if (!$user->can('view-leave-quota-report') && !$user->isAdmin()) {
            abort(403, 'Akses khusus HRD.');
        }

        $year = $request->query('year', date('Y'));
        $quotas = LeaveQuota::with('user.department')->where('year', $year)->get();
        $fileName = "laporan_sisa_cuti_sgin_{$year}_" . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        return response()->stream(function () use ($quotas) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['NIK', 'Nama Karyawan', 'Departemen', 'Tahun', 'Total Kuota', 'Terpakai', 'Sisa Kuota']);

            foreach ($quotas as $q) {
                fputcsv($file, [
                    $q->user->nik ?? '-',
                    $q->user->name ?? '-',
                    $q->user->department->name ?? 'General',
                    $q->year,
                    $q->total_quota,
                    $q->used_quota,
                    $q->remaining_quota,
                ]);
            }
            fclose($file);
        }, 200, $headers);
    }

    public function exportDepartmentSummary(Request $request): StreamedResponse
    {
        $user = Auth::user();
        if (!$user->can('view-department-report') && !$user->isAdmin()) {
            abort(403, 'Akses khusus HRD.');
        }

        $departments = \App\Models\Department::with(['users.leaveRequests' => function($q) {
            $q->select('user_id', 'status', 'amount');
        }])->get();

        $fileName = 'rekap_departemen_sgin_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        return response()->stream(function () use ($departments) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Kode', 'Departemen', 'Total Karyawan', 'Cuti Pending', 'Cuti Disetujui (Hari)', 'Cuti Ditolak']);

            foreach ($departments as $dept) {
                $totalEmployees = $dept->users->count();
                $pending = 0;
                $approvedDays = 0;
                $rejected = 0;

                foreach ($dept->users as $u) {
                    foreach ($u->leaveRequests as $lr) {
                        if ($lr->status === 'pending') $pending++;
                        if ($lr->status === 'approved') $approvedDays += (float) $lr->amount;
                        if ($lr->status === 'rejected') $rejected++;
                    }
                }

                fputcsv($file, [
                    $dept->code,
                    $dept->name,
                    $totalEmployees,
                    $pending,
                    $approvedDays,
                    $rejected,
                ]);
            }
            fclose($file);
        }, 200, $headers);
    }
}
