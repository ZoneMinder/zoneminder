#include <stdio.h>
#include <stdlib.h>
#include <math.h>
#include "nierrno.h"
#include "yolo_postprocess.h"
#include "yolo_model.h"
#include "zm_logger.h"

#define BIASES_NUM  12

/* class */
// int g_masks[2][3] = { { 3, 4, 5 }, { 1, 2, 3 } };
// float g_biases[] = { 10, 14, 23, 27, 37, 58, 81, 82, 135, 169, 344, 319 };

/* human face */
static int g_masks[2][3] = {{3, 4, 5}, {0, 1, 2}};
static float g_biases[] = {10, 16, 25, 37, 49, 71, 85, 118, 143, 190, 274, 283};

static int entry_index(ni_roi_network_layer_t *l, int batch, int location,
                       int entry) {
    int n   = location / (l->width * l->height);
    int loc = location % (l->width * l->height);
    return n * l->width * l->height * (4 + l->classes + 1) +
           entry * l->width * l->height + loc;
}

static float sigmoid(float x) {
    return (float)(1.0 / (1.0 + (float)exp((double)(-x))));
}

/*
 * nw: network input width
 * nh: network input height
 * lw: layer width
 * lh: layer height
 */
static box get_yolo_box(float *x, float *biases, int n, int index, int col,
                        int row, int lw, int lh, int nw, int nh, int stride) {
    box b;

    b.x = (float)((float)col + sigmoid(x[index + 0 * stride])) / (float)lw;
    b.y = (float)((float)row + sigmoid(x[index + 1 * stride])) / (float)lh;
    b.w = (float)exp((double)x[index + 2 * stride]) * biases[2 * n] / (float)nw;
    b.h = (float)exp((double)x[index + 3 * stride]) * biases[2 * n + 1] /
          (float)nh;

    b.x -= (float)(b.w / 2.0);
    b.y -= (float)(b.h / 2.0);

    return b;
}

static int get_yolo_detections(ni_roi_network_layer_t *l, int netw,
                               int neth, float thresh,
                               detection_cache *det_cache, int *dets_num) {
    int i, n, k;
    float *predictions = l->output;
    float max_prob;
    int prob_class;
    // This snippet below is not necessary
    // Need to comment it in order to batch processing >= 2 images
    // if (l.batch == 2) avg_flipped_yolo(l);
    int count       = 0;
    detection *dets = det_cache->dets;

    *dets_num = 0;

    Debug(1, "pic %dx%d, comp=%d, class=%d, net %dx%d, thresh=%f\n", l->width,
           l->height, l->component, l->classes, netw, neth, thresh);
    for (i = 0; i < l->width * l->height; ++i) {
        int row = i / l->width;
        int col = i % l->width;
        for (n = 0; n < l->component; ++n) {
            int obj_index = entry_index(l, 0, n * l->width * l->height + i, 4);
            float objectness = predictions[obj_index];
            objectness       = sigmoid(objectness);

            prob_class = -1;
            max_prob   = thresh;
            for (k = 0; k < l->classes; k++) {
                int class_index =
                    entry_index(l, 0, n * l->width * l->height + i, 4 + 1 + k);
                double prob = objectness * sigmoid(predictions[class_index]);
                if (prob >= max_prob) {
                    prob_class = k;
                    max_prob   = (float)prob;
                }
            }

            if (prob_class >= 0) {
                box bbox;
                int box_index =
                    entry_index(l, 0, n * l->width * l->height + i, 0);

                if (det_cache->dets_num >= det_cache->capacity) {
                    dets =static_cast<detection *>(
                        realloc(det_cache->dets,
                                sizeof(detection) * (det_cache->capacity + 10)));
                    if (!dets) {
                        Error("failed to realloc detections capacity %d",
                               det_cache->capacity);
                        return NIERROR(ENOMEM);
                    }
                    det_cache->dets = dets;
                    det_cache->capacity += 10;
                    if (det_cache->capacity >= 100) {
                        Warning("too many detections %d\n", det_cache->dets_num);
                    }
                }

                Debug(1, "max_prob %f, class %d\n", max_prob, prob_class);
                bbox = get_yolo_box(predictions, l->biases, l->mask[n],
                                    box_index, col, row, l->width, l->height,
                                    netw, neth, l->width * l->height);

                dets[det_cache->dets_num].max_prob   = max_prob;
                dets[det_cache->dets_num].prob_class = prob_class;
                dets[det_cache->dets_num].bbox       = bbox;
                dets[det_cache->dets_num].objectness = objectness;
                dets[det_cache->dets_num].classes    = l->classes;
                dets[det_cache->dets_num].color      = n;

                Debug(1, "%d, x %f, y %f, w %f, h %f\n",
                       det_cache->dets_num, dets[det_cache->dets_num].bbox.x,
                       dets[det_cache->dets_num].bbox.y,
                       dets[det_cache->dets_num].bbox.w,
                       dets[det_cache->dets_num].bbox.h);
                det_cache->dets_num++;
                count++;
            }
        }
    }
    *dets_num = count;
    return 0;
}

