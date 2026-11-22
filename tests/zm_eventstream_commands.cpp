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

#include "zm_eventstream.h"

#include <chrono>
#include <string>
#include <vector>

namespace {

// Gives a test access to the protected frame state and a loaded event without a
// database.
class ProbeEventStream : public EventStream {
 public:
  void loadFrames(int count, Microseconds spacing) {
    event_data = new EventData();
    event_data->start_time = SystemTimePoint(Seconds(1000));
    event_data->end_time = event_data->start_time + spacing * count;
    event_data->duration = spacing * count;
    event_data->frame_count = count;
    for (int i = 0; i < count; i++) {
      event_data->frames.emplace_back(i + 1, event_data->start_time + spacing * i, spacing * i,
                                      i ? spacing : Microseconds(0), true);
    }
  }
  // Frames at the given offsets from the start, in an event of the given length.
  void loadFrameTimes(const std::vector<Microseconds> &offsets, Microseconds length) {
    event_data = new EventData();
    event_data->start_time = SystemTimePoint(Seconds(1000));
    event_data->end_time = event_data->start_time + length;
    event_data->duration = length;
    event_data->frame_count = static_cast<int>(offsets.size());
    for (size_t i = 0; i < offsets.size(); i++) {
      event_data->frames.emplace_back(static_cast<unsigned int>(i) + 1, event_data->start_time + offsets[i], offsets[i],
                                      i ? offsets[i] - offsets[i - 1] : Microseconds(0), true);
    }
  }
  void start(SystemTimePoint t) { startAtTime(t); }
  int frameId() const { return curr_frame_id; }
  void rate(int r) { replay_rate = r; }
  SystemTimePoint startTime() const { return event_data->start_time; }
};

}  // namespace

TEST_CASE("MODE_SINGLE loops to the end of the event when playing in reverse", "[EventStreamCmd]") {
  // Reverse playback that steps past frame 1 left curr_frame_id at 1 again, so
  // the stream showed frame 1 for ever.
  CHECK(EventStream::LoopedFrameId(0, 10) == 10);
  CHECK(EventStream::LoopedFrameId(-3, 10) == 10);
  CHECK(EventStream::LoopedFrameId(11, 10) == 1);
  CHECK(EventStream::LoopedFrameId(14, 10) == 1);
}

TEST_CASE("A stream starts on frame 1 unless asked for another valid frame", "[EventStreamCmd]") {
  // frame=0 or frame=abc (strtoull gives 0) left curr_frame_id at 0, which
  // checkEventLoaded reads as "ran off the start" and so paused the stream, or
  // moved to the previous event.
  CHECK(EventStream::InitialFrameId(0, 10) == 1);
  CHECK(EventStream::InitialFrameId(1, 10) == 1);
  CHECK(EventStream::InitialFrameId(7, 10) == 7);
  CHECK(EventStream::InitialFrameId(10, 10) == 10);
  CHECK(EventStream::InitialFrameId(11, 10) == 1);
  CHECK(EventStream::InitialFrameId(-1, 10) == 1);
  CHECK(EventStream::InitialFrameId(5, 0) == 1);
}

TEST_CASE("Frames made up for an event without Frames rows are 1/fps apart", "[EventStreamCmd]") {
  // 100 frames over 10 s is 100 ms a frame. The delta came out as
  // 1e6 * fps / duration = 1 s, so the event got 11 frames.
  CHECK(EventStream::SyntheticFrameDelta(100, Seconds(10)) == Microseconds(100000));
  CHECK(EventStream::SyntheticFrameDelta(300, Seconds(30)) == Microseconds(100000));
  CHECK(EventStream::SyntheticFrameDelta(30, Seconds(1)) == Microseconds(33333));
  CHECK(EventStream::SyntheticFrameDelta(0, Seconds(10)) == Microseconds(0));
  CHECK(EventStream::SyntheticFrameDelta(10, Seconds(0)) == Microseconds(0));
  CHECK(EventStream::SyntheticFrameDelta(10, Seconds(-5)) == Microseconds(0));
}

