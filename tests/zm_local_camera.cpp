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

#include <cerrno>
#include <cstring>
#include <fcntl.h>
#include <any>
#include <map>
#include <string>
#include <sys/ioctl.h>
#include <sys/mman.h>
#include <sys/stat.h>
#include <unistd.h>
#include <vector>

// The tests reset LocalCamera's static bookkeeping between cameras.
#define protected public
#define private public
#include "zm_local_camera.h"
#undef protected
#undef private

#include "zm_monitor.h"

#if ZM_HAS_V4L2

#include <sys/sysmacros.h>

TEST_CASE("LocalCamera::UnmapBuffers skips unmapped slots", "[local_camera]") {
  const size_t len = 4096;
  auto *buffers = new LocalCamera::V4L2MappedBuffer[3]();

  buffers[0].start = mmap(nullptr, len, PROT_READ | PROT_WRITE, MAP_SHARED | MAP_ANONYMOUS, -1, 0);
  buffers[0].length = len;
  REQUIRE(buffers[0].start != MAP_FAILED);
  buffers[1].start = MAP_FAILED;
  buffers[1].length = len;
  void *mapped = buffers[0].start;

  LocalCamera::UnmapBuffers(buffers, 3);

  REQUIRE(buffers[0].start == nullptr);
  REQUIRE(buffers[1].start == MAP_FAILED);
  REQUIRE(buffers[2].start == nullptr);
  unsigned char vec;
  REQUIRE(mincore(mapped, len, &vec) == -1);
  REQUIRE(errno == ENOMEM);
  delete[] buffers;
}

TEST_CASE("LocalCamera::UnmapBuffers accepts null array", "[local_camera]") {
  LocalCamera::UnmapBuffers(nullptr, 8);
}


namespace {

// Fake V4L2 driver. Any ioctl on a file descriptor that refers to /dev/zero is
// answered from this state while it is active.
struct FakeV4L2 {
  bool active = false;
  int inputs = 2;
  int formats = 2;
  std::vector<int> set_inputs;
  std::map<unsigned, int> controls;
  std::vector<std::pair<unsigned, int>> ctrl_sets;
  int queued = 0;
  int fail_input = -1;
  unsigned fmt_width = 64;
};
FakeV4L2 g_fake;

bool IsFakeFd(int fd) {
  if (!g_fake.active)
    return false;
  struct stat st;
  return fstat(fd, &st) == 0 && S_ISCHR(st.st_mode) && st.st_rdev == makedev(1, 5);
}

}  // namespace

static int FakeIoctl(int fd, unsigned long request, void *arg) {
  if (!IsFakeFd(fd)) {
    errno = ENOTTY;
    return -1;
  }

  switch (static_cast<uint32_t>(request)) {
  case VIDIOC_QUERYCAP: {
    auto *cap = static_cast<v4l2_capability *>(arg);
    strcpy(reinterpret_cast<char *>(cap->driver), "fake");
    cap->capabilities = V4L2_CAP_VIDEO_CAPTURE | V4L2_CAP_STREAMING;
    return 0;
  }
  case VIDIOC_ENUMINPUT: {
    auto *in = static_cast<v4l2_input *>(arg);
    if (static_cast<int>(in->index) >= g_fake.inputs) {
      errno = EINVAL;
      return -1;
    }
    snprintf(reinterpret_cast<char *>(in->name), sizeof(in->name), "in%u", in->index);
    in->type = V4L2_INPUT_TYPE_CAMERA;
    return 0;
  }
  case VIDIOC_S_INPUT:
    if (*static_cast<int *>(arg) == g_fake.fail_input) {
      errno = EBUSY;
      return -1;
    }
    g_fake.set_inputs.push_back(*static_cast<int *>(arg));
    return 0;
  case VIDIOC_ENUMSTD:
    errno = ENOTTY;
    return -1;
  case VIDIOC_ENUM_FMT: {
    auto *f = static_cast<v4l2_fmtdesc *>(arg);
    if (static_cast<int>(f->index) >= g_fake.formats) {
      errno = EINVAL;
      return -1;
    }
    f->pixelformat = f->index ? V4L2_PIX_FMT_MJPEG : V4L2_PIX_FMT_YUYV;
    snprintf(reinterpret_cast<char *>(f->description), sizeof(f->description), "fmt%u", f->index);
    return 0;
  }
  case VIDIOC_CROPCAP:
  case VIDIOC_G_CROP:
    errno = EINVAL;
    return -1;
  case VIDIOC_G_FMT:
  case VIDIOC_S_FMT: {
    auto *fmt = static_cast<v4l2_format *>(arg);
    fmt->type = V4L2_BUF_TYPE_VIDEO_CAPTURE;
    fmt->fmt.pix.width = g_fake.fmt_width;
    fmt->fmt.pix.height = 48;
    fmt->fmt.pix.pixelformat = V4L2_PIX_FMT_YUYV;
    fmt->fmt.pix.bytesperline = 128;
    fmt->fmt.pix.sizeimage = 128 * 48;
    return 0;
  }
  case VIDIOC_REQBUFS:
    return 0;
  case VIDIOC_QBUF:
    g_fake.queued++;
    return 0;
  case VIDIOC_DQBUF: {
    if (g_fake.queued <= 0) {
      errno = EIO;
      return -1;
    }
    g_fake.queued--;
    auto *b = static_cast<v4l2_buffer *>(arg);
    b->index = 0;
    b->bytesused = 128 * 48;
    return 0;
  }
  case VIDIOC_STREAMON:
  case VIDIOC_STREAMOFF:
  case VIDIOC_S_STD:
    return 0;
  case VIDIOC_QUERYBUF: {
    auto *b = static_cast<v4l2_buffer *>(arg);
    b->length = 128 * 48;
    b->m.offset = 0;
    return 0;
  }
  case VIDIOC_G_CTRL: {
    auto *c = static_cast<v4l2_control *>(arg);
    auto it = g_fake.controls.find(c->id);
    if (it == g_fake.controls.end()) {
      errno = EINVAL;
      return -1;
    }
    c->value = it->second;
    return 0;
  }
  case VIDIOC_S_CTRL: {
    auto *c = static_cast<v4l2_control *>(arg);
    g_fake.ctrl_sets.emplace_back(c->id, c->value);
    g_fake.controls[c->id] = c->value;
    return 0;
  }
  default:
    errno = ENOTTY;
    return -1;
  }
}

