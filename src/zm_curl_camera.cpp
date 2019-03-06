//
// ZoneMinder cURL Camera Class Implementation, $Date: 2009-01-16 12:18:50 +0000 (Fri, 16 Jan 2009) $, $Revision: 2713 $
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

#include "zm.h"

#include "zm_curl_camera.h"

#include "zm_packetqueue.h"

#if HAVE_LIBCURL

#define CURL_MAXRETRY 5
#define CURL_BUFFER_INITIAL_SIZE 65536

const char* content_length_match = "Content-Length:";
const char* content_type_match = "Content-Type:";
size_t content_length_match_len;
size_t content_type_match_len;

cURLCamera::cURLCamera( int p_id, const std::string &p_path, const std::string &p_user, const std::string &p_pass, unsigned int p_width, unsigned int p_height, int p_colours, int p_brightness, int p_contrast, int p_hue, int p_colour, bool p_capture, bool p_record_audio ) :
  Camera( p_id, CURL_SRC, p_width, p_height, p_colours, ZM_SUBPIX_ORDER_DEFAULT_FOR_COLOUR(p_colours), p_brightness, p_contrast, p_hue, p_colour, p_capture, p_record_audio ),
  mPath( p_path ), mUser( p_user ), mPass ( p_pass ), bTerminate( false ), bReset( false ), mode ( MODE_UNSET )
{

  if ( capture ) {
    Initialise();
  }
}

cURLCamera::~cURLCamera() {
  if ( capture ) {

    Terminate();
  }
}

void cURLCamera::Initialise() {
  content_length_match_len = strlen(content_length_match);
  content_type_match_len = strlen(content_type_match);

  databuffer.expand(CURL_BUFFER_INITIAL_SIZE);

  /* cURL initialization */
  CURLcode cRet = curl_global_init(CURL_GLOBAL_ALL);
  if(cRet != CURLE_OK) {
    Fatal("libcurl initialization failed: ", curl_easy_strerror(cRet));
  }

  Debug(2,"libcurl version: %s",curl_version());

  /* Create the shared data mutex */
  int nRet = pthread_mutex_init(&shareddata_mutex, NULL);
  if(nRet != 0) {
    Fatal("Shared data mutex creation failed: %s",strerror(nRet));
  }
  /* Create the data available condition variable */
  nRet = pthread_cond_init(&data_available_cond, NULL);
  if(nRet != 0) {
    Fatal("Data available condition variable creation failed: %s",strerror(nRet));
  }
  /* Create the request complete condition variable */
  nRet = pthread_cond_init(&request_complete_cond, NULL);
  if(nRet != 0) {
    Fatal("Request complete condition variable creation failed: %s",strerror(nRet));
  }

  /* Create the thread */
  nRet = pthread_create(&thread, NULL, thread_func_dispatcher, this);
  if(nRet != 0) {
    Fatal("Thread creation failed: %s",strerror(nRet));
  }
}

void cURLCamera::Terminate() {
  /* Signal the thread to terminate */
  bTerminate = true;

  /* Wait for thread termination */
  pthread_join(thread, NULL);

  /* Destroy condition variables */
  pthread_cond_destroy(&request_complete_cond);
  pthread_cond_destroy(&data_available_cond);

  /* Destroy mutex */
  pthread_mutex_destroy(&shareddata_mutex);

  /* cURL cleanup */
  curl_global_cleanup();

}

int cURLCamera::PrimeCapture() {
  //Info( "Priming capture from %s", mPath.c_str() );
  return 0;
}

int cURLCamera::PreCapture() {
    // Nothing to do here
    return( 0 );
}

