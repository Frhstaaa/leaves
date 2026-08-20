<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50 text-slate-900">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Sistem Informasi Form SGIN - Pengajuan Cuti, Izin, Sakit, Lembur, dan Distribusi Slip Gaji Karyawan Real-time." />
    <meta name="theme-color" content="#059669" />
    <title>Absence & Leave Management System - Form SGIN</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" media="print" onload="this.media='all'">
    <noscript>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap">
    </noscript>

    @php
        $manifestPath = public_path('build/manifest.json');
        $manifest = file_exists($manifestPath) ? json_decode(file_get_contents($manifestPath), true) : [];
        $cssFile = isset($manifest['resources/js/app.jsx']['css'][0]) ? '/build/' . $manifest['resources/js/app.jsx']['css'][0] : (isset($manifest['resources/css/app.css']['file']) ? '/build/' . $manifest['resources/css/app.css']['file'] : '');
        $jsFile = isset($manifest['resources/js/app.jsx']['file']) ? '/build/' . $manifest['resources/js/app.jsx']['file'] : '';
        $imports = isset($manifest['resources/js/app.jsx']['imports']) ? $manifest['resources/js/app.jsx']['imports'] : [];
    @endphp

    @if($cssFile)
        <link rel="preload" as="style" href="{{ asset($cssFile) }}">
        <link rel="stylesheet" href="{{ asset($cssFile) }}">
    @endif

    @if($jsFile)
        <link rel="modulepreload" href="{{ asset($jsFile) }}">
    @endif

    @foreach($imports as $importKey)
        @if(isset($manifest[$importKey]['file']))
            <link rel="modulepreload" href="{{ asset('/build/' . $manifest[$importKey]['file']) }}">
        @endif
    @endforeach

    @inertiaHead
</head>
<body class="h-full bg-[#F5FAF7] font-sans antialiased text-slate-900 selection:bg-emerald-600 selection:text-white">
    @inertia

    @if($jsFile)
        <script type="module" src="{{ asset($jsFile) }}"></script>
    @endif
</body>
</html>
