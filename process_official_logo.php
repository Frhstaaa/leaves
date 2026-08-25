<?php

$srcPath = __DIR__ . '/public/icons/official_company_logo.png';
$src = imagecreatefromstring(file_get_contents($srcPath));
$srcW = imagesx($src);
$srcH = imagesy($src);

// Find bounding box of GREEN pixels
$minX = $srcW;
$minY = $srcH;
$maxX = 0;
$maxY = 0;

$searchW = (int) round($srcW * 0.30);

for ($y = 0; $y < $srcH; $y++) {
    for ($x = 0; $x < $searchW; $x++) {
        $rgb = imagecolorat($src, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;

        // Check if green is dominant (the green hexagon emblem)
        if ($g > 100 && $g > ($r + 25) && $g > ($b + 25)) {
            if ($x < $minX) $minX = $x;
            if ($x > $maxX) $maxX = $x;
            if ($y < $minY) $minY = $y;
            if ($y > $maxY) $maxY = $y;
        }
    }
}

echo "Green emblem bounds: x=[$minX, $maxX], y=[$minY, $maxY]\n";

$pad = 4;
$minX = max(0, $minX - $pad);
$minY = max(0, $minY - $pad);
$maxX = min($srcW - 1, $maxX + $pad);
$maxY = min($srcH - 1, $maxY + $pad);

$emblemW = $maxX - $minX + 1;
$emblemH = $maxY - $minY + 1;

$emblem = imagecreatetruecolor($emblemW, $emblemH);
$white = imagecolorallocate($emblem, 255, 255, 255);
imagefilledrectangle($emblem, 0, 0, $emblemW, $emblemH, $white);
imagecopy($emblem, $src, 0, 0, $minX, $minY, $emblemW, $emblemH);

// Clean any stray grey/black artifacts
for ($ey = 0; $ey < $emblemH; $ey++) {
    for ($ex = 0; $ex < $emblemW; $ex++) {
        $rgb = imagecolorat($emblem, $ex, $ey);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        if (!($g > 50 && $g > ($r + 10) && $g > ($b + 10))) {
            imagesetpixel($emblem, $ex, $ey, $white);
        }
    }
}

// Master 1024x1024 Icon
$masterSize = 1024;
$master = imagecreatetruecolor($masterSize, $masterSize);
$mWhite = imagecolorallocate($master, 255, 255, 255);
imagefilledrectangle($master, 0, 0, $masterSize, $masterSize, $mWhite);

// Center emblem with generous safe margin (62% of canvas size)
$targetInner = (int) round($masterSize * 0.62);
$scale = min($targetInner / $emblemW, $targetInner / $emblemH);
$dstW = (int) round($emblemW * $scale);
$dstH = (int) round($emblemH * $scale);
$dstX = (int) round(($masterSize - $dstW) / 2);
$dstY = (int) round(($masterSize - $dstH) / 2);

imagealphablending($master, true);
imagecopyresampled($master, $emblem, $dstX, $dstY, 0, 0, $dstW, $dstH, $emblemW, $emblemH);

// Save master logo
$masterPath = __DIR__ . '/public/icons/company_logo_master.png';
imagepng($master, $masterPath, 9);
echo "Master emblem saved: $masterPath\n";

// Copy to storage/app/public/logos/
$storageLogoDir = __DIR__ . '/storage/app/public/logos';
if (!is_dir($storageLogoDir)) {
    mkdir($storageLogoDir, 0777, true);
}
copy($srcPath, $storageLogoDir . '/official_company_logo.png');
copy($masterPath, $storageLogoDir . '/company_logo_master.png');

// Generate all PWA icon resolutions
$sizes = [
    ['size' => 180, 'name' => 'icon-180x180.png'],
    ['size' => 192, 'name' => 'icon-192x192.png'],
    ['size' => 192, 'name' => 'icon-maskable-192.png'],
    ['size' => 512, 'name' => 'icon-512x512.png'],
    ['size' => 512, 'name' => 'icon-maskable-512.png'],
];

foreach ($sizes as $s) {
    $out = imagecreatetruecolor($s['size'], $s['size']);
    $bg = imagecolorallocate($out, 255, 255, 255);
    imagefilledrectangle($out, 0, 0, $s['size'], $s['size'], $bg);
    
    imagealphablending($out, true);
    imagecopyresampled($out, $master, 0, 0, 0, 0, $s['size'], $s['size'], $masterSize, $masterSize);
    
    $outPath = __DIR__ . '/public/icons/' . $s['name'];
    imagepng($out, $outPath, 9);
    imagedestroy($out);
    echo "Generated: " . $s['name'] . "\n";
}

imagedestroy($src);
imagedestroy($emblem);
imagedestroy($master);
echo "Clean green Sugiyama emblem generated successfully!\n";
