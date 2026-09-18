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

#include "zm_mp4_sidx.h"

#include <cstdio>
#include <cstring>
#include <fcntl.h>
#include <filesystem>
#include <algorithm>
#include <fstream>
#include <string>
#include <unistd.h>
#include <vector>

extern "C" {
#include <libavformat/avformat.h>
}

// The fixtures are the reference tool's own output (tools/mp4sidx in the
// kiosk repo, mp4.md): a short fragmented recording, written the way
// VideoStore writes one, already carrying the index this code produces. Each
// test blanks the index back to the `free` box open() reserves and asks
// zm_mp4 to fill it again, so "correct" means byte-for-byte what a second,
// independent implementation arrived at.
namespace {

constexpr int64_t kFixtureReserve = 4096;   // small, to keep the fixtures small

std::filesystem::path fixture(const std::string &name) {
  return std::filesystem::path(ZM_SOURCE_DIR) / "tests" / "data" / "mp4" / name;
}

std::vector<uint8_t> read_file(const std::filesystem::path &path) {
  std::ifstream in(path, std::ios::binary);
  REQUIRE(in.good());
  return std::vector<uint8_t>((std::istreambuf_iterator<char>(in)),
                              std::istreambuf_iterator<char>());
}

void write_file(const std::filesystem::path &path, const std::vector<uint8_t> &bytes) {
  std::ofstream out(path, std::ios::binary | std::ios::trunc);
  REQUIRE(out.good());
  out.write(reinterpret_cast<const char *>(bytes.data()),
            static_cast<std::streamsize>(bytes.size()));
  out.close();
  REQUIRE(std::filesystem::file_size(path) == bytes.size());
}

// Where two files first differ, or npos when they do not. REQUIRE must never
// be handed the vectors themselves: Catch2 renders both operands, and 70 KB
// of rendered bytes takes its text wrapper down with it.
constexpr size_t kSame = static_cast<size_t>(-1);

size_t first_difference(const std::vector<uint8_t> &a, const std::vector<uint8_t> &b) {
  const size_t shared = std::min(a.size(), b.size());
  for (size_t i = 0; i < shared; i++) if (a[i] != b[i]) return i;
  return a.size() == b.size() ? kSame : shared;
}

uint32_t be32(const uint8_t *p) {
  return (uint32_t(p[0]) << 24) | (uint32_t(p[1]) << 16) | (uint32_t(p[2]) << 8) | p[3];
}

// A box walker of the test's own, so a bug in the code under test cannot hide
// behind the same bug in the check.
struct TopBox {
  std::string type;
  size_t offset;
  size_t size;
};

std::vector<TopBox> top_level(const std::vector<uint8_t> &file) {
  std::vector<TopBox> boxes;
  size_t pos = 0;
  while (pos + 8 <= file.size()) {
    size_t size = be32(&file[pos]);
    const std::string type(reinterpret_cast<const char *>(&file[pos + 4]), 4);
    if (size == 0) size = file.size() - pos;
    REQUIRE(size >= 8);
    REQUIRE(pos + size <= file.size());
    boxes.push_back({type, pos, size});
    pos += size;
  }
  return boxes;
}

size_t offset_of(const std::vector<TopBox> &boxes, const std::string &type) {
  for (const TopBox &box : boxes) if (box.type == type) return box.offset;
  FAIL("no " << type << " box in the fixture");
  return 0;
}

// The fixture with its region blanked back to one `free` box: the file as it
// stands the moment finalize() is about to index it.
struct Subject {
  std::filesystem::path path;
  std::vector<uint8_t> expected;   // the fixture, index and all
  int64_t region_offset = 0;
  int64_t region_size = 0;

  explicit Subject(const std::string &name) {
    expected = read_file(fixture(name));
    const std::vector<TopBox> boxes = top_level(expected);
    const size_t moov = offset_of(boxes, "moov");
    size_t moov_end = 0;
    for (const TopBox &box : boxes) if (box.offset == moov) moov_end = box.offset + box.size;
    region_offset = static_cast<int64_t>(moov_end);
    region_size = static_cast<int64_t>(offset_of(boxes, "moof") - moov_end);

    path = std::filesystem::temp_directory_path() / ("zm-sidx-" + name);
    std::vector<uint8_t> blank = expected;
    std::fill(blank.begin() + region_offset,
              blank.begin() + region_offset + region_size, uint8_t(0));
    blank[region_offset + 0] = uint8_t(region_size >> 24);
    blank[region_offset + 1] = uint8_t(region_size >> 16);
    blank[region_offset + 2] = uint8_t(region_size >> 8);
    blank[region_offset + 3] = uint8_t(region_size);
    memcpy(&blank[region_offset + 4], "free", 4);
    write_file(path, blank);
  }

