<?php
$jobId = basename($_GET['job'] ?? '');
if (!$jobId) { header('Location: index.php'); exit; }

$logFile = __DIR__ . '/uploads/' . $jobId . '.log';

$rawLines   = [];
$done       = false;
$failed     = false;
$resultFile = null;
$probeData  = false;

if (file_exists($logFile)) {
    $rawLines = preg_split('/\r\n|\r|\n/', rtrim(file_get_contents($logFile)));
    $probeStart = null;
    foreach ($rawLines as $i => $line) {
        if (str_starts_with($line, 'CUTS_DONE:')) {
            $done       = true;
            $resultFile = substr($line, 10);
        }
        if ($line === 'CUTS_FAIL')        $failed     = true;
        if ($line === 'CUTS_PROBE_START') $probeStart = $i;
    }
    if ($probeStart !== null) {
        $probeData = implode("\n", array_slice($rawLines, $probeStart + 1));
    }
}

// Only show log lines before any CUTS_ markers, last 10
$displayLines = [];
foreach ($rawLines as $line) {
    if (str_starts_with($line, 'CUTS_')) break;
    if ($line !== '') $displayLines[] = $line;
}
$showLines = array_slice($displayLines, -10);

$ext     = $resultFile ? strtolower(pathinfo($resultFile, PATHINFO_EXTENSION)) : '';
$isAudio = in_array($ext, ['mp3', 'm4a', 'wav', 'aac', 'ogg', 'flac']);
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Cuts – Processing</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <?php if (!$done && !$failed): ?>
    <meta http-equiv="refresh" content="3">
    <?php endif; ?>
      <?php include 'darkHead.php'; ?>
  </head>
  <body>
    <div class="w3-container w3-padding-24">
      <h1>Cuts</h1>
      <div class="w3-card w3-padding" style="max-width:800px">

        <?php if ($done && $resultFile): ?>
          <h2 class="w3-text-green">Done</h2>
          <?php if ($isAudio): ?>
            <audio controls class="w3-margin-top" style="width:100%">
              <source src="<?= htmlspecialchars($resultFile) ?>">
            </audio>
          <?php else: ?>
            <video controls class="w3-margin-top" style="max-width:100%">
              <source src="<?= htmlspecialchars($resultFile) ?>">
            </video>
          <?php endif; ?>
          <p class="w3-small w3-text-grey w3-margin-top"><?= htmlspecialchars(basename($resultFile)) ?></p>

        <?php elseif ($failed): ?>
          <h2 class="w3-text-red">Failed</h2>
          <?php if ($probeData !== false): ?>
            <p>Fast mode requires all clips to share the same codec, resolution, frame rate, and pixel format. Here's what ffprobe found for each clip:</p>
            <pre style="background:#111;color:#ddd;padding:12px;overflow:auto;max-height:400px;font-size:12px;margin-top:8px"><?= htmlspecialchars($probeData) ?></pre>
            <p class="w3-margin-top">Go back and use <strong>Re-encode</strong> mode to combine clips with different settings.</p>
          <?php else: ?>
            <p>The command did not produce an output file. See the log below.</p>
          <?php endif; ?>

        <?php else: ?>
          <h2>Processing…</h2>
          <p class="w3-text-grey">Page refreshes every 3 seconds.</p>
        <?php endif; ?>

        <?php if (!empty($showLines)): ?>
          <pre style="background:#111;color:#ddd;padding:12px;overflow:auto;max-height:460px;font-size:12px;margin-top:16px"><?= htmlspecialchars(implode("\n", $showLines)) ?></pre>
        <?php elseif (!$done && !$failed): ?>
          <p class="w3-text-grey w3-small w3-margin-top">Waiting for output…</p>
        <?php endif; ?>

        <?php include 'backToHomeButton.php'; ?>
      </div>
    </div>
  </body>
</html>
