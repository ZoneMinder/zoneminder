/* ---------------------------------------------------------------------------
** This software is in the public domain, furnished "as is", without technical
** support, and with no warranty, express or implied, as to its usefulness for
** any purpose.
**
** H264_ZoneMinderFifoSource.h
**
** H264 ZoneMinder live555 source
**
** -------------------------------------------------------------------------*/

#ifndef ZM_RTSP_H264_FIFO_SOURCE_H
#define ZM_RTSP_H264_FIFO_SOURCE_H

#include "zm_config.h"
#include "zm_rtsp_server_fifo_video_source.h"

// ---------------------------------
// H264 ZoneMinder FramedSource
// ---------------------------------
#if HAVE_RTSP_SERVER
class H26X_ZoneMinderFifoSource : public ZoneMinderFifoVideoSource {
	protected:
		H26X_ZoneMinderFifoSource(
        UsageEnvironment& env,
        std::string fifo,
        unsigned int queueSize,
        bool repeatConfig,
        bool keepMarker)
			:
        ZoneMinderFifoVideoSource(env, fifo, queueSize),
        m_repeatConfig(repeatConfig),
        m_keepMarker(keepMarker),
        m_frameType(0) { }

		virtual ~H26X_ZoneMinderFifoSource() {}

		virtual unsigned char* extractFrame(unsigned char* frame, size_t& size, size_t& outsize);
    virtual unsigned char* findMarker(unsigned char *frame, size_t size, size_t &length);

	protected:
		std::string m_sps;
		std::string m_pps;
		bool        m_repeatConfig;
		bool        m_keepMarker;
		int         m_frameType;
};

class H264_ZoneMinderFifoSource : public H26X_ZoneMinderFifoSource {
	public:
		static H264_ZoneMinderFifoSource* createNew(
				UsageEnvironment& env,
        std::string fifo,
				unsigned int queueSize,
				bool repeatConfig,
				bool keepMarker) {
			return new H264_ZoneMinderFifoSource(env, fifo, queueSize, repeatConfig, keepMarker);
		}

	protected:
		H264_ZoneMinderFifoSource(
        UsageEnvironment& env,
        std::string fifo,
        unsigned int queueSize,
        bool repeatConfig,
        bool keepMarker);

		// overide ZoneMinderFifoSource
		virtual std::list< std::pair<unsigned char*,size_t> > splitFrames(unsigned char* frame, unsigned &frameSize);
};

class H265_ZoneMinderFifoSource : public H26X_ZoneMinderFifoSource {
	public:
		static H265_ZoneMinderFifoSource* createNew(
        UsageEnvironment& env,
        std::string fifo,
        unsigned int queueSize,
        bool repeatConfig,
        bool keepMarker) {
			return new H265_ZoneMinderFifoSource(env, fifo, queueSize, repeatConfig, keepMarker);
		}

	protected:
		H265_ZoneMinderFifoSource(
        UsageEnvironment& env,
        std::string fifo,
        unsigned int queueSize,
        bool repeatConfig,
        bool keepMarker);

		// overide ZoneMinderFifoSource
		virtual std::list< std::pair<unsigned char*,size_t> > splitFrames(unsigned char* frame, unsigned frameSize);

	protected:
		std::string m_vps;
};
#endif // HAVE_RTSP_SERVER

#endif // ZM_RTSP_H264_FIFO_SOURCE_H
