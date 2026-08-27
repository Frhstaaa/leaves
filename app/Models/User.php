<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'nik',
        'name',
        'email',
        'password',
        'role',
        'department_id',
        'manager_id',
        'approver_1_id',
        'approver_2_id',
        'avatar',

        // Form Data Diri PT SUGIYAMA INDONESIA Fields
        'join_date',
        'employee_status',
        'education',
        'position',
        'contract_end_date',

        'ktp_number',
        'gender',
        'birth_place',
        'birth_date',
        'phone_number',
        'ktp_address',
        'domicile_address',
        'marital_status',
        'mother_maiden_name',
        'kk_number',
        'blood_type',

        'npwp',
        'bpjs_kesehatan_number',
        'bpjs_health_facility',
        'bpjs_ketenagakerjaan_number',
        'bank_name',
        'bank_account_number',

        'vehicle_plate_number',
        'sim_number',
        'sim_valid_until',
        'shoe_size',

        'emergency_contact_name',
        'emergency_contact_relationship',
        'emergency_contact_phone',
        'emergency_contact_address',

        'spouse_name',
        'spouse_ktp_number',
        'spouse_birth_place',
        'spouse_birth_date',
        'child_1_name',
        'child_2_name',
        'child_3_name',

        'is_profile_completed',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'join_date' => 'date:Y-m-d',
        'birth_date' => 'date:Y-m-d',
        'contract_end_date' => 'date:Y-m-d',
        'sim_valid_until' => 'date:Y-m-d',
        'spouse_birth_date' => 'date:Y-m-d',
        'is_profile_completed' => 'boolean',
    ];

    protected $appends = [
        'avatar_url',
        'profile_completeness',
    ];

    /**
     * Hitung persentase kelengkapan data diri (0 - 100%).
     */
    public function getProfileCompletenessAttribute(): int
    {
        $fields = [
            'name',
            'nik',
            'email',
            'department_id',
            'join_date',
            'employee_status',
            'education',
            'position',
            'ktp_number',
            'gender',
            'birth_place',
            'birth_date',
            'phone_number',
            'ktp_address',
            'domicile_address',
            'marital_status',
            'mother_maiden_name',
            'kk_number',
            'blood_type',
            'emergency_contact_name',
            'emergency_contact_relationship',
            'emergency_contact_phone',
            'bank_account_number',
        ];

        $filled = 0;
        foreach ($fields as $field) {
            $val = $this->getAttributeValue($field);
            if ($val !== null && $val !== '') {
                $filled++;
            }
        }

        return (int) round(($filled / count($fields)) * 100);
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if (!$this->avatar) {
            return null;
        }

        if (str_starts_with($this->avatar, 'http://') || str_starts_with($this->avatar, 'https://')) {
            return $this->avatar;
        }

        return url('storage/' . ltrim($this->avatar, '/'));
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

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

    public function subordinates()
    {
        return $this->hasMany(User::class, 'manager_id');
    }

    public function leaveQuotas()
    {
        return $this->hasMany(LeaveQuota::class);
    }

    public function currentQuota()
    {
        return $this->hasOne(LeaveQuota::class)->where('year', date('Y'));
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function isSuperadmin(): bool
    {
        return $this->role === 'superadmin' || $this->hasRole('superadmin');
    }

    public function isEmployee(): bool
    {
        return $this->role === 'employee' || $this->hasRole('employee');
    }

    public function isManager(): bool
    {
        if ($this->role === 'manager' || $this->hasRole('manager')) {
            return true;
        }

        if (!in_array($this->role, ['employee', 'admin', 'superadmin']) && !empty($this->role)) {
            return true;
        }

        return false;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin' || $this->hasRole('admin') || $this->isSuperadmin();
    }

    public function isApprover(): bool
    {
        if ($this->isAdmin() || $this->isManager()) {
            return true;
        }

        if (method_exists($this, 'hasPermissionTo')) {
            try {
                if ($this->can('approve-leave-request') || $this->can('manage-approvals')) {
                    return true;
                }
            } catch (\Throwable $e) {}
        }

        // Check if user is assigned as approver_1_id, approver_2_id, or manager_id in any department
        $isDeptApprover = Department::where('approver_1_id', $this->id)
            ->orWhere('approver_2_id', $this->id)
            ->orWhere('manager_id', $this->id)
            ->exists();

        if ($isDeptApprover) {
            return true;
        }

        // Check if user is assigned as direct approver for any employee
        $isUserApprover = User::where('approver_1_id', $this->id)
            ->orWhere('approver_2_id', $this->id)
            ->orWhere('manager_id', $this->id)
            ->exists();

        return $isUserApprover;
    }

    public function getEffectiveApprover1()
    {
        if ($this->approver1) {
            return $this->approver1;
        }
        if ($this->department && $this->department->approver1) {
            return $this->department->approver1;
        }
        return null;
    }

    public function getEffectiveApprover2()
    {
        if ($this->approver2) {
            return $this->approver2;
        }
        if ($this->manager) {
            return $this->manager;
        }
        if ($this->department && $this->department->approver2) {
            return $this->department->approver2;
        }
        if ($this->department && $this->department->manager) {
            return $this->department->manager;
        }
        return null;
    }

    public function payslips()
    {
        return $this->hasMany(Payslip::class);
    }

    public function getPendingApprovalsQuery()
    {
        if ($this->isAdmin()) {
            return LeaveRequest::where('status', 'pending')
                ->where(function ($q) {
                    $q->where(function ($h) {
                        $h->where('current_stage', 'hrd')
                          ->orWhereNull('current_stage');
                    })->orWhere(function ($sub) {
                        $sub->where('current_stage', 'approval_1')
                            ->whereHas('user', function ($u) {
                                $u->where('approver_1_id', $this->id)
                                  ->orWhere(function ($d) {
                                      $d->whereNull('approver_1_id')
                                        ->whereHas('department', function ($dept) {
                                            $dept->where('approver_1_id', $this->id);
                                        });
                                  });
                            });
                    })->orWhere(function ($sub) {
                        $sub->where('current_stage', 'approval_2')
                            ->whereHas('user', function ($u) {
                                $u->where('approver_2_id', $this->id)
                                  ->orWhere('manager_id', $this->id)
                                  ->orWhere(function ($d) {
                                      $d->whereNull('manager_id')
                                        ->whereNull('approver_2_id')
                                        ->whereHas('department', function ($dept) {
                                            $dept->where('approver_2_id', $this->id)
                                                 ->orWhere('manager_id', $this->id);
                                        });
                                  });
                            });
                    });
                });
        }

        if ($this->isApprover()) {
            return LeaveRequest::where('status', 'pending')
                ->where(function ($q) {
                    $q->where(function ($sub) {
                        $sub->where('current_stage', 'approval_1')
                            ->whereHas('user', function ($u) {
                                $u->where('approver_1_id', $this->id)
                                  ->orWhere(function ($d) {
                                      $d->whereNull('approver_1_id')
                                        ->whereHas('department', function ($dept) {
                                            $dept->where('approver_1_id', $this->id);
                                        });
                                  });
                            });
                    })->orWhere(function ($sub) {
                        $sub->where('current_stage', 'approval_2')
                            ->whereHas('user', function ($u) {
                                $u->where('approver_2_id', $this->id)
                                  ->orWhere('manager_id', $this->id)
                                  ->orWhere(function ($d) {
                                      $d->whereNull('manager_id')
                                        ->whereNull('approver_2_id')
                                        ->whereHas('department', function ($dept) {
                                            $dept->where('approver_2_id', $this->id)
                                                 ->orWhere('manager_id', $this->id);
                                        });
                                  });
                            });
                    });
                });
        }

        return LeaveRequest::whereRaw('1 = 0');
    }

    public function getPendingApprovalsCount(): int
    {
        if (!$this->isApprover() && !$this->isAdmin()) {
            return 0;
        }

        return $this->getPendingApprovalsQuery()->count();
    }

    public function getPendingApprovalsList(int $limit = 5)
    {
        if (!$this->isApprover() && !$this->isAdmin()) {
            return collect();
        }

        return $this->getPendingApprovalsQuery()
            ->with(['user.department', 'category'])
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();
    }
}
