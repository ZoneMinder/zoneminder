#ifndef ZM_RTSP_SERVER_FRAME_H
#define ZM_RTSP_SERVER_FRAME_H

#include "zm_config.h"
#include "zm_logger.h"
#include <cstring>
#include <sys/time.h>

#if HAVE_RTSP_SERVER
// ---------------------------------
// Captured frame
// ---------------------------------
const char H264marker[] = {0,0,0,1};
const char H264shortmarker[] = {0,0,1};

class NAL_Frame {
 public:
  NAL_Frame(unsigned char * buffer, size_t size, int64 pts) :
    m_buffer(nullptr),
    m_size(size),
    m_pts(pts),
    m_ref_count(1) {
    m_buffer = new unsigned char[m_size];
    if (buffer) {
      memcpy(m_buffer, buffer, m_size);
    }
  };
  NAL_Frame& operator=(const NAL_Frame&);
  ~NAL_Frame()  {
    delete[] m_buffer;
    m_buffer = nullptr;
  };
  unsigned char *buffer() const { return m_buffer; };
  // The buffer has a 32bit nal size value at the front, so if we want the nal, it's
  // the address of the buffer plus 4 bytes.
  unsigned char *nal() const { return m_buffer+4; };
  size_t size() const { return m_size; };
  size_t size(size_t new_size) { m_size=new_size; return m_size; };
  size_t nal_size() const { return m_size-4; };
  int64_t pts() const { return m_pts; };
  bool check() const {
    // Look for marker at beginning
    unsigned char *marker = (unsigned char*)memmem(m_buffer, sizeof(H264marker), H264marker, sizeof(H264marker));
    if ( marker ) {
      Debug(1, "marker found at beginning");
      return true;
    } else {
      marker = (unsigned char*)memmem(m_buffer, m_size, H264marker, sizeof(H264marker));
      if ( marker ) {
        Debug(1, "marker not found at beginning");
        return false;
      }
    }
    return false;
  }

  void debug() {
    if (m_size <= 4) {
      Debug(1, "NAL: %zu: %.2x %.2x %.2x %.2x", m_size,
            m_buffer[0], m_buffer[1], m_buffer[2], m_buffer[3]);
    } else {
      Debug(1, "NAL: %zu: %.2x %.2x %.2x %.2x   %.2x %.2x %.2x %.2x ", m_size,
            m_buffer[0], m_buffer[1], m_buffer[2], m_buffer[3],
            m_buffer[4], m_buffer[5], m_buffer[6], m_buffer[7]
           );
    }
  }

 private:
  unsigned char* m_buffer;
  size_t m_size;
 public:
  int64 m_pts;
 private:
  int m_ref_count;
};
#endif // HAVE_RTSP_SERVER

#endif // ZM_RTSP_SERVER_FRAME_H
