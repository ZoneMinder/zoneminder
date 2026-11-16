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

#include "zm_config.h"
#include "zm_image.h"

#include <cstdlib>
#include <unistd.h>

// A File monitor reads its source JPEG into a monitor-sized Image on every
// capture. A file with larger dimensions must get a buffer sized for the file,
// not be decoded into the smaller existing one. refs GHSA-rpp4-xmqm-84ff
TEST_CASE("Image::ReadJpeg reallocates for a larger JPEG", "[image]") {
  // Image::Initialise() dereferences config.font_file_location, which the test
  // harness does not load.
  if (!config.font_file_location) config.font_file_location = "";
  const unsigned int big_w = 256, big_h = 192;
  const unsigned int small_w = 64, small_h = 48;

  Image big(big_w, big_h, ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_RGBA);
  big.Clear();
  char path[] = "/tmp/zm_readjpeg_XXXXXX.jpg";
  int fd = mkstemps(path, 4);
  REQUIRE(fd >= 0);
  close(fd);
  REQUIRE(big.WriteJpeg(path, 90));

  Image small(small_w, small_h, ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_RGBA);
  const unsigned int small_size = small.Size();
  REQUIRE(small.ReadJpeg(path, ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_RGBA));
  unlink(path);

  CHECK(small.Width() == big_w);
  CHECK(small.Height() == big_h);
  // The buffer must hold every decoded row at the image's stride.
  CHECK(small.LineSize() >= big_w * 4);
  CHECK(small.Size() >= small.LineSize() * big_h);
  CHECK(small.Size() > small_size);
}
