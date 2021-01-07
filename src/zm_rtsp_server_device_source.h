/* ---------------------------------------------------------------------------
**
** DeviceSource.h
** 
**  live555 source 
**
** -------------------------------------------------------------------------*/


#ifndef DEVICE_SOURCE
#define DEVICE_SOURCE

#include <string>
#include <list> 
#include <iostream>
#include <iomanip>

#include <liveMedia.hh>

#include "zm_monitor.h"
#include "zm_rtsp_server_frame.h"
#include "zm_packetqueue.h"

#include <linux/types.h>
/*  Four-character-code (FOURCC) */
#define fourcc(a, b, c, d)\
    ((__u32)(a) | ((__u32)(b) << 8) | ((__u32)(c) << 16) | ((__u32)(d) << 24))

#define PIX_FMT_H264        fourcc('H', '2', '6', '4') /* H264 with start codes */
#define PIX_FMT_H264_NO_SC  fourcc('A', 'V', 'C', '1') /* H264 without start codes */
#define PIX_FMT_VP8         fourcc('V', 'P', '8', '0')
#define PIX_FMT_VP9         fourcc('V', 'P', '9', '0')
#define PIX_FMT_HEVC        fourcc('H', 'E', 'V', 'C')

class ZoneMinderDeviceSource: public FramedSource {
	public:
		
		// ---------------------------------
		// Compute simple stats
		// ---------------------------------
		class Stats {
			public:
				Stats(const std::string & msg) : m_fps(0), m_fps_sec(0), m_size(0), m_msg(msg) {};
				
			public:
				int notify(int tv_sec, int framesize);
			
			protected:
				int m_fps;
				int m_fps_sec;
				int m_size;
				const std::string m_msg;
		};
		
	public:
		static ZoneMinderDeviceSource* createNew(
        UsageEnvironment& env,
        Monitor* monitor,
        int stream_id,
        unsigned int queueSize,
        bool useThread);
		std::string getAuxLine() { return m_auxLine; };	
		int getWidth() { return m_monitor->Width(); };	
		int getHeight() { return m_monitor->Height(); };	

	protected:
		ZoneMinderDeviceSource(UsageEnvironment& env, Monitor* monitor, int stream_id, unsigned int queueSize, bool useThread);
		virtual ~ZoneMinderDeviceSource();

	protected:	
		static void* threadStub(void* clientData) { return ((ZoneMinderDeviceSource*) clientData)->thread();};
		void* thread();
		static void deliverFrameStub(void* clientData) {((ZoneMinderDeviceSource*) clientData)->deliverFrame();};
		void deliverFrame();
		static void incomingPacketHandlerStub(void* clientData, int mask) { ((ZoneMinderDeviceSource*) clientData)->incomingPacketHandler(); };
		void incomingPacketHandler();
		int getNextFrame();
		void processFrame(char * frame, int frameSize, const timeval &ref);
		void queueFrame(char * frame, int frameSize, const timeval &tv);

		// split packet in frames
		virtual std::list< std::pair<unsigned char*, size_t> > splitFrames(unsigned char* frame, unsigned frameSize);
		
		// overide FramedSource
		virtual void doGetNextFrame();	
		virtual void doStopGettingFrames();
    virtual unsigned char *extractFrame(unsigned char *data, size_t& size, size_t& outsize) = 0;
					
	protected:
    unsigned int packetBufferSize;
    unsigned char *packetBuffer; // buffer where we copy packet.data and looks for NALs
    unsigned char *packetBufferPtr; // ptr into the buffer where we write new data

		std::list<NAL_Frame*> m_captureQueue;
		Stats m_in;
		Stats m_out;
		EventTriggerId m_eventTriggerId;
		int m_stream_id;
		Monitor* m_monitor;
    zm_packetqueue *m_packetqueue;
    std::list<ZMPacket *>::iterator *m_packetqueue_it;

		unsigned int m_queueSize;
		pthread_t m_thid;
		pthread_mutex_t m_mutex;
		std::string m_auxLine;
};

#endif
