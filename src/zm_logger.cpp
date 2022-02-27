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
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/ 

#include "zm_logger.h"

#include "zm_db.h"
#include "zm_time.h"
#include "zm_utils.h"
#include <libgen.h>
#include <syslog.h>
#include <unistd.h>

#ifdef __FreeBSD__
#include <sys/thr.h>
#endif
#include <cerrno>
#include <csignal>
#include <cstdarg>
#include <cstring>


bool Logger::smInitialised = false;
Logger *Logger::smInstance = nullptr;

Logger::StringMap Logger::smCodes;
Logger::IntMap Logger::smSyslogPriorities;

void Logger::usrHandler(int sig) {
  Logger *logger = fetch();
  if (sig == SIGUSR1)
    logger->level(logger->level()+1);
  else if (sig == SIGUSR2)
    logger->level(logger->level()-1);
  Info("Logger - Level changed to %d %s", logger->level(), smCodes[logger->level()].c_str());
}

Logger::Logger() :
  mLevel(INFO),
  mTerminalLevel(NOLOG),
  mDatabaseLevel(NOLOG),
  mFileLevel(NOLOG),
  mSyslogLevel(NOLOG),
  mEffectiveLevel(NOLOG),
  mDbConnected(false),
  mLogPath(staticConfig.PATH_LOGS.c_str()),
  //  mLogFile( mLogPath+"/"+mId+".log" ),
  mLogFileFP(nullptr),
  mHasTerminal(false),
  mFlush(false) {
  if (smInstance) {
    Panic("Attempt to create second instance of Logger class");
  }

  if (!smInitialised) {
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
    for (int i = DEBUG1; i <= DEBUG9; i++) {
      snprintf(code, sizeof(code), "DB%d", i);
      smCodes[i] = code;
      smSyslogPriorities[i] = LOG_DEBUG;
    }

    smInitialised = true;
  }  // end if ! smInitialised

  if (fileno(stderr) && isatty(fileno(stderr))) {
    mHasTerminal = true;
    mTerminalLevel = WARNING;
  }
}  // End Logger::Logger

Logger::~Logger() {
  terminate();
  smCodes.clear();
  smSyslogPriorities.clear();
  smInitialised = false;
}

