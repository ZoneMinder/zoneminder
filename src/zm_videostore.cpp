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

extern "C"{
#include "libavutil/time.h"
}

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

VideoStore::VideoStore(const char *filename_in, const char *format_in, AVStream *input_st, AVStream *inpaud_st, int64_t nStartTime){
    
    //see http://stackoverflow.com/questions/17592120/ffmpeg-how-to-copy-codec-video-and-audio-from-mp4-container-to-ts-cont
    //see https://www.ffmpeg.org/doxygen/trunk/remuxing_8c-example.html#a41
    //store inputs in variables local to class
	filename = filename_in;//FIXME hmm
	format = format_in;//FIXME hmm
    
    keyframeMessage = false;
    keyframeSkipNumber = 0;
    char szErr[1024];

	Info("Opening video storage stream %s\n", filename);
    
	//Init everything we need
	int ret;
	av_register_all();
	
	//AVOutputFormat *outfmt = av_guess_format(NULL,filename,NULL);
    
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
    
   /* AVCodec *out_vid_codec,*out_aud_codec;
    out_vid_codec = out_aud_codec = NULL;
    //create a new video stream based on the incoming stream from the camera and copy the context across
    if(outfmt){//FIXME what if we failed
        out_vid_codec = avcodec_find_encoder(outfmt->video_codec);//what exactly are we doing here all we have is something based on the filename which if it is a container doesnt imply a codec?
        out_aud_codec = avcodec_find_encoder(outfmt->audio_codec);//what exactly are we doing here all we have is something based on the filename which if it is a container doesnt imply a codec?
    } else
        Fatal("Unable to guess output format\n");*/
    
    video_st = avformat_new_stream(oc, /*out_vid_codec?out_vid_codec:*/input_st->codec->codec);
    if(video_st){ //FIXME handle failures
        ret=avcodec_copy_context(video_st->codec, input_st->codec);
        if(ret==0){
            /*int m_fps=25;//FIXME doesn't say where to get this from?
            video_st->sample_aspect_ratio.den = input_st->codec->sample_aspect_ratio.den;
            video_st->sample_aspect_ratio.num = input_st->codec->sample_aspect_ratio.num;
            video_st->codec->codec_id = input_st->codec->codec_id;
            video_st->codec->time_base.num = 1;
            video_st->codec->time_base.den = m_fps*(input_st->codec->ticks_per_frame);         
            video_st->time_base.num = 1;
            video_st->time_base.den = 1000;
            video_st->r_frame_rate.num = m_fps;
            video_st->r_frame_rate.den = 1;
            video_st->avg_frame_rate.den = 1;
            video_st->avg_frame_rate.num = m_fps;
            //video_st->duration = (m_out_end_time - m_out_start_time)*1000;//FIXME what the hell do i put here*/
        } else 
            Fatal("Unable to copy video context %s\n", av_make_error_string(szErr,1024,ret));
        video_st->codec->codec_tag = 0;
        if (oc->oformat->flags & AVFMT_GLOBALHEADER)
            video_st->codec->flags |= CODEC_FLAG_GLOBAL_HEADER;
    } else
        Fatal("Unable to create video out stream\n");
    
    if(inpaud_st){
        audio_st = avformat_new_stream(oc, /*out_aud_codec?out_aud_codec:*/inpaud_st->codec->codec);
        if(audio_st){//FIXME failure?
            ret=avcodec_copy_context(audio_st->codec, inpaud_st->codec);
            if(ret==0){ //FIXME failure?
              /*  audio_st->codec->codec_id = inpaud_st->codec->codec_id;
                audio_st->codec->codec_tag = 0;
                audio_st->pts = inpaud_st->pts;
                audio_st->duration = inpaud_st->duration;
                audio_st->time_base.num = inpaud_st->time_base.num;
                audio_st->time_base.den = inpaud_st->time_base.den;*/
            } else
                Fatal("Unable to copy audio context %s\n", av_make_error_string(szErr,1024,ret));
            audio_st->codec->codec_tag = 0;
            if (oc->oformat->flags & AVFMT_GLOBALHEADER)
                audio_st->codec->flags |= CODEC_FLAG_GLOBAL_HEADER;
        } else
            Fatal("Unable to create audio out stream\n");
    }else{
        audio_st = NULL;
    }    
    
	//av_dump_format(oc, 0, filename, 1);
    
	/* open the output file, if needed */
    if (!(fmt->flags & AVFMT_NOFILE)) {
        ret = avio_open2(&oc->pb, filename, AVIO_FLAG_WRITE,NULL,NULL);
        if (ret < 0) {
            Fatal("Could not open '%s': %s\n", filename, av_make_error_string(szErr,1024,ret));
        }
    }
    
	/* Write the stream header, if any. */
	ret = avformat_write_header(oc, NULL);
	if (ret < 0) {
		Fatal("Error occurred when opening output file: %s\n", av_make_error_string(szErr,1024,ret));
	}
    
    startPts = 0;
    startDts = 0;
    filter_in_rescale_delta_last = AV_NOPTS_VALUE;

    startTime=av_gettime()-nStartTime;//oc->start_time;
    Info("VideoStore startTime=%d\n",startTime);
}

