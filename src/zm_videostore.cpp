//
// ZoneMinder Video Storage Implementation
// Written by Chris Wiggins
// http://chriswiggins.co.nz
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
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//

#include <stdlib.h>
#include <string.h>

#include "zm.h"
#include "zm_videostore.h"

#if LIBAVCODEC_VERSION_MAJOR < 54

#define avformat_alloc_output_context2(x,y,z,a) hacked_up_context2_for_older_ffmpeg(x,y,z,a)
#define av_err2str(x) ""

int hacked_up_context2_for_older_ffmpeg(AVFormatContext **avctx, AVOutputFormat *oformat,
                                   const char *format, const char *filename)
{
    AVFormatContext *s = avformat_alloc_context();
    int ret = 0;

    *avctx = NULL;
    if (!s)
    {
	av_log(s, AV_LOG_ERROR, "Out of memory\n");
	ret = AVERROR(ENOMEM);
	return ret;
    }

    if (!oformat) {
        if (format) {
            oformat = av_guess_format(format, NULL, NULL);
            if (!oformat) {
                av_log(s, AV_LOG_ERROR, "Requested output format '%s' is not a suitable output format\n", format);
                ret = AVERROR(EINVAL);
            }
        } else {
            oformat = av_guess_format(NULL, filename, NULL);
            if (!oformat) {
                ret = AVERROR(EINVAL);
                av_log(s, AV_LOG_ERROR, "Unable to find a suitable output format for '%s'\n",
                       filename);
            }
        }
    }

    if (ret)
    {
	avformat_free_context(s);
	return ret;
    } else
    {
	s->oformat = oformat;
	if (s->oformat->priv_data_size > 0) {
	    s->priv_data = av_mallocz(s->oformat->priv_data_size);
        if (s->priv_data)
        {
	    if (s->oformat->priv_class) {
		*(const AVClass**)s->priv_data= s->oformat->priv_class;
		av_opt_set_defaults(s->priv_data);
	    }
	} else
	{
	    av_log(s, AV_LOG_ERROR, "Out of memory\n");
	    ret = AVERROR(ENOMEM);
	   return ret;
	}
        s->priv_data = NULL;
    }


    if (filename)
        strncpy(s->filename, filename, sizeof(s->filename));
    *avctx = s;
    return 0;
   }
}
#endif

VideoStore::VideoStore(const char *filename_in, const char *format_in, AVStream *input_st){
    
    //store inputs in variables local to class
	filename = filename_in;
	format = format_in;
    
    keyframeMessage = false;
    keyframeSkipNumber = 0;


	Info("Opening video storage stream %s\n", filename);
    
	//Init everything we need
	int ret;
	av_register_all();
    
	//Allocate the output media context based on the filename of the context
	avformat_alloc_output_context2(&oc, NULL, NULL, filename);
    
	//Couldn't deduce format from filename, trying from format name
	if(!oc){
		avformat_alloc_output_context2(&oc, NULL, format, filename);
	}
    
	//Couldn't deduce format from filename, using MPEG
	if(!oc){
		Error("Couldn't deduce format from filename, using MPEG");
		avformat_alloc_output_context2(&oc, NULL, format, filename);
	}
    
	if(!oc){
		Fatal("No output context was assigned...");
	}
    
	fmt = oc->oformat;
    
    //create a new video stream based on the incoming stream from the camera and copy the context across
    video_st = avformat_new_stream(oc, input_st->codec->codec);
    avcodec_copy_context(video_st->codec, input_st->codec);
    
	av_dump_format(oc, 0, filename, 1);
    
	/* open the output file, if needed */
    if (!(fmt->flags & AVFMT_NOFILE)) {
        ret = avio_open(&oc->pb, filename, AVIO_FLAG_WRITE);
        if (ret < 0) {
            Fatal("Could not open '%s': %s\n", filename, av_err2str(ret));
        }
    }
    
	/* Write the stream header, if any. */
	ret = avformat_write_header(oc, NULL);
	if (ret < 0) {
		Fatal("Error occurred when opening output file: %s\n", av_err2str(ret));
	}
    
    startPts = 0;
    startDts = 0;
    
}

