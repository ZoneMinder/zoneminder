/*
 * ZoneMinder Debug Implementation, $Date$, $Revision$
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

#include "zm_debug.h"

int zmDbgLevel = 0;

static char dbgSyslog[64];
static char dbgName[64];
static char dbgId[64];

static char dbgLog[PATH_MAX] = "";
static FILE *dbgLogFP = (FILE *)NULL;
static int dbgPrint = FALSE;
static int dbgFlush = FALSE;
static int dbgRuntime = FALSE;
static int dbgAddLogId = FALSE;
static struct timeval dbgStart;

static int dbgRunning = FALSE;

const char *zmDbgName()
{
    return( dbgName );
}

void zmUsrHandler( int sig )
{
	if( sig == SIGUSR1)
	{
		if ( zmDbgLevel < 9 )
		{
			zmDbgLevel++;
		}
	}
	else if ( sig == SIGUSR2 )
	{
		if( zmDbgLevel > -3 )
		{
			zmDbgLevel--;
		}
	}
	Info( "Debug Level Changed to %d", zmDbgLevel );
}

int zmGetDebugEnv()
{
	char envName[128];
	char *envPtr = 0;

	envPtr = getenv( "ZM_DBG_PRINT" );
	if ( envPtr == (char *)NULL )
	{
		dbgPrint = FALSE;
	}
	else
	{
		dbgPrint = atoi( envPtr );
	}

	envPtr = getenv( "ZM_DBG_FLUSH" );
	if ( envPtr == (char *)NULL )
	{
		dbgFlush = FALSE;
	}
	else
	{
		dbgFlush = atoi( envPtr );
	}

	envPtr = getenv( "ZM_DBG_RUNTIME" );
	if ( envPtr == (char *)NULL )
	{
		dbgRuntime = FALSE;
	}
	else
	{
		dbgRuntime = atoi( envPtr );
	}

	envPtr = NULL;
	sprintf( envName, "ZM_DBG_LEVEL_%s_%s", dbgName, dbgId );
	envPtr = getenv(envName);
	if ( envPtr == (char *)NULL )
	{
		sprintf( envName, "ZM_DBG_LEVEL_%s", dbgName );
		envPtr = getenv(envName);
		if ( envPtr == (char *)NULL )
		{
			sprintf( envName, "ZM_DBG_LEVEL" );
			envPtr = getenv(envName);
		}
	}
	if ( envPtr != (char *)NULL )
	{
		zmDbgLevel = atoi(envPtr);
	}

	envPtr = NULL;
	sprintf( envName, "ZM_DBG_LOG_%s_%s", dbgName, dbgId );
	envPtr = getenv(envName);
	if ( envPtr == (char *)NULL )
	{
		sprintf( envName, "ZM_DBG_LOG_%s", dbgName );
		envPtr = getenv(envName);
		if ( envPtr == (char *)NULL )
		{
			sprintf( envName, "ZM_DBG_LOG" );
			envPtr = getenv(envName);
		}
	}
	if ( envPtr != (char *)NULL )
	{
		/* If we do not want to add a pid to the debug logs
		 * which is the default, and original method
		 */
		if ( envPtr[strlen(envPtr)-1] == '+' )
		{
			/* remove the + character from the string */
			envPtr[strlen(envPtr)-1] = '\0';
			dbgAddLogId = TRUE;
		}
		if ( dbgAddLogId == FALSE )
		{
			strncpy( dbgLog, envPtr, sizeof(dbgLog) );
		}
		else
		{
			snprintf( dbgLog, sizeof(dbgLog), "%s.%05d", envPtr, getpid() );
		}
	}

	return( 0 );
}

