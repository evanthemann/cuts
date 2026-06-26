<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Cuts – Extract audio</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
  </head>
  <body>
    <div class="w3-container">
      <h1>Cuts</h1>
      <div class="w3-container w3-card w3-green w3-half">
        <h2 class="w3-monospace">Extract audio</h2>

        <form action="extractAudioResult.php" method="post">

          <h4>Which video?</h4>
          <select class="w3-section w3-select w3-border" name="filename">
            <option value="">— choose a file —</option>
            <?php
              $files = glob('uploads/*.{mp4,mkv,mov,avi,webm}', GLOB_BRACE);
              sort($files);
              foreach ($files as $f):
                $name = basename($f);
            ?>
            <option value="<?= htmlspecialchars($name) ?>"><?= htmlspecialchars($name) ?></option>
            <?php endforeach; ?>
          </select>

          <h4>Output format</h4>
          <label class="w3-margin-bottom" style="display:block">
            <input type="radio" name="format" value="mp3" checked>
            <strong>MP3</strong> — re-encodes, plays anywhere
          </label>
          <label class="w3-margin-bottom" style="display:block">
            <input type="radio" name="format" value="copy">
            <strong>Copy codec</strong> — instant, keeps original audio stream as-is
          </label>

          <input class="w3-section w3-button w3-green w3-border w3-border-white" type="submit" value="Extract">
        </form>

        <?php include 'videoFolder.php'; ?>
        <?php include 'backToHomeButton.php'; ?>
      </div>
    </div>
  </body>
</html>
