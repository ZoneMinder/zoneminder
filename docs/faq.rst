FAQ
=====

.. todo:: needs to be reviewed - some entries may be old/invalid. I've done one round, but icOn needs to review.

This is the FAQ page. Feel free to contribute any FAQs that you think are missing.

.. note:: It is always a good idea to refer to the `ZoneMinder forums <https://forums.zoneminder.com/viewforum.php?f=26&sid=fc93f65726d3a29fbf6f610b21f52b16>`__ for tips and tricks. While we try and make sure this FAQ is pruned/adjusted to align with the latest stable release, some of the entries may no longer be accurate (or there may be better suggestions in the forums).

How can I stop ZoneMinder filling up my disk?
---------------------------------------------

Recent versions of ZoneMinder come with a filter you can use for this purpose already included. 
The filter is called **PurgeWhenFull** and to find it, choose one of the event counts from the console page, for instance events in the last hour, for one of your monitors. **Note** that this filter is automatically enabled if you do a fresh install of ZoneMinder including creating a new database. If you already have an existing database and are upgrading ZoneMinder, it will retain the settings of the filter (which in earlier releases was disabled by default). So you may want to check if PurgeWhenFull is enabled and if not, enable it.

To enable it, go to Web Console, click on any of your Events of any of your monitors.
This will bring up an event listing and a filter window.

In the filter window there is a drop down select box labeled 'Use Filter', that lets your select a saved filter. Select 'PurgeWhenFull' and it will load that filter.

Make any modifications you might want, such as the percentage full you want it to kick in, or how many events to delete at a time (it will repeat the filter as many times as needed to clear the space, but will only delete this many events each time to get there).

Then click on 'Save' which will bring up a new window. Make sure the 'Automatically delete' box is checked and press save to save your filter. This will then run in the background to keep your disk within those limits.

After you've done that, you changes will automatically be loaded into zmfilter within a few minutes.

Check the ``zmfilter.log`` file to make sure it is running as sometimes missing perl modules mean that it never runs but people don't always realize.

**Purge By Age**
To delete events that are older than 7 days, create a new filter with "Date" set to "less than" and a value of "-7 days", sort by "date/time" in "asc"ending order, then enable the checkbox "delete all matches". You can also use a value of week or week and days: "-2 week"  or "-2 week 4 day"

Save with 'Run Filter In Background' enabled to have it run automatically.
Optional skip archived events:  click on the plus sign next to -7 days to add another condition.  "and" "archive status" equal to "unarchived only".

Optional slow delete:  limit the number of results to a number, say ``10`` in the filter.  If you have a large backlog of events that would be deleted, this can hard spike the CPU usage for a long time.  Limiting the number of results to only the first three each time the filter is run spreads out the delete processes over time, dramatically lessening the CPU load.


.. warning:: We no longer recommend use enable ``OPT_FAST_DELETE`` or ``RUN_AUDIT`` anymore, unless you are using an old or low powered system to run Zoneminder. Please consider the remaining tips in this answer to be 'generally deprecated, use only if you must'.

There are two methods for ZM to remove files when they are deleted that can be found in Options under the System tab ZM_OPT_FAST_DELETE and ZM_RUN_AUDIT.


ZM_OPT_FAST_DELETE:

Normally an event created as the result of an alarm consists of entries in one or more database tables plus the various files associated with it. When deleting events in the browser it can take a long time to remove all of this if you are trying to do a lot of events at once. If you are running on an older or under-powered system, you may want to set this option which means that the browser client only deletes the key entries in the events table, which means the events will no longer appear in the listing, and leaves the zmaudit daemon to clear up the rest later. If you do so, disk space will not be freed immediately so you will need to run zmaudit more frequently.  On modern systems, we recommend that you leave this **off**.

ZM_RUN_AUDIT:

The zmaudit daemon exists to check that the saved information in the database and on the file system match and are consistent with each other. If an error occurs or if you are using 'fast deletes' it may be that database records are deleted but files remain. In this case, and similar, zmaudit will remove redundant information to synchronize the two data stores. This option controls whether zmaudit is run in the background and performs these checks and fixes continuously. This is recommended for most systems however if you have a very large number of events the process of scanning the database and file system may take a long time and impact performance. In this case you may prefer to not have zmaudit running unconditionally and schedule occasional checks at other, more convenient, times.

ZM_AUDIT_CHECK_INTERVAL:

The zmaudit daemon exists to check that the saved information in the database and on the files system match and are consistent with each other. If an error occurs or if you are using 'fast deletes' it may be that database records are deleted but files remain. In this case, and similar, zmaudit will remove redundant information to synchronize the two data stores. The default check interval of 900 seconds (15 minutes) is fine for most systems however if you have a very large number of events the process of scanning the database and file system may take a long time and impact performance. In this case you may prefer to make this interval much larger to reduce the impact on your system. This option determines how often these checks are performed.


Math for Memory: Making sure you have enough memory to handle your cameras
---------------------------------------------------------------------------
One of the most common issues for erratic ZoneMinder behavior is you don't have enough memory to handle all your cameras. Many users often configure multiple HD cameras at full resolution and 15FPS or more and then face various issues about processes failing, blank screens and other completely erratic behavior. The core reason for all of this is you either don't have enough memory or horsepower to handle all your cameras. The solution often is to reduce FPS, reduce cameras or bump up your server capabilities.

