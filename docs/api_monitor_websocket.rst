Monitor Websocket API
=====================

ZoneMinder can expose live monitor data directly from ``zmc`` over a websocket
connection.

Overview
^^^^^^^^

Each monitor listens on:

::

   MIN_WEBSOCKET_PORT + MonitorId

For example, if ``MIN_WEBSOCKET_PORT`` is ``31000`` and the monitor id is
``5``, the websocket endpoint is:

::

   ws://your-server:31005/

This requires ``Options -> Network -> MIN_WEBSOCKET_PORT`` to be configured and
the web server or reverse proxy to allow those ports.

.. warning::

   The monitor websocket endpoint can expose live camera data to any client
   that can reach the monitor's websocket port. Native TLS is not provided by
   ``zmc`` itself, so do not expose these ports directly to untrusted
   networks. Restrict access with firewall rules and/or place the endpoint
   behind a reverse proxy that terminates TLS.

Connection model
^^^^^^^^^^^^^^^^

The websocket server is created by ``zmc`` and runs independently per monitor.

Clients may:

* request one response
* subscribe to repeated updates
* unsubscribe later

Text frames carry JSON control and metadata messages. Binary frames carry the
requested image or stream payload bytes.

Authentication
^^^^^^^^^^^^^^

If ``OPT_USE_AUTH`` is disabled, websocket clients may connect without
credentials.

If ``OPT_USE_AUTH`` is enabled, the websocket handshake is authenticated before
the connection is upgraded. The authenticated user must have live stream view
permission and monitor access for the target monitor.

Supported authentication inputs mirror the existing ZoneMinder streaming paths:

* ``?token=<jwt>`` or ``?jwt_token=<jwt>`` in the websocket URL
* ``Authorization: Bearer <jwt>`` in the HTTP upgrade request
* ``?auth=<hash>&username=<name>`` when auth-hash relay is in use
* ``?username=<name>&password=<password>`` when direct credentials are allowed
* ``?username=<name>`` when ``AUTH_RELAY`` is ``none``

Examples:

::

   ws://your-server:31005/?token=<jwt>

or:

::

   ws://your-server:31005/?auth=<hash>&username=alice

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
* ``rgba``

One-shot stream packet request:

::

   {"command":"stream","codec":"mjpeg","request_id":"optional-id"}

Status subscription:

::

   {"command":"subscribe","topic":"status","interval_ms":1000}

Event subscription:

::

   {"command":"subscribe","topic":"events"}

Stream subscription:

::

   {"command":"subscribe","topic":"stream","codec":"mjpeg","interval_ms":1000}

Unsubscribe:

::

   {"command":"unsubscribe","topic":"status"}

or:

::

   {"command":"unsubscribe","topic":"events"}

or:

::

   {"command":"unsubscribe","topic":"stream"}

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

Payload metadata and binary payloads
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Every image or stream payload is sent as two websocket frames:

1. A JSON text metadata frame
2. A binary frame containing the payload bytes

The metadata frame includes:

* ``type = "image"`` or ``"stream"``
* ``request_id``
* ``format``
* ``content_type``
* ``monitor_id``
* ``width``
* ``height``
* ``line_size``
* ``colours``
* ``subpixel_order``
* ``image_count``
* ``sequence``
* ``keyframe``
* ``payload_bytes``

Image behavior
^^^^^^^^^^^^^^

Image requests use the latest decoded video frame available in the monitor
packet queue.

``jpeg`` returns a compressed still image.

``rgba`` returns an aligned raw RGBA buffer. Because the line size may include
padding, clients must use the reported ``line_size`` value rather than assuming
``width * 4`` bytes per row.

Stream behavior
^^^^^^^^^^^^^^^

Stream requests and subscriptions use explicit codec names instead of treating
encoded video packets as images.

``mjpeg`` returns a stream of JPEG frames. For subscription mode,
``interval_ms`` controls how often the server checks for and sends a newer
frame.

Passthrough codec streams currently support:

* ``h264``
* ``h265``
* ``av1``

Passthrough stream payloads use the monitor packet queue and are only available
when the monitor is already producing that codec.

One-shot passthrough stream requests return a decodable packet snapshot:

* the payload starts at the latest available queued keyframe for that codec
* codec extradata is prepended before the keyframe packet bytes

Passthrough subscriptions stream queued packets in order starting from the
latest available keyframe in the queue. This gives new subscribers a decodable
start point and avoids dropping interdependent packets.

For passthrough codec subscriptions:

* packets are pushed in queue order
* ``interval_ms`` is ignored
* ``sequence`` tracks the packet queue order
* ``keyframe`` indicates whether the payload begins a new decodable segment

Implementation notes
^^^^^^^^^^^^^^^^^^^^

This transport currently uses a small in-tree websocket implementation rather
than adding a new dependency such as ``websocketpp`` to ``zmc``.

The advantage is a smaller integration surface inside the capture daemon and
direct control over packet queue interaction.

The tradeoff is that TLS is intentionally left to the deployment boundary
instead of being implemented inside this small in-tree websocket server. In
practice, production deployments should terminate TLS in a reverse proxy, load
balancer, or similar front-end before exposing this transport to clients.

Errors
^^^^^^

Protocol errors are returned as JSON text frames:

::

   {"type":"error","message":"..."}

Unsupported image formats, unsupported stream codecs, unavailable monitor data,
or malformed commands return an error frame instead of a binary payload.
