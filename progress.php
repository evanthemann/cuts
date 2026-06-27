<?php
$jobId = basename($_GET['job'] ?? '');
if (!$jobId) { header('Location: index.php'); exit; }

$logFile = __DIR__ . '/uploads/' . $jobId . '.log';

$rawLines      = [];
$done          = false;
$failed        = false;
$cancelled     = false;
$resultFile    = null;
$probeData     = false;
$totalDuration = 0;
$jobOp         = null;
$jobInputs     = [];
$jobOutput     = null;

if (file_exists($logFile)) {
    $rawLines = preg_split('/\r\n|\r|\n/', rtrim(file_get_contents($logFile)));
    $probeStart = null;
    foreach ($rawLines as $i => $line) {
        if (str_starts_with($line, 'CUTS_DONE:')) {
            $done       = true;
            $resultFile = substr($line, 10);
        }
        if ($line === 'CUTS_FAIL')        $failed        = true;
        if ($line === 'CUTS_CANCELLED')   $cancelled     = true;
        if ($line === 'CUTS_PROBE_START') $probeStart    = $i;
        if (str_starts_with($line, 'CUTS_TOTAL_DURATION:')) $totalDuration = (float)substr($line, 20);
        if (str_starts_with($line, 'CUTS_OP:'))            $jobOp         = substr($line, 8);
        if (str_starts_with($line, 'CUTS_INPUTS:'))        $jobInputs     = explode(',', substr($line, 12));
        if (str_starts_with($line, 'CUTS_OUTPUT:'))        $jobOutput     = substr($line, 12);
    }
    if ($probeStart !== null) {
        $probeData = implode("\n", array_slice($rawLines, $probeStart + 1));
    }
}

// Show log lines before terminal CUTS_ markers (skip metadata sentinels), last 10
$displayLines = [];
foreach ($rawLines as $line) {
    if ($line === 'CUTS_FAIL' || $line === 'CUTS_CANCELLED' || $line === 'CUTS_PROBE_START' || str_starts_with($line, 'CUTS_DONE:')) break;
    if (str_starts_with($line, 'CUTS_')) continue;
    if ($line !== '') $displayLines[] = $line;
}
$showLines = array_slice($displayLines, -10);

// Parse current encode time from latest stats line
$currentSec = 0;
if ($totalDuration > 0 && !$done && !$failed) {
    foreach (array_reverse($displayLines) as $line) {
        if (preg_match('/time=(\d+):(\d+):([\d.]+)/', $line, $tm)) {
            $currentSec = $tm[1] * 3600 + $tm[2] * 60 + (float)$tm[3];
            break;
        }
    }
}

$ext     = $resultFile ? strtolower(pathinfo($resultFile, PATHINFO_EXTENSION)) : '';
$isAudio = in_array($ext, ['mp3', 'm4a', 'wav', 'aac', 'ogg', 'flac']);

// Write job history record on first terminal render
if (($done || $failed || $cancelled) && $jobOp) {
    require_once __DIR__ . '/jobHistory.php';
    $histStatus = $done ? 'done' : ($cancelled ? 'cancelled' : 'failed');
    logJobHistory($jobOp, $jobInputs, $jobOutput, $histStatus);
    $jobOp = null; // prevent double-write if somehow rendered twice before cleanup
}

// Clean up job sidecar files once we've reached a terminal state
if ($done || $failed || $cancelled) {
    $base = __DIR__ . '/uploads/' . $jobId;
    foreach (['.log', '.pid', '_concat.txt', '_params.json'] as $ext) {
        $f = $base . $ext;
        if (file_exists($f)) unlink($f);
    }
    // fmt_ files (yt-dlp format cache)
    foreach (glob(__DIR__ . '/uploads/fmt_' . $jobId . '*') ?: [] as $f) unlink($f);
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Cuts – Processing</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <?php if (!$done && !$failed && !$cancelled): ?>
    <meta http-equiv="refresh" content="3">
    <?php endif; ?>
      <?php include 'darkHead.php'; ?>
  </head>
  <body>
    <div class="w3-container w3-padding-24">
      <h1>Cuts</h1>
      <div class="w3-card w3-padding">

        <?php if ($done && $resultFile): ?>
          <h2 class="w3-text-green">Done</h2>
          <?php if ($isAudio): ?>
            <audio controls class="w3-margin-top" style="width:100%">
              <source src="<?= htmlspecialchars($resultFile) ?>">
            </audio>
          <?php else: ?>
            <video controls preload="metadata" class="w3-margin-top" style="width:100%;max-width:100%">
              <source src="<?= htmlspecialchars($resultFile) ?>">
            </video>
            <a href="<?= htmlspecialchars($resultFile) ?>" download
               class="w3-button w3-dark-grey w3-small w3-round w3-margin-top" style="display:inline-block">
              Download
            </a>
          <?php endif; ?>
          <p class="w3-small w3-text-grey w3-margin-top"><?= htmlspecialchars(basename($resultFile)) ?></p>

        <?php elseif ($cancelled): ?>
          <h2 class="w3-text-orange">Cancelled</h2>
          <p class="w3-text-grey">The job was stopped.</p>

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
          <?php if ($totalDuration > 0): ?>
            <?php $pct = min(100, $totalDuration > 0 ? (int)round($currentSec / $totalDuration * 100) : 0); ?>
            <div class="w3-light-grey w3-round w3-margin-top" style="height:22px">
              <div class="w3-blue w3-round" style="height:22px;width:<?= $pct ?>%;transition:width 0.5s"></div>
            </div>
            <p class="w3-small w3-text-grey w3-margin-top"><?= $pct ?>% &nbsp;·&nbsp; refreshes every 3 seconds</p>
          <?php else: ?>
            <p class="w3-text-grey">Page refreshes every 3 seconds.</p>
          <?php endif; ?>
          <form method="post" action="cancelJob.php" class="w3-margin-top">
            <input type="hidden" name="job" value="<?= htmlspecialchars($jobId) ?>">
            <button type="submit" class="w3-button w3-red w3-round w3-small">Cancel</button>
          </form>
        <?php endif; ?>

        <?php if (!empty($showLines)): ?>
          <pre style="background:#111;color:#ddd;padding:12px;overflow:auto;max-height:460px;font-size:12px;margin-top:16px"><?= htmlspecialchars(implode("\n", $showLines)) ?></pre>
        <?php elseif (!$done && !$failed && !$cancelled && !file_exists($logFile)): ?>
          <p class="w3-text-grey w3-small w3-margin-top">Job not found — it may have already completed.</p>
        <?php elseif (!$done && !$failed): ?>
          <p class="w3-text-grey w3-small w3-margin-top">Waiting for output…</p>
        <?php endif; ?>

        <?php include 'backToHomeButton.php'; ?>
      </div>
    </div>
  </body>
</html>
