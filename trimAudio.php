<?php
$qs = '?tab=audio';
if (isset($_GET['file'])) $qs .= '&file=' . urlencode(basename($_GET['file']));
header('Location: trim.php' . $qs);
exit;
