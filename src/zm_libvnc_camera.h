
#ifndef ZN_LIBVNC_CAMERA_H
#define ZN_LIBVNC_CAMERA_H

#include "zm_camera.h"
#include "zm_swscale.h"

// Names tried when loading libvncclient. The unversioned name only exists
// when the -dev package is installed; the runtime package (which is all the
// ZoneMinder packages depend on) ships just the versioned soname.
static const char *const zm_libvnc_names[] = {
  "libvncclient.so",
  "libvncclient.so.1",
  "libvncclient.so.0",
  nullptr
};

// Returns the first handle load() gives for one of the names, or nullptr.
template <typename Loader>
void *zm_libvnc_open(Loader load) {
  for (const char *const *name = zm_libvnc_names; *name; ++name) {
    if (void *handle = load(*name)) return handle;
  }
  return nullptr;
}

// Replaces a VNC framebuffer after a server resize. The capture buffer pointer
// is updated too so Capture() never reads the freed old buffer.
template <typename Alloc, typename Free>
bool zm_libvnc_replace_framebuffer(uint8_t *&frameBuffer, uint8_t *&published,
                                   int width, int height, Alloc alloc, Free release) {
  if (frameBuffer) release(frameBuffer);
  frameBuffer = alloc(static_cast<size_t>(width) * height * 4);
  published = frameBuffer;
  return frameBuffer != nullptr;
}

#if HAVE_LIBVNC
#include <rfb/rfbclient.h>

// Older versions of libvncserver defined a max macro in rfb/rfbproto.h
// Undef it here so it doesn't collide with std::max
// TODO: Remove this once CentOS 7 support is dropped
#ifdef max
#undef max
#endif


// Used by vnc callbacks
struct VncPrivateData {
  uint8_t *buffer;
  uint8_t width;
  uint8_t height;
};

class VncCamera : public Camera {
 protected:
  rfbClient *mRfb;
  VncPrivateData mVncData;
  SWScale scale;
  AVPixelFormat mImgPixFmt;
  std::string mHost;
  std::string mPort;
  std::string mUser;
  std::string mPass;
 public:
  VncCamera(
    const Monitor *monitor,
    const std::string &host,
    const std::string &port,
    const std::string &user,
    const std::string &pass,
    int p_width,
    int p_height,
    int p_colours,
    int p_brightness,
    int p_contrast,
    int p_hue,
    int p_colour,
    bool p_capture,
    bool p_record_audio);

  ~VncCamera();

  int PreCapture() override;
  int PrimeCapture() override;
  int Capture(std::shared_ptr<ZMPacket> &packet) override;
  int PostCapture() override;
  int Close() override;
};

#endif // HAVE_LIBVNC
#endif // ZN_LIBVNC_CAMERA_H
