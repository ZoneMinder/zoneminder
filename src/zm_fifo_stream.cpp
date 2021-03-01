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

#include "zm_fifo_stream.h"
#include "zm_fifo.h"
#include "zm_monitor.h"
#include "zm_signal.h"
#include <fcntl.h>
#include <sys/file.h>
#include <sys/stat.h>

#define RAW_BUFFER 512
bool FifoStream::sendRAWFrames() {
  static unsigned char buffer[RAW_BUFFER];
  int fd = open(stream_path, O_RDONLY);
  if ( fd < 0 ) {
    Error("Can't open %s: %s", stream_path, strerror(errno));
    return false;
  }
  while ( (bytes_read = read(fd, buffer, RAW_BUFFER)) ) {
    if ( bytes_read == 0 )
      continue;
    if ( bytes_read < 0 ) {
      Error("Problem during reading: %s", strerror(errno));
      close(fd);
      return false;
    }
    if ( fwrite(buffer, bytes_read, 1, stdout) != 1 ) {
      if ( !zm_terminate ) 
        Error("Problem during writing: %s", strerror(errno));
      close(fd);
      return false;
    }
    fflush(stdout);
  }
  close(fd);
  return true;
}

bool FifoStream::sendMJEGFrames() {
  static unsigned char buffer[ZM_MAX_IMAGE_SIZE];
  int fd = open(stream_path, O_RDONLY);
  if ( fd < 0 ) {
    Error("Can't open %s: %s", stream_path, strerror(errno));
    return false;
  }
  total_read = 0;
  while (
      (bytes_read = read(fd, buffer+total_read, ZM_MAX_IMAGE_SIZE-total_read))
      ) {
    if ( bytes_read < 0 ) {
      Error("Problem during reading: %s", strerror(errno));
      close(fd);
      return false;
    }
    total_read += bytes_read;
  }
  close(fd);

  if ( (total_read == 0) || (frame_count%frame_mod != 0) )
    return true;

  if ( fprintf(stdout,
        "--" BOUNDARY "\r\n"
      "Content-Type: image/jpeg\r\n"
      "Content-Length: %d\r\n\r\n",
      total_read) < 0 ) {
    Error("Problem during writing: %s", strerror(errno));
    return false;
  }

  if ( fwrite(buffer, total_read, 1, stdout) != 1 ) {
    Error("Problem during reading: %s", strerror(errno));
    return false;
  }
  fprintf(stdout, "\r\n\r\n");
  fflush(stdout);
  last_frame_sent = TV_2_FLOAT(now);
  frame_count++;
  return true;
}

void FifoStream::setStreamStart(const char * path) {
  stream_path = strdup(path);
}

void FifoStream::setStreamStart(int monitor_id, const char * format) {
  char diag_path[PATH_MAX];
  std::shared_ptr<Monitor> monitor = Monitor::Load(monitor_id, false, Monitor::QUERY);

  if ( !strcmp(format, "reference") ) {
    snprintf(diag_path, sizeof(diag_path), "%s/diagpipe-r-%d.jpg",
        staticConfig.PATH_SOCKS.c_str(), monitor->Id());
    stream_type = MJPEG;
  } else if ( !strcmp(format, "delta") ) {
    snprintf(diag_path, sizeof(diag_path), "%s/diagpipe-d-%d.jpg",
        staticConfig.PATH_SOCKS.c_str(), monitor->Id());
    stream_type = MJPEG;
  } else {
    if ( strcmp(format, "raw") ) {
      Warning("Unknown or unspecified format.  Defaulting to raw");
    }
    snprintf(diag_path, sizeof(diag_path), "%s/dbgpipe-%d.log",
        staticConfig.PATH_SOCKS.c_str(), monitor->Id());
    stream_type = RAW;
  }

  setStreamStart(diag_path);
}

void FifoStream::runStream() {
  if ( stream_type == MJPEG ) {
    fprintf(stdout, "Content-Type: multipart/x-mixed-replace;boundary=" BOUNDARY "\r\n\r\n");
  } else {
    fprintf(stdout, "Content-Type: text/html\r\n\r\n");
  }

  /* only 1 person can read from a fifo at a time, so use a lock */
  char lock_file[PATH_MAX];
  snprintf(lock_file, sizeof(lock_file), "%s.rlock", stream_path);
  Fifo::file_create_if_missing(lock_file, false);
  Debug(1, "Locking %s", lock_file);

  int fd_lock = open(lock_file, O_RDONLY);
  if ( fd_lock < 0 ) {
    Error("Can't open %s: %s", lock_file, strerror(errno));
    return;
  }
  int res = flock(fd_lock, LOCK_EX | LOCK_NB);
  while ( (res < 0 and errno == EAGAIN) and (! zm_terminate) ) {
    Warning("Flocking problem on %s: - %s", lock_file, strerror(errno));
    sleep(1);
    res = flock(fd_lock, LOCK_EX | LOCK_NB);
  }
  if ( res < 0 ) {
    Error("Flocking problem on %d != %d %s: - %s", EAGAIN, res, lock_file, strerror(errno));
    close(fd_lock);
    return;
  }

  while ( !zm_terminate ) {
    gettimeofday(&now, nullptr);
    checkCommandQueue();
    if ( stream_type == MJPEG ) {
      if ( !sendMJEGFrames() )
        zm_terminate = true;
    } else {
      if ( !sendRAWFrames() )
        zm_terminate = true;
    }
  }
  close(fd_lock);
}
