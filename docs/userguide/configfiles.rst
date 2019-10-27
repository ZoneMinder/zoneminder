Configuration Files
--------------------
This section describes configuration files that ZoneMinder uses beyond the various Web UI options.

.. _replacement_for_options_path:

System Path Configurations
~~~~~~~~~~~~~~~~~~~~~~~~~~
At one point of time, ZoneMinder stored various system path configurations under the Web UI (``Options->Paths``). This was removed a few versions ago and now resides in a configuration file. The motivation for this change can be read in `this discussion <https://github.com/ZoneMinder/zoneminder/pull/1908>`__.

Typically, path configurations now reside in ``/etc/zm``.

Here is an example of the file hierarchy:

::

  /etc/zm
  ├── conf.d
  │   ├── 01-system-paths.conf
  │   ├── 02-multiserver.conf
  |   ├── 03-custom.conf #optional
  │   └── README
  ├── objectconfig.ini # optional
  ├── zm.conf 
  └── zmeventnotification.ini #optional

The roles of the files are as follows:

* ``zm.conf`` contains various base configuration entries. You should not edit this file as it may be overwritten on an upgrade.
* ``zmeventnotification.ini`` is only present if you have installed the ZoneMinder Event Notification Server.
* ``objectconfig.ini`` is only present if you have installed the machine learning hooks for the Event Notification Server.
* ``conf.d`` contains additional configuration items as follows:
  
  * ``01-system-paths.conf`` contains all the paths that were once part of ``Options->Paths`` in the Web UI. You should not edit this file as it may be overwritten on an upgrade
  * ``02-multiserver.conf`` file consists of custom variables if you are deploying ZoneMinder in a multi-server configuration (see :doc:`/installationguide/multiserver`) 
  * ``03-custom.conf`` is an  custom config file that I created to override specific variables in the path files. **This is the recommended way to customize entries**. Anything that you want to change should be in a new file inside ``conf.d``. Note that ZoneMinder will sort all the files alphabetically and run their contents in ascending order. So it doesn't really matter what you name them, as long as you make sure your changes are not overwritten by another file in the sorting sequence. It is therefore good practice to prefix your file names by ``nn-`` where ``nn`` is a monotonically increasing numerical sequence ``01-`` ``02-`` ``03-`` and so forth, so you know the order they will be processed. 

Timezone Configuration
~~~~~~~~~~~~~~~~~~~~~~~
Earlier versions of ZoneMinder relied on ``php.ini`` to set Date/Time Zone. This is no longer the case. You can (and must) set the Timezone via the Web UI, starting ZoneMinder version 1.34. See :ref:`here <timezone_config>`.

Database Specific Configuration
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. todo:: do we really need to have this section? Not sure if its generic and not specific to ZM

While the ZoneMinder specific database config entries reside in ``/etc/zm/zm.conf`` and related customizations discussed above, general database configuration items can be tweaked in ``/etc/mysql`` (or whichever path your DB server is installed)