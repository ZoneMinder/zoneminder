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
$zmSlangActual               = 'Действительный';
$zmSlangAddNewMonitor        = 'Добавить монитор';
$zmSlangAddNewUser           = 'Добавить пользователя';
$zmSlangAddNewZone           = 'Добавить зону';
$zmSlangAlarmBrFrames        = 'Кадры<br/>тревоги';
$zmSlangAlarmFrame           = 'Кадр тревоги';
$zmSlangAlarmLimits          = 'Гран.&nbsp;зоны&nbsp;трев.';
$zmSlangAlarm                = 'Тревога';
$zmSlangAlarmPx              = 'Пкс&nbsp;трев.';
$zmSlangAlert                = 'Настороже';
$zmSlangAll                  = 'Все';
$zmSlangApply                = 'Применить';
$zmSlangApplyingStateChange  = 'Состояние сервиса изменяется';
$zmSlangArchArchived         = 'Только в архиве';
$zmSlangArchive              = 'Архив';
$zmSlangArchUnarchived       = 'Только не в архиве';
$zmSlangAttrAlarmFrames      = 'Кол-во кадров тревоги';
$zmSlangAttrArchiveStatus    = 'Статус архивации';
$zmSlangAttrAvgScore         = 'Сред. оценка';
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
$zmSlangAttrTime             = 'Время';
$zmSlangAttrTotalScore       = 'Сумм. оценка';
$zmSlangAttrWeekday          = 'День недели';
$zmSlangAutoArchiveEvents    = 'Automatically archive all matches';
$zmSlangAutoDeleteEvents     = 'Automatically delete all matches';
$zmSlangAutoEmailEvents      = 'Automatically email details of all matches';
$zmSlangAutoExecuteEvents    = 'Automatically execute command on all matches';
$zmSlangAutoMessageEvents    = 'Automatically message details of all matches';
$zmSlangAutoUploadEvents     = 'Automatically upload all matches';
$zmSlangAvgBrScore           = 'Сред.<br/>оценка';
$zmSlangBadMonitorChars      = 'Название Монитора может содержать только буквы, дефис и подчеркивание';
$zmSlangBandwidth            = 'канал';
$zmSlangBlobPx               = 'Пкс объекта';
$zmSlangBlobs                = 'Кол-во объектов';
$zmSlangBlobSizes            = 'Размер объектов';
$zmSlangBrightness           = 'Яркость';
$zmSlangBuffers              = 'Буферы';
$zmSlangCancelForcedAlarm    = 'Отменить&nbsp;форсированную&nbsp;тревогу';
$zmSlangCancel               = 'Отменить';
$zmSlangCaptureHeight        = 'Размер по Y';
$zmSlangCapturePalette       = 'Режим захвата';
$zmSlangCaptureWidth         = 'Размер по X';
$zmSlangCheckAll             = 'Пометить все';
$zmSlangCheckMethod          = 'Метод проверки тревоги';
$zmSlangChooseFilter         = 'Выбрать фильтр';
$zmSlangClose                = 'Закрыть';
$zmSlangColour               = 'Цвет';
$zmSlangConfig               = 'Config';
$zmSlangConfiguredFor        = 'Настроен на';
$zmSlangConfirmPassword      = 'Подтвердите пароль';
$zmSlangConjAnd              = 'и';
$zmSlangConjOr               = 'или';
$zmSlangConsole              = 'Сервер';
$zmSlangContactAdmin         = 'Пожалуйста обратитесь к вашему администратору.';
$zmSlangContrast             = 'Контраст';
$zmSlangCycleWatch           = 'Циклический просмотр';
$zmSlangDay                  = 'День';
$zmSlangDeleteAndNext        = 'Удалить &amp; след.';
$zmSlangDeleteAndPrev        = 'Удалить &amp; пред.';
$zmSlangDelete               = 'Удалить';
$zmSlangDeleteSavedFilter    = 'Удалить сохраненный фильтр';
$zmSlangDescription          = 'Описание';
$zmSlangDeviceChannel        = 'Канал';
$zmSlangDeviceFormat         = 'Формат (0=PAL,1=NTSC и т.д.)';
$zmSlangDeviceNumber         = 'Номер устройства (/dev/video?)';
$zmSlangDimensions           = 'Размеры';
$zmSlangDisk                 = 'Disk';
$zmSlangDuration             = 'Длительность';
$zmSlangEdit                 = 'Редактирование';
$zmSlangEmail                = 'Email';
$zmSlangEnabled              = 'разрешен';
$zmSlangEnterNewFilterName   = 'Введите новое название фильтра';
$zmSlangErrorBrackets        = 'Ошибка: количество открывающих и закрывающих скобок должно быть одинаковым';
$zmSlangError                = 'Ошибка';
$zmSlangErrorValidValue      = 'Ошибка: проверьте что все термы имеют действительное значение';
$zmSlangEtc                  = 'и т.д.';
$zmSlangEventFilter          = 'Фильтр событий';
$zmSlangEventId              = 'Event Id';
$zmSlangEvent                = 'Событие';
$zmSlangEvents               = 'События';
$zmSlangExclude              = 'Исключить';
$zmSlangFeed                 = 'Feed';
$zmSlangFilterPx             = 'Пкс фильтра';
$zmSlangFirst                = 'Первый';
$zmSlangForceAlarm           = 'Включить&nbsp;тревогу';
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
$zmSlangGenerateVideo        = 'Генерировать видео';
$zmSlangGeneratingVideo      = 'Генерируется видео';
$zmSlangGoToZoneMinder       = 'Перейти на ZoneMinder.com';
$zmSlangGrey                 = 'ч/б';
$zmSlangHighBW               = 'Широкий канал';
$zmSlangHigh                 = 'широкий';
$zmSlangHour                 = 'Час';
$zmSlangHue                  = 'Оттенок';
$zmSlangId                   = 'Id';
$zmSlangIdle                 = 'Idle';
$zmSlangIgnore               = 'Игнорировать';
$zmSlangImageBufferSize      = 'Размер буфера изображения';
$zmSlangImage                = 'Изображение';
$zmSlangInclude              = 'Включить';
$zmSlangInverted             = 'Инвертировать';
$zmSlangLanguage             = 'Язык';
$zmSlangLast                 = 'Последний';
$zmSlangLimitResultsPost     = 'results only;'; // This is used at the end of the phrase 'Limit to first N results only'
$zmSlangLimitResultsPre      = 'Limit to first'; // This is used at the beginning of the phrase 'Limit to first N results only'
$zmSlangLoad                 = 'Load';
$zmSlangLocal                = 'Локальный';
$zmSlangLoggedInAs           = 'Пользователь';
$zmSlangLoggingIn            = 'Вход в систему';
$zmSlangLogin                = 'Войти';
$zmSlangLogout               = 'Выйти';
$zmSlangLowBW                = 'Узкий канал';
$zmSlangLow                  = 'узкий';
$zmSlangMark                 = 'Метка';
$zmSlangMaxBrScore           = 'Макс.<br/>оценка';
$zmSlangMax                  = 'Макс.';
$zmSlangMaximumFPS           = 'Ограничение скорости записи (к/с)';
$zmSlangMediumBW             = 'Обычный канал';
$zmSlangMedium               = 'средний';
$zmSlangMinAlarmGeMinBlob    = 'Мин. количество пикселей тревоги должно быть больше или равно мин. количеству пикселей объекта';
$zmSlangMinAlarmGeMinFilter  = 'Мин. количество пикселей тревоги должно быть больше или равно мин. количеству пикселей после фильтрации';
$zmSlangMinAlarmPixelsLtMax  = 'Минимальное кол-во пикселей тревоги должно быть меньше максимального кол-ва пикселей тревоги';
$zmSlangMinBlobAreaLtMax     = 'Минимальная площадь объекта должна быть меньше чем максимальная площадь объекта';
$zmSlangMinBlobsLtMax        = 'Минимальное число объектов должно быть меньше чем максимальное число объектов';
$zmSlangMinFilterPixelsLtMax = 'Минимальное число пикселей после фильтрации должно быть меньше чем максимальное число пикселей фильтрации';
$zmSlangMinPixelThresLtMax   = 'Нижний порог кол-ва пикселей должен быть ниже верхнего порога кол-ва пикселей';
$zmSlangMisc                 = 'Разное';
$zmSlangMonitorIds           = 'Id&nbsp;Мониторов';
$zmSlangMonitor              = 'Монитор';
$zmSlangMonitors             = 'Мониторы';
$zmSlangMontage              = 'Montage';
$zmSlangMonth                = 'Месяц';
$zmSlangMustBeGe             = 'должно быть больше или равно';
$zmSlangMustBeLe             = 'должно быть меньше или равно';
$zmSlangMustConfirmPassword  = 'Вы должны подтвердить пароль';
$zmSlangMustSupplyPassword   = 'Вы должны ввести пароль';
$zmSlangMustSupplyUsername   = 'Вы должны ввести имя пользователя';
$zmSlangName                 = 'Имя';
$zmSlangNetwork              = 'Сеть';
$zmSlangNew                  = 'Нов.';
$zmSlangNewPassword          = 'Новый пароль';
$zmSlangNewState             = 'Новое состояние';
$zmSlangNewUser              = 'Новый пользователь';
$zmSlangNext                 = 'След.';
$zmSlangNoFramesRecorded     = 'Это событие не содежит кадров';
$zmSlangNo                   = 'Нет';
$zmSlangNoneAvailable        = 'не доступны';
$zmSlangNone                 = 'отсутствует';
$zmSlangNormal               = 'Нормальная';
$zmSlangNoSavedFilters       = 'нет сохраненных фильтров';
$zmSlangNoStatisticsRecorded = 'Статистика по этому событию/кадру не записана';
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
$zmSlangOrEnterNewName       = 'или введите новое имя';
$zmSlangOrientation          = 'Ориентация';
$zmSlangOverwriteExisting    = 'Перезаписать существующее';
$zmSlangPaged                = 'По страницам';
$zmSlangParameter            = 'Парамер';
$zmSlangPassword             = 'Пароль';
$zmSlangPasswordsDifferent   = 'Пароли не совпадают';
$zmSlangPaths                = 'Пути';
$zmSlangPhoneBW              = 'Телефонная линия';
$zmSlangPixels               = 'в пикселях';
$zmSlangPleaseWait           = 'Пожалуйста подождите';
$zmSlangPostEventImageBuffer = 'Буфер после события';
$zmSlangPreEventImageBuffer  = 'Буфер до события';
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
$zmSlangRestart              = 'Перезапустить';
$zmSlangRestarting           = 'Перезапускается';
$zmSlangRestrictedCameraIds  = 'Id запрещенных камер';
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
$zmSlangSetLearnPrefs        = 'Set Learn Prefs'; // This can be ignored for now
$zmSlangSetNewBandwidth      = 'Установка новой ширина канала';
$zmSlangSettings             = 'Настройки';
$zmSlangShowFilterWindow     = 'Показать окно фильтра';
$zmSlangSortAsc              = 'Asc';
$zmSlangSortBy               = 'Sort by';
$zmSlangSortDesc             = 'Desc';
$zmSlangSource               = 'Источник';
$zmSlangSourceType           = 'Тип источника';
$zmSlangStart                = 'Запустить';
$zmSlangState                = 'Состояние';
$zmSlangStats                = 'Статистика';
$zmSlangStatus               = 'Статус';
$zmSlangStills               = 'Стоп-кадры';
$zmSlangStop                 = 'Остановить';
$zmSlangStopped              = 'Остановлен';
$zmSlangStream               = 'Поток';
$zmSlangSystem               = 'Система';
$zmSlangTimeDelta            = 'Относительное время';
$zmSlangTime                 = 'Время';
$zmSlangTimestamp            = 'Метка времени';
$zmSlangTimeStamp            = 'Метка времени';
$zmSlangTimestampLabelFormat = 'Формат метки';
$zmSlangTimestampLabelX      = 'X-координата метки';
$zmSlangTimestampLabelY      = 'Y-координата метки';
$zmSlangTools                = 'Инструменты';
$zmSlangTotalBrScore         = 'Сумм.<br/>оценка';
$zmSlangTriggers             = 'Триггеры';
$zmSlangType                 = 'Тип';
$zmSlangUnarchive            = 'Уд.&nbsp;из&nbsp;архива';
$zmSlangUnits                = 'Ед. измерения';
$zmSlangUnknown              = 'Unknown';
$zmSlangUpdateAvailable      = 'Доступно обновление ZoneMinder';
$zmSlangUpdateNotNecessary   = 'Обновление не требуется';
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
$zmSlangVideoGenFailed       = 'Ошибка генерации видео!';
$zmSlangVideoGenParms        = 'Параметры генерации видео';
$zmSlangVideoSize            = 'Размер изображения';
$zmSlangViewAll              = 'Просм. все';
$zmSlangView                 = 'Просмотр';
$zmSlangViewPaged            = 'Просм. постранично';
$zmSlangWarmupFrames         = 'Кадры разогрева';
$zmSlangWatch                = 'Watch';
$zmSlangWeb                  = 'Интерфейс';
$zmSlangWeek                 = 'Неделя';
$zmSlangX10ActivationString  = 'X10 Activation String';
$zmSlangX10InputAlarmString  = 'X10 Input Alarm String';
$zmSlangX10OutputAlarmString = 'X10 Output Alarm String';
$zmSlangX10                  = 'X10';
$zmSlangYes                  = 'Да';
$zmSlangYouNoPerms           = 'У вас не достаточно прав для доступа к этому ресурсу.';
$zmSlangZoneAlarmColour      = 'Цвет тревоги (RGB)';
$zmSlangZoneAlarmThreshold   = 'Порог срабатывания (0>=?<=255)';
$zmSlangZoneFilterHeight     = 'Высота фильтра (в пкс.)';
$zmSlangZoneFilterWidth      = 'Ширина фильтра (в пкс.)';
$zmSlangZoneMaxAlarmedArea   = 'Макс. площадь тревоги';
$zmSlangZoneMaxBlobArea      = 'Макс. площадь объекта';
$zmSlangZoneMaxBlobs         = 'Макс. кол-во объектов';
$zmSlangZoneMaxFilteredArea  = 'Макc. площадь после фильтрации';
$zmSlangZoneMaxPixelThres    = 'Верхний порог кол-ва пикселей (0>=?<=255)';
$zmSlangZoneMaxX             = 'Макс. X координата (правый край)';
$zmSlangZoneMaxY             = 'Mакс. Y координата (нижний край)';
$zmSlangZoneMinAlarmedArea   = 'Мин. площадь тревоги';
$zmSlangZoneMinBlobArea      = 'Мин. площадь объекта';
$zmSlangZoneMinBlobs         = 'Мин. кол-во объектов';
$zmSlangZoneMinFilteredArea  = 'Мин. площадь после фильтрации';
$zmSlangZoneMinPixelThres    = 'Нижний порог кол-ва пикселей (0>=?<=255)';
$zmSlangZoneMinX             = 'Мин. X координата (левый край)';
$zmSlangZoneMinY             = 'Mин. Y координата (верхний край)';
$zmSlangZones                = 'Зоны';
$zmSlangZone                 = 'Зона';

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
