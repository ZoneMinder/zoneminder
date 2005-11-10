<?php
//
// ZoneMinder web UK English language file, $Date$, $Revision$
// Copyright (C) 2003, 2004, 2005  Philip Coombes
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
$zmSlang24BitColour          = '24 bit colour';
$zmSlang8BitGrey             = '8 bit greyscale';
$zmSlangAction               = 'Action';
$zmSlangActual               = 'Actual';
$zmSlangAddNewControl        = 'Add New Control';
$zmSlangAddNewMonitor        = 'Add New Monitor';
$zmSlangAddNewUser           = 'Add New User';
$zmSlangAddNewZone           = 'Add New Zone';
$zmSlangAlarm                = 'Alarm';
$zmSlangAlarmBrFrames        = 'Alarm<br/>Frames';
$zmSlangAlarmFrame           = 'Alarm Frame';
$zmSlangAlarmFrameCount      = 'Alarm Frame Count';
$zmSlangAlarmLimits          = 'Alarm Limits';
$zmSlangAlarmPx              = 'Alarm Px';
$zmSlangAlert                = 'Alert';
$zmSlangAll                  = 'All';
$zmSlangApply                = 'Apply';
$zmSlangApplyingStateChange  = 'Applying State Change';
$zmSlangArchArchived         = 'Archived Only';
$zmSlangArchive              = 'Archive';
$zmSlangArchived             = 'Archived';
$zmSlangArchUnarchived       = 'Unarchived Only';
$zmSlangAttrAlarmFrames      = 'Alarm Frames';
$zmSlangAttrArchiveStatus    = 'Archive Status';
$zmSlangAttrAvgScore         = 'Avg. Score';
$zmSlangAttrCause            = 'Cause';
$zmSlangAttrDate             = 'Date';
$zmSlangAttrDateTime         = 'Date/Time';
$zmSlangAttrDiskBlocks       = 'Disk Blocks';
$zmSlangAttrDiskPercent      = 'Disk Percent';
$zmSlangAttrDuration         = 'Duration';
$zmSlangAttrFrames           = 'Frames';
$zmSlangAttrId               = 'Id';
$zmSlangAttrMaxScore         = 'Max. Score';
$zmSlangAttrMonitorId        = 'Monitor Id';
$zmSlangAttrMonitorName      = 'Monitor Name';
$zmSlangAttrName             = 'Name';
$zmSlangAttrTime             = 'Time';
$zmSlangAttrTotalScore       = 'Total Score';
$zmSlangAttrWeekday          = 'Weekday';
$zmSlangAutoArchiveEvents    = 'Automatically archive all matches';
$zmSlangAuto                 = 'Auto';
$zmSlangAutoDeleteEvents     = 'Automatically delete all matches';
$zmSlangAutoEmailEvents      = 'Automatically email details of all matches';
$zmSlangAutoExecuteEvents    = 'Automatically execute command on all matches';
$zmSlangAutoMessageEvents    = 'Automatically message details of all matches';
$zmSlangAutoStopTimeout      = 'Auto Stop Timeout';
$zmSlangAutoUploadEvents     = 'Automatically upload all matches';
$zmSlangAvgBrScore           = 'Avg.<br/>Score';
$zmSlangBadNameChars         = 'Names may only contain alphanumeric characters plus hyphen and underscore';
$zmSlangBandwidth            = 'Bandwidth';
$zmSlangBlobPx               = 'Blob Px';
$zmSlangBlobs                = 'Blobs';
$zmSlangBlobSizes            = 'Blob Sizes';
$zmSlangBrightness           = 'Brightness';
$zmSlangBuffers              = 'Buffers';
$zmSlangCanAutoFocus         = 'Can Auto Focus';
$zmSlangCanAutoGain          = 'Can Auto Gain';
$zmSlangCanAutoIris          = 'Can Auto Iris';
$zmSlangCanAutoWhite         = 'Can Auto White Bal.';
$zmSlangCanAutoZoom          = 'Can Auto Zoom';
$zmSlangCancel               = 'Cancel';
$zmSlangCancelForcedAlarm    = 'Cancel&nbsp;Forced&nbsp;Alarm';
$zmSlangCanFocusAbs          = 'Can Focus Absolute';
$zmSlangCanFocus             = 'Can Focus';
$zmSlangCanFocusCon          = 'Can Focus Continuous';
$zmSlangCanFocusRel          = 'Can Focus Relative';
$zmSlangCanGainAbs           = 'Can Gain Absolute';
$zmSlangCanGain              = 'Can Gain ';
$zmSlangCanGainCon           = 'Can Gain Continuous';
$zmSlangCanGainRel           = 'Can Gain Relative';
$zmSlangCanIrisAbs           = 'Can Iris Absolute';
$zmSlangCanIris              = 'Can Iris';
$zmSlangCanIrisCon           = 'Can Iris Continuous';
$zmSlangCanIrisRel           = 'Can Iris Relative';
$zmSlangCanMoveAbs           = 'Can Move Absolute';
$zmSlangCanMove              = 'Can Move';
$zmSlangCanMoveCon           = 'Can Move Continuous';
$zmSlangCanMoveDiag          = 'Can Move Diagonally';
$zmSlangCanMoveMap           = 'Can Move Mapped';
$zmSlangCanMoveRel           = 'Can Move Relative';
$zmSlangCanPan               = 'Can Pan' ;
$zmSlangCanReset             = 'Can Reset';
$zmSlangCanSetPresets        = 'Can Set Presets';
$zmSlangCanSleep             = 'Can Sleep';
$zmSlangCanTilt              = 'Can Tilt';
$zmSlangCanWake              = 'Can Wake';
$zmSlangCanWhiteAbs          = 'Can White Bal. Absolute';
$zmSlangCanWhiteBal          = 'Can White Bal.';
$zmSlangCanWhite             = 'Can White Balance';
$zmSlangCanWhiteCon          = 'Can White Bal. Continuous';
$zmSlangCanWhiteRel          = 'Can White Bal. Relative';
$zmSlangCanZoomAbs           = 'Can Zoom Absolute';
$zmSlangCanZoom              = 'Can Zoom';
$zmSlangCanZoomCon           = 'Can Zoom Continuous';
$zmSlangCanZoomRel           = 'Can Zoom Relative';
$zmSlangCaptureHeight        = 'Capture Height';
$zmSlangCapturePalette       = 'Capture Palette';
$zmSlangCaptureWidth         = 'Capture Width';
$zmSlangCause                = 'Cause';
$zmSlangCheckAll             = 'Check All';
$zmSlangCheckMethod          = 'Alarm Check Method';
$zmSlangChooseFilter         = 'Choose Filter';
$zmSlangClose                = 'Close';
$zmSlangColour               = 'Colour';
$zmSlangCommand              = 'Command';
$zmSlangConfig               = 'Config';
$zmSlangConfiguredFor        = 'Configured for';
$zmSlangConfirmPassword      = 'Confirm Password';
$zmSlangConjAnd              = 'and';
$zmSlangConjOr               = 'or';
$zmSlangConsole              = 'Console';
$zmSlangContactAdmin         = 'Please contact your adminstrator for details.';
$zmSlangContinue             = 'Continue';
$zmSlangContrast             = 'Contrast';
$zmSlangControlAddress       = 'Control Address';
$zmSlangControlCap           = 'Control Capability';
$zmSlangControlCaps          = 'Control Capabilities';
$zmSlangControl              = 'Control';
$zmSlangControlDevice        = 'Control Device';
$zmSlangControllable         = 'Controllable';
$zmSlangControlType          = 'Control Type';
$zmSlangCycle                = 'Cycle';
$zmSlangCycleWatch           = 'Cycle Watch';
$zmSlangDay                  = 'Day';
$zmSlangDefaultScale         = 'Default Scale';
$zmSlangDeleteAndNext        = 'Delete &amp; Next';
$zmSlangDeleteAndPrev        = 'Delete &amp; Prev';
$zmSlangDelete               = 'Delete';
$zmSlangDeleteSavedFilter    = 'Delete saved filter';
$zmSlangDescription          = 'Description';
$zmSlangDeviceChannel        = 'Device Channel';
$zmSlangDeviceFormat         = 'Device Format (0=PAL,1=NTSC etc)';
$zmSlangDeviceNumber         = 'Device Number (/dev/video?)';
$zmSlangDevicePath           = 'Device Path';
$zmSlangDimensions           = 'Dimensions';
$zmSlangDisableAlarms        = 'Disable Alarms';
$zmSlangDisk                 = 'Disk';
$zmSlangDonateAlready        = 'No, I\'ve already donated';
$zmSlangDonateEnticement     = 'You\'ve been running ZoneMinder for a while now and hopefully are finding it a useful addition to your home or workplace security. Although ZoneMinder is, and will remain, free and open source, it costs money to develop and support. If you would like to help support future development and new features then please consider donating. Donating is, of course, optional but very much appreciated and you can donate as much or as little as you like.<br><br>If you would like to donate please select the option below or go to http://www.zoneminder.com/donate.html in your browser.<br><br>Thank you for using ZoneMinder and don\'t forget to visit the forums on ZoneMinder.com for support or suggestions about how to make your ZoneMinder experience even better.';
$zmSlangDonate               = 'Please Donate';
$zmSlangDonateRemindDay      = 'Not yet, remind again in 1 day';
$zmSlangDonateRemindHour     = 'Not yet, remind again in 1 hour';
$zmSlangDonateRemindMonth    = 'Not yet, remind again in 1 month';
$zmSlangDonateRemindNever    = 'No, I don\'t want to donate, never remind';
$zmSlangDonateRemindWeek     = 'Not yet, remind again in 1 week';
$zmSlangDonateYes            = 'Yes, I\'d like to donate now';
$zmSlangDownload             = 'Download';
$zmSlangDuration             = 'Duration';
$zmSlangEdit                 = 'Edit';
$zmSlangEmail                = 'Email';
$zmSlangEnableAlarms         = 'Enable Alarms';
$zmSlangEnabled              = 'Enabled';
$zmSlangEnterNewFilterName   = 'Enter new filter name';
$zmSlangErrorBrackets        = 'Error, please check you have an equal number of opening and closing brackets';
$zmSlangError                = 'Error';
$zmSlangErrorValidValue      = 'Error, please check that all terms have a valid value';
$zmSlangEtc                  = 'etc';
$zmSlangEvent                = 'Event';
$zmSlangEventFilter          = 'Event Filter';
$zmSlangEventId              = 'Event Id';
$zmSlangEventName            = 'Event Name';
$zmSlangEventPrefix          = 'Event Prefix';
$zmSlangEvents               = 'Events';
$zmSlangExclude              = 'Exclude';
$zmSlangExportDetails        = 'Export Event Details';
$zmSlangExport               = 'Export';
$zmSlangExportFailed         = 'Export Failed';
$zmSlangExportFormat         = 'Export File Format';
$zmSlangExportFormatTar      = 'Tar';
$zmSlangExportFormatZip      = 'Zip';
$zmSlangExportFrames         = 'Export Frame Details';
$zmSlangExportImageFiles     = 'Export Image Files';
$zmSlangExporting            = 'Exporting';
$zmSlangExportMiscFiles      = 'Export Other Files (if present)';
$zmSlangExportOptions        = 'Export Options';
$zmSlangExportVideoFiles     = 'Export Video Files (if present)';
$zmSlangFar                  = 'Far';
$zmSlangFeed                 = 'Feed';
$zmSlangFileColours          = 'File Colours';
$zmSlangFile                 = 'File';
$zmSlangFilePath             = 'File Path';
$zmSlangFilterPx             = 'Filter Px';
$zmSlangFilters              = 'Filters';
$zmSlangFirst                = 'First';
$zmSlangFlippedHori          = 'Flipped Horizontally';
$zmSlangFlippedVert          = 'Flipped Vertically';
$zmSlangFocus                = 'Focus';
$zmSlangForceAlarm           = 'Force&nbsp;Alarm';
$zmSlangFormat               = 'Format';
$zmSlangFPS                  = 'fps';
$zmSlangFPSReportInterval    = 'FPS Report Interval';
$zmSlangFrame                = 'Frame';
$zmSlangFrameId              = 'Frame Id';
$zmSlangFrameRate            = 'Frame Rate';
$zmSlangFrames               = 'Frames';
$zmSlangFrameSkip            = 'Frame Skip';
$zmSlangFTP                  = 'FTP';
$zmSlangFunc                 = 'Func';
$zmSlangFunction             = 'Function';
$zmSlangGain                 = 'Gain';
$zmSlangGenerateVideo        = 'Generate Video';
$zmSlangGeneratingVideo      = 'Generating Video';
$zmSlangGoToZoneMinder       = 'Go to ZoneMinder.com';
$zmSlangGrey                 = 'Grey';
$zmSlangGroups               = 'Groups';
$zmSlangHasFocusSpeed        = 'Has Focus Speed';
$zmSlangHasGainSpeed         = 'Has Gain Speed';
$zmSlangHasHomePreset        = 'Has Home Preset';
$zmSlangHasIrisSpeed         = 'Has Iris Speed';
$zmSlangHasPanSpeed          = 'Has Pan Speed';
$zmSlangHasPresets           = 'Has Presets';
$zmSlangHasTiltSpeed         = 'Has Tilt Speed';
$zmSlangHasTurboPan          = 'Has Turbo Pan';
$zmSlangHasTurboTilt         = 'Has Turbo Tilt';
$zmSlangHasWhiteSpeed        = 'Has White Bal. Speed';
$zmSlangHasZoomSpeed         = 'Has Zoom Speed';
$zmSlangHighBW               = 'High&nbsp;B/W';
$zmSlangHigh                 = 'High';
$zmSlangHome                 = 'Home';
$zmSlangHour                 = 'Hour';
$zmSlangHue                  = 'Hue';
$zmSlangId                   = 'Id';
$zmSlangIdle                 = 'Idle';
$zmSlangIgnore               = 'Ignore';
$zmSlangImageBufferSize      = 'Image Buffer Size (frames)';
$zmSlangImage                = 'Image';
$zmSlangInclude              = 'Include';
$zmSlangIn                   = 'In';
$zmSlangInverted             = 'Inverted';
$zmSlangIris                 = 'Iris';
$zmSlangLanguage             = 'Language';
$zmSlangLast                 = 'Last';
$zmSlangLimitResultsPost     = 'results only;'; // This is used at the end of the phrase 'Limit to first N results only'
$zmSlangLimitResultsPre      = 'Limit to first'; // This is used at the beginning of the phrase 'Limit to first N results only'
$zmSlangList                 = 'List';
$zmSlangLoad                 = 'Load';
$zmSlangLocal                = 'Local';
$zmSlangLoggedInAs           = 'Logged In As';
$zmSlangLoggingIn            = 'Logging In';
$zmSlangLogin                = 'Login';
$zmSlangLogout               = 'Logout';
$zmSlangLowBW                = 'Low&nbsp;B/W';
$zmSlangLow                  = 'Low';
$zmSlangMain                 = 'Main';
$zmSlangMan                  = 'Man';
$zmSlangManual               = 'Manual';
$zmSlangMark                 = 'Mark';
$zmSlangMaxBandwidth         = 'Max Bandwidth';
$zmSlangMaxBrScore           = 'Max.<br/>Score';
$zmSlangMaxFocusRange        = 'Max Focus Range';
$zmSlangMaxFocusSpeed        = 'Max Focus Speed';
$zmSlangMaxFocusStep         = 'Max Focus Step';
$zmSlangMaxGainRange         = 'Max Gain Range';
$zmSlangMaxGainSpeed         = 'Max Gain Speed';
$zmSlangMaxGainStep          = 'Max Gain Step';
$zmSlangMaximumFPS           = 'Maximum FPS';
$zmSlangMaxIrisRange         = 'Max Iris Range';
$zmSlangMaxIrisSpeed         = 'Max Iris Speed';
$zmSlangMaxIrisStep          = 'Max Iris Step';
$zmSlangMax                  = 'Max';
$zmSlangMaxPanRange          = 'Max Pan Range';
$zmSlangMaxPanSpeed          = 'Max Pan Speed';
$zmSlangMaxPanStep           = 'Max Pan Step';
$zmSlangMaxTiltRange         = 'Max Tilt Range';
$zmSlangMaxTiltSpeed         = 'Max Tilt Speed';
$zmSlangMaxTiltStep          = 'Max Tilt Step';
$zmSlangMaxWhiteRange        = 'Max White Bal. Range';
$zmSlangMaxWhiteSpeed        = 'Max White Bal. Speed';
$zmSlangMaxWhiteStep         = 'Max White Bal. Step';
$zmSlangMaxZoomRange         = 'Max Zoom Range';
$zmSlangMaxZoomSpeed         = 'Max Zoom Speed';
$zmSlangMaxZoomStep          = 'Max Zoom Step';
$zmSlangMediumBW             = 'Medium&nbsp;B/W';
$zmSlangMedium               = 'Medium';
$zmSlangMinAlarmGeMinBlob    = 'Minimum alarm pixels should be greater than or equal to minimum blob pixels';
$zmSlangMinAlarmGeMinFilter  = 'Minimum alarm pixels should be greater than or equal to minimum filter pixels';
$zmSlangMinAlarmPixelsLtMax  = 'Minimum alarm pixels should be less than maximum alarm pixels';
$zmSlangMinBlobAreaLtMax     = 'Minimum blob area should be less than maximum blob area';
$zmSlangMinBlobsLtMax        = 'Minimum blobs should be less than maximum blobs';
$zmSlangMinFilterPixelsLtMax = 'Minimum filter pixels should be less than maximum filter pixels';
$zmSlangMinFocusRange        = 'Min Focus Range';
$zmSlangMinFocusSpeed        = 'Min Focus Speed';
$zmSlangMinFocusStep         = 'Min Focus Step';
$zmSlangMinGainRange         = 'Min Gain Range';
$zmSlangMinGainSpeed         = 'Min Gain Speed';
$zmSlangMinGainStep          = 'Min Gain Step';
$zmSlangMinIrisRange         = 'Min Iris Range';
$zmSlangMinIrisSpeed         = 'Min Iris Speed';
$zmSlangMinIrisStep          = 'Min Iris Step';
$zmSlangMinPanRange          = 'Min Pan Range';
$zmSlangMinPanSpeed          = 'Min Pan Speed';
$zmSlangMinPanStep           = 'Min Pan Step';
$zmSlangMinPixelThresLtMax   = 'Minimum pixel threshold should be less than maximum pixel threshold';
$zmSlangMinTiltRange         = 'Min Tilt Range';
$zmSlangMinTiltSpeed         = 'Min Tilt Speed';
$zmSlangMinTiltStep          = 'Min Tilt Step';
$zmSlangMinWhiteRange        = 'Min White Bal. Range';
$zmSlangMinWhiteSpeed        = 'Min White Bal. Speed';
$zmSlangMinWhiteStep         = 'Min White Bal. Step';
$zmSlangMinZoomRange         = 'Min Zoom Range';
$zmSlangMinZoomSpeed         = 'Min Zoom Speed';
$zmSlangMinZoomStep          = 'Min Zoom Step';
$zmSlangMisc                 = 'Misc';
$zmSlangMonitorIds           = 'Monitor&nbsp;Ids';
$zmSlangMonitor              = 'Monitor';
$zmSlangMonitors             = 'Monitors';
$zmSlangMontage              = 'Montage';
$zmSlangMonth                = 'Month';
$zmSlangMove                 = 'Move';
$zmSlangMustBeGe             = 'must be greater than or equal to';
$zmSlangMustBeLe             = 'must be less than or equal to';
$zmSlangMustConfirmPassword  = 'You must confirm the password';
$zmSlangMustSupplyPassword   = 'You must supply a password';
$zmSlangMustSupplyUsername   = 'You must supply a username';
$zmSlangName                 = 'Name';
$zmSlangNear                 = 'Near';
$zmSlangNetwork              = 'Network';
$zmSlangNewGroup             = 'New Group';
$zmSlangNew                  = 'New';
$zmSlangNewPassword          = 'New Password';
$zmSlangNewState             = 'New State';
$zmSlangNewUser              = 'New User';
$zmSlangNext                 = 'Next';
$zmSlangNoFramesRecorded     = 'There are no frames recorded for this event';
$zmSlangNoGroups             = 'No groups have been defined';
$zmSlangNoneAvailable        = 'None available';
$zmSlangNone                 = 'None';
$zmSlangNo                   = 'No';
$zmSlangNormal               = 'Normal';
$zmSlangNoSavedFilters       = 'NoSavedFilters';
$zmSlangNoStatisticsRecorded = 'There are no statistics recorded for this event/frame';
$zmSlangNotes                = 'Notes';
$zmSlangNumPresets           = 'Num Presets';
$zmSlangOpen                 = 'Open';
$zmSlangOpEq                 = 'equal to';
$zmSlangOpGtEq               = 'greater than or equal to';
$zmSlangOpGt                 = 'greater than';
$zmSlangOpIn                 = 'in set';
$zmSlangOpLtEq               = 'less than or equal to';
$zmSlangOpLt                 = 'less than';
$zmSlangOpMatches            = 'matches';
$zmSlangOpNe                 = 'not equal to';
$zmSlangOpNotIn              = 'not in set';
$zmSlangOpNotMatches         = 'does not match';
$zmSlangOptionHelp           = 'OptionHelp';
$zmSlangOptionRestartWarning = 'These changes may not come into effect fully\nwhile the system is running. When you have\nfinished making your changes please ensure that\nyou restart ZoneMinder.';
$zmSlangOptions              = 'Options';
$zmSlangOrder                = 'Order';
$zmSlangOrEnterNewName       = 'or enter new name';
$zmSlangOrientation          = 'Orientation';
$zmSlangOut                  = 'Out';
$zmSlangOverwriteExisting    = 'Overwrite Existing';
$zmSlangPaged                = 'Paged';
$zmSlangPanLeft              = 'Pan Left';
$zmSlangPan                  = 'Pan';
$zmSlangPanRight             = 'Pan Right';
$zmSlangPanTilt              = 'Pan/Tilt';
$zmSlangParameter            = 'Parameter';
$zmSlangPassword             = 'Password';
$zmSlangPasswordsDifferent   = 'The new and confirm passwords are different';
$zmSlangPaths                = 'Paths';
$zmSlangPhoneBW              = 'Phone&nbsp;B/W';
$zmSlangPhone                = 'Phone';
$zmSlangPixels               = 'pixels';
$zmSlangPlayAll              = 'Play All';
$zmSlangPleaseWait           = 'Please Wait';
$zmSlangPostEventImageBuffer = 'Post Event Image Buffer';
$zmSlangPreEventImageBuffer  = 'Pre Event Image Buffer';
$zmSlangPreset               = 'Preset';
$zmSlangPresets              = 'Presets';
$zmSlangPrev                 = 'Prev';
$zmSlangRate                 = 'Rate';
$zmSlangReal                 = 'Real';
$zmSlangRecord               = 'Record';
$zmSlangRefImageBlendPct     = 'Reference Image Blend %ge';
$zmSlangRefresh              = 'Refresh';
$zmSlangRemoteHostName       = 'Remote Host Name';
$zmSlangRemoteHostPath       = 'Remote Host Path';
$zmSlangRemoteHostPort       = 'Remote Host Port';
$zmSlangRemoteImageColours   = 'Remote Image Colours';
$zmSlangRemote               = 'Remote';
$zmSlangRename               = 'Rename';
$zmSlangReplay               = 'Replay';
$zmSlangResetEventCounts     = 'Reset Event Counts';
$zmSlangReset                = 'Reset';
$zmSlangRestarting           = 'Restarting';
$zmSlangRestart              = 'Restart';
$zmSlangRestrictedCameraIds  = 'Restricted Camera Ids';
$zmSlangReturnDelay          = 'Return Delay';
$zmSlangReturnLocation       = 'Return Location';
$zmSlangRotateLeft           = 'Rotate Left';
$zmSlangRotateRight          = 'Rotate Right';
$zmSlangRunMode              = 'Run Mode';
$zmSlangRunning              = 'Running';
$zmSlangRunState             = 'Run State';
$zmSlangSaveAs               = 'Save as';
$zmSlangSaveFilter           = 'Save Filter';
$zmSlangSave                 = 'Save';
$zmSlangScale                = 'Scale';
$zmSlangScore                = 'Score';
$zmSlangSecs                 = 'Secs';
$zmSlangSectionlength        = 'Section length';
$zmSlangSelect               = 'Select';
$zmSlangSetLearnPrefs        = 'Set Learn Prefs'; // This can be ignored for now
$zmSlangSetNewBandwidth      = 'Set New Bandwidth';
$zmSlangSetPreset            = 'Set Preset';
$zmSlangSet                  = 'Set';
$zmSlangSettings             = 'Settings';
$zmSlangShowFilterWindow     = 'ShowFilterWindow';
$zmSlangShowTimeline         = 'Show Timeline';
$zmSlangSize                 = 'Size';
$zmSlangSleep                = 'Sleep';
$zmSlangSortAsc              = 'Asc';
$zmSlangSortBy               = 'Sort by';
$zmSlangSortDesc             = 'Desc';
$zmSlangSource               = 'Source';
$zmSlangSourceType           = 'Source Type';
$zmSlangSpeedHigh            = 'High Speed';
$zmSlangSpeedLow             = 'Low Speed';
$zmSlangSpeedMedium          = 'Medium Speed';
$zmSlangSpeed                = 'Speed';
$zmSlangSpeedTurbo           = 'Turbo Speed';
$zmSlangStart                = 'Start';
$zmSlangState                = 'State';
$zmSlangStats                = 'Stats';
$zmSlangStatus               = 'Status';
$zmSlangStepLarge            = 'Large Step';
$zmSlangStepMedium           = 'Medium Step';
$zmSlangStepNone             = 'No Step';
$zmSlangStepSmall            = 'Small Step';
$zmSlangStep                 = 'Step';
$zmSlangStills               = 'Stills';
$zmSlangStopped              = 'Stopped';
$zmSlangStop                 = 'Stop';
$zmSlangStream               = 'Stream';
$zmSlangSubmit               = 'Submit';
$zmSlangSystem               = 'System';
$zmSlangTele                 = 'Tele';
$zmSlangThumbnail            = 'Thumbnail';
$zmSlangTilt                 = 'Tilt';
$zmSlangTimeDelta            = 'Time Delta';
$zmSlangTimeline             = 'Timeline';
$zmSlangTimestampLabelFormat = 'Timestamp Label Format';
$zmSlangTimestampLabelX      = 'Timestamp Label X';
$zmSlangTimestampLabelY      = 'Timestamp Label Y';
$zmSlangTimestamp            = 'Timestamp';
$zmSlangTimeStamp            = 'Time Stamp';
$zmSlangTime                 = 'Time';
$zmSlangToday                = 'Today';
$zmSlangTools                = 'Tools';
$zmSlangTotalBrScore         = 'Total<br/>Score';
$zmSlangTrackDelay           = 'Track Delay';
$zmSlangTrackMotion          = 'Track Motion';
$zmSlangTriggers             = 'Triggers';
$zmSlangTurboPanSpeed        = 'Turbo Pan Speed';
$zmSlangTurboTiltSpeed       = 'Turbo Tilt Speed';
$zmSlangType                 = 'Type';
$zmSlangUnarchive            = 'Unarchive';
$zmSlangUnits                = 'Units';
$zmSlangUnknown              = 'Unknown';
$zmSlangUpdateAvailable      = 'An update to ZoneMinder is available.';
$zmSlangUpdateNotNecessary   = 'No update is necessary.';
$zmSlangUseFilterExprsPost   = '&nbsp;filter&nbsp;expressions'; // This is used at the end of the phrase 'use N filter expressions'
$zmSlangUseFilterExprsPre    = 'Use&nbsp;'; // This is used at the beginning of the phrase 'use N filter expressions'
$zmSlangUseFilter            = 'Use Filter';
$zmSlangUsername             = 'Username';
$zmSlangUsers                = 'Users';
$zmSlangUser                 = 'User';
$zmSlangValue                = 'Value';
$zmSlangVersionIgnore        = 'Ignore this version';
$zmSlangVersionRemindDay     = 'Remind again in 1 day';
$zmSlangVersionRemindHour    = 'Remind again in 1 hour';
$zmSlangVersionRemindNever   = 'Don\'t remind about new versions';
$zmSlangVersionRemindWeek    = 'Remind again in 1 week';
$zmSlangVersion              = 'Version';
$zmSlangVideoFormat          = 'Video Format';
$zmSlangVideoGenFailed       = 'Video Generation Failed!';
$zmSlangVideoGenFiles        = 'Existing Video Files';
$zmSlangVideoGenNoFiles      = 'No Video Files Found';
$zmSlangVideoGenParms        = 'Video Generation Parameters';
$zmSlangVideoGenSucceeded    = 'Video Generation Succeeded!';
$zmSlangVideoSize            = 'Video Size';
$zmSlangVideo                = 'Video';
$zmSlangViewAll              = 'View All';
$zmSlangViewEvent            = 'View Event';
$zmSlangViewPaged            = 'View Paged';
$zmSlangView                 = 'View';
$zmSlangWake                 = 'Wake';
$zmSlangWarmupFrames         = 'Warmup Frames';
$zmSlangWatch                = 'Watch';
$zmSlangWebColour            = 'Web Colour';
$zmSlangWeb                  = 'Web';
$zmSlangWeek                 = 'Week';
$zmSlangWhiteBalance         = 'White Balance';
$zmSlangWhite                = 'White';
$zmSlangWide                 = 'Wide';
$zmSlangX10ActivationString  = 'X10 Activation String';
$zmSlangX10InputAlarmString  = 'X10 Input Alarm String';
$zmSlangX10OutputAlarmString = 'X10 Output Alarm String';
$zmSlangX10                  = 'X10';
$zmSlangYes                  = 'Yes';
$zmSlangYouNoPerms           = 'You do not have permissions to access this resource.';
$zmSlangZoneAlarmColour      = 'Alarm Colour (RGB)';
$zmSlangZoneFilterHeight     = 'Filter Height (pixels)';
$zmSlangZoneFilterWidth      = 'Filter Width (pixels)';
$zmSlangZoneMaxAlarmedArea   = 'Maximum Alarmed Area';
$zmSlangZoneMaxBlobArea      = 'Maximum Blob Area';
$zmSlangZoneMaxBlobs         = 'Maximum Blobs';
$zmSlangZoneMaxFilteredArea  = 'Maximum Filtered Area';
$zmSlangZoneMaxPixelThres    = 'Maximum Pixel Threshold (0-255)';
$zmSlangZoneMaxX             = 'Maximum X (right)';
$zmSlangZoneMaxY             = 'Maximum Y (bottom)';
$zmSlangZoneMinAlarmedArea   = 'Minimum Alarmed Area';
$zmSlangZoneMinBlobArea      = 'Minimum Blob Area';
$zmSlangZoneMinBlobs         = 'Minimum Blobs';
$zmSlangZoneMinFilteredArea  = 'Minimum Filtered Area';
$zmSlangZoneMinPixelThres    = 'Minimum Pixel Threshold (0-255)';
$zmSlangZoneMinX             = 'Minimum X (left)';
$zmSlangZoneMinY             = 'Minimum Y (top)';
$zmSlangZones                = 'Zones';
$zmSlangZone                 = 'Zone';
$zmSlangZoomIn               = 'Zoom In';
$zmSlangZoomOut              = 'Zoom Out';
$zmSlangZoom                 = 'Zoom';

