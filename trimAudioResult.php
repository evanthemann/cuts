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

    <h2 class="w3-monospace">Trim video</h2>

    <?php

      $filename = basename($_POST['filename']);
      $startSeconds = floatval($_POST['startSeconds']);
      $endSeconds = floatval($_POST['endSeconds']);
      $path = 'uploads/';
      $inputFile = $path . $filename;

      if (!file_exists($inputFile)) {
        die('<div class="w3-panel w3-red">File not found.</div>');
      }

      $ffmpegPath = '/usr/local/bin/ffmpeg';
      $command = $ffmpegPath
        . ' -i ' . escapeshellarg($inputFile)
        . ' -ss ' . $startSeconds
        . ' -to ' . $endSeconds
        . ' -c:v copy -c:a copy -y '
        . escapeshellarg($path . 'output_' . $filename);

      shell_exec($command);

    ?>
    <br>
    <audio autoplay controls>
      <source src="uploads/output_<?php echo $filename; ?>">
    </audio>
    <?php include 'videoFolder.php'; ?>
    <?php include 'backToHomeButton.php'; ?>
    </div>
  </div>


  </body>
</html>
