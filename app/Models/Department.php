<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'manager_id',
        'approver_1_id',
        'approver_2_id',
        'approval_type',
        'description',
    ];

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function approver1()
    {
        return $this->belongsTo(User::class, 'approver_1_id');
    }

    public function approver2()
    {
        return $this->belongsTo(User::class, 'approver_2_id');
    }

    public function employees()
    {
        return $this->hasMany(User::class, 'department_id');
    }
}
