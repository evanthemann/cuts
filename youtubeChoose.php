<?php
$jobId      = basename($_GET['job'] ?? '');
$youtubeUrl = $_GET['url'] ?? '';
if (!$jobId || !$youtubeUrl) { header('Location: youtube.php'); exit; }

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
        $failed = true;
    }
}

if ($ready) {
    $title           = $data['title'] ?? '';
    $thumbnail       = $data['thumbnail'] ?? '';
    $videoFormats    = [];
    $audioFormats    = [];
    $combinedFormats = [];
    $limitedFormats  = false;

    foreach ($data['formats'] as $f) {
        $vcodec = $f['vcodec'] ?? 'none';
        $acodec = $f['acodec'] ?? 'none';
        if (($f['ext'] ?? '') === 'mhtml') continue;
        if ($vcodec !== 'none' && $acodec === 'none')     $videoFormats[]    = $f;
        elseif ($vcodec === 'none' && $acodec !== 'none') $audioFormats[]    = $f;
        elseif ($vcodec !== 'none' && $acodec !== 'none') $combinedFormats[] = $f;
    }
    usort($videoFormats,    fn($a,$b) => ($b['height'] ?? 0) - ($a['height'] ?? 0));
    usort($audioFormats,    fn($a,$b) => ($b['tbr']    ?? 0) - ($a['tbr']    ?? 0));
    usort($combinedFormats, fn($a,$b) => ($b['height'] ?? 0) - ($a['height'] ?? 0));
    if (empty($videoFormats) && empty($audioFormats)) $limitedFormats = true;

    // Build subtitle track list: manual first, then auto-generated
    $subTracks = [];
    foreach ($data['subtitles'] ?? [] as $lang => $tracks) {
        $subTracks[$lang] = $lang . ' (manual)';
    }
    foreach ($data['automatic_captions'] ?? [] as $lang => $tracks) {
        if (!isset($subTracks[$lang])) {
            $subTracks[$lang] = $lang . ' (auto)';
        }
    }
    ksort($subTracks);
    $subDefault = isset($subTracks['en']) ? 'en' : (isset($subTracks['en-orig']) ? 'en-orig' : array_key_first($subTracks));

    unlink($jsonFile);
    if (file_exists($logFile)) unlink($logFile);
} else {
    $title      = '';
    $subTracks  = [];
    $subDefault = 'en';
    $tail       = '';
    if (file_exists($logFile)) {
        $lines = array_filter(explode("\n", file_get_contents($logFile)));
        $tail  = htmlspecialchars(implode("\n", array_slice($lines, -8)));
    }
    $elapsed = file_exists($logFile) ? (time() - filemtime($logFile)) : 0;
}

