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
#include <cstdio>
#include <cstring>
#include <unistd.h>

#define TRACE_SIZE 16

bool zm_reload = false;
std::atomic<bool> zm_terminate = false;

// Descriptor of the current per-process log file, published by the Logger when
// it opens or closes that file. The crash handler writes to it with write(2)
// (async-signal-safe); -1 when no log file is open.
std::atomic<int> zm_crash_log_fd{-1};

namespace {

// Write a NUL-terminated string to stderr and, when open, the log file, using
// only async-signal-safe calls. EINTR and partial writes are retried; any other
// error ends that target.
void crash_emit(const char *str) {
  size_t len = 0;
  while (str[len]) len++;

  const int fds[2] = {STDERR_FILENO, zm_crash_log_fd.load()};
  for (int fd : fds) {
    if (fd < 0) continue;
    size_t off = 0;
    while (off < len) {
      ssize_t written = write(fd, str + off, len - off);
      if (written < 0) {
        if (errno == EINTR) continue;
        break;
      }
      off += static_cast<size_t>(written);
    }
  }
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

}  // namespace

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

  // If a fault happens again while we are handling one (for instance the
  // backtrace itself faults), die immediately with the default disposition
  // rather than looping inside the handler.
  static volatile sig_atomic_t handling = 0;
  if (handling) {
    struct sigaction dfl = {};
    dfl.sa_handler = SIG_DFL;
    sigaction(signal, &dfl, nullptr);
    raise(signal);
    _exit(-1);
  }
  handling = 1;

  // Everything below is emitted with async-signal-safe write(2) only. We do not
  // touch the database, the log queue or the Logger from the handler: closing
  // them here (as Fatal() used to) joins and frees objects other threads may
  // still be using, runs atexit handlers, and can deadlock on the log mutex.
  char line[1024];
  snprintf(line, sizeof(line), "Got signal %d (%s), crashing\n", signal, crash_signal_name(signal));
  crash_emit(line);
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
    snprintf(line, sizeof(line), "Fault reason: %s (signal code %d)\n", code_desc, info->si_code);
    crash_emit(line);

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
    if ( ip ) {
      snprintf(line, sizeof(line), "Fault address: %p, instruction pointer: %p\n", fault_addr, ip);
    } else {
      snprintf(line, sizeof(line), "Fault address: %p, instruction pointer: unavailable\n", fault_addr);
    }
    crash_emit(line);
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

  char cmd[1024] = "addr2line -Cfip -e ";
  char *cmd_ptr = cmd + strlen(cmd);
  cmd_ptr += snprintf(cmd_ptr, sizeof(cmd) - (cmd_ptr - cmd), "%s", self ? self : "zoneminder");
  bool found_offset = false;

  while (unw_step(&cursor) > 0 && frame < TRACE_SIZE) {
    unw_get_reg(&cursor, UNW_REG_IP, &pc);
    if (pc == 0)
      break;

    if (unw_get_proc_name(&cursor, sym, sizeof(sym), &offset) == 0) {
      snprintf(line, sizeof(line), "Backtrace %d: %s+0x%lx [0x%lx]\n", frame, sym, (unsigned long)offset, (unsigned long)pc);
    } else {
      snprintf(line, sizeof(line), "Backtrace %d: [0x%lx]\n", frame, (unsigned long)pc);
    }
    crash_emit(line);

    // Collect addresses for addr2line
    int rc = snprintf(cmd_ptr, sizeof(cmd) - (cmd_ptr - cmd), " 0x%lx", (unsigned long)pc);
    if (rc > 0 && static_cast<size_t>(rc) < sizeof(cmd) - (cmd_ptr - cmd)) {
      cmd_ptr += rc;
      found_offset = true;
    }

    frame++;
  }

  if (found_offset) {
    crash_emit("Backtrace complete, please install debug symbols (typically zoneminder-dbg)\n");
    crash_emit("and execute the following command for more information:\n");
    crash_emit(cmd);
    crash_emit("\n");
  }
#elif HAVE_DECL_BACKTRACE && HAVE_DECL_BACKTRACE_SYMBOLS
  // Fallback to glibc backtrace (less reliable on ARM). backtrace() and
  // backtrace_symbols_fd() are async-signal-safe; backtrace_symbols() is not
  // (it allocates), so it is deliberately not used here. The frames it prints
  // already carry the "(+0xoffset)" that addr2line -e <binary> consumes.
  void *trace[TRACE_SIZE];
  int trace_size = backtrace(trace, TRACE_SIZE);

  crash_emit("Backtrace:\n");
  backtrace_symbols_fd(trace, trace_size, STDERR_FILENO);
  int log_fd = zm_crash_log_fd.load();
  if (log_fd >= 0)
    backtrace_symbols_fd(trace, trace_size, log_fd);
  crash_emit("Backtrace complete, please install debug symbols (typically zoneminder-dbg)\n");
  crash_emit("and run addr2line -Cfip -e <binary> <offsets> for source locations.\n");
#endif  // HAVE_LIBUNWIND / HAVE_DECL_BACKTRACE
#endif  // !defined(ZM_NO_CRASHTRACE)
  // Die of the actual fault: restore the default disposition, unblock the
  // signal, and re-raise it. The process then terminates on the real signal
  // (leaving a core dump if enabled) instead of calling exit() from inside the
  // handler. zmdc restarts the daemon and records the abnormal exit.
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
  // still be reported. A fixed size is used because SIGSTKSZ is not a constant
  // expression on current glibc.
  static char alt_stack_mem[64 * 1024];
  static stack_t alt_stack;
  if (!alt_stack.ss_sp) {
    alt_stack.ss_sp = alt_stack_mem;
    alt_stack.ss_size = sizeof(alt_stack_mem);
    alt_stack.ss_flags = 0;
    sigaltstack(&alt_stack, nullptr);
  }

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
