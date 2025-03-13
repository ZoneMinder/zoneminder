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
    '24BitColour'          => '24-битный цвет',
    '32BitColour'          => '32-битный цвет',          // Added - 2011-06-15
    '8BitGrey'             => '8-битный серый цвет',
    'Action'               => 'Действие',
    'Actual'               => 'Реальный',
    'AddNewControl'        => 'Добавить профиль управления PTZ',
    'AddNewMonitor'        => 'Добавить монитор',
    'AddNewServer'         => 'Добавить сервер',         // Added - 2018-08-30
    'AddNewStorage'        => 'Добавить хранилище',        // Added - 2018-08-30
    'AddNewUser'           => 'Добавить пользователя',
    'AddNewZone'           => 'Добавить зону',
    'Alarm'                => 'Тревога',
    'AlarmBrFrames'        => 'Кадры<br/>тревоги',
    'AlarmFrame'           => 'Кадр тревоги',
    'AlarmFrameCount'      => 'Число кадров тревоги',
    'AlarmLimits'          => 'Границы зоны тревоги',
    'AlarmMaximumFPS'      => 'Макс. к/с при тревоге',
    'AlarmPx'              => 'Тревожных пикселей',
    'AlarmRGBUnset'        => 'Вы должны установить цвет тревоги (RGB)',
    'AlarmRefImageBlendPct'=> 'Смешение опорного кадра тревоги, %', // Added - 2015-04-18
    'Alert'                => 'Бдительность',
    'All'                  => 'Все',
    'AnalysisFPS'          => 'Частота анализа (к/с)',           // Added - 2015-07-22
    'AnalysisUpdateDelay'  => 'Задержка обновления анализа',  // Added - 2015-07-23
    'Apply'                => 'Применить',
    'ApplyingStateChange'  => 'Изменение состояния сервиса',
    'ArchArchived'         => 'Только в архиве',
    'ArchUnarchived'       => 'Только не в архиве',
    'Archive'              => 'Архив',
    'Archived'             => 'Архивировано',
    'Area'                 => 'Зона',
    'AreaUnits'            => 'Размер (пикс./%)',
    'AttrAlarmFrames'      => 'Кол-во кадров тревоги',
    'AttrArchiveStatus'    => 'Статус архивации',
    'AttrAvgScore'         => 'Сред. оценка',
    'AttrCause'            => 'Причина',
    'AttrDiskBlocks'       => 'Дисковые блоки',
    'AttrDiskPercent'      => 'Дисковый процент',
    'AttrDiskSpace'        => 'Дисковое пространство',             // Added - 2018-08-30
    'AttrDuration'         => 'Продолжительность',
    'AttrEndDate'          => 'Дата окончания',               // Added - 2018-08-30
    'AttrEndDateTime'      => 'Дата/время окончания',          // Edited - 2019-03-24
    'AttrEndTime'          => 'Время окончания',               // Edited - 2019-03-24
    'AttrEndWeekday'       => 'Конец недели',            // Added - 2018-08-30
    'AttrFilterServer'     => 'Фильтр для серверов запущен', // Edited - 2019-03-24
    'AttrFrames'           => 'Кол-во кадров',
    'AttrId'               => 'ИД',
    'AttrMaxScore'         => 'Макс. оценка',
    'AttrMonitorId'        => 'ИД Монитора',
    'AttrMonitorName'      => 'Название монитора',
    'AttrMonitorServer'    => 'Монитор серверов запущен', // Edited - 2019-03-24
    'AttrName'             => 'Имя',
    'AttrNotes'            => 'Примечание',
    'AttrStartDate'        => 'Дата начала',             // Edited - 2019-03-24
    'AttrStartDateTime'    => 'Дата/время начала',        // Edited - 2019-03-24
    'AttrStartTime'        => 'Время начала',             // Edited - 2019-03-24
    'AttrStartWeekday'     => 'Начало недели',          // Added - 2018-08-30
    'AttrStateId'          => 'Текущий статус',              // Added - 2018-08-30
    'AttrStorageArea'      => 'Хранилище',           // Added - 2018-08-30
    'AttrStorageServer'    => 'Сервер для размещения хранилища', // Added - 2018-08-30
    'AttrSystemLoad'       => 'Нагрузка проц.',
    'AttrTotalScore'       => 'Сумм. оценка',
    'Auto'                 => 'Авто',
    'AutoStopTimeout'      => 'Тайм-аут автоостановки',
    'Available'            => 'Доступно',              // Added - 2009-03-31
    'AvgBrScore'           => 'Сред.<br/>оценка',
    'Background'           => 'Фоновый',
    'BackgroundFilter'     => 'Выполнить фильтр в фоновом режиме',
    'BadAlarmFrameCount'   => 'Число кадров тревоги должно быть целочисенным и больше нуля',
    'BadAlarmMaxFPS'       => 'Макс. к/с при тревоге должно быть положительным',
    'BadAnalysisFPS'       => 'Частота анализа должна быть положительной', // Added - 2015-07-22
    'BadAnalysisUpdateDelay'=> 'Задержка обновления анализа должна быть целочисленной, не менее 0', // Added - 2015-07-23
    'BadChannel'           => 'Канал должен быть целочисленным, не менее 0',
    'BadColours'           => 'Неправильное цветовое пространство', // Added - 2011-06-15
    'BadDevice'            => 'Неправильный путь к устройству',
    'BadFPSReportInterval' => 'Период обновления индикации скорости должен быть целочисленным, не менее 0',
    'BadFormat'            => 'Неправильный формат',
    'BadFrameSkip'         => 'Количество пропускаемых кадров должно быть целочисленным, не менее 0',
    'BadHeight'            => 'Неправильная высота',
    'BadHost'              => 'Неправильный IP или имя хоста. Указывается без http://',
    'BadImageBufferCount'  => 'Размер буфера изображения должен быть целым числом (2 или более)',
    'BadLabelX'            => 'X координата ярлыка должна быть целочисленной, не менее 0',
    'BadLabelY'            => 'Y координата ярлыка должна быть целочисленной, не менее 0',
    'BadMaxFPS'            => 'Ограничение скорости записи должно быть положительным',
    'BadMotionFrameSkip'   => 'Количество пропускаемых кадров движения должно быть целочисленным, не менее 0',
    'BadNameChars'         => 'Имя может содержать только латинские буквы, цифры и символы пробела, плюса, минуса и подчеркивания',
    'BadPalette'           => 'Неправильная палитра', // Added - 2009-03-31
    'BadPath'              => 'Неправильный путь',
    'BadPort'              => 'Неправильный порт',
    'BadPostEventCount'    => 'Буфер после события должен быть целочисленным, не менее 0',
    'BadPreEventCount'     => 'Буфер до события должен быть целочисленным, не менее 0, но меньше буфера изображений',
    'BadPreEventCountMaxImageBufferCount' => 'Максимальный буфер изображений должен быть больше чем буфер до события, иначе может не работать',
    'BadRefBlendPerc'      => 'Смешение опорного кадра должно быть положительным и целочисленным',
    'BadSectionLength'     => 'Длина секции должна быть целочисленной и большей либо равной тридцати',
    'BadSignalCheckColour' => 'Цвет проверки сигнала должен быть правильной строкой формата RGB',
    'BadSourceType'        => 'Тип источника \"Веб-сайт\" требует, чтобы функция была установлена в \"Монитор\"', // Added - 2018-08-30
    'BadStreamReplayBuffer'=> 'Буфер потока повторного воспроизведения должен быть целочисленным, не менее 0',
    'BadWarmupCount'       => 'Кол-во кадров разогрева должно быть целочисленным, не менее 0',
    'BadWebColour'         => 'Цвет отметки должен быть правильным Web-цветом',
    'BadWebSitePath'       => 'Пожалуйста, введите полной название сайта url, включая http:// или https:// префикс.', // Edited - 2019-03-24
    'BadWidth'             => 'Неправильная ширина',
    'Bandwidth'            => 'канал',
    'BandwidthHead'        => 'канал', // This is the end of the bandwidth status on the top of the console, different in many language due to phrasing;
    'BlobPx'               => 'Пикселей объекта',
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
    'ChooseLogSelection'   => 'Выбор логирования', // Added - 2011-06-17
    'ChoosePreset'         => 'Выберите предустановку',
    'Clear'                => 'Очистить',                  // Added - 2011-06-16
    'CloneMonitor'         => 'Клонировать',                  // Edited - 2019-03-25
    'Close'                => 'Закрыть',
    'Colour'               => 'Цвет',
    'Command'              => 'Команда',
    'Component'            => 'Компонент',              // Added - 2011-06-16
    'ConcurrentFilter'     => 'Запуск фильтра', // Added - 2018-08-30
    'Config'               => 'Конфигурация',
    'ConfiguredFor'        => 'настроен на',
    'ConfirmDeleteEvents'  => 'Вы действительно хотите удалить выбранные события?',
    'ConfirmDeleteLayout'  => 'Вы действительно хотите удалить текущий шаблон?',
    'ConfirmPassword'      => 'Подтвердите пароль',
    'ConfirmUnarchiveEvents'=> 'Вы уверены, что хотите удалить из архива выбранные события?',
    'ConjAnd'              => 'и',
    'ConjOr'               => 'или',
    'Console'              => 'Сервер',
    'ContactAdmin'         => 'Пожалуйста, обратитесь к вашему администратору.',
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
    'Cycle'                => 'Цикл',
    'CycleWatch'           => 'Просмотр цикла',
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
    'Display'              => 'Дисплей',                // Added - 2011-01-30
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
    'events'               => 'событий', // Added - 2024-07-16
    'EventsLoading'        => 'Идет загрузка событий',
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
    'ExportVideoFiles'     => 'Экспортировать видеофайлы (если есть)',
    'Exporting'            => 'Экспортирую',
    'FPS'                  => 'к/c',
    'FPSReportInterval'    => 'Период обновления индикации скорости',
    'FTP'                  => 'FTP',
    'Far'                  => 'Удаленный',
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
    'FilterMessageEvents'  => 'Подробная информация о совпадениях',
    'FilterMoveEvents'     => 'Переместить все совпадения',       // Added - 2018-08-30
    'FilterPx'             => 'Фильтр пикселей',
    'FilterUnset'          => 'Вы должны указать ширину и высоту фильтра', // Added - 2019-03-25
    'FilterUpdateDiskSpace'=> 'Обновить используемое дисковое пространство', // Edited - 2019-03-25
    'FilterUploadEvents'   => 'Загрузить все совпадения', // Added - 2019-03-25
    'FilterVideoEvents'    => 'Создать видео для всех совпадений', // Added - 2019-03-25
    'Filters'              => 'Фильтры',
    'First'                => 'Первый',
    'FlippedHori'          => 'Перевернуть горизонтально', // Added - 2019-03-25
    'FlippedVert'          => 'Перевернуть вертикально', // Added - 2019-03-25
    'FnMocord'             => 'Запись монитора',            // Added 2013.08.16.
    'FnModect'             => 'Запись обнаруженного движения',            // Added 2013.08.16.
    'FnMonitor'            => 'Монитор',            // Added 2013.08.16.
    'FnNodect'             => 'Отсутствие обнаружения',            // Added 2013.08.16.
    'FnNone'               => 'Нет',            // Added 2013.08.16.
    'FnRecord'             => 'Запись',            // Added 2013.08.16.
    'Focus'                => 'Фокус',
    'ForceAlarm'           => 'Поднять тревогу',
    'Format'               => 'Формат',
    'Frame'                => 'Кадр',
    'FrameId'              => 'ID кадра',
    'FrameRate'            => 'Частота кадров',
    'FrameSkip'            => 'Кол-во пропуск. кадров',
    'Frames'               => 'Кадры',
    'Func'                 => 'Функ.',
    'Function'             => 'Функция',
    'Gain'                 => 'Усиление',
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
    'In'                   => 'В',
    'Include'              => 'Включить',
    'Inverted'             => 'Инвертировать',
    'Iris'                 => 'Наклон',
    'KeyString'            => 'Ключевая строка',
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
    'Man'                  => 'Человек',
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
    'Monitor status position' => 'Положение статуса монитора',
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
    'Near'                 => 'Рядом',
    'Network'              => 'Сеть',
    'Network Scan'         => 'Сканировать сеть',
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
    'NoneAvailable'        => 'недоступны',
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
    'OpIs'                 => 'является',                     // Added - 2018-08-30
    'OpIsNot'              => 'не является',                 // Added - 2018-08-30
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
    'Out'                  => 'Из',
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
    'PixelDiff'            => 'Разница в пикселях',
    'Pixels'               => 'в пикселях',
    'Play'                 => 'Играть',
    'PlayAll'              => 'Воспр. все',
    'PleaseWait'           => 'Пожалуйста, подождите',
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
    'Ratio'                => 'Соотношение',
    'Real'                 => 'Реальная',
    'RecaptchaWarning'     => 'Ваш секретный ключ reCAPTCHA недействителен. Пожалуйста, исправьте это, или reCAPTCHA не будет работать', // Added - 2018-08-30
    'Record'               => 'Запись',
    'RecordAudio'          => 'Сохранять ли аудиопоток при сохранении события.', // Edited - 2019-03-25
    'RefImageBlendPct'     => 'Смешение опорного кадра, %',
    'Refresh'              => 'Обновить',
    'Remote'               => 'Удаленный',
    'RemoteHostName'       => 'Имя удаленного хоста',
    'RemoteHostPath'       => 'Путь на удаленном хосте',
    'RemoteHostPort'       => 'удаленный порт',
    'RemoteHostSubPath'    => 'Подпуть удаленного хоста',    // Added - 2009-02-08
    'RemoteImageColours'   => 'Цветность на удаленном хосте',
    'RemoteMethod'         => 'Метод доступа',          // Added - 2009-02-08
    'RemoteProtocol'       => 'Удаленный протокол',        // Added - 2009-02-08
    'Rename'               => 'Переименовать',
    'Replay'               => 'Повтор',
    'ReplayAll'            => 'Все события',
    'ReplayGapless'        => 'События подряд',
    'ReplaySingle'         => 'Одно событие',
    'ReportEventAudit'     => 'Отчёт о событиях аудита',    // Edited - 2019-03-24
    'Reports'              => 'Отчеты',
    'Reset'                => 'Сбросить',
    'ResetEventCounts'     => 'Обнулить счетчик событий',
    'Restart'              => 'Перезапустить',
    'Restarting'           => 'Перезапускается',
    'RestrictedCameraIds'  => 'Id запрещенных камер',
    'RestrictedMonitors'   => 'Ограниченные мониторы',
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
    'SelfIntersecting'     => 'Края многоугольника не должны пересекаться',
    'Set'                  => 'Установка',  // Edited - 2019-03-24
    'SetNewBandwidth'      => 'Установка новой ширина канала',
    'SetPreset'            => 'Установка пресета',  // Edited - 2019-03-24
    'Settings'             => 'Настройки',
    'ShowFilterWindow'     => 'Показать окно фильтра',
    'ShowTimeline'         => 'Показать график',
    'SignalCheckColour'    => 'Цвет проверки сигнала',
    'SignalCheckPoints'    => 'Контрольные точки сигнала',    // Added - 2018-08-30
    'Size'                 => 'Размер',  // Edited - 2019-03-24
    'SkinDescription'      => 'Смена стандартного скина для данного компьютера', // Edited - 2019-03-24
    'Sleep'                => 'Сон',
    'Sort'                 => 'Сортировка',
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
    'StorageArea'          => 'Хранилище',           // Added - 2018-08-30
    'StorageScheme'        => 'Схема',                 // Added - 2018-08-30
    'Stream'               => 'Поток',
    'StreamReplayBuffer'   => 'Буфер потока повторного воспр.',
    'Stream quality'       => 'Качество потока',
    'Submit'               => 'Применить',
    'System'               => 'Система',
    'SystemLog'            => 'Лог системы',             // Added - 2011-06-16
    'Tags'                 => 'Теги',
    'TargetColorspace'     => 'Цветовое пространство',      // Added - 2015-04-18
    'Tele'                 => 'Теле',
    'Thumbnail'            => 'Миниатюра',
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
    'TooManyEventsForTimeline' => 'Слишком много событий для шкалы времени. Уменьшите количество мониторов или уменьшите видимый диапазон шкалы',
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
    'V4LCapturesPerFrame'  => 'Захват кадров',     // Added - 2015-04-18
    'V4LMultiBuffer'       => 'Мультибуферизация',        // Added - 2015-04-18
    'Value'                => 'Значение',
    'Version'              => 'Версия',
    'VersionIgnore'        => 'Игнорировать эту версию',
    'VersionRemindDay'     => 'Напомнить через день',
    'VersionRemindHour'    => 'Напомнить через час',
    'VersionRemindNever'   => 'Не говорить о новых версиях',
    'VersionRemindWeek'    => 'Напомнить через неделю',
    'Video'                => 'Видео',
    'VideoFormat'          => 'Формат видео', // Edited - 2019-03-24
    'VideoGenFailed'       => 'Ошибка генерации видео',
    'VideoGenFiles'        => 'Существующие видеофайлы',
    'VideoGenNoFiles'      => 'Видео не найдено',  // Edited - 2019-03-24
    'VideoGenParms'        => 'Параметры генерации видео',
    'VideoGenSucceeded'    => 'Видео сгенерировано!', // Edited - 2019-03-24
    'VideoSize'            => 'Размер изображения',
    'VideoWriter'          => 'Автор видео',           // Added - 2018-08-30
    'View'                 => 'Просмотр',
    'ViewAll'              => 'Просм. все',
    'ViewEvent'            => 'Просм. событие',  // Edited - 2019-03-24
    'ViewPaged'            => 'Просм. постранично',
    'Wake'                 => 'Разбудить',
    'WarmupFrames'         => 'Кадры разогрева',
    'Watch'                => 'Часы',
    'Web'                  => 'Интерфейс',
    'WebColour'            => 'Цвет отметки',
    'WebSiteUrl'           => 'URL сайта',            // Edited - 2019-03-25
    'Week'                 => 'Неделя',
    'White'                => 'Бал. белого',
    'WhiteBalance'         => 'Баланс белого',
    'Wide'                 => 'Широкий',
    'X'                    => 'X',
    'X10'                  => 'X10',
    'X10ActivationString'  => 'X10 Строка активации',
    'X10InputAlarmString'  => 'X10 Строка входного сигнала Тревоги',
    'X10OutputAlarmString' => 'X10 Строка выходного сигнала Тревоги',
    'Y'                    => 'Y',
    'Yes'                  => 'Да',
    'YouNoPerms'           => 'У вас недостаточно прав для доступа к этому ресурсу.',
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
// *********07-2022************************************ 
    'Storage'		   => 'Хранилище',
    'Back'                 => 'Вернуться',
    'ParentGroup'          => 'Родительская группа',
    'FilterUnarchiveEvents' => 'Разархивировать все совпадения',
    'FilterCopyEvents'     => 'Скопируйте все совпадения',
    'ViewMatches'          => 'Посмотреть совпадения',
    'ExportMatches'        => 'Экспорт совпадений',
    'FilterLockRows'       => 'Блокировать Строки',
    'OpLike'               => 'Содержит',
    'OpNotLike'            => 'Не содержит',
    'Width'                => 'Ширина',
    'Height'               => 'Высота',
    'PreviousMonitor'      => 'Предыдущий монитор',
    'PauseCycle'           => 'Приостановить цикл',
    'PlayCycle'            => 'Запуск цикла',
    'NextMonitor'          => 'Следующий монитор',
    'Server'               => 'Сервер',
    'Servers'              => 'Сервера',
    'DiskSpace'            => 'Дисковое пространство',
    'using'                => 'Утилизация',
    'of'                   => 'от',
    'EditControl'          => 'Редактировать элемент управления',
    'Privacy'              => 'Конфиденциальность',
    'APIEnabled'           => 'Включить API',
    'RevokeAllTokens'      => 'Отозвать все токены',
    'Revoke Token'         => 'Отозвать токен',
    'API Enabled'          => 'API включен',
    'CpuLoad'              => 'Загрузка процессора',
    'Actions'              => 'Действия',
    '24 Hour'              => '24 часа',
    '8 Hour'               => '8 часов',
    '1 Hour'               => '1 час',
    'Scale'                => 'Шкала', // Montage Review -> type button
    'All Events'           => 'Все события',
    'HISTORY'              => 'История', // Montage Review -> type button -> js
    'Archive Status'       => 'Архивный статус', // Montage Review -> type label
//    'Live'                 => 'En vivo', // Montage Review -> type button -> js
    'Download Video'       => 'Скачать видео',
//    '2 Wide'               => '2 Columnas', // Montage -> type Dropdown menu
    'Show Zones'           => 'Показать зоны',
    'Hide Zones'           => 'Скрыть зоны',
    'FirstEvent'           => 'Первое событие',
    'LastEvent'            => 'Последнее событие',
    'MissingFiles'         => 'Отсутствующие файлы',
    'ZeroSize'             => 'Нулевой размер',
    'MinGap'               => 'Минимальный разрыв',
    'MaxGap'               => 'Максимальный разрыв',
    'Event Start Time'     => 'Время начала события',
    'Start Time'           => 'Время старта',
    'to'                   => 'до',
    'Accept'               => 'Принять',
    'Decline'              => 'Отклонить',
    'Analysis Enabled'     => 'Анализ включен',
    'Importance'           => 'Важность',
    'DefaultCodec'         => 'Кодек по умолчанию для "Live"',
    'RTSPServer'           => 'Сервер RTSP',
    'RTSPStreamName'       => 'Название потока RTSP',
    'MinSectionlength'     => 'Минимальная длина секции',
    'seconds'              => 'секунд',
    'frames'               => 'кадров',
    'Latitude'             => 'Широта',
    'Longitude'            => 'Долгота',
    'GetCurrentLocation'   => 'Получить местоположение',
    'Location'             => 'Расположение',
    'ModectDuringPTZ'      => 'Обнаружение во время движения',
    'SourceSecondPath'     => 'Путь второго потока',
    'Decoder'              => 'Декодер',
    'DecoderHWAccelName'   => 'Имя аппаратного ускорителя декодирования',
    'DecoderHWAccelDevice' => 'Устройство аппаратного декодирования',
    'MaxImageBufferCount'  => 'Максимальный размер буфера изображений (кадров)',
    'ONVIF_URL'            => 'URL ONVIF',
    'ONVIF_Options'        => 'Опции для ONVIF',
    'OutputCodec'          => 'Выходной кодек',
    'Encoder'              => 'Кодировщик',
    'Encode'               => 'Кодирование',
    'Camera Passthrough'   => 'Проброс камеры',
    'Audio recording only available with FFMPEG' => 'Аудиозапись доступна только с помощью FFMPEG',
    'Estimated Ram Use'    => 'Расчетное использование ОЗУ',
    'Unlimited'            => 'Неограниченная', // Monitor / Buffers -> js
    'Percent'              => 'Процент',
    'Skin'                 => 'Тема',
