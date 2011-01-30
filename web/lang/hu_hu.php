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
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//

// ZoneMinder Hungarian Translation by szimszon at oregpreshaz dot eu, robi
// version: 0.6 - 2009.06.21. - frissítés 1.24.2-höz (robi)
// version: 0.5 - 2007.12.30. - frissítés 1.23.1-hez (robi)
// version: 0.4 - 2007.12.30. - frissítés 1.23.0-hoz (robi)
// version: 0.3 - 2006.04.27. - fordítás befejezése, elrendezése elféréshez (robi)
// version: 0.2 - 2006.12.05. - par javitas
// version: 0.1 - 2006.11.27. - sok typoval es par leforditatlan resszel

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
header( "Content-Type: text/html; charset=iso8859-2" );

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

setlocale( LC_TIME, 'hu_HU' );
setlocale( LC_ALL, 'hu_HU' );

// Simple String Replacements
$SLANG = array(
    '24BitColour'          => '24 bites szín',
    '8BitGrey'             => '8 bit szürkeárnyalat',
    'Action'               => 'Mûvelet',
    'Actual'               => 'Valós',
    'AddNewControl'        => 'Új vezérlés',
    'AddNewMonitor'        => 'Új monitor',
    'AddNewUser'           => 'Új felhasználó',
    'AddNewZone'           => 'Új zóna',
    'Alarm'                => 'Riadó',
    'AlarmBrFrames'        => 'Riadó<br/>képek',
    'AlarmFrame'           => 'Riadó kép',
    'AlarmFrameCount'      => 'Riadó képek száma',
    'AlarmLimits'          => 'Riasztási határok',
    'AlarmMaximumFPS'      => 'Maximális FPS riasztásnál',
    'AlarmPx'              => 'Riadó képpont',
    'AlarmRGBUnset'        => 'Be kell állítani egy RGB színt a riasztáshoz',
    'Alert'                => 'Riasztás',
    'All'                  => 'Mind',
    'Apply'                => 'Alkalmaz',
    'ApplyingStateChange'  => 'Állapot váltás...',
    'ArchArchived'         => 'Csak archivált',
    'ArchUnarchived'       => 'Csak archiválatlan',
    'Archive'              => 'Archiválás',
    'Archived'             => 'Archívum',
    'Area'                 => 'Terület',
    'AreaUnits'            => 'Terület (képpont / %)',
    'AttrAlarmFrames'      => 'Riadó képkockák',
    'AttrArchiveStatus'    => 'Archivált állapot',
    'AttrAvgScore'         => 'Átlagos érték',
    'AttrCause'            => 'Okozó',
    'AttrDate'             => 'Dátum',
    'AttrDateTime'         => 'Dátum/Idõ',
    'AttrDiskBlocks'       => 'Lemez blokkok',
    'AttrDiskPercent'      => 'Lemez százalék',
    'AttrDuration'         => 'Idõtartam',
    'AttrFrames'           => 'Képkockák',
    'AttrId'               => 'Azonosító',
    'AttrMaxScore'         => 'Max. érték',
    'AttrMonitorId'        => 'Monitor azon.',
    'AttrMonitorName'      => 'Monitor név',
    'AttrName'             => 'Név',
    'AttrNotes'            => 'Megjegyzés',
    'AttrSystemLoad'       => 'Rendszer terhelés',
    'AttrTime'             => 'Idõ',
    'AttrTotalScore'       => 'Össz. érték',
    'AttrWeekday'          => 'Hétköznap',
    'Auto'                 => 'Auto',
    'AutoStopTimeout'      => 'Auto megállási idõ túllépés',
    'Available'            => 'Elérhetõ',
    'AvgBrScore'           => 'Átlag<br/>érték',
    'Background'           => 'Háttér',
    'BackgroundFilter'     => 'Szûrõ futtatása a háttérben',
    'BadAlarmFrameCount'   => 'Riadó képek száma 1 vagy nagyobb egész szám legyen',
    'BadAlarmMaxFPS'       => 'A riadó maximális FPS száma pozitív szám legyen',
    'BadChannel'           => 'A csatorna száma 0 vagy nagyobb egész szám legyen',
    'BadDevice'            => 'Az eszköz érték valós legyen',
    'BadFPSReportInterval' => 'FPS információs idõköz puffer számlálója 100 vagy nagyobb egész legyen',
    'BadFormat'            => 'A típus 0 vagy nagyobb egész szám legyen',
    'BadFrameSkip'         => 'Képkocka eldobások száma 0 vagy nagyobb egész szám legyen',
    'BadHeight'            => 'A képmagasság érvényes érték legyen képpontban',
    'BadHost'              => 'A hoszt valós IP cím vagy hosztnév legyen, http:// nélkül',
    'BadImageBufferCount'  => 'Kép puffer mérete legyen 10 vagy nagyobb szám',
    'BadLabelX'            => 'A cimke X koordinátája legyen 0 vagy nagyobb egész szám',
    'BadLabelY'            => 'A cimke Y koordinátája legyen 0 vagy nagyobb egész szám',
    'BadMaxFPS'            => 'A maximális FPS nullánál nagyobb szám legyen',
    'BadNameChars'         => 'A név csak alfanumerikus karaktereket, plusz-, kötõ-, és aláhúzásjelet tartalmazhat',
    'BadPalette'           => 'A palettának egy helyes értéket kell megadni',
    'BadPath'              => 'A kép elérési útvonala valós legyen',
    'BadPort'              => 'A portszám valós legyen',
    'BadPostEventCount'    => 'Az esemény utáni képek puffere 0 vagy nagyobb egész szám legyen',
    'BadPreEventCount'     => 'Az esemény elõtti képek puffere 0 vagy nagyobb egész szám legyen',
    'BadRefBlendPerc'      => 'A referencia képkeverék-százalék pozitív egész szám legyen',
    'BadSectionLength'     => 'Egy egység hossza 30 vagy hosszabb legyen',
    'BadSignalCheckColour' => 'A jel ellenõrzési szín egy érvényes RGP kód kell legyen',
    'BadStreamReplayBuffer'=> 'Folyam visszajátszó puffer 0 vagy nagyobb egész szám legyen',
    'BadWarmupCount'       => 'Bemelegítõ képek száma 0 vagy nagyobb egész szám legyen',
    'BadWebColour'         => 'A web szín érvényes web szín kód legyen',
    'BadWidth'             => 'A képszélesség érvényes érték legyen képpontban',
    'Bandwidth'            => 'sávszélességre',
    'BlobPx'               => 'Blob képpont',
    'BlobSizes'            => 'Blob mérete',
    'Blobs'                => 'Blob-ok',
    'Brightness'           => 'Fényerõ',
    'Buffers'              => 'Pufferek',
    'CanAutoFocus'         => 'Auto fókusz van',
    'CanAutoGain'          => 'Auto gain van',
    'CanAutoIris'          => 'Auto írisz van',
    'CanAutoWhite'         => 'Van autómata fehér egyensúly',
    'CanAutoZoom'          => 'Auto zoom van',
    'CanFocus'             => 'Tud fókuszálni',
    'CanFocusAbs'          => 'Tud abszolút fókuszt',
    'CanFocusCon'          => 'Tud folyamatos fókuszt',
    'CanFocusRel'          => 'Tud relatív fókuszt',
    'CanGain'              => 'Tud erõsíteni',
    'CanGainAbs'           => 'Tud abszolút erõsítést',
    'CanGainCon'           => 'Tud folyamatos erõsítést',
    'CanGainRel'           => 'Tud relatív erõsítést',
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
    'CanSetPresets'        => 'Tud menteni profilokat',
    'CanSleep'             => 'Tud phihenõ üzemmódot',
    'CanTilt'              => 'Tud fel-le mozgást',
    'CanWake'              => 'Tud feléledni',
    'CanWhite'             => 'Tud fehér egyensúlyt',
    'CanWhiteAbs'          => 'Tud abszolut fehér egyensúlyt',
    'CanWhiteBal'          => 'Tud fehér egyensúlyt',
    'CanWhiteCon'          => 'Tud folyamatos fehér egyensúlyt',
    'CanWhiteRel'          => 'Tud relatív fehér egyensúlyt',
    'CanZoom'              => 'Tud zoom-olni',
    'CanZoomAbs'           => 'Tud abszolut zoom-ot',
    'CanZoomCon'           => 'Tud folyamatos zoom-ot',
    'CanZoomRel'           => 'Tud relatív zoom-ot',
    'Cancel'               => 'Mégsem',
    'CancelForcedAlarm'    => 'Kézi riasztás leállítása',
    'CaptureHeight'        => 'Képmagasság',
    'CaptureMethod'        => 'Digitalizálás módszere',
    'CapturePalette'       => 'Digitalizálás szín-palettája',
    'CaptureWidth'         => 'Képszélesség',
    'Cause'                => 'Okozó',
    'CheckMethod'          => 'A riasztás figyelésének módja',
    'ChooseDetectedCamera' => 'Válasszon érzékelt kamerát',
    'ChooseFilter'         => 'Válassz szûrõt',
    'ChoosePreset'         => 'Válassz profilt',
    'Close'                => 'Bezár',
    'Colour'               => 'Szín',
    'Command'              => 'Parancs',
    'Config'               => 'Beállítás',
    'ConfiguredFor'        => 'Beállítva',
    'ConfirmDeleteEvents'  => 'Biztos benne, hogy törli a kiválasztott eseményeket?',
    'ConfirmPassword'      => 'Jelszó megerõsítés',
    'ConjAnd'              => 'és',
    'ConjOr'               => 'vagy',
    'Console'              => 'Konzol',
    'ContactAdmin'         => 'Kérem vegye fel a kapcsolatot a rendszergazdával a részletekért.',
    'Continue'             => 'Folytatás',
    'Contrast'             => 'Kontraszt',
    'Control'              => 'Vezérlés',
    'ControlAddress'       => 'Vezérlési jogok',
    'ControlCap'           => 'Vezérlési lehetõség',
    'ControlCaps'          => 'Vezérlési lehetõségek',
    'ControlDevice'        => 'Vezérlõ eszköz',
    'ControlType'          => 'Vezérlés típusa',
    'Controllable'         => 'Vezérelhetõ',
    'Cycle'                => 'Körbekapcsolás',
    'CycleWatch'           => 'Körbekapcsolás',
    'Day'                  => 'Napon',
    'Debug'                => 'Nyomon<br>követés',
    'DefaultRate'          => 'Alapértelmezett sebesség',
    'DefaultScale'         => 'Alapértelmezett méret',
    'DefaultView'          => 'Alapértelmezett nézet',
    'Delete'               => 'Töröl',
    'DeleteAndNext'        => 'Töröl &amp;<br>következõ',
    'DeleteAndPrev'        => 'Töröl &amp;<br>elõzõ',
    'DeleteSavedFilter'    => 'Mentett szûrõ törlése',
    'Description'          => 'Leírás',
    'DetectedCameras'      => 'Érzékelt kamerák',
    'Device'               => 'Eszköz',
    'DeviceChannel'        => 'Eszköz csatornája',
    'DeviceFormat'         => 'Eszköz formátuma',
    'DeviceNumber'         => 'Eszköz szám',
    'DevicePath'           => 'Eszköz elérési útvonala',
    'Devices'              => 'Eszközök',
    'Dimensions'           => 'Dimenziók',
    'DisableAlarms'        => 'Riasztás tiltása',
    'Disk'                 => 'Tárhely',
    'Display'               => 'Megjelenés',
    'Donate'               => 'Kérem támogasson',
    'DonateAlready'        => 'Nem, én már támogattam',
    'DonateEnticement'     => 'Ön már jó ideje használja a ZoneMindert remélhetõleg hasznos kiegészítésnek tartja háza vagy munkahelye biztosításában. Bár ZoneMinder szabad, nyílt forráskódú, és az is marad; a fejlesztése pénzbe kerül. Ha támogatni szeretné a jövõbeni fejlesztéseket és az új funkciókat kérem támogasson. A támogatás teljesen önkéntes, de nagyon megbecsült és annyival tud támogatni amennyivel kíván.<br><br>Ha támogatni szertne kérem válasszon az alábbi lehetõségekbõl vagy látogassa meg a http://www.zoneminder.com/donate.html oldalt.<br><br>Köszönöm, hogy használja a ZoneMinder-t és ne felejtse el meglátogatni a fórumokat a ZoneMinder.com oldalon támogatásért és ötletekért, hogy tudja még jobban használni a ZoneMinder-t.',
    'DonateRemindDay'      => 'Nem most, figyelmeztess 1 nap múlva',
    'DonateRemindHour'     => 'Nem most, figyelmeztess 1 óra múlva',
    'DonateRemindMonth'    => 'Nem most, figyelmeztess 1 hónap múlva',
    'DonateRemindNever'    => 'Nem akarom támogatni, ne is emlékeztess',
    'DonateRemindWeek'     => 'Nem most, figyelmeztess 1 hét múlva',
    'DonateYes'            => 'Igen, most szeretném támogatni',
    'Download'             => 'Letölt',
    'DuplicateMonitorName' => 'Monitor nevének duplikálása',
    'Duration'             => 'Idõtartam',
    'Edit'                 => 'Szerkeszt',
    'Email'                => 'Email',
    'EnableAlarms'         => 'Riasztás feloldása',
    'Enabled'              => 'Engedélyezve',
    'EnterNewFilterName'   => 'Írd be az új szûrõ nevét',
    'Error'                => 'Hiba',
    'ErrorBrackets'        => 'Hiba, ellenõrizd, hogy ugyanannyi nyitó és záró zárójel van',
    'ErrorValidValue'      => 'Hiba, ellenõrizd, hogy minden beállításnak érvényes értéke van',
    'Etc'                  => 'stb',
    'Event'                => 'Esemény',
    'EventFilter'          => 'Esemény szûrõ',
    'EventId'              => 'Esemény azonosító',
    'EventName'            => 'Esemény név',
    'EventPrefix'          => 'Esemény elõtag',
    'Events'               => 'Események',
    'Exclude'              => 'Kizár',
    'Execute'              => 'Futtat',
    'Export'               => 'Exportál',
    'ExportDetails'        => 'Esemény adatainak exportálása',
    'ExportFailed'         => 'Hibás exportálás',
    'ExportFormat'         => 'Exportált fájl formátuma',
    'ExportFormatTar'      => 'TAR',
    'ExportFormatZip'      => 'ZIP',
    'ExportFrames'         => 'Képek adatainak exportálása',
    'ExportImageFiles'     => 'Képek exportálása',
    'ExportMiscFiles'      => 'Egyéb fájlok exportálása (ha vannak)',
    'ExportOptions'        => 'Exportálás beállításai',
    'ExportSucceeded'      => 'Az exportálás sikerült',
    'ExportVideoFiles'     => 'Videó fájlok exportálása (ha vannak)',
    'Exporting'            => 'Exportálás...',
    'FPS'                  => 'fps',
    'FPSReportInterval'    => 'FPS megjelenítés idõköze',
    'FTP'                  => 'FTP',
    'Far'                  => 'Távol',
    'FastForward'          => 'Elõre tekerés',
    'Feed'                 => 'Folyam',
    'Ffmpeg'               => 'Ffmpeg',
    'File'                 => 'Fájl',
    'FilterArchiveEvents'  => 'Minden találat archiválása',
    'FilterDeleteEvents'   => 'Minden találat törlése',
    'FilterEmailEvents'    => 'Minden találat adatainak elküldése E-mailben',
    'FilterExecuteEvents'  => 'Parancs futtatása minden találaton',
    'FilterMessageEvents'  => 'Minden találat adatainak üzenése',
    'FilterPx'             => 'Szûrt képkockák',
    'FilterUnset'          => 'Meg kell adnod a szûrõ szélességét és magasságát',
    'FilterUploadEvents'   => 'Minden találat feltöltése',
    'FilterVideoEvents'    => 'Videó készítése minden találatról',
    'Filters'              => 'Szûrõk',
    'First'                => 'Elsõ',
    'FlippedHori'          => 'Vízszintes tükrözés',
    'FlippedVert'          => 'Függõleges tükrözés',
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
    'Gain'                 => 'Erõsítés',
    'General'              => 'Általános',
    'GenerateVideo'        => 'Videó készítés',
    'GeneratingVideo'      => 'Videó készítése...',
    'GoToZoneMinder'       => 'Látogatás a ZoneMinder.com-ra',
    'Grey'                 => 'Szürke',
    'Group'                => 'Csoport',
    'Groups'               => 'Csoportok',
    'HasFocusSpeed'        => 'Van fókusz sebesség',
    'HasGainSpeed'         => 'Van erõsítés sebesség',
    'HasHomePreset'        => 'Van kedvenc profilja',
    'HasIrisSpeed'         => 'Van írisz sebesség',
    'HasPanSpeed'          => 'Van jobb-bal sebesség',
    'HasPresets'           => 'Vannak profiljai',
    'HasTiltSpeed'         => 'Van le-fel sebesség',
    'HasTurboPan'          => 'Van turbó jobb-bal',
    'HasTurboTilt'         => 'Van turbó le-fel',
    'HasWhiteSpeed'        => 'Van fehér egyensúly sebesség',
    'HasZoomSpeed'         => 'Van zoom sebesség',
    'High'                 => 'Magas',
    'HighBW'               => 'Magas<br>sávsz.',
    'Home'                 => 'Home',
    'Hour'                 => 'Órában',
    'Hue'                  => 'Színárnyalat',
    'Id'                   => 'Az.',
    'Idle'                 => 'Nyugalom',
    'Ignore'               => 'Figyelmen kívül hagy',
    'Image'                => 'Kép',
    'ImageBufferSize'      => 'Képpuffer mérete (képkockák)',
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
    'LimitResultsPost'     => 'találatig korlátoz', // This is used at the end of the phrase 'Limit to first N results only'
    'LimitResultsPre'      => 'Az elsõ', // This is used at the beginning of the phrase 'Limit to first N results only'
    'LinkedMonitors'       => 'Összefüggõ monitorok',
    'List'                 => 'Lista',
    'Load'                 => 'Terhelés',
    'Local'                => 'Helyi',
    'LoggedInAs'           => 'Bejelentkezve mint',
    'LoggingIn'            => 'Bejelentkezés folyamatban',
    'Login'                => 'Bejelentkezés',
    'Logout'               => 'Kilépés',
    'Low'                  => 'Alacsony',
    'LowBW'                => 'Alacsony<br>sávsz.',
    'Main'                 => 'Fõ',
    'Man'                  => 'Man',
    'Manual'               => 'Kézikönyv',
    'Mark'                 => 'Jelölés',
    'Max'                  => 'Max.',
    'MaxBandwidth'         => 'Max. sávszélesség',
    'MaxBrScore'           => 'Max.<br/>érték',
    'MaxFocusRange'        => 'Max. fókusz tartomány',
    'MaxFocusSpeed'        => 'Max. fókusz sebesség',
    'MaxFocusStep'         => 'Max. fókusz lépés',
    'MaxGainRange'         => 'Max Gain Range',
    'MaxGainSpeed'         => 'Max Gain Speed',
    'MaxGainStep'          => 'Max Gain Step',
    'MaxIrisRange'         => 'Max. írisz tartomány',
    'MaxIrisSpeed'         => 'Max. írisz sebesség',
    'MaxIrisStep'          => 'Max. írisz lépés',
    'MaxPanRange'          => 'Max. jobb-bal tartomány',
    'MaxPanSpeed'          => 'Max. jobb-bal sebesség',
    'MaxPanStep'           => 'Max. jobb-bal lépés',
    'MaxTiltRange'         => 'Max. fel-le tartomány',
    'MaxTiltSpeed'         => 'Max. fel-le sebesség',
    'MaxTiltStep'          => 'Max. fel-le lépés',
    'MaxWhiteRange'        => 'Max. fehér egyensúly tartomány',
    'MaxWhiteSpeed'        => 'Max. fehér egyensúly sebesség',
    'MaxWhiteStep'         => 'Max. fehér egyensúly lépés',
    'MaxZoomRange'         => 'Max. zoom tartomány',
    'MaxZoomSpeed'         => 'Max. zoom sebesség',
    'MaxZoomStep'          => 'Max. zoom lépés',
    'MaximumFPS'           => 'Maximum FPS',
    'Medium'               => 'Közepes',
    'MediumBW'             => 'Közepes<br>sávsz.',
    'MinAlarmAreaLtMax'    => 'A minimum riasztott területnek kisebbnek kell lennie mint a maximumnak',
    'MinAlarmAreaUnset'    => 'Meg kell adnod a minimum riasztott képpontok számát',
    'MinBlobAreaLtMax'     => 'A minimum blob területnek kisebbnek kell lennie mint a maximumnak',
    'MinBlobAreaUnset'     => 'Meg kell adnod a minimum blob képpontok számát',
    'MinBlobLtMinFilter'   => 'A minimum blob területnek kisebbnek vagy egyenlõnek kell lennie a megszûrt területtel',
    'MinBlobsLtMax'        => 'A minimum bloboknak kisebbeknek kell lenniük, mint a maximum',
    'MinBlobsUnset'        => 'Meg kell adnod a blobok számát',
    'MinFilterAreaLtMax'   => 'A minimum megszûrt területnek kisebbnek kell lennie mint a maximum',
    'MinFilterAreaUnset'   => 'Meg kell adnod a megszûrt terület képpontjainak számát',
    'MinFilterLtMinAlarm'  => 'A megszûrt területnek kisebbnek vagy ugyanakkorának kell lennie mint a riasztott terület',
    'MinFocusRange'        => 'Min. fókusz terület',
    'MinFocusSpeed'        => 'Min. fókusz sebesség',
    'MinFocusStep'         => 'Min. fókusz lépés',
    'MinGainRange'         => 'Min Gain Range',
    'MinGainSpeed'         => 'Min Gain Speed',
    'MinGainStep'          => 'Min Gain Step',
    'MinIrisRange'         => 'Min. írisz terület',
    'MinIrisSpeed'         => 'Min. írisz sebesség',
    'MinIrisStep'          => 'Min. írisz lépés',
    'MinPanRange'          => 'Min. jobb-bal tartomány',
    'MinPanSpeed'          => 'Min. jobb-bal sebesség',
    'MinPanStep'           => 'Min. jobb-bal lépés',
    'MinPixelThresLtMax'   => 'A képpont minimum eltérési küszöbének kisebbnek kell lennie, mint a maximum',
    'MinPixelThresUnset'   => 'Meg kell adnod a képpont minimum eltérési küszöbét',
    'MinTiltRange'         => 'Min. fel-le tartomány',
    'MinTiltSpeed'         => 'Min. fel-le sebesség',
    'MinTiltStep'          => 'Min. fel-le lépés',
    'MinWhiteRange'        => 'Min. fehér egyensúly terület',
    'MinWhiteSpeed'        => 'Min. fehér egyensúly sebesség',
    'MinWhiteStep'         => 'Min. fehér egyensúly lépés',
    'MinZoomRange'         => 'Min. zoom terület',
    'MinZoomSpeed'         => 'Min. zoom sebesség',
    'MinZoomStep'          => 'Min. zoom lépés',
    'Misc'                 => 'Egyéb',
    'Monitor'              => 'Monitor',
    'MonitorIds'           => 'Monitor&nbsp;azonosítók',
    'MonitorPreset'        => 'Elõre beállított monitorprofilok',
    'MonitorPresetIntro'   => 'Válassz egy, az elõre meghatározott<br> értékprofilt az alábbiak közül.<br><br>Vedd figyelembe, hogy ez felülírhatja <br>az általad már beállított értékeket.<br><br>',
    'MonitorProbe'         => 'Monitor észlelés',
    'MonitorProbeIntro'    => 'Az alábbi listában találhatók az automatikusan érzékelt analóg és hálózati kamerákat, illetve azt, hogy közülük melyik van használatban, vagy kiválasztható.<br/><br/>Válasszon egyet az alábbi listából.<br/><br/>Figyelem! Nem biztos, hogy minden kamerát lehet automatikusan érzékelni. Az itt kiválasztott kamara adatai felülírhatják azokat, amelyeket már ehhez a monitorhoz beállított.<br/><br/>',
    'Monitors'             => 'Monitorok',
    'Montage'              => 'Többkamerás nézet',
    'Month'                => 'Hónapban',
    'Move'                 => 'Mozgás',
    'MustBeGe'             => 'nagyobbnak vagy egyenlõnek kell lennie',
    'MustBeLe'             => 'kisebbnek vagy egyenlõnek kell lennie',
    'MustConfirmPassword'  => 'Meg kell erõsítened a jelszót',
    'MustSupplyPassword'   => 'Meg kell adnod a jelszót',
    'MustSupplyUsername'   => 'Meg kell adnod felhasználói nevet',
    'Name'                 => 'Név',
    'Near'                 => 'Közel',
    'Network'              => 'Hálózat',
    'New'                  => 'Uj',
    'NewGroup'             => 'Új csoport',
    'NewLabel'             => 'Új cimke',
    'NewPassword'          => 'Új jelszó',
    'NewState'             => 'Új állapot neve',
    'NewUser'              => 'Új felhasználó',
    'Next'                 => 'Következõ',
    'No'                   => 'Nem',
    'NoDetectedCameras'    => 'Nincsenek érzékelt kamerák',
    'NoFramesRecorded'     => 'Nincs felvett képkocka ehhez az eseményhez',
    'NoGroup'              => 'Nincs csoport',
    'NoSavedFilters'       => 'Nincs mentett szûrõ',
    'NoStatisticsRecorded' => 'Nincs mentett statisztika ehhez az eseményhez/képkockához',
    'None'                 => 'Nincs kiválasztva',
    'NoneAvailable'        => 'Nem elérhetõ',
    'Normal'               => 'Normál',
    'Notes'                => 'Megjegyzések',
    'NumPresets'           => 'Profilok száma',
    'Off'                  => 'Ki',
    'On'                   => 'Be',
    'OpEq'                 => 'egyenlõ',
    'OpGt'                 => 'nagyobb mint',
    'OpGtEq'               => 'nagyobb van egyenlõ',
    'OpIn'                 => 'beállítva',
    'OpLt'                 => 'kisebb mint',
    'OpLtEq'               => 'kisebb vagy egyenlõ',
    'OpMatches'            => 'találatok',
    'OpNe'                 => 'nem egyenlõ',
    'OpNotIn'              => 'nincs beállítva',
    'OpNotMatches'         => 'nincs találat',
    'Open'                 => 'Megnyitás',
    'OptionHelp'           => 'Beállítási segítség',
    'OptionRestartWarning' => 'Ez a beállítás nem jut teljesen érvényre\namíg a rendszer fut. Ha megtettél minden\nbeállítást, indítsd újra a ZoneMinder szolgáltatást.',
    'Options'              => 'Beállítások',
    'OrEnterNewName'       => 'vagy adj meg új nevet',
    'Order'                => 'Sorrend',
    'Orientation'          => 'Orientáció',
    'Out'                  => 'Kifelé',
    'OverwriteExisting'    => 'Meglévõ felülírása',
    'Paged'                => 'Lapozva',
    'Pan'                  => 'Jobb-bal mozgás',
    'PanLeft'              => 'Mozgás balra',
    'PanRight'             => 'Mozgás jobbra',
    'PanTilt'              => 'Mozgat',
    'Parameter'            => 'Paraméter',
    'Password'             => 'Jelszó',
    'PasswordsDifferent'   => 'Az új és a megerõsített jelszó különbözik!',
    'Paths'                => 'Útvonalak',
    'Pause'                => 'Szünet',
    'Phone'                => 'Telefonon betárcsázva',
    'PhoneBW'              => 'Betárcsázó<br>sávsz.',
    'PixelDiff'            => 'Képpont eltérés',
    'Pixels'               => 'képpont',
    'Play'                 => 'Lejátszás',
    'PlayAll'              => 'Mind lejátszása',
    'PleaseWait'           => 'Kérlek várj...',
    'Point'                => 'Pont',
    'PostEventImageBuffer' => 'Esemény utáni képpuffer',
    'PreEventImageBuffer'  => 'Esemény elötti képpuffer',
    'PreserveAspect'       => 'Képarány megtartása',
    'Preset'               => 'Elõre beállított profil',
    'Presets'              => 'Elõre beállított profilok',
    'Prev'                 => 'Elõzõ',
    'Probe'                => 'Érzékelés',
    'Protocol'             => 'Protocol',
    'Rate'                 => 'FPS',
    'Real'                 => 'Valós',
    'Record'               => 'Felvétel',
    'RefImageBlendPct'     => 'Változás a referenciaképtõl %-ban',
    'Refresh'              => 'Frissít',
    'Remote'               => 'Hálózati',
    'RemoteHostName'       => 'Hálózati IP cím/hosztnév',
    'RemoteHostPath'       => 'A kép elérési útvonala',
    'RemoteHostPort'       => 'Hálózati portszám',
    'RemoteHostSubPath'    => 'A kép elérési al-útvonala',
    'RemoteImageColours'   => 'A kép színe',
    'RemoteMethod'         => 'Hálózati metódus',
    'RemoteProtocol'       => 'Hálózati protokoll',
    'Rename'               => 'Átnevezés',
    'Replay'               => 'Visszajátszás',
    'ReplayAll'            => 'Minden eseményt',
    'ReplayGapless'        => 'Folyamatos eseményeket',
    'ReplaySingle'         => 'Egyéni esemény',
    'Reset'                => 'Alapértékre állít',
    'ResetEventCounts'     => 'Esemény számláló nullázása',
    'Restart'              => 'A szolgáltatás újraindítása',
    'Restarting'           => 'Újraindítás',
    'RestrictedCameraIds'  => 'Korlátozott kamerák azonosítói',
    'RestrictedMonitors'   => 'Korlátozott kamerák',
    'ReturnDelay'          => 'Visszaérkezés késleltetése',
    'ReturnLocation'       => 'Visszaérkezés helye',
    'Rewind'               => 'Visszatekerés',
    'RotateLeft'           => 'Balra forgatás',
    'RotateRight'          => 'Jobbra forgatás',
    'RunMode'              => 'Futási mód',
    'RunState'             => 'A ZoneMinder állapota',
    'Running'              => 'Éles',
    'Save'                 => 'Mentés',
    'SaveAs'               => 'Mentés mint',
    'SaveFilter'           => 'Szûrõ mentése',
    'Scale'                => 'Méret',
    'Score'                => 'Pontszám',
    'Secs'                 => 'mp.',
    'Sectionlength'        => 'Rész hossz',
    'Select'               => 'Kiválasztás',
    'SelectMonitors'       => 'Monitorok kiválasztása',
    'SelfIntersecting'     => 'A sokszög szélei nem keresztezõdhetnek',
    'Set'                  => 'Beállít',
    'SetNewBandwidth'      => 'Új sávszélesség beállítás',
    'SetPreset'            => 'Alapértelmezett beállítása',
    'Settings'             => 'Beállítások',
    'ShowFilterWindow'     => 'Szûrõablak megjelenítés',
    'ShowTimeline'         => 'Idõvonal megjelenítés',
    'SignalCheckColour'    => 'Szín a jel kimaradásakor',
    'Size'                 => 'Fájlméret',
    'SkinDescription'      => 'Change the default skin for this computer', // Added - 2011-01-30
    'Sleep'                => 'Alvás',
    'SortAsc'              => 'Növekvõ',
    'SortBy'               => 'Sorbarendezés:',
    'SortDesc'             => 'Csökkenõ',
    'Source'               => 'Forrás',
    'SourceColours'        => 'A kép színe',
    'SourcePath'           => 'A kép elérési útvonala',
    'SourceType'           => 'Kép-forrás típusa',
    'Speed'                => 'Sebesség',
    'SpeedHigh'            => 'Nagy sebsség',
    'SpeedLow'             => 'Alacsony sebesség',
    'SpeedMedium'          => 'Közepes sebesség',
    'SpeedTurbo'           => 'Turbó sebesség',
    'Start'                => 'Indít',
    'State'                => 'Állapot',
    'Stats'                => 'Statisztikák',
    'Status'               => 'Státusz',
    'Step'                 => 'Ugrás',
    'StepBack'             => 'Visszalépés',
    'StepForward'          => 'Elõrelépés',
    'StepLarge'            => 'Nagy ugrás',
    'StepMedium'           => 'Közepes ugrás',
    'StepNone'             => 'Nincs ugrás',
    'StepSmall'            => 'Kis ugrás',
    'Stills'               => 'Állóképek',
    'Stop'                 => 'A szolgáltatás leállítása',
    'Stopped'              => 'Leállítva',
    'Stream'               => 'Élõ folyam',
    'StreamReplayBuffer'   => 'Folyam visszajátszó képpuffer',
    'Submit'               => 'Elküld',
    'System'               => 'Rendszer',
    'Tele'                 => 'Táv',
    'Thumbnail'            => 'Elõnézet',
    'Tilt'                 => 'Fel-le mozgás',
    'Time'                 => 'Idõpont',
    'TimeDelta'            => 'Idõ változás',
    'TimeStamp'            => 'Idõbélyeg',
    'Timeline'             => 'Idõvonal',
    'Timestamp'            => 'Idõbélyeg',
    'TimestampLabelFormat' => 'Idõbélyeg formátum',
    'TimestampLabelX'      => 'Elhelyezés X pozició',
    'TimestampLabelY'      => 'Elhelyezés Y pozició',
    'Today'                => 'Ma',
    'Tools'                => 'Eszközök',
    'TotalBrScore'         => 'Össz.<br/>pontszám',
    'TrackDelay'           => 'Késleltetés követése',
    'TrackMotion'          => 'Mozgás követése',
    'Triggers'             => 'Elõidézõk',
    'TurboPanSpeed'        => 'Turbó jobb-bal sebesség',
    'TurboTiltSpeed'       => 'Turbo fel-le sebesség',
    'Type'                 => 'Típus',
    'Unarchive'            => 'Archívumból ki',
    'Undefined'            => 'Nincs megadva',
    'Units'                => 'Egység',
    'Unknown'              => 'Ismeretlen',
    'Update'               => 'Frissítés',
    'UpdateAvailable'      => 'Elérhetõ ZoneMinder frissítés.',
    'UpdateNotNecessary'   => 'Nem szükséges a frissítés.',
    'UseFilter'            => 'Szûrõt használ',
    'UseFilterExprsPost'   => '&nbsp;szürõ&nbsp;kifejezés használata', // This is used at the end of the phrase 'use N filter expressions'
    'UseFilterExprsPre'    => '&nbsp;', // This is used at the beginning of the phrase 'use N filter expressions'
    'User'                 => 'Felhasználó',
    'Username'             => 'Felhasználónév',
    'Users'                => 'Felhasználók',
    'Value'                => 'Érték',
    'Version'              => 'Verzió',
    'VersionIgnore'        => 'Ennek a verziónak a figyelmen kívül hagyása',
    'VersionRemindDay'     => '1 nap múlva emlékeztess',
    'VersionRemindHour'    => '1 óra múlva emlékeztess',
    'VersionRemindNever'   => 'Ne emlékeztess az új verzióról',
    'VersionRemindWeek'    => '1 hét múlva emlékeztess',
    'Video'                => 'Videó',
    'VideoFormat'          => 'Videó formátum',
    'VideoGenFailed'       => 'Hiba a videó készítésekor!',
    'VideoGenFiles'        => 'Létezõ videók',
    'VideoGenNoFiles'      => 'Nem találhatók videók',
    'VideoGenParms'        => 'Videó készítési paraméterek',
    'VideoGenSucceeded'    => 'A videó elkészült!',
    'VideoSize'            => 'Kép mérete',
    'View'                 => 'Megtekint',
    'ViewAll'              => 'Az összes listázása',
    'ViewEvent'            => 'Események nézet',
    'ViewPaged'            => 'Oldal nézet',
    'Wake'                 => 'Ébreszt',
    'WarmupFrames'         => 'Bemelegítõ képkockák',
    'Watch'                => 'Figyel',
    'Web'                  => 'Web',
    'WebColour'            => 'Szín az idõvonal ablakban',
    'Week'                 => 'Héten',
    'White'                => 'Fehér',
    'WhiteBalance'         => 'Fehér egyensúly',
    'Wide'                 => 'Széles',
    'X'                    => 'X',
    'X10'                  => 'X10',
    'X10ActivationString'  => 'X10 élesítõ karaktersor',
    'X10InputAlarmString'  => 'X10 bemeneti riadó karaktersor',
    'X10OutputAlarmString' => 'X10 kimeneti riadó karaktersor',
    'Y'                    => 'Y',
    'Yes'                  => 'Igen',
    'YouNoPerms'           => 'Nincs jogod az erõforrás eléréséhez.',
    'Zone'                 => 'Zóna:',
    'ZoneAlarmColour'      => 'Riadó színezés (R/G/B)',
    'ZoneArea'             => 'Zóna lefedettsége',
    'ZoneFilterSize'       => 'Szûrt szélesség/magasság<br>(képpont)',
    'ZoneMinMaxAlarmArea'  => 'Min/Max riadó terület',
    'ZoneMinMaxBlobArea'   => 'Min/Max Blob terület',
    'ZoneMinMaxBlobs'      => 'Min/Max Blobok',
    'ZoneMinMaxFiltArea'   => 'Min/Max szûrt terület',
    'ZoneMinMaxPixelThres' => 'Min/Max képpont eltérési<br>küszöb (0-255)',
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
    'LatestRelease'        => 'Az utolsó kiadás v%1$s, ami neked van v%2$s.',
    'MonitorCount'         => '%1$s %2$s', // For example '4 Monitors' (from Vlang below)
    'MonitorFunction'      => 'Megfigyelés funkció: %1$s',
    'RunningRecentVer'     => 'A legfrissebb ZoneMinder verziót használod, v%s.',
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
    'Event'                => array( 0=>'Esemény', 1=>'Esemény', 2=>'Esemény' ),
    'Monitor'              => array( 0=>'Monitor', 1=>'Monitor', 2=>'Monitor' ),
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
//    'LANG_DEFAULT' => array(
//        'Prompt' => "This is a new prompt for this option",
//        'Help' => "This is some new help for this option which will be displayed in the popup window when the ? is clicked"
//    ),
);

?>
