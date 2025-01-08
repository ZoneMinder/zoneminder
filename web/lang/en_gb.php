<?php
//
// ZoneMinder web UK English language file, $Date$, $Revision$
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

// ZoneMinder <your language> Translation by <your name>

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

// Simple String Replacements
$SLANG = array(
    'SystemLog'             => 'System Log',
    'DateTime'              => 'Date/Time',
    'Component'             => 'Component',
    'Pid'                   => 'PID',
    'Level'                 => 'Level',
    'Message'               => 'Message',
    'Line'                  => 'Line',
    'More'                  => 'More',
    'Clear'                 => 'Clear',
    '24BitColour'           => '24 bit colour',
    '32BitColour'           => '32 bit colour',
    '8BitGrey'              => '8 bit greyscale',
    'Action'                => 'Action',
    'Actual'                => 'Actual',
    'AddNewControl'         => 'Add New Control',
    'AddNewMonitor'         => 'Add',
    'AddMonitorDisabled'    => 'Your user is not allowed to add a new monitor',
    'AddNewServer'          => 'Add New Server',
    'AddNewStorage'         => 'Add New Storage',
    'AddNewUser'            => 'Add New User',
    'AddNewZone'            => 'Add New Zone',
    'Alarm'                 => 'Alarm',
    'AlarmBrFrames'         => 'Alarm<br/>Frames',
    'AlarmFrame'            => 'Alarm Frame',
    'AlarmFrameCount'       => 'Alarm Frame Count',
    'AlarmLimits'           => 'Alarm Limits',
    'AlarmMaximumFPS'       => 'Alarm Maximum FPS',
    'AlarmPx'               => 'Alarm Px',
    'AlarmRefImageBlendPct' => 'Alarm Reference Image Blend %ge',
    'AlarmRGBUnset'         => 'You must set an alarm RGB colour',
    'Alert'                 => 'Alert',
    'All'                   => 'All',
    'AllTokensRevoked'      => 'All Tokens Revoked',
    'AnalysisFPS'           => 'Analysis FPS',
    'AnalysisUpdateDelay'   => 'Analysis Update Delay',
    'API'                   => 'API',
    'APIEnabled'            => 'API Enabled',
    'Apply'                 => 'Apply',
    'ApplyingStateChange'   => 'Applying State Change',
    'ArchArchived'          => 'Archived Only',
    'Archive'               => 'Archive',
    'Archived'              => 'Archived',
    'ArchUnarchived'        => 'Unarchived Only',
    'Area'                  => 'Area',
    'AreaUnits'             => 'Area (px/%)',
    'AttrAlarmFrames'       => 'Alarm Frames',
    'AttrAlarmedZone'       => 'Alarmed Zone',
    'AttrArchiveStatus'     => 'Archive Status',
    'AttrAvgScore'          => 'Avg. Score',
    'AttrCause'             => 'Cause',
    'AttrStartDate'         => 'Start Date',
    'AttrEndDate'           => 'End Date',
    'AttrStartDateTime'     => 'Start Date/Time',
    'AttrEndDateTime'       => 'End Date/Time',
    'AttrEventDiskSpace'    => 'Event Disk Space',
    'AttrDiskSpace'         => 'File System Disk Space',
    'AttrDiskBlocks'        => 'Disk Blocks',
    'AttrDiskPercent'       => 'Disk Percent',
    'AttrDuration'          => 'Duration',
    'AttrFrames'            => 'Frames',
    'AttrId'                => 'Id',
    'AttrMaxScore'          => 'Max. Score',
    'AttrMonitorId'         => 'Monitor Id',
    'AttrMonitorName'       => 'Monitor Name',
    'AttrSecondaryStorageArea' => 'Secondary Storage Area',
    'AttrStorageArea'       => 'Storage Area',
    'AttrFilterServer'      => 'Server Filter is Running On',
    'AttrMonitorServer'     => 'Server Monitor is Running On',
    'AttrStorageServer'     => 'Server Hosting Storage',
    'AttrStateId'           => 'Run State',
    'AttrName'              => 'Name',
    'AttrNotes'             => 'Notes',
    'AttrSystemLoad'        => 'System Load',
    'AttrStartTime'         => 'Start Time',
    'AttrEndTime'           => 'End Time',
    'AttrTotalScore'        => 'Total Score',
    'AttrStartWeekday'      => 'Start Weekday',
    'AttrEndWeekday'        => 'End Weekday',
    'Auto'                  => 'Auto',
    'AutoStopTimeout'       => 'Auto Stop Timeout',
    'Available'             => 'Available',
    'AvgBrScore'            => 'Avg.<br/>Score',
    'Available'             => 'Available',
    'Background'            => 'Background',
    'BackgroundFilter'      => 'Run filter in background',
    'BadAlarmFrameCount'    => 'Alarm frame count must be an integer of one or more',
    'BadAlarmMaxFPS'        => 'Alarm Maximum FPS must be a positive integer or floating point value',
    'BadAnalysisFPS'        => 'Analysis FPS must be a positive integer or floating point value',
    'BadAnalysisUpdateDelay'=> 'Analysis update delay must be set to an integer of zero or more',
    'BadChannel'            => 'Channel must be set to an integer of zero or more',
    'BadDevice'             => 'Device must be set to a valid value',
    'BadEncoderParameters'  => 'Encoder does not work well without at least a value for crf. Please see the help.',
    'BadFormat'             => 'Format must be set to a valid value',
    'BadFPSReportInterval'  => 'FPS report interval buffer count must be an integer of 0 or more',
    'BadFrameSkip'          => 'Frame skip count must be an integer of zero or more',
    'BadMotionFrameSkip'    => 'Motion Frame skip count must be an integer of zero or more',
    'BadHeight'             => 'Height must be set to a valid value',
    'BadHost'               => 'Host must be set to a valid ip address or hostname, do not include http://',
    'BadImageBufferCount'   => 'Image buffer size must be an integer of 2 or more',
    'BadLabelX'             => 'Label X co-ordinate must be set to an integer of zero or more',
    'BadLabelY'             => 'Label Y co-ordinate must be set to an integer of zero or more',
    'BadMaxFPS'             => 'Maximum FPS must be a positive integer or floating point value',
    'BadNameChars'          => 'Names may only contain alphanumeric characters plus spaces, hyphen and underscore',
    'BadPalette'            => 'Palette must be set to a valid value',
    'BadColours'            => 'Target colour must be set to a valid value',
    'BadPassthrough'        => 'Passthrough only works with ffmpeg type monitors.',
    'BadPath'               => 'Path must be set to a valid value',
    'BadPathNotEncoded'     => 'Path must be set to a valid value. We have detected invalid characters !*\'()$ ,#[] that may need to be url percent encoded.',
    'BadPort'               => 'Port must be set to a valid number',
    'BadPostEventCount'     => 'Post event image count must be an integer of zero or more',
    'BadPreEventCount'      => 'Pre event image count must be at least zero, and less than image buffer size',
    'BadPreEventCountMaxImageBufferCount'      => 'Max Image Buffer Count should be greater than Pre event image count or else it cannot be satisfied',
    'BadRefBlendPerc'       => 'Reference blend percentage must be a positive integer',
    'BadNoSaveJPEGsOrVideoWriter' => 'SaveJPEGs and VideoWriter are both set to disabled.  Nothing will be recorded!',
    'BadSectionLength'      => 'Section length must be an integer of 30 or more',
    'BadSignalCheckColour'  => 'Signal check colour must be a valid RGB colour string',
    'BadStreamReplayBuffer' => 'Stream replay buffer must be an integer of zero or more',
    'BadSourceType'         => 'Source Type \"Web Site\" requires the Function to be set to \"Monitor\"',
    'BadWarmupCount'        => 'Warmup frames must be an integer of zero or more',
    'BadWebColour'          => 'Web colour must be a valid web colour string',
    'BadWebSitePath'        => 'Please enter a complete website url, including the http:// or https:// prefix.',
    'BadWidth'              => 'Width must be set to a valid value',
    'Bandwidth'             => 'Bandwidth',
    'BandwidthHead'         => 'Bandwidth',	// This is the end of the bandwidth status on the top of the console, different in many language due to phrasing
    'BlobPx'                => 'Blob Px',
    'Blobs'                 => 'Blobs',
    'BlobSizes'             => 'Blob Sizes',
    'Brightness'            => 'Brightness',
    'Buffer'                => 'Buffer',
    'Buffers'               => 'Buffers',
    'CanAutoFocus'          => 'Can Auto Focus',
    'CanAutoGain'           => 'Can Auto Gain',
    'CanAutoIris'           => 'Can Auto Iris',
    'CanAutoWhite'          => 'Can Auto White Bal.',
    'CanAutoZoom'           => 'Can Auto Zoom',
    'Cancel'                => 'Cancel',
    'CancelForcedAlarm'     => 'Cancel Forced Alarm',
    'CanFocusAbs'           => 'Can Focus Absolute',
    'CanFocus'              => 'Can Focus',
    'CanFocusCon'           => 'Can Focus Continuous',
    'CanFocusRel'           => 'Can Focus Relative',
    'CanGainAbs'            => 'Can Gain Absolute',
    'CanGain'               => 'Can Gain ',
    'CanGainCon'            => 'Can Gain Continuous',
    'CanGainRel'            => 'Can Gain Relative',
    'CanIrisAbs'            => 'Can Iris Absolute',
    'CanIris'               => 'Can Iris',
    'CanIrisCon'            => 'Can Iris Continuous',
    'CanIrisRel'            => 'Can Iris Relative',
    'CanMoveAbs'            => 'Can Move Absolute',
    'CanMove'               => 'Can Move',
    'CanMoveCon'            => 'Can Move Continuous',
    'CanMoveDiag'           => 'Can Move Diagonally',
    'CanMoveMap'            => 'Can Move Mapped',
    'CanMoveRel'            => 'Can Move Relative',
    'CanPan'                => 'Can Pan' ,
    'CanReset'              => 'Can Reset',
	'CanReboot'             => 'Can Reboot',
    'CanSetPresets'         => 'Can Set Presets',
    'CanSleep'              => 'Can Sleep',
    'CanTilt'               => 'Can Tilt',
    'CanWake'               => 'Can Wake',
    'CanWhiteAbs'           => 'Can White Bal. Absolute',
    'CanWhiteBal'           => 'Can White Bal.',
    'CanWhite'              => 'Can White Balance',
    'CanWhiteCon'           => 'Can White Bal. Continuous',
    'CanWhiteRel'           => 'Can White Bal. Relative',
    'CanZoomAbs'            => 'Can Zoom Absolute',
    'CanZoom'               => 'Can Zoom',
    'CanZoomCon'            => 'Can Zoom Continuous',
    'CanZoomRel'            => 'Can Zoom Relative',
    'CaptureHeight'         => 'Capture Height',
    'CaptureMethod'         => 'Capture Method',
    'CaptureResolution'     => 'Capture Resolution',
    'CapturePalette'        => 'Capture Palette',
    'CaptureWidth'          => 'Capture Width',
    'Cause'                 => 'Cause',
    'CheckMethod'           => 'Alarm Check Method',
    'ChooseDetectedCamera'  => 'Choose Detected Camera',
    'ChooseDetectedProfile' => 'Choose Detected Profile',
    'ChooseFilter'          => 'Choose Filter',
    'ChooseLogFormat'       => 'Choose a log format',
    'ChooseLogSelection'    => 'Choose a log selection',
    'ChoosePreset'          => 'Choose Preset',
    'CloneMonitor'          => 'Clone',
    'Close'                 => 'Close',
    'Colour'                => 'Colour',
    'Command'               => 'Command',
    'ConcurrentFilter'      => 'Run filter concurrently',
    'Config'                => 'Config',
    'ConfigOptions'         => 'ConfigOptions',
    'ConfigType'            => 'Config Type',
    'ConfiguredFor'         => 'Configured for',
    'ConfigURL'             => 'Config Base URL',
    'ConfirmDeleteControl'  => 'Warning, deleting a control will reset all monitors that use it to be uncontrollable.<br><br>Are you sure you wish to delete?',
    'ConfirmDeleteDevices'  => 'Are you sure you wish to delete the selected devices?',
    'ConfirmDeleteEvents'   => 'Are you sure you wish to delete the selected events?',
    'ConfirmDeleteTitle'    => 'Delete Confirmation',
    'ConfirmPassword'       => 'Confirm Password',
    'ConjAnd'               => 'and',
    'ConjOr'                => 'or',
    'Console'               => 'Console',
    'ContactAdmin'          => 'Please contact your adminstrator for details.',
    'Continue'              => 'Continue',
    'Contrast'              => 'Contrast',
    'ControlAddress'        => 'Control Address',
    'ControlCap'            => 'Control Capability',
    'ControlCaps'           => 'Control Capabilities',
    'Control'               => 'Control',
    'ControlDevice'         => 'Control Device',
    'Controllable'          => 'Controllable',
    'ControlType'           => 'Control Type',
    'Current'               => 'Current',
    'Cycle'                 => 'Cycle',
    'CycleWatch'            => 'Cycle Watch',
    'Day'                   => 'Day',
    'Debug'                 => 'Debug',
    'DefaultRate'           => 'Default Rate',
    'DefaultScale'          => 'Default Scale',
    'DefaultCodec'          => 'Default Method For Viewing Events',
    'DefaultView'           => 'Default View',
    'Deinterlacing'         => 'Deinterlacing',
    'RTSPDescribe'          => 'Use RTSP Response Media URL',
    'Delay'                 => 'Delay',
    'DeleteAndNext'         => 'Delete &amp; Next',
    'DeleteAndPrev'         => 'Delete &amp; Prev',
    'Delete'                => 'Delete',
    'DeleteSavedFilter'     => 'Delete saved filter',
    'Description'           => 'Description',
    'DetectedCameras'       => 'Detected Cameras',
    'DetectedProfiles'      => 'Detected Profiles',
    'DeviceChannel'         => 'Device Channel',
    'DeviceFormat'          => 'Device Format',
    'DeviceNumber'          => 'Device Number',
    'DevicePath'            => 'Device Path',
    'Device'                => 'Device',
    'Devices'               => 'Devices',
    'Dimensions'            => 'Dimensions',
    'DisableAlarms'         => 'Disable Alarms',
    'Disk'                  => 'Disk',
    'Display'               => 'Display',
    'Displaying'            => 'Displaying',
    'DonateAlready'         => 'No, I\'ve already donated',
    'DonateEnticement'      => 'You\'ve been running ZoneMinder for a while now and hopefully are finding it a useful addition to your home or workplace security. Although ZoneMinder is, and will remain, free and open source, it costs money to develop and support. If you would like to help support future development and new features then please consider donating. Donating is, of course, optional but very much appreciated and you can donate as much or as little as you like.<br/><br/>If you would like to donate please select the option below or go to <a href="https://zoneminder.com/donate/" target="_blank">https://zoneminder.com/donate/</a> in your browser.<br/><br/>Thank you for using ZoneMinder and don\'t forget to visit the forums on <a href="https://forums.zoneminder.com">ZoneMinder.com</a> for support or suggestions about how to make your ZoneMinder experience even better.',
    'Donate'                => 'Please Donate',
    'DonateRemindDay'       => 'Not yet, remind again in 1 day',
    'DonateRemindHour'      => 'Not yet, remind again in 1 hour',
    'DonateRemindMonth'     => 'Not yet, remind again in 1 month',
    'DonateRemindNever'     => 'No, I don\'t want to donate, never remind',
    'DonateRemindWeek'      => 'Not yet, remind again in 1 week',
    'DonateYes'             => 'Yes, I\'d like to donate now',
    'DoNativeMotionDetection'=> 'Do Native Motion Detection',
    'Download'              => 'Download',
    'DuplicateMonitorName'  => 'Duplicate Monitor Name',
    'DuplicateRTSPStreamName' =>  'Duplicate RTSP Stream Name',
    'Duration'              => 'Duration',
    'Edit'                  => 'Edit',
    'EditControl'           => 'Edit Control',
    'EditLayout'            => 'Edit Layout',
    'Email'                 => 'Email',
    'EnableAlarms'          => 'Enable Alarms',
    'Enabled'               => 'Enabled',
    'EnterNewFilterName'    => 'Enter new filter name',
    'ErrorBrackets'         => 'Error, please check you have an equal number of opening and closing brackets',
    'Error'                 => 'Error',
    'ErrorValidValue'       => 'Error, please check that all terms have a valid value',
    'Etc'                   => 'etc',
    'Event'                 => 'Event',
    'EventFilter'           => 'Event Filter',
    'EventId'               => 'Event Id',
    'EventName'             => 'Event Name',
    'EventPrefix'           => 'Event Prefix',
    'Events'                => 'Events',
    'Exclude'               => 'Exclude',
    'Execute'               => 'Execute',
    'ExportCompress'        => 'Use Compression',
    'ExportDetails'         => 'Export Event Details',
    'ExportMatches'         => 'Export Matches',
    'Exif'                  => 'Embed EXIF data into image',
    'Export'                => 'Export',
    'DownloadVideo'         => 'Download Video',
    'GenerateDownload'      => 'Generate Download',
    'ExistsInFileSystem'    => 'Exists In File System',
    'ExportFailed'          => 'Export Failed',
    'ExportFormat'          => 'Export File Format',
    'ExportFormatTar'       => 'Tar',
    'ExportFormatZip'       => 'Zip',
    'ExportFrames'          => 'Export Frame Details',
    'ExportImageFiles'      => 'Export Image Files',
    'ExportLog'             => 'Export Log',
    'Exporting'             => 'Exporting',
    'ExportMiscFiles'       => 'Export Other Files (if present)',
    'ExportOptions'         => 'Export Options',
    'ExportSucceeded'       => 'Export Succeeded',
    'ExportVideoFiles'      => 'Export Video Files (if present)',
    'Far'                   => 'Far',
    'FastForward'           => 'Fast Forward',
    'Feed'                  => 'Feed',
    'Ffmpeg'                => 'Ffmpeg',
    'File'                  => 'File',
    'FilterArchiveEvents'   => 'Archive all matches',
    'FilterUnarchiveEvents' => 'Unarchive all matches',
    'FilterUpdateDiskSpace' => 'Update used disk space',
    'FilterDeleteEvents'    => 'Delete all matches',
    'FilterCopyEvents'      => 'Copy all matches',
    'FilterLockRows'        => 'Lock Rows',
    'FilterMoveEvents'      => 'Move all matches',
    'FilterEmailEvents'     => 'Email details of all matches',
    'FilterEmailTo'    			=> 'Email To',
    'FilterEmailSubject'	  => 'Email Subject',
    'FilterEmailBody'   	  => 'Email Body',
    'FilterExecuteEvents'   => 'Execute command on all matches',
    'FilterLog'             => 'Filter log',
    'FilterMessageEvents'   => 'Message details of all matches',
    'FilterPx'              => 'Filter Px',
    'Filter'                => 'Filter',
    'Filters'               => 'Filters',
    'FilterUnset'           => 'You must specify a filter width and height',
    'FilterUploadEvents'    => 'Upload all matches',
    'FilterUser'            => 'User to run filter as',
    'FilterVideoEvents'     => 'Create video for all matches',
    'First'                 => 'First',
    'FlippedHori'           => 'Flipped Horizontally',
    'FlippedVert'           => 'Flipped Vertically',
    'FnNone'                => 'None',            // Added 2013.08.16.
    'FnMonitor'             => 'Monitor',            // Added 2013.08.16.
    'FnModect'              => 'Modect',            // Added 2013.08.16.
    'FnRecord'              => 'Record',            // Added 2013.08.16.
    'FnMocord'              => 'Mocord',            // Added 2013.08.16.
    'FnNodect'              => 'Nodect',            // Added 2013.08.16.
    'FnExtdect'             => 'Extdect',           // Added 2014.12.14.
    'Focus'                 => 'Focus',
    'ForceAlarm'            => 'Force Alarm',
    'Format'                => 'Format',
    'FPS'                   => 'fps',
    'FPSReportInterval'     => 'FPS Report Interval',
    'Frame'                 => 'Frame',
    'FrameId'               => 'Frame Id',
    'FrameRate'             => 'Frame Rate',
    'Frames'                => 'Frames',
    'FrameSkip'             => 'Frame Skip',
    'MotionFrameSkip'       => 'Motion Frame Skip',
    'FTP'                   => 'FTP',
    'Func'                  => 'Func',
    'Function'              => 'Function',
    'Gain'                  => 'Gain',
    'General'               => 'General',
    'GenerateVideo'         => 'Generate Video',
    'GeneratingVideo'       => 'Generating Video',
    'GetCurrentLocation'    => 'Get Current Location',
    'GoToZoneMinder'        => 'Go to ZoneMinder.com',
    'Grey'                  => 'Grey',
    'Group'                 => 'Group',
    'Groups'                => 'Groups',
    'HasFocusSpeed'         => 'Has Focus Speed',
    'HasGainSpeed'          => 'Has Gain Speed',
    'HasHomePreset'         => 'Has Home Preset',
    'HasIrisSpeed'          => 'Has Iris Speed',
    'HasPanSpeed'           => 'Has Pan Speed',
    'HasPresets'            => 'Has Presets',
    'HasTiltSpeed'          => 'Has Tilt Speed',
    'HasTurboPan'           => 'Has Turbo Pan',
    'HasTurboTilt'          => 'Has Turbo Tilt',
    'HasWhiteSpeed'         => 'Has White Bal. Speed',
    'HasZoomSpeed'          => 'Has Zoom Speed',
    'HighBW'                => 'High&nbsp;B/W',
    'High'                  => 'High',
    'Home'                  => 'Home',
    'Hostname'				=> 'Hostname',
    'Hour'                  => 'Hour',
    'Hue'                   => 'Hue',
    'Id'                    => 'Id',
    'Idle'                  => 'Idle',
    'Ignore'                => 'Ignore',
    'ImageBufferSize'       => 'Image Buffer Size (frames)',
    'MaxImageBufferCount'   => 'Maximum Image Buffer Size (frames)',
    'Image'                 => 'Image',
    'Images'                => 'Images',
    'Include'               => 'Include',
    'In'                    => 'In',
    'InvalidateTokens'      => 'Invalidate all generated tokens',
    'Inverted'              => 'Inverted',
    'Iris'                  => 'Iris',
    'KeyString'             => 'Key String',
    'Label'                 => 'Label',
    'Language'              => 'Language',
    'Last'                  => 'Last',
    'Layout'                => 'Layout',
    'Libvlc'                => 'Libvlc',
    'LimitResultsPost'      => 'results only', // This is used at the end of the phrase 'Limit to first N results only'
    'LimitResultsPre'       => 'Limit to first', // This is used at the beginning of the phrase 'Limit to first N results only'
    'LinkedMonitors'        => 'Linked Monitors',
    'List'                  => 'List',
    'ListMatches'           => 'List Matches',
    'Load'                  => 'Load',
    'Local'                 => 'Local',
    'Log'                   => 'Log',
    'Logs'                  => 'Logs',
    'Logging'               => 'Logging',
    'LoggedInAs'            => 'Logged in as',
    'LoggingIn'             => 'Logging In',
    'Login'                 => 'Login',
    'Logout'                => 'Logout',
    'LowBW'                 => 'Low&nbsp;B/W',
    'Low'                   => 'Low',
    'Main'                  => 'Main',
    'Man'                   => 'Man',
    'Manual'                => 'Manual',
    'Mark'                  => 'Mark',
    'MaxBandwidth'          => 'Max Bandwidth',
    'MaxBrScore'            => 'Max.<br/>Score',
    'MaxFocusRange'         => 'Max Focus Range',
    'MaxFocusSpeed'         => 'Max Focus Speed',
    'MaxFocusStep'          => 'Max Focus Step',
    'MaxGainRange'          => 'Max Gain Range',
    'MaxGainSpeed'          => 'Max Gain Speed',
    'MaxGainStep'           => 'Max Gain Step',
    'MaximumFPS'            => 'Maximum FPS',
    'MaxIrisRange'          => 'Max Iris Range',
    'MaxIrisSpeed'          => 'Max Iris Speed',
    'MaxIrisStep'           => 'Max Iris Step',
    'Max'                   => 'Max',
    'MaxPanRange'           => 'Max Pan Range',
    'MaxPanSpeed'           => 'Max Pan Speed',
    'MaxPanStep'            => 'Max Pan Step',
    'MaxTiltRange'          => 'Max Tilt Range',
    'MaxTiltSpeed'          => 'Max Tilt Speed',
    'MaxTiltStep'           => 'Max Tilt Step',
    'MaxWhiteRange'         => 'Max White Bal. Range',
    'MaxWhiteSpeed'         => 'Max White Bal. Speed',
    'MaxWhiteStep'          => 'Max White Bal. Step',
    'MaxZoomRange'          => 'Max Zoom Range',
    'MaxZoomSpeed'          => 'Max Zoom Speed',
    'MaxZoomStep'           => 'Max Zoom Step',
    'MediumBW'              => 'Medium&nbsp;B/W',
    'Medium'                => 'Medium',
    'MetaConfig'            => 'Meta Config',
    'MinAlarmAreaLtMax'     => 'Minimum alarm area should be less than maximum',
    'MinAlarmAreaUnset'     => 'You must specify the minimum alarm pixel count',
    'MinBlobAreaLtMax'      => 'Minimum blob area should be less than maximum',
    'MinBlobAreaUnset'      => 'You must specify the minimum blob pixel count',
    'MinBlobLtMinFilter'    => 'Minimum blob area should be less than or equal to minimum filter area',
    'MinBlobsLtMax'         => 'Minimum blobs should be less than maximum',
    'MinBlobsUnset'         => 'You must specify the minimum blob count',
    'MinFilterAreaLtMax'    => 'Minimum filter area should be less than maximum',
    'MinFilterAreaUnset'    => 'You must specify the minimum filter pixel count',
    'MinFilterLtMinAlarm'   => 'Minimum filter area should be less than or equal to minimum alarm area',
    'MinFocusRange'         => 'Min Focus Range',
    'MinFocusSpeed'         => 'Min Focus Speed',
    'MinFocusStep'          => 'Min Focus Step',
    'MinGainRange'          => 'Min Gain Range',
    'MinGainSpeed'          => 'Min Gain Speed',
    'MinGainStep'           => 'Min Gain Step',
    'MinIrisRange'          => 'Min Iris Range',
    'MinIrisSpeed'          => 'Min Iris Speed',
    'MinIrisStep'           => 'Min Iris Step',
    'MinPanRange'           => 'Min Pan Range',
    'MinPanSpeed'           => 'Min Pan Speed',
    'MinPanStep'            => 'Min Pan Step',
    'MinPixelThresLtMax'    => 'Minimum pixel threshold should be less than maximum',
    'MinPixelThresUnset'    => 'You must specify a minimum pixel threshold',
    'MinSectionlength'      => 'Minimum section length',
    'MinTiltRange'          => 'Min Tilt Range',
    'MinTiltSpeed'          => 'Min Tilt Speed',
    'MinTiltStep'           => 'Min Tilt Step',
    'MinWhiteRange'         => 'Min White Bal. Range',
    'MinWhiteSpeed'         => 'Min White Bal. Speed',
    'MinWhiteStep'          => 'Min White Bal. Step',
    'MinZoomRange'          => 'Min Zoom Range',
    'MinZoomSpeed'          => 'Min Zoom Speed',
    'MinZoomStep'           => 'Min Zoom Step',
    'Misc'                  => 'Misc',
    'Mode'                  => 'Mode',
    'ModectDuringPTZ'       => 'Do motion detection during PTZ motion',
    'MonitorIds'            => 'Monitor&nbsp;Ids',
    'Monitor'               => 'Monitor',
    'MonitorPresetIntro'    => 'Select an appropriate preset from the list below.<br/><br/>Please note that this may overwrite any values you already have configured for the current monitor.<br/><br/>',
    'MonitorPreset'         => 'Monitor Preset',
    'MonitorProbeIntro'     => 'The list below shows detected analog and network cameras and whether they are already being used or available for selection.<br/><br/>Select the desired entry from the list below.<br/><br/>Please note that not all cameras may be detected and that choosing a camera here may overwrite any values you already have configured for the current monitor.<br/><br/>',
    'MonitorProbe'          => 'Monitor Probe',
    'Monitors'              => 'Monitors',
    'Montage'               => 'Montage',
    'MontageReview'         => 'Montage Review',
    'Month'                 => 'Month',
    'Move'                  => 'Move',
    'MtgDefault'            => 'Default',              // Added 2013.08.15.
    'Mtg2widgrd'            => '2-wide grid',              // Added 2013.08.15.
    'Mtg3widgrd'            => '3-wide grid',              // Added 2013.08.15.
    'Mtg4widgrd'            => '4-wide grid',              // Added 2013.08.15.
    'Mtg3widgrx'            => '3-wide grid, scaled, enlarge on alarm',              // Added 2013.08.15.
    'MustBeGe'              => 'must be greater than or equal to',
    'MustBeLe'              => 'must be less than or equal to',
    'MustConfirmPassword'   => 'You must confirm the password',
    'MustSupplyPassword'    => 'You must supply a password',
    'MustSupplyUsername'    => 'You must supply a username',
    'Name'                  => 'Name',
    'Near'                  => 'Near',
    'Network'               => 'Network',
    'NewGroup'              => 'New Group',
    'NewLabel'              => 'New Label',
    'New'                   => 'New',
    'NewPassword'           => 'New Password',
    'NewState'              => 'New State',
    'NewUser'               => 'New User',
    'Next'                  => 'Next',
    'NextMonitor'           => 'Next Monitor',
    'NoDetectedCameras'     => 'No Detected Cameras',
    'NoDetectedProfiles'    => 'No Detected Profiles',
    'NoFramesRecorded'      => 'There are no frames recorded for this event',
    'NoGroup'               => 'No Group',
    'NoneAvailable'         => 'None available',
    'None'                  => 'None',
    'No'                    => 'No',
    'Normal'                => 'Normal',
    'NoSavedFilters'        => 'NoSavedFilters',
    'NoStatisticsRecorded'  => 'There are no statistics recorded for this event/frame',
    'Notes'                 => 'Notes',
    'NumPresets'            => 'Num Presets',
    'Off'                   => 'Off',
    'On'                    => 'On',
    'OnvifProbe'            => 'ONVIF',
    'OnvifProbeIntro'       => 'The list below shows detected ONVIF cameras and whether they are already being used or available for selection.<br/><br/>Select the desired entry from the list below.<br/><br/>Please note that not all cameras may be detected and that choosing a camera here may overwrite any values you already have configured for the current monitor.<br/><br/>',
    'OnvifCredentialsIntro' => 'Please supply user name and password for the selected camera.<br/>If no user has been created for the camera then the user given here will be created with the given password.<br/><br/>',
    'Open'                  => 'Open',
    'OpEq'                  => 'equal to',
    'OpGtEq'                => 'greater than or equal to',
    'OpGt'                  => 'greater than',
    'OpIn'                  => 'in set',
    'OpLtEq'                => 'less than or equal to',
    'OpLt'                  => 'less than',
    'OpMatches'             => 'matches',
    'OpNe'                  => 'not equal to',
    'OpNotIn'               => 'not in set',
    'OpNotMatches'          => 'does not match',
    'OpIs'                  => 'is',
    'OpIsNot'               => 'is not',
    'OpLike'                => 'contains',
    'OpNotLike'             => 'does not contain',
    'OptionalEncoderParam'  => 'Optional Encoder Parameters',
    'OptionHelp'            => 'Option Help',
    'OptionRestartWarning'  => 'These changes may not come into effect fully\nwhile the system is running. When you have\nfinished making your changes please ensure that\nyou restart ZoneMinder.',
    'Options'               => 'Options',
    'Order'                 => 'Order',
    'OrEnterNewName'        => 'or enter new name',
    'Orientation'           => 'Orientation',
    'Out'                   => 'Out',
    'OverwriteExisting'     => 'Overwrite Existing',
    'Paged'                 => 'Paged',
    'PanLeft'               => 'Pan Left',
    'Pan'                   => 'Pan',
    'PanRight'              => 'Pan Right',
    'PanTilt'               => 'Pan/Tilt',
    'Parameter'             => 'Parameter',
    'ParentGroup'           => 'Parent Group',
    'Password'              => 'Password',
    'PasswordsDifferent'    => 'The new and confirm passwords are different',
    'PathToIndex'           => 'Path To Index',
    'PathToZMS'             => 'Path To ZMS',
    'PathToApi'             => 'Path To Api',
    'Paths'                 => 'Paths',
    'Pause'                 => 'Pause',
    'PauseCycle'            => 'Pause Cycle',
    'PhoneBW'               => 'Phone&nbsp;B/W',
    'Phone'                 => 'Phone',
    'PixelDiff'             => 'Pixel Diff',
    'Pixels'                => 'pixels',
    'PlayAll'               => 'Play All',
    'Play'                  => 'Play',
    'PlayCycle'             => 'Play Cycle',
    'Plugins'               => 'Plugins',
    'PleaseWait'            => 'Please Wait',
    'Point'                 => 'Point',
    'PostEventImageBuffer'  => 'Post Event Image Count',
    'PreEventImageBuffer'   => 'Pre Event Image Count',
    'PreserveAspect'        => 'Preserve Aspect Ratio',
    'Preset'                => 'Preset',
    'Presets'               => 'Presets',
    'Prev'                  => 'Prev',
    'PreviousMonitor'       => 'Previous Monitor',
    'Privacy'               => 'Privacy',
    'PrivacyAbout'          => 'About',
    'PrivacyAboutText'      => 'Since 2002, ZoneMinder has been the premier free and open-source Video Management System (VMS) solution for Linux platforms. ZoneMinder is supported by the community and is managed by those who choose to volunteer their spare time to the project. The best way to improve ZoneMinder is to get involved.',
    'PrivacyContact'        => 'Contact',
    'PrivacyContactText'    => 'Please contact us <a href="https://zoneminder.com/contact/">here</a> for any questions regarding our privacy policy or to have your information removed.<br><br>For support, there are three primary ways to engage with the community:<ul><li>The ZoneMinder <a href="https://forums.zoneminder.com/">user forum</a></li><li>The ZoneMinder <a href="https://zoneminder-chat.herokuapp.com/">Slack channel</a></li><li>The ZoneMinder <a href="https://github.com/ZoneMinder/zoneminder/issues">Github forum</a></li></ul><p>Our Github forum is only for bug reporting. Please use our user forum or slack channel for all other questions or comments.</p>',
    'PrivacyCookies'        => 'Cookies',
    'PrivacyCookiesText'    => 'Whether you use a web browser or a mobile app to communicate with the ZoneMinder server, a ZMSESSID cookie is created on the client to uniquely identify a session with the ZoneMinder server. ZmCSS and zmSkin cookies are created to remember your style and skin choices.',
    'PrivacyTelemetry'      => 'Telemetry',
    'PrivacyTelemetryText'  => 'Because ZoneMinder is open-source, anyone can install it without registering. This makes it difficult to  answer questions such as: how many systems are out there, what is the largest system out there, what kind of systems are out there, or where are these systems located? Knowing the answers to these questions, helps users who ask us these questions, and it helps us set priorities based on the majority user base.',
    'PrivacyTelemetryList'  => 'The ZoneMinder Telemetry daemon collects the following data about your system:<ul><li>A unique identifier (UUID) <li>City based location is gathered by querying <a href="https://ipinfo.io/geo">ipinfo.io</a>. City, region, country, latitude, and longitude parameters are saved. The latitude and longitude coordinates are accurate down to the city or town level only!<li>Current time<li>Total number of monitors<li>Total number of events<li>System architecture<li>Operating system kernel, distro, and distro version<li>Version of ZoneMinder<li>Total amount of memory<li>Number of cpu cores</ul>',
    'PrivacyMonitorList'    => 'The following configuration parameters from each monitor are collected:<ul><li>Id<li>Name<li>Type<li>Function<li>Width<li>Height<li>Colours<li>MaxFPS<li>AlarmMaxFPS</ul>',
    'PrivacyConclusionText' => 'We are <u>NOT</u> collecting any image specific data from your cameras. We don’t know what your cameras are watching. This data will not be sold or used for any purpose not stated herein. By clicking accept, you agree to send us this data to help make ZoneMinder a better product. By clicking decline, you can still freely use ZoneMinder and all its features.',
    'Probe'                 => 'Probe',
    'ProfileProbe'          => 'Stream Probe',
    'ProfileProbeIntro'     => 'The list below shows the existing stream profiles of the selected camera .<br/><br/>Select the desired entry from the list below.<br/><br/>Please note that ZoneMinder cannot configure additional profiles and that choosing a camera here may overwrite any values you already have configured for the current monitor.<br/><br/>',
    'Progress'              => 'Progress',
    'Protocol'              => 'Protocol',
    'Rate'                  => 'Rate',
    'RecaptchaWarning'      => 'Your reCaptcha secret key is invalid. Please correct it, or reCaptcha will not work', // added Sep 24 2015 - PP
    'RecordAudio'           => 'Record Audio?', // Edited to streamline UI label column size. Description moves to popup help text in OPTIONS_RECORDAUDIO - RkR
    'Real'                  => 'Real',
    'Record'                => 'Record',
    'RefImageBlendPct'      => 'Reference Image Blend %ge',
    'Refresh'               => 'Refresh',
    'RemoteHostName'        => 'Host Name',
    'RemoteHostPath'        => 'Path',
    'RemoteHostSubPath'     => 'SubPath',
    'RemoteHostPort'        => 'Port',
    'RemoteImageColours'    => 'Image Colours',
    'RemoteMethod'          => 'Method',
    'RemoteProtocol'        => 'Protocol',
    'Remote'                => 'Remote',
    'Rename'                => 'Rename',
    'ReplayAll'             => 'All Events',
    'ReplayGapless'         => 'Gapless Events',
    'Replay'                => 'Replay',
    'ReplaySingle'          => 'Single Event',
    'ReportEventAudit'      => 'Audit Events Report',
    'ResetEventCounts'      => 'Reset Event Counts',
    'Reset'                 => 'Reset',
    'Restarting'            => 'Restarting',
    'Restart'               => 'Restart',
    'RestrictedCameraIds'   => 'Restricted Camera Ids',
    'RestrictedMonitors'    => 'Restricted Monitors',
    'ReturnDelay'           => 'Return Delay',
    'ReturnLocation'        => 'Return Location',
    'RevokeAllTokens'       =>  'Revoke All Tokens',
    'Rewind'                => 'Rewind',
    'RotateLeft'            => 'Rotate Left',
    'RotateRight'           => 'Rotate Right',
    'RTSPTransport'         => 'RTSP Transport Protocol',
    'RunAudit'              => 'Run Audit Process',
    'RunLocalUpdate'        => 'Please run zmupdate.pl to update',
    'RunMode'               => 'Run Mode',
    'Running'               => 'Running',
    'RunState'              => 'Run State',
    'RunStats'              => 'Run Stats Process',
    'RunTrigger'            => 'Run Trigger Process',
    'RunEventNotification'  => 'Run Event Notification Process',
    'SaveAs'                => 'Save as',
    'SaveFilter'            => 'Save Filter',
    'SaveJPEGs'             => 'Save JPEGs',
    'Save'                  => 'Save',
    'Scale'                 => 'Scale',
    'Score'                 => 'Score',
    'Secs'                  => 'Secs',
    'Sectionlength'         => 'Section length',
    'SelectMonitors'        => 'Select Monitors',
    'Select'                => 'Select',
    'SelectFormat'          => 'Select Format',
    'SelectLog'             => 'Select Log',
    'SelfIntersecting'      => 'Polygon edges must not intersect',
    'SetNewBandwidth'       => 'Set New Bandwidth',
    'SetPreset'             => 'Set Preset',
    'Set'                   => 'Set',
    'Settings'              => 'Settings',
    'ShowFilterWindow'      => 'Show Filter Window',
    'ShowTimeline'          => 'Show Timeline',
    'Shutdown'              => 'Shutdown',
    'SignalCheckColour'     => 'Signal Check Colour',
    'SignalCheckPoints'     => 'Signal Check Points',
    'Size'                  => 'Size',
    'SkinDescription'       => 'Change the skin for this session',
    'CSSDescription'        => 'Change the css for this session',
    'Sleep'                 => 'Sleep',
    'SortAsc'               => 'Asc',
    'SortBy'                => 'Sort by',
    'SortDesc'              => 'Desc',
    'Source'                => 'Source',
    'SourceColours'         => 'Source Colours',
    'SourcePath'            => 'Source Path',
    'SourceType'            => 'Source Type',
    'SpeedHigh'             => 'High Speed',
    'SpeedLow'              => 'Low Speed',
    'SpeedMedium'           => 'Medium Speed',
    'Speed'                 => 'Speed',
    'SpeedTurbo'            => 'Turbo Speed',
    'Start'                 => 'Start',
    'State'                 => 'State',
    'Stats'                 => 'Stats',
    'Status'                => 'Status',
    'StatusUnknown'         => 'Unknown',
    'StatusConnected'       => 'Capturing',
    'StatusNotRunning'      => 'Not Running',
    'StatusRunning'         => 'Not Capturing',
    'StepBack'              => 'Step Back',
    'StepForward'           => 'Step Forward',
    'StepLarge'             => 'Large Step',
    'StepMedium'            => 'Medium Step',
    'StepNone'              => 'No Step',
    'StepSmall'             => 'Small Step',
    'Step'                  => 'Step',
    'Stills'                => 'Stills',
    'Stopped'               => 'Stopped',
    'Stop'                  => 'Stop',
    'StorageArea'           => 'Storage Area',
    'StorageDoDelete'       => 'Do Deletes',
    'StorageScheme'         => 'Scheme',
    'StreamReplayBuffer'    => 'Stream Replay Image Buffer',
    'Stream'                => 'Stream',
    'Submit'                => 'Submit',
    'System'                => 'System',
    'TargetColorspace'      => 'Target colorspace',
    'Tele'                  => 'Tele',
    'Thumbnail'             => 'Thumbnail',
    'Tilt'                  => 'Tilt',
    'TimeDelta'             => 'Time Delta',
    'Timeline'              => 'Timeline',
    'TimelineTip1'          => 'Pass your mouse over the graph to view a snapshot image and event details.',              // Added 2013.08.15.
    'TimelineTip2'          => 'Click on the coloured sections of the graph, or the image, to view the event.',              // Added 2013.08.15.
    'TimelineTip3'          => 'Click on the background to zoom in to a smaller time period based around your click.',              // Added 2013.08.15.
    'TimelineTip4'          => 'Use the controls below to zoom out or navigate back and forward through the time range.',              // Added 2013.08.15.
    'TimestampLabelFormat'  => 'Timestamp Label Format',
    'TimestampLabelX'       => 'Timestamp Label X',
    'TimestampLabelY'       => 'Timestamp Label Y',
    'TimestampLabelSize'    => 'Font Size',
    'Timestamp'             => 'Timestamp',
    'TimeStamp'             => 'Time Stamp',
    'Time'                  => 'Time',
    'Today'                 => 'Today',
    'Tools'                 => 'Tools',
    'Total'                 => 'Total',
    'TotalBrScore'          => 'Total<br/>Score',
    'TrackDelay'            => 'Track Delay',
    'TrackMotion'           => 'Track Motion',
    'Triggers'              => 'Triggers',
    'TurboPanSpeed'         => 'Turbo Pan Speed',
    'TurboTiltSpeed'        => 'Turbo Tilt Speed',
    'Type'                  => 'Type',
    'TZUnset'               => 'Unset - use value in php.ini',
    'Unarchive'             => 'Unarchive',
    'Undefined'             => 'Undefined',
    'Units'                 => 'Units',
    'Unknown'               => 'Unknown',
    'UpdateAvailable'       => 'An update to ZoneMinder is available.',
    'UpdateNotNecessary'    => 'No update is necessary.',
    'Update'                => 'Update',
    'Upload'                => 'Upload',
    'Updated'               => 'Updated',
    'UsedPlugins'	        => 'Used Plugins',
    'UseFilterExprsPost'    => '&nbsp;filter&nbsp;expressions', // This is used at the end of the phrase 'use N filter expressions'
    'UseFilterExprsPre'     => 'Use&nbsp;', // This is used at the beginning of the phrase 'use N filter expressions'
    'UseFilter'             => 'Use Filter',
    'Username'              => 'Username',
    'Users'                 => 'Users',
    'User'                  => 'User',
    'Value'                 => 'Value',
    'VersionIgnore'         => 'Ignore this version',
    'VersionRemindDay'      => 'Remind again in 1 day',
    'VersionRemindHour'     => 'Remind again in 1 hour',
    'VersionRemindNever'    => 'Don\'t remind about new versions',
    'VersionRemindWeek'     => 'Remind again in 1 week',
    'VersionRemindMonth'    => 'Remind again in 1 month',
    'Version'               => 'Version',
    'ViewMatches'           => 'View Matches',
    'VideoFormat'           => 'Video Format',
    'VideoGenFailed'        => 'Video Generation Failed!',
    'VideoGenFiles'         => 'Existing Video Files',
    'VideoGenNoFiles'       => 'No Video Files Found',
    'VideoGenParms'         => 'Video Generation Parameters',
    'VideoGenSucceeded'     => 'Video Generation Succeeded!',
    'VideoSize'             => 'Video Size',
    'VideoWriter'           => 'Video Writer',
    'Video'                 => 'Video',
    'ViewAll'               => 'View All',
    'ViewEvent'             => 'View Event',
    'ViewPaged'             => 'View Paged',
    'View'                  => 'View',
	'V4LCapturesPerFrame'	=> 'Captures Per Frame',
	'V4LMultiBuffer'		=> 'Multi Buffering',
    'Wake'                  => 'Wake',
    'WarmupFrames'          => 'Warmup Frames',
    'Watch'                 => 'Watch',
    'WebColour'             => 'Web Colour',
    'Web'                   => 'Web',
    'WebSiteUrl'            => 'Website URL',
    'Week'                  => 'Week',
    'WhiteBalance'          => 'White Balance',
    'White'                 => 'White',
    'Wide'                  => 'Wide',
    'X10ActivationString'   => 'X10 Activation String',
    'X10InputAlarmString'   => 'X10 Input Alarm String',
    'X10OutputAlarmString'  => 'X10 Output Alarm String',
    'X10'                   => 'X10',
    'X'                     => 'X',
    'Yes'                   => 'Yes',
    'YouNoPerms'            => 'You do not have permissions to access this resource.',
    'Y'                     => 'Y',
    'ZoneAlarmColour'       => 'Alarm Colour (Red/Green/Blue)',
    'ZoneArea'              => 'Zone Area',
    'ZoneFilterSize'        => 'Filter Width/Height (pixels)',
    'ZoneMinderLog'         => 'ZoneMinder Log',
    'ZoneMinMaxAlarmArea'   => 'Min/Max Alarmed Area',
    'ZoneMinMaxBlobArea'    => 'Min/Max Blob Area',
    'ZoneMinMaxBlobs'       => 'Min/Max Blobs',
    'ZoneMinMaxFiltArea'    => 'Min/Max Filtered Area',
    'ZoneMinMaxPixelThres'  => 'Min/Max Pixel Threshold (0-255)',
    'ZoneOverloadFrames'    => 'Overload Frame Ignore Count',
    'ZoneExtendAlarmFrames' => 'Extend Alarm Frame Count',
    'Zones'                 => 'Zones',
    'Zone'                  => 'Zone',
    'ZoomIn'                => 'Zoom In',
    'ZoomOut'               => 'Zoom Out',
    'Zoom'                  => 'Zoom',
);

