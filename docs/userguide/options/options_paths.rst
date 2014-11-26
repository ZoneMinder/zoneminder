Options - Paths
<<<<<<< HEAD
===============

.. image:: images/Config_images.png

=======
---------------

.. image:: images/Options_Paths.png

ZM_DIR_EVENTS - This is the path to the events directory where all the event images and other miscellaneous files are stored. CAUTION: The directory you specify here cannot be outside the web root. This is a common mistake. Most users should never change this value. If you intend to record events to a second disk or network share, then you should mount the drive or share directly to the ZoneMinder events folder or follow the instructions in the ZoneMinder Wiki titled Using a dedicated Hard Drive.

USE_DEEP_STORAGE - Traditionally ZoneMinder stores all events for a monitor in one directory for that monitor. This is simple and efficient except when you have very large amounts of events. Some filesystems are unable to store more than 32k files in one directory and even without this limitation, large numbers of files in a directory can slow creation and deletion of files. This option allows you to select an alternate method of storing events by year/month/day/hour/min/second which has the effect of separating events out into more directories, resulting in less per directory, and also making it easier to manually navigate to any events that may have happened at a particular time or date.

DIR_IMAGES - ZoneMinder generates a myriad of images, mostly of which are associated with events. For those that aren't this is where they go. CAUTION: The directory you specify here cannot be outside the web root. This is a common mistake. Most users should never change this value. If you intend to save images to a second disk or network share, then you should mount the drive or share directly to the ZoneMinder images folder or follow the instructions in the ZoneMinder Wiki titled Using a dedicated Hard Drive.

DIR_SOUNDS - ZoneMinder can optionally play a sound file when an alarm is detected. This indicates where to look for this file. CAUTION: The directory you specify here cannot be outside the web root. Most users should never change this value.

PATH_ZMS - The ZoneMinder streaming server is required to send streamed images to your browser. It will be installed into the cgi-bin path given at configuration time. This option determines what the web path to the server is rather than the local path on your machine. Ordinarily the streaming server runs in parser-header mode however if you experience problems with streaming you can change this to non-parsed-header (nph) mode by changing 'zms' to 'nph-zms'.

PATH_MAP - ZoneMinder has historically used IPC shared memory for shared data between processes. This has it's advantages and limitations. This version of ZoneMinder can use an alternate method, mapped memory, instead with can be enabled with the --enable--mmap directive to configure. This requires less system configuration and is generally more flexible. However it requires each shared data segment to map onto a filesystem file. This option indicates where those mapped files go. You should ensure that this location has sufficient space for these files and for the best performance it should be a tmpfs file system or ramdisk otherwise disk access may render this method slower than the regular shared memory one.

PATH_SOCKS - ZoneMinder generally uses Unix domain sockets where possible. This reduces the need for port assignments and prevents external applications from possibly compromising the daemons. However each Unix socket requires a .sock file to be created. This option indicates where those socket files go.

PATH_LOGS - There are various daemons that are used by ZoneMinder to perform various tasks. Most generate helpful log files and this is where they go. They can be deleted if not required for debugging.

PATH_SWAP - Buffered playback requires temporary swap images to be stored for each instance of the streaming daemons. This option determines where these images will be stored. The images will actually be stored in sub directories beneath this location and will be automatically cleaned up after a period of time.
>>>>>>> fb436fb... Merge pull request #591 from SteveGilvarry/docs-updates
