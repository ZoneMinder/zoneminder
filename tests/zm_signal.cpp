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
#include <cstring>
#include <fcntl.h>
#include <functional>
#include <pthread.h>
#include <string>
#include <sys/resource.h>
#include <sys/socket.h>
#include <sys/un.h>
#include <sys/wait.h>
#include <thread>
#include <unistd.h>

// zm_die_handler is defined in zm_signal.cpp with external linkage but is not
// declared in the public header.
#if ( HAVE_SIGINFO_T && HAVE_UCONTEXT_T )
RETSIGTYPE zm_die_handler(int signal, siginfo_t *info, void *context);
#else
RETSIGTYPE zm_die_handler(int signal);
#endif

namespace {

std::string read_all(int fd) {
  std::string contents;
  char buf[4096];
  ssize_t n;
  while ((n = read(fd, buf, sizeof(buf))) > 0)
    contents.append(buf, static_cast<size_t>(n));
  return contents;
}

std::string read_file(const char *path) {
  std::string contents;
  int fd = open(path, O_RDONLY);
  if (fd >= 0) {
    contents = read_all(fd);
    close(fd);
  }
  return contents;
}

size_t count(const std::string &haystack, const std::string &needle) {
  size_t n = 0;
  for (size_t pos = haystack.find(needle); pos != std::string::npos; pos = haystack.find(needle, pos + 1)) n++;
  return n;
}

// Fork a child that installs the die handler, runs crash() (which is expected
// to end the process with a signal), and collect what the handler wrote to
// stderr in captured. Returns the raw wait(2) status.
int run_crashing_child(std::string &captured, const std::function<void()> &crash) {
  int err_pipe[2];
  REQUIRE(pipe(err_pipe) == 0);

  pid_t pid = fork();
  REQUIRE(pid >= 0);
  if (pid == 0) {
    // Child: no core dumps, stderr -> pipe, then crash.
    struct rlimit no_core = {0, 0};
    setrlimit(RLIMIT_CORE, &no_core);
    close(err_pipe[0]);
    dup2(err_pipe[1], STDERR_FILENO);
    close(err_pipe[1]);
    // A handler stuck in its report fails the test with SIGALRM instead of
    // hanging it.
    alarm(10);
    zmSetDieHandler(zm_die_handler);
    crash();
    // Reaching here means the handler failed to end the process.
    _exit(42);
  }

  // Parent.
  close(err_pipe[1]);
  captured = read_all(err_pipe[0]);
  close(err_pipe[0]);

  int status = 0;
  waitpid(pid, &status, 0);
  // SIGALRM is the watchdog above, not the handler: the report got stuck.
  REQUIRE_FALSE((WIFSIGNALED(status) && WTERMSIG(status) == SIGALRM));
  return status;
}

// A datagram socket bound where the handler is told syslog is, standing in for
// syslogd.
class SyslogListener {
 public:
  SyslogListener() {
    REQUIRE(mkdtemp(dir_) != nullptr);
    path_ = std::string(dir_) + "/log";
    Bind();
  }
  ~SyslogListener() {
    close(fd_);
    unlink(path_.c_str());
    rmdir(dir_);
  }
  const char *path() const { return path_.c_str(); }
  // As a restarted syslogd does: remove the socket and make a new one there.
  void Recreate() {
    close(fd_);
    unlink(path_.c_str());
    Bind();
  }
  // Fill the receive queue so a blocking send to it would wait. Linux also
  // limits what each sender may have queued, so this takes fresh senders until
  // one cannot queue even its first message.
  void Fill() {
    for (int senders = 0; senders < 64; senders++) {
      int sender = socket(AF_UNIX, SOCK_DGRAM, 0);
      REQUIRE(sender >= 0);
      fcntl(sender, F_SETFL, O_NONBLOCK);
      int sent = 0;
      while (sendto(sender, "x", 1, 0, reinterpret_cast<struct sockaddr *>(&addr_), sizeof(addr_)) > 0) sent++;
      close(sender);
      if (!sent) return;
    }
    FAIL("could not fill the syslog queue");
  }
  // The first message waiting, or "" if there is none.
  std::string Receive() {
    char buf[2048];
    ssize_t n = recv(fd_, buf, sizeof(buf), MSG_DONTWAIT);
    return n > 0 ? std::string(buf, static_cast<size_t>(n)) : std::string();
  }

