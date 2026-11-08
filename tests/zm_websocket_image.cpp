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


#include "zm_catch2.h"
#include "zm_websocket_image.h"

using Result = zm::websocket::LatestImage::Result;

TEST_CASE("The latest websocket image is held until it goes stale") {
  zm::websocket::LatestImage latest;
  Image frame(8, 8, 1, ZM_SUBPIX_ORDER_NONE);
  Image out;
  uint64_t sequence = 0;
  // Nothing published yet: the caller falls back to the packet queue.
  REQUIRE(latest.Take(&out, &sequence, false) == Result::kNone);

  latest.Publish(frame, 7, latest.MaskGeneration());
  REQUIRE(latest.Take(&out, &sequence, false) == Result::kTaken);
  REQUIRE(sequence == 7);
  REQUIRE(out.Width() == 8);
  // Nobody asked for a while, so the decoder stopped publishing.
  REQUIRE(latest.Take(&out, &sequence, true) == Result::kNone);
}

TEST_CASE("A privacy mask change holds back images masked with the old one") {
  zm::websocket::LatestImage latest;
  Image frame(8, 8, 1, ZM_SUBPIX_ORDER_NONE);
  Image out;
  uint64_t sequence = 0;
  latest.Publish(frame, 1, latest.MaskGeneration());

  // The decoder read the generation, then Privacy zones were added.
  const uint32_t old_mask = latest.MaskGeneration();
  latest.MaskChanged();
  // The held image, and the packet queue's, were masked with the old mask.
  REQUIRE(latest.Take(&out, &sequence, false) == Result::kWaitForMasked);
  // A frame the decoder masked before the change.
  latest.Publish(frame, 2, old_mask);
  REQUIRE(latest.Take(&out, &sequence, false) == Result::kWaitForMasked);
  // The wait also holds for a request after a quiet spell, which would
  // otherwise read the packet queue.
  REQUIRE(latest.Take(&out, &sequence, true) == Result::kWaitForMasked);
  // A frame masked with the new mask.
  latest.Publish(frame, 3, latest.MaskGeneration());
  REQUIRE(latest.Take(&out, &sequence, false) == Result::kTaken);
  REQUIRE(sequence == 3);
  // Once a masked frame has been published the queue's images are newer than
  // the change, so falling back to them is fine again.
  REQUIRE(latest.Take(&out, &sequence, true) == Result::kNone);
}
