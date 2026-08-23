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
        // Automatically enforce HTTPS in production
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            URL::forceScheme('https');
        }

        // Dynamically bind Root URL to match request path & host for subfolders (e.g. /leaves-application)
        if (!app()->runningInConsole() && isset($_SERVER['HTTP_HOST'])) {
            $rootUrl = request()->root();
            if ($rootUrl) {
                URL::forceRootUrl($rootUrl);
            }
        }
    }
}
