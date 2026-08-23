<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
    ];

    public static function get(string $key, $default = null)
    {
        $settings = self::getAll();
        return $settings[$key] ?? $default;
    }

    public static function set(string $key, $value): void
    {
        self::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('app_settings_raw');
        Cache::forget('app_settings_all');
    }

    public static function getAll(): array
    {
        $defaults = [
            'app_name' => 'Form SGIN',
            'app_subname' => 'Cuti & Ketidakhadiran',
            'company_name' => 'PT. SGIN Indonesia',
            'company_address' => 'Jl. Industri Raya No. 123, Kawasan Industri',
            'company_phone' => '+62 21 8901234',
            'company_email' => 'hrd@sgin.co.id',
            'app_logo' => null,
            'app_favicon' => null,
            'theme_color' => '#059669',
            'app_description' => 'Sistem Informasi Manajemen Cuti, Ketidakhadiran, Izin, Sakit, Lembur, dan Distribusi Slip Gaji Karyawan Real-time.',
        ];

        $raw = Cache::remember('app_settings_raw', 3600, function () use ($defaults) {
            try {
                $dbSettings = self::pluck('value', 'key')->toArray();
                return array_merge($defaults, $dbSettings);
            } catch (\Throwable $e) {
                return $defaults;
            }
        });

        // Compute dynamic absolute URL with subfolder awareness on each request
        $logo = $raw['app_logo'] ?? null;
        if ($logo) {
            $clean = preg_replace('/^\/?storage\//', '', $logo);
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : (request()->isSecure() ? 'https' : 'http');
            $host = $_SERVER['HTTP_HOST'] ?? request()->getHost();
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            $subfolder = str_contains($uri, 'leaves-application') ? '/leaves-application' : '';
            $root = $scheme . '://' . $host . $subfolder;
            $raw['app_logo_url'] = str_starts_with($logo, 'http') ? $logo : $root . '/storage/' . $clean;
        } else {
            $raw['app_logo_url'] = null;
        }

        return $raw;
    }
}
