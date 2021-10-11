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

#include <algorithm>
#include <assert.h>
#include <memory>
#include <stdlib.h>

#include "zm_config.h"
#include "zm_image.h"
#include "zm_monitor.h"
#include "zm_time.h"
#include "zm_utils.h"
#include "zm_zone.h"


//
// This allows you to feed in a set of columns and timing rows, and print it
// out in a nice-looking table.
//
class TimingsTable {
public:
  TimingsTable(const std::vector<std::string> &inColumns) : columns(inColumns) {}

  void AddRow(const std::string &label, const std::vector<Microseconds> &timings) {
    assert(timings.size() == columns.size());
    Row row;
    row.label = label;
    row.timings = timings;
    rows.push_back(row);
  }

  void Print(const int columnPad = 5) {
    // Figure out column widths.
    std::vector<int> widths(columns.size() + 1);

    // The first width is the max of the row labels.
    auto result = std::max_element(rows.begin(), rows.end(), [](const Row &a, const Row &b) -> bool { return a.label.length() < b.label.length(); });
    widths[0] = result->label.length() + columnPad;

    // Calculate the rest of the column widths.
    for (size_t i=0; i < columns.size(); i++)
      widths[i+1] = columns[i].length() + columnPad;

    auto PrintColStr = [&](size_t icol, const std::string &str) {
      printf("%s", str.c_str());
      PrintPadding(widths[icol] - str.length());
    };

    // Print the header.
    PrintColStr(0, "");
    for (size_t i=0; i < columns.size(); i++) {
      PrintColStr(i+1, columns[i]);
    }
    printf("\n");

    // Print the timings rows.
    for (const auto &row : rows) {
      PrintColStr(0, row.label);

      for (size_t i=0; i < row.timings.size(); i++) {
        char num[128];
        sprintf(num, "%.2f", row.timings[i].count() / 1000.0);
        PrintColStr(i+1, num);
      }

      printf("\n");
    }
  }

private:
  void PrintPadding(int count) {
    std::string str(count, ' ');
    printf("%s", str.c_str());
  }

  class Row {
  public:
    std::string label;
    std::vector<Microseconds> timings;
  };

  std::vector<std::string> columns;
  std::vector<Row> rows;
};



//
// Generate a greyscale image that simulates a delta that can be fed to
// Zone::CheckAlarms. This first creates a black image, and then it fills
// a box of a certain size inside the image with random data. This is to simulate
// a typical scene where most of the scene doesn't change except a specific region.
// 
// Args:
//  changeBoxPercent: 0-100 value telling how large the box with random data should be.
//    Set to 0 to leave the whole thing black.
//  width: The width of the new image.
//  height: The height of the new image.
//   
//  Return:
//    An image with all pixels initialized to values in the [minVal,maxVal] range.
//
std::shared_ptr<Image> GenerateRandomImage(
    const int changeBoxPercent,
    const int width = 3840,
    const int height = 2160) {
  // Create the image.
  Image *image = new Image(
    width,
    height,
    ZM_COLOUR_GRAY8,
    ZM_SUBPIX_ORDER_NONE);

  // Set it to black initially.
  memset((void*)image->Buffer(0, 0), 0, image->LineSize() * image->Height());

  // Now randomize the pixels inside a box.
  const int boxWidth = (width * changeBoxPercent) / 100;
  const int boxHeight = (height * changeBoxPercent) / 100;
  const int boxX = (int)((uint64_t)rand() * (width - boxWidth) / RAND_MAX);
  const int boxY = (int)((uint64_t)rand() * (height - boxHeight) / RAND_MAX);

  for (int y=0; y < boxHeight; y++)
  {
    uint8_t *row = (uint8_t*)image->Buffer(boxX, boxY + y);
    for (int x=0; x < boxWidth; x++) {
      row[x] = (uint8_t)rand();
    }
  }

  return std::shared_ptr<Image>(image);
}


//
// This is used to help rig up Monitor benchmarks.
//
class TestMonitor : public Monitor
{
public:
  TestMonitor(int width, int height) {
    cur_zone_id = 111;

    this->width = width;
    this->height = height;

    // Create a dummy ref_image.
    std::shared_ptr<Image> tempImage = GenerateRandomImage(0, width, height);
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
      const Microseconds in_timer,
      const std::string &in_label) :
    timer(in_timer),
    label(in_label)
  {
  }

  const Microseconds timer;
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
    printf("%s: %liµs\n", counter.label.c_str(), counter.timer.count());
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
//  p_filter_box: The size of the filter to use for alarm detection.
//  
// Return:
//  The average time taken for each DetectMotion call.
//
Microseconds RunDetectMotionBenchmark(
    const std::string &label,
    std::shared_ptr<Image> image,
    const Vector2 &p_filter_box) {
  // Create a monitor to use for the benchmark. Give it 1 zone that uses
  // a 5x5 filter.
  TestMonitor testMonitor(image->Width(), image->Height());
  testMonitor.AddZone(Zone::CheckMethod::FILTERED_PIXELS, p_filter_box);

  // Generate a black image to use as the reference image.
  std::shared_ptr<Image> blackImage = GenerateRandomImage(
    0, image->Width(), image->Height());
  testMonitor.SetRefImage(blackImage.get());

  Microseconds totalTimeTaken(0);

  // Run a series of passes over DetectMotion.
  const int numPasses = 10;
  for (int i=0; i < numPasses; i++) 
  {
    printf("\r%s - pass %2d / %2d   ", label.c_str(), i+1, numPasses);
    fflush(stdout);

    TimeSegmentAdder adder(totalTimeTaken);

    Event::StringSet zoneSet;
    testMonitor.DetectMotion(*image.get(), zoneSet);
  }
  printf("\n");

  return totalTimeTaken / numPasses;
}


void RunDetectMotionBenchmarks(
    TimingsTable &table,
    const std::vector<int> &deltaBoxPercents,
    const Vector2 &p_filter_box) {
  std::vector<Microseconds> timings;

  for (int percent : deltaBoxPercents) {
    Microseconds timing = RunDetectMotionBenchmark(
      std::string("DetectMotion: ") + std::to_string(p_filter_box.x_) + "x" + std::to_string(p_filter_box.y_) + " box, " + std::to_string(percent) + "% delta",
      GenerateRandomImage(percent),
      p_filter_box);
    timings.push_back(timing);
  }

  table.AddRow(
    std::to_string(p_filter_box.x_) + "x" + std::to_string(p_filter_box.y_) + " filter",
    timings);
}


int main(int argc, char *argv[]) {
  srand(111);

  // Init global stuff that we need.
  config.font_file_location = "../fonts/default.zmfnt";
  config.event_close_mode = "time";
  config.cpu_extensions = 1;

  // Detect SSE version.
  HwCapsDetect();
 
  // Setup the column titles for the TimingsTable we'll generate.
  // Each column represents how large the box in the image is with delta pixels.
  // Each row represents a different filter size.
  const std::vector<int> percents = {0, 10, 50, 100};
  std::vector<std::string> columns(percents.size());
  std::transform(percents.begin(), percents.end(), columns.begin(),
    [](const int percent) {return std::to_string(percent) + "% delta (ms)";});
  TimingsTable table(columns);

  std::vector<Vector2> filterSizes = {Vector2(3,3), Vector2(5,5), Vector2(13,13)};
  for (const auto filterSize : filterSizes) {
    RunDetectMotionBenchmarks(table, percents, filterSize);
  }

  table.Print();
  return 0;
}

