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
    'SystemLog'             => '系統日誌',
    'DateTime'              => '日期/時間',
    'Component'             => '元件',
    'Pid'                   => 'PID',
    'Level'                 => '等級',
    'Message'               => '訊息',
    'Line'                  => 'Line',
    'More'                  => 'More',
    'Clear'                 => '清除',
    '24BitColour'           => '24 bit 彩色',
    '32BitColour'           => '32 bit 彩色',
    '8BitGrey'              => '8 bit 灰階',
    'Action'                => 'Action',
    'Actual'                => '實際',
    'AddNewControl'         => '增加新控制',
    'AddNewMonitor'         => '新增',
    'AddMonitorDisabled'    => 'Your user is not allowed to add a new monitor',
    'AddNewServer'          => '增加新伺服器',
    'AddNewStorage'         => '增加新儲存',
    'AddNewUser'            => '增加新使用者',
    'AddNewZone'            => '增加新區域',
    'Alarm'                 => '警報',
    'AlarmBrFrames'         => '警報<br/>Frames',
    'AlarmFrame'            => '警報 Frame',
    'AlarmFrameCount'       => '警報 Frame Count',
    'AlarmLimits'           => '警報 Limits',
    'AlarmMaximumFPS'       => '警報最大 FPS',
    'AlarmPx'               => '警報 Px',
    'AlarmRefImageBlendPct' => '警報 Reference Image Blend %ge',
    'AlarmRGBUnset'         => '你必須設定警報的 RGB 顏色',
    'Alert'                 => '警告',
    'All'                   => '全部',
    'AllTokensRevoked'      => '全部 Tokens 已撤銷',
    'AnalysisFPS'           => 'Analysis FPS',
    'AnalysisUpdateDelay'   => 'Analysis Update Delay',
    'API'                   => 'API',
    'APIEnabled'            => 'API 已啟用',
    'Apply'                 => '確定',
    'ApplyingStateChange'   => 'Applying State Change',
    'ArchArchived'          => 'Archived Only',
    'Archive'               => '封存',
    'Archived'              => '已封存',
    'ArchUnarchived'        => 'Unarchived Only',
    'Area'                  => '區域',
    'AreaUnits'             => '區域 (px/%)',
    'AttrAlarmFrames'       => 'Alarm Frames',
    'AttrArchiveStatus'     => 'Archive Status',
    'AttrAvgScore'          => '平均分數',
    'AttrCause'             => 'Cause',
    'AttrStartDate'         => '開始日期',
    'AttrEndDate'           => '結束日期',
    'AttrStartDateTime'     => '開始 Date/Time',
    'AttrEndDateTime'       => '結束 Date/Time',
    'AttrDiskSpace'         => '磁碟空間',
    'AttrDiskBlocks'        => '磁碟 Blocks',
    'AttrDiskPercent'       => '磁碟 Percent',
    'AttrDuration'          => '歷時',
    'AttrFrames'            => 'Frames',
    'AttrId'                => 'Id',
    'AttrMaxScore'          => '最高分數',
    'AttrMonitorId'         => '監視器 Id',
    'AttrMonitorName'       => '監視器名稱',
    'AttrSecondaryStorageArea' => '次要儲存區',
    'AttrStorageArea'       => '儲存區',
    'AttrFilterServer'      => 'Server Filter is Running On',
    'AttrMonitorServer'     => 'Server Monitor is Running On',
    'AttrStorageServer'     => 'Server Hosting Storage',
    'AttrStateId'           => '運行狀態',
    'AttrName'              => '名稱',
    'AttrNotes'             => '備註',
    'AttrSystemLoad'        => '系統負載',
    'AttrStartTime'         => '開始時間',
    'AttrEndTime'           => '結束時間',
    'AttrTotalScore'        => '總分數',
    'AttrStartWeekday'      => '開始 Weekday',
    'AttrEndWeekday'        => '結束 Weekday',
    'Auto'                  => '自動',
    'AutoStopTimeout'       => '超時自動停止',
    'Available'             => 'Available',
    'AvgBrScore'            => '平均<br/>分數',
    'Available'             => 'Available',
    'Background'            => '背景',
    'BackgroundFilter'      => 'Run filter in background',
    'BadAlarmFrameCount'    => '警報 frame count must be an integer of one or more',
    'BadAlarmMaxFPS'        => '警報最大 FPS must be a positive integer or floating point value',
    'BadAnalysisFPS'        => 'Analysis FPS must be a positive integer or floating point value',
    'BadAnalysisUpdateDelay'=> 'Analysis update delay must be set to an integer of zero or more',
    'BadChannel'            => 'Channel must be set to an integer of zero or more',
    'BadDevice'             => 'Device must be set to a valid value',
    'BadFormat'             => 'Format must be set to a valid value',
    'BadFPSReportInterval'  => 'FPS report interval buffer count must be an integer of 0 or more',
    'BadFrameSkip'          => 'Frame skip count must be an integer of zero or more',
    'BadMotionFrameSkip'    => 'Motion Frame skip count must be an integer of zero or more',
    'BadHeight'             => '高度必須設定正確的值',
    'BadHost'               => 'Host must be set to a valid ip address or hostname, do not include http://',
    'BadImageBufferCount'   => 'Image buffer size must be an integer of 10 or more',
    'BadLabelX'             => 'Label X co-ordinate must be set to an integer of zero or more',
    'BadLabelY'             => 'Label Y co-ordinate must be set to an integer of zero or more',
    'BadMaxFPS'             => 'Maximum FPS must be a positive integer or floating point value',
    'BadNameChars'          => 'Names may only contain alphanumeric characters plus spaces, hyphen and underscore',
    'BadPalette'            => 'Palette must be set to a valid value',
    'BadColours'            => 'Target colour must be set to a valid value',
    'BadPath'               => 'Path must be set to a valid value',
    'BadPort'               => 'Port must be set to a valid number',
    'BadPostEventCount'     => 'Post event image count must be an integer of zero or more',
    'BadPreEventCount'      => 'Pre event image count must be at least zero, and less than image buffer size',
    'BadRefBlendPerc'       => 'Reference blend percentage must be a positive integer',
    'BadNoSaveJPEGsOrVideoWriter' => 'SaveJPEGs and VideoWriter are both set to disabled.  Nothing will be recorded!',
    'BadSectionLength'      => 'Section length must be an integer of 30 or more',
    'BadSignalCheckColour'  => 'Signal check colour must be a valid RGB colour string',
    'BadStreamReplayBuffer' => 'Stream replay buffer must be an integer of zero or more',
    'BadSourceType'         => 'Source Type \"Web Site\" requires the Function to be set to \"Monitor\"',
    'BadWarmupCount'        => 'Warmup frames must be an integer of zero or more',
    'BadWebColour'          => 'Web colour must be a valid web colour string',
    'BadWebSitePath'        => '請輸入完整的網站 url, 包含 http:// 或 https:// 開頭.',
    'BadWidth'              => '寬度必須設定正確的值',
    'Bandwidth'             => '頻寬',
    'BandwidthHead'         => '頻寬',	// This is the end of the bandwidth status on the top of the console, different in many language due to phrasing
    'BlobPx'                => 'Blob Px',
    'Blobs'                 => 'Blobs',
    'BlobSizes'             => 'Blob Sizes',
    'Brightness'            => '亮度',
    'Buffer'                => '緩衝',
    'Buffers'               => '緩衝',
    'CanAutoFocus'          => '可自動對焦',
    'CanAutoGain'           => '可自動 Gain',
    'CanAutoIris'           => '可自動 Iris',
    'CanAutoWhite'          => '可自動白平衡',
    'CanAutoZoom'           => '可自動縮放',
    'Cancel'                => '取消',
    'CancelForcedAlarm'     => '取消強制警報',
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
    'CanPan'                => '可平移' ,
    'CanReset'              => 'Can Reset',
    'CanReboot'             => 'Can Reboot',
    'CanSetPresets'         => 'Can Set Presets',
    'CanSleep'              => '可睡眠',
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
    'CaptureHeight'         => 'Capture 高度',
    'CaptureMethod'         => 'Capture Method',
    'CaptureResolution'     => 'Capture Resolution',
    'CapturePalette'        => 'Capture Palette',
    'CaptureWidth'          => 'Capture 寬度',
    'Cause'                 => 'Cause',
    'CheckMethod'           => 'Alarm Check Method',
    'ChooseDetectedCamera'  => 'Choose Detected Camera',
    'ChooseDetectedProfile' => 'Choose Detected Profile',
    'ChooseFilter'          => 'Choose Filter',
    'ChooseLogFormat'       => 'Choose a log format',
    'ChooseLogSelection'    => 'Choose a log selection',
    'ChoosePreset'          => 'Choose Preset',
    'CloneMonitor'          => '複製',
    'Close'                 => '關閉',
    'Colour'                => '顏色',
    'Command'               => 'Command',
    'ConcurrentFilter'      => 'Run filter concurrently',
    'Config'                => '設定',
    'ConfiguredFor'         => 'Configured for',
    'ConfirmDeleteEvents'   => 'Are you sure you wish to delete the selected events?',
    'ConfirmPassword'       => '確認密碼',
    'ConjAnd'               => '且',
    'ConjOr'                => '或',
    'Console'               => '主控台',
    'ContactAdmin'          => '詳細情形請聯繫管理員.',
    'Continue'              => '繼續',
    'Contrast'              => 'Contrast',
    'ControlAddress'        => 'Control Address',
    'ControlCap'            => 'Control Capability',
    'ControlCaps'           => 'Control Capabilities',
    'Control'               => '控制',
    'ControlDevice'         => 'Control Device',
    'Controllable'          => 'Controllable',
    'ControlType'           => 'Control Type',
    'Current'               => '目前',
    'Cycle'                 => '循環',
    'CycleWatch'            => 'Cycle Watch',
    'Day'                   => '天',
    'Debug'                 => '除錯',
    'DefaultRate'           => '預設 Rate',
    'DefaultScale'          => '預設 Scale',
    'DefaultCodec'          => '預設 Method For Live View',
    'DefaultView'           => '預設 View',
    'Deinterlacing'         => 'Deinterlacing',
    'RTSPDescribe'          => 'Use RTSP Response Media URL',
    'Delay'                 => 'Delay',
    'DeleteAndNext'         => 'Delete &amp; Next',
    'DeleteAndPrev'         => 'Delete &amp; Prev',
    'Delete'                => '刪除',
    'DeleteSavedFilter'     => 'Delete saved filter',
    'Description'           => '描述',
    'DetectedCameras'       => 'Detected Cameras',
    'DetectedProfiles'      => 'Detected Profiles',
    'DeviceChannel'         => 'Device Channel',
    'DeviceFormat'          => 'Device Format',
    'DeviceNumber'          => 'Device Number',
    'DevicePath'            => 'Device Path',
    'Device'                => '設備',
    'Devices'               => '設備',
    'Dimensions'            => 'Dimensions',
    'DisableAlarms'         => '關閉報警',
    'Disk'                  => '磁碟',
    'Display'               => '顯示',
    'Displaying'            => 'Displaying',
    'DonateAlready'         => '否, 我已贊助過了',
    'DonateEnticement'      => 'You\'ve been running ZoneMinder for a while now and hopefully are finding it a useful addition to your home or workplace security. Although ZoneMinder is, and will remain, free and open source, it costs money to develop and support. If you would like to help support future development and new features then please consider donating. Donating is, of course, optional but very much appreciated and you can donate as much or as little as you like.<br/><br/>If you would like to donate please select the option below or go to <a href="https://zoneminder.com/donate/" target="_blank">https://zoneminder.com/donate/</a> in your browser.<br/><br/>Thank you for using ZoneMinder and don\'t forget to visit the forums on <a href="https://forums.zoneminder.com">ZoneMinder.com</a> for support or suggestions about how to make your ZoneMinder experience even better.',
    'Donate'                => '請贊助',
    'DonateRemindDay'       => '考慮中, 一天之後再提示',
    'DonateRemindHour'      => '考慮中, 一小時之後再提示',
    'DonateRemindMonth'     => '考慮中, 一個月後再提示',
    'DonateRemindNever'     => '否, 我不想贊助, 不要再提示',
    'DonateRemindWeek'      => '考慮中, 一週後再提示',
    'DonateYes'             => '好, 我想贊助',
    'DoNativeMotionDetection'=> 'Do Native Motion Detection',
    'Download'              => '下載',
    'DuplicateMonitorName'  => '監視器名稱重複',
    'Duration'              => '歷時',
    'Edit'                  => '編輯',
    'EditLayout'            => '編輯布局',
    'Email'                 => 'Email',
    'EnableAlarms'          => '啟用警報',
    'Enabled'               => '已啟用',
    'EnterNewFilterName'    => '輸入新的 filter 名稱',
    'ErrorBrackets'         => '錯誤, please check you have an equal number of opening and closing brackets',
    'Error'                 => '錯誤',
    'ErrorValidValue'       => '錯誤, please check that all terms have a valid value',
    'Etc'                   => 'etc',
    'Event'                 => '事件',
    'EventFilter'           => '事件 Filter',
    'EventId'               => '事件 Id',
    'EventName'             => '事件名稱',
    'EventPrefix'           => '事件 Prefix',
    'Events'                => '事件',
    'Exclude'               => '排除',
    'Execute'               => '執行',
    'ExportCompress'        => '使用壓縮',
    'ExportDetails'         => '匯出事件 Details',
    'ExportMatches'         => '匯出 Matches',
    'Exif'                  => '嵌入 EXIF 資料到圖片',
    'Export'                => '匯出',
    'DownloadVideo'         => '下載影片',
    'GenerateDownload'      => 'Generate Download',
    'ExportFailed'          => '匯出失敗',
    'ExportFormat'          => '匯出檔案格式',
    'ExportFormatTar'       => 'Tar',
    'ExportFormatZip'       => 'Zip',
    'ExportFrames'          => '匯出 Frame Details',
    'ExportImageFiles'      => '匯出圖片檔案',
    'ExportLog'             => '匯出日誌',
    'Exporting'             => '匯出中',
    'ExportMiscFiles'       => '匯出其他檔案 (如果存在)',
    'ExportOptions'         => '匯出選項',
    'ExportSucceeded'       => '匯出已成功',
    'ExportVideoFiles'      => '匯出影片檔案 (如果存在)',
    'Far'                   => 'Far',
    'FastForward'           => '快速前進',
    'Feed'                  => 'Feed',
    'Ffmpeg'                => 'FFmpeg',
    'File'                  => '檔案',
    'FilterArchiveEvents'   => 'Archive all matches',
    'FilterUpdateDiskSpace' => '更新磁碟已使用空間',
    'FilterDeleteEvents'    => 'Delete all matches',
    'FilterCopyEvents'      => 'Copy all matches',
    'FilterMoveEvents'      => 'Move all matches',
    'FilterEmailEvents'     => 'Email details of all matches',
    'FilterExecuteEvents'   => 'Execute command on all matches',
    'FilterLog'             => '過濾器日誌',
    'FilterMessageEvents'   => 'Message details of all matches',
    'FilterPx'              => '過濾器 Px',
    'Filter'                => '過濾器',
    'Filters'               => '過濾器',
    'FilterUnset'           => 'You must specify a filter 寬度與高度',
    'FilterUploadEvents'    => 'Upload all matches',
    'FilterVideoEvents'     => 'Create video for all matches',
    'First'                 => '開頭',
    'FlippedHori'           => '水平翻轉',
    'FlippedVert'           => '垂直翻轉',
    'FnNone'                => '無',
    'FnMonitor'             => '監視器',
    'FnModect'              => '運動偵測', // Motion Detection
    'FnRecord'              => '錄影',
    'FnMocord'              => '運動偵測與錄影', // Modect + Record
    'FnNodect'              => 'Nodect',
    'Focus'                 => '對焦',
    'ForceAlarm'            => '強制警報',
    'Format'                => '格式',
    'FPS'                   => 'fps',
    'FPSReportInterval'     => 'FPS Report Interval',
    'Frame'                 => 'Frame',
    'FrameId'               => 'Frame Id',
    'FrameRate'             => 'Frame Rate',
    'Frames'                => 'Frames',
    'FrameSkip'             => 'Frame Skip',
    'MotionFrameSkip'       => 'Motion Frame Skip',
    'FTP'                   => 'FTP',
    'Func'                  => '功能',
    'Function'              => '功能',
    'Gain'                  => '增益',
    'General'               => '一般',
    'GenerateVideo'         => '產製影片',
    'GeneratingVideo'       => '正在產製影片',
    'GoToZoneMinder'        => '前往 ZoneMinder.com',
    'Grey'                  => 'Grey',
    'Group'                 => '群組',
    'Groups'                => '群組',
    'HasFocusSpeed'         => '有對焦速度',
    'HasGainSpeed'          => '有增益速度',
    'HasHomePreset'         => 'Has Home Preset',
    'HasIrisSpeed'          => '有光圈速度',
    'HasPanSpeed'           => '有平移速度',
    'HasPresets'            => '有預設值',
    'HasTiltSpeed'          => '有傾斜速度',
    'HasTurboPan'           => '有快速平移',
    'HasTurboTilt'          => '有快速傾斜',
    'HasWhiteSpeed'         => '有白平衡速度',
    'HasZoomSpeed'          => '有縮放速度',
    'HighBW'                => '高&nbsp;B/W',
    'High'                  => '高',
    'Home'                  => 'Home',
    'Hostname'              => '主機名稱',
    'Hour'                  => '小時',
    'Hue'                   => '色調',
    'Id'                    => 'Id',
    'Idle'                  => 'Idle',
    'Ignore'                => '忽略',
    'ImageBufferSize'       => '圖片緩衝大小 (frames)',
    'Image'                 => '圖片',
    'Images'                => '圖片',
    'Include'               => '包含',
    'In'                    => 'In',
    'InvalidateTokens'      => 'Invalidate all generated tokens',
    'Inverted'              => 'Inverted',
    'Iris'                  => '光圈',
    'KeyString'             => 'Key String',
    'Label'                 => '標籤',
    'Language'              => '語言',
    'Last'                  => '最後',
    'Layout'                => '布局',
    'Libvlc'                => 'Libvlc',
    'LimitResultsPost'      => 'results only', // This is used at the end of the phrase 'Limit to first N results only'
    'LimitResultsPre'       => 'Limit to first', // This is used at the beginning of the phrase 'Limit to first N results only'
    'LinkedMonitors'        => 'Linked Monitors',
    'List'                  => 'List',
    'ListMatches'           => 'List Matches',
    'Load'                  => '負載',
    'Local'                 => '本機',
    'Log'                   => '日誌',
    'Logs'                  => '日誌',
    'Logging'               => '日誌',
    'LoggedInAs'            => '已登入為',
    'LoggingIn'             => '登入中',
    'Login'                 => '登入',
    'Logout'                => '登出',
    'LowBW'                 => '低&nbsp;B/W',
    'Low'                   => '低',
    'Main'                  => '主要',
    'Man'                   => '人',
    'Manual'                => '手動',
    'Mark'                  => '勾選',
    'MaxBandwidth'          => '最大頻寬',
    'MaxBrScore'            => '最高<br/>分數',
    'MaxFocusRange'         => '最大對焦範圍',
    'MaxFocusSpeed'         => '最大對焦速度',
    'MaxFocusStep'          => '最大對焦步進',
    'MaxGainRange'          => '最大增益範圍',
    'MaxGainSpeed'          => '最大增益速度',
    'MaxGainStep'           => '最大增益步進',
    'MaximumFPS'            => '最大 FPS',
    'MaxIrisRange'          => '最大光圈範圍',
    'MaxIrisSpeed'          => '最大光圈速度',
    'MaxIrisStep'           => '最大光圈步進',
    'Max'                   => '最大',
    'MaxPanRange'           => '最大平移範圍',
    'MaxPanSpeed'           => '最大平移速度',
    'MaxPanStep'            => '最大平移步進',
    'MaxTiltRange'          => '最大傾斜範圍',
    'MaxTiltSpeed'          => '最大傾斜速度',
    'MaxTiltStep'           => '最大傾斜步進',
    'MaxWhiteRange'         => '最大白平衡範圍',
    'MaxWhiteSpeed'         => '最大白平衡速度',
    'MaxWhiteStep'          => '最大白平衡步進',
    'MaxZoomRange'          => '最大縮放範圍',
    'MaxZoomSpeed'          => '最大縮放速度',
    'MaxZoomStep'           => '最大縮放步進',
    'MediumBW'              => '中&nbsp;B/W',
    'Medium'                => '中',
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
    'MinFocusRange'         => '最小對焦範圍',
    'MinFocusSpeed'         => '最小對焦速度',
    'MinFocusStep'          => '最小對焦步進',
    'MinGainRange'          => '最小增益範圍',
    'MinGainSpeed'          => '最小增益速度',
    'MinGainStep'           => '最小增益步進',
    'MinIrisRange'          => '最小光圈範圍',
    'MinIrisSpeed'          => '最小光圈速度',
    'MinIrisStep'           => '最小光圈步進',
    'MinPanRange'           => '最小平移範圍',
    'MinPanSpeed'           => '最小平移速度',
    'MinPanStep'            => '最小平移步進',
    'MinPixelThresLtMax'    => 'Minimum pixel threshold should be less than maximum',
    'MinPixelThresUnset'    => 'You must specify a minimum pixel threshold',
    'MinTiltRange'          => '最小傾斜範圍',
    'MinTiltSpeed'          => '最小傾斜速度',
    'MinTiltStep'           => '最小傾斜步進',
    'MinWhiteRange'         => '最小白平衡範圍',
    'MinWhiteSpeed'         => '最小白平衡速度',
    'MinWhiteStep'          => '最小白平衡步進',
    'MinZoomRange'          => '最小縮放範圍',
    'MinZoomSpeed'          => '最小縮放速度',
    'MinZoomStep'           => '最小縮放步進',
    'Misc'                  => '雜項',
    'Mode'                  => 'Mode',
    'MonitorIds'            => '監視器&nbsp;Ids',
    'Monitor'               => '監視器',
    'MonitorPresetIntro'    => 'Select an appropriate preset from the list below.<br/><br/>Please note that this may overwrite any values you already have configured for the current monitor.<br/><br/>',
    'MonitorPreset'         => '監視器 Preset',
    'MonitorProbeIntro'     => 'The list below shows detected analog and network cameras and whether they are already being used or available for selection.<br/><br/>Select the desired entry from the list below.<br/><br/>Please note that not all cameras may be detected and that choosing a camera here may overwrite any values you already have configured for the current monitor.<br/><br/>',
    'MonitorProbe'          => '監視器探測',
    'Monitors'              => '監視器',
    'Montage'               => 'Montage',
    'MontageReview'         => 'Montage Review',
    'Month'                 => '月',
    'Move'                  => '移動',
    'MtgDefault'            => '預設',              // Added 2013.08.15.
    'Mtg2widgrd'            => '2-wide grid',              // Added 2013.08.15.
    'Mtg3widgrd'            => '3-wide grid',              // Added 2013.08.15.
    'Mtg4widgrd'            => '4-wide grid',              // Added 2013.08.15.
    'Mtg3widgrx'            => '3-wide grid, scaled, enlarge on alarm',              // Added 2013.08.15.
    'MustBeGe'              => '必須是大於或等於',
    'MustBeLe'              => '必須是小於或等於',
    'MustConfirmPassword'   => '你必須確認密碼',
    'MustSupplyPassword'    => '你必須提供密碼',
    'MustSupplyUsername'    => '你必須提供使用者名稱',
    'Name'                  => '名稱',
    'Near'                  => '接近',
    'Network'               => '網路',
    'NewGroup'              => '新群組',
    'NewLabel'              => '新標籤',
    'New'                   => '新增',
    'NewPassword'           => '新密碼',
    'NewState'              => '新狀態',
    'NewUser'               => '新使用者',
    'Next'                  => '下一個',
    'NextMonitor'           => '下一個監視器',
    'NoDetectedCameras'     => 'No Detected Cameras',
    'NoDetectedProfiles'    => 'No Detected Profiles',
    'NoFramesRecorded'      => 'There are no frames recorded for this event',
    'NoGroup'               => 'No Group',
    'NoneAvailable'         => 'None available',
    'None'                  => 'None',
    'No'                    => 'No',
    'Normal'                => '正常',
    'NoSavedFilters'        => 'NoSavedFilters',
    'NoStatisticsRecorded'  => 'There are no statistics recorded for this event/frame',
    'Notes'                 => '備註',
    'NumPresets'            => 'Num Presets',
    'Off'                   => 'Off',
    'On'                    => 'On',
    'OnvifProbe'            => 'ONVIF',
    'OnvifProbeIntro'       => 'The list below shows detected ONVIF cameras and whether they are already being used or available for selection.<br/><br/>Select the desired entry from the list below.<br/><br/>Please note that not all cameras may be detected and that choosing a camera here may overwrite any values you already have configured for the current monitor.<br/><br/>',
    'OnvifCredentialsIntro' => 'Please supply user name and password for the selected camera.<br/>If no user has been created for the camera then the user given here will be created with the given password.<br/><br/>',
    'Open'                  => '開啟',
    'OpEq'                  => '等於',
    'OpGtEq'                => '大於或等於',
    'OpGt'                  => '大於',
    'OpIn'                  => '在列表中',
    'OpLtEq'                => '小於或等於',
    'OpLt'                  => '小於',
    'OpMatches'             => '符合',
    'OpNe'                  => '不等於',
    'OpNotIn'               => '不在列表中',
    'OpNotMatches'          => '不符合',
    'OpIs'                  => '是',
    'OpIsNot'               => '不是',
    'OpLike'                => '包含',
    'OpNotLike'             => '不包含',
    'OptionalEncoderParam'  => 'Optional Encoder Parameters',
    'OptionHelp'            => 'Option Help',
    'OptionRestartWarning'  => 'These changes may not come into effect fully\nwhile the system is running. When you have\nfinished making your changes please ensure that\nyou restart ZoneMinder.',
    'Options'               => '選項',
    'Order'                 => '排序',
    'OrEnterNewName'        => '或輸入新名稱',
    'Orientation'           => '方向',
    'Out'                   => 'Out',
    'OverwriteExisting'     => 'Overwrite Existing',
    'Paged'                 => 'Paged',
    'PanLeft'               => 'Pan Left',
    'Pan'                   => 'Pan',
    'PanRight'              => 'Pan Right',
    'PanTilt'               => 'Pan/Tilt',
    'Parameter'             => '參數',
    'ParentGroup'           => '上層群組',
    'Password'              => '密碼',
    'PasswordsDifferent'    => '新密碼與確認密碼不相同',
    'PathToIndex'           => 'Path To Index',
    'PathToZMS'             => 'Path To ZMS',
    'PathToApi'             => 'Path To Api',
    'Paths'                 => 'Paths',
    'Pause'                 => '暫停',
    'PauseCycle'            => '暫停循環',
    'PhoneBW'               => '電話&nbsp;B/W',
    'Phone'                 => '電話',
    'PixelDiff'             => 'Pixel Diff',
    'Pixels'                => '像素',
    'PlayAll'               => '播放全部',
    'Play'                  => '播放',
    'PlayCycle'             => 'Play Cycle',
    'Plugins'               => 'Plugins',
    'PleaseWait'            => '請稍後',
    'Point'                 => 'Point',
    'PostEventImageBuffer'  => 'Post Event Image Count',
    'PreEventImageBuffer'   => 'Pre Event Image Count',
    'PreserveAspect'        => 'Preserve Aspect Ratio',
    'Preset'                => '預設',
    'Presets'               => '預設',
    'Prev'                  => '前一個',
    'PreviousMonitor'       => '前一個監視器',
    'Privacy'               => 'Privacy',
    'PrivacyAbout'          => '關於',
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
    'Probe'                 => '探測',
    'ProfileProbe'          => 'Stream 探測',
    'ProfileProbeIntro'     => 'The list below shows the existing stream profiles of the selected camera .<br/><br/>Select the desired entry from the list below.<br/><br/>Please note that ZoneMinder cannot configure additional profiles and that choosing a camera here may overwrite any values you already have configured for the current monitor.<br/><br/>',
    'Progress'              => 'Progress',
    'Protocol'              => '通訊協定',
    'Rate'                  => 'Rate',
    'RecaptchaWarning'      => 'Your reCaptcha secret key is invalid. Please correct it, or reCaptcha will not work', // added Sep 24 2015 - PP
	'RecordAudio'			=> 'Whether to store the audio stream when saving an event.',
    'Real'                  => 'Real',
    'Record'                => '紀錄',
    'RefImageBlendPct'      => 'Reference Image Blend %ge',
    'Refresh'               => '重新整理',
    'RemoteHostName'        => '主機名稱',
    'RemoteHostPath'        => '路徑',
    'RemoteHostSubPath'     => '子路徑',
    'RemoteHostPort'        => '通訊埠',
    'RemoteImageColours'    => '圖片顏色',
    'RemoteMethod'          => 'Method',
    'RemoteProtocol'        => '通訊協定',
    'Remote'                => '遠端',
    'Rename'                => '重新命名',
    'ReplayAll'             => '全部事件',
    'ReplayGapless'         => '無間隙事件',
    'Replay'                => 'Replay',
    'ReplaySingle'          => 'Single 事件',
    'ReportEventAudit'      => '稽核事件報告',
    'ResetEventCounts'      => '重置事件計數',
    'Reset'                 => '重置',
    'Restarting'            => '重新啟動中',
    'Restart'               => '重新啟動',
    'RestrictedCameraIds'   => 'Restricted Camera Ids',
    'RestrictedMonitors'    => 'Restricted Monitors',
    'ReturnDelay'           => 'Return Delay',
    'ReturnLocation'        => 'Return Location',
    'RevokeAllTokens'       => '撤銷全部 Tokens',
    'Rewind'                => 'Rewind',
    'RotateLeft'            => '向左旋轉',
    'RotateRight'           => '向右旋轉',
    'RTSPTransport'         => 'RTSP 傳輸協定',
    'RunAudit'              => 'Run Audit Process',
    'RunLocalUpdate'        => '請執行 zmupdate.pl 進行更新',
    'RunMode'               => '運行模式',
    'Running'               => '運行中',
    'RunState'              => 'Run State',
    'RunStats'              => 'Run Stats Process',
    'RunTrigger'            => 'Run Trigger Process',
    'RunEventNotification'  => 'Run Event Notification Process',
    'SaveAs'                => '另儲存為',
    'SaveFilter'            => '儲存 Filter',
    'SaveJPEGs'             => '儲存 JPEGs',
    'Save'                  => '儲存',
    'Scale'                 => '比例',
    'Score'                 => '分數',
    'Secs'                  => '秒',
    'Sectionlength'         => '段落長度',
    'SelectMonitors'        => '選擇監視器',
    'Select'                => '選擇',
    'SelectFormat'          => '選擇格式',
    'SelectLog'             => '選擇日誌',
    'SelfIntersecting'      => 'Polygon edges must not intersect',
    'SetNewBandwidth'       => '設定新頻寬',
    'SetPreset'             => '設定預設值',
    'Set'                   => '設定',
    'Settings'              => '設定',
    'ShowFilterWindow'      => '顯示過濾器視窗',
    'ShowTimeline'          => '顯示時間軸',
    'Shutdown'              => '關閉',
    'SignalCheckColour'     => 'Signal Check Colour',
    'SignalCheckPoints'     => 'Signal Check Points',
    'Size'                  => 'Size',
    'SkinDescription'       => 'Change the skin for this session',
    'CSSDescription'        => 'Change the css for this session',
    'Sleep'                 => '睡眠',
    'SortAsc'               => '升冪',
    'SortBy'                => '排序',
    'SortDesc'              => '降冪',
    'Source'                => '訊號源',
    'SourceColours'         => '訊號源顏色',
    'SourcePath'            => '訊號源路徑',
    'SourceType'            => '訊號源類型',
    'SpeedHigh'             => '高速',
    'SpeedLow'              => '低速',
    'SpeedMedium'           => '中速',
    'Speed'                 => '速度',
    'SpeedTurbo'            => '快速',
    'Start'                 => '開始',
    'State'                 => '狀態',
    'Stats'                 => 'Stats',
    'Status'                => '狀態',
    'StatusUnknown'         => '未知',
    'StatusConnected'       => '擷取中',
    'StatusNotRunning'      => '未運行',
    'StatusRunning'         => '未擷取',
    'StepBack'              => 'Step Back',
    'StepForward'           => 'Step Forward',
    'StepLarge'             => 'Large Step',
    'StepMedium'            => 'Medium Step',
    'StepNone'              => '無步進',
    'StepSmall'             => 'Small Step',
    'Step'                  => '步進',
    'Stills'                => 'Stills',
    'Stopped'               => '已停止',
    'Stop'                  => '停止',
    'StorageArea'           => '儲存區',
    'StorageDoDelete'       => 'Do Deletes',
    'StorageScheme'         => 'Scheme',
    'StreamReplayBuffer'    => 'Stream Replay Image Buffer',
    'Stream'                => 'Stream',
    'Submit'                => 'Submit',
    'System'                => '系統',
    'TargetColorspace'      => 'Target colorspace',
    'Tele'                  => 'Tele',
    'Thumbnail'             => '縮圖',
    'Tilt'                  => 'Tilt',
    'TimeDelta'             => 'Time Delta',
    'Timeline'              => '時間軸',
    'TimelineTip1'          => 'Pass your mouse over the graph to view a snapshot image and event details.',              // Added 2013.08.15.
    'TimelineTip2'          => 'Click on the coloured sections of the graph, or the image, to view the event.',              // Added 2013.08.15.
    'TimelineTip3'          => 'Click on the background to zoom in to a smaller time period based around your click.',              // Added 2013.08.15.
    'TimelineTip4'          => 'Use the controls below to zoom out or navigate back and forward through the time range.',              // Added 2013.08.15.
    'TimestampLabelFormat'  => 'Timestamp Label Format',
    'TimestampLabelX'       => 'Timestamp Label X',
    'TimestampLabelY'       => 'Timestamp Label Y',
    'TimestampLabelSize'    => '字體大小',
    'Timestamp'             => 'Timestamp',
    'TimeStamp'             => 'Time Stamp',
    'Time'                  => '時間',
    'Today'                 => '今日',
    'Tools'                 => '工具',
    'Total'                 => 'Total',
    'TotalBrScore'          => '總<br/>分數',
    'TrackDelay'            => 'Track Delay',
    'TrackMotion'           => 'Track Motion',
    'Triggers'              => 'Triggers',
    'TurboPanSpeed'         => '快速平移速度',
    'TurboTiltSpeed'        => '快速 Tilt 速度',
    'Type'                  => '類型',
    'TZUnset'               => '未設定 - 使用 php.ini 的設定值',
    'Unarchive'             => 'Unarchive',
    'Undefined'             => '未定義',
    'Units'                 => 'Units',
    'Unknown'               => '未知',
    'UpdateAvailable'       => 'An update to ZoneMinder is available.',
    'UpdateNotNecessary'    => 'No update is necessary.',
    'Update'                => '更新',
    'Upload'                => '上傳',
    'Updated'               => '已更新',
    'UsedPlugins'	    => 'Used Plugins',
    'UseFilterExprsPost'    => '&nbsp;filter&nbsp;expressions', // This is used at the end of the phrase 'use N filter expressions'
    'UseFilterExprsPre'     => 'Use&nbsp;', // This is used at the beginning of the phrase 'use N filter expressions'
    'UseFilter'             => '使用過濾器',
    'Username'              => '使用者帳號',
    'Users'                 => '使用者',
    'User'                  => '使用者',
    'Value'                 => 'Value',
    'VersionIgnore'         => '忽略此版本',
    'VersionRemindDay'      => '一天之後再提醒',
    'VersionRemindHour'     => '一小時後再提醒',
    'VersionRemindNever'    => 'Don\'t remind about new versions',
    'VersionRemindWeek'     => '一週之後再提醒',
    'VersionRemindMonth'    => '一個月後再提醒',
    'Version'               => '版本',
    'ViewMatches'           => 'View Matches',
    'VideoFormat'           => '影像格式',
    'VideoGenFailed'        => 'Video Generation Failed!',
    'VideoGenFiles'         => 'Existing Video Files',
    'VideoGenNoFiles'       => 'No Video Files Found',
    'VideoGenParms'         => '影片 Generation Parameters',
    'VideoGenSucceeded'     => '影片 Generation Succeeded!',
    'VideoSize'             => '影片 Size',
    'VideoWriter'           => '影片 Writer',
    'Video'                 => '影片',
    'ViewAll'               => 'View All',
    'ViewEvent'             => 'View Event',
    'ViewPaged'             => 'View Paged',
    'View'                  => 'View',
    'V4L'                   => 'V4L',
    'V4LCapturesPerFrame'   => 'Captures Per Frame',
    'V4LMultiBuffer'        => 'Multi Buffering',
    'Wake'                  => 'Wake',
    'WarmupFrames'          => 'Warmup Frames',
    'Watch'                 => 'Watch',
    'WebColour'             => '網站色系',
    'Web'                   => '網站',
    'WebSiteUrl'            => 'Website URL',
    'Week'                  => '週',
    'WhiteBalance'          => '白平衡',
    'White'                 => '白',
    'Wide'                  => 'Wide',
    'X10ActivationString'   => 'X10 Activation String',
    'X10InputAlarmString'   => 'X10 Input Alarm String',
    'X10OutputAlarmString'  => 'X10 Output Alarm String',
    'X10'                   => 'X10',
    'X'                     => 'X',
    'Yes'                   => 'Yes',
    'YouNoPerms'            => 'You do not have permissions to access this resource.',
    'Y'                     => 'Y',
    'ZoneAlarmColour'       => '警報顏色 (紅/綠/藍)',
    'ZoneArea'              => 'Zone Area',
    'ZoneFilterSize'        => 'Filter 寬/高 (pixels)',
    'ZoneMinderLog'         => 'ZoneMinder 日誌',
    'ZoneMinMaxAlarmArea'   => '最小/最大 Alarmed Area',
    'ZoneMinMaxBlobArea'    => '最小/最大 Blob Area',
    'ZoneMinMaxBlobs'       => '最小/最大 Blobs',
    'ZoneMinMaxFiltArea'    => '最小/最大 Filtered Area',
    'ZoneMinMaxPixelThres'  => '最小/最大 Pixel Threshold (0-255)',
    'ZoneOverloadFrames'    => 'Overload Frame Ignore Count',
    'ZoneExtendAlarmFrames' => 'Extend Alarm Frame Count',
    'Zones'                 => 'Zones',
    'Zone'                  => 'Zone',
    'ZoomIn'                => 'Zoom In',
    'ZoomOut'               => 'Zoom Out',
    'Zoom'                  => '縮放',
);

// Complex replacements with formatting and/or placements, must be passed through sprintf
$CLANG = array(
    'CurrentLogin'          => '目前登入者是 \'%1$s\'',
    'EventCount'            => '%1$s %2$s', // For example '37 Events' (from Vlang below)
    'LastEvents'            => '最後 %1$s %2$s', // For example 'Last 37 Events' (from Vlang below)
    'LatestRelease'         => 'The latest release is v%1$s, you have v%2$s.',
    'MonitorCount'          => '%1$s %2$s', // For example '4 Monitors' (from Vlang below)
    'MonitorFunction'       => '監視器 %1$s Function',
    'RunningRecentVer'      => 'You are running the most recent version of ZoneMinder, v%s.',
    'VersionMismatch'       => '版本不相同, 系統版本 %1$s, 資料庫 %2$s.',
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
    die( '錯誤, unable to correlate variable language string' );
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

//    'LANG_DEFAULT' => array(
//        'Prompt' => "This is a new prompt for this option",
//        'Help' => "This is some new help for this option which will be displayed in the popup window when the ? is clicked"
//    ),
);

