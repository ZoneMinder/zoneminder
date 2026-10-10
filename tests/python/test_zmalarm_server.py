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
import time
import unittest

HERE = os.path.dirname(os.path.abspath(__file__))
SCRIPT = os.path.join(HERE, '..', '..', 'scripts', 'zmalarm-server.py')
spec = importlib.util.spec_from_file_location('zmalarm_server', SCRIPT)
als = importlib.util.module_from_spec(spec)
spec.loader.exec_module(als)

import configparser

CAMERA = '192.168.1.10'
OTHER_CAMERA = '192.168.1.11'
MONITORS = {CAMERA: [3], OTHER_CAMERA: [4]}
# Two cameras behind one NAT address, as map_monitors() builds it when both
# Paths point at the NAT address and secrets.ini names each camera's LAN address.
NAT = '203.0.113.7'
NAT_MONITORS = {NAT: [5, 6], '10.0.0.5': [5], '10.0.0.6': [6]}


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

    def test_camera_claiming_another_gets_its_own_monitor(self):
        data = body(Address=address_field(OTHER_CAMERA), Event='HumanDetect')
        self.assertEqual(als.parse_alarm(data, CAMERA, MONITORS), (3, 'HumanDetect'))

    def test_behind_nat(self):
        # Path holds the NAT address; the camera reports its LAN address.
        data = body(Address=address_field('10.1.1.20'), Event='HumanDetect')
        self.assertEqual(als.parse_alarm(data, NAT, {NAT: [7]}), (7, 'HumanDetect'))

    def test_shared_nat_address_uses_the_reported_address(self):
        for lan, monid in (('10.0.0.5', 5), ('10.0.0.6', 6)):
            data = body(Address=address_field(lan), Event='HumanDetect')
            self.assertEqual(als.parse_alarm(data, NAT, NAT_MONITORS), (monid, 'HumanDetect'))

    def test_forged_from_lan_address_of_nat_camera_rejected(self):
        # A host that is not the NAT address cannot use a camera's LAN address.
        data = body(Address=address_field('10.0.0.5'), Event='HumanDetect')
        with self.assertRaises(ValueError):
            als.parse_alarm(data, '198.51.100.9', NAT_MONITORS)

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


class AlarmSources(unittest.TestCase):
    def conf(self, text):
        c = configparser.ConfigParser(interpolation=None)
        c.read_string(text)
        return c

    def test_none(self):
        self.assertEqual(als.alarm_sources(self.conf('[secrets]\nZM_USER=admin\n')), {})

    def test_listed(self):
        conf = self.conf('[alarmserver]\nmonitor_5 = 203.0.113.7 10.0.0.5\nmonitor_6 = 10.0.0.6, 203.0.113.7\nother = 1.2.3.4\n')
        self.assertEqual(als.alarm_sources(conf), {5: ['203.0.113.7', '10.0.0.5'], 6: ['10.0.0.6', '203.0.113.7']})

    def test_map_monitors(self):
        monitors = [(5, 'rtsp://203.0.113.7:5541/s'), (6, 'rtsp://203.0.113.7:5542/s'),
                    (3, 'rtsp://u:p@192.168.1.10/x'), (8, '/dev/video0')]
        sources = {5: ['10.0.0.5'], 6: ['10.0.0.6']}
        self.assertEqual(als.map_monitors(monitors, sources),
                         {NAT: [5, 6], '10.0.0.5': [5], '10.0.0.6': [6], CAMERA: [3]})


class FakeMonitor:
    def __init__(self, fail_arm=False):
        self.calls = []
        self.fail_arm = fail_arm

    def arm(self):
        self.calls.append('arm')
        if self.fail_arm:
            raise OSError('API down')

    def disarm(self):
        self.calls.append('disarm')


class ForcedAlarms(unittest.TestCase):
    LENGTH = 0.3

    def alarms(self, alarmed=False):
        return als.ForcedAlarms(self.LENGTH, already_alarmed=lambda monid: alarmed)

    def test_ends_after_its_length(self):
        alarms, m = self.alarms(), FakeMonitor()
        self.assertTrue(alarms.trigger(3, m))
        self.assertEqual(m.calls, ['arm'])
        time.sleep(self.LENGTH + 0.3)
        self.assertEqual(m.calls, ['arm', 'disarm'])
        self.assertEqual(alarms.active, {})

    def test_new_alarm_extends_with_one_timer(self):
        alarms, m = self.alarms(), FakeMonitor()
        alarms.trigger(3, m)
        time.sleep(self.LENGTH * 0.6)
        self.assertFalse(alarms.trigger(3, m))
        time.sleep(self.LENGTH * 0.6)
        # Armed again (it may have been cancelled from the web), not disarmed
        # by the first alarm's timer.
        self.assertEqual(m.calls, ['arm', 'arm'], 'cancelled before the extended deadline')
        time.sleep(self.LENGTH)
        self.assertEqual(m.calls, ['arm', 'arm', 'disarm'])

    def test_stop_cancels_running_alarms(self):
        alarms, m3, m4 = self.alarms(), FakeMonitor(), FakeMonitor()
        alarms.trigger(3, m3)
        alarms.trigger(4, m4)
        alarms.stop()
        self.assertEqual((m3.calls, m4.calls), (['arm', 'disarm'], ['arm', 'disarm']))
        time.sleep(self.LENGTH + 0.3)
        self.assertEqual((m3.calls, m4.calls), (['arm', 'disarm'], ['arm', 'disarm']), 'disarmed twice')

    def test_no_alarm_after_stop(self):
        alarms, m = self.alarms(), FakeMonitor()
        alarms.stop()
        self.assertFalse(alarms.trigger(3, m))
        self.assertEqual(m.calls, [])

    def test_arm_failure_is_not_tracked(self):
        alarms, m = self.alarms(), FakeMonitor(fail_arm=True)
        self.assertFalse(alarms.trigger(3, m))
        self.assertEqual(alarms.active, {})

    def test_monitor_already_in_alarm_left_alone(self):
        alarms, m = self.alarms(alarmed=True), FakeMonitor()
        self.assertFalse(alarms.trigger(3, m))
        self.assertEqual(m.calls, [])

    def test_sigterm_handler_exits(self):
        with self.assertRaises(SystemExit):
            als.terminate(15, None)


class MonitorAddress(unittest.TestCase):
    def test_ip_in_path(self):
        self.assertEqual(als.monitor_address('rtsp://u:p@192.168.1.10:554/x'), CAMERA)

    def test_no_address(self):
        self.assertIsNone(als.monitor_address(''))
        self.assertIsNone(als.monitor_address(None))
        self.assertIsNone(als.monitor_address('/dev/video0'))


if __name__ == '__main__':
    unittest.main()
