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
// 2019-06-05 Updated by GospoGied  > adm_gospogied(at)poczta.fm
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
    '32BitColour'          => 'Kolor (32 bity)',
    '8BitGrey'             => 'Cz/b (8 bitów)',
    'Action'               => 'Działanie:',
    'Actual'               => 'Domyślna',
    'AddNewControl'        => 'Dodaj nowy kontroler',
    'AddNewMonitor'        => 'Dodaj nowy monitor',
    'AddNewServer'         => 'Dodaj nowy serwer',
    'AddNewStorage'        => 'Dodaj nowy magazyn',
    'AddNewUser'           => 'Dodaj użytkownika',
    'AddNewZone'           => 'Dodaj nową strefę',
    'Alarm'                => 'Alarm',
    'AlarmBrFrames'        => 'Ramki<br/>alarmowe',
    'AlarmFrame'           => 'Ramka alarmowa',
    'AlarmFrameCount'      => 'Ilość ramek alarmowych',
    'AlarmLimits'          => 'Ograniczenia alarmu',
    'AlarmMaximumFPS'      => 'Maksymalny FPS alarmu',
    'AlarmPx'              => 'Alarm Px',
    'AlarmRGBUnset'        => 'Musisz ustawić kolor RGB alarmu',
    'AlarmRefImageBlendPct'=> 'Mieszanie alarmu z obrazem referencyjnym %ge',
    'Alert'                => 'Gotowość',
    'All'                  => 'Wszystko',
    'AnalysisFPS'          => 'FPS analizy',
    'AnalysisUpdateDelay'  => 'Opóźnienie aktualizacji analizy',
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
    'AttrDiskSpace'        => 'Miejsce na dysku',
    'AttrDuration'         => 'Czas trwania',
    'AttrEndDate'          => 'Końcowa data',
    'AttrEndDateTime'      => 'Końcowa data/godzina',
    'AttrEndTime'          => 'Końcowy czas',
    'AttrEndWeekday'       => 'Końcowy tydzień',
    'AttrFilterServer'     => 'Filtr serwera pracuje na',
    'AttrFrames'           => 'Klatki',
    'AttrId'               => 'Nr',
    'AttrMaxScore'         => 'Maks. wynik',
    'AttrMonitorId'        => 'Nr monitora',
    'AttrMonitorName'      => 'Nazwa monitora',
    'AttrMonitorServer'    => 'Monitor serwera pracuje na',
    'AttrName'             => 'Nazwa',
    'AttrNotes'            => 'Notatki',
    'AttrStartDate'        => 'Początkowa data',
    'AttrStartDateTime'    => 'Początkowa data/czas',
    'AttrStartTime'        => 'Początkowy czas',
    'AttrStartWeekday'     => 'Początkowy tydzień',
    'AttrStateId'          => 'Stan działania',
    'AttrStorageArea'      => 'Magazyn',
    'AttrStorageServer'    => 'Serwer hostujący',
    'AttrSystemLoad'       => 'Obciążenie systemu',
    'AttrTotalScore'       => 'Całkowity wynik',
    'Auto'                 => 'Auto',
    'AutoStopTimeout'      => 'Czas automatycznego stopu',
    'Available'            => 'Dostępne',
    'AvgBrScore'           => 'Śred.<br/>wynik',
    'Background'           => 'Działa w tle',
    'BackgroundFilter'     => 'Uruchom filtr w tle',
    'BadAlarmFrameCount'   => 'Liczba ramek alarmowych musi być liczbą całkowitą jeden lub więcej',
    'BadAlarmMaxFPS'       => 'Maks. FPS alarmu musi być dodatnią liczbą całkowitą lub zmiennoprzecinkową',
    'BadAnalysisFPS'       => 'Analiza FPS musi być dodatnią liczbą całkowitą lub zmiennoprzecinkową',
    'BadAnalysisUpdateDelay'=> 'Opóźnienie aktualizacji analizy musi być ustawione na liczbę całkowitą równą zero lub więcej',
    'BadChannel'           => 'Kanał musi być ustawiony na liczbę całkowitą równą zero lub więcej',
    'BadColours'           => 'Kolor docelowy musi być ustawiony na prawidłową wartość',
    'BadDevice'            => 'Urządzenie musi mieć poprawną wartość',
    'BadFPSReportInterval' => 'Liczba buforów interwału raportu FPS musi być liczbą całkowitą równą 0 lub więcej',
    'BadFormat'            => 'Format musi być ustawiony na liczbę całkowitą równą zero lub więcej',
    'BadFrameSkip'         => 'Liczba pomijanych ramek musi być liczbą całkowitą równą zero lub więcej',
    'BadHeight'            => 'Wysokość musi być ustawiona na prawidłową wartość',
    'BadHost'              => 'Host musi być ustawiony na prawidłowy adres IP lub nazwę hosta, nie dołączaj http://',
    'BadImageBufferCount'  => 'Rozmiar bufora obrazu musi być liczbą całkowitą 10 lub większą',
    'BadLabelX'            => 'Współrzędna X etykiety musi być ustawiona na liczbę całkowitą równą zero lub więceje',
    'BadLabelY'            => 'Współrzędna Y etykiety musi być ustawiona na liczbę całkowitą równą zero lub więcej',
    'BadMaxFPS'            => 'Maksymalna liczba klatek na sekundę musi być dodatnią liczbą całkowitą lub zmiennoprzecinkową',
    'BadMotionFrameSkip'   => 'Liczba pomijanych ramek ruchu musi być liczbą całkowitą równą zero lub więcej',
    'BadNameChars'         => 'Nazwy mogą zawierać tylko litery, cyfry oraz myślnik i podkreślenie',
    'BadPalette'           => 'Paleta musi mieć poprawną wartość',
    'BadPath'              => 'Ścieżka musi mieć poprawną wartość',
    'BadPort'              => 'Port musi być ustawiony na prawidłowy numer',
    'BadPostEventCount'    => 'Liczba zdjęć po zdarzeniu musi być liczbą całkowitą równą zero lub więcej',
    'BadPreEventCount'     => 'Liczba obrazów przed zdarzeniem musi wynosić co najmniej zero i mniej niż rozmiar bufora obrazu',
    'BadRefBlendPerc'      => 'Procent mieszania z referencyjnym obrazem musi być dodatnią liczbą całkowitą',
    'BadSectionLength'     => 'Długość sekcji musi być liczbą całkowitą 30 lub większą',
    'BadSignalCheckColour' => 'Kolor kontroli sygnału musi być prawidłowym ciągiem kolorów RGB',
    'BadSourceType'        => 'Typ źródła \"Web Site\" wymaga ustawienia funkcji \"Monitorowanie\"',
    'BadStreamReplayBuffer'=> 'Bufor odtwarzania strumieniowego musi być liczbą całkowitą równą zero lub więcej',
    'BadWarmupCount'       => 'Ramki rozgrzewające muszą być liczbą całkowitą równą zero lub więcej',
    'BadWebColour'         => 'Kolor strony musi być prawidłowy dla strony web',
    'BadWebSitePath'       => 'Wprowadź pełny adres URL strony, w tym prefiks http: // lub https: //.',
    'BadWidth'             => 'Szerokość musi być ustawiona na poprawną wartość',
    'Bandwidth'            => 'Przepustowość',
    'BandwidthHead'        => 'Przepustowość',	// This is the end of the bandwidth status on the top of the console, different in many language due to phrasing
    'BlobPx'               => 'Plamka Px',
    'BlobSizes'            => 'Rozmiary plamek',
    'Blobs'                => 'Plamki',
    'Brightness'           => 'Jaskrawość',
    'Buffer'               => 'Bufor',
    'Buffers'              => 'Bufory',
    'CSSDescription'       => 'Zmień domyślny css dla tego komputera',
    'CanAutoFocus'         => 'Może auto. skupiać',
    'CanAutoGain'          => 'Może auto. wzmacniać',
    'CanAutoIris'          => 'Może auto. ust. ogniskową',
    'CanAutoWhite'         => 'Może auto. ust. balans bieli',
    'CanAutoZoom'          => 'Może auto. zbliżać',
    'CanFocus'             => 'Może ust. ogniskową',
    'CanFocusAbs'          => 'Może ust. ogniskową całkowicie',
    'CanFocusCon'          => 'Może ust. ogniskową ciągle',
    'CanFocusRel'          => 'Może ust. ogniskową relatywnie',
    'CanGain'              => 'Może wzmacniać ',
    'CanGainAbs'           => 'Może wzmacniać absolutnie',
    'CanGainCon'           => 'Może wzmacniać ciągle',
    'CanGainRel'           => 'Może wzmacniać relatywnie',
    'CanIris'              => 'Może ust. ogniskową',
    'CanIrisAbs'           => 'Może ust. ogniskową całkowicie',
    'CanIrisCon'           => 'Może ust. ogniskową ciągle',
    'CanIrisRel'           => 'Może ust. ogniskową relatywnie',
    'CanMove'              => 'Można obracać',
    'CanMoveAbs'           => 'Można obracać całkowicie',
    'CanMoveCon'           => 'Można obracać ciągle',
    'CanMoveDiag'          => 'Można obracać po przekątnej',
    'CanMoveMap'           => 'Można obracać mapowanie',
    'CanMoveRel'           => 'Można obracać relatywnie',
    'CanPan'               => 'Można panoramę' ,
    'CanReset'             => 'Można resetować',
    'CanReboot'            => 'Można restartować',
    'CanSetPresets'        => 'Można ustawiać predefiniowana',
    'CanSleep'             => 'Można usypiać',
    'CanTilt'              => 'Można odchylać',
    'CanWake'              => 'Można wybudzać',
    'CanWhite'             => 'Może ust. balans bieli',
    'CanWhiteAbs'          => 'Może ust. balans bieli całkowicie',
    'CanWhiteBal'          => 'Może ust. balans bieli',
    'CanWhiteCon'          => 'Może ust. balans bieli ciągle',
    'CanWhiteRel'          => 'Może ust. balans bieli relatywnie',
    'CanZoom'              => 'Można zbliżać',
    'CanZoomAbs'           => 'Można zbliżać całkowicie',
    'CanZoomCon'           => 'Można zbliżać ciągle',
    'CanZoomRel'           => 'Można zbliżać relatywnie',
    'Cancel'               => 'Anuluj',
    'CancelForcedAlarm'    => 'Anuluj wymuszony alarm',
    'CaptureHeight'        => 'Wysokość obrazu',
    'CaptureMethod'        => 'Metoda przechwytywania',
    'CapturePalette'       => 'Paleta kolorów obrazu',
    'CaptureResolution'    => 'Rozdzielczość nagrywania',
    'CaptureWidth'         => 'Szerokość obrazu',
    'Cause'                => 'Przyczyna',
    'CheckMethod'          => 'Metoda sprawdzenia alarmu',
    'ChooseDetectedCamera' => 'Wybierz wykrytą kamerę',
    'ChooseFilter'         => 'Wybierz filtr',
    'ChooseLogFormat'      => 'Wybierz format logów',
    'ChooseLogSelection'   => 'Wybierz sposób wybierania logów',
    'ChoosePreset'         => 'Wybierz ustawienie',
    'Clear'                => 'Wyczyść',
    'CloneMonitor'         => 'Klonuj',
    'Close'                => 'Zamknij',
    'Colour'               => 'Nasycenie',
    'Command'              => 'Polecenie',
    'Component'            => 'Komponent',
    'ConcurrentFilter'     => 'Uruchom filtr równolegle',
    'Config'               => 'Konfiguracja',
    'ConfiguredFor'        => 'Ustawiona',
    'ConfirmDeleteEvents'  => 'Jesteś pewien, że chcesz usunąć zaznaczone zdarzenia?',
    'ConfirmPassword'      => 'Potwierdź hasło',
    'ConjAnd'              => 'i',
    'ConjOr'               => 'lub',
    'Console'              => 'Konsola',
    'ContactAdmin'         => 'Skontaktuj się z Twoim administratorem w sprawie szczegółów.',
    'Continue'             => 'Kontynuuj',
    'Contrast'             => 'Kontrast',
    'Control'              => 'Kontrola',
    'ControlAddress'       => 'Adres sterowania',
    'ControlCap'           => 'Możliwość sterowania',
    'ControlCaps'          => 'Możliwość sterowania',
    'ControlDevice'        => 'Kontrola urządzenia',
    'ControlType'          => 'Typ sterowania',
    'Controllable'         => 'Sterowana',
    'Current'              => 'Obecny',
    'Cycle'                => 'Podgląd cykliczny',
    'CycleWatch'           => 'Cykl podglądu',
    'DateTime'             => 'Data/Czas',
    'Day'                  => 'Dzień',
    'Debug'                => 'Debug',
    'DefaultRate'          => 'Domyślna szybkość',
    'DefaultScale'         => 'Skala domyślna',
    'DefaultView'          => 'Widok domyślny',
    'Deinterlacing'        => 'Usuwanie przeplotu',
    'Delay'                => 'Opóźnienie',
    'Delete'               => 'Usuń',
    'DeleteAndNext'        => 'Usuń &amp; następny',
    'DeleteAndPrev'        => 'Usuń &amp; poprzedni',
    'DeleteSavedFilter'    => 'Usuń zapisany filtr',
    'Description'          => 'Opis',
    'DetectedCameras'      => 'Wykryte kamery',
    'DetectedProfiles'     => 'Wykryte profile',
    'Device'               => 'Urządzenie',
    'DeviceChannel'        => 'Numer wejścia w urządzeniu',
    'DeviceFormat'         => 'System TV',
    'DeviceNumber'         => 'Numer urządzenia',
    'DevicePath'           => 'Ścieżka urządzenia',
    'Devices'              => 'Urządzenia',
    'Dimensions'           => 'Rozmiary',
    'DisableAlarms'        => 'Wyłącz alarm',
    'Disk'                 => 'Dysk',
    'Display'              => 'Wygląd',
    'Displaying'           => 'Wyświetlanie',
    'DoNativeMotionDetection'=> 'Wykonaj natywne wykrywanie ruchu',
    'Donate'               => 'Proszę zrób darowiznę',
    'DonateAlready'        => 'Nie, już wykonałem darowiznę',
    'DonateEnticement'     => 'Używasz ZoneMinder już od jakiegoś czasu i mam nadzieję, że jest to przydatne uzupełnienie bezpieczeństwa w domu lub w miejscu pracy. Mimo że ZoneMinder jest i pozostanie darmowy i otwarty, to tworzenie go i wsparcie kosztuje. Jeśli chcesz wesprzeć przyszły rozwój i nowe funkcje, weź pod uwagę darowiznę. Darowizna jest oczywiście opcjonalna, ale bardzo ceniona i możesz przekazać darowizny tak dużo lub tak mało, jak chcesz. <br> <br> Jeśli chcesz przekazać darowiznę, wybierz opcję poniżej lub przejdź do https://zoneminder.com/donate/ w przeglądarce. <br> <br> Dziękujemy za korzystanie z ZoneMinder i nie zapomnij odwiedzić forów na ZoneMinder.com, aby uzyskać pomoc lub sugestie, jak sprawić, by korzystanie z ZoneMinder było jeszcze lepsze.',
    'DonateRemindDay'      => 'Jeszcze nie, przypomnij za 1 dzień',
    'DonateRemindHour'     => 'Jeszcze nie, przypomnij za 1 godzinę',
    'DonateRemindMonth'    => 'Jeszcze nie, przypomnij za 1 miesiąc',
    'DonateRemindNever'    => 'Nie, nie chcę wykonać darowizny, nigdy nie przypominaj',
    'DonateRemindWeek'     => 'Jeszcze nie, przypomnij za 1 tydzień',
    'DonateYes'            => 'Tak, chcę wykonać darowiznę teraz',
    'Download'             => 'Pobierz',
    'DownloadVideo'        => 'Pobierz wideo',
    'DuplicateMonitorName' => 'Duplikuj nazwę ',
    'Duration'             => 'Czas trwania',
    'Edit'                 => 'Edycja',
    'EditLayout'           => 'Edytuj układ',
    'Email'                => 'Email',
    'EnableAlarms'         => 'Włącz alarmy',
    'Enabled'              => 'Aktywny',
    'EnterNewFilterName'   => 'Wpisz nową nazwę filtra',
    'Error'                => 'Błąd',
    'ErrorBrackets'        => 'Błąd, proszę sprawdzić ilość nawiasów otwierających i zamykających',
    'ErrorValidValue'      => 'Błąd, proszę sprawdzić czy wszystkie warunki mają poprawne wartości',
    'Etc'                  => 'itp',
    'Event'                => 'Zdarzenie',
    'EventFilter'          => 'Filtr zdarzeń',
    'EventId'              => 'Nr zdarzenia',
    'EventName'            => 'Nazwa zdarzenia',
    'EventPrefix'          => 'Prefiks zdarzenia',
    'Events'               => 'Zdarzenia',
    'Exclude'              => 'Wyklucz',
    'Execute'              => 'Wykonaj',
    'Exif'                 => 'Zapisz dane EXIF do obrazu',
    'Export'               => 'Eksport',
    'ExportDetails'        => 'Eksport szczegółów zdarzenia',
    'ExportFailed'         => 'Eksport nie powiódł się',
    'ExportFormat'         => 'Rodzaj archiwum',
    'ExportFormatTar'      => 'Tar',
    'ExportFormatZip'      => 'Zip',
    'ExportFrames'         => 'Eksport szczegółów klatki',
    'ExportImageFiles'     => 'Eksport plików obrazowych (klatek)',
    'ExportLog'            => 'Eksport logów',
    'ExportMiscFiles'      => 'Eksport innych plików (jeśli dostępne)',
    'ExportOptions'        => 'Opcje eksportu',
    'ExportSucceeded'      => 'Eksport zakończony pomyślnie',
    'ExportVideoFiles'     => 'Eksport plików video (jeśli dostępne)',
    'Exporting'            => 'Eksportowanie',
    'FPS'                  => 'fps',
    'FPSReportInterval'    => 'Raport (ramek/s)',
    'FTP'                  => 'FTP',
    'Far'                  => 'Daleko',
    'FastForward'          => 'Szybkie przewijanie',
    'Feed'                 => 'Dostarcz',
    'Ffmpeg'               => 'FFmpeg',
    'File'                 => 'Plik',
    'Filter'               => 'Filtr',
    'FilterArchiveEvents'  => 'Archiwizuj wszystkie pasujące',
    'FilterDeleteEvents'   => 'Usuń wszystkie pasujące',
    'FilterEmailEvents'    => 'Wysyłaj pocztą wszystkie pasujące',
    'FilterExecuteEvents'  => 'Wywołuj komendę dla wszystkich pasujących',
    'FilterLog'            => 'Filtr logów',
    'FilterMessageEvents'  => 'Wyświetlaj komunikat na wszystkie pasujące',
    'FilterMoveEvents'     => 'Przenieś wszystkie pasujące',
    'FilterPx'             => 'Filtr Px',
    'FilterUnset'          => 'Musisz określić szerokość i wysokość filtra',
    'FilterUpdateDiskSpace'=> 'Zaktualizuj zajętość dysku',
    'FilterUploadEvents'   => 'Wysyłaj wszystkie pasujące',
    'FilterVideoEvents'    => 'Utwórz nagranie dla zaznaczonych',
    'Filters'              => 'Filtry',
    'First'                => 'Pierwszy',
    'FlippedHori'          => 'Odwróć poziomo',
    'FlippedVert'          => 'Odwróć pionowo',
    'FnMocord'             => 'Wykr. ruchu z nagrywaniem',
    'FnModect'             => 'Wykr. ruchu',
    'FnMonitor'            => 'Monitorowanie',
    'FnNodect'             => 'Zew. zdarzania',
    'FnNone'               => 'Wyłączony',
    'FnRecord'             => 'Nagrywanie',
    'Focus'                => 'Skupienie',
    'ForceAlarm'           => 'Wymuś alarm',
    'Format'               => 'Format',
    'Frame'                => 'Ramka',
    'FrameId'              => 'Nr. ramki',
    'FrameRate'            => 'Tempo ramek',
    'FrameSkip'            => 'Pomiń ramkę',
    'Frames'               => 'Ramki',
    'Func'                 => 'Funkcja',
    'Function'             => 'Funkcja',
    'Gain'                 => 'Wzmocnienie',
    'General'              => 'Ogólne',
    'GenerateDownload'     => 'Generowanie pobierania',
    'GenerateVideo'        => 'Generowanie wideo',
    'GeneratingVideo'      => 'Generuję wideo',
    'GoToZoneMinder'       => 'Przejdź na ZoneMinder.com',
    'Grey'                 => 'Cz/b',
    'Group'                => 'Grupa',
    'Groups'               => 'Grupy',
    'HasFocusSpeed'        => 'Ma prędkość skupiania',
    'HasGainSpeed'         => 'Ma prędkość wzmocnienia',
    'HasHomePreset'        => 'Ma ustawienia początkowej pozycji',
    'HasIrisSpeed'         => 'Ma prędkość ust. ogniskowej',
    'HasPanSpeed'          => 'Ma prędkość panoramy',
    'HasPresets'           => 'Ma ustawienia predefiniowane',
    'HasTiltSpeed'         => 'Ma prędkość odchylania',
    'HasTurboPan'          => 'Ma turbo panoramę',
    'HasTurboTilt'         => 'Ma turbo odchylanie',
    'HasWhiteSpeed'        => 'Ma prędkość balansu bieli',
    'HasZoomSpeed'         => 'Ma prędkość zbliżenia',
    'High'                 => 'Wysokość',
    'HighBW'               => 'Wys.&nbsp;prz.',
    'Home'                 => 'Początkowa pozycja',
    'Hostname'             => 'Nazwa hosta',
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
    'Iris'                 => 'Ogniskowa',
    'KeyString'            => 'Łańcuch klucza',
    'Label'                => 'Etykieta',
    'Language'             => 'Język',
    'Last'                 => 'Ostatni',
    'Layout'               => 'Układ',
    'Level'                => 'Poziom',
    'Libvlc'               => 'Libvlc',
    'LimitResultsPost'     => 'wyników;', // This is used at the end of the phrase 'Limit to first N results only'
    'LimitResultsPre'      => 'Ogranicz do początkowych', // This is used at the beginning of the phrase 'Limit to first N results only'
    'Line'                 => 'Linia',
    'LinkedMonitors'       => 'Połączone monitory',
    'List'                 => 'Lista',
    'ListMatches'          => 'Pokaż pasujące',
    'Load'                 => 'Obc.',
    'Local'                => 'Lokalny',
    'Log'                  => 'Logi',
    'LoggedInAs'           => 'Zalogowany jako',
    'Logging'              => 'Logowanie',
    'LoggingIn'            => 'Logowanie',
    'Login'                => 'Login',
    'Logout'               => 'Wyloguj',
    'Logs'                 => 'Logi',
    'Low'                  => 'niska',
    'LowBW'                => 'Nis.&nbsp;prz.',
    'Main'                 => 'Główny',
    'Man'                  => 'Podr.',
    'Manual'               => 'Podręcznik',
    'Mark'                 => 'Znacznik',
    'Max'                  => 'Maks.',
    'MaxBandwidth'         => 'Maks. przepustowość',
    'MaxBrScore'           => 'Maks.<br/>wynik',
    'MaxFocusRange'        => 'Maks. zakres skupiania',
    'MaxFocusSpeed'        => 'Maks. prędkość skupiania',
    'MaxFocusStep'         => 'Maks. krok skupiania',
    'MaxGainRange'         => 'Maks. zakres wzmocnienia',
    'MaxGainSpeed'         => 'Maks. prędkość wzmocnienia',
    'MaxGainStep'          => 'Maks. krok wzmocnienia',
    'MaxIrisRange'         => 'Maks. zakres ust. ogniskowej',
    'MaxIrisSpeed'         => 'Maks. prędkość ust. ogniskowej',
    'MaxIrisStep'          => 'Maks. krok ust. ogniskowej',
    'MaxPanRange'          => 'Maks. zakres panoramy',
    'MaxPanSpeed'          => 'Maks. prędkość panoramy',
    'MaxPanStep'           => 'Maks. krok panoramy',
    'MaxTiltRange'         => 'Maks. zakres odchylania',
    'MaxTiltSpeed'         => 'Maks. prędkość odchylania',
    'MaxTiltStep'          => 'Maks. krok odchylania',
    'MaxWhiteRange'        => 'Maks. zakres balansu bieli',
    'MaxWhiteSpeed'        => 'Maks. prędkość balansu bieli',
    'MaxWhiteStep'         => 'Maks. krok balansu bieli',
    'MaxZoomRange'         => 'Maks. zakres zbliżenia',
    'MaxZoomSpeed'         => 'Maks. prędkość zbliżenia',
    'MaxZoomStep'          => 'Maks. krok zbliżenia',
    'MaximumFPS'           => 'Maks. FPS',
    'Medium'               => 'średnia',
    'MediumBW'             => 'Śred.&nbsp;prz.',
    'Message'              => 'Treść',
    'MinAlarmAreaLtMax'    => 'Minimalna powierzchnia alarmu powinna być mniejsza niż maksymalna',
    'MinAlarmAreaUnset'    => 'Musisz określić minimalną liczbę pikseli alarmu',
    'MinBlobAreaLtMax'     => 'Minimalny obszar plamki powinien być mniejszy od maksymalnego obszaru plamki',
    'MinBlobAreaUnset'     => 'Musisz określić minimalną liczbę pikseli plamki',
    'MinBlobLtMinFilter'   => 'Minimalna powierzchnia plamki powinna być mniejsza lub równa minimalnej powierzchni filtra',
    'MinBlobsLtMax'        => 'Najmniejsze plamki powinny być mniejsze od największych plamek' ,
    'MinBlobsUnset'        => 'Musisz określić minimalną liczbę plamek',
    'MinFilterAreaLtMax'   => 'Minimalna powierzchnia filtra powinna być mniejsza niż maksymalna',
    'MinFilterAreaUnset'   => 'Musisz określić minimalną liczbę pikseli filtra',
    'MinFilterLtMinAlarm'  => 'Minimalna powierzchnia filtra powinna być mniejsza lub równa minimalnej powierzchni alarmu',
    'MinFocusRange'        => 'Min. zakres skupiania',
    'MinFocusSpeed'        => 'Min. prędkość skupiania',
    'MinFocusStep'         => 'Min. krok skupiania',
    'MinGainRange'         => 'Min. zakres wzmocnienia',
    'MinGainSpeed'         => 'Min. prędkość wzmocnienia',
    'MinGainStep'          => 'Min. krok wzmocnienia',
    'MinIrisRange'         => 'Min. zakres ust. ogniskowej',
    'MinIrisSpeed'         => 'Min. prędkość ust. ogniskowej',
    'MinIrisStep'          => 'Min. krok ust. ogniskowej',
    'MinPanRange'          => 'Min. zakres panoramy',
    'MinPanSpeed'          => 'Min. prędkość panoramy',
    'MinPanStep'           => 'Min. krok panoramy',
    'MinPixelThresLtMax'   => 'Najmniejsze progi pikseli powinny być mniejsze od największych progów pikseli',
    'MinPixelThresUnset'   => 'Musisz określić minimalny próg pikseli',
    'MinTiltRange'         => 'Min zakres odchylania',
    'MinTiltSpeed'         => 'Min prędkość odchylania',
    'MinTiltStep'          => 'Min krok odchylania',
    'MinWhiteRange'        => 'Min zakres balansu bieli',
    'MinWhiteSpeed'        => 'Min prędkość balansu bieli',
    'MinWhiteStep'         => 'Min krok balansu bieli',
    'MinZoomRange'         => 'Min zakres zbliżenia',
    'MinZoomSpeed'         => 'Min prędkość zbliżenia',
    'MinZoomStep'          => 'Min krok zbliżenia',
    'Misc'                 => 'Inne',
    'Mode'                 => 'Tryb',
    'Monitor'              => 'Monitor',
    'MonitorIds'           => 'Numery monitorów',
    'MonitorPreset'        => 'Predefiniowane ustawienia ',
    'MonitorPresetIntro'   => 'Wybierz odpowiednie ustawienie wstępne z poniższej listy.<br><br>Pamiętaj, że może to zastąpić wszystkie wartości skonfigurowane dla tego monitora.<br><br>',
    'MonitorProbe'         => 'Predefiniowane ustawienia monitorów',
    'MonitorProbeIntro'    => 'Poniższa lista pokazuje wykryte kamery analogowe i sieciowe oraz informację, czy są one już używane lub dostępne do wyboru.<br/><br/>Wybierz żądany wpis z poniższej listy.<br/><br/>Należy pamiętać, że nie wszystkie kamery mogły zostać wykryte i wybór tutaj kamery może zastąpić wszystkie wartości skonfigurowane dla bieżącego monitora.<br/><br/>',
    'Monitors'             => 'Monitory',
    'Montage'              => 'Podgląd wszystich kamer na raz',
    'MontageReview'        => 'Podgląd wszystich kamer Alternatywny',
    'Month'                => 'Miesiąc',
    'More'                 => 'Pokaż więcej',
    'MotionFrameSkip'      => 'Pomijanie ramek wykrycia ruchu',
    'Move'                 => 'Przesuń',
    'Mtg2widgrd'           => '2-kolumnowa siatka',
    'Mtg3widgrd'           => '3-kolumnowa siatka',
    'Mtg3widgrx'           => '3-kolumnowa siatka, skalowana, powiększana na alarm',
    'Mtg4widgrd'           => '4-kolumnowa siatka',
    'MtgDefault'           => 'Domyślny',
    'MustBeGe'             => 'musi być większe lub równe od',
    'MustBeLe'             => 'musi być mniejsze lub równe od',
    'MustConfirmPassword'  => 'Musisz potwierdzić hasło',
    'MustSupplyPassword'   => 'Musisz podać hasło',
    'MustSupplyUsername'   => 'Musisz podać nazwę użytkownika',
    'Name'                 => 'Nazwa',
    'Near'                 => 'W pobliżu',
    'Network'              => 'Sieć',
    'New'                  => 'Nowy',
    'NewGroup'             => 'Nowa grupa',
    'NewLabel'             => 'Nowa etykieta',
    'NewPassword'          => 'Nowe hasło',
    'NewState'             => 'Nowy stan',
    'NewUser'              => 'nowy',
    'Next'                 => 'Następny',
    'No'                   => 'Nie',
    'NoDetectedCameras'    => 'Nie wykryto kamer',
    'NoDetectedProfiles'   => 'Brak wykrytych profili',
    'NoFramesRecorded'     => 'Brak zapisanych ramek dla tego zdarzenia',
    'NoGroup'              => 'Brak grupy',
    'NoSavedFilters'       => 'Brak zapisanych filtrów',
    'NoStatisticsRecorded' => 'Brak zapisanych statystyk dla tego zdarzenia/ramki',
    'None'                 => 'Brak',
    'NoneAvailable'        => 'Niedostępne',
    'Normal'               => 'Normalny',
    'Notes'                => 'Uwagi',
    'NumPresets'           => 'Liczba ustawień predefiniowanych',
    'Off'                  => 'Wyłącz',
    'On'                   => 'Włącz',
    'OnvifCredentialsIntro'=> 'Podaj nazwę użytkownika i hasło dla wybranej kamery.<br/>Jeśli nie utworzono żadnego użytkownika dla kamery, użytkownik podany tutaj zostanie utworzony z podanym hasłem.<br/><br/>',
    'OnvifProbe'           => 'ONVIF',
    'OnvifProbeIntro'      => 'Poniższa lista pokazuje wykryte kamery ONVIF i informacje, czy są one już używane lub dostępne do wyboru.<br/><br/>Wybierz żądany wpis z listy poniżej.<br/><br/>Należy pamiętać, że nie wszystkie kamery mogą zostać wykryte, a wybór kamery z poniższej listy może zastąpić wszystkie wartości skonfigurowane dla bieżącego monitora.<br/><br/>',
    'OpEq'                 => 'równy',
    'OpGt'                 => 'większe od',
    'OpGtEq'               => 'większe lub równe od',
    'OpIn'                 => 'w zestawie',
    'OpIs'                 => 'jest',
    'OpIsNot'              => 'nie jest',
    'OpLt'                 => 'mniejsze od',
    'OpLtEq'               => 'mniejsze lub równe od',
    'OpMatches'            => 'pasujące',
    'OpNe'                 => 'różne od',
    'OpNotIn'              => 'brak w zestawie',
    'OpNotMatches'         => 'nie pasujące',
    'Open'                 => 'Otwórz',
    'OptionHelp'           => 'OpcjePomoc',
    'OptionRestartWarning' => 'Te zmiany nie przyniosą natychmiastowego efektu\ndopóki system pracuje. Kiedy zakończysz robić zmiany\nproszę koniecznie zrestartować ZoneMinder.',
    'OptionalEncoderParam' => 'Opcjonalne parametry enkodera',
    'Options'              => 'Opcje',
    'OrEnterNewName'       => 'lub wpisz nową nazwę',
    'Order'                => 'Kolejność',
    'Orientation'          => 'Orientacja',
    'Out'                  => 'Wyjście',
    'OverwriteExisting'    => 'Nadpisz istniejące',
    'Paged'                => 'Stronicowane',
    'Pan'                  => 'Panoramiczny',
    'PanLeft'              => 'Przesuń w lewo',
    'PanRight'             => 'Przesuń w prawo',
    'PanTilt'              => 'Panorama/Odchylenie',
    'Parameter'            => 'Parametr',
    'Password'             => 'Hasło',
    'PasswordsDifferent'   => 'Hasła: nowe i potwierdzone są różne!',
    'Paths'                => 'Ścieżki',
    'Pause'                => 'Pauza',
    'Phone'                => 'Telefon',
    'PhoneBW'              => 'Tel.&nbsp;prz.',
    'Pid'                  => 'PID',
    'PixelDiff'            => 'Różnica pikseli',
    'Pixels'               => 'pikseli',
    'Play'                 => 'Odtwórz',
    'PlayAll'              => 'Odtwórz wszystkie',
    'PleaseWait'           => 'Proszę czekać',
    'Plugins'              => 'Dodatki',
    'Point'                => 'Punkt',
    'PostEventImageBuffer' => 'Bufor obrazów po zdarzeniu',
    'PreEventImageBuffer'  => 'Bufor obrazów przed zdarzeniem',
    'PreserveAspect'       => 'Zachowaj proporcje',
    'Preset'               => 'Predefiniowane ustawienie',
    'Presets'              => 'Predefiniowane ustawienia',
    'Prev'                 => 'Poprzedni',
    'Probe'                => 'Wykrywanie',
    'ProfileProbe'         => 'Wykrywanie strumienia',
    'ProfileProbeIntro'    => 'Poniższa lista pokazuje istniejące profile strumieni wybranej kamery.<br/><br/>Wybierz żądany wpis z listy poniżej.<br/><br/>Należy pamiętać, że ZoneMinder nie może skonfigurować dodatkowych profili i że wybór tutaj kamery może zastąpić wszystkie wartości skonfigurowane dla bieżącego monitora.<br/><br/>',
    'Progress'             => 'Postęp',
    'Protocol'             => 'Protokół',
    'RTSPDescribe'         => 'Użyj URL nośnika odpowiedzi RTSP',
    'RTSPTransport'        => 'Protokół transportu RTSP',
    'Rate'                 => 'Tempo',
    'Real'                 => 'Rzeczywista',
    'RecaptchaWarning'     => 'Twój tajny klucz reCaptcha jest nieprawidłowy. Popraw to lub reCaptcha nie zadziała',
    'Record'               => 'Zapis',
    'RecordAudio'          => 'Zapisuj dźwięk ze zdarzeniem',
    'RefImageBlendPct'     => 'Miks z obrazem odniesienia',
    'Refresh'              => 'Odśwież',
    'Remote'               => 'Zdalny',
    'RemoteHostName'       => 'Nazwa hostu zdalnego',
    'RemoteHostPath'       => 'Ścieżka hostu zdalnego ',
    'RemoteHostPort'       => 'Port hostu zdalnego ',
    'RemoteHostSubPath'    => 'Podścieżka hostu zdalnego',
    'RemoteImageColours'   => 'Kolory obrazu zdalnego',
    'RemoteMethod'         => 'Rodzaj zdalnego połączenia',
    'RemoteProtocol'       => 'Zdalny protokół',
    'Rename'               => 'Zmień nazwę',
    'Replay'               => 'Odtwarzaj',
    'ReplayAll'            => 'Wszystko',
    'ReplayGapless'        => 'Wszystko i powtarzaj',
    'ReplaySingle'         => 'Bieżące zdarzenie',
    'ReportEventAudit'     => 'Raport zdarzeń',
    'Reset'                => 'Resetuj',
    'ResetEventCounts'     => 'Kasuj licznik zdarzeń',
    'Restart'              => 'Restart',
    'Restarting'           => 'Restartuję',
    'RestrictedCameraIds'  => 'Numery kamer',
    'RestrictedMonitors'   => 'Monitory z ograniczeniami',
    'ReturnDelay'          => 'Opóźnienie odpowiedzi',
    'ReturnLocation'       => 'Lokalizacja powrotu',
    'Rewind'               => 'Przewijanie',
    'RotateLeft'           => 'Obróć w lewo',
    'RotateRight'          => 'Obróć w prawo',
    'RunLocalUpdate'       => 'Proszę uruchom skrypt zmupdate.pl w celu aktualizacji',
    'RunMode'              => 'Tryb pracy',
    'RunState'             => 'Stan pracy',
    'Running'              => 'Pracuje',
    'Save'                 => 'Zapisz',
    'SaveAs'               => 'Zapisz jako',
    'SaveFilter'           => 'Zapisz filtr',
    'SaveJPEGs'            => 'Zapisz pliki JPEG',
    'Scale'                => 'Skala',
    'Score'                => 'Wynik',
    'Secs'                 => 'Sekund',
    'Sectionlength'        => 'Długość sekcji',
    'Select'               => 'Wybierz',
    'SelectFormat'         => 'Wybierz format',
    'SelectLog'            => 'Wybierz log',
    'SelectMonitors'       => 'Wybierz monitory',
    'SelfIntersecting'     => 'Krawędzie wielokątów nie mogą się przecinać',
    'Set'                  => 'Ustaw',
    'SetNewBandwidth'      => 'Ustaw nową przepustowość',
    'SetPreset'            => 'Ustaw ust. predefiniowane',
    'Settings'             => 'Ustawienia',
    'ShowFilterWindow'     => 'Pokaż okno filtru',
    'ShowTimeline'         => 'Pokaż oś czasu',
    'SignalCheckColour'    => 'Kolor testu sygnału',
    'SignalCheckPoints'    => 'Punkty kontroli sygnału',
    'Size'                 => 'Rozmiar',
    'SkinDescription'      => 'Zmień domyślną skórkę dla tego komputera',
    'Sleep'                => 'Uśpij',
    'SortAsc'              => 'rosnąco',
    'SortBy'               => 'Sortuj po',
    'SortDesc'             => 'malejąco',
    'Source'               => 'Źródło',
    'SourceColours'        => 'Kolor źródła',
    'SourcePath'           => 'Ścieżka źródłowa',
    'SourceType'           => 'Typ źródła',
    'Speed'                => 'Prędkość',
    'SpeedHigh'            => 'Wysoka prędkość',
    'SpeedLow'             => 'Niska prędkość',
    'SpeedMedium'          => 'Średnia prędkość',
    'SpeedTurbo'           => 'Turbo prędkość',
    'Start'                => 'Start',
    'State'                => 'Stan',
    'Stats'                => 'Statystyki',
    'Status'               => 'Status',
    'StatusConnected'      => 'Nagrywanie',
    'StatusNotRunning'     => 'Nie pracuje',
    'StatusRunning'        => 'Nie nagrywa',
    'StatusUnknown'        => 'Nieznany',
    'Step'                 => 'Krok',
    'StepBack'             => 'Krok w tył',
    'StepForward'          => 'Krok w przód',
    'StepLarge'            => 'Duży krok',
    'StepMedium'           => 'Średni krok',
    'StepNone'             => 'Brak kroku',
    'StepSmall'            => 'Mały krok',
    'Stills'               => 'Podgląd klatek',
    'Stop'                 => 'Stop',
    'Stopped'              => 'Zatrzymany',
    'StorageArea'          => 'Magazyn',
    'StorageScheme'        => 'Schemat',
    'Stream'               => 'Odtwarzacz',
    'StreamReplayBuffer'   => 'Bufor odtwarzania strumienia',
    'Submit'               => 'Zatwierdź',
    'System'               => 'System',
    'SystemLog'            => 'Logi systemu',
    'TargetColorspace'     => 'Przestrzeń kolorów źródła',
    'Tele'                 => 'Tel',
    'Thumbnail'            => 'Miniaturka',
    'Tilt'                 => 'Odchylenie',
    'Time'                 => 'Czas',
    'TimeDelta'            => 'Różnica czasu',
    'TimeStamp'            => 'Znak czasu',
    'Timeline'             => 'Oś czasu',
    'TimelineTip1'         => 'Przeciągnij kursor myszki na wykresie, aby wyświetlić obraz migawki i szczegóły zdarzenia.',
    'TimelineTip2'         => 'Kliknij na kolorowe fragmenty wykresu, aby zobaczyć wydarzenie.',
    'TimelineTip3'         => 'Kliknij w tło, aby przybliżyć się do mniejszego okresu opartego wokół wykonanego kliknięcia..',
    'TimelineTip4'         => 'Użyj opcji poniżej, w celu nawigacji.',
    'Timestamp'            => 'Czas',
    'TimestampLabelFormat' => 'Format etykiety czasu',
    'TimestampLabelSize'   => 'Rozmiar czcionki',
    'TimestampLabelX'      => 'Wsp. X etykiety czasu',
    'TimestampLabelY'      => 'Wsp. Y etykiety czasu',
    'Today'                => 'Dziś',
    'Tools'                => 'Narzędzia',
    'Total'                => 'Całość',
    'TotalBrScore'         => 'Całkowity<br/>wynik',
    'TrackDelay'           => 'Śledź opóźnienia',
    'TrackMotion'          => 'Śledź ruch',
    'Triggers'             => 'Wyzwalacze',
    'TurboPanSpeed'        => 'Turbo prędkość panoramy',
    'TurboTiltSpeed'       => 'Turbo prędkość odchylenia',
    'Type'                 => 'Typ',
    'Unarchive'            => 'Usuń z archiwum',
    'Undefined'            => 'Niezdefiniowany',
    'Units'                => 'Jednostki',
    'Unknown'              => 'Nieznany',
    'Update'               => 'Aktualizuj',
    'UpdateAvailable'      => 'Jest dostępne uaktualnienie ZoneMinder ',
    'UpdateNotNecessary'   => 'Nie jest wymagane uaktualnienie',
    'Updated'              => 'Zaktualizowane',
    'Upload'               => 'Wysyłanie',
    'UseFilter'            => 'Użyj filtru',
    'UseFilterExprsPost'   => '&nbsp;wyrażenie&nbsp;filtru', // This is used at the end of the phrase 'use N filter expressions'
    'UseFilterExprsPre'    => 'Użyj&nbsp;', // This is used at the beginning of the phrase 'use N filter expressions'
    'UsedPlugins'          => 'Użyte dodatki',
    'User'                 => 'Użytkownik',
    'Username'             => 'Nazwa użytkownika',
    'Users'                => 'Użytkownicy',
    'V4L'                  => 'V4L',
    'V4LCapturesPerFrame'  => 'Przechwycenia na ramkę',
    'V4LMultiBuffer'       => 'Multi buforowanie',
    'Value'                => 'Wartość',
    'Version'              => 'Wersja',
    'VersionIgnore'        => 'Zignoruj tą wersję',
    'VersionRemindDay'     => 'Przypomnij po 1 dniu',
    'VersionRemindHour'    => 'Przypomnij po 1 godzinie',
    'VersionRemindNever'   => 'Nie przypominaj o nowych wersjach',
    'VersionRemindWeek'    => 'Przypomnij po 1 tygodniu',
    'Video'                => 'Eksport wideo',
    'VideoFormat'          => 'Format nagrania',
    'VideoGenFailed'       => 'Generowanie filmu wideo nie powiodło się!',
    'VideoGenFiles'        => 'Lista wygenerowanych plików:',
    'VideoGenNoFiles'      => 'Nie odnaleziono plików wideo',
    'VideoGenParms'        => 'Parametry generowania filmu wideo',
    'VideoGenSucceeded'    => 'Wygenerowano pomyślnie!',
    'VideoSize'            => 'Rozmiar filmu wideo',
    'VideoWriter'          => 'Sposób zapisu wideo',
    'View'                 => 'Podgląd',
    'ViewAll'              => 'Pokaż wszystko',
    'ViewEvent'            => 'Pokaż zdarzenie',
    'ViewPaged'            => 'Pokaż stronami',
    'Wake'                 => 'Obudź',
    'WarmupFrames'         => 'Ignorowane ramki',
    'Watch'                => 'podgląd',
    'Web'                  => 'Sieć',
    'WebColour'            => 'Kolor strony',
    'WebSiteUrl'           => 'URL strony',
    'Week'                 => 'Tydzień',
    'White'                => 'Biel',
    'WhiteBalance'         => 'Balans bieli',
    'Wide'                 => 'Szerokość',
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
    'ZoneArea'             => 'Obszar strefy',
    'ZoneExtendAlarmFrames' => 'Rozszerz licznik ramek alarmowych',
    'ZoneFilterSize'       => 'Szerokość/wysokość filtra (piksele)',
    'ZoneMinMaxAlarmArea'  => 'Min/Max obszar alarmu',
    'ZoneMinMaxBlobArea'   => 'Min/Max obszar plamki',
    'ZoneMinMaxBlobs'      => 'Min/Max plamki',
    'ZoneMinMaxFiltArea'   => 'Min/Max obszar filtrowany',
    'ZoneMinMaxPixelThres' => 'Min/Max próg pikseli (0-255)',
    'ZoneMinderLog'        => 'Log ZoneMinder',
    'ZoneOverloadFrames'   => 'Liczba ignorowanych ramek po przeciążeniu alarmu',
    'Zones'                => 'Strefy',
    'Zoom'                 => 'Powiększenie',
    'ZoomIn'               => 'Przybliż',
    'ZoomOut'              => 'Oddal',
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
    'VersionMismatch'      => 'Niezgodność wersji, wersja systemu %1$s, bazy danych %2$s.',
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
		'Help' => "Parametry z tego pola są przekazywane do FFmpeg. Wiele parametrów może być rozdzielone przez ,~~ ".
		          "Przykłady (nie wpisuj cytatów)~~~~".
		          "\"allowed_media_types=video\" Ustaw typ danych na żądanie z kamery (audio, video, data)~~~~".
		          "\"reorder_queue_size=nnn\" Ustaw liczbę pakietów do buforowania do obsługi zmienionych pakietów~~~~".
		          "\"loglevel=debug\" Ustaw gadatliwość FFmpeg (quiet, panic, fatal, error, warning, info, verbose, debug)"
	),
	'OPTIONS_LIBVLC' => array(
		'Help' => "Parametry w tym polu są przekazywane do libVLC. Wiele parametrów może być rozdzielone przez ,~~ ".
		          "Przykłady (nie wpisuj cytatów)~~~~".
		          "\"--rtp-client-port=nnn\" Ustaw port lokalny, który ma być używany dla danych rtp~~~~".
		          "\"--verbose=2\" Ustaw gadatliwość libVLC"
	),

//    'LANG_DEFAULT' => array(
//        'Prompt' => "This is a new prompt for this option",
//        'Help' => "This is some new help for this option which will be displayed in the popup window when the ? is clicked"
//    ),
);

?>
