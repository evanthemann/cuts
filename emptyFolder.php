<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Cuts</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
  </head>
  <body>

    <?php

    $target_dir = "uploads/";
    $command = 'rm -rf uploads/*';

    shell_exec($command);

    ?>


    <div class="w3-container">
      <div class="w3-section w3-panel w3-green w3-border">

      Done. Folder empty. <a href="index.php">Clear alert.</a>

      </div>
      <h1>Cuts</h1>
      <p>What do you want to do?</p>
      <a href="youtubeDownload.php"><button class="w3-button w3-orange">Import a YouTube video</button></a>
      <a href="upload.php"><button class="w3-button w3-blue">Upload a file</button></a>

      <?php include 'videoFolder.php'; ?>

    </div>
  </body>
</html>
