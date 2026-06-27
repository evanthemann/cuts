<?php
$filename = basename($_POST['filename'] ?? '');
$format   = ($_POST['format'] ?? 'mp3') === 'copy' ? 'copy' : 'mp3';

if (!$filename) die('<div class="w3-panel w3-red">No file selected.</div>');

$inputPath = __DIR__ . '/uploads/' . $filename;
if (!file_exists($inputPath)) die('<div class="w3-panel w3-red">File not found.</div>');

$ffmpeg   = '/usr/bin/ffmpeg';
$basename = pathinfo($filename, PATHINFO_FILENAME);

if ($format === 'mp3') {
    $outputName = $basename . '_audio.mp3';
    $cmd = $ffmpeg . ' -loglevel error -stats -i ' . escapeshellarg($inputPath) . ' -vn -c:a libmp3lame -q:a 2 -y ';
} else {
    $outputName = $basename . '_audio.m4a';
    $cmd = $ffmpeg . ' -loglevel error -stats -i ' . escapeshellarg($inputPath) . ' -vn -c:a copy -y ';
}

$outputPath = __DIR__ . '/uploads/' . $outputName;
$outputWeb  = 'uploads/' . $outputName;
$cmd       .= escapeshellarg($outputPath);

$jobId         = uniqid('job_');
$logFile       = __DIR__ . '/uploads/' . $jobId . '.log';
$pidFile       = __DIR__ . '/uploads/' . $jobId . '.pid';
$durOut        = shell_exec('/usr/bin/ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1 ' . escapeshellarg($inputPath) . ' 2>/dev/null');
$totalDuration = 0;
if (preg_match('/duration=([\d.]+)/', $durOut ?? '', $dm)) $totalDuration = (float)$dm[1];
file_put_contents($logFile, 'CUTS_TOTAL_DURATION:' . $totalDuration . "\n"
    . 'CUTS_OP:extract_audio' . "\n"
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
