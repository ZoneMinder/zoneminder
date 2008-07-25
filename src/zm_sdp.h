#ifndef ZM_SDP_H
#define ZM_SDP_H

#if HAVE_LIBAVFORMAT_AVFORMAT_H
#include <libavformat/rtsp.h>
#elif HAVE_FFMPEG_AVFORMAT_H
#include <ffmpeg/rtsp.h>
#else
#error "No location for rtsp.h found"
#endif

//
// This file contains chunks of ffmpeg code that are not currently exported.
// The main thing we are after is the sdp parser
//

//
// Part of libavformat/rtp.h
//

#define RTP_MIN_PACKET_LENGTH 12
#define RTP_MAX_PACKET_LENGTH 1500 /* XXX: suppress this define */

int rtp_get_codec_info(AVCodecContext *codec, int payload_type);

/** return < 0 if unknown payload type */
int rtp_get_payload_type(AVCodecContext *codec);

typedef struct RTPDemuxContext RTPDemuxContext;
typedef struct rtp_payload_data_s rtp_payload_data_s;
RTPDemuxContext *rtp_parse_open(AVFormatContext *s1, AVStream *st, URLContext *rtpc, int payload_type, rtp_payload_data_s *rtp_payload_data);
int rtp_parse_packet(RTPDemuxContext *s, AVPacket *pkt,
                     const uint8_t *buf, int len);
void rtp_parse_close(RTPDemuxContext *s);

int rtp_get_local_port(URLContext *h);
int rtp_set_remote_url(URLContext *h, const char *uri);
void rtp_get_file_handles(URLContext *h, int *prtp_fd, int *prtcp_fd);

/**
 * some rtp servers assume client is dead if they don't hear from them...
 * so we send a Receiver Report to the provided ByteIO context
 * (we don't have access to the rtcp handle from here)
 */
int rtp_check_and_send_back_rr(RTPDemuxContext *s, int count);

#define RTP_PT_PRIVATE 96
#define RTP_VERSION 2
#define RTP_MAX_SDES 256   /**< maximum text length for SDES */

/* RTCP paquets use 0.5 % of the bandwidth */
#define RTCP_TX_RATIO_NUM 5
#define RTCP_TX_RATIO_DEN 1000

/** Structure listing useful vars to parse RTP packet payload*/
typedef struct rtp_payload_data_s
{
    int sizelength;
    int indexlength;
    int indexdeltalength;
    int profile_level_id;
    int streamtype;
    int objecttype;
    char *mode;

    /** mpeg 4 AU headers */
    struct AUHeaders {
        int size;
        int index;
        int cts_flag;
        int cts;
        int dts_flag;
        int dts;
        int rap_flag;
        int streamstate;
    } *au_headers;
    int nb_au_headers;
    int au_headers_length_bytes;
    int cur_au_index;
} rtp_payload_data_t;

//
// Part of libavformat/rtp_internal.h
//

#include <stdint.h>
//#include "rtp.h"

// these statistics are used for rtcp receiver reports...
typedef struct {
    uint16_t max_seq;           ///< highest sequence number seen
    uint32_t cycles;            ///< shifted count of sequence number cycles
    uint32_t base_seq;          ///< base sequence number
    uint32_t bad_seq;           ///< last bad sequence number + 1
    int probation;              ///< sequence packets till source is valid
    int received;               ///< packets received
    int expected_prior;         ///< packets expected in last interval
    int received_prior;         ///< packets received in last interval
    uint32_t transit;           ///< relative transit time for previous packet
    uint32_t jitter;            ///< estimated jitter.
} RTPStatistics;

/**
 * Packet parsing for "private" payloads in the RTP specs.
 *
 * @param s stream context
 * @param pkt packet in which to write the parsed data
 * @param timestamp pointer in which to write the timestamp of this RTP packet
 * @param buf pointer to raw RTP packet data
 * @param len length of buf
 * @param flags flags from the RTP packet header (PKT_FLAG_*)
 */
typedef int (*DynamicPayloadPacketHandlerProc) (struct RTPDemuxContext * s,
                                                AVPacket * pkt,
                                                uint32_t *timestamp,
                                                const uint8_t * buf,
                                                int len, int flags);

