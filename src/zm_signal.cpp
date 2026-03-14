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
#include <cstring>

#define TRACE_SIZE 16

bool zm_reload = false;
bool zm_terminate = false;
bool zm_panic = false;

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

#if ( HAVE_SIGINFO_T && HAVE_UCONTEXT_T )
RETSIGTYPE zm_die_handler(int signal, siginfo_t * info, void *context)
#else
RETSIGTYPE zm_die_handler(int signal)
#endif
{
  zm_terminate = true;
  if (zm_panic)
    Fatal("Got signal %d (%s), crashing", signal, strsignal(signal));
  zm_panic = true;
  Error("Got signal %d (%s), crashing", signal, strsignal(signal));
#if (defined(__i386__) || defined(__x86_64__) || defined(__arm__) || defined(__aarch64__))
  // Get more information if available
#if ( HAVE_SIGINFO_T && HAVE_UCONTEXT_T )
  void *ip = nullptr;
  void *fault_addr = nullptr;
  if ( info && context ) {
    // Log signal code and errno (these are always valid)
    Debug(1, "Signal information: number %d code %d errno %d",
          signal, info->si_code, info->si_errno);

    // For memory fault signals (SIGSEGV, SIGBUS, SIGFPE, SIGILL), si_addr is valid
    // For other signals, si_pid/si_uid/si_status would be valid but we don't handle those here
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
    Debug(1, "Fault reason: %s", code_desc);

    ucontext_t *uc = (ucontext_t *) context;
#if defined(__x86_64__)
#if defined(__FreeBSD_kernel__) || defined(__FreeBSD__)
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
    ip = (void *)(uc->uc_mcontext.pc);
#elif defined(__arm__)
    ip = (void *)(uc->uc_mcontext.arm_pc);
#endif

    // Print the fault address and instruction pointer
    if ( ip ) {
      Error("Fault address: %p, instruction pointer: %p", fault_addr, ip);
    } else {
      Error("Fault address: %p, instruction pointer: unavailable", fault_addr);
    }
  }
#endif				// ( HAVE_SIGINFO_T && HAVE_UCONTEXT_T )
#endif                          // (defined(__i386__) || defined(__x86_64__) || defined(__arm__) || defined(__aarch64__))


  // Print backtrace if enabled and available
#if !defined(ZM_NO_CRASHTRACE)
#if HAVE_LIBUNWIND
  // libunwind provides better backtraces, especially on ARM
  unw_cursor_t cursor;
  unw_context_t uc;
  unw_word_t pc, offset;
  char sym[256];
  int frame = 0;

  unw_getcontext(&uc);
  unw_init_local(&cursor, &uc);

  char cmd[1024] = "addr2line -Cfip -e ";
  char *cmd_ptr = cmd + strlen(cmd);
  cmd_ptr += snprintf(cmd_ptr, sizeof(cmd) - (cmd_ptr - cmd), "%s", self);
  bool found_offset = false;

  while (unw_step(&cursor) > 0 && frame < TRACE_SIZE) {
    unw_get_reg(&cursor, UNW_REG_IP, &pc);
    if (pc == 0)
      break;

    if (unw_get_proc_name(&cursor, sym, sizeof(sym), &offset) == 0) {
      Error("Backtrace %d: %s+0x%lx [0x%lx]", frame, sym, (unsigned long)offset, (unsigned long)pc);
    } else {
      Error("Backtrace %d: [0x%lx]", frame, (unsigned long)pc);
    }

    // Collect addresses for addr2line
    int rc = snprintf(cmd_ptr, sizeof(cmd) - (cmd_ptr - cmd), " 0x%lx", (unsigned long)pc);
    if (rc > 0 && static_cast<size_t>(rc) < sizeof(cmd) - (cmd_ptr - cmd)) {
      cmd_ptr += rc;
      found_offset = true;
    }

    frame++;
  }

  if (found_offset) {
    Error("Backtrace complete, please install debug symbols (typically zoneminder-dbg)");
    Error("and execute the following command for more information:");
    Error("%s", cmd);
  }
#elif HAVE_DECL_BACKTRACE && HAVE_DECL_BACKTRACE_SYMBOLS
  // Fallback to glibc backtrace (less reliable on ARM)
  void *trace[TRACE_SIZE];
  int trace_size = 0;
  trace_size = backtrace(trace, TRACE_SIZE);

  char cmd[1024] = "addr2line -Cfip -e ";
  char *cmd_ptr = cmd + strlen(cmd);
  cmd_ptr += snprintf(cmd_ptr, sizeof(cmd) - (cmd_ptr - cmd), "%s", self);

  char **messages = backtrace_symbols(trace, trace_size);
  char *ofs_ptr;
  char *end_ptr;
  bool found_offset = false;

  // Print the full backtrace
  for (int i = 0; i < trace_size; i++) {
    Error("Backtrace %d: %s", i, messages[i]);
    if (strstr(messages[i], self) == nullptr)
      continue;
    ofs_ptr = strstr(messages[i], "(+0x");
    if (ofs_ptr == nullptr)
      continue;
    ofs_ptr += 2;
    end_ptr = strchr(ofs_ptr, ')');
    if (end_ptr == nullptr)
      continue;
    found_offset = true;
    int rc = snprintf(cmd_ptr, sizeof(cmd) - (cmd_ptr - cmd), " %.*s", static_cast<int>(end_ptr - ofs_ptr), ofs_ptr);
    if (rc < 0 || static_cast<size_t>(rc) > sizeof(cmd) - (cmd_ptr - cmd))
      break;
    cmd_ptr += rc;
  }
  free(messages);

  if (found_offset) {
    Error("Backtrace complete, please install debug symbols (typically zoneminder-dbg)");
    Error("and execute the following command for more information:");
    Error("%s", cmd);
  }
#endif  // HAVE_LIBUNWIND / HAVE_DECL_BACKTRACE
#endif  // !defined(ZM_NO_CRASHTRACE)
  // Icon: Don't exit, setting zm_terminate should cause the exit to happen in a timely manner.
  // The main reason not to here is to make valgrind traces quieter because logger gets free while other threads
  // are still running and trying to log.
  //exit(signal);
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

  action.sa_mask = block_set;
#if ( HAVE_SIGINFO_T && HAVE_UCONTEXT_T )
  action.sa_sigaction = handler;
  action.sa_flags = SA_SIGINFO;
#else
  action.sa_handler = handler;
  action.sa_flags = 0;
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

void zmSetDefaultDieHandler() {
  if ( config.dump_cores ) {
    // Do nothing
  } else {
    zmSetDieHandler(zm_die_handler);
  }
}
