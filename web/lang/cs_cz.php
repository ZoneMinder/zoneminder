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
$SLANG = array(
    '24BitColour'          => '24 bit barevná',
    '8BitGrey'             => '8 bit ¹edá ¹kála',
    'Action'               => 'Akce',
    'Actual'               => 'Skuteèná',
    'AddNewControl'        => 'Pøidat nové øízení',
    'AddNewMonitor'        => 'Pøidat kameru',
    'AddNewUser'           => 'Pøidat u¾ivatele',
    'AddNewZone'           => 'Pøidat zónu',
    'Alarm'                => 'Alarm',
    'AlarmBrFrames'        => 'Alarm<br/>Snímky',
    'AlarmFrame'           => 'Alarm snímek',
    'AlarmFrameCount'      => 'Poèet alarm snímkù',
    'AlarmLimits'          => 'Limity alarmu',
    'AlarmMaximumFPS'      => 'Alarm Maximum FPS',
    'AlarmPx'              => 'Alarm Px',
    'AlarmRGBUnset'        => 'You must set an alarm RGB colour',
    'Alert'                => 'Pozor',
    'All'                  => 'V¹echny',
    'ApplyingStateChange'  => 'Aplikuji zmìnu stavu',
    'Apply'                => 'Pou¾ít',
    'ArchArchived'         => 'Pouze archivované',
    'Archive'              => 'Archiv',
    'Archived'             => 'Archivován',
    'ArchUnarchived'       => 'Pouze nearchivované',
    'Area'                 => 'Area',
    'AreaUnits'            => 'Area (px/%)',
    'AttrAlarmFrames'      => 'Alarm snímky',
    'AttrArchiveStatus'    => 'Archiv status',
    'AttrAvgScore'         => 'Prùm. skóre',
    'AttrCause'            => 'Pøíèina',
    'AttrDate'             => 'Datum',
    'AttrDateTime'         => 'Datum/Èas',
    'AttrDiskBlocks'       => 'Bloky disku',
    'AttrDiskPercent'      => 'Zaplnìní disku',
    'AttrDuration'         => 'Prùbìh',
    'AttrFrames'           => 'Snímky',
    'AttrId'               => 'Id',
    'AttrMaxScore'         => 'Max. skóre',
    'AttrMonitorId'        => 'Kamera Id',
    'AttrMonitorName'      => 'Jméno kamery',
    'AttrName'             => 'Jméno',
    'AttrNotes'            => 'Notes',
    'AttrSystemLoad'       => 'System Load',
    'AttrTime'             => 'Èas',
    'AttrTotalScore'       => 'Celkové skóre',
    'AttrWeekday'          => 'Den v týdnu',
    'Auto'                 => 'Auto',
    'AutoStopTimeout'      => 'Èasový limit pro vypr¹ení',
    'AvgBrScore'           => 'Prùm.<br/>Skóre',
    'Background'           => 'Background',
    'BackgroundFilter'     => 'Run filter in background',
    'BadAlarmFrameCount'   => 'Alarm frame count must be an integer of one or more',
    'BadAlarmMaxFPS'       => 'Alarm Maximum FPS must be a positive integer or floating point value',
    'BadChannel'           => 'Channel must be set to an integer of zero or more',
    'BadDevice'            => 'Device must be set to a valid value',
    'BadFormat'            => 'Format must be set to an integer of zero or more',
    'BadFPSReportInterval' => 'FPS report interval buffer count must be an integer of 100 or more',
    'BadFrameSkip'         => 'Frame skip count must be an integer of zero or more',
    'BadHeight'            => 'Height must be set to a valid value',
    'BadHost'              => 'Host must be set to a valid ip address or hostname, do not include http://',
    'BadImageBufferCount'  => 'Image buffer size must be an integer of 10 or more',
    'BadLabelX'            => 'Label X co-ordinate must be set to an integer of zero or more',
    'BadLabelY'            => 'Label Y co-ordinate must be set to an integer of zero or more',
    'BadMaxFPS'            => 'Maximum FPS must be a positive integer or floating point value',
    'BadNameChars'         => 'Jména moho obsahovat pouze alfanumerické znaky a podtr¾ítko èi pomlèku',
    'BadPath'              => 'Path must be set to a valid value',
    'BadPort'              => 'Port must be set to a valid number',
    'BadPostEventCount'    => 'Post event image count must be an integer of zero or more',
    'BadPreEventCount'     => 'Pre event image count must be at least zero, and less than image buffer size',
    'BadRefBlendPerc'      => 'Reference blend percentage must be a positive integer',
    'BadSectionLength'     => 'Section length must be an integer of 30 or more',
    'BadSignalCheckColour' => 'Signal check colour must be a valid RGB colour string',
    'BadStreamReplayBuffer'=> 'Stream replay buffer must be an integer of zero or more',
    'BadWarmupCount'       => 'Warmup frames must be an integer of zero or more',
    'BadWebColour'         => 'Web colour must be a valid web colour string',
    'BadWidth'             => 'Width must be set to a valid value',
    'Bandwidth'            => 'Rychlost sítì',
    'BlobPx'               => 'Znaèka Px',
    'BlobSizes'            => 'Velikost znaèky',
    'Blobs'                => 'Znaèky',
    'Brightness'           => 'Svìtlost',
    'Buffers'              => 'Bufery',
    'CanAutoFocus'         => 'Umí automaticky zaostøit',
    'CanAutoGain'          => 'Umí automatický zisk',
    'CanAutoIris'          => 'Umí auto iris',
    'CanAutoWhite'         => 'Umí automaticky vyvá¾it bílou',
    'CanAutoZoom'          => 'Umí automaticky zoomovat',
    'CancelForcedAlarm'    => 'Zastavit spu¹tìný alarm',
    'Cancel'               => 'Zru¹it',
    'CanFocusAbs'          => 'Umí zaostøit absolutnì',
    'CanFocusCon'          => 'Umí prùbì¾nì zaostøit',
    'CanFocusRel'          => 'Umí relativnì zaostøit',
    'CanFocus'             => 'Umí zaostøit',
    'CanGainAbs'           => 'Umí absolutní zisk',
    'CanGainCon'           => 'Umí prùbì¾ný zisk',
    'CanGainRel'           => 'Umí relativní zisk',
    'CanGain'              => 'Umí zisk',
    'CanIrisAbs'           => 'Umí absolutní iris',
    'CanIrisCon'           => 'Umí prùbì¾ný iris',
    'CanIrisRel'           => 'Umí relativní iris',
    'CanIris'              => 'Umí iris',
    'CanMoveAbs'           => 'Umí absoultní pohyb',
    'CanMoveCon'           => 'Umí prùbì¾ný pohyb',
    'CanMoveDiag'          => 'Umí diagonální pohyb',
    'CanMoveMap'           => 'Umí mapovaný pohyb',
    'CanMoveRel'           => 'Umí relativní pohyb',
    'CanMove'              => 'Umí pohyb',
    'CanPan'               => 'Umí otáèení',
    'CanReset'             => 'Umí reset',
    'CanSetPresets'        => 'Umí navolit pøedvolby',
    'CanSleep'             => 'Mù¾e spát',
    'CanTilt'              => 'Umí náklon',
    'CanWake'              => 'Lze vzbudit',
    'CanWhiteAbs'          => 'Umí absolutní vyvá¾ení bílé',
    'CanWhiteBal'          => 'Umí vyvá¾ení bílé',
    'CanWhiteCon'          => 'Umí prùbì¾né vyvá¾ení bílé',
    'CanWhiteRel'          => 'Umí relativní vyvá¾ení bílé',
    'CanWhite'             => 'Umí vyvá¾ení bílé',
    'CanZoomAbs'           => 'Umí absolutní zoom',
    'CanZoomCon'           => 'Umí prùbì¾ný zoom',
    'CanZoomRel'           => 'Umí relativní zoom',
    'CanZoom'              => 'Umí zoom',
    'CaptureHeight'        => 'Vý¹ka zdrojového snímku',
    'CapturePalette'       => 'Paleta zdrojového snímku',
    'CaptureWidth'         => '©íøka zdrojového snímku',
    'Cause'                => 'Pøíèina',
    'CheckMethod'          => 'Metoda znaèkování alarmem',
    'ChooseFilter'         => 'Vybrat filtr',
    'ChoosePreset'         => 'Choose Preset',
    'Close'                => 'Zavøít',
    'Colour'               => 'Barva',
    'Command'              => 'Pøíkaz',
    'Config'               => 'Nastavení',
    'ConfiguredFor'        => 'Nastaveno pro',
    'ConfirmDeleteEvents'  => 'Are you sure you wish to delete the selected events?',
    'ConfirmPassword'      => 'Potvrdit heslo',
    'ConjAnd'              => 'a',
    'ConjOr'               => 'nebo',
    'Console'              => 'Konzola',
    'ContactAdmin'         => 'Pro detailní info kontaktujte Va¹eho administrátora.',
    'Continue'             => 'Pokraèovat',
    'Contrast'             => 'Kontrast',
    'ControlAddress'       => 'Adresa øízení',
    'ControlCap'           => 'Schopnosti øízení',
    'ControlCaps'          => 'Typy øízení',
    'ControlDevice'        => 'Zaøízení øízení',
    'Controllable'         => 'Øíditelná',
    'ControlType'          => 'Typ øízení',
    'Control'              => 'Øízení',
    'Cycle'                => 'Cyklus',
    'CycleWatch'           => 'Cyklické prohlí¾ení',
    'Day'                  => 'Den',
    'Debug'                => 'Debug',
    'DefaultRate'          => 'Default Rate',
    'DefaultScale'         => 'Pøednastavená velikost',
    'DefaultView'          => 'Default View',
    'DeleteAndNext'        => 'Smazat &amp; Dal¹í',
    'DeleteAndPrev'        => 'Smazat &amp; Pøedchozí',
    'DeleteSavedFilter'    => 'Smazat filtr',
    'Delete'               => 'Smazat',
    'Description'          => 'Popis',
    'DeviceChannel'        => 'Kanál zaøízení',
    'DeviceFormat'         => 'Formát zaøízení',
    'DeviceNumber'         => 'Èíslo zarízení',
    'DevicePath'           => 'Cesta k zaøízení',
    'Devices'              => 'Devices',
    'Dimensions'           => 'Rozmìry',
    'DisableAlarms'        => 'Zakázat alarmy',
    'Disk'                 => 'Disk',
    'DonateAlready'        => 'Ne, u¾ jsem podpoøil',
    'DonateEnticement'     => 'Ji¾ nìjakou dobu pou¾íváte software ZoneMinder k ochranì svého majetku a pøedpokládám, ¾e jej shledáváte u¾iteèným. Pøesto¾e je ZoneMinder, znovu pøipomínám, zdarma a volnì ¹íøený software, stojí jeho vývoj a podpora nìjaké peníze. Pokud byste chtìl/a podpoøit budoucí vývoj a nové mo¾nosti softwaru, prosím zva¾te darování finanèní pomoci. Darování je, samozøejmì, dobrovolné, ale zato velmi cenìné mù¾ete pøispìt jakou èástkou chcete.<br><br>Pokud máte zájem podpoøit ná¹ tým, prosím, vyberte ní¾e uvedenou mo¾nost, nebo nav¹tivte http://www.zoneminder.com/donate.html.<br><br>Dìkuji Vám ¾e jste si vybral/a software ZoneMinder a nezapomeòte nav¹tívit fórum na ZoneMinder.com pro podporu a návrhy jak udìlat ZoneMinder je¹tì lep¹ím ne¾ je dnes.',
    'Donate'               => 'Prosím podpoøte',
    'DonateRemindDay'      => 'Nyní ne, pøipomenout za 1 den',
    'DonateRemindHour'     => 'Nyní ne, pøipomenout za hodinu',
    'DonateRemindMonth'    => 'Nyní ne, pøipomenout za mìsíc',
    'DonateRemindNever'    => 'Ne, nechci podpoøit ZoneMinder, nepøipomínat',
    'DonateRemindWeek'     => 'Nyní ne, pøipomenout za týden',
    'DonateYes'            => 'Ano, chcit podpoøit ZoneMinder nyní',
    'Download'             => 'Stáhnout',
    'Duration'             => 'Prùbìh',
    'Edit'                 => 'Editovat',
    'Email'                => 'Email',
    'EnableAlarms'         => 'Povolit alarmy',
    'Enabled'              => 'Povoleno',
    'EnterNewFilterName'   => 'Zadejte nové jméno filtru',
    'ErrorBrackets'        => 'Chyba, zkontrolujte prosím závorky',
    'Error'                => 'Chyba',
    'ErrorValidValue'      => 'Chyba, zkontrolujte ¾e podmínky mají správné hodnoty',
    'Etc'                  => 'atd',
    'EventFilter'          => 'Filtr záznamù',
    'EventId'              => 'Id záznamu',
    'EventName'            => 'Jméno záznamu',
    'EventPrefix'          => 'Prefix záznamu',
    'Events'               => 'Záznamy',
    'Event'                => 'Záznam',
    'Exclude'              => 'Vyjmout',
    'Execute'              => 'Execute',
    'ExportDetails'        => 'Exportovat detaily záznamu',
    'Export'               => 'Exportovat',
    'ExportFailed'         => 'Chyba pøi exportu',
    'ExportFormat'         => 'Formát exportovaného souboru',
    'ExportFormatTar'      => 'Tar',
    'ExportFormatZip'      => 'Zip',
    'ExportFrames'         => 'Exportovat detaily snímku',
    'ExportImageFiles'     => 'Exportovat obrazové soubory',
    'Exporting'            => 'Exportuji',
    'ExportMiscFiles'      => 'Exportovat ostatní soubory (jestli existují)',
    'ExportOptions'        => 'Mo¾nosti exportu',
    'ExportVideoFiles'     => 'Exportovat video soubory (jestli existují)',
    'Far'                  => 'Daleko',
    'FastForward'          => 'Fast Forward',
    'Feed'                 => 'Nasytit',
    'FileColours'          => 'Barvy souboru',
    'FilePath'             => 'Cesta k souboru',
    'File'                 => 'Soubor',
    'FilterArchiveEvents'  => 'Archivovat v¹echny nalezené',
    'FilterDeleteEvents'   => 'Smazat v¹echny nalezené',
    'FilterEmailEvents'    => 'Poslat email s detaily nalezených',
    'FilterExecuteEvents'  => 'Spustit pøíkaz na v¹ech nalezených',
    'FilterMessageEvents'  => 'Podat zprávu o v¹ech nalezených',
    'FilterPx'             => 'Filtr Px',
    'Filters'              => 'Filtry',
    'FilterUnset'          => 'You must specify a filter width and height',
    'FilterUploadEvents'   => 'Uploadovat nalezené',
    'FilterVideoEvents'    => 'Create video for all matches',
    'First'                => 'První',
    'FlippedHori'          => 'Pøeklopený vodorovnì',
    'FlippedVert'          => 'Pøeklopený svisle',
    'Focus'                => 'Zaostøení',
    'ForceAlarm'           => 'Spustit alarm',
    'Format'               => 'Formát',
    'FPS'                  => 'fps',
    'FPSReportInterval'    => 'FPS Interval pro report',
    'FrameId'              => 'Snímek Id',
    'FrameRate'            => 'Rychlost snímkù',
    'FrameSkip'            => 'Vynechat snímek',
    'Frame'                => 'Snímek',
    'Frames'               => 'Snímky',
    'FTP'                  => 'FTP',
    'Func'                 => 'Funkce',
    'Function'             => 'Funkce',
    'Gain'                 => 'Zisk',
    'General'              => 'General',
    'GenerateVideo'        => 'Generovat video',
    'GeneratingVideo'      => 'Generuji video',
    'GoToZoneMinder'       => 'Jít na ZoneMinder.com',
    'Grey'                 => '©edá',
    'Group'                => 'Group',
    'Groups'               => 'Skupiny',
    'HasFocusSpeed'        => 'Má rychlost zaostøení',
    'HasGainSpeed'         => 'Má rychlost zisku',
    'HasHomePreset'        => 'Má Home volbu',
    'HasIrisSpeed'         => 'Má rychlost irisu',
    'HasPanSpeed'          => 'Má rychlost otáèení',
    'HasPresets'           => 'Má pøedvolby',
    'HasTiltSpeed'         => 'Má rychlost náklonu',
    'HasTurboPan'          => 'Má Turbo otáèení',
    'HasTurboTilt'         => 'Má Turbo náklon',
    'HasWhiteSpeed'        => 'Má rychlost vyvá¾ení bílé',
    'HasZoomSpeed'         => 'Má rychlost zoomu',
    'HighBW'               => 'Rychlá&nbsp;B/W',
    'High'                 => 'Rychlá',
    'Home'                 => 'Domù',
    'Hour'                 => 'Hodina',
    'Hue'                  => 'Odstín',
    'Id'                   => 'Id',
    'Idle'                 => 'Pøipraven',
    'Ignore'               => 'Ignorovat',
    'ImageBufferSize'      => 'Velikost buferu snímkù',
    'Image'                => 'Obraz',
    'Images'               => 'Images',
    'Include'              => 'Vlo¾it',
    'In'                   => 'Dovnitø',
    'Inverted'             => 'Pøevrácenì',
    'Iris'                 => 'Iris',
    'KeyString'            => 'Key String',
    'Label'                => 'Label',
    'Language'             => 'Jazyk',
    'Last'                 => 'Poslední',
    'LimitResultsPost'     => 'výsledkù', // This is used at the end of the phrase 'Limit to first N results only'
    'LimitResultsPre'      => 'Zobrazit pouze prvních', // This is used at the beginning of the phrase 'Limit to first N results only'
    'LinkedMonitors'       => 'Linked Monitors',
    'List'                 => 'Seznam',
    'Load'                 => 'Load',
    'Local'                => 'Lokální',
    'LoggedInAs'           => 'Pøihlá¹en jako',
    'LoggingIn'            => 'Pøihla¹uji',
    'Login'                => 'Pøihlásit',
    'Logout'               => 'Odhlásit',
    'LowBW'                => 'Pomalá&nbsp;B/W',
    'Low'                  => 'Pomalá',
    'Main'                 => 'Hlavní',
    'Man'                  => 'Man',
    'Manual'               => 'Manuál',
    'Mark'                 => 'Oznaèit',
    'MaxBandwidth'         => 'Max bandwidth',
    'MaxBrScore'           => 'Max.<br/>skóre',
    'MaxFocusRange'        => 'Max rozsah zaostøení',
    'MaxFocusSpeed'        => 'Max rychlost zaostøení',
    'MaxFocusStep'         => 'Max krok zaostøení',
    'MaxGainRange'         => 'Max rozsah zisku',
    'MaxGainSpeed'         => 'Max rychlost zisku',
    'MaxGainStep'          => 'Max krok zisku',
    'MaximumFPS'           => 'Maximum FPS',
    'MaxIrisRange'         => 'Max rozsah iris',
    'MaxIrisSpeed'         => 'Max rychlost iris',
    'MaxIrisStep'          => 'Max krok iris',
    'Max'                  => 'Max',
    'MaxPanRange'          => 'Max rozsah otáèení',
    'MaxPanSpeed'          => 'Max rychlost otáèení',
    'MaxPanStep'           => 'Max krok otáèení',
    'MaxTiltRange'         => 'Max rozsah náklonu',
    'MaxTiltSpeed'         => 'Max rychlost náklonu',
    'MaxTiltStep'          => 'Max krok náklonu',
    'MaxWhiteRange'        => 'Max rozsah vyvá¾ení bílé',
    'MaxWhiteSpeed'        => 'Max rychlost vyvá¾ení bílé',
    'MaxWhiteStep'         => 'Max krok vyvá¾ení bílé',
    'MaxZoomRange'         => 'Max rozsah zoomu',
    'MaxZoomSpeed'         => 'Max rychlost zoomu',
    'MaxZoomStep'          => 'Max krok zoomu',
    'MediumBW'             => 'Støední&nbsp;B/W',
    'Medium'               => 'Støední',
    'MinAlarmAreaLtMax'    => 'Minimum alarm area should be less than maximum',
    'MinAlarmAreaUnset'    => 'You must specify the minimum alarm pixel count',
    'MinBlobAreaLtMax'     => 'Minimum znaèkované oblasti by mìlo být men¹í ne¾ maximum',
    'MinBlobAreaUnset'     => 'You must specify the minimum blob pixel count',
    'MinBlobLtMinFilter'   => 'Minimum blob area should be less than or equal to minimum filter area',
    'MinBlobsLtMax'        => 'Minimum znaèek by mìlo být men¹í ne¾ maximum',
    'MinBlobsUnset'        => 'You must specify the minimum blob count',
    'MinFilterAreaLtMax'   => 'Minimum filter area should be less than maximum',
    'MinFilterAreaUnset'   => 'You must specify the minimum filter pixel count',
    'MinFilterLtMinAlarm'  => 'Minimum filter area should be less than or equal to minimum alarm area',
    'MinFocusRange'        => 'Min rozsah zaostøení',
    'MinFocusSpeed'        => 'Min rychlost zaostøení',
    'MinFocusStep'         => 'Min krok zaostøení',
    'MinGainRange'         => 'Min rozsah zisku',
    'MinGainSpeed'         => 'Min rychlost zisku',
    'MinGainStep'          => 'Min krok zisku',
    'MinIrisRange'         => 'Min rozsah iris',
    'MinIrisSpeed'         => 'Min rychlost iris',
    'MinIrisStep'          => 'Min krok iris',
    'MinPanRange'          => 'Min rozsah otáèení',
    'MinPanSpeed'          => 'Min rychlost otáèení',
    'MinPanStep'           => 'Min krok otáèení',
    'MinPixelThresLtMax'   => 'Minimální práh pixelu by mìl být men¹í ne¾  maximumální',
    'MinPixelThresUnset'   => 'You must specify a minimum pixel threshold',
    'MinTiltRange'         => 'Min rozsah náklonu',
    'MinTiltSpeed'         => 'Min rychlost náklonu',
    'MinTiltStep'          => 'Min krok náklonu',
    'MinWhiteRange'        => 'Min rozsah vyvá¾ení bílé',
    'MinWhiteSpeed'        => 'Min rychlost vyvá¾ení bílé',
    'MinWhiteStep'         => 'Min krok vyvá¾ení bílé',
    'MinZoomRange'         => 'Min rozsah zoomu',
    'MinZoomSpeed'         => 'Min rychlost zoomu',
    'MinZoomStep'          => 'Min krok zoomu',
    'Misc'                 => 'Ostatní',
    'MonitorIds'           => 'Id&nbsp;kamer',
    'Monitor'              => 'Kamera',
    'MonitorPresetIntro'   => 'Select an appropriate preset from the list below.<br><br>Please note that this may overwrite any values you already have configured for this monitor.<br><br>',
    'MonitorPreset'        => 'Monitor Preset',
    'Monitors'             => 'Kamery',
    'Montage'              => 'Sestøih',
    'Month'                => 'Mìsíc',
    'Move'                 => 'Pohyb',
    'MustBeGe'             => 'musí být vìt¹í nebo rovno ne¾',
    'MustBeLe'             => 'musí být men¹í nebo rovno ne¾',
    'MustConfirmPassword'  => 'Musíte potvrdit heslo',
    'MustSupplyPassword'   => 'Musíte zadat heslo',
    'MustSupplyUsername'   => 'Musíte zadat u¾ivatelské jméno',
    'Name'                 => 'Jméno',
    'Near'                 => 'Blízko',
    'Network'              => 'Sí»',
    'NewGroup'             => 'Nová skupina',
    'NewLabel'             => 'New Label',
    'New'                  => 'Nový',
    'NewPassword'          => 'Nové heslo',
    'NewState'             => 'Nový stav',
    'NewUser'              => 'Nový u¾ivatel',
    'Next'                 => 'Dal¹í',
    'NoFramesRecorded'     => 'Pro tento snímek nejsou ¾ádné záznamy',
    'NoGroup'              => 'No Group',
    'No'                   => 'Ne',
    'NoneAvailable'        => '®ádná není dostupná',
    'None'                 => 'Zakázat',
    'Normal'               => 'Normalní',
    'NoSavedFilters'       => '®ádné ulo¾ené filtry',
    'NoStatisticsRecorded' => 'Pro tento záznam/snímek nejsou zaznamenány ¾ádné statistiky',
    'Notes'                => 'Poznámky',
    'NumPresets'           => 'Poèet pøedvoleb',
    'Off'                  => 'Off',
    'On'                   => 'On',
    'Open'                 => 'Otevøít',
    'OpEq'                 => 'rovno',
    'OpGtEq'               => 'vìt¹í nebo rovno',
    'OpGt'                 => 'vìt¹í',
    'OpIn'                 => 'nin set',
    'OpLtEq'               => 'men¹í nebo rovno',
    'OpLt'                 => 'men¹í',
    'OpMatches'            => 'obsahuje',
    'OpNe'                 => 'nerovná se',
    'OpNotIn'              => 'nnot in set',
    'OpNotMatches'         => 'neobsahuje',
    'OptionHelp'           => 'Mo¾nostHelp',
    'OptionRestartWarning' => 'Tyto zmìny se neprojeví\ndokud systém bì¾í. Jakmile\ndokonèíte provádìní zmìn prosím\nrestartujte ZoneMinder.',
    'Options'              => 'Mo¾nosti',
    'Order'                => 'Poøadí',
    'OrEnterNewName'       => 'nebo vlo¾te nové jméno',
    'Orientation'          => 'Orientace',
    'Out'                  => 'Ven',
    'OverwriteExisting'    => 'Pøepsat existující',
    'Paged'                => 'Strákovì',
    'PanLeft'              => 'Posunout vlevo',
    'Pan'                  => 'Otáèení',
    'PanRight'             => 'Posunout vpravo',
    'PanTilt'              => 'Otáèení/Náklon',
    'Parameter'            => 'Parametr',
    'Password'             => 'Heslo',
    'PasswordsDifferent'   => 'Hesla se neshodují',
    'Paths'                => 'Cesty',
    'Pause'                => 'Pause',
    'PhoneBW'              => 'Modem&nbsp;B/W',
    'Phone'                => 'Modem',
    'PixelDiff'            => 'Pixel Diff',
    'Pixels'               => 'pixely',
    'PlayAll'              => 'Pøehrát v¹e',
    'Play'                 => 'Play',
    'PleaseWait'           => 'Prosím èekejte',
    'Point'                => 'Point',
    'PostEventImageBuffer' => 'Pozáznamový bufer',
    'PreEventImageBuffer'  => 'Pøedzáznamový bufer',
    'PreserveAspect'       => 'Preserve Aspect Ratio',
    'Preset'               => 'Pøedvolba',
    'Presets'              => 'Pøedvolby',
    'Prev'                 => 'Zpìt',
    'Protocol'             => 'Protocol',
    'Rate'                 => 'Rychlost',
    'Real'                 => 'Skuteèná',
    'Record'               => 'Nahrávat',
    'RefImageBlendPct'     => 'Reference Image Blend %ge',
    'Refresh'              => 'Obnovit',
    'RemoteHostName'       => 'Adresa',
    'RemoteHostPath'       => 'Cesta',
    'RemoteHostPort'       => 'Port',
    'RemoteImageColours'   => 'Barvy',
    'Remote'               => 'Sí»ová',
    'Rename'               => 'Pøejmenovat',
    'ReplayAll'            => 'All Events',
    'ReplayGapless'        => 'Gapless Events',
    'Replay'               => 'Pøehrát znovu',
    'Replay'               => 'Replay',
    'ReplaySingle'         => 'Single Event',
    'ResetEventCounts'     => 'Resetovat poèty záznamù',
    'Reset'                => 'Reset',
    'Restarting'           => 'Restartuji',
    'Restart'              => 'Restartovat',
    'RestrictedCameraIds'  => 'Povolené id kamer',
    'RestrictedMonitors'   => 'Restricted Monitors',
    'ReturnDelay'          => 'Prodleva vracení',
    'ReturnLocation'       => 'Lokace vrácení',
    'Rewind'               => 'Rewind',
    'RotateLeft'           => 'Otoèit vlevo',
    'RotateRight'          => 'Otoèit vpravo',
    'RunMode'              => 'Re¾im',
    'Running'              => 'Bì¾í',
    'RunState'             => 'Stav',
    'SaveAs'               => 'Ulo¾it jako',
    'SaveFilter'           => 'Ulo¾it filtr',
    'Save'                 => 'Ulo¾it',
    'Scale'                => 'Velikost',
    'Score'                => 'Skóre',
    'Secs'                 => 'Délka(s)',
    'Sectionlength'        => 'Délka sekce',
    'SelectMonitors'       => 'Select Monitors',
    'Select'               => 'Vybrat',
    'SelfIntersecting'     => 'Polygon edges must not intersect',
    'Set'                  => 'Nastavit',
    'SetNewBandwidth'      => 'Nastavit novou rychlost sítì',
    'SetPreset'            => 'Nastavit pøedvolbu',
    'Settings'             => 'Nastavení',
    'ShowFilterWindow'     => 'Zobrazit filtr',
    'ShowTimeline'         => 'Zobrazit èasovou linii ',
    'SignalCheckColour'    => 'Signal Check Colour',
    'Size'                 => 'Velikost',
    'Sleep'                => 'Spát',
    'SortAsc'              => 'Vzestupnì',
    'SortBy'               => 'Øadit dle',
    'SortDesc'             => 'Sestupnì',
    'SourceType'           => 'Typ zdroje',
    'Source'               => 'Zdroj',
    'SpeedHigh'            => 'Vysoká rychlost',
    'SpeedLow'             => 'Nízká rychlost',
    'SpeedMedium'          => 'Støední rychlost',
    'Speed'                => 'Rychlost',
    'SpeedTurbo'           => 'Turbo rychlost',
    'Start'                => 'Start',
    'State'                => 'Stav',
    'Stats'                => 'Statistiky',
    'Status'               => 'Status',
    'StepBack'             => 'Step Back',
    'StepForward'          => 'Step Forward',
    'Step'                 => 'Krok',
    'StepLarge'            => 'Velký krok',
    'StepMedium'           => 'Støední krok',
    'StepNone'             => '®ádný krok',
    'StepSmall'            => 'Malý krok',
    'Stills'               => 'Snímky',
    'Stopped'              => 'Zastaven',
    'Stop'                 => 'Zastavit',
    'StreamReplayBuffer'   => 'Stream Replay Image Buffer',
    'Stream'               => 'Stream',
    'Submit'               => 'Potvrdit',
    'System'               => 'System',
    'Tele'                 => 'Pøiblí¾it',
    'Thumbnail'            => 'Miniatura',
    'Tilt'                 => 'Náklon',
    'Time'                 => 'Èas',
    'TimeDelta'            => 'Delta èasu',
    'Timeline'             => 'Èasová linie',
    'TimeStamp'            => 'Èasové razítko',
    'TimestampLabelFormat' => 'Formát èasového razítka',
    'TimestampLabelX'      => 'Èasové razítko X',
    'TimestampLabelY'      => 'Èasové razítko Y',
    'Timestamp'            => 'Razítko',
    'Today'                => 'Dnes',
    'Tools'                => 'Nástroje',
    'TotalBrScore'         => 'Celkové<br/>skóre',
    'TrackDelay'           => 'Prodleva dráhy',
    'TrackMotion'          => 'Pohyb po dráze',
    'Triggers'             => 'Trigery',
    'TurboPanSpeed'        => 'Rychlost Turbo otáèení',
    'TurboTiltSpeed'       => 'Rychlost Turbo náklonu',
    'Type'                 => 'Typ',
    'Unarchive'            => 'Vyjmout z archivu',
    'Units'                => 'Jednotky',
    'Unknown'              => 'Neznámý',
    'UpdateAvailable'      => 'Je dostupný nový update ZoneMinder.',
    'UpdateNotNecessary'   => 'Update není potøeba.',
    'Update'               => 'Update',
    'UseFilterExprsPost'   => '&nbsp;výrazù', // This is used at the end of the phrase 'use N filter expressions'
    'UseFilterExprsPre'    => 'Pou¾ít&nbsp;', // This is used at the beginning of the phrase 'use N filter expressions'
    'UseFilter'            => 'Pou¾ít filtr',
    'Username'             => 'U¾ivatelské jméno',
    'Users'                => 'U¾ivatelé',
    'User'                 => 'U¾ivatel',
    'Value'                => 'Hodnota',
    'VersionIgnore'        => 'Ignorovat tuto verzi',
    'VersionRemindDay'     => 'Pøipomenout za 1 den',
    'VersionRemindHour'    => 'Pøipomenout za hodinu',
    'VersionRemindNever'   => 'Nepøipomínat nové veze',
    'VersionRemindWeek'    => 'Pøipomenout za týden',
    'Version'              => 'Verze',
    'VideoFormat'          => 'Video formát',
    'VideoGenFailed'       => 'Chyba pøi generování videa!',
    'VideoGenFiles'        => 'Existující video soubory',
    'VideoGenNoFiles'      => '®ádné video soubory nenalezeny',
    'VideoGenParms'        => 'Parametry generování videa',
    'VideoGenSucceeded'    => 'Video vygenerováno úspì¹nì!',
    'VideoSize'            => 'Velikost videa',
    'Video'                => 'Video',
    'ViewAll'              => 'Zobrazit v¹echny',
    'ViewEvent'            => 'Zobrazit záznam',
    'ViewPaged'            => 'Zobrazit strákovì',
    'View'                 => 'Zobrazit',
    'Wake'                 => 'Vzbudit',
    'WarmupFrames'         => 'Zahøívací snímky',
    'Watch'                => 'Sledovat',
    'WebColour'            => 'Webová barva',
    'Web'                  => 'Web',
    'Week'                 => 'Týden',
    'WhiteBalance'         => 'Vyvá¾ení bílé',
    'White'                => 'Bílá',
    'Wide'                 => 'Oddálit',
    'X10ActivationString'  => 'X10 aktivaèní øetìzec',
    'X10InputAlarmString'  => 'X10 input alarm øetìzec',
    'X10OutputAlarmString' => 'X10 output alarm øetìzec',
    'X10'                  => 'X10',
    'X'                    => 'X',
    'Yes'                  => 'Ano',
    'YouNoPerms'           => 'K tomuto zdroji nemáte oprávnìní.',
    'Y'                    => 'Y',
    'ZoneAlarmColour'      => 'Barva alarmu (Red/Green/Blue)',
    'ZoneArea'             => 'Zone Area',
    'ZoneFilterSize'       => 'Filter Width/Height (pixels)',
    'ZoneMinMaxAlarmArea'  => 'Min/Max Alarmed Area',
    'ZoneMinMaxBlobArea'   => 'Min/Max Blob Area',
    'ZoneMinMaxBlobs'      => 'Min/Max Blobs',
    'ZoneMinMaxFiltArea'   => 'Min/Max Filtered Area',
    'ZoneMinMaxPixelThres' => 'Min/Max Pixel Threshold (0-255)',
    'ZoneOverloadFrames'   => 'Overload Frame Ignore Count',
    'Zones'                => 'Zóny',
    'Zone'                 => 'Zóna',
    'ZoomIn'               => 'Zvìt¹it',
    'ZoomOut'              => 'Zmen¹it',
    'Zoom'                 => 'Zoom',
);

// Complex replacements with formatting and/or placements, must be passed through sprintf
$CLANG = array(
    'CurrentLogin'         => 'Právì je pøihlá¹en \'%1$s\'',
    'EventCount'           => '%1$s %2$s', // For example '37 Events' (from Vlang below)
    'LastEvents'           => 'Posledních %1$s %2$s', // For example 'Last 37 Events' (from Vlang below)
    'LatestRelease'        => 'Poslední verze je v%1$s, vy máte v%2$s.',
    'MonitorCount'         => '%1$s %2$s', // For example '4 Monitors' (from Vlang below)
    'MonitorFunction'      => 'Funkce %1$s kamery',
    'RunningRecentVer'     => 'Pou¾íváte poslední verzi ZoneMinder, v%s.',
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
    'Event'                => array( 0=>'Záznamù', 1=>'Záznam', 2=>'Záznamy', 5=>'Záznamù' ),
    'Monitor'              => array( 0=>'Kamer', 1=>'Kamera', 2=>'Kamery', 5=>'Kamer' ),
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
