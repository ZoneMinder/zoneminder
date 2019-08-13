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

#include <fcntl.h>
#include <sys/file.h>
#include <stdio.h>
#include <stdarg.h>
#include <signal.h>

#include "zm.h"
#include "zm_time.h"
#include "zm_signal.h"
#include "zm_monitor.h"
#include "zm_fifo.h"
#define RAW_BUFFER 512
static bool zm_fifodbg_inited = false;
FILE *zm_fifodbg_log_fd = 0;
char zm_fifodbg_log[PATH_MAX] = "";

static bool zmFifoDbgOpen() {
  if ( zm_fifodbg_log_fd )
    fclose(zm_fifodbg_log_fd);
  zm_fifodbg_log_fd = NULL;
  signal(SIGPIPE, SIG_IGN);
  FifoStream::fifo_create_if_missing(zm_fifodbg_log);
  int fd = open(zm_fifodbg_log, O_WRONLY|O_NONBLOCK|O_TRUNC);
  if ( fd < 0 )
    return false;
  int res = flock(fd, LOCK_EX | LOCK_NB);
  if ( res < 0 ) {
    close(fd);
    return false;
  }
  zm_fifodbg_log_fd = fdopen(fd, "wb");
  if ( zm_fifodbg_log_fd == NULL ) {
    close(fd);
    return false;
  }
  return true;
}

int zmFifoDbgInit(Monitor *monitor) {
  zm_fifodbg_inited = true;
  snprintf(zm_fifodbg_log, sizeof(zm_fifodbg_log), "%s/%d/dbgpipe.log",
      monitor->getStorage()->Path(), monitor->Id());
  zmFifoDbgOpen();
  return 1;
}

void zmFifoDbgOutput(
    int hex,
    const char * const file,
    const int line,
    const int level,
    const char *fstring,
    ...
    ) {
  char dbg_string[8192];
  int str_size = sizeof(dbg_string);

  va_list arg_ptr;
  if ( (!zm_fifodbg_inited) || ( !zm_fifodbg_log_fd && !zmFifoDbgOpen() ) )
    return;

  char *dbg_ptr = dbg_string;
  va_start(arg_ptr, fstring);
  if ( hex ) {
    unsigned char *data = va_arg(arg_ptr, unsigned char *);
    int len = va_arg(arg_ptr, int);
    dbg_ptr += snprintf(dbg_ptr, str_size-(dbg_ptr-dbg_string), "%d:", len);
    for ( int i = 0; i < len; i++ ) {
      dbg_ptr += snprintf(dbg_ptr, str_size-(dbg_ptr-dbg_string), " %02x", data[i]);
    }
  } else {
    dbg_ptr += vsnprintf(dbg_ptr, str_size-(dbg_ptr-dbg_string), fstring, arg_ptr);
  }
  va_end(arg_ptr);
  strncpy(dbg_ptr++, "\n", 2);
  int res = fwrite(dbg_string, dbg_ptr-dbg_string, 1, zm_fifodbg_log_fd);
  if ( res != 1 ) {
    fclose(zm_fifodbg_log_fd);
    zm_fifodbg_log_fd = NULL;
  } else {
    fflush(zm_fifodbg_log_fd);
  }
}

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
      Error("Problem during writing: %s", strerror(errno));
      close(fd);
      return false;
    }
    fflush(stdout);
  }
  close(fd);
  return true;
}

void FifoStream::file_create_if_missing(
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
  if ( !is_fifo ) {
    Debug(5, "Creating non fifo file as requested: %s", path);
    fd = open(path, O_CREAT|O_WRONLY, S_IRUSR|S_IWUSR);
    close(fd);
    return;
  }
  Debug(5, "Making fifo file of: %s", path);
  mkfifo(path, S_IRUSR|S_IWUSR);
}

void FifoStream::fifo_create_if_missing(
    const char * path,
    bool delete_fake_fifo
    ) {
  file_create_if_missing(path, true, delete_fake_fifo);
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
        "--ZoneMinderFrame\r\n"
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
  const char * filename;
  Monitor * monitor = Monitor::Load(monitor_id, false, Monitor::QUERY);

  if ( !strcmp(format, "reference") ) {
    stream_type = MJPEG;
    filename = "diagpipe-r.jpg";
  } else if ( !strcmp(format, "delta") ) {
    filename = "diagpipe-d.jpg";
    stream_type = MJPEG;
  } else {
    stream_type = RAW;
    filename = "dbgpipe.log";
  }

  snprintf(diag_path, sizeof(diag_path), "%s/%d/%s",
      monitor->getStorage()->Path(), monitor->Id(), filename);
  setStreamStart(diag_path);
}

void FifoStream::runStream() {
  if ( stream_type == MJPEG ) {
    fprintf(stdout, "Content-Type: multipart/x-mixed-replace;boundary=ZoneMinderFrame\r\n\r\n");
  } else {
    fprintf(stdout, "Content-Type: text/html\r\n\r\n");
  }

  char lock_file[PATH_MAX];
  snprintf(lock_file, sizeof(lock_file), "%s.rlock", stream_path);
  file_create_if_missing(lock_file, false);

  int fd_lock = open(lock_file, O_RDONLY);
  if ( fd_lock < 0 ) {
    Error("Can't open %s: %s", lock_file, strerror(errno));
    return;
  }
  int res = flock(fd_lock, LOCK_EX | LOCK_NB);
  if ( res < 0 ) {
    Error("Flocking problem on %s: - %s", lock_file, strerror(errno));
    close(fd_lock);
    return;
  }

  while ( !zm_terminate ) {
    gettimeofday(&now, NULL);
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
