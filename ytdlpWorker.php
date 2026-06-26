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

// Find any subtitle files next to the output (broader than lang-code glob to handle en-orig etc.)
function findSubFiles($outputBase) {
    $subExts = ['vtt', 'srt', 'ass', 'ttml', 'srv3', 'srv2', 'srv1', 'json3'];
    $candidates = glob($outputBase . '.*') ?: [];
    return array_filter($candidates, fn($f) => in_array(strtolower(pathinfo($f, PATHINFO_EXTENSION)), $subExts));
}

if ($p['subsEnabled'] && $p['subsMode'] === 'soft' && file_exists($outputFile)) {
    // --embed-subs already embedded them; clean up loose files
    foreach (findSubFiles($p['outputBase']) as $sf) unlink($sf);
}

if ($p['subsEnabled'] && $p['subsMode'] === 'hard' && file_exists($outputFile)) {
    $subFiles = array_values(findSubFiles($p['outputBase']));
    echo "\n[cuts] Found " . count($subFiles) . " subtitle file(s) for burn-in: " . implode(', ', array_map('basename', $subFiles)) . "\n";
    if (!empty($subFiles)) {
        $subFile    = $subFiles[0];
        $burnedFile = $p['outputBase'] . '_burned.mp4';

        // Detect source height — upscale to 480p if too small for readable subs
        $probeJson  = shell_exec('ffprobe -v error -select_streams v:0 -show_entries stream=height -of json ' . escapeshellarg($outputFile) . ' 2>/dev/null');
        $probeData  = json_decode($probeJson, true);
        $srcHeight  = (int)($probeData['streams'][0]['height'] ?? 0);
        $needsScale = $srcHeight > 0 && $srcHeight < 480;

        if ($needsScale) {
            echo "[cuts] Source is {$srcHeight}p — upscaling to 480p before burn-in so subtitles are readable.\n";
        }

        // Build -vf argument: for upscale, chain scale before subtitles in one filter string
        $vfArg = $needsScale
            ? escapeshellarg('scale=-2:480:flags=lanczos,subtitles=' . $subFile)
            : 'subtitles=' . escapeshellarg($subFile);

        echo "[cuts] Running ffmpeg burn-in with: " . basename($subFile) . "\n";
        passthru(
            $ffmpeg
            . ' -loglevel error -stats'
            . ' -i ' . escapeshellarg($outputFile)
            . ' -vf ' . $vfArg
            . ' -c:a copy'
            . ' ' . escapeshellarg($burnedFile)
        );
        if (file_exists($burnedFile)) {
            $outputFile = $burnedFile;
            $outputWeb  = 'uploads/' . basename($burnedFile);
            unlink($p['outputBase'] . '.mp4');
            foreach ($subFiles as $sf) unlink($sf);
            echo "[cuts] Burn-in complete" . ($needsScale ? " (upscaled to 480p)." : ".") . "\n";
        } else {
            echo "[cuts] ffmpeg burn-in failed — burned file not found.\n";
        }
    } else {
        echo "[cuts] No subtitle files found — skipping burn-in.\n";
    }
}

echo "\n" . (file_exists($outputFile) ? 'CUTS_DONE:' . $outputWeb : 'CUTS_FAIL') . "\n";
