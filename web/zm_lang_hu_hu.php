<?php
//
// ZoneMinder web HU Hungarian language file, $Date$, $Revision$
// Copyright (C) 2003, 2004, 2005, 2006  Philip Coombes
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
// version: 0.4 - 2007.12.30. - frissítés 1.23.0-hoz
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

setlocale(LC_TIME, 'hu_HU');

// Simple String Replacements
$zmSlang24BitColour          = '24 bites szín';
$zmSlang8BitGrey             = '8 bit szürkeárnyalat';
$zmSlangAction               = 'Mûvelet';
$zmSlangActual               = 'Tényleges';
$zmSlangAddNewControl        = 'Új vezérlés';
$zmSlangAddNewMonitor        = 'Új monitor';
$zmSlangAddNewUser           = 'Új felhasználó';
$zmSlangAddNewZone           = 'Új zóna';
$zmSlangAlarmBrFrames        = 'Riadó<br/>képek';
$zmSlangAlarmFrameCount      = 'Riadó képek száma';
$zmSlangAlarmFrame           = 'Riadó kép';
$zmSlangAlarmLimits          = 'Riasztási határok';
$zmSlangAlarmMaximumFPS      = 'Maximális FPS riasztásnál';
$zmSlangAlarmPx              = 'Riadó képpont';
$zmSlangAlarmRGBUnset        = 'Be kell állítani egy RGB színt a riasztáshoz';
$zmSlangAlarm                = 'Riadó';
$zmSlangAlert                = 'Riasztás';
$zmSlangAll                  = 'Mind';
$zmSlangApply                = 'Alkalmaz';
$zmSlangApplyingStateChange  = 'Állapot váltás...';
$zmSlangArchArchived         = 'Csak archivált';
$zmSlangArchive              = 'Archívum';
$zmSlangArchived             = 'Archivált';
$zmSlangArchUnarchived       = 'Csak archiválatlan';
$zmSlangArea                 = 'Terület';
$zmSlangAreaUnits            = 'Terület (px/%)';
$zmSlangAttrAlarmFrames      = 'Riadó képkockák';
$zmSlangAttrArchiveStatus    = 'Archivált állapot';
$zmSlangAttrAvgScore         = 'Átlagos érték';
$zmSlangAttrCause            = 'Okozó';
$zmSlangAttrDate             = 'Dátum';
$zmSlangAttrDateTime         = 'Dátum/Idõ';
$zmSlangAttrDiskBlocks       = 'Lemez blokkok';
$zmSlangAttrDiskPercent      = 'Lemez százalék';
$zmSlangAttrDuration         = 'Idõtartam';
$zmSlangAttrFrames           = 'Képkockák';
$zmSlangAttrId               = 'Azonosító';
$zmSlangAttrMaxScore         = 'Max. érték';
$zmSlangAttrMonitorId        = 'Monitor azon.';
$zmSlangAttrMonitorName      = 'Monitor név';
$zmSlangAttrName             = 'Név';
$zmSlangAttrNotes            = 'Megjegyzés';
$zmSlangAttrSystemLoad       = 'System Load';
$zmSlangAttrTime             = 'Idõ';
$zmSlangAttrTotalScore       = 'Össz. érték';
$zmSlangAttrWeekday          = 'Hétköznap';
$zmSlangAuto                 = 'Auto';
$zmSlangAutoStopTimeout      = 'Auto megállási idõ túllépés';
$zmSlangAvgBrScore           = 'Átlag<br/>érték';
$zmSlangBackgroundFilter     = 'Szûrõ futtatása a háttérben';
$zmSlangBackground           = 'Háttér';
$zmSlangBadAlarmFrameCount   = 'Riadó képek száma 1 vagy nagyobb egész szám legyen';
$zmSlangBadAlarmMaxFPS       = 'A riadó maximális FPS száma pozitív szám legyen';
$zmSlangBadChannel           = 'A csatorna száma 0 vagy nagyobb egész szám legyen';
$zmSlangBadDevice            = 'Az eszköz érték valós legyen';
$zmSlangBadFormat            = 'A típus 0 vagy nagyobb egész szám legyen';
$zmSlangBadFPSReportInterval = 'FPS információs idõköz puffer számlálója 100 vagy nagyobb egész legyen';
$zmSlangBadFrameSkip         = 'Képkocka eldobások száma 0 vagy nagyobb egész szám legyen';
$zmSlangBadHeight            = 'A képmagasság érvényes érték legyen képpontban';
$zmSlangBadHost              = 'A hoszt valós IP cím vagy hosztnév legyen, http:// nélkül';
$zmSlangBadImageBufferCount  = 'Kép puffer mérete legyen 10 vagy nagyobb szám';
$zmSlangBadLabelX            = 'A cimke X koordinátája legyen 0 vagy nagyobb egész szám';
$zmSlangBadLabelY            = 'A cimke Y koordinátája legyen 0 vagy nagyobb egész szám';
$zmSlangBadMaxFPS            = 'A maximális FPS nullánál nagyobb szám legyen';
$zmSlangBadNameChars         = 'A név csak alfanumerikus karaktereket, plusz-, kötõ-, és aláhúzásjelet tartalmazhat';
$zmSlangBadPath              = 'A kép elérési útvonala valós legyen';
$zmSlangBadPort              = 'A portszám valós legyen';
$zmSlangBadPostEventCount    = 'Az esemény utáni képek puffere 0 vagy nagyobb egész szám legyen';
$zmSlangBadPreEventCount     = 'Az esemény elõtti képek puffere 0 vagy nagyobb egész szám legyen';
$zmSlangBadRefBlendPerc      = 'A referencia képkeverék-százalék pozitív egész szám legyen';
$zmSlangBadSectionLength     = 'Egy egység hossza 30 vagy hosszabb legyen';
$zmSlangBadSignalCheckColour = 'A jel ellenõrzési szín egy érvényes RGP kód kell legyen';
$zmSlangBadStreamReplayBuffer= 'Folyam visszajátszó puffer 0 vagy nagyobb egész szám legyen';
$zmSlangBadWarmupCount       = 'Bemelegítõ képek száma 0 vagy nagyobb egész szám legyen';
$zmSlangBadWebColour         = 'A web szín érvényes web szín kód legyen';
$zmSlangBadWidth             = 'A képszélesség érvényes érték legyen képpontban';
$zmSlangBandwidth            = 'Sávszélességre';
$zmSlangBlobPx               = 'Blob képpont';
$zmSlangBlobs                = 'Blob-ok';
$zmSlangBlobSizes            = 'Blob mérete';
$zmSlangBrightness           = 'Fényerõ';
$zmSlangBuffers              = 'Pufferek';
$zmSlangCanAutoFocus         = 'Auto fókusz van';
$zmSlangCanAutoGain          = 'Auto gain van';
$zmSlangCanAutoIris          = 'Auto írisz van';
$zmSlangCanAutoWhite         = 'Van autómata fehér egyensúly';
$zmSlangCanAutoZoom          = 'Auto zoom van';
$zmSlangCancelForcedAlarm    = 'Kézi riasztás leállítása';
$zmSlangCancel               = 'Mégsem';
$zmSlangCanFocusAbs          = 'Tud abszolút fókuszt';
$zmSlangCanFocusCon          = 'Tud folyamatos fókuszt';
$zmSlangCanFocusRel          = 'Tud relatív fókuszt';
$zmSlangCanFocus             = 'Tud fókuszálni';
$zmSlangCanGainAbs           = 'Tud abszolút erõsítést';
$zmSlangCanGainCon           = 'Tud folyamatos erõsítést';
$zmSlangCanGainRel           = 'Tud relatív erõsítést';
$zmSlangCanGain              = 'Tud erõsíteni';
$zmSlangCanIrisAbs           = 'Tud abszolut íriszt';
$zmSlangCanIrisCon           = 'Folyamatosan tud íriszt változtatni';
$zmSlangCanIrisRel           = 'Relatíven tud íriszt változtatni';
$zmSlangCanIris              = 'Tud íriszt változtatni';
$zmSlangCanMoveAbs           = 'Tud abszolult mozgást';
$zmSlangCanMoveCon           = 'Folyamatosan tud mozogni';
$zmSlangCanMoveDiag          = 'Diagonálban tud mozogni';
$zmSlangCanMoveMap           = 'Útvonalon tud mozogni';
$zmSlangCanMoveRel           = 'Relatíven tud mozogni';
$zmSlangCanMove              = 'Tud mozogni';
$zmSlangCanPan               = 'Tud jobb-bal mozgást' ;
$zmSlangCanReset             = 'Tud alaphelyzetbe jönni';
$zmSlangCanSetPresets        = 'Tud menteni profilokat';
$zmSlangCanSleep             = 'Tud phihenõ üzemmódot';
$zmSlangCanTilt              = 'Tud fel-le mozgást';
$zmSlangCanWake              = 'Tud feléledni';
$zmSlangCanWhiteAbs          = 'Tud abszolut fehér egyensúlyt';
$zmSlangCanWhiteBal          = 'Tud fehér egyensúlyt';
$zmSlangCanWhiteCon          = 'Tud folyamatos fehér egyensúlyt';
$zmSlangCanWhiteRel          = 'Tud relatív fehér egyensúlyt';
$zmSlangCanWhite             = 'Tud fehér egyensúlyt';
$zmSlangCanZoomAbs           = 'Tud abszolut zoom-ot';
$zmSlangCanZoomCon           = 'Tud folyamatos zoom-ot';
$zmSlangCanZoomRel           = 'Tud relatív zoom-ot';
$zmSlangCanZoom              = 'Tud zoom-olni';
$zmSlangCaptureHeight        = 'Képmagasság';
$zmSlangCapturePalette       = 'Felvétel szín-paletta';
$zmSlangCaptureWidth         = 'Képszélesség';
$zmSlangCause                = 'Okozó';
$zmSlangCheckMethod          = 'A riasztás figyelésének módja';
$zmSlangChooseFilter         = 'Válassz szûrõt';
$zmSlangChoosePreset         = 'Válassz profilt';
$zmSlangClose                = 'Bezár';
$zmSlangColour               = 'Szín';
$zmSlangCommand              = 'Parancs';
$zmSlangConfig               = 'Beállítás';
$zmSlangConfiguredFor        = 'Beállítva';
$zmSlangConfirmDeleteEvents  = 'Biztos benne, hogy törli a kiválasztott eseményeket?';
$zmSlangConfirmPassword      = 'Jelszó megerõsítés';
$zmSlangConjAnd              = 'és';
$zmSlangConjOr               = 'vagy';
$zmSlangConsole              = 'Konzol';
$zmSlangContactAdmin         = 'Kérem vegye fel a kapcsolatot a rendszergazdával a részletekért.';
$zmSlangContinue             = 'Folytatás';
$zmSlangContrast             = 'Kontraszt';
$zmSlangControlAddress       = 'Vezérlési jogok';
$zmSlangControlCaps          = 'Vezérlési lehetõségek';
$zmSlangControlCap           = 'Vezérlési lehetõség';
$zmSlangControlDevice        = 'Vezérlõ eszköz';
$zmSlangControllable         = 'Vezérelhetõ';
$zmSlangControlType          = 'Vezérlés típusa';
$zmSlangControl              = 'Vezérlés';
$zmSlangCycle                = 'Körkapcsolás';
$zmSlangCycleWatch           = 'Körkapcsolás';
$zmSlangDay                  = 'Napon';
$zmSlangDebug                = 'Nyomon<br>követés';
$zmSlangDefaultRate          = 'Alapértelmezett FPS';
$zmSlangDefaultScale         = 'Alapértelmezett arány';
$zmSlangDefaultView          = 'Alapértelmezett nézet';
$zmSlangDeleteAndNext        = 'Töröl &amp;<br>következõ';
$zmSlangDeleteAndPrev        = 'Töröl &amp;<br>elõzõ';
$zmSlangDeleteSavedFilter    = 'Mentett szûrõ törlése';
$zmSlangDelete               = 'Töröl';
$zmSlangDescription          = 'Leírás';
$zmSlangDeviceChannel        = 'Eszköz csatornája';
$zmSlangDeviceFormat         = 'Eszköz formátuma';
$zmSlangDeviceNumber         = 'Eszköz szám';
$zmSlangDevicePath           = 'Eszköz elérési útvonala';
$zmSlangDevices              = 'Eszközök';
$zmSlangDimensions           = 'Dimenziók';
$zmSlangDisableAlarms        = 'Riasztás tiltása';
$zmSlangDisk                 = 'Tárhely';
$zmSlangDonateAlready        = 'Nem, én már támogattam';
$zmSlangDonateEnticement     = 'Ön már jó ideje használja a ZoneMindert remélhetõleg hasznos kiegészítésnek tartja háza vagy munkahelye biztosításában. Bár ZoneMinder szabad, nyílt forráskódú, és az is marad; a fejlesztése pénzbe kerül. Ha támogatni szeretné a jövõbeni fejlesztéseket és az új funkciókat kérem támogasson. A támogatás teljesen önkéntes, de nagyon megbecsült és annyival tud támogatni amennyivel kíván.<br><br>Ha támogatni szertne kérem válasszon az alábbi lehetõségekbõl vagy látogassa meg a http://www.zoneminder.com/donate.html oldalt.<br><br>Köszönöm, hogy használja a ZoneMinder-t és ne felejtse el meglátogatni a fórumokat a ZoneMinder.com oldalon támogatásért és ötletekért, hogy tudja még jobban használni a ZoneMinder-t.';
$zmSlangDonate               = 'Kérem támogasson';
$zmSlangDonateRemindDay      = 'Nem most, figyelmeztess 1 nap múlva';
$zmSlangDonateRemindHour     = 'Nem most, figyelmeztess 1 óra múlva';
$zmSlangDonateRemindMonth    = 'Nem most, figyelmeztess 1 hónap múlva';
$zmSlangDonateRemindNever    = 'Nem akarom támogatni, ne is emlékeztess';
$zmSlangDonateRemindWeek     = 'Nem most, figyelmeztess 1 hét múlva';
$zmSlangDonateYes            = 'Igen, most szeretném támogatni';
$zmSlangDownload             = 'Letölt';
$zmSlangDuration             = 'Idõtartam';
$zmSlangEdit                 = 'Szerkeszt';
$zmSlangEmail                = 'Email';
$zmSlangEnableAlarms         = 'Riasztás feloldása';
$zmSlangEnabled              = 'Engedélyezve';
$zmSlangEnterNewFilterName   = 'Írd be az új szûrõ nevét';
$zmSlangErrorBrackets        = 'Hiba, ellenõrizd, hogy ugyanannyi nyitó és záró zárójel van';
$zmSlangError                = 'Hiba';
$zmSlangErrorValidValue      = 'Hiba, ellenõrizd, hogy minden beállításnak érvényes értéke van';
$zmSlangEtc                  = 'stb';
$zmSlangEvent                = 'Esemény';
$zmSlangEventFilter          = 'Esemény szûrõ';
$zmSlangEventId              = 'Esemény azonosító';
$zmSlangEventName            = 'Esemény név';
$zmSlangEventPrefix          = 'Esemény elõtag';
$zmSlangEvents               = 'Események';
$zmSlangExclude              = 'Kizár';
$zmSlangExecute              = 'Futtat';
$zmSlangExportDetails        = 'Esemény adatainak exportálása';
$zmSlangExport               = 'Exportál';
$zmSlangExportFailed         = 'Hibás exportálás';
$zmSlangExportFormat         = 'Exportált fájl formátuma';
$zmSlangExportFormatTar      = 'TAR';
$zmSlangExportFormatZip      = 'ZIP';
$zmSlangExportFrames         = 'Képek adatainak exportálása';
$zmSlangExportImageFiles     = 'Képek exportálása';
$zmSlangExporting            = 'Exportálás...';
$zmSlangExportMiscFiles      = 'Egyéb fájlok exportálása (ha vannak)';
$zmSlangExportOptions        = 'Exportálás beállításai';
$zmSlangExportVideoFiles     = 'Videó fájlok exportálása (ha vannak)';
$zmSlangFar                  = 'Távol';
$zmSlangFastForward          = 'Elõre tekerés';
$zmSlangFeed                 = 'Folyam';
$zmSlangFileColours          = 'Fájl színei';
$zmSlangFile                 = 'Fájl';
$zmSlangFilePath             = 'Fájl elérési útvonala';
$zmSlangFilterArchiveEvents  = 'Minden találat archiválása';
$zmSlangFilterDeleteEvents   = 'Minden találat törlése';
$zmSlangFilterEmailEvents    = 'Minden találat adatainak elküldése E-mailben';
$zmSlangFilterExecuteEvents  = 'Parancs futtatása minden találaton';
$zmSlangFilterMessageEvents  = 'Minden találat adatainak üzenése';
$zmSlangFilterPx             = 'Szûrt képkockák';
$zmSlangFilters              = 'Szûrõk';
$zmSlangFilterUnset          = 'Meg kell adnod a szûrõ szélességét és magasságát';
$zmSlangFilterUploadEvents   = 'Minden találat feltöltése';
$zmSlangFilterVideoEvents    = 'Videó készítése minden találatról';
$zmSlangFirst                = 'Elsõ';
$zmSlangFlippedHori          = 'Vízszintes tükrözés';
$zmSlangFlippedVert          = 'Függõleges tükrözés';
$zmSlangFocus                = 'Fókusz';
$zmSlangForceAlarm           = 'Kézi riasztás';
$zmSlangFormat               = 'Formátum';
$zmSlangFPS                  = 'fps';
$zmSlangFPSReportInterval    = 'FPS jelentés idõköze';
$zmSlangFrameId              = 'Képkocka azonosító';
$zmSlangFrame                = 'Képkocka';
$zmSlangFrameRate            = 'FPS';
$zmSlangFrameSkip            = 'Képk. kihagyás';
$zmSlangFrames               = 'Képkocka';
$zmSlangFTP                  = 'FTP';
$zmSlangFunc                 = 'Funk.';
$zmSlangFunction             = 'Funkció';
$zmSlangGain                 = 'Erõsítés';
$zmSlangGeneral              = 'Általános';
$zmSlangGenerateVideo        = 'Videó készítés';
$zmSlangGeneratingVideo      = 'Videó készítése...';
$zmSlangGoToZoneMinder       = 'Látogatás a ZoneMinder.com-ra';
$zmSlangGrey                 = 'Szürke';
$zmSlangGroup                = 'Csoport';
$zmSlangGroups               = 'Csoportok';
$zmSlangHasFocusSpeed        = 'Van fókusz sebesség';
$zmSlangHasGainSpeed         = 'Van erõsítés sebesség';
$zmSlangHasHomePreset        = 'Van kedvenc profilja';
$zmSlangHasIrisSpeed         = 'Van írisz sebesség';
$zmSlangHasPanSpeed          = 'Van jobb-bal sebesség';
$zmSlangHasPresets           = 'Vannak profiljai';
$zmSlangHasTiltSpeed         = 'Van le-fel sebesség';
$zmSlangHasTurboPan          = 'Van turbó jobb-bal';
$zmSlangHasTurboTilt         = 'Van turbó le-fel';
$zmSlangHasWhiteSpeed        = 'Van fehér egyensúly sebesség';
$zmSlangHasZoomSpeed         = 'Van zoom sebesség';
$zmSlangHighBW               = 'Magas<br>sávsz.';
$zmSlangHigh                 = 'Magas';
$zmSlangHome                 = 'Home';
$zmSlangHour                 = 'Órában';
$zmSlangHue                  = 'Színárnyalat';
$zmSlangId                   = 'Az.';
$zmSlangIdle                 = 'Nyugalom';
$zmSlangIgnore               = 'Figyelmen kívül hagy';
$zmSlangImageBufferSize      = 'Képpuffer mérete (képkockák)';
$zmSlangImage                = 'Kép';
$zmSlangImages               = 'Kép';
$zmSlangInclude              = 'Beágyaz';
$zmSlangIn                   = 'In';
$zmSlangInverted             = 'Invertálva';
$zmSlangIris                 = 'Írisz';
$zmSlangKeyString            = 'Kulcs karaktersor';
$zmSlangLabel                = 'Cimke';
$zmSlangLanguage             = 'Nyelv';
$zmSlangLast                 = 'Utolsó';
$zmSlangLimitResultsPost     = 'találatig korlátoz'; // This is used at the end of the phrase 'Limit to first N results only'
$zmSlangLimitResultsPre      = 'Az elsõ'; // This is used at the beginning of the phrase 'Limit to first N results only'
$zmSlangLinkedMonitors       = 'Összefüggõ monitorok';
$zmSlangList                 = 'Lista';
$zmSlangLoad                 = 'Terhelés';
$zmSlangLocal                = 'Helyi';
$zmSlangLoggedInAs           = 'Bejelentkezve mint';
$zmSlangLoggingIn            = 'Bejelentkezés folyamatban';
$zmSlangLogin                = 'Bejelentkezés';
$zmSlangLogout               = 'Kilépés';
$zmSlangLow                  = 'Alacsony';
$zmSlangLowBW                = 'Alacsony<br>sávsz.';
$zmSlangMain                 = 'Fõ';
$zmSlangMan                  = 'Man';
$zmSlangManual               = 'Kézikönyv';
$zmSlangMark                 = 'Jelölés';
$zmSlangMaxBandwidth         = 'Max. sávszélesség';
$zmSlangMaxBrScore           = 'Max.<br/>érték';
$zmSlangMaxFocusRange        = 'Max. fókusz tartomány';
$zmSlangMaxFocusSpeed        = 'Max. fókusz sebesség';
$zmSlangMaxFocusStep         = 'Max. fókusz lépés';
$zmSlangMaxGainRange         = 'Max Gain Range';
$zmSlangMaxGainSpeed         = 'Max Gain Speed';
$zmSlangMaxGainStep          = 'Max Gain Step';
$zmSlangMaximumFPS           = 'Maximum FPS';
$zmSlangMaxIrisRange         = 'Max. írisz tartomány';
$zmSlangMaxIrisSpeed         = 'Max. írisz sebesség';
$zmSlangMaxIrisStep          = 'Max. írisz lépés';
$zmSlangMax                  = 'Max.';
$zmSlangMaxPanRange          = 'Max. jobb-bal tartomány';
$zmSlangMaxPanSpeed          = 'Max. jobb-bal sebesség';
$zmSlangMaxPanStep           = 'Max. jobb-bal lépés';
$zmSlangMaxTiltRange         = 'Max. fel-le tartomány';
$zmSlangMaxTiltSpeed         = 'Max. fel-le sebesség';
$zmSlangMaxTiltStep          = 'Max. fel-le lépés';
$zmSlangMaxWhiteRange        = 'Max. fehér egyensúly tartomány';
$zmSlangMaxWhiteSpeed        = 'Max. fehér egyensúly sebesség';
$zmSlangMaxWhiteStep         = 'Max. fehér egyensúly lépés';
$zmSlangMaxZoomRange         = 'Max. zoom tartomány';
$zmSlangMaxZoomSpeed         = 'Max. zoom sebesség';
$zmSlangMaxZoomStep          = 'Max. zoom lépés';
$zmSlangMediumBW             = 'Közepes<br>sávsz.';
$zmSlangMedium               = 'Közepes';
$zmSlangMinAlarmAreaLtMax    = 'A minimum riasztott területnek kisebbnek kell lennie mint a maximumnak';
$zmSlangMinAlarmAreaUnset    = 'Meg kell adnod a minimum riasztott képpontok számát';
$zmSlangMinBlobAreaLtMax     = 'A minimum blob területnek kisebbnek kell lennie mint a maximumnak';
$zmSlangMinBlobAreaUnset     = 'Meg kell adnod a minimum blob képpontok számát';
$zmSlangMinBlobLtMinFilter   = 'A minimum blob területnek kisebbnek vagy egyenlõnek kell lennie a megszûrt területtel';
$zmSlangMinBlobsLtMax        = 'A minimum bloboknak kisebbeknek kell lenniük, mint a maximum';
$zmSlangMinBlobsUnset        = 'Meg kell adnod a blobok számát';
$zmSlangMinFilterAreaLtMax   = 'A minimum megszûrt területnek kisebbnek kell lennie mint a maximum';
$zmSlangMinFilterAreaUnset   = 'Meg kell adnod a megszûrt terület képpontjainak számát';
$zmSlangMinFilterLtMinAlarm  = 'A megszûrt területnek kisebbnek vagy ugyanakkorának kell lennie mint a riasztott terület';
$zmSlangMinFocusRange        = 'Min. fókusz terület';
$zmSlangMinFocusSpeed        = 'Min. fókusz sebesség';
$zmSlangMinFocusStep         = 'Min. fókusz lépés';
$zmSlangMinGainRange         = 'Min Gain Range';
$zmSlangMinGainSpeed         = 'Min Gain Speed';
$zmSlangMinGainStep          = 'Min Gain Step';
$zmSlangMinIrisRange         = 'Min. írisz terület';
$zmSlangMinIrisSpeed         = 'Min. írisz sebesség';
$zmSlangMinIrisStep          = 'Min. írisz lépés';
$zmSlangMinPanRange          = 'Min. jobb-bal tartomány';
$zmSlangMinPanSpeed          = 'Min. jobb-bal sebesség';
$zmSlangMinPanStep           = 'Min. jobb-bal lépés';
$zmSlangMinPixelThresLtMax   = 'A minimum küszöb képpontnak kisebbnek kell lennie, mint a maximum';
$zmSlangMinPixelThresUnset   = 'Meg kell adnod a minimum képpont küszöböt';
$zmSlangMinTiltRange         = 'Min. fel-le tartomány';
$zmSlangMinTiltSpeed         = 'Min. fel-le sebesség';
$zmSlangMinTiltStep          = 'Min. fel-le lépés';
$zmSlangMinWhiteRange        = 'Min. fehér egyensúly terület';
$zmSlangMinWhiteSpeed        = 'Min. fehér egyensúly sebesség';
$zmSlangMinWhiteStep         = 'Min. fehér egyensúly lépés';
$zmSlangMinZoomRange         = 'Min. zoom terület';
$zmSlangMinZoomSpeed         = 'Min. zoom sebesség';
$zmSlangMinZoomStep          = 'Min. zoom lépés';
$zmSlangMisc                 = 'Egyéb';
$zmSlangMonitorIds           = 'Monitor&nbsp;Azonosítók';
$zmSlangMonitor              = 'Monitor';
$zmSlangMonitorPreset        = 'Elõre beállított értékprofilok megfigyeléshez';
$zmSlangMonitorPresetIntro   = 'Válassz egy, az elõre meghatározott<br> értékprofilt az alábbiak közül.<br><br>Vedd figyelembe, hogy ez felülírhatja <br>az általad már beállított értékeket.<br><br>';
$zmSlangMonitors             = 'Megfigyelések';
$zmSlangMontage              = 'Többkamerás nézet';
$zmSlangMonth                = 'Hónapban';
$zmSlangMove                 = 'Mozgás';
$zmSlangMustBeGe             = 'nagyobbnak vagy egyenlõnek kell lennie';
$zmSlangMustBeLe             = 'kisebbnek vagy egyenlõnek kell lennie';
$zmSlangMustConfirmPassword  = 'Meg kell erõsítened a jelszót';
$zmSlangMustSupplyPassword   = 'Meg kell adnod a jelszót';
$zmSlangMustSupplyUsername   = 'Meg kell adnod felhasználói nevet';
$zmSlangName                 = 'Név';
$zmSlangNear                 = 'Közel';
$zmSlangNetwork              = 'Hálózat';
$zmSlangNewGroup             = 'Új csoport';
$zmSlangNewLabel             = 'Új cimke';
$zmSlangNewPassword          = 'Új jelszó';
$zmSlangNewState             = 'Új állapot';
$zmSlangNew                  = 'Uj';
$zmSlangNewUser              = 'Új felhasználó';
$zmSlangNext                 = 'Következõ';
$zmSlangNoFramesRecorded     = 'Nincs felvett képkocka ehhez az eseményhez';
$zmSlangNoGroup              = 'Nincs csoport';
$zmSlangNoneAvailable        = 'Nincs elérhetõ';
$zmSlangNo                   = 'Nem';
$zmSlangNone                 = 'Nincs kiválasztva';
$zmSlangNormal               = 'Normális';
$zmSlangNoSavedFilters       = 'Nincs mentett szûrõ';
$zmSlangNoStatisticsRecorded = 'Nincs mentett statisztika ehhez az eseményhez/képkockához';
$zmSlangNotes                = 'Megjegyzések';
$zmSlangNumPresets           = 'Profilok száma';
$zmSlangOff                  = 'Ki';
$zmSlangOn                   = 'Be';
$zmSlangOpen                 = 'Megnyitás';
$zmSlangOpEq                 = 'egyenlõ';
$zmSlangOpGtEq               = 'nagyobb van egyenlõ';
$zmSlangOpGt                 = 'nagyobb mint';
$zmSlangOpIn                 = 'beállítva';
$zmSlangOpLtEq               = 'kisebb vagy egyenlõ';
$zmSlangOpLt                 = 'kisebb mint';
$zmSlangOpMatches            = 'találatok';
$zmSlangOpNe                 = 'nem egyenlõ';
$zmSlangOpNotIn              = 'nincs beállítva';
$zmSlangOpNotMatches         = 'nincs találat';
$zmSlangOptionHelp           = 'Beállítási segítség';
$zmSlangOptionRestartWarning = 'Ez a beállítás nem jut teljesen érvényre\namíg a rendszer fut. Ha megtettél minden\nbeállítást, indítsd újra a ZoneMinder szolgáltatást.';
$zmSlangOptions              = 'Beállítások';
$zmSlangOrder                = 'Sorrend';
$zmSlangOrEnterNewName       = 'vagy adj meg új nevet';
$zmSlangOrientation          = 'Orientáció';
$zmSlangOut                  = 'Kifelé';
$zmSlangOverwriteExisting    = 'Meglévõ felülírása';
$zmSlangPaged                = 'Lapozva';
$zmSlangPan                  = 'Jobb-bal mozgás';
$zmSlangPanLeft              = 'Mozgás balra';
$zmSlangPanRight             = 'Mozgás jobbra';
$zmSlangPanTilt              = 'Mozgat';
$zmSlangParameter            = 'Paraméter';
$zmSlangPassword             = 'Jelszó';
$zmSlangPasswordsDifferent   = 'Az új és a megerõsített jelszó különbözik!';
$zmSlangPaths                = 'Útvonalak';
$zmSlangPause                = 'Szünet';
$zmSlangPhoneBW              = 'Betárcsázó<br>sávsz.';
$zmSlangPhone                = 'Telefonon betárcsázva';
$zmSlangPixelDiff            = 'Képpont eltérés';
$zmSlangPixels               = 'képpont';
$zmSlangPlayAll              = 'Mind lejátszása';
$zmSlangPlay                 = 'Lejátszás';
$zmSlangPleaseWait           = 'Kérlek várj...';
$zmSlangPoint                = 'Pont';
$zmSlangPostEventImageBuffer = 'Esemény utáni képpuffer';
$zmSlangPreEventImageBuffer  = 'Esemény elötti képpuffer';
$zmSlangPreserveAspect	     = 'Képarány megtartása';
$zmSlangPreset               = 'Elõre beállított profil';
$zmSlangPresets              = 'Elõre beállított profilok';
$zmSlangPrev                 = 'Elõzõ';
$zmSlangProtocol             = 'Protocol';
$zmSlangRate                 = 'FPS';
$zmSlangReal                 = 'Valós';
$zmSlangRecord               = 'Felvétel';
$zmSlangRefImageBlendPct     = 'Változás a referenciaképtõl %-ban';
$zmSlangRefresh              = 'Frissít';
$zmSlangRemote               = 'Hálózati';
$zmSlangRemoteHostName       = 'Hálózati IP cím/hosztnév';
$zmSlangRemoteHostPath       = 'A kép elérési útja';
$zmSlangRemoteHostPort       = 'Hálózati gép portszáma';
$zmSlangRemoteImageColours   = 'A kép színe';
$zmSlangRename               = 'Átnevez';
$zmSlangReplayAll            = 'Minden eseményt';
$zmSlangReplay               = 'Az elejétõl';
$zmSlangReplayGapless        = 'Folyamatos eseményeket';
$zmSlangReplaySingle         = 'Egyéni esemény';
$zmSlangReplay               = 'Visszajátszás';
$zmSlangReset                = 'Alapértékre állít';
$zmSlangResetEventCounts     = 'Esemény számláló nullázása';
$zmSlangRestarting           = 'Újraindítás';
$zmSlangRestart              = 'Újraindít';
$zmSlangRestrictedCameraIds  = 'Korlátozott kamerák azonosítói';
$zmSlangRestrictedMonitors   = 'Korlátozott kamerák';
$zmSlangReturnDelay          = 'Visszaérkezés késleltetése';
$zmSlangReturnLocation       = 'Visszaérkezés helye';
$zmSlangRewind               = 'Visszatekerés';
$zmSlangRotateLeft           = 'Balra forgatás';
$zmSlangRotateRight          = 'Jobbra forgatás';
$zmSlangRunMode              = 'Futási mód';
$zmSlangRunning              = 'Éles';
$zmSlangRunState             = 'Futási állapot';
$zmSlangSaveAs               = 'Mentés mint';
$zmSlangSaveFilter           = 'Szûrõ mentése';
$zmSlangSave                 = 'Mentés';
$zmSlangScale                = 'Méret';
$zmSlangScore                = 'Pontszám';
$zmSlangSecs                 = 'mp.';
$zmSlangSectionlength        = 'Rész hossz';
$zmSlangSelect               = 'Kiválasztás';
$zmSlangSelectMonitors       = 'Monitorok kiválasztása';
$zmSlangSelfIntersecting     = 'A sokszög szélei nem keresztezõdhetnek';
$zmSlangSet                  = 'Beállít';
$zmSlangSetLearnPrefs        = 'Set Learn Prefs'; // This can be ignored for now
$zmSlangSetNewBandwidth      = 'Új sávszélesség beállítás';
$zmSlangSetPreset            = 'Alapértelmezett beállítása';
$zmSlangSettings             = 'Beállítások';
$zmSlangShowFilterWindow     = 'Szûrõablak megjelenítés';
$zmSlangShowTimeline         = 'Idõvonal megjelenítés';
$zmSlangSignalCheckColour    = 'Szín a jel kimaradásakor';
$zmSlangSize                 = 'Fájlméret';
$zmSlangSleep                = 'Alvás';
$zmSlangSortAsc              = 'Növekvõ';
$zmSlangSortBy               = 'Sorbarendezés:';
$zmSlangSortDesc             = 'Csökkenõ';
$zmSlangSource               = 'Forrás';
$zmSlangSourceType           = 'Forrás típusa';
$zmSlangSpeedHigh            = 'Nagy sebsség';
$zmSlangSpeedLow             = 'Alacsony sebesség';
$zmSlangSpeedMedium          = 'Közepes sebesség';
$zmSlangSpeed                = 'Sebesség';
$zmSlangSpeedTurbo           = 'Turbó sebesség';
$zmSlangStart                = 'Indít';
$zmSlangState                = 'Állapot';
$zmSlangStats                = 'Statisztikák';
$zmSlangStatus               = 'Státusz';
$zmSlangStepBack             = 'Visszalépés';
$zmSlangStepForward          = 'Elõrelépés';
$zmSlangStepLarge            = 'Nagy ugrás';
$zmSlangStepMedium           = 'Közepes ugrás';
$zmSlangStepNone             = 'Nincs ugrás';
$zmSlangStepSmall            = 'Kis ugrás';
$zmSlangStep                 = 'Ugrás';
$zmSlangStills               = 'Állóképek';
$zmSlangStop                 = 'Megállítás';
$zmSlangStopped              = 'Megállítva';
$zmSlangStream               = 'Élõ folyam';
$zmSlangStreamReplayBuffer   = 'Folyam visszajátszó képpuffer';
$zmSlangSubmit               = 'Elküld';
$zmSlangSystem               = 'Rendszer';
$zmSlangTele                 = 'Táv';
$zmSlangThumbnail            = 'Elõnézet';
$zmSlangTilt                 = 'Fel-le mozgás';
$zmSlangTimeDelta            = 'Idõ változás';
$zmSlangTime                 = 'Idõpont';
$zmSlangTimeline             = 'Idõvonal';
$zmSlangTimestamp            = 'Idõbélyeg';
$zmSlangTimeStamp            = 'Idõbélyeg';
$zmSlangTimestampLabelFormat = 'Idõbélyeg formátum';
$zmSlangTimestampLabelX      = 'Elhelyezés X pozició';
$zmSlangTimestampLabelY      = 'Elhelyezés Y pozició';
$zmSlangToday                = 'Ma';
$zmSlangTools                = 'Eszközök';
$zmSlangTotalBrScore         = 'Össz.<br/>pontszám';
$zmSlangTrackDelay           = 'Késleltetés követése';
$zmSlangTrackMotion          = 'Mozgás követése';
$zmSlangTriggers             = 'Elõidézõk';
$zmSlangTurboPanSpeed        = 'Turbó jobb-bal sebesség';
$zmSlangTurboTiltSpeed       = 'Turbo fel-le sebesség';
$zmSlangType                 = 'Típus';
$zmSlangUnarchive            = 'Archívumból ki';
$zmSlangUnits                = 'Egységek';
$zmSlangUnknown              = 'Ismeretlen';
$zmSlangUpdateAvailable      = 'Elérhetõ ZoneMinder frissítés.';
$zmSlangUpdate               = 'Frissítés';
$zmSlangUpdateNotNecessary   = 'Nem szükséges a frissítés.';
$zmSlangUseFilterExprsPost   = '&nbsp;szürõ&nbsp;kifejezés használata'; // This is used at the end of the phrase 'use N filter expressions'
$zmSlangUseFilterExprsPre    = '&nbsp;'; // This is used at the beginning of the phrase 'use N filter expressions'
$zmSlangUseFilter            = 'Szûrõt használ';
$zmSlangUser                 = 'Felhasználó';
$zmSlangUsername             = 'Felhasználónév';
$zmSlangUsers                = 'Felhasználók';
$zmSlangValue                = 'Érték';
$zmSlangVersionIgnore        = 'Ennek a verziónak a figyelmen kívül hagyása';
$zmSlangVersionRemindDay     = '1 nap múlva emlékeztess';
$zmSlangVersionRemindHour    = '1 óra múlva emlékeztess';
$zmSlangVersionRemindNever   = 'Ne emlékeztess az új verzióról';
$zmSlangVersionRemindWeek    = '1 hét múlva emlékeztess';
$zmSlangVersion              = 'Verzió';
$zmSlangVideoFormat          = 'Videó formátum';
$zmSlangVideoGenFailed       = 'Hiba a videó készítésekor!';
$zmSlangVideoGenFiles        = 'Létezõ videók';
$zmSlangVideoGenNoFiles      = 'Nem találhatók videók';
$zmSlangVideoGenParms        = 'Videó készítési paraméterek';
$zmSlangVideoGenSucceeded    = 'A videó elkészült!';
$zmSlangVideoSize            = 'Kép mérete';
$zmSlangVideo                = 'Videó';
$zmSlangViewAll              = 'Az összes listázása';
$zmSlangViewEvent            = 'Események nézet';
$zmSlangView                 = 'Megtekint';
$zmSlangViewPaged            = 'Oldal nézet';
$zmSlangWake                 = 'Ébreszt';
$zmSlangWarmupFrames         = 'Bemelegítõ képkockák';
$zmSlangWatch                = 'Figyel';
$zmSlangWebColour            = 'Szín az idõvonal ablakban';
$zmSlangWeb                  = 'Web';
$zmSlangWeek                 = 'Héten';
$zmSlangWhiteBalance         = 'Fehér egyensúly';
$zmSlangWhite                = 'Fehér';
$zmSlangWide                 = 'Széles';
$zmSlangX10ActivationString  = 'X10 élesítõ karaktersor';
$zmSlangX10InputAlarmString  = 'X10 bemeneti riadó karaktersor';
$zmSlangX10OutputAlarmString = 'X10 kimeneti riadó karaktersor';
$zmSlangX10                  = 'X10';
$zmSlangX                    = 'X';
$zmSlangYes                  = 'Igen';
$zmSlangYouNoPerms           = 'Nincs jogod az erõforrás eléréséhez.';
$zmSlangY                    = 'Y';
$zmSlangZoneAlarmColour      = 'Riadó szín (R/G/B)';
$zmSlangZoneArea             = 'Zóna terület';
$zmSlangZoneFilterSize       = 'Szûrt szélesség/magasság (képpontok)';
$zmSlangZoneMinMaxAlarmArea  = 'Min/Max riadó terület';
$zmSlangZoneMinMaxBlobArea   = 'Min/Max Blob terület';
$zmSlangZoneMinMaxBlobs      = 'Min/Max Blobok';
$zmSlangZoneMinMaxFiltArea   = 'Min/Max szûrt terület';
$zmSlangZoneMinMaxPixelThres = 'Min/Max képpont küszöb (0-255)';
$zmSlangZoneOverloadFrames   = 'Overload Frame Ignore Count';
$zmSlangZones                = 'Zónák';
$zmSlangZone                 = 'Zóna:';
$zmSlangZoomIn               = 'Zoom be';
$zmSlangZoomOut              = 'Zoom ki';
$zmSlangZoom                 = 'Zoom';

