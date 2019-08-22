<?php
//
// ZoneMinder web Japanese language file, $Date$, $Revision$
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

// ZoneMinder Japanese Translation by Andrew Arkley

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
header( "Content-Type: text/html; charset=Shift_JIS" );

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
    '24BitColour'          => '24ﾋﾞｯﾄｶﾗｰ',
    '32BitColour'          => '32ﾋﾞｯﾄｶﾗｰ',          // Added - 2011-06-15
    '8BitGrey'             => '8ﾋﾞｯﾄ濃淡画像',
    'Action'               => 'Action',
    'Actual'               => '生中継',
    'AddNewControl'        => 'Add New Control',
    'AddNewMonitor'        => 'ﾓﾆﾀｰ追加',
    'AddNewServer'         => 'Add New Server',         // Added - 2018-08-30
    'AddNewStorage'        => 'Add New Storage',        // Added - 2018-08-30
    'AddNewUser'           => 'ﾕｰｻﾞ追加',
    'AddNewZone'           => 'ｿﾞｰﾝ追加',
    'Alarm'                => 'ｱﾗｰﾑ',
    'AlarmBrFrames'        => 'ｱﾗｰﾑ<br/>ﾌﾚｰﾑ', 
    'AlarmFrame'           => 'ｱﾗｰﾑ ﾌﾚｰﾑ',
    'AlarmFrameCount'      => 'Alarm Frame Count',
    'AlarmLimits'          => 'ｱﾗｰﾑ限度',
    'AlarmMaximumFPS'      => 'Alarm Maximum FPS',
    'AlarmPx'              => 'ｱﾗｰﾑ Px',
    'AlarmRGBUnset'        => 'You must set an alarm RGB colour',
    'AlarmRefImageBlendPct'=> 'Alarm Reference Image Blend %ge', // Added - 2015-04-18
    'Alert'                => '警告',
    'All'                  => '全て',
    'AnalysisFPS'          => 'Analysis FPS',           // Added - 2015-07-22
    'AnalysisUpdateDelay'  => 'Analysis Update Delay',  // Added - 2015-07-23
    'Apply'                => '適用',
    'ApplyingStateChange'  => '変更適用中',
    'ArchArchived'         => '保存分のみ',
    'ArchUnarchived'       => '保存分以外のみ',
    'Archive'              => 'ｱｰｶｲﾌﾞ',
    'Archived'             => 'Archived',
    'Area'                 => 'Area',
    'AreaUnits'            => 'Area (px/%)',
    'AttrAlarmFrames'      => 'ｱﾗｰﾑ ﾌﾚｰﾑ',
    'AttrArchiveStatus'    => '保存状態',
    'AttrAvgScore'         => '平均ｽｺｱｰ',
    'AttrCause'            => 'Cause',
    'AttrDiskBlocks'       => 'Disk Blocks',
    'AttrDiskPercent'      => 'Disk Percent',
    'AttrDiskSpace'        => 'Disk Space',             // Added - 2018-08-30
    'AttrDuration'         => '継続時間',
    'AttrEndDate'          => 'End Date',               // Added - 2018-08-30
    'AttrEndDateTime'      => 'End Date/Time',          // Added - 2018-08-30
    'AttrEndTime'          => 'End Time',               // Added - 2018-08-30
    'AttrEndWeekday'       => 'End Weekday',            // Added - 2018-08-30
    'AttrFilterServer'     => 'Server Filter is Running On', // Added - 2018-08-30
    'AttrFrames'           => 'ﾌﾚｰﾑ',
    'AttrId'               => 'Id',
    'AttrMaxScore'         => '最高ｽｺｱｰ',
    'AttrMonitorId'        => 'ﾓﾆﾀｰ Id',
    'AttrMonitorName'      => 'ﾓﾆﾀｰ 名前',
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
    'AttrTotalScore'       => '合計ｽｺｱｰ',
    'Auto'                 => 'Auto',
    'AutoStopTimeout'      => 'Auto Stop Timeout',
    'Available'            => 'Available',              // Added - 2009-03-31
    'AvgBrScore'           => '平均<br/>ｽｺｱｰ',
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
    'BadNameChars'         => 'Names may only contain alphanumeric characters, spaces plus hyphen and underscore',
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
    'Bandwidth'            => '帯域幅',
    'BandwidthHead'         => 'Bandwidth',	// This is the end of the bandwidth status on the top of the console, different in many language due to phrasing
    'BlobPx'               => 'ﾌﾞﾛﾌﾞ Px',
    'BlobSizes'            => 'ﾌﾞﾛﾌﾞ ｻｲｽﾞ',
    'Blobs'                => 'ﾌﾞﾛﾌﾞ',
    'Brightness'           => '輝度',
    'Buffer'               => 'Buffer',                 // Added - 2015-04-18
    'Buffers'              => 'ﾊﾞｯﾌｧ',
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
    'Cancel'               => 'ｷｬﾝｾﾙ',
    'CancelForcedAlarm'    => '強制ｱﾗｰﾑｷｬﾝｾﾙ',
    'CaptureHeight'        => '取り込み高さ',
    'CaptureMethod'        => 'Capture Method',         // Added - 2009-02-08
    'CapturePalette'       => '取り込みﾊﾟﾚｯﾄ',
    'CaptureResolution'    => 'Capture Resolution',     // Added - 2015-04-18
    'CaptureWidth'         => '取り込み幅',
    'Cause'                => 'Cause',
    'CheckMethod'          => 'ｱﾗｰﾑ ﾁｪｯｸ方法',
    'ChooseDetectedCamera' => 'Choose Detected Camera', // Added - 2009-03-31
    'ChooseFilter'         => 'ﾌｨﾙﾀｰの選択',
    'ChooseLogFormat'      => 'Choose a log format',    // Added - 2011-06-17
    'ChooseLogSelection'   => 'Choose a log selection', // Added - 2011-06-17
    'ChoosePreset'         => 'Choose Preset',
    'Clear'                => 'Clear',                  // Added - 2011-06-16
    'CloneMonitor'         => 'Clone',                  // Added - 2018-08-30
    'Close'                => '閉じる',
    'Colour'               => '色',
    'Command'              => 'Command',
    'Component'            => 'Component',              // Added - 2011-06-16
    'ConcurrentFilter'     => 'Run filter concurrently', // Added - 2018-08-30
    'Config'               => 'Config',
    'ConfiguredFor'        => '設定:',
    'ConfirmDeleteEvents'  => 'Are you sure you wish to delete the selected events?',
    'ConfirmPassword'      => 'ﾊﾟｽﾜｰﾄﾞの確認',
    'ConjAnd'              => '及び',
    'ConjOr'               => '又は',
    'Console'              => 'ｺﾝｿｰﾙ',
    'ContactAdmin'         => '管理者にお問い合わせください。',
    'Continue'             => 'Continue',
    'Contrast'             => 'ｺﾝﾄﾗｽﾄ',
    'Control'              => 'Control',
    'ControlAddress'       => 'Control Address',
    'ControlCap'           => 'Control Capability',
    'ControlCaps'          => 'Control Capabilities',
    'ControlDevice'        => 'Control Device',
    'ControlType'          => 'Control Type',
    'Controllable'         => 'Controllable',
    'Current'              => 'Current',                // Added - 2015-04-18
    'Cycle'                => 'Cycle',
    'CycleWatch'           => 'ｻｲｸﾙ観察',
    'DateTime'             => 'Date/Time',              // Added - 2011-06-16
    'Day'                  => '曜日',
    'Debug'                => 'Debug',
    'DefaultRate'          => 'Default Rate',
    'DefaultScale'         => 'Default Scale',
    'DefaultView'          => 'Default View',
    'Deinterlacing'        => 'Deinterlacing',          // Added - 2015-04-18
    'Delay'                => 'Delay',                  // Added - 2015-04-18
    'Delete'               => '削除',
    'DeleteAndNext'        => '次を削除',
    'DeleteAndPrev'        => '前を削除',
    'DeleteSavedFilter'    => '保存ﾌｨﾙﾀｰの削除',
    'Description'          => '説明',
    'DetectedCameras'      => 'Detected Cameras',       // Added - 2009-03-31
    'DetectedProfiles'     => 'Detected Profiles',      // Added - 2015-04-18
    'Device'               => 'Device',                 // Added - 2009-02-08
    'DeviceChannel'        => 'ﾃﾞﾊﾞｲｽ ﾁｬﾝﾈﾙ',
    'DeviceFormat'         => 'ﾃﾞﾊﾞｲｽ ﾌｫｰﾏｯﾄ',
    'DeviceNumber'         => 'ﾃﾞﾊﾞｲｽ番号',
    'DevicePath'           => 'Device Path',
    'Devices'              => 'Devices',
    'Dimensions'           => '寸法',
    'DisableAlarms'        => 'Disable Alarms',
    'Disk'                 => 'Disk',
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
    'Download'             => 'Download',
    'DownloadVideo'        => 'Download Video',         // Added - 2018-08-30
    'DuplicateMonitorName' => 'Duplicate Monitor Name', // Added - 2009-03-31
    'Duration'             => '継続時間',
    'Edit'                 => '編集',
    'EditLayout'           => 'Edit Layout',            // Added - 2018-08-30
    'Email'                => 'ﾒｰﾙ',
    'EnableAlarms'         => 'Enable Alarms',
    'Enabled'              => '使用可能',
    'EnterNewFilterName'   => '新しいﾌｨﾙﾀｰ名の入力',
    'Error'                => 'エラー',
    'ErrorBrackets'        => 'エラー、開き括弧と閉じ括弧の数が合っているのかを確認してください',
    'ErrorValidValue'      => 'エラー、全ての項の数値が有効かどうかを確認してください',
    'Etc'                  => '等',
    'Event'                => 'ｲﾍﾞﾝﾄ',
    'EventFilter'          => 'ｲﾍﾞﾝﾄ ﾌｨﾙﾀｰ',
    'EventId'              => 'Event Id',
    'EventName'            => 'Event Name',
    'EventPrefix'          => 'Event Prefix',
    'Events'               => 'ｲﾍﾞﾝﾄ',
    'Exclude'              => '排除',
    'Execute'              => 'Execute',
    'Exif'                 => 'Embed EXIF data into image', // Added - 2018-08-30
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
    'FPSReportInterval'    => 'FPS報告間隔',
    'FTP'                  => 'FTP',
    'Far'                  => 'Far',
    'FastForward'          => 'Fast Forward',
    'Feed'                 => '送り込む',
    'Ffmpeg'               => 'Ffmpeg',                 // Added - 2009-02-08
    'File'                 => 'File',
    'Filter'               => 'Filter',                 // Added - 2015-04-18
    'FilterArchiveEvents'  => 'Archive all matches',
    'FilterDeleteEvents'   => 'Delete all matches',
    'FilterEmailEvents'    => 'Email details of all matches',
    'FilterExecuteEvents'  => 'Execute command on all matches',
    'FilterLog'            => 'Filter log',             // Added - 2015-04-18
    'FilterMessageEvents'  => 'Message details of all matches',
    'FilterMoveEvents'     => 'Move all matches',       // Added - 2018-08-30
    'FilterPx'             => 'ﾌｨﾙﾀｰ Px',
    'FilterUnset'          => 'You must specify a filter width and height',
    'FilterUpdateDiskSpace'=> 'Update used disk space', // Added - 2018-08-30
    'FilterUploadEvents'   => 'Upload all matches',
    'FilterVideoEvents'    => 'Create video for all matches',
    'Filters'              => 'Filters',
    'First'                => '最初',
    'FlippedHori'          => 'Flipped Horizontally',
    'FlippedVert'          => 'Flipped Vertically',
    'FnMocord'              => 'Mocord',            // Added 2013.08.16.
    'FnModect'              => 'Modect',            // Added 2013.08.16.
    'FnMonitor'             => 'Monitor',            // Added 2013.08.16.
    'FnNodect'              => 'Nodect',            // Added 2013.08.16.
    'FnNone'                => 'None',            // Added 2013.08.16.
    'FnRecord'              => 'Record',            // Added 2013.08.16.
    'Focus'                => 'Focus',
    'ForceAlarm'           => '強制ｱﾗｰﾑ',
    'Format'               => 'Format',
    'Frame'                => 'ﾌﾚｰﾑ',
    'FrameId'              => 'ﾌﾚｰﾑ ID',
    'FrameRate'            => 'ﾌﾚｰﾑﾚｰﾄ',
    'FrameSkip'            => 'ﾌﾚｰﾑｽｷｯﾌﾟ',
    'Frames'               => 'ﾌﾚｰﾑ',
    'Func'                 => '機能',
    'Function'             => '機能',
    'Gain'                 => 'Gain',
    'General'              => 'General',
    'GenerateDownload'     => 'Generate Download',      // Added - 2018-08-30
    'GenerateVideo'        => 'ﾋﾞﾃﾞｵの生成',
    'GeneratingVideo'      => 'ﾋﾞﾃﾞｵ生成中',
    'GoToZoneMinder'       => 'ZoneMinder.comに行く',
    'Grey'                 => 'ｸﾞﾚｰ',
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
    'HighBW'               => '高帯域',
    'Home'                 => 'Home',
    'Hostname'             => 'Hostname',               // Added - 2018-08-30
    'Hour'                 => '時',
    'Hue'                  => '色相',
    'Id'                   => 'ID',
    'Idle'                 => '待機状態',
    'Ignore'               => '無視',
    'Image'                => '画像',
    'ImageBufferSize'      => '画像 ﾊﾞｯﾌｧ ｻｲｽﾞ',
    'Images'               => 'Images',
    'In'                   => 'In',
    'Include'              => '組み込む',
    'Inverted'             => '反転',
    'Iris'                 => 'Iris',
    'KeyString'            => 'Key String',
    'Label'                => 'Label',
    'Language'             => '言語',
    'Last'                 => '最終',
    'Layout'               => 'Layout',                 // Added - 2009-02-08
    'Level'                => 'Level',                  // Added - 2011-06-16
    'Libvlc'               => 'Libvlc',
    'LimitResultsPost'     => 'results only;', // This is used at the end of the phrase 'Limit to first N results only'
    'LimitResultsPre'      => 'Limit to first', // This is used at the beginning of the phrase 'Limit to first N results only'
    'Line'                 => 'Line',                   // Added - 2011-06-16
    'LinkedMonitors'       => 'Linked Monitors',
    'List'                 => 'List',
    'ListMatches'          => 'List Matches',           // Added - 2018-08-30
    'Load'                 => 'Load',
    'Local'                => 'ﾛｰｶﾙ',
    'Log'                  => 'Log',                    // Added - 2011-06-16
    'LoggedInAs'           => 'ﾛｸﾞｲﾝ済み:',
    'Logging'              => 'Logging',                // Added - 2011-06-16
    'LoggingIn'            => 'ﾛｸﾞｲﾝ中',
    'Login'                => 'ﾛｸﾞｲﾝ',
    'Logout'               => 'ﾛｸﾞｱｳﾄ',
    'Logs'                 => 'Logs',                   // Added - 2011-06-17
    'Low'                  => '低',
    'LowBW'                => '低帯域',
    'Main'                 => 'Main',
    'Man'                  => 'Man',
    'Manual'               => 'Manual',
    'Mark'                 => '選択',
    'Max'                  => '最高',
    'MaxBandwidth'         => 'Max Bandwidth',
    'MaxBrScore'           => '最高<br/>ｽｺｱｰ',
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
    'MaximumFPS'           => '最高 FPS',
    'Medium'               => '中',
    'MediumBW'             => '中帯域',
    'Message'              => 'Message',                // Added - 2011-06-16
    'MinAlarmAreaLtMax'    => 'Minimum alarm area should be less than maximum',
    'MinAlarmAreaUnset'    => 'You must specify the minimum alarm pixel count',
    'MinBlobAreaLtMax'     => '最低ﾌﾞﾛｯﾌﾞ範囲は最高値より以下でなければいけない',
    'MinBlobAreaUnset'     => 'You must specify the minimum blob pixel count',
    'MinBlobLtMinFilter'   => 'Minimum blob area should be less than or equal to minimum filter area',
    'MinBlobsLtMax'        => '最低ﾌﾞﾛｯﾌﾞ数は最高数より以下でなければいけない',
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
    'MinPixelThresLtMax'   => '最低ﾋﾟｸｾﾙ閾値は最高値より以下でなければいけない',
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
    'Misc'                 => 'その他',
    'Mode'                 => 'Mode',                   // Added - 2015-04-18
    'Monitor'              => 'ﾓﾆﾀｰ',
    'MonitorIds'           => 'ﾓﾆﾀｰ ID',
    'MonitorPreset'        => 'Monitor Preset',
    'MonitorPresetIntro'   => 'Select an appropriate preset from the list below.<br><br>Please note that this may overwrite any values you already have configured for this monitor.<br><br>',
    'MonitorProbe'         => 'Monitor Probe',          // Added - 2009-03-31
    'MonitorProbeIntro'    => 'The list below shows detected analog and network cameras and whether they are already being used or available for selection.<br/><br/>Select the desired entry from the list below.<br/><br/>Please note that not all cameras may be detected and that choosing a camera here may overwrite any values you already have configured for the current monitor.<br/><br/>', // Added - 2009-03-31
    'Monitors'             => 'ﾓﾆﾀｰ',
    'Montage'              => 'ﾓﾝﾀｰｼﾞｭ',
    'MontageReview'        => 'Montage Review',         // Added - 2018-08-30
    'Month'                => '月',
    'More'                 => 'More',                   // Added - 2011-06-16
    'MotionFrameSkip'      => 'Motion Frame Skip',
    'Move'                 => 'Move',
    'Mtg2widgrd'           => '2-wide grid',              // Added 2013.08.15.
    'Mtg3widgrd'           => '3-wide grid',              // Added 2013.08.15.
    'Mtg3widgrx'           => '3-wide grid, scaled, enlarge on alarm',              // Added 2013.08.15.
    'Mtg4widgrd'           => '4-wide grid',              // Added 2013.08.15.
    'MtgDefault'           => 'Default',              // Added 2013.08.15.
    'MustBeGe'             => '同等か以上でなければいけない',
    'MustBeLe'             => '同等か以下でなければいけない',
    'MustConfirmPassword'  => 'パスワードの確認をしてください',
    'MustSupplyPassword'   => 'パスワードを入力してください',
    'MustSupplyUsername'   => 'ユーザ名を入力してください',
    'Name'                 => '名前',
    'Near'                 => 'Near',
    'Network'              => 'ﾈｯﾄﾜｰｸ',
    'New'                  => '新規',
    'NewGroup'             => 'New Group',
    'NewLabel'             => 'New Label',
    'NewPassword'          => '新しいﾊﾟｽﾜｰﾄﾞ',
    'NewState'             => '新規状態',  
    'NewUser'              => '新しいﾕｰｻﾞ',
    'Next'                 => '次',
    'No'                   => 'いいえ',
    'NoDetectedCameras'    => 'No Detected Cameras',    // Added - 2009-03-31
    'NoDetectedProfiles'   => 'No Detected Profiles',   // Added - 2018-08-30
    'NoFramesRecorded'     => 'このｲﾍﾞﾝﾄのﾌﾚｰﾑは登録されていません',
    'NoGroup'              => 'No Group',
    'NoSavedFilters'       => '保存されたﾌｨﾙﾀｰはありません',
    'NoStatisticsRecorded' => 'このｲﾍﾞﾝﾄ/ﾌﾚｰﾑの統計は登録されていません',
    'None'                 => 'ありません',
    'NoneAvailable'        => 'ありません',
    'Normal'               => '普通',
    'Notes'                => 'Notes',
    'NumPresets'           => 'Num Presets',
    'Off'                  => 'Off',
    'On'                   => 'On',
    'OnvifCredentialsIntro'=> 'Please supply user name and password for the selected camera.<br/>If no user has been created for the camera then the user given here will be created with the given password.<br/><br/>', // Added - 2015-04-18
    'OnvifProbe'           => 'ONVIF',                  // Added - 2015-04-18
    'OnvifProbeIntro'      => 'The list below shows detected ONVIF cameras and whether they are already being used or available for selection.<br/><br/>Select the desired entry from the list below.<br/><br/>Please note that not all cameras may be detected and that choosing a camera here may overwrite any values you already have configured for the current monitor.<br/><br/>', // Added - 2015-04-18
    'OpEq'                 => '同等',
    'OpGt'                 => '以下',
    'OpGtEq'               => '同等か以上',
    'OpIn'                 => 'ｾｯﾄに入っている',
    'OpIs'                 => 'is',                     // Added - 2018-08-30
    'OpIsNot'              => 'is not',                 // Added - 2018-08-30
    'OpLt'                 => '以下',
    'OpLtEq'               => '同等か以下',
    'OpMatches'            => '一致する',
    'OpNe'                 => '同等でない',
    'OpNotIn'              => 'ｾｯﾄに入っていない',
    'OpNotMatches'         => '一致しない',
    'Open'                 => 'Open',
    'OptionHelp'           => 'ｵﾌﾟｼｮﾝ ﾍﾙﾌﾟ',
    'OptionRestartWarning' => 'この変更は起動中反映されない場合があります。\n変更してからZoneMinderを再起動してください。',
    'OptionalEncoderParam' => 'Optional Encoder Parameters', // Added - 2018-08-30
    'Options'              => 'ｵﾌﾟｼｮﾝ',
    'OrEnterNewName'       => '又は新しい名前を入力してください',
    'Order'                => 'Order',
    'Orientation'          => 'ｵﾘｵﾝﾃｰｼｮﾝ',
    'Out'                  => 'Out',
    'OverwriteExisting'    => '上書きします',
    'Paged'                => 'ﾍﾟｰｼﾞ化',
    'Pan'                  => 'Pan',
    'PanLeft'              => 'Pan Left',
    'PanRight'             => 'Pan Right',
    'PanTilt'              => 'Pan/Tilt',
    'Parameter'            => 'ﾊﾟﾗﾒｰﾀ',
    'Password'             => 'ﾊﾟｽﾜｰﾄﾞ',
    'PasswordsDifferent'   => '新しいパスワードと再入力パスワードが一致しません',
    'Paths'                => 'ﾊﾟｽ',
    'Pause'                => 'Pause',
    'Phone'                => 'Phone',
    'PhoneBW'              => '携帯用',
    'Pid'                  => 'PID',                    // Added - 2011-06-16
    'PixelDiff'            => 'Pixel Diff',
    'Pixels'               => 'ﾋﾟｸｾﾙ',
    'Play'                 => 'Play',
    'PlayAll'              => 'Play All',
    'PleaseWait'           => 'お待ちください',
    'Plugins'              => 'Plugins',
    'Point'                => 'Point',
    'PostEventImageBuffer' => 'ｲﾍﾞﾝﾄ ｲﾒｰｼﾞ ﾊﾞｯﾌｧ後',
    'PreEventImageBuffer'  => 'ｲﾍﾞﾝﾄ ｲﾒｰｼﾞ ﾊﾞｯﾌｧ前',
    'PreserveAspect'       => 'Preserve Aspect Ratio',
    'Preset'               => 'Preset',
    'Presets'              => 'Presets',
    'Prev'                 => '前',
    'Probe'                => 'Probe',                  // Added - 2009-03-31
    'ProfileProbe'         => 'Stream Probe',           // Added - 2015-04-18
    'ProfileProbeIntro'    => 'The list below shows the existing stream profiles of the selected camera .<br/><br/>Select the desired entry from the list below.<br/><br/>Please note that ZoneMinder cannot configure additional profiles and that choosing a camera here may overwrite any values you already have configured for the current monitor.<br/><br/>', // Added - 2015-04-18
    'Progress'             => 'Progress',               // Added - 2015-04-18
    'Protocol'             => 'Protocol',
    'RTSPDescribe'         => 'Use RTSP Response Media URL', // Added - 2018-08-30
    'RTSPTransport'        => 'RTSP Transport Protocol', // Added - 2018-08-30
    'Rate'                 => 'ﾚｰﾄ',
    'Real'                 => '生中継',
    'RecaptchaWarning'     => 'Your reCaptcha secret key is invalid. Please correct it, or reCaptcha will not work', // Added - 2018-08-30
    'Record'               => '録画',
    'RecordAudio'          => 'Whether to store the audio stream when saving an event.', // Added - 2018-08-30
    'RefImageBlendPct'     => 'ｲﾒｰｼﾞ ﾌﾞﾚﾝﾄﾞ 参照 %',
    'Refresh'              => '最新の情報に更新',
    'Remote'               => 'ﾘﾓｰﾄ',
    'RemoteHostName'       => 'ﾘﾓｰﾄ ﾎｽﾄ 名',
    'RemoteHostPath'       => 'ﾘﾓｰﾄ ﾎｽﾄ ﾊﾟｽ',
    'RemoteHostPort'       => 'ﾘﾓｰﾄ ﾎｽﾄ ﾎﾟｰﾄ',
    'RemoteHostSubPath'    => 'Remote Host SubPath',    // Added - 2009-02-08
    'RemoteImageColours'   => 'ﾘﾓｰﾄ ｲﾒｰｼﾞ ｶﾗｰ',
    'RemoteMethod'         => 'Remote Method',          // Added - 2009-02-08
    'RemoteProtocol'       => 'Remote Protocol',        // Added - 2009-02-08
    'Rename'               => '新しい名前をつける',
    'Replay'               => 'Replay',
    'ReplayAll'            => 'All Events',
    'ReplayGapless'        => 'Gapless Events',
    'ReplaySingle'         => 'Single Event',
    'ReportEventAudit'     => 'Audit Events Report',    // Added - 2018-08-30
    'Reset'                => 'Reset',
    'ResetEventCounts'     => 'ｲﾍﾞﾝﾄ ｶｳﾝﾄ ﾘｾｯﾄ',
    'Restart'              => '再起動',
    'Restarting'           => '再起動中',
    'RestrictedCameraIds'  => '制限されたｶﾒﾗ ID',
    'RestrictedMonitors'   => 'Restricted Monitors',
    'ReturnDelay'          => 'Return Delay',
    'ReturnLocation'       => 'Return Location',
    'Rewind'               => 'Rewind',
    'RotateLeft'           => '左に回転',
    'RotateRight'          => '右に回転',
    'RunLocalUpdate'       => 'Please run zmupdate.pl to update', // Added - 2011-05-25
    'RunMode'              => '起動ﾓｰﾄﾞ',
    'RunState'             => '起動状態',
    'Running'              => '起動中',
    'Save'                 => '保存',
    'SaveAs'               => '名前をつけて保存',
    'SaveFilter'           => 'ﾌｨﾙﾀｰを保存',
    'SaveJPEGs'            => 'Save JPEGs',             // Added - 2018-08-30
    'Scale'                => 'ｽｹｰﾙ',
    'Score'                => 'ｽｺｱｰ',
    'Secs'                 => '秒',
    'Sectionlength'        => '長さ',
    'Select'               => 'Select',
    'SelectFormat'         => 'Select Format',          // Added - 2011-06-17
    'SelectLog'            => 'Select Log',             // Added - 2011-06-17
    'SelectMonitors'       => 'Select Monitors',
    'SelfIntersecting'     => 'Polygon edges must not intersect',
    'Set'                  => 'Set',
    'SetNewBandwidth'      => '新しい帯域幅の設定',
    'SetPreset'            => 'Set Preset',
    'Settings'             => '設定',
    'ShowFilterWindow'     => 'ﾌｨﾙﾀｰ ｳｲﾝﾄﾞｰの表示',
    'ShowTimeline'         => 'Show Timeline',
    'SignalCheckColour'    => 'Signal Check Colour',
    'SignalCheckPoints'    => 'Signal Check Points',    // Added - 2018-08-30
    'Size'                 => 'Size',
    'SkinDescription'      => 'Change the default skin for this computer', // Added - 2011-01-30
    'Sleep'                => 'Sleep',
    'SortAsc'              => 'Asc',
    'SortBy'               => 'Sort by',
    'SortDesc'             => 'Desc',
    'Source'               => 'ｿｰｽ',
    'SourceColours'        => 'Source Colours',         // Added - 2009-02-08
    'SourcePath'           => 'Source Path',            // Added - 2009-02-08
    'SourceType'           => 'ｿｰｽ ﾀｲﾌﾟ',
    'Speed'                => 'Speed',
    'SpeedHigh'            => 'High Speed',
    'SpeedLow'             => 'Low Speed',
    'SpeedMedium'          => 'Medium Speed',
    'SpeedTurbo'           => 'Turbo Speed',
    'Start'                => 'ｽﾀｰﾄ',
    'State'                => '状態',
    'Stats'                => '統計',
    'Status'               => '状態',
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
    'Stills'               => 'ｽﾁｰﾙ画像',
    'Stop'                 => '停止',
    'Stopped'              => '停止状態',
    'StorageArea'          => 'Storage Area',           // Added - 2018-08-30
    'StorageScheme'        => 'Scheme',                 // Added - 2018-08-30
    'Stream'               => 'ｽﾄﾘｰﾑ',
    'StreamReplayBuffer'   => 'Stream Replay Image Buffer',
    'Submit'               => 'Submit',
    'System'               => 'ｼｽﾃﾑ',
    'SystemLog'            => 'System Log',             // Added - 2011-06-16
    'TargetColorspace'     => 'Target colorspace',      // Added - 2015-04-18
    'Tele'                 => 'Tele',
    'Thumbnail'            => 'Thumbnail',
    'Tilt'                 => 'Tilt',
    'Time'                 => '時間',
    'TimeDelta'            => 'ﾃﾞﾙﾀ ﾀｲﾑ',
    'TimeStamp'            => 'ﾀｲﾑ ｽﾀﾝﾌﾟ',
    'Timeline'             => 'Timeline',
    'TimelineTip1'          => 'Pass your mouse over the graph to view a snapshot image and event details.',              // Added 2013.08.15.
    'TimelineTip2'          => 'Click on the coloured sections of the graph, or the image, to view the event.',              // Added 2013.08.15.
    'TimelineTip3'          => 'Click on the background to zoom in to a smaller time period based around your click.',              // Added 2013.08.15.
    'TimelineTip4'          => 'Use the controls below to zoom out or navigate back and forward through the time range.',              // Added 2013.08.15.
    'Timestamp'            => 'ﾀｲﾑｽﾀﾝﾌﾟ',
    'TimestampLabelFormat' => 'ﾀｲﾑｽﾀﾝﾌﾟ ﾗﾍﾞﾙ ﾌｫｰﾏｯﾄ',
    'TimestampLabelSize'   => 'Font Size',              // Added - 2018-08-30
    'TimestampLabelX'      => 'ﾀｲﾑｽﾀﾝﾌﾟ ﾗﾍﾞﾙ X',
    'TimestampLabelY'      => 'ﾀｲﾑｽﾀﾝﾌﾟ ﾗﾍﾞﾙ Y',
    'Today'                => 'Today',
    'Tools'                => 'ﾂｰﾙ',
    'Total'                => 'Total',                  // Added - 2011-06-16
    'TotalBrScore'         => '合計<br/>ｽｺｱｰ',
    'TrackDelay'           => 'Track Delay',
    'TrackMotion'          => 'Track Motion',
    'Triggers'             => 'ﾄﾘｶﾞｰ',
    'TurboPanSpeed'        => 'Turbo Pan Speed',
    'TurboTiltSpeed'       => 'Turbo Tilt Speed',
    'Type'                 => 'ﾀｲﾌﾟ',
    'Unarchive'            => '解凍',
    'Undefined'            => 'Undefined',              // Added - 2009-02-08
    'Units'                => 'ﾕﾆｯﾄ',
    'Unknown'              => '不明',
    'Update'               => 'Update',
    'UpdateAvailable'      => 'ZoneMinderのｱｯﾌﾟﾃﾞｰﾄがあります',
    'UpdateNotNecessary'   => 'ｱｯﾌﾟﾃﾞｰﾄの必要はありません',
    'Updated'              => 'Updated',                // Added - 2011-06-16
    'Upload'               => 'Upload',                 // Added - 2011-08-23
    'UseFilter'            => 'ﾌｨﾙﾀｰを使用してください',
    'UseFilterExprsPost'   => '&nbsp;ﾌｨﾙﾀｰ個数', // This is used at the end of the phrase 'use N filter expressions'
    'UseFilterExprsPre'    => '指定してください:&nbsp;', // This is used at the beginning of the phrase 'use N filter expressions'
    'UsedPlugins'	   => 'Used Plugins',
    'User'                 => 'ﾕｰｻﾞ',
    'Username'             => 'ﾕｰｻﾞ名',
    'Users'                => 'ﾕｰｻﾞ',
    'V4L'                  => 'V4L',                    // Added - 2015-04-18
    'V4LCapturesPerFrame'  => 'Captures Per Frame',     // Added - 2015-04-18
    'V4LMultiBuffer'       => 'Multi Buffering',        // Added - 2015-04-18
    'Value'                => '数値',
    'Version'              => 'ﾊﾞｰｼﾞｮﾝ',
    'VersionIgnore'        => 'このﾊﾞｰｼﾞｮﾝを無視',
    'VersionRemindDay'     => '1日後に再度知らせる',
    'VersionRemindHour'    => '1時間後に再度知らせる',
    'VersionRemindNever'   => '新しいﾊﾞｰｼﾞｮﾝの知らせは必要ない',
    'VersionRemindWeek'    => '1週間後に再度知らせる',
    'Video'                => 'ﾋﾞﾃﾞｵ',
    'VideoFormat'          => 'Video Format',
    'VideoGenFailed'       => 'ﾋﾞﾃﾞｵ生成の失敗！',
    'VideoGenFiles'        => 'Existing Video Files',
    'VideoGenNoFiles'      => 'No Video Files Found',
    'VideoGenParms'        => 'ﾋﾞﾃﾞｵ生成 ﾊﾟﾗﾒｰﾀ',
    'VideoGenSucceeded'    => 'Video Generation Succeeded!',
    'VideoSize'            => 'ﾋﾞﾃﾞｵ ｻｲｽﾞ',
    'VideoWriter'          => 'Video Writer',           // Added - 2018-08-30
    'View'                 => '表示',
    'ViewAll'              => '全部表示',
    'ViewEvent'            => 'View Event',
    'ViewPaged'            => 'ﾍﾟｰｼﾞ化の表示',
    'Wake'                 => 'Wake',
    'WarmupFrames'         => 'ｳｫｰﾑｱｯﾌﾟ ﾌﾚｰﾑ',
    'Watch'                => '監視',
    'Web'                  => 'ｳｪﾌﾞ',
    'WebColour'            => 'Web Colour',
    'WebSiteUrl'           => 'Website URL',            // Added - 2018-08-30
    'Week'                 => '週',
    'White'                => 'White',
    'WhiteBalance'         => 'White Balance',
    'Wide'                 => 'Wide',
    'X'                    => 'X',
    'X10'                  => 'X10',
    'X10ActivationString'  => 'X10起動文字列',
    'X10InputAlarmString'  => 'X10入力ｱﾗｰﾑ文字列',
    'X10OutputAlarmString' => 'X10出力ｱﾗｰﾑ文字列',
    'Y'                    => 'Y',
    'Yes'                  => 'はい',
    'YouNoPerms'           => 'この資源のｱｸｾｽ権がありません。',
    'Zone'                 => 'ｿﾞｰﾝ',
    'ZoneAlarmColour'      => 'ｱﾗｰﾑ ｶﾗｰ (Red/Green/Blue)',
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
    'Zones'                => 'ｿﾞｰﾝ',
    'Zoom'                 => 'Zoom',
    'ZoomIn'               => 'Zoom In',
    'ZoomOut'              => 'Zoom Out',
);

// Complex replacements with formatting and/or placements, must be passed through sprintf
$CLANG = array(
    'CurrentLogin'         => 'ただ今\'%1$s\がﾛｸﾞｲﾝしています',
    'EventCount'           => '%1$s %2$s',
    'LastEvents'           => '最終 %1$s %2$s',
    'LatestRelease'        => '最新ﾊﾞｰｼﾞｮﾝは v%1$s、ご利用ﾊﾞｰｼﾞｮﾝはv%2$s.',
    'MonitorCount'         => '%1$s %2$s',
    'MonitorFunction'      => 'ﾓﾆﾀｰ%1$s 機能',
    'RunningRecentVer'     => 'あなたはZoneMinderの最新ﾊﾞｰｼﾞｮﾝ v%s.を使っています',
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
    'Event'                => array( 0=>'ｲﾍﾞﾝﾄ', 1=>'ｲﾍﾞﾝﾄ', 2=>'ｲﾍﾞﾝﾄ' ),
    'Monitor'              => array( 0=>'ﾓﾆﾀｰ', 1=>'ﾓﾆﾀｰ', 2=>'ﾓﾆﾀｰ' ),
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
