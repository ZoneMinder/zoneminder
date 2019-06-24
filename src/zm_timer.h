//
// ZoneMinder Timer Class Interface, $Date$, $Revision$
// Copyright (C) 2001-2008 Philip Coombes
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
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
// 

#ifndef ZM_TIMER_H
#define ZM_TIMER_H

#ifdef HAVE_SYS_SYSCALL_H
#include <sys/syscall.h>
#endif // HAVE_SYS_SYSCALL_H
#include "zm_thread.h"

#include "zm_exception.h"

class Timer
{
private:
  class TimerException : public Exception
  {
  private:
#ifndef SOLARIS
    pid_t pid() {
    pid_t tid;
#ifdef __FreeBSD__
    long lwpid;
    thr_self(&lwpid);
    tid = lwpid;
#else
  #ifdef __FreeBSD_kernel__
    if ( (syscall(SYS_thr_self, &tid)) < 0 ) // Thread/Process id
  #else
    tid=syscall(SYS_gettid);
  #endif
#endif
    return tid;
  }
#else
  pthread_t pid() { return( pthread_self() ); }
#endif
  public:
    explicit TimerException( const std::string &message ) : Exception( stringtf("(%d) ", (long int)pid())+message ) {
    }
  };

  class TimerThread : public Thread
  {
  private:
    typedef ThreadData<bool> ExpiryFlag;

  private:
    static int mNextTimerId;

  private:
    int mTimerId;
    Timer &mTimer;
    int mDuration;
    int mRepeat;
    int mReset;
    ExpiryFlag mExpiryFlag;
    Mutex mAccessMutex;

  private:
    void quit()
    {
      cancel();
    }

  public:
    TimerThread( Timer &timer, int timeout, bool repeat );
    ~TimerThread();

    void cancel();
    void reset();
    int run();
  };

protected:
  TimerThread mTimerThread;

protected:
  Timer( int timeout, bool repeat=false );

public:
  virtual ~Timer();

protected:
  virtual void expire()=0;

public:
  void cancel();
  void reset();
};

#endif // ZM_TIMER_H
