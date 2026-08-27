<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     */
    public function version(Request $request): ?string
    {
        $manifest = public_path('build/manifest.json');
        if (file_exists($manifest)) {
            return md5_file($manifest);
        }
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        if ($user) {
            $user->load('department');
        }

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'nik' => $user->nik,
                    'role' => $user->role,
                    'roles' => fn () => $user->getRoleNames(),
                    'permissions' => fn () => $user->getAllPermissions()->pluck('name'),
                    'is_superadmin' => $user->isSuperadmin(),
                    'is_admin' => $user->isAdmin(),
                    'is_manager' => $user->isManager(),
                    'is_approver' => $user->isApprover(),
                    'department_id' => $user->department_id,
                    'department_name' => $user->department ? $user->department->name : 'General',
                    'department' => $user->department,
                    'avatar' => $user->avatar_url,
                    'profile_completeness' => $user->profile_completeness,
                    'is_profile_completed' => (bool) $user->is_profile_completed,
                    'join_date' => $user->join_date ? $user->join_date->format('Y-m-d') : null,
                    'employee_status' => $user->employee_status,
                    'education' => $user->education,
                    'position' => $user->position,
                    'contract_end_date' => $user->contract_end_date ? $user->contract_end_date->format('Y-m-d') : null,
                    'ktp_number' => $user->ktp_number,
                    'gender' => $user->gender,
                    'birth_place' => $user->birth_place,
                    'birth_date' => $user->birth_date ? $user->birth_date->format('Y-m-d') : null,
                    'phone_number' => $user->phone_number,
                    'ktp_address' => $user->ktp_address,
                    'domicile_address' => $user->domicile_address,
                    'marital_status' => $user->marital_status,
                    'mother_maiden_name' => $user->mother_maiden_name,
                    'kk_number' => $user->kk_number,
                    'blood_type' => $user->blood_type,
                    'npwp' => $user->npwp,
                    'bpjs_kesehatan_number' => $user->bpjs_kesehatan_number,
                    'bpjs_health_facility' => $user->bpjs_health_facility,
                    'bpjs_ketenagakerjaan_number' => $user->bpjs_ketenagakerjaan_number,
                    'bank_name' => $user->bank_name,
                    'bank_account_number' => $user->bank_account_number,
                    'vehicle_plate_number' => $user->vehicle_plate_number,
                    'sim_number' => $user->sim_number,
                    'sim_valid_until' => $user->sim_valid_until ? $user->sim_valid_until->format('Y-m-d') : null,
                    'shoe_size' => $user->shoe_size,
                    'emergency_contact_name' => $user->emergency_contact_name,
                    'emergency_contact_relationship' => $user->emergency_contact_relationship,
                    'emergency_contact_phone' => $user->emergency_contact_phone,
                    'emergency_contact_address' => $user->emergency_contact_address,
                    'spouse_name' => $user->spouse_name,
                    'spouse_ktp_number' => $user->spouse_ktp_number,
                    'spouse_birth_place' => $user->spouse_birth_place,
                    'spouse_birth_date' => $user->spouse_birth_date ? $user->spouse_birth_date->format('Y-m-d') : null,
                    'child_1_name' => $user->child_1_name,
                    'child_2_name' => $user->child_2_name,
                    'child_3_name' => $user->child_3_name,
                    'pending_approvals_count' => fn () => $user->getPendingApprovalsCount(),
                    'pending_approvals_list' => fn () => $user->getPendingApprovalsList(5),
                    'my_recent_requests' => fn () => $user->leaveRequests()->with('category')->latest()->take(3)->get(),
                ] : null,
            ],
            'app_settings' => fn () => \App\Models\Setting::getAll(),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ]);
    }
}