void Logger::initialise(const std::string &id, const Options &options) {
  char *envPtr;

  if ( !id.empty() )
    this->id(id);

  std::string tempLogFile;

  if ( (envPtr = getTargettedEnv("LOG_FILE")) ) {
    tempLogFile = envPtr;
  } else if ( options.mLogFile.size() ) {
    tempLogFile = options.mLogFile;
  } else {
    // options.mLogPath defaults to '.' so only use it if we don't already have a path
    if ( (!mLogPath.size()) || options.mLogPath != "." ) {
      mLogPath = options.mLogPath;
    }
    tempLogFile = mLogPath+"/"+mId+".log";
  }

  Level tempLevel = INFO;
  Level tempTerminalLevel = mTerminalLevel;

  if ( options.mTerminalLevel != NOOPT )
    tempTerminalLevel = options.mTerminalLevel;

  // DEBUG1 == 1.  So >= DEBUG1, we set to DEBUG9?! Why? icon: because log_level_database only goes up to debug.
  Level tempDatabaseLevel;
  if ( options.mDatabaseLevel != NOOPT )
    tempDatabaseLevel = options.mDatabaseLevel;
  else
    tempDatabaseLevel = config.log_level_database >= DEBUG1 ? DEBUG9 : config.log_level_database;

  Level tempFileLevel;
  if ( options.mFileLevel != NOOPT )
    tempFileLevel = options.mFileLevel;
  else
    tempFileLevel = config.log_level_file >= DEBUG1 ? DEBUG9 : config.log_level_file;

  Level tempSyslogLevel;
  if ( options.mSyslogLevel != NOOPT )
    tempSyslogLevel = options.mSyslogLevel;
  else
    tempSyslogLevel = config.log_level_syslog >= DEBUG1 ? DEBUG9 : config.log_level_syslog;

  // Legacy
  if ( (envPtr = getenv("LOG_PRINT")) )
    tempTerminalLevel = atoi(envPtr) ? DEBUG9 : NOLOG;

  if ( (envPtr = getTargettedEnv("LOG_LEVEL")) )
    tempLevel = atoi(envPtr);

  if ( (envPtr = getTargettedEnv("LOG_LEVEL_TERM")) )
    tempTerminalLevel = atoi(envPtr);
  if ( (envPtr = getTargettedEnv("LOG_LEVEL_DATABASE")) )
    tempDatabaseLevel = atoi(envPtr);
  if ( (envPtr = getTargettedEnv("LOG_LEVEL_FILE")) )
    tempFileLevel = atoi(envPtr);
  if ( (envPtr = getTargettedEnv("LOG_LEVEL_SYSLOG")) )
    tempSyslogLevel = atoi(envPtr);

  if ( config.log_debug ) {
    StringVector targets = Split(config.log_debug_target, "|");
    for ( unsigned int i = 0; i < targets.size(); i++ ) {
      const std::string &target = targets[i];
      if ( target == mId || target == "_"+mId || target == "_"+mIdRoot || target == "" ) {
        if ( config.log_debug_level > NOLOG ) {
          tempLevel = config.log_debug_level;
          if ( config.log_debug_file[0] ) {
            tempLogFile = config.log_debug_file;
            tempFileLevel = tempLevel;
          }
        }
      }
    }  // end foreach target
  } else {
    // if we don't have debug turned on, then the max effective log level is INFO
    if ( tempSyslogLevel > INFO ) tempSyslogLevel = INFO;
    if ( tempFileLevel > INFO ) tempFileLevel = INFO;
    if ( tempTerminalLevel > INFO ) tempTerminalLevel = INFO;
    if ( tempDatabaseLevel > INFO ) tempDatabaseLevel = INFO;
    if ( tempLevel > INFO ) tempLevel = INFO;
  }  // end if config.log_debug

  logFile(tempLogFile);

  terminalLevel(tempTerminalLevel);
  databaseLevel(tempDatabaseLevel);
  fileLevel(tempFileLevel);
  syslogLevel(tempSyslogLevel);

  level(tempLevel);

  mFlush = false;
  if ( (envPtr = getenv("LOG_FLUSH")) ) {
    mFlush = atoi(envPtr);
  } else if ( config.log_debug ) {
    mFlush = true;
  }

  {
    struct sigaction action;
    memset(&action, 0, sizeof(action));
    action.sa_handler = usrHandler;
    action.sa_flags = SA_RESTART;

    // Does this REALLY need to be fatal?
    if ( sigaction(SIGUSR1, &action, 0) < 0 ) {
      Fatal("sigaction(), error = %s", strerror(errno));
    }
    if ( sigaction(SIGUSR2, &action, 0) < 0) {
      Fatal("sigaction(), error = %s", strerror(errno));
    }
  }

  mInitialised = true;

  Debug(1, "LogOpts: level=%s effective=%s, screen=%s, database=%s, logfile=%s->%s, syslog=%s",
      smCodes[mLevel].c_str(),
      smCodes[mEffectiveLevel].c_str(),
      smCodes[mTerminalLevel].c_str(),
      smCodes[mDatabaseLevel].c_str(),
      smCodes[mFileLevel].c_str(),
      mLogFile.c_str(),
      smCodes[mSyslogLevel].c_str()
      );
}

void Logger::terminate() {
  if ( mFileLevel > NOLOG )
    closeFile();

  if ( mSyslogLevel > NOLOG )
    closeSyslog();

  if ( mDatabaseLevel > NOLOG )
    closeDatabase();
}

// These don't belong here, they have nothing to do with logging
bool Logger::boolEnv(const std::string &name, bool defaultValue) {
  const char *envPtr = getenv(name.c_str());
  return envPtr ? atoi(envPtr) : defaultValue;
}

int Logger::intEnv(const std::string &name, bool defaultValue) {
  const char *envPtr = getenv(name.c_str());
  return envPtr ? atoi(envPtr) : defaultValue;
}

std::string Logger::strEnv(const std::string &name, const std::string &defaultValue) {
  const char *envPtr = getenv(name.c_str());
  return envPtr ? envPtr : defaultValue;
}

char *Logger::getTargettedEnv(const std::string &name) {
  std::string envName = name+"_"+mId;
  char *envPtr = getenv(envName.c_str());
  if ( !envPtr && mId != mIdRoot ) {
    envName = name+"_"+mIdRoot;
    envPtr = getenv(envName.c_str());
  }
  if ( !envPtr )
    envPtr = getenv(name.c_str());
  return envPtr;
}

const std::string &Logger::id(const std::string &id) {
  std::string tempId = id;

  size_t pos;
  // Remove whitespace
  while ( (pos = tempId.find_first_of(" \t")) != std::string::npos ) {
    tempId.replace(pos, 1, "");
  }
  // Replace non-alphanum with underscore
  while ( (pos = tempId.find_first_not_of("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_")) != std::string::npos ) {
    tempId.replace(pos, 1, "_");
  }
  if ( mId != tempId ) {
    mId = tempId;
    pos = mId.find('_');
    if ( pos != std::string::npos ) {
      mIdRoot = mId.substr(0, pos);
      if ( ++pos < mId.size() )
        mIdArgs = mId.substr(pos);
    }
  }
  return mId;
}

