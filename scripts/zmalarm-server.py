#!/usr/bin/env python3
# -*- coding: utf-8 -*-

# ==========================================================================
#
# ZoneMinder Alarm Server Script for Netsurveillence Software cameras, $Date$, $Revision$
# Copyright (C) 2022
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
#
# ==========================================================================



# Alarms are accepted only from an address that belongs to a monitor: the
# camera address in its Path, or one listed for it in /etc/zm/secrets.ini:
#
#   [alarmserver]
#   monitor_5 = 203.0.113.7 10.0.0.5
#
# Every address listed is trusted to send alarms for that monitor. A camera
# behind NAT whose Path holds the NAT address needs no entry. Several
# cameras behind one NAT address each need the LAN address they report in
# their packets listed for their monitor, or their alarms go to the last of
# those monitors.

import os, sys, struct, json, re, signal
from time import sleep, monotonic
from socket import *
from urllib.parse import urlsplit
# multi threading
from threading import Thread, Lock, Event

# Header of a NETSurveillance alarm packet: head, version, session,
# sequence number, message id, length of the JSON body that follows.
HEADER_FORMAT = 'BB2xII2xHI'
HEADER_SIZE = struct.calcsize(HEADER_FORMAT)
MAX_BODY = 65536
RECV_TIMEOUT = 5.0
ZMTRIGGER_PORT = 6802

IPV4_PATTERN = re.compile(r'((25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)')


def writezmlog(m,s):
    import pyzm.ZMLog as zmlog
    zmlog.init()
    zmlog.Info(m+s)


def already_alarmed(monid):
    import subprocess
    result = subprocess.run(['zmu','-m', str(monid), '-s'] ,stdout=subprocess.PIPE)
    return result.stdout.decode('utf-8') == '3\n'


class ForcedAlarms:
    """The forced alarms this server raised, one per monitor.

    Each ends length seconds after the last alarm packet for its monitor. The
    server used to arm, sleep and disarm in a thread, so when zmdc stopped or
    restarted it inside that window the disarm never ran and the monitor
    stayed in forced alarm, recording, until someone cancelled it. stop()
    cancels every alarm still running; main() calls it on the way out,
    SIGTERM included.
    """

    def __init__(self, length, already_alarmed=already_alarmed):
        self.length = length
        self.already_alarmed = already_alarmed
        self.lock = Lock()
        self.stopping = Event()
        self.active = {}  # monitor id -> [monitor, deadline]

    def trigger(self, monid, monitor):
        """Raise a forced alarm on monitor, or extend the one running. True if raised."""
        with self.lock:
            if self.stopping.is_set():
                return False
            if monid in self.active:
                # Arm again, as every alarm packet did: the forced alarm may
                # have been cancelled from the web meanwhile.
                self.active[monid][1] = monotonic() + self.length
                print('Monitor %s: forced alarm extended' % monid)
                try:
                    monitor.arm()
                except Exception as e:
                    print('Monitor %s: arm failed: %s' % (monid, e))
                return False
            if self.already_alarmed(monid):
                print('Monitor %s already in status Alarm...' % monid)
                return False
            # Recorded before arming, so stop() also cancels an alarm whose
            # arm() was interrupted.
            self.active[monid] = [monitor, monotonic() + self.length]
            print('Changing monitor %s status to Alarm...' % monid)
            try:
                monitor.arm()
            except Exception as e:
                del self.active[monid]
                print('Monitor %s: arm failed: %s' % (monid, e))
                return False
        Thread(target=self._expire, args=(monid,), daemon=True).start()
        return True

    def _expire(self, monid):
        while True:
            with self.lock:
                entry = self.active.get(monid)
                if entry is None:
                    return
                left = entry[1] - monotonic()
                if left <= 0:
                    del self.active[monid]
                    self._disarm(monid, entry[0])
                    return
            self.stopping.wait(left)

    def _disarm(self, monid, monitor):
        try:
            monitor.disarm()
            print('Monitor %s: forced alarm cancelled' % monid)
        except Exception as e:
            print('Monitor %s: disarm failed: %s' % (monid, e))

    def stop(self):
        """Cancel every forced alarm still running; refuse new ones."""
        self.stopping.set()
        with self.lock:
            for monid, (monitor, deadline) in list(self.active.items()):
                self._disarm(monid, monitor)
            self.active.clear()


def terminate(signum, frame):
    raise SystemExit(0)


