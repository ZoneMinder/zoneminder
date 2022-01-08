//
// ZoneMinder Fifo Stream
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
#ifndef ZM_FIFO_STREAM_H
#define ZM_FIFO_STREAM_H

#include "zm_stream.h"

class Monitor;

class FifoStream : public StreamBase {
 private:
    std::string stream_path;
    int total_read;
    int bytes_read;

 protected:
    typedef enum { UNKNOWN, MJPEG, RAW } StreamType;
    StreamType  stream_type;
    bool sendMJEGFrames();
    bool sendRAWFrames();
    void processCommand(const CmdMsg *msg) override {}

 public:
    FifoStream() : 
      StreamBase(),
      total_read(0),
      bytes_read(0),
      stream_type(UNKNOWN)
    {}

    void setStreamStart(const std::string &path);
    void setStreamStart(int monitor_id, const char * format);
    void runStream() override;
};
#endif  // ZM_FIFO_STREAM_H
