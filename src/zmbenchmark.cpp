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
#include <cassert>
#include <cstdlib>
#include <memory>
#include <random>
#include <utility>

#include "zm_config.h"
#include "zm_image.h"
#include "zm_monitor.h"
#include "zm_time.h"
#include "zm_utils.h"
#include "zm_zone.h"

static std::mt19937 mt_rand(111);

//
// This allows you to feed in a set of columns and timing rows, and print it
// out in a nice-looking table.
//
class TimingsTable {
 public:
  explicit TimingsTable(std::vector<std::string> in_columns) : columns_(std::move(in_columns)) {}

  //
  // Add a row to the end of the table.
  //
  // Args:
  //  label: The name of the row (printed in the first column).
  //  timings: The values for all the other columns in this row.
  void AddRow(const std::string &label, const std::vector<Microseconds> &timings) {
    assert(timings.size() == columns_.size());
    Row row;
    row.label = label;
    row.timings = timings;
    rows_.push_back(row);
  }

  //
  // Print out the table.
  //
  // Args:
  //  columnPad: # characters between table columns
  //
  void Print(const int column_pad = 5) {
    // Figure out column widths.
    std::vector<size_t> widths(columns_.size() + 1);

    // The first width is the max of the row labels.
    auto result = std::max_element(rows_.begin(),
                                   rows_.end(),
    [](const Row &a, const Row &b) -> bool {
      return a.label.length() < b.label.length();
    });
    widths[0] = result->label.length() + column_pad;

    // Calculate the rest of the column widths.
    for (size_t i = 0 ; i < columns_.size() ; i++)
      widths[i + 1] = columns_[i].length() + column_pad;

    auto PrintColStr = [&](size_t icol, const std::string &str) {
      printf("%s", str.c_str());
      PrintPadding(widths[icol] - str.length());
    };

    // Print the header.
    PrintColStr(0, "");
    for (size_t i = 0 ; i < columns_.size() ; i++) {
      PrintColStr(i + 1, columns_[i]);
    }
    printf("\n");

    // Print the timings rows.
    for (const Row &row : rows_) {
      PrintColStr(0, row.label);

      for (size_t i = 0 ; i < row.timings.size() ; i++) {
        std::string num = stringtf("%.2f", std::chrono::duration_cast<FPSeconds>(row.timings[i]).count());
        PrintColStr(i + 1, num);
      }

      printf("\n");
    }
  }

 private:
  static void PrintPadding(size_t count) {
    std::string str(count, ' ');
    printf("%s", str.c_str());
  }

  struct Row {
    std::string label;
    std::vector<Microseconds> timings;
  };

  std::vector<std::string> columns_;
  std::vector<Row> rows_;
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
  const int change_box_percent,
  const int width = 3840,
  const int height = 2160) {
  // Create the image.
  Image *image = new Image(width, height, ZM_COLOUR_GRAY8, ZM_SUBPIX_ORDER_NONE);

  // Set it to black initially.
  memset(image->Buffer(0, 0), 0, (size_t) image->LineSize() * (size_t) image->Height());

  // Now randomize the pixels inside a box.
  const int box_width = (width * change_box_percent) / 100;
  const int box_height = (height * change_box_percent) / 100;
  const int box_x = (int) ((uint64_t) mt_rand() * (width - box_width) / RAND_MAX);
  const int box_y = (int) ((uint64_t) mt_rand() * (height - box_height) / RAND_MAX);

  for (int y = 0 ; y < box_height ; y++) {
    uint8_t *row = image->Buffer(box_x, box_y + y);
    for (int x = 0 ; x < box_width ; x++) {
      row[x] = (uint8_t) mt_rand();
    }
  }

  return std::shared_ptr<Image>(image);
}