VideoStore::~VideoStore(){
    /* Write the trailer, if any. The trailer must be written before you
     * close the CodecContexts open when you wrote the header; otherwise
     * av_write_trailer() may try to use memory that was freed on
     * av_codec_close(). */
    av_write_trailer(oc);
    
    avcodec_close(video_st->codec);
    
    if (!(fmt->flags & AVFMT_NOFILE))
    /* Close the output file. */
        avio_close(oc->pb);
    
    /* free the stream */
    avformat_free_context(oc);
}



int VideoStore::writeVideoFramePacket(AVPacket *ipkt, AVStream *input_st, AVPacket *lastKeyframePkt){
    /*
     See 01349 of http://www.ffmpeg.org/doxygen/trunk/ffmpeg_8c-source.html
     do_streamcopy
     */
    AVPacket opkt;
    AVPicture pict;//Not sure how much we need this
    
    av_init_packet(&opkt);
    
    //Wait for a keyframe to show up or use
    if (!video_st->nb_frames && !(ipkt->flags & AV_PKT_FLAG_KEY)){
        if(!keyframeMessage && (lastKeyframePkt->flags & AV_PKT_FLAG_KEY)){
            int64_t tmpPts = ipkt->pts;
            int64_t tmpDts = ipkt->dts;
            av_copy_packet(ipkt, lastKeyframePkt);
            ipkt->pts = tmpPts;
            ipkt->dts = tmpDts;
            Info("Used buffered keyframe as first frame");
            
            if (ipkt->pts != AV_NOPTS_VALUE)
                startPts = ipkt->pts;
            else
                startPts = 0;
            
            if (ipkt->dts != AV_NOPTS_VALUE)
                startDts = ipkt->pts;
            else
                startDts = 0;
            
        
        } else if(!keyframeMessage){
            Warning("Waiting for keyframe before starting recording");
            keyframeMessage = true;
        }
        
        if(keyframeMessage){
            keyframeSkipNumber++;
            return -1;
        }

    }else{
        if(keyframeMessage){
            Warning("Skipped %d frames waiting for keyframe", keyframeSkipNumber);
            keyframeMessage = false;
            
            if (ipkt->pts != AV_NOPTS_VALUE)
                startPts = ipkt->pts;
            else
                startPts = 0;
            
            if (ipkt->dts != AV_NOPTS_VALUE)
                startDts = ipkt->pts;
            else
                startDts = 0;
        }
    }

    
    opkt.stream_index = video_st->index;
    
    //Scale the PTS of the outgoing packet to be the correct time base
    if (ipkt->pts != AV_NOPTS_VALUE)
        opkt.pts = av_rescale_q((ipkt->pts)-startPts, input_st->time_base, video_st->time_base);
    else
        opkt.pts = AV_NOPTS_VALUE;
    
    //Scale the DTS of the outgoing packet to be the correct time base
    if(ipkt->dts == AV_NOPTS_VALUE)
        opkt.dts = av_rescale_q((input_st->reference_dts)-startDts, AV_TIME_BASE_Q, video_st->time_base);
    else
        opkt.dts = av_rescale_q((ipkt->dts)-startDts, input_st->time_base, video_st->time_base);
    //opkt.dts -= ost_tb_start_time;
    
    opkt.duration = av_rescale_q(ipkt->duration, input_st->time_base, video_st->time_base);
    opkt.flags = ipkt->flags;
    
    //TODO: Should be checking if not H264, mpeg1, etc
    //Maybe the check isn't needed if we're only going to do this for H264 video incoming
    
    opkt.data = ipkt->data;
    opkt.size = ipkt->size;
    
    if (video_st->codec->codec_type == AVMEDIA_TYPE_VIDEO && (fmt->flags & AVFMT_RAWPICTURE)) {
        /* store AVPicture in AVPacket, as expected by the output format */
        //Info("Raw picture storage");
        avpicture_fill(&pict, opkt.data, video_st->codec->pix_fmt, video_st->codec->width, video_st->codec->height);
        opkt.data = (uint8_t *)&pict;
        opkt.size = sizeof(AVPicture);
        opkt.flags |= AV_PKT_FLAG_KEY;
    }
    
    
    int ret;
    ret = av_interleaved_write_frame(oc, &opkt);
    if(ret<0){
        Fatal("Error encoding video frame packet: %s\n", av_err2str(ret));
    }
    
    av_free_packet(&opkt);
    return 0;
    
}



