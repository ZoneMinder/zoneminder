Options - Web
<<<<<<< HEAD
=============

.. image:: images/Options_web.png


=======
-------------

.. image:: images/Options_web.png

WEB_TITLE_PREFIX - If you have more than one installation of ZoneMinder it can be helpful to display different titles for each one. Changing this option allows you to customise the window titles to include further information to aid identification.

WEB_RESIZE_CONSOLE - Traditionally the main ZoneMinder web console window has resized itself to shrink to a size small enough to list only the monitors that are actually present. This is intended to make the window more unobtrusize but may not be to everyones tastes, especially if opened in a tab in browsers which support this kind if layout. Switch this option off to have the console window size left to the users preference

WEB_POPUP_ON_ALARM - When viewing a live monitor stream you can specify whether you want the window to pop to the front if an alarm occurs when the window is minimised or behind another window. This is most useful if your monitors are over doors for example when they can pop up if someone comes to the doorway.

WEB_SOUND_ON_ALARM - When viewing a live monitor stream you can specify whether you want the window to play a sound to alert you if an alarm occurs.

WEB_ALARM_SOUND - You can specify a sound file to play if an alarm occurs whilst you are watching a live monitor stream. So long as your browser understands the format it does not need to be any particular type. This file should be placed in the sounds directory defined earlier.

WEB_COMPACT_MONTAGE - The montage view shows the output of all of your active monitors in one window. This include a small menu and status information for each one. This can increase the web traffic and make the window larger than may be desired. Setting this option on removes all this extraneous information and just displays the images.

WEB_EVENT_SORT_FIELD - Events in lists can be initially ordered in any way you want. This option controls what field is used to sort them. You can modify this ordering from filters or by clicking on headings in the lists themselves. Bear in mind however that the 'Prev' and 'Next' links, when scrolling through events, relate to the ordering in the lists and so not always to time based ordering.

WEB_EVENT_SORT_ORDER - Events in lists can be initially ordered in any way you want. This option controls what order (ascending or descending) is used to sort them. You can modify this ordering from filters or by clicking on headings in the lists themselves. Bear in mind however that the 'Prev' and 'Next' links, when scrolling through events, relate to the ordering in the lists and so not always to time based ordering.

WEB_EVENTS_PER_PAGE - In the event list view you can either list all events or just a page at a time. This option controls how many events are listed per page in paged mode and how often to repeat the column headers in non-paged mode.

WEB_LIST_THUMBS - Ordinarily the event lists just display text details of the events to save space and time. By switching this option on you can also display small thumbnails to help you identify events of interest. The size of these thumbnails is controlled by the following two options.

WEB_LIST_THUMB_WIDTH - This options controls the width of the thumbnail images that appear in the event lists. It should be fairly small to fit in with the rest of the table. If you prefer you can specify a height instead in the next option but you should only use one of the width or height and the other option should be set to zero. If both width and height are specified then width will be used and height ignored.

WEB_LIST_THUMB_HEIGHT - This options controls the height of the thumbnail images that appear in the event lists. It should be fairly small to fit in with the rest of the table. If you prefer you can specify a width instead in the previous option but you should only use one of the width or height and the other option should be set to zero. If both width and height are specified then width will be used and height ignored.

WEB_USE_OBJECT_TAGS - There are two methods of including media content in web pages. The most common way is use the EMBED tag which is able to give some indication of the type of content. However this is not a standard part of HTML. The official method is to use OBJECT tags which are able to give more information allowing the correct media viewers etc to be loaded. However these are less widely supported and content may be specifically tailored to a particular platform or player. This option controls whether media content is enclosed in EMBED tags only or whether, where appropriate, it is additionally wrapped in OBJECT tags. Currently OBJECT tags are only used in a limited number of circumstances but they may become more widespread in the future. It is suggested that you leave this option on unless you encounter problems playing some content.
>>>>>>> fb436fb... Merge pull request #591 from SteveGilvarry/docs-updates
