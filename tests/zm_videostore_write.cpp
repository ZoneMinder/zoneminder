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

#include "zm_monitor.h"
#include "zm_packet.h"
#include "zm_videostore.h"

#include <cstdlib>
#include <filesystem>
#include <memory>
#include <vector>

// VideoStore passthrough/transcode writing, checked by reading the file back.

namespace {

void EnsureConfig() {
  if (!config.font_file_location) config.font_file_location = "";
  if (!config.event_close_mode) config.event_close_mode = "idle";
}

class PassthroughMonitor : public Monitor {
 public:
  PassthroughMonitor() : Monitor() {
    videowriter = PASSTHROUGH;
    wallclock_timestamps = false;
  }
};

struct TempDir {
  std::filesystem::path path;
  TempDir() {
    std::string tmpl = (std::filesystem::temp_directory_path() / "zm_vs_XXXXXX").string();
    REQUIRE(mkdtemp(tmpl.data()) != nullptr);
    path = tmpl;
  }
  ~TempDir() {
    std::error_code ec;
    std::filesystem::remove_all(path, ec);
  }
};

struct InputFormat {
  AVFormatContext *ctx = nullptr;
  InputFormat() { ctx = avformat_alloc_context(); }
  ~InputFormat() { avformat_free_context(ctx); }
  AVStream *NewVideo(AVRational tb) {
    AVStream *s = avformat_new_stream(ctx, nullptr);
    s->codecpar->codec_type = AVMEDIA_TYPE_VIDEO;
    s->codecpar->codec_id = AV_CODEC_ID_MJPEG;
    s->codecpar->width = 64;
    s->codecpar->height = 48;
    s->codecpar->format = AV_PIX_FMT_YUVJ420P;
    s->time_base = tb;
    return s;
  }
  AVStream *NewMuLaw() {
    AVStream *s = avformat_new_stream(ctx, nullptr);
    s->codecpar->codec_type = AVMEDIA_TYPE_AUDIO;
    s->codecpar->codec_id = AV_CODEC_ID_PCM_MULAW;
    s->codecpar->sample_rate = 8000;
    s->codecpar->bit_rate = 64000;
    s->codecpar->format = AV_SAMPLE_FMT_S16;
#if LIBAVUTIL_VERSION_CHECK(57, 28, 100, 28, 0)
    av_channel_layout_default(&s->codecpar->ch_layout, 1);
#else
    s->codecpar->channels = 1;
    s->codecpar->channel_layout = AV_CH_LAYOUT_MONO;
#endif
    s->time_base = {1, 8000};
    return s;
  }
};

std::shared_ptr<ZMPacket> MakePacket(AVStream *stream, AVMediaType type, int64_t pts, int64_t dts,
                                     int64_t duration, bool key = true, int size = 100) {
  auto zp = std::make_shared<ZMPacket>();
  zp->codec_type = type;
  zp->stream = stream;
  zp->keyframe = key;
  av_new_packet(zp->packet.get(), size);
  memset(zp->packet->data, 0, size);
  zp->packet->pts = pts;
  zp->packet->dts = dts;
  zp->packet->duration = duration;
  zp->packet->stream_index = stream->index;
  if (key) zp->packet->flags |= AV_PKT_FLAG_KEY;
  return zp;
}

struct OutPkt {
  int stream;
  int64_t pts, dts, duration;
  AVRational tb;
};

std::vector<OutPkt> ReadBack(const std::string &file) {
  std::vector<OutPkt> out;
  AVFormatContext *ic = nullptr;
  REQUIRE(avformat_open_input(&ic, file.c_str(), nullptr, nullptr) == 0);
  REQUIRE(avformat_find_stream_info(ic, nullptr) >= 0);
  av_packet_ptr pkt{av_packet_alloc()};
  while (av_read_frame(ic, pkt.get()) >= 0) {
    out.push_back({pkt->stream_index, pkt->pts, pkt->dts, pkt->duration, ic->streams[pkt->stream_index]->time_base});
    av_packet_unref(pkt.get());
  }
  avformat_close_input(&ic);
  return out;
}

}  // namespace

