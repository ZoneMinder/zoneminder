<?php
//
// ZoneMinder web IL Hebrew language file, $Date$, $Revision$
// Copyright (C) 2001-2008 Philip Coombes
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
//

// ZoneMinder Hebrew Translation by oc666@netvision.net.il

// Notes for Translators
// 0. Get some credit, put your name in the line above (optional)
// 1. When composing the language tokens in your language you should try and keep to roughly the
//   same length text if possible. Abbreviate where necessary as spacing is quite close in a number of places.
// 2. There are four types of string replacement
//   a) Simple replacements are words or short phrases that are static and used directly. This type of
//     replacement can be used 'as is'.
//   b) Complex replacements involve some dynamic element being included and so may require substitution
//     or changing into a different order. The token listed in this file will be passed through sprintf as
//     a formatting string. If the dynamic element is a number you will usually need to use a variable
//     replacement also as described below.
//   c) Variable replacements are used in conjunction with complex replacements and involve the generation
//     of a singular or plural noun depending on the number passed into the zmVlang function. See the 
//     the zmVlang section below for a further description of this.
//   d) Optional strings which can be used to replace the prompts and/or help text for the Options section
//     of the web interface. These are not listed below as they are quite large and held in the database
//     so that they can also be used by the zmconfig.pl script. However you can build up your own list
//     quite easily from the Config table in the database if necessary.
// 3. The tokens listed below are not used to build up phrases or sentences from single words. Therefore
//   you can safely assume that a single word token will only be used in that context.
// 4. In new language files, or if you are changing only a few words or phrases it makes sense from a 
//   maintenance point of view to include the original language file and override the old definitions rather
//   than copy all the language tokens across. To do this change the line below to whatever your base language
//   is and uncomment it.
// require_once( 'zm_lang_en_gb.php' );

// You may need to change the character set here, if your web server does not already
// do this by default, uncomment this if required.
//
// Example
header( "Content-Type: text/html; charset=UTF-8" );

// You may need to change your locale here if your default one is incorrect for the
// language described in this file, or if you have multiple languages supported.
// If you do need to change your locale, be aware that the format of this function
// is subtlely different in versions of PHP before and after 4.3.0, see
// http://uk2.php.net/manual/en/function.setlocale.php for details.
// Also be aware that changing the whole locale may affect some floating point or decimal 
// arithmetic in the database, if this is the case change only the individual locale areas
// that don't affect this rather than all at once. See the examples below.
// Finally, depending on your setup, PHP may not enjoy have multiple locales in a shared 
// threaded environment, if you get funny errors it may be this.
//
// Examples
// setlocale( 'LC_ALL', 'he_IL' ); All locale settings pre-4.3.0
setlocale( LC_ALL, 'he_IL' ); //All locale settings 4.3.0 and after
// setlocale( LC_CTYPE, 'he_IL' ); Character class settings 4.3.0 and after
// setlocale( LC_TIME, 'he_IL' ); Date and time formatting 4.3.0 and after