typedef struct RTPDynamicProtocolHandler_s {
    // fields from AVRtpDynamicPayloadType_s
    const char enc_name[50];    /* XXX: still why 50 ? ;-) */
    enum CodecType codec_type;
    enum CodecID codec_id;

    // may be null
    int (*parse_sdp_a_line) (AVStream * stream,
                             void *protocol_data,
                             const char *line); ///< Parse the a= line from the sdp field
    void *(*open) (); ///< allocate any data needed by the rtp parsing for this dynamic data.
    void (*close)(void *protocol_data); ///< free any data needed by the rtp parsing for this dynamic data.
    DynamicPayloadPacketHandlerProc parse_packet; ///< parse handler for this dynamic packet.

    struct RTPDynamicProtocolHandler_s *next;
} RTPDynamicProtocolHandler;

// moved out of rtp.c, because the h264 decoder needs to know about this structure..
struct RTPDemuxContext {
    AVFormatContext *ic;
    AVStream *st;
    int payload_type;
    uint32_t ssrc;
    uint16_t seq;
    uint32_t timestamp;
    uint32_t base_timestamp;
    uint32_t cur_timestamp;
    int max_payload_size;
    struct MpegTSContext *ts;   /* only used for MP2T payloads */
    int read_buf_index;
    int read_buf_size;
    /* used to send back RTCP RR */
    URLContext *rtp_ctx;
    char hostname[256];

    RTPStatistics statistics; ///< Statistics for this stream (used by RTCP receiver reports)

    /* rtcp sender statistics receive */
    int64_t last_rtcp_ntp_time;    // TODO: move into statistics
    int64_t first_rtcp_ntp_time;   // TODO: move into statistics
    uint32_t last_rtcp_timestamp;  // TODO: move into statistics

    /* rtcp sender statistics */
    unsigned int packet_count;     // TODO: move into statistics (outgoing)
    unsigned int octet_count;      // TODO: move into statistics (outgoing)
    unsigned int last_octet_count; // TODO: move into statistics (outgoing)
    int first_packet;
    /* buffer for output */
    uint8_t buf[RTP_MAX_PACKET_LENGTH];
    uint8_t *buf_ptr;

    /* special infos for au headers parsing */
    rtp_payload_data_t *rtp_payload_data; // TODO: Move into dynamic payload handlers

    /* dynamic payload stuff */
    DynamicPayloadPacketHandlerProc parse_packet;     ///< This is also copied from the dynamic protocol handler structure
    void *dynamic_protocol_context;        ///< This is a copy from the values setup from the sdp parsing, in rtsp.c don't free me.
    int max_frames_per_packet;
};

extern RTPDynamicProtocolHandler *RTPFirstDynamicPayloadHandler;

int rtsp_next_attr_and_value(const char **p, char *attr, int attr_size, char *value, int value_size); ///< from rtsp.c, but used by rtp dynamic protocol handlers.

void ff_rtp_send_data(AVFormatContext *s1, const uint8_t *buf1, int len, int m);
const char *ff_rtp_enc_name(int payload_type);
enum CodecID ff_rtp_codec_id(const char *buf, enum CodecType codec_type);

void av_register_rtp_dynamic_payload_handlers(void);

// //
// Part of libavformat/rtsp.c
// //

#include <sys/time.h>
#include <unistd.h> /* for select() prototype */

//#define DEBUG
//#define DEBUG_RTP_TCP

enum RTSPClientState {
    RTSP_STATE_IDLE,
    RTSP_STATE_PLAYING,
    RTSP_STATE_PAUSED,
};

typedef struct RTSPState {
    URLContext *rtsp_hd; /* RTSP TCP connexion handle */
    int nb_rtsp_streams;
    struct RTSPStream **rtsp_streams;

    enum RTSPClientState state;
    int64_t seek_timestamp;

    /* XXX: currently we use unbuffered input */
    //    ByteIOContext rtsp_gb;
    int seq;        /* RTSP command sequence number */
    char session_id[512];
    enum RTSPProtocol protocol;
    char last_reply[2048]; /* XXX: allocate ? */
    RTPDemuxContext *cur_rtp;
} RTSPState;

//
// Declaration from libavformat/rtsp.c
//

int sdp_parse(AVFormatContext *s, const char *content);

#endif // ZM_SDP_H
