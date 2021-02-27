/* ---------------------------------------------------------------------------
** This software is in the public domain, furnished "as is", without technical
** support, and with no warranty, express or implied, as to its usefulness for
** any purpose.
**
**
** ZoneMinder Live555 source
**
** -------------------------------------------------------------------------*/

#include "zm_rtsp_server_fifo_video_source.h"

#if HAVE_RTSP_SERVER
ZoneMinderFifoVideoSource::ZoneMinderFifoVideoSource(
    UsageEnvironment& env, std::string fifo, unsigned int queueSize) :
  ZoneMinderFifoSource(env,fifo,queueSize)
{
}

#endif // HAVE_RTSP_SERVER