Here are some guidelines with examples on how you can figure out how much memory you need. With respect to CPU, you should benchmark your server using standard unix tools like top, iotop and others to make sure your CPU load is manageable. ZoneMinder also shows average load on the top right corner of the Web Console for easy access.

In *general* a good estimate of memory required would be:

::

	Min Bits of Memory = 20% overhead * (image-width*image-height*image buffer size*target color space*number of cameras) 

Where:

* image-width and image-height are the width and height of images that your camera is configured for (in my case, 1280x960). This value is in the Source tab for each monitor
* image buffer size is the # of images ZM will keep in memory (this is used by ZM to make sure it has pre and post images before detecting an alarm - very useful because by the time an alarm is detected, the reason for the alarm may move out of view and a buffer is really useful for this, including for analyzing stats/scores). This value is in the buffers tab for each monitor
* target color space is the color depth - 8bit, 24bit or 32bit. It's again in the source tab of each monitor

The 20% overhead on top of the calculation to account for image/stream overheads (this is an estimate)

The math breakdown for 4 cameras running at 1280x960 capture, 50 frame buffer, 24 bit color space:

::

	1280*960 = 1,228,800 (bytes)
	1,228,800 * (3 bytes for 24 bit) = 3,686,400 (bytes) 
	3,686,400 * 50 = 184,320,000 (bytes)
	184,320,000 * 4 = 737,280,000 (bytes)
	737,280,000 / 1024 = 720,000 (Kilobytes)
	720,000 / 1024 = 703.125 (Megabytes)
	703.125 / 1024 = 0.686 (Gigabytes)

Around 700MB of memory.

So if you have 2GB of memory, you should be all set. Right? **Not, really**:

	* This is just the base memory required to capture the streams. Remember ZM is always capturing streams irrespective of whether you are actually recording or not - to make sure its image ring buffer is there with pre images when an alarm kicks in.
	* You also need to account for other processes not related to ZM running in your box
	* You also need to account for other ZM processes - for example, I noticed the audit daemon takes up a good amount of memory when it runs, DB updates also take up memory
	* If you are using H264 encoding, that buffers a lot of frames in memory as well.

So a good rule of thumb is to make sure you have twice the memory as the calculation above (and if you are using the ZM server for other purposes, please factor in those memory requirements as well)

**Also remember by default ZM only uses 50% of your available memory unless you change it**

As it turns out, ZM uses mapped memory and by default, 50% of your physical memory is what this will grow to. When you reach that limit , ZM breaks down with various errors.


A good way to know how much memory is allocated to ZM for its operation is to do a ``df -h``

A sample output on Ubuntu:

::

	pp@camerapc:~$ df -h|grep "Filesystem\|shm"
	Filesystem                 Size  Used Avail Use% Mounted on
	tmpfs                      2.6G  923M  1.7G  36% /run/shm


The key item here is tmpfs --> the example above shows we have allocated 1.7G of mapped memory space of which 36% is used which is a healthy number. If you are seeing ``Use%`` going beyond 70% you should probaby increase the mapped memory.

For example, if you want to increase this limit to 70% of your memory, add the following to ``/etc/fstab``
``tmpfs SHMPATH tmpfs defaults,noexec,nosuid,size=70% 0 0``
where SHMPATH is the ``Mounted on`` path.  Here, that would be ``/run/shm``.  Other systems may be ``/dev/shm``.



I have enabled motion detection but it is not always being triggered when things happen in the camera view
---------------------------------------------------------------------------------------------------------------

ZoneMinder uses zones to examine images for motion detection. When you create the initial zones you can choose from a number of preset values for sensitivity etc. Whilst these are usually a good starting point they are not always suitable for all situations and you will probably need to tweak the values for your specific circumstances. The meanings of the various settings are described in the documentation (`here <https://zoneminder.readthedocs.io/en/latest/userguide/definezone.html>`__). Another user contributed illustrated Zone definition guide can be found here: `An illustrated guide to Zones <https://wiki.zoneminder.com/index.php/Understanding_ZoneMinder%27s_Zoning_system_for_Dummies>`__

However if you believe you have sensible settings configured then there are diagnostic approaches you can use. 


Event Statistics
^^^^^^^^^^^^^^^^^
The first technique is to use event statistics. Firstly you should ensure they are switched on in ``Options->Logging->RECORD_EVENT_STATS``. This will then cause the raw motion detection statistics for any subsequently generated events to be written to the DB. These can then be accessed by first clicking on the Frames or Alarm Frames values of the event from any event list view in the web gui. Then click on the score value to see the actual values that caused the event. Alternatively the stats can be accessed by clicking on the 'Stats' link when viewing any individual frame. The values displayed there correspond with the values that are used in the zone configuration and give you an idea of what 'real world' values are being generated. 

Note that if you are investigating why events 'do not' happen then these will not be saved and so won't be accessible. The best thing to do in that circumstance is to make your zone more sensitive so that it captures all events (perhap even ones you don't want) so you can get an idea of what values are being generated and then start to adjust back to less sensitive settings if necessary. You should make sure you test your settings under a variety of lighting conditions (e.g. day and night, sunny or dull) to get the best feel for that works and what doesn't.

Using statistics will slow your system down to a small degree and use a little extra disk space in the DB so once you are happy you can switch them off again. However it is perfectly feasible to keep them permanently on if your system is able to cope which will allow you to review your setting periodically.

