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

### ~~Upload progress / async upload~~ (done)

**The short answer: the file transfer itself can't be detached** — PHP has to receive the full file before it can do anything with it. That part is inherently synchronous.

**What can be improved:**

1. **JS XHR/fetch upload with a progress bar** — instead of a plain form submit (which shows nothing until done), use `XMLHttpRequest` or `fetch` with an `upload.onprogress` handler. Show a live `X MB / Y MB` or percentage bar while the bytes are transferring. On completion, redirect to index.php. This is the main win — large uploads currently look frozen.

2. **Post-upload processing detached** — if we ever add post-upload steps (ffprobe scan, thumbnail generation), those can be fired as background jobs using the existing async pattern once the file is on disk. Currently there's nothing to detach since upload.php just moves the file and redirects.

3. **Investigate PHP upload timeout** — check whether `upload.php` has a timeout risk for large files. Relevant php.ini limits: `max_execution_time` (script timeout), `max_input_time` (time allowed to receive POST data — this is the one that kills uploads), `upload_max_filesize`, and `post_max_size`. A 1GB upload on a slow connection could easily hit `max_input_time` (default 60s). May need `@ini_set('max_input_time', -1)` at the top of `upload.php` similar to the `set_time_limit(0)` already used on download pages.

**Verdict:** fully doable. The upload transfer is always synchronous by nature (PHP must receive the file), but a JS progress bar during the transfer is totally doable and would be the real UX win for large files.

### ~~Branding — Cuts icon~~ (done)

A custom favicon/app icon: scissors + film strip as the base shape, with a small download arrow at the top-right suggesting internet connectivity ("smart" / "connected"). Should read well at 32×32 (favicon) and 192×192 (PWA icon).

Deliverables: `favicon.ico`, `icon-192.png`, `icon-512.png`. Add `<link rel="icon" href="favicon.ico">` to `darkHead.php` so it applies everywhere. Optionally add a PWA manifest (`site.webmanifest`) so it installs nicely on mobile home screens.

The favicon is the quick win here — even a simple 32×32 scissors icon stops the browser showing a blank/generic tab icon.

### ~~Rename files~~ (done)

Inline rename for files in the homepage table. Click a filename (or an edit icon), it becomes an editable text input, user types the new name and confirms. PHP backend renames the file plus any sidecar files (`.jpg`, `.vtt`, thumb) that share the same base name.

### ~~Job history~~ (done)

Show the last N completed jobs on `status.php` as a readable history log.

**Approach:** each result PHP file appends a JSON line to `uploads/job_history.json` when the job reaches a terminal state (done/failed/cancelled). One record per job:

```json
{"time": 1719445200, "op": "combine", "inputs": ["a.mp4", "b.mp4"], "output": "combined_xyz.mp4", "status": "done"}
```

`status.php` reads the last 10 lines of that file and renders a table: timestamp, operation, input(s), output (linked if file still exists), status badge. Raw log files (ffmpeg stats noise) are not kept — the structured record has everything worth knowing.

Operations to instrument: trim, trim audio, extract audio, combine, yt-dlp download.

### Homepage layout — tools first, files beneath

Move the Tools section above the Your Files table so the app's capabilities are immediately visible without scrolling. Import (Download, Upload) placement is TBD — it may stay as its own section or get folded in differently.

**Needs a design deep-dive before building.** Open questions:
- Where does Import live? Its own section above tools? Collapsed? Part of the tools grid?
- Should Generate get a placeholder card or stay hidden until built?
- Does the file table need a header/summary row (total size, file count) when it moves lower?
- Mobile: does tools-first still feel right when the file list is what you use most after importing?

Before implementing, sketch out 2-3 layout options and pick one.

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

### ~~Cancel running job~~ (done)

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

### ~~Progress bar on processing page~~ (done)

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

### ~~Visual trim scrubber~~ (done)

Add a timeline scrubber to the trim page to help users find exact timestamps without guessing.

**UI:**
- A horizontal scrubber (range input) spanning the full duration of the selected file
- As the user drags, a `<video>` element scrubs to that position showing the current frame
- Below the scrubber: a read-only `HH:MM:SS.mmm` timestamp display
- A readonly text input showing the current `HH:MM:SS.mmm` timestamp — user can manually select and copy from it on any device
- A **Copy** button next to it that attempts `navigator.clipboard.writeText()` as a convenience shortcut where supported

**Implementation notes:**
- Video element can be hidden or small (thumbnail size) — purpose is frame preview, not playback
- `input[type=range]` with `min=0 max=duration step=0.033` (one frame at 30fps); set `video.currentTime = scrubber.value` on `input` event
- Duration from `video.loadedmetadata` event: `duration = video.duration`
- Format timestamp: `Math.floor(t/3600)` hours, `Math.floor((t%3600)/60)` minutes, `(t%60).toFixed(3)` seconds
- No server-side changes needed — pure JS on the existing trim form
- Works for both video trim and audio trim (audio scrubber shows waveform or just time; skip the frame preview for audio-only files)

