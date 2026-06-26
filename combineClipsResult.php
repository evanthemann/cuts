<?php

set_time_limit(0);

$mode     = ($_POST['mode'] ?? 'copy') === 'reencode' ? 'reencode' : 'copy';
$ffmpeg   = '/usr/bin/ffmpeg';
$uploadsDir = __DIR__ . '/uploads/';

// Collect and validate selected clips
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

$outputName = 'combined_' . time() . '.mp4';
$outputPath = $uploadsDir . $outputName;
$outputWeb  = 'uploads/' . $outputName;

if ($mode === 'copy') {
    // Write concat list file — paths relative to uploads/ dir (where the list lives)
    $listPath = $uploadsDir . 'concat_' . time() . '.txt';
    $lines    = array_map(fn($c) => "file '" . str_replace("'", "\\'", $c['name']) . "'", $clips);
    file_put_contents($listPath, implode("\n", $lines));

    shell_exec(
        $ffmpeg
        . ' -f concat -safe 0'
        . ' -i ' . escapeshellarg($listPath)
        . ' -c copy -y '
        . escapeshellarg($outputPath)
        . ' 2>&1'
    );

    unlink($listPath);

} else {
    // Concat filter — re-encodes, handles mixed formats
    $inputArgs    = '';
    $filterInputs = '';
    foreach ($clips as $i => $c) {
        $inputArgs    .= ' -i ' . escapeshellarg($c['path']);
        $filterInputs .= "[{$i}:v:0][{$i}:a:0]";
    }
    $n             = count($clips);
    $filterComplex = $filterInputs . "concat=n={$n}:v=1:a=1[outv][outa]";

    shell_exec(
        $ffmpeg
        . $inputArgs
        . ' -filter_complex ' . escapeshellarg($filterComplex)
        . ' -map "[outv]" -map "[outa]"'
        . ' -c:v libx264 -c:a aac -y '
        . escapeshellarg($outputPath)
        . ' 2>&1'
    );
}

?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Cuts – Combine clips</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
  </head>
  <body>
    <div class="w3-container">
      <h1>Cuts</h1>
      <div class="w3-container w3-card w3-orange w3-half">
        <h2 class="w3-monospace">Combine clips</h2>

        <?php if (!file_exists($outputPath)): ?>
          <div class="w3-panel w3-red">
            Combine failed. If you used Fast mode, try Re-encode — clips may have different codecs or resolutions.
          </div>
        <?php else: ?>
          <p class="w3-small">Combined <?= count($clips) ?> clips → <?= htmlspecialchars($outputName) ?></p>
          <video width="480" controls class="w3-margin-top">
            <source src="<?= htmlspecialchars($outputWeb) ?>">
          </video>
        <?php endif; ?>

        <?php include 'videoFolder.php'; ?>
        <?php include 'backToHomeButton.php'; ?>
      </div>
    </div>
  </body>
</html>
