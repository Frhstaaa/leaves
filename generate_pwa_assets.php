<?php

$baseDir = __DIR__;
$iconsDir = $baseDir . '/public/icons';
if (!is_dir($iconsDir)) {
    mkdir($iconsDir, 0777, true);
}

// 1. Attempt to find company logo from various potential paths
$logoCandidates = [
    $baseDir . '/public/icons/company_logo_master.png',
    $baseDir . '/storage/app/public/logos',
    $baseDir . '/public/storage/logos',
    $baseDir . '/storage/logos',
];

$foundLogoPath = null;

// If Laravel is booted or available, check database setting
if (class_exists('\\App\\Models\\Setting')) {
    try {
        $settings = \App\Models\Setting::getAll();
        $dbLogo = $settings['app_pwa_icon'] ?? $settings['app_logo'] ?? null;
        if ($dbLogo) {
            $cleaned = preg_replace('/^\/?storage\//', '', $dbLogo);
            $checkPaths = [
                $baseDir . '/storage/app/public/' . $cleaned,
                $baseDir . '/public/storage/' . $cleaned,
                $baseDir . '/public/' . $dbLogo,
            ];
            foreach ($checkPaths as $cp) {
                if (file_exists($cp) && !is_dir($cp)) {
                    $foundLogoPath = $cp;
                    break;
                }
            }
        }
    } catch (\Throwable $e) {
        // Continue to file scan
    }
}

// If not found in DB setting, scan logo candidates
if (!$foundLogoPath) {
    foreach ($logoCandidates as $dir) {
        if (is_file($dir) && file_exists($dir)) {
            $foundLogoPath = $dir;
            break;
        } elseif (is_dir($dir)) {
            $files = scandir($dir);
            $latestTime = 0;
            $latestFile = null;
            foreach ($files as $f) {
                if ($f === '.' || $f === '..') continue;
                $full = $dir . '/' . $f;
                if (is_file($full)) {
                    $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
                    if (in_array($ext, ['png', 'webp', 'jpg', 'jpeg'])) {
                        $mtime = filemtime($full);
                        if ($mtime > $latestTime) {
                            $latestTime = $mtime;
                            $latestFile = $full;
                        }
                    }
                }
            }
            if ($latestFile) {
                $foundLogoPath = $latestFile;
                break;
            }
        }
    }
}

echo "Source Logo: " . ($foundLogoPath ? $foundLogoPath : "Default Brand Emblem") . "\n";

// Function to load image resource from path
function loadSourceImage($path) {
    if (!$path || !file_exists($path)) return null;
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    return match ($ext) {
        'png' => @imagecreatefrompng($path),
        'webp' => @imagecreatefromwebp($path),
        'jpg', 'jpeg' => @imagecreatefromjpeg($path),
        default => null,
    };
}

$sourceImg = loadSourceImage($foundLogoPath);

// Function to generate high-resolution PWA icon
function createPwaIcon($size, $isMaskable, $sourceImg) {
    $img = imagecreatetruecolor($size, $size);
    imagealphablending($img, false);
    imagesavealpha($img, true);

    if ($isMaskable || $size === 180) {
        // Solid crisp white canvas for maskable & iOS icons so no black background appears in launchers
        $bg = imagecolorallocate($img, 255, 255, 255);
        imagefilledrectangle($img, 0, 0, $size, $size, $bg);
        $padding = (int) round($size * 0.16); // 16% safe zone margin
    } else {
        // Clean white background with rounded corner feel for standard icon
        $bg = imagecolorallocate($img, 255, 255, 255);
        imagefilledrectangle($img, 0, 0, $size, $size, $bg);
        $padding = (int) round($size * 0.10); // 10% margin
    }

    if ($sourceImg) {
        $srcW = imagesx($sourceImg);
        $srcH = imagesy($sourceImg);

        $targetArea = $size - ($padding * 2);
        $ratio = min($targetArea / max(1, $srcW), $targetArea / max(1, $srcH));
        $newW = (int) round($srcW * $ratio);
        $newH = (int) round($srcH * $ratio);

        $dstX = (int) round(($size - $newW) / 2);
        $dstY = (int) round(($size - $newH) / 2);

        imagealphablending($img, true);
        imagecopyresampled($img, $sourceImg, $dstX, $dstY, 0, 0, $newW, $newH, $srcW, $srcH);
    } else {
        // Fallback load company logo master
        $masterFile = $baseDir . '/public/icons/company_logo_master.png';
        if (file_exists($masterFile)) {
            $mImg = @imagecreatefrompng($masterFile);
            if ($mImg) {
                imagealphablending($img, true);
                imagecopyresampled($img, $mImg, 0, 0, 0, 0, $size, $size, imagesx($mImg), imagesy($mImg));
                imagedestroy($mImg);
            }
        }
    }

    return $img;
}

