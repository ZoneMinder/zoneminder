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

#include "zm_monitor.h"

namespace {

using zm::ai::Detection;
using zm::ai::DetectStatus;

Detection Det(const std::string &label, float confidence) {
  Detection d;
  d.label = label;
  d.confidence = confidence;
  d.x = 4;
  d.y = 4;
  d.w = 20;
  d.h = 16;
  return d;
}

// Hands back canned results, one per Detect() call.
class FakeDetector : public zm::ai::Detector {
 public:
  const char *Name() const override { return "fake"; }
  bool Setup(const zm::ai::DetectorConfig &) override { return true; }
  DetectStatus Detect(ZMPacket &, std::vector<Detection> &out) override {
    calls++;
    if (status != DetectStatus::Done) return status;
    out.clear();
    if (!results.empty()) {
      out = results.front();
      results.erase(results.begin());
    }
    return DetectStatus::Done;
  }
  std::vector<std::vector<Detection>> results;
  DetectStatus status = DetectStatus::Done;
  int calls = 0;
};

class DetectingMonitor : public Monitor {
 public:
  DetectingMonitor() : Monitor() { id = 3; }
  FakeDetector *UseFake(zm::ai::ClassSettings classes = {}) {
    ai_config_changed_ = false;
    ai_active_.backend = "fake";
    ai_active_.object_threshold = 0.4f;
    ai_active_.classes = std::move(classes);
    auto fake = std::make_unique<FakeDetector>();
    FakeDetector *raw = fake.get();
    detector_ = std::move(fake);
    return raw;
  }
  void SetSkip(int skip) { motion_frame_skip = skip; }
  void SetFrame(int count) { analysis_image_count = count; }
  bool HasDetector() const { return detector_ != nullptr; }
  void TurnOff() {
    std::lock_guard<std::mutex> lock(ai_config_mutex_);
    ai_config_ = AIConfig();
    ai_config_changed_ = true;
  }
};

std::shared_ptr<ZMPacket> TestFrame(double age_seconds = 0) {
  auto packet = std::make_shared<ZMPacket>();
  packet->image = new Image(64, 48, ZM_COLOUR_RGB24, ZM_SUBPIX_ORDER_RGB);
  packet->image->Clear();
  packet->timestamp = std::chrono::system_clock::now()
      - std::chrono::duration_cast<SystemTimePoint::duration>(FPSeconds(age_seconds));
  return packet;
}

}  // namespace

TEST_CASE("Monitor::DetectObjects scores, records and draws reported detections", "[Monitor][detector]") {
  config.font_file_location = "data/fonts/04_valid.zmfnt";
  DetectingMonitor monitor;
  zm::ai::ClassSetting car;
  car.report = false;
  car.confidence_threshold = 10;
  FakeDetector *fake = monitor.UseFake(zm::ai::ClassSettings{{"car", car}});

  SECTION("a reported class raises the score and names the cause") {
    fake->results = {{Det("person", 0.9f), Det("car", 0.95f)}};
    auto packet = TestFrame();
    std::string cause;
    Event::StringSetMap notes;
    CHECK(monitor.DetectObjects(packet, cause, notes) == 90);
    CHECK(cause == "Object:person");
    CHECK(notes[OBJECT_CAUSE] == Event::StringSet{"person"});
    REQUIRE(packet->analysis_image != nullptr);
#if ZM_HAS_NLOHMANN_JSON
    CHECK(packet->detections["detections"].size() == 2);  // both are recorded
#endif
  }

  SECTION("a class that doesn't report is drawn and recorded without alarming") {
    fake->results = {{Det("car", 0.95f)}};
    auto packet = TestFrame();
    std::string cause;
    Event::StringSetMap notes;
    CHECK(monitor.DetectObjects(packet, cause, notes) == 0);
    CHECK(cause.empty());
    CHECK(notes.empty());
    CHECK(packet->analysis_image != nullptr);
  }

  SECTION("detections under the object threshold are dropped") {
    fake->results = {{Det("person", 0.2f)}};
    auto packet = TestFrame();
    std::string cause;
    Event::StringSetMap notes;
    CHECK(monitor.DetectObjects(packet, cause, notes) == 0);
    CHECK(packet->analysis_image == nullptr);
  }
}

TEST_CASE("Monitor::DetectObjects redraws between inferences until one finds nothing", "[Monitor][detector]") {
  config.font_file_location = "data/fonts/04_valid.zmfnt";
  DetectingMonitor monitor;
  FakeDetector *fake = monitor.UseFake();
  monitor.SetSkip(2);  // infer on every third frame
  fake->results = {{Det("person", 0.9f)}, {}};
  std::string cause;
  Event::StringSetMap notes;

  monitor.SetFrame(0);
  auto first = TestFrame();
  CHECK(monitor.DetectObjects(first, cause, notes) == 90);

  monitor.SetFrame(1);  // skipped: the last boxes are redrawn, but not scored again
  auto between = TestFrame();
  CHECK(monitor.DetectObjects(between, cause, notes) == 0);
  CHECK(between->analysis_image != nullptr);
  CHECK(fake->calls == 1);

  monitor.SetFrame(3);  // inference finds nothing
  auto empty = TestFrame();
  CHECK(monitor.DetectObjects(empty, cause, notes) == 0);
  monitor.SetFrame(4);  // so nothing is redrawn after it
  auto after = TestFrame();
  CHECK(monitor.DetectObjects(after, cause, notes) == 0);
  CHECK(after->analysis_image == nullptr);
}

TEST_CASE("Monitor::DetectObjects skips inference while behind and drops a failed backend", "[Monitor][detector]") {
  DetectingMonitor monitor;
  FakeDetector *fake = monitor.UseFake();
  std::string cause;
  Event::StringSetMap notes;

  SECTION("a frame more than 2s old isn't sent") {
    auto old_frame = TestFrame(5.0);
    CHECK(monitor.DetectObjects(old_frame, cause, notes) == 0);
    CHECK(fake->calls == 0);
  }

  SECTION("a failed backend is dropped, to be set up again later") {
    fake->status = DetectStatus::Failed;
    auto packet = TestFrame();
    CHECK(monitor.DetectObjects(packet, cause, notes) == 0);
    CHECK_FALSE(monitor.HasDetector());
  }

  SECTION("turning detection off in the settings drops the backend") {
    monitor.TurnOff();
    auto packet = TestFrame();
    CHECK(monitor.DetectObjects(packet, cause, notes) == 0);
    CHECK_FALSE(monitor.HasDetector());
  }
}
