//
// ZoneMinder Thread Class Implementation, $Date$, $Revision$
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

#include "zm_thread.h"

#include "zm_logger.h"
#include "zm_utils.h"

#include <string.h>
#include <signal.h>
#include <errno.h>
#include <sys/time.h>

struct timespec getTimeout( int secs ) {
  struct timespec timeout;
  struct timeval temp_timeout;
  gettimeofday(&temp_timeout, 0);
  timeout.tv_sec = temp_timeout.tv_sec + secs;
  timeout.tv_nsec = temp_timeout.tv_usec*1000;
  return timeout;
}

struct timespec getTimeout( double secs ) {
  struct timespec timeout;
  struct timeval temp_timeout;
  gettimeofday( &temp_timeout, 0 );
  timeout.tv_sec = temp_timeout.tv_sec + int(secs);
  timeout.tv_nsec = temp_timeout.tv_usec += (long int)(1000000000.0*(secs-int(secs)));
  if ( timeout.tv_nsec > 1000000000 ) {
    timeout.tv_sec += 1;
    timeout.tv_nsec -= 1000000000;
  }
  return timeout;
}

Mutex::Mutex() {
  if ( pthread_mutex_init(&mMutex, NULL) < 0 )
    Error("Unable to create pthread mutex: %s", strerror(errno));
}

Mutex::~Mutex() {
  if ( locked() )
    Warning("Destroying mutex when locked");
  if ( pthread_mutex_destroy(&mMutex) < 0 )
    Error("Unable to destroy pthread mutex: %s", strerror(errno));
}

int Mutex::trylock() {
  return pthread_mutex_trylock(&mMutex);
}
void Mutex::lock() {
  if ( pthread_mutex_lock(&mMutex) < 0 )
    throw ThreadException( stringtf( "Unable to lock pthread mutex: %s", strerror(errno) ) );
}

void Mutex::lock( int secs ) {
  struct timespec timeout = getTimeout( secs );
  if ( pthread_mutex_timedlock( &mMutex, &timeout ) < 0 )
    throw ThreadException( stringtf( "Unable to timedlock pthread mutex: %s", strerror(errno) ) );
}

void Mutex::lock( double secs ) {
  struct timespec timeout = getTimeout( secs );
  if ( pthread_mutex_timedlock( &mMutex, &timeout ) < 0 )
    throw ThreadException( stringtf( "Unable to timedlock pthread mutex: %s", strerror(errno) ) );
}

void Mutex::unlock() {
  if ( pthread_mutex_unlock( &mMutex ) < 0 )
    throw ThreadException( stringtf( "Unable to unlock pthread mutex: %s", strerror(errno) ) );
}

bool Mutex::locked() {
  int state = pthread_mutex_trylock( &mMutex );
  if ( state != 0 && state != EBUSY )
    throw ThreadException( stringtf( "Unable to trylock pthread mutex: %s", strerror(errno) ) );
  if ( state != EBUSY )
    unlock();
  return( state == EBUSY );
}

RecursiveMutex::RecursiveMutex() {
  pthread_mutexattr_t attr;
  pthread_mutexattr_init(&attr);
  pthread_mutexattr_settype(&attr, PTHREAD_MUTEX_RECURSIVE);

  if ( pthread_mutex_init(&mMutex, &attr) < 0 )
    Error("Unable to create pthread mutex: %s", strerror(errno));
}

Condition::Condition( Mutex &mutex ) : mMutex( mutex ) {
  if ( pthread_cond_init( &mCondition, NULL ) < 0 )
    throw ThreadException( stringtf( "Unable to create pthread condition: %s", strerror(errno) ) );
}

Condition::~Condition() {
  if ( pthread_cond_destroy( &mCondition ) < 0 )
    Error("Unable to destroy pthread condition: %s", strerror(errno));
}

void Condition::wait() {
  // Locking done outside of this function
  if ( pthread_cond_wait(&mCondition, mMutex.getMutex()) < 0 )
    throw ThreadException(stringtf("Unable to wait pthread condition: %s", strerror(errno)));
}

bool Condition::wait(int secs) {
  // Locking done outside of this function
  Debug(8, "Waiting for %d seconds", secs);
  struct timespec timeout = getTimeout(secs);
  if (
      ( pthread_cond_timedwait(&mCondition, mMutex.getMutex(), &timeout) < 0 )
      &&
     ( errno != ETIMEDOUT ) 
     )
    throw ThreadException(stringtf("Unable to timedwait pthread condition: %s", strerror(errno)));
  return errno != ETIMEDOUT;
}

bool Condition::wait( double secs ) {
  // Locking done outside of this function
  struct timespec timeout = getTimeout( secs );
  if (
      (pthread_cond_timedwait( &mCondition, mMutex.getMutex(), &timeout ) < 0)
      &&
      (errno != ETIMEDOUT) )
    throw ThreadException( stringtf( "Unable to timedwait pthread condition: %s", strerror(errno) ) );
  return errno != ETIMEDOUT;
}

void Condition::signal() {
  if ( pthread_cond_signal( &mCondition ) < 0 )
    throw ThreadException( stringtf( "Unable to signal pthread condition: %s", strerror(errno) ) );
}

void Condition::broadcast() {
  if ( pthread_cond_broadcast( &mCondition ) < 0 )
    throw ThreadException( stringtf( "Unable to broadcast pthread condition: %s", strerror(errno) ) );
}

