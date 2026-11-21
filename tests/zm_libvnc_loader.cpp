#include "zm_catch2.h"

#include "zm_libvnc_camera.h"

#include <string>
#include <vector>

TEST_CASE("libvncclient loader falls back to the versioned soname", "[VncLoader]") {
  // Runtime-only installs (libvncclient1) have no libvncclient.so symlink.
  std::vector<std::string> tried;
  int dummy;
  void *handle = zm_libvnc_open([&](const char *name) -> void * {
    tried.push_back(name);
    return std::string(name) == "libvncclient.so.1" ? &dummy : nullptr;
  });
  REQUIRE(handle == &dummy);
  REQUIRE(tried.back() == "libvncclient.so.1");
}

TEST_CASE("libvncclient loader returns null when nothing loads", "[VncLoader]") {
  REQUIRE(zm_libvnc_open([](const char *) -> void * { return nullptr; }) == nullptr);
}

TEST_CASE("libvncclient loader prefers the first name", "[VncLoader]") {
  int dummy;
  int calls = 0;
  void *handle = zm_libvnc_open([&](const char *) -> void * { ++calls; return &dummy; });
  REQUIRE(handle == &dummy);
  REQUIRE(calls == 1);
}

TEST_CASE("vnc resize publishes the new framebuffer to the capture buffer", "[VncResize]") {
  uint8_t oldfb[4], newfb[16];
  uint8_t *frameBuffer = oldfb;
  uint8_t *published = oldfb;  // what Capture() reads from
  uint8_t *freed = nullptr;
  size_t asked = 0;
  bool ok = zm_libvnc_replace_framebuffer(
              frameBuffer, published, 2, 2,
              [&](size_t n) -> uint8_t * { asked = n; return newfb; },
              [&](uint8_t *p) { freed = p; });
  REQUIRE(ok);
  REQUIRE(freed == oldfb);
  REQUIRE(asked == 16);
  REQUIRE(frameBuffer == newfb);
  REQUIRE(published == newfb);  // not the freed buffer
}

TEST_CASE("vnc resize failure leaves no dangling capture buffer", "[VncResize]") {
  uint8_t oldfb[4];
  uint8_t *frameBuffer = oldfb;
  uint8_t *published = oldfb;
  bool ok = zm_libvnc_replace_framebuffer(
              frameBuffer, published, 2, 2,
              [](size_t) -> uint8_t * { return nullptr; },
              [](uint8_t *) {});
  REQUIRE_FALSE(ok);
  REQUIRE(frameBuffer == nullptr);
  REQUIRE(published == nullptr);
}
