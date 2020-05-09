<?php
//
// ZoneMinder web HU Hungarian language file, $Date$, $Revision$
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
// ZoneMinder Hungarian Translation by szimszon at oregpreshaz dot eu, robi
// version: 0.8 - 2013.08.16. - frissítés 1.26.0-hoz (robi)
// version: 0.7 - 2013.05.12. - frissítés 1.25.0-hoz (robi)
// version: 0.6 - 2009.06.21. - frissítés 1.24.2-höz (robi)
// version: 0.5 - 2007.12.30. - frissítés 1.23.1-hez (robi)
// version: 0.4 - 2007.12.30. - frissítés 1.23.0-hoz (robi)
// version: 0.3 - 2006.04.27. - fordítás befejezése, elrendezése elféréshez (robi)
// version: 0.2 - 2006.12.05. - par javitas
// version: 0.1 - 2006.11.27. - sok typoval es par leforditatlan resszel
//
// To enable correct Hungarian locales, make sure to install hu_HU locale on your system.
//
// On Ubuntu 12.04 do it like this:
//
// locale -a
// - to see what's installed
//
// cat /usr/share/i18n/SUPPORTED | grep hu
// - to check the possibility of installation
//
// sudo locale-gen hu_HU
// sudo locale-gen hu_HU.utf8
// - to install
//
// sudo service apache2 restart
// - to make PHP aware of it
//
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
//header( "Content-Type: text/html; charset=iso8859-2" );
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
// setlocale( LC_ALL, 'hu_HU' ); //All locale settings 4.3.0 and after
//setlocale( LC_CTYPE, 'hu_HU'); //Character class settings 4.3.0 and after
//setlocale( LC_TIME, 'hu_HU'); //Date and time formatting 4.3.0 and after

setlocale( LC_CTYPE, 'hu_HU.UTF-8');
setlocale( LC_TIME, 'hu_HU.UTF-8' );

//
// Date and time formats, specific to this language
//
define( "DATE_FMT_CONSOLE_LONG", "%Y. %b. %e. %A, %H:%M" );     // This is the main console date/time, date() or strftime() format
define( "DATE_FMT_CONSOLE_SHORT", "%H:%M" );                    // This is the xHTML console date/time, date() or strftime() format

define( "STRF_FMT_DATETIME", "%Y. %b. %e., %H:%M" );            // Strftime locale aware format for dates with times
define( "STRF_FMT_DATE", "%Y. %b. %e." );                       // Strftime locale aware format for dates without times
define( "STRF_FMT_TIME", "%H:%M:%S" );                          // Strftime locale aware format for times without dates

define( "STRF_FMT_DATETIME_SHORT", "%y/%m/%d %H:%M:%S" );       // Strftime shorter format for dates with time
define( "STRF_FMT_DATETIME_SHORTER", "%m.%d. %H:%M:%S" );       // Strftime shorter format for dates with time, used where space is tight (events list)

