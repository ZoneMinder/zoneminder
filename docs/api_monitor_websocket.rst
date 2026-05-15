Monitor Websocket API
=====================

ZoneMinder can expose live monitor data directly from ``zmc`` over a websocket
connection.

Overview
^^^^^^^^

Each monitor listens on:

::

   MIN_STREAMING_PORT + MonitorId

For example, if ``MIN_STREAMING_PORT`` is ``30000`` and the monitor id is
``5``, the websocket endpoint is:

::

   ws://your-server:30005/

This requires ``Options -> Network -> MIN_STREAMING_PORT`` to be configured and
the web server or reverse proxy to allow those ports.

.. warning::

   The monitor websocket endpoint can expose live camera data to any client
   that can reach the monitor's streaming port. This transport does not provide
   access control by itself, so do not expose these ports directly to
   untrusted networks. Restrict access with firewall rules and/or place the
   endpoint behind a reverse proxy that enforces authentication and
   authorization.

Connection model
^^^^^^^^^^^^^^^^

The websocket server is created by ``zmc`` and runs independently per monitor.

Clients may:

* request one response
* subscribe to repeated updates
* unsubscribe later

Text frames carry JSON control and metadata messages. Binary frames carry the
requested ``jpeg``, ``raw``, or ``h264`` payload bytes.

Commands
^^^^^^^^

One-shot status request:

::

   {"command":"status","request_id":"optional-id"}

One-shot image request:

::

   {"command":"image","format":"jpeg","request_id":"optional-id"}

Supported image formats are:

* ``jpeg``
* ``raw``
* ``h264``

Status subscription:

::

   {"command":"subscribe","topic":"status","interval_ms":1000}

Event subscription:

::

   {"command":"subscribe","topic":"events"}

Image subscription:

::

   {"command":"subscribe","topic":"image","format":"jpeg","interval_ms":1000}

Unsubscribe:

::

   {"command":"unsubscribe","topic":"status"}

or:

::

   {"command":"unsubscribe","topic":"events"}

or:

::

   {"command":"unsubscribe","topic":"image"}

Status messages
^^^^^^^^^^^^^^^

Status replies are JSON text frames with fields such as:

* ``monitor_id``
* ``monitor_name``
* ``connected``
* ``shm_valid``
* ``state`` / ``state_id``
* ``capture_fps``
* ``analysis_fps``
* ``capture_bandwidth``
* ``image_count``
* ``signal``
* ``last_event_id``

Event messages
^^^^^^^^^^^^^^

Event subscriptions receive JSON text frames with:

* ``type = "event"``
* ``monitor_id``
* ``event``
* ``message``

These are queue-based notifications generated from capture-side failures and
recovery transitions so the capture loop does not block on websocket clients.

Image metadata and binary payloads
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Every image or video payload is sent as two websocket frames:

1. A JSON text metadata frame
2. A binary frame containing the payload bytes

The metadata frame includes:

* ``type = "image"``
* ``request_id``
* ``format``
* ``content_type``
* ``monitor_id``
* ``width``
* ``height``
* ``colours``
* ``subpixel_order``
* ``image_count``
* ``sequence``
* ``keyframe``
* ``payload_bytes``

JPEG and raw image behavior
^^^^^^^^^^^^^^^^^^^^^^^^^^^

``jpeg`` and ``raw`` payloads are generated from the latest image in the
monitor shared-memory ring buffer.

For subscription mode, ``interval_ms`` controls how often the server checks for
and sends a newer frame.

H264 behavior
^^^^^^^^^^^^^

``h264`` delivery uses the monitor packet queue, not the shared-memory image
buffer.

One-shot ``h264`` requests return a decodable packet snapshot:

* the payload starts at the latest available queued H.264 keyframe
* codec extradata is prepended before the keyframe packet bytes

``h264`` subscriptions stream queued packets in order starting from the latest
available keyframe in the queue. This gives new subscribers a decodable start
point and avoids dropping interdependent packets.

For ``h264`` subscriptions:

* packets are pushed in queue order
* ``interval_ms`` is ignored
* ``sequence`` tracks the packet queue order
* ``keyframe`` indicates whether the payload begins a new decodable segment

Errors
^^^^^^

Protocol errors are returned as JSON text frames:

::

   {"type":"error","message":"..."}

Unsupported payload formats, unavailable monitor data, or malformed commands
return an error frame instead of a binary payload.
