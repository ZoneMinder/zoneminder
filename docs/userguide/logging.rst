Logging
=======

.. note::
  Understanding how logging works in ZoneMinder is key to being able to isolate/pinpoint issues well. Please refer to :doc:`/userguide/options/options_logging` to read about how to customize logging.

Most components of ZoneMinder can emit informational, warning, error and debug messages in a standard format. These messages can be logged in one or more locations. By default all messages produced by scripts are logged in <script name>.log files which are placed in the directory defined by the ``ZM_PATH_LOGS`` configuration variable. This is initially defined as ``/var/log/zm`` (on debian based systems) though it can be overridden to a custom path (the path is usually defined in ``/etc/zm/conf.d/01-system-paths.conf``, but to override it, you should create your own config file, not overwrite this file). So for example, the ``zmdc.pl`` script will output messages to ``/var/log/zmdc.log``, an example of these messages is::

  10/24/2019 08:01:19.291513 zmdc[6414].INF [ZMServer:408] [Starting pending process, zma -m 2]
  10/24/2019 08:01:19.296575 zmdc[6414].INF [ZMServer:408] ['zma -m 2' starting at 19/10/24 08:01:19, pid = 15740]
  10/24/2019 08:01:19.296927 zmdc[15740].INF [ZMServer:408] ['zma -m 2' started at 19/10/24 08:01:19]

where the first part refers to the date and time of the entry, the next section is the name (or an abbreviated version) of the script, followed by the process id in square brackets, a severity code (INF, WAR, ERR or DBG) and the debug text. If you change the location of the log directory, ensure it refers to an existing directory which the web user has permissions to write to. Also ensure that no logs are present in that directory the web user does not have permission to open. This can happen if you run commands or scripts as the root user for testing at some point. If this occurs then subsequent non-privileged runs will fails due to being unable to open the log files.

As well as specific script logging above, information, warning and error messages are logged via the system syslog service. This is a standard component on Linux systems and allows logging of all sorts of messages in a standard way and using a standard format. On most systems, unless otherwise configured, messages produced by ZoneMinder will go to the ``/var/log/messages`` or ``/var/log/syslog`` file. On some distributions they may end up in another file, but usually still in /var/log. Messages in this file are similar to those in the script log files but differ slightly. For example the above event in the system log file looks like::
 
  Jan  3 13:46:00 shuttle52 zmpkg[11148]: INF [Command: start]

where you can see that the date is formatted differently (and only to 1 second precision) and there is an additional field for the hostname (as syslog can operate over a network). As well as ZoneMinder entries in this file you may also see entries from various other system components. You should ensure that your syslogd daemon is running for syslog messages to be correctly handled.


Customizing logging properly in ZoneMinder
-------------------------------------------

.. todo:
  Is this all valid anymore ?


Other Notes
-------------
A number of users have asked how to suppress or redirect ZoneMinder messages that are written to this file. This most often occurs due to not wanting other system messages to be overwhelmed and obscured by the ZoneMinder produced ones (which can be quite frequent by default). In order to control syslog messages you need to locate and edit the syslog.conf file on your system. This will often be in the /etc directory. This file allows configuration of syslog so that certain classes and categories of messages are routed to different files or highlighted to a console, or just ignored. Full details of the format of this file is outside the scope of this document (typing ‘man syslog.conf’ will give you more information) but the most often requested changes are easy to implement.

The syslog service uses the concept of priorities and facilities where the former refers to the importance of the message and the latter refers to that part of the system from which it originated. Standard priorities include ‘info’, ‘warning’, ‘err’ and ‘debug’ and ZoneMinder uses these priorities when generating the corresponding class of message. Standard facilities include ‘mail’, ‘cron’ and ‘security’ etc but as well this, there are eight ‘local’ facilities that can be used by machine specific message generators. ZoneMinder produces it’s messages via the ‘local1’ facility.

So armed with the knowledge of the priority and facility of a message, the syslog.conf file can be amended to handle messages however you like.

So to ensure that all ZoneMinder messages go to a specific log file you can add the following line near the top of your syslog.conf file:

::

  # Save ZoneMinder messages to zm.log
  local1.*                        /var/log/zm/zm.log

which will ensure that all messages produced with the local1 facility are routed to fhe /var/log/zm/zm.log file. However this does not necessarily prevent them also going into the standard system log. To do this you will need to modify the line that determines which messages are logged to this file. This may look something like:

::

  # Log anything (except mail) of level info or higher.
  # Don't log private authentication messages!
  *.info;mail.none;news.none;authpriv.none;cron.none      /var/log/messages

by default. To remove ZoneMinder messages altogether from this file you can modify this line to look like:

::

  *.info;local1.!*;mail.none;news.none;authpriv.none;cron.none     /var/log/messages

which instructs syslog to ignore any messages from the local1 facility. If however you still want warnings and errors to occur in the system log file, you could change it to:

::


  *.info;local1.!*;local1.warning;mail.none;news.none;authpriv.none;cron.none     /var/log/messages

which follows the ignore instruction with a further one to indicate that any messages with a facility of local1 and a priority of warning or above should still go into the file.

These recipes are just examples of how you can modify the logging to suit your system, there are a lot of other modifications you could make. If you do make any changes to syslog.conf you should ensure you restart the syslogd process or send it a HUP signal to force it to reread its configuration file otherwise your changes will be ignored.

The discussion of logging above began by describing how scripts produce error and debug messages. The way that the binaries work is slightly different. Binaries generate information, warning and error messages using syslog in exactly the same way as scripts and these messages will be handled identically. However debug output is somewhat different. For the scripts, if you want to enable debug you will need to edit the script file itself and change the DBG_LEVEL constant to have a value of 1. This will then cause debug messages to be written to the <script>.log file as well as the more important messages. Debug messages however are not routed via syslog. Scripts currently only have one level of debug so this will cause any and all debug messages to be generated. Binaries work slightly differently and while you can edit the call to zmDbgInit that is present in every binary’s ‘main’ function to update the initial value of the debug level, there are easier ways.

The simplest way of collecting debug output is to click on the Options link from the main ZoneMinder console view and then go to the Debug tab. There you will find a number of debug options. The first thing you should do is ensure that the ZM_EXTRA_DEBUG setting is switched on. This enables debug generally. The next thing you need to do is select the debug target, level and destination file using the relevant options. Click on the ‘?’ by each option for more information about valid settings. You will need to restart ZoneMinder as a whole or at least the component in question for logging to take effect. When you have finished debugging you should ensure you switch debug off by unchecking the ZM_EXTRA_DEBUG option and restarting ZoneMinder. You can leave the other options as you like as they are ignored if the master debug option is off.

Once you have debug being logged you can modify the level by sending USR1 and USR2 signals to the relevant binary (or binaries) to increase or decrease the level of debug being emitted with immediate effect. This modification will not persist if the binary gets restarted however.

If you wish to run a binary directly from the command line to test specific functionality or scenarios, you can set the ZM_DBG_LEVEL and ZM_DBG_LOG environment variables to set the level and log file of the debug you wish to see, and the ZM_DBG_PRINT environment variable to 1 to output the debug directly to your terminal.

All ZoneMinder logs can now be rotated by logrotate. A sample logrotate config file is shown below:

::

  /var/log/zm/*.log {
      missingok
      notifempty
      sharedscripts
      postrotate
          /usr/local/bin/zmpkg.pl logrot 2> /dev/null > /dev/null || true
      endscript
  }


