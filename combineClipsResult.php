<?php
$mode       = ($_POST['mode'] ?? 'reencode') === 'copy' ? 'copy' : 'reencode';
$ffmpeg     = '/usr/bin/ffmpeg';
$ffprobe    = '/usr/bin/ffprobe';
$uploadsDir = __DIR__ . '/uploads/';

$clips = [];
foreach ($_POST['clips'] ?? [] as $raw) {
    if ($raw === '') continue;
    $name = basename($raw);
    $path = $uploadsDir . $name;
    if (!file_exists($path)) continue;
    $clips[] = ['name' => $name, 'path' => $path];
}

if (count($clips) < 2) {
    die('<div class="w3-panel w3-red">Please select at least 2 clips.</div>');
}

$jobId      = uniqid('job_');
$outputName = 'combined_' . $jobId . '.mp4';
$outputPath = $uploadsDir . $outputName;
$outputWeb  = 'uploads/' . $outputName;
$logFile    = $uploadsDir . $jobId . '.log';

$pidFile       = $uploadsDir . $jobId . '.pid';
$totalDuration = 0;
foreach ($clips as $c) {
    $dOut = shell_exec($ffprobe . ' -v error -show_entries format=duration -of default=noprint_wrappers=1 ' . escapeshellarg($c['path']) . ' 2>/dev/null');
    if (preg_match('/duration=([\d.]+)/', $dOut ?? '', $dm)) $totalDuration += (float)$dm[1];
}
file_put_contents($logFile, 'CUTS_TOTAL_DURATION:' . $totalDuration . "\n");

