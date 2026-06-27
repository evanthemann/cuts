<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Cuts – Combine clips</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <?php include 'darkHead.php'; ?>
  </head>
  <body>
    <div class="w3-container w3-padding-24">
      <h1>Cuts</h1>
      <div class="w3-card w3-padding w3-orange" style="max-width:560px">
        <h2 style="margin-top:0">Combine clips</h2>
        <p style="margin-top:0">Pick clips in the order you want them joined.</p>
        <form action="combineClipsResult.php" method="post">
          <?php
            $files = glob('uploads/*.{mp4,mkv,mov,avi,webm,mp3,m4a,wav,aac,ogg,flac}', GLOB_BRACE);
            sort($files);
            $opts = '<option value="">— none —</option>';
            foreach ($files as $f) {
                $name = htmlspecialchars(basename($f));
                $opts .= "<option value=\"$name\">$name</option>";
            }
          ?>
          <div id="clipSlots">
            <label class="w3-text-white"><b>Clip 1</b></label>
            <select class="w3-select w3-border w3-margin-bottom" name="clips[]">
              <?= $opts ?>
            </select>
            <label class="w3-text-white"><b>Clip 2</b></label>
            <select class="w3-select w3-border w3-margin-bottom" name="clips[]">
              <?= $opts ?>
            </select>
          </div>
          <button type="button" class="w3-button w3-white w3-text-orange w3-round w3-margin-bottom" onclick="addClip()">+ Add clip</button>

          <label class="w3-text-white w3-margin-bottom" style="display:block"><b>Mode</b></label>
          <label style="display:block" class="w3-margin-bottom">
            <input type="radio" name="mode" value="reencode" checked>
            <span class="w3-text-white"><b>Re-encode</b> — converts to H.264 MP4, works with mixed formats</span>
          </label>
          <label style="display:block" class="w3-margin-bottom">
            <input type="radio" name="mode" value="copy">
            <span class="w3-text-white"><b>Fast</b> — copy codec, no re-encoding (clips must be identical format)</span>
          </label>
          <button type="submit" class="w3-button w3-white w3-text-orange w3-round">Combine</button>
        </form>
      </div>
      <div style="margin-top:16px"><?php include 'backToHomeButton.php'; ?></div>
    </div>
    <script>
    var clipCount = 2;
    function addClip() {
      clipCount++;
      var slots = document.getElementById('clipSlots');
      var lastSelect = slots.querySelector('select:last-of-type');
      var newSelect = lastSelect.cloneNode(true);
      newSelect.value = '';
      var label = document.createElement('label');
      label.className = 'w3-text-white';
      label.innerHTML = '<b>Clip ' + clipCount + ' <span class="w3-small" style="opacity:.7">(optional)</span></b>';
      slots.appendChild(label);
      slots.appendChild(document.createElement('br'));
      slots.appendChild(newSelect);
    }
    </script>
  </body>
</html>