//
// This is used to help rig up Monitor benchmarks.
//
class TestMonitor : public Monitor {
 public:
  TestMonitor(int width, int height) : cur_zone_id(111) {
    this->width = width;
    this->height = height;

    // Create a dummy ref_image.
    std::shared_ptr<Image> tempImage = GenerateRandomImage(0, width, height);
    ref_image = *tempImage;

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
  void AddZone(Zone::CheckMethod checkMethod, const Vector2 &p_filter_box = Vector2(5, 5)) {
    const int p_min_pixel_threshold = 50;
    const int p_max_pixel_threshold = 255;
    const int p_min_alarm_pixels = 1000;
    const int p_max_alarm_pixels = 10000000;

    const int zone_id = cur_zone_id++;
    const std::string zone_label = std::string("zone_") + std::to_string(zone_id);
    const Zone::ZoneType zone_type = Zone::ZoneType::ACTIVE;
    const Polygon poly({Vector2(0, 0),
                        Vector2(width - 1, 0),
                        Vector2(width - 1, height - 1),
                        Vector2(0, height - 1)});

    Zone zone(shared_from_this(),
              zone_id,
              zone_label.c_str(),
              zone_type,
              poly,
              kRGBGreen,
              Zone::CheckMethod::FILTERED_PIXELS,
              p_min_pixel_threshold,
              p_max_pixel_threshold,
              p_min_alarm_pixels,
              p_max_alarm_pixels,
              p_filter_box);
    zones.push_back(zone);
  }

  void SetRefImage(const Image *image) {
    ref_image = *image;
  }

 private:
  SharedData temp_shared_data;
  int cur_zone_id;
};

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
Microseconds RunDetectMotionBenchmark(const std::string &label,
                                      const std::shared_ptr<Image>& image,
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
  for (int i = 0 ; i < numPasses ; i++) {
    printf("\r%s - pass %2d / %2d   ", label.c_str(), i + 1, numPasses);
    fflush(stdout);

    TimeSegmentAdder adder(totalTimeTaken);

    Event::StringSet zoneSet;
    testMonitor.DetectMotion(*image, zoneSet);
  }
  printf("\n");

  return totalTimeTaken / numPasses;
}

//
// This runs a set of Monitor::DetectMotion benchmarks, one for each of the
// "delta box percents" that are passed in. This adds one row to the
// TimingsTable specified.
//
// Args:
//  table: The table to add timings into.
//
//  deltaBoxPercents: Each of these defines a box size in the delta images
//    passed to DetectMotion (larger boxes make it slower, sometimes significantly so).
//
//  p_filter_box: Defines the filter size used in DetectMotion.
//
void RunDetectMotionBenchmarks(
  TimingsTable &table,
  const std::vector<int> &delta_box_percents,
  const Vector2 &p_filter_box) {
  std::vector<Microseconds> timings;

  for (int percent : delta_box_percents) {
    Microseconds timing = RunDetectMotionBenchmark(
                            std::string("DetectMotion: ") + std::to_string(p_filter_box.x_) + "x" + std::to_string(p_filter_box.y_)
                            + " box, " + std::to_string(percent) + "% delta",
                            GenerateRandomImage(percent),
                            p_filter_box);
    timings.push_back(timing);
  }

  table.AddRow(
    std::to_string(p_filter_box.x_) + "x" + std::to_string(p_filter_box.y_) + " filter",
    timings);
}

int main(int argc, char *argv[]) {
  // Init global stuff that we need.
  config.font_file_location = "../fonts/default.zmfnt";
  config.event_close_mode = "time";
  config.cpu_extensions = true;

  // Detect SSE version.
  HwCapsDetect();

  // Setup the column titles for the TimingsTable we'll generate.
  // Each column represents how large the box in the image is with delta pixels.
  // Each row represents a different filter size.
  const std::vector<int> percents = {0, 10, 50, 100};
  std::vector<std::string> columns(percents.size());
  std::transform(percents.begin(), percents.end(), columns.begin(),
  [](const int percent) { return std::to_string(percent) + "% delta (ms)"; });
  TimingsTable table(columns);

  std::vector<Vector2> filterSizes = {Vector2(3, 3), Vector2(5, 5), Vector2(13, 13)};
  for (const auto filterSize : filterSizes) {
    RunDetectMotionBenchmarks(table, percents, filterSize);
  }

  table.Print();
  return 0;
}

