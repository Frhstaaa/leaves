<?php

namespace App\Services;

class CloudflareR2
{
    /**
     * Get R2 credentials from environment or config.
     */
    public static function getCredentials(): array
    {
        $r2Config = function_exists('config') ? config('filesystems.disks.r2', []) : [];

        $getEnv = function ($key, $default = null) use ($r2Config) {
            // 1. First check Laravel config
            $configKeyMap = [
                'CLOUDFLARE_R2_ACCESS_KEY_ID' => 'key',
                'CLOUDFLARE_R2_SECRET_ACCESS_KEY' => 'secret',
                'CLOUDFLARE_R2_ENDPOINT' => 'endpoint',
                'CLOUDFLARE_R2_BUCKET' => 'bucket',
                'CLOUDFLARE_R2_URL' => 'url',
            ];
            if (isset($configKeyMap[$key]) && !empty($r2Config[$configKeyMap[$key]])) {
                return $r2Config[$configKeyMap[$key]];
            }

            // 2. Then check env helper
            if (function_exists('env')) {
                $val = env($key);
                if ($val !== null && $val !== '') {
                    return $val;
                }
            }

            // 3. Fallback to getenv
            $val = getenv($key);
            return ($val !== false && $val !== '') ? $val : $default;
        };

        return [
            'key' => $getEnv('CLOUDFLARE_R2_ACCESS_KEY_ID', 'fbe7d6c6ec7f262c09fbaa7e45b2d4da'),
            'secret' => $getEnv('CLOUDFLARE_R2_SECRET_ACCESS_KEY', '4f4941af6f1a58b7b00a33de9b20c5f3974a3a15c48636f99f2dd846cca20b69'),
            'endpoint' => $getEnv('CLOUDFLARE_R2_ENDPOINT', 'https://a6cec2d2f2d06ff617a7e61a35c11429.r2.cloudflarestorage.com'),
            'bucket' => $getEnv('CLOUDFLARE_R2_BUCKET', 'sgin'),
            'public_url' => $getEnv('CLOUDFLARE_R2_URL', 'https://a6cec2d2f2d06ff617a7e61a35c11429.r2.cloudflarestorage.com/sgin'),
            'region' => 'auto',
        ];
    }

    /**
     * Check if Cloudflare R2 is configured and ready.
     */
    public static function isConfigured(): bool
    {
        $cred = self::getCredentials();
        return !empty($cred['key']) && !empty($cred['secret']) && !empty($cred['endpoint']) && !empty($cred['bucket']);
    }

    /**
     * Test connection to Cloudflare R2 and return detailed diagnostics.
     */
    public static function testConnection(): array
    {
        if (!self::isConfigured()) {
            return [
                'success' => false,
                'message' => 'Cloudflare R2 belum terkonfigurasi dengan lengkap.',
                'details' => self::getCredentials(),
            ];
        }

        $testKey = '_health_check_' . time() . '.txt';
        $testPayload = 'Cloudflare R2 Health Check OK at ' . date('c');

        $putOk = self::put($testKey, $testPayload, 'text/plain');
        if (!$putOk) {
            return [
                'success' => false,
                'message' => 'Gagal mengunggah test object ke Cloudflare R2. Periksa permission API token R2.',
                'details' => ['endpoint' => self::getCredentials()['endpoint'], 'bucket' => self::getCredentials()['bucket']],
            ];
        }

        $exists = self::exists($testKey);
        $downloaded = self::get($testKey);
        self::delete($testKey);

        if (!$exists || $downloaded !== $testPayload) {
            return [
                'success' => false,
                'message' => 'Upload berhasil namun verifikasi retrieval data Cloudflare R2 gagal.',
            ];
        }

        return [
            'success' => true,
            'message' => 'Koneksi Cloudflare R2 aktif, terverifikasi, dan berfungsi 100%!',
            'bucket' => self::getCredentials()['bucket'],
            'endpoint' => self::getCredentials()['endpoint'],
        ];
    }

