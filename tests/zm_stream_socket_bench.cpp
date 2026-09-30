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

// Stream socket cost benchmark. Hidden from the default run; execute with
//
//   ./tests "[benchmark]"
//
// It pushes a synthetic H.264-like packet stream (one 150 KB keyframe every
// 50 frames, 15 KB deltas otherwise) through a StreamSocket at 250 packets
// a second, with 0, 1 and 8 consumers draining it, and reports:
//
//   - SendMedia() wall time on the producer thread (p50/p99/max), which is
//     what the capture loop pays per packet
//   - CPU per packet spent by the producer, by the socket's listener thread
//     and by an average consumer
//   - how many packets each consumer actually received
//
// Absolute numbers depend on the machine; the shape (producer cost flat as
// consumers are added, listener cost scaling with consumers) is the point.

#include "zm_catch2.h"

#include "zm_stream_socket.h"
#include "zm_stream_socket_protocol.h"

#include <algorithm>
#include <atomic>
#include <chrono>
#include <cstdint>
#include <cstdio>
#include <cstring>
#include <memory>
#include <string>
#include <sys/socket.h>
#include <sys/un.h>
#include <thread>
#include <time.h>
#include <unistd.h>
#include <vector>

using namespace zm::stream_socket;

namespace {

const std::string kSockPathStr =
    "/tmp/zm.stream_socket.bench." + std::to_string(getpid()) + ".sock";

constexpr size_t kPackets = 1000;
constexpr size_t kGop = 50;
constexpr size_t kKeyframeBytes = 150 * 1024;
constexpr size_t kDeltaBytes = 15 * 1024;
// Producer pacing. Ten times a 25 fps camera: fast enough to finish in a few
// seconds, slow enough that consumers keep up and the received column shows
// delivery rather than burst drops (an unpaced producer outruns eight
// consumers' socket buffers and the queue policy drops, as designed).
constexpr std::chrono::microseconds kPacketInterval{4000};

struct AVCodecParametersDeleter {
  void operator()(AVCodecParameters *par) const { avcodec_parameters_free(&par); }
};
using codec_parameters_ptr = std::unique_ptr<AVCodecParameters, AVCodecParametersDeleter>;

double cpu_seconds(clockid_t clock) {
  timespec ts = {};
  clock_gettime(clock, &ts);
  return ts.tv_sec + ts.tv_nsec / 1e9;
}

// CPU seconds consumed by one back-to-back pair of thread clock reads, the
// fixed cost of sampling around each SendMedia call. A few microseconds on a
// VM, which would otherwise dwarf the zero-consumer figure.
double clock_pair_overhead() {
  constexpr int kSamples = 2000;
  double total = 0;
  for (int i = 0; i < kSamples; ++i) {
    double a = cpu_seconds(CLOCK_THREAD_CPUTIME_ID);
    double b = cpu_seconds(CLOCK_THREAD_CPUTIME_ID);
    total += b - a;
  }
  return total / kSamples;
}

av_packet_ptr make_packet(size_t size, uint8_t fill) {
  av_packet_ptr packet{av_packet_alloc()};
  REQUIRE(av_new_packet(packet.get(), size) == 0);
  memset(packet->data, fill, size);
  return packet;
}

// Reads and discards everything the server sends, tracking media sequence
// numbers so the producer can tell when the last packet has arrived.
struct Consumer {
  std::thread thread;
  std::atomic<uint32_t> last_sequence{0};
  std::atomic<uint64_t> media_received{0};
  std::atomic<bool> saw_media{false};
  std::atomic<bool> done{false};
  double cpu = 0;  // thread CPU seconds, valid once done

  void Start() {
    thread = std::thread([this] { Run(); });
  }

