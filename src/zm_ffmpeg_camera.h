//
// ZoneMinder Ffmpeg Class Interface, $Date: 2008-07-25 10:33:23 +0100 (Fri, 25 Jul 2008) $, $Revision: 2611 $
// Copyright (C) 2001-2008 Philip Coombes
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
//

#ifndef ZM_FFMPEG_CAMERA_H
#define ZM_FFMPEG_CAMERA_H

#include "zm_camera.h"

#include <memory>

class FFmpeg_Input;

#if HAVE_LIBAVUTIL_HWCONTEXT_H
typedef struct DecodeContext {
  AVBufferRef *hw_device_ref;
} DecodeContext;
#endif
//
// Class representing 'ffmpeg' cameras, i.e. those which are
// accessed using ffmpeg multimedia framework
//
class FfmpegCamera : public Camera {
 protected:
  std::string         mPath;
  std::string         mMaskedPath;
  std::string         mSecondPath;
  std::string         mUser;
  std::string         mPass;
  std::string         mMaskedSecondPath;
  std::string         mMethod;
  std::string         mOptions;

  std::string         encoder_options;
  std::string         hwaccel_name;
  std::string         hwaccel_device;

  std::unique_ptr<FFmpeg_Input> mSecondInput;

  int frameCount;

  _AVPIXELFORMAT      imagePixFormat;

  bool                use_hwaccel; //will default to on if hwaccel specified, will get turned off if there is a failure
#if HAVE_LIBAVUTIL_HWCONTEXT_H
  AVBufferRef *hw_device_ctx = nullptr;
#endif

  // Used to store the incoming packet, it will get copied when queued.
  // We only ever need one at a time, so instead of constantly allocating
  // and freeing this structure, we will just make it a member of the object.
  av_packet_ptr packet;

  int OpenFfmpeg();
  int Close() override;

  struct SwsContext   *mConvertContext;

  int                 error_count;
  int stream_width;   /* What the camera is actually sending */
  int stream_height;

 public:
  FfmpegCamera(
    const Monitor *monitor,
    const std::string &p_path,
    const std::string &p_second_path,
    const std::string &p_user,
    const std::string &p_pass,
    const std::string &p_method,
    const std::string &p_options,
    int p_width,
    int p_height,
    int p_colours,
    int p_brightness,
    int p_contrast,
    int p_hue,
    int p_colour,
    bool p_capture,
    bool p_record_audio,
    const std::string &p_hwaccel_name,
    const std::string &p_hwaccel_device
  );
  ~FfmpegCamera();

  const std::string &Path() const { return mPath; }
  const std::string &Options() const { return mOptions; }
  const std::string &Method() const { return mMethod; }

  int PrimeCapture() override;
  int PreCapture() override;
  int Capture(std::shared_ptr<ZMPacket> &p) override;
  int PostCapture() override;
 private:
  static int FfmpegInterruptCallback(void*ctx);
};
#endif // ZM_FFMPEG_CAMERA_H
