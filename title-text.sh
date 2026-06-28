#!/bin/bash
set -e

LOG_FILE="log.txt"
STATUS_FILE="status.txt"

log() {
  echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1"
}

title_text="$1"
bg_color="$2"
duration="$3"
font="$4"

if [ -z "$title_text" ]; then
  log "ERROR: No title text provided"
  echo "error" > "$STATUS_FILE"
  exit 1
fi

title_safe=$(echo "$title_text" | sed 's/[^a-zA-Z0-9_\-]/_/g')
output_file="files/${title_safe}.mp4"

echo "running" > "$STATUS_FILE"
log "Starting video generation"
log "Title: $title_text"
log "BG Color: $bg_color"
log "Duration: $duration"
log "Font: $font"

log "Generating cellauto video"
ffmpeg -loglevel fatal -f lavfi -i cellauto=s=1920x1080:rule=30 -t $duration -c:v libx264 cellauto.mp4 -y

log "Generating color overlay"
ffmpeg -loglevel fatal -f lavfi -i "color=c='$bg_color':s=1920x1080" -t $duration -c:v libx264 color.mp4 -y

log "Blending videos"
ffmpeg -loglevel fatal -i cellauto.mp4 -i color.mp4 \
-filter_complex "[0:v] format=rgba [bg]; [1:v] format=rgba [fg]; [bg][fg] blend=all_mode='multiply':all_opacity=1, format=rgba" \
out.mp4 -y

log "Applying blur"
ffmpeg -loglevel fatal -i out.mp4 -vf "gblur=sigma=2" outblur.mp4 -y

log "Adding title text"
ffmpeg -loglevel fatal -i outblur.mp4 \
-vf "drawtext=text='$title_text':x=(w-text_w)/2:y=(h-text_h)/2:fontsize=100:fontcolor=white:font=$font" \
"$output_file" -y

log "Cleaning up temp files"
rm -f cellauto.mp4 color.mp4 out.mp4 outblur.mp4

log "Video created: $output_file"

# HandBrake step
#hb_input="$output_file"

#hb_dir="$(dirname "$hb_input")"
#hb_base="$(basename "$hb_input" .mp4)"
#hb_output="$hb_dir/${hb_base}-2.mp4"

#log "Running HandBrake..."
#/Users/evanmann/HandBrakeCLI -v 1 \
#  --input "$hb_input" \
#  --output "$hb_output" \
#  > /dev/null 2>&1

# log "Handbrake converted: $hb_output"

echo "done" > "$STATUS_FILE"
