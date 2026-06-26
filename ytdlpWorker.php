#!/usr/bin/env php
<?php
// CLI-only worker launched in background by ytdlpAdvancedDownload.php
// Usage: php ytdlpWorker.php <paramsFile>
// stdout/stderr are redirected to the job log file by the caller

if (PHP_SAPI !== 'cli') exit(1);

$paramsFile = $argv[1] ?? null;
if (!$paramsFile || !file_exists($paramsFile)) exit(1);

$p = json_decode(file_get_contents($paramsFile), true);
unlink($paramsFile);

$ytdlp  = '/usr/local/bin/yt-dlp';
$ffmpeg = '/usr/bin/ffmpeg';

$formatArg = ($p['formatMode'] === 'combined')
    ? $p['vidFormat']
    : $p['vidFormat'] . '+' . $p['audFormat'];

$cmd = $ytdlp
    . ' -f ' . escapeshellarg($formatArg)
    . ' --no-playlist'
    . ' --merge-output-format mp4'
    . ' --write-thumbnail --convert-thumbnails jpg'
    . ' -o ' . escapeshellarg($p['outputBase'] . '.%(ext)s');

if ($p['subsEnabled']) {
    $cmd .= ' --write-subs --write-auto-subs'
         .  ' --sub-langs ' . escapeshellarg($p['subsLang'])
         .  ' --sub-format ' . escapeshellarg('srt/vtt/best');
    if ($p['subsMode'] === 'soft') {
        $cmd .= ' --embed-subs';
    }
}
$cmd .= ' ' . escapeshellarg($p['url']);

// Run yt-dlp — output goes to stdout which is redirected to the log file
passthru($cmd);

$outputFile = $p['outputBase'] . '.mp4';
$outputWeb  = 'uploads/' . basename($outputFile);
$subPattern = $p['outputBase'] . '.' . $p['subsLang'] . '.*';

if ($p['subsEnabled'] && $p['subsMode'] === 'soft' && file_exists($outputFile)) {
    foreach (glob($subPattern) as $sf) unlink($sf);
}

if ($p['subsEnabled'] && $p['subsMode'] === 'hard' && file_exists($outputFile)) {
    $subFiles = glob($subPattern);
    if (!empty($subFiles)) {
        $subFile    = $subFiles[0];
        $burnedFile = $p['outputBase'] . '_burned.mp4';
        passthru(
            $ffmpeg
            . ' -loglevel error'
            . ' -i ' . escapeshellarg($outputFile)
            . ' -vf subtitles=' . escapeshellarg($subFile)
            . ' -c:a copy '
            . escapeshellarg($burnedFile)
        );
        if (file_exists($burnedFile)) {
            $outputFile = $burnedFile;
            $outputWeb  = 'uploads/' . basename($burnedFile);
            unlink($p['outputBase'] . '.mp4');
            foreach ($subFiles as $sf) unlink($sf);
        }
    }
}

echo "\n" . (file_exists($outputFile) ? 'CUTS_DONE:' . $outputWeb : 'CUTS_FAIL') . "\n";
