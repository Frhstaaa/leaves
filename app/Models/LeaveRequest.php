<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_number',
        'submission_type',
        'approval_agreed',
        'user_id',
        'leave_category_id',
        'unit',
        'amount',
        'start_date',
        'end_date',
        'reason',
        'attachment_path',
        'attachment_name',
        'status',
        'current_stage',
        'approved_by_1',
        'approval_1_note',
        'approved_1_at',
        'approved_by_2',
        'approval_2_note',
        'approved_2_at',
        'approved_by_hrd',
        'approval_hrd_note',
        'approved_hrd_at',
        'approved_by',
        'approval_note',
        'approved_at',
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'approved_1_at' => 'datetime',
        'approved_2_at' => 'datetime',
        'approved_hrd_at' => 'datetime',
        'approved_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    protected $appends = [
        'attachment_url',
    ];

    public function getAttachmentUrlAttribute(): ?string
    {
        if (!$this->attachment_path) {
            return null;
        }

        if (str_starts_with($this->attachment_path, 'http://') || str_starts_with($this->attachment_path, 'https://')) {
            return $this->attachment_path;
        }

        $r2Url = env('CLOUDFLARE_R2_URL');
        $defaultDisk = config('filesystems.default', 'public');

        if (($defaultDisk === 'r2' || $defaultDisk === 's3') && $r2Url) {
            return rtrim($r2Url, '/') . '/' . ltrim($this->attachment_path, '/');
        }

        return url('storage/' . ltrim($this->attachment_path, '/'));
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(LeaveCategory::class, 'leave_category_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function approver1()
    {
        return $this->belongsTo(User::class, 'approved_by_1');
    }

    public function approver2()
    {
        return $this->belongsTo(User::class, 'approved_by_2');
    }

    public function approverHrd()
    {
        return $this->belongsTo(User::class, 'approved_by_hrd');
    }
}
