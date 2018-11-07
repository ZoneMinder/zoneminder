API
====

This document will provide an overview of ZoneMinder's API. This is work in progress. 

Overview
^^^^^^^^
In an effort to further 'open up' ZoneMinder, an API was needed.  This will
allow quick integration with and development of ZoneMinder.

The API is built in CakePHP and lives under the ``/api`` directory.  It
provides a RESTful service and supports CRUD (create, retrieve, update, delete)
functions for Monitors, Events, Frames, Zones and Config.

Streaming Interface
^^^^^^^^^^^^^^^^^^^
Developers working on their application often ask if there is an "API" to receive live streams, or recorded event streams.
It is possible to stream both live and recorded streams. This isn't strictly an "API" per-se (that is, it is not integrated
into the Cake PHP based API layer discussed here) and also why we've used the term "Interface" instead of an "API".

Live Streams
~~~~~~~~~~~~~~
What you need to know is that if you want to display "live streams", ZoneMinder sends you streaming JPEG images (MJPEG)
which can easily be rendered in a browser using an ``img src`` tag.

For example:

::

    <img src="https://yourserver/zm/cgi-bin/nph-zms?scale=50&width=640p&height=480px&mode=jpeg&maxfps=5&buffer=1000&&monitor=1&auth=b54a589e09f330498f4ae2203&connkey=36139" />

will display a live feed from monitor id 1, scaled down by 50% in quality and resized to 640x480px. 

* This assumes ``/zm/cgi-bin`` is your CGI_BIN path. Change it to what is correct in your system
* The "auth" token you see above is required if you use ZoneMinder authentication. To understand how to get the auth token, please read the "Login, Logout & API security" section below.
* The "connkey" parameter is essentially a random number which uniquely identifies a stream. If you don't specify a connkey, ZM will generate its own. It is recommended to generate a connkey because you can then use it to "control" the stream (pause/resume etc.)
* Instead of dealing with the "auth" token, you can also use ``&user=username&pass=password`` where "username" and "password" are your ZoneMinder username and password respectively. Note that this is not recommended because you are transmitting them in a URL and even if you use HTTPS, they may show up in web server logs.


PTZ on live streams
-------------------
PTZ commands are pretty cryptic in ZoneMinder. This is not meant to be an exhaustive guide, but just something to whet your appetite:


Lets assume you have a monitor, with ID=6. Let's further assume you want to pan it left.

You'd need to send a:
``POST`` command to ``https://yourserver/zm/index.php`` with the following data payload in the command (NOT in the URL)

``view=request&request=control&id=6&control=moveConLeft&xge=30&yge=30``

Obviously, if you are using authentication, you need to be logged in for this to work.

Like I said, at this stage, this is only meant to get you started. Explore the ZoneMinder code and use "Inspect source" as you use PTZ commands in the ZoneMinder source code.
`control_functions.php <https://github.com/ZoneMinder/zoneminder/blob/10531df54312f52f0f32adec3d4720c063897b62/web/skins/classic/includes/control_functions.php>`__ is a great place to start.


Pre-recorded (past event) streams
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Similar to live playback, if you have chosen to store events in JPEG mode, you can play it back using:

::

    <img src="https://yourserver/zm/cgi-bin/nph-zms?mode=jpeg&frame=1&replay=none&source=event&event=293820&connkey=77493&auth=b54a58f5f4ae2203" />


* This assumes ``/zm/cgi-bin`` is your CGI_BIN path. Change it to what is correct in your system
* This will playback event 293820, starting from frame 1 as an MJPEG stream
* Like before, you can add more parameters like ``scale`` etc. 
* auth and connkey have the same meaning as before, and yes, you can replace auth by ``&user=usename&pass=password`` as before and the same security concerns cited above apply.

If instead, you have chosen to use the MP4 (Video) storage mode for events, you can directly play back the saved video file:

::
   
    <video src="https://yourserver/zm/index.php?view=view_video&eid=294690&auth=33f3d558af84cf08" type="video/mp4"></video>

* This will play back the video recording for event 294690

