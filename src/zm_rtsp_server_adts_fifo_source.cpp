/* ---------------------------------------------------------------------------
**
** ADTS_FifoSource.cpp
**
** ADTS Live555 source
**
** -------------------------------------------------------------------------*/

#include "zm_logger.h"
#include "zm_rtsp_server_adts_fifo_source.h"

#include <iomanip>
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
ADTS_ZoneMinderFifoSource::ADTS_ZoneMinderFifoSource(
    UsageEnvironment& env,
    std::string fifo,
    unsigned int queueSize
    )
  :
    ZoneMinderFifoAudioSource(env, fifo, queueSize)
{
#if 1
  int profile = 0;

  unsigned char audioSpecificConfig[2];
  u_int8_t const audioObjectType = profile + 1;
  audioSpecificConfig[0] = (audioObjectType<<3) | (samplingFrequencyIndex>>1);
  audioSpecificConfig[1] = (samplingFrequencyIndex<<7) | (channels<<3);

  std::ostringstream os;
  os <<
    "profile-level-id=1;"
    "mode=AAC-hbr;sizelength=13;indexlength=3;"
    "indexdeltalength=3;config=" << std::hex << std::setw(2) << std::setfill('0') << audioSpecificConfig[0]
    << std::hex << std::setw(2) << std::setfill('0') << audioSpecificConfig[1]
    << "\r\n";
  // Construct the 'AudioSpecificConfig', and from it, the corresponding ASCII string:

  m_auxLine.assign(os.str());    
  Debug(1, "m_auxline is %s", m_auxLine.c_str());
#endif
}
#endif // HAVE_RTSP_SERVER
