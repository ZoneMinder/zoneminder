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
#include "zm_rtsp_server_stream_h264_source.h"

#include <chrono>
#include <condition_variable>
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
#endif  // HAVE_RTSP_SERVER
