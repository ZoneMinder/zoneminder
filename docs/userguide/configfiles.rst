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

ZoneMinder finds its database using the following ``zm.conf`` settings. The
defaults match what the standard packages install, so most users never need to
change them; a remote database or custom credentials is the usual reason to.

.. list-table::
   :header-rows: 1
   :widths: 20 15 65

   * - Setting
     - Default
     - Description
   * - ``ZM_DB_TYPE``
     - ``mysql``
     - Database server type.
   * - ``ZM_DB_HOST``
     - ``localhost``
     - Hostname or IP of the database server. A port can be appended as
       ``host:port``, or a local socket as ``host:/path/to/socket``.
   * - ``ZM_DB_NAME``
     - ``zm``
     - Name of the ZoneMinder database.
   * - ``ZM_DB_USER``
     - ``zmuser``
     - Database user ZoneMinder connects as.
   * - ``ZM_DB_PASS``
     - ``zmpass``
     - Password for that database user.

As with the SSL settings below, override these in a ``conf.d`` file rather than
editing ``zm.conf`` directly, so your changes survive an upgrade.

Connecting to the Database over SSL/TLS
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

ZoneMinder can connect to its database over an encrypted SSL/TLS connection,
which is useful when the database runs on a separate host. The connection is
controlled by the following settings:

==================================  ============================================================
Setting                             Description
==================================  ============================================================
``ZM_DB_SSL_CA_CERT``               Path to the CA certificate that signed the database
                                    server's certificate. Setting this is what enables SSL;
                                    leave it empty for an unencrypted connection.
``ZM_DB_SSL_CLIENT_KEY``            Path to the client private key, for mutual TLS where the
                                    server also authenticates the client.
``ZM_DB_SSL_CLIENT_CERT``           Path to the client certificate, for mutual TLS.
``ZM_DB_SSL_VERIFY_SERVER_CERT``    Whether to verify the server's certificate. Defaults to ``1``
                                    (verify). Verification is by *identity*: the certificate must
                                    chain to ``ZM_DB_SSL_CA_CERT`` **and** its CN/SAN must match the
                                    host in ``ZM_DB_HOST``. Set to ``0`` (or ``false``/``no``/``off``)
                                    to allow a self-signed or non-matching server certificate.
==================================  ============================================================

These settings apply to every ZoneMinder component that talks to the database:
the C++ daemons, the PHP web interface, the CakePHP API and the Perl scripts.

Although these entries exist in ``/etc/zm/zm.conf``, that file may be overwritten
on an upgrade, so put your values in a ``conf.d`` override file instead. For
example, ``/etc/zm/conf.d/04-database-ssl.conf``::

  ZM_DB_SSL_CA_CERT=/etc/zm/ssl/ca.pem
  ZM_DB_SSL_CLIENT_KEY=/etc/zm/ssl/client-key.pem
  ZM_DB_SSL_CLIENT_CERT=/etc/zm/ssl/client-cert.pem

.. note::

   ``ZM_DB_SSL_VERIFY_SERVER_CERT`` only has an effect when ``ZM_DB_SSL_CA_CERT``
   is set, and applies consistently across the C++ daemons, the PHP web
   interface, the API and the Perl scripts. Verification includes a hostname
   check, so the server certificate's CN/SAN must match the value of
   ``ZM_DB_HOST``. A common gotcha: if ``ZM_DB_HOST`` is an IP address but the
   certificate was issued for a hostname, verification fails — connect by the
   hostname the certificate was issued for, add that IP to the certificate's
   SAN, or set ``ZM_DB_SSL_VERIFY_SERVER_CERT=0``. Likewise a self-signed
   certificate will fail verification; set the option to ``0`` to allow it.
   Disabling verification means the server's identity is no longer checked, so
   only do this on a trusted network.

   Leaving the value empty (rather than ``1`` or ``0``) leaves the database
   client library's own default in place. Fresh installs default to ``1``;
   an empty value is mainly relevant to installs upgraded from a version that
   predates this option, whose connection behaviour is then unchanged until the
   value is set explicitly.