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

static unsigned int DbgArchive_ext  = 1;
static unsigned int DbgArchive_time = 0;
static unsigned int DbgArchive_size = 0;

static unsigned long DbgArchive_sec = 0;
static unsigned long DbgArchive_cnt = 0;

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

/* support for automatic reopening of debug files on failure to open */
static int dbg_running = FALSE;
static int dbg_reopen_cnt = 10;
static time_t dbg_log_closed = 0;
static int dbg_trig_arc = FALSE;

void UsrHandler( int sig )
{
	if( sig == SIGUSR1)
	{
		dbg_switched_on = TRUE;
		if ( dbg_level < 9 )
		{
			dbg_level++;
		}
#if 0
		if ( SIGNAL( SIGUSR1,UsrHandler ) < 0 )
		{
			Error(( "%s(), error = %s", dbg_policy_nm[dbg_sig_policy], strerror(errno) ));
		}
#endif
	}
	else if ( sig == SIGUSR2 )
	{
		if( dbg_level > -3 )
		{
			dbg_level--;
		}
#if 0
		if ( SIGNAL( SIGUSR2,UsrHandler ) < 0 )
		{
			Error(( "%s(), error = %s", dbg_policy_nm[dbg_sig_policy], strerror(errno) ));
		}
#endif
	}
	Info(( "Debug Level Changed to %d", dbg_level ));
}

int GetDebugEnv( const char * const command )
{
	char buffer[128];
	char *env_ptr;

	/* dbg_level = 0; */
	/* dbg_log[0] = '\0'; */

	env_ptr = getenv( "DB_PRINT" );
	if ( env_ptr == (char *)NULL )
	{
		dbg_print = FALSE;
	}
	else
	{
		dbg_print = atoi( env_ptr );
	}

	env_ptr = getenv( "DB_FLUSH" );
	if ( env_ptr == (char *)NULL )
	{
		dbg_flush = FALSE;
	}
	else
	{
		dbg_flush = atoi( env_ptr );
	}

	env_ptr = getenv( "DB_RUNTIME" );
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
	env_ptr = getenv( "DB_REOPEN_TRY" );
	if ( env_ptr != (char *) NULL )
	{
	    /* This counts the number of times the DbgOutput function
	     * has been called when the debug logs have been closed
	     * before a reopen is attempted
	     */
	    dbg_reopen_cnt = atoi(env_ptr);
	    if (dbg_reopen_cnt < 1)
	    {
		dbg_reopen_cnt = 1;
	    }
	}

	return(0);
}


