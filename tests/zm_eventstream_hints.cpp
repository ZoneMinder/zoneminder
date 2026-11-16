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
#include "zm_image.h"
#include "zm_mpeg.h"

#include <cstring>
#include <vector>

// Gapless playback over zms: the event view sends zms the next/prev event in the
// filter the user is viewing (CMD_SET_EVENT), because zms's own query only knows
// the same monitor and skipped other monitors' events (refs #4186).

namespace {

using Choice = EventStream::NeighbourChoice;

// The command list and message layout are protected in StreamBase.
class StreamAccess : public EventStream {
 public:
  static constexpr int kSetEvent = CMD_SET_EVENT;
  static constexpr size_t kMsgDataSize = sizeof(CmdMsg::msg_data);
};

// msg_data as ajax/stream.php packs it: command, direction, 64-bit target and
// 48-bit source, both big-endian.
std::vector<char> SetEventMessage(bool prev, uint64_t event_id, uint64_t source_event_id) {
  std::vector<char> msg(16);
  msg[0] = StreamAccess::kSetEvent;
  msg[1] = prev ? 1 : 0;
  for (int i = 0; i < 8; i++) msg[2 + i] = static_cast<char>(event_id >> (8 * (7 - i)));
  for (int i = 0; i < 6; i++) msg[10 + i] = static_cast<char>(source_event_id >> (8 * (5 - i)));
  return msg;
}

EventStream::EventHint Hint(uint64_t event_id, uint64_t source_event_id) {
  EventStream::EventHint hint;
  hint.have = true;
  hint.event_id = event_id;
  hint.source_event_id = source_event_id;
  return hint;
}

}  // namespace

TEST_CASE("CMD_SET_EVENT keeps its place in the command list", "[EventStream]") {
  // web/includes/config.php.in defines CMD_SET_EVENT as 22.
  CHECK(StreamAccess::kSetEvent == 22);
  CHECK(StreamAccess::kMsgDataSize == 16);
}

TEST_CASE("DecodeSetEvent reads the payload stream.php packs", "[EventStream]") {
  std::vector<char> msg = SetEventMessage(false, 634646, 634645);
  EventStream::SetEventCommand command = EventStream::DecodeSetEvent(msg.data());
  CHECK_FALSE(command.prev);
  CHECK(command.event_id == 634646);
  CHECK(command.source_event_id == 634645);

  msg = SetEventMessage(true, 0x0102030405060708ULL, 0x0000A1B2C3D4E5F6ULL);
  command = EventStream::DecodeSetEvent(msg.data());
  CHECK(command.prev);
  CHECK(command.event_id == 0x0102030405060708ULL);
  CHECK(command.source_event_id == 0x0000A1B2C3D4E5F6ULL);

  msg = SetEventMessage(false, 0, 7);
  CHECK(EventStream::DecodeSetEvent(msg.data()).event_id == 0);
}

TEST_CASE("A hint applies only to the event it was computed for", "[EventStream]") {
  CHECK(EventStream::HintIsFor(Hint(11, 10), 10));
  CHECK_FALSE(EventStream::HintIsFor(Hint(11, 10), 11));
  EventStream::EventHint none;
  none.source_event_id = 10;
  CHECK_FALSE(EventStream::HintIsFor(none, 10));
  // Only 48 bits of the source are sent.
  CHECK(EventStream::HintIsFor(Hint(5, 1), (1ULL << 48) | 1));
}

TEST_CASE("ChooseNeighbour follows the UI once it sends hints", "[EventStream]") {
  const Microseconds none(0);
  const Microseconds soon(std::chrono::duration_cast<Microseconds>(Seconds(1)));
  const Microseconds late(std::chrono::duration_cast<Microseconds>(EventStream::kHintWait));

  SECTION("a hint for the current event is used, 0 is the filter boundary") {
    CHECK(EventStream::ChooseNeighbour(Hint(11, 10), 10, true, none) == Choice::kHint);
    CHECK(EventStream::ChooseNeighbour(Hint(0, 10), 10, true, none) == Choice::kBoundary);
  }

  SECTION("without a UI the same-monitor query is used, as before") {
    CHECK(EventStream::ChooseNeighbour(EventStream::EventHint(), 10, false, none) == Choice::kSameMonitor);
    CHECK(EventStream::ChooseNeighbour(Hint(11, 9), 10, false, late) == Choice::kSameMonitor);
  }

  SECTION("an event that ends before its hint arrives waits for it") {
    // Id order 10 (M1), 11 (M2), 12 (M1), 13 (M2): at the end of 11 the hint
    // still names 10's neighbour. Falling back to the same-monitor query here
    // went 11 -> 13 and skipped 12.
    CHECK(EventStream::ChooseNeighbour(Hint(11, 10), 11, true, none) == Choice::kWait);
    CHECK(EventStream::ChooseNeighbour(Hint(11, 10), 11, true, soon) == Choice::kWait);
    CHECK(EventStream::ChooseNeighbour(Hint(12, 11), 11, true, soon) == Choice::kHint);
  }

  SECTION("a UI that never answers stops playback rather than leaving the filter") {
    CHECK(EventStream::ChooseNeighbour(Hint(11, 10), 11, true, late) == Choice::kTimedOut);
  }
}