// Complex replacements with formatting and/or placements, must be passed through sprintf
$zmClangCurrentLogin         = 'Current login is \'%1$s\'';
$zmClangEventCount           = '%1$s %2$s'; // For example '37 Events' (from Vlang below)
$zmClangLastEvents           = 'Last %1$s %2$s'; // For example 'Last 37 Events' (from Vlang below)
$zmClangLatestRelease        = 'The latest release is v%1$s, you have v%2$s.';
$zmClangMonitorCount         = '%1$s %2$s'; // For example '4 Monitors' (from Vlang below)
$zmClangMonitorFunction      = 'Monitor %1$s Function';
$zmClangRunningRecentVer     = 'You are running the most recent version of ZoneMinder, v%s.';

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
$zmVlangEvent                = array( 0=>'Events', 1=>'Event', 2=>'Events' );
$zmVlangMonitor              = array( 0=>'Monitors', 1=>'Monitor', 2=>'Monitors' );

// You will need to choose or write a function that can correlate the plurality string arrays
// with variable counts. This is used to conjugate the Vlang arrays above with a number passed
// in to generate the correct noun form.
//
// In languages such as English this is fairly simple 
// Note this still has to be used with printf etc to get the right formating
function zmVlang( $lang_var_array, $count )
{
	krsort( $lang_var_array );
	foreach ( $lang_var_array as $key=>$value )
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
// function zmVlang( $lang_var_array, $count )
// {
// 	$secondlastdigit = substr( $count, -2, 1 );
// 	$lastdigit = substr( $count, -1, 1 );
// 	// or
// 	// $secondlastdigit = ($count/10)%10;
// 	// $lastdigit = $count%10;
// 
// 	// Get rid of the special cases first, the teens
// 	if ( $secondlastdigit == 1 && $lastdigit != 0 )
// 	{
// 		return( $lang_var_array[1] );
// 	}
// 	switch ( $lastdigit )
// 	{
// 		case 0 :
// 		case 5 :
// 		case 6 :
// 		case 7 :
// 		case 8 :
// 		case 9 :
// 		{
// 			return( $lang_var_array[1] );
// 			break;
// 		}
// 		case 1 :
// 		{
// 			return( $lang_var_array[2] );
// 			break;
// 		}
// 		case 2 :
// 		case 3 :
// 		case 4 :
// 		{
// 			return( $lang_var_array[3] );
// 			break;
// 		}
// 	}
// 	die( 'Error, unable to correlate variable language string' );
// }

// This is an example of how the function is used in the code which you can uncomment and 
// use to test your custom function.
//$monitors = array();
//$monitors[] = 1; // Choose any number
//echo sprintf( $zmClangMonitorCount, count($monitors), zmVlang( $zmVlangMonitor, count($monitors) ) );

// In this section you can override the default prompt and help texts for the options area
// These overrides are in the form of $zmOlangPrompt<option> and $zmOlangHelp<option>
// where <option> represents the option name minus the initial ZM_
// So for example, to override the help text for ZM_LANG_DEFAULT do
//$zmOlangPromptLANG_DEFAULT = "This is a new prompt for this option";
//$zmOlangHelpLANG_DEFAULT = "This is some new help for this option which will be displayed in the popup window when the ? is clicked";
//

?>
