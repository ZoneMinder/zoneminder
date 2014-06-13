---
layout: page
title: About
permalink: /about/
---

<h3>Overview</h3>
ZoneMinder is intended for use in single or multi-camera video security applications, including commercial or home CCTV, theft prevention and child, family member or home monitoring and other domestic care scenarios such as nanny cam installations. It supports capture, analysis, recording, and monitoring of video data coming from one or more video or network cameras attached to a Linux system.

ZoneMinder also supports web and semi-automatic control of Pan/Tilt/Zoom cameras using a variety of protocols. It is suitable for use as a DIY home video security system and for commercial or professional video security and surveillance. It can also be integrated into a home automation system via X.10 or other protocols.

<h3>Technical Details</h3>
ZoneMinder is an integrated set of applications which provide a complete surveillance solution allowing capture, analysis, recording and monitoring of any CCTV or security cameras attached to a Linux based machine. It is designed to run on distributions which support the Video For Linux (V4L) interface and has been tested with video cameras attached to BTTV cards, various USB cameras and also supports most IP network cameras. A partial list is given in the Wiki and Support sections, please give feedback in the Forums if it works with yours. ZoneMinder also requires MySQL and PHP, and is enhanced by a webserver such as Apache.

ZoneMinder is highly componentised and comprises both the back-end daemons which do the actual image capture and analysis and a user friendly web GUI enabling you to both monitor the current situation and view and organise historical events that have taken place. The web GUI allows you to check and control your ZoneMinder installation from other computers in your home or from anywhere in the world. ZoneMinder does not require X at all, or the web interface for day-to-day functions and so is also suitable for 'headless' systems. There is also a simple xHTML interface allowing basic monitoring from suitable phones! Recent versions of ZoneMinder also include optional DVR (digital video recorder) functions allowing you to pause, rewind and even digitally zoom both live and historical video.

There is no hard upper or lower limit to the number of cameras that ZoneMinder can support, it entirely depends on the resources available on the host PC. This means that a basic home CCTV system can often be installed on old hardware that may be lying around unused giving you DIY CCTV completely for free!


<h3>Feature List</h3>

<div class="row">
	<div class="col-sm-6">
		<ul>
			<li>Runs on any Linux distribution!</li>
			<li>Supports analog, USB and IP cameras.</li>
			<li>Support Pan/Tilt/Zoom cameras, extensible to add new control protocols.</li>
			<li>Built on standard tools, C++, perl and PHP.</li>
			<li>Uses high performance MySQL database.</li>
			<li>High performance independent video capture and analysis daemons allowing high failure redundancy.</li>
			<li>Multiple Zones (Regions Of Interest) can be defined per camera. Each can have a different sensitivity or be ignored altogether.</li>
			<li>Large number of configuration options allowing maximum performance on any hardware.</li>
			<li>User friendly web interface allowing full control of system or cameras as well as live views and event replays.</li>
		</ul>
	</div>
	<div class="col-sm-6">
		<ul>
			<li>Supports live video in mpeg video, multi-part jpeg and stills formats.</li>
			<li>Supports event replay in mpeg video, multi-part jpeg, stills formats, along with statistics detail.</li>
			<li>User defined filters allowing selection of any number of events by combination of characteristics in any order.</li>
			<li>Event notification by email or SMS including attached still images or video of specific events by filter.</li>
			<li>Automatic uploading of matching events to external FTP storage for archiving and data security.</li>
			<li>Includes bi-directional X.10 (home automation protocol) integration allowing X.10 signals to control when video is captured and for motion detection to trigger X.10 devices.</li>
			<li>Highly partitioned design allow other hardware interfacing protocols to be added easily for support of alarm panels etc.</li>
			<li>Multiple users and user access levels    Multi-language support with many languages already included    Full control script support allowing most tasks to be automated or added to other applications.</li>
			<li>Support external triggering by 3rd party applications or equipment.</li>
			<li>xHTML mobile/cellular phone access allowing access to common functions</li>
			<li>iPhone interface available</li>
		</ul>
	</div>
</div>
