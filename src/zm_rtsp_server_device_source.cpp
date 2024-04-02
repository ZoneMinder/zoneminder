/* ---------------------------------------------------------------------------
** This software is in the public domain, furnished "as is", without technical
** support, and with no warranty, express or implied, as to its usefulness for
** any purpose.
**
**
** ZoneMinder Live555 source
**
** -------------------------------------------------------------------------*/

#include "zm_rtsp_server_device_source.h"

#include "zm_config.h"
#include "zm_logger.h"
#include "zm_rtsp_server_frame.h"
#include "zm_signal.h"

#if HAVE_RTSP_SERVER
ZoneMinderDeviceSource::ZoneMinderDeviceSource(
  UsageEnvironment& env,
  std::shared_ptr<Monitor> monitor,
  AVStream *stream,
  unsigned int queueSize
) :
  FramedSource(env),
  m_eventTriggerId(envir().taskScheduler().createEventTrigger(ZoneMinderDeviceSource::deliverFrameStub)),
  m_stream(stream),
  m_monitor(std::move(monitor)),
  m_packetqueue(nullptr),
  m_packetqueue_it(nullptr),
  m_queueSize(queueSize) {
  memset(&m_thid, 0, sizeof(m_thid));
  memset(&m_mutex, 0, sizeof(m_mutex));
  if ( m_monitor ) {
    m_packetqueue = m_monitor->GetPacketQueue();
    if ( !m_packetqueue ) {
      Fatal("No packetqueue");
    }
    pthread_mutex_init(&m_mutex, nullptr);
    pthread_create(&m_thid, nullptr, threadStub, this);
  } else {
    Error("No monitor in ZoneMinderDeviceSource");
  }
}

ZoneMinderDeviceSource::~ZoneMinderDeviceSource() {
  stop = 1;
  envir().taskScheduler().deleteEventTrigger(m_eventTriggerId);
  pthread_join(m_thid, nullptr);
  while ( m_captureQueue.size() ) {
    NAL_Frame * f = m_captureQueue.front();
    m_captureQueue.pop_front();
    delete f;
  }

  pthread_mutex_destroy(&m_mutex);
}

// thread mainloop
void* ZoneMinderDeviceSource::thread() {
  stop = 0;

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
  stop = 1;
  Debug(1, "ZoneMinderDeviceSource::doStopGettingFrames");
  FramedSource::doStopGettingFrames();
}

// deliver frame to the sink
void ZoneMinderDeviceSource::deliverFrame() {
  if ( !isCurrentlyAwaitingData() ) {
    Debug(4, "not awaiting data");
    return;
  }

  pthread_mutex_lock(&m_mutex);
  if ( m_captureQueue.empty() ) {
    Debug(4, "Queue is empty");
    pthread_mutex_unlock(&m_mutex);
    return;
  }

  NAL_Frame *frame = m_captureQueue.front();
  m_captureQueue.pop_front();
  pthread_mutex_unlock(&m_mutex);

  fDurationInMicroseconds = 0;
  fFrameSize = 0;

  unsigned int nal_size = frame->size();

  if ( nal_size > fMaxSize ) {
    fFrameSize = fMaxSize;
    fNumTruncatedBytes = nal_size - fMaxSize;
  } else {
    fFrameSize = nal_size;
  }
  Debug(2, "deliverFrame stream: %d timestamp: %ld.%06ld size: %d queuesize: %d",
        m_stream->index,
        frame->m_timestamp.tv_sec, frame->m_timestamp.tv_usec,
        fFrameSize,
        m_captureQueue.size()
       );

  fPresentationTime = frame->m_timestamp;
  memcpy(fTo, frame->buffer(), fFrameSize);

  if ( fFrameSize > 0 ) {
    // send Frame to the consumer
    FramedSource::afterGetting(this);
  }
  delete frame;
}  // end void ZoneMinderDeviceSource::deliverFrame()

// FrameSource callback on read event
void ZoneMinderDeviceSource::incomingPacketHandler() {
  if ( this->getNextFrame() <= 0 ) {
    handleClosure(this);
  }
}

// read from monitor
int ZoneMinderDeviceSource::getNextFrame() {
  if ( zm_terminate )
    return -1;

  if ( !m_packetqueue_it ) {
    m_packetqueue_it = m_packetqueue->get_video_it(true);
  }
  ZMPacket *zm_packet = m_packetqueue->get_packet(m_packetqueue_it);
  while ( zm_packet and (zm_packet->packet->stream_index != m_stream->index) ) {
    zm_packet->unlock();
    // We want our stream to start at the same it as the video
    // but if this is an audio stream we need to increment past that first packet
    Debug(4, "Have audio packet, skipping");
    m_packetqueue->increment_it(m_packetqueue_it, m_stream->index);
    zm_packet = m_packetqueue->get_packet(m_packetqueue_it);
  }
  if ( !zm_packet ) {
    Debug(1, "null zm_packet %p", zm_packet);
    return -1;
  }
  // packet is locked
  AVPacket *pkt = &zm_packet->packet;

  // Convert pts to timeval
  int64_t pts = av_rescale_q(pkt->dts, m_stream->time_base, AV_TIME_BASE_Q);
  timeval tv = { pts/1000000, pts%1000000 };
  ZM_DUMP_STREAM_PACKET(m_stream, (*pkt), "rtspServer");
  Debug(2, "pts %" PRId64 " pkt.pts %" PRId64 " tv %d.%d", pts, pkt->pts, tv.tv_sec, tv.tv_usec);

  std::list< std::pair<unsigned char*, size_t> > framesList = this->splitFrames(pkt->data, pkt->size);
  zm_packet->unlock();
  zm_packet = nullptr;// we no longer have the lock so shouldn't be accessing it
  m_packetqueue->increment_it(m_packetqueue_it, m_stream->index);

  while ( framesList.size() ) {
    std::pair<unsigned char*, size_t> nal = framesList.front();
    framesList.pop_front();

    NAL_Frame *frame  = new NAL_Frame(nal.first, nal.second, tv);

    pthread_mutex_lock(&m_mutex);
    if ( m_captureQueue.size() ) {
      NAL_Frame * f = m_captureQueue.front();
      while ( m_captureQueue.size() and ((f->m_timestamp.tv_sec - tv.tv_sec) > 10) ) {
        m_captureQueue.pop_front();
        delete f;
        f = m_captureQueue.front();
      }
    }
#if 0
    while ( m_captureQueue.size() >= m_queueSize ) {
      Debug(2, "Queue full dropping frame %d", m_captureQueue.size());
      NAL_Frame * f = m_captureQueue.front();
      m_captureQueue.pop_front();
      delete f;
    }
#endif
    m_captureQueue.push_back(frame);
    pthread_mutex_unlock(&m_mutex);

    // post an event to ask to deliver the frame
    envir().taskScheduler().triggerEvent(m_eventTriggerId, this);
  }  // end while we get frame from data
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

// extract a frame
unsigned char*  ZoneMinderDeviceSource::extractFrame(unsigned char* frame, size_t& size, size_t& outsize) {
  outsize = size;
  size = 0;
  return frame;
}
#endif // HAVE_RTSP_SERVER
