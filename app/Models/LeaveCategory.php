<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'unit_type',
        'requires_attachment',
        'default_quota',
        'description',
    ];

    protected $casts = [
        'requires_attachment' => 'boolean',
    ];

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }
}
