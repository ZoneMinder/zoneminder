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

#include "zm_fifo.h"

#include "zm_logger.h"
#include <fcntl.h>
#include <sys/stat.h>
#include <unistd.h>

void Fifo::file_create_if_missing(const std::string &path, bool is_fifo, bool delete_fake_fifo) {
  struct stat st = {};

  if (stat(path.c_str(), &st) == 0) {
    if ((!is_fifo) || S_ISFIFO(st.st_mode) || !delete_fake_fifo)
      return;
    Debug(5, "Supposed to be a fifo pipe but isn't, unlinking: %s", path.c_str());
    unlink(path.c_str());
  }
  if (!is_fifo) {
    Debug(5, "Creating non fifo file as requested: %s", path.c_str());
    int fd = ::open(path.c_str(), O_CREAT | O_WRONLY, S_IRUSR | S_IWUSR);
    if (fd >= 0) ::close(fd);
    return;
  }
  Debug(5, "Making fifo file of: %s", path.c_str());
  mkfifo(path.c_str(), S_IRUSR | S_IWUSR);
}

void Fifo::fifo_create_if_missing(const std::string &path, bool delete_fake_fifo) {
  file_create_if_missing(path, true, delete_fake_fifo);
}