What other parameters are supported?
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
The best way to answer this question is to play with ZoneMinder console. Open a browser, play back live or recorded feed, and do an "Inspect Source" to see what parameters 
are generated. Change and observe.


Enabling API
^^^^^^^^^^^^
A default ZoneMinder installs with APIs enabled. You can explictly enable/disable the APIs
via the Options->System menu by enabling/disabling ``OPT_USE_API``. Note that if you intend
to use APIs with 3rd party apps, such as zmNinja or others that use APIs, you should also
enable ``AUTH_HASH_LOGINS``.

Login, Logout & API Security
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
The APIs tie into ZoneMinder's existing security model. This means if you have
OPT_AUTH enabled, you need to log into ZoneMinder using the same browser you plan to 
use the APIs from. If you are developing an app that relies on the API, you need 
to do a POST login from the app into ZoneMinder before you can access the API.

Then, you need to re-use the authentication information of the login (returned as cookie states)
with subsequent APIs for the authentication information to flow through to the APIs.

This means if you plan to use cuRL to experiment with these APIs, you first need to login:

**Login process for ZoneMinder v1.32.0 and above**

::

    curl -XPOST -d "user=XXXX&pass=YYYY" -c cookies.txt  http://yourzmip/zm/api/host/login.json

Staring ZM 1.32.0, you also have a `logout` API that basically clears your session. It looks like this:

::

    curl -b cookies.txt  http://yourzmip/zm/api/host/logout.json


**Login process for older versions of ZoneMinder**

::

    curl -d "username=XXXX&password=YYYY&action=login&view=console" -c cookies.txt  http://yourzmip/zm/index.php

The equivalent logout process for older versions of ZoneMinder is:

::

    curl -XPOST -d "username=XXXX&password=YYYY&action=logout&view=console" -b cookies.txt  http://yourzmip/zm/index.php

replacing *XXXX* and *YYYY* with your username and password, respectively.

Please make sure you do this in a directory where you have write permissions, otherwise cookies.txt will not be created
and the command will silently  fail.


What the "-c cookies.txt" does is store a cookie state reflecting that you have logged into ZM. You now need
to apply that cookie state to all subsequent APIs. You do that by using a '-b cookies.txt' to subsequent APIs if you are
using CuRL like so:

::

    curl -b cookies.txt http://yourzmip/zm/api/monitors.json

This would return a list of monitors and pass on the authentication information to the ZM API layer.

A deeper dive into the login process
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

As you might have seen above, there are two ways to login, one that uses the `login.json` API and the other that logs in using the ZM portal. If you are running ZoneMinder 1.32.0 and above, it is *strongly* recommended you use the `login.json` approach. The "old" approach will still work but is not as powerful as the API based login. Here are the reasons why:

 * The "old" approach basically uses the same login webpage (`index.php`) that a user would log into when viewing the ZM console. This is not really using an API and more importantly, if you have additional components like reCAPTCHA enabled, this will not work. Using the API approach is much cleaner and will work irrespective of reCAPTCHA

 * The new login API returns important information that you can use to stream videos as well, right after login. Consider for example, a typical response to the login API (`/login.json`):

::

    {
        "credentials": "auth=f5b9cf48693fe8552503c8ABCD5",
        "append_password": 0,
        "version": "1.31.44",
        "apiversion": "1.0"
    } 

In this example I have `OPT_AUTH` enabled in ZoneMinder and it returns my credential key. You can then use this key to stream images like so:

::

    <img src="https://server/zm/cgi-bin/nph-zms?monitor=1&auth=<authval>" />

Where `authval` is the credentials returned to start streaming videos.

The `append_password` field will contain 1 when it is necessary for you to append your ZM password. This is the case when you set `AUTH_RELAY` in ZM options to "plain", for example. In that case, the `credentials` field may contain something like `&user=admin&pass=` and you have to add your password to that string.


.. NOTE:: It is recommended you invoke the `login` API once every 60 minutes to make sure the session stays alive. The same is true if you use the old login method too.



