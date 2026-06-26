# Cuts Roadmap

## In progress / planned

## Backlog

### ~~Advanced yt-dlp downloader~~ (done)

**Pages:** `ytdlpAdvanced.php` → `ytdlpAdvancedFormats.php` → `ytdlpAdvancedDownload.php`

### Advanced yt-dlp downloader (new flow, 3 pages)

Goal: let the user pick exact quality/filesize before downloading, and optionally embed subtitles.

**Page 1 — URL entry** (`ytdlpAdvanced.php`)
- Single URL text input, submit button
- POSTs to page 2

**Page 2 — Format picker** (`ytdlpAdvancedFormats.php`)
- Runs `yt-dlp -J --no-download <url>` (returns JSON — easier to parse than `-F` text)
- `json_decode()` the output; pull the `formats` array
- Render two tables side by side:
  - **Video** (rows where `acodec == "none"`): columns → ID, resolution, fps, codec, filesize
  - **Audio** (rows where `vcodec == "none"`): columns → ID, bitrate (tbr), codec, filesize
  - Sort each table by filesize descending so "best quality" is at the top
- User picks one video format + one audio format (radio buttons or `<select>`)
- Subtitle section:
  - Checkbox: "Include subtitles"
  - Radio: Soft (embedded track) / Hard (burned in)
  - Text field: language code (default `en`)
- POSTs format IDs + subtitle prefs to page 3

**Page 3 — Download** (`ytdlpAdvancedDownload.php`)
- Build yt-dlp command: `yt-dlp -f "[vid_id]+[aud_id]" -o "uploads/..."  <url>`
- yt-dlp auto-merges via ffmpeg — output will be mkv or mp4
- Subtitle handling:
  - **Soft**: add `--write-subs --sub-langs [lang] --embed-subs` to the yt-dlp command
  - **Hard**: add `--write-subs --sub-langs [lang]` to yt-dlp, then run a second ffmpeg pass:
    `ffmpeg -i input.mkv -vf subtitles=input.[lang].vtt -c:a copy output_burned.mp4`
- Show resulting `<video>` element when done

**Implementation notes**
- Filesize from yt-dlp JSON is in bytes; display as MB (divide by 1048576, 1 decimal place)
- Some formats have `filesize: null` — show "~" and use `filesize_approx` if available, else "unknown"
- Hard subs require knowing the subtitle filename yt-dlp writes; use `--write-subs` output path pattern to predict it
- Validate all POST values before shell use: `escapeshellarg()` on URL, whitelist format IDs to `preg_match('/^\d+$/')`, whitelist lang to `preg_match('/^[a-z]{2,5}$/')`

- [x] Switch from `youtube-dl` to `yt-dlp`
- [x] Auto-update removed — nginx runs as `message+`, not `evan` (who owns the binary). Update manually with `yt-dlp --update`.
- [x] Fix command injection in trim and YouTube download handlers (sanitize/validate all shell inputs)
- [ ] Make filename selection a dropdown/clickable list instead of a manual text field
- [ ] Implement Extract audio (ffmpeg `-vn`)
- [ ] Implement Combine clips (ffmpeg concat)
- [ ] Fix "Trim video" heading in `trimAudioResult.php`
- [ ] Fix "Upload Video" label on audio upload submit button
- [ ] Delete orphaned `clicktext.php`