  ~Subject() {
    std::error_code ignored;
    std::filesystem::remove(path, ignored);
  }

  int open_read() const {
    int fd = open(path.c_str(), O_RDONLY);
    REQUIRE(fd >= 0);
    return fd;
  }
};

}  // namespace

TEST_CASE("Mp4SidxReadsTheVideoTrack") {
  Subject subject("sidx-abs.mp4");
  const int fd = subject.open_read();
  zm_mp4::VideoTrack track;
  REQUIRE(zm_mp4::read_video_track(fd, std::filesystem::file_size(subject.path), &track));
  // The fixture is 160x120 at 10 fps with audio, so the video track is 1 and
  // libavformat gives it a 10240 timescale.
  REQUIRE(track.id == 1);
  REQUIRE(track.timescale == 10240);
  close(fd);
}

TEST_CASE("Mp4SidxScanCoversEveryByteOfTheMedia") {
  Subject subject("sidx-abs.mp4");
  const int64_t size = static_cast<int64_t>(std::filesystem::file_size(subject.path));
  const int fd = subject.open_read();

  zm_mp4::VideoTrack track;
  REQUIRE(zm_mp4::read_video_track(fd, size, &track));

  const int64_t first_moof = subject.region_offset + subject.region_size;
  const int64_t end = zm_mp4::media_end(fd, size);
  REQUIRE(end < size);                       // the fixture has an mfra

  std::vector<zm_mp4::Fragment> fragments;
  REQUIRE(zm_mp4::scan_fragments(fd, first_moof, end, track, &fragments));
  REQUIRE(fragments.size() == 3);
  REQUIRE(fragments.front().offset == first_moof);

  int64_t covered = 0;
  for (const zm_mp4::Fragment &fragment : fragments) covered += fragment.size;
  // An index that does not reach the mfra exactly is not a complete one, and
  // a player gains nothing from it.
  REQUIRE(covered == end - first_moof);
  close(fd);
}

TEST_CASE("Mp4SidxWritesTheSameIndexAsTheReferenceTool") {
  // Both fixtures: absolute tfhd offsets (what 1.38.4 records) and
  // moof-relative ones (what master's movflags produce).
  for (const char *name : {"sidx-abs.mp4", "sidx-moof.mp4"}) {
    INFO("fixture " << name);
    Subject subject(name);
    REQUIRE(subject.region_size == kFixtureReserve);
    // Really blanked: the difference is inside the region and nowhere else.
    const size_t blanked_at = first_difference(read_file(subject.path), subject.expected);
    REQUIRE(blanked_at >= static_cast<size_t>(subject.region_offset));
    REQUIRE(blanked_at < static_cast<size_t>(subject.region_offset + subject.region_size));

    REQUIRE(zm_mp4::write_leading_sidx(subject.path.string(),
                                       subject.region_offset, subject.region_size));
    REQUIRE(first_difference(read_file(subject.path), subject.expected) == kSame);
  }
}

TEST_CASE("Mp4SidxIndexesAFileWithNoMfra") {
  // A recording whose writer died leaves no trailer; the index must then run
  // to the end of the file instead.
  Subject subject("sidx-nomfra.mp4");
  const int64_t size = static_cast<int64_t>(std::filesystem::file_size(subject.path));
  const int fd = subject.open_read();
  REQUIRE(zm_mp4::media_end(fd, size) == size);
  close(fd);

  REQUIRE(zm_mp4::write_leading_sidx(subject.path.string(),
                                     subject.region_offset, subject.region_size));
  REQUIRE(first_difference(read_file(subject.path), subject.expected) == kSame);
}

