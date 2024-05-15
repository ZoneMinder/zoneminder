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

#include "zm_fifo_debug.h"
#include "zm_fifo.h"
#include "zm_monitor.h"
#include "zm_signal.h"
#include <fcntl.h>
#include <sys/file.h>
#include <unistd.h>

#define RAW_BUFFER 512
static bool zm_fifodbg_inited = false;
FILE *zm_fifodbg_log_fd = nullptr;
std::string zm_fifodbg_log;

static bool zmFifoDbgOpen() {
  if ( zm_fifodbg_log_fd )
    fclose(zm_fifodbg_log_fd);
  zm_fifodbg_log_fd = nullptr;
  signal(SIGPIPE, SIG_IGN);
  Fifo::fifo_create_if_missing(zm_fifodbg_log);
  int fd = open(zm_fifodbg_log.c_str(), O_WRONLY | O_NONBLOCK | O_TRUNC);
  if ( fd < 0 )
    return false;
  int res = flock(fd, LOCK_EX | LOCK_NB);
  if ( res < 0 ) {
    close(fd);
    return false;
  }
  zm_fifodbg_log_fd = fdopen(fd, "wb");
  if ( zm_fifodbg_log_fd == nullptr ) {
    close(fd);
    return false;
  }
  return true;
}

int zmFifoDbgInit(Monitor *monitor) {
  zm_fifodbg_inited = true;
  zm_fifodbg_log = stringtf("%s/dbgpipe-%u.log", staticConfig.PATH_SOCKS.c_str(), monitor->Id());
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
      const auto max_len = str_size - (dbg_ptr - dbg_string);
      int rc = snprintf(dbg_ptr, max_len, " %02x", data[i]);
      if (rc < 0 || rc > max_len)
        break;
      dbg_ptr += rc;
    }
  } else {
    dbg_ptr += vsnprintf(dbg_ptr, str_size-(dbg_ptr-dbg_string), fstring, arg_ptr);
  }
  va_end(arg_ptr);
  strncpy(dbg_ptr++, "\n", 2);
  int res = fwrite(dbg_string, dbg_ptr-dbg_string, 1, zm_fifodbg_log_fd);
  if ( res != 1 ) {
    fclose(zm_fifodbg_log_fd);
    zm_fifodbg_log_fd = nullptr;
  } else {
    fflush(zm_fifodbg_log_fd);
  }
}
