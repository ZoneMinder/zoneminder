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
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
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
#if HAVE_STRSIGNAL
	Info("Got signal %d (%s), reloading", signal, strsignal(signal));
#else				// HAVE_STRSIGNAL
	Info("Got HUP signal, reloading");
#endif				// HAVE_STRSIGNAL
	zm_reload = true;
}

RETSIGTYPE zm_term_handler(int signal)
{
#if HAVE_STRSIGNAL
	Info("Got signal %d (%s), exiting", signal, strsignal(signal));
#else				// HAVE_STRSIGNAL
	Info("Got TERM signal, exiting");
#endif				// HAVE_STRSIGNAL
	zm_terminate = true;
}

#if HAVE_STRUCT_SIGCONTEXT
RETSIGTYPE zm_die_handler(int signal, struct sigcontext context)
#elif ( HAVE_SIGINFO_T && HAVE_UCONTEXT_T )
RETSIGTYPE zm_die_handler(int signal, siginfo_t * info, void *ucontext)
#else
RETSIGTYPE zm_die_handler(int signal)
#endif
{
	void *cr2 = 0;
	void *ip = 0;

	// Print signal number and also signal text if available
	if (signal == SIGABRT) {
#if HAVE_STRSIGNAL
		Info("Got signal %d (%s), exiting and forcing backtrace",
		     signal, strsignal(signal));
#else				// HAVE_STRSIGNAL
		Error("Got signal %d, exiting and forcing backtrace", signal);
#endif				// HAVE_STRSIGNAL
	} else {
#if HAVE_STRSIGNAL
		Info("Got signal %d (%s), crashing", signal, strsignal(signal));
#else				// HAVE_STRSIGNAL
		Error("Got signal %d, crashing", signal);
#endif				// HAVE_STRSIGNAL
	}

	// Get signal address and instruction pointer if available
#if (( HAVE_SIGINFO_T && HAVE_UCONTEXT_T ) || HAVE_STRUCT_SIGCONTEXT )
#if HAVE_STRUCT_SIGCONTEXT
#if HAVE_STRUCT_SIGCONTEXT_RIP
	cr2 = (void *)context.cr2;
	ip = (void *)context.rip;
#elif HAVE_STRUCT_SIGCONTEXT_EIP
	cr2 = (void *)context.cr2;
	ip = (void *)context.eip;
#else
	cr2 = (void *)context.cr2;
#endif				// HAVE_STRUCT_SIGCONTEXT_*

#else				// HAVE_STRUCT_SIGCONTEXT
	if (info && ucontext) {
		ucontext_t *uc = (ucontext_t *) ucontext;
#if defined(__x86_64__)
		cr2 = info->si_addr;
		ip = (void *)(uc->uc_mcontext.gregs[REG_RIP]);
#else
		cr2 = info->si_addr;
		ip = (void *)(uc->uc_mcontext.gregs[REG_EIP]);
#endif				// defined(__x86_64__)
	}
#endif				// HAVE_STRUCT_SIGCONTEXT
#endif				// (( HAVE_SIGINFO_T && HAVE_UCONTEXT_T ) || HAVE_STRUCT_SIGCONTEXT )

	// Print the signal address and instruction pointer if available
	if (cr2) {
		if (ip) {
			Error("Signal address is %p, from %p", cr2, ip);
		} else {
			Error("Signal address is %p, no instruction pointer",
			      cr2);
		}
	}

	// Print backtrace if enabled and available
#if ( !defined(ZM_NO_CRASHTRACE) && HAVE_DECL_BACKTRACE && HAVE_DECL_BACKTRACE_SYMBOLS )
	void *trace[TRACE_SIZE];
	int trace_size = 0;
	trace_size = backtrace(trace, TRACE_SIZE);

	char cmd[1024] = "addr2line -e ";
	char *cmd_ptr = cmd + strlen(cmd);

	char **messages = backtrace_symbols(trace, trace_size);
	cmd_ptr += snprintf(cmd_ptr, sizeof(cmd) - (cmd_ptr - cmd), "%s", self);

	// Skip the last entries that have no text, they probably point here
	bool found_last = false;
	for (int i = 1; i < trace_size; i++) {
		if ((!found_last && messages[i][0] != '[') || found_last) {
			found_last = true;
			Error("Backtrace: %s", messages[i]);
			cmd_ptr +=
			    snprintf(cmd_ptr, sizeof(cmd) - (cmd_ptr - cmd),
				     " %p", trace[i]);
		}

	}
	Info("Backtrace complete, please execute the following command for more information");
	Info(cmd);
#endif				// ( !defined(ZM_NO_CRASHTRACE) && HAVE_DECL_BACKTRACE && HAVE_DECL_BACKTRACE_SYMBOLS )

	exit(signal);
}

void zmSetHupHandler(SigHandler * handler)
{
	sigset_t block_set;
	sigemptyset(&block_set);
	struct sigaction action, old_action;

	action.sa_handler = (SigHandler *) handler;
	action.sa_mask = block_set;
	action.sa_flags = 0;
	sigaction(SIGHUP, &action, &old_action);
}

void zmSetTermHandler(SigHandler * handler)
{
	sigset_t block_set;
	sigemptyset(&block_set);
	struct sigaction action, old_action;

	action.sa_handler = (SigHandler *) handler;
	action.sa_mask = block_set;
	action.sa_flags = 0;
	sigaction(SIGTERM, &action, &old_action);
}

void zmSetDieHandler(SigHandler * handler)
{
	sigset_t block_set;
	sigemptyset(&block_set);
	struct sigaction action, old_action;

	action.sa_handler = (SigHandler *) handler;
	action.sa_mask = block_set;
	action.sa_flags = 0;

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
