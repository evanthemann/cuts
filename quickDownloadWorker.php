#!/usr/bin/env php
<?php
if (PHP_SAPI !== 'cli') exit(1);

$url   = $argv[1] ?? null;
$jobId = $argv[2] ?? null;
if (!$url || !$jobId) exit(1);

$ytdlp    = '/usr/local/bin/yt-dlp';
$pathFile = __DIR__ . '/uploads/' . $jobId . '_path.txt';

$cmd = $ytdlp
    . ' -f "18/best[ext=mp4]"'
    . ' --no-playlist'
    . ' --merge-output-format mp4'
    . ' --write-thumbnail --convert-thumbnails jpg'
    . ' -o ' . escapeshellarg(__DIR__ . '/uploads/%(title)s.%(ext)s')
    . ' --print after_move:filepath'
    . ' ' . escapeshellarg($url);

// fd1 (stdout) → pathFile to capture the printed filepath
// fd2 (stderr) → inherited PHP stdout → log (so progress shows in real time)
$descriptors = [
    0 => ['file', '/dev/null', 'r'],
    1 => ['file', $pathFile, 'w'],
    2 => STDOUT,
];
$proc = proc_open($cmd, $descriptors, $pipes);
proc_close($proc);

$outputPath = trim((string)(file_exists($pathFile) ? file_get_contents($pathFile) : ''));
if (file_exists($pathFile)) unlink($pathFile);

if ($outputPath && file_exists($outputPath)) {
    echo "\nCUTS_DONE:uploads/" . basename($outputPath) . "\n";
} else {
    echo "\nCUTS_FAIL\n";
}
