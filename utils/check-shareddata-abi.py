#!/usr/bin/env python3
"""Fail if the SharedData shared-memory layout differs between 32bit and 64bit.

zmc, zma and zms share the Monitor::SharedData struct through /dev/shm, and it
is also read by the Perl (ZoneMinder::Memory) and PHP (web/includes/Monitor.php)
SHM readers. Both of those assume 8-byte alignment for double/uint64/time_t
unconditionally, i.e. the x86-64 layout, on every architecture.

The struct is not packed, so that assumption only holds if the C++ struct is
laid out identically on both word sizes. It is not automatic: the i386 SysV ABI
aligns double and uint64_t to 4 bytes where x86-64 aligns them to 8, which drops
interior padding and shifts every later member. Explicit epadding members in the
struct compensate; this check proves they still do.

The struct body and its static_asserts are lifted verbatim out of zm_monitor.h,
so the expected size lives in exactly one place (the header) and is never
duplicated here. Requires a compiler that can target both -m32 and -m64
(gcc-multilib on Debian/Ubuntu). Run from the repo root:

    python3 utils/check-shareddata-abi.py
"""
import os
import re
import subprocess
import sys
import tempfile

HEADER = os.path.join('src', 'zm_monitor.h')

# Members are declared as `<type> name;` or `<type> name[N];`. Anonymous unions
# are handled separately since their members are what callers actually name.
MEMBER_RE = re.compile(
    r'^\s*(?:uint\d+_t|int\d+_t|double|char)\s+(\w+)\s*(?:\[\d+\])?\s*;', re.M)
UNION_MEMBER_RE = re.compile(r'^\s*time_t\s+(\w+)\s*;', re.M)


def fail(msg):
    print('ERROR: {}'.format(msg))
    print('\nThis check compiles Monitor::SharedData from {} at -m32 and -m64\n'
          'and requires the layouts to be identical.'.format(HEADER))
    return 1


def extract(src):
    """Pull the SharedData struct body and its static_asserts out of the header."""
    if 'typedef struct {' not in src or '} SharedData;' not in src:
        return None, None
    body = src.split('typedef struct {', 1)[1].split('} SharedData;', 1)[0]
    # Only the asserts that constrain SharedData itself.
    asserts = [ln.strip() for ln in src.split('\n')
               if ln.strip().startswith('static_assert(')
               and 'SharedData' in ln]
    return body, asserts


def build_probe(body, asserts):
    names = list(dict.fromkeys(
        MEMBER_RE.findall(body) + UNION_MEMBER_RE.findall(body)))
    if not names:
        return None, None
    out = ['#include <cstdio>', '#include <cstdint>', '#include <ctime>',
           '#include <cstddef>', 'typedef struct {' + body + '} SharedData;']
    out.extend(asserts)
    out.append('int main() {')
    out.append('  printf("sizeof %zu\\n", sizeof(SharedData));')
    for n in names:
        out.append('  printf("%-32s %zu\\n", "{0}", offsetof(SharedData, {0}));'
                   .format(n))
    out.append('  return 0;\n}')
    return '\n'.join(out), names


def run_for_bits(cxx, source, bits, workdir):
    exe = os.path.join(workdir, 'probe{}'.format(bits))
    compile_proc = subprocess.run(
        [cxx, '-m{}'.format(bits), '-std=c++17', '-w', source, '-o', exe],
        capture_output=True, text=True)
    if compile_proc.returncode != 0:
        return None, compile_proc.stderr
    run_proc = subprocess.run([exe], capture_output=True, text=True)
    if run_proc.returncode != 0:
        return None, run_proc.stderr
    return run_proc.stdout, None


def main():
    if not os.path.exists(HEADER):
        return fail('{} not found; run from the repository root.'.format(HEADER))

    body, asserts = extract(open(HEADER, encoding='utf-8').read())
    if body is None:
        return fail('could not locate the SharedData struct in {}. If it was '
                    'renamed or reformatted, update this script.'.format(HEADER))
    if not asserts:
        return fail('no static_assert on SharedData found in {}. The ABI guard '
                    'must not be removed.'.format(HEADER))

    source_text, names = build_probe(body, asserts)
    if source_text is None:
        return fail('parsed the SharedData struct but found no members; the '
                    'declaration style likely changed.')

    cxx = os.environ.get('CXX', 'g++')
    with tempfile.TemporaryDirectory() as workdir:
        source = os.path.join(workdir, 'probe.cpp')
        with open(source, 'w', encoding='utf-8') as handle:
            handle.write(source_text)

        results = {}
        for bits in (64, 32):
            stdout, err = run_for_bits(cxx, source, bits, workdir)
            if stdout is None:
                if 'static assertion' in (err or ''):
                    print('SharedData static_assert failed at -m{}:\n'.format(bits))
                    for line in (err or '').splitlines():
                        if 'static assertion' in line or 'reduces to' in line:
                            print('  ' + line.strip())
                    return fail('the {}bit layout violates the header\'s own '
                                'assertions.'.format(bits))
                print(err or '')
                return fail('could not build the {}bit probe with {}. Install a '
                            'multilib toolchain (gcc-multilib).'.format(bits, cxx))
            results[bits] = stdout

    if results[32] != results[64]:
        print('SharedData layout differs between 32bit and 64bit:\n')
        left = dict(l.rsplit(None, 1) for l in results[64].strip().split('\n'))
        right = dict(l.rsplit(None, 1) for l in results[32].strip().split('\n'))
        print('  {:<32} {:>8} {:>8}'.format('member', '64bit', '32bit'))
        for key in left:
            if left[key] != right.get(key):
                print('  {:<32} {:>8} {:>8}'.format(
                    key.strip(), left[key], right.get(key, '?')))
        print('\nAdd or adjust the epadding members in {} so both agree.'
              .format(HEADER))
        return 1

    size = results[64].split('\n', 1)[0].split()[1]
    print('OK: SharedData is {} bytes with identical offsets at -m32 and -m64 '
          '({} members checked).'.format(size, len(names)))
    return 0


if __name__ == '__main__':
    sys.exit(main())
