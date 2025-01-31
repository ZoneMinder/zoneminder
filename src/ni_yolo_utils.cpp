#include "ni_yolo_utils.h"
#include "zm_logger.h"

int anchor_stride[3] = {8, 16, 32};

static float sigmoid(float x)
{
    return (float)(1.0 / (1.0 + (float)exp((double)(-x))));
}

/*
 * nw: network input width
 * nh: network input height
 * lw: layer width
 * lh: layer height
 */
static box get_yolo_box(float *x, float *biases, int oidx, int n, int index, int col,
                        int row, int lw, int lh, int nw, int nh, int stride, int normalize)
{
    box b;

    b.x = (sigmoid(x[index + 0 * stride]) * 2.0 - 0.5 + (float)col) * anchor_stride[oidx];
    b.y = (sigmoid(x[index + 1 * stride]) * 2.0 - 0.5 + (float)row) * anchor_stride[oidx];
    b.w = (float)(pow((sigmoid(x[index + 2 * stride]) * 2.0), 2.0)) * biases[2 * n];
    b.h = (float)(pow((sigmoid(x[index + 3 * stride]) * 2.0), 2.0)) * biases[2 * n + 1];

    if (normalize) {
        float normalized = 0.0015625;

        b.x = b.x * nh * normalized;
        b.y = b.y * nw * normalized;
        b.w = b.w * nh * normalized;
        b.h = b.h * nw * normalized;
    }
    return b;
}

static int get_yolo_detections(ni_roi_network_layer_t *l, int netw,
                               int neth, float thresh,
                               detection_cache *det_cache, int *dets_num,
                               struct box_entry_set *set, int normalize_box)
{
    int i, n, k;
    float *predictions = l->output;
    float max_prob;
    int prob_class;
    int count       = 0;
    detection *dets = det_cache->dets;

    *dets_num = 0;

