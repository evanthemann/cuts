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
    . ' -loglevel error -stats'
    . ' -i ' . escapeshellarg($inputPath)
    . ' -ss ' . $startSeconds
    . ' -to ' . $endSeconds
    . ' -c:v copy -c:a copy -y '
    . escapeshellarg($outputPath);

$jobId         = uniqid('job_');
$logFile       = __DIR__ . '/uploads/' . $jobId . '.log';
$pidFile       = __DIR__ . '/uploads/' . $jobId . '.pid';
$totalDuration = max(0, $endSeconds - $startSeconds);
file_put_contents($logFile, 'CUTS_TOTAL_DURATION:' . $totalDuration . "\n"
    . 'CUTS_OP:trim' . "\n"
    . 'CUTS_INPUTS:' . $filename . "\n"
    . 'CUTS_OUTPUT:' . $outputName . "\n");

$bgCmd = '(' . $cmd . ' >> ' . escapeshellarg($logFile) . ' 2>&1 & _FFPID=$!; echo $_FFPID > ' . escapeshellarg($pidFile)
       . '; wait $_FFPID; rm -f ' . escapeshellarg($pidFile)
       . '; if grep -q CUTS_CANCELLED ' . escapeshellarg($logFile) . ' 2>/dev/null; then :'
       . '; elif [ -s ' . escapeshellarg($outputPath) . ' ]; then echo ' . escapeshellarg('CUTS_DONE:' . $outputWeb) . ' >> ' . escapeshellarg($logFile)
       . '; else echo CUTS_FAIL >> ' . escapeshellarg($logFile) . '; fi) > /dev/null 2>&1 &';

shell_exec($bgCmd);

header('Location: progress.php?job=' . urlencode($jobId));
exit;
