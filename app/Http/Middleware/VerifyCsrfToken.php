<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'login',
        'leaves-application/login',
        '/login',
        '/leaves-application/login',
        'logout',
        'leaves-application/logout',
        '/logout',
        '/leaves-application/logout',
        'profile/biodata*',
        'leaves-application/profile/biodata*',
        '/profile/biodata*',
        '/leaves-application/profile/biodata*',
        'biodata*',
        'leaves-application/biodata*',
        '/biodata*',
        '/leaves-application/biodata*',
        'hrd/employees/*/biodata*',
        'leaves-application/hrd/employees/*/biodata*',
        '/hrd/employees/*/biodata*',
        '/leaves-application/hrd/employees/*/biodata*',
        'hrd/employees/*',
        'leaves-application/hrd/employees/*',
        '/hrd/employees/*',
        '/leaves-application/hrd/employees/*',
        'profile/*',
        'leaves-application/profile/*',
        '/profile/*',
        '/leaves-application/profile/*',
    ];
}
