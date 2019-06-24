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

#include "zm.h"
#include "zm_signal.h"
#include "zm_libvlc_camera.h"

#if HAVE_LIBVLC

// Do all the buffer checking work here to avoid unnecessary locking 
void* LibvlcLockBuffer(void* opaque, void** planes) {
  LibvlcPrivateData* data = reinterpret_cast<LibvlcPrivateData*>(opaque);
  data->mutex.lock();

  uint8_t* buffer = data->buffer;
  data->buffer = data->prevBuffer;
  data->prevBuffer = buffer;

  *planes = data->buffer;
  return NULL;
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
    data->newImage.updateValueSignal(true);
  }
}

LibvlcCamera::LibvlcCamera(
    int p_id,
    const std::string &p_path,
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
      p_id,
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
  mMethod(p_method),
  mOptions(p_options)
{  
  mLibvlcInstance = NULL;
  mLibvlcMedia = NULL;
  mLibvlcMediaPlayer = NULL;
  mLibvlcData.buffer = NULL;
  mLibvlcData.prevBuffer = NULL;
  mOptArgV = NULL;

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
  if ( mLibvlcMediaPlayer != NULL ) {
    libvlc_media_player_release(mLibvlcMediaPlayer);
    mLibvlcMediaPlayer = NULL;
  }
  if ( mLibvlcMedia != NULL ) {
    libvlc_media_release(mLibvlcMedia);
    mLibvlcMedia = NULL;
  }
  if ( mLibvlcInstance != NULL ) {
    libvlc_release(mLibvlcInstance);
    mLibvlcInstance = NULL;
  }
  if ( mOptArgV != NULL ) {
    delete[] mOptArgV;
  }
}

void LibvlcCamera::Initialise() {
}

void LibvlcCamera::Terminate() {
  libvlc_media_player_stop(mLibvlcMediaPlayer);
  if ( mLibvlcData.buffer ) {
    zm_freealigned(mLibvlcData.buffer);
    mLibvlcData.buffer = NULL;
  }
  if ( mLibvlcData.prevBuffer ) {
    zm_freealigned(mLibvlcData.prevBuffer);
    mLibvlcData.prevBuffer = NULL;
  }
}

int LibvlcCamera::PrimeCapture() {
  Info("Priming capture from %s", mPath.c_str());

  StringVector opVect = split(Options(), ",");

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
    Debug(2, "Number of Options: %d",opVect.size());
    for (size_t i=0; i< opVect.size(); i++) {
      opVect[i] = trimSpaces(opVect[i]);
      mOptArgV[i] = (char *)opVect[i].c_str();
      Debug(2, "set option %d to '%s'", i,  opVect[i].c_str());
    }
  }

  mLibvlcInstance = libvlc_new(opVect.size(), (const char* const*)mOptArgV);
  if ( mLibvlcInstance == NULL ) {
    Error("Unable to create libvlc instance due to: %s", libvlc_errmsg());
    return -1;
  }
  libvlc_log_set(mLibvlcInstance, LibvlcCamera::log_callback, NULL);


  mLibvlcMedia = libvlc_media_new_location(mLibvlcInstance, mPath.c_str());
  if ( mLibvlcMedia == NULL ) {
    Error("Unable to open input %s due to: %s", mPath.c_str(), libvlc_errmsg());
    return -1;
  }

  mLibvlcMediaPlayer = libvlc_media_player_new_from_media(mLibvlcMedia);
  if ( mLibvlcMediaPlayer == NULL ) {
    Error("Unable to create player for %s due to: %s", mPath.c_str(), libvlc_errmsg());
    return -1;
  }

  libvlc_video_set_format(mLibvlcMediaPlayer, mTargetChroma.c_str(), width, height, width * mBpp);
  libvlc_video_set_callbacks(mLibvlcMediaPlayer, &LibvlcLockBuffer, &LibvlcUnlockBuffer, NULL, &mLibvlcData);

  mLibvlcData.bufferSize = width * height * mBpp;
  // Libvlc wants 32 byte alignment for images (should in theory do this for all image lines)
  mLibvlcData.buffer = (uint8_t*)zm_mallocaligned(64, mLibvlcData.bufferSize);
  mLibvlcData.prevBuffer = (uint8_t*)zm_mallocaligned(64, mLibvlcData.bufferSize);
  
  mLibvlcData.newImage.setValueImmediate(false);

  libvlc_media_player_play(mLibvlcMediaPlayer);

  return 0;
}


int LibvlcCamera::PreCapture() {    
  return 0;
}

// Should not return -1 as cancels capture. Always wait for image if available.
int LibvlcCamera::Capture(Image &image) {   

  // newImage is a mutex/condition based flag to tell us when there is an image available
  while( !mLibvlcData.newImage.getValueImmediate() ) {
    if (zm_terminate)
      return 0;
    mLibvlcData.newImage.getUpdatedValue(1);
  }

  mLibvlcData.mutex.lock();
  image.Assign(width, height, colours, subpixelorder, mLibvlcData.buffer, width * height * mBpp);
  mLibvlcData.newImage.setValueImmediate(false);
  mLibvlcData.mutex.unlock();

  return 1;
}

int LibvlcCamera::CaptureAndRecord(Image &image, timeval recording, char* event_directory) {
  return 0;
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
    char            logString[8192];
    vsnprintf(logString, sizeof(logString)-1, fmt, vargs);
    log->logPrint(false, __FILE__, __LINE__, log_level, logString);
  }
}
#endif // HAVE_LIBVLC