TEST_CASE("VideoStore passthrough: dts that steps back is not lost when packets carry no duration",
          "[VideoStore][write]") {
  EnsureConfig();
  TempDir dir;
  const std::string file = (dir.path / "out.mp4").string();
  PassthroughMonitor mon;
  InputFormat in;
  AVStream *vs = in.NewVideo({1, 90000});
  {
    VideoStore store(file.c_str(), "mp4", vs, nullptr, nullptr, &mon);
    REQUIRE(store.open());
    // A camera clock that jumps back once; the packets carry no duration.
    const int64_t ts[] = {0, 3000, 6000, 9000, 7000, 12000, 15000};
    for (int64_t t : ts) store.writePacket(MakePacket(vs, AVMEDIA_TYPE_VIDEO, t, t, 0));
    store.finalize();
  }
  auto pkts = ReadBack(file);
  CHECK(pkts.size() == 7);
}

TEST_CASE("VideoStore passthrough: packets with dts but no pts keep their duration in the output time base",
          "[VideoStore][write]") {
  EnsureConfig();
  TempDir dir;
  const std::string file = (dir.path / "out.mp4").string();
  PassthroughMonitor mon;
  InputFormat in;
  // Millisecond time base: the mp4 muxer picks a larger track time scale.
  AVStream *vs = in.NewVideo({1, 1000});
  {
    VideoStore store(file.c_str(), "mp4", vs, nullptr, nullptr, &mon);
    REQUIRE(store.open());
    for (int i = 0; i < 6; i++)
      store.writePacket(MakePacket(vs, AVMEDIA_TYPE_VIDEO, AV_NOPTS_VALUE, 1000 + i * 100, 100));
    store.finalize();
  }
  auto pkts = ReadBack(file);
  REQUIRE(pkts.size() == 6);
  // 5 frame intervals of 100ms
  double span = (pkts.back().dts - pkts.front().dts) * av_q2d(pkts.front().tb);
  CHECK(span == Catch::Approx(0.5).margin(0.01));
}

TEST_CASE("VideoStore passthrough: packets with pts but no dts keep their time base",
          "[VideoStore][write]") {
  EnsureConfig();
  TempDir dir;
  const std::string file = (dir.path / "out.mp4").string();
  PassthroughMonitor mon;
  InputFormat in;
  AVStream *vs = in.NewVideo({1, 1000});
  {
    VideoStore store(file.c_str(), "mp4", vs, nullptr, nullptr, &mon);
    REQUIRE(store.open());
    for (int i = 0; i < 6; i++)
      store.writePacket(MakePacket(vs, AVMEDIA_TYPE_VIDEO, 1000 + i * 100, AV_NOPTS_VALUE, 100));
    store.finalize();
  }
  auto pkts = ReadBack(file);
  REQUIRE(pkts.size() == 6);
  double span = (pkts.back().pts - pkts.front().pts) * av_q2d(pkts.front().tb);
  CHECK(span == Catch::Approx(0.5).margin(0.01));
}

TEST_CASE("VideoStore audio transcode: flushed audio keeps real timestamps",
          "[VideoStore][write]") {
  EnsureConfig();
  TempDir dir;
  const std::string file = (dir.path / "out.mp4").string();
  PassthroughMonitor mon;
  InputFormat in;
  AVStream *vs = in.NewVideo({1, 90000});
  AVStream *as = in.NewMuLaw();
  const int kPackets = 100;  // 160 samples of 8kHz each: 2 seconds
  {
    VideoStore store(file.c_str(), "mp4", vs, nullptr, as, &mon);
    REQUIRE(store.open());
    for (int i = 0; i < kPackets; i++) {
      store.writePacket(MakePacket(vs, AVMEDIA_TYPE_VIDEO, i * 3000, i * 3000, 3000, i == 0));
      auto a = MakePacket(as, AVMEDIA_TYPE_AUDIO, i * 160, i * 160, 160, true, 160);
      store.writePacket(a);
    }
    store.finalize();
  }
  auto pkts = ReadBack(file);
  std::vector<OutPkt> audio;
  for (const auto &p : pkts)
    if (p.stream == 1) audio.push_back(p);
  REQUIRE(audio.size() > 8);
  // 16000 samples went in, so at least that many have to come out.
  CHECK(audio.size() * 1024 >= 16000);
  // Every AAC frame is 1024 samples long, so consecutive dts are 1024 apart
  // right up to the last one, the one written when the encoder is flushed.
  for (size_t i = 1; i < audio.size(); i++) {
    INFO("audio packet " << i << " of " << audio.size());
    CHECK(audio[i].dts - audio[i - 1].dts == 1024);
  }
}