Logger::Level Logger::level(Logger::Level level) {
  if (level > NOOPT) {
    mLevel = limit(level);

    mEffectiveLevel = NOLOG;
    if (mTerminalLevel > mEffectiveLevel)
      mEffectiveLevel = mTerminalLevel;
    if (mDatabaseLevel > mEffectiveLevel)
      mEffectiveLevel = mDatabaseLevel;
    if (mFileLevel > mEffectiveLevel)
      mEffectiveLevel = mFileLevel;
    if (mSyslogLevel > mEffectiveLevel)
      mEffectiveLevel = mSyslogLevel;
    if (mEffectiveLevel > mLevel)
      mEffectiveLevel = mLevel;

    // DEBUG levels should flush
    if (mLevel > INFO)
      mFlush = true;
  }
  return mLevel;
}

Logger::Level Logger::terminalLevel(Logger::Level terminalLevel) {
  if ( terminalLevel > NOOPT ) {
    if ( !mHasTerminal )
      terminalLevel = NOLOG;
    mTerminalLevel = limit(terminalLevel);
  }
  return mTerminalLevel;
}

Logger::Level Logger::databaseLevel(Logger::Level databaseLevel) {
  if (databaseLevel > NOOPT) {
    databaseLevel = limit(databaseLevel);
    if (mDatabaseLevel != databaseLevel) {
      if ((databaseLevel > NOLOG) && (mDatabaseLevel <= NOLOG)) { // <= NOLOG would be NOOPT
        if (!zmDbConnected) {
          databaseLevel = NOLOG;
        }
      }
      mDatabaseLevel = databaseLevel;
    }
  }

  return mDatabaseLevel;
}

Logger::Level Logger::fileLevel(Logger::Level fileLevel) {
  if (fileLevel > NOOPT) {
    fileLevel = limit(fileLevel);
    // Always close, because we may have changed file names
    if (mFileLevel > NOLOG)
      closeFile();
    mFileLevel = fileLevel;
    // Don't try to open it here because it will create the log file even if we never write to it.
  }
  return mFileLevel;
}

Logger::Level Logger::syslogLevel(Logger::Level syslogLevel) {
  if (syslogLevel > NOOPT) {
    syslogLevel = limit(syslogLevel);
    if (mSyslogLevel != syslogLevel) {
      if (mSyslogLevel > NOLOG)
        closeSyslog();
      mSyslogLevel = syslogLevel;
      if (mSyslogLevel > NOLOG)
        openSyslog();
    }
  }
  return mSyslogLevel;
}

void Logger::logFile(const std::string &logFile) {
  bool addLogPid = false;
  std::string tempLogFile = logFile;
  if (tempLogFile[tempLogFile.length()-1] == '+') {
    tempLogFile.resize(tempLogFile.length()-1);
    addLogPid = true;
  }
  if (addLogPid)
    mLogFile = stringtf("%s.%05d", tempLogFile.c_str(), getpid());
  else
    mLogFile = tempLogFile;
}

void Logger::openFile() {
  if (mLogFile.size()) {
    if ( (mLogFileFP = fopen(mLogFile.c_str(), "a")) == nullptr ) {
      mFileLevel = NOLOG;
      Error("fopen() for %s, error = %s", mLogFile.c_str(), strerror(errno));
    }
  } else {
    puts("Called Logger::openFile() without a filename");
  }
}

void Logger::closeFile() {
  if (mLogFileFP) {
    fflush(mLogFileFP);
    if (fclose(mLogFileFP) < 0) {
      mLogFileFP = nullptr;
      Error("fclose(), error = %s", strerror(errno));
    }
    mLogFileFP = nullptr;
  }
}

void Logger::closeDatabase() {
}

void Logger::openSyslog() {
  (void) openlog(mId.c_str(), LOG_PID|LOG_NDELAY, LOG_LOCAL1);
}

void Logger::closeSyslog() {
  (void) closelog();
}

