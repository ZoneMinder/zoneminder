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
// and publishes the latest decoded frame as an analysis-ready Image at the
// substream's NATIVE resolution (no scaling to the primary camera dimensions).
// Depending on the monitor's analysis_image setting the mailbox holds either the
// raw Y channel (ANALYSISIMAGE_YCHANNEL) or a full-colour, pixel-format-converted
// image; either way it stays at the substream's own width/height and the consumer
// (Monitor::getMotionSourceImage) rebuilds zones at that size.  Used when
// AnalysisSource=Secondary so that motion detection does not require
// software-decoding the full-resolution primary stream while nobody is watching
// live.
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
  // monotonically increasing frame counter used to detect fresh frames) and
  // capture_steady (the steady_clock time the frame was captured, for wallclock
  // sync against a primary packet's steady stamp).  Callers use this to decide
  // whether a copy is worthwhile before paying for GetLatestImage.
  bool PeekLatest(uint64_t &sequence, TimePoint &capture_steady);

  // Copy the latest published analysis image into dest.  Returns false if no
  // frame has ever been produced.  On success sets sequence and capture_steady
  // as above.
  bool GetLatestImage(Image &dest, uint64_t &sequence, TimePoint &capture_steady);

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
  TimePoint capture_time_;  // steady_clock time the latest frame was captured
};

#endif  // ZM_SECOND_STREAM_THREAD_H
