Misc Tab
--------

    The Misc tab contains settings related to saving events such as section lengths, frame skipping, FPS logging, and handling EXIF data.

.. figure:: images/define-monitor-misc.png

    Monitor Misc Tab

- **Event Prefix**: By default events are named ‘Event-<event id>’, where 'Event-' is the prefix. This option lets you modify the event prefix to be a value of your choice so that events are named differently when they are generated. This allows you to name events according to which monitor generated them.
- **Section Length**: This specifies the length (in seconds) of any fixed length events produced when the Monitor Recording is set to 'Always' or 'On Motion / Trigger / etc'. Otherwise it is ignored. This should not be so long that events are difficult to navigate nor so short that too many events are generated. A length of between 300 and 900 seconds is recommended but should be set appropriately for each server.
- **Minimum Section Length**: This specifies the minimum length (in seconds) of events which will be recorded. 
- **Motion Frame Skip**: How many frames to skip between motion detection passes. Zero analyses every captured frame, one analyses every second frame, and so on. Frames are still captured and recorded as normal, this only reduces how often motion detection runs.
- **Analysis Update Delay**: Undocumented parameter
- **FPS Report Interval**: How often the current performance in terms of Frames Per Second is output to the system log and updated in the Monitor Status table. A lower value can cause excess logging and has a slight database performance penalty. The default value of 100 is likely fine for most systems during setup. At 10fps this will cause an update every 10 seconds. Once the system is configured and stable a higher number can be used to reduce logfile clutter. 
- **Signal Check Points**: Undocumented parameter
- **Signal Check Colour**: Undocumented parameter
- **Web Colour**: Some elements of ZoneMinder now use colours to identify monitors on certain views. You can select which colour is used for each monitor here. Any specification that is valid for HTML colours is valid here, e.g. ‘red’ or ‘#ff0000’. A small swatch next to the input box displays the colour you have chosen.
- **Embed EXIF data into image**: Select this to embed EXIF data into each JPEG frame.
- **Importance**: Available options are Normal, Less Important and Not Important.
