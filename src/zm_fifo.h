//
// ZoneMinder Fifo
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
#include "zm_packet.h"

class Monitor;

class Fifo {
 private:
  std::string path;
  bool on_blocking_abort;
  FILE *outfile;
  int raw_fd;

 public:
  static void file_create_if_missing(const std::string &path, bool is_fifo, bool delete_fake_fifo = true);
  static void fifo_create_if_missing(const std::string &path, bool delete_fake_fifo = true);

  Fifo() :
    on_blocking_abort(true),
    outfile(nullptr),
    raw_fd(-1)
  {}
  Fifo(const char *p_path, bool p_on_blocking_abort) :
    path(p_path),
    on_blocking_abort(p_on_blocking_abort),
    outfile(nullptr),
    raw_fd(-1)
  {}
  ~Fifo();

  static bool writePacket(const std::string &filename, const ZMPacket &packet);
  static bool write(const std::string &filename, uint8_t *data, size_t size);

  bool open();
  bool close();

  bool writePacket(const ZMPacket &packet);
  bool write(uint8_t *data, size_t size, int64_t pts);
};
#endif  // ZM_FIFO_H
