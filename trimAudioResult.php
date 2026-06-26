<?php
$filename = basename($_POST['filename'] ?? '');
if (!$filename) die('<div class="w3-panel w3-red">No file selected.</div>');

$startSeconds = floatval($_POST['startSeconds']);
$endSeconds   = floatval($_POST['endSeconds']);
$inputPath    = __DIR__ . '/uploads/' . $filename;

if (!file_exists($inputPath)) die('<div class="w3-panel w3-red">File not found.</div>');

$outputName = 'output_' . $filename;
$outputPath = __DIR__ . '/uploads/' . $outputName;
$outputWeb  = 'uploads/' . $outputName;

$ffmpeg = '/usr/bin/ffmpeg';
$cmd = $ffmpeg
    . ' -loglevel error'
    . ' -i ' . escapeshellarg($inputPath)
    . ' -ss ' . $startSeconds
    . ' -to ' . $endSeconds
    . ' -c:a copy -y '
    . escapeshellarg($outputPath);

$jobId   = uniqid('job_');
$logFile = __DIR__ . '/uploads/' . $jobId . '.log';

$bgCmd = '(' . $cmd . ' >> ' . escapeshellarg($logFile) . ' 2>&1'
       . '; if [ -f ' . escapeshellarg($outputPath) . ' ]; then echo ' . escapeshellarg('CUTS_DONE:' . $outputWeb) . ' >> ' . escapeshellarg($logFile)
       . '; else echo CUTS_FAIL >> ' . escapeshellarg($logFile) . '; fi) &';

shell_exec($bgCmd);

header('Location: progress.php?job=' . urlencode($jobId));
exit;
