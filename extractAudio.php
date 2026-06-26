<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Cuts – Extract audio</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <?php include 'darkHead.php'; ?>
  </head>
  <body>
    <div class="w3-container w3-padding-24">
      <h1>Cuts</h1>
      <div class="w3-card w3-padding w3-deep-orange" style="max-width:560px">
        <h2 style="margin-top:0">Extract audio</h2>
        <form action="extractAudioResult.php" method="post">
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
          <label class="w3-text-white w3-margin-bottom" style="display:block"><b>Output format</b></label>
          <label style="display:block" class="w3-margin-bottom">
            <input type="radio" name="format" value="mp3" checked>
            <span class="w3-text-white"><b>MP3</b> — re-encodes, plays anywhere</span>
          </label>
          <label style="display:block" class="w3-margin-bottom">
            <input type="radio" name="format" value="copy">
            <span class="w3-text-white"><b>Copy codec</b> — instant, keeps original audio stream</span>
          </label>
          <button type="submit" class="w3-button w3-white w3-text-deep-orange w3-round">Extract</button>
        </form>
      </div>
      <div style="margin-top:16px"><?php include 'backToHomeButton.php'; ?></div>
    </div>
  </body>
</html>
