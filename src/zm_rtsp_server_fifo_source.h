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
#include "zm_rtsp_server_frame.h"
#include <list>
#include <string>
#include <thread>
#include <condition_variable>
#include <utility>

#if HAVE_RTSP_SERVER
#include "xop/RtspServer.h"

class ZoneMinderFifoSource {
		
	public:

    void Stop() {
      stop_ = true;
      condition_.notify_all();
    };

    ZoneMinderFifoSource(
        std::shared_ptr<xop::RtspServer>& rtspServer,
        xop::MediaSessionId sessionId,
        xop::MediaChannelId channelId,
        const std::string &fifo
        );
		virtual ~ZoneMinderFifoSource();

	protected:	
		void ReadRun();
		void WriteRun();

		int getNextFrame();
    virtual void PushFrame(const uint8_t *data, size_t size, int64_t pts) = 0;
     // split packet in frames
    virtual std::list< std::pair<unsigned char*, size_t> > splitFrames(unsigned char* frame, size_t &frameSize);
    virtual unsigned char *extractFrame(unsigned char *data, size_t& size, size_t& outsize);

	protected:

    std::mutex  mutex_;
    std::condition_variable condition_;

    std::thread read_thread_;
    std::thread write_thread_;
    std::atomic<bool> stop_;

    std::shared_ptr<xop::RtspServer>& m_rtspServer;
    xop::MediaSessionId m_sessionId;
    xop::MediaChannelId m_channelId;
    std::string m_fifo;
    int m_fd;
    Buffer  m_buffer;
    AVRational m_timeBase;
    std::queue<NAL_Frame *> m_nalQueue;
    int m_hType;
};
#endif // HAVE_RTSP_SERVER

#endif // ZM_RTSP_SERVER_FIFO_SOURCE_H