// Complex replacements with formatting and/or placements, must be passed through sprintf
$zmClangCurrentLogin         = 'Jelenleg belépve mint \'%1$s\'';
$zmClangEventCount           = '%1$s %2$s'; // For example '37 Events' (from Vlang below)
$zmClangLastEvents           = 'Utolsó %1$s %2$s'; // For example 'Last 37 Events' (from Vlang below)
$zmClangLatestRelease        = 'Az utolsó kiadás v%1$s, ami neked van v%2$s.';
$zmClangMonitorCount         = '%1$s %2$s'; // For example '4 Monitors' (from Vlang below)
$zmClangMonitorFunction      = 'Megfigyelés funkció: %1$s';
$zmClangRunningRecentVer     = 'A legfrissebb ZoneMinder verziót használod, v%s.';

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
$zmVlangEvent                = array( 0=>'Események', 1=>'Esemény', 2=>'Esemény' );
$zmVlangMonitor              = array( 0=>'Monitorok', 1=>'Monitor', 2=>'Monitor' );

// You will need to choose or write a function that can correlate the plurality string arrays
// with variable counts. This is used to conjugate the Vlang arrays above with a number passed
// in to generate the correct noun form.
//
// In languages such as English this is fairly simple 
// Note this still has to be used with printf etc to get the right formating
function zmVlang( $lang_var_array, $count )
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
}

