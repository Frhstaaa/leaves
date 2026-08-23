<?php

namespace App\Repositories\Eloquent;

use App\Models\LeaveRequest;
use App\Models\User;
use App\Repositories\Contracts\LeaveRequestRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class LeaveRequestRepository implements LeaveRequestRepositoryInterface
{
    public function findById(int $id, array $relations = ['user.department', 'category', 'approver', 'approver1', 'approver2']): ?LeaveRequest
    {
        return LeaveRequest::with($relations)->find($id);
    }

    public function getUserRequestsPaginated(int $userId, array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = LeaveRequest::with(['category', 'approver', 'approver1', 'approver2'])
            ->where('user_id', $userId);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('request_number', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['status']) && in_array($filters['status'], ['pending', 'approved', 'rejected'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest('id')->paginate($perPage)->withQueryString();
    }

    public function getRecentUserRequests(int $userId, int $limit = 5): Collection
    {
        return LeaveRequest::with(['category', 'approver'])
            ->where('user_id', $userId)
            ->latest('id')
            ->take($limit)
            ->get();
    }

    public function getPendingApprovalsForUser(User $user, array $filters = [], int $perPage = 10): LengthAwarePaginator
    {
        $query = $user->getPendingApprovalsQuery();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('request_number', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('nik', 'like', "%{$search}%");
                    });
            });
        }

        if (!empty($filters['status']) && in_array($filters['status'], ['pending', 'approved', 'rejected'])) {
            $query->where('status', $filters['status']);
        }

        // Sub-filter by stage for HRD
        if ($user->isAdmin() && !empty($filters['stage']) && in_array($filters['stage'], ['approval_1', 'approval_2', 'hrd'])) {
            $query->where('current_stage', $filters['stage']);
        }

        return $query->latest('id')->paginate($perPage)->withQueryString();
    }

    public function countPendingApprovalsForUser(User $user): int
    {
        return $user->getPendingApprovalsQuery()->count();
    }

    public function getHrdRecapPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->buildHrdRecapQuery($filters);
        return $query->latest('id')->paginate($perPage)->withQueryString();
    }

    public function getHrdRecapAll(array $filters = []): Collection
    {
        $query = $this->buildHrdRecapQuery($filters);
        return $query->latest('id')->get();
    }

    private function buildHrdRecapQuery(array $filters)
    {
        $query = LeaveRequest::with(['user.department', 'category', 'approver', 'approver1', 'approver2']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('request_number', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('nik', 'like', "%{$search}%");
                    });
            });
        }

        if (!empty($filters['status']) && in_array($filters['status'], ['pending', 'approved', 'rejected'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['department_id'])) {
            $query->whereHas('user', function ($q) use ($filters) {
                $q->where('department_id', $filters['department_id']);
            });
        }

        if (!empty($filters['category_id'])) {
            $query->where('leave_category_id', $filters['category_id']);
        }

        if (!empty($filters['start_date'])) {
            $query->where('start_date', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->where('end_date', '<=', $filters['end_date']);
        }

        return $query;
    }

    public function create(array $data): LeaveRequest
    {
        return LeaveRequest::create($data);
    }

    public function update(LeaveRequest $leaveRequest, array $data): bool
    {
        return $leaveRequest->update($data);
    }

    public function delete(LeaveRequest $leaveRequest): bool
    {
        return $leaveRequest->delete();
    }
}
