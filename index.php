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

$mediaExts = ['mp4','mkv','mov','avi','webm','mp3','m4a','wav','aac','ogg','flac'];
$mediaFiles = [];
$otherFiles = [];

foreach (glob('uploads/*') as $f) {
    if (!is_file($f)) continue;
    $name = basename($f);
    if ($name === '.gitkeep') continue;
    $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
    if (in_array($ext, $mediaExts)) {
        $mediaFiles[] = ['path' => $f, 'name' => $name, 'info' => ffprobeInfo($f)];
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
  </head>
  <body>
    <div class="w3-container w3-padding-24">
      <h1>Cuts</h1>

      <!-- ── IMPORT ─────────────────────────────────────── -->
      <div class="w3-card w3-padding w3-margin-bottom">
        <h3 class="w3-margin-top">Import</h3>
        <a href="youtubeDownload.php"><button class="w3-button w3-orange w3-margin-bottom">YouTube (quick)</button></a>
        <a href="ytdlpAdvanced.php"><button class="w3-button w3-teal w3-margin-bottom">YouTube (advanced)</button></a>
        <a href="upload.php"><button class="w3-button w3-blue w3-margin-bottom">Upload video</button></a>
        <a href="uploadAudio.php"><button class="w3-button w3-green w3-margin-bottom">Upload audio</button></a>
      </div>

      <!-- ── YOUR FILES ─────────────────────────────────── -->
      <div class="w3-card w3-padding w3-margin-bottom">
        <div style="display:flex; justify-content:space-between; align-items:baseline">
          <h3 class="w3-margin-top">Your files <?= count($mediaFiles) > 0 ? '(' . count($mediaFiles) . ')' : '' ?></h3>
          <?php if (!empty($mediaFiles) || !empty($otherFiles)): ?>
          <form action="emptyFolder.php" method="post" style="margin:0" onsubmit="return confirm('Empty the uploads folder? This cannot be undone.')">
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
                <th>Type</th>
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
                <td>
                  <?php if ($isVideo): ?>
                    <span class="w3-tag w3-blue w3-small">video</span>
                  <?php else: ?>
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
                  <?php if ($isVideo): ?>
                    <a href="trim.php?file=<?= $enc ?>"><button class="w3-button w3-purple w3-small">Trim</button></a>
                    <a href="extractAudio.php?file=<?= $enc ?>"><button class="w3-button w3-green w3-small">Extract audio</button></a>
                  <?php else: ?>
                    <a href="trimAudio.php?file=<?= $enc ?>"><button class="w3-button w3-purple w3-small">Trim</button></a>
                  <?php endif; ?>
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
            <div class="w3-card w3-padding w3-grey tool-card">
              <h4>Combine clips</h4>
              <p class="w3-small">Concatenate multiple clips into one file.</p>
              <button class="w3-button w3-white w3-small" disabled title="Coming soon">Coming soon</button>
            </div>
          </div>

        </div>
      </div>

    </div>
  </body>
</html>
