<?php

namespace App\Repositories\Contracts;

use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface LeaveRequestRepositoryInterface
{
    public function findById(int $id, array $relations = []): ?LeaveRequest;

    public function getUserRequestsPaginated(int $userId, array $filters = [], int $perPage = 10): LengthAwarePaginator;

    public function getRecentUserRequests(int $userId, int $limit = 5): Collection;

    public function getPendingApprovalsForUser(User $user, array $filters = [], int $perPage = 10): LengthAwarePaginator;

    public function countPendingApprovalsForUser(User $user): int;

    public function getHrdRecapPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function getHrdRecapAll(array $filters = []): Collection;

    public function create(array $data): LeaveRequest;

    public function update(LeaveRequest $leaveRequest, array $data): bool;

    public function delete(LeaveRequest $leaveRequest): bool;
}