function fmtSize($f) {
    $bytes = $f['filesize'] ?? $f['filesize_approx'] ?? null;
    if ($bytes === null) return '?';
    return (isset($f['filesize']) ? '' : '~') . round($bytes / 1048576, 1) . ' MB';
}
function shortCodec($c) { return htmlspecialchars(explode('.', $c ?? '?')[0]); }
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Cuts – YouTube</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <style>
      table input[type=radio] { cursor:pointer; }
      tbody tr:hover { background:#e8f5e9; cursor:pointer; }
      html.dark tbody tr:hover { background:#333; }
    </style>
    <?php include 'darkHead.php'; ?>
    <?php if (!$ready && !$failed): ?>
    <meta http-equiv="refresh" content="2; url=youtubeChoose.php?job=<?= urlencode($jobId) ?>&url=<?= urlencode($youtubeUrl) ?>">
    <?php endif; ?>
  </head>
  <body>
    <div class="w3-container w3-padding-24">
      <h1>Cuts</h1>
      <div style="max-width:820px">

        <?php if (!$ready && !$failed): ?>
        <!-- ── LOADING STATE ──────────────────────────────── -->
        <div class="w3-card w3-padding w3-margin-bottom">
          <h3 style="margin-top:0">Fetching formats… <span class="w3-text-grey w3-small"><?= $elapsed ?>s</span></h3>
          <p class="w3-small w3-text-grey" style="margin:0 0 8px;word-break:break-all"><?= htmlspecialchars($youtubeUrl) ?></p>
          <pre style="background:#111;color:#ddd;padding:10px;font-size:12px;min-height:80px;white-space:pre-wrap;word-break:break-word;margin:0"><?= $tail ?: 'Starting…' ?></pre>
        </div>

        <?php elseif ($failed): ?>
        <!-- ── FAILED ─────────────────────────────────────── -->
        <div class="w3-panel w3-red">Could not fetch format list for this URL.</div>

        <?php else: ?>
        <!-- ── READY ──────────────────────────────────────── -->

        <div style="display:flex;gap:16px;align-items:flex-start;margin-bottom:16px">
          <?php if ($thumbnail): ?>
            <img src="<?= htmlspecialchars($thumbnail) ?>" alt="" style="width:160px;height:90px;object-fit:cover;border-radius:4px;flex-shrink:0">
          <?php endif; ?>
          <?php if ($title): ?>
            <h3 style="margin:0;align-self:center"><?= htmlspecialchars($title) ?></h3>
          <?php endif; ?>
        </div>

        <!-- Quick download -->
        <div class="w3-card w3-padding w3-teal w3-margin-bottom" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
          <div>
            <b style="font-size:16px">Quick download</b>
            <span class="w3-small" style="margin-left:10px">Best available quality · MP4</span>
          </div>
          <form action="quickDownload.php" method="post">
            <input type="hidden" name="youtubeUrl" value="<?= htmlspecialchars($youtubeUrl) ?>">
            <button type="submit" class="w3-button w3-white w3-text-teal w3-round">Download now →</button>
          </form>
        </div>

        <!-- Format picker -->
        <div class="w3-card w3-padding w3-margin-bottom">
          <h4 style="margin-top:0">Custom quality</h4>

          <form action="ytdlpAdvancedDownload.php" method="post">
            <input type="hidden" name="youtubeUrl"  value="<?= htmlspecialchars($youtubeUrl) ?>">
            <input type="hidden" name="format_mode" value="<?= $limitedFormats ? 'combined' : 'separate' ?>">

            <?php if ($limitedFormats && empty($combinedFormats)): ?>
              <div class="w3-panel w3-red">No downloadable formats found.</div>

            <?php elseif ($limitedFormats): ?>
              <div class="w3-panel w3-amber w3-small w3-margin-bottom"><b>Limited formats</b> — only combined streams available.</div>
              <table class="w3-table w3-striped w3-bordered w3-small w3-margin-bottom">
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

            <?php else: ?>
              <div class="w3-row-padding w3-margin-bottom">
                <div class="w3-half">
                  <h5 style="margin-top:0">Video stream</h5>
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
                <div class="w3-half">
                  <h5 style="margin-top:0">Audio stream</h5>
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
            <?php endif; ?>

            <!-- Subtitles -->
            <div class="w3-card w3-padding w3-margin-bottom">
              <b>Subtitles</b>
              <?php if (empty($subTracks)): ?>
                <span class="w3-small w3-text-grey" style="margin-left:12px">None available for this video</span>
              <?php else: ?>
                <label style="margin-left:12px"><input type="checkbox" name="subs" id="subs_check"> Include subtitles</label>
                <div id="subs_options" style="display:none;margin-top:10px">
                  <label class="w3-margin-right"><input type="radio" name="subs_mode" value="soft" checked> <b>Soft</b> — embedded track</label>
                  <label><input type="radio" name="subs_mode" value="hard"> <b>Hard</b> — burned in</label>
                  <div style="margin-top:8px">
                    <label>Track:
                      <select name="subs_lang" class="w3-select w3-border" style="max-width:260px;display:inline-block;margin-left:8px">
                        <?php foreach ($subTracks as $code => $label): ?>
                          <option value="<?= htmlspecialchars($code) ?>" <?= $code === $subDefault ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </label>
                  </div>
                </div>
              <?php endif; ?>
            </div>

            <button type="submit" class="w3-button w3-teal w3-round">Download with selected settings</button>
          </form>
        </div>

        <?php endif; ?>

        <?php include 'backToHomeButton.php'; ?>
      </div>
    </div>

    <script>
      var subsCheck = document.getElementById('subs_check');
      if (subsCheck) {
        subsCheck.addEventListener('change', function() {
          document.getElementById('subs_options').style.display = this.checked ? 'block' : 'none';
        });
      }
    </script>
  </body>
</html>
