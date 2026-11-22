#!/usr/bin/env python3
"""Fail if first-party source contains Cyrillic/Greek homoglyphs.

Contributors occasionally paste characters that look like ASCII letters but are
not, e.g. Cyrillic Es (U+0421) instead of Latin C. These break identifier and
translation-key lookups, and the multi-byte UTF-8 sequences are corrupted by
some reverse proxies (ZoneMinder discussions #4993, #4678). This scans the
git-tracked source tree for characters in the Cyrillic (U+0400-U+04FF) and
Greek (U+0370-U+03FF) blocks and exits non-zero if any are found.

Translations (web/lang/) and vendored/minified assets legitimately contain
these characters and are excluded. Run from the repo root:

    python3 utils/check-homoglyphs.py
"""
import re
import subprocess
import sys

# Only these extensions are scanned; everything else (images, fonts, binaries)
# is ignored.
SCANNED_EXT = (
    '.php', '.js', '.cpp', '.h', '.hpp', '.c', '.cc',
    '.pl', '.pm', '.pl.in', '.pm.in', '.in',
    '.css', '.sql', '.py', '.sh',
)

# Paths where non-ASCII letters are legitimate (translations) or not ours to
# fix (vendored, minified, generated). Matched as a case-insensitive substring
# of the repo-relative path.
EXCLUDE = re.compile(
    r'(?:'
    r'/lang/'                       # translation catalogues
    r'|\.min\.'                     # minified vendored bundles
    r'|/assets/'                    # bundled third-party skin assets
    r'|/vendor/|/dist/|/lib/'       # vendored libraries
    r'|node_modules/'
    r'|jquery|bootstrap|chosen|moment|video\.js|audiomotion'
    r'|pro-sidebar|dygraph|packery|flatpickr'
    r')',
    re.IGNORECASE,
)

# Cyrillic (U+0400-U+04FF) and Greek (U+0370-U+03FF) letter blocks. These are
# what homoglyph attacks and accidental paste-ins draw from; ZoneMinder code is
# otherwise ASCII. Written with \u escapes so this script is itself ASCII and
# passes its own check.
HOMOGLYPH = re.compile("[\u0400-\u04ff\u0370-\u03ff]")


def tracked_files():
    out = subprocess.check_output(['git', 'ls-files'], text=True)
    return out.splitlines()


def main():
    violations = []
    for path in tracked_files():
        if EXCLUDE.search(path):
            continue
        if not path.endswith(SCANNED_EXT):
            continue
        try:
            with open(path, encoding='utf-8') as handle:
                lines = handle.readlines()
        except (OSError, UnicodeDecodeError):
            continue
        for lineno, line in enumerate(lines, 1):
            for match in HOMOGLYPH.finditer(line):
                char = match.group()
                violations.append(
                    (path, lineno, match.start() + 1, ord(char), line.rstrip('\n'))
                )

    if not violations:
        print('OK: no Cyrillic/Greek homoglyphs in first-party source.')
        return 0

    print('Found {} homoglyph character(s) in first-party source:\n'.format(len(violations)))
    for path, lineno, col, cp, text in violations:
        print('  {}:{}:{}  U+{:04X}'.format(path, lineno, col, cp))
        print('    {}'.format(text.strip()))
    print('\nReplace each with its ASCII equivalent (e.g. Cyrillic Es U+0421 -> Latin C).')
    print('If a character is genuinely required, add its path to EXCLUDE in {}.'.format(sys.argv[0]))
    return 1


if __name__ == '__main__':
    sys.exit(main())