int cURLCamera::Capture( Image &image ) {
  bool frameComplete = false;

  /* MODE_STREAM specific variables */
  bool SubHeadersParsingComplete = false;
  unsigned int frame_content_length = 0;
  std::string frame_content_type;
  bool need_more_data = false;
  int nRet;

  /* Grab the mutex to ensure exclusive access to the shared data */
  lock();

  while ( !frameComplete ) {

    /* If the work thread did a reset, reset our local variables */
    if ( bReset ) {
      SubHeadersParsingComplete = false;
      frame_content_length = 0;
      frame_content_type.clear();
      need_more_data = false;
      bReset = false;
    }

    if ( mode == MODE_UNSET ) {
      /* Don't have a mode yet. Sleep while waiting for data */
      nRet = pthread_cond_wait(&data_available_cond,&shareddata_mutex);
      if ( nRet != 0 ) {
        Error("Failed waiting for available data condition variable: %s",strerror(nRet));
        return -20;
      }
    }

    if ( mode == MODE_STREAM ) {

      /* Subheader parsing */
      while( !SubHeadersParsingComplete && !need_more_data ) {

        size_t crlf_start, crlf_end, crlf_size;
        std::string subheader;

        /* Check if the buffer contains something */
        if ( databuffer.empty() ) {
          /* Empty buffer, wait for data */
          need_more_data = true;
          break;
        }
     
        /* Find crlf start */
        crlf_start = memcspn(databuffer,"\r\n",databuffer.size());
        if ( crlf_start == databuffer.size() ) {
          /* Not found, wait for more data */
          need_more_data = true;
          break;
        }

        /* See if we have enough data for determining crlf length */
        if ( databuffer.size() < crlf_start+5 ) {
          /* Need more data */
          need_more_data = true;
          break;
        }

        /* Find crlf end and calculate crlf size */
        crlf_end = memspn(((const char*)databuffer.head())+crlf_start,"\r\n",5);
        crlf_size = (crlf_start + crlf_end) - crlf_start;

        /* Is this the end of a previous stream? (This is just before the boundary) */
        if ( crlf_start == 0 ) {
          databuffer.consume(crlf_size);
          continue;        
        }

        /* Check for invalid CRLF size */
        if ( crlf_size > 4 ) {
          Error("Invalid CRLF length");
        }

        /* Check if the crlf is \n\n or \r\n\r\n (marks end of headers, this is the last header) */
        if( (crlf_size == 2 && memcmp(((const char*)databuffer.head())+crlf_start,"\n\n",2) == 0) || (crlf_size == 4 && memcmp(((const char*)databuffer.head())+crlf_start,"\r\n\r\n",4) == 0) ) {
          /* This is the last header */
          SubHeadersParsingComplete = true;
        }

        /* Copy the subheader, excluding the crlf */
        subheader.assign(databuffer, crlf_start);

        /* Advance the buffer past this one */
        databuffer.consume(crlf_start+crlf_size);

        Debug(7,"Got subheader: %s",subheader.c_str());

        /* Find where the data in this header starts */
        size_t subheader_data_start = subheader.rfind(' ');
        if ( subheader_data_start == std::string::npos ) {
          subheader_data_start = subheader.find(':');
        }

        /* Extract the data into a string */
        std::string subheader_data = subheader.substr(subheader_data_start+1, std::string::npos);

        Debug(8,"Got subheader data: %s",subheader_data.c_str());

        /* Check the header */
        if(strncasecmp(subheader.c_str(),content_length_match,content_length_match_len) == 0) {  
          /* Found the content-length header */
          frame_content_length = atoi(subheader_data.c_str());
          Debug(6,"Got content-length subheader: %d",frame_content_length);
        } else if(strncasecmp(subheader.c_str(),content_type_match,content_type_match_len) == 0) { 
          /* Found the content-type header */
          frame_content_type = subheader_data;
          Debug(6,"Got content-type subheader: %s",frame_content_type.c_str());
        }

      }

      /* Attempt to extract the frame */
      if(!need_more_data) {
        if(!SubHeadersParsingComplete) {
          /* We haven't parsed all headers yet */
          need_more_data = true;
        } else if ( ! frame_content_length ) {
          /* Invalid frame */
          Error("Invalid frame: invalid content length");
        } else if ( frame_content_type != "image/jpeg" ) {
          /* Unsupported frame type */
          Error("Unsupported frame: %s",frame_content_type.c_str());
        } else if(frame_content_length > databuffer.size()) {
          /* Incomplete frame, wait for more data */
          need_more_data = true;
        } else {
          /* All good. decode the image */
          image.DecodeJpeg(databuffer.extract(frame_content_length), frame_content_length, colours, subpixelorder);
          frameComplete = true;
        }
      }

      /* Attempt to get more data */
      if(need_more_data) {
        nRet = pthread_cond_wait(&data_available_cond,&shareddata_mutex);
        if(nRet != 0) {
          Error("Failed waiting for available data condition variable: %s",strerror(nRet));
          return -18;
        }
        need_more_data = false;
      }

    } else if(mode == MODE_SINGLE) { 
      /* Check if we have anything */
      if (!single_offsets.empty()) {
        if( (single_offsets.front() > 0) && (databuffer.size() >= single_offsets.front()) ) {
          /* Extract frame */
          image.DecodeJpeg(databuffer.extract(single_offsets.front()), single_offsets.front(), colours, subpixelorder);
          single_offsets.pop_front();
          frameComplete = true;
        } else {
          /* This shouldn't happen */
          Error("Internal error. Attempting recovery");
          databuffer.consume(single_offsets.front());
          single_offsets.pop_front();
        }
      } else {
        /* Don't have a frame yet, wait for the request complete condition variable */
        nRet = pthread_cond_wait(&request_complete_cond,&shareddata_mutex);
        if(nRet != 0) {
          Error("Failed waiting for request complete condition variable: %s",strerror(nRet));
          return -19;
        }
      }
    } else {
      /* Failed to match content-type */
      Fatal("Unable to match Content-Type. Check URL, username and password");
    } /* mode */

  } /* frameComplete loop */

  /* Release the mutex */
  unlock();

  if(!frameComplete)
    return -1;

  return 1;
}

