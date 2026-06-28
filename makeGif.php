<?php
$ffprobe  = '/usr/bin/ffprobe';
$preselect = basename($_GET['file'] ?? '');

$allVideo = glob('uploads/*.{mp4,mkv,mov,avi,webm}', GLOB_BRACE) ?: [];

// Keep only video files ≤ 7s
$eligible = [];
foreach ($allVideo as $f) {
    $dOut = shell_exec($ffprobe . ' -v error -show_entries format=duration -of default=noprint_wrappers=1 ' . escapeshellarg($f) . ' 2>/dev/null');
    if (preg_match('/duration=([\d.]+)/', $dOut ?? '', $m) && (float)$m[1] <= 10.0) {
        $eligible[] = $f;
    }
}

// Validate preselect is in eligible list
if ($preselect && !in_array('uploads/' . $preselect, $eligible, true)) {
    $preselect = '';
}

function gifFileOpts($files, $preselect) {
    $out = '<option value="">— choose a file —</option>';
    foreach ($files as $f) {
        $name = basename($f);
        $sel  = $name === $preselect ? ' selected' : '';
        $out .= '<option value="' . htmlspecialchars($name, ENT_QUOTES) . '"' . $sel . '>' . htmlspecialchars($name) . '</option>';
    }
    return $out;
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Cuts – Make GIF</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <?php include 'darkHead.php'; ?>
  </head>
  <body>
    <div class="w3-container w3-padding-24">
      <h1>Cuts</h1>
      <div class="w3-card w3-teal" style="max-width:560px">
        <div class="w3-padding-16 w3-padding-left w3-padding-right">
          <h3 style="margin-top:0">Make GIF</h3>
          <p class="w3-small" style="margin-top:-8px;opacity:.85">Export a short video clip (≤ 10 s) as an animated GIF. Output: 480 px wide, 15 fps.</p>
        </div>

        <div class="w3-padding">
          <?php if (empty($eligible)): ?>
            <p class="w3-text-white">No eligible clips found. Upload a video that is 10 seconds or shorter.</p>
          <?php else: ?>
          <form action="makeGifResult.php" method="post">

            <label class="w3-text-white"><b>File</b></label>
            <select name="filename" id="g-file" class="w3-select w3-border w3-margin-bottom"
              onchange="loadMedia(this.value)">
              <?= gifFileOpts($eligible, $preselect) ?>
            </select>

            <div id="g-scrub" style="display:none;margin-bottom:16px">
              <video id="g-media" preload="metadata"
                style="width:100%;border-radius:4px;background:#000;display:block;margin-bottom:8px"></video>
              <input id="g-scrubber" type="range" min="0" max="100" step="0.033" value="0"
                style="width:100%;margin-bottom:8px" oninput="onScrub(this.value)">
              <div class="w3-margin-bottom" style="display:flex;gap:6px;flex-wrap:wrap">
                <button type="button" onclick="nudge(-30)" class="w3-button w3-small w3-border">−30f</button>
                <button type="button" onclick="nudge(-1)"  class="w3-button w3-small w3-border">−1f</button>
                <button type="button" id="g-play-btn" onclick="togglePlay()" class="w3-button w3-small w3-border">Play</button>
                <button type="button" onclick="nudge(1)"   class="w3-button w3-small w3-border">+1f</button>
                <button type="button" onclick="nudge(30)"  class="w3-button w3-small w3-border">+30f</button>
              </div>
              <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                <input id="g-time" type="text" readonly value="00:00:00.000"
                  style="width:126px;font-family:monospace;font-size:14px;background:#222;color:#fff;border:1px solid #555;padding:4px 8px;border-radius:4px">
                <button type="button" onclick="setPoint('start')" class="w3-button w3-small w3-border">→ Start</button>
                <button type="button" onclick="setPoint('end')"   class="w3-button w3-small w3-border">→ End</button>
              </div>
            </div>

            <label class="w3-text-white"><b>Start</b> <span class="w3-small" style="font-weight:normal;opacity:.75">(optional, seconds)</span></label>
            <input id="g-start" class="w3-input w3-border w3-margin-bottom" type="number" step="0.001" min="0" name="startSeconds" placeholder="leave blank for beginning">
            <label class="w3-text-white"><b>End</b> <span class="w3-small" style="font-weight:normal;opacity:.75">(optional, seconds)</span></label>
            <input id="g-end" class="w3-input w3-border w3-margin-bottom" type="number" step="0.001" min="0" name="endSeconds" placeholder="leave blank for end of clip">

            <button type="submit" class="w3-button w3-white w3-text-teal w3-round">Make GIF</button>
          </form>
          <?php endif; ?>
        </div>
      </div>
      <div style="margin-top:16px"><?php include 'backToHomeButton.php'; ?></div>
    </div>

  <script>
  var FRAME = 1 / 30;
  var media    = document.getElementById('g-media');
  var scrubber = document.getElementById('g-scrubber');
  var timeBox  = document.getElementById('g-time');
  var playBtn  = document.getElementById('g-play-btn');
  var scrubSec = document.getElementById('g-scrub');
  var startFld = document.getElementById('g-start');
  var endFld   = document.getElementById('g-end');

  if (media) {
    media.addEventListener('loadedmetadata', function() {
      scrubber.max   = media.duration;
      scrubber.value = 0;
      timeBox.value  = fmtTime(0);
      scrubSec.style.display = 'block';
    });
    media.addEventListener('seeked',     function() { syncUI(); });
    media.addEventListener('timeupdate', function() { syncUI(); });
    media.addEventListener('play',  function() { playBtn.textContent = 'Pause'; });
    media.addEventListener('pause', function() { playBtn.textContent = 'Play'; });
  }

  function syncUI()            { scrubber.value = media.currentTime; timeBox.value = fmtTime(media.currentTime); }
  function loadMedia(filename) {
    if (!filename) { scrubSec.style.display = 'none'; return; }
    media.src = 'uploads/' + encodeURIComponent(filename);
    media.load();
  }
  function onScrub(val)        { media.currentTime = parseFloat(val); }
  function nudge(frames)       { media.currentTime = Math.max(0, Math.min(media.duration, media.currentTime + frames * FRAME)); }
  function togglePlay()        { media.paused ? media.play() : media.pause(); }
  function setPoint(which)     { (which === 'start' ? startFld : endFld).value = parseFloat(media.currentTime).toFixed(3); }

  function fmtTime(s) {
    var h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60), sec = s % 60;
    return pad(h) + ':' + pad(m) + ':' + (sec < 10 ? '0' : '') + sec.toFixed(3);
  }
  function pad(n) { return n < 10 ? '0' + n : '' + n; }

  // Pre-load scrubber if file was pre-selected
  var sel = document.getElementById('g-file');
  if (sel && sel.value) loadMedia(sel.value);
  </script>
  </body>
</html>
