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

#include "zm_swscale.h"

extern "C" {
#include <libavutil/imgutils.h>
}

#include <memory>
#include <vector>

TEST_CASE("SWScale::Convert refuses an input buffer smaller than the input format needs", "[swscale]") {
  SWScale scale;
  REQUIRE(scale.init());
  const unsigned int w = 33, h = 17;
  const size_t needed = av_image_get_buffer_size(AV_PIX_FMT_RGBA, w, h, 1);
  const size_t given = needed - 4 * w * 4;  // four rows short
  const int out_size = av_image_get_buffer_size(AV_PIX_FMT_GRAY8, w, h, 32);
  std::unique_ptr<uint8_t[]> in(new uint8_t[given]);
  memset(in.get(), 0x40, given);
  std::vector<uint8_t> out(out_size);
  const int rc = scale.Convert(in.get(), given, out.data(), out.size(),
                               AV_PIX_FMT_RGBA, AV_PIX_FMT_GRAY8, w, h, 1, 32);
  REQUIRE(rc != 0);
}