// Simple String Replacements
$SLANG = array(
    '24BitColour'          => 'צבע 24 ביט',
    '32BitColour'          => 'צבע 32 ביט',          // Added - 2011-06-15
    '8BitGrey'             => 'גווני אפור 8 ביט',
    'Action'               => 'פעולה',
    'Actual'               => 'מקורי',
    'AddNewControl'        => 'הוסף קונטרול חדש',
    'AddNewMonitor'        => 'הוסף מוניטור חדש',
    'AddNewServer'         => 'Add New Server',         // Added - 2018-08-30
    'AddNewStorage'        => 'Add New Storage',        // Added - 2018-08-30
    'AddNewUser'           => 'הוסף משתמש חדש',
    'AddNewZone'           => 'הוסף איזור חדש',
    'Alarm'                => 'אזעקה',
    'AlarmBrFrames'        => 'אזעקת<br/>פריימים',
    'AlarmFrame'           => 'אזעקת פריימים',
    'AlarmFrameCount'      => 'ספירת אזעקות פריימים',
    'AlarmLimits'          => 'הגבלות אזעקה',
    'AlarmMaximumFPS'      => 'Alarm Maximum FPS',
    'AlarmPx'              => 'אזעקת Px',
    'AlarmRGBUnset'        => 'הינך חייב לאתחל אזעקת צבע',
    'AlarmRefImageBlendPct'=> 'Alarm Reference Image Blend %ge', // Added - 2015-04-18
    'Alert'                => 'התראה',
    'All'                  => 'הכל',
    'AnalysisFPS'          => 'Analysis FPS',           // Added - 2015-07-22
    'AnalysisUpdateDelay'  => 'Analysis Update Delay',  // Added - 2015-07-23
    'Apply'                => 'החל',
    'ApplyingStateChange'  => 'החל שינוי מצב',
    'ArchArchived'         => 'ארכיב בלבד',
    'ArchUnarchived'       => 'לא לארכיב בלבד',
    'Archive'              => 'ארכיב',
    'Archived'             => 'אורכב',
    'Area'                 => 'אזור',
    'AreaUnits'            => 'אזור (px/%)',
    'AttrAlarmFrames'      => 'Alarm Frames',
    'AttrArchiveStatus'    => 'Archive Status',
    'AttrAvgScore'         => 'ניקוד ממוצע',
    'AttrCause'            => 'סיבה',
    'AttrDiskBlocks'       => 'Disk Blocks',
    'AttrDiskPercent'      => 'Disk Percent',
    'AttrDiskSpace'        => 'Disk Space',             // Added - 2018-08-30
    'AttrDuration'         => 'משך זמן',
    'AttrEndDate'          => 'End Date',               // Added - 2018-08-30
    'AttrEndDateTime'      => 'End Date/Time',          // Added - 2018-08-30
    'AttrEndTime'          => 'End Time',               // Added - 2018-08-30
    'AttrEndWeekday'       => 'End Weekday',            // Added - 2018-08-30
    'AttrFilterServer'     => 'Server Filter is Running On', // Added - 2018-08-30
    'AttrFrames'           => 'פריימים',
    'AttrId'               => 'Id',
    'AttrMaxScore'         => 'ניקוד מקסימלי',
    'AttrMonitorId'        => 'Monitor Id',
    'AttrMonitorName'      => 'שם מוניטור',
    'AttrMonitorServer'    => 'Server Monitor is Running On', // Added - 2018-08-30
    'AttrName'             => 'שם',
    'AttrNotes'            => 'הערות',
    'AttrStartDate'        => 'Start Date',             // Added - 2018-08-30
    'AttrStartDateTime'    => 'Start Date/Time',        // Added - 2018-08-30
    'AttrStartTime'        => 'Start Time',             // Added - 2018-08-30
    'AttrStartWeekday'     => 'Start Weekday',          // Added - 2018-08-30
    'AttrStateId'          => 'Run State',              // Added - 2018-08-30
    'AttrStorageArea'      => 'Storage Area',           // Added - 2018-08-30
    'AttrStorageServer'    => 'Server Hosting Storage', // Added - 2018-08-30
    'AttrSystemLoad'       => 'System Load',
    'AttrTotalScore'       => 'סך סכום',
    'Auto'                 => 'אוטו',
    'AutoStopTimeout'      => 'פסק זמן עצירה אוטו',
    'Available'            => 'Available',              // Added - 2009-03-31
    'AvgBrScore'           => 'ניקוד<br/>ממוצע',
    'Background'           => 'רקע',
    'BackgroundFilter'     => 'הרץ מסנן ברקע',
    'BadAlarmFrameCount'   => 'Alarm frame count must be an integer of one or more',
    'BadAlarmMaxFPS'       => 'Alarm Maximum FPS must be a positive integer or floating point value',
    'BadAnalysisFPS'       => 'Analysis FPS must be a positive integer or floating point value', // Added - 2015-07-22
    'BadAnalysisUpdateDelay'=> 'Analysis update delay must be set to an integer of zero or more', // Added - 2015-07-23
    'BadChannel'           => 'Channel must be set to an integer of zero or more',
    'BadColours'           => 'Target colour must be set to a valid value', // Added - 2011-06-15
    'BadDevice'            => 'Device must be set to a valid value',
    'BadFPSReportInterval' => 'FPS report interval buffer count must be an integer of 0 or more',
    'BadFormat'            => 'Format must be set to an integer of zero or more',
    'BadFrameSkip'         => 'Frame skip count must be an integer of zero or more',
    'BadHeight'            => 'Height must be set to a valid value',
    'BadHost'              => 'Host must be set to a valid ip address or hostname, do not include http://',
    'BadImageBufferCount'  => 'Image buffer size must be an integer of 10 or more',
    'BadLabelX'            => 'Label X co-ordinate must be set to an integer of zero or more',
    'BadLabelY'            => 'Label Y co-ordinate must be set to an integer of zero or more',
    'BadMaxFPS'            => 'Maximum FPS must be a positive integer or floating point value',
    'BadMotionFrameSkip'   => 'Motion Frame skip count must be an integer of zero or more',
    'BadNameChars'         => 'Names may only contain alphanumeric characters, spaces plus hyphen and underscore',
    'BadPalette'           => 'Palette must be set to a valid value', // Added - 2009-03-31
    'BadPath'              => 'Path must be set to a valid value',
    'BadPort'              => 'Port must be set to a valid number',
    'BadPostEventCount'    => 'Post event image count must be an integer of zero or more',
    'BadPreEventCount'     => 'Pre event image count must be at least zero, and less than image buffer size',
    'BadRefBlendPerc'      => 'Reference blend percentage must be a positive integer',
    'BadSectionLength'     => 'Section length must be an integer of 30 or more',
    'BadSignalCheckColour' => 'Signal check colour must be a valid RGB colour string',
    'BadSourceType'        => 'Source Type "Web Site" requires the Function to be set to "Monitor"', // Added - 2018-08-30
    'BadStreamReplayBuffer'=> 'Stream replay buffer must be an integer of zero or more',
    'BadWarmupCount'       => 'Warmup frames must be an integer of zero or more',
    'BadWebColour'         => 'Web colour must be a valid web colour string',
    'BadWebSitePath'       => 'Please enter a complete website url, including the http:// or https:// prefix.', // Added - 2018-08-30
    'BadWidth'             => 'Width must be set to a valid value',
    'Bandwidth'            => 'רוחב פס',
    'BandwidthHead'        => 'Bandwidth',	// This is the end of the bandwidth status on the top of the console, different in many language due to phrasing
    'BlobPx'               => 'Blob Px',
    'BlobSizes'            => 'Blob Sizes',
    'Blobs'                => 'Blobs',
    'Brightness'           => 'בהירות',
    'Buffer'               => 'Buffer',                 // Added - 2015-04-18
    'Buffers'              => 'Buffers',
    'CSSDescription'       => 'Change the default css for this computer', // Added - 2015-04-18
    'CanAutoFocus'         => 'אפשר התמקדות אוטומטי',
    'CanAutoGain'          => 'Can Auto Gain',
    'CanAutoIris'          => 'Can Auto Iris',
    'CanAutoWhite'         => 'Can Auto White Bal.',
    'CanAutoZoom'          => 'אפשר זום אוטומטי',
    'CanFocus'             => 'אפשר התמקדות',
    'CanFocusAbs'          => 'אפשר התמקדות אבסולוטי',
    'CanFocusCon'          => 'אפשר התמקדות מתמשך',
    'CanFocusRel'          => 'אפשר התמקדות יחסי',
    'CanGain'              => 'Can Gain ',
    'CanGainAbs'           => 'Can Gain Absolute',
    'CanGainCon'           => 'Can Gain Continuous',
    'CanGainRel'           => 'Can Gain Relative',
    'CanIris'              => 'Can Iris',
    'CanIrisAbs'           => 'Can Iris Absolute',
    'CanIrisCon'           => 'Can Iris Continuous',
    'CanIrisRel'           => 'Can Iris Relative',
    'CanMove'              => 'אפשר תנועה',
    'CanMoveAbs'           => 'אפשר תנועה אבסולוטית',
    'CanMoveCon'           => 'אפשר תזוזה מתמשכת',
    'CanMoveDiag'          => 'Can Move Diagonally',
    'CanMoveMap'           => 'Can Move Mapped',
    'CanMoveRel'           => 'אפשר תזוזה יחסית',
    'CanPan'               => 'Can Pan' ,
    'CanReset'             => 'אפשר אתחול',
    'CanSetPresets'        => 'Can Set Presets',
    'CanSleep'             => 'אפשר מצב שינה',
    'CanTilt'              => 'אפשר זעזוע',
    'CanWake'              => 'אפשר יציאה ממצב שינה',
    'CanWhite'             => 'Can White Balance',
    'CanWhiteAbs'          => 'Can White Bal. Absolute',
    'CanWhiteBal'          => 'Can White Bal.',
    'CanWhiteCon'          => 'Can White Bal. Continuous',
    'CanWhiteRel'          => 'Can White Bal. Relative',
    'CanZoom'              => 'אפשר זום',
    'CanZoomAbs'           => 'אפשר זום אבסולוטי',
    'CanZoomCon'           => 'אפשר זום מתמשך',
    'CanZoomRel'           => 'אפשר זום יחסי',
    'Cancel'               => 'בטל',
    'CancelForcedAlarm'    => 'Cancel Forced Alarm',
    'CaptureHeight'        => 'Capture Height',
    'CaptureMethod'        => 'Capture Method',         // Added - 2009-02-08
    'CapturePalette'       => 'Capture Palette',
    'CaptureResolution'    => 'Capture Resolution',     // Added - 2015-04-18
    'CaptureWidth'         => 'Capture Width',
    'Cause'                => 'סיבה',
    'CheckMethod'          => 'Alarm Check Method',
    'ChooseDetectedCamera' => 'Choose Detected Camera', // Added - 2009-03-31
    'ChooseFilter'         => 'בחר מסנן',
    'ChooseLogFormat'      => 'Choose a log format',    // Added - 2011-06-17
    'ChooseLogSelection'   => 'Choose a log selection', // Added - 2011-06-17
    'ChoosePreset'         => 'Choose Preset',
    'Clear'                => 'Clear',                  // Added - 2011-06-16
    'CloneMonitor'         => 'Clone',                  // Added - 2018-08-30
    'Close'                => 'סגור',
    'Colour'               => 'צבע',
    'Command'              => 'פקודה',
    'Component'            => 'Component',              // Added - 2011-06-16
    'ConcurrentFilter'     => 'Run filter concurrently', // Added - 2018-08-30
    'Config'               => 'תצורה',
    'ConfiguredFor'        => 'תצורה עבור',
    'ConfirmDeleteEvents'  => 'Are you sure you wish to delete the selected events?',
    'ConfirmPassword'      => 'אשר סיסמא',
    'ConjAnd'              => 'ו',
    'ConjOr'               => 'או',
    'Console'              => 'קונסול',
    'ContactAdmin'         => 'צור קשר עם מנהל המערכת בשביל פרטים נוספים.',
    'Continue'             => 'המשך',
    'Contrast'             => 'ניגודיות',
    'Control'              => 'קונטרול',
    'ControlAddress'       => 'כתובת הקונטרול',
    'ControlCap'           => 'יכולת הקונטרול',
    'ControlCaps'          => 'יכולות הקונטרול',
    'ControlDevice'        => 'התקן הקונטרול',
    'ControlType'          => 'סוג הקונטרול',
    'Controllable'         => 'Controllable',
    'Current'              => 'Current',                // Added - 2015-04-18
    'Cycle'                => 'מחזורי',
    'CycleWatch'           => 'צפייה מחזורית',
    'DateTime'             => 'Date/Time',              // Added - 2011-06-16
    'Day'                  => 'יום',
    'Debug'                => 'Debug',
    'DefaultRate'          => 'Default Rate',
    'DefaultScale'         => 'Default Scale',
    'DefaultView'          => 'Default View',
    'Deinterlacing'        => 'Deinterlacing',          // Added - 2015-04-18
    'Delay'                => 'Delay',                  // Added - 2015-04-18
    'Delete'               => 'מחק',
    'DeleteAndNext'        => 'מחק & הבא',
    'DeleteAndPrev'        => 'מחק & הקודם',
    'DeleteSavedFilter'    => 'מחק מסנן שמור',
    'Description'          => 'תיאור',
    'DetectedCameras'      => 'Detected Cameras',       // Added - 2009-03-31
    'DetectedProfiles'     => 'Detected Profiles',      // Added - 2015-04-18
    'Device'               => 'Device',                 // Added - 2009-02-08
    'DeviceChannel'        => 'ערוץ ההתקן',
    'DeviceFormat'         => 'תבנית ההתקן',
    'DeviceNumber'         => 'מספר ההתקן',
    'DevicePath'           => 'נתיב ההתקן',
    'Devices'              => 'התקנים',
    'Dimensions'           => 'מימדים',
    'DisableAlarms'        => 'נטרל אזעקות',
    'Disk'                 => 'דיסק',
    'Display'              => 'Display',                // Added - 2011-01-30
    'Displaying'           => 'Displaying',             // Added - 2011-06-16
    'DoNativeMotionDetection'=> 'Do Native Motion Detection',
    'Donate'               => 'תרום בבקשה',
    'DonateAlready'        => 'לא, תרמתי כבר',
    'DonateEnticement'     => 'You\'ve been running ZoneMinder for a while now and hopefully are finding it a useful addition to your home or workplace security. Although ZoneMinder is, and will remain, free and open source, it costs money to develop and support. If you would like to help support future development and new features then please consider donating. Donating is, of course, optional but very much appreciated and you can donate as much or as little as you like.<br><br>If you would like to donate please select the option below or go to http://www.zoneminder.com/donate.html in your browser.<br><br>Thank you for using ZoneMinder and don\'t forget to visit the forums on ZoneMinder.com for support or suggestions about how to make your ZoneMinder experience even better.',
    'DonateRemindDay'      => 'עדיין לא, הזכר לא בעוד יום אחד',
    'DonateRemindHour'     => 'עדיין לא, הזכר לי בעוד שעה אחת',
    'DonateRemindMonth'    => 'עדיין לא, הזכר לי בעוד חודש אחד',
    'DonateRemindNever'    => 'לא, אני לא רוצה לתרום, אל תתזכר אותי',
    'DonateRemindWeek'     => 'עדיין לא, הזכר לי בעוד שבוע אחד',
    'DonateYes'            => 'כן, אני מעוניין לתרום עכשיו',
    'Download'             => 'הורד',
    'DownloadVideo'        => 'Download Video',         // Added - 2018-08-30
    'DuplicateMonitorName' => 'Duplicate Monitor Name', // Added - 2009-03-31
    'Duration'             => 'משך זמן',
    'Edit'                 => 'ערוך',
    'EditLayout'           => 'Edit Layout',            // Added - 2018-08-30
    'Email'                => 'דוא"ל',
    'EnableAlarms'         => 'אפשר אזעקות',
    'Enabled'              => 'אפשר',
    'EnterNewFilterName'   => 'הזן מסנן חדש',
    'Error'                => 'שגיאה',
    'ErrorBrackets'        => 'Error, please check you have an equal number of opening and closing brackets',
    'ErrorValidValue'      => 'Error, please check that all terms have a valid value',
    'Etc'                  => 'וכו\'',
    'Event'                => 'אירוע',
    'EventFilter'          => 'מסנן אירוע',
    'EventId'              => 'זיהוי אירוע',
    'EventName'            => 'שם אירוע',
    'EventPrefix'          => 'Event Prefix',
    'Events'               => 'אירועים',
    'Exclude'              => 'ללא',
    'Execute'              => 'בצע',
    'Exif'                 => 'Embed EXIF data into image', // Added - 2018-08-30
    'Export'               => 'יצא',
    'ExportDetails'        => 'יצא פרטי אירוע',
    'ExportFailed'         => 'יצוא נכשל',
    'ExportFormat'         => 'יצא תבנית קובץ',
    'ExportFormatTar'      => 'Tar',
    'ExportFormatZip'      => 'Zip',
    'ExportFrames'         => 'Export Frame Details',
    'ExportImageFiles'     => 'יצא קבצי תמונה',
    'ExportLog'            => 'Export Log',             // Added - 2011-06-17
    'ExportMiscFiles'      => 'יצא קבצים אחרים (אם ישנם)',
    'ExportOptions'        => 'יצא אפשרויות',
    'ExportSucceeded'      => 'Export Succeeded',       // Added - 2009-02-08
    'ExportVideoFiles'     => 'Export Video Files (if present)',
    'Exporting'            => 'מייצא',
    'FPS'                  => 'fps',
    'FPSReportInterval'    => 'FPS Report Interval',
    'FTP'                  => 'FTP',
    'Far'                  => 'Far',
    'FastForward'          => 'Fast Forward',
    'Feed'                 => 'Feed',
    'Ffmpeg'               => 'Ffmpeg',                 // Added - 2009-02-08
    'File'                 => 'קובץ',
    'Filter'               => 'Filter',                 // Added - 2015-04-18
    'FilterArchiveEvents'  => 'ארכב תואמים',
    'FilterDeleteEvents'   => 'מחק תואמים',
    'FilterEmailEvents'    => 'שלח דואר של כל התואמים',
    'FilterExecuteEvents'  => 'Execute command on all matches',
    'FilterLog'            => 'Filter log',             // Added - 2015-04-18
    'FilterMessageEvents'  => 'Message details of all matches',
    'FilterMoveEvents'     => 'Move all matches',       // Added - 2018-08-30
    'FilterPx'             => 'Filter Px',
    'FilterUnset'          => 'עליך לציין רוחב וגובה מסנן',
    'FilterUpdateDiskSpace'=> 'Update used disk space', // Added - 2018-08-30
    'FilterUploadEvents'   => 'עלה את כל התואמים',
    'FilterVideoEvents'    => 'צור וידאו לכל התואמים',
    'Filters'              => 'מסננים',
    'First'                => 'הראשון',
    'FlippedHori'          => 'Flipped Horizontally',
    'FlippedVert'          => 'Flipped Vertically',
    'FnMocord'              => 'Mocord',            // Added 2013.08.16.
    'FnModect'              => 'Modect',            // Added 2013.08.16.
    'FnMonitor'             => 'Monitor',            // Added 2013.08.16.
    'FnNodect'              => 'Nodect',            // Added 2013.08.16.
    'FnNone'                => 'None',            // Added 2013.08.16.
    'FnRecord'              => 'Record',            // Added 2013.08.16.
    'Focus'                => 'התמקד',
    'ForceAlarm'           => 'הכרח אזעקה',
    'Format'               => 'תבנית',
    'Frame'                => 'פריים',
    'FrameId'              => 'Frame Id',
    'FrameRate'            => 'Frame Rate',
    'FrameSkip'            => 'דלג פריים',
    'Frames'               => 'פריימים',
    'Func'                 => 'פונק',
    'Function'             => 'פונקציה',
    'Gain'                 => 'Gain',
    'General'              => 'כללי',
    'GenerateDownload'     => 'Generate Download',      // Added - 2018-08-30
    'GenerateVideo'        => 'צור וידאו',
    'GeneratingVideo'      => 'מייצר וידאו',
    'GoToZoneMinder'       => 'בקר ZoneMinder.com',
    'Grey'                 => 'אפור',
    'Group'                => 'קבוצה',
    'Groups'               => 'קבוצות',
    'HasFocusSpeed'        => 'Has Focus Speed',
    'HasGainSpeed'         => 'Has Gain Speed',
    'HasHomePreset'        => 'Has Home Preset',
    'HasIrisSpeed'         => 'Has Iris Speed',
    'HasPanSpeed'          => 'Has Pan Speed',
    'HasPresets'           => 'Has Presets',
    'HasTiltSpeed'         => 'Has Tilt Speed',
    'HasTurboPan'          => 'Has Turbo Pan',
    'HasTurboTilt'         => 'Has Turbo Tilt',
    'HasWhiteSpeed'        => 'Has White Bal. Speed',
    'HasZoomSpeed'         => 'Has Zoom Speed',
    'High'                 => 'גבוה',
    'HighBW'               => 'גבוה ר/פ',
    'Home'                 => 'בית',
    'Hostname'             => 'Hostname',               // Added - 2018-08-30
    'Hour'                 => 'שעה',
    'Hue'                  => 'Hue',
    'Id'                   => 'זיהוי',
    'Idle'                 => 'המתנה',
    'Ignore'               => 'התעלם',
    'Image'                => 'תמונה',
    'ImageBufferSize'      => 'Image Buffer Size (frames)',
    'Images'               => 'תמונות',
    'In'                   => 'בתוך',
    'Include'              => 'כלול',
    'Inverted'             => 'הפוך',
    'Iris'                 => 'Iris',
    'KeyString'            => 'מחרוזת תוים',
    'Label'                => 'תווית',
    'Language'             => 'שפה',
    'Last'                 => 'אחרון',
    'Layout'               => 'Layout',                 // Added - 2009-02-08
    'Level'                => 'Level',                  // Added - 2011-06-16
    'Libvlc'               => 'Libvlc',
    'LimitResultsPost'     => 'תוצאות בלבד;', // This is used at the end of the phrase 'Limit to first N results only'
    'LimitResultsPre'      => 'הגבל לראשון', // This is used at the beginning of the phrase 'Limit to first N results only'
    'Line'                 => 'Line',                   // Added - 2011-06-16
    'LinkedMonitors'       => 'מוניטורים מקושרים',
    'List'                 => 'רשימה',
    'ListMatches'          => 'List Matches',           // Added - 2018-08-30
    'Load'                 => 'טען',
    'Local'                => 'מקומי',
    'Log'                  => 'Log',                    // Added - 2011-06-16
    'LoggedInAs'           => 'התחבר כ',
    'Logging'              => 'Logging',                // Added - 2011-06-16
    'LoggingIn'            => 'מתחבר',
    'Login'                => 'התחבר',
    'Logout'               => 'התנתק',
    'Logs'                 => 'Logs',                   // Added - 2011-06-17
    'Low'                  => 'נמוך',
    'LowBW'                => 'נמוך ר/פ',
    'Main'                 => 'מרכזי',
    'Man'                  => 'מדריך',
    'Manual'               => 'מדריך',
    'Mark'                 => 'סמן',
    'Max'                  => 'מקס',
    'MaxBandwidth'         => 'רוחב פס מקס',
    'MaxBrScore'           => 'ניקוד<br/>מקסימלי',
    'MaxFocusRange'        => 'Max Focus Range',
    'MaxFocusSpeed'        => 'Max Focus Speed',
    'MaxFocusStep'         => 'Max Focus Step',
    'MaxGainRange'         => 'Max Gain Range',
    'MaxGainSpeed'         => 'Max Gain Speed',
    'MaxGainStep'          => 'Max Gain Step',
    'MaxIrisRange'         => 'Max Iris Range',
    'MaxIrisSpeed'         => 'Max Iris Speed',
    'MaxIrisStep'          => 'Max Iris Step',
    'MaxPanRange'          => 'Max Pan Range',
    'MaxPanSpeed'          => 'Max Pan Speed',
    'MaxPanStep'           => 'Max Pan Step',
    'MaxTiltRange'         => 'Max Tilt Range',
    'MaxTiltSpeed'         => 'Max Tilt Speed',
    'MaxTiltStep'          => 'Max Tilt Step',
    'MaxWhiteRange'        => 'Max White Bal. Range',
    'MaxWhiteSpeed'        => 'Max White Bal. Speed',
    'MaxWhiteStep'         => 'Max White Bal. Step',
    'MaxZoomRange'         => 'Max Zoom Range',
    'MaxZoomSpeed'         => 'Max Zoom Speed',
    'MaxZoomStep'          => 'Max Zoom Step',
    'MaximumFPS'           => 'Maximum FPS',
    'Medium'               => 'בינוני',
    'MediumBW'             => 'Medium B/W',
    'Message'              => 'Message',                // Added - 2011-06-16
    'MinAlarmAreaLtMax'    => 'Minimum alarm area should be less than maximum',
    'MinAlarmAreaUnset'    => 'You must specify the minimum alarm pixel count',
    'MinBlobAreaLtMax'     => 'Minimum blob area should be less than maximum',
    'MinBlobAreaUnset'     => 'You must specify the minimum blob pixel count',
    'MinBlobLtMinFilter'   => 'Minimum blob area should be less than or equal to minimum filter area',
    'MinBlobsLtMax'        => 'Minimum blobs should be less than maximum',
    'MinBlobsUnset'        => 'You must specify the minimum blob count',
    'MinFilterAreaLtMax'   => 'Minimum filter area should be less than maximum',
    'MinFilterAreaUnset'   => 'You must specify the minimum filter pixel count',
    'MinFilterLtMinAlarm'  => 'Minimum filter area should be less than or equal to minimum alarm area',
    'MinFocusRange'        => 'Min Focus Range',
    'MinFocusSpeed'        => 'Min Focus Speed',
    'MinFocusStep'         => 'Min Focus Step',
    'MinGainRange'         => 'Min Gain Range',
    'MinGainSpeed'         => 'Min Gain Speed',
    'MinGainStep'          => 'Min Gain Step',
    'MinIrisRange'         => 'Min Iris Range',
    'MinIrisSpeed'         => 'Min Iris Speed',
    'MinIrisStep'          => 'Min Iris Step',
    'MinPanRange'          => 'Min Pan Range',
    'MinPanSpeed'          => 'Min Pan Speed',
    'MinPanStep'           => 'Min Pan Step',
    'MinPixelThresLtMax'   => 'Minimum pixel threshold should be less than maximum',
    'MinPixelThresUnset'   => 'You must specify a minimum pixel threshold',
    'MinTiltRange'         => 'Min Tilt Range',
    'MinTiltSpeed'         => 'Min Tilt Speed',
    'MinTiltStep'          => 'Min Tilt Step',
    'MinWhiteRange'        => 'Min White Bal. Range',
    'MinWhiteSpeed'        => 'Min White Bal. Speed',
    'MinWhiteStep'         => 'Min White Bal. Step',
    'MinZoomRange'         => 'Min Zoom Range',
    'MinZoomSpeed'         => 'Min Zoom Speed',
    'MinZoomStep'          => 'Min Zoom Step',
    'Misc'                 => 'Misc',
    'Mode'                 => 'Mode',                   // Added - 2015-04-18
    'Monitor'              => 'מוניטור',
    'MonitorIds'           => 'Monitor Ids',
    'MonitorPreset'        => 'Monitor Preset',
    'MonitorPresetIntro'   => 'Select an appropriate preset from the list below.<br><br>Please note that this may overwrite any values you already have configured for this monitor.<br><br>',
    'MonitorProbe'         => 'Monitor Probe',          // Added - 2009-03-31
    'MonitorProbeIntro'    => 'The list below shows detected analog and network cameras and whether they are already being used or available for selection.<br/><br/>Select the desired entry from the list below.<br/><br/>Please note that not all cameras may be detected and that choosing a camera here may overwrite any values you already have configured for the current monitor.<br/><br/>', // Added - 2009-03-31
    'Monitors'             => 'מוניטורים',
    'Montage'              => 'Montage',
    'MontageReview'        => 'Montage Review',         // Added - 2018-08-30
    'Month'                => 'חודש',
    'More'                 => 'More',                   // Added - 2011-06-16
    'MotionFrameSkip'      => 'Motion Frame Skip',
    'Move'                 => 'הזז',
    'Mtg2widgrd'            => '2-wide grid',              // Added 2013.08.15.
    'Mtg3widgrd'            => '3-wide grid',              // Added 2013.08.15.
    'Mtg3widgrx'            => '3-wide grid, scaled, enlarge on alarm',              // Added 2013.08.15.
    'Mtg4widgrd'            => '4-wide grid',              // Added 2013.08.15.
    'MtgDefault'            => 'Default',              // Added 2013.08.15.
    'MustBeGe'             => 'must be greater than or equal to',
    'MustBeLe'             => 'must be less than or equal to',
    'MustConfirmPassword'  => 'You must confirm the password',
    'MustSupplyPassword'   => 'You must supply a password',
    'MustSupplyUsername'   => 'You must supply a username',
    'Name'                 => 'שם',
    'Near'                 => 'ליד',
    'Network'              => 'רשת',
    'New'                  => 'חדש',
    'NewGroup'             => 'קבוצה חדשה',
    'NewLabel'             => 'תווית חדשה',
    'NewPassword'          => 'סיסמא חדשה',
    'NewState'             => 'מצב חדש',
    'NewUser'              => 'משתמש חדש',
    'Next'                 => 'הבא',
    'No'                   => 'לא',
    'NoDetectedCameras'    => 'No Detected Cameras',    // Added - 2009-03-31
    'NoDetectedProfiles'   => 'No Detected Profiles',   // Added - 2018-08-30
    'NoFramesRecorded'     => 'There are no frames recorded for this event',
    'NoGroup'              => 'ללא קבוצה',
    'NoSavedFilters'       => 'NoSavedFilters',
    'NoStatisticsRecorded' => 'There are no statistics recorded for this event/frame',
    'None'                 => 'ריק',
    'NoneAvailable'        => 'בלתי זמין',
    'Normal'               => 'נורמלי',
    'Notes'                => 'Notes',
    'NumPresets'           => 'Num Presets',
    'Off'                  => 'כבוי',
    'On'                   => 'דלוק',
    'OnvifCredentialsIntro'=> 'Please supply user name and password for the selected camera.<br/>If no user has been created for the camera then the user given here will be created with the given password.<br/><br/>', // Added - 2015-04-18
    'OnvifProbe'           => 'ONVIF',                  // Added - 2015-04-18
    'OnvifProbeIntro'      => 'The list below shows detected ONVIF cameras and whether they are already being used or available for selection.<br/><br/>Select the desired entry from the list below.<br/><br/>Please note that not all cameras may be detected and that choosing a camera here may overwrite any values you already have configured for the current monitor.<br/><br/>', // Added - 2015-04-18
    'OpEq'                 => 'שווה ל',
    'OpGt'                 => 'גדול מ',
    'OpGtEq'               => 'greater than or equal to',
    'OpIn'                 => 'in set',
    'OpIs'                 => 'is',                     // Added - 2018-08-30
    'OpIsNot'              => 'is not',                 // Added - 2018-08-30
    'OpLt'                 => 'פחות מ',
    'OpLtEq'               => 'less than or equal to',
    'OpMatches'            => 'matches',
    'OpNe'                 => 'אינו שווה',
    'OpNotIn'              => 'not in set',
    'OpNotMatches'         => 'אינו תואם',
    'Open'                 => 'פתח',
    'OptionHelp'           => 'OptionHelp',
    'OptionRestartWarning' => 'These changes may not come into effect fully\nwhile the system is running. When you have\nfinished making your changes please ensure that\nyou restart ZoneMinder.',
    'OptionalEncoderParam' => 'Optional Encoder Parameters', // Added - 2018-08-30
    'Options'              => 'אפשרויות',
    'OrEnterNewName'       => 'or enter new name',
    'Order'                => 'מיון',
    'Orientation'          => 'Orientation',
    'Out'                  => 'Out',
    'OverwriteExisting'    => 'Overwrite Existing',
    'Paged'                => 'Paged',
    'Pan'                  => 'Pan',
    'PanLeft'              => 'Pan Left',
    'PanRight'             => 'Pan Right',
    'PanTilt'              => 'Pan/Tilt',
    'Parameter'            => 'פרמטר',
    'Password'             => 'סיסמא',
    'PasswordsDifferent'   => 'The new and confirm passwords are different',
    'Paths'                => 'נתיבים',
    'Pause'                => 'Pause',
    'Phone'                => 'טלפון',
    'PhoneBW'              => 'ר/פ טלפון',
    'Pid'                  => 'PID',                    // Added - 2011-06-16
    'PixelDiff'            => 'Pixel Diff',
    'Pixels'               => 'פיקסלים',
    'Play'                 => 'Play',
    'PlayAll'              => 'נגן הכל',
    'PleaseWait'           => 'המתן בבקשה',
    'Plugins'              => 'Plugins',
    'Point'                => 'נקודה',
    'PostEventImageBuffer' => 'Post Event Image Count',
    'PreEventImageBuffer'  => 'Pre Event Image Count',
    'PreserveAspect'       => 'Preserve Aspect Ratio',
    'Preset'               => 'Preset',
    'Presets'              => 'Presets',
    'Prev'                 => 'הקודם',
    'Probe'                => 'Probe',                  // Added - 2009-03-31
    'ProfileProbe'         => 'Stream Probe',           // Added - 2015-04-18
    'ProfileProbeIntro'    => 'The list below shows the existing stream profiles of the selected camera .<br/><br/>Select the desired entry from the list below.<br/><br/>Please note that ZoneMinder cannot configure additional profiles and that choosing a camera here may overwrite any values you already have configured for the current monitor.<br/><br/>', // Added - 2015-04-18
    'Progress'             => 'Progress',               // Added - 2015-04-18
    'Protocol'             => 'Protocol',
    'RTSPDescribe'         => 'Use RTSP Response Media URL', // Added - 2018-08-30
    'RTSPTransport'        => 'RTSP Transport Protocol', // Added - 2018-08-30
    'Rate'                 => 'דירוג',
    'Real'                 => 'אמיתי',
    'RecaptchaWarning'     => 'Your reCaptcha secret key is invalid. Please correct it, or reCaptcha will not work', // Added - 2018-08-30
    'Record'               => 'הקלטה',
    'RecordAudio'          => 'Whether to store the audio stream when saving an event.', // Added - 2018-08-30
    'RefImageBlendPct'     => 'Reference Image Blend %ge',
    'Refresh'              => 'רענון',
    'Remote'               => 'מרוחק',
    'RemoteHostName'       => 'שם מארח מרוחק',
    'RemoteHostPath'       => 'נתיב מארח מרוחק',
    'RemoteHostPort'       => 'פורט מארח מרוחק',
    'RemoteHostSubPath'    => 'Remote Host SubPath',    // Added - 2009-02-08
    'RemoteImageColours'   => 'Remote Image Colours',
    'RemoteMethod'         => 'Remote Method',          // Added - 2009-02-08
    'RemoteProtocol'       => 'Remote Protocol',        // Added - 2009-02-08
    'Rename'               => 'שנה שם',
    'Replay'               => 'Replay',
    'ReplayAll'            => 'All Events',
    'ReplayGapless'        => 'Gapless Events',
    'ReplaySingle'         => 'Single Event',
    'ReportEventAudit'     => 'Audit Events Report',    // Added - 2018-08-30
    'Reset'                => 'אפס',
    'ResetEventCounts'     => 'Reset Event Counts',
    'Restart'              => 'אתחל',
    'Restarting'           => 'מאתחל',
    'RestrictedCameraIds'  => 'Restricted Camera Ids',
    'RestrictedMonitors'   => 'Restricted Monitors',
    'ReturnDelay'          => 'חזרה מהשהיה',
    'ReturnLocation'       => 'מיקום חזרה',
    'Rewind'               => 'Rewind',
    'RotateLeft'           => 'סובב שמאלה',
    'RotateRight'          => 'סובב ימינה',
    'RunLocalUpdate'       => 'Please run zmupdate.pl to update', // Added - 2011-05-25
    'RunMode'              => 'צורת ריצה',
    'RunState'             => 'מצב ריצה',
    'Running'              => 'מריץ',
    'Save'                 => 'שמור',
    'SaveAs'               => 'שמור בשם',
    'SaveFilter'           => 'שמור מסנן',
    'SaveJPEGs'            => 'Save JPEGs',             // Added - 2018-08-30
    'Scale'                => 'סקאלה',
    'Score'                => 'ניקוד',
    'Secs'                 => 'שניות',
    'Sectionlength'        => 'אורך קטע',
    'Select'               => 'בחר',
    'SelectFormat'         => 'Select Format',          // Added - 2011-06-17
    'SelectLog'            => 'Select Log',             // Added - 2011-06-17
    'SelectMonitors'       => 'בחר מוניטורים',
    'SelfIntersecting'     => 'Polygon edges must not intersect',
    'Set'                  => 'קבע',
    'SetNewBandwidth'      => 'Set New Bandwidth',
    'SetPreset'            => 'Set Preset',
    'Settings'             => 'הגדרות',
    'ShowFilterWindow'     => 'Show Filter Window',
    'ShowTimeline'         => 'Show Timeline',
    'SignalCheckColour'    => 'Signal Check Colour',
    'SignalCheckPoints'    => 'Signal Check Points',    // Added - 2018-08-30
    'Size'                 => 'גודל',
    'SkinDescription'      => 'Change the default skin for this computer', // Added - 2011-01-30
    'Sleep'                => 'שינה',
    'SortAsc'              => 'Asc',
    'SortBy'               => 'Sort by',
    'SortDesc'             => 'Desc',
    'Source'               => 'מקור',
    'SourceColours'        => 'Source Colours',         // Added - 2009-02-08
    'SourcePath'           => 'Source Path',            // Added - 2009-02-08
    'SourceType'           => 'סוג מקור',
    'Speed'                => 'מהירות',
    'SpeedHigh'            => 'מהירות גבוהה',
    'SpeedLow'             => 'מהירות נמוכה',
    'SpeedMedium'          => 'מצלמה בינונית',
    'SpeedTurbo'           => 'מהירות טורבו',
    'Start'                => 'התחל',
    'State'                => 'מצב',
    'Stats'                => 'מצבים',
    'Status'               => 'סטטוס',
    'StatusConnected'      => 'Capturing',              // Added - 2018-08-30
    'StatusNotRunning'     => 'Not Running',            // Added - 2018-08-30
    'StatusRunning'        => 'Not Capturing',          // Added - 2018-08-30
    'StatusUnknown'        => 'Unknown',                // Added - 2018-08-30
    'Step'                 => 'צעד',
    'StepBack'             => 'Step Back',
    'StepForward'          => 'Step Forward',
    'StepLarge'            => 'צעד גדול',
    'StepMedium'           => 'צעד בינוני',
    'StepNone'             => 'אל תצעד',
    'StepSmall'            => 'צעד קטן',
    'Stills'               => 'סטילס',
    'Stop'                 => 'עצור',
    'Stopped'              => 'נעצר',
    'StorageArea'          => 'Storage Area',           // Added - 2018-08-30
    'StorageScheme'        => 'Scheme',                 // Added - 2018-08-30
    'Stream'               => 'סטרים',
    'StreamReplayBuffer'   => 'Stream Replay Image Buffer',
    'Submit'               => 'Submit',
    'System'               => 'מערכת',
    'SystemLog'            => 'System Log',             // Added - 2011-06-16
    'TargetColorspace'     => 'Target colorspace',      // Added - 2015-04-18
    'Tele'                 => 'טל',
    'Thumbnail'            => 'Thumbnail',
    'Tilt'                 => 'Tilt',
    'Time'                 => 'זמן',
    'TimeDelta'            => 'שינוי בזמן',
    'TimeStamp'            => 'חותמת זמן',
    'Timeline'             => 'קו זמן',
    'TimelineTip1'          => 'Pass your mouse over the graph to view a snapshot image and event details.',              // Added 2013.08.15.
    'TimelineTip2'          => 'Click on the coloured sections of the graph, or the image, to view the event.',              // Added 2013.08.15.
    'TimelineTip3'          => 'Click on the background to zoom in to a smaller time period based around your click.',              // Added 2013.08.15.
    'TimelineTip4'          => 'Use the controls below to zoom out or navigate back and forward through the time range.',              // Added 2013.08.15.
    'Timestamp'            => 'חותמת זמן',
    'TimestampLabelFormat' => 'Timestamp Label Format',
    'TimestampLabelSize'   => 'Font Size',              // Added - 2018-08-30
    'TimestampLabelX'      => 'Timestamp Label X',
    'TimestampLabelY'      => 'Timestamp Label Y',
    'Today'                => 'היום',
    'Tools'                => 'כלים',
    'Total'                => 'Total',                  // Added - 2011-06-16
    'TotalBrScore'         => 'סך<br/>ניקוד',
    'TrackDelay'           => 'Track Delay',
    'TrackMotion'          => 'Track Motion',
    'Triggers'             => 'טריגרים',
    'TurboPanSpeed'        => 'Turbo Pan Speed',
    'TurboTiltSpeed'       => 'Turbo Tilt Speed',
    'Type'                 => 'סוג',
    'Unarchive'            => 'בלתי ארכיב',
    'Undefined'            => 'Undefined',              // Added - 2009-02-08
    'Units'                => 'יחידות',
    'Unknown'              => 'בלתי ידוע',
    'Update'               => 'עדכון',
    'UpdateAvailable'      => 'עדכון לזון-מינדר אפשרי.',
    'UpdateNotNecessary'   => 'עדכון אינו הכרחי.',
    'Updated'              => 'Updated',                // Added - 2011-06-16
    'Upload'               => 'Upload',                 // Added - 2011-08-23
    'UseFilter'            => 'שימוש במסנן',
    'UseFilterExprsPost'   => ' filter expressions', // This is used at the end of the phrase 'use N filter expressions'
    'UseFilterExprsPre'    => 'שימוש ', // This is used at the beginning of the phrase 'use N filter expressions'
    'UsedPlugins'	   => 'Used Plugins',
    'User'                 => 'משתמש',
    'Username'             => 'שם משתמש',
    'Users'                => 'משתמשים',
    'V4L'                  => 'V4L',                    // Added - 2015-04-18
    'V4LCapturesPerFrame'  => 'Captures Per Frame',     // Added - 2015-04-18
    'V4LMultiBuffer'       => 'Multi Buffering',        // Added - 2015-04-18
    'Value'                => 'ערך',
    'Version'              => 'גירסה',
    'VersionIgnore'        => 'התעלם מגירסה זו',
    'VersionRemindDay'     => 'הזכר לי בעוד יום אחד',
    'VersionRemindHour'    => 'הזכר לי בעוד שעה אחת',
    'VersionRemindNever'   => 'Don\'t remind about new versions',
    'VersionRemindWeek'    => 'Remind again in 1 week',
    'Video'                => 'וידאו',
    'VideoFormat'          => 'תבנית וידאו',
    'VideoGenFailed'       => 'Video Generation Failed!',
    'VideoGenFiles'        => 'Existing Video Files',
    'VideoGenNoFiles'      => 'No Video Files Found',
    'VideoGenParms'        => 'Video Generation Parameters',
    'VideoGenSucceeded'    => 'Video Generation Succeeded!',
    'VideoSize'            => 'גודל וידאו',
    'VideoWriter'          => 'Video Writer',           // Added - 2018-08-30
    'View'                 => 'הצג',
    'ViewAll'              => 'הצג הכל',
    'ViewEvent'            => 'הצג אירוע',
    'ViewPaged'            => 'View Paged',
    'Wake'                 => 'הער',
    'WarmupFrames'         => 'Warmup Frames',
    'Watch'                => 'צפה',
    'Web'                  => 'אינטרנט',
    'WebColour'            => 'צבע אינטרנט',
    'WebSiteUrl'           => 'Website URL',            // Added - 2018-08-30
    'Week'                 => 'שבוע',
    'White'                => 'לבן',
    'WhiteBalance'         => 'White Balance',
    'Wide'                 => 'רחב',
    'X'                    => 'X',
    'X10'                  => 'X10',
    'X10ActivationString'  => 'X10 Activation String',
    'X10InputAlarmString'  => 'X10 Input Alarm String',
    'X10OutputAlarmString' => 'X10 Output Alarm String',
    'Y'                    => 'Y',
    'Yes'                  => 'כן',
    'YouNoPerms'           => 'אין לך הרשאה להיכנס למקור זה.',
    'Zone'                 => 'אזור',
    'ZoneAlarmColour'      => 'Alarm Colour (Red/Green/Blue)',
    'ZoneArea'             => 'Zone Area',
    'ZoneExtendAlarmFrames' => 'Extend Alarm Frame Count',
    'ZoneFilterSize'       => 'Filter Width/Height (pixels)',
    'ZoneMinMaxAlarmArea'  => 'Min/Max Alarmed Area',
    'ZoneMinMaxBlobArea'   => 'Min/Max Blob Area',
    'ZoneMinMaxBlobs'      => 'Min/Max Blobs',
    'ZoneMinMaxFiltArea'   => 'Min/Max Filtered Area',
    'ZoneMinMaxPixelThres' => 'Min/Max Pixel Threshold (0-255)',
    'ZoneMinderLog'        => 'ZoneMinder Log',         // Added - 2011-06-17
    'ZoneOverloadFrames'   => 'Overload Frame Ignore Count',
    'Zones'                => 'אזורים',
    'Zoom'                 => 'זום',
    'ZoomIn'               => 'זום פנימה',
    'ZoomOut'              => 'זום החוצה',
);

