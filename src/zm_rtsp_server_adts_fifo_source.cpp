/* ---------------------------------------------------------------------------
**
** ADTS_FifoSource.cpp
**
** ADTS Live555 source
**
** -------------------------------------------------------------------------*/

#include "zm_logger.h"
#include "zm_rtsp_server_adts_fifo_source.h"

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
    ZoneMinderFifoSource(env, fifo, queueSize),
    samplingFrequencyIndex(11),
    channels(1)
{
  std::ostringstream os;
  os <<
    "profile-level-id=1;"
    "mode=AAC-hbr;sizelength=13;indexlength=3;"
    "indexdeltalength=3"
    << "\r\n";
  m_auxLine.assign(os.str());    
  Debug(1, "m_auxline is %s", m_auxLine.c_str());
}
#endif // HAVE_RTSP_SERVER