Diagnostic Images along with FIFO
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
The second approach is to use diagnostic images which are saved copies of the intermediate images that ZM uses when determining motion detection. These are switched on and off using ``Options->Logging->RECORD_DIAG_IMAGES``.

.. note:: In addition to the detailed explanation below, a recently added ``RECORD_DIAG_IMAGES_FIFO`` option, also available in ``Options->Logging`` can be an invaluable tool to see how your current motion settings are affecting motion detection. The ``delta`` stream along with the ``raw`` (json output) stream can be invaluable to see the effect in real time. Please refer to the explanation of this feature in :doc:`/userguide/options/options_logging`

There are two kinds of diagnostic images which are and are written (and continuously overwritten) to the top level monitor event directory. If an event occurs then the files are additionally copied to the event directory and renamed with the appropriate frame number as a prefix.

The first set are produced by the monitor on the image as a whole. The ``diag-r.jpg`` image is the current reference image against which all individual frames are compared and the ``diag-d.jpg`` image is the delta image highlighting the difference between the reference image and the last analysed image. In this images identical pixels will be black and the more different a pixel is the whiter it will be. Viewing this image and determining the colour of the pixels is a good way of getting a feel for the pixel differences you might expect (often more than you think).

The second set of diag images are labelled as ``diag-<zoneid>-<stage>.jpg`` where zoneid is the id of the zone in question (Smile) and the stage is where in the alarm check process the image is generated from. So if you have several zones you can expect to see multiple files. Also these files are only interested in what is happening in their zone only and will ignore anything else outside of the zone. The stages that each number represents are as follows,

* Alarmed Pixels - This image shows all pixels in the zone that are considered to be alarmed as white pixels and all other pixels as black.
* Filtered Pixels - This is as stage one except that all pixels removed by the filters are now black. The white pixels represent the pixels that are candidates to generate an event.
* Raw Blobs - This image contains all alarmed pixels from stage 2 but aggrageted into blobs. Each blob will have a different greyscale value (between 1 and 254) so they can be difficult to spot with the naked eye but using a colour picker or photoshop will make it easier to see what blob is what.
* Filtered Blobs - This image is as stage 3 but under (or over) sized blobs have been removed. This is the final step before determining if an event has occurred, just prior to the number of blobs being counted. Thus this image forms the basis for determining whether an event is generated and outlining on alarmed images is done from the blobs in this image.

Using the above images you should be able to tell at all stages what ZM is doing to determine if an event should happen or not. They are useful diagnostic tools but as is mentioned elsewhere they will massively slow your system down and take up a great deal more space. You should never leave ZM running for any length of time with diagnostic images on.


Why can't ZoneMinder capture images (either at all or just particularly fast) when I can see my camera just fine in xawtv or similar?
----------------------------------------------------------------------------------------------------------------------------------------------

With capture cards ZoneMinder will pull images as fast as it possibly can unless limited by configuration. ZoneMinder (and any similar application) uses the frame grabber interface to copy frames from video memory into user memory. This takes some time, plus if you have several inputs sharing one capture chip it has to switch between inputs between captures which further slows things down.

On average a card that can capture at 25fps per chip PAL for one input will do maybe 6-10fps for two, 1-4fps for three and 1-2 for four. For a 30fps NTSC chip the figures will be correspondingly higher. However sometimes it is necessary to slow down capture even further as after an input switch it may take a short while for the new image to settle before it can be captured without corruption.

When using xawtv etc to view the stream you are not looking at an image captured using the frame grabber but the card's video memory mapped onto your screen. This requires no capture or processing unless you do an explicit capture via the J or ctrl-J keys for instance. Some cards or drivers do not support the frame grabber interface at all so may not work with ZoneMinder even though you can view the stream in xawtv. If you can grab a still using the grab functionality of xawtv then in general your card will work with ZoneMinder.

Why can't I see streamed images when I can see stills in the zone window etc?
-------------------------------------------------------------------------------------

This issue is normally down to one of two causes

1) You are using Internet Explorer and are trying to view multi-part jpeg streams. IE does not support these streams directly, unlike most other browsers. You will need to install Cambozola or another multi-part jpeg aware plugin to view them. To do this you will need to obtain the applet from the Downloads page and install the cambozola.jar file in the same directory as the ZoneMinder php files. Then find the ZoneMinder Options->Images page and enable ``OPT_CAMBOZOLA`` and enter the web path to the .jar file in ``PATH_CAMBOZOLA``. This will ordinarily just be cambozola.jar. Provided (Options / B/W tabs) ``WEB_H_CAN_STREAM`` is set to auto and ``WEB_H_STREAM_METHOD`` is set to jpeg then Cambozola should be loaded next time you try and view a stream.

**NOTE**: If you find that the Cambozola applet loads in IE but the applet just displays the version  of Cambozola and the author's name (as opposed to seeing the streaming images), you may need to chmod (``-rwxrwxr-x``) your (``usr/share/zoneminder/``) cambozola.jar:

::

	sudo chmod 775 cambozola.jar

Once I did this, images started to stream for me.

