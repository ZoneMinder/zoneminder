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

#include "zm.h"
#include "zm_signal.h"

#include <stdio.h>
#include <string.h>
#include <stdlib.h>

#define TRACE_SIZE 16

bool zm_reload = false;
bool zm_terminate = false;

RETSIGTYPE zm_hup_handler(int signal)
{
  // Shouldn't do complex things in signal handlers, logging is complex and can block due to mutexes.
	//Info("Got signal %d (%s), reloading", signal, strsignal(signal));
	zm_reload = true;
}

RETSIGTYPE zm_term_handler(int signal)
{
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
	Error("Got signal %d (%s), crashing", signal, strsignal(signal));
#if (defined(__i386__) || defined(__x86_64__))
	// Get more information if available
  #if ( HAVE_SIGINFO_T && HAVE_UCONTEXT_T )
	void *ip = 0;
	void *cr2 = 0;
	if (info && context) {

		Debug(1,
		      "Signal information: number %d code %d errno %d pid %d uid %d status %d",
		      signal, info->si_code, info->si_errno, info->si_pid,
		      info->si_uid, info->si_status);

		ucontext_t *uc = (ucontext_t *) context;
		cr2 = info->si_addr;
    #if defined(__x86_64__)
	    #if defined(__FreeBSD_kernel__) || defined(__FreeBSD__) 
		ip = (void *)(uc->uc_mcontext.mc_rip);
	    #else
		ip = (void *)(uc->uc_mcontext.gregs[REG_RIP]);
	    #endif
    #else
	    #if defined(__FreeBSD_kernel__) || defined(__FreeBSD__)
		ip = (void *)(uc->uc_mcontext.mc_eip);
	    #else
		ip = (void *)(uc->uc_mcontext.gregs[REG_EIP]);
	    #endif
    #endif				// defined(__x86_64__)

		// Print the signal address and instruction pointer if available
		if (ip) {
			Error("Signal address is %p, from %p", cr2, ip);
		} else {
			Error("Signal address is %p, no instruction pointer", cr2);
		}
	}
  #endif				// ( HAVE_SIGINFO_T && HAVE_UCONTEXT_T )


	// Print backtrace if enabled and available
  #if ( !defined(ZM_NO_CRASHTRACE) && HAVE_DECL_BACKTRACE && HAVE_DECL_BACKTRACE_SYMBOLS )
	void *trace[TRACE_SIZE];
	int trace_size = 0;
	trace_size = backtrace(trace, TRACE_SIZE);

	char cmd[1024] = "addr2line -e ";
	char *cmd_ptr = cmd + strlen(cmd);
	cmd_ptr += snprintf(cmd_ptr, sizeof(cmd) - (cmd_ptr - cmd), "%s", self);

	char **messages = backtrace_symbols(trace, trace_size);
	// Print the full backtrace
	for (int i = 0; i < trace_size; i++) {
		Error("Backtrace %u: %s", i, messages[i]);
		cmd_ptr +=
		    snprintf(cmd_ptr, sizeof(cmd) - (cmd_ptr - cmd), " %p",
			     trace[i]);
	}
	free(messages);

	Info("Backtrace complete, please execute the following command for more information");
	Info(cmd);
  #endif				// ( !defined(ZM_NO_CRASHTRACE) && HAVE_DECL_BACKTRACE && HAVE_DECL_BACKTRACE_SYMBOLS )
#endif                          // (defined(__i386__) || defined(__x86_64__)
	exit(signal);
}

void zmSetHupHandler(SigHandler * handler)
{
	sigset_t block_set;
	sigemptyset(&block_set);
	struct sigaction action, old_action;

	action.sa_handler = (SigHandler *) handler;
	action.sa_mask = block_set;
	action.sa_flags = SA_RESTART;
	sigaction(SIGHUP, &action, &old_action);
}

void zmSetTermHandler(SigHandler * handler)
{
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

void zmSetDieHandler(SigHandler * handler)
{
	sigset_t block_set;
	sigemptyset(&block_set);
	struct sigaction action, old_action;

	action.sa_mask = block_set;
#if ( HAVE_SIGINFO_T && HAVE_UCONTEXT_T )
	action.sa_sigaction = (void (*)(int, siginfo_t *, void *))handler;
	action.sa_flags = SA_SIGINFO;
#else
	action.sa_handler = (SigHandler *) handler;
	action.sa_flags = 0;
#endif

	sigaction(SIGBUS, &action, &old_action);
	sigaction(SIGSEGV, &action, &old_action);
	sigaction(SIGABRT, &action, &old_action);
	sigaction(SIGILL, &action, &old_action);
	sigaction(SIGFPE, &action, &old_action);
}

void zmSetDefaultHupHandler()
{
	zmSetHupHandler((SigHandler *) zm_hup_handler);
}

void zmSetDefaultTermHandler()
{
	zmSetTermHandler((SigHandler *) zm_term_handler);
}

void zmSetDefaultDieHandler()
{
	if (config.dump_cores) {
		// Do nothing
	} else {
		zmSetDieHandler((SigHandler *) zm_die_handler);
	}
}
