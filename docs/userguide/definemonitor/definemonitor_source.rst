
.. _source_tab:

Source Tab
----------

The Source tab contains parameters related to the video stream as well as selection of capture and decode methods. The parameters listed in the Source tab depend on the **Source Type** specified in the General tab. 

For documentation purposes we have split the Source tab information into two parts:

- **Common Source Parameters**: All source types except Web Site contain these listed parameters. They are shown in the :ref:`common_source_parameters` section just below and can be entered first.
- **Specific Source Parameters**: Each source type has a dedicated section that contains their unique parameters. The common source parameters are not listed again in these sections.

.. _common_source_parameters:

Common Source Parameters
^^^^^^^^^^^^^^^^^^^^^^^^

.. figure:: images/define-monitor-source.png

    Common Parameters for all Source Types (other than Web Site)

- **Capturing**

    - **None**: The ZoneMinder Capture daemon (zmc) is disabled. Event recording is not possible.
    - **On Demand**: The ZoneMinder Capture daemon (zmc) is enabled but will wait for a viewer to start the stream capture. Viewer is defined as either a live view, thumbnail or RTSP server connection.
    - **Always**: This is the normal mode of operation.  A ZoneMinder Capture daemon (zmc) will run and constantly capture video.

- **Decoding**

    - **None**: No frames will be decoded, live view and thumbnails will not be available.
    - **On Demand**: Only enable decoding when someone is watching.
    - **KeyFrames**: Only keyframes will be decoded. Viewing frame rate will be very low depending on the keyframe interval set in the camera.
    - **KeyFrames + On Demand**: Keyframes will be decoded when someone is watching.
    - **Always**: Every frame will be decoded, live view and thumbnails will be available.

- **Target Colorspace**: Specify the color depth or bits per pixel in the captured image. Most modern cameras support 32 bit depth. This has no controlling effect on the camera.

    - **32 bit colour**: The default setting. Most modern cameras support 32 bit color depth.
    - **24 bit colour**: Try this setting for older cameras if 32 bit is not supported.
    - **8 bit greyscale**: For non-color streams. This can reduce both CPU and storage requirements.

- **Capture Resolution**: Resolution is the dimensions in bits of the video stream provided by camera. It is important that the entered capture resolution matches the camera stream. The pulldown menu contains most commonly used resolutions up to 4K and is the recommended way to enter resolution. Resolution can also be entered manually if necessary.
- **Preserve Aspect Ratio**: Enable this checkbox to ensure that the width preserves ratio to the height, or vice versa. Enter either width or height and the other dimension will be calculated. The default ratio is 4:3 but can be amended globally via the :guilabel:`Options` -> :guilabel:`Config` -> :guilabel:`DEFAULT_ASPECT_RATIO` setting.
- **Orientation**: Use this option if the camera stream is not oriented as desired and cannot be changed directly in the camera firmware. Usually this is needed when the camera is not installed with correct physical orientation. This incurs an additional processing overhead in ZoneMinder. Remember to swap the height and width fields so that they apply correctly after rotation.
- **Deinterlacing**: Only required for processing streams from interlaced sources so that the video is converted to progressive frames. Select the desired deinterlacing type from the dropdown menu.
- **Maximum FPS**: For modern streaming IP cameras, *do not use this field to reduce the frame rate*. It is recommended to leave this blank which implies no Maximum FPS limit. ZoneMinder will use the FPS as provided from the camera stream. This option is only useful for local devices or older IP cameras that support snapshot mode.
- **Alarm Maximum FPS**: For modern streaming IP cameras, *do not use this field to reduce the frame rate*. It is recommended to leave this blank which implies no Alarm Maximum FPS limit. ZoneMinder will use the FPS as provided from the camera stream. This option is only useful for local devices or older IP cameras that support snapshot mode.

.. warning:: Do not use the Maximum FPS parameters unless you know what you are doing. Setting these parameters may have undesirable results for network IP cameras.

Source Tab for FFmpeg
^^^^^^^^^^^^^^^^^^^^^

    This is the **recommended** source type for most modern networked IP cameras.

