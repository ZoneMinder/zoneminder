/*
 * ZoneMinder Debug Interface, $Date$, $Revision$
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
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
*/  

#ifndef ZM_DEBUG_H
#define ZM_DEBUG_H

#include <sys/types.h>	
#include <limits.h>	

#ifndef TRUE
#define TRUE 1
#endif

#ifndef FALSE
#define FALSE 0
#endif

/* Leave 0 and below for debug */
#define ZM_DBG_INF			0
#define	ZM_DBG_WAR			-1
#define ZM_DBG_ERR			-2
#define ZM_DBG_FAT			-3
#define ZM_DBG_PNC			-4

/* Define the level at which messages go through syslog */
#define ZM_DBG_SYSLOG		ZM_DBG_INF 

#define zmDbgPrintf(level,params...)	{\
					if (level <= zmDbgLevel)\
						zmDbgOutput( 0, __FILE__, __LINE__, level, ##params );\
				}

#define zmDbgHexdump(level,data,len)	{\
					if (level <= zmDbgLevel)\
						zmDbgOutput( 1, __FILE__, __LINE__, level, "%p (%d)", data, len );\
				}

/* Turn off debug here */
#ifndef ZM_DBG_OFF
#define Debug(level,params...)	zmDbgPrintf(level,##params)
#define Hexdump(level,data,len)	zmDbgHexdump(level,data,len)
#else
#define Debug(level,params...)
#define Hexdump(level,data,len)
#endif

#define Info(params...)		zmDbgPrintf(ZM_DBG_INF,##params)
#define Warning(params...)	zmDbgPrintf(ZM_DBG_WAR,##params)
#define Error(params...)	zmDbgPrintf(ZM_DBG_ERR,##params)
#define Fatal(params...)	zmDbgPrintf(ZM_DBG_FAT,##params)
#define Panic(params...)	zmDbgPrintf(ZM_DBG_PNC,##params)
#define Mark()				Info("Mark/%s/%d",__FILE__,__LINE__)
#define Log()				Info("Log")
#ifdef __GNUC__
#define Enter(level)		zmDbgPrintf(level,("Entering %s",__PRETTY_FUNCTION__))
#define Exit(level)			zmDbgPrintf(level,("Exiting %s",__PRETTY_FUNCTION__))
#else
#if 0
#define Enter(level)		zmDbgPrintf(level,("Entering <unknown>"))
#define Exit(level)			zmDbgPrintf(level,("Exiting <unknown>"))
#endif
#define Enter(level)		
#define Exit(level)			
#endif

#ifdef __cplusplus
extern "C" {
#endif 

/* function declarations */
const char *zmDbgName();
void zmUsrHandler( int sig );
int zmGetDebugEnv( void );
int zmDebugPrepareLog( void );
int zmDebugInitialise( const char *name, const char *id, int level );
int zmDebugReinitialise( const char *target );
int zmDebugTerminate( void );
void zmDbgSubtractTime( struct timeval * const tp1, struct timeval * const tp2 );

#if defined(__STDC__) || defined(__cplusplus)
int zmDbgInit( const char *name, const char *id, int level );
int zmDbgReinit( const char *target );
int zmDbgTerm(void);
void zmDbgOutput( int hex, const char * const file, const int line, const int level, const char *fstring, ... ) __attribute__ ((format(printf, 5, 6)));
#else
int zmDbgInit();
int zmDbgReinit();
int zmDbgTerm();
void zmDbgOutput();
#endif

extern int zmDbgLevel;

#ifndef _STDIO_INCLUDED
#include <stdio.h>
#endif

#ifdef __cplusplus
} /* extern "C" */
#endif

#endif // ZM_DEBUG_H
