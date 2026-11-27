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

#include <string>

// Named-pipe creation helpers, used by the diagnostic image pipes
// (diagpipe-*, see zm_monitor.cpp / zm_zone.cpp and FifoStream).
//
// The per-monitor media FIFO writer that used to live here was replaced by
// the monitor stream socket (zm_stream_socket.h): a unix domain socket at
// PATH_SOCKS/stream_{monitor_id}.sock carrying both streams with codec
// parameters in a HELLO handshake, supporting multiple consumers.
class Fifo {
 public:
  static void file_create_if_missing(const std::string &path, bool is_fifo, bool delete_fake_fifo = true);
  static void fifo_create_if_missing(const std::string &path, bool delete_fake_fifo = true);
};
#endif  // ZM_FIFO_H
