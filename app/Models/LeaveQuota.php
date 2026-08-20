<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Synchronize and recalculate used and remaining quota based on real approved leave requests.
     */
    public static function syncForUser(int $userId, ?int $year = null): self
    {
        $year = $year ?: (int) date('Y');

        $quota = self::firstOrCreate(
            ['user_id' => $userId, 'year' => $year],
            ['total_quota' => 12, 'used_quota' => 0, 'remaining_quota' => 12]
        );

        // Sum of all approved 'Cuti Tahunan' requests in days for this user in the specified year
        $usedDays = (int) LeaveRequest::where('user_id', $userId)
            ->where('status', 'approved')
            ->whereYear('start_date', $year)
            ->where('unit', 'hari')
            ->whereHas('category', function ($q) {
                $q->whereRaw('LOWER(name) = ?', ['cuti tahunan']);
            })
            ->sum('amount');

        $total = $quota->total_quota ?? 12;
        $remaining = max(0, $total - $usedDays);

        if ((int)$quota->used_quota !== $usedDays || (int)$quota->remaining_quota !== $remaining) {
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
