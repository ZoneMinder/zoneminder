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
#include "zm_rtsp_server_frame.h"

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
    const std::string &fifo
    ) :
  stop_(false),
  m_rtspServer(rtspServer),
  m_sessionId(sessionId),
  m_channelId(channelId),
	m_fifo(fifo),
  m_fd(-1),
  m_hType(0)
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
      Debug(1, "Sleeping because couldn't getNextFrame");
      sleep(1);
    }
	}
}
void ZoneMinderFifoSource::WriteRun() {
  size_t maxNalSize = 1400;

  if (stop_) Warning("bad value for stop_ in WriteRun");
	while (!stop_) {
    NAL_Frame *nal = nullptr;
    while (!stop_ and !nal) {
      std::unique_lock<std::mutex> lck(mutex_);
      if (m_nalQueue.empty()) {
        Debug(3, "waiting");
        condition_.wait(lck);
      }
      if (!m_nalQueue.empty()) {
        nal = m_nalQueue.front();
        m_nalQueue.pop();
      }
    }

    if (nal) {
      if (1 and (nal->size() > maxNalSize)) {
        Debug(3, "Splitting NAL %zu", nal->size());
        size_t nalRemaining = nal->size();
        u_int8_t *nalSrc = nal->buffer();

        int fuNalSize = maxNalSize;
       // ? nalRemaining : maxNalSize;
        NAL_Frame fuNal(nullptr, fuNalSize, nal->pts());
        memcpy(fuNal.buffer()+1, nalSrc, fuNalSize-1);

        if (m_hType == 264) {
          fuNal.buffer()[0] = (nalSrc[0] & 0xE0) | 28; // FU indicator
          fuNal.buffer()[1] = 0x80 | (nalSrc[0] & 0x1F); // FU header (with S bit)
        } else { // 265
          u_int8_t nalUnitType = (nalSrc[0]&0x7E)>>1;
          fuNal.buffer()[0] = (nalSrc[0] & 0x81) | (49<<1); // Payload header (1st byte)
          fuNal.buffer()[1] = nalSrc[1]; // Payload header (2nd byte)
          fuNal.buffer()[2] = 0x80 | nalUnitType; // FU header (with S bit)
        }
        PushFrame(fuNal.buffer(), fuNal.size(), fuNal.pts());
        nalRemaining -= maxNalSize-1;
        nalSrc += maxNalSize-1;
        int nal_count = 0;

        int headerSize = 0;
        if (m_hType == 264) {
          fuNal.buffer()[1] = fuNal.buffer()[1]&~0x80; // FU header (no S bit)
          headerSize = 2;
        } else { // 265
          fuNal.buffer()[2] = fuNal.buffer()[2]&~0x80; // FU header (no S bit)
          headerSize = 3;
        }
        while (nalRemaining && !stop_) {
          if ( nalRemaining < maxNalSize ) {
            // This is the last fragment:
            fuNal.buffer()[headerSize-1] |= 0x40; // set the E bit in the FU header
          }
          fuNalSize = (nalRemaining < maxNalSize-headerSize) ? nalRemaining : maxNalSize-headerSize;
          fuNal.size(fuNalSize+headerSize);
          memcpy(fuNal.buffer()+headerSize, nalSrc, fuNalSize);

          PushFrame(fuNal.buffer(), fuNal.size(), fuNal.pts());
          nalRemaining -= fuNalSize;
          nalSrc += fuNalSize;
          nal_count += 1;
        }
        Debug(3, "Sending %d NALs @ %zu and 1 @ %zu", nal_count, maxNalSize, fuNal.size());
      } else {
        Debug(3, "Pushing nal of size %zu at %" PRId64, nal->size(), nal->pts());
        PushFrame(nal->buffer(), nal->size(), nal->pts());
      }
      delete nal;
      nal = nullptr;
      Debug(3, "Done Pushing nal");
    }  // end if nal
	}  // end while !_stop
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

  int bytes_read = m_buffer.read_into(m_fd, 4096);
  //int bytes_read = m_buffer.read_into(m_fd, 4096, {1,0});
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
  while (m_buffer.size() and !stop_) {
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
        m_buffer.consume((header_start-m_buffer.head()) + 2);
        delete[] header;
        return 0;
      }
      *content_length_ptr = '\0';
      content_length_ptr ++;
      char *pts_ptr = strchr(content_length_ptr, ' ');
      if (!pts_ptr) {
        m_buffer.consume((header_start-m_buffer.head()) + 2);
        Debug(1, "Didn't find space delineating pts in %s", header);
        delete[] header;
        return 0;
      }
      *pts_ptr = '\0';
      pts_ptr ++;
      data_size = atoi(content_length_ptr);
      pts = strtoll(pts_ptr, nullptr, 10);
      Debug(4, "ZM Packet %s header_size %d packet size %u pts %s %" PRId64, header, header_size, data_size, pts_ptr, pts);
      delete[] header;
    } else {
      Debug(1, "ZM header not found in %u of buffer.", m_buffer.size());
      m_buffer.clear();
      return 0;
    }
    if (header_start != m_buffer) {
      Debug(4, "ZM Packet didn't start at beginning of buffer %ld. %c%c",
            header_start - m_buffer.head(), m_buffer[0], m_buffer[1]);
    }

    // read_into may invalidate packet_start
    unsigned int header_size = (header_end+1) /*packet_start*/ - m_buffer.head(); // includes any bytes before header

    int bytes_needed = data_size - (m_buffer.size() - header_size);
    if (bytes_needed > 0) {
      Debug(4, "Need another %d bytes. Trying to read them", bytes_needed);
      while (bytes_needed and !stop_) {
        bytes_read = m_buffer.read_into(m_fd, bytes_needed);
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
    //Debug(4, "Consuming %d", header_size);
    //m_buffer.consume(header_size);

    unsigned char *packet_start = m_buffer.head() + header_size;
    size_t bytes_remaining = data_size;
    std::list< std::pair<unsigned char*, size_t> > framesList = this->splitFrames(packet_start, bytes_remaining);
    m_buffer.consume(data_size+header_size);
    Debug(3, "Got %zu frames, consuming %d bytes, remaining %zu",
          framesList.size(),
          data_size + header_size,
          bytes_remaining);

    {
      std::unique_lock<std::mutex> lck(mutex_);
      Debug(3, "have lock");
      while (!stop_ && framesList.size()) {
        std::pair<unsigned char*, size_t> nal = framesList.front();
        framesList.pop_front();
        NAL_Frame *Nal = new NAL_Frame(nal.first, nal.second, pts);
        m_nalQueue.push(Nal);
      }
    }
    Debug(3, "notifying");
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
