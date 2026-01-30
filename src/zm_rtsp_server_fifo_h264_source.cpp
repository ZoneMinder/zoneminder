/* ---------------------------------------------------------------------------
**
** H264_FifoSource.cpp
**
** H264 Live555 source
**
** -------------------------------------------------------------------------*/

#include "zm_rtsp_server_fifo_h264_source.h"

#include "zm_config.h"
#include "zm_logger.h"
#include "xop/H264Source.h"
#include "xop/H265Source.h"
#include <iomanip>
#include <sstream>

#if HAVE_RTSP_SERVER

// ---------------------------------
// H264 ZoneMinder FramedSource
// ---------------------------------
//
H264_ZoneMinderFifoSource::H264_ZoneMinderFifoSource(
  std::shared_ptr<xop::RtspServer>& rtspServer,
  xop::MediaSessionId sessionId,
  xop::MediaChannelId channelId,
  const std::string &fifo
)
  : H26X_ZoneMinderFifoSource(rtspServer, sessionId, channelId, fifo) {
  // extradata appears to simply be the SPS and PPS NAL's
  //this->splitFrames(m_stream->codecpar->extradata, m_stream->codecpar->extradata_size);
  m_hType = 264;
}

// split packet into frames
std::list< std::pair<unsigned char*, size_t> > H264_ZoneMinderFifoSource::splitFrames(unsigned char* frame, size_t &frameSize) {
  std::list< std::pair<unsigned char*, size_t> > frameList;

  size_t bufSize = frameSize;
  size_t size = 0;
  unsigned char* buffer = this->extractFrame(frame, bufSize, size);
  while ( buffer != nullptr ) {
    // Extract SPS/PPS from the stream and update xop source
    switch ( m_frameType & 0x1F ) {
    case 7:  // SPS
      Debug(4, "H264 SPS: size=%zu bufSize=%zu", size, bufSize);
      m_sps.assign((char*)buffer, size);
      if (m_h264Source && !m_sps.empty()) {
        m_h264Source->SetSPS((const uint8_t*)m_sps.data(), m_sps.size());
        Debug(2, "H264: Set SPS on xop source (%zu bytes)", m_sps.size());
      }
      break;
    case 8:  // PPS
      Debug(4, "H264 PPS: size=%zu bufSize=%zu", size, bufSize);
      m_pps.assign((char*)buffer, size);
      if (m_h264Source && !m_pps.empty()) {
        m_h264Source->SetPPS((const uint8_t*)m_pps.data(), m_pps.size());
        Debug(2, "H264: Set PPS on xop source (%zu bytes)", m_pps.size());
      }
      break;
    case 5:  // IDR
      Debug(4, "H264 IDR: size=%zu bufSize=%zu", size, bufSize);
      break;
    default:
      break;
    }

    frameList.push_back(std::pair<unsigned char*,size_t>(buffer, size));
    if (!bufSize) break;

    buffer = this->extractFrame(&buffer[size], bufSize, size);
  }  // end while buffer
  frameSize = bufSize;
  return frameList;
}

H265_ZoneMinderFifoSource::H265_ZoneMinderFifoSource(
  std::shared_ptr<xop::RtspServer>& rtspServer,
  xop::MediaSessionId sessionId,
  xop::MediaChannelId channelId,
  const std::string &fifo
)
  : H26X_ZoneMinderFifoSource(rtspServer, sessionId, channelId, fifo) {
  // extradata appears to simply be the SPS and PPS NAL's
  // this->splitFrames(m_stream->codecpar->extradata, m_stream->codecpar->extradata_size);
  m_hType = 265;
}

