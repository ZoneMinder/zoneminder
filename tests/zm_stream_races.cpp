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

#include "zm_stream.h"

#include <atomic>
#include <thread>

// zms runs two threads over one StreamBase: the command thread
// (checkCommandQueue -> processCommand) writes the playback state, and the
// streaming loop (runStream) reads it every iteration. Nothing synchronises
// them, so before zoneminder/zoneminder#4939 every one of these fields was a
// data race, which is undefined behaviour regardless of what it does on x86.
//
// This exercises those exact members from two threads. It passes trivially in a
// normal build; its value is under ThreadSanitizer:
//
//   cmake -DTSAN=ON -DBUILD_TEST_SUITE=ON .. && cmake --build .
//   ./tests/tests "[races]"
//
// With the fields plain, TSan reports a write/read race on each. With them
// atomic it is clean.

namespace {

class PlaybackStateProbe : public StreamBase {
 public:
  // The writes MonitorStream::processCommand performs on the command thread.
  void applyCommands(int iterations) {
    for (int i = 0; i < iterations; i++) {
      stopped = false;
      paused = true;
      replay_rate = ZM_RATE_BASE * ((i % 5) + 1);
      scale = 100 + (i % 100);
      zoom = 100 + (i % 50);
      x = static_cast<unsigned short>(i % 1000);
      y = static_cast<unsigned short>(i % 800);
      step = (i % 3) - 1;
      send_twice = (i % 2) == 0;
      maxfps = 1.0 + (i % 30);
      frame_type = (i % 2) ? FRAME_ANALYSIS : FRAME_NORMAL;
      paused = false;
    }
  }

  // The reads runStream() performs on the streaming thread. The accumulator
  // exists so none of this is optimised away.
  int64 readPlaybackState(int iterations) {
    int64 sink = 0;
    for (int i = 0; i < iterations; i++) {
      sink += paused ? 1 : 0;
      sink += stopped ? 1 : 0;
      sink += replay_rate;
      sink += scale;
      sink += zoom;
      sink += x;
      sink += y;
      sink += step;
      sink += send_twice ? 1 : 0;
      sink += static_cast<int64>(maxfps);
      sink += (frame_type == FRAME_ANALYSIS) ? 1 : 0;
    }
    return sink;
  }

 protected:
  void processCommand(const CmdMsg *) override {}
  void runStream() override {}
};

}  // namespace

TEST_CASE("StreamBase playback state is shared safely between the command and stream threads", "[races]") {
  // connkey defaults to 0, so ~StreamBase does no socket teardown here.
  PlaybackStateProbe probe;

  constexpr int kIterations = 20000;
  std::atomic<int64> observed{0};

  std::thread command_thread([&probe]() { probe.applyCommands(kIterations); });
  std::thread stream_thread([&probe, &observed]() {
    observed = probe.readPlaybackState(kIterations);
  });

  command_thread.join();
  stream_thread.join();

  // The exact total is timing dependent and deliberately not asserted. What
  // matters is that both threads completed and that, under TSan, the run
  // produced no race report.
  REQUIRE(observed.load() >= 0);
}
