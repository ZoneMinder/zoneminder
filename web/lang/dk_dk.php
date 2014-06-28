<?php
//
// ZoneMinder web Danish language file, $Date$, $Revision$
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
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//

// ZoneMinder Danish Translation by Tom Stage

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
// header( "Content-Type: text/html; charset=iso-8859-1" );
header( "Content-Type: text/html; charset=windows-1252" );

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
// setlocale( 'LC_ALL', 'en_GB' ); All locale settings pre-4.3.0
// setlocale( LC_ALL, 'en_GB' ); All locale settings 4.3.0 and after
// setlocale( LC_CTYPE, 'en_GB' ); Character class settings 4.3.0 and after
// setlocale( LC_TIME, 'en_GB' ); Date and time formatting 4.3.0 and after

// Simple String Replacements
$SLANG = array(
    '24BitColour'          => '24 bit farve',
    '32BitColour'          => '32 bit farve',          // Added - 2011-06-15
    '8BitGrey'             => '8 bit greyscale',
    'Action'               => 'Action',
    'Actual'               => 'Aktuel',
    'AddNewControl'        => 'Tilføj Ny kontrol',
    'AddNewMonitor'        => 'Tilføj Ny Monitor',
    'AddNewUser'           => 'Tilføj Ny Bruger',
    'AddNewZone'           => 'Tilføj Ny Zone',
    'Alarm'                => 'Alarm',
    'AlarmBrFrames'        => 'Alarm<br/>Billeder',
    'AlarmFrame'           => 'Alarm Billede',
    'AlarmFrameCount'      => 'Alarm Billede Tæller',
    'AlarmLimits'          => 'Alarm Begrændsing',
    'AlarmMaximumFPS'      => 'Alarm Maximum FPS',
    'AlarmPx'              => 'Alarm Px',
    'AlarmRGBUnset'        => 'You must set an alarm RGB colour',
    'Alert'                => 'Alarm',
    'All'                  => 'Alle',
    'Apply'                => 'Aktiver',
    'ApplyingStateChange'  => 'Aktivere State Ændring',
    'ArchArchived'         => 'Kun Arkiverede',
    'ArchUnarchived'       => 'Kun Ikke Arkiverede',
    'Archive'              => 'Arkiver',
    'Archived'             => 'Archived',
    'Area'                 => 'Area',
    'AreaUnits'            => 'Area (px/%)',
    'AttrAlarmFrames'      => 'Alarm Billeder',
    'AttrArchiveStatus'    => 'Arkiverings Status',
    'AttrAvgScore'         => 'Avg. Skore',
    'AttrCause'            => 'Årsag',
    'AttrDate'             => 'Dato',
    'AttrDateTime'         => 'Dato/Tid',
    'AttrDiskBlocks'       => 'Disk Blocks',
    'AttrDiskPercent'      => 'Disk Procent',
    'AttrDuration'         => 'Forløb',
    'AttrFrames'           => 'Billeder',
    'AttrId'               => 'Id',
    'AttrMaxScore'         => 'Max. Skore',
    'AttrMonitorId'        => 'Monitor Id',
    'AttrMonitorName'      => 'Monitor Navn',
    'AttrName'             => 'Navn',
    'AttrNotes'            => 'Notes',
    'AttrSystemLoad'       => 'System Load',
    'AttrTime'             => 'Tid',
    'AttrTotalScore'       => 'Total Skore',
    'AttrWeekday'          => 'Uge Dag',
    'Auto'                 => 'Auto',
    'AutoStopTimeout'      => 'Auto Stop Timeout',
    'Available'            => 'Available',              // Added - 2009-03-31
    'AvgBrScore'           => 'Avg.<br/>Skore',
    'Background'           => 'Background',
    'BackgroundFilter'     => 'Run filter in background',
    'BadAlarmFrameCount'   => 'Alarm frame count must be an integer of one or more',
    'BadAlarmMaxFPS'       => 'Alarm Maximum FPS must be a positive integer or floating point value',
    'BadChannel'           => 'Channel must be set to an integer of zero or more',
    'BadColours'           => 'Target colour must be set to a valid value', // Added - 2011-06-15
    'BadDevice'            => 'Device must be set to a valid value',
    'BadFPSReportInterval' => 'FPS report interval buffer count must be an integer of 0 or more',
    'BadFormat'            => 'Format must be set to an integer of zero or more',
    'BadFrameSkip'         => 'Frame skip count must be an integer of zero or more',
    'BadMotionFrameSkip'   => 'Motion Frame skip count must be an integer of zero or more',
    'BadHeight'            => 'Height must be set to a valid value',
    'BadHost'              => 'Host must be set to a valid ip address or hostname, do not include http://',
    'BadImageBufferCount'  => 'Image buffer size must be an integer of 10 or more',
    'BadLabelX'            => 'Label X co-ordinate must be set to an integer of zero or more',
    'BadLabelY'            => 'Label Y co-ordinate must be set to an integer of zero or more',
    'BadMaxFPS'            => 'Maximum FPS must be a positive integer or floating point value',
    'BadNameChars'         => 'Navne må kun indeholde alphanumeric karaktere plus hyphen og underscore',
    'BadPalette'           => 'Palette must be set to a valid value', // Added - 2009-03-31
    'BadPath'              => 'Path must be set to a valid value',
    'BadPort'              => 'Port must be set to a valid number',
    'BadPostEventCount'    => 'Post event image count must be an integer of zero or more',
    'BadPreEventCount'     => 'Pre event image count must be at least zero, and less than image buffer size',
    'BadRefBlendPerc'      => 'Reference blend percentage must be a positive integer',
    'BadSectionLength'     => 'Section length must be an integer of 30 or more',
    'BadSignalCheckColour' => 'Signal check colour must be a valid RGB colour string',
    'BadStreamReplayBuffer'=> 'Stream replay buffer must be an integer of zero or more',
    'BadWarmupCount'       => 'Warmup frames must be an integer of zero or more',
    'BadWebColour'         => 'Web colour must be a valid web colour string',
    'BadWidth'             => 'Width must be set to a valid value',
    'Bandwidth'            => 'Båndbrede',
    'BandwidthHead'        => 'Bandwidth',	// This is the end of the bandwidth status on the top of the console, different in many language due to phrasing
    'BlobPx'               => 'Blob Px',
    'BlobSizes'            => 'Blob Størelse',
    'Blobs'                => 'Blobs',
    'Brightness'           => 'Brightness',
    'Buffers'              => 'Buffere',
    'CanAutoFocus'         => 'Kan Auto Focus',
    'CanAutoGain'          => 'Kan Auto Gain',
    'CanAutoIris'          => 'Kan Auto Iris',
    'CanAutoWhite'         => 'Kan Auto White Bal.',
    'CanAutoZoom'          => 'Kan Auto Zoom',
    'CanFocus'             => 'Kan Focus',
    'CanFocusAbs'          => 'Kan Focus Absolut',
    'CanFocusCon'          => 'Kan Focus Kontinuerligt',
    'CanFocusRel'          => 'Kan Focus Relativt',
    'CanGain'              => 'Kan Gain ',
    'CanGainAbs'           => 'Kan Gain Absolut',
    'CanGainCon'           => 'Kan Gain Kontinuerligt',
    'CanGainRel'           => 'Kan Gain Relativt',
    'CanIris'              => 'Kan Iris',
    'CanIrisAbs'           => 'Kan Iris Absolut',
    'CanIrisCon'           => 'Kan Iris Kontinuerligt',
    'CanIrisRel'           => 'Kan Iris Relativt',
    'CanMove'              => 'Kan Bevæge',
    'CanMoveAbs'           => 'Kan Bevæge Absolut',
    'CanMoveCon'           => 'Kan Bevæge Kontinuerligt',
    'CanMoveDiag'          => 'Kan Bevæge Diagonalt',
    'CanMoveMap'           => 'Kan Bevæge Mapped',
    'CanMoveRel'           => 'Kan Bevæge Relativt',
    'CanPan'               => 'Kan Pan' ,
    'CanReset'             => 'Kan Reset',
    'CanSetPresets'        => 'Kan Set Presets',
    'CanSleep'             => 'Kan Sove',
    'CanTilt'              => 'Kan Tilt',
    'CanWake'              => 'Kan Vågne',
    'CanWhite'             => 'Kan White Balance',
    'CanWhiteAbs'          => 'Kan White Bal. Absolut',
    'CanWhiteBal'          => 'Kan White Bal.',
    'CanWhiteCon'          => 'Kan White Bal. Kontinuerligt',
    'CanWhiteRel'          => 'Kan White Bal. Relativt',
    'CanZoom'              => 'Kan Zoom',
    'CanZoomAbs'           => 'Kan Zoom Absolut',
    'CanZoomCon'           => 'Kan Zoom Kontinuerligt',
    'CanZoomRel'           => 'Kan Zoom Relativt',
    'Cancel'               => 'Fortryd',
    'CancelForcedAlarm'    => 'Fortryd Forced Alarm',
    'CaptureHeight'        => 'Capture Height',
    'CaptureMethod'        => 'Capture Method',         // Added - 2009-02-08
    'CapturePalette'       => 'Capture Palette',
    'CaptureWidth'         => 'Capture Width',
    'Cause'                => 'Årsag',
    'CheckMethod'          => 'Alarm Check Methode',
    'ChooseDetectedCamera' => 'Choose Detected Camera', // Added - 2009-03-31
    'ChooseFilter'         => 'Vælg Filter',
    'ChooseLogFormat'      => 'Choose a log format',    // Added - 2011-06-17
    'ChooseLogSelection'   => 'Choose a log selection', // Added - 2011-06-17
    'ChoosePreset'         => 'Choose Preset',
    'Clear'                => 'Clear',                  // Added - 2011-06-16
    'Close'                => 'Luk',
    'Colour'               => 'Farve',
    'Command'              => 'Kommando',
    'Component'            => 'Component',              // Added - 2011-06-16
    'Config'               => 'konfig',
    'ConfiguredFor'        => 'Konfigureret for',
    'ConfirmDeleteEvents'  => 'Are you sure you wish to delete the selected events?',
    'ConfirmPassword'      => 'Verifiser Password',
    'ConjAnd'              => 'og',
    'ConjOr'               => 'eller',
    'Console'              => 'Konsol',
    'ContactAdmin'         => 'Kontakt Din adminstrator for detalier.',
    'Continue'             => 'Fortsæt',
    'Contrast'             => 'Kontrast',
    'Control'              => 'Kontrol',
    'ControlAddress'       => 'Kontrol Addresse',
    'ControlCap'           => 'Kontrol Capability',
    'ControlCaps'          => 'Kontrol Capabilities',
    'ControlDevice'        => 'Kontrol Enhed',
    'ControlType'          => 'Kontrol Type',
    'Controllable'         => 'Controllable',
    'Cycle'                => 'Cycle',
    'CycleWatch'           => 'Cycle Watch',
    'DateTime'             => 'Date/Time',              // Added - 2011-06-16
    'Day'                  => 'Dag',
    'Debug'                => 'Debug',
    'DefaultRate'          => 'Default Rate',
    'DefaultScale'         => 'Default Scale',
    'DefaultView'          => 'Default View',
    'Delete'               => 'Slet',
    'DeleteAndNext'        => 'Slet &amp; Næste',
    'DeleteAndPrev'        => 'Slet &amp; Forrige',
    'DeleteSavedFilter'    => 'Slet Gemte filter',
    'Description'          => 'Beskrivelse',
    'DetectedCameras'      => 'Detected Cameras',       // Added - 2009-03-31
    'Device'               => 'Device',                 // Added - 2009-02-08
    'DeviceChannel'        => 'Enheds Kanal',
    'DeviceFormat'         => 'Enheds Format',
    'DeviceNumber'         => 'Enheds Nummer',
    'DevicePath'           => 'Device Path',
    'Devices'              => 'Devices',
    'Dimensions'           => 'Dimentioner',
    'DisableAlarms'        => 'Disable Alarms',
    'Disk'                 => 'Disk',
    'Display'              => 'Display',                // Added - 2011-01-30
    'Displaying'           => 'Displaying',             // Added - 2011-06-16
    'Donate'               => 'Please Donate',
    'DonateAlready'        => 'No, I\'ve already donated',
    'DonateEnticement'     => 'You\'ve been running ZoneMinder for a while now and hopefully are finding it a useful addition to your home or workplace security. Although ZoneMinder is, and will remain, free and open source, it costs money to develop and support. If you would like to help support future development and new features then please consider donating. Donating is, of course, optional but very much appreciated and you can donate as much or as little as you like.<br><br>If you would like to donate please select the option below or go to http://www.zoneminder.com/donate.html in your browser.<br><br>Thank you for using ZoneMinder and don\'t forget to visit the forums on ZoneMinder.com for support or suggestions about how to make your ZoneMinder experience even better.',
    'DonateRemindDay'      => 'Not yet, remind again in 1 day',
    'DonateRemindHour'     => 'Not yet, remind again in 1 hour',
    'DonateRemindMonth'    => 'Not yet, remind again in 1 month',
    'DonateRemindNever'    => 'No, I don\'t want to donate, never remind',
    'DonateRemindWeek'     => 'Not yet, remind again in 1 week',
    'DonateYes'            => 'Yes, I\'d like to donate now',
    'DoNativeMotionDetection'=> 'Do Native Motion Detection',
    'Download'             => 'Download',
    'DuplicateMonitorName' => 'Duplicate Monitor Name', // Added - 2009-03-31
    'Duration'             => 'Forløb',
    'Edit'                 => 'Rediger',
    'Email'                => 'Email',
    'EnableAlarms'         => 'Enable Alarms',
    'Enabled'              => 'Aktiv',
    'EnterNewFilterName'   => 'Skriv Nyt filter navn',
    'Error'                => 'Fejl',
    'ErrorBrackets'        => 'Fejl, check at du har lige antal af Åbnings og Lukkende brackets',
    'ErrorValidValue'      => 'Fejl, check at alle terms har en valid værdig',
    'Etc'                  => 'etc',
    'Event'                => 'Event',
    'EventFilter'          => 'Event Filter',
    'EventId'              => 'Event Id',
    'EventName'            => 'Event Navn',
    'EventPrefix'          => 'Event Prefix',
    'Events'               => 'Events',
    'Exclude'              => 'Exclude',
    'Execute'              => 'Execute',
    'Export'               => 'Export',
    'ExportDetails'        => 'Export Event Details',
    'ExportFailed'         => 'Export Failed',
    'ExportFormat'         => 'Export File Format',
    'ExportFormatTar'      => 'Tar',
    'ExportFormatZip'      => 'Zip',
    'ExportFrames'         => 'Export Frame Details',
    'ExportImageFiles'     => 'Export Image Files',
    'ExportLog'            => 'Export Log',             // Added - 2011-06-17
    'ExportMiscFiles'      => 'Export Other Files (if present)',
    'ExportOptions'        => 'Export Options',
    'ExportSucceeded'      => 'Export Succeeded',       // Added - 2009-02-08
    'ExportVideoFiles'     => 'Export Video Files (if present)',
    'Exporting'            => 'Exporting',
    'FPS'                  => 'fps',
    'FPSReportInterval'    => 'FPS Raport Interval',
    'FTP'                  => 'FTP',
    'Far'                  => 'Far',
    'FastForward'          => 'Fast Forward',
    'Feed'                 => 'Feed',
    'Ffmpeg'               => 'Ffmpeg',                 // Added - 2009-02-08
    'File'                 => 'File',
    'FilterArchiveEvents'  => 'Arkiver alle matchende',
    'FilterDeleteEvents'   => 'Slet alle matchende',
    'FilterEmailEvents'    => 'Email detalier af alle matchende',
    'FilterExecuteEvents'  => 'Kør kommando på alle matchende',
    'FilterMessageEvents'  => 'Send detalier af alle matchende',
    'FilterPx'             => 'Filter Px',
    'FilterUnset'          => 'You must specify a filter width and height',
    'FilterUploadEvents'   => 'Upload alle matchende',
    'FilterVideoEvents'    => 'Create video for all matches',
    'Filters'              => 'Filters',
    'First'                => 'Første',
    'FlippedHori'          => 'Flipped Horizontally',
    'FlippedVert'          => 'Flipped Vertically',
    'FnNone'                => 'None',            // Added 2013.08.16.
    'FnMonitor'             => 'Monitor',            // Added 2013.08.16.
    'FnModect'              => 'Modect',            // Added 2013.08.16.
    'FnRecord'              => 'Record',            // Added 2013.08.16.
    'FnMocord'              => 'Mocord',            // Added 2013.08.16.
    'FnNodect'              => 'Nodect',            // Added 2013.08.16.
    'Focus'                => 'Fokus',
    'ForceAlarm'           => 'Tving Alarm',
    'Format'               => 'Format',
    'Frame'                => 'Billede',
    'FrameId'              => 'Billede Id',
    'FrameRate'            => 'Billede Rate',
    'FrameSkip'            => 'Billede Skip',
    'MotionFrameSkip'      => 'Motion Frame Skip',
    'Frames'               => 'Billede',
    'Func'                 => 'Func',
    'Function'             => 'Funktion',
    'Gain'                 => 'Gain',
    'General'              => 'General',
    'GenerateVideo'        => 'Generer Video',
    'GeneratingVideo'      => 'Generere Video',
    'GoToZoneMinder'       => 'Gå til ZoneMinder.com',
    'Grey'                 => 'Grå',
    'Group'                => 'Group',
    'Groups'               => 'Grupper',
    'HasFocusSpeed'        => 'Har Fokus Hastighed',
    'HasGainSpeed'         => 'Har Gain Hastighed',
    'HasHomePreset'        => 'Har Hjem Preset',
    'HasIrisSpeed'         => 'Har Iris Hastighed',
    'HasPanSpeed'          => 'Har Pan Hastighed',
    'HasPresets'           => 'Har Presets',
    'HasTiltSpeed'         => 'Har Tilt Hastighed',
    'HasTurboPan'          => 'Har Turbo Pan',
    'HasTurboTilt'         => 'Har Turbo Tilt',
    'HasWhiteSpeed'        => 'Har White Bal. Hastighed',
    'HasZoomSpeed'         => 'Har Zoom Hastighed',
    'High'                 => 'Høj',
    'HighBW'               => 'Høj&nbsp;B/B',
    'Home'                 => 'Hjem',
    'Hour'                 => 'Time',
    'Hue'                  => 'Hue',
    'Id'                   => 'Id',
    'Idle'                 => 'Idle',
    'Ignore'               => 'Ignorer',
    'Image'                => 'Billede',
    'ImageBufferSize'      => 'Billede Buffer Størelse (Billeder)',
    'Images'               => 'Images',
    'In'                   => 'Ind',
    'Include'              => 'Inkluder',
    'Inverted'             => 'Inverteret',
    'Iris'                 => 'Iris',
    'KeyString'            => 'Key String',
    'Label'                => 'Label',
    'Language'             => 'Sprog',
    'Last'                 => 'Sidste',
    'Layout'               => 'Layout',                 // Added - 2009-02-08
    'Level'                => 'Level',                  // Added - 2011-06-16
    'Libvlc'               => 'Libvlc',
    'LimitResultsPost'     => 'results only;', // This is used at the end of the phrase 'Limit to first N results only'
    'LimitResultsPre'      => 'Limit to first', // This is used at the beginning of the phrase 'Limit to first N results only'
    'Line'                 => 'Line',                   // Added - 2011-06-16
    'LinkedMonitors'       => 'Linked Monitors',
    'List'                 => 'List',
    'Load'                 => 'Load',
    'Local'                => 'Lokal',
    'Log'                  => 'Log',                    // Added - 2011-06-16
    'LoggedInAs'           => 'Logget Ind Som',
    'Logging'              => 'Logging',                // Added - 2011-06-16
    'LoggingIn'            => 'Logger Ind',
    'Login'                => 'Logind',
    'Logout'               => 'Logud',
    'Logs'                 => 'Logs',                   // Added - 2011-06-17
    'Low'                  => 'Lav',
    'LowBW'                => 'Lav&nbsp;B/B',
    'Main'                 => 'Main',
    'Man'                  => 'Man',
    'Manual'               => 'Manual',
    'Mark'                 => 'Marker',
    'Max'                  => 'Max',
    'MaxBandwidth'         => 'Max Bandwidth',
    'MaxBrScore'           => 'Max.<br/>Skore',
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
    'MaximumFPS'           => 'Maximale FPS',
    'Medium'               => 'Medium',
    'MediumBW'             => 'Medium&nbsp;B/B',
    'Message'              => 'Message',                // Added - 2011-06-16
    'MinAlarmAreaLtMax'    => 'Minimum alarm area should be less than maximum',
    'MinAlarmAreaUnset'    => 'You must specify the minimum alarm pixel count',
    'MinBlobAreaLtMax'     => 'Minimum blob område bør være mindre end maximum',
    'MinBlobAreaUnset'     => 'You must specify the minimum blob pixel count',
    'MinBlobLtMinFilter'   => 'Minimum blob area should be less than or equal to minimum filter area',
    'MinBlobsLtMax'        => 'Minimum blobs bør være mindre end maximum',
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
    'MinPixelThresLtMax'   => 'Minimum pixel threshold bør være mindre end maximum',
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
    'Monitor'              => 'Monitor',
    'MonitorIds'           => 'Monitor&nbsp;Ids',
    'MonitorPreset'        => 'Monitor Preset',
    'MonitorPresetIntro'   => 'Select an appropriate preset from the list below.<br><br>Please note that this may overwrite any values you already have configured for this monitor.<br><br>',
    'MonitorProbe'         => 'Monitor Probe',          // Added - 2009-03-31
    'MonitorProbeIntro'    => 'The list below shows detected analog and network cameras and whether they are already being used or available for selection.<br/><br/>Select the desired entry from the list below.<br/><br/>Please note that not all cameras may be detected and that choosing a camera here may overwrite any values you already have configured for the current monitor.<br/><br/>', // Added - 2009-03-31
    'Monitors'             => 'Monitore',
    'Montage'              => 'Montage',
    'Month'                => 'Måned',
    'More'                 => 'More',                   // Added - 2011-06-16
    'Move'                 => 'Flyt',
    'MtgDefault'            => 'Default',              // Added 2013.08.15.
    'Mtg2widgrd'            => '2-wide grid',              // Added 2013.08.15.
    'Mtg3widgrd'            => '3-wide grid',              // Added 2013.08.15.
    'Mtg4widgrd'            => '4-wide grid',              // Added 2013.08.15.
    'Mtg3widgrx'            => '3-wide grid, scaled, enlarge on alarm',              // Added 2013.08.15.
    'MustBeGe'             => 'skal være støre end eller ligmed',
    'MustBeLe'             => 'Skal være mindre end eller ligmed',
    'MustConfirmPassword'  => 'Du skal konfimere password',
    'MustSupplyPassword'   => 'Du skal angive et password',
    'MustSupplyUsername'   => 'Du skal opgive et username',
    'Name'                 => 'Navn',
    'Near'                 => 'Near',
    'Network'              => 'Netværk',
    'New'                  => 'Ny',
    'NewGroup'             => 'Ny Gruppe',
    'NewLabel'             => 'New Label',
    'NewPassword'          => 'Nyt Password',
    'NewState'             => 'Ny State',
    'NewUser'              => 'Ny User',
    'Next'                 => 'Næste',
    'No'                   => 'Nej',
    'NoDetectedCameras'    => 'No Detected Cameras',    // Added - 2009-03-31
    'NoFramesRecorded'     => 'Der er ingen billeder optaget for denne event',
    'NoGroup'              => 'No Group',
    'NoSavedFilters'       => 'NoSavedFilters',
    'NoStatisticsRecorded' => 'Der er ingen statestikker optaget for denne event/frame',
    'None'                 => 'Ingen',
    'NoneAvailable'        => 'Ingen Tilstede',
    'Normal'               => 'Normal',
    'Notes'                => 'Notes',
    'NumPresets'           => 'Num Presets',
    'Off'                  => 'Off',
    'On'                   => 'On',
    'OpEq'                 => 'ligmed',
    'OpGt'                 => 'støre end',
    'OpGtEq'               => 'støre end eller ligmed',
    'OpIn'                 => 'i sættet',
    'OpLt'                 => 'mindre end',
    'OpLtEq'               => 'mindre end eller ligmed',
    'OpMatches'            => 'matches',
    'OpNe'                 => 'ikke ligmed',
    'OpNotIn'              => 'ikke i sættet',
    'OpNotMatches'         => 'does not match',
    'Open'                 => 'Åben',
    'OptionHelp'           => 'OptionHelp',
    'OptionRestartWarning' => 'Disse ændringer træder ikke i fuld effect\nmens systemt køre. Når du har\nafsluttet ændringer bedes du\ngenstarte ZoneMinder.',
    'Options'              => 'Indstillinger',
    'OrEnterNewName'       => 'eller skriv nyt navn',
    'Order'                => 'Order',
    'Orientation'          => 'Orientation',
    'Out'                  => 'Ud',
    'OverwriteExisting'    => 'Overskriv Eksisterende',
    'Paged'                => 'Paged',
    'Pan'                  => 'Pan',
    'PanLeft'              => 'Pan Left',
    'PanRight'             => 'Pan Right',
    'PanTilt'              => 'Pan/Tilt',
    'Parameter'            => 'Parameter',
    'Password'             => 'Password',
    'PasswordsDifferent'   => 'Det nye og konfimerede passwords er forskellige',
    'Paths'                => 'Stiger',
    'Pause'                => 'Pause',
    'Phone'                => 'Telefon',
    'PhoneBW'              => 'Telefon&nbsp;B/B',
    'Pid'                  => 'PID',                    // Added - 2011-06-16
    'PixelDiff'            => 'Pixel Diff',
    'Pixels'               => 'pixels',
    'Play'                 => 'Play',
    'PlayAll'              => 'Afspil Alle',
    'PleaseWait'           => 'Vent venligst',
    'Plugins'              => 'Plugins',
    'Point'                => 'Point',
    'PostEventImageBuffer' => 'Efter Event Billed Buffer',
    'PreEventImageBuffer'  => 'Før Event Billed Buffer',
    'PreserveAspect'       => 'Preserve Aspect Ratio',
    'Preset'               => 'Preset',
    'Presets'              => 'Presets',
    'Prev'                 => 'Prev',
    'Probe'                => 'Probe',                  // Added - 2009-03-31
    'Protocol'             => 'Protocol',
    'Rate'                 => 'Rate',
    'Real'                 => 'Real',
    'Record'               => 'Optag',
    'RefImageBlendPct'     => 'Reference Billede Blend %ge',
    'Refresh'              => 'Opdater',
    'Remote'               => 'Remote',
    'RemoteHostName'       => 'Remote Host Navn',
    'RemoteHostPath'       => 'Remote Host Stig',
    'RemoteHostPort'       => 'Remote Host Port',
    'RemoteHostSubPath'    => 'Remote Host SubPath',    // Added - 2009-02-08
    'RemoteImageColours'   => 'Remote Image Farver',
    'RemoteMethod'         => 'Remote Method',          // Added - 2009-02-08
    'RemoteProtocol'       => 'Remote Protocol',        // Added - 2009-02-08
    'Rename'               => 'Omdøb',
    'Replay'               => 'Spil Igen',
    'ReplayAll'            => 'All Events',
    'ReplayGapless'        => 'Gapless Events',
    'ReplaySingle'         => 'Single Event',
    'Reset'                => 'Nulstil',
    'ResetEventCounts'     => 'Reset Event Counts',
    'Restart'              => 'Genstart',
    'Restarting'           => 'Genstarter',
    'RestrictedCameraIds'  => 'Begranset Kamera Ids',
    'RestrictedMonitors'   => 'Restricted Monitors',
    'ReturnDelay'          => 'Return Delay',
    'ReturnLocation'       => 'Return Location',
    'Rewind'               => 'Rewind',
    'RotateLeft'           => 'Rotate Left',
    'RotateRight'          => 'Rotate Right',
    'RunLocalUpdate'       => 'Please run zmupdate.pl to update', // Added - 2011-05-25
    'RunMode'              => 'Kørsels Mode',
    'RunState'             => 'Run State',
    'Running'              => 'Køre',
    'Save'                 => 'Gem',
    'SaveAs'               => 'Gem Som',
    'SaveFilter'           => 'Gem Filter',
    'Scale'                => 'Scale',
    'Score'                => 'Skore',
    'Secs'                 => 'Sekunder',
    'Sectionlength'        => 'Sektion længde',
    'Select'               => 'Vælg',
    'SelectFormat'         => 'Select Format',          // Added - 2011-06-17
    'SelectLog'            => 'Select Log',             // Added - 2011-06-17
    'SelectMonitors'       => 'Select Monitors',
    'SelfIntersecting'     => 'Polygon edges must not intersect',
    'Set'                  => 'Sæt',
    'SetNewBandwidth'      => 'Sæt Ny Båndbrede',
    'SetPreset'            => 'Sæt Preset',
    'Settings'             => 'Indstillinger',
    'ShowFilterWindow'     => 'VisFilterVindue',
    'ShowTimeline'         => 'Show Timeline',
    'SignalCheckColour'    => 'Signal Check Colour',
    'Size'                 => 'Size',
    'SkinDescription'      => 'Change the default skin for this computer', // Added - 2011-01-30
    'Sleep'                => 'Sov',
    'SortAsc'              => 'Asc',
    'SortBy'               => 'Sorter efter',
    'SortDesc'             => 'Desc',
    'Source'               => 'Enhed',
    'SourceColours'        => 'Source Colours',         // Added - 2009-02-08
    'SourcePath'           => 'Source Path',            // Added - 2009-02-08
    'SourceType'           => 'Enheds Type',
    'Speed'                => 'Hastighed',
    'SpeedHigh'            => 'Høj Hastighed',
    'SpeedLow'             => 'Lav Hastighed',
    'SpeedMedium'          => 'Medium Hastighed',
    'SpeedTurbo'           => 'Turbo Hastighed',
    'Start'                => 'Start',
    'State'                => 'State',
    'Stats'                => 'Stats',
    'Status'               => 'Status',
    'Step'                 => 'Step',
    'StepBack'             => 'Step Back',
    'StepForward'          => 'Step Forward',
    'StepLarge'            => 'Large Step',
    'StepMedium'           => 'Medium Step',
    'StepNone'             => 'No Step',
    'StepSmall'            => 'Small Step',
    'Stills'               => 'Stills',
    'Stop'                 => 'Stop',
    'Stopped'              => 'Stoppet',
    'Stream'               => 'Stream',
    'StreamReplayBuffer'   => 'Stream Replay Image Buffer',
    'Submit'               => 'Submit',
    'System'               => 'System',
    'SystemLog'            => 'System Log',             // Added - 2011-06-16
    'Tele'                 => 'Tele',
    'Thumbnail'            => 'Thumbnail',
    'Tilt'                 => 'Tilt',
    'Time'                 => 'Tid',
    'TimeDelta'            => 'Time Delta',
    'TimeStamp'            => 'Tids Stempel',
    'Timeline'             => 'Timeline',
    'TimelineTip1'         => 'Pass your mouse over the graph to view a snapshot image and event details.',              // Added 2013.08.15.
    'TimelineTip2'         => 'Click on the coloured sections of the graph, or the image, to view the event.',              // Added 2013.08.15.
    'TimelineTip3'         => 'Click on the background to zoom in to a smaller time period based around your click.',              // Added 2013.08.15.
    'TimelineTip4'         => 'Use the controls below to zoom out or navigate back and forward through the time range.',              // Added 2013.08.15.
    'Timestamp'            => 'Tidsstempel',
    'TimestampLabelFormat' => 'Tidsstempel Mærkning´s Format',
    'TimestampLabelX'      => 'Tidsstempel Mærkning X',
    'TimestampLabelY'      => 'Tidsstempel Mærkning Y',
    'Today'                => 'Idag',
    'Tools'                => 'Tools',
    'Total'                => 'Total',                  // Added - 2011-06-16
    'TotalBrScore'         => 'Total<br/>Skore',
    'TrackDelay'           => 'Track Delay',
    'TrackMotion'          => 'Track Motion',
    'Triggers'             => 'Triggers',
    'TurboPanSpeed'        => 'Turbo Pan Hastighed',
    'TurboTiltSpeed'       => 'Turbo Tilt Hastighed',
    'Type'                 => 'Type',
    'Unarchive'            => 'Unarchive',
    'Undefined'            => 'Undefined',              // Added - 2009-02-08
    'Units'                => 'Units',
    'Unknown'              => 'Unknown',
    'Update'               => 'Update',
    'UpdateAvailable'      => 'En updatering til ZoneMinder er tilstede.',
    'UpdateNotNecessary'   => 'Ingen updatering er nødvendig.',
    'Updated'              => 'Updated',                // Added - 2011-06-16
    'Upload'               => 'Upload',                 // Added - 2011-08-23
    'UsedPlugins'	   => 'Used Plugins',
    'UseFilter'            => 'Brug Filter',
    'UseFilterExprsPost'   => '&nbsp;filter&nbsp;expressions', // This is used at the end of the phrase 'use N filter expressions'
    'UseFilterExprsPre'    => 'Brug&nbsp;', // This is used at the beginning of the phrase 'use N filter expressions'
    'User'                 => 'Bruger',
    'Username'             => 'Bruger Navn',
    'Users'                => 'Brugere',
    'Value'                => 'Værdig',
    'Version'              => 'Version',
    'VersionIgnore'        => 'Ignorer denne version',
    'VersionRemindDay'     => 'Påmind igen om 1 dag',
    'VersionRemindHour'    => 'Påmind igen om 1 time',
    'VersionRemindNever'   => 'Mind ikke om nye versioner',
    'VersionRemindWeek'    => 'Påmind igen om 1 uge',
    'Video'                => 'Video',
    'VideoFormat'          => 'Video Format',
    'VideoGenFailed'       => 'Video Generering Fejlede!',
    'VideoGenFiles'        => 'Existing Video Files',
    'VideoGenNoFiles'      => 'No Video Files Found',
    'VideoGenParms'        => 'Video Generaring Parametre',
    'VideoGenSucceeded'    => 'Video Generation Succeeded!',
    'VideoSize'            => 'Video Størelse',
    'View'                 => 'Vis',
    'ViewAll'              => 'Vis Alle',
    'ViewEvent'            => 'View Event',
    'ViewPaged'            => 'View Paged',
    'Wake'                 => 'Wake',
    'WarmupFrames'         => 'Varmop Billeder',
    'Watch'                => 'Se',
    'Web'                  => 'Web',
    'WebColour'            => 'Web Colour',
    'Week'                 => 'Uge',
    'White'                => 'White',
    'WhiteBalance'         => 'White Balance',
    'Wide'                 => 'Wide',
    'X'                    => 'X',
    'X10'                  => 'X10',
    'X10ActivationString'  => 'X10 Activerings Streng',
    'X10InputAlarmString'  => 'X10 Input Alarm Streng',
    'X10OutputAlarmString' => 'X10 Output Alarm Streng',
    'Y'                    => 'Y',
    'Yes'                  => 'Ja',
    'YouNoPerms'           => 'Du har ikke adgang til denne resourse.',
    'Zone'                 => 'Zone',
    'ZoneAlarmColour'      => 'Alarm Farve (Red/Green/Blue)',
    'ZoneArea'             => 'Zone Area',
    'ZoneFilterSize'       => 'Filter Width/Height (pixels)',
    'ZoneMinMaxAlarmArea'  => 'Min/Max Alarmed Area',
    'ZoneMinMaxBlobArea'   => 'Min/Max Blob Area',
    'ZoneMinMaxBlobs'      => 'Min/Max Blobs',
    'ZoneMinMaxFiltArea'   => 'Min/Max Filtered Area',
    'ZoneMinMaxPixelThres' => 'Min/Max Pixel Threshold (0-255)',
    'ZoneMinderLog'        => 'ZoneMinder Log',         // Added - 2011-06-17
    'ZoneOverloadFrames'   => 'Overload Frame Ignore Count',
    'ZoneExtendAlarmFrames' => 'Extend Alarm Frame Count',
    'Zones'                => 'Zoner',
    'Zoom'                 => 'Zoom',
    'ZoomIn'               => 'Zoom In',
    'ZoomOut'              => 'Zoom Out',
);

