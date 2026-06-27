# Cuts Roadmap

## Done
- [x] Switch from `youtube-dl` to `yt-dlp`
- [x] Fix command injection in trim and YouTube download handlers
- [x] Advanced YouTube downloader — format picker (144p–4K), soft/hard subtitle embed
- [x] Fix ffmpeg path (`/usr/bin/ffmpeg`) — was silently failing everywhere
- [x] Fix PHP 30s timeout on download pages (`set_time_limit(0)`)
- [x] Playwright set up for automated UI testing

## Backlog

### ~~Homepage redesign~~ (done)

### Homepage redesign — dashboard layout

**Goal:** at a glance, see everything the app can do AND act on files already in the working folder.

**Two-section layout:**

**Section 1 — Import** (bring something in)
- One card/panel that groups all intake methods together:
  - YouTube URL (basic, fast)
  - YouTube URL (advanced — format picker)
  - Upload video file
  - Upload audio file
- Label this clearly: "Import a file"

**Section 2 — Your files** (work with what's here)
- Replace the plain `ls` output with a proper file table using `ffprobe` for metadata:
  - Columns: filename, duration, resolution (video) or bitrate (audio), filesize, type badge (video/audio)
  - Each row has quick-action buttons: **Trim**, **Extract audio**, **Combine** — clicking pre-selects that file in the relevant tool
- Files with no ffprobe data (e.g. srt) shown in a separate "other files" list or hidden
- "Empty folder" button stays

**Section 3 — Tools** (visible even when folder is empty)
- Cards for each operation so the app's capabilities are always visible:
  - Trim video, Trim audio, Extract audio, Combine clips
  - Each card has a one-line description of what it does

**Implementation notes:**
- `ffprobe -v error -show_entries format=duration,size:stream=width,height,codec_type -of json` gives duration + resolution + type in one call
- Duration: format as `H:MM:SS` or `M:SS`
- Resolution: `1920×1080` for video; show bitrate for audio-only files
- Quick-action links pass `?file=filename` as a GET param; trim/extract pages read it and pre-select the dropdown
- Pre-selection in dropdown: `<option selected>` when `basename($f) === $_GET['file']`
- [x] Make filename selection a dropdown/clickable list instead of manual text entry (trim pages)
- [x] Implement Extract audio (ffmpeg `-vn`)
- [x] Implement Combine clips (ffmpeg concat demuxer + filter fallback)
- [x] Fix "Trim video" heading in `trimAudioResult.php` (copy-paste bug)
- [x] Fix "Upload Video" label on audio upload submit button
- [x] Delete orphaned `clicktext.php`
- [x] Clean up loose `.srt` files after successful sub downloads (soft and hard)
- [ ] Empty uploads folder doesn't show audio upload button on its confirmation page
- [x] "Your files" table — View button per row opens file in a w3-modal (video/audio player inline)
- [x] Dark mode

### Homepage file list — actions dropdown + smart multi-select combine

**Per-row actions:**

Replace the row of buttons next to each file with just two controls: a **Delete** button and an **Actions** dropdown. The dropdown is context-aware based on media type and file properties:

- **Any video** → Trim, Extract audio
- **Any audio** → Trim audio
- **Video < 5 seconds** → also shows Make GIF (once that tool exists)
- Future tools slot in here automatically by type

Detecting duration/type: already have ffprobe metadata available in the file table — reuse it. < 5s threshold based on `format.duration` from the existing probe call.

**Multi-select combine:**

When 2+ files are checked in the homepage table, the selection bar (already exists for bulk delete) also shows a **Combine clips** button alongside Delete. Clicking it redirects to `combineClips.php` with all selected filenames passed as GET params (`?clips[]=file1.mp4&clips[]=file2.mp4`). The combine page reads those params and pre-selects the dropdowns — user still sees the full form (mode, reference clip, etc.) and hits Combine themselves.

**Implementation notes:**
- Dropdown: a `<div>` with a toggle button + absolutely-positioned list; close on outside click. w3.css `w3-dropdown-click` pattern works here.
- Pre-selecting dropdowns in `combineClips.php`: read `$_GET['clips']` and emit `selected` on the matching `<option>` for the first N slots; add extra slots if more clips are passed than the default 2.
- Only video files should appear as combine candidates (filter out audio in the selection bar check).

### Cancel running job

Add a **Cancel** button to `progress.php` that kills the background ffmpeg process mid-run.

**How it's possible without pkill:**

Capture ffmpeg's PID inside the subshell and write it to a `.pid` sidecar file:
```bash
(ffmpeg ... & FFMPEG_PID=$!; echo $FFMPEG_PID > job.pid; wait $FFMPEG_PID; ...) > /dev/null 2>&1 &
```
A `cancelJob.php` endpoint reads the `.pid` file and sends SIGTERM via `posix_kill((int)$pid, SIGTERM)` — no `pkill`, no root, just PHP's built-in POSIX function (available as long as the `posix` extension is loaded, which it typically is on Linux shared hosts).

`progress.php` shows the Cancel button only while the job is still running (i.e. not yet done/failed). On cancel: write `CUTS_CANCELLED` to the log, redirect back to progress.php which renders a "Cancelled" state.

**Edge cases to handle:**
- PID file missing (job finished before cancel was clicked) — treat as already done
- Process already gone (PID reused by OS) — `posix_kill` returns false gracefully
- Partial output file left behind — delete it in `cancelJob.php`

### Progress bar on processing page

Show a real-time percent-complete bar while ffmpeg is running.

**How it works:**

1. Before launching the background ffmpeg job, run `ffprobe` synchronously to get the total frame count of the expected output (duration × fps of the first/reference clip).
2. ffmpeg's `-stats` output writes lines like `frame= 1234 ...` to the log file — these are already being captured.
3. `progress.php` (on each 3-second meta-refresh) reads the log, finds the last `frame=` line, and computes `pct = current_frame / total_frames * 100`.
4. Render a `w3-light-grey` / `w3-blue` w3.css progress bar (`<div class="w3-grey"><div class="w3-blue" style="height:24px;width:{$pct}%"></div></div>`) plus a `{$pct}%` label.

**Scope:**
- Start with combine (re-encode mode) since that's the longest job and already uses `-stats`.
- Once working, apply to trim, extract audio — all jobs that already write to the same log pattern.
- Copy-mode combine and jobs with no video stream can skip the bar (or show indeterminate spinner).

**Notes:**
- Total frames: `ffprobe -v error -select_streams v:0 -show_entries stream=nb_frames` gives it directly for some containers; fallback is `duration × r_frame_rate` for containers that don't store it.
- Edge case: if `nb_frames` is unavailable and duration probe fails, just omit the bar and show the existing "Waiting for output…" text — don't block the job.
- The bar disappears once `CUTS_DONE` or `CUTS_FAIL` is written; the done/fail state renders as it does today.

### Generate subtitles

Automatically generate a subtitle file from a video's audio using a speech-to-text engine.

- **Whisper** (OpenAI, runs locally) — best quality, `whisper video.mp4 --output_format srt`
- Output `.srt` alongside the video in uploads, ready to use in the subtitle burn-in flow
- Could offer language selection and model size (tiny/base/small/medium/large — speed vs. accuracy tradeoff)
- Runs as a background job through the existing progress.php system
- Natural next step: after generating, offer "burn in" or "embed as soft track" immediately on the result page

### Generate (lavfi video creation)

Create videos from scratch using ffmpeg's `lavfi` virtual input device — no source file needed.

**Ideas:**
- **Waveform visualizer** — take an audio file, render animated waveform/spectrum bars as a video (`showwaves`, `showspectrum` filters)
- **Solid color / gradient backgrounds** — `color=c=black:size=1920x1080:rate=30` as a base layer
- **Text / title cards** — `drawtext` filter with custom font, size, color, position, fade in/out
- **Countdown timer** — animated countdown rendered purely in ffmpeg
- **Slideshow from images** — `concat` demuxer + `zoompan` for Ken Burns effect
- **Audio visualizer export** — combine audio + waveform render into a shareable video

**Implementation approach:**
- "Generate" page (already stubbed as coming soon on homepage) with preset options
- Each preset is a PHP function that builds the ffmpeg `lavfi` command
- Output goes through the same `progress.php` job system as other tools

### User accounts

Each person gets their own login with isolated uploads and job history.

- Login/register page (session-based auth, bcrypt passwords)
- Each user's files stored under `uploads/{user_id}/` — no cross-user visibility
- All tool pages, download workers, and progress page scoped to the logged-in user
- Admin view to see all users and their storage usage
- Logout button in the header
- Consider: single shared ffmpeg/yt-dlp queue vs. per-user concurrent jobs

### Unified trim page

Merge `trim.php` and `trimAudio.php` into a single page with two tabs — **Video** and **Audio** — using w3.css tab pattern. Same file selector, same start/end inputs; tab selection determines which ffmpeg command runs (`-c:v copy -c:a copy` vs `-c:a copy -vn`). Reduces clutter on the homepage and makes the tool feel like one coherent operation.

### Make GIF

Export a clip as an animated GIF. Inputs: file, start time, end time, optional scale/fps reduction. ffmpeg approach: two-pass with a generated palette (`palettegen` + `paletteuse`) for decent quality at small size. Output goes through the existing progress.php job system.

### Investigate frei0r filters

Audit the available frei0r plugin set (`frei0r-plugins` package) and decide which effects are worth exposing as tools or options. Candidates: color correction, blur, edge detection, film grain, chroma key. Output of this investigation: a short list of frei0r filters to build UI around.

### Investigate MoviePy integration

Survey what MoviePy can do that ffmpeg alone can't easily expose through a simple PHP/shell interface. Candidates: programmatic clip composition, text overlays with Python font rendering, speed ramping, image sequences. Output: decide whether any MoviePy capabilities are worth adding a Python worker script for, or whether ffmpeg filters cover the same ground.

### ~~Low-bandwidth subtitle workflow~~ (done)

**Problem:** Burning hard subs onto a 144p video produces ~10px text — completely unreadable.

**Insight:** Video quality can stay 144p (fast/cheap download), but subs need a larger canvas to render at readable size.

**Solution:** When hard-burn subtitles are selected and source video height ≤ 360p, upscale to 480p *before* burning, then deliver the 480p file.

- `ffmpeg -vf "scale=854:480:flags=lanczos,subtitles=file.srt" -c:a copy`
- Scale and subtitle burn in a single pass — no intermediate file
- 480p is the sweet spot: readable subs, short encode, moderate file size (upscaling a 144p source to 1080p is wasteful — looks identical, just more pixels)
- Optionally expose a "target resolution" dropdown (360p / 480p / 720p) when hard-burn + low-res source are both selected
- Detect source height via ffprobe before building the ffmpeg command; skip upscale if source is already ≥ 480p
