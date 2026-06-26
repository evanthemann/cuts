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

## Notes

- All files are stored in `uploads/` — use the Empty folder button to clear it
- The web server user doesn't have write access to `/usr/local/bin`, so yt-dlp must be updated manually
- Playwright (`npm install -g playwright --prefix ~/.local`) is used for automated UI testing
