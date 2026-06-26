<?php

$youtubeUrl = $_POST['youtubeUrl'] ?? '';
if (!$youtubeUrl) {
    header('Location: ytdlpAdvanced.php');
    exit;
}

$ytdlp = '/usr/local/bin/yt-dlp';
$json  = shell_exec($ytdlp . ' -J --no-download ' . escapeshellarg($youtubeUrl) . ' 2>/dev/null');
$data  = json_decode($json, true);

$error           = null;
$videoFormats    = [];
$audioFormats    = [];
$combinedFormats = [];
$title           = '';
$limitedFormats  = false;

if (!$data || !isset($data['formats'])) {
    $error = 'Could not fetch video info. Check the URL and try again.';
} else {
    $title = $data['title'] ?? '';

    foreach ($data['formats'] as $f) {
        $vcodec = $f['vcodec'] ?? 'none';
        $acodec = $f['acodec'] ?? 'none';
        $ext    = $f['ext']    ?? '';

        if ($ext === 'mhtml') continue; // skip storyboards

        if ($vcodec !== 'none' && $acodec === 'none') {
            $videoFormats[] = $f;
        } elseif ($vcodec === 'none' && $acodec !== 'none') {
            $audioFormats[] = $f;
        } elseif ($vcodec !== 'none' && $acodec !== 'none') {
            $combinedFormats[] = $f;
        }
    }

    usort($videoFormats, function ($a, $b) {
        return ($b['height'] ?? 0) - ($a['height'] ?? 0);
    });
    usort($audioFormats, function ($a, $b) {
        return ($b['tbr'] ?? 0) - ($a['tbr'] ?? 0);
    });
    usort($combinedFormats, function ($a, $b) {
        return ($b['height'] ?? 0) - ($a['height'] ?? 0);
    });

    // If no separate streams, we're in limited mode (no JS runtime / outdated yt-dlp)
    if (empty($videoFormats) && empty($audioFormats)) {
        $limitedFormats = true;
    }
}

