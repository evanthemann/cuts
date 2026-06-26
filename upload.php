<?php
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
        <?php if ($error): ?>
          <div class="w3-panel w3-red w3-round"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data">
          <input type="file" name="file" accept="video/*,audio/*"
                 class="w3-input w3-round w3-white w3-margin-bottom" style="padding:8px">
          <button type="submit" class="w3-button w3-white w3-text-blue w3-round">Upload</button>
        </form>
      </div>
      <div style="margin-top:16px"><?php include 'backToHomeButton.php'; ?></div>
    </div>
  </body>
</html>
