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
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//

// ZoneMinder Russian Translation by Borodin A.S.

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
header( "Content-Type: text/html; charset=koi8-r" );

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
    '24BitColour'          => '24 битный цвет',
    '32BitColour'          => '32 битный цвет',          // Added - 2011-06-15
    '8BitGrey'             => '256 оттенков серого',
    'Action'               => 'Action',
    'Actual'               => 'Действительный',
    'AddNewControl'        => 'Add New Control',
    'AddNewMonitor'        => 'Добавить монитор',
    'AddNewUser'           => 'Добавить пользователя',
    'AddNewZone'           => 'Добавить зону',
    'Alarm'                => 'Тревога',
    'AlarmBrFrames'        => 'Кадры<br/>тревоги',
    'AlarmFrame'           => 'Кадр тревоги',
    'AlarmFrameCount'      => 'Alarm Frame Count',
    'AlarmLimits'          => 'Гран.&nbsp;зоны&nbsp;трев.',
    'AlarmMaximumFPS'      => 'Alarm Maximum FPS',
    'AlarmPx'              => 'Пкс&nbsp;трев.',
    'AlarmRGBUnset'        => 'You must set an alarm RGB colour',
    'Alert'                => 'Настороже',
    'All'                  => 'Все',
    'Apply'                => 'Применить',
    'ApplyingStateChange'  => 'Состояние сервиса изменяется',
    'ArchArchived'         => 'Только в архиве',
    'ArchUnarchived'       => 'Только не в архиве',
    'Archive'              => 'Архив',
    'Archived'             => 'Archived',
    'Area'                 => 'Area',
    'AreaUnits'            => 'Area (px/%)',
    'AttrAlarmFrames'      => 'Кол-во кадров тревоги',
    'AttrArchiveStatus'    => 'Статус архивации',
    'AttrAvgScore'         => 'Сред. оценка',
    'AttrCause'            => 'Cause',
    'AttrDate'             => 'Дата',
    'AttrDateTime'         => 'Дата/Время',
    'AttrDiskBlocks'       => 'Disk Blocks',
    'AttrDiskPercent'      => 'Disk Percent',
    'AttrDuration'         => 'Длительность',
    'AttrFrames'           => 'Кол-во кадров',
    'AttrId'               => 'Id',
    'AttrMaxScore'         => 'Макс. оценка',
    'AttrMonitorId'        => 'Id Монитора',
    'AttrMonitorName'      => 'Название Монитора',
    'AttrName'             => 'Name',
    'AttrNotes'            => 'Notes',
    'AttrSystemLoad'       => 'System Load',
    'AttrTime'             => 'Время',
    'AttrTotalScore'       => 'Сумм. оценка',
    'AttrWeekday'          => 'День недели',
    'Auto'                 => 'Auto',
    'AutoStopTimeout'      => 'Auto Stop Timeout',
    'Available'            => 'Available',              // Added - 2009-03-31
    'AvgBrScore'           => 'Сред.<br/>оценка',
    'Background'           => 'Background',
    'BackgroundFilter'     => 'Run filter in background',
    'BadAlarmFrameCount'   => 'Alarm frame count must be an integer of one or more',
    'BadAlarmMaxFPS'       => 'Alarm Maximum FPS must be a positive integer or floating point value',
    'BadChannel'           => 'Channel must be set to an integer of zero or more',
    'BadColours'           => 'Target colour must be set to a valid value', // Added - 2011-06-15
    'BadDevice'            => 'Device must be set to a valid value',
    'BadFPSReportInterval' => 'FPS report interval buffer count must be an integer of 0 or more',
    'BadFormat'            => 'Format must be set to an integer of zero or more',
    'BadFrameSkip'         => 'Frame skip count must be an integer of zero or more',
    'BadMotionFrameSkip'   => 'Motion Frame skip count must be an integer of zero or more',
    'BadHeight'            => 'Height must be set to a valid value',
    'BadHost'              => 'Host must be set to a valid ip address or hostname, do not include http://',
    'BadImageBufferCount'  => 'Image buffer size must be an integer of 10 or more',
    'BadLabelX'            => 'Label X co-ordinate must be set to an integer of zero or more',
    'BadLabelY'            => 'Label Y co-ordinate must be set to an integer of zero or more',
    'BadMaxFPS'            => 'Maximum FPS must be a positive integer or floating point value',
    'BadNameChars'         => 'Names may only contain alphanumeric characters plus hyphen and underscore',
    'BadPalette'           => 'Palette must be set to a valid value', // Added - 2009-03-31
    'BadPath'              => 'Path must be set to a valid value',
    'BadPort'              => 'Port must be set to a valid number',
    'BadPostEventCount'    => 'Post event image count must be an integer of zero or more',
    'BadPreEventCount'     => 'Pre event image count must be at least zero, and less than image buffer size',
    'BadRefBlendPerc'      => 'Reference blend percentage must be a positive integer',
    'BadSectionLength'     => 'Section length must be an integer of 30 or more',
    'BadSignalCheckColour' => 'Signal check colour must be a valid RGB colour string',
    'BadStreamReplayBuffer'=> 'Stream replay buffer must be an integer of zero or more',
    'BadWarmupCount'       => 'Warmup frames must be an integer of zero or more',
    'BadWebColour'         => 'Web colour must be a valid web colour string',
    'BadWidth'             => 'Width must be set to a valid value',
    'Bandwidth'            => 'канал',
    'BandwidthHead'         => 'Bandwidth',	// This is the end of the bandwidth status on the top of the console, different in many language due to phrasing
    'BlobPx'               => 'Пкс объекта',
    'BlobSizes'            => 'Размер объектов',
    'Blobs'                => 'Кол-во объектов',
    'Brightness'           => 'Яркость',
    'Buffers'              => 'Буферы',
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
    'Cancel'               => 'Отменить',
    'CancelForcedAlarm'    => 'Отменить форсированную тревогу',
    'CaptureHeight'        => 'Размер по Y',
    'CaptureMethod'        => 'Capture Method',         // Added - 2009-02-08
    'CapturePalette'       => 'Режим захвата',
    'CaptureWidth'         => 'Размер по X',
    'Cause'                => 'Cause',
    'CheckMethod'          => 'Метод проверки тревоги',
    'ChooseDetectedCamera' => 'Choose Detected Camera', // Added - 2009-03-31
    'ChooseFilter'         => 'Выбрать фильтр',
    'ChooseLogFormat'      => 'Choose a log format',    // Added - 2011-06-17
    'ChooseLogSelection'   => 'Choose a log selection', // Added - 2011-06-17
    'ChoosePreset'         => 'Choose Preset',
    'Clear'                => 'Clear',                  // Added - 2011-06-16
    'Close'                => 'Закрыть',
    'Colour'               => 'Цвет',
    'Command'              => 'Command',
    'Component'            => 'Component',              // Added - 2011-06-16
    'Config'               => 'Config',
    'ConfiguredFor'        => 'Настроен на',
    'ConfirmDeleteEvents'  => 'Are you sure you wish to delete the selected events?',
    'ConfirmPassword'      => 'Подтвердите пароль',
    'ConjAnd'              => 'и',
    'ConjOr'               => 'или',
    'Console'              => 'Сервер',
    'ContactAdmin'         => 'Пожалуйста обратитесь к вашему администратору.',
    'Continue'             => 'Continue',
    'Contrast'             => 'Контраст',
    'Control'              => 'Control',
    'ControlAddress'       => 'Control Address',
    'ControlCap'           => 'Control Capability',
    'ControlCaps'          => 'Control Capabilities',
    'ControlDevice'        => 'Control Device',
    'ControlType'          => 'Control Type',
    'Controllable'         => 'Controllable',
    'Cycle'                => 'Cycle',
    'CycleWatch'           => 'Циклический просмотр',
    'DateTime'             => 'Date/Time',              // Added - 2011-06-16
    'Day'                  => 'День',
    'Debug'                => 'Debug',
    'DefaultRate'          => 'Default Rate',
    'DefaultScale'         => 'Default Scale',
    'DefaultView'          => 'Default View',
    'Delete'               => 'Удалить',
    'DeleteAndNext'        => 'Удалить &amp; след.',
    'DeleteAndPrev'        => 'Удалить &amp; пред.',
    'DeleteSavedFilter'    => 'Удалить сохраненный фильтр',
    'Description'          => 'Описание',
    'DetectedCameras'      => 'Detected Cameras',       // Added - 2009-03-31
    'Device'               => 'Device',                 // Added - 2009-02-08
    'DeviceChannel'        => 'Канал',
    'DeviceFormat'         => 'Формат',
    'DeviceNumber'         => 'Номер устройства',
    'DevicePath'           => 'Device Path',
    'Devices'              => 'Devices',
    'Dimensions'           => 'Размеры',
    'DisableAlarms'        => 'Disable Alarms',
    'Disk'                 => 'Disk',
    'Display'              => 'Display',                // Added - 2011-01-30
    'Displaying'           => 'Displaying',             // Added - 2011-06-16
    'Donate'               => 'Please Donate',
    'DonateAlready'        => 'No, I\'ve already donated',
    'DonateEnticement'     => 'You\'ve been running ZoneMinder for a while now and hopefully are finding it a useful addition to your home or workplace security. Although ZoneMinder is, and will remain, free and open source, it costs money to develop and support. If you would like to help support future development and new features then please consider donating. Donating is, of course, optional but very much appreciated and you can donate as much or as little as you like.<br><br>If you would like to donate please select the option below or go to http://www.zoneminder.com/donate.html in your browser.<br><br>Thank you for using ZoneMinder and don\'t forget to visit the forums on ZoneMinder.com for support or suggestions about how to make your ZoneMinder experience even better.',
    'DonateRemindDay'      => 'Not yet, remind again in 1 day',
    'DonateRemindHour'     => 'Not yet, remind again in 1 hour',
    'DonateRemindMonth'    => 'Not yet, remind again in 1 month',
    'DonateRemindNever'    => 'No, I don\'t want to donate, never remind',
    'DonateRemindWeek'     => 'Not yet, remind again in 1 week',
    'DonateYes'            => 'Yes, I\'d like to donate now',
    'DoNativeMotionDetection'=> 'Do Native Motion Detection',
    'Download'             => 'Download',
    'DuplicateMonitorName' => 'Duplicate Monitor Name', // Added - 2009-03-31
    'Duration'             => 'Длительность',
    'Edit'                 => 'Редактирование',
    'Email'                => 'Email',
    'EnableAlarms'         => 'Enable Alarms',
    'Enabled'              => 'разрешен',
    'EnterNewFilterName'   => 'Введите новое название фильтра',
    'Error'                => 'Ошибка',
    'ErrorBrackets'        => 'Ошибка: количество открывающих и закрывающих скобок должно быть одинаковым',
    'ErrorValidValue'      => 'Ошибка: проверьте что все термы имеют действительное значение',
    'Etc'                  => 'и т.д.',
    'Event'                => 'Событие',
    'EventFilter'          => 'Фильтр событий',
    'EventId'              => 'Event Id',
    'EventName'            => 'Event Name',
    'EventPrefix'          => 'Event Prefix',
    'Events'               => 'События',
    'Exclude'              => 'Исключить',
    'Execute'              => 'Execute',
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
    'FPS'                  => 'к/c',
    'FPSReportInterval'    => 'Период обновления индикации скорости',
    'FTP'                  => 'FTP',
    'Far'                  => 'Far',
    'FastForward'          => 'Fast Forward',
    'Feed'                 => 'Feed',
    'Ffmpeg'               => 'Ffmpeg',                 // Added - 2009-02-08
    'File'                 => 'File',
    'FilterArchiveEvents'  => 'Archive all matches',
    'FilterDeleteEvents'   => 'Delete all matches',
    'FilterEmailEvents'    => 'Email details of all matches',
    'FilterExecuteEvents'  => 'Execute command on all matches',
    'FilterMessageEvents'  => 'Message details of all matches',
    'FilterPx'             => 'Пкс фильтра',
    'FilterUnset'          => 'You must specify a filter width and height',
    'FilterUploadEvents'   => 'Upload all matches',
    'FilterVideoEvents'    => 'Create video for all matches',
    'Filters'              => 'Filters',
    'First'                => 'Первый',
    'FlippedHori'          => 'Flipped Horizontally',
    'FlippedVert'          => 'Flipped Vertically',
    'FnNone'                => 'None',            // Added 2013.08.16.
    'FnMonitor'             => 'Monitor',            // Added 2013.08.16.
    'FnModect'              => 'Modect',            // Added 2013.08.16.
    'FnRecord'              => 'Record',            // Added 2013.08.16.
    'FnMocord'              => 'Mocord',            // Added 2013.08.16.
    'FnNodect'              => 'Nodect',            // Added 2013.08.16.
    'Focus'                => 'Focus',
    'ForceAlarm'           => 'Включить тревогу',
    'Format'               => 'Format',
    'Frame'                => 'Кадр',
    'FrameId'              => 'Id кадра',
    'FrameRate'            => 'Скорость',
    'FrameSkip'            => 'Пропускать кадры',
    'MotionFrameSkip'      => 'Motion Frame Skip',
    'Frames'               => 'кадры',
    'Func'                 => 'Функ.',
    'Function'             => 'Функция',
    'Gain'                 => 'Gain',
    'General'              => 'General',
    'GenerateVideo'        => 'Генерировать видео',
    'GeneratingVideo'      => 'Генерируется видео',
    'GoToZoneMinder'       => 'Перейти на ZoneMinder.com',
    'Grey'                 => 'ч/б',
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
    'High'                 => 'широкий',
    'HighBW'               => 'Широкий канал',
    'Home'                 => 'Home',
    'Hour'                 => 'Час',
    'Hue'                  => 'Оттенок',
    'Id'                   => 'Id',
    'Idle'                 => 'Idle',
    'Ignore'               => 'Игнорировать',
    'Image'                => 'Изображение',
    'ImageBufferSize'      => 'Размер буфера изображения',
    'Images'               => 'Images',
    'In'                   => 'In',
    'Include'              => 'Включить',
    'Inverted'             => 'Инвертировать',
    'Iris'                 => 'Iris',
    'KeyString'            => 'Key String',
    'Label'                => 'Label',
    'Language'             => 'Язык',
    'Last'                 => 'Последний',
    'Layout'               => 'Layout',                 // Added - 2009-02-08
    'Level'                => 'Level',                  // Added - 2011-06-16
    'Libvlc'               => 'Libvlc',
    'LimitResultsPost'     => 'results only;', // This is used at the end of the phrase 'Limit to first N results only'
    'LimitResultsPre'      => 'Limit to first', // This is used at the beginning of the phrase 'Limit to first N results only'
    'Line'                 => 'Line',                   // Added - 2011-06-16
    'LinkedMonitors'       => 'Linked Monitors',
    'List'                 => 'List',
    'Load'                 => 'Load',
    'Local'                => 'Локальный',
    'Log'                  => 'Log',                    // Added - 2011-06-16
    'LoggedInAs'           => 'Пользователь',
    'Logging'              => 'Logging',                // Added - 2011-06-16
    'LoggingIn'            => 'Вход в систему',
    'Login'                => 'Войти',
    'Logout'               => 'Выйти',
    'Logs'                 => 'Logs',                   // Added - 2011-06-17
    'Low'                  => 'узкий',
    'LowBW'                => 'Узкий канал',
    'Main'                 => 'Main',
    'Man'                  => 'Man',
    'Manual'               => 'Manual',
    'Mark'                 => 'Метка',
    'Max'                  => 'Макс.',
    'MaxBandwidth'         => 'Max Bandwidth',
    'MaxBrScore'           => 'Макс.<br/>оценка',
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
    'MaximumFPS'           => 'Ограничение скорости записи (к/с)',
    'Medium'               => 'средний',
    'MediumBW'             => 'Обычный канал',
    'Message'              => 'Message',                // Added - 2011-06-16
    'MinAlarmAreaLtMax'    => 'Minimum alarm area should be less than maximum',
    'MinAlarmAreaUnset'    => 'You must specify the minimum alarm pixel count',
    'MinBlobAreaLtMax'     => 'Минимальная площадь объекта должна быть меньше чем максимальная площадь объекта',
    'MinBlobAreaUnset'     => 'You must specify the minimum blob pixel count',
    'MinBlobLtMinFilter'   => 'Minimum blob area should be less than or equal to minimum filter area',
    'MinBlobsLtMax'        => 'Минимальное число объектов должно быть меньше чем максимальное число объектов',
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
    'MinPixelThresLtMax'   => 'Нижний порог кол-ва пикселей должен быть ниже верхнего порога кол-ва пикселей',
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
    'Misc'                 => 'Разное',
    'Monitor'              => 'Монитор',
    'MonitorIds'           => 'Id&nbsp;Мониторов',
    'MonitorPreset'        => 'Monitor Preset',
    'MonitorPresetIntro'   => 'Select an appropriate preset from the list below.<br><br>Please note that this may overwrite any values you already have configured for this monitor.<br><br>',
    'MonitorProbe'         => 'Monitor Probe',          // Added - 2009-03-31
    'MonitorProbeIntro'    => 'The list below shows detected analog and network cameras and whether they are already being used or available for selection.<br/><br/>Select the desired entry from the list below.<br/><br/>Please note that not all cameras may be detected and that choosing a camera here may overwrite any values you already have configured for the current monitor.<br/><br/>', // Added - 2009-03-31
    'Monitors'             => 'Мониторы',
    'Montage'              => 'Montage',
    'Month'                => 'Месяц',
    'More'                 => 'More',                   // Added - 2011-06-16
    'Move'                 => 'Move',
    'MtgDefault'           => 'Default',              // Added 2013.08.15.
    'Mtg2widgrd'           => '2-wide grid',              // Added 2013.08.15.
    'Mtg3widgrd'           => '3-wide grid',              // Added 2013.08.15.
    'Mtg4widgrd'           => '4-wide grid',              // Added 2013.08.15.
    'Mtg3widgrx'           => '3-wide grid, scaled, enlarge on alarm',              // Added 2013.08.15.
    'MustBeGe'             => 'должно быть больше или равно',
    'MustBeLe'             => 'должно быть меньше или равно',
    'MustConfirmPassword'  => 'Вы должны подтвердить пароль',
    'MustSupplyPassword'   => 'Вы должны ввести пароль',
    'MustSupplyUsername'   => 'Вы должны ввести имя пользователя',
    'Name'                 => 'Имя',
    'Near'                 => 'Near',
    'Network'              => 'Сеть',
    'New'                  => 'Нов.',
    'NewGroup'             => 'New Group',
    'NewLabel'             => 'New Label',
    'NewPassword'          => 'Новый пароль',
    'NewState'             => 'Новое состояние',
    'NewUser'              => 'Новый пользователь',
    'Next'                 => 'След.',
    'No'                   => 'Нет',
    'NoDetectedCameras'    => 'No Detected Cameras',    // Added - 2009-03-31
    'NoFramesRecorded'     => 'Это событие не содежит кадров',
    'NoGroup'              => 'No Group',
    'NoSavedFilters'       => 'нет сохраненных фильтров',
    'NoStatisticsRecorded' => 'Статистика по этому событию/кадру не записана',
    'None'                 => 'отсутствует',
    'NoneAvailable'        => 'не доступны',
    'Normal'               => 'Нормальная',
    'Notes'                => 'Notes',
    'NumPresets'           => 'Num Presets',
    'Off'                  => 'Off',
    'On'                   => 'On',
    'OpEq'                 => 'равно',
    'OpGt'                 => 'больше',
    'OpGtEq'               => 'больше либо равно',
    'OpIn'                 => 'в списке',
    'OpLt'                 => 'меньше',
    'OpLtEq'               => 'меньше или равно',
    'OpMatches'            => 'совпадает',
    'OpNe'                 => 'не равно',
    'OpNotIn'              => 'не в списке',
    'OpNotMatches'         => 'не совпадает',
    'Open'                 => 'Open',
    'OptionHelp'           => 'OptionHelp',
    'OptionRestartWarning' => 'Эти изменения подействуют только после перезапуска программы.',
    'Options'              => 'Опции',
    'OrEnterNewName'       => 'или введите новое имя',
    'Order'                => 'Order',
    'Orientation'          => 'Ориентация',
    'Out'                  => 'Out',
    'OverwriteExisting'    => 'Перезаписать существующее',
    'Paged'                => 'По страницам',
    'Pan'                  => 'Pan',
    'PanLeft'              => 'Pan Left',
    'PanRight'             => 'Pan Right',
    'PanTilt'              => 'Pan/Tilt',
    'Parameter'            => 'Парамер',
    'Password'             => 'Пароль',
    'PasswordsDifferent'   => 'Пароли не совпадают',
    'Paths'                => 'Пути',
    'Pause'                => 'Pause',
    'Phone'                => 'Phone',
    'PhoneBW'              => 'Телефонная линия',
    'Pid'                  => 'PID',                    // Added - 2011-06-16
    'PixelDiff'            => 'Pixel Diff',
    'Pixels'               => 'в пикселях',
    'Play'                 => 'Play',
    'PlayAll'              => 'Play All',
    'PleaseWait'           => 'Пожалуйста подождите',
    'Plugins'              => 'Plugins',
    'Point'                => 'Point',
    'PostEventImageBuffer' => 'Буфер после события',
    'PreEventImageBuffer'  => 'Буфер до события',
    'PreserveAspect'       => 'Preserve Aspect Ratio',
    'Preset'               => 'Preset',
    'Presets'              => 'Presets',
    'Prev'                 => 'Пред.',
    'Probe'                => 'Probe',                  // Added - 2009-03-31
    'Protocol'             => 'Protocol',
    'Rate'                 => 'Скорость',
    'Real'                 => 'Реальная',
    'Record'               => 'Record',
    'RefImageBlendPct'     => 'Прозрачность опорного кадра, %',
    'Refresh'              => 'Обновить',
    'Remote'               => 'Удаленный',
    'RemoteHostName'       => 'Имя удаленного хоста',
    'RemoteHostPath'       => 'Путь на удаленном хосте',
    'RemoteHostPort'       => 'удаленный порт',
    'RemoteHostSubPath'    => 'Remote Host SubPath',    // Added - 2009-02-08
    'RemoteImageColours'   => 'Цветность на удаленном хосте',
    'RemoteMethod'         => 'Remote Method',          // Added - 2009-02-08
    'RemoteProtocol'       => 'Remote Protocol',        // Added - 2009-02-08
    'Rename'               => 'Переименовать',
    'Replay'               => 'Replay',
    'ReplayAll'            => 'All Events',
    'ReplayGapless'        => 'Gapless Events',
    'ReplaySingle'         => 'Single Event',
    'Reset'                => 'Reset',
    'ResetEventCounts'     => 'Обнулить счетчик событий',
    'Restart'              => 'Перезапустить',
    'Restarting'           => 'Перезапускается',
    'RestrictedCameraIds'  => 'Id запрещенных камер',
    'RestrictedMonitors'   => 'Restricted Monitors',
    'ReturnDelay'          => 'Return Delay',
    'ReturnLocation'       => 'Return Location',
    'Rewind'               => 'Rewind',
    'RotateLeft'           => 'Повернуть влево',
    'RotateRight'          => 'Повернуть вправо',
    'RunLocalUpdate'       => 'Please run zmupdate.pl to update', // Added - 2011-05-25
    'RunMode'              => 'Режим работы',
    'RunState'             => 'Состояние',
    'Running'              => 'Выполняется',
    'Save'                 => 'Сохранить',
    'SaveAs'               => 'Сохранить как',
    'SaveFilter'           => 'Сохранить фильтр',
    'Scale'                => 'Масштаб',
    'Score'                => 'Оценка',
    'Secs'                 => 'Сек.',
    'Sectionlength'        => 'Длина секции (в кадрах)',
    'Select'               => 'Select',
    'SelectFormat'         => 'Select Format',          // Added - 2011-06-17
    'SelectLog'            => 'Select Log',             // Added - 2011-06-17
    'SelectMonitors'       => 'Select Monitors',
    'SelfIntersecting'     => 'Polygon edges must not intersect',
    'Set'                  => 'Set',
    'SetNewBandwidth'      => 'Установка новой ширина канала',
    'SetPreset'            => 'Set Preset',
    'Settings'             => 'Настройки',
    'ShowFilterWindow'     => 'Показать окно фильтра',
    'ShowTimeline'         => 'Show Timeline',
    'SignalCheckColour'    => 'Signal Check Colour',
    'Size'                 => 'Size',
    'SkinDescription'      => 'Change the default skin for this computer', // Added - 2011-01-30
    'Sleep'                => 'Sleep',
    'SortAsc'              => 'Asc',
    'SortBy'               => 'Sort by',
    'SortDesc'             => 'Desc',
    'Source'               => 'Источник',
    'SourceColours'        => 'Source Colours',         // Added - 2009-02-08
    'SourcePath'           => 'Source Path',            // Added - 2009-02-08
    'SourceType'           => 'Тип источника',
    'Speed'                => 'Speed',
    'SpeedHigh'            => 'High Speed',
    'SpeedLow'             => 'Low Speed',
    'SpeedMedium'          => 'Medium Speed',
    'SpeedTurbo'           => 'Turbo Speed',
    'Start'                => 'Запустить',
    'State'                => 'Состояние',
    'Stats'                => 'Статистика',
    'Status'               => 'Статус',
    'Step'                 => 'Step',
    'StepBack'             => 'Step Back',
    'StepForward'          => 'Step Forward',
    'StepLarge'            => 'Large Step',
    'StepMedium'           => 'Medium Step',
    'StepNone'             => 'No Step',
    'StepSmall'            => 'Small Step',
    'Stills'               => 'Стоп-кадры',
    'Stop'                 => 'Остановить',
    'Stopped'              => 'Остановлен',
    'Stream'               => 'Поток',
    'StreamReplayBuffer'   => 'Stream Replay Image Buffer',
    'Submit'               => 'Submit',
    'System'               => 'Система',
    'SystemLog'            => 'System Log',             // Added - 2011-06-16
    'Tele'                 => 'Tele',
    'Thumbnail'            => 'Thumbnail',
    'Tilt'                 => 'Tilt',
    'Time'                 => 'Время',
    'TimeDelta'            => 'Относительное время',
    'TimeStamp'            => 'Метка времени',
    'Timeline'             => 'Timeline',
    'TimelineTip1'         => 'Pass your mouse over the graph to view a snapshot image and event details.',              // Added 2013.08.15.
    'TimelineTip2'         => 'Click on the coloured sections of the graph, or the image, to view the event.',              // Added 2013.08.15.
    'TimelineTip3'         => 'Click on the background to zoom in to a smaller time period based around your click.',              // Added 2013.08.15.
    'TimelineTip4'         => 'Use the controls below to zoom out or navigate back and forward through the time range.',              // Added 2013.08.15.
    'Timestamp'            => 'Метка времени',
    'TimestampLabelFormat' => 'Формат метки',
    'TimestampLabelX'      => 'X-координата метки',
    'TimestampLabelY'      => 'Y-координата метки',
    'Today'                => 'Today',
    'Tools'                => 'Инструменты',
    'Total'                => 'Total',                  // Added - 2011-06-16
    'TotalBrScore'         => 'Сумм.<br/>оценка',
    'TrackDelay'           => 'Track Delay',
    'TrackMotion'          => 'Track Motion',
    'Triggers'             => 'Триггеры',
    'TurboPanSpeed'        => 'Turbo Pan Speed',
    'TurboTiltSpeed'       => 'Turbo Tilt Speed',
    'Type'                 => 'Тип',
    'Unarchive'            => 'Уд.&nbsp;из&nbsp;архива',
    'Undefined'            => 'Undefined',              // Added - 2009-02-08
    'Units'                => 'Ед. измерения',
    'Unknown'              => 'Unknown',
    'Update'               => 'Update',
    'UpdateAvailable'      => 'Доступно обновление ZoneMinder',
    'UpdateNotNecessary'   => 'Обновление не требуется',
    'Updated'              => 'Updated',                // Added - 2011-06-16
    'Upload'               => 'Upload',                 // Added - 2011-08-23
    'UsedPlugins'	   => 'Used Plugins',
    'UseFilter'            => 'Использовать фильтр',
    'UseFilterExprsPost'   => '&nbsp;выражений&nbsp;для&nbsp;фильтра', // This is used at the end of the phrase 'use N filter expressions'
    'UseFilterExprsPre'    => 'Испол.&nbsp;', // This is used at the beginning of the phrase 'use N filter expressions'
    'User'                 => 'Пользователь',
    'Username'             => 'Имя пользователя',
    'Users'                => 'Пользователи',
    'Value'                => 'Значение',
    'Version'              => 'Версия',
    'VersionIgnore'        => 'Игнорировать эту версию',
    'VersionRemindDay'     => 'Напомнить через день',
    'VersionRemindHour'    => 'Напомнить через час',
    'VersionRemindNever'   => 'Не говорить о новых версиях',
    'VersionRemindWeek'    => 'Напомнить через неделю',
    'Video'                => 'Видео',
    'VideoFormat'          => 'Video Format',
    'VideoGenFailed'       => 'Ошибка генерации видео!',
    'VideoGenFiles'        => 'Existing Video Files',
    'VideoGenNoFiles'      => 'No Video Files Found',
    'VideoGenParms'        => 'Параметры генерации видео',
    'VideoGenSucceeded'    => 'Video Generation Succeeded!',
    'VideoSize'            => 'Размер изображения',
    'View'                 => 'Просмотр',
    'ViewAll'              => 'Просм. все',
    'ViewEvent'            => 'View Event',
    'ViewPaged'            => 'Просм. постранично',
    'Wake'                 => 'Wake',
    'WarmupFrames'         => 'Кадры разогрева',
    'Watch'                => 'Watch',
    'Web'                  => 'Интерфейс',
    'WebColour'            => 'Web Colour',
    'Week'                 => 'Неделя',
    'White'                => 'White',
    'WhiteBalance'         => 'White Balance',
    'Wide'                 => 'Wide',
    'X'                    => 'X',
    'X10'                  => 'X10',
    'X10ActivationString'  => 'X10 Activation String',
    'X10InputAlarmString'  => 'X10 Input Alarm String',
    'X10OutputAlarmString' => 'X10 Output Alarm String',
    'Y'                    => 'Y',
    'Yes'                  => 'Да',
    'YouNoPerms'           => 'У вас не достаточно прав для доступа к этому ресурсу.',
    'Zone'                 => 'Зона',
    'ZoneAlarmColour'      => 'Цвет тревоги (Red/Green/Blue)',
    'ZoneArea'             => 'Zone Area',
    'ZoneFilterSize'       => 'Filter Width/Height (pixels)',
    'ZoneMinMaxAlarmArea'  => 'Min/Max Alarmed Area',
    'ZoneMinMaxBlobArea'   => 'Min/Max Blob Area',
    'ZoneMinMaxBlobs'      => 'Min/Max Blobs',
    'ZoneMinMaxFiltArea'   => 'Min/Max Filtered Area',
    'ZoneMinMaxPixelThres' => 'Min/Max Pixel Threshold (0-255)',
    'ZoneMinderLog'        => 'ZoneMinder Log',         // Added - 2011-06-17
    'ZoneOverloadFrames'   => 'Overload Frame Ignore Count',
    'ZoneExtendAlarmFrames' => 'Extend Alarm Frame Count',
    'Zones'                => 'Зоны',
    'Zoom'                 => 'Zoom',
    'ZoomIn'               => 'Zoom In',
    'ZoomOut'              => 'Zoom Out',
);

