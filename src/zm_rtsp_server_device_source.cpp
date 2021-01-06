/* ---------------------------------------------------------------------------
** This software is in the public domain, furnished "as is", without technical
** support, and with no warranty, express or implied, as to its usefulness for
** any purpose.
**
** v4l2DeviceSource.cpp
**
** ZoneMinder Live555 source
**
** -------------------------------------------------------------------------*/

#include <fcntl.h>
#include <iomanip>
#include <sstream>

#include "zm_rtsp_server_device_source.h"
#include "zm_rtsp_server_frame.h"
#include "zm_logger.h"

// ---------------------------------
// ZoneMinder FramedSource Stats
// ---------------------------------
int ZoneMinderDeviceSource::Stats::notify(int tv_sec, int framesize) {
	m_fps++;
	m_size += framesize;
	if ( tv_sec != m_fps_sec ) {
		//LOG(INFO) << m_msg  << "tv_sec:" <<   tv_sec << " fps:" << m_fps << " bandwidth:"<< (m_size/128) << "kbps";
		m_fps_sec = tv_sec;
		m_fps = 0;
		m_size = 0;
	}
	return m_fps;
}

// Constructor
ZoneMinderDeviceSource::ZoneMinderDeviceSource(
    UsageEnvironment& env,
    Monitor* monitor,
    int outputFd,
    unsigned int queueSize,
    bool useThread) :
  FramedSource(env),
  packetBufferSize(0),
  packetBuffer(nullptr),
  packetBufferPtr(nullptr),
	m_in("in"),
	m_out("out") ,
	m_outfd(outputFd),
	m_monitor(monitor),
  m_packetqueue(nullptr),
  m_packetqueue_it(nullptr),
	m_queueSize(queueSize)
{
	m_eventTriggerId = envir().taskScheduler().createEventTrigger(ZoneMinderDeviceSource::deliverFrameStub);
	memset(&m_thid, 0, sizeof(m_thid));
	memset(&m_mutex, 0, sizeof(m_mutex));
	if ( m_monitor ) {
    m_packetqueue = m_monitor->GetPacketQueue();
    if ( !m_packetqueue ) {
      Fatal("No packetqueue");
    }
		if ( useThread ) {
			pthread_mutex_init(&m_mutex, nullptr);
			pthread_create(&m_thid, nullptr, threadStub, this);
		} else {
      Debug(1, "Not using thread");
			//envir().monitorScheduler().turnOnBackgroundReadHandling( m_monitor->getFd(), ZoneMinderDeviceSource::incomingPacketHandlerStub, this);
		}
  } else {
    Error("No monitor in ZoneMinderDeviceSource");
	}
}

// Destructor
ZoneMinderDeviceSource::~ZoneMinderDeviceSource() {
	envir().taskScheduler().deleteEventTrigger(m_eventTriggerId);
	pthread_join(m_thid, nullptr);
	pthread_mutex_destroy(&m_mutex);
}

// thread mainloop
void* ZoneMinderDeviceSource::thread() {
	int stop = 0;
	fd_set fdset;
	FD_ZERO(&fdset);

	while ( !stop ) {
		getNextFrame();
	}
	return nullptr;
}

// getting FrameSource callback
void ZoneMinderDeviceSource::doGetNextFrame() {
	deliverFrame();
}

// stopping FrameSource callback
void ZoneMinderDeviceSource::doStopGettingFrames() {
	//LOG(INFO) << "ZoneMinderDeviceSource::doStopGettingFrames";
	Debug(1, "ZoneMinderDeviceSource::doStopGettingFrames");
	FramedSource::doStopGettingFrames();
}

// deliver frame to the sink
void ZoneMinderDeviceSource::deliverFrame() {
	if ( isCurrentlyAwaitingData() ) {
		fDurationInMicroseconds = 0;
		fFrameSize = 0;

		pthread_mutex_lock(&m_mutex);
		if ( m_captureQueue.empty() ) {
			//LOG(INFO) << "Queue is empty";
      Debug(1, "Queue is empty");
		} else {
      Debug(1, "Queue is not empty");
			timeval curTime;
			gettimeofday(&curTime, nullptr);
			NAL_Frame *frame = m_captureQueue.front();
			m_captureQueue.pop_front();

			unsigned int nal_size = frame->size();
			m_out.notify(curTime.tv_sec, nal_size);

			if ( nal_size > fMaxSize ) {
				fFrameSize = fMaxSize;
				fNumTruncatedBytes = nal_size - fMaxSize;
			} else {
				fFrameSize = nal_size;
			}
			timeval diff;
			timersub(&curTime, &(frame->m_timestamp), &diff);

			//LOG(INFO) << "deliverFrame\ttimestamp:" << curTime.tv_sec << "." << curTime.tv_usec << "\tsize:" << fFrameSize <<"\tdiff:" <<  (diff.tv_sec*1000+diff.tv_usec/1000) << "ms\tqueue:" << m_captureQueue.size();

			fPresentationTime = frame->m_timestamp;
			memcpy(fTo, frame->buffer(), fFrameSize);
			delete frame;
		}
		pthread_mutex_unlock(&m_mutex);

		if ( fFrameSize > 0 ) {
			// send Frame to the consumer
			FramedSource::afterGetting(this);
		}
	}
}

