<?php
$youtubeUrl = $_POST['youtubeUrl'] ?? '';
if (!$youtubeUrl) { header('Location: youtube.php'); exit; }

$ytdlp = '/usr/local/bin/yt-dlp';

// Fetch title for a meaningful filename (fast, synchronous)
$rawTitle          = trim((string) shell_exec($ytdlp . ' --get-filename --no-playlist -o "%(title)s" ' . escapeshellarg($youtubeUrl) . ' 2>/dev/null'));
$sanitizedFilename = preg_replace('/_+/', '_', preg_replace('/[^a-zA-Z0-9_\-]/', '_', $rawTitle));
$sanitizedFilename = trim($sanitizedFilename, '_');
if (empty($sanitizedFilename)) $sanitizedFilename = 'ytdlp_' . time();

$outputWeb  = 'uploads/' . $sanitizedFilename . '.mp4';
$outputPath = __DIR__ . '/' . $outputWeb;

$jobId   = uniqid('job_');
$logFile = __DIR__ . '/uploads/' . $jobId . '.log';

$cmd = $ytdlp
    . ' -f "18/best[ext=mp4]"'
    . ' --no-playlist'
    . ' -o ' . escapeshellarg('uploads/' . $sanitizedFilename . '.%(ext)s')
    . ' ' . escapeshellarg($youtubeUrl);

$bgCmd = '(' . $cmd . ' >> ' . escapeshellarg($logFile) . ' 2>&1'
       . '; if [ -f ' . escapeshellarg($outputPath) . ' ]; then echo ' . escapeshellarg('CUTS_DONE:' . $outputWeb) . ' >> ' . escapeshellarg($logFile)
       . '; else echo CUTS_FAIL >> ' . escapeshellarg($logFile) . '; fi) &';

shell_exec($bgCmd);

header('Location: progress.php?job=' . urlencode($jobId));
exit;
