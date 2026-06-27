<?php
$preselect    = basename($_GET['file'] ?? '');
$preselectExt = strtolower(pathinfo($preselect, PATHINFO_EXTENSION));
$audioExts    = ['mp3','m4a','wav','aac','ogg','flac'];
$defaultTab   = isset($_GET['tab']) ? $_GET['tab'] : (in_array($preselectExt, $audioExts) ? 'audio' : 'video');
$defaultTab   = in_array($defaultTab, ['video','audio']) ? $defaultTab : 'video';

$videoFiles = glob('uploads/*.{mp4,mkv,mov,avi,webm}', GLOB_BRACE) ?: [];
$audioFiles = glob('uploads/*.{mp3,m4a,wav,aac,ogg,flac}', GLOB_BRACE) ?: [];

function fileOpts($files, $preselect) {
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
    <title>Cuts – Trim</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <?php include 'darkHead.php'; ?>
  </head>
  <body>
    <div class="w3-container w3-padding-24">
      <h1>Cuts</h1>
      <div class="w3-card w3-purple" style="max-width:560px">

        <!-- Tab bar -->
        <div class="w3-bar">
          <button id="tab-btn-video" class="w3-bar-item w3-button" onclick="switchTab('video')">Video</button>
          <button id="tab-btn-audio" class="w3-bar-item w3-button" onclick="switchTab('audio')">Audio</button>
        </div>

        <div class="w3-padding">

          <!-- ── VIDEO PANEL ───────────────────────── -->
          <div id="panel-video">
            <form action="trimresult.php" method="post">
              <label class="w3-text-white"><b>File</b></label>
              <select name="filename" id="v-file" class="w3-select w3-border w3-margin-bottom"
                onchange="loadMedia('video', this.value)">
                <?= fileOpts($videoFiles, $defaultTab === 'video' ? $preselect : '') ?>
              </select>

              <div id="v-scrub" style="display:none;margin-bottom:16px">
                <video id="v-media" preload="metadata"
                  style="width:100%;border-radius:4px;background:#000;display:block;margin-bottom:8px"></video>
                <input id="v-scrubber" type="range" min="0" max="100" step="0.033" value="0"
                  style="width:100%;margin-bottom:8px" oninput="onScrub('video', this.value)">
                <div class="w3-margin-bottom" style="display:flex;gap:6px;flex-wrap:wrap">
                  <button type="button" onclick="nudge('video',-30)" class="w3-button w3-small w3-border">−30f</button>
                  <button type="button" onclick="nudge('video',-1)"  class="w3-button w3-small w3-border">−1f</button>
                  <button type="button" id="v-play-btn" onclick="togglePlay('video')" class="w3-button w3-small w3-border">Play</button>
                  <button type="button" onclick="nudge('video',1)"   class="w3-button w3-small w3-border">+1f</button>
                  <button type="button" onclick="nudge('video',30)"  class="w3-button w3-small w3-border">+30f</button>
                </div>
                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                  <input id="v-time" type="text" readonly value="00:00:00.000"
                    style="width:126px;font-family:monospace;font-size:14px;background:#222;color:#fff;border:1px solid #555;padding:4px 8px;border-radius:4px">
                  <button type="button" onclick="setPoint('video','start')" class="w3-button w3-small w3-border">→ Start</button>
                  <button type="button" onclick="setPoint('video','end')"   class="w3-button w3-small w3-border">→ End</button>
                </div>
              </div>

              <label class="w3-text-white"><b>Start</b></label>
              <input id="v-start" class="w3-input w3-border w3-margin-bottom" type="number" step="0.001" min="0" name="startSeconds" placeholder="seconds">
              <label class="w3-text-white"><b>End</b></label>
              <input id="v-end" class="w3-input w3-border w3-margin-bottom" type="number" step="0.001" min="0" name="endSeconds" placeholder="seconds">
              <button type="submit" class="w3-button w3-white w3-text-purple w3-round">Trim</button>
            </form>
          </div>

          <!-- ── AUDIO PANEL ───────────────────────── -->
          <div id="panel-audio" style="display:none">
            <form action="trimAudioResult.php" method="post">
              <label class="w3-text-white"><b>File</b></label>
              <select name="filename" id="a-file" class="w3-select w3-border w3-margin-bottom"
                onchange="loadMedia('audio', this.value)">
                <?= fileOpts($audioFiles, $defaultTab === 'audio' ? $preselect : '') ?>
              </select>

              <div id="a-scrub" style="display:none;margin-bottom:16px">
                <audio id="a-media" preload="metadata" style="display:none"></audio>
                <input id="a-scrubber" type="range" min="0" max="100" step="0.033" value="0"
                  style="width:100%;margin-bottom:8px" oninput="onScrub('audio', this.value)">
                <div class="w3-margin-bottom" style="display:flex;gap:6px;flex-wrap:wrap">
                  <button type="button" onclick="nudge('audio',-30)" class="w3-button w3-small w3-border">−30f</button>
                  <button type="button" onclick="nudge('audio',-1)"  class="w3-button w3-small w3-border">−1f</button>
                  <button type="button" id="a-play-btn" onclick="togglePlay('audio')" class="w3-button w3-small w3-border">Play</button>
                  <button type="button" onclick="nudge('audio',1)"   class="w3-button w3-small w3-border">+1f</button>
                  <button type="button" onclick="nudge('audio',30)"  class="w3-button w3-small w3-border">+30f</button>
                </div>
                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                  <input id="a-time" type="text" readonly value="00:00:00.000"
                    style="width:126px;font-family:monospace;font-size:14px;background:#222;color:#fff;border:1px solid #555;padding:4px 8px;border-radius:4px">
                  <button type="button" onclick="setPoint('audio','start')" class="w3-button w3-small w3-border">→ Start</button>
                  <button type="button" onclick="setPoint('audio','end')"   class="w3-button w3-small w3-border">→ End</button>
                </div>
              </div>

              <label class="w3-text-white"><b>Start</b></label>
              <input id="a-start" class="w3-input w3-border w3-margin-bottom" type="number" step="0.001" min="0" name="startSeconds" placeholder="seconds">
              <label class="w3-text-white"><b>End</b></label>
              <input id="a-end" class="w3-input w3-border w3-margin-bottom" type="number" step="0.001" min="0" name="endSeconds" placeholder="seconds">
              <button type="submit" class="w3-button w3-white w3-text-purple w3-round">Trim</button>
            </form>
          </div>

        </div><!-- /w3-padding -->
      </div>
      <div style="margin-top:16px"><?php include 'backToHomeButton.php'; ?></div>
    </div>

  <script>
  var FRAME = 1 / 30;

  var t = {
    video: {
      media:    document.getElementById('v-media'),
      scrubber: document.getElementById('v-scrubber'),
      timeBox:  document.getElementById('v-time'),
      playBtn:  document.getElementById('v-play-btn'),
      scrubSec: document.getElementById('v-scrub'),
      startFld: document.getElementById('v-start'),
      endFld:   document.getElementById('v-end'),
    },
    audio: {
      media:    document.getElementById('a-media'),
      scrubber: document.getElementById('a-scrubber'),
      timeBox:  document.getElementById('a-time'),
      playBtn:  document.getElementById('a-play-btn'),
      scrubSec: document.getElementById('a-scrub'),
      startFld: document.getElementById('a-start'),
      endFld:   document.getElementById('a-end'),
    }
  };

  ['video','audio'].forEach(function(tab) {
    t[tab].media.addEventListener('loadedmetadata', function() {
      t[tab].scrubber.max   = t[tab].media.duration;
      t[tab].scrubber.value = 0;
      t[tab].timeBox.value  = fmtTime(0);
      t[tab].scrubSec.style.display = 'block';
    });
    t[tab].media.addEventListener('seeked',     function() { syncUI(tab); });
    t[tab].media.addEventListener('timeupdate', function() { syncUI(tab); });
    t[tab].media.addEventListener('play',  function() { t[tab].playBtn.textContent = 'Pause'; });
    t[tab].media.addEventListener('pause', function() { t[tab].playBtn.textContent = 'Play'; });
  });

  function syncUI(tab) {
    t[tab].scrubber.value = t[tab].media.currentTime;
    t[tab].timeBox.value  = fmtTime(t[tab].media.currentTime);
  }
  function loadMedia(tab, filename) {
    if (!filename) { t[tab].scrubSec.style.display = 'none'; return; }
    t[tab].media.src = 'uploads/' + encodeURIComponent(filename);
    t[tab].media.load();
  }
  function onScrub(tab, val)    { t[tab].media.currentTime = parseFloat(val); }
  function nudge(tab, frames)   { t[tab].media.currentTime = Math.max(0, Math.min(t[tab].media.duration, t[tab].media.currentTime + frames * FRAME)); }
  function togglePlay(tab)      { t[tab].media.paused ? t[tab].media.play() : t[tab].media.pause(); }
  function setPoint(tab, which) { (which === 'start' ? t[tab].startFld : t[tab].endFld).value = parseFloat(t[tab].media.currentTime).toFixed(3); }

  function fmtTime(s) {
    var h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60), sec = s % 60;
    return pad(h) + ':' + pad(m) + ':' + (sec < 10 ? '0' : '') + sec.toFixed(3);
  }
  function pad(n) { return n < 10 ? '0' + n : '' + n; }

  // Tab switching
  var activeTab = '<?= $defaultTab ?>';
  function switchTab(tab) {
    // Pause whichever media is playing
    t[activeTab].media.pause();
    activeTab = tab;
    document.getElementById('panel-video').style.display = tab === 'video' ? 'block' : 'none';
    document.getElementById('panel-audio').style.display = tab === 'audio' ? 'block' : 'none';
    document.getElementById('tab-btn-video').className = 'w3-bar-item w3-button' + (tab === 'video' ? ' w3-white w3-text-purple' : '');
    document.getElementById('tab-btn-audio').className = 'w3-bar-item w3-button' + (tab === 'audio' ? ' w3-white w3-text-purple' : '');
  }
  switchTab(activeTab);

  // Pre-load scrubber if file was pre-selected
  var vFile = document.getElementById('v-file').value;
  var aFile = document.getElementById('a-file').value;
  if (vFile) loadMedia('video', vFile);
  if (aFile) loadMedia('audio', aFile);
  </script>
  </body>
</html>
