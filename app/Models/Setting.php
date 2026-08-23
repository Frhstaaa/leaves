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
        Cache::forget('app_settings_all');
    }

    public static function getAll(): array
    {
        return Cache::remember('app_settings_all', 3600, function () {
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

            try {
                $dbSettings = self::pluck('value', 'key')->toArray();
                $merged = array_merge($defaults, $dbSettings);
                
                $logo = $merged['app_logo'] ?? null;
                if ($logo) {
                    $clean = preg_replace('/^\/?storage\//', '', $logo);
                    $root = app()->runningInConsole() ? url('/') : rtrim(request()->root(), '/');
                    $merged['app_logo_url'] = str_starts_with($logo, 'http') ? $logo : $root . '/storage/' . $clean;
                } else {
                    $merged['app_logo_url'] = null;
                }

                return $merged;
            } catch (\Throwable $e) {
                return $defaults;
            }
        });
    }
}
