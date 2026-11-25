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

#ifndef ZM_STREAM_ENCODER_H
#define ZM_STREAM_ENCODER_H

#include "zm_ffmpeg.h"
#include "zm_swscale.h"
#include "zm_time.h"

#include <functional>
#include <string>

class Image;

// Encodes the images of a monitor whose camera hands zmc decoded pictures
// (Local/V4L2, MJPEG over HTTP, VNC...) so they can be published on the
// monitor stream socket, which otherwise has no video to carry for them.
//
// The monitor's StreamEncoder setting picks the encoder. "mjpeg" is the
// baseline: cheap, and available in every FFmpeg build. A hardware encoder
// from the encoder table (h264_vaapi, hevc_nvenc...) gives a consumer H.264
// or H.265 at little CPU cost; if it will not open on this box, the encoder
// falls back to mjpeg rather than leave the socket without video. Software
// H.264/H.265 encoders are deliberately not offered: running one
// continuously for every such monitor costs more than the feature is worth.
class StreamEncoder {
 public:
  using Sink = std::function<void(const AVPacket *packet, bool keyframe, int64_t pts_us)>;

  // encoder_name: "mjpeg" or the name of a hardware encoder.
  // hwaccel_device: device for the hardware encoder; empty uses its default.
  // jpeg_quality: libjpeg-style 0-100 quality for mjpeg.
  StreamEncoder(std::string encoder_name, std::string hwaccel_device, int jpeg_quality);
  ~StreamEncoder();
  StreamEncoder(const StreamEncoder &) = delete;
  StreamEncoder &operator=(const StreamEncoder &) = delete;

  // Whether name is a setting the stream encoder accepts: "mjpeg" or one of
  // the hardware encoders in the encoder table.
  static bool IsSupported(const std::string &name);

  // Opens an encoder for width x height images. A rate-controlled encoder
  // spreads its bit rate over frame_rate frames a second, so pass the real
  // capture rate; {0, 0} means unknown and assumes 25. Returns false only
  // when not even mjpeg will open.
  bool Open(int width, int height, AVRational frame_rate);
  void Close();
  bool IsOpen() const { return ctx_ != nullptr; }

  int Width() const { return width_; }
  int Height() const { return height_; }
  // The encoder actually in use, which is "mjpeg" after a fallback.
  const std::string &Name() const { return active_name_; }
  // For the stream socket HELLO. Valid while open.
  const AVCodecParameters *Parameters() const { return par_; }
  // The frame rate the encoder was opened with (25 when it was not known).
  AVRational FrameRate() const { return ctx_ ? ctx_->framerate : AVRational{0, 0}; }

  // Encodes one image, handing each packet the encoder produces to sink.
  // force_keyframe asks for the next packet to be a keyframe (a consumer has
  // just connected). H.264/H.265 keyframes carry their parameter sets in
  // band, so every keyframe can start a decoder. Returns false on an error.
  bool Encode(const Image *image, SystemTimePoint timestamp, bool force_keyframe, const Sink &sink);

 private:
  bool OpenEncoder(const std::string &name, int width, int height, AVRational frame_rate);
  bool Drain(const Sink &sink);

  std::string wanted_name_;
  std::string hwaccel_device_;
  int jpeg_quality_;

  std::string active_name_;
  AVCodecContext *ctx_ = nullptr;
  AVCodecParameters *par_ = nullptr;
  AVPixelFormat sw_pix_fmt_ = AV_PIX_FMT_NONE;
  int width_ = 0;
  int height_ = 0;
  SWScale swscale_;
  int64_t first_us_ = AV_NOPTS_VALUE;
  int64_t last_pts_ = AV_NOPTS_VALUE;
  // Packets from frames sent before the last forced keyframe are dropped: a
  // pipelined encoder still holds them from before an idle spell, and they
  // would reach a new consumer ahead of the keyframe it can start on.
  int64_t discard_before_ = AV_NOPTS_VALUE;
};

#endif  // ZM_STREAM_ENCODER_H