.. figure:: images/define-monitor-source-ffmpeg.png

    Source Tab for FFmpeg

Listed below are the parameters unique to FFmpeg Source. Please also see :ref:`common_source_parameters` section above for information related to the common source parameters which also need to be entered.

- **Source Path**: Use this field to enter the full URL of the camera stream or file. There are several methods to learn this:

    - Camera user manual or manufacturer support website
    - Use ONVIF Probe feature in ZoneMinder by clicking on the related icon
    - Look for the camera in the `ZoneMinder Hardware Compatibility wiki <https://wiki.zoneminder.com/Hardware_Compatibility_List>`__
    - Look for the camera or ask questions in the `ZoneMinder Hardware Compatibility Forum <https://forums.zoneminder.com/viewforum.php?f=10&sid=1778ce18ebd267e36da01158e502cd11>`__
    - Use a third party website with camera database such as `Camera Connection Database <https://www.ispyconnect.com/cameras>`__

- **Username**: The username of camera stream. Note that if your URL contains camera authentication these may be automatically populated into the Username and Password fields.
- **Password**: The password of camera stream. Note that if your URL contains camera authentication these may be automatically populated into the Username and Password fields.
- **Method**:

    - **TCP**: This is the default and preferred option. TCP is a reliable but slower protocol as it has more overhead. Some older cameras do not support it.
    - **UDP**: UDP is an faster but less reliable protocol. Some cameras will display smearing effects due to lost or out of order packets. These can be fixed by either using TCP, or specify ``reorder_queue_size=xx`` in Options.
    - **UDP Multicast**: Multicast is when the camera broadcasts the video once on the network and ZoneMinder subscribes to and receives the video. This is more efficient when there are multiple viewers.
    - **HTTP Tunnel**: Tunnel RTSP over HTTP.

- **Options**: Here you can pass various settings to the FFmpeg libraries. Common options include ``reorder_queue_size`` and ``allowed_media_types``.
- **SourceSecondPath**: This can be used to define a second stream for this Monitor. Enter URL path for second stream. The target use case is for a low resolution substream to use for analysis which can trigger recording on the main high resolution stream.

.. admonition:: New Feature in ZoneMinder 1.37

    Note that the **SourceSecondPath** feature was added in ZoneMinder 1.37 and is not yet fully supported. Use at your own risk with expectation that functionality may change in the future.

- **Decoder**: Selection of the FFmpeg decoder. In most cases leave at default Auto setting. The pulldown menu lists the available decoders.
- **DecoderHWAccelName**: Option to support GPU hardware acceleration. This is equivalent to the ``ffmpeg -hwaccels <device>`` command line option. With Intel graphics support, use ``vaapi``. For NVIDIA cuda support use ``cuda``. To check for support, run ``ffmpeg -hwaccels`` on the command line.
- **DecoderHWAccelDevice**: Option to support GPU hardware acceleration. This is equivalent to the ``ffmpeg -hwaccels <device>`` command line option. You should only have to specify this if you have multiple GPUs. A typical value for Intel VAAPI would be ``/dev/dri/renderD128``.

.. attention::

    Limited GPU support. Note that GPU support has not yet been fully implemented in ZoneMinder. Use at your own risk with expectation that functionality may change in the future.

Source Tab for libVLC
^^^^^^^^^^^^^^^^^^^^^

    The fields for the LibVLC source type are configured the same way as the FFmpeg source type. We recommend only using this source type if issues are experienced with the FFmpeg type.

Source Tab for Remote
^^^^^^^^^^^^^^^^^^^^^

    The Remote source type is mainly used for older IP cameras that stream using HTTP protocol. Past use of this source type supported RTSP protocol. The Remote -> RTSP method is no longer being maintained and may be removed in the future. If camera supports RTSP we recommend using FFmpeg source type.

.. figure:: images/define-monitor-source-remote.png

    Source Tab for Remote

