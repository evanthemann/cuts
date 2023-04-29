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

      $startSeconds = $_POST['startSeconds'];
      $endSeconds = $_POST['endSeconds'];
      $filename = $_POST['filename'];
      $path = 'uploads/';
      $ffmpegPath = '/usr/local/bin/ffmpeg';
      $command = $ffmpegPath . ' -i ' . $path . $filename . ' -ss ' . $startSeconds . ' -to ' . $endSeconds . ' -c:v copy -c:a copy -y ' . $path . 'output_' . $filename;

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
