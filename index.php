<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Cuts</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
  </head>
  <body>
    <div class="w3-container">
      <h1>Cuts</h1>
      <p>What do you want to do?</p>
      <a href="youtubeDownload.php"><button class="w3-button w3-orange">Import a YouTube video</button></a>
      <a href="upload.php"><button class="w3-button w3-blue">Upload a video file</button></a>
      <a href="uploadAudio.php"><button class="w3-button w3-green">Upload an audio file</button></a>

      <?php include 'videoFolder.php'; ?>

    </div>
  </body>
</html>
