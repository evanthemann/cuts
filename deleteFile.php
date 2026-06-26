<?php
$filename = basename($_POST['filename'] ?? '');
if (!$filename) { header('Location: index.php'); exit; }

$path = __DIR__ . '/uploads/' . $filename;
if (file_exists($path) && is_file($path)) {
    unlink($path);
}

header('Location: index.php');
exit;
