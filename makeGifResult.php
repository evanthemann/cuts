<?php
$ffmpeg     = '/usr/bin/ffmpeg';
$ffprobe    = '/usr/bin/ffprobe';
$uploadsDir = __DIR__ . '/uploads/';

$filename = basename($_POST['filename'] ?? '');
if (!$filename) die('<div class="w3-panel w3-red w3-padding">No file selected.</div>');

$inputPath = $uploadsDir . $filename;
if (!file_exists($inputPath)) die('<div class="w3-panel w3-red w3-padding">File not found.</div>');

// Hard-reject if over the 7s limit
$dOut = shell_exec($ffprobe . ' -v error -show_entries format=duration -of default=noprint_wrappers=1 ' . escapeshellarg($inputPath) . ' 2>/dev/null');
$fileDuration = 0;
if (preg_match('/duration=([\d.]+)/', $dOut ?? '', $m)) $fileDuration = (float)$m[1];
if ($fileDuration > 10.0) die('<div class="w3-panel w3-red w3-padding">File exceeds 10-second limit.</div>');

$startSeconds = isset($_POST['startSeconds']) && $_POST['startSeconds'] !== '' ? (float)$_POST['startSeconds'] : null;
$endSeconds   = isset($_POST['endSeconds'])   && $_POST['endSeconds']   !== '' ? (float)$_POST['endSeconds']   : null;

// Clip duration for progress tracking
$clipStart    = $startSeconds ?? 0;
$clipEnd      = $endSeconds   ?? $fileDuration;
$clipDuration = max(0.1, $clipEnd - $clipStart);

$jobId      = uniqid('job_');
$stem       = pathinfo($filename, PATHINFO_FILENAME);
$outputName = $stem . '_gifted_' . substr($jobId, -6) . '.gif';
$outputPath = $uploadsDir . $outputName;
$outputWeb  = 'uploads/' . $outputName;
$logFile    = $uploadsDir . $jobId . '.log';
$pidFile    = $uploadsDir . $jobId . '.pid';
$palFile    = $uploadsDir . $jobId . '_palette.png';

// Build optional time args (same for both passes)
$timeArgs = '';
if ($startSeconds !== null) $timeArgs .= ' -ss ' . $startSeconds;
if ($endSeconds   !== null) $timeArgs .= ' -to ' . $endSeconds;

// Pass 1 — palettegen (synchronous; fast for short clips)
$pass1 = $ffmpeg . ' -loglevel error'
       . $timeArgs
       . ' -i ' . escapeshellarg($inputPath)
       . ' -vf ' . escapeshellarg('fps=15,scale=480:-1:flags=lanczos,palettegen')
       . ' -y ' . escapeshellarg($palFile);
shell_exec($pass1 . ' 2>/dev/null');

if (!file_exists($palFile) || filesize($palFile) === 0) {
    die('<div class="w3-panel w3-red w3-padding">Palette generation failed. The file may be unreadable.</div>');
}

// Write job metadata before launching pass 2
file_put_contents($logFile,
    'CUTS_TOTAL_DURATION:' . $clipDuration . "\n"
    . 'CUTS_OP:make_gif' . "\n"
    . 'CUTS_INPUTS:' . $filename . "\n"
    . 'CUTS_OUTPUT:' . $outputName . "\n");

// Pass 2 — paletteuse (async background job)
// paletteuse takes 2 inputs so must use -filter_complex, not simple -vf
$pass2 = $ffmpeg . ' -loglevel error -stats'
       . $timeArgs
       . ' -i ' . escapeshellarg($inputPath)
       . ' -i ' . escapeshellarg($palFile)
       . ' -filter_complex ' . escapeshellarg('[0:v]fps=15,scale=480:-1:flags=lanczos[x];[x][1:v]paletteuse')
       . ' -y ' . escapeshellarg($outputPath);

$bgCmd = '(' . $pass2 . ' >> ' . escapeshellarg($logFile) . ' 2>&1 & _FFPID=$!; echo $_FFPID > ' . escapeshellarg($pidFile)
       . '; wait $_FFPID; rm -f ' . escapeshellarg($pidFile) . ' ' . escapeshellarg($palFile)
       . '; if grep -q CUTS_CANCELLED ' . escapeshellarg($logFile) . ' 2>/dev/null; then :'
       . '; elif [ -s ' . escapeshellarg($outputPath) . ' ]; then echo ' . escapeshellarg('CUTS_DONE:' . $outputWeb) . ' >> ' . escapeshellarg($logFile)
       . '; else echo CUTS_FAIL >> ' . escapeshellarg($logFile) . '; fi) > /dev/null 2>&1 &';

shell_exec($bgCmd);

header('Location: progress.php?job=' . urlencode($jobId));
exit;
