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
#include "zm_time.h"

#include <memory>

class FFmpeg_Input;

#if HAVE_LIBAVUTIL_HWCONTEXT_H
typedef struct DecodeContext {
  AVBufferRef *hw_device_ref;
} DecodeContext;
#endif

// Pure real-time pacing decision, factored out of FfmpegCamera so it can be
// unit-tested without a camera/ffmpeg instance. Given the current packet
// timestamp and the active anchor, decide whether to re-anchor and how long to
// sleep. `elapsed` is wall-clock time since the anchor was set; `cap` is the
// discontinuity threshold above which we re-anchor instead of sleeping.
struct RealtimePaceDecision {
  bool reanchor;       // caller should reset the anchor to (now, ts_us)
  Microseconds sleep;  // how long to wait before delivering this packet
};
RealtimePaceDecision ComputeRealtimePace(
    int64_t ts_us, int64_t anchor_ts_us, Microseconds elapsed, Microseconds cap);
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

  // File-loop support: when the "loop=1" option is set on a seekable file
  // input, seek back to the start on EOF instead of failing/reconnecting.
  // Packet timestamps are shifted by a per-stream offset so they stay
  // monotonically increasing across loops.
  bool                mLoop;
  int64_t             mLoopVideoOffset;        // added to video pts/dts (video time_base)
  int64_t             mLoopAudioOffset;        // added to audio pts/dts (audio time_base)
  int64_t             mLoopVideoFrameDuration; // last/typical video frame duration (video time_base)
  int64_t             mLoopAudioFrameDuration; // last/typical audio frame duration (audio time_base)

  // Real-time pacing ("realtime=1"/"re=1" option, like ffmpeg's -re flag): when
  // reading from a file, throttle packet delivery to the rate implied by the
  // stream timestamps instead of reading as fast as possible. Anchors wall-clock
  // time to the timestamp of the first emitted packet and sleeps before each
  // subsequent packet so it is not delivered ahead of its scheduled time.
  bool                mRealtime;
  bool                mRealtimeAnchored;   // true once the anchor below is set
  TimePoint           mRealtimeStartWall;  // steady_clock anchor for the first packet
  int64_t             mRealtimeStartTS;    // timestamp of the first packet (AV_TIME_BASE_Q, i.e. microseconds)

  std::string         encoder_options;
  std::string         hwaccel_name;
  std::string         hwaccel_device;

  std::unique_ptr<FFmpeg_Input> mSecondInput;

  int frameCount;

  bool                use_hwaccel; //will default to on if hwaccel specified, will get turned off if there is a failure
#if HAVE_LIBAVUTIL_HWCONTEXT_H
  AVBufferRef *hw_device_ctx = nullptr;
  AVPixelFormat hw_pix_fmt = AV_PIX_FMT_NONE;  // Per-instance hw pixel format for get_hw_format callback
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
  // Read a frame, transparently looping a seekable file input back to the start
  // on EOF when mLoop is set. Returns the av_read_frame() result.
  int readFrameWithLoop(AVFormatContext *ctx, AVPacket *pkt);
  // Seek ctx back to the start and bump the per-stream timestamp offsets so the
  // next packet continues monotonically. Returns false if the seek failed.
  bool loopSeekToStart(AVFormatContext *ctx);
  // Real-time pacing: given the just-read packet's timestamp in microseconds
  // (AV_TIME_BASE_Q), sleep until wall-clock time has caught up to the stream's
  // schedule. No-op unless mRealtime is set. Re-anchors on large discontinuities.
  void paceRealtime(int64_t ts_us);
};
#endif // ZM_FFMPEG_CAMERA_H
