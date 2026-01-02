#!/usr/bin/env bash
set -euo pipefail

url="${1:-http://localhost:8080}"
timeout="${2:-60}"
start=$SECONDS

while true; do
  if curl -fsS -o /dev/null "$url"; then
    echo "OK: $url"
    exit 0
  fi
  if (( SECONDS - start >= timeout )); then
    echo "Timed out waiting for $url" >&2
    exit 1
  fi
  sleep 2
done
