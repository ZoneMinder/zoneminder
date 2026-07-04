#ifndef ZM_SECOND_STREAM_THREAD_H
#define ZM_SECOND_STREAM_THREAD_H

#include "zm_image.h"
#include "zm_time.h"

#include <atomic>
#include <cstdint>
#include <mutex>
#include <thread>

class Monitor;
class FFmpeg_Input;
struct AVFrame;
struct SwsContext;

// Decodes a monitor's low-res analysis substream (SecondPath) in its own thread
// and publishes the latest frame as an analysis-ready Image, scaled/converted to
// the primary camera dimensions.  Used when AnalysisSource=Secondary so that
// motion detection does not require software-decoding the full-resolution
// primary stream while nobody is watching live.
//
// The thread owns its own FFmpeg_Input outright (it must free nothing it did not
// allocate) and never emits packets into the monitor packetqueue.  On read
// failure it closes the input and reconnects with exponential backoff, which
// also keeps the substream RTSP session alive (an unread session gets FIN'd by
// the camera and left stuck in CLOSE-WAIT).
class SecondStreamThread {
 public:
  explicit SecondStreamThread(Monitor *monitor);
  ~SecondStreamThread();
  SecondStreamThread(const SecondStreamThread &) = delete;
  SecondStreamThread(SecondStreamThread &&) = delete;

  void Start();
  void Stop();
  void Join();

  // Cheap peek at the newest frame's metadata without copying pixels.  Returns
  // false if no frame has ever been produced.  On success sets sequence (a
  // monotonically increasing frame counter used to detect fresh frames) and age
  // (time since the frame was captured).  Callers use this to decide whether a
  // copy is worthwhile before paying for GetLatestImage.
  bool PeekLatest(uint64_t &sequence, FPSeconds &age);

  // Copy the latest published analysis image into dest.  Returns false if no
  // frame has ever been produced.  On success sets sequence and age as above.
  bool GetLatestImage(Image &dest, uint64_t &sequence, FPSeconds &age);

 private:
  void Run();
  bool OpenInput();
  void CloseInput();
  bool ProduceImage(AVFrame *frame);

  Monitor *monitor_;
  std::atomic<bool> terminate_;
  std::thread thread_;

  FFmpeg_Input *input_;
  SwsContext *convert_context_;

  std::mutex mutex_;
  Image latest_image_;
  bool have_image_;
  uint64_t sequence_;
  SystemTimePoint capture_time_;
};

#endif  // ZM_SECOND_STREAM_THREAD_H
