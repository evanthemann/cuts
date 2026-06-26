<?php
$youtubeUrl  = $_POST['youtubeUrl'] ?? '';
$vidFormat   = $_POST['vid_format'] ?? '';
$audFormat   = $_POST['aud_format'] ?? '';
$formatMode  = ($_POST['format_mode'] ?? 'separate') === 'combined' ? 'combined' : 'separate';
$subsEnabled = isset($_POST['subs']);
$subsMode    = ($_POST['subs_mode'] ?? 'soft') === 'hard' ? 'hard' : 'soft';
$subsLang    = $_POST['subs_lang'] ?? 'en';

$vidOk = preg_match('/^\d+$/', $vidFormat);
$audOk = $formatMode === 'combined' || preg_match('/^\d+$/', $audFormat);

if (!$youtubeUrl || !$vidOk || !$audOk) {
    die('<div class="w3-panel w3-red">Invalid input.</div>');
}
if (!preg_match('/^[a-z]{2,20}(-[a-zA-Z0-9]{2,8})*$/', $subsLang)) {
    $subsLang = 'en';
}

$ytdlp = '/usr/local/bin/yt-dlp';

// Fetch title for a meaningful filename (fast, synchronous)
$rawTitle  = trim((string) shell_exec($ytdlp . ' --get-filename --no-playlist -o "%(title)s" ' . escapeshellarg($youtubeUrl) . ' 2>/dev/null'));
$titleBase = preg_replace('/_+/', '_', preg_replace('/[^a-zA-Z0-9_\-]/', '_', $rawTitle));
$titleBase = trim($titleBase, '_');
if (empty($titleBase)) $titleBase = 'ytdlp_' . time();
if (strlen($titleBase) > 120) $titleBase = substr($titleBase, 0, 120);

$jobId      = uniqid('job_');
$logFile    = __DIR__ . '/uploads/' . $jobId . '.log';
$paramsFile = __DIR__ . '/uploads/' . $jobId . '_params.json';

file_put_contents($paramsFile, json_encode([
    'url'        => $youtubeUrl,
    'vidFormat'  => $vidFormat,
    'audFormat'  => $audFormat,
    'formatMode' => $formatMode,
    'subsEnabled' => $subsEnabled,
    'subsMode'   => $subsMode,
    'subsLang'   => $subsLang,
    'outputBase' => __DIR__ . '/uploads/' . $titleBase,
]));

shell_exec('php ' . escapeshellarg(__DIR__ . '/ytdlpWorker.php') . ' ' . escapeshellarg($paramsFile) . ' >> ' . escapeshellarg($logFile) . ' 2>&1 &');

header('Location: progress.php?job=' . urlencode($jobId));
exit;
