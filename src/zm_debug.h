/*
 * ZoneMinder Debug Interface, $Date$, $Revision$
 * Copyright (C) 2003  Philip Coombes
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

#ifndef TRUE
#define TRUE 1
#endif

#ifndef FALSE
#define FALSE 0
#endif

/* Leve 0 and below */
#define ZM_DBG_OK			0
#define ZM_DBG_SUCCESS			0
#define ZM_DBG_INFO			1
#define ZM_DBG_WARNING			-1
#define ZM_DBG_ERROR			-2
#define ZM_DBG_FATAL			-3

#define ZM_DBG_INF			0
#define	ZM_DBG_WAR			-1
#define ZM_DBG_ERR			-2
#define ZM_DBG_FAT			-3

#ifndef ZM_DBG_OFF

#define zmDbgPrintf(code,params)	{\
					if (code <= zm_dbg_level)\
					{\
						(void) zmDbgPrepare(__FILE__,__LINE__,code);\
						(void) zmDbgOutput params;\
					}\
				}

#define Null(params)
#define Debug(level,params)	zmDbgPrintf(level,params)
#define Info(params)		zmDbgPrintf(0, params)
#define Warning(params)		zmDbgPrintf(ZM_DBG_WAR,params)
#define Error(params)		zmDbgPrintf(ZM_DBG_ERR,params)
#define Fatal(params)		zmDbgPrintf(ZM_DBG_FAT,params)
#define Entrypoint(params)	zmDbgPrintf(9,params);
#define Exitpoint(params)	zmDbgPrintf(9,params);
#define Mark()				Info(("Mark/%s/%d", __FILE__, __LINE__ ))
#define Log()				Info(("Log"))
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

#define HexDump(t,n)		{if(zm_dbg_level == 9)	\
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
int zmGetDebugEnv( const char * const command );
int zmDebugInitialise( void );
int zmDebugTerminate( void );
void zmDbgSubtractTime( struct timeval * const tp1, struct timeval * const tp2 );


#if defined(__STDC__) || defined(__cplusplus)
int zmDbgInit(void);
int zmDbgTerm(void);
int zmDbgPrepare(const char * const file,const int line, const int code);
int zmDbgOutput(const char *fstring, ... ) __attribute__ ((format(printf, 1, 2)));
#else
int zmDbgInit();
int zmDbgTerm();
int zmDbgPrepare();
int zmDbgOutput();
#endif

extern int zm_dbg_level;
extern int zm_dbg_pid;
extern char zm_dbg_log[];
#ifndef _STDIO_INCLUDED
#include <stdio.h>
#endif
extern FILE *zm_dbg_log_fd;
extern const char *zm_dbg_name;
extern int zm_dbg_print;
extern int zm_dbg_flush;
extern int zm_dbg_add_log_id;

#ifdef __cplusplus
} /* extern "C" */
#endif

#else

#define zmDebugInitialise(params)		ZM_DBG_OK
#define zmDebugTerminate(params)		ZM_DBG_OK

#define Debug(lvl,params)
#define Info(params)
#define Warning(params)
#define Error(params)
#define Fatal(params)
#define Mark()
#define Log()
#define Enter()
#define Exit()

#define zmDbgInit()
#define zmDbgTerm()

#endif /* !ZM_DBG_OFF */
