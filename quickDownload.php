<?php
$youtubeUrl = $_POST['youtubeUrl'] ?? '';
if (!$youtubeUrl) { header('Location: youtube.php'); exit; }

$jobId   = uniqid('job_');
$logFile = __DIR__ . '/uploads/' . $jobId . '.log';

shell_exec('php ' . escapeshellarg(__DIR__ . '/quickDownloadWorker.php')
    . ' ' . escapeshellarg($youtubeUrl)
    . ' ' . escapeshellarg($jobId)
    . ' >> ' . escapeshellarg($logFile) . ' 2>&1 &');

header('Location: progress.php?job=' . urlencode($jobId));
exit;
