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

#include "zm_ffmpeg.h"

TEST_CASE("every encoder the monitor form offers is selectable", "[ffmpeg]") {
  const char *offered[] = {
    "libx264", "h264_nvenc", "h264_omx", "h264_qsv", "h264_vaapi", "h264_v4l2m2m",
    "libx265", "hevc_nvenc", "hevc_qsv", "hevc_vaapi",
    "libvpx-vp9", "vp9_qsv", "libsvtav1", "libaom-av1", "av1_qsv", "av1_vaapi", "av1_nvenc",
  };
  for (const char *name : offered) {
    CAPTURE(name);
    if (!avcodec_find_encoder_by_name(name)) continue;
    const auto found = get_encoder_data("", name);
    REQUIRE(found.size() == 1);
    REQUIRE(std::string(found.front()->codec_name) == name);
  }
}