function fmtSize($f) {
    $bytes = $f['filesize'] ?? $f['filesize_approx'] ?? null;
    if ($bytes === null) return '?';
    $approx = isset($f['filesize']) ? '' : '~';
    return $approx . round($bytes / 1048576, 1) . ' MB';
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
    </style>
  </head>
  <body>
    <div class="w3-container">
      <h1>Cuts</h1>

      <?php if ($error): ?>
        <div class="w3-panel w3-red"><?= htmlspecialchars($error) ?></div>
        <?php include 'backToHomeButton.php'; ?>

      <?php elseif ($limitedFormats && empty($combinedFormats)): ?>
        <div class="w3-panel w3-red">No downloadable formats found for this video.</div>
        <?php include 'backToHomeButton.php'; ?>

      <?php else: ?>

      <h2 class="w3-monospace"><?= htmlspecialchars($title) ?></h2>

      <?php if ($limitedFormats): ?>
      <div class="w3-panel w3-amber">
        <b>Limited formats available.</b> yt-dlp couldn't see separate video/audio streams —
        this usually means it needs to be updated or a JS runtime (deno) needs to be installed on the server.
        Only combined formats are shown below.
      </div>
      <?php endif; ?>

      <form action="ytdlpAdvancedDownload.php" method="post">
        <input type="hidden" name="youtubeUrl" value="<?= htmlspecialchars($youtubeUrl) ?>">
        <input type="hidden" name="format_mode" value="<?= $limitedFormats ? 'combined' : 'separate' ?>">

        <?php if ($limitedFormats): ?>

        <!-- Combined format picker (fallback) -->
        <div class="w3-card w3-padding w3-margin-bottom">
          <h3>Available formats</h3>
          <p class="w3-small">These are pre-merged streams. Pick the quality you want.</p>
          <table class="w3-table w3-striped w3-bordered w3-small">
            <thead>
              <tr class="w3-teal">
                <th></th>
                <th>Resolution</th>
                <th>FPS</th>
                <th>Video codec</th>
                <th>Audio codec</th>
                <th>Size</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($combinedFormats as $i => $f): ?>
              <tr onclick="this.querySelector('input').click()">
                <td><input type="radio" name="vid_format" value="<?= htmlspecialchars($f['format_id']) ?>" <?= $i === 0 ? 'checked' : '' ?>></td>
                <td><?= isset($f['height']) ? $f['height'] . 'p' : '?' ?></td>
                <td><?= isset($f['fps']) ? round($f['fps']) : '?' ?></td>
                <td><?= shortCodec($f['vcodec'] ?? '') ?></td>
                <td><?= shortCodec($f['acodec'] ?? '') ?></td>
                <td><?= fmtSize($f) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <?php else: ?>

        <!-- Separate stream picker -->
        <p>Pick one video stream and one audio stream — yt-dlp will merge them.</p>
        <div class="w3-row-padding">

          <div class="w3-half">
            <div class="w3-card w3-padding w3-margin-bottom">
              <h3>Video stream</h3>
              <table class="w3-table w3-striped w3-bordered w3-small">
                <thead>
                  <tr class="w3-teal">
                    <th></th>
                    <th>Resolution</th>
                    <th>FPS</th>
                    <th>Codec</th>
                    <th>Size</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($videoFormats as $i => $f): ?>
                  <tr onclick="this.querySelector('input').click()">
                    <td><input type="radio" name="vid_format" value="<?= htmlspecialchars($f['format_id']) ?>" <?= $i === 0 ? 'checked' : '' ?>></td>
                    <td><?= isset($f['height']) ? $f['height'] . 'p' : '?' ?></td>
                    <td><?= isset($f['fps']) ? round($f['fps']) : '?' ?></td>
                    <td><?= shortCodec($f['vcodec'] ?? '') ?></td>
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
                <thead>
                  <tr class="w3-teal">
                    <th></th>
                    <th>Bitrate</th>
                    <th>Codec</th>
                    <th>Size</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($audioFormats as $i => $f): ?>
                  <tr onclick="this.querySelector('input').click()">
                    <td><input type="radio" name="aud_format" value="<?= htmlspecialchars($f['format_id']) ?>" <?= $i === 0 ? 'checked' : '' ?>></td>
                    <td><?= isset($f['tbr']) ? round($f['tbr']) . 'k' : '?' ?></td>
                    <td><?= shortCodec($f['acodec'] ?? '') ?></td>
                    <td><?= fmtSize($f) ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>

        </div>
        <?php endif; ?>

        <!-- Subtitle options -->
        <div class="w3-card w3-padding w3-margin-bottom">
          <h3>Subtitles</h3>
          <label>
            <input type="checkbox" name="subs" id="subs_check">
            Include subtitles
          </label>

          <div id="subs_options" class="w3-margin-top" style="display:none">
            <label class="w3-margin-right">
              <input type="radio" name="subs_mode" value="soft" checked>
              <strong>Soft</strong> — embedded track (can be toggled in player)
            </label>
            <label>
              <input type="radio" name="subs_mode" value="hard">
              <strong>Hard</strong> — burned into video (always visible)
            </label>
            <div class="w3-margin-top">
              <label>Language code</label>
              <input class="w3-input w3-border" type="text" name="subs_lang" value="en"
                     placeholder="e.g. en, es, fr" style="max-width:150px; display:inline-block; margin-left:8px">
            </div>
          </div>
        </div>

        <input class="w3-button w3-green w3-large w3-margin-bottom" type="submit" value="Download">
      </form>

      <?php include 'backToHomeButton.php'; ?>
      <?php endif; ?>

    </div>

    <script>
      document.getElementById('subs_check').addEventListener('change', function () {
        document.getElementById('subs_options').style.display = this.checked ? 'block' : 'none';
      });
    </script>
  </body>
</html>
