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

#include "zm_audio_detector.h"

#include <cmath>
#include <vector>

namespace {

// A full-scale square wave: every sample at the same magnitude, so its RMS is
// exactly that magnitude and the expected level can be reasoned about rather
// than measured.
std::vector<int16_t> SquareS16(int16_t amplitude, size_t count) {
  std::vector<int16_t> samples(count);
  for (size_t i = 0; i < count; i++)
    samples[i] = (i % 2) ? amplitude : static_cast<int16_t>(-amplitude);
  return samples;
}

}  // namespace

TEST_CASE("Audio RMS of signed 16-bit samples") {
  SECTION("silence is zero") {
    const std::vector<int16_t> silence(128, 0);
    REQUIRE(AudioDetector::RmsS16(silence.data(), silence.size()) == 0.0);
  }

  SECTION("a full scale square wave is full scale") {
    const auto samples = SquareS16(32767, 64);
    REQUIRE(AudioDetector::RmsS16(samples.data(), samples.size()) == Catch::Approx(1.0).margin(0.001));
  }

  SECTION("the most negative sample does not exceed full scale") {
    // -32768 over 32767 would give a ratio above 1.0 and, once squared and
    // fed to log10, a positive dB reading for a legal sample.
    const std::vector<int16_t> samples(16, -32768);
    REQUIRE(AudioDetector::RmsS16(samples.data(), samples.size()) <= 1.0);
  }

  SECTION("halving the amplitude halves the rms") {
    const auto loud = SquareS16(16384, 64);
    const auto quiet = SquareS16(8192, 64);
    REQUIRE(AudioDetector::RmsS16(loud.data(), loud.size()) ==
            Catch::Approx(2.0 * AudioDetector::RmsS16(quiet.data(), quiet.size())));
  }

  SECTION("no samples is zero, not a division by zero") {
    REQUIRE(AudioDetector::RmsS16(nullptr, 0) == 0.0);
    const std::vector<int16_t> samples(4, 1000);
    REQUIRE(AudioDetector::RmsS16(samples.data(), 0) == 0.0);
  }
}

TEST_CASE("Audio RMS of float samples") {
  SECTION("silence is zero") {
    const std::vector<float> silence(64, 0.0f);
    REQUIRE(AudioDetector::RmsFloat(silence.data(), silence.size()) == 0.0);
  }

  SECTION("full scale is one") {
    const std::vector<float> samples(64, 1.0f);
    REQUIRE(AudioDetector::RmsFloat(samples.data(), samples.size()) == Catch::Approx(1.0));
  }

  SECTION("decoder overshoot is clamped rather than scoring above full scale") {
    // Float decoders are allowed to emit values outside -1..1; without the
    // clamp a hot AAC stream would report a level above 100.
    const std::vector<float> samples(64, 4.0f);
    REQUIRE(AudioDetector::RmsFloat(samples.data(), samples.size()) == Catch::Approx(1.0));
  }
}

TEST_CASE("Audio level scale") {
  SECTION("silence and sub-floor signals are zero") {
    REQUIRE(AudioDetector::LevelFromRms(0.0) == 0);
    // -80 dBFS, well under the -60 floor.
    REQUIRE(AudioDetector::LevelFromRms(0.0001) == 0);
  }

  SECTION("full scale is 100") {
    REQUIRE(AudioDetector::LevelFromRms(1.0) == 100);
  }

  SECTION("the floor itself is the bottom of the scale") {
    const double floor_rms = std::pow(10.0, AudioDetector::AUDIO_FLOOR_DB / 20.0);
    REQUIRE(AudioDetector::LevelFromRms(floor_rms) == 0);
  }

  SECTION("half the floor in dB is half the scale") {
    // -30 dBFS is the midpoint of a -60..0 range, so it must land on 50.
    REQUIRE(AudioDetector::LevelFromRms(std::pow(10.0, -30.0 / 20.0)) == 50);
  }

  SECTION("the scale is monotonic and never leaves 0..100") {
    int previous = -1;
    for (int db = -70; db <= 0; db++) {
      const int level = AudioDetector::LevelFromRms(std::pow(10.0, db / 20.0));
      REQUIRE(level >= 0);
      REQUIRE(level <= 100);
      REQUIRE(level >= previous);
      previous = level;
    }
  }

  SECTION("ordinary speech lands somewhere usable, not pinned at the bottom") {
    // Roughly 2% of full scale. On a linear scale this would be level 2 and
    // indistinguishable from noise; the whole point of the dB mapping is that
    // it is not.
    const int level = AudioDetector::LevelFromRms(0.02);
    REQUIRE(level > 30);
    REQUIRE(level < 50);
  }

  SECTION("a value above full scale still cannot exceed 100") {
    REQUIRE(AudioDetector::LevelFromRms(2.0) == 100);
  }
}

TEST_CASE("Audio alarm threshold") {
  SECTION("a level at or above the threshold alarms") {
    REQUIRE(AudioDetector::IsAlarm(40, 40));
    REQUIRE(AudioDetector::IsAlarm(41, 40));
  }

  SECTION("a quieter level does not") {
    REQUIRE_FALSE(AudioDetector::IsAlarm(39, 40));
    REQUIRE_FALSE(AudioDetector::IsAlarm(0, 40));
  }

  SECTION("a threshold of zero means off, not alarm on silence") {
    // The naive level >= threshold test makes an unconfigured monitor alarm on
    // every single audio packet, which is the worst possible default.
    REQUIRE_FALSE(AudioDetector::IsAlarm(0, 0));
    REQUIRE_FALSE(AudioDetector::IsAlarm(100, 0));
    REQUIRE_FALSE(AudioDetector::IsAlarm(50, -1));
  }
}

TEST_CASE("Audio detector with no decoder open") {
  AudioDetector detector;

  SECTION("reports no level and refuses to open a null stream") {
    REQUIRE_FALSE(detector.IsOpen());
    REQUIRE_FALSE(detector.Open(nullptr));
    REQUIRE(detector.Level() == 0);
  }

  SECTION("processing without a decoder is a no-op rather than a crash") {
    REQUIRE(detector.Process(nullptr) == 0);
    REQUIRE(detector.Level() == 0);
  }
}
