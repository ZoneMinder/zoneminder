Configuration Files
--------------------
This section describes configuration files that ZoneMinder users beyond the various Web UI options.

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
* ``zmeventnotification.ini`` is only present if you have installed the ZoneMinder Event Server.
* ``objectconfig.ini`` is only present if you have installed the machine learning hooks for the Event Server.
* ``conf.d`` contains additional configuration items as follows:
  
  * ``01-system-paths.conf`` contains all the paths that were once part of ``Options->Paths`` in the Web UI. You should not edit this file as it may be overwritten on an upgrade
  * ``02-multiserver.conf`` file consists of custom variables if you are deploying ZoneMinder in a multi-server configuration (see :doc:`/installationguide/multiserver`) 
  * ``03-custom.conf`` is an  custom config file that I created to override specific variables in the path files. **This is the recommended way to customize entries**. Anything that you want to change should be in a new file inside ``conf.d``

Timezone Configuration
~~~~~~~~~~~~~~~~~~~~~~~
Earlier versions of ZoneMinder relied on ``php.ini`` to set Date/Time Zone. This is no longer the case. You can (and must) set the Timezone via the Web UI, starting ZoneMinder version 1.34. See :ref:`here <timezone_config>`.

Database specific configuration
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. todo:: do we really need to have this section? Not sure if its generic and not specific to ZM

While the ZoneMinder specific database config entries reside in ``/etc/zm/zm.conf`` and related customizations discussed above, general database configuration items can be tweaked in ``/etc/mysql`` (or whichever path your DB server is installed)