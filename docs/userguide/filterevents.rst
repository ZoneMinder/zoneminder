Filtering Events
================

The other columns on the main console window contain various event totals for your monitors over the last hour, day, week and month as well as a grand total and a total for events that you may have archived for safekeeping. Clicking on one of these totals or on the 'All' or 'Archive' links from the monitor window described above will present you with a new display. This is the full event window and contains a list of events selected according to a filter which will also pop up in its own window. Thus if you clicked on a 'day' total the filter will indicate that this is the period for which events are being filtered. The event listing window contains a similar listing to the recent events in the monitor window. The primary differences are that the frames and alarm frames and the score and maximum score are now broken out into their own columns, all of which can be sorted by clicking on the heading. Also this window will not refresh automatically, rather only on request. Other than that, you can choose to view events here or delete them as before.

The other window that appeared is a filter window. You can use this window to create your own filters or to modify existing ones. You can even save your favourite filters to re-use at a future date. Filtering itself is fairly simple; you first choose how many expressions you'd like your filter to contain. Changing this value will cause the window to redraw with a corresponding row for each expression. You then select what you want to filter on and how the expressions relate by choosing whether they are 'and' or 'or' relationships. For filters comprised of many expressions you will also get the option to bracket parts of the filter to ensure you can express it as desired. Then if you like choose how you want your results sorted and whether you want to limit the amount of events displayed.

There are several different elements to an event that you can filter on, some of which require further explanation. These are as follows, 'Date/Time' which must evaluate to a date and a time together, 'Date' and 'Time' which are variants which may only contain the relevant subsets of this, 'Weekday' which as expected is a day of the week.

