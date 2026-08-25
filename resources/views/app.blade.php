<!DOCTYPE html>
<html lang="id" class="h-full bg-[#F5FAF7] text-slate-900">
<head>
    <meta charset="utf-8" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
    <meta name="description" content="Sistem Informasi Form SGIN - Pengajuan Cuti, Izin, Sakit, Lembur, dan Distribusi Slip Gaji Karyawan Real-time." />
    <meta name="theme-color" content="#F5FAF7" />
    <meta name="apple-mobile-web-app-status-bar-style" content="default" />
    <meta name="msapplication-navbutton-color" content="#F5FAF7" />
    <title>Absence & Leave Management System - Form SGIN</title>
    <link rel="dns-prefetch" href="https://fonts.googleapis.com">
    <link rel="dns-prefetch" href="https://fonts.gstatic.com">
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
    <script src="https://cdn.tailwindcss.com"></script>

    @php
        // Dynamically compute exact URL with subfolder prefix (e.g. https://www.sgin.co.id/leaves-application)
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : (request()->isSecure() ? 'https' : 'http');
        $host = $_SERVER['HTTP_HOST'] ?? request()->getHost();
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        
        $subfolder = '';
        if (str_contains($uri, 'leaves-application')) {
            $subfolder = '/leaves-application';
        } elseif (preg_match('#^(/[^/]+)#', $uri, $m) && !in_array($m[1], ['/login', '/dashboard', '/build', '/api', '/sw.js', '/quick-login', '/logout'])) {
            $subfolder = $m[1];
        }

        $appBaseUrl = $scheme . '://' . $host . $subfolder;
        
        // Multi-candidate search for manifest.json
        $manifestCandidates = [
            public_path('build/manifest.json'),
            base_path('public/build/manifest.json'),
            base_path('build/manifest.json'),
            __DIR__ . '/../../public/build/manifest.json',
            __DIR__ . '/../../../public/build/manifest.json',
        ];

        $manifest = [];
        foreach ($manifestCandidates as $candidate) {
            if (file_exists($candidate)) {
                $manifest = json_decode(file_get_contents($candidate), true) ?: [];
                if (!empty($manifest)) break;
            }
        }

        $cssFile = '';
        if (isset($manifest['resources/js/app.jsx']['css'][0])) {
            $cssFile = 'build/' . $manifest['resources/js/app.jsx']['css'][0];
        } elseif (isset($manifest['resources/css/app.css']['file'])) {
            $cssFile = 'build/' . $manifest['resources/css/app.css']['file'];
        }

        $jsFile = isset($manifest['resources/js/app.jsx']['file']) ? 'build/' . $manifest['resources/js/app.jsx']['file'] : '';
        $imports = isset($manifest['resources/js/app.jsx']['imports']) ? $manifest['resources/js/app.jsx']['imports'] : [];

        // Automatic directory scan fallback if manifest is missing or files not resolved
        if (empty($cssFile) || empty($jsFile)) {
            $assetsDir = public_path('build/assets');
            if (!is_dir($assetsDir)) {
                $assetsDir = base_path('public/build/assets');
            }
            if (is_dir($assetsDir)) {
                $scanned = scandir($assetsDir);
                foreach ($scanned as $f) {
                    if (str_starts_with($f, 'app-') && str_ends_with($f, '.css') && empty($cssFile)) {
                        $cssFile = 'build/assets/' . $f;
                    }
                    if (str_starts_with($f, 'app-') && str_ends_with($f, '.js') && empty($jsFile)) {
                        $jsFile = 'build/assets/' . $f;
                    }
                }
            }
        }

        $settings = \App\Models\Setting::getAll();
        $appName = $settings['app_name'] ?? 'Form SGIN';
        $themeColor = $settings['theme_color'] ?? '#F5FAF7';
        $pwaVersion = substr(md5(($settings['app_logo'] ?? '') . ($settings['app_name'] ?? '')), 0, 8);
    @endphp

    <base href="{{ $appBaseUrl }}/">
    <link rel="manifest" href="{{ $appBaseUrl }}/manifest.webmanifest?v={{ $pwaVersion }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ $appBaseUrl }}/app-icon/180?v={{ $pwaVersion }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ $appBaseUrl }}/app-icon/192?v={{ $pwaVersion }}">
    <link rel="icon" type="image/png" sizes="512x512" href="{{ $appBaseUrl }}/app-icon/512?v={{ $pwaVersion }}">
    <link rel="shortcut icon" href="{{ $appBaseUrl }}/app-icon/192?v={{ $pwaVersion }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="{{ $appName }}">
    <meta name="application-name" content="{{ $appName }}">

    @if($cssFile)
        <link rel="preload" as="style" href="{{ $appBaseUrl }}/{{ $cssFile }}">
        <link rel="stylesheet" href="{{ $appBaseUrl }}/{{ $cssFile }}">
    @endif

    @if($jsFile)
        <link rel="modulepreload" href="{{ $appBaseUrl }}/{{ $jsFile }}">
    @endif

    @foreach($imports as $importKey)
        @if(isset($manifest[$importKey]['file']))
            <link rel="modulepreload" href="{{ $appBaseUrl }}/build/{{ $manifest[$importKey]['file'] }}">
        @endif
    @endforeach

    <script>
        window.deferredPWAInstallPrompt = null;
        window.addEventListener('beforeinstallprompt', function(e) {
            e.preventDefault();
            window.deferredPWAInstallPrompt = e;
            window.dispatchEvent(new CustomEvent('pwa-prompt-ready', { detail: e }));
        });
        window.addEventListener('appinstalled', function() {
            window.deferredPWAInstallPrompt = null;
            window.dispatchEvent(new CustomEvent('pwa-installed'));
        });
    </script>

    @routes
    @inertiaHead
