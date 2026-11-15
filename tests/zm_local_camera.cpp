/*
 * This file is part of the ZoneMinder Project. See AUTHORS file for Copyright information
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation; either version 2 of the License, or (at your
 * option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program. If not, see <http://www.gnu.org/licenses/>.
 */

#include "zm_catch2.h"

#include "zm_local_camera.h"

#if ZM_HAS_V4L2

#include <cerrno>
#include <sys/mman.h>

TEST_CASE("LocalCamera::UnmapBuffers skips unmapped slots", "[local_camera]") {
  const size_t len = 4096;
  auto *buffers = new LocalCamera::V4L2MappedBuffer[3]();

  buffers[0].start = mmap(nullptr, len, PROT_READ | PROT_WRITE, MAP_SHARED | MAP_ANONYMOUS, -1, 0);
  buffers[0].length = len;
  REQUIRE(buffers[0].start != MAP_FAILED);
  buffers[1].start = MAP_FAILED;
  buffers[1].length = len;
  void *mapped = buffers[0].start;

  LocalCamera::UnmapBuffers(buffers, 3);

  REQUIRE(buffers[0].start == nullptr);
  REQUIRE(buffers[1].start == MAP_FAILED);
  REQUIRE(buffers[2].start == nullptr);
  unsigned char vec;
  REQUIRE(mincore(mapped, len, &vec) == -1);
  REQUIRE(errno == ENOMEM);
  delete[] buffers;
}

TEST_CASE("LocalCamera::UnmapBuffers accepts null array", "[local_camera]") {
  LocalCamera::UnmapBuffers(nullptr, 8);
}

#endif
