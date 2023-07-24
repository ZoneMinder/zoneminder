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



# Adds pyzm support
import pyzm.api as zmapi
import pyzm.ZMLog as zmlog
import pyzm.helpers.utils as utils

import os, sys, struct, json
from time import sleep
#import time
from socket import *
# from datetime import *
# telnet
from telnetlib import Telnet
# multi threading
from threading import Thread

def writezmlog(m,s):
    zmlog.init()
    zmlog.Info(m+s)
#    zmlog.close()

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


def event_thread(m_id,eventlenght):
    import subprocess
    print("Monitor " + str(m_id)+" entered event_thread...")
    result = subprocess.run(['zmu','-m', str(m_id), '-x'] ,stdout=subprocess.PIPE)
    if result.stdout.decode('utf-8') == '0\n':
        print('Firing monitor '+ str(m_id) + ' trigger...')
        telbuff = str(m_id) + '|on+'+str(eventlenght)+'|1|Human Motion Detected|'
        with Telnet('localhost', 6802) as tn:
          tn.write(telbuff.encode('ascii') + alarm_desc.encode('ascii') + b'\n')
          tn.read_until(b'off')
    else:
        print('Monitor '+ str(m_id) + ' already triggered, doing nothing...')
    print('Finishing thread...')



def tolog(s):
    logfile = open(datetime.now().strftime('%Y_%m_%d_') + log, 'a+')
    logfile.write(s)
    logfile.close()


def GetIP(s):
    return inet_ntoa(struct.pack('<I', int(s, 16)))


# config variables
eventlenght = 60
wrzmlog = 'n'
wrzmevent ='n'
rsealm = 'n'
port = '15002'

if len(sys.argv) > 1:
    keys = ["--log=","-l=","--alarm=","-a=","--port=","-p=","--event=","-e="]
    for i in range(1,len(sys.argv)):
        for key in keys:
            if sys.argv[i].find(key) == 0:
                if  key == "--log=" or key == "-l=":
                     wrzmlog=sys.argv[i][len(key):]
                elif key == "--alarm=" or key == "-a=":
                     rsealm=sys.argv[i][len(key):]
                elif key == "--port=" or key == "-p=":
                     port=sys.argv[i][len(key):]
                elif key == "--event=" or key == "-e=":
                     wrzmevent=sys.argv[i][len(key):]
                break

else:
    print('Usage: %s [--port|-p=<value> --log|-l=<y/n> --alarm|-a=<y/n> --event|-e=<y/n>]' % os.path.basename(sys.argv[0]))
    sys.exit(1) 

print ('Create log entry: ', wrzmlog)
print ('Trigger event: ', wrzmlog)
print ('Raise Alarm: ', rsealm)

server = socket(AF_INET, SOCK_STREAM)
server.bind(('0.0.0.0', int(port)))
# server.settimeout(0.5)
server.listen(1)

log = "AlarmServer.log"

conf = utils.read_config('/etc/zm/secrets.ini')
api_options  = {
    'apiurl': utils.get(key='ZM_API_PORTAL', section='secrets', conf=conf),
    'portalurl':utils.get(key='ZM_PORTAL', section='secrets', conf=conf),
    'user': utils.get(key='ZM_USER', section='secrets', conf=conf),
    #'disable_ssl_cert_check': True
}

zmapi = zmapi.ZMApi(options=api_options)

# importing the regex to get ip out of path
import re
#define regex pattern for IP addresses
pattern =re.compile('''((25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)''')
# store the response of URL
#process monitors create dict of monitors
list_monit = {}
zm_monitors = zmapi.monitors()
for m in zm_monitors.list():
	ip_v4=pattern.search(m.get()['Path'])
	list_monit[ip_v4.group()]=m.id()

writezmlog('Listening on port: '+port,' AlarmServer.py')
print ('Listening on port: '+port)
#run Alarm Server
while True:
    try:
        conn, addr = server.accept()
        head, version, session, sequence_number, msgid, len_data = struct.unpack(
            'BB2xII2xHI', conn.recv(20)
        )
        sleep(0.1)  # Just for receive whole packet
        data = conn.recv(len_data)
        conn.close()
        # make the json a Dictionary
        reply = json.loads(data)
        # get ip
        ip_v4 = GetIP(reply.get('Address'))
        # get alarm_event_desc
        alarm_desc = reply.get('Event')
#        print(datetime.now().strftime('[%Y-%m-%d %H:%M:%S]>>>'))
        print ('Ip Address: ',ip_v4)
        print ("Alarm Description: ", alarm_desc)
        print('<<<')
#        tolog(repr(data) + "\r\n")
        if alarm_desc == 'HumanDetect':
            if wrzmlog == 'y':
               writezmlog(alarm_desc+' in monitor ',str(list_monit[ip_v4]))
            if rsealm == 'y':
               print ("Triggering Alarm...")
               mthread = Thread(target=alarm_thread, args=(zm_monitors.find(list_monit[ip_v4]),list_monit[ip_v4],eventlenght))
               mthread.start()
            elif wrzmevent == 'y':
               print ("Triggering Event Rec on zmtrigger...")
               mthread = Thread(target=event_thread, args=(list_monit[ip_v4],eventlenght))
               mthread.start()
    except (KeyboardInterrupt, SystemExit):
        break
server.close()
# needs to be closed again... otherwise it will crash on exit.
zmlog.close()
sys.exit(1)
