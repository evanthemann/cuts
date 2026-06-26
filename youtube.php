<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Cuts – YouTube</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
      <?php include 'darkHead.php'; ?>
  </head>
  <body>
    <div class="w3-container w3-padding-24">
      <h1>Cuts</h1>
      <div class="w3-card w3-padding w3-teal" style="max-width:640px">
        <h2 class="w3-monospace">YouTube download</h2>

        <!-- Mode toggle -->
        <div class="w3-bar w3-margin-bottom">
          <button id="tab-simple"   class="w3-bar-item w3-button w3-white w3-text-teal w3-round-left"  onclick="showTab('simple')">Simple</button>
          <button id="tab-advanced" class="w3-bar-item w3-button w3-round-right" style="opacity:0.65" onclick="showTab('advanced')">Advanced</button>
        </div>

        <!-- Simple pane -->
        <div id="pane-simple">
          <p>Quick download — best available quality, saved as MP4.</p>
          <form action="youtubeDownload2.php" method="post">
            <input class="w3-input w3-round w3-margin-bottom" type="text" name="youtubeUrl"
                   placeholder="https://www.youtube.com/watch?v=...">
            <input class="w3-button w3-white w3-text-teal w3-round" type="submit" value="Download">
          </form>
        </div>

        <!-- Advanced pane -->
        <div id="pane-advanced" style="display:none">
          <p>Fetch all available streams, pick exact video + audio quality, and optionally embed or burn in subtitles.</p>
          <form action="ytdlpFetchFormats.php" method="post">
            <input class="w3-input w3-round w3-margin-bottom" type="text" name="youtubeUrl"
                   placeholder="https://www.youtube.com/watch?v=...">
            <input class="w3-button w3-white w3-text-teal w3-round" type="submit" value="Get available formats">
          </form>
        </div>

        <?php include 'backToHomeButton.php'; ?>
      </div>
    </div>

    <script>
      function showTab(tab) {
        ['simple', 'advanced'].forEach(function(t) {
          var pane = document.getElementById('pane-' + t);
          var btn  = document.getElementById('tab-' + t);
          var active = t === tab;
          pane.style.display = active ? 'block' : 'none';
          btn.style.opacity  = active ? '1' : '0.65';
          if (active) {
            btn.classList.add('w3-white', 'w3-text-teal');
          } else {
            btn.classList.remove('w3-white', 'w3-text-teal');
          }
        });
      }
    </script>
  </body>
</html>