  void Run() {
    // No Catch2 assertions here: they are not thread-safe. A failed connect
    // leaves media_received at 0, which the main thread checks.
    int fd = ::socket(AF_UNIX, SOCK_STREAM, 0);
    if (fd < 0) {
      done = true;
      return;
    }
    sockaddr_un addr = {};
    addr.sun_family = AF_UNIX;
    strncpy(addr.sun_path, kSockPathStr.c_str(), sizeof(addr.sun_path) - 1);
    if (::connect(fd, reinterpret_cast<sockaddr *>(&addr), sizeof(addr)) != 0) {
      ::close(fd);
      done = true;
      return;
    }
    timeval tv = {5, 0};
    setsockopt(fd, SOL_SOCKET, SO_RCVTIMEO, &tv, sizeof(tv));

    std::vector<uint8_t> payload(kKeyframeBytes);
    uint8_t header_bytes[kHeaderSize];
    while (true) {
      if (!ReadExact(fd, header_bytes, kHeaderSize))
        break;
      Header header;
      if (!ParseHeader(header_bytes, header))
        break;
      size_t size = header.payload_size();
      if (size > payload.size())
        payload.resize(size);
      if (size > 0 and !ReadExact(fd, payload.data(), size))
        break;
      if (header.type == static_cast<uint8_t>(MessageType::Media)) {
        last_sequence = header.sequence;
        saw_media = true;
        ++media_received;
      } else if (header.type == static_cast<uint8_t>(MessageType::Bye)) {
        break;
      }
    }
    ::close(fd);
    cpu = cpu_seconds(CLOCK_THREAD_CPUTIME_ID);
    done = true;
  }

  static bool ReadExact(int fd, uint8_t *out, size_t len) {
    size_t got = 0;
    while (got < len) {
      ssize_t bytes = ::recv(fd, out + got, len - got, 0);
      if (bytes <= 0)
        return false;
      got += bytes;
    }
    return true;
  }
};

struct Result {
  double p50_us, p99_us, max_us;
  double producer_cpu_us, listener_cpu_us, consumer_cpu_us;
  uint64_t min_received, max_received;
};

Result RunScenario(size_t consumer_count) {
  StreamSocket server(1, kSockPathStr);
  REQUIRE(server.Start());

  codec_parameters_ptr par{avcodec_parameters_alloc()};
  par->codec_type = AVMEDIA_TYPE_VIDEO;
  par->codec_id = AV_CODEC_ID_H264;
  par->width = 1920;
  par->height = 1080;
  server.SetVideoParams(par.get(), {25, 1});

  std::vector<std::unique_ptr<Consumer>> consumers;
  for (size_t i = 0; i < consumer_count; ++i) {
    consumers.push_back(std::make_unique<Consumer>());
    consumers.back()->Start();
  }
  // let the listener accept and register them
  std::this_thread::sleep_for(std::chrono::milliseconds(200));

  av_packet_ptr keyframe = make_packet(kKeyframeBytes, 0x65);
  av_packet_ptr delta = make_packet(kDeltaBytes, 0x41);

  std::vector<double> latency_us;
  latency_us.reserve(kPackets);
  double process_cpu_start = cpu_seconds(CLOCK_PROCESS_CPUTIME_ID);
  double producer_cpu_start = cpu_seconds(CLOCK_THREAD_CPUTIME_ID);

  // CPU is sampled around each SendMedia so the pacing sleeps (and the
  // scheduler wake-ups they cause) do not count against the producer.
  double send_cpu = 0;
  double sample_overhead = clock_pair_overhead();
  auto start = std::chrono::steady_clock::now();
  for (size_t i = 0; i < kPackets; ++i) {
    bool key = (i % kGop) == 0;
    // Signed multiplier: a size_t would give the deadline an unsigned rep, and
    // libc++'s sleep_until underflows to a near-infinite sleep once the
    // deadline is already in the past.
    std::this_thread::sleep_until(start + kPacketInterval * static_cast<int64_t>(i));
    double cpu0 = cpu_seconds(CLOCK_THREAD_CPUTIME_ID);
    auto t0 = std::chrono::steady_clock::now();
    server.SendMedia(key ? keyframe.get() : delta.get(), StreamId::Video, key,
                     static_cast<int64_t>(i) * 40000);
    auto t1 = std::chrono::steady_clock::now();
    send_cpu += std::max(0.0, cpu_seconds(CLOCK_THREAD_CPUTIME_ID) - cpu0 - sample_overhead);
    latency_us.push_back(std::chrono::duration<double, std::micro>(t1 - t0).count());
  }
  // Whole-thread figure, sleeps included, only for the listener subtraction
  double producer_thread_cpu = cpu_seconds(CLOCK_THREAD_CPUTIME_ID) - producer_cpu_start;

  // Wait for every consumer to see the final packet (or give up after 10 s)
  auto deadline = std::chrono::steady_clock::now() + std::chrono::seconds(10);
  for (auto &consumer : consumers) {
    while (std::chrono::steady_clock::now() < deadline
           and !(consumer->saw_media and consumer->last_sequence >= kPackets - 1)) {
      std::this_thread::sleep_for(std::chrono::milliseconds(5));
    }
  }
  double process_cpu = cpu_seconds(CLOCK_PROCESS_CPUTIME_ID) - process_cpu_start;

  server.Stop();  // BYE ends every consumer thread
  double consumer_cpu = 0;
  uint64_t min_received = consumers.empty() ? 0 : UINT64_MAX;
  uint64_t max_received = 0;
  for (auto &consumer : consumers) {
    consumer->thread.join();
    consumer_cpu += consumer->cpu;
    min_received = std::min<uint64_t>(min_received, consumer->media_received);
    max_received = std::max<uint64_t>(max_received, consumer->media_received);
  }

  std::sort(latency_us.begin(), latency_us.end());
  Result r;
  r.p50_us = latency_us[latency_us.size() / 2];
  r.p99_us = latency_us[latency_us.size() * 99 / 100];
  r.max_us = latency_us.back();
  r.producer_cpu_us = send_cpu * 1e6 / kPackets;
  // Whatever the process spent beyond the producer thread and the consumers
  // is the listener thread (plus a sliver of test bookkeeping).
  double listener_cpu = std::max(0.0, process_cpu - producer_thread_cpu - consumer_cpu);
  r.listener_cpu_us = listener_cpu * 1e6 / kPackets;
  r.consumer_cpu_us = consumer_count ? consumer_cpu * 1e6 / kPackets / consumer_count : 0;
  r.min_received = min_received;
  r.max_received = max_received;
  return r;
}

}  // namespace

