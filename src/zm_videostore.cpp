//
// ZoneMinder Video Storage Implementation
// Written by Chris Wiggins
// http://chriswiggins.co.nz
// Modification by Steve Gilvarry
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

#define __STDC_FORMAT_MACROS 1

#include <stdlib.h>
#include <string.h>
#include <inttypes.h>

#include "zm.h"
#include "zm_videostore.h"

extern "C"{
#include "libavutil/time.h"
}

VideoStore::VideoStore(const char *filename_in, const char *format_in,
                       AVStream *input_st, AVStream *inpaud_st,
                       int64_t nStartTime) {
    
	AVDictionary *pmetadata = NULL;
	int dsr;

    //store inputs in variables local to class
    filename = filename_in;
    format = format_in;
    
    keyframeMessage = false;
    keyframeSkipNumber = 0;


    Info("Opening video storage stream %s\n", filename);

    //Init everything we need
    int ret;
    av_register_all();

    avformat_alloc_output_context2(&oc, NULL, NULL, filename);
        
    //Couldn't deduce format from filename, trying from format name
    if (!oc) {
        avformat_alloc_output_context2(&oc, NULL, format, filename);
        if (!oc) {
            Fatal("Could not create video storage stream %s as no output context"
                  " could not be assigned based on filename or format %s",
                    filename, format);
        }
    }

	dsr = av_dict_set(&pmetadata, "title", "Zoneminder Security Recording", 0);
	if (dsr < 0) Warning("%s:%d: title set failed", __FILE__, __LINE__ );

	oc->metadata = pmetadata;
        
    fmt = oc->oformat;
    
	video_st = avformat_new_stream(oc, input_st->codec->codec);
    if (!video_st) {
        Fatal("Unable to create video out stream\n");
    }
    
    ret = avcodec_copy_context(video_st->codec, input_st->codec);
    if (ret < 0) { 
        Fatal("Unable to copy input video context to output video context "
              "%s\n", av_make_error_string(ret).c_str());
        }

    video_st->codec->codec_tag = 0;
    if (oc->oformat->flags & AVFMT_GLOBALHEADER) {
        video_st->codec->flags |= CODEC_FLAG_GLOBAL_HEADER;
    }
    
    if (inpaud_st) {
        audio_st = avformat_new_stream(oc, inpaud_st->codec->codec);
        if (!audio_st) {
                Fatal("Unable to create audio out stream\n");
        }
        ret=avcodec_copy_context(audio_st->codec, inpaud_st->codec);
        if (ret < 0) {
            Fatal("Unable to copy audio context %s\n", av_make_error_string(ret).c_str());
        }   
        audio_st->codec->codec_tag = 0;
        if (oc->oformat->flags & AVFMT_GLOBALHEADER) {
            audio_st->codec->flags |= CODEC_FLAG_GLOBAL_HEADER;
        }
    } else {
        audio_st = NULL;
    }    

    /* open the output file, if needed */
    if (!(fmt->flags & AVFMT_NOFILE)) {
        ret = avio_open2(&oc->pb, filename, AVIO_FLAG_WRITE,NULL,NULL);
        if (ret < 0) {
            Fatal("Could not open output file '%s': %s\n", filename,
                    av_make_error_string(ret).c_str());
        }
    }


    /* Write the stream header, if any. */
    ret = avformat_write_header(oc, NULL);
    if (ret < 0) {
            Fatal("Error occurred when writing output file header: %s\n",
                    av_make_error_string(ret).c_str());
    }
    
	 prevDts = 0;
    startPts = 0;
    startDts = 0;
    filter_in_rescale_delta_last = AV_NOPTS_VALUE;

    startTime=av_gettime()-nStartTime;//oc->start_time;
    Info("VideoStore startTime=%d\n",startTime);
}


VideoStore::~VideoStore(){
    /* Write the trailer before close */
    av_write_trailer(oc);
    
    avcodec_close(video_st->codec);
    if (audio_st) {
        avcodec_close(audio_st->codec);
    }
    
    if (!(fmt->flags & AVFMT_NOFILE)) {
    /* Close the output file. */
        avio_close(oc->pb);
    }
    
    /* free the stream */
    avformat_free_context(oc);
}


void VideoStore::dumpPacket( AVPacket *pkt ){
	char b[10240];

	snprintf(b, sizeof(b), " pts: %" PRId64 ", dts: %" PRId64 ", data: %p, size: %d, sindex: %d, dflags: %04x, s-pos: %" PRId64 ", c-duration: %" PRId64 "\n"
			, pkt->pts
			, pkt->dts
			, pkt->data
			, pkt->size
			, pkt->stream_index
			, pkt->flags
			, pkt->pos
			, pkt->convergence_duration
			);
	Info("%s:%d:DEBUG: %s", __FILE__, __LINE__, b);
}

