/*
 * ZoneMinder Logger Implementation, $Date$, $Revision$
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

#include "zm_logger.h"

#include "zm_config.h"
#include "zm_utils.h"

#include <unistd.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <sys/time.h>
#include <syslog.h>
#include <signal.h>
#include <stdarg.h>
#include <errno.h>

bool Logger::smInitialised = false;
Logger *Logger::smInstance = 0;

Logger::StringMap Logger::smCodes;
Logger::IntMap Logger::smSyslogPriorities;

#if 0
static void subtractTime( struct timeval * const tp1, struct timeval * const tp2 )
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
#endif

void Logger::usrHandler( int sig )
{
    Logger *logger = fetch();
    if ( sig == SIGUSR1 )
        logger->level( logger->level()+1 );
    else if ( sig == SIGUSR2 )
        logger->level( logger->level()-1 );
    Info( "Logger - Level changed to %d", logger->level() );
}

Logger::Logger() :
    mLevel( INFO ),
    mTermLevel( NOLOG ),
    mDatabaseLevel( NOLOG ),
    mFileLevel( NOLOG ),
    mSyslogLevel( NOLOG ),
    mEffectiveLevel( NOLOG ),
    //mLogPath( config.path_logs ),
    //mLogFile( mLogPath+"/"+mId+".log" ),
    mDbConnected( false ),
    mLogFileFP( NULL ),
    mHasTerm( false ),
    mFlush( false )
{
    if ( smInstance )
    {
        Panic( "Attempt to create second instance of Logger class" );
    }

    if ( !smInitialised )
    {
        smCodes[INFO] = "INF";
        smCodes[WARNING] = "WAR";
        smCodes[ERROR] = "ERR";
        smCodes[FATAL] = "FAT";
        smCodes[PANIC] = "PNC";
        smCodes[NOLOG] = "OFF";

        smSyslogPriorities[INFO] = LOG_INFO;
        smSyslogPriorities[WARNING] = LOG_WARNING;
        smSyslogPriorities[ERROR] = LOG_ERR;
        smSyslogPriorities[FATAL] = LOG_ERR;
        smSyslogPriorities[PANIC] = LOG_ERR;

        char code[4] = "";
        for ( int i = DEBUG1; i <= DEBUG9; i++ )
        {
            snprintf( code, sizeof(code), "DB%d", i );
            smCodes[i] = code;
            smSyslogPriorities[i] = LOG_DEBUG;
        }

        smInitialised = true;
    }

    if ( fileno(stderr) && isatty(fileno(stderr)) )
        mHasTerm = true;
}

Logger::~Logger()
{
    terminate();
}

void Logger::initialise( const std::string &id, const Options &options )
{
    char *envPtr;

    if ( !id.empty() )
        this->id( id );

    std::string tempLogFile;
    if ( options.mLogPath.size() )
    {
        mLogPath = options.mLogPath;
        tempLogFile = mLogPath+"/"+mId+".log";
    }
    if ( options.mLogFile.size() )
        tempLogFile = options.mLogFile;
    else
        tempLogFile = mLogPath+"/"+mId+".log";
    if ( (envPtr = getTargettedEnv( "LOG_FILE" )) )
        tempLogFile = envPtr;

    Level tempLevel = INFO;
    Level tempTermLevel = mTermLevel;
    Level tempDatabaseLevel = mDatabaseLevel;
    Level tempFileLevel = mFileLevel;
    Level tempSyslogLevel = mSyslogLevel;

    if ( options.mTermLevel != NOOPT )
        tempTermLevel = options.mTermLevel;
    if ( options.mDatabaseLevel != NOOPT )
        tempDatabaseLevel = options.mDatabaseLevel;
    else
        tempDatabaseLevel = config.log_level_database >= DEBUG1 ? DEBUG9 : config.log_level_database;
    if ( options.mFileLevel != NOOPT )
        tempFileLevel = options.mFileLevel;
    else
        tempFileLevel = config.log_level_file >= DEBUG1 ? DEBUG9 : config.log_level_file;
    if ( options.mSyslogLevel != NOOPT )
        tempSyslogLevel = options.mSyslogLevel;
    else
        tempSyslogLevel = config.log_level_syslog >= DEBUG1 ? DEBUG9 : config.log_level_syslog;

    // Legacy
    if ( (envPtr = getenv( "LOG_PRINT" )) )
        tempTermLevel = atoi(envPtr) ? DEBUG9 : NOLOG;

    if ( (envPtr = getTargettedEnv( "LOG_LEVEL" )) )
        tempLevel = atoi(envPtr);

    if ( (envPtr = getTargettedEnv( "LOG_LEVEL_TERM" )) )
        tempTermLevel = atoi(envPtr);
    if ( (envPtr = getTargettedEnv( "LOG_LEVEL_DATABASE" )) )
        tempDatabaseLevel = atoi(envPtr);
    if ( (envPtr = getTargettedEnv( "LOG_LEVEL_FILE" )) )
        tempFileLevel = atoi(envPtr);
    if ( (envPtr = getTargettedEnv( "LOG_LEVEL_SYSLOG" )) )
        tempSyslogLevel = atoi(envPtr);

    if ( config.log_debug )
    {
        StringVector targets = split( config.log_debug_target, "|" );
        for ( unsigned int i = 0; i < targets.size(); i++ )
        {
            const std::string &target = targets[i];
            if ( target == mId || target == "_"+mId || target == "_"+mIdRoot || target == "_"+mIdRoot || target == "" )
            {
                if ( config.log_debug_level > NOLOG )
                {
                    tempLevel = config.log_debug_level;
                    if ( config.log_debug_file[0] )
                    {
                        tempLogFile = config.log_debug_file;
                        tempFileLevel = tempLevel;
                    }
                }
            }
        }
    }

    logFile( tempLogFile );

    termLevel( tempTermLevel );
    databaseLevel( tempDatabaseLevel );
    fileLevel( tempFileLevel );
    syslogLevel( tempSyslogLevel );

    level( tempLevel );

    mFlush = (envPtr = getenv( "LOG_FLUSH")) ? atoi( envPtr ) : false;

    //mRuntime = (envPtr = getenv( "LOG_RUNTIME")) ? atoi( envPtr ) : false;

    {
        struct sigaction action;
        memset( &action, 0, sizeof(action) );
        action.sa_handler = usrHandler;
        action.sa_flags = SA_RESTART;

        if ( sigaction( SIGUSR1, &action, 0 ) < 0 )
        {
            Fatal( "sigaction(), error = %s", strerror(errno) );
        }
        if ( sigaction( SIGUSR2, &action, 0 ) < 0)
        {
            Fatal( "sigaction(), error = %s", strerror(errno) );
        }
    }

    mInitialised = true;

    Debug( 1, "LogOpts: level=%s/%s, screen=%s, database=%s, logfile=%s->%s, syslog=%s", smCodes[mLevel].c_str(), smCodes[mEffectiveLevel].c_str(), smCodes[mTermLevel].c_str(), smCodes[mDatabaseLevel].c_str(), smCodes[mFileLevel].c_str(), mLogFile.c_str(), smCodes[mSyslogLevel].c_str() );
}

void Logger::terminate()
{
    Info( "Terminating Logger" );

    if ( mFileLevel > NOLOG )
        closeFile();

    if ( mSyslogLevel > NOLOG )
        closeSyslog();

    if ( mDatabaseLevel > NOLOG )
        closeDatabase();
}

bool Logger::boolEnv( const std::string &name, bool defaultValue )
{
    const char *envPtr = getenv( name.c_str() );
    return( envPtr ? atoi( envPtr ) : defaultValue );
}

int Logger::intEnv( const std::string &name, bool defaultValue )
{
    const char *envPtr = getenv( name.c_str() );
    return( envPtr ? atoi( envPtr ) : defaultValue );
}

std::string Logger::strEnv( const std::string &name, const std::string defaultValue )
{
    const char *envPtr = getenv( name.c_str() );
    return( envPtr ? envPtr : defaultValue );
}

char *Logger::getTargettedEnv( const std::string &name )
{
    char *envPtr = NULL;
    std::string envName;

    envName = name+"_"+mId;
    envPtr = getenv( envName.c_str() );
    if ( !envPtr && mId != mIdRoot )
    {
        envName = name+"_"+mIdRoot;
        envPtr = getenv( envName.c_str() );
    }
    if ( !envPtr )
        envPtr = getenv( name.c_str() );
    return( envPtr );
}

const std::string &Logger::id( const std::string &id )
{
    std::string tempId = id;

    size_t pos;
    // Remove whitespace
    while ( (pos = tempId.find_first_of( " \t" )) != std::string::npos )
    {
        tempId.replace( pos, 1, "" );
    }
    // Replace non-alphanum with underscore
    while ( (pos = tempId.find_first_not_of( "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_" )) != std::string::npos )
    {
        tempId.replace( pos, 1, "_" );
    }
    if ( mId != tempId )
    {
        mId = tempId;
        pos = mId.find( '_' );
        if ( pos != std::string::npos )
        {
            mIdRoot = mId.substr( 0, pos );
            if ( ++pos < mId.size() )
                mIdArgs = mId.substr( pos );
        }
    }
    return( mId );
}

Logger::Level Logger::level( Logger::Level level )
{
    if ( level > NOOPT )
    {
        level = limit(level);
        if ( mLevel != level )
            mLevel = level;

        mEffectiveLevel = NOLOG;
        if ( mTermLevel > mEffectiveLevel )
            mEffectiveLevel = mTermLevel;
        if ( mDatabaseLevel > mEffectiveLevel )
            mEffectiveLevel = mDatabaseLevel;
        if ( mFileLevel > mEffectiveLevel )
            mEffectiveLevel = mFileLevel;
        if ( mSyslogLevel > mEffectiveLevel )
            mEffectiveLevel = mSyslogLevel;
        if ( mEffectiveLevel > mLevel)
            mEffectiveLevel = mLevel;
    }
    return( mLevel );
}

Logger::Level Logger::termLevel( Logger::Level termLevel )
{
    if ( termLevel > NOOPT )
    {
        if ( !mHasTerm )
            termLevel = NOLOG;
        termLevel = limit(termLevel);
        if ( mTermLevel != termLevel )
            mTermLevel = termLevel;
    }
    return( mTermLevel );
}

Logger::Level Logger::databaseLevel( Logger::Level databaseLevel )
{
    if ( databaseLevel > NOOPT )
    {
        databaseLevel = limit(databaseLevel);
        if ( mDatabaseLevel != databaseLevel )
        {
            if ( databaseLevel > NOLOG && mDatabaseLevel <= NOLOG )
            {
                if ( !mDbConnected )
                {
                    if ( !mysql_init( &mDbConnection ) )
                    {
                        Fatal( "Can't initialise database connection: %s", mysql_error( &mDbConnection ) );
                        exit( mysql_errno( &mDbConnection ) );
                    }
                    my_bool reconnect = 1;
                    if ( mysql_options( &mDbConnection, MYSQL_OPT_RECONNECT, &reconnect ) )
                        Fatal( "Can't set database auto reconnect option: %s", mysql_error( &mDbConnection ) );
                    std::string::size_type colonIndex = staticConfig.DB_HOST.find( ":/" );
                    if ( colonIndex != std::string::npos )
                    {
                        std::string dbHost = staticConfig.DB_HOST.substr( 0, colonIndex );
                        std::string dbPort = staticConfig.DB_HOST.substr( colonIndex+1 );
                        if ( !mysql_real_connect( &mDbConnection, dbHost.c_str(), staticConfig.DB_USER.c_str(), staticConfig.DB_PASS.c_str(), 0, atoi(dbPort.c_str()), 0, 0 ) )
                        {
                            Fatal( "Can't connect to database: %s", mysql_error( &mDbConnection ) );
                            exit( mysql_errno( &mDbConnection ) );
                        }
                    }
                    else
                    {
                        if ( !mysql_real_connect( &mDbConnection, staticConfig.DB_HOST.c_str(), staticConfig.DB_USER.c_str(), staticConfig.DB_PASS.c_str(), 0, 0, 0, 0 ) )
                        {
                            Fatal( "Can't connect to database: %s", mysql_error( &mDbConnection ) );
                            exit( mysql_errno( &mDbConnection ) );
                        }
                    }
                    unsigned long mysqlVersion = mysql_get_server_version( &mDbConnection );
                    if ( mysqlVersion < 50019 )
                        if ( mysql_options( &mDbConnection, MYSQL_OPT_RECONNECT, &reconnect ) )
                            Fatal( "Can't set database auto reconnect option: %s", mysql_error( &mDbConnection ) );
                    if ( mysql_select_db( &mDbConnection, staticConfig.DB_NAME.c_str() ) )
                    {
                        Fatal( "Can't select database: %s", mysql_error( &mDbConnection ) );
                        exit( mysql_errno( &mDbConnection ) );
                    }
                    mDbConnected = true;
                }
            }
            mDatabaseLevel = databaseLevel;
        }
    }
    return( mDatabaseLevel );
}

Logger::Level Logger::fileLevel( Logger::Level fileLevel )
{
    if ( fileLevel > NOOPT )
    {
        fileLevel = limit(fileLevel);
        if ( mFileLevel != fileLevel )
        {
            if ( mFileLevel > NOLOG )
                closeFile();
            mFileLevel = fileLevel;
            if ( mFileLevel > NOLOG )
                openFile();
        }
    }
    return( mFileLevel );
}

Logger::Level Logger::syslogLevel( Logger::Level syslogLevel )
{
    if ( syslogLevel > NOOPT )
    {
        syslogLevel = limit(syslogLevel);
        if ( mSyslogLevel != syslogLevel )
        {
            if ( mSyslogLevel > NOLOG )
                closeSyslog();
            mSyslogLevel = syslogLevel;
            if ( mSyslogLevel > NOLOG )
                openSyslog();
        }
    }
    return( mSyslogLevel );
}

void Logger::logFile( const std::string &logFile )
{
    bool addLogPid = false;
    std::string tempLogFile = logFile;
    if ( tempLogFile[tempLogFile.length()-1] == '+' )
    {
        tempLogFile.resize(tempLogFile.length()-1);
        addLogPid = true;
    }
    if ( addLogPid )
        mLogFile = stringtf( "%s.%05d", tempLogFile.c_str(), getpid() );
    else
        mLogFile = tempLogFile;
}

void Logger::openFile()
{
    if ( mLogFile.size() && (mLogFileFP = fopen( mLogFile.c_str() ,"w" )) == (FILE *)NULL )
    {
        mFileLevel = NOLOG;
        Fatal( "fopen() for %s, error = %s", mLogFile.c_str(), strerror(errno) );
    }
}

void Logger::closeFile()
{
    if ( mLogFileFP )
    {
        fflush( mLogFileFP );
        if ( fclose( mLogFileFP ) < 0 )
        {
            Fatal( "fclose(), error = %s",strerror(errno) );
        }
        mLogFileFP = (FILE *)NULL;
    }
}

void Logger::closeDatabase()
{
    if ( mDbConnected )
    {
        mysql_close( &mDbConnection );
        mDbConnected = false;
    }
}

void Logger::openSyslog()
{
    (void) openlog( mId.c_str(), LOG_PID|LOG_NDELAY, LOG_LOCAL1 );
}

void Logger::closeSyslog()
{
    (void) closelog();
}

void Logger::logPrint( bool hex, const char * const file, const int line, const int level, const char *fstring, ... )
{
    if ( level <= mEffectiveLevel )
    {
        char            classString[4];
        char            timeString[64];
        char            logString[8192];
        va_list         argPtr;
        struct timeval  timeVal;
        
        if ( level < PANIC || level > DEBUG9 )
            Panic( "Invalid logger level %d", level );

        strncpy( classString, smCodes[level].c_str(), sizeof(classString) );

        gettimeofday( &timeVal, NULL );

    #if 0
        if ( logRuntime )
        {
            static struct timeval logStart;

            subtractTime( &timeVal, &logStart );

            snprintf( timeString, sizeof(timeString), "%ld.%03ld", timeVal.tv_sec, timeVal.tv_usec/1000 );
        }
        else
        {
    #endif
            char *timePtr = timeString;
            timePtr += strftime( timePtr, sizeof(timeString), "%x %H:%M:%S", localtime(&timeVal.tv_sec) );
            snprintf( timePtr, sizeof(timeString)-(timePtr-timeString), ".%06ld", timeVal.tv_usec );
    #if 0
        }
    #endif

        pid_t tid;
#ifdef HAVE_SYSCALL
        if ( (tid = syscall(SYS_gettid)) < 0 ) // Thread/Process id
#endif // HAVE_SYSCALL
        tid = getpid(); // Process id

        char *logPtr = logString;
        logPtr += snprintf( logPtr, sizeof(logString), "%s %s[%d].%s-%s/%d [", 
                    timeString,
                    mId.c_str(),
                    tid,
                    classString,
                    file,
                    line
                );
        char *syslogStart = logPtr;

        va_start( argPtr, fstring );
        if ( hex )
        {
            unsigned char *data = va_arg( argPtr, unsigned char * );
            int len = va_arg( argPtr, int );
            int i;
            logPtr += snprintf( logPtr, sizeof(logString)-(logPtr-logString), "%d:", len );
            for ( i = 0; i < len; i++ )
            {
                logPtr += snprintf( logPtr, sizeof(logString)-(logPtr-logString), " %02x", data[i] );
            }
        }
        else
        {
            logPtr += vsnprintf( logPtr, sizeof(logString)-(logPtr-logString), fstring, argPtr );
        }
        va_end(argPtr);
        char *syslogEnd = logPtr;
        strncpy( logPtr, "]\n", sizeof(logString)-(logPtr-logString) );   

        if ( level <= mTermLevel )
        {
            printf( "%s", logString );
            fflush( stdout );
        }
        if ( level <= mFileLevel )
        {
            fprintf( mLogFileFP, "%s", logString );
            if ( mFlush )
                fflush( mLogFileFP );
        }
        *syslogEnd = '\0';
        if ( level <= mDatabaseLevel )
        {
            char sql[ZM_SQL_MED_BUFSIZ];
            char escapedString[(strlen(syslogStart)*2)+1];

            mysql_real_escape_string( &mDbConnection, escapedString, syslogStart, strlen(syslogStart) );
            snprintf( sql, sizeof(sql), "insert into Logs ( TimeKey, Component, Pid, Level, Code, Message, File, Line ) values ( %ld.%06ld, '%s', %d, %d, '%s', '%s', '%s', %d )", timeVal.tv_sec, timeVal.tv_usec, mId.c_str(), tid, level, classString, escapedString, file, line );
            if ( mysql_query( &mDbConnection, sql ) )
            {
                databaseLevel( NOLOG );
                Fatal( "Can't insert log entry: %s", mysql_error( &mDbConnection ) );
                exit( mysql_errno( &mDbConnection ) );
            }
        }
        if ( level <= mSyslogLevel )
        {
            int priority = smSyslogPriorities[level];
            //priority |= LOG_DAEMON;
            syslog( priority, "%s [%s]", classString, syslogStart );
        }

        if ( level <= FATAL )
        {
            if ( level <= PANIC )
                abort();
            exit( -1 );
        }
    }
}

void logInit( const char *name, const Logger::Options &options )
{
    if ( !Logger::smInstance )
        Logger::smInstance = new Logger();
    Logger::Options tempOptions = options;
    tempOptions.mLogPath = config.path_logs;
    Logger::smInstance->initialise( name, tempOptions );
}

void logTerm()
{
    if ( Logger::smInstance )
        delete Logger::smInstance;
}
