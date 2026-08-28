<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/Login');
    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => ['required', 'string'],
                'password' => ['required', 'string'],
            ], [
                'email.required' => 'Email atau NIK wajib diisi.',
                'password.required' => 'Kata sandi wajib diisi.',
            ]);

            $loginInput = trim($request->input('email'));
            $password = $request->input('password');

            // Check if input is an email format or NIK
            $isEmail = filter_var($loginInput, FILTER_VALIDATE_EMAIL);
            $field = $isEmail ? 'email' : 'nik';

            // Security: Rate Limiting by Login Identifier + Client IP (5 attempts per minute)
            $throttleKey = Str::transliterate(Str::lower($loginInput) . '|' . $request->ip());

            try {
                if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
                    $seconds = RateLimiter::availableIn($throttleKey);
                    return back()->withErrors([
                        'email' => "Terlalu banyak percobaan masuk yang salah. Silakan coba kembali dalam {$seconds} detik demi keamanan akun Anda.",
                    ]);
                }
            } catch (\Throwable $e) {}

            $credentials = [
                $field => $loginInput,
                'password' => $password,
            ];

            if (Auth::attempt($credentials, $request->boolean('remember'))) {
                try { RateLimiter::clear($throttleKey); } catch (\Throwable $e) {}
                $request->session()->regenerate();
                return redirect()->intended(route('dashboard'));
            }

            try { RateLimiter::hit($throttleKey, 60); } catch (\Throwable $e) {}

            return back()->withErrors([
                'email' => 'Email / NIK atau kata sandi yang Anda masukkan tidak cocok.',
            ])->onlyInput('email');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()->withErrors([
                'email' => 'Kendala Server / Database: ' . $e->getMessage(),
            ]);
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
