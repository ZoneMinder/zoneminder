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
#include "zm_rtsp_server_session.h"
#include "zm_rtsp_server_stream_adts_source.h"
#include "zm_rtsp_server_stream_h264_source.h"
#include "net/EventLoop.h"

#include <atomic>
#include <chrono>
#include <condition_variable>
#include <mutex>
#include <thread>
#include <vector>

namespace {

// Records the payloads the packer hands to the RTSP layer
class CapturingH264Source : public H264_ZoneMinderStreamSource {
 public:
  explicit CapturingH264Source(std::shared_ptr<xop::RtspServer> &server) :
    H264_ZoneMinderStreamSource(server, 0, xop::channel_0) {}
  ~CapturingH264Source() override { StopAndJoin(); }

  void PushFrame(const uint8_t *data, size_t size, int64_t, bool last) override {
    std::lock_guard<std::mutex> lock(record_mutex);
    pushed.emplace_back(data, data + size);
    if (last) {
      done = true;
      record_cv.notify_all();
    }
  }

  bool Wait() {
    std::unique_lock<std::mutex> lock(record_mutex);
    return record_cv.wait_for(lock, std::chrono::seconds(5), [this] { return done; });
  }

  std::mutex record_mutex;
  std::condition_variable record_cv;
  bool done = false;
  std::vector<std::vector<uint8_t>> pushed;
};

// Lets a test hold the mutex the write thread waits with
class MutexExposedSource : public ZoneMinderStreamSource {
 public:
  explicit MutexExposedSource(std::shared_ptr<xop::RtspServer> &server) :
    ZoneMinderStreamSource(server, 0, xop::channel_0) {}
  ~MutexExposedSource() override { StopAndJoin(); }

  std::mutex &WaitMutex() { return mutex_; }

 protected:
  void PushFrame(const uint8_t *, size_t, int64_t, bool) override {}
};

}  // namespace

TEST_CASE("RTSP NALs do not carry trailing zero bytes", "[rtsp_nal_split]") {
  std::shared_ptr<xop::RtspServer> server;
  CapturingH264Source source(server);

  // SPS, then zero padding and a 4-byte start code (00 00 00 00 00 01), then
  // an IDR slice that ends in padding
  std::vector<uint8_t> au = {
    0, 0, 0, 1, 0x67, 0x42, 0x00, 0x1F,
    0, 0, 0,
    0, 0, 0, 1, 0x65, 0x88, 0x84, 0x21,
    0, 0, 0
  };
  source.OnPacket(au.data(), au.size(), 1000);
  REQUIRE(source.Wait());
  source.StopAndJoin();

  REQUIRE(source.pushed.size() == 2);
  CHECK(source.pushed[0] == std::vector<uint8_t>{0x67, 0x42, 0x00, 0x1F});
  CHECK(source.pushed[1] == std::vector<uint8_t>{0x65, 0x88, 0x84, 0x21});
}

TEST_CASE("RTSP timestamps never reach xop as 0", "[rtsp_nal_split]") {
  // a missing pts is left 0 so xop uses the wall clock; a real 0 must not be
  AVRational tb = {1, 90000};
  CHECK(ZoneMinderStreamVideoSource::RtpTimestamp(AV_NOPTS_VALUE, tb) == 0);  // wall clock, not INT64_MIN
  CHECK(ZoneMinderStreamVideoSource::RtpTimestamp(0, tb) != 0);
  CHECK(ZoneMinderStreamVideoSource::RtpTimestamp(1000000, tb) == 90000);
}

TEST_CASE("RTSP audio without a pts gets a running RTP timestamp", "[rtsp_nal_split]") {
  // a missing pts must not collapse every audio packet onto timestamp 0
  AVRational tb = {1, 8000};
  CHECK(ZoneMinderStreamAudioSource::RtpTimestamp(1000000, tb) == 8000);
  int64_t first = ZoneMinderStreamAudioSource::RtpTimestamp(AV_NOPTS_VALUE, tb);
  CHECK(first != 0);
  CHECK(first != AV_NOPTS_VALUE);
  std::this_thread::sleep_for(std::chrono::milliseconds(50));
  int64_t second = ZoneMinderStreamAudioSource::RtpTimestamp(AV_NOPTS_VALUE, tb);
  CHECK(second > first);
}

TEST_CASE("RTSP source Stop waits for the write thread's mutex", "[rtsp_nal_split]") {
  // The write thread holds this mutex between testing stop_ and waiting on the
  // condition variable. A Stop() that does not take it can set stop_ and
  // notify in that gap, and the thread then never wakes.
  std::shared_ptr<xop::RtspServer> server;
  MutexExposedSource source(server);
  std::atomic<bool> stopped{false};
  std::thread stopper;
  {
    std::lock_guard<std::mutex> lock(source.WaitMutex());
    stopper = std::thread([&] {
      source.Stop();
      stopped = true;
    });
    std::this_thread::sleep_for(std::chrono::milliseconds(200));
    CHECK_FALSE(stopped);
  }
  stopper.join();
  CHECK(stopped);
  source.StopAndJoin();
}

TEST_CASE("RTSP session with a taken stream name is refused", "[rtsp_nal_split]") {
  xop::EventLoop loop;
  // let the scheduler thread start before the loop is torn down
  std::this_thread::sleep_for(std::chrono::milliseconds(100));
  std::shared_ptr<xop::RtspServer> server = xop::RtspServer::Create(&loop);
  // Two monitors with an empty RTSPStreamName collide on the same suffix
  xop::MediaSession *first = xop::MediaSession::CreateNew("");
  xop::MediaSession *second = xop::MediaSession::CreateNew("");
  CHECK(RtspAddSession(*server, first));
  CHECK_FALSE(RtspAddSession(*server, second));
}
#endif  // HAVE_RTSP_SERVER