static int nms_comparator(const void *pa, const void *pb) {
    detection *a = (detection *)pa;
    detection *b = (detection *)pb;

    if (a->prob_class > b->prob_class)
        return 1;
    else if (a->prob_class < b->prob_class)
        return -1;
    else {
        if (a->max_prob < b->max_prob)
            return 1;
        else if (a->max_prob > b->max_prob)
            return -1;
    }
    return 0;
}

static float overlap(float x1, float w1, float x2, float w2) {
    float l1    = x1 - w1 / 2;
    float l2    = x2 - w2 / 2;
    float left  = l1 > l2 ? l1 : l2;
    float r1    = x1 + w1 / 2;
    float r2    = x2 + w2 / 2;
    float right = r1 < r2 ? r1 : r2;
    return right - left;
}

static float box_intersection(box a, box b) {
    float w = overlap(a.x, a.w, b.x, b.w);
    float h = overlap(a.y, a.h, b.y, b.h);
    float area;

    if (w < 0 || h < 0)
        return 0;

    area = w * h;
    return area;
}

static float box_union(box a, box b) {
    float i = box_intersection(a, b);
    float u = a.w * a.h + b.w * b.h - i;
    return u;
}

static float box_iou(box a, box b) {
    // return box_intersection(a, b)/box_union(a, b);

    float I = box_intersection(a, b);
    float U = box_union(a, b);
    if (I == 0 || U == 0)
        return 0;

    return I / U;
}

static int nms_sort(detection *dets, int dets_num, float nms_thresh) {
  box boxa, boxb;

  for (int i = 0; i < (dets_num - 1); i++) {
    int prob_class = dets[i].prob_class;
    if (dets[i].max_prob == 0)
      continue;

    if (dets[i].prob_class != dets[i + 1].prob_class)
      continue;

    boxa = dets[i].bbox;
    for (int j = i + 1; j < dets_num && dets[j].prob_class == prob_class; j++) {
      if (dets[j].max_prob == 0)
        continue;

      boxb = dets[j].bbox;
      if (box_iou(boxa, boxb) > nms_thresh)
        dets[j].max_prob = 0;
    }
  }

  return 0;
}

static int resize_coords(detection *dets, int dets_num,
                         uint32_t img_width, uint32_t img_height,
                         uint32_t netw, uint32_t neth,
                         struct roi_box **roi_box, int *roi_num) {
    int i;
    unsigned int left, right, top, bot;
    struct roi_box *rbox;
    int rbox_num = 0;

    if (dets_num == 0) {
        return 0;
    }

    rbox = static_cast<struct roi_box *>(malloc(sizeof(struct roi_box) * dets_num));
    if (!rbox)
        return NIERROR(ENOMEM);

    for (i = 0; i < dets_num; i++) {
        Debug(1, "index %d, max_prob %f, class %d\n", i,
               dets[i].max_prob, dets[i].prob_class);
        if (dets[i].max_prob == 0)
            continue;

        top   = (int)floor(dets[i].bbox.y * img_height + 0.5);
        left  = (int)floor(dets[i].bbox.x * img_width + 0.5);
        right = (int)floor((dets[i].bbox.x + dets[i].bbox.w) * img_width + 0.5);
        bot = (int)floor((dets[i].bbox.y + dets[i].bbox.h) * img_height + 0.5);

        if (right > img_width)
            right = img_width;

        if (bot > img_height)
            bot = img_height;

        Debug(1, "top %d, left %d, right %d, bottom %d\n", top,
               left, right, bot);

        rbox[rbox_num].left       = left;
        rbox[rbox_num].right      = right;
        rbox[rbox_num].top        = top;
        rbox[rbox_num].bottom     = bot;
        rbox[rbox_num].ai_class      = dets[i].prob_class;
        rbox[rbox_num].objectness = dets[i].objectness;
        rbox[rbox_num].prob       = dets[i].max_prob;
        rbox_num++;
    }

    if (rbox_num == 0) {
        free(rbox);
        *roi_num = rbox_num;
        *roi_box = NULL;
    } else {
        *roi_num = rbox_num;
        *roi_box = rbox;
    }

    return 0;
}

static int ni_yolov4_get_boxes(YoloModelCtx *ctx, uint32_t img_width,
        uint32_t img_height, struct roi_box **roi_box, int *roi_num)
{
    int i;
    int ret;
    int dets_num    = 0;
    detection *dets = NULL;
    detection_cache *det_cache = &ctx->det_cache;

    *roi_box = NULL;
    *roi_num = 0;

    ctx->det_cache.dets_num = 0;

    for (i = 0; i < ctx->output_number; i++) {
        ret = get_yolo_detections(&ctx->layers[i], ctx->input_width,
                ctx->input_height, ctx->obj_thresh, det_cache, &dets_num);
        if (ret != 0) {
            Error("failed to get yolo detection at layer %d", i);
            return ret;
        }
        Debug(1, "layer %d, yolo detections: %d", i, dets_num);
    }

    if (det_cache->dets_num == 0) {
        return 0;
    }

    dets     = det_cache->dets;
    dets_num = det_cache->dets_num;
    for (i = 0; i < dets_num; i++) {
        Debug(1, "orig dets %d: x %f,y %f,w %f,h %f,c %d,p %f", i,
               dets[i].bbox.x, dets[i].bbox.y, dets[i].bbox.w, dets[i].bbox.h,
               dets[i].prob_class, dets[i].max_prob);
    }

    qsort(dets, dets_num, sizeof(detection), nms_comparator);
    for (i = 0; i < dets_num; i++) {
        Debug(1, "sorted dets %d: x %f,y %f,w %f,h %f,c %d,p %f", i,
               dets[i].bbox.x, dets[i].bbox.y, dets[i].bbox.w, dets[i].bbox.h,
               dets[i].prob_class, dets[i].max_prob);
    }

    nms_sort(dets, dets_num, ctx->nms_thresh);
    ret = resize_coords(dets, dets_num, img_width, img_height, 
					ctx->input_width, ctx->input_height, roi_box, roi_num);
    if (ret != 0) {
        Error("cannot resize coordinates");
        return ret;
    }

    return 0;
}