All of the preceding elements take a very flexible free format of dates and time based on the PHP strtotime function (http://www.php.net/manual/en/function.strtotime.php). This allows values such as 'last Wednesday' etc to be entered. I recommend acquainting yourself with this function to see what the allowed formats are. However automated filters are run in perl and so are parsed by the Date::Manip package. Not all date formats are available in both so if you are saved your filter to do automatic deletions or other tasks you should make sure that the date and time format you use is compatible with both methods. The safest type of format to use is ‘-3 day’ or similar with easily parseable numbers and units are in English.

The other things you can filter on are all fairly self explanatory, except perhaps for 'Archived' which you can use to include or exclude Archived events. In general you'll probably do most filtering on un-archived events. There are also two elements, Disk Blocks and Disk Percent which don’t directly relate to the events themselves but to the disk partition on which the events are stored. These allow you to specify an amount of disk usage either in blocks or in percentage as returned by the ‘df’ command. They relate to the amount of disk space used and not the amount left free. Once your filter is specified, clicking 'submit' will filter the events according to your specification. As the disk based elements are not event related directly if you create a filter and include the term ‘DiskPercent > 95’ then if your current disk usage is over that amount when you submit the filter then all events will be listed whereas if it is less then none at all will. As such the disk related terms will tend to be used mostly for automatic filters (see below). If you have created a filter you want to keep, you can name it and save it by clicking 'Save'.

If you do this then the subsequent dialog will also allow you specify whether you want this filter automatically applied in order to delete events or upload events via ftp to another server and mail notifications of events to one or more email accounts. Emails and messages (essentially small emails intended for mobile phones or pagers) have a format defined in the Options screen, and may include a variety of tokens that can be substituted for various details of the event that caused them. This includes links to the event view or the filter as well as the option of attaching images or videos to the email itself. Be aware that tokens that represent links may require you to log in to access the actual page, and sometimes may function differently when viewed outside of the general ZoneMinder context. The tokens you can use are as follows.

:    %EI%           Id of the event
:    %EN%          Name of the event
:    %EC%          Cause of the event
:    %ED%          Event description
:    %ET%          Time of the event
:    %EL%          Length of the event
:    %EF%          Number of frames in the event
:    %EFA%        Number of alarm frames in the event
:    %EST%        Total score of the event
:    %ESA%       Average score of the event
:    %ESM%       Maximum score of the event
:    %EP%          Path to the event
:    %EPS%       Path to the event stream
:    %EPI%         Path to the event images
:    %EPI1%       Path to the first alarmed event image
:    %EPIM%      Path to the (first) event image with the highest score
:    %EI1%         Attach first alarmed event image
:    %EIM%        Attach (first) event image with the highest score
:    %EV%          Attach event mpeg video
:    %MN%         Name of the monitor
:    %MET%       Total number of events for the monitor
:    %MEH%       Number of events for the monitor in the last hour
:    %MED%       Number of events for the monitor in the last day
:    %MEW%      Number of events for the monitor in the last week
:    %MEM%      Number of events for the monitor in the last month
:    %MEA%       Number of archived events for the monitor
:    %MP%         Path to the monitor window
:    %MPS%       Path to the monitor stream
:    %MPI%        Path to the monitor recent image
:    %FN%          Name of the current filter that matched
:    %FP%          Path to the current filter that matched
:    %ZP%          Path to your ZoneMinder console

Finally you can also specify a script which is run on each matched event. This script should be readable and executable by your web server user. It will get run once per event and the relative path to the directory containing the event in question. Normally this will be of the form <MonitorName>/<EventId> so from this path you can derive both the monitor name and event id and perform any action you wish. Note that arbitrary commands are not allowed to be specified in the filter, for security the only thing it may contain is the full path to an executable. What that contains is entirely up to you however.

Filtering is a powerful mechanism you can use to eliminate events that fit a certain pattern however in many cases modifying the zone settings will better address this. Where it really comes into its own is generally in applying time filters, so for instance events that happen during weekdays or at certain times of the day are highlighted, uploaded or deleted. Additionally using disk related terms in your filters means you can automatically create filters that delete the oldest events when your disk gets full. Be warned however that if you use this strategy then you should limit the returned results to the amount of events you want deleted in each pass until the disk usage is at an acceptable level. If you do not do this then the first pass when the disk usage is high will match, and then delete, all events unless you have used other criteria inside of limits. ZoneMinder ships with a sample filter already installed, though disabled. The PurgeWhenFull filter can be used to delete the oldest events when your disk starts filling up. To use it you should select and load it in the filter interface, modify it to your requirements, and then save it making you sure you check the ‘Delete all matches’ option. This will then run in the background and ensure that your disk does not fill up with events.


Relative items in date strings
------------------------------

Relative items adjust a date (or the current date if none) forward or backward. The effects of relative items accumulate. Here are some examples:
 	

1 year
1 year ago
3 years
2 days

The unit of time displacement may be selected by the string ‘year’ or ‘month’ for moving by whole years or months. These are fuzzy units, as years and months are not all of equal duration. More precise units are ‘fortnight’ which is worth 14 days, ‘week’ worth 7 days, ‘day’ worth 24 hours, ‘hour’ worth 60 minutes, ‘minute’ or ‘min’ worth 60 seconds, and ‘second’ or ‘sec’ worth one second. An ‘s’ suffix on these units is accepted and ignored.

The unit of time may be preceded by a multiplier, given as an optionally signed number. Unsigned numbers are taken as positively signed. No number at all implies 1 for a multiplier. Following a relative item by the string ‘ago’ is equivalent to preceding the unit by a multiplier with value -1.

The string ‘tomorrow’ is worth one day in the future (equivalent to ‘day’), the string ‘yesterday’ is worth one day in the past (equivalent to ‘day ago’).

The strings ‘now’ or ‘today’ are relative items corresponding to zero-valued time displacement, these strings come from the fact a zero-valued time displacement represents the current time when not otherwise changed by previous items. They may be used to stress other items, like in ‘12:00 today’. The string ‘this’ also has the meaning of a zero-valued time displacement, but is preferred in date strings like ‘this thursday’.

When a relative item causes the resulting date to cross a boundary where the clocks were adjusted, typically for daylight saving time, the resulting date and time are adjusted accordingly.

The fuzz in units can cause problems with relative items. For example, ‘2003-07-31 -1 month’ might evaluate to 2003-07-01, because 2003-06-31 is an invalid date. To determine the previous month more reliably, you can ask for the month before the 15th of the current month. For example:
 	

 $ date -R
 Thu, 31 Jul 2003 13:02:39 -0700
 $ date --date='-1 month' +'Last month was %B?'
 Last month was July?
 $ date --date="$(date +%Y-%m-15) -1 month" +'Last month was %B!'
 Last month was June!


As this applies to ZoneMinder filters, you might want to search  for events in a period of time, or maybe for example create a purge filter that removes events older than 30 days.
For the later you would want at least two lines in your filter. The first line should be:

 [<Archive Status> <equal to> <Unarchived Only>] 

as you don't want to delete your archived events. 

Your second line to find events older than 30 days would be:

 [and <Date><less than> -30 days] 

You use "less than" to indicate that you want to match events before the specified date, and you specify "-30 days" to indicate a date 30 days before the time the filter is run. Of course you could use 30 days ago as well(?).

You should always test your filters before enabling any actions based on them to make sure they consistently return the results you want. You can use the submit button to see what events are returned by your query.

