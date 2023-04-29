<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Clip app</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
  </head>
  <body>

        <div class="w3-container w3-card w3-purple w3-half">

        <h2 class="w3-monospace">YouTube download</h2>

        <?php

          $youtubeUrl = $_POST['youtubeUrl'];
          $youtubeDlPath = '/usr/local/bin/youtube-dl';
          $path = 'uploads/';
          $getYoutubeFilenameCommand = $youtubeDlPath . ' --get-filename -f 18 -o "%(title)s" ' . $youtubeUrl;

          $youtubeFilename = (shell_exec($getYoutubeFilenameCommand));
          $newYoutubeFilename = str_replace(' ', '_', $youtubeFilename);
          $newYoutubeFilename = str_replace(')', '_', $newYoutubeFilename);
          $newYoutubeFilename = str_replace('(', '_', $newYoutubeFilename);
          $newYoutubeFilename = str_replace('.', '_', $newYoutubeFilename);
          $newYoutubeFilename = str_replace('"', '_', $newYoutubeFilename);


          // Remove last 3 characters because some weird character at the end breaks it by adding a question mark at the end.

          $sanitizedFilename = substr_replace($newYoutubeFilename ,"", -3);

          $command = $youtubeDlPath . ' -f 18 -o "uploads/' . $sanitizedFilename . '.%(ext)s" ' . $youtubeUrl;

          shell_exec($command);


        ?>
        <br>
        <video width="320" autoplay controls>
          <source src="uploads/<?php echo $sanitizedFilename; ?>.mp4">
        </video>
        <?php include 'videoFolder.php'; ?>
        <?php include 'backToHomeButton.php'; ?>
      </div>



  </body>
</html>
