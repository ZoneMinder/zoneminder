<?php
//
// ZoneMinder web Polish language file, $Date$, $Revision$
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
//
// ZoneMinder Polish Translation by Robert Krysztof
// 2016-08-25 Updated by Dawid Kasza > dawid.kasza@gmail.com
//
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
//setlocale( LC_ALL, 'pl_PL:UTF-8' ); // All locale settings pre-4.3.0
// setlocale( LC_ALL, 'pl_PL:UTF-8' );   // All locale settings 4.3.0 and after
//setlocale( LC_CTYPE, 'pl_PL:UTF-8' ); // Character class settings 4.3.0 and after
//setlocale( LC_TIME, 'pl_PL:UTF-8' );  // Date and time formatting 4.3.0 and after

//
// Date and time formats, specific to this language
//

define( "DATE_FMT_CONSOLE_LONG", "%d.%m.%Y, %H:%M" );			// This is the main console date/time, date() or strftime() format
define( "DATE_FMT_CONSOLE_SHORT", "%H:%m" );                    // This is the xHTML console date/time, date() or strftime() format

define( "STRF_FMT_DATETIME", "%b. %e. %Y., %H:%M" );            // Strftime locale aware format for dates with times
define( "STRF_FMT_DATE", "%b. %e. %Y." );                       // Strftime locale aware format for dates without times
define( "STRF_FMT_TIME", "%H:%m:%S" );                          // Strftime locale aware format for times without dates

define( "STRF_FMT_DATETIME_SHORT", "%y/%m/%d %H:%M:%S" );       // Strftime shorter format for dates with time
define( "STRF_FMT_DATETIME_SHORTER", "%m.%d. %H:%M:%S" );       // Strftime shorter format for dates with time, used where space is tight (events list)

