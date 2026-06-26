<?php
$youtubeUrl = $_POST['youtubeUrl'] ?? '';
if (!$youtubeUrl) { header('Location: youtube.php'); exit; }

$ytdlp    = '/usr/local/bin/yt-dlp';
$jobId    = uniqid('fmt_');
$jsonFile = __DIR__ . '/uploads/' . $jobId . '.json';
$logFile  = __DIR__ . '/uploads/' . $jobId . '.log';

$ytdlpCmd = $ytdlp . ' -J --no-download --no-warnings --no-playlist ' . escapeshellarg($youtubeUrl)
    . ' >> ' . escapeshellarg($logFile)
    . ' 2>&1 > ' . escapeshellarg($jsonFile);

file_put_contents($logFile, '[' . date('H:i:s') . '] $ ' . $ytdlpCmd . "\n");

shell_exec($ytdlpCmd . ' &');

header('Location: ytdlpAdvancedFormats.php?job=' . urlencode($jobId) . '&url=' . urlencode($youtubeUrl));
exit;