int VideoStore::writeVideoFramePacket(AVPacket *ipkt, AVStream *input_st){//, AVPacket *lastKeyframePkt){

    int64_t ost_tb_start_time = av_rescale_q(startTime, AV_TIME_BASE_Q, video_st->time_base);
     
    AVPacket opkt, safepkt;
    AVPicture pict;
    
    av_init_packet(&opkt);

    //Scale the PTS of the outgoing packet to be the correct time base
    if (ipkt->pts != AV_NOPTS_VALUE) {
        opkt.pts = av_rescale_q(ipkt->pts-startPts, input_st->time_base, video_st->time_base) - ost_tb_start_time;
	 } else {
        opkt.pts = AV_NOPTS_VALUE;
	 }
    
    //Scale the DTS of the outgoing packet to be the correct time base
    if(ipkt->dts != AV_NOPTS_VALUE) {
        opkt.dts = av_rescale_q(input_st->cur_dts-startDts, AV_TIME_BASE_Q, video_st->time_base);
	 } else {
        opkt.dts = av_rescale_q(ipkt->dts-startDts, input_st->time_base, video_st->time_base);
	 }

    opkt.dts -= ost_tb_start_time;

    opkt.duration = av_rescale_q(ipkt->duration, input_st->time_base, video_st->time_base);
    opkt.flags = ipkt->flags;
    opkt.pos=-1;

    opkt.data = ipkt->data;
    opkt.size = ipkt->size;
    opkt.stream_index = ipkt->stream_index;
    /*opkt.flags |= AV_PKT_FLAG_KEY;*/
    
    if (video_st->codec->codec_type == AVMEDIA_TYPE_VIDEO && (fmt->flags & AVFMT_RAWPICTURE)) {
        /* store AVPicture in AVPacket, as expected by the output format */
        avpicture_fill(&pict, opkt.data, video_st->codec->pix_fmt, video_st->codec->width, video_st->codec->height);
        opkt.data = (uint8_t *)&pict;
        opkt.size = sizeof(AVPicture);
        opkt.flags |= AV_PKT_FLAG_KEY;
    }
	  
	 memcpy(&safepkt, &opkt, sizeof(AVPacket));
    
	 if ((opkt.data == NULL)||(opkt.size < 1)) {
		Warning("%s:%d: Mangled AVPacket: discarding frame", __FILE__, __LINE__ ); 
		 dumpPacket(&opkt);

	 } else if ((prevDts > 0) && (prevDts >= opkt.dts)) {
		Warning("%s:%d: DTS out of order: %lld \u226E %lld; discarding frame", __FILE__, __LINE__, prevDts, opkt.dts); 
		prevDts = opkt.dts; 
		 dumpPacket(&opkt);

	 } else {
		int ret;

		prevDts = opkt.dts; // Unsure if av_interleaved_write_frame() clobbers opkt.dts when out of order, so storing in advance
		ret = av_interleaved_write_frame(oc, &opkt);
		if(ret<0){
			// There's nothing we can really do if the frame is rejected, just drop it and get on with the next
        Warning("%s:%d: Writing frame [av_interleaved_write_frame()] failed: %s(%d)  ", __FILE__, __LINE__,  av_make_error_string(ret).c_str(), (ret));
		  dumpPacket(&safepkt);
		}
	 }


    av_free_packet(&opkt); 

    return 0;
    
}

int VideoStore::writeAudioFramePacket(AVPacket *ipkt, AVStream *input_st){

    if(!audio_st)
        return -1;//FIXME -ve return codes do not free packet in ffmpeg_camera at the moment
    /*if(!keyframeMessage)
        return -1;*/
        
    int64_t ost_tb_start_time = av_rescale_q(startTime, AV_TIME_BASE_Q, video_st->time_base);
        
    AVPacket opkt;
    
    av_init_packet(&opkt);

    
    //Scale the PTS of the outgoing packet to be the correct time base
    if (ipkt->pts != AV_NOPTS_VALUE)
        opkt.pts = av_rescale_q(ipkt->pts-startPts, input_st->time_base, audio_st->time_base) - ost_tb_start_time;
    else
        opkt.pts = AV_NOPTS_VALUE;
    
    //Scale the DTS of the outgoing packet to be the correct time base
    if(ipkt->dts == AV_NOPTS_VALUE)
        opkt.dts = av_rescale_q(input_st->cur_dts-startDts, AV_TIME_BASE_Q, audio_st->time_base);
    else
        opkt.dts = av_rescale_q(ipkt->dts-startDts, input_st->time_base, audio_st->time_base);
    opkt.dts -= ost_tb_start_time;
    
    if (audio_st->codec->codec_type == AVMEDIA_TYPE_AUDIO && ipkt->dts != AV_NOPTS_VALUE) {
         int duration = av_get_audio_frame_duration(input_st->codec, ipkt->size);
         if(!duration)
             duration = input_st->codec->frame_size;
             
        //FIXME where to get filter_in_rescale_delta_last
		//FIXME av_rescale_delta doesn't exist in ubuntu vivid libavtools
        opkt.dts = opkt.pts = av_rescale_delta(input_st->time_base, ipkt->dts,
                                                (AVRational){1, input_st->codec->sample_rate}, duration, &filter_in_rescale_delta_last,
                                                audio_st->time_base) - ost_tb_start_time;
    }
    
    opkt.duration = av_rescale_q(ipkt->duration, input_st->time_base, audio_st->time_base);
    opkt.pos=-1;
    opkt.flags = ipkt->flags;
    
    opkt.data = ipkt->data;
    opkt.size = ipkt->size;
    opkt.stream_index = ipkt->stream_index;
    /*opkt.flags |= AV_PKT_FLAG_KEY;*/
        
    int ret;
    ret = av_interleaved_write_frame(oc, &opkt);
    if(ret!=0){
        Fatal("Error encoding audio frame packet: %s\n", av_make_error_string(ret).c_str());
    }
    
    av_free_packet(&opkt);
    return 0;
}
