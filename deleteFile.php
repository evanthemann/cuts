<?php
function deleteOne($filename) {
    $filename = basename($filename);
    if (!$filename) return;
    $path = __DIR__ . '/uploads/' . $filename;
    if (!file_exists($path) || !is_file($path)) return;
    unlink($path);
    $base     = __DIR__ . '/uploads/' . pathinfo($filename, PATHINFO_FILENAME);
    $baseOrig = preg_replace('/_burned$/', '', $base);
    foreach (array_unique([$base, $baseOrig]) as $b) {
        if (file_exists($b . '.jpg')) unlink($b . '.jpg');
        if (file_exists($b . '.vtt')) unlink($b . '.vtt');
    }
    $cached = __DIR__ . '/uploads/thumbs/' . md5($filename) . '.jpg';
    if (file_exists($cached)) unlink($cached);
}

// Bulk delete
if (!empty($_POST['filenames']) && is_array($_POST['filenames'])) {
    foreach ($_POST['filenames'] as $name) deleteOne($name);
    header('Location: index.php');
    exit;
}

// Single delete (legacy)
if (!empty($_POST['filename'])) {
    deleteOne($_POST['filename']);
}

header('Location: index.php');
exit;
