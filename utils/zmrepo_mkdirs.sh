#!/bin/bash
#
# zmrepo_mkdirs.sh - (Re)create the directory tree the RPM build workflows
# rsync into on zmrepo.
#
# The build-rpm-packages*.yml workflows publish with easingthemes/ssh-deploy
# using rsync args "-rltgoDzvO" (no --mkpath), so rsync will NOT create the
# parent directories of the target. If the tree is deleted, the deploy step
# fails. This script recreates the full tree.
#
# Target layout (matches workflow TARGET: rpm/master/<family>/<releasever>/<arch>/):
#
#   <root>/rpm/master/el/9/{x86_64,aarch64}/
#   <root>/rpm/master/el/8/{x86_64,aarch64}/
#   <root>/rpm/master/fedora/43/{x86_64,aarch64}/
#   <root>/rpm/master/fedora/42/{x86_64,aarch64}/
#   <root>/rpm/master/fedora/41/{x86_64,aarch64}/
#
# Usage:
#   # Run directly on zmrepo (root defaults to the SSH user's home, ".")
#   ./zmrepo_mkdirs.sh
#
#   # Run from anywhere over SSH (uses the same host the workflow rsyncs to)
#   ./zmrepo_mkdirs.sh --ssh user@zmrepo.example.com
#
#   # Override the repo root and/or branch
#   ROOT=/srv/www/zmrepo BRANCH=master ./zmrepo_mkdirs.sh
#
# Options:
#   --ssh <dest>   Create the tree on a remote host via ssh instead of locally.
#   --createrepo   After making dirs, run createrepo_c so empty dirs are valid
#                  yum repos (clients error on a repo with no repodata).
#   -n, --dry-run  Print what would be done without doing it.
#
set -euo pipefail

ROOT="${ROOT:-.}"
BRANCH="${BRANCH:-master}"
SSH_DEST=""
RUN_CREATEREPO=0
DRY_RUN=0

while [ $# -gt 0 ]; do
  case "$1" in
    --ssh)        SSH_DEST="$2"; shift 2 ;;
    --createrepo) RUN_CREATEREPO=1; shift ;;
    -n|--dry-run) DRY_RUN=1; shift ;;
    -h|--help)    grep '^#' "$0" | sed 's/^# \{0,1\}//'; exit 0 ;;
    *) echo "Unknown option: $1" >&2; exit 1 ;;
  esac
done

# Build matrix, mirrors the matrix in .github/workflows/build-rpm-packages*.yml
# Format: "family releasever"
DISTROS=(
  "el 9"
  "el 8"
  "fedora 43"
  "fedora 42"
  "fedora 41"
)
ARCHES=(x86_64 aarch64)

# Assemble the list of directories to create.
DIRS=()
for d in "${DISTROS[@]}"; do
  read -r family releasever <<< "$d"
  for arch in "${ARCHES[@]}"; do
    DIRS+=("${ROOT}/rpm/${BRANCH}/${family}/${releasever}/${arch}")
  done
done

# Build a single remote-safe command so it works over ssh in one round-trip.
MKDIR_CMD="mkdir -p"
for dir in "${DIRS[@]}"; do
  MKDIR_CMD+=" $(printf '%q' "$dir")"
done

run() {
  if [ "$DRY_RUN" -eq 1 ]; then
    echo "+ $*"
  elif [ -n "$SSH_DEST" ]; then
    # shellcheck disable=SC2029
    ssh "$SSH_DEST" "$*"
  else
    eval "$*"
  fi
}

echo "Creating ${#DIRS[@]} directories under '${ROOT}/rpm/${BRANCH}'${SSH_DEST:+ on ${SSH_DEST}}:"
printf '  %s\n' "${DIRS[@]}"

run "$MKDIR_CMD"

if [ "$RUN_CREATEREPO" -eq 1 ]; then
  for dir in "${DIRS[@]}"; do
    run "createrepo_c --update $(printf '%q' "$dir")"
  done
fi

echo "Done."
