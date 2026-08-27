<?php

namespace App\Http\Controllers;

use App\Services\CloudflareR2;
use Illuminate\Http\Response;

class AssetController extends Controller
{
    /**
     * Serve Service Worker javascript
     */
    public function serviceWorker()
    {
        $path = public_path('sw.js');
        if (!file_exists($path)) {
            $path = base_path('public/sw.js');
        }
        if (file_exists($path)) {
            return response(file_get_contents($path), 200, [
                'Content-Type' => 'application/javascript; charset=utf-8',
                'Service-Worker-Allowed' => '/',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
            ]);
        }
        return response('// sw.js not found', 404);
    }

    /**
     * Serve Vite build assets with correct mime types & CORS
     */
    public function buildAsset(string $path)
    {
        if (str_contains($path, '..') || str_contains($path, "\0")) {
            abort(404);
        }
        $cleanPath = ltrim(str_replace('\\', '/', $path), '/');
        
        $candidates = [
            public_path('build/' . $cleanPath),
            base_path('public/build/' . $cleanPath),
            base_path('build/' . $cleanPath),
            dirname(dirname(__DIR__)) . '/public/build/' . $cleanPath,
        ];

        $targetFile = null;
        foreach ($candidates as $c) {
            if (file_exists($c) && is_file($c)) {
                $targetFile = $c;
                break;
            }
        }

        if ($targetFile) {
            $ext = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
            $mimes = [
                'js' => 'application/javascript; charset=utf-8',
                'mjs' => 'application/javascript; charset=utf-8',
                'css' => 'text/css; charset=utf-8',
                'json' => 'application/json; charset=utf-8',
                'map' => 'application/json; charset=utf-8',
                'svg' => 'image/svg+xml',
                'webp' => 'image/webp',
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'woff2' => 'font/woff2',
                'woff' => 'font/woff',
                'ttf' => 'font/ttf',
            ];
            $mime = $mimes[$ext] ?? 'application/octet-stream';
            return response(file_get_contents($targetFile), 200, [
                'Content-Type' => $mime,
                'Access-Control-Allow-Origin' => '*',
                'Cache-Control' => 'public, max-age=31536000, immutable',
            ]);
        }

        return response('/* Asset not found: ' . $cleanPath . ' */', 404);
    }

    /**
     * Serve local storage or proxy Cloudflare R2 files
     */
    public function storageProxy(string $path)
    {
        if (str_contains($path, '..') || str_contains($path, "\0")) {
            abort(404);
        }
        $cleanPath = ltrim(str_replace('\\', '/', $path), '/');

        // 1. Check local files
        $localCandidates = [
            storage_path('app/public/' . $cleanPath),
            public_path('storage/' . $cleanPath),
            base_path('public/storage/' . $cleanPath),
        ];
        $allowedRoots = array_filter([
            realpath(storage_path('app/public')),
            realpath(public_path('storage')),
            realpath(base_path('public/storage')),
        ]);

        foreach ($localCandidates as $loc) {
            $real = realpath($loc);
            if ($real && is_file($real)) {
                $isSafe = false;
                foreach ($allowedRoots as $root) {
                    if ($root && str_starts_with($real, $root)) {
                        $isSafe = true;
                        break;
                    }
                }
                if ($isSafe) {
                    $ext = strtolower(pathinfo($real, PATHINFO_EXTENSION));
                    $mimes = [
                        'png' => 'image/png',
                        'webp' => 'image/webp',
                        'jpg' => 'image/jpeg',
                        'jpeg' => 'image/jpeg',
                        'svg' => 'image/svg+xml',
                        'pdf' => 'application/pdf',
                    ];
                    return response(file_get_contents($real), 200, [
                        'Content-Type' => $mimes[$ext] ?? 'application/octet-stream',
                        'Access-Control-Allow-Origin' => '*',
                        'Cache-Control' => 'public, max-age=86400',
                    ]);
                }
            }
        }

        // 2. Check in Cloudflare R2
        if (CloudflareR2::isConfigured() && CloudflareR2::exists($cleanPath)) {
            $content = CloudflareR2::get($cleanPath);
            if ($content !== null) {
                $ext = strtolower(pathinfo($cleanPath, PATHINFO_EXTENSION));
                $mimes = [
                    'png' => 'image/png',
                    'webp' => 'image/webp',
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'svg' => 'image/svg+xml',
                    'pdf' => 'application/pdf',
                ];
                return response($content, 200, [
                    'Content-Type' => $mimes[$ext] ?? 'application/octet-stream',
                    'Access-Control-Allow-Origin' => '*',
                    'Cache-Control' => 'public, max-age=86400',
                ]);
            }
        }

        // 3. Fallback for logos
        if (str_contains($cleanPath, 'logo')) {
            $masterLogo = public_path('icons/company_logo_master.png');
            if (!file_exists($masterLogo)) {
                $masterLogo = base_path('public/icons/company_logo_master.png');
            }
            if (file_exists($masterLogo)) {
                return response(file_get_contents($masterLogo), 200, [
                    'Content-Type' => 'image/png',
                    'Access-Control-Allow-Origin' => '*',
                ]);
            }
        }

        // 4. Fallback for avatar requests (prevent broken images & 404s if file was lost during redeploy)
        if (str_contains($cleanPath, 'avatar')) {
            $svgAvatar = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128 128" width="128" height="128"><circle cx="64" cy="64" r="64" fill="#059669"/><circle cx="64" cy="48" r="22" fill="#ffffff"/><path d="M24 108c0-22.091 17.909-40 40-40s40 17.909 40 40z" fill="#ffffff"/></svg>';
            return response($svgAvatar, 200, [
                'Content-Type' => 'image/svg+xml',
                'Access-Control-Allow-Origin' => '*',
                'Cache-Control' => 'public, max-age=3600',
            ]);
        }

        return response('File not found', 404);
    }

    /**
     * Serve static app icon
     */
    public function iconAsset(string $filename)
    {
        if (str_contains($filename, '..') || str_contains($filename, "\0")) {
            abort(404);
        }
        $cleanFilename = basename($filename);
        $path = public_path('icons/' . $cleanFilename);
        $real = realpath($path);
        $iconsDir = realpath(public_path('icons'));
        if ($real && is_file($real) && $iconsDir && str_starts_with($real, $iconsDir)) {
            return response(file_get_contents($real), 200, [
                'Content-Type' => 'image/png',
                'Access-Control-Allow-Origin' => '*',
                'Cache-Control' => 'public, max-age=31536000, immutable',
            ]);
        }
        return response('Icon not found', 404);
    }
}
