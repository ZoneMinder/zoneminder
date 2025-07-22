//
// ZoneMinder MonitorStream Class Interfaces, $Date$, $Revision$
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

#ifndef ZM_MONITORSTREAM_H
#define ZM_MONITORSTREAM_H

#include "zm_stream.h"

class MonitorStream : public StreamBase {
 protected:
  struct SwapImage {
    bool valid = false;
    SystemTimePoint timestamp;
    std::string file_name;
  };

 private:
  SwapImage *temp_image_buffer;
  int temp_image_buffer_count;
  int temp_read_index;
  int temp_write_index;

 protected:
  Microseconds ttl;
  int playback_buffer;
  bool delayed;

 protected:
  bool checkSwapPath(const char *path, bool create_path);
  bool sendFrame(const std::string &filepath, SystemTimePoint timestamp);
  bool sendFrame(Image *image, SystemTimePoint timestamp);
  void processCommand(const CmdMsg *msg) override;
  void SingleImage(int scale=100);
  void SingleImageRaw(int scale=100);
#ifdef HAVE_ZLIB_H
  void SingleImageZip(int scale=100);
#endif

 public:
  MonitorStream() :
    temp_image_buffer(nullptr),
    temp_image_buffer_count(0),
    temp_read_index(0),
    temp_write_index(0),
    ttl(0),
    playback_buffer(0),
    delayed(false)
  {}

  void setStreamBuffer(int p_playback_buffer) {
    playback_buffer = p_playback_buffer;
  }
  void setStreamTTL(time_t p_ttl) {
    ttl = Seconds(p_ttl);
  }
  bool setStreamStart(int monitor_id) {
    return loadMonitor(monitor_id);
  }
  void runStream() override;
};

#endif // ZM_MONITORSTREAM_H
