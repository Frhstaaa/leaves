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
     * Deduct quota for approved leave request (Cuti Tahunan & Cuti Haid).
     */
    public function deductQuota(LeaveRequest $leaveRequest): void
    {
        if ($leaveRequest->unit !== 'hari') {
            return;
        }

        $category = $leaveRequest->category;
        $isDeductible = $category && (
            $category->deducts_quota || 
            in_array(strtolower(trim($category->name)), ['cuti tahunan', 'cuti haid'])
        );

        if (!$isDeductible) {
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

        $category = $leaveRequest->category;
        $isDeductible = $category && (
            $category->deducts_quota || 
            in_array(strtolower(trim($category->name)), ['cuti tahunan', 'cuti haid'])
        );

        if (!$isDeductible) {
            return;
        }

        $year = (int) date('Y', strtotime($leaveRequest->start_date));
        $this->getOrSyncUserQuota($leaveRequest->user_id, $year);
    }

    /**
     * Update total quota and optionally remaining quota for an employee.
     */
    public function setQuota(int $userId, float|int $totalQuota, float|int|null $remainingQuota = null, ?int $year = null): LeaveQuota
    {
        $year = $year ?: (int) date('Y');
        $totalQuota = (float) $totalQuota;
        $remainingQuota = $remainingQuota !== null ? (float) $remainingQuota : null;

        return DB::transaction(function () use ($userId, $totalQuota, $remainingQuota, $year) {
            $quota = LeaveQuota::firstOrCreate(
                ['user_id' => $userId, 'year' => $year],
                ['total_quota' => $totalQuota, 'used_quota' => 0.0, 'remaining_quota' => $totalQuota]
            );

            if ($remainingQuota !== null) {
                $remaining = max(0.0, min($totalQuota, $remainingQuota));
                $used = max(0.0, $totalQuota - $remaining);
            } else {
                $used = (float) LeaveRequest::where('user_id', $userId)
                    ->where('status', 'approved')
                    ->whereYear('start_date', $year)
                    ->where('unit', 'hari')
                    ->whereHas('category', function ($q) {
                        $q->where('deducts_quota', true)
                          ->orWhereRaw('LOWER(name) IN (?, ?)', ['cuti tahunan', 'cuti haid']);
                    })
                    ->sum('amount');
                $remaining = max(0.0, $totalQuota - $used);
            }

            $quota->update([
                'total_quota' => $totalQuota,
                'used_quota' => $used,
                'remaining_quota' => $remaining,
            ]);

            return $quota;
        });
    }

    /**
     * Update total quota allowance for an employee.
     */
    public function setTotalQuota(int $userId, float|int $totalQuota, ?int $year = null): LeaveQuota
    {
        return $this->setQuota($userId, $totalQuota, null, $year);
    }

    /**
     * Synchronize all quotas in system.
     */
    public function syncAllQuotas(?int $year = null): void
    {
        LeaveQuota::syncAllUsers($year);
    }
}
