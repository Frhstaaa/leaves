<?php

$baseDir = __DIR__;
$iconsDir = $baseDir . '/public/icons';
if (!is_dir($iconsDir)) {
    mkdir($iconsDir, 0777, true);
}

// Function to create crisp corporate icon with PT. Sugiyama green logo
function generateIcon($size, $isMaskable = false) {
    $img = imagecreatetruecolor($size, $size);
    imagealphablending($img, false);
    imagesavealpha($img, true);

    if ($isMaskable || $size === 180) {
        // Crisp white background for maskable and iOS
        $bg = imagecolorallocate($img, 255, 255, 255);
        imagefilledrectangle($img, 0, 0, $size, $size, $bg);
        $pad = (int) round($size * 0.15);
    } else {
        // Emerald background with rounded feel
        $bg = imagecolorallocate($img, 15, 161, 114); // #0FA172
        imagefilledrectangle($img, 0, 0, $size, $size, $bg);
        $pad = (int) round($size * 0.10);
    }

    $innerSize = $size - ($pad * 2);
    
    // Draw Emerald stylized "S" or "SG" badge
    $textColor = ($isMaskable || $size === 180) ? imagecolorallocate($img, 15, 161, 114) : imagecolorallocate($img, 255, 255, 255);
    
    // Draw stylish shield/square in center
    $centerX = (int) round($size / 2);
    $centerY = (int) round($size / 2);

    if ($isMaskable || $size === 180) {
        // Green rounded badge inside white canvas
        $badgeBg = imagecolorallocate($img, 15, 161, 114);
        imagefilledellipse($img, $centerX, $centerY, (int) round($innerSize * 0.95), (int) round($innerSize * 0.95), $badgeBg);
        $textColor = imagecolorallocate($img, 255, 255, 255);
    }

    // Bold text "SG" in center
    $font = 5; // Built-in GD font
    $text = "SG";
    $textW = imagefontwidth($font) * strlen($text);
    $textH = imagefontheight($font);
    
    // Scale text up if large icon
    $scale = max(1, (int) round($size / 70));
    $bigW = $textW * $scale;
    $bigH = $textH * $scale;
    
    $temp = imagecreatetruecolor($textW, $textH);
    $tBg = imagecolorallocate($temp, 0, 0, 0);
    imagecolortransparent($temp, $tBg);
    $tFg = imagecolorallocate($temp, 255, 255, 255);
    imagestring($temp, $font, 0, 0, $text, $tFg);
    
    $dstX = (int) round($centerX - ($bigW / 2));
    $dstY = (int) round($centerY - ($bigH / 2));
    
    imagecopyresized($img, $temp, $dstX, $dstY, 0, 0, $bigW, $bigH, $textW, $textH);
    imagedestroy($temp);

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
    $icon = generateIcon($s['size'], $s['maskable']);
    imagepng($icon, $iconsDir . '/' . $s['name']);
    imagedestroy($icon);
    echo "Generated: " . $iconsDir . '/' . $s['name'] . "\n";
}

// Generate static manifest files in public
$manifest = [
    'id' => './?source=pwa',
    'name' => 'PT. Sugiyama - Cuti & Ketidakhadiran',
    'short_name' => 'PT. Sugiyama',
    'description' => 'Sistem Informasi Pengajuan Cuti & Slip Gaji Karyawan PT. Sugiyama Indonesia',
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
            'src' => 'icons/icon-180x180.png',
            'sizes' => '180x180',
            'type' => 'image/png',
            'purpose' => 'any'
        ],
        [
            'src' => 'icons/icon-192x192.png',
            'sizes' => '192x192',
            'type' => 'image/png',
            'purpose' => 'any'
        ],
        [
            'src' => 'icons/icon-maskable-192.png',
            'sizes' => '192x192',
            'type' => 'image/png',
            'purpose' => 'maskable'
        ],
        [
            'src' => 'icons/icon-512x512.png',
            'sizes' => '512x512',
            'type' => 'image/png',
            'purpose' => 'any'
        ],
        [
            'src' => 'icons/icon-maskable-512.png',
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
            'url' => 'leave-requests/create',
            'icons' => [['src' => 'icons/icon-192x192.png', 'sizes' => '192x192']]
        ],
        [
            'name' => 'Persetujuan Team',
            'short_name' => 'Approval',
            'description' => 'Tinjau persetujuan cuti bawahan',
            'url' => 'approvals',
            'icons' => [['src' => 'icons/icon-192x192.png', 'sizes' => '192x192']]
        ]
    ]
];

$jsonContent = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
file_put_contents($baseDir . '/public/manifest.webmanifest', $jsonContent);
file_put_contents($baseDir . '/public/manifest.json', $jsonContent);
echo "Generated static manifest files in public/\n";