VideoStore::~VideoStore(){
    /* Write the trailer, if any. The trailer must be written before you
     * close the CodecContexts open when you wrote the header; otherwise
     * av_write_trailer() may try to use memory that was freed on
     * av_codec_close(). */
    av_write_trailer(oc);
    
    avcodec_close(video_st->codec);
    if (audio_st)
        avcodec_close(audio_st->codec);
    
    if (!(fmt->flags & AVFMT_NOFILE))
    /* Close the output file. */
        avio_close(oc->pb);
    
    /* free the stream */
    avformat_free_context(oc);
}

int VideoStore::writeVideoFramePacket(AVPacket *ipkt, AVStream *input_st){//, AVPacket *lastKeyframePkt){
    /*
     See 01349 of http://www.ffmpeg.org/doxygen/trunk/ffmpeg_8c-source.html
     do_streamcopy
     */
          
  /*  if(!keyframeMessage) //video should only be split on key frame now
    {
        if(!video_st->nb_frames && !(ipkt->flags & AV_PKT_FLAG_KEY))//FIXME relavence ... video_st->nb_frames 
            return -1;
        keyframeMessage=true;
        if (ipkt->pts != AV_NOPTS_VALUE)
            startPts = ipkt->pts;
        else
            startPts = 0;
        
        if (ipkt->dts != AV_NOPTS_VALUE)
            startDts = ipkt->dts;
        else
            startDts = 0;
    }*/
     
    char szErr[1024];
    int64_t ost_tb_start_time = av_rescale_q(startTime, AV_TIME_BASE_Q, video_st->time_base);
     
    AVPacket opkt;
    AVPicture pict;//Not sure how much we need this
    
    av_init_packet(&opkt);
    
    //Wait for a keyframe to show up or use
  /*  if (!video_st->nb_frames && !(ipkt->flags & AV_PKT_FLAG_KEY)){
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
            return -1;//FIXME bad opkt not freed
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
    }*/

    
    //opkt.stream_index = video_st->index;
    
    //Scale the PTS of the outgoing packet to be the correct time base
    if (ipkt->pts != AV_NOPTS_VALUE)
        opkt.pts = av_rescale_q(ipkt->pts-startPts, input_st->time_base, video_st->time_base) - ost_tb_start_time;
    else
        opkt.pts = AV_NOPTS_VALUE;
    
    //Scale the DTS of the outgoing packet to be the correct time base
    if(ipkt->dts == AV_NOPTS_VALUE)
        opkt.dts = av_rescale_q(input_st->cur_dts-startDts, AV_TIME_BASE_Q, video_st->time_base);
    else
        opkt.dts = av_rescale_q(ipkt->dts-startDts, input_st->time_base, video_st->time_base);
    opkt.dts -= ost_tb_start_time;
    
    opkt.duration = av_rescale_q(ipkt->duration, input_st->time_base, video_st->time_base);
    opkt.flags = ipkt->flags;
    opkt.pos=-1;
    
    //TODO: Should be checking if not H264, mpeg1, etc
    //Maybe the check isn't needed if we're only going to do this for H264 video incoming
    
    opkt.data = ipkt->data;
    opkt.size = ipkt->size;
    opkt.stream_index = ipkt->stream_index;
    /*opkt.flags |= AV_PKT_FLAG_KEY;*/
    
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
        Fatal("Error encoding video frame packet: %s\n", av_make_error_string(szErr,1024,ret));
    }
    
    av_free_packet(&opkt);
    return 0;
    
}

int VideoStore::writeAudioFramePacket(AVPacket *ipkt, AVStream *input_st){
    /*
     See 01349 of http://www.ffmpeg.org/doxygen/trunk/ffmpeg_8c-source.html
     do_streamcopy
     */
    if(!audio_st)
        return -1;//FIXME -ve return codes do not free packet in ffmpeg_camera at the moment
    /*if(!keyframeMessage)
        return -1;*/
        
    char szErr[1024];
    int64_t ost_tb_start_time = av_rescale_q(startTime, AV_TIME_BASE_Q, video_st->time_base);
        
    AVPacket opkt;
    
    av_init_packet(&opkt);
    
    //opkt.stream_index = audio_st->index;
    
    //FIXME does the PTS/DTS code below still apply to our audio the same way?
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
        opkt.dts = opkt.pts = av_rescale_delta(input_st->time_base, ipkt->dts,
                                                (AVRational){1, input_st->codec->sample_rate}, duration, &filter_in_rescale_delta_last,
                                                audio_st->time_base) - ost_tb_start_time;
    }
    
    opkt.duration = av_rescale_q(ipkt->duration, input_st->time_base, audio_st->time_base);
    opkt.pos=-1;
    opkt.flags = ipkt->flags;
    
    //TODO: Should be checking if not H264, mpeg1, etc
    //Maybe the check isn't needed if we're only going to do this for H264 video incoming
    
    opkt.data = ipkt->data;
    opkt.size = ipkt->size;
    opkt.stream_index = ipkt->stream_index;
    /*opkt.flags |= AV_PKT_FLAG_KEY;*/
        
    int ret;
    ret = av_interleaved_write_frame(oc, &opkt);
    if(ret<0){
        Fatal("Error encoding audio frame packet: %s\n", av_make_error_string(szErr,1024,ret));
    }
    
    av_free_packet(&opkt);
    return 0;
}


