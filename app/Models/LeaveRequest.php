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
        'approved_by',
        'approval_note',
        'approved_at',
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'approved_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

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
}
