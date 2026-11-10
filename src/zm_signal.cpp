//
// ZoneMinder Signal Handling Implementation, $Date$, $Revision$
// Copyright (C) 2001-2008 Philip Coombes
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
//

#include "zm_signal.h"

#include "zm.h"
#include "zm_logger.h"
#include <cerrno>
#include <cstdint>
#include <cstring>
#include <fcntl.h>
#include <memory>
#include <sys/socket.h>
#include <sys/un.h>
#include <syslog.h>
#include <unistd.h>

#define TRACE_SIZE 16

namespace {

// Size of the alternate stack the die handler runs on. A fixed size is used
// because SIGSTKSZ is not a constant expression on current glibc.
constexpr size_t kAltStackSize = 64 * 1024;

// A descriptor that always refers to the current per-process log file. It is
// never closed: each new log file is dup2()ed onto it, which replaces what it
// refers to atomically, so a handler that has loaded it writes to either the
// old or the new log file and never to whatever reused a closed number.
std::atomic<int> crash_log_fd{-1};

// A datagram socket connected to syslog, and the ident and facility to send
// with, so the report reaches syslog without calling syslog(3), which is not
// async-signal-safe. Also never closed.
std::atomic<int> crash_syslog_fd{-1};
char crash_syslog_ident[64] = "zoneminder";

// Set by the first thread to report a crash. Other threads that fault while it
// reports wait for it to end the process instead of writing a second report.
// std::atomic_flag is the one type guaranteed lock-free.
std::atomic_flag crash_reporting = ATOMIC_FLAG_INIT;

// Set while this thread is in the die handler, so a fault inside the handler
// itself ends the process at once instead of looping.
thread_local volatile sig_atomic_t in_die_handler = 0;

// Fixed-size line formatted with async-signal-safe code only (no snprintf).
// Anything past the end is cut off.
class CrashLine {
 public:
  CrashLine &str(const char *s) {
    while (*s && len_ < sizeof(buf_) - 1) buf_[len_++] = *s++;
    buf_[len_] = '\0';
    return *this;
  }
  CrashLine &dec(long value) {
    char digits[24];
    size_t n = 0;
    unsigned long magnitude = value < 0 ? 0UL - static_cast<unsigned long>(value) : static_cast<unsigned long>(value);
    do {
      digits[n++] = '0' + static_cast<char>(magnitude % 10);
      magnitude /= 10;
    } while (magnitude);
    if (value < 0) str("-");
    return append_reversed(digits, n);
  }
  CrashLine &hex(unsigned long value) {
    char digits[24];
    size_t n = 0;
    do {
      digits[n++] = "0123456789abcdef"[value & 0xf];
      value >>= 4;
    } while (value);
    str("0x");
    return append_reversed(digits, n);
  }
  CrashLine &ptr(const void *p) { return hex(reinterpret_cast<uintptr_t>(p)); }
  const char *c_str() const { return buf_; }
  size_t size() const { return len_; }
  size_t room() const { return sizeof(buf_) - 1 - len_; }
  void clear() { len_ = 0; buf_[0] = '\0'; }

