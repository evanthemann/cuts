<?php
$mode       = ($_POST['mode'] ?? 'copy') === 'reencode' ? 'reencode' : 'copy';
$ffmpeg     = '/usr/bin/ffmpeg';
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

if ($mode === 'copy') {
    $listPath  = $uploadsDir . $jobId . '_concat.txt';
    $listLines = array_map(fn($c) => "file '" . str_replace("'", "\\'", $c['name']) . "'", $clips);
    file_put_contents($listPath, implode("\n", $listLines));

    $cmd = $ffmpeg . ' -loglevel error -f concat -safe 0'
         . ' -i ' . escapeshellarg($listPath)
         . ' -c copy -y '
         . escapeshellarg($outputPath);

    $bgCmd = '(' . $cmd . ' >> ' . escapeshellarg($logFile) . ' 2>&1'
           . '; rm -f ' . escapeshellarg($listPath)
           . '; if [ -f ' . escapeshellarg($outputPath) . ' ]; then echo ' . escapeshellarg('CUTS_DONE:' . $outputWeb) . ' >> ' . escapeshellarg($logFile)
           . '; else echo CUTS_FAIL >> ' . escapeshellarg($logFile) . '; fi) &';
} else {
    $inputArgs    = '';
    $filterInputs = '';
    foreach ($clips as $i => $c) {
        $inputArgs    .= ' -i ' . escapeshellarg($c['path']);
        $filterInputs .= "[{$i}:v:0][{$i}:a:0]";
    }
    $n             = count($clips);
    $filterComplex = $filterInputs . "concat=n={$n}:v=1:a=1[outv][outa]";

    $cmd = $ffmpeg . ' -loglevel error'
         . $inputArgs
         . ' -filter_complex ' . escapeshellarg($filterComplex)
         . ' -map "[outv]" -map "[outa]"'
         . ' -c:v libx264 -c:a aac -y '
         . escapeshellarg($outputPath);

    $bgCmd = '(' . $cmd . ' >> ' . escapeshellarg($logFile) . ' 2>&1'
           . '; if [ -f ' . escapeshellarg($outputPath) . ' ]; then echo ' . escapeshellarg('CUTS_DONE:' . $outputWeb) . ' >> ' . escapeshellarg($logFile)
           . '; else echo CUTS_FAIL >> ' . escapeshellarg($logFile) . '; fi) &';
}

shell_exec($bgCmd);

header('Location: progress.php?job=' . urlencode($jobId));
exit;
