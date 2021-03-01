/* ---------------------------------------------------------------------------
**
** ADTS_FifoSource.cpp
**
** ADTS Live555 source
**
** -------------------------------------------------------------------------*/

#include "zm_logger.h"
#include "zm_rtsp_server_fifo_audio_source.h"

#include <sstream>

#if HAVE_RTSP_SERVER

static unsigned const samplingFrequencyTable[16] = {
  96000, 88200, 64000, 48000,
  44100, 32000, 24000, 22050,
  16000, 12000, 11025, 8000,
  7350, 0, 0, 0
};
// ---------------------------------
// ADTS ZoneMinder FramedSource
// ---------------------------------
//
ZoneMinderFifoAudioSource::ZoneMinderFifoAudioSource(
    UsageEnvironment& env,
    std::string fifo,
    unsigned int queueSize
    )
  :
    ZoneMinderFifoSource(env, fifo, queueSize),
    samplingFrequencyIndex(-1),
    frequency(-1),
    channels(1)
{
}
#endif // HAVE_RTSP_SERVER
