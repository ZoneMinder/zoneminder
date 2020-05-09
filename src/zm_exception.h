//
// ZoneMinder Exception Class Interface, $Date$, $Revision$
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

#ifndef ZM_EXCEPTION_H
#define ZM_EXCEPTION_H

#include <string>

class Exception
{
protected:
  typedef enum { INFO, WARNING, ERROR, FATAL } Severity;

protected:
  std::string mMessage;
  Severity mSeverity;

public:
  Exception( const std::string &message, Severity severity=ERROR ) : mMessage( message ), mSeverity( severity )
  {
  }

public:
  const std::string &getMessage() const
  {
    return( mMessage );
  }
  Severity getSeverity() const
  {
    return( mSeverity );
  }
  bool isInfo() const
  {
    return( mSeverity == INFO );
  }
  bool isWarning() const
  {
    return( mSeverity == WARNING );
  }
  bool isError() const
  {
    return( mSeverity == ERROR );
  }
  bool isFatal() const
  {
    return( mSeverity == FATAL );
  }
};

#endif // ZM_EXCEPTION_H
