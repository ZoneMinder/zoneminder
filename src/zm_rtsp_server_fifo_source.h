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
#include "zm_define.h"
#include <list>
#include <string>
#include <utility>

#if HAVE_RTSP_SERVER
#include <liveMedia.hh>

class NAL_Frame;

class ZoneMinderFifoSource: public FramedSource {
		
	public:
		static ZoneMinderFifoSource* createNew(
        UsageEnvironment& env,
        std::string fifo,
        unsigned int queueSize
        ) {
			return new ZoneMinderFifoSource(env, fifo, queueSize);
    };
		std::string getAuxLine() { return m_auxLine; };	
		int getWidth() { return m_width; };	
		int getHeight() { return m_height; };	
		int setWidth(int width) { return m_width=width; };	
		int setHeight(int height) { return m_height=height; };	

	protected:
		ZoneMinderFifoSource(UsageEnvironment& env, std::string fifo, unsigned int queueSize);
		virtual ~ZoneMinderFifoSource();

	protected:	
		static void* threadStub(void* clientData) { return ((ZoneMinderFifoSource*) clientData)->thread();};
		void* thread();
		static void deliverFrameStub(void* clientData) {((ZoneMinderFifoSource*) clientData)->deliverFrame();};
		void deliverFrame();
		static void incomingPacketHandlerStub(void* clientData, int mask) { ((ZoneMinderFifoSource*) clientData)->incomingPacketHandler(); };
		void incomingPacketHandler();
		int getNextFrame();
		void processFrame(char * frame, int frameSize, const timeval &ref);
		void queueFrame(char * frame, int frameSize, const timeval &tv);

		// split packet in frames
		virtual std::list< std::pair<unsigned char*, size_t> > splitFrames(unsigned char* frame, unsigned &frameSize);
		
		// overide FramedSource
		virtual void doGetNextFrame();	
		virtual void doStopGettingFrames();
    virtual unsigned char *extractFrame(unsigned char *data, size_t& size, size_t& outsize);
					
	protected:
		std::list<NAL_Frame*> m_captureQueue;
		EventTriggerId m_eventTriggerId;
    std::string m_fifo;

    int m_width;
    int m_height;
		unsigned int m_queueSize;
		pthread_t m_thid;
		pthread_mutex_t m_mutex;
		std::string m_auxLine;
    int stop;

    int m_fd;
    Buffer  m_buffer;
};
#endif // HAVE_RTSP_SERVER

#endif // ZM_RTSP_SERVER_FIFO_SOURCE_H
