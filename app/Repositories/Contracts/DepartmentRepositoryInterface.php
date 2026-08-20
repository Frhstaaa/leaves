<?php

namespace App\Repositories\Contracts;

use App\Models\Department;
use Illuminate\Database\Eloquent\Collection;

interface DepartmentRepositoryInterface
{
    public function getAll(array $relations = []): Collection;

    public function findById(int $id, array $relations = []): ?Department;

    public function create(array $data): Department;

    public function update(Department $department, array $data): bool;

    public function delete(Department $department): bool;
}
