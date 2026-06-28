<?php
@ini_set('max_input_time', -1);
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $videoExts = ['mp4','mkv','mov','avi','webm'];
    $audioExts = ['mp3','m4a','wav','aac','ogg','flac'];
    $allExts   = array_merge($videoExts, $audioExts);
    $file      = $_FILES['file'] ?? null;

    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
        $error = 'No file selected.';
    } elseif ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
        $error = 'File exceeds the upload size limit.';
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Upload error (code ' . $file['error'] . ').';
    } else {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allExts)) {
            $error = 'Unsupported file type. Allowed: ' . implode(', ', $allExts) . '.';
        } else {
            $name = preg_replace('/[^a-zA-Z0-9_\-.]/', '_', basename($file['name']));
            $dest = __DIR__ . '/uploads/' . $name;
            if (file_exists($dest)) {
                $error = '"' . htmlspecialchars($name) . '" already exists.';
            } elseif (!move_uploaded_file($file['tmp_name'], $dest)) {
                $error = 'Failed to save the file. Check server permissions.';
            } else {
                header('Location: index.php?uploaded=' . urlencode($name));
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Cuts – Upload</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <?php include 'darkHead.php'; ?>
  </head>
  <body>
    <div class="w3-container w3-padding-24">
      <h1>Cuts</h1>
      <div class="w3-card w3-padding w3-blue" style="max-width:560px">
        <h2 style="margin-top:0">Upload</h2>
        <p class="w3-small" style="margin-top:0;opacity:.8">Max file size: <?= ini_get('upload_max_filesize') ?></p>
        <?php if ($error): ?>
          <div class="w3-panel w3-red w3-round"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form id="upload-form" method="post" enctype="multipart/form-data">
          <input type="file" name="file" id="file-input" accept="video/*,audio/*"
                 class="w3-input w3-round w3-white w3-margin-bottom" style="padding:8px">
          <button type="submit" id="upload-btn" class="w3-button w3-white w3-text-blue w3-round">Upload</button>
        </form>
        <div id="progress-wrap" style="display:none;margin-top:16px">
          <div class="w3-light-grey w3-round" style="height:24px;overflow:hidden">
            <div id="progress-bar" class="w3-blue w3-round" style="height:24px;width:0%;transition:width .2s"></div>
          </div>
          <p id="progress-label" class="w3-small" style="margin:4px 0 0;opacity:.85">0%</p>
        </div>
      </div>
      <div style="margin-top:16px"><?php include 'backToHomeButton.php'; ?></div>
    </div>
    <script>
      document.getElementById('upload-form').addEventListener('submit', function(e) {
        e.preventDefault();
        var fileInput = document.getElementById('file-input');
        if (!fileInput.files.length) return;

        var btn = document.getElementById('upload-btn');
        btn.disabled = true;
        btn.textContent = 'Uploading…';

        document.getElementById('progress-wrap').style.display = 'block';

        var xhr = new XMLHttpRequest();
        xhr.upload.addEventListener('progress', function(ev) {
          if (!ev.lengthComputable) return;
          var pct = Math.round(ev.loaded / ev.total * 100);
          var mb_done = (ev.loaded / 1048576).toFixed(1);
          var mb_total = (ev.total / 1048576).toFixed(1);
          document.getElementById('progress-bar').style.width = pct + '%';
          document.getElementById('progress-label').textContent = pct + '% — ' + mb_done + ' MB / ' + mb_total + ' MB';
        });
        xhr.addEventListener('load', function() {
          if (xhr.status >= 200 && xhr.status < 400) {
            window.location.href = xhr.responseURL || 'index.php';
          } else {
            btn.disabled = false;
            btn.textContent = 'Upload';
            document.getElementById('progress-wrap').style.display = 'none';
            alert('Upload failed (HTTP ' + xhr.status + ').');
          }
        });
        xhr.addEventListener('error', function() {
          btn.disabled = false;
          btn.textContent = 'Upload';
          document.getElementById('progress-wrap').style.display = 'none';
          alert('Upload failed. Check your connection and try again.');
        });

        var form = document.getElementById('upload-form');
        xhr.open('POST', form.action || window.location.href);
        xhr.send(new FormData(form));
      });
    </script>
  </body>
</html>