// Complex replacements with formatting and/or placements, must be passed through sprintf
$CLANG = array(
    'CurrentLogin'          => 'Current login is \'%1$s\'',
    'EventCount'            => '%1$s %2$s', // For example '37 Events' (from Vlang below)
    'LastEvents'            => 'Last %1$s %2$s', // For example 'Last 37 Events' (from Vlang below)
    'LatestRelease'         => 'The latest release is v%1$s, you have v%2$s.',
    'MonitorCount'          => '%1$s %2$s', // For example '4 Monitors' (from Vlang below)
    'MonitorFunction'       => 'Monitor %1$s Function',
    'RunningRecentVer'      => 'You are running the most recent version of ZoneMinder, v%s.',
    'VersionMismatch'       => 'Version mismatch, system is version %1$s, database is %2$s.',
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
// 'Potato' => array( 1=>'Potati', 2=>'Potaton', 3=>'Potaten' ),
//
// and the zmVlang function decides that the first form is used for counts ending in
// 0, 5-9 or 11-19 and the second form when ending in 1 etc.
//

// Variable arrays expressing plurality, see the zmVlang description above
$VLANG = array(
    'Event'                 => array( 0=>'Events', 1=>'Event', 2=>'Events' ),
    'Monitor'               => array( 0=>'Monitors', 1=>'Monitor', 2=>'Monitors' ),
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
//echo sprintf( $CLANG['MonitorCount'], count($monitors), zmVlang( $VLANG['VlangMonitor'], count($monitors) ) );

// In this section you can override the default prompt and help texts for the options area
// These overrides are in the form show below where the array key represents the option name minus the initial ZM_
// So for example, to override the help text for ZM_LANG_DEFAULT do
$OLANG = array(
	'OPTIONS_FFMPEG' => array(
    'Help' => '
      Parameters in this field are passed on to FFmpeg. Multiple parameters can be separated by ,~~
      Examples (do not enter quotes)~~~~
      "allowed_media_types=video" Set datatype to request from cam (audio, video, data)~~~~
      "reorder_queue_size=nnn" Set number of packets to buffer for handling of reordered packets
    '
	),
  'OPTIONS_ENCODER_PARAMETERS' => array(
    'Help' => '
    Parameters passed to the encoding codec. name=value separated by either , or newline.~~
    For example to changing quality, use the crf option.  1 is best, 51 is worst 23 is default.~~
~~
    crf=23~~
    ~~
    You might want to alter the movflags value to support different behaviours. Some people have troubles viewing videos due to the frag_keyframe option, but that option is supposed to allow viewing of incomplete events. See 
    [https://ffmpeg.org/ffmpeg-formats.html](https://ffmpeg.org/ffmpeg-formats.html)
    for more information.  ZoneMinder\'s default is frag_keyframe,empty_moov~~
    ',
  ),
  'OPTIONS_RECORDAUDIO' => array(
    'Help' => '
    Whether to store the audio stream when saving an event.'
    ),
  'OPTIONS_DECODERHWACCELNAME' => array(
    'Help' => '
    This is equivalent to the ffmpeg -hwaccel command line option.  With intel graphics support, use "vaapi".  For NVIDIA cuda support use "cuda". To check for support, run ffmpeg -hwaccels on the command line.'
    ),
  'OPTIONS_DECODERHWACCELDEVICE' => array(
    'Help' => '
    This is equivalent to the ffmpeg -hwaccel_device command line option.  You should only have to specify this if you have multiple GPUs.  A typical value for Intel VAAPI would be /dev/dri/renderD128.'
    ),
    'OPTIONS_RTSPTrans' => array(
      'Help' => '
        This sets the RTSP Transport Protocol for FFmpeg.~~
        TCP - Use TCP (interleaving within the RTSP control channel) as transport protocol.~~
        UDP - Use UDP as transport protocol. Higher resolution cameras have experienced some \'smearing\' while using UDP, if so try TCP~~
        UDP Multicast - Use UDP Multicast as transport protocol~~
        HTTP - Use HTTP tunneling as transport protocol, which is useful for passing proxies.~~
      '
	),
	'OPTIONS_LIBVLC' => array(
    'Help' => '
      Parameters in this field are passed on to libVLC. Multiple parameters can be separated by ,~~
      Examples (do not enter quotes)~~~~
      "--rtp-client-port=nnn" Set local port to use for rtp data~~~~
      "--verbose=2" Set verbosity of libVLC
      '
	),
	'OPTIONS_EXIF' => array(
		'Help' => 'Enable this option to embed EXIF data into each jpeg frame.'
	),
	'OPTIONS_RTSPDESCRIBE' => array(
    'Help' => '
      Sometimes, during the initial RTSP handshake, the camera will send an updated media URL.
      Enable this option to tell ZoneMinder to use this URL. Disable this option to ignore the
      value from the camera and use the value as entered in the monitor configuration~~~~
      Generally this should be enabled. However, there are cases where the camera can get its
      own URL incorrect, such as when the camera is streaming through a firewall
    '
  ),
	'OPTIONS_MAXFPS' => array(
    'Help' => '
      This field has certain limitations when used for non-local devices.~~
      Failure to adhere to these limitations will cause a delay in live video, irregular frame skipping,
      and missed events~~
      For streaming IP cameras, do not use this field to reduce the frame rate. Set the frame rate in the
      camera, instead. In the past it was advised to set a value higher than the frame rate of the camera
      but this is no longer needed or a good idea.
      Some, mostly older, IP cameras support snapshot mode. In this case ZoneMinder is actively polling the camera
      for new images. In this case, it is safe to use the field.
      '
	),
	'OPTIONS_ALARMMAXFPS' => array(
    'Help' => '
    This field has certain limitations when used for non-local devices.~~
    Failure to adhere to these limitations will cause a delay in live video, irregular frame skipping,
    and missed events~
    This setting allows you to override the Maximum FPS value if this circumstance occurs. As with the Maximum FPS 
    setting, leaving this blank implies no limit.
    '
	),
	'OPTIONS_LINKED_MONITORS' => array(
    'Help' => '
      This field allows you to select other monitors on your system that act as 
      triggers for this monitor. So if you have a camera covering one aspect of 
      your property you can force all cameras to record while that camera 
      detects motion or other events. Click on ‘Select’ to choose linked monitors. 
      Be very careful not to create circular dependencies with this feature 
      because you will have infinitely persisting alarms which is almost 
      certainly not what you want! To unlink monitors you can ctrl-click.
      '
	),
  'FUNCTION_NONE' => array(
    'Help' => '
      In None mode no processes are started.  No capturing will occur.
      '
  ),
  'FUNCTION_MONITOR' => array(
    'Help' => '
      In Monitor mode the capture process (zmc) will connect to the camera and stream data.
      It will be decoded if necessary and live viewing will be possible.
      No motion detection will be performed.  This monitor type cannot save video.
      '
  ),
  'FUNCTION_MODECT' => array(
    'Help' => '
      In Modect mode the capture process (zmc) will connect to the camera and stream data.
      It will be decoded if necessary and live viewing will be possible.
      In addition the video will be analysed for motion.  
      When motion is detected, events will be created and video will be stored.
      Motion data will be stored in the database for each event.
      Events may also be triggered externally (zmtrigger) or by linked monitors.
      '
  ),
  'FUNCTION_RECORD' => array(
    'Help' => '
      In Record mode the capture process (zmc) will connect to the camera and stream data.
      It will be decoded if necessary and live viewing will be possible.
      Motion detection will not be performed.
      Events will be created at fixed intervals and video will be stored.
      '
  ),
  'FUNCTION_MOCORD' => array(
    'Help' => '
      In Mocord mode the capture process (zmc) will connect to the camera and stream data.
      It will be decoded if necessary and live viewing will be possible.
      In addition the video will be analysed for motion.  
      Events will be created at fixed intervals or at start and stop of motion.
      Video will always be stored to disk and events will have the motion data stored in the database.
      Events may also be triggered externally (zmtrigger) or by linked monitors.
      '
  ),
  'FUNCTION_NODECT' => array(
    'Help' => '
      In Nodect mode the capture process (zmc) will connect to the camera and stream data.
      It will be decoded if necessary and live viewing will be possible.
      In addition any linked cameras will be checked for their alarm status. 
      When linked cameras or an external trigger (zmtrigger) are alarmed, events will be created
      and video will be stored.  No other motion detection will occur.
      '
  ),
  'FUNCTION_ANALYSIS_ENABLED' => array(
    'Help' => '
      When in Modect, Mocord, Nodect or RECORD mode the analysis process can be turned on/off.
      This setting sets the default state when the process starts up.
      It can then be turned on/off through external triggers zmtrigger zmu or the web ui.
      When not enabled no motion detection or linked monitor checking will be performed and 
      no events will be created.
      '
  ),
  'FUNCTION_DECODING_ENABLED' => array(
    'Help' => '
      When in Record or Nodect mode and using H264Passthrough with no jpegs being saved, we can
      optionally choose to not decode the H264/H265 packets.  This will drastically reduce cpu use
      but will make live view unavailable for this monitor.'
  ),
  'ImageBufferCount' => array(
    'Help' => '
    Number of raw images available in /dev/shm. Currently should be set in the 3-5 range.  Used for live viewing.'
  ),
  'MaxImageBufferCount' => array(
    'Help' => '
    Maximum number of video packets that will be held in the packet queue.
    The packetqueue will normally manage itself, keeping Pre Event Count frames or all since last keyframe if using 
    passthrough mode. You can set a maximum to prevent the monitor from consuming too much ram, but your events might
    not have all the frames they should if your keyframe interval is larger than this value.
    You will get errors in your logs about this. So make sure your keyframe interval is low or you have enough ram.
  '
  ),

//    'LANG_DEFAULT' => array(
//        'Prompt' => "This is a new prompt for this option",
//        'Help' => "This is some new help for this option which will be displayed in the window when the ? is clicked"
//    ),
);

?>
