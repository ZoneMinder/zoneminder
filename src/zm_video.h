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

#ifndef ZM_VIDEO_H
#define ZM_VIDEO_H

#include "zm.h"
#include "zm_rgb.h"
#include "zm_utils.h"
#include "zm_ffmpeg.h"
#include "zm_buffer.h"
#include "zm_swscale.h"

/*
#define HAVE_LIBX264 1
#define HAVE_LIBMP4V2 1
#define HAVE_X264_H 1
#define HAVE_MP4_H 1
*/

#if HAVE_MP4V2_MP4V2_H
#include <mp4v2/mp4v2.h>
#endif
#if HAVE_MP4V2_H
#include <mp4v2.h>
#endif
#if HAVE_MP4_H
#include <mp4.h>
#endif

#if HAVE_X264_H
#ifdef __cplusplus
extern "C" {
#endif
#include <x264.h>
#ifdef __cplusplus
}
#endif
#endif

/* Structure for user parameters to the encoder */
struct EncoderParameter_t {
	char pname[48];
	char pvalue[48];

};
int ParseEncoderParameters(const char* str, std::vector<EncoderParameter_t>* vec);

/* VideoWriter is a generic interface that ZM uses to save events as videos */
/* It is relatively simple and the functions are pure virtual, so they must be implemented by the deriving class */

class VideoWriter {

protected:
	std::string container;
	std::string codec;
	std::string path;
	unsigned int width;
	unsigned int height;
	unsigned int colours;
	unsigned int subpixelorder;
	
	unsigned int frame_count;

public:
	VideoWriter(const char* p_container, const char* p_codec, const char* p_path, const unsigned int p_width, const unsigned int p_height, const unsigned int p_colours, const unsigned int p_subpixelorder);
	virtual ~VideoWriter();
	virtual int Encode(const uint8_t* data, const size_t data_size, const unsigned int frame_time) = 0;
	virtual int Encode(const Image* img, const unsigned int frame_time) = 0;
	virtual int Open() = 0;
	virtual int Close() = 0;
	virtual int Reset(const char* new_path = NULL);
	
	const char* GetContainer() const {
		return container.c_str();
	}
	const char* GetCodec() const {
		return codec.c_str();
	}
	const char* GetPath() const {
		return path.c_str();
	}
	unsigned int GetWidth() const {
		return width;
	}
	unsigned int GetHeight() const {
		return height;
	}
	unsigned int GetColours() const {
		return colours;
	}
	unsigned int GetSubpixelorder () const {
		return subpixelorder;
	}
	unsigned int GetFrameCount() const {
		return frame_count;
	}
};

#if HAVE_LIBX264 && HAVE_LIBMP4V2 && HAVE_LIBAVUTIL && HAVE_LIBSWSCALE
#define ZM_HAVE_VIDEOWRITER_X264MP4 1
class X264MP4Writer : public VideoWriter {

protected:
	
	bool bOpen;
	bool bGotH264AVCInfo;
	bool bFirstFrame;
	
	/* SWScale */
	SWScale swscaleobj;
	enum _AVPIXELFORMAT zm_pf;
	enum _AVPIXELFORMAT codec_pf;
	size_t codec_imgsize;
	size_t zm_imgsize;

	/* User parameters */
	std::vector<EncoderParameter_t> user_params;
	
	/* AVC Information */
	uint8_t x264_profleindication;
	uint8_t x264_profilecompat;
	uint8_t x264_levelindication;

	/* NALs */
	Buffer buffer;

	/* Previous frame */
	int prevPTS;
	int prevDTS;
	bool prevKeyframe;
	Buffer prevpayload;
	std::vector<x264_nal_t> prevnals;

	/* Internal functions */
	int x264config();
	int x264encodeloop(bool bFlush = false);

	/* x264 objects */
	x264_t* x264enc;
	x264_param_t x264params;
	x264_picture_t x264picin;
	x264_picture_t x264picout;
	
	/* MP4v2 objects */
	MP4FileHandle mp4h;
	MP4TrackId mp4vtid;


public:
	X264MP4Writer(const char* p_path, const unsigned int p_width, const unsigned int p_height, const unsigned int p_colours, const unsigned int p_subpixelorder, const std::vector<EncoderParameter_t>* p_user_params = NULL);
	~X264MP4Writer();
	int Encode(const uint8_t* data, const size_t data_size, const unsigned int frame_time);
	int Encode(const Image* img, const unsigned int frame_time);
	int Open();
	int Close();
	int Reset(const char* new_path = NULL);
	
};
#endif // HAVE_LIBX264 && HAVE_LIBMP4V2 && HAVE_LIBAVUTIL && HAVE_LIBSWSCALE

#endif // ZM_VIDEO_H
