#include "zm_decoder_thread.h"

#include "zm_monitor.h"
#include "zm_signal.h"
#include "zm_time.h"

#define AI_IN_DECODE 0

#if HAVE_QUADRA
#include "libavutil/hwcontext_ni_quad.h"
#endif

DecoderThread::DecoderThread(Monitor *monitor) :
  monitor_(monitor), terminate_(false) {
  thread_ = std::thread(&DecoderThread::Run, this);
  set_cpu_affinity(thread_);
}

DecoderThread::~DecoderThread() {
  Stop();
  if (thread_.joinable()) thread_.join();
}

void DecoderThread::Start() {
  if (thread_.joinable()) thread_.join();
  terminate_ = false;
  thread_ = std::thread(&DecoderThread::Run, this);
  set_cpu_affinity(thread_);
}

void DecoderThread::Stop() {
  terminate_ = true;
}

void DecoderThread::Join() {
  if (thread_.joinable()) thread_.join();
}

void DecoderThread::Run() {
  Debug(2, "DecoderThread::Run() for %d", monitor_->Id());

  while (!(terminate_ or zm_terminate)) {
    if (!Decode()) {
      if (!(terminate_ or zm_terminate)) {
        // We only sleep when Decode returns false because it is an error condition and we will spin like mad if it persists.
        Microseconds sleep_for = monitor_->Active() ? Microseconds(ZM_SAMPLE_RATE) : Microseconds(ZM_SUSPENDED_RATE);
        Warning("Sleeping for %" PRId64 "us", int64(sleep_for.count()));
        std::this_thread::sleep_for(sleep_for);
      }
    }
  }
  while (monitor_->decoder_queue.size()) monitor_->decoder_queue.pop_front();

  if (monitor_->mVideoCodecContext) {
    avcodec_free_context(&monitor_->mVideoCodecContext);
    monitor_->mVideoCodecContext = nullptr;
  }
  if (monitor_->mAudioCodecContext) {
    avcodec_free_context(&monitor_->mAudioCodecContext);
    monitor_->mAudioCodecContext = nullptr;
  }
}

