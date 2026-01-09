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
    'Pid'                   => 'PID',
    '24BitColour'           => '24 bit colour',
    '32BitColour'           => '32 bit colour',
    '8BitGrey'              => '8 bit greyscale',
    'AddNewControl'         => 'Add New Control',
    'AddNewMonitor'         => 'Add',
    'AddMonitorDisabled'    => 'Your user is not allowed to add a new monitor',
    'AddNewServer'          => 'Add New Server',
    'AddNewStorage'         => 'Add New Storage',
    'AddNewUser'            => 'Add New User',
    'AddNewZone'            => 'Add New Zone',
    'AlarmBrFrames'         => 'Alarm<br/>Frames',
    'AlarmFrame'            => 'Alarm Frame',
    'AlarmFrameCount'       => 'Alarm Frame Count',
    'AlarmLimits'           => 'Alarm Limits',
    'AlarmMaximumFPS'       => 'Alarm Maximum FPS',
    'AlarmPx'               => 'Alarm Px',
    'AlarmRefImageBlendPct' => 'Alarm Reference Image Blend %ge',
    'AlarmRGBUnset'         => 'You must set an alarm RGB colour',
    'All'                   => 'All',
    'AllTokensRevoked'      => 'All Tokens Revoked',
    'AnalysisFPS'           => 'Analysis FPS',
    'AnalysisUpdateDelay'   => 'Analysis Update Delay',
    'APIEnabled'            => 'API Enabled',
    'ApplyingStateChange'   => 'Applying State Change',
    'ArchArchived'          => 'Archived Only',
    'ArchUnarchived'        => 'Unarchived Only',
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
    'Auth'                  => 'Authentication',
    'AutoStopTimeout'       => 'Auto Stop Timeout',
    'AvgBrScore'            => 'Avg.<br/>Score',
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
    'BadPassthrough'        => 'Recording -> Passthrough only works with ffmpeg type monitors.',
    'BadPath'               => 'Source -> Path must be set to a valid value',
    'BadPathNotEncoded'     => 'Source -> Path must be set to a valid value. We have detected invalid characters !*\'()$ ,#[] that may need to be url percent encoded.',
    'BadPort'               => 'Source -> Port must be set to a valid number',
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
    'BandwidthHead'         => 'Bandwidth',	// This is the end of the bandwidth status on the top of the console, different in many language due to phrasing
    'BlobPx'                => 'Blob Px',
    'BlobSizes'             => 'Blob Sizes',
    'CanAutoFocus'          => 'Can Auto Focus',
    'CanAutoGain'           => 'Can Auto Gain',
    'CanAutoIris'           => 'Can Auto Iris',
    'CanAutoWhite'          => 'Can Auto White Bal.',
    'CanAutoZoom'           => 'Can Auto Zoom',
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
    'CheckMethod'           => 'Alarm Check Method',
    'ChooseDetectedCamera'  => 'Choose Detected Camera',
    'ChooseDetectedProfile' => 'Choose Detected Profile',
    'ChooseFilter'          => 'Choose Filter',
    'ChooseLogFormat'       => 'Choose a log format',
    'ChooseLogSelection'    => 'Choose a log selection',
    'ChoosePreset'          => 'Choose Preset',
    'CloneMonitor'          => 'Clone',
    'ConcurrentFilter'      => 'Run filter concurrently',
    'ConfigOptions'         => 'ConfigOptions',
    'ConfigType'            => 'Config Type',
    'ConfiguredFor'         => 'Configured for',
    'ConfigURL'             => 'Config Base URL',
    'ConfirmAction'         => 'Action Confirmation',
    'ConfirmDeleteControl'  => 'Warning, deleting a control will reset all monitors that use it to be uncontrollable.<br><br>Are you sure you wish to delete?',
    'ConfirmDeleteDevices'  => 'Are you sure you wish to delete the selected devices?',
    'ConfirmDeleteEvents'   => 'Are you sure you wish to delete the selected events?',
    'ConfirmDeleteLayout'   => 'Are you sure you wish to delete current layout?',
    'ConfirmDeleteTitle'    => 'Delete Confirmation',
    'ConfirmPassword'       => 'Confirm Password',
    'ConfirmUnarchiveEvents'=> 'Are you sure you wish to unarchive the selected events?',
    'ConjAnd'               => 'and',
    'ConjOr'                => 'or',
    'ContactAdmin'          => 'Please contact your adminstrator for details.',
    'ControlAddress'        => 'Control Address',
    'ControlCap'            => 'Control Capability',
    'ControlCaps'           => 'Control Capabilities',
    'ControlDevice'         => 'Control Device',
    'Controllable'          => 'Controllable',
    'ControlType'           => 'Control Type',
    'CycleWatch'            => 'Cycle Watch',
    'DefaultRate'           => 'Default Rate',
    'DefaultScale'          => 'Default Scale',
    'DefaultCodec'          => 'Default Method For Event View',
    'DefaultView'           => 'Default View',
    'RTSPDescribe'          => 'Use RTSP Response Media URL',
    'DeleteAndNext'         => 'Delete &amp; Next',
    'DeleteAndPrev'         => 'Delete &amp; Prev',
    'DeleteSavedFilter'     => 'Delete saved filter',
    'DetectedCameras'       => 'Detected Cameras',
    'DetectedProfiles'      => 'Detected Profiles',
    'DeviceChannel'         => 'Device Channel',
    'DeviceFormat'          => 'Device Format',
    'DeviceNumber'          => 'Device Number',
    'DevicePath'            => 'Device Path',
    'DisableAlarms'         => 'Disable Alarms',
    'Dnsmasq'               => 'DHCP',
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
    'DontSave'              => 'Do not save',
    'DuplicateMonitorName'  => 'Duplicate Monitor Name',
    'DuplicateRTSPStreamName' =>  'Duplicate RTSP Stream Name',
    'EditControl'           => 'Edit Control',
    'EditLayout'            => 'Edit Layout',
    'EnableAlarms'          => 'Enable Alarms',
    'EnterNewFilterName'    => 'Enter new filter name',
    'ErrorBrackets'         => 'Error, please check you have an equal number of opening and closing brackets',
    'ErrorValidValue'       => 'Error, please check that all terms have a valid value',
    'Etc'                   => 'etc',
    'EventFilter'           => 'Event Filter',
    'EventId'               => 'Event Id',
    'EventName'             => 'Event Name',
    'EventPrefix'           => 'Event Prefix',
    'ExportCompress'        => 'Use Compression',
    'ExportDetails'         => 'Export Event Details',
    'ExportMatches'         => 'Export Matches',
    'Exif'                  => 'Embed EXIF data into image',
    'DownloadVideo'         => 'Download Video',
    'GenerateDownload'      => 'Generate Download',
    'EventsLoading'         => 'Events are loading',
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
    'FastForward'           => 'Fast Forward',
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
    'FilterUnset'           => 'You must specify a filter width and height',
    'FilterUploadEvents'    => 'Upload all matches',
    'FilterUser'            => 'User to run filter as',
    'FilterVideoEvents'     => 'Create video for all matches',
    'FlippedHori'           => 'Flipped Horizontally',
    'FlippedVert'           => 'Flipped Vertically',
    'ForceAlarm'            => 'Force Alarm',
    'FPS'                   => 'fps',
    'FPSReportInterval'     => 'FPS Report Interval',
    'FrameId'               => 'Frame Id',
    'FrameRate'             => 'Frame Rate',
    'FrameSkip'             => 'Frame Skip',
    'MotionFrameSkip'       => 'Motion Frame Skip',
    'GenerateVideo'         => 'Generate Video',
    'GeneratingVideo'       => 'Generating Video',
    'GetCurrentLocation'    => 'Get Current Location',
    'GoToZoneMinder'        => 'Go to ZoneMinder.com',
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
    'Highband'              => 'High&nbsp;B/W',
    'ImageBufferSize'       => 'Image Buffer Size (frames)',
    'MaxImageBufferCount'   => 'Maximum Image Buffer Size (frames)',
    'InvalidateTokens'      => 'Invalidate all generated tokens',
    'KeyString'             => 'Key String',
    'LimitResultsPost'      => 'results only', // This is used at the end of the phrase 'Limit to first N results only'
    'LimitResultsPre'       => 'Limit to first', // This is used at the beginning of the phrase 'Limit to first N results only'
    'LinkedMonitors'        => 'Linked Monitors',
    'ListMatches'           => 'List Matches',
    'LoggedInAs'            => 'Logged in as',
    'LoggingIn'             => 'Logging In',
    'Lowband'               => 'Low&nbsp;B/W',
    'Mail'                  => 'Email',
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
    'Medband'               => 'Medium&nbsp;B/W',
    'MessageSavingDataWhenLeavingPage' => 'You are leaving the page.<br>Want to save data?',
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
    'ModectDuringPTZ'       => 'Do motion detection during PTZ motion',
    'MonitorDataIsSaved'    => 'Monitor data is saved',
    'MonitorIds'            => 'Monitor&nbsp;Ids',
    'MonitorPresetIntro'    => 'Select an appropriate preset from the list below.<br/><br/>Please note that this may overwrite any values you already have configured for the current monitor.<br/><br/>',
    'MonitorPreset'         => 'Monitor Preset',
    'MonitorProbeIntro'     => 'The list below shows detected analog and network cameras and whether they are already being used or available for selection.<br/><br/>Select the desired entry from the list below.<br/><br/>Please note that not all cameras may be detected and that choosing a camera here may overwrite any values you already have configured for the current monitor.<br/><br/>',
    'MonitorProbe'          => 'Monitor Probe',
    'MontageReview'         => 'Montage Review',
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
    'NewGroup'              => 'New Group',
    'NewLabel'              => 'New Label',
    'NewPassword'           => 'New Password',
    'NewState'              => 'New State',
    'NewUser'               => 'New User',
    'NextMonitor'           => 'Next Monitor',
    'NoDetectedCameras'     => 'No Detected Cameras',
    'NoDetectedProfiles'    => 'No Detected Profiles',
    'NoFramesRecorded'      => 'There are no frames recorded for this event',
    'NoGroup'               => 'No Group',
    'NoneAvailable'         => 'None available',
    'NoSavedFilters'        => 'No Saved Filters',
    'NoStatisticsRecorded'  => 'There are no statistics recorded for this event/frame',
    'No Tag'                => 'No Tag',
    'NumPresets'            => 'Num Presets',
    'OnvifProbe'            => 'ONVIF',
    'OnvifProbeIntro'       => 'The list below shows detected ONVIF cameras and whether they are already being used or available for selection.<br/><br/>Select the desired entry from the list below.<br/><br/>Please note that not all cameras may be detected and that choosing a camera here may overwrite any values you already have configured for the current monitor.<br/><br/>',
    'OnvifCredentialsIntro' => 'Please supply user name and password for the selected camera.<br/>If no user has been created for the camera then the user given here will be created with the given password.<br/><br/>',
    'ONVIF'                 => 'ONVIF',
    'ONVIF_Alarm_Text'      => 'ONVIF Alarm Text',
    'ONVIF_Event_Listener'  => 'ONVIF Event Listener',
    'ONVIF_EVENTS_PATH'     => 'ONVIF Events Path',
    'ONVIF_Options'         => 'ONVIF Options',
    'ONVIF_URL'             => 'ONVIF URL',
    'OpBlank'               => 'is blank',
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
    'OverwriteExisting'     => 'Overwrite Existing',
    'PanLeft'               => 'Pan Left',
    'PanRight'              => 'Pan Right',
    'PanTilt'               => 'Pan/Tilt',
    'ParentGroup'           => 'Parent Group',
    'PasswordsDifferent'    => 'The new and confirm passwords are different',
    'Password'              => 'Password',
    'PathToIndex'           => 'Path To Index',
    'PathToZMS'             => 'Path To ZMS',
    'PathToApi'             => 'Path To Api',
    'PauseCycle'            => 'Pause Cycle',
    'PhoneBW'               => 'Phone&nbsp;B/W',
    'PixelDiff'             => 'Pixel Diff',
    'Pixels'                => 'pixels',
    'PlayAll'               => 'Play All',
    'PlayCycle'             => 'Play Cycle',
    'PlayerDisabledInMonitorSettings'  => 'The player is disabled in the monitor settings.',
    'PleaseWait'            => 'Please Wait',
    'PostEventImageBuffer'  => 'Post Event Image Count',
    'PreEventImageBuffer'   => 'Pre Event Image Count',
    'PreserveAspect'        => 'Preserve Aspect Ratio',
    'PreviousMonitor'       => 'Previous Monitor',
    'PrivacyAbout'          => 'About',
    'PrivacyAboutText'      => 'Since 2002, ZoneMinder has been the premier free and open-source Video Management System (VMS) solution for Linux platforms. ZoneMinder is supported by the community and is managed by those who choose to volunteer their spare time to the project. The best way to improve ZoneMinder is to get involved.',
    'PrivacyContact'        => 'Contact',
    'PrivacyContactText'    => 'Please contact us <a href="https://zoneminder.com/contact/">here</a> for any questions regarding our privacy policy or to have your information removed.<br><br>For support, there are three primary ways to engage with the community:<ul><li>The ZoneMinder <a href="https://forums.zoneminder.com/">user forum</a></li><li>The ZoneMinder <a href="https://zoneminder-chat.herokuapp.com/">Slack channel</a></li><li>The ZoneMinder <a href="https://github.com/ZoneMinder/zoneminder/issues">Github forum</a></li></ul><p>Our Github forum is only for bug reporting. Please use our user forum or slack channel for all other questions or comments.</p>',
    'PrivacyCookies'        => 'Cookies',
    'PrivacyCookiesText'    => 'Whether you use a web browser or a mobile app to communicate with the ZoneMinder server, a ZMSESSID cookie is created on the client to uniquely identify a session with the ZoneMinder server. ZmCSS and zmSkin cookies are created to remember your style and skin choices.',
    'PrivacyTelemetry'      => 'Telemetry',
    'PrivacyTelemetryText'  => 'Because ZoneMinder is open-source, anyone can install it without registering. This makes it difficult to  answer questions such as: how many systems are out there, what is the largest system out there, what kind of systems are out there, or where are these systems located? Knowing the answers to these questions, helps users who ask us these questions, and it helps us set priorities based on the majority user base.',
    'PrivacyTelemetryList'  => 'The ZoneMinder Telemetry daemon collects the following data about your system:
    <ul>
      <li>A unique identifier (UUID)</li>
      <li>City based location is gathered by querying <a href="https://ipinfo.io/geo">ipinfo.io</a>. City, region, country, latitude, and longitude parameters are saved. The latitude and longitude coordinates are accurate down to the city or town level only!</li>
      <li>Current time</li>
      <li>Total number of monitors</li>
      <li>Total number of events</li>
      <li>System architecture</li>
      <li>Operating system kernel, distro, and distro version</li>
      <li>Version of ZoneMinder</li>
      <li>Total amount of memory</li>
      <li>Number of cpu cores</li>
    </ul>',
    'PrivacyMonitorList'    => 'The following configuration parameters from each monitor are collected:
   <ul>
    <li>Id</li>
    <li>Name</li>
    <li>Manufacturer</li>
    <li>Model</li>
    <li>Type</li>
    <li>Function</li>
    <li>Width</li>
    <li>Height</li>
    <li>Colours</li>
    <li>MaxFPS</li>
    <li>AlarmMaxFPS</li>
   </ul>',
    'PrivacyConclusionText' => 'We are <u>NOT</u> collecting any image specific data from your cameras. We don’t know what your cameras are watching. This data will not be sold or used for any purpose not stated herein. By clicking accept, you agree to send us this data to help make ZoneMinder a better product. By clicking decline, you can still freely use ZoneMinder and all its features.',
    'Probe'                 => 'Probe',
    'ProfileProbe'          => 'Stream Probe',
    'ProfileProbeIntro'     => 'The list below shows the existing stream profiles of the selected camera .<br/><br/>Select the desired entry from the list below.<br/><br/>Please note that ZoneMinder cannot configure additional profiles and that choosing a camera here may overwrite any values you already have configured for the current monitor.<br/><br/>',
    'RecaptchaWarning'      => 'Your reCaptcha secret key is invalid. Please correct it, or reCaptcha will not work', // added Sep 24 2015 - PP
    'RecordAudio'		       	=> 'Whether to store the audio stream when saving an event.',
    'RefImageBlendPct'      => 'Reference Image Blend %ge',
    'RemoteHostName'        => 'Host Name',
    'RemoteHostPath'        => 'Path',
    'RemoteHostSubPath'     => 'SubPath',
    'RemoteHostPort'        => 'Port',
    'RemoteImageColours'    => 'Image Colours',
    'RemoteMethod'          => 'Method',
    'RemoteProtocol'        => 'Protocol',
    'ReplayAll'             => 'All Events',
    'ReplayGapless'         => 'Gapless Events',
    'ReplaySingle'          => 'Single Event',
    'ReportEventAudit'      => 'Audit Events Report',
    'ResetEventCounts'      => 'Reset Event Counts',
    'RestrictedCameraIds'   => 'Restricted Camera Ids',
    'RestrictedMonitors'    => 'Restricted Monitors',
    'ReturnDelay'           => 'Return Delay',
    'ReturnLocation'        => 'Return Location',
    'RevokeAllTokens'       => 'Revoke All Tokens',
    'RotateLeft'            => 'Rotate Left',
    'RotateRight'           => 'Rotate Right',
    'RTSPTransport'         => 'RTSP Transport Protocol',
    'RunAudit'              => 'Run Audit Process',
    'RunLocalUpdate'        => 'Please run zmupdate.pl to update',
    'RunMode'               => 'Run Mode',
    'RunState'              => 'Run State',
    'RunStats'              => 'Run Stats Process',
    'RunTrigger'            => 'Run Trigger Process',
    'RunEventNotification'  => 'Run Event Notification Process',
    'SaveAndClose'          => 'Save and close',
    'SaveAs'                => 'Save as',
    'SaveFilter'            => 'Save Filter',
    'SaveJPEGs'             => 'Save JPEGs',
    'Sectionlength'         => 'Section length',
    'SelectMonitors'        => 'Select Monitors',
    'SelectFormat'          => 'Select Format',
    'SelectLog'             => 'Select Log',
    'SelfIntersecting'      => 'Polygon edges must not intersect',
    'SetNewBandwidth'       => 'Set New Bandwidth',
    'SetPreset'             => 'Set Preset',
    'ShowFilterWindow'      => 'Show Filter Window',
    'ShowTimeline'          => 'Show Timeline',
    'SignalCheckColour'     => 'Signal Check Colour',
    'SignalCheckPoints'     => 'Signal Check Points',
    'SkinDescription'       => 'Change the skin for this session',
    'CSSDescription'        => 'Change the css for this session',
    'SortAsc'               => 'Asc',
    'SortBy'                => 'Sort by',
    'SortDesc'              => 'Desc',
    'SourceColours'         => 'Source Colours',
    'SourcePath'            => 'Source Path',
    'SourceType'            => 'Source Type',
    'SOAP WSA COMPLIANCE'   => 'SOAP WSA Compliance',
    'SpeedHigh'             => 'High Speed',
    'SpeedLow'              => 'Low Speed',
    'SpeedMedium'           => 'Medium Speed',
    'SpeedTurbo'            => 'Turbo Speed',
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
    'StorageArea'           => 'Storage Area',
    'StorageDoDelete'       => 'Do Deletes',
    'StorageScheme'         => 'Scheme',
    'StreamReplayBuffer'    => 'Stream Replay Image Buffer',
    'TargetColorspace'      => 'Target colorspace',
    'TimeDelta'             => 'Time Delta',
    'TimelineTip1'          => 'Pass your mouse over the graph to view a snapshot image and event details.',              // Added 2013.08.15.
    'TimelineTip2'          => 'Click on the coloured sections of the graph, or the image, to view the event.',              // Added 2013.08.15.
    'TimelineTip3'          => 'Click on the background to zoom in to a smaller time period based around your click.',              // Added 2013.08.15.
    'TimelineTip4'          => 'Use the controls below to zoom out or navigate back and forward through the time range.',              // Added 2013.08.15.
    'TimestampLabelFormat'  => 'Timestamp Label Format',
    'TimestampLabelX'       => 'Timestamp Label X',
    'TimestampLabelY'       => 'Timestamp Label Y',
    'TimestampLabelSize'    => 'Font Size',
    'TimeStamp'             => 'Time Stamp',
    'TooManyEventsForTimeline' => 'Too many events for Timeline. Reduce the number of monitors or reduce the visible range of the Timeline',
    'TotalBrScore'          => 'Total<br/>Score',
    'TrackDelay'            => 'Track Delay',
    'TrackMotion'           => 'Track Motion',
    'TurboPanSpeed'         => 'Turbo Pan Speed',
    'TurboTiltSpeed'        => 'Turbo Tilt Speed',
    'TZUnset'               => 'Unset - use value in php.ini',
    'UpdateAvailable'       => 'An update to ZoneMinder is available.',
    'UpdateNotNecessary'    => 'No update is necessary.',
    'UsedPlugins'	          => 'Used Plugins',
    'Username'              => 'Username',
    'UseFilterExprsPost'    => '&nbsp;filter&nbsp;expressions', // This is used at the end of the phrase 'use N filter expressions'
    'UseFilterExprsPre'     => 'Use&nbsp;', // This is used at the beginning of the phrase 'use N filter expressions'
    'UseFilter'             => 'Use Filter',
    'VersionIgnore'         => 'Ignore this version',
    'VersionRemindDay'      => 'Remind again in 1 day',
    'VersionRemindHour'     => 'Remind again in 1 hour',
    'VersionRemindNever'    => 'Don\'t remind about new versions',
    'VersionRemindWeek'     => 'Remind again in 1 week',
    'VersionRemindMonth'    => 'Remind again in 1 month',
    'ViewMatches'           => 'View Matches',
    'VideoFormat'           => 'Video Format',
    'VideoGenFailed'        => 'Video Generation Failed!',
    'VideoGenFiles'         => 'Existing Video Files',
    'VideoGenNoFiles'       => 'No Video Files Found',
    'VideoGenParms'         => 'Video Generation Parameters',
    'VideoGenSucceeded'     => 'Video Generation Succeeded!',
    'VideoSize'             => 'Video Size',
    'VideoWriter'           => 'Video Writer',
    'ViewAll'               => 'View All',
    'ViewEvent'             => 'View Event',
    'ViewPaged'             => 'View Paged',
    'V4LCapturesPerFrame'  	=> 'Captures Per Frame',
    'V4LMultiBuffer'		    => 'Multi Buffering',
    'WarmupFrames'          => 'Warmup Frames',
    'WebColour'             => 'Web Colour',
    'WebSiteUrl'            => 'Website URL',
    'WhiteBalance'          => 'White Balance',
    'X10ActivationString'   => 'X10 Activation String',
    'X10InputAlarmString'   => 'X10 Input Alarm String',
    'X10OutputAlarmString'  => 'X10 Output Alarm String',
    'YouNoPerms'            => 'You do not have permissions to access this resource.',
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
    'ZoomIn'                => 'Zoom In',
    'ZoomOut'               => 'Zoom Out',
