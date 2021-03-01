/* ---------------------------------------------------------------------------
** This software is in the public domain, furnished "as is", without technical
** support, and with no warranty, express or implied, as to its usefulness for
** any purpose.
**
**
** ZoneMinder Live555 source
**
** -------------------------------------------------------------------------*/

#include "zm_rtsp_server_fifo_source.h"

#include "zm_config.h"
#include "zm_logger.h"
#include "zm_rtsp_server_frame.h"
#include "zm_signal.h"

#include <fcntl.h>
#include <sys/file.h>

#if HAVE_RTSP_SERVER
ZoneMinderFifoSource::ZoneMinderFifoSource(
    UsageEnvironment& env,
    std::string fifo,
    unsigned int queueSize
    ) :
  FramedSource(env),
	m_fifo(fifo),
	m_queueSize(queueSize),
  m_fd(-1)
{
	m_eventTriggerId = envir().taskScheduler().createEventTrigger(ZoneMinderFifoSource::deliverFrameStub);
	memset(&m_thid, 0, sizeof(m_thid));
	memset(&m_mutex, 0, sizeof(m_mutex));
  pthread_mutex_init(&m_mutex, nullptr);
  pthread_create(&m_thid, nullptr, threadStub, this);
}

ZoneMinderFifoSource::~ZoneMinderFifoSource() {
  Debug(1, "Deleting Fifo Source");
  stop = 1;
	envir().taskScheduler().deleteEventTrigger(m_eventTriggerId);
	pthread_join(m_thid, nullptr);
  while (m_captureQueue.size()) {
    NAL_Frame * f = m_captureQueue.front();
    m_captureQueue.pop_front();
    delete f;
  }
	pthread_mutex_destroy(&m_mutex);
}

// thread mainloop
void* ZoneMinderFifoSource::thread() {
	stop = 0;

	while (!stop) {
		getNextFrame();
	}
	return nullptr;
}

// getting FrameSource callback
void ZoneMinderFifoSource::doGetNextFrame() {
	deliverFrame();
}

// stopping FrameSource callback
void ZoneMinderFifoSource::doStopGettingFrames() {
  //stop = 1;
	Debug(1, "ZoneMinderFifoSource::doStopGettingFrames");
	FramedSource::doStopGettingFrames();
}

// deliver frame to the sink
void ZoneMinderFifoSource::deliverFrame() {
	if (!isCurrentlyAwaitingData()) {
    Debug(5, "not awaiting data");
    return;
  } 

  pthread_mutex_lock(&m_mutex);
  if (m_captureQueue.empty()) {
    Debug(5, "Queue is empty");
    pthread_mutex_unlock(&m_mutex);
    return;
  }

  NAL_Frame *frame = m_captureQueue.front();
  m_captureQueue.pop_front();
  pthread_mutex_unlock(&m_mutex);

  fDurationInMicroseconds = 0;
  fFrameSize = 0;

  unsigned int nal_size = frame->size();

  if (nal_size > fMaxSize) {
    fFrameSize = fMaxSize;
    fNumTruncatedBytes = nal_size - fMaxSize;
  } else {
    fFrameSize = nal_size;
  }

  Debug(4, "deliverFrame timestamp: %ld.%06ld size: %d queuesize: %d",
      frame->m_timestamp.tv_sec, frame->m_timestamp.tv_usec,
      fFrameSize, 
      m_captureQueue.size()
      );

  fPresentationTime = frame->m_timestamp;
  memcpy(fTo, frame->buffer(), fFrameSize);

  if (fFrameSize > 0) {
    // send Frame to the consumer
    FramedSource::afterGetting(this);
  }
  delete frame;
}  // end void ZoneMinderFifoSource::deliverFrame()

// FrameSource callback on read event
void ZoneMinderFifoSource::incomingPacketHandler() {
	if (this->getNextFrame() <= 0) {
		handleClosure(this);
	}
}

