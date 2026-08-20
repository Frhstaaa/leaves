<?php

namespace App\Repositories\Eloquent;

use App\Models\Department;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class DepartmentRepository implements DepartmentRepositoryInterface
{
    public function getAll(array $relations = ['manager', 'approver1', 'approver2', 'users']): Collection
    {
        return Department::with($relations)->withCount('users')->orderBy('name')->get();
    }

    public function findById(int $id, array $relations = ['manager', 'approver1', 'approver2', 'users']): ?Department
    {
        return Department::with($relations)->find($id);
    }

    public function create(array $data): Department
    {
        return Department::create($data);
    }

    public function update(Department $department, array $data): bool
    {
        return $department->update($data);
    }

    public function delete(Department $department): bool
    {
        return $department->delete();
    }
}
