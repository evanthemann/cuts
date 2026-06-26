<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Cuts – Advanced Download</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
  </head>
  <body>
    <div class="w3-container">
      <h1>Cuts</h1>
      <div class="w3-container w3-card w3-teal w3-half">
        <h2 class="w3-monospace">Advanced YouTube download</h2>
        <p>Fetch all available formats, pick your video and audio streams, and optionally embed or burn in subtitles.</p>
        <form action="ytdlpAdvancedFormats.php" method="post">
          <h4>YouTube URL</h4>
          <input class="w3-section w3-input w3-round" type="text" name="youtubeUrl" placeholder="https://www.youtube.com/watch?v=...">
          <input class="w3-section w3-input w3-round w3-hover-green" type="submit" value="Get available formats" name="submit">
        </form>
        <?php include 'videoFolder.php'; ?>
        <?php include 'backToHomeButton.php'; ?>
      </div>
    </div>
  </body>
</html>
