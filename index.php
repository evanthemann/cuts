<?php

function ffprobeInfo($path) {
    $json = shell_exec('ffprobe -v error -print_format json -show_format -show_streams ' . escapeshellarg($path) . ' 2>/dev/null');
    $data = json_decode($json, true);
    if (!$data) return null;

    $result = ['duration' => null, 'size' => null, 'type' => null, 'resolution' => null, 'bitrate' => null];

    if (isset($data['format']['duration'])) $result['duration'] = (float)$data['format']['duration'];
    if (isset($data['format']['size']))    $result['size']     = (int)$data['format']['size'];

    foreach ($data['streams'] ?? [] as $s) {
        if ($s['codec_type'] === 'video' && $result['type'] !== 'video') {
            $result['type'] = 'video';
            if (isset($s['width'], $s['height'])) {
                $result['resolution'] = $s['width'] . '×' . $s['height'];
            }
        }
        if ($s['codec_type'] === 'audio') {
            if (!$result['type']) $result['type'] = 'audio';
            if (!$result['bitrate'] && isset($s['bit_rate'])) {
                $result['bitrate'] = round($s['bit_rate'] / 1000) . 'k';
            }
        }
    }
    return $result;
}

function fmtDuration($sec) {
    if ($sec === null) return '—';
    $sec = (int)$sec;
    $h = intdiv($sec, 3600);
    $m = intdiv($sec % 3600, 60);
    $s = $sec % 60;
    return $h > 0 ? sprintf('%d:%02d:%02d', $h, $m, $s) : sprintf('%d:%02d', $m, $s);
}

function fmtSize($bytes) {
    if ($bytes === null) return '—';
    if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
    return round($bytes / 1024) . ' KB';
}

function findSubVtt($path) {
    $base = substr($path, 0, strrpos($path, '.'));
    return file_exists($base . '.vtt') ? $base . '.vtt' : null;
}

function findThumb($path) {
    $base = substr($path, 0, strrpos($path, '.'));
    if (file_exists($base . '.jpg')) return $base . '.jpg';
    // burned files are renamed VideoTitle_burned.mp4; thumbnail kept original name
    $baseOrig = preg_replace('/_burned$/', '', $base);
    if ($baseOrig !== $base && file_exists($baseOrig . '.jpg')) return $baseOrig . '.jpg';
    // ffmpeg fallback: extract frame and cache in uploads/thumbs/
    $thumbsDir = __DIR__ . '/uploads/thumbs';
    if (!is_dir($thumbsDir)) mkdir($thumbsDir, 0755, true);
    $cached = $thumbsDir . '/' . md5(basename($path)) . '.jpg';
    if (!file_exists($cached)) {
        shell_exec('ffmpeg -y -i ' . escapeshellarg($path)
            . ' -ss 00:00:03 -vframes 1 -vf "scale=160:-1"'
            . ' ' . escapeshellarg($cached) . ' 2>/dev/null');
    }
    return file_exists($cached) ? 'uploads/thumbs/' . basename($cached) : null;
}

$mediaExts = ['mp4','mkv','mov','avi','webm','mp3','m4a','wav','aac','ogg','flac'];
$videoExts = ['mp4','mkv','mov','avi','webm'];
$mediaFiles = [];
$otherFiles = [];

foreach (glob('uploads/*') as $f) {
    if (!is_file($f)) continue;
    $name = basename($f);
    if ($name === '.gitkeep') continue;
    $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
    if ($ext === 'jpg' || $ext === 'jpeg') {
        // skip thumbnail sidecars — check both original name and _burned variant
        $base = substr($f, 0, strrpos($f, '.'));
        foreach ($videoExts as $ve) {
            if (file_exists($base . '.' . $ve)) continue 2;
            if (file_exists($base . '_burned.' . $ve)) continue 2;
        }
    }
    if ($ext === 'log' || $ext === 'json') continue; // skip job artifacts
    if ($ext === 'vtt') {                            // skip subtitle sidecars
        $base = substr($f, 0, strrpos($f, '.'));
        foreach ($videoExts as $ve) {
            if (file_exists($base . '.' . $ve)) continue 2;
        }
    }
    if (in_array($ext, $mediaExts)) {
        $isVid = in_array($ext, $videoExts);
        $mediaFiles[] = ['path' => $f, 'name' => $name, 'info' => ffprobeInfo($f),
                         'thumb' => $isVid ? findThumb($f) : null,
                         'vtt'   => $isVid ? findSubVtt($f) : null];
    } else {
        $otherFiles[] = $name;
    }
}
usort($mediaFiles, fn($a, $b) => strcmp($a['name'], $b['name']));

