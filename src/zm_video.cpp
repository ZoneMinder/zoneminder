// Copyright (C) 2001-2017 ZoneMinder LLC
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
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//
#include "zm.h"
#include "zm_video.h"
#include "zm_image.h"
#include "zm_utils.h"
#include "zm_rgb.h"
#include <sstream>
#include <string>
#include <vector>

VideoWriter::VideoWriter(
    const char* p_container,
    const char* p_codec,
    const char* p_path,
    const unsigned int p_width,
    const unsigned int p_height,
    const unsigned int p_colours,
    const unsigned int p_subpixelorder) :
  container(p_container),
  codec(p_codec),
  path(p_path),
  width(p_width),
  height(p_height),
  colours(p_colours),
  subpixelorder(p_subpixelorder),
  frame_count(0) {
  Debug(7, "Video object created");

  /* Parameter checking */
  if ( path.empty() ) {
    Error("Invalid file path");
  }
  if ( !width || !height ) {
    Error("Invalid width or height");
  }
}

VideoWriter::~VideoWriter() {
  Debug(7, "Video object destroyed");
}

int VideoWriter::Reset(const char* new_path) {
  /* Common variables reset */

  /* If there is a new path, use it */
  if ( new_path != NULL ) {
    path = new_path;
  }

  /* Reset frame counter */
  frame_count = 0;

  return 0;
}


#if ZM_HAVE_VIDEOWRITER_X264MP4
X264MP4Writer::X264MP4Writer(
    const char* p_path,
    const unsigned int p_width,
    const unsigned int p_height,
    const unsigned int p_colours,
    const unsigned int p_subpixelorder,
    const std::vector<EncoderParameter_t>* p_user_params) :
  VideoWriter(
      "mp4",
      "h264",
      p_path,
      p_width,
      p_height,
      p_colours,
      p_subpixelorder),
  bOpen(false),
  bGotH264AVCInfo(false),
  bFirstFrame(true) {
  /* Initialize ffmpeg if it hasn't been initialized yet */
  FFMPEGInit();

  /* Initialize swscale */
  zm_pf = GetFFMPEGPixelFormat(colours, subpixelorder);
  if ( zm_pf == 0 ) {
    Error("Unable to match ffmpeg pixelformat");
  }
  codec_pf = AV_PIX_FMT_YUV420P;

  if ( ! swscaleobj.init() ) {
    Error("Failed init swscaleobj");
    return;
  }

  swscaleobj.SetDefaults(zm_pf, codec_pf, width, height);

  /* Calculate the image sizes. We will need this for parameter checking */
  zm_imgsize = colours * width * height;
#if LIBAVUTIL_VERSION_CHECK(54, 6, 0, 6, 0)
  codec_imgsize = av_image_get_buffer_size(codec_pf, width, height, 1);
#else
  codec_imgsize = avpicture_get_size(codec_pf, width, height);
#endif
  if ( !codec_imgsize ) {
    Error("Failed calculating codec pixel format image size");
  }

  /* If supplied with user parameters to the encoder, copy them */
  if ( p_user_params != NULL ) {
    user_params = *p_user_params;
  }

  /* Setup x264 parameters */
  if ( x264config() < 0 ) {
    Error("Failed setting x264 parameters");
  }

  /* Allocate x264 input picture */
  x264_picture_alloc(
      &x264picin,
      X264_CSP_I420,
      x264params.i_width,
      x264params.i_height);
}

X264MP4Writer::~X264MP4Writer() {
  /* Free x264 input picture */
  x264_picture_clean(&x264picin);

  if ( bOpen )
    Close();
}

