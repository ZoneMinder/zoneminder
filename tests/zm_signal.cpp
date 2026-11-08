/*
 * This file is part of the ZoneMinder Project. See AUTHORS file for Copyright information
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation; either version 2 of the License, or (at your
 * option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program. If not, see <http://www.gnu.org/licenses/>.
 */

#include "zm_catch2.h"

#include "zm_signal.h"

#include <csignal>
#include <cstdlib>
#include <fcntl.h>
#include <string>
#include <sys/resource.h>
#include <sys/wait.h>
#include <unistd.h>

// zm_die_handler is defined in zm_signal.cpp with external linkage but is not
// declared in the public header.
#if ( HAVE_SIGINFO_T && HAVE_UCONTEXT_T )
RETSIGTYPE zm_die_handler(int signal, siginfo_t *info, void *context);
#else
RETSIGTYPE zm_die_handler(int signal);
#endif

namespace {

// Fork a child, install the die handler, raise SIGSEGV, and collect whatever
// the handler wrote to stderr. When use_log_fd is set, the child also points
// zm_crash_log_fd at a temp file whose contents are returned in log_contents.
// Returns the raw wait(2) status.
int run_crashing_child(std::string &captured, bool use_log_fd, std::string &log_contents) {
  int err_pipe[2];
  REQUIRE(pipe(err_pipe) == 0);

  char log_path[] = "/tmp/zm_signal_test_XXXXXX";
  int log_fd = -1;
  if (use_log_fd) {
    log_fd = mkstemp(log_path);
    REQUIRE(log_fd >= 0);
  }

  pid_t pid = fork();
  REQUIRE(pid >= 0);
  if (pid == 0) {
    // Child: no core dumps, stderr -> pipe, then crash.
    struct rlimit no_core = {0, 0};
    setrlimit(RLIMIT_CORE, &no_core);
    close(err_pipe[0]);
    dup2(err_pipe[1], STDERR_FILENO);
    if (use_log_fd)
      zm_crash_log_fd = log_fd;
    zmSetDieHandler(zm_die_handler);
    raise(SIGSEGV);
    // Reaching here means the handler failed to end the process.
    _exit(42);
  }

  // Parent.
  close(err_pipe[1]);
  if (log_fd >= 0)
    close(log_fd);

  char buf[4096];
  ssize_t n;
  while ((n = read(err_pipe[0], buf, sizeof(buf))) > 0)
    captured.append(buf, static_cast<size_t>(n));
  close(err_pipe[0]);

  int status = 0;
  waitpid(pid, &status, 0);

  if (use_log_fd) {
    int rfd = open(log_path, O_RDONLY);
    if (rfd >= 0) {
      while ((n = read(rfd, buf, sizeof(buf))) > 0)
        log_contents.append(buf, static_cast<size_t>(n));
      close(rfd);
    }
    unlink(log_path);
  }
  return status;
}

}  // namespace

TEST_CASE("zm_die_handler dies of the real signal rather than exiting from the handler", "[signal]") {
  std::string captured, unused;
  int status = run_crashing_child(captured, false, unused);

  // The process must terminate because of the signal itself, not an exit() or
  // _exit() call made from inside the handler.
  REQUIRE(WIFSIGNALED(status));
  REQUIRE(WTERMSIG(status) == SIGSEGV);
  REQUIRE_FALSE(WIFEXITED(status));

  // It must have written the crash report before re-raising.
  REQUIRE(captured.find("Got signal") != std::string::npos);
  REQUIRE(captured.find("crashing") != std::string::npos);
}

TEST_CASE("zm_die_handler also writes the crash report to the published log fd", "[signal]") {
  std::string captured, log_contents;
  int status = run_crashing_child(captured, true, log_contents);

  REQUIRE(WIFSIGNALED(status));
  REQUIRE(WTERMSIG(status) == SIGSEGV);
  REQUIRE(log_contents.find("Got signal") != std::string::npos);
}
