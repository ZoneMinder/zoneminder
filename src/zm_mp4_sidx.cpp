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

#include "zm_mp4_sidx.h"

#include "zm_logger.h"

#include <cerrno>
#include <cstring>
#include <fcntl.h>
#include <sys/stat.h>
#include <unistd.h>

namespace zm_mp4 {

namespace {

constexpr int64_t kSidxHeaderBytes = 40;      // version 1, up to the references
constexpr int64_t kSidxReferenceBytes = 12;
constexpr size_t kMaxReferenceCount = 65535;  // reference_count is 16 bits
constexpr int64_t kMaxReferencedSize = (int64_t(1) << 31) - 1;
constexpr uint32_t kMaxSamplesPerTrun = 1u << 20;   // sanity, not a spec limit

// tfhd flags, 14496-12 8.8.7
constexpr uint32_t kTfhdBaseDataOffset = 0x000001;
constexpr uint32_t kTfhdSampleDescriptionIndex = 0x000002;
constexpr uint32_t kTfhdDefaultSampleDuration = 0x000008;

// trun flags, 14496-12 8.8.8
constexpr uint32_t kTrunDataOffset = 0x000001;
constexpr uint32_t kTrunFirstSampleFlags = 0x000004;
constexpr uint32_t kTrunSampleDuration = 0x000100;
constexpr uint32_t kTrunSampleSize = 0x000200;
constexpr uint32_t kTrunSampleFlags = 0x000400;
constexpr uint32_t kTrunSampleCts = 0x000800;

uint32_t rb32(const uint8_t *p) {
  return (static_cast<uint32_t>(p[0]) << 24) | (static_cast<uint32_t>(p[1]) << 16)
       | (static_cast<uint32_t>(p[2]) << 8) | static_cast<uint32_t>(p[3]);
}

uint64_t rb64(const uint8_t *p) {
  return (static_cast<uint64_t>(rb32(p)) << 32) | rb32(p + 4);
}

void wb16(uint8_t *p, uint16_t v) {
  p[0] = static_cast<uint8_t>(v >> 8);
  p[1] = static_cast<uint8_t>(v);
}

void wb32(uint8_t *p, uint32_t v) {
  p[0] = static_cast<uint8_t>(v >> 24);
  p[1] = static_cast<uint8_t>(v >> 16);
  p[2] = static_cast<uint8_t>(v >> 8);
  p[3] = static_cast<uint8_t>(v);
}

void wb64(uint8_t *p, uint64_t v) {
  wb32(p, static_cast<uint32_t>(v >> 32));
  wb32(p + 4, static_cast<uint32_t>(v));
}

bool read_exact(int fd, void *buffer, size_t bytes, int64_t offset) {
  uint8_t *p = static_cast<uint8_t *>(buffer);
  while (bytes > 0) {
    ssize_t got = pread(fd, p, bytes, offset);
    if (got <= 0) return false;
    p += got;
    bytes -= static_cast<size_t>(got);
    offset += got;
  }
  return true;
}

bool write_exact(int fd, const void *buffer, size_t bytes, int64_t offset) {
  const uint8_t *p = static_cast<const uint8_t *>(buffer);
  while (bytes > 0) {
    ssize_t put = pwrite(fd, p, bytes, offset);
    if (put <= 0) return false;
    p += put;
    bytes -= static_cast<size_t>(put);
    offset += put;
  }
  return true;
}

struct Box {
  char type[5] = {0};
  int64_t offset = 0;
  int64_t size = 0;
  int64_t header = 0;

