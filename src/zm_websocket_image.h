/*
 * This file is part of the ZoneMinder Project. See AUTHORS file for Copyright information
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation; either version 2 of the License, or (at your
 * option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program. If not, see <http://www.gnu.org/licenses/>.
 */

#ifndef ZM_WEBSOCKET_IMAGE_H
#define ZM_WEBSOCKET_IMAGE_H

#include "zm_image.h"

#include <atomic>
#include <cstdint>
#include <mutex>

namespace zm {
namespace websocket {

// The decoder's latest processed frame, copied for the WebSocket thread while a
// client has asked for images recently. Analyse() frees the images of
// non-keyframe packets when no event is open, so the packet queue alone would
// only offer the last keyframe's image (refs #2875).
//
// Frames carry the privacy mask they were made with. After the mask changes,
// the held frame and the images in the packet queue were masked with the old
// one, so none is handed out until the decoder publishes a frame masked with
// the new one.
class LatestImage {
 public:
  // The current mask's generation. The decoder reads it before it applies the
  // mask, and passes it to Publish().
  uint32_t MaskGeneration() const { return mask_generation_.load(); }
  // Called once the privacy mask has been rebuilt.
  void MaskChanged();

  // Keeps a copy of image, unless it was masked with an older mask.
  void Publish(const Image &image, uint64_t sequence, uint32_t mask_generation);

  // kTaken: *image and *sequence are the held frame. kNone: nothing is held,
  // and the caller may use the packet queue. kWaitForMasked: nothing may be
  // sent until the decoder publishes a frame with the current mask.
  enum class Result { kTaken, kNone, kWaitForMasked };
  // stale: nobody has asked for images for a while, so the decoder stopped
  // publishing and what is held is old.
  Result Take(Image *image, uint64_t *sequence, bool stale);

 private:
  std::mutex mutex_;
  Image image_;
  uint64_t sequence_ = 0;
  bool valid_ = false;
  bool wait_for_masked_ = false;
  std::atomic<uint32_t> mask_generation_{0};
};

}  // namespace websocket
}  // namespace zm

#endif  // ZM_WEBSOCKET_IMAGE_H
