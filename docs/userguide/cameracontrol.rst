Camera Control
==============

ZoneMinder provides the facility to control cameras from the web interface and to some extent automatically. Pan/Tilt/Zoom (PTZ) cameras have a wide range of capabilities and use a large number of different protocols making any kind of generic control solution potentially very difficult. To address this ZoneMinder uses two key approaches to get around this problem.

Definition of Capabilities
  For each camera model you use, an entry in the camera capabilities table must be created. These indicate what functions the camera supports and ensure that the interface presents only those capabilities that the camera supports. There are a very large number of capabilities that may be supported and it is very important that the entries in this table reflect the actual abilities of the camera. A small number of example capabilities are included in ZoneMinder, these can be used ‘as is’ or modified.

Control Scripts
  ZoneMinder itself does not generally provide the ability to send commands to cameras or receive responses. What it does is mediate motion requests from the web interface into a standard set of commands which are passed to a script defined in the control capability. Example scripts are provided in ZoneMinder which support a number of serial or network protocols but it is likely that for many cameras new scripts will have to be created. These can be modelled on the example ones, or if control commands already exist from other applications, then the script can just act as a ‘glue’ layer between ZoneMinder and those commands.

It should be emphasised that the control and capability elements of ZoneMinder are not intended to be able to support every camera out of the box. Some degree of development is likely to be required for many cameras.

Controlling Monitors
====================

If you have defined your system as having controllable monitors and you are looking at a monitor that is configured for control, then clicking on the ‘Control’ link along the top of the window will change the short event listing area to a control area. The capabilities you have defined earlier determine exactly what is displayed in this window. Generally you will have a Pan/Tilt control area along with one or subsidiary areas such as zoom or focus control to the side. If you have preset support then these will be near the bottom of the window. The normal method of controlling the monitor is by clicking on the appropriate graphics which then send a command via the control script to the camera itself. This may sometimes take a noticeable delay before the camera responds.

It is usually the case that the control arrows are sensitive to where you click on them. If you have a camera that allows different speeds to be used for panning or zooming etc then clicking near the point of the arrow will invoke the faster speed whilst clicking near the base of the arrow will be slower. If you have defined continuous motion then ongoing activities can be stopped by clicking on the area between the arrows, which will either be a graphic in the case of pan/tilt controls or a word in the case of zoom and focus controls etc.

Certain control capabilities such as mapped motion allow direct control by clicking on the image itself when used in browsers which support streamed images directly. Used in this way you can just click on the area of the image that interests you and the camera will centre on that spot. You can also use direct image control for relative motion when the area of the image you click on defines the direction and the distance away from the centre of the image determines the speed. As it is not always very easy to estimate direction near the centre of the image, the active area does not start until a short distance away from the centre, resulting in a ‘dead’ zone in the middle of the image.

Control Flow
^^^^^^^^^^^^
Having a basic understanding of how camera control works in ZoneMinder will go a long way in debugging issues in the future. It is important to note that many of the 'camera control' scripts are user contributed and it is entirely possible that they break in a future version upgrade. 

* ZoneMinder relies on 'control protocols' for specific camera models. These 'control' protocols are nothing but perl packages located in ``/usr/share/perl5/ZoneMinder/Control/`` (in Ubuntu distributions) that are invoked by ZoneMinder when you invoke a PTZ operation

* When you associate a 'protocol' for PTZ for a camera, you are effectively letting ZoneMinder know where to locate the perl file that will eventually control the camera movement

* Let's for example, assume that you are configuring a Foscam 9831W camera and have associated the '9831w' protocol to that camara. This basically means when you move the camera via ZoneMinder, it will pass on the movements to FI9831w.pm in ``/usr/share/perl5/ZoneMinder/Control/``

* ZoneMinder also maintains protocol configuration parameters in a table called ``Controls`` in the DB. This table is used to store parameters like whether the camera supports continuous move, zoom etc. 

* The ``Controls`` table is used by ZoneMinder to build its PTZ web interface. For example, an FI9831W camera does not support Zoom --> so when you open the PTZ interface of ZoneMinder via the Web Console and navigate to the FI9831W camera, the Zoom option will not be shown. It knows not to show this because the ``Control`` table entry for FI9831W specifies it does not support Zoom. Note that you edit these parameters via Source->Control->Control Type->Edit in the web console

