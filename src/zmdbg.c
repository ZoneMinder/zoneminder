//
// Zone Monitor Debug Implementation, $Date$, $Revision$
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

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <time.h>
#include <sys/time.h>
#include <syslog.h>
#include <signal.h>
#include <stdarg.h>
#include <errno.h>

#include "zmdbg.h"

#define PRESERVE_ATTEMPTS          3

static char string[4096];
static char dbg_string[4096+512];
static const char *dbg_file;
static int dbg_line;
static int dbg_code;
static char dbg_class[4];
const char *dbg_name = "";
int dbg_pid = -1;
static int dbg_switched_on = FALSE;

int dbg_level = 0;
char dbg_log[128] = "";
FILE *dbg_log_fd = (FILE *)NULL;
int dbg_print = FALSE;
int dbg_flush = FALSE;
int dbg_runtime = FALSE;
int dbg_add_log_id = FALSE;
struct timeval dbg_start;

static int dbg_running = FALSE;

void UsrHandler( int sig )
{
	if( sig == SIGUSR1)
	{
		dbg_switched_on = TRUE;
		if ( dbg_level < 9 )
		{
			dbg_level++;
		}
	}
	else if ( sig == SIGUSR2 )
	{
		if( dbg_level > -3 )
		{
			dbg_level--;
		}
	}
	Info(( "Debug Level Changed to %d", dbg_level ));
}

int GetDebugEnv( const char * const command )
{
	char buffer[128];
	char *env_ptr;

	/* dbg_level = 0; */
	/* dbg_log[0] = '\0'; */

	env_ptr = getenv( "DBG_PRINT" );
	if ( env_ptr == (char *)NULL )
	{
		dbg_print = FALSE;
	}
	else
	{
		dbg_print = atoi( env_ptr );
	}

	env_ptr = getenv( "DBG_FLUSH" );
	if ( env_ptr == (char *)NULL )
	{
		dbg_flush = FALSE;
	}
	else
	{
		dbg_flush = atoi( env_ptr );
	}

	env_ptr = getenv( "DBG_RUNTIME" );
	if ( env_ptr == (char *)NULL )
	{
		dbg_runtime = FALSE;
	}
	else
	{
		dbg_runtime = atoi( env_ptr );
	}

	sprintf(buffer,"DLVL_%s",command);
	env_ptr = getenv(buffer);
	if( env_ptr != (char *)NULL )
	{
		dbg_level = atoi(env_ptr);
	}
	sprintf( buffer, "DLOG_%s", command );
	env_ptr = getenv( buffer );
	if ( env_ptr != (char *)NULL )
	{
		/* If we do not want to add a pid to the debug logs
		 * which is the default, and original method
		 */
		if ( env_ptr[strlen(env_ptr)-1] == '+' )
		{
			/* remove the + character from the string */
			env_ptr[strlen(env_ptr)-1] = '\0';
			dbg_add_log_id = TRUE;
		}
		if ( dbg_add_log_id == FALSE )
		{
			strcpy( dbg_log, env_ptr );
		}
		else
		{
			sprintf( dbg_log, "%s.%05d", env_ptr, getpid() );
		}
	}

	return(0);
}

int InitialiseDebug()
{
	char *prev_file = (char*)NULL;

	FILE *tmp_fp;

	int status;

	struct timezone tzp;

	gettimeofday( &dbg_start, &tzp );

	Debug(1,("Initialising Debug"));

	/* Now set up the syslog stuff */
	(void) openlog( dbg_name, LOG_PID|LOG_NDELAY, LOG_LOCAL1 );

	string[0] = '\0';
	dbg_class[0] = '\0';

	dbg_pid = getpid();
	dbg_log_fd = (FILE *)NULL;
	if( (status = GetDebugEnv(dbg_name) ) < 0)
	{
		Error(("Debug Environment Error, status = %d",status));
		return(DBG_ERROR);
	}

	if ( ( dbg_add_log_id == FALSE && dbg_log[0] ) && ( dbg_log[strlen(dbg_log)-1] == '~' ) )
	{
		dbg_log[strlen(dbg_log)-1] = '\0';

		if ( (tmp_fp = fopen(dbg_log, "r")) != NULL )
		{
			char old_pth[256];
			
			sprintf(old_pth, "%s.old", dbg_log);
			rename(dbg_log, old_pth);
			fclose(tmp_fp);		/* should maybe fclose() before rename() ? */
		}
	}

	if( dbg_log[0] && (dbg_log_fd = fopen(dbg_log,"w")) == (FILE *)NULL )
	{
	    Error(("fopen() for %s, error = %s",dbg_log,strerror(errno)));
		return(DBG_ERROR);
	}
	Info(("Debug Level = %d, Debug Log = %s",dbg_level,dbg_log));

	{
	struct sigaction action, old_action;
	action.sa_handler = UsrHandler;
	action.sa_flags = SA_RESTART;

	if ( sigaction( SIGUSR1, &action, &old_action ) < 0 )
	{
		Error(("%s(), error = %s",sigaction,strerror(errno)));
		return(DBG_ERROR);
	}
	if ( sigaction( SIGUSR2, &action, &old_action ) < 0)
	{
		Error(("%s(), error = %s",sigaction,strerror(errno)));
		return(DBG_ERROR);
	}
	}
	dbg_running = TRUE;
	return(DBG_OK);
}

