#include "config.h"

#ifdef HAVE_QUADRA
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
 * @example netint_network.c
 *
 * @added by zheng.lv@netint.ca
 * use libxcoder api to create netint network, put input into network, 
 * get output from network.
 */

#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>
#include "netint_network.h"
#include "ni_log.h"
#include "zm_logger.h"

static int init_hwframe_scale(NiNetworkContext *network_ctx,
        int scale_format, int scale_width, int scale_height,
        int devid, int devfd, int blkfd,
        int keep_alive_timeout)
{
    ni_retcode_t retval;
    ni_session_context_t *scale_api_ctx = &network_ctx->scale_api_ctx;
    int ret = 0;

    retval = ni_device_session_context_init(scale_api_ctx);
    if (retval != NI_RETCODE_SUCCESS) {
        Error("hw scaler session context init failure");
        return NIERROR(EIO);
    }

    //scale_api_ctx->device_handle     = devfd;
    //scale_api_ctx->blk_io_handle     = blkfd;
    scale_api_ctx->device_type       = NI_DEVICE_TYPE_SCALER;
    scale_api_ctx->scaler_operation  = NI_SCALER_OPCODE_SCALE;
    scale_api_ctx->hw_id             = devid;
    scale_api_ctx->keep_alive_timeout = keep_alive_timeout;

    retval = ni_device_session_open(scale_api_ctx, NI_DEVICE_TYPE_SCALER);
    if (retval < 0) {
        Error("could not open scaler session");
        ret = NIERROR(EIO);
        goto out;
    }

    Debug(1, "initialize scaler, %dx%d, format %d",
            scale_width, scale_height, scale_format);

    ni_log2(scale_api_ctx, NI_LOG_INFO, "Initialize Scaler: device %d, blk_io_handle %d\n", scale_api_ctx->hw_id,
            scale_api_ctx->blk_io_handle);

    /* Create scale frame pool on device */
    retval = ni_device_alloc_frame(scale_api_ctx,
                             NIALIGN(scale_width, 2),
                             NIALIGN(scale_height, 2),
                             scale_format,
                             NI_SCALER_FLAG_IO | NI_SCALER_FLAG_PC,
                             0, // rec width
                             0, // rec height
                             0, // rec X pos
                             0, // rec Y pos
                             NUM_NETWORK_FRAME, // rgba color/pool size
                             0, // frame index
                             NI_DEVICE_TYPE_SCALER);
    if (retval < 0) {
        Error("could not build frame pool");
        ni_device_session_close(scale_api_ctx, 1, NI_DEVICE_TYPE_SCALER);
        ni_device_session_context_clear(scale_api_ctx);
        ret = NIERROR(EIO);
        goto out;
    }

out:
    return ret;
}

static void cleanup_hwframe_scale(NiNetworkContext *network_ctx)
{
    ni_session_context_t *scale_api_ctx = &network_ctx->scale_api_ctx;

    ni_log2(scale_api_ctx, NI_LOG_INFO, "Cleanup Scaler: device %d, blk_io_handle %d\n", scale_api_ctx->hw_id, scale_api_ctx->blk_io_handle);

    ni_device_session_close(scale_api_ctx, 1, NI_DEVICE_TYPE_SCALER);
    ni_device_session_context_clear(scale_api_ctx);
}

void ni_cleanup_network_context(NiNetworkContext *network_ctx, bool hwframe)
{
    if (network_ctx) {
        ni_retcode_t retval =
            ni_device_session_close(&network_ctx->npu_api_ctx, 1, NI_DEVICE_TYPE_AI);
        if (retval != NI_RETCODE_SUCCESS) {
            Error("%s: failed to close npu session. retval %d", __func__,
                   retval);
        }
        ni_device_session_context_clear(&network_ctx->npu_api_ctx);
        if (hwframe == true) {
            cleanup_hwframe_scale(network_ctx);
        }
        free(network_ctx);
        network_ctx = nullptr;
    }
}