int zmDebugPrepareLog()
{
	FILE *tempLogFP = NULL;

	if ( dbgLogFP )
	{
		fflush( dbgLogFP );
		if ( fclose(dbgLogFP) == -1 )
		{
			Error( "fclose(), error = %s",strerror(errno) );
			return( -1 );
		}
		dbgLogFP = (FILE *)NULL;
	}

	if ( ( dbgAddLogId == FALSE && dbgLog[0] ) && ( dbgLog[strlen(dbgLog)-1] == '~' ) )
	{
		dbgLog[strlen(dbgLog)-1] = '\0';

		if ( (tempLogFP = fopen(dbgLog, "r")) != NULL )
		{
			char oldLogPath[256];
			
			sprintf( oldLogPath, "%s.old", dbgLog );
			rename( dbgLog, oldLogPath );
			fclose( tempLogFP );
		}
	}

	if( dbgLog[0] && (dbgLogFP = fopen(dbgLog,"w")) == (FILE *)NULL )
	{
	    Error( "fopen() for %s, error = %s", dbgLog, strerror(errno) );
		return( -1 );
	}
    return( 0 );
}

int zmDebugInitialise( const char *name, const char *id, int level )
{
	int status;

	gettimeofday( &dbgStart, NULL );

	strncpy( dbgName, name, sizeof(dbgName) );
	strncpy( dbgId, id, sizeof(dbgId) );
	zmDbgLevel = level;
	
	/* Now set up the syslog stuff */
	if ( dbgId[0] )
		snprintf( dbgSyslog, sizeof(dbgSyslog), "%s_%s", dbgName, dbgId );
	else
		strncpy( dbgSyslog, dbgName, sizeof(dbgSyslog) );

	(void) openlog( dbgSyslog, LOG_PID|LOG_NDELAY, LOG_LOCAL1 );

	dbgLogFP = (FILE *)NULL;

	if( (status = zmGetDebugEnv() ) < 0)
	{
		Error( "Debug Environment Error, status = %d", status );
		return( -1 );
	}

	zmDebugPrepareLog();

	Info( "Debug Level = %d, Debug Log = %s", zmDbgLevel, dbgLog[0]?dbgLog:"<none>" );

	{
	struct sigaction action;
	memset( &action, 0, sizeof(action) );
	action.sa_handler = zmUsrHandler;
	action.sa_flags = SA_RESTART;

	if ( sigaction( SIGUSR1, &action, 0 ) < 0 )
	{
		Error( "sigaction(), error = %s", strerror(errno) );
		return( -1 );
	}
	if ( sigaction( SIGUSR2, &action, 0 ) < 0)
	{
		Error( "sigaction(), error = %s", strerror(errno) );
		return( -1 );
	}
	}
	dbgRunning = TRUE;
	return( 0 );
}

int zmDbgInit( const char *name, const char *id, int level )
{
	return( zmDebugInitialise( name, id, level ) );
}

int zmDebugReinitialise( const char *target )
{
	int status;
	int reinit = FALSE;
	char buffer[64];

	if ( target )
	{
		snprintf( buffer, sizeof(buffer), "_%s_%s", dbgName, dbgId );
		if ( strcmp( target, buffer ) == 0 )
		{
			reinit = TRUE;
		}
		else
		{
			snprintf( buffer, sizeof(buffer), "_%s", dbgName );
			if ( strcmp( target, buffer ) == 0 )
			{
				reinit = TRUE;
			}
			else
			{
				if ( strcmp( target, "" ) == 0 )
				{
					reinit = TRUE;
				}
			}
		}
	}

	if ( reinit )
	{
		if ( (status = zmGetDebugEnv() ) < 0 )
		{
			Error( "Debug Environment Error, status = %d", status );
			return( -1 );
		}

		zmDebugPrepareLog();

		Info( "New Debug Level = %d, New Debug Log = %s", zmDbgLevel, dbgLog[0]?dbgLog:"<none>" );
	}

	return( 0 );
}

int zmDbgReinit( const char *target )
{
	return( zmDebugReinitialise( target ) );
}

int zmDebugTerminate()
{
	Debug( 1, "Terminating Debug" );
	fflush( dbgLogFP );
	if ( fclose(dbgLogFP) == -1 )
	{
		Error( "fclose(), error = %s", strerror(errno) );
		return( -1 );
	}
	dbgLogFP = (FILE *)NULL;
	(void) closelog();

	dbgRunning = FALSE;
	return( 0 );
}

int zmDbgTerm()
{
	return( zmDebugTerminate() );
}