void Logger::logPrint(bool hex, const char *filepath, int line, int level, const char *fstring, ...) {
  if (level > mEffectiveLevel) return;
  if (level < PANIC || level > DEBUG9)
    Panic("Invalid logger level %d", level);
    
  log_mutex.lock();
  // Can we save some cycles by having these as members and not allocate them on the fly? I think so.
  char            timeString[64];
  char            logString[4096]; // SQL TEXT can hold 64k so we could go up to 32k here but why?
  va_list         argPtr;

  const char *base = strrchr(filepath, '/');
  const char *file = base ? base+1 : filepath;
  const char *classString = smCodes[level].c_str();

  SystemTimePoint now = std::chrono::system_clock::now();
  time_t now_sec = std::chrono::system_clock::to_time_t(now);
  Microseconds now_frac = std::chrono::duration_cast<Microseconds>(
      now.time_since_epoch() - std::chrono::duration_cast<Seconds>(now.time_since_epoch()));

  char *timePtr = timeString;
  tm now_tm = {};
  timePtr += strftime(timePtr, sizeof(timeString), "%x %H:%M:%S", localtime_r(&now_sec, &now_tm));
  snprintf(timePtr, sizeof(timeString) - (timePtr - timeString), ".%06" PRIi64, static_cast<int64>(now_frac.count()));

  pid_t tid;
#ifdef __FreeBSD__
  long lwpid;
  thr_self(&lwpid);
  tid = lwpid;

  if ( tid < 0 )  // Thread/Process id
#else
  #ifdef HAVE_SYSCALL
    #ifdef __FreeBSD_kernel__
    if ((syscall(SYS_thr_self, &tid)) < 0)  // Thread/Process id

    # else
      // SOLARIS doesn't have SYS_gettid; don't assume
      #ifdef SYS_gettid
    if ((tid = syscall(SYS_gettid)) < 0)  // Thread/Process id
      #endif // SYS_gettid
    #endif
  #endif // HAVE_SYSCALL
#endif
    tid = getpid(); // Process id

  char *logPtr = logString;
  logPtr += snprintf(logPtr, sizeof(logString), "%s %s[%d].%s-%s/%d [",
      timeString,
      mId.c_str(),
      tid,
      classString,
      file,
      line
      );
  char *syslogStart = logPtr;

  va_start(argPtr, fstring);
  if ( hex ) {
    unsigned char *data = va_arg(argPtr, unsigned char *);
    int len = va_arg(argPtr, int);
    int i;
    logPtr += snprintf(logPtr, sizeof(logString)-(logPtr-logString), "%d:", len);
    for ( i = 0; i < len; i++ ) {
      logPtr += snprintf(logPtr, sizeof(logString)-(logPtr-logString), " %02x", data[i]);
    }
  } else {
    logPtr += vsnprintf(logPtr, sizeof(logString)-(logPtr-logString), fstring, argPtr);
  }
  va_end(argPtr);
  char *syslogEnd = logPtr;

  if ( static_cast<size_t>(logPtr - logString) >= sizeof(logString) ) {
    // vsnprintf won't exceed the the buffer, but it might hit the end.
    logPtr = logString + sizeof(logString)-3;
  }
  strncpy(logPtr, "]\n", sizeof(logString)-(logPtr-logString));

  if (level <= mTerminalLevel) {
    puts(logString);
    fflush(stdout);
  }

  if (level <= mFileLevel) {
    if (!mLogFileFP) {
      // FIXME unlocking here is a problem. Another thread could sneak in.
      // We are using a recursive mutex so unlocking shouldn't be neccessary
      //log_mutex.unlock();
      // We do this here so that we only create the file if we ever write to it.
      openFile();
      //log_mutex.lock();
    }
    if (mLogFileFP) {
      fputs(logString, mLogFileFP);
      if (mFlush) fflush(mLogFileFP);
    } else if (mTerminalLevel != NOLOG) {
      puts("Logging to file but failed to open it\n");
    }
  }  // end if level <= mFileLevel

  if (level <= mDatabaseLevel) {
    if (zmDbConnected) {
      std::string escapedString = zmDbEscapeString({syslogStart, syslogEnd});

      std::string sql_string = stringtf(
          "INSERT INTO `Logs` "
          "( `TimeKey`, `Component`, `ServerId`, `Pid`, `Level`, `Code`, `Message`, `File`, `Line` )"
          " VALUES "
          "( %ld.%06" PRIi64 ", '%s', %d, %d, %d, '%s', '%s', '%s', %d )",
          now_sec, static_cast<int64>(now_frac.count()), mId.c_str(), staticConfig.SERVER_ID, tid, level, classString,
          escapedString.c_str(), file, line);
      dbQueue.push(std::move(sql_string));
    } else {
      puts("Db is closed");
    }
  }  // end if level <= mDatabaseLevel

  if (level <= mSyslogLevel) {
    *syslogEnd = '\0';
    syslog(smSyslogPriorities[level], "%s [%s] [%s]", classString, mId.c_str(), syslogStart);
  }

  log_mutex.unlock();
  if (level <= FATAL) {
    logTerm();
    zmDbClose();
    if (level <= PANIC) abort();
    exit(-1);
  }
}  // end logPrint

void logInit(const std::string &id, const Logger::Options &options) {
  if (Logger::smInstance) {
    delete Logger::smInstance;
    Logger::smInstance = nullptr;
  }

  Logger::smInstance = new Logger();
  Logger::smInstance->initialise(id, options);
}

void logTerm() {
  if (Logger::smInstance) {
    delete Logger::smInstance;
    Logger::smInstance = nullptr;
  }
}