?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Cuts</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <style>
      .file-name { max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: block; }
      .tool-card { height: 100%; }
    </style>
      <?php include 'darkHead.php'; ?>
  </head>
  <body>
    <div class="w3-container w3-padding-24">
      <div style="display:flex;justify-content:space-between;align-items:center">
        <h1 style="margin:0">Cuts</h1>
        <div style="display:flex;gap:8px">
          <a href="status.php"><button class="w3-button w3-round w3-border">Status</button></a>
          <button class="cuts-dark-btn w3-button w3-round w3-border" onclick="toggleDark()">Dark</button>
        </div>
      </div>

      <?php if ($_GET['emptied'] ?? false): ?>
        <div class="w3-panel w3-green">Folder emptied.</div>
      <?php endif; ?>
      <?php if ($_GET['uploaded'] ?? false): ?>
        <div class="w3-panel w3-green"><?= htmlspecialchars($_GET['uploaded']) ?> uploaded.</div>
      <?php endif; ?>

      <!-- ── IMPORT ─────────────────────────────────────── -->
      <div class="w3-card w3-padding w3-margin-bottom">
        <h3 class="w3-margin-top">Import</h3>
        <a href="youtube.php"><button class="w3-button w3-teal w3-margin-bottom">YouTube</button></a>
        <a href="upload.php"><button class="w3-button w3-blue w3-margin-bottom">Upload video</button></a>
        <a href="uploadAudio.php"><button class="w3-button w3-green w3-margin-bottom">Upload audio</button></a>
      </div>

      <!-- ── YOUR FILES ─────────────────────────────────── -->
      <div class="w3-card w3-padding w3-margin-bottom">
        <div style="display:flex; justify-content:space-between; align-items:baseline">
          <h3 class="w3-margin-top">Your files <?= count($mediaFiles) > 0 ? '(' . count($mediaFiles) . ')' : '' ?></h3>
          <?php if (!empty($mediaFiles) || !empty($otherFiles)): ?>
          <form action="emptyFolder.php" method="post" style="margin:0" onsubmit="return confirm('Delete ALL files in uploads/? This cannot be undone.')">
            <button class="w3-button w3-small w3-black w3-hover-red" type="submit">Empty folder</button>
          </form>
          <?php endif; ?>
        </div>

        <?php if (empty($mediaFiles) && empty($otherFiles)): ?>
          <p class="w3-text-grey">No files yet — import something above to get started.</p>
        <?php else: ?>

        <div class="w3-responsive">
          <table class="w3-table w3-striped w3-hoverable w3-bordered w3-small">
            <thead>
              <tr class="w3-dark-grey">
                <th></th>
                <th>Name</th>
                <th>Duration</th>
                <th>Resolution / Bitrate</th>
                <th>Size</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($mediaFiles as $f):
                $info = $f['info'];
                $type = $info['type'] ?? 'unknown';
                $isVideo = $type === 'video';
                $enc = urlencode($f['name']);
              ?>
              <tr>
                <td style="padding:4px;width:88px">
                  <?php if ($f['thumb']): ?>
                    <img src="<?= htmlspecialchars($f['thumb']) ?>" alt=""
                         style="width:80px;height:45px;object-fit:cover;border-radius:3px;display:block">
                  <?php elseif (!$isVideo): ?>
                    <span class="w3-tag w3-green w3-small">audio</span>
                  <?php endif; ?>
                </td>
                <td title="<?= htmlspecialchars($f['name']) ?>">
                  <span class="file-name"><?= htmlspecialchars($f['name']) ?></span>
                </td>
                <td><?= fmtDuration($info['duration'] ?? null) ?></td>
                <td>
                  <?php if ($isVideo && $info['resolution']): ?>
                    <?= htmlspecialchars($info['resolution']) ?>
                  <?php elseif ($info['bitrate']): ?>
                    <?= htmlspecialchars($info['bitrate']) ?>
                  <?php else: ?>
                    —
                  <?php endif; ?>
                </td>
                <td><?= fmtSize($info['size'] ?? null) ?></td>
                <td style="white-space:nowrap">
                  <button class="w3-button w3-teal w3-small"
                    onclick="viewFile('<?= htmlspecialchars($f['path'], ENT_QUOTES) ?>','<?= htmlspecialchars($f['name'], ENT_QUOTES) ?>',<?= $isVideo ? 'false' : 'true' ?>,'<?= htmlspecialchars($f['vtt'] ?? '', ENT_QUOTES) ?>')">View</button>
                  <?php if ($isVideo): ?>
                    <a href="trim.php?file=<?= $enc ?>"><button class="w3-button w3-purple w3-small">Trim</button></a>
                    <a href="extractAudio.php?file=<?= $enc ?>"><button class="w3-button w3-green w3-small">Extract audio</button></a>
                  <?php else: ?>
                    <a href="trimAudio.php?file=<?= $enc ?>"><button class="w3-button w3-purple w3-small">Trim</button></a>
                  <?php endif; ?>
                  <form method="post" action="deleteFile.php" style="display:inline"
                        onsubmit="return confirm('Delete <?= htmlspecialchars($f['name'], ENT_QUOTES) ?>?')">
                    <input type="hidden" name="filename" value="<?= htmlspecialchars($f['name'], ENT_QUOTES) ?>">
                    <button type="submit" class="w3-button w3-red w3-small">Delete</button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <?php if (!empty($otherFiles)): ?>
          <p class="w3-small w3-text-grey w3-margin-top">
            Other files: <?= htmlspecialchars(implode(', ', $otherFiles)) ?>
          </p>
        <?php endif; ?>

        <?php endif; ?>
      </div>

      <!-- ── TOOLS ──────────────────────────────────────── -->
      <div class="w3-card w3-padding w3-margin-bottom">
        <h3 class="w3-margin-top">Tools</h3>
        <div class="w3-row-padding">

          <div class="w3-quarter w3-margin-bottom">
            <div class="w3-card w3-padding w3-purple tool-card">
              <h4>Trim video</h4>
              <p class="w3-small">Cut a clip between two timestamps.</p>
              <a href="trim.php"><button class="w3-button w3-white w3-small">Open</button></a>
            </div>
          </div>

          <div class="w3-quarter w3-margin-bottom">
            <div class="w3-card w3-padding w3-green tool-card">
              <h4>Trim audio</h4>
              <p class="w3-small">Cut an audio file between two timestamps.</p>
              <a href="trimAudio.php"><button class="w3-button w3-white w3-small">Open</button></a>
            </div>
          </div>

          <div class="w3-quarter w3-margin-bottom">
            <div class="w3-card w3-padding w3-deep-orange tool-card">
              <h4>Extract audio</h4>
              <p class="w3-small">Pull the audio track out of a video file.</p>
              <a href="extractAudio.php"><button class="w3-button w3-white w3-small">Open</button></a>
            </div>
          </div>

          <div class="w3-quarter w3-margin-bottom">
            <div class="w3-card w3-padding w3-orange tool-card">
              <h4>Combine clips</h4>
              <p class="w3-small">Concatenate multiple clips into one file.</p>
              <a href="combineClips.php"><button class="w3-button w3-white w3-small">Open</button></a>
            </div>
          </div>

        </div>
      </div>

    </div>
    <!-- ── VIEW MODAL ─────────────────────────────────────── -->
    <div id="view-modal" class="w3-modal" onclick="closeModal()" style="display:none">
      <div class="w3-modal-content w3-animate-zoom" onclick="event.stopPropagation()" style="max-width:860px;margin:40px auto">
        <div class="w3-bar w3-dark-grey">
          <span id="modal-title" class="w3-bar-item w3-padding" style="font-family:monospace;font-size:13px"></span>
          <button class="w3-bar-item w3-button w3-right w3-large" onclick="closeModal()" style="line-height:1">&times;</button>
        </div>
        <div style="background:#000;text-align:center;padding:8px">
          <video id="modal-video" controls style="max-width:100%;max-height:72vh;display:none"></video>
          <audio id="modal-audio" controls style="width:90%;margin:16px auto;display:none"></audio>
        </div>
      </div>
    </div>

    <script>
      function viewFile(path, name, isAudio, vttSrc) {
        document.getElementById('modal-title').textContent = name;
        var vid = document.getElementById('modal-video');
        var aud = document.getElementById('modal-audio');
        if (isAudio) {
          vid.style.display = 'none'; vid.src = '';
          aud.style.display = 'block'; aud.src = path;
          aud.play();
        } else {
          aud.style.display = 'none'; aud.src = '';
          // Clear any previous track elements
          Array.from(vid.querySelectorAll('track')).forEach(function(t) { t.remove(); });
          vid.style.display = 'block'; vid.src = path;
          if (vttSrc) {
            var track = document.createElement('track');
            track.kind = 'subtitles';
            track.src = vttSrc;
            track.srclang = 'en';
            track.label = 'Subtitles';
            track.default = true;
            vid.appendChild(track);
          }
          vid.play();
        }
        document.getElementById('view-modal').style.display = 'block';
      }
      function closeModal() {
        document.getElementById('view-modal').style.display = 'none';
        var vid = document.getElementById('modal-video');
        var aud = document.getElementById('modal-audio');
        vid.pause(); vid.src = '';
        Array.from(vid.querySelectorAll('track')).forEach(function(t) { t.remove(); });
        aud.pause(); aud.src = '';
      }
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeModal();
      });
    </script>
  </body>
</html>