Examples (please read security notice above)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Please remember, if you are using authentication, please add a ``-b cookies.txt``  to each of the commands below if you are using
CuRL. If you are not using CuRL and writing your own app, you need to make sure you pass on cookies to subsequent requests
in your app.


(In all examples, replace 'server' with IP or hostname & port where ZoneMinder is running)

API Version
^^^^^^^^^^^
To retrieve the API version:

::

  curl http://server/zm/api/host/getVersion.json


Return a list of all monitors
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

::
  
	curl http://server/zm/api/monitors.json

It is worthwhile to note that starting ZM 1.32.3 and beyond, this API also returns a ``Monitor_Status`` object per monitor. It looks like this:

::

        "Monitor_Status": {
                "MonitorId": "2",
                "Status": "Connected",
                "CaptureFPS": "1.67",
                "AnalysisFPS": "1.67",
                "CaptureBandwidth": "52095"
            }


If you don't see this in your API, you are running an older version of ZM. This gives you a very convenient way to check monitor status without calling the ``daemonCheck`` API described later.


Retrieve monitor 1
^^^^^^^^^^^^^^^^^^^

::
  
  	curl http://server/zm/api/monitors/1.json


Change State of Monitor 1
^^^^^^^^^^^^^^^^^^^^^^^^^^

This API changes monitor 1 to Modect and Enabled

::

  curl -XPOST http://server/zm/api/monitors/1.json -d "Monitor[Function]=Modect&Monitor[Enabled]=1"
  
Get Daemon Status of Monitor 1
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

::

  	curl http://server/zm/api/monitors/daemonStatus/id:1/daemon:zmc.json

Add a monitor
^^^^^^^^^^^^^^

This command will add a new http monitor.

::

  curl -XPOST http://server/zm/api/monitors.json -d "Monitor[Name]=Cliff-Burton\
  &Monitor[Function]=Modect\
  &Monitor[Protocol]=http\
  &Monitor[Method]=simple\
  &Monitor[Host]=usr:pass@192.168.11.20\
  &Monitor[Port]=80\
  &Monitor[Path]=/mjpg/video.mjpg\
  &Monitor[Width]=704\
  &Monitor[Height]=480\
  &Monitor[Colours]=4"

Edit monitor 1
^^^^^^^^^^^^^^^

This command will change the 'Name' field of Monitor 1 to 'test1'

::

  curl -XPUT http://server/zm/api/monitors/1.json -d "Monitor[Name]=test1"


Delete monitor 1
^^^^^^^^^^^^^^^^^

This command will delete Monitor 1, but will _not_ delete any Events which
depend on it.

::

  curl -XDELETE http://server/zm/api/monitors/1.json


Arm/Disarm monitors
^^^^^^^^^^^^^^^^^^^^

This command will force an alarm on Monitor 1:

::

  curl http://server/zm/api/monitors/alarm/id:1/command:on.json

This command will disable the  alarm on Monitor 1:

::

  curl http://server/zm/api/monitors/alarm/id:1/command:off.json

This command will report the status of the alarm  Monitor 1:

::

  curl http://server/zm/api/monitors/alarm/id:1/command:status.json


Return a list of all events
^^^^^^^^^^^^^^^^^^^^^^^^^^^^

::

  http://server/zm/api/events.json


Note that events list can be quite large and this API (as with all other APIs in ZM)
uses pagination. Each page returns a specific set of entries. By default this is 25
and ties into WEB_EVENTS_PER_PAGE in the ZM options menu. 

So the logic to iterate through all events should be something like this (pseudocode):
(unfortunately there is no way to get pageCount without getting the first page)

::

  data = http://server/zm/api/events.json?page=1 # this returns the first page
  # The json object returned now has a property called data.pagination.pageCount
  count = data.pagination.pageCount;
  for (i=1, i<count, i++)
  {
    data = http://server/zm/api/events.json?page=i;
     doStuff(data);
  }


Retrieve event Id 1000
^^^^^^^^^^^^^^^^^^^^^^

::

  curl -XGET http://server/zm/api/events/1000.json


Edit event 1
^^^^^^^^^^^^^