Listed below are the parameters unique to Remote Source. Please also see :ref:`common_source_parameters` section above for information related to the common source parameters which also need to be entered.

- **Username**: Enter username of remote device if one is needed for authentication.
- **Password**: Enter password of remote device if one is needed for authentication.
- **Protocol**: Choices are currently HTTP and RTSP. Before RTSP became the industry standard, many IP cameras streamed directly from their web interface using HTTP. If the IP camera does not speak RTSP then choose HTTP here. *If the camera does speak RTSP then change your source type to FFmpeg instead of selecting RTSP here.* The Remote -> RTSP method is no longer being maintained and may be removed in the future.
- **Method**: Choices are currently Simple and Regexp. Simple is the default and preferred method. Methods related to RTSP have been removed, please use FFmpeg for RTSP streaming support.

Example URL for Remote Source Type: ``http://username:password@hostname.com:port/videoMain``

- **Host Name**: Enter the domain part of the URL which is ``hostname.com`` for this example.
- **Port**: Enter port part of the URL which is ``port`` (HTTP commonly uses 80).
- **Path**: Enter path part of the URL which is ``videoMain`` for this example.

.. todo:: Check status of Remote RTSP support in ZoneMinder version 1.37+

Source Tab for Local
^^^^^^^^^^^^^^^^^^^^

    The Local source type is mainly used for locally attached USB cameras or video capture cards.

.. figure:: images/define-monitor-source-local.png

    Source Tab for Local

Listed below are the parameters unique to Local Source. Please also see :ref:`common_source_parameters` section above for information related to the common source parameters which also need to be entered.

- **Device Path**: Enter the full path to the device file that your camera is attached. Most likely it will be one of ``/dev/video0`` through ``/dev/video63``. Look for the camera in the `ZoneMinder Hardware Compatibility wiki <https://wiki.zoneminder.com/Hardware_Compatibility_List>`__ to see if your capture card or USB webcam is supported or not and any extra settings that may be needed.
- **Capture Method**: Currently available selection is Video For Linux V2
- **Multi Buffering**: Available choices are Yes, No and Use Config Value.
- **Captures Per Frame**: Default is 1. Acts as a FPS divisor and is mainly useful for video capture cards connected to multiple cameras to slow FPS down.

Source Tab for File
^^^^^^^^^^^^^^^^^^^

    The File source type is used for periodically loading an image file. This is useful for the case where a device or script writes single files instead of the more common stream that the other Source types handle. A description of this can be found in the `ZoneMinder wiki <https://wiki.zoneminder.com/index.php/How_to_use_ZoneMinder_with_cameras_it_may_not_directly_support>`__.

.. figure:: images/define-monitor-source-file.png

    Source Tab for File

Listed below are the parameters unique to File Source. Please also see :ref:`common_source_parameters` section above for information related to the common source parameters which also need to be entered.

* **Source Path**: Enter a path from the file system such as ``/mnt/images/foo.jpg``.

Source Tab for Web Site
^^^^^^^^^^^^^^^^^^^^^^^

The Web Site Source Type allows one to configure an arbitrary website as a non-recordable, fully interactive Monitor in ZoneMinder. Note that sites with self-signed certificates will not display until the end user first manually navigates to the site and accepts the unsigned certificate. Also note that some sites will set an X-Frame option in the header, which discourages their site from being displayed within a frame. ZoneMinder will detect this condition and present a warning in the log. When this occurs, the end user can choose to install a browser plugin or extension to workaround this issue.

.. figure:: images/define-monitor-source-website.png

    Source Tab for Web Site

Listed below are the parameters unique to Web Site Source. Please also see :ref:`common_source_parameters` section above for information related to the common source parameters which also need to be entered.

- **Website URL**: Enter full HTTP or HTTPS URL such as ``http://hostname.com``.
- **Width (pixels)**: Enter page width in pixels. This may take some experimentation.
- **Height (pixels)**: Enter page height in pixels. This may take some experimentation.
- **Web Site Refresh**: If the website in question has static content, optionally enter a time period in seconds for ZoneMinder to refresh the content.
