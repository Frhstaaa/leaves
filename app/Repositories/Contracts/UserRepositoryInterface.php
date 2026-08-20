<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    public function findById(int $id, array $relations = []): ?User;

    public function findByEmail(string $email): ?User;

    public function getEmployeesPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function getEmployeesAll(array $filters = []): Collection;

    public function getManagers(): Collection;

    public function create(array $data): User;

    public function update(User $user, array $data): bool;

    public function delete(User $user): bool;
}