// read from monitor
int ZoneMinderFifoSource::getNextFrame() {
  if (zm_terminate) return -1;

  if (m_fd == -1) {
    Debug(1, "Opening fifo %s", m_fifo.c_str());
    m_fd = open(m_fifo.c_str(), O_RDONLY);
    if (m_fd < 0) {
      Error("Can't open %s: %s", m_fifo.c_str(), strerror(errno));
      return -1;
    }
  }

  int bytes_read = m_buffer.read_into(m_fd, 4096);
  if (bytes_read == 0) {
    Debug(3, "No bytes read");
    sleep(1);
    return -1;
  }
  if (bytes_read < 0) {
    Error("Problem during reading: %s", strerror(errno));
    ::close(m_fd);
    m_fd = -1;
    return -1;
  }

  Debug(4, "%s bytes read %d bytes, buffer size %u", m_fifo.c_str(), bytes_read, m_buffer.size());
  while (m_buffer.size()) {

    unsigned int data_size = 0;
    int64_t pts;
    unsigned char *header_end = nullptr;
    unsigned char *header_start = nullptr;

    if ((header_start = (unsigned char *)memmem(m_buffer.head(), m_buffer.size(), "ZM", 2))) {
      // next step, look for \n
      header_end = (unsigned char *)memchr(header_start, '\n', m_buffer.tail()-header_start);
      if (!header_end) {
        // Must not have enough data.  So... keep all.
        Debug(1, "Didn't find newline");
        return -1;
      }

      unsigned int header_size = header_end-header_start;
      char *header = new char[header_size+1];
      header[header_size] = '\0';
      strncpy(header, reinterpret_cast<const char *>(header_start), header_end-header_start);

      char *content_length_ptr = strchr(header, ' ');
      if (!content_length_ptr) {
        Debug(1, "Didn't find space delineating size in %s", header);
        m_buffer.consume(header_start-m_buffer.head() + 2);
        delete header;
        return -1;
      }
      *content_length_ptr = '\0';
      content_length_ptr ++;
      char *pts_ptr = strchr(content_length_ptr, ' ');
      if (!pts_ptr) {
        m_buffer.consume(header_start-m_buffer.head() + 2);
        Warning("Didn't find space delineating pts");
        delete header;
        return -1;
      }
      *pts_ptr = '\0';
      pts_ptr ++;
      data_size = atoi(content_length_ptr);
      pts = strtoll(pts_ptr, nullptr, 10);
      delete header;
    } else {
      Debug(1, "ZM header not found.");
      return -1;
    }
    Debug(4, "ZM Packet size %u pts %" PRId64, data_size, pts);
    if (header_start != m_buffer) {
      Debug(4, "ZM Packet didn't start at beginning of buffer %u. %c%c",
          header_start-m_buffer.head(), m_buffer[0], m_buffer[1]);
    }
    unsigned char *packet_start = header_end+1;
    unsigned int header_size = packet_start - m_buffer.head(); // includes any bytes before header

    int bytes_needed = data_size - (m_buffer.size() - header_size);
    if (bytes_needed > 0) {
      Debug(4, "Need another %d bytes. Trying to read them", bytes_needed);
      int bytes_read = m_buffer.read_into(m_fd, bytes_needed);
      if ( bytes_read != bytes_needed )
        return -1;
    }

    // splitFrames modifies so make a copy
    unsigned int bytes_remaining = data_size;
    std::list< std::pair<unsigned char*, size_t> > framesList = this->splitFrames(packet_start, bytes_remaining);
    Debug(3, "Got %d frames, consuming %d bytes", framesList.size(), header_size + data_size);
    m_buffer.consume(header_size + data_size);

    timeval tv;
    tv.tv_sec  = pts / 1000000;
    tv.tv_usec = pts % 1000000;

    while (framesList.size()) {
      std::pair<unsigned char*, size_t> nal = framesList.front();
      framesList.pop_front();

      NAL_Frame *frame  = new NAL_Frame(nal.first, nal.second, tv);
      Debug(3, "Got frame, size %d, queue_size %d", frame->size(), m_captureQueue.size());

      pthread_mutex_lock(&m_mutex);
      if (m_captureQueue.size() > 25) {  // 1 sec at 25 fps
        NAL_Frame * f = m_captureQueue.front();
        while (m_captureQueue.size() and ((f->m_timestamp.tv_sec - tv.tv_sec) > 2)) {
          m_captureQueue.pop_front();
          delete f;
          f = m_captureQueue.front();
        }
      }
      m_captureQueue.push_back(frame);
      pthread_mutex_unlock(&m_mutex);

      // post an event to ask to deliver the frame
      envir().taskScheduler().triggerEvent(m_eventTriggerId, this);
    }  // end while we get frame from data
  } // end while m_buffer.size()
  return 1;
}

// split packet in frames
std::list< std::pair<unsigned char*,size_t> > ZoneMinderFifoSource::splitFrames(unsigned char* frame, unsigned &frameSize) {
	std::list< std::pair<unsigned char*,size_t> > frameList;
	if (frame != nullptr) {
		frameList.push_back(std::pair<unsigned char*,size_t>(frame, frameSize));
	}
  // We consume it all
  frameSize = 0;
	return frameList;
}

// extract a frame
unsigned char*  ZoneMinderFifoSource::extractFrame(unsigned char* frame, size_t& size, size_t& outsize) {
  outsize = size;
  size = 0;
  return frame;
}
#endif // HAVE_RTSP_SERVER
