<?php
//
// ZoneMinder web UK English language file, $Date$, $Revision$
// Copyright (C) 2003  Philip Coombes
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

// Notes for Translators
// 1. When composing the language tokens in your language you should try and keep to roughly the
//   same length text if possible. Abbreviate where necessary as spacing is quite close in a number of places.
// 2. There are three types of string replacement
//   a) Simple replacements are words or short phrases that are static and used directly. This type of
//     replacement can be used 'as is'.
//   b) Complex replacements involve some dynamic element being included and so may require substitution
//     or changing into a different order. The token listed in this file will be passed through sprintf as
//     a formatting string. If the dynamic element is a number you will usually need to use a variable
//     replacement also as described below.
//   c) Variable replacements are used in conjunction with complex replacements and involve the generation
//     of a singular or plural noun depending on the number passed into the zmVlang function. This is
//     intended to allow phrases such a '0 potatoes', '1 potato', '2 potatoes' etc to conjunct correctly
//     with the associated numerator. Variable replacements are expressed are arrays with a series of
//     counts and their associated words. When doing a replacement the passed value is compared with 
//     those counts in descending order and the nearest match below is used if no exact match is found.
//     Therefore is you have a variable replacement with 0,1 and 2 counts, which would be the normal form
//     in English, if you have 5 'things' then the nearest match below is '2' and so that plural would be used.
// 3. The tokens listed below are not used to build up phrases or sentences from single words. Therefore
//   you can safely assume that a single word token will only be used in that context.
// 4. In new language files, or if you are changing only a few words or phrases it makes sense from a 
//   maintenance point of view to include the original language file and override the old definitions rather
//   than copy all the language tokens across. To do this change the line below to whatever your base language
//   is and uncomment it.
// require_once( 'zm_lang_en_gb.php' );

