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
