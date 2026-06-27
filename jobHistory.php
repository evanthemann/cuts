<?php
// Append one record to the job history log.
// $op     — string: 'trim', 'trim_audio', 'extract_audio', 'combine', 'download'
// $inputs — array of input filenames (basenames only)
// $output — output filename (basename) or null
// $status — 'done' | 'failed' | 'cancelled'
function logJobHistory($op, array $inputs, $output, $status) {
    $record = json_encode([
        'time'   => time(),
        'op'     => $op,
        'inputs' => $inputs,
        'output' => $output,
        'status' => $status,
    ]);
    $file = __DIR__ . '/uploads/job_history.ndjson';
    file_put_contents($file, $record . "\n", FILE_APPEND | LOCK_EX);
}

// Read the last $n records, newest first.
function readJobHistory($n = 20) {
    $file = __DIR__ . '/uploads/job_history.ndjson';
    if (!file_exists($file)) return [];
    $lines = array_filter(explode("\n", trim(file_get_contents($file))));
    $lines = array_slice(array_values($lines), -$n);
    $records = [];
    foreach (array_reverse($lines) as $line) {
        $r = json_decode($line, true);
        if ($r) $records[] = $r;
    }
    return $records;
}