bool DecoderThread::Decode() {
  if (monitor_->decoding != Monitor::DECODING_NONE) {
    // Not fatal... because we can still record
    if ((!monitor_->mVideoCodecContext) and monitor_->camera->NeedsDecode()) {
      if (monitor_->OpenDecoder() > 0) {

        // The proper thing to do might be to remove packets until we hit a keyframe, then start inserting.
        // If we don't encounter a keyframe, then.... keep popping off until we get one.
        // If we have queued packets, need to stuff them into the decoder.
        bool sending = false;
        std::list<ZMPacketLock> new_decoder_queue;
        while (!monitor_->decoder_queue.empty() and !zm_terminate) {
          Debug(1, "Sending queued packets to new decoder %zu", monitor_->decoder_queue.size());
          // Inject current queue into the decoder.
          ZMPacketLock delayed_packet_lock = std::move(monitor_->decoder_queue.front());
          monitor_->decoder_queue.pop_front();
          auto delayed_packet = delayed_packet_lock.packet_;
#if 1

          if (delayed_packet->keyframe or sending) {
            int ret = delayed_packet->send_packet(monitor_->mVideoCodecContext);
            if (ret<0) Error("Failed sending packet %d", delayed_packet->image_index);
            sending = true;
            new_decoder_queue.push_back(std::move(delayed_packet_lock));
          } else {
            delayed_packet->decoded = true;
          }
#else
            delayed_packet->decoded = true;
#endif
        } // end while packets in queue
        if (new_decoder_queue.size()) monitor_->decoder_queue = std::move(new_decoder_queue);
      } else {
        Debug(1, "Failed to open decoder, have %zu delayed packets to clear", monitor_->decoder_queue.size());
        while (!monitor_->decoder_queue.empty() and !zm_terminate) {
          ZMPacketLock delayed_packet_lock = std::move(monitor_->decoder_queue.front());
          monitor_->decoder_queue.pop_front();
          delayed_packet_lock.packet_->decoded = true;
        }
      } // end if success opening codec
    } // end if ! mCodec
  } // end != DECODING_NONE

  ZMPacketLock packet_lock;
  std::shared_ptr<ZMPacket> packet;

  // ===========================================================================
  // PHASE 1: Try to receive a decoded frame from the decoder
  // ===========================================================================
  // We do receive BEFORE send (reverse of normal libavcodec workflow) because
  // we need to maintain packet locks on packets while they're in the decoder.
  // Packets in decoder_queue have been sent but not yet received.

  if (!monitor_->decoder_queue.empty()) {
    Debug(2, "Decoder queue has %zu packets, trying receive_frame", monitor_->decoder_queue.size());
    auto &front_lock = monitor_->decoder_queue.front();
    auto front_packet = front_lock.packet_;

    int ret = front_packet->receive_frame(monitor_->mVideoCodecContext);
    if (ret > 0) {
      // Success - got a decoded frame, take ownership and process it
      packet_lock = std::move(monitor_->decoder_queue.front());
      monitor_->decoder_queue.pop_front();
      packet = front_packet;
      Debug(2, "Received frame for packet %d", packet->image_index);
      // Continue to PHASE 3 (frame processing)
    } else if (ret < 0) {
      // Decoder error
      Debug(1, "receive_frame failed: %d", ret);
      if (ret == AVERROR_EOF) {
        monitor_->CloseDecoder();
      }
      return false;
    } else {
      // EAGAIN - decoder needs more input, fall through to send another packet
      Debug(2, "receive_frame returned EAGAIN for packet %d", front_packet->image_index);
    }
  }

  // ===========================================================================
  // PHASE 2: Get a new packet and send it to decoder (if needed)
  // ===========================================================================
  // Only if we didn't receive a frame above

  if (!packet) {
    // Throttle: don't queue too many packets in the decoder
    int max_keyframe = monitor_->packetqueue.get_max_keyframe_interval();
    if ((max_keyframe > 0) &&
        (monitor_->decoder_queue.size() > 2 * static_cast<size_t>(max_keyframe))) {
      Debug(1, "Decoder queue full (%zu packets), throttling", monitor_->decoder_queue.size());
      return false;
    }

    // Get next packet from the main packet queue
    packet_lock = monitor_->packetqueue.get_packet(monitor_->decoder_it);
    packet = packet_lock.packet_;
    if (!packet) {
      Debug(2, "No packet available");
      return false;
    }

    // Audio packets don't need video decoding
    if (packet->codec_type != AVMEDIA_TYPE_VIDEO) {
      Debug(3, "Audio packet %d, marking decoded", packet->image_index);
      packet->decoded = true;
      monitor_->packetqueue.increment_it(monitor_->decoder_it, !monitor_->decoder_queue.empty());
      return true;
    }

    // Check if this packet needs to be sent to the decoder
    bool dominated = packet->image || packet->in_frame || !packet->packet->size;
    bool should_decode = !dominated && (
      (monitor_->decoding == Monitor::DECODING_ALWAYS) ||
      ((monitor_->decoding == Monitor::DECODING_ONDEMAND) && (monitor_->hasViewers() || monitor_->shared_data->last_decoder_index == monitor_->image_buffer_count)) ||
      ((monitor_->decoding == Monitor::DECODING_KEYFRAMES) && packet->keyframe) ||
      ((monitor_->decoding == Monitor::DECODING_KEYFRAMESONDEMAND) && (monitor_->hasViewers() || packet->keyframe))
    );

    if (should_decode) {
      Debug(2, "Sending packet %d to decoder", packet->image_index);

      if (!monitor_->mVideoCodecContext) {
        Debug(1, "No decoder");
        packet->decoded = true;
        monitor_->packetqueue.increment_it(monitor_->decoder_it, (monitor_->decoder_queue.size() > 0));
        return 1;
      }
      Debug(1, "send_packet %d", packet->image_index);
#if 1
      SystemTimePoint starttime = std::chrono::system_clock::now();
      int ret = packet->send_packet(monitor_->mVideoCodecContext);
      SystemTimePoint endtime = std::chrono::system_clock::now();

      // Warn if send_packet is taking too long
      int fps = static_cast<int>(monitor_->get_capture_fps());
      if ((fps > 0) && (endtime - starttime > Milliseconds(1000 / fps)) and Logger::fetch()->debugOn()) {
        Warning("send_packet %d is too slow: %.3f seconds. Capture fps is %d, queue size is %zu, keyframe interval is %d, retval was %d",
            packet->image_index, FPSeconds(endtime - starttime).count(), fps,
            monitor_->decoder_queue.size(), monitor_->packetqueue.get_max_keyframe_interval(), ret);
      } else {
        Debug(3, "send_packet took: %.3f seconds. Capture fps is %d", FPSeconds(endtime - starttime).count(), fps);
      }
#else
      int ret = packet->send_packet(monitor_->mVideoCodecContext);
#endif

      if (ret == 0) {
        // EAGAIN - decoder's input buffer is full, need to drain first
        Debug(2, "send_packet returned EAGAIN, will retry");
        return true;
      } else if (ret < 0) {
        // Error
        Debug(1, "send_packet failed: %d", ret);
        monitor_->CloseDecoder();
        return false;
      }

      // Success - packet sent to decoder, queue it for receive later
      monitor_->decoder_queue.push_back(std::move(packet_lock));
      monitor_->packetqueue.increment_it(monitor_->decoder_it, false);
      return true;  // Frame will be received on a future call
    }

    // Packet doesn't need decoding (or already has frame/image)
    Debug(2, "Packet %d doesn't need decoding (mode=%d)", packet->image_index, static_cast<int>(monitor_->decoding));
    monitor_->packetqueue.increment_it(monitor_->decoder_it, !monitor_->decoder_queue.empty());
  }

  // ===========================================================================
  // PHASE 3: Convert decoded frame to Image
  // ===========================================================================

  if (packet->in_frame && !packet->image) {
    // Handle hardware-accelerated frames
    packet->transfer_hwframe(monitor_->mVideoCodecContext);

    if (!packet->image) {
      packet->image = new Image(monitor_->camera_width, monitor_->camera_height, monitor_->camera->Colours(), monitor_->camera->SubpixelOrder());

      bool have_converter = monitor_->convert_context || monitor_->setupConvertContext(packet->in_frame.get(), packet->image);
      if (have_converter) {
        if (!packet->image->Assign(packet->in_frame.get(), monitor_->convert_context)) {
          delete packet->image;
          packet->image = nullptr;
        }
      } else {
        delete packet->image;
        packet->image = nullptr;
      }
    }
  }

  // ===========================================================================
  // PHASE 4: Prepare Y-channel image for analysis (if needed)
  // ===========================================================================

  if ((monitor_->shared_data->analysing != Monitor::ANALYSING_NONE) && (monitor_->analysis_image == Monitor::ANALYSISIMAGE_YCHANNEL)) {
    Image *y_image = packet->get_y_image();
    if (y_image) {
      if (packet->in_frame->width != monitor_->camera_width || packet->in_frame->height != monitor_->camera_height) {
        y_image->Scale(monitor_->camera_width, monitor_->camera_height);
      }
      monitor_->applyOrientation(y_image);
    } else if (monitor_->decoding == Monitor::DECODING_ALWAYS) {
      Error("Want to use y-channel, but no in_frame or wrong format");
    }
  }

  // ===========================================================================
  // PHASE 5: Process the RGB image (deinterlace, rotate, privacy, timestamp)
  // ===========================================================================

  if (packet->image) {
    Image *capture_image = packet->image;

    // Deinterlacing
    if (monitor_->deinterlacing_value) {
      if (!monitor_->applyDeinterlacing(packet, capture_image)) {
        packet->decoded = true;
        return false;
      }
    }

    // Rotation/flip
    monitor_->applyOrientation(capture_image);

    // Privacy masking
    if (monitor_->privacy_bitmask) {
      capture_image->MaskPrivacy(monitor_->privacy_bitmask);
    }

    // Timestamp overlay
    if (config.timestamp_on_capture) {
      monitor_->TimestampImage(capture_image, packet->timestamp);
    }

    monitor_->shared_data->signal = (capture_image and monitor_->signal_check_points) ? monitor_->CheckSignal(capture_image) : true;
  }  // end if have image

  // ===========================================================================
  // PHASE 6: Write to shared memory image buffer
  // ===========================================================================

  unsigned int index = packet->image_index % monitor_->image_buffer_count;

  if (packet->image) {
    monitor_->image_buffer[index]->AVPixFormat(monitor_->image_pixelformats[index] = packet->image->AVPixFormat());
    Debug(1, "Assigning %s for index %d to %s", packet->image->toString().c_str(), index, monitor_->image_buffer[index]->toString().c_str());
    monitor_->image_buffer[index]->Assign(*(packet->image));
  } else if (packet->in_frame) {
#if AI_IN_DECODE

#if HAVE_QUADRA
    if (monitor_->objectdetection == Monitor::OBJECT_DETECTION_QUADRA) {
      std::pair<int, std::string> results = monitor_->Analyse_Quadra(packet);
    }
#endif
#if HAVE_MX_ACCL_H
    if (monitor_->objectdetection == Monitor::OBJECT_DETECTION_MX_ACCL) {
      std::pair<int, std::string> results = monitor_->Analyse_MxAccl(packet);
    }
#endif
    if (packet->ai_frame) {
      Debug(1, "Assigning ai_frame for index %d", index);
      monitor_->image_buffer[index]->AVPixFormat(monitor_->image_pixelformats[index] = static_cast<AVPixelFormat>(packet->ai_frame->format));
      monitor_->image_buffer[index]->Assign(packet->ai_frame.get());
    } else if (packet->ai_image) {
      Debug(1, "Assigning ai_image for index %d", index);
      monitor_->image_buffer[index]->AVPixFormat(monitor_->image_pixelformats[index] = static_cast<AVPixelFormat>(packet->ai_frame->format));
      monitor_->image_buffer[index]->Assign(*(packet->ai_image));
    } else {
      if (packet->needs_hw_transfer(monitor_->mVideoCodecContext))
        packet->transfer_hwframe(monitor_->mVideoCodecContext);
      monitor_->image_buffer[index]->AVPixFormat(monitor_->image_pixelformats[index] = static_cast<AVPixelFormat>(packet->in_frame->format));
      monitor_->image_buffer[index]->Assign(packet->in_frame.get());
    }
#endif // AI_IN_DECODE
    if (packet->needs_hw_transfer(monitor_->mVideoCodecContext))
      packet->transfer_hwframe(monitor_->mVideoCodecContext);
  }

  // Update shared memory timestamps - do this for BOTH packet->image and packet->in_frame cases
  if (packet->image || packet->in_frame) {
    monitor_->shared_timestamps[index] = zm::chrono::duration_cast<timeval>(packet->timestamp.time_since_epoch());
    monitor_->shared_data->last_decoder_index = index;
    monitor_->shared_data->decoder_image_count++;
    SystemTimePoint now = std::chrono::system_clock::now();
    monitor_->shared_data->last_write_time = std::chrono::system_clock::to_time_t(now);
    if (now - packet->timestamp > Seconds(ZM_WATCH_MAX_DELAY)) {
      Warning("Decoding is not keeping up. We are %.2f seconds behind capture.",
          FPSeconds(now - packet->timestamp).count());
    }
    Debug(1, "Setting last_write_time to %s", SystemTimePointToString(now).c_str());
  }

  packet->decoded = true;
  // The idea is that capture is firing often enough
  monitor_->packetqueue.notify_all();
  return 1;
}  // end DecoderThread::Decode()
