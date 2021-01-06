/* ---------------------------------------------------------------------------
**
** H264_DeviceSource.cpp
**
** H264 Live555 source
**
** -------------------------------------------------------------------------*/

#include <sstream>

// live555
#include <Base64.hh>

// project
#include "zm_rtsp_server_h264_device_source.h"

// ---------------------------------
// H264 ZoneMinder FramedSource
// ---------------------------------

// split packet into frames
std::list< std::pair<unsigned char*, size_t> > H264_ZoneMinderDeviceSource::splitFrames(unsigned char* frame, unsigned frameSize) {
	std::list< std::pair<unsigned char*, size_t> > frameList;

	size_t bufSize = frameSize;
	size_t size = 0;
	unsigned char* buffer = this->extractFrame(frame, bufSize, size);
	while ( buffer != nullptr ) {
		switch ( m_frameType & 0x1F ) {
			case 7:
        //LOG(INFO) << "SPS size:" << size << " bufSize:" << bufSize;
        m_sps.assign((char*)buffer,size);
        break;
			case 8:
        //LOG(INFO) << "PPS size:" << size << " bufSize:" << bufSize;
        m_pps.assign((char*)buffer,size);
        break;
			case 5:
        //LOG(INFO) << "IDR size:" << size << " bufSize:" << bufSize;
				if ( m_repeatConfig && !m_sps.empty() && !m_pps.empty() ) {
					frameList.push_back(std::pair<unsigned char*,size_t>((unsigned char*)m_sps.c_str(), m_sps.size()));
					frameList.push_back(std::pair<unsigned char*,size_t>((unsigned char*)m_pps.c_str(), m_pps.size()));
				}
				break;
			default:
				break;
		}

		if ( !m_sps.empty() && !m_pps.empty() ) {
			u_int32_t profile_level_id = 0;
			if ( m_sps.size() >= 4 ) profile_level_id = (m_sps[1]<<16)|(m_sps[2]<<8)|m_sps[3];

			char* sps_base64 = base64Encode(m_sps.c_str(), m_sps.size());
			char* pps_base64 = base64Encode(m_pps.c_str(), m_pps.size());

			std::ostringstream os;
			os << "profile-level-id=" << std::hex << std::setw(6) << std::setfill('0') << profile_level_id;
			os << ";sprop-parameter-sets=" << sps_base64 <<"," << pps_base64;
			m_auxLine.assign(os.str());

			delete [] sps_base64;
			delete [] pps_base64;
		}
		frameList.push_back(std::pair<unsigned char*,size_t>(buffer, size));

		buffer = this->extractFrame(&buffer[size], bufSize, size);
	}
	return frameList;
}

// split packet in frames
std::list< std::pair<unsigned char*,size_t> > H265_ZoneMinderDeviceSource::splitFrames(unsigned char* frame, unsigned frameSize) {
	std::list< std::pair<unsigned char*,size_t> > frameList;

	size_t bufSize = frameSize;
	size_t size = 0;
	unsigned char* buffer = this->extractFrame(frame, bufSize, size);
	while ( buffer != nullptr ) {
		switch ((m_frameType&0x7E)>>1) {
			case 32: 
        //Info( "VPS size:" << size << " bufSize:" << bufSize;
        m_vps.assign((char*)buffer,size);
        break;
			case 33:
        //LOG(INFO) << "SPS size:" << size << " bufSize:" << bufSize;
        m_sps.assign((char*)buffer,size);
        break;
			case 34:
        //LOG(INFO) << "PPS size:" << size << " bufSize:" << bufSize;
        m_pps.assign((char*)buffer,size);
        break;
			case 19:
			case 20:
        //LOG(INFO) << "IDR size:" << size << " bufSize:" << bufSize;
				if ( m_repeatConfig && !m_vps.empty() && !m_sps.empty() && !m_pps.empty() ) {
					frameList.push_back(std::pair<unsigned char*,size_t>((unsigned char*)m_vps.c_str(), m_vps.size()));
					frameList.push_back(std::pair<unsigned char*,size_t>((unsigned char*)m_sps.c_str(), m_sps.size()));
					frameList.push_back(std::pair<unsigned char*,size_t>((unsigned char*)m_pps.c_str(), m_pps.size()));
				}
			break;
			default: break;
		}

		if (!m_vps.empty() && !m_sps.empty() && !m_pps.empty()) {
			char* vps_base64 = base64Encode(m_vps.c_str(), m_vps.size());
			char* sps_base64 = base64Encode(m_sps.c_str(), m_sps.size());
			char* pps_base64 = base64Encode(m_pps.c_str(), m_pps.size());

			std::ostringstream os;
			os << "sprop-vps=" << vps_base64;
			os << ";sprop-sps=" << sps_base64;
			os << ";sprop-pps=" << pps_base64;
			m_auxLine.assign(os.str());

			delete [] vps_base64;
			delete [] sps_base64;
			delete [] pps_base64;
		}
		frameList.push_back(std::pair<unsigned char*,size_t>(buffer, size));

		buffer = this->extractFrame(&buffer[size], bufSize, size);
	}
	return frameList;
}

// extract a frame
unsigned char*  H26X_ZoneMinderDeviceSource::extractFrame(unsigned char* frame, size_t& size, size_t& outsize) {
	unsigned char * outFrame = nullptr;
	outsize = 0;
	unsigned int markerlength = 0;
	m_frameType = 0;

	unsigned char *startFrame = (unsigned char*)memmem(frame, size, H264marker, sizeof(H264marker));
	if ( startFrame != nullptr ) {
		markerlength = sizeof(H264marker);
	} else {
		startFrame = (unsigned char*)memmem(frame, size, H264shortmarker, sizeof(H264shortmarker));
		if ( startFrame != nullptr ) {
			markerlength = sizeof(H264shortmarker);
		}
	}
	if ( startFrame != nullptr ) {
		m_frameType = startFrame[markerlength];

		int remainingSize = size-(startFrame-frame+markerlength);
		unsigned char *endFrame = (unsigned char*)memmem(&startFrame[markerlength], remainingSize, H264marker, sizeof(H264marker));
		if ( endFrame == nullptr ) {
			endFrame = (unsigned char*)memmem(&startFrame[markerlength], remainingSize, H264shortmarker, sizeof(H264shortmarker));
		}

		if ( m_keepMarker ) {
			size -=  startFrame-frame;
			outFrame = startFrame;
		} else {
			size -=  startFrame-frame+markerlength;
			outFrame = &startFrame[markerlength];
		}

		if ( endFrame != nullptr ) {
			outsize = endFrame - outFrame;
		} else {
			outsize = size;
		}
		size -= outsize;
	} else if ( size >= sizeof(H264shortmarker) ) {
		 Info("No marker found");
	}

	return outFrame;
}
