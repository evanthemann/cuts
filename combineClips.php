<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Cuts – Combine clips</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
      <?php include 'darkHead.php'; ?>
  </head>
  <body>
    <div class="w3-container">
      <h1>Cuts</h1>
      <div class="w3-container w3-card w3-orange w3-half">
        <h2 class="w3-monospace">Combine clips</h2>
        <p>Pick clips in the order you want them joined.</p>

        <form action="combineClipsResult.php" method="post">

          <?php
            $files = glob('uploads/*.{mp4,mkv,mov,avi,webm,mp3,m4a,wav,aac,ogg,flac}', GLOB_BRACE);
            sort($files);
            $opts = '<option value="">— none —</option>';
            foreach ($files as $f) {
                $name = htmlspecialchars(basename($f));
                $opts .= "<option value=\"$name\">$name</option>";
            }

            for ($i = 1; $i <= 5; $i++):
          ?>
          <h4>Clip <?= $i ?><?= $i > 2 ? ' <span class="w3-small w3-text-light-grey">(optional)</span>' : '' ?></h4>
          <select class="w3-section w3-select w3-border" name="clips[]">
            <?= $opts ?>
          </select>
          <?php endfor; ?>

          <h4>Mode</h4>
          <label style="display:block" class="w3-margin-bottom">
            <input type="radio" name="mode" value="copy" checked>
            <strong>Fast</strong> — copy codec, no re-encoding (clips must be same format)
          </label>
          <label style="display:block" class="w3-margin-bottom">
            <input type="radio" name="mode" value="reencode">
            <strong>Re-encode</strong> — converts everything to H.264 MP4 (works with mixed formats, slower)
          </label>

          <input class="w3-section w3-button w3-white w3-border w3-margin-bottom" type="submit" value="Combine">
        </form>

        <?php include 'videoFolder.php'; ?>
        <?php include 'backToHomeButton.php'; ?>
      </div>
    </div>
  </body>
</html>
