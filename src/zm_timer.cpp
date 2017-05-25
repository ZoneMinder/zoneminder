//
// ZoneMinder Timer Class Implementation, $Date$, $Revision$
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

#include "zm_timer.h"

#include "zm_logger.h"

int Timer::TimerThread::mNextTimerId = 0;

Timer::TimerThread::TimerThread( Timer &timer, int duration, bool repeat ) :
  mTimerId( 0 ),
  mTimer( timer ),
  mDuration( duration ),
  mRepeat( repeat ),
  mReset( false ),
  mExpiryFlag( true )
{
  mAccessMutex.lock();
  mTimerId = mNextTimerId++;
  Debug( 5, "Creating timer %d for %d seconds%s", mTimerId, mDuration, mRepeat?", repeating":"" );
  mAccessMutex.unlock();
}

Timer::TimerThread::~TimerThread()
{
  cancel();
}

void Timer::TimerThread::cancel()
{
  mAccessMutex.lock();
  if ( mRunning )
  {
    Debug( 4, "Cancelling timer %d", mTimerId );
    mRepeat = false;
    mReset = false;
    mExpiryFlag.updateValueSignal( false );
  }
  mAccessMutex.unlock();
}

void Timer::TimerThread::reset()
{
  mAccessMutex.lock();
  if ( mRunning )
  {
    Debug( 4, "Resetting timer" );
    mReset = true;
    mExpiryFlag.updateValueSignal( false );
  }
  else
  {
    Error( "Attempting to reset expired timer %d", mTimerId );
  }
  mAccessMutex.unlock();
}

int Timer::TimerThread::run()
{
  Debug( 4, "Starting timer %d for %d seconds", mTimerId, mDuration );
  bool timerExpired = false;
  do
  {
    mAccessMutex.lock();
    mReset = false;
    mExpiryFlag.setValue( true );
    mAccessMutex.unlock();
    timerExpired = mExpiryFlag.getUpdatedValue( mDuration );
    mAccessMutex.lock();
    if ( timerExpired )
    {
      Debug( 4, "Timer %d expired", mTimerId );
      mTimer.expire();
    }
    else
    {
      Debug( 4, "Timer %d %s", mTimerId, mReset?"reset":"cancelled" );
    }
    mAccessMutex.unlock();
  } while ( mRepeat || (mReset && !timerExpired) );
  return( timerExpired );
}

Timer::Timer( int timeout, bool repeat ) : mTimerThread( *this, timeout, repeat )
{
  mTimerThread.start();
}

Timer::~Timer()
{
  //cancel();
}

void Timer::Timer::cancel()
{
  mTimerThread.cancel();
}

void Timer::Timer::reset()
{
  mTimerThread.reset();
}

