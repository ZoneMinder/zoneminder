#ifndef ZM_AVFILTER_WORKER_H
#define ZM_AVFILTER_WORKER_H

#include <string>

extern "C" {
#include <libavcodec/avcodec.h>
#include <libavformat/avformat.h>

#include <libavfilter/buffersink.h>
#include <libavfilter/buffersrc.h>

#include <libavutil/frame.h>
#include <libavutil/opt.h>

#include <libswscale/swscale.h>
}

class filter_worker {
  public:
    AVFilterContext *buffersink_ctx;
    AVFilterContext *buffersrc_ctx;
    AVFilterGraph *filter_graph;
    AVFilterContext *filter_ctx;
    AVCodecContext *dec_ctx;
    AVRational time_base;
    bool initialised;

    filter_worker();
    ~filter_worker();
    bool setup(const std::string &filter_desc, const std::string &filter_of_interest, AVCodecContext *ctx, AVRational tbase, AVBufferRef *hw_frames_ctx, AVPixelFormat pix_fmt);
    int execute(AVFrame *in_frame, AVFrame **out_frame);

    int opt_set(const std::string &opt, const std::string &value);
    int opt_set(const std::string &opt, int value);

    int send_command(const char *filter_name, const char *command, const char *option);
    int init_filter(const char *filters_desc, AVBufferRef * 	hw_frames_ctx, AVPixelFormat in_ipxfmt);
};

#endif