  int64_t body() const { return offset + header; }
  int64_t end() const { return offset + size; }
  bool is(const char *what) const { return memcmp(type, what, 4) == 0; }
};

// One box at `offset`. Fails on anything that would run past `limit`, which
// is how a truncated recording is refused rather than half-indexed.
bool read_box(int fd, int64_t offset, int64_t limit, Box *box) {
  if (offset < 0 || offset + 8 > limit) return false;
  uint8_t head[16];
  if (!read_exact(fd, head, 8, offset)) return false;
  int64_t size = rb32(head);
  int64_t header = 8;
  if (size == 1) {
    if (offset + 16 > limit) return false;
    if (!read_exact(fd, head + 8, 8, offset + 8)) return false;
    size = static_cast<int64_t>(rb64(head + 8));
    header = 16;
  } else if (size == 0) {
    size = limit - offset;
  }
  if (size < header || offset > limit - size) return false;
  memcpy(box->type, head + 4, 4);
  box->type[4] = 0;
  box->offset = offset;
  box->size = size;
  box->header = header;
  return true;
}

// The first child of [start, end) with this type.
bool find_box(int fd, int64_t start, int64_t end, const char *type, Box *box) {
  int64_t pos = start;
  while (pos + 8 <= end) {
    Box candidate;
    if (!read_box(fd, pos, end, &candidate)) return false;
    if (candidate.is(type)) {
      *box = candidate;
      return true;
    }
    pos = candidate.end();
  }
  return false;
}

}  // namespace

int64_t media_end(int fd, int64_t file_size) {
  // An `mfro` is always the last 16 bytes of an `mfra` and states its size,
  // so the trailer can be found without walking the fragments to reach it.
  if (file_size < 16) return file_size;
  uint8_t tail[16];
  if (!read_exact(fd, tail, 16, file_size - 16)) return file_size;
  if (rb32(tail) != 16 || memcmp(tail + 4, "mfro", 4) != 0) return file_size;
  int64_t mfra_size = rb32(tail + 12);
  if (mfra_size <= 0 || mfra_size > file_size) return file_size;
  int64_t offset = file_size - mfra_size;
  Box mfra;
  if (!read_box(fd, offset, file_size, &mfra) || !mfra.is("mfra")
      || mfra.size != mfra_size) {
    return file_size;
  }
  return offset;
}

bool read_video_track(int fd, int64_t file_size, VideoTrack *track) {
  Box moov;
  if (!find_box(fd, 0, file_size, "moov", &moov)) {
    Debug(1, "sidx: no moov");
    return false;
  }

  bool found = false;
  int64_t pos = moov.body();
  while (pos + 8 <= moov.end()) {
    Box trak;
    if (!read_box(fd, pos, moov.end(), &trak)) return false;
    pos = trak.end();
    if (!trak.is("trak")) continue;

    Box tkhd;
    Box mdia;
    if (!find_box(fd, trak.body(), trak.end(), "tkhd", &tkhd)) continue;
    if (!find_box(fd, trak.body(), trak.end(), "mdia", &mdia)) continue;

    Box hdlr;
    Box mdhd;
    if (!find_box(fd, mdia.body(), mdia.end(), "hdlr", &hdlr)) continue;
    if (!find_box(fd, mdia.body(), mdia.end(), "mdhd", &mdhd)) continue;

    uint8_t handler[4];
    if (!read_exact(fd, handler, 4, hdlr.body() + 8)) return false;
    if (memcmp(handler, "vide", 4) != 0) continue;

    uint8_t version = 0;
    if (!read_exact(fd, &version, 1, tkhd.body())) return false;
    uint8_t word[4];
    if (!read_exact(fd, word, 4, tkhd.body() + (version == 1 ? 20 : 12))) return false;
    track->id = rb32(word);

    if (!read_exact(fd, &version, 1, mdhd.body())) return false;
    if (!read_exact(fd, word, 4, mdhd.body() + (version == 1 ? 20 : 12))) return false;
    track->timescale = rb32(word);
    found = true;
    break;
  }
  if (!found) {
    Debug(1, "sidx: no video track in moov");
    return false;
  }
  if (track->timescale == 0) {
    Debug(1, "sidx: video track %u has a zero timescale", track->id);
    return false;
  }

  Box mvex;
  if (find_box(fd, moov.body(), moov.end(), "mvex", &mvex)) {
    int64_t at = mvex.body();
    while (at + 8 <= mvex.end()) {
      Box trex;
      if (!read_box(fd, at, mvex.end(), &trex)) break;
      at = trex.end();
      if (!trex.is("trex")) continue;
      uint8_t body[16];
      if (!read_exact(fd, body, 16, trex.body() + 4)) break;
      if (rb32(body) == track->id) {
        track->default_sample_duration = rb32(body + 8);
        break;
      }
    }
  }
  return true;
}

namespace {

// One `traf`: which track it belongs to, and -- for the video track -- the
// decode time it starts at and how long its samples last.
bool parse_traf(int fd, const Box &traf, const VideoTrack &track,
                bool want_first_cts, uint32_t *track_id,
                int64_t *tfdt, int64_t *duration, int64_t *first_cts) {
  Box tfhd;
  if (!find_box(fd, traf.body(), traf.end(), "tfhd", &tfhd)) return false;
  uint8_t head[8];
  if (!read_exact(fd, head, 8, tfhd.body())) return false;
  const uint32_t flags = rb32(head) & 0xFFFFFF;
  *track_id = rb32(head + 4);

  int64_t at = tfhd.body() + 8;
  if (flags & kTfhdBaseDataOffset) at += 8;
  if (flags & kTfhdSampleDescriptionIndex) at += 4;
  uint32_t default_duration = track.default_sample_duration;
  if (flags & kTfhdDefaultSampleDuration) {
    uint8_t word[4];
    if (!read_exact(fd, word, 4, at)) return false;
    default_duration = rb32(word);
    at += 4;
  }

  *tfdt = -1;
  Box tfdt_box;
  if (find_box(fd, traf.body(), traf.end(), "tfdt", &tfdt_box)) {
    uint8_t version = 0;
    if (!read_exact(fd, &version, 1, tfdt_box.body())) return false;
    uint8_t value[8];
    if (version == 1) {
      if (!read_exact(fd, value, 8, tfdt_box.body() + 4)) return false;
      *tfdt = static_cast<int64_t>(rb64(value));
    } else {
      if (!read_exact(fd, value, 4, tfdt_box.body() + 4)) return false;
      *tfdt = rb32(value);
    }
  }

  *duration = 0;
  *first_cts = 0;
  bool have_first = false;
  int64_t pos = traf.body();
  while (pos + 8 <= traf.end()) {
    Box trun;
    if (!read_box(fd, pos, traf.end(), &trun)) return false;
    pos = trun.end();
    if (!trun.is("trun")) continue;

    uint8_t trun_head[8];
    if (!read_exact(fd, trun_head, 8, trun.body())) return false;
    const uint8_t version = trun_head[0];
    const uint32_t trun_flags = rb32(trun_head) & 0xFFFFFF;
    const uint32_t count = rb32(trun_head + 4);
    if (count > kMaxSamplesPerTrun) return false;

    int64_t entry = trun.body() + 8;
    if (trun_flags & kTrunDataOffset) entry += 4;
    if (trun_flags & kTrunFirstSampleFlags) entry += 4;
    const int per_sample =
        ((trun_flags & kTrunSampleDuration) ? 4 : 0)
      + ((trun_flags & kTrunSampleSize) ? 4 : 0)
      + ((trun_flags & kTrunSampleFlags) ? 4 : 0)
      + ((trun_flags & kTrunSampleCts) ? 4 : 0);

    if (!(trun_flags & kTrunSampleDuration)) {
      // Every sample lasts the default, so the table need not be read.
      *duration += static_cast<int64_t>(count) * default_duration;
    }
    if (per_sample > 0 && count > 0) {
      const size_t bytes = static_cast<size_t>(count) * static_cast<size_t>(per_sample);
      if (entry + static_cast<int64_t>(bytes) > trun.end()) return false;
      std::vector<uint8_t> table(bytes);
      if (!read_exact(fd, table.data(), bytes, entry)) return false;
      for (uint32_t i = 0; i < count; i++) {
        const uint8_t *sample = table.data() + static_cast<size_t>(i) * per_sample;
        if (trun_flags & kTrunSampleDuration) *duration += rb32(sample);
        if (want_first_cts && !have_first && (trun_flags & kTrunSampleCts)) {
          const uint8_t *cts = sample + per_sample - 4;
          // 14496-12 8.8.8: signed in version 1, unsigned in version 0.
          *first_cts = (version == 1)
              ? static_cast<int64_t>(static_cast<int32_t>(rb32(cts)))
              : static_cast<int64_t>(rb32(cts));
          have_first = true;
        }
      }
    }
  }
  return true;
}

}  // namespace

bool scan_fragments(int fd, int64_t from, int64_t to, const VideoTrack &track,
                    std::vector<Fragment> *fragments) {
  fragments->clear();
  int64_t pos = from;
  while (pos + 8 <= to) {
    Box moof;
    if (!read_box(fd, pos, to, &moof) || !moof.is("moof")) {
      Debug(1, "sidx: expected a moof at %" PRId64, pos);
      return false;
    }
    Box mdat;
    if (!read_box(fd, moof.end(), to, &mdat) || !mdat.is("mdat")) {
      Debug(1, "sidx: moof at %" PRId64 " is not followed by an mdat", pos);
      return false;
    }

    Fragment fragment;
    fragment.offset = moof.offset;
    fragment.size = moof.size + mdat.size;
    bool have_video = false;

    int64_t at = moof.body();
    while (at + 8 <= moof.end()) {
      Box traf;
      if (!read_box(fd, at, moof.end(), &traf)) return false;
      at = traf.end();
      if (!traf.is("traf")) continue;

      uint32_t track_id = 0;
      int64_t tfdt = -1;
      int64_t duration = 0;
      int64_t first_cts = 0;
      const bool want_cts = fragments->empty();
      if (!parse_traf(fd, traf, track, want_cts, &track_id, &tfdt, &duration, &first_cts)) {
        Debug(1, "sidx: cannot parse a traf at %" PRId64, traf.offset);
        return false;
      }
      if (track_id != track.id) continue;
      if (tfdt < 0) {
        Debug(1, "sidx: the video traf at %" PRId64 " has no tfdt", traf.offset);
        return false;
      }
      fragment.tfdt = tfdt;
      fragment.trun_duration = duration;
      fragment.first_cts = want_cts ? first_cts : 0;
      have_video = true;
    }
    if (!have_video) {
      Debug(1, "sidx: the moof at %" PRId64 " has no video traf", moof.offset);
      return false;
    }
    fragments->push_back(fragment);
    pos = mdat.end();
  }
  if (pos != to) {
    Debug(1, "sidx: %" PRId64 " bytes at %" PRId64 " are not a fragment", to - pos, pos);
    return false;
  }
  return !fragments->empty();
}

size_t max_references(int64_t reserve) {
  // A region is either filled exactly, or leaves room for a whole `free` box
  // header (8 bytes) to cover what is left.
  const int64_t exact = reserve - kSidxHeaderBytes;
  if (exact >= 0 && exact % kSidxReferenceBytes == 0) {
    const int64_t n = exact / kSidxReferenceBytes;
    if (n >= 0 && static_cast<uint64_t>(n) <= kMaxReferenceCount) {
      return static_cast<size_t>(n);
    }
  }
  const int64_t n = (reserve - kSidxHeaderBytes - 8) / kSidxReferenceBytes;
  if (n <= 0) return 0;
  return static_cast<size_t>(n) > kMaxReferenceCount
      ? kMaxReferenceCount : static_cast<size_t>(n);
}

std::vector<uint8_t> build_sidx_region(const VideoTrack &track,
                                       const std::vector<Fragment> &fragments,
                                       int64_t reserve) {
  std::vector<uint8_t> empty;
  if (fragments.empty() || track.timescale == 0) return empty;

  const size_t limit = max_references(reserve);
  if (limit == 0) {
    Debug(1, "sidx: a %" PRId64 " byte region holds no references", reserve);
    return empty;
  }

  // More fragments than references: fold neighbours together. A merged
  // reference still points at a real `moof`, so the demuxer seeks to it and
  // reads forward; merging coarsens seeking and never breaks playback.
  const size_t per_reference =
      fragments.size() <= limit ? 1 : (fragments.size() + limit - 1) / limit;

  struct Reference {
    int64_t size;
    int64_t duration;
  };
  std::vector<Reference> references;
  references.reserve((fragments.size() + per_reference - 1) / per_reference);

  for (size_t i = 0; i < fragments.size(); i += per_reference) {
    Reference reference{0, 0};
    for (size_t j = i; j < i + per_reference && j < fragments.size(); j++) {
      reference.size += fragments[j].size;
      // A fragment lasts until the next one starts, which its `tfdt` states
      // exactly; only the last has to be summed from its `trun`.
      reference.duration += (j + 1 < fragments.size())
          ? fragments[j + 1].tfdt - fragments[j].tfdt
          : fragments[j].trun_duration;
    }
    if (reference.size <= 0 || reference.size > kMaxReferencedSize) {
      Debug(1, "sidx: a reference would cover %" PRId64 " bytes", reference.size);
      return empty;
    }
    if (reference.duration <= 0 || reference.duration > 0xFFFFFFFF) {
      Debug(1, "sidx: a reference would last %" PRId64 " ticks", reference.duration);
      return empty;
    }
    references.push_back(reference);
  }

  const int64_t sidx_bytes =
      kSidxHeaderBytes + kSidxReferenceBytes * static_cast<int64_t>(references.size());
  const int64_t padding = reserve - sidx_bytes;
  if (padding < 0 || (padding > 0 && padding < 8)) {
    Debug(1, "sidx: %" PRId64 " bytes of sidx do not fit a %" PRId64 " byte region",
          sidx_bytes, reserve);
    return empty;
  }

  int64_t earliest = fragments.front().tfdt + fragments.front().first_cts;
  if (earliest < 0) earliest = 0;

  std::vector<uint8_t> region(static_cast<size_t>(reserve), 0);
  uint8_t *p = region.data();
  if (padding > 0) {
    // The padding goes FIRST, so the sidx ends where the fragments begin and
    // first_offset can be 0 -- which is what makes the index complete.
    wb32(p, static_cast<uint32_t>(padding));
    memcpy(p + 4, "free", 4);
    p += padding;
  }
  wb32(p, static_cast<uint32_t>(sidx_bytes));
  memcpy(p + 4, "sidx", 4);
  p[8] = 1;                       // version 1: 64-bit times and offsets
  p[9] = p[10] = p[11] = 0;       // flags
  wb32(p + 12, track.id);
  wb32(p + 16, track.timescale);
  wb64(p + 20, static_cast<uint64_t>(earliest));
  wb64(p + 28, 0);                // first_offset: the next byte is the moof
  wb16(p + 36, 0);                // reserved
  wb16(p + 38, static_cast<uint16_t>(references.size()));
  uint8_t *entry = p + 40;
  for (const Reference &reference : references) {
    wb32(entry, static_cast<uint32_t>(reference.size));      // type 0 + size
    wb32(entry + 4, static_cast<uint32_t>(reference.duration));
    wb32(entry + 8, 0x90000000);  // starts with a SAP, type 1
    entry += kSidxReferenceBytes;
  }
  return region;
}

bool write_leading_sidx(const std::string &path, int64_t region_offset,
                        int64_t region_size) {
  if (region_offset < 0 || region_size <= 0) return false;

  const int fd = open(path.c_str(), O_RDWR | O_CLOEXEC);
  if (fd < 0) {
    Warning("sidx: cannot reopen %s: %s", path.c_str(), strerror(errno));
    return false;
  }

  bool done = false;
  do {
    struct stat info;
    if (fstat(fd, &info) != 0) {
      Warning("sidx: cannot stat %s: %s", path.c_str(), strerror(errno));
      break;
    }
    const int64_t file_size = info.st_size;
    const int64_t first_moof = region_offset + region_size;

    Box moof;
    if (!read_box(fd, first_moof, file_size, &moof) || !moof.is("moof")) {
      Debug(1, "sidx: no moof at %" PRId64 " in %s, leaving the region alone",
            first_moof, path.c_str());
      break;
    }

    VideoTrack track;
    if (!read_video_track(fd, file_size, &track)) break;

    std::vector<Fragment> fragments;
    const int64_t end = media_end(fd, file_size);
    if (!scan_fragments(fd, first_moof, end, track, &fragments)) break;

    const std::vector<uint8_t> region = build_sidx_region(track, fragments, region_size);
    if (region.empty()) break;

    // The region is one `free` box until the moment its first eight bytes
    // change, so write the body first and that header last: a write cut short
    // anywhere leaves a file that still parses exactly as it did before.
    if (!write_exact(fd, region.data() + 8, region.size() - 8, region_offset + 8)) {
      Warning("sidx: cannot write the index into %s: %s", path.c_str(), strerror(errno));
      break;
    }
    if (fsync(fd) != 0) {
      Warning("sidx: cannot flush %s: %s", path.c_str(), strerror(errno));
      break;
    }
    if (!write_exact(fd, region.data(), 8, region_offset)) {
      Warning("sidx: cannot commit the index in %s: %s", path.c_str(), strerror(errno));
      break;
    }
    if (fsync(fd) != 0) {
      Warning("sidx: cannot flush %s: %s", path.c_str(), strerror(errno));
      break;
    }

    Debug(1, "sidx: indexed %s -- %zu fragments in %" PRId64 " bytes",
          path.c_str(), fragments.size(), region_size);
    done = true;
  } while (false);

  close(fd);
  return done;
}

}  // namespace zm_mp4
