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
  CHECK(zm::ai::ReportedLabels(found, settings) == std::vector<std::string>{"person", "dog"});
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
  // Labels are drawn inside the top left of each box, in whichever font the
  // test binary loaded first, so check the bottom of the left edges.
  Image image(160, 120, ZM_COLOUR_RGB24, ZM_SUBPIX_ORDER_RGB);
  image.Clear();
  ClassSettings settings;
  ClassSetting blue = Setting(true, 50);
  blue.box_color = kRGBBlue;
  settings["car"] = blue;

  zm::ai::DrawDetections(image, {Det("person", 0.9f, 2, 2, 40, 50), Det("car", 0.9f, 80, 60, 40, 50)}, settings, 1);
  CHECK(image.Buffer(2, 50)[0] == 0xff);    // person: default red
  CHECK(image.Buffer(2, 50)[2] == 0);
  CHECK(image.Buffer(80, 108)[2] == 0xff);  // car: blue
  CHECK(image.Buffer(80, 108)[0] == 0);
  CHECK(image.Buffer(150, 10)[0] == 0);     // nothing drawn outside the boxes
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

TEST_CASE("DetectionLabels gives each class in a stored document once", "[detector]") {
  const nlohmann::json doc = zm::ai::DetectionsToJson(
      {Det("person", 0.9f), Det("dog", 0.5f), Det("person", 0.6f), Det("", 0.6f)}, 0);
  CHECK(zm::ai::DetectionLabels(doc) == std::vector<std::string>{"person", "dog"});
  CHECK(zm::ai::DetectionLabels(nlohmann::json::object()).empty());
  CHECK(zm::ai::DetectionLabels(nlohmann::json::parse(R"({"detections":[{"label":5},"x"]})")).empty());
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

#if HAVE_LIBCURL && ZM_HAS_NLOHMANN_JSON
#include "zm_detector_http.h"
#include "zm_packet.h"

#include <arpa/inet.h>
#include <netinet/in.h>
#include <sys/socket.h>
#include <unistd.h>

#include <algorithm>
#include <thread>

namespace {

// Answers one HTTP request on 127.0.0.1 with a fixed status and body, and
// keeps the request it received.
class StubServer {
 public:
  StubServer(int status, std::string body) : status_(status), body_(std::move(body)) {
    fd_ = socket(AF_INET, SOCK_STREAM, 0);
    sockaddr_in addr = {};
    addr.sin_family = AF_INET;
    addr.sin_addr.s_addr = htonl(INADDR_LOOPBACK);
    addr.sin_port = 0;
    REQUIRE(bind(fd_, reinterpret_cast<sockaddr *>(&addr), sizeof(addr)) == 0);
    REQUIRE(listen(fd_, 1) == 0);
    socklen_t len = sizeof(addr);
    REQUIRE(getsockname(fd_, reinterpret_cast<sockaddr *>(&addr), &len) == 0);
    port_ = ntohs(addr.sin_port);
    thread_ = std::thread([this] { Serve(); });
  }
  ~StubServer() {
    if (thread_.joinable()) thread_.join();
    if (fd_ >= 0) close(fd_);
  }
  std::string Url(const std::string &path = "/detect") const {
    return stringtf("http://127.0.0.1:%d%s", port_, path.c_str());
  }
  const std::string &Request() {
    if (thread_.joinable()) thread_.join();
    return request_;
  }

 private:
  void Serve() {
    int client = accept(fd_, nullptr, nullptr);
    if (client < 0) return;
    char buf[65536];
    size_t content_length = 0;
    size_t header_end = std::string::npos;
    while (true) {
      ssize_t n = read(client, buf, sizeof(buf));
      if (n <= 0) break;
      request_.append(buf, n);
      if (header_end == std::string::npos) {
        header_end = request_.find("\r\n\r\n");
        if (header_end != std::string::npos) {
          std::string lower = request_.substr(0, header_end);
          std::transform(lower.begin(), lower.end(), lower.begin(), ::tolower);
          size_t at = lower.find("content-length:");
          if (at != std::string::npos) content_length = std::stoul(lower.substr(at + 15));
        }
      }
      if (header_end != std::string::npos and request_.size() >= header_end + 4 + content_length) break;
    }
    std::string response = stringtf("HTTP/1.1 %d X\r\nContent-Type: application/json\r\nContent-Length: %zu\r\n"
                                     "Connection: close\r\n\r\n", status_, body_.size()) + body_;
    ssize_t written = write(client, response.data(), response.size());
    (void)written;
    close(client);
  }

  int fd_ = -1;
  int port_ = 0;
  int status_;
  std::string body_;
  std::string request_;
  std::thread thread_;
};

zm::ai::DetectorConfig HttpConfig(const std::string &url) {
  zm::ai::DetectorConfig config;
  config.monitor_id = 7;
  config.model = url;
  config.width = 64;
  config.height = 48;
  return config;
}

std::shared_ptr<ZMPacket> PacketWithImage() {
  auto packet = std::make_shared<ZMPacket>();
  packet->image = new Image(64, 48, ZM_COLOUR_RGB24, ZM_SUBPIX_ORDER_RGB);
  packet->image->Clear();
  return packet;
}

}  // namespace

TEST_CASE("The http backend posts a JPEG and reads detections back", "[detector][http]") {
  StubServer server(200, R"({"detections":[{"label":"person","confidence":0.88,"bbox":[4,5,20,30],"class_id":0}]})");
  zm::ai::HttpDetector detector;
  REQUIRE(detector.Setup(HttpConfig(server.Url())));

  auto packet = PacketWithImage();
  std::vector<zm::ai::Detection> found;
  REQUIRE(detector.Detect(*packet, found) == zm::ai::DetectStatus::Done);
  REQUIRE(found.size() == 1);
  CHECK(found[0].label == "person");
  CHECK(found[0].w == 20);

  const std::string &request = server.Request();
  CHECK(request.rfind("POST /detect", 0) == 0);
  CHECK(request.find("Content-Type: image/jpeg") != std::string::npos);
  CHECK(request.find("name=\"monitor_id\"") != std::string::npos);
  CHECK(request.find("\xff\xd8\xff") != std::string::npos);  // JPEG start of image
}

TEST_CASE("The http backend reports an unusable server", "[detector][http]") {
  SECTION("an error status") {
    StubServer server(500, "{}");
    zm::ai::HttpDetector detector;
    REQUIRE(detector.Setup(HttpConfig(server.Url())));
    auto packet = PacketWithImage();
    std::vector<zm::ai::Detection> found;
    CHECK(detector.Detect(*packet, found) == zm::ai::DetectStatus::Failed);
  }
  SECTION("a response that isn't JSON") {
    StubServer server(200, "not json");
    zm::ai::HttpDetector detector;
    REQUIRE(detector.Setup(HttpConfig(server.Url())));
    auto packet = PacketWithImage();
    std::vector<zm::ai::Detection> found;
    CHECK(detector.Detect(*packet, found) == zm::ai::DetectStatus::Failed);
  }
}

TEST_CASE("The http backend needs an http URL and a decoded image", "[detector][http]") {
  zm::ai::HttpDetector detector;
  CHECK_FALSE(detector.Setup(HttpConfig("")));
  CHECK_FALSE(detector.Setup(HttpConfig("/usr/share/models/yolo.onnx")));
  CHECK_FALSE(detector.Setup(HttpConfig("file:///etc/passwd")));
  REQUIRE(detector.Setup(HttpConfig("http://127.0.0.1:9/detect")));

  ZMPacket packet;  // no image: nothing to infer on, so no new result
  std::vector<zm::ai::Detection> found;
  CHECK(detector.Detect(packet, found) == zm::ai::DetectStatus::Pending);
}
#endif
