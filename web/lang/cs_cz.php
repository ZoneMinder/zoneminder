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
    '8BitGrey'             => '8 bit šedá škála',
    'Action'               => 'Akce',
    'Actual'               => 'Skutečná',
    'AddNewControl'        => 'Přidat nové řízení',
    'AddNewMonitor'        => 'Přidat kameru',
    'AddNewServer'         => 'Add New Server',         // Added - 2018-08-30
    'AddNewStorage'        => 'Add New Storage',        // Added - 2018-08-30
    'AddNewUser'           => 'Přidat uživatele',
    'AddNewZone'           => 'Přidat zónu',
    'Alarm'                => 'Alarm',
    'AlarmBrFrames'        => 'Alarm<br/>Snímky',
    'AlarmFrame'           => 'Alarm snímek',
    'AlarmFrameCount'      => 'Počet alarm snímků',
    'AlarmLimits'          => 'Limity alarmu',
    'AlarmMaximumFPS'      => 'Alarm Maximum FPS',
    'AlarmPx'              => 'Alarm Px',
    'AlarmRGBUnset'        => 'You must set an alarm RGB colour',
    'AlarmRefImageBlendPct'=> 'Alarm Reference Image Blend %ge', // Added - 2015-04-18
    'Alert'                => 'Pozor',
    'All'                  => 'Všechny',
    'AnalysisFPS'          => 'Analysis FPS',           // Added - 2015-07-22
    'AnalysisUpdateDelay'  => 'Analysis Update Delay',  // Added - 2015-07-23
    'Apply'                => 'Použít',
    'ApplyingStateChange'  => 'Aplikuji změnu stavu',
    'ArchArchived'         => 'Pouze archivované',
    'ArchUnarchived'       => 'Pouze nearchivované',
    'Archive'              => 'Archiv',
    'Archived'             => 'Archivován',
    'Area'                 => 'Area',
    'AreaUnits'            => 'Area (px/%)',
    'AttrAlarmFrames'      => 'Alarm snímky',
    'AttrArchiveStatus'    => 'Archiv status',
    'AttrAvgScore'         => 'Prům. skóre',
    'AttrCause'            => 'Příčina',
    'AttrDiskBlocks'       => 'Bloky disku',
    'AttrDiskPercent'      => 'Zaplnění disku',
    'AttrDiskSpace'        => 'Disk Space',             // Added - 2018-08-30
    'AttrDuration'         => 'Průběh',
    'AttrEndDate'          => 'End Date',               // Added - 2018-08-30
    'AttrEndDateTime'      => 'End Date/Time',          // Added - 2018-08-30
    'AttrEndTime'          => 'End Time',               // Added - 2018-08-30
    'AttrEndWeekday'       => 'End Weekday',            // Added - 2018-08-30
    'AttrFilterServer'     => 'Server Filter is Running On', // Added - 2018-08-30
    'AttrFrames'           => 'Snímky',
    'AttrId'               => 'Id',
    'AttrMaxScore'         => 'Max. skóre',
    'AttrMonitorId'        => 'Kamera Id',
    'AttrMonitorName'      => 'Jméno kamery',
    'AttrMonitorServer'    => 'Server Monitor is Running On', // Added - 2018-08-30
    'AttrName'             => 'Jméno',
    'AttrNotes'            => 'Notes',
    'AttrStartDate'        => 'Start Date',             // Added - 2018-08-30
    'AttrStartDateTime'    => 'Start Date/Time',        // Added - 2018-08-30
    'AttrStartTime'        => 'Start Time',             // Added - 2018-08-30
    'AttrStartWeekday'     => 'Start Weekday',          // Added - 2018-08-30
    'AttrStateId'          => 'Run State',              // Added - 2018-08-30
    'AttrStorageArea'      => 'Storage Area',           // Added - 2018-08-30
    'AttrStorageServer'    => 'Server Hosting Storage', // Added - 2018-08-30
    'AttrSystemLoad'       => 'System Load',
    'AttrTotalScore'       => 'Celkové skóre',
    'Auto'                 => 'Auto',
    'AutoStopTimeout'      => 'Časový limit pro vypršení',
    'Available'            => 'Available',              // Added - 2009-03-31
    'AvgBrScore'           => 'Prům.<br/>Skóre',
    'Background'           => 'Background',
    'BackgroundFilter'     => 'Run filter in background',
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
    'BadNameChars'         => 'Jména moho obsahovat pouze alfanumerické znaky a podtržítko či pomlčku',
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
    'Bandwidth'            => 'Rychlost sítě',
    'BandwidthHead'        => 'Bandwidth',	// This is the end of the bandwidth status on the top of the console, different in many language due to phrasing
    'BlobPx'               => 'Značka Px',
    'BlobSizes'            => 'Velikost značky',
    'Blobs'                => 'Značky',
    'Brightness'           => 'Světlost',
    'Buffer'               => 'Buffer',                 // Added - 2015-04-18
    'Buffers'              => 'Bufery',
    'CSSDescription'       => 'Change the default css for this computer', // Added - 2015-04-18
    'CanAutoFocus'         => 'Umí automaticky zaostřit',
    'CanAutoGain'          => 'Umí automatický zisk',
    'CanAutoIris'          => 'Umí auto iris',
    'CanAutoWhite'         => 'Umí automaticky vyvážit bílou',
    'CanAutoZoom'          => 'Umí automaticky zoomovat',
    'CanFocus'             => 'Umí zaostřit',
    'CanFocusAbs'          => 'Umí zaostřit absolutně',
    'CanFocusCon'          => 'Umí průběžně zaostřit',
    'CanFocusRel'          => 'Umí relativně zaostřit',
    'CanGain'              => 'Umí zisk',
    'CanGainAbs'           => 'Umí absolutní zisk',
    'CanGainCon'           => 'Umí průběžný zisk',
    'CanGainRel'           => 'Umí relativní zisk',
    'CanIris'              => 'Umí iris',
    'CanIrisAbs'           => 'Umí absolutní iris',
    'CanIrisCon'           => 'Umí průběžný iris',
    'CanIrisRel'           => 'Umí relativní iris',
    'CanMove'              => 'Umí pohyb',
    'CanMoveAbs'           => 'Umí absoultní pohyb',
    'CanMoveCon'           => 'Umí průběžný pohyb',
    'CanMoveDiag'          => 'Umí diagonální pohyb',
    'CanMoveMap'           => 'Umí mapovaný pohyb',
    'CanMoveRel'           => 'Umí relativní pohyb',
    'CanPan'               => 'Umí otáčení',
    'CanReset'             => 'Umí reset',
	'CanReboot'             => 'Can Reboot',
    'CanSetPresets'        => 'Umí navolit předvolby',
    'CanSleep'             => 'Může spát',
    'CanTilt'              => 'Umí náklon',
    'CanWake'              => 'Lze vzbudit',
    'CanWhite'             => 'Umí vyvážení bílé',
    'CanWhiteAbs'          => 'Umí absolutní vyvážení bílé',
    'CanWhiteBal'          => 'Umí vyvážení bílé',
    'CanWhiteCon'          => 'Umí průběžné vyvážení bílé',
    'CanWhiteRel'          => 'Umí relativní vyvážení bílé',
    'CanZoom'              => 'Umí zoom',
    'CanZoomAbs'           => 'Umí absolutní zoom',
    'CanZoomCon'           => 'Umí průběžný zoom',
    'CanZoomRel'           => 'Umí relativní zoom',
    'Cancel'               => 'Zrušit',
    'CancelForcedAlarm'    => 'Zastavit spuštěný alarm',
    'CaptureHeight'        => 'Výška zdrojového snímku',
    'CaptureMethod'        => 'Capture Method',         // Added - 2009-02-08
    'CapturePalette'       => 'Paleta zdrojového snímku',
    'CaptureResolution'    => 'Capture Resolution',     // Added - 2015-04-18
    'CaptureWidth'         => 'Šířka zdrojového snímku',
    'Cause'                => 'Příčina',
    'CheckMethod'          => 'Metoda značkování alarmem',
    'ChooseDetectedCamera' => 'Choose Detected Camera', // Added - 2009-03-31
    'ChooseFilter'         => 'Vybrat filtr',
    'ChooseLogFormat'      => 'Choose a log format',    // Added - 2011-06-17
    'ChooseLogSelection'   => 'Choose a log selection', // Added - 2011-06-17
    'ChoosePreset'         => 'Choose Preset',
    'Clear'                => 'Clear',                  // Added - 2011-06-16
    'CloneMonitor'         => 'Clone',                  // Added - 2018-08-30
    'Close'                => 'Zavřít',
    'Colour'               => 'Barva',
    'Command'              => 'Příkaz',
    'Component'            => 'Component',              // Added - 2011-06-16
    'ConcurrentFilter'     => 'Run filter concurrently', // Added - 2018-08-30
    'Config'               => 'Nastavení',
    'ConfiguredFor'        => 'Nastaveno pro',
    'ConfirmDeleteEvents'  => 'Are you sure you wish to delete the selected events?',
    'ConfirmPassword'      => 'Potvrdit heslo',
    'ConjAnd'              => 'a',
    'ConjOr'               => 'nebo',
    'Console'              => 'Konzola',
    'ContactAdmin'         => 'Pro detailní info kontaktujte Vašeho administrátora.',
    'Continue'             => 'Pokračovat',
    'Contrast'             => 'Kontrast',
    'Control'              => 'Řízení',
    'ControlAddress'       => 'Adresa řízení',
    'ControlCap'           => 'Schopnosti řízení',
    'ControlCaps'          => 'Typy řízení',
    'ControlDevice'        => 'Zařízení řízení',
    'ControlType'          => 'Typ řízení',
    'Controllable'         => 'Říditelná',
    'Current'              => 'Current',                // Added - 2015-04-18
    'Cycle'                => 'Cyklus',
    'CycleWatch'           => 'Cyklické prohlížení',
    'DateTime'             => 'Date/Time',              // Added - 2011-06-16
    'Day'                  => 'Den',
    'Debug'                => 'Debug',
    'DefaultRate'          => 'Default Rate',
    'DefaultScale'         => 'Přednastavená velikost',
    'DefaultView'          => 'Default View',
    'Deinterlacing'        => 'Deinterlacing',          // Added - 2015-04-18
    'Delay'                => 'Delay',                  // Added - 2015-04-18
    'Delete'               => 'Smazat',
    'DeleteAndNext'        => 'Smazat &amp; Další',
    'DeleteAndPrev'        => 'Smazat &amp; Předchozí',
    'DeleteSavedFilter'    => 'Smazat filtr',
    'Description'          => 'Popis',
    'DetectedCameras'      => 'Detected Cameras',       // Added - 2009-03-31
    'DetectedProfiles'     => 'Detected Profiles',      // Added - 2015-04-18
    'Device'               => 'Device',                 // Added - 2009-02-08
    'DeviceChannel'        => 'Kanál zařízení',
    'DeviceFormat'         => 'Formát zařízení',
    'DeviceNumber'         => 'Číslo zarízení',
    'DevicePath'           => 'Cesta k zařízení',
    'Devices'              => 'Devices',
    'Dimensions'           => 'Rozměry',
    'DisableAlarms'        => 'Zakázat alarmy',
    'Disk'                 => 'Disk',
    'Display'              => 'Display',                // Added - 2011-01-30
    'Displaying'           => 'Displaying',             // Added - 2011-06-16
    'DoNativeMotionDetection'=> 'Do Native Motion Detection',
    'Donate'               => 'Prosím podpořte',
    'DonateAlready'        => 'Ne, už jsem podpořil',
    'DonateEnticement'     => 'Již nějakou dobu používáte software ZoneMinder k ochraně svého majetku a předpokládám, že jej shledáváte užitečným. Přestože je ZoneMinder, znovu připomínám, zdarma a volně šířený software, stojí jeho vývoj a podpora nějaké peníze. Pokud byste chtěl/a podpořit budoucí vývoj a nové možnosti softwaru, prosím zvažte darování finanční pomoci. Darování je, samozřejmě, dobrovolné, ale zato velmi ceněné můžete přispět jakou částkou chcete.<br><br>Pokud máte zájem podpořit náš tým, prosím, vyberte níže uvedenou možnost, nebo navštivte https://zoneminder.com/donate/.<br><br>Děkuji Vám že jste si vybral/a software ZoneMinder a nezapomeňte navštívit fórum na ZoneMinder.com pro podporu a návrhy jak udělat ZoneMinder ještě lepším než je dnes.',
    'DonateRemindDay'      => 'Nyní ne, připomenout za 1 den',
    'DonateRemindHour'     => 'Nyní ne, připomenout za hodinu',
    'DonateRemindMonth'    => 'Nyní ne, připomenout za měsíc',
    'DonateRemindNever'    => 'Ne, nechci podpořit ZoneMinder, nepřipomínat',
    'DonateRemindWeek'     => 'Nyní ne, připomenout za týden',
    'DonateYes'            => 'Ano, chcit podpořit ZoneMinder nyní',
    'Download'             => 'Stáhnout',
    'DownloadVideo'        => 'Download Video',         // Added - 2018-08-30
    'DuplicateMonitorName' => 'Duplicate Monitor Name', // Added - 2009-03-31
    'Duration'             => 'Průběh',
    'Edit'                 => 'Editovat',
    'EditLayout'           => 'Edit Layout',            // Added - 2018-08-30
    'Email'                => 'Email',
    'EnableAlarms'         => 'Povolit alarmy',
    'Enabled'              => 'Povoleno',
    'EnterNewFilterName'   => 'Zadejte nové jméno filtru',
    'Error'                => 'Chyba',
    'ErrorBrackets'        => 'Chyba, zkontrolujte prosím závorky',
    'ErrorValidValue'      => 'Chyba, zkontrolujte že podmínky mají správné hodnoty',
    'Etc'                  => 'atd',
    'Event'                => 'Záznam',
    'EventFilter'          => 'Filtr záznamů',
    'EventId'              => 'Id záznamu',
    'EventName'            => 'Jméno záznamu',
    'EventPrefix'          => 'Prefix záznamu',
    'Events'               => 'Záznamy',
    'Exclude'              => 'Vyjmout',
    'Execute'              => 'Execute',
    'Exif'                 => 'Embed EXIF data into image', // Added - 2018-08-30
    'Export'               => 'Exportovat',
    'ExportDetails'        => 'Exportovat detaily záznamu',
    'ExportFailed'         => 'Chyba při exportu',
    'ExportFormat'         => 'Formát exportovaného souboru',
    'ExportFormatTar'      => 'Tar',
    'ExportFormatZip'      => 'Zip',
    'ExportFrames'         => 'Exportovat detaily snímku',
    'ExportImageFiles'     => 'Exportovat obrazové soubory',
    'ExportLog'            => 'Export Log',             // Added - 2011-06-17
    'ExportMiscFiles'      => 'Exportovat ostatní soubory (jestli existují)',
    'ExportOptions'        => 'Možnosti exportu',
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
    'Filter'               => 'Filter',                 // Added - 2015-04-18
    'FilterArchiveEvents'  => 'Archivovat všechny nalezené',
    'FilterDeleteEvents'   => 'Smazat všechny nalezené',
    'FilterEmailEvents'    => 'Poslat email s detaily nalezených',
    'FilterExecuteEvents'  => 'Spustit příkaz na všech nalezených',
    'FilterLog'            => 'Filter log',             // Added - 2015-04-18
    'FilterMessageEvents'  => 'Podat zprávu o všech nalezených',
    'FilterMoveEvents'     => 'Move all matches',       // Added - 2018-08-30
    'FilterPx'             => 'Filtr Px',
    'FilterUnset'          => 'You must specify a filter width and height',
    'FilterUpdateDiskSpace'=> 'Update used disk space', // Added - 2018-08-30
    'FilterUploadEvents'   => 'Uploadovat nalezené',
    'FilterVideoEvents'    => 'Create video for all matches',
    'Filters'              => 'Filtry',
    'First'                => 'První',
    'FlippedHori'          => 'Překlopený vodorovně',
    'FlippedVert'          => 'Překlopený svisle',
    'FnMocord'              => 'Mocord',            // Added 2013.08.16.
    'FnModect'              => 'Modect',            // Added 2013.08.16.
    'FnMonitor'             => 'Monitor',            // Added 2013.08.16.
    'FnNodect'              => 'Nodect',            // Added 2013.08.16.
    'FnNone'                => 'None',            // Added 2013.08.16.
    'FnRecord'              => 'Record',            // Added 2013.08.16.
    'Focus'                => 'Zaostření',
    'ForceAlarm'           => 'Spustit alarm',
    'Format'               => 'Formát',
    'Frame'                => 'Snímek',
    'FrameId'              => 'Snímek Id',
    'FrameRate'            => 'Rychlost snímků',
    'FrameSkip'            => 'Vynechat snímek',
    'Frames'               => 'Snímky',
    'Func'                 => 'Funkce',
    'Function'             => 'Funkce',
    'Gain'                 => 'Zisk',
    'General'              => 'General',
    'GenerateDownload'     => 'Generate Download',      // Added - 2018-08-30
    'GenerateVideo'        => 'Generovat video',
    'GeneratingVideo'      => 'Generuji video',
    'GoToZoneMinder'       => 'Jít na ZoneMinder.com',
    'Grey'                 => 'Šedá',
    'Group'                => 'Group',
    'Groups'               => 'Skupiny',
    'HasFocusSpeed'        => 'Má rychlost zaostření',
    'HasGainSpeed'         => 'Má rychlost zisku',
    'HasHomePreset'        => 'Má Home volbu',
    'HasIrisSpeed'         => 'Má rychlost irisu',
    'HasPanSpeed'          => 'Má rychlost otáčení',
    'HasPresets'           => 'Má předvolby',
    'HasTiltSpeed'         => 'Má rychlost náklonu',
    'HasTurboPan'          => 'Má Turbo otáčení',
    'HasTurboTilt'         => 'Má Turbo náklon',
    'HasWhiteSpeed'        => 'Má rychlost vyvážení bílé',
    'HasZoomSpeed'         => 'Má rychlost zoomu',
    'High'                 => 'Rychlá',
    'HighBW'               => 'Rychlá&nbsp;B/W',
    'Home'                 => 'Domů',
    'Hostname'             => 'Hostname',               // Added - 2018-08-30
    'Hour'                 => 'Hodina',
    'Hue'                  => 'Odstín',
    'Id'                   => 'Id',
    'Idle'                 => 'Připraven',
    'Ignore'               => 'Ignorovat',
    'Image'                => 'Obraz',
    'ImageBufferSize'      => 'Velikost buferu snímků',
    'Images'               => 'Images',
    'In'                   => 'Dovnitř',
    'Include'              => 'Vložit',
    'Inverted'             => 'Převráceně',
    'Iris'                 => 'Iris',
    'KeyString'            => 'Key String',
    'Label'                => 'Label',
    'Language'             => 'Jazyk',
    'Last'                 => 'Poslední',
    'Layout'               => 'Layout',                 // Added - 2009-02-08
    'Level'                => 'Level',                  // Added - 2011-06-16
    'Libvlc'               => 'Libvlc',
    'LimitResultsPost'     => 'výsledků', // This is used at the end of the phrase 'Limit to first N results only'
    'LimitResultsPre'      => 'Zobrazit pouze prvních', // This is used at the beginning of the phrase 'Limit to first N results only'
    'Line'                 => 'Line',                   // Added - 2011-06-16
    'LinkedMonitors'       => 'Linked Monitors',
    'List'                 => 'Seznam',
    'ListMatches'          => 'List Matches',           // Added - 2018-08-30
    'Load'                 => 'Load',
    'Local'                => 'Lokální',
    'Log'                  => 'Log',                    // Added - 2011-06-16
    'LoggedInAs'           => 'Přihlášen jako',
    'Logging'              => 'Logging',                // Added - 2011-06-16
    'LoggingIn'            => 'Přihlašuji',
    'Login'                => 'Přihlásit',
    'Logout'               => 'Odhlásit',
    'Logs'                 => 'Logs',                   // Added - 2011-06-17
    'Low'                  => 'Pomalá',
    'LowBW'                => 'Pomalá&nbsp;B/W',
    'Main'                 => 'Hlavní',
    'Man'                  => 'Man',
    'Manual'               => 'Manuál',
    'Mark'                 => 'Označit',
    'Max'                  => 'Max',
    'MaxBandwidth'         => 'Max bandwidth',
    'MaxBrScore'           => 'Max.<br/>skóre',
    'MaxFocusRange'        => 'Max rozsah zaostření',
    'MaxFocusSpeed'        => 'Max rychlost zaostření',
    'MaxFocusStep'         => 'Max krok zaostření',
    'MaxGainRange'         => 'Max rozsah zisku',
    'MaxGainSpeed'         => 'Max rychlost zisku',
    'MaxGainStep'          => 'Max krok zisku',
    'MaxIrisRange'         => 'Max rozsah iris',
    'MaxIrisSpeed'         => 'Max rychlost iris',
    'MaxIrisStep'          => 'Max krok iris',
    'MaxPanRange'          => 'Max rozsah otáčení',
    'MaxPanSpeed'          => 'Max rychlost otáčení',
    'MaxPanStep'           => 'Max krok otáčení',
    'MaxTiltRange'         => 'Max rozsah náklonu',
    'MaxTiltSpeed'         => 'Max rychlost náklonu',
    'MaxTiltStep'          => 'Max krok náklonu',
    'MaxWhiteRange'        => 'Max rozsah vyvážení bílé',
    'MaxWhiteSpeed'        => 'Max rychlost vyvážení bílé',
    'MaxWhiteStep'         => 'Max krok vyvážení bílé',
    'MaxZoomRange'         => 'Max rozsah zoomu',
    'MaxZoomSpeed'         => 'Max rychlost zoomu',
    'MaxZoomStep'          => 'Max krok zoomu',
    'MaximumFPS'           => 'Maximum FPS',
    'Medium'               => 'Střední',
    'MediumBW'             => 'Střední&nbsp;B/W',
    'Message'              => 'Message',                // Added - 2011-06-16
    'MinAlarmAreaLtMax'    => 'Minimum alarm area should be less than maximum',
    'MinAlarmAreaUnset'    => 'You must specify the minimum alarm pixel count',
    'MinBlobAreaLtMax'     => 'Minimum značkované oblasti by mělo být menší než maximum',
    'MinBlobAreaUnset'     => 'You must specify the minimum blob pixel count',
    'MinBlobLtMinFilter'   => 'Minimum blob area should be less than or equal to minimum filter area',
    'MinBlobsLtMax'        => 'Minimum značek by mělo být menší než maximum',
    'MinBlobsUnset'        => 'You must specify the minimum blob count',
    'MinFilterAreaLtMax'   => 'Minimum filter area should be less than maximum',
    'MinFilterAreaUnset'   => 'You must specify the minimum filter pixel count',
    'MinFilterLtMinAlarm'  => 'Minimum filter area should be less than or equal to minimum alarm area',
    'MinFocusRange'        => 'Min rozsah zaostření',
    'MinFocusSpeed'        => 'Min rychlost zaostření',
    'MinFocusStep'         => 'Min krok zaostření',
    'MinGainRange'         => 'Min rozsah zisku',
    'MinGainSpeed'         => 'Min rychlost zisku',
    'MinGainStep'          => 'Min krok zisku',
    'MinIrisRange'         => 'Min rozsah iris',
    'MinIrisSpeed'         => 'Min rychlost iris',
    'MinIrisStep'          => 'Min krok iris',
    'MinPanRange'          => 'Min rozsah otáčení',
    'MinPanSpeed'          => 'Min rychlost otáčení',
    'MinPanStep'           => 'Min krok otáčení',
    'MinPixelThresLtMax'   => 'Minimální práh pixelu by měl být menší než  maximumální',
    'MinPixelThresUnset'   => 'You must specify a minimum pixel threshold',
    'MinTiltRange'         => 'Min rozsah náklonu',
    'MinTiltSpeed'         => 'Min rychlost náklonu',
    'MinTiltStep'          => 'Min krok náklonu',
    'MinWhiteRange'        => 'Min rozsah vyvážení bílé',
    'MinWhiteSpeed'        => 'Min rychlost vyvážení bílé',
    'MinWhiteStep'         => 'Min krok vyvážení bílé',
    'MinZoomRange'         => 'Min rozsah zoomu',
    'MinZoomSpeed'         => 'Min rychlost zoomu',
    'MinZoomStep'          => 'Min krok zoomu',
    'Misc'                 => 'Ostatní',
    'Mode'                 => 'Mode',                   // Added - 2015-04-18
    'Monitor'              => 'Kamera',
    'MonitorIds'           => 'Id&nbsp;kamer',
    'MonitorPreset'        => 'Monitor Preset',
    'MonitorPresetIntro'   => 'Select an appropriate preset from the list below.<br><br>Please note that this may overwrite any values you already have configured for this monitor.<br><br>',
    'MonitorProbe'         => 'Monitor Probe',          // Added - 2009-03-31
    'MonitorProbeIntro'    => 'The list below shows detected analog and network cameras and whether they are already being used or available for selection.<br/><br/>Select the desired entry from the list below.<br/><br/>Please note that not all cameras may be detected and that choosing a camera here may overwrite any values you already have configured for the current monitor.<br/><br/>', // Added - 2009-03-31
    'Monitors'             => 'Kamery',
    'Montage'              => 'Sestřih',
    'MontageReview'        => 'Montage Review',         // Added - 2018-08-30
    'Month'                => 'Měsíc',
    'More'                 => 'More',                   // Added - 2011-06-16
    'MotionFrameSkip'      => 'Motion Frame Skip',
    'Move'                 => 'Pohyb',
    'Mtg2widgrd'           => '2-wide grid',              // Added 2013.08.15.
    'Mtg3widgrd'           => '3-wide grid',              // Added 2013.08.15.
    'Mtg3widgrx'           => '3-wide grid, scaled, enlarge on alarm',              // Added 2013.08.15.
    'Mtg4widgrd'           => '4-wide grid',              // Added 2013.08.15.
    'MtgDefault'           => 'Default',              // Added 2013.08.15.
    'MustBeGe'             => 'musí být větší nebo rovno než',
    'MustBeLe'             => 'musí být menší nebo rovno než',
    'MustConfirmPassword'  => 'Musíte potvrdit heslo',
    'MustSupplyPassword'   => 'Musíte zadat heslo',
    'MustSupplyUsername'   => 'Musíte zadat uživatelské jméno',
    'Name'                 => 'Jméno',
    'Near'                 => 'Blízko',
    'Network'              => 'Síť',
    'New'                  => 'Nový',
    'NewGroup'             => 'Nová skupina',
    'NewLabel'             => 'New Label',
    'NewPassword'          => 'Nové heslo',
    'NewState'             => 'Nový stav',
    'NewUser'              => 'Nový uživatel',
    'Next'                 => 'Další',
    'No'                   => 'Ne',
    'NoDetectedCameras'    => 'No Detected Cameras',    // Added - 2009-03-31
    'NoDetectedProfiles'   => 'No Detected Profiles',   // Added - 2018-08-30
    'NoFramesRecorded'     => 'Pro tento snímek nejsou žádné záznamy',
    'NoGroup'              => 'No Group',
    'NoSavedFilters'       => 'Žádné uložené filtry',
    'NoStatisticsRecorded' => 'Pro tento záznam/snímek nejsou zaznamenány žádné statistiky',
    'None'                 => 'Zakázat',
    'NoneAvailable'        => 'Žádná není dostupná',
    'Normal'               => 'Normalní',
    'Notes'                => 'Poznámky',
    'NumPresets'           => 'Počet předvoleb',
    'Off'                  => 'Off',
    'On'                   => 'On',
    'OnvifCredentialsIntro'=> 'Please supply user name and password for the selected camera.<br/>If no user has been created for the camera then the user given here will be created with the given password.<br/><br/>', // Added - 2015-04-18
    'OnvifProbe'           => 'ONVIF',                  // Added - 2015-04-18
    'OnvifProbeIntro'      => 'The list below shows detected ONVIF cameras and whether they are already being used or available for selection.<br/><br/>Select the desired entry from the list below.<br/><br/>Please note that not all cameras may be detected and that choosing a camera here may overwrite any values you already have configured for the current monitor.<br/><br/>', // Added - 2015-04-18
    'OpEq'                 => 'rovno',
    'OpGt'                 => 'větší',
    'OpGtEq'               => 'větší nebo rovno',
    'OpIn'                 => 'nin set',
    'OpIs'                 => 'is',                     // Added - 2018-08-30
    'OpIsNot'              => 'is not',                 // Added - 2018-08-30
    'OpLt'                 => 'menší',
    'OpLtEq'               => 'menší nebo rovno',
    'OpMatches'            => 'obsahuje',
    'OpNe'                 => 'nerovná se',
    'OpNotIn'              => 'nnot in set',
    'OpNotMatches'         => 'neobsahuje',
    'Open'                 => 'Otevřít',
    'OptionHelp'           => 'MožnostHelp',
    'OptionRestartWarning' => 'Tyto změny se neprojeví\ndokud systém běží. Jakmile\ndokončíte provádění změn prosím\nrestartujte ZoneMinder.',
    'OptionalEncoderParam' => 'Optional Encoder Parameters', // Added - 2018-08-30
    'Options'              => 'Možnosti',
    'OrEnterNewName'       => 'nebo vložte nové jméno',
    'Order'                => 'Pořadí',
    'Orientation'          => 'Orientace',
    'Out'                  => 'Ven',
    'OverwriteExisting'    => 'Přepsat existující',
    'Paged'                => 'Strákově',
    'Pan'                  => 'Otáčení',
    'PanLeft'              => 'Posunout vlevo',
    'PanRight'             => 'Posunout vpravo',
    'PanTilt'              => 'Otáčení/Náklon',
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
    'PlayAll'              => 'Přehrát vše',
    'PleaseWait'           => 'Prosím čekejte',
    'Plugins'              => 'Plugins',
    'Point'                => 'Point',
    'PostEventImageBuffer' => 'Pozáznamový bufer',
    'PreEventImageBuffer'  => 'Předzáznamový bufer',
    'PreserveAspect'       => 'Preserve Aspect Ratio',
    'Preset'               => 'Předvolba',
    'Presets'              => 'Předvolby',
    'Prev'                 => 'Zpět',
    'Probe'                => 'Probe',                  // Added - 2009-03-31
    'ProfileProbe'         => 'Stream Probe',           // Added - 2015-04-18
    'ProfileProbeIntro'    => 'The list below shows the existing stream profiles of the selected camera .<br/><br/>Select the desired entry from the list below.<br/><br/>Please note that ZoneMinder cannot configure additional profiles and that choosing a camera here may overwrite any values you already have configured for the current monitor.<br/><br/>', // Added - 2015-04-18
    'Progress'             => 'Progress',               // Added - 2015-04-18
    'Protocol'             => 'Protocol',
    'RTSPDescribe'         => 'Use RTSP Response Media URL', // Added - 2018-08-30
    'RTSPTransport'        => 'RTSP Transport Protocol', // Added - 2018-08-30
    'Rate'                 => 'Rychlost',
    'Real'                 => 'Skutečná',
    'RecaptchaWarning'     => 'Your reCaptcha secret key is invalid. Please correct it, or reCaptcha will not work', // Added - 2018-08-30
    'Record'               => 'Nahrávat',
    'RecordAudio'          => 'Whether to store the audio stream when saving an event.', // Added - 2018-08-30
    'RefImageBlendPct'     => 'Reference Image Blend %ge',
    'Refresh'              => 'Obnovit',
    'Remote'               => 'Síťová',
    'RemoteHostName'       => 'Adresa',
    'RemoteHostPath'       => 'Cesta',
    'RemoteHostPort'       => 'Port',
    'RemoteHostSubPath'    => 'Remote Host SubPath',    // Added - 2009-02-08
    'RemoteImageColours'   => 'Barvy',
    'RemoteMethod'         => 'Remote Method',          // Added - 2009-02-08
    'RemoteProtocol'       => 'Remote Protocol',        // Added - 2009-02-08
    'Rename'               => 'Přejmenovat',
    'Replay'               => 'Replay',
    'ReplayAll'            => 'All Events',
    'ReplayGapless'        => 'Gapless Events',
    'ReplaySingle'         => 'Single Event',
    'ReportEventAudit'     => 'Audit Events Report',    // Added - 2018-08-30
    'Reset'                => 'Reset',
    'ResetEventCounts'     => 'Resetovat počty záznamů',
    'Restart'              => 'Restartovat',
    'Restarting'           => 'Restartuji',
    'RestrictedCameraIds'  => 'Povolené id kamer',
    'RestrictedMonitors'   => 'Restricted Monitors',
    'ReturnDelay'          => 'Prodleva vracení',
    'ReturnLocation'       => 'Lokace vrácení',
    'Rewind'               => 'Rewind',
    'RotateLeft'           => 'Otočit vlevo',
    'RotateRight'          => 'Otočit vpravo',
    'RunLocalUpdate'       => 'Please run zmupdate.pl to update', // Added - 2011-05-25
    'RunMode'              => 'Režim',
    'RunState'             => 'Stav',
    'Running'              => 'Běží',
    'Save'                 => 'Uložit',
    'SaveAs'               => 'Uložit jako',
    'SaveFilter'           => 'Uložit filtr',
    'SaveJPEGs'            => 'Save JPEGs',             // Added - 2018-08-30
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
    'SetNewBandwidth'      => 'Nastavit novou rychlost sítě',
    'SetPreset'            => 'Nastavit předvolbu',
    'Settings'             => 'Nastavení',
    'ShowFilterWindow'     => 'Zobrazit filtr',
    'ShowTimeline'         => 'Zobrazit časovou linii ',
    'SignalCheckColour'    => 'Signal Check Colour',
    'SignalCheckPoints'    => 'Signal Check Points',    // Added - 2018-08-30
    'Size'                 => 'Velikost',
    'SkinDescription'      => 'Change the default skin for this computer', // Added - 2011-01-30
    'Sleep'                => 'Spát',
    'SortAsc'              => 'Vzestupně',
    'SortBy'               => 'Řadit dle',
    'SortDesc'             => 'Sestupně',
    'Source'               => 'Zdroj',
    'SourceColours'        => 'Source Colours',         // Added - 2009-02-08
    'SourcePath'           => 'Source Path',            // Added - 2009-02-08
    'SourceType'           => 'Typ zdroje',
    'Speed'                => 'Rychlost',
    'SpeedHigh'            => 'Vysoká rychlost',
    'SpeedLow'             => 'Nízká rychlost',
    'SpeedMedium'          => 'Střední rychlost',
    'SpeedTurbo'           => 'Turbo rychlost',
    'Start'                => 'Start',
    'State'                => 'Stav',
    'Stats'                => 'Statistiky',
    'Status'               => 'Status',
    'StatusConnected'      => 'Capturing',              // Added - 2018-08-30
    'StatusNotRunning'     => 'Not Running',            // Added - 2018-08-30
    'StatusRunning'        => 'Not Capturing',          // Added - 2018-08-30
    'StatusUnknown'        => 'Unknown',                // Added - 2018-08-30
    'Step'                 => 'Krok',
    'StepBack'             => 'Step Back',
    'StepForward'          => 'Step Forward',
    'StepLarge'            => 'Velký krok',
    'StepMedium'           => 'Střední krok',
    'StepNone'             => 'Žádný krok',
    'StepSmall'            => 'Malý krok',
    'Stills'               => 'Snímky',
    'Stop'                 => 'Zastavit',
    'Stopped'              => 'Zastaven',
    'StorageArea'          => 'Storage Area',           // Added - 2018-08-30
    'StorageScheme'        => 'Scheme',                 // Added - 2018-08-30
    'Stream'               => 'Stream',
    'StreamReplayBuffer'   => 'Stream Replay Image Buffer',
    'Submit'               => 'Potvrdit',
    'System'               => 'System',
    'SystemLog'            => 'System Log',             // Added - 2011-06-16
    'TargetColorspace'     => 'Target colorspace',      // Added - 2015-04-18
    'Tele'                 => 'Přiblížit',
    'Thumbnail'            => 'Miniatura',
    'Tilt'                 => 'Náklon',
    'Time'                 => 'Čas',
    'TimeDelta'            => 'Delta času',
    'TimeStamp'            => 'Časové razítko',
    'Timeline'             => 'Časová linie',
    'TimelineTip1'          => 'Pass your mouse over the graph to view a snapshot image and event details.',              // Added 2013.08.15.
    'TimelineTip2'          => 'Click on the coloured sections of the graph, or the image, to view the event.',              // Added 2013.08.15.
    'TimelineTip3'          => 'Click on the background to zoom in to a smaller time period based around your click.',              // Added 2013.08.15.
    'TimelineTip4'          => 'Use the controls below to zoom out or navigate back and forward through the time range.',              // Added 2013.08.15.
    'Timestamp'            => 'Razítko',
    'TimestampLabelFormat' => 'Formát časového razítka',
    'TimestampLabelSize'   => 'Font Size',              // Added - 2018-08-30
    'TimestampLabelX'      => 'Časové razítko X',
    'TimestampLabelY'      => 'Časové razítko Y',
    'Today'                => 'Dnes',
    'Tools'                => 'Nástroje',
    'Total'                => 'Total',                  // Added - 2011-06-16
    'TotalBrScore'         => 'Celkové<br/>skóre',
    'TrackDelay'           => 'Prodleva dráhy',
    'TrackMotion'          => 'Pohyb po dráze',
    'Triggers'             => 'Trigery',
    'TurboPanSpeed'        => 'Rychlost Turbo otáčení',
    'TurboTiltSpeed'       => 'Rychlost Turbo náklonu',
    'Type'                 => 'Typ',
    'Unarchive'            => 'Vyjmout z archivu',
    'Undefined'            => 'Undefined',              // Added - 2009-02-08
    'Units'                => 'Jednotky',
    'Unknown'              => 'Neznámý',
    'Update'               => 'Update',
    'UpdateAvailable'      => 'Je dostupný nový update ZoneMinder.',
    'UpdateNotNecessary'   => 'Update není potřeba.',
    'Updated'              => 'Updated',                // Added - 2011-06-16
    'Upload'               => 'Upload',                 // Added - 2011-08-23
    'UseFilter'            => 'Použít filtr',
    'UseFilterExprsPost'   => '&nbsp;výrazů', // This is used at the end of the phrase 'use N filter expressions'
    'UseFilterExprsPre'    => 'Použít&nbsp;', // This is used at the beginning of the phrase 'use N filter expressions'
    'UsedPlugins'	   => 'Used Plugins',
    'User'                 => 'Uživatel',
    'Username'             => 'Uživatelské jméno',
    'Users'                => 'Uživatelé',
    'V4L'                  => 'V4L',                    // Added - 2015-04-18
    'V4LCapturesPerFrame'  => 'Captures Per Frame',     // Added - 2015-04-18
    'V4LMultiBuffer'       => 'Multi Buffering',        // Added - 2015-04-18
    'Value'                => 'Hodnota',
    'Version'              => 'Verze',
    'VersionIgnore'        => 'Ignorovat tuto verzi',
    'VersionRemindDay'     => 'Připomenout za 1 den',
    'VersionRemindHour'    => 'Připomenout za hodinu',
    'VersionRemindNever'   => 'Nepřipomínat nové veze',
    'VersionRemindWeek'    => 'Připomenout za týden',
    'Video'                => 'Video',
    'VideoFormat'          => 'Video formát',
    'VideoGenFailed'       => 'Chyba při generování videa!',
    'VideoGenFiles'        => 'Existující video soubory',
    'VideoGenNoFiles'      => 'Žádné video soubory nenalezeny',
    'VideoGenParms'        => 'Parametry generování videa',
    'VideoGenSucceeded'    => 'Video vygenerováno úspěšně!',
    'VideoSize'            => 'Velikost videa',
    'VideoWriter'          => 'Video Writer',           // Added - 2018-08-30
    'View'                 => 'Zobrazit',
    'ViewAll'              => 'Zobrazit všechny',
    'ViewEvent'            => 'Zobrazit záznam',
    'ViewPaged'            => 'Zobrazit strákově',
    'Wake'                 => 'Vzbudit',
    'WarmupFrames'         => 'Zahřívací snímky',
    'Watch'                => 'Sledovat',
    'Web'                  => 'Web',
    'WebColour'            => 'Webová barva',
    'WebSiteUrl'           => 'Website URL',            // Added - 2018-08-30
    'Week'                 => 'Týden',
    'White'                => 'Bílá',
    'WhiteBalance'         => 'Vyvážení bílé',
    'Wide'                 => 'Oddálit',
    'X'                    => 'X',
    'X10'                  => 'X10',
    'X10ActivationString'  => 'X10 aktivační řetězec',
    'X10InputAlarmString'  => 'X10 input alarm řetězec',
    'X10OutputAlarmString' => 'X10 output alarm řetězec',
    'Y'                    => 'Y',
    'Yes'                  => 'Ano',
    'YouNoPerms'           => 'K tomuto zdroji nemáte oprávnění.',
    'Zone'                 => 'Zóna',
    'ZoneAlarmColour'      => 'Barva alarmu (Red/Green/Blue)',
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
    'Zones'                => 'Zóny',
    'Zoom'                 => 'Zoom',
    'ZoomIn'               => 'Zvětšit',
    'ZoomOut'              => 'Zmenšit',
);

