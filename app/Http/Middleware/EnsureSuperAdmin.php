<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !($user->isSuperadmin() || $user->isAdmin() || $user->role === 'superadmin' || $user->role === 'admin' || $user->hasRole('superadmin') || $user->hasRole('admin'))) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Unauthorized. Halaman ini hanya untuk Superadmin / Admin.'], 403);
            }
            return redirect()->route('dashboard')->with('error', 'Akses ditolak. Anda tidak memiliki izin Superadmin / Admin.');
        }

        return $next($request);
    }
}