def event_thread(m_id,eventlenght,alarm_desc):
    import subprocess
    print("Monitor " + str(m_id)+" entered event_thread...")
    result = subprocess.run(['zmu','-m', str(m_id), '-x'] ,stdout=subprocess.PIPE)
    if result.stdout.decode('utf-8') == '0\n':
        print('Firing monitor '+ str(m_id) + ' trigger...')
        telbuff = str(m_id) + '|on+'+str(eventlenght)+'|1|Human Motion Detected|' + alarm_desc + '\n'
        with create_connection(('localhost', ZMTRIGGER_PORT)) as tn:
            tn.sendall(telbuff.encode('ascii', 'replace'))
            buf = b''
            while b'off' not in buf:
                d = tn.recv(4096)
                if not d:
                    break
                buf += d
    else:
        print('Monitor '+ str(m_id) + ' already triggered, doing nothing...')
    print('Finishing thread...')


def GetIP(s):
    return inet_ntoa(struct.pack('<I', int(s, 16)))


def monitor_address(path):
    """IPv4 address of the camera a monitor Path points at, or None."""
    if not path:
        return None
    try:
        host = urlsplit(path).hostname
    except ValueError:
        host = None
    if host:
        try:
            return gethostbyname(host)
        except (OSError, UnicodeError):
            pass
    match = IPV4_PATTERN.search(path)
    return match.group() if match else None


def recv_exact(conn, size):
    """Read exactly size bytes; raise ValueError if the peer sends fewer."""
    data = b''
    while len(data) < size:
        chunk = conn.recv(size - len(data))
        if not chunk:
            raise ValueError('connection closed after %d of %d bytes' % (len(data), size))
        data += chunk
    return data


def parse_header(header):
    """Body length announced by a packet header; raise ValueError if it is not usable."""
    if len(header) != HEADER_SIZE:
        raise ValueError('short header')
    head, version, session, sequence_number, msgid, len_data = struct.unpack(HEADER_FORMAT, header)
    if len_data == 0 or len_data > MAX_BODY:
        raise ValueError('body length %d out of range' % len_data)
    return len_data


def parse_alarm(data, source_ip, list_monit):
    """Return (monitor id, event) for an alarm body sent from source_ip.

    The packet's Address field carries no credential: any host can write any
    address into it. What identifies the camera is the address the packet came
    from, so the alarm goes to a monitor that source_ip belongs to, never to
    one it does not. list_monit maps an address to the ids of the monitors
    that use it (camera address in the Path, or a configured alarm source).

    The Address field only chooses among the monitors of source_ip, for
    several cameras behind one address. A camera behind NAT reports its own
    LAN address there while its packets arrive from the NAT address, so a
    mismatch is accepted as long as source_ip itself belongs to a monitor.

    Raise ValueError when the body is not a JSON object with Address and
    Event, or when source_ip belongs to no monitor.
    """
    try:
        reply = json.loads(data)
    except (ValueError, UnicodeDecodeError) as e:
        raise ValueError('body is not JSON: %s' % e)
    if not isinstance(reply, dict):
        raise ValueError('body is not a JSON object')
    address = reply.get('Address')
    alarm_desc = reply.get('Event')
    if not isinstance(address, str) or not isinstance(alarm_desc, str):
        raise ValueError('Address or Event missing')
    try:
        ip_v4 = GetIP(address)
    except (ValueError, struct.error, OverflowError):
        raise ValueError('Address %r is not an IPv4 address' % address)
    senders = list_monit.get(source_ip, [])
    if not senders:
        raise ValueError('no monitor uses sender address %s (packet Address %s)' % (source_ip, ip_v4))
    claimed = [m for m in senders if m in list_monit.get(ip_v4, [])]
    # Several monitors on one address: the last one listed, as before.
    return (claimed or senders)[-1], alarm_desc


def alarm_sources(conf):
    """Extra addresses per monitor id from the [alarmserver] section of
    secrets.ini: monitor_<id> = <address> [<address> ...]. A monitor's alarms
    are accepted from these as well as from the camera address in its Path:
    the NAT address its packets arrive from, or the LAN address a camera
    behind a shared NAT address reports in its packets."""
    sources = {}
    try:
        items = conf.items('alarmserver')
    except Exception:
        return sources
    for key, value in items:
        match = re.fullmatch(r'monitor_(\d+)', key.strip().lower())
        if not match:
            continue
        for host in value.replace(',', ' ').split():
            try:
                ip = gethostbyname(host)
            except (OSError, UnicodeError):
                print('[alarmserver] %s: cannot resolve %s, skipped' % (key, host))
                continue
            sources.setdefault(int(match.group(1)), []).append(ip)
    return sources


def map_monitors(monitors, sources):
    """address -> ids of the monitors that use it, in listing order."""
    list_monit = {}
    for monid, path in monitors:
        addresses = []
        ip_v4 = monitor_address(path)
        if ip_v4 is not None:
            addresses.append(ip_v4)
        addresses += [ip for ip in sources.get(monid, []) if ip not in addresses]
        if not addresses:
            print('Monitor %s: no camera address in its Path and no alarm source, skipped' % monid)
        for ip in addresses:
            list_monit.setdefault(ip, []).append(monid)
    return list_monit