TEST_CASE("Mp4SidxMergesWhenTheRegionIsTight") {
  std::vector<zm_mp4::Fragment> fragments;
  for (int i = 0; i < 8; i++) {
    zm_mp4::Fragment fragment;
    fragment.offset = 1000 + i * 100;
    fragment.size = 100;
    fragment.tfdt = i * 90000;
    fragment.trun_duration = 90000;
    fragments.push_back(fragment);
  }
  zm_mp4::VideoTrack track;
  track.id = 1;
  track.timescale = 90000;

  SECTION("room for every fragment") {
    const std::vector<uint8_t> region =
        zm_mp4::build_sidx_region(track, fragments, zm_mp4::kSidxReserve);
    REQUIRE(region.size() == size_t(zm_mp4::kSidxReserve));
    // free padding first, then the sidx: the index has to END where the
    // fragments begin, or a player will not treat it as complete.
    REQUIRE(memcmp(region.data() + 4, "free", 4) == 0);
    const size_t sidx_at = zm_mp4::kSidxReserve - (40 + 12 * 8);
    REQUIRE(memcmp(region.data() + sidx_at + 4, "sidx", 4) == 0);
    REQUIRE(be32(region.data() + sidx_at + 28) == 0);   // first_offset, high half
    REQUIRE(be32(region.data() + sidx_at + 32) == 0);   // first_offset, low half
    REQUIRE(be32(region.data() + sidx_at + 36) == 8);   // reserved + count
  }

  SECTION("room for three") {
    const int64_t reserve = 40 + 12 * 3 + 8;
    REQUIRE(zm_mp4::max_references(reserve) == 3);
    const std::vector<uint8_t> region = zm_mp4::build_sidx_region(track, fragments, reserve);
    REQUIRE(region.size() == size_t(reserve));
    const uint8_t *sidx = region.data() + 8;
    REQUIRE(be32(sidx + 36) == 3);                       // reserved + count
    // Eight fragments, three references: groups of three, three, two.
    REQUIRE(be32(sidx + 40) == 300);                     // referenced_size
    REQUIRE(be32(sidx + 44) == 3 * 90000);               // subsegment_duration
    REQUIRE(be32(sidx + 40 + 24) == 200);                // the short last group
  }

  SECTION("no room at all") {
    REQUIRE(zm_mp4::max_references(40) == 0);
    REQUIRE(zm_mp4::build_sidx_region(track, fragments, 40).empty());
  }
}

TEST_CASE("Mp4SidxLeavesTheFileAloneWhenItCannotIndex") {
  Subject subject("sidx-abs.mp4");
  const std::vector<uint8_t> before = read_file(subject.path);

  // A region size that does not reach the first fragment: there is no moof
  // where one is claimed to be, so nothing may be written.
  REQUIRE_FALSE(zm_mp4::write_leading_sidx(subject.path.string(),
                                           subject.region_offset,
                                           subject.region_size - 16));
  REQUIRE(first_difference(read_file(subject.path), before) == kSame);

  REQUIRE_FALSE(zm_mp4::write_leading_sidx(subject.path.string(), -1, subject.region_size));
  REQUIRE(first_difference(read_file(subject.path), before) == kSame);
}

