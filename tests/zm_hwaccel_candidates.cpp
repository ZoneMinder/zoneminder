/*
 * Which hwaccel device types a DecoderHWAccelName asks to try.
 *
 * The parsing is what an operator's monitor setting runs into, and it used to
 * sit inline in FfmpegCamera::OpenFfmpeg() where no test could reach it. The
 * cases that matter are the ones a person actually types: a single name, a
 * priority list, spaces around the commas, and a typo in the middle of an
 * otherwise good list.
 *
 * What the host can offer is not fixed, so the assertions are about the shape
 * of the answer rather than a particular device being present.
 */

#include "zm_catch2.h"

#include "zm_ffmpeg.h"

#include <algorithm>

namespace {

bool contains(const std::vector<enum AVHWDeviceType> &v, enum AVHWDeviceType t) {
  return std::find(v.begin(), v.end(), t) != v.end();
}

// A device type this build actually knows, or NONE if it knows none at all.
enum AVHWDeviceType some_known_type() {
  enum AVHWDeviceType t = AV_HWDEVICE_TYPE_NONE;
  return av_hwdevice_iterate_types(t);
}

}  // namespace

TEST_CASE("hwaccel_candidate_types", "[ffmpeg][hwaccel]") {
  SECTION("no hwaccel asked for yields nothing") {
    REQUIRE(hwaccel_candidate_types("").empty());
  }

  SECTION("auto offers everything this build has") {
    const auto all = hwaccel_candidate_types("auto");
    enum AVHWDeviceType expected = AV_HWDEVICE_TYPE_NONE;
    size_t count = 0;
    while ((expected = av_hwdevice_iterate_types(expected)) != AV_HWDEVICE_TYPE_NONE) count++;
    REQUIRE(all.size() == count);
  }

  SECTION("a name this build does not have is dropped, not fatal") {
    // The warning is the operator's signal; the return is simply empty.
    REQUIRE(hwaccel_candidate_types("notahwaccel").empty());
  }

  SECTION("a typo does not cost the working entries beside it") {
    const enum AVHWDeviceType known = some_known_type();
    if (known == AV_HWDEVICE_TYPE_NONE) return;  // nothing to assert against
    const std::string name = av_hwdevice_get_type_name(known);

    const auto got = hwaccel_candidate_types("notahwaccel," + name);
    REQUIRE(got.size() == 1);
    REQUIRE(got.front() == known);
  }

  SECTION("a list keeps the order it was written in") {
    std::vector<enum AVHWDeviceType> known;
    enum AVHWDeviceType t = AV_HWDEVICE_TYPE_NONE;
    while ((t = av_hwdevice_iterate_types(t)) != AV_HWDEVICE_TYPE_NONE) known.push_back(t);
    if (known.size() < 2) return;  // cannot show ordering with fewer than two

    const std::string first = av_hwdevice_get_type_name(known[0]);
    const std::string second = av_hwdevice_get_type_name(known[1]);

    const auto forward = hwaccel_candidate_types(first + "," + second);
    REQUIRE(forward.size() == 2);
    REQUIRE(forward[0] == known[0]);
    REQUIRE(forward[1] == known[1]);

    // Reversing the setting must reverse what gets tried, or the priority the
    // operator expressed is not being honoured.
    const auto backward = hwaccel_candidate_types(second + "," + first);
    REQUIRE(backward.size() == 2);
    REQUIRE(backward[0] == known[1]);
    REQUIRE(backward[1] == known[0]);
  }

  SECTION("spaces around the commas are not part of the name") {
    const enum AVHWDeviceType known = some_known_type();
    if (known == AV_HWDEVICE_TYPE_NONE) return;
    const std::string name = av_hwdevice_get_type_name(known);

    REQUIRE(contains(hwaccel_candidate_types("  " + name + "  "), known));
    REQUIRE(contains(hwaccel_candidate_types(name + " , "), known));
  }

  SECTION("empty entries in a list are skipped") {
    const enum AVHWDeviceType known = some_known_type();
    if (known == AV_HWDEVICE_TYPE_NONE) return;
    const std::string name = av_hwdevice_get_type_name(known);

    const auto got = hwaccel_candidate_types("," + name + ",,");
    REQUIRE(got.size() == 1);
    REQUIRE(got.front() == known);
  }
}
