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

Understanding the Web Console
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
Before we proceed, lets spend a few minutes understanding the key functions of the web console. 
For the sake of illustration, we are going to use a populated zoneminder configuration with several monitors and events.
Obviously, this does not reflect your current web console - which is essentially void of any useful information till now,
as we are yet to add things. Let's take a small break and understand what the various functions are before we configure our
own empty screen.

.. image:: images/getting-started-understand-console.png

* **A**: This is the username that is logged in. You are logged in as 'admin' here
* **B**: Click here to explore the various options of ZoneMinder and how to configure them. You already used this to enable authentication and change style above. Over time, you will find this to have many other things you will want to customize.
* **C**: This link, when clicked, opens up a color coded log window of what is going on in Zoneminder and often gives you good insight into what is going wrong or right. Note that the color here is red - that is an indication that some error occurred in ZoneMinder. You should click it and investigate.
* **D**: This is the core of ZoneMinder - recording events. It gives you a count of how many events were recorded over the hour, day, week, month.
* **E**: These are the "Zones". Zones are areas within the camera that you mark as 'hotspots' for motion detection. Simply put, when you first configure your monitors (cameras), by default Zoneminder uses the entire field of view of the camera to detect motion. You may not want this. You may want to create "zones" specifically for detecting motion and ignore others. For example, lets consider a room with a fan that spins. You surely don't want to consider the fan moving continously a reason for triggering a record? Probably not - in that case, you'd leave the fan out while making your zones.
* **F**: This is the "source" column that tells you the type of the camera - if its an IP camera, a USB camera or more. In this example, they are all IP cameras. Note the color red on item F ? Well that means there is something wrong with that camera. No wonder the log also shows red. Good indication for you to tap on logs and investigate
* **G**: This defines how Zoneminder will record events. There are various modes. In brief Modect == record if a motion is detected,Record = always record 24x7, Mocord = always record PLUS detect motion,  Monitor = just provide a live view but don't record anytime, Modect = Don't record till an externa entity via zmtrigger tells Zoneminder to (this is advanced usage).
* **H**: If you click on these links you can view a "Montage" of all your configured monitors or cycle through each one


Adding Monitors
^^^^^^^^^^^^^^^
Now that we have a basic understanding of the web console, lets go about adding a new camera (monitor). For this example, lets assume we have an IP camera that streams RTSP at LAN IP address 192.168.1.17. 

The first thing we will need to know is how to access that camera's video feed.





