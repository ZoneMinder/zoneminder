//
// ZoneMinder Benchmark, $Date$, $Revision$
// Copyright (C) 2001-2008 Philip Coombes
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

#include <memory>
#include <stdlib.h>

#include "zm_image.h"
#include "zm_zone.h"



//
// Generate a greyscale image that simulates a delta that can be fed to
// Zone::CheckAlarms.
// 
// Args:
//  minVal: 0-255 value telling the minimum (random) value to initialize
//    all the pixels to.
//  maxVal: 0-255 value telling the maximum (random) value to initialize
//    all the pixels to.
//  width: The width of the new image.
//  height: The height of the new image.
//   
//  Return:
//    An image with all pixels initialized to values in the [minVal,maxVal] range.
//
std::shared_ptr<Image> GenerateDeltaImage(
    int minVal,
    int maxVal,
    int width = 3840,
    int height = 2160) {
  // Create the image.
  Image *image = new Image(
    width,
    height,
    ZM_COLOUR_GRAY8,
    ZM_SUBPIX_ORDER_NONE);

  const int range = maxVal - minVal + 1;
  for (int y=0; y < height; y++)
  {
    uint8_t *row = (uint8_t*)image->Buffer(0, y);
    for (int x=0; x < width; x++)
      row[x] = (rand() * range) / RAND_MAX + minVal;
  }

  return image;
}


int main(int argc, char *argv[]) {
  srand(111);

  RunZoneBenchmark("0%% delta", GenerateDeltaImage(0, 0));
  RunZoneBenchmark("50%% delta", GenerateDeltaImage(0, 255));
  RunZoneBenchmark("100%% delta", GenerateDeltaImage(255, 255));

  return 0;
}

