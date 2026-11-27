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
#include "zm_monitor.h"

#include <memory>

extern "C" {
#include <libavutil/opt.h>
}

// Monitor::Decode calls setupConvertContext() for every decoded frame. A stream
// whose pixel format changes mid-stream (MJPEG alternating yuvj422p/yuvj444p) must
// get a converter for the new format, and an unchanged stream must keep its own.
namespace {

class ConvertMonitor : public Monitor {
 public:
  ConvertMonitor() : Monitor() {}
  SwsContext *Ctx() const { return convert_context; }
};

struct FrameDeleter {
  void operator()(AVFrame *f) const { av_frame_free(&f); }
};

std::unique_ptr<AVFrame, FrameDeleter> MakeFrame(AVPixelFormat fmt, int w, int h) {
  std::unique_ptr<AVFrame, FrameDeleter> frame(av_frame_alloc());
  frame->format = fmt;
  frame->width = w;
  frame->height = h;
  REQUIRE(av_frame_get_buffer(frame.get(), 0) == 0);
  return frame;
}

int64_t SrcFormat(SwsContext *ctx) {
  // The option is "src_format" in newer libswscale and "srcformat" before.
  int64_t v = -1;
  if (av_opt_get_int(ctx, "src_format", 0, &v) < 0)
    REQUIRE(av_opt_get_int(ctx, "srcformat", 0, &v) >= 0);
  return v;
}

}  // namespace

TEST_CASE("Monitor::setupConvertContext keeps the converter while the frame format is unchanged", "[Monitor]") {
  if (!config.font_file_location) config.font_file_location = "";
  auto monitor = std::make_shared<ConvertMonitor>();
  Image image(64, 48, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_YUV420P);
  auto frame = MakeFrame(AV_PIX_FMT_YUVJ422P, 64, 48);

  REQUIRE(monitor->setupConvertContext(frame.get(), &image));
  SwsContext *first = monitor->Ctx();
  REQUIRE(first != nullptr);
  REQUIRE(monitor->setupConvertContext(frame.get(), &image));
  REQUIRE(monitor->Ctx() == first);
}

TEST_CASE("Monitor::setupConvertContext follows a pixel format change in the stream", "[Monitor]") {
  if (!config.font_file_location) config.font_file_location = "";
  auto monitor = std::make_shared<ConvertMonitor>();
  Image image(64, 48, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_YUV420P);

  auto f422 = MakeFrame(AV_PIX_FMT_YUVJ422P, 64, 48);
  REQUIRE(monitor->setupConvertContext(f422.get(), &image));
  REQUIRE(SrcFormat(monitor->Ctx()) == AV_PIX_FMT_YUV422P);

  auto f444 = MakeFrame(AV_PIX_FMT_YUVJ444P, 64, 48);
  REQUIRE(monitor->setupConvertContext(f444.get(), &image));
  REQUIRE(SrcFormat(monitor->Ctx()) == AV_PIX_FMT_YUV444P);

  auto f420 = MakeFrame(AV_PIX_FMT_YUVJ420P, 64, 48);
  REQUIRE(monitor->setupConvertContext(f420.get(), &image));
  REQUIRE(SrcFormat(monitor->Ctx()) == AV_PIX_FMT_YUV420P);
}