// Simple String Replacements
$SLANG = array(
    '24BitColour'          => 'Kolor (24 bity)',
    '32BitColour'          => 'Kolor (32 bity)',          // Added - 2011-06-15
    '8BitGrey'             => 'Cz/b (8 bitów)',
    'Action'               => 'Działanie:',
    'Actual'               => 'Domyślna',
    'AddNewControl'        => 'Add New Control',
    'AddNewMonitor'        => 'Dodaj nowy monitor',
    'AddNewServer'         => 'Add New Server',         // Added - 2018-08-30
    'AddNewStorage'        => 'Add New Storage',        // Added - 2018-08-30
    'AddNewUser'           => 'Dodaj użytkownika',
    'AddNewZone'           => 'Dodaj nową strefę',
    'Alarm'                => 'Alarm',
    'AlarmBrFrames'        => 'Ramki<br/>alarmowe',
    'AlarmFrame'           => 'Ramka alarmowa',
    'AlarmFrameCount'      => 'Alarm Frame Count',
    'AlarmLimits'          => 'Ograniczenia alarmu',
    'AlarmMaximumFPS'      => 'Alarm Maximum FPS',
    'AlarmPx'              => 'Alarm Px',
    'AlarmRGBUnset'        => 'You must set an alarm RGB colour',
    'AlarmRefImageBlendPct'=> 'Alarm Reference Image Blend %ge', // Added - 2015-04-18
    'Alert'                => 'Gotowość',
    'All'                  => 'Wszystko',
    'AnalysisFPS'          => 'Analysis FPS',           // Added - 2015-07-22
    'AnalysisUpdateDelay'  => 'Analysis Update Delay',  // Added - 2015-07-23
    'Apply'                => 'Zastosuj',
    'ApplyingStateChange'  => 'Zmieniam stan pracy',
    'ArchArchived'         => 'Tylko zarchiwizowane',
    'ArchUnarchived'       => 'Tylko niezarchiwizowane',
    'Archive'              => 'Dodaj do archiwum',
    'Archived'             => 'Zarchiwizowane',
    'Area'                 => 'Obszar',
    'AreaUnits'            => 'Obszar (px/%)',
    'AttrAlarmFrames'      => 'Ramki alarmowe',
    'AttrArchiveStatus'    => 'Status archiwum',
    'AttrAvgScore'         => 'Śred. wynik',
    'AttrCause'            => 'Powód',
    'AttrDiskBlocks'       => 'Dysk Bloki',
    'AttrDiskPercent'      => 'Procent zajętości',
    'AttrDiskSpace'        => 'Disk Space',             // Added - 2018-08-30
    'AttrDuration'         => 'Czas trwania',
    'AttrEndDate'          => 'End Date',               // Added - 2018-08-30
    'AttrEndDateTime'      => 'End Date/Time',          // Added - 2018-08-30
    'AttrEndTime'          => 'End Time',               // Added - 2018-08-30
    'AttrEndWeekday'       => 'End Weekday',            // Added - 2018-08-30
    'AttrFilterServer'     => 'Server Filter is Running On', // Added - 2018-08-30
    'AttrFrames'           => 'Klatki',
    'AttrId'               => 'Id',
    'AttrMaxScore'         => 'Maks. wynik',
    'AttrMonitorId'        => 'Nr monitora',
    'AttrMonitorName'      => 'Nazwa monitora',
    'AttrMonitorServer'    => 'Server Monitor is Running On', // Added - 2018-08-30
    'AttrName'             => 'Nazwa',
    'AttrNotes'            => 'Notes',
    'AttrStartDate'        => 'Start Date',             // Added - 2018-08-30
    'AttrStartDateTime'    => 'Start Date/Time',        // Added - 2018-08-30
    'AttrStartTime'        => 'Start Time',             // Added - 2018-08-30
    'AttrStartWeekday'     => 'Start Weekday',          // Added - 2018-08-30
    'AttrStateId'          => 'Run State',              // Added - 2018-08-30
    'AttrStorageArea'      => 'Storage Area',           // Added - 2018-08-30
    'AttrStorageServer'    => 'Server Hosting Storage', // Added - 2018-08-30
    'AttrSystemLoad'       => 'Obiążenie systemu',
    'AttrTotalScore'       => 'Całkowity wynik',
    'Auto'                 => 'Auto',
    'AutoStopTimeout'      => 'Auto Stop Timeout',
    'Available'            => 'Dostępne',              // Added - 2009-03-31
    'AvgBrScore'           => 'Śred.<br/>wynik',
    'Background'           => 'Działa w tle',
    'BackgroundFilter'     => 'Uruchom filtr w tle',
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
    'BadNameChars'         => 'Nazwy mogą zawierać tylko litery, cyfry oraz myślnik i podkreślenie',
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
    'Bandwidth'            => 'Przepustowość',
    'BandwidthHead'        => 'Przepustowość',	// This is the end of the bandwidth status on the top of the console, different in many language due to phrasing
    'BlobPx'               => 'Plamka Px',
    'BlobSizes'            => 'Rozmiary plamek',
    'Blobs'                => 'Plamki',
    'Brightness'           => 'Jaskrawość',
    'Buffer'               => 'Bufor',                 // Added - 2015-04-18
    'Buffers'              => 'Bufory',
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
    'Cancel'               => 'Anuluj',
    'CancelForcedAlarm'    => 'Anuluj wymuszony alarm',
    'CaptureHeight'        => 'Wysokość obrazu',
    'CaptureMethod'        => 'Metoda przechwytywania',         // Added - 2009-02-08
    'CapturePalette'       => 'Paleta kolorów obrazu',
    'CaptureResolution'    => 'Capture Resolution',     // Added - 2015-04-18
    'CaptureWidth'         => 'Szerokość obrazu',
    'Cause'                => 'Przyczyna',
    'CheckMethod'          => 'Metoda sprawdzenia alarmu',
    'ChooseDetectedCamera' => 'Choose Detected Camera', // Added - 2009-03-31
    'ChooseFilter'         => 'Wybierz filtr',
    'ChooseLogFormat'      => 'Choose a log format',    // Added - 2011-06-17
    'ChooseLogSelection'   => 'Choose a log selection', // Added - 2011-06-17
    'ChoosePreset'         => 'Choose Preset',
    'Clear'                => 'Wyczyść',                  // Added - 2011-06-16
    'CloneMonitor'         => 'Clone',                  // Added - 2018-08-30
    'Close'                => 'Zamknij',
    'Colour'               => 'Nasycenie',
    'Command'              => 'Polecenie',
    'Component'            => 'Komponent',              // Added - 2011-06-16
    'ConcurrentFilter'     => 'Run filter concurrently', // Added - 2018-08-30
    'Config'               => 'Konfiguracja',
    'ConfiguredFor'        => 'Ustawiona',
    'ConfirmDeleteEvents'  => 'Jesteś pewien, że chcesz usunąć zaznaczone zdarzenia?',
    'ConfirmPassword'      => 'PotwierdŹ hasło',
    'ConjAnd'              => 'i',
    'ConjOr'               => 'lub',
    'Console'              => 'Konsola',
    'ContactAdmin'         => 'Skontaktuj się z Twoim adminstratorem w sprawie szczegółów.',
    'Continue'             => 'Continue',
    'Contrast'             => 'Kontrast',
    'Control'              => 'Control',
    'ControlAddress'       => 'Control Address',
    'ControlCap'           => 'Control Capability',
    'ControlCaps'          => 'Control Capabilities',
    'ControlDevice'        => 'Control Device',
    'ControlType'          => 'Control Type',
    'Controllable'         => 'Controllable',
    'Current'              => 'Obecny',                // Added - 2015-04-18
    'Cycle'                => 'Cykl',
    'CycleWatch'           => 'Cykl podglądu',
    'DateTime'             => 'Data/Czas',              // Added - 2011-06-16
    'Day'                  => 'Dzień',
    'Debug'                => 'Debug',
    'DefaultRate'          => 'Default Rate',
    'DefaultScale'         => 'Skala domyślna',
    'DefaultView'          => 'Widok domyślny',
    'Deinterlacing'        => 'Usuwanie przeplotu',          // Added - 2015-04-18
    'Delay'                => 'Opóźnienie',                  // Added - 2015-04-18
    'Delete'               => 'Usuń',
    'DeleteAndNext'        => 'Usuń &amp; następny',
    'DeleteAndPrev'        => 'Usuń &amp; poprzedni',
    'DeleteSavedFilter'    => 'Usuń zapisany filtr',
    'Description'          => 'Opis',
    'DetectedCameras'      => 'Wykryte kamery',       // Added - 2009-03-31
    'DetectedProfiles'     => 'Wykryte profile',      // Added - 2015-04-18
    'Device'               => 'Urządzenie',                 // Added - 2009-02-08
    'DeviceChannel'        => 'Numer wejścia w urządzeniu',
    'DeviceFormat'         => 'System TV',
    'DeviceNumber'         => 'Numer urządzenia',
    'DevicePath'           => 'Ścieżka urządzenia',
    'Devices'              => 'Urządzenia',
    'Dimensions'           => 'Rozmiary',
    'DisableAlarms'        => 'Wyłącz alarm',
    'Disk'                 => 'Dysk',
    'Display'              => 'Wygląd',                // Added - 2011-01-30
    'Displaying'           => 'Wyświetlanie',             // Added - 2011-06-16
    'DoNativeMotionDetection'=> 'Do Native Motion Detection',
    'Donate'               => 'Please Donate',
    'DonateAlready'        => 'No, I\'ve already donated',
    'DonateEnticement'     => 'You\'ve been running ZoneMinder for a while now and hopefully are finding it a useful addition to your home or workplace security. Although ZoneMinder is, and will remain, free and open source, it costs money to develop and support. If you would like to help support future development and new features then please consider donating. Donating is, of course, optional but very much appreciated and you can donate as much or as little as you like.<br><br>If you would like to donate please select the option below or go to http://www.zoneminder.com/donate.html in your browser.<br><br>Thank you for using ZoneMinder and don\'t forget to visit the forums on ZoneMinder.com for support or suggestions about how to make your ZoneMinder experience even better.',
    'DonateRemindDay'      => 'Not yet, remind again in 1 day',
    'DonateRemindHour'     => 'Not yet, remind again in 1 hour',
    'DonateRemindMonth'    => 'Not yet, remind again in 1 month',
    'DonateRemindNever'    => 'No, I don\'t want to donate, never remind',
    'DonateRemindWeek'     => 'Not yet, remind again in 1 week',
    'DonateYes'            => 'Yes, I\'d like to donate now',
    'Download'             => 'Pobierz',
    'DownloadVideo'        => 'Download Video',         // Added - 2018-08-30
    'DuplicateMonitorName' => 'Duplicate Monitor Name', // Added - 2009-03-31
    'Duration'             => 'Czas trwania',
    'Edit'                 => 'Edycja',
    'EditLayout'           => 'Edit Layout',            // Added - 2018-08-30
    'Email'                => 'Email',
    'EnableAlarms'         => 'Enable Alarms',
    'Enabled'              => 'Aktywny',
    'EnterNewFilterName'   => 'Wpisz nową nazwę filtra',
    'Error'                => 'Błąd',
    'ErrorBrackets'        => 'Błąd, proszę sprawdzić ilość nawiasów otwierających i zamykających',
    'ErrorValidValue'      => 'Błąd, proszę sprawdzić czy wszystkie warunki mają poprawne wartości',
    'Etc'                  => 'itp',
    'Event'                => 'Zdarzenie',
    'EventFilter'          => 'Filtr zdarzeń',
    'EventId'              => 'Id zdarzenia',
    'EventName'            => 'Event Name',
    'EventPrefix'          => 'Event Prefix',
    'Events'               => 'Zdarzenia',
    'Exclude'              => 'Wyklucz',
    'Execute'              => 'Wykonaj',
    'Exif'                 => 'Embed EXIF data into image', // Added - 2018-08-30
    'Export'               => 'Eksport',
    'ExportDetails'        => 'Eksport szczegółów zdarzenia',
    'ExportFailed'         => 'Eksport nie powiódł się',
    'ExportFormat'         => 'Rodzaj archiwum',
    'ExportFormatTar'      => 'Tar',
    'ExportFormatZip'      => 'Zip',
    'ExportFrames'         => 'Eksport szczgółów klatki',
    'ExportImageFiles'     => 'Eksport plików obrazowych (klatek)',
    'ExportLog'            => 'Eksport logów',             // Added - 2011-06-17
    'ExportMiscFiles'      => 'Eksport innych plików (jeśli dostępne)',
    'ExportOptions'        => 'Opcje eksportu',
    'ExportSucceeded'      => 'Eksport zakończony pomyślnie',       // Added - 2009-02-08
    'ExportVideoFiles'     => 'Eksport plików video (jeśli dostępne)',
    'Exporting'            => 'Eksportowanie',
    'FPS'                  => 'fps',
    'FPSReportInterval'    => 'Raport (ramek/s)',
    'FTP'                  => 'FTP',
    'Far'                  => 'Far',
    'FastForward'          => 'Fast Forward',
    'Feed'                 => 'Dostarcz',
    'Ffmpeg'               => 'Ffmpeg',                 // Added - 2009-02-08
    'File'                 => 'Plik',
    'Filter'               => 'Filter',                 // Added - 2015-04-18
    'FilterArchiveEvents'  => 'Archiwizuj wszystkie pasujące',
    'FilterDeleteEvents'   => 'Usuwaj wszystkie pasujące',
    'FilterEmailEvents'    => 'Wysyłaj pocztą wszystkie pasujące',
    'FilterExecuteEvents'  => 'Wywołuj komendę dla wszystkich pasujących',
    'FilterLog'            => 'Filtr logów',             // Added - 2015-04-18
    'FilterMessageEvents'  => 'Wyświetlaj komunikat na wszystkie pasujące',
    'FilterMoveEvents'     => 'Move all matches',       // Added - 2018-08-30
    'FilterPx'             => 'Filtr Px',
    'FilterUnset'          => 'You must specify a filter width and height',
    'FilterUpdateDiskSpace'=> 'Update used disk space', // Added - 2018-08-30
    'FilterUploadEvents'   => 'Wysyłaj wszystkie pasujące',
    'FilterVideoEvents'    => 'Utwórz nagranie dla zaznaczonych',
    'Filters'              => 'Filtry',
    'First'                => 'Pierwszy',
    'FlippedHori'          => 'Odwróć poziomo',
    'FlippedVert'          => 'Odwróć pionowo',
    'FnMocord'              => 'Mocord',            // Added 2013.08.16.
    'FnModect'              => 'Modect',            // Added 2013.08.16.
    'FnMonitor'             => 'Monitor',            // Added 2013.08.16.
    'FnNodect'              => 'Nodect',            // Added 2013.08.16.
    'FnNone'                => 'None',            // Added 2013.08.16.
    'FnRecord'              => 'Record',            // Added 2013.08.16.
    'Focus'                => 'Focus',
    'ForceAlarm'           => 'Wymuś alarm',
    'Format'               => 'Format',
    'Frame'                => 'Ramka',
    'FrameId'              => 'Nr ramki',
    'FrameRate'            => 'Tempo ramek',
    'FrameSkip'            => 'Pomiń ramkę',
    'Frames'               => 'Ramki',
    'Func'                 => 'Funkcja',
    'Function'             => 'Funkcja',
    'Gain'                 => 'Gain',
    'General'              => 'General',
    'GenerateDownload'     => 'Generate Download',      // Added - 2018-08-30
    'GenerateVideo'        => 'Generowanie Video',
    'GeneratingVideo'      => 'Generuję Video',
    'GoToZoneMinder'       => 'PrzejdŹ na ZoneMinder.com',
    'Grey'                 => 'Cz/b',
    'Group'                => 'Grupa',
    'Groups'               => 'Grupy',
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
    'High'                 => 'wysoka',
    'HighBW'               => 'Wys.&nbsp;prz.',
    'Home'                 => 'Home',
    'Hostname'             => 'Hostname',               // Added - 2018-08-30
    'Hour'                 => 'Godzina',
    'Hue'                  => 'Odcień',
    'Id'                   => 'Nr',
    'Idle'                 => 'Bezczynny',
    'Ignore'               => 'Ignoruj',
    'Image'                => 'Obraz',
    'ImageBufferSize'      => 'Rozmiar bufora obrazu (ramek)',
    'Images'               => 'Obrazy',
    'In'                   => 'In',
    'Include'              => 'Dołącz',
    'Inverted'             => 'Odwrócony',
    'Iris'                 => 'Iris',
    'KeyString'            => 'Key String',
    'Label'                => 'Label',
    'Language'             => 'Język',
    'Last'                 => 'Ostatni',
    'Layout'               => 'Layout',                 // Added - 2009-02-08
    'Level'                => 'Level',                  // Added - 2011-06-16
    'Libvlc'               => 'Libvlc',
    'LimitResultsPost'     => 'wyników;', // This is used at the end of the phrase 'Limit to first N results only'
    'LimitResultsPre'      => 'Ogranicz do początkowych', // This is used at the beginning of the phrase 'Limit to first N results only'
    'Line'                 => 'Linia',                   // Added - 2011-06-16
    'LinkedMonitors'       => 'Połączone monitory',
    'List'                 => 'Lista',
    'ListMatches'          => 'List Matches',           // Added - 2018-08-30
    'Load'                 => 'Obc.',
    'Local'                => 'Lokalny',
    'Log'                  => 'Logi',                    // Added - 2011-06-16
    'LoggedInAs'           => 'Zalogowany jako',
    'Logging'              => 'Logowanie',                // Added - 2011-06-16
    'LoggingIn'            => 'Logowanie',
    'Login'                => 'Login',
    'Logout'               => 'Wyloguj',
    'Logs'                 => 'Logi',                   // Added - 2011-06-17
    'Low'                  => 'niska',
    'LowBW'                => 'Nis.&nbsp;prz.',
    'Main'                 => 'Main',
    'Man'                  => 'Man',
    'Manual'               => 'Manual',
    'Mark'                 => 'Znacznik',
    'Max'                  => 'Maks.',
    'MaxBandwidth'         => 'Max Bandwidth',
    'MaxBrScore'           => 'Maks.<br/>wynik',
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
    'MaximumFPS'           => 'Maks. FPS',
    'Medium'               => 'średnia',
    'MediumBW'             => 'Śred.&nbsp;prz.',
    'Message'              => 'Treść',                // Added - 2011-06-16
    'MinAlarmAreaLtMax'    => 'Minimum alarm area should be less than maximum',
    'MinAlarmAreaUnset'    => 'You must specify the minimum alarm pixel count',
    'MinBlobAreaLtMax'     => 'Minimalny obszar plamki powinien być mniejszy od maksymalnego obszaru plamki',
    'MinBlobAreaUnset'     => 'You must specify the minimum blob pixel count',
    'MinBlobLtMinFilter'   => 'Minimum blob area should be less than or equal to minimum filter area',
    'MinBlobsLtMax'        => 'Najmniejsze plamki powinny być mniejsze od największych plamek' ,
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
    'MinPixelThresLtMax'   => 'Najmniejsze progi pikseli powinny być mniejsze od największych progów pikseli',
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
    'Misc'                 => 'Inne',
    'Mode'                 => 'Tryb',                   // Added - 2015-04-18
    'Monitor'              => 'Monitor',
    'MonitorIds'           => 'Numery&nbsp;monitorów',
    'MonitorPreset'        => 'Ustawienia predefiniowane',
    'MonitorPresetIntro'   => 'Select an appropriate preset from the list below.<br><br>Please note that this may overwrite any values you already have configured for this monitor.<br><br>',
    'MonitorProbe'         => 'Monitor Probe',          // Added - 2009-03-31
    'MonitorProbeIntro'    => 'The list below shows detected analog and network cameras and whether they are already being used or available for selection.<br/><br/>Select the desired entry from the list below.<br/><br/>Please note that not all cameras may be detected and that choosing a camera here may overwrite any values you already have configured for the current monitor.<br/><br/>', // Added - 2009-03-31
    'Monitors'             => 'Monitory',
    'Montage'              => 'Montaż',
    'MontageReview'        => 'Montage Review',         // Added - 2018-08-30
    'Month'                => 'Miesiąc',
    'More'                 => 'Pokaż więcej',                   // Added - 2011-06-16
    'MotionFrameSkip'      => 'Motion Frame Skip',
    'Move'                 => 'Move',
    'Mtg2widgrd'           => '2-wide grid',              // Added 2013.08.15.
    'Mtg3widgrd'           => '3-wide grid',              // Added 2013.08.15.
    'Mtg3widgrx'           => '3-wide grid, scaled, enlarge on alarm',              // Added 2013.08.15.
    'Mtg4widgrd'           => '4-wide grid',              // Added 2013.08.15.
    'MtgDefault'           => 'Default',              // Added 2013.08.15.
    'MustBeGe'             => 'musi być większe lub równe od',
    'MustBeLe'             => 'musi być mniejsze lub równe od',
    'MustConfirmPassword'  => 'Musisz potwierdzić hasło',
    'MustSupplyPassword'   => 'Musisz podać hasło',
    'MustSupplyUsername'   => 'Musisz podać nazwę użytkownika',
    'Name'                 => 'Nazwa',
    'Near'                 => 'Near',
    'Network'              => 'Sieć',
    'New'                  => 'Nowy',
    'NewGroup'             => 'Nowa grupa',
    'NewLabel'             => 'Nowa etykieta',
    'NewPassword'          => 'Nowe hasło',
    'NewState'             => 'Nowy stan',
    'NewUser'              => 'nowy',
    'Next'                 => 'Następny',
    'No'                   => 'Nie',
    'NoDetectedCameras'    => 'Nie wykryto kamer',    // Added - 2009-03-31
    'NoDetectedProfiles'   => 'No Detected Profiles',   // Added - 2018-08-30
    'NoFramesRecorded'     => 'Brak zapisanych ramek dla tego zdarzenia',
    'NoGroup'              => 'Brak grupy',
    'NoSavedFilters'       => 'BrakZapisanychFiltrów',
    'NoStatisticsRecorded' => 'Brak zapisanych statystyk dla tego zdarzenia/ramki',
    'None'                 => 'Brak',
    'NoneAvailable'        => 'Niedostępne',
    'Normal'               => 'Normalny',
    'Notes'                => 'Uwagi',
    'NumPresets'           => 'Num Presets',
    'Off'                  => 'Off',
    'On'                   => 'On',
    'OnvifCredentialsIntro'=> 'Please supply user name and password for the selected camera.<br/>If no user has been created for the camera then the user given here will be created with the given password.<br/><br/>', // Added - 2015-04-18
    'OnvifProbe'           => 'ONVIF',                  // Added - 2015-04-18
    'OnvifProbeIntro'      => 'The list below shows detected ONVIF cameras and whether they are already being used or available for selection.<br/><br/>Select the desired entry from the list below.<br/><br/>Please note that not all cameras may be detected and that choosing a camera here may overwrite any values you already have configured for the current monitor.<br/><br/>', // Added - 2015-04-18
    'OpEq'                 => 'równy',
    'OpGt'                 => 'większe od',
    'OpGtEq'               => 'większe lub równe od',
    'OpIn'                 => 'w zestawie',
    'OpIs'                 => 'is',                     // Added - 2018-08-30
    'OpIsNot'              => 'is not',                 // Added - 2018-08-30
    'OpLt'                 => 'mniejsze od',
    'OpLtEq'               => 'mniejsze lub równe od',
    'OpMatches'            => 'pasujące',
    'OpNe'                 => 'różne od',
    'OpNotIn'              => 'brak w zestawie',
    'OpNotMatches'         => 'nie pasujące',
    'Open'                 => 'Otwórz',
    'OptionHelp'           => 'OpcjePomoc',
    'OptionRestartWarning' => 'Te zmiany nie przyniosą natychmiastowego efektu\ndopóki system pracuje. Kiedy zakończysz robić zmiany\nproszę koniecznie zrestartować ZoneMinder.',
    'OptionalEncoderParam' => 'Optional Encoder Parameters', // Added - 2018-08-30
    'Options'              => 'Opcje',
    'OrEnterNewName'       => 'lub wpisz nową nazwę',
    'Order'                => 'Kolejność',
    'Orientation'          => 'Orientacja',
    'Out'                  => 'Out',
    'OverwriteExisting'    => 'Nadpisz istniejące',
    'Paged'                => 'Stronicowane',
    'Pan'                  => 'Pan',
    'PanLeft'              => 'Pan Left',
    'PanRight'             => 'Pan Right',
    'PanTilt'              => 'Pan/Tilt',
    'Parameter'            => 'Parametr',
    'Password'             => 'Hasło',
    'PasswordsDifferent'   => 'Hasła: nowe i potwierdzone są różne!',
    'Paths'                => 'Ścieżki',
    'Pause'                => 'Pauza',
    'Phone'                => 'Telefon',
    'PhoneBW'              => 'Tel.&nbsp;prz.',
    'Pid'                  => 'PID',                    // Added - 2011-06-16
    'PixelDiff'            => 'Pixel Diff',
    'Pixels'               => 'pikseli',
    'Play'                 => 'Odtwórz',
    'PlayAll'              => 'Play All',
    'PleaseWait'           => 'Proszę czekać',
    'Plugins'              => 'Plugins',
    'Point'                => 'Point',
    'PostEventImageBuffer' => 'Bufor obrazów po zdarzeniu',
    'PreEventImageBuffer'  => 'Bufor obrazów przed zdarzeniem',
    'PreserveAspect'       => 'Preserve Aspect Ratio',
    'Preset'               => 'Preset',
    'Presets'              => 'Presets',
    'Prev'                 => 'Poprzedni',
    'Probe'                => 'Probe',                  // Added - 2009-03-31
    'ProfileProbe'         => 'Stream Probe',           // Added - 2015-04-18
    'ProfileProbeIntro'    => 'The list below shows the existing stream profiles of the selected camera .<br/><br/>Select the desired entry from the list below.<br/><br/>Please note that ZoneMinder cannot configure additional profiles and that choosing a camera here may overwrite any values you already have configured for the current monitor.<br/><br/>', // Added - 2015-04-18
    'Progress'             => 'Postęp',               // Added - 2015-04-18
    'Protocol'             => 'Protocol',
    'RTSPDescribe'         => 'Use RTSP Response Media URL', // Added - 2018-08-30
    'RTSPTransport'        => 'RTSP Transport Protocol', // Added - 2018-08-30
    'Rate'                 => 'Tempo',
    'Real'                 => 'Rzeczywiste',
    'RecaptchaWarning'     => 'Your reCaptcha secret key is invalid. Please correct it, or reCaptcha will not work', // Added - 2018-08-30
    'Record'               => 'Zapis',
    'RecordAudio'          => 'Whether to store the audio stream when saving an event.', // Added - 2018-08-30
    'RefImageBlendPct'     => 'Miks z obrazem odniesienia',
    'Refresh'              => 'Odśwież',
    'Remote'               => 'Zdalny',
    'RemoteHostName'       => 'Nazwa hostu zdalnego',
    'RemoteHostPath'       => 'Scieżka hostu zdalnego ',
    'RemoteHostPort'       => 'Port hostu zdalnego ',
    'RemoteHostSubPath'    => 'Podścieżka hostu zdalnego',    // Added - 2009-02-08
    'RemoteImageColours'   => 'Kolory obrazu zdalnego',
    'RemoteMethod'         => 'Remote Method',          // Added - 2009-02-08
    'RemoteProtocol'       => 'Remote Protocol',        // Added - 2009-02-08
    'Rename'               => 'Zmień nazwę',
    'Replay'               => 'Odtwarzaj',
    'ReplayAll'            => 'Wszystko',
    'ReplayGapless'        => 'Wszystko i powtarzaj',
    'ReplaySingle'         => 'Bieżące zdarzenie',
    'ReportEventAudit'     => 'Audit Events Report',    // Added - 2018-08-30
    'Reset'                => 'Reset',
    'ResetEventCounts'     => 'Kasuj licznik zdarzeń',
    'Restart'              => 'Restart',
    'Restarting'           => 'Restartuję',
    'RestrictedCameraIds'  => 'Numery kamer',
    'RestrictedMonitors'   => 'Restricted Monitors',
    'ReturnDelay'          => 'Return Delay',
    'ReturnLocation'       => 'Return Location',
    'Rewind'               => 'Przewijanie',
    'RotateLeft'           => 'Obróć w lewo',
    'RotateRight'          => 'Obróć w prawo',
    'RunLocalUpdate'       => 'Proszę uruchom skrypt zmupdate.pl w celu aktualizacji', // Added - 2011-05-25
    'RunMode'              => 'Tryb pracy',
    'RunState'             => 'Stan pracy',
    'Running'              => 'Pracuje',
    'Save'                 => 'Zapisz',
    'SaveAs'               => 'Zapisz jako',
    'SaveFilter'           => 'Zapisz filtr',
    'SaveJPEGs'            => 'Save JPEGs',             // Added - 2018-08-30
    'Scale'                => 'Skala',
    'Score'                => 'Wynik',
    'Secs'                 => 'Sekund',
    'Sectionlength'        => 'Długość sekcji',
    'Select'               => 'Wybierz',
    'SelectFormat'         => 'Wybierz format',          // Added - 2011-06-17
    'SelectLog'            => 'Wybierz log',             // Added - 2011-06-17
    'SelectMonitors'       => 'Select Monitors',
    'SelfIntersecting'     => 'Polygon edges must not intersect',
    'Set'                  => 'Set',
    'SetNewBandwidth'      => 'Ustaw nową przepustowość',
    'SetPreset'            => 'Set Preset',
    'Settings'             => 'Ustawienia',
    'ShowFilterWindow'     => 'PokażOknoFiltru',
    'ShowTimeline'         => 'Pokaż oś czasu',
    'SignalCheckColour'    => 'Signal Check Colour',
    'SignalCheckPoints'    => 'Signal Check Points',    // Added - 2018-08-30
    'Size'                 => 'Rozmiar',
    'SkinDescription'      => 'Change the default skin for this computer', // Added - 2011-01-30
    'Sleep'                => 'Sleep',
    'SortAsc'              => 'rosnąco',
    'SortBy'               => 'Sortuj',
    'SortDesc'             => 'malejąco',
    'Source'               => 'Źródło',
    'SourceColours'        => 'Source Colours',         // Added - 2009-02-08
    'SourcePath'           => 'Source Path',            // Added - 2009-02-08
    'SourceType'           => 'Typ Źródła',
    'Speed'                => 'Speed',
    'SpeedHigh'            => 'High Speed',
    'SpeedLow'             => 'Low Speed',
    'SpeedMedium'          => 'Medium Speed',
    'SpeedTurbo'           => 'Turbo Speed',
    'Start'                => 'Start',
    'State'                => 'Stan',
    'Stats'                => 'Statystyki',
    'Status'               => 'Status',
    'StatusConnected'      => 'Capturing',              // Added - 2018-08-30
    'StatusNotRunning'     => 'Not Running',            // Added - 2018-08-30
    'StatusRunning'        => 'Not Capturing',          // Added - 2018-08-30
    'StatusUnknown'        => 'Unknown',                // Added - 2018-08-30
    'Step'                 => 'Krok',
    'StepBack'             => 'Step Back',
    'StepForward'          => 'Step Forward',
    'StepLarge'            => 'Large Step',
    'StepMedium'           => 'Medium Step',
    'StepNone'             => 'No Step',
    'StepSmall'            => 'Small Step',
    'Stills'               => 'Podgląd klatek',
    'Stop'                 => 'Stop',
    'Stopped'              => 'Zatrzymany',
    'StorageArea'          => 'Storage Area',           // Added - 2018-08-30
    'StorageScheme'        => 'Scheme',                 // Added - 2018-08-30
    'Stream'               => 'Odtwarzacz',
    'StreamReplayBuffer'   => 'Stream Replay Image Buffer',
    'Submit'               => 'Zatwierdź',
    'System'               => 'System',
    'SystemLog'            => 'Logi systemu',             // Added - 2011-06-16
    'TargetColorspace'     => 'Target colorspace',      // Added - 2015-04-18
    'Tele'                 => 'Tele',
    'Thumbnail'            => 'Thumbnail',
    'Tilt'                 => 'Tilt',
    'Time'                 => 'Czas',
    'TimeDelta'            => 'Różnica czasu',
    'TimeStamp'            => 'Znak czasu',
    'Timeline'             => 'Oś czasu',
    'TimelineTip1'         => 'Przeciągnij kursor myszki na wykresie, aby wyświetlić obraz migawki i szczegóły zdarzenia.',              // Added 2013.08.15.
    'TimelineTip2'         => 'Kliknij na kolorowe fragmenty wykresu, aby zobaczyć wydarzenie.',              // Added 2013.08.15.
    'TimelineTip3'         => 'Kliknij w tło, aby przybliżyć się do mniejszego okresu opartego wokół wykonanego kliknięcia..',              // Added 2013.08.15.
    'TimelineTip4'         => 'Użyj opcji poniżej, w celu nawigacji.',              // Added 2013.08.15.
    'Timestamp'            => 'Czas',
    'TimestampLabelFormat' => 'Format etykiety czasu',
    'TimestampLabelSize'   => 'Font Size',              // Added - 2018-08-30
    'TimestampLabelX'      => 'Wsp. X etykiety czasu',
    'TimestampLabelY'      => 'Wsp. Y etykiety czasu',
    'Today'                => 'Dziś',
    'Tools'                => 'Narzędzia',
    'Total'                => 'Total',                  // Added - 2011-06-16
    'TotalBrScore'         => 'Całkowity<br/>wynik',
    'TrackDelay'           => 'Track Delay',
    'TrackMotion'          => 'Track Motion',
    'Triggers'             => 'Wyzwalacze',
    'TurboPanSpeed'        => 'Turbo Pan Speed',
    'TurboTiltSpeed'       => 'Turbo Tilt Speed',
    'Type'                 => 'Typ',
    'Unarchive'            => 'Usuń z archiwum',
    'Undefined'            => 'Undefined',              // Added - 2009-02-08
    'Units'                => 'Jednostki',
    'Unknown'              => 'Nieznany',
    'Update'               => 'Update',
    'UpdateAvailable'      => 'Jest dostępne uaktualnienie ZoneMinder ',
    'UpdateNotNecessary'   => 'Nie jest wymagane uaktualnienie',
    'Updated'              => 'Updated',                // Added - 2011-06-16
    'Upload'               => 'Upload',                 // Added - 2011-08-23
    'UseFilter'            => 'Użyj filtru',
    'UseFilterExprsPost'   => '&nbsp;wyrażenie&nbsp;filtru', // This is used at the end of the phrase 'use N filter expressions'
    'UseFilterExprsPre'    => 'Użyj&nbsp;', // This is used at the beginning of the phrase 'use N filter expressions'
    'UsedPlugins'	   => 'Used Plugins',
    'User'                 => 'Użytkownik',
    'Username'             => 'Nazwa użytkownika',
    'Users'                => 'Użytkownicy',
    'V4L'                  => 'V4L',                    // Added - 2015-04-18
    'V4LCapturesPerFrame'  => 'Captures Per Frame',     // Added - 2015-04-18
    'V4LMultiBuffer'       => 'Multi Buffering',        // Added - 2015-04-18
    'Value'                => 'Wartość',
    'Version'              => 'Wersja',
    'VersionIgnore'        => 'Zignoruj tą wersję',
    'VersionRemindDay'     => 'Przypomnij po 1 dniu',
    'VersionRemindHour'    => 'Przypomnij po 1 godzinie',
    'VersionRemindNever'   => 'Nie przypominaj o nowych wersjach',
    'VersionRemindWeek'    => 'Przypomnij po 1 tygodniu',
    'Video'                => 'Eksport Video',
    'VideoFormat'          => 'Format nagrania',
    'VideoGenFailed'       => 'Generowanie filmu Video nie powiodło się!',
    'VideoGenFiles'        => 'Lista wygenerowanych plików:',
    'VideoGenNoFiles'      => 'Nie odnaleziono plików Video',
    'VideoGenParms'        => 'Parametery generowania filmu Video',
    'VideoGenSucceeded'    => 'Wygenerowano pomyślnie!',
    'VideoSize'            => 'Rozmiar filmu Video',
    'VideoWriter'          => 'Video Writer',           // Added - 2018-08-30
    'View'                 => 'Podgląd',
    'ViewAll'              => 'Pokaż wszystko',
    'ViewEvent'            => 'Pokaż zdarzenie',
    'ViewPaged'            => 'Pokaż stronami',
    'Wake'                 => 'Wake',
    'WarmupFrames'         => 'Ignorowane ramki',
    'Watch'                => 'podgląd',
    'Web'                  => 'Sieć',
    'WebColour'            => 'Web Colour',
    'WebSiteUrl'           => 'Website URL',            // Added - 2018-08-30
    'Week'                 => 'Tydzień',
    'White'                => 'Biel',
    'WhiteBalance'         => 'Balans bieli',
    'Wide'                 => 'Wide',
    'X'                    => 'X',
    'X10'                  => 'X10',
    'X10ActivationString'  => 'X10: łańcuch aktywujący',
    'X10InputAlarmString'  => 'X10: łańcuch wejścia alarmu',
    'X10OutputAlarmString' => 'X10: łańcuch wyjścia alarmu',
    'Y'                    => 'Y',
    'Yes'                  => 'Tak',
    'YouNoPerms'           => 'Nie masz uprawnień na dostęp do tego zasobu.',
    'Zone'                 => 'Strefa',
    'ZoneAlarmColour'      => 'Kolor alarmu (Red/Green/Blue)',
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
    'Zones'                => 'Strefy',
    'Zoom'                 => 'Zoom',
    'ZoomIn'               => 'Zoom In',
    'ZoomOut'              => 'Zoom Out',
);