void zmDbgSubtractTime( struct timeval * const tp1, struct timeval * const tp2 )
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

void zmDbgOutput( int hex, const char * const file, const int line, const int level, const char *fstring, ... )
{
    char            classString[4];
	char			timeString[64];
    char            dbgString[8192];
	va_list			argPtr;
	int				logCode;
	struct timeval	timeVal;
	
	switch ( level )
	{
        case ZM_DBG_INF:
            strncpy( classString, "INF", sizeof(classString) );
            break;
        case ZM_DBG_WAR:
            strncpy( classString, "WAR", sizeof(classString) );
            break;
        case ZM_DBG_ERR:
            strncpy( classString, "ERR", sizeof(classString) );
            break;
        case ZM_DBG_FAT:
            strncpy( classString, "FAT", sizeof(classString) );
            break;
        case ZM_DBG_PNC:
            strncpy( classString, "PNC", sizeof(classString) );
            break;
        default:
            if ( level > 0 && level <= 9 )
            {
                snprintf( classString, sizeof(classString), "DB%d", level );
            }
            else
            {
                Error( "Unknown Error Level %d", level );
            }
            break;
	}

	gettimeofday( &timeVal, NULL );

	if ( dbgRuntime )
	{
		zmDbgSubtractTime( &timeVal, &dbgStart );

		snprintf( timeString, sizeof(timeString), "%ld.%03ld", timeVal.tv_sec, timeVal.tv_usec/1000 );
	}
	else
	{
        char *timePtr = timeString;
        timePtr += strftime( timePtr, sizeof(timeString), "%x %H:%M:%S", localtime(&timeVal.tv_sec) );
		snprintf( timePtr, sizeof(timeString)-(timePtr-timeString), ".%06ld", timeVal.tv_usec );
	}

    char *dbgPtr = dbgString;
	dbgPtr += snprintf( dbgPtr, sizeof(dbgString), "%s %s[%ld].%s-%s/%d [", 
              	timeString,
				dbgSyslog,
				syscall(224),
               	classString,
				file,
				line
			);
    char *dbgLogStart = dbgPtr;

	va_start( argPtr, fstring );
    if ( hex )
    {
        unsigned char *data = va_arg( argPtr, unsigned char * );
        int len = va_arg( argPtr, int );
        int i;
        dbgPtr += snprintf( dbgPtr, sizeof(dbgString)-(dbgPtr-dbgString), "%d:", len );
        for ( i = 0; i < len; i++ )
        {
            dbgPtr += snprintf( dbgPtr, sizeof(dbgString)-(dbgPtr-dbgString), " %02x", data[i] );
        }
    }
    else
    {
	    dbgPtr += vsnprintf( dbgPtr, sizeof(dbgString)-(dbgPtr-dbgString), fstring, argPtr );
    }
	va_end(argPtr);
    char *dbg_log_end = dbgPtr;
    strncpy( dbgPtr, "]\n", sizeof(dbgString)-(dbgPtr-dbgString) );   

	if ( dbgPrint )
	{
		printf( "%s", dbgString );
		fflush( stdout );
	}
	if ( dbgLogFP != (FILE *)NULL )
	{
		fprintf( dbgLogFP, "%s", dbgString );

		if ( dbgFlush )
		{
			fflush( dbgLogFP );
		}
	}
	/* For Info, Warning, Errors etc we want to log them */
	if ( level <= ZM_DBG_SYSLOG )
	{
		switch( level )
		{
			case ZM_DBG_INF:
				logCode = LOG_INFO;
				break;
			case ZM_DBG_WAR:
				logCode = LOG_WARNING;
				break;
			case ZM_DBG_ERR:
			case ZM_DBG_FAT:
			case ZM_DBG_PNC:
				logCode = LOG_ERR;
				break;
			default:
				logCode = LOG_DEBUG;
				break;
		}
		//logCode |= LOG_DAEMON;
        *dbg_log_end = '\0';
		syslog( logCode, "%s [%s]", classString, dbgLogStart );
	}
	if ( level <= ZM_DBG_FAT )
	{
	    if ( level <= ZM_DBG_PNC )
            abort();
		exit( -1 );
	}
}
