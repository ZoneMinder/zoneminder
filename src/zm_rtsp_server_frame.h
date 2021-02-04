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
    NAL_Frame(unsigned char * buffer, size_t size, timeval timestamp) :
      m_buffer(nullptr),
      m_size(size),
      m_timestamp(timestamp),
      m_ref_count(1) {
        m_buffer = new unsigned char[m_size];
        memcpy(m_buffer, buffer, m_size);
      };
    NAL_Frame(unsigned char* buffer, size_t size) : m_buffer(buffer), m_size(size) {
      gettimeofday(&m_timestamp, NULL);
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
    size_t nal_size() const { return m_size-4; };
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

  private:
    unsigned char* m_buffer;
    size_t m_size;
  public:
    timeval m_timestamp;
  private:
    int m_ref_count;
};
#endif // HAVE_RTSP_SERVER

#endif // ZM_RTSP_SERVER_FRAME_H