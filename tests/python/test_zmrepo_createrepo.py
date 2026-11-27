#!/usr/bin/env python3
"""utils/zmrepo_createrepo.sh, the repo metadata refresh the RPM workflows run on zmrepo.

Run with: python3 tests/python/test_zmrepo_createrepo.py
Needs bash and flock (util-linux), so Linux. createrepo_c is replaced by a stub
that behaves like the real one where it matters: it refuses to start while a
.repodata/ temp dir exists, and only removes that dir when it finishes.
"""
import os
import shutil
import signal
import subprocess
import tempfile
import time
import unittest

HERE = os.path.dirname(os.path.abspath(__file__))
SCRIPT = os.path.join(HERE, '..', '..', 'utils', 'zmrepo_createrepo.sh')

STUB = """#!/bin/bash
echo "$@" >> "$STUB_CALLS"
dir="${@: -1}"
if [ -e "$dir/.repodata" ]; then
  echo "Temporary repodata directory: $dir/.repodata/ already exists! (Another createrepo process is running?)" >&2
  exit 1
fi
mkdir "$dir/.repodata"
sleep "${STUB_SLEEP:-0}"
mkdir -p "$dir/repodata"
echo '<repomd/>' > "$dir/repodata/repomd.xml"
rmdir "$dir/.repodata"
echo "stub createrepo_c done"
exit "${STUB_EXIT:-0}"
"""


class CreaterepoTest(unittest.TestCase):

  def setUp(self):
    self.tmp = tempfile.mkdtemp()
    self.bin = os.path.join(self.tmp, 'bin')
    self.repo = os.path.join(self.tmp, 'rpm', 'master', 'el', '10', 'x86_64')
    os.makedirs(self.bin)
    os.makedirs(self.repo)
    self.calls = os.path.join(self.tmp, 'calls')
    stub = os.path.join(self.bin, 'createrepo_c')
    with open(stub, 'w') as f:
      f.write(STUB)
    os.chmod(stub, 0o755)
    self.env = dict(os.environ, STUB_CALLS=self.calls,
                    PATH=self.bin + ':/usr/bin:/bin')

  def tearDown(self):
    shutil.rmtree(self.tmp)

  def start(self, *args, **env):
    """Start the script the way the workflow does: piped into bash -s."""
    script = open(SCRIPT, 'rb')
    proc = subprocess.Popen(['bash', '-s', '--'] + list(args), stdin=script,
                            stdout=subprocess.PIPE, stderr=subprocess.STDOUT,
                            env=dict(self.env, **env), start_new_session=True)
    script.close()
    return proc

  def run_script(self, *args, **env):
    proc = self.start(*args, **env)
    out, _ = proc.communicate(timeout=30)
    return proc.returncode, out.decode()

  def test_runs_createrepo_update_on_the_dir(self):
    rc, out = self.run_script(self.repo)
    self.assertEqual(rc, 0, out)
    self.assertIn('stub createrepo_c done', out)
    with open(self.calls) as f:
      self.assertEqual(f.read().split(), ['--update', self.repo])
    self.assertTrue(os.path.exists(os.path.join(self.repo, 'repodata', 'repomd.xml')))

  def test_removes_stale_temp_dir_left_by_a_dead_run(self):
    stale = os.path.join(self.repo, '.repodata')
    os.makedirs(stale)
    open(os.path.join(stale, 'other.sqlite'), 'w').close()
    rc, out = self.run_script(self.repo)
    self.assertEqual(rc, 0, out)
    self.assertIn('stale', out)
    self.assertFalse(os.path.exists(stale))
    self.assertTrue(os.path.exists(os.path.join(self.repo, 'repodata', 'repomd.xml')))

  def test_createrepo_failure_fails_the_step(self):
    rc, out = self.run_script(self.repo, STUB_EXIT='3')
    self.assertEqual(rc, 3, out)
    self.assertIn('stub createrepo_c done', out)

  def test_missing_createrepo_c_is_reported(self):
    os.remove(os.path.join(self.bin, 'createrepo_c'))
    rc, out = self.run_script(self.repo)
    self.assertNotEqual(rc, 0)
    self.assertIn('createrepo_c is not installed', out)

  def test_missing_dir_is_reported(self):
    rc, out = self.run_script(os.path.join(self.tmp, 'nope'))
    self.assertNotEqual(rc, 0)
    self.assertIn('not a directory', out)

  def test_waits_for_the_lock_and_leaves_a_running_job_alone(self):
    # Another run holds the lock and has its .repodata in place.
    os.makedirs(os.path.join(self.repo, '.repodata'))
    lock = os.path.join(self.repo, '.createrepo.lock')
    holder = subprocess.Popen(['flock', lock, 'sleep', '30'])
    try:
      time.sleep(0.3)
      rc, out = self.run_script(self.repo, LOCK_TIMEOUT='1')
    finally:
      holder.kill()
      holder.wait()
    self.assertNotEqual(rc, 0)
    self.assertIn('timed out waiting', out)
    # The running job's temp dir must not be touched.
    self.assertTrue(os.path.exists(os.path.join(self.repo, '.repodata')))
    self.assertFalse(os.path.exists(self.calls))

  def test_survives_ssh_dropping_mid_run(self):
    # A cancelled GitHub job kills the ssh client: the remote side gets a
    # hangup and its stdout pipe goes away. createrepo_c must still finish.
    proc = self.start(self.repo, STUB_SLEEP='2')
    deadline = time.time() + 10
    while not os.path.exists(os.path.join(self.repo, '.repodata')):
      self.assertLess(time.time(), deadline, 'stub never started')
      time.sleep(0.05)
    os.killpg(proc.pid, signal.SIGHUP)
    proc.stdout.close()
    deadline = time.time() + 15
    while not os.path.exists(os.path.join(self.repo, 'repodata', 'repomd.xml')):
      self.assertLess(time.time(), deadline, 'createrepo_c was killed by the hangup')
      time.sleep(0.1)
    self.assertFalse(os.path.exists(os.path.join(self.repo, '.repodata')))
    proc.wait(timeout=10)


if __name__ == '__main__':
  unittest.main()
