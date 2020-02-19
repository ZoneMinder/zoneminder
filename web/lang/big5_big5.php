<?php
//
// ZoneMinder web Chinese Traditional language file, $Date$, $Revision$
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

// ZoneMinder <Chinese Traditional> Translation by <Greener C. Chiou>

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
//require_once( 'zm_lang_en_gb.php' );

// You may need to change the character set here, if your web server does not already
// do this by default, uncomment this if required.
//
// Example
//header( "Content-Type: text/html; charset=Big5" );

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
 //setlocale( 'LC_ALL', 'en_GB' ); All locale settings pre-4.3.0
 //setlocale( LC_ALL, 'en_GB' ); All locale settings 4.3.0 and after
// setlocale( LC_CTYPE, 'en_GB' ); Character class settings 4.3.0 and after
// setlocale( LC_TIME, 'en_GB' ); Date and time formatting 4.3.0 and after
setlocale( LC_ALL, 'Big5' ); //All locale settings pre-4.3.0
//setlocale( LC_ALL, 'Big5' ); //All locale settings 4.3.0 and after
setlocale( LC_CTYPE, 'Big5' ); //Character class settings 4.3.0 and after
setlocale( LC_TIME, 'Big5' ); //Date and time formatting 4.3.0 and after

// Simple String Replacements
$SLANG = array(
    '24BitColour'          => '24 位元色彩',
    '32BitColour'          => '32 位元色彩',          // Added - 2011-06-15
    '8BitGrey'             => '8 位元灰階',
    'Action'               => 'Action',
    'Actual'               => 'Actual',
    'AddNewControl'        => '新增控制',
    'AddNewMonitor'        => '新增監視',
    'AddNewServer'         => 'Add New Server',         // Added - 2018-08-30
    'AddNewStorage'        => 'Add New Storage',        // Added - 2018-08-30
    'AddNewUser'           => '新增使用者',
    'AddNewZone'           => '新增監視區',
    'Alarm'                => '警報',
    'AlarmBrFrames'        => '警報<br/>框架',
    'AlarmFrame'           => '警報框架',
    'AlarmFrameCount'      => '警報框架數',
    'AlarmLimits'          => 'Alarm Limits',
    'AlarmMaximumFPS'      => 'Alarm Maximum FPS',
    'AlarmPx'              => 'Alarm Px',
    'AlarmRGBUnset'        => 'You must set an alarm RGB colour',
    'AlarmRefImageBlendPct'=> 'Alarm Reference Image Blend %ge', // Added - 2015-04-18
    'Alert'                => '警告',
    'All'                  => '全部',
    'AnalysisFPS'          => 'Analysis FPS',           // Added - 2015-07-22
    'AnalysisUpdateDelay'  => 'Analysis Update Delay',  // Added - 2015-07-23
    'Apply'                => '確定',
    'ApplyingStateChange'  => '確定狀態改變',
    'ArchArchived'         => 'Archived Only',
    'ArchUnarchived'       => 'Unarchived Only',
    'Archive'              => '存檔',
    'Archived'             => '已存檔',
    'Area'                 => 'Area',
    'AreaUnits'            => 'Area (px/%)',
    'AttrAlarmFrames'      => 'Alarm Frames',
    'AttrArchiveStatus'    => 'Archive Status',
    'AttrAvgScore'         => 'Average Score',
    'AttrCause'            => 'Cause',
    'AttrDiskBlocks'       => 'Disk Blocks',
    'AttrDiskPercent'      => 'Disk Percent',
    'AttrDiskSpace'        => 'Disk Space',             // Added - 2018-08-30
    'AttrDuration'         => 'Duration',
    'AttrEndDate'          => 'End Date',               // Added - 2018-08-30
    'AttrEndDateTime'      => 'End Date/Time',          // Added - 2018-08-30
    'AttrEndTime'          => 'End Time',               // Added - 2018-08-30
    'AttrEndWeekday'       => 'End Weekday',            // Added - 2018-08-30
    'AttrFilterServer'     => 'Server Filter is Running On', // Added - 2018-08-30
    'AttrFrames'           => 'Frames',
    'AttrId'               => 'Id',
    'AttrMaxScore'         => 'Max. Score',
    'AttrMonitorId'        => 'Monitor Id',
    'AttrMonitorName'      => 'Monitor Name',
    'AttrMonitorServer'    => 'Server Monitor is Running On', // Added - 2018-08-30
    'AttrName'             => 'Name',
    'AttrNotes'            => 'Notes',
    'AttrStartDate'        => 'Start Date',             // Added - 2018-08-30
    'AttrStartDateTime'    => 'Start Date/Time',        // Added - 2018-08-30
    'AttrStartTime'        => 'Start Time',             // Added - 2018-08-30
    'AttrStartWeekday'     => 'Start Weekday',          // Added - 2018-08-30
    'AttrStateId'          => 'Run State',              // Added - 2018-08-30
    'AttrStorageArea'      => 'Storage Area',           // Added - 2018-08-30
    'AttrStorageServer'    => 'Server Hosting Storage', // Added - 2018-08-30
    'AttrSystemLoad'       => 'System Load',
    'AttrTotalScore'       => 'Total Score',
    'Auto'                 => '自動',
    'AutoStopTimeout'      => '時間過自動停止',
    'Available'            => 'Available',              // Added - 2009-03-31
    'AvgBrScore'           => '平均<br/>分數',
    'Background'           => 'Background',
    'BackgroundFilter'     => 'Run filter in background',
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
    'BadNameChars'         => 'Names may only contain alphanumeric characters plus hyphen and underscore',
    'BadPalette'           => 'Palette must be set to a valid value', // Added - 2009-03-31
    'BadPath'              => 'Path must be set to a valid value',
    'BadPort'              => 'Port must be set to a valid number',
    'BadPostEventCount'    => 'Post event image count must be an integer of zero or more',
    'BadPreEventCount'     => 'Pre event image count must be at least zero, and less than image buffer size',
    'BadRefBlendPerc'      => 'Reference blend percentage must be a positive integer',
    'BadSectionLength'     => 'Section length must be an integer of 30 or more',
    'BadSignalCheckColour' => 'Signal check colour must be a valid RGB colour string',
    'BadSourceType'        => 'Source Type \"Web Site\" requires the Function to be set to \"Monitor\"', // Added - 2018-08-30
    'BadStreamReplayBuffer'=> 'Stream replay buffer must be an integer of zero or more',
    'BadWarmupCount'       => 'Warmup frames must be an integer of zero or more',
    'BadWebColour'         => 'Web colour must be a valid web colour string',
    'BadWebSitePath'       => 'Please enter a complete website url, including the http:// or https:// prefix.', // Added - 2018-08-30
    'BadWidth'             => 'Width must be set to a valid value',
    'Bandwidth'            => '頻寬',
    'BandwidthHead'        => 'Bandwidth',	// This is the end of the bandwidth status on the top of the console, different in many language due to phrasing
    'BlobPx'               => 'Blob Px',
    'BlobSizes'            => 'Blob Sizes',
    'Blobs'                => 'Blobs',
    'Brightness'           => '亮度',
    'Buffer'               => 'Buffer',                 // Added - 2015-04-18
    'Buffers'              => '緩衝',
    'CSSDescription'       => 'Change the default css for this computer', // Added - 2015-04-18
    'CanAutoFocus'         => 'Can Auto Focus',
    'CanAutoGain'          => 'Can Auto Gain',
    'CanAutoIris'          => 'Can Auto Iris',
    'CanAutoWhite'         => 'Can Auto White Bal.',
    'CanAutoZoom'          => 'Can Auto Zoom',
    'CanFocus'             => 'Can Focus',
    'CanFocusAbs'          => 'Can Focus Absolute',
    'CanFocusCon'          => 'Can Focus Continuous',
    'CanFocusRel'          => 'Can Focus Relative',
    'CanGain'              => 'Can Gain ',
    'CanGainAbs'           => 'Can Gain Absolute',
    'CanGainCon'           => 'Can Gain Continuous',
    'CanGainRel'           => 'Can Gain Relative',
    'CanIris'              => 'Can Iris',
    'CanIrisAbs'           => 'Can Iris Absolute',
    'CanIrisCon'           => 'Can Iris Continuous',
    'CanIrisRel'           => 'Can Iris Relative',
    'CanMove'              => 'Can Move',
    'CanMoveAbs'           => 'Can Move Absolute',
    'CanMoveCon'           => 'Can Move Continuous',
    'CanMoveDiag'          => 'Can Move Diagonally',
    'CanMoveMap'           => 'Can Move Mapped',
    'CanMoveRel'           => 'Can Move Relative',
    'CanPan'               => 'Can Pan' ,
    'CanReset'             => 'Can Reset',
	'CanReboot'             => 'Can Reboot',
    'CanSetPresets'        => 'Can Set Presets',
    'CanSleep'             => 'Can Sleep',
    'CanTilt'              => 'Can Tilt',
    'CanWake'              => 'Can Wake',
    'CanWhite'             => 'Can White Balance',
    'CanWhiteAbs'          => 'Can White Bal. Absolute',
    'CanWhiteBal'          => 'Can White Bal.',
    'CanWhiteCon'          => 'Can White Bal. Continuous',
    'CanWhiteRel'          => 'Can White Bal. Relative',
    'CanZoom'              => 'Can Zoom',
    'CanZoomAbs'           => 'Can Zoom Absolute',
    'CanZoomCon'           => 'Can Zoom Continuous',
    'CanZoomRel'           => 'Can Zoom Relative',
    'Cancel'               => '取消',
    'CancelForcedAlarm'    => 'Cancel Forced Alarm',
    'CaptureHeight'        => '捕捉高度',
    'CaptureMethod'        => 'Capture Method',         // Added - 2009-02-08
    'CapturePalette'       => '捕捉格式',
    'CaptureResolution'    => 'Capture Resolution',     // Added - 2015-04-18
    'CaptureWidth'         => '捕捉寬度',
    'Cause'                => '因素',
    'CheckMethod'          => 'Alarm Check Method',
    'ChooseDetectedCamera' => 'Choose Detected Camera', // Added - 2009-03-31
    'ChooseFilter'         => 'Choose Filter',
    'ChooseLogFormat'      => 'Choose a log format',    // Added - 2011-06-17
    'ChooseLogSelection'   => 'Choose a log selection', // Added - 2011-06-17
    'ChoosePreset'         => 'Choose Preset',
    'Clear'                => 'Clear',                  // Added - 2011-06-16
    'CloneMonitor'         => 'Clone',                  // Added - 2018-08-30
    'Close'                => '關閉',
    'Colour'               => 'Colour',
    'Command'              => 'Command',
    'Component'            => 'Component',              // Added - 2011-06-16
    'ConcurrentFilter'     => 'Run filter concurrently', // Added - 2018-08-30
    'Config'               => 'Config',
    'ConfiguredFor'        => '配置為',
    'ConfirmDeleteEvents'  => 'Are you sure you wish to delete the selected events?',
    'ConfirmPassword'      => '確認密碼',
    'ConjAnd'              => 'and',
    'ConjOr'               => 'or',
    'Console'              => '操控台',
    'ContactAdmin'         => '請與系統管理者聯繫.',
    'Continue'             => '連續',
    'Contrast'             => 'Contrast',
    'Control'              => 'Control',
    'ControlAddress'       => 'Control Address',
    'ControlCap'           => 'Control Capability',
    'ControlCaps'          => 'Control Capabilities',
    'ControlDevice'        => 'Control Device',
    'ControlType'          => 'Control Type',
    'Controllable'         => 'Controllable',
    'Current'              => 'Current',                // Added - 2015-04-18
    'Cycle'                => '分區輪流檢視',
    'CycleWatch'           => '分區輪流檢視',
    'DateTime'             => 'Date/Time',              // Added - 2011-06-16
    'Day'                  => '日',
    'Debug'                => 'debug',
    'DefaultRate'          => '預設速率',
    'DefaultScale'         => '預設尺寸',
    'DefaultView'          => 'Default View',
    'Deinterlacing'        => 'Deinterlacing',          // Added - 2015-04-18
    'Delay'                => 'Delay',                  // Added - 2015-04-18
    'Delete'               => '刪除',
    'DeleteAndNext'        => '刪除 &amp; 下一事件',
    'DeleteAndPrev'        => '刪除 &amp; 上一事件',
    'DeleteSavedFilter'    => '刪除儲存過濾',
    'Description'          => '描述',
    'DetectedCameras'      => 'Detected Cameras',       // Added - 2009-03-31
    'DetectedProfiles'     => 'Detected Profiles',      // Added - 2015-04-18
    'Device'               => 'Device',                 // Added - 2009-02-08
    'DeviceChannel'        => '裝置通道',
    'DeviceFormat'         => '裝置格式',
    'DeviceNumber'         => '裝置編號',
    'DevicePath'           => '裝置路徑',
    'Devices'              => 'Devices',
    'Dimensions'           => '尺寸',
    'DisableAlarms'        => '取消警報',
    'Disk'                 => '磁碟',
    'Display'              => 'Display',                // Added - 2011-01-30
    'Displaying'           => 'Displaying',             // Added - 2011-06-16
    'DoNativeMotionDetection'=> 'Do Native Motion Detection',
    'Donate'               => 'Please Donate',
    'DonateAlready'        => 'No, I\'ve already donated',
    'DonateEnticement'     => 'You\'ve been running ZoneMinder for a while now and hopefully are finding it a useful addition to your home or workplace security. Although ZoneMinder is, and will remain, free and open source, it costs money to develop and support. If you would like to help support future development and new features then please consider donating. Donating is, of course, optional but very much appreciated and you can donate as much or as little as you like.<br><br>If you would like to donate please select the option below or go to https://zoneminder.com/donate/ in your browser.<br><br>Thank you for using ZoneMinder and don\'t forget to visit the forums on ZoneMinder.com for support or suggestions about how to make your ZoneMinder experience even better.',
    'DonateRemindDay'      => 'Not yet, remind again in 1 day',
    'DonateRemindHour'     => 'Not yet, remind again in 1 hour',
    'DonateRemindMonth'    => 'Not yet, remind again in 1 month',
    'DonateRemindNever'    => 'No, I don\'t want to donate, never remind',
    'DonateRemindWeek'     => 'Not yet, remind again in 1 week',
    'DonateYes'            => 'Yes, I\'d like to donate now',
    'Download'             => '下載',
    'DownloadVideo'        => 'Download Video',         // Added - 2018-08-30
    'DuplicateMonitorName' => 'Duplicate Monitor Name', // Added - 2009-03-31
    'Duration'             => '歷時',
    'Edit'                 => '編輯',
    'EditLayout'           => 'Edit Layout',            // Added - 2018-08-30
    'Email'                => 'Email',
    'EnableAlarms'         => '啟動警報',
    'Enabled'              => '啟用',
    'EnterNewFilterName'   => 'Enter new filter name',
    'Error'                => '錯誤',
    'ErrorBrackets'        => 'Error, please check you have an equal number of opening and closing brackets',
    'ErrorValidValue'      => 'Error, please check that all terms have a valid value',
    'Etc'                  => 'etc',
    'Event'                => '事件',
    'EventFilter'          => '事件過濾',
    'EventId'              => '事件Id',
    'EventName'            => '事件名稱',
    'EventPrefix'          => '事件字首',
    'Events'               => '事件',
    'Exclude'              => '不包含',
    'Execute'              => 'Execute',
    'Exif'                 => 'Embed EXIF data into image', // Added - 2018-08-30
    'Export'               => '輸出',
    'ExportDetails'        => '輸出事件細項',
    'ExportFailed'         => '輸出失敗',
    'ExportFormat'         => '輸出檔案格式',
    'ExportFormatTar'      => 'Tar',
    'ExportFormatZip'      => 'Zip',
    'ExportFrames'         => '輸出框架細項',
    'ExportImageFiles'     => '輸出圖片檔',
    'ExportLog'            => 'Export Log',             // Added - 2011-06-17
    'ExportMiscFiles'      => '輸出其他檔(若有)',
    'ExportOptions'        => '輸出選項',
    'ExportSucceeded'      => 'Export Succeeded',       // Added - 2009-02-08
    'ExportVideoFiles'     => '輸出影片檔(若有)',
    'Exporting'            => '輸出中',
    'FPS'                  => 'fps',
    'FPSReportInterval'    => 'FPS 報告間距',
    'FTP'                  => 'FTP',
    'Far'                  => 'Far',
    'FastForward'          => 'Fast Forward',
    'Feed'                 => 'Feed',
    'Ffmpeg'               => 'Ffmpeg',                 // Added - 2009-02-08
    'File'                 => 'File',
    'Filter'               => 'Filter',                 // Added - 2015-04-18
    'FilterArchiveEvents'  => '自動儲存符合項目',
    'FilterDeleteEvents'   => '自動刪除符合項目',
    'FilterEmailEvents'    => '自動寄出詳細符合項目',
    'FilterExecuteEvents'  => '自動執行符合指令',
    'FilterLog'            => 'Filter log',             // Added - 2015-04-18
    'FilterMessageEvents'  => '自動發出符合訊息',
    'FilterMoveEvents'     => 'Move all matches',       // Added - 2018-08-30
    'FilterPx'             => 'Filter Px',
    'FilterUnset'          => '您必需設定濾鏡的寬度和高度',
    'FilterUpdateDiskSpace'=> 'Update used disk space', // Added - 2018-08-30
    'FilterUploadEvents'   => '自動上傳符合項目',
    'FilterVideoEvents'    => '自動產生符合的影像檔',
    'Filters'              => '濾鏡',
    'First'                => 'First',
    'FlippedHori'          => '水平反轉',
    'FlippedVert'          => '垂直反轉',
    'FnMocord'             => 'Mocord',            // Added 2013.08.16.
    'FnModect'             => 'Modect',            // Added 2013.08.16.
    'FnMonitor'            => 'Monitor',            // Added 2013.08.16.
    'FnNodect'             => 'Nodect',            // Added 2013.08.16.
    'FnNone'               => 'None',            // Added 2013.08.16.
    'FnRecord'             => 'Record',            // Added 2013.08.16.
    'Focus'                => 'Focus',
    'ForceAlarm'           => 'Force Alarm',
    'Format'               => '格式',
    'Frame'                => '框架',
    'FrameId'              => '框架 Id',
    'FrameRate'            => '框架速率',
    'FrameSkip'            => '框架忽略',
    'Frames'               => '框架',
    'Func'                 => 'Func',
    'Function'             => '功能',
    'Gain'                 => 'Gain',
    'General'              => '一般',
    'GenerateDownload'     => 'Generate Download',      // Added - 2018-08-30
    'GenerateVideo'        => '輸出影片',
    'GeneratingVideo'      => '輸出影片中',
    'GoToZoneMinder'       => 'Go to ZoneMinder.com',
    'Grey'                 => 'Grey',
    'Group'                => 'Group',
    'Groups'               => 'Groups',
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
    'High'                 => '高',
    'HighBW'               => 'High&nbsp;B/W',
    'Home'                 => 'Home',
    'Hostname'             => 'Hostname',               // Added - 2018-08-30
    'Hour'                 => '時',
    'Hue'                  => 'Hue',
    'Id'                   => 'Id',
    'Idle'                 => 'Idle',
    'Ignore'               => 'Ignore',
    'Image'                => '影像',
    'ImageBufferSize'      => '影像緩衝大小',
    'Images'               => 'Images',
    'In'                   => 'In',
    'Include'              => '包含',
    'Inverted'             => '反轉',
    'Iris'                 => 'Iris',
    'KeyString'            => 'Key String',
    'Label'                => 'Label',
    'Language'             => '語言',
    'Last'                 => 'Last',
    'Layout'               => 'Layout',                 // Added - 2009-02-08
    'Level'                => 'Level',                  // Added - 2011-06-16
    'Libvlc'               => 'Libvlc',
    'LimitResultsPost'     => 'results only;', // This is used at the end of the phrase 'Limit to first N results only'
    'LimitResultsPre'      => 'Limit to first', // This is used at the beginning of the phrase 'Limit to first N results only'
    'Line'                 => 'Line',                   // Added - 2011-06-16
    'LinkedMonitors'       => 'Linked Monitors',
    'List'                 => '列出',
    'ListMatches'          => 'List Matches',           // Added - 2018-08-30
    'Load'                 => '載入',
    'Local'                => 'Local',
    'Log'                  => 'Log',                    // Added - 2011-06-16
    'LoggedInAs'           => '登入名稱',
    'Logging'              => 'Logging',                // Added - 2011-06-16
    'LoggingIn'            => '登入中... 請稍後...',
    'Login'                => '登入',
    'Logout'               => '登出',
    'Logs'                 => 'Logs',                   // Added - 2011-06-17
    'Low'                  => '低',
    'LowBW'                => 'Low&nbsp;B/W',
    'Main'                 => 'Main',
    'Man'                  => 'Man',
    'Manual'               => 'Manual',
    'Mark'                 => '標註',
    'Max'                  => 'Max',
    'MaxBandwidth'         => 'Max Bandwidth',          // Added - 2009-02-08
    'MaxBrScore'           => '最高<br/>分數',
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
    'MaximumFPS'           => '最大每秒框架數 fps',
    'Medium'               => '中',
    'MediumBW'             => 'Medium&nbsp;B/W',
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
    'Misc'                 => '細項',
    'Mode'                 => 'Mode',                   // Added - 2015-04-18
    'Monitor'              => '監視',
    'MonitorIds'           => 'Monitor&nbsp;Ids',
    'MonitorPreset'        => 'Monitor Preset',
    'MonitorPresetIntro'   => 'Select an appropriate preset from the list below.<br><br>Please note that this may overwrite any values you already have configured for this monitor.<br><br>',
    'MonitorProbe'         => 'Monitor Probe',          // Added - 2009-03-31
    'MonitorProbeIntro'    => 'The list below shows detected analog and network cameras and whether they are already being used or available for selection.<br/><br/>Select the desired entry from the list below.<br/><br/>Please note that not all cameras may be detected and that choosing a camera here may overwrite any values you already have configured for the current monitor.<br/><br/>', // Added - 2009-03-31
    'Monitors'             => '監視',
    'Montage'              => '全部顯示',
    'MontageReview'        => 'Montage Review',         // Added - 2018-08-30
    'Month'                => '月',
    'More'                 => 'More',                   // Added - 2011-06-16
    'MotionFrameSkip'       => 'Motion Frame Skip',
    'Move'                 => '移動',
    'Mtg2widgrd'           => '2-wide grid',              // Added 2013.08.15.
    'Mtg3widgrd'           => '3-wide grid',              // Added 2013.08.15.
    'Mtg3widgrx'           => '3-wide grid, scaled, enlarge on alarm',              // Added 2013.08.15.
    'Mtg4widgrd'           => '4-wide grid',              // Added 2013.08.15.
    'MtgDefault'           => 'Default',              // Added 2013.08.15.
    'MustBeGe'             => '需大於或等於',
    'MustBeLe'             => '需小於或等於',
    'MustConfirmPassword'  => '您必需確認密碼',
    'MustSupplyPassword'   => '您必需提供密碼',
    'MustSupplyUsername'   => '您必需提供使用者名稱',
    'Name'                 => '名稱',
    'Near'                 => 'Near',
    'Network'              => 'Network',
    'New'                  => '新增',
    'NewGroup'             => '新群組',
    'NewLabel'             => 'New Label',
    'NewPassword'          => '新密碼',
    'NewState'             => '新狀態',
    'NewUser'              => '新使用者',
    'Next'                 => '下一步',
    'No'                   => 'No',
    'NoDetectedCameras'    => 'No Detected Cameras',    // Added - 2009-03-31
    'NoDetectedProfiles'   => 'No Detected Profiles',   // Added - 2018-08-30
    'NoFramesRecorded'     => 'There are no frames recorded for this event',
    'NoGroup'              => 'No Group',               // Added - 2009-02-08
    'NoSavedFilters'       => 'NoSavedFilters',
    'NoStatisticsRecorded' => 'There are no statistics recorded for this event/frame',
    'None'                 => '無選取',
    'NoneAvailable'        => 'None available',
    'Normal'               => 'Normal',
    'Notes'                => 'Notes',
    'NumPresets'           => 'Num Presets',
    'Off'                  => 'Off',
    'On'                   => 'On',
    'OnvifCredentialsIntro'=> 'Please supply user name and password for the selected camera.<br/>If no user has been created for the camera then the user given here will be created with the given password.<br/><br/>', // Added - 2015-04-18
    'OnvifProbe'           => 'ONVIF',                  // Added - 2015-04-18
    'OnvifProbeIntro'      => 'The list below shows detected ONVIF cameras and whether they are already being used or available for selection.<br/><br/>Select the desired entry from the list below.<br/><br/>Please note that not all cameras may be detected and that choosing a camera here may overwrite any values you already have configured for the current monitor.<br/><br/>', // Added - 2015-04-18
    'OpEq'                 => 'equal to',
    'OpGt'                 => 'greater than',
    'OpGtEq'               => 'greater than or equal to',
    'OpIn'                 => 'in set',
    'OpIs'                 => 'is',                     // Added - 2018-08-30
    'OpIsNot'              => 'is not',                 // Added - 2018-08-30
    'OpLt'                 => 'less than',
    'OpLtEq'               => 'less than or equal to',
    'OpMatches'            => 'matches',
    'OpNe'                 => 'not equal to',
    'OpNotIn'              => 'not in set',
    'OpNotMatches'         => 'does not match',
    'Open'                 => 'Open',
    'OptionHelp'           => 'OptionHelp',
    'OptionRestartWarning' => 'These changes may not come into effect fully\nwhile the system is running. When you have\nfinished making your changes please ensure that\nyou restart ZoneMinder.',
    'OptionalEncoderParam' => 'Optional Encoder Parameters', // Added - 2018-08-30
    'Options'              => '銓垣專用',//進階選項
    'OrEnterNewName'       => 'or enter new name',
    'Order'                => '順序',
    'Orientation'          => '方向',
    'Out'                  => 'Out',
    'OverwriteExisting'    => 'Overwrite Existing',
    'Paged'                => 'Paged',
    'Pan'                  => 'Pan',
    'PanLeft'              => 'Pan Left',
    'PanRight'             => 'Pan Right',
    'PanTilt'              => 'Pan/Tilt',
    'Parameter'            => '參數',
    'Password'             => '密碼',
    'PasswordsDifferent'   => 'The new and confirm passwords are different',
    'Paths'                => 'Paths',
    'Pause'                => 'Pause',
    'Phone'                => 'Phone',
    'PhoneBW'              => 'Phone&nbsp;B/W',
    'Pid'                  => 'PID',                    // Added - 2011-06-16
    'PixelDiff'            => 'Pixel Diff',
    'Pixels'               => 'pixels',
    'Play'                 => 'Play',
    'PlayAll'              => '全部播放',
    'PleaseWait'           => 'Please Wait',
    'Plugins'              => 'Plugins',
    'Point'                => '點',
    'PostEventImageBuffer' => '後置事件影像緩衝',
    'PreEventImageBuffer'  => '前置事件影像緩衝',
    'PreserveAspect'       => 'Preserve Aspect Ratio',
    'Preset'               => 'Preset',
    'Presets'              => 'Presets',
    'Prev'                 => '上一事件',
    'Probe'                => 'Probe',                  // Added - 2009-03-31
    'ProfileProbe'         => 'Stream Probe',           // Added - 2015-04-18
    'ProfileProbeIntro'    => 'The list below shows the existing stream profiles of the selected camera .<br/><br/>Select the desired entry from the list below.<br/><br/>Please note that ZoneMinder cannot configure additional profiles and that choosing a camera here may overwrite any values you already have configured for the current monitor.<br/><br/>', // Added - 2015-04-18
    'Progress'             => 'Progress',               // Added - 2015-04-18
    'Protocol'             => 'Protocol',
    'RTSPDescribe'         => 'Use RTSP Response Media URL', // Added - 2018-08-30
    'RTSPTransport'        => 'RTSP Transport Protocol', // Added - 2018-08-30
    'Rate'                 => 'Rate',
    'Real'                 => 'Real',
    'RecaptchaWarning'     => 'Your reCaptcha secret key is invalid. Please correct it, or reCaptcha will not work', // Added - 2018-08-30
    'Record'               => '錄影',
    'RecordAudio'          => 'Whether to store the audio stream when saving an event.', // Added - 2018-08-30
    'RefImageBlendPct'     => '參考影像混合 %ge',
    'Refresh'              => '更新',
    'Remote'               => 'Remote',
    'RemoteHostName'       => '遠端主機名稱',
    'RemoteHostPath'       => '遠端主機路徑',
    'RemoteHostPort'       => '遠端主機端口',
    'RemoteHostSubPath'    => 'Remote Host SubPath',    // Added - 2009-02-08
    'RemoteImageColours'   => 'Remote Image Colours',
    'RemoteMethod'         => 'Remote Method',          // Added - 2009-02-08
    'RemoteProtocol'       => 'Remote Protocol',        // Added - 2009-02-08
    'Rename'               => '重新命名',
    'Replay'               => '重新播放',
    'ReplayAll'            => 'All Events',
    'ReplayGapless'        => 'Gapless Events',
    'ReplaySingle'         => 'Single Event',
    'ReportEventAudit'     => 'Audit Events Report',    // Added - 2018-08-30
    'Reset'                => 'Reset',
    'ResetEventCounts'     => 'Reset Event Counts',
    'Restart'              => '重新啟動',
    'Restarting'           => 'Restarting',
    'RestrictedCameraIds'  => 'Restricted Camera Ids',
    'RestrictedMonitors'   => 'Restricted Monitors',
    'ReturnDelay'          => 'Return Delay',
    'ReturnLocation'       => 'Return Location',
    'Rewind'               => 'Rewind',
    'RotateLeft'           => 'Rotate Left',
    'RotateRight'          => 'Rotate Right',
    'RunLocalUpdate'       => 'Please run zmupdate.pl to update', // Added - 2011-05-25
    'RunMode'              => '監視模式',
    'RunState'             => '運作狀態',
    'Running'              => '運行中',
    'Save'                 => '存檔',
    'SaveAs'               => '儲存為',
    'SaveFilter'           => 'Save Filter',
    'SaveJPEGs'            => 'Save JPEGs',             // Added - 2018-08-30
    'Scale'                => 'Scale',
    'Score'                => '分數',
    'Secs'                 => 'Secs',
    'Sectionlength'        => '片段長度',
    'Select'               => '選取',
    'SelectFormat'         => 'Select Format',          // Added - 2011-06-17
    'SelectLog'            => 'Select Log',             // Added - 2011-06-17
    'SelectMonitors'       => 'Select Monitors',
    'SelfIntersecting'     => 'Polygon edges must not intersect',
    'Set'                  => 'Set',
    'SetNewBandwidth'      => '設定新頻寬速度',
    'SetPreset'            => 'Set Preset',
    'Settings'             => 'Settings',
    'ShowFilterWindow'     => '顯示過濾視窗',
    'ShowTimeline'         => 'Show Timeline',
    'SignalCheckColour'    => 'Signal Check Colour',
    'SignalCheckPoints'    => 'Signal Check Points',    // Added - 2018-08-30
    'Size'                 => 'Size',
    'SkinDescription'      => 'Change the default skin for this computer', // Added - 2011-01-30
    'Sleep'                => 'Sleep',
    'SortAsc'              => 'Asc',
    'SortBy'               => 'Sort by',
    'SortDesc'             => 'Desc',
    'Source'               => '來源',
    'SourceColours'        => 'Source Colours',         // Added - 2009-02-08
    'SourcePath'           => 'Source Path',            // Added - 2009-02-08
    'SourceType'           => '來源形式',
    'Speed'                => '速度',
    'SpeedHigh'            => '高 速',
    'SpeedLow'             => '低 速',
    'SpeedMedium'          => '中速',
    'SpeedTurbo'           => 'Turbo Speed',
    'Start'                => 'Start',
    'State'                => 'State',
    'Stats'                => 'Stats',
    'Status'               => 'Status',
    'StatusConnected'      => 'Capturing',              // Added - 2018-08-30
    'StatusNotRunning'     => 'Not Running',            // Added - 2018-08-30
    'StatusRunning'        => 'Not Capturing',          // Added - 2018-08-30
    'StatusUnknown'        => 'Unknown',                // Added - 2018-08-30
    'Step'                 => 'Step',
    'StepBack'             => 'Step Back',
    'StepForward'          => 'Step Forward',
    'StepLarge'            => 'Large Step',
    'StepMedium'           => 'Medium Step',
    'StepNone'             => 'No Step',
    'StepSmall'            => 'Small Step',
    'Stills'               => '靜止',
    'Stop'                 => '停止',
    'Stopped'              => '已停止',
    'StorageArea'          => 'Storage Area',           // Added - 2018-08-30
    'StorageScheme'        => 'Scheme',                 // Added - 2018-08-30
    'Stream'               => '串流',
    'StreamReplayBuffer'   => 'Stream Replay Image Buffer',
    'Submit'               => 'Submit',
    'System'               => 'System',
    'SystemLog'            => 'System Log',             // Added - 2011-06-16
    'TargetColorspace'     => 'Target colorspace',      // Added - 2015-04-18
    'Tele'                 => 'Tele',
    'Thumbnail'            => '小圖檢視',
    'Tilt'                 => 'Tilt',
    'Time'                 => '時間',
    'TimeDelta'            => 'Time Delta',
    'TimeStamp'            => 'Time Stamp',             // Added - 2009-02-08
    'Timeline'             => 'Timeline',               // Added - 2009-02-08
    'TimelineTip1'          => 'Pass your mouse over the graph to view a snapshot image and event details.',              // Added 2013.08.15.
    'TimelineTip2'          => 'Click on the coloured sections of the graph, or the image, to view the event.',              // Added 2013.08.15.
    'TimelineTip3'          => 'Click on the background to zoom in to a smaller time period based around your click.',              // Added 2013.08.15.
    'TimelineTip4'          => 'Use the controls below to zoom out or navigate back and forward through the time range.',              // Added 2013.08.15.
    'Timestamp'            => '時間格式',
    'TimestampLabelFormat' => '時間標示格式',
    'TimestampLabelSize'   => 'Font Size',              // Added - 2018-08-30
    'TimestampLabelX'      => '時間標示 X',
    'TimestampLabelY'      => '時間標示 Y',
    'Today'                => 'Today',
    'Tools'                => 'Tools',
    'Total'                => 'Total',                  // Added - 2011-06-16
    'TotalBrScore'         => '全部<br/>分數',
    'TrackDelay'           => 'Track Delay',
    'TrackMotion'          => 'Track Motion',
    'Triggers'             => '觸發',
    'TurboPanSpeed'        => 'Turbo Pan Speed',
    'TurboTiltSpeed'       => 'Turbo Tilt Speed',
    'Type'                 => 'Type',
    'Unarchive'            => '不存檔',
    'Undefined'            => 'Undefined',              // Added - 2009-02-08
    'Units'                => 'Units',
    'Unknown'              => 'Unknown',
    'Update'               => 'Update',                 // Added - 2009-02-08
    'UpdateAvailable'      => 'An update to ZoneMinder is available.',
    'UpdateNotNecessary'   => 'No update is necessary.',
    'Updated'              => 'Updated',                // Added - 2011-06-16
    'Upload'               => 'Upload',                 // Added - 2011-08-23
    'UseFilter'            => 'Use Filter',
    'UseFilterExprsPost'   => '&nbsp;filter&nbsp;expressions', // This is used at the end of the phrase 'use N filter expressions'
    'UseFilterExprsPre'    => 'Use&nbsp;', // This is used at the beginning of the phrase 'use N filter expressions'
    'UsedPlugins'	   => 'Used Plugins',
    'User'                 => 'User',
    'Username'             => '使用者名稱',
    'Users'                => 'Users',
    'V4L'                  => 'V4L',                    // Added - 2015-04-18
    'V4LCapturesPerFrame'  => 'Captures Per Frame',     // Added - 2015-04-18
    'V4LMultiBuffer'       => 'Multi Buffering',        // Added - 2015-04-18
    'Value'                => '設定值',
    'Version'              => '版本',
    'VersionIgnore'        => 'Ignore this version',
    'VersionRemindDay'     => 'Remind again in 1 day',
    'VersionRemindHour'    => 'Remind again in 1 hour',
    'VersionRemindNever'   => 'Don\'t remind about new versions',
    'VersionRemindWeek'    => 'Remind again in 1 week',
    'Video'                => 'Video',
    'VideoFormat'          => 'Video Format',           // Added - 2009-02-08
    'VideoGenFailed'       => '輸出影片失敗!',
    'VideoGenFiles'        => 'Existing Video Files',   // Added - 2009-02-08
    'VideoGenNoFiles'      => 'No Video Files Found',   // Added - 2009-02-08
    'VideoGenParms'        => '輸出影片參數',
    'VideoGenSucceeded'    => 'Video Generation Succeeded!', // Added - 2009-02-08
    'VideoSize'            => '影片尺寸',
    'VideoWriter'          => 'Video Writer',           // Added - 2018-08-30
    'View'                 => '檢視',
    'ViewAll'              => '全部檢視',
    'ViewEvent'            => 'View Event',             // Added - 2009-02-08
    'ViewPaged'            => '分頁檢視',
    'Wake'                 => 'Wake',
    'WarmupFrames'         => '熱機框架',
    'Watch'                => 'Watch',
    'Web'                  => 'Web',
    'WebColour'            => 'Web Colour',             // Added - 2009-02-08
    'WebSiteUrl'           => 'Website URL',            // Added - 2018-08-30
    'Week'                 => '週',
    'White'                => 'White',
    'WhiteBalance'         => 'White Balance',
    'Wide'                 => 'Wide',
    'X'                    => 'X',
    'X10'                  => 'X10',
    'X10ActivationString'  => 'X10 Activation String',
    'X10InputAlarmString'  => 'X10 Input Alarm String',
    'X10OutputAlarmString' => 'X10 Output Alarm String',
    'Y'                    => 'Y',                      // Added - 2009-02-08
    'Yes'                  => 'Yes',
    'YouNoPerms'           => 'You do not have permissions to access this resource.',
    'Zone'                 => 'Zone',
    'ZoneAlarmColour'      => 'Alarm Colour (RGB)',
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
    'Zones'                => '監視區',
    'Zoom'                 => 'Zoom',
    'ZoomIn'               => 'Zoom In',                // Added - 2009-02-08
    'ZoomOut'              => 'Zoom Out',               // Added - 2009-02-08
);

// Complex replacements with formatting and/or placements, must be passed through sprintf
$CLANG = array(
    'CurrentLogin'         => '目前登入者是 \'%1$s\'',
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
    'Event'                => array( 0=>'事件', 1=>'事件', 2=>'事件' ),
    'Monitor'              => array( 0=>'監視', 1=>'監視', 2=>'監視' ),
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
