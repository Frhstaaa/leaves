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
        'deducts_quota',
        'default_quota',
        'description',
    ];

    protected $casts = [
        'requires_attachment' => 'boolean',
        'deducts_quota' => 'boolean',
    ];

    /**
     * Check whether this absence category deducts annual leave quota.
     * Cuti Tahunan and Cuti Haid deduct quota, while other leave categories do not.
     */
    public function isQuotaDeductible(): bool
    {
        if ($this->deducts_quota !== null) {
            if ($this->deducts_quota) {
                return true;
            }
        }
        
        $nameLower = strtolower(trim($this->name));
        return in_array($nameLower, ['cuti tahunan', 'cuti haid']);
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }
}