$sizes = [
    ['size' => 180, 'maskable' => false, 'name' => 'icon-180x180.png'],
    ['size' => 192, 'maskable' => false, 'name' => 'icon-192x192.png'],
    ['size' => 192, 'maskable' => true,  'name' => 'icon-maskable-192.png'],
    ['size' => 512, 'maskable' => false, 'name' => 'icon-512x512.png'],
    ['size' => 512, 'maskable' => true,  'name' => 'icon-maskable-512.png'],
];

foreach ($sizes as $s) {
    $icon = createPwaIcon($s['size'], $s['maskable'], $sourceImg);
    imagepng($icon, $iconsDir . '/' . $s['name']);
    imagedestroy($icon);
    echo "Generated: " . $iconsDir . '/' . $s['name'] . "\n";
}

if ($sourceImg) {
    imagedestroy($sourceImg);
}

// Generate static manifest files in public
$appName = 'PT. Sugiyama';
$appDesc = 'Sistem Informasi Pengajuan Cuti & Slip Gaji Karyawan PT. Sugiyama Indonesia';
if (class_exists('\\App\\Models\\Setting')) {
    try {
        $settings = \App\Models\Setting::getAll();
        $appName = $settings['app_name'] ?? $appName;
        $appDesc = $settings['app_description'] ?? $appDesc;
    } catch (\Throwable $e) {}
}

$v = time();

$manifest = [
    'id' => './?source=pwa',
    'name' => $appName . ' - Cuti & Ketidakhadiran',
    'short_name' => $appName,
    'description' => $appDesc,
    'start_url' => './login?source=pwa',
    'scope' => './',
    'display' => 'standalone',
    'display_override' => ['window-controls-overlay', 'standalone', 'minimal-ui'],
    'background_color' => '#F5FAF7',
    'theme_color' => '#0FA172',
    'orientation' => 'portrait',
    'categories' => ['business', 'productivity', 'utilities'],
    'prefer_related_applications' => false,
    'icons' => [
        [
            'src' => './icons/icon-180x180.png',
            'sizes' => '180x180',
            'type' => 'image/png',
            'purpose' => 'any'
        ],
        [
            'src' => './icons/icon-192x192.png',
            'sizes' => '192x192',
            'type' => 'image/png',
            'purpose' => 'any'
        ],
        [
            'src' => './icons/icon-maskable-192.png',
            'sizes' => '192x192',
            'type' => 'image/png',
            'purpose' => 'maskable'
        ],
        [
            'src' => './icons/icon-512x512.png',
            'sizes' => '512x512',
            'type' => 'image/png',
            'purpose' => 'any'
        ],
        [
            'src' => './icons/icon-maskable-512.png',
            'sizes' => '512x512',
            'type' => 'image/png',
            'purpose' => 'maskable'
        ]
    ],
    'shortcuts' => [
        [
            'name' => 'Buat Pengajuan',
            'short_name' => 'Pengajuan',
            'description' => 'Buat pengajuan cuti atau izin baru',
            'url' => './leave-requests/create',
            'icons' => [['src' => './icons/icon-192x192.png', 'sizes' => '192x192']]
        ],
        [
            'name' => 'Persetujuan Team',
            'short_name' => 'Approval',
            'description' => 'Tinjau persetujuan cuti bawahan',
            'url' => './approvals',
            'icons' => [['src' => './icons/icon-192x192.png', 'sizes' => '192x192']]
        ]
    ]
];

$jsonContent = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
file_put_contents($baseDir . '/public/manifest.webmanifest', $jsonContent);
file_put_contents($baseDir . '/public/manifest.json', $jsonContent);
echo "✓ Manifest and static PWA assets synced with relative paths.\n";
