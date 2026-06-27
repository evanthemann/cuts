# Cuts

A personal PHP video editing tool that runs in the browser and wraps ffmpeg and yt-dlp.

## Features

- **YouTube import** — quick download or advanced format picker (144p to 4K, choose exact video + audio stream)
- **Subtitle support** — soft embed (togglable track) or hard burn-in with language selection; auto-upscales to 480p before burn if source is ≤360p
- **Upload** — video or audio files up to 1GB
- **Trim** — video and audio on one tabbed page; visual scrubber with frame preview, play/pause, ±1f/±30f nudge, and → Start / → End buttons
- **Extract audio** — pull the audio track from a video as MP3 or copy-codec
- **Combine clips** — join any number of clips in order; fast copy mode or re-encode for mixed formats; quality reference picker (auto picks highest resolution)
- **Cancel** — Cancel button on the progress page kills the background ffmpeg process mid-run
- **Progress bar** — time-based percent-complete bar during encoding, updates every 3 seconds
- **Dashboard** — homepage shows all files with ffprobe metadata (duration, resolution, size), inline rename, and one-click quick actions
- **Job history** — recent jobs logged to `uploads/job_history.ndjson`, visible on status.php

## Dependencies

- PHP 8+
- ffmpeg (`/usr/bin/ffmpeg`)
- yt-dlp (`/usr/local/bin/yt-dlp`) — update periodically with `yt-dlp --update`
- Node.js — required by yt-dlp for YouTube JS extraction (`--js-runtimes node`)

## Setup

1. Clone into your web root
2. Make `uploads/` writable by the web server user
3. Ensure ffmpeg and yt-dlp are installed at the paths above

## Async job pattern

Every operation that triggers a shell command (ffmpeg, yt-dlp) follows the same pattern. **All new features must use this pattern** — never block the HTTP request waiting for a shell process to finish.

### How it works

```
[form] → [*Result.php / *Download.php]
              │
              ├─ writes CUTS_TOTAL_DURATION / CUTS_OP / CUTS_INPUTS / CUTS_OUTPUT sentinels to log
              ├─ fires shell process in background, captures PID to .pid sidecar
              ├─ header('Location: progress.php?job=ID')
              └─ exit
                    │
              [progress.php]
                    ├─ reads uploads/job_ID.log every 3s (meta refresh)
                    ├─ shows progress bar (time= from ffmpeg -stats vs CUTS_TOTAL_DURATION)
                    ├─ shows Cancel button → cancelJob.php kills PID, writes CUTS_CANCELLED
                    ├─ CUTS_DONE:uploads/filename  → show video/audio player + download link
                    ├─ CUTS_FAIL                   → show error + log
                    ├─ CUTS_CANCELLED              → show cancelled state
                    └─ on terminal state: writes job_history.ndjson, deletes log + sidecar files
```

### Sentinel values written to the log

| Sentinel | Written by | Meaning |
|---|---|---|
| `CUTS_TOTAL_DURATION:N` | result PHP | Total seconds of expected output — used for progress bar |
| `CUTS_OP:name` | result PHP | Operation name (`trim`, `trim_audio`, `extract_audio`, `combine`, `download`) |
| `CUTS_INPUTS:a,b` | result PHP | Comma-separated input filenames — used for job history |
| `CUTS_OUTPUT:filename` | result PHP | Expected output filename — used for job history |
| `CUTS_DONE:uploads/filename` | shell / worker | Job succeeded; `progress.php` shows the result file |
| `CUTS_FAIL` | shell / worker | Job failed; `progress.php` shows an error and the log |
| `CUTS_CANCELLED` | cancelJob.php | User cancelled; shell checks for this before writing CUTS_DONE/FAIL |
| `CUTS_PROBE_START` | shell (combine) | Everything after this line is ffprobe output explaining a copy-mode mismatch |

### Three implementation variants

**1. Inline bash subshell** — used by ffmpeg operations (trim, extract audio, combine):
```php
$bgCmd = '(' . $ffmpegCmd . ' >> ' . escapeshellarg($logFile) . ' 2>&1 & _FFPID=$!; echo $_FFPID > ' . escapeshellarg($pidFile)
       . '; wait $_FFPID; rm -f ' . escapeshellarg($pidFile)
       . '; if grep -q CUTS_CANCELLED ' . escapeshellarg($logFile) . ' 2>/dev/null; then :'
       . '; elif [ -s ' . escapeshellarg($outputPath) . ' ]; then echo ' . escapeshellarg('CUTS_DONE:' . $outputWeb) . ' >> ' . escapeshellarg($logFile)
       . '; else echo CUTS_FAIL >> ' . escapeshellarg($logFile) . '; fi) > /dev/null 2>&1 &';
shell_exec($bgCmd);
header('Location: progress.php?job=' . urlencode($jobId));
exit;
```

**Critical:** the subshell must end with `) > /dev/null 2>&1 &`, not just `) &`. Without `> /dev/null`, the subshell inherits PHP's stdout pipe and `shell_exec` blocks until ffmpeg finishes — the HTTP response never arrives. The inner `>> $logFile` redirects still work correctly; `> /dev/null` only closes the pipe so PHP can return immediately.

The PID capture pattern (`& _FFPID=$!; echo $_FFPID > pidfile; wait $_FFPID; rm -f pidfile`) is required so `cancelJob.php` can kill the process mid-run. Always include it. Use `-s` (non-empty file), not `-f`, to detect output — ffmpeg can create an empty file on failure.

**2. PHP CLI worker** — used when the background logic is too complex for a bash one-liner (yt-dlp advanced download, quick download):
```php
shell_exec('php ' . escapeshellarg(__DIR__ . '/myWorker.php') . ' ' . escapeshellarg($paramsFile)
    . ' >> ' . escapeshellarg($logFile) . ' 2>&1 &');
header('Location: progress.php?job=' . urlencode($jobId));
exit;
```
The `>> $logFile` redirect closes the pipe before the worker starts, so this pattern does not need `> /dev/null`. The worker script echoes `CUTS_DONE:path` or `CUTS_FAIL` to stdout (redirected to the log by the caller).

**3. Synchronous pre-fetch then background** — used when a fast synchronous step is needed before backgrounding (e.g. fetching video title or probing clip resolution):
```php
$info = shell_exec($ffprobe . ' ... ' . escapeshellarg($clip)); // fast, < 200ms
// use $info to build the background command, then fire it
$bgCmd = '(...) > /dev/null 2>&1 &';
shell_exec($bgCmd);
header('Location: progress.php?job=' . urlencode($jobId));
exit;
```
Keep synchronous steps under ~500ms. Anything longer belongs in the worker.

### Job ID and log file naming

```php
$jobId   = uniqid('job_');                           // e.g. job_6a3fe717286c9
$logFile = __DIR__ . '/uploads/' . $jobId . '.log';  // always in uploads/
$pidFile = __DIR__ . '/uploads/' . $jobId . '.pid';  // deleted after job completes
```

Log and sidecar files are automatically deleted by `progress.php` when a terminal state is reached. Do not rely on them persisting after the job is done — use `job_history.ndjson` for durable job records.

## Notes

- All files are stored in `uploads/` — use the Empty folder button to clear it
- The web server user doesn't have write access to `/usr/local/bin`, so yt-dlp must be updated manually
- `status.php` shows running ffmpeg/yt-dlp processes and recent job history
- Playwright (`npm install -g playwright --prefix ~/.local`) is used for automated UI testing
