#!/usr/bin/env python3
"""Dump messages from a ZoneMinder monitor stream socket (protocol v1).

Usage: zm_stream_socket_dump.py /run/zm/stream_1.sock [message_count]
"""
import socket
import struct
import sys

TYPES = {1: 'HELLO', 2: 'MEDIA', 3: 'KEYFRAME', 4: 'STATS', 5: 'BYE', 6: 'EVENT'}
TLV_NAMES = {1: 'codec_id', 2: 'extradata', 3: 'width', 4: 'height',
             5: 'fps_num', 6: 'fps_den', 7: 'sample_rate', 8: 'channels',
             9: 'profile', 10: 'level'}
EVENT_CODES = {0x0001: 'snapshot', 0x0101: 'connection_failed',
               0x0102: 'connection_restored', 0x0103: 'prime_capture_failed',
               0x0104: 'prime_capture_restored', 0x0105: 'capture_failed',
               0x0106: 'capture_resumed', 0x0201: 'state_changed'}
EVENT_TLV_NAMES = {1: 'wall_clock_us', 2: 'message', 3: 'state_id',
                   4: 'prev_state_id', 5: 'detail', 6: 'state_name',
                   7: 'health_code'}


def read_exact(sock, n):
    buf = b''
    while len(buf) < n:
        chunk = sock.recv(n - len(buf))
        if not chunk:
            raise EOFError('socket closed')
        buf += chunk
    return buf


def parse_hello(payload):
    parts = []
    pos = 0
    while pos + 3 <= len(payload):
        tag = payload[pos]
        length = struct.unpack('<H', payload[pos + 1:pos + 3])[0]
        value = payload[pos + 3:pos + 3 + length]
        pos += 3 + length
        name = TLV_NAMES.get(tag, 'tag%#x' % tag)
        if tag == 2:
            parts.append('extradata=%dB[%s...]' % (len(value), value[:8].hex()))
        elif len(value) == 4:
            parts.append('%s=%d' % (name, struct.unpack('<I', value)[0]))
        else:
            parts.append('%s=%s' % (name, value.hex()))
    return ' '.join(parts)


def parse_event(payload):
    if len(payload) < 2:
        return 'truncated'
    code = struct.unpack('<H', payload[:2])[0]
    parts = [EVENT_CODES.get(code, 'code%#06x' % code)]
    pos = 2
    while pos + 3 <= len(payload):
        tag = payload[pos]
        length = struct.unpack('<H', payload[pos + 1:pos + 3])[0]
        value = payload[pos + 3:pos + 3 + length]
        pos += 3 + length
        name = EVENT_TLV_NAMES.get(tag, 'tag%#x' % tag)
        if tag == 2 or tag == 6:  # message, state_name
            parts.append('%s=%r' % (name, value.decode('utf-8', 'replace')))
        elif len(value) == 8:
            parts.append('%s=%d' % (name, struct.unpack('<Q', value)[0]))
        elif len(value) == 4:
            parts.append('%s=%d' % (name, struct.unpack('<I', value)[0]))
        elif len(value) == 2:
            parts.append('%s=%d' % (name, struct.unpack('<H', value)[0]))
        else:
            parts.append('%s=%s' % (name, value.hex()))
    return ' '.join(parts)


def main():
    path = sys.argv[1]
    count = int(sys.argv[2]) if len(sys.argv) > 2 else 30
    sock = socket.socket(socket.AF_UNIX, socket.SOCK_STREAM)
    sock.settimeout(20)
    sock.connect(path)
    media_seen = {}
    for _ in range(count):
        header = read_exact(sock, 24)
        length, version, mtype, stream, flags, seq, gen, pts = \
            struct.unpack('<IBBBBIIQ', header)
        assert version == 1, 'unexpected protocol version %d' % version
        payload = read_exact(sock, length - 20)
        line = '%-8s stream=%d flags=%#04x seq=%-6d gen=%d pts=%-16d payload=%dB' % (
            TYPES.get(mtype, hex(mtype)), stream, flags, seq, gen, pts, len(payload))
        if mtype == 1:
            line += '  {%s}' % parse_hello(payload)
        elif mtype == 6:
            line += '  {%s}' % parse_event(payload)
        elif mtype == 4:
            sent, dropped = struct.unpack('<QQ', payload)
            line += '  sent=%d dropped=%d' % (sent, dropped)
        elif mtype in (2, 3):
            prev = media_seen.get(stream)
            if prev is not None and mtype == 2 and seq != prev + 1:
                line += '  <-- SEQ GAP (lost %d)' % (seq - prev - 1)
            if mtype == 2:
                media_seen[stream] = seq
        print(line, flush=True)


if __name__ == '__main__':
    main()
