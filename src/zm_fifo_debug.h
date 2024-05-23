//
// ZoneMinder Fifo Debug
// Copyright (C) 2019 ZoneMinder LLC
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
#ifndef ZM_FIFO_DEBUG_H
#define ZM_FIFO_DEBUG_H

class Monitor;

#define zmFifoDbgPrintf(level, params...) {\
  zmFifoDbgOutput(0, __FILE__, __LINE__, level, ##params);\
  }

#ifndef ZM_DBG_OFF
#define FifoDebug(level, params...) zmFifoDbgPrintf(level, ##params)
#else
#define FifoDebug(level, params...)
#endif
void zmFifoDbgOutput(
  int hex,
  const char * const file,
  const int line,
  const int level,
  const char *fstring,
  ...) __attribute__((format(printf, 5, 6)));
int zmFifoDbgInit(Monitor * monitor);

#endif  // ZM_FIFO_DEBUG_H
