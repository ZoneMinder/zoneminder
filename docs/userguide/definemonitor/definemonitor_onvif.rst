ONVIF Tab
---------

    The ONVIF tab contains settings related to the ONVIF communications protocol. ONVIF provides a common protocol for network IP cameras and associated equipment to facilitate device discovery and various control features.

.. figure:: images/define-monitor-onvif.png

    Monitor ONVIF Tab

- **ONVIF_URL**: Enter URL for ONVIF controlled device. Typical URL format is ``http://username:password@hostname:port/onvif/device_service``
- **Username**: The username of ONVIF access for camera. Note that if your URL contains authentication this may be automatically populated into the Username field.
- **Password**: The password of ONVIF access for camera. Note that if your URL contains authentication this may be automatically populated into the Password field.
- **ONVIF_Options**: Advanced ONVIF options. This is an optional field used to fine-tune the ONVIF event listener behaviour.

  **Format**: A comma-separated list of ``key=value`` pairs, for example::

      pull_timeout=5,subscription_timeout=120,max_retries=5

  **Supported options**:

  - ``pull_timeout=<seconds>`` — How long (in seconds) to wait for the camera to return ONVIF events on each poll request. Default is ``1``. Must be a positive integer. Increasing this value reduces network overhead but also slows down alarm detection. The value must be less than the subscription renewal advance window (60 s by default), so values of 59 or below are safe.

  - ``subscription_timeout=<seconds>`` — How long (in seconds) the ONVIF event subscription should remain valid before ZoneMinder renews it. Default is ``300`` (5 minutes). Must be a positive integer. Increase for cameras that struggle to keep up with frequent renewal requests.

  - ``max_retries=<count>`` — Maximum number of consecutive retry attempts before ZoneMinder enters a longer cool-down wait. Default is ``10``. Accepted range is ``0``–``100``. Set to ``0`` to disable retries (not recommended for production use).

  - ``timestamp_validity=<seconds>`` — Lifetime of the WS-Security timestamp included in each ONVIF request, in seconds. Default is ``60``. Accepted range is ``10``–``600``. Increase this value if ZoneMinder logs authentication errors caused by clock drift between the ZoneMinder server and the camera.

  - ``soap_log=<path>`` — Write all raw SOAP request and response messages to the specified file path. Useful for diagnosing camera compatibility problems. Example: ``soap_log=/tmp/zm_onvif_soap.log``. Leave this option unset in normal operation to avoid large log files.

  - ``renewal_enabled=<true|false>`` — Whether ZoneMinder should automatically renew the ONVIF event subscription before it expires. Default is ``true``. Set to ``false`` (or ``0`` or ``no``) to disable automatic renewal; ZoneMinder will re-subscribe from scratch each time the subscription expires instead. Try this if a camera rejects renewal requests.

  - ``expire_alarms=<true|false>`` — Whether ZoneMinder should automatically clear alarms that have not been explicitly cancelled by the camera after their per-topic timeout elapses. Default is ``true``. Disable (``false``, ``0``, or ``no``) only if your camera reliably sends an explicit alarm-off notification and you are seeing spurious alarm clearances.

  - ``closes_event`` — Force ZoneMinder to immediately treat the camera as one that sends explicit alarm-off notifications (``PropertyOperation="Changed"`` or ``PropertyOperation="Deleted"``). By default this is auto-detected from the first event message received. Setting this option skips the auto-detection step and is normally not required.

- **ONVIF_Alarm_Text**: Text associated with event when alarm is activated.
- **ONVIF_Event_Listener**: Options are Enabled or Disabled.
