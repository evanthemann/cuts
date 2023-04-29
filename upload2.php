<html lang="en" dir="ltr">

  <head>

    <meta charset="utf-8">
    <title>Clip app</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">

  </head>

  <body>

    <div class="w3-container">

      <h1>Cuts</h1>

        <div class="w3-container w3-card w3-blue w3-half">



      <?php

          $target_dir = "uploads/";

          $newFileName =  str_replace(' ', '_', basename($_FILES["fileToUpload"]["name"]));
          $newFileName =  str_replace(')', '_', $newFileName);
          $newFileName =  str_replace('(', '_', $newFileName);
          $target_file = $target_dir . $newFileName;

          // $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);

          $uploadOk = 1;
          $videoFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

          // Check if image file is a actual image or fake image
          if(isset($_POST["submit"])) {
            $check = (mime_content_type($_FILES["fileToUpload"]["tmp_name"])=='video/mp4');
            if($check !== false) {
              $uploadOk = 1;
            } else {
              echo
              "<div class=\"w3-panel w3-red\">
              File is not a video.
              </div>";
              $uploadOk = 0;
            }
          }

          // Check if file already exists
          if (file_exists($target_file)) {
            echo
            "<div class=\"w3-panel w3-red\">
            Sorry, file already exists.
            </div>";
            $uploadOk = 0;
          }

          // Check file size max 1GB
          if ($_FILES["fileToUpload"]["size"] > 1048576000) {
            echo "<div class=\"w3-panel w3-red\">
            Sorry, your file is too large.
            </div>";
            $uploadOk = 0;
          }

          // Allow certain file formats
          if($videoFileType != "MP4" && $videoFileType != "mp4" ) {
            echo "<div class=\"w3-panel w3-red\">
            Sorry, only mp4 files are allowed.
            </div>";
            $uploadOk = 0;
          }

          // Check if file name is output.mp4
          if($_FILES["fileToUpload"]["name"] == 'output.mp4' ) {
            echo "<div class=\"w3-panel w3-red\">
            Can't use filename output.mp4.
            </div>";
            $uploadOk = 0;
          }

          // Check if $uploadOk is set to 0 by an error
          if ($uploadOk == 0) {
            echo
            "<div class=\"w3-panel w3-red\">
            Sorry, your file was not uploaded.
            </div>";
          // if everything is ok, try to upload file
          } else {
            if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
              echo "<div class=\"w3-panel w3-green\">
              The file <em>". htmlspecialchars( basename( $_FILES["fileToUpload"]["name"]))."</em> has been uploaded.
              </div>";
            } else {
              echo "<div class=\"w3-panel w3-red\">
              Sorry, there was an error uploading your file.
              </div>";
            }
          }
      ?>


      <h2 class="w3-monospace">Choose program</h2>
      <a href="trim.php"><button class="w3-button w3-purple w3-section">Trim video</button></a>
      <a href="trimAudio.php"><button class="w3-button w3-purple w3-section">Trim audio</button></a>
      <button type="button" name="button" class="w3-button w3-green w3-section">Extract audio</button>
      <button type="button" name="button" class="w3-button w3-orange w3-section">Combine clips</button>
      <button type="button" name="button" class="w3-button w3-yellow w3-section">Other</button>

      <?php include 'videoFolder.php'; ?>
      <?php include 'backToHomeButton.php'; ?>

        </div>

    </div>

  </body>
</html>