namespace {

class FakeGuard {
  int (*saved_ioctl)(int, unsigned long, void *) = zm_v4l2_ioctl;

 public:
  FakeGuard() {
    g_fake = FakeV4L2();
    g_fake.active = true;
    zm_v4l2_ioctl = FakeIoctl;
    LocalCamera::last_camera = nullptr;
  }
  ~FakeGuard() {
    g_fake.active = false;
    zm_v4l2_ioctl = saved_ioctl;
    LocalCamera::last_camera = nullptr;
  }
};

class TestMonitor : public Monitor {
 public:
  TestMonitor() : Monitor() {}
};

std::unique_ptr<LocalCamera> MakeLocalCamera(const Monitor *monitor, int channel) {
  return std::make_unique<LocalCamera>(
           monitor, "/dev/zero", channel, V4L2_STD_UNKNOWN, false, 1, "v4l2",
           64, 48, ZM_COLOUR_RGB24, V4L2_PIX_FMT_YUYV, -1, -1, -1, -1, true, false);
}

}  // namespace

TEST_CASE("LocalCamera: Initialise selects the configured input", "[local_camera]") {
  FakeGuard fake;
  TestMonitor monitor;
  auto camera = MakeLocalCamera(&monitor, 1);

  REQUIRE(camera->Initialise() == 0);
  REQUIRE(g_fake.set_inputs == std::vector<int>{1});
  camera->Close();
  camera.reset();
}

TEST_CASE("LocalCamera: Capture requeues the buffer when it fails after dequeue", "[local_camera]") {
  FakeGuard fake;
  TestMonitor monitor;
  LocalCamera::camera_count = 0;
  LocalCamera::channel_count = 0;
  auto camera = MakeLocalCamera(&monitor, 0);
  auto other = MakeLocalCamera(&monitor, 1);
  REQUIRE(camera->Initialise() == 0);
  REQUIRE(camera->PrimeCapture() == 1);
  const int queued_after_prime = g_fake.queued;
  REQUIRE(queued_after_prime == 1);
  auto packet = std::make_shared<ZMPacket>();

  SECTION("input switch failure") {
    g_fake.fail_input = 1;
    REQUIRE(camera->Capture(packet) == -1);
    REQUIRE(g_fake.queued == queued_after_prime);
  }
  SECTION("image larger than the monitor") {
    g_fake.fmt_width = 128;
    LocalCamera::v4l2_data.fmt.fmt.pix.width = 128;
    REQUIRE(camera->Capture(packet) == -1);
    REQUIRE(g_fake.queued == queued_after_prime);
  }
  camera->Close();
  other.reset();
  camera.reset();
  LocalCamera::camera_count = 0;
  LocalCamera::channel_count = 0;
}