int ni_alloc_network_context(NiNetworkContext **p_network_ctx,
        bool hwframe, int devid, int keep_alive_timeout, int scale_format,
        int scale_width, int scale_height, const char *nbg_file)
{
    int ret;

    if ((nbg_file == NULL) || (access(nbg_file, R_OK) != 0)) {
        Error("invalid network binary path");
        return NIERROR(EINVAL);
    }

    NiNetworkContext *network_ctx = new NiNetworkContext();
    //(NiNetworkContext *)calloc(1, sizeof(NiNetworkContext));
    if (!network_ctx) {
        Error("failed to allocate network context");
        return NIERROR(ENOMEM);
    }

    ni_retcode_t retval = ni_device_session_context_init(&network_ctx->npu_api_ctx);
    if (retval != NI_RETCODE_SUCCESS) {
        Error("failed to initialize npu session context");
        return NIERROR(EIO);
    }

    if (hwframe) {
//        network_ctx->npu_api_ctx.device_handle = devfd;
//        network_ctx->npu_api_ctx.blk_io_handle = blkfd;
        network_ctx->npu_api_ctx.hw_action = NI_CODEC_HW_ENABLE;
    }
    network_ctx->npu_api_ctx.hw_id = devid;
    network_ctx->npu_api_ctx.device_type = NI_DEVICE_TYPE_AI;
    network_ctx->npu_api_ctx.keep_alive_timeout = keep_alive_timeout;
    retval = ni_device_session_open(&network_ctx->npu_api_ctx, NI_DEVICE_TYPE_AI);
    if (retval != NI_RETCODE_SUCCESS) {
      Error("failed to open npu session. retval %d", retval);
      return NIERROR(EIO);
    }

    retval = ni_ai_config_network_binary(&network_ctx->npu_api_ctx,
                                         &network_ctx->network_data,
                                         nbg_file);
    if (retval != NI_RETCODE_SUCCESS) {
        Error("failed to configure npu session. retval %d",
               retval);
        ret = NIERROR(EIO);
        goto failed_out;
    }

    if (scale_width != 0 && scale_height != 0) {
        if (scale_width != network_ctx->network_data.linfo.in_param[0].sizes[0] ||
                scale_height != network_ctx->network_data.linfo.in_param[0].sizes[1]) {
            Error("input dimensions not match: expect %dx%d, actual %dx%d",
                    scale_width, scale_height,
                    network_ctx->network_data.linfo.in_param[0].sizes[0],
                    network_ctx->network_data.linfo.in_param[0].sizes[1]);
            ret = NIERROR(EINVAL);
            goto failed_out;
        }
    }

    if (hwframe) {
        ret = init_hwframe_scale(network_ctx, scale_format, scale_width,
                scale_height, devid, network_ctx->npu_api_ctx.device_handle,
                network_ctx->npu_api_ctx.blk_io_handle, keep_alive_timeout);
        if (ret != 0) {
            Error("failed to initialize hw scale");
            goto failed_out;
        }
    }
    *p_network_ctx = network_ctx;
    return 0;

failed_out:
    ni_cleanup_network_context(network_ctx, hwframe);
    return ret;
}

static int ni_hwframe_dwl(NiNetworkContext *network_ctx, ni_session_data_io_t *p_session_data,
        niFrameSurface1_t *src_surf, int output_format)
{
    int ret = 0;
    int pixel_format;
    ni_session_context_t *scale_ctx = &network_ctx->scale_api_ctx;

    switch (output_format) {
        case GC620_I420:
            pixel_format = NI_PIX_FMT_YUV420P;
            break;
        case GC620_RGBA8888:
            pixel_format = NI_PIX_FMT_RGBA;
            break;
        case GC620_RGB888_PLANAR:
            pixel_format = NI_PIX_FMT_BGRP;
            break;
        default:
            ni_log(NI_LOG_ERROR, "Pixel format not supported.");
            return NI_RETCODE_INVALID_PARAM;
    }

    Debug(1, "HwDwl Scaler: device %d, blk_io_handle %d", scale_ctx->hw_id, scale_ctx->blk_io_handle);
    ret = ni_frame_buffer_alloc_dl(&(p_session_data->data.frame), src_surf->ui16width, src_surf->ui16height, pixel_format);
    if (ret != NI_RETCODE_SUCCESS) {
        return NI_RETCODE_ERROR_MEM_ALOC;
    }

    scale_ctx->is_auto_dl = false;
    ret = ni_device_session_hwdl(scale_ctx, p_session_data, src_surf);
    if (ret <= 0) {
        ni_frame_buffer_free(&p_session_data->data.frame);
        return ret;
    }
    return ret;
}

