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

#include <liveMedia.hh>

#include "zm_monitor.h"
#include "zm_rtsp_server_frame.h"
#include "zm_packetqueue.h"

#include <linux/types.h>

class ZoneMinderDeviceSource: public FramedSource {
		
	public:
		static ZoneMinderDeviceSource* createNew(
        UsageEnvironment& env,
        Monitor* monitor,
        AVStream * stream,
        unsigned int queueSize,
        bool useThread);
		std::string getAuxLine() { return m_auxLine; };	
		int getWidth() { return m_monitor->Width(); };	
		int getHeight() { return m_monitor->Height(); };	

	protected:
		ZoneMinderDeviceSource(UsageEnvironment& env, Monitor* monitor, AVStream * stream, unsigned int queueSize);
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
		std::list<NAL_Frame*> m_captureQueue;
		EventTriggerId m_eventTriggerId;
    AVStream *m_stream;
		Monitor* m_monitor;
    zm_packetqueue *m_packetqueue;
    std::list<ZMPacket *>::iterator *m_packetqueue_it;

		unsigned int m_queueSize;
		pthread_t m_thid;
		pthread_mutex_t m_mutex;
		std::string m_auxLine;
    int stop;
};

#endif