int X264MP4Writer::Open() {
  /* Open the encoder */
  x264enc = x264_encoder_open(&x264params);
  if ( x264enc == NULL ) {
    Error("Failed opening x264 encoder");
    return -1;
  }

  // Debug(4,"x264 maximum delayed frames: %d",
  // x264_encoder_maximum_delayed_frames(x264enc));

  x264_nal_t* nals;
  int i_nals;
  if ( !x264_encoder_headers(x264enc, &nals, &i_nals) ) {
    Error("Failed getting encoder headers");
    return -2;
  }

  /* Search SPS NAL for AVC information */
  for ( int i = 0; i < i_nals; i++ ) {
    if ( nals[i].i_type == NAL_SPS ) {
      x264_profleindication = nals[i].p_payload[5];
      x264_profilecompat = nals[i].p_payload[6];
      x264_levelindication = nals[i].p_payload[7];
      bGotH264AVCInfo = true;
      break;
    }
  }
  if ( !bGotH264AVCInfo ) {
    Warning("Missing AVC information");
  }

  /* Create the file */
  mp4h = MP4Create((path + ".incomplete").c_str());
  if ( mp4h == MP4_INVALID_FILE_HANDLE ) {
    Error("Failed creating mp4 file: %s", path.c_str());
    return -10;
  }

  /* Set the global timescale */
  if ( !MP4SetTimeScale(mp4h, 1000) ) {
    Error("Failed setting timescale");
    return -11;
  }

  /* Set the global video profile */
  /* I am a bit confused about this one.
     I couldn't find what the value should be
     Some use 0x15 while others use 0x7f. */
  MP4SetVideoProfileLevel(mp4h, 0x7f);

  /* Add H264 video track */
  mp4vtid = MP4AddH264VideoTrack(
      mp4h,
      1000,
      MP4_INVALID_DURATION,
      width,
      height,
      x264_profleindication,
      x264_profilecompat,
      x264_levelindication,
      3);
  if ( mp4vtid == MP4_INVALID_TRACK_ID ) {
    Error("Failed adding H264 video track");
    return -12;
  }

  bOpen = true;

  return 0;
}

int X264MP4Writer::Close() {
  /* Flush all pending frames */
  for ( int i = (x264_encoder_delayed_frames(x264enc) + 1); i > 0; i-- ) {
Debug(1,"Encoding delayed frame");
    if ( x264encodeloop(true) < 0 )
      break;
  }

  /* Close the encoder */
  x264_encoder_close(x264enc);

  /* Close MP4 handle */
  MP4Close(mp4h);

  Debug(1,"Optimising");
  /* Required for proper HTTP streaming */
  MP4Optimize((path + ".incomplete").c_str(), path.c_str());

  /* Delete the temporary file */
  unlink((path + ".incomplete").c_str());

  bOpen = false;

  Debug(1, "Video closed. Total frames: %d", frame_count);

  return 0;
}

int X264MP4Writer::Reset(const char* new_path) {
  /* Close the encoder and file */
  if ( bOpen )
    Close();

  /* Reset common variables */
  VideoWriter::Reset(new_path);

  /* Reset local variables */
  bFirstFrame = true;
  bGotH264AVCInfo = false;
  prevnals.clear();
  prevpayload.clear();

  /* Reset x264 parameters */
  x264config();

  /* Open the encoder */
  Open();

  return 0;
}

int X264MP4Writer::Encode(
    const uint8_t* data,
    const size_t data_size,
    const unsigned int frame_time) {
  /* Parameter checking */
  if ( data == NULL ) {
    Error("NULL buffer");
    return -1;
  }

  if ( data_size != zm_imgsize ) {
    Error("The data buffer size (%d) != expected (%d)", data_size, zm_imgsize);
    return -2;
  }

  if ( !bOpen ) {
    Warning("The encoder was not initialized, initializing now");
    Open();
  }

  /* Convert the image into the x264 input picture */
  if ( swscaleobj.ConvertDefaults(data, data_size, x264picin.img.plane[0], codec_imgsize) < 0 ) {
    Error("Image conversion failed");
    return -3;
  }

  /* Set PTS */
  x264picin.i_pts = frame_time;

  /* Do the encoding */
  x264encodeloop();

  /* Increment frame counter */
  frame_count++;

  return 0;
}

int X264MP4Writer::Encode(const Image* img, const unsigned int frame_time) {
  if ( img->Width() != width ) {
    Error("Source image width differs. Source: %d Output: %d", img->Width(), width);
    return -12;
  }

  if ( img->Height() != height ) {
    Error("Source image height differs. Source: %d Output: %d", img->Height(), height);
    return -13;
  }

  return Encode(img->Buffer(), img->Size(), frame_time);
}