 private:
  CrashLine &append_reversed(const char *digits, size_t n) {
    while (n && len_ < sizeof(buf_) - 1) buf_[len_++] = digits[--n];
    buf_[len_] = '\0';
    return *this;
  }
  char buf_[1024] = "";
  size_t len_ = 0;
};

// write(2) all of str to fd. EINTR and partial writes are retried; any other
// error gives up on that descriptor.
void crash_write(int fd, const char *str, size_t len) {
  size_t off = 0;
  while (off < len) {
    ssize_t written = write(fd, str + off, len - off);
    if (written < 0) {
      if (errno == EINTR) continue;
      return;
    }
    off += static_cast<size_t>(written);
  }
}

// Send one line of the report to syslog, as one datagram in the form syslogd
// and journald read from /dev/log, without the trailing newline.
void crash_syslog(const char *str) {
  int syslog_fd = crash_syslog_fd.load();
  if (syslog_fd < 0 or !*str) return;
  CrashLine message;
  message.str("<").dec(LOG_LOCAL1 | LOG_ERR).str(">").str(crash_syslog_ident).str("[").dec(getpid()).str("]: ").str(str);
  size_t size = message.size();
  if (message.c_str()[size - 1] == '\n') size--;
#ifdef MSG_NOSIGNAL
  send(syslog_fd, message.c_str(), size, MSG_NOSIGNAL);
#else
  send(syslog_fd, message.c_str(), size, 0);
#endif
}

void crash_syslog(const CrashLine &line) {
  crash_syslog(line.c_str());
}

// Send one line of the report to stderr, the log file and syslog, using only
// async-signal-safe calls. zmdc points a daemon's stderr at /dev/null, so with
// file logging off syslog is the only place the report reaches.
void crash_emit(const char *str) {
  size_t len = strlen(str);
  crash_write(STDERR_FILENO, str, len);
  int log_fd = crash_log_fd.load();
  if (log_fd >= 0) crash_write(log_fd, str, len);
  crash_syslog(str);
}

void crash_emit(const CrashLine &line) {
  crash_emit(line.c_str());
}

// A name for the signals the die handler is installed for, without the
// locale-using (and not async-signal-safe) strsignal().
const char *crash_signal_name(int signal) {
  switch (signal) {
    case SIGSEGV: return "SIGSEGV, Segmentation fault";
    case SIGBUS:  return "SIGBUS, Bus error";
    case SIGABRT: return "SIGABRT, Aborted";
    case SIGILL:  return "SIGILL, Illegal instruction";
    case SIGFPE:  return "SIGFPE, Floating-point exception";
    default:      return "signal";
  }
}

// Die of signal: restore the default disposition, unblock it (it is blocked
// while its handler runs, so raise() alone would only queue it) and re-raise it.
// The process then ends on the real signal, leaving a core dump if enabled,
// instead of calling exit() from inside the handler. zmdc restarts the daemon
// and records the abnormal exit.
[[noreturn]] void die_of(int signal) {
  struct sigaction dfl = {};
  dfl.sa_handler = SIG_DFL;
  sigaction(signal, &dfl, nullptr);

  sigset_t unblock;
  sigemptyset(&unblock);
  sigaddset(&unblock, signal);
  sigprocmask(SIG_UNBLOCK, &unblock, nullptr);

  raise(signal);
  _exit(-1);  // Fallback if raise() somehow did not terminate us.
}

// This thread's alternate signal stack, released when the thread ends.
struct ThreadAltStack {
  std::unique_ptr<char[]> mem;
  ~ThreadAltStack() {
    if (!mem) return;
    stack_t disable = {};
    disable.ss_flags = SS_DISABLE;
    sigaltstack(&disable, nullptr);
  }
};
thread_local ThreadAltStack thread_alt_stack;

}  // namespace

bool zm_reload = false;
std::atomic<bool> zm_terminate = false;

void zmSetCrashLogFile(int fd) {
  if (fd < 0) return;
  int reserved = crash_log_fd.load();
  if (reserved < 0) {
    int copy = fcntl(fd, F_DUPFD_CLOEXEC, 0);
    if (copy < 0) return;
    if (crash_log_fd.compare_exchange_strong(reserved, copy)) return;
    close(copy);  // Another thread reserved one first; reserved now holds it.
  }
  if (dup2(fd, reserved) >= 0) fcntl(reserved, F_SETFD, FD_CLOEXEC);
}

void zmSetCrashSyslog(const char *ident, const char *path) {
  // The ident is only rewritten here, at startup or when the Logger reopens
  // syslog under a new id, never while a report is being written.
  size_t n = 0;
  for (; ident && ident[n] && n < sizeof(crash_syslog_ident) - 1; n++) crash_syslog_ident[n] = ident[n];
  if (n) crash_syslog_ident[n] = '\0';

  int fd = crash_syslog_fd.load();
  if (fd < 0) {
    int created = socket(AF_UNIX, SOCK_DGRAM, 0);
    if (created < 0) return;
    fcntl(created, F_SETFD, FD_CLOEXEC);
    if (crash_syslog_fd.compare_exchange_strong(fd, created)) {
      fd = created;
    } else {
      close(created);  // Another thread created one first; fd now holds it.
    }
  }
  // (Re)connecting a datagram socket just changes where it sends, so this
  // also follows a syslog daemon that was restarted and made a new socket.
  struct sockaddr_un addr = {};
  addr.sun_family = AF_UNIX;
  strncpy(addr.sun_path, path, sizeof(addr.sun_path) - 1);
  connect(fd, reinterpret_cast<struct sockaddr *>(&addr), sizeof(addr));
}

void zmSetThreadAltStack() {
  if (thread_alt_stack.mem) return;
  stack_t current = {};
  if (sigaltstack(nullptr, &current) == 0 and !(current.ss_flags & SS_DISABLE)) return;

  thread_alt_stack.mem.reset(new char[kAltStackSize]);
  stack_t alt_stack = {};
  alt_stack.ss_sp = thread_alt_stack.mem.get();
  alt_stack.ss_size = kAltStackSize;
  if (sigaltstack(&alt_stack, nullptr) < 0) thread_alt_stack.mem.reset();
}