* If you ever look at any of the control protocol files, you will notice it has functions like ``moveRelUp`` or ``moveConLeft`` etc. -> these are the functions that eventually get invoked to move the camera around and it is expected that contributors who implement missing camera profiles fill in these functions with the appropriate camera specific commands. This way, the core ZoneMinder code does not need to worry about camera specific commands. All it needs to know is the features of a camera and accordinfly invoke abstract commands in the protocol perl file and it is the responsibility of the perl file for that camera to implement the specifics. So, if you are facing problems with PTZ not working, these protocol files are what you should be debugging.


Control Capabilities
^^^^^^^^^^^^^^^^^^^^

If you have a camera that supports PTZ controls and wish to use it with ZoneMinder then the first thing you need to do is ensure that it has an accurate entry in the capabilities table. To do this you need to go to the Control tab of the Monitor configuration dialog and select ‘Edit’ where it is listed by the Control Type selection box. This will bring up a new window which lists, with a brief summary, the existing capabilities. To edit an existing capability to modify select the Id or Name of the capability in question, or click on the Add button to add a new control capability. Either of these approaches will create a new window, in familiar style, with tabs along the top and forms fields below. In the case of the capabilities table there are a large number of settings and tabs, the mean and use of these are briefly explained below.



Main Tab
--------
Name
  This is the name of the control capability, it will usually make sense to name capabilities after the camera model or protocol being used.
Type
  Whether the capability uses a local (usually serial) or network control protocol.
Command
  This is the full path to a script or application that will map the standard set of ZoneMinder control commands to equivalent control protocol command. This may be one of the shipped example zmcontrol-\*.pl scripts or something else entirely.
Can Wake
  This is the first of the actual capability definitions. Checking this box indicates that a protocol command exists to wake up the camera from a sleeping state.
Can Sleep
  The camera can be put to sleep.
Can Reset 
 The camera can be reset to a previously defined state.

Move Tab
--------
Can Move
  The camera is able move, i.e. pan or tilt.
Can Move Diagonally
  The camera can move diagonally. Some devices can move only vertically or horizontally at a time.
Can Move Mapped
  The camera is able internally map a point on an image to a precise degree of motion to centre that point in the image.
Can Move Absolute
  The camera can move to an absolute location.
Can Move Relative
  The camera can more to a relative location, e.g. 7 point left or up.
Can Move Continuous
  The camera can move continuously in a defined direction until told to stop or the movement limits are reached, e.g. left.

Pan Tab
-------
Can Pan
  The camera can pan, or move horizontally.
Min/Max Pan Range
  If the camera supports absolute motion this is the minimum and maximum pan co-ordinates that may be specified, e.g. -100 to 100.
Min/Man Pan Step
  If the camera supports relative motion, this is the minimum and maximum amount of movement that can be specified.
Has Pan Speed
  The camera supports specification of pan speeds.
Min/Max Pan Speed
  The minimum and maximum pan speed supported.
Has Turbo Pan
  The camera supports an additional turbo pan speed.
Turbo Pan Speed
  The actual turbo pan speed.

Tilt Tab
--------
Definition of Tilt capabilities, fields as for ‘Pan’ tab.

Zoom Tab
--------
Can Zoom
  The camera can zoom.
Can Zoom Absolute
  The camera can zoom to an absolute position.
Can Zoom Relative
  The camera can zoom to a relative position.
Can Zoom Continuous
  The camera can zoom continuously in or out until told to stop or the zoom limits are reached.
Min/Max Zoom Range
  If the camera supports absolute zoom this is the minimum and maximum zoom amounts that may be specified.
Min/Man Zoom Step
  If the camera supports relative zoom, this is the minimum and maximum amount of zoom change that can be specified.
Has Zoom Speed
  The camera supports specification of zoom speed.
Min/Max Zoom Speed
  The minimum and maximum zoom speed supported.

Focus Tab
---------
Definition of Focus capabilities, fields as for ‘Zoom’ tab, but with the following additional capability.

Can Auto Focus
  The camera can focus automatically.

White Tab
---------
Definition of White Balance capabilities, fields as for ‘Focus’ tab.

Iris Tab
--------
Definition of Iris Control capabilities, fields as for ‘Focus’ tab.

Presets Tab
-----------

Has Presets
  The camera supports preset positions.
Num Presets
  How many presets the camera supports. If the camera supports a huge number of presets then it makes sense to specify a more reasonable number here, 20 or less is recommended.
