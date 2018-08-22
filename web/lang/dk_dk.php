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
    'SystemLog'             => 'System Log',
    'DateTime'              => 'Dato/Tid',
    'Component'             => 'Komponent',
    'Pid'                   => 'PID',
    'Level'                 => 'Niveau',
    'Message'               => 'Meddelelse',
    'Line'                  => 'Linie',
    'More'                  => 'Mere',
    'Clear'                 => 'Slet',
    '24BitColour'           => '24 bit farve',
    '32BitColour'           => '32 bit farve',
    '8BitGrey'              => '8 bit gråskala',
    'Action'                => 'Handling',
    'Actual'                => 'Aktuel',
    'AddNewControl'         => 'Tilføj Ny Kontrol',
    'AddNewMonitor'         => 'Tilføj Ny Monitor',
    'AddNewServer'          => 'Tilføj Ny Server',
    'AddNewUser'            => 'Tilføj Ny Bruger',
    'AddNewZone'            => 'Tilføj Ny Zone',
    'Alarm'                 => 'Alarm',
    'AlarmBrFrames'         => 'Alarm<br/>Rammer',
    'AlarmFrame'            => 'Alarm Ramme',
    'AlarmFrameCount'       => 'Antal Alarm Rammer',
    'AlarmLimits'           => 'Alarm Grænser',
    'AlarmMaximumFPS'       => 'Alarm Maksimum FPS',
    'AlarmPx'               => 'Alarm Px',
    'AlarmRefImageBlendPct' => 'Alarm Reference Billede Blandings %',
    'AlarmRGBUnset'         => 'Du skal vælge en alarm RGB farve',
    'Alert'                 => 'Advarsel',
    'All'                   => 'Alle',
    'AnalysisFPS'           => 'Analyse FPS',
    'AnalysisUpdateDelay'   => 'Analyse Opdaterings Forsinkelse',
    'Apply'                 => 'Udfør',
    'ApplyingStateChange'   => 'Udfører tilstandsændring',
    'ArchArchived'          => 'Kun arkiverede',
    'Archive'               => 'Arkivér',
    'Archived'              => 'Arkiverede',
    'ArchUnarchived'        => 'Kun ikke-arkiverede',
    'Area'                  => 'Område',
    'AreaUnits'             => 'Område (px/%)',
    'AttrAlarmFrames'       => 'Alarm Rammer',
    'AttrArchiveStatus'     => 'Arkiverings Status',
    'AttrAvgScore'          => 'Middel Score',
    'AttrCause'             => 'Årsag',
    'AttrDate'              => 'Dato',
    'AttrDateTime'          => 'Dato/Tid',
    'AttrDiskBlocks'        => 'Disk Blokke',
    'AttrDiskPercent'       => 'Disk Procent',
    'AttrDuration'          => 'Varighed',
    'AttrFrames'            => 'Rammer',
    'AttrId'                => 'Id',
    'AttrMaxScore'          => 'Max. Score',
    'AttrMonitorId'         => 'Monitor Id',
    'AttrMonitorName'       => 'Monitor Navn',
    'AttrServer'            => 'Server',
    'AttrName'              => 'Navn',
    'AttrNotes'             => 'Noter',
    'AttrSystemLoad'        => 'System Belastning',
    'AttrTime'              => 'Tid',
    'AttrTotalScore'        => 'Total Score',
    'AttrWeekday'           => 'Ugedag',
    'Auto'                  => 'Auto',
    'AutoStopTimeout'       => 'Auto Stop Timeout',
    'Available'             => 'Tilgængelig',
    'AvgBrScore'            => 'Middel<br/>Score',
    'Available'             => 'Tilgængelig',
    'Background'            => 'Baggrund',
    'BackgroundFilter'      => 'Kør filteret i baggrunden',
    'BadAlarmFrameCount'    => 'Antal alarm rammer skal være et positivt heltal',
    'BadAlarmMaxFPS'        => 'Alarm Maximum FPS skal være et positivt heltal eller flydende tal',
    'BadAnalysisFPS'        => 'Analyse FPS skal være et positivt heltal eller flydende tal',
    'BadAnalysisUpdateDelay'=> 'Analyse opdaterings forsinkelse skal være et heltal på 0 eller mere',
    'BadChannel'            => 'Kanal skal sættes til et heltal på 0 eller mere',
    'BadDevice'             => 'Enhed skal sættes til en gyldig værdi',
    'BadFormat'             => 'Format skal sættes til en gyldig værdi',
    'BadFPSReportInterval'  => 'Antal FPS report interval buffere skal være et heltal på 0 eller mere',
    'BadFrameSkip'          => 'Antal Frame skip skal være et heltal på 0 eller mere',
    'BadMotionFrameSkip'    => 'Antal Motion Frame skip skal være et heltal på 0 eller mere',
    'BadHeight'             => 'Højde skal sættes til en gyldig værdi',
    'BadHost'               => 'Host skal vare en gyldig IP adresse eller hostname, inkludér ikke http://',
    'BadImageBufferCount'   => 'Billed buffer størrelse skal være et heltal på 10 eller mere',
    'BadLabelX'             => 'Mærkat X co-ordinaten skal sættes til et heltal på 0 eller mere',
    'BadLabelY'             => 'Mærkat Y co-ordinaten skal sættes til et heltal på 0 eller mere',
    'BadMaxFPS'             => 'Maximum FPS skal være et positivt heltal eller flydende tal',
    'BadNameChars'          => 'Navne kan kun indeholde alfanumeriske tegn samt mellemrum, bindestreg og understregning',
    'BadPalette'            => 'Palette skal sættes til en gyldig værdi',
    'BadColours'            => 'Målfarven skal sættes til en gyldig værdi',
    'BadPath'               => 'Sti skal sættes til en gyldig værdi',
    'BadPort'               => 'Port skal sættes til et gyldigt nummer',
    'BadPostEventCount'     => 'Antal rammer efter hændelsen skal være et heltal på 0 eller mere',
    'BadPreEventCount'      => 'Antal rammer før hændelsen skal være mindst 0 samt mindre en billedbufferstørrelsen',
    'BadRefBlendPerc'       => 'Reference blandings procentdelen skal være et positivt heltal',
    'BadSectionLength'      => 'Sektionslængden skal være et heltal på 30 eller mere',
    'BadSignalCheckColour'  => 'Signal check farve skal være en gyldig RGB farve streng',
    'BadStreamReplayBuffer' => 'Videostrøm genspilsbufferen skal sættes til et heltal på 0 eller mere',
    'BadWarmupCount'        => 'Opvarmnings rammer skal være et heltal på 0 eller mere',
    'BadWebColour'          => 'Web farve skal være en gyldigt web farve streng',
    'BadWidth'              => 'Bredde skal sættes til en gyldig værdi',
    'Bandwidth'             => 'Båndbredde',
    'BandwidthHead'         => 'Båndbredde',	// This is the end of the bandwidth status on the top of the console, different in many language due to phrasing
    'BlobPx'                => 'Blob Px',
    'Blobs'                 => 'Blobs',
    'BlobSizes'             => 'Blob Størrelser',
    'Brightness'            => 'Lysstyrke',
    'Buffer'                => 'Buffer',
    'Buffers'               => 'Buffere',
    'CanAutoFocus'          => 'Can Auto Focus',
    'CanAutoGain'           => 'Can Auto Gain',
    'CanAutoIris'           => 'Can Auto Iris',
    'CanAutoWhite'          => 'Can Auto White Bal.',
    'CanAutoZoom'           => 'Can Auto Zoom',
    'Cancel'                => 'Fortryd',
    'CancelForcedAlarm'     => 'Fortryd Tvungen Alarm',
    'CanFocusAbs'           => 'Can Focus Absolute',
    'CanFocus'              => 'Can Focus',
    'CanFocusCon'           => 'Can Focus Continuous',
    'CanFocusRel'           => 'Can Focus Relative',
    'CanGainAbs'            => 'Can Gain Absolute',
    'CanGain'               => 'Can Gain ',
    'CanGainCon'            => 'Can Gain Continuous',
    'CanGainRel'            => 'Can Gain Relative',
    'CanIrisAbs'            => 'Can Iris Absolute',
    'CanIris'               => 'Can Iris',
    'CanIrisCon'            => 'Can Iris Continuous',
    'CanIrisRel'            => 'Can Iris Relative',
    'CanMoveAbs'            => 'Can Move Absolute',
    'CanMove'               => 'Can Move',
    'CanMoveCon'            => 'Can Move Continuous',
    'CanMoveDiag'           => 'Can Move Diagonally',
    'CanMoveMap'            => 'Can Move Mapped',
    'CanMoveRel'            => 'Can Move Relative',
    'CanPan'                => 'Can Pan' ,
    'CanReset'              => 'Can Reset',
    'CanSetPresets'         => 'Can Set Presets',
    'CanSleep'              => 'Can Sleep',
    'CanTilt'               => 'Can Tilt',
    'CanWake'               => 'Can Wake',
    'CanWhiteAbs'           => 'Can White Bal. Absolute',
    'CanWhiteBal'           => 'Can White Bal.',
    'CanWhite'              => 'Can White Balance',
    'CanWhiteCon'           => 'Can White Bal. Continuous',
    'CanWhiteRel'           => 'Can White Bal. Relative',
    'CanZoomAbs'            => 'Can Zoom Absolute',
    'CanZoom'               => 'Can Zoom',
    'CanZoomCon'            => 'Can Zoom Continuous',
    'CanZoomRel'            => 'Can Zoom Relative',
    'CaptureHeight'         => 'Capture Højde',
    'CaptureMethod'         => 'Capture Metode',
    'CaptureResolution'     => 'Capture Opløsning',
    'CapturePalette'        => 'Capture Palette',
    'CaptureWidth'          => 'Capture Bredde',
    'Cause'                 => 'Årsag',
    'CheckMethod'           => 'Alarm Check Metode',
    'ChooseDetectedCamera'  => 'Vælg Fundet Kamera',
    'ChooseFilter'          => 'Vælg Filter',
    'ChooseLogFormat'       => 'Vælg et lognings format',
    'ChooseLogSelection'    => 'Vælg et lognings udvælgelse',
    'ChoosePreset'          => 'Vælg Forudindstilling',
    'CloneMonitor'          => 'Klon Monitor',
    'Close'                 => 'Luk',
    'Colour'                => 'Farve',
    'Command'               => 'Kommando',
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
    'ControlAddress'        => 'Control Address',
    'ControlCap'            => 'Control Capability',
    'ControlCaps'           => 'Control Capabilities',
    'Control'               => 'Control',
    'ControlDevice'         => 'Control Device',
    'Controllable'          => 'Controllable',
    'ControlType'           => 'Control Type',
    'Current'               => 'Nuværende',
    'Cycle'                 => 'Cyklisk',
    'CycleWatch'            => 'Cyklisk Overvågning',
    'Day'                   => 'Dag',
    'Debug'                 => 'Fejlfind',
    'DefaultRate'           => 'Standard Rate',
    'DefaultScale'          => 'Standard Skalering',
    'DefaultView'           => 'Standard Visning',
    'Deinterlacing'         => 'Deinterlacing',
    'RTSPDescribe'          => 'Brug RTSP Response Media URL',
    'Delay'                 => 'Forsilkelse',
    'DeleteAndNext'         => 'Slet &amp; Næste',
    'DeleteAndPrev'         => 'Slet &amp; Forrige',
    'Delete'                => 'Slet',
    'DeleteSavedFilter'     => 'Slet gemt filter',
    'Description'           => 'Beskrivelse',
    'DetectedCameras'       => 'Fundne Kameraer',
    'DetectedProfiles'      => 'Fundne Profiler',
    'DeviceChannel'         => 'Enheds Kanal',
    'DeviceFormat'          => 'Enheds Format',
    'DeviceNumber'          => 'Enheds Number',
    'DevicePath'            => 'Sti Til Enhed',
    'Device'                => 'Enheds',
    'Devices'               => 'Enheder',
    'Dimensions'            => 'Dimensioner',
    'DisableAlarms'         => 'Deaktiver Alarmer',
    'Disk'                  => 'Disk',
    'Display'               => 'Display',
    'Displaying'            => 'Displaying',
    'DonateAlready'         => 'Nej, jeg har allerede doneret',
    'DonateEnticement'      => 'You\'ve been running ZoneMinder for a while now and hopefully are finding it a useful addition to your home or workplace security. Although ZoneMinder is, and will remain, free and open source, it costs money to develop and support. If you would like to help support future development and new features then please consider donating. Donating is, of course, optional but very much appreciated and you can donate as much or as little as you like.<br/><br/>If you would like to donate please select the option below or go to http://www.zoneminder.com/donate.html in your browser.<br/><br/>Thank you for using ZoneMinder and don\'t forget to visit the forums on ZoneMinder.com for support or suggestions about how to make your ZoneMinder experience even better.',
    'Donate'                => 'Venligst Donér',
    'DonateRemindDay'       => 'Ikke endnu, påmind igen on 1 dag',
    'DonateRemindHour'      => 'Ikke endnu, påmind igen on 1 time',
    'DonateRemindMonth'     => 'Ikke endnu, påmind igen on 1 måned',
    'DonateRemindNever'     => 'Nej, jeg ønsker ikke at donere, påmind ikke igen',
    'DonateRemindWeek'      => 'Ikke endnu, påmind igen on 1 uge',
    'DonateYes'             => 'Ja, jeg vil gerne donere nu',
    'DoNativeMotionDetection'=> 'Do Native Motion Detection',
    'Download'              => 'Download',
    'DuplicateMonitorName'  => 'Dupliket Monitor Navn',
    'Duration'              => 'Varighed',
    'Edit'                  => 'Ret',
    'Email'                 => 'Email',
    'EnableAlarms'          => 'Aktivér Alarmer',
    'Enabled'               => 'Virksom',
    'EnterNewFilterName'    => 'Indtast nyt filternavn',
    'ErrorBrackets'         => 'Fejl, venligst check, at du har samme antal open og lukke klammer',
    'Error'                 => 'Fejl',
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
    'ExportDetails'         => 'Exporter Hændelses Detaljer',
    'Exif'                  => 'Indlejre EXIF data i billede',
    'Export'                => 'Exporter',
    'ExportFailed'          => 'Export Mislykkedes',
    'ExportFormat'          => 'Export Fil Format',
    'ExportFormatTar'       => 'Tar',
    'ExportFormatZip'       => 'Zip',
    'ExportFrames'          => 'Exporter Ramme Detaljer',
    'ExportImageFiles'      => 'Exporter billed filer',
    'ExportLog'             => 'Export Log',
    'Exporting'             => 'Exporterer',
    'ExportMiscFiles'       => 'Exporter Andre Filer (hvis tilstede)',
    'ExportOptions'         => 'Export Indstillinger',
    'ExportSucceeded'       => 'Export Lykkedes',
    'ExportVideoFiles'      => 'Exporter Video Filer (hvis tilstede)',
    'Far'                   => 'Fjern',
    'FastForward'           => 'Hurtigt Frem',
    'Feed'                  => 'Feed',
    'Ffmpeg'                => 'Ffmpeg',
    'File'                  => 'Fil',
    'FilterArchiveEvents'   => 'Arkiver alle matchende',
    'FilterDeleteEvents'    => 'Slet alle matchende',
    'FilterEmailEvents'     => 'Email detaljer for alle matchende',
    'FilterExecuteEvents'   => 'Udfør kommando for alle matchende',
    'FilterLog'             => 'Filter log',
    'FilterMessageEvents'   => 'Meddel detaljer for alle matchende',
    'FilterPx'              => 'Filter Px',
    'Filter'                => 'Filter',
    'Filters'               => 'Filtre',
    'FilterUnset'           => 'Du skal angive filter bredde og højde',
    'FilterUploadEvents'    => 'Upload alle match',
    'FilterVideoEvents'     => 'Opret video for alle match',
    'First'                 => 'Første',
    'FlippedHori'           => 'Spejlet Horizontalt',
    'FlippedVert'           => 'Spejlet Vertikalt',
    'FnNone'                => 'None',            // Added 2013.08.16.
    'FnMonitor'             => 'Monitor',            // Added 2013.08.16.
    'FnModect'              => 'Modect',            // Added 2013.08.16.
    'FnRecord'              => 'Record',            // Added 2013.08.16.
    'FnMocord'              => 'Mocord',            // Added 2013.08.16.
    'FnNodect'              => 'Nodect',            // Added 2013.08.16.
    'Focus'                 => 'Focus',
    'ForceAlarm'            => 'Force Alarm',
    'Format'                => 'Format',
    'FPS'                   => 'fps',
    'FPSReportInterval'     => 'FPS Rapport Interval',
    'Frame'                 => 'Ramme',
    'FrameId'               => 'Ramme Id',
    'FrameRate'             => 'Billedhastighed',
    'Frames'                => 'Rammer',
    'FrameSkip'             => 'Spring over antal rammer',
    'MotionFrameSkip'       => 'Spring over antal bevægelsesrammer',
    'FTP'                   => 'FTP',
    'Func'                  => 'Funk',
    'Function'              => 'Funktion',
    'Gain'                  => 'Gain',
    'General'               => 'Generelt',
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
    'HighBW'                => 'High&nbsp;B/W',
    'High'                  => 'Høj',
    'Home'                  => 'Hjemme',
    'Hostname'              => 'Hostname',
    'Hour'                  => 'Time',
    'Hue'                   => 'Farvetone',
    'Id'                    => 'Id',
    'Idle'                  => 'Afventende',
    'Ignore'                => 'Ignorer',
    'ImageBufferSize'       => 'Billed Buffer Størrelse (rammer)',
    'Image'                 => 'Billede',
    'Images'                => 'Billeder',
    'Include'               => 'Inkluder',
    'In'                    => 'I',
    'Inverted'              => 'Inverteret',
    'Iris'                  => 'Blænde',
    'KeyString'             => 'Nøgle Streng',
    'Label'                 => 'Mærkat',
    'Language'              => 'Sprog',
    'Last'                  => 'Sidste',
    'Layout'                => 'Layout',
    'Libvlc'                => 'Libvlc',
    'LimitResultsPost'      => 'resultater', // This is used at the end of the phrase 'Limit to first N results only'
    'LimitResultsPre'       => 'Begræns til kun de første', // This is used at the beginning of the phrase 'Limit to first N results only'
    'LinkedMonitors'        => 'Sammenkædede Monitorer',
    'List'                  => 'Liste',
    'Load'                  => 'Belastning',
    'Local'                 => 'Lokal',
    'Log'                   => 'Log',
    'Logs'                  => 'Logs',
    'Logging'               => 'Logning',
    'LoggedInAs'            => 'Logget ind som',
    'LoggingIn'             => 'Logger ind',
    'Login'                 => 'Logind',
    'Logout'                => 'Logud',
    'LowBW'                 => 'Lav&nbsp;B/W',
    'Low'                   => 'Lav',
    'Main'                  => 'Hoved',
    'Man'                   => 'Man',
    'Manual'                => 'Manuel',
    'Mark'                  => 'Markér',
    'MaxBandwidth'          => 'Max Båndbredde',
    'MaxBrScore'            => 'Max.<br/>Score',
    'MaxFocusRange'         => 'Max Focus Range',
    'MaxFocusSpeed'         => 'Max Focus Speed',
    'MaxFocusStep'          => 'Max Focus Step',
    'MaxGainRange'          => 'Max Gain Range',
    'MaxGainSpeed'          => 'Max Gain Speed',
    'MaxGainStep'           => 'Max Gain Step',
    'MaximumFPS'            => 'Maximum FPS',
    'MaxIrisRange'          => 'Max Iris Range',
    'MaxIrisSpeed'          => 'Max Iris Speed',
    'MaxIrisStep'           => 'Max Iris Step',
    'Max'                   => 'Max',
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
    'MediumBW'              => 'Medium&nbsp;B/W',
    'Medium'                => 'Medium',
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
    'MonitorIds'            => 'Monitor&nbsp;Ids',
    'Monitor'               => 'Monitor',
    'MonitorPresetIntro'    => 'Vælg en passende forudindstilling fra listen herunder.<br/><br/>Vær opmærksom på, at dette kan overskrive værdier, du allerede har angivet for den aktuelle monitor.<br/><br/>',
    'MonitorPreset'         => 'Monitor Forudindstillinger',
    'MonitorProbeIntro'     => 'Listen herunder viser fundne analoge og nætværks kameraer samt hvorvidt de allerede er i brug eller tilgængelige for udvælgelse.<br/><br/>Vælg det ønskede fra listen herunder.<br/><br/>Vær opmærksom på, at muligvis ikke alle kameraer er fundet og at valg af et kamera kan overskrive værdier, du allerede har angivet for den aktuelle monitor.<br/><br/>',
    'MonitorProbe'          => 'Monitor Probe',
    'Monitors'              => 'Monitorer',
    'Montage'               => 'Montage',
    'MontageReview'         => 'Montage Review',
    'Month'                 => 'Måned',
    'Move'                  => 'Bevæg',
    'MtgDefault'            => 'Standard',                 // Added 2013.08.15.
    'Mtg2widgrd'            => '2-bred gitter',              // Added 2013.08.15.
    'Mtg3widgrd'            => '3-bred gitter',              // Added 2013.08.15.
    'Mtg4widgrd'            => '4-bred gitter',              // Added 2013.08.15.
    'Mtg3widgrx'            => '3-bred gitter, skaleret, forstørret ved alarm',              // Added 2013.08.15.
    'MustBeGe'              => 'Skal være større end eller lig med',
    'MustBeLe'              => 'Skal være mindre end eller lig med',
    'MustConfirmPassword'   => 'Du skal bekræfte adgangskoden',
    'MustSupplyPassword'    => 'Du skal levere en adgangskode',
    'MustSupplyUsername'    => 'Du skal levere et brugernavn',
    'Name'                  => 'Navn',
    'Near'                  => 'Nær',
    'Network'               => 'Netværk',
    'NewGroup'              => 'Ny Gruppe',
    'NewLabel'              => 'Ny Mærkat',
    'New'                   => 'Ny',
    'NewPassword'           => 'Ny Adgangskode',
    'NewState'              => 'Ny Tilstand',
    'NewUser'               => 'Ny bruger',
    'Next'                  => 'Næste',
    'NoDetectedCameras'     => 'Ingen Detected Cameras',
    'NoDetectedProfiles'    => 'Ingen Fundne Profiler',
    'NoFramesRecorded'      => 'Der er ingen billeder optaget for denne hændelse',
    'NoGroup'               => 'Ingen gruppe',
    'NoneAvailable'         => 'Ingen tilgængelig',
    'None'                  => 'Ingen',
    'No'                    => 'Nej',
    'Normal'                => 'Normalt',
    'NoSavedFilters'        => 'IngenGemteFiltre',
    'NoStatisticsRecorded'  => 'Der er ingen statistik noteret for denne hændelse/ramme',
    'Notes'                 => 'Noter',
    'NumPresets'            => 'Num Forudinst.',
    'Off'                   => 'Fra',
    'On'                    => 'Til',
    'OnvifProbe'            => 'ONVIF',
    'OnvifProbeIntro'       => 'Listen nedenfor viser fundne ONVIF kameraer samt hvorvidt de allerede er i brug eller tilgængelige for udvælgelse.<br/><br/>Vælg det ønskede fra listen herunder.<br/><br/>Vær opmærksom på, at muligvis ikke alle kameraer er fundet og at valg af et kamera kan overskrive værdier, du allerede har angivet for den aktuelle monitor.<br/><br/>',
    'OnvifCredentialsIntro' => 'Venligst lever brugernavn og adgangskodefor de valgte kamera.<br/>Hvis der ikke er oprettet nogen bruger for kameraet, så vil brugeren givet her blive oprettet med den angivne adgangskode.<br/><br/>',
    'Open'                  => 'Åben',
    'OpEq'                  => 'lig med',
    'OpGtEq'                => 'større end eller lig med',
    'OpGt'                  => 'større end',
    'OpIn'                  => 'indeholdt i',
    'OpLtEq'                => 'mindre end eller lig med',
    'OpLt'                  => 'mindre end',
    'OpMatches'             => 'matcher',
    'OpNe'                  => 'ikke lig med',
    'OpNotIn'               => 'ikke indeholdt i',
    'OpNotMatches'          => 'matcher ikke',
    'OptionalEncoderParam'  => 'Optionelle Encoder Parametre',
    'OptionHelp'            => 'Indstillinger hjælp',
    'OptionRestartWarning'  => 'Disse ændringer har muligvis ikke fuld effekt\nmens systemet er kørende. Når du har\nafsluttet dine ændringer, skal du huske at\ngenstarte ZoneMinder.',
    'Options'               => 'Indstillinger',
    'Order'                 => 'Rækkefølge',
    'OrEnterNewName'        => 'eller indtast nyt navn',
    'Orientation'           => 'Orientering',
    'Out'                   => 'Ud',
    'OverwriteExisting'     => 'Overskriv Eksisterende',
    'Paged'                 => 'Sidevis',
    'PanLeft'               => 'Pan Left',
    'Pan'                   => 'Pan',
    'PanRight'              => 'Pan Right',
    'PanTilt'               => 'Pan/Tilt',
    'Parameter'             => 'Parameter',
    'Password'              => 'Adgangskode',
    'PasswordsDifferent'    => 'Den nye og den bekræftende adgangskode er forskellige',
    'Paths'                 => 'Stier',
    'Pause'                 => 'Pause',
    'PhoneBW'               => 'Telefon&nbsp;B/W',
    'Phone'                 => 'Telefon',
    'PixelDiff'             => 'Pixel Forskel',
    'Pixels'                => 'pixels',
    'PlayAll'               => 'Afspil Alle',
    'Play'                  => 'Afspil',
    'Plugins'               => 'Plugins',
    'PleaseWait'            => 'Vent venligst',
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
    'Rate'                  => 'Rate',
    'RecaptchaWarning'      => 'Your reCaptcha secret key is invalid. Please correct it, or reCaptcha will not work', // added Sep 24 2015 - PP
    'RecordAudio'           => 'Skal lydsporet gemmes sammen med en hændelse.',
    'Real'                  => 'Naturtro',
    'Record'                => 'Optag',
    'RefImageBlendPct'      => 'Reference Billede Blandings %',
    'Refresh'               => 'Genindlæs',
    'RemoteHostName'        => 'Remote Host Name',
    'RemoteHostPath'        => 'Remote Host Path',
    'RemoteHostSubPath'     => 'Remote Host SubPath',
    'RemoteHostPort'        => 'Remote Host Port',
    'RemoteImageColours'    => 'Remote Image Colours',
    'RemoteMethod'          => 'Remote Method',
    'RemoteProtocol'        => 'Remote Protocol',
    'Remote'                => 'Remote',
    'Rename'                => 'Omdøb',
    'ReplayAll'             => 'Alle Hændelser',
    'ReplayGapless'         => 'Hændelser uafbrudt',
    'Replay'                => 'Genafspil',
    'ReplaySingle'          => 'Enkelt Hændelse',
    'ResetEventCounts'      => 'Nulstil Hændelses Tæller',
    'Reset'                 => 'Nulstil',
    'Restarting'            => 'Genstarter',
    'Restart'               => 'Genstart',
    'RestrictedCameraIds'   => 'Restricted Camera Ids',
    'RestrictedMonitors'    => 'Restricted Monitors',
    'ReturnDelay'           => 'Return Delay',
    'ReturnLocation'        => 'Return Location',
    'Rewind'                => 'Hurtigt Tilbage',
    'RotateLeft'            => 'Roter til venstrte',
    'RotateRight'           => 'Roter til højre',
    'RTSPTransport'         => 'RTSP Transport Protocol',
    'RunLocalUpdate'        => 'Kør venligst zmupdate.pl for at opdatere',
    'RunMode'               => 'Driftsmåde',
    'Running'               => 'Kørende',
    'RunState'              => 'Drift Tilstand',
    'SaveAs'                => 'Gem som',
    'SaveFilter'            => 'Gem Filter',
    'SaveJPEGS'             => 'Gem JPEGs',
    'Save'                  => 'Gem',
    'Scale'                 => 'Skaler',
    'Score'                 => 'Score',
    'Secs'                  => 'Sek.',
    'Sectionlength'         => 'Sektions længde',
    'SelectMonitors'        => 'Vælg Monitorer',
    'Select'                => 'Vælg',
    'SelectFormat'          => 'Vælg Format',
    'SelectLog'             => 'Vælg Log',
    'SelfIntersecting'      => 'Polygonens kanter må ikke krydses',
    'SetNewBandwidth'       => 'Vælg ny båndbredde',
    'SetPreset'             => 'Set Preset',
    'Set'                   => 'Sæt',
    'Settings'              => 'Indstillinger',
    'ShowFilterWindow'      => 'Vis Filter Vindue',
    'ShowTimeline'          => 'Vis Tidslinie',
    'SignalCheckColour'     => 'Signal Check Colour',
    'Size'                  => 'Størrelse',
    'SkinDescription'       => 'SKift standard skin for denne computer',
    'CSSDescription'        => 'SKift standard css for denne computer',
    'Sleep'                 => 'Sover',
    'SortAsc'               => 'Voksende',
    'SortBy'                => 'Sortér efter',
    'SortDesc'              => 'Faldende',
    'Source'                => 'Kilde',
    'SourceColours'         => 'Kilde Farver',
    'SourcePath'            => 'Kilde Sti',
    'SourceType'            => 'Kilde Type',
    'SpeedHigh'             => 'High Speed',
    'SpeedLow'              => 'Low Speed',
    'SpeedMedium'           => 'Medium Speed',
    'Speed'                 => 'Speed',
    'SpeedTurbo'            => 'Turbo Speed',
    'Start'                 => 'Start',
    'State'                 => 'Tilstand',
    'Stats'                 => 'Stats',
    'Status'                => 'Status',
    'StepBack'              => 'Skridt Tilbage',
    'StepForward'           => 'Skridt Frem',
    'StepLarge'             => 'Langt Skridt',
    'StepMedium'            => 'Medium Skridt',
    'StepNone'              => 'Ingen Skridt',
    'StepSmall'             => 'Lille Skridt',
    'Step'                  => 'Skridt',
    'Stills'                => 'Stilbilleder',
    'Stopped'               => 'Stoppet',
    'Stop'                  => 'Stop',
    'StreamReplayBuffer'    => 'Stream Replay Image Buffer',
    'Stream'                => 'Stream',
    'Submit'                => 'Påtryk',
    'System'                => 'System',
    'TargetColorspace'      => 'Target colorspace',
    'Tele'                  => 'Tele',
    'Thumbnail'             => 'Thumbnail',
    'Tilt'                  => 'Tilt',
    'TimeDelta'             => 'Tidsforskel',
    'Timeline'              => 'Tidslinie',
    'TimelineTip1'          => 'Før musen over grafen for at vise snapshot billede og detaljer om hændelsen.',              // Added 2013.08.15.
    'TimelineTip2'          => 'Klip på de farvede områder af grafen, eller billedet, for at se hændelsen.',              // Added 2013.08.15.
    'TimelineTip3'          => 'Klik på baggrunden for at zoome ind på en mindre tidsperiode omkring dit klik.',              // Added 2013.08.15.
    'TimelineTip4'          => 'Brug kontrollerne nedenfor for at zoome ud eller navigere frem eller tilbage i tiden.',              // Added 2013.08.15.
    'TimestampLabelFormat'  => 'Tidsstempel Mærkat Format',
    'TimestampLabelX'       => 'Tidsstempel Mærkat X',
    'TimestampLabelY'       => 'Tidsstempel Mærkat Y',
    'TimestampLabelSize'    => 'Font Størrelse',
    'Timestamp'             => 'Tidsstempel',
    'TimeStamp'             => 'Tids stempel',
    'Time'                  => 'Tidspunkt',
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
    'UpdateAvailable'       => 'En opdatering til ZoneMinder er tilgængelig.',
    'UpdateNotNecessary'    => 'Ingen opdatering er nødvendig.',
    'Update'                => 'Opdater',
    'Upload'                => 'Upload',
    'Updated'               => 'Opdateret',
    'UsedPlugins'           => 'Anvendte Plugins',
    'UseFilterExprsPost'    => '&nbsp;filter&nbsp;udtryk', // This is used at the end of the phrase 'use N filter expressions'
    'UseFilterExprsPre'     => 'Anvend&nbsp;', // This is used at the beginning of the phrase 'use N filter expressions'
    'UseFilter'             => 'Anvend Filter',
    'Username'              => 'Brugernavn',
    'Users'                 => 'Brugere',
    'User'                  => 'Bruger',
    'Value'                 => 'Værdi',
    'VersionIgnore'         => 'Ignorer denne værdi',
    'VersionRemindDay'      => 'Påmind igen om 1 dag',
    'VersionRemindHour'     => 'Påmind igen om 1 time',
    'VersionRemindNever'    => 'Påmind ikke om nye versioner',
    'VersionRemindWeek'     => 'Påmind igen om 1 uge',
    'Version'               => 'Version',
    'VideoFormat'           => 'Video Format',
    'VideoGenFailed'        => 'Video Generering Fejlede!',
    'VideoGenFiles'         => 'Existerende Video Filer',
    'VideoGenNoFiles'       => 'Ingen Video Filer Fundet',
    'VideoGenParms'         => 'Video Genererings Parametre',
    'VideoGenSucceeded'     => 'Video Generering Succeeded!',
    'VideoSize'             => 'Video Størrelse',
    'VideoWriter'           => 'Video Skriver',
    'Video'                 => 'Video',
    'ViewAll'               => 'Vis Alle',
    'ViewEvent'             => 'Vis Hændelse',
    'ViewPaged'             => 'Vis Sidevis',
    'View'                  => 'Vis',
	'V4L'					=> 'V4L',
	'V4LCapturesPerFrame'	=> 'Captures Per Frame',
	'V4LMultiBuffer'		=> 'Multi Buffering',
    'Wake'                  => 'Vågen',
    'WarmupFrames'          => 'Opvarmningsbilleder',
    'Watch'                 => 'Ur',
    'WebColour'             => 'Web Farve',
    'Web'                   => 'Web',
    'Week'                  => 'Uge',
    'WhiteBalance'          => 'Hvidbalance',
    'White'                 => 'Hvid',
    'Wide'                  => 'Bred',
    'X10ActivationString'   => 'X10 Activerings Streng',
    'X10InputAlarmString'   => 'X10 Input Alarm Streng',
    'X10OutputAlarmString'  => 'X10 Output Alarm Streng',
    'X10'                   => 'X10',
    'X'                     => 'X',
    'Yes'                   => 'Ja',
    'YouNoPerms'            => 'Du har ikke tilladelse til at tilgå denne ressurse.',
    'Y'                     => 'Y',
    'ZoneAlarmColour'       => 'Alarm Farve (Rød/Grøn/Blå)',
    'ZoneArea'              => 'Zone Område',
    'ZoneFilterSize'        => 'Filter Bredde/Højde (pixels)',
    'ZoneMinderLog'         => 'ZoneMinder Log',
    'ZoneMinMaxAlarmArea'   => 'Min/Max Alarmeret Område',
    'ZoneMinMaxBlobArea'    => 'Min/Max Blob Område',
    'ZoneMinMaxBlobs'       => 'Min/Max Blobs',
    'ZoneMinMaxFiltArea'    => 'Min/Max Filtreret Område',
    'ZoneMinMaxPixelThres'  => 'Min/Max Pixel Grænseværdi (0-255)',
    'ZoneOverloadFrames'    => 'Antal Rammer At Ignorere Efter Overload',
    'ZoneExtendAlarmFrames' => 'Udvid Antal Alarm Rammer',
    'Zones'                 => 'Zoner',
    'Zone'                  => 'Zone',
    'ZoomIn'                => 'Zoom Ind',
    'ZoomOut'               => 'Zoom Ud',
    'Zoom'                  => 'Zoom',
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