/******************* 1.37.x **********************************/
    'Capturing'            => 'Захват',
    'Analysing'            => 'Анализ',
    'Recording'            => 'Запись',
    'Manufacturer'         => 'Производитель',
    'Model'                => 'Модель',
    'Analysis'             => 'Анализ',
    'Motion Detection'     => 'Обнаружение движения',
    'Analysis Image'       => 'Анализ изображения',
    'Decoding'             => 'Декодирование',
    'OutputContainer'      => 'Выходной контейнер',
    'Event Start Command'  => 'Команда запуска события',
    'Event End Command'    => 'Команда завершения события',
    'Viewing'              => 'Просмотр',
    'Janus Live Stream'    => 'Прямая трансляция Janus',
    'Janus Live Stream Audio' => 'Прямая трансляция аудио Janus',
    'ONVIF_Event_Listener' => 'Прослушиватель событий ONVIF',
    'Disabled'             => 'Отключенный',
    'Skip Locked'          => 'Пропуск заблокирован',
    'Execute Interval'     => 'Интервал выполнения',
    'Toggle cycle sidebar' => 'Переключение боковой панели цикла',
    'Memory'               => 'Память',
    'UnArchived'           => 'Неархивированный',
    'Fullscreen'           => 'Полноэкранный',
    'Always'               => 'Всегда',
    'On Demand'            => 'По требованию',
    'Fit'                  => 'В форме',
    'Live'                 => 'Прямой эфир',
    'Scale'                => 'Масштаб',
    'minute'               => 'минута',
    'minutes'              => 'минут',
    'on demand'            => 'по требованию',
    'On Motion'            => 'В движении',
    'OnMotion'             => 'При движении',
    'Prealarm'             => 'Предтревожный',
    'Settings only available for Local monitors.' => 'Настройки доступны только для локальных мониторов.',
    'KeyFrames Only'       => 'Только ключевые кадры',
    'Keyframes + Ondemand' => 'Ключевые кадры + по требованию',
    'On Motion / Trigger / etc'  => 'При движении / срабатывании / и т.д',
    'Less important'       => 'Менее важный',
    'Not important'        => 'Не важно',
    'Small'                => 'Маленький',
    'Large'                => 'Большой',
    'Extra Large'          => 'Очень большой',
    'Camera Passthrough - only for FFMPEG' => 'Проброс камеры - только для FFMPEG',
    'Frames only'          => 'Только кадры',
    'Analysis images only (if available)' => 'Только аналитические изображения (если есть)',
    'Frames + Analysis images (if available)' => 'Кадры + Аналитические изображения (если есть)',
    'Full Colour'          => 'Полноцветный',
    'Y-Channel (Greyscale)'=> 'Y-Канал (оттенки серого)',
    'Linear'               => 'Линейный',
    'Discard'              => 'Отбрасывать',
    'Blend'                => 'Смешивание',
    'Blend (25%)'          => 'Смешивание (25%)',
    'Four field motion adaptive - Soft' => 'Адаптивное движение в четырех полях - мягкое',
    'Four field motion adaptive - Medium' => 'Адаптивное движение с четырьмя полями - среднее',
    'Four field motion adaptive - Hard' => 'Адаптивное движение с четырьмя полями - жесткое',
    'No blending'          => 'Без смешивания',
    '6.25% (Indoor)'       => '6,25% (в помещении)',
    '12.5% (Outdoor)'      => '12,5% (Наружный)',
    'No blending (Alarm lasts forever)' => 'Без смешивания (сигнал тревоги длится вечно)',
    '50% (Alarm lasts a moment)' => '50% (сигнал тревоги длится мгновение)',
    'Change State'         => 'Изменить состояние',
    'Run State'            => 'Состояние выполнения',
//    '3 Wide'               => '3 Ancho', js
    'Showing Analysis'     => 'Отображение анализа',
    'ConfirmDeleteTitle'   => 'Подтвердите удаление заголовка',
    'Continuous'           => 'Непрерывный',
    'ONVIF_Alarm_Text'     => 'Текст сигнала тревоги ONVIF', //added 18/07/2022
    'None'                 => 'Нет',
    'Free'                 => 'Свободно',
    'RunStats'             => 'Текущие показатели',
    'RunAudit'             => 'Запуск аудита',
    'RunTrigger'           => 'Запуск триггера',
    'RunEventNotification' => 'Запустить уведомление о событии',
    'normal'               => 'нормальный',
    'Path'                 => 'Путь',
    'Snapshots'            => 'Снапшоты',
/******************* 27-02-24 **********************************/
    'ONVIF_EVENTS_PATH'    => 'Путь к ONVIF событиям',
    'SOAP WSA COMPLIANCE'  => 'Совместимость с SOAP WSA',

/******************* 16-07-24 Montage page ***************************/
    'Inside bottom'        => 'Внизу внутри',
    'Outside bottom'       => 'Внизу снаружи',
    'Hidden'               => 'Скрыт',
    'Show on hover'        => 'При наведении',

/******************* Название языков 27-02-24 **********************************/
    'es_la' => 'Испанский Латинская Америка',
    'es_CR' => 'Испанский Коста Рика',
    'es_ar' => 'Испанский Аргентина',
    'es_es' => 'Испанский',
    'en_gb' => 'Английский Британия',
    'en_us' => 'Английский',
    'fr_fr' => 'Французский',
    'cs_cz' => 'Чешский',
    'zh_cn' => 'Китайский упрощенный',
    'zh_tw' => 'Китайский Тайвань',
    'de_de' => 'Германский',
    'it_it' => 'Итальянский',
    'ja_jp' => 'Японский',
    'hu_hu' => 'Венгерский',
    'pl_pl' => 'Польский',
    'pt_br' => 'Португальский Бразилия',
    'ru_ru' => 'Русский',
    'nl_nl' => 'Голландский',
    'se_se' => 'Северносаамский Швеция',
    'et_ee' => 'Эстонский',
    'he_il' => 'Иврит',
    'dk_dk' => 'Датский',
    'ro_ro' => 'Румынский',
    'big5_big5' => 'Китайский традиционный',
    'ba_ba' => 'Боснийский',
);

// Complex replacements with formatting and/or placements, must be passed through sprintf
$CLANG = array(
    'CurrentLogin'         => 'Текущий пользователь: \'%1$s\'',
    'EventCount'           => '%1$s %2$s',
    'LastEvents'           => 'Последние %1$s %2$s',
    'LatestRelease'        => 'Последняя версия: v%1$s, у Вас установлена: v%2$s.',
    'MonitorCount'         => '%1$s %2$s',
    'MonitorFunction'      => 'Функция монитора %1$s',
    'RunningRecentVer'     => 'У вас установлена новейшая версия ZoneMinder, v%s.',
    'VersionMismatch'      => 'Несоответствие версий, версия системы - %1$s, версия БД - %2$s.', // Added - 2011-05-25
);

// Variable arrays expressing plurality, see the zmVlang description above
$VLANG = array(
    'Event'                => array( 0=>'Событий', 1=>'Событие', 2=>'События' ),
    'Monitor'              => array( 0=>'Мониторов', 1=>'Монитор', 2=>'Монитора' ),
);

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
    die( 'Невозможно сопоставить переменную языковую строку' );
}