// FrameSource callback on read event
void ZoneMinderDeviceSource::incomingPacketHandler() {
	if ( this->getNextFrame() <= 0 ) {
		handleClosure(this);
	}
}

// read from monitor
int ZoneMinderDeviceSource::getNextFrame() {
	timeval ref;
	gettimeofday(&ref, nullptr);

  if ( !m_packetqueue_it ) {
    m_packetqueue_it = m_packetqueue->get_video_it(true);
    return -1;
  }
  ZMPacket *zm_packet = m_packetqueue->get_packet(m_packetqueue_it);
  if ( !zm_packet ) {
    Debug(1, "null zm_packet %p", zm_packet);
    return -1;
  }
  // packet is locked
  AVPacket pkt = zm_packet->packet;
  m_packetqueue->increment_it(m_packetqueue_it);

  if ( !packetBufferSize ) {
    packetBufferSize = pkt.size * 2;
    Debug(1, "Initializing buffer space to %dbytes", packetBufferSize);
    packetBuffer = new unsigned char[packetBufferSize];
    packetBufferPtr = packetBuffer;
  } else {
    int bytesAvailable = packetBufferSize - (packetBufferPtr - packetBuffer);
    if ( bytesAvailable < pkt.size ) {
      // not enough space in buffer, so double it.
      int newPacketBufferSize = packetBufferSize * 2;
      if ( newPacketBufferSize < pkt.size )
        newPacketBufferSize = pkt.size * 2;

      Debug(1, "Doubling buffer space to %d . Available=%d, pkt.size=%d", newPacketBufferSize,
          bytesAvailable, pkt.size);
      unsigned char *newBuffer = new unsigned char[newPacketBufferSize];
      unsigned int bytesUsed = packetBufferPtr-packetBuffer;
      Debug(1, "Copying %d bytes as %p-%p", bytesUsed, packetBufferPtr, packetBuffer);
      memcpy(newBuffer, packetBuffer, bytesUsed);
      delete[] packetBuffer;
      packetBuffer = newBuffer;
      packetBufferPtr = packetBuffer + bytesUsed;
      packetBufferSize = newPacketBufferSize;
    } else {
      Debug(1, "Not Doubling buffer spaceCurrent size %d . Available=%d, pkt.size=%d", packetBufferSize,
          bytesAvailable, pkt.size);
    }
  }

  Debug(1, "Copying pkt data to %p. buffer start is %p, remaining buffer size %d",
      packetBufferPtr, packetBuffer, packetBufferPtr-packetBuffer);
  memcpy(packetBufferPtr, pkt.data, pkt.size);
  packetBufferPtr += pkt.size;
  zm_packet->unlock();

  size_t frame_size;
  size_t pkt_size = packetBufferPtr-packetBuffer;

  Debug(1, "Calling extractFrame. pkt size %d", pkt_size);
  unsigned char *data = this->extractFrame(packetBuffer, pkt_size, frame_size);

	if ( !data ) {
		///std::cerr << "No frame from get_h264_frame\n";
    Debug(1, "No frame from packet");
		return -1;
	}

	timeval tv;
	gettimeofday(&tv, nullptr);
  Debug(1, "Have nal frame at %p size %d. Remaining pktsize %d", data, frame_size, pkt_size);
  NAL_Frame *frame  = new NAL_Frame(data, frame_size, tv);
  //frame->check();
  zm_packet->unlock();

	timeval diff;
	timersub(&tv, &ref, &diff);
	m_in.notify(tv.tv_sec, frame->size());
	//m_in.notify(tv.tv_sec, frame->nal_size());
	//LOG(INFO) << "getNextFrame\ttimestamp:" << ref.tv_sec << "." << ref.tv_usec << "\tsize:" << frame->nal_size() <<"\tdiff:" <<  (diff.tv_sec*1000+diff.tv_usec/1000) << "ms";

	pthread_mutex_lock(&m_mutex);
	while ( m_captureQueue.size() >= m_queueSize ) {
		//LOG(DEBUG) << "Queue full size drop frame size:"  << (int)m_captureQueue.size() ;
    Debug(2, "Queue full dropping frame %d", m_captureQueue.size());
		NAL_Frame * f = m_captureQueue.front();
		m_captureQueue.pop_front();
    delete f;
	}
	m_captureQueue.push_back(frame);
	pthread_mutex_unlock(&m_mutex);

  if ( pkt_size ) {
    // Discard any bytes up to and including the frame.

    memmove(packetBuffer, data+frame_size, pkt_size);
    packetBufferPtr = packetBuffer + pkt_size;
    Debug(1, "Updated pkt data to %p. buffer start is %p, remaining buffer size %d bytesAfter%d",
        packetBufferPtr, packetBuffer, packetBufferPtr-packetBuffer, pkt_size);
  }

	// post an event to ask to deliver the frame
	envir().taskScheduler().triggerEvent(m_eventTriggerId, this);
  return 1;
}

// split packet in frames
std::list< std::pair<unsigned char*,size_t> > ZoneMinderDeviceSource::splitFrames(unsigned char* frame, unsigned frameSize) {
	std::list< std::pair<unsigned char*,size_t> > frameList;
	if ( frame != nullptr ) {
		frameList.push_back(std::pair<unsigned char*,size_t>(frame, frameSize));
	}
	return frameList;
}
