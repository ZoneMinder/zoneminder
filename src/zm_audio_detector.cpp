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

#include "zm_audio_detector.h"

#include "zm_logger.h"

#include <algorithm>
#include <cmath>

namespace {

int FrameChannels(const AVFrame *frame) {
#if LIBAVUTIL_VERSION_CHECK(57, 28, 100, 28, 0)
  return frame->ch_layout.nb_channels;
#else
  return frame->channels;
#endif
}

}  // namespace

AudioDetector::~AudioDetector() {
  Close();
}

bool AudioDetector::Open(const AVCodecParameters *codecpar) {
  Close();

  if (!codecpar) return false;

  const AVCodec *codec = avcodec_find_decoder(codecpar->codec_id);
  if (!codec) {
    Warning("Audio detection: no decoder for codec %d, detection disabled", codecpar->codec_id);
    return false;
  }

  codec_context_ = avcodec_alloc_context3(codec);
  if (!codec_context_) {
    Error("Audio detection: could not allocate a decoder context");
    return false;
  }

  if (avcodec_parameters_to_context(codec_context_, codecpar) < 0) {
    Error("Audio detection: could not copy stream parameters");
    Close();
    return false;
  }

  if (avcodec_open2(codec_context_, codec, nullptr) < 0) {
    Error("Audio detection: could not open the %s decoder", codec->name);
    Close();
    return false;
  }

  Debug(1, "Audio detection: opened %s decoder", codec->name);
  return true;
}

void AudioDetector::Close() {
  if (codec_context_) avcodec_free_context(&codec_context_);
  codec_context_ = nullptr;
  level_.store(0, std::memory_order_relaxed);
}

double AudioDetector::RmsS16(const int16_t *samples, size_t count) {
  if (!samples or !count) return 0.0;

  double sum = 0.0;
  for (size_t i = 0; i < count; i++) {
    // 32768 rather than 32767: -32768 is a legal sample and would otherwise
    // push the ratio just over 1.0.
    const double v = static_cast<double>(samples[i]) / 32768.0;
    sum += v * v;
  }
  return std::sqrt(sum / static_cast<double>(count));
}

double AudioDetector::RmsFloat(const float *samples, size_t count) {
  if (!samples or !count) return 0.0;

  double sum = 0.0;
  for (size_t i = 0; i < count; i++) {
    const double v = std::max(-1.0, std::min(1.0, static_cast<double>(samples[i])));
    sum += v * v;
  }
  return std::sqrt(sum / static_cast<double>(count));
}

int AudioDetector::LevelFromRms(double rms) {
  if (!(rms > 0.0)) return 0;  // also catches NaN

  const double db = 20.0 * std::log10(std::min(rms, 1.0));
  if (db <= AUDIO_FLOOR_DB) return 0;

  const double level = 100.0 * (1.0 - db / AUDIO_FLOOR_DB);
  return static_cast<int>(std::lround(std::max(0.0, std::min(100.0, level))));
}

bool AudioDetector::IsAlarm(int level, int threshold) {
  // Threshold 0 is "off". Without this a monitor that had detection enabled
  // but never had a threshold set would alarm on every packet, silence
  // included, because a level of 0 is >= a threshold of 0.
  if (threshold <= 0) return false;
  return level >= threshold;
}

double AudioDetector::RmsFromFrame(const AVFrame *frame) const {
  const int channels = FrameChannels(frame);
  const int samples = frame->nb_samples;
  if (channels <= 0 or samples <= 0) return 0.0;

  const AVSampleFormat format = static_cast<AVSampleFormat>(frame->format);
  const bool planar = av_sample_fmt_is_planar(format) != 0;
  // Planar frames keep one plane per channel; interleaved keeps everything in
  // plane 0, so one "plane" of channels * samples values.
  const int planes = planar ? channels : 1;
  const size_t per_plane = static_cast<size_t>(samples) * (planar ? 1 : channels);

  // Averaging the per-plane mean squares gives the same answer as one pass
  // over every sample, and keeps interleaved and planar on the same scale.
  double sum_of_squares = 0.0;
  for (int p = 0; p < planes; p++) {
    if (!frame->extended_data[p]) continue;

    double rms = 0.0;
    switch (format) {
      case AV_SAMPLE_FMT_S16:
      case AV_SAMPLE_FMT_S16P:
        rms = RmsS16(reinterpret_cast<const int16_t *>(frame->extended_data[p]), per_plane);
        break;
      case AV_SAMPLE_FMT_FLT:
      case AV_SAMPLE_FMT_FLTP:
        rms = RmsFloat(reinterpret_cast<const float *>(frame->extended_data[p]), per_plane);
        break;
      default:
        // Every codec ZM sees over RTSP decodes to s16 or float. Anything else
        // is reported once rather than silently scoring 0 forever.
        Debug(1, "Audio detection: unhandled sample format %s",
              av_get_sample_fmt_name(format));
        return 0.0;
    }
    sum_of_squares += rms * rms;
  }

  return std::sqrt(sum_of_squares / static_cast<double>(planes));
}

int AudioDetector::Process(const AVPacket *packet) {
  if (!codec_context_ or !packet) return Level();

  if (avcodec_send_packet(codec_context_, packet) < 0) {
    Debug(2, "Audio detection: decoder rejected a packet");
    return Level();
  }

  AVFrame *frame = av_frame_alloc();
  if (!frame) return Level();

  int level = -1;
  while (avcodec_receive_frame(codec_context_, frame) == 0) {
    // A packet can yield several frames; the loudest wins, so a short sound at
    // the head of the packet is not averaged away by the quiet that follows.
    level = std::max(level, LevelFromRms(RmsFromFrame(frame)));
    av_frame_unref(frame);
  }
  av_frame_free(&frame);

  // No frames means the decoder is still priming, which is normal. Holding the
  // previous level is better than reporting a spurious 0.
  if (level < 0) return Level();

  level_.store(level, std::memory_order_relaxed);
  return level;
}
