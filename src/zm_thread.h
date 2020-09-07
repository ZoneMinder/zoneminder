//
// ZoneMinder Thread Class Interface, $Date$, $Revision$
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

#ifndef ZM_THREAD_H
#define ZM_THREAD_H

class RecursiveMutex;


#include "zm_config.h"
#include <unistd.h>
#include <pthread.h>
#ifdef HAVE_SYS_SYSCALL_H
#include <sys/syscall.h>
#endif // HAVE_SYS_SYSCALL_H
#include "zm_exception.h"
#include "zm_utils.h"
#ifdef __FreeBSD__
#include <sys/thr.h>
#endif

class ThreadException : public Exception {
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
  # else
    tid=syscall(SYS_gettid); 
  #endif
#endif
    return tid;
  }  
#else
  pthread_t pid() { return( pthread_self() ); }
#endif
public:
  explicit ThreadException(const std::string &message) :
    Exception(stringtf("(%d) ", (long int)pid())+message)
  {
  }
};

class Mutex {
  friend class Condition;

  private:
    pthread_mutex_t mMutex;

  public:
    Mutex();
    ~Mutex();

  private:
    pthread_mutex_t *getMutex() {
      return &mMutex;
    }

  public:
    int trylock();
    void lock();
    void lock( int secs );
    void lock( double secs );
    void unlock();
    bool locked();
};

class RecursiveMutex : public Mutex {
  private:
    pthread_mutex_t mMutex;
  public:
    RecursiveMutex();
};

class ScopedMutex {
private:
  Mutex &mMutex;

public:
  explicit ScopedMutex( Mutex &mutex ) : mMutex( mutex ) {
    mMutex.lock();
  }
  ~ScopedMutex() {
    mMutex.unlock();
  }

private:
  ScopedMutex( const ScopedMutex & );
};

class Condition {
private:
  Mutex &mMutex;
  pthread_cond_t mCondition;

public:
  explicit Condition(Mutex &mutex);
  ~Condition();

  void wait();
  bool wait(int secs);
  bool wait(double secs);
  void signal();
  void broadcast();
};

class Semaphore : public Condition {
private:
  Mutex mMutex;

public:
  Semaphore() : Condition(mMutex) {
  }

  void wait() {
    mMutex.lock();
    Condition::wait();
    mMutex.unlock();
  }
  bool wait(int secs) {
    mMutex.lock();
    bool result = Condition::wait(secs);
    mMutex.unlock();
    return result;
  }
  bool wait(double secs) {
    mMutex.lock();
    bool result = Condition::wait(secs);
    mMutex.unlock();
    return result;
  }
  void signal() {
    mMutex.lock();
    Condition::signal();
    mMutex.unlock();
  }
  void broadcast() {
    mMutex.lock();
    Condition::broadcast();
    mMutex.unlock();
  }
};

template <class T> class ThreadData {
private:
  T mValue;
  mutable bool mChanged;
  mutable Mutex mMutex;
  mutable Condition mCondition;

public:
  __attribute__((used)) ThreadData() :
    mValue(0), mCondition(mMutex)
  {
    mChanged = false;
  }
  explicit __attribute__((used)) ThreadData(T value) :
    mValue(value), mCondition(mMutex) {
    mChanged = false;
  }

  __attribute__((used)) operator T() const {
    return getValue();
  }
  __attribute__((used)) const T operator=( const T value ) {
    return setValue(value);
  }

  __attribute__((used)) const T getValueImmediate() const {
    return mValue;
  }
  __attribute__((used)) T setValueImmediate( const T value ) {
    return mValue = value;
  }
  __attribute__((used)) const T getValue() const;
  __attribute__((used)) T setValue( const T value );
  __attribute__((used)) const T getUpdatedValue() const;
  __attribute__((used)) const T getUpdatedValue(double secs) const;
  __attribute__((used)) const T getUpdatedValue(int secs) const;
  __attribute__((used)) void updateValueSignal(const T value);
  __attribute__((used)) void updateValueBroadcast(const T value);
};

class Thread {
public:
  typedef void *(*ThreadFunc)( void * );

protected:
  pthread_t mThread;

  Mutex mThreadMutex;
  Condition mThreadCondition;
#ifndef SOLARIS
  pid_t mPid;
#else
  pthread_t mPid;
#endif
  bool  mStarted;
  bool  mRunning;
  int   status;		// Used in various functions to get around return a local variable

protected:
  Thread();
  virtual ~Thread();

#ifndef SOLARIS
  pid_t id() const {
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
  pthread_t id() const {
    return pthread_self();
  }
#endif
  void exit( int p_status = 0 ) {
    //INFO( "Exiting" );
    pthread_exit( (void *)&p_status );
  }
  static void *mThreadFunc( void *arg );

public:
  virtual int run() = 0;

  void start();
  void join();
  void kill( int signal );
  bool isThread() {
    return( mPid > -1 && pthread_equal( pthread_self(), mThread ) );
  }
  bool isStarted() const { return mStarted; }
  bool isRunning() const { return mRunning; }
};

#endif // ZM_THREAD_H
