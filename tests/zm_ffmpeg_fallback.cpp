/*
 * This file is part of the ZoneMinder Project. See AUTHORS file for Copyright information
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

#include "zm_catch2.h"

#include "zm_ffmpeg.h"

// open_fallback_decoder() is the "try whatever ffmpeg actually has" path used
// when none of the preferred decoders in dec_codecs are available. The
// hard-coded table is only a preference list; a codec present in the ffmpeg
// build but absent from the table must still open.

static AVCodecParameters *make_video_par(enum AVCodecID id) {
  AVCodecParameters *par = avcodec_parameters_alloc();
  par->codec_type = AVMEDIA_TYPE_VIDEO;
  par->codec_id = id;
  par->width = 1280;
  par->height = 720;
  par->format = AV_PIX_FMT_YUV420P;
  return par;
}

TEST_CASE("open_fallback_decoder: opens a decoder not listed in dec_codecs") {
  // MPEG2VIDEO has a native ffmpeg decoder that is always compiled in, and it is
  // deliberately not in the preferred dec_codecs table, so it only resolves via
  // the fallback. If this build somehow lacks it, skip rather than false-fail.
  if (!avcodec_find_decoder(AV_CODEC_ID_MPEG2VIDEO)) {
    WARN("mpeg2video decoder not present in this ffmpeg build; skipping");
    return;
  }

  AVCodecParameters *par = make_video_par(AV_CODEC_ID_MPEG2VIDEO);
  const AVCodec *codec = nullptr;
  AVCodecContext *ctx = open_fallback_decoder(par, &codec);

  REQUIRE(ctx != nullptr);
  REQUIRE(codec != nullptr);
  REQUIRE(codec->id == AV_CODEC_ID_MPEG2VIDEO);

  avcodec_free_context(&ctx);
  avcodec_parameters_free(&par);
}

TEST_CASE("open_fallback_decoder: codec_out is optional") {
  if (!avcodec_find_decoder(AV_CODEC_ID_MPEG2VIDEO)) {
    WARN("mpeg2video decoder not present in this ffmpeg build; skipping");
    return;
  }

  AVCodecParameters *par = make_video_par(AV_CODEC_ID_MPEG2VIDEO);
  AVCodecContext *ctx = open_fallback_decoder(par);  // default nullptr codec_out

  REQUIRE(ctx != nullptr);

  avcodec_free_context(&ctx);
  avcodec_parameters_free(&par);
}

TEST_CASE("open_fallback_decoder: returns nullptr when no decoder exists") {
  AVCodecParameters *par = make_video_par(AV_CODEC_ID_NONE);
  const AVCodec *codec = reinterpret_cast<const AVCodec *>(0x1);
  AVCodecContext *ctx = open_fallback_decoder(par, &codec);

  REQUIRE(ctx == nullptr);
  // codec_out must be left untouched on failure.
  REQUIRE(codec == reinterpret_cast<const AVCodec *>(0x1));

  avcodec_parameters_free(&par);
}
