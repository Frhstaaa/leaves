<?php

$size = 1024;
$img = imagecreatetruecolor($size, $size);
imagealphablending($img, false);
imagesavealpha($img, true);

// Clean pure white background
$bg = imagecolorallocate($img, 255, 255, 255);
imagefilledrectangle($img, 0, 0, $size, $size, $bg);
imagealphablending($img, true);

// Primary Sugiyama Emerald Green #0FA172
$green = imagecolorallocate($img, 15, 161, 114);
$darkGreen = imagecolorallocate($img, 10, 125, 88);
$lightGreen = imagecolorallocate($img, 30, 185, 135);

$cx = $size / 2;
$cy = $size / 2;

// Draw high-resolution stylized geometric corporate emblem
// Interlocking geometric precision links forming the Sugiyama S-shield symbol
$thickness = 64;

// Upper loop
$topCy = $cy - 120;
imagefilledellipse($img, $cx, $topCy, 380, 260, $green);
imagefilledellipse($img, $cx, $topCy, 380 - ($thickness * 2), 260 - ($thickness * 2), $bg);

// Lower loop
$botCy = $cy + 120;
imagefilledellipse($img, $cx, $botCy, 380, 260, $green);
imagefilledellipse($img, $cx, $botCy, 380 - ($thickness * 2), 260 - ($thickness * 2), $bg);

// Diagonal bridge connecting both loops
$polyPoints = [
    $cx - 150, $cy - 40,
    $cx - 70,  $cy - 140,
    $cx + 150, $cy + 40,
    $cx + 70,  $cy + 140,
];
imagefilledpolygon($img, $polyPoints, $green);

// Center diamond accent
$dSize = 48;
$diamond = [
    $cx, $cy - $dSize,
    $cx + $dSize, $cy,
    $cx, $cy + $dSize,
    $cx - $dSize, $cy,
];
imagefilledpolygon($img, $diamond, $bg);

$outPath = __DIR__ . '/public/icons/company_logo_master.png';
imagepng($img, $outPath, 9);
imagedestroy($img);

echo "Master logo generated: " . $outPath . "\n";
