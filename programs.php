<?php
// programs.php - returns a JSON list of image files for blog/program display
header('Content-Type: application/json; charset=utf-8');

$imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$files = [];

// 1) Preferred source: programs/ directory
$programsDir = __DIR__ . '/programs';
if (is_dir($programsDir)) {
    $items = scandir($programsDir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $programsDir . DIRECTORY_SEPARATOR . $item;
        if (!is_file($path)) {
            continue;
        }
        $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
        if (in_array($ext, $imageExts, true)) {
            $files[] = 'programs/' . rawurlencode($item);
        }
    }
}

// 2) Additional source: root files named "download*.jpg|png|..."
foreach (glob(__DIR__ . '/download*.*') ?: [] as $path) {
    if (!is_file($path)) {
        continue;
    }
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if (in_array($ext, $imageExts, true)) {
        $files[] = rawurlencode(basename($path));
    }
}

$files = array_values(array_unique($files));
echo json_encode($files);
