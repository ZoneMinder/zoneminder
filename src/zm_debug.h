/*
 * ZoneMinder Debug Interface, $Date$, $Revision$
 * Copyright (C) 2003, 2004, 2005, 2006  Philip Coombes
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

/* Define the level at which messages go through syslog */
#define ZM_DBG_SYSLOG		ZM_DBG_INF 

#define zmDbgPrintf(level,params...)	{\
					if (level <= zm_dbg_level)\
						zmDbgOutput( __FILE__, __LINE__, level, ##params );\
				}

#define zmDbgHexdump(level,data,len)	{\
					if (level <= zm_dbg_level)\
						zmDbgOutput( __FILE__, __LINE__, level, ##params );\
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

#define HexDump(level,t,n)	{if(level<=zm_dbg_level)	\
							{	\
								int _i;	\
								int _len;	\
								char *_s;	\
								_s = (t);	\
								_len = (n);	\
								for(_i = 0; _i < _len; _i++,_s++)	\
								{	\
									if(!(_i % 16))	\
									{	\
										fprintf(zm_dbg_log_fd,"\n");	\
									}	\
									fprintf(zm_dbg_log_fd,"0x%02x ",	\
												((int)*_s)&0xff);	\
								}	\
								fprintf(zm_dbg_log_fd,"\n");	\
							}}
#ifdef __cplusplus
extern "C" {
#endif 

/* function declarations */
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
void zmDbgOutput( const char * const file, const int line, const int level, const char *fstring, ... ) __attribute__ ((format(printf, 4, 5)));

#else
int zmDbgInit();
int zmDbgReinit();
int zmDbgTerm();
void zmDbgOutput();
#endif

extern int zm_dbg_level;
extern int zm_dbg_pid;
extern char zm_dbg_log[];
#ifndef _STDIO_INCLUDED
#include <stdio.h>
#endif
extern FILE *zm_dbg_log_fd;
extern char zm_dbg_name[];
extern char zm_dbg_id[];
extern int zm_dbg_print;
extern int zm_dbg_flush;
extern int zm_dbg_add_log_id;

#ifdef __cplusplus
} /* extern "C" */
#endif
