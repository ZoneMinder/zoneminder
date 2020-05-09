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

Security
^^^^^^^^^
The APIs tie into ZoneMinder's existing security model. This means if you have
OPT_AUTH enabled, you need to log into ZoneMinder using the same browser you plan to 
use the APIs from. If you are developing an app that relies on the API, you need 
to do a POST login from the app into ZoneMinder before you can access the API.

Then, you need to re-use the authentication information of the login (returned as cookie states)
with subsequent APIs for the authentication information to flow through to the APIs.

This means if you plan to use cuRL to experiment with these APIs, you first need to do

::

	curl -d "username=XXXX&password=YYYY&action=login&view=console" -c cookies.txt  http://yourzmip/zm/index.php

replacing *XXXX* and *YYYY* with your username and password, respectively.

Please make sure you do this in a directory where you have write permissions, otherwise cookies.txt will not be created
and the command will silently  fail.


What the "-c cookies.txt" does is store a cookie state reflecting that you have logged into ZM. You now need
to apply that cookie state to all subsequent APIs. You do that by using a '-b cookies.txt' to subsequent APIs if you are 
using CuRL like so:

::

	curl -b cookies.txt http://yourzmip/zm/api/monitors.json

This would return a list of monitors and pass on the authentication information to the ZM API layer. 

So remember, if you are using authentication, please add a ``-b cookies.txt``  to each of the commands below if you are using
CuRL. If you are not using CuRL and writing your own app, you need to make sure you pass on cookies to subsequent requests
in your app.

Examples (please read security notice above)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

You will see each URL ending in either ``.xml`` or ``.json``.  This is the
format of the request, and it determines the format that any data returned to
you will be in.  I like json, however you can use xml if you'd like.

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

Retrieve monitor 1
^^^^^^^^^^^^^^^^^^^

::
  
  	curl http://server/zm/api/monitors/1.json


Change State of Monitor 1
^^^^^^^^^^^^^^^^^^^^^^^^^^

This API changes monitor 1 to Modect and Enabled

::

  curl -XPOST http://server/zm/api/monitors/1.json -d "Monitor[Function]=Modect&Monitor[Enabled]:true"

Add a monitor
^^^^^^^^^^^^^^

This command will add a new http monitor.

::

  curl -XPOST http://server/zm/api/monitors.json -d "Monitor[Name]=Cliff-Burton \
  &Monitor[Function]=Modect \
  &Monitor[Protocol]=http \
  &Monitor[Method]=simple \
  &Monitor[Host]=usr:pass@192.168.11.20 \
  &Monitor[Port]=80 \
  &Monitor[Path]=/mjpg/video.mjpg \
  &Monitor[Width]=704 \
  &Monitor[Height]=480 \
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

  curl -XGET http://server/zm/api/events/events/index/MonitorId:5.json``


Note that the same pagination logic applies if the list is too long


Return a list of events for a specific monitor within a specific date/time range
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

::

  http://server/zm/api/events/events/index/MonitorId:5/StartTime >=:2015-05-15 18:43:56/EndTime <=:2015-05-16 18:43:56.json


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

(Example of changing config TBD)

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

  curl -XPOST http://server/zm/api/zones.json -d "Zone[Name]=Jason-Newsted \
  &Zone[MonitorId]=3 \
  &Zone[Type]=Active \
  &Zone[Units]=Percent \
  &Zone[NumCoords]=4 \
  &Zone[Coords]=0,0 639,0 639,479 0,479 \
  &Zone[AlarmRGB]=16711680 \
  &Zone[CheckMethod]=Blobs \
  &Zone[MinPixelThreshold]=25 \
  &Zone[MaxPixelThreshold]= \
  &Zone[MinAlarmPixels]=9216 \
  &Zone[MaxAlarmPixels]= \
  &Zone[FilterX]=3 \
  &Zone[FilterY]=3 \
  &Zone[MinFilterPixels]=9216 \
  &Zone[MaxFilterPixels]=230400 \
  &Zone[MinBlobPixels]=6144 \
  &Zone[MaxBlobPixels]= \
  &Zone[MinBlobs]=1 \
  &Zone[MaxBlobs]= \
  &Zone[OverloadFrames]=0"

PTZ Control APIs
^^^^^^^^^^^^^^^^
PTZ controls associated with a monitor are stored in the Controls table and not the Monitors table inside ZM. What that means is when you get the details of a Monitor, you will only know if it is controllable (isControllable:true) and the control ID.
To be able to retrieve PTZ information related to that Control ID, you need to use the controls API

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

  curl -XGET  http://server/zm/api/host/daemonCheck.json # 1 = ZM running 0=not running
  curl -XGET  http://server/zm/api/host/getLoad.json # returns current load of ZM
  curl -XGET  http://server/zm/api/host/getDiskPercent.json # returns in GB (not percentage), disk usage per monitor (that is,   space taken to store various event related information,images etc. per monitor) ``

