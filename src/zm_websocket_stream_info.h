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
class VideoStreamInfo {
 public:
  struct Snapshot {
    AVCodecID codec_id = AV_CODEC_ID_NONE;
    uint32_t width = 0;
    uint32_t height = 0;
    std::string extradata;  // owned copy of codec extradata (SPS/PPS etc.)
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
  // after the lock is released. Returns false when no stream is published or
  // the published codec differs.
  bool Get(AVCodecID requested_codec, Snapshot *out) const;

  // True when a valid stream is currently published, whatever its codec.
  bool Valid() const;

 private:
  mutable std::mutex mutex_;
  bool valid_ = false;
  Snapshot snapshot_;
};

}  // namespace websocket
}  // namespace zm

#endif  // ZM_WEBSOCKET_STREAM_INFO_H