// Complex replacements with formatting and/or placements, must be passed through sprintf
$CLANG = array(
    'CurrentLogin'         => 'Aktualny login \'%1$s\'',
    'EventCount'           => '%1$s %2$s',
    'LastEvents'           => 'Ostatnie %1$s %2$s',
    'LatestRelease'        => 'Najnowsza wersja to v%1$s, Ty posiadasz v%2$s.',
    'MonitorCount'         => '%1$s %2$s',
    'MonitorFunction'      => 'Monitor %1$s Funkcja',
    'RunningRecentVer'     => 'Uruchomiłeś najnowszą wersję ZoneMinder, v%s.',
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
    'Event'                => array( 0=>'Zdarzeń', 1=>'Zdarzenie', 2=>'Zdarzenia'),
    'Monitor'              => array( 0=>'Monitorów', 1=>'Monitor', 2=>'Monitory'),
);

// You will need to choose or write a function that can correlate the plurality string arrays
// with variable counts. This is used to conjugate the Vlang arrays above with a number passed
// in to generate the correct noun form.

// This is an version that could be used in the Polish language
// 
function zmVlang( $langVarArray, $count )
{
    $secondlastdigit = substr( $count, -2, 1 );
    $lastdigit = substr( $count, -1, 1 );
    if ( $count == 1 )
    {
        return( $langVarArray[1] );
    }
    if (($secondlastdigit == 0)|( $secondlastdigit == 1))
    {
        return( $langVarArray[0] );
    }
    if ( $secondlastdigit >= 2)
    {
        switch ( $lastdigit )
        {
            case 0 :
            case 1 :
            case 5 :
            case 6 :
            case 7 :
            case 8 :
            case 9 :
            {
                return( $langVarArray[0] );
                break;
            }
            case 2 :
            case 3 :
            case 4 :
            {
                return( $langVarArray[2] );
                break;
            }
        }
    }
    die( 'BŁĄD! zmVlang nie może skorelowac łańcucha!' );
}

// This is an example of how the function is used in the code which you can uncomment and 
// use to test your custom function.
// $monitors = 12; // Choose any number
// echo $monitors." ";
// echo zmVlang( $zmVlangMonitor, $monitors);

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
