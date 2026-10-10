#!/usr/bin/env python3
"""Packet parsing in scripts/zmalarm-server.py.

Run with: python3 tests/python/test_zmalarm_server.py
Needs neither pyzm nor a running ZoneMinder.
"""
import importlib.util
import json
import os
import socket
import struct
import unittest

HERE = os.path.dirname(os.path.abspath(__file__))
SCRIPT = os.path.join(HERE, '..', '..', 'scripts', 'zmalarm-server.py')
spec = importlib.util.spec_from_file_location('zmalarm_server', SCRIPT)
als = importlib.util.module_from_spec(spec)
spec.loader.exec_module(als)

CAMERA = '192.168.1.10'
MONITORS = {CAMERA: 3}


def address_field(ip):
    return '0x%08X' % struct.unpack('<I', socket.inet_aton(ip))[0]


def body(**fields):
    return json.dumps(fields).encode()


class ParseHeader(unittest.TestCase):
    def test_valid(self):
        header = struct.pack('<' + als.HEADER_FORMAT, 0xff, 1, 0, 0, 1504, 120)
        self.assertEqual(als.parse_header(header), 120)

    def test_short(self):
        with self.assertRaises(ValueError):
            als.parse_header(b'\xff\x01\x00')

    def test_length_out_of_range(self):
        for n in (0, als.MAX_BODY + 1):
            header = struct.pack('<' + als.HEADER_FORMAT, 0xff, 1, 0, 0, 1504, n)
            with self.assertRaises(ValueError):
                als.parse_header(header)


class ParseAlarm(unittest.TestCase):
    def test_from_camera(self):
        data = body(Address=address_field(CAMERA), Event='HumanDetect')
        self.assertEqual(als.parse_alarm(data, CAMERA, MONITORS), (3, 'HumanDetect'))

    def test_forged_address_rejected(self):
        data = body(Address=address_field(CAMERA), Event='HumanDetect')
        with self.assertRaises(ValueError):
            als.parse_alarm(data, '192.168.1.99', MONITORS)

    def test_unknown_camera(self):
        data = body(Address=address_field('192.168.1.99'), Event='HumanDetect')
        with self.assertRaises(ValueError):
            als.parse_alarm(data, '192.168.1.99', MONITORS)

    def test_malformed(self):
        for data in (b'not json{', b'\xff\xfe', b'[]', b'null',
                     body(Event='HumanDetect'),
                     body(Address=address_field(CAMERA)),
                     body(Address='zz', Event='HumanDetect'),
                     body(Address='0x1FFFFFFFF', Event='HumanDetect'),
                     body(Address=7, Event='HumanDetect')):
            with self.assertRaises(ValueError, msg=data):
                als.parse_alarm(data, CAMERA, MONITORS)


class RecvExact(unittest.TestCase):
    def test_short_read(self):
        a, b = socket.socketpair()
        a.sendall(b'abc')
        a.close()
        with self.assertRaises(ValueError):
            als.recv_exact(b, 5)
        b.close()

    def test_timeout(self):
        a, b = socket.socketpair()
        b.settimeout(0.1)
        with self.assertRaises(OSError):
            als.recv_exact(b, 5)
        a.close()
        b.close()


class MonitorAddress(unittest.TestCase):
    def test_ip_in_path(self):
        self.assertEqual(als.monitor_address('rtsp://u:p@192.168.1.10:554/x'), CAMERA)

    def test_no_address(self):
        self.assertIsNone(als.monitor_address(''))
        self.assertIsNone(als.monitor_address(None))
        self.assertIsNone(als.monitor_address('/dev/video0'))


if __name__ == '__main__':
    unittest.main()
