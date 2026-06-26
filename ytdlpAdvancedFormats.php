<?php

$jobId      = basename($_GET['job'] ?? '');
$youtubeUrl = $_GET['url'] ?? '';

if (!$jobId || !$youtubeUrl) {
    header('Location: youtube.php');
    exit;
}

$jsonFile = __DIR__ . '/uploads/' . $jobId . '.json';
$logFile  = __DIR__ . '/uploads/' . $jobId . '.log';

$data   = null;
$ready  = false;
$failed = false;

if (file_exists($jsonFile) && filesize($jsonFile) > 0) {
    $decoded = json_decode(file_get_contents($jsonFile), true);
    if ($decoded && isset($decoded['formats'])) {
        $data  = $decoded;
        $ready = true;
    } else {
        // File exists but isn't valid — yt-dlp may have errored
        $failed = true;
    }
}

// ── WAITING ──────────────────────────────────────────────────
if (!$ready && !$failed) {
    $tail = '';
    if (file_exists($logFile)) {
        $lines = array_filter(explode("\n", file_get_contents($logFile)));
        $tail  = htmlspecialchars(implode("\n", array_slice($lines, -5)));
    }
    ?>
    <!DOCTYPE html>
    <html lang="en" dir="ltr">
      <head>
        <meta charset="utf-8">
        <title>Cuts – Fetching formats</title>
        <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
        <?php include 'darkHead.php'; ?>
        <meta http-equiv="refresh" content="2">
      </head>
      <body>
        <div class="w3-container w3-padding-24">
          <h1>Cuts</h1>
          <div class="w3-card w3-padding w3-teal" style="max-width:640px">
            <h2 class="w3-monospace">Fetching formats…</h2>
            <?php
              $started = file_exists($logFile) ? filemtime($logFile) : time();
              $elapsed = time() - $started;
            ?>
            <p>Talking to YouTube — page refreshes every 2 seconds. <span style="opacity:0.8">(<?= $elapsed ?>s elapsed)</span></p>
            <pre style="background:#0d3d38;color:#b2dfdb;padding:10px;font-size:12px;min-height:60px;white-space:pre-wrap;word-break:break-word;overflow:hidden"><?= $tail ?: 'yt-dlp running…' ?></pre>
            <?php include 'backToHomeButton.php'; ?>
          </div>
        </div>
      </body>
    </html>
    <?php
    exit;
}

// ── FAILED ───────────────────────────────────────────────────
if ($failed) {
    $log = file_exists($logFile) ? htmlspecialchars(file_get_contents($logFile)) : '';
    ?>
    <!DOCTYPE html>
    <html lang="en" dir="ltr">
      <head>
        <meta charset="utf-8">
        <title>Cuts – Error</title>
        <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
        <?php include 'darkHead.php'; ?>
      </head>
      <body>
        <div class="w3-container w3-padding-24">
          <h1>Cuts</h1>
          <div class="w3-card w3-padding" style="max-width:640px">
            <div class="w3-panel w3-red">Could not fetch video info. Check the URL and try again.</div>
            <?php if ($log): ?>
              <pre style="background:#111;color:#ddd;padding:10px;font-size:12px;overflow:auto;max-height:300px"><?= $log ?></pre>
            <?php endif; ?>
            <?php include 'backToHomeButton.php'; ?>
          </div>
        </div>
      </body>
    </html>
    <?php
    exit;
}

// ── FORMAT PICKER ─────────────────────────────────────────────
// Clean up job files — data is parsed, no longer needed
if (file_exists($jsonFile)) unlink($jsonFile);
if (file_exists($logFile))  unlink($logFile);

$title           = $data['title'] ?? '';
$videoFormats    = [];
$audioFormats    = [];
$combinedFormats = [];
$limitedFormats  = false;

foreach ($data['formats'] as $f) {
    $vcodec = $f['vcodec'] ?? 'none';
    $acodec = $f['acodec'] ?? 'none';
    $ext    = $f['ext']    ?? '';
    if ($ext === 'mhtml') continue;
    if ($vcodec !== 'none' && $acodec === 'none')         $videoFormats[]    = $f;
    elseif ($vcodec === 'none' && $acodec !== 'none')     $audioFormats[]    = $f;
    elseif ($vcodec !== 'none' && $acodec !== 'none')     $combinedFormats[] = $f;
}

usort($videoFormats,    fn($a,$b) => ($b['height'] ?? 0)  - ($a['height'] ?? 0));
usort($audioFormats,    fn($a,$b) => ($b['tbr']    ?? 0)  - ($a['tbr']    ?? 0));
usort($combinedFormats, fn($a,$b) => ($b['height'] ?? 0)  - ($a['height'] ?? 0));

if (empty($videoFormats) && empty($audioFormats)) $limitedFormats = true;

function fmtSize($f) {
    $bytes = $f['filesize'] ?? $f['filesize_approx'] ?? null;
    if ($bytes === null) return '?';
    return (isset($f['filesize']) ? '' : '~') . round($bytes / 1048576, 1) . ' MB';
}
function shortCodec($codec) {
    return htmlspecialchars(explode('.', $codec ?? '?')[0]);
}