// Simple String Replacements
$zmSlang24BitColour          = '24 bit colour';
$zmSlang8BitGrey             = '8 bit greyscale';
$zmSlangActual               = 'Actual';
$zmSlangAddNewMonitor        = 'Add New Monitor';
$zmSlangAddNewUser           = 'Add New User';
$zmSlangAddNewZone           = 'Add New Zone';
$zmSlangAlarm                = 'Alarm';
$zmSlangAlarmBrFrames        = 'Alarm<br/>Frames';
$zmSlangAlarmFrame           = 'Alarm Frame';
$zmSlangAlarmLimits          = 'Alarm Limits';
$zmSlangAlarmPx              = 'Alarm Px';
$zmSlangAlert                = 'Alert';
$zmSlangAll                  = 'All';
$zmSlangApply                = 'Apply';
$zmSlangApplyingStateChange  = 'Applying State Change';
$zmSlangArchArchived         = 'Archived Only';
$zmSlangArchive              = 'Archive';
$zmSlangArchUnarchived       = 'Unarchived Only';
$zmSlangAttrAlarmFrames      = 'Alarm Frames';
$zmSlangAttrArchiveStatus    = 'Archive Status';
$zmSlangAttrAvgScore         = 'Avg. Score';
$zmSlangAttrDate             = 'Date';
$zmSlangAttrDateTime         = 'Date/Time';
$zmSlangAttrDuration         = 'Duration';
$zmSlangAttrFrames           = 'Frames';
$zmSlangAttrMaxScore         = 'Max. Score';
$zmSlangAttrMontage          = 'Montage';
$zmSlangAttrTime             = 'Time';
$zmSlangAttrTotalScore       = 'Total Score';
$zmSlangAttrWeekday          = 'Weekday';
$zmSlangAutoArchiveEvents    = 'Automatically archive all matching events';
$zmSlangAutoDeleteEvents     = 'Automatically delete all matching events';
$zmSlangAutoEmailEvents      = 'Automatically email details of all matching events';
$zmSlangAutoMessageEvents    = 'Automatically message details of all matching events';
$zmSlangAutoUploadEvents     = 'Automatically upload all matching events';
$zmSlangAvgBrScore           = 'Avg.<br/>Score';
$zmSlangBandwidth            = 'Bandwidth';
$zmSlangBlobPx               = 'Blob Px';
$zmSlangBlobs                = 'Blobs';
$zmSlangBlobSizes            = 'Blob Sizes';
$zmSlangBrightness           = 'Brightness';
$zmSlangBuffers              = 'Buffers';
$zmSlangCancel               = 'Cancel';
$zmSlangCancelForcedAlarm    = 'Cancel&nbsp;Forced&nbsp;Alarm';
$zmSlangCaptureHeight        = 'Capture Height';
$zmSlangCapturePalette       = 'Capture Palette';
$zmSlangCaptureWidth         = 'Capture Width';
$zmSlangCheckAll             = 'Check All';
$zmSlangChooseFilter         = 'Choose Filter';
$zmSlangClose                = 'Close';
$zmSlangColour               = 'Colour';
$zmSlangConfiguredFor        = 'Configured for';
$zmSlangConfirmPassword      = 'Confirm Password';
$zmSlangConjAnd              = 'and';
$zmSlangConjOr               = 'or';
$zmSlangConsole              = 'Console';
$zmSlangContactAdmin         = 'Please contact your adminstrator for details.';
$zmSlangContrast             = 'Contrast';
$zmSlangCycleWatch           = 'Cycle Watch';
$zmSlangDay                  = 'Day';
$zmSlangDeleteAndNext        = 'Delete &amp; Next';
$zmSlangDeleteAndPrev        = 'Delete &amp; Prev';
$zmSlangDelete               = 'Delete';
$zmSlangDeleteSavedFilter    = 'Delete saved filter';
$zmSlangDescription          = 'Description';
$zmSlangDeviceChannel        = 'Device Channel';
$zmSlangDeviceFormat         = 'Device Format (0=PAL,1=NTSC etc)';
$zmSlangDeviceNumber         = 'Device Number (/dev/video?)';
$zmSlangDimensions           = 'Dimensions';
$zmSlangDuration             = 'Duration';
$zmSlangEdit                 = 'Edit';
$zmSlangEmail                = 'Email';
$zmSlangEnabled              = 'Enabled';
$zmSlangEnterNewFilterName   = 'Enter new filter name';
$zmSlangErrorBrackets        = 'Error, please check you have an equal number of opening and closing brackets';
$zmSlangError                = 'Error';
$zmSlangErrorValidValue      = 'Error, please check that all terms have a valid value';
$zmSlangEtc                  = 'etc';
$zmSlangEvent                = 'Event';
$zmSlangEventFilter          = 'Event Filter';
$zmSlangEvents               = 'Events';
$zmSlangExclude              = 'Exclude';
$zmSlangFeed                 = 'Feed';
$zmSlangFilterPx             = 'Filter Px';
$zmSlangFirst                = 'First';
$zmSlangForceAlarm           = 'Force&nbsp;Alarm';
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
$zmSlangGenerateVideo        = 'Generate Video';
$zmSlangGeneratingVideo      = 'Generating Video';
$zmSlangGrey                 = 'Grey';
$zmSlangHighBW               = 'High&nbsp;B/W';
$zmSlangHigh                 = 'High';
$zmSlangHour                 = 'Hour';
$zmSlangHue                  = 'Hue';
$zmSlangId                   = 'Id';
$zmSlangIdle                 = 'Idle';
$zmSlangIgnore               = 'Ignore';
$zmSlangImageBufferSize      = 'Image Buffer Size';
$zmSlangImage                = 'Image';
$zmSlangInclude              = 'Include';
$zmSlangInverted             = 'Inverted';
$zmSlangLanguage             = 'Language';
$zmSlangLast                 = 'Last';
$zmSlangLocal                = 'Local';
$zmSlangLoggedInAs           = 'Logged In As';
$zmSlangLoggingIn            = 'Logging In';
$zmSlangLogin                = 'Login';
$zmSlangLogout               = 'Logout';
$zmSlangLowBW                = 'Low&nbsp;B/W';
$zmSlangLow                  = 'Low';
$zmSlangMark                 = 'Mark';
$zmSlangMaxBrScore           = 'Max.<br/>Score';
$zmSlangMaximumFPS           = 'Maximum FPS';
$zmSlangMax                  = 'Max';
$zmSlangMediumBW             = 'Medium&nbsp;B/W';
$zmSlangMedium               = 'Medium';
$zmSlangMinAlarmGeMinBlob    = 'Minimum alarm pixels should be greater than or equal to minimum blob pixels';
$zmSlangMinAlarmGeMinFilter  = 'Minimum alarm pixels should be greater than or equal to minimum filter pixels';
$zmSlangMisc                 = 'Misc';
$zmSlangMonitorIds           = 'Monitor&nbsp;Ids';
$zmSlangMonitor              = 'Monitor';
$zmSlangMonitors             = 'Monitors';
$zmSlangMontage              = 'Montage';
$zmSlangMonth                = 'Month';
$zmSlangMustBeGe             = 'must be greater than or equal to';
$zmSlangMustBeLe             = 'must be less than or equal to';
$zmSlangMustConfirmPassword  = 'You must confirm the password';
$zmSlangMustSupplyPassword   = 'You must supply a password';
$zmSlangMustSupplyUsername   = 'You must supply a username';
$zmSlangName                 = 'Name';
$zmSlangNetwork              = 'Network';
$zmSlangNew                  = 'New';
$zmSlangNewPassword          = 'New Password';
$zmSlangNewState             = 'New State';
$zmSlangNewUser              = 'New User';
$zmSlangNext                 = 'Next';
$zmSlangNoFramesRecorded     = 'There are no frames recorded for this event';
$zmSlangNoneAvailable        = 'None available';
$zmSlangNone                 = 'None';
$zmSlangNo                   = 'No';
$zmSlangNormal               = 'Normal';
$zmSlangNoSavedFilters       = 'NoSavedFilters';
$zmSlangNoStatisticsRecorded = 'There are no statistics recorded for this event/frame';
$zmSlangOpEq                 = 'equal to';
$zmSlangOpGtEq               = 'greater than or equal to';
$zmSlangOpGt                 = 'greater than';
$zmSlangOpLtEq               = 'less than or equal to';
$zmSlangOpLt                 = 'less than';
$zmSlangOpNe                 = 'not equal to';
$zmSlangOptionHelp           = 'OptionHelp';
$zmSlangOptionRestartWarning = 'These changes may not come into effect fully\nwhile the system is running. When you have\nfinished making your changes please ensure that\nyou restart ZoneMinder.';
$zmSlangOptions              = 'Options';
$zmSlangOrEnterNewName       = 'or enter new name';
$zmSlangOrientation          = 'Orientation';
$zmSlangOverwriteExisting    = 'Overwrite Existing';
$zmSlangPaged                = 'Paged';
$zmSlangParameter            = 'Parameter';
$zmSlangPassword             = 'Password';
$zmSlangPasswordsDifferent   = 'The new and confirm passwords are different';
$zmSlangPaths                = 'Paths';
$zmSlangPhoneBW              = 'Phone&nbsp;B/W';
$zmSlangPixels               = 'pixels';
$zmSlangPleaseWait           = 'Please Wait';
$zmSlangPostEventImageBuffer = 'Post Event Image Buffer';
$zmSlangPreEventImageBuffer  = 'Pre Event Image Buffer<';
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
$zmSlangRestarting           = 'Restarting';
$zmSlangRestart              = 'Restart';
$zmSlangRestrictedCameraIds  = 'Restricted Camera Ids';
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
$zmSlangServerLoad           = 'Server Load';
$zmSlangSetLearnPrefs        = 'Set Learn Prefs'; // This can be ignored for now
$zmSlangSetNewBandwidth      = 'Set New Bandwidth';
$zmSlangSettings             = 'Settings';
$zmSlangShowFilterWindow     = 'ShowFilterWindow';
$zmSlangSource               = 'Source';
$zmSlangSourceType           = 'Source Type';
$zmSlangStart                = 'Start';
$zmSlangState                = 'State';
$zmSlangStats                = 'Stats';
$zmSlangStatus               = 'Status';
$zmSlangStills               = 'Stills';
$zmSlangStopped              = 'Stopped';
$zmSlangStop                 = 'Stop';
$zmSlangStream               = 'Stream';
$zmSlangSystem               = 'System';
$zmSlangTimeDelta            = 'Time Delta';
$zmSlangTimestampLabelFormat = 'Timestamp Label Format';
$zmSlangTimestampLabelX      = 'Timestamp Label X';
$zmSlangTimestampLabelY      = 'Timestamp Label Y';
$zmSlangTimestamp            = 'Timestamp';
$zmSlangTimeStamp            = 'Time Stamp';
$zmSlangTime                 = 'Time';
$zmSlangTools                = 'Tools';
$zmSlangTotalBrScore         = 'Total<br/>Score';
$zmSlangTriggers             = 'Triggers';
$zmSlangType                 = 'Type';
$zmSlangUnarchive            = 'Unarchive';
$zmSlangUnits                = 'Units';
$zmSlangUnknown              = 'Unknown';
$zmSlangUseFilterExprsPost   = '&nbsp;filter&nbsp;expressions'; // This is used at the end of the phrase 'use N filter expressions'
$zmSlangUseFilterExprsPre    = 'Use&nbsp;'; // This is used at the beginning of the phrase 'use N filter expressions'
$zmSlangUseFilter            = 'Use Filter';
$zmSlangUsername             = 'Username';
$zmSlangUsers                = 'Users';
$zmSlangUser                 = 'User';
$zmSlangValue                = 'Value';
$zmSlangVideoGenFailed       = 'Video Generation Failed!';
$zmSlangVideoGenParms        = 'Video Generation Parameters';
$zmSlangVideoSize            = 'Video Size';
$zmSlangVideo                = 'Video';
$zmSlangViewAll              = 'View All';
$zmSlangViewPaged            = 'View Paged';
$zmSlangView                 = 'View';
$zmSlangWarmupFrames         = 'Warmup Frames';
$zmSlangWatch                = 'Watch';
$zmSlangWeb                  = 'Web';
$zmSlangWeek                 = 'Week';
$zmSlangX10ActivationString  = 'X10 Activation String';
$zmSlangX10InputAlarmString  = 'X10 Input Alarm String';
$zmSlangX10OutputAlarmString = 'X10 Output Alarm String';
$zmSlangX10                  = 'X10';
$zmSlangYes                  = 'Yes';
$zmSlangYouNoPerms           = 'You do not have permissions to access this resource.';
$zmSlangZoneAlarmColour      = 'Alarm Colour (RGB)';
$zmSlangZoneAlarmThreshold   = 'Alarm Threshold (0>=?<=255)';
$zmSlangZoneFilterHeight     = 'Filter Height (pixels)';
$zmSlangZoneFilterWidth      = 'Filter Width (pixels)';
$zmSlangZoneMaxAlarmedArea   = 'Maximum Alarmed Area';
$zmSlangZoneMaxBlobArea      = 'Maximum Blob Area';
$zmSlangZoneMaxBlobs         = 'Maximum Blobs';
$zmSlangZoneMaxFilteredArea  = 'Maximum Filtered Area';
$zmSlangZoneMaxX             = 'Maximum X (right)';
$zmSlangZoneMaxY             = 'Maximum Y (bottom)';
$zmSlangZoneMinAlarmedArea   = 'Minimum Alarmed Area';
$zmSlangZoneMinBlobArea      = 'Minimum Blob Area';
$zmSlangZoneMinBlobs         = 'Minimum Blobs';
$zmSlangZoneMinFilteredArea  = 'Minimum Filtered Area';
$zmSlangZoneMinX             = 'Minimum X (left)';
$zmSlangZoneMinY             = 'Minimum Y (top)';
$zmSlangZones                = 'Zones';
$zmSlangZone                 = 'Zone';

// Complex replacements with formatting and/or placements, must be passed through sprintf
$zmClangCurrentLogin         = 'Current login is \'%1$s\'';
$zmClangEventCount           = '%1$s %2$s'; // For example '37 Events' (from Vlang below)
$zmClangLastEvents           = 'Last %1$s %2$s'; // For example 'Last 37 Events' (from Vlang below)
$zmClangMonitorCount         = '%1$s %2$s'; // For example '4 Monitors' (from Vlang below)
$zmClangMonitorFunction      = 'Monitor %1$s Function';

// Variable arrays expressing plurality
$zmVlangEvent                = array( 0=>'Events', 1=>'Event', 2=>'Events' );
$zmVlangMonitor              = array( 0=>'Monitors', 1=>'Monitor', 2=>'Monitors' );

?>
