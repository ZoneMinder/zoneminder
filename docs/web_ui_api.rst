
WEB UI API
##########

This document will provide information on interfacing with ZoneMinder's WEB UI ajax components.

Overview
********

The ZoneMinder Web UI is written in PHP and various aspects of the web ui use AJAX calls to perform various tasks.

These AJAX requests take the form of a GET (NOT POST!).  It requires a query parameter called request.
In addition, if authentication is turned on, you will need to append either username and password, auth hash or jwt token to supply authentication in the request. See the REST API documentation for further information on authentication.

Acceptable values for request are:

* :ref:`add_monitors`
* :ref:`alarm`
* :ref:`console`
* :ref:`control`
* :ref:`controlcaps`
* :ref:`device`
* :ref:`devices`
* :ref:`event`
* :ref:`events`
* :ref:`frames`
* :ref:`log`
* :ref:`modal`
* :ref:`models`
* :ref:`reports`
* :ref:`shutdown`
* :ref:`snapshots`
* :ref:`stats`
* :ref:`status`
* :ref:`stream`
* :ref:`tags`
* :ref:`watch`
* :ref:`zone`

(In all examples, replace 'server' with IP or hostname & port where ZoneMinder is running)

.. _add_monitors:

add_monitors
============

.. _alarm:

alarm
=====

.. _console:

console
=======

.. _control:

control
=======

Used for sending PTZ commands to monitors.

Lets assume you have a monitor, with ID=6. Let's further assume you want to pan it left.

You'd need to send a:

``GET`` command to ``https://server/zm/index.php`` with the following data payload in the command (NOT in the URL)

``view=request&request=control&id=6&control=moveConLeft&xge=30&yge=30``


.. _controlcaps:

controlcaps
===========

.. _device:

device
======

.. _devices:

devices
=======

.. _event:

event
======

Commands are passed using the "action" query parameter. Available values are: 

* addtag,
* archive,
* delete,
* :ref:`download` 
* eventdetail,
* export, 
* getselectedtags,
* removetag,
* rename,
* unarchive,
* video

.. _download:

download
***********

   Parameters are:

   * exportFormat:
     tar or zip. Defaults to zip.
   * exportFileName
     Defaults to 'Export'+connkey
   * id or eids[]
     Specify events by single id or an array of events ids.
   * filter:
     A url-encoded representation of a filter to use to get a list of eids.
   * mergevents:
     Whether to leave each event as a single mp4, or merge events for each monitor into a single mp4 for that monitor.
   * connkey:
     a seimi-unique value to uniquely identify this request from others. Typically ZM uses 6 decimal digits generated randomly.

   For example, a request could look like:

::

   curl http://server/zm/index.php?view=request&request=event&action=download&connkey=198605&exportVideo=1&mergeevents=1&eids%5B%5D=15433324&eids%5B%5D=15433318&exportFileName=zmDownload-198605&exportFormat=zip


On success, the response will look like:

::

  {
    "result": "Ok",
    "exportFile": "?view=archive&type=zip&file=zmDownload-198605.zip",
    "exportFormat": "zip",
    "connkey": "198605"
  }

You may then use the value in exportFile as a url to download the generated zip file.

.. _events:

events
*******

Commands are passed using the task query parameter.  Available values are: archive, unarchive, delete, query

.. _frames:

frames
======

.. _log:

log
===

.. _modal:

modal
=====

.. _models:

models
======

.. _reports:

reports
=======

.. _shutdown:

shutdown
========

.. _snapshots:

snapshots
=========

.. _stats:

stats
=====

.. _status:

status
======

.. _stream:

stream
======

.. _tags:

tags
====

.. _watch:

watch
======

.. _zone:

zone
====
  
