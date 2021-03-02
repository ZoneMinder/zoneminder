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

#include "zm_fifo.h"

#include "zm_monitor.h"
#include "zm_signal.h"
#include <fcntl.h>
#include <sys/file.h>
#include <sys/stat.h>

#define RAW_BUFFER 512

void Fifo::file_create_if_missing(
    const char * path,
    bool is_fifo,
    bool delete_fake_fifo
    ) {
  static struct stat st;
  if ( stat(path, &st) == 0 ) {
    if ( (!is_fifo) || S_ISFIFO(st.st_mode) || !delete_fake_fifo )
      return;
    Debug(5, "Supposed to be a fifo pipe but isn't, unlinking: %s", path);
    unlink(path);
  }
  int fd;
  if (!is_fifo) {
    Debug(5, "Creating non fifo file as requested: %s", path);
    fd = ::open(path, O_CREAT|O_WRONLY, S_IRUSR|S_IWUSR);
    ::close(fd);
    return;
  }
  Debug(5, "Making fifo file of: %s", path);
  mkfifo(path, S_IRUSR|S_IWUSR);
}

void Fifo::fifo_create_if_missing(
    const char * path,
    bool delete_fake_fifo
    ) {
  file_create_if_missing(path, true, delete_fake_fifo);
}

Fifo::~Fifo() {
  close();
}
bool Fifo::open() {
  fifo_create_if_missing(path.c_str());
  if (!on_blocking_abort) {
    if ( (outfile = fopen(path.c_str(), "wb")) == nullptr ) {
      Error("Can't open %s for writing: %s", path.c_str(), strerror(errno));
      return false;
    }
  } else {
    raw_fd = ::open(path.c_str(), O_WRONLY|O_NONBLOCK|O_CREAT|O_TRUNC,S_IRUSR|S_IWUSR|S_IRGRP|S_IROTH);
    if (raw_fd < 0)
      return false;
    outfile = fdopen(raw_fd, "wb");
    if (outfile == nullptr) {
      ::close(raw_fd);
      raw_fd = -1;
      return false;
    }
  }
  int ret = fcntl(raw_fd, F_SETPIPE_SZ, 1024 * 1024);
  if (ret < 0) {
    Error("set pipe size failed.");
  }
  long pipe_size = (long)fcntl(raw_fd, F_GETPIPE_SZ);
  if (pipe_size == -1) {
    perror("get pipe size failed.");
  }
  Debug(1, "default pipe size: %ld\n", pipe_size);
  return true;
}

bool Fifo::close() {
  fclose(outfile);
  return true;
}

bool Fifo::writePacket(ZMPacket &packet) {
  if (!(outfile or open())) return false;

  Debug(1, "Writing header ZM %u %" PRId64,  packet.packet.size, packet.pts);
  // Going to write a brief header
  if ( fprintf(outfile, "ZM %u %" PRId64 "\n", packet.packet.size, packet.pts) < 0 ) {
    if (errno != EAGAIN)
      Error("Problem during writing: %s", strerror(errno));
    else
      Debug(1, "Problem during writing: %s", strerror(errno));
    return false;
  }

  if (fwrite(packet.packet.data, packet.packet.size, 1, outfile) != 1) {
    Debug(1, "Unable to write to '%s': %s", path.c_str(), strerror(errno));
    return false;
  }
  return true;
}
bool Fifo::writePacket(std::string filename, ZMPacket &packet) {
  bool on_blocking_abort = true;
  FILE *outfile = nullptr;
  int raw_fd = 0;

  if ( !on_blocking_abort ) {
    if ( (outfile = fopen(filename.c_str(), "wb")) == nullptr ) {
      Error("Can't open %s for writing: %s", filename, strerror(errno));
      return false;
    }
  } else {
    raw_fd = ::open(filename.c_str(), O_WRONLY|O_NONBLOCK|O_CREAT|O_TRUNC,S_IRUSR|S_IWUSR|S_IRGRP|S_IROTH);
    if (raw_fd < 0)
      return false;
    outfile = fdopen(raw_fd, "wb");
    if (outfile == nullptr) {
      ::close(raw_fd);
      return false;
    }
  }

  if (fwrite(packet.packet.data, packet.packet.size, 1, outfile) != 1) {
    Debug(1, "Unable to write to '%s': %s", filename.c_str(), strerror(errno));
    fclose(outfile);
    return false;
  }

  fclose(outfile);
  return true;
}

bool Fifo::write(uint8_t *data, size_t bytes, int64_t pts) {
  if (!(outfile or open())) return false;
  // Going to write a brief header
  Debug(1, "Writing header ZM %lu %" PRId64,  bytes, pts);
  if ( fprintf(outfile, "ZM %lu %" PRId64 "\n", bytes, pts) < 0 ) {
    if (errno != EAGAIN)
      Error("Problem during writing: %s", strerror(errno));
    else
      Debug(1, "Problem during writing: %s", strerror(errno));
    return false;
  }
  if (fwrite(data, bytes, 1, outfile) != 1) {
    Debug(1, "Unable to write to '%s': %s", path.c_str(), strerror(errno));
    return false;
  }
  return true;
}

bool Fifo::write(std::string filename, uint8_t *data, size_t bytes) {
  bool on_blocking_abort = true;
  FILE *outfile = nullptr;
  int raw_fd = 0;

  if ( !on_blocking_abort ) {
    if ( (outfile = fopen(filename.c_str(), "wb")) == nullptr ) {
      Error("Can't open %s for writing: %s", filename, strerror(errno));
      return false;
    }
  } else {
    raw_fd = ::open(filename.c_str(), O_WRONLY|O_NONBLOCK|O_CREAT|O_TRUNC,S_IRUSR|S_IWUSR|S_IRGRP|S_IROTH);
    if (raw_fd < 0)
      return false;
    outfile = fdopen(raw_fd, "wb");
    if (outfile == nullptr) {
      ::close(raw_fd);
      return false;
    }
  }

  if (fwrite(data, bytes, 1, outfile) != 1) {
    Debug(1, "Unable to write to '%s': %s", filename.c_str(), strerror(errno));
    fclose(outfile);
    return false;
  }

  fclose(outfile);
  return true;
}