This command will change the 'Name' field of Event 1 to 'Seek and Destroy'

::

  curl -XPUT http://server/zm/api/events/1.json -d "Event[Name]=Seek and Destroy"

Delete event 1
^^^^^^^^^^^^^^
This command will delete Event 1, and any Frames which depend on it.

::

  curl -XDELETE http://server/zm/api/events/1.json


Return a list of events for a specific monitor Id =5
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
::

  curl -XGET http://server/zm/api/events/index/MonitorId:5.json


Note that the same pagination logic applies if the list is too long


Return a list of events for a specific monitor within a specific date/time range
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

::

  http://server/zm/api/events/index/MonitorId:5/StartTime >=:2015-05-15 18:43:56/EndTime <=:2015-05-16 18:43:56.json


To try this in CuRL, you need to URL escape the spaces like so:

::

  curl -XGET  "http://server/zm/api/events/index/MonitorId:5/StartTime%20>=:2015-05-15%2018:43:56/EndTime%20<=:2015-05-16%2018:43:56.json"


Return a list of events for all monitors within a specified date/time range
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

::

  curl -XGET "http://server/zm/api/events/index/StartTime%20>=:2015-05-15%2018:43:56/EndTime%20<=:208:43:56.json"


Return event count based on times and conditions
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

The API also supports a handy mechanism to return a count of events for a period of time.

This returns number of events per monitor that were recorded in the last one hour

::

  curl "http://server/zm/api/events/consoleEvents/1%20hour.json"

This returns number of events per monitor that were recorded in the last day where there were atleast 10 frames that were alarms"

::

  curl "http://server/zm/api/events/consoleEvents/1%20day.json/AlarmFrames >=: 10.json"





Configuration Apis
^^^^^^^^^^^^^^^^^^^

The APIs allow you to access all the configuration parameters of ZM that you typically set inside the web console.
This returns the full list of configuration parameters:

::

  curl -XGET http://server/zm/api/configs.json


Each configuration parameter has an Id, Name, Value and other fields. Chances are you are likely only going to focus on these 3.

The edit function of the Configs API is a little quirky at the moment. Its format deviates from the usual edit flow of other APIs. This will be fixed, eventually. For now, to change the "Value" of ZM_X10_HOUSE_CODE from A to B:

::

    curl -XPUT http://server/zm/api/configs/edit/ZM_X10_HOUSE_CODE.json  -d "Config[Value]=B"

To validate changes have been made:

::

    curl -XGET http://server/zm/api/configs/view/ZM_X10_HOUSE_CODE.json 

Run State Apis
^^^^^^^^^^^^^^^

ZM API can be used to start/stop/restart/list states of  ZM as well
Examples:

::

  curl -XGET  http://server/zm/api/states.json # returns list of run states
  curl -XPOST  http://server/zm/api/states/change/restart.json #restarts ZM
  curl -XPOST  http://server/zm/api/states/change/stop.json #Stops ZM
  curl -XPOST  http://server/zm/api/states/change/start.json #Starts ZM



Create a Zone
^^^^^^^^^^^^^^

::

  curl -XPOST http://server/zm/api/zones.json -d "Zone[Name]=Jason-Newsted\
  &Zone[MonitorId]=3\
  &Zone[Type]=Active\
  &Zone[Units]=Percent\
  &Zone[NumCoords]=4\
  &Zone[Coords]=0,0 639,0 639,479 0,479\
  &Zone[AlarmRGB]=16711680\
  &Zone[CheckMethod]=Blobs\
  &Zone[MinPixelThreshold]=25\
  &Zone[MaxPixelThreshold]=\
  &Zone[MinAlarmPixels]=9216\
  &Zone[MaxAlarmPixels]=\
  &Zone[FilterX]=3\
  &Zone[FilterY]=3\
  &Zone[MinFilterPixels]=9216\
  &Zone[MaxFilterPixels]=230400\
  &Zone[MinBlobPixels]=6144\
  &Zone[MaxBlobPixels]=\
  &Zone[MinBlobs]=1\
  &Zone[MaxBlobs]=\
  &Zone[OverloadFrames]=0"