// Simple String Replacements
$SLANG = array(
    '24BitColour'          => '24 bites szín',
    '32BitColour'          => '32 bit colour',          // Added - 2015-04-18
    '8BitGrey'             => '8 bit szürkeárnyalat',
    'Action'               => 'Művelet',
    'Actual'               => 'Valós',
    'AddNewControl'        => 'Új vezérlés',
    'AddNewMonitor'        => 'Új kamera',
    'AddNewServer'         => 'Add New Server',         // Added - 2018-08-30
    'AddNewStorage'        => 'Add New Storage',        // Added - 2018-08-30
    'AddNewUser'           => 'Új felhasználó',
    'AddNewZone'           => 'Új zóna',
    'Alarm'                => 'Riadó',
    'AlarmBrFrames'        => 'Riasztó<br/>képek',
    'AlarmFrame'           => 'Riasztó kép',
    'AlarmFrameCount'      => 'Riasztáshoz szükséges képkockák száma',
    'AlarmLimits'          => 'Riasztási határok',
    'AlarmMaximumFPS'      => 'Maximális FPS riasztott állapotban',
    'AlarmPx'              => 'Riasztó képpont',
    'AlarmRGBUnset'        => 'Be kell állítani egy RGB színt a riasztáshoz',
    'AlarmRefImageBlendPct'=> 'Alarm Reference Image Blend %ge', // Added - 2015-04-18
    'Alert'                => 'Figyelem',
    'All'                  => 'Mind',
    'AnalysisFPS'          => 'Analysis FPS',           // Added - 2015-07-22
    'AnalysisUpdateDelay'  => 'Analysis Update Delay',  // Added - 2015-07-23
    'Apply'                => 'Alkalmaz',
    'ApplyingStateChange'  => 'Állapot váltása...',
    'ArchArchived'         => 'Csak archivált',
    'ArchUnarchived'       => 'Csak archiválatlan',
    'Archive'              => 'Archiválás',
    'Archived'             => 'Archívum',
    'Area'                 => 'Terület',
    'AreaUnits'            => 'Terület (képpont / %)',
    'AttrAlarmFrames'      => 'Riadó képkockák',
    'AttrArchiveStatus'    => 'Archivált állapot',
    'AttrAvgScore'         => 'Átlagérték',
    'AttrCause'            => 'Okozó',
    'AttrDiskBlocks'       => 'Tárhely blokk',
    'AttrDiskPercent'      => 'Tárhely százalék',
    'AttrDiskSpace'        => 'Disk Space',             // Added - 2018-08-30
    'AttrDuration'         => 'Időtartam',
    'AttrEndDate'          => 'End Date',               // Added - 2018-08-30
    'AttrEndDateTime'      => 'End Date/Time',          // Added - 2018-08-30
    'AttrEndTime'          => 'End Time',               // Added - 2018-08-30
    'AttrEndWeekday'       => 'End Weekday',            // Added - 2018-08-30
    'AttrFilterServer'     => 'Server Filter is Running On', // Added - 2018-08-30
    'AttrFrames'           => 'Képkocka',
    'AttrId'               => 'Azonosító',
    'AttrMaxScore'         => 'Max. érték',
    'AttrMonitorId'        => 'Kamera azonosító',
    'AttrMonitorName'      => 'Kamera név',
    'AttrMonitorServer'    => 'Server Monitor is Running On', // Added - 2018-08-30
    'AttrName'             => 'Név',
    'AttrNotes'            => 'Megjegyzés',
    'AttrStartDate'        => 'Start Date',             // Added - 2018-08-30
    'AttrStartDateTime'    => 'Start Date/Time',        // Added - 2018-08-30
    'AttrStartTime'        => 'Start Time',             // Added - 2018-08-30
    'AttrStartWeekday'     => 'Start Weekday',          // Added - 2018-08-30
    'AttrStateId'          => 'Run State',              // Added - 2018-08-30
    'AttrStorageArea'      => 'Storage Area',           // Added - 2018-08-30
    'AttrStorageServer'    => 'Server Hosting Storage', // Added - 2018-08-30
    'AttrSystemLoad'       => 'Rendszerterhelés',
    'AttrTotalScore'       => 'Összérték',
    'Auto'                 => 'Auto',
    'AutoStopTimeout'      => 'Auto megállási idő túllépés',
    'Available'            => 'Elérhető',
    'AvgBrScore'           => 'Átlag<br/>érték',
    'Background'           => 'Háttérben futó',
    'BackgroundFilter'     => 'Szűrő automatikus futtatása a háttérben',
    'BadAlarmFrameCount'   => 'Riasztáshoz szükséges képkockák száma legyen legalább 1',
    'BadAlarmMaxFPS'       => 'Maximális FPS riasztott állapotban legyen megadva',
    'BadAnalysisFPS'       => 'Analysis FPS must be a positive integer or floating point value', // Added - 2015-07-22
    'BadAnalysisUpdateDelay'=> 'Analysis update delay must be set to an integer of zero or more', // Added - 2015-07-23
    'BadChannel'           => 'A csatorna száma legyen legalább 0',
    'BadColours'           => 'Target colour must be set to a valid value', // Added - 2015-04-18
    'BadDevice'            => 'Az eszköz elérése valós legyen',
    'BadFPSReportInterval' => 'Az FPS naplózásának gyakorisága legyen legalább 0',
    'BadFormat'            => 'A típus 0 vagy nagyobb egész szám legyen',
    'BadFrameSkip'         => 'Az eldobott képkockák száma legyen legalább 0',
    'BadHeight'            => 'A képmagasság legyen érvényes érték képpontban megadva',
    'BadHost'              => 'A hoszt legyen valós IP cím vagy hosztnév http:// nélkül',
    'BadImageBufferCount'  => 'A képkockák száma a pufferben legyen legalább 10',
    'BadLabelX'            => 'A cimke X koordinátája legyen legalább 0',
    'BadLabelY'            => 'A cimke Y koordinátája legyen legalább 0',
    'BadMaxFPS'            => 'Maximális FPS nyugalmi állapotban legyen megadva',
    'BadMotionFrameSkip'   => 'Motion Frame skip count must be an integer of zero or more',
    'BadNameChars'         => 'A név csak betűket, számokat, plusz-, kötő-, és aláhúzásjelet tartalmazhat',
    'BadPalette'           => 'A szín-palettának válasszin egy helyes értéket',
    'BadPath'              => 'A kép elérési útvonala helytelen',
    'BadPort'              => 'A portszám helytelen',
    'BadPostEventCount'    => 'Az esemény utáni képkockák száma legyen legalább 0',
    'BadPreEventCount'     => 'Az esemény előtti képkockák száma legyen legalább 0',
    'BadRefBlendPerc'      => 'Változás a referenciaképtől legyen legalább 1',
    'BadSectionLength'     => 'Fix időtartamú esemény hossza legyen legalább 30',
    'BadSignalCheckColour' => 'Szín a jel kimaradásakor legyen egy érvényes HTML szín-kód',
    'BadSourceType'        => 'Source Type \"Web Site\" requires the Function to be set to \"Monitor\"', // Added - 2018-08-30
    'BadStreamReplayBuffer'=> 'Folyam visszajátszó puffer legyen legalább 0',
    'BadWarmupCount'       => 'Bemelegítő képkockák száma legyen legalább 0',
    'BadWebColour'         => 'Szín az idővonal ablakban legyen egy érvényes HTML szín-kód',
    'BadWebSitePath'       => 'Please enter a complete website url, including the http:// or https:// prefix.', // Added - 2018-08-30
    'BadWidth'             => 'A képszélesség legyen érvényes érték képpontban megadva',
    'Bandwidth'            => 'Sávszélesség',
    'BandwidthHead'        => 'sávszélességre',
    'BlobPx'               => 'Blob képpont',
    'BlobSizes'            => 'Blob méretek',
    'Blobs'                => 'Blob-ok',
    'Brightness'           => 'Fényerő',
    'Buffer'               => 'Buffer',                 // Added - 2015-04-18
    'Buffers'              => 'Pufferek',
    'CSSDescription'       => 'Change the default css for this computer', // Added - 2015-04-18
    'CanAutoFocus'         => 'Van autofókusza',
    'CanAutoGain'          => 'Van AGC-je',
    'CanAutoIris'          => 'Van autoírisze',
    'CanAutoWhite'         => 'Van autómata fehér egyensúlya',
    'CanAutoZoom'          => 'Van auto-zoomja',
    'CanFocus'             => 'Tud fókuszálni',
    'CanFocusAbs'          => 'Tud abszolút fókuszt',
    'CanFocusCon'          => 'Tud folyamatos fókuszt',
    'CanFocusRel'          => 'Tud relatív fókuszt',
    'CanGain'              => 'Tud erősíteni',
    'CanGainAbs'           => 'Tud abszolút erősítést',
    'CanGainCon'           => 'Tud folyamatos erősítést',
    'CanGainRel'           => 'Tud relatív erősítést',
    'CanIris'              => 'Tud íriszt változtatni',
    'CanIrisAbs'           => 'Tud abszolut íriszt',
    'CanIrisCon'           => 'Folyamatosan tud íriszt változtatni',
    'CanIrisRel'           => 'Relatíven tud íriszt változtatni',
    'CanMove'              => 'Tud mozogni',
    'CanMoveAbs'           => 'Tud abszolult mozgást',
    'CanMoveCon'           => 'Folyamatosan tud mozogni',
    'CanMoveDiag'          => 'Diagonálban tud mozogni',
    'CanMoveMap'           => 'Útvonalon tud mozogni',
    'CanMoveRel'           => 'Relatíven tud mozogni',
    'CanPan'               => 'Tud jobb-bal mozgást' ,
    'CanReset'             => 'Tud alaphelyzetbe jönni',
	'CanReboot'             => 'Can Reboot',
    'CanSetPresets'        => 'Tud menteni profilokat',
    'CanSleep'             => 'Tud phihenő üzemmódot',
    'CanTilt'              => 'Tud fel-le mozgást',
    'CanWake'              => 'Tud feléledni',
    'CanWhite'             => 'Van fehér szintje',
    'CanWhiteAbs'          => 'Van abszolut fehér szintje',
    'CanWhiteBal'          => 'Van fehér egyensúlya',
    'CanWhiteCon'          => 'Van folyamatos fehér egyensúlya',
    'CanWhiteRel'          => 'Van relatív fehér egyensúlya',
    'CanZoom'              => 'Tud zoom-olni',
    'CanZoomAbs'           => 'Tud abszolut zoom-ot',
    'CanZoomCon'           => 'Tud folyamatos zoom-ot',
    'CanZoomRel'           => 'Tud relatív zoom-ot',
    'Cancel'               => 'Mégsem',
    'CancelForcedAlarm'    => 'Kézi riasztás megszűntetése',
    'CaptureHeight'        => 'Képmagasság',
    'CaptureMethod'        => 'Digitalizálás módszere',
    'CapturePalette'       => 'Digitalizálás szín-palettája',
    'CaptureResolution'    => 'Capture Resolution',     // Added - 2015-04-18
    'CaptureWidth'         => 'Képszélesség',
    'Cause'                => 'Okozó',
    'CheckMethod'          => 'A riasztás figyelésének módja',
    'ChooseDetectedCamera' => 'Válasszon érzékelt kamerát',
    'ChooseFilter'         => 'Válasszon szűrőt',
    'ChooseLogFormat'      => 'Válasszon napló formátumot',
    'ChooseLogSelection'   => 'Válasszon naplót',
    'ChoosePreset'         => 'Válasszon profilt',
    'Clear'                => 'Törlés',
    'CloneMonitor'         => 'Clone',                  // Added - 2018-08-30
    'Close'                => 'Bezárás',
    'Colour'               => 'Szín',
    'Command'              => 'Parancs',
    'Component'            => 'Komponens',
    'ConcurrentFilter'     => 'Run filter concurrently', // Added - 2018-08-30
    'Config'               => 'Beállítás',
    'ConfiguredFor'        => 'Beállítva',
    'ConfirmDeleteEvents'  => 'Biztos benne, hogy törli a kiválasztott eseményeket?',
    'ConfirmPassword'      => 'Jelszó megerősítés',
    'ConjAnd'              => 'és',
    'ConjOr'               => 'vagy',
    'Console'              => 'Konzol',
    'ContactAdmin'         => 'Kérem vegye fel a kapcsolatot a rendszergazdával a részletekért.',
    'Continue'             => 'Folytatás',
    'Contrast'             => 'Kontraszt',
    'Control'              => 'Vezérlés',
    'ControlAddress'       => 'Vezérlési jogok',
    'ControlCap'           => 'Vezérlési lehetőség',
    'ControlCaps'          => 'Vezérlési lehetőségek',
    'ControlDevice'        => 'Vezérlő eszköz',
    'ControlType'          => 'Vezérlés típusa',
    'Controllable'         => 'Vezérelhető',
    'Current'              => 'Current',                // Added - 2015-04-18
    'Cycle'                => 'Ciklikus nézet',
    'CycleWatch'           => 'Ciklikus nézet',
    'DateTime'             => 'Dátum/Idő', 
    'Day'                  => 'Napon',
    'Debug'                => 'Hibakeresés',
    'DefaultRate'          => 'Alapértelmezett sebesség',
    'DefaultScale'         => 'Alapértelmezett méret',
    'DefaultView'          => 'Alapértelmezett nézet',
    'Deinterlacing'        => 'Deinterlacing',          // Added - 2015-04-18
    'Delay'                => 'Delay',                  // Added - 2015-04-18
    'Delete'               => 'Törlés',
    'DeleteAndNext'        => 'Törlés &amp;<br>következő',
    'DeleteAndPrev'        => 'Törlés &amp;<br>előző',
    'DeleteSavedFilter'    => 'Mentett szűrő törlése',
    'Description'          => 'Leírás',
    'DetectedCameras'      => 'Érzékelt kamerák',
    'DetectedProfiles'     => 'Detected Profiles',      // Added - 2015-04-18
    'Device'               => 'Eszköz',
    'DeviceChannel'        => 'Eszköz csatornája',
    'DeviceFormat'         => 'Eszköz formátuma',
    'DeviceNumber'         => 'Eszköz szám',
    'DevicePath'           => 'Eszköz elérési útvonala',
    'Devices'              => 'Eszközök',
    'Dimensions'           => 'Méretek',
    'DisableAlarms'        => 'Riasztások tiltása',
    'Disk'                 => 'Tárhely',
    'Display'              => 'Megjelenés',
    'Displaying'           => 'Megjelenítés',
    'DoNativeMotionDetection'=> 'Do Native Motion Detection', // Added - 2015-04-18
    'Donate'               => 'Kérem támogasson',
    'DonateAlready'        => 'Nem, én már támogattam',
    'DonateEnticement'     => 'Ön már jó ideje használja a ZoneMindert, és reméljük hasznos eszköznek tartja háza vagy munkahelye biztonságában. Bár a ZoneMinder egy szabad, nyílt forráskódú termék és az is marad, a fejlesztése pénzbe kerül. Ha van lehetősége támogatni a jövőbeni fejlesztéseket és az új funkciókat kérem, tegye meg. A támogatás teljesen önkéntes, de nagyon megbecsült és mértéke is tetszőleges.<br><br>Ha támogatni szertne, kérem, válasszon az alábbi lehetőségekből vagy látogassa meg a https://zoneminder.com/donate/ oldalt.<br><br>Köszönjük, hogy használja a ZoneMinder-t és ne felejtse el meglátogatni a fórumokat a ZoneMinder.com oldalon támogatásért és ötletekért, hogy a jövőben is még jobban ki tudja használni a ZoneMinder lehetőségeit.',
    'DonateRemindDay'      => 'Nem most, figyelmeztessen egy nap múlva',
    'DonateRemindHour'     => 'Nem most, figyelmeztessen egy óra múlva',
    'DonateRemindMonth'    => 'Nem most, figyelmeztessen egy hónap múlva',
    'DonateRemindNever'    => 'Nem szeretném támogatni, ne is emlékeztessen',
    'DonateRemindWeek'     => 'Nem most, figyelmeztessen egy hét múlva',
    'DonateYes'            => 'Igen, most szeretném támogatni',
    'Download'             => 'Letöltés',
    'DownloadVideo'        => 'Download Video',         // Added - 2018-08-30
    'DuplicateMonitorName' => 'Kameranév duplikálás',
    'Duration'             => 'Időtartam',
    'Edit'                 => 'Szerkesztés',
    'EditLayout'           => 'Edit Layout',            // Added - 2018-08-30
    'Email'                => 'E-mail',
    'EnableAlarms'         => 'Riasztások engedélyezése',
    'Enabled'              => 'Engedélyezve',
    'EnterNewFilterName'   => 'Adja meg az új szűrő nevét',
    'Error'                => 'Hiba',
    'ErrorBrackets'        => 'Hiba: kérem ellenőrizze, hogy a zárójel-párok rendben vannak-e',
    'ErrorValidValue'      => 'Hiba: kérem ellenőrizze, hogy minden beállításnak érvényes értéke van-e',
    'Etc'                  => 'stb',
    'Event'                => 'Esemény',
    'EventFilter'          => 'Esemény szűrő',
    'EventId'              => 'Esemény azonosító',
    'EventName'            => 'Esemény neve',
    'EventPrefix'          => 'Eseménynév előtag',
    'Events'               => 'Események',
    'Exclude'              => 'Kizárás',
    'Execute'              => 'Végrehajtás',
    'Exif'                 => 'Embed EXIF data into image', // Added - 2018-08-30
    'Export'               => 'Exportálás',
    'ExportDetails'        => 'Esemény adatainak exportálása',
    'ExportFailed'         => 'Az exportálás sikertelen',
    'ExportFormat'         => 'Tömörített exportfájl formátuma',
    'ExportFormatTar'      => 'TAR',
    'ExportFormatZip'      => 'ZIP',
    'ExportFrames'         => 'Képkockák adatainak exportálása',
    'ExportImageFiles'     => 'Képkockák exportálása',
    'ExportLog'            => 'Naplók exportálása',
    'ExportMiscFiles'      => 'Egyéb fájlok exportálása (ha vannak)',
    'ExportOptions'        => 'Exportálás beállításai',
    'ExportSucceeded'      => 'Az exportálás sikerült',
    'ExportVideoFiles'     => 'Videófájlok exportálása (ha vannak)',
    'Exporting'            => 'Exportálás folyamatban',
    'FPS'                  => 'fps',
    'FPSReportInterval'    => 'FPS naplózásának gyakorisága',
    'FTP'                  => 'FTP',
    'Far'                  => 'Távol',
    'FastForward'          => 'Előre tekerés',
    'Feed'                 => 'Folyam',
    'Ffmpeg'               => 'ffmpeg',
    'File'                 => 'Fájl',
    'Filter'               => 'Filter',                 // Added - 2015-04-18
    'FilterArchiveEvents'  => 'Minden találat archiválása',
    'FilterDeleteEvents'   => 'Minden találat törlése',
    'FilterEmailEvents'    => 'Minden találat adatainak küldése E-mailben',
    'FilterExecuteEvents'  => 'Parancs futtatása minden találaton',
    'FilterLog'            => 'Filter log',             // Added - 2015-04-18
    'FilterMessageEvents'  => 'Minden találat adatainak üzenése',
    'FilterMoveEvents'     => 'Move all matches',       // Added - 2018-08-30
    'FilterPx'             => 'Szűrt képkockák',
    'FilterUnset'          => 'Meg kell adnod a szűrő szélességét és magasságát',
    'FilterUpdateDiskSpace'=> 'Update used disk space', // Added - 2018-08-30
    'FilterUploadEvents'   => 'Minden találat feltöltése',
    'FilterVideoEvents'    => 'Videó készítése minden találatról',
    'Filters'              => 'Szűrők',
    'First'                => 'Első',
    'FlippedHori'          => 'Vízszintes tükrözés',
    'FlippedVert'          => 'Függőleges tükrözés',
    'FnMocord'             => 'Folyamatos mozgással',
    'FnModect'             => 'Mozgásérzékelő',
    'FnMonitor'            => 'Csak monitorozás',
    'FnNodect'             => 'Külső érzékelő',
    'FnNone'               => 'Letiltva',
    'FnRecord'             => 'Folyamatos felvétel',
    'Focus'                => 'Fókusz',
    'ForceAlarm'           => 'Kézi riasztás',
    'Format'               => 'Formátum',
    'Frame'                => 'Képkocka',
    'FrameId'              => 'Képkocka azonosító',
    'FrameRate'            => 'FPS',
    'FrameSkip'            => 'Képkocka kihagyás',
    'Frames'               => 'Képkocka',
    'Func'                 => 'Funk.',
    'Function'             => 'Funkció',
    'Gain'                 => 'Erősítés',
    'General'              => 'Általános',
    'GenerateDownload'     => 'Generate Download',      // Added - 2018-08-30
    'GenerateVideo'        => 'Videó készítés',
    'GeneratingVideo'      => 'Videó készítése folyamatban',
    'GoToZoneMinder'       => 'Ellenőrzés a ZoneMinder.com-on',
    'Grey'                 => 'Szürke',
    'Group'                => 'Csoport',
    'Groups'               => 'Csoportok',
    'HasFocusSpeed'        => 'Van fókusz sebesség',
    'HasGainSpeed'         => 'Van AGC sebesség',
    'HasHomePreset'        => 'Van alapállás profilja',
    'HasIrisSpeed'         => 'Van írisz sebesség',
    'HasPanSpeed'          => 'Van jobb-bal sebesség',
    'HasPresets'           => 'Vannak profiljai',
    'HasTiltSpeed'         => 'Van le-fel sebesség',
    'HasTurboPan'          => 'Van gyors jobb-bal',
    'HasTurboTilt'         => 'Van gyors le-fel',
    'HasWhiteSpeed'        => 'Van fehér egyensúly sebesség',
    'HasZoomSpeed'         => 'Van zoom sebesség',
    'High'                 => 'Magas',
    'HighBW'               => 'Magas<br>sávszél',
    'Home'                 => 'Alapba',
    'Hostname'             => 'Hostname',               // Added - 2018-08-30
    'Hour'                 => 'Órában',
    'Hue'                  => 'Színárnyalat',
    'Id'                   => 'Az.',
    'Idle'                 => 'Nyugalom',
    'Ignore'               => 'Figyelmen kívül hagyás',
    'Image'                => 'Kép',
    'ImageBufferSize'      => 'Képkockák száma a pufferben',
    'Images'               => 'Kép',
    'In'                   => 'In',
    'Include'              => 'Beágyaz',
    'Inverted'             => 'Invertálva',
    'Iris'                 => 'Írisz',
    'KeyString'            => 'Kulcs karaktersor',
    'Label'                => 'Cimke',
    'Language'             => 'Nyelv',
    'Last'                 => 'Utolsó',
    'Layout'               => 'Elrendezés',
    'Level'                => 'Szint', 
    'Libvlc'               => 'Libvlc',
    'LimitResultsPost'     => 'találatra', // This is used at the end of the phrase 'Limit to first N results only'
    'LimitResultsPre'      => 'Csak az első', // This is used at the beginning of the phrase 'Limit to first N results only'
    'Line'                 => 'Sor',
    'LinkedMonitors'       => 'Összefüggés más kamerákkal<br>(jelölés Ctrl+kattintással)',
    'List'                 => 'Lista',
    'ListMatches'          => 'List Matches',           // Added - 2018-08-30
    'Load'                 => 'Terhelés',
    'Local'                => 'Helyi',
    'Log'                  => 'Napló',
    'LoggedInAs'           => 'Bejelentkezve mint',
    'Logging'              => 'Naplózás',
    'LoggingIn'            => 'Bejelentkezés folyamatban',
    'Login'                => 'Bejelentkezés',
    'Logout'               => 'Kilépés',
    'Logs'                 => 'Naplók',
    'Low'                  => 'Alacsony',
    'LowBW'                => 'Alacsony<br>sávszél',
    'Main'                 => 'Fő',
    'Man'                  => 'Man',
    'Manual'               => 'Kézikönyv',
    'Mark'                 => 'Jelölő',
    'Max'                  => 'Max.',
    'MaxBandwidth'         => 'Max. sávszélesség',
    'MaxBrScore'           => 'Max.<br/>érték',
    'MaxFocusRange'        => 'Max. fókusz tartomány',
    'MaxFocusSpeed'        => 'Max. fókusz sebesség',
    'MaxFocusStep'         => 'Max. fókusz lépték',
    'MaxGainRange'         => 'Max. AGC tartomány',
    'MaxGainSpeed'         => 'Max. AGC sebesség',
    'MaxGainStep'          => 'Max. AGC lépték',
    'MaxIrisRange'         => 'Max. írisz tartomány',
    'MaxIrisSpeed'         => 'Max. írisz sebesség',
    'MaxIrisStep'          => 'Max. írisz lépték',
    'MaxPanRange'          => 'Max. jobb-bal tartomány',
    'MaxPanSpeed'          => 'Max. jobb-bal sebesség',
    'MaxPanStep'           => 'Max. jobb-bal lépték',
    'MaxTiltRange'         => 'Max. fel-le tartomány',
    'MaxTiltSpeed'         => 'Max. fel-le sebesség',
    'MaxTiltStep'          => 'Max. fel-le lépték',
    'MaxWhiteRange'        => 'Max. fehér egyensúly tartomány',
    'MaxWhiteSpeed'        => 'Max. fehér egyensúly sebesség',
    'MaxWhiteStep'         => 'Max. fehér egyensúly lépték',
    'MaxZoomRange'         => 'Max. zoom tartomány',
    'MaxZoomSpeed'         => 'Max. zoom sebesség',
    'MaxZoomStep'          => 'Max. zoom lépték',
    'MaximumFPS'           => 'Maximális FPS nyugalmi állapotban',
    'Medium'               => 'Közepes',
    'MediumBW'             => 'Közepes<br>sávszél',
    'Message'              => 'Üzenet',
    'MinAlarmAreaLtMax'    => 'A minimum riasztott területnek kisebbnek kell lennie mint a maximumnak',
    'MinAlarmAreaUnset'    => 'Meg kell adnod a minimum képpont számot, ami riasztást okoz',
    'MinBlobAreaLtMax'     => 'A minimum blob területnek kisebbnek kell lennie mint a maximumnak',
    'MinBlobAreaUnset'     => 'Meg kell adnod a minimum blob képpont számot, ami riasztást okoz',
    'MinBlobLtMinFilter'   => 'A minimum blob területnek kisebbnek vagy egyenlőnek kell lennie a megszűrt területtel',
    'MinBlobsLtMax'        => 'A minimum bloboknak kisebbeknek kell lenniük, mint a maximum',
    'MinBlobsUnset'        => 'Meg kell adnod a blobok számát',
    'MinFilterAreaLtMax'   => 'A minimum megszűrt területnek kisebbnek kell lennie mint a maximum',
    'MinFilterAreaUnset'   => 'Meg kell adnod a megszűrt terület képpontjainak számát',
    'MinFilterLtMinAlarm'  => 'A megszűrt területnek kisebbnek vagy ugyanakkorának kell lennie mint a riasztott terület',
    'MinFocusRange'        => 'Min. fókusz terület',
    'MinFocusSpeed'        => 'Min. fókusz sebesség',
    'MinFocusStep'         => 'Min. fókusz lépték',
    'MinGainRange'         => 'Min. AGC tartomány',
    'MinGainSpeed'         => 'Min AGC sebesség',
    'MinGainStep'          => 'Min. AGC lépték',
    'MinIrisRange'         => 'Min. írisz terület',
    'MinIrisSpeed'         => 'Min. írisz sebesség',
    'MinIrisStep'          => 'Min. írisz lépték',
    'MinPanRange'          => 'Min. jobb-bal tartomány',
    'MinPanSpeed'          => 'Min. jobb-bal sebesség',
    'MinPanStep'           => 'Min. jobb-bal lépték',
    'MinPixelThresLtMax'   => 'A képpont minimum eltérési küszöbének kisebbnek kell lennie, mint a maximum',
    'MinPixelThresUnset'   => 'Meg kell adnod a képpont minimum eltérési küszöbét',
    'MinTiltRange'         => 'Min. fel-le tartomány',
    'MinTiltSpeed'         => 'Min. fel-le sebesség',
    'MinTiltStep'          => 'Min. fel-le lépték',
    'MinWhiteRange'        => 'Min. fehér egyensúly terület',
    'MinWhiteSpeed'        => 'Min. fehér egyensúly sebesség',
    'MinWhiteStep'         => 'Min. fehér egyensúly lépték',
    'MinZoomRange'         => 'Min. zoom terület',
    'MinZoomSpeed'         => 'Min. zoom sebesség',
    'MinZoomStep'          => 'Min. zoom lépték',
    'Misc'                 => 'Egyéb',
    'Mode'                 => 'Mode',                   // Added - 2015-04-18
    'Monitor'              => 'Kamera',
    'MonitorIds'           => 'Kamera&nbsp;azonosítók',
    'MonitorPreset'        => 'Előre beállított kameraprofilok',
    'MonitorPresetIntro'   => 'Kérem, válasszon egy kész kameraprofilt az alábbiak közül.<br>Figyelem: ez felülírja a korábban már beállított értékeket.<br><br>',
    'MonitorProbe'         => 'Kamerajel észlelés',
    'MonitorProbeIntro'    => 'Az alábbi listában találhatók az automatikusan érzékelt analóg és hálózati kamerákat, illetve azt, hogy közülük melyik van használatban, vagy kiválasztható.<br/><br/>Válasszon egyet az alábbi listából.<br/><br/>Figyelem: nem biztos, hogy minden kamerát lehet automatikusan érzékelni. Az itt kiválasztott kamara adatai felülírhatják azokat, amelyeket már ehhez a monitorhoz beállított.<br/><br/>',
    'Monitors'             => 'Kamerák',
    'Montage'              => 'Többkamerás nézet',
    'MontageReview'        => 'Montage Review',         // Added - 2018-08-30
    'Month'                => 'Hónapban',
    'More'                 => 'Több',
    'MotionFrameSkip'      => 'Motion Frame Skip',
    'Move'                 => 'Mozgás',
    'Mtg2widgrd'           => '2 oszlopban',
    'Mtg3widgrd'           => '3 oszlopban',
    'Mtg3widgrx'           => '3 oszlopban skálázva, riasztás esetén kinagyítva',
    'Mtg4widgrd'           => '4 oszlopban',
    'MtgDefault'           => 'Böngésző alapértelmezése szerint',
    'MustBeGe'             => 'nagyobbnak vagy egyenlőnek kell lennie',
    'MustBeLe'             => 'kisebbnek vagy egyenlőnek kell lennie',
    'MustConfirmPassword'  => 'Meg kell erősítenie a jelszót',
    'MustSupplyPassword'   => 'Meg kell adnia a jelszót',
    'MustSupplyUsername'   => 'Meg kell adnia felhasználói nevet',
    'Name'                 => 'Név',
    'Near'                 => 'Közel',
    'Network'              => 'Hálózat',
    'New'                  => 'Uj',
    'NewGroup'             => 'Új csoport',
    'NewLabel'             => 'Új cimke',
    'NewPassword'          => 'Új jelszó',
    'NewState'             => 'Új állapot neve',
    'NewUser'              => 'Új felhasználó',
    'Next'                 => 'Következő',
    'No'                   => 'Nem',
    'NoDetectedCameras'    => 'Nem érzékelhetőek kamerák',
    'NoDetectedProfiles'   => 'No Detected Profiles',   // Added - 2018-08-30
    'NoFramesRecorded'     => 'Nincs rögzített képkocka ehhez az eseményhez',
    'NoGroup'              => 'Nincs csoport',
    'NoSavedFilters'       => 'Nincs mentett szűrő',
    'NoStatisticsRecorded' => 'Nincs mentett statisztika ehhez az eseményhez/képkockához',
    'None'                 => 'Nincs kiválasztva',
    'NoneAvailable'        => 'Nem elérhető',
    'Normal'               => 'Normál',
    'Notes'                => 'Megjegyzések',
    'NumPresets'           => 'Profilok száma',
    'Off'                  => 'Ki',
    'On'                   => 'Be',
    'OnvifCredentialsIntro'=> 'Please supply user name and password for the selected camera.<br/>If no user has been created for the camera then the user given here will be created with the given password.<br/><br/>', // Added - 2015-04-18
    'OnvifProbe'           => 'ONVIF',                  // Added - 2015-04-18
    'OnvifProbeIntro'      => 'The list below shows detected ONVIF cameras and whether they are already being used or available for selection.<br/><br/>Select the desired entry from the list below.<br/><br/>Please note that not all cameras may be detected and that choosing a camera here may overwrite any values you already have configured for the current monitor.<br/><br/>', // Added - 2015-04-18
    'OpEq'                 => 'egyenlő',
    'OpGt'                 => 'nagyobb mint',
    'OpGtEq'               => 'nagyobb van egyenlő',
    'OpIn'                 => 'beállítva',
    'OpIs'                 => 'is',                     // Added - 2018-08-30
    'OpIsNot'              => 'is not',                 // Added - 2018-08-30
    'OpLt'                 => 'kisebb mint',
    'OpLtEq'               => 'kisebb vagy egyenlő',
    'OpMatches'            => 'találatok',
    'OpNe'                 => 'nem egyenlő',
    'OpNotIn'              => 'nincs beállítva',
    'OpNotMatches'         => 'nincs találat',
    'Open'                 => 'Megnyitás',
    'OptionHelp'           => 'Beállítási segítség',
    'OptionRestartWarning' => 'Ez a beállítás nem tud érvénybe lépni miközben az élő rendszer fut.\nHa végzett minden beállítással, kérem, indítsa újra a ZoneMinder szolgáltatást.',
    'OptionalEncoderParam' => 'Optional Encoder Parameters', // Added - 2018-08-30
    'Options'              => 'Beállítások',
    'OrEnterNewName'       => 'vagy új néven:',
    'Order'                => 'Sorrend',
    'Orientation'          => 'Orientáció',
    'Out'                  => 'Kifelé',
    'OverwriteExisting'    => 'Meglévő felülírása',
    'Paged'                => 'Lapozva',
    'Pan'                  => 'Jobb-bal mozgatás',
    'PanLeft'              => 'Mozgatás balra',
    'PanRight'             => 'Mozgatás jobbra',
    'PanTilt'              => 'Mozgat',
    'Parameter'            => 'Paraméter',
    'Password'             => 'Jelszó',
    'PasswordsDifferent'   => 'Az új és a megerősített jelszó különböznek!',
    'Paths'                => 'Útvonalak',
    'Pause'                => 'Szünet',
    'Phone'                => 'Telefonon betárcsázva',
    'PhoneBW'              => 'Mobil<br>sávszél',
    'Pid'                  => 'PID',
    'PixelDiff'            => 'Képpont eltérés',
    'Pixels'               => 'képpont',
    'Play'                 => 'Lejátszás',
    'PlayAll'              => 'Mind lejátszása',
    'PleaseWait'           => 'Kérlek várj...',
    'Plugins'              => 'Plugins',                // Added - 2015-04-18
    'Point'                => 'Pont',
    'PostEventImageBuffer' => 'Esemény utáni képkockák a pufferben',
    'PreEventImageBuffer'  => 'Esemény elötti képkockák a pufferben',
    'PreserveAspect'       => 'Méretarány megtartása',
    'Preset'               => 'Előre beállított profil',
    'Presets'              => 'Előre beállított profilok',
    'Prev'                 => 'Előző',
    'Probe'                => 'Érzékelés',
    'ProfileProbe'         => 'Stream Probe',           // Added - 2015-04-18
    'ProfileProbeIntro'    => 'The list below shows the existing stream profiles of the selected camera .<br/><br/>Select the desired entry from the list below.<br/><br/>Please note that ZoneMinder cannot configure additional profiles and that choosing a camera here may overwrite any values you already have configured for the current monitor.<br/><br/>', // Added - 2015-04-18
    'Progress'             => 'Progress',               // Added - 2015-04-18
    'Protocol'             => 'Protocol',
    'RTSPDescribe'         => 'Use RTSP Response Media URL', // Added - 2018-08-30
    'RTSPTransport'        => 'RTSP Transport Protocol', // Added - 2018-08-30
    'Rate'                 => 'FPS',
    'Real'                 => 'Valós',
    'RecaptchaWarning'     => 'Your reCaptcha secret key is invalid. Please correct it, or reCaptcha will not work', // Added - 2018-08-30
    'Record'               => 'Felvétel',
    'RecordAudio'          => 'Whether to store the audio stream when saving an event.', // Added - 2018-08-30
    'RefImageBlendPct'     => 'Változás a referenciaképtől %-ban',
    'Refresh'              => 'Frissítés',
    'Remote'               => 'Hálózati',
    'RemoteHostName'       => 'Hálózati IP cím/hosztnév',
    'RemoteHostPath'       => 'A kép elérési útvonala',
    'RemoteHostPort'       => 'Hálózati portszám',
    'RemoteHostSubPath'    => 'A kép elérési al-útvonala',
    'RemoteImageColours'   => 'A kép színe',
    'RemoteMethod'         => 'Hálózati cím mód',
    'RemoteProtocol'       => 'Hálózati protokoll',
    'Rename'               => 'Átnevezés',
    'Replay'               => 'Események visszajátszása',
    'ReplayAll'            => 'Mindet',
    'ReplayGapless'        => 'Szünet nélkülieket',
    'ReplaySingle'         => 'Egyenként',
    'ReportEventAudit'     => 'Audit Events Report',    // Added - 2018-08-30
    'Reset'                => 'Alapértékre',
    'ResetEventCounts'     => 'Eseményszámláló nullázása',
    'Restart'              => 'A szolgáltatás újraindítása',
    'Restarting'           => 'Újraindítás',
    'RestrictedCameraIds'  => 'Korlátozott kamerák azonosítói',
    'RestrictedMonitors'   => 'Korlátozott kamerák',
    'ReturnDelay'          => 'Visszaérkezés késleltetése',
    'ReturnLocation'       => 'Visszaérkezés helye',
    'Rewind'               => 'Visszatekerés',
    'RotateLeft'           => 'Balra forgatás',
    'RotateRight'          => 'Jobbra forgatás',
    'RunLocalUpdate'       => 'Kérem, futtassa le a zmupdate.pl szkriptet a frissítéshez.',
    'RunMode'              => 'Futási mód',
    'RunState'             => 'A ZoneMinder állapota',
    'Running'              => 'Élő',
    'Save'                 => 'Mentés',
    'SaveAs'               => 'Mentés erre:',
    'SaveFilter'           => 'Szűrő mentése',
    'SaveJPEGs'            => 'Save JPEGs',             // Added - 2018-08-30
    'Scale'                => 'Méret',
    'Score'                => 'Pontszám',
    'Secs'                 => 'mp.',
    'Sectionlength'        => 'Fix időtartamú esemény hossza',
    'Select'               => 'Kiválasztás',
    'SelectFormat'         => 'Válasszon formátumot',
    'SelectLog'            => 'Válasszon naplót',
    'SelectMonitors'       => 'Kamerák kiválasztása',
    'SelfIntersecting'     => 'A sokszög szélei nem kereszteződhetnek',
    'Set'                  => 'Beállít',
    'SetNewBandwidth'      => 'Sávszélességi profil választása a böngészöhöz',
    'SetPreset'            => 'Profil beállítása',
    'Settings'             => 'Beállítások',
    'ShowFilterWindow'     => 'Szűrőablak megjelenítése',
    'ShowTimeline'         => 'Idővonal megjelenítése',
    'SignalCheckColour'    => 'Szín a jel kimaradásakor',
    'SignalCheckPoints'    => 'Signal Check Points',    // Added - 2018-08-30
    'Size'                 => 'Fájlméret',
    'SkinDescription'      => 'Alapértelmezett felület ebben a böngészőben',
    'Sleep'                => 'Alvás',
    'SortAsc'              => 'Növekvő',
    'SortBy'               => 'Sorbarendezés:',
    'SortDesc'             => 'Csökkenő',
    'Source'               => 'Jelforrás',
    'SourceColours'        => 'A kép színe',
    'SourcePath'           => 'A kép elérési útvonala',
    'SourceType'           => 'Jelforrás típusa',
    'Speed'                => 'Sebesség',
    'SpeedHigh'            => 'Nagy sebsség',
    'SpeedLow'             => 'Alacsony sebesség',
    'SpeedMedium'          => 'Közepes sebesség',
    'SpeedTurbo'           => 'Turbó sebesség',
    'Start'                => 'Indít',
    'State'                => 'Állapot',
    'Stats'                => 'Statisztikák',
    'Status'               => 'Státusz',
    'StatusConnected'      => 'Capturing',              // Added - 2018-08-30
    'StatusNotRunning'     => 'Not Running',            // Added - 2018-08-30
    'StatusRunning'        => 'Not Capturing',          // Added - 2018-08-30
    'StatusUnknown'        => 'Unknown',                // Added - 2018-08-30
    'Step'                 => 'Ugrás',
    'StepBack'             => 'Visszalépés',
    'StepForward'          => 'Előrelépés',
    'StepLarge'            => 'Nagy ugrás',
    'StepMedium'           => 'Közepes ugrás',
    'StepNone'             => 'Nincs ugrás',
    'StepSmall'            => 'Kis ugrás',
    'Stills'               => 'Állóképek',
    'Stop'                 => 'A szolgáltatás leállítása',
    'Stopped'              => 'Leállítva',
    'StorageArea'          => 'Storage Area',           // Added - 2018-08-30
    'StorageScheme'        => 'Scheme',                 // Added - 2018-08-30
    'Stream'               => 'Élő folyam',
    'StreamReplayBuffer'   => 'Képkockák száma a pufferben visszajátszáskor',
    'Submit'               => 'Küldés',
    'System'               => 'Rendszer',
    'SystemLog'            => 'Rendszernapló',
    'TargetColorspace'     => 'Target colorspace',      // Added - 2015-04-18
    'Tele'                 => 'Táv',
    'Thumbnail'            => 'Előnézet',
    'Tilt'                 => 'Fel-le mozgás',
    'Time'                 => 'Időpont',
    'TimeDelta'            => 'Idő változás',
    'TimeStamp'            => 'Időbélyeg',
    'Timeline'             => 'Idővonal',
    'TimelineTip1'         => 'Mozgassa az egeret a grafikon fölött, hogy képet és adatokat láthasson az eseményről.',
    'TimelineTip2'         => 'Kattintson a grafikon színes részére, vagy a pillanatképre, hogy láthassa a részleteket.',
    'TimelineTip3'         => 'Kattintson a grafikon hátterére, hogy az időskálába nagyítson tetszőleges időpontban.',
    'TimelineTip4'         => 'Használja az alábbi gombokat hogy az időskálát csúsztassa, vagy kicsinyítse.',
    'Timestamp'            => 'Időbélyeg',
    'TimestampLabelFormat' => 'Időbélyeg formátuma',
    'TimestampLabelSize'   => 'Font Size',              // Added - 2018-08-30
    'TimestampLabelX'      => 'Elhelyezés X pozició',
    'TimestampLabelY'      => 'Elhelyezés Y pozició',
    'Today'                => 'Ma',
    'Tools'                => 'Eszközök',
    'Total'                => 'Összes',
    'TotalBrScore'         => 'Össz.<br/>pontszám',
    'TrackDelay'           => 'Késleltetés követése',
    'TrackMotion'          => 'Mozgás követése',
    'Triggers'             => 'Külső érzékelők (triggers)',
    'TurboPanSpeed'        => 'Jobb-bal gyorssebesség',
    'TurboTiltSpeed'       => 'Fel-le gyorssebesség',
    'Type'                 => 'Típus',
    'Unarchive'            => 'Archívumból ki',
    'Undefined'            => 'Nincs megadva',
    'Units'                => 'Egység',
    'Unknown'              => 'Ismeretlen',
    'Update'               => 'Frissítés',
    'UpdateAvailable'      => 'Elérhető ZoneMinder frissítés.',
    'UpdateNotNecessary'   => 'Nem szükséges a frissítés.',
    'Updated'              => 'Frissítve',
    'Upload'               => 'Feltöltés',
    'UseFilter'            => 'Szűrőt használ',
    'UseFilterExprsPost'   => '&nbsp;szürés&nbsp; használata', // This is used at the end of the phrase 'use N filter expressions'
    'UseFilterExprsPre'    => '&nbsp;', // This is used at the beginning of the phrase 'use N filter expressions'
    'UsedPlugins'          => 'Used Plugins',           // Added - 2015-04-18
    'User'                 => 'Felhasználó',
    'Username'             => 'Felhasználónév',
    'Users'                => 'Felhasználók',
    'V4L'                  => 'V4L',                    // Added - 2015-04-18
    'V4LCapturesPerFrame'  => 'Captures Per Frame',     // Added - 2015-04-18
    'V4LMultiBuffer'       => 'Multi Buffering',        // Added - 2015-04-18
    'Value'                => 'Érték',
    'Version'              => 'Verzió',
    'VersionIgnore'        => 'Ezen verzió figyelmen kívül hagyása',
    'VersionRemindDay'     => 'Egy nap múlva emlékeztessen',
    'VersionRemindHour'    => 'Egy óra múlva emlékeztessen',
    'VersionRemindNever'   => 'Ne emlékeztessen az új verzióról',
    'VersionRemindWeek'    => 'Egy hét múlva emlékeztessen',
    'Video'                => 'Videó',
    'VideoFormat'          => 'Videó formátum',
    'VideoGenFailed'       => 'A videó készítése sikertelen.',
    'VideoGenFiles'        => 'Létező videók',
    'VideoGenNoFiles'      => 'Nem találhatók videók',
    'VideoGenParms'        => 'Videó készítési paraméterek',
    'VideoGenSucceeded'    => 'A videó elkészült.',
    'VideoSize'            => 'Képméret',
    'VideoWriter'          => 'Video Writer',           // Added - 2018-08-30
    'View'                 => 'Megtekintés',
    'ViewAll'              => 'Az összes listázása',
    'ViewEvent'            => 'Események nézet',
    'ViewPaged'            => 'Oldal nézet',
    'Wake'                 => 'Ébresztés',
    'WarmupFrames'         => 'Bemelegítő képkockák',
    'Watch'                => 'Figyelés',
    'Web'                  => 'Web',
    'WebColour'            => 'Szín az idővonal skálán',
    'WebSiteUrl'           => 'Website URL',            // Added - 2018-08-30
    'Week'                 => 'Héten',
    'White'                => 'Fehér',
    'WhiteBalance'         => 'Fehér egyensúly',
    'Wide'                 => 'Széles',
    'X'                    => 'X',
    'X10'                  => 'X10',
    'X10ActivationString'  => 'X10 élesítő karaktersor',
    'X10InputAlarmString'  => 'X10 bemeneti riadó karaktersor',
    'X10OutputAlarmString' => 'X10 kimeneti riadó karaktersor',
    'Y'                    => 'Y',
    'Yes'                  => 'Igen',
    'YouNoPerms'           => 'Nincs joga az erőforrás eléréséhez.',
    'Zone'                 => 'Zóna:',
    'ZoneAlarmColour'      => 'Riasztott terület<br>színezése (R/G/B)',
    'ZoneArea'             => 'Zóna lefedettsége',
    'ZoneExtendAlarmFrames' => 'Extend Alarm Frame Count',
    'ZoneFilterSize'       => 'Szélesség és magasság<br>szűrés képpontban',
    'ZoneMinMaxAlarmArea'  => 'Min/Max riasztó terület',
    'ZoneMinMaxBlobArea'   => 'Min/Max Blob terület',
    'ZoneMinMaxBlobs'      => 'Min/Max Blobok',
    'ZoneMinMaxFiltArea'   => 'Min/Max szűrt terület',
    'ZoneMinMaxPixelThres' => 'Min/Max képpont változási<br>küszöb (0-255)',
    'ZoneMinderLog'        => 'ZoneMinder Napló',
    'ZoneOverloadFrames'   => 'Túlterhelés esetén<br>ennyi képkocka hagyható ki',
    'Zones'                => 'Zónák',
    'Zoom'                 => 'Zoom',
    'ZoomIn'               => 'Zoom be',
    'ZoomOut'              => 'Zoom ki',
);

