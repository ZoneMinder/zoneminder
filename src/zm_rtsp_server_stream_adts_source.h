/* ---------------------------------------------------------------------------
** This software is in the public domain, furnished "as is", without technical
** support, and with no warranty, express or implied, as to its usefulness for
** any purpose.
**
** ADTS_ZoneMinderStreamSource.h
**
** ADTS ZoneMinder live555 source
**
** -------------------------------------------------------------------------*/

#ifndef ZM_RTSP_SERVER_STREAM_ADTS_SOURCE_H
#define ZM_RTSP_SERVER_STREAM_ADTS_SOURCE_H

#include "zm_config.h"
#include "zm_rtsp_server_stream_audio_source.h"

#if HAVE_RTSP_SERVER
// ---------------------------------
// ADTS(AAC) ZoneMinder FramedSource
// ---------------------------------

class ADTS_ZoneMinderStreamSource : public ZoneMinderStreamAudioSource {
 public:
  ADTS_ZoneMinderStreamSource(
    std::shared_ptr<xop::RtspServer>& rtspServer,
    xop::MediaSessionId sessionId,
    xop::MediaChannelId channelId
  );

  virtual ~ADTS_ZoneMinderStreamSource() {}
};
#endif // HAVE_RTSP_SERVER

#endif // ZM_RTSP_SERVER_STREAM_ADTS_SOURCE_H