int cURLCamera::PostCapture() {
    // Nothing to do here
    return( 0 );
}

int cURLCamera::CaptureAndRecord( Image &image, struct timeval recording, char* event_directory ) {
  Error("Capture and Record not implemented for the cURL camera type");
  // Nothing to do here
  return( 0 );
}

size_t cURLCamera::data_callback(void *buffer, size_t size, size_t nmemb, void *userdata) {
  lock();

  /* Append the data we just received to our buffer */
  databuffer.append((const char*)buffer, size*nmemb);

  /* Signal data available */
  int nRet = pthread_cond_signal(&data_available_cond);
  if ( nRet != 0 ) {
    Error("Failed signaling data available condition variable: %s",strerror(nRet));
    unlock();
    return -16;
  }

  unlock();

  /* Return bytes processed */
  return size*nmemb;
}

size_t cURLCamera::header_callback( void *buffer, size_t size, size_t nmemb, void *userdata) {
  std::string header;
  header.assign((const char*)buffer, size*nmemb);
  
  Debug(4,"Got header: %s",header.c_str());

  /* Check Content-Type header */  
  if(strncasecmp(header.c_str(),content_type_match,content_type_match_len) == 0) {    
    size_t pos = header.find(';');
    if(pos != std::string::npos) {
      header.erase(pos, std::string::npos);
    }

    pos = header.rfind(' ');
    if(pos == std::string::npos) {
      pos = header.find(':');
    }

    std::string content_type = header.substr(pos+1, std::string::npos);
    Debug(6,"Content-Type is: %s",content_type.c_str());

    lock();

    const char* multipart_match = "multipart/x-mixed-replace";
    const char* image_jpeg_match = "image/jpeg";
    if(strncasecmp(content_type.c_str(),multipart_match,strlen(multipart_match)) == 0) {  
      Debug(7,"Content type matched as multipart/x-mixed-replace");
      mode = MODE_STREAM;
    } else if(strncasecmp(content_type.c_str(),image_jpeg_match,strlen(image_jpeg_match)) == 0) {
      Debug(7,"Content type matched as image/jpeg");
      mode = MODE_SINGLE;
    }

    unlock();
  }
  
  /* Return bytes processed */
  return size*nmemb;
}

