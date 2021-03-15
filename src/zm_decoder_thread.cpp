#include "zm_decoder_thread.h"

#include "zm_monitor.h"
#include "zm_signal.h"
#include "zm_utils.h"

//DecoderThread::DecoderThread(std::shared_ptr<Monitor> monitor) :
DecoderThread::DecoderThread(Monitor * monitor) :
    monitor_(monitor), terminate_(false) {
    //monitor_(std::move(monitor)), terminate_(false) {
  thread_ = std::thread(&DecoderThread::Run, this);
}

DecoderThread::~DecoderThread() {
  Stop();
  if (thread_.joinable())
    thread_.join();
}

void DecoderThread::Run() {
  Debug(2, "DecoderThread::Run() for %d", monitor_->Id());

  //Microseconds decoder_rate = Microseconds(monitor_->GetDecoderRate());
  //Seconds decoder_update_delay = Seconds(monitor_->GetDecoderUpdateDelay());
  //Debug(2, "DecoderThread::Run() have update delay %d", decoder_update_delay);

  //TimePoint last_decoder_update_time = std::chrono::steady_clock::now();
  //TimePoint cur_time;

  while (!(terminate_ or zm_terminate)) {
    // Some periodic updates are required for variable capturing framerate
    //if (decoder_update_delay != Seconds::zero()) {
      //cur_time = std::chrono::steady_clock::now();
      //Debug(2, "Updating adaptive skip");
      //if ((cur_time - last_decoder_update_time) > decoder_update_delay) {
        //decoder_rate = Microseconds(monitor_->GetDecoderRate());
        //last_decoder_update_time = cur_time;
      //}
    //}

    if (!monitor_->Decode()) {
      //if ( !(terminate_ or zm_terminate) ) {
        //Microseconds sleep_for = monitor_->Active() ? Microseconds(ZM_SAMPLE_RATE) : Microseconds(ZM_SUSPENDED_RATE);
        //Debug(2, "Sleeping for %" PRId64 "us", int64(sleep_for.count()));
        //std::this_thread::sleep_for(sleep_for);
      //}
    //} else if (decoder_rate != Microseconds::zero()) {
      //Debug(2, "Sleeping for %" PRId64 " us", int64(decoder_rate.count()));
      //std::this_thread::sleep_for(decoder_rate);
    //} else {
      //Debug(2, "Not sleeping");
    }
  }
}