int write_rawvideo_data(FILE *p_file, int width, int height, int format,
                        ni_frame_t *p_out_frame)
{
    if (p_file && p_out_frame)
    {
        if (format == GC620_I420)
        {
            int i, j;
            for (i = 0; i < 3; i++)
            {
                uint8_t *src = p_out_frame->p_data[i];
                int write_width = width;
                int write_height = height;
                int plane_width = width;
                int plane_height = height;

                write_width *= 1;   // bit depth 1

                if (i == 1 || i == 2)
                {
                    plane_height /= 2;
                    // U/V stride size is multiple of 128, following the calculation
                    // in ni_decoder_frame_buffer_alloc
                    plane_width =
                        (((int)(write_width) / 2 * 1 + 127) / 128) * 128;
                    write_height /= 2;
                    write_width /= 2;
                }

                for (j = 0; j < plane_height; j++)
                {
                    if (j < write_height &&
                        fwrite(src, write_width, 1, p_file) != 1)
                    {
                        Error("Error: writing data plane %d: height %d error! ret = %d",
                                i, plane_height, ferror(p_file));
                    }
                    src += plane_width;
                }
            }
        } else if (format == GC620_RGBA8888)
        {
            uint8_t *src = p_out_frame->p_data[0];
            if (fwrite(src, width * height * 4, 1, p_file) != 1)
            {
                Error("Error: ferror rc = %d", ferror(p_file));
            }
        } else if (format == GC620_RGB888_PLANAR)
        {
            uint8_t *src;
            int i;
            for (i = 0; i < 3; i++)
            {
                src = p_out_frame->p_data[i];
                if (fwrite(src, width * height, 1, p_file) != 1)
                {
                    Error("Error: ferror rc = %d", ferror(p_file));
                }
            }
        }

        if (fflush(p_file))
        {
            Error("Error: writing data frame flush failed! errno %d",
                    errno);
        }
    }
    return 0;
}

static int ni_scale_dwl(NiNetworkContext *network_ctx, niFrameSurface1_t *src_frame,
        int width, int height, int output_format)
{
    ni_session_data_io_t hwdl_session_data = {};
    static int frame_number = 0;
    int ret = ni_hwframe_dwl(network_ctx, &hwdl_session_data, src_frame, output_format);
    if (ret > 0) {
        char name[256] = { 0 };
        FILE *fp;
        snprintf(name, sizeof(name), "scale/scaled_%d.dat", frame_number);
        fp = fopen(name, "wb");
        if (fp) {
            write_rawvideo_data(fp, width, height, output_format,
                    &(hwdl_session_data.data.frame));
            fclose(fp);
        }
    }

    frame_number++;
    ni_frame_buffer_free(&(hwdl_session_data.data.frame));
    return ret;
}

