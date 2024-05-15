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
#if 0
    bool updateAux = false;
    switch ( m_frameType & 0x1F ) {
    case 7:
      Debug(4, "SPS_Size: %d bufSize %d", size, bufSize);
      m_sps.assign((char*)buffer, size);
      updateAux = true;
      break;
    case 8:
      Debug(4, "PPS_Size: %d bufSize %d", size, bufSize);
      m_pps.assign((char*)buffer, size);
      updateAux = true;
      break;
    case 5:
      Debug(4, "IDR_Size: %d bufSize %d", size, bufSize);
      if ( m_repeatConfig && !m_sps.empty() && !m_pps.empty() ) {
        frameList.push_back(std::pair<unsigned char*,size_t>((unsigned char*)m_sps.c_str(), m_sps.size()));
        frameList.push_back(std::pair<unsigned char*,size_t>((unsigned char*)m_pps.c_str(), m_pps.size()));
      }
      break;
    default:
      break;
    }

    if ( updateAux and !m_sps.empty() and !m_pps.empty() ) {
      u_int32_t profile_level_id = 0;
      if ( m_sps.size() >= 4 ) profile_level_id = (m_sps[1]<<16)|(m_sps[2]<<8)|m_sps[3];

      char* sps_base64 = base64Encode(m_sps.c_str(), m_sps.size());
      char* pps_base64 = base64Encode(m_pps.c_str(), m_pps.size());

      std::ostringstream os;
      os << "profile-level-id=" << std::hex << std::setw(6) << std::setfill('0') << profile_level_id;
      os << ";sprop-parameter-sets=" << sps_base64 << "," << pps_base64 << "\r\n";
      if (!(m_width and m_height))
        os << "a=x-dimensions:" << m_width << "," <<  m_height  << "\r\n";
      m_auxLine.assign(os.str());
      Debug(3, "auxLine: %s", m_auxLine.c_str());

      delete [] sps_base64;
      delete [] pps_base64;
    }
#endif
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
#if 0
    bool updateAux = false;
    switch ((m_frameType&0x7E)>>1) {
    case 32:
      Debug(4, "VPS_Size: %d bufSize %d", size, bufSize);
      m_vps.assign((char*)buffer,size);
      updateAux = true;
      break;
    case 33:
      Debug(4, "SPS_Size: %d bufSize %d", size, bufSize);
      m_sps.assign((char*)buffer,size);
      updateAux = true;
      break;
    case 34:
      Debug(4, "PPS_Size: %d bufSize %d", size, bufSize);
      m_pps.assign((char*)buffer,size);
      updateAux = true;
      break;
    case 19:
    case 20:
      Debug(4, "IDR_Size: %d bufSize %d", size, bufSize);
      if ( m_repeatConfig && !m_vps.empty() && !m_sps.empty() && !m_pps.empty() ) {
        frameList.push_back(std::pair<unsigned char*,size_t>((unsigned char*)m_vps.c_str(), m_vps.size()));
        frameList.push_back(std::pair<unsigned char*,size_t>((unsigned char*)m_sps.c_str(), m_sps.size()));
        frameList.push_back(std::pair<unsigned char*,size_t>((unsigned char*)m_pps.c_str(), m_pps.size()));
      }
      break;
    default:
      //Debug(4, "Unknown frametype!? %d %d", m_frameType, ((m_frameType & 0x7E) >> 1));
      break;
    }

    if ( updateAux and !m_vps.empty() and !m_sps.empty() and !m_pps.empty() ) {
      char* vps_base64 = base64Encode(m_vps.c_str(), m_vps.size());
      char* sps_base64 = base64Encode(m_sps.c_str(), m_sps.size());
      char* pps_base64 = base64Encode(m_pps.c_str(), m_pps.size());

      std::ostringstream os;
      os << "sprop-vps=" << vps_base64;
      os << ";sprop-sps=" << sps_base64;
      os << ";sprop-pps=" << pps_base64;
      os << "\r\n" << "a=x-dimensions:" << m_width << "," <<  m_height  << "\r\n";
      m_auxLine.assign(os.str());
      Debug(1, "Assigned h265 auxLine to %s", m_auxLine.c_str());

      delete [] vps_base64;
      delete [] sps_base64;
      delete [] pps_base64;
    }
#endif
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