2) The other common cause for being unable to view streams is that you have installed the ZoneMinder cgi binaries (zms and nph-zms) in a different directory than your web server is expecting. Make sure that the --with-cgidir option you use to the ZoneMinder configure script is the same as the CGI directory configure for your web server. If you are using Apache, which is the most common one, then in your httpd.conf file there should be a line like ``ScriptAlias /cgi-bin/ "/var/www/cgi-bin/"`` where the last directory in the quotes is the one you have specified. If not then change one or the other to match. Be warned that configuring apache can be complex so changing the one passed to the ZoneMinder configure (and then rebuilding and reinstalling) is recommended in the first instance. If you change the apache config you will need to restart apache for the changes to take effect. If you still cannot see stream reliably then try changing ``ZM_PATH_ZMS`` in your ``/etc/zm/config`` directory to just use zms if nph-zms is specified, or vice versa. Also check in your apache error logs.

Lastly, please look for errors created by the zmc processes.  If zmc isn't running, then zms will not be able to get an image from it and will exit.

I have several monitors configured but when I load the Montage view in FireFox why can I only see two? or, Why don't all my cameras display when I use the Montage view in FireFox?
--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

By default FireFox only supports a small number of simultaneous connections. Using the montage view usually requires one persistent connection for each camera plus intermittent connections for other information such as statuses.

You will need to increase the number of allowed connections to use the montage view with more than a small number of cameras.  Certain FireFox extensions such as FasterFox may also help to achieve the same result.

To resolve this situation, follow the instructions below:

Enter ``about:config`` in the address bar

scroll down to
``browser.cache.check_doc_frequency 3``
change the 3 to a 1

::

	browser.cache.disk.enable True -> False
	network.http.max-connections-per-server -> put a value of 100
	network.http.max-persistent-connections-per-proxy -> 100 again
	network.http.max-persistent-connections-per-server -> 100 again


I can't see more than 6 monitors in montage on my browser
---------------------------------------------------------
Browsers such a Chrome and Safari only support upto 6 streams from the same domain. To work around that, take a look at the multi-port configuration discussed in  the ``MIN_STREAMING_PORT`` configuration in :doc:`/userguide/options/options_network`

Why is ZoneMinder using so much CPU?
---------------------------------------

The various elements of ZoneMinder can be involved in some pretty intensive activity, especially while analysing images for motion. However generally this should not overwhelm your machine unless it is very old or underpowered.

There are a number of specific reasons why processor loads can be high either by design or by accident. To figure out exactly what is causing it in your circumstances requires a bit of experimentation.

The main causes are.

	* Using a video palette other than greyscale or RGB24. This can cause a relatively minor performance hit, though still significant. Although some cameras and cards require using planar palettes ZM currently doesn't support this format internally and each frame is converted to an RGB representation prior to processing. Unless you have compelling reasons for using YUV or reduced RGB type palettes such as hitting USB transfer limits I would experiment to see if RGB24 or greyscale is quicker. Put your monitors into 'Monitor' mode so that only the capture daemons are running and monitor the process load of these (the 'zmc' processes) using top. Try it with various palettes to see if it makes a difference.
	* Big image sizes. A image of 640x480 requires at least four times the processing of a 320x240 image. Experiment with different sizes to see what effect it may have. Sometimes a large image is just two interlaced smaller frames so has no real benefit anyway. This is especially true for analog cameras/cards as image height over 320 (NTSC) or 352 PAL) are invariably interlaced.
	* Capture frame rates. Unless there's a compelling reason in your case there is often little benefit in running cameras at 25fps when 5-10fps would often get you results just as good. Try changing your monitor settings to limit your cameras to lower frame rates. You can still configure ZM to ignore these limits and capture as fast as possible when motion is detected.
	* Run function. Obviously running in Record or Mocord modes or in Modect with lots of events generates a lot of DB and file activity and so CPU and load will increase.
	*  Basic default detection zones. By default when a camera is added one detection zone is added which covers the whole image with a default set of parameters. If your camera covers a view in which various regions are unlikely to generate a valid alarm (ie the sky) then I would experiment with reducing the zone sizes or adding inactive zones to blank out areas you don't want to monitor. Additionally the actual settings of the zone themselves may not be optimal. When doing motion detection the number of changed pixels above a threshold is examined, then this is filter, then contiguous regions are calculated to see if an alarm is generated. If any maximum or minimum threshold is exceeded according to your zone settings at any time the calculation stops. If your settings always result in the calculations going through to the last stage before being failed then additional CPU time is used unnecessarily. Make sure your maximum and minimumzone thresholds are set to sensible values and experiment by switching ``RECORD_EVENT_STATS`` on and seeing what the actual values of alarmed pixels etc are during sample events.
	* Optimise your settings. After you've got some settings you're happy with then switching off ``RECORD_EVENT_STATS`` will prevent the statistics being written to the database which saves some time. Other settings which might make a difference are ``ZM_FAST_RGB_DIFFS`` and the ``JPEG_xxx_QUALITY`` ones.

I'm sure there are other things which might make a difference such as what else you have running on the box and memory sizes (make sure there's no swapping going on). Also speed of disk etc will make some difference during event capture and also if you are watching the whole time then you may have a bunch of zms processes running also.

I think the biggest factors are image size, colour depth and capture rate. Having said that I also don't always know why you get certains results from 'top'. For instance if I have a 'zma' daemon running for a monitor that is capturing an image. I've commented out the actual analysis so all it's doing is blending the image with the previous one. In colour mode this takes ~11 milliseconds per frame on my system and the camera is capturing at ~10fps. Using 'top' this reports the process as using ~5% of CPU and permanently in R(un) state. Changing to greyscale mode the blending takes ~4msec (as you would expect as this is roughly a third of 11) but top reports the process as now with 0% CPU and permanently in S(leep) state. So an actual CPU resource usage change of a factor of 3 causes huge differences in reported CPU usage. I have yet to get to the bottom of this but I suspect it's to do with scheduling somewhere along the line and that maybe the greyscale processing will fit into one scheduling time slice whereas the colour one won't but I have no evidence of this yet!