static int ni_hwframe_scale(NiNetworkContext *network_ctx,
        niFrameSurface1_t *in_frame, crop_box *area_box,
        int pic_width, int pic_height, NiNetworkFrame *out_frame)
{
    ni_session_context_t *scale_api_ctx = &network_ctx->scale_api_ctx;
    ni_retcode_t retcode;

    Debug(1, "Scale Scaler: device %d, blk_io_handle %d", scale_api_ctx->hw_id, scale_api_ctx->blk_io_handle);
    /*
     * Allocate device input frame. This call won't actually allocate a frame,
     * but sends the incoming hardware frame index to the scaler manager
     */
    if (area_box) {
        retcode = ni_device_alloc_frame(
            scale_api_ctx, NIALIGN(pic_width, 2), NIALIGN(pic_height, 2),
            GC620_I420, 0, area_box->w, area_box->h, area_box->x, area_box->y,
            in_frame->ui32nodeAddress, in_frame->ui16FrameIdx, NI_DEVICE_TYPE_SCALER);
    } else {
        retcode = ni_device_alloc_frame(
            scale_api_ctx, NIALIGN(pic_width, 2), NIALIGN(pic_height, 2),
            GC620_I420, 0, 0, 0, 0, 0, in_frame->ui32nodeAddress, in_frame->ui16FrameIdx, NI_DEVICE_TYPE_SCALER);
    }

    if (retcode != NI_RETCODE_SUCCESS) {
        Error("Can't allocate device input frame %d %dx%d index %d %d",
               retcode, NIALIGN(pic_width, 2), NIALIGN(pic_height, 2), in_frame->ui16FrameIdx, GC620_I420);
        return NIERROR(ENOMEM);
    }
        Debug(1, "Can allocate device input frame %d %dx%d index %d %d",
               retcode, NIALIGN(pic_width, 2), NIALIGN(pic_height, 2), in_frame->ui16FrameIdx, GC620_I420);

    /* Allocate hardware device destination frame. This acquires a frame from
     * the pool */
    retcode = ni_device_alloc_frame(
        scale_api_ctx, NIALIGN(out_frame->scale_width, 2),
        NIALIGN(out_frame->scale_height, 2),
        out_frame->scale_format, NI_SCALER_FLAG_IO, 0, 0,
        0, 0, 0, -1, NI_DEVICE_TYPE_SCALER);
    if (retcode != NI_RETCODE_SUCCESS) {
        Error("Can't allocate device output frame %d %dx%d format %d",
               retcode, NIALIGN(out_frame->scale_width, 2), NIALIGN(out_frame->scale_height, 2), out_frame->scale_format);
        return NIERROR(ENOMEM);
    }

    int ret = ni_device_session_read_hwdesc(
            scale_api_ctx, &out_frame->api_frame, NI_DEVICE_TYPE_SCALER);
    if (ret != NI_RETCODE_SUCCESS) {
        Error("Cannot read hwdesc");
        return NIERROR(EIO);
    }
    // download raw data, only for test
    if (0) {
        printf("dump scaled output, scale width %d, height %d, format %d",
                out_frame->scale_width, out_frame->scale_height, out_frame->scale_format);
        niFrameSurface1_t *filt_frame_surface;
        filt_frame_surface = (niFrameSurface1_t *)out_frame->api_frame.data.frame.p_data[3];
        filt_frame_surface->ui16width = out_frame->scale_width;
        filt_frame_surface->ui16height = out_frame->scale_height;
        filt_frame_surface->bit_depth = 1;
        filt_frame_surface->encoding_type = NI_PIXEL_PLANAR_FORMAT_PLANAR;
        printf("filtered frame: width %d, height %d", filt_frame_surface->ui16width,
                filt_frame_surface->ui16height);
        ni_scale_dwl(network_ctx, filt_frame_surface, out_frame->scale_width,
                out_frame->scale_height, out_frame->scale_format);
    }

    return 0;
}

int ni_set_network_input(NiNetworkContext *network_ctx, bool hwframe,
        ni_session_data_io_t *in_frame, crop_box *area_box,
        int pic_width, int pic_height, NiNetworkFrame *out_frame,
        bool blockable)
{
    int ret = 0;
    ni_retcode_t retval;

    if (hwframe) {
        niFrameSurface1_t *filt_frame_surface;
        // Looks like hwscale to 640x640
        ret = ni_hwframe_scale(network_ctx, (niFrameSurface1_t *)in_frame->data.frame.p_data[3], area_box, pic_width, pic_height, out_frame);
        if (ret != 0) {
            Error("Error run hwframe scale");
            goto out;
        }

        filt_frame_surface = (niFrameSurface1_t *)out_frame->api_frame.data.frame.p_data[3];
        //Debug(1, ("filt frame surface frameIdx %d",
        //        filt_frame_surface->ui16FrameIdx);
        //fflush(stdout);

        /* allocate output buffer */
        retval = ni_device_alloc_frame(&network_ctx->npu_api_ctx, 0, 0, 0, 0, 0, 0, 0, 0,
                filt_frame_surface->ui32nodeAddress,
                filt_frame_surface->ui16FrameIdx,
                NI_DEVICE_TYPE_AI);
        if (retval != NI_RETCODE_SUCCESS) {
          ni_hwframe_buffer_recycle(filt_frame_surface, filt_frame_surface->device_handle);
          Error("failed to alloc hw input frame");
          ret = NIERROR(ENOMEM);
          goto out;
        }
    } else {
      // in_frame should already have been scaled
      ret = ni_device_session_write(&network_ctx->npu_api_ctx, in_frame, NI_DEVICE_TYPE_AI);
      if (ret < 0) {
        return NIERROR(EIO);
      } else if (ret == 0) {
        return NIERROR(EAGAIN);
      }
    }

out:
    return ret;
}

