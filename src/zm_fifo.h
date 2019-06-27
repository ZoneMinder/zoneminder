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

#if 0
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <errno.h>
#include <limits.h>
#include <time.h>
#include <sys/time.h>
#include <sys/stat.h>
#include <sys/types.h>

#include "zm.h"
#include "zm_image.h"
#endif
#include "zm_monitor.h"
#include "zm_stream.h"

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
    typedef enum { MJPEG, RAW } StreamType;
    StreamType  stream_type;
    bool sendMJEGFrames();
    bool sendRAWFrames();
    void processCommand(const CmdMsg *msg) {}

 public:
    FifoStream() {}
    static void fifo_create_if_missing(
        const char * path,
        bool delete_fake_fifo = true);
    void setStreamStart(const char * path);
    void setStreamStart(int monitor_id, const char * format);
    void runStream();
};
#endif  // ZM_FIFO_H
