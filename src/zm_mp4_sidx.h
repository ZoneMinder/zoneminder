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
//                 \____ the reserved region, kSidxReserve bytes ____/
//
// Nothing about the recorded video changes, and a failure at any step leaves
// the region as the `free` box it started as -- exactly the file ZoneMinder
// wrote before this existed.
namespace zm_mp4 {

// 64 KiB, which holds 5458 references and is a whole number of filesystem
// blocks. A one-hour event at a one-second GOP has ~3600 fragments, so the
// common case fits with room to spare; anything longer merges neighbouring
// fragments into one reference rather than giving up (see BuildSidxRegion).
constexpr int64_t kSidxReserve = 65536;

struct VideoTrack {
  uint32_t id = 0;
  uint32_t timescale = 0;
  uint32_t default_sample_duration = 0;
};

struct Fragment {
  int64_t offset = 0;          // where the `moof` starts
  int64_t size = 0;            // `moof` + `mdat`
  int64_t tfdt = 0;            // the video track's baseMediaDecodeTime
  int64_t trun_duration = 0;   // its summed sample durations
  int64_t first_cts = 0;       // composition offset of the first sample
};

// The video track's id, timescale and `trex` default sample duration.
bool read_video_track(int fd, int64_t file_size, VideoTrack *track);

// Where the media ends: the `mfra` offset when there is one, else file_size.
int64_t media_end(int fd, int64_t file_size);

// Every `moof`+`mdat` pair in [from, to). Fails unless the range is nothing
// but whole fragments, which is what makes a complete index possible.
bool scan_fragments(int fd, int64_t from, int64_t to, const VideoTrack &track,
                    std::vector<Fragment> *fragments);

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