// Complex replacements with formatting and/or placements, must be passed through sprintf
$CLANG = array(
    'CurrentLogin'         => 'Текущий пользователь: \'%1$s\'',
    'EventCount'           => '%1$s %2$s', // For example '37 Events' (from Vlang below)
    'LastEvents'           => 'Последние %1$s %2$s', // For example 'Last 37 Events' (from Vlang below)
    'LatestRelease'        => 'Последняя версия: v%1$s, у Вас установлена: v%2$s.',
    'MonitorCount'         => '%1$s %2$s', // For example '4 Monitors' (from Vlang below)
    'MonitorFunction'      => 'Функция монитора %1$s',
    'RunningRecentVer'     => 'У вас установлена новейшая версия ZoneMinder, v%s.', 
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
// --> actually, if written in 'translit', or russian words in english letters,
// the example would be ( 1=>"Kartoshek", 2=>"Katroshka", 3=>"Kartoshki"); :)
//
// and the zmVlang function decides that the first form is used for counts ending in
// 0, 5-9 or 11-19 and the second form when ending in 1 etc.
//

// Variable arrays expressing plurality, see the zmVlang description above
$VLANG = array(
    'Event'                => array( 1=>'Событий', 2=>'Событие', 3=>'События' ),
    'Monitor'              => array( 1=>'Мониторов', 2=>'Монитор', 3=>'Монитора' ),
);

// You will need to choose or write a function that can correlate the plurality string arrays
// with variable counts. This is used to conjugate the Vlang arrays above with a number passed
// in to generate the correct noun form.
//
// In languages such as English this is fairly simple
// Note this still has to be used with printf etc to get the right formating
/*function zmVlang( $langVarArray, $count )
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
}*/

// This is an version that could be used in the Russian example above
// The rules are that the first word form is used if the count ends in
// 0, 5-9 or 11-19. The second form is used then the count ends in 1
// (not including 11 as above) and the third form is used when the
// count ends in 2-4, again excluding any values ending in 12-14.
//
function zmVlang( $langVarArray, $count )
{
    $secondlastdigit = ($count/10)%10;
    $lastdigit = $count%10;

    // Get rid of the special cases first, the teens
    if ( $secondlastdigit == 1 && $lastdigit != 0 )
    {
        return( $langVarArray[1] );
    }
    switch ( $lastdigit )
    {
        case 0 :
        case 5 :
        case 6 :
        case 7 :
        case 8 :
        case 9 :
        {
            return( $langVarArray[1] );
            break;
        }
        case 1 :
    {
            return( $langVarArray[2] );
            break;
        }
        case 2 :
        case 3 :
        case 4 :
        {
            return( $langVarArray[3] );
            break;
        }
    }
    die( 'Error, unable to correlate variable language string' );
}

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
		'Help' => "Parameters in this field are passwd on to FFmpeg. Multiple parameters can be separated by ,~~ ".
		          "Examples (do not enter quotes)~~~~".
		          "\"allowed_media_types=video\" Set datatype to request fromcam (audio, video, data)~~~~".
		          "\"reorder_queue_size=nnn\" Set number of packets to buffer for handling of reordered packets~~~~".
		          "\"loglevel=debug\" Set verbosiy of FFmpeg (quiet, panic, fatal, error, warning, info, verbose, debug)"
	),
	'OPTIONS_LIBVLC' => array(
		'Help' => "Parameters in this field are passwd on to libVLC. Multiple parameters can be separated by ,~~ ".
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
