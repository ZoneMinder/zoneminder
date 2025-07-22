/*
 * ZoneMinder Libvlc Camera Class Implementation, $Date$, $Revision$
 * Copyright (C) 2001-2008 Philip Coombes
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/

#include "zm_libvlc_camera.h"

#include "zm_packet.h"
#include "zm_signal.h"
#include <dlfcn.h>

#if HAVE_LIBVLC
static void *libvlc_lib = nullptr;
static void (*libvlc_media_player_release_f)(libvlc_media_player_t* ) = nullptr;
static void (*libvlc_media_release_f)(libvlc_media_t* ) = nullptr;
static void (*libvlc_release_f)(libvlc_instance_t* )	= nullptr;
static void (*libvlc_media_player_stop_f)(libvlc_media_player_t* ) = nullptr;
static libvlc_instance_t* (*libvlc_new_f)(int, const char* const *) = nullptr;
static void (*libvlc_log_set_f)(libvlc_instance_t*, libvlc_log_cb, void *) = nullptr;
static libvlc_media_t* (*libvlc_media_new_location_f)(libvlc_instance_t*, const char*) = nullptr;
static libvlc_media_player_t* (*libvlc_media_player_new_from_media_f)(libvlc_media_t*) = nullptr;
static void (*libvlc_video_set_format_f)(libvlc_media_player_t*, const char*, unsigned, unsigned, unsigned) = nullptr;
static void (*libvlc_video_set_callbacks_f)(libvlc_media_player_t*, libvlc_video_lock_cb, libvlc_video_unlock_cb, libvlc_video_display_cb, void*) = nullptr;
static int (*libvlc_media_player_play_f)(libvlc_media_player_t *) = nullptr;
static const char* (*libvlc_errmsg_f)(void) = nullptr;
static const char* (*libvlc_get_version_f)(void) = nullptr;

void bind_libvlc_symbols() {
  if(libvlc_lib != nullptr) // Safe-check
    return;

  libvlc_lib = dlopen("libvlc.so", RTLD_LAZY | RTLD_GLOBAL);
  if(!libvlc_lib) {
    Error("Error loading libvlc: %s", dlerror());
    return;
  }

  *(void**) (&libvlc_media_player_release_f) = dlsym(libvlc_lib, "libvlc_media_player_release");
  *(void**) (&libvlc_media_release_f) = dlsym(libvlc_lib, "libvlc_media_release");
  *(void**) (&libvlc_release_f) = dlsym(libvlc_lib, "libvlc_release");
  *(void**) (&libvlc_media_player_stop_f) = dlsym(libvlc_lib, "libvlc_media_player_stop");
  *(void**) (&libvlc_new_f) = dlsym(libvlc_lib, "libvlc_new");
  *(void**) (&libvlc_log_set_f) = dlsym(libvlc_lib, "libvlc_log_set");
  *(void**) (&libvlc_media_new_location_f) = dlsym(libvlc_lib, "libvlc_media_new_location");
  *(void**) (&libvlc_media_player_new_from_media_f) = dlsym(libvlc_lib, "libvlc_media_player_new_from_media");
  *(void**) (&libvlc_video_set_format_f) = dlsym(libvlc_lib, "libvlc_video_set_format");
  *(void**) (&libvlc_video_set_callbacks_f) = dlsym(libvlc_lib, "libvlc_video_set_callbacks");
  *(void**) (&libvlc_media_player_play_f) = dlsym(libvlc_lib, "libvlc_media_player_play");
  *(void**) (&libvlc_errmsg_f) = dlsym(libvlc_lib, "libvlc_errmsg");
  *(void**) (&libvlc_get_version_f) = dlsym(libvlc_lib, "libvlc_get_version");
}
// Do all the buffer checking work here to avoid unnecessary locking
void* LibvlcLockBuffer(void* opaque, void** planes) {
  LibvlcPrivateData* data = reinterpret_cast<LibvlcPrivateData*>(opaque);
  data->mutex.lock();

  uint8_t* buffer = data->buffer;
  data->buffer = data->prevBuffer;
  data->prevBuffer = buffer;

  *planes = data->buffer;
  return nullptr;
}

void LibvlcUnlockBuffer(void* opaque, void* picture, void *const *planes) {
  LibvlcPrivateData* data = reinterpret_cast<LibvlcPrivateData*>(opaque);

  bool newFrame = false;
  for( unsigned int i=0; i < data->bufferSize; i++ ) {
    if ( data->buffer[i] != data->prevBuffer[i] ) {
      newFrame = true;
      break;
    }
  }
  data->mutex.unlock();

  time_t now;
  time(&now);
  // Return frames slightly faster than 1fps (if time() supports greater than one second resolution)
  if ( newFrame || difftime(now, data->prevTime) >= 0.8 ) {
    data->prevTime = now;
    {
      std::lock_guard<std::mutex> lck(data->newImageMutex);
      data->newImage = true;
    }
    data->newImageCv.notify_all();
  }
}

LibvlcCamera::LibvlcCamera(
  const Monitor *monitor,
  const std::string &p_path,
  const std::string &p_user,
  const std::string &p_pass,
  const std::string &p_method,
  const std::string &p_options,
  int p_width,
  int p_height,
  int p_colours,
  int p_brightness,
  int p_contrast,
  int p_hue,
  int p_colour,
  bool p_capture,
  bool p_record_audio
) :
  Camera(
    monitor,
    LIBVLC_SRC,
    p_width,
    p_height,
    p_colours,
    ZM_SUBPIX_ORDER_DEFAULT_FOR_COLOUR(p_colours),
    p_brightness,
    p_contrast,
    p_hue,
    p_colour,
    p_capture,
    p_record_audio
  ),
  mPath(p_path),
  mUser(UriEncode(p_user)),
  mPass(UriEncode(p_pass)),
  mMethod(p_method),
  mOptions(p_options) {
  mLibvlcInstance = nullptr;
  mLibvlcMedia = nullptr;
  mLibvlcMediaPlayer = nullptr;
  mLibvlcData.buffer = nullptr;
  mLibvlcData.prevBuffer = nullptr;
  mOptArgV = nullptr;

  /* Has to be located inside the constructor so other components such as zma will receive correct colours and subpixel order */
  if ( colours == ZM_COLOUR_RGB32 ) {
    subpixelorder = ZM_SUBPIX_ORDER_BGRA;
    mTargetChroma = "RV32";
    mBpp = 4;
  } else if ( colours == ZM_COLOUR_RGB24 ) {
    subpixelorder = ZM_SUBPIX_ORDER_BGR;
    mTargetChroma = "RV24";
    mBpp = 3;
  } else if ( colours == ZM_COLOUR_GRAY8 ) {
    subpixelorder = ZM_SUBPIX_ORDER_NONE;
    mTargetChroma = "GREY";
    mBpp = 1;
  } else {
    mBpp = 0;
    Panic("Unexpected colours: %d",colours);
  }

  if ( capture ) {
    Initialise();
  }
}

