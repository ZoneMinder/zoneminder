#!/bin/bash
#
# zmrepo_createrepo.sh - Regenerate the yum/dnf metadata for one zmrepo
# directory after new RPMs have been copied into it.
#
# Runs on the zmrepo host. The build-rpm-packages*.yml workflows pipe it over
# ssh after each publish, so the host needs createrepo_c and flock installed
# but no copy of this script:
#
#   ssh user@zmrepo 'bash -s -- rpm/master/el/9/x86_64' < utils/zmrepo_createrepo.sh
#
# createrepo_c builds new metadata in <dir>/.repodata/ and refuses to start
# while that directory exists. A run that gets killed partway leaves it behind,
# and every later run then fails, so the repo silently stops updating. That is
# what froze el/10/x86_64 and fc/42/x86_64 on 2026-06-18 (issue #5232). This
# script protects against it in two ways:
#
#   - runs are serialized with flock, so a .repodata/ seen while holding the
#     lock can only be left over from a dead run, and is removed;
#   - hangups are ignored and output goes to a temp file, so a cancelled
#     workflow job (which drops the ssh connection) doesn't kill createrepo_c.
#
# Environment:
#   LOCK_TIMEOUT  seconds to wait for another run on the same dir (default 1800)
#
set -euo pipefail

main() {
  local dir="${1:?usage: zmrepo_createrepo.sh <repo dir>}"

  if [ ! -d "$dir" ]; then
    echo "error: $dir is not a directory" >&2
    return 1
  fi
  if ! command -v createrepo_c >/dev/null; then
    echo "error: createrepo_c is not installed on $(hostname)" >&2
    return 1
  fi

  # From here on, losing the ssh connection must not stop the run. Ignored
  # signals stay ignored in the child processes.
  trap '' HUP PIPE

  local log rc=0
  log=$(mktemp)
  (
    if ! flock -w "${LOCK_TIMEOUT:-1800}" 9; then
      echo "error: timed out waiting for $dir/.createrepo.lock"
      exit 1
    fi
    if [ -e "$dir/.repodata" ]; then
      echo "removing stale $dir/.repodata left by a run that did not finish"
      rm -rf "$dir/.repodata"
    fi
    createrepo_c --update "$dir"
  ) 9>"$dir/.createrepo.lock" </dev/null >"$log" 2>&1 || rc=$?

  cat "$log" || true
  rm -f "$log"
  return "$rc"
}

# Everything above is a function, so bash has read the whole script from
# stdin before anything runs, even if the connection drops.
main "$@"
