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
$zmSlang24BitColour          = '24 битный цвет';
$zmSlang8BitGrey             = '256 оттенков серого';
$zmSlangAction               = 'Action';
$zmSlangActual               = 'Действительный';
$zmSlangAddNewControl        = 'Add New Control';
$zmSlangAddNewMonitor        = 'Добавить монитор';
$zmSlangAddNewUser           = 'Добавить пользователя';
$zmSlangAddNewZone           = 'Добавить зону';
$zmSlangAlarmBrFrames        = 'Кадры<br/>тревоги';
$zmSlangAlarmFrameCount      = 'Alarm Frame Count';
$zmSlangAlarmFrame           = 'Кадр тревоги';
$zmSlangAlarmLimits          = 'Гран.&nbsp;зоны&nbsp;трев.';
$zmSlangAlarm                = 'Тревога';
$zmSlangAlarmPx              = 'Пкс&nbsp;трев.';
$zmSlangAlarmRGBUnset        = 'You must set an alarm RGB colour';
$zmSlangAlert                = 'Настороже';
$zmSlangAll                  = 'Все';
$zmSlangApply                = 'Применить';
$zmSlangApplyingStateChange  = 'Состояние сервиса изменяется';
$zmSlangArchArchived         = 'Только в архиве';
$zmSlangArchive              = 'Архив';
$zmSlangArchived             = 'Archived';
$zmSlangArchUnarchived       = 'Только не в архиве';
$zmSlangArea                 = 'Area';
$zmSlangAreaUnits            = 'Area (px/%)';
$zmSlangAttrAlarmFrames      = 'Кол-во кадров тревоги';
$zmSlangAttrArchiveStatus    = 'Статус архивации';
$zmSlangAttrAvgScore         = 'Сред. оценка';
$zmSlangAttrCause            = 'Cause';
$zmSlangAttrDate             = 'Дата';
$zmSlangAttrDateTime         = 'Дата/Время';
$zmSlangAttrDiskBlocks       = 'Disk Blocks';
$zmSlangAttrDiskPercent      = 'Disk Percent';
$zmSlangAttrDuration         = 'Длительность';
$zmSlangAttrFrames           = 'Кол-во кадров';
$zmSlangAttrId               = 'Id';
$zmSlangAttrMaxScore         = 'Макс. оценка';
$zmSlangAttrMonitorId        = 'Id Монитора';
$zmSlangAttrMonitorName      = 'Название Монитора';
$zmSlangAttrMontage          = 'Монтаж';
$zmSlangAttrName             = 'Name';
$zmSlangAttrNotes            = 'Notes';
$zmSlangAttrTime             = 'Время';
$zmSlangAttrTotalScore       = 'Сумм. оценка';
$zmSlangAttrWeekday          = 'День недели';
$zmSlangAutoArchiveAbbr      = 'Archive';
$zmSlangAutoArchiveEvents    = 'Automatically archive all matches';
$zmSlangAuto                 = 'Auto';
$zmSlangAutoDeleteAbbr       = 'Delete';
$zmSlangAutoDeleteEvents     = 'Automatically delete all matches';
$zmSlangAutoEmailAbbr        = 'Email';
$zmSlangAutoEmailEvents      = 'Automatically email details of all matches';
$zmSlangAutoExecuteAbbr      = 'Execute';
$zmSlangAutoExecuteEvents    = 'Automatically execute command on all matches';
$zmSlangAutoMessageAbbr      = 'Message';
$zmSlangAutoMessageEvents    = 'Automatically message details of all matches';
$zmSlangAutoStopTimeout      = 'Auto Stop Timeout';
$zmSlangAutoUploadAbbr       = 'Upload';
$zmSlangAutoUploadEvents     = 'Automatically upload all matches';
$zmSlangAutoVideoAbbr        = 'Video';
$zmSlangAutoVideoEvents      = 'Automatically create video for all matches';
$zmSlangAvgBrScore           = 'Сред.<br/>оценка';
$zmSlangBadNameChars         = 'Names may only contain alphanumeric characters plus hyphen and underscore';
$zmSlangBandwidth            = 'канал';
$zmSlangBlobPx               = 'Пкс объекта';
$zmSlangBlobs                = 'Кол-во объектов';
$zmSlangBlobSizes            = 'Размер объектов';
$zmSlangBrightness           = 'Яркость';
$zmSlangBuffers              = 'Буферы';
$zmSlangCanAutoFocus         = 'Can Auto Focus';
$zmSlangCanAutoGain          = 'Can Auto Gain';
$zmSlangCanAutoIris          = 'Can Auto Iris';
$zmSlangCanAutoWhite         = 'Can Auto White Bal.';
$zmSlangCanAutoZoom          = 'Can Auto Zoom';
$zmSlangCancelForcedAlarm    = 'Отменить&nbsp;форсированную&nbsp;тревогу';
$zmSlangCancel               = 'Отменить';
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
$zmSlangCaptureHeight        = 'Размер по Y';
$zmSlangCapturePalette       = 'Режим захвата';
$zmSlangCaptureWidth         = 'Размер по X';
$zmSlangCause                = 'Cause';
$zmSlangCheckMethod          = 'Метод проверки тревоги';
$zmSlangChooseFilter         = 'Выбрать фильтр';
$zmSlangChoosePreset         = 'Choose Preset';
$zmSlangClose                = 'Закрыть';
$zmSlangColour               = 'Цвет';
$zmSlangCommand              = 'Command';
$zmSlangConfig               = 'Config';
$zmSlangConfiguredFor        = 'Настроен на';
$zmSlangConfirmPassword      = 'Подтвердите пароль';
$zmSlangConjAnd              = 'и';
$zmSlangConjOr               = 'или';
$zmSlangConsole              = 'Сервер';
$zmSlangContactAdmin         = 'Пожалуйста обратитесь к вашему администратору.';
$zmSlangContinue             = 'Continue';
$zmSlangContrast             = 'Контраст';
$zmSlangControlAddress       = 'Control Address';
$zmSlangControlCap           = 'Control Capability';
$zmSlangControlCaps          = 'Control Capabilities';
$zmSlangControl              = 'Control';
$zmSlangControlDevice        = 'Control Device';
$zmSlangControllable         = 'Controllable';
$zmSlangControlType          = 'Control Type';
$zmSlangCycle                = 'Cycle';
$zmSlangCycleWatch           = 'Циклический просмотр';
$zmSlangDay                  = 'День';
$zmSlangDefaultRate          = 'Default Rate';
$zmSlangDefaultScale         = 'Default Scale';
$zmSlangDeleteAndNext        = 'Удалить &amp; след.';
$zmSlangDeleteAndPrev        = 'Удалить &amp; пред.';
$zmSlangDelete               = 'Удалить';
$zmSlangDeleteSavedFilter    = 'Удалить сохраненный фильтр';
$zmSlangDescription          = 'Описание';
$zmSlangDeviceChannel        = 'Канал';
$zmSlangDeviceFormat         = 'Формат (0=PAL,1=NTSC и т.д.)';
$zmSlangDeviceNumber         = 'Номер устройства (/dev/video?)';
$zmSlangDevicePath           = 'Device Path';
$zmSlangDimensions           = 'Размеры';
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
$zmSlangDuration             = 'Длительность';
$zmSlangEdit                 = 'Редактирование';
$zmSlangEmail                = 'Email';
$zmSlangEnableAlarms         = 'Enable Alarms';
$zmSlangEnabled              = 'разрешен';
$zmSlangEnterNewFilterName   = 'Введите новое название фильтра';
$zmSlangErrorBrackets        = 'Ошибка: количество открывающих и закрывающих скобок должно быть одинаковым';
$zmSlangError                = 'Ошибка';
$zmSlangErrorValidValue      = 'Ошибка: проверьте что все термы имеют действительное значение';
$zmSlangEtc                  = 'и т.д.';
$zmSlangEventFilter          = 'Фильтр событий';
$zmSlangEventId              = 'Event Id';
$zmSlangEventName            = 'Event Name';
$zmSlangEvent                = 'Событие';
$zmSlangEventPrefix          = 'Event Prefix';
$zmSlangEvents               = 'События';
$zmSlangExclude              = 'Исключить';
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
$zmSlangFilterPx             = 'Пкс фильтра';
$zmSlangFilters              = 'Filters';
$zmSlangFilterUnset          = 'You must specify a filter width and height';
$zmSlangFirst                = 'Первый';
$zmSlangFlippedHori          = 'Flipped Horizontally';
$zmSlangFlippedVert          = 'Flipped Vertically';
$zmSlangFocus                = 'Focus';
$zmSlangForceAlarm           = 'Включить&nbsp;тревогу';
$zmSlangFormat               = 'Format';
$zmSlangFPS                  = 'к/c';
$zmSlangFPSReportInterval    = 'Период обновления индикации скорости';
$zmSlangFrame                = 'Кадр';
$zmSlangFrameId              = 'Id кадра';
$zmSlangFrameRate            = 'Скорость';
$zmSlangFrames               = 'кадры';
$zmSlangFrameSkip            = 'Пропускать кадры';
$zmSlangFTP                  = 'FTP';
$zmSlangFunc                 = 'Функ.';
$zmSlangFunction             = 'Функция';
$zmSlangGain                 = 'Gain';
$zmSlangGeneral              = 'General';
$zmSlangGenerateVideo        = 'Генерировать видео';
$zmSlangGeneratingVideo      = 'Генерируется видео';
$zmSlangGoToZoneMinder       = 'Перейти на ZoneMinder.com';
$zmSlangGrey                 = 'ч/б';
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
$zmSlangHighBW               = 'Широкий канал';
$zmSlangHigh                 = 'широкий';
$zmSlangHome                 = 'Home';
$zmSlangHour                 = 'Час';
$zmSlangHue                  = 'Оттенок';
$zmSlangId                   = 'Id';
$zmSlangIdle                 = 'Idle';
$zmSlangIgnore               = 'Игнорировать';
$zmSlangImageBufferSize      = 'Размер буфера изображения';
$zmSlangImage                = 'Изображение';
$zmSlangInclude              = 'Включить';
$zmSlangIn                   = 'In';
$zmSlangInverted             = 'Инвертировать';
$zmSlangIris                 = 'Iris';
$zmSlangLanguage             = 'Язык';
$zmSlangLast                 = 'Последний';
$zmSlangLimitResultsPost     = 'results only;'; // This is used at the end of the phrase 'Limit to first N results only'
$zmSlangLimitResultsPre      = 'Limit to first'; // This is used at the beginning of the phrase 'Limit to first N results only'
$zmSlangList                 = 'List';
$zmSlangLoad                 = 'Load';
$zmSlangLocal                = 'Локальный';
$zmSlangLoggedInAs           = 'Пользователь';
$zmSlangLoggingIn            = 'Вход в систему';
$zmSlangLogin                = 'Войти';
$zmSlangLogout               = 'Выйти';
$zmSlangLowBW                = 'Узкий канал';
$zmSlangLow                  = 'узкий';
$zmSlangMain                 = 'Main';
$zmSlangMan                  = 'Man';
$zmSlangManual               = 'Manual';
$zmSlangMark                 = 'Метка';
$zmSlangMaxBandwidth         = 'Max Bandwidth';
$zmSlangMaxBrScore           = 'Макс.<br/>оценка';
$zmSlangMaxFocusRange        = 'Max Focus Range';
$zmSlangMaxFocusSpeed        = 'Max Focus Speed';
$zmSlangMaxFocusStep         = 'Max Focus Step';
$zmSlangMaxGainRange         = 'Max Gain Range';
$zmSlangMaxGainSpeed         = 'Max Gain Speed';
$zmSlangMaxGainStep          = 'Max Gain Step';
$zmSlangMax                  = 'Макс.';
$zmSlangMaximumFPS           = 'Ограничение скорости записи (к/с)';
$zmSlangMaxIrisRange         = 'Max Iris Range';
$zmSlangMaxIrisSpeed         = 'Max Iris Speed';
$zmSlangMaxIrisStep          = 'Max Iris Step';
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
$zmSlangMediumBW             = 'Обычный канал';
$zmSlangMedium               = 'средний';
$zmSlangMinAlarmAreaLtMax    = 'Minimum alarm area should be less than maximum';
$zmSlangMinAlarmAreaUnset    = 'You must specify the minimum alarm pixel count';
$zmSlangMinBlobAreaLtMax     = 'Минимальная площадь объекта должна быть меньше чем максимальная площадь объекта';
$zmSlangMinBlobAreaUnset     = 'You must specify the minimum blob pixel count';
$zmSlangMinBlobLtMinFilter   = 'Minimum blob area should be less than or equal to minimum filter area';
$zmSlangMinBlobsLtMax        = 'Минимальное число объектов должно быть меньше чем максимальное число объектов';
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
$zmSlangMinPixelThresLtMax   = 'Нижний порог кол-ва пикселей должен быть ниже верхнего порога кол-ва пикселей';
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
$zmSlangMisc                 = 'Разное';
$zmSlangMonitorIds           = 'Id&nbsp;Мониторов';
$zmSlangMonitor              = 'Монитор';
$zmSlangMonitorPresetIntro   = 'Select an appropriate preset from the list below.<br><br>Please note that this may overwrite any values you already have configured for this monitor.<br><br>';
$zmSlangMonitorPreset        = 'Monitor Preset';
$zmSlangMonitors             = 'Мониторы';
$zmSlangMontage              = 'Montage';
$zmSlangMonth                = 'Месяц';
$zmSlangMove                 = 'Move';
$zmSlangMustBeGe             = 'должно быть больше или равно';
$zmSlangMustBeLe             = 'должно быть меньше или равно';
$zmSlangMustConfirmPassword  = 'Вы должны подтвердить пароль';
$zmSlangMustSupplyPassword   = 'Вы должны ввести пароль';
$zmSlangMustSupplyUsername   = 'Вы должны ввести имя пользователя';
$zmSlangName                 = 'Имя';
$zmSlangNear                 = 'Near';
$zmSlangNetwork              = 'Сеть';
$zmSlangNewGroup             = 'New Group';
$zmSlangNew                  = 'Нов.';
$zmSlangNewPassword          = 'Новый пароль';
$zmSlangNewState             = 'Новое состояние';
$zmSlangNewUser              = 'Новый пользователь';
$zmSlangNext                 = 'След.';
$zmSlangNoFramesRecorded     = 'Это событие не содежит кадров';
$zmSlangNoGroup              = 'No Group';
$zmSlangNo                   = 'Нет';
$zmSlangNoneAvailable        = 'не доступны';
$zmSlangNone                 = 'отсутствует';
$zmSlangNormal               = 'Нормальная';
$zmSlangNoSavedFilters       = 'нет сохраненных фильтров';
$zmSlangNoStatisticsRecorded = 'Статистика по этому событию/кадру не записана';
$zmSlangNotes                = 'Notes';
$zmSlangNumPresets           = 'Num Presets';
$zmSlangOpen                 = 'Open';
$zmSlangOpEq                 = 'равно';
$zmSlangOpGt                 = 'больше';
$zmSlangOpGtEq               = 'больше либо равно';
$zmSlangOpIn                 = 'в списке';
$zmSlangOpLtEq               = 'меньше или равно';
$zmSlangOpLt                 = 'меньше';
$zmSlangOpMatches            = 'совпадает';
$zmSlangOpNe                 = 'не равно';
$zmSlangOpNotIn              = 'не в списке';
$zmSlangOpNotMatches         = 'не совпадает';
$zmSlangOptionHelp           = 'OptionHelp';
$zmSlangOptionRestartWarning = 'Эти изменения подействуют только после перезапуска программы.';
$zmSlangOptions              = 'Опции';
$zmSlangOrder                = 'Order';
$zmSlangOrEnterNewName       = 'или введите новое имя';
$zmSlangOrientation          = 'Ориентация';
$zmSlangOut                  = 'Out';
$zmSlangOverwriteExisting    = 'Перезаписать существующее';
$zmSlangPaged                = 'По страницам';
$zmSlangPanLeft              = 'Pan Left';
$zmSlangPan                  = 'Pan';
$zmSlangPanRight             = 'Pan Right';
$zmSlangPanTilt              = 'Pan/Tilt';
$zmSlangParameter            = 'Парамер';
$zmSlangPassword             = 'Пароль';
$zmSlangPasswordsDifferent   = 'Пароли не совпадают';
$zmSlangPaths                = 'Пути';
$zmSlangPhoneBW              = 'Телефонная линия';
$zmSlangPhone                = 'Phone';
$zmSlangPixels               = 'в пикселях';
$zmSlangPlayAll              = 'Play All';
$zmSlangPleaseWait           = 'Пожалуйста подождите';
$zmSlangPoint                = 'Point';
$zmSlangPostEventImageBuffer = 'Буфер после события';
$zmSlangPreEventImageBuffer  = 'Буфер до события';
$zmSlangPreset               = 'Preset';
$zmSlangPresets              = 'Presets';
$zmSlangPrev                 = 'Пред.';
$zmSlangRate                 = 'Скорость';
$zmSlangReal                 = 'Реальная';
$zmSlangRecord               = 'Record';
$zmSlangRefImageBlendPct     = 'Прозрачность опорного кадра, %';
$zmSlangRefresh              = 'Обновить';
$zmSlangRemoteHostName       = 'Имя удаленного хоста';
$zmSlangRemoteHostPath       = 'Путь на удаленном хосте';
$zmSlangRemoteHostPort       = 'удаленный порт';
$zmSlangRemoteImageColours   = 'Цветность на удаленном хосте';
$zmSlangRemote               = 'Удаленный';
$zmSlangRename               = 'Переименовать';
$zmSlangReplay               = 'Проиграть';
$zmSlangResetEventCounts     = 'Обнулить счетчик событий';
$zmSlangReset                = 'Reset';
$zmSlangRestart              = 'Перезапустить';
$zmSlangRestarting           = 'Перезапускается';
$zmSlangRestrictedCameraIds  = 'Id запрещенных камер';
$zmSlangReturnDelay          = 'Return Delay';
$zmSlangReturnLocation       = 'Return Location';
$zmSlangRotateLeft           = 'Повернуть влево';
$zmSlangRotateRight          = 'Повернуть вправо';
$zmSlangRunMode              = 'Режим работы';
$zmSlangRunning              = 'Выполняется';
$zmSlangRunState             = 'Состояние';
$zmSlangSaveAs               = 'Сохранить как';
$zmSlangSaveFilter           = 'Сохранить фильтр';
$zmSlangSave                 = 'Сохранить';
$zmSlangScale                = 'Масштаб';
$zmSlangScore                = 'Оценка';
$zmSlangSecs                 = 'Сек.';
$zmSlangSectionlength        = 'Длина секции (в кадрах)';
$zmSlangSelect               = 'Select';
$zmSlangSelfIntersecting     = 'Polygon edges must not intersect';
$zmSlangSetLearnPrefs        = 'Set Learn Prefs'; // This can be ignored for now
$zmSlangSetNewBandwidth      = 'Установка новой ширина канала';
$zmSlangSetPreset            = 'Set Preset';
$zmSlangSet                  = 'Set';
$zmSlangSettings             = 'Настройки';
$zmSlangShowFilterWindow     = 'Показать окно фильтра';
$zmSlangShowTimeline         = 'Show Timeline';
$zmSlangSize                 = 'Size';
$zmSlangSleep                = 'Sleep';
$zmSlangSortAsc              = 'Asc';
$zmSlangSortBy               = 'Sort by';
$zmSlangSortDesc             = 'Desc';
$zmSlangSource               = 'Источник';
$zmSlangSourceType           = 'Тип источника';
$zmSlangSpeedHigh            = 'High Speed';
$zmSlangSpeedLow             = 'Low Speed';
$zmSlangSpeedMedium          = 'Medium Speed';
$zmSlangSpeed                = 'Speed';
$zmSlangSpeedTurbo           = 'Turbo Speed';
$zmSlangStart                = 'Запустить';
$zmSlangState                = 'Состояние';
$zmSlangStats                = 'Статистика';
$zmSlangStatus               = 'Статус';
$zmSlangStepLarge            = 'Large Step';
$zmSlangStepMedium           = 'Medium Step';
$zmSlangStepNone             = 'No Step';
$zmSlangStepSmall            = 'Small Step';
$zmSlangStep                 = 'Step';
$zmSlangStills               = 'Стоп-кадры';
$zmSlangStop                 = 'Остановить';
$zmSlangStopped              = 'Остановлен';
$zmSlangStream               = 'Поток';
$zmSlangSubmit               = 'Submit';
$zmSlangSystem               = 'Система';
$zmSlangTele                 = 'Tele';
$zmSlangThumbnail            = 'Thumbnail';
$zmSlangTilt                 = 'Tilt';
$zmSlangTimeDelta            = 'Относительное время';
$zmSlangTimeline             = 'Timeline';
$zmSlangTime                 = 'Время';
$zmSlangTimestamp            = 'Метка времени';
$zmSlangTimeStamp            = 'Метка времени';
$zmSlangTimestampLabelFormat = 'Формат метки';
$zmSlangTimestampLabelX      = 'X-координата метки';
$zmSlangTimestampLabelY      = 'Y-координата метки';
$zmSlangToday                = 'Today';
$zmSlangTools                = 'Инструменты';
$zmSlangTotalBrScore         = 'Сумм.<br/>оценка';
$zmSlangTrackDelay           = 'Track Delay';
$zmSlangTrackMotion          = 'Track Motion';
$zmSlangTriggers             = 'Триггеры';
$zmSlangTurboPanSpeed        = 'Turbo Pan Speed';
$zmSlangTurboTiltSpeed       = 'Turbo Tilt Speed';
$zmSlangType                 = 'Тип';
$zmSlangUnarchive            = 'Уд.&nbsp;из&nbsp;архива';
$zmSlangUnits                = 'Ед. измерения';
$zmSlangUnknown              = 'Unknown';
$zmSlangUpdateAvailable      = 'Доступно обновление ZoneMinder';
$zmSlangUpdateNotNecessary   = 'Обновление не требуется';
$zmSlangUpdate               = 'Update';
$zmSlangUseFilter            = 'Использовать фильтр';
$zmSlangUseFilterExprsPost   = '&nbsp;выражений&nbsp;для&nbsp;фильтра'; // This is used at the end of the phrase 'use N filter expressions'
$zmSlangUseFilterExprsPre    = 'Испол.&nbsp;'; // This is used at the beginning of the phrase 'use N filter expressions'
$zmSlangUser                 = 'Пользователь';
$zmSlangUsername             = 'Имя пользователя';
$zmSlangUsers                = 'Пользователи';
$zmSlangValue                = 'Значение';
$zmSlangVersion              = 'Версия';
$zmSlangVersionIgnore        = 'Игнорировать эту версию';
$zmSlangVersionRemindDay     = 'Напомнить через день';
$zmSlangVersionRemindHour    = 'Напомнить через час';
$zmSlangVersionRemindNever   = 'Не говорить о новых версиях';
$zmSlangVersionRemindWeek    = 'Напомнить через неделю';
$zmSlangVideo                = 'Видео';
$zmSlangVideoFormat          = 'Video Format';
$zmSlangVideoGenFailed       = 'Ошибка генерации видео!';
$zmSlangVideoGenFiles        = 'Existing Video Files';
$zmSlangVideoGenNoFiles      = 'No Video Files Found';
$zmSlangVideoGenParms        = 'Параметры генерации видео';
$zmSlangVideoGenSucceeded    = 'Video Generation Succeeded!';
$zmSlangVideoSize            = 'Размер изображения';
$zmSlangViewAll              = 'Просм. все';
$zmSlangView                 = 'Просмотр';
$zmSlangViewEvent            = 'View Event';
$zmSlangViewPaged            = 'Просм. постранично';
$zmSlangWake                 = 'Wake';
$zmSlangWarmupFrames         = 'Кадры разогрева';
$zmSlangWatch                = 'Watch';
$zmSlangWebColour            = 'Web Colour';
$zmSlangWeb                  = 'Интерфейс';
$zmSlangWeek                 = 'Неделя';
$zmSlangWhiteBalance         = 'White Balance';
$zmSlangWhite                = 'White';
$zmSlangWide                 = 'Wide';
$zmSlangX10ActivationString  = 'X10 Activation String';
$zmSlangX10InputAlarmString  = 'X10 Input Alarm String';
$zmSlangX10OutputAlarmString = 'X10 Output Alarm String';
$zmSlangX10                  = 'X10';
$zmSlangX                    = 'X';
$zmSlangYes                  = 'Да';
$zmSlangYouNoPerms           = 'У вас не достаточно прав для доступа к этому ресурсу.';
$zmSlangY                    = 'Y';
$zmSlangZoneAlarmColour      = 'Цвет тревоги (Red/Green/Blue)';
$zmSlangZoneAlarmThreshold   = 'Порог срабатывания (0-255)';
$zmSlangZoneArea             = 'Zone Area';
$zmSlangZoneFilterSize       = 'Filter Width/Height (pixels)';
$zmSlangZoneMinMaxAlarmArea  = 'Min/Max Alarmed Area';
$zmSlangZoneMinMaxBlobArea   = 'Min/Max Blob Area';
$zmSlangZoneMinMaxBlobs      = 'Min/Max Blobs';
$zmSlangZoneMinMaxFiltArea   = 'Min/Max Filtered Area';
$zmSlangZoneMinMaxPixelThres = 'Min/Max Pixel Threshold (0-255)';
$zmSlangZones                = 'Зоны';
$zmSlangZone                 = 'Зона';
$zmSlangZoomIn               = 'Zoom In';
$zmSlangZoomOut              = 'Zoom Out';
$zmSlangZoom                 = 'Zoom';