TEST_CASE("LocalCamera: a second camera on the same channel converts the shared capture", "[local_camera]") {
  FakeGuard fake;
  TestMonitor monitor;
  LocalCamera::camera_count = 0;
  LocalCamera::channel_count = 0;
  auto camera = MakeLocalCamera(&monitor, 0);
  auto shared = MakeLocalCamera(&monitor, 0);
  REQUIRE(camera->channel_prime);
  REQUIRE_FALSE(shared->channel_prime);
  REQUIRE(camera->conversion_type == 1);
  REQUIRE(camera->Initialise() == 0);
  REQUIRE(camera->PrimeCapture() == 1);

  auto packet = std::make_shared<ZMPacket>();
  auto shared_packet = std::make_shared<ZMPacket>();
  REQUIRE(camera->Capture(packet) == 1);
  REQUIRE(shared->Capture(shared_packet) == 1);
  REQUIRE(shared_packet->image != nullptr);

  camera->Close();
  shared.reset();
  camera.reset();
  LocalCamera::camera_count = 0;
  LocalCamera::channel_count = 0;
}

TEST_CASE("LocalCamera: a destroyed camera is not kept as last_camera", "[local_camera]") {
  // The next camera's constructor reads last_camera, so it must not dangle.
  FakeGuard fake;
  TestMonitor monitor;
  auto camera = MakeLocalCamera(&monitor, 1);
  REQUIRE(LocalCamera::last_camera == camera.get());
  camera.reset();
  REQUIRE(LocalCamera::last_camera == nullptr);
}

TEST_CASE("LocalCamera: control values below -1 are applied", "[local_camera]") {
  FakeGuard fake;
  TestMonitor monitor;
  auto camera = MakeLocalCamera(&monitor, 0);
  LocalCamera::vid_fd = open("/dev/zero", O_RDONLY);
  REQUIRE(LocalCamera::vid_fd >= 0);
  g_fake.controls[V4L2_CID_BRIGHTNESS] = 5;
  g_fake.controls[V4L2_CID_HUE] = 0;

  SECTION("negative brightness is written to the device") {
    REQUIRE(camera->Brightness(-10) == -10);
    REQUIRE(g_fake.ctrl_sets.size() == 1);
    REQUIRE(g_fake.controls[V4L2_CID_BRIGHTNESS] == -10);
  }
  SECTION("negative hue is written to the device") {
    REQUIRE(camera->Hue(-64) == -64);
    REQUIRE(g_fake.controls[V4L2_CID_HUE] == -64);
  }
  SECTION("default argument only reads") {
    REQUIRE(camera->Brightness() == 5);
    REQUIRE(g_fake.ctrl_sets.empty());
  }
  SECTION("zero is written") {
    REQUIRE(camera->Brightness(0) == 0);
    REQUIRE(g_fake.ctrl_sets.size() == 1);
  }

  close(LocalCamera::vid_fd);
  LocalCamera::vid_fd = -1;
  camera.reset();
}

TEST_CASE("LocalCamera::GetCurrentSettings verbose output keeps earlier sections", "[local_camera]") {
  FakeGuard fake;
  static char out[0x10000];
  REQUIRE(LocalCamera::GetCurrentSettings("/dev/zero", out, 2, true));
  std::string text(out);
  REQUIRE(text.find("Video Device: /dev/zero") == 0);
  REQUIRE(text.find("General Capabilities") != std::string::npos);
  REQUIRE(text.find("Input 0") != std::string::npos);
  REQUIRE(text.find("Input 1") != std::string::npos);
}

TEST_CASE("LocalCamera::GetCurrentSettings lists formats separated by slash", "[local_camera]") {
  FakeGuard fake;
  static char out[0x10000];
  REQUIRE(LocalCamera::GetCurrentSettings("/dev/zero", out, 2, false));
  std::string text(out);
  REQUIRE(text.find("F:0x56595559/0x47504a4d|") != std::string::npos);
}

TEST_CASE("getFfPixFormatFromV4lPalette maps palettes to matching pixel layouts", "[local_camera]") {
  REQUIRE(getFfPixFormatFromV4lPalette(2, V4L2_PIX_FMT_YUYV) == AV_PIX_FMT_YUYV422);
  REQUIRE(getFfPixFormatFromV4lPalette(2, V4L2_PIX_FMT_RGB24) == AV_PIX_FMT_RGB24);
  REQUIRE(getFfPixFormatFromV4lPalette(2, V4L2_PIX_FMT_BGR24) == AV_PIX_FMT_BGR24);
  REQUIRE(getFfPixFormatFromV4lPalette(2, V4L2_PIX_FMT_YUV420) == AV_PIX_FMT_YUV420P);
#ifdef V4L2_PIX_FMT_YUV444
  // V4L2 YUV444 is a packed 16 bit format, planar 24 bit YUV444P would read past the buffer
  REQUIRE(getFfPixFormatFromV4lPalette(2, V4L2_PIX_FMT_YUV444) == AV_PIX_FMT_NONE);
#endif
}

#endif
