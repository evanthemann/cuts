<html lang="en" dir="ltr">

  <head>

    <meta charset="utf-8">
    <title>Clip app</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">

  </head>

  <body>

    <div class="w3-container">
      <h1>Cuts</h1>

    <div class="w3-container w3-card w3-green w3-half">

    <h2 class="w3-sans-serif">Trim audio</h2>

    <form class="w3-form" action="trimAudioResult.php" method="post">
      <h6>Copy and paste your file name into the field, then choose start and end point in seconds.</h6>
      <h4>Which video?</h4><input class="w3-section w3-input" type="text" name="filename">
      <h4>Start where? (seconds)?</h4><input class="w3-section w3-input" type="text" name="startSeconds">
      <h4>End where? (seconds)?</h4><input class="w3-section w3-input" type="text" name="endSeconds">
      <input class="w3-section w3-input w3-green" type="submit" value="Go" name="submit">
    </form>

    <?php include 'videoFolder.php'; ?>
    <?php include 'backToHomeButton.php'; ?>

    <br>
  </div>
</div>

  </body>
</html>
