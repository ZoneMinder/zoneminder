Options - Images
<<<<<<< HEAD
================

.. image:: images/Options_images.png


=======
----------------

.. image:: images/Options_images.png

OPT_FFMPEG - ZoneMinder can optionally encode a series of video images into an MPEG encoded movie file for viewing, downloading or storage. This option allows you to specify whether you have the ffmpeg tools installed. Note that creating MPEG files can be fairly CPU and disk intensive and is not a required option as events can still be reviewed as video streams without it.

PATH_FFMPEG - This path should point to where ffmpeg has been installed.

FFMPEG_INPUT_OPTIONS - Ffmpeg can take many options on the command line to control the quality of video produced. This option allows you to specify your own set that apply to the input to ffmpeg (options that are given before the -i option). Check the ffmpeg documentation for a full list of options which may be used here.

FFMPEG_OUTPUT_OPTIONS - Ffmpeg can take many options on the command line to control the quality of video produced. This option allows you to specify your own set that apply to the output from ffmpeg (options that are given after the -i option). Check the ffmpeg documentation for a full list of options which may be used here. The most common one will often be to force an output frame rate supported by the video encoder.

FFMPEG_FORMATS - Ffmpeg can generate video in many different formats. This option allows you to list the ones you want to be able to select. As new formats are supported by ffmpeg you can add them here and be able to use them immediately. Adding a '*' after a format indicates that this will be the default format used for web video, adding '**' defines the default format for phone video.

FFMPEG_OPEN_TIMEOUT - When Ffmpeg is opening a stream, it can take a long time before failing; certain circumstances even seem to be able to lock indefinitely. This option allows you to set a maximum time in seconds to pass before closing the stream and trying to reopen it again.

JPEG_STREAM_QUALITY - When viewing a 'live' stream for a monitor ZoneMinder will grab an image from the buffer and encode it into JPEG format before sending it. This option specifies what image quality should be used to encode these images. A higher number means better quality but less compression so will take longer to view over a slow connection. By contrast a low number means quicker to view images but at the price of lower quality images. This option does not apply when viewing events or still images as these are usually just read from disk and so will be encoded at the quality specified by the previous options.

MPEG_TIMED_FRAMES - When using streamed MPEG based video, either for live monitor streams or events, ZoneMinder can send the streams in two ways. If this option is selected then the timestamp for each frame, taken from it's capture time, is included in the stream. This means that where the frame rate varies, for instance around an alarm, the stream will still maintain it's 'real' timing. If this option is not selected then an approximate frame rate is calculated and that is used to schedule frames instead. This option should be selected unless you encounter problems with your preferred streaming method.

MPEG_LIVE_FORMAT - When using MPEG mode ZoneMinder can output live video. However what formats are handled by the browser varies greatly between machines. This option allows you to specify a video format using a file extension format, so you would just enter the extension of the file type you would like and the rest is determined from that. The default of 'asf' works well under Windows with Windows Media Player but I'm currently not sure what, if anything, works on a Linux platform. If you find out please let me know! If this option is left blank then live streams will revert to being in motion jpeg format

MPEG_REPLAY_FORMAT - When using MPEG mode ZoneMinder can replay events in encoded video format. However what formats are handled by the browser varies greatly between machines. This option allows you to specify a video format using a file extension format, so you would just enter the extension of the file type you would like and the rest is determined from that. The default of 'asf' works well under Windows with Windows Media Player and 'mpg', or 'avi' etc should work under Linux. If you know any more then please let me know! If this option is left blank then live streams will revert to being in motion jpeg format

RAND_STREAM - Some browsers can cache the streams used by ZoneMinder. In order to prevent his a harmless random string can be appended to the url to make each invocation of the stream appear unique.

OPT_CAMBOZOLA - Cambozola is a handy low fat cheese flavoured Java applet that ZoneMinder uses to view image streams on browsers such as Internet Explorer that don't natively support this format. If you use this browser it is highly recommended to install this from http://www.charliemouse.com/code/cambozola/  however if it is not installed still images at a lower refresh rate can still be viewed.

PATH_CAMBOZOLA - Cambozola is a handy low fat cheese flavoured Java applet that ZoneMinder uses to view image streams on browsers such as Internet Explorer that don't natively support this format. If you use this browser it is highly recommended to install this from http://www.charliemouse.com/code/cambozola/  however if it is not installed still images at a lower refresh rate can still be viewed. Leave this as 'cambozola.jar' if cambozola is installed in the same directory as the ZoneMinder web client files.

RELOAD_CAMBOZOLA - Cambozola allows for the viewing of streaming MJPEG however it caches the entire stream into cache space on the computer, setting this to a number > 0 will cause it to automatically reload after that many seconds to avoid filling up a hard drive.

OPT_FFMPEG - ZoneMinder can optionally encode a series of video images into an MPEG encoded movie file for viewing, downloading or storage. This option allows you to specify whether you have the ffmpeg tools installed. Note that creating MPEG files can be fairly CPU and disk intensive and is not a required option as events can still be reviewed as video streams without it.

PATH_FFMPEG - This path should point to where ffmpeg has been installed.

FFMPEG_INPUT_OPTIONS - Ffmpeg can take many options on the command line to control the quality of video produced. This option allows you to specify your own set that apply to the input to ffmpeg (options that are given before the -i option). Check the ffmpeg documentation for a full list of options which may be used here.

FFMPEG_OUTPUT_OPTIONS - Ffmpeg can take many options on the command line to control the quality of video produced. This option allows you to specify your own set that apply to the output from ffmpeg (options that are given after the -i option). Check the ffmpeg documentation for a full list of options which may be used here. The most common one will often be to force an output frame rate supported by the video encoder.

FFMPEG_FORMATS - Ffmpeg can generate video in many different formats. This option allows you to list the ones you want to be able to select. As new formats are supported by ffmpeg you can add them here and be able to use them immediately. Adding a '*' after a format indicates that this will be the default format used for web video, adding '**' defines the default format for phone video.

FFMPEG_OPEN_TIMEOUT - When Ffmpeg is opening a stream, it can take a long time before failing; certain circumstances even seem to be able to lock indefinitely. This option allows you to set a maximum time in seconds to pass before closing the stream and trying to reopen it again.
>>>>>>> fb436fb... Merge pull request #591 from SteveGilvarry/docs-updates
