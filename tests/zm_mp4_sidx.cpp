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

#include <cmath>
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
#include <libavutil/opt.h>
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

// A file of this process's own in the temp directory. ctest runs each test
// case as a process of its own and in parallel, so a fixed name would have
// one case overwrite or remove another's file under it.
std::filesystem::path scratch(const std::string &name) {
  return std::filesystem::temp_directory_path() /
         ("zm-sidx-" + std::to_string(getpid()) + "-" + name);
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

    path = scratch(name);
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

TEST_CASE("Mp4SidxMediaEndTrustsOnlyAnMfraThatIsThere") {
  // finalize() sizes the last HLS fragment from this, so an mfro whose size
  // does not land on an mfra of that size must not cut the media short.
  std::vector<uint8_t> file = read_file(fixture("sidx-abs.mp4"));
  const int64_t size = static_cast<int64_t>(file.size());
  const int64_t mfra = static_cast<int64_t>(offset_of(top_level(file), "mfra"));
  const std::filesystem::path path = scratch("mfro.mp4");

  auto end_of = [&path](const std::vector<uint8_t> &bytes) {
    write_file(path, bytes);
    const int fd = open(path.c_str(), O_RDONLY);
    REQUIRE(fd >= 0);
    const int64_t end = zm_mp4::media_end(fd, static_cast<int64_t>(bytes.size()));
    close(fd);
    return end;
  };

  REQUIRE(end_of(file) == mfra);

  SECTION("an mfro size off by four") {
    file[size - 1] = uint8_t(file[size - 1] + 4);
    REQUIRE(end_of(file) == size);
  }
  SECTION("an mfro size larger than the file") {
    file[size - 4] = 0xFF;
    REQUIRE(end_of(file) == size);
  }
  SECTION("no mfro at all") {
    memcpy(&file[size - 12], "xxxx", 4);
    REQUIRE(end_of(file) == size);
  }

  std::error_code ignored;
  std::filesystem::remove(path, ignored);
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

TEST_CASE("Mp4SidxSizesTheRegionForTheEvent") {
  SECTION("unknown length or GOP takes the most") {
    REQUIRE(zm_mp4::reserve_size(0, 1) == zm_mp4::kSidxReserve);
    REQUIRE(zm_mp4::reserve_size(600, 0) == zm_mp4::kSidxReserve);
    REQUIRE(zm_mp4::reserve_size(-1, 1) == zm_mp4::kSidxReserve);
    REQUIRE(zm_mp4::reserve_size(600, std::nan("")) == zm_mp4::kSidxReserve);
  }

  SECTION("a default 600 s section at a 1 s GOP") {
    // 1200 references, 14448 bytes, rounded up to whole 4 KiB blocks.
    REQUIRE(zm_mp4::reserve_size(600, 1) == 16384);
  }

  SECTION("short events take the least") {
    REQUIRE(zm_mp4::reserve_size(10, 1) == zm_mp4::kSidxMinReserve);
  }

  SECTION("long events take no more than the most") {
    REQUIRE(zm_mp4::reserve_size(3600, 1) == zm_mp4::kSidxReserve);
    REQUIRE(zm_mp4::reserve_size(600, 0.001) == zm_mp4::kSidxReserve);
  }

  SECTION("the region holds twice the expected fragments") {
    for (double seconds : {30.0, 120.0, 600.0, 1200.0}) {
      for (double gop : {0.5, 1.0, 2.0, 4.0}) {
        INFO(seconds << " s at a " << gop << " s GOP");
        const int64_t reserve = zm_mp4::reserve_size(seconds, gop);
        REQUIRE(reserve % 4096 == 0);
        REQUIRE(reserve >= zm_mp4::kSidxMinReserve);
        REQUIRE(reserve <= zm_mp4::kSidxReserve);
        if (reserve < zm_mp4::kSidxReserve) {
          REQUIRE(zm_mp4::max_references(reserve) >= size_t(2 * std::ceil(seconds / gop)));
        }
      }
    }
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

namespace {

// Remux the video of `source` into `out` the way VideoStore writes an event:
// header, a reserved region on the muxer's own AVIOContext, packets, trailer.
// `frag_duration` (microseconds, 0 for none) cuts fragments mid-GOP as well.
struct Remuxed {
  int64_t region_offset = -1;
  int written = 0;
};

Remuxed remux_with_region(const std::filesystem::path &source,
                          const std::filesystem::path &out,
                          const char *movflags, int64_t frag_duration) {
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
  av_dict_set(&opts, "movflags", movflags, 0);
  if (frag_duration > 0) av_dict_set_int(&opts, "frag_duration", frag_duration, 0);
  REQUIRE(avformat_write_header(oc, &opts) >= 0);
  av_dict_free(&opts);

  Remuxed result;
  result.region_offset = zm_mp4::reserve_region(oc, zm_mp4::kSidxReserve);
  REQUIRE(result.region_offset > 0);
  REQUIRE(avio_tell(oc->pb) == result.region_offset + zm_mp4::kSidxReserve);

  AVPacket *packet = av_packet_alloc();
  REQUIRE(packet != nullptr);
  while (av_read_frame(in, packet) >= 0) {
    if (packet->stream_index == video_in) {
      av_packet_rescale_ts(packet, in->streams[video_in]->time_base, video_out->time_base);
      packet->stream_index = 0;
      packet->pos = -1;
      REQUIRE(av_interleaved_write_frame(oc, packet) >= 0);
      result.written++;
    }
    av_packet_unref(packet);
  }
  av_packet_free(&packet);
  REQUIRE(result.written > 0);
  REQUIRE(av_write_trailer(oc) >= 0);
  avio_closep(&oc->pb);
  avformat_free_context(oc);
  avformat_close_input(&in);
  return result;
}

}  // namespace

TEST_CASE("Mp4SidxIndexesWhatTheMuxerWritesAfterAReservedRegion") {
  // The sequence VideoStore follows, against the real muxer: write the
  // header, put the `free` region on the muxer's own AVIOContext before any
  // packet, remux a recording through it, write the trailer, then fill the
  // region. What this proves is the part unit tests of the parser cannot:
  // that the muxer's own tfhd and tfra offsets account for the reserved
  // bytes, because it takes every offset from avio_tell.
  const std::filesystem::path out = scratch("remux.mp4");
  std::error_code ignored;
  const Remuxed remuxed = remux_with_region(
      fixture("sidx-abs.mp4"), out, "frag_keyframe+empty_moov+default_base_moof", 0);
  const int64_t region_offset = remuxed.region_offset;
  const int written = remuxed.written;

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

TEST_CASE("Mp4SidxMarksOnlyKeyframeFragmentsAsSaps") {
  // frag_duration cuts a fragment every 250 ms as well as at each keyframe,
  // so the 3 s, 1 s GOP fixture becomes about a dozen fragments of which
  // exactly the three that begin at a keyframe start with a SAP. A reference
  // that claims one where there is none sends a seek to a frame that cannot
  // be decoded on its own.
  const std::filesystem::path out = scratch("mid-gop.mp4");
  std::error_code ignored;
  const Remuxed remuxed = remux_with_region(
      fixture("sidx-abs.mp4"), out, "frag_keyframe+empty_moov+default_base_moof", 250000);
  REQUIRE(zm_mp4::write_leading_sidx(out.string(), remuxed.region_offset, zm_mp4::kSidxReserve));

  const std::vector<uint8_t> file = read_file(out);
  const size_t sidx = offset_of(top_level(file), "sidx");
  const size_t count = (size_t(file[sidx + 38]) << 8) | file[sidx + 39];
  REQUIRE(count > 3);
  size_t saps = 0;
  for (size_t i = 0; i < count; i++) {
    const uint32_t sap = be32(&file[sidx + 40 + 12 * i + 8]);
    if (sap == 0x90000000) {
      saps++;
    } else {
      REQUIRE(sap == 0);   // no SAP, no type, no delta
    }
  }
  REQUIRE(be32(&file[sidx + 40 + 8]) == 0x90000000);   // the event opens on a keyframe
  REQUIRE(saps == 3);

  std::filesystem::remove(out, ignored);
}

TEST_CASE("Mp4SidxFindsTheSapsOfAKeyframeRecording") {
  Subject subject("sidx-abs.mp4");
  const int fd = subject.open_read();
  const int64_t size = int64_t(std::filesystem::file_size(subject.path));
  zm_mp4::VideoTrack track;
  REQUIRE(zm_mp4::read_video_track(fd, size, &track));
  std::vector<zm_mp4::Fragment> fragments;
  REQUIRE(zm_mp4::scan_fragments(fd, subject.region_offset + subject.region_size,
                                 zm_mp4::media_end(fd, size), track, &fragments));
  close(fd);
  REQUIRE(fragments.size() == 3);
  for (const zm_mp4::Fragment &fragment : fragments) REQUIRE(fragment.starts_with_sap);
}

TEST_CASE("Mp4SidxMergedReferenceTakesItsFirstFragmentsSap") {
  std::vector<zm_mp4::Fragment> fragments;
  for (int i = 0; i < 4; i++) {
    zm_mp4::Fragment fragment;
    fragment.offset = 1000 + i * 100;
    fragment.size = 100;
    fragment.tfdt = i * 90000;
    fragment.trun_duration = 90000;
    fragment.starts_with_sap = (i == 0 || i == 3);
    fragments.push_back(fragment);
  }
  zm_mp4::VideoTrack track;
  track.id = 1;
  track.timescale = 90000;

  SECTION("one reference each") {
    const std::vector<uint8_t> region =
        zm_mp4::build_sidx_region(track, fragments, zm_mp4::kSidxMinReserve);
    const uint8_t *entries = region.data() + zm_mp4::kSidxMinReserve - 12 * 4;
    REQUIRE(be32(entries + 8) == 0x90000000);
    REQUIRE(be32(entries + 12 + 8) == 0);
    REQUIRE(be32(entries + 24 + 8) == 0);
    REQUIRE(be32(entries + 36 + 8) == 0x90000000);
  }

  SECTION("merged in pairs") {
    const int64_t reserve = 40 + 12 * 2 + 8;
    const std::vector<uint8_t> region = zm_mp4::build_sidx_region(track, fragments, reserve);
    REQUIRE(region.size() == size_t(reserve));
    const uint8_t *entries = region.data() + 8 + 40;
    REQUIRE(be32(entries + 8) == 0x90000000);   // fragments 0 and 1
    REQUIRE(be32(entries + 12 + 8) == 0);       // fragments 2 and 3
  }
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

TEST_CASE("Mp4SidxReservesOnlyWhereFragmentsFollowTheMoov") {
  // VideoStore hands the monitor's encoder options to whatever muxer the
  // container picked, so neither the muxer nor the movflags are a given. A
  // region is only worth its bytes where the header has put the moov down and
  // bare moof+mdat fragments follow it; anywhere else it is padding nothing
  // will fill -- and in Matroska a corrupt stream. `moov` is what the header
  // must look like on disk, checked here against the bytes the muxer wrote,
  // so the flags the decision reads are held to what they produce.
  struct Case {
    const char *muxer;
    const char *movflags;   // nullptr: none, the muxer's own default
    bool moov;              // the header ends in a moov announcing fragments
    bool reserves;
  };
  const Case cases[] = {
    {"mp4", "frag_keyframe+empty_moov+default_base_moof", true, true},   // VideoStore's default
    {"mp4", "frag_keyframe+empty_moov", true, true},                     // absolute tfhd offsets
    {"mp4", "frag_keyframe+empty_moov+faststart", true, true},           // 1.38's; no-op once fragmented
    {"mp4", "cmaf", true, true},                                         // empty_moov implied
    {"mp4", "dash+skip_sidx", true, true},
    {"mov", "frag_keyframe+empty_moov", true, true},
    {"mp4", nullptr, false, false},                                      // not fragmented
    {"mp4", "frag_keyframe", false, false},                              // moov with the first fragment
    {"mp4", "frag_keyframe+empty_moov+delay_moov", false, false},
    {"mp4", "dash", true, false},                                        // a sidx before every fragment
    {"mp4", "frag_keyframe+empty_moov+global_sidx", true, false},        // the muxer's own index
    {"mp4", "frag_keyframe+empty_moov+hybrid_fragmented", true, false},  // made non-fragmented at the end
    {"matroska", "frag_keyframe+empty_moov+default_base_moof", false, false},
  };

  AVFormatContext *in = nullptr;
  REQUIRE(avformat_open_input(&in, fixture("sidx-abs.mp4").c_str(), nullptr, nullptr) == 0);
  REQUIRE(avformat_find_stream_info(in, nullptr) >= 0);
  const int video_in = av_find_best_stream(in, AVMEDIA_TYPE_VIDEO, -1, -1, nullptr, 0);
  REQUIRE(video_in >= 0);

  const std::filesystem::path out = scratch("header.out");
  std::error_code ignored;
  for (const Case &c : cases) {
    INFO(c.muxer << " movflags=" << (c.movflags ? c.movflags : "(none)"));
    AVFormatContext *oc = nullptr;
    REQUIRE(avformat_alloc_output_context2(&oc, nullptr, c.muxer, out.c_str()) >= 0);

    // hybrid_fragmented is FFmpeg 7.1's; an older libavformat cannot be asked.
    bool known = true;
    if (c.movflags && !strcmp(c.muxer, "mp4")) {
      std::string flags = c.movflags;
      for (size_t at = 0; at != std::string::npos;) {
        const size_t plus = flags.find('+', at);
        const std::string flag = flags.substr(at, plus == std::string::npos ? plus : plus - at);
        if (!av_opt_find(oc->priv_data, flag.c_str(), "movflags", 0, 0)) known = false;
        at = plus == std::string::npos ? plus : plus + 1;
      }
    }
    if (!known) {
      avformat_free_context(oc);
      continue;
    }

    AVStream *video = avformat_new_stream(oc, nullptr);
    REQUIRE(video != nullptr);
    REQUIRE(avcodec_parameters_copy(video->codecpar, in->streams[video_in]->codecpar) >= 0);
    video->codecpar->codec_tag = 0;
    REQUIRE(avio_open(&oc->pb, out.c_str(), AVIO_FLAG_WRITE) >= 0);
    AVDictionary *opts = nullptr;
    if (c.movflags) av_dict_set(&opts, "movflags", c.movflags, 0);
    REQUIRE(avformat_write_header(oc, &opts) >= 0);
    av_dict_free(&opts);
    avio_flush(oc->pb);
    const int64_t header_end = avio_tell(oc->pb);

    REQUIRE(zm_mp4::fragments_follow_header(oc) == c.reserves);

    if (strcmp(c.muxer, "matroska") != 0) {
      const std::vector<uint8_t> header = read_file(out);
      REQUIRE(header.size() == size_t(header_end));
      const std::vector<TopBox> boxes = top_level(header);
      bool moov = false;
      if (!boxes.empty() && boxes.back().type == "moov") {
        const TopBox &box = boxes.back();
        const std::vector<uint8_t> body(header.begin() + box.offset + 8,
                                        header.begin() + box.offset + box.size);
        for (const TopBox &child : top_level(body)) moov = moov || child.type == "mvex";
      }
      REQUIRE(moov == c.moov);
    }

    // And the decision is what VideoStore acts on: a region exactly where the
    // header ended, or not a byte.
    const int64_t region = zm_mp4::reserve_region(oc, zm_mp4::kSidxReserve);
    avio_flush(oc->pb);
    REQUIRE(region == (c.reserves ? header_end : -1));
    REQUIRE(avio_tell(oc->pb) == header_end + (c.reserves ? zm_mp4::kSidxReserve : 0));

    avio_closep(&oc->pb);
    avformat_free_context(oc);
    std::filesystem::remove(out, ignored);
  }
  avformat_close_input(&in);
}