LibvlcCamera::~LibvlcCamera() {
  if ( capture ) {
    Terminate();
  }

  mLibvlcData.newImageCv.notify_all(); // to unblock on termination (zm_terminate)

  if ( mLibvlcMediaPlayer != nullptr ) {
    (*libvlc_media_player_release_f)(mLibvlcMediaPlayer);
    mLibvlcMediaPlayer = nullptr;
  }
  if ( mLibvlcMedia != nullptr ) {
    (*libvlc_media_release_f)(mLibvlcMedia);
    mLibvlcMedia = nullptr;
  }
  if ( mLibvlcInstance != nullptr ) {
    (*libvlc_release_f)(mLibvlcInstance);
    mLibvlcInstance = nullptr;
  }
  if (libvlc_lib) {
    dlclose(libvlc_lib);
    libvlc_lib = nullptr;
  }
  if ( mOptArgV != nullptr ) {
    delete[] mOptArgV;
  }
}

void LibvlcCamera::Initialise() {
  bind_libvlc_symbols();
}

void LibvlcCamera::Terminate() {
  (*libvlc_media_player_stop_f)(mLibvlcMediaPlayer);
  if ( mLibvlcData.buffer ) {
    zm_freealigned(mLibvlcData.buffer);
    mLibvlcData.buffer = nullptr;
  }

  if ( mLibvlcData.prevBuffer ) {
    zm_freealigned(mLibvlcData.prevBuffer);
    mLibvlcData.prevBuffer = nullptr;
  }
}