RETSIGTYPE zm_hup_handler(int signal) {
  // Shouldn't do complex things in signal handlers, logging is complex and can block due to mutexes.
  //Info("Got signal %d (%s), reloading", signal, strsignal(signal));
  zm_reload = true;
}

RETSIGTYPE zm_term_handler(int signal) {
  // Shouldn't do complex things in signal handlers, logging is complex and can block due to mutexes.
  //Info("Got signal %d (%s), exiting", signal, strsignal(signal));
  zm_terminate = true;
}

RETSIGTYPE zm_pipe_handler(int signal) {
  // Raised when writing to a closed pipe (browser disconnect during streaming).
  // Treat as a request to shut down cleanly so main() runs exit_zm() and
  // closes the DB handle and log.
  zm_terminate = true;
}

#if ( HAVE_SIGINFO_T && HAVE_UCONTEXT_T )
RETSIGTYPE zm_die_handler(int signal, siginfo_t * info, void *context)
#else
RETSIGTYPE zm_die_handler(int signal)
#endif
{
  zm_terminate = true;

  // A fault inside this handler on this thread (for instance the backtrace
  // itself faults): die at once rather than loop.
  if (in_die_handler) die_of(signal);
  in_die_handler = 1;

  // Another thread is already reporting a crash. Leave the report to it and
  // wait for it to end the process; die anyway if it has not after a while.
  if (crash_reporting.test_and_set()) {
    for (int i = 0; i < 3; i++) sleep(1);
    die_of(signal);
  }

  // Everything below is emitted with async-signal-safe write(2) only. We do not
  // touch the database, the log queue or the Logger from the handler: closing
  // them here (as Fatal() used to) joins and frees objects other threads may
  // still be using, runs atexit handlers, and can deadlock on the log mutex.
  CrashLine line;
  crash_emit(line.str("Got signal ").dec(signal).str(" (").str(crash_signal_name(signal)).str("), crashing\n"));
#if (defined(__i386__) || defined(__x86_64__) || defined(__arm__) || defined(__aarch64__))
  // Get more information if available
#if ( HAVE_SIGINFO_T && HAVE_UCONTEXT_T )
  void *ip = nullptr;
  void *fault_addr = nullptr;
  if ( info && context ) {
    // For memory fault signals (SIGSEGV, SIGBUS, SIGFPE, SIGILL), si_addr is valid.
    fault_addr = info->si_addr;

    // Provide human-readable description of the fault
    const char *code_desc = "unknown";
    if (signal == SIGSEGV) {
      switch (info->si_code) {
        case SEGV_MAPERR: code_desc = "address not mapped to object"; break;
        case SEGV_ACCERR: code_desc = "invalid permissions for mapped object"; break;
#ifdef SEGV_BNDERR
        case SEGV_BNDERR: code_desc = "failed address bound checks"; break;
#endif
#ifdef SEGV_PKUERR
        case SEGV_PKUERR: code_desc = "access denied by memory protection keys"; break;
#endif
        default: code_desc = "unknown segfault code"; break;
      }
    } else if (signal == SIGBUS) {
      switch (info->si_code) {
        case BUS_ADRALN: code_desc = "invalid address alignment"; break;
        case BUS_ADRERR: code_desc = "nonexistent physical address"; break;
        case BUS_OBJERR: code_desc = "object-specific hardware error"; break;
        default: code_desc = "unknown bus error code"; break;
      }
    } else if (signal == SIGFPE) {
      switch (info->si_code) {
        case FPE_INTDIV: code_desc = "integer divide by zero"; break;
        case FPE_INTOVF: code_desc = "integer overflow"; break;
        case FPE_FLTDIV: code_desc = "floating-point divide by zero"; break;
        case FPE_FLTOVF: code_desc = "floating-point overflow"; break;
        case FPE_FLTUND: code_desc = "floating-point underflow"; break;
        case FPE_FLTRES: code_desc = "floating-point inexact result"; break;
        case FPE_FLTINV: code_desc = "floating-point invalid operation"; break;
        case FPE_FLTSUB: code_desc = "subscript out of range"; break;
        default: code_desc = "unknown FPE code"; break;
      }
    } else if (signal == SIGILL) {
      switch (info->si_code) {
        case ILL_ILLOPC: code_desc = "illegal opcode"; break;
        case ILL_ILLOPN: code_desc = "illegal operand"; break;
        case ILL_ILLADR: code_desc = "illegal addressing mode"; break;
        case ILL_ILLTRP: code_desc = "illegal trap"; break;
        case ILL_PRVOPC: code_desc = "privileged opcode"; break;
        case ILL_PRVREG: code_desc = "privileged register"; break;
        case ILL_COPROC: code_desc = "coprocessor error"; break;
        case ILL_BADSTK: code_desc = "internal stack error"; break;
        default: code_desc = "unknown illegal instruction code"; break;
      }
    }
    line.clear();
    crash_emit(line.str("Fault reason: ").str(code_desc).str(" (signal code ").dec(info->si_code).str(")\n"));

    ucontext_t *uc = (ucontext_t *) context;
#if defined(__x86_64__)
#if defined(__APPLE__)
    ip = (void *)(uc->uc_mcontext->__ss.__rip);
#elif defined(__FreeBSD_kernel__) || defined(__FreeBSD__)
    ip = (void *)(uc->uc_mcontext.mc_rip);
#elif defined(__OpenBSD__)
    ip = (void *)(uc->sc_rip);
#else
    ip = (void *)(uc->uc_mcontext.gregs[REG_RIP]);
#endif
#elif defined(__i386__)
#if defined(__FreeBSD_kernel__) || defined(__FreeBSD__)
    ip = (void *)(uc->uc_mcontext.mc_eip);
#else
    ip = (void *)(uc->uc_mcontext.gregs[REG_EIP]);
#endif
#elif defined(__aarch64__)
#if defined(__APPLE__)
    ip = (void *)(uc->uc_mcontext->__ss.__pc);
#elif defined(__FreeBSD__)
    ip = (void *)(uc->uc_mcontext.mc_gpregs.gp_elr);
#else
    ip = (void *)(uc->uc_mcontext.pc);
#endif
#elif defined(__arm__)
#if defined(__FreeBSD__)
    ip = (void *)(uc->uc_mcontext.__gregs[_REG_PC]);
#else
    ip = (void *)(uc->uc_mcontext.arm_pc);
#endif
#endif

    // Report the fault address and instruction pointer.
    line.clear();
    line.str("Fault address: ").ptr(fault_addr).str(", instruction pointer: ");
    if ( ip ) {
      line.ptr(ip);
    } else {
      line.str("unavailable");
    }
    crash_emit(line.str("\n"));
  }
#endif				// ( HAVE_SIGINFO_T && HAVE_UCONTEXT_T )
#endif                          // (defined(__i386__) || defined(__x86_64__) || defined(__arm__) || defined(__aarch64__))


  // Print backtrace if enabled and available
#if !defined(ZM_NO_CRASHTRACE)
#if HAVE_LIBUNWIND
  // libunwind provides better backtraces, especially on ARM. unw_get_proc_name
  // copies into our buffer and does not allocate, so this stays async-safe.
  unw_cursor_t cursor;
  unw_context_t uc;
  unw_word_t pc, offset;
  char sym[256];
  int frame = 0;

  unw_getcontext(&uc);
  unw_init_local(&cursor, &uc);

  CrashLine cmd;
  cmd.str("addr2line -Cfip -e ").str(self ? self : "zoneminder");
  bool found_offset = false;

  while (unw_step(&cursor) > 0 && frame < TRACE_SIZE) {
    unw_get_reg(&cursor, UNW_REG_IP, &pc);
    if (pc == 0)
      break;

    line.clear();
    line.str("Backtrace ").dec(frame).str(": ");
    if (unw_get_proc_name(&cursor, sym, sizeof(sym), &offset) == 0)
      line.str(sym).str("+").hex(offset).str(" ");
    crash_emit(line.str("[").hex(pc).str("]\n"));

    // Collect addresses for addr2line, whole ones only.
    if (cmd.room() > 2 + 2 * sizeof(pc) + 1) {
      cmd.str(" ").hex(pc);
      found_offset = true;
    }

    frame++;
  }

  if (found_offset) {
    crash_emit("Backtrace complete, please install debug symbols (typically zoneminder-dbg)\n");
    crash_emit("and execute the following command for more information:\n");
    crash_emit(cmd.str("\n"));
  }
#elif HAVE_DECL_BACKTRACE && HAVE_DECL_BACKTRACE_SYMBOLS
  // Fallback to glibc backtrace (less reliable on ARM). backtrace() loads
  // libgcc_s on first use, which allocates and takes loader locks, so
  // zmSetDieHandler() calls it once ahead of time; after that it only walks the
  // stack. backtrace_symbols() allocates and is not used. backtrace_symbols_fd()
  // writes the names to stderr and the log file; syslog gets the addresses.
  void *trace[TRACE_SIZE];
  int trace_size = backtrace(trace, TRACE_SIZE);

  crash_emit("Backtrace:\n");
  backtrace_symbols_fd(trace, trace_size, STDERR_FILENO);
  int log_fd = crash_log_fd.load();
  if (log_fd >= 0)
    backtrace_symbols_fd(trace, trace_size, log_fd);
  for (int i = 0; i < trace_size; i++) {
    line.clear();
    crash_syslog(line.str("Backtrace ").dec(i).str(": [").ptr(trace[i]).str("]"));
  }
  crash_emit("Backtrace complete, please install debug symbols (typically zoneminder-dbg)\n");
  crash_emit("and run addr2line -Cfip -e <binary> <offsets> for source locations.\n");
#endif  // HAVE_LIBUNWIND / HAVE_DECL_BACKTRACE
#endif  // !defined(ZM_NO_CRASHTRACE)
  die_of(signal);
}

