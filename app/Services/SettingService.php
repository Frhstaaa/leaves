<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;

class SettingService
{
    /**
     * Get all settings as key-value array.
     */
    public function getAllSettings(): array
    {
        return Setting::getAll();
    }

    /**
     * Update settings.
     */
    public function updateSettings(array $data, $logoFile = null): array
    {
        if ($logoFile) {
            $path = $logoFile->store('logos', 'public');
            $data['app_logo'] = $path;
        }

        foreach ($data as $key => $value) {
            if ($value !== null) {
                Setting::set($key, $value);
            }
        }

        return Setting::getAll();
    }

    /**
     * Generate dynamic PWA webmanifest.
     */
    public function generateManifest(): array
    {
        $settings = Setting::getAll();
        $appName = $settings['app_name'] ?? 'Form SGIN';
        $shortName = $settings['app_name'] ?? 'Form SGIN';
        $themeColor = $settings['theme_color'] ?? '#0FA172';
        $description = $settings['app_description'] ?? 'Sistem Informasi Pengajuan Cuti & Slip Gaji Karyawan PT. Sugiyama Indonesia';
        $version = substr(md5(($settings['app_logo'] ?? '') . ($settings['app_name'] ?? '')), 0, 8);
        $root = app()->runningInConsole() ? url('/') : rtrim(request()->root(), '/');

        return [
            'id' => $root . '/?source=pwa',
            'name' => $appName . ' - PT. Sugiyama Indonesia',
            'short_name' => $shortName,
            'description' => $description,
            'start_url' => $root . '/login?source=pwa',
            'scope' => $root . '/',
            'display' => 'standalone',
            'display_override' => ['window-controls-overlay', 'standalone', 'minimal-ui'],
            'background_color' => '#F5FAF7',
            'theme_color' => $themeColor,
            'orientation' => 'portrait',
            'categories' => ['business', 'productivity', 'utilities'],
            'prefer_related_applications' => false,
            'icons' => [
                [
                    'src' => $root . "/app-icon/180?v={$version}",
                    'sizes' => '180x180',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => $root . "/app-icon/192?v={$version}",
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => $root . "/app-icon/192?v={$version}&maskable=1",
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'maskable',
                ],
                [
                    'src' => $root . "/app-icon/512?v={$version}",
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => $root . "/app-icon/512?v={$version}&maskable=1",
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'maskable',
                ],
            ],
            'shortcuts' => [
                [
                    'name' => 'Buat Pengajuan',
                    'short_name' => 'Pengajuan',
                    'description' => 'Buat pengajuan cuti atau izin baru',
                    'url' => $root . '/leave-requests/create',
                    'icons' => [['src' => $root . "/app-icon/192?v={$version}", 'sizes' => '192x192']],
                ],
                [
                    'name' => 'Persetujuan Team',
                    'short_name' => 'Approval',
                    'description' => 'Tinjau persetujuan cuti bawahan',
                    'url' => $root . '/approvals',
                    'icons' => [['src' => $root . "/app-icon/192?v={$version}", 'sizes' => '192x192']],
                ],
            ],
        ];
    }
}
