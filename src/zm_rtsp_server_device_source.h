/* ---------------------------------------------------------------------------
**
** DeviceSource.h
**
**  live555 source
**
** -------------------------------------------------------------------------*/

#ifndef ZM_RTSP_SERVER_DEVICE_SOURCE_H
#define ZM_RTSP_SERVER_DEVICE_SOURCE_H

#include "zm_config.h"
#include "zm_define.h"
#include "zm_monitor.h"
#include <list>
#include <string>
#include <utility>

#if HAVE_RTSP_SERVER
#include <liveMedia.hh>

class NAL_Frame;

class ZoneMinderDeviceSource: public FramedSource {

 public:
  static ZoneMinderDeviceSource* createNew(
    UsageEnvironment& env,
    std::shared_ptr<Monitor> monitor,
    AVStream * stream,
    unsigned int queueSize
  ) {
    return new ZoneMinderDeviceSource(env, monitor, stream, queueSize);
  };
  std::string getAuxLine() { return m_auxLine; };
  int getWidth() { return m_monitor->Width(); };
  int getHeight() { return m_monitor->Height(); };

 protected:
  ZoneMinderDeviceSource(UsageEnvironment& env, std::shared_ptr<Monitor> monitor, AVStream * stream, unsigned int queueSize);
  virtual ~ZoneMinderDeviceSource();

 protected:
  static void* threadStub(void* clientData) { return ((ZoneMinderDeviceSource*) clientData)->thread();};
  void* thread();
  static void deliverFrameStub(void* clientData) {((ZoneMinderDeviceSource*) clientData)->deliverFrame();};
  void deliverFrame();
  static void incomingPacketHandlerStub(void* clientData, int mask) { ((ZoneMinderDeviceSource*) clientData)->incomingPacketHandler(); };
  void incomingPacketHandler();
  int getNextFrame();
  void processFrame(char * frame, int frameSize, const timeval &ref);
  void queueFrame(char * frame, int frameSize, const timeval &tv);

  // split packet in frames
  virtual std::list< std::pair<unsigned char*, size_t> > splitFrames(unsigned char* frame, unsigned frameSize);

  // override FramedSource
  virtual void doGetNextFrame();
  virtual void doStopGettingFrames();
  virtual unsigned char *extractFrame(unsigned char *data, size_t& size, size_t& outsize);

 protected:
  std::list<NAL_Frame*> m_captureQueue;
  EventTriggerId m_eventTriggerId;
  AVStream *m_stream;
  std::shared_ptr<Monitor> m_monitor;
  PacketQueue *m_packetqueue;
  std::list<ZMPacket *>::iterator *m_packetqueue_it;

  unsigned int m_queueSize;
  pthread_t m_thid;
  pthread_mutex_t m_mutex;
  std::string m_auxLine;
  int stop;
};
#endif // HAVE_RTSP_SERVER

#endif // ZM_RTSP_SERVER_DEVICE_SOURCE_H
