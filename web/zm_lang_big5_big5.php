<?php
//
// ZoneMinder web Chinese Traditional language file, $Date$, $Revision$
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
setlocale( 'LC_ALL', 'Big5' ); //All locale settings pre-4.3.0
//setlocale( LC_ALL, 'Big5' ); //All locale settings 4.3.0 and after
setlocale( 'LC_CTYPE', 'Big5' ); //Character class settings 4.3.0 and after
setlocale( 'LC_TIME', 'Big5' ); //Date and time formatting 4.3.0 and after

// Simple String Replacements
$zmSlang24BitColour          = '24 位元色彩';
$zmSlang8BitGrey             = '8 位元灰階';
$zmSlangAction               = 'Action';
$zmSlangActual               = 'Actual';
$zmSlangAddNewControl        = '新增控制';
$zmSlangAddNewMonitor        = '新增監視';
$zmSlangAddNewUser           = '新增使用者';
$zmSlangAddNewZone           = '新增監視區';
$zmSlangAlarmBrFrames        = '警報<br/>框架';
$zmSlangAlarm                = '警報';
$zmSlangAlarmFrameCount      = '警報框架數';
$zmSlangAlarmFrame           = '警報框架';
$zmSlangAlarmLimits          = 'Alarm Limits';
$zmSlangAlarmMaximumFPS      = 'Alarm Maximum FPS';
$zmSlangAlarmPx              = 'Alarm Px';
$zmSlangAlarmRGBUnset        = 'You must set an alarm RGB colour';
$zmSlangAlert                = '警告';
$zmSlangAll                  = '全部';
$zmSlangApply                = '確定';
$zmSlangApplyingStateChange  = '確定狀態改變';
$zmSlangArchArchived         = 'Archived Only';
$zmSlangArchive              = '存檔';
$zmSlangArchived             = '已存檔';
$zmSlangArchUnarchived       = 'Unarchived Only';
$zmSlangArea                 = 'Area';
$zmSlangAreaUnits            = 'Area (px/%)';
$zmSlangAttrAlarmFrames      = 'Alarm Frames';
$zmSlangAttrArchiveStatus    = 'Archive Status';
$zmSlangAttrAvgScore         = 'Average Score';
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
$zmSlangAttrNotes            = 'Notes';
$zmSlangAttrTime             = 'Time';
$zmSlangAttrTotalScore       = 'Total Score';
$zmSlangAttrWeekday          = 'Weekday';
$zmSlangArchiveEvents    = '自動儲存符合項目';
$zmSlangDeleteEvents     = '自動刪除符合項目';
$zmSlangAuto                 = '自動';
$zmSlangEmailEvents      = '自動寄出詳細符合項目';
$zmSlangExecuteEvents    = '自動執行符合指令';
$zmSlangMessageEvents    = '自動發出符合訊息';
$zmSlangAutoStopTimeout      = '時間過自動停止';
$zmSlangUploadEvents     = '自動上傳符合項目';
$zmSlangVideoEvents      = '自動產生符合的影像檔';
$zmSlangAvgBrScore           = '平均<br/>分數';
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
$zmSlangBadPostEventCount    = 'Post event image buffer must be an integer of zero or more';
$zmSlangBadPreEventCount     = 'Pre event image buffer must be at least zero, and less than image buffer size';
$zmSlangBadRefBlendPerc      = 'Reference blendpercentage must be a positive integer';
$zmSlangBadSectionLength     = 'Section length must be an integer of 30 or more';
$zmSlangBadWarmupCount       = 'Warmup frames must be an integer of zero or more';
$zmSlangBadWebColour         = 'Web colour must be a valid web colour string';
$zmSlangBadWidth             = 'Width must be set to a valid value';
$zmSlangBandwidth            = '頻寬';
$zmSlangBlobPx               = 'Blob Px';
$zmSlangBlobs                = 'Blobs';
$zmSlangBlobSizes            = 'Blob Sizes';
$zmSlangBrightness           = '亮度';
$zmSlangBuffers              = '緩衝';
$zmSlangCanAutoFocus         = 'Can Auto Focus';
$zmSlangCanAutoGain          = 'Can Auto Gain';
$zmSlangCanAutoIris          = 'Can Auto Iris';
$zmSlangCanAutoWhite         = 'Can Auto White Bal.';
$zmSlangCanAutoZoom          = 'Can Auto Zoom';
$zmSlangCancel               = '取消';
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
$zmSlangCaptureHeight        = '捕捉高度';
$zmSlangCapturePalette       = '捕捉格式';
$zmSlangCaptureWidth         = '捕捉寬度';
$zmSlangCause                = '因素';
//$zmSlangCheckAll             = '全部勾選';
$zmSlangCheckMethod          = 'Alarm Check Method';
$zmSlangChooseFilter         = 'Choose Filter';
$zmSlangChoosePreset         = 'Choose Preset';
$zmSlangClose                = '關閉';
$zmSlangColour               = 'Colour';
$zmSlangCommand              = 'Command';
$zmSlangConfig               = 'Config';
$zmSlangConfiguredFor        = '配置為';
$zmSlangConfirmDeleteEvents  = 'Are you sure you wish to delete the selected events?';
$zmSlangConfirmPassword      = '確認密碼';
$zmSlangConjAnd              = 'and';
$zmSlangConjOr               = 'or';
$zmSlangConsole              = '操控台';
$zmSlangContactAdmin         = '請與系統管理者聯繫.';
$zmSlangContinue             = '連續';
$zmSlangContrast             = 'Contrast';
$zmSlangControlAddress       = 'Control Address';
$zmSlangControlCap           = 'Control Capability';
$zmSlangControlCaps          = 'Control Capabilities';
$zmSlangControl              = 'Control';
$zmSlangControlDevice        = 'Control Device';
$zmSlangControllable         = 'Controllable';
$zmSlangControlType          = 'Control Type';
$zmSlangCycle                = '分區輪流檢視';
$zmSlangCycleWatch           = '分區輪流檢視';
$zmSlangDay                  = '日';
$zmSlangDebug                = 'debug';
$zmSlangDefaultRate          = '預設速率';
$zmSlangDefaultScale         = '預設尺寸';
$zmSlangDelete               = '刪除';
$zmSlangDeleteAndNext        = '刪除 &amp; 下一事件';
$zmSlangDeleteAndPrev        = '刪除 &amp; 上一事件';
$zmSlangDeleteSavedFilter    = '刪除儲存過濾';
$zmSlangDescription          = '描述';
$zmSlangDeviceChannel        = '裝置通道';
$zmSlangDeviceFormat         = '裝置格式';
$zmSlangDeviceNumber         = '裝置編號';
$zmSlangDevicePath           = '裝置路徑';
$zmSlangDimensions           = '尺寸';
$zmSlangDisableAlarms        = '取消警報';
$zmSlangDisk                 = '磁碟';
$zmSlangDonateAlready        = 'No, I\'ve already donated';
$zmSlangDonateEnticement     = 'You\'ve been running ZoneMinder for a while now and hopefully are finding it a useful addition to your home or workplace security. Although ZoneMinder is, and will remain, free and open source, it costs money to develop and support. If you would like to help support future development and new features then please consider donating. Donating is, of course, optional but very much appreciated and you can donate as much or as little as you like.<br><br>If you would like to donate please select the option below or go to http://www.zoneminder.com/donate.html in your browser.<br><br>Thank you for using ZoneMinder and don\'t forget to visit the forums on ZoneMinder.com for support or suggestions about how to make your ZoneMinder experience even better.';
$zmSlangDonate               = 'Please Donate';
$zmSlangDonateRemindDay      = 'Not yet, remind again in 1 day';
$zmSlangDonateRemindHour     = 'Not yet, remind again in 1 hour';
$zmSlangDonateRemindMonth    = 'Not yet, remind again in 1 month';
$zmSlangDonateRemindNever    = 'No, I don\'t want to donate, never remind';
$zmSlangDonateRemindWeek     = 'Not yet, remind again in 1 week';
$zmSlangDonateYes            = 'Yes, I\'d like to donate now';
$zmSlangDownload             = '下載';
$zmSlangDuration             = '歷時';
$zmSlangEdit                 = '編輯';
$zmSlangEmail                = 'Email';
$zmSlangEnableAlarms         = '啟動警報';
$zmSlangEnabled              = '啟用';
$zmSlangEnterNewFilterName   = 'Enter new filter name';
$zmSlangErrorBrackets        = 'Error, please check you have an equal number of opening and closing brackets';
$zmSlangError                = '錯誤';
$zmSlangErrorValidValue      = 'Error, please check that all terms have a valid value';
$zmSlangEtc                  = 'etc';
$zmSlangEvent                = '事件';
$zmSlangEventFilter          = '事件過濾';
$zmSlangEventId              = '事件Id';
$zmSlangEventName            = '事件名稱';
$zmSlangEventPrefix          = '事件字首';
$zmSlangEvents               = '事件';
$zmSlangExclude              = '不包含';
$zmSlangExportDetails        = '輸出事件細項';
$zmSlangExport               = '輸出';
$zmSlangExportFailed         = '輸出失敗';
$zmSlangExportFormat         = '輸出檔案格式';
$zmSlangExportFormatTar      = 'Tar';
$zmSlangExportFormatZip      = 'Zip';
$zmSlangExportFrames         = '輸出框架細項';
$zmSlangExportImageFiles     = '輸出圖片檔';
$zmSlangExporting            = '輸出中';
$zmSlangExportMiscFiles      = '輸出其他檔(若有)';
$zmSlangExportOptions        = '輸出選項';
$zmSlangExportVideoFiles     = '輸出影片檔(若有)';
$zmSlangFar                  = 'Far';
$zmSlangFeed                 = 'Feed';
$zmSlangFileColours          = '檔案色彩';
$zmSlangFile                 = 'File';
$zmSlangFilePath             = '檔案路徑';
$zmSlangFilterPx             = 'Filter Px';
$zmSlangFilters              = '濾鏡';
$zmSlangFilterUnset          = '您必需設定濾鏡的寬度和高度';
$zmSlangFirst                = 'First';
$zmSlangFlippedHori          = '水平反轉';
$zmSlangFlippedVert          = '垂直反轉';
$zmSlangFocus                = 'Focus';
$zmSlangForceAlarm           = 'Force&nbsp;Alarm';
$zmSlangFormat               = '格式';
$zmSlangFPS                  = 'fps';
$zmSlangFPSReportInterval    = 'FPS 報告間距';
$zmSlangFrame                = '框架';
$zmSlangFrameId              = '框架 Id';
$zmSlangFrameRate            = '框架速率';
$zmSlangFrames               = '框架';
$zmSlangFrameSkip            = '框架忽略';
$zmSlangFTP                  = 'FTP';
$zmSlangFunc                 = 'Func';
$zmSlangFunction             = '功能';
$zmSlangGain                 = 'Gain';
$zmSlangGeneral              = '一般';
$zmSlangGenerateVideo        = '輸出影片';
$zmSlangGeneratingVideo      = '輸出影片中';
$zmSlangGoToZoneMinder       = 'Go to ZoneMinder.com';
$zmSlangGrey                 = 'Grey';
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
$zmSlangHighBW               = 'High&nbsp;B/W';
$zmSlangHigh                 = '高';
$zmSlangHome                 = 'Home';
$zmSlangHour                 = '時';
$zmSlangHue                  = 'Hue';
$zmSlangId                   = 'Id';
$zmSlangIdle                 = 'Idle';
$zmSlangIgnore               = 'Ignore';
$zmSlangImage                = '影像';
$zmSlangImageBufferSize      = '影像緩衝大小';
$zmSlangImages               = 'Images';
$zmSlangInclude              = '包含';
$zmSlangIn                   = 'In';
$zmSlangInverted             = '反轉';
$zmSlangIris                 = 'Iris';
$zmSlangLanguage             = '語言';
$zmSlangLast                 = 'Last';
$zmSlangLimitResultsPost     = 'results only;'; // This is used at the end of the phrase 'Limit to first N results only'
$zmSlangLimitResultsPre      = 'Limit to first'; // This is used at the beginning of the phrase 'Limit to first N results only'
$zmSlangLinkedMonitors       = 'Linked Monitors';
$zmSlangList                 = '列出';
$zmSlangLoad                 = '載入';
$zmSlangLocal                = 'Local';
$zmSlangLoggedInAs           = '登入名稱';
$zmSlangLoggingIn            = '登入中... 請稍後...';
$zmSlangLogin                = '登入';
$zmSlangLogout               = '登出';
$zmSlangLow                  = '低';
$zmSlangLowBW                = 'Low&nbsp;B/W';
$zmSlangMain                 = 'Main';
$zmSlangMan                  = 'Man';
$zmSlangManual               = 'Manual';
$zmSlangMark                 = '標註';
$zmSlangMaxBrScore           = '最高<br/>分數';
$zmSlangMaxFocusRange        = 'Max Focus Range';
$zmSlangMaxFocusSpeed        = 'Max Focus Speed';
$zmSlangMaxFocusStep         = 'Max Focus Step';
$zmSlangMaxGainRange         = 'Max Gain Range';
$zmSlangMaxGainSpeed         = 'Max Gain Speed';
$zmSlangMaxGainStep          = 'Max Gain Step';
$zmSlangMaximumFPS           = '最大每秒框架數 fps';
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
$zmSlangMedium               = '中';
$zmSlangMediumBW             = 'Medium&nbsp;B/W';
$zmSlangPixelDiff            = 'Pixel Diff';
$zmSlangRestrictedMonitors   = 'Restricted Monitors';
$zmSlangSelectMonitors       = 'Select Monitors';
$zmSlangMinBlobLtMinFilter   = 'Minimum blob area should be less than or equal to minimum filter area';
$zmSlangMinFilterLtMinAlarm  = 'Minimum filter area should be less than or equal to minimum alarm area';
$zmSlangMinAlarmAreaLtMax    = 'Minimum alarm area should be less than maximum';
$zmSlangMinAlarmAreaUnset    = 'You must specify the minimum alarm pixel count';
$zmSlangMinBlobAreaLtMax     = 'Minimum blob area should be less than maximum';
$zmSlangMinBlobAreaUnset     = 'You must specify the minimum blob pixel count';
$zmSlangMinBlobsLtMax        = 'Minimum blobs should be less than maximum';
$zmSlangMinBlobsUnset        = 'You must specify the minimum blob count';
$zmSlangMinFilterAreaLtMax   = 'Minimum filter area should be less than maximum';
$zmSlangMinFilterAreaUnset   = 'You must specify the minimum filter pixel count';
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
$zmSlangMinPixelThresLtMax   = 'Minimum pixel threshold should be less than maximum';
$zmSlangMinPixelThresUnset   = 'You must specify a minimum pixel threshold';
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
$zmSlangMinPixelThresLtMax   = 'Minimum pixel threshold should be less than maximum';
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
$zmSlangMisc                 = '細項';
$zmSlangMonitorIds           = 'Monitor&nbsp;Ids';
$zmSlangMonitor              = '監視';
$zmSlangMonitors             = '監視';
$zmSlangMonitorPreset        = 'Monitor Preset';
$zmSlangMonitorPresetIntro   = 'Select an appropriate preset from the list below.<br><br>Please note that this may overwrite any values you already have configured for this monitor.<br><br>';
$zmSlangMontage              = '全部顯示';
$zmSlangMonth                = '月';
$zmSlangMove                 = '移動';
$zmSlangMustBeGe             = '需大於或等於';
$zmSlangMustBeLe             = '需小於或等於';
$zmSlangMustConfirmPassword  = '您必需確認密碼';
$zmSlangMustSupplyPassword   = '您必需提供密碼';
$zmSlangMustSupplyUsername   = '您必需提供使用者名稱';
$zmSlangName                 = '名稱';
$zmSlangNear                 = 'Near';
$zmSlangNetwork              = 'Network';
$zmSlangNewGroup             = '新群組';
$zmSlangNew                  = '新增';
$zmSlangNewPassword          = '新密碼';
$zmSlangNewState             = '新狀態';
$zmSlangNewUser              = '新使用者';
$zmSlangNext                 = '下一步';
$zmSlangNoFramesRecorded     = 'There are no frames recorded for this event';
$zmSlangNoGroups             = 'No groups have been defined';
$zmSlangNoneAvailable        = 'None available';
$zmSlangNone                 = '無選取';
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
$zmSlangOptions              = '銓垣專用';//進階選項
$zmSlangOrder                = '順序';
$zmSlangOrEnterNewName       = 'or enter new name';
$zmSlangOrientation          = '方向';
$zmSlangOut                  = 'Out';
$zmSlangOverwriteExisting    = 'Overwrite Existing';
$zmSlangPaged                = 'Paged';
$zmSlangPanLeft              = 'Pan Left';
$zmSlangPan                  = 'Pan';
$zmSlangPanRight             = 'Pan Right';
$zmSlangPanTilt              = 'Pan/Tilt';
$zmSlangParameter            = '參數';
$zmSlangPassword             = '密碼';
$zmSlangPasswordsDifferent   = 'The new and confirm passwords are different';
$zmSlangPaths                = 'Paths';
$zmSlangPhoneBW              = 'Phone&nbsp;B/W';
$zmSlangPhone                = 'Phone';
$zmSlangPixels               = 'pixels';
$zmSlangPlayAll              = '全部播放';
$zmSlangPleaseWait           = 'Please Wait';
$zmSlangPoint                = '點';
$zmSlangPostEventImageBuffer = '後置事件影像緩衝';
$zmSlangPreEventImageBuffer  = '前置事件影像緩衝';
$zmSlangPreset               = 'Preset';
$zmSlangPresets              = 'Presets';
$zmSlangPrev                 = '上一事件';
$zmSlangRate                 = 'Rate';
$zmSlangReal                 = 'Real';
$zmSlangRecord               = '錄影';
$zmSlangRefImageBlendPct     = '參考影像混合 %ge';
$zmSlangRefresh              = '更新';
$zmSlangRemoteHostName       = '遠端主機名稱';
$zmSlangRemoteHostPath       = '遠端主機路徑';
$zmSlangRemoteHostPort       = '遠端主機端口';
$zmSlangRemoteImageColours   = 'Remote Image Colours';
$zmSlangRemote               = 'Remote';
$zmSlangRename               = '重新命名';
$zmSlangReplay               = '重新播放';
$zmSlangResetEventCounts     = 'Reset Event Counts';
$zmSlangReset                = 'Reset';
$zmSlangRestarting           = 'Restarting';
$zmSlangRestart              = '重新啟動';
$zmSlangRestrictedCameraIds  = 'Restricted Camera Ids';
$zmSlangReturnDelay          = 'Return Delay';
$zmSlangReturnLocation       = 'Return Location';
$zmSlangRotateLeft           = 'Rotate Left';
$zmSlangRotateRight          = 'Rotate Right';
$zmSlangRunMode              = '監視模式';
$zmSlangRunning              = '運行中';
$zmSlangRunState             = '運作狀態';
$zmSlangSaveAs               = '儲存為';
$zmSlangSaveFilter           = 'Save Filter';
$zmSlangSave                 = '存檔';
$zmSlangScale                = 'Scale';
$zmSlangScore                = '分數';
$zmSlangSecs                 = 'Secs';
$zmSlangSectionlength        = '片段長度';
$zmSlangSelect               = '選取';
$zmSlangSelfIntersecting     = 'Polygon edges must not intersect';
$zmSlangSetLearnPrefs        = 'Set Learn Prefs'; // This can be ignored for now
$zmSlangSetNewBandwidth      = '設定新頻寬速度';
$zmSlangSetPreset            = 'Set Preset';
$zmSlangSet                  = 'Set';
$zmSlangSettings             = 'Settings';
$zmSlangShowFilterWindow     = '顯示過濾視窗';
$zmSlangShowTimeline         = 'Show Timeline';
$zmSlangSize                 = 'Size';
$zmSlangSleep                = 'Sleep';
$zmSlangSortAsc              = 'Asc';
$zmSlangSortBy               = 'Sort by';
$zmSlangSortDesc             = 'Desc';
$zmSlangSource               = '來源';
$zmSlangSourceType           = '來源形式';
$zmSlangSpeedHigh            = '高 速';
$zmSlangSpeedLow             = '低 速';
$zmSlangSpeedMedium          = '中速';
$zmSlangSpeed                = '速度';
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
$zmSlangStills               = '靜止';
$zmSlangStopped              = '已停止';
$zmSlangStop                 = '停止';
$zmSlangStream               = '串流';
$zmSlangSubmit               = 'Submit';
$zmSlangSystem               = 'System';
$zmSlangTele                 = 'Tele';
$zmSlangThumbnail            = '小圖檢視';
$zmSlangTilt                 = 'Tilt';
$zmSlangTimeDelta            = 'Time Delta';
$zmSlangTimestampLabelFormat = '時間標示格式';
$zmSlangTimestampLabelX      = '時間標示 X';
$zmSlangTimestampLabelY      = '時間標示 Y';
$zmSlangTimestamp            = '時間格式';
$zmSlangTime                 = '時間';
$zmSlangToday                = 'Today';
$zmSlangTools                = 'Tools';
$zmSlangTotalBrScore         = '全部<br/>分數';
$zmSlangTrackDelay           = 'Track Delay';
$zmSlangTrackMotion          = 'Track Motion';
$zmSlangTriggers             = '觸發';
$zmSlangTurboPanSpeed        = 'Turbo Pan Speed';
$zmSlangTurboTiltSpeed       = 'Turbo Tilt Speed';
$zmSlangType                 = 'Type';
$zmSlangUnarchive            = '不存檔';
$zmSlangUnits                = 'Units';
$zmSlangUnknown              = 'Unknown';
$zmSlangUpdateAvailable      = 'An update to ZoneMinder is available.';
$zmSlangUpdateNotNecessary   = 'No update is necessary.';
$zmSlangUseFilterExprsPost   = '&nbsp;filter&nbsp;expressions'; // This is used at the end of the phrase 'use N filter expressions'
$zmSlangUseFilterExprsPre    = 'Use&nbsp;'; // This is used at the beginning of the phrase 'use N filter expressions'
$zmSlangUseFilter            = 'Use Filter';
$zmSlangUsername             = '使用者名稱';
$zmSlangUsers                = 'Users';
$zmSlangUser                 = 'User';
$zmSlangValue                = '設定值';
$zmSlangVersionIgnore        = 'Ignore this version';
$zmSlangVersionRemindDay     = 'Remind again in 1 day';
$zmSlangVersionRemindHour    = 'Remind again in 1 hour';
$zmSlangVersionRemindNever   = 'Don\'t remind about new versions';
$zmSlangVersionRemindWeek    = 'Remind again in 1 week';
$zmSlangVersion              = '版本';
$zmSlangVideoGenFailed       = '輸出影片失敗!';
$zmSlangVideoGenParms        = '輸出影片參數';
$zmSlangVideoSize            = '影片尺寸';
$zmSlangVideo                = 'Video';
$zmSlangViewAll              = '全部檢視';
$zmSlangViewPaged            = '分頁檢視';
$zmSlangView                 = '檢視';
$zmSlangWake                 = 'Wake';
$zmSlangWarmupFrames         = '熱機框架';
$zmSlangWatch                = 'Watch';
$zmSlangWeb                  = 'Web';
$zmSlangWeek                 = '週';
$zmSlangWhiteBalance         = 'White Balance';
$zmSlangWhite                = 'White';
$zmSlangWide                 = 'Wide';
$zmSlangX                    = 'X';
$zmSlangX10ActivationString  = 'X10 Activation String';
$zmSlangX10InputAlarmString  = 'X10 Input Alarm String';
$zmSlangX10OutputAlarmString = 'X10 Output Alarm String';
$zmSlangX10                  = 'X10';
$zmSlangYes                  = 'Yes';
$zmSlangYouNoPerms           = 'You do not have permissions to access this resource.';
$zmSlangZoneArea             = 'Zone Area';
$zmSlangZoneAlarmColour      = 'Alarm Colour (Red/Green/Blue)';
$zmSlangZoneFilterSize       = 'Filter Width/Height (pixels)';
$zmSlangZoneMinMaxAlarmArea  = 'Min/Max Alarmed Area';
$zmSlangZoneMinMaxBlobArea   = 'Min/Max Blob Area';
$zmSlangZoneMinMaxBlobs      = 'Min/Max Blobs';
$zmSlangZoneMinMaxFiltArea   = 'Min/Max Filtered Area';
$zmSlangZoneMinMaxPixelThres = 'Min/Max Pixel Threshold (0-255)';
$zmSlangZoneAlarmColour      = 'Alarm Colour (RGB)';
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
$zmSlangZoneMinPixelThres    = 'Minimum Pixel Threshold (0-255)';
$zmSlangZoneMinX             = 'Minimum X (left)';
$zmSlangZoneMinY             = 'Minimum Y (top)';
$zmSlangZones                = '監視區';
$zmSlangZone                 = 'Zone';
$zmSlangZoom                 = 'Zoom';

// Complex replacements with formatting and/or placements, must be passed through sprintf
$zmClangCurrentLogin         = '目前登入者是 \'%1$s\'';
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
$zmVlangEvent                = array( 0=>'事件', 1=>'事件', 2=>'事件' );
$zmVlangMonitor              = array( 0=>'監視', 1=>'監視', 2=>'監視' );

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
