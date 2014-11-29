//
// ZoneMinder Streamer, $Date: 2010-10-14 23:21:00 +0200 (Thu, 14 Oct 2010) $
// Copyright (C) 2001-2010 Philip Coombes, Chris Kistner
//
// This program is based on revision 3143 of
// http://svn.zoneminder.com/svn/zm/trunk/src/zms.cpp 
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

#include <stdio.h>
#include <stdlib.h>

#include <sys/ipc.h>
#include <sys/msg.h>

#include "zm.h"
#include "zm_db.h"
#include "zm_user.h"
#include "zm_signal.h"
#include "zm_monitor.h"
#include "zm_stream.h"

// Possible command-line options
#define OPTIONS "e:o:u:f:s:b:m:d:i:?"

// Default ZMS values
#define ZMS_DEFAULT_DEBUG 0
#define ZMS_DEFAULT_ID 1
#define ZMS_DEFAULT_BITRATE 100000
#define ZMS_DEFAULT_SCALE 100
#define ZMS_DEFAULT_MODE "mpeg"
#define ZMS_DEFAULT_FORMAT "asf"
#define ZMS_DEFAULT_FPS 25.0
#define ZMS_DEFAULT_BUFFER 1000

int main(int argc, char** argv) {
    self = argv[0];
    // Set initial values to the default values
    int debug = ZMS_DEFAULT_DEBUG;
    int id = ZMS_DEFAULT_ID;
    int bitrate = ZMS_DEFAULT_BITRATE;
    int scale = ZMS_DEFAULT_SCALE;
    char mode[32];
    sprintf(mode, "%s", ZMS_DEFAULT_MODE);
    char format[32];
    sprintf(format, "%s", ZMS_DEFAULT_FORMAT);
    double maxfps = ZMS_DEFAULT_FPS;
    int buffer = ZMS_DEFAULT_BUFFER;

    // Parse command-line options
    int arg;
    while ((arg = getopt(argc, argv, OPTIONS)) != -1) {
        switch (arg) {
            case 'e':
                sprintf(mode, "%s", optarg);
                break;
            case 'o':
                sprintf(format, "%s", optarg);
                break;
            case 'u':
                buffer = atoi(optarg);
                break;
            case 'f':
                maxfps = atof(optarg);
                break;
            case 's':
                scale = atoi(optarg);
                break;
            case 'b':
                bitrate = atoi(optarg);
                break;
            case 'm':
                id = atoi(optarg);
                break;
            case 'd':
                debug = atoi(optarg);
                break;
            case 'i':
            case '?':
                printf("-e <mode> : Specify output mode: mpeg/jpg/zip/single/raw. Default = %s\n", ZMS_DEFAULT_MODE);
                printf("-o <format> : Specify output format. Default = %s\n", ZMS_DEFAULT_FORMAT);
                printf("-u <buffer size> : Specify buffer size in ms. Default = %d\n", ZMS_DEFAULT_BUFFER);
                printf("-f <maximum fps> : Specify maximum framerate. Default = %lf\n", ZMS_DEFAULT_FPS);
                printf("-s <scale> : Specify scale. Default = %d\n", ZMS_DEFAULT_SCALE);
                printf("-b <bitrate in bps> : Specify bitrate. Default = %d\n", ZMS_DEFAULT_BITRATE);
                printf("-m <monitor id> : Specify monitor id. Default = %d\n", ZMS_DEFAULT_ID);
                printf("-d <debug mode> : 0 = off, 1 = no streaming, 2 = with streaming. Default = 0\n");
                printf("-i or -? : This information\n");
                return EXIT_SUCCESS;
        }
    }

    // Set stream type
    StreamBase::StreamType streamtype;
    if (!strcasecmp("raw", mode))
        streamtype = MonitorStream::STREAM_RAW;
    else if (!strcasecmp("mpeg", mode))
        streamtype = MonitorStream::STREAM_MPEG;
    else if (!strcasecmp("jpg", mode))
        streamtype = MonitorStream::STREAM_JPEG;
    else if (!strcasecmp("single", mode))
        streamtype = MonitorStream::STREAM_SINGLE;
    else if (!strcasecmp("zip", mode))
        streamtype = MonitorStream::STREAM_ZIP;
    else
        streamtype = MonitorStream::STREAM_MPEG;

    if (debug) {
        // Show stream parameters
        printf("Stream parameters:\n");
        switch (streamtype) {
            case MonitorStream::STREAM_MPEG:
                printf("Output mode (-e) = %s\n", "mpeg");
                printf("Output format (-o) = %s\n", format);
                break;
            default:
                printf("Output mode (-e) = %s\n", mode);
        }
        printf("Buffer size (-u) = %d ms\n", buffer);
        printf("Maximum FPS (-f) = %lf FPS\n", maxfps);
        printf("Scale (-s) = %d%%\n", scale);
        printf("Bitrate (-b) = %d bps\n", bitrate);
        printf("Monitor Id (-m) = %d\n", id);
    }

    if (debug) {
        // Set ZM debugger to print to stdout
        printf("Setting up ZoneMinder debugger to print to stdout...");
        setenv("ZM_DBG_PRINT", "1", 1);
        printf("Done.\n");
    }

    // Loading ZM configurations
    printf("Loading ZoneMinder configurations...");
    zmLoadConfig();
    printf("Done.\n");

    logInit("zmstreamer");
    
    ssedetect();

    // Setting stream parameters
    MonitorStream stream;
    stream.setStreamScale(scale); // default = 100 (scale)
    stream.setStreamReplayRate(100); // default = 100 (rate)
    stream.setStreamMaxFPS(maxfps); // default = 10 (maxfps)
    if (debug) stream.setStreamTTL(1);
    else stream.setStreamTTL(0); // default = 0 (ttl)
    stream.setStreamQueue(0); // default = 0 (connkey)
    stream.setStreamBuffer(buffer); // default = 0 (buffer)
    stream.setStreamStart(id); // default = 0 (monitor_id)
    stream.setStreamType(streamtype);
    if (streamtype == MonitorStream::STREAM_MPEG) {
#if HAVE_LIBAVCODEC
        if (debug) printf("HAVE_LIBAVCODEC is set\n");
        stream.setStreamFormat(format); // default = "" (format)
        stream.setStreamBitrate(bitrate); // default = 100000 (bitrate)
#else
        fprintf(stderr, "MPEG streaming is disabled.\nYou should configure with the --with-ffmpeg option and rebuild to use this functionality.\n");
        logTerm();
        zmDbClose();
        return EXIT_FAILURE;
#endif
    }

    if (debug != 1) {
        if (debug) printf("Running stream...");

        // Output headers
        fprintf(stdout, "Server: ZoneMinder Video Server/%s\r\n", ZM_VERSION);
        time_t now = time(0);
        char date_string[64];
        strftime(date_string, sizeof (date_string) - 1, "%a, %d %b %Y %H:%M:%S GMT", gmtime(&now));
        fprintf(stdout, "Expires: Mon, 26 Jul 1997 05:00:00 GMT\r\n");
        fprintf(stdout, "Last-Modified: %s\r\n", date_string);
        fprintf(stdout, "Cache-Control: no-store, no-cache, must-revalidate\r\n");
        fprintf(stdout, "Cache-Control: post-check=0, pre-check=0\r\n");
        fprintf(stdout, "Pragma: no-cache\r\n");

        // Run stream
        stream.runStream();
    }
    if (debug) printf("Done.\n");

    logTerm();
    zmDbClose();

    return (EXIT_SUCCESS);
}
