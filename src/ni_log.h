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
 * @added by zheng.lv@netint.ca
 */

#ifndef _NI_LOG_H
#define _NI_LOG_H

#include <stdio.h>

extern int ni_dev_log_level;

enum {
    NI_LVL_MIN = 0,
    NI_LVL_ERROR = NI_LVL_MIN,
    NI_LVL_WARNING,
    NI_LVL_INFO,
    NI_LVL_DEBUG,
    NI_LVL_TRACE,
    NI_LVL_MAX = NI_LVL_TRACE
};

#define ni_trace(fmt, ...) \
    do {    \
        if (ni_dev_log_level >= NI_LVL_TRACE) {    \
            fprintf(stdout, fmt, ##__VA_ARGS__);    \
        }   \
    } while (0)

#define ni_dbg(fmt, ...) \
    do {    \
        if (ni_dev_log_level >= NI_LVL_DEBUG) {    \
            fprintf(stdout, fmt, ##__VA_ARGS__);    \
        }   \
    } while (0)

#define ni_info(fmt, ...) \
    do {    \
        if (ni_dev_log_level >= NI_LVL_INFO) {    \
            fprintf(stdout, fmt, ##__VA_ARGS__);    \
        }   \
    } while (0)

#define ni_warn(fmt, ...) \
    do {    \
        if (ni_dev_log_level >= NI_LVL_WARNING) {    \
            fprintf(stdout, fmt, ##__VA_ARGS__);    \
        }   \
    } while (0)

#define ni_err(fmt, ...) \
    do {    \
        if (ni_dev_log_level >= NI_LVL_ERROR) {    \
            fprintf(stderr, fmt, ##__VA_ARGS__);    \
        }   \
    } while (0)

#define pr_log(fmt, ...)
#define pr_warn(fmt, ...) \
    do {    \
        fprintf(stdout, fmt, ##__VA_ARGS__);    \
    } while (0)

#define pr_err(fmt, ...) \
    do {    \
        fprintf(stderr, fmt, ##__VA_ARGS__);    \
    } while (0)

extern void ni_dev_set_log_level(int level);
#endif