void zmSetHupHandler(SigHandler * handler) {
  sigset_t block_set;
  sigemptyset(&block_set);
  struct sigaction action, old_action;

  action.sa_handler = (SigHandler *) handler;
  action.sa_mask = block_set;
  action.sa_flags = SA_RESTART;
  sigaction(SIGHUP, &action, &old_action);
}

void zmSetTermHandler(SigHandler * handler) {
  sigset_t block_set;
  sigemptyset(&block_set);
  struct sigaction action, old_action;

  action.sa_handler = (SigHandler *) handler;
  action.sa_mask = block_set;
  action.sa_flags = SA_RESTART;
  sigaction(SIGTERM, &action, &old_action);
  sigaction(SIGINT, &action, &old_action);
  sigaction(SIGQUIT, &action, &old_action);
}

void zmSetPipeHandler(SigHandler * handler) {
  sigset_t block_set;
  sigemptyset(&block_set);
  struct sigaction action, old_action;

  action.sa_handler = (SigHandler *) handler;
  action.sa_mask = block_set;
  action.sa_flags = SA_RESTART;
  sigaction(SIGPIPE, &action, &old_action);
}

void zmSetDieHandler(
#if ( HAVE_SIGINFO_T && HAVE_UCONTEXT_T )
    SigActionHandler *handler
#else
    SigHandler *handler
#endif
) {
  sigset_t block_set;
  sigemptyset(&block_set);
  struct sigaction action, old_action;

  // Run the handler on a dedicated stack so a stack-overflow fault (SIGSEGV on
  // the guard page, where the normal stack has no room to call anything) can
  // still be reported. The stack is per thread: this covers the calling thread,
  // and worker threads call zmSetThreadAltStack() themselves.
  zmSetThreadAltStack();

#if !defined(ZM_NO_CRASHTRACE) && !HAVE_LIBUNWIND && HAVE_DECL_BACKTRACE && HAVE_DECL_BACKTRACE_SYMBOLS
  // backtrace() loads libgcc_s on first use, which allocates; do that now
  // rather than inside the handler.
  void *warm_up[1];
  backtrace(warm_up, 1);
#endif

  action.sa_mask = block_set;
#if ( HAVE_SIGINFO_T && HAVE_UCONTEXT_T )
  action.sa_sigaction = handler;
  action.sa_flags = SA_SIGINFO | SA_ONSTACK;
#else
  action.sa_handler = handler;
  action.sa_flags = SA_ONSTACK;
#endif

  sigaction(SIGBUS, &action, &old_action);
  sigaction(SIGSEGV, &action, &old_action);
  sigaction(SIGABRT, &action, &old_action);
  sigaction(SIGILL, &action, &old_action);
  sigaction(SIGFPE, &action, &old_action);
}

void zmSetDefaultHupHandler() {
  zmSetHupHandler((SigHandler *) zm_hup_handler);
}

void zmSetDefaultTermHandler() {
  zmSetTermHandler((SigHandler *) zm_term_handler);
}

void zmSetDefaultPipeHandler() {
  zmSetPipeHandler((SigHandler *) zm_pipe_handler);
}

void zmSetDefaultDieHandler() {
  if ( config.dump_cores ) {
    // Do nothing
  } else {
    zmSetDieHandler(zm_die_handler);
  }
}
