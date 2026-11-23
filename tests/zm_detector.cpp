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

#include "zm_detector.h"
#include "zm_image.h"

using zm::ai::ClassSetting;
using zm::ai::ClassSettings;
using zm::ai::Detection;

namespace {

Detection Det(const std::string &label, float confidence, float x = 1, float y = 1, float w = 4, float h = 4) {
  Detection d;
  d.label = label;
  d.confidence = confidence;
  d.x = x;
  d.y = y;
  d.w = w;
  d.h = h;
  return d;
}

ClassSetting Setting(bool enabled, int threshold, bool report = true) {
  ClassSetting s;
  s.enabled = enabled;
  s.confidence_threshold = threshold;
  s.report = report;
  return s;
}

}  // namespace

TEST_CASE("ParseBoxColor reads #RRGGBB into ZoneMinder's colour order", "[detector]") {
  CHECK(zm::ai::ParseBoxColor("#FF0000") == kRGBRed);
  CHECK(zm::ai::ParseBoxColor("#00ff00") == kRGBGreen);
  CHECK(zm::ai::ParseBoxColor("0000FF") == kRGBBlue);
  CHECK(zm::ai::ParseBoxColor("") == kRGBRed);
  CHECK(zm::ai::ParseBoxColor("#12") == kRGBRed);
  CHECK(zm::ai::ParseBoxColor("#GGGGGG") == kRGBRed);
}

TEST_CASE("FilterDetections applies per-class settings", "[detector]") {
  ClassSettings settings;
  settings["person"] = Setting(true, 60);
  settings["cat"] = Setting(false, 10);
  const std::vector<Detection> found = {
    Det("person", 0.7f), Det("person", 0.5f), Det("cat", 0.99f), Det("car", 0.45f), Det("car", 0.35f)};

  // Classes without a setting use the monitor's object threshold.
  const std::vector<Detection> kept = zm::ai::FilterDetections(found, settings, 0.4f);
  REQUIRE(kept.size() == 2);
  CHECK(kept[0].label == "person");
  CHECK(kept[0].confidence == 0.7f);
  CHECK(kept[1].label == "car");
  CHECK(kept[1].confidence == 0.45f);

  SECTION("a threshold is inclusive") {
    CHECK(zm::ai::FilterDetections({Det("person", 0.6f)}, settings, 0.4f).size() == 1);
  }
  SECTION("no settings keeps everything over the object threshold") {
    CHECK(zm::ai::FilterDetections(found, {}, 0.4f).size() == 4);
  }
}

TEST_CASE("Only reported classes alarm, scored by the most confident", "[detector]") {
  ClassSettings settings;
  settings["car"] = Setting(true, 10, false);
  const std::vector<Detection> found = {Det("car", 0.95f), Det("person", 0.62f), Det("dog", 0.8f), Det("person", 0.9f)};

  CHECK(zm::ai::AlarmScore(found, settings) == 90);
  CHECK(zm::ai::AlarmCause(found, settings) == "person, dog");
  CHECK(zm::ai::AlarmScore({Det("car", 0.95f)}, settings) == 0);
  CHECK(zm::ai::AlarmCause({Det("car", 0.95f)}, settings).empty());
  CHECK(zm::ai::AlarmScore({}, settings) == 0);
}

TEST_CASE("InferenceBehind falls behind at the threshold and recovers under half of it", "[detector]") {
  CHECK_FALSE(zm::ai::InferenceBehind(false, 1.9));
  CHECK(zm::ai::InferenceBehind(false, 2.1));
  CHECK(zm::ai::InferenceBehind(true, 1.5));   // still above the 1s recovery point
  CHECK_FALSE(zm::ai::InferenceBehind(true, 0.9));
}

