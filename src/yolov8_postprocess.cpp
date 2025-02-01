#include <stdio.h>
#include <stdlib.h>
#include <math.h>
#include "nierrno.h"
#include "yolo_postprocess.h"
#include "yolo_model.h"
#include "ni_yolo_utils.h"
#include "zm_logger.h"

#define BIASES_NUM  18
// #define OLD_YOLOV5

#ifdef OLD_YOLOV5
static int g_masks[3][3] = {{3, 4, 5}, {6, 7, 8}, {0, 1, 2}};
static int sequence[3] = {2, 0, 1};
#else
static int g_masks[3][3] = {{0, 1, 2}, {3, 4, 5}, {6, 7, 8}};
static int sequence[3] = {0, 1, 2};
#endif
static float g_biases[] = {10, 13, 16, 30, 33, 23, 30, 61, 62, 45, 59, 119, 116, 90, 156, 198, 373, 326};

static int ni_yolov8_get_boxes(YoloModelCtx *ctx, uint32_t img_width,
        uint32_t img_height, struct roi_box **roi_box, int *roi_num)
{
    int ret;
    int dets_num = 0;
    int real_num = 0;
#ifdef OLD_YOLOV5
    int normalize_box = 1;
#else
    int normalize_box = 0;
#endif
    detection *dets;
    struct roi_box *rbox;

    *roi_box = NULL;
    *roi_num = 0;

    ret = ni_get_yolov8_detections(ctx, sequence, normalize_box);
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

    ctx->obj_thresh = obj_thresh;
    ctx->nms_thresh = nms_thresh;

    ctx->input_width  = network_data->linfo.in_param[0].sizes[0];
    ctx->input_height = network_data->linfo.in_param[0].sizes[1];

    ctx->output_number = network_data->output_num;
    ctx->out_tensor = (uint8_t **)calloc(network_data->output_num,
            sizeof(uint8_t **));
    if (ctx->out_tensor == NULL) {
        Error("failed to allocate output tensor bufptr");
        ret = NIERROR(ENOMEM);
        goto out;
    }

    for (unsigned int i = 0; i < network_data->output_num; i++) {
        ni_network_layer_params_t *p_param = &network_data->linfo.out_param[i];
        ctx->out_tensor[i] =
                (uint8_t *)malloc(ni_ai_network_layer_dims(p_param) * sizeof(float));
        if (ctx->out_tensor[i] == NULL) {
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
        ctx->layers[i].component = 3;
        ctx->layers[i].classes = (ctx->layers[i].channel - (4 + 1));
        ctx->layers[i].output_number =
            ni_ai_network_layer_dims(&network_data->linfo.out_param[i]);
        ctx->layers[i].padding   = 0;
        ctx->layers[i].output = (float *)ctx->out_tensor[i];

        ///TODO rm [0]
        memcpy(ctx->layers[i].mask, &g_masks[i][0], sizeof(ctx->layers[i].mask));

        ctx->layers[i].biases = (float *)malloc(BIASES_NUM * sizeof(float));
        if (! ctx->layers[i].biases) {
            Error("cannot allocate network layer memory");
            ret = NIERROR(ENOMEM);
            goto out;
        }
        memcpy(ctx->layers[i].biases, &g_biases[0], BIASES_NUM * sizeof(float));

        Debug(1, "network layer %d: w %d, h %d, ch %d, co %d, cl %d\n", i,
                ctx->layers[i].width, ctx->layers[i].height,
                ctx->layers[i].channel, ctx->layers[i].component,
                ctx->layers[i].classes);
    }

    ctx->entry_set.obj_entry = 4;
    ctx->entry_set.class_entry = 5;
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

static void destroy_yolov8_model(YoloModelCtx *ctx)
{
    if (ctx->out_tensor) {
        int i;
        for (i = 0; i < ctx->output_number; i++) {
            free(ctx->out_tensor[i]);
            free(ctx->layers[i].biases);
            ctx->layers[i].biases = NULL;
        }
        free(ctx->out_tensor);
        ctx->out_tensor = NULL;
    }
    free(ctx->det_cache.dets);
    free(ctx->layers);
    ctx->layers = NULL;
}

YoloModel yolov8 = {
    .create_model  = create_yolov8_model,
    .destroy_model = destroy_yolov8_model,
    .ni_get_boxes  = ni_yolov8_get_boxes,
};

