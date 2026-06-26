<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Cuts – Download</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <?php include 'darkHead.php'; ?>
  </head>
  <body>
    <div class="w3-container w3-padding-24">
      <h1>Cuts</h1>
      <div class="w3-card w3-padding w3-teal" style="max-width:600px">
        <h2 class="w3-monospace">Download</h2>
        <form action="youtubeLaunch.php" method="post">
          <input class="w3-input w3-round w3-margin-bottom" type="text" name="youtubeUrl"
                 placeholder="https://www.youtube.com/watch?v=... or any yt-dlp URL">
          <input class="w3-button w3-white w3-text-teal w3-round" type="submit" value="Next →">
        </form>
        <?php include 'backToHomeButton.php'; ?>
      </div>
    </div>
  </body>
</html>