PTZ Control Meta-Data APIs
^^^^^^^^^^^^^^^^^^^^^^^^^^^
PTZ controls associated with a monitor are stored in the Controls table and not the Monitors table inside ZM. What that means is when you get the details of a Monitor, you will only know if it is controllable (isControllable:true) and the control ID.
To be able to retrieve PTZ information related to that Control ID, you need to use the controls API

Note that these APIs only retrieve control data related to PTZ. They don't actually move the camera. See the "PTZ on live streams" section to move the camera.

This returns all the control definitions:
::

  curl http://server/zm/api/controls.json

This returns control definitions for a specific control ID=5
::
  
  curl http://server/zm/api/controls/5.json

Host APIs
^^^^^^^^^^

ZM APIs have various APIs that help you in determining host (aka ZM) daemon status, load etc. Some examples:

::

  curl -XGET  http://server/zm/api/host/getLoad.json # returns current load of ZM

  # Note that ZM 1.32.3 onwards has the same information in Monitors.json which is more reliable and works for multi-server too.
  curl -XGET  http://server/zm/api/host/daemonCheck.json # 1 = ZM running 0=not running

  # The API below uses "du" to calculate disk space. We no longer recommend you use it if you have many events. Use the Storage APIs instead, described later
  curl -XGET  http://server/zm/api/host/getDiskPercent.json # returns in GB (not percentage), disk usage per monitor (that is,space taken to store various event related information,images etc. per monitor)


Storage and Server APIs
^^^^^^^^^^^^^^^^^^^^^^^

ZoneMinder introduced many new options that allowed you to configure multiserver/multistorage configurations. While a part of this was available in previous versions, a lot of rework was done as part of ZM 1.31 and 1.32. As part of that work, a lot of new and useful APIs were added. Some of these are part of ZM 1.32 and others will be part of ZM 1.32.3 (of course, if you build from master, you can access them right away, or wait till a stable release is out.



This returns storage data for my single server install. If you are using multi-storage, you'll see many such "Storage" entries, one for each storage defined:

::

        curl http://server/zm/api/storage.json

Returns:

::

        {
            "storage": [
                {
                    "Storage": {
                        "Id": "0",
                        "Path": "\/var\/cache\/zoneminder\/events",
                        "Name": "Default",
                        "Type": "local",
                        "Url": null,
                        "DiskSpace": "364705447651",
                        "Scheme": "Medium",
                        "ServerId": null,
                        "DoDelete": true
                    }
                 }
               ]
        }



"DiskSpace" is the disk used in bytes. While this doesn't return disk space data as rich as  ``/host/getDiskPercent``, it is much more efficient.

Similarly, 

::

        curl http://server/zm/api/servers.json 

Returns:

::

      {
            "servers": [
                {
                    "Server": {
                        "Id": "1",
                        "Name": "server1",
                        "Hostname": "server1.mydomain.com",
                        "State_Id": null,
                        "Status": "Running",
                        "CpuLoad": "0.9",
                        "TotalMem": "6186237952",
                        "FreeMem": "156102656",
                        "TotalSwap": "536866816",
                        "FreeSwap": "525697024",
                        "zmstats": false,
                        "zmaudit": false,
                        "zmtrigger": false
                    }
                }
            ]
        }

This only works if you have a multiserver setup in place. If you don't it will return an empty array.


Further Reading
^^^^^^^^^^^^^^^^
As described earlier, treat this document as an "introduction" to the important parts of the API and streaming interfaces.
There are several details that haven't yet been documented. Till they are, here are some resources:

* zmNinja, the open source mobile app for ZoneMinder is 100% based on ZM APIs. Explore its `source code <https://github.com/pliablepixels/zmNinja>`__ to see how things work.
* Launch up ZM console in a browser, and do an "Inspect source". See how images are being rendered. Go to the networks tab of the inspect source console and look at network requests that are made when you pause/play/forward streams.
* If you still can't find an answer, post your question in the `forums <https://forums.zoneminder.com/index.php>`__ (not the github repo).



