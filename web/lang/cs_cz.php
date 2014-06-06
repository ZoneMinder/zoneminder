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
    '32BitColour'          => '32 bit barevná',          // Added - 2011-06-15
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
    'Apply'                => 'Pou¾ít',
    'ApplyingStateChange'  => 'Aplikuji zmìnu stavu',
    'ArchArchived'         => 'Pouze archivované',
    'ArchUnarchived'       => 'Pouze nearchivované',
    'Archive'              => 'Archiv',
    'Archived'             => 'Archivován',
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
    'Available'            => 'Available',              // Added - 2009-03-31
    'AvgBrScore'           => 'Prùm.<br/>Skóre',
    'Background'           => 'Background',
    'BackgroundFilter'     => 'Run filter in background',
    'BadAlarmFrameCount'   => 'Alarm frame count must be an integer of one or more',
    'BadAlarmMaxFPS'       => 'Alarm Maximum FPS must be a positive integer or floating point value',
    'BadChannel'           => 'Channel must be set to an integer of zero or more',
    'BadColours'           => 'Target colour must be set to a valid value', // Added - 2011-06-15
    'BadDevice'            => 'Device must be set to a valid value',
    'BadFPSReportInterval' => 'FPS report interval buffer count must be an integer of 0 or more',
    'BadFormat'            => 'Format must be set to an integer of zero or more',
    'BadFrameSkip'         => 'Frame skip count must be an integer of zero or more',
    'BadMotionFrameSkip'   => 'Motion Frame skip count must be an integer of zero or more',
    'BadHeight'            => 'Height must be set to a valid value',
    'BadHost'              => 'Host must be set to a valid ip address or hostname, do not include http://',
    'BadImageBufferCount'  => 'Image buffer size must be an integer of 10 or more',
    'BadLabelX'            => 'Label X co-ordinate must be set to an integer of zero or more',
    'BadLabelY'            => 'Label Y co-ordinate must be set to an integer of zero or more',
    'BadMaxFPS'            => 'Maximum FPS must be a positive integer or floating point value',
    'BadNameChars'         => 'Jména moho obsahovat pouze alfanumerické znaky a podtr¾ítko èi pomlèku',
    'BadPalette'           => 'Palette must be set to a valid value', // Added - 2009-03-31
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
    'BandwidthHead'        => 'Bandwidth',	// This is the end of the bandwidth status on the top of the console, different in many language due to phrasing
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
    'CanFocus'             => 'Umí zaostøit',
    'CanFocusAbs'          => 'Umí zaostøit absolutnì',
    'CanFocusCon'          => 'Umí prùbì¾nì zaostøit',
    'CanFocusRel'          => 'Umí relativnì zaostøit',
    'CanGain'              => 'Umí zisk',
    'CanGainAbs'           => 'Umí absolutní zisk',
    'CanGainCon'           => 'Umí prùbì¾ný zisk',
    'CanGainRel'           => 'Umí relativní zisk',
    'CanIris'              => 'Umí iris',
    'CanIrisAbs'           => 'Umí absolutní iris',
    'CanIrisCon'           => 'Umí prùbì¾ný iris',
    'CanIrisRel'           => 'Umí relativní iris',
    'CanMove'              => 'Umí pohyb',
    'CanMoveAbs'           => 'Umí absoultní pohyb',
    'CanMoveCon'           => 'Umí prùbì¾ný pohyb',
    'CanMoveDiag'          => 'Umí diagonální pohyb',
    'CanMoveMap'           => 'Umí mapovaný pohyb',
    'CanMoveRel'           => 'Umí relativní pohyb',
    'CanPan'               => 'Umí otáèení',
    'CanReset'             => 'Umí reset',
    'CanSetPresets'        => 'Umí navolit pøedvolby',
    'CanSleep'             => 'Mù¾e spát',
    'CanTilt'              => 'Umí náklon',
    'CanWake'              => 'Lze vzbudit',
    'CanWhite'             => 'Umí vyvá¾ení bílé',
    'CanWhiteAbs'          => 'Umí absolutní vyvá¾ení bílé',
    'CanWhiteBal'          => 'Umí vyvá¾ení bílé',
    'CanWhiteCon'          => 'Umí prùbì¾né vyvá¾ení bílé',
    'CanWhiteRel'          => 'Umí relativní vyvá¾ení bílé',
    'CanZoom'              => 'Umí zoom',
    'CanZoomAbs'           => 'Umí absolutní zoom',
    'CanZoomCon'           => 'Umí prùbì¾ný zoom',
    'CanZoomRel'           => 'Umí relativní zoom',
    'Cancel'               => 'Zru¹it',
    'CancelForcedAlarm'    => 'Zastavit spu¹tìný alarm',
    'CaptureHeight'        => 'Vý¹ka zdrojového snímku',
    'CaptureMethod'        => 'Capture Method',         // Added - 2009-02-08
    'CapturePalette'       => 'Paleta zdrojového snímku',
    'CaptureWidth'         => '©íøka zdrojového snímku',
    'Cause'                => 'Pøíèina',
    'CheckMethod'          => 'Metoda znaèkování alarmem',
    'ChooseDetectedCamera' => 'Choose Detected Camera', // Added - 2009-03-31
    'ChooseFilter'         => 'Vybrat filtr',
    'ChooseLogFormat'      => 'Choose a log format',    // Added - 2011-06-17
    'ChooseLogSelection'   => 'Choose a log selection', // Added - 2011-06-17
    'ChoosePreset'         => 'Choose Preset',
    'Clear'                => 'Clear',                  // Added - 2011-06-16
    'Close'                => 'Zavøít',
    'Colour'               => 'Barva',
    'Command'              => 'Pøíkaz',
    'Component'            => 'Component',              // Added - 2011-06-16
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
    'Control'              => 'Øízení',
    'ControlAddress'       => 'Adresa øízení',
    'ControlCap'           => 'Schopnosti øízení',
    'ControlCaps'          => 'Typy øízení',
    'ControlDevice'        => 'Zaøízení øízení',
    'ControlType'          => 'Typ øízení',
    'Controllable'         => 'Øíditelná',
    'Cycle'                => 'Cyklus',
    'CycleWatch'           => 'Cyklické prohlí¾ení',
    'DateTime'             => 'Date/Time',              // Added - 2011-06-16
    'Day'                  => 'Den',
    'Debug'                => 'Debug',
    'DefaultRate'          => 'Default Rate',
    'DefaultScale'         => 'Pøednastavená velikost',
    'DefaultView'          => 'Default View',
    'Delete'               => 'Smazat',
    'DeleteAndNext'        => 'Smazat &amp; Dal¹í',
    'DeleteAndPrev'        => 'Smazat &amp; Pøedchozí',
    'DeleteSavedFilter'    => 'Smazat filtr',
    'Description'          => 'Popis',
    'DetectedCameras'      => 'Detected Cameras',       // Added - 2009-03-31
    'Device'               => 'Device',                 // Added - 2009-02-08
    'DeviceChannel'        => 'Kanál zaøízení',
    'DeviceFormat'         => 'Formát zaøízení',
    'DeviceNumber'         => 'Èíslo zarízení',
    'DevicePath'           => 'Cesta k zaøízení',
    'Devices'              => 'Devices',
    'Dimensions'           => 'Rozmìry',
    'DisableAlarms'        => 'Zakázat alarmy',
    'Disk'                 => 'Disk',
    'Display'              => 'Display',                // Added - 2011-01-30
    'Displaying'           => 'Displaying',             // Added - 2011-06-16
    'Donate'               => 'Prosím podpoøte',
    'DonateAlready'        => 'Ne, u¾ jsem podpoøil',
    'DonateEnticement'     => 'Ji¾ nìjakou dobu pou¾íváte software ZoneMinder k ochranì svého majetku a pøedpokládám, ¾e jej shledáváte u¾iteèným. Pøesto¾e je ZoneMinder, znovu pøipomínám, zdarma a volnì ¹íøený software, stojí jeho vývoj a podpora nìjaké peníze. Pokud byste chtìl/a podpoøit budoucí vývoj a nové mo¾nosti softwaru, prosím zva¾te darování finanèní pomoci. Darování je, samozøejmì, dobrovolné, ale zato velmi cenìné mù¾ete pøispìt jakou èástkou chcete.<br><br>Pokud máte zájem podpoøit ná¹ tým, prosím, vyberte ní¾e uvedenou mo¾nost, nebo nav¹tivte http://www.zoneminder.com/donate.html.<br><br>Dìkuji Vám ¾e jste si vybral/a software ZoneMinder a nezapomeòte nav¹tívit fórum na ZoneMinder.com pro podporu a návrhy jak udìlat ZoneMinder je¹tì lep¹ím ne¾ je dnes.',
    'DonateRemindDay'      => 'Nyní ne, pøipomenout za 1 den',
    'DonateRemindHour'     => 'Nyní ne, pøipomenout za hodinu',
    'DonateRemindMonth'    => 'Nyní ne, pøipomenout za mìsíc',
    'DonateRemindNever'    => 'Ne, nechci podpoøit ZoneMinder, nepøipomínat',
    'DonateRemindWeek'     => 'Nyní ne, pøipomenout za týden',
    'DonateYes'            => 'Ano, chcit podpoøit ZoneMinder nyní',
    'DoNativeMotionDetection'=> 'Do Native Motion Detection',
    'Download'             => 'Stáhnout',
    'DuplicateMonitorName' => 'Duplicate Monitor Name', // Added - 2009-03-31
    'Duration'             => 'Prùbìh',
    'Edit'                 => 'Editovat',
    'Email'                => 'Email',
    'EnableAlarms'         => 'Povolit alarmy',
    'Enabled'              => 'Povoleno',
    'EnterNewFilterName'   => 'Zadejte nové jméno filtru',
    'Error'                => 'Chyba',
    'ErrorBrackets'        => 'Chyba, zkontrolujte prosím závorky',
    'ErrorValidValue'      => 'Chyba, zkontrolujte ¾e podmínky mají správné hodnoty',
    'Etc'                  => 'atd',
    'Event'                => 'Záznam',
    'EventFilter'          => 'Filtr záznamù',
    'EventId'              => 'Id záznamu',
    'EventName'            => 'Jméno záznamu',
    'EventPrefix'          => 'Prefix záznamu',
    'Events'               => 'Záznamy',
    'Exclude'              => 'Vyjmout',
    'Execute'              => 'Execute',
    'Export'               => 'Exportovat',
    'ExportDetails'        => 'Exportovat detaily záznamu',
    'ExportFailed'         => 'Chyba pøi exportu',
    'ExportFormat'         => 'Formát exportovaného souboru',
    'ExportFormatTar'      => 'Tar',
    'ExportFormatZip'      => 'Zip',
    'ExportFrames'         => 'Exportovat detaily snímku',
    'ExportImageFiles'     => 'Exportovat obrazové soubory',
    'ExportLog'            => 'Export Log',             // Added - 2011-06-17
    'ExportMiscFiles'      => 'Exportovat ostatní soubory (jestli existují)',
    'ExportOptions'        => 'Mo¾nosti exportu',
    'ExportSucceeded'      => 'Export Succeeded',       // Added - 2009-02-08
    'ExportVideoFiles'     => 'Exportovat video soubory (jestli existují)',
    'Exporting'            => 'Exportuji',
    'FPS'                  => 'fps',
    'FPSReportInterval'    => 'FPS Interval pro report',
    'FTP'                  => 'FTP',
    'Far'                  => 'Daleko',
    'FastForward'          => 'Fast Forward',
    'Feed'                 => 'Nasytit',
    'Ffmpeg'               => 'Ffmpeg',                 // Added - 2009-02-08
    'File'                 => 'Soubor',
    'FilterArchiveEvents'  => 'Archivovat v¹echny nalezené',
    'FilterDeleteEvents'   => 'Smazat v¹echny nalezené',
    'FilterEmailEvents'    => 'Poslat email s detaily nalezených',
    'FilterExecuteEvents'  => 'Spustit pøíkaz na v¹ech nalezených',
    'FilterMessageEvents'  => 'Podat zprávu o v¹ech nalezených',
    'FilterPx'             => 'Filtr Px',
    'FilterUnset'          => 'You must specify a filter width and height',
    'FilterUploadEvents'   => 'Uploadovat nalezené',
    'FilterVideoEvents'    => 'Create video for all matches',
    'Filters'              => 'Filtry',
    'First'                => 'První',
    'FlippedHori'          => 'Pøeklopený vodorovnì',
    'FlippedVert'          => 'Pøeklopený svisle',
    'FnNone'                => 'None',            // Added 2013.08.16.
    'FnMonitor'             => 'Monitor',            // Added 2013.08.16.
    'FnModect'              => 'Modect',            // Added 2013.08.16.
    'FnRecord'              => 'Record',            // Added 2013.08.16.
    'FnMocord'              => 'Mocord',            // Added 2013.08.16.
    'FnNodect'              => 'Nodect',            // Added 2013.08.16.
    'Focus'                => 'Zaostøení',
    'ForceAlarm'           => 'Spustit alarm',
    'Format'               => 'Formát',
    'Frame'                => 'Snímek',
    'FrameId'              => 'Snímek Id',
    'FrameRate'            => 'Rychlost snímkù',
    'FrameSkip'            => 'Vynechat snímek',
    'MotionFrameSkip'      => 'Motion Frame Skip',
    'Frames'               => 'Snímky',
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
    'High'                 => 'Rychlá',
    'HighBW'               => 'Rychlá&nbsp;B/W',
    'Home'                 => 'Domù',
    'Hour'                 => 'Hodina',
    'Hue'                  => 'Odstín',
    'Id'                   => 'Id',
    'Idle'                 => 'Pøipraven',
    'Ignore'               => 'Ignorovat',
    'Image'                => 'Obraz',
    'ImageBufferSize'      => 'Velikost buferu snímkù',
    'Images'               => 'Images',
    'In'                   => 'Dovnitø',
    'Include'              => 'Vlo¾it',
    'Inverted'             => 'Pøevrácenì',
    'Iris'                 => 'Iris',
    'KeyString'            => 'Key String',
    'Label'                => 'Label',
    'Language'             => 'Jazyk',
    'Last'                 => 'Poslední',
    'Layout'               => 'Layout',                 // Added - 2009-02-08
    'Level'                => 'Level',                  // Added - 2011-06-16
    'Libvlc'               => 'Libvlc',
    'LimitResultsPost'     => 'výsledkù', // This is used at the end of the phrase 'Limit to first N results only'
    'LimitResultsPre'      => 'Zobrazit pouze prvních', // This is used at the beginning of the phrase 'Limit to first N results only'
    'Line'                 => 'Line',                   // Added - 2011-06-16
    'LinkedMonitors'       => 'Linked Monitors',
    'List'                 => 'Seznam',
    'Load'                 => 'Load',
    'Local'                => 'Lokální',
    'Log'                  => 'Log',                    // Added - 2011-06-16
    'LoggedInAs'           => 'Pøihlá¹en jako',
    'Logging'              => 'Logging',                // Added - 2011-06-16
    'LoggingIn'            => 'Pøihla¹uji',
    'Login'                => 'Pøihlásit',
    'Logout'               => 'Odhlásit',
    'Logs'                 => 'Logs',                   // Added - 2011-06-17
    'Low'                  => 'Pomalá',
    'LowBW'                => 'Pomalá&nbsp;B/W',
    'Main'                 => 'Hlavní',
    'Man'                  => 'Man',
    'Manual'               => 'Manuál',
    'Mark'                 => 'Oznaèit',
    'Max'                  => 'Max',
    'MaxBandwidth'         => 'Max bandwidth',
    'MaxBrScore'           => 'Max.<br/>skóre',
    'MaxFocusRange'        => 'Max rozsah zaostøení',
    'MaxFocusSpeed'        => 'Max rychlost zaostøení',
    'MaxFocusStep'         => 'Max krok zaostøení',
    'MaxGainRange'         => 'Max rozsah zisku',
    'MaxGainSpeed'         => 'Max rychlost zisku',
    'MaxGainStep'          => 'Max krok zisku',
    'MaxIrisRange'         => 'Max rozsah iris',
    'MaxIrisSpeed'         => 'Max rychlost iris',
    'MaxIrisStep'          => 'Max krok iris',
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
    'MaximumFPS'           => 'Maximum FPS',
    'Medium'               => 'Støední',
    'MediumBW'             => 'Støední&nbsp;B/W',
    'Message'              => 'Message',                // Added - 2011-06-16
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
    'Monitor'              => 'Kamera',
    'MonitorIds'           => 'Id&nbsp;kamer',
    'MonitorPreset'        => 'Monitor Preset',
    'MonitorPresetIntro'   => 'Select an appropriate preset from the list below.<br><br>Please note that this may overwrite any values you already have configured for this monitor.<br><br>',
    'MonitorProbe'         => 'Monitor Probe',          // Added - 2009-03-31
    'MonitorProbeIntro'    => 'The list below shows detected analog and network cameras and whether they are already being used or available for selection.<br/><br/>Select the desired entry from the list below.<br/><br/>Please note that not all cameras may be detected and that choosing a camera here may overwrite any values you already have configured for the current monitor.<br/><br/>', // Added - 2009-03-31
    'Monitors'             => 'Kamery',
    'Montage'              => 'Sestøih',
    'Month'                => 'Mìsíc',
    'More'                 => 'More',                   // Added - 2011-06-16
    'Move'                 => 'Pohyb',
    'MtgDefault'           => 'Default',              // Added 2013.08.15.
    'Mtg2widgrd'           => '2-wide grid',              // Added 2013.08.15.
    'Mtg3widgrd'           => '3-wide grid',              // Added 2013.08.15.
    'Mtg4widgrd'           => '4-wide grid',              // Added 2013.08.15.
    'Mtg3widgrx'           => '3-wide grid, scaled, enlarge on alarm',              // Added 2013.08.15.
    'MustBeGe'             => 'musí být vìt¹í nebo rovno ne¾',
    'MustBeLe'             => 'musí být men¹í nebo rovno ne¾',
    'MustConfirmPassword'  => 'Musíte potvrdit heslo',
    'MustSupplyPassword'   => 'Musíte zadat heslo',
    'MustSupplyUsername'   => 'Musíte zadat u¾ivatelské jméno',
    'Name'                 => 'Jméno',
    'Near'                 => 'Blízko',
    'Network'              => 'Sí»',
    'New'                  => 'Nový',
    'NewGroup'             => 'Nová skupina',
    'NewLabel'             => 'New Label',
    'NewPassword'          => 'Nové heslo',
    'NewState'             => 'Nový stav',
    'NewUser'              => 'Nový u¾ivatel',
    'Next'                 => 'Dal¹í',
    'No'                   => 'Ne',
    'NoDetectedCameras'    => 'No Detected Cameras',    // Added - 2009-03-31
    'NoFramesRecorded'     => 'Pro tento snímek nejsou ¾ádné záznamy',
    'NoGroup'              => 'No Group',
    'NoSavedFilters'       => '®ádné ulo¾ené filtry',
    'NoStatisticsRecorded' => 'Pro tento záznam/snímek nejsou zaznamenány ¾ádné statistiky',
    'None'                 => 'Zakázat',
    'NoneAvailable'        => '®ádná není dostupná',
    'Normal'               => 'Normalní',
    'Notes'                => 'Poznámky',
    'NumPresets'           => 'Poèet pøedvoleb',
    'Off'                  => 'Off',
    'On'                   => 'On',
    'OpEq'                 => 'rovno',
    'OpGt'                 => 'vìt¹í',
    'OpGtEq'               => 'vìt¹í nebo rovno',
    'OpIn'                 => 'nin set',
    'OpLt'                 => 'men¹í',
    'OpLtEq'               => 'men¹í nebo rovno',
    'OpMatches'            => 'obsahuje',
    'OpNe'                 => 'nerovná se',
    'OpNotIn'              => 'nnot in set',
    'OpNotMatches'         => 'neobsahuje',
    'Open'                 => 'Otevøít',
    'OptionHelp'           => 'Mo¾nostHelp',
    'OptionRestartWarning' => 'Tyto zmìny se neprojeví\ndokud systém bì¾í. Jakmile\ndokonèíte provádìní zmìn prosím\nrestartujte ZoneMinder.',
    'Options'              => 'Mo¾nosti',
    'OrEnterNewName'       => 'nebo vlo¾te nové jméno',
    'Order'                => 'Poøadí',
    'Orientation'          => 'Orientace',
    'Out'                  => 'Ven',
    'OverwriteExisting'    => 'Pøepsat existující',
    'Paged'                => 'Strákovì',
    'Pan'                  => 'Otáèení',
    'PanLeft'              => 'Posunout vlevo',
    'PanRight'             => 'Posunout vpravo',
    'PanTilt'              => 'Otáèení/Náklon',
    'Parameter'            => 'Parametr',
    'Password'             => 'Heslo',
    'PasswordsDifferent'   => 'Hesla se neshodují',
    'Paths'                => 'Cesty',
    'Pause'                => 'Pause',
    'Phone'                => 'Modem',
    'PhoneBW'              => 'Modem&nbsp;B/W',
    'Pid'                  => 'PID',                    // Added - 2011-06-16
    'PixelDiff'            => 'Pixel Diff',
    'Pixels'               => 'pixely',
    'Play'                 => 'Play',
    'PlayAll'              => 'Pøehrát v¹e',
    'PleaseWait'           => 'Prosím èekejte',
    'Plugins'              => 'Plugins',
    'Point'                => 'Point',
    'PostEventImageBuffer' => 'Pozáznamový bufer',
    'PreEventImageBuffer'  => 'Pøedzáznamový bufer',
    'PreserveAspect'       => 'Preserve Aspect Ratio',
    'Preset'               => 'Pøedvolba',
    'Presets'              => 'Pøedvolby',
    'Prev'                 => 'Zpìt',
    'Probe'                => 'Probe',                  // Added - 2009-03-31
    'Protocol'             => 'Protocol',
    'Rate'                 => 'Rychlost',
    'Real'                 => 'Skuteèná',
    'Record'               => 'Nahrávat',
    'RefImageBlendPct'     => 'Reference Image Blend %ge',
    'Refresh'              => 'Obnovit',
    'Remote'               => 'Sí»ová',
    'RemoteHostName'       => 'Adresa',
    'RemoteHostPath'       => 'Cesta',
    'RemoteHostPort'       => 'Port',
    'RemoteHostSubPath'    => 'Remote Host SubPath',    // Added - 2009-02-08
    'RemoteImageColours'   => 'Barvy',
    'RemoteMethod'         => 'Remote Method',          // Added - 2009-02-08
    'RemoteProtocol'       => 'Remote Protocol',        // Added - 2009-02-08
    'Rename'               => 'Pøejmenovat',
    'Replay'               => 'Replay',
    'ReplayAll'            => 'All Events',
    'ReplayGapless'        => 'Gapless Events',
    'ReplaySingle'         => 'Single Event',
    'Reset'                => 'Reset',
    'ResetEventCounts'     => 'Resetovat poèty záznamù',
    'Restart'              => 'Restartovat',
    'Restarting'           => 'Restartuji',
    'RestrictedCameraIds'  => 'Povolené id kamer',
    'RestrictedMonitors'   => 'Restricted Monitors',
    'ReturnDelay'          => 'Prodleva vracení',
    'ReturnLocation'       => 'Lokace vrácení',
    'Rewind'               => 'Rewind',
    'RotateLeft'           => 'Otoèit vlevo',
    'RotateRight'          => 'Otoèit vpravo',
    'RunLocalUpdate'       => 'Please run zmupdate.pl to update', // Added - 2011-05-25
    'RunMode'              => 'Re¾im',
    'RunState'             => 'Stav',
    'Running'              => 'Bì¾í',
    'Save'                 => 'Ulo¾it',
    'SaveAs'               => 'Ulo¾it jako',
    'SaveFilter'           => 'Ulo¾it filtr',
    'Scale'                => 'Velikost',
    'Score'                => 'Skóre',
    'Secs'                 => 'Délka(s)',
    'Sectionlength'        => 'Délka sekce',
    'Select'               => 'Vybrat',
    'SelectFormat'         => 'Select Format',          // Added - 2011-06-17
    'SelectLog'            => 'Select Log',             // Added - 2011-06-17
    'SelectMonitors'       => 'Select Monitors',
    'SelfIntersecting'     => 'Polygon edges must not intersect',
    'Set'                  => 'Nastavit',
    'SetNewBandwidth'      => 'Nastavit novou rychlost sítì',
    'SetPreset'            => 'Nastavit pøedvolbu',
    'Settings'             => 'Nastavení',
    'ShowFilterWindow'     => 'Zobrazit filtr',
    'ShowTimeline'         => 'Zobrazit èasovou linii ',
    'SignalCheckColour'    => 'Signal Check Colour',
    'Size'                 => 'Velikost',
    'SkinDescription'      => 'Change the default skin for this computer', // Added - 2011-01-30
    'Sleep'                => 'Spát',
    'SortAsc'              => 'Vzestupnì',
    'SortBy'               => 'Øadit dle',
    'SortDesc'             => 'Sestupnì',
    'Source'               => 'Zdroj',
    'SourceColours'        => 'Source Colours',         // Added - 2009-02-08
    'SourcePath'           => 'Source Path',            // Added - 2009-02-08
    'SourceType'           => 'Typ zdroje',
    'Speed'                => 'Rychlost',
    'SpeedHigh'            => 'Vysoká rychlost',
    'SpeedLow'             => 'Nízká rychlost',
    'SpeedMedium'          => 'Støední rychlost',
    'SpeedTurbo'           => 'Turbo rychlost',
    'Start'                => 'Start',
    'State'                => 'Stav',
    'Stats'                => 'Statistiky',
    'Status'               => 'Status',
    'Step'                 => 'Krok',
    'StepBack'             => 'Step Back',
    'StepForward'          => 'Step Forward',
    'StepLarge'            => 'Velký krok',
    'StepMedium'           => 'Støední krok',
    'StepNone'             => '®ádný krok',
    'StepSmall'            => 'Malý krok',
    'Stills'               => 'Snímky',
    'Stop'                 => 'Zastavit',
    'Stopped'              => 'Zastaven',
    'Stream'               => 'Stream',
    'StreamReplayBuffer'   => 'Stream Replay Image Buffer',
    'Submit'               => 'Potvrdit',
    'System'               => 'System',
    'SystemLog'            => 'System Log',             // Added - 2011-06-16
    'Tele'                 => 'Pøiblí¾it',
    'Thumbnail'            => 'Miniatura',
    'Tilt'                 => 'Náklon',
    'Time'                 => 'Èas',
    'TimeDelta'            => 'Delta èasu',
    'TimeStamp'            => 'Èasové razítko',
    'Timeline'             => 'Èasová linie',
    'TimelineTip1'          => 'Pass your mouse over the graph to view a snapshot image and event details.',              // Added 2013.08.15.
    'TimelineTip2'          => 'Click on the coloured sections of the graph, or the image, to view the event.',              // Added 2013.08.15.
    'TimelineTip3'          => 'Click on the background to zoom in to a smaller time period based around your click.',              // Added 2013.08.15.
    'TimelineTip4'          => 'Use the controls below to zoom out or navigate back and forward through the time range.',              // Added 2013.08.15.
    'Timestamp'            => 'Razítko',
    'TimestampLabelFormat' => 'Formát èasového razítka',
    'TimestampLabelX'      => 'Èasové razítko X',
    'TimestampLabelY'      => 'Èasové razítko Y',
    'Today'                => 'Dnes',
    'Tools'                => 'Nástroje',
    'Total'                => 'Total',                  // Added - 2011-06-16
    'TotalBrScore'         => 'Celkové<br/>skóre',
    'TrackDelay'           => 'Prodleva dráhy',
    'TrackMotion'          => 'Pohyb po dráze',
    'Triggers'             => 'Trigery',
    'TurboPanSpeed'        => 'Rychlost Turbo otáèení',
    'TurboTiltSpeed'       => 'Rychlost Turbo náklonu',
    'Type'                 => 'Typ',
    'Unarchive'            => 'Vyjmout z archivu',
    'Undefined'            => 'Undefined',              // Added - 2009-02-08
    'Units'                => 'Jednotky',
    'Unknown'              => 'Neznámý',
    'Update'               => 'Update',
    'UpdateAvailable'      => 'Je dostupný nový update ZoneMinder.',
    'UpdateNotNecessary'   => 'Update není potøeba.',
    'Updated'              => 'Updated',                // Added - 2011-06-16
    'Upload'               => 'Upload',                 // Added - 2011-08-23
    'UsedPlugins'	   => 'Used Plugins',
    'UseFilter'            => 'Pou¾ít filtr',
    'UseFilterExprsPost'   => '&nbsp;výrazù', // This is used at the end of the phrase 'use N filter expressions'
    'UseFilterExprsPre'    => 'Pou¾ít&nbsp;', // This is used at the beginning of the phrase 'use N filter expressions'
    'User'                 => 'U¾ivatel',
    'Username'             => 'U¾ivatelské jméno',
    'Users'                => 'U¾ivatelé',
    'Value'                => 'Hodnota',
    'Version'              => 'Verze',
    'VersionIgnore'        => 'Ignorovat tuto verzi',
    'VersionRemindDay'     => 'Pøipomenout za 1 den',
    'VersionRemindHour'    => 'Pøipomenout za hodinu',
    'VersionRemindNever'   => 'Nepøipomínat nové veze',
    'VersionRemindWeek'    => 'Pøipomenout za týden',
    'Video'                => 'Video',
    'VideoFormat'          => 'Video formát',
    'VideoGenFailed'       => 'Chyba pøi generování videa!',
    'VideoGenFiles'        => 'Existující video soubory',
    'VideoGenNoFiles'      => '®ádné video soubory nenalezeny',
    'VideoGenParms'        => 'Parametry generování videa',
    'VideoGenSucceeded'    => 'Video vygenerováno úspì¹nì!',
    'VideoSize'            => 'Velikost videa',
    'View'                 => 'Zobrazit',
    'ViewAll'              => 'Zobrazit v¹echny',
    'ViewEvent'            => 'Zobrazit záznam',
    'ViewPaged'            => 'Zobrazit strákovì',
    'Wake'                 => 'Vzbudit',
    'WarmupFrames'         => 'Zahøívací snímky',
    'Watch'                => 'Sledovat',
    'Web'                  => 'Web',
    'WebColour'            => 'Webová barva',
    'Week'                 => 'Týden',
    'White'                => 'Bílá',
    'WhiteBalance'         => 'Vyvá¾ení bílé',
    'Wide'                 => 'Oddálit',
    'X'                    => 'X',
    'X10'                  => 'X10',
    'X10ActivationString'  => 'X10 aktivaèní øetìzec',
    'X10InputAlarmString'  => 'X10 input alarm øetìzec',
    'X10OutputAlarmString' => 'X10 output alarm øetìzec',
    'Y'                    => 'Y',
    'Yes'                  => 'Ano',
    'YouNoPerms'           => 'K tomuto zdroji nemáte oprávnìní.',
    'Zone'                 => 'Zóna',
    'ZoneAlarmColour'      => 'Barva alarmu (Red/Green/Blue)',
    'ZoneArea'             => 'Zone Area',
    'ZoneFilterSize'       => 'Filter Width/Height (pixels)',
    'ZoneMinMaxAlarmArea'  => 'Min/Max Alarmed Area',
    'ZoneMinMaxBlobArea'   => 'Min/Max Blob Area',
    'ZoneMinMaxBlobs'      => 'Min/Max Blobs',
    'ZoneMinMaxFiltArea'   => 'Min/Max Filtered Area',
    'ZoneMinMaxPixelThres' => 'Min/Max Pixel Threshold (0-255)',
    'ZoneMinderLog'        => 'ZoneMinder Log',         // Added - 2011-06-17
    'ZoneOverloadFrames'   => 'Overload Frame Ignore Count',
    'ZoneExtendAlarmFrames' => 'Extend Alarm Frame Count',
    'Zones'                => 'Zóny',
    'Zoom'                 => 'Zoom',
    'ZoomIn'               => 'Zvìt¹it',
    'ZoomOut'              => 'Zmen¹it',
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
	'OPTIONS_FFMPEG' => array(
		'Help' => "Parameters in this field are passwd on to FFmpeg. Multiple parameters can be separated by ,~~ ".
		          "Examples (do not enter quotes)~~~~".
		          "\"allowed_media_types=video\" Set datatype to request fromcam (audio, video, data)~~~~".
		          "\"reorder_queue_size=nnn\" Set number of packets to buffer for handling of reordered packets~~~~".
		          "\"loglevel=debug\" Set verbosiy of FFmpeg (quiet, panic, fatal, error, warning, info, verbose, debug)"
	),
	'OPTIONS_LIBVLC' => array(
		'Help' => "Parameters in this field are passwd on to libVLC. Multiple parameters can be separated by ,~~ ".
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
