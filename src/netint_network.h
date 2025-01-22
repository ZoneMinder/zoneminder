/*
 * Copyright (c) 2010 Nicolas George
 * Copyright (c) 2011 Stefano Sabatini
 * Copyright (c) 2014 Andrey Utkin
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * @file
 * API for ai to create netint network, put input into network, 
 * get output from network
 * @example netint_network.h
 *
 * @added by zheng.lv@netint.ca
 * use libxcoder api to create netint network, put input into network, 
 * get output from network.
 */

#ifndef _NETINT_NETWORK_H
#define _NETINT_NETWORK_H

#include <stdint.h>
#include "nierrno.h"
#include "ni_device_api.h"
#include "ni_util.h"

#define NUM_NETWORK_FRAME 7

typedef struct NiNetworkFrame {
    ni_session_data_io_t api_frame;
    ni_session_data_io_t api_packet;

//    int pic_width;
//    int pic_height;
//    AVPixelFormat sw_format;

    int scale_width;
    int scale_height;
    int scale_format;

    int in_use;
} NiNetworkFrame;

typedef struct crop_box
{
    int w;
    int h;
    int x;
    int y;
}crop_box;

//typedef struct NiNetworkPacket {
//    ni_session_data_io_t api_packet;
//} NiNetworkPacket;

typedef struct NiNetworkLayer {
    void *data;
    uint32_t size;
} NiNetworkLayer;

typedef struct NiNetworkContext {
    ni_session_context_t npu_api_ctx;
    ni_session_context_t scale_api_ctx;
    ni_network_data_t network_data;
} NiNetworkContext;

extern int ni_set_network_input(NiNetworkContext *network_ctx, bool hwframe,
        ni_session_data_io_t *in_frame, crop_box *area_box,
        int pic_width, int pic_height, NiNetworkFrame *out_frame,
        bool blockable);
extern int ni_invoke_network_inference(NiNetworkContext *network_ctx, bool hwframe);
extern int ni_get_network_output(NiNetworkContext *network_ctx, bool hwframe,
        NiNetworkFrame *out_frame, bool blockable, bool convert,
        uint8_t **data);
extern int ni_alloc_network_context(NiNetworkContext **p_network_ctx,
        bool hwframe, int devid, int keep_alive_timeout, int scale_format,
        int scale_width, int scale_height, const char *nbg_file);
extern void ni_cleanup_network_context(NiNetworkContext *network_ctx, bool hwframe);
extern int ni_convert_to_tensor(NiNetworkContext *network_ctx, NiNetworkFrame *frame, void *data, int index);
extern int ni_convert_to_tensors(NiNetworkContext *network_ctx, NiNetworkFrame *frame, void **data);
#endif
