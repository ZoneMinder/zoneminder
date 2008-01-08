<?php
//
// ZoneMinder web UK English language file, $Date$, $Revision$
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

// ZoneMinder Czech Translation by Lukas Pokorny/Mlada Boleslav

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
//require_once( 'zm_lang_en_gb.php' );

// You may need to change the character set here, if your web server does not already
// do this by default, uncomment this if required.
//
// Example
//header( "Content-Type: text/html; charset=iso-8859-2" );

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
//setlocale( 'LC_ALL', 'cs_CZ' ); All locale settings pre-4.3.0
// setlocale( LC_ALL, 'en_GB' ); All locale settings 4.3.0 and after
// setlocale( LC_CTYPE, 'en_GB' ); Character class settings 4.3.0 and after
// setlocale( LC_TIME, 'en_GB' ); Date and time formatting 4.3.0 and after

// Simple String Replacements
$zmSlang24BitColour          = '24 bit barevná';
$zmSlang8BitGrey             = '8 bit ¹edá ¹kála';
$zmSlangAction               = 'Akce';
$zmSlangActual               = 'Skuteèná';
$zmSlangAddNewControl        = 'Pøidat nové øízení';
$zmSlangAddNewMonitor        = 'Pøidat kameru';
$zmSlangAddNewUser           = 'Pøidat u¾ivatele';
$zmSlangAddNewZone           = 'Pøidat zónu';
$zmSlangAlarm                = 'Alarm';
$zmSlangAlarmBrFrames        = 'Alarm<br/>Snímky';
$zmSlangAlarmFrame           = 'Alarm snímek';
$zmSlangAlarmFrameCount      = 'Poèet alarm snímkù';
$zmSlangAlarmLimits          = 'Limity alarmu';
$zmSlangAlarmMaximumFPS      = 'Alarm Maximum FPS';
$zmSlangAlarmPx              = 'Alarm Px';
$zmSlangAlarmRGBUnset        = 'You must set an alarm RGB colour';
$zmSlangAlert                = 'Pozor';
$zmSlangAll                  = 'V¹echny';
$zmSlangApplyingStateChange  = 'Aplikuji zmìnu stavu';
$zmSlangApply                = 'Pou¾ít';
$zmSlangArchArchived         = 'Pouze archivované';
$zmSlangArchive              = 'Archiv';
$zmSlangArchived             = 'Archivován';
$zmSlangArchUnarchived       = 'Pouze nearchivované';
$zmSlangArea                 = 'Area';
$zmSlangAreaUnits            = 'Area (px/%)';
$zmSlangAttrAlarmFrames      = 'Alarm snímky';
$zmSlangAttrArchiveStatus    = 'Archiv status';
$zmSlangAttrAvgScore         = 'Prùm. skóre';
$zmSlangAttrCause            = 'Pøíèina';
$zmSlangAttrDate             = 'Datum';
$zmSlangAttrDateTime         = 'Datum/Èas';
$zmSlangAttrDiskBlocks       = 'Bloky disku';
$zmSlangAttrDiskPercent      = 'Zaplnìní disku';
$zmSlangAttrDuration         = 'Prùbìh';
$zmSlangAttrFrames           = 'Snímky';
$zmSlangAttrId               = 'Id';
$zmSlangAttrMaxScore         = 'Max. skóre';
$zmSlangAttrMonitorId        = 'Kamera Id';
$zmSlangAttrMonitorName      = 'Jméno kamery';
$zmSlangAttrName             = 'Jméno';
$zmSlangAttrNotes            = 'Notes';
$zmSlangAttrSystemLoad       = 'System Load';
$zmSlangAttrTime             = 'Èas';
$zmSlangAttrTotalScore       = 'Celkové skóre';
$zmSlangAttrWeekday          = 'Den v týdnu';
$zmSlangAuto                 = 'Auto';
$zmSlangAutoStopTimeout      = 'Èasový limit pro vypr¹ení';
$zmSlangAvgBrScore           = 'Prùm.<br/>Skóre';
$zmSlangBackground           = 'Background';
$zmSlangBackgroundFilter     = 'Run filter in background';
$zmSlangBadAlarmFrameCount   = 'Alarm frame count must be an integer of one or more';
$zmSlangBadAlarmMaxFPS       = 'Alarm Maximum FPS must be a positive integer or floating point value';
$zmSlangBadChannel           = 'Channel must be set to an integer of zero or more';
$zmSlangBadDevice            = 'Device must be set to a valid value';
$zmSlangBadFormat            = 'Format must be set to an integer of zero or more';
$zmSlangBadFPSReportInterval = 'FPS report interval buffer count must be an integer of 100 or more';
$zmSlangBadFrameSkip         = 'Frame skip count must be an integer of zero or more';
$zmSlangBadHeight            = 'Height must be set to a valid value';
$zmSlangBadHost              = 'Host must be set to a valid ip address or hostname, do not include http://';
$zmSlangBadImageBufferCount  = 'Image buffer size must be an integer of 10 or more';
$zmSlangBadLabelX            = 'Label X co-ordinate must be set to an integer of zero or more';
$zmSlangBadLabelY            = 'Label Y co-ordinate must be set to an integer of zero or more';
$zmSlangBadMaxFPS            = 'Maximum FPS must be a positive integer or floating point value';
$zmSlangBadNameChars         = 'Jména moho obsahovat pouze alfanumerické znaky a podtr¾ítko èi pomlèku';
$zmSlangBadPath              = 'Path must be set to a valid value';
$zmSlangBadPort              = 'Port must be set to a valid number';
$zmSlangBadPostEventCount    = 'Post event image count must be an integer of zero or more';
$zmSlangBadPreEventCount     = 'Pre event image count must be at least zero, and less than image buffer size';
$zmSlangBadRefBlendPerc      = 'Reference blend percentage must be a positive integer';
$zmSlangBadSectionLength     = 'Section length must be an integer of 30 or more';
$zmSlangBadSignalCheckColour = 'Signal check colour must be a valid RGB colour string';
$zmSlangBadStreamReplayBuffer= 'Stream replay buffer must be an integer of zero or more';
$zmSlangBadWarmupCount       = 'Warmup frames must be an integer of zero or more';
$zmSlangBadWebColour         = 'Web colour must be a valid web colour string';
$zmSlangBadWidth             = 'Width must be set to a valid value';
$zmSlangBandwidth            = 'Rychlost sítì';
$zmSlangBlobPx               = 'Znaèka Px';
$zmSlangBlobSizes            = 'Velikost znaèky';
$zmSlangBlobs                = 'Znaèky';
$zmSlangBrightness           = 'Svìtlost';
$zmSlangBuffers              = 'Bufery';
$zmSlangCanAutoFocus         = 'Umí automaticky zaostøit';
$zmSlangCanAutoGain          = 'Umí automatický zisk';
$zmSlangCanAutoIris          = 'Umí auto iris';
$zmSlangCanAutoWhite         = 'Umí automaticky vyvá¾it bílou';
$zmSlangCanAutoZoom          = 'Umí automaticky zoomovat';
$zmSlangCancelForcedAlarm    = 'Zastavit spu¹tìný alarm';
$zmSlangCancel               = 'Zru¹it';
$zmSlangCanFocusAbs          = 'Umí zaostøit absolutnì';
$zmSlangCanFocusCon          = 'Umí prùbì¾nì zaostøit';
$zmSlangCanFocusRel          = 'Umí relativnì zaostøit';
$zmSlangCanFocus             = 'Umí zaostøit';
$zmSlangCanGainAbs           = 'Umí absolutní zisk';
$zmSlangCanGainCon           = 'Umí prùbì¾ný zisk';
$zmSlangCanGainRel           = 'Umí relativní zisk';
$zmSlangCanGain              = 'Umí zisk';
$zmSlangCanIrisAbs           = 'Umí absolutní iris';
$zmSlangCanIrisCon           = 'Umí prùbì¾ný iris';
$zmSlangCanIrisRel           = 'Umí relativní iris';
$zmSlangCanIris              = 'Umí iris';
$zmSlangCanMoveAbs           = 'Umí absoultní pohyb';
$zmSlangCanMoveCon           = 'Umí prùbì¾ný pohyb';
$zmSlangCanMoveDiag          = 'Umí diagonální pohyb';
$zmSlangCanMoveMap           = 'Umí mapovaný pohyb';
$zmSlangCanMoveRel           = 'Umí relativní pohyb';
$zmSlangCanMove              = 'Umí pohyb';
$zmSlangCanPan               = 'Umí otáèení';
$zmSlangCanReset             = 'Umí reset';
$zmSlangCanSetPresets        = 'Umí navolit pøedvolby';
$zmSlangCanSleep             = 'Mù¾e spát';
$zmSlangCanTilt              = 'Umí náklon';
$zmSlangCanWake              = 'Lze vzbudit';
$zmSlangCanWhiteAbs          = 'Umí absolutní vyvá¾ení bílé';
$zmSlangCanWhiteBal          = 'Umí vyvá¾ení bílé';
$zmSlangCanWhiteCon          = 'Umí prùbì¾né vyvá¾ení bílé';
$zmSlangCanWhiteRel          = 'Umí relativní vyvá¾ení bílé';
$zmSlangCanWhite             = 'Umí vyvá¾ení bílé';
$zmSlangCanZoomAbs           = 'Umí absolutní zoom';
$zmSlangCanZoomCon           = 'Umí prùbì¾ný zoom';
$zmSlangCanZoomRel           = 'Umí relativní zoom';
$zmSlangCanZoom              = 'Umí zoom';
$zmSlangCaptureHeight        = 'Vý¹ka zdrojového snímku';
$zmSlangCapturePalette       = 'Paleta zdrojového snímku';
$zmSlangCaptureWidth         = '©íøka zdrojového snímku';
$zmSlangCause                = 'Pøíèina';
$zmSlangCheckMethod          = 'Metoda znaèkování alarmem';
$zmSlangChooseFilter         = 'Vybrat filtr';
$zmSlangChoosePreset         = 'Choose Preset';
$zmSlangClose                = 'Zavøít';
$zmSlangColour               = 'Barva';
$zmSlangCommand              = 'Pøíkaz';
$zmSlangConfig               = 'Nastavení';
$zmSlangConfiguredFor        = 'Nastaveno pro';
$zmSlangConfirmDeleteEvents  = 'Are you sure you wish to delete the selected events?';
$zmSlangConfirmPassword      = 'Potvrdit heslo';
$zmSlangConjAnd              = 'a';
$zmSlangConjOr               = 'nebo';
$zmSlangConsole              = 'Konzola';
$zmSlangContactAdmin         = 'Pro detailní info kontaktujte Va¹eho administrátora.';
$zmSlangContinue             = 'Pokraèovat';
$zmSlangContrast             = 'Kontrast';
$zmSlangControlAddress       = 'Adresa øízení';
$zmSlangControlCap           = 'Schopnosti øízení';
$zmSlangControlCaps          = 'Typy øízení';
$zmSlangControlDevice        = 'Zaøízení øízení';
$zmSlangControllable         = 'Øíditelná';
$zmSlangControlType          = 'Typ øízení';
$zmSlangControl              = 'Øízení';
$zmSlangCycle                = 'Cyklus';
$zmSlangCycleWatch           = 'Cyklické prohlí¾ení';
$zmSlangDay                  = 'Den';
$zmSlangDebug                = 'Debug';
$zmSlangDefaultRate          = 'Default Rate';
$zmSlangDefaultScale         = 'Pøednastavená velikost';
$zmSlangDefaultView          = 'Default View';
$zmSlangDeleteAndNext        = 'Smazat &amp; Dal¹í';
$zmSlangDeleteAndPrev        = 'Smazat &amp; Pøedchozí';
$zmSlangDeleteSavedFilter    = 'Smazat filtr';
$zmSlangDelete               = 'Smazat';
$zmSlangDescription          = 'Popis';
$zmSlangDeviceChannel        = 'Kanál zaøízení';
$zmSlangDeviceFormat         = 'Formát zaøízení';
$zmSlangDeviceNumber         = 'Èíslo zarízení';
$zmSlangDevicePath           = 'Cesta k zaøízení';
$zmSlangDevices              = 'Devices';
$zmSlangDimensions           = 'Rozmìry';
$zmSlangDisableAlarms        = 'Zakázat alarmy';
$zmSlangDisk                 = 'Disk';
$zmSlangDonateAlready        = 'Ne, u¾ jsem podpoøil';
$zmSlangDonateEnticement     = 'Ji¾ nìjakou dobu pou¾íváte software ZoneMinder k ochranì svého majetku a pøedpokládám, ¾e jej shledáváte u¾iteèným. Pøesto¾e je ZoneMinder, znovu pøipomínám, zdarma a volnì ¹íøený software, stojí jeho vývoj a podpora nìjaké peníze. Pokud byste chtìl/a podpoøit budoucí vývoj a nové mo¾nosti softwaru, prosím zva¾te darování finanèní pomoci. Darování je, samozøejmì, dobrovolné, ale zato velmi cenìné mù¾ete pøispìt jakou èástkou chcete.<br><br>Pokud máte zájem podpoøit ná¹ tým, prosím, vyberte ní¾e uvedenou mo¾nost, nebo nav¹tivte http://www.zoneminder.com/donate.html.<br><br>Dìkuji Vám ¾e jste si vybral/a software ZoneMinder a nezapomeòte nav¹tívit fórum na ZoneMinder.com pro podporu a návrhy jak udìlat ZoneMinder je¹tì lep¹ím ne¾ je dnes.';
$zmSlangDonate               = 'Prosím podpoøte';
$zmSlangDonateRemindDay      = 'Nyní ne, pøipomenout za 1 den';
$zmSlangDonateRemindHour     = 'Nyní ne, pøipomenout za hodinu';
$zmSlangDonateRemindMonth    = 'Nyní ne, pøipomenout za mìsíc';
$zmSlangDonateRemindNever    = 'Ne, nechci podpoøit ZoneMinder, nepøipomínat';
$zmSlangDonateRemindWeek     = 'Nyní ne, pøipomenout za týden';
$zmSlangDonateYes            = 'Ano, chcit podpoøit ZoneMinder nyní';
$zmSlangDownload             = 'Stáhnout';
$zmSlangDuration             = 'Prùbìh';
$zmSlangEdit                 = 'Editovat';
$zmSlangEmail                = 'Email';
$zmSlangEnableAlarms         = 'Povolit alarmy';
$zmSlangEnabled              = 'Povoleno';
$zmSlangEnterNewFilterName   = 'Zadejte nové jméno filtru';
$zmSlangErrorBrackets        = 'Chyba, zkontrolujte prosím závorky';
$zmSlangError                = 'Chyba';
$zmSlangErrorValidValue      = 'Chyba, zkontrolujte ¾e podmínky mají správné hodnoty';
$zmSlangEtc                  = 'atd';
$zmSlangEventFilter          = 'Filtr záznamù';
$zmSlangEventId              = 'Id záznamu';
$zmSlangEventName            = 'Jméno záznamu';
$zmSlangEventPrefix          = 'Prefix záznamu';
$zmSlangEvents               = 'Záznamy';
$zmSlangEvent                = 'Záznam';
$zmSlangExclude              = 'Vyjmout';
$zmSlangExecute              = 'Execute';
$zmSlangExportDetails        = 'Exportovat detaily záznamu';
$zmSlangExport               = 'Exportovat';
$zmSlangExportFailed         = 'Chyba pøi exportu';
$zmSlangExportFormat         = 'Formát exportovaného souboru';
$zmSlangExportFormatTar      = 'Tar';
$zmSlangExportFormatZip      = 'Zip';
$zmSlangExportFrames         = 'Exportovat detaily snímku';
$zmSlangExportImageFiles     = 'Exportovat obrazové soubory';
$zmSlangExporting            = 'Exportuji';
$zmSlangExportMiscFiles      = 'Exportovat ostatní soubory (jestli existují)';
$zmSlangExportOptions        = 'Mo¾nosti exportu';
$zmSlangExportVideoFiles     = 'Exportovat video soubory (jestli existují)';
$zmSlangFar                  = 'Daleko';
$zmSlangFastForward          = 'Fast Forward';
$zmSlangFeed                 = 'Nasytit';
$zmSlangFileColours          = 'Barvy souboru';
$zmSlangFilePath             = 'Cesta k souboru';
$zmSlangFile                 = 'Soubor';
$zmSlangFilterArchiveEvents  = 'Archivovat v¹echny nalezené';
$zmSlangFilterDeleteEvents   = 'Smazat v¹echny nalezené';
$zmSlangFilterEmailEvents    = 'Poslat email s detaily nalezených';
$zmSlangFilterExecuteEvents  = 'Spustit pøíkaz na v¹ech nalezených';
$zmSlangFilterMessageEvents  = 'Podat zprávu o v¹ech nalezených';
$zmSlangFilterPx             = 'Filtr Px';
$zmSlangFilters              = 'Filtry';
$zmSlangFilterUnset          = 'You must specify a filter width and height';
$zmSlangFilterUploadEvents   = 'Uploadovat nalezené';
$zmSlangFilterVideoEvents    = 'Create video for all matches';
$zmSlangFirst                = 'První';
$zmSlangFlippedHori          = 'Pøeklopený vodorovnì';
$zmSlangFlippedVert          = 'Pøeklopený svisle';
$zmSlangFocus                = 'Zaostøení';
$zmSlangForceAlarm           = 'Spustit alarm';
$zmSlangFormat               = 'Formát';
$zmSlangFPS                  = 'fps';
$zmSlangFPSReportInterval    = 'FPS Interval pro report';
$zmSlangFrameId              = 'Snímek Id';
$zmSlangFrameRate            = 'Rychlost snímkù';
$zmSlangFrameSkip            = 'Vynechat snímek';
$zmSlangFrame                = 'Snímek';
$zmSlangFrames               = 'Snímky';
$zmSlangFTP                  = 'FTP';
$zmSlangFunc                 = 'Funkce';
$zmSlangFunction             = 'Funkce';
$zmSlangGain                 = 'Zisk';
$zmSlangGeneral              = 'General';
$zmSlangGenerateVideo        = 'Generovat video';
$zmSlangGeneratingVideo      = 'Generuji video';
$zmSlangGoToZoneMinder       = 'Jít na ZoneMinder.com';
$zmSlangGrey                 = '©edá';
$zmSlangGroup                = 'Group';
$zmSlangGroups               = 'Skupiny';
$zmSlangHasFocusSpeed        = 'Má rychlost zaostøení';
$zmSlangHasGainSpeed         = 'Má rychlost zisku';
$zmSlangHasHomePreset        = 'Má Home volbu';
$zmSlangHasIrisSpeed         = 'Má rychlost irisu';
$zmSlangHasPanSpeed          = 'Má rychlost otáèení';
$zmSlangHasPresets           = 'Má pøedvolby';
$zmSlangHasTiltSpeed         = 'Má rychlost náklonu';
$zmSlangHasTurboPan          = 'Má Turbo otáèení';
$zmSlangHasTurboTilt         = 'Má Turbo náklon';
$zmSlangHasWhiteSpeed        = 'Má rychlost vyvá¾ení bílé';
$zmSlangHasZoomSpeed         = 'Má rychlost zoomu';
$zmSlangHighBW               = 'Rychlá&nbsp;B/W';
$zmSlangHigh                 = 'Rychlá';
$zmSlangHome                 = 'Domù';
$zmSlangHour                 = 'Hodina';
$zmSlangHue                  = 'Odstín';
$zmSlangId                   = 'Id';
$zmSlangIdle                 = 'Pøipraven';
$zmSlangIgnore               = 'Ignorovat';
$zmSlangImageBufferSize      = 'Velikost buferu snímkù';
$zmSlangImage                = 'Obraz';
$zmSlangImages               = 'Images';
$zmSlangInclude              = 'Vlo¾it';
$zmSlangIn                   = 'Dovnitø';
$zmSlangInverted             = 'Pøevrácenì';
$zmSlangIris                 = 'Iris';
$zmSlangKeyString            = 'Key String';
$zmSlangLabel                = 'Label';
$zmSlangLanguage             = 'Jazyk';
$zmSlangLast                 = 'Poslední';
$zmSlangLimitResultsPost     = 'výsledkù'; // This is used at the end of the phrase 'Limit to first N results only'
$zmSlangLimitResultsPre      = 'Zobrazit pouze prvních'; // This is used at the beginning of the phrase 'Limit to first N results only'
$zmSlangLinkedMonitors       = 'Linked Monitors';
$zmSlangList                 = 'Seznam';
$zmSlangLoad                 = 'Load';
$zmSlangLocal                = 'Lokální';
$zmSlangLoggedInAs           = 'Pøihlá¹en jako';
$zmSlangLoggingIn            = 'Pøihla¹uji';
$zmSlangLogin                = 'Pøihlásit';
$zmSlangLogout               = 'Odhlásit';
$zmSlangLowBW                = 'Pomalá&nbsp;B/W';
$zmSlangLow                  = 'Pomalá';
$zmSlangMain                 = 'Hlavní';
$zmSlangMan                  = 'Man';
$zmSlangManual               = 'Manuál';
$zmSlangMark                 = 'Oznaèit';
$zmSlangMaxBandwidth         = 'Max bandwidth';
$zmSlangMaxBrScore           = 'Max.<br/>skóre';
$zmSlangMaxFocusRange        = 'Max rozsah zaostøení';
$zmSlangMaxFocusSpeed        = 'Max rychlost zaostøení';
$zmSlangMaxFocusStep         = 'Max krok zaostøení';
$zmSlangMaxGainRange         = 'Max rozsah zisku';
$zmSlangMaxGainSpeed         = 'Max rychlost zisku';
$zmSlangMaxGainStep          = 'Max krok zisku';
$zmSlangMaximumFPS           = 'Maximum FPS';
$zmSlangMaxIrisRange         = 'Max rozsah iris';
$zmSlangMaxIrisSpeed         = 'Max rychlost iris';
$zmSlangMaxIrisStep          = 'Max krok iris';
$zmSlangMax                  = 'Max';
$zmSlangMaxPanRange          = 'Max rozsah otáèení';
$zmSlangMaxPanSpeed          = 'Max rychlost otáèení';
$zmSlangMaxPanStep           = 'Max krok otáèení';
$zmSlangMaxTiltRange         = 'Max rozsah náklonu';
$zmSlangMaxTiltSpeed         = 'Max rychlost náklonu';
$zmSlangMaxTiltStep          = 'Max krok náklonu';
$zmSlangMaxWhiteRange        = 'Max rozsah vyvá¾ení bílé';
$zmSlangMaxWhiteSpeed        = 'Max rychlost vyvá¾ení bílé';
$zmSlangMaxWhiteStep         = 'Max krok vyvá¾ení bílé';
$zmSlangMaxZoomRange         = 'Max rozsah zoomu';
$zmSlangMaxZoomSpeed         = 'Max rychlost zoomu';
$zmSlangMaxZoomStep          = 'Max krok zoomu';
$zmSlangMediumBW             = 'Støední&nbsp;B/W';
$zmSlangMedium               = 'Støední';
$zmSlangMinAlarmAreaLtMax    = 'Minimum alarm area should be less than maximum';
$zmSlangMinAlarmAreaUnset    = 'You must specify the minimum alarm pixel count';
$zmSlangMinBlobAreaLtMax     = 'Minimum znaèkované oblasti by mìlo být men¹í ne¾ maximum';
$zmSlangMinBlobAreaUnset     = 'You must specify the minimum blob pixel count';
$zmSlangMinBlobLtMinFilter   = 'Minimum blob area should be less than or equal to minimum filter area';
$zmSlangMinBlobsLtMax        = 'Minimum znaèek by mìlo být men¹í ne¾ maximum';
$zmSlangMinBlobsUnset        = 'You must specify the minimum blob count';
$zmSlangMinFilterAreaLtMax   = 'Minimum filter area should be less than maximum';
$zmSlangMinFilterAreaUnset   = 'You must specify the minimum filter pixel count';
$zmSlangMinFilterLtMinAlarm  = 'Minimum filter area should be less than or equal to minimum alarm area';
$zmSlangMinFocusRange        = 'Min rozsah zaostøení';
$zmSlangMinFocusSpeed        = 'Min rychlost zaostøení';
$zmSlangMinFocusStep         = 'Min krok zaostøení';
$zmSlangMinGainRange         = 'Min rozsah zisku';
$zmSlangMinGainSpeed         = 'Min rychlost zisku';
$zmSlangMinGainStep          = 'Min krok zisku';
$zmSlangMinIrisRange         = 'Min rozsah iris';
$zmSlangMinIrisSpeed         = 'Min rychlost iris';
$zmSlangMinIrisStep          = 'Min krok iris';
$zmSlangMinPanRange          = 'Min rozsah otáèení';
$zmSlangMinPanSpeed          = 'Min rychlost otáèení';
$zmSlangMinPanStep           = 'Min krok otáèení';
$zmSlangMinPixelThresLtMax   = 'Minimální práh pixelu by mìl být men¹í ne¾  maximumální';
$zmSlangMinPixelThresUnset   = 'You must specify a minimum pixel threshold';
$zmSlangMinTiltRange         = 'Min rozsah náklonu';
$zmSlangMinTiltSpeed         = 'Min rychlost náklonu';
$zmSlangMinTiltStep          = 'Min krok náklonu';
$zmSlangMinWhiteRange        = 'Min rozsah vyvá¾ení bílé';
$zmSlangMinWhiteSpeed        = 'Min rychlost vyvá¾ení bílé';
$zmSlangMinWhiteStep         = 'Min krok vyvá¾ení bílé';
$zmSlangMinZoomRange         = 'Min rozsah zoomu';
$zmSlangMinZoomSpeed         = 'Min rychlost zoomu';
$zmSlangMinZoomStep          = 'Min krok zoomu';
$zmSlangMisc                 = 'Ostatní';
$zmSlangMonitorIds           = 'Id&nbsp;kamer';
$zmSlangMonitor              = 'Kamera';
$zmSlangMonitorPresetIntro   = 'Select an appropriate preset from the list below.<br><br>Please note that this may overwrite any values you already have configured for this monitor.<br><br>';
$zmSlangMonitorPreset        = 'Monitor Preset';
$zmSlangMonitors             = 'Kamery';
$zmSlangMontage              = 'Sestøih';
$zmSlangMonth                = 'Mìsíc';
$zmSlangMove                 = 'Pohyb';
$zmSlangMustBeGe             = 'musí být vìt¹í nebo rovno ne¾';
$zmSlangMustBeLe             = 'musí být men¹í nebo rovno ne¾';
$zmSlangMustConfirmPassword  = 'Musíte potvrdit heslo';
$zmSlangMustSupplyPassword   = 'Musíte zadat heslo';
$zmSlangMustSupplyUsername   = 'Musíte zadat u¾ivatelské jméno';
$zmSlangName                 = 'Jméno';
$zmSlangNear                 = 'Blízko';
$zmSlangNetwork              = 'Sí»';
$zmSlangNewGroup             = 'Nová skupina';
$zmSlangNewLabel             = 'New Label';
$zmSlangNew                  = 'Nový';
$zmSlangNewPassword          = 'Nové heslo';
$zmSlangNewState             = 'Nový stav';
$zmSlangNewUser              = 'Nový u¾ivatel';
$zmSlangNext                 = 'Dal¹í';
$zmSlangNoFramesRecorded     = 'Pro tento snímek nejsou ¾ádné záznamy';
$zmSlangNoGroup              = 'No Group';
$zmSlangNo                   = 'Ne';
$zmSlangNoneAvailable        = '®ádná není dostupná';
$zmSlangNone                 = 'Zakázat';
$zmSlangNormal               = 'Normalní';
$zmSlangNoSavedFilters       = '®ádné ulo¾ené filtry';
$zmSlangNoStatisticsRecorded = 'Pro tento záznam/snímek nejsou zaznamenány ¾ádné statistiky';
$zmSlangNotes                = 'Poznámky';
$zmSlangNumPresets           = 'Poèet pøedvoleb';
$zmSlangOff                  = 'Off';
$zmSlangOn                   = 'On';
$zmSlangOpen                 = 'Otevøít';
$zmSlangOpEq                 = 'rovno';
$zmSlangOpGtEq               = 'vìt¹í nebo rovno';
$zmSlangOpGt                 = 'vìt¹í';
$zmSlangOpIn                 = 'nin set';
$zmSlangOpLtEq               = 'men¹í nebo rovno';
$zmSlangOpLt                 = 'men¹í';
$zmSlangOpMatches            = 'obsahuje';
$zmSlangOpNe                 = 'nerovná se';
$zmSlangOpNotIn              = 'nnot in set';
$zmSlangOpNotMatches         = 'neobsahuje';
$zmSlangOptionHelp           = 'Mo¾nostHelp';
$zmSlangOptionRestartWarning = 'Tyto zmìny se neprojeví\ndokud systém bì¾í. Jakmile\ndokonèíte provádìní zmìn prosím\nrestartujte ZoneMinder.';
$zmSlangOptions              = 'Mo¾nosti';
$zmSlangOrder                = 'Poøadí';
$zmSlangOrEnterNewName       = 'nebo vlo¾te nové jméno';
$zmSlangOrientation          = 'Orientace';
$zmSlangOut                  = 'Ven';
$zmSlangOverwriteExisting    = 'Pøepsat existující';
$zmSlangPaged                = 'Strákovì';
$zmSlangPanLeft              = 'Posunout vlevo';
$zmSlangPan                  = 'Otáèení';
$zmSlangPanRight             = 'Posunout vpravo';
$zmSlangPanTilt              = 'Otáèení/Náklon';
$zmSlangParameter            = 'Parametr';
$zmSlangPassword             = 'Heslo';
$zmSlangPasswordsDifferent   = 'Hesla se neshodují';
$zmSlangPaths                = 'Cesty';
$zmSlangPause                = 'Pause';
$zmSlangPhoneBW              = 'Modem&nbsp;B/W';
$zmSlangPhone                = 'Modem';
$zmSlangPixelDiff            = 'Pixel Diff';
$zmSlangPixels               = 'pixely';
$zmSlangPlayAll              = 'Pøehrát v¹e';
$zmSlangPlay                 = 'Play';
$zmSlangPleaseWait           = 'Prosím èekejte';
$zmSlangPoint                = 'Point';
$zmSlangPostEventImageBuffer = 'Pozáznamový bufer';
$zmSlangPreEventImageBuffer  = 'Pøedzáznamový bufer';
$zmSlangPreserveAspect       = 'Preserve Aspect Ratio';
$zmSlangPreset               = 'Pøedvolba';
$zmSlangPresets              = 'Pøedvolby';
$zmSlangPrev                 = 'Zpìt';
$zmSlangProtocol             = 'Protocol';
$zmSlangRate                 = 'Rychlost';
$zmSlangReal                 = 'Skuteèná';
$zmSlangRecord               = 'Nahrávat';
$zmSlangRefImageBlendPct     = 'Reference Image Blend %ge';
$zmSlangRefresh              = 'Obnovit';
$zmSlangRemoteHostName       = 'Adresa';
$zmSlangRemoteHostPath       = 'Cesta';
$zmSlangRemoteHostPort       = 'Port';
$zmSlangRemoteImageColours   = 'Barvy';
$zmSlangRemote               = 'Sí»ová';
$zmSlangRename               = 'Pøejmenovat';
$zmSlangReplayAll            = 'All Events';
$zmSlangReplayGapless        = 'Gapless Events';
$zmSlangReplay               = 'Pøehrát znovu';
$zmSlangReplay               = 'Replay';
$zmSlangReplaySingle         = 'Single Event';
$zmSlangResetEventCounts     = 'Resetovat poèty záznamù';
$zmSlangReset                = 'Reset';
$zmSlangRestarting           = 'Restartuji';
$zmSlangRestart              = 'Restartovat';
$zmSlangRestrictedCameraIds  = 'Povolené id kamer';
$zmSlangRestrictedMonitors   = 'Restricted Monitors';
$zmSlangReturnDelay          = 'Prodleva vracení';
$zmSlangReturnLocation       = 'Lokace vrácení';
$zmSlangRewind               = 'Rewind';
$zmSlangRotateLeft           = 'Otoèit vlevo';
$zmSlangRotateRight          = 'Otoèit vpravo';
$zmSlangRunMode              = 'Re¾im';
$zmSlangRunning              = 'Bì¾í';
$zmSlangRunState             = 'Stav';
$zmSlangSaveAs               = 'Ulo¾it jako';
$zmSlangSaveFilter           = 'Ulo¾it filtr';
$zmSlangSave                 = 'Ulo¾it';
$zmSlangScale                = 'Velikost';
$zmSlangScore                = 'Skóre';
$zmSlangSecs                 = 'Délka(s)';
$zmSlangSectionlength        = 'Délka sekce';
$zmSlangSelectMonitors       = 'Select Monitors';
$zmSlangSelect               = 'Vybrat';
$zmSlangSelfIntersecting     = 'Polygon edges must not intersect';
$zmSlangSetLearnPrefs        = 'Set Learn Prefs'; // This can be ignored for now
$zmSlangSet                  = 'Nastavit';
$zmSlangSetNewBandwidth      = 'Nastavit novou rychlost sítì';
$zmSlangSetPreset            = 'Nastavit pøedvolbu';
$zmSlangSettings             = 'Nastavení';
$zmSlangShowFilterWindow     = 'Zobrazit filtr';
$zmSlangShowTimeline         = 'Zobrazit èasovou linii ';
$zmSlangSignalCheckColour    = 'Signal Check Colour';
$zmSlangSize                 = 'Velikost';
$zmSlangSleep                = 'Spát';
$zmSlangSortAsc              = 'Vzestupnì';
$zmSlangSortBy               = 'Øadit dle';
$zmSlangSortDesc             = 'Sestupnì';
$zmSlangSourceType           = 'Typ zdroje';
$zmSlangSource               = 'Zdroj';
$zmSlangSpeedHigh            = 'Vysoká rychlost';
$zmSlangSpeedLow             = 'Nízká rychlost';
$zmSlangSpeedMedium          = 'Støední rychlost';
$zmSlangSpeed                = 'Rychlost';
$zmSlangSpeedTurbo           = 'Turbo rychlost';
$zmSlangStart                = 'Start';
$zmSlangState                = 'Stav';
$zmSlangStats                = 'Statistiky';
$zmSlangStatus               = 'Status';
$zmSlangStepBack             = 'Step Back';
$zmSlangStepForward          = 'Step Forward';
$zmSlangStep                 = 'Krok';
$zmSlangStepLarge            = 'Velký krok';
$zmSlangStepMedium           = 'Støední krok';
$zmSlangStepNone             = '®ádný krok';
$zmSlangStepSmall            = 'Malý krok';
$zmSlangStills               = 'Snímky';
$zmSlangStopped              = 'Zastaven';
$zmSlangStop                 = 'Zastavit';
$zmSlangStreamReplayBuffer   = 'Stream Replay Image Buffer';
$zmSlangStream               = 'Stream';
$zmSlangSubmit               = 'Potvrdit';
$zmSlangSystem               = 'System';
$zmSlangTele                 = 'Pøiblí¾it';
$zmSlangThumbnail            = 'Miniatura';
$zmSlangTilt                 = 'Náklon';
$zmSlangTime                 = 'Èas';
$zmSlangTimeDelta            = 'Delta èasu';
$zmSlangTimeline             = 'Èasová linie';
$zmSlangTimeStamp            = 'Èasové razítko';
$zmSlangTimestampLabelFormat = 'Formát èasového razítka';
$zmSlangTimestampLabelX      = 'Èasové razítko X';
$zmSlangTimestampLabelY      = 'Èasové razítko Y';
$zmSlangTimestamp            = 'Razítko';
$zmSlangToday                = 'Dnes';
$zmSlangTools                = 'Nástroje';
$zmSlangTotalBrScore         = 'Celkové<br/>skóre';
$zmSlangTrackDelay           = 'Prodleva dráhy';
$zmSlangTrackMotion          = 'Pohyb po dráze';
$zmSlangTriggers             = 'Trigery';
$zmSlangTurboPanSpeed        = 'Rychlost Turbo otáèení';
$zmSlangTurboTiltSpeed       = 'Rychlost Turbo náklonu';
$zmSlangType                 = 'Typ';
$zmSlangUnarchive            = 'Vyjmout z archivu';
$zmSlangUnits                = 'Jednotky';
$zmSlangUnknown              = 'Neznámý';
$zmSlangUpdateAvailable      = 'Je dostupný nový update ZoneMinder.';
$zmSlangUpdateNotNecessary   = 'Update není potøeba.';
$zmSlangUpdate               = 'Update';
$zmSlangUseFilterExprsPost   = '&nbsp;výrazù'; // This is used at the end of the phrase 'use N filter expressions'
$zmSlangUseFilterExprsPre    = 'Pou¾ít&nbsp;'; // This is used at the beginning of the phrase 'use N filter expressions'
$zmSlangUseFilter            = 'Pou¾ít filtr';
$zmSlangUsername             = 'U¾ivatelské jméno';
$zmSlangUsers                = 'U¾ivatelé';
$zmSlangUser                 = 'U¾ivatel';
$zmSlangValue                = 'Hodnota';
$zmSlangVersionIgnore        = 'Ignorovat tuto verzi';
$zmSlangVersionRemindDay     = 'Pøipomenout za 1 den';
$zmSlangVersionRemindHour    = 'Pøipomenout za hodinu';
$zmSlangVersionRemindNever   = 'Nepøipomínat nové veze';
$zmSlangVersionRemindWeek    = 'Pøipomenout za týden';
$zmSlangVersion              = 'Verze';
$zmSlangVideoFormat          = 'Video formát';
$zmSlangVideoGenFailed       = 'Chyba pøi generování videa!';
$zmSlangVideoGenFiles        = 'Existující video soubory';
$zmSlangVideoGenNoFiles      = '®ádné video soubory nenalezeny';
$zmSlangVideoGenParms        = 'Parametry generování videa';
$zmSlangVideoGenSucceeded    = 'Video vygenerováno úspì¹nì!';
$zmSlangVideoSize            = 'Velikost videa';
$zmSlangVideo                = 'Video';
$zmSlangViewAll              = 'Zobrazit v¹echny';
$zmSlangViewEvent            = 'Zobrazit záznam';
$zmSlangViewPaged            = 'Zobrazit strákovì';
$zmSlangView                 = 'Zobrazit';
$zmSlangWake                 = 'Vzbudit';
$zmSlangWarmupFrames         = 'Zahøívací snímky';
$zmSlangWatch                = 'Sledovat';
$zmSlangWebColour            = 'Webová barva';
$zmSlangWeb                  = 'Web';
$zmSlangWeek                 = 'Týden';
$zmSlangWhiteBalance         = 'Vyvá¾ení bílé';
$zmSlangWhite                = 'Bílá';
$zmSlangWide                 = 'Oddálit';
$zmSlangX10ActivationString  = 'X10 aktivaèní øetìzec';
$zmSlangX10InputAlarmString  = 'X10 input alarm øetìzec';
$zmSlangX10OutputAlarmString = 'X10 output alarm øetìzec';
$zmSlangX10                  = 'X10';
$zmSlangX                    = 'X';
$zmSlangYes                  = 'Ano';
$zmSlangYouNoPerms           = 'K tomuto zdroji nemáte oprávnìní.';
$zmSlangY                    = 'Y';
$zmSlangZoneAlarmColour      = 'Barva alarmu (Red/Green/Blue)';
$zmSlangZoneArea             = 'Zone Area';
$zmSlangZoneFilterSize       = 'Filter Width/Height (pixels)';
$zmSlangZoneMinMaxAlarmArea  = 'Min/Max Alarmed Area';
$zmSlangZoneMinMaxBlobArea   = 'Min/Max Blob Area';
$zmSlangZoneMinMaxBlobs      = 'Min/Max Blobs';
$zmSlangZoneMinMaxFiltArea   = 'Min/Max Filtered Area';
$zmSlangZoneMinMaxPixelThres = 'Min/Max Pixel Threshold (0-255)';
$zmSlangZoneOverloadFrames   = 'Overload Frame Ignore Count';
$zmSlangZones                = 'Zóny';
$zmSlangZone                 = 'Zóna';
$zmSlangZoomIn               = 'Zvìt¹it';
$zmSlangZoomOut              = 'Zmen¹it';
$zmSlangZoom                 = 'Zoom';

// Complex replacements with formatting and/or placements, must be passed through sprintf
$zmClangCurrentLogin         = 'Právì je pøihlá¹en \'%1$s\'';
$zmClangEventCount           = '%1$s %2$s'; // For example '37 Events' (from Vlang below)
$zmClangLastEvents           = 'Posledních %1$s %2$s'; // For example 'Last 37 Events' (from Vlang below)
$zmClangLatestRelease        = 'Poslední verze je v%1$s, vy máte v%2$s.';
$zmClangMonitorCount         = '%1$s %2$s'; // For example '4 Monitors' (from Vlang below)
$zmClangMonitorFunction      = 'Funkce %1$s kamery';
$zmClangRunningRecentVer     = 'Pou¾íváte poslední verzi ZoneMinder, v%s.';

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
$zmVlangEvent                = array( 0=>'Záznamù', 1=>'Záznam', 2=>'Záznamy', 5=>'Záznamù' );
$zmVlangMonitor              = array( 0=>'Kamer', 1=>'Kamera', 2=>'Kamery', 5=>'Kamer' );

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