static int create_yolov4_model(
    YoloModelCtx *ctx,
    ni_network_data_t *network_data,
    float obj_thresh,
    float nms_thresh,
    unsigned int ctx_width,
    unsigned int ctx_height)
{
    unsigned int i;
    int ret = 0;

    ctx->obj_thresh = obj_thresh;
    ctx->nms_thresh = nms_thresh;

    Debug(1, "Creating yolov4 model %ux%u %p", ctx_width, ctx_height, network_data);
    if (ctx_width != network_data->linfo.in_param[0].sizes[0] ||
            ctx_height != network_data->linfo.in_param[0].sizes[1]) {
        Error("input dimensions not match: expect %dx%d, actual %dx%d",
                ctx_width, ctx_height,
                network_data->linfo.in_param[0].sizes[0],
                network_data->linfo.in_param[0].sizes[1]);
        return NIERROR(EINVAL);
    }

    ctx->input_width  = network_data->linfo.in_param[0].sizes[0];
    ctx->input_height = network_data->linfo.in_param[0].sizes[1];

    ctx->output_number = network_data->output_num;
    ctx->out_tensor = (uint8_t **)calloc(network_data->output_num,
            sizeof(uint8_t **));
    if (ctx->out_tensor == NULL) {
        Error("failed to allocate output tensor bufptr");
        return NIERROR(ENOMEM);
    }

    for (i = 0; i < network_data->output_num; i++) {
        ni_network_layer_params_t *p_param = &network_data->linfo.out_param[i];
        ctx->out_tensor[i] =
                (uint8_t *)malloc(ni_ai_network_layer_dims(p_param) * sizeof(float));
        if (ctx->out_tensor[i] == NULL) {
            Error( "failed to allocate output tensor buffer");
            return NIERROR(ENOMEM);
        }
    }

    ctx->layers =static_cast<ni_roi_network_layer_t *>(
        malloc(sizeof(ni_roi_network_layer_t) * network_data->output_num));
    if (!ctx->layers) {
        fprintf(stderr, "cannot allocate network layer memory\n");
        return NIERROR(ENOMEM);
    }

    for (i = 0; i < network_data->output_num; i++) {
        ctx->layers[i].width     = network_data->linfo.out_param[i].sizes[0];
        ctx->layers[i].height    = network_data->linfo.out_param[i].sizes[1];
        ctx->layers[i].channel   = network_data->linfo.out_param[i].sizes[2];
        ctx->layers[i].component = 3;
        ctx->layers[i].classes =
            (ctx->layers[i].channel / ctx->layers[i].component) -
            (4 + 1);
        ctx->layers[i].output_number =
            ni_ai_network_layer_dims(&network_data->linfo.out_param[i]);

        ctx->layers[i].output = (float *)ctx->out_tensor[i];

        memcpy(ctx->layers[i].mask, &g_masks[i][0], sizeof(ctx->layers[i].mask));

        ctx->layers[i].biases = (float *)malloc(BIASES_NUM * sizeof(float));
        if (! ctx->layers[i].biases) {
          Error("cannot allocate network layer memory");
          return NIERROR(ENOMEM);
        }
        memcpy(ctx->layers[i].biases, &g_biases[0], BIASES_NUM * sizeof(float));

        Debug(1, "network layer %d: w %d, h %d, ch %d, co %d, cl %d\n", i,
                ctx->layers[i].width, ctx->layers[i].height,
                ctx->layers[i].channel, ctx->layers[i].component,
                ctx->layers[i].classes);
    }

    ctx->det_cache.dets_num = 0;
    ctx->det_cache.capacity = 20;
    ctx->det_cache.dets = static_cast<detection *>(malloc(sizeof(detection) * ctx->det_cache.capacity));
    if (!ctx->det_cache.dets) {
        Error("failed to allocate detection cache");
        return NIERROR(ENOMEM);
    }
    return ret;
}

static void destroy_yolov4_model(YoloModelCtx *ctx)
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

YoloModel yolov4 = {
  .create_model       = create_yolov4_model,
  .destroy_model      = destroy_yolov4_model,
  .ni_get_boxes  = ni_yolov4_get_boxes,
};

