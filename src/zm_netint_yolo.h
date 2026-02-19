#ifndef ZM_QUADRA_YOLO_H
#define ZM_QUADRA_YOLO_H

#include "zm_signal.h"
#include "zm_ffmpeg.h"
#include "zm_avfilter_worker.h"
#include "zm_object_classes.h"

extern "C" {
#include <libavcodec/avcodec.h>
#include <libavutil/frame.h>
#include <libswscale/swscale.h>
}

#include "yolo_model.h"
#include "netint_network.h"

#include <nlohmann/json.hpp>
#include <string>
#include <vector>

#define NI_TRANSCODE_FRAME_NUM 3
#define NI_SAME_CENTER_THRESH 2
#define NI_SAME_BORDER_THRESH 8

class Monitor;

class Quadra_Yolo {
  private:
    Monitor *monitor;
    int model_width;
    int model_height;
    int model_format;
    bool model_bgr;  // true if model expects BGR channel order, false for RGB
    ObjectClasses object_classes_;  // Class labels (defaults to COCO, can load from .names file)
    float obj_thresh = 0.25;
    float nms_thresh = 0.45;
    NiNetworkContext *network_ctx;
    YoloModel *model;
    YoloModelCtx *model_ctx;
    NiNetworkFrame net_frame;
    //ni_session_data_io_t *ai_frame;

    av_frame_ptr scaled_frame;
    //SWScale swscale;
    SwsContext *sw_scale_ctx;

    // Letterbox parameters for aspect ratio preservation
    int letterbox_offset_x = 0;  // X offset of scaled image within model frame
    int letterbox_offset_y = 0;  // Y offset of scaled image within model frame
    int letterbox_width = 0;     // Width of scaled image (without padding)
    int letterbox_height = 0;    // Height of scaled image (without padding)
    float letterbox_scale = 1.0f; // Scale factor applied to original image

    filter_worker hwdl_filter;

    bool drawbox;
    filter_worker drawbox_filter;

    bool drawtext;
    filter_worker drawtext_filter;
    // Needed for format conversion
    filter_worker scale_to_rgba_filter;
    filter_worker scale_to_yuv420p_filter;

    AVStream *dec_stream;
    AVCodecContext *dec_ctx;

    int aiframe_number;
    AVRegionOfInterest *last_roi;
    AVRegionOfInterestNetintExtra *last_roi_extra;
    int last_roi_count;

    bool use_hwframe;
    nlohmann::json detections;

    int filt_cnt;
  public:
    Quadra_Yolo(Monitor *p_monitor, bool p_use_hwframe);
    ~Quadra_Yolo();
    bool setup(AVStream *p_dec_stream, AVCodecContext *decoder_ctx, const std::string &model_name="", const std::string &nbg_file="", int deviceid=-1);
    bool setup_drawbox();
    bool setup_drawtext();
    int send_packet(std::shared_ptr<ZMPacket> in_packet);
    int receive_detection(std::shared_ptr<ZMPacket> out_packet);
    int detect(std::shared_ptr<ZMPacket>in_packet, std::shared_ptr<ZMPacket> out_packet);
    int draw_last_roi(std::shared_ptr<ZMPacket> packet);
    int draw_text(AVFrame *input, AVFrame **output, const std::string &text, int x, int y, const std::string &colour);
  private:
    int annotate(AVFrame *input, AVFrame **output, const AVRegionOfInterest &roi, const AVRegionOfInterestNetintExtra &roi_extra, Rgb box_color = 0);
    int draw_roi_box(AVFrame *inframe, AVFrame **outframe, AVRegionOfInterest roi, AVRegionOfInterestNetintExtra roi_extra, int line_width, Rgb box_color = 0);
    int draw_roi_box_in_place(AVFrame *inframe, AVRegionOfInterest roi, AVRegionOfInterestNetintExtra roi_extra, int line_width, Rgb box_color = 0);
    int ni_recreate_ai_frame(ni_frame_t *ni_frame, AVFrame *frame);
    int generate_ai_frame(ni_session_data_io_t *ai_frame, AVFrame *avframe, bool hwframe);
    int process_roi(AVFrame *frame, AVFrame **filt_frame);
    int check_movement( AVRegionOfInterest cur_roi, AVRegionOfInterestNetintExtra cur_roi_extra);
    int ni_read_roi(AVFrame *out, int frame_count);
    bool parse_model_file(const std::string &nbg_file);
};

#endif