// Complex replacements with formatting and/or placements, must be passed through sprintf
$CLANG = array(
    'CurrentLogin'         => 'Current login is \'%1$s\'',
    'EventCount'           => '%1$s %2$s', // For example '37 Events' (from Vlang below)
    'LastEvents'           => 'Last %1$s %2$s', // For example 'Last 37 Events' (from Vlang below)
    'LatestRelease'        => 'The latest release is v%1$s, you have v%2$s.',
    'MonitorCount'         => '%1$s %2$s', // For example '4 Monitors' (from Vlang below)
    'MonitorFunction'      => 'Monitor %1$s Function',
    'RunningRecentVer'     => 'You are running the most recent version of ZoneMinder, v%s.',
    'VersionMismatch'      => 'Version mismatch, system is version %1$s, database is %2$s.', // Added - 2011-05-25
);

// The next section allows you to describe a series of word ending and counts used to 
// generate the correctly conjugated forms of words depending on a count that is associated
// with that word.
// This intended to allow phrases such a '0 potatoes', '1 potato', '2 potatoes' etc to
// conjugate correctly with the associated count.
// In some languages such as English this is fairly simple and can be expressed by assigning
// a count with a singular or plural form of a word and then finding the nearest (lower) value.
// So '0' of something generally ends in 's', 1 of something is singular and has no extra
// ending and 2 or more is a plural and ends in 's' also. So to find the ending for '187' of
// something you would find the nearest lower count (2) and use that ending.
//
// So examples of this would be
// $zmVlangPotato = array( 0=>'Potatoes', 1=>'Potato', 2=>'Potatoes' );
// $zmVlangSheep = array( 0=>'Sheep' );
//
// where you can have as few or as many entries in the array as necessary
// If your language is similar in form to this then use the same format and choose the
// appropriate zmVlang function below.
// If however you have a language with a different format of plural endings then another
// approach is required . For instance in Russian the word endings change continuously
// depending on the last digit (or digits) of the numerator. In this case then zmVlang
// arrays could be written so that the array index just represents an arbitrary 'type'
// and the zmVlang function does the calculation about which version is appropriate.
//
// So an example in Russian might be (using English words, and made up endings as I
// don't know any Russian!!)
// $zmVlangPotato = array( 1=>'Potati', 2=>'Potaton', 3=>'Potaten' );
//
// and the zmVlang function decides that the first form is used for counts ending in
// 0, 5-9 or 11-19 and the second form when ending in 1 etc.
//

