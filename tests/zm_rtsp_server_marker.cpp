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

#if HAVE_RTSP_SERVER
#include "zm_rtsp_server_stream_av1_source.h"
#include "zm_rtsp_server_stream_h264_source.h"

#include <algorithm>
#include <chrono>
#include <condition_variable>
#include <memory>
#include <mutex>
#include <vector>

namespace {

// Records what the packer hands to the RTSP layer instead of sending it
class RecordingH264Source : public H264_ZoneMinderStreamSource {
 public:
  RecordingH264Source(std::shared_ptr<xop::RtspServer> &server) :
    H264_ZoneMinderStreamSource(server, 0, xop::channel_0) {}
  ~RecordingH264Source() override { StopAndJoin(); }

  struct Pushed {
    size_t size;
    bool last;
  };

  void PushFrame(const uint8_t *data, size_t size, int64_t pts, bool last) override {
    std::lock_guard<std::mutex> lock(record_mutex);
    pushed.push_back({size, last});
    if (last)
      record_cv.notify_all();
  }

  bool WaitForLast() {
    std::unique_lock<std::mutex> lock(record_mutex);
    return record_cv.wait_for(lock, std::chrono::seconds(5), [this] {
      for (const Pushed &p : pushed)
        if (p.last) return true;
      return false;
    });
  }

  std::mutex record_mutex;
  std::condition_variable record_cv;
  std::vector<Pushed> pushed;
};

std::vector<uint8_t> AnnexB(std::initializer_list<std::pair<uint8_t, size_t>> nals) {
  std::vector<uint8_t> out;
  for (const auto &nal : nals) {
    out.insert(out.end(), {0, 0, 0, 1, nal.first});
    out.insert(out.end(), nal.second - 1, 0x55);
  }
  return out;
}

}  // namespace

TEST_CASE("RTSP marker bit is set on the last packet of an access unit only", "[rtsp_marker]") {
  std::shared_ptr<xop::RtspServer> server;
  RecordingH264Source source(server);

  // AUD, SPS, PPS, an IDR slice that needs fragmenting, then a small slice
  std::vector<uint8_t> au = AnnexB({{0x09, 2}, {0x67, 20}, {0x68, 6}, {0x65, 4000}, {0x41, 100}});
  source.OnPacket(au.data(), au.size(), 1000);
  REQUIRE(source.WaitForLast());
  source.StopAndJoin();

  REQUIRE(source.pushed.size() > 5);  // the IDR slice went out as several FU-A packets
  for (size_t i = 0; i + 1 < source.pushed.size(); i++)
    CHECK_FALSE(source.pushed[i].last);
  CHECK(source.pushed.back().last);
}

TEST_CASE("RTSP marker bit lands on the last fragment of a trailing large NAL", "[rtsp_marker]") {
  std::shared_ptr<xop::RtspServer> server;
  RecordingH264Source source(server);

  std::vector<uint8_t> au = AnnexB({{0x09, 2}, {0x65, 5000}});
  source.OnPacket(au.data(), au.size(), 1000);
  REQUIRE(source.WaitForLast());
  source.StopAndJoin();

  REQUIRE(source.pushed.size() > 2);
  for (size_t i = 0; i + 1 < source.pushed.size(); i++)
    CHECK_FALSE(source.pushed[i].last);
  CHECK(source.pushed.back().last);
}

TEST_CASE("RTSP extractFrame ignores a start code that ends the data", "[rtsp_marker]") {
  std::shared_ptr<xop::RtspServer> server;
  RecordingH264Source source(server);

  // Heap copies sized exactly, so a read of the byte after the start code is out of bounds
  const std::vector<std::vector<uint8_t>> inputs = {
    {0x11, 0x22, 0x00, 0x00, 0x01},        // short start code last
    {0x11, 0x00, 0x00, 0x00, 0x01},        // long start code last
    {0x00, 0x00, 0x01},                    // nothing but a short start code
    {0x00, 0x00, 0x00, 0x01},              // nothing but a long start code
  };
  for (const auto &input : inputs) {
    std::unique_ptr<unsigned char[]> data(new unsigned char[input.size()]);
    std::copy(input.begin(), input.end(), data.get());
    size_t size = input.size();
    size_t outsize = 99;
    CHECK(source.extractFrame(data.get(), size, outsize) == nullptr);
    CHECK(outsize == 0);
  }

  // A start code followed by a header byte is still found
  std::vector<uint8_t> ok = {0x00, 0x00, 0x00, 0x01, 0x65};
  size_t size = ok.size();
  size_t outsize = 0;
  unsigned char *frame = source.extractFrame(ok.data(), size, outsize);
  REQUIRE(frame != nullptr);
  CHECK(frame == ok.data() + 4);
  CHECK(outsize == 1);
}

namespace {

class RecordingAV1Source : public AV1_ZoneMinderStreamSource {
 public:
  RecordingAV1Source(std::shared_ptr<xop::RtspServer> &server) :
    AV1_ZoneMinderStreamSource(server, 0, xop::channel_0) {}
  ~RecordingAV1Source() override { StopAndJoin(); }

  void PushFrame(const uint8_t *data, size_t size, int64_t pts, bool last) override {
    std::lock_guard<std::mutex> lock(record_mutex);
    pushed.emplace_back(data, data + size);
    if (last)
      record_cv.notify_all();
  }

  bool WaitForPush() {
    std::unique_lock<std::mutex> lock(record_mutex);
    return record_cv.wait_for(lock, std::chrono::seconds(5), [this] { return !pushed.empty(); });
  }

  std::mutex record_mutex;
  std::condition_variable record_cv;
  std::vector<std::vector<uint8_t>> pushed;
};

}  // namespace

TEST_CASE("RTSP AV1 OBUs larger than an RTP packet are passed on whole", "[rtsp_marker]") {
  std::shared_ptr<xop::RtspServer> server;
  RecordingAV1Source source(server);

  // OBU_FRAME with a size field: header, LEB128(2997), 2997 payload bytes
  std::vector<uint8_t> obu = {0x32, 0xB5, 0x17};
  obu.insert(obu.end(), 2997, 0x55);
  source.OnPacket(obu.data(), obu.size(), 1000);
  REQUIRE(source.WaitForPush());
  source.StopAndJoin();

  REQUIRE(source.pushed.size() == 1);
  CHECK(source.pushed[0] == obu);
}
#endif  // HAVE_RTSP_SERVER
