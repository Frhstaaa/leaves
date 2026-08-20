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
            'role' => 'required|in:employee,manager,admin,superadmin',
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
            'role' => 'required|in:employee,manager,admin,superadmin',
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
            'name' => 'required|string|max:255|unique:departments,name',
            'code' => 'required|string|max:50|unique:departments,code',
            'manager_id' => 'nullable',
            'approver_1_id' => 'nullable',
            'approver_2_id' => 'nullable',
            'approval_type' => 'required|in:3_tier,2_tier,1_tier,custom',
            'description' => 'nullable|string|max:1000',
        ]);

        $managerId = (!empty($validated['manager_id']) && $validated['manager_id'] !== '0') ? (int) $validated['manager_id'] : null;
        $approver1Id = (!empty($validated['approver_1_id']) && $validated['approver_1_id'] !== '0') ? (int) $validated['approver_1_id'] : null;
        $approver2Id = (!empty($validated['approver_2_id']) && $validated['approver_2_id'] !== '0') ? (int) $validated['approver_2_id'] : null;

        if ($managerId && !$approver2Id) $approver2Id = $managerId;

        $dept = $this->departmentRepo->create([
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

    public function updateDepartment(Request $request, int $id): RedirectResponse
    {
        $dept = $this->departmentRepo->findById($id);
        if (!$dept) {
            return back()->with('error', 'Departemen tidak ditemukan.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:departments,name,' . $dept->id,
            'code' => 'required|string|max:50|unique:departments,code,' . $dept->id,
            'manager_id' => 'nullable',
            'approver_1_id' => 'nullable',
            'approver_2_id' => 'nullable',
            'approval_type' => 'required|in:3_tier,2_tier,1_tier,custom',
            'description' => 'nullable|string|max:1000',
        ]);

        $managerId = (!empty($validated['manager_id']) && $validated['manager_id'] !== '0') ? (int) $validated['manager_id'] : null;
        $approver1Id = (!empty($validated['approver_1_id']) && $validated['approver_1_id'] !== '0') ? (int) $validated['approver_1_id'] : null;
        $approver2Id = (!empty($validated['approver_2_id']) && $validated['approver_2_id'] !== '0') ? (int) $validated['approver_2_id'] : null;

        if ($managerId && !$approver2Id) $approver2Id = $managerId;

        $this->departmentRepo->update($dept, [
            'name' => trim($validated['name']),
            'code' => strtoupper(trim($validated['code'])),
            'manager_id' => $managerId,
            'approver_1_id' => $approver1Id,
            'approver_2_id' => $approver2Id,
            'approval_type' => $validated['approval_type'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->back()->with('success', "Departemen '{$dept->name}' berhasil diperbarui!");
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
            fputcsv($file, ['NIK', 'Nama Karyawan', 'Email', 'Password', 'Role (employee/manager/admin)', 'Kode/Nama Departemen', 'NIK/Email Atasan 1', 'NIK/Email Atasan 2', 'Jatah Kuota Cuti']);
            fputcsv($file, ['EMP-101', 'Budi Santoso', 'budi@sgin.com', 'password123', 'employee', 'Information Technology', '', '', '12']);
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
        $rowNumber = 0;
        $departments = $this->departmentRepo->getAll();

        while (($row = fgetcsv($handle, 2000, ',')) !== false) {
            $rowNumber++;
            if ($rowNumber === 1 && preg_match('/(nik|nama|email)/i', $row[0] ?? '')) continue;
            if (empty($row[1]) || empty($row[2])) continue;

            $nik = trim($row[0] ?? '') ?: 'EMP-' . date('Y') . '-' . rand(100, 999);
            $name = trim($row[1]);
            $email = trim($row[2]);
            $password = trim($row[3] ?? '') ?: 'password123';
            $role = in_array(strtolower(trim($row[4] ?? '')), ['employee', 'manager', 'admin', 'superadmin']) ? strtolower(trim($row[4])) : 'employee';
            $deptInput = trim($row[5] ?? '');
            $quota = isset($row[8]) && is_numeric($row[8]) ? (int) $row[8] : 12;

            $deptId = null;
            if ($deptInput) {
                $matched = $departments->first(fn($d) => strcasecmp($d->code, $deptInput) === 0 || strcasecmp($d->name, $deptInput) === 0);
                $deptId = $matched?->id;
            }

            $user = User::updateOrCreate(
                ['email' => $email],
                ['nik' => $nik, 'name' => $name, 'password' => Hash::make($password), 'role' => $role, 'department_id' => $deptId]
            );

            $this->quotaService->setTotalQuota($user->id, $quota);
            $successCount++;
        }

        fclose($handle);
        return back()->with('success', "Import berhasil! {$successCount} data karyawan berhasil diproses.");
    }
}
