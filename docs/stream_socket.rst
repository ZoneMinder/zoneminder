Monitor Stream Socket
=====================

Every monitor's ``zmc`` process serves the monitor's compressed media
(video access units and audio packets, as received from the camera) on a
local unix domain socket::

    ZM_PATH_SOCKS/stream_{monitor_id}.sock     e.g. /run/zm/stream_1.sock

The socket replaces the per-monitor media FIFOs
(``video_fifo_{id}.{codec}`` / ``audio_fifo_{id}.{codec}``) that previous
versions created when the RTSPServer option was enabled. Unlike the FIFOs,
the socket is always on, carries both streams on one connection, supports
multiple simultaneous consumers, delivers codec parameters in a handshake,
and makes packet loss observable per consumer. The same connection also
carries monitor lifecycle events (capture faults and analysis-state
changes) as a push channel. ZoneMinder's own ``zm_rtsp_server`` is a
consumer of this socket.

The path follows a fixed convention, so consumers can derive it from
``ZM_PATH_SOCKS`` (zm.conf) and the monitor id. It is also published in the
monitor's shared-memory ``stream_socket_path`` field (exposed by the web
Monitor object and the ``ZoneMinder::Memory`` Perl module) so a reader with
shm access does not need the convention or the producer's zm.conf. Either
way, connect with retry — the socket appears when zmc starts and survives
camera reconnects.

Only cameras that deliver encoded packets (the Ffmpeg source and anything
built on it) announce a media stream. Sources that hand zmc decoded images
(V4L2, MJPEG over HTTP, VNC) have no encoded stream to forward, so their
socket sends no HELLO or MEDIA and carries lifecycle events only.

Access control
--------------

Sockets are created with mode 0660, owned by the ZoneMinder user, with the
group taken from ``ZM_STREAM_SOCKET_GROUP`` (conf.d, defaults to the web
group). Grant a service access by group membership. Optionally,
``ZM_STREAM_SOCKET_ALLOWED_UIDS`` (comma-separated numeric uids) restricts
connections using kernel-verified peer credentials (``SO_PEERCRED`` on
Linux, ``getpeereid`` on the BSDs and macOS).

Per-consumer queue limits are tunable in conf.d:
``ZM_STREAM_SOCKET_MAX_CLIENTS`` (default 8),
``ZM_STREAM_SOCKET_QUEUE_BYTES`` (8 MiB),
``ZM_STREAM_SOCKET_QUEUE_MSGS`` (256) and
``ZM_STREAM_SOCKET_STALL_SECS`` (10). A consumer that exceeds its queue has
its oldest media messages dropped (never HELLO), observable as sequence
gaps and in STATS; a consumer accepting no data while its queue is full is
disconnected. A slow consumer never affects zmc or other consumers.

Wire protocol (version 1)
-------------------------

All integers are little-endian. Every message starts with a 24-byte fixed
header::

    u32  length      bytes following this field (20 + payload size)
    u8   version     protocol version, 1
    u8   type        message type, below
    u8   stream      0 = video, 1 = audio, 2 = monitor (EVENT frames)
    u8   flags       bit 0: keyframe (video); other bits reserved, 0
    u32  sequence    per-stream, counts every message produced
    u32  generation  stream epoch; a bump means re-init the decoder
    i64  pts_us      signed microseconds (AV_TIME_BASE_Q), shared clock;
                     AV_NOPTS_VALUE (0x8000000000000000) means unknown

``sequence`` counts messages *produced*, including any dropped from a slow
consumer's queue, so loss appears as gaps. ``generation`` increments when
stream parameters change (camera reconfigure); a fresh HELLO follows and
sequences restart at 0.

Message types:

``0x01 HELLO``
  Sent per stream on connect and again on every generation bump. The
  payload is a TLV list (u8 tag, u16 length, value; unknown tags must be
  skipped): ``0x01`` codec id (u32, AVCodecID), ``0x02`` extradata (raw
  ``codecpar->extradata``: SPS/PPS/VPS for H.26x, AudioSpecificConfig for
  AAC, sequence header OBU for AV1), ``0x03``/``0x04`` width/height (u32),
  ``0x05``/``0x06`` fps numerator/denominator (u32), ``0x07``/``0x08``
  sample rate/channels (u32), ``0x09``/``0x0A`` profile/level (u32).

