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

Edit monitor 1
^^^^^^^^^^^^^^

This command will change the 'Name' field of Monitor 1 to 'tits'

``curl -XPUT http://zmdevapi/monitors/1.json -d "Monitor[Name]=tits"``

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
