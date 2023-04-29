<!DOCTYPE html>
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
        <h2 class="w3-monospace">Select audio to upload:</h2>
        <form action="uploadAudio2.php" method="post" enctype="multipart/form-data">
          <input class="w3-section w3-input w3-round w3-hover-green" type="file" name="fileToUpload" id="fileToUpload">
          <input class="w3-section w3-input w3-round w3-hover-green" type="submit" value="Upload Video" name="submit">
        </form>

        <?php include 'videoFolder.php'; ?>
        <?php include 'backToHomeButton.php'; ?>

      </div>



    </div>



  </body>
</html>