// Variable arrays expressing plurality, see the zmVlang description above
$VLANG = array(
    'Event'                => array( 0=>'Events', 1=>'Event', 2=>'Events' ),
    'Monitor'              => array( 0=>'Monitors', 1=>'Monitor', 2=>'Monitors' ),
);

// You will need to choose or write a function that can correlate the plurality string arrays
// with variable counts. This is used to conjugate the Vlang arrays above with a number passed
// in to generate the correct noun form.
//
// In languages such as English this is fairly simple 
// Note this still has to be used with printf etc to get the right formatting
function zmVlang( $langVarArray, $count )
{
    krsort( $langVarArray );
    foreach ( $langVarArray as $key=>$value )
    {
        if ( abs($count) >= $key )
        {
            return( $value );
        }
    }
    die( 'Error, unable to correlate variable language string' );
}

// This is an version that could be used in the Russian example above
// The rules are that the first word form is used if the count ends in
// 0, 5-9 or 11-19. The second form is used then the count ends in 1
// (not including 11 as above) and the third form is used when the 
// count ends in 2-4, again excluding any values ending in 12-14.
// 
// function zmVlang( $langVarArray, $count )
// {
//  $secondlastdigit = substr( $count, -2, 1 );
//  $lastdigit = substr( $count, -1, 1 );
//  // or
//  // $secondlastdigit = ($count/10)%10;
//  // $lastdigit = $count%10;
// 
//  // Get rid of the special cases first, the teens
//  if ( $secondlastdigit == 1 && $lastdigit != 0 )
//  {
//      return( $langVarArray[1] );
//  }
//  switch ( $lastdigit )
//  {
//      case 0 :
//      case 5 :
//      case 6 :
//      case 7 :
//      case 8 :
//      case 9 :
//      {
//          return( $langVarArray[1] );
//          break;
//      }
//      case 1 :
//      {
//          return( $langVarArray[2] );
//          break;
//      }
//      case 2 :
//      case 3 :
//      case 4 :
//      {
//          return( $langVarArray[3] );
//          break;
//      }
//  }
//  die( 'Error, unable to correlate variable language string' );
// }