Why is the timeline view all messed up?
-----------------------------------------

The timeline view is a new view allowing you to see a graph of alarm activity over time and to quickly scan and home in on events of interest. However this feature is highly complex and still in beta. It is based extensively on HTML div tags, sometimes lots of them. Whilst FireFox is able to render this view successfully other browsers, particular Internet Explorer do not seem able to cope and so present a messed up view, either always or when there are a lot of events.
Using the timeline view is only recommended when using FireFox, however even then there may be issues.

This function has from time to time been corrupted in the SVN release or in the stable releases, try and reinstall from a fresh download.

.. _disk_bw_faq:

How much Hard Disk Space / Bandwidth do I need for ZM?
---------------------------------------------------------------
Please see `this online excel sheet <https://docs.google.com/spreadsheets/d/1iPMCGwlXiW8WnQuewgFhUqBs7ZaPKqXDkBtCQVHR6H0/edit?usp=sharing>`__. Note that this is just an estimate

Or go to `this link <http://www.axis.com/products/video/design_tool/v2/>`__ for the Axis bandwidth calculator. Although this is aimed at Axis cameras it still produces valid results for any kind of IP camera.

As a quick guide I have 4 cameras at 320x240 storing 1 fps except during alarm events. After 1 week 60GB of space in the volume where the events are stored (/var/www/html/zm) has been used.

When I try and run ZoneMinder I get lots of audit permission errors in the logs and it won't start
-------------------------------------------------------------------------------------------------------
Many Linux distributions nowadays are built with security in mind. One of the latest methods of achieving this is via SELinux (Secure Linux) which controls who is able to run what in a more precise way then traditional accounting and file based permissions (`link <https://en.wikipedia.org/wiki/Selinux>`__).
If you are seeing entries in your system log like:

   Jun 11 20:44:02 kernel: audit(1150033442.443:226): avc: denied { read } for pid=5068
   comm="uptime" name="utmp" dev=dm-0 ino=16908345 scontext=user_u:system_r:httpd_sys_script_t
   tcontext=user_u:object_r:initrc_var_run_t tclass=file

then it is likely that your system has SELinux enabled and it is preventing ZoneMinder from performaing certain activities. You then have two choices. You can either tune SELinux to permit the required operations or you can disable SELinux entirely which will permit ZoneMinder to run unhindered. Disabling SELinux is usually performed by editing its configuration file (e.g., ``/etc/selinux/config``) and then rebooting. However if you run a public server you should read up on the risks associated with disabled Secure Linux before disabling it.

Note that SELinux may cause errors other than those listed above. If you are in any doubt then it can be worth disabling SELinux experimentally to see if it fixes your problem before trying other solutions.

How do I enable ZoneMinder's security?
-------------------------------------------
In the console, click on ``Options->System``. Check the box next to ``ZM_OPT_USE_AUTH``. You will immediately be asked to login. The default username is 'admin' and the password is 'admin'.

To Manage Users:
In main console, go to ``Options->Users``.

You may also consider to use the web server security, for example, htaccess files under Apache scope; You may even use this as an additional/redundant security on top of Zoneminders built-in security features. Note that if you choose to enable webserver auth, zmNinja may have issues. Please read the `zmNinja FAQ on basic authentication <https://zmninja.readthedocs.io/en/latest/guides/FAQ.html#i-can-t-see-streams-i-use-basic-auth>`__ for more information. Also please note that zmNinja does not support digest authentication.


Managing system load (with IP Cameras in mind)
----------------------------------------------------

Introduction
^^^^^^^^^^^^^^^
Zoneminder is a superb application in every way, but it does a job that needs a lot of horsepower especially when using multiple IP cameras. IP Cams require an extra level of processing to analogue cards as the jpg or mjpeg images need to be decoded before analysing. This needs grunt. If you have lots of cameras, you need lots of grunt.

Why do ZM need so much grunt?
Think what Zoneminder is actually doing. In modect mode ZM is:
1. Fetching a jpeg from the camera. (Either in single part or multipart stream)
2. Decoding the jpeg image. 
3. Comparing the zoned selections to the previous image or images and applying rules.
4. If in alarm state, writing that image to the disk and updating the mysql database.

If you're capturing at five frames per second, the above is repeated five times every second, multiplied by the number of cameras. Decoding the images is what takes the real power from the processor and this is the main reason why analogue cameras which present an image ready-decoded in memory take less work.

How do I know if my computer is overloaded?
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
If your CPU is running at 100% all the time, it's probably overloaded (or running at exact optimisation). If the load is consistently high (over 10.0 for a single processor) then Bad Things happen - like lost frames, unrecorded events etc. Occasional peaks are fine, normal and nothing to worry about.

Zoneminder runs on Linux, Linux measures system load using "load", which is complicated but gives a rough guide on what the computer is doing at any given time. Zoneminder shows Load on the main page (top right) as well as disk space. Typing "uptime" on the command line will give a similar guide, but with three figures to give a fuller measure of what's happening over a period of time but for the best guide to see what's happening, install "htop" - which gives easy to read graphs for load, memory and cpu usage.