// Complex replacements with formatting and/or placements, must be passed through sprintf
$zmClangCurrentLogin         = 'Текущий пользователь: \'%1$s\'';
$zmClangEventCount           = '%1$s %2$s'; // For example '37 Events' (from Vlang below)
$zmClangLastEvents           = 'Последние %1$s %2$s'; // For example 'Last 37 Events' (from Vlang below)
$zmClangLatestRelease        = 'Последняя версия: v%1$s, у Вас установлена: v%2$s.';
$zmClangMonitorCount         = '%1$s %2$s'; // For example '4 Monitors' (from Vlang below)
$zmClangMonitorFunction      = 'Функция монитора %1$s';
$zmClangRunningRecentVer     = 'У вас установлена новейшая версия ZoneMinder, v%s.'; 

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
$zmVlangEvent                = array( 1=>'Событий', 2=>'Событие', 3=>'События' );
$zmVlangMonitor              = array( 1=>'Мониторов', 2=>'Монитор', 3=>'Монитора' );

// You will need to choose or write a function that can correlate the plurality string arrays
// with variable counts. This is used to conjugate the Vlang arrays above with a number passed
// in to generate the correct noun form.
//
// In languages such as English this is fairly simple
// Note this still has to be used with printf etc to get the right formating
/*function zmVlang( $lang_var_array, $count )
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
}*/

// This is an version that could be used in the Russian example above
// The rules are that the first word form is used if the count ends in
// 0, 5-9 or 11-19. The second form is used then the count ends in 1
// (not including 11 as above) and the third form is used when the
// count ends in 2-4, again excluding any values ending in 12-14.
//
function zmVlang( $lang_var_array, $count )
{
	$secondlastdigit = ($count/10)%10;
	$lastdigit = $count%10;

	// Get rid of the special cases first, the teens
	if ( $secondlastdigit == 1 && $lastdigit != 0 )
	{
		return( $lang_var_array[1] );
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
			return( $lang_var_array[1] );
			break;
		}
		case 1 :
	{
			return( $lang_var_array[2] );
			break;
		}
		case 2 :
		case 3 :
		case 4 :
		{
			return( $lang_var_array[3] );
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
// These overrides are in the form of $zmVarOlangPrompt<option> and $zmVarOlangHelp<option>
// where <option> represents the option name minus the initial ZM_
// So for example, to override the help text for ZM_LANG_DEFAULT do
// $zmVarOlangPromptLANG_DEFAULT = "This is a new prompt for this option";
// $zmVarOlangHelpLANG_DEFAULT = "This is some new help for this option which will be displayed in the popup window when the ? is clicked";
//

?>