if ($mode === 'copy') {
    // Probe all clips upfront to catch codec/format mismatches before running ffmpeg
    $probeLines = '';
    $codecs = [];
    foreach ($clips as $c) {
        $out = shell_exec($ffprobe . ' -v error -select_streams v:0'
            . ' -show_entries stream=codec_name,width,height,r_frame_rate,pix_fmt,sample_aspect_ratio'
            . ' -of default=noprint_wrappers=1 ' . escapeshellarg($c['path']) . ' 2>/dev/null');
        $probeLines .= '--- ' . $c['name'] . " ---\n" . trim($out ?? '') . "\n";
        if (preg_match('/codec_name=(\S+)/', $out ?? '', $m)) $codecs[] = $m[1];
    }

    $uniqueCodecs = array_unique($codecs);
    if (count($uniqueCodecs) > 1) {
        // Different codecs — fail immediately without running ffmpeg
        file_put_contents($logFile,
            'Clips use different codecs (' . implode(', ', $uniqueCodecs) . ') and cannot be joined without re-encoding.' . "\n"
            . "CUTS_FAIL\nCUTS_PROBE_START\n" . $probeLines, FILE_APPEND);
        header('Location: progress.php?job=' . urlencode($jobId));
        exit;
    }

    $listPath  = $uploadsDir . $jobId . '_concat.txt';
    $listLines = array_map(fn($c) => "file '" . str_replace("'", "\\'", $c['name']) . "'", $clips);
    file_put_contents($listPath, implode("\n", $listLines));

    // VP9 in MP4 requires the superframe BSF to strip superframe headers
    $bsf = ($codecs && $codecs[0] === 'vp9') ? ' -bsf:v vp9_superframe' : '';

    $cmd = $ffmpeg . ' -loglevel error -f concat -safe 0'
         . ' -i ' . escapeshellarg($listPath)
         . $bsf
         . ' -c copy -y '
         . escapeshellarg($outputPath);

    // Build ffprobe section to run on failure (other incompatibilities)
    $escapedProbeLines = escapeshellarg($probeLines);
    $bgCmd = '(' . $cmd . ' >> ' . escapeshellarg($logFile) . ' 2>&1 & _FFPID=$!; echo $_FFPID > ' . escapeshellarg($pidFile)
           . '; wait $_FFPID; rm -f ' . escapeshellarg($pidFile) . ' ' . escapeshellarg($listPath)
           . '; if grep -q CUTS_CANCELLED ' . escapeshellarg($logFile) . ' 2>/dev/null; then :'
           . '; elif [ -s ' . escapeshellarg($outputPath) . ' ]; then echo ' . escapeshellarg('CUTS_DONE:' . $outputWeb) . ' >> ' . escapeshellarg($logFile)
           . '; else echo CUTS_FAIL >> ' . escapeshellarg($logFile)
           . '; echo CUTS_PROBE_START >> ' . escapeshellarg($logFile)
           . '; echo ' . $escapedProbeLines . ' >> ' . escapeshellarg($logFile)
           . '; fi) > /dev/null 2>&1 &';
} else {
    // Pick the reference clip: explicit index or auto (highest resolution)
    $refParam = $_POST['reference'] ?? 'auto';
    if ($refParam === 'auto') {
        $bestIdx = 0; $bestPixels = 0;
        foreach ($clips as $i => $c) {
            $pOut = shell_exec($ffprobe . ' -v error -select_streams v:0'
                . ' -show_entries stream=width,height'
                . ' -of default=noprint_wrappers=1 ' . escapeshellarg($c['path']) . ' 2>/dev/null');
            $w = 0; $h = 0;
            if (preg_match('/width=(\d+)/', $pOut ?? '', $m))  $w = (int)$m[1];
            if (preg_match('/height=(\d+)/', $pOut ?? '', $m)) $h = (int)$m[1];
            if ($w * $h > $bestPixels) { $bestPixels = $w * $h; $bestIdx = $i; }
        }
        $refClip = $clips[$bestIdx];
    } else {
        $refIdx  = max(0, min((int)$refParam, count($clips) - 1));
        $refClip = $clips[$refIdx];
    }

    // Probe reference clip for resolution and frame rate
    $probeOut = shell_exec($ffprobe . ' -v error -select_streams v:0'
        . ' -show_entries stream=width,height,r_frame_rate'
        . ' -of default=noprint_wrappers=1 ' . escapeshellarg($refClip['path']) . ' 2>/dev/null');

    $targetW = 1280; $targetH = 720; $targetFps = '30';
    if ($probeOut) {
        if (preg_match('/width=(\d+)/', $probeOut, $m))       $targetW   = (int)$m[1];
        if (preg_match('/height=(\d+)/', $probeOut, $m))      $targetH   = (int)$m[1];
        if (preg_match('/r_frame_rate=(\S+)/', $probeOut, $m)) $targetFps = $m[1];
    }

    $inputArgs    = '';
    $filterParts  = '';
    $filterInputs = '';
    foreach ($clips as $i => $c) {
        $inputArgs    .= ' -i ' . escapeshellarg($c['path']);
        $filterParts  .= "[{$i}:v]scale={$targetW}:{$targetH}:force_original_aspect_ratio=decrease,"
                       . "pad={$targetW}:{$targetH}:(ow-iw)/2:(oh-ih)/2,"
                       . "fps={$targetFps},setsar=1[v{$i}];";
        $filterInputs .= "[v{$i}][{$i}:a:0]";
    }
    $n             = count($clips);
    $filterComplex = $filterParts . $filterInputs . "concat=n={$n}:v=1:a=1[outv][outa]";

    $cmd = $ffmpeg . ' -loglevel error -stats'
         . $inputArgs
         . ' -filter_complex ' . escapeshellarg($filterComplex)
         . ' -map "[outv]" -map "[outa]"'
         . ' -c:v libx264 -c:a aac -y '
         . escapeshellarg($outputPath);

    $bgCmd = '(' . $cmd . ' >> ' . escapeshellarg($logFile) . ' 2>&1 & _FFPID=$!; echo $_FFPID > ' . escapeshellarg($pidFile)
           . '; wait $_FFPID; rm -f ' . escapeshellarg($pidFile)
           . '; if grep -q CUTS_CANCELLED ' . escapeshellarg($logFile) . ' 2>/dev/null; then :'
           . '; elif [ -s ' . escapeshellarg($outputPath) . ' ]; then echo ' . escapeshellarg('CUTS_DONE:' . $outputWeb) . ' >> ' . escapeshellarg($logFile)
           . '; else echo CUTS_FAIL >> ' . escapeshellarg($logFile) . '; fi) > /dev/null 2>&1 &';
}

shell_exec($bgCmd);

header('Location: progress.php?job=' . urlencode($jobId));
exit;
