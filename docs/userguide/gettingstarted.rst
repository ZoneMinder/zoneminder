Getting Started
===============

Having followed the :doc:`/installationguide/index` for your distribution you should now be able to load the ZoneMinder web frontend. By default this will be with the Classic skin, below is an example of the page you should now see.

.. image::  ../installationguide/images/zm_first_screen_post_install.png


Enabling Authentication
^^^^^^^^^^^^^^^^^^^^^^^
We strongly recommend enabling authentication right away. There are some situations where certain users don't enable authentication, such as instances where the server is in a LAN not directly exposed to the Internet, and is only accessible via VPN etc., but in most cases, authentication should be enabled. So let's do that right away.

* Click on the Options link on the top right corner of the web interface
* You will now be presented with a screen full of options. Click on the "System" tab
	
.. image:: images/getting-started-enable-auth.png

* The relevant portions to change are marked in red above
* Enable OPT_USE_ATH - this automatically switches to authentication mode with a default user (more on that later)
* Select a random string for AUTH_HASH_SECRET - this is used to make the authentication logic more secure, so 
  please generate your own string and please don't use the same value in the example.
* The other options highlighed above should already be set, but if not, please make sure they are

* Click on Save at the bottom and that's it! The next time you refresh that page, you will now be presented with a login screen. Job well done!

.. image:: images/getting-started-login.png

.. NOTE:: The default login/password is "admin/admin"


Switching to flat theme
^^^^^^^^^^^^^^^^^^^^^^^
What you see is what is called a "classic" skin. Zoneminder has a host of configuration options that you can customize over time. This guide is meant to get you started the easiest possible way, so we will not go into all the details. However, it is worthwhile to note that Zoneminder also has a 'flat' theme that depending on your preferences may look more modern. So let's use that as an example of introducing you to the Options menu

* Click on the Options link on the top right of the web interface in the image above
* This will bring you to the options window as shown below. Click on the "System" tab and then select the 
  "flat" option for CSS_DEFAULT as shown below

.. image:: images/getting-started-flat-css.png  

* Click Save at the bottom

Now, switch to the "Display" tab and also select "Flat" there like so:

.. image:: images/getting-started-flat-css-2.png

Your screen will now look like this:


Congratulations! You now have a modern looking interface.

.. image:: images/getting-started-modern-look.png




To add cameras to the system you need to create a Monitor for each camera. Click 'Add New Monitor' to bring up the dialog.