TEST_CASE("DrawDetections outlines each detection in its class colour", "[detector]") {
  config.font_file_location = "data/fonts/04_valid.zmfnt";
  Image image(32, 24, ZM_COLOUR_RGB24, ZM_SUBPIX_ORDER_RGB);
  image.Clear();
  ClassSettings settings;
  ClassSetting blue = Setting(true, 50);
  blue.box_color = kRGBBlue;
  settings["car"] = blue;

  zm::ai::DrawDetections(image, {Det("person", 0.9f, 2, 2, 10, 8), Det("car", 0.9f, 16, 12, 10, 8)}, settings, 1);
  CHECK(image.Buffer(2, 6)[0] == 0xff);    // person: default red, left edge
  CHECK(image.Buffer(16, 16)[2] == 0xff);  // car: blue, left edge
  CHECK(image.Buffer(16, 16)[0] == 0);
}

#if ZM_HAS_NLOHMANN_JSON
// The stored document is the detection event zm-next publishes, so zm-api can
// read detections from zmc and zm-next the same way.
TEST_CASE("DetectionsToJson writes zm-next's detection event", "[detector]") {
  Detection d = Det("person", 0.9f, 10, 20, 30, 40);
  d.class_id = 0;
  const nlohmann::json doc = zm::ai::DetectionsToJson({d}, 1234567);
  CHECK(doc["type"] == "detection");
  CHECK(doc["pts_usec"] == 1234567);
  REQUIRE(doc["detections"].size() == 1);
  const nlohmann::json &j = doc["detections"][0];
  CHECK(j["label"] == "person");
  CHECK(j["confidence"].get<float>() == 0.9f);
  CHECK(j["bbox"] == nlohmann::json::array({10.0, 20.0, 30.0, 40.0}));
  CHECK(j["class_id"] == 0);
}

TEST_CASE("DetectionsFromJson reads a backend's response", "[detector]") {
  SECTION("zm-next's own shape, scaled back to the frame") {
    const auto doc = nlohmann::json::parse(
        R"({"detections":[{"label":"person","confidence":0.8,"bbox":[10,20,30,40],"class_id":0}]})");
    const std::vector<Detection> found = zm::ai::DetectionsFromJson(doc, 2.0f, 0.5f);
    REQUIRE(found.size() == 1);
    CHECK(found[0].label == "person");
    CHECK(found[0].class_id == 0);
    CHECK(found[0].x == 20);
    CHECK(found[0].y == 10);
    CHECK(found[0].w == 60);
    CHECK(found[0].h == 20);
  }
  SECTION("ai_server's remote API shape: predictions with class, score and corner boxes") {
    const auto doc = nlohmann::json::parse(
        R"({"predictions":[{"class":"car","score":0.7,"box":[5,6,15,26]}]})");
    const std::vector<Detection> found = zm::ai::DetectionsFromJson(doc, 1.0f, 1.0f);
    REQUIRE(found.size() == 1);
    CHECK(found[0].label == "car");
    CHECK(found[0].confidence == 0.7f);
    CHECK(found[0].x == 5);
    CHECK(found[0].w == 10);
    CHECK(found[0].h == 20);
  }
  SECTION("malformed entries are skipped, not thrown") {
    const auto doc = nlohmann::json::parse(
        R"({"detections":[{"label":"person"},{"confidence":"x","bbox":[1,2,3,4]},"junk",
                          {"label":"dog","confidence":0.5,"bbox":[1,2,3,4]}]})");
    const std::vector<Detection> found = zm::ai::DetectionsFromJson(doc, 1.0f, 1.0f);
    REQUIRE(found.size() == 1);
    CHECK(found[0].label == "dog");
  }
  SECTION("a document without detections gives none") {
    CHECK(zm::ai::DetectionsFromJson(nlohmann::json::parse("{}"), 1, 1).empty());
    CHECK(zm::ai::DetectionsFromJson(nlohmann::json::parse("[1,2]"), 1, 1).empty());
  }
}
#endif

TEST_CASE("MakeDetector knows the compiled-in backends", "[detector]") {
  CHECK(zm::ai::MakeDetector("none") == nullptr);
  CHECK(zm::ai::MakeDetector("no-such-backend") == nullptr);
  for (const std::string &name : zm::ai::DetectorNames()) {
    std::unique_ptr<zm::ai::Detector> detector = zm::ai::MakeDetector(name);
    REQUIRE(detector != nullptr);
    CHECK(name == detector->Name());
  }
}
