#!/usr/bin/env bash

set -euo pipefail

WATCH_DIRS=(
  "routes"
  "app/Http/Controllers"
  "app/Http/Requests"
  "addons"
)

command -v fswatch >/dev/null 2>&1 || {
  echo "fswatch is required for docs-watch. Install with: brew install fswatch" >&2
  exit 1
}

echo "Watching for changes to regenerate API docs..."
fswatch -o "${WATCH_DIRS[@]}" | while read -r _; do
  echo "Changes detected. Regenerating docs..."
  composer docs:generate || echo "Docs generation failed"
done

