#ifndef ZM_DECODER_THREAD_H
#define ZM_DECODER_THREAD_H

#include <atomic>
#include <memory>
#include <thread>

class Monitor;

class DecoderThread {
 public:
  explicit DecoderThread(Monitor *monitor);
  ~DecoderThread();
  DecoderThread(DecoderThread &rhs) = delete;
  DecoderThread(DecoderThread &&rhs) = delete;

  void Start();
  void Stop();
  void Join();

 private:
  void Run();

  Monitor *monitor_;
  std::atomic<bool> terminate_;
  std::thread thread_;
};

#endif