TEST_CASE("event= with a time outside the event starts on frame 1", "[EventStreamCmd]") {
  ProbeEventStream stream;
  stream.loadFrames(10, Milliseconds(100));

  SECTION("a time before the event") {
    stream.start(SystemTimePoint(Seconds(5)));
    CHECK(stream.frameId() == 1);
  }
  SECTION("a time after the event") {
    stream.start(SystemTimePoint(Seconds(5000)));
    CHECK(stream.frameId() == 1);
  }
  SECTION("no time") {
    stream.start(SystemTimePoint(Seconds(0)));
    CHECK(stream.frameId() == 1);
  }
  SECTION("a time inside the event") {
    stream.start(stream.startTime() + Milliseconds(500));
    CHECK(stream.frameId() > 1);
  }
}

TEST_CASE("event= with a time starts on the last frame at or before it", "[EventStreamCmd]") {
  ProbeEventStream stream;

  SECTION("a time on each frame") {
    stream.loadFrames(10, Milliseconds(100));
    for (int i = 0; i < 10; i++) {
      stream.start(stream.startTime() + Milliseconds(100) * i);
      CHECK(stream.frameId() == i + 1);
    }
    stream.start(stream.startTime() + Milliseconds(450));
    CHECK(stream.frameId() == 5);
  }
  SECTION("a time in a gap longer than the one before it") {
    stream.loadFrameTimes({Milliseconds(0), Milliseconds(100), Milliseconds(150), Milliseconds(300)}, Milliseconds(400));
    stream.start(stream.startTime() + Milliseconds(290));
    CHECK(stream.frameId() == 3);
  }
  SECTION("a time after the last frame") {
    stream.loadFrameTimes({Milliseconds(0), Milliseconds(100), Milliseconds(200)}, Milliseconds(500));
    stream.start(stream.startTime() + Milliseconds(450));
    CHECK(stream.frameId() == 3);
  }
}

TEST_CASE("zms scale= is limited, and a negative value is not a huge one", "[EventStreamCmd]") {
  CHECK(StreamBase::ParseScaleParam("50") == 50);
  CHECK(StreamBase::ParseScaleParam("1600") == 1600);
  CHECK(StreamBase::ParseScaleParam("1700") == 1600);
  CHECK(StreamBase::ParseScaleParam("99999999999") == 1600);
  CHECK(StreamBase::ParseScaleParam("0") == 100);
  CHECK(StreamBase::ParseScaleParam("abc") == 100);
  CHECK(StreamBase::ParseScaleParam("-50") == 100);
  CHECK(StreamBase::ParseScaleParam("-1") == 100);
}

TEST_CASE("zms time= gives the time it names", "[EventStreamCmd]") {
  // A double near 1.7e9 is up to 0.12 us off the decimal it was read from.
  // Truncated to a nanosecond clock, about a quarter of two-decimal times came
  // out just before the frame at that time, so the stream started a frame early.
  const SystemTimePoint start(Seconds(1763600000));
  for (int cs = 0; cs < 100; cs++) {
    const std::string value = std::to_string(1763600000) + (cs < 10 ? ".0" : ".") + std::to_string(cs);
    CAPTURE(value);
    CHECK(StreamBase::ParseTimeParam(value.c_str()) == start + Milliseconds(10 * cs));
  }
}

TEST_CASE("A stream past the last frame of a recording event waits before it checks again", "[EventStreamCmd]") {
  // checkEventLoaded reloads a recording event and puts the stream back on its
  // last frame, which was sent again with no wait: a query and a frame per pass
  // for as long as the viewer stayed on the tail.
  const Microseconds none(0);
  // EventStream::STREAM_PAUSE_WAIT, which is not public
  const Microseconds poll = Milliseconds(250);

  CHECK(EventStream::TailPollDelta(true, 11, 10, none) == poll);
  CHECK(EventStream::TailPollDelta(true, 11, 10, Microseconds(30000)) == poll);

  // Frames still to send, a finished event and reverse play keep their own pacing.
  CHECK(EventStream::TailPollDelta(true, 10, 10, Microseconds(30000)) == Microseconds(30000));
  CHECK(EventStream::TailPollDelta(true, 1, 10, none) == none);
  CHECK(EventStream::TailPollDelta(true, 0, 10, none) == none);
  CHECK(EventStream::TailPollDelta(false, 11, 10, Microseconds(30000)) == Microseconds(30000));
}