def main(argv):
    import pyzm.api as zmapi
    import pyzm.ZMLog as zmlog
    import pyzm.helpers.utils as utils

    # config variables
    eventlenght = 60
    wrzmlog = 'n'
    wrzmevent ='n'
    rsealm = 'n'
    port = '15002'
    bind_address = '0.0.0.0'

    if len(argv) > 1:
        keys = ["--log=","-l=","--alarm=","-a=","--port=","-p=","--event=","-e=","--bind=","-b="]
        for i in range(1,len(argv)):
            if argv[i].isdigit():
                # zmpkg.pl passes ZM_OPT_ALS_PORT as a bare number
                port = argv[i]
                continue
            for key in keys:
                if argv[i].find(key) == 0:
                    if  key == "--log=" or key == "-l=":
                         wrzmlog=argv[i][len(key):]
                    elif key == "--alarm=" or key == "-a=":
                         rsealm=argv[i][len(key):]
                    elif key == "--port=" or key == "-p=":
                         port=argv[i][len(key):]
                    elif key == "--event=" or key == "-e=":
                         wrzmevent=argv[i][len(key):]
                    elif key == "--bind=" or key == "-b=":
                         bind_address=argv[i][len(key):]
                    break
    else:
        print('Usage: %s [<port>] [--port|-p=<value> --bind|-b=<address> --log|-l=<y/n> --alarm|-a=<y/n> --event|-e=<y/n>]' % os.path.basename(argv[0]))
        return 1

    print ('Create log entry: ', wrzmlog)
    print ('Trigger event: ', wrzmevent)
    print ('Raise Alarm: ', rsealm)

    conf = utils.read_config('/etc/zm/secrets.ini')
    api_options  = {
        'apiurl': utils.get(key='ZM_API_PORTAL', section='secrets', conf=conf),
        'portalurl':utils.get(key='ZM_PORTAL', section='secrets', conf=conf),
        'user': utils.get(key='ZM_USER', section='secrets', conf=conf),
        #'disable_ssl_cert_check': True
    }

    api = zmapi.ZMApi(options=api_options)

    # map camera address -> monitor ids
    zm_monitors = api.monitors()
    list_monit = map_monitors([(m.id(), m.get().get('Path')) for m in zm_monitors.list()],
                              alarm_sources(conf))

    server = socket(AF_INET, SOCK_STREAM)
    server.setsockopt(SOL_SOCKET, SO_REUSEADDR, 1)
    server.bind((bind_address, int(port)))
    server.listen(5)

    writezmlog('Listening on '+bind_address+':'+port,' AlarmServer.py')
    print ('Listening on '+bind_address+':'+port)
    alarms = ForcedAlarms(eventlenght)
    # zmdc stops its daemons with SIGTERM; leave through the finally below.
    signal.signal(signal.SIGTERM, terminate)
    #run Alarm Server
    try:
        serve(server, list_monit, zm_monitors, alarms, wrzmlog, rsealm, wrzmevent, eventlenght)
    finally:
        signal.signal(signal.SIGTERM, signal.SIG_IGN)
        alarms.stop()
        server.close()
    # needs to be closed again... otherwise it will crash on exit.
    zmlog.close()
    return 0


def serve(server, list_monit, zm_monitors, alarms, wrzmlog, rsealm, wrzmevent, eventlenght):
    while True:
        try:
            conn, addr = server.accept()
        except (KeyboardInterrupt, SystemExit):
            break
        try:
            conn.settimeout(RECV_TIMEOUT)
            len_data = parse_header(recv_exact(conn, HEADER_SIZE))
            data = recv_exact(conn, len_data)
            monid, alarm_desc = parse_alarm(data, addr[0], list_monit)
        except (KeyboardInterrupt, SystemExit):
            break
        except (OSError, ValueError) as e:
            print('Ignoring packet from %s: %s' % (addr[0], e))
            continue
        finally:
            conn.close()
        print ('Ip Address: ',addr[0])
        print ("Alarm Description: ", alarm_desc)
        print('<<<')
        if alarm_desc == 'HumanDetect':
            if wrzmlog == 'y':
               writezmlog(alarm_desc+' in monitor ',str(monid))
            if rsealm == 'y':
               print ("Triggering Alarm...")
               alarms.trigger(monid, zm_monitors.find(monid))
            elif wrzmevent == 'y':
               print ("Triggering Event Rec on zmtrigger...")
               mthread = Thread(target=event_thread, args=(monid,eventlenght,alarm_desc))
               mthread.start()


if __name__ == '__main__':
    sys.exit(main(sys.argv))
