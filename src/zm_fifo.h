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
#ifndef ZM_FIFO_H
#define ZM_FIFO_H

#include "zm_stream.h"

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

class FifoStream : public StreamBase {
 private:
    char * stream_path;
    int fd;
    int total_read;
    int bytes_read;
    unsigned int frame_count;
    static void file_create_if_missing(
        const char * path,
        bool is_fifo,
        bool delete_fake_fifo = true
        );

 protected:
    typedef enum { UNKNOWN, MJPEG, RAW } StreamType;
    StreamType  stream_type;
    bool sendMJEGFrames();
    bool sendRAWFrames();
    void processCommand(const CmdMsg *msg) {}

 public:
    FifoStream() : 
      stream_path(nullptr),
      fd(0),
      total_read(0),
      bytes_read(0),
      frame_count(0),
      stream_type(UNKNOWN)
    {}
    static void fifo_create_if_missing(
        const char * path,
        bool delete_fake_fifo = true);
    void setStreamStart(const char * path);
    void setStreamStart(int monitor_id, const char * format);
    void runStream() override;
};
#endif  // ZM_FIFO_H
