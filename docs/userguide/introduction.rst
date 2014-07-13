Introduction
============

Welcome to ZoneMinder, the all-in-one Linux GPL'd security camera solution.

Most commercial "security systems" are designed as a monitoring system that also records.  Recording quality can vary from bad to unusable, locating the relevant video can range from challenging to impractical, and exporting can often only be done with the manual present.  ZoneMinder was designed primarily to record, and allow easy searches and exporting.  Recordings are of the best possible quality, easy to filter and find, and simple to export using any system with a web browser.  It also monitors.

ZoneMinder is designed around a series of independent components that only function when necessary limiting any wasted resource and maximising the efficiency of your machine. A fairly ancient Pentium II PC should be able to track one camera per device at up to 25 frames per second with this dropping by half approximately for each additional camera on the same device. Additional cameras on other devices do not interact so can maintain this frame rate. Even monitoring several cameras still will not overload the CPU as frame processing is designed to synchronise with capture and not stall it.

As well as being fast ZoneMinder is designed to be friendly and even more than that, actually useful. As well as the fast video interface core it also comes with a user friendly and comprehensive PHP based web interface allowing you to control and monitor your cameras from home, at work, on the road, or even a web enabled cell phone. It supports variable web capabilities based on available bandwidth. The web interface also allows you to view events that your cameras have captured and archive them or review them time and again, or delete the ones you no longer wish to keep. The web pages directly interact with the core daemons ensuring full co-operation at all times. ZoneMinder can even be installed as a system service ensuring it is right there if your computer has to reboot for any reason.

The core of ZoneMinder is the capture and analysis of images and there is a highly configurable set of parameters that allow you to ensure that you can eliminate false positives whilst ensuring that anything you don't want to miss will be captured and saved. ZoneMinder allows you to define a set of 'zones' for each camera of varying sensitivity and functionality. This allows you to eliminate regions that you don't wish to track or define areas that will alarm if various thresholds are exceeded in conjunction with other zones.

ZoneMinder is free, but if you do find it useful then please feel free to visit http://www.zoneminder.com/donate.html and help to fund future improvements to ZoneMinder.