// This is an example of how the function is used in the code which you can uncomment and 
// use to test your custom function.
//$monitors = array();
//$monitors[] = 1; // Choose any number
//echo sprintf( $zmClangMonitorCount, count($monitors), zmVlang( $zmVlangMonitor, count($monitors) ) );

// In this section you can override the default prompt and help texts for the options area
// These overrides are in the form show below where the array key represents the option name minus the initial ZM_
// So for example, to override the help text for ZM_LANG_DEFAULT do
$OLANG = array(
	'OPTIONS_FFMPEG' => array(
		'Help' => "Parameters in this field are passed on to FFmpeg. Multiple parameters can be separated by ,~~ ".
		          "Examples (do not enter quotes)~~~~".
		          "\"allowed_media_types=video\" Set datatype to request fromcam (audio, video, data)~~~~".
		          "\"reorder_queue_size=nnn\" Set number of packets to buffer for handling of reordered packets~~~~".
		          "\"loglevel=debug\" Set verbosity of FFmpeg (quiet, panic, fatal, error, warning, info, verbose, debug)"
	),
	'OPTIONS_LIBVLC' => array(
		'Help' => "Parameters in this field are passed on to libVLC. Multiple parameters can be separated by ,~~ ".
		          "Examples (do not enter quotes)~~~~".
		          "\"--rtp-client-port=nnn\" Set local port to use for rtp data~~~~". 
		          "\"--verbose=2\" Set verbosity of libVLC"
	),
	
//    'LANG_DEFAULT' => array(
//        'Prompt' => "This is a new prompt for this option",
//        'Help' => "This is some new help for this option which will be displayed in the popup window when the ? is clicked"
//    ),
);

?>
