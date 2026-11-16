/*
 * This file is part of the ZoneMinder Project. See AUTHORS file for Copyright information
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation; either version 2 of the License, or (at your
 * option) any later version.
 */

#include "zm_catch2.h"

// The helpers live in zmc.cpp; build it here with its main() renamed.
#define main zmc_main
#include "zmc.cpp"
#undef main
#include "zm_monitor.h"

#include <atomic>
#include <getopt.h>
#include <string>
#include <vector>

namespace {
std::string fake_escape(const std::string &s) { return s; }

// Run getopt_long over args with zmc's option string; returns options seen in order.
std::string parse(std::vector<const char *> args) {
  static struct option long_options[] = {
    {"protocol", 1, nullptr, 'r'}, {"help", 0, nullptr, 'h'}, {nullptr, 0, nullptr, 0}};
  args.insert(args.begin(), "zmc");
  std::vector<char *> argv;
  for (const char *a : args) argv.push_back(const_cast<char *>(a));
  optind = 1;
  opterr = 0;
  std::string seen;
  int idx = 0;
  int c;
  while ((c = getopt_long(static_cast<int>(argv.size()), argv.data(), kZmcShortOptions, long_options, &idx)) != -1) {
    seen += static_cast<char>(c);
    if (c == 'r') seen += std::string("=") + optarg;
  }
  return seen;
}
}  // namespace

TEST_CASE("zmc short -r takes the protocol", "[zmcmain]") {
  REQUIRE(parse({"-r", "http", "-H", "cam"}) == "r=httpH");
}

TEST_CASE("zmc -h is a flag and does not eat the next argument", "[zmcmain]") {
  REQUIRE(parse({"-h", "-m", "3"}) == "hm");
}

TEST_CASE("remote monitor filter matches only the given parts", "[zmcmain]") {
  REQUIRE(Monitor::RemoteMonitorFilter("", "cam", "80", "/x", fake_escape)
          == " AND `Host` = 'cam' AND `Port` = '80' AND `Path` = '/x'");
  REQUIRE(Monitor::RemoteMonitorFilter("http", "cam", "", "", fake_escape)
          == " AND `Protocol` = 'http' AND `Host` = 'cam'");
}

TEST_CASE("remote monitor filter escapes values", "[zmcmain]") {
  auto esc = [](const std::string &s) { return "<" + s + ">"; };
  REQUIRE(Monitor::RemoteMonitorFilter(nullptr, "h", "1", "p", esc)
          == " AND `Host` = '<h>' AND `Port` = '<1>' AND `Path` = '<p>'");
}

TEST_CASE("interruptible sleep stops when asked and keeps ticking", "[zmcmain]") {
  std::atomic<int> ticks{0};
  auto start = std::chrono::steady_clock::now();
  bool done = InterruptibleSleep(std::chrono::seconds(30),
                                 [&]() { return ticks.load() >= 3; }, [&]() { ticks++; });
  REQUIRE_FALSE(done);
  REQUIRE(ticks == 3);
  REQUIRE(std::chrono::steady_clock::now() - start < std::chrono::seconds(2));
  REQUIRE(InterruptibleSleep(std::chrono::milliseconds(150), []() { return false; }, nullptr));
}