// Complex replacements with formatting and/or placements, must be passed through sprintf
$CLANG = array(
    'CurrentLogin'         => 'Právě je přihlášen \'%1$s\'',
    'EventCount'           => '%1$s %2$s', // For example '37 Events' (from Vlang below)
    'LastEvents'           => 'Posledních %1$s %2$s', // For example 'Last 37 Events' (from Vlang below)
    'LatestRelease'        => 'Poslední verze je v%1$s, vy máte v%2$s.',
    'MonitorCount'         => '%1$s %2$s', // For example '4 Monitors' (from Vlang below)
    'MonitorFunction'      => 'Funkce %1$s kamery',
    'RunningRecentVer'     => 'Používáte poslední verzi ZoneMinder, v%s.',
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
    'Event'                => array( 0=>'Záznamů', 1=>'Záznam', 2=>'Záznamy', 5=>'Záznamů' ),
    'Monitor'              => array( 0=>'Kamer', 1=>'Kamera', 2=>'Kamery', 5=>'Kamer' ),
);

// You will need to choose or write a function that can correlate the plurality string arrays
// with variable counts. This is used to conjugate the Vlang arrays above with a number passed
// in to generate the correct noun form.
//
// In languages such as English this is fairly simple 
// Note this still has to be used with printf etc to get the right formatting
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
