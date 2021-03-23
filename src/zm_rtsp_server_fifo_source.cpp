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
#include "zm_ffmpeg.h"
#include "zm_logger.h"
#include "zm_signal.h"

#include <fcntl.h>
#include <sys/file.h>

#if HAVE_RTSP_SERVER
ZoneMinderFifoSource::ZoneMinderFifoSource(
    std::shared_ptr<xop::RtspServer>& rtspServer,
    xop::MediaSessionId sessionId,
    xop::MediaChannelId channelId,
    std::string fifo
    ) :
  stop_(false),
  m_rtspServer(rtspServer),
  m_sessionId(sessionId),
  m_channelId(channelId),
	m_fifo(fifo),
  m_fd(-1)
{
  read_thread_ = std::thread(&ZoneMinderFifoSource::ReadRun, this);
  write_thread_ = std::thread(&ZoneMinderFifoSource::WriteRun, this);
}

ZoneMinderFifoSource::~ZoneMinderFifoSource() {
  Debug(1, "Deleting Fifo Source");
    Stop();
  if (read_thread_.joinable())
    read_thread_.join();
  if (write_thread_.joinable())
    write_thread_.join();
  Debug(1, "Deleting Fifo Source done");
}

// thread mainloop
void ZoneMinderFifoSource::ReadRun() {
  if (stop_) Warning("bad value for stop_ in ReadRun");
	while (!stop_) {
		if (getNextFrame() < 0) {
      Debug(1, "Sleeping");
      sleep(1);
    }
	}
}
void ZoneMinderFifoSource::WriteRun() {
  if (stop_) Warning("bad value for stop_ in WriteRun");
	while (!stop_) {
    std::unique_lock<std::mutex> lck(mutex_);
    if (m_nalQueue.empty()) {
      Debug(3, "waiting");
      condition_.wait(lck);
    }

    while (!m_nalQueue.empty()) {
      NAL_Frame *nal = m_nalQueue.front();
      m_nalQueue.pop();

      Debug(3, "Pushing nal of size %d at %" PRId64, nal->size(), nal->pts());
      PushFrame(nal->buffer(), nal->size(), nal->pts());
      Debug(3, "Done Pushing nal");
      delete nal;
    }
	}
}

// read from monitor
int ZoneMinderFifoSource::getNextFrame() {
  if (zm_terminate or stop_) {
    Debug(1, "Terminating %d %d", zm_terminate, (stop_==true?1:0));
    return -1;
  }

  if (m_fd == -1) {
    Debug(1, "Opening fifo %s", m_fifo.c_str());
    m_fd = open(m_fifo.c_str(), O_RDONLY);
    if (m_fd < 0) {
      Error("Can't open %s: %s", m_fifo.c_str(), strerror(errno));
      return -1;
    }
  }

  int bytes_read = m_buffer.read_into(m_fd, 4096, {1,0});
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

  Debug(3, "%s bytes read %d bytes, buffer size %u", m_fifo.c_str(), bytes_read, m_buffer.size());
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
        Debug(1, "Didn't find newline buffer size is %d", m_buffer.size());
        return 0;
      }

      unsigned int header_size = header_end-header_start;
      char *header = new char[header_size+1];
      strncpy(header, reinterpret_cast<const char *>(header_start), header_size);
      header[header_size] = '\0';

      char *content_length_ptr = strchr(header, ' ');
      if (!content_length_ptr) {
        Debug(1, "Didn't find space delineating size in %s", header);
        m_buffer.consume(header_start-m_buffer.head() + 2);
        delete[] header;
        return 0;
      }
      *content_length_ptr = '\0';
      content_length_ptr ++;
      char *pts_ptr = strchr(content_length_ptr, ' ');
      if (!pts_ptr) {
        m_buffer.consume(header_start-m_buffer.head() + 2);
        Debug(1, "Didn't find space delineating pts in %s", header);
        delete[] header;
        return 0;
      }
      *pts_ptr = '\0';
      pts_ptr ++;
      data_size = atoi(content_length_ptr);
      pts = strtoll(pts_ptr, nullptr, 10);
      Debug(4, "ZM Packet %s header_size %d packet size %u pts %" PRId64, header, header_size, data_size, pts);
      delete[] header;
    } else {
      Debug(1, "ZM header not found in %d of buffer:%s.", m_buffer.size(), m_buffer.head());
      return 0;
    }
    if (header_start != m_buffer) {
      Debug(4, "ZM Packet didn't start at beginning of buffer %u. %c%c",
          header_start-m_buffer.head(), m_buffer[0], m_buffer[1]);
    }

    // read_into may invalidate packet_start
    unsigned int header_size = (header_end+1) /*packet_start*/ - m_buffer.head(); // includes any bytes before header

    int bytes_needed = data_size - (m_buffer.size() - header_size);
    if (bytes_needed > 0) {
      Debug(4, "Need another %d bytes. Trying to read them", bytes_needed);
      while (bytes_needed) {
        int bytes_read = m_buffer.read_into(m_fd, bytes_needed);
        if (bytes_read <= 0) {
          Debug(1, "Failed to read another %d bytes, got %d.", bytes_needed, bytes_read);
          return -1;
        }

        if (bytes_read != bytes_needed) {
          Debug(4, "Failed to read another %d bytes, got %d.", bytes_needed, bytes_read);
        }
        bytes_needed -= bytes_read;
      }  // end while bytes_neeeded
    }
    m_buffer.consume(header_size);
    unsigned char *packet_start = m_buffer.head();
    size_t bytes_remaining = data_size;
    std::list< std::pair<unsigned char*, size_t> > framesList = this->splitFrames(packet_start, bytes_remaining);
    Debug(3, "Got %d frames, consuming %d bytes, remaining %d", framesList.size(), data_size, bytes_remaining);
    m_buffer.consume(data_size);

    {
      std::unique_lock<std::mutex> lck(mutex_);
      while (framesList.size()) {
        std::pair<unsigned char*, size_t> nal = framesList.front();
        framesList.pop_front();
        NAL_Frame *Nal = new NAL_Frame(nal.first, nal.second, pts);
        m_nalQueue.push(Nal);
      }
    }
    condition_.notify_all();
  } // end while m_buffer.size()
  return 1;
}

// split packet in frames
std::list< std::pair<unsigned char*,size_t> > ZoneMinderFifoSource::splitFrames(unsigned char* frame, size_t &frameSize) {
  std::list< std::pair<unsigned char*,size_t> > frameList;
  if ( frame != nullptr ) {
    frameList.push_back(std::pair<unsigned char*,size_t>(frame, frameSize));
  }
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
