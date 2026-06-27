<?php
$old = basename($_POST['old_name'] ?? '');
$new = basename($_POST['new_name'] ?? '');

if (!$old || !$new || $old === $new) { header('Location: index.php'); exit; }

// Force same extension as original
$oldExt  = pathinfo($old, PATHINFO_EXTENSION);
$newBase = pathinfo($new, PATHINFO_FILENAME);
if (!$newBase) { header('Location: index.php'); exit; }
$new = $newBase . '.' . $oldExt;

$dir     = __DIR__ . '/uploads/';
$oldPath = $dir . $old;
$newPath = $dir . $new;

if (!file_exists($oldPath) || file_exists($newPath)) { header('Location: index.php'); exit; }

rename($oldPath, $newPath);

// Sidecars: .jpg, .vtt
$oldBase = pathinfo($old, PATHINFO_FILENAME);
foreach (['.jpg', '.vtt'] as $ext) {
    $o = $dir . $oldBase . $ext;
    if (file_exists($o)) rename($o, $dir . $newBase . $ext);
}

// Cached thumbnail
$oldThumb = $dir . 'thumbs/' . md5($old) . '.jpg';
$newThumb = $dir . 'thumbs/' . md5($new) . '.jpg';
if (file_exists($oldThumb)) rename($oldThumb, $newThumb);

header('Location: index.php');
exit;