 private:
  void Bind() {
    fd_ = socket(AF_UNIX, SOCK_DGRAM, 0);
    REQUIRE(fd_ >= 0);
    addr_ = {};
    addr_.sun_family = AF_UNIX;
    strncpy(addr_.sun_path, path_.c_str(), sizeof(addr_.sun_path) - 1);
    REQUIRE(bind(fd_, reinterpret_cast<struct sockaddr *>(&addr_), sizeof(addr_)) == 0);
  }
  char dir_[32] = "/tmp/zm_signal_test_XXXXXX";
  std::string path_;
  struct sockaddr_un addr_ = {};
  int fd_ = -1;
};

// A pipe nobody reads, filled up, so the next write(2) to it blocks: pointed
// at by the crash log, it holds the handler mid-report.
int blocking_full_pipe() {
  int p[2];
  REQUIRE(pipe(p) == 0);
  fcntl(p[1], F_SETFL, O_NONBLOCK);
  char fill[4096] = {};
  while (write(p[1], fill, sizeof(fill)) > 0) {}
  fcntl(p[1], F_SETFL, 0);
  return p[1];  // The read end stays open and unread.
}

struct DelayedSignal {
  pthread_t target;
  int signal;
};

// Send a signal to a thread after it has had time to block in the handler.
void *send_signal_later(void *arg) {
  auto *delayed = static_cast<DelayedSignal *>(arg);
  usleep(200 * 1000);
  pthread_kill(delayed->target, delayed->signal);
  return nullptr;
}

// Fault on this thread after another has had time to start its report.
void *segfault_later(void *) {
  usleep(200 * 1000);
  raise(SIGSEGV);
  return nullptr;
}

// Recurse until the stack overflows. The volatile buffer keeps each frame
// from being optimised away or turned into a loop.
volatile bool stop_overflow = false;

int overflow(int depth) {
  if (stop_overflow) return depth;
  volatile char frame[1024];
  frame[0] = static_cast<char>(depth);
  return overflow(depth + 1) + frame[0];
}

void *overflow_thread(void *) {
  zmSetThreadAltStack();
  overflow(0);
  return nullptr;
}

}  // namespace

TEST_CASE("zm_die_handler dies of the real signal rather than exiting from the handler", "[signal]") {
  std::string captured;
  int status = run_crashing_child(captured, [] { raise(SIGSEGV); });

  // The process must terminate because of the signal itself, not an exit() or
  // _exit() call made from inside the handler.
  REQUIRE(WIFSIGNALED(status));
  REQUIRE(WTERMSIG(status) == SIGSEGV);
  REQUIRE_FALSE(WIFEXITED(status));

  // It must have written the crash report, formatted without printf, before
  // re-raising.
  REQUIRE(captured.find("Got signal 11 (SIGSEGV, Segmentation fault), crashing\n") != std::string::npos);
}

TEST_CASE("zm_die_handler writes to the log file most recently set, not one closed since", "[signal]") {
  char first_path[] = "/tmp/zm_signal_test_XXXXXX";
  char second_path[] = "/tmp/zm_signal_test_XXXXXX";
  int first = mkstemp(first_path);
  int second = mkstemp(second_path);
  REQUIRE(first >= 0);
  REQUIRE(second >= 0);

  std::string captured;
  int status = run_crashing_child(captured, [&] {
    // As a log rotation does: open the new file and close the old one. The
    // numbers they had are free for reuse, yet the report reaches the new file.
    zmSetCrashLogFile(first);
    close(first);
    zmSetCrashLogFile(second);
    close(second);
    int unrelated = open("/dev/null", O_WRONLY);
    (void)unrelated;
    raise(SIGSEGV);
  });
  close(first);
  close(second);

  REQUIRE(WIFSIGNALED(status));
  REQUIRE(read_file(first_path).empty());
  REQUIRE(read_file(second_path).find("Got signal") != std::string::npos);
  unlink(first_path);
  unlink(second_path);
}