### ~~Unified trim page~~ (done)

Merge `trim.php` and `trimAudio.php` into a single page with two tabs — **Video** and **Audio** — using w3.css tab pattern. Same file selector, same start/end inputs; tab selection determines which ffmpeg command runs (`-c:v copy -c:a copy` vs `-c:a copy -vn`). Reduces clutter on the homepage and makes the tool feel like one coherent operation.

### Fix trim — frozen frame at start of output (keyframe alignment bug)

**Symptom:** Trimmed clips start with a frozen or garbled frame for a moment before playback catches up.

**Why it happens:** The current trim uses `-c:v copy` (stream copy — no re-encode). Video is stored as **Groups of Pictures (GOPs)**: I-frames (full keyframes) followed by P-frames and B-frames that only store differences from nearby frames. If the trim start point falls between keyframes, the output stream starts with a P or B frame that references a missing I-frame. Most players show a frozen or corrupt frame until the next I-frame appears.

**Fix options:**

1. **Re-encode mode (recommended):** Drop `-c:v copy`, use `-c:v libx264 -c:a aac` (or `-c:v libx264 -c:a copy`). ffmpeg re-encodes from the exact start point, guaranteeing the output opens on an I-frame. Slower but clean output. For short clips this is imperceptible; for long clips it's noticeable but still acceptable.

2. **Smart copy (complex, not worth it here):** Two-pass trim — seek to the keyframe before the start, re-encode only the first GOP, stream-copy the rest. Minimizes encode time but adds significant complexity.

