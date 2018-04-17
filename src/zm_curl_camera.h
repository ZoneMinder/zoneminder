//
// ZoneMinder cURL Class Interface, $Date: 2008-07-25 10:33:23 +0100 (Fri, 25 Jul 2008) $, $Revision: 2611 $
// Copyright (C) 2001-2008 Philip Coombes
// 
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
// 
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// 
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
// 

#ifndef ZM_CURL_CAMERA_H
#define ZM_CURL_CAMERA_H

#if HAVE_LIBCURL

#include "zm_camera.h"
#include "zm_ffmpeg.h"
#include "zm_buffer.h"
#include "zm_regexp.h"
#include "zm_utils.h"
#include "zm_signal.h"
#include <string>
#include <deque>

#if HAVE_CURL_CURL_H
#include <curl/curl.h>
#endif

//
// Class representing 'curl' cameras, i.e. those which are
// accessed using the curl library
//
class cURLCamera : public Camera {
protected:
  typedef enum {MODE_UNSET, MODE_SINGLE, MODE_STREAM} mode_t;

  std::string mPath;
  std::string mUser;
  std::string mPass;

  /* cURL object(s) */
  CURL* c;

  /* Shared data */
  volatile bool bTerminate;
  volatile bool bReset;
  volatile mode_t mode;
  Buffer databuffer;
  std::deque<size_t> single_offsets;

  /* pthread objects */
  pthread_t thread;
  pthread_mutex_t shareddata_mutex;
  pthread_cond_t data_available_cond;
  pthread_cond_t request_complete_cond;

public:
  cURLCamera( int p_id, const std::string &path, const std::string &username, const std::string &password, unsigned int p_width, unsigned int p_height, int p_colours, int p_brightness, int p_contrast, int p_hue, int p_colour, bool p_capture, bool p_record_audio );
  ~cURLCamera();

  const std::string &Path() const { return( mPath ); }
  const std::string &Username() const { return( mUser ); }
  const std::string &Password() const { return( mPass ); }

  void Initialise();
  void Terminate();
  int Close() { return 0; };

  int PrimeCapture();
  int PreCapture();
  int Capture( Image &image );
  int PostCapture();
  int CaptureAndRecord( Image &image, struct timeval recording, char* event_directory );

  size_t data_callback(void *buffer, size_t size, size_t nmemb, void *userdata);
  size_t header_callback(void *buffer, size_t size, size_t nmemb, void *userdata);
  int progress_callback(void *userdata, double dltotal, double dlnow, double ultotal, double ulnow);  
  int debug_callback(CURL* handle, curl_infotype type, char* str, size_t strsize, void* data);
  void* thread_func();
  int lock();
  int unlock();

};

/* Dispatchers */
size_t header_callback_dispatcher(void *buffer, size_t size, size_t nmemb, void *userdata);
size_t data_callback_dispatcher(void *buffer, size_t size, size_t nmemb, void *userdata);
int progress_callback_dispatcher(void *userdata, double dltotal, double dlnow, double ultotal, double ulnow);
void* thread_func_dispatcher(void* object);

#endif // HAVE_LIBCURL

#endif // ZM_CURL_CAMERA_H