// language names translation
    'es_la' => 'Spanish Latam',
    'es_CR' => 'Spanish Costa Rica',
    'es_ar' => 'Spanish Argentina',
    'es_es' => 'Spanish Spain',
    'en_gb' => 'British English',
    'en_us' => 'Us English',
    'fr_fr' => 'French',
    'cs_cz' => 'Czech',
    'zh_cn' => 'Simplified Chinese',
    'zh_tw' => 'Traditional Chinese',
    'de_de' => 'German',
    'it_it' => 'Italian',
    'ja_jp' => 'Japanese',
    'hu_hu' => 'Hungarian',
    'pl_pl' => 'Polish',
    'pt_br' => 'Portuguese Brazil',
    'ru_ru' => 'Russian',
    'nl_nl' => 'Dutch',
    'se_se' => 'Sami',
    'et_ee' => 'Estonian',
    'he_il' => 'Hebrew',
    'dk_dk' => 'Danish',
    'ro_ro' => 'Romanian',
    'no_nb' => 'Norwegian',
  'option:router' => 'Gateway',
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
function zmVlang($langVarArray, $count) {
  krsort($langVarArray);
  foreach ($langVarArray as $key=>$value) {
    if (abs($count) >= $key) {
      return $value;
    }
  }
  ZM\Error('Unable to correlate variable language string');
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
  'OPTIONS_ENCODERHWACCELNAME' => array(
    'Help' => '
    This is equivalent to the ffmpeg -hwaccel command line option.  With intel graphics support, use "vaapi".  For NVIDIA cuda support use "cuda". To check for support, run ffmpeg -hwaccels on the command line.'
    ),
  'OPTIONS_ENCODERHWACCELDEVICE' => array(
    'Help' => '
    This is equivalent to the ffmpeg -hwaccel_device command line option.  You should only have to specify this if you have multiple GPUs.  A typical value for Intel VAAPI would be /dev/dri/renderD128.'
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
  'OPTIONS_CAPTURING' => array(
    'Help' => 'When to do capturing:~~~~
None: Do not run a process, do not do capturing.  Equivalent to the old Function == None~~~~
Ondemand: A zmc process will run, but will wait for a viewer (live view, thumbnail or rstp server connection) before connecting to the camera.~~~~
Always: A zmc process will run and immediately connect and stay connected.~~~~
',
  ),
  'OPTIONS_RTSPSERVER' => array(
    'Help' => '
     ZM supplies its own RTSP server that can re-stream RTSP or attempt to convert the
     monitor stream into RTSP. This is useful if you want to use the ZM Host machines
     resources instead of having multiple clients pulling from a single camera.~~~~
     NOTE:~~
     Options > Network > MIN_RTSP_PORT is configurable.
     ',
    ),
  'OPTIONS_RTSPSTREAMNAME' => array(
     'Help' => '
     If RTSPServer is enabled, this will be the endpoint it will be available at.
     For example, if this is monitor ID 6, MIN_RTSP_PORT=20000 and RTSPServerName
     is set to "my_camera", access the stream at rtsp://ZM_HOST:20006/my_camera
     ',
    ),
  'FUNCTION_ANALYSIS_ENABLED' => array(
    'Help' => '
      When to perform motion detection on the captured video.  
      This setting sets the default state when the process starts up.
      It can then be turned on/off through external triggers zmtrigger zmu or the web ui.
      When not enabled no motion detection or linked monitor checking will be performed and 
      no events will be created.
      '
  ),
  'FUNCTION_DECODING' => array(
    'Help' => '
      When not performing motion detection and using H264Passthrough with no jpegs being saved, we can
      optionally choose to not decode the H264/H265 packets.  This will drastically reduce cpu use.~~~~
Always: every frame will be decoded, live view and thumbnails will be available.~~~~
OnDemand: only do decoding when someone is watching.~~~~
KeyFrames: Only keyframes will be decoded, so viewing frame rate will be very low, depending on the keyframe interval set in the camera.~~~~
None: No frames will be decoded, live view and thumbnails will not be available~~~~
'
  ),
  'FUNCTION_RTSP2WEB_ENABLED' => array(
    'Help' => '
      Attempt to use RTSP2Web streaming server for h264/h265 live view. Experimental, but allows
      for significantly better performance.'
  ),
  'FUNCTION_RTSP2WEB_TYPE' => array(
    'Help' => '
      RTSP2Web supports MSE (Media Source Extensions), HLS (HTTP Live Streaming), and WebRTC.
      Each has its advantages, with WebRTC probably being the most performant, but also the most picky about codecs.'
  ),
  'FUNCTION_JANUS_ENABLED' => array(
    'Help' => '
      Attempt to use Janus streaming server for h264/h265 live view. Experimental, but allows
      for significantly better performance.'
  ),
  'FUNCTION_JANUS_AUDIO_ENABLED' => array(
    'Help' => '
      Attempt to enable audio in the Janus stream. Has no effect for cameras without audio support,
      but can prevent a stream playing if your camera sends an audio format unsupported by the browser.'
  ),
  'FUNCTION_JANUS_PROFILE_OVERRIDE' => array(
    'Help' => '
      Manually set a Profile-ID, which can force a browser to try to play a given stream. Try "42e01f"
      for a universally supported value, or leave this blank to use the Profile-ID specified by the source.'
  ),
  'FUNCTION_JANUS_USE_RTSP_RESTREAM' => array(
    'Help' => '
      If your camera will not work under Janus with any other options, enable this to use the ZoneMinder
      RTSP restream as the Janus source.'
  ),
  'FUNCTION_JANUS_RTSP_SESSION_TIMEOUT' => array(
    'Help' => '
    Override or set a timeout period in seconds for the RTSP session. Useful if you see a lot of 401
    Unauthorized responses in janus logs. Set to 0 to use the timeout (if sent) from the source.'
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
// Help for soap_wsa issue with chinesse cameras
   'OPTIONS_SOAP_wsa' => array(
    'Help' => '
     Disable it if you receive an error ~~~ Couldnt do Renew Error 12 ActionNotSupported
     <env:Text>The device do NOT support this feature</env:Text> ~~~ when trying to enable/use ONVIF ~~it may
     help to get it to work... it is confirmed to work in some chinese cameras that do not implement ONVIF entirely
    '
   ),

//    'LANG_DEFAULT' => array(
//        'Prompt' => "This is a new prompt for this option",
//        'Help' => "This is some new help for this option which will be displayed in the window when the ? is clicked"
//    ),
);

?>