/*

// Duration of one frame in AV_TIME_BASE units
int64_t timeBase;

void open(const char* fpath){
    ...
    timeBase = (int64_t(pCodecCtx->time_base.num) * AV_TIME_BASE) / int64_t(pCodecCtx->time_base.den);
    ...
}

bool seek(int frameIndex){

    if(!pFormatCtx)
        return false;

    int64_t seekTarget = int64_t(frameIndex) * timeBase;

    if(av_seek_frame(pFormatCtx, -1, seekTarget, AVSEEK_FLAG_ANY) < 0)
        mexErrMsgTxt("av_seek_frame failed.");
}

void saveFrame(const AVFrame* frame, int width, int height, int frameNumber)
{
    char filename[32];
    sprintf(filename, "frame%d.ppm", frameNumber);
    std::ofstream file(filename, std::ios_base::binary | std::ios_base::trunc | std::ios_base::out);

    if (!file.good())
    {
        throw std::runtime_error("Unable to open the file to write the frame");
    }

    file << "P5\n" << width << '\n' << height << "\n255\n";

    for (int i = 0; i < height; ++i)
    {
        file.write((char*)(frame->data[0] + i * frame->linesize[0]), width);
    }
}

int main()
{
    av_register_all();
    AVFrame* frame = avcodec_alloc_frame();
    if (!frame)
    {
        return 1;
    }

    AVFormatContext* formatContext = NULL;
    if (avformat_open_input(&formatContext, "mpeg2.mov", NULL, NULL) != 0)
    {
        av_free(frame);
        return 1;
    }

    if (avformat_find_stream_info(formatContext, NULL) < 0)
    {
        av_free(frame);
        av_close_input_file(formatContext);
        return 1;
    }

    if (formatContext->nb_streams < 1 || formatContext->streams[0]->codec->codec_type != AVMEDIA_TYPE_VIDEO)
    {
        av_free(frame);
        av_close_input_file(formatContext);
        return 1;
    }

    AVStream* stream = formatContext->streams[0];
    AVCodecContext* codecContext = stream->codec;

    codecContext->codec = avcodec_find_decoder(codecContext->codec_id);
    if (codecContext->codec == NULL)
    {
        av_free(frame);
        avcodec_close(codecContext);
        av_close_input_file(formatContext);
        return 1;
    }
    else if (avcodec_open2(codecContext, codecContext->codec, NULL) != 0)
    {
        av_free(frame);
        avcodec_close(codecContext);
        av_close_input_file(formatContext);
        return 1;
    }

    avcodec_flush_buffers(codecContext);
    
    std::cout << "Seek successful? " << (av_seek_frame(formatContext, -1, 0, AVSEEK_FLAG_BACKWARD) >= 0) << std::endl;

    AVPacket packet;
    av_init_packet(&packet);

    std::ofstream stats("stats.txt");

    int frameNumber = 0;
    while (av_read_frame(formatContext, &packet) == 0)
    {
        std::cout << "key packet? " << (packet.flags & AV_PKT_FLAG_KEY) << std::endl;
        if (packet.stream_index == stream->index)
        {
            int frameFinished = 0;
            avcodec_decode_video2(codecContext, frame, &frameFinished, &packet);

            if (frameFinished)
            {
                saveFrame(frame, codecContext->width, codecContext->height, frameNumber++);
                stats << "repeat: " << frame->repeat_pict << "\tkeyframe: " << frame->key_frame << "\tbest_ts: " << frame->best_effort_timestamp << '\n';
            }
        }
    }
    
    av_free_packet(&packet);

    if (codecContext->codec->capabilities & CODEC_CAP_DELAY)
    {
        av_init_packet(&packet);
        int frameFinished = 0;
        int result = 0;
        while ((result = avcodec_decode_video2(codecContext, frame, &frameFinished, &packet)) >= 0 && frameFinished)
        {
            if (frameFinished)
            {
                saveFrame(frame, codecContext->width, codecContext->height, frameNumber++);
                stats << "repeat: " << frame->repeat_pict << "\tkeyframe: " << frame->key_frame << "\tbest_ts: " << frame->best_effort_timestamp << '\n';
            }
        }
    }

    av_free(frame);
    avcodec_close(codecContext);
    av_close_input_file(formatContext);
}*/