TEST_CASE("zm_die_handler sends the crash report to syslog", "[signal]") {
  SyslogListener syslogd;
  std::string captured;
  int status = run_crashing_child(captured, [&] {
    zmSetCrashSyslog("zmtest_m1", syslogd.path());
    raise(SIGSEGV);
  });
  REQUIRE(WIFSIGNALED(status));

  std::string message = syslogd.Receive();
  // <LOG_LOCAL1|LOG_ERR>ident[pid]: text, one line per datagram, no newline.
  REQUIRE(message.rfind("<139>zmtest_m1[", 0) == 0);
  REQUIRE(message.find("]: Got signal 11 (SIGSEGV") != std::string::npos);
  REQUIRE(message.back() != '\n');
}

TEST_CASE("zm_die_handler reaches a syslog daemon that made a new socket", "[signal]") {
  SyslogListener syslogd;
  int ready[2], go[2];
  REQUIRE(pipe(ready) == 0);
  REQUIRE(pipe(go) == 0);
  // Restart "syslogd" after the child has set up its crash socket.
  std::thread restart([&] {
    char c;
    // No REQUIRE here: Catch2's assertions are for the test's own thread.
    if (read(ready[0], &c, 1) == 1) syslogd.Recreate();
    if (write(go[1], "g", 1) != 1) {}
  });

  std::string captured;
  int status = run_crashing_child(captured, [&] {
    zmSetCrashSyslog("zmtest_m1", syslogd.path());
    char c = 'r';
    if (write(ready[1], &c, 1) != 1 or read(go[0], &c, 1) != 1) _exit(43);
    raise(SIGSEGV);
  });
  restart.join();
  for (int fd : {ready[0], ready[1], go[0], go[1]}) close(fd);

  REQUIRE(WIFSIGNALED(status));
  REQUIRE(syslogd.Receive().find("Got signal 11") != std::string::npos);
}

TEST_CASE("a syslog that is not keeping up does not hang the crash report", "[signal]") {
  SyslogListener syslogd;
  syslogd.Fill();
  std::string captured;
  int status = run_crashing_child(captured, [&] {
    zmSetCrashSyslog("zmtest_m1", syslogd.path());
    raise(SIGSEGV);
  });

  // run_crashing_child fails the test if the watchdog had to end it.
  REQUIRE(WIFSIGNALED(status));
  REQUIRE(WTERMSIG(status) == SIGSEGV);
  REQUIRE(captured.find("Got signal 11") != std::string::npos);
}

TEST_CASE("a second signal during the crash report still kills the process with that signal", "[signal]") {
  std::string captured;
  int status = run_crashing_child(captured, [] {
    zmSetCrashLogFile(blocking_full_pipe());
    // The SIGSEGV report blocks writing the log; a SIGABRT then arrives on the
    // same thread. It is blocked while its own handler runs, so the handler must
    // unblock it to die of it.
    static DelayedSignal delayed = {pthread_self(), SIGABRT};
    pthread_t sender;
    pthread_create(&sender, nullptr, send_signal_later, &delayed);
    raise(SIGSEGV);
  });

  REQUIRE(WIFSIGNALED(status));
  REQUIRE(WTERMSIG(status) == SIGABRT);
}

TEST_CASE("a fault on another thread during the crash report adds no second report", "[signal]") {
  std::string captured;
  int status = run_crashing_child(captured, [] {
    zmSetCrashLogFile(blocking_full_pipe());
    pthread_t other;
    pthread_create(&other, nullptr, segfault_later, nullptr);
    raise(SIGSEGV);
  });

  // The first report is stuck, so the second thread gives up waiting for it
  // and ends the process itself.
  REQUIRE(WIFSIGNALED(status));
  REQUIRE(WTERMSIG(status) == SIGSEGV);
  REQUIRE(count(captured, "Got signal") == 1);
}

TEST_CASE("a stack overflow on a worker thread is reported", "[signal]") {
  std::string captured;
  int status = run_crashing_child(captured, [] {
    pthread_attr_t attr;
    pthread_attr_init(&attr);
    pthread_attr_setstacksize(&attr, 256 * 1024);
    pthread_t worker;
    pthread_create(&worker, &attr, overflow_thread, nullptr);
    pthread_join(worker, nullptr);
  });

  // A stack overflow is SIGSEGV on Linux and SIGBUS on macOS.
  REQUIRE(WIFSIGNALED(status));
  REQUIRE((WTERMSIG(status) == SIGSEGV || WTERMSIG(status) == SIGBUS));
  REQUIRE(captured.find("Got signal") != std::string::npos);
}