int ni_invoke_network_inference(NiNetworkContext *network_ctx, bool hwframe)
{
    return 0;
}

int ni_get_network_output(NiNetworkContext *network_ctx, bool hwframe,
        NiNetworkFrame *out_frame, bool blockable, bool convert,
        uint8_t **data)
{
    int ret = 0;
    int retval;
    ni_session_context_t *npu_api_ctx = &network_ctx->npu_api_ctx;

redo:
    retval = ni_device_session_read(npu_api_ctx, &out_frame->api_packet, NI_DEVICE_TYPE_AI);
    if (retval < 0) {
        Error("read hwdesc retval %d", ret);
        ret = NIERROR(EIO);
        goto out;
    } else if (retval == 0) {
        if (blockable) {
            goto redo;
        } else {
          Debug(1, "EAGAIN");
            ret = NIERROR(EAGAIN);
            goto out;
        }
    } else {
      Debug(1, "ret from ni_device_session_read, %d", ret);
    }

    if (hwframe) {
        niFrameSurface1_t *filt_frame_surface = (niFrameSurface1_t *)out_frame->api_frame.data.frame.p_data[3];
        ni_hwframe_buffer_recycle(filt_frame_surface, filt_frame_surface->device_handle);
    }

    if (convert) {
        static int frame_number = 0;
        for (uint32_t i = 0; i < network_ctx->network_data.output_num; i++) {
          retval = ni_network_layer_convert_output((float *)data[i],
                    ni_ai_network_layer_dims(&network_ctx->network_data.linfo.out_param[i]) * sizeof(float),
                    &out_frame->api_packet.data.packet, &network_ctx->network_data,
                    i);
          Debug(1, "ouput %d %d", i, retval);
            if (retval != NI_RETCODE_SUCCESS) {
                Error("failed to read layer %d output. retval %d",
                        i, retval);
                ret = NIERROR(EIO);
                goto out;
            }
        }
        frame_number++;
    }
out:
    return ret;
}

int ni_convert_to_tensor(NiNetworkContext *network_ctx, NiNetworkFrame *frame, void *data, unsigned int index)
{
    if (index >= network_ctx->network_data.output_num) {
        return NIERROR(EINVAL);
    }

    ni_retcode_t retval = ni_network_layer_convert_output((float *)data,
            ni_ai_network_layer_dims(&network_ctx->network_data.linfo.out_param[index]) * sizeof(float),
            &frame->api_packet.data.packet, &network_ctx->network_data,
            index);
    if (retval != NI_RETCODE_SUCCESS) {
        Error("failed to read layer %d output. retval %d",
                index, retval);
        return NIERROR(EIO);
    }
    return 0;
}

int ni_convert_to_tensors(NiNetworkContext *network_ctx, NiNetworkFrame *frame, void **data) {
    for (uint32_t i = 0; i < network_ctx->network_data.output_num; i++) {
        ni_retcode_t retval = ni_network_layer_convert_output((float *)data[i],
                ni_ai_network_layer_dims(&network_ctx->network_data.linfo.out_param[i]) * sizeof(float),
                &frame->api_packet.data.packet, &network_ctx->network_data,
                i);
        if (retval != NI_RETCODE_SUCCESS) {
            Error("failed to read layer %d output. retval %d",
                    i, retval);
            return NIERROR(EIO);
        }
    }
    return 0;
}
#endif
