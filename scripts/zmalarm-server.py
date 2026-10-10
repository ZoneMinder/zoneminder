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



import os, sys, struct, json, re
from time import sleep
from socket import *
from urllib.parse import urlsplit
# multi threading
from threading import Thread

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


def alarm_thread(m, monid,eventlenght):
    import subprocess
    print("Monitor " + str(monid)+" entered alarm_thread...")
    result = subprocess.run(['zmu','-m', str(monid), '-s'] ,stdout=subprocess.PIPE)
    if result.stdout.decode('utf-8') != '3\n':
        print('Changing monitor '+ str(monid) + ' status to Alarm...')
        m.arm()
        sleep(eventlenght)
        m.disarm()
    else:
        print('Monitor '+ str(monid) + ' already in status Alarm...')
    print('Finishing thread...')


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

    Raise ValueError when the body is not a JSON object with Address and
    Event, when its Address is not the address it was sent from, or when no
    monitor uses that address.
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
    if ip_v4 != source_ip:
        raise ValueError('Address %s does not match sender %s' % (ip_v4, source_ip))
    if ip_v4 not in list_monit:
        raise ValueError('no monitor uses address %s' % ip_v4)
    return list_monit[ip_v4], alarm_desc


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

    # map camera address -> monitor id
    list_monit = {}
    zm_monitors = api.monitors()
    for m in zm_monitors.list():
        ip_v4 = monitor_address(m.get().get('Path'))
        if ip_v4 is None:
            print('Monitor %s: no camera address in its Path, skipped' % m.id())
            continue
        list_monit[ip_v4]=m.id()

    server = socket(AF_INET, SOCK_STREAM)
    server.setsockopt(SOL_SOCKET, SO_REUSEADDR, 1)
    server.bind((bind_address, int(port)))
    server.listen(5)

    writezmlog('Listening on '+bind_address+':'+port,' AlarmServer.py')
    print ('Listening on '+bind_address+':'+port)
    #run Alarm Server
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
               mthread = Thread(target=alarm_thread, args=(zm_monitors.find(monid),monid,eventlenght))
               mthread.start()
            elif wrzmevent == 'y':
               print ("Triggering Event Rec on zmtrigger...")
               mthread = Thread(target=event_thread, args=(monid,eventlenght,alarm_desc))
               mthread.start()
    server.close()
    # needs to be closed again... otherwise it will crash on exit.
    zmlog.close()
    return 1


if __name__ == '__main__':
    sys.exit(main(sys.argv))