// Complex replacements with formatting and/or placements, must be passed through sprintf
$CLANG = array(
    'CurrentLogin'         => 'Jelenleg belépve mint \'%1$s\'',
    'EventCount'           => '%1$s %2$s', // For example '37 Events' (from Vlang below)
    'LastEvents'           => 'Utolsó %1$s %2$s', // For example 'Last 37 Events' (from Vlang below)
    'LatestRelease'        => 'Az utolsó kiadás verziószáma v%1$s, ami itt fent van v%2$s.',
    'MonitorCount'         => '%1$s %2$s', // For example '4 Monitors' (from Vlang below)
    'MonitorFunction'      => 'Kamerafunkció: %1$s',
    'RunningRecentVer'     => 'A legfrissebb ZoneMinder verziót használja: v%s.',
    'VersionMismatch'      => 'Verziószám eltérés: rendszerverzió %1$s, adatbázis %2$s.',
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
    'Event'                => array( 0=>'esemény', 1=>'esemény', 2=>'esemény' ),
    'Monitor'              => array( 0=>'kamera', 1=>'kamera', 2=>'kamera' ),
);

// You will need to choose or write a function that can correlate the plurality string arrays
// with variable counts. This is used to conjugate the Vlang arrays above with a number passed
// in to generate the correct noun form.
//
// In languages such as English this is fairly simple 
// Note this still has to be used with printf etc to get the right formating
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
//echo sprintf( $zmClangMonitorCount, count($monitors), zmVlang( $zmVlangMonitor, count($monitors) ) );

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
