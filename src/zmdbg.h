//
// Zone Monitor Debug Interface, $Date$, $Revision$
// Copyright (C) 2002  Philip Coombes
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

#include <sys/types.h>	

#ifndef TRUE
#define TRUE 1
#endif

#ifndef FALSE
#define FALSE 0
#endif

/* Leve 0 and below */
#define DBG_OK			0
#define DBG_SUCCESS			0
#define DBG_INFO			1
#define DBG_WARNING			-1
#define DBG_ERROR			-2
#define DBG_FATAL			-3

#define DBG_INF			0
#define	DBG_WAR			-1
#define DBG_ERR			-2
#define DBG_FAT			-3

#ifndef DEBUG_OFF

#define DbgPrintf(code,params)	{\
					if (code <= dbg_level)\
					{\
						(void) DbgPrepare(__FILE__,__LINE__,code);\
						(void) DbgOutput params;\
					}\
				}

#define Null(params)
#define Debug(level,params)	DbgPrintf(level,params)
#define Info(params)		DbgPrintf(0, params)
#define Warning(params)		DbgPrintf(DBG_WAR,params)
#define Error(params)		DbgPrintf(DBG_ERR,params)
#define Fatal(params)		DbgPrintf(DBG_FAT,params)
#define Entrypoint(params)	DbgPrintf(9,params);
#define Exitpoint(params)	DbgPrintf(9,params);
#define Mark()				Info(("Mark"))
#define Log()				Info(("Log"))
#ifdef __GNUC__
#define Enter(level)		DbgPrintf(level,("Entering %s",__PRETTY_FUNCTION__))
#define Exit(level)			DbgPrintf(level,("Exiting %s",__PRETTY_FUNCTION__))
#else
#if 0
#define Enter(level)		DbgPrintf(level,("Entering <unknown>"))
#define Exit(level)			DbgPrintf(level,("Exiting <unknown>"))
#endif
#define Enter(level)		
#define Exit(level)			
#endif

#define HexDump(t,n)		{if(dbg_level == 9)	\
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
										fprintf(dbg_log_fd,"\n");	\
									}	\
									fprintf(dbg_log_fd,"0x%02x ",	\
												((int)*_s)&0xff);	\
								}	\
								fprintf(dbg_log_fd,"\n");	\
							}}
#ifdef __cplusplus
extern "C" {
#endif 

#if defined(__STDC__) || defined(__cplusplus)
int DbgInit(void);
int DbgTerm(void);
int DbgPrepare(const char * const file,const int line, const int code);
int DbgOutput(const char *fstring, ... ) __attribute__ ((format(printf, 1, 2)));
#else
int DbgInit();
int DbgTerm();
int DbgPrepare();
int DbgOutput();
#endif

extern int dbg_level;
extern int dbg_pid;
extern char dbg_log[];
#ifndef _STDIO_INCLUDED
#include <stdio.h>
#endif
extern FILE *dbg_log_fd;
extern const char *dbg_name;
extern int dbg_print;
extern int dbg_flush;
extern int dbg_add_log_id;

#ifdef __cplusplus
} //extern "C"
#endif

#else

#define InitialiseDebug(params)		DBG_OK
#define TerminateDebug(params)		DBG_OK

#define Debug(lvl,params)
#define Info(params)
#define Warning(params)
#define Error(params)
#define Fatal(params)
#define Mark()
#define Log()
#define Enter()
#define Exit()

#define DbgInit()
#define DbgTerm()

#endif /* DEBUG_ON */
