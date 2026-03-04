zma - Event Re-Analysis
=======================

Description
-----------

``zma`` re-analyses a previously recorded event using the current zone settings
for the monitor. It decodes each frame from the event's stored video or JPEG
files and runs the full motion detection pipeline.

This is useful for tuning zone settings and re-running analysis on existing
footage without needing live cameras. After adjusting zones in the web
interface, you can use ``zma`` to see how those changes would affect detection
on past events.

By default, ``zma`` updates the existing event's motion statistics
(AlarmFrames, TotScore, AvgScore, MaxScore) in the database. With the
``--create-events`` option, it instead creates new events from the detected
motion regions.

Synopsis
--------

.. code-block:: bash

   zma -e <event_id> [options]

Options
-------

``-e, --event <event_id>``
   Event ID to re-analyse. Required.

``-m, --monitor <monitor_id>``
   Override the monitor ID used for zone configuration. By default, ``zma``
   uses the monitor that owns the event. Use this option to apply a different
   monitor's zone settings to the event's footage.

``-c, --create-events``
   Create new events from detected motion instead of updating the original
   event's scores. The new events are inserted into the database and linked
   to the source video files via hard links (falling back to copies if on a
   different filesystem).

``-a, --save-analysis``
   Write analysis JPEG images showing zone alarm overlays for each frame
   that triggered motion. The images are written to the event's directory
   using the standard analysis file naming convention.

``-v, --verbose``
   Increase debug verbosity. Can be specified multiple times for higher
   verbosity levels.

``-h, --help``
   Display usage information.

``-V, --version``
   Print the installed version of ZoneMinder.

Modes of Operation
------------------

Update Existing (default)
^^^^^^^^^^^^^^^^^^^^^^^^^

When run without ``--create-events``, ``zma`` processes every frame and then
updates the original event row in the database with the recalculated scores:

- **AlarmFrames** -- number of frames that triggered motion
- **TotScore** -- sum of all alarm frame scores
- **AvgScore** -- average score across alarm frames
- **MaxScore** -- highest single-frame score

This lets you see how zone tuning changes the event's score metrics without
creating any new data.

Create Events
^^^^^^^^^^^^^

With ``--create-events``, ``zma`` uses the monitor's alarm state machine
(including ``AlarmFrameCount`` and ``PostEventCount`` settings) to detect
alarm regions in the footage and creates new event records in the database for
each one. The source video files are hard-linked into the new event directories
so no additional disk space is used (on the same filesystem).

Save Analysis
^^^^^^^^^^^^^

The ``--save-analysis`` flag can be combined with either mode. When a frame
triggers motion, ``zma`` writes analysis JPEG images with zone alarm overlays
into the event directory. These images show which zones were triggered and can
be viewed in the web interface to visually verify zone configuration.

Examples
--------

Re-analyse event 12345 and update its scores in the database:

.. code-block:: bash

   zma -e 12345

Re-analyse event 12345 using monitor 2's zone settings:

.. code-block:: bash

   zma -e 12345 -m 2

Create new events from detected motion in event 12345:

.. code-block:: bash

   zma -e 12345 --create-events

Re-analyse with analysis images and verbose output:

.. code-block:: bash

   zma -e 12345 --save-analysis -v