int DbgInit()
{
	return((InitialiseDebug() == DBG_OK ? 0 : 1));
}

int TerminateDebug()
{
	Debug(1,("Terminating Debug"));
	fflush(dbg_log_fd);
	if((fclose(dbg_log_fd)) == -1)
	{
		Error(("fclose(), error = %s",strerror(errno)));
		return(DBG_ERROR);
	}
	dbg_log_fd = (FILE *)NULL;
	(void) closelog();

	dbg_running = FALSE;
	return(DBG_OK);
}

int DbgTerm()
{
	return((TerminateDebug() == DBG_OK ? 0 : 1));
}

void DbgSubtractTime( struct timeval * const tp1, struct timeval * const tp2 )
{
	tp1->tv_sec -= tp2->tv_sec;
	if ( tp1->tv_usec <= tp2->tv_usec )
	{
		tp1->tv_sec--;
		tp1->tv_usec = 1000000 - (tp2->tv_usec - tp1->tv_usec);
	}
	else
	{
		tp1->tv_usec = tp1->tv_usec - tp2->tv_usec;
	}
}

int DbgPrepare( const char * const file, const int line, const int code )
{
	dbg_file = file;
	dbg_line = line;
	dbg_code = code;
	switch(code)
	{
	case DBG_INF:
		strcpy(dbg_class,"INF");
		break;
	case DBG_WAR:
		strcpy(dbg_class,"WAR");
		break;
	case DBG_ERR:
		strcpy(dbg_class,"ERR");
		break;
	case DBG_FAT:
		strcpy(dbg_class,"FAT");
		break;
	default:
		if(code > 0 && code <= 9)
		{
			sprintf(dbg_class,"DB%d",code);
		}
		else
		{
			Error(("Unknown Error Code %d",code));
		}
		break;
	}
	return(code);
}

int DbgOutput( const char *fstring, ... )
{
	char			time_string[64];
	va_list			arg_ptr;
	int				log_code;
	struct timeval	tp;
	struct timezone tzp;
	static int count = 0;
	
	string[0] = '\0';
	va_start(arg_ptr,fstring);
	vsprintf(string,fstring,arg_ptr);

	gettimeofday( &tp, &tzp );

	if ( dbg_runtime )
	{
		DbgSubtractTime( &tp, &dbg_start );

		sprintf( time_string, "%d.%03ld", tp.tv_sec, tp.tv_usec/1000 );
	}
	else
	{
		time_t the_time;

		the_time = tp.tv_sec;

        strftime(time_string,63,"%x %H:%M:%S",localtime(&the_time));
		sprintf(&(time_string[strlen(time_string)]), ".%06ld", tp.tv_usec);

	}
	sprintf(dbg_string,"%s %s[%d].%s-%s/%d [%s]\n", 
              	time_string,dbg_name,dbg_pid,
               	dbg_class,dbg_file,dbg_line,string);
	if ( dbg_print )
	{
		printf("%s", dbg_string);
		fflush(stdout);
	}
	if ( dbg_log_fd != (FILE *)NULL )
	{
		fprintf( dbg_log_fd, "%s", dbg_string );

		if ( dbg_flush )
		{
			fflush(dbg_log_fd);
		}
	}
	/* For Info, Warning, Errors etc we want to log them */
	if ( dbg_code <= DBG_INF )
	{
		if ( !dbg_flush )
		{
			fflush(dbg_log_fd);
		}
		switch(dbg_code)
		{
			case DBG_INF:
				log_code = LOG_INFO;
				break;
			case DBG_WAR:
				log_code = LOG_WARNING;
				break;
			case DBG_ERR:
				log_code = LOG_ERR;
				break;
			case DBG_FAT:
				log_code = LOG_CRIT;
				break;
			default:
				log_code = LOG_CRIT;
				break;
		}
		log_code |= LOG_LOCAL1;
		syslog( log_code, "%s [%s]", dbg_class, string );
	}
	va_end(arg_ptr);
	if ( dbg_code == DBG_FAT )
	{
		exit(-1);
	}
	return( strlen( string ) );
}