// This is an version that could be used in the Russian example above
// The rules are that the first word form is used if the count ends in
// 0, 5-9 or 11-19. The second form is used then the count ends in 1
// (not including 11 as above) and the third form is used when the 
// count ends in 2-4, again excluding any values ending in 12-14.
// 
// function zmVlang( $lang_var_array, $count )
// {
// 	$secondlastdigit = substr( $count, -2, 1 );
// 	$lastdigit = substr( $count, -1, 1 );
// 	// or
// 	// $secondlastdigit = ($count/10)%10;
// 	// $lastdigit = $count%10;
// 
// 	// Get rid of the special cases first, the teens
// 	if ( $secondlastdigit == 1 && $lastdigit != 0 )
// 	{
// 		return( $lang_var_array[1] );
// 	}
// 	switch ( $lastdigit )
// 	{
// 		case 0 :
// 		case 5 :
// 		case 6 :
// 		case 7 :
// 		case 8 :
// 		case 9 :
// 		{
// 			return( $lang_var_array[1] );
// 			break;
// 		}
// 		case 1 :
// 		{
// 			return( $lang_var_array[2] );
// 			break;
// 		}
// 		case 2 :
// 		case 3 :
// 		case 4 :
// 		{
// 			return( $lang_var_array[3] );
// 			break;
// 		}
// 	}
// 	die( 'Error, unable to correlate variable language string' );
// }

// This is an example of how the function is used in the code which you can uncomment and 
// use to test your custom function.
//$monitors = array();
//$monitors[] = 1; // Choose any number
//echo sprintf( $zmClangMonitorCount, count($monitors), zmVlang( $zmVlangMonitor, count($monitors) ) );

// In this section you can override the default prompt and help texts for the options area
// These overrides are in the form of $zmOlangPrompt<option> and $zmOlangHelp<option>
// where <option> represents the option name minus the initial ZM_
// So for example, to override the help text for ZM_LANG_DEFAULT do
//$zmOlangPromptLANG_DEFAULT = "This is a new prompt for this option";
//$zmOlangHelpLANG_DEFAULT = "This is some new help for this option which will be displayed in the popup window when the ? is clicked";
//

?>

