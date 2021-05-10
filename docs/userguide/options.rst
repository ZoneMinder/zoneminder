Options
=======

The various options you can specify are displayed in a tabbed dialog with each group of options displayed under a different heading. Each option is displayed with its name, a short description and the current value. You can also click on the ‘?’ link following each description to get a fuller explanation about each option. This is the same as you would get from zmconfig.pl. A number of option groups have a master option near the top which enables or disables the whole group so you should be aware of the state of this before modifying options and expecting them to make any difference.

If you have changed the value of an option you should then ‘save’ it. A number of the option groups will then prompt you to let you know that the option(s) you have changed will require a system restart. This is not done automatically in case you will be changing many values in the same session, however once you have made all of your changes you should restart ZoneMinder as soon as possible. The reason for this is that web and some scripts will pick up the new changes immediately but some of the daemons will still be using the old values and this can lead to data inconsistency or loss.

.. note:: If you are looking for ``Options->Paths`` documentation, it was moved to a configuration file starting ZoneMinder 1.32. See :ref:`here <replacement_for_options_path>`.

.. toctree::

   options/options_display
   options/options_system
   options/options_config
   options/options_api
   options/options_servers
   options/options_storage
   options/options_web
   options/options_images
   options/options_logging
   options/options_network
   options/options_email
   options/options_upload
   options/options_x10
   options/options_bw
   options/options_users
