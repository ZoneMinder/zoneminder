#ifndef ZM_SDP_H
#define ZM_SDP_H
#include <stdlib.h>
#include <stdint.h>

#include <assert.h>
#include <string.h>

#include <netinet/in.h>

extern "C"
{
//#include "network.h"
#include "ffmpeg/avstring.h"
#include "ffmpeg/rtsp.h"

//#include <stdint.h>
//#include "avcodec.h"
#include "ffmpeg/rtp.h"
}

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

typedef struct RTSPStream {
    URLContext *rtp_handle; /* RTP stream handle */
    RTPDemuxContext *rtp_ctx; /* RTP parse context */

    int stream_index; /* corresponding stream index, if any. -1 if none (MPEG2TS case) */
    int interleaved_min, interleaved_max;  /* interleave ids, if TCP transport */
    char control_url[1024]; /* url for this stream (from SDP) */

    int sdp_port; /* port (from SDP content - not used in RTSP) */
    struct in_addr sdp_ip; /* IP address  (from SDP content - not used in RTSP) */
    int sdp_ttl;  /* IP TTL (from SDP content - not used in RTSP) */
    int sdp_payload_type; /* payload type - only used in SDP */
    rtp_payload_data_t rtp_payload_data; /* rtp payload parsing infos from SDP */

    RTPDynamicProtocolHandler *dynamic_handler; ///< Only valid if it's a dynamic protocol. (This is the handler structure)
    void *dynamic_protocol_context; ///< Only valid if it's a dynamic protocol. (This is any private data associated with the dynamic protocol)
} RTSPStream;

extern "C"
{
int rtsp_next_attr_and_value(const char **p, char *attr, int attr_size, char *value, int value_size); ///< from rtsp.c, but used by rtp dynamic protocol handlers.

void ff_rtp_send_data(AVFormatContext *s1, const uint8_t *buf1, int len, int m);
const char *ff_rtp_enc_name(int payload_type);
enum CodecID ff_rtp_codec_id(const char *buf, enum CodecType codec_type);

void av_register_rtp_dynamic_payload_handlers(void);
}

int sdp_parse(AVFormatContext *s, const char *content);

#endif //ZM_SDP_H