int X264MP4Writer::x264config() {
  /* Sets up the encoder configuration */

  int x264ret;

  /* Defaults */
  const char* preset = "veryfast";
  const char* tune = "stillimage";
  const char* profile = "main";

  /* Search the user parameters for preset, tune and profile */
  for ( unsigned int i = 0; i < user_params.size(); i++ ) {
    if ( strcmp(user_params[i].pname, "preset") == 0 ) {
      /* Got preset */
      preset = user_params[i].pvalue;
    } else if ( strcmp(user_params[i].pname, "tune") == 0 ) {
      /* Got tune */
      tune = user_params[i].pvalue;
    } else if ( strcmp(user_params[i].pname, "profile") == 0 ) {
      /* Got profile */
      profile = user_params[i].pvalue;
    }
  }

  /* Set the defaults and preset and tune */
  x264ret = x264_param_default_preset(&x264params, preset, tune);
  if ( x264ret != 0 ) {
    Error("Failed setting x264 preset %s and tune %s : %d", preset, tune, x264ret);
  }

  /* Set the profile */
  x264ret = x264_param_apply_profile(&x264params, profile);
  if ( x264ret != 0 ) {
    Error("Failed setting x264 profile %s : %d", profile, x264ret);
  }

  /* Input format */
  x264params.i_width = width;
  x264params.i_height = height;
  x264params.i_csp = X264_CSP_I420;

  /* Quality control */
  x264params.rc.i_rc_method = X264_RC_CRF;
  x264params.rc.f_rf_constant = 23.0;

  /* Enable b-frames */
  x264params.i_bframe = 16;
  x264params.i_bframe_adaptive = 1;

  /* Timebase */
  x264params.i_timebase_num = 1;
  x264params.i_timebase_den = 1000;

  /* Enable variable frame rate */
  x264params.b_vfr_input = 1;

  /* Disable annex-b (start codes) */
  x264params.b_annexb = 0;

  /* TODO: Setup error handler */
  if ( logDebugging() )
    x264params.i_log_level = X264_LOG_DEBUG;
  else
    x264params.i_log_level = X264_LOG_NONE;

  /* Process user parameters (excluding preset, tune and profile) */
  for ( unsigned int i = 0; i < user_params.size(); i++ ) {
    /* Skip preset, tune and profile */
    if (
        (strcmp(user_params[i].pname, "preset") == 0) ||
        (strcmp(user_params[i].pname, "tune") == 0) ||
        (strcmp(user_params[i].pname, "profile") == 0) ) {
      continue;
    }

    /* Pass the name and value to x264 */
    x264ret = x264_param_parse(&x264params, user_params[i].pname, user_params[i].pvalue);

    /* Error checking */
    if ( x264ret != 0 ) {
      if ( x264ret == X264_PARAM_BAD_NAME ) {
        Error("Failed processing x264 user parameter %s=%s : Bad name",
            user_params[i].pname, user_params[i].pvalue);
      } else if ( x264ret == X264_PARAM_BAD_VALUE ) {
        Error("Failed processing x264 user parameter %s=%s : Bad value",
            user_params[i].pname, user_params[i].pvalue);
      } else  {
        Error("Failed processing x264 user parameter %s=%s : Unknown error (%d)",
            user_params[i].pname, user_params[i].pvalue, x264ret);
      }
    }
  }

  return 0;
}

