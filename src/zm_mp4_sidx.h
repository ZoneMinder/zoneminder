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

#ifndef ZM_MP4_SIDX_H
#define ZM_MP4_SIDX_H

#include <cstdint>
#include <string>
#include <vector>

struct AVFormatContext;

// A leading `sidx` (segment index) for the fragmented MP4s VideoStore writes.
//
// Without an index, a player's demuxer cannot know where the fragments are,
// so it reads forward through every `moof` before it can present the first
// frame -- which means pulling the WHOLE file over the network. FFmpeg's mov
// demuxer, the one inside Chromium, the Android WebView and Electron, marks
// its fragment index complete only when it meets a `sidx` that
//
//   * has first_offset == 0, i.e. ENDS where the first `moof` begins
//     (FFmpeg master and Chromium's copy require it), and
//   * covers every byte from there to the `mfra` (or to EOF) exactly.
//
// So VideoStore reserves a region right after `moov` when it opens the file,
// and fills it once the fragments are all on disk:
//
//   ftyp | moov | free (padding) | sidx | moof mdat | ... | mfra
//                 \______ the reserved region, reserve_size() ______/
//
// Nothing about the recorded video changes, and a failure at any step leaves
// the region as the `free` box it started as -- exactly the file ZoneMinder
// wrote before this existed.
namespace zm_mp4 {

// The most a region takes: 64 KiB, which holds 5458 references. A one-hour
// event at a one-second GOP has ~3600 fragments, so the common case fits with
// room to spare; anything longer merges neighbouring fragments into one
// reference rather than giving up (see build_sidx_region).
constexpr int64_t kSidxReserve = 65536;
// The least: one filesystem block, 337 references.
constexpr int64_t kSidxMinReserve = 4096;

struct VideoTrack {
  uint32_t id = 0;
  uint32_t timescale = 0;
  uint32_t default_sample_duration = 0;
  uint32_t default_sample_flags = 0;
};

struct Fragment {
  int64_t offset = 0;          // where the `moof` starts
  int64_t size = 0;            // `moof` + `mdat`
  int64_t tfdt = 0;            // the video track's baseMediaDecodeTime
  int64_t trun_duration = 0;   // its summed sample durations
  int64_t first_cts = 0;       // composition offset of the first sample
  bool starts_with_sap = false;  // its first video sample is a sync sample
};

// The video track's id, timescale and `trex` default sample duration and flags.
bool read_video_track(int fd, int64_t file_size, VideoTrack *track);

// Where the media ends: the `mfra` offset when there is one, else file_size.
int64_t media_end(int fd, int64_t file_size);

// Every `moof`+`mdat` pair in [from, to). Fails unless the range is nothing
// but whole fragments, which is what makes a complete index possible.
bool scan_fragments(int fd, int64_t from, int64_t to, const VideoTrack &track,
                    std::vector<Fragment> *fragments);

// Whether a region reserved right now, after `oc`'s header has been written,
// could be indexed once the recording ends. That takes the MOV/MP4 muxer, a
// header that already wrote the moov, fragments of nothing but moof+mdat to
// follow it, and no movflag of the muxer's own that rewrites the front of the
// file in the trailer. The answer comes from the flags the muxer settled on,
// so dash, cmaf and ismv count as the empty_moov they imply.
bool fragments_follow_header(AVFormatContext *oc);

// Write a region of `reserve` bytes -- one `free` box -- to `oc`'s output right
// after its header, when fragments_follow_header() says it can be indexed.
// Returns where the region begins, or -1 when there is none to fill.
int64_t reserve_region(AVFormatContext *oc, int64_t reserve);

// The region for a recording of about `seconds`, cut into fragments of about
// `fragment_seconds` each: room for twice that many references, in whole 4 KiB
// blocks from kSidxMinReserve to kSidxReserve. kSidxReserve when either is
// unknown. Guessing short only merges references, which coarsens seeking.
int64_t reserve_size(double seconds, double fragment_seconds);

// How many references fit a region of `reserve` bytes.
size_t max_references(int64_t reserve);

// The whole region: `free` padding followed by the `sidx`, exactly `reserve`
// bytes. Empty on any inconsistency, and the caller must then write nothing.
std::vector<uint8_t> build_sidx_region(const VideoTrack &track,
                                       const std::vector<Fragment> &fragments,
                                       int64_t reserve);

// Fill a region that VideoStore reserved, in place. `region_offset` is where
// the `free` box begins and `region_size` how big it is, so the first `moof`
// must start at region_offset + region_size. Returns false and leaves the
// file untouched if anything does not add up.
bool write_leading_sidx(const std::string &path, int64_t region_offset,
                        int64_t region_size);

}  // namespace zm_mp4

#endif  // ZM_MP4_SIDX_H
