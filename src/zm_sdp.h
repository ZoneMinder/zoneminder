#if 0
#ifndef ZM_SDP_H
#define ZM_SDP_H

#if HAVE_LIBAVFORMAT_AVFORMAT_H
#include <libavformat/avformat.h>
#elif HAVE_FFMPEG_AVFORMAT_H
#include <ffmpeg/avformat.h>
#else
#error "No location for avformat.h found"
#endif

//
// Part of libavformat/rtp.h
//

//
// Part of libavformat/rtp_internal.h
//

//
// Part of libavformat/rtp.h
//

//
// Part of libavformat/rtsp.c
//

//
// Declaration from libavformat/rtsp.c
//
void av_register_rtp_dynamic_payload_handlers(void);

int sdp_parse(AVFormatContext *s, const char *content);

#endif // ZM_SDP_H
#endif
