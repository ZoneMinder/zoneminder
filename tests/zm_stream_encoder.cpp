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

#include "zm_image.h"
#include "zm_rgb.h"
#include "zm_stream_encoder.h"

#include <cstring>
#include <vector>

namespace {

struct Encoded {
  std::vector<uint8_t> data;
  bool keyframe;
  int64_t pts_us;
};

std::vector<Encoded> encode(StreamEncoder &encoder, const Image &image, SystemTimePoint when,
                            bool force_keyframe = false) {
  std::vector<Encoded> out;
  REQUIRE(encoder.Encode(&image, when, force_keyframe,
      [&out](const AVPacket *pkt, bool keyframe, int64_t pts_us) {
        out.push_back({std::vector<uint8_t>(pkt->data, pkt->data + pkt->size), keyframe, pts_us});
      }));
  return out;
}

// A gradient, so the encoder has something other than a flat field.
void fill(Image &image) {
  uint8_t *buffer = image.WriteBuffer(image.Width(), image.Height(), ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_RGBA);
  for (unsigned int i = 0; i < image.Size(); i++) buffer[i] = static_cast<uint8_t>(i * 7);
}

}  // namespace

TEST_CASE("StreamEncoder::IsSupported") {
  REQUIRE(StreamEncoder::IsSupported("mjpeg"));
  REQUIRE(StreamEncoder::IsSupported("h264_vaapi"));
  REQUIRE(StreamEncoder::IsSupported("hevc_nvenc"));
  // Software encoders are deliberately not offered.
  REQUIRE_FALSE(StreamEncoder::IsSupported("libx264"));
  REQUIRE_FALSE(StreamEncoder::IsSupported("libx265"));
  REQUIRE_FALSE(StreamEncoder::IsSupported(""));
  REQUIRE_FALSE(StreamEncoder::IsSupported("none"));
}

TEST_CASE("StreamEncoder encodes images as MJPEG") {
  StreamEncoder encoder("mjpeg", "", 70);
  REQUIRE(encoder.Open(64, 48, {0, 0}));
  REQUIRE(encoder.Name() == "mjpeg");
  REQUIRE(encoder.Parameters()->codec_id == AV_CODEC_ID_MJPEG);
  REQUIRE(encoder.Parameters()->width == 64);
  REQUIRE(encoder.Parameters()->height == 48);
  REQUIRE(av_cmp_q(encoder.FrameRate(), AVRational{25, 1}) == 0);  // not known: assumed

  Image image(64, 48, ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_RGBA);
  fill(image);
  SystemTimePoint start = std::chrono::system_clock::now();

  std::vector<Encoded> first = encode(encoder, image, start);
  REQUIRE(first.size() == 1);
  REQUIRE(first[0].keyframe);
  REQUIRE(first[0].pts_us == 0);
  // A complete JPEG: SOI ... EOI
  REQUIRE(first[0].data.size() > 4);
  REQUIRE(first[0].data[0] == 0xff);
  REQUIRE(first[0].data[1] == 0xd8);
  REQUIRE(first[0].data[first[0].data.size() - 2] == 0xff);
  REQUIRE(first[0].data[first[0].data.size() - 1] == 0xd9);

  SECTION("pts is microseconds since the first image") {
    std::vector<Encoded> second = encode(encoder, image, start + std::chrono::milliseconds(40));
    REQUIRE(second.size() == 1);
    REQUIRE(second[0].pts_us == 40000);
  }

  SECTION("pts keeps increasing when the clock does not") {
    std::vector<Encoded> second = encode(encoder, image, start);
    std::vector<Encoded> third = encode(encoder, image, start - std::chrono::seconds(1));
    REQUIRE(second.size() == 1);
    REQUIRE(third.size() == 1);
    REQUIRE(second[0].pts_us > first[0].pts_us);
    REQUIRE(third[0].pts_us > second[0].pts_us);
  }

  SECTION("an image of another size is refused") {
    Image other(32, 24, ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_RGBA);
    fill(other);
    bool called = false;
    REQUIRE_FALSE(encoder.Encode(&other, start, false,
        [&called](const AVPacket *, bool, int64_t) { called = true; }));
    REQUIRE_FALSE(called);
  }

  SECTION("Close and Open start a new timeline") {
    encoder.Close();
    REQUIRE_FALSE(encoder.IsOpen());
    REQUIRE(encoder.Parameters() == nullptr);
    REQUIRE(encoder.Open(64, 48, {0, 0}));
    std::vector<Encoded> again = encode(encoder, image, start + std::chrono::seconds(5));
    REQUIRE(again.size() == 1);
    REQUIRE(again[0].pts_us == 0);
  }
}

TEST_CASE("StreamEncoder falls back to MJPEG") {
  SECTION("for an encoder that is not a stream encoder") {
    StreamEncoder encoder("libx264", "", 70);
    REQUIRE(encoder.Open(64, 48, {0, 0}));
    REQUIRE(encoder.Name() == "mjpeg");
  }

  SECTION("for a hardware encoder whose device is not there") {
    StreamEncoder encoder("h264_vaapi", "/dev/dri/no-such-device", 70);
    REQUIRE(encoder.Open(64, 48, {0, 0}));
    REQUIRE(encoder.Name() == "mjpeg");
    REQUIRE(encoder.Parameters()->codec_id == AV_CODEC_ID_MJPEG);
  }
}