// Complex replacements with formatting and/or placements, must be passed through sprintf
$CLANG = array(
    'CurrentLogin'         => 'Nuværende login er \'%1$s\'',
    'EventCount'           => '%1$s %2$s', // For example '37 Events' (from Vlang below)
    'LastEvents'           => 'Sidste %1$s %2$s', // For example 'Last 37 Events' (from Vlang below)
    'LatestRelease'        => 'Den Seneste version er v%1$s, du har v%2$s.',
    'MonitorCount'         => '%1$s %2$s', // For example '4 Monitors' (from Vlang below)
    'MonitorFunction'      => 'Monitor %1$s Function',
    'RunningRecentVer'     => 'Du Køre med seneste version af ZoneMinder, v%s.',
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
// Note this still has to be used with printf etc to get the right formating
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
		'Help' => "Parameters in this field are passwd on to FFmpeg. Multiple parameters can be separated by ,~~ ".
		          "Examples (do not enter quotes)~~~~".
		          "\"allowed_media_types=video\" Set datatype to request fromcam (audio, video, data)~~~~".
		          "\"reorder_queue_size=nnn\" Set number of packets to buffer for handling of reordered packets~~~~".
		          "\"loglevel=debug\" Set verbosiy of FFmpeg (quiet, panic, fatal, error, warning, info, verbose, debug)"
	),
	'OPTIONS_LIBVLC' => array(
		'Help' => "Parameters in this field are passwd on to libVLC. Multiple parameters can be separated by ,~~ ".
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