3. **MoviePy:** Uses ffmpeg under the hood but re-encodes by default — eliminates the keyframe problem. Adds a Python worker dependency for no net gain over option 1 (it's still just libx264 doing the work). **Not worth the added complexity for this bug alone.**

**Verdict:** Change the default to re-encode (`libx264`). Keep stream copy available as a power-user toggle with an explicit warning that the start frame may freeze. Most users should never need stream copy.

**Implementation:**
- `trimresult.php`: default to `-c:v libx264 -c:a aac`; read a `fast` POST checkbox — if checked, fall back to `-c:v copy -c:a copy`
- Add to trim form: `<label><input type="checkbox" name="fast"> Fast mode (stream copy) — may freeze on first frame</label>`
- Add `CUTS_TOTAL_DURATION` to the re-encode path so the progress bar works; stream-copy path can skip it (finishes too fast to matter)
- `trimAudioResult.php` (if separate): `-c:a copy -vn` is already fine — audio-only trim has no keyframe issue

### ~~Make GIF~~ (done, two known bugs below)

Export a clip as an animated GIF. Only available for video files 10 seconds or shorter (enforced both in the UI — only shown in the actions dropdown for short clips — and server-side as a hard reject).

Inputs: file, optional start/end time (scrubber UI). Fixed output: 480px wide, 15 fps. ffmpeg two-pass approach for decent quality at small file size:

```bash
ffmpeg -i input.mp4 -vf "fps=15,scale=480:-1:flags=lanczos,palettegen" palette.png
ffmpeg -i input.mp4 -i palette.png -filter_complex "[0:v]fps=15,scale=480:-1:flags=lanczos[x];[x][1:v]paletteuse" output.gif
```

Output goes through the existing progress.php job system. The two-pass nature means the result PHP file runs pass 1 synchronously (palettegen is fast), then launches pass 2 as the background job.

**Known bugs:**

- **Results page shows `<video>` for GIF output** — `progress.php` detects the output file and renders it in a `<video>` element, but GIFs don't work in `<video>`. Fix: detect `.gif` extension in `progress.php` and render an `<img>` tag instead. Simple extension check before the player block.

- **Output starts at nearest I-frame instead of exact start time** — same root cause as the trim keyframe bug above. The `-ss` seek snaps to the nearest keyframe before the requested time, so the GIF may include a few frames before the intended start. This will be fixed automatically when the keyframe fix lands: switch pass 1 and pass 2 to use slow-seek (`-ss` after `-i`) or force a re-decode from the exact timestamp. Low priority since GIFs are short and the drift is usually < 0.5s.

### Fix subtitle display — rolling/append format from YouTube

**Symptom:** Burned-in or embedded subtitles cycle awkwardly: a top line appears, then a bottom line, then the top line is replaced, then the bottom line is replaced — rather than showing clean subtitle blocks.

**Why it happens:** YouTube auto-generated captions (downloaded via yt-dlp as `.vtt` or `.srt`) use a **rolling/append format** — each cue shows the full visible caption window at that moment, building up line by line:

```
1: "First phrase"
2: "First phrase\nSecond phrase"   ← first appears at top, second at bottom
3: "Second phrase\nThird phrase"   ← top line swapped out
4: "Third phrase\nFourth phrase"   ← etc.
```

When burned in, viewers see lines flickering in and out rather than clean sentence-level subtitles.

**Fix:** Post-process the downloaded `.srt`/`.vtt` before passing it to ffmpeg. Strip duplicate/overlapping lines and merge overlapping cues into clean non-overlapping blocks. A small PHP or Python script can do this:

1. Parse all SRT cues
2. For each cue, keep only the *new* bottom line (the one not present in the previous cue) — or deduplicate by tracking which lines have already been shown
3. Re-number and write clean SRT

**Where to hook it in:** `downloadResult.php` (or whichever file handles the yt-dlp subtitle workflow) — run the cleanup immediately after yt-dlp writes the `.srt`, before the ffmpeg burn step. Clean SRT replaces the original in place.

**Note:** Manual/human-written subtitles downloaded from YouTube typically don't have this problem — they're already in block format. The rolling format is specific to auto-generated captions.

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

### Docker — containerized deployment

Package the app as a Docker image so it can be spun up anywhere with a single command — no PHP/ffmpeg/yt-dlp setup on the host.

**What goes in the image:**
- Base: `php:8.x-apache` (official image, includes Apache + PHP)
- System packages: `ffmpeg`, `python3-pip` → `yt-dlp`, `ffprobe` (bundled with ffmpeg)
- Optional: `openai-whisper` if generate-subtitles lands first
- App files copied to `/var/www/html/`
- `uploads/` mounted as a named volume so files persist across container restarts

**Dockerfile sketch:**
```dockerfile
FROM php:8.2-apache
RUN apt-get update && apt-get install -y ffmpeg python3 python3-pip \
    && pip3 install yt-dlp
COPY . /var/www/html/
RUN mkdir -p /var/www/html/uploads && chmod 777 /var/www/html/uploads
VOLUME ["/var/www/html/uploads"]
EXPOSE 80
```

**docker-compose.yml** for local use:
```yaml
services:
  cuts:
    build: .
    ports:
      - "8080:80"
    volumes:
      - cuts_uploads:/var/www/html/uploads
volumes:
  cuts_uploads:
```

**Open questions before building:**
- PHP config overrides (`upload_max_filesize`, `post_max_size`, `max_input_time`) — bake into a custom `php.ini` or use `ini_set()` at runtime?
- Does the `posix` PHP extension need to be explicitly enabled in the official image? (Required for cancel-job feature.)
- `.htaccess` / `mod_rewrite` — ensure `AllowOverride All` is set in the Apache config.
- Whisper install makes the image ~5GB; make it optional (separate `Dockerfile.full`).

**Stretch:** push the image to Docker Hub so anyone can `docker pull evanmann/cuts` and run it immediately.

### Public hosting — launch Cuts for others to use

Deploy Cuts to a public URL so anyone can try it without self-hosting, and optionally monetize or accept support.

**Hosting options (ranked by fit):**

| Option | Fit | Notes |
|---|---|---|
| **DigitalOcean Droplet** | Best | $6/mo, full control, easy Docker deploy, no vendor lock-in |
| **Fly.io** | Great | Free tier generous, Docker-native, auto-scales to zero |
| **AWS Lightsail** | Good | Fixed-price VPS like DO, familiar if you know AWS |
| **Render** | OK | Docker support, but persistent disks cost extra |
| **Bluehost shared** | Poor | PHP-only, no ffmpeg, no Docker — doesn't work for this app |

**Recommended path:** DigitalOcean Droplet ($6/mo, 1 vCPU / 1GB RAM) + the Docker container above. Attach a $1/mo block storage volume for uploads. Point a domain at it. Done.

**Concerns to solve before going public:**

- **Abuse / storage**: anonymous users will upload and never clean up. Need either: (a) user accounts with quotas, or (b) a TTL cron job that deletes files older than 24–48 hours from `uploads/`. The cron approach is simpler and probably right for a public free tool.
- **Concurrent ffmpeg jobs**: a single $6 droplet will fall over with 3+ simultaneous encodes. Options: queue jobs (one at a time), or upgrade the droplet. A job queue (flat file or Redis) is the clean fix but adds complexity.
- **yt-dlp ToS exposure**: downloading YouTube videos is a legal grey area. A public instance might attract DMCA attention. Either add a disclaimer, restrict to non-YouTube URLs, or keep it invite-only initially.
- **HTTPS**: Caddy or Certbot + Let's Encrypt. Caddy is easiest — single binary, auto-renews, works as a reverse proxy in front of the PHP/Apache container.

**Monetization / support options:**

- **Buy Me a Coffee** — drop a BMC button in the footer (`buymeacoffee.com`). Zero friction, no payment processing to manage. Good for a free tool.
- **Ko-fi** — same idea, slightly more customizable.
- **Patreon** — overkill unless there's a content angle.
- **Self-hosted / bring-your-own-server** framing — market it as a tool devs deploy themselves; Docker image is the product, no hosting costs for you.

**Minimum to launch publicly:**
1. Docker image builds and runs cleanly (see above)
2. TTL cleanup cron (delete uploads > 48h)
3. HTTPS via Caddy
4. Buy Me a Coffee button in footer
5. A landing page or README explaining what it is
