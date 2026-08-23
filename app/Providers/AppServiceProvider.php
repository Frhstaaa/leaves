<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Dynamically bind Root URL and Scheme with subfolder detection (e.g. /leaves-application)
        if (!app()->runningInConsole() && isset($_SERVER['HTTP_HOST'])) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : (request()->isSecure() ? 'https' : 'http');
            $host = $_SERVER['HTTP_HOST'];
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            
            $subfolder = '';
            if (str_contains($uri, 'leaves-application')) {
                $subfolder = '/leaves-application';
            } elseif (preg_match('#^(/[^/]+)#', $uri, $m) && !in_array($m[1], ['/login', '/dashboard', '/build', '/api', '/sw.js', '/quick-login', '/logout'])) {
                $subfolder = $m[1];
            }

            $forcedRoot = $scheme . '://' . $host . $subfolder;
            URL::forceRootUrl($forcedRoot);
            URL::forceScheme($scheme);
        } else {
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
                URL::forceScheme('https');
            }
        }
    }
}
