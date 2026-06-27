<?php
$jobId = basename($_POST['job'] ?? '');
if (!$jobId) { header('Location: index.php'); exit; }

$uploadsDir = __DIR__ . '/uploads/';
$logFile    = $uploadsDir . $jobId . '.log';
$pidFile    = $uploadsDir . $jobId . '.pid';

if (file_exists($logFile)) {
    file_put_contents($logFile, "\nCUTS_CANCELLED\n", FILE_APPEND);
}

if (file_exists($pidFile)) {
    $pid = (int)trim(file_get_contents($pidFile));
    if ($pid > 0) shell_exec('kill ' . $pid . ' 2>/dev/null');
    unlink($pidFile);
}

header('Location: progress.php?job=' . urlencode($jobId));
exit;
