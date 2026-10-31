/*
 * This file is part of the ZoneMinder Project. See AUTHORS file for contributors.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

#include "zm_catch2.h"

#include "zm_websocket.h"

#include <atomic>
#include <thread>
#include <vector>

extern "C" {
#include <libavcodec/avcodec.h>
}

// zm::websocket::VideoStreamInfo is the Monitor's owned snapshot of the capture
// video stream. The capture thread publishes it from the camera (Update) and
// clears it before camera->Close() frees the AVStream (Invalidate); the
// websocket server thread reads an owned copy (Get) and so never touches the
// camera or a live AVStream. See refs #2875 - the point is that nothing the
// reader keeps points back into the camera.

namespace {

// Builds a video AVCodecParameters with the given codec and extradata bytes, or
// returns nullptr on allocation failure. Uses no Catch2 assertion macros so it
// is safe to call from a worker thread (the macros are not thread-safe).
// Caller frees with avcodec_parameters_free.
AVCodecParameters *makeCodecpar(AVCodecID codec_id, int width, int height,
                                const std::string &extradata) {
  AVCodecParameters *par = avcodec_parameters_alloc();
  if (!par) {
    return nullptr;
  }
  par->codec_type = AVMEDIA_TYPE_VIDEO;
  par->codec_id = codec_id;
  par->width = width;
  par->height = height;
  if (!extradata.empty()) {
    par->extradata = reinterpret_cast<uint8_t *>(av_malloc(extradata.size() + AV_INPUT_BUFFER_PADDING_SIZE));
    if (!par->extradata) {
      avcodec_parameters_free(&par);
      return nullptr;
    }
    memcpy(par->extradata, extradata.data(), extradata.size());
    memset(par->extradata + extradata.size(), 0, AV_INPUT_BUFFER_PADDING_SIZE);
    par->extradata_size = static_cast<int>(extradata.size());
  }
  return par;
}

}  // namespace

TEST_CASE("websocket video stream info starts invalid", "[websocket]") {
  zm::websocket::VideoStreamInfo info;
  REQUIRE_FALSE(info.Valid());

  zm::websocket::VideoStreamInfo::Snapshot snap;
  REQUIRE_FALSE(info.Get(AV_CODEC_ID_H264, &snap));
}

TEST_CASE("websocket video stream info publishes a matching codec", "[websocket]") {
  zm::websocket::VideoStreamInfo info;
  const std::string extradata = "\x00\x00\x00\x01sps";  // arbitrary bytes
  AVCodecParameters *par = makeCodecpar(AV_CODEC_ID_H264, 1920, 1080, extradata);
  REQUIRE(par != nullptr);

  info.Update(par, 640, 480);
  avcodec_parameters_free(&par);  // snapshot must survive the source being freed

  REQUIRE(info.Valid());

  zm::websocket::VideoStreamInfo::Snapshot snap;
  REQUIRE(info.Get(AV_CODEC_ID_H264, &snap));
  REQUIRE(snap.codec_id == AV_CODEC_ID_H264);
  REQUIRE(snap.width == 1920);
  REQUIRE(snap.height == 1080);
  REQUIRE(snap.extradata == extradata);

  // A request for a different codec does not match.
  zm::websocket::VideoStreamInfo::Snapshot other;
  REQUIRE_FALSE(info.Get(AV_CODEC_ID_HEVC, &other));
}

TEST_CASE("websocket video stream info falls back to monitor dimensions", "[websocket]") {
  zm::websocket::VideoStreamInfo info;
  AVCodecParameters *par = makeCodecpar(AV_CODEC_ID_H264, 0, 0, "");
  REQUIRE(par != nullptr);
  info.Update(par, 640, 480);
  avcodec_parameters_free(&par);

  zm::websocket::VideoStreamInfo::Snapshot snap;
  REQUIRE(info.Get(AV_CODEC_ID_H264, &snap));
  REQUIRE(snap.width == 640);
  REQUIRE(snap.height == 480);
}

TEST_CASE("websocket video stream info ignores a camera with no encoded stream", "[websocket]") {
  // V4L2/MJPEG/VNC cameras hand ZM decoded images and have no encoded video
  // stream; publishing a null codecpar must leave the snapshot invalid so the
  // websocket offers no encoded video for them.
  zm::websocket::VideoStreamInfo info;
  info.Update(nullptr, 640, 480);
  REQUIRE_FALSE(info.Valid());

  zm::websocket::VideoStreamInfo::Snapshot snap;
  REQUIRE_FALSE(info.Get(AV_CODEC_ID_H264, &snap));
}

TEST_CASE("websocket video stream info invalidates before the camera closes", "[websocket]") {
  zm::websocket::VideoStreamInfo info;
  AVCodecParameters *par = makeCodecpar(AV_CODEC_ID_H264, 1920, 1080, "sps");
  REQUIRE(par != nullptr);
  info.Update(par, 640, 480);
  avcodec_parameters_free(&par);
  REQUIRE(info.Valid());

  info.Invalidate();
  REQUIRE_FALSE(info.Valid());

  zm::websocket::VideoStreamInfo::Snapshot snap;
  REQUIRE_FALSE(info.Get(AV_CODEC_ID_H264, &snap));

  // Re-publishing after a reconnect works again.
  par = makeCodecpar(AV_CODEC_ID_HEVC, 1280, 720, "vps");
  REQUIRE(par != nullptr);
  info.Update(par, 640, 480);
  avcodec_parameters_free(&par);
  REQUIRE(info.Get(AV_CODEC_ID_HEVC, &snap));
  REQUIRE(snap.codec_id == AV_CODEC_ID_HEVC);
}

// The whole point of the snapshot: the capture thread publishing/invalidating
// while the websocket thread reads must be free of data races. Passes trivially
// in a normal build; its value is under ThreadSanitizer:
//
//   cmake -DTSAN=ON -DBUILD_TEST_SUITE=ON .. && cmake --build .
//   ./tests/tests "[races]"
TEST_CASE("websocket video stream info is safe across threads", "[websocket][races]") {
  zm::websocket::VideoStreamInfo info;
  std::atomic<bool> stop{false};

  std::thread writer([&]() {
    int i = 0;
    while (!stop.load(std::memory_order_relaxed)) {
      AVCodecParameters *par = makeCodecpar(
          (i % 2) ? AV_CODEC_ID_H264 : AV_CODEC_ID_HEVC,
          1920, 1080, std::string(64 + (i % 64), 'x'));
      if (par) {
        info.Update(par, 640, 480);
        avcodec_parameters_free(&par);
      }
      info.Invalidate();
      ++i;
    }
  });

  std::atomic<uint64_t> matched{0};
  std::thread reader([&]() {
    zm::websocket::VideoStreamInfo::Snapshot snap;
    while (!stop.load(std::memory_order_relaxed)) {
      if (info.Get(AV_CODEC_ID_H264, &snap)) {
        // Touch the owned copy; it must be self-consistent and not dangle.
        volatile size_t n = snap.extradata.size();
        (void) n;
        matched.fetch_add(1, std::memory_order_relaxed);
      }
    }
  });

  std::this_thread::sleep_for(std::chrono::milliseconds(200));
  stop.store(true, std::memory_order_relaxed);
  writer.join();
  reader.join();
  SUCCEED("no data race or crash under concurrent publish/read");
}
