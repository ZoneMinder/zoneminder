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

static unsigned int codec_tag_for(const char *format_name, unsigned int tag) {
  AVCodecParameters *par = avcodec_parameters_alloc();
  par->codec_type = AVMEDIA_TYPE_VIDEO;
  par->codec_id = AV_CODEC_ID_H264;
  par->codec_tag = tag;
  const AVOutputFormat *format = av_guess_format(format_name, nullptr, nullptr);
  REQUIRE(format != nullptr);
  zm_clear_incompatible_codec_tag(format, par);
  tag = par->codec_tag;
  avcodec_parameters_free(&par);
  return tag;
}

TEST_CASE("zm_clear_incompatible_codec_tag", "[ffmpeg]") {
  FFMPEGInit();
  // 0x1b is the mpegts stream type for H.264
  REQUIRE(codec_tag_for("mp4", 0x1b) == 0);
  REQUIRE(codec_tag_for("mp4", MKTAG('a', 'v', 'c', '1')) == MKTAG('a', 'v', 'c', '1'));
  REQUIRE(codec_tag_for("mp4", MKTAG('h', 'e', 'v', '1')) == 0);
  REQUIRE(codec_tag_for("mpegts", 0x1b) == 0x1b);
}