A load of 1.0 means the processor has "just enough to do right now". Also worth noting that a load of 4.0 means exactly the same for a quad processor machine - each number equals a single processor's workload. A very high load can be fine on a computer that has a stacked workload - such as a machine sending out bulk emails, or working its way through a knotty problem; it'll just keep churning away until it's done. However - Zoneminder needs to process information in real time so it can't afford to stack its jobs, it needs to deal with them right away.

For a better and full explanation of Load: `Please read this <https://en.wikipedia.org/wiki/Load_%28computing%29>`__

My load is too high, how can I reduce it?
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

(The previous documentation explained how to use turbo jpeg libraries as an optimization technique. These libraries have long been part of standard linux distros since that article was authored and hence that section has been removed)

Zoneminder is *very* tweakable and it's possible to tune it to compromise. The following are good things to try, in no particular order;

	* If your camera allows you to change image size, think whether you can get away with smaller images. Smaller pics = less load. 320x240 is usually ok for close-up corridor shots.

	* Go Black and White. Colour pictures use twice to three times the CPU, memory and diskspace but give little benefit to identification.

	* Reduce frames per second. Halve the fps, halve the workload. If your camera supports fps throttling (Axis do), try that - saves ZM having to drop frames from a stream. 2-5 fps seems to be widely used.

	* Experiment with using jpeg instead of mjpeg. Some users have reported it gives better performance, but YMMV.

	* Tweak the zones. Keep them as small and as few as possible. Stick to one zone unless you really need more. Read `this <https://wiki.zoneminder.com/index.php/Understanding_ZoneMinder%27s_Zoning_system_for_Dummies>`__ for an easy to understand explanation along with the official Zone guide.

	* Schedule. If you are running a linux system at near capacity, you'll need to think carefully about things like backups and scheduled tasks. updatedb - the process which maintains a file database so that 'locate' works quickly, is normally scheduled to run once a day and if on a busy system can create a heavy increase on the load. The same is true for scheduled backups, especially those which compress the files. Re-schedule these tasks to a time when the cpu is less likely to be busy, if possible - and also use the "nice" command to reduce their priority. (crontab and /etc/cron.daily/ are good places to start)

	* Reduce clutter on your PC. Don't run X unless you really need it, the GUI is a huge overhead in both memory and cpu.

More expensive options:

	* Increase RAM. If your system is having to use disk swap it will HUGELY impact performance in all areas. Again, htop is a good monitor - but first you need to understand that because Linux is using all the memory, it doesn't mean it needs it all - linux handles ram very differently to Windows/DOS and caches stuff. htop will show cached ram as a different colour in the memory graph. Also check that you're actually using a high memory capable kernel - many kernels don't enable high memory by default. 

	* Faster CPU. Simple but effective. Zoneminder also works very well with multiple processor systems out of the box (if SMP is enabled in your kernel). The load of different cameras is spread across the processors.


	* Try building Zoneminder with processor specific instructions that are optimised to the system it will be running on, also increasing the optimisation level of GCC beyond -O2 will help.  This topic is beyond the scope of this document.

Processor specific commands can be found in the `GCC manual <https://gcc.gnu.org/onlinedocs/gcc/>`__ along with some more options that may increase performance. 


What about disks and bandwidth?
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
A typical 100mbit LAN will cope with most setups easily. If you're feeding from cameras over smaller or internet links, obviously fps will be much lower.

Disk and Bandwidth calculators are referenced in :ref:`disk_bw_faq`.

How do I build for X10 support?
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

You do not need to rebuild ZM for X10 support. You will need to install the perl module and switch on X10 in the options, then restart. Installing the perl module is covered in the README amongst other places but in summary, do:

 perl -MCPAN -eshell
 install X10::ActiveHome
 quit

Extending Zoneminder
------------------------
.. _runstate_cron_example:

How can I get ZM to do different things at different times of day or week?
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

If you want to configure ZoneMinder to do motion detection during the day and just record at night, for example, you will need to use ZoneMinder 'run states'. A run state is a particular configuration of monitor functions that you want to use at any time.

To save a run state you should first configure your monitors for Modect, Record, Monitor etc as you would want them during one of the times of day. Then click on the running state link at the top of the Console view. This will usually say 'Running' or 'Stopped'. You will then be able to save the current state and give it a name, 'Daytime' for example. Now configure your monitors how you would want them during other times of day and save that, for instance as 'Nighttime'.

Now you can switch between these two states by selecting them from the same dialog you saved them, or from the command line from issue the command ''zmpkg.pl <run state>'', for example ''zmpkg.pl Daytime''.

The final step you need to take, is scheduling the time the changes take effect. For this you can use `cron <https://en.wikipedia.org/wiki/Cron>`__. A simple entry to change to the Daylight state at at 8am and to the nighttime state at 8pm would be as follows,

::

	0 8 * * * root /usr/local/bin/zmpkg.pl Daytime
	0 20 * * * root /usr/local/bin/zmpkg.pl Nighttime

On Ubuntu 7.04 and possibly others, look in /usr/bin not just /usr/local/bin for the zmpkg.pl file.

Although the example above describes changing states at different times of day, the same principle can equally be applied to days of the week or other more arbitrary periods.


How can I use ZoneMinder to trigger something else when there is an alarm?
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
ZoneMinder includes a perl API which means you can create a script to interact with the ZM shared memory data and use it in your own scripts to react to ZM alarms or to trigger ZM to generate new alarms. Full details are in the README or by doing ``perldoc ZoneMinder``  etc.