// split packet in frames
std::list< std::pair<unsigned char*,size_t> >
H265_ZoneMinderFifoSource::splitFrames(unsigned char* frame, size_t &frameSize) {
  std::list< std::pair<unsigned char*, size_t> > frameList;

  size_t bufSize = frameSize;
  size_t size = 0;
  unsigned char* buffer = this->extractFrame(frame, bufSize, size);
  while ( buffer != nullptr ) {
    // Extract VPS/SPS/PPS from the stream and update xop source
    switch ((m_frameType&0x7E)>>1) {
    case 32:  // VPS
      Debug(4, "H265 VPS: size=%zu bufSize=%zu", size, bufSize);
      m_vps.assign((char*)buffer, size);
      if (m_h265Source && !m_vps.empty()) {
        m_h265Source->SetVPS((const uint8_t*)m_vps.data(), m_vps.size());
        Debug(2, "H265: Set VPS on xop source (%zu bytes)", m_vps.size());
      }
      break;
    case 33:  // SPS
      Debug(4, "H265 SPS: size=%zu bufSize=%zu", size, bufSize);
      m_sps.assign((char*)buffer, size);
      if (m_h265Source && !m_sps.empty()) {
        m_h265Source->SetSPS((const uint8_t*)m_sps.data(), m_sps.size());
        Debug(2, "H265: Set SPS on xop source (%zu bytes)", m_sps.size());
      }
      break;
    case 34:  // PPS
      Debug(4, "H265 PPS: size=%zu bufSize=%zu", size, bufSize);
      m_pps.assign((char*)buffer, size);
      if (m_h265Source && !m_pps.empty()) {
        m_h265Source->SetPPS((const uint8_t*)m_pps.data(), m_pps.size());
        Debug(2, "H265: Set PPS on xop source (%zu bytes)", m_pps.size());
      }
      break;
    case 19:  // IDR_W_RADL
    case 20:  // IDR_N_LP
      Debug(4, "H265 IDR: size=%zu bufSize=%zu", size, bufSize);
      break;
    default:
      break;
    }

    frameList.push_back(std::pair<unsigned char*,size_t>(buffer, size));

    buffer = this->extractFrame(&buffer[size], bufSize, size);
  }  // end while buffer
  frameSize = bufSize;
  return frameList;
}  // end H265_ZoneMinderFifoSource::splitFrames(unsigned char* frame, unsigned frameSize)

unsigned char * H26X_ZoneMinderFifoSource::findMarker(
  unsigned char *frame, size_t size, size_t &length
) {
  //Debug(1, "findMarker %p %d", frame, size);
  unsigned char *start = nullptr;
  for ( size_t i = 0; i < size-2; i += 1 ) {
    //Debug(1, "%d: %d %d %d", i, frame[i], frame[i+1], frame[i+2]);
    if ( (frame[i] == 0) and (frame[i+1]) == 0 and (frame[i+2] == 1) ) {
      if ( i and (frame[i-1] == 0) ) {
        start = frame + i - 1;
        length = sizeof(H264marker);
      } else {
        start = frame + i;
        length = sizeof(H264shortmarker);
      }
      break;
    }
  }
  return start;
}

// extract a frame
unsigned char*  H26X_ZoneMinderFifoSource::extractFrame(unsigned char* frame, size_t& size, size_t& outsize) {
  unsigned char *outFrame = nullptr;
  Debug(4, "ExtractFrame: %p %zu", frame, size);
  outsize = 0;
  size_t markerLength = 0;
  m_frameType = 0;
  unsigned char *startFrame = nullptr;
  if (size >= 3)
    startFrame = this->findMarker(frame, size, markerLength);
  if (startFrame != nullptr) {
    size_t endMarkerLength = 0;
    Debug(4, "startFrame: %p marker Length %zu", startFrame, markerLength);
    m_frameType = startFrame[markerLength];

    int remainingSize = size-(startFrame-frame+markerLength);
    unsigned char *endFrame = nullptr;
    if ( remainingSize > 3 ) {
      endFrame = this->findMarker(startFrame+markerLength, remainingSize, endMarkerLength);
    }
    Debug(4, "endFrame: %p marker Length %zu, remaining size %d", endFrame, endMarkerLength, remainingSize);

    if ( m_keepMarker ) {
      size -=  startFrame-frame;
      outFrame = startFrame;
    } else {
      size -=  startFrame-frame+markerLength;
      outFrame = &startFrame[markerLength];
    }

    if ( endFrame != nullptr ) {
      outsize = endFrame - outFrame;
    } else {
      outsize = size;
    }
    size -= outsize;
    Debug(4, "Have frame type: %d size %zu, keepmarker %d", m_frameType, outsize, m_keepMarker);
  } else if ( size >= sizeof(H264shortmarker) ) {
    Info("No marker found size %zu", size);
  }

  return outFrame;
}
#endif // HAVE_RTSP_SERVER