int LibvlcCamera::PrimeCapture() {
  Debug(1, "Priming capture from %s, libvlc version %s", mPath.c_str(), (*libvlc_get_version_f)());

  opVect = Split(Options(), ",");

  Debug(1, "Method: '%s'", Method().c_str());

  // Set transport method as specified by method field, rtpUni is default
  if ( Method() == "rtpMulti" )
    opVect.push_back("--rtsp-mcast");
  else if ( Method() == "rtpRtsp" )
    opVect.push_back("--rtsp-tcp");
  else if ( Method() == "rtpRtspHttp" )
    opVect.push_back("--rtsp-http");

  opVect.push_back("--no-audio");

  if ( opVect.size() > 0 ) {
    mOptArgV = new char*[opVect.size()];
    Debug(2, "Number of Options: %zu", opVect.size());
    for (size_t i=0; i< opVect.size(); i++) {
      opVect[i] = TrimSpaces(opVect[i]);
      mOptArgV[i] = (char *)opVect[i].c_str();
      Debug(2, "set option %zu to '%s'", i, opVect[i].c_str());
    }
  }

  mLibvlcInstance = (*libvlc_new_f)(opVect.size(), (const char* const*)mOptArgV);
  if ( mLibvlcInstance == nullptr ) {
    Error("Unable to create libvlc instance due to: %s", (*libvlc_errmsg_f)());
    return -1;
  }
  (*libvlc_log_set_f)(mLibvlcInstance, LibvlcCamera::log_callback, nullptr);

  // recreate the path with encoded authentication info
  if( mUser.length() > 0 ) {
    std::string mMaskedPath = remove_authentication(mPath);

    std::string protocol = StringToUpper(mPath.substr(0, 4));
    if ( protocol == "RTSP" ) {
      // build the actual uri string with encoded parameters (from the user and pass fields)
      mPath = StringToLower(protocol) + "://" + mUser + ":" + mPass + "@" + mMaskedPath.substr(7, std::string::npos);
      Debug(1, "Rebuilt URI with encoded parameters: '%s'", mPath.c_str());
    }
  }

  mLibvlcMedia = (*libvlc_media_new_location_f)(mLibvlcInstance, mPath.c_str());
  if ( mLibvlcMedia == nullptr ) {
    Error("Unable to open input %s due to: %s", mPath.c_str(), (*libvlc_errmsg_f)());
    return -1;
  }

  mLibvlcMediaPlayer = (*libvlc_media_player_new_from_media_f)(mLibvlcMedia);
  if ( mLibvlcMediaPlayer == nullptr ) {
    Error("Unable to create player for %s due to: %s", mPath.c_str(), (*libvlc_errmsg_f)());
    return -1;
  }

  (*libvlc_video_set_format_f)(mLibvlcMediaPlayer, mTargetChroma.c_str(), width, height, width * mBpp);
  (*libvlc_video_set_callbacks_f)(mLibvlcMediaPlayer, &LibvlcLockBuffer, &LibvlcUnlockBuffer, nullptr, &mLibvlcData);

  mLibvlcData.bufferSize = width * height * mBpp;
  // Libvlc wants 32 byte alignment for images (should in theory do this for all image lines)
  mLibvlcData.buffer = (uint8_t*)zm_mallocaligned(64, mLibvlcData.bufferSize);
  mLibvlcData.prevBuffer = (uint8_t*)zm_mallocaligned(64, mLibvlcData.bufferSize);

  mLibvlcData.newImage = false;

  (*libvlc_media_player_play_f)(mLibvlcMediaPlayer);

  return 0;
}


int LibvlcCamera::PreCapture() {
  return 0;
}

// Should not return -1 as cancels capture. Always wait for image if available.
int LibvlcCamera::Capture(std::shared_ptr<ZMPacket> &zm_packet) {
  // newImage is a mutex/condition based flag to tell us when there is an image available
  {
    std::unique_lock<std::mutex> lck(mLibvlcData.newImageMutex);
    mLibvlcData.newImageCv.wait(lck, [&] { return mLibvlcData.newImage || zm_terminate; });
    mLibvlcData.newImage = false;
  }

  if (zm_terminate)
    return 0;

  mLibvlcData.mutex.lock();
  zm_packet->image->Assign(width, height, colours, subpixelorder, mLibvlcData.buffer, width * height * mBpp);
  zm_packet->packet->stream_index = mVideoStreamId;
  zm_packet->stream = mVideoStream;
  mLibvlcData.mutex.unlock();

  return 1;
}

int LibvlcCamera::PostCapture() {
  return 0;
}

void LibvlcCamera::log_callback(void *ptr, int level, const libvlc_log_t *ctx, const char *fmt, va_list vargs) {
  Logger *log = Logger::fetch();
  int log_level = Logger::NOLOG;
  if ( level == LIBVLC_ERROR ) {
    log_level = Logger::WARNING; // ffmpeg outputs a lot of errors that don't really affect anything.
    //log_level = Logger::ERROR;
  } else if ( level == LIBVLC_WARNING ) {
    log_level = Logger::INFO;
    //log_level = Logger::WARNING;
  } else if ( level == LIBVLC_NOTICE ) {
    log_level = Logger::DEBUG1;
    //log_level = Logger::INFO;
  } else if ( level == LIBVLC_DEBUG ) {
    log_level = Logger::DEBUG3;
  } else {
    Error("Unknown log level %d", level);
  }

  if ( log ) {
    char logString[8192];
    vsnprintf(logString, sizeof(logString) - 1, fmt, vargs);
    log->logPrint(false, __FILE__, __LINE__, log_level, "%s", logString);
  }
}
#endif // HAVE_LIBVLC