    Debug(4, "pic %dx%d, comp=%d, class=%d, net %dx%d, thresh=%f", l->width,
           l->height, l->component, l->classes, netw, neth, thresh);
    for (n = 0; n < l->component; ++n) {
        for (i = 0; i < l->width * l->height; ++i) {
            int row = i / l->width;
            int col = i % l->width;
            int obj_index = entry_index(l, 0, n, i, set->obj_entry);
            float objectness = predictions[obj_index];
            objectness       = sigmoid(objectness);

            if (objectness <= thresh) {
                continue;
            }

            prob_class = -1;
            max_prob   = thresh;
            for (k = 0; k < l->classes; k++) {
                int class_index =
                    entry_index(l, 0, n, i, set->class_entry + k);
                double prob = objectness * sigmoid(predictions[class_index]);
                if (prob >= max_prob) {
                    prob_class = k;
                    max_prob   = (float)prob;
                }
            }

            if (prob_class >= 0) {
                box bbox;
                int box_index =
                    entry_index(l, 0, n, i, set->coods_entry);

                if (det_cache->dets_num >= det_cache->capacity) {
                    dets = static_cast<detection *>(realloc(det_cache->dets,
                                sizeof(detection) * (det_cache->capacity + 10)));
                    if (!dets) {
                        Error("failed to realloc detections capacity %d",
                                det_cache->capacity);
                        return NIERROR(ENOMEM);
                    }
                    det_cache->dets = dets;
                    det_cache->capacity += 10;
                    if (det_cache->capacity >= 300) {
                        ni_warn("too many detections %d\n", det_cache->dets_num);
                    }
                }

                Debug(4, "max_prob %f, class %d", max_prob, prob_class);
                bbox = get_yolo_box(predictions, l->biases, l->index, l->mask[n],
                        box_index, col, row, l->width, l->height,
                        netw, neth, l->width * l->height, normalize_box);

                dets[det_cache->dets_num].max_prob   = max_prob;
                dets[det_cache->dets_num].prob_class = prob_class;
                dets[det_cache->dets_num].bbox       = bbox;
                dets[det_cache->dets_num].objectness = objectness;
                dets[det_cache->dets_num].classes    = l->classes;
                dets[det_cache->dets_num].color      = n;
                dets[det_cache->dets_num].sub_idx    = i;
                dets[det_cache->dets_num].layer_idx  = l->index;

                Debug(4, "%d, x %f, y %f, w %f, h %f",
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

static int nms_comparator(const void *pa, const void *pb)
{
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

static float overlap(float x1, float w1, float x2, float w2)
{
    float l1    = x1 - w1 / 2;
    float l2    = x2 - w2 / 2;
    float left  = l1 > l2 ? l1 : l2;
    float r1    = x1 + w1 / 2;
    float r2    = x2 + w2 / 2;
    float right = r1 < r2 ? r1 : r2;
    return right - left;
}

static float box_intersection(box a, box b)
{
    float w = overlap(a.x, a.w, b.x, b.w);
    float h = overlap(a.y, a.h, b.y, b.h);
    float area;

    if (w < 0 || h < 0)
        return 0;

    area = w * h;
    return area;
}

static float box_union(box a, box b, float overlay)
{
    // float i = box_intersection(a, b);
    float u = a.w * a.h + b.w * b.h - overlay;
    return u;
}

static float box_iou(box a, box b)
{
    // return box_intersection(a, b)/box_union(a, b);

    float I = box_intersection(a, b);
    float U = box_union(a, b, I);
    if (I == 0 || U == 0)
        return 0;

    return I / U;
}

// rm the same detection is overlay area ratio > nms_thresh
static int nms_sort(detection *dets, int dets_num,
                    float nms_thresh)
{
    int i, j;
    box boxa, boxb;

    for (i = 0; i < (dets_num - 1); i++) {
        int prob_class = dets[i].prob_class;
        if (dets[i].max_prob == 0)
            continue;

        if (dets[i].prob_class != dets[i + 1].prob_class)
            continue;

        boxa = dets[i].bbox;
        for (j = i + 1; j < dets_num && dets[j].prob_class == prob_class; j++) {
            if (dets[j].max_prob == 0)
                continue;

            boxb = dets[j].bbox;
            if (box_iou(boxa, boxb) > nms_thresh)
                dets[j].max_prob = 0;
        }
    }

    return 0;
}

int ni_get_yolov5_detections(YoloModelCtx *ctx, int sequence[3], int normalize_box)
{
    int i;
    int ret;
    int dets_num    = 0;
    detection *dets = NULL;
    detection_cache *det_cache = &ctx->det_cache;

    ctx->det_cache.dets_num = 0;
    for (i = 0; i < ctx->output_number; i++) {
        ret = get_yolo_detections(&ctx->layers[sequence[i]], ctx->input_width,
                ctx->input_height, ctx->obj_thresh, det_cache, &dets_num,
                &ctx->entry_set, normalize_box);
        if (ret != 0) {
            ni_err("failed to get yolo detection at layer %d\n", i);
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
        Debug(4,"orig dets %d: x %f,y %f,w %f,h %f,c %d,p %f", i,
               dets[i].bbox.x, dets[i].bbox.y, dets[i].bbox.w, dets[i].bbox.h,
               dets[i].prob_class, dets[i].max_prob);
    }

    qsort(dets, dets_num, sizeof(detection), nms_comparator);

    for (i = 0; i < dets_num; i++) {
        Debug(4, "sorted dets %d: x %f,y %f,w %f,h %f,c %d,p %f", i,
               dets[i].bbox.x, dets[i].bbox.y, dets[i].bbox.w, dets[i].bbox.h,
               dets[i].prob_class, dets[i].max_prob);
    }

    nms_sort(dets, dets_num, ctx->nms_thresh);

    /* convert xywh -> x1y1x2y2 */
    for (i = 0; i < dets_num; i++) {
        if (dets[i].max_prob == 0)
            continue;
        box b = dets[i].bbox;
        dets[i].bbox.x -= (b.w / 2.0);
        dets[i].bbox.y -= (b.h / 2.0);
        dets[i].bbox.w = b.x + (b.w / 2.0);
        dets[i].bbox.h = b.y + (b.h / 2.0);
        // pr_err("i %d, class:%d, score:%.8f, x:%.8f, y:%.8f,"
        //        " w:%.8f, h:%.8f\n",
        //        i, dets[i].prob_class, dets[i].objectness,
        //        dets[i].bbox.x, dets[i].bbox.y, dets[i].bbox.w, dets[i].bbox.h);
        // fflush(stdout);
    }

    return dets_num;
}

void ni_resize_coords_tiling_mode(detection *det, struct roi_box *roi_box,
        int img_width, int img_height, float gain_x, float gain_y)
{
    int left, right, top, bot;

    left   = (det->bbox.x / gain_x);
    top    = (det->bbox.y / gain_y);
    right  = (det->bbox.w / gain_x);
    bot    = (det->bbox.h / gain_y);

    if (top < 0)
        top = 0;

    if (left < 0)
        left = 0;

    if (right > img_width)
        right = img_width;

    if (bot > img_height)
        bot = img_height;

    Debug(1, "top %d, left %d, right %d, bottom %d\n", top,
            left, right, bot);

    roi_box->left       = left;
    roi_box->right      = right;
    roi_box->top        = top;
    roi_box->bottom     = bot;
    roi_box->ai_class   = det->prob_class;
    roi_box->objectness = det->objectness;
    roi_box->prob       = det->max_prob;
}

void ni_resize_coords_padding_mode(detection *det, struct roi_box *roi_box,
        int img_width, int img_height, float gain, float pad0, float pad1)
{
    int left, right, top, bot;
    /*
     * box.x, box.y : (x1, y1)
     * box.w, box.h : (x2, y2)
     */
    left   = ((det->bbox.x - pad0) / gain);
    top    = ((det->bbox.y - pad1) / gain);
    right  = ((det->bbox.w - pad0) / gain);
    bot    = ((det->bbox.h - pad1) / gain);

    if (top < 0)
        top = 0;

    if (left < 0)
        left = 0;

    if (right > img_width)
        right = img_width;

    if (bot > img_height)
        bot = img_height;

    Debug(1, "top %d, left %d, right %d, bottom %d\n", top,
            left, right, bot);

    roi_box->left       = left;
    roi_box->right      = right;
    roi_box->top        = top;
    roi_box->bottom     = bot;
    roi_box->ai_class      = det->prob_class;
    roi_box->objectness = det->objectness;
    roi_box->prob       = det->max_prob;
}
