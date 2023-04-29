<div class="w3-container w3-section w3-card w3-gray">
<?php
  echo "<h4>Your video folder:</h4>";
  echo "<pre>";
  echo shell_exec('ls uploads/');
  echo "</pre>";
?>
<form action="emptyFolder.php" method="post" enctype="multipart/form-data">
  <input class="w3-button w3-hover-red w3-section w3-black w3-small w3-right" type="submit" value="Empty folder">
</form>
</div>
