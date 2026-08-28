<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class LeaveQuota extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'year',
        'total_quota',
        'used_quota',
        'remaining_quota',
    ];

    protected $casts = [
        'total_quota' => 'float',
        'used_quota' => 'float',
        'remaining_quota' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Synchronize and recalculate used and remaining quota based on real approved leave requests.
     * Only Cuti Tahunan and Cuti Haid deduct quota; other categories do not deduct annual quota.
     */
    public static function syncForUser(int $userId, ?int $year = null): self
    {
        $year = $year ?: (int) date('Y');

        $quota = self::firstOrCreate(
            ['user_id' => $userId, 'year' => $year],
            ['total_quota' => 12.0, 'used_quota' => 0.0, 'remaining_quota' => 12.0]
        );

        // Sum of all approved 'Cuti Tahunan' & 'Cuti Haid' (or deducts_quota = true) in days
        $usedDays = (float) LeaveRequest::where('user_id', $userId)
            ->where('status', 'approved')
            ->whereYear('start_date', $year)
            ->where('unit', 'hari')
            ->whereHas('category', function ($q) {
                $q->where('deducts_quota', true)
                  ->orWhereRaw('LOWER(name) IN (?, ?)', ['cuti tahunan', 'cuti haid']);
            })
            ->sum('amount');

        $total = (float) ($quota->total_quota ?? 12.0);
        $remaining = max(0.0, $total - $usedDays);

        if ((float)$quota->used_quota !== (float)$usedDays || (float)$quota->remaining_quota !== (float)$remaining) {
            $quota->update([
                'used_quota' => $usedDays,
                'remaining_quota' => $remaining,
            ]);
        }

        return $quota;
    }

    /**
     * Synchronize all quotas for all users in the system.
     */
    public static function syncAllUsers(?int $year = null): void
    {
        $year = $year ?: (int) date('Y');
        $userIds = User::pluck('id');

        foreach ($userIds as $uid) {
            self::syncForUser($uid, $year);
        }
    }
}
