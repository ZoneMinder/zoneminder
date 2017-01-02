/*
 * ZoneMinder Signal Handling Interface, $Date$, $Revision$
 * Copyright (C) 2001-2008 Philip Coombes
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/  

#ifndef ZM_SIGNAL_H
#define ZM_SIGNAL_H

#include <signal.h>

#if HAVE_EXECINFO_H
#include <execinfo.h>
#endif
#if HAVE_UCONTEXT_H
#include <ucontext.h>
#endif


#include "zm.h"

typedef RETSIGTYPE (SigHandler)( int );

extern bool zm_reload;
extern bool zm_terminate;

RETSIGTYPE zmc_hup_handler( int signal );
RETSIGTYPE zmc_term_handler( int signal );
#if ( HAVE_SIGINFO_T && HAVE_UCONTEXT_T )
RETSIGTYPE zmc_die_handler( int signal, siginfo_t *info, void *context );
#else
RETSIGTYPE zmc_die_handler( int signal );
#endif

void zmSetHupHandler( SigHandler *handler );
void zmSetTermHandler( SigHandler *handler );
void zmSetDieHandler( SigHandler *handler );

void zmSetDefaultHupHandler();
void zmSetDefaultTermHandler();
void zmSetDefaultDieHandler();

#endif // ZM_SIGNAL_H
