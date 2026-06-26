<?php
$filename = basename($_POST['filename'] ?? '');
if (!$filename) { header('Location: index.php'); exit; }

$path = __DIR__ . '/uploads/' . $filename;
if (file_exists($path) && is_file($path)) {
    unlink($path);

    // Delete sidecar thumbnail — check both exact name and without _burned suffix
    $base      = __DIR__ . '/uploads/' . pathinfo($filename, PATHINFO_FILENAME);
    $baseOrig  = preg_replace('/_burned$/', '', $base);
    foreach (array_unique([$base, $baseOrig]) as $b) {
        if (file_exists($b . '.jpg')) unlink($b . '.jpg');
    }

    // Delete cached ffmpeg thumb if present
    $cached = __DIR__ . '/uploads/thumbs/' . md5($filename) . '.jpg';
    if (file_exists($cached)) unlink($cached);
}

header('Location: index.php');
exit;
