API
===

This document will provide an overview of ZoneMinder's API.

Overview
--------

In an effort to further 'open up' ZoneMinder, an API was needed.  This will
allow quick integration with and development of ZoneMinder.

The API is built in CakePHP and lives under the ``/api`` directory.  It
provides a RESTful service and supports CRUD (create, retrieve, update, delete)
functions for Monitors, Events, Frames, Zones and Config.

Examples
--------

Here be a list of examples.  Some results may be truncated.

You will see each URL ending in either ``.xml`` or ``.json``.  This is the
format of the request, and it determines the format that any data returned to
you will be in.  I like json, however you can use xml if you'd like.

Return a list of all monitors
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

``curl -XGET http://zmdevapi/monitors.json``

Retrieve monitor 1
^^^^^^^^^^^^^^^^^^
``curl -XGET http://zmdevapi/monitors/1.json``

Add a monitor
^^^^^^^^^^^^^

This command will add a new http monitor.

``curl -XPOST http://zmdevapi/monitors.js -d "Monitor[Name]=Cliff-Burton \
&Monitor[Function]=Modect \
&Monitor[Protocol]=http \
&Monitor[Method]=simple \
&Monitor[Host]=ussr:pass@192.168.11.20 \
&Monitor[Port]=80 \
&Monitor[Path]=/mjpg/video.mjpg \
&Monitor[Width]=704 \
&Monitor[Height]=480 \
&Monitor[Colours]=4"``

Edit monitor 1
^^^^^^^^^^^^^^

This command will change the 'Name' field of Monitor 1 to 'test1'

``curl -XPUT http://zmdevapi/monitors/1.json -d "Monitor[Name]=test1"``

Delete monitor 1
^^^^^^^^^^^^^^^^

This command will delete Monitor 1, but will _not_ delete any Events which
depend on it.


``curl -XDELETE http://zmdevapi/monitors/1.json``

Return a list of all events
^^^^^^^^^^^^^^^^^^^^^^^^^^^

``curl -XGET http://zmdevapi/events.json``

Retrieve event 1
^^^^^^^^^^^^^^^^
``curl -XGET http://zmdevapi/events/1.json``

Edit event 1
^^^^^^^^^^^^

This command will change the 'Name' field of Event 1 to 'Seek and Destroy'

``curl -XPUT http://zmdevapi/events/1.json -d "Event[Name]=Seek and Destroy"``

Delete event 1
^^^^^^^^^^^^^^
This command will delete Event 1, and any Frames which depend on it.

``curl -XDELETE http://zmdevapi/events/1.json``

Edit config 121
^^^^^^^^^^^^^^^

This command will change the 'Value' field of Config 121 to 901.

``curl -XPUT http://zmdevapi/configs/121.json -d "Config[Value]=901"``

Create a Zone
^^^^^^^^^^^^^

``curl -XPOST http://zmdevapi/zones.json -d "Zone[Name]=Jason-Newsted \
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
&Zone[OverloadFrames]=0"``
