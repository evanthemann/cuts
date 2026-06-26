<?php
shell_exec('rm -rf ' . escapeshellarg(__DIR__ . '/uploads/') . '*');
header('Location: index.php?emptied=1');
exit;
