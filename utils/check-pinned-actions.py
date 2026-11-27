#!/usr/bin/env python3
"""Fail if a workflow uses an action by a branch or tag instead of a commit SHA.

A branch or tag can be moved by whoever controls the action's repository, and the
package workflows hand their steps the zmrepo SSH key and the package signing key,
so every `uses:` must name a full 40-character commit SHA. Keep the version as a
comment after it (`@<sha> # v7`) so dependabot can update it.

Run from the repository root: python3 utils/check-pinned-actions.py
"""
import glob
import re
import sys

USES = re.compile(r'^\s*-?\s*uses:\s*([^\s#]+)')
PINNED = re.compile(r'^[\w.-]+/[\w.-]+(/[\w./-]+)?@[0-9a-f]{40}$')

failures = []
for path in sorted(glob.glob('.github/workflows/*.yml') + glob.glob('.github/workflows/*.yaml')):
    with open(path, encoding='utf-8') as f:
        for number, line in enumerate(f, 1):
            m = USES.match(line)
            if not m:
                continue
            ref = m.group(1)
            if ref.startswith('./') or ref.startswith('docker://'):
                continue
            if not PINNED.match(ref):
                failures.append(f'{path}:{number}: {ref} is not pinned to a commit SHA')

for failure in failures:
    print(failure)
if failures:
    print(f'\n{len(failures)} action reference(s) not pinned to a full commit SHA.')
    sys.exit(1)
print('All workflow actions are pinned to commit SHAs.')
