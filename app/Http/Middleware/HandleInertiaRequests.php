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
                    'department_id' => $user->department_id,
                    'department_name' => $user->department ? $user->department->name : 'General',
                    'department' => $user->department,
                    'avatar' => $user->avatar_url,
                    'pending_approvals_count' => fn () => $user->getPendingApprovalsCount(),
                    'pending_approvals_list' => fn () => $user->getPendingApprovalsList(5),
                    'my_recent_requests' => fn () => $user->leaveRequests()->with('category')->latest()->take(3)->get(),
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ]);
    }
}