template <class T> const T ThreadData<T>::getValue() const {
  mMutex.lock();
  const T valueCopy = mValue;
  mMutex.unlock();
  return valueCopy;
}

template <class T> T ThreadData<T>::setValue(const T value) {
  mMutex.lock();
  const T valueCopy = mValue = value;
  mMutex.unlock();
  return valueCopy;
}

template <class T> const T ThreadData<T>::getUpdatedValue() const {
  Debug(8, "Waiting for value update, %p", this);
  mMutex.lock();
  mChanged = false;
  mCondition.wait();
  const T valueCopy = mValue;
  mMutex.unlock();
  Debug(9, "Got value update, %p", this);
  return valueCopy;
}

template <class T> const T ThreadData<T>::getUpdatedValue(double secs) const {
  Debug(8, "Waiting for value update, %.2f secs, %p", secs, this);
  mMutex.lock();
  mChanged = false;
  //do {
    mCondition.wait(secs);
  //} while ( !mChanged );
  const T valueCopy = mValue;
  mMutex.unlock();
  Debug(9, "Got value update, %p", this);
  return valueCopy;
}

template <class T> const T ThreadData<T>::getUpdatedValue(int secs) const {
  Debug(8, "Waiting for value update, %d secs, %p", secs, this);
  mMutex.lock();
  mChanged = false;
  //do {
    mCondition.wait(secs);
  //} while ( !mChanged );
  const T valueCopy = mValue;
  mMutex.unlock();
  Debug(9, "Got value update, %p", this);
  return valueCopy;
}

template <class T> void ThreadData<T>::updateValueSignal(const T value) {
  Debug(8, "Updating value with signal, %p", this);
  mMutex.lock();
  mValue = value;
  mChanged = true;
  mCondition.signal();
  mMutex.unlock();
  Debug(9, "Updated value, %p", this);
}

template <class T> void ThreadData<T>::updateValueBroadcast( const T value ) {
  Debug(8, "Updating value with broadcast, %p", this);
  mMutex.lock();
  mValue = value;
  mChanged = true;
  mCondition.broadcast();
  mMutex.unlock();
  Debug(9, "Updated value, %p", this);
}

Thread::Thread() :
  mThreadCondition( mThreadMutex ),
  mPid( -1 ),
  mStarted( false ),
  mRunning( false )
{
  Debug( 1, "Creating thread" );
}

Thread::~Thread() {
  Debug( 1, "Destroying thread %d", mPid );
  if ( mStarted )
    join();
}

void *Thread::mThreadFunc( void *arg ) {
  Debug( 2, "Invoking thread" );

  Thread *thisPtr = (Thread *)arg;
  thisPtr->status = 0;
  try {
    thisPtr->mThreadMutex.lock();
    thisPtr->mPid = thisPtr->id();
    thisPtr->mThreadCondition.signal();
    thisPtr->mThreadMutex.unlock();
    thisPtr->mRunning = true;
    thisPtr->status = thisPtr->run();
    thisPtr->mRunning = false;
    Debug( 2, "Exiting thread, status %p", (void *)&(thisPtr->status) );
    return (void *)&(thisPtr->status);
  } catch ( const ThreadException &e ) {
    Error( "%s", e.getMessage().c_str() );
    thisPtr->mRunning = false;
    Debug( 2, "Exiting thread after exception, status %p", (void *)-1 );
    return (void *)-1;
  }
}

void Thread::start() {
  Debug( 1, "Starting thread" );
  if ( isThread() )
    throw ThreadException( "Can't self start thread" );
  mThreadMutex.lock();
  if ( !mStarted ) {
    pthread_attr_t threadAttrs;
    pthread_attr_init( &threadAttrs );
    pthread_attr_setscope( &threadAttrs, PTHREAD_SCOPE_SYSTEM );

    mStarted = true;
    if ( pthread_create( &mThread, &threadAttrs, mThreadFunc, this ) < 0 )
      throw ThreadException( stringtf( "Can't create thread: %s", strerror(errno) ) );
    pthread_attr_destroy( &threadAttrs );
  } else {
    Error( "Attempt to start already running thread %d", mPid );
  }
  mThreadCondition.wait();
  mThreadMutex.unlock();
  Debug( 1, "Started thread %d", mPid );
}

void Thread::join() {
  Debug( 1, "Joining thread %d", mPid );
  if ( isThread() )
    throw ThreadException( "Can't self join thread" );
  mThreadMutex.lock();
  if ( mPid >= 0 ) {
    if ( mStarted ) {
      void *threadStatus = 0;
      if ( pthread_join( mThread, &threadStatus ) < 0 )
        throw ThreadException( stringtf( "Can't join sender thread: %s", strerror(errno) ) );
      mStarted = false;
      Debug( 1, "Thread %d exited, status %p", mPid, threadStatus );
    } else {
      Warning( "Attempt to join already finished thread %d", mPid );
    }
  } else {
    Warning( "Attempt to join non-started thread %d", mPid );
  }
  mThreadMutex.unlock();
  Debug( 1, "Joined thread %d", mPid );
}

void Thread::kill( int signal ) {
  pthread_kill( mThread, signal );
}

// Some explicit template instantiations
#include "zm_threaddata.cpp"
