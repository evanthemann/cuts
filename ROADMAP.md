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

### User accounts

Each person gets their own login with isolated uploads and job history.

- Login/register page (session-based auth, bcrypt passwords)
- Each user's files stored under `uploads/{user_id}/` — no cross-user visibility
- All tool pages, download workers, and progress page scoped to the logged-in user
- Admin view to see all users and their storage usage
- Logout button in the header
- Consider: single shared ffmpeg/yt-dlp queue vs. per-user concurrent jobs

### Low-bandwidth subtitle workflow

**Problem:** Burning hard subs onto a 144p video produces ~10px text — completely unreadable.

**Insight:** Video quality can stay 144p (fast/cheap download), but subs need a larger canvas to render at readable size.

**Solution:** When hard-burn subtitles are selected and source video height ≤ 360p, upscale to 480p *before* burning, then deliver the 480p file.

- `ffmpeg -vf "scale=854:480:flags=lanczos,subtitles=file.srt" -c:a copy`
- Scale and subtitle burn in a single pass — no intermediate file
- 480p is the sweet spot: readable subs, short encode, moderate file size (upscaling a 144p source to 1080p is wasteful — looks identical, just more pixels)
- Optionally expose a "target resolution" dropdown (360p / 480p / 720p) when hard-burn + low-res source are both selected
- Detect source height via ffprobe before building the ffmpeg command; skip upscale if source is already ≥ 480p
