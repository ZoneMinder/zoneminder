<?php
//
// ZoneMinder web Japanese language file, $Date$, $Revision$
// Copyright (C) 2003, 2004, 2005, 2006  Philip Coombes
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
$zmSlang24BitColour          = '24ﾋﾞｯﾄｶﾗｰ';
$zmSlang8BitGrey             = '8ﾋﾞｯﾄ濃淡画像';
$zmSlangAction               = 'Action';
$zmSlangActual               = '生中継';
$zmSlangAddNewControl        = 'Add New Control';
$zmSlangAddNewMonitor        = 'ﾓﾆﾀｰ追加';
$zmSlangAddNewUser           = 'ﾕｰｻﾞ追加';
$zmSlangAddNewZone           = 'ｿﾞｰﾝ追加';
$zmSlangAlarm                = 'ｱﾗｰﾑ';
$zmSlangAlarmBrFrames        = 'ｱﾗｰﾑ<br/>ﾌﾚｰﾑ';	
$zmSlangAlarmFrame           = 'ｱﾗｰﾑ ﾌﾚｰﾑ';
$zmSlangAlarmFrameCount      = 'Alarm Frame Count';
$zmSlangAlarmLimits          = 'ｱﾗｰﾑ限度';
$zmSlangAlarmMaximumFPS      = 'Alarm Maximum FPS';
$zmSlangAlarmPx              = 'ｱﾗｰﾑ Px';
$zmSlangAlarmRGBUnset        = 'You must set an alarm RGB colour';
$zmSlangAlert                = '警告';
$zmSlangAll                  = '全て';
$zmSlangApplyingStateChange  = '変更適用中';
$zmSlangApply                = '適用';
$zmSlangArchArchived         = '保存分のみ';
$zmSlangArchive              = 'ｱｰｶｲﾌﾞ';
$zmSlangArchived             = 'Archived';
$zmSlangArchUnarchived       = '保存分以外のみ';
$zmSlangArea                 = 'Area';
$zmSlangAreaUnits            = 'Area (px/%)';
$zmSlangAttrAlarmFrames      = 'ｱﾗｰﾑ ﾌﾚｰﾑ';
$zmSlangAttrArchiveStatus    = '保存状態';
$zmSlangAttrAvgScore         = '平均ｽｺｱｰ';
$zmSlangAttrCause            = 'Cause';
$zmSlangAttrDate             = '日付';
$zmSlangAttrDateTime         = '日時';
$zmSlangAttrDiskBlocks       = 'Disk Blocks';
$zmSlangAttrDiskPercent      = 'Disk Percent';
$zmSlangAttrDuration         = '継続時間';
$zmSlangAttrFrames           = 'ﾌﾚｰﾑ';
$zmSlangAttrId               = 'Id';
$zmSlangAttrMaxScore         = '最高ｽｺｱｰ';
$zmSlangAttrMonitorId        = 'ﾓﾆﾀｰ Id';
$zmSlangAttrMonitorName      = 'ﾓﾆﾀｰ 名前';
$zmSlangAttrName             = 'Name';
$zmSlangAttrNotes            = 'Notes';
$zmSlangAttrSystemLoad       = 'System Load';
$zmSlangAttrTime             = '時間';
$zmSlangAttrTotalScore       = '合計ｽｺｱｰ';
$zmSlangAttrWeekday          = '曜日';
$zmSlangAuto                 = 'Auto';
$zmSlangAutoStopTimeout      = 'Auto Stop Timeout';
$zmSlangAvgBrScore           = '平均<br/>ｽｺｱｰ';
$zmSlangBackground           = 'Background';
$zmSlangBackgroundFilter     = 'Run filter in background';
$zmSlangBadAlarmFrameCount   = 'Alarm frame count must be an integer of one or more';
$zmSlangBadAlarmMaxFPS       = 'Alarm Maximum FPS must be a positive integer or floating point value';
$zmSlangBadChannel           = 'Channel must be set to an integer of zero or more';
$zmSlangBadDevice            = 'Device must be set to a valid value';
$zmSlangBadFormat            = 'Format must be set to an integer of zero or more';
$zmSlangBadFPSReportInterval = 'FPS report interval buffer count must be an integer of 100 or more';
$zmSlangBadFrameSkip         = 'Frame skip count must be an integer of zero or more';
$zmSlangBadHeight            = 'Height must be set to a valid value';
$zmSlangBadHost              = 'Host must be set to a valid ip address or hostname, do not include http://';
$zmSlangBadImageBufferCount  = 'Image buffer size must be an integer of 10 or more';
$zmSlangBadLabelX            = 'Label X co-ordinate must be set to an integer of zero or more';
$zmSlangBadLabelY            = 'Label Y co-ordinate must be set to an integer of zero or more';
$zmSlangBadMaxFPS            = 'Maximum FPS must be a positive integer or floating point value';
$zmSlangBadNameChars         = 'Names may only contain alphanumeric characters plus hyphen and underscore';
$zmSlangBadPath              = 'Path must be set to a valid value';
$zmSlangBadPort              = 'Port must be set to a valid number';
$zmSlangBadPostEventCount    = 'Post event image count must be an integer of zero or more';
$zmSlangBadPreEventCount     = 'Pre event image count must be at least zero, and less than image buffer size';
$zmSlangBadRefBlendPerc      = 'Reference blend percentage must be a positive integer';
$zmSlangBadSectionLength     = 'Section length must be an integer of 30 or more';
$zmSlangBadSignalCheckColour = 'Signal check colour must be a valid RGB colour string';
$zmSlangBadStreamReplayBuffer= 'Stream replay buffer must be an integer of zero or more';
$zmSlangBadWarmupCount       = 'Warmup frames must be an integer of zero or more';
$zmSlangBadWebColour         = 'Web colour must be a valid web colour string';
$zmSlangBadWidth             = 'Width must be set to a valid value';
$zmSlangBandwidth            = '帯域幅';
$zmSlangBlobPx               = 'ﾌﾞﾛﾌﾞ Px';
$zmSlangBlobs                = 'ﾌﾞﾛﾌﾞ';
$zmSlangBlobSizes            = 'ﾌﾞﾛﾌﾞ ｻｲｽﾞ';
$zmSlangBrightness           = '輝度';
$zmSlangBuffers              = 'ﾊﾞｯﾌｧ';
$zmSlangCanAutoFocus         = 'Can Auto Focus';
$zmSlangCanAutoGain          = 'Can Auto Gain';
$zmSlangCanAutoIris          = 'Can Auto Iris';
$zmSlangCanAutoWhite         = 'Can Auto White Bal.';
$zmSlangCanAutoZoom          = 'Can Auto Zoom';
$zmSlangCancel               = 'ｷｬﾝｾﾙ';
$zmSlangCancelForcedAlarm    = '強制ｱﾗｰﾑｷｬﾝｾﾙ';
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
$zmSlangCaptureHeight        = '取り込み高さ';
$zmSlangCapturePalette       = '取り込みﾊﾟﾚｯﾄ';
$zmSlangCaptureWidth         = '取り込み幅';
$zmSlangCause                = 'Cause';
$zmSlangCheckMethod          = 'ｱﾗｰﾑ ﾁｪｯｸ方法';
$zmSlangChooseFilter         = 'ﾌｨﾙﾀｰの選択';
$zmSlangChoosePreset         = 'Choose Preset';
$zmSlangClose                = '閉じる';
$zmSlangColour               = '色';
$zmSlangCommand              = 'Command';
$zmSlangConfig               = 'Config';
$zmSlangConfiguredFor        = '設定:';
$zmSlangConfirmDeleteEvents  = 'Are you sure you wish to delete the selected events?';
$zmSlangConfirmPassword      = 'ﾊﾟｽﾜｰﾄﾞの確認';
$zmSlangConjAnd              = '及び';
$zmSlangConjOr               = '又は';
$zmSlangConsole              = 'ｺﾝｿｰﾙ';
$zmSlangContactAdmin         = '管理者にお問い合わせください。';
$zmSlangContinue             = 'Continue';
$zmSlangContrast             = 'ｺﾝﾄﾗｽﾄ';
$zmSlangControlAddress       = 'Control Address';
$zmSlangControlCap           = 'Control Capability';
$zmSlangControlCaps          = 'Control Capabilities';
$zmSlangControl              = 'Control';
$zmSlangControlDevice        = 'Control Device';
$zmSlangControllable         = 'Controllable';
$zmSlangControlType          = 'Control Type';
$zmSlangCycle                = 'Cycle';
$zmSlangCycleWatch           = 'ｻｲｸﾙ観察';
$zmSlangDay                  = '曜日';
$zmSlangDebug                = 'Debug';
$zmSlangDefaultRate          = 'Default Rate';
$zmSlangDefaultScale         = 'Default Scale';
$zmSlangDefaultView          = 'Default View';
$zmSlangDelete               = '削除';
$zmSlangDeleteAndNext        = '次を削除';
$zmSlangDeleteAndPrev        = '前を削除';
$zmSlangDeleteSavedFilter    = '保存ﾌｨﾙﾀｰの削除';
$zmSlangDescription          = '説明';
$zmSlangDeviceChannel        = 'ﾃﾞﾊﾞｲｽ ﾁｬﾝﾈﾙ';
$zmSlangDeviceFormat         = 'ﾃﾞﾊﾞｲｽ ﾌｫｰﾏｯﾄ';
$zmSlangDeviceNumber         = 'ﾃﾞﾊﾞｲｽ番号';
$zmSlangDevicePath           = 'Device Path';
$zmSlangDevices              = 'Devices';
$zmSlangDimensions           = '寸法';
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
$zmSlangDuration             = '継続時間';
$zmSlangEdit                 = '編集';
$zmSlangEmail                = 'ﾒｰﾙ';
$zmSlangEnableAlarms         = 'Enable Alarms';
$zmSlangEnabled              = '使用可能\';
$zmSlangEnterNewFilterName   = '新しいﾌｨﾙﾀｰ名の入力';
$zmSlangErrorBrackets        = 'エラー、開き括弧と閉じ括弧の数が合っているのかを確認してください';
$zmSlangError                = 'エラー';
$zmSlangErrorValidValue      = 'エラー、全ての項の数値が有効かどうかを確認してください';
$zmSlangEtc                  = '等';
$zmSlangEvent                = 'ｲﾍﾞﾝﾄ';
$zmSlangEventFilter          = 'ｲﾍﾞﾝﾄ ﾌｨﾙﾀｰ';
$zmSlangEventId              = 'Event Id';
$zmSlangEventName            = 'Event Name';
$zmSlangEventPrefix          = 'Event Prefix';
$zmSlangEvents               = 'ｲﾍﾞﾝﾄ';
$zmSlangExclude              = '排除';
$zmSlangExecute              = 'Execute';
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
$zmSlangFastForward          = 'Fast Forward';
$zmSlangFeed                 = '送り込む';
$zmSlangFileColours          = 'File Colours';
$zmSlangFile                 = 'File';
$zmSlangFilePath             = 'File Path';
$zmSlangFilterArchiveEvents  = 'Archive all matches';
$zmSlangFilterDeleteEvents   = 'Delete all matches';
$zmSlangFilterEmailEvents    = 'Email details of all matches';
$zmSlangFilterExecuteEvents  = 'Execute command on all matches';
$zmSlangFilterMessageEvents  = 'Message details of all matches';
$zmSlangFilterPx             = 'ﾌｨﾙﾀｰ Px';
$zmSlangFilters              = 'Filters';
$zmSlangFilterUnset          = 'You must specify a filter width and height';
$zmSlangFilterUploadEvents   = 'Upload all matches';
$zmSlangFilterVideoEvents    = 'Create video for all matches';
$zmSlangFirst                = '最初';
$zmSlangFlippedHori          = 'Flipped Horizontally';
$zmSlangFlippedVert          = 'Flipped Vertically';
$zmSlangFocus                = 'Focus';
$zmSlangForceAlarm           = '強制ｱﾗｰﾑ';
$zmSlangFormat               = 'Format';
$zmSlangFPS                  = 'fps';
$zmSlangFPSReportInterval    = 'FPS報告間隔';
$zmSlangFrame                = 'ﾌﾚｰﾑ';
$zmSlangFrameId              = 'ﾌﾚｰﾑ ID';
$zmSlangFrameRate            = 'ﾌﾚｰﾑﾚｰﾄ';
$zmSlangFrames               = 'ﾌﾚｰﾑ';
$zmSlangFrameSkip            = 'ﾌﾚｰﾑｽｷｯﾌﾟ';
$zmSlangFTP                  = 'FTP';
$zmSlangFunc                 = '機能\';
$zmSlangFunction             = '機能\';
$zmSlangGain                 = 'Gain';
$zmSlangGeneral              = 'General';
$zmSlangGenerateVideo        = 'ﾋﾞﾃﾞｵの生成';
$zmSlangGeneratingVideo      = 'ﾋﾞﾃﾞｵ生成中';
$zmSlangGoToZoneMinder       = 'ZoneMinder.comに行く';
$zmSlangGrey                 = 'ｸﾞﾚｰ';
$zmSlangGroup                = 'Group';
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
$zmSlangHigh                 = '高';
$zmSlangHighBW               = '高帯域';
$zmSlangHome                 = 'Home';
$zmSlangHour                 = '時';
$zmSlangHue                  = '色相';
$zmSlangId                   = 'ID';
$zmSlangIdle                 = '待機状態';
$zmSlangIgnore               = '無視';
$zmSlangImageBufferSize      = '画像 ﾊﾞｯﾌｧ ｻｲｽﾞ';
$zmSlangImages               = 'Images';
$zmSlangImage                = '画像';
$zmSlangInclude              = '組み込む';
$zmSlangIn                   = 'In';
$zmSlangInverted             = '反転';
$zmSlangIris                 = 'Iris';
$zmSlangKeyString            = 'Key String';
$zmSlangLabel                = 'Label';
$zmSlangLanguage             = '言語';
$zmSlangLast                 = '最終';
$zmSlangLimitResultsPost     = 'results only;'; // This is used at the end of the phrase 'Limit to first N results only'
$zmSlangLimitResultsPre      = 'Limit to first'; // This is used at the beginning of the phrase 'Limit to first N results only'
$zmSlangLinkedMonitors       = 'Linked Monitors';
$zmSlangList                 = 'List';
$zmSlangLoad                 = 'Load';
$zmSlangLocal                = 'ﾛｰｶﾙ';
$zmSlangLoggedInAs           = 'ﾛｸﾞｲﾝ済み:';
$zmSlangLoggingIn            = 'ﾛｸﾞｲﾝ中';
$zmSlangLogin                = 'ﾛｸﾞｲﾝ';
$zmSlangLogout               = 'ﾛｸﾞｱｳﾄ';
$zmSlangLow                  = '低';
$zmSlangLowBW                = '低帯域';
$zmSlangMain                 = 'Main';
$zmSlangMan                  = 'Man';
$zmSlangManual               = 'Manual';
$zmSlangMark                 = '選択';
$zmSlangMaxBandwidth         = 'Max Bandwidth';
$zmSlangMaxBrScore           = '最高<br/>ｽｺｱｰ';
$zmSlangMaxFocusRange        = 'Max Focus Range';
$zmSlangMaxFocusSpeed        = 'Max Focus Speed';
$zmSlangMaxFocusStep         = 'Max Focus Step';
$zmSlangMaxGainRange         = 'Max Gain Range';
$zmSlangMaxGainSpeed         = 'Max Gain Speed';
$zmSlangMaxGainStep          = 'Max Gain Step';
$zmSlangMaximumFPS           = '最高 FPS';
$zmSlangMaxIrisRange         = 'Max Iris Range';
$zmSlangMaxIrisSpeed         = 'Max Iris Speed';
$zmSlangMaxIrisStep          = 'Max Iris Step';
$zmSlangMax                  = '最高';
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
$zmSlangMedium               = '中';
$zmSlangMediumBW             = '中帯域';
$zmSlangMinAlarmAreaLtMax    = 'Minimum alarm area should be less than maximum';
$zmSlangMinAlarmAreaUnset    = 'You must specify the minimum alarm pixel count';
$zmSlangMinBlobAreaLtMax     = '最低ﾌﾞﾛｯﾌﾞ範囲は最高値より以下でなければいけない';
$zmSlangMinBlobAreaUnset     = 'You must specify the minimum blob pixel count';
$zmSlangMinBlobLtMinFilter   = 'Minimum blob area should be less than or equal to minimum filter area';
$zmSlangMinBlobsLtMax        = '最低ﾌﾞﾛｯﾌﾞ数は最高数より以下でなければいけない';
$zmSlangMinBlobsUnset        = 'You must specify the minimum blob count';
$zmSlangMinFilterAreaLtMax   = 'Minimum filter area should be less than maximum';
$zmSlangMinFilterAreaUnset   = 'You must specify the minimum filter pixel count';
$zmSlangMinFilterLtMinAlarm  = 'Minimum filter area should be less than or equal to minimum alarm area';
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
$zmSlangMinPixelThresLtMax   = '最低ﾋﾟｸｾﾙ閾値は最高値より以下でなければいけない';
$zmSlangMinPixelThresUnset   = 'You must specify a minimum pixel threshold';
$zmSlangMinTiltRange         = 'Min Tilt Range';
$zmSlangMinTiltSpeed         = 'Min Tilt Speed';
$zmSlangMinTiltStep          = 'Min Tilt Step';
$zmSlangMinWhiteRange        = 'Min White Bal. Range';
$zmSlangMinWhiteSpeed        = 'Min White Bal. Speed';
$zmSlangMinWhiteStep         = 'Min White Bal. Step';
$zmSlangMinZoomRange         = 'Min Zoom Range';
$zmSlangMinZoomSpeed         = 'Min Zoom Speed';
$zmSlangMinZoomStep          = 'Min Zoom Step';
$zmSlangMisc                 = 'その他';
$zmSlangMonitor              = 'ﾓﾆﾀｰ';
$zmSlangMonitorIds           = 'ﾓﾆﾀｰ ID';
$zmSlangMonitorPresetIntro   = 'Select an appropriate preset from the list below.<br><br>Please note that this may overwrite any values you already have configured for this monitor.<br><br>';
$zmSlangMonitorPreset        = 'Monitor Preset';
$zmSlangMonitors             = 'ﾓﾆﾀｰ';
$zmSlangMontage              = 'ﾓﾝﾀｰｼﾞｭ';
$zmSlangMonth                = '月';
$zmSlangMove                 = 'Move';
$zmSlangMustBeGe             = '同等か以上でなければいけない';
$zmSlangMustBeLe             = '同等か以下でなければいけない';
$zmSlangMustConfirmPassword  = 'パスワードの確認をしてください';
$zmSlangMustSupplyPassword   = 'パスワードを入力してください';
$zmSlangMustSupplyUsername   = 'ユーザ名を入力してください';
$zmSlangName                 = '名前';
$zmSlangNear                 = 'Near';
$zmSlangNetwork              = 'ﾈｯﾄﾜｰｸ';
$zmSlangNewGroup             = 'New Group';
$zmSlangNewLabel             = 'New Label';
$zmSlangNewPassword          = '新しいﾊﾟｽﾜｰﾄﾞ';
$zmSlangNewState             = '新規状態';	
$zmSlangNewUser              = '新しいﾕｰｻﾞ';
$zmSlangNew                  = '新規';
$zmSlangNext                 = '次';
$zmSlangNo                   = 'いいえ';
$zmSlangNoFramesRecorded     = 'このｲﾍﾞﾝﾄのﾌﾚｰﾑは登録されていません';
$zmSlangNoGroup              = 'No Group';
$zmSlangNone                 = 'ありません';
$zmSlangNoneAvailable        = 'ありません';
$zmSlangNormal               = '普通';
$zmSlangNoSavedFilters       = '保存されたﾌｨﾙﾀｰはありません';
$zmSlangNoStatisticsRecorded = 'このｲﾍﾞﾝﾄ/ﾌﾚｰﾑの統計は登録されていません';
$zmSlangNotes                = 'Notes';
$zmSlangNumPresets           = 'Num Presets';
$zmSlangOff                  = 'Off';
$zmSlangOn                   = 'On';
$zmSlangOpen                 = 'Open';
$zmSlangOpEq                 = '同等';
$zmSlangOpGtEq               = '同等か以上';
$zmSlangOpGt                 = '以下';
$zmSlangOpIn                 = 'ｾｯﾄに入っている';
$zmSlangOpLtEq               = '同等か以下';
$zmSlangOpLt                 = '以下';
$zmSlangOpMatches            = '一致する';
$zmSlangOpNe                 = '同等でない';
$zmSlangOpNotIn              = 'ｾｯﾄに入っていない';
$zmSlangOpNotMatches         = '一致しない';
$zmSlangOptionHelp           = 'ｵﾌﾟｼｮﾝ ﾍﾙﾌﾟ';
$zmSlangOptionRestartWarning = 'この変更は起動中反映されない場合があります。\n変更してからZoneMinderを再起動してください。';
$zmSlangOptions              = 'ｵﾌﾟｼｮﾝ';
$zmSlangOrder                = 'Order';
$zmSlangOrEnterNewName       = '又は新しい名前を入力してください';
$zmSlangOrientation          = 'ｵﾘｵﾝﾃｰｼｮﾝ';
$zmSlangOut                  = 'Out';
$zmSlangOverwriteExisting    = '上書きします';
$zmSlangPaged                = 'ﾍﾟｰｼﾞ化';
$zmSlangPanLeft              = 'Pan Left';
$zmSlangPan                  = 'Pan';
$zmSlangPanRight             = 'Pan Right';
$zmSlangPanTilt              = 'Pan/Tilt';
$zmSlangParameter            = 'ﾊﾟﾗﾒｰﾀ';
$zmSlangPassword             = 'ﾊﾟｽﾜｰﾄﾞ';
$zmSlangPasswordsDifferent   = '新しいパスワードと再入力パスワードが一致しません';
$zmSlangPaths                = 'ﾊﾟｽ';
$zmSlangPause                = 'Pause';
$zmSlangPhoneBW              = '携帯用';
$zmSlangPhone                = 'Phone';
$zmSlangPixelDiff            = 'Pixel Diff';
$zmSlangPixels               = 'ﾋﾟｸｾﾙ';
$zmSlangPlayAll              = 'Play All';
$zmSlangPlay                 = 'Play';
$zmSlangPleaseWait           = 'お待ちください';
$zmSlangPoint                = 'Point';
$zmSlangPostEventImageBuffer = 'ｲﾍﾞﾝﾄ ｲﾒｰｼﾞ ﾊﾞｯﾌｧ後';
$zmSlangPreEventImageBuffer  = 'ｲﾍﾞﾝﾄ ｲﾒｰｼﾞ ﾊﾞｯﾌｧ前';
$zmSlangPreserveAspect       = 'Preserve Aspect Ratio';
$zmSlangPreset               = 'Preset';
$zmSlangPresets              = 'Presets';
$zmSlangPrev                 = '前';
$zmSlangRate                 = 'ﾚｰﾄ';
$zmSlangReal                 = '生中継';
$zmSlangRecord               = '録画';
$zmSlangRefImageBlendPct     = 'ｲﾒｰｼﾞ ﾌﾞﾚﾝﾄﾞ 参照 %';
$zmSlangRefresh              = '最新の情報に更新';
$zmSlangRemoteHostName       = 'ﾘﾓｰﾄ ﾎｽﾄ 名';
$zmSlangRemoteHostPath       = 'ﾘﾓｰﾄ ﾎｽﾄ ﾊﾟｽ';
$zmSlangRemoteHostPort       = 'ﾘﾓｰﾄ ﾎｽﾄ ﾎﾟｰﾄ';
$zmSlangRemoteImageColours   = 'ﾘﾓｰﾄ ｲﾒｰｼﾞ ｶﾗｰ';
$zmSlangRemote               = 'ﾘﾓｰﾄ';
$zmSlangRename               = '新しい名前をつける';
$zmSlangReplayAll            = 'All Events';
$zmSlangReplay               = '再生';
$zmSlangReplayGapless        = 'Gapless Events';
$zmSlangReplay               = 'Replay';
$zmSlangReplaySingle         = 'Single Event';
$zmSlangResetEventCounts     = 'ｲﾍﾞﾝﾄ ｶｳﾝﾄ ﾘｾｯﾄ';
$zmSlangReset                = 'Reset';
$zmSlangRestart              = '再起動';
$zmSlangRestarting           = '再起動中';
$zmSlangRestrictedCameraIds  = '制限されたｶﾒﾗ ID';
$zmSlangRestrictedMonitors   = 'Restricted Monitors';
$zmSlangReturnDelay          = 'Return Delay';
$zmSlangReturnLocation       = 'Return Location';
$zmSlangRewind               = 'Rewind';
$zmSlangRotateLeft           = '左に回転';
$zmSlangRotateRight          = '右に回転';
$zmSlangRunMode              = '起動ﾓｰﾄﾞ';
$zmSlangRunning              = '起動中';
$zmSlangRunState             = '起動状態';
$zmSlangSave                 = '保存';
$zmSlangSaveAs               = '名前をつけて保存';
$zmSlangSaveFilter           = 'ﾌｨﾙﾀｰを保存';
$zmSlangScale                = 'ｽｹｰﾙ';
$zmSlangScore                = 'ｽｺｱｰ';
$zmSlangSecs                 = '秒';
$zmSlangSectionlength        = '長さ';
$zmSlangSelectMonitors       = 'Select Monitors';
$zmSlangSelect               = 'Select';
$zmSlangSelfIntersecting     = 'Polygon edges must not intersect';
$zmSlangSetLearnPrefs        = 'Set Learn Prefs'; // 新しい設定の自動保存　This can be ignored for now
$zmSlangSetNewBandwidth      = '新しい帯域幅の設定';
$zmSlangSetPreset            = 'Set Preset';
$zmSlangSet                  = 'Set';
$zmSlangSettings             = '設定';
$zmSlangShowFilterWindow     = 'ﾌｨﾙﾀｰ ｳｲﾝﾄﾞｰの表示';
$zmSlangShowTimeline         = 'Show Timeline';
$zmSlangSignalCheckColour    = 'Signal Check Colour';
$zmSlangSize                 = 'Size';
$zmSlangSleep                = 'Sleep';
$zmSlangSortAsc              = 'Asc';
$zmSlangSortBy               = 'Sort by';
$zmSlangSortDesc             = 'Desc';
$zmSlangSource               = 'ｿｰｽ';
$zmSlangSourceType           = 'ｿｰｽ ﾀｲﾌﾟ';
$zmSlangSpeedHigh            = 'High Speed';
$zmSlangSpeedLow             = 'Low Speed';
$zmSlangSpeedMedium          = 'Medium Speed';
$zmSlangSpeed                = 'Speed';
$zmSlangSpeedTurbo           = 'Turbo Speed';
$zmSlangStart                = 'ｽﾀｰﾄ';
$zmSlangState                = '状態';
$zmSlangStats                = '統計';
$zmSlangStatus               = '状態';
$zmSlangStepBack             = 'Step Back';
$zmSlangStepForward          = 'Step Forward';
$zmSlangStepLarge            = 'Large Step';
$zmSlangStepMedium           = 'Medium Step';
$zmSlangStepNone             = 'No Step';
$zmSlangStepSmall            = 'Small Step';
$zmSlangStep                 = 'Step';
$zmSlangStills               = 'ｽﾁｰﾙ画像';
$zmSlangStop                 = '停止';
$zmSlangStopped              = '停止状態';
$zmSlangStreamReplayBuffer   = 'Stream Replay Image Buffer';
$zmSlangStream               = 'ｽﾄﾘｰﾑ';
$zmSlangSubmit               = 'Submit';
$zmSlangSystem               = 'ｼｽﾃﾑ';
$zmSlangTele                 = 'Tele';
$zmSlangThumbnail            = 'Thumbnail';
$zmSlangTilt                 = 'Tilt';
$zmSlangTime                 = '時間';
$zmSlangTimeDelta            = 'ﾃﾞﾙﾀ ﾀｲﾑ';
$zmSlangTimeline             = 'Timeline';
$zmSlangTimestamp            = 'ﾀｲﾑｽﾀﾝﾌﾟ';
$zmSlangTimeStamp            = 'ﾀｲﾑ ｽﾀﾝﾌﾟ';
$zmSlangTimestampLabelFormat = 'ﾀｲﾑｽﾀﾝﾌﾟ ﾗﾍﾞﾙ ﾌｫｰﾏｯﾄ';
$zmSlangTimestampLabelX      = 'ﾀｲﾑｽﾀﾝﾌﾟ ﾗﾍﾞﾙ X';
$zmSlangTimestampLabelY      = 'ﾀｲﾑｽﾀﾝﾌﾟ ﾗﾍﾞﾙ Y';
$zmSlangToday                = 'Today';
$zmSlangTools                = 'ﾂｰﾙ';
$zmSlangTotalBrScore         = '合計<br/>ｽｺｱｰ';
$zmSlangTrackDelay           = 'Track Delay';
$zmSlangTrackMotion          = 'Track Motion';
$zmSlangTriggers             = 'ﾄﾘｶﾞｰ';
$zmSlangTurboPanSpeed        = 'Turbo Pan Speed';
$zmSlangTurboTiltSpeed       = 'Turbo Tilt Speed';
$zmSlangType                 = 'ﾀｲﾌﾟ';
$zmSlangUnarchive            = '解凍';
$zmSlangUnits                = 'ﾕﾆｯﾄ';
$zmSlangUnknown              = '不明';
$zmSlangUpdateAvailable      = 'ZoneMinderのｱｯﾌﾟﾃﾞｰﾄがあります';
$zmSlangUpdateNotNecessary   = 'ｱｯﾌﾟﾃﾞｰﾄの必要はありません';
$zmSlangUpdate               = 'Update';
$zmSlangUseFilterExprsPost   = '&nbsp;ﾌｨﾙﾀｰ個数'; // This is used at the end of the phrase 'use N filter expressions'
$zmSlangUseFilterExprsPre    = '指定してください:&nbsp;'; // This is used at the beginning of the phrase 'use N filter expressions'
$zmSlangUseFilter            = 'ﾌｨﾙﾀｰを使用してください';
$zmSlangUsername             = 'ﾕｰｻﾞ名';
$zmSlangUsers                = 'ﾕｰｻﾞ';
$zmSlangUser                 = 'ﾕｰｻﾞ';
$zmSlangValue                = '数値';
$zmSlangVersion              = 'ﾊﾞｰｼﾞｮﾝ';
$zmSlangVersionIgnore        = 'このﾊﾞｰｼﾞｮﾝを無視';
$zmSlangVersionRemindDay     = '1日後に再度知らせる';
$zmSlangVersionRemindHour    = '1時間後に再度知らせる';
$zmSlangVersionRemindNever   = '新しいﾊﾞｰｼﾞｮﾝの知らせは必要ない';
$zmSlangVersionRemindWeek    = '1週間後に再度知らせる';
$zmSlangVideo                = 'ﾋﾞﾃﾞｵ';
$zmSlangVideoFormat          = 'Video Format';
$zmSlangVideoGenFailed       = 'ﾋﾞﾃﾞｵ生成の失敗！';
$zmSlangVideoGenFiles        = 'Existing Video Files';
$zmSlangVideoGenNoFiles      = 'No Video Files Found';
$zmSlangVideoGenParms        = 'ﾋﾞﾃﾞｵ生成 ﾊﾟﾗﾒｰﾀ';
$zmSlangVideoGenSucceeded    = 'Video Generation Succeeded!';
$zmSlangVideoSize            = 'ﾋﾞﾃﾞｵ ｻｲｽﾞ';
$zmSlangView                 = '表示';
$zmSlangViewAll              = '全部表示';
$zmSlangViewEvent            = 'View Event';
$zmSlangViewPaged            = 'ﾍﾟｰｼﾞ化の表示';
$zmSlangWake                 = 'Wake';
$zmSlangWarmupFrames         = 'ｳｫｰﾑｱｯﾌﾟ ﾌﾚｰﾑ';
$zmSlangWatch                = '監視';
$zmSlangWeb                  = 'ｳｪﾌﾞ';
$zmSlangWebColour            = 'Web Colour';
$zmSlangWeek                 = '週';
$zmSlangWhiteBalance         = 'White Balance';
$zmSlangWhite                = 'White';
$zmSlangWide                 = 'Wide';
$zmSlangX10ActivationString  = 'X10起動文字列';
$zmSlangX10InputAlarmString  = 'X10入力ｱﾗｰﾑ文字列';
$zmSlangX10OutputAlarmString = 'X10出力ｱﾗｰﾑ文字列';
$zmSlangX10                  = 'X10';
$zmSlangX                    = 'X';
$zmSlangYes                  = 'はい';
$zmSlangYouNoPerms           = 'この資源のｱｸｾｽ権がありません。';
$zmSlangY                    = 'Y';
$zmSlangZone                 = 'ｿﾞｰﾝ';
$zmSlangZoneAlarmColour      = 'ｱﾗｰﾑ ｶﾗｰ (Red/Green/Blue)';
$zmSlangZoneArea             = 'Zone Area';
$zmSlangZoneFilterSize       = 'Filter Width/Height (pixels)';
$zmSlangZoneMinMaxAlarmArea  = 'Min/Max Alarmed Area';
$zmSlangZoneMinMaxBlobArea   = 'Min/Max Blob Area';
$zmSlangZoneMinMaxBlobs      = 'Min/Max Blobs';
$zmSlangZoneMinMaxFiltArea   = 'Min/Max Filtered Area';
$zmSlangZoneMinMaxPixelThres = 'Min/Max Pixel Threshold (0-255)';
$zmSlangZoneOverloadFrames   = 'Overload Frame Ignore Count';
$zmSlangZones                = 'ｿﾞｰﾝ';
$zmSlangZoomIn               = 'Zoom In';
$zmSlangZoomOut              = 'Zoom Out';
$zmSlangZoom                 = 'Zoom';

// Complex replacements with formatting and/or placements, must be passed through sprintf
$zmClangCurrentLogin         = 'ただ今\'%1$s\がﾛｸﾞｲﾝしています';
$zmClangEventCount           = '%1$s %2$s';
$zmClangLastEvents           = '最終 %1$s %2$s';
$zmClangLatestRelease        = '最新ﾊﾞｰｼﾞｮﾝは v%1$s、ご利用ﾊﾞｰｼﾞｮﾝはv%2$s.';
$zmClangMonitorCount         = '%1$s %2$s';
$zmClangMonitorFunction      = 'ﾓﾆﾀｰ%1$s 機能\';
$zmClangRunningRecentVer     = 'あなたはZoneMinderの最新ﾊﾞｰｼﾞｮﾝ v%s.を使っています';

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
$zmVlangEvent                = array( 0=>'ｲﾍﾞﾝﾄ', 1=>'ｲﾍﾞﾝﾄ', 2=>'ｲﾍﾞﾝﾄ' );
$zmVlangMonitor              = array( 0=>'ﾓﾆﾀｰ', 1=>'ﾓﾆﾀｰ', 2=>'ﾓﾆﾀｰ' );

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