TEST_CASE("StreamSocket producer and listener cost per packet", "[.benchmark][stream_socket]") {
  std::printf("[benchmark] %zu packets at %lld/s, %zu KB keyframe every %zu, %zu KB deltas\n",
              kPackets, static_cast<long long>(1000000 / kPacketInterval.count()),
              kKeyframeBytes / 1024, kGop, kDeltaBytes / 1024);
  std::printf("[benchmark] %-9s %-28s %-36s %s\n",
              "consumers", "SendMedia p50/p99/max (us)", "CPU/packet producer/listener/consumer (us)",
              "received per consumer");

  for (size_t consumers : {0u, 1u, 8u}) {
    Result r = RunScenario(consumers);
    std::printf("[benchmark] %-9zu %6.1f /%6.1f /%7.1f       %6.1f /%7.1f /%7.1f              %llu..%llu of %zu\n",
                consumers, r.p50_us, r.p99_us, r.max_us,
                r.producer_cpu_us, r.listener_cpu_us, r.consumer_cpu_us,
                static_cast<unsigned long long>(r.min_received),
                static_cast<unsigned long long>(r.max_received), kPackets);
    std::fflush(stdout);

    // Sanity: the producer never waits on consumers for long, and nobody
    // starves. Generous bounds so a loaded CI box does not fail the run.
    REQUIRE(r.p99_us < 5000.0);
    if (consumers > 0)
      REQUIRE(r.min_received > 0);
  }
}
