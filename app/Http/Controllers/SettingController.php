<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\MediaOptimizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SettingController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            return redirect()->route('dashboard')->with('error', 'Halaman ini khusus untuk HRD / PGA Admin.');
        }

        $settings = Setting::getAll();

        return Inertia::render('HRD/Settings', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        if (!$user->isAdmin()) {
            return redirect()->route('dashboard')->with('error', 'Halaman ini khusus untuk HRD / PGA Admin.');
        }

        $validated = $request->validate([
            'app_name' => 'required|string|max:100',
            'app_subname' => 'nullable|string|max:150',
            'company_name' => 'required|string|max:150',
            'company_address' => 'nullable|string|max:500',
            'company_phone' => 'nullable|string|max:50',
            'company_email' => 'nullable|email|max:100',
            'theme_color' => 'nullable|string|max:20',
            'app_description' => 'nullable|string|max:500',
            'app_logo' => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:5120',
        ]);

        foreach (['app_name', 'app_subname', 'company_name', 'company_address', 'company_phone', 'company_email', 'theme_color', 'app_description'] as $field) {
            if ($request->has($field)) {
                Setting::set($field, $request->input($field));
            }
        }

        if ($request->hasFile('app_logo')) {
            $file = $request->file('app_logo');
            $ext = strtolower($file->getClientOriginalExtension());

            if ($ext === 'svg') {
                $path = $file->store('logos', 'public');
                Setting::set('app_logo', $path);
            } else {
                // Convert to optimized WebP for web UI
                $webpPath = MediaOptimizer::convertImageToWebp($file, 'logos', 90, 800, 800);
                Setting::set('app_logo', $webpPath);

                // Also store standard PNG version for PWA icon generator
                $pngFilename = 'logos/pwa_icon_master_' . time() . '.png';
                $realPath = $file->getRealPath();
                $src = match (true) {
                    str_contains($file->getMimeType(), 'jpeg') || str_contains($file->getMimeType(), 'jpg') => @imagecreatefromjpeg($realPath),
                    str_contains($file->getMimeType(), 'png') => @imagecreatefrompng($realPath),
                    str_contains($file->getMimeType(), 'webp') => @imagecreatefromwebp($realPath),
                    default => null,
                };

                if ($src) {
                    $w = imagesx($src);
                    $h = imagesy($src);
                    $size = max($w, $h);
                    $square = imagecreatetruecolor($size, $size);
                    imagealphablending($square, false);
                    imagesavealpha($square, true);
                    $transparent = imagecolorallocatealpha($square, 255, 255, 255, 127);
                    imagefilledrectangle($square, 0, 0, $size, $size, $transparent);

                    $dstX = (int) round(($size - $w) / 2);
                    $dstY = (int) round(($size - $h) / 2);
                    imagecopy($square, $src, $dstX, $dstY, 0, 0, $w, $h);

                    ob_start();
                    imagepng($square);
                    $pngData = ob_get_clean();
                    imagedestroy($src);
                    imagedestroy($square);

                    Storage::disk('public')->put($pngFilename, $pngData);
                    Setting::set('app_pwa_icon', $pngFilename);
                }
            }
        }

        return redirect()->back()->with('success', 'Pengaturan aplikasi & logo berhasil disimpan!');
    }

    public function manifest()
    {
        $settings = Setting::getAll();
        $appName = $settings['app_name'] ?? 'Form SGIN';
        $shortName = $settings['app_name'] ?? 'Form SGIN';
        $themeColor = $settings['theme_color'] ?? '#059669';
        $description = $settings['app_description'] ?? 'Sistem Informasi Pengajuan Cuti & Slip Gaji Karyawan';

        $manifest = [
            'name' => $appName . ' - Absence & Leave Management',
            'short_name' => $shortName,
            'description' => $description,
            'start_url' => '/',
            'scope' => '/',
            'display' => 'standalone',
            'background_color' => '#F5FAF7',
            'theme_color' => $themeColor,
            'orientation' => 'portrait',
            'icons' => [
                [
                    'src' => '/app-icon/192',
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any maskable',
                ],
                [
                    'src' => '/app-icon/512',
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any maskable',
                ],
            ],
            'shortcuts' => [
                [
                    'name' => 'Buat Pengajuan',
                    'short_name' => 'Pengajuan',
                    'description' => 'Buat pengajuan cuti atau izin baru',
                    'url' => '/leave-requests/create',
                    'icons' => [['src' => '/app-icon/192', 'sizes' => '192x192']],
                ],
                [
                    'name' => 'Persetujuan Team',
                    'short_name' => 'Approval',
                    'description' => 'Tinjau persetujuan cuti bawahan',
                    'url' => '/approvals',
                    'icons' => [['src' => '/app-icon/192', 'sizes' => '192x192']],
                ],
            ],
        ];

        return response()->json($manifest)
            ->header('Content-Type', 'application/manifest+json; charset=utf-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    public function getAppIcon($size = 192)
    {
        $size = in_array((int)$size, [192, 512]) ? (int)$size : 192;
        $settings = Setting::getAll();
        $customIcon = $settings['app_pwa_icon'] ?? $settings['app_logo'] ?? null;

        if ($customIcon && Storage::disk('public')->exists($customIcon)) {
            $fullPath = storage_path('app/public/' . $customIcon);
            $mime = function_exists('mime_content_type') ? @mime_content_type($fullPath) : '';

            $src = match (true) {
                str_contains($mime, 'jpeg') || str_contains($mime, 'jpg') => @imagecreatefromjpeg($fullPath),
                str_contains($mime, 'png') => @imagecreatefrompng($fullPath),
                str_contains($mime, 'webp') => @imagecreatefromwebp($fullPath),
                default => null,
            };

            if ($src) {
                $canvas = imagecreatetruecolor($size, $size);
                imagealphablending($canvas, false);
                imagesavealpha($canvas, true);
                $transparent = imagecolorallocatealpha($canvas, 255, 255, 255, 127);
                imagefilledrectangle($canvas, 0, 0, $size, $size, $transparent);

                $w = imagesx($src);
                $h = imagesy($src);

                // Fit within canvas with margin
                $padding = (int) round($size * 0.08);
                $targetArea = $size - ($padding * 2);
                $ratio = min($targetArea / $w, $targetArea / $h);
                $newW = (int) round($w * $ratio);
                $newH = (int) round($h * $ratio);
                $dstX = (int) round(($size - $newW) / 2);
                $dstY = (int) round(($size - $newH) / 2);

                imagecopyresampled($canvas, $src, $dstX, $dstY, 0, 0, $newW, $newH, $w, $h);

                ob_start();
                imagepng($canvas);
                $pngData = ob_get_clean();
                imagedestroy($src);
                imagedestroy($canvas);

                return response($pngData, 200, [
                    'Content-Type' => 'image/png',
                    'Cache-Control' => 'public, max-age=86400',
                ]);
            }
        }

        // Generate default branded gradient icon
        $canvas = imagecreatetruecolor($size, $size);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);

        // Emerald background
        $green = imagecolorallocate($canvas, 5, 150, 105);
        imagefilledrectangle($canvas, 0, 0, $size, $size, $green);

        // White text "SG"
        $white = imagecolorallocate($canvas, 255, 255, 255);
        $font = 5; // Built-in GD font
        $text = "SG";
        $textWidth = imagefontwidth($font) * strlen($text);
        $textHeight = imagefontheight($font);
        $x = (int) round(($size - $textWidth) / 2);
        $y = (int) round(($size - $textHeight) / 2);
        imagestring($canvas, $font, $x, $y, $text, $white);

        ob_start();
        imagepng($canvas);
        $pngData = ob_get_clean();
        imagedestroy($canvas);

        return response($pngData, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
