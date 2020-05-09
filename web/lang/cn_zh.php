<?php
//
// ZoneMinder web Simplified Chinese language file, $Date: 2009-02-19 12:45:24 +0000 (Tue, 27 Jan 2009) $, $Revision: 0001 $
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

// ZoneMinder <Simplified Chinese> Translation by <allankliu@yahoo.com.cn>

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
// require_once( 'zm_lang_zh_cn.php' );

// You may need to change the character set here, if your web server does not already
// do this by default, uncomment this if required.
//
// Example
// header( "Content-Type: text/html; charset=utf-8" );

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
 setlocale( LC_ALL, 'cn_ZH' ); //All locale settings 4.3.0 and after
 setlocale( LC_CTYPE, 'cn_ZH' ); //Character class settings 4.3.0 and after
 setlocale( LC_TIME, 'cn_ZH' ); //Date and time formatting 4.3.0 and after

// Simple String Replacements
$SLANG = array(
    '24BitColour'           => '24 位彩色',
    '32BitColour'           => '32 位彩色',          // Added - 2011-06-15
    '8BitGrey'              => '8 位灰度',
    'API'                  => 'API',                    // Added - 2020-04-09
    'APIEnabled'           => 'API已启用',            // Added - 2020-04-09
    'Action'                => '活动动作',
    'Actual'                => '实际',
    'AddNewControl'         => '新建控制',
    'AddNewMonitor'         => '新建监视器',
    'AddNewServer'         => '新建服务器',         // Added - 2018-08-30
    'AddNewStorage'        => '新建存储',        // Added - 2018-08-30
    'AddNewUser'            => '新建用户',
    'AddNewZone'            => '新建区域',
    'Alarm'                 => '报警',
    'AlarmBrFrames'         => '报警<br/>帧',
    'AlarmFrame'            => '报警帧',
    'AlarmFrameCount'       => '报警帧数',
    'AlarmLimits'           => '报警限制',
    'AlarmMaximumFPS'       => '报警最大帧率FPS',
    'AlarmPx'               => '报警像素',
    'AlarmRGBUnset'         => '你必须设置一个报警颜色(RGB)',
    'AlarmRefImageBlendPct'=> '报警参考影像混合 %ge', // Added - 2015-04-18
    'Alert'                 => '警报',
    'All'                   => '全部',
    'AllTokensRevoked'     => '已撤销所有tokens',     // Added - 2020-04-09
    'AnalysisFPS'          => '分析帧率 FPS',           // Added - 2015-07-22
    'AnalysisUpdateDelay'  => '分析更新延迟',  // Added - 2015-07-23
    'Apply'                 => '应用',
    'ApplyingStateChange'   => '状态改变生效',
    'ArchArchived'          => '仅限于存档',
    'ArchUnarchived'        => '仅限于未存档',
    'Archive'               => '存档',
    'Archived'              => '已经存档',
    'Area'                  => '区域',
    'AreaUnits'             => '区域 (px/%)',
    'AttrAlarmFrames'       => '报警帧',
    'AttrArchiveStatus'     => '存档状态',
    'AttrAvgScore'          => '平均分数',
    'AttrCause'             => '原因',
    'AttrDiskBlocks'        => '磁盘区块',
    'AttrDiskPercent'       => '磁盘百分比',
    'AttrDiskSpace'        => '磁盘空间',             // Added - 2018-08-30
    'AttrDuration'          => '过程',
    'AttrEndDate'          => '结束日期',               // Added - 2018-08-30
    'AttrEndDateTime'      => '结束日期/时间',          // Added - 2018-08-30
    'AttrEndTime'          => '结束时间',               // Added - 2018-08-30
    'AttrEndWeekday'       => '结束星期',            // Added - 2018-08-30
    'AttrFilterServer'     => '过滤服务正运行在', // Added - 2018-08-30
    'AttrFrames'            => '帧',
    'AttrId'                => 'Id',
    'AttrMaxScore'          => '最大分数',
    'AttrMonitorId'         => '监视器 Id',
    'AttrMonitorName'       => '监视器名称',
    'AttrMonitorServer'    => '监控服务正运行在', // Added - 2018-08-30
    'AttrName'              => '名称',
    'AttrNotes'             => '备注',
    'AttrSecondaryStorageArea'=> '第二存储区域', // Added - 2020-04-09
    'AttrStartDate'        => '开始日期',             // Added - 2018-08-30
    'AttrStartDateTime'    => '开始日期/时间',        // Added - 2018-08-30
    'AttrStartTime'        => '开始时间',             // Added - 2018-08-30
    'AttrStartWeekday'     => '开始星期',          // Added - 2018-08-30
    'AttrStateId'          => '运行状态',              // Added - 2018-08-30
    'AttrStorageArea'      => '存储区域',           // Added - 2018-08-30
    'AttrStorageServer'    => '存储服务器', // Added - 2018-08-30
    'AttrSystemLoad'        => '系统负载',
    'AttrTotalScore'        => '总分数',
    'Auto'                  => '自动',
    'AutoStopTimeout'       => '超时自动停止',
    'Available'            => 'Available',              // Added - 2009-03-31
    'AvgBrScore'            => '平均<br/>分数',
    'Background'            => '后台',
    'BackgroundFilter'      => '在后台运行筛选器',
    'BadAlarmFrameCount'    => '报警帧数必须设为大于1的整数',
    'BadAlarmMaxFPS'        => '报警最大帧率必须是正整数或正浮点数',
    'BadAnalysisFPS'       => '分析帧率 FPS 必须是正整数或正浮点数', // Added - 2015-07-22
    'BadAnalysisUpdateDelay'=> '分析更新延迟必须设为大于零的整数', // Added - 2015-07-23
    'BadChannel'            => '通道必须设为大于零的整数',
    'BadColours'           => '颜色必须设置为有效值', // Added - 2011-06-15
    'BadDevice'             => '必须为器件设置有效值',
    'BadFPSReportInterval'  => 'FPS帧数报告间隔缓冲数必须是0以上整数',
    'BadFormat'             => '格式必须设为大于零的整数',
    'BadFrameSkip'          => '跳帧数必须设为大于零的整数',
    'BadHeight'             => '高度必须设为有效值',
    'BadHost'               => '主机必须设为有效IP地址或主机名，不要包含 http://',
    'BadImageBufferCount'   => '图像缓冲器大小必须设为大于10的整数',
    'BadLabelX'             => '标签 X 坐标必须设为大于零的整数',
    'BadLabelY'             => '标签 Y 坐标必须设为大于零的整数',
    'BadMaxFPS'             => '最大帧数FPS必须设为正整数或着浮点数',
    'BadMotionFrameSkip'    => '运动跳帧数必须设为大于零的整数',
    'BadNameChars'          => '名称只可以包含字母，数字，波折号和下划线',
    'BadNoSaveJPEGsOrVideoWriter'=> '保存为JPEGs和保存为视频同时禁用后。不会有任何记录 ', // Added - 2020-04-09
    'BadPalette'           => '调色板必须设为有效值', // Added - 2009-03-31
    'BadPath'               => '路径必须设为有效值',
    'BadPort'               => '端口必须设为有效数字',
    'BadPostEventCount'     => '之后事件影像数目必须设为大于零的整数',
    'BadPreEventCount'      => '之前事件影像数目必须最小值为零，并且小于影像缓冲区',
    'BadRefBlendPerc'       => '参考混合百分比必须设为一个正整数',
    'BadSectionLength'      => '节长度必须设为30的整数倍',
    'BadSignalCheckColour'  => '信号检查颜色必须设为有效的RGB颜色字符',
    'BadSourceType'        => '源类型 \"网站\" 要求 功能 设置为 \"监视\"', // Added - 2018-08-30
    'BadStreamReplayBuffer' => '流重放缓冲必须为零或更多整数',
    'BadWarmupCount'        => '预热帪必须设为零或更多整数',
    'BadWebColour'          => 'Web颜色必须设为有效Web颜色字符',
    'BadWebSitePath'       => '请输入一个完整的网站链接，包括http://或https://前缀。', // Added - 2018-08-30
    'BadWidth'              => '宽度必须设为有效值',
    'Bandwidth'             => '带宽',
    'BandwidthHead'         => 'Bandwidth',	// This is the end of the bandwidth status on the top of the console, different in many language due to phrasing
    'BlobPx'                => 'Blob像素',
    'BlobSizes'             => 'Blob大小',
    'Blobs'                 => 'Blobs',
    'Brightness'            => '亮度',
    'Buffer'               => '缓冲',                 // Added - 2015-04-18
    'Buffers'               => '缓冲器',
    'CSSDescription'       => '改变本机默认css', // Added - 2015-04-18
    'CanAutoFocus'          => '可以自动对焦',
    'CanAutoGain'           => '可以自动增益控制',
    'CanAutoIris'           => '可以自动光圈',
    'CanAutoWhite'          => '可以自动白平衡',
    'CanAutoZoom'           => '可以自动缩放',
    'CanFocus'              => '可以对焦',
    'CanFocusAbs'           => '可以绝对对焦',
    'CanFocusCon'           => '可以连续对焦',
    'CanFocusRel'           => '可以相对对焦',
    'CanGain'               => '可以增益',
    'CanGainAbs'            => '可以绝对增益',
    'CanGainCon'            => '可以连续增益',
    'CanGainRel'            => '可以相对增益',
    'CanIris'               => '可以光圈',
    'CanIrisAbs'            => '可以绝对光圈',
    'CanIrisCon'            => '可以连续光圈',
    'CanIrisRel'            => '可以相对光圈',
    'CanMove'               => '可以移动',
    'CanMoveAbs'            => '可以绝对移动',
    'CanMoveCon'            => '可以连续移动',
    'CanMoveDiag'           => '可以对角移动',
    'CanMoveMap'            => '可以映射网格移动',
    'CanMoveRel'            => '可以相对移动',
    'CanPan'                => '可以平移' ,
	'CanReboot'             => '可以重启',
    'CanReset'              => '可以复位',
    'CanSetPresets'         => '可以进行预设',
    'CanSleep'              => '可以休眠',
    'CanTilt'               => '可以倾斜',
    'CanWake'               => '可以唤醒',
    'CanWhite'              => '可以白平衡',
    'CanWhiteAbs'           => '可以绝对白平衡',
    'CanWhiteBal'           => '可以白平衡',
    'CanWhiteCon'           => '可以连续白平衡',
    'CanWhiteRel'           => '可以相对白平衡',
    'CanZoom'               => '可以缩放',
    'CanZoomAbs'            => '可以绝对缩放',
    'CanZoomCon'            => '可以连续缩放',
    'CanZoomRel'            => '可以相对缩放',
    'Cancel'                => '取消',
    'CancelForcedAlarm'     => '取消强制报警',
    'CaptureHeight'         => '捕获高度',
    'CaptureMethod'         => '捕获方式',
    'CapturePalette'        => '捕获调色板',
    'CaptureResolution'    => '捕获分辨率',     // Added - 2015-04-18
    'CaptureWidth'          => '捕获宽度',
    'Cause'                 => '原因',
    'CheckMethod'           => '报警检查方式',
    'ChooseDetectedCamera' => '选择检测到的摄像头', // Added - 2009-03-31
    'ChooseDetectedProfile'=> '选择检测到的流媒体', // Added - 2020-04-09
    'ChooseFilter'          => '选择筛选器',
    'ChooseLogFormat'      => '选择日志格式',    // Added - 2011-06-17
    'ChooseLogSelection'   => 'Choose a log selection', // Added - 2011-06-17
    'ChoosePreset'          => '选择预置',
    'Clear'                => '清除',                  // Added - 2011-06-16
    'CloneMonitor'         => '克隆',                  // Added - 2018-08-30
    'Close'                 => '关闭',
    'Colour'                => '彩色',
    'Command'               => '命令',
    'Component'            => '组件',              // Added - 2011-06-16
    'ConcurrentFilter'     => '同时应用筛选器', // Added - 2018-08-30
    'Config'                => '配置',
    'ConfiguredFor'         => '配置标的',
    'ConfirmDeleteEvents'   => '确认希望删除所选事件？',
    'ConfirmPassword'       => '密码确认',
    'ConjAnd'               => '及',
    'ConjOr'                => '或',
    'Console'               => '控制台',
    'ContactAdmin'          => '请联系您的管理员了解详情。',
    'Continue'              => '继续',
    'Contrast'              => '对比度',
    'Control'               => '控制',
    'ControlAddress'        => '控制地址',
    'ControlCap'            => '控制能力',
    'ControlCaps'           => '控制能力',
    'ControlDevice'         => '控制设备',
    'ControlType'           => '控制类型',
    'Controllable'          => '可控',
    'Current'              => '现在',                // Added - 2015-04-18
    'Cycle'                 => '循环',
    'CycleWatch'            => '循环监视',
    'DateTime'             => '日期',              // Added - 2011-06-16
    'Day'                   => '日',
    'Debug'                 => '调试',
    'DefaultCodec'         => '默认即时观看方法', // Added - 2020-04-09
    'DefaultRate'           => '缺省速率',
    'DefaultScale'          => '缺省缩放',
    'DefaultView'           => '缺省视角',
    'Deinterlacing'        => '去隔行',          // Added - 2015-04-18
    'Delay'                => '延迟',                  // Added - 2015-04-18
    'Delete'                => '删除',
    'DeleteAndNext'         => '删除并下一个',
    'DeleteAndPrev'         => '删除并前一个',
    'DeleteSavedFilter'     => '删除存储过滤器',
    'Description'           => '描述',
    'DetectedCameras'      => '检测到的摄像头',       // Added - 2009-03-31
    'DetectedProfiles'     => '检测到的流媒体',      // Added - 2015-04-18
    'Device'                => '设备',
    'DeviceChannel'         => '设备通道',
    'DeviceFormat'          => '设备格式',
    'DeviceNumber'          => '设备编号',
    'DevicePath'            => '设备路径',
    'Devices'               => '设备',
    'Dimensions'            => '维度',
    'DisableAlarms'         => '关闭警报',
    'Disk'                  => '磁盘',
    'Display'              => '显示',                // Added - 2011-01-30
    'Displaying'           => '正在显示',             // Added - 2011-06-16
    'DoNativeMotionDetection'=> '在本机进行运动检测',
    'Donate'                => '请捐款',
    'DonateAlready'         => '不，我已经捐赠过了',
    'DonateEnticement'      => '迄今，您已经运行ZoneMinder有一阵子了，希望它能够有助于增强您家或者办公区域的安全。尽管ZoneMinder是，并将保持免费和开源，该项目依然在研发和支持中投入了资金和精力。如果您愿意支持今后的开发和新功能，那么请考虑为该项目捐款。捐款不是必须的，任何数量的捐赠，我们都很感谢。<br/><br/>如果您愿意捐款，请选择下列选项，或者访问 https://zoneminder.com/donate/ 捐赠主页。<br/><br/>感谢您使用ZoneMinder，并且不要忘记访问访问ZoneMinder.com的论坛以获得支持或建议，这可以提升您的ZoneMinder的体验。',
    'DonateRemindDay'       => '现在不，1天内再次提醒我',
    'DonateRemindHour'      => '现在不，1小时内再次提醒我',
    'DonateRemindMonth'     => '现在不，1个月内再次提醒我',
    'DonateRemindNever'     => '不，我不打算捐款',
    'DonateRemindWeek'      => '现在不，1星期内再次提醒我',
    'DonateYes'             => '好，我现在就捐款',
    'Download'              => '下载',
    'DownloadVideo'        => '下载视频',         // Added - 2018-08-30
    'DuplicateMonitorName' => 'Duplicate Monitor Name', // Added - 2009-03-31
    'Duration'              => 'Duration',
    'Edit'                  => '编辑',
    'EditLayout'           => '编辑布局',            // Added - 2018-08-30
    'Email'                 => 'Email',
    'EnableAlarms'          => '启动报警',
    'Enabled'               => '已启动',
    'EnterNewFilterName'    => '输入新过滤器名称',
    'Error'                 => '错误',
    'ErrorBrackets'         => '错误, 请检查左右括号数，必须相等',
    'ErrorValidValue'       => '错误, 请检查所有条件具备有效值',
    'Etc'                   => '等',
    'Event'                 => '事件',
    'EventFilter'           => '事件过滤器',
    'EventId'               => '事件 Id',
    'EventName'             => '事件名称',
    'EventPrefix'           => '事件前缀',
    'Events'                => '事件',
    'Exclude'               => '排除',
    'Execute'               => '执行',
    'Exif'                 => '嵌入EXIF信息到图片', // Added - 2018-08-30
    'Export'                => '导出',
    'ExportCompress'       => '使用压缩',        // Added - 2020-04-09
    'ExportDetails'         => '导出时间详情',
    'ExportFailed'          => '导出失败',
    'ExportFormat'          => '导出文件格式',
    'ExportFormatTar'       => 'Tar',
    'ExportFormatZip'       => 'Zip',
    'ExportFrames'          => '导出帧详情',
    'ExportImageFiles'      => '导出影像文件',
    'ExportLog'            => '导出日志',             // Added - 2011-06-17
    'ExportMatches'        => '导出匹配项',         // Added - 2020-04-09
    'ExportMiscFiles'       => '导出其他文件 (如果存在)',
    'ExportOptions'         => '导出选项',
    'ExportSucceeded'       => '导出成功',
    'ExportVideoFiles'      => '导出视频文件 (如果存在)',
    'Exporting'             => '正在导出',
    'FPS'                   => 'fps',
    'FPSReportInterval'     => 'FPS 报告间隔',
    'FTP'                   => 'FTP',
    'Far'                   => '远',
    'FastForward'           => '快进',
    'Feed'                  => '转送源',
    'Ffmpeg'                => 'Ffmpeg',
    'File'                  => '文件',
    'Filter'               => '过滤器',                 // Added - 2015-04-18
    'FilterArchiveEvents'   => '存档全部匹配项',
    'FilterCopyEvents'     => '复制全部匹配项',       // Added - 2020-04-09
    'FilterDeleteEvents'    => '删除全部匹配项',
    'FilterEmailEvents'     => '邮件发送全部匹配项详情',
    'FilterExecuteEvents'   => '执行全部匹配项命令',
    'FilterLog'            => '过滤日志',             // Added - 2015-04-18
    'FilterMessageEvents'   => '全部匹配项的信息详情',
    'FilterMoveEvents'     => '移除全部匹配项',       // Added - 2018-08-30
    'FilterPx'              => '过滤器像素',
    'FilterUnset'           => '您必须指定过滤器宽度和高度',
    'FilterUpdateDiskSpace'=> '刷新磁盘空间', // Added - 2018-08-30
    'FilterUploadEvents'    => '上传全部匹配项',
    'FilterVideoEvents'     => '为全部匹配项创建视频',
    'Filters'               => '过滤器',
    'First'                 => '首先',
    'FlippedHori'           => '水平翻转',
    'FlippedVert'           => '垂直翻转',
    'FnMocord'              => '运动侦测并录制',            // Added 2013.08.16.
    'FnModect'              => '运动侦测',            // Added 2013.08.16.
    'FnMonitor'             => '监视',            // Added 2013.08.16.
    'FnNodect'              => 'Nodect',            // Added 2013.08.16.
    'FnNone'                => '无',            // Added 2013.08.16.
    'FnRecord'              => '录制',            // Added 2013.08.16.
    'Focus'                 => '聚焦',
    'ForceAlarm'            => '强制报警',
    'Format'                => '格式',
    'Frame'                 => '帧',
    'FrameId'               => '帧 Id',
    'FrameRate'             => '帧率',
    'FrameSkip'             => '跳帧',
    'Frames'                => '帧',
    'Func'                  => '功能',
    'Function'              => '功能',
    'Gain'                  => '增益',
    'General'               => '一般',
    'GenerateDownload'     => '创建下载项',      // Added - 2018-08-30
    'GenerateVideo'         => '创建视频',
    'GeneratingVideo'       => '正在创建视频',
    'GoToZoneMinder'        => '访问 ZoneMinder.com',
    'Grey'                  => '灰',
    'Group'                 => '组',
    'Groups'                => '组',
    'HasFocusSpeed'         => '有聚焦速度',
    'HasGainSpeed'          => '有增益速度',
    'HasHomePreset'         => '有主页预设',
    'HasIrisSpeed'          => '有光圈速度',
    'HasPanSpeed'           => '有平移速度',
    'HasPresets'            => '有预设值',
    'HasTiltSpeed'          => '有倾斜速度',
    'HasTurboPan'           => '有加速平移',
    'HasTurboTilt'          => '有加速斜率',
    'HasWhiteSpeed'         => '有白平衡速度',
    'HasZoomSpeed'          => '有缩放速度',
    'High'                  => '高',
    'HighBW'                => '高&nbsp;B/W',
    'Home'                  => '主页',
    'Hostname'             => '主机名',               // Added - 2018-08-30
    'Hour'                  => '小时',
    'Hue'                   => '色调',
    'Id'                    => 'Id',
    'Idle'                  => '空闲',
    'Ignore'                => '忽略',
    'Image'                 => '影像',
    'ImageBufferSize'       => '影像缓冲区大小 (帧)',
    'Images'                => '影像',
    'In'                    => '在',
    'Include'               => '包含',
    'InvalidateTokens'     => '使所有创建的tokens无效', // Added - 2020-04-09
    'Inverted'              => '反向',
    'Iris'                  => '光圈',
    'KeyString'             => '密钥字符',
    'Label'                 => '标签',
    'Language'              => '语言',
    'Last'                  => '最后',
    'Layout'                => '布局',
    'Level'                => '级别',                  // Added - 2011-06-16
    'Libvlc'               => 'Libvlc',
    'LimitResultsPost'      => '个结果', // This is used at the end of the phrase 'Limit to first N results only'
    'LimitResultsPre'       => '仅限于开始', // This is used at the beginning of the phrase 'Limit to first N results only'
    'Line'                 => '行',                   // Added - 2011-06-16
    'LinkedMonitors'        => '管理监视器',
    'List'                  => '列表',
    'ListMatches'          => '列出匹配项',           // Added - 2018-08-30
    'Load'                  => '加载',
    'Local'                 => '本地',
    'Log'                  => '日志',                    // Added - 2011-06-16
    'LoggedInAs'            => '登录为',
    'Logging'              => '日志',                // Added - 2011-06-16
    'LoggingIn'             => '登录',
    'Login'                 => '登入',
    'Logout'                => '登出',
    'Logs'                 => 'Logs',                   // Added - 2011-06-17
    'Low'                   => '低',
    'LowBW'                 => '低&nbsp;B/W',
    'Main'                  => '主要',
    'Man'                   => '人',
    'Manual'                => '手册',
    'Mark'                  => '标记',
    'Max'                   => '最大',
    'MaxBandwidth'          => '最大带宽',
    'MaxBrScore'            => '最大<br/>Score',
    'MaxFocusRange'         => '最大聚焦范围',
    'MaxFocusSpeed'         => '最大聚焦速度',
    'MaxFocusStep'          => '最大聚焦步进',
    'MaxGainRange'          => '最大增益范围',
    'MaxGainSpeed'          => '最大增益速度',
    'MaxGainStep'           => '最大增益步进',
    'MaxIrisRange'          => '最大光圈范围',
    'MaxIrisSpeed'          => '最大光圈速度',
    'MaxIrisStep'           => '最大光圈步进',
    'MaxPanRange'           => '最大平移范围',
    'MaxPanSpeed'           => '最大平移速度',
    'MaxPanStep'            => '最大平移步进',
    'MaxTiltRange'          => '最大倾斜范围',
    'MaxTiltSpeed'          => '最大倾斜速度',
    'MaxTiltStep'           => '最大倾斜步进',
    'MaxWhiteRange'         => '最大白平衡范围',
    'MaxWhiteSpeed'         => '最大白平衡速度',
    'MaxWhiteStep'          => '最大白平衡步进',
    'MaxZoomRange'          => '最大缩放范围',
    'MaxZoomSpeed'          => '最大缩放速度',
    'MaxZoomStep'           => '最大缩放步进',
    'MaximumFPS'            => '最大帧率 FPS',
    'Medium'                => '中等',
    'MediumBW'              => '中等&nbsp;B/W',
    'Message'              => '消息',                // Added - 2011-06-16
    'MinAlarmAreaLtMax'     => '最小报警区域应该小于最大区域',
    'MinAlarmAreaUnset'     => '您必须指定最小报警像素数量',
    'MinBlobAreaLtMax'      => '最小blob区必须小数最大区域',
    'MinBlobAreaUnset'      => '您必须指定最小blob像素数量',
    'MinBlobLtMinFilter'    => '最小 blob 区必须小于等于最小过滤区域',
    'MinBlobsLtMax'         => '最小 blob 必须小于最大区域',
    'MinBlobsUnset'         => '您必须指定最小 blob 数',
    'MinFilterAreaLtMax'    => '最小过滤区域必须小于最大区域',
    'MinFilterAreaUnset'    => '您必须指定最小过滤像素数量',
    'MinFilterLtMinAlarm'   => '最小过滤区域应该小于等于最小报警区域',
    'MinFocusRange'         => '最小聚焦区域',
    'MinFocusSpeed'         => '最小聚焦速度',
    'MinFocusStep'          => '最小聚焦步进',
    'MinGainRange'          => '最小增益范围',
    'MinGainSpeed'          => '最小增益速度',
    'MinGainStep'           => '最小增益步进',
    'MinIrisRange'          => '最小光圈范围',
    'MinIrisSpeed'          => '最小光圈速度',
    'MinIrisStep'           => '最小光圈步进',
    'MinPanRange'           => '最小平移范围',
    'MinPanSpeed'           => '最小平移速度',
    'MinPanStep'            => '最小平移步进',
    'MinPixelThresLtMax'    => '最小像素阈值应该小于最大值',
    'MinPixelThresUnset'    => '您必须指定一个最小像素阈值',
    'MinTiltRange'          => '最小倾斜范围',
    'MinTiltSpeed'          => '最小倾斜速度',
    'MinTiltStep'           => '最小倾斜步进',
    'MinWhiteRange'         => '最小白平衡范围',
    'MinWhiteSpeed'         => '最小白平衡速度',
    'MinWhiteStep'          => '最小白平衡步进',
    'MinZoomRange'          => '最小缩放范围',
    'MinZoomSpeed'          => '最小缩放速度',
    'MinZoomStep'           => '最小缩放步进',
    'Misc'                  => '杂项',
    'Mode'                 => '模式',                   // Added - 2015-04-18
    'Monitor'               => '监视器',
    'MonitorIds'            => '监视器&nbsp;Ids',
    'MonitorPreset'         => '监视器预设值',
    'MonitorPresetIntro'    => '从以下列表中选择一个合适的预设值.<br/><br/>请注意该方式可能覆盖您为该监视器配置的数值.<br/><br/>',
    'MonitorProbe'         => '监视器探测',          // Added - 2009-03-31
    'MonitorProbeIntro'    => '以下列表显示了检测到的模拟和网络摄像头，以及其可用状态<br/><br/>请从列表中选择你想要的项<br/><br/>请注意可能有些摄像头并没有检测到，而且选择一个摄像头可能覆盖一些你已经设置的配置。<br/><br/>', // Added - 2009-03-31
    'Monitors'              => '监视器',
    'Montage'               => '镜头组接',
    'MontageReview'        => 'Montage Review',         // Added - 2018-08-30
    'Month'                 => '月',
    'More'                 => '更多',                   // Added - 2011-06-16
    'MotionFrameSkip'       => '运动侦测跳帧',
    'Move'                  => '移动',
    'Mtg2widgrd'            => '2-wide grid',              // Added 2013.08.15.
    'Mtg3widgrd'            => '3-wide grid',              // Added 2013.08.15.
    'Mtg3widgrx'            => '3-wide grid, scaled, enlarge on alarm',              // Added 2013.08.15.
    'Mtg4widgrd'            => '4-wide grid',              // Added 2013.08.15.
    'MtgDefault'            => '默认',              // Added 2013.08.15.
    'MustBeGe'              => '必须大于等于',
    'MustBeLe'              => '必须小于等于',
    'MustConfirmPassword'   => '您必须确认密码',
    'MustSupplyPassword'    => '您必须提供密码',
    'MustSupplyUsername'    => '您必须提供用户名',
    'Name'                  => '名称',
    'Near'                  => '近',
    'Network'               => '网络',
    'New'                   => '新建',
    'NewGroup'              => '新建组',
    'NewLabel'              => '新建标签',
    'NewPassword'           => '新建密码',
    'NewState'              => '新状态',
    'NewUser'               => '新用户',
    'Next'                  => '下一个',
    'NextMonitor'          => '下一个监视器',           // Added - 2020-04-09
    'No'                    => '不',
    'NoDetectedCameras'    => '没有检测到摄像头',    // Added - 2009-03-31
    'NoDetectedProfiles'   => '没有检测到流媒体',   // Added - 2018-08-30
    'NoFramesRecorded'      => '该事件没有相关帧的记录',
    'NoGroup'               => '无组',
    'NoSavedFilters'        => '没有保存过滤器',
    'NoStatisticsRecorded'  => '没有该事件/帧的统计记录',
    'None'                  => '无',
    'NoneAvailable'         => '没有',
    'Normal'                => '正常',
    'Notes'                 => '备注',
    'NumPresets'            => '数值预置',
    'Off'                   => '关',
    'On'                    => '开',
    'OnvifCredentialsIntro'=> '请为所选摄像头提供用户名和密码。<br/>如果还没有为这台摄像头创建过用户，那么将会使用提供的用户名和密码创建用户<br/><br/>', // Added - 2015-04-18
    'OnvifProbe'           => 'ONVIF',                  // Added - 2015-04-18
    'OnvifProbeIntro'      => '以下列表显示了检测到的ONVIF摄像头和其可用状态。<br/><br/>选择一个你想要的项<br/><br/>请注意可能有些摄像头并没有检测到，而且选择一个摄像头可能覆盖一些你已经设置的配置。<br/><br/>', // Added - 2015-04-18
    'OpEq'                  => '等于',
    'OpGt'                  => '大于',
    'OpGtEq'                => '大于等于',
    'OpIn'                  => '在集',
    'OpIs'                 => '是',                     // Added - 2018-08-30
    'OpIsNot'              => '不是',                 // Added - 2018-08-30
    'OpLike'               => '包含',               // Added - 2020-04-09
    'OpLt'                  => '小于',
    'OpLtEq'                => '小于等于',
    'OpMatches'             => '匹配',
    'OpNe'                  => '不等于',
    'OpNotIn'               => '未在集',
    'OpNotLike'            => '未包含',       // Added - 2020-04-09
    'OpNotMatches'          => '不匹配',
    'Open'                  => '打开',
    'OptionHelp'            => '选项帮助',
    'OptionRestartWarning'  => '这些改动在系统运行时可以不会完全生效.\n 当你设置完毕改动后\n请确认\n您重新启动 ZoneMinder.',
    'OptionalEncoderParam' => '编码参数(可选)', // Added - 2018-08-30
    'Options'               => '选项',
    'OrEnterNewName'        => '或输入新名词',
    'Order'                 => '次序',
    'Orientation'           => '方向',
    'Out'                   => '外部',
    'OverwriteExisting'     => '覆盖现有的',
    'Paged'                 => '分页',
    'Pan'                   => '平移',
    'PanLeft'               => '向左平移',
    'PanRight'              => '向右平移',
    'PanTilt'               => '平移/倾斜',
    'Parameter'             => '参数',
    'ParentGroup'          => '父组',           // Added - 2020-04-09
    'Password'              => '密码',
    'PasswordsDifferent'    => '新建密码和确认密码不一致',
    'PathToApi'            => 'Api路径',            // Added - 2020-04-09
    'PathToIndex'          => 'Index路径',          // Added - 2020-04-09
    'PathToZMS'            => 'ZMS路径',            // Added - 2020-04-09
    'Paths'                 => '路径',
    'Pause'                 => '暂停',
    'PauseCycle'           => 'Pause Cycle',            // Added - 2020-04-09
    'Phone'                 => '电话',
    'PhoneBW'               => '电话&nbsp;B/W',
    'Pid'                  => 'PID',                    // Added - 2011-06-16
    'PixelDiff'             => '像素差别',
    'Pixels'                => '像素',
    'Play'                  => '播放',
    'PlayAll'               => '播放全部',
    'PlayCycle'            => 'Play Cycle',             // Added - 2020-04-09
    'PleaseWait'            => '请等待',
    'Plugins'              => '插件',
    'Point'                 => '点',
    'PostEventImageBuffer'  => '事件之后影像数',
    'PreEventImageBuffer'   => '时间之前影像数',
    'PreserveAspect'        => '维持长宽比',
    'Preset'                => '预置',
    'Presets'               => '预置',
    'Prev'                  => '前',
    'PreviousMonitor'      => '前一个监视器',       // Added - 2020-04-09
    'Privacy'              => 'Privacy',                // Added - 2020-04-09
    'PrivacyAbout'         => '关于',                  // Added - 2020-04-09
    'PrivacyAboutText'     => 'Since 2002, ZoneMinder has been the premier free and open-source Video Management System (VMS) solution for Linux platforms. ZoneMinder is supported by the community and is managed by those who choose to volunteer their spare time to the project. The best way to improve ZoneMinder is to get involved.', // Added - 2020-04-09
    'PrivacyConclusionText'=> 'We are <u>NOT</u> collecting any image specific data from your cameras. We don’t know what your cameras are watching. This data will not be sold or used for any purpose not stated herein. By clicking accept, you agree to send us this data to help make ZoneMinder a better product. By clicking decline, you can still freely use ZoneMinder and all its features.', // Added - 2020-04-09
    'PrivacyContact'       => '联系',                // Added - 2020-04-09
    'PrivacyContactText'   => 'Please contact us <a href="https://zoneminder.com/contact/">here</a> for any questions regarding our privacy policy or to have your information removed.<br><br>For support, there are three primary ways to engage with the community:<ul><li>The ZoneMinder <a href="https://forums.zoneminder.com/">user forum</a></li><li>The ZoneMinder <a href="https://zoneminder-chat.herokuapp.com/">Slack channel</a></li><li>The ZoneMinder <a href="https://github.com/ZoneMinder/zoneminder/issues">Github forum</a></li></ul><p>Our Github forum is only for bug reporting. Please use our user forum or slack channel for all other questions or comments.</p>', // Added - 2020-04-09
    'PrivacyCookies'       => 'Cookies',                // Added - 2020-04-09
    'PrivacyCookiesText'   => 'Whether you use a web browser or a mobile app to communicate with the ZoneMinder server, a ZMSESSID cookie is created on the client to uniquely identify a session with the ZoneMinder server. ZmCSS and zmSkin cookies are created to remember your style and skin choices.', // Added - 2020-04-09
    'PrivacyMonitorList'   => 'The following configuration parameters from each monitor are collected:<ul><li>Id<li>Name<li>Type<li>Function<li>Width<li>Height<li>Colours<li>MaxFPS<li>AlarmMaxFPS</ul>', // Added - 2020-04-09
    'PrivacyTelemetry'     => 'Telemetry',              // Added - 2020-04-09
    'PrivacyTelemetryList' => 'The ZoneMinder Telemetry daemon collects the following data about your system:<ul><li>A unique identifier (UUID) <li>City based location is gathered by querying <a href="https://ipinfo.io/geo">ipinfo.io</a>. City, region, country, latitude, and longitude parameters are saved. The latitude and longitude coordinates are accurate down to the city or town level only!<li>Current time<li>Total number of monitors<li>Total number of events<li>System architecture<li>Operating system kernel, distro, and distro version<li>Version of ZoneMinder<li>Total amount of memory<li>Number of cpu cores</ul>', // Added - 2020-04-09
    'PrivacyTelemetryText' => 'Because ZoneMinder is open-source, anyone can install it without registering. This makes it difficult to  answer questions such as: how many systems are out there, what is the largest system out there, what kind of systems are out there, or where are these systems located? Knowing the answers to these questions, helps users who ask us these questions, and it helps us set priorities based on the majority user base.', // Added - 2020-04-09
    'Probe'                => '探测',                  // Added - 2009-03-31
    'ProfileProbe'         => '流媒体探测',           // Added - 2015-04-18
    'ProfileProbeIntro'    => '以下列表显示了所选摄像头可用的流媒体。<br/><br/>从列表中选择一个你想要的项<br/><br/>请注意ZoneMinder不能设置额外的配置并且选择摄像头可能会覆盖一些你已设置的配置。<br/><br/>', // Added - 2015-04-18
    'Progress'             => 'Progress',               // Added - 2015-04-18
    'Protocol'              => '协议',
    'RTSPDescribe'         => '使用 RTSP Response 媒体链接', // Added - 2018-08-30
    'RTSPTransport'        => 'RTSP传输协议', // Added - 2018-08-30
    'Rate'                  => '速率',
    'Real'                  => '实际',
    'RecaptchaWarning'     => '你的reCaptcha秘匙无效。 请更正，否则reCaptcha无法工作', // Added - 2018-08-30
    'Record'                => '记录',
    'RecordAudio'          => '记录事件时保存音频.', // Added - 2018-08-30
    'RefImageBlendPct'      => '参考影像混合 %ge',
    'Refresh'               => '刷新',
    'Remote'                => '远程',
    'RemoteHostName'        => '远程主机名',
    'RemoteHostPath'        => '远程主机路径',
    'RemoteHostPort'        => '远程主机端口',
    'RemoteHostSubPath'     => '远程主机子路径',
    'RemoteImageColours'    => '远程影像颜色',
    'RemoteMethod'          => '远程方法',
    'RemoteProtocol'        => '远程协议',
    'Rename'                => '重命名',
    'Replay'                => '重放',
    'ReplayAll'             => '全部事件',
    'ReplayGapless'         => '无间隙事件',
    'ReplaySingle'          => '单一事件',
    'ReportEventAudit'     => '事件报表',    // Added - 2018-08-30
    'Reset'                 => '重置',
    'ResetEventCounts'      => '重置事件数',
    'Restart'               => '重启动',
    'Restarting'            => '重启动',
    'RestrictedCameraIds'   => '受限摄像机 Id',
    'RestrictedMonitors'    => '受限监视器',
    'ReturnDelay'           => '返回延时',
    'ReturnLocation'        => '返回位置',
    'RevokeAllTokens'      => '撤销所有Tokens',      // Added - 2020-04-09
    'Rewind'                => '重绕',
    'RotateLeft'            => '向左旋转',
    'RotateRight'           => '向右旋转',
    'RunAudit'             => '审计',      // Added - 2020-04-09
    'RunEventNotification' => '事件提醒', // Added - 2020-04-09
    'RunLocalUpdate'       => '请运行zmupdate.pl来更新', // Added - 2011-05-25
    'RunMode'               => '运行模式',
    'RunState'              => '运行状态',
    'RunStats'             => '状态检测',      // Added - 2020-04-09
    'RunTrigger'           => '触发',    // Added - 2020-04-09
    'Running'               => '运行',
    'Save'                  => '保存',
    'SaveAs'                => '另存为',
    'SaveFilter'            => '存储过滤器',
    'SaveJPEGs'            => '保存为JPEGs',             // Added - 2018-08-30
    'Scale'                 => '比例',
    'Score'                 => '分数',
    'Secs'                  => '秒',
    'Sectionlength'         => '段长度',
    'Select'                => '选择',
    'SelectFormat'         => '选择格式',          // Added - 2011-06-17
    'SelectLog'            => '选择日志',             // Added - 2011-06-17
    'SelectMonitors'        => '选择监视器',
    'SelfIntersecting'      => '多边形边线不得交叉',
    'Set'                   => '设置',
    'SetNewBandwidth'       => '设置新的带宽',
    'SetPreset'             => '设置预设值',
    'Settings'              => '设置',
    'ShowFilterWindow'      => '显示过滤器视窗',
    'ShowTimeline'          => '显示时间轴',
    'Shutdown'             => '关机',               // Added - 2020-04-09
    'SignalCheckColour'     => '信号检查颜色',
    'SignalCheckPoints'    => '信号检测点数目',    // Added - 2018-08-30
    'Size'                  => '大小',
    'SkinDescription'      => '改变本机默认皮肤', // Added - 2011-01-30
    'Sleep'                 => '睡眠',
    'SortAsc'               => '升序',
    'SortBy'                => '排序',
    'SortDesc'              => '降序',
    'Source'                => '信号源',
    'SourceColours'         => '信号源颜色',
    'SourcePath'            => '信号源路径',
    'SourceType'            => '信号源类型',
    'Speed'                 => '加速',
    'SpeedHigh'             => '高速',
    'SpeedLow'              => '慢速',
    'SpeedMedium'           => '中等速度',
    'SpeedTurbo'            => '加速度',
    'Start'                 => '开始',
    'State'                 => '状态',
    'Stats'                 => '统计',
    'Status'                => '状况',
    'StatusConnected'      => '正在捕获',              // Added - 2018-08-30
    'StatusNotRunning'     => '未运行',            // Added - 2018-08-30
    'StatusRunning'        => '未捕获',          // Added - 2018-08-30
    'StatusUnknown'        => '未知',                // Added - 2018-08-30
    'Step'                  => '步进',
    'StepBack'              => '单步后退',
    'StepForward'           => '单步前进',
    'StepLarge'             => '大步步进',
    'StepMedium'            => '中步步进',
    'StepNone'              => '无步进',
    'StepSmall'             => '小步步进',
    'Stills'                => '静止',
    'Stop'                  => '停止',
    'Stopped'               => '已停止',
    'StorageArea'          => '存储区域',           // Added - 2018-08-30
    'StorageDoDelete'      => '开始删除',             // Added - 2020-04-09
    'StorageScheme'        => '存储方案',                 // Added - 2018-08-30
    'Stream'                => '流',
    'StreamReplayBuffer'    => '流重放影像缓冲',
    'Submit'                => '发送',
    'System'                => '系统',
    'SystemLog'            => '系统日志',             // Added - 2011-06-16
    'TZUnset'              => '未设置 - 使用php.ini的配置', // Added - 2020-04-09
    'TargetColorspace'     => '色彩空间',      // Added - 2015-04-18
    'Tele'                  => 'Tele',
    'Thumbnail'             => '缩略图',
    'Tilt'                  => '倾斜',
    'Time'                  => '时间',
    'TimeDelta'             => '相对时间',
    'TimeStamp'             => '时间戳',
    'Timeline'              => '时间轴',
    'TimelineTip1'          => '移动你的鼠标到图表上来查看快照图片和事件详情。',              // Added 2013.08.15.
    'TimelineTip2'          => '单击图形或图像的彩色部分来查看事件。',              // Added 2013.08.15.
    'TimelineTip3'          => 'Click on the background to zoom in to a smaller time period based around your click.',              // Added 2013.08.15.
    'TimelineTip4'          => 'Use the controls below to zoom out or navigate back and forward through the time range.',              // Added 2013.08.15.
    'Timestamp'             => '时间戳',
    'TimestampLabelFormat'  => '时间戳标签格式',
    'TimestampLabelSize'   => '字体大小',              // Added - 2018-08-30
    'TimestampLabelX'       => '时间戳标签 X',
    'TimestampLabelY'       => '时间戳标签 Y',
    'Today'                 => '今天',
    'Tools'                 => '工具',
    'Total'                => '总',                  // Added - 2011-06-16
    'TotalBrScore'          => '总<br/>分数',
    'TrackDelay'            => '轨迹延时',
    'TrackMotion'           => '轨迹运动',
    'Triggers'              => '触发器',
    'TurboPanSpeed'         => '加速平移速度',
    'TurboTiltSpeed'        => '加速倾斜速度',
    'Type'                  => '类型',
    'Unarchive'             => '未存档',
    'Undefined'             => '未定义',
    'Units'                 => '单元',
    'Unknown'               => '未知',
    'Update'                => '更新',
    'UpdateAvailable'       => '有新版本的ZoneMinder.',
    'UpdateNotNecessary'    => '无须更新',
    'Updated'              => '已更新',                // Added - 2011-06-16
    'Upload'               => '上传',                 // Added - 2011-08-23
    'UseFilter'             => '使用筛选器',
    'UseFilterExprsPost'    => '&nbsp;筛选器&nbsp;表达式', // This is used at the end of the phrase 'use N filter expressions'
    'UseFilterExprsPre'     => '使用&nbsp;', // This is used at the beginning of the phrase 'use N filter expressions'
    'UsedPlugins'	   => '已使用的插件',
    'User'                  => '用户',
    'Username'              => '用户名',
    'Users'                 => '用户',
    'V4L'                  => 'V4L',                    // Added - 2015-04-18
    'V4LCapturesPerFrame'  => '每帧捕获',     // Added - 2015-04-18
    'V4LMultiBuffer'       => '多缓冲',        // Added - 2015-04-18
    'Value'                 => '数值',
    'Version'               => '版本',
    'VersionIgnore'         => '忽略该版本',
    'VersionRemindDay'      => '一天内再次提醒',
    'VersionRemindHour'     => '一小时内再次提醒',
    'VersionRemindMonth'   => '一个月内再次提醒', // Added - 2020-04-09
    'VersionRemindNever'    => '不再提醒新版本',
    'VersionRemindWeek'     => '一周内再次提醒',
    'Video'                 => '视频',
    'VideoFormat'           => '视频格式',
    'VideoGenFailed'        => '视频产生失败!',
    'VideoGenFiles'         => '现有视频文件',
    'VideoGenNoFiles'       => '没有找到视频文件',
    'VideoGenParms'         => '视频产生参数',
    'VideoGenSucceeded'     => '视频产生成功!',
    'VideoSize'             => '视频尺寸',
    'VideoWriter'          => '保存为视频',           // Added - 2018-08-30
    'View'                  => '查看',
    'ViewAll'               => '查看全部',
    'ViewEvent'             => '查看事件',
    'ViewMatches'          => '查看匹配项',           // Added - 2020-04-09
    'ViewPaged'             => '查看分页',
    'Wake'                  => '唤醒',
    'WarmupFrames'          => '预热帪',
    'Watch'                 => '观察',
    'Web'                   => 'Web',
    'WebColour'             => 'Web颜色',
    'WebSiteUrl'           => '网站链接',            // Added - 2018-08-30
    'Week'                  => '周',
    'White'                 => '白',
    'WhiteBalance'          => '白平衡',
    'Wide'                  => '宽',
    'X'                     => 'X',
    'X10'                   => 'X10',
    'X10ActivationString'   => 'X10 激活字符',
    'X10InputAlarmString'   => 'X10 输入警报字符',
    'X10OutputAlarmString'  => 'X10 输出警报字符',
    'Y'                     => 'Y',
    'Yes'                   => '是',
    'YouNoPerms'            => '您没有访问该资源的权限。',
    'Zone'                  => '区域',
    'ZoneAlarmColour'       => '报警色彩 (红/绿/蓝)',
    'ZoneArea'              => '区域',
    'ZoneExtendAlarmFrames' => 'Extend Alarm Frame Count',
    'ZoneFilterSize'        => '过滤宽度/高度 (像素)',
    'ZoneMinMaxAlarmArea'   => '最小/最大报警区域',
    'ZoneMinMaxBlobArea'    => '最小/最大污渍区 Blob',
    'ZoneMinMaxBlobs'       => '最小/最大污渍区数 Blobs',
    'ZoneMinMaxFiltArea'    => '最小/最大过滤区域',
    'ZoneMinMaxPixelThres'  => '最小/最大像素阈值(0-255)',
    'ZoneMinderLog'        => 'ZoneMinder日志',         // Added - 2011-06-17
    'ZoneOverloadFrames'    => '忽略过载帪数',
    'Zones'                 => '区域',
    'Zoom'                  => '缩放',
    'ZoomIn'                => '放大',
    'ZoomOut'               => '缩小',
);

// Complex replacements with formatting and/or placements, must be passed through sprintf
$CLANG = array(
    'CurrentLogin'          => '当前登入的是 \'%1$s\'',
    'EventCount'            => '%1$s %2$s', // For example '37 Events' (from Vlang below)
    'LastEvents'            => '最新 %1$s %2$s', // For example 'Last 37 Events' (from Vlang below)
    'LatestRelease'         => '最新版为 v%1$s, 您有的是 v%2$s.',
    'MonitorCount'          => '%1$s %2$s', // For example '4 Monitors' (from Vlang below)
    'MonitorFunction'       => '监视器 %1$s 功能',
    'RunningRecentVer'      => '您运行的是最新版的 ZoneMinder, v%s.',
    'VersionMismatch'      => '版本不匹配, 系统版本是 %1$s, 数据库版本是 %2$s.', // Added - 2011-05-25
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
    'Event'                 => array( 0=>'事件', 1=>'事件', 2=>'事件' ),
    'Monitor'               => array( 0=>'监视器', 1=>'监视器', 2=>'监视器' ),
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
