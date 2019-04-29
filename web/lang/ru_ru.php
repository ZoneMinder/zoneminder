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

// ZoneMinder Russian Translation by Borodin A.S.
// ZoneMinder Russian Translation updated by IDDQDesnik, 2017
// ZoneMinder Russian Translation updated by santos995, 2019

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
header( "Content-Type: text/html; charset=utf-8" );

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
    'Action'               => 'Действие', 
    'Actual'               => 'Актуальный',                 // Edited - 2019-03-25
    'AddNewControl'        => 'Добавить новый',
    'AddNewMonitor'        => 'Добавить монитор',
    'AddNewServer'         => 'Добавить новый сервер',         // Edited - 2019-03-25
    'AddNewStorage'        => 'Добавить новое хранилище',        // Edited - 2019-03-25
    'AddNewUser'           => 'Добавить пользователя',
    'AddNewZone'           => 'Добавить зону',
    'Alarm'                => 'Тревога',
    'AlarmBrFrames'        => 'Кадры<br/>тревоги',
    'AlarmFrame'           => 'Кадр тревоги',
    'AlarmFrameCount'      => 'Число кадров тревоги', 
    'AlarmLimits'          => 'Гран.&nbsp;зоны&nbsp;трев.',
    'AlarmMaximumFPS'      => 'Макс. к/с при тревоге', 
    'AlarmPx'              => 'Пкс&nbsp;трев.',
    'AlarmRGBUnset'        => 'You must set an alarm RGB colour',
    'AlarmRefImageBlendPct'=> 'Смешение опорного кадра тревоги, %', // Added - 2015-04-18
    'Alert'                => 'Настороже',
    'All'                  => 'Все',
    'AnalysisFPS'          => 'Частота анализа (к/с)',           // Added - 2015-07-22
    'AnalysisUpdateDelay'  => 'Задержка обновления анализа',  // Added - 2015-07-23
    'Apply'                => 'Применить',
    'ApplyingStateChange'  => 'Состояние сервиса изменяется',
    'ArchArchived'         => 'Только в архиве',
    'ArchUnarchived'       => 'Только не в архиве',
    'Archive'              => 'Архив',
    'Archived'             => 'Архив',
    'Area'                 => 'Зона',
    'AreaUnits'            => 'Размер (пикс./%)',
    'AttrAlarmFrames'      => 'Кол-во кадров тревоги',
    'AttrArchiveStatus'    => 'Статус архивации',
    'AttrAvgScore'         => 'Сред. оценка',
    'AttrCause'            => 'Причина',
    'AttrDiskBlocks'       => 'Диск, блоки',
    'AttrDiskPercent'      => 'Диск, проценты',
    'AttrDiskSpace'        => 'Дисковое пространство',             // Edited - 2019-03-24
    'AttrDuration'         => 'Длительность',
    'AttrEndDate'          => 'Дата окончания',               // Edited - 2019-03-24
    'AttrEndDateTime'      => 'Дата/время окончания',          // Edited - 2019-03-24
    'AttrEndTime'          => 'Время окончания',               // Edited - 2019-03-24
    'AttrEndWeekday'       => 'End Weekday',            // Added - 2018-08-30
    'AttrFilterServer'     => 'Фильтр для серверов запущен', // Edited - 2019-03-24
    'AttrFrames'           => 'Кол-во кадров',
    'AttrId'               => 'ИД',
    'AttrMaxScore'         => 'Макс. оценка',
    'AttrMonitorId'        => 'ИД Монитора',
    'AttrMonitorName'      => 'Название Монитора',
    'AttrMonitorServer'    => 'Монитор серверов запущен', // Edited - 2019-03-24
    'AttrName'             => 'Имя',
    'AttrNotes'            => 'Примечание',
    'AttrStartDate'        => 'Дата начала',             // Edited - 2019-03-24
    'AttrStartDateTime'    => 'Дата/Время начала',        // Edited - 2019-03-24
    'AttrStartTime'        => 'Время начала',             // Edited - 2019-03-24
    'AttrStartWeekday'     => 'Start Weekday',          // Added - 2018-08-30
    'AttrStateId'          => 'Run State',              // Added - 2018-08-30
    'AttrStorageArea'      => 'Storage Area',           // Added - 2018-08-30
    'AttrStorageServer'    => 'Server Hosting Storage', // Added - 2018-08-30
    'AttrSystemLoad'       => 'Нагрузка проц.',
    'AttrTotalScore'       => 'Сумм. оценка',
    'Auto'                 => 'Auto',
    'AutoStopTimeout'      => 'Тайм-аут автоостановки',
    'Available'            => 'Доступно',              // Added - 2009-03-31
    'AvgBrScore'           => 'Сред.<br/>оценка',
    'Background'           => 'Фоновый',
    'BackgroundFilter'     => 'Выполнить фильтр в фоновом режиме',
    'BadAlarmFrameCount'   => 'Число кадров тревоги должно быть целочисенным и больше нуля',
    'BadAlarmMaxFPS'       => 'Макс. к/с при тревоге должно быть положительным',
    'BadAnalysisFPS'       => 'Частота анализа должна быть положительной', // Added - 2015-07-22
    'BadAnalysisUpdateDelay'=> 'Задержка обновления анализа должна быть целочисленной и большей либо равной нулю', // Added - 2015-07-23
    'BadChannel'           => 'Канал должен быть целочисленным и большим либо равным нулю',
    'BadColours'           => 'Неправильное цветовое пространство', // Added - 2011-06-15
    'BadDevice'            => 'Неправильный путь к устройству',
    'BadFPSReportInterval' => 'Период обновления индикации скорости должен быть целочисленным и большим либо равным нулю',
    'BadFormat'            => 'Неправильный формат',
    'BadFrameSkip'         => 'Количество пропускаемых кадров должно быть целочисленным и большим либо равным нулю',
    'BadHeight'            => 'Неправильная высота',
    'BadHost'              => 'Неправильный IP или имя хоста; указывается без http://',
    'BadImageBufferCount'  => 'Image buffer size must be an integer of 10 or more',
    'BadLabelX'            => 'X координата ярлыка должна быть целочисленной и большей либо равной нулю',
    'BadLabelY'            => 'Y координата ярлыка должна быть целочисленной и большей либо равной нулю',
    'BadMaxFPS'            => 'Ограничение скорости записи должно быть положительным',
    'BadMotionFrameSkip'   => 'Количество пропускаемых кадров движения должно быть целочисленным и большим либо равным нулю',
    'BadNameChars'         => 'Имя может содержать только латинские буквы, цифры и символы пробела, плюса, минуса и подчеркивания',
    'BadPalette'           => 'Неправильная палитра', // Added - 2009-03-31
    'BadPath'              => 'Неправильный путь',
    'BadPort'              => 'Неправильный порт',
    'BadPostEventCount'    => 'Буфер после события должен быть целочисленным и большим либо равным нулю',
    'BadPreEventCount'     => 'Буфер до события должен быть целочисленным и большим либо равным нулю и меньше буфера изображений',
    'BadRefBlendPerc'      => 'Смешение опорного кадра должно быть положительным и целочисленным',
    'BadSectionLength'     => 'Длина секции должна быть целочисленной и большей либо равной тридцати',
    'BadSignalCheckColour' => 'Цвет проверки сигнала должен быть правильной строкой формата RGB',
    'BadSourceType'        => 'Source Type \"Web Site\" requires the Function to be set to \"Monitor\"', // Added - 2018-08-30
    'BadStreamReplayBuffer'=> 'Буфер потока повторного воспроизведения должен быть целочисленным и большим либо равным нулю',
    'BadWarmupCount'       => 'Кол-во кадров разогрева должно быть целочисленным и большим либо равным нулю',
    'BadWebColour'         => 'Цвет отметки должен быть правильным Web-цветом',
    'BadWebSitePath'       => 'Пожалуйста, введите полной название сайта url, включая http:// или https:// префикс.', // Edited - 2019-03-24
    'BadWidth'             => 'Неправильная ширина',
    'Bandwidth'            => 'канал',
    'BandwidthHead'        => 'канал', // This is the end of the bandwidth status on the top of the console, different in many language due to phrasing;
    'BlobPx'               => 'Пкс объекта',
    'BlobSizes'            => 'Размер объектов',
    'Blobs'                => 'Кол-во объектов',
    'Brightness'           => 'Яркость',
    'Buffer'               => 'Буфер',                 // Edited - 2019-03-24
    'Buffers'              => 'Буферы',
    'CSSDescription'       => 'Изменить стандартный CSS для данного компьютера', // Edited - 2019-03-24
    'CanAutoFocus'         => 'Автофокус',
    'CanAutoGain'          => 'Автоусиление',
    'CanAutoIris'          => 'Автодиафрагма',
    'CanAutoWhite'         => 'Автобаланс белого',
    'CanAutoZoom'          => 'Автоприближение',
    'CanFocus'             => 'Фокусировка',
    'CanFocusAbs'          => 'Абсолютный фокус',
    'CanFocusCon'          => 'Непрерывный фокус',
    'CanFocusRel'          => 'Относительный фокус',
    'CanGain'              => 'Усиление',
    'CanGainAbs'           => 'Абсолютное усиление',
    'CanGainCon'           => 'Непрерывное усиление',
    'CanGainRel'           => 'Относительное усиление',
    'CanIris'              => 'Диафрагма',
    'CanIrisAbs'           => 'Абсолютная диафрагма',
    'CanIrisCon'           => 'Непрерывная диафрагма',
    'CanIrisRel'           => 'Относительная диафрагма',
    'CanMove'              => 'Перемещение',
    'CanMoveAbs'           => 'Абсолютное перемещение',
    'CanMoveCon'           => 'Непрерывное перемещение',
    'CanMoveDiag'          => 'Диагональное перемещение',
    'CanMoveMap'           => 'Перемещение по точкам',
    'CanMoveRel'           => 'Относительное перемещение',
    'CanPan'               => 'Панорама' ,
    'CanReset'             => 'Сброс',
    'CanReboot'            => 'Перезагрузка',  // Added - 2019-03-24
    'CanSetPresets'        => 'Создание предустановок',
    'CanSleep'             => 'Сон',
    'CanTilt'              => 'Наклон',
    'CanWake'              => 'Пробуждение',
    'CanWhite'             => 'Баланс белого',
    'CanWhiteAbs'          => 'Абсолютный баланс белого',
    'CanWhiteBal'          => 'Баланс белого',
    'CanWhiteCon'          => 'Непрерывный баланс белого',
    'CanWhiteRel'          => 'Относительный баланс белого',
    'CanZoom'              => 'Увеличение',
    'CanZoomAbs'           => 'Абсолютное увеличение',
    'CanZoomCon'           => 'Непрерывное увеличение',
    'CanZoomRel'           => 'Относительное увеличение',
    'Cancel'               => 'Отменить',
    'CancelForcedAlarm'    => 'Отменить форсированную тревогу',
    'CaptureHeight'        => 'Размер по Y',
    'CaptureMethod'        => 'Метод захвата',         // Added - 2009-02-08
    'CapturePalette'       => 'Режим захвата',
    'CaptureResolution'    => 'Разрешение',     // Edited - 2019-03-24
    'CaptureWidth'         => 'Размер по X',
    'Cause'                => 'Причина',
    'CheckMethod'          => 'Метод проверки тревоги',
    'ChooseDetectedCamera' => 'Выберите камеру', // Added - 2009-03-31
    'ChooseFilter'         => 'Выбрать фильтр',
    'ChooseLogFormat'      => 'Выбрать формат лога',    // Edited - 2019-03-25
    'ChooseLogSelection'   => 'Choose a log selection', // Added - 2011-06-17
    'ChoosePreset'         => 'Выберите предустановку',
    'Clear'                => 'Очистить',                  // Added - 2011-06-16
    'CloneMonitor'         => 'Клонировать',                  // Edited - 2019-03-25
    'Close'                => 'Закрыть',
    'Colour'               => 'Цвет',
    'Command'              => 'Command',
    'Component'            => 'Компонент',              // Added - 2011-06-16
    'ConcurrentFilter'     => 'Run filter concurrently', // Added - 2018-08-30
    'Config'               => 'Config',
    'ConfiguredFor'        => 'настроен на',
    'ConfirmDeleteEvents'  => 'Вы действительно хотите удалить выбранные события?',
    'ConfirmPassword'      => 'Подтвердите пароль',
    'ConjAnd'              => 'и',
    'ConjOr'               => 'или',
    'Console'              => 'Сервер',
    'ContactAdmin'         => 'Пожалуйста обратитесь к вашему администратору.',
    'Continue'             => 'Продолжить', // Added - 2019-03-25
    'Contrast'             => 'Контраст',
    'Control'              => 'Управление',
    'ControlAddress'       => 'Адрес устройства',
    'ControlCap'           => 'Тип управления',
    'ControlCaps'          => 'Типы управления',
    'ControlDevice'        => 'Управляемое устройство',
    'ControlType'          => 'Тип управления',
    'Controllable'         => 'Управляемая',
    'Current'              => 'Текущий',                // Added - 2015-04-18
    'Cycle'                => 'Циклически',
    'CycleWatch'           => 'Циклический просмотр',
    'DateTime'             => 'Дата/Время',              // Added - 2011-06-16
    'Day'                  => 'День',
    'Debug'                => 'Отладка', // Added - 2019-03-25
    'DefaultRate'          => 'Скорость по умолчанию',
    'DefaultScale'         => 'Масштаб по умолчанию',
    'DefaultView'          => 'Вид по умолчанию',
    'Deinterlacing'        => 'Устранение чересстрочности',          // Added - 2015-04-18
    'Delay'                => 'Задержка',                  // Edited - 2019-03-25
    'Delete'               => 'Удалить',
    'DeleteAndNext'        => 'Удалить &amp; след.',
    'DeleteAndPrev'        => 'Удалить &amp; пред.',
    'DeleteSavedFilter'    => 'Удалить сохраненный фильтр',
    'Description'          => 'Описание',
    'DetectedCameras'      => 'Найденные камеры',       // Added - 2009-03-31
    'DetectedProfiles'     => 'Найденные профили',      // Added - 2015-04-18
    'Device'               => 'Устройство',                 // Edited - 2019-03-25
    'DeviceChannel'        => 'Канал',
    'DeviceFormat'         => 'Формат',
    'DeviceNumber'         => 'Номер устройства',
    'DevicePath'           => 'Путь к устройству',
    'Devices'              => 'Устройства', // Edited - 2019-03-25
    'Dimensions'           => 'Размеры',
    'DisableAlarms'        => 'Запретить тревогу',
    'Disk'                 => 'Диск', 
    'Display'              => 'Display',                // Added - 2011-01-30
    'Displaying'           => 'Отображено',             // Added - 2011-06-16
    'DoNativeMotionDetection'=> 'Использовать встроенное обнаружение движения', // Edited - 2019-03-25
    'Donate'               => 'Поддержите проект', 
    'DonateAlready'        => 'Нет, я уже сделал пожертвование', 
    'DonateEnticement'     => 'Вы какое-то время используете ZoneMinder и, надеемся, находите его полезным дополнением к вашей домашней или рабочей безопасности. Хотя ZoneMinder есть и будет оставаться свободным и бесплатным, он требует денег на разработку и поддержку. Если Вы хотите поддержать его будущее развитие и новые функции, пожалуйста сделайте пожертвование. Это, конечно, необязательно, но очень высоко ценится. Вы можете пожертвовать любую сумму.<br><br>Если Вы хотите сделать пожертвование, то выберите соответствующий вариант ниже или перейдите по адресу https://www.bountysource.com/teams/zoneminder в вашем браузере.<br><br>Спасибо за использование ZoneMinder, и не забывайте посетить форум на ZoneMinder.com для поддержки и пожеланий как сделать ZoneMinder еще лучше.', 
    'DonateRemindDay'      => 'Нет, не сейчас, напомнить через день', 
    'DonateRemindHour'     => 'Нет, не сейчас, напомнить через час', 
    'DonateRemindMonth'    => 'Нет, не сейчас, напомнить через месяц', 
    'DonateRemindNever'    => 'Нет, и не напоминать, я не хочу жертвовать', 
    'DonateRemindWeek'     => 'Нет, не сейчас, напомнить через неделю', 
    'DonateYes'            => 'Да, я хотел бы сделать пожертвование', 
    'Download'             => 'Скачать',
    'DownloadVideo'        => 'Скачать видео',         // Added - 2019-03-24
    'DuplicateMonitorName' => 'Скопировать имя монитора', // Added - 2019-03-25
    'Duration'             => 'Длительность',
    'Edit'                 => 'Редактирование',
    'EditLayout'           => 'Редактирование шаблона',            // Added - 2019-03-25
    'Email'                => 'Email',
    'EnableAlarms'         => 'Разрешить тревогу',
    'Enabled'              => 'Включен',
    'EnterNewFilterName'   => 'Введите новое название фильтра',
    'Error'                => 'Ошибка',
    'ErrorBrackets'        => 'Ошибка: количество открывающих и закрывающих скобок должно быть одинаковым',
    'ErrorValidValue'      => 'Ошибка: проверьте что все термы имеют действительное значение',
    'Etc'                  => 'и т.д.',
    'Event'                => 'Событие',
    'EventFilter'          => 'Фильтр событий',
    'EventId'              => 'Id события', // Added - 2019-03-25
    'EventName'            => 'Имя события', // Added - 2019-03-25
    'EventPrefix'          => 'Префикс события',
    'Events'               => 'События',
    'Exclude'              => 'Исключить',
    'Execute'              => 'Выполнить',
    'Exif'                 => 'Включить EXIF информацию в изображение', // Added - 2019-03-24
    'Export'               => 'Экспорт',
    'ExportDetails'        => 'Экспортировать описание события',
    'ExportFailed'         => 'Ошибка экспорта',
    'ExportFormat'         => 'Формат экспорта',
    'ExportFormatTar'      => 'Tar',
    'ExportFormatZip'      => 'Zip',
    'ExportFrames'         => 'Экспортировать описание кадров',
    'ExportImageFiles'     => 'Экспортировать изображения',
    'ExportLog'            => 'Сохранить лог',             // Added - 2011-06-17
    'ExportMiscFiles'      => 'Экспортировать прочие файлы (если есть)',
    'ExportOptions'        => 'Настройки экспорта',
    'ExportSucceeded'      => 'Экспорт успешен',       // Added - 2009-02-08
    'ExportVideoFiles'     => 'Экспортировать видео файлы (если есть)',
    'Exporting'            => 'Экспортирую',
    'FPS'                  => 'к/c',
    'FPSReportInterval'    => 'Период обновления индикации скорости',
    'FTP'                  => 'FTP',
    'Far'                  => 'Far',
    'FastForward'          => 'Перемотать',
    'Feed'                 => 'Feed',
    'Ffmpeg'               => 'Ffmpeg',                 // Added - 2009-02-08
    'File'                 => 'Файл',
    'Filter'               => 'Фильтр',                 // Added - 2015-04-18
    'FilterArchiveEvents'  => 'Архивировать выбранное',
    'FilterDeleteEvents'   => 'Удалить выбранное',
    'FilterEmailEvents'    => 'Отправить выбранное по Email',
    'FilterExecuteEvents'  => 'Выполнить команду над выбранным',
    'FilterLog'            => 'Фильтр лога',             // Added - 2015-04-18
    'FilterMessageEvents'  => 'Message details of all matches',
    'FilterMoveEvents'     => 'Move all matches',       // Added - 2018-08-30
    'FilterPx'             => 'Пкс фильтра',
    'FilterUnset'          => 'Вы должны указать ширину и высоту фильтра', // Added - 2019-03-25
    'FilterUpdateDiskSpace'=> 'Обновить используемое дисковое пространство', // Edited - 2019-03-25
    'FilterUploadEvents'   => 'Загрузить все совпадения', // Added - 2019-03-25
    'FilterVideoEvents'    => 'Создать видео для всех совпадений', // Added - 2019-03-25
    'Filters'              => 'Фильтры',
    'First'                => 'Первый',
    'FlippedHori'          => 'Перевернутый горизонтально', // Added - 2019-03-25
    'FlippedVert'          => 'Перевернутый вертикально', // Added - 2019-03-25
    'FnMocord'             => 'Mocord',            // Added 2013.08.16.
    'FnModect'             => 'Modect',            // Added 2013.08.16.
    'FnMonitor'            => 'Monitor',            // Added 2013.08.16.
    'FnNodect'             => 'Nodect',            // Added 2013.08.16.
    'FnNone'               => 'None',            // Added 2013.08.16.
    'FnRecord'             => 'Record',            // Added 2013.08.16.
    'Focus'                => 'Фокус',
    'ForceAlarm'           => 'Поднять тревогу',
    'Format'               => 'Формат',
    'Frame'                => 'Кадр',
    'FrameId'              => 'ИД кадра',
    'FrameRate'            => 'Частота кадров',
    'FrameSkip'            => 'Кол-во пропуск. кадров',
    'Frames'               => 'кадры',
    'Func'                 => 'Функ.',
    'Function'             => 'Функция',
    'Gain'                 => 'Gain',
    'General'              => 'Основные',
    'GenerateDownload'     => 'Сгенерировать загрузку',      // Edited - 2019-03-25
    'GenerateVideo'        => 'Генерировать видео',
    'GeneratingVideo'      => 'Генерируется видео',
    'GoToZoneMinder'       => 'Перейти на ZoneMinder.com',
    'Grey'                 => 'ч/б',
    'Group'                => 'Группа',
    'Groups'               => 'Группы',
    'HasFocusSpeed'        => 'Скорость фокуса',
    'HasGainSpeed'         => 'Скорость усиления',
    'HasHomePreset'        => 'Домашняя предустановка',
    'HasIrisSpeed'         => 'Скорость диафрагмы',
    'HasPanSpeed'          => 'Скорость панорамир.',
    'HasPresets'           => 'Предустановки',
    'HasTiltSpeed'         => 'Скорость наклона',
    'HasTurboPan'          => 'Укоренное панорамир.',
    'HasTurboTilt'         => 'Укоренный наклон',
    'HasWhiteSpeed'        => 'Скорость баланса белого',
    'HasZoomSpeed'         => 'Скорость увеличения',
    'High'                 => 'широкий',
    'HighBW'               => 'Широкий канал',
    'Home'                 => 'Домой',
    'Hostname'             => 'Имя хоста',               // Edited - 2019-03-25
    'Hour'                 => 'Час',
    'Hue'                  => 'Оттенок',
    'Id'                   => 'ИД',
    'Idle'                 => 'Покой',
    'Ignore'               => 'Игнорировать',
    'Image'                => 'Изображение',
    'ImageBufferSize'      => 'Буфер изображений',
    'Images'               => 'Изображения', // Added - 2019-03-25
    'In'                   => 'In',
    'Include'              => 'Включить',
    'Inverted'             => 'Инвертировать',
    'Iris'                 => 'Наклон',
    'KeyString'            => 'Key String',
    'Label'                => 'Имя', // Added - 2019-03-25
    'Language'             => 'Язык',
    'Last'                 => 'Последний',
    'Layout'               => 'Раскладка',                 // Added - 2009-02-08
    'Level'                => 'Уровень',                  // Added - 2011-06-16
    'Libvlc'               => 'Libvlc',
    'LimitResultsPost'     => 'результатами;', // This is used at the end of the phrase 'Limit to first N results only';
    'LimitResultsPre'      => 'Ограничить первыми', // This is used at the beginning of the phrase 'Limit to first N results only';
    'Line'                 => 'Строка',                   // Added - 2011-06-16
    'LinkedMonitors'       => 'Привязанные мониторы',
    'List'                 => 'Список',
    'ListMatches'          => 'Список совпадений',           // Edited - 2019-03-25
    'Load'                 => 'Нагрузка', 
    'Local'                => 'Локальный',
    'Log'                  => 'Лог',                    // Added - 2011-06-16;
    'LoggedInAs'           => 'Пользователь',
    'Logging'              => 'Логгирование',                // Added - 2019-03-24
    'LoggingIn'            => 'Вход в систему',
    'Login'                => 'Войти',
    'Logout'               => 'Выйти',
    'Logs'                 => 'Логи',                   // Added - 2019-03-24
    'Low'                  => 'узкий',
    'LowBW'                => 'Узкий канал',
    'Main'                 => 'Основные',
    'Man'                  => 'Man',
    'Manual'               => 'Manual',
    'Mark'                 => 'Метка',
    'Max'                  => 'Макс.',
    'MaxBandwidth'         => 'Макс. пропускная способность', // Added - 2019-03-25
    'MaxBrScore'           => 'Макс.<br/>оценка',
    'MaxFocusRange'        => 'Макс. диап. фокуса',
    'MaxFocusSpeed'        => 'Макс. скор. фокуса',
    'MaxFocusStep'         => 'Макс. шаг фокуса',
    'MaxGainRange'         => 'Макс. диап. усиления',
    'MaxGainSpeed'         => 'Макс. скор. усиления',
    'MaxGainStep'          => 'Макс. шаг усиления',
    'MaxIrisRange'         => 'Макс. диап. диафрагмы',
    'MaxIrisSpeed'         => 'Макс. скор. диафрагмы',
    'MaxIrisStep'          => 'Макс. шаг диафрагмы',
    'MaxPanRange'          => 'Макс. диап. панорамы',
    'MaxPanSpeed'          => 'Макс. скор. панорамы',
    'MaxPanStep'           => 'Макс. шаг панорамы',
    'MaxTiltRange'         => 'Макс. диап. наклона',
    'MaxTiltSpeed'         => 'Макс. скор. наклона',
    'MaxTiltStep'          => 'Макс. шаг наклона',
    'MaxWhiteRange'        => 'Макс. диап. баланса белого',
    'MaxWhiteSpeed'        => 'Макс. скор. баланса белого',
    'MaxWhiteStep'         => 'Макс. шаг. баланса белого',
    'MaxZoomRange'         => 'Макс. диап. увеличения',
    'MaxZoomSpeed'         => 'Макс. скор. увеличения',
    'MaxZoomStep'          => 'Макс. шаг увеличения',
    'MaximumFPS'           => 'Ограничение скорости записи (к/с)',
    'Medium'               => 'средний',
    'MediumBW'             => 'Обычный канал',
    'Message'              => 'Сообщение',                // Added - 2011-06-16
    'MinAlarmAreaLtMax'    => 'Минимум зоны тревоги должен быть меньше максимума',
    'MinAlarmAreaUnset'    => 'Укажите минимальное число пикселей зоны тревоги',
    'MinBlobAreaLtMax'     => 'Минимальная площадь объекта должна быть меньше чем максимальная площадь объекта',
    'MinBlobAreaUnset'     => 'Укажите минимальное число пикселей объекта',
    'MinBlobLtMinFilter'   => 'Минимум пикселей объекта должен быть меньше или равен минимуму фильтр. зоны',
    'MinBlobsLtMax'        => 'Минимальное число объектов должно быть меньше чем максимальное число объектов',
    'MinBlobsUnset'        => 'Укажите минимальное число объектов',
    'MinFilterAreaLtMax'   => 'Минимум фильтр. зоны должен быть меньше максимума',
    'MinFilterAreaUnset'   => 'Укажите минимальное число пикселей фильтр. зоны',
    'MinFilterLtMinAlarm'  => 'Минимум фильтр. зоны должен быть меньше или равен минимуму зоны тревоги',
    'MinFocusRange'        => 'Мин. диап. фокуса',
    'MinFocusSpeed'        => 'Мин. скор фокуса',
    'MinFocusStep'         => 'Мин. шаг фокуса',
    'MinGainRange'         => 'Мин. диап. усиления',
    'MinGainSpeed'         => 'Мин. скор. усиления',
    'MinGainStep'          => 'Мин. шаг усиления',
    'MinIrisRange'         => 'Мин. диап. диафрагмы',
    'MinIrisSpeed'         => 'Мин. скор. диафрагмы',
    'MinIrisStep'          => 'Мин. шаг диафрагмы',
    'MinPanRange'          => 'Мин. диап. панорамы',
    'MinPanSpeed'          => 'Мин. скор. панорамы',
    'MinPanStep'           => 'Мин. шаг панорамы',
    'MinPixelThresLtMax'   => 'Нижний порог изменения пикселя должен быть ниже верхнего порога изменения пикселя',
    'MinPixelThresUnset'   => 'Укажите минимальный порог изменения пикселя',
    'MinTiltRange'         => 'Мин. диап. наклона',
    'MinTiltSpeed'         => 'Мин. скор. наклона',
    'MinTiltStep'          => 'Мин. шаг наклона',
    'MinWhiteRange'        => 'Мин. диап. баланса белого',
    'MinWhiteSpeed'        => 'Мин. скор. баланса белого',
    'MinWhiteStep'         => 'Мин. шаг баланса белого',
    'MinZoomRange'         => 'Мин. диап. увеличения',
    'MinZoomSpeed'         => 'Мин. скор. увеличения',
    'MinZoomStep'          => 'Мин. шаг увеличения',
    'Misc'                 => 'Разное',
    'Mode'                 => 'Режим',                   // Added - 2015-04-18
    'Monitor'              => 'Монитор',
    'MonitorIds'           => 'ИД&nbsp;Мониторов',
    'MonitorPreset'        => 'Предустановки монитора',//The list below shows detected analog and network cameras and whether they are already being used or available for selection.
    'MonitorPresetIntro'   => 'Выберите подходящий вариант из списка ниже.<br><br>Обратите внимание, что это может переписать настройки определенные для этого монитора.<br><br>',
    'MonitorProbe'         => 'Поиск камеры',          // Added - 2009-03-31
    'MonitorProbeIntro'    => 'В этом списке показаны найденные аналоговые и сетевые камеры, как уже заведенные, так и доступные для выбора.<br/><br/>Выберите нужную из списка ниже.<br/><br/>Обратите внимание, что не все камеры могут быть найдены, и что выбор камеры может переписать настройки определенные для этого монитора.<br/><br/>', // Added - 2009-03-31
    'Monitors'             => 'Мониторы',
    'Montage'              => 'Монтаж', 
    'MontageReview'        => 'Обзор монтажа',         // Added - 2019-03-24
    'Month'                => 'Месяц',
    'More'                 => 'Еще',                   // Added - 2011-06-16
    'MotionFrameSkip'      => 'Кол-во пропуск. кадров движения',
    'Move'                 => 'Перемещение',
    'Mtg2widgrd'           => '2 в ряд',              // Added 2013.08.15.
    'Mtg3widgrd'           => '3 в ряд',              // Added 2013.08.15.
    'Mtg3widgrx'           => '3 в ряд, увеличиваются при тревоге',              // Added 2013.08.15.
    'Mtg4widgrd'           => '4 в ряд',              // Added 2013.08.15.
    'MtgDefault'           => 'По умолчанию',              // Added 2013.08.15.
    'MustBeGe'             => 'должно быть больше или равно',
    'MustBeLe'             => 'должно быть меньше или равно',
    'MustConfirmPassword'  => 'Вы должны подтвердить пароль',
    'MustSupplyPassword'   => 'Вы должны ввести пароль',
    'MustSupplyUsername'   => 'Вы должны ввести имя пользователя',
    'Name'                 => 'Имя',
    'Near'                 => 'Near',
    'Network'              => 'Сеть',
    'New'                  => 'Нов.',
    'NewGroup'             => 'Новая группа',
    'NewLabel'             => 'Новое имя', // Added - 2019-03-25
    'NewPassword'          => 'Новый пароль',
    'NewState'             => 'Новое состояние',
    'NewUser'              => 'Новый пользователь',
    'Next'                 => 'След.',
    'No'                   => 'Нет',
    'NoDetectedCameras'    => 'Нет камер',    // Added - 2019-03-24
    'NoDetectedProfiles'   => 'Нет профилей',   // Added - 2019-03-24
    'NoFramesRecorded'     => 'Это событие не содержит кадров',
    'NoGroup'              => 'Нет группы',   // Added - 2019-03-24
    'NoSavedFilters'       => 'нет сохраненных фильтров',
    'NoStatisticsRecorded' => 'Статистика по этому событию/кадру не записана',
    'None'                 => 'отсутствует',
    'NoneAvailable'        => 'не доступны',
    'Normal'               => 'Нормальная',
    'Notes'                => 'Примечание',
    'NumPresets'           => 'Кол-во предустановок',
    'Off'                  => 'Выкл.', // Added - 2019-03-25
    'On'                   => 'Вкл.', // Added - 2019-03-25
    'OnvifCredentialsIntro'=> 'Пожалуйста укажите имя пользователя и пароль для выбранной камеры.<br/><br/>Если пользователь для камеры не был создан, тогда будет создан новый с указанными данными.<br/><br/>', // Added - 2015-04-18
    'OnvifProbe'           => 'ONVIF',                  // Added - 2015-04-18
    'OnvifProbeIntro'      => 'В этом списке показаны найденные ONVIF камеры, как уже заведенные, так и доступные для выбора.<br/><br/>Выберите нужную из списка ниже.<br/><br/>Обратите внимание, что не все камеры могут быть найдены, и что выбор камеры может переписать настройки определенные для этого монитора.<br/><br/>', // Added - 2015-04-18
    'OpEq'                 => 'равно',
    'OpGt'                 => 'больше',
    'OpGtEq'               => 'больше либо равно',
    'OpIn'                 => 'в списке',
    'OpIs'                 => 'is',                     // Added - 2018-08-30
    'OpIsNot'              => 'is not',                 // Added - 2018-08-30
    'OpLt'                 => 'меньше',
    'OpLtEq'               => 'меньше или равно',
    'OpMatches'            => 'совпадает',
    'OpNe'                 => 'не равно',
    'OpNotIn'              => 'не в списке',
    'OpNotMatches'         => 'не совпадает',
    'Open'                 => 'Открыть',
    'OptionHelp'           => 'Справка',
    'OptionRestartWarning' => 'Эти изменения подействуют только после перезапуска программы.',
    'OptionalEncoderParam' => 'Необязательные параметры кодировщика', // Added - 2019-03-24
    'Options'              => 'Опции',
    'OrEnterNewName'       => 'или введите новое имя',
    'Order'                => 'Сортировка',
    'Orientation'          => 'Ориентация',
    'Out'                  => 'Out',
    'OverwriteExisting'    => 'Перезаписать существующее',
    'Paged'                => 'По страницам',
    'Pan'                  => 'Панорама',
    'PanLeft'              => 'Панорама влево',
    'PanRight'             => 'Панорама вправо',
    'PanTilt'              => 'Панорама/Наклон',
    'Parameter'            => 'Параметр',
    'Password'             => 'Пароль',
    'PasswordsDifferent'   => 'Пароли не совпадают',
    'Paths'                => 'Пути',
    'Pause'                => 'Пауза',
    'Phone'                => 'Phone',
    'PhoneBW'              => 'Телефонная линия',
    'Pid'                  => 'PID',                    // Added - 2011-06-16
    'PixelDiff'            => 'Pixel Diff',
    'Pixels'               => 'в пикселях',
    'Play'                 => 'Играть',
    'PlayAll'              => 'Воспр. все',
    'PleaseWait'           => 'Пожалуйста подождите',
    'Plugins'              => 'Плагины',   // Edited - 2019-03-24
    'Point'                => 'Точка',
    'PostEventImageBuffer' => 'Буфер после события',
    'PreEventImageBuffer'  => 'Буфер до события',
    'PreserveAspect'       => 'Сохранять соотношение сторон',
    'Preset'               => 'Предустановка',
    'Presets'              => 'Предустановки',
    'Prev'                 => 'Пред.',
    'Probe'                => 'Поиск',                  // Added - 2009-03-31
    'ProfileProbe'         => 'Поиск потока',           // Added - 2015-04-18
    'ProfileProbeIntro'    => 'В этом списке показаны существующие профили потока выбранной камеры.<br/><br/>Выберите нужный из списка ниже.<br/><br/>Обратите внимание, что ZoneMinder не может добавить дополнительный профиль, и что выбор профиля может переписать настройки определенные для этого монитора.<br/><br/>', // Added - 2015-04-18
    'Progress'             => 'Прогресс',               // Added - 2015-04-18
    'Protocol'             => 'Протокол',
    'RTSPDescribe'         => 'Использовать RTSP URL для ответа', // Edited - 2019-03-25
    'RTSPTransport'        => 'Транспортный протокол RTSP', // Edited - 2019-03-25
    'Rate'                 => 'Скорость',
    'Real'                 => 'Реальная',
    'RecaptchaWarning'     => 'Your reCaptcha secret key is invalid. Please correct it, or reCaptcha will not work', // Added - 2018-08-30
    'Record'               => 'Record',
    'RecordAudio'          => 'Сохранять ли аудиопоток при сохранении события.', // Edited - 2019-03-25
    'RefImageBlendPct'     => 'Смешение опорного кадра, %',
    'Refresh'              => 'Обновить',
    'Remote'               => 'Удаленный',
    'RemoteHostName'       => 'Имя удаленного хоста',
    'RemoteHostPath'       => 'Путь на удаленном хосте',
    'RemoteHostPort'       => 'удаленный порт',
    'RemoteHostSubPath'    => 'Remote Host SubPath',    // Added - 2009-02-08
    'RemoteImageColours'   => 'Цветность на удаленном хосте',
    'RemoteMethod'         => 'Метод доступа',          // Added - 2009-02-08
    'RemoteProtocol'       => 'Remote Protocol',        // Added - 2009-02-08
    'Rename'               => 'Переименовать',
    'Replay'               => 'Повтор',
    'ReplayAll'            => 'Все события',
    'ReplayGapless'        => 'События подряд',
    'ReplaySingle'         => 'Одно событие',
    'ReportEventAudit'     => 'Отчёт о событиях аудита',    // Edited - 2019-03-24
    'Reset'                => 'Сбросить',
    'ResetEventCounts'     => 'Обнулить счетчик событий',
    'Restart'              => 'Перезапустить',
    'Restarting'           => 'Перезапускается',
    'RestrictedCameraIds'  => 'Id запрещенных камер',
    'RestrictedMonitors'   => 'Restricted Monitors',
    'ReturnDelay'          => 'Задержка возврата',
    'ReturnLocation'       => 'Положение возврата',
    'Rewind'               => 'Назад',
    'RotateLeft'           => 'Повернуть влево',
    'RotateRight'          => 'Повернуть вправо',
    'RunLocalUpdate'       => 'Запустите zmupdate.pl для обновления', // Edited - 2019-03-24
    'RunMode'              => 'Режим работы',
    'RunState'             => 'Состояние',
    'Running'              => 'Выполняется',
    'Save'                 => 'Сохранить',
    'SaveAs'               => 'Сохранить как',
    'SaveFilter'           => 'Сохранить фильтр',
    'SaveJPEGs'            => 'Сохранить JPEG-и',             // Edited - 2019-03-24
    'Scale'                => 'Масштаб',
    'Score'                => 'Оценка',
    'Secs'                 => 'Сек.',
    'Sectionlength'        => 'Длина секции (в кадрах)',
    'Select'               => 'Выбор',
    'SelectFormat'         => 'Выберите формат',          // Added - 2011-06-17
    'SelectLog'            => 'Выберите лог',             // Added - 2011-06-17
    'SelectMonitors'       => 'Выбрать Мониторы',   //  Edited - 2019-03-24
    'SelfIntersecting'     => 'Polygon edges must not intersect',
    'Set'                  => 'Установка',  // Edited - 2019-03-24
    'SetNewBandwidth'      => 'Установка новой ширина канала',
    'SetPreset'            => 'Установка пресета',  // Edited - 2019-03-24
    'Settings'             => 'Настройки',
    'ShowFilterWindow'     => 'Показать окно фильтра',
    'ShowTimeline'         => 'Показать график',
    'SignalCheckColour'    => 'Цвет проверки сигнала',
    'SignalCheckPoints'    => 'Signal Check Points',    // Added - 2018-08-30
    'Size'                 => 'Размер',  // Edited - 2019-03-24
    'SkinDescription'      => 'Смена стандартного скина для данного компьютера', // Edited - 2019-03-24
    'Sleep'                => 'Sleep',
    'SortAsc'              => 'По возр.',
    'SortBy'               => 'Сортировать',
    'SortDesc'             => 'По убыв.',
    'Source'               => 'Источник',
    'SourceColours'        => 'Цвета источника',         // Edited - 2019-03-24
    'SourcePath'           => 'Путь к источнику',            // Added - 2009-02-08
    'SourceType'           => 'Тип источника',
    'Speed'                => 'Скорость',  //Edited - 2019-03-24
    'SpeedHigh'            => 'Высокая скорость',  //Edited - 2019-03-24
    'SpeedLow'             => 'Низкая скорость',  //Edited - 2019-03-24
    'SpeedMedium'          => 'Средняя скорость',
    'SpeedTurbo'           => 'Максимальная скорость', // Edited - 2019-03-24
    'Start'                => 'Запустить',
    'State'                => 'Состояние',
    'Stats'                => 'Статистика',
    'Status'               => 'Статус',
    'StatusConnected'      => 'Записывается',              // Edited - 2019-03-25
    'StatusNotRunning'     => 'Не запущен',            // Edited - 2019-03-25
    'StatusRunning'        => 'Не записывается',          // Edited - 2019-03-25
    'StatusUnknown'        => 'Неизвестно',                // Edited - 2019-03-25
    'Step'                 => 'Шаг',
    'StepBack'             => 'Кадр назад',
    'StepForward'          => 'Кадр вперед',
    'StepLarge'            => 'Большой шаг', // Added - 2019-03-25
    'StepMedium'           => 'Средний шаг', // Added - 2019-03-25
    'StepNone'             => 'Без шагов', // Added - 2019-03-25
    'StepSmall'            => 'Малый шаг', // Added - 2019-03-25
    'Stills'               => 'Стоп-кадры',
    'Stop'                 => 'Остановить',
    'Stopped'              => 'Остановлен',
    'StorageArea'          => 'Storage Area',           // Added - 2018-08-30
    'StorageScheme'        => 'Scheme',                 // Added - 2018-08-30
    'Stream'               => 'Поток',
    'StreamReplayBuffer'   => 'Буфер потока повторного воспр.',
    'Submit'               => 'Применить',
    'System'               => 'Система',
    'SystemLog'            => 'Лог системы',             // Added - 2011-06-16
    'TargetColorspace'     => 'Цветовое пространство',      // Added - 2015-04-18
    'Tele'                 => 'Tele',
    'Thumbnail'            => 'Thumbnail',
    'Tilt'                 => 'Наклон',
    'Time'                 => 'Время',
    'TimeDelta'            => 'Относительное время',
    'TimeStamp'            => 'Метка времени',
    'Timeline'             => 'График',
    'TimelineTip1'         => 'Наведите мышку на график чтобы увидеть снимок и описание события.',              // Added 2013.08.15.
    'TimelineTip2'         => 'Нажмите на окрашенный участок графика или на снимок чтобы просмотреть событие.',              // Added 2013.08.15.
    'TimelineTip3'         => 'Нажмите на фоновый графика чтобы приблизит его.',              // Added 2013.08.15.
    'TimelineTip4'         => 'Используйте кнопки снизу для отдаления и перемещения по временной шкале.',              // Added 2013.08.15.
    'Timestamp'            => 'Метка времени',
    'TimestampLabelFormat' => 'Формат метки',
    'TimestampLabelSize'   => 'Размер метки',
    'TimestampLabelX'      => 'X-координата метки',
    'TimestampLabelY'      => 'Y-координата метки',
    'Today'                => 'Сегодня',
    'Tools'                => 'Инструменты',
    'Total'                => 'Всего',                  // Added - 2011-06-16 
    'TotalBrScore'         => 'Сумм.<br/>оценка',
    'TrackDelay'           => 'Задержка обнаружения',
    'TrackMotion'          => 'Отслеживать движение',
    'Triggers'             => 'Триггеры',
    'TurboPanSpeed'        => 'Скорость ускор. панорам.',
    'TurboTiltSpeed'       => 'Скорость ускор. наклона',
    'Type'                 => 'Тип',
    'Unarchive'            => 'Уд.&nbsp;из&nbsp;архива',
    'Undefined'            => 'Не определено',              // Added - 2009-02-08
    'Units'                => 'Ед. измерения',
    'Unknown'              => 'Неизвестно',
    'Update'               => 'Обновить',
    'UpdateAvailable'      => 'Доступно обновление ZoneMinder',
    'UpdateNotNecessary'   => 'Обновление не требуется',
    'Updated'              => 'Обновлено',                // Added - 2011-06-16
    'Upload'               => 'Загрузить',                 // Added - 2011-08-23
    'UseFilter'            => 'Использовать фильтр',
    'UseFilterExprsPost'   => '&nbsp;выражений&nbsp;для&nbsp;фильтра', // This is used at the end of the phrase 'use N filter expressions'
    'UseFilterExprsPre'    => 'Испол.&nbsp;', // This is used at the beginning of the phrase 'use N filter expressions'
    'UsedPlugins'          => 'Использующиеся плагины',
    'User'                 => 'Пользователь',
    'Username'             => 'Имя пользователя',
    'Users'                => 'Пользователи',
    'V4L'                  => 'V4L',                    // Added - 2015-04-18
    'V4LCapturesPerFrame'  => 'Captures Per Frame',     // Added - 2015-04-18
    'V4LMultiBuffer'       => 'Multi Buffering',        // Added - 2015-04-18
    'Value'                => 'Значение',
    'Version'              => 'Версия',
    'VersionIgnore'        => 'Игнорировать эту версию',
    'VersionRemindDay'     => 'Напомнить через день',
    'VersionRemindHour'    => 'Напомнить через час',
    'VersionRemindNever'   => 'Не говорить о новых версиях',
    'VersionRemindWeek'    => 'Напомнить через неделю',
    'Video'                => 'Видео',
    'VideoFormat'          => 'Формат видео', // Edited - 2019-03-24
    'VideoGenFailed'       => 'Ошибка генерации видео!',
    'VideoGenFiles'        => 'Existing Video Files',
    'VideoGenNoFiles'      => 'Видео не найдено',  // Edited - 2019-03-24
    'VideoGenParms'        => 'Параметры генерации видео',
    'VideoGenSucceeded'    => 'Видео сгенерировано!', // Edited - 2019-03-24
    'VideoSize'            => 'Размер изображения',
    'VideoWriter'          => 'Video Writer',           // Added - 2018-08-30
    'View'                 => 'Просмотр',
    'ViewAll'              => 'Просм. все',
    'ViewEvent'            => 'Просм. событие',  // Edited - 2019-03-24
    'ViewPaged'            => 'Просм. постранично',
    'Wake'                 => 'Wake',
    'WarmupFrames'         => 'Кадры разогрева',
    'Watch'                => 'Watch',
    'Web'                  => 'Интерфейс',
    'WebColour'            => 'Цвет отметки',
    'WebSiteUrl'           => 'URL сайта',            // Edited - 2019-03-25
    'Week'                 => 'Неделя',
    'White'                => 'Бал. белого',
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
    'ZoneAlarmColour'      => 'Цвет тревоги (Кр./Зел./Синий)',
    'ZoneArea'             => 'Размер зоны',
    'ZoneExtendAlarmFrames' => 'Кол-во кадров продления тревоги',
    'ZoneFilterSize'       => 'Ширина/Высота фильтра (пикс.)',
    'ZoneMinMaxAlarmArea'  => 'Мин/Макс разм. зоны тревоги',
    'ZoneMinMaxBlobArea'   => 'Мин/Макс разм. объекта',
    'ZoneMinMaxBlobs'      => 'Мин/Макс кол-во объектов',
    'ZoneMinMaxFiltArea'   => 'Мин/Макс разм. фильтр. зоны ',
    'ZoneMinMaxPixelThres' => 'Мин/Макс порог изм. пикс. (0-255)',
    'ZoneMinderLog'        => 'Лог ZoneMinder',         // Edited - 2019-03-25
    'ZoneOverloadFrames'   => 'Кол-во игнор. кадров перегрузки',
    'Zones'                => 'Зоны',
    'Zoom'                 => 'Увеличение',
    'ZoomIn'               => 'Приблизить',
    'ZoomOut'              => 'Отдалить',
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
    'VersionMismatch'      => 'Несоответствие версий, версия системы - %1$s, версия БД - %2$s.', // Added - 2011-05-25
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
// Note this still has to be used with printf etc to get the right formatting
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
		'Help' => "Параметры заданные в этом поле передаются в FFmpeg. Множественные параметры разделяются запятой.~~ ".
		          "Примеры (вводятся без кавычек)~~~~".
		          "\"allowed_media_types=video\" Задает тип данных, запрашиваемый от камеры (аудио, видео, данные)~~~~".
		          "\"reorder_queue_size=nnn\" Задает количество пакетов в очереди обработки переупорядочивания пакетов~~~~".
		          "\"loglevel=debug\" Задает уровень вывода сообщений от FFmpeg (quiet, panic, fatal, error, warning, info, verbose, debug)"
	),
	'OPTIONS_LIBVLC' => array(
		'Help' => "Параметры заданные в этом поле передаются в libVLC. Множественные параметры разделяются запятой.~~ ".
		          "Примеры (вводятся без кавычек)~~~~".
		          "\"--rtp-client-port=nnn\" Задает локальный порт используемый для RTP данных~~~~". 
		          "\"--verbose=2\" Задает уровень вывода сообщений от libVLC"
	),
	'SKIN_DEFAULT' => array(
        'Prompt' => "Тема оформления по умолчанию",
		'Help' => "ZoneMinder позволяет использовать много разных веб-интерфейсов. Эта настройка позволяет вам выбрать тему оформления используемую сайтом по умолчанию. Пользователи могут изменить свою подзнее."
	),
	'CSS_DEFAULT' => array(
        'Prompt' => "Набор стилей оформления CSS по умолчанию",
		'Help' => "ZoneMinder позволяет использовать много разных веб-интерфейсов, некоторые из них позволяют использовать разные наборы стилей оформления. Эта настройка позволяет вам выбрать набор стилей для темы оформления используемый сайтом по умолчанию. Пользователи могут изменить свой подзнее."
	),

	
//    'LANG_DEFAULT' => array(
//        'Prompt' => "This is a new prompt for this option",
//        'Help' => "This is some new help for this option which will be displayed in the popup window when the ? is clicked"
//    ),
);

?>
