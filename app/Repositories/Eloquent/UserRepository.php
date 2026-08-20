<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class UserRepository implements UserRepositoryInterface
{
    public function findById(int $id, array $relations = ['department', 'currentQuota', 'manager', 'approver1', 'approver2']): ?User
    {
        return User::with($relations)->find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function getEmployeesPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->buildEmployeeQuery($filters);
        return $query->latest('id')->paginate($perPage)->withQueryString();
    }

    public function getEmployeesAll(array $filters = []): Collection
    {
        $query = $this->buildEmployeeQuery($filters);
        return $query->latest('id')->get();
    }

    public function getManagers(): Collection
    {
        return User::whereIn('role', ['manager', 'admin', 'superadmin'])
            ->orderBy('name')
            ->get(['id', 'name', 'nik', 'role', 'department_id']);
    }

    private function buildEmployeeQuery(array $filters)
    {
        $query = User::with([
            'department',
            'currentQuota',
            'approver1',
            'approver2',
            'manager'
        ]);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        return $query;
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(User $user, array $data): bool
    {
        return $user->update($data);
    }

    public function delete(User $user): bool
    {
        return $user->delete();
    }
}
