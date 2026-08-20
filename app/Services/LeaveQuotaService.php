<?php

namespace App\Services;

use App\Models\LeaveQuota;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LeaveQuotaService
{
    /**
     * Get and synchronize user leave quota for a specific year.
     */
    public function getOrSyncUserQuota(int $userId, ?int $year = null): LeaveQuota
    {
        $year = $year ?: (int) date('Y');
        return LeaveQuota::syncForUser($userId, $year);
    }

    /**
     * Deduct quota for approved leave request.
     */
    public function deductQuota(LeaveRequest $leaveRequest): void
    {
        if ($leaveRequest->unit !== 'hari') {
            return;
        }

        $isAnnualLeave = $leaveRequest->category && strtolower($leaveRequest->category->name) === 'cuti tahunan';
        if (!$isAnnualLeave) {
            return;
        }

        $year = (int) date('Y', strtotime($leaveRequest->start_date));
        $this->getOrSyncUserQuota($leaveRequest->user_id, $year);
    }

    /**
     * Restore quota for rejected or cancelled leave request.
     */
    public function restoreQuota(LeaveRequest $leaveRequest): void
    {
        if ($leaveRequest->unit !== 'hari') {
            return;
        }

        $year = (int) date('Y', strtotime($leaveRequest->start_date));
        $this->getOrSyncUserQuota($leaveRequest->user_id, $year);
    }

    /**
     * Update total quota allowance for an employee.
     */
    public function setTotalQuota(int $userId, int $totalQuota, ?int $year = null): LeaveQuota
    {
        $year = $year ?: (int) date('Y');

        return DB::transaction(function () use ($userId, $totalQuota, $year) {
            $quota = LeaveQuota::firstOrCreate(
                ['user_id' => $userId, 'year' => $year],
                ['total_quota' => $totalQuota, 'used_quota' => 0, 'remaining_quota' => $totalQuota]
            );

            $used = (int) LeaveRequest::where('user_id', $userId)
                ->where('status', 'approved')
                ->whereYear('start_date', $year)
                ->where('unit', 'hari')
                ->whereHas('category', function ($q) {
                    $q->whereRaw('LOWER(name) = ?', ['cuti tahunan']);
                })
                ->sum('amount');

            $remaining = max(0, $totalQuota - $used);

            $quota->update([
                'total_quota' => $totalQuota,
                'used_quota' => $used,
                'remaining_quota' => $remaining,
            ]);

            return $quota;
        });
    }

    /**
     * Synchronize all quotas in system.
     */
    public function syncAllQuotas(?int $year = null): void
    {
        LeaveQuota::syncAllUsers($year);
    }
}
