libzm-plugin-openalpr
=====================

## Overview

libzm-plugin-openalpr is a plugin for Automatic Licence Plate Recognition (ALPR).
The recognized license plates are added to Zoneminder's event notes.

## Requirements

libzm-plugin-openalpr is based on the OpenALPR library.
Currently this library is not packaged in distributions but it can be built and installed following the instructions on github project page (https://github.com/openalpr/openalpr).

## Configuration

After installation, please make some adjustments in file `/etc/zm/plugins.d/openalpr.conf`.
Most of default values can be kept, but if you live in Europe, you can set the `country_code` setting to `eu` to improve reading of plates with EU format

All the next configuration steps are done through the web interface.

Firstly, the plugin loading has to be enabled in ZM options (please check the `LOAD_PLUGIN` setting in `Config` tab).

Then, you can configure the plugin settings from each `Zone` configuration page.

![Zone](https://github.com/manupap1/libzoneminder-plugin-openalpr/blob/master/misc/zone.png)

Available plugins are listed with a color code under the `Plugins` row:
- `Default color` - Plugin is not enabled for the zone
- `Green` - Plugin is enabled for the zone
- `Grey` - Plugin loading is disabled (please check `LOAD_PLUGIN` setting in `Config` tab)
- `Orange` - Plugin is enabled for the zone but not active (configuration setting mismatch)
- `Red` - ZoneMinder failed to load the plugin object (software error)

Once a plugin object is loaded, the `Plugin` configuration page is accessed by clicking on the plugin name.

![Plugin](https://github.com/manupap1/libzoneminder-plugin-openalpr/blob/master/misc/plugin.png)

The first options are available for all plugins:
- `Enabled` - A yes/no select box to enable or disable the plugin
- `Require Native Detection` - A yes/no select box to specify if native detection is required before to process plugin analysis. This option allow to limit CPU usage by using the plugin for post processing after native detection. This option is recommended for this plugin as it may use a lot of CPU ressources
- `Include Native Detection` - A yes/no select box to specify if native detection shall be included in alarm score and image overlay
- `Reinit. Native Detection` - A yes/no select box to specify if native detection shall be reinitialized after detection. ZoneMinder's native detection is performed by comparing the current image to a reference image. By design, the reference image is assigned when analysis is activated, and this image is not periodically refreshed. This operating method is not necessarily optimal because some plugins may require native detection only when motion is truly detected (current image different from the previous image). This option is recommended for libzoneminder-plugin-openalpr. For example, without this option enabled, if a vehicle appears and parks in the camera field of view, the native detection will be be triggered as long as the vehicle is parked, and therefore the plugin analysis would be performed for an unnecessary period of time. With this option enabled, the plugin analysis stops when the vehicle stops.
- `Alarm Score` - A text box to enter the score provided by the plugin in case of detection

The next options are specifics to this plugin and can be used to adjust the detection accuracy:
- `Minimum Confidence` - A text box to enter the minimum confidence level. All plates with a lower confidence level will be excluded.
- `Min. Number of Characters` - A text box to enter the minimum number of characters in a license plate. All plates with a lower number of detected characters will be excluded.
- `Max. Number of Characters` - A text box to enter the maximum number of characters in a license plate. All plates with a greater number of detected characters will be excluded.
- `List of Targeted Plates` - A list to specify targeted plates (detected plates will have a 100% confidence).
- `Detect only Targeted Plates` - A yes/no select box to specify if plates not in target list shall be ignored.
- `Strict Targeting` - A yes/no select box to specify if target matching shall be strict (plates must match exactly).
- `Assume target matching` - When strict targeting is off plates included in wider strings are detected (within the max. number of characters limit). This yes/no select box allow to specify if such detected plates shall be considered as being equal to the target. If yes is selected, the plugin will report these plates with the target string and with a 100% confidence. If no is selected, the plugin will report these plates individually. This option can be helpfull if the plate format includes a field for a logo which may be considered as a character by openalpr.

The configuration is saved to the database and applied when clicking on the `Save` button.

## Using

When a license plate is detected, this triggers an event with alarmed frame(s).
Depending on your configuration settings and video content, an event may contain multiple alarmed frames.

![Events](https://github.com/manupap1/libzoneminder-plugin-openalpr/blob/master/misc/events.png)

Licenses plates are stored in the event note field accessible by a click on the event detection cause.

![Event](https://github.com/manupap1/libzoneminder-plugin-openalpr/blob/master/misc/event.png)

Alarmed frames are highlighted with the plate's detection area(s).

![Frame](https://github.com/manupap1/libzoneminder-plugin-openalpr/blob/master/misc/frame.png)

The detected plate number(s) can be added in email or sms notifications by using the %ED% token in EMAIL_BODY or MESSAGE_BODY option.
