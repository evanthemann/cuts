<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Cuts – Trim video</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <?php include 'darkHead.php'; ?>
  </head>
  <body>
    <div class="w3-container w3-padding-24">
      <h1>Cuts</h1>
      <div class="w3-card w3-padding w3-purple" style="max-width:560px">
        <h2 style="margin-top:0">Trim video</h2>
        <form action="trimresult.php" method="post">
          <label class="w3-text-white"><b>File</b></label>
          <select class="w3-select w3-border w3-margin-bottom" name="filename">
            <option value="">— choose a file —</option>
            <?php
              $preselect = basename($_GET['file'] ?? '');
              foreach (glob('uploads/*.{mp4,mkv,mov,avi,webm}', GLOB_BRACE) as $f):
                $name = basename($f);
            ?>
            <option value="<?= htmlspecialchars($name) ?>" <?= $name === $preselect ? 'selected' : '' ?>><?= htmlspecialchars($name) ?></option>
            <?php endforeach; ?>
          </select>
          <label class="w3-text-white"><b>Start</b></label>
          <input class="w3-input w3-border w3-margin-bottom" type="number" step="0.1" min="0" name="startSeconds" placeholder="seconds">
          <label class="w3-text-white"><b>End</b></label>
          <input class="w3-input w3-border w3-margin-bottom" type="number" step="0.1" min="0" name="endSeconds" placeholder="seconds">
          <button type="submit" class="w3-button w3-white w3-text-purple w3-round">Trim</button>
        </form>
      </div>
      <div style="margin-top:16px"><?php include 'backToHomeButton.php'; ?></div>
    </div>
  </body>
</html>
