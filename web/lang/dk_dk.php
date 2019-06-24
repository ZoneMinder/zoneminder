<?php
//
// ZoneMinder web Danish language file, $Date$, $Revision$
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

// ZoneMinder Danish Translation by Jørgen Edelbo

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
// header( "Content-Type: text/html; charset=windows-1252" );

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
    '24BitColour'           => '24 bit farve',
    '32BitColour'           => '32 bit farve',
    '8BitGrey'              => '8 bit gråskala',
    'Action'                => 'Handling',
    'Actual'                => 'Aktuel',
    'AddNewControl'         => 'Tilføj Ny Kontrol',
    'AddNewMonitor'         => 'Tilføj Ny Monitor',
    'AddNewServer'          => 'Tilføj Ny Server',
    'AddNewStorage'        => 'Add New Storage',        // Added - 2018-08-30
    'AddNewUser'            => 'Tilføj Ny Bruger',
    'AddNewZone'            => 'Tilføj Ny Zone',
    'Alarm'                 => 'Alarm',
    'AlarmBrFrames'         => 'Alarm<br/>Rammer',
    'AlarmFrame'            => 'Alarm Ramme',
    'AlarmFrameCount'       => 'Antal Alarm Rammer',
    'AlarmLimits'           => 'Alarm Grænser',
    'AlarmMaximumFPS'       => 'Alarm Maksimum FPS',
    'AlarmPx'               => 'Alarm Px',
    'AlarmRGBUnset'         => 'Du skal vælge en alarm RGB farve',
    'AlarmRefImageBlendPct' => 'Alarm Reference Billede Blandings %',
    'Alert'                 => 'Advarsel',
    'All'                   => 'Alle',
    'AnalysisFPS'           => 'Analyse FPS',
    'AnalysisUpdateDelay'   => 'Analyse Opdaterings Forsinkelse',
    'Apply'                 => 'Udfør',
    'ApplyingStateChange'   => 'Udfører tilstandsændring',
    'ArchArchived'          => 'Kun arkiverede',
    'ArchUnarchived'        => 'Kun ikke-arkiverede',
    'Archive'               => 'Arkivér',
    'Archived'              => 'Arkiverede',
    'Area'                  => 'Område',
    'AreaUnits'             => 'Område (px/%)',
    'AttrAlarmFrames'       => 'Alarm Rammer',
    'AttrArchiveStatus'     => 'Arkiverings Status',
    'AttrAvgScore'          => 'Middel Score',
    'AttrCause'             => 'Årsag',
    'AttrDiskBlocks'        => 'Disk Blokke',
    'AttrDiskPercent'       => 'Disk Procent',
    'AttrDiskSpace'        => 'Disk Space',             // Added - 2018-08-30
    'AttrDuration'          => 'Varighed',
    'AttrEndDate'          => 'End Date',               // Added - 2018-08-30
    'AttrEndDateTime'      => 'End Date/Time',          // Added - 2018-08-30
    'AttrEndTime'          => 'End Time',               // Added - 2018-08-30
    'AttrEndWeekday'       => 'End Weekday',            // Added - 2018-08-30
    'AttrFilterServer'     => 'Server Filter is Running On', // Added - 2018-08-30
    'AttrFrames'            => 'Rammer',
    'AttrId'                => 'Id',
    'AttrMaxScore'          => 'Max. Score',
    'AttrMonitorId'         => 'Monitor Id',
    'AttrMonitorName'       => 'Monitor Navn',
    'AttrMonitorServer'    => 'Server Monitor is Running On', // Added - 2018-08-30
    'AttrName'              => 'Navn',
    'AttrNotes'             => 'Noter',
    'AttrStartDate'        => 'Start Date',             // Added - 2018-08-30
    'AttrStartDateTime'    => 'Start Date/Time',        // Added - 2018-08-30
    'AttrStartTime'        => 'Start Time',             // Added - 2018-08-30
    'AttrStartWeekday'     => 'Start Weekday',          // Added - 2018-08-30
    'AttrStateId'          => 'Run State',              // Added - 2018-08-30
    'AttrStorageArea'      => 'Storage Area',           // Added - 2018-08-30
    'AttrStorageServer'    => 'Server Hosting Storage', // Added - 2018-08-30
    'AttrSystemLoad'        => 'System Belastning',
    'AttrTotalScore'        => 'Total Score',
    'Auto'                  => 'Auto',
    'AutoStopTimeout'       => 'Auto Stop Timeout',
    'Available'             => 'Tilgængelig',
    'AvgBrScore'            => 'Middel<br/>Score',
    'Background'            => 'Baggrund',
    'BackgroundFilter'      => 'Kør filteret i baggrunden',
    'BadAlarmFrameCount'    => 'Antal alarm rammer skal være et positivt heltal',
    'BadAlarmMaxFPS'        => 'Alarm Maximum FPS skal være et positivt heltal eller flydende tal',
    'BadAnalysisFPS'        => 'Analyse FPS skal være et positivt heltal eller flydende tal',
    'BadAnalysisUpdateDelay'=> 'Analyse opdaterings forsinkelse skal være et heltal på 0 eller mere',
    'BadChannel'            => 'Kanal skal sættes til et heltal på 0 eller mere',
    'BadColours'            => 'Målfarven skal sættes til en gyldig værdi',
    'BadDevice'             => 'Enhed skal sættes til en gyldig værdi',
    'BadFPSReportInterval'  => 'Antal FPS report interval buffere skal være et heltal på 0 eller mere',
    'BadFormat'             => 'Format skal sættes til en gyldig værdi',
    'BadFrameSkip'          => 'Antal Frame skip skal være et heltal på 0 eller mere',
    'BadHeight'             => 'Højde skal sættes til en gyldig værdi',
    'BadHost'               => 'Host skal vare en gyldig IP adresse eller hostname, inkludér ikke http://',
    'BadImageBufferCount'   => 'Billed buffer størrelse skal være et heltal på 10 eller mere',
    'BadLabelX'             => 'Mærkat X co-ordinaten skal sættes til et heltal på 0 eller mere',
    'BadLabelY'             => 'Mærkat Y co-ordinaten skal sættes til et heltal på 0 eller mere',
    'BadMaxFPS'             => 'Maximum FPS skal være et positivt heltal eller flydende tal',
    'BadMotionFrameSkip'    => 'Antal Motion Frame skip skal være et heltal på 0 eller mere',
    'BadNameChars'          => 'Navne kan kun indeholde alfanumeriske tegn samt mellemrum, bindestreg og understregning',
    'BadPalette'            => 'Palette skal sættes til en gyldig værdi',
    'BadPath'               => 'Sti skal sættes til en gyldig værdi',
    'BadPort'               => 'Port skal sættes til et gyldigt nummer',
    'BadPostEventCount'     => 'Antal rammer efter hændelsen skal være et heltal på 0 eller mere',
    'BadPreEventCount'      => 'Antal rammer før hændelsen skal være mindst 0 samt mindre en billedbufferstørrelsen',
    'BadRefBlendPerc'       => 'Reference blandings procentdelen skal være et positivt heltal',
    'BadSectionLength'      => 'Sektionslængden skal være et heltal på 30 eller mere',
    'BadSignalCheckColour'  => 'Signal check farve skal være en gyldig RGB farve streng',
    'BadSourceType'        => 'Source Type \"Web Site\" requires the Function to be set to \"Monitor\"', // Added - 2018-08-30
    'BadStreamReplayBuffer' => 'Videostrøm genspilsbufferen skal sættes til et heltal på 0 eller mere',
    'BadWarmupCount'        => 'Opvarmnings rammer skal være et heltal på 0 eller mere',
    'BadWebColour'          => 'Web farve skal være en gyldigt web farve streng',
    'BadWebSitePath'       => 'Please enter a complete website url, including the http:// or https:// prefix.', // Added - 2018-08-30
    'BadWidth'              => 'Bredde skal sættes til en gyldig værdi',
    'Bandwidth'             => 'Båndbredde',
    'BandwidthHead'         => 'Båndbredde',	// This is the end of the bandwidth status on the top of the console, different in many language due to phrasing
    'BlobPx'                => 'Blob Px',
    'BlobSizes'             => 'Blob Størrelser',
    'Blobs'                 => 'Blobs',
    'Brightness'            => 'Lysstyrke',
    'Buffer'                => 'Buffer',
    'Buffers'               => 'Buffere',
    'CSSDescription'        => 'SKift standard css for denne computer',
    'CanAutoFocus'          => 'Can Auto Focus',
    'CanAutoGain'           => 'Can Auto Gain',
    'CanAutoIris'           => 'Can Auto Iris',
    'CanAutoWhite'          => 'Can Auto White Bal.',
    'CanAutoZoom'           => 'Can Auto Zoom',
    'CanFocus'              => 'Can Focus',
    'CanFocusAbs'           => 'Can Focus Absolute',
    'CanFocusCon'           => 'Can Focus Continuous',
    'CanFocusRel'           => 'Can Focus Relative',
    'CanGain'               => 'Can Gain ',
    'CanGainAbs'            => 'Can Gain Absolute',
    'CanGainCon'            => 'Can Gain Continuous',
    'CanGainRel'            => 'Can Gain Relative',
    'CanIris'               => 'Can Iris',
    'CanIrisAbs'            => 'Can Iris Absolute',
    'CanIrisCon'            => 'Can Iris Continuous',
    'CanIrisRel'            => 'Can Iris Relative',
    'CanMove'               => 'Can Move',
    'CanMoveAbs'            => 'Can Move Absolute',
    'CanMoveCon'            => 'Can Move Continuous',
    'CanMoveDiag'           => 'Can Move Diagonally',
    'CanMoveMap'            => 'Can Move Mapped',
    'CanMoveRel'            => 'Can Move Relative',
    'CanPan'                => 'Can Pan' ,
    'CanReset'              => 'Can Reset',
	'CanReboot'             => 'Can Reboot',
    'CanSetPresets'         => 'Can Set Presets',
    'CanSleep'              => 'Can Sleep',
    'CanTilt'               => 'Can Tilt',
    'CanWake'               => 'Can Wake',
    'CanWhite'              => 'Can White Balance',
    'CanWhiteAbs'           => 'Can White Bal. Absolute',
    'CanWhiteBal'           => 'Can White Bal.',
    'CanWhiteCon'           => 'Can White Bal. Continuous',
    'CanWhiteRel'           => 'Can White Bal. Relative',
    'CanZoom'               => 'Can Zoom',
    'CanZoomAbs'            => 'Can Zoom Absolute',
    'CanZoomCon'            => 'Can Zoom Continuous',
    'CanZoomRel'            => 'Can Zoom Relative',
    'Cancel'                => 'Fortryd',
    'CancelForcedAlarm'     => 'Fortryd Tvungen Alarm',
    'CaptureHeight'         => 'Capture Højde',
    'CaptureMethod'         => 'Capture Metode',
    'CapturePalette'        => 'Capture Palette',
    'CaptureResolution'     => 'Capture Opløsning',
    'CaptureWidth'          => 'Capture Bredde',
    'Cause'                 => 'Årsag',
    'CheckMethod'           => 'Alarm Check Metode',
    'ChooseDetectedCamera'  => 'Vælg Fundet Kamera',
    'ChooseFilter'          => 'Vælg Filter',
    'ChooseLogFormat'       => 'Vælg et lognings format',
    'ChooseLogSelection'    => 'Vælg et lognings udvælgelse',
    'ChoosePreset'          => 'Vælg Forudindstilling',
    'Clear'                 => 'Slet',
    'CloneMonitor'          => 'Klon Monitor',
    'Close'                 => 'Luk',
    'Colour'                => 'Farve',
    'Command'               => 'Kommando',
    'Component'             => 'Komponent',
    'ConcurrentFilter'     => 'Run filter concurrently', // Added - 2018-08-30
    'Config'                => 'Konfigurer',
    'ConfiguredFor'         => 'Konfigureret for',
    'ConfirmDeleteEvents'   => 'Er du sikker på, at du vil slette de markerede hændelser?',
    'ConfirmPassword'       => 'Bekræft Adgangskode',
    'ConjAnd'               => 'og',
    'ConjOr'                => 'eller',
    'Console'               => 'Konsol',
    'ContactAdmin'          => 'Venligst kontakt din administrator for detaljer.',
    'Continue'              => 'Fortsæt',
    'Contrast'              => 'Kontrast',
    'Control'               => 'Control',
    'ControlAddress'        => 'Control Address',
    'ControlCap'            => 'Control Capability',
    'ControlCaps'           => 'Control Capabilities',
    'ControlDevice'         => 'Control Device',
    'ControlType'           => 'Control Type',
    'Controllable'          => 'Controllable',
    'Current'               => 'Nuværende',
    'Cycle'                 => 'Cyklisk',
    'CycleWatch'            => 'Cyklisk Overvågning',
    'DateTime'              => 'Dato/Tid',
    'Day'                   => 'Dag',
    'Debug'                 => 'Fejlfind',
    'DefaultRate'           => 'Standard Rate',
    'DefaultScale'          => 'Standard Skalering',
    'DefaultView'           => 'Standard Visning',
    'Deinterlacing'         => 'Deinterlacing',
    'Delay'                 => 'Forsilkelse',
    'Delete'                => 'Slet',
    'DeleteAndNext'         => 'Slet &amp; Næste',
    'DeleteAndPrev'         => 'Slet &amp; Forrige',
    'DeleteSavedFilter'     => 'Slet gemt filter',
    'Description'           => 'Beskrivelse',
    'DetectedCameras'       => 'Fundne Kameraer',
    'DetectedProfiles'      => 'Fundne Profiler',
    'Device'                => 'Enheds',
    'DeviceChannel'         => 'Enheds Kanal',
    'DeviceFormat'          => 'Enheds Format',
    'DeviceNumber'          => 'Enheds Number',
    'DevicePath'            => 'Sti Til Enhed',
    'Devices'               => 'Enheder',
    'Dimensions'            => 'Dimensioner',
    'DisableAlarms'         => 'Deaktiver Alarmer',
    'Disk'                  => 'Disk',
    'Display'               => 'Display',
    'Displaying'            => 'Displaying',
    'DoNativeMotionDetection'=> 'Do Native Motion Detection',
    'Donate'                => 'Venligst Donér',
    'DonateAlready'         => 'Nej, jeg har allerede doneret',
    'DonateEnticement'      => 'You\'ve been running ZoneMinder for a while now and hopefully are finding it a useful addition to your home or workplace security. Although ZoneMinder is, and will remain, free and open source, it costs money to develop and support. If you would like to help support future development and new features then please consider donating. Donating is, of course, optional but very much appreciated and you can donate as much or as little as you like.<br/><br/>If you would like to donate please select the option below or go to https://zoneminder.com/donate/ in your browser.<br/><br/>Thank you for using ZoneMinder and don\'t forget to visit the forums on ZoneMinder.com for support or suggestions about how to make your ZoneMinder experience even better.',
    'DonateRemindDay'       => 'Ikke endnu, påmind igen on 1 dag',
    'DonateRemindHour'      => 'Ikke endnu, påmind igen on 1 time',
    'DonateRemindMonth'     => 'Ikke endnu, påmind igen on 1 måned',
    'DonateRemindNever'     => 'Nej, jeg ønsker ikke at donere, påmind ikke igen',
    'DonateRemindWeek'      => 'Ikke endnu, påmind igen on 1 uge',
    'DonateYes'             => 'Ja, jeg vil gerne donere nu',
    'Download'              => 'Download',
    'DownloadVideo'        => 'Download Video',         // Added - 2018-08-30
    'DuplicateMonitorName'  => 'Dupliket Monitor Navn',
    'Duration'              => 'Varighed',
    'Edit'                  => 'Ret',
    'EditLayout'           => 'Edit Layout',            // Added - 2018-08-30
    'Email'                 => 'Email',
    'EnableAlarms'          => 'Aktivér Alarmer',
    'Enabled'               => 'Virksom',
    'EnterNewFilterName'    => 'Indtast nyt filternavn',
    'Error'                 => 'Fejl',
    'ErrorBrackets'         => 'Fejl, venligst check, at du har samme antal open og lukke klammer',
    'ErrorValidValue'       => 'Fejl, venligst check at alle parametre har en gyldig værdi',
    'Etc'                   => 'etc',
    'Event'                 => 'Hændelse',
    'EventFilter'           => 'Hændelses Filter',
    'EventId'               => 'Hændelses Id',
    'EventName'             => 'Hændelses Name',
    'EventPrefix'           => 'Hændelses Prefix',
    'Events'                => 'Hændelser',
    'Exclude'               => 'Ekskluder',
    'Execute'               => 'Udfør',
    'Exif'                  => 'Indlejre EXIF data i billede',
    'Export'                => 'Exporter',
    'ExportDetails'         => 'Exporter Hændelses Detaljer',
    'ExportFailed'          => 'Export Mislykkedes',
    'ExportFormat'          => 'Export Fil Format',
    'ExportFormatTar'       => 'Tar',
    'ExportFormatZip'       => 'Zip',
    'ExportFrames'          => 'Exporter Ramme Detaljer',
    'ExportImageFiles'      => 'Exporter billed filer',
    'ExportLog'             => 'Export Log',
    'ExportMiscFiles'       => 'Exporter Andre Filer (hvis tilstede)',
    'ExportOptions'         => 'Export Indstillinger',
    'ExportSucceeded'       => 'Export Lykkedes',
    'ExportVideoFiles'      => 'Exporter Video Filer (hvis tilstede)',
    'Exporting'             => 'Exporterer',
    'FPS'                   => 'fps',
    'FPSReportInterval'     => 'FPS Rapport Interval',
    'FTP'                   => 'FTP',
    'Far'                   => 'Fjern',
    'FastForward'           => 'Hurtigt Frem',
    'Feed'                  => 'Feed',
    'Ffmpeg'                => 'Ffmpeg',
    'File'                  => 'Fil',
    'Filter'                => 'Filter',
    'FilterArchiveEvents'   => 'Arkiver alle matchende',
    'FilterDeleteEvents'    => 'Slet alle matchende',
    'FilterEmailEvents'     => 'Email detaljer for alle matchende',
    'FilterExecuteEvents'   => 'Udfør kommando for alle matchende',
    'FilterLog'             => 'Filter log',
    'FilterMessageEvents'   => 'Meddel detaljer for alle matchende',
    'FilterMoveEvents'     => 'Move all matches',       // Added - 2018-08-30
    'FilterPx'              => 'Filter Px',
    'FilterUnset'           => 'Du skal angive filter bredde og højde',
    'FilterUpdateDiskSpace'=> 'Update used disk space', // Added - 2018-08-30
    'FilterUploadEvents'    => 'Upload alle match',
    'FilterVideoEvents'     => 'Opret video for alle match',
    'Filters'               => 'Filtre',
    'First'                 => 'Første',
    'FlippedHori'           => 'Spejlet Horizontalt',
    'FlippedVert'           => 'Spejlet Vertikalt',
    'FnMocord'              => 'Mocord',            // Added 2013.08.16.
    'FnModect'              => 'Modect',            // Added 2013.08.16.
    'FnMonitor'             => 'Monitor',            // Added 2013.08.16.
    'FnNodect'              => 'Nodect',            // Added 2013.08.16.
    'FnNone'                => 'None',            // Added 2013.08.16.
    'FnRecord'              => 'Record',            // Added 2013.08.16.
    'Focus'                 => 'Focus',
    'ForceAlarm'            => 'Force Alarm',
    'Format'                => 'Format',
    'Frame'                 => 'Ramme',
    'FrameId'               => 'Ramme Id',
    'FrameRate'             => 'Billedhastighed',
    'FrameSkip'             => 'Spring over antal rammer',
    'Frames'                => 'Rammer',
    'Func'                  => 'Funk',
    'Function'              => 'Funktion',
    'Gain'                  => 'Gain',
    'General'               => 'Generelt',
    'GenerateDownload'     => 'Generate Download',      // Added - 2018-08-30
    'GenerateVideo'         => 'Generer Video',
    'GeneratingVideo'       => 'Genererer Video',
    'GoToZoneMinder'        => 'Gå til ZoneMinder.com',
    'Grey'                  => 'Gråskala',
    'Group'                 => 'Gruppe',
    'Groups'                => 'Grupper',
    'HasFocusSpeed'         => 'Has Focus Speed',
    'HasGainSpeed'          => 'Has Gain Speed',
    'HasHomePreset'         => 'Has Home Preset',
    'HasIrisSpeed'          => 'Has Iris Speed',
    'HasPanSpeed'           => 'Has Pan Speed',
    'HasPresets'            => 'Has Presets',
    'HasTiltSpeed'          => 'Has Tilt Speed',
    'HasTurboPan'           => 'Has Turbo Pan',
    'HasTurboTilt'          => 'Has Turbo Tilt',
    'HasWhiteSpeed'         => 'Has White Bal. Speed',
    'HasZoomSpeed'          => 'Has Zoom Speed',
    'High'                  => 'Høj',
    'HighBW'                => 'High&nbsp;B/W',
    'Home'                  => 'Hjemme',
    'Hostname'              => 'Hostname',
    'Hour'                  => 'Time',
    'Hue'                   => 'Farvetone',
    'Id'                    => 'Id',
    'Idle'                  => 'Afventende',
    'Ignore'                => 'Ignorer',
    'Image'                 => 'Billede',
    'ImageBufferSize'       => 'Billed Buffer Størrelse (rammer)',
    'Images'                => 'Billeder',
    'In'                    => 'I',
    'Include'               => 'Inkluder',
    'Inverted'              => 'Inverteret',
    'Iris'                  => 'Blænde',
    'KeyString'             => 'Nøgle Streng',
    'Label'                 => 'Mærkat',
    'Language'              => 'Sprog',
    'Last'                  => 'Sidste',
    'Layout'                => 'Layout',
    'Level'                 => 'Niveau',
    'Libvlc'                => 'Libvlc',
    'LimitResultsPost'      => 'resultater', // This is used at the end of the phrase 'Limit to first N results only'
    'LimitResultsPre'       => 'Begræns til kun de første', // This is used at the beginning of the phrase 'Limit to first N results only'
    'Line'                  => 'Linie',
    'LinkedMonitors'        => 'Sammenkædede Monitorer',
    'List'                  => 'Liste',
    'ListMatches'          => 'List Matches',           // Added - 2018-08-30
    'Load'                  => 'Belastning',
    'Local'                 => 'Lokal',
    'Log'                   => 'Log',
    'LoggedInAs'            => 'Logget ind som',
    'Logging'               => 'Logning',
    'LoggingIn'             => 'Logger ind',
    'Login'                 => 'Logind',
    'Logout'                => 'Logud',
    'Logs'                  => 'Logs',
    'Low'                   => 'Lav',
    'LowBW'                 => 'Lav&nbsp;B/W',
    'Main'                  => 'Hoved',
    'Man'                   => 'Man',
    'Manual'                => 'Manuel',
    'Mark'                  => 'Markér',
    'Max'                   => 'Max',
    'MaxBandwidth'          => 'Max Båndbredde',
    'MaxBrScore'            => 'Max.<br/>Score',
    'MaxFocusRange'         => 'Max Focus Range',
    'MaxFocusSpeed'         => 'Max Focus Speed',
    'MaxFocusStep'          => 'Max Focus Step',
    'MaxGainRange'          => 'Max Gain Range',
    'MaxGainSpeed'          => 'Max Gain Speed',
    'MaxGainStep'           => 'Max Gain Step',
    'MaxIrisRange'          => 'Max Iris Range',
    'MaxIrisSpeed'          => 'Max Iris Speed',
    'MaxIrisStep'           => 'Max Iris Step',
    'MaxPanRange'           => 'Max Pan Range',
    'MaxPanSpeed'           => 'Max Pan Speed',
    'MaxPanStep'            => 'Max Pan Step',
    'MaxTiltRange'          => 'Max Tilt Range',
    'MaxTiltSpeed'          => 'Max Tilt Speed',
    'MaxTiltStep'           => 'Max Tilt Step',
    'MaxWhiteRange'         => 'Max White Bal. Range',
    'MaxWhiteSpeed'         => 'Max White Bal. Speed',
    'MaxWhiteStep'          => 'Max White Bal. Step',
    'MaxZoomRange'          => 'Max Zoom Range',
    'MaxZoomSpeed'          => 'Max Zoom Speed',
    'MaxZoomStep'           => 'Max Zoom Step',
    'MaximumFPS'            => 'Maximum FPS',
    'Medium'                => 'Medium',
    'MediumBW'              => 'Medium&nbsp;B/W',
    'Message'               => 'Meddelelse',
    'MinAlarmAreaLtMax'     => 'Minimum alarm område skal være mindre end maksimum',
    'MinAlarmAreaUnset'     => 'Du skal angive det minimale antal alarm pixels',
    'MinBlobAreaLtMax'      => 'Minimum blob område skal være mindre end maksimum',
    'MinBlobAreaUnset'      => 'Du skal angive det minimale antal blob pixels',
    'MinBlobLtMinFilter'    => 'Minimum blob område skal være mindre end eller lig med minimum filter område',
    'MinBlobsLtMax'         => 'Minimum blobs skal være mindre end maksimum',
    'MinBlobsUnset'         => 'Du skal angive det minimale antal blobs',
    'MinFilterAreaLtMax'    => 'Minimum filter område skal være mindre end maksimum',
    'MinFilterAreaUnset'    => 'Du skal angive det minimale antal filter pixels',
    'MinFilterLtMinAlarm'   => 'Minimum filter område skal være mindre end eller lig med minimum alarm område',
    'MinFocusRange'         => 'Min Focus Range',
    'MinFocusSpeed'         => 'Min Focus Speed',
    'MinFocusStep'          => 'Min Focus Step',
    'MinGainRange'          => 'Min Gain Range',
    'MinGainSpeed'          => 'Min Gain Speed',
    'MinGainStep'           => 'Min Gain Step',
    'MinIrisRange'          => 'Min Iris Range',
    'MinIrisSpeed'          => 'Min Iris Speed',
    'MinIrisStep'           => 'Min Iris Step',
    'MinPanRange'           => 'Min Pan Range',
    'MinPanSpeed'           => 'Min Pan Speed',
    'MinPanStep'            => 'Min Pan Step',
    'MinPixelThresLtMax'    => 'Minimum pixel grænseværdi skal være mindre end maksimum',
    'MinPixelThresUnset'    => 'Du skal angive en minimum pixel grænseværdi',
    'MinTiltRange'          => 'Min Tilt Range',
    'MinTiltSpeed'          => 'Min Tilt Speed',
    'MinTiltStep'           => 'Min Tilt Step',
    'MinWhiteRange'         => 'Min White Bal. Range',
    'MinWhiteSpeed'         => 'Min White Bal. Speed',
    'MinWhiteStep'          => 'Min White Bal. Step',
    'MinZoomRange'          => 'Min Zoom Range',
    'MinZoomSpeed'          => 'Min Zoom Speed',
    'MinZoomStep'           => 'Min Zoom Step',
    'Misc'                  => 'Diverse',
    'Mode'                  => 'Mode',
    'Monitor'               => 'Monitor',
    'MonitorIds'            => 'Monitor&nbsp;Ids',
    'MonitorPreset'         => 'Monitor Forudindstillinger',
    'MonitorPresetIntro'    => 'Vælg en passende forudindstilling fra listen herunder.<br/><br/>Vær opmærksom på, at dette kan overskrive værdier, du allerede har angivet for den aktuelle monitor.<br/><br/>',
    'MonitorProbe'          => 'Monitor Probe',
    'MonitorProbeIntro'     => 'Listen herunder viser fundne analoge og nætværks kameraer samt hvorvidt de allerede er i brug eller tilgængelige for udvælgelse.<br/><br/>Vælg det ønskede fra listen herunder.<br/><br/>Vær opmærksom på, at muligvis ikke alle kameraer er fundet og at valg af et kamera kan overskrive værdier, du allerede har angivet for den aktuelle monitor.<br/><br/>',
    'Monitors'              => 'Monitorer',
    'Montage'               => 'Montage',
    'MontageReview'         => 'Montage Review',
    'Month'                 => 'Måned',
    'More'                  => 'Mere',
    'MotionFrameSkip'       => 'Spring over antal bevægelsesrammer',
    'Move'                  => 'Bevæg',
    'Mtg2widgrd'            => '2-bred gitter',              // Added 2013.08.15.
    'Mtg3widgrd'            => '3-bred gitter',              // Added 2013.08.15.
    'Mtg3widgrx'            => '3-bred gitter, skaleret, forstørret ved alarm',              // Added 2013.08.15.
    'Mtg4widgrd'            => '4-bred gitter',              // Added 2013.08.15.
    'MtgDefault'            => 'Standard',                 // Added 2013.08.15.
    'MustBeGe'              => 'Skal være større end eller lig med',
    'MustBeLe'              => 'Skal være mindre end eller lig med',
    'MustConfirmPassword'   => 'Du skal bekræfte adgangskoden',
    'MustSupplyPassword'    => 'Du skal levere en adgangskode',
    'MustSupplyUsername'    => 'Du skal levere et brugernavn',
    'Name'                  => 'Navn',
    'Near'                  => 'Nær',
    'Network'               => 'Netværk',
    'New'                   => 'Ny',
    'NewGroup'              => 'Ny Gruppe',
    'NewLabel'              => 'Ny Mærkat',
    'NewPassword'           => 'Ny Adgangskode',
    'NewState'              => 'Ny Tilstand',
    'NewUser'               => 'Ny bruger',
    'Next'                  => 'Næste',
    'No'                    => 'Nej',
    'NoDetectedCameras'     => 'Ingen Detected Cameras',
    'NoDetectedProfiles'    => 'Ingen Fundne Profiler',
    'NoFramesRecorded'      => 'Der er ingen billeder optaget for denne hændelse',
    'NoGroup'               => 'Ingen gruppe',
    'NoSavedFilters'        => 'IngenGemteFiltre',
    'NoStatisticsRecorded'  => 'Der er ingen statistik noteret for denne hændelse/ramme',
    'None'                  => 'Ingen',
    'NoneAvailable'         => 'Ingen tilgængelig',
    'Normal'                => 'Normalt',
    'Notes'                 => 'Noter',
    'NumPresets'            => 'Num Forudinst.',
    'Off'                   => 'Fra',
    'On'                    => 'Til',
    'OnvifCredentialsIntro' => 'Venligst lever brugernavn og adgangskodefor de valgte kamera.<br/>Hvis der ikke er oprettet nogen bruger for kameraet, så vil brugeren givet her blive oprettet med den angivne adgangskode.<br/><br/>',
    'OnvifProbe'            => 'ONVIF',
    'OnvifProbeIntro'       => 'Listen nedenfor viser fundne ONVIF kameraer samt hvorvidt de allerede er i brug eller tilgængelige for udvælgelse.<br/><br/>Vælg det ønskede fra listen herunder.<br/><br/>Vær opmærksom på, at muligvis ikke alle kameraer er fundet og at valg af et kamera kan overskrive værdier, du allerede har angivet for den aktuelle monitor.<br/><br/>',
    'OpEq'                  => 'lig med',
    'OpGt'                  => 'større end',
    'OpGtEq'                => 'større end eller lig med',
    'OpIn'                  => 'indeholdt i',
    'OpIs'                 => 'is',                     // Added - 2018-08-30
    'OpIsNot'              => 'is not',                 // Added - 2018-08-30
    'OpLt'                  => 'mindre end',
    'OpLtEq'                => 'mindre end eller lig med',
    'OpMatches'             => 'matcher',
    'OpNe'                  => 'ikke lig med',
    'OpNotIn'               => 'ikke indeholdt i',
    'OpNotMatches'          => 'matcher ikke',
    'Open'                  => 'Åben',
    'OptionHelp'            => 'Indstillinger hjælp',
    'OptionRestartWarning'  => 'Disse ændringer har muligvis ikke fuld effekt\nmens systemet er kørende. Når du har\nafsluttet dine ændringer, skal du huske at\ngenstarte ZoneMinder.',
    'OptionalEncoderParam'  => 'Optionelle Encoder Parametre',
    'Options'               => 'Indstillinger',
    'OrEnterNewName'        => 'eller indtast nyt navn',
    'Order'                 => 'Rækkefølge',
    'Orientation'           => 'Orientering',
    'Out'                   => 'Ud',
    'OverwriteExisting'     => 'Overskriv Eksisterende',
    'Paged'                 => 'Sidevis',
    'Pan'                   => 'Pan',
    'PanLeft'               => 'Pan Left',
    'PanRight'              => 'Pan Right',
    'PanTilt'               => 'Pan/Tilt',
    'Parameter'             => 'Parameter',
    'Password'              => 'Adgangskode',
    'PasswordsDifferent'    => 'Den nye og den bekræftende adgangskode er forskellige',
    'Paths'                 => 'Stier',
    'Pause'                 => 'Pause',
    'Phone'                 => 'Telefon',
    'PhoneBW'               => 'Telefon&nbsp;B/W',
    'Pid'                   => 'PID',
    'PixelDiff'             => 'Pixel Forskel',
    'Pixels'                => 'pixels',
    'Play'                  => 'Afspil',
    'PlayAll'               => 'Afspil Alle',
    'PleaseWait'            => 'Vent venligst',
    'Plugins'               => 'Plugins',
    'Point'                 => 'Point',
    'PostEventImageBuffer'  => 'Antal Billeder Efter Hændelse',
    'PreEventImageBuffer'   => 'Antal Billeder Før Hændelse',
    'PreserveAspect'        => 'Oprethold højde-bredde-forhold',
    'Preset'                => 'Forudindstilling',
    'Presets'               => 'Forudindstillinger',
    'Prev'                  => 'Forrige',
    'Probe'                 => 'Probe',
    'ProfileProbe'          => 'Stream Probe',
    'ProfileProbeIntro'     => 'The list below shows the existing stream profiles of the selected camera .<br/><br/>Select the desired entry from the list below.<br/><br/>Please note that ZoneMinder cannot configure additional profiles and that choosing a camera here may overwrite any values you already have configured for the current monitor.<br/><br/>',
    'Progress'              => 'Position',
    'Protocol'              => 'Protokol',
    'RTSPDescribe'          => 'Brug RTSP Response Media URL',
    'RTSPTransport'         => 'RTSP Transport Protocol',
    'Rate'                  => 'Rate',
    'Real'                  => 'Naturtro',
    'RecaptchaWarning'      => 'Your reCaptcha secret key is invalid. Please correct it, or reCaptcha will not work', // added Sep 24 2015 - PP
    'Record'                => 'Optag',
    'RecordAudio'           => 'Skal lydsporet gemmes sammen med en hændelse.',
    'RefImageBlendPct'      => 'Reference Billede Blandings %',
    'Refresh'               => 'Genindlæs',
    'Remote'                => 'Remote',
    'RemoteHostName'        => 'Remote Host Name',
    'RemoteHostPath'        => 'Remote Host Path',
    'RemoteHostPort'        => 'Remote Host Port',
    'RemoteHostSubPath'     => 'Remote Host SubPath',
    'RemoteImageColours'    => 'Remote Image Colours',
    'RemoteMethod'          => 'Remote Method',
    'RemoteProtocol'        => 'Remote Protocol',
    'Rename'                => 'Omdøb',
    'Replay'                => 'Genafspil',
    'ReplayAll'             => 'Alle Hændelser',
    'ReplayGapless'         => 'Hændelser uafbrudt',
    'ReplaySingle'          => 'Enkelt Hændelse',
    'ReportEventAudit'     => 'Audit Events Report',    // Added - 2018-08-30
    'Reset'                 => 'Nulstil',
    'ResetEventCounts'      => 'Nulstil Hændelses Tæller',
    'Restart'               => 'Genstart',
    'Restarting'            => 'Genstarter',
    'RestrictedCameraIds'   => 'Restricted Camera Ids',
    'RestrictedMonitors'    => 'Restricted Monitors',
    'ReturnDelay'           => 'Return Delay',
    'ReturnLocation'        => 'Return Location',
    'Rewind'                => 'Hurtigt Tilbage',
    'RotateLeft'            => 'Roter til venstrte',
    'RotateRight'           => 'Roter til højre',
    'RunLocalUpdate'        => 'Kør venligst zmupdate.pl for at opdatere',
    'RunMode'               => 'Driftsmåde',
    'RunState'              => 'Drift Tilstand',
    'Running'               => 'Kørende',
    'Save'                  => 'Gem',
    'SaveAs'                => 'Gem som',
    'SaveFilter'            => 'Gem Filter',
    'SaveJPEGs'            => 'Save JPEGs',             // Added - 2018-08-30
    'Scale'                 => 'Skaler',
    'Score'                 => 'Score',
    'Secs'                  => 'Sek.',
    'Sectionlength'         => 'Sektions længde',
    'Select'                => 'Vælg',
    'SelectFormat'          => 'Vælg Format',
    'SelectLog'             => 'Vælg Log',
    'SelectMonitors'        => 'Vælg Monitorer',
    'SelfIntersecting'      => 'Polygonens kanter må ikke krydses',
    'Set'                   => 'Sæt',
    'SetNewBandwidth'       => 'Vælg ny båndbredde',
    'SetPreset'             => 'Set Preset',
    'Settings'              => 'Indstillinger',
    'ShowFilterWindow'      => 'Vis Filter Vindue',
    'ShowTimeline'          => 'Vis Tidslinie',
    'SignalCheckColour'     => 'Signal Check Colour',
    'SignalCheckPoints'    => 'Signal Check Points',    // Added - 2018-08-30
    'Size'                  => 'Størrelse',
    'SkinDescription'       => 'SKift standard skin for denne computer',
    'Sleep'                 => 'Sover',
    'SortAsc'               => 'Voksende',
    'SortBy'                => 'Sortér efter',
    'SortDesc'              => 'Faldende',
    'Source'                => 'Kilde',
    'SourceColours'         => 'Kilde Farver',
    'SourcePath'            => 'Kilde Sti',
    'SourceType'            => 'Kilde Type',
    'Speed'                 => 'Speed',
    'SpeedHigh'             => 'High Speed',
    'SpeedLow'              => 'Low Speed',
    'SpeedMedium'           => 'Medium Speed',
    'SpeedTurbo'            => 'Turbo Speed',
    'Start'                 => 'Start',
    'State'                 => 'Tilstand',
    'Stats'                 => 'Stats',
    'Status'                => 'Status',
    'StatusConnected'      => 'Capturing',              // Added - 2018-08-30
    'StatusNotRunning'     => 'Not Running',            // Added - 2018-08-30
    'StatusRunning'        => 'Not Capturing',          // Added - 2018-08-30
    'StatusUnknown'        => 'Unknown',                // Added - 2018-08-30
    'Step'                  => 'Skridt',
    'StepBack'              => 'Skridt Tilbage',
    'StepForward'           => 'Skridt Frem',
    'StepLarge'             => 'Langt Skridt',
    'StepMedium'            => 'Medium Skridt',
    'StepNone'              => 'Ingen Skridt',
    'StepSmall'             => 'Lille Skridt',
    'Stills'                => 'Stilbilleder',
    'Stop'                  => 'Stop',
    'Stopped'               => 'Stoppet',
    'StorageArea'          => 'Storage Area',           // Added - 2018-08-30
    'StorageScheme'        => 'Scheme',                 // Added - 2018-08-30
    'Stream'                => 'Stream',
    'StreamReplayBuffer'    => 'Stream Replay Image Buffer',
    'Submit'                => 'Påtryk',
    'System'                => 'System',
    'SystemLog'             => 'System Log',
    'TargetColorspace'      => 'Target colorspace',
    'Tele'                  => 'Tele',
    'Thumbnail'             => 'Thumbnail',
    'Tilt'                  => 'Tilt',
    'Time'                  => 'Tidspunkt',
    'TimeDelta'             => 'Tidsforskel',
    'TimeStamp'             => 'Tids stempel',
    'Timeline'              => 'Tidslinie',
    'TimelineTip1'          => 'Før musen over grafen for at vise snapshot billede og detaljer om hændelsen.',              // Added 2013.08.15.
    'TimelineTip2'          => 'Klip på de farvede områder af grafen, eller billedet, for at se hændelsen.',              // Added 2013.08.15.
    'TimelineTip3'          => 'Klik på baggrunden for at zoome ind på en mindre tidsperiode omkring dit klik.',              // Added 2013.08.15.
    'TimelineTip4'          => 'Brug kontrollerne nedenfor for at zoome ud eller navigere frem eller tilbage i tiden.',              // Added 2013.08.15.
    'Timestamp'             => 'Tidsstempel',
    'TimestampLabelFormat'  => 'Tidsstempel Mærkat Format',
    'TimestampLabelSize'    => 'Font Størrelse',
    'TimestampLabelX'       => 'Tidsstempel Mærkat X',
    'TimestampLabelY'       => 'Tidsstempel Mærkat Y',
    'Today'                 => 'Idag',
    'Tools'                 => 'Værktøjer',
    'Total'                 => 'Total',
    'TotalBrScore'          => 'Total<br/>Score',
    'TrackDelay'            => 'Track Delay',
    'TrackMotion'           => 'Track Motion',
    'Triggers'              => 'Triggere',
    'TurboPanSpeed'         => 'Turbo Pan Speed',
    'TurboTiltSpeed'        => 'Turbo Tilt Speed',
    'Type'                  => 'Type',
    'Unarchive'             => 'Ikke Arkiveret',
    'Undefined'             => 'Udefineret',
    'Units'                 => 'Enheder',
    'Unknown'               => 'Ukendt',
    'Update'                => 'Opdater',
    'UpdateAvailable'       => 'En opdatering til ZoneMinder er tilgængelig.',
    'UpdateNotNecessary'    => 'Ingen opdatering er nødvendig.',
    'Updated'               => 'Opdateret',
    'Upload'                => 'Upload',
    'UseFilter'             => 'Anvend Filter',
    'UseFilterExprsPost'    => '&nbsp;filter&nbsp;udtryk', // This is used at the end of the phrase 'use N filter expressions'
    'UseFilterExprsPre'     => 'Anvend&nbsp;', // This is used at the beginning of the phrase 'use N filter expressions'
    'UsedPlugins'           => 'Anvendte Plugins',
    'User'                  => 'Bruger',
    'Username'              => 'Brugernavn',
    'Users'                 => 'Brugere',
	'V4L'					=> 'V4L',
	'V4LCapturesPerFrame'	=> 'Captures Per Frame',
	'V4LMultiBuffer'		=> 'Multi Buffering',
    'Value'                 => 'Værdi',
    'Version'               => 'Version',
    'VersionIgnore'         => 'Ignorer denne værdi',
    'VersionRemindDay'      => 'Påmind igen om 1 dag',
    'VersionRemindHour'     => 'Påmind igen om 1 time',
    'VersionRemindNever'    => 'Påmind ikke om nye versioner',
    'VersionRemindWeek'     => 'Påmind igen om 1 uge',
    'Video'                 => 'Video',
    'VideoFormat'           => 'Video Format',
    'VideoGenFailed'        => 'Video Generering Fejlede!',
    'VideoGenFiles'         => 'Existerende Video Filer',
    'VideoGenNoFiles'       => 'Ingen Video Filer Fundet',
    'VideoGenParms'         => 'Video Genererings Parametre',
    'VideoGenSucceeded'     => 'Video Generering Succeeded!',
    'VideoSize'             => 'Video Størrelse',
    'VideoWriter'           => 'Video Skriver',
    'View'                  => 'Vis',
    'ViewAll'               => 'Vis Alle',
    'ViewEvent'             => 'Vis Hændelse',
    'ViewPaged'             => 'Vis Sidevis',
    'Wake'                  => 'Vågen',
    'WarmupFrames'          => 'Opvarmningsbilleder',
    'Watch'                 => 'Ur',
    'Web'                   => 'Web',
    'WebColour'             => 'Web Farve',
    'WebSiteUrl'           => 'Website URL',            // Added - 2018-08-30
    'Week'                  => 'Uge',
    'White'                 => 'Hvid',
    'WhiteBalance'          => 'Hvidbalance',
    'Wide'                  => 'Bred',
    'X'                     => 'X',
    'X10'                   => 'X10',
    'X10ActivationString'   => 'X10 Activerings Streng',
    'X10InputAlarmString'   => 'X10 Input Alarm Streng',
    'X10OutputAlarmString'  => 'X10 Output Alarm Streng',
    'Y'                     => 'Y',
    'Yes'                   => 'Ja',
    'YouNoPerms'            => 'Du har ikke tilladelse til at tilgå denne ressurse.',
    'Zone'                  => 'Zone',
    'ZoneAlarmColour'       => 'Alarm Farve (Rød/Grøn/Blå)',
    'ZoneArea'              => 'Zone Område',
    'ZoneExtendAlarmFrames' => 'Udvid Antal Alarm Rammer',
    'ZoneFilterSize'        => 'Filter Bredde/Højde (pixels)',
    'ZoneMinMaxAlarmArea'   => 'Min/Max Alarmeret Område',
    'ZoneMinMaxBlobArea'    => 'Min/Max Blob Område',
    'ZoneMinMaxBlobs'       => 'Min/Max Blobs',
    'ZoneMinMaxFiltArea'    => 'Min/Max Filtreret Område',
    'ZoneMinMaxPixelThres'  => 'Min/Max Pixel Grænseværdi (0-255)',
    'ZoneMinderLog'         => 'ZoneMinder Log',
    'ZoneOverloadFrames'    => 'Antal Rammer At Ignorere Efter Overload',
    'Zones'                 => 'Zoner',
    'Zoom'                  => 'Zoom',
    'ZoomIn'                => 'Zoom Ind',
    'ZoomOut'               => 'Zoom Ud',
);

