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

TEST_CASE("websocket video stream info tells a reconnect from a codec change", "[websocket]") {
  using Availability = zm::websocket::VideoStreamInfo::Availability;
  zm::websocket::VideoStreamInfo info;
  REQUIRE(info.Check(AV_CODEC_ID_H264) == Availability::kNoStream);

  AVCodecParameters *par = makeCodecpar(AV_CODEC_ID_H264, 1280, 720, "");
  REQUIRE(par != nullptr);
  info.Update(par, 0, 0);
  avcodec_parameters_free(&par);
  REQUIRE(info.Check(AV_CODEC_ID_H264) == Availability::kAvailable);
  REQUIRE(info.Check(AV_CODEC_ID_HEVC) == Availability::kOtherCodec);

  // While the camera reconnects nothing is published.
  info.Invalidate();
  REQUIRE(info.Check(AV_CODEC_ID_H264) == Availability::kNoStream);

  // It came back producing HEVC: an h264 client can no longer be fed.
  par = makeCodecpar(AV_CODEC_ID_HEVC, 1280, 720, "");
  REQUIRE(par != nullptr);
  info.Update(par, 0, 0);
  avcodec_parameters_free(&par);
  REQUIRE(info.Check(AV_CODEC_ID_H264) == Availability::kOtherCodec);
  REQUIRE(info.Check(AV_CODEC_ID_HEVC) == Availability::kAvailable);
}

namespace {

std::string bytes(std::initializer_list<int> list) {
  std::string out;
  for (int b : list) out.push_back(static_cast<char>(b));
  return out;
}

const std::string kStartCode = bytes({0, 0, 0, 1});

}  // namespace

// An MP4 source gives H.264/H.265 extradata as an avcC/hvcC record and its
// packets length-prefixed. Prepending that record to a keyframe made neither
// Annex-B nor a valid access unit, so passthrough clients got undecodable data.
TEST_CASE("websocket passthrough converts avcC to Annex-B", "[websocket]") {
  const std::string sps = bytes({0x67, 0x42, 0xc0, 0x1e});
  const std::string pps = bytes({0x68, 0xce, 0x3c, 0x80});
  // version 1, profile/compat/level, 0xff = 4-byte lengths, 1 SPS, then 1 PPS
  const std::string avcc = bytes({1, 0x42, 0xc0, 0x1e, 0xff, 0xe1, 0, 4}) + sps + bytes({1, 0, 4}) + pps;

  std::string extradata;
  int nal_length_size = -1;
  REQUIRE(zm::websocket::ExtradataToAnnexB(AV_CODEC_ID_H264, avcc, &extradata, &nal_length_size));
  REQUIRE(nal_length_size == 4);
  REQUIRE(extradata == kStartCode + sps + kStartCode + pps);

  // Two NAL units of 3 and 2 bytes, each with a 4-byte length.
  const std::string packet = bytes({0, 0, 0, 3, 0x65, 0x88, 0x84, 0, 0, 0, 2, 0x06, 0x05});
  std::string annexb;
  REQUIRE(zm::websocket::LengthPrefixedToAnnexB(reinterpret_cast<const uint8_t *>(packet.data()), packet.size(),
                                                nal_length_size, &annexb));
  REQUIRE(annexb == kStartCode + bytes({0x65, 0x88, 0x84}) + kStartCode + bytes({0x06, 0x05}));

  // A length running past the packet is refused.
  const std::string truncated = bytes({0, 0, 0, 9, 0x65});
  REQUIRE_FALSE(zm::websocket::LengthPrefixedToAnnexB(reinterpret_cast<const uint8_t *>(truncated.data()),
                                                      truncated.size(), 4, &annexb));
  // So is a record cut short.
  REQUIRE_FALSE(zm::websocket::ExtradataToAnnexB(AV_CODEC_ID_H264, avcc.substr(0, 10), &extradata, &nal_length_size));
}

