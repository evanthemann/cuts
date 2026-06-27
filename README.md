# Cuts

A personal PHP video editing tool that runs in the browser and wraps ffmpeg and yt-dlp.

## Features

- **YouTube import** — quick download or advanced format picker (144p to 4K, choose exact video + audio stream)
- **Subtitle support** — soft embed (togglable track) or hard burn-in, with language selection
- **Upload** — video or audio files up to 1GB
- **Trim video / trim audio** — cut clips by start and end timestamp
- **Extract audio** — pull the audio track from a video as MP3 or copy-codec
- **Combine clips** — join up to 5 clips in order; fast copy mode or re-encode for mixed formats
- **Dashboard** — homepage shows all files with ffprobe metadata (duration, resolution, size) and one-click quick actions

## Dependencies

- PHP 8+
- ffmpeg (`/usr/bin/ffmpeg`)
- yt-dlp (`/usr/local/bin/yt-dlp`) — update periodically with `yt-dlp --update`

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
              ├─ fires shell process in background (&)
              ├─ header('Location: progress.php?job=ID')
              └─ exit
                    │
              [progress.php]
                    ├─ reads uploads/job_ID.log
                    ├─ shows output lines
                    ├─ <meta refresh> every 3s until a sentinel is found
                    ├─ CUTS_DONE:uploads/filename  → show video/audio player
                    └─ CUTS_FAIL                   → show error + log
```

### Sentinel values written to the log

| Sentinel | Meaning |
|---|---|
| `CUTS_DONE:uploads/filename` | Job succeeded; `progress.php` shows the result file |
| `CUTS_FAIL` | Job failed; `progress.php` shows an error and the log |
| `CUTS_PROBE_START` | (combine, copy-mode failure only) Everything after this line is ffprobe output explaining the mismatch |

### Three implementation variants

**1. Inline bash subshell** — used by ffmpeg operations (trim, extract audio, combine):
```php
$bgCmd = '(' . $ffmpegCmd . ' >> ' . escapeshellarg($logFile) . ' 2>&1'
       . '; if [ -s ' . escapeshellarg($outputPath) . ' ]; then echo ' . escapeshellarg('CUTS_DONE:' . $outputWeb) . ' >> ' . escapeshellarg($logFile)
       . '; else echo CUTS_FAIL >> ' . escapeshellarg($logFile) . '; fi) > /dev/null 2>&1 &';
shell_exec($bgCmd);
header('Location: progress.php?job=' . urlencode($jobId));
exit;
```

**Critical:** the subshell must end with `) > /dev/null 2>&1 &`, not just `) &`. Without `> /dev/null`, the subshell inherits PHP's stdout pipe and `shell_exec` blocks until ffmpeg finishes — the HTTP response never arrives. The inner `>> $logFile` redirects still work correctly; `> /dev/null` only closes the pipe so PHP can return immediately. Use `-s` (non-empty file), not `-f`, to detect output — ffmpeg can create an empty file on failure.

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
```

The `status.php` page tracks orphaned `job_*.log` files and running ffmpeg/yt-dlp processes.

## Notes

- All files are stored in `uploads/` — use the Empty folder button to clear it
- The web server user doesn't have write access to `/usr/local/bin`, so yt-dlp must be updated manually
- Playwright (`npm install -g playwright --prefix ~/.local`) is used for automated UI testing
