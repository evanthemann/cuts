<html lang="en" dir="ltr">

  <head>

    <meta charset="utf-8">
    <title>Clip app</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">

  </head>

  <body>

    <div class="w3-container">
      <h1>Cuts</h1>

    <div class="w3-container w3-card w3-purple w3-half">

    <h2 class="w3-sans-serif">Trim video</h2>

    <form class="w3-form" action="trimresult.php" method="post">
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
      <h4>Start (seconds)</h4><input class="w3-section w3-input" type="number" step="0.1" min="0" name="startSeconds" placeholder="0">
      <h4>End (seconds)</h4><input class="w3-section w3-input" type="number" step="0.1" min="0" name="endSeconds" placeholder="0">
      <input class="w3-section w3-input w3-green" type="submit" value="Go" name="submit">
    </form>

    <?php include 'videoFolder.php'; ?>
    <?php include 'backToHomeButton.php'; ?>

    <br>
  </div>
</div>

  </body>
</html>