    /**
     * Upload an object to Cloudflare R2.
     */
    public static function put(string $path, string $content, string $mimeType = 'application/octet-stream'): bool
    {
        if (!self::isConfigured()) {
            return false;
        }

        $cred = self::getCredentials();
        $key = ltrim($path, '/');
        $bucket = $cred['bucket'];
        
        $host = parse_url($cred['endpoint'], PHP_URL_HOST);
        $url = rtrim($cred['endpoint'], '/') . '/' . $bucket . '/' . $key;
        
        $amzDate = gmdate('Ymd\THis\Z');
        $dateStamp = gmdate('Ymd');
        $payloadHash = hash('sha256', $content);
        
        $canonicalUri = '/' . $bucket . '/' . $key;
        $canonicalHeaders = "host:{$host}\nx-amz-content-sha256:{$payloadHash}\nx-amz-date:{$amzDate}\n";
        $signedHeaders = "host;x-amz-content-sha256;x-amz-date";
        
        $canonicalRequest = "PUT\n{$canonicalUri}\n\n{$canonicalHeaders}\n{$signedHeaders}\n{$payloadHash}";
        
        $algorithm = 'AWS4-HMAC-SHA256';
        $credentialScope = "{$dateStamp}/auto/s3/aws4_request";
        $stringToSign = "{$algorithm}\n{$amzDate}\n{$credentialScope}\n" . hash('sha256', $canonicalRequest);
        
        $kSecret = 'AWS4' . $cred['secret'];
        $kDate = hash_hmac('sha256', $dateStamp, $kSecret, true);
        $kRegion = hash_hmac('sha256', 'auto', $kDate, true);
        $kService = hash_hmac('sha256', 's3', $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);
        
        $authHeader = "{$algorithm} Credential={$cred['key']}/{$credentialScope}, SignedHeaders={$signedHeaders}, Signature={$signature}";
        
        $headers = [
            "Host: {$host}",
            "x-amz-date: {$amzDate}",
            "x-amz-content-sha256: {$payloadHash}",
            "Authorization: {$authHeader}",
            "Content-Type: {$mimeType}",
            "Content-Length: " . strlen($content),
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $code === 200;
    }

    /**
     * Download or retrieve an object from Cloudflare R2.
     */
    public static function get(string $path): ?string
    {
        if (!self::isConfigured()) {
            return null;
        }

        $cred = self::getCredentials();
        $key = ltrim($path, '/');
        $bucket = $cred['bucket'];
        
        $host = parse_url($cred['endpoint'], PHP_URL_HOST);
        $url = rtrim($cred['endpoint'], '/') . '/' . $bucket . '/' . $key;
        
        $amzDate = gmdate('Ymd\THis\Z');
        $dateStamp = gmdate('Ymd');
        $payloadHash = hash('sha256', '');
        
        $canonicalUri = '/' . $bucket . '/' . $key;
        $canonicalHeaders = "host:{$host}\nx-amz-content-sha256:{$payloadHash}\nx-amz-date:{$amzDate}\n";
        $signedHeaders = "host;x-amz-content-sha256;x-amz-date";
        
        $canonicalRequest = "GET\n{$canonicalUri}\n\n{$canonicalHeaders}\n{$signedHeaders}\n{$payloadHash}";
        
        $algorithm = 'AWS4-HMAC-SHA256';
        $credentialScope = "{$dateStamp}/auto/s3/aws4_request";
        $stringToSign = "{$algorithm}\n{$amzDate}\n{$credentialScope}\n" . hash('sha256', $canonicalRequest);
        
        $kSecret = 'AWS4' . $cred['secret'];
        $kDate = hash_hmac('sha256', $dateStamp, $kSecret, true);
        $kRegion = hash_hmac('sha256', 'auto', $kDate, true);
        $kService = hash_hmac('sha256', 's3', $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);
        
        $authHeader = "{$algorithm} Credential={$cred['key']}/{$credentialScope}, SignedHeaders={$signedHeaders}, Signature={$signature}";
        
        $headers = [
            "Host: {$host}",
            "x-amz-date: {$amzDate}",
            "x-amz-content-sha256: {$payloadHash}",
            "Authorization: {$authHeader}",
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return ($code === 200) ? $res : null;
    }

    /**
     * Check if an object exists on Cloudflare R2.
     */
    public static function exists(string $path): bool
    {
        if (!self::isConfigured()) {
            return false;
        }

        $cred = self::getCredentials();
        $key = ltrim($path, '/');
        $bucket = $cred['bucket'];
        
        $host = parse_url($cred['endpoint'], PHP_URL_HOST);
        $url = rtrim($cred['endpoint'], '/') . '/' . $bucket . '/' . $key;
        
        $amzDate = gmdate('Ymd\THis\Z');
        $dateStamp = gmdate('Ymd');
        $payloadHash = hash('sha256', '');
        
        $canonicalUri = '/' . $bucket . '/' . $key;
        $canonicalHeaders = "host:{$host}\nx-amz-content-sha256:{$payloadHash}\nx-amz-date:{$amzDate}\n";
        $signedHeaders = "host;x-amz-content-sha256;x-amz-date";
        
        $canonicalRequest = "HEAD\n{$canonicalUri}\n\n{$canonicalHeaders}\n{$signedHeaders}\n{$payloadHash}";
        
        $algorithm = 'AWS4-HMAC-SHA256';
        $credentialScope = "{$dateStamp}/auto/s3/aws4_request";
        $stringToSign = "{$algorithm}\n{$amzDate}\n{$credentialScope}\n" . hash('sha256', $canonicalRequest);
        
        $kSecret = 'AWS4' . $cred['secret'];
        $kDate = hash_hmac('sha256', $dateStamp, $kSecret, true);
        $kRegion = hash_hmac('sha256', 'auto', $kDate, true);
        $kService = hash_hmac('sha256', 's3', $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);
        
        $authHeader = "{$algorithm} Credential={$cred['key']}/{$credentialScope}, SignedHeaders={$signedHeaders}, Signature={$signature}";
        
        $headers = [
            "Host: {$host}",
            "x-amz-date: {$amzDate}",
            "x-amz-content-sha256: {$payloadHash}",
            "Authorization: {$authHeader}",
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $code === 200;
    }

    /**
     * Delete an object from Cloudflare R2.
     */
    public static function delete(string $path): bool
    {
        if (!self::isConfigured()) {
            return false;
        }

        $cred = self::getCredentials();
        $key = ltrim($path, '/');
        $bucket = $cred['bucket'];
        
        $host = parse_url($cred['endpoint'], PHP_URL_HOST);
        $url = rtrim($cred['endpoint'], '/') . '/' . $bucket . '/' . $key;
        
        $amzDate = gmdate('Ymd\THis\Z');
        $dateStamp = gmdate('Ymd');
        $payloadHash = hash('sha256', '');
        
        $canonicalUri = '/' . $bucket . '/' . $key;
        $canonicalHeaders = "host:{$host}\nx-amz-content-sha256:{$payloadHash}\nx-amz-date:{$amzDate}\n";
        $signedHeaders = "host;x-amz-content-sha256;x-amz-date";
        
        $canonicalRequest = "DELETE\n{$canonicalUri}\n\n{$canonicalHeaders}\n{$signedHeaders}\n{$payloadHash}";
        
        $algorithm = 'AWS4-HMAC-SHA256';
        $credentialScope = "{$dateStamp}/auto/s3/aws4_request";
        $stringToSign = "{$algorithm}\n{$amzDate}\n{$credentialScope}\n" . hash('sha256', $canonicalRequest);
        
        $kSecret = 'AWS4' . $cred['secret'];
        $kDate = hash_hmac('sha256', $dateStamp, $kSecret, true);
        $kRegion = hash_hmac('sha256', 'auto', $kDate, true);
        $kService = hash_hmac('sha256', 's3', $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);
        
        $authHeader = "{$algorithm} Credential={$cred['key']}/{$credentialScope}, SignedHeaders={$signedHeaders}, Signature={$signature}";
        
        $headers = [
            "Host: {$host}",
            "x-amz-date: {$amzDate}",
            "x-amz-content-sha256: {$payloadHash}",
            "Authorization: {$authHeader}",
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $code === 204 || $code === 200;
    }
}