int X264MP4Writer::x264encodeloop(bool bFlush) {
  x264_nal_t* nals;
  int i_nals;
  int frame_size;

  if ( bFlush ) {
    frame_size = x264_encoder_encode(x264enc, &nals, &i_nals, NULL, &x264picout);
  } else {
    frame_size = x264_encoder_encode(x264enc, &nals, &i_nals, &x264picin, &x264picout);
  }

  if ( frame_size > 0 || bFlush ) {
    Debug(1, "x264 Frame: %d PTS: %d DTS: %d Size: %d\n",
        frame_count, x264picout.i_pts, x264picout.i_dts, frame_size);

    /* Handle the previous frame */
    if ( !bFirstFrame ) {
      buffer.clear();

      /* Process the NALs for the previous frame */
      for ( unsigned int i = 0; i < prevnals.size(); i++ ) {
        Debug(9, "Processing NAL: Type %d Size %d",
            prevnals[i].i_type,
            prevnals[i].i_payload);

        switch ( prevnals[i].i_type ) {
          case NAL_PPS:
            /* PPS NAL */
            MP4AddH264PictureParameterSet(mp4h, mp4vtid, prevnals[i].p_payload+4, prevnals[i].i_payload-4);
            break;
          case NAL_SPS:
            /* SPS NAL */
            MP4AddH264SequenceParameterSet(mp4h, mp4vtid, prevnals[i].p_payload+4, prevnals[i].i_payload-4);
            break;
          default:
            /* Anything else, hopefully frames, so copy it into the sample */
            buffer.append(prevnals[i].p_payload, prevnals[i].i_payload);
        }
      }

      /* Calculate frame duration and offset */
      int duration = x264picout.i_dts - prevDTS;
      int offset = prevPTS - prevDTS;

      /* Write the sample */
      if ( !buffer.empty() ) {
        unsigned int bufSize = buffer.size();
        if ( !MP4WriteSample(
              mp4h,
              mp4vtid,
              buffer.extract(bufSize),
              bufSize,
              duration,
              offset,
              prevKeyframe) ) {
          Error("Failed writing sample");
        }
      }

      /* Cleanup */
      prevnals.clear();
      prevpayload.clear();
    }

    /* Got a frame. Copy this new frame into the previous frame */
    if ( frame_size > 0 ) {
      /* Copy the NALs and the payloads */
      for ( int i = 0; i < i_nals; i++ ) {
        prevnals.push_back(nals[i]);
        prevpayload.append(nals[i].p_payload, nals[i].i_payload);
      }

      /* Update the payload pointers */
      /* This is done in a separate loop because the previous loop might reallocate memory when appending,
         making the pointers invalid */
      unsigned int payload_offset = 0;
      for ( unsigned int i = 0; i < prevnals.size(); i++ ) {
        prevnals[i].p_payload = prevpayload.head() + payload_offset;
        payload_offset += nals[i].i_payload;
      }

      /* We need this for the next frame */
      prevPTS = x264picout.i_pts;
      prevDTS = x264picout.i_dts;
      prevKeyframe = x264picout.b_keyframe;

      bFirstFrame = false;
    }

  } else if ( frame_size == 0 ) {
    Debug(1, "x264 encode returned zero. Delayed frames: %d",
        x264_encoder_delayed_frames(x264enc));
  } else {
    Error("x264 encode failed: %d", frame_size);
  }
  return frame_size;
}
#endif  // ZM_VIDEOWRITER_X264MP4

int ParseEncoderParameters(
    const char* str,
    std::vector<EncoderParameter_t>* vec
    ) {
  if ( vec == NULL ) {
    Error("NULL Encoder parameters vector pointer");
    return -1;
  }

  if ( str == NULL ) {
    Error("NULL Encoder parameters string");
    return -2;
  }

  vec->clear();

  if ( str[0] == 0 ) {
    /* Empty */
    return 0;
  }

  std::string line;
  std::stringstream ss(str);
  size_t valueoffset;
  size_t valuelen;
  unsigned int lineno = 0;
  EncoderParameter_t param;

  while ( std::getline(ss, line) ) {
    lineno++;

    /* Remove CR if exists */
    if ( line.length() >= 1 && line[line.length()-1] == '\r' ) {
      line.erase(line.length() - 1);
    }

    /* Skip comments and empty lines */
    if ( line.empty() || line[0] == '#' ) {
      continue;
    }

    valueoffset = line.find('=');
    if ( valueoffset == std::string::npos || valueoffset+1 >= line.length() || valueoffset == 0 ) {
      Warning("Failed parsing encoder parameters line %d: Invalid pair", lineno);
      continue;
    }

    if ( valueoffset > (sizeof(param.pname) - 1 ) ) {
      Warning("Failed parsing encoder parameters line %d: Name too long", lineno);
      continue;
    }

    valuelen = line.length() - (valueoffset+1);

    if ( valuelen > (sizeof(param.pvalue) - 1 ) ) {
      Warning("Failed parsing encoder parameters line %d: Value too long", lineno);
      continue;
    }

    /* Copy and NULL terminate */
    line.copy(param.pname, valueoffset, 0);
    line.copy(param.pvalue, valuelen, valueoffset+1);
    param.pname[valueoffset] = 0;
    param.pvalue[valuelen] = 0;

    /* Push to the vector */
    vec->push_back(param);

    Debug(7, "Parsed encoder parameter: %s = %s", param.pname, param.pvalue);
  }

  Debug(7, "Parsed %d lines", lineno);

  return 0;
}