TEST_CASE("Mp4SidxIndexesWhatTheMuxerWritesAfterAReservedRegion") {
  // The sequence VideoStore follows, against the real muxer: write the
  // header, put the `free` region on the muxer's own AVIOContext before any
  // packet, remux a recording through it, write the trailer, then fill the
  // region. What this proves is the part unit tests of the parser cannot:
  // that the muxer's own tfhd and tfra offsets account for the reserved
  // bytes, because it takes every offset from avio_tell.
  const std::filesystem::path source = fixture("sidx-abs.mp4");
  const std::filesystem::path out =
      std::filesystem::temp_directory_path() / "zm-sidx-remux.mp4";
  std::error_code ignored;
  std::filesystem::remove(out, ignored);

  AVFormatContext *in = nullptr;
  REQUIRE(avformat_open_input(&in, source.c_str(), nullptr, nullptr) == 0);
  REQUIRE(avformat_find_stream_info(in, nullptr) >= 0);
  int video_in = -1;
  for (unsigned i = 0; i < in->nb_streams; i++) {
    if (in->streams[i]->codecpar->codec_type == AVMEDIA_TYPE_VIDEO) video_in = int(i);
  }
  REQUIRE(video_in >= 0);

  AVFormatContext *oc = nullptr;
  REQUIRE(avformat_alloc_output_context2(&oc, nullptr, "mp4", out.c_str()) >= 0);
  AVStream *video_out = avformat_new_stream(oc, nullptr);
  REQUIRE(video_out != nullptr);
  REQUIRE(avcodec_parameters_copy(video_out->codecpar, in->streams[video_in]->codecpar) >= 0);
  video_out->codecpar->codec_tag = 0;
  REQUIRE(avio_open(&oc->pb, out.c_str(), AVIO_FLAG_WRITE) >= 0);

  AVDictionary *opts = nullptr;
  av_dict_set(&opts, "movflags", "frag_keyframe+empty_moov+default_base_moof", 0);
  REQUIRE(avformat_write_header(oc, &opts) >= 0);
  av_dict_free(&opts);

  const int64_t region_offset = avio_tell(oc->pb);
  std::vector<uint8_t> region(zm_mp4::kSidxReserve, 0);
  region[0] = uint8_t(zm_mp4::kSidxReserve >> 24);
  region[1] = uint8_t(zm_mp4::kSidxReserve >> 16);
  region[2] = uint8_t(zm_mp4::kSidxReserve >> 8);
  region[3] = uint8_t(zm_mp4::kSidxReserve);
  memcpy(region.data() + 4, "free", 4);
  avio_write(oc->pb, region.data(), int(region.size()));
  avio_flush(oc->pb);
  REQUIRE(avio_tell(oc->pb) == region_offset + zm_mp4::kSidxReserve);

  int written = 0;
  AVPacket *packet = av_packet_alloc();
  REQUIRE(packet != nullptr);
  while (av_read_frame(in, packet) >= 0) {
    if (packet->stream_index == video_in) {
      av_packet_rescale_ts(packet, in->streams[video_in]->time_base, video_out->time_base);
      packet->stream_index = 0;
      packet->pos = -1;
      REQUIRE(av_interleaved_write_frame(oc, packet) >= 0);
      written++;
    }
    av_packet_unref(packet);
  }
  av_packet_free(&packet);
  REQUIRE(written > 0);
  REQUIRE(av_write_trailer(oc) >= 0);
  avio_closep(&oc->pb);
  avformat_free_context(oc);
  avformat_close_input(&in);

  // Before: the demuxer has to walk the fragments. After: it stops.
  const std::vector<uint8_t> muxed = read_file(out);
  const std::vector<TopBox> boxes = top_level(muxed);
  int64_t second_moof = 0;
  bool seen = false;
  for (const TopBox &box : boxes) {
    if (box.type != "moof") continue;
    if (seen) { second_moof = int64_t(box.offset); break; }
    seen = true;
  }
  REQUIRE(second_moof > 0);

  auto position_after_header = [](const std::filesystem::path &path) {
    AVFormatContext *ctx = nullptr;
    REQUIRE(avformat_open_input(&ctx, path.c_str(), nullptr, nullptr) == 0);
    const int64_t position = avio_tell(ctx->pb);
    avformat_close_input(&ctx);
    return position;
  };
  auto packets_readable = [](const std::filesystem::path &path) {
    AVFormatContext *ctx = nullptr;
    REQUIRE(avformat_open_input(&ctx, path.c_str(), nullptr, nullptr) == 0);
    AVPacket *pkt = av_packet_alloc();
    int count = 0;
    while (av_read_frame(ctx, pkt) >= 0) { count++; av_packet_unref(pkt); }
    av_packet_free(&pkt);
    avformat_close_input(&ctx);
    return count;
  };

  REQUIRE(position_after_header(out) > second_moof);
  const int before = packets_readable(out);
  REQUIRE(before == written);

  REQUIRE(zm_mp4::write_leading_sidx(out.string(), region_offset, zm_mp4::kSidxReserve));
  REQUIRE(position_after_header(out) < second_moof);
  REQUIRE(packets_readable(out) == written);   // nothing lost by indexing

  std::filesystem::remove(out, ignored);
}

TEST_CASE("Mp4SidxStopsTheDemuxerAfterTheFirstFragment") {
  // The point of all of it: with the index in place libavformat -- the
  // demuxer inside Chromium and the Android WebView too -- must not read on
  // through the fragments before it can hand over a frame.
  Subject subject("sidx-abs.mp4");
  const std::vector<TopBox> boxes = top_level(subject.expected);
  int64_t second_moof = 0;
  bool seen_first = false;
  for (const TopBox &box : boxes) {
    if (box.type != "moof") continue;
    if (seen_first) { second_moof = static_cast<int64_t>(box.offset); break; }
    seen_first = true;
  }
  REQUIRE(second_moof > 0);

  auto position_after_header = [](const std::filesystem::path &path) {
    AVFormatContext *ctx = nullptr;
    REQUIRE(avformat_open_input(&ctx, path.c_str(), nullptr, nullptr) == 0);
    const int64_t position = avio_tell(ctx->pb);
    avformat_close_input(&ctx);
    return position;
  };

  REQUIRE(position_after_header(subject.path) > second_moof);   // blanked: reads on
  REQUIRE(zm_mp4::write_leading_sidx(subject.path.string(),
                                     subject.region_offset, subject.region_size));
  REQUIRE(position_after_header(subject.path) < second_moof);   // indexed: stops
}