?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Cuts – Choose format</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <style>
      table input[type=radio] { cursor: pointer; }
      tbody tr:hover { background: #e8f5e9; cursor: pointer; }
      html.dark tbody tr:hover { background: #333; }
    </style>
    <?php include 'darkHead.php'; ?>
  </head>
  <body>
    <div class="w3-container w3-padding-24">
      <h1>Cuts</h1>

      <?php if ($limitedFormats && empty($combinedFormats)): ?>
        <div class="w3-panel w3-red">No downloadable formats found for this video.</div>
        <?php include 'backToHomeButton.php'; ?>
      <?php else: ?>

      <h2 class="w3-monospace"><?= htmlspecialchars($title) ?></h2>

      <?php if ($limitedFormats): ?>
      <div class="w3-panel w3-amber">
        <b>Limited formats available.</b> Only combined streams found — yt-dlp may need updating.
      </div>
      <?php endif; ?>

      <form action="ytdlpAdvancedDownload.php" method="post">
        <input type="hidden" name="youtubeUrl"  value="<?= htmlspecialchars($youtubeUrl) ?>">
        <input type="hidden" name="format_mode" value="<?= $limitedFormats ? 'combined' : 'separate' ?>">

        <?php if ($limitedFormats): ?>
        <div class="w3-card w3-padding w3-margin-bottom">
          <h3>Available formats</h3>
          <table class="w3-table w3-striped w3-bordered w3-small">
            <thead><tr class="w3-teal"><th></th><th>Resolution</th><th>FPS</th><th>Video</th><th>Audio</th><th>Size</th></tr></thead>
            <tbody>
              <?php foreach ($combinedFormats as $i => $f): ?>
              <tr onclick="this.querySelector('input').click()">
                <td><input type="radio" name="vid_format" value="<?= htmlspecialchars($f['format_id']) ?>" <?= $i===0?'checked':'' ?>></td>
                <td><?= isset($f['height']) ? $f['height'].'p' : '?' ?></td>
                <td><?= isset($f['fps']) ? round($f['fps']) : '?' ?></td>
                <td><?= shortCodec($f['vcodec']??'') ?></td>
                <td><?= shortCodec($f['acodec']??'') ?></td>
                <td><?= fmtSize($f) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <?php else: ?>
        <p>Pick one video stream and one audio stream — yt-dlp will merge them.</p>
        <div class="w3-row-padding">
          <div class="w3-half">
            <div class="w3-card w3-padding w3-margin-bottom">
              <h3>Video stream</h3>
              <table class="w3-table w3-striped w3-bordered w3-small">
                <thead><tr class="w3-teal"><th></th><th>Resolution</th><th>FPS</th><th>Codec</th><th>Size</th></tr></thead>
                <tbody>
                  <?php foreach ($videoFormats as $i => $f): ?>
                  <tr onclick="this.querySelector('input').click()">
                    <td><input type="radio" name="vid_format" value="<?= htmlspecialchars($f['format_id']) ?>" <?= $i===0?'checked':'' ?>></td>
                    <td><?= isset($f['height']) ? $f['height'].'p' : '?' ?></td>
                    <td><?= isset($f['fps']) ? round($f['fps']) : '?' ?></td>
                    <td><?= shortCodec($f['vcodec']??'') ?></td>
                    <td><?= fmtSize($f) ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
          <div class="w3-half">
            <div class="w3-card w3-padding w3-margin-bottom">
              <h3>Audio stream</h3>
              <table class="w3-table w3-striped w3-bordered w3-small">
                <thead><tr class="w3-teal"><th></th><th>Bitrate</th><th>Codec</th><th>Size</th></tr></thead>
                <tbody>
                  <?php foreach ($audioFormats as $i => $f): ?>
                  <tr onclick="this.querySelector('input').click()">
                    <td><input type="radio" name="aud_format" value="<?= htmlspecialchars($f['format_id']) ?>" <?= $i===0?'checked':'' ?>></td>
                    <td><?= isset($f['tbr']) ? round($f['tbr']).'k' : '?' ?></td>
                    <td><?= shortCodec($f['acodec']??'') ?></td>
                    <td><?= fmtSize($f) ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <div class="w3-card w3-padding w3-margin-bottom">
          <h3>Subtitles</h3>
          <label><input type="checkbox" name="subs" id="subs_check"> Include subtitles</label>
          <div id="subs_options" class="w3-margin-top" style="display:none">
            <label class="w3-margin-right">
              <input type="radio" name="subs_mode" value="soft" checked>
              <strong>Soft</strong> — embedded track
            </label>
            <label>
              <input type="radio" name="subs_mode" value="hard">
              <strong>Hard</strong> — burned in
            </label>
            <div class="w3-margin-top">
              <label>Language</label>
              <input class="w3-input w3-border" type="text" name="subs_lang" value="en"
                     placeholder="en, es, fr…" style="max-width:120px;display:inline-block;margin-left:8px">
            </div>
          </div>
        </div>

        <input class="w3-button w3-green w3-large w3-margin-bottom" type="submit" value="Download">
      </form>

      <?php include 'backToHomeButton.php'; ?>
      <?php endif; ?>
    </div>

    <script>
      document.getElementById('subs_check').addEventListener('change', function() {
        document.getElementById('subs_options').style.display = this.checked ? 'block' : 'none';
      });
    </script>
  </body>
</html>