// Complex replacements with formatting and/or placements, must be passed through sprintf
$CLANG = array(
    'CurrentLogin'          => 'Nuværende login er \'%1$s\'',
    'EventCount'            => '%1$s %2$s', // For example '37 Events' (from Vlang below)
    'LastEvents'            => 'Sidste %1$s %2$s', // For example 'Last 37 Events' (from Vlang below)
    'LatestRelease'         => 'Sidste release er v%1$s, du har v%2$s.',
    'MonitorCount'          => '%1$s %2$s', // For example '4 Monitors' (from Vlang below)
    'MonitorFunction'       => 'Monitor %1$s Function',
    'RunningRecentVer'      => 'Du kører den nyeste version af ZoneMinder, v%s.',
    'VersionMismatch'       => 'Version misforhold, system er version %1$s, database er %2$s.',
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
// 'Potato' => array( 1=>'Potati', 2=>'Potaton', 3=>'Potaten' ),
//
// and the zmVlang function decides that the first form is used for counts ending in
// 0, 5-9 or 11-19 and the second form when ending in 1 etc.
//

// Variable arrays expressing plurality, see the zmVlang description above
$VLANG = array(
    'Event'                 => array( 0=>'Hændelser', 1=>'Hændelse', 2=>'Hændelser' ),
    'Monitor'               => array( 0=>'Monitorer', 1=>'Monitor', 2=>'Monitorer' ),
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
//echo sprintf( $CLANG['MonitorCount'], count($monitors), zmVlang( $VLANG['VlangMonitor'], count($monitors) ) );

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
        'OPTIONS_RTSPTrans' => array(
		'Help' => "This sets the RTSP Transport Protocol for FFmpeg.~~ ".
                          "TCP - Use TCP (interleaving within the RTSP control channel) as transport protocol.~~".
                          "UDP - Use UDP as transport protocol. Higher resolution cameras have experienced some 'smearing' while using UDP, if so try TCP~~".
                          "UDP Multicast - Use UDP Multicast as transport protocol~~".
                          "HTTP - Use HTTP tunneling as transport protocol, which is useful for passing proxies.~~"
	),
	'OPTIONS_LIBVLC' => array(
		'Help' => "Parameters in this field are passed on to libVLC. Multiple parameters can be separated by ,~~ ".
		          "Examples (do not enter quotes)~~~~".
		          "\"--rtp-client-port=nnn\" Set local port to use for rtp data~~~~". 
		          "\"--verbose=2\" Set verbosity of libVLC"
	),
	'OPTIONS_EXIF' => array(
		'Help' => "Enable this option to embed EXIF data into each jpeg frame."
	),
	'OPTIONS_RTSPDESCRIBE' => array(
		'Help' => "Sometimes, during the initial RTSP handshake, the camera will send an updated media URL. ".
		          "Enable this option to tell ZoneMinder to use this URL. Disable this option to ignore the ".
		          "value from the camera and use the value as entered in the monitor configuration~~~~". 
		          "Generally this should be enabled. However, there are cases where the camera can get its".
		          "own URL incorrect, such as when the camera is streaming through a firewall"),
	'OPTIONS_MAXFPS' => array(
		'Help' => "This field has certain limitations when used for non-local devices.~~ ".
		          "Failure to adhere to these limitations will cause a delay in live video, irregular frame skipping, ".
		          "and missed events~~".
		          "For streaming IP cameras, do not use this field to reduce the frame rate. Set the frame rate in the".
                          " camera, instead. You can, however, use a value that is slightly higher than the frame rate in the camera. ".
		          "In this case, this helps keep the cpu from being overtaxed in the event of a network problem.~~". 
		          "Some, mostly older, IP cameras support snapshot mode. In this case ZoneMinder is actively polling the camera ".
		          "for new images. In this case, it is safe to use the field."
	),
	
//    'LANG_DEFAULT' => array(
//        'Prompt' => "This is a new prompt for this option",
//        'Help' => "This is some new help for this option which will be displayed in the popup window when the ? is clicked"
//    ),
);

?>