TEST_CASE("A late hint restarts a stream stopped at the end of the filter", "[EventStream]") {
  // zms stopped at the end of event 10 going forward: the UI said there was no
  // next event, or said nothing in time. The UI keeps asking, and an event
  // recorded since, or the hint that was just too late, should still play.
  EventStream::SetEventCommand next{false, 14, 10};
  EventStream::SetEventCommand prev{true, 9, 10};
  EventStream::SetEventCommand boundary{false, 0, 10};

  CHECK(EventStream::HintResumes(next, true, false));
  CHECK_FALSE(EventStream::HintResumes(boundary, true, false));
  // Only the direction we stopped in.
  CHECK_FALSE(EventStream::HintResumes(prev, true, false));
  CHECK(EventStream::HintResumes(prev, true, true));
  // Not a stream that is playing, or that the user paused.
  CHECK_FALSE(EventStream::HintResumes(next, false, false));
}

TEST_CASE("VideoStream::PackImage converts to the encoder's size, format and stride", "[VideoStream]") {
  SwsContext *context = nullptr;
  std::vector<uint8_t> out;

  SECTION("RGBA is converted to RGB24 without row padding") {
    // A text frame in the monitor's RGB32 after the stream opened on RGB24 event
    // frames: copied as is it was a third larger than the encoder's buffer.
    Image image(10, 2, ZM_COLOUR_RGB32, ZM_SUBPIX_ORDER_RGBA);
    REQUIRE(image.LineSize() >= 40);
    for (unsigned int y = 0; y < 2; y++) {
      uint8_t *row = image.Buffer() + y * image.LineSize();
      for (unsigned int x = 0; x < 10; x++) {
        row[4 * x] = 10 + y;
        row[4 * x + 1] = 20 + y;
        row[4 * x + 2] = 30 + y;
        row[4 * x + 3] = 255;
      }
    }
    REQUIRE(VideoStream::PackImage(image, AV_PIX_FMT_RGB24, 10, 2, out, &context));
    REQUIRE(out.size() == 10 * 2 * 3);
    for (unsigned int y = 0; y < 2; y++) {
      for (unsigned int x = 0; x < 10; x++) {
        const uint8_t *pixel = &out[(y * 10 + x) * 3];
        CHECK(pixel[0] == 10 + y);
        CHECK(pixel[1] == 20 + y);
        CHECK(pixel[2] == 30 + y);
      }
    }
  }

  SECTION("RGB24 rows on a 32-byte stride come out packed") {
    Image image(10, 2, ZM_COLOUR_RGB24, ZM_SUBPIX_ORDER_RGB);
    REQUIRE(image.LineSize() == 32);
    std::memset(image.Buffer(), 0xEE, image.LineSize() * 2);
    for (unsigned int y = 0; y < 2; y++) std::memset(image.Buffer() + y * image.LineSize(), 40 + y, 30);
    REQUIRE(VideoStream::PackImage(image, AV_PIX_FMT_RGB24, 10, 2, out, &context));
    REQUIRE(out.size() == 60);
    CHECK(out[0] == 40);
    CHECK(out[29] == 40);
    CHECK(out[30] == 41);
    CHECK(out[59] == 41);
  }

  SECTION("an image wrapping a packed buffer is read at its own stride") {
    // Image(width, height, colours, order, buffer) does not pad its rows.
    // Mapped on a 32-byte stride, row 1 would start at byte 32 and the last row
    // would be read past the end of this 60-byte buffer.
    std::vector<uint8_t> packed(60);
    std::memset(packed.data(), 40, 30);
    std::memset(packed.data() + 30, 41, 30);
    Image image(10, 2, ZM_COLOUR_RGB24, ZM_SUBPIX_ORDER_RGB, packed.data());
    REQUIRE(image.LineSize() == 30);
    REQUIRE(VideoStream::PackImage(image, AV_PIX_FMT_RGB24, 10, 2, out, &context));
    REQUIRE(out.size() == 60);
    CHECK(out[29] == 40);
    CHECK(out[30] == 41);
    CHECK(out[59] == 41);
  }

  SECTION("a packed YUV420P frame whose width is a multiple of 32 is read") {
    // 32x16 packed: the luma stride is 32 either way, but the 32-byte layout
    // pads the chroma rows to 32 and needs 1024 bytes where this frame has 768.
    std::vector<uint8_t> packed(32 * 16 + 2 * 16 * 8);
    std::memset(packed.data(), 50, 32 * 16);
    std::memset(packed.data() + 32 * 16, 60, 16 * 8);
    std::memset(packed.data() + 32 * 16 + 16 * 8, 70, 16 * 8);
    AVFrame *frame = av_frame_alloc();
    REQUIRE(frame);
    frame->width = 32;
    frame->height = 16;
    frame->format = AV_PIX_FMT_YUV420P;
    REQUIRE(av_image_fill_arrays(frame->data, frame->linesize, packed.data(), AV_PIX_FMT_YUV420P, 32, 16, 1)
            == static_cast<int>(packed.size()));
    Image image;
    image.AssignDirect(frame);
    REQUIRE(image.LineSize() == 32);
    REQUIRE(image.Size() == packed.size());

    REQUIRE(VideoStream::PackImage(image, AV_PIX_FMT_YUV420P, 32, 16, out, &context));
    REQUIRE(out.size() == packed.size());
    CHECK(out[0] == 50);
    CHECK(out[32 * 16] == 60);
    CHECK(out[32 * 16 + 16 * 8] == 70);
    av_frame_free(&frame);
  }

  SECTION("a frame of another shape is fitted inside on black, not stretched") {
    // A 2:1 frame in a stream whose encoder opened square: 16x8 in the middle
    // rows, black above and below.
    Image image(32, 16, ZM_COLOUR_RGB24, ZM_SUBPIX_ORDER_RGB);
    for (unsigned int y = 0; y < 16; y++) std::memset(image.Buffer() + y * image.LineSize(), 200, 96);
    REQUIRE(VideoStream::PackImage(image, AV_PIX_FMT_RGB24, 16, 16, out, &context));
    REQUIRE(out.size() == 16 * 16 * 3);
    auto row = [&](int y) { return &out[y * 16 * 3]; };
    for (int y : {0, 3, 12, 15}) for (int i = 0; i < 48; i++) CHECK(row(y)[i] == 0);
    for (int y : {4, 8, 11}) for (int i = 0; i < 48; i++) CHECK(row(y)[i] == 200);
  }

  SECTION("letterboxed YUV420P is black, not green") {
    Image image(32, 16, ZM_COLOUR_RGB24, ZM_SUBPIX_ORDER_RGB);
    std::memset(image.Buffer(), 255, image.LineSize() * 16);
    REQUIRE(VideoStream::PackImage(image, AV_PIX_FMT_YUV420P, 16, 16, out, &context));
    REQUIRE(out.size() == 16 * 16 * 3 / 2);
    CHECK(out[0] == 16);                     // luma, top bar
    CHECK(out[8 * 16] > 200);                // luma, picture
    CHECK(out[16 * 16] == 128);              // U, top bar
    CHECK(out[16 * 16 + 8 * 8] == 128);      // V, top bar
  }

  SECTION("a frame from a larger monitor is scaled down to the encoder's size") {
    Image image(32, 16, ZM_COLOUR_RGB24, ZM_SUBPIX_ORDER_RGB);
    for (unsigned int y = 0; y < 16; y++) std::memset(image.Buffer() + y * image.LineSize(), 90, 96);
    REQUIRE(VideoStream::PackImage(image, AV_PIX_FMT_RGB24, 8, 4, out, &context));
    REQUIRE(out.size() == 8 * 4 * 3);
    for (uint8_t value : out) CHECK(value == 90);
  }

  sws_freeContext(context);
}
