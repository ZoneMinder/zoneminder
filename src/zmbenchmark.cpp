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

#include "zm_config.h"
#include "zm_image.h"
#include "zm_monitor.h"
#include "zm_utils.h"
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
std::shared_ptr<Image> GenerateRandomImage(
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

  return std::shared_ptr<Image>(image);
}


//
// This is used to help rig up tests of Monitor.
//
class TestMonitor : public Monitor
{
public:
  TestMonitor(int width, int height) {
    cur_zone_id = 111;

    this->width = width;
    this->height = height;

    // Create a dummy ref_image.
    std::shared_ptr<Image> tempImage = GenerateRandomImage(0, 0, width, height);
    ref_image = *tempImage.get();

    shared_data = &temp_shared_data;
  }

  //
  // Add a new zone to this monitor.
  // 
  // Args:
  //  checkMethod: This controls how this zone will actually do motion detection.
  //  
  //  p_filter_box: The size of the filter to use.
  //
  void AddZone(Zone::CheckMethod checkMethod, const Vector2 &p_filter_box=Vector2(5,5)) {
      const int p_min_pixel_threshold = 50;
      const int p_max_pixel_threshold = 255;
      const int p_min_alarm_pixels = 1000;
      const int p_max_alarm_pixels = 10000000;

      const int zone_id = cur_zone_id++;
      const std::string zone_label = std::string("zone_") + std::to_string(zone_id);
      const Zone::ZoneType zoneType = Zone::ZoneType::ACTIVE;
      const Polygon poly({
        Vector2(0, 0),
        Vector2(width-1, 0),
        Vector2(width-1, height-1),
        Vector2(0, height-1)});

      Zone zone(
        this,
        zone_id,
        zone_label.c_str(),
        zoneType,
        poly,
        kRGBGreen,
        Zone::CheckMethod::FILTERED_PIXELS,
        p_min_pixel_threshold,
        p_max_pixel_threshold,
        p_min_alarm_pixels,
        p_max_alarm_pixels,
        p_filter_box
        );
      zones.push_back(zone);
  }

  void SetRefImage(const Image *image) {
    ref_image = *image;
  }

private:
  SharedData temp_shared_data;
  int cur_zone_id;
};



class CounterInfo {
public:
  CounterInfo(
      const std::chrono::microseconds &in_timer,
      const std::string &in_label) :
    timer(in_timer),
    label(in_label)
  {
  }

  const std::chrono::microseconds timer;
  const std::string label;  
};

//
// Print out a table of timing results.
// 
// Args:
//  counters: The list of counters to print out, and info about how to format it.
//
void PrintCounters(std::vector<CounterInfo> counters) {
  for (const auto counter : counters) {
    printf("%s: %lims\n", counter.label.c_str(), counter.timer.count());
  }
}

//
// Run zone benchmarks on the given image.
// 
// Args:
//  label: A label to be printed before the output.
//  
//  image: The image to run the tests on.
//
void RunZoneBenchmark(const char *label, std::shared_ptr<Image> image) {
  // Create a monitor to use for the benchmark. Give it 1 zone that uses
  // a 5x5 filter.
  TestMonitor testMonitor(image->Width(), image->Height());
  testMonitor.AddZone(Zone::CheckMethod::FILTERED_PIXELS, Vector2(5,5));

  // Generate a black image to use as the reference image.
  std::shared_ptr<Image> blackImage = GenerateRandomImage(
    0, 0, image->Width(), image->Height());
  testMonitor.SetRefImage(blackImage.get());

  std::chrono::microseconds totalTimeTaken(0);

  // Run a series of passes over DetectMotion.
  const int numPasses = 10;
  for (int i=0; i < numPasses; i++) 
  {
    printf("\r(%d / %d)   ", i+1, numPasses);
    fflush(stdout);

    Event::StringSet zoneSet;
    testMonitor.DetectMotion(*image.get(), zoneSet);
  }

  printf("\n");
  printf("------- %s -------\n", label);
  PrintCounters({
    CounterInfo(totalTimeTaken, "Total zone benchmark time")});
}


int main(int argc, char *argv[]) {
  srand(111);

  // Init global stuff that we need.
  config.font_file_location = "../fonts/default.zmfnt";
  config.event_close_mode = "time";
  config.cpu_extensions = 1;

  // Detect SSE version.
  HwCapsDetect();

  RunZoneBenchmark("0%% delta", GenerateRandomImage(0, 0));
  RunZoneBenchmark("50%% delta", GenerateRandomImage(0, 255));
  RunZoneBenchmark("100%% delta", GenerateRandomImage(255, 255));

  return 0;
}