$OLANG = array(
	'OPTIONS_FFMPEG' => array(
		'Help' => "Параметры в этом поле передаются в FFmpeg. Несколько параметров могут быть разделены с помощью ,~~ ".
		          "Примеры (не вводите кавычки)~~~~".
		          "\"allowed_media_types=video\" Установите тип данных для запроса с камеры (аудио, видео, данные)~~~~".
		          "\"reorder_queue_size=nnn\" Установите количество пакетов в буфер для обработки переупорядоченных пакетов~~~~".
		          "\"loglevel=debug\" Установить уровень информации.FFmpeg (тихий, паника, фатальный, ошибка, предупреждение, информация, подробный, отладка)"
	),
	'OPTIONS_LIBVLC' => array(
		'Help' => "Параметры в этом поле передаются в libVLC. Несколько параметров могут быть разделены ,~~ ".
		          "Примеры (не вводите кавычки)~~~~".
		          "\"--rtp-client-port=nnn\" Установите локальный порт для использования для данных rtp~~~~". 
		          "\"--verbose=2\" Установить уровень информации. из libVLC"
	),
	 'OPTIONS_ENCODER_PARAMETERS' => array(
    		'Help' => '
    Параметры, передаваемые кодеку кодирования. имя=значение, разделенное либо , либо новой строкой.~~
    Например, для изменения качества используйте опцию crf.  1 - лучший, 51 - худший, 23 - по умолчанию.~~
~~
    crf=23~~
    ~~
    Возможно, вы захотите изменить значение movflags для поддержки другого поведения. У некоторых людей возникают проблемы с просмотром видео из-за опции frag_keyframe, но предполагается, что эта опция позволяет просматривать незавершенные события. Посмотрите 
    [https://ffmpeg.org/ffmpeg-formats.html](https://ffmpeg.org/ffmpeg-formats.html)
    для получения дополнительной информации.  Значение по умолчанию ZoneMinder равно frag_keyframe,empty_moov~~
    ',
  ),
  'OPTIONS_DECODERHWACCELNAME' => array(
    'Help' => '
    Это эквивалентно параметру командной строки ffmpeg -hwaccel.  При поддержке графики intel используйте "vaapi".  Для поддержки NVIDIA cuda используйте "cuda". Чтобы проверить наличие поддержки, запустите ffmpeg -hwaccel в командной строке.'
    ),
  'OPTIONS_DECODERHWACCELDEVICE' => array(
    'Help' => '
    Это эквивалентно параметру командной строки ffmpeg -hwaccel_device.  Вам нужно указать это только в том случае, если у вас несколько графических процессоров.  Типичным значением для Intel VAAPI будет /dev/dri/renderD128.'
    ),
    'OPTIONS_RTSPTrans' => array(
      'Help' => '
        Это устанавливает транспортный протокол RTSP для FFmpeg.~~
        TCP - Используйте TCP (чередование в канале управления RTSP) в качестве транспортного протокола.~~
        UDP - Используйте UDP в качестве транспортного протокола. Камеры с более высоким разрешением испытали некоторое \'размытие\' при использовании UDP, если это так, попробуйте TCP~~
        Многоадресная рассылка UDP - Используйте многоадресную рассылку UDP в качестве транспортного протокола~~
        HTTP - Используйте туннелирование HTTP в качестве транспортного протокола, который полезен для передачи прокси-серверов.~~
      '
	),
	'OPTIONS_RTSPDESCRIBE' => array(
    'Help' => '
      Иногда во время первоначального рукопожатия RTSP камера отправляет обновленный URL-адрес мультимедиа.
      Включите этот параметр, чтобы указать ZoneMinder использовать этот URL-адрес. Отключите этот параметр, чтобы игнорировать
      значение с камеры и использовать значение, введенное в конфигурации монитора~~~~
      Как правило, это должно быть включено. Однако бывают случаи, когда камера может получить свой
      неверный собственный URL-адрес, например, при потоковой передаче камеры через брандмауэр'
  ),
	'OPTIONS_MAXFPS' => array(
    'Help' => '
      Это поле имеет определенные ограничения при использовании для нелокальных устройств.~~
      Несоблюдение этих ограничений приведет к задержке видео в реальном времени, неправильному пропуску кадров
      и пропущенным событиям~~
      Для потоковых IP-камер не используйте это поле для уменьшения частоты кадров. Вместо этого установите частоту кадров в
      камере. В прошлом советовалось устанавливать значение, превышающее частоту кадров камеры
      но в этом больше нет необходимости или это хорошая идея.
      Некоторые, в основном старые, IP-камеры поддерживают режим моментального снимка. В этом случае ZoneMinder активно опрашивает камеру
      для новых изображений. В этом случае использование поля безопасно.
      '
	),
	'OPTIONS_LINKED_MONITORS' => array(
    'Help' => '
      Это поле позволяет вам выбрать другие мониторы в вашей системе, которые действуют как
      триггеры для этого монитора. Таким образом, если у вас есть камера, охватывающая один аспект
      вашей собственности, вы можете заставить все камеры записывать, пока эта камера 
      обнаруживает движение или другие события. Нажмите на кнопку "Выбрать", чтобы выбрать связанные мониторы. 
      Будьте очень осторожны, чтобы не создавать циклические зависимости с помощью этой функции 
      потому что у вас будут бесконечно сохраняющиеся сигналы тревоги, а это почти
      наверняка не то, чего вы хотите! Чтобы разорвать связь с мониторами, вы можете нажать ctrl-click.
      '
	),
  'OPTIONS_CAPTURING' => array(
    'Help' => 'Когда делать захват:~~~~
Нет: не запускайте процесс, не выполняйте захват.  Эквивалент старой функции == Нет~~~~
По требованию: Процесс zmc будет запущен, но будет ждать просмотра (просмотр в реальном времени, миниатюра или подключение к серверу rstp) перед подключением к камере.~~~~
Всегда: Будет запущен процесс zmc, который немедленно подключится и останется на связи.~~~~
'
  ),
    'OPTIONS_SOAP_wsa' => array(
    'Help' => '
      Отключите, если вы получаете сообщение об ошибке:
      ~~ "Couldnt do Renew Error 12 ActionNotSupported <env:Text>The device do NOT support this feature</env:Text>"
      при попытке включить/использовать ONVIF
      ~~ Отключение может помочь заставить работать ONVIF на некоторых китайских камерах, которые не полностью поддерживают ONVIF
      '
    ),
  'FUNCTION_ANALYSIS_ENABLED' => array(
    'Help' => '
      Когда следует выполнять обнаружение движения на захваченном видео.  
      Этот параметр устанавливает состояние по умолчанию при запуске процесса.
      Затем его можно включить / выключить с помощью внешних триггеров trigger zmu или веб-интерфейса.
      Если этот параметр не включен, обнаружение движения или проверка связанного монитора выполняться
не будут и никакие события создаваться не будут.      '
  ),
  'FUNCTION_DECODING' => array(
    'Help' => '
      Если не выполняется обнаружение движения и используется передача H264 без сохранения файлов jpeg, мы можем
дополнительно выбрать не декодировать пакеты H264 / H265.  Это значительно сократит использование процессора.~~~~
Всегда: каждый кадр будет декодирован, будет доступен просмотр в реальном времени и миниатюры.~~~~
OnDemand: выполняйте декодирование только тогда, когда кто-то смотрит.~~~~
Ключевые кадры: Будут декодированы только ключевые кадры, поэтому частота кадров при просмотре будет очень низкой, в зависимости от интервала ключевых кадров, установленного в камере.~~~~
Нет: кадры декодироваться не будут, просмотр в реальном времени и миниатюры будут недоступны.~~~~
'
  ),
  'FUNCTION_JANUS_ENABLED' => array(
    'Help' => '
      Попробуйте использовать потоковый сервер Janus для просмотра в реальном времени h264/h265. Экспериментальный, но позволяет
      для значительно лучшей производительности.'
  ),
  'FUNCTION_JANUS_AUDIO_ENABLED' => array(
    'Help' => '
      Попытайтесь включить звук в потоке Janus. Не влияет на камеры без поддержки звука,
      но может помешать воспроизведению потока, если ваша камера отправляет аудиоформат, не поддерживаемый браузером.'
  ),
  'FUNCTION_JANUS_PROFILE_OVERRIDE' => array(
    'Help' => '
      Вручную установите идентификатор профиля, который может заставить браузер попытаться воспроизвести данный поток. Попробуйте "42e01f"
      для универсально поддерживаемого значения или оставьте это поле пустым, чтобы использовать идентификатор профиля, указанный источником.'
  ),
  'FUNCTION_JANUS_USE_RTSP_RESTREAM' => array(
    'Help' => '
      Если ваша камера не будет работать в Janus с какими-либо другими опциями, включите это, чтобы использовать ZoneMinder
      Поток RTSP в качестве источника Janus.'
  ),
  'ImageBufferCount' => array(
    'Help' => '
    Количество необработанных изображений, доступных в /dev/shm. В настоящее время должно быть установлено в диапазоне 3-5.  Используется для просмотра в реальном времени.'
  ),
  'MaxImageBufferCount' => array(
    'Help' => '
    Максимальное количество видеопакетов, которые будут храниться в очереди пакетов.
    Очередь пакетов обычно управляется сама собой, сохраняя кадры предварительного подсчета событий или все кадры с момента последнего ключевого кадра, если используется
    режим передачи. Вы можете установить максимальное значение, чтобы монитор не потреблял слишком много оперативной памяти, но ваши события могут
    не все кадры, которые они должны иметь, если ваш интервал ключевых кадров больше этого значения.
    Вы получите ошибки в своих журналах по этому поводу. Поэтому убедитесь, что ваш интервал ключевых кадров невелик или у вас достаточно оперативной памяти.'
  ),
'ZM_BANDWIDTH_DEFAULT' => array(
    'Help' => '
    Классический скин для ZoneMinder имеет различные профили для использования при соединениях с низкой, средней или высокой пропускной способностью.'
  ),
'ZM_SKIN_DEFAULT' => array(
    'Help' => '
    ZoneMinder позволяет использовать множество различных веб-интерфейсов. Этот параметр позволяет вам установить скин по умолчанию, используемый веб-сайтом. Пользователи могут изменить свой скин позже, это просто устанавливает значение по умолчанию.'
  ),
'ZM_CSS_DEFAULT' => array(
    'Help' => '
    ZoneMinder позволяет использовать множество различных веб-интерфейсов, а некоторые скины позволяют использовать другой набор CSS-файлов для управления внешним видом. 
    Этот параметр позволяет вам установить набор css-файлов по умолчанию, используемых веб-сайтом. 
    Пользователи могут изменить свой css позже, это просто устанавливает значение по умолчанию.'
  ),
'ZM_LANG_DEFAULT' => array(
    'Help' => '
    ZoneMinder позволяет веб-интерфейсу использовать языки, отличные от английского, если соответствующий языковой файл был создан и присутствует. 
    Этот параметр позволяет вам изменить используемый по умолчанию язык с поставляемого языка, британского английского, на другой язык'
  ),
'ZM_LOCALE_DEFAULT' => array(
    'Help' => '
    ZoneMinder по умолчанию будет использовать языковой стандарт, установленный системой. Эта опция позволяет переопределить его. 
    Языковой стандарт используется для определения строки формата, используемой при форматировании дат и времени.'
  ),
'ZM_DATE_FORMAT_PATTERN' => array(
    'Help' => '
    Шаблон, используемый для переопределения строки формата, используемой для дат. Оставьте его пустым, чтобы использовать значения по умолчанию для заданной локали. 
    Посмотрите <a href="https://unicode-org.github.io/icu/userguide/format_parse/datetime/" target="_blank">unicode-org</a> для значений'
  ),
'ZM_TIME_FORMAT_PATTERN' => array(
    'Help' => '
    Шаблон, используемый для переопределения строки формата, используемой для времени без дат. 
    Оставьте его пустым, чтобы использовать значения по умолчанию для заданной локали. 
    Значения см. в разделе unicode-org.'
  ),
'ZM_DATETIME_FORMAT_PATTERN' => array(
    'Help' => '
    Шаблон, используемый для переопределения строки формата, используемой для дат и времени. 
    Оставьте его пустым, чтобы использовать значения по умолчанию для заданной локали. 
    Значения см. в разделе unicode-org.'
  ),
'ZM_OPT_USE_AUTH' => array(
    'Help' => '
    ZoneMinder может работать в двух режимах. 
    Самый простой - это полностью неаутентифицированный режим, в котором любой желающий может получить доступ к ZoneMinder и выполнять все задачи. 
    Это наиболее подходит для установок, где доступ к веб-серверу ограничен другими способами. 
    Другой режим позволяет использовать учетные записи пользователей с различными наборами разрешений. 
    Пользователи должны войти в систему или пройти аутентификацию, чтобы получить доступ к ZoneMinder, и они ограничены определенными разрешениями.'
  ),
'ZM_AUTH_TYPE' => array(
    'Help' => '
    ZoneMinder может использовать два метода для аутентификации пользователей при запуске в режиме аутентификации. 
    Первый - это встроенный метод, в котором ZoneMinder предоставляет пользователям возможность входа в систему и отслеживает их личность. 
    Второй метод позволяет взаимодействовать с другими методами, такими как базовая аутентификация http, которая проходит независимую аутентификацию "удаленного" пользователя через http. 
    В этом случае ZoneMinder будет использовать предоставленного пользователя без дополнительной аутентификации при условии, что такой пользователь настроен в ZoneMinder.'
  ),
'ZM_AUTH_RELAY' => array(
    'Help' => '
    Когда ZoneMinder работает в режиме аутентификации, он может передавать данные пользователя между веб-страницами и внутренними процессами. 
    Для этого есть два способа. 
    Первый способ заключается в использовании хэшированной строки с ограниченным временем, которая не содержит прямых сведений о имени пользователя или пароле, второй способ заключается в передаче имени пользователя и паролей открытым текстом. 
    Этот метод не рекомендуется, за исключением случаев, когда в вашей системе отсутствуют библиотеки md5 или у вас полностью изолированная система без внешнего доступа. 
    Вы также можете отключить ретрансляцию аутентификации, если ваша система изолирована другими способами.'
  ),
'ZM_AUTH_HASH_SECRET' => array(
    'Help' => '
    Когда ZoneMinder работает в режиме хэшированной аутентификации, необходимо сгенерировать хэшированные строки, содержащие зашифрованную конфиденциальную информацию, такую как имена пользователей и пароль. 
    Хотя эти строки достаточно безопасны, добавление случайного секрета существенно повышает безопасность.'
  ),
'ZM_AUTH_HASH_IPS' => array(
    'Help' => '
    Когда ZoneMinder работает в режиме хэшированной аутентификации, он может дополнительно включать запрашивающий IP-адрес в результирующий хэш. 
    Это добавляет дополнительный уровень безопасности, поскольку только запросы с этого адреса могут использовать этот ключ аутентификации. 
    Однако в некоторых обстоятельствах, таких как доступ по мобильным сетям, запрашивающий адрес может меняться для каждого запроса, что приведет к сбою большинства запросов. 
    Этот параметр позволяет вам контролировать, включаются ли IP-адреса в хэш аутентификации в вашей системе. 
    Если у вас периодически возникают проблемы с аутентификацией, отключение этой опции может помочь.'
  ),
'ZM_AUTH_HASH_TTL' => array(
    'Help' => '
    По умолчанию традиционно используется значение 2 часа. Новый хэш будет автоматически восстановлен с половиной этого значения.'
  ),
'ZM_AUTH_HASH_LOGINS' => array(
    'Help' => '
    Обычный процесс входа в ZoneMinder осуществляется через экран входа в систему с именем пользователя и паролем. 
    В некоторых обстоятельствах может оказаться желательным разрешить прямой доступ к одной или нескольким страницам, например, из стороннего приложения. 
    Если эта опция включена, то добавление параметра \'auth\' к любому запросу будет включать в себя быстрый вход в систему в обход экрана входа в систему, если вы еще не вошли в систему. 
    Поскольку хэши аутентификации ограничены по времени и, при необходимости, по IP, это может обеспечить кратковременный доступ к экранам ZoneMinder с других веб-страниц и т.д. 
    Чтобы использовать это, вызывающее приложение должно будет само сгенерировать хэш аутентификации и убедиться, что он действителен. 
    Если вы используете этот параметр, вы должны убедиться, что вы изменили \'ZM_AUTH_HASH_SECRET\' на что-то уникальное для вашей системы.'
  ),
'ZM_ENABLE_CSRF_MAGIC' => array(
    'Help' => '
    CSRF расшифровывается как подделка межсайтовых запросов, которая при определенных обстоятельствах может позволить злоумышленнику выполнить любую задачу, на выполнение которой у вашей учетной записи пользователя ZoneMinder есть разрешение. 
    Чтобы добиться этого, злоумышленник должен написать очень специфическую веб-страницу и заставить вас перейти на нее, в то время как вы одновременно входите в веб-консоль ZoneMinder. 
    Включение \'ZM_ENABLE_CSRF_MAGIC\' поможет смягчить подобные атаки.'
  ),
'ZM_OPT_USE_API' => array(
    'Help' => '
    ZoneMinder теперь имеет новый API, с помощью которого сторонние приложения могут взаимодействовать с данными ZoneMinder. Настоятельно рекомендуется включить проверку подлинности вместе с API. 
    Обратите внимание, что API-интерфейсы возвращают конфиденциальные данные, такие как сведения о доступе к монитору, которые настроены как объекты JSON. 
    Именно поэтому мы рекомендуем вам включить проверку подлинности, особенно если вы предоставляете доступ к своему экземпляру ZM в Интернете.'
  ),
'ZM_OPT_USE_LEGACY_API_AUTH' => array(
    'Help' => '
    Начиная с версии 1.34.0, ZoneMinder использует более безопасный механизм аутентификации с использованием токенов JWT. 
    Более старые версии использовали менее безопасный хэш аутентификации на основе MD5. 
    Рекомендуется отключить эту функцию после того, как вы убедитесь, что она вам не нужна. 
    Если вы используете стороннее приложение, которое использует более старые механизмы аутентификации API, вам придется обновить это приложение, если вы отключите это. 
    Обратите внимание, что zmNinja с версии 1.3.057 поддерживает новую систему токенов'
  ),
'ZM_OPT_USE_EVENTNOTIFICATION' => array(
    'Help' => '
    zm event notification - это сторонний сервер уведомлений о событиях, который используется для получения уведомлений о тревогах, обнаруженных ZoneMinder в режиме реального времени. 
    zmNinja требуется этот сервер для push-уведомлений на мобильные телефоны. 
    Этот параметр включает сервер только в том случае, если он уже установлен. 
    Пожалуйста, посетите сайт проекта zeneventserver для получения инструкций по установке.'
  ),
'ZM_OPT_USE_GOOG_RECAPTCHA' => array(
    'Help' => '
    Этот параметр позволяет вам включить проверку Google reCAPTCHA при входе в систему. 
    Это означает, что в дополнение к предоставлению действительного имени пользователя и пароля вам также необходимо будет пройти тест reCAPTCHA. 
    Пожалуйста, обратите внимание, что включение этой опции приводит к тому, что страница входа в zoneminder обращается к серверам Google для проверки captcha. 
    Также, пожалуйста, обратите внимание, что включение этой опции приведет к сбою сторонних клиентов, таких как zmNinja и zmView, поскольку им также необходимо войти в ZoneMinder, и они не пройдут тест reCAPTCHA.'
  ),
'ZM_OPT_GOOG_RECAPTCHA_SITEKEY' => array(
    'Help' => '
    Вам необходимо сгенерировать свои ключи с веб-сайта Google reCAPTCHA. 
    Пожалуйста, обратитесь к сайту проекта recaptcha для получения более подробной информации.'
  ),
'ZM_OPT_GOOG_RECAPTCHA_SECRETKEY' => array(
    'Help' => '
    Вам необходимо сгенерировать свои ключи с веб-сайта Google reCAPTCHA. 
    Пожалуйста, обратитесь к сайту проекта recaptcha для получения более подробной информации.'
  ),
'ZM_OPT_USE_GEOLOCATION' => array(
    'Help' => '
    Следует ли включать настройки широты/долготы на мониторах и включать параметры отображения.'
  ),
'ZM_OPT_GEOLOCATION_TILE_PROVIDER' => array(
    'Help' => '
    Openstreetmap сам по себе не предоставляет изображения для использования на карте. 
    Есть из чего выбирать. 
    Mapbox.com это один из примеров, который предлагает бесплатные плитки и был протестирован во время разработки этой функции.'
  ),
'ZM_OPT_GEOLOCATION_ACCESS_TOKEN' => array(
    'Help' => '
    Openstreetmap сам по себе не предоставляет изображения для использования на карте. 
    Есть из чего выбирать. 
    Mapbox.com это один из примеров, который предлагает бесплатные плитки и был протестирован во время разработки этой функции. 
    Вы должны пойти в mapbox.com и зарегистрируйтесь, и получите токен доступа, и вырежьте и вставьте его сюда.'
  ),
'ZM_SYSTEM_SHUTDOWN' => array(
    'Help' => '
    В системе необходимо будет установить sudo и добавить следующее в /etc/sudoers

    www-data ALL=NOPASSWD: /sbin/shutdown

    для завершения работы или перезагрузки'
  ),
'ZM_OPT_FAST_DELETE' => array(
    'Help' => '
    Обычно событие, созданное в результате аварийного сигнала, состоит из записей в одной или нескольких таблицах базы данных плюс различные файлы, связанные с ним. 
    При удалении событий в браузере может потребоваться много времени, чтобы удалить все это, если вы пытаетесь выполнить много событий одновременно. 
    Рекомендуется установить этот параметр, который означает, что клиент браузера удаляет только ключевые записи в таблице событий, что означает, что события больше не будут отображаться в списке, и оставляет демону zmaudit для очистки остальных позже. 
    Обратите внимание, что эта функция менее актуальна для современного оборудования. Рекомендуем отключить эту функцию.'
  ),
'ZM_FILTER_RELOAD_DELAY' => array(
    'Help' => '
    ZoneMinder позволяет сохранять фильтры в базе данных, которые позволяют отправлять события, соответствующие определенным критериям, по электронной почте, удалять или загружать на удаленную машину и т.д. 
    Демон фильтра загружает их и выполняет фактическую операцию. 
    Этот параметр определяет, как часто фильтры перезагружаются из базы данных для получения последних версий или новых фильтров. 
    Если вы не очень часто меняете фильтры, это значение может быть установлено на большое значение.'
  ),
'ZM_FILTER_EXECUTE_INTERVAL' => array(
    'Help' => '
    ZoneMinder позволяет сохранять фильтры в базе данных, которые позволяют отправлять события, соответствующие определенным критериям, по электронной почте, удалять или загружать на удаленную машину и т.д. 
    Демон фильтра загружает их и выполняет фактическую операцию. Этот параметр определяет, как часто фильтры выполняются для сохраненного события в базе данных. 
    Если вы хотите быстро реагировать на новые события, это должно быть меньшее значение, однако это может увеличить общую нагрузку на систему и повлиять на производительность других элементов.'
  ),
'ZM_MAX_RESTART_DELAY' => array(
    'Help' => '
    Процесс zmdc (zm daemon control) управляет запуском или остановкой процессов и попытается перезапустить любой из них, который завершится неудачей. 
    Если демон часто выходит из строя, то между каждой попыткой перезапуска вводится задержка. 
    Если демон все еще терпит неудачу, то эта задержка увеличивается, чтобы предотвратить дополнительную нагрузку на систему из-за постоянных перезапусков. 
    Этот параметр определяет, какова эта максимальная задержка.'
  ),
'ZM_STATS_UPDATE_INTERVAL' => array(
    'Help' => '
    Демон zmstats выполняет различные запросы к базе данных, которые могут занимать много времени в фоновом режиме.'
  ),
'ZM_WATCH_CHECK_INTERVAL' => array(
    'Help' => '
    Демон zmwatch проверяет производительность захвата изображений демонами захвата, чтобы убедиться, что они не заблокированы (в редких случаях может возникнуть ошибка синхронизации, которая блокируется на неопределенный срок). 
    Этот параметр определяет, как часто проверяются демоны.'
  ),
'ZM_WATCH_MAX_DELAY' => array(
    'Help' => '
    Демон zmwatch проверяет производительность захвата изображений демонами захвата, чтобы убедиться, что они не заблокированы (в редких случаях может возникнуть ошибка синхронизации, которая блокируется на неопределенный срок). 
    Этот параметр определяет максимальную задержку, допустимую с момента последнего захваченного кадра. 
    Демон будет перезапущен, если по истечении этого периода он не захватил никаких изображений, хотя фактический перезапуск может занять немного больше времени в сочетании с указанным выше значением интервала проверки.'
  ),
'ZM_RUN_AUDIT' => array(
    'Help' => '
    Демон zmaudit существует для проверки соответствия и согласованности сохраненной информации в базе данных и файловой системе. 
    Если возникает ошибка или если вы используете "быстрое удаление", возможно, записи базы данных удаляются, но файлы остаются. 
    В этом и аналогичном случае zmaudit удалит избыточную информацию для синхронизации двух хранилищ данных. 
    Этот параметр определяет, выполняется ли zmaudit в фоновом режиме, и выполняет эти проверки и исправления непрерывно. 
    Это рекомендуется для большинства систем, однако, если у вас очень большое количество событий, процесс сканирования базы данных и файловой системы может занять много времени и повлиять на производительность. 
    В этом случае вы можете предпочесть не запускать zmaudit безоговорочно и запланировать периодические проверки в другое, более удобное время.'
  ),
'ZM_AUDIT_CHECK_INTERVAL' => array(
    'Help' => '
    Демон zmaudit существует для проверки соответствия и согласованности сохраненной информации в базе данных и файловой системе. 
    Если возникает ошибка или если вы используете "быстрое удаление", возможно, записи базы данных удаляются, но файлы остаются. 
    В этом и аналогичном случае zmaudit удалит избыточную информацию для синхронизации двух хранилищ данных. 
    Интервал проверки по умолчанию в 900 секунд (15 минут) подходит для большинства систем, однако, если у вас очень большое количество событий, процесс сканирования базы данных и файловой системы может занять много времени и повлиять на производительность. 
    В этом случае вы можете предпочесть сделать этот интервал намного больше, чтобы уменьшить воздействие на вашу систему. 
    Этот параметр определяет, как часто выполняются эти проверки.'
  ),
'ZM_AUDIT_MIN_AGE' => array(
    'Help' => '
    Демон zmaudit существует для проверки соответствия и согласованности сохраненной информации в базе данных и файловой системе. 
    Файлы событий или записи базы данных, которые моложе этого параметра, не будут удалены, и будет выдано предупреждение.'
  ),
'ZM_OPT_CONTROL' => array(
    'Help' => '
    ZoneMinder включает ограниченную поддержку управляемых камер. 
    Включен ряд примеров протоколов, и другие могут быть легко добавлены. 
    Если вы хотите управлять своими камерами через ZoneMinder, выберите этот параметр, в противном случае, если у вас есть только статические камеры или вы используете другие методы управления, оставьте этот параметр выключенным.'
  ),
'ZM_OPT_TRIGGERS' => array(
    'Help' => '
    ZoneMinder может взаимодействовать с внешними системами, которые запрашивают или отменяют сигналы тревоги. 
    Это делается с помощью zmtrigger.pl сценарий. 
    Этот параметр указывает, хотите ли вы использовать эти внешние триггеры. 
    Большинство людей скажут здесь "нет".'
  ),
'ZM_CHECK_FOR_UPDATES' => array(
    'Help' => '
    Ожидается, что начиная с версии 1.17.0 ZoneMinder новые версии будут появляться чаще. 
    Чтобы сохранить проверку вручную для каждой новой версии, ZoneMinder может проверить с помощью zoneminder.com веб-сайт, чтобы определить самую последнюю версию. 
    Эти проверки проводятся нечасто, примерно раз в неделю, и никакая личная или системная информация, кроме вашего текущего номера версии, не передается. 
    Если вы не хотите, чтобы эти проверки выполнялись, или ваша система ZoneMinder не имеет доступа в Интернет, вы можете отключить эти проверки с помощью этой переменной конфигурации'
  ),
'ZM_CSP_REPORT_URI' => array(
    'Help' => '
    Посмотрите https://en.wikipedia.org/wiki/Content_Security_Policy для получения дополнительной информации. 
    Когда браузер обнаруживает небезопасный встроенный javascript, он сообщает об этом по этому URL-адресу, который может предупредить вас о вредоносных атаках на вашу установку ZoneMinder.'
  ),
'ZM_TELEMETRY_DATA' => array(
    'Help' => '
    Включите сбор информации об использовании локальной системы и отправьте ее команде разработчиков ZoneMinder. 
    Эти данные будут использоваться для определения таких вещей, как кто и где находятся наши клиенты, насколько велики их системы, базовое оборудование и операционная система и т.д. 
    Это делается с единственной целью - создать лучший продукт для нашей целевой аудитории. 
    Этот сценарий предназначен для того, чтобы быть полностью прозрачным для конечного пользователя, и его можно отключить в веб-консоли в разделе Параметры. 
    Для получения более подробной информации о том, какую информацию мы собираем, пожалуйста, ознакомьтесь с нашим заявлением о конфиденциальности.'
  ),
'ZM_TELEMETRY_INTERVAL' => array(
    'Help' => '
    Для удобства это значение может быть выражено в виде математического выражения.'
  ),
'ZM_UPDATE_CHECK_PROXY' => array(
    'Help' => '
    Если вы используете прокси-сервер для доступа в Интернет, то ZoneMinder должен знать об этом, чтобы он мог получить доступ zoneminder.com чтобы проверить наличие обновлений. 
    Если вы используете прокси-сервер, введите полный URL-адрес прокси-сервера здесь в форме http://<proxy host>:<proxy port>/'
  ),
'ZM_SHM_KEY' => array(
    'Help' => '
    ZoneMinder использует общую память для ускорения обмена данными между модулями. 
    Для определения нужной области для использования используются ключи общей памяти. 
    Этот параметр определяет, что такое базовый ключ, каждый монитор будет иметь свой идентификатор или использовать его, чтобы получить фактический используемый ключ. 
    Обычно вам не нужно изменять это значение, если оно не конфликтует с другим экземпляром ZoneMinder на том же компьютере. 
    Используются только первые четыре шестнадцатеричные цифры, нижние четыре будут замаскированы и проигнорированы.'
  ),
'ZM_COOKIE_LIFETIME' => array(
    'Help' => '
    Это повлияет на то, как долго сеанс будет действителен с момента последнего запроса. 
    Сохранение этой краткости помогает предотвратить перехват сеанса. 
    Сохранение его в течение длительного времени позволяет вам дольше оставаться в системе, не обновляя представление.'
  ),
'ZM_TIMESTAMP_ON_CAPTURE' => array(
    'Help' => '
    ZoneMinder может добавлять временную метку к изображениям двумя способами. 
    Метод по умолчанию, когда этот параметр установлен, заключается в том, что каждое изображение сразу же помечается временной меткой при захвате, и поэтому изображение, хранящееся в памяти, сразу же помечается. 
    Второй метод не ставит временные метки на изображения до тех пор, пока они не будут либо сохранены как часть события, либо доступны через Интернет. 
    Временная метка, используемая в обоих методах, будет содержать одно и то же время, поскольку она сохраняется вместе с изображением. 
    Первый метод гарантирует, что изображение будет помечено временем независимо от любых других обстоятельств, но приведет к тому, что все изображения будут помечены временем, даже те, 
    которые никогда не сохранялись или не просматривались. 
    Второй способ требует, чтобы сохраненные изображения копировались перед сохранением, в противном случае могут быть применены две временные метки, возможно, в разных масштабах. 
    Это имеет (возможно) желательный побочный эффект, заключающийся в том, что временная метка всегда применяется с одинаковым разрешением, 
    поэтому изображение, к которому применено масштабирование, все равно будет иметь четкую и правильно масштабированную временную метку.'
  ),
'ZM_TIMESTAMP_CODE_CHAR' => array(
    'Help' => '
    Есть несколько кодов, которые можно использовать, чтобы указать ZoneMinder вставлять данные во временную метку каждого изображения. 
    Традиционно для идентификации этих кодов использовался символ процента (%), поскольку текущие коды символов не конфликтуют с кодами strftime, которые также можно использовать в метке времени. 
    Хотя это хорошо работает для Linux, это плохо работает для операционных систем BSD. Изменение символа по умолчанию на что-то другое, например восклицательный знак (!), устраняет проблему. 
    Обратите внимание, что это влияет только на коды временных меток, встроенные в ZoneMinder. 
    Это никак не влияет на семейство кодов strftime, которые можно использовать.'
  ),
'ZM_CPU_EXTENSIONS' => array(
    'Help' => '
    Когда доступны расширенные процессорные расширения, такие как SSE2 или SSSE3, ZoneMinder может использовать их, что должно повысить производительность и снизить нагрузку на систему. 
    Включение этой опции на процессорах, которые не поддерживают расширенные расширения процессоров, используемые ZoneMinder, безвредно и не будет иметь никакого эффекта.'
  ),
'ZM_FAST_IMAGE_BLENDS' => array(
    'Help' => '
    Для обнаружения сигналов тревоги ZoneMinder необходимо смешать захваченное изображение с сохраненным эталонным изображением, чтобы обновить его для сравнения со следующим изображением. 
    Процент наложения эталона, указанный для монитора, определяет, насколько новое изображение влияет на эталонное изображение. 
    Для этого доступны два метода. Если этот параметр установлен, то используется быстрое вычисление, которое не использует никакого умножения или деления. 
    Этот расчет выполняется чрезвычайно быстро, однако он ограничивает возможные проценты смешивания следующим 50%, 25%, 12.5%, 6.25%, 3.25% и 1,5%. 
    Любой другой процент смешивания будет округлен до ближайшего возможного значения. 
    Альтернативой является отключение этой опции и использование вместо нее стандартного наложения, что происходит медленнее.'
  ),
'ZM_OPT_ADAPTIVE_SKIP' => array(
    'Help' => '
    В предыдущих версиях ZoneMinder демон анализа пытался идти в ногу с демоном захвата, обрабатывая последний захваченный кадр на каждом проходе. 
    Иногда это может иметь нежелательный побочный эффект, заключающийся в пропуске фрагмента начальной активности, вызвавшей тревогу, поскольку все кадры, предшествующие тревоге, 
    должны быть записаны на диск и в базу данных перед обработкой следующего кадра, что приводит к некоторой задержке между первым и вторым кадрами события. 
    Установка этого параметра позволяет использовать более новый адаптивный алгоритм, в котором демон анализа пытается обработать как можно больше захваченных кадров, пропуская кадры только тогда, 
    когда существует опасность перезаписи демоном захвата еще не обработанных кадров. 
    Этот пропуск является переменным в зависимости от размера кольцевого буфера и количества свободного места, оставшегося в нем. 
    Включение этой опции даст вам гораздо лучший охват начала сигналов тревоги при одновременном смещении любых пропущенных кадров к середине или концу события. 
    Однако вы должны знать, что это приведет к тому, что демон анализа будет выполняться несколько позже демона захвата во время событий, 
    а при особенно высоких скоростях захвата адаптивный алгоритм может быть перегружен и не успеть отреагировать на быстрое накопление ожидающих кадров и, 
    следовательно, для буфера должно возникнуть условие переполнения.'
  ),
'ZM_MAX_SUSPEND_TIME' => array(
    'Help' => '
    ZoneMinder позволяет мониторам приостанавливать обнаружение движения, например, во время панорамирования камеры. 
    Обычно это зависит от того, что оператор впоследствии возобновит обнаружение движения, поскольку невыполнение этого требования может привести к тому, что монитор будет постоянно находиться в подвешенном состоянии. 
    Этот параметр позволяет установить максимальное время, на которое камера может быть приостановлена, прежде чем она автоматически возобновит обнаружение движения. 
    Это время может быть увеличено с помощью последующих индикаций приостановки после первой, так что непрерывное движение камеры также будет происходить во время приостановки монитора.'
  ),
'ZM_STRICT_VIDEO_CONFIG' => array(
    'Help' => '
    С некоторыми видеоустройствами могут сообщаться об ошибках при настройке различных атрибутов видео, когда на самом деле операция прошла успешно. 
    Выключение этой опции по-прежнему позволит сообщать об этих ошибках, но не приведет к их завершению работы демона видеозахвата. 
    Однако обратите внимание, что это приведет к игнорированию всех ошибок, включая те, которые являются подлинными и которые могут привести к неправильной работе видеозахвата. 
    Используйте этот параметр с осторожностью.'
  ),
'ZM_LD_PRELOAD' => array(
    'Help' => '
    Для некоторых старых камер требуется использование библиотеки совместимости v4l1. 
    Этот параметр позволяет задать путь к библиотеке, чтобы она могла быть загружена с помощью zmdc.pl перед запуском zmc.'
  ),
'ZM_V4L_MULTI_BUFFER' => array(
    'Help' => '
    Производительность при использовании устройств Video 4 Linux обычно лучше, если используется несколько буферов, позволяющих захватывать следующее изображение во время обработки предыдущего. 
    Если у вас есть несколько устройств на карте, совместно использующих один вход, который требует переключения, то такой подход иногда может привести к тому, что кадры из одного источника будут перепутаны с кадрами из другого. 
    Выключение этой опции предотвращает множественную буферизацию, что приводит к более медленному, но более стабильному захвату изображения. 
    Этот параметр игнорируется для нелокальных камер или если на чипе захвата присутствует только один вход. 
    Этот параметр решает проблему, аналогичную проблеме с параметром ZM_CAPTURES_PER_FRAME, и обычно вы должны изменять значение только одного из параметров одновременно. 
    Если у вас есть разные карты захвата, которым нужны разные значения, вы можете переопределить их в каждом отдельном мониторе на исходной странице.'
  ),
'ZM_CAPTURES_PER_FRAME' => array(
    'Help' => '
    Если вы используете камеры, подключенные к плате видеозахвата, которая вынуждает несколько входов использовать один чип захвата, иногда это может привести к получению изображений с перевернутыми чересстрочными кадрами, 
    что приведет к ухудшению качества изображения и появлению характерных гребенчатых краев. 
    Увеличение этого параметра позволяет принудительно выполнять дополнительные снимки изображения до того, как один из них будет выбран в качестве захваченного кадра. 
    Это позволяет аппаратному обеспечению захвата "успокоиться" и получать изображения более высокого качества ценой меньшей скорости захвата. 
    Этот параметр не влияет на (а) сетевые камеры или (б) там, где несколько входов не используют один и тот же чип захвата. 
    Этот параметр решает проблему, аналогичную проблеме с параметром \'ZM_V4L_MULTI_BUFFER\', и обычно вы должны изменять значение только одного из параметров одновременно. 
    Если у вас есть разные карты захвата, которым нужны разные значения, вы можете переопределить их в каждом отдельном мониторе на исходной странице.'
  ),
'ZM_FORCED_ALARM_SCORE' => array(
    'Help' => '
    Утилита \'zmu\' может использоваться для принудительной подачи сигнала тревоги на монитор, а не полагаться на алгоритмы обнаружения движения. 
    Этот параметр определяет, какую оценку присвоить этим сигналам тревоги, чтобы отличить их от обычных. 
    Оно должно быть 255 или меньше.'
  ),
'ZM_BULK_FRAME_INTERVAL' => array(
    'Help' => '
    Традиционно ZoneMinder записывает запись в таблицу базы данных кадров для каждого захваченного и сохраненного кадра. 
    Это хорошо работает в сценариях обнаружения движения, но в ситуации с видеорегистратором (режим "Запись обнаруженного движения" или "Запись монитора") это приводит к огромному количеству записей кадров и большой пропускной способности базы данных и 
    диска при очень небольшом количестве дополнительной информации. 
    Установка этого значения в ненулевое значение позволит ZoneMinder группировать эти кадры, не являющиеся сигналами тревоги, в одну "массовую" запись кадра, что значительно экономит полосу пропускания и пространство. 
    Единственным недостатком этого является то, что информация о времени для отдельных кадров теряется, но в ситуациях с постоянной частотой кадров это обычно не имеет значения. Этот параметр игнорируется в режиме Запись обнаруженного движения, 
    и отдельные кадры по-прежнему записываются, если тревога возникает и в режиме запись монитора.'
  ),
'ZM_EVENT_CLOSE_MODE' => array(
    'Help' => '
    Когда монитор работает в режиме непрерывной записи (запись или запись монитора), события обычно закрываются через фиксированный промежуток времени (длина раздела). 
    Однако в режиме Mocord возможно, что обнаружение движения может произойти ближе к концу секции. 
    Эта опция управляет тем, что происходит при возникновении аварийного сигнала в режиме запись монитора.
    Настройка "время" означает, что событие будет закрыто в конце раздела независимо от активности сигнала тревоги.
    Настройка "простой" означает, что событие будет закрыто в конце раздела, если в это время не происходит никаких действий по тревоге, в противном случае оно будет закрыто после окончания тревоги, 
    что означает, что событие может оказаться длиннее, чем обычная длина раздела.
    Настройка "тревога" означает, что если во время события возникнет тревога, событие будет закрыто и будет открыто новое. 
    Таким образом, события будут только тревожными или непрерывными. 
    Это приводит к ограничению количества сигналов тревоги до одного на событие, и события могут быть короче длины раздела, если произошел сигнал тревоги.'
  ),
'ZM_WEIGHTED_ALARM_CENTRES' => array(
    'Help' => '
    ZoneMinder всегда вычисляет центральную точку сигнала тревоги в зоне, чтобы дать некоторое представление о том, где он находится на экране. 
    Это может быть использовано экспериментальной функцией отслеживания движения или вашими собственными пользовательскими расширениями. 
    В режиме тревожных или отфильтрованных пикселей это простая средняя точка между экстентами обнаруженных пикселей. 
    Однако в методе больших двоичных объектов это может быть вычислено с использованием взвешенных местоположений пикселей, чтобы обеспечить более точное позиционирование для больших двоичных объектов неправильной формы. 
    Этот метод, хотя и более точный, также работает медленнее и поэтому по умолчанию отключен.'
  ),
'ZM_EVENT_IMAGE_DIGITS' => array(
    'Help' => '
    По мере захвата изображений событий они сохраняются в файловой системе с числовым индексом. 
    По умолчанию этот индекс состоит из трех цифр, поэтому числа начинаются с 001, 002 и т.д. 
    Это работает для большинства сценариев, поскольку события с более чем 999 кадрами редко фиксируются. 
    Однако, если у вас очень длинные события и вы используете внешние приложения, вы можете увеличить это значение, чтобы обеспечить правильную сортировку изображений в списках и т.д. 
    Предупреждение, увеличение этого значения в действующей системе может сделать существующие события недоступными для просмотра, поскольку событие будет сохранено с предыдущей схемой. 
    Уменьшение этого значения не должно иметь никаких негативных последствий.'
  ),
'ZM_DEFAULT_ASPECT_RATIO' => array(
    'Help' => '
    При указании размеров мониторов вы можете установить флажок, чтобы убедиться, что ширина остается в правильном соотношении к высоте, или наоборот. 
    Этот параметр позволяет указать, каким должно быть соотношение этих параметров. 
    Это должно быть указано в формате <значение ширины>:<значение высоты>, и обычно допустимо значение по умолчанию 4:3, но 11:9 - еще одна распространенная настройка. 
    Если флажок не установлен при указании размеров монитора, этот параметр не имеет никакого эффекта.'
  ),
'ZM_USER_SELF_EDIT' => array(
    'Help' => '
    Обычно только пользователи с правами системного редактирования могут изменять сведения о пользователях. 
    Включение этой опции позволяет обычным пользователям изменять свои пароли и языковые настройки'
  ),
'ZM_FONT_FILE_LOCATION' => array(
    'Help' => '
    Этот шрифт используется для меток времени.'
  ),
'ZM_WEB_NAVBAR_TYPE' => array(
    'Help' => '
    Выберите один из различных стилей панели навигации для веб-консоли. 
    В "обычном" стиле вверху есть меню, которое сворачивается в выпадающее меню на маленьких экранах. 
    Стиль "свернутый" постоянно сворачивается. Вместо меню вверху, доступ к пунктам меню осуществляется из выпадающего меню справа.'
  ),
'ZM_WEB_TITLE' => array(
    'Help' => '
    Если вы хотите, чтобы сайт идентифицировался как нечто отличное от ZoneMinder, измените это здесь. 
    Его можно использовать для более точной идентификации этой установки среди других.'
  ),
'ZM_WEB_TITLE_PREFIX' => array(
    'Help' => '
    Если у вас установлено несколько установок ZoneMinder, может быть полезно отображать разные заголовки для каждого из них. 
    Изменение этого параметра позволяет настроить заголовки окон таким образом, чтобы они включали дополнительную информацию, облегчающую идентификацию.'
  ),
'ZM_HOME_URL' => array(
    'Help' => '
    По умолчанию это приведет вас к zoneminder.com веб-сайт, но, возможно, вы предпочли бы, чтобы он привел вас куда-нибудь в другое место.'
  ),
'ZM_HOME_CONTENT' => array(
    'Help' => '
    Возможно, вы захотите установить это значение пустым, если вы используете css для размещения на нем фонового изображения.'
  ),
'ZM_HOME_ABOUT' => array(
    'Help' => '
    При включении логотип ZoneMinder ZoneMinder ZoneMinder в верхнем левом углу панели навигации превращается в меню со ссылками на: веб-сайт ZoneMinder, документацию ZoneMinder и форум ZoneMinder. 
    Конечные пользователи, желающие провести ребрендинг своей системы, могут отключить это, поскольку пункты меню в настоящее время жестко запрограммированы.'
  ),
'ZM_WEB_CONSOLE_BANNER' => array(
    'Help' => '
    Позволяет администратору размещать произвольное текстовое сообщение в верхней части веб-консоли. 
    Это полезно для разработчиков, чтобы отобразить сообщение, указывающее, что запущенный экземпляр ZoneMinder является моментальным снимком разработки, но его также можно использовать и для любых других целей.'
  ),
'ZM_WEB_EVENT_DISK_SPACE' => array(
    'Help' => '
    Добавляет еще один столбец в список событий, показывающий дисковое пространство, используемое событием. 
    Это приведет к небольшим накладным расходам, поскольку вызовет в каталоге событий. 
    На практике эти накладные расходы довольно малы, но могут быть заметны в системах с ограниченным вводом-выводом.'
  ),
'ZM_WEB_ID_ON_CONSOLE' => array(
    'Help' => '
    Некоторые считают полезным, чтобы идентификатор всегда был виден на консоли. 
    Этот параметр добавит столбец с его списком.'
  ),
'ZM_WEB_POPUP_ON_ALARM' => array(
    'Help' => '
    При просмотре прямой трансляции с монитора вы можете указать, хотите ли вы, чтобы окно выскакивало вперед, если возникает сигнал тревоги, когда окно свернуто или находится за другим окном. 
    Это наиболее полезно, если ваши мониторы расположены над дверями, например, когда они могут появиться, если кто-то подойдет к дверному проему.'
  ),
'ZM_WEB_SOUND_ON_ALARM' => array(
    'Help' => '
    При просмотре прямой трансляции с монитора вы можете указать, хотите ли вы, чтобы окно воспроизводило звук, предупреждающий вас о возникновении тревоги.'
  ),
'ZM_WEB_ALARM_SOUND' => array(
    'Help' => '
    Вы можете указать звуковой файл для воспроизведения, если во время просмотра прямой трансляции с монитора возникает сигнал тревоги. 
    До тех пор, пока ваш браузер понимает формат, он не обязательно должен быть какого-то определенного типа. 
    Этот файл должен быть помещен в каталог sounds, определенный ранее.'
  ),
'ZM_WEB_COMPACT_MONTAGE' => array(
    'Help' => '
    В режиме монтажа отображаются выходные данные всех ваших активных мониторов в одном окне. 
    Это включает в себя небольшое меню и информацию о состоянии для каждого из них. 
    Это может увеличить веб-трафик и сделать окно больше, чем хотелось бы. 
    Включение этой опции удаляет всю эту постороннюю информацию и просто отображает изображения.'
  ),
'ZM_WEB_EVENT_SORT_FIELD
' => array(
    'Help' => '
    События в списках могут быть изначально упорядочены любым удобным вам способом. 
    Этот параметр определяет, какое поле используется для их сортировки. 
    Вы можете изменить этот порядок с помощью фильтров или нажав на заголовки в самих списках. 
    Однако имейте в виду, что ссылки "Предыдущий" и "Следующий" при прокрутке событий относятся к порядку в списках и, следовательно, не всегда к порядку, основанному на времени.'
  ),
'ZM_WEB_EVENT_SORT_ORDER' => array(
    'Help' => '
    События в списках могут быть изначально упорядочены любым удобным вам способом. 
    Этот параметр определяет, в каком порядке (по возрастанию или по убыванию) они сортируются. 
    Вы можете изменить этот порядок с помощью фильтров или нажав на заголовки в самих списках. 
    Однако имейте в виду, что ссылки "Предыдущий" и "Следующий" при прокрутке событий относятся к порядку в списках и, следовательно, не всегда к порядку, основанному на времени.'
  ),
'ZM_WEB_EVENTS_PER_PAGE' => array(
    'Help' => '
    В представлении списка событий вы можете либо перечислить все события, либо только страницу за раз. 
    Этот параметр определяет, сколько событий перечисляется на странице в постраничном режиме и как часто следует повторять заголовки столбцов в нестраничном режиме.'
  ),
'ZM_WEB_LIST_THUMBS' => array(
    'Help' => '
    Обычно в списках событий отображаются только текстовые сведения о событиях, чтобы сэкономить место и время. 
    Включив эту опцию, вы также можете отображать небольшие миниатюры, которые помогут вам определить интересующие события. 
    Размер этих миниатюр регулируется следующими двумя параметрами.'
  ),
'ZM_WEB_LIST_THUMB_WIDTH' => array(
    'Help' => '
    Этот параметр определяет ширину уменьшенных изображений, которые отображаются в списках событий. 
    Он должен быть достаточно маленьким, чтобы вписаться в остальную часть стола. 
    Если вы предпочитаете, вы можете указать высоту вместо этого в следующем параметре, но вы должны использовать только один из параметров width или height, а другой параметр должен быть равен нулю. 
    Если указаны как ширина, так и высота, то будет использоваться ширина, а высота игнорироваться.'
  ),
'ZM_WEB_LIST_THUMB_HEIGHT' => array(
    'Help' => '
    Этот параметр определяет высоту уменьшенных изображений, которые отображаются в списках событий. 
    Он должен быть достаточно маленьким, чтобы вписаться в остальную часть стола. 
    Если вы предпочитаете, вы можете указать ширину вместо этого в предыдущем варианте, но вы должны использовать только один из параметров width или height, а другой параметр должен быть установлен на ноль. 
    Если указаны как ширина, так и высота, то будет использоваться ширина, а высота игнорироваться.'
  ),
'ZM_WEB_ANIMATE_THUMBS' => array(
    'Help' => '
    Включение этой опции приводит к увеличению статического эскиза, отображаемого на определенных видах, и отображению прямой трансляции при наведении курсора мыши на эскиз.'
  ),
'ZM_WEB_USE_OBJECT_TAGS' => array(
    'Help' => '
    Существует два способа включения медиаконтента на веб-страницы. 
    Наиболее распространенным способом является использование тега EMBED, который способен дать некоторое представление о типе контента. 
    Однако это не стандартная часть HTML. 
    Официальный метод заключается в использовании тегов OBJECT, которые способны предоставить больше информации, позволяя правильно просматривать медиа и т.д. быть загруженным. 
    Однако они поддерживаются менее широко, и контент может быть специально адаптирован к конкретной платформе или плееру. 
    Этот параметр определяет, будет ли медиаконтент заключен только в теги для встраивания или, при необходимости, он будет дополнительно заключен в теги OBJECT. 
    В настоящее время теги OBJECT используются только в ограниченном числе случаев, но в будущем они могут получить более широкое распространение. 
    Рекомендуется оставить эту опцию включенной, если только у вас не возникнут проблемы с воспроизведением какого-либо контента.'
  ),
'ZM_WEB_XFRAME_WARN' => array(
    'Help' => '
    При создании монитора веб-сайта, если для целевого веб-сайта в заголовке X-Frame-Options установлено значение sameorigin, сайт не будет отображаться в ZoneMinder. 
    Это особенность дизайна в большинстве современных браузеров. 
    При возникновении этого условия ZoneMinder запишет предупреждение в файл журнала. 
    Чтобы обойти это, можно установить плагин или расширение для браузера, чтобы игнорировать заголовки X-Frame, и тогда страница будет отображаться правильно. 
    После установки плагина или расширения конечный пользователь может отключить это предупреждение.'
  ),
'ZM_WEB_FILTER_SOURCE' => array(
    'Help' => '
    Этот параметр влияет только на мониторы с типом источника \'Ffmpeg\', \'Libvlc\' или \'веб-сайт\'. 
    Этот параметр определяет, какая информация отображается в столбце Источника на консоли. 
    Выбор "Нет" ничего не отфильтрует. Будет отображена вся исходная строка, которая может содержать конфиденциальную информацию. 
    Выбор "Без учетных данных" приведет к удалению имен пользователей и паролей из строки. 
    Если в строке есть какие-либо номера портов, и они являются общими (80, 554 и т.д.), То они также будут удалены. 
    При выборе \'Имя хоста\' будет отфильтрована вся информация, за исключением имени хоста или IP-адреса. 
    Если вы сомневаетесь, используйте "Имя хоста" по умолчанию. 
    Эта функция использует функцию php \'url_parts\' для идентификации различных частей URL-адреса. 
    Если рассматриваемый URL-адрес является необычным или каким-то образом нестандартным, то фильтрация может не дать желаемых результатов.'
  ),
'ZM_COLOUR_JPEG_FILES' => array(
    'Help' => '
    Камеры, осуществляющие съемку в оттенках серого, могут записывать захваченные изображения в файлы jpeg с соответствующим цветовым пространством в оттенках серого. 
    Это экономит небольшой объем дискового пространства по сравнению с цветными. 
    Однако некоторые инструменты, такие как ffmpeg, либо не работают с этим цветовым пространством, либо должны предварительно преобразовать его. 
    Установка этого параметра в значение yes занимает немного больше места, но значительно ускоряет создание файлов MPEG.'
  ),
'ZM_ADD_JPEG_COMMENTS' => array(
    'Help' => '
    Файлы JPEG могут содержать несколько дополнительных полей, добавленных в заголовок файла. 
    В поле комментария может быть добавлен любой текст. 
    Этот параметр позволяет дополнительно включить в качестве комментария к заголовку файла тот же текст, который используется для аннотирования изображения. 
    Если вы архивируете изображения событий в другие места, это может помочь вам найти изображения для определенных событий или времени, если вы используете программное обеспечение, которое может читать заголовки комментариев.'
  ),
'ZM_JPEG_FILE_QUALITY' => array(
    'Help' => '
    Когда ZoneMinder обнаружит событие, он сохранит изображения, связанные с этим событием, в файлах. 
    Эти файлы находятся в формате \'JPEG\' и могут быть просмотрены или переданы позже. 
    Этот параметр определяет, какое качество изображения должно использоваться для сохранения этих файлов. 
    Большее число означает лучшее качество, но меньшее сжатие, поэтому оно займет больше места на диске и займет больше времени для просмотра при медленном соединении. 
    В отличие от этого, низкое число означает меньшие по размеру и более быстрые для просмотра файлы, но по цене изображений более низкого качества. 
    Этот параметр применяется ко всем записанным изображениям, за исключением случаев, когда захваченное изображение вызвало сигнал тревоги, 
    а параметр качества файла сигнала тревоги установлен на более высокое значение, когда он используется вместо этого.'
  ),
'ZM_JPEG_ALARM_FILE_QUALITY' => array(
    'Help' => '
    Это значение эквивалентно обычному параметру качества файла \'jpeg\', указанному выше, за исключением того, что оно применяется только к изображениям, сохраненным в состоянии тревоги, 
    и то только в том случае, если для этого значения установлено значение более высокого качества, чем для обычного файла. 
    Если установлено меньшее значение, то оно игнорируется. 
    Таким образом, оставить значение \'по умолчанию\' равным \'0\' фактически означает использовать обычную настройку качества файла для всех сохраненных изображений. 
    Это делается для предотвращения случайного сохранения важных изображений с худшим качеством.'
  ),
'ZM_JPEG_STREAM_QUALITY' => array(
    'Help' => '
    При просмотре \'живого\' потока для монитора ZoneMinder извлекает изображение из буфера и кодирует его в формат \'JPEG\' перед отправкой. 
    Этот параметр определяет, какое качество изображения должно использоваться для кодирования этих изображений. 
    Большее число означает лучшее качество, но меньшее сжатие, поэтому просмотр при медленном соединении займет больше времени. 
    В отличие от этого, низкое количество означает более быстрый просмотр изображений, но по цене изображений более низкого качества. 
    Этот параметр не применяется при просмотре событий или неподвижных изображений, поскольку они обычно просто считываются с диска и поэтому будут закодированы с качеством, указанным в предыдущих параметрах.'
  ),
'ZM_MPEG_TIMED_FRAMES' => array(
    'Help' => '
    При использовании потокового видео на основе \'MPEG\', будь то для прямых трансляций или событий, ZoneMinder может отправлять потоки двумя способами. 
    Если выбран этот параметр, то временная метка для каждого кадра, взятая из времени его захвата, включается в поток. 
    Это означает, что там, где частота кадров меняется, например, вокруг сигнала тревоги, поток все равно будет поддерживать свое "реальное" время. 
    Если этот параметр не выбран, то вычисляется приблизительная частота кадров, которая вместо этого используется для планирования кадров. 
    Этот параметр следует выбрать, если только у вас не возникнут проблемы с предпочтительным методом потоковой передачи.'
  ),
'ZM_MPEG_LIVE_FORMAT' => array(
    'Help' => '
    При использовании режима \'MPEG\' ZoneMinder может выводить видео в реальном времени. 
    Однако то, какие форматы обрабатываются браузером, сильно различается на разных машинах. 
    Эта опция позволяет вам указать формат видео, используя формат расширения файла, поэтому вам просто нужно ввести расширение нужного вам типа файла, а остальное определяется исходя из этого. 
    Значение по умолчанию \'asf\' хорошо работает под \'Windows с Windows Media Player\', но в настоящее время я не уверен, что работает на платформе \'Linux\', если вообще работает. 
    Если вы что-нибудь узнаете, пожалуйста, дайте мне знать! 
    Если этот параметр оставить пустым, то прямые трансляции вернутся к формату \'motion jpeg\''
  ),
'ZM_MPEG_REPLAY_FORMAT' => array(
    'Help' => '
    При использовании режима \'MPEG\' ZoneMinder может воспроизводить события в формате закодированного видео. 
    Однако то, какие форматы обрабатываются браузером, сильно различается на разных машинах. 
    Эта опция позволяет вам указать формат видео, используя формат расширения файла, поэтому вам просто нужно ввести расширение нужного вам типа файла, а остальное определяется исходя из этого. 
    Значение по умолчанию \'asf\' хорошо работает под \'Windows\' с \'Windows Media Player\', а \'mpg\' или \'avi\' и т.д. 
    Должны работать под \'Linux\'. Если вы знаете еще что-нибудь, пожалуйста, дайте мне знать! 
    Если этот параметр оставить пустым, то прямые трансляции вернутся к формату \'motion jpeg\''
  ),
'ZM_RAND_STREAM' => array(
    'Help' => '
    Некоторые браузеры могут кэшировать потоки, используемые ZoneMinder. 
    Чтобы предотвратить это, к URL-адресу можно добавить безвредную случайную строку, чтобы каждый вызов потока выглядел уникальным.'
  ),
'ZM_OPT_CAMBOZOLA' => array(
    'Help' => '
    \'Cambozola\' - это удобный \'Java\'-апплет со вкусом нежирного сыра, который ZoneMinder использует для просмотра потоков изображений в браузерах, таких как \'Internet Explorer\', которые изначально не поддерживают этот формат. 
    Если вы используете этот браузер, настоятельно рекомендуется установить его с сайта проекта \'cambozola\'. 
    Однако, если он не установлен, все равно можно просматривать неподвижные изображения с более низкой частотой обновления.'
  ),
'ZM_PATH_CAMBOZOLA' => array(
    'Help' => '
    Cambozola - это удобный Java-апплет со вкусом нежирного сыра, который ZoneMinder использует для просмотра потоков изображений в браузерах, таких как Internet Explorer, которые изначально не поддерживают этот формат. 
    Если вы используете этот браузер, настоятельно рекомендуется установить его с сайта проекта cambozola. 
    Однако, если он не установлен, все равно можно просматривать неподвижные изображения с более низкой частотой обновления. 
    Оставьте это как \'cambozola.jar\' если cambozola установлена в том же каталоге, что и файлы веб-клиента ZoneMinder.'
  ),
'ZM_RELOAD_CAMBOZOLA' => array(
    'Help' => '
    Cambozola позволяет просматривать потоковый MJPEG, однако он кэширует весь поток в кэш-пространство на компьютере, 
    установка этого значения на число > 0 приведет к его автоматической перезагрузке через столько секунд, чтобы избежать заполнения жесткого диска.'
  ),
'ZM_OPT_FFMPEG' => array(
    'Help' => '
    ZoneMinder может дополнительно кодировать серию видеоизображений в видеофайл с кодировкой MPEG для просмотра, загрузки или хранения. 
    Этот параметр позволяет вам указать, установлены ли у вас инструменты ffmpeg. 
    Обратите внимание, что создание файлов MPEG может быть довольно трудоемким для процессора и диска и не является обязательным вариантом, поскольку события все равно можно просматривать как видеопотоки без него.'
  ),
'ZM_PATH_FFMPEG' => array(
    'Help' => '
   Этот путь должен указывать на то, где был установлен ffmpeg.
   Пожалуйста, обратите внимание, что это невозможно отредактировать через веб-интерфейс или API.
   Его можно изменить только через файлы .conf в /etc/zm/conf.d'
  ),
'ZM_FFMPEG_INPUT_OPTIONS' => array(
    'Help' => '
    Ffmpeg может использовать множество опций в командной строке для управления качеством создаваемого видео. 
    Этот параметр позволяет вам указать свой собственный набор, который применяется к вводу в ffmpeg (параметры, которые указаны перед параметром -i). 
    Ознакомьтесь с документацией ffmpeg для получения полного списка опций, которые могут быть использованы здесь.'
  ),
'ZM_FFMPEG_OUTPUT_OPTIONS' => array(
    'Help' => '
    Ffmpeg может использовать множество опций в командной строке для управления качеством создаваемого видео. 
    Этот параметр позволяет вам указать свой собственный набор, который применяется к выходным данным из ffmpeg (параметры, которые указаны после параметра -i). 
    Ознакомьтесь с документацией ffmpeg для получения полного списка опций, которые могут быть использованы здесь. 
    Наиболее распространенным из них часто является принудительное изменение частоты кадров на выходе, поддерживаемой видеокодером.'
  ),
'ZM_FFMPEG_FORMATS' => array(
    'Help' => '
    Ffmpeg может генерировать видео во многих различных форматах. 
    Этот параметр позволяет вам перечислить те, которые вы хотите иметь возможность выбрать. 
    Поскольку ffmpeg поддерживает новые форматы, вы можете добавить их сюда и сразу же использовать. 
    Добавление \'*\' после формата указывает, что это будет формат по умолчанию, используемый для веб-видео, добавление \'**\' определяет формат по умолчанию для телефонного видео.'
  ),
'ZM_FFMPEG_OPEN_TIMEOUT' => array(
    'Help' => '
    Когда Ffmpeg открывает поток, может пройти много времени, прежде чем произойдет сбой; 
    при определенных обстоятельствах даже кажется, что он может блокироваться на неопределенный срок. 
    Этот параметр позволяет вам установить максимальное время в секундах, которое должно пройти перед закрытием потока и попыткой его повторного открытия.'
  ),
'ZM_LOG_LEVEL_SYSLOG' => array(
    'Help' => '
    Ведение журнала ZoneMinder теперь более интегрировано между компонентами и позволяет вам указывать назначение выходных данных журнала и отдельные уровни для каждого из них. 
    Этот параметр позволяет вам управлять уровнем выходных данных журнала, которые поступают в системный журнал. 
    Двоичные файлы ZoneMinder всегда регистрировались в системном журнале, но теперь включены сценарии и веб-журналы. 
    Чтобы сохранить предыдущее поведение, вы должны убедиться, что для этого значения установлено значение Info или Warning. 
    Этот параметр определяет максимальный уровень ведения журнала, который будет записан, поэтому информация включает предупреждения, ошибки и т.д. 
    Чтобы полностью отключить, установите для этого параметра значение Нет. 
    Вам следует соблюдать осторожность при установке этого параметра в значение Debug, поскольку это может серьезно повлиять на производительность системы. 
    Если вы хотите отладить, вам также нужно будет установить уровень и компонент ниже'
  ),
'ZM_LOG_LEVEL_FILE' => array(
    'Help' => '
    Ведение журнала ZoneMinder теперь более интегрировано между компонентами и позволяет вам указывать назначение выходных данных журнала и отдельные уровни для каждого из них. 
    Этот параметр позволяет вам управлять уровнем выходных данных журнала, которые поступают в отдельные файлы журнала, записанные определенными компонентами. 
    Именно так раньше работало ведение журнала, и, хотя это было полезно для отслеживания проблем в определенных компонентах, это также приводило к появлению множества разрозненных файлов журнала. 
    Чтобы сохранить это поведение, вы должны убедиться, что для этого значения установлено значение Info или Warning. 
    Этот параметр определяет максимальный уровень ведения журнала, который будет записан, поэтому информация включает предупреждения, ошибки и т.д. 
    Чтобы полностью отключить, установите для этого параметра значение Нет. 
    Вам следует проявлять осторожность при установке этого параметра в значение Debug, поскольку он может серьезно повлиять на производительность системы, хотя вывод файла оказывает меньшее влияние, чем другие параметры. 
    Если вы хотите отладить, вам также нужно будет установить уровень и компонент ниже'
  ),
'ZM_LOG_LEVEL_WEBLOG' => array(
    'Help' => '
    Ведение журнала ZoneMinder теперь более интегрировано между компонентами и позволяет вам указывать назначение выходных данных журнала и отдельные уровни для каждого из них. 
    Этот параметр позволяет вам управлять уровнем вывода журнала из веб-интерфейса, который переходит в журнал ошибок httpd. 
    Обратите внимание, что включено только ведение веб-журнала из файлов PHP и JavaScript, поэтому эта опция действительно полезна только для расследования конкретных проблем с этими компонентами. 
    Этот параметр определяет максимальный уровень ведения журнала, который будет записан, поэтому информация включает предупреждения, ошибки и т.д. 
    Чтобы полностью отключить, установите для этого параметра значение Нет. 
    Вам следует соблюдать осторожность при установке этого параметра в значение Debug, поскольку это может серьезно повлиять на производительность системы. 
    Если вы хотите отладить, вам также нужно будет установить уровень и компонент ниже'
  ),
'ZM_LOG_LEVEL_DATABASE' => array(
    'Help' => '
    Ведение журнала ZoneMinder теперь более интегрировано между компонентами и позволяет вам указывать назначение выходных данных журнала и отдельные уровни для каждого из них. 
    Этот параметр позволяет управлять уровнем выходных данных журнала, записываемых в базу данных. 
    Это новая опция, которая может сделать просмотр выходных данных журнала более простым и интуитивно понятным, а также позволяет получить общее представление о том, как работает система. 
    Если у вас большая или очень загруженная система, то возможно, что использование этой опции может замедлить работу вашей системы, если таблица станет очень большой. 
    Убедитесь, что вы используете параметр LOG_DATABASE_LIMIT, чтобы сохранить управляемый размер таблицы. 
    Этот параметр определяет максимальный уровень ведения журнала, который будет записан, поэтому информация включает предупреждения, ошибки и т.д. 
    Чтобы полностью отключить, установите для этого параметра значение Нет. 
    Вам следует соблюдать осторожность при установке этого параметра в значение Debug, поскольку это может серьезно повлиять на производительность системы. 
    Если вы хотите отладить, вам также нужно будет установить уровень и компонент ниже'
  ),
'ZM_LOG_DATABASE_LIMIT' => array(
    'Help' => '
    Если вы используете ведение журнала в базе данных, то можно быстро создать большое количество записей в таблице журналов. 
    Этот параметр позволяет указать, сколько из этих записей будет сохранено. 
    Если для этого параметра задано значение, большее нуля, то это число используется для определения максимального количества строк, меньшее или равное нулю указывает на отсутствие ограничения и не рекомендуется. 
    Вы также можете установить для этого значения значения времени, такие как "<n> день", что ограничит записи журнала более новыми, чем это время. 
    Вы можете указать "час", "день", "неделю", "месяц" и "год", обратите внимание, что значения должны быть единственными (без "s" в конце). 
    Таблица журналов периодически обрезается, так что за это время возможно кратковременное присутствие большего количества строк, чем ожидаемое.'
  ),
'ZM_LOG_FFMPEG' => array(
    'Help' => '
    Если эта опция включена (по умолчанию включена), эта опция будет регистрировать сообщения FFMPEG. 
    Сообщения FFMPEG могут быть полезны при отладке проблем с потоковой передачей. 
    Однако, в зависимости от вашего дистрибутива и версии FFMPEG, это также может привести к большему количеству журналов, чем вы обычно хотели бы видеть. 
    Если все ваши потоки работают хорошо, вы можете отключить это.'
  ),
'ZM_LOG_DEBUG' => array(
    'Help' => '
    Компоненты ZoneMinder обычно поддерживают ведение журнала отладки, доступного для помощи в диагностике проблем. 
    Двоичные компоненты имеют несколько уровней отладки, в то время как большинство других компонентов имеют только один. 
    Обычно это отключено, чтобы минимизировать потери производительности и избежать слишком быстрого заполнения журналов. 
    Этот параметр позволяет включить другие параметры, позволяющие настроить вывод дополнительной отладочной информации. 
    Компоненты получат эту инструкцию при перезапуске.'
  ),
'ZM_LOG_DEBUG_TARGET' => array(
    'Help' => '
    Доступны три области отладки. 
    Если оставить этот параметр пустым, это означает, что все компоненты будут использовать дополнительную отладку (не рекомендуется). 
    Установка этого параметра в значение \'_<компонент>\', например _zmc, ограничит дополнительную отладку только этим компонентом. 
    Установка этого параметра в значение \'_<component>_<identity>\', например \'_zmc_m1\' ограничит дополнительную отладку только этим экземпляром компонента. 
    Обычно это то, что вы, вероятно, хотите сделать. Для отладки скриптов используйте их имена без расширения .pl, например \'_zmvideo\', а для отладки проблем с веб-интерфейсом используйте \'_web\'. 
    Вы можете указать несколько целевых объектов, разделив их символами \'|\'.'
  ),
'ZM_LOG_DEBUG_LEVEL' => array(
    'Help' => '
    Доступно 9 уровней отладки, причем более высокие числа означают больше отладки, а уровень 0 - отсутствие отладки. 
    Однако не все уровни используются всеми компонентами. 
    Кроме того, если есть отладка на высоком уровне, обычно она, скорее всего, будет выводиться с такой громкостью, что это может помешать нормальной работе. 
    По этой причине вы должны тщательно и осторожно устанавливать уровень до тех пор, пока не будет достигнута желаемая степень отладки. 
    Скрипты и веб-интерфейс имеют только один уровень, поэтому для них это опция типа включения / выключения.'
  ),
'ZM_LOG_DEBUG_FILE' => array(
    'Help' => '
    Этот параметр позволяет указать другую цель для вывода отладки. 
    Все компоненты имеют файл журнала по умолчанию, который обычно находится в \'/tmp\' или \'/var/log\', и именно туда будет записана отладка, если это значение пустое. 
    Добавление пути сюда временно перенаправит отладку и другие выходные данные журнала в этот файл. 
    Этот параметр представляет собой простое имя файла, и вы отлаживаете несколько компонентов, после чего все они будут пытаться выполнить запись в один и тот же файл с нежелательными последствиями. 
    Добавление \'+\' к имени файла приведет к созданию файла с суффиксом \'.<pid>\', содержащим ваш идентификатор процесса. 
    Таким образом, отладка при каждом запуске компонента сохраняется отдельно. 
    Это рекомендуемый параметр, поскольку он также предотвратит перезапись того же журнала при последующих запусках. 
    Вы должны убедиться, что разрешения настроены таким образом, чтобы разрешить запись в файл и каталог, указанные здесь.'
  ),
'ZM_LOG_CHECK_PERIOD' => array(
    'Help' => '
    Когда ZoneMinder регистрирует события в базе данных, он может ретроспективно проанализировать количество возникших предупреждений и ошибок, чтобы рассчитать общее состояние работоспособности системы. 
    Этот параметр позволяет указать, какой период исторических событий используется в этом расчете. 
    Это значение выражается в секундах и игнорируется, если для \'LOG_LEVEL_DATABASE\' установлено значение \'None\'.'
  ),
'ZM_LOG_ALERT_WAR_COUNT' => array(
    'Help' => '
    Когда ZoneMinder регистрирует события в базе данных, он может ретроспективно проанализировать количество возникших предупреждений и ошибок, чтобы рассчитать общее состояние работоспособности системы. 
    Этот параметр позволяет указать, сколько предупреждений должно произойти в течение определенного периода времени, чтобы сгенерировать общее состояние предупреждения системы. 
    Значение, равное нулю, означает, что предупреждения не учитываются. 
    Это значение игнорируется, если для \'LOG_LEVEL_DATABASE\' установлено значение \'None\'.'
  ),
'ZM_LOG_ALERT_ERR_COUNT' => array(
    'Help' => '
    Когда ZoneMinder регистрирует события в базе данных, он может ретроспективно проанализировать количество возникших предупреждений и ошибок, чтобы рассчитать общее состояние работоспособности системы. 
    Этот параметр позволяет указать, сколько ошибок должно произойти в течение определенного периода времени, чтобы сгенерировать общее состояние предупреждения системы. 
    Нулевое значение означает, что ошибки не учитываются. 
    Это значение игнорируется, если для \'LOG_LEVEL_DATABASE\' установлено значение \'None\'.'
  ),
'ZM_LOG_ALERT_FAT_COUNT' => array(
    'Help' => '
    Когда ZoneMinder регистрирует события в базе данных, он может ретроспективно проанализировать количество возникших предупреждений и ошибок, чтобы рассчитать общее состояние работоспособности системы. 
    Этот параметр позволяет указать, сколько фатальных ошибок (включая панику) должно произойти в течение определенного периода времени, чтобы сгенерировать общее состояние предупреждения системы. 
    Нулевое значение означает, что фатальные ошибки не учитываются. 
    Это значение игнорируется, если для \'LOG_LEVEL_DATABASE\' установлено значение \'None\'.'
  ),
'ZM_LOG_ALARM_WAR_COUNT' => array(
    'Help' => '
    Когда ZoneMinder регистрирует события в базе данных, он может ретроспективно проанализировать количество возникших предупреждений и ошибок, чтобы рассчитать общее состояние работоспособности системы. 
    Этот параметр позволяет указать, сколько предупреждений должно произойти в течение определенного периода времени, чтобы сгенерировать общее состояние тревоги системы. 
    Значение, равное нулю, означает, что предупреждения не учитываются. 
    Это значение игнорируется, если для \'LOG_LEVEL_DATABASE\' установлено значение \'None\'.'
  ),
'ZM_LOG_ALARM_ERR_COUNT' => array(
    'Help' => '
    Когда ZoneMinder регистрирует события в базе данных, он может ретроспективно проанализировать количество возникших предупреждений и ошибок, чтобы рассчитать общее состояние работоспособности системы. 
    Этот параметр позволяет указать, сколько ошибок должно произойти в течение определенного периода времени, чтобы сгенерировать общее состояние тревоги системы. 
    Нулевое значение означает, что ошибки не учитываются. 
    Это значение игнорируется, если для \'LOG_LEVEL_DATABASE\' установлено значение \'None\'.'
  ),
'ZM_LOG_ALARM_FAT_COUNT' => array(
    'Help' => '
    Когда ZoneMinder регистрирует события в базе данных, он может ретроспективно проанализировать количество возникших предупреждений и ошибок, чтобы рассчитать общее состояние работоспособности системы. 
    Этот параметр позволяет указать, сколько фатальных ошибок (включая панику) должно произойти в течение определенного периода времени, чтобы сгенерировать общее состояние тревоги системы. 
    Нулевое значение означает, что фатальные ошибки не учитываются. 
    Это значение игнорируется, если для \'LOG_LEVEL_DATABASE\' установлено значение \'None\'.'
  ),
'ZM_RECORD_EVENT_STATS' => array(
    'Help' => '
    Эта версия ZoneMinder записывает подробную информацию о событиях в таблицу статистики. Это может помочь в определении оптимальных настроек для зон, хотя в настоящее время это сложно. 
    Однако в будущих версиях это будет сделано более легко и интуитивно, особенно с большой выборкой событий. 
    Опция по умолчанию "да" позволяет собирать эту информацию сейчас в готовности к этому, 
    но если вы беспокоитесь о производительности, вы можете отключить эту опцию, и в этом случае информация о статистике сохраняться не будет.'
  ),
'ZM_RECORD_DIAG_IMAGES' => array(
    'Help' => '
    В дополнение к записи статистики событий вы также можете записывать промежуточные диагностические изображения, которые отображают результаты различных проверок и обработки, которые происходят при попытке определить, имело ли место тревожное событие. 
    Существует несколько таких изображений, генерируемых для каждого кадра и зоны для каждого кадра тревоги или предупреждения, так что это может оказать огромное влияние на производительность. 
    Включайте этот параметр только для целей отладки или анализа и не забудьте снова отключить его, как только он больше не понадобится.'
  ),
'ZM_DUMP_CORES' => array(
    'Help' => '
    Когда в двоичном процессе ZoneMinder возникает неустранимая ошибка, традиционно происходит перехват, и детали записываются в журналы, чтобы помочь в удаленном анализе. 
    Однако в некоторых случаях диагностировать ошибку проще, если создается основной файл, представляющий собой дамп памяти процесса на момент возникновения ошибки. 
    Это может быть интерактивно проанализировано в отладчике и может выявить больше или лучшую информацию, чем та, которая доступна из журналов. 
    Этот параметр рекомендуется использовать только для опытных пользователей, в противном случае оставьте значение по умолчанию. 
    Примечание. использование этой опции для запуска основных файлов будет означать, что в двоичных журналах не будет никаких указаний на то, что процесс умер, они просто остановятся, однако журнал zmdc все равно будет содержать запись. 
    Также обратите внимание, что вам, возможно, придется явно включить создание основного файла в вашей системе с помощью команды \'ulimit -c\' или другими средствами, в противном случае файл не будет создан независимо от значения этого параметра.'
  ),
'ZM_RECORD_DIAG_IMAGES_FIFO' => array(
    'Help' => '
    Это пытается уменьшить нагрузку на запись изображений diag, отправляя их в канал FIFO памяти вместо создания каждого файла.'
  ),
'ZM_HTTP_VERSION' => array(
    'Help' => '
    ZoneMinder может взаимодействовать с сетевыми камерами, используя любой из стандартов HTTP/1.1 или HTTP/1.0. 
    Сервер обычно возвращается к версии, которую он поддерживает, без проблем, поэтому обычно это значение следует оставить по умолчанию. 
    Однако он может быть изменен на HTTP/1.0, если это необходимо для решения конкретных проблем.'
  ),
'ZM_HTTP_UA' => array(
    'Help' => '
    Когда ZoneMinder взаимодействует с удаленными камерами, он идентифицирует себя с помощью этой строки и номера версии. 
    Обычно этого достаточно, однако, если конкретная камера ожидает взаимодействия только с определенными браузерами, то это можно изменить на другую строку, идентифицирующую ZoneMinder как Internet Explorer или Netscape и т.д.'
  ),
'ZM_HTTP_TIMEOUT' => array(
    'Help' => '
    При получении удаленных изображений ZoneMinder будет ждать этот промежуток времени, прежде чем решить, что изображение не будет получено, и предпринять шаги для повторной попытки. 
    Этот тайм-аут измеряется в миллисекундах (1000 в секунду) и будет применяться к каждой части изображения, если оно не отправляется одним целым фрагментом.'
  ),
'ZM_MIN_STREAMING_PORT' => array(
    'Help' => '
    Из-за того, что браузеры хотят открывать только 6 подключений, если у вас более 6 мониторов, у вас могут возникнуть проблемы с просмотром более 6. 
    Этот параметр определяет начало диапазона портов, который будет использоваться для связи с ZM on. 
    Каждый монитор будет использовать это значение плюс идентификатор монитора для потоковой передачи контента. 
    Таким образом, значение 2000 здесь приведет к тому, что поток для монитора 1 попадет на порт 2001. 
    Пожалуйста, убедитесь, что вы правильно настроили apache для ответа на эти порты.'
  ),
'ZM_MIN_RTSP_PORT' => array(
    'Help' => '
    Начало диапазона портов, который будет использоваться для обеспечения потоковой передачи RTSP захваченного видео в реальном времени. 
    Каждый монитор будет использовать это значение плюс идентификатор монитора для потоковой передачи контента. 
    Таким образом, значение 2000 здесь приведет к тому, что поток для монитора 1 попадет на порт 2001.'
  ),
'ZM_MIN_RTP_PORT' => array(
    'Help' => '
    Когда ZoneMinder взаимодействует с камерами, поддерживающими MPEG 4, используя RTSP с помощью метода одноадресной рассылки, он должен открывать порты для обратного подключения камеры для целей управления и потоковой передачи. 
    Этот параметр определяет минимальный номер порта, который будет использовать ZoneMinder. Обычно для каждой камеры используются два соседних порта, один для пакетов управления и один для пакетов данных. 
    Этот порт должен быть установлен на четное число, вам также может потребоваться открыть дыру в вашем брандмауэре, чтобы камеры могли подключаться обратно, если вы хотите использовать одноадресную рассылку.'
  ),
'ZM_MAX_RTP_PORT' => array(
    'Help' => '
    Когда ZoneMinder взаимодействует с камерами, поддерживающими MPEG 4, используя RTSP с помощью метода одноадресной рассылки, он должен открывать порты для обратного подключения камеры для целей управления и потоковой передачи. 
    Этот параметр определяет максимальный номер порта, который будет использовать ZoneMinder.
    Обычно для каждой камеры используются два соседних порта, один для пакетов управления и один для пакетов данных. 
    Этот порт должен быть установлен на четное число, вам также может потребоваться открыть дыру в вашем брандмауэре, чтобы камеры могли подключаться обратно, если вы хотите использовать одноадресную рассылку. 
    Вы также должны убедиться, что вы открыли по крайней мере два порта для каждого монитора, который будет подключаться к одноадресным сетевым камерам.'
  ),
'ZM_OPT_EMAIL' => array(
    'Help' => '
    В ZoneMinder вы можете создавать фильтры событий, которые определяют, должны ли события, соответствующие определенным критериям, отправлять вам свои сведения по электронной почте на указанный адрес электронной почты. 
    Это позволит вам получать уведомления о событиях, как только они происходят, а также быстро просматривать события напрямую. 
    Этот параметр указывает, должна ли быть доступна эта функция. 
    Электронное письмо, созданное с помощью этой опции, может быть любого размера и предназначено для отправки на обычное устройство чтения электронной почты, а не на мобильное устройство.'
  ),
'ZM_OPT_MESSAGE' => array(
    'Help' => '
    В ZoneMinder вы можете создавать фильтры событий, которые определяют, должны ли события, соответствующие определенным критериям, отправлять вам свои сведения на указанный адрес электронной почты с коротким сообщением. 
    Это позволит вам получать уведомления о событиях, как только они произойдут. 
    Этот параметр указывает, должна ли быть доступна эта функция. 
    Электронное письмо, созданное с помощью этой опции, будет кратким и предназначено для отправки на шлюз SMS или минимальное средство чтения почты, такое как мобильное устройство или телефон, а не на обычное средство чтения электронной почты.'
  ),
'ZM_MESSAGE_ADDRESS' => array(
    'Help' => '
    Этот параметр используется для определения адреса электронной почты с коротким сообщением, на который будут отправляться любые события, соответствующие соответствующим фильтрам.'
  ),
'ZM_MESSAGE_SUBJECT' => array(
    'Help' => '
    Этот параметр используется для определения темы сообщения, отправляемого для любых событий, соответствующих соответствующим фильтрам.'
  ),
'ZM_MESSAGE_BODY' => array(
    'Help' => '
    Этот параметр используется для определения содержимого сообщения, отправляемого для любых событий, соответствующих соответствующим фильтрам.'
  ),
'ZM_NEW_MAIL_MODULES' => array(
    'Help' => '
    Традиционно ZoneMinder использовал модуль MIME::Entity perl для создания и отправки электронных писем и сообщений с уведомлениями. 
    Некоторые люди сообщали о проблемах, связанных с тем, что этот модуль вообще отсутствует или недостаточно гибок для их нужд. 
    Если вы один из таких людей, эта опция позволяет вам выбрать новый способ рассылки с помощью MIME::Lite и Net::SMTP вместо этого. 
    Этот метод был предложен Россом Мелином и должен работать для всех, но не был тщательно протестирован, поэтому в настоящее время он не выбран по умолчанию.'
  ),
'ZM_EMAIL_HOST' => array(
    'Help' => '
    Если вы выбрали SMTP в качестве метода отправки уведомлений по электронной почте или сообщений, то этот параметр позволяет вам выбрать, какой SMTP-сервер использовать для их отправки. 
    Значение localhost по умолчанию может работать, если у вас запущен sendmail, exim или аналогичный демон, однако вы можете указать здесь почтовый сервер SMTP вашего интернет-провайдера.'
  ),
'ZM_FROM_EMAIL' => array(
    'Help' => '
    Электронные письма или сообщения, которые будут отправлены вам с информацией о событиях, могут быть отправлены с указанного адреса электронной почты, чтобы помочь вам с фильтрацией почты и т.д. 
    Адрес чего-то вроде ZoneMinder\@your.domain рекомендуется.'
  ),
'ZM_URL' => array(
    'Help' => '
    Электронные письма или сообщения, которые будут отправлены вам с информацией о событиях, могут содержать ссылку на сами события для удобства просмотра. 
    Если вы собираетесь использовать эту функцию, то установите этот параметр для URL-адреса вашей установки, как он будет отображаться с того места, где вы читаете свое электронное письмо, например http://host.your.domain/zm.php .'
  ),
'ZM_SSMTP_MAIL' => array(
    'Help' => '
    \'SSMTP\' - это легкий и эффективный способ отправки электронной почты. 
    Приложение SSMTP по умолчанию не установлено. \'NEW_MAIL_MODULES\' также должен быть включен. 
    Пожалуйста, посетите вики-страницу ZoneMinder \'SSMTP\' для получения справки по настройке и настройке.'
  ),
'ZM_SSMTP_PATH' => array(
    'Help' => '
    Рекомендуем указать путь к приложению \'SMTP\'. Если путь не определен. 
    Zoneminder попытается определить путь с помощью команды командной оболочки. 
    Пример пути: \'/usr/sbin/ssmtp\'.'
  ),
'ZM_OPT_UPLOAD' => array(
    'Help' => '
    В ZoneMinder вы можете создавать фильтры событий, которые определяют, следует ли загружать события, соответствующие определенным критериям, на удаленный сервер для архивирования. 
    Этот параметр указывает, должна ли быть доступна эта функция'
  ),
'ZM_UPLOAD_ARCH_FORMAT' => array(
    'Help' => '
    Загруженные события могут храниться в формате .tar или .zip, этот параметр определяет, в каком формате. 
    Обратите внимание, что для его использования вам необходимо установить модули Archive::Tar и/или Archive::Zip perl.'
  ),
'ZM_UPLOAD_ARCH_COMPRESS' => array(
    'Help' => '
    Когда архивные файлы создаются, их можно сжать. 
    Однако в целом, поскольку изображения уже сжаты, это экономит лишь минимальное количество места по сравнению с использованием большего количества процессора при их создании. 
    Включайте только в том случае, если у вас много ресурсов процессора и ограничено дисковое пространство на удаленном сервере или пропускная способность.'
  ),
'ZM_UPLOAD_ARCH_ANALYSE' => array(
    'Help' => '
    При создании архивных файлов они могут содержать либо только захваченные кадры, либо оба захваченных кадра, а для кадров, вызвавших тревогу, проанализированное изображение с выделенной измененной областью. Этот параметр управляет включением файлов. 
    Включайте проанализированные кадры только в том случае, если у вас высокоскоростное подключение к удаленному серверу или если вам нужна помощь в выяснении того, что вызвало тревогу в первую очередь, поскольку архивы с этими файлами могут быть значительно больше.'
  ),
'ZM_UPLOAD_PROTOCOL' => array(
    'Help' => '
    ZoneMinder может загружать события на удаленный сервер с помощью FTP или SFTP. Обычный FTP широко поддерживается, но не обязательно очень безопасен, в то время как SFTP (Secure FTP) работает по ssh-соединению и поэтому зашифрован и использует обычные ssh-порты. 
    Обратите внимание, что для его использования вам потребуется установить соответствующий модуль perl, либо Net::FTP, либо Net::SFTP, в зависимости от вашего выбора.'
  ),
'ZM_UPLOAD_HOST' => array(
    'Help' => '
    Вы можете использовать фильтры, чтобы указать ZoneMinder загружать события на удаленный сервер. 
    Этот параметр указывает имя или IP-адрес используемого сервера.'
  ),
'ZM_UPLOAD_PORT' => array(
    'Help' => '
    Вы можете использовать фильтры, чтобы указать ZoneMinder загружать события на удаленный сервер. 
    Если вы используете протокол SFTP, то эта опция позволяет вам указать конкретный порт, который будет использоваться для подключения. 
    Если этот параметр оставлен пустым, то используется порт 22 по умолчанию. 
    Этот параметр игнорируется при загрузке по FTP.'
  ),
'ZM_UPLOAD_USER' => array(
    'Help' => '
    Вы можете использовать фильтры, чтобы указать ZoneMinder загружать события на удаленный сервер. 
    Этот параметр указывает имя пользователя, которое ZoneMinder должен использовать для входа в систему для передачи.'
  ),
'ZM_UPLOAD_PASS' => array(
    'Help' => '
    Вы можете использовать фильтры, чтобы указать ZoneMinder загружать события на удаленный сервер. 
    Этот параметр указывает пароль, который ZoneMinder должен использовать для входа в систему для передачи. 
    Если вы используете учетные записи на основе сертификатов для SFTP-серверов, вы можете оставить этот параметр пустым.'
  ),
'ZM_UPLOAD_LOC_DIR' => array(
    'Help' => '
    Вы можете использовать фильтры, чтобы указать ZoneMinder загружать события на удаленный сервер.  
    Этот параметр указывает локальный каталог, который ZoneMinder должен использовать для временной загрузки файлов. 
    Это файлы, которые создаются на основе событий, загружаются, а затем удаляются.'
  ),
'ZM_UPLOAD_REM_DIR' => array(
    'Help' => '
    Вы можете использовать фильтры, чтобы указать ZoneMinder загружать события на удаленный сервер. 
    Этот параметр указывает удаленный каталог, который ZoneMinder должен использовать для загрузки файлов событий.'
  ),
'ZM_UPLOAD_TIMEOUT' => array(
    'Help' => '
    Вы можете использовать фильтры, чтобы указать ZoneMinder загружать события на удаленный сервер. 
    Этот параметр указывает максимальное время ожидания бездействия (в секундах), которое должно быть допустимо до того, 
    как ZoneMinder определит, что передача завершилась неудачно, и закроет соединение.'
  ),
'ZM_UPLOAD_STRICT' => array(
    'Help' => '
    Вы можете потребовать загрузки SFTP для проверки ключа хоста удаленного сервера для защиты от атак типа "человек посередине". 
    Вам нужно будет добавить ключ сервера в файл known_hosts. 
    В большинстве систем это будет ~/.ssh/known_hosts, где ~ - это домашний каталог веб-сервера, на котором работает ZoneMinder.'
  ),
'ZM_UPLOAD_FTP_PASSIVE' => array(
    'Help' => '
    Если ваш компьютер находится за брандмауэром или прокси-сервером, вам может потребоваться перевести FTP в пассивный режим. 
    На самом деле для простых переводов в любом случае нет особого смысла поступать иначе, но вы можете установить для этого значение "Нет", если хотите.'
  ),
'ZM_UPLOAD_DEBUG' => array(
    'Help' => '
    Вы можете использовать фильтры, чтобы указать ZoneMinder загружать события на удаленный сервер. 
    Если у вас возникли (или ожидаются) проблемы с загрузкой событий, 
    то установка этого параметра в значение "да" позволяет базовым модулям передачи генерировать дополнительную информацию и включать ее в журналы.'
  ),
'ZM_OPT_X10' => array(
    'Help' => '
    Если у вас дома установлена система домашней автоматизации X10, вы можете использовать ZoneMinder для инициирования или реагирования на сигналы X10, 
    если на вашем компьютере установлен соответствующий контроллер интерфейса. 
    Этот параметр указывает, будут ли параметры X10 доступны в клиенте браузера.'
  ),
'ZM_X10_DEVICE' => array(
    'Help' => '
    Если к вашему компьютеру подключено устройство контроллера X10 (например, XM10U), в этом параметре указывается, к какому порту оно подключено. 
    По умолчанию /dev/ttyS0 соответствует последовательному или com-порту 1.'
  ),
'ZM_X10_HOUSE_CODE' => array(
    'Help' => '
    Устройства X10 группируются вместе, идентифицируя их как принадлежащие одному Домашнему коду. 
    Этот параметр подробно описывает, что это такое. 
    Это должна быть одна буква между A и P.'
  ),
'ZM_X10_DB_RELOAD_INTERVAL' => array(
    'Help' => '
    Демон zmx10 периодически проверяет базу данных, чтобы выяснить, какие события X10 вызывают или являются результатом аварийных сигналов. 
    Этот параметр определяет, как часто выполняется эта проверка, если вы не меняете эту область часто, это может быть довольно большое значение.'
  ),
'ZM_WEB_H_REFRESH_MAIN' => array(
    'Help' => '
    В главном окне консоли отображается общее состояние и итоговые данные по событиям для всех мониторов. 
    Это нетривиальная задача, и ее не следует повторять слишком часто, иначе это может повлиять на производительность остальной части системы.'
  ),
'ZM_WEB_H_REFRESH_NAVBAR' => array(
    'Help' => '
    Заголовок навигации содержит общую информацию о состоянии загрузки сервера и пространстве для хранения.'
  ),
'ZM_WEB_H_REFRESH_CYCLE' => array(
    'Help' => '
    Окно просмотра цикла - это метод непрерывного переключения между изображениями со всех ваших мониторов. 
    Этот параметр определяет, как часто обновлять новое изображение.'
  ),
'ZM_WEB_H_REFRESH_IMAGE' => array(
    'Help' => '
    Изображения в реальном времени с монитора можно просматривать как в потоковом режиме, так и в режиме фотосъемки. 
    Этот параметр определяет, как часто обновляется неподвижное изображение, он не влияет, если выбран параметр потоковой передачи.'
  ),
'ZM_WEB_H_REFRESH_STATUS' => array(
    'Help' => '
    Окно монитора на самом деле состоит из нескольких кадров. 
    Тот, что посередине, просто содержит статус монитора, который необходимо обновлять довольно часто, чтобы дать верное представление. 
    Этот параметр определяет эту частоту.'
  ),
'ZM_WEB_H_REFRESH_EVENTS' => array(
    'Help' => '
    Окно монитора на самом деле состоит из нескольких кадров. 
    Нижняя рамка содержит список последних нескольких событий для легкого доступа. 
    Этот параметр определяет, как часто он обновляется.'
  ),
'ZM_WEB_H_CAN_STREAM' => array(
    'Help' => '
    Если вы знаете, что ваш браузер может обрабатывать потоки изображений типа "multipart/x-mixed-replace", 
    но ZoneMinder не определяет это правильно, вы можете установить этот параметр, чтобы гарантировать, что поток доставляется с использованием плагина Cambozola или без него. 
    Выбор "да" сообщит ZoneMinder, что ваш браузер может обрабатывать потоки изначально, "нет" означает, 
    что он не может, и поэтому будет использоваться плагин, в то время как "авто" позволяет ZoneMinder решать.'
  ),
'ZM_WEB_H_STREAM_METHOD' => array(
    'Help' => '
    ZoneMinder может быть настроен на использование либо видео в кодировке mpeg, либо серии неподвижных изображений в формате jpeg при отправке видеопотоков. 
    Этот параметр определяет, какой из них используется. 
    Если вы выберете mpeg, вы должны убедиться, что в вашем браузере доступны соответствующие плагины, 
    тогда как выбор jpeg будет работать изначально в Mozilla и связанных браузерах, а также с Java-апплетом в Internet Explorer.'
  ),
'ZM_WEB_H_DEFAULT_SCALE' => array(
    'Help' => '
    Обычно ZoneMinder отображает потоки "прямой эфир" или "событие" в их собственном размере. 
    Однако, если у вас мониторы с большими размерами или медленной связью, вы можете предпочесть уменьшить этот размер, в качестве альтернативы для небольших мониторов вы можете увеличить его. 
    Этот параметр позволяет указать, каким будет коэффициент масштабирования по умолчанию. 
    Он выражается в процентах, поэтому 100 - это нормальный размер, 200 - двойной размер и т.д.'
  ),
'ZM_WEB_H_DEFAULT_RATE' => array(
    'Help' => '
    Обычно ZoneMinder отображает потоки "событий" с их собственной скоростью, то есть как можно ближе к реальному времени. 
    Однако, если у вас есть длинные события, часто бывает удобно воспроизвести их в более быстром темпе для просмотра. 
    Этот параметр позволяет вам указать, какой будет частота воспроизведения по умолчанию. 
    Она выражается в процентах, поэтому 100 - это нормальная скорость, 200 - двойная скорость и т.д.'
  ),
'ZM_WEB_H_VIDEO_BITRATE' => array(
    'Help' => '
    При кодировании реального видео с помощью библиотеки ffmpeg может быть задана скорость передачи данных, которая примерно соответствует доступной полосе пропускания, используемой для потока. 
    Этот параметр фактически соответствует параметру "качество" для видео. 
    Низкое значение приведет к блочному изображению, в то время как высокое значение обеспечит более четкое изображение. 
    Обратите внимание, что этот параметр не управляет частотой кадров видео, однако на качество создаваемого видео влияет как этот параметр, так и частота кадров, с которой создается видео. 
    Более высокая частота кадров при определенной скорости передачи данных приводит к тому, что отдельные кадры имеют более низкое качество.'
  ),
'ZM_WEB_H_VIDEO_MAXFPS' => array(
    'Help' => '
    При использовании потокового видео основным элементом управления является битрейт, который определяет, сколько данных может быть передано. 
    Однако более низкий битрейт при высокой частоте кадров приводит к снижению качества изображения. Эта опция позволяет ограничить максимальную частоту кадров, чтобы обеспечить сохранение качества видео. 
    Дополнительным преимуществом является то, что кодирование видео с высокой частотой кадров является трудоемкой задачей для процессора, когда по большей части очень высокая частота кадров дает мало заметных улучшений по сравнению с той, которая требует более управляемых ресурсов. 
    Обратите внимание, что эта опция реализована как ограничение, за пределами которого происходит двоичное сокращение. 
    Таким образом, если у вас есть устройство, снимающее со скоростью 15 кадров в секунду, и установите для этого параметра значение 10 кадров в секунду, 
    то видео будет воспроизводиться не со скоростью 10 кадров в секунду, а со скоростью 7,5 кадров в секунду (15 делится на 2), поскольку конечная частота кадров должна быть исходной, деленной на степень 2.'
  ),
'ZM_WEB_H_SCALE_THUMBS' => array(
    'Help' => '
    Если этот параметр не установлен, то все изображение отправляется в браузер, который изменяет его размер в окне. 
    Если задано, изображение уменьшается на сервере перед отправкой изображения уменьшенного размера в браузер, чтобы сэкономить пропускную способность за счет процессора на сервере. 
    Обратите внимание, что ZM может выполнять изменение размера только в том случае, если установлена соответствующая графическая функциональность PHP. 
    Обычно это доступно в пакете php-gd.'
  ),
'ZM_WEB_H_EVENTS_VIEW' => array(
    'Help' => '
    Сохраненные события можно просматривать либо в формате списка событий, либо в формате, основанном на временной шкале. 
    Этот параметр задает вид по умолчанию, который будет использоваться. 
    Выбор одного вида здесь не препятствует использованию другого вида, поскольку он всегда будет выбираться из того вида, который используется в данный момент.'
  ),
'ZM_WEB_H_SHOW_PROGRESS' => array(
    'Help' => '
    При просмотре событий панель навигации по событию и индикатор выполнения отображаются под самим событием. 
    Это позволяет вам переходить к определенным точкам события, но также может динамически обновляться для отображения текущего хода воспроизведения самого события. 
    Этот прогресс рассчитывается исходя из фактической продолжительности события и напрямую не связан с самим воспроизведением, поэтому при ограниченной пропускной способности соединения могут не совпадать с воспроизведением. 
    Эта опция позволяет отключить отображение хода выполнения, сохраняя при этом навигационный аспект, поскольку пропускная способность не позволяет ему эффективно функционировать.'
  ),
'ZM_WEB_H_AJAX_TIMEOUT' => array(
    'Help' => '
    В более новых версиях прямой трансляции и представлений событий используется Ajax для запроса информации с сервера и динамического заполнения представлений. 
    Этот параметр позволяет вам указать тайм-аут, если требуется, после которого запросы будут отклонены. 
    Тайм-аут может потребоваться, если в противном случае запросы будут зависать, например, при медленном соединении. 
    Это, как правило, потребляет много памяти браузера и делает интерфейс невосприимчивым. 
    Обычно ни один запрос не должен иметь тайм-аута, поэтому для этого параметра следует установить значение, превышающее самый медленный ожидаемый ответ. 
    Это значение выражается в миллисекундах, но если оно равно нулю, то тайм-аут использоваться не будет.'
  ),
'ZM_WEB_M_REFRESH_MAIN' => array(
    'Help' => '
    В главном окне консоли отображается общее состояние и итоговые данные по событиям для всех мониторов. 
    Это нетривиальная задача, и ее не следует повторять слишком часто, иначе это может повлиять на производительность остальной части системы.'
  ),
'ZM_WEB_M_REFRESH_NAVBAR' => array(
    'Help' => '
    Заголовок навигации содержит общую информацию о состоянии загрузки сервера и пространстве для хранения.'
  ),
'ZM_WEB_M_REFRESH_CYCLE' => array(
    'Help' => '
    Окно просмотра цикла - это метод непрерывного переключения между изображениями со всех ваших мониторов. 
    Этот параметр определяет, как часто обновлять новое изображение.'
  ),
'ZM_WEB_M_REFRESH_IMAGE' => array(
    'Help' => '
    Изображения в реальном времени с монитора можно просматривать как в потоковом режиме, так и в режиме фотосъемки. 
    Этот параметр определяет, как часто обновляется неподвижное изображение, он не влияет, если выбран параметр потоковой передачи.'
  ),
'ZM_WEB_M_REFRESH_STATUS' => array(
    'Help' => '
    Окно монитора на самом деле состоит из нескольких кадров. 
    Тот, что посередине, просто содержит статус монитора, который необходимо обновлять довольно часто, чтобы дать верное представление. 
    Этот параметр определяет эту частоту.'
  ),
'ZM_WEB_M_REFRESH_EVENTS' => array(
    'Help' => '
    Окно монитора на самом деле состоит из нескольких кадров. 
    Нижняя рамка содержит список последних нескольких событий для легкого доступа. 
    Этот параметр определяет, как часто он обновляется.'
  ),
'ZM_WEB_M_CAN_STREAM' => array(
    'Help' => '
    Если вы знаете, что ваш браузер может обрабатывать потоки изображений типа "multipart/x-mixed-replace", 
    но ZoneMinder не определяет это правильно, вы можете установить этот параметр, чтобы гарантировать, 
    что поток доставляется с использованием плагина Cambozola или без него. 
    Выбор "да" сообщит ZoneMinder, что ваш браузер может обрабатывать потоки изначально, "нет" означает, что он не может, 
    и поэтому будет использоваться плагин, в то время как "авто" позволяет ZoneMinder решать.'
  ),
'ZM_WEB_M_STREAM_METHOD' => array(
    'Help' => '
    ZoneMinder может быть настроен на использование либо видео в кодировке mpeg, либо серии неподвижных изображений в формате jpeg при отправке видеопотоков. 
    Этот параметр определяет, какой из них используется. 
    Если вы выберете mpeg, вы должны убедиться, что в вашем браузере доступны соответствующие плагины, 
    тогда как выбор jpeg будет работать изначально в Mozilla и связанных браузерах, а также с Java-апплетом в Internet Explorer.'
  ),
'ZM_WEB_M_DEFAULT_SCALE' => array(
    'Help' => '
    Обычно ZoneMinder отображает потоки "прямой эфир" или "событие" в их собственном размере. 
    Однако, если у вас мониторы с большими размерами или медленной связью, вы можете предпочесть уменьшить этот размер, в качестве альтернативы для небольших мониторов вы можете увеличить его. 
    Этот параметр позволяет указать, каким будет коэффициент масштабирования по умолчанию. 
    Он выражается в процентах, поэтому 100 - это нормальный размер, 200 - двойной размер и т.д.'
  ),
'ZM_WEB_M_DEFAULT_RATE' => array(
    'Help' => '
    Обычно ZoneMinder отображает потоки "событий" с их собственной скоростью, то есть как можно ближе к реальному времени. 
    Однако, если у вас есть длинные события, часто бывает удобно воспроизвести их в более быстром темпе для просмотра. 
    Этот параметр позволяет вам указать, какой будет частота воспроизведения по умолчанию. 
    Она выражается в процентах, поэтому 100 - это нормальная скорость, 200 - двойная скорость и т.д.'
  ),
'ZM_WEB_M_VIDEO_BITRATE' => array(
    'Help' => '
    При кодировании реального видео с помощью библиотеки ffmpeg может быть задана скорость передачи данных, которая примерно соответствует доступной полосе пропускания, используемой для потока. 
    Этот параметр фактически соответствует параметру "качество" для видео. Низкое значение приведет к получению блочного изображения, в то время как высокое значение обеспечит более четкое изображение. 
    Обратите внимание, что этот параметр не управляет частотой кадров видео, однако на качество создаваемого видео влияет как этот параметр, так и частота кадров, с которой создается видео. 
    Более высокая частота кадров при определенной скорости передачи данных приводит к тому, что отдельные кадры имеют более низкое качество.'
  ),
'ZM_WEB_M_VIDEO_MAXFPS' => array(
    'Help' => '
    При использовании потокового видео основным элементом управления является битрейт, который определяет, сколько данных может быть передано. 
    Однако более низкий битрейт при высокой частоте кадров приводит к снижению качества изображения. 
    Эта опция позволяет ограничить максимальную частоту кадров, чтобы обеспечить сохранение качества видео. 
    Дополнительным преимуществом является то, что кодирование видео с высокой частотой кадров является трудоемкой задачей для процессора, 
    когда по большей части очень высокая частота кадров дает мало заметных улучшений по сравнению с той, которая требует более управляемых ресурсов. 
    Обратите внимание, что эта опция реализована как ограничение, за пределами которого происходит двоичное сокращение. 
    Таким образом, если у вас есть устройство, снимающее со скоростью 15 кадров в секунду, и установите для этого параметра значение 10 кадров в секунду, 
    то видео будет воспроизводиться не со скоростью 10 кадров в секунду, а со скоростью 7,5 кадров в секунду (15 делится на 2), поскольку конечная частота кадров должна быть исходной, деленной на степень 2.'
  ),
'ZM_WEB_M_SCALE_THUMBS' => array(
    'Help' => '
    Если этот параметр не установлен, то все изображение отправляется в браузер, который изменяет его размер в окне. 
    Если задано, изображение уменьшается на сервере перед отправкой изображения уменьшенного размера в браузер, чтобы сэкономить пропускную способность за счет процессора на сервере. 
    Обратите внимание, что ZM может выполнять изменение размера только в том случае, если установлена соответствующая графическая функциональность PHP. 
    Обычно это доступно в пакете php-gd.'
  ),
'ZM_WEB_M_EVENTS_VIEW' => array(
    'Help' => '
    Сохраненные события можно просматривать либо в формате списка событий, либо в формате, основанном на временной шкале. 
    Этот параметр задает вид по умолчанию, который будет использоваться. 
    Выбор одного вида здесь не препятствует использованию другого вида, поскольку он всегда будет выбираться из того вида, который используется в данный момент.'
  ),
'ZM_WEB_M_SHOW_PROGRESS' => array(
    'Help' => '
    При просмотре событий панель навигации по событию и индикатор выполнения отображаются под самим событием. 
    Это позволяет вам переходить к определенным точкам события, но также может динамически обновляться для отображения текущего хода воспроизведения самого события. 
    Этот прогресс рассчитывается исходя из фактической продолжительности события и напрямую не связан с самим воспроизведением, 
    поэтому при ограниченной пропускной способности соединения могут не совпадать с воспроизведением. 
    Эта опция позволяет отключить отображение хода выполнения, сохраняя при этом навигационный аспект, поскольку пропускная способность не позволяет ему эффективно функционировать.'
  ),
'ZM_WEB_M_AJAX_TIMEOUT' => array(
    'Help' => '
    В более новых версиях прямой трансляции и представлений событий используется Ajax для запроса информации с сервера и динамического заполнения представлений. 
    Этот параметр позволяет вам указать тайм-аут, если требуется, после которого запросы будут отклонены. 
    Тайм-аут может потребоваться, если в противном случае запросы будут зависать, например, при медленном соединении. 
    Это, как правило, потребляет много памяти браузера и делает интерфейс невосприимчивым. 
    Обычно ни один запрос не должен иметь тайм-аута, поэтому для этого параметра следует установить значение, превышающее самый медленный ожидаемый ответ. 
    Это значение выражается в миллисекундах, но если оно равно нулю, то тайм-аут использоваться не будет.'
  ),
'ZM_WEB_L_REFRESH_MAIN' => array(
    'Help' => '
    В главном окне консоли отображается общее состояние и итоговые данные по событиям для всех мониторов. 
    Это нетривиальная задача, и ее не следует повторять слишком часто, иначе это может повлиять на производительность остальной части системы.'
  ),
'ZM_WEB_L_REFRESH_NAVBAR' => array(
    'Help' => '
    Заголовок навигации содержит общую информацию о состоянии загрузки сервера и пространстве для хранения.'
  ),
'ZM_WEB_L_REFRESH_CYCLE' => array(
    'Help' => '
    Окно просмотра цикла - это метод непрерывного переключения между изображениями со всех ваших мониторов. 
    Этот параметр определяет, как часто обновлять новое изображение.'
  ),
'ZM_WEB_L_REFRESH_IMAGE' => array(
    'Help' => '
    Изображения в реальном времени с монитора можно просматривать как в потоковом режиме, так и в режиме фотосъемки. 
    Этот параметр определяет, как часто обновляется неподвижное изображение, он не влияет, если выбран параметр потоковой передачи.'
  ),
'ZM_WEB_L_REFRESH_STATUS' => array(
    'Help' => '
    Окно монитора на самом деле состоит из нескольких кадров. 
    Тот, что посередине, просто содержит статус монитора, который необходимо обновлять довольно часто, чтобы дать верное представление. 
    Этот параметр определяет эту частоту.'
  ),
'ZM_WEB_L_REFRESH_EVENTS' => array(
    'Help' => '
    Окно монитора на самом деле состоит из нескольких кадров. 
    Нижняя рамка содержит список последних нескольких событий для легкого доступа. 
    Этот параметр определяет, как часто он обновляется.'
  ),
'ZM_WEB_L_CAN_STREAM' => array(
    'Help' => '
    Если вы знаете, что ваш браузер может обрабатывать потоки изображений типа "multipart/x-mixed-replace", 
    но ZoneMinder не определяет это правильно, вы можете установить этот параметр, чтобы гарантировать, 
    что поток доставляется с использованием плагина Cambozola или без него. 
    Выбор "да" сообщит ZoneMinder, что ваш браузер может обрабатывать потоки изначально, "нет" означает, что он не может, 
    и поэтому будет использоваться плагин, в то время как "авто" позволяет ZoneMinder решать.'
  ),
'ZM_WEB_L_STREAM_METHOD' => array(
    'Help' => '
    ZoneMinder может быть настроен на использование либо видео в кодировке mpeg, либо серии неподвижных изображений в формате jpeg при отправке видеопотоков. 
    Этот параметр определяет, какой из них используется. 
    Если вы выберете mpeg, вы должны убедиться, что в вашем браузере доступны соответствующие плагины, 
    тогда как выбор jpeg будет работать изначально в Mozilla и связанных браузерах, а также с Java-апплетом в Internet Explorer.'
  ),
'ZM_WEB_L_DEFAULT_SCALE' => array(
    'Help' => '
    Обычно ZoneMinder отображает потоки "прямой эфир" или "событие" в их собственном размере. 
    Однако, если у вас мониторы с большими размерами или медленной связью, вы можете предпочесть уменьшить этот размер, 
    в качестве альтернативы для небольших мониторов вы можете увеличить его. 
    Этот параметр позволяет указать, каким будет коэффициент масштабирования по умолчанию. 
    Он выражается в процентах, поэтому 100 - это нормальный размер, 200 - двойной размер и т.д.'
  ),
'ZM_WEB_L_DEFAULT_RATE' => array(
    'Help' => '
    Обычно ZoneMinder отображает потоки "событий" с их собственной скоростью, то есть как можно ближе к реальному времени. 
    Однако, если у вас есть длинные события, часто бывает удобно воспроизвести их в более быстром темпе для просмотра. 
    Этот параметр позволяет вам указать, какой будет частота воспроизведения по умолчанию. 
    Она выражается в процентах, поэтому 100 - это нормальная скорость, 200 - двойная скорость и т.д.'
  ),
'ZM_WEB_L_VIDEO_BITRATE' => array(
    'Help' => '
    При кодировании реального видео с помощью библиотеки ffmpeg может быть задана скорость передачи данных, 
    которая примерно соответствует доступной полосе пропускания, используемой для потока. 
    Этот параметр фактически соответствует параметру "качество" для видео. 
    Низкое значение приведет к получению блочного изображения, в то время как высокое значение обеспечит более четкое изображение. 
    Обратите внимание, что этот параметр не управляет частотой кадров видео, 
    однако на качество создаваемого видео влияет как этот параметр, так и частота кадров, с которой создается видео. 
    Более высокая частота кадров при определенной скорости передачи данных приводит к тому, что отдельные кадры имеют более низкое качество.'
  ),
'ZM_WEB_L_VIDEO_MAXFPS' => array(
    'Help' => '
    При использовании потокового видео основным элементом управления является битрейт, который определяет, сколько данных может быть передано. 
    Однако более низкий битрейт при высокой частоте кадров приводит к снижению качества изображения. 
    Эта опция позволяет ограничить максимальную частоту кадров, чтобы обеспечить сохранение качества видео. 
    Дополнительным преимуществом является то, что кодирование видео с высокой частотой кадров является трудоемкой задачей для процессора, 
    когда по большей части очень высокая частота кадров дает мало заметных улучшений по сравнению с той, которая требует более управляемых ресурсов. 
    Обратите внимание, что эта опция реализована как ограничение, за пределами которого происходит двоичное сокращение. 
    Таким образом, если у вас есть устройство, снимающее со скоростью 15 кадров в секунду, 
    и установите для этого параметра значение 10 кадров в секунду, то видео будет воспроизводиться не со скоростью 10 кадров в секунду, 
    а со скоростью 7,5 кадров в секунду (15 делится на 2), поскольку конечная частота кадров должна быть исходной, деленной на степень 2.'
  ),
'ZM_WEB_L_SCALE_THUMBS' => array(
    'Help' => '
    Если этот параметр не установлен, то все изображение отправляется в браузер, который изменяет его размер в окне. 
    Если задано, изображение уменьшается на сервере перед отправкой изображения уменьшенного размера в браузер, 
    чтобы сэкономить пропускную способность за счет процессора на сервере. 
    Обратите внимание, что ZM может выполнять изменение размера только в том случае, 
    если установлена соответствующая графическая функциональность PHP. 
    Обычно это доступно в пакете php-gd.'
  ),
'ZM_WEB_L_EVENTS_VIEW' => array(
    'Help' => '
    Сохраненные события можно просматривать либо в формате списка событий, либо в формате, основанном на временной шкале. 
    Этот параметр задает вид по умолчанию, который будет использоваться. 
    Выбор одного вида здесь не препятствует использованию другого вида, поскольку он всегда будет выбираться из того вида, 
    который используется в данный момент.'
  ),
'ZM_WEB_L_SHOW_PROGRESS' => array(
    'Help' => '
    При просмотре событий панель навигации по событию и индикатор выполнения отображаются под самим событием. 
    Это позволяет вам переходить к определенным точкам события, но также может динамически обновляться для отображения текущего хода воспроизведения самого события. 
    Этот прогресс рассчитывается исходя из фактической продолжительности события и напрямую не связан с самим воспроизведением, 
    поэтому при ограниченной пропускной способности соединения могут не совпадать с воспроизведением. 
    Эта опция позволяет отключить отображение хода выполнения, сохраняя при этом навигационный аспект, 
    поскольку пропускная способность не позволяет ему эффективно функционировать.'
  ),
'ZM_WEB_L_AJAX_TIMEOUT' => array(
    'Help' => '
    В более новых версиях прямой трансляции и представлений событий используется Ajax для запроса информации с сервера и динамического заполнения представлений. 
    Этот параметр позволяет вам указать тайм-аут, если требуется, после которого запросы будут отклонены. 
    Тайм-аут может потребоваться, если запросы будут чрезмерно зависать, например, при медленном соединении. 
    Это, как правило, потребляет много памяти браузера и делает интерфейс невосприимчивым. 
    Обычно ни один запрос не должен иметь тайм-аута, поэтому для этого параметра следует установить значение, превышающее самый медленный ожидаемый ответ. 
    Это значение выражается в миллисекундах, но если оно равно нулю, то тайм-аут использоваться не будет.'
  ),
'ZM_FONT_FILE_LOCATION' => array(
    'Help' => '
    Этот шрифт используется для меток времени.'
  ),
'ZM_FONT_FILE_LOCATION' => array(
    'Help' => '
    Этот шрифт используется для меток времени.'
  ),
'ZM_FONT_FILE_LOCATION' => array(
    'Help' => '
    Этот шрифт используется для меток времени.'
  ),

//   ****************Prompts *************************

	'ADD_JPEG_COMMENTS' => array ( 'Prompt'=>'Добавление аннотаций временных меток в формате jpeg в качестве комментариев к заголовку файла'),
	'AUDIT_CHECK_INTERVAL' => array ( 'Prompt'=>'Как часто проверять согласованность базы данных и файловой системы'),
	'AUDIT_MIN_AGE' => array ( 'Prompt'=>'Минимальный возраст данных события в секундах должен быть таким, чтобы их можно было удалить.'),
	'AUTH_HASH_IPS' => array ( 'Prompt'=>'Включение IP-адресов в хэш аутентификации'),
	'AUTH_HASH_LOGINS' => array ( 'Prompt'=>'Разрешить вход в систему с использованием хэша аутентификации'),
	'AUTH_HASH_SECRET' => array ( 'Prompt'=>'Секрет кодирования хэшированной информации аутентификации'),
	'AUTH_HASH_TTL' => array ( 'Prompt'=>'Количество часов, в течение которых хэш аутентификации действителен.'),
	'AUTH_RELAY' => array ( 'Prompt'=>'Метод, используемый для передачи аутентификационной информации'),
	'AUTH_TYPE' => array ( 'Prompt'=>'Что используется для аутентификации пользователей ZoneMinder'),
	'BANDWIDTH_DEFAULT' => array ( 'Prompt'=>'Настройка по умолчанию для профиля пропускной способности, используемого веб-интерфейсом'),
	'BULK_FRAME_INTERVAL' => array ( 'Prompt'=>'Как часто следует массово записывать таблицы в базу данных'),
	'CAPTURES_PER_FRAME' => array ( 'Prompt'=>'Сколько изображений снято за отклоненный кадр для общих локальных камер'),
	'CHECK_FOR_UPDATES' => array ( 'Prompt'=>'Проверьте с помощью zoneminder.com по обновленным версиям'),
	'COLOUR_JPEG_FILES' => array ( 'Prompt'=>'Раскрашивание файлов JPEG изначально в оттенки серого'),
	'COOKIE_LIFETIME' => array ( 'Prompt'=>'Максимальный срок службы файла COOKIE, используемого при настройке обработчика сеанса PHP.'),
	'CPU_EXTENSIONS' => array ( 'Prompt'=>'Использование расширенных расширений ЦП для повышения производительности'),
	'CSP_REPORT_URI' => array ( 'Prompt'=>'URI для сообщения о встроенных нарушениях безопасности javascript'),
	'CSS_DEFAULT' => array ( 'Prompt'=>'Набор файлов css по умолчанию, используемых веб-интерфейсом'),
	'DATETIME_FORMAT_PATTERN' => array ( 'Prompt'=>'Перезапись системного формата даты/времени.'),
	'DATE_FORMAT_PATTERN' => array ( 'Prompt'=>'Перезаписать системный формат даты.'),
	'DEFAULT_ASPECT_RATIO' => array ( 'Prompt'=>'Соотношение сторон ширина:высота по умолчанию, используемое на мониторах'),
	'DUMP_CORES' => array ( 'Prompt'=>'Создавайте “основные " файлы на случай непредвиденного сбоя процесса.'),
	'DYN_CURR_VERSION' => array ( 'Prompt'=>''.
	      'Какой может быть эффективная установленная версия ZoneMinder'.
	      ' отличается от текущего, если версии были проигнорированы.'
        	),
	'DYN_DB_VERSION' => array ( 'Prompt'=>'Какая версия базы данных из zmupdate'),
	'DYN_DONATE_REMINDER_TIME' => array ( 'Prompt'=>'Когда будет самое раннее время вспомнить о пожертвованиях'),
	'DYN_LAST_CHECK' => array ( 'Prompt'=>'Когда была проведена последняя проверка версии на zoneminder.com'),
	'DYN_LAST_VERSION' => array ( 'Prompt'=>'Какая последняя версия ZoneMinder зарегистрирована на zoneminder.com'),
	'DYN_NEXT_REMINDER' => array ( 'Prompt'=>'Когда будет самое раннее время вспомнить версии'),
	'DYN_SHOW_DONATE_REMINDER' => array ( 'Prompt'=>'Напомнить о пожертвованиях или нет'),
	'EMAIL_HOST' => array ( 'Prompt'=>'Адрес хоста вашего почтового сервера SMTP'),
	'ENABLE_CSRF_MAGIC' => array ( 'Prompt'=>'Включить библиотеку csrf-magic'),
	'EVENT_CLOSE_MODE' => array ( 'Prompt'=>'Когда текущие события будут закрыты.'),
	'EVENT_IMAGE_DIGITS' => array ( 'Prompt'=>'Сколько значащих цифр используется при нумерации изображений событий'),
	'FAST_IMAGE_BLENDS' => array ( 'Prompt'=>'Используйте быстрый алгоритм для смешивания эталонного изображения'),
	'FEATURES_SNAPSHOTS' => array ( 'Prompt'=>'Включите функцию создания моментальных снимков (снапшоты).'),
	'FFMPEG_FORMATS' => array ( 'Prompt'=>'Форматы, позволяющие создавать видео в формате ffmpeg'),
	'FFMPEG_INPUT_OPTIONS' => array ( 'Prompt'=>'Дополнительные параметры ввода для ffmpeg'),
	'FFMPEG_OPEN_TIMEOUT' => array ( 'Prompt'=>'Тайм-аут в секундах при открытии потока.'),
	'FFMPEG_OUTPUT_OPTIONS' => array ( 'Prompt'=>'Дополнительные параметры вывода для ffmpeg'),
	'FILTER_EXECUTE_INTERVAL' => array ( 'Prompt'=>'Как часто (в секундах) запускать автоматически сохраненные фильтры'),
	'FILTER_RELOAD_DELAY' => array ( 'Prompt'=>'Как часто (в секундах) фильтры перезагружаются в zmfilter'),
	'FONT_FILE_LOCATION' => array ( 'Prompt'=>'Расположение исходного файла'),
	'FORCED_ALARM_SCORE' => array ( 'Prompt'=>'Оценка, присвоенная принудительной тревоге'),
	'FROM_EMAIL' => array ( 'Prompt'=>'Адрес электронной почты, с которого вы хотите получать уведомления о событиях'),
	'HOME_ABOUT' => array ( 'Prompt'=>'Включить меню - О программе... - в ZoneMinder.'),
	'HOME_CONTENT' => array ( 'Prompt'=>'Содержимое кнопки "Домой".'),
	'HOME_URL' => array ( 'Prompt'=>'URL-адрес, используемый в области запуска / логотипа панели навигации.URL-адрес, используемый в области запуска / логотипа панели навигации.'),
	'HTTP_TIMEOUT' => array ( 'Prompt'=>'Как долго ZoneMinder ждет, прежде чем отказаться от изображений (миллисекунды)'),
	'HTTP_UA' => array ( 'Prompt'=>'Пользовательский агент, который использует ZoneMinder для идентификации себя'),
	'HTTP_VERSION' => array ( 'Prompt'=>'Версия HTTP, которую ZoneMinder будет использовать для подключения'),
	'JANUS_PATH' => array ( 'Prompt'=>'URL-адрес сервера Janus ( HTTP / HTTPS: порт).'),
	'JANUS_SECRET' => array ( 'Prompt'=>'Пароль для управления потоковой передачей Janus (потоковая передача).'),
	'JPEG_ALARM_FILE_QUALITY' => array ( 'Prompt'=>'Установите настройки качества JPEG для файлов событий, сохраненных во время тревоги (1-100)'),
	'JPEG_FILE_QUALITY' => array ( 'Prompt'=>'Установите параметры качества JPEG для сохраненных файлов событий (1-100)'),
	'JPEG_STREAM_QUALITY' => array ( 'Prompt'=>'Установите настройки качества JPEG для передаваемых "живых" изображений (1-100)'),
	'LANG_DEFAULT' => array ( 'Prompt'=>'Язык по умолчанию, используемый веб-интерфейсом'),
	'LD_PRELOAD' => array ( 'Prompt'=>'Путь к библиотеке для предварительной загрузки перед запуском демонов (демонов)'),
	'LOCALE_DEFAULT' => array ( 'Prompt'=>'Языковой стандарт по умолчанию, используемый при форматировании строк даты/времени.'),
	'LOG_ALARM_ERR_COUNT' => array ( 'Prompt'=>'Количество ошибок, указывающих на состояние тревоги системы'),
	'LOG_ALARM_FAT_COUNT' => array ( 'Prompt'=>'Количество фатальных ошибок, указывающих на состояние тревоги системы'),
	'LOG_ALARM_WAR_COUNT' => array ( 'Prompt'=>'Количество предупреждений (warnings), указывающих на аварийный статус системы'),
	'LOG_ALERT_ERR_COUNT' => array ( 'Prompt'=>'Количество ошибок, указывающих на состояние готовности системы'),
	'LOG_ALERT_FAT_COUNT' => array ( 'Prompt'=>'Количество фатальных ошибок, указывающих на состояние готовности системы'),
	'LOG_ALERT_WAR_COUNT' => array ( 'Prompt'=>'Количество предупреждений (warnings), указывающих на состояние готовности системы'),
	'LOG_CHECK_PERIOD' => array ( 'Prompt'=>'Период времени, используемый при расчете общего состояния системы'),
	'LOG_DATABASE_LIMIT' => array ( 'Prompt'=>'Максимальное количество сохраняемых записей журнала (журналов) '),
	'LOG_DEBUG' => array ( 'Prompt'=>'Включить отладку'),
	'LOG_DEBUG_FILE' => array ( 'Prompt'=>'Куда отправляется дополнительная отладочная информация'),
	'LOG_DEBUG_LEVEL' => array ( 'Prompt'=>'Какой дополнительный уровень отладки вы хотите включить'),
	'LOG_DEBUG_TARGET' => array ( 'Prompt'=>'Какие компоненты должны иметь включенную дополнительную отладку'),
	'LOG_FFMPEG' => array ( 'Prompt'=>'Регистрировать сообщения FFMPEG'),
	'LOG_LEVEL_DATABASE' => array ( 'Prompt'=>'Сохранение выходных данных журнала (журналов) в базе данных'),
	'LOG_LEVEL_FILE' => array ( 'Prompt'=>'Сохраните вывод журнала (logs) в файлы компонента'),
	'LOG_LEVEL_SYSLOG' => array ( 'Prompt'=>'Сохранение выходных данных журнала (журналов) в системный журнал (системный журнал)'),
	'LOG_LEVEL_WEBLOG' => array ( 'Prompt'=>'Сохранение выходных данных журнала (журналов) в веб'),
	'MAX_RESTART_DELAY' => array ( 'Prompt'=>'Максимальная задержка (в секундах) между попытками перезапуска демона.'),
	'MAX_RTP_PORT' => array ( 'Prompt'=>'Максимальный порт, через который ZoneMinder будет прослушивать RTP-трафик'),
	'MAX_SUSPEND_TIME' => array ( 'Prompt'=>'Максимальное время, в течение которого монитор может приостанавливать обнаружение движения'),
	'MESSAGE_ADDRESS' => array ( 'Prompt'=>'Адрес электронной почты, на который нужно отправить сведения о соответствующем событии'),
	'MESSAGE_BODY' => array ( 'Prompt'=>'Тело сообщения, используемое для отправки сведений о совпадающих событиях'),
	'MESSAGE_SUBJECT' => array ( 'Prompt'=>'Тема сообщения, используемая для отправки сведений о совпадающих событиях'),
	'MIN_RTP_PORT' => array ( 'Prompt'=>'Минимальный порт, через который ZoneMinder будет прослушивать RTP-трафик'),
	'MIN_RTSP_PORT' => array ( 'Prompt'=>'Начало диапазона портов для связи для потоковой передачи видео (потоковой передачи) по протоколу RTSP.'),
	'MIN_STREAMING_PORT' => array ( 'Prompt'=>'Альтернативный диапазон портов для связи для потоковой передачи.'),
	'MPEG_LIVE_FORMAT' => array ( 'Prompt'=>'Формат для воспроизведения "прямых" видеопотоков"'),
	'MPEG_REPLAY_FORMAT' => array ( 'Prompt'=>'Формат для воспроизведения предварительно записанных видеоматериалов'),
	'MPEG_TIMED_FRAMES' => array ( 'Prompt'=>'Пометьте видеокадры меткой времени для более реалистичной трансляции'),
	'NEW_MAIL_MODULES' => array ( 'Prompt'=>'Использование более нового метода perl для отправки электронных писем'),
	'OPT_ADAPTIVE_SKIP' => array ( 'Prompt'=>'Должен ли анализ кадров быть эффективным при пропуске кадров'),
	'OPT_CONTROL' => array ( 'Prompt'=>'Поддержка управляемых камер (например, PTZ)'),
	'OPT_CAMBOZOLA' => array ( 'Prompt'=>'Установлен ли (необязательно) потоковый клиент cambozola java'),
	'PATH_CAMBOZOLA' => array ( 'Prompt'=>'Веб-путь к (необязательно) потоковому java-клиенту cambozola'),
	'RELOAD_CAMBOZOLA' => array ( 'Prompt'=>'Через сколько секунд cambozola должна быть перезагружена в режиме реального времени'),
	'OPT_EMAIL' => array ( 'Prompt'=>'Вы должны отправить ZoneMinder по электронной почте подробную информацию о событиях, соответствующих соответствующим фильтрам'),
	'OPT_FAST_DELETE' => array ( 'Prompt'=>'Удаление только записей из базы данных событий для увеличения скорости'),
	'OPT_FFMPEG' => array ( 'Prompt'=>'Установлен видеокодер / декодер ffmpeg'),
	'OPT_GEOLOCATION_ACCESS_TOKEN' => array ( 'Prompt'=>'Токен доступа для поставщика плиток карт.'),
	'OPT_GEOLOCATION_TILE_PROVIDER' => array ( 'Prompt'=>'Поставщик плиток для карт.'),
	'OPT_GOOG_RECAPTCHA_SECRETKEY' => array ( 'Prompt'=>'Ваш секретный ключ recaptcha'),
	'OPT_GOOG_RECAPTCHA_SITEKEY' => array ( 'Prompt'=>'Ваш ключ сайта recaptcha'),
	'OPT_MESSAGE' => array ( 'Prompt'=>'Должен ли ZoneMinder отправить вам сообщение с подробной информацией о событиях, соответствующей соответствующим фильтрам'),
	'OPT_TRIGGERS' => array ( 'Prompt'=>'Подключение внешних триггеров событий через сокеты или файлы “/dev/“'),
	'OPT_UPLOAD' => array ( 'Prompt'=>'Должен ли ZoneMinder разрешать загрузку событий из фильтров'),
	'OPT_USE_API' => array ( 'Prompt'=>'Включить API-интерфейсы ZoneMinder'),
	'OPT_USE_AUTH' => array ( 'Prompt'=>'Аутентификация логинов пользователей в ZoneMinder'),
	'OPT_USE_EVENTNOTIFICATION' => array ( 'Prompt'=>'Включить сторонний сервер уведомлений о событиях'),
	'OPT_USE_GEOLOCATION' => array ( 'Prompt'=>'Добавьте функции геолокации в ZoneMinder.'),
	'OPT_USE_GOOG_RECAPTCHA' => array ( 'Prompt'=>'Добавьте Google reCAPTCHA на страницу входа в систему'),
	'OPT_USE_LEGACY_API_AUTH' => array ( 'Prompt'=>'Включить устаревшую аутентификацию API'),
	'OPT_X10' => array ( 'Prompt'=>'Поддержка взаимодействия с устройствами X10'),
	'PATH_FFMPEG' => array ( 'Prompt'=>'Путь к кодировщику ffmpeg mpeg (необязательно)'),
	'RAND_STREAM' => array ( 'Prompt'=>'Добавление случайной строки для предотвращения кэширования потоков'),
	'RECORD_DIAG_IMAGES' => array ( 'Prompt'=>'Запись диагностических изображений промежуточных сигналов тревоги может быть очень медленной'),
	'RECORD_DIAG_IMAGES_FIFO' => array ( 'Prompt'=>'Промежуточная диагностическая запись аварийного сигнала используйте fifo вместо файлов (быстрее)'),
	'RECORD_EVENT_STATS' => array ( 'Prompt'=>'Запись статистической информации о событиях выключите, если она становится слишком медленной'),
	'RUN_AUDIT' => array ( 'Prompt'=>'Запустите zmaudit, чтобы проверить согласованность данных'),
	'SHM_KEY' => array ( 'Prompt'=>'Корневой ключ общей памяти для использования'),
	'SHOW_PRIVACY' => array ( 'Prompt'=>'Подать заявление о конфиденциальности'),
	'SKIN_DEFAULT' => array ( 'Prompt'=>'Тема по умолчанию, используемая веб-интерфейсом'),
	'SSMTP_MAIL' => array ( 
		'Prompt' => ' '.
      		'Используйте почтовый сервер SSMTP, если он доступен.'.
      		' NEW_MAIL_MODULES он должен быть включен'
      		),
	'SSMTP_PATH' => array ( 'Prompt'=>'Путь к удаляемому SSMTP'),
	'STATS_UPDATE_INTERVAL' => array ( 'Prompt'=>'Как часто обновлять статистику базы данных'),
	'STRICT_VIDEO_CONFIG' => array ( 'Prompt'=>'Разрешить ошибки в настройках видео со смертельным исходом'),
	'SYSTEM_SHUTDOWN' => array ( 'Prompt'=>'Разрешить пользователям-администраторам выключать или перезагружать систему из пользовательского интерфейса ZoneMinder.'),
	'TELEMETRY_DATA' => array ( 'Prompt'=>'Отправить информацию об использовании в ZoneMinder'),
	'TELEMETRY_INTERVAL' => array ( 'Prompt'=>'Интервал в секундах между обновлениями телеметрии.'),
	'TELEMETRY_LAST_UPLOAD' => array ( 'Prompt'=>'Когда произошла последняя загрузка телеметрии в ZoneMinder'),
	'TELEMETRY_SERVER_ENDPOINT' => array ( 'Prompt'=>'URL-адрес, на который ZoneMinder будет отправлять данные об использовании'),
	'TELEMETRY_UUID' => array ( 'Prompt'=>'Уникальный идентификатор для телеметрии ZoneMinder'),
	'TIMESTAMP_CODE_CHAR' => array ( 'Prompt'=>'Символ, используемый для идентификации кодов временных меток'),
	'TIMESTAMP_ON_CAPTURE' => array ( 'Prompt'=>'Добавление метки времени к изображениям, как только они будут сняты'),
	'TIMEZONE' => array ( 'Prompt'=>'Часовой пояс, который вы должны указать в php.'),
	'TIME_FORMAT_PATTERN' => array ( 'Prompt'=>'Перезаписать формат системного времени.'),
	'UPDATE_CHECK_PROXY' => array ( 'Prompt'=>'URL-адрес прокси-сервера, если требуется для доступа к zoneminder.com'),
	'UPLOAD_ARCH_ANALYSE' => array ( 'Prompt'=>'Включить файлы анализа в заархивированное событие'),
	'UPLOAD_ARCH_COMPRESS' => array ( 'Prompt'=>'Архивированные события должны быть сжаты'),
	'UPLOAD_ARCH_FORMAT' => array ( 'Prompt'=>'В каком формате должны быть созданы события для удаленной загрузки.'),
	'UPLOAD_DEBUG' => array ( 'Prompt'=>'Включить отладку удаленной загрузки'),
	'UPLOAD_FTP_PASSIVE' => array ( 'Prompt'=>'Используйте пассивный ftp при удаленной загрузке'),
	'UPLOAD_HOST' => array ( 'Prompt'=>'Удаленный сервер для загрузки событий'),
	'UPLOAD_LOC_DIR' => array ( 'Prompt'=>'Локальный каталог для создания файлов для удаленной загрузки'),
	'UPLOAD_PASS' => array ( 'Prompt'=>'Пароль удаленного сервера'),
	'UPLOAD_PORT' => array ( 'Prompt'=>'Порт на сервере удаленной загрузки, если он не установлен по умолчанию (только SFTP)'),
	'UPLOAD_PROTOCOL' => array ( 'Prompt'=>'Какой протокол использовать для загрузки событий на удаленный сервер'),
	'UPLOAD_REM_DIR' => array ( 'Prompt'=>'Удаленный каталог для загрузки'),
	'UPLOAD_STRICT' => array ( 'Prompt'=>'Требовать строгой проверки ключа хоста для загрузки SFTP'),
	'UPLOAD_TIMEOUT' => array ( 'Prompt'=>'Сколько времени требуется для передачи каждого файла'),
	'UPLOAD_USER' => array ( 'Prompt'=>'Имя пользователя удаленного сервера'),
	'URL' => array ( 'Prompt'=>'URL-адрес вашей установки ZoneMinder'),
	'USER_SELF_EDIT' => array ( 'Prompt'=>'Разрешить непривилегированным пользователям изменять свои данные'),
	'USE_DEEP_STORAGE' => array ( 'Prompt'=>'Использование глубокой иерархии файловой системы для событий'),
	'V4L_MULTI_BUFFER' => array ( 'Prompt'=>'Используйте более одного буфера для видеоустройств 4 Linux'),
	'WATCH_CHECK_INTERVAL' => array ( 'Prompt'=>'Как часто проверять, что демоны (демоны) захвата не были заблокированы'),
	'WATCH_MAX_DELAY' => array ( 'Prompt'=>'Максимально допустимая задержка с момента последнего снятого изображения'),
	'WEB_ALARM_SOUND' => array ( 'Prompt'=>'Звук для воспроизведения при тревоге должен быть помещен в каталог звуков'),
	'WEB_ANIMATE_THUMBS' => array ( 'Prompt'=>'Увеличивайте масштаб и показывайте прямую трансляцию при наведении курсора мыши на миниатюру монитора'),
	'WEB_COMPACT_MONTAGE' => array ( 'Prompt'=>'Уменьшите объем монтажного вида, скрыв дополнительные детали'),
	'WEB_CONSOLE_BANNER' => array ( 'Prompt'=>'Произвольное текстовое сообщение в верхней части консоли'),
	'WEB_EVENTS_PER_PAGE' => array ( 'Prompt'=>'Сколько событий нужно перечислить на странице в постраничном режиме'),
	'WEB_EVENT_DISK_SPACE' => array ( 'Prompt'=>'Отображение дискового пространства, используемого для каждого события.'),
	'WEB_EVENT_SORT_FIELD' => array ( 'Prompt'=>'Поле по умолчанию, по которому сортируются списки событий'),
	'WEB_EVENT_SORT_ORDER' => array ( 'Prompt'=>'Порядок по умолчанию, по которому сортируются списки событий'),
	'WEB_FILTER_SOURCE' => array ( 'Prompt'=>'Как отфильтровать информацию в столбце источник.'),
	'WEB_H_AJAX_TIMEOUT' => array ( 'Prompt'=>'Как долго ждать ответов на запросы Ajax (мс)'),
	'WEB_H_CAN_STREAM' => array ( 'Prompt'=>'Игнорировать автоматическое определение возможности потоковой передачи (прямой эфир) в браузере'),
	'WEB_H_DEFAULT_RATE' => array ( 'Prompt'=>'Какой коэффициент частоты воспроизведения по умолчанию применяется к визуализациям событий (%)'),
	'WEB_H_DEFAULT_SCALE' => array ( 'Prompt'=>'Какой коэффициент масштабирования по умолчанию применяется к представлениям "прямой эфир" или "событие" (%)'),
	'WEB_H_EVENTS_VIEW' => array ( 'Prompt'=>'Каким должно быть представление по умолчанию для различных событий.'),
	'WEB_H_REFRESH_CYCLE' => array ( 'Prompt'=>'Как часто (в секундах) окно отображения цикла переключается на следующий монитор'),
	'WEB_H_REFRESH_EVENTS' => array ( 'Prompt'=>'Как часто (в секундах) обновляется список событий в окне просмотра'),
	'WEB_H_REFRESH_IMAGE' => array ( 'Prompt'=>'Как часто (в секундах) обновляется отображаемое изображение (если не потоковое)'),
	'WEB_H_REFRESH_MAIN' => array ( 'Prompt'=>'Как часто (в секундах) необходимо обновлять главное окно консоли'),
	'WEB_H_REFRESH_NAVBAR' => array ( 'Prompt'=>'Как часто (в секундах) необходимо обновлять заголовок навигации'),
	'WEB_H_REFRESH_STATUS' => array ( 'Prompt'=>'Как часто (в секундах) обновляется статус в окне просмотра'),
	'WEB_H_SCALE_THUMBS' => array ( 'Prompt'=>'Масштабирование эскизов в событиях при изменении пропускной способности по сравнению с масштабированием ЦП'),
	'WEB_H_SHOW_PROGRESS' => array ( 'Prompt'=>'Отображает ход воспроизведения в средстве просмотра событий.'),
	'WEB_H_STREAM_METHOD' => array ( 'Prompt'=>'Какой метод следует использовать для отправки видеопотоков (потоков) в ваш браузер.'),
	'WEB_H_VIDEO_BITRATE' => array ( 'Prompt'=>'Каким должен быть битрейт закодированного видеопотока (потока) '),
	'WEB_H_VIDEO_MAXFPS' => array ( 'Prompt'=>'Какой должна быть максимальная частота кадров для потокового видео'),
	'WEB_ID_ON_CONSOLE' => array ( 'Prompt'=>'Должна ли консоль отображать идентификатор монитора (Ид)'),
	'WEB_LIST_THUMBS' => array ( 'Prompt'=>'Показать мини-миниатюры изображений событий в списке событий'),
	'WEB_LIST_THUMB_HEIGHT' => array ( 'Prompt'=>'Загрузка миниатюр, отображаемых в списке событий'),
	'WEB_LIST_THUMB_WIDTH' => array ( 'Prompt'=>'Ширина миниатюр, отображаемых в списке событий'),
	'WEB_L_AJAX_TIMEOUT' => array ( 'Prompt'=>'Как долго ждать ответов на запросы Ajax (мс)'),
	'WEB_L_CAN_STREAM' => array ( 'Prompt'=>'Игнорировать автоматическое определение возможности потоковой передачи (поток) в браузере'),
	'WEB_L_DEFAULT_RATE' => array ( 'Prompt'=>'Какой коэффициент частоты воспроизведения по умолчанию применяется к визуализациям событий (%)'),
	'WEB_L_DEFAULT_SCALE' => array ( 'Prompt'=>'Какой коэффициент масштабирования по умолчанию применяется к представлениям "прямой эфир" или "событие" (%)'),
	'WEB_L_EVENTS_VIEW' => array ( 'Prompt'=>'Каким должно быть представление по умолчанию для различных событий.'),
	'WEB_L_REFRESH_CYCLE' => array ( 'Prompt'=>'Как часто (в секундах) окно отображения цикла переключается на следующий монитор'),
	'WEB_L_REFRESH_EVENTS' => array ( 'Prompt'=>'Как часто (в секундах) обновляется список событий в окне просмотра'),
	'WEB_L_REFRESH_IMAGE' => array ( 'Prompt'=>'Как часто (в секундах) обновляется отображаемое изображение (если не потоковое)'),
	'WEB_L_REFRESH_MAIN' => array ( 'Prompt'=>'Как часто (в секундах) необходимо обновлять главное окно консоли'),
	'WEB_L_REFRESH_NAVBAR' => array ( 'Prompt'=>'Как часто (в секундах) необходимо обновлять заголовок навигации'),
	'WEB_L_REFRESH_STATUS' => array ( 'Prompt'=>'Как часто (в секундах) обновляется статус в окне просмотра'),
	'WEB_L_SCALE_THUMBS' => array ( 'Prompt'=>'Масштабирование эскизов в событиях при изменении пропускной способности по сравнению с масштабированием ЦП'),
	'WEB_L_SHOW_PROGRESS' => array ( 'Prompt'=>'Отображает ход воспроизведения в средстве просмотра событий.'),
	'WEB_L_STREAM_METHOD' => array ( 'Prompt'=>'Какой метод следует использовать для отправки видеопотоков (потоков) в ваш браузер.'),
	'WEB_L_VIDEO_BITRATE' => array ( 'Prompt'=>'Каким должен быть битрейт закодированного видеопотока (потока) '),
	'WEB_L_VIDEO_MAXFPS' => array ( 'Prompt'=>'Какой должна быть максимальная частота кадров для потокового видео'),
	'WEB_M_AJAX_TIMEOUT' => array ( 'Prompt'=>'Как долго ждать ответов на запросы Ajax (мс)'),
	'WEB_M_CAN_STREAM' => array ( 'Prompt'=>'Игнорировать автоматическое определение возможности потоковой передачи (прямой эфир) в браузере'),
	'WEB_M_DEFAULT_RATE' => array ( 'Prompt'=>'Какой коэффициент частоты воспроизведения по умолчанию применяется к визуализациям событий (%)'),
	'WEB_M_DEFAULT_SCALE' => array ( 'Prompt'=>'Какой коэффициент масштабирования по умолчанию применяется к представлениям "прямой эфир" или "событие" (%)'),
	'WEB_M_EVENTS_VIEW' => array ( 'Prompt'=>'Каким должно быть представление по умолчанию для различных событий.'),
	'WEB_M_REFRESH_CYCLE' => array ( 'Prompt'=>'Как часто (в секундах) окно отображения цикла переключается на следующий монитор'),
	'WEB_M_REFRESH_EVENTS' => array ( 'Prompt'=>'Как часто (в секундах) обновляется список событий в окне просмотра'),
	'WEB_M_REFRESH_IMAGE' => array ( 'Prompt'=>'Как часто (в секундах) обновляется отображаемое изображение (если не потоковое)'),
	'WEB_M_REFRESH_MAIN' => array ( 'Prompt'=>'Как часто (в секундах) необходимо обновлять главное окно консоли'),
	'WEB_M_REFRESH_NAVBAR' => array ( 'Prompt'=>'Как часто (в секундах) необходимо обновлять заголовок навигации'),
	'WEB_M_REFRESH_STATUS' => array ( 'Prompt'=>'Как часто (в секундах) обновляется статус в окне просмотра'),
	'WEB_M_SCALE_THUMBS' => array ( 'Prompt'=>'Масштабирование эскизов в событиях при изменении пропускной способности по сравнению с масштабированием ЦП'),
	'WEB_M_SHOW_PROGRESS' => array ( 'Prompt'=>'Отображает ход воспроизведения в средстве просмотра событий.'),
	'WEB_M_STREAM_METHOD' => array ( 'Prompt'=>'Какой метод следует использовать для отправки видеопотоков (потоков) в ваш браузер.'),
	'WEB_M_VIDEO_BITRATE' => array ( 'Prompt'=>'Каким должен быть битрейт закодированного видеопотока (потока) '),
	'WEB_M_VIDEO_MAXFPS' => array ( 'Prompt'=>'Какой должна быть максимальная частота кадров для потокового видео'),
	'WEB_NAVBAR_TYPE' => array ( 'Prompt'=>'Стиль панели навигации веб-консоли'),
	'WEB_POPUP_ON_ALARM' => array ( 'Prompt'=>'Должно ли окно монитора выйти на передний план в случае возникновения аварийного сигнала'),
	'WEB_SOUND_ON_ALARM' => array ( 'Prompt'=>'Должно ли окно монитора воспроизводить звук в случае срабатывания будильника'),
	'WEB_TITLE' => array ( 'Prompt'=>'Показывать заголовок, если сайт ссылается на себя.'),
	'WEB_TITLE_PREFIX' => array ( 'Prompt'=>'Префикс заголовка, отображаемый в каждом окне'),
	'WEB_USE_OBJECT_TAGS' => array ( 'Prompt'=>'Упаковать в теги объекта атрибут-встроенный-для мультимедийного контента'),
	'WEB_XFRAME_WARN' => array ( 'Prompt'=>'Предупреждать, когда параметры X-Frame на веб-сайте config_trured с тем же источником'),
	'WEIGHTED_ALARM_CENTRES' => array ( 'Prompt'=>'Используйте взвешенный алгоритм для расчета центра сигнала тревоги'),
	'X10_DB_RELOAD_INTERVAL' => array ( 'Prompt'=>'Как часто (в секундах) демон (демон) X10 перезагружает мониторы из базы данных'),
	'X10_DEVICE' => array ( 'Prompt'=>'К какому устройству подключен ваш контроллер X10'),
	'X10_HOUSE_CODE' => array ( 'Prompt'=>'Какой домашний код X10 следует использовать'),

//   ***************End Prompts **********************


);

?>
