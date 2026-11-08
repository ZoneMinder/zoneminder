//
// ZoneMinder per-monitor websocket video stream snapshot
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
//

#ifndef ZM_WEBSOCKET_STREAM_INFO_H
#define ZM_WEBSOCKET_STREAM_INFO_H

#include <cstdint>
#include <mutex>
#include <string>

extern "C" {
#include <libavcodec/codec_id.h>
}

struct AVCodecParameters;

namespace zm {
namespace websocket {

// Thread-safe snapshot of the capture video stream's parameters.
//
// The websocket server runs on its own thread and must describe the encoded
// video stream (codec, dimensions, and the extradata it prepends to keyframes)
// to clients. Reading that off the camera's live AVStream is a use-after-free:
// the capture thread frees the AVStream in camera->Close() on every reconnect,
// and the queued packets' stream back-pointer dangles with it (refs #2875).
//
// Instead the capture thread publishes an owned copy here while the stream is
// valid (Update), and clears it before closing the camera (Invalidate). The
// websocket thread takes a copy (Get) that owns its extradata, so nothing it
// holds points back into the camera. This mirrors how StreamSocket caches its
// stream parameters.
// Converts codec extradata into what a passthrough client is sent before a
// keyframe. An avcC/hvcC record (MP4 sources) becomes its parameter sets, each
// behind a 00 00 00 01 start code, and *nal_length_size is set to the length
// field size its packets use; Annex-B extradata (RTSP sources) is kept as is
// with *nal_length_size 0. For AV1, the av1C header is dropped, leaving the
// configuration OBUs. Returns false when a record is malformed.
bool ExtradataToAnnexB(AVCodecID codec, const std::string &extradata, std::string *out, int *nal_length_size);

// Replaces each nal_length_size-byte length before a NAL unit with a start
// code. Returns false when a length runs past the packet.
bool LengthPrefixedToAnnexB(const uint8_t *data, size_t size, int nal_length_size, std::string *out);

class VideoStreamInfo {
 public:
  struct Snapshot {
    AVCodecID codec_id = AV_CODEC_ID_NONE;
    uint32_t width = 0;
    uint32_t height = 0;
    // What a passthrough client needs before a keyframe: the parameter sets as
    // Annex-B (H.264/H.265) or the configuration OBUs (AV1). Owned.
    std::string extradata;
    // Bytes in the length before each NAL unit in this stream's packets (an MP4
    // source), or 0 when the packets are already Annex-B (RTSP) or AV1.
    int nal_length_size = 0;
    // Generation() when this snapshot was published.
    uint64_t generation = 0;
  };

  // Publishes the parameters of an open video stream, copying extradata so the
  // snapshot owns it. A null codecpar (a camera that produces decoded images
  // has no encoded video stream) publishes an invalid snapshot. The fallbacks
  // are used when codecpar carries no dimensions. Safe to call from the
  // capture thread while that thread still owns a valid camera.
  void Update(const AVCodecParameters *codecpar, uint32_t fallback_width, uint32_t fallback_height);

  // Marks the snapshot invalid. Called before the camera is closed so readers
  // stop at once and Get() fails until a stream is published again.
  void Invalidate();

  // Copies the published stream into *out when one matching requested_codec is
  // available, returning true. The copy owns its extradata and stays valid
  // after the lock is released. Returns false when no stream is published, the
  // published codec differs, or the monitor has Privacy zones.
  bool Get(AVCodecID requested_codec, Snapshot *out) const;

  // Moves on with every Update() and Invalidate(). A reconnect invalidates the
  // snapshot before the packet queue is cleared, so a packet read after a
  // snapshot belongs to that snapshot's stream only if Generation() is still
  // the snapshot's generation once the packet is held.
  uint64_t Generation() const;

  // True when a valid stream is currently published, whatever its codec.
  bool Valid() const;

  // Set while the monitor has Privacy zones. Passthrough sends the camera's own
  // encoded bytes, which the privacy mask applied to decoded images never
  // touched, so Get() fails and Check() is kPrivacyMasked until it is cleared.
  // Survives Invalidate() and Update(): it follows the zones, not the camera.
  void SetPrivacyMasked(bool masked);

  // Whether a passthrough client of requested_codec can be fed: kAvailable when
  // that codec is published, kNoStream while nothing is (the camera is
  // reconnecting, or produces no encoded stream), kOtherCodec when the camera
  // now produces a different codec, which that client cannot decode, and
  // kPrivacyMasked while the monitor has Privacy zones.
  enum class Availability { kAvailable, kNoStream, kOtherCodec, kPrivacyMasked };
  Availability Check(AVCodecID requested_codec) const;

 private:
  mutable std::mutex mutex_;
  bool valid_ = false;
  bool privacy_masked_ = false;
  uint64_t generation_ = 0;
  Snapshot snapshot_;
};

}  // namespace websocket
}  // namespace zm

#endif  // ZM_WEBSOCKET_STREAM_INFO_H