TEST_CASE("websocket passthrough converts hvcC to Annex-B", "[websocket]") {
  const std::string vps = bytes({0x40, 0x01, 0x0c});
  const std::string sps = bytes({0x42, 0x01, 0x01});
  // configurationVersion 1, then the rest of the 22-byte header.
  std::string hvcc = bytes({1});
  hvcc += std::string(21, '\0');
  hvcc[21] = static_cast<char>(0xfc | 3);  // lengthSizeMinusOne = 3
  hvcc += bytes({2});                       // two arrays
  hvcc += bytes({0x20, 0, 1, 0, 3}) + vps;  // VPS array, one NAL unit
  hvcc += bytes({0x21, 0, 1, 0, 3}) + sps;  // SPS array, one NAL unit

  std::string extradata;
  int nal_length_size = -1;
  REQUIRE(zm::websocket::ExtradataToAnnexB(AV_CODEC_ID_HEVC, hvcc, &extradata, &nal_length_size));
  REQUIRE(nal_length_size == 4);
  REQUIRE(extradata == kStartCode + vps + kStartCode + sps);
}

TEST_CASE("websocket passthrough leaves Annex-B and AV1 sources alone", "[websocket]") {
  std::string extradata;
  int nal_length_size = -1;
  // An RTSP source's extradata is already Annex-B, and so are its packets.
  const std::string annexb = kStartCode + bytes({0x67, 0x42}) + kStartCode + bytes({0x68, 0xce});
  REQUIRE(zm::websocket::ExtradataToAnnexB(AV_CODEC_ID_H264, annexb, &extradata, &nal_length_size));
  REQUIRE(nal_length_size == 0);
  REQUIRE(extradata == annexb);

  // av1C: a 4-byte header, then the configuration OBUs, which are what a
  // decoder needs before the first temporal unit.
  const std::string obus = bytes({0x0a, 0x0b, 0x00, 0x00});
  REQUIRE(zm::websocket::ExtradataToAnnexB(AV_CODEC_ID_AV1, bytes({0x81, 0x00, 0x0c, 0x00}) + obus,
                                           &extradata, &nal_length_size));
  REQUIRE(nal_length_size == 0);
  REQUIRE(extradata == obus);

  REQUIRE(zm::websocket::ExtradataToAnnexB(AV_CODEC_ID_H264, "", &extradata, &nal_length_size));
  REQUIRE(extradata.empty());
  REQUIRE(nal_length_size == 0);
}

TEST_CASE("websocket passthrough is refused while the monitor has Privacy zones", "[websocket]") {
  // Passthrough sends the camera's own encoded bytes, which the decoder's
  // privacy mask never touched.
  using Availability = zm::websocket::VideoStreamInfo::Availability;
  zm::websocket::VideoStreamInfo info;
  AVCodecParameters *par = makeCodecpar(AV_CODEC_ID_H264, 1280, 720, "");
  REQUIRE(par != nullptr);
  info.Update(par, 0, 0);
  avcodec_parameters_free(&par);

  info.SetPrivacyMasked(true);
  zm::websocket::VideoStreamInfo::Snapshot snapshot;
  REQUIRE(info.Check(AV_CODEC_ID_H264) == Availability::kPrivacyMasked);
  REQUIRE(info.Check(AV_CODEC_ID_HEVC) == Availability::kPrivacyMasked);
  REQUIRE_FALSE(info.Get(AV_CODEC_ID_H264, &snapshot));

  // A camera reconnect republishes the stream; the zones still apply.
  info.Invalidate();
  par = makeCodecpar(AV_CODEC_ID_H264, 1280, 720, "");
  REQUIRE(par != nullptr);
  info.Update(par, 0, 0);
  avcodec_parameters_free(&par);
  REQUIRE(info.Check(AV_CODEC_ID_H264) == Availability::kPrivacyMasked);

  // The zones were removed.
  info.SetPrivacyMasked(false);
  REQUIRE(info.Check(AV_CODEC_ID_H264) == Availability::kAvailable);
  REQUIRE(info.Get(AV_CODEC_ID_H264, &snapshot));
}
