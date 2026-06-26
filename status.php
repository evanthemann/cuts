<?php

// Delete an orphaned job file if requested
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_file'])) {
    $name = basename($_POST['delete_file']);
    $path = __DIR__ . '/uploads/' . $name;
    if (file_exists($path) && is_file($path)) unlink($path);
    header('Location: status.php');
    exit;
}

function getProcs($name) {
    $out  = shell_exec('ps aux | grep ' . escapeshellarg($name) . ' | grep -v grep | grep -v status.php') ?? '';
    $rows = [];
    foreach (explode("\n", trim($out)) as $line) {
        if (!$line) continue;
        $cols = preg_split('/\s+/', $line, 11);
        if (count($cols) < 11) continue;
        if ($cols[0] !== 'www-data') continue; // only show cuts-owned processes
        $rows[] = [
            'user'    => $cols[0],
            'pid'     => (int)$cols[1],
            'cpu'     => $cols[2],
            'mem'     => $cols[3],
            'elapsed' => $cols[9],
            'cmd'     => $cols[10],
        ];
    }
    return $rows;
}

$ytdlpProcs  = getProcs('yt-dlp');
$ffmpegProcs = getProcs('ffmpeg');

// Orphaned job files in uploads/
$jobFiles = array_merge(
    glob(__DIR__ . '/uploads/fmt_*') ?: [],
    glob(__DIR__ . '/uploads/job_*.log') ?: [],
    glob(__DIR__ . '/uploads/job_*_params.json') ?: []
);

?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>Cuts – Status</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <?php include 'darkHead.php'; ?>
    <meta http-equiv="refresh" content="5">
  </head>
  <body>
    <div class="w3-container w3-padding-24">
      <h1>Cuts</h1>
      <p class="w3-text-grey w3-small">Auto-refreshes every 5 seconds.</p>

      <?php
        function procTable($procs, $label) {
            if (empty($procs)) {
                echo '<p class="w3-text-grey">None.</p>';
                return;
            }
            $pids    = implode(' ', array_column($procs, 'pid'));
            $killCmd = 'sudo kill ' . $pids;
      ?>
          <div class="w3-panel w3-pale-yellow w3-border w3-small" style="margin-bottom:12px">
            Web kill buttons may not work (process ownership). Use terminal:
            <code id="kill-cmd-<?= htmlspecialchars($label) ?>" style="margin-left:6px;font-size:13px"><?= htmlspecialchars($killCmd) ?></code>
            <button class="w3-button w3-black w3-small w3-margin-left"
              onclick="navigator.clipboard.writeText('<?= htmlspecialchars($killCmd, ENT_QUOTES) ?>');this.textContent='Copied!';setTimeout(()=>this.textContent='Copy',1500)">Copy</button>
          </div>
          <div class="w3-responsive">
            <table class="w3-table w3-striped w3-bordered w3-small">
              <thead><tr class="w3-dark-grey"><th>PID</th><th>User</th><th>CPU%</th><th>MEM%</th><th>Elapsed</th><th>Command</th><th></th></tr></thead>
              <tbody>
                <?php foreach ($procs as $p): ?>
                <tr>
                  <td><?= $p['pid'] ?></td>
                  <td><?= htmlspecialchars($p['user']) ?></td>
                  <td><?= $p['cpu'] ?></td>
                  <td><?= $p['mem'] ?></td>
                  <td><?= htmlspecialchars($p['elapsed']) ?></td>
                  <td style="max-width:460px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= htmlspecialchars($p['cmd']) ?>"><?= htmlspecialchars($p['cmd']) ?></td>
                  <td>
                    <button class="w3-button w3-black w3-small"
                      onclick="navigator.clipboard.writeText('sudo kill <?= $p['pid'] ?>');this.textContent='Copied!';setTimeout(()=>this.textContent='Copy',1500)">Copy</button>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
      <?php } ?>

      <!-- yt-dlp -->
      <div class="w3-card w3-padding w3-margin-bottom">
        <h3>yt-dlp processes
          <span class="w3-tag <?= count($ytdlpProcs) ? 'w3-orange' : 'w3-green' ?> w3-small w3-margin-left"><?= count($ytdlpProcs) ?> running</span>
        </h3>
        <?php procTable($ytdlpProcs, 'ytdlp'); ?>
      </div>

      <!-- ffmpeg -->
      <div class="w3-card w3-padding w3-margin-bottom">
        <h3>ffmpeg processes
          <span class="w3-tag <?= count($ffmpegProcs) ? 'w3-orange' : 'w3-green' ?> w3-small w3-margin-left"><?= count($ffmpegProcs) ?> running</span>
        </h3>
        <?php procTable($ffmpegProcs, 'ffmpeg'); ?>
      </div>

      <!-- Orphaned job files -->
      <div class="w3-card w3-padding w3-margin-bottom">
        <h3>Orphaned job files
          <span class="w3-tag <?= count($jobFiles) ? 'w3-orange' : 'w3-green' ?> w3-small w3-margin-left"><?= count($jobFiles) ?></span>
        </h3>
        <?php if (empty($jobFiles)): ?>
          <p class="w3-text-grey">None.</p>
        <?php else: ?>
          <table class="w3-table w3-striped w3-bordered w3-small">
            <thead><tr class="w3-dark-grey"><th>File</th><th>Size</th><th>Age</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($jobFiles as $jf):
                $name = basename($jf);
                $size = file_exists($jf) ? round(filesize($jf) / 1024, 1) . ' KB' : '?';
                $age  = file_exists($jf) ? round((time() - filemtime($jf)) / 60) . 'm ago' : '?';
              ?>
              <tr>
                <td><?= htmlspecialchars($name) ?></td>
                <td><?= $size ?></td>
                <td><?= $age ?></td>
                <td>
                  <form method="post" style="margin:0">
                    <input type="hidden" name="delete_file" value="<?= htmlspecialchars($name) ?>">
                    <button class="w3-button w3-red w3-small" type="submit">Delete</button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>

      <?php include 'backToHomeButton.php'; ?>
    </div>
  </body>
</html>