char *move_debug_file ( void )
{
	int i;
	static char new_dbg_log[200];

	for ( i=0; i<PRESERVE_ATTEMPTS; i++ )
	{
		FILE *fd = (FILE *)NULL;

		sprintf ( new_dbg_log, "%s.%d", dbg_log, DbgArchive_ext );
		DbgArchive_ext ++;

		if ( ( fd = fopen ( new_dbg_log, "r" ) ) == NULL )
		{
			fclose ( fd );
			rename ( dbg_log, new_dbg_log );
			return ( new_dbg_log );
		}
	}
	return ( NULL );
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

	/* If we have purposely set the last character in the log name to a
	 * tilda, rename it .....old and get ready for new debug file.
	 */

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
	if ( DbgArchive_time || DbgArchive_size )
	{
		/* if either of the archive parameters are set then try to archive an
		 * existing log file
		 */
		DbgArchive_sec = dbg_start.tv_sec;  /* time of last archive is set to now */
		DbgArchive_cnt = 0;                /* running file size is set to zero   */

		if ( ( tmp_fp = fopen ( dbg_log, "r" ) ) != NULL )
		{
			fclose ( tmp_fp );
			prev_file = move_debug_file ( );
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

static int DbgArchiveNowLocal( const unsigned long secs )
{
	DbgArchive_sec = secs;
	DbgArchive_cnt = 0;

	fflush ( dbg_log_fd );
	if ( dbg_log_fd != NULL && ( fclose ( dbg_log_fd ) ) == -1 )
	{
	    Error ( ( "Failed to archive: fclose(), error = %s", strerror ( errno ) ) );
	    /* clearing dbg_log_fd to make sure that we don't confused about
	     * this debug file */
	    if (dbg_log_closed == 0)
	    {
		dbg_log_closed = time(NULL);
	    }
	    dbg_log_fd = NULL;
	}
	else
	{
		char *prev_file;

		dbg_log_fd = (FILE *)NULL;

		prev_file = move_debug_file ( );

		if ( prev_file == (char*)NULL )
		{
			if ( ( dbg_log_fd = fopen ( dbg_log, "a" ) ) == (FILE *)NULL )
			{
			    if (dbg_log_closed == 0)
			    {
				dbg_log_closed = time(NULL);
			    }
			    return ( DBG_ERROR );
			}
			Error ( ( "Debug Log archive failed" ) );
		}
		else
		{
			if ( ( dbg_log_fd = fopen ( dbg_log, "w" ) ) == (FILE *)NULL )
			{
			    if (dbg_log_closed == 0)
			    {
				dbg_log_closed = time(NULL);
			    }
				return ( DBG_ERROR );
			}
		}

		Info(("Debug Level = %d, Debug Log = %s",dbg_level,dbg_log));
	}

	return ( DBG_OK );
}

int DbgArchiveNow ( void )
{
	struct timeval	tp;
	struct timezone tzp;
	int rtn;
	
	gettimeofday( &tp, &tzp );
	
	rtn = DbgArchiveNowLocal ( tp.tv_sec );
	Info(("Reopening of debug logs under applications control"));

	return ( rtn );
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

	/* Archive processing */
#if 0
	if ( ( DbgArchive_time && ( ( tp.tv_sec - DbgArchive_sec ) > DbgArchive_time ) ) ||
	     ( DbgArchive_size && ( DbgArchive_cnt > DbgArchive_size ) ) )
	{
		int rtn;

		if ( ( rtn = DbgArchiveNowLocal ( tp.tv_sec ) ) != DBG_OK )
		{
			return ( rtn );
		}
		Info(("Reopening of debug triggered by debug library (MaxOpenTime=%d,MaxSize=%d", DbgArchive_time, DbgArchive_size));

	}
#endif

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
		int cnt;

		/*
		 * Attempt to seek to the end of the file.
		 * This will add about 2 micro seconds to every
		 * debug call.  This is not considerd significant.
		 * Note that if it fails it fails and we don't care
		 * it's only to be nice to fstidy.
		 */
		lseek(fileno(dbg_log_fd), 0, 2);

		if ( ( cnt = fprintf ( dbg_log_fd, "%s", dbg_string ) ) > 0 )
		{
			DbgArchive_cnt += cnt;
		}

		if ( dbg_flush )
		{
			fflush(dbg_log_fd);
		}
	}
	else if (dbg_log_fd == NULL && dbg_running == TRUE)
	{
	    /* in this section, debug is on because dbg_running (i.e.
	     * InitialiseDebug has been called and TerminateDebug has not),
	     * dbg_log_fd is NULL, probably from a failed close, so
	     * what we do is increment the count of times into DbgOutput
	     * function.  Once this is greater than the dbg_reopen_cnt
	     * we try to archive (this should hopefully tidy things up a
	     * little, but even if it doesn't it should try reopening 
	     * the debug log.  Once it is reopened we spit out an
	     * extra message for information indicating the time the
	     * debug log got lost.  Note that if we can't archive (say
	     * filesystem is still full) we just return as we would have
	     * done earlier.  Also note that in the case where we've
	     * entered this function greater than dbg_reopen_cnt we
	     * reset the counter.
	     */
#if 0
	    count++;
	    if (count > dbg_reopen_cnt)
	    {
		int rtn;

		count = 0;
		if ( ( rtn = DbgArchiveNowLocal ( tp.tv_sec ) ) != DBG_OK )
		{
			return ( rtn );
		}
		Info(("Warning debug log closed since UTC=%d", dbg_log_closed));
		dbg_log_closed = 0;
	    }
#endif
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

void DbgArchive ( const unsigned int ArchiveTime, const unsigned int ArchiveSize )
{
	DbgArchive_time = ArchiveTime;
	DbgArchive_size = ArchiveSize;
}
