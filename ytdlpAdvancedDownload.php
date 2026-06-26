<?php

set_time_limit(0);

$youtubeUrl  = $_POST['youtubeUrl'] ?? '';
$vidFormat   = $_POST['vid_format'] ?? '';
$audFormat   = $_POST['aud_format'] ?? '';
$formatMode  = ($_POST['format_mode'] ?? 'separate') === 'combined' ? 'combined' : 'separate';
$subsEnabled = isset($_POST['subs']);
$subsMode    = ($_POST['subs_mode'] ?? 'soft') === 'hard' ? 'hard' : 'soft';
$subsLang    = $_POST['subs_lang'] ?? 'en';

// In separate mode both streams are required; in combined mode only vid_format is used
$vidOk = preg_match('/^\d+$/', $vidFormat);
$audOk = $formatMode === 'combined' || preg_match('/^\d+$/', $audFormat);

if (!$youtubeUrl || !$vidOk || !$audOk) {
    die('<div class="w3-panel w3-red">Invalid input.</div>');
}
if (!preg_match('/^[a-z]{2,10}$/', $subsLang)) {
    $subsLang = 'en';
}

$ytdlp  = '/usr/local/bin/yt-dlp';
$ffmpeg = '/usr/bin/ffmpeg';
$base   = 'ytdlp_' . time();

$formatArg = ($formatMode === 'combined')
    ? $vidFormat
    : $vidFormat . '+' . $audFormat;

$cmd = $ytdlp
    . ' -f ' . escapeshellarg($formatArg)
    . ' --merge-output-format mp4'
    . ' -o ' . escapeshellarg('uploads/' . $base . '.%(ext)s');

if ($subsEnabled) {
    $cmd .= ' --write-subs'
         .  ' --sub-langs ' . escapeshellarg($subsLang)
         .  ' --sub-format "srt/vtt/best"';
    if ($subsMode === 'soft') {
        $cmd .= ' --embed-subs';
    }
}

$cmd .= ' ' . escapeshellarg($youtubeUrl);

shell_exec($cmd . ' 2>&1');

$outputFile = 'uploads/' . $base . '.mp4';
$burnFailed = false;
$subPattern = 'uploads/' . $base . '.' . $subsLang . '.*';

if ($subsEnabled && $subsMode === 'soft' && file_exists($outputFile)) {
    // Subs are embedded — remove the loose subtitle file
    foreach (glob($subPattern) as $sf) unlink($sf);
}

if ($subsEnabled && $subsMode === 'hard' && file_exists($outputFile)) {
    $subFiles = glob($subPattern);
    if (!empty($subFiles)) {
        $subFile    = $subFiles[0];
        $burnedFile = 'uploads/' . $base . '_burned.mp4';
        shell_exec(
            $ffmpeg
            . ' -i '  . escapeshellarg($outputFile)
            . ' -vf subtitles=' . escapeshellarg($subFile)
            . ' -c:a copy '
            . escapeshellarg($burnedFile)
            . ' 2>&1'
        );
        if (file_exists($burnedFile)) {
            $outputFile = $burnedFile;
            // Clean up intermediate unburned mp4 and loose srt
            unlink('uploads/' . $base . '.mp4');
            foreach ($subFiles as $sf) unlink($sf);
        } else {
            $burnFailed = true;
        }
    } else {
        $burnFailed = true;
    }
}

?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Cuts – Download</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
  </head>
  <body>
    <div class="w3-container">
      <h1>Cuts</h1>
      <div class="w3-container w3-card w3-teal w3-half">
        <h2 class="w3-monospace">Advanced YouTube download</h2>

        <?php if (!file_exists($outputFile)): ?>
          <div class="w3-panel w3-red">Download failed. The URL may be unsupported or the format combination unavailable.</div>
        <?php else: ?>
          <?php if ($burnFailed): ?>
            <div class="w3-panel w3-yellow">Downloaded OK, but subtitle burn-in failed. Playing without burned subs.</div>
          <?php endif; ?>
          <video width="480" autoplay controls class="w3-margin-top">
            <source src="<?= htmlspecialchars($outputFile) ?>">
          </video>
        <?php endif; ?>

        <?php include 'videoFolder.php'; ?>
        <?php include 'backToHomeButton.php'; ?>
      </div>
    </div>
  </body>
</html>
