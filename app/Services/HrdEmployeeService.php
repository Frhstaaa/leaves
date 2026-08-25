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
     * Create a new employee with initial quota.
     */
    public function createEmployee(array $data, $avatarFile = null): User
    {
        return DB::transaction(function () use ($data, $avatarFile) {
            $avatarPath = null;
            if ($avatarFile) {
                $avatarPath = MediaOptimizer::convertImageToWebp($avatarFile, 'avatars', 85, 400, 400);
            }

            $user = $this->userRepo->create([
                'nik' => $data['nik'],
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password'] ?? 'password123'),
                'role' => $data['role'] ?? 'employee',
                'department_id' => $data['department_id'] ?: null,
                'approver_1_id' => $data['approver_1_id'] ?: null,
                'approver_2_id' => $data['approver_2_id'] ?: null,
                'manager_id' => $data['manager_id'] ?: ($data['approver_2_id'] ?: ($data['approver_1_id'] ?: null)),
                'avatar' => $avatarPath,
            ]);

            // Assign Spatie Role
            if (method_exists($user, 'assignRole')) {
                $user->syncRoles([$user->role]);
            }

            // Create initial quota
            $totalQuota = (int) ($data['total_quota'] ?? 12);
            $this->quotaService->setTotalQuota($user->id, $totalQuota);

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
                'department_id' => !empty($data['department_id']) ? $data['department_id'] : null,
                'approver_1_id' => !empty($data['approver_1_id']) ? $data['approver_1_id'] : null,
                'approver_2_id' => !empty($data['approver_2_id']) ? $data['approver_2_id'] : null,
                'manager_id' => !empty($data['manager_id']) ? $data['manager_id'] : (!empty($data['approver_2_id']) ? $data['approver_2_id'] : (!empty($data['approver_1_id']) ? $data['approver_1_id'] : null)),
            ];

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

            $this->userRepo->update($user, $updateData);

            if (method_exists($user, 'syncRoles')) {
                $user->syncRoles([$updateData['role']]);
            }

            if (isset($data['total_quota'])) {
                $this->quotaService->setTotalQuota($user->id, (int) $data['total_quota']);
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