ZoneMinder provides a sample alarm script called `zmalarm.pl <https://github.com/ZoneMinder/zoneminder/blob/master/utils/zm-alarm.pl>`__ that you can refer to as a starting point.


Trouble Shooting
-------------------
Here are some things that will help you track down whats wrong.
This is also how to obtain the info that we need to help you on the forums.

What logs should I check for errors?
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
ZoneMinder creates its own logs and are usually located in the ``/var/log/`` directory. Refer to the logging discussion in :doc:`/userguide/options/options_logging` for more details on where logs are stored and how to enable various log levels.

Since ZM is dependent on other components to work, you might not find errors in ZM but in the other components.

:: 

	*/var/log/messages and/or /var/log/syslog
	*/var/log/dmesg
	*/var/log/httpd/error_log`` (RedHat/Fedora) or ``/var/log/apache2/error_log
	*/var/log/mysqld.log`` (Errors here don't happen very often but just in case)

If ZM is not functioning, you should always be able to find an error in at least one of these logs. Use the [[tail]] command to get info from the logs. This can be done like so: 

  tail -f /var/log/messages /var/log/httpd/error_log /var/log/zm/zm*.log

This will append any data entered to any of these logs to your console screen (``-f``). To exit, hit [ctrl -c].


How can I trouble shoot the hardware and/or software?
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Here are some commands to get information about your hardware. Some commands are distribution dependent.
* ``[[lspci]] -vv`` -- Returns lots of detailed info. Check for conflicting interrupts or port assignments. You can sometimes alter interrupts/ ports in bios. Try a different pci slot to get a clue if it is HW conflict (command provided by the pciutils package).
* ``[[scanpci]] -v``  -- Gives you information from your hardware EPROM
* ``[[lsusb]] -vv`` -- Returns lots of detail about USB devices (camand provided by usbutils package).
* ``[[dmesg]]`` -- Shows you how your hardware initialized (or didn't) on boot-up. You will get the most use of this.
* ``[[v4l-info]]`` -- to see how driver is talking to card. look for unusual values.
* ``[[modinfo bttv]]`` -- some bttv driver stats.
* ``[[zmu]]  -m 0 -q -v`` -- Returns various information regarding a monitor configuration.
* ``[[ipcs]] ``  -- Provides information on the ipc facilities for which the calling process has read access.
* ``[[ipcrm]] ``  -- The ipcrm command can be used to remove an IPC object from the kernel.
* ``cat /proc/interrupts``  -- This will dispaly what interrupts your hardware is using.

Why am I getting a 403 access error with my web browser when trying to access http //localhost/zm?
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

The apache web server needs to have the right permissions and configuration to be able to read the Zoneminder files. Check the forums for solution, and edit the apache configuration and change directory permissions to give apache the right to read the Zoneminder files. Depending on your Zoneminder configuration, you would use the zm user and group that Zoneminder was built with, such as wwwuser and www.

Why am I getting broken images when trying to view events?
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Zoneminder and the Apache web server need to have the right permissions. Check `this forum topic <https://forums.zoneminder.com/viewtopic.php?p=48754>`__ and similar ones:



I can review events for the current day, but ones from yesterday and beyond error out
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

If you've checked that the ``www-data`` user has permissions to the storage folders, perhaps your php.ini's timezone setting is incorrect. They _must_ match for certain playback functions. 

If you're using Linux, this can be found using the following command: 

::

  timedatectl | grep "Time zone"

If using FreeBSD, you can use this one-liner: 

::

  cd /usr/share/zoneinfo/ && find * -type f -exec cmp -s {} /etc/localtime \; -print;
  
Once you know what timezone your system is set to make sure you set the right time zone in ZM (Available in ``Options->System->TimeZone``)


Why is the image from my color camera appearing in black and white?
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
If you recently upgraded to zoneminder 1.26, there is a per camera option that defaults to black and white and can be mis-set if your upgrade didn't happen right. See this thread: https://forums.zoneminder.com/viewtopic.php?f=30&t=21344

This may occur if you have a NTSC analog camera but have configured the source in ZoneMinder as PAL for the Device Format under the source tab.  You may also be mislead because zmu can report the video port as being PAL when the camera is actually NTSC.  Confirm the format of your analog camera by checking it's technical specifications, possibly found with the packaging it came in, on the manufacturers website, or even on the retail website where you purchased the camera.  Change the Device Format setting to NTSC and set it to the lowest resolution of 320 x 240.  If you have confirmed that the camera itself is NTSC format, but don't get a picture using the NTSC setting, consider increasing the shared memory '''kernel.shmall''' and '''kernel.shmmax''' settings in /etc/sysctl.conf to a larger value such as 268435456.  This is also the reason you should start with the 320x240 resolution, so as to minimize the potential of memory problems which would interfere with your attempts to troubleshoot the device format issue.  Once you have obtained a picture in the monitor using the NTSC format, then you can experiment with raising the resolution.

Why do I only see blue screens with a timestamp when monitoring my camera?
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
If this camera is attached to a capture card, then you may have selected the wrong Device Source or Channel when configuring the monitor in the ZoneMinder console.  If you have a capture card with 2 D-sub style inputs(looks like a VGA port) to which you attach a provided splitter that splits off multiple cables, then the splitter may be attached to the wrong port.  For example, PV-149 capture cards have two D-sub style ports labeled as DB1 and DB2, and come packaged with a connector for one of these ports that splits into 4 BNC connecters.  The initial four video ports are available with the splitter attached to DB1.

Why do I only see black screens with a timestamp when monitoring my camera?
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
In the monitor windows where you see the black screen with a timestamp, select settings and enter the Brightness, Contrast, Hue, and Color settings reported for the device by ``zmu -d <device_path> -q -v``.  32768 may be appropriate values to try for these settings.  After saving the settings, select Settings again to confirm they saved successfully.


How do I repair the MySQL Database?
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
There is two ways to go about this. In most cases you can run from the command prompt ->
``mysqlcheck --all-databases --auto-repair -p your_database_password -u your_databse_user``

If that does not work then you will have to make sure that ZoneMinder is stopped then run the following (nothing should be using the database while running this and you will have to adjust for your correct path if it is different):

``myisamchk --silent --force --fast --update-state -O key_buffer=64M -O sort_buffer=64M -O read_buffer=1M -O write_buffer=1M /var/lib/mysql/*/*.MYI``


How do I repair the MySQL Database when the cli fails?
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
In Ubuntu, the commands listed above do not seem to work.  However, actually doing it by hand from within MySQL does.  (But that is beyond the scope of this document)  But that got me thinking...  And phpmyadmin does work.  Bring up a terminal.
``sudo apt-get install phpmyadmin``

Now go to ``http://zoneminder_IP/`` and stop the ZM service.  Continue to ``http://zoneminder_IP/phpmyadmin`` and select the zoneminder database.  Select and tables marked 'in use' and pick the action 'repare' to fix.  Restart the zoneminder service from the web browser.  Remove or disable the phpmyadmin tool, as it is not always the most secure thing around, and opens your database wide to any skilled hacker.
``sudo apt-get remove phpmyadmin``

I upgraded by distribution and ZM stopped working
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
Some possibilities (Incomplete list and subject to correction)
``[[/usr/local/bin/zmfix: /usr/lib/libmysqlclient.so.15: version `MYSQL_5.0' not found (required by /usr/local/bin/zmfix)]]``  :: Solution: Recompile and reinstall Zoneminder.
Any time you update a major version that ZoneMinder depends on, you need to recompile ZoneMinder.

Zoneminder doesn't start automatically on boot
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
Check the list for log entries like "zmfix[766]: ERR [Can't connect to server: Can't connect to local MySQL server through socket '/var/run/mysqld/mysqld.sock' (2)] ". 
What can happen is that zoneminder is started too quickly after Mysql and tries to contact the database server before it's ready. Zoneminder gets no answer and aborts. 
August 2010 - Ubuntu upgrades seem to be leaving several systems in this state. One way around this is to add a delay to the zoneminder startup script allowing Mysql to finish starting. 
"Simply adding 'sleep 15' in the line above 'zmfix -a' in the /etc/init.d/zoneminder file fixed my ZoneMinder startup problems!" - credit to Pada.

Remote Path setup for Panasonic and other Camera
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
On adding or editing the source you can select the preset link for the parameters for the specified camera .  In version 1.23.3  presets for BTTV,Axis,Panasonic,GadSpot,VEO, and BlueNet are available . Selecting the presets  ZM fills up the required value for the remote path variable

Why do I get repeated/ mixed/unstable/ blank monitors on bt878-like cards (a.k.a. PICO 2000)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Please have a check at [[Pico2000]];

What causes "Invalid JPEG file structure: two SOI markers" from zmc (1.24.x)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Some settings that used to be global only are now per camera.  On the Monitor Source tab, if you are using Remote Protocol  "HTTP" and Remote Method "Simple", try changing Remote Method to "Regexp".



Miscellaneous
-------------------
I see ZoneMinder is licensed under the GPL. What does that allow or restrict me in doing with ZoneMinder?
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

The ZoneMinder license is described at the end of the documentation and consists of the following section

 This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as
 published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.
 
 This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty
 of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

This means that ZoneMinder is licensed under the terms described `here <https://www.gnu.org/licenses/old-licenses/gpl-2.0.html>`__. There is a comprehensive FAQ covering the GPL at https://www.gnu.org/licenses/gpl-faq.html but in essence you are allowed to redistribute or modify GPL licensed software provided that you release your distribution or modifications freely under the same terms. You are allowed to sell systems based on GPL software. You are not allowed to restrict or reduce the rights of GPL software in your distribution however. Of course if you are just making modifications for your system locally you are not releasing changes so you have no obligations in this case. I recommend reading the GPL FAQ for more in-depth coverage of this issue.

Can I use ZoneMinder as part of my commercial product?
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

The GPL license allows you produce systems based on GPL software provided your systems also adhere to that license and any modifications you make are also released under the same terms.  The GPL does not permit you to include ZoneMinder in proprietary systems (see https://www.gnu.org/licenses/gpl-faq.html#GPLInProprietarySystem for details). If you wish to include ZoneMinder in this kind of system then you will need to license ZoneMinder under different terms. This is sometimes possible and you will need to contact me for further details in these circumstances.

I am having issues with zmNinja and/or Event Notification Server
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
zmNinja and the Event Notification Server are 3rd party solutions. The developer maintains exhaustive `documentation and FAQs <https://zmninja.readthedocs.io/en/latest/>`__. Please direct your questions there.