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

#ifndef ZM_AUDIO_DETECTOR_H
#define ZM_AUDIO_DETECTOR_H

#include "zm_ffmpeg.h"

#include <atomic>
#include <cstdint>

// Reports how loud a monitor's audio is, on the same 0-100 scale the monitor
// editor asks the user to set a threshold on.
//
// The level is a dBFS reading, not a raw amplitude ratio. Linear RMS is close
// to useless for a threshold: ordinary speech sits around 1-3% of full scale,
// so every interesting sound would be crowded into the bottom couple of points
// of the range and the setting would be impossible to tune. Mapping
// AUDIO_FLOOR_DB..0 dBFS onto 0..100 spreads the useful range out instead.
class AudioDetector {
 public:
  // Anything at or below this is reported as 0. -60 dBFS is comfortably under a
  // quiet room's noise floor on the hardware this was written against.
  static constexpr double AUDIO_FLOOR_DB = -60.0;

  AudioDetector() = default;
  ~AudioDetector();

  AudioDetector(const AudioDetector &) = delete;
  AudioDetector &operator=(const AudioDetector &) = delete;

  // Takes a reference to the stream's parameters. Returns false if no decoder
  // is available for the codec, in which case the detector stays disabled and
  // Level() keeps reporting 0 rather than the caller having to track that.
  //
  // A codec that has already failed is refused immediately and silently. The
  // caller retries on every audio packet, so without this a stream ZoneMinder
  // cannot decode logs a warning at the audio packet rate forever. A reconnect
  // that brings a different codec is still tried.
  bool Open(const AVCodecParameters *codecpar);
  bool IsOpen() const { return codec_context_ != nullptr; }
  void Close();

  // Decodes the packet and updates the current level. Returns the level, or
  // the previous one if the packet produced no frames (a decoder priming a
  // buffer is normal, not an error).
  int Process(const AVPacket *packet);

  int Level() const { return level_.load(std::memory_order_relaxed); }

  // Highest level seen since the last call, and resets to 0 so the next call
  // covers the next interval. Frames rows are written well below the capture
  // rate, so recording Level() at write time would miss a bang that happened
  // between two rows and had already decayed; the peak survives it.
  //
  // Called from the analysis thread while the capture thread is still writing,
  // hence the exchange rather than a read followed by a store.
  int TakePeak() { return peak_.exchange(0, std::memory_order_relaxed); }

  // Raises the peak to level if it is higher. Called by Process for each
  // decoded packet; public so the peak can be exercised without standing up a
  // decoder, the same reason RmsS16 and LevelFromRms are.
  void RaisePeak(int level);

  // --- Pure helpers, split out so the scale is testable without a decoder ----

  // Root-mean-square of interleaved or planar signed 16-bit samples, as a
  // fraction of full scale (0.0 .. 1.0).
  static double RmsS16(const int16_t *samples, size_t count);
  // Same, for the float formats the AAC and Opus decoders emit. Float samples
  // are nominally -1.0 .. 1.0 but decoders do overshoot, so this clamps.
  static double RmsFloat(const float *samples, size_t count);

  // Maps a full-scale fraction onto 0-100. Silence and anything below
  // AUDIO_FLOOR_DB give 0; full scale gives 100.
  static int LevelFromRms(double rms);

  // Whether a level should count as an alarm. A threshold of 0 means the
  // feature is off rather than "alarm on absolute silence", which is what a
  // naive >= comparison would give.
  static bool IsAlarm(int level, int threshold);

  // Whether anything wants a reading: the monitor scores on audio, or someone
  // asked for one and the request has not expired. request_until is a wall
  // clock second, 0 meaning nobody asked. Split out from Monitor so the
  // expiry rule is testable without a monitor or its shared memory.
  static bool LevelWanted(bool audio_detection, uint32_t request_until, uint32_t now);

 private:
  double RmsFromFrame(const AVFrame *frame) const;

  AVCodecContext *codec_context_ = nullptr;
  // The codec a decoder lookup already failed for, or AV_CODEC_ID_NONE.
  AVCodecID failed_codec_ = AV_CODEC_ID_NONE;
  // Written by the capture thread, read by the analysis thread.
  std::atomic<int> level_{0};
  // Written by the capture thread, taken and cleared by the analysis thread.
  std::atomic<int> peak_{0};
};

#endif // ZM_AUDIO_DETECTOR_H
