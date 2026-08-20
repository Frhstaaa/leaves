<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaOptimizer
{
    /**
     * Convert any uploaded image to optimized WebP format with optional resizing and EXIF orientation correction.
     *
     * @param UploadedFile|string $file Uploaded file or absolute file path
     * @param string $folder Target storage folder (e.g. 'avatars', 'attachments')
     * @param int $quality WebP quality (0-100, default: 80)
     * @param int $maxWidth Maximum width in pixels (default: 1920)
     * @param int $maxHeight Maximum height in pixels (default: 1920)
     * @return string Relative storage path (e.g. 'attachments/attach_abc123.webp')
     */
    public static function convertImageToWebp($file, string $folder = 'attachments', int $quality = 80, int $maxWidth = 1920, int $maxHeight = 1920): string
    {
        $realPath = $file instanceof UploadedFile ? $file->getRealPath() : $file;
        $mime = $file instanceof UploadedFile ? $file->getMimeType() : (function_exists('mime_content_type') ? @mime_content_type($realPath) : '');

        if (!file_exists($realPath)) {
            if ($file instanceof UploadedFile) {
                return $file->store($folder, 'public');
            }
            return '';
        }

        // Try creating image resource from various formats
        $srcImage = match (true) {
            str_contains($mime, 'jpeg') || str_contains($mime, 'jpg') => @imagecreatefromjpeg($realPath),
            str_contains($mime, 'png') => @imagecreatefrompng($realPath),
            str_contains($mime, 'webp') => @imagecreatefromwebp($realPath),
            str_contains($mime, 'gif') => @imagecreatefromgif($realPath),
            str_contains($mime, 'bmp') || str_contains($mime, 'x-ms-bmp') => function_exists('imagecreatefrombmp') ? @imagecreatefrombmp($realPath) : null,
            default => null,
        };

        // Fallback if mime check failed but extension is image
        if (!$srcImage) {
            $ext = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
            $srcImage = match ($ext) {
                'jpg', 'jpeg' => @imagecreatefromjpeg($realPath),
                'png' => @imagecreatefrompng($realPath),
                'webp' => @imagecreatefromwebp($realPath),
                'gif' => @imagecreatefromgif($realPath),
                'bmp' => function_exists('imagecreatefrombmp') ? @imagecreatefrombmp($realPath) : null,
                default => null,
            };
        }

        // If GD is unavailable or image cannot be parsed, fallback to default store
        if (!$srcImage || !function_exists('imagewebp')) {
            if ($file instanceof UploadedFile) {
                return $file->store($folder, 'public');
            }
            $filename = $folder . '/' . uniqid('img_') . '_' . time() . '.' . pathinfo($realPath, PATHINFO_EXTENSION);
            Storage::disk('public')->put($filename, file_get_contents($realPath));
            return $filename;
        }

        // Fix EXIF orientation for phone camera photos if exif_read_data is available
        if (function_exists('exif_read_data') && (str_contains($mime, 'jpeg') || str_contains($mime, 'jpg'))) {
            try {
                $exif = @exif_read_data($realPath);
                if (!empty($exif['Orientation'])) {
                    $srcImage = match ($exif['Orientation']) {
                        3 => imagerotate($srcImage, 180, 0),
                        6 => imagerotate($srcImage, -90, 0),
                        8 => imagerotate($srcImage, 90, 0),
                        default => $srcImage,
                    };
                }
            } catch (\Throwable $e) {
                // Ignore EXIF read errors
            }
        }

        $origWidth = imagesx($srcImage);
        $origHeight = imagesy($srcImage);

        // Calculate proportional scale if dimensions exceed max
        $ratio = min($maxWidth / max(1, $origWidth), $maxHeight / max(1, $origHeight), 1.0);
        $newWidth = (int) round($origWidth * $ratio);
        $newHeight = (int) round($origHeight * $ratio);

        // Create new truecolor image canvas
        $targetImage = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve alpha transparency for PNG / WebP
        imagealphablending($targetImage, false);
        imagesavealpha($targetImage, true);
        $transparent = imagecolorallocatealpha($targetImage, 255, 255, 255, 127);
        imagefilledrectangle($targetImage, 0, 0, $newWidth, $newHeight, $transparent);

        // Resample with anti-aliasing
        imagecopyresampled($targetImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

        // Encode to WebP buffer
        ob_start();
        imagewebp($targetImage, null, $quality);
        $webpBuffer = ob_get_clean();

        imagedestroy($srcImage);
        imagedestroy($targetImage);

        $filename = $folder . '/' . uniqid('opt_') . '_' . time() . '.webp';
        Storage::disk('public')->put($filename, $webpBuffer);

        return $filename;
    }

    /**
     * Compress an uploaded or existing PDF file without degrading visual quality.
     *
     * @param UploadedFile|string $file
     * @param string $folder Target storage folder (e.g. 'payslips/2026/08', 'attachments')
     * @param string|null $customFileName
     * @return string Relative storage path (e.g. 'payslips/2026/08/payslip_123.pdf')
     */
    public static function optimizePdfAndStore($file, string $folder = 'attachments', ?string $customFileName = null): string
    {
        $realPath = $file instanceof UploadedFile ? $file->getRealPath() : $file;
        $originalName = $file instanceof UploadedFile ? $file->getClientOriginalName() : basename($realPath);
        
        $fileName = $customFileName ?: (uniqid('doc_') . '_' . time() . '.pdf');
        $targetRelativePath = rtrim($folder, '/') . '/' . $fileName;
        $targetFullPath = storage_path('app/public/' . $targetRelativePath);

        // Ensure directory exists
        $targetDir = dirname($targetFullPath);
        if (!is_dir($targetDir)) {
            File::makeDirectory($targetDir, 0755, true);
        }

        // Try Ghostscript optimization if available
        $gsOptimized = self::tryGhostscriptCompression($realPath, $targetFullPath);

        if (!$gsOptimized) {
            // Try QPDF optimization if available
            $qpdfOptimized = self::tryQpdfCompression($realPath, $targetFullPath);

            if (!$qpdfOptimized) {
                // Fallback: Copy original file directly
                if ($file instanceof UploadedFile) {
                    $file->storeAs($folder, $fileName, 'public');
                } else {
                    File::copy($realPath, $targetFullPath);
                }
            }
        }

        return $targetRelativePath;
    }

    /**
     * Try Ghostscript PDF optimization with /ebook profile (150 DPI, crisp text & vectors, 40-70% size reduction).
     */
    private static function tryGhostscriptCompression(string $inputPath, string $outputPath): bool
    {
        if (!function_exists('shell_exec') && !function_exists('exec')) {
            return false;
        }

        // Detect Ghostscript binary name
        $gsBins = ['gs', 'gswin64c', 'gswin32c', '/usr/bin/gs', '/usr/local/bin/gs'];
        $foundBin = null;

        foreach ($gsBins as $bin) {
            $test = @shell_exec("{$bin} --version 2>&1");
            if ($test && preg_match('/^[0-9]+\.[0-9]+/', trim($test))) {
                $foundBin = $bin;
                break;
            }
        }

        if (!$foundBin) {
            return false;
        }

        $tempOut = storage_path('app/temp_gs_' . uniqid() . '.pdf');
        $cmd = "{$foundBin} -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/ebook -dNOPAUSE -dQUIET -dBATCH -sOutputFile=" . escapeshellarg($tempOut) . " " . escapeshellarg($inputPath) . " 2>&1";

        @shell_exec($cmd);

        if (file_exists($tempOut) && filesize($tempOut) > 0) {
            // Ensure compressed file is not bigger than original
            if (filesize($tempOut) < filesize($inputPath)) {
                File::move($tempOut, $outputPath);
                return true;
            } else {
                // Original is already smaller
                @unlink($tempOut);
                File::copy($inputPath, $outputPath);
                return true;
            }
        }

        if (file_exists($tempOut)) {
            @unlink($tempOut);
        }

        return false;
    }

    /**
     * Try QPDF stream compression & linearization.
     */
    private static function tryQpdfCompression(string $inputPath, string $outputPath): bool
    {
        if (!function_exists('shell_exec')) {
            return false;
        }

        $test = @shell_exec("qpdf --version 2>&1");
        if (!$test || !str_contains(strtolower($test), 'qpdf version')) {
            return false;
        }

        $tempOut = storage_path('app/temp_qpdf_' . uniqid() . '.pdf');
        $cmd = "qpdf --linearize --stream-data=compress " . escapeshellarg($inputPath) . " " . escapeshellarg($tempOut) . " 2>&1";

        @shell_exec($cmd);

        if (file_exists($tempOut) && filesize($tempOut) > 0) {
            File::move($tempOut, $outputPath);
            return true;
        }

        if (file_exists($tempOut)) {
            @unlink($tempOut);
        }

        return false;
    }
}