``0x02 MEDIA``
  One complete video access unit or one audio packet (raw, not
  ADTS-wrapped — the HELLO extradata makes wrapping unnecessary). The
  bytes are exactly what the camera's demuxer produced, so the H.264/H.265
  NAL framing follows the source: RTSP cameras deliver Annex B start
  codes, while MP4/MKV file sources deliver AVCC length-prefixed NALs. A
  consumer can tell which from the HELLO extradata (an AVCC
  ``avcC``/``hvcC`` record starts with ``0x01``; Annex B parameter sets
  start with ``00 00 00 01``). ``zm_rtsp_server`` handles Annex B only.

``0x03 KEYFRAME``
  Sent once after HELLO to a newly connected consumer: the most recent
  cached video keyframe access unit, carrying its original pts. Lets a
  consumer render a first frame immediately instead of waiting up to a
  GOP; treat the next MEDIA keyframe as the stream anchor. The cache is
  cleared when the camera connection closes, so nothing is replayed from
  a previous capture session.

``0x04 STATS``
  Periodic (default every 5 s): u64 messages sent, u64 messages dropped
  for this consumer.

``0x05 BYE``
  zmc is shutting the stream down; the close that follows is not an error.

``0x06 EVENT``
  A monitor lifecycle event (``stream`` is ``2``). This is a push channel
  for capture-fault and analysis-state changes, independent of media: it
  flows even while the camera is disconnected and the media streams are
  stalled. ``sequence`` is a per-monitor event counter (its own series, not
  reset by a media generation bump), ``generation`` is the media epoch in
  effect at emission for correlation, and ``pts_us`` is 0 when no media is
  flowing — the event's own timestamp travels in a TLV.

  The payload is a ``u16`` event code followed by a TLV tail (u8 tag, u16
  length, value; unknown tags skipped). Event codes::

      0x0001 snapshot                current health + state, on connect
      0x0101 connection_failed       camera connect failed
      0x0102 connection_restored
      0x0103 prime_capture_failed    could not prime the capture source
      0x0104 prime_capture_restored
      0x0105 capture_failed          pre/capture/post capture failed
      0x0106 capture_resumed         pipeline recovered after any fault
      0x0201 state_changed           analysis state transition

  TLV tags: ``0x01`` wall_clock_us (u64, unix-epoch microseconds — the
  timestamp to surface), ``0x02`` message (utf8 detail), ``0x03`` state_id
  (u32, current state), ``0x04`` prev_state_id (u32, for state_changed),
  ``0x05`` detail (u32, errno / ffmpeg error code), ``0x06`` state_name
  (utf8, e.g. ``IDLE``/``ALARM``), ``0x07`` health_code (u16, the active
  fault code carried by a faulted snapshot; absent or 0 means healthy).

  ``snapshot`` is sent to every consumer on connect (after the HELLOs, the
  events analogue of the cached KEYFRAME) and is refreshed on every health
  or state change, so a late subscriber learns current status without
  waiting for the next transition. Its ``sequence`` is the number of events
  produced so far, i.e. the sequence the next broadcast EVENT will carry; the
  snapshot is a state message, not an event in that series, so a consumer
  seeds its gap tracking from it and must not treat the following EVENT with
  the same sequence as a duplicate. The capture-fault edges are emitted by
  zmc once per transition; because the socket survives camera reconnects,
  ``connection_failed`` is observable exactly when media has stopped.

There are no client-to-server messages in version 1; zmc ignores inbound
bytes.

The reference encoder/decoder lives in ``src/zm_stream_socket_protocol.h``;
``src/zm_stream_socket_client.cpp`` is a reusable C++ consumer with
callbacks for HELLO, MEDIA/KEYFRAME, STATS, EVENT, BYE and disconnect, and
``tools/zm_stream_socket_dump.py`` is a dependency-free Python example that
prints every message.