void* cURLCamera::thread_func() {
  long tRet;
  double dSize;

  c = curl_easy_init();
  if(c == NULL) {
    Fatal("Failed getting easy handle from libcurl");
  }

  CURLcode cRet;
  /* Set URL */
  cRet = curl_easy_setopt(c, CURLOPT_URL, mPath.c_str());
  if(cRet != CURLE_OK)
    Fatal("Failed setting libcurl URL: %s", curl_easy_strerror(cRet));
  
  /* Header callback */
  cRet = curl_easy_setopt(c, CURLOPT_HEADERFUNCTION, &header_callback_dispatcher);
  if(cRet != CURLE_OK)
    Fatal("Failed setting libcurl header callback function: %s", curl_easy_strerror(cRet));
  cRet = curl_easy_setopt(c, CURLOPT_HEADERDATA, this);
  if(cRet != CURLE_OK)
    Fatal("Failed setting libcurl header callback object: %s", curl_easy_strerror(cRet));

  /* Data callback */
  cRet = curl_easy_setopt(c, CURLOPT_WRITEFUNCTION, &data_callback_dispatcher);
  if(cRet != CURLE_OK)
    Fatal("Failed setting libcurl data callback function: %s", curl_easy_strerror(cRet));
  cRet = curl_easy_setopt(c, CURLOPT_WRITEDATA, this);
  if(cRet != CURLE_OK)
    Fatal("Failed setting libcurl data callback object: %s", curl_easy_strerror(cRet));

  /* Progress callback */
  cRet = curl_easy_setopt(c, CURLOPT_NOPROGRESS, 0);
  if(cRet != CURLE_OK)
    Fatal("Failed enabling libcurl progress callback function: %s", curl_easy_strerror(cRet));  
  cRet = curl_easy_setopt(c, CURLOPT_PROGRESSFUNCTION, &progress_callback_dispatcher);
  if(cRet != CURLE_OK)
    Fatal("Failed setting libcurl progress callback function: %s", curl_easy_strerror(cRet));
  cRet = curl_easy_setopt(c, CURLOPT_PROGRESSDATA, this);
  if(cRet != CURLE_OK)
    Fatal("Failed setting libcurl progress callback object: %s", curl_easy_strerror(cRet));

  /* Set username and password */
  if(!mUser.empty()) {
    cRet = curl_easy_setopt(c, CURLOPT_USERNAME, mUser.c_str());
    if(cRet != CURLE_OK)
      Error("Failed setting username: %s", curl_easy_strerror(cRet));
  }
  if(!mPass.empty()) {
    cRet = curl_easy_setopt(c, CURLOPT_PASSWORD, mPass.c_str());
    if(cRet != CURLE_OK)
      Error("Failed setting password: %s", curl_easy_strerror(cRet));
  }

  /* Authenication preference */
  cRet = curl_easy_setopt(c, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
  if(cRet != CURLE_OK)
    Warning("Failed setting libcurl acceptable http authenication methods: %s", curl_easy_strerror(cRet));


  /* Work loop */
  for(int attempt=1;attempt<=CURL_MAXRETRY;attempt++) {
    tRet = 0;
    while(!bTerminate) {
      /* Do the work */
      cRet = curl_easy_perform(c);

      if(mode == MODE_SINGLE) {
        if(cRet != CURLE_OK) {
          break;
        }
        /* Attempt to get the size of the file */
        cRet = curl_easy_getinfo(c, CURLINFO_CONTENT_LENGTH_DOWNLOAD, &dSize);
        if(cRet != CURLE_OK) {
          break;
        }
        /* We need to lock for the offsets array and the condition variable */
        lock();
        /* Push the size into our offsets array */
        if(dSize > 0) {
          single_offsets.push_back(dSize);
        } else {
          Fatal("Unable to get the size of the image");
        }
        /* Signal the request complete condition variable */
        tRet = pthread_cond_signal(&request_complete_cond);
        if(tRet != 0) {
          Error("Failed signaling request completed condition variable: %s",strerror(tRet));
        }
        /* Unlock */
        unlock();

      } else if (mode == MODE_STREAM) {
        break;
      }
    }

    /* Return value checking */
    if(cRet == CURLE_ABORTED_BY_CALLBACK || bTerminate) {
      /* Aborted */
      break;
    } else if (cRet != CURLE_OK) {
      /* Some error */
      Error("cURL Request failed: %s",curl_easy_strerror(cRet));
      if(attempt < CURL_MAXRETRY) {
        Error("Retrying.. Attempt %d of %d",attempt,CURL_MAXRETRY);
        /* Do a reset */
        lock();
        databuffer.clear();
        single_offsets.clear();
        mode = MODE_UNSET;
        bReset = true;
        unlock();
      }
      tRet = -50;
    }
  }
      
  /* Cleanup */
  curl_easy_cleanup(c);
  c = NULL;
  
  return (void*)tRet;
}

int cURLCamera::lock() {
  int nRet;

  /* Lock shared data */
  nRet = pthread_mutex_lock(&shareddata_mutex);
  if(nRet != 0) {
    Error("Failed locking shared data mutex: %s",strerror(nRet));
  }
  return nRet;
}

int cURLCamera::unlock() {
  int nRet;

  /* Unlock shared data */
  nRet = pthread_mutex_unlock(&shareddata_mutex);
  if(nRet != 0) {
    Error("Failed unlocking shared data mutex: %s",strerror(nRet));
  }
  return nRet;
}

int cURLCamera::progress_callback(void *userdata, double dltotal, double dlnow, double ultotal, double ulnow) {
  /* Signal the curl thread to terminate */
  if(bTerminate)
    return -10;
  
  return 0;
}

/* These functions call the functions in the class for the correct object */
size_t data_callback_dispatcher(void *buffer, size_t size, size_t nmemb, void *userdata) {
  return reinterpret_cast<cURLCamera*>(userdata)->data_callback(buffer,size,nmemb,userdata);
}

size_t header_callback_dispatcher(void *buffer, size_t size, size_t nmemb, void *userdata) {
  return reinterpret_cast<cURLCamera*>(userdata)->header_callback(buffer,size,nmemb,userdata);
}

int progress_callback_dispatcher(void *userdata, double dltotal, double dlnow, double ultotal, double ulnow) {
  return reinterpret_cast<cURLCamera*>(userdata)->progress_callback(userdata,dltotal,dlnow,ultotal,ulnow);
}

void* thread_func_dispatcher(void* object) {
  return reinterpret_cast<cURLCamera*>(object)->thread_func();
}

#endif // HAVE_LIBCURL