TEST_CASE("VideoStore audio transcode: packets longer than one AAC frame lose no samples",
          "[VideoStore][write]") {
  EnsureConfig();
  TempDir dir;
  const std::string file = (dir.path / "out.mp4").string();
  PassthroughMonitor mon;
  InputFormat in;
  AVStream *vs = in.NewVideo({1, 90000});
  AVStream *as = in.NewMuLaw();
  {
    VideoStore store(file.c_str(), "mp4", vs, nullptr, as, &mon);
    REQUIRE(store.open());
    for (int i = 0; i < 10; i++) {  // 2000 samples each: 2.5 seconds
      store.writePacket(MakePacket(vs, AVMEDIA_TYPE_VIDEO, i * 9000, i * 9000, 9000, i == 0));
      auto a = MakePacket(as, AVMEDIA_TYPE_AUDIO, i * 2000, i * 2000, 2000, true, 2000);
      store.writePacket(a);
    }
    store.finalize();
  }
  size_t audio = 0;
  for (const auto &p : ReadBack(file))
    if (p.stream == 1) audio++;
  CHECK(audio * 1024 >= 20000);
}

// finalize() must not flush the codecs and write the container trailer after a
// write has failed (the muxer state can be inconsistent and av_write_trailer
// can abort), nor when no packet ever reached the muxer (the fragmented-MP4
// muxer aborts on an empty fragment list, which short-lived events on hardware
// encoders hit). A prior failure takes priority over the empty case.
TEST_CASE("videostore_finalize_action", "[videostore]") {
  SECTION("writes the trailer when packets were written and none failed") {
    REQUIRE(videostore_finalize_action(false, 5) == VideoStoreFinalizeAction::kWriteTrailer);
  }
  SECTION("skips on a prior write failure") {
    REQUIRE(videostore_finalize_action(true, 5) == VideoStoreFinalizeAction::kSkipWriteFailed);
  }
  SECTION("skips when no packet ever reached the muxer") {
    REQUIRE(videostore_finalize_action(false, 0) == VideoStoreFinalizeAction::kSkipNoPackets);
  }
  SECTION("a prior failure takes priority over the empty case") {
    REQUIRE(videostore_finalize_action(true, 0) == VideoStoreFinalizeAction::kSkipWriteFailed);
  }
}

// finalize() must drain the encoder before it decides on the trailer. Feed one
// audio packet shorter than a single AAC frame (1024 samples) and no video, so
// the AAC encoder holds every sample and nothing is written during the session
// — packets_written is 0 right up until finalize() calls flush_codecs(). If the
// no-packets decision were made before the flush (the bug), the buffered tail
// would be dropped and the file would come back empty. After the fix the flush
// produces the frame, the trailer is written, and the sample survives.
TEST_CASE("VideoStore transcode: a tail shorter than one encoder frame is flushed, not dropped",
          "[VideoStore][write]") {
  EnsureConfig();
  TempDir dir;
  const std::string file = (dir.path / "out.mp4").string();
  PassthroughMonitor mon;
  InputFormat in;
  AVStream *vs = in.NewVideo({1, 90000});
  AVStream *as = in.NewMuLaw();
  {
    VideoStore store(file.c_str(), "mp4", vs, nullptr, as, &mon);
    REQUIRE(store.open());
    // 500 mu-law samples: fewer than one 1024-sample AAC frame, so the encoder
    // buffers them and emits nothing until the flush in finalize().
    store.writePacket(MakePacket(as, AVMEDIA_TYPE_AUDIO, 0, 0, 500, true, 500));
    store.finalize();
  }
  size_t audio = 0;
  for (const auto &p : ReadBack(file))
    if (p.stream == 1) audio++;
  CHECK(audio >= 1);
}
