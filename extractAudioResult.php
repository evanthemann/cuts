<?php

set_time_limit(0);

$filename = basename($_POST['filename'] ?? '');
$format   = ($_POST['format'] ?? 'mp3') === 'copy' ? 'copy' : 'mp3';

if (!$filename) {
    die('<div class="w3-panel w3-red">No file selected.</div>');
}

$inputFile = 'uploads/' . $filename;

if (!file_exists($inputFile)) {
    die('<div class="w3-panel w3-red">File not found.</div>');
}

$ffmpeg   = '/usr/bin/ffmpeg';
$basename = pathinfo($filename, PATHINFO_FILENAME);

if ($format === 'mp3') {
    $outputFile = 'uploads/' . $basename . '_audio.mp3';
    $cmd = $ffmpeg
        . ' -i ' . escapeshellarg($inputFile)
        . ' -vn -c:a libmp3lame -q:a 2 -y '
        . escapeshellarg($outputFile)
        . ' 2>&1';
} else {
    $outputFile = 'uploads/' . $basename . '_audio.m4a';
    $cmd = $ffmpeg
        . ' -i ' . escapeshellarg($inputFile)
        . ' -vn -c:a copy -y '
        . escapeshellarg($outputFile)
        . ' 2>&1';
}

shell_exec($cmd);

?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Cuts – Extract audio</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
  </head>
  <body>
    <div class="w3-container">
      <h1>Cuts</h1>
      <div class="w3-container w3-card w3-green w3-half">
        <h2 class="w3-monospace">Extract audio</h2>

        <?php if (!file_exists($outputFile)): ?>
          <div class="w3-panel w3-red">Extraction failed. The file may not contain an audio stream.</div>
        <?php else: ?>
          <audio controls class="w3-margin-top" style="width:100%">
            <source src="<?= htmlspecialchars($outputFile) ?>">
          </audio>
          <p class="w3-small"><?= htmlspecialchars(basename($outputFile)) ?></p>
        <?php endif; ?>

        <?php include 'videoFolder.php'; ?>
        <?php include 'backToHomeButton.php'; ?>
      </div>
    </div>
  </body>
</html>
