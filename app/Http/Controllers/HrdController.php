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
            'remaining_quota' => 'nullable|integer|min:0|max:100',
            'join_date' => 'nullable|date',
            'position' => 'nullable|string|max:100',
            'gender' => 'nullable|string|max:20',
            'employee_status' => 'nullable|string|max:50',
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
            'remaining_quota' => 'nullable|integer|min:0|max:100',
            'join_date' => 'nullable|date',
            'position' => 'nullable|string|max:100',
            'gender' => 'nullable|string|max:20',
            'employee_status' => 'nullable|string|max:50',
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
            'remaining_quota' => 'nullable|integer|min:0|max:100',
        ]);

        $employee = $this->userRepo->findById($userId);
        if (!$employee) {
            return back()->with('error', 'Karyawan tidak ditemukan.');
        }

        $remaining = isset($validated['remaining_quota']) && $validated['remaining_quota'] !== '' ? (int) $validated['remaining_quota'] : null;
        $quota = $this->quotaService->setQuota($employee->id, (int) $validated['total_quota'], $remaining);

        return redirect()->back()->with('success', "Kuota cuti {$employee->name} berhasil diperbarui (Total: {$quota->total_quota} hari, Sisa: {$quota->remaining_quota} hari).");
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
            fputcsv($file, ['# TEMPLATE IMPORT & UPDATE MASSAL DATA KARYAWAN - PT SUGIYAMA INDONESIA (SGIN LEAVES APPLICATION)']);
            fputcsv($file, ['# =========================================================================================================']);
            fputcsv($file, ['# [PANDUAN & ATURAN PENGISIAN]:']);
            fputcsv($file, ['# 1. TAMBAH KARYAWAN BARU : Wajib isi "Nama Lengkap Karyawan" dan "Email Login Akun". Kolom lain opsional.']);
            fputcsv($file, ['# 2. UPDATE KARYAWAN LAMA : Cukup isi "NIK SGIN" atau "Email", lalu isi data yang ingin diperbarui']);
            fputcsv($file, ['#    (misal: Tanggal Bergabung, Jabatan, Departemen, Jenis Kelamin, Kuota Cuti, Status Karyawan).']);
            fputcsv($file, ['#    Password lama TIDAK AKAN BERUBAH jika kolom Password dikosongkan.']);
            fputcsv($file, ['# 3. FORMAT TANGGAL BERGABUNG:']);
            fputcsv($file, ['#    - Format standar : YYYY-MM-DD (Contoh: 2024-01-15) atau DD/MM/YYYY (Contoh: 15/01/2024) atau DD-MM-YYYY.']);
            fputcsv($file, ['# 4. KETERANGAN KOLOM LAINNYA:']);
            fputcsv($file, ['#    - NIK SGIN          : Kosongkan jika ingin dibuatkan NIK otomatis (untuk karyawan baru).']);
            fputcsv($file, ['#    - Password          : Kosongkan jika ingin mempertahankan password lama atau default "password123".']);
            fputcsv($file, ['#    - Role              : Pilihan: employee, manager, supervisor, admin, superadmin (Default: employee).']);
            fputcsv($file, ['#    - Departemen        : Tulis Kode atau Nama Departemen sesuai list aktif di bawah.']);
            fputcsv($file, ['#    - Jabatan / Posisi  : Tulis nama jabatan (Contoh: "Staff IT", "Operator Produksi", "Leader QC", dll).']);
            fputcsv($file, ['#    - Jenis Kelamin     : Tulis "Laki-laki" / "L" atau "Perempuan" / "P".']);
            fputcsv($file, ['#    - Total Jatah Kuota : Total jatah cuti tahunan (Default: 12 hari).']);
            fputcsv($file, ['#    - Sisa Kuota        : Sisa hari cuti tahun berjalan yang masih dimiliki karyawan.']);
            fputcsv($file, ['#    - Status Karyawan   : Pilihan: "Tetap", "Kontrak", "Magang", atau "Percobaan" (Default: "Tetap").']);
            fputcsv($file, ['#']);
            fputcsv($file, ['# [DAFTAR KODE & NAMA DEPARTEMEN AKTIF]:']);
            fputcsv($file, ["# -> {$deptList}"]);
            fputcsv($file, ['#']);
            fputcsv($file, ['# [DAFTAR ROLE YANG TERSEDIA]:']);
            fputcsv($file, ["# -> {$roleList}"]);
            fputcsv($file, ['#']);
            fputcsv($file, ['# [PENTING] Masukkan data karyawan pada baris di bawah judul kolom berikut:']);
            fputcsv($file, ['# =========================================================================================================']);

            // =========================================================================================================
            // 2. 12 KOLOM UTAMA LENGKAP
            // =========================================================================================================
            fputcsv($file, [
                '[OPSIONAL] NIK SGIN',
                '[WAJIB] Nama Lengkap Karyawan',
                '[WAJIB] Email Login Akun',
                '[OPSIONAL] Password Awal',
                '[OPSIONAL] Role',
                '[OPSIONAL] Departemen',
                '[OPSIONAL] Jabatan / Posisi',
                '[OPSIONAL] Jenis Kelamin (Laki-laki / Perempuan)',
                '[OPSIONAL] Tanggal Bergabung (YYYY-MM-DD atau DD/MM/YYYY)',
                '[OPSIONAL] Total Jatah Kuota (Default: 12)',
                '[OPSIONAL] Sisa Kuota Cuti Tahun Ini',
                '[OPSIONAL] Status Karyawan'
            ]);

            // =========================================================================================================
            // 3. CONTOH DATA BARIS (Contoh Format Tanggal Bergabung)
            // =========================================================================================================
            fputcsv($file, [
                'SA-001',
                'Ahmad Fauzi',
                'ahmad.fauzi@sugiyama.co.id',
                '',
                'employee',
                'IT',
                'Staff IT & Support',
                'Laki-laki',
                '2023-05-10',
                '12',
                '8',
                'Tetap'
            ]);

            fputcsv($file, [
                'SA-002',
                'Siti Rahmawati',
                'siti.rahma@sugiyama.co.id',
                '',
                'supervisor',
                'HRD',
                'Supervisor HRD & GA',
                'Perempuan',
                '15/02/2022',
                '14',
                '10',
                'Tetap'
            ]);

            fputcsv($file, [
                'SA-003',
                'Budi Santoso',
                'budi.santoso@sugiyama.co.id',
                '',
                'employee',
                'PROD',
                'Operator Produksi',
                'Laki-laki',
                '2024-01-15',
                '12',
                '12',
                'Kontrak'
            ]);

            fclose($file);
        }, 200, $headers);
    }

    public function importEmployees(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            return back()->with('error', 'Akses khusus HRD / Admin.');
        }

        $request->validate(['file' => 'required|file|max:15360']);
        $file = $request->file('file');

        try {
            $path = $file->getRealPath();
            $handle = fopen($path, 'r');
            if (!$handle) {
                return back()->with('error', 'Tidak dapat membuka file yang diunggah.');
            }

            // 1. Detect UTF-8 BOM
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }

            // 2. Automatic Delimiter Detection (Comma, Semicolon, or Tab)
            $samplePos = ftell($handle);
            $sampleLine = fgets($handle);
            $delimiter = ',';
            if ($sampleLine !== false) {
                $commaCount = substr_count($sampleLine, ',');
                $semiCount = substr_count($sampleLine, ';');
                $tabCount = substr_count($sampleLine, "\t");

                if ($semiCount > $commaCount && $semiCount > $tabCount) {
                    $delimiter = ';';
                } elseif ($tabCount > $commaCount && $tabCount > $semiCount) {
                    $delimiter = "\t";
                }
            }
            fseek($handle, $samplePos);

            $successCount = 0;
            $updatedCount = 0;
            $createdCount = 0;
            $departments = $this->departmentRepo->getAll();
            $dbRoles = class_exists('\\Spatie\\Permission\\Models\\Role') ? \Spatie\Permission\Models\Role::pluck('name')->map(fn($r) => strtolower(trim($r)))->toArray() : [];
            $validRoles = array_unique(array_merge(['employee', 'manager', 'admin', 'superadmin', 'supervisor'], $dbRoles));

            $normalizeGender = function($val) {
                if (empty($val)) return null;
                $v = strtolower(trim((string) $val));
                if (str_starts_with($v, 'l') || str_starts_with($v, 'm') || str_starts_with($v, 'pria')) {
                    return 'Laki-laki';
                }
                if (str_starts_with($v, 'p') || str_starts_with($v, 'w') || str_starts_with($v, 'f') || str_starts_with($v, 'wanita')) {
                    if ($v !== 'pria' && $v !== 'percobaan') {
                        return 'Perempuan';
                    }
                }
                return null;
            };

            $normalizeDate = function($val) {
                if (empty($val)) return null;
                $v = trim((string) $val);
                if ($v === '' || $v === '-' || $v === '0000-00-00' || strtolower($v) === 'null') return null;

                // Handle Excel numeric serial dates (e.g., 44562 for 2022-01-01)
                if (is_numeric($v) && (int)$v > 20000 && (int)$v < 70000) {
                    try {
                        $excelDate = (int)$v;
                        $unix = ($excelDate - 25569) * 86400;
                        return gmdate('Y-m-d', $unix);
                    } catch (\Throwable $e) {}
                }

                // Handle DD/MM/YYYY or DD-MM-YYYY or DD.MM.YYYY
                if (preg_match('/^(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})$/', $v, $m)) {
                    $d = (int)$m[1];
                    $mo = (int)$m[2];
                    $y = (int)$m[3];
                    if (checkdate($mo, $d, $y)) {
                        return sprintf('%04d-%02d-%02d', $y, $mo, $d);
                    }
                }

                // Handle YYYY-MM-DD or YYYY/MM/DD or YYYY.MM.DD
                if (preg_match('/^(\d{4})[\/\-\.](\d{1,2})[\/\-\.](\d{1,2})$/', $v, $m)) {
                    $y = (int)$m[1];
                    $mo = (int)$m[2];
                    $d = (int)$m[3];
                    if (checkdate($mo, $d, $y)) {
                        return sprintf('%04d-%02d-%02d', $y, $mo, $d);
                    }
                }

                // Handle Indonesian month names like '15 Januari 2024', '10 Mei 2023'
                $idMonths = [
                    'januari' => 'January', 'februari' => 'February', 'maret' => 'March',
                    'april' => 'April', 'mei' => 'May', 'juni' => 'June',
                    'juli' => 'July', 'agustus' => 'August', 'september' => 'September',
                    'oktober' => 'October', 'november' => 'November', 'desember' => 'December',
                    'agu' => 'Aug', 'okt' => 'Oct', 'des' => 'Dec'
                ];
                $engV = str_ireplace(array_keys($idMonths), array_values($idMonths), $v);

                try {
                    return \Carbon\Carbon::parse($engV)->format('Y-m-d');
                } catch (\Throwable $e) {
                    return null;
                }
            };

            $headerMap = [];
            $headerFound = false;

            while (($row = fgetcsv($handle, 4000, $delimiter)) !== false) {
                $col0 = trim($row[0] ?? '');
                $col1 = trim($row[1] ?? '');
                $col2 = trim($row[2] ?? '');

                // Skip empty rows
                if (empty($col0) && empty($col1) && empty($col2) && count(array_filter($row)) === 0) {
                    continue;
                }

                // Skip comments / divider rows
                if (str_starts_with($col0, '#') || 
                    str_starts_with($col0, '=') || 
                    str_starts_with($col0, '-') || 
                    preg_match('/^(tutorial|panduan|petunjuk|daftar|catatan|kode|urutan|langkah)/i', $col0)) {
                    continue;
                }

                // Header Detection & Dynamic Column Mapping
                $rowCombined = strtolower(implode(' ', $row));
                if (!$headerFound && (
                    (str_contains($rowCombined, 'nama') || str_contains($rowCombined, 'name')) &&
                    (str_contains($rowCombined, 'email') || str_contains($rowCombined, 'nik') || str_contains($rowCombined, 'departemen') || str_contains($rowCombined, 'bergabung') || str_contains($rowCombined, 'join'))
                )) {
                    $headerFound = true;
                    foreach ($row as $colIdx => $headerTitle) {
                        $clean = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', (string)$headerTitle)));
                        if (str_contains($clean, 'nik')) {
                            if (str_contains($clean, 'ktp')) {
                                $headerMap['ktp_number'] = $colIdx;
                            } elseif (!isset($headerMap['nik'])) {
                                $headerMap['nik'] = $colIdx;
                            }
                        } elseif (str_contains($clean, 'nama') || str_contains($clean, 'name')) {
                            if (str_contains($clean, 'ibu')) {
                                $headerMap['mother_maiden_name'] = $colIdx;
                            } elseif (str_contains($clean, 'pasangan')) {
                                $headerMap['spouse_name'] = $colIdx;
                            } elseif (str_contains($clean, 'darurat')) {
                                $headerMap['emergency_contact_name'] = $colIdx;
                            } elseif (str_contains($clean, 'bank')) {
                                $headerMap['bank_name'] = $colIdx;
                            } elseif (!isset($headerMap['name'])) {
                                $headerMap['name'] = $colIdx;
                            }
                        } elseif (str_contains($clean, 'email')) {
                            $headerMap['email'] = $colIdx;
                        } elseif (str_contains($clean, 'password')) {
                            $headerMap['password'] = $colIdx;
                        } elseif (str_contains($clean, 'role')) {
                            $headerMap['role'] = $colIdx;
                        } elseif (str_contains($clean, 'departemen') || str_contains($clean, 'department')) {
                            $headerMap['department'] = $colIdx;
                        } elseif (str_contains($clean, 'jabatan') || str_contains($clean, 'posisi') || str_contains($clean, 'position')) {
                            $headerMap['position'] = $colIdx;
                        } elseif (str_contains($clean, 'gender') || str_contains($clean, 'kelamin')) {
                            $headerMap['gender'] = $colIdx;
                        } elseif (str_contains($clean, 'tanggalbergabung') || str_contains($clean, 'tanggalmasuk') || str_contains($clean, 'tglmasuk') || str_contains($clean, 'joindate') || str_contains($clean, 'bergabung')) {
                            $headerMap['join_date'] = $colIdx;
                        } elseif (str_contains($clean, 'totalkuota') || str_contains($clean, 'jatahkuota') || str_contains($clean, 'totaljatah') || str_contains($clean, 'totalquota')) {
                            $headerMap['total_quota'] = $colIdx;
                        } elseif (str_contains($clean, 'sisakuota') || str_contains($clean, 'remainingquota')) {
                            $headerMap['remaining_quota'] = $colIdx;
                        } elseif (str_contains($clean, 'statuskaryawan') || $clean === 'status') {
                            $headerMap['employee_status'] = $colIdx;
                        } elseif (str_contains($clean, 'pendidikan') || str_contains($clean, 'education')) {
                            $headerMap['education'] = $colIdx;
                        } elseif (str_contains($clean, 'noktp') || str_contains($clean, 'nikktp')) {
                            $headerMap['ktp_number'] = $colIdx;
                        } elseif (str_contains($clean, 'tempatlahir')) {
                            $headerMap['birth_place'] = $colIdx;
                        } elseif (str_contains($clean, 'tanggallahir')) {
                            $headerMap['birth_date'] = $colIdx;
                        } elseif (str_contains($clean, 'nohp') || str_contains($clean, 'telepon')) {
                            $headerMap['phone_number'] = $colIdx;
                        } elseif (str_contains($clean, 'alamatktp')) {
                            $headerMap['ktp_address'] = $colIdx;
                        } elseif (str_contains($clean, 'alamatdomisili')) {
                            $headerMap['domicile_address'] = $colIdx;
                        } elseif (str_contains($clean, 'statusnikah') || str_contains($clean, 'statuskawin')) {
                            $headerMap['marital_status'] = $colIdx;
                        } elseif (str_contains($clean, 'npwp')) {
                            $headerMap['npwp'] = $colIdx;
                        } elseif (str_contains($clean, 'bpjskesehatan')) {
                            $headerMap['bpjs_kesehatan_number'] = $colIdx;
                        } elseif (str_contains($clean, 'faskes')) {
                            $headerMap['bpjs_health_facility'] = $colIdx;
                        } elseif (str_contains($clean, 'bpjsketenagakerjaan') || str_contains($clean, 'bpjstku')) {
                            $headerMap['bpjs_ketenagakerjaan_number'] = $colIdx;
                        } elseif (str_contains($clean, 'bank') || str_contains($clean, 'namabank')) {
                            $headerMap['bank_name'] = $colIdx;
                        } elseif (str_contains($clean, 'norekening') || str_contains($clean, 'rekening')) {
                            $headerMap['bank_account_number'] = $colIdx;
                        } elseif (str_contains($clean, 'nopol') || str_contains($clean, 'kendaraan')) {
                            $headerMap['vehicle_plate_number'] = $colIdx;
                        } elseif (str_contains($clean, 'nosim') || str_contains($clean, 'simnumber')) {
                            $headerMap['sim_number'] = $colIdx;
                        } elseif (str_contains($clean, 'masaberlakusim')) {
                            $headerMap['sim_valid_until'] = $colIdx;
                        } elseif (str_contains($clean, 'sepatu') || str_contains($clean, 'shoesize')) {
                            $headerMap['shoe_size'] = $colIdx;
                        } elseif (str_contains($clean, 'goldarah') || str_contains($clean, 'bloodtype')) {
                            $headerMap['blood_type'] = $colIdx;
                        } elseif (str_contains($clean, 'nokk') || str_contains($clean, 'kknumber')) {
                            $headerMap['kk_number'] = $colIdx;
                        }
                    }
                    continue; // Skip processing header row as employee data
                }

                // Extract Field Values using Header Map (if available) or Positional Index Fallback
                $getCol = function($key, $posFallback) use ($row, $headerMap) {
                    if (isset($headerMap[$key]) && isset($row[$headerMap[$key]])) {
                        return trim((string) $row[$headerMap[$key]]);
                    }
                    if (isset($row[$posFallback])) {
                        return trim((string) $row[$posFallback]);
                    }
                    return '';
                };

                $nikInput = $getCol('nik', 0);
                $nameInput = $getCol('name', 1);
                $emailInput = strtolower($getCol('email', 2));
                $passwordInput = $getCol('password', 3);
                $inputRole = strtolower($getCol('role', 4));
                $role = in_array($inputRole, $validRoles) ? $inputRole : null;
                $deptInput = $getCol('department', 5);
                $positionInput = $getCol('position', 6);
                $genderRaw = $getCol('gender', 7);
                $genderInput = $normalizeGender($genderRaw);
                $joinDateRaw = $getCol('join_date', 8);
                $joinDateInput = $normalizeDate($joinDateRaw);
                $totalQuotaRaw = $getCol('total_quota', 9);
                $totalQuotaInput = is_numeric($totalQuotaRaw) ? (int) $totalQuotaRaw : null;
                $remainingQuotaRaw = $getCol('remaining_quota', 10);
                $remainingQuotaInput = is_numeric($remainingQuotaRaw) ? (int) $remainingQuotaRaw : null;
                $statusInput = $getCol('employee_status', 11) ?: 'Tetap';

                // Skip header-like keywords if appeared in data
                if (preg_match('/(nama|lengkap|wajib)/i', $nameInput) && preg_match('/(email|login|akun)/i', $emailInput)) {
                    continue;
                }

                // Must have at least NIK or Name or Email
                if (empty($nameInput) && empty($emailInput) && empty($nikInput)) {
                    continue;
                }

                // 1. Find Existing User by Email OR by NIK
                $existingUser = null;
                if (!empty($emailInput)) {
                    $existingUser = User::where('email', $emailInput)->first();
                }
                if (!$existingUser && !empty($nikInput)) {
                    $existingUser = User::where('nik', $nikInput)->first();
                }

                // 2. Department Matching by Code or Name
                $deptId = null;
                if (!empty($deptInput)) {
                    $matched = $departments->first(fn($d) => strcasecmp($d->code, $deptInput) === 0 || strcasecmp($d->name, $deptInput) === 0);
                    $deptId = $matched?->id;
                }

                // 3. Safe NIK Handling
                $nik = $nikInput;
                if (empty($nik)) {
                    if ($existingUser && !empty($existingUser->nik)) {
                        $nik = $existingUser->nik;
                    } else {
                        do {
                            $nik = 'EMP-' . date('Y') . '-' . str_pad((string) rand(100, 9999), 4, '0', STR_PAD_LEFT);
                        } while (User::where('nik', $nik)->exists());
                    }
                }

                if ($existingUser) {
                    // =========================================================================
                    // UPDATE EXISTING EMPLOYEE (UPDATE TANGGAL BERGABUNG & BIODATA)
                    // =========================================================================
                    $updateData = [];
                    if (!empty($nameInput)) $updateData['name'] = $nameInput;
                    if (!empty($nik) && (empty($existingUser->nik) || $existingUser->nik !== $nik)) {
                        // Ensure NIK isn't colliding with another user
                        if (!User::where('nik', $nik)->where('id', '!=', $existingUser->id)->exists()) {
                            $updateData['nik'] = $nik;
                        }
                    }
                    if (!empty($role)) $updateData['role'] = $role;
                    if ($deptId !== null) $updateData['department_id'] = $deptId;
                    if (!empty($statusInput)) $updateData['employee_status'] = $statusInput;
                    if (!empty($positionInput)) $updateData['position'] = $positionInput;
                    if (!empty($genderInput)) $updateData['gender'] = $genderInput;
                    
                    // Update Tanggal Bergabung
                    if (!empty($joinDateInput)) {
                        $updateData['join_date'] = $joinDateInput;
                    }

                    // Optional password update if provided
                    if (!empty($passwordInput)) {
                        $updateData['password'] = Hash::make($passwordInput);
                    }

                    // Extra biodata fields if present in header map
                    $extraFields = [
                        'education', 'ktp_number', 'birth_place', 'phone_number',
                        'ktp_address', 'domicile_address', 'marital_status', 'npwp',
                        'bpjs_kesehatan_number', 'bpjs_health_facility', 'bpjs_ketenagakerjaan_number',
                        'bank_name', 'bank_account_number', 'vehicle_plate_number', 'sim_number',
                        'shoe_size', 'blood_type', 'mother_maiden_name', 'kk_number'
                    ];
                    foreach ($extraFields as $f) {
                        $val = $getCol($f, -1);
                        if (!empty($val)) {
                            $updateData[$f] = $val;
                        }
                    }
                    if (!empty($getCol('birth_date', -1))) {
                        $bDate = $normalizeDate($getCol('birth_date', -1));
                        if ($bDate) $updateData['birth_date'] = $bDate;
                    }
                    if (!empty($getCol('sim_valid_until', -1))) {
                        $sDate = $normalizeDate($getCol('sim_valid_until', -1));
                        if ($sDate) $updateData['sim_valid_until'] = $sDate;
                    }

                    $existingUser->fill($updateData);
                    try {
                        $existingUser->is_profile_completed = ($existingUser->profile_completeness >= 75);
                    } catch (\Throwable $e) {}
                    $existingUser->save();
                    $savedUser = $existingUser;

                    // Update Quotas if specified
                    if ($totalQuotaInput !== null || $remainingQuotaInput !== null) {
                        $targetTotal = $totalQuotaInput ?? ($existingUser->currentQuota?->total_quota ?? 12);
                        $this->quotaService->setQuota($existingUser->id, $targetTotal, $remainingQuotaInput);
                    }

                    $updatedCount++;
                } else {
                    // =========================================================================
                    // CREATE NEW EMPLOYEE
                    // =========================================================================
                    $userData = [
                        'nik' => $nik,
                        'name' => $nameInput ?: 'Karyawan Baru',
                        'email' => $emailInput ?: ($nik . '@sugiyama.co.id'),
                        'role' => $role ?: 'employee',
                        'department_id' => $deptId,
                        'employee_status' => $statusInput ?: 'Tetap',
                        'position' => $positionInput,
                        'gender' => $genderInput,
                        'join_date' => $joinDateInput,
                        'password' => !empty($passwordInput) ? Hash::make($passwordInput) : Hash::make('password123'),
                    ];

                    $extraFields = [
                        'education', 'ktp_number', 'birth_place', 'phone_number',
                        'ktp_address', 'domicile_address', 'marital_status', 'npwp',
                        'bpjs_kesehatan_number', 'bpjs_health_facility', 'bpjs_ketenagakerjaan_number',
                        'bank_name', 'bank_account_number', 'vehicle_plate_number', 'sim_number',
                        'shoe_size', 'blood_type', 'mother_maiden_name', 'kk_number'
                    ];
                    foreach ($extraFields as $f) {
                        $val = $getCol($f, -1);
                        if (!empty($val)) {
                            $userData[$f] = $val;
                        }
                    }
                    if (!empty($getCol('birth_date', -1))) {
                        $bDate = $normalizeDate($getCol('birth_date', -1));
                        if ($bDate) $userData['birth_date'] = $bDate;
                    }
                    if (!empty($getCol('sim_valid_until', -1))) {
                        $sDate = $normalizeDate($getCol('sim_valid_until', -1));
                        if ($sDate) $userData['sim_valid_until'] = $sDate;
                    }

                    $savedUser = User::create($userData);
                    try {
                        $savedUser->is_profile_completed = ($savedUser->profile_completeness >= 75);
                        $savedUser->save();
                    } catch (\Throwable $e) {}

                    $targetTotal = $totalQuotaInput ?? 12;
                    $this->quotaService->setQuota($savedUser->id, $targetTotal, $remainingQuotaInput);
                    $createdCount++;
                }

                // Sync Spatie role
                $activeRole = $savedUser->role;
                if (method_exists($savedUser, 'syncRoles') && !empty($activeRole)) {
                    try {
                        if (class_exists('\\Spatie\\Permission\\Models\\Role')) {
                            \Spatie\Permission\Models\Role::firstOrCreate(['name' => $activeRole, 'guard_name' => 'web']);
                            $savedUser->syncRoles([$activeRole]);
                        }
                    } catch (\Throwable $e) {}
                }

                $successCount++;
            }

            fclose($handle);

            if ($successCount === 0) {
                return back()->with('error', 'Tidak ada data karyawan valid yang berhasil dibaca dari file. Pastikan format CSV sesuai template.');
            }

            $msg = "Import berhasil! {$successCount} data karyawan diproses";
            if ($updatedCount > 0 && $createdCount > 0) {
                $msg .= " ({$updatedCount} data karyawan diperbarui / update tanggal bergabung, {$createdCount} karyawan baru ditambahkan).";
            } elseif ($updatedCount > 0) {
                $msg .= " ({$updatedCount} data karyawan lama berhasil diperbarui / update tanggal bergabung).";
            } else {
                $msg .= " ({$createdCount} karyawan baru ditambahkan).";
            }

            return back()->with('success', $msg);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Import error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Gagal memproses import data: ' . $e->getMessage());
        }
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

            $formatDateStr = function ($val, $format = 'Y-m-d') {
                if (empty($val)) return '-';
                if ($val instanceof \DateTimeInterface) return $val->format($format);
                if (is_string($val)) {
                    $clean = trim($val);
                    if ($clean === '' || $clean === '0000-00-00') return '-';
                    return substr($clean, 0, 10);
                }
                return '-';
            };

            foreach ($employees as $e) {
                fputcsv($file, [
                    $e->nik ?? '-',
                    $e->name,
                    $e->email,
                    strtoupper($e->role),
                    $e->department->name ?? 'General',
                    $e->is_profile_completed ? 'LENGKAP' : 'BELUM LENGKAP',
                    $e->profile_completeness . '%',
                    $formatDateStr($e->join_date),
                    $e->employee_status ?? '-',
                    $e->position ?? '-',
                    $e->education ?? '-',
                    $formatDateStr($e->contract_end_date),
                    $e->ktp_number ?? '-',
                    $e->gender ?? '-',
                    $e->birth_place ?? '-',
                    $formatDateStr($e->birth_date),
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
                    $formatDateStr($e->sim_valid_until),
                    $e->shoe_size ?? '-',
                    $e->emergency_contact_relationship ?? '-',
                    $e->emergency_contact_phone ?? '-',
                    $e->emergency_contact_address ?? '-',
                    $e->spouse_name ?? '-',
                    $e->spouse_ktp_number ?? '-',
                    ($e->spouse_birth_place ? $e->spouse_birth_place . ', ' : '') . $formatDateStr($e->spouse_birth_date, 'd/m/Y'),
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

        $employee->loadMissing(['department', 'manager', 'approver1', 'approver2']);
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

