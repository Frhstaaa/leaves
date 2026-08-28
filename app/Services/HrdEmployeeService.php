<?php

namespace App\Services;

use App\Models\LeaveQuota;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class HrdEmployeeService
{
    protected UserRepositoryInterface $userRepo;
    protected LeaveQuotaService $quotaService;

    public function __construct(
        UserRepositoryInterface $userRepo,
        LeaveQuotaService $quotaService
    ) {
        $this->userRepo = $userRepo;
        $this->quotaService = $quotaService;
    }

    /**
     * Create a new employee with initial quota and biodata.
     */
    public function createEmployee(array $data, $avatarFile = null): User
    {
        return DB::transaction(function () use ($data, $avatarFile) {
            $avatarPath = null;
            if ($avatarFile) {
                $avatarPath = MediaOptimizer::convertImageToWebp($avatarFile, 'avatars', 85, 400, 400);
            }

            $userData = [
                'nik' => $data['nik'],
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password'] ?? 'password123'),
                'role' => $data['role'] ?? 'employee',
                'department_id' => !empty($data['department_id']) ? $data['department_id'] : null,
                'approver_1_id' => !empty($data['approver_1_id']) ? $data['approver_1_id'] : null,
                'approver_2_id' => !empty($data['approver_2_id']) ? $data['approver_2_id'] : null,
                'manager_id' => !empty($data['manager_id']) ? $data['manager_id'] : (!empty($data['approver_2_id']) ? $data['approver_2_id'] : (!empty($data['approver_1_id']) ? $data['approver_1_id'] : null)),
                'avatar' => $avatarPath,

                // Optional Biodata Fields
                'join_date' => $data['join_date'] ?? null,
                'employee_status' => $data['employee_status'] ?? 'Tetap',
                'education' => $data['education'] ?? null,
                'position' => $data['position'] ?? null,
                'contract_end_date' => $data['contract_end_date'] ?? null,
                'ktp_number' => $data['ktp_number'] ?? null,
                'gender' => $data['gender'] ?? null,
                'birth_place' => $data['birth_place'] ?? null,
                'birth_date' => $data['birth_date'] ?? null,
                'phone_number' => $data['phone_number'] ?? null,
                'ktp_address' => $data['ktp_address'] ?? null,
                'domicile_address' => $data['domicile_address'] ?? null,
                'marital_status' => $data['marital_status'] ?? null,
                'mother_maiden_name' => $data['mother_maiden_name'] ?? null,
                'kk_number' => $data['kk_number'] ?? null,
                'blood_type' => $data['blood_type'] ?? null,
                'npwp' => $data['npwp'] ?? null,
                'bpjs_kesehatan_number' => $data['bpjs_kesehatan_number'] ?? null,
                'bpjs_health_facility' => $data['bpjs_health_facility'] ?? null,
                'bpjs_ketenagakerjaan_number' => $data['bpjs_ketenagakerjaan_number'] ?? null,
                'bank_name' => $data['bank_name'] ?? null,
                'bank_account_number' => $data['bank_account_number'] ?? null,
                'vehicle_plate_number' => $data['vehicle_plate_number'] ?? null,
                'sim_number' => $data['sim_number'] ?? null,
                'sim_valid_until' => $data['sim_valid_until'] ?? null,
                'shoe_size' => $data['shoe_size'] ?? null,
                'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
                'emergency_contact_relationship' => $data['emergency_contact_relationship'] ?? null,
                'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
                'emergency_contact_address' => $data['emergency_contact_address'] ?? null,
                'spouse_name' => $data['spouse_name'] ?? null,
                'spouse_ktp_number' => $data['spouse_ktp_number'] ?? null,
                'spouse_birth_place' => $data['spouse_birth_place'] ?? null,
                'spouse_birth_date' => $data['spouse_birth_date'] ?? null,
                'child_1_name' => $data['child_1_name'] ?? null,
                'child_2_name' => $data['child_2_name'] ?? null,
                'child_3_name' => $data['child_3_name'] ?? null,
            ];

            $user = $this->userRepo->create($userData);

            // Assign Spatie Role
            if (method_exists($user, 'syncRoles') && !empty($user->role)) {
                try {
                    if (class_exists('\\Spatie\\Permission\\Models\\Role')) {
                        \Spatie\Permission\Models\Role::firstOrCreate(['name' => $user->role, 'guard_name' => 'web']);
                        $user->syncRoles([$user->role]);
                    }
                } catch (\Throwable $e) {}
            }

            // Create initial quota
            $totalQuota = (float) ($data['total_quota'] ?? 12.0);
            $remainingQuota = isset($data['remaining_quota']) && $data['remaining_quota'] !== '' ? (float) $data['remaining_quota'] : null;
            $this->quotaService->setQuota($user->id, $totalQuota, $remainingQuota);

            return $user;
        });
    }

    /**
     * Update existing employee.
     */
    public function updateEmployee(User $user, array $data, $avatarFile = null): User
    {
        return DB::transaction(function () use ($user, $data, $avatarFile) {
            $updateData = [
                'nik' => $data['nik'] ?? $user->nik,
                'name' => $data['name'] ?? $user->name,
                'email' => $data['email'] ?? $user->email,
                'role' => $data['role'] ?? $user->role,
                'department_id' => array_key_exists('department_id', $data) ? (!empty($data['department_id']) ? $data['department_id'] : null) : $user->department_id,
                'approver_1_id' => array_key_exists('approver_1_id', $data) ? (!empty($data['approver_1_id']) ? $data['approver_1_id'] : null) : $user->approver_1_id,
                'approver_2_id' => array_key_exists('approver_2_id', $data) ? (!empty($data['approver_2_id']) ? $data['approver_2_id'] : null) : $user->approver_2_id,
                'manager_id' => array_key_exists('manager_id', $data) ? (!empty($data['manager_id']) ? $data['manager_id'] : null) : $user->manager_id,
            ];

            // Biodata fields if provided in $data
            $biodataKeys = [
                'join_date', 'employee_status', 'education', 'position', 'contract_end_date',
                'ktp_number', 'gender', 'birth_place', 'birth_date', 'phone_number',
                'ktp_address', 'domicile_address', 'marital_status', 'mother_maiden_name',
                'kk_number', 'blood_type', 'npwp', 'bpjs_kesehatan_number', 'bpjs_health_facility',
                'bpjs_ketenagakerjaan_number', 'bank_name', 'bank_account_number',
                'vehicle_plate_number', 'sim_number', 'sim_valid_until', 'shoe_size',
                'emergency_contact_name', 'emergency_contact_relationship', 'emergency_contact_phone', 'emergency_contact_address',
                'spouse_name', 'spouse_ktp_number', 'spouse_birth_place', 'spouse_birth_date',
                'child_1_name', 'child_2_name', 'child_3_name', 'is_profile_completed'
            ];

            foreach ($biodataKeys as $key) {
                if (array_key_exists($key, $data)) {
                    $updateData[$key] = $data[$key];
                }
            }

            if (!empty($data['password'])) {
                $updateData['password'] = Hash::make($data['password']);
            }

            if ($avatarFile) {
                if ($user->avatar) {
                    $disk = config('filesystems.default', 'public');
                    @Storage::disk($disk)->delete($user->avatar);
                    if ($disk !== 'public') {
                        @Storage::disk('public')->delete($user->avatar);
                    }
                }
                $updateData['avatar'] = MediaOptimizer::convertImageToWebp($avatarFile, 'avatars', 85, 400, 400);
            }

            $user->fill($updateData);
            try {
                $user->is_profile_completed = ($user->profile_completeness >= 75);
                $updateData['is_profile_completed'] = $user->is_profile_completed;
            } catch (\Throwable $e) {}

            $this->userRepo->update($user, $updateData);

            if (method_exists($user, 'syncRoles') && !empty($updateData['role'])) {
                try {
                    if (class_exists('\\Spatie\\Permission\\Models\\Role')) {
                        \Spatie\Permission\Models\Role::firstOrCreate(['name' => $updateData['role'], 'guard_name' => 'web']);
                        $user->syncRoles([$updateData['role']]);
                    }
                } catch (\Throwable $e) {}
            }

            if (isset($data['total_quota'])) {
                $remainingQuota = isset($data['remaining_quota']) && $data['remaining_quota'] !== '' ? (float) $data['remaining_quota'] : null;
                $this->quotaService->setQuota($user->id, (float) $data['total_quota'], $remainingQuota);
            }

            return $user->fresh();
        });
    }

    /**
     * Delete employee and cleanup data.
     */
    public function deleteEmployee(User $user): bool
    {
        return DB::transaction(function () use ($user) {
            if ($user->avatar) {
                $disk = config('filesystems.default', 'public');
                @Storage::disk($disk)->delete($user->avatar);
                if ($disk !== 'public') {
                    @Storage::disk('public')->delete($user->avatar);
                }
            }
            LeaveQuota::where('user_id', $user->id)->delete();
            return $this->userRepo->delete($user);
        });
    }
}