Has Home Preset
  The camera has a defined ‘home’ position, usually in the mid point of its range.
Can Set Presets
  The camera supports setting preset locations via its control protocol.

Control Scripts
^^^^^^^^^^^^^^^
The second key element to controlling cameras with ZoneMinder is ensuring that an appropriate control script or application is present. A small number of sample scripts are included with ZoneMinder and can be used directly or as the basis for development. Control scripts are run atomically, that is to say that one requested action from the web interface results in one execution of the script and no state information is maintained. If your protocol requires state information to be preserved then you should ensure that your scripts do this as ZoneMinder has no concept of the state of the camera in control terms.

If you are writing a new control script then you need to ensure that it supports the parameters that ZoneMinder will pass to it. If you already have scripts or applications that control your cameras, the ZoneMinder control script will just act as glue to convert the parameters passed into a form that your existing application understands. If you are writing a script to support a new protocol then you will need to convert the parameters passed into the script to equivalent protocol commands. If you have carefully defined your control capabilities above then you should only expect commands that correspond to those capabilities.

The standard set of parameters passed to control scripts is defined below,

  --device=<device> : This is the control device from the monitor definition. Absent if no device is specified.

  --address=<address> : This is the control address from the monitor definition. This will usually be a hostname or ip address for network cameras or a simple numeric camera id for other cameras.

  --autostop=<timeout> : This indicates whether an automatic timeout should be applied to '''stop''' the given command. It will only be included for '''continuous''' commands, as listed below, and will be a timeout in decimal seconds, probably fractional.

  --command=<command> : This specifies the command that the script should execute. Valid commands are given below.

  --xcoord=<x>, --ycoord=<y> : This specifies the x and/or y coordinates for commands which require them. These will normally be absolute or mapped commands.

  --width=<width>'', ''--height=<height> : This specifies the width and height of the current image, for mapped motion commands where the coordinates values passed must have a context.

  --speed=<speed> : This specifies the speed that the command should use, if appropriate.

  --panspeed=<speed>'', ''--tiltspeed=<speed> : This indicates the specific pan and tilt speeds for diagonal movements which may allow a different motion rate for horizontal and vertical components.

  --step=<step> : This specifies the amount of motion that the command should use, if appropriate. Normally used for relative commands only.

  --panstep=<step>'', ''--tiltstep=<step> : This indicates the specific pan and tilt steps for diagonal movements which may allow a different amount of motion for horizontal and vertical components.

  --preset=<preset> : This specifies the particular preset that relevant commands should operate on.

The *command* option listed above may take one of the following commands as a parameter.

wake
  Wake the camera.
sleep
  Send the camera to sleep.
reset
  Reset the camera.
move_map
  Move mapped to a specified location on the image.
move_pseudo_map
  As move_map above. Pseudo-mapped motion can be used when mapped motion is not supported but relative motion is in which case mapped motion can be roughly approximated by careful calibration.
move_abs_<direction>
  Move to a specified absolute location. The direction element gives a hint to the direction to go but can be omitted. If present it will be one of "up", "down", "left", "right", "upleft", "upright", "downleft" or "downright".
move_rel_<direction>
  Move a specified amount in the given direction. 
move_con_<direction>
  Move continuously in the given direction until told to stop.
move_stop
  Stop any motion which may be in progress. 
zoom_abs_<direction>
  Zoom to a specified absolute zoom position. The direction element gives a hint to the direction to go but can be omitted. If present it will be one of "tele" or "wide".
zoom_rel_<direction>
  Zoom a specified amount in the given direction.
zoom_con_<direction>
  Zoom continuously in the given direction until told to stop.
zoom_stop
  Stop any zooming which may be in progress.
focus_auto
  Set focusing to be automatic.
focus_man
  Set focusing to be manual.
focus_abs_<direction>
  Focus to a specified absolute focus position. The direction element gives a hint to the direction to go but can be omitted. If present it will be one of "near" or "far".
focus_rel_<direction>
  Focus a specified amount in the given direction.
focus_con_<direction>
  Focus continuously in the given direction until told to stop.
focus_stop
  Stop any focusing which may be in progress.
white_<subcommand>
  As per the focus commands, except that direction may be "in" or "out".
iris_<subcommand>
  As per the focus commands, except that direction may be "open" or "close".
preset_set
  Set the given preset to the current location.
preset_goto
  Move to the given preset.
preset_home
  Move to the "home" preset.
