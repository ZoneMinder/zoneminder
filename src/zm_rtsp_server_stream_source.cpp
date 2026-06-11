/* ---------------------------------------------------------------------------
** This software is in the public domain, furnished "as is", without technical
** support, and with no warranty, express or implied, as to its usefulness for
** any purpose.
**
**
** ZoneMinder RTSP stream source, fed from the monitor stream socket
**
** -------------------------------------------------------------------------*/

#include "zm_rtsp_server_stream_source.h"
#include "zm_rtsp_server_frame.h"

#include "zm_config.h"
#include "zm_ffmpeg.h"
#include "zm_logger.h"
#include "zm_signal.h"

#include <climits>

#if HAVE_RTSP_SERVER
ZoneMinderStreamSource::ZoneMinderStreamSource(
  std::shared_ptr<xop::RtspServer>& rtspServer,
  xop::MediaSessionId sessionId,
  xop::MediaChannelId channelId
) :
  stop_(false),
  m_rtspServer(rtspServer),
  m_sessionId(sessionId),
  m_channelId(channelId),
  m_hType(0) {
  write_thread_ = std::thread(&ZoneMinderStreamSource::WriteRun, this);
}

ZoneMinderStreamSource::~ZoneMinderStreamSource() {
  Debug(1, "Deleting Stream Source");
  Stop();
  if (write_thread_.joinable()) {
    Debug(3, "Joining write thread");
    write_thread_.join();
  }
  while (!m_nalQueue.empty()) {
    delete m_nalQueue.front();
    m_nalQueue.pop();
  }
  Debug(1, "Deleting Stream Source done");
}

// One complete access unit / audio packet from the stream socket
void ZoneMinderStreamSource::OnPacket(const uint8_t *data, size_t size, int64_t pts) {
  if (!data or !size or stop_ or zm_terminate)
    return;

  size_t remaining = size;
  std::list< std::pair<unsigned char*, size_t> > framesList =
      this->splitFrames(const_cast<unsigned char *>(data), remaining);
  Debug(3, "Got %zu frames from %zu bytes, remaining %zu",
        framesList.size(), size, remaining);

  {
    std::unique_lock<std::mutex> lck(mutex_);
    while (!stop_ && framesList.size()) {
      std::pair<unsigned char*, size_t> nal = framesList.front();
      framesList.pop_front();
      m_nalQueue.push(new NAL_Frame(nal.first, nal.second, pts));
    }
  }
  condition_.notify_all();
}

void ZoneMinderStreamSource::WriteRun() {
  size_t maxNalSize = 1400;

  if (stop_) Warning("bad value for stop_ in WriteRun");
  while (!stop_) {
    NAL_Frame *nal = nullptr;
    while (!stop_ and !zm_terminate and !nal) {
      std::unique_lock<std::mutex> lck(mutex_);
      if (m_nalQueue.empty()) {
        Debug(3, "waiting");
        condition_.wait(lck);
        if (stop_ or zm_terminate) return;
      }
      if (!m_nalQueue.empty()) {
        nal = m_nalQueue.front();
        m_nalQueue.pop();
      }
    }

    if (nal) {
      if (nal->size() > maxNalSize) {
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

// split packet in frames
std::list< std::pair<unsigned char*,size_t> > ZoneMinderStreamSource::splitFrames(unsigned char* frame, size_t &frameSize) {
  std::list< std::pair<unsigned char*,size_t> > frameList;
  if ( frame != nullptr ) {
    frameList.push_back(std::pair<unsigned char*,size_t>(frame, frameSize));
  }
  frameSize = 0;
  return frameList;
}

// extract a frame
unsigned char*  ZoneMinderStreamSource::extractFrame(unsigned char* frame, size_t& size, size_t& outsize) {
  outsize = size;
  size = 0;
  return frame;
}
#endif // HAVE_RTSP_SERVER
