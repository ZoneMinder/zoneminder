/* ---------------------------------------------------------------------------
**
** FifoSource.h
** 
**  live555 source 
**
** -------------------------------------------------------------------------*/

#ifndef ZM_RTSP_SERVER_FIFO_SOURCE_H
#define ZM_RTSP_SERVER_FIFO_SOURCE_H

#include "zm_buffer.h"
#include "zm_config.h"
#include "zm_ffmpeg.h"
#include "zm_define.h"
#include <list>
#include <string>
#include <utility>

#if HAVE_RTSP_SERVER
#include "xop/RtspServer.h"

class ZoneMinderFifoSource {
		
	public:

    void Stop() { stop=1; };

    ZoneMinderFifoSource(
        std::shared_ptr<xop::RtspServer>& rtspServer,
        xop::MediaSessionId sessionId,
        xop::MediaChannelId channelId,
        std::string fifo
        );
		virtual ~ZoneMinderFifoSource();

	protected:	
		static void* threadStub(void* clientData) { return ((ZoneMinderFifoSource*) clientData)->thread();};
		void* thread();
		int getNextFrame();
    virtual void PushFrame(const uint8_t *data, size_t size, int64_t pts) = 0;
     // split packet in frames
    virtual std::list< std::pair<unsigned char*, size_t> > splitFrames(unsigned char* frame, size_t &frameSize);
    virtual unsigned char *extractFrame(unsigned char *data, size_t& size, size_t& outsize);

	protected:

    int m_width;
    int m_height;
		pthread_t m_thid;
		pthread_mutex_t m_mutex;
    int stop;

    std::shared_ptr<xop::RtspServer>& m_rtspServer;
    xop::MediaSessionId m_sessionId;
    xop::MediaChannelId m_channelId;
    std::string m_fifo;
    int m_fd;
    Buffer  m_buffer;
    AVRational m_timeBase;
};
#endif // HAVE_RTSP_SERVER

#endif // ZM_RTSP_SERVER_FIFO_SOURCE_H