</head>
<body class="h-full bg-[#F5FAF7] font-sans antialiased text-slate-900 selection:bg-emerald-600 selection:text-white">
    <div id="app" data-page="{{ json_encode($page) }}">
        <div class="min-h-screen bg-[#F5FAF7] p-4 sm:p-8 flex flex-col justify-between" style="font-family: 'Plus Jakarta Sans', sans-serif;">
            <div class="max-w-6xl mx-auto w-full space-y-6">
                <!-- Header skeleton -->
                <div class="flex items-center justify-between py-3 border-b border-slate-200">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-500 flex items-center justify-center font-black text-white text-sm shadow-md shadow-emerald-600/20">SG</div>
                        <div class="space-y-1.5">
                            <div class="h-4 w-32 bg-slate-200 rounded-md animate-pulse"></div>
                            <div class="h-3 w-20 bg-slate-200/60 rounded-md animate-pulse"></div>
                        </div>
                    </div>
                    <div class="w-9 h-9 rounded-2xl bg-slate-200 animate-pulse"></div>
                </div>
                <!-- 3 Metric Cards Skeleton -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="h-28 rounded-3xl bg-white border border-slate-200/80 p-5 space-y-3 shadow-xs">
                        <div class="h-4 w-24 bg-slate-200 rounded animate-pulse"></div>
                        <div class="h-8 w-16 bg-slate-200/70 rounded-lg animate-pulse"></div>
                    </div>
                    <div class="h-28 rounded-3xl bg-white border border-slate-200/80 p-5 space-y-3 shadow-xs">
                        <div class="h-4 w-24 bg-slate-200 rounded animate-pulse"></div>
                        <div class="h-8 w-16 bg-slate-200/70 rounded-lg animate-pulse"></div>
                    </div>
                    <div class="h-28 rounded-3xl bg-white border border-slate-200/80 p-5 space-y-3 shadow-xs">
                        <div class="h-4 w-24 bg-slate-200 rounded animate-pulse"></div>
                        <div class="h-8 w-16 bg-slate-200/70 rounded-lg animate-pulse"></div>
                    </div>
                </div>
                <!-- Content Table Skeleton -->
                <div class="h-72 rounded-3xl bg-white border border-slate-200/80 p-6 space-y-4 shadow-xs">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="h-5 w-40 bg-slate-200 rounded animate-pulse"></div>
                        <div class="h-7 w-24 bg-slate-200/60 rounded-xl animate-pulse"></div>
                    </div>
                    <div class="space-y-3 pt-2">
                        <div class="h-12 rounded-2xl bg-slate-100/80 animate-pulse"></div>
                        <div class="h-12 rounded-2xl bg-slate-100/80 animate-pulse"></div>
                        <div class="h-12 rounded-2xl bg-slate-100/80 animate-pulse"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($jsFile)
        <script type="module" src="{{ $appBaseUrl }}/{{ $jsFile }}"></script>
    @endif

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                var swUrl = '{{ $appBaseUrl }}/sw.js?v={{ $pwaVersion }}';
                var swScope = '{{ $appBaseUrl }}/';
                navigator.serviceWorker.register(swUrl, { scope: swScope }).then(function(registration) {
                    console.log('PWA ServiceWorker registered with scope: ', registration.scope);
                }, function(err) {
                    console.log('PWA ServiceWorker registration failed: ', err);
                });
            });
        }
    </script>
</body>
</html>
