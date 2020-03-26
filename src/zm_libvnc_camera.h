
#ifndef ZN_LIBVNC_CAMERA_H
#define ZN_LIBVNC_CAMERA_H

#include "zm_buffer.h"
#include "zm_camera.h"
#include "zm_thread.h"

#if HAVE_LIBVNC
#include <rfb/rfbclient.h>

// Used by vnc callbacks
struct VncPrivateData
{
  uint8_t *buffer;
  uint8_t *prevBuffer; 
  uint32_t bufferSize;
  Mutex mutex;
  ThreadData<bool> newImage;
};

class VncCamera : public Camera {
protected:
  rfbClient *mRfb;
  VncPrivateData mVncData;
  int mBpp;
  int mSpp;
  int mBps;
  char** mOptArgvs;
  std::string mHost;
  std::string mPort;
  std::string mUser;
  std::string mPass;
  time_t secs;
public:
  VncCamera(
      unsigned int p_monitor_id,
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
      bool p_record_audio );
    
  ~VncCamera();

  void Initialise();
  void Terminate();
  
  int PreCapture();
  int PrimeCapture();
  int Capture( Image &image );
  int PostCapture();
  int CaptureAndRecord( Image &image, timeval recording, char* event_directory );
  int Close();
};

#endif // HAVE_LIBVNC
