#include "config.h"

#if HAVE_QUADRA
#include <stdio.h>
#include <stdlib.h>
#include <math.h>
#include "nierrno.h"
#include "yolo_postprocess.h"
#include "yolo_model.h"
#include "ni_yolo_utils.h"
#include "zm_logger.h"

static int ni_yolov8_get_boxes(YoloModelCtx *ctx, uint32_t img_width,
        uint32_t img_height, struct roi_box **roi_box, int *roi_num)
{
    int ret;
    int dets_num = 0;
    int real_num = 0;

    detection *dets;
    struct roi_box *rbox;

    *roi_box = nullptr;
    *roi_num = 0;

    ret = ni_get_yolov8_detections(ctx);
    if (ret < 0) {
        Error("cannot get detection");
        return ret;
    } else if (ret == 0) {
        return ret;
    }
    dets_num = ret;
    dets = ctx->det_cache.dets;

    for (int i = 0; i < dets_num; i++) {
        if (dets[i].max_prob != 0) {
            real_num++;
        }
    }
    if (real_num == 0) {
        return 0;
    }

    rbox = static_cast<struct roi_box *>(malloc(sizeof(struct roi_box) * real_num));
    if (!rbox) {
        Error("cannot allocate roi box");
        return NIERROR(ENOMEM);
    }

    if (1) { //tiling mode
        float gain_x = (ctx->input_width  / (float)img_width);
        float gain_y = (ctx->input_height / (float)img_height);

        for (int i = 0; i < dets_num; i++) {
            if (dets[i].max_prob != 0) {
                struct roi_box *pbox = &rbox[(*roi_num)++];
                ni_resize_coords_tiling_mode(&dets[i], pbox, img_width, img_height,
                        gain_x, gain_y);
            }
        }
    } else {
        float gain = (float)((ctx->input_width > ctx->input_height) ? ctx->input_width : ctx->input_height)
                / ((img_width > img_height) ? img_width : img_height);
        float pad0 = (ctx->input_width  - img_width  * gain) / 2.0;
        float pad1 = (ctx->input_height - img_height * gain) / 2.0;

        for (int i = 0; i < dets_num; i++) {
            if (dets[i].max_prob != 0) {
                struct roi_box *pbox = &rbox[(*roi_num)++];
                ni_resize_coords_padding_mode(&dets[i], pbox, img_width, img_height,
                        gain, pad0, pad1);
            }
        }
    }

    *roi_box = rbox;
    return 0;
}

static int create_yolov8_model(YoloModelCtx *ctx, ni_network_data_t *network_data,
        float obj_thresh, float nms_thresh,
        unsigned int model_width, unsigned int model_height)
{
    int ret = 0;
    int reg_max = 16;

    ctx->obj_thresh = obj_thresh;
    ctx->nms_thresh = nms_thresh;

    ctx->input_width  = network_data->linfo.in_param[0].sizes[0];
    ctx->input_height = network_data->linfo.in_param[0].sizes[1];

    ctx->output_number = network_data->output_num;
    ctx->out_tensor = (uint8_t **)calloc(network_data->output_num, sizeof(uint8_t **));
    if (ctx->out_tensor == nullptr) {
        Error("failed to allocate output tensor bufptr");
        ret = NIERROR(ENOMEM);
        goto out;
    }

    for (unsigned int i = 0; i < network_data->output_num; i++) {
        ni_network_layer_params_t *p_param = &network_data->linfo.out_param[i];
        ctx->out_tensor[i] = (uint8_t *)malloc(ni_ai_network_layer_dims(p_param) * sizeof(float));
        if (ctx->out_tensor[i] == nullptr) {
            Error("failed to allocate output tensor buffer");
            ret = NIERROR(ENOMEM);
            goto out;
        }
    }

    ctx->layers = static_cast<ni_roi_network_layer_t *>(malloc(sizeof(ni_roi_network_layer_t) * network_data->output_num));
    if (!ctx->layers) {
        Error("cannot allocate network layer memory");
        ret = NIERROR(ENOMEM);
        goto out;
    }

    for (unsigned int i = 0; i < network_data->output_num; i++) {
        ctx->layers[i].index     = i;
        ctx->layers[i].width     = network_data->linfo.out_param[i].sizes[0];
        ctx->layers[i].height    = network_data->linfo.out_param[i].sizes[1];
        ctx->layers[i].channel   = network_data->linfo.out_param[i].sizes[2];
        ctx->layers[i].component = 1;
        ctx->layers[i].classes = (ctx->layers[i].channel - (4 * reg_max));
        ctx->layers[i].output_number = ni_ai_network_layer_dims(&network_data->linfo.out_param[i]);
        ctx->layers[i].padding   = 0;
        ctx->layers[i].output = (float *)ctx->out_tensor[i];

        Debug(1, "network layer %d: w %d, h %d, ch %d, co %d, cl %d\n", i,
                ctx->layers[i].width, ctx->layers[i].height,
                ctx->layers[i].channel, ctx->layers[i].component,
                ctx->layers[i].classes);
    }

    ctx->entry_set.obj_entry = -1;
    ctx->entry_set.class_entry = 4 * reg_max;
    ctx->entry_set.coods_entry = 0;

    ctx->det_cache.dets_num = 0;
    ctx->det_cache.capacity = 20;
    ctx->det_cache.dets = static_cast<detection *>(malloc(sizeof(detection) * ctx->det_cache.capacity));
    if (!ctx->det_cache.dets) {
        Error("failed to allocate detection cache");
        ret = NIERROR(ENOMEM);
    }
out:
    return ret;
}

static void destroy_yolov8_model(YoloModelCtx *ctx) {
    if (ctx->out_tensor) {
        int i;
        for (i = 0; i < ctx->output_number; i++) {
            free(ctx->out_tensor[i]);
            //free(ctx->layers[i].biases);
            //ctx->layers[i].biases = nullptr;
        }
        free(ctx->out_tensor);
        ctx->out_tensor = nullptr;
    }
    free(ctx->det_cache.dets);
    free(ctx->layers);
    ctx->layers = nullptr;
}

YoloModel yolov8 = {
    .create_model  = create_yolov8_model,
    .destroy_model = destroy_yolov8_model,
    .ni_get_boxes  = ni_yolov8_get_boxes,
};

#endif
