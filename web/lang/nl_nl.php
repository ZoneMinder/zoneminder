<?php
//
// ZoneMinder web Dutch language file, $Date: 2011-06-21 10:19:10 +0100 (Tue, 21 Jun 2011) $, $Revision: 3459 $
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

// ZoneMinder Dutch Translation by Alco (a.k. nightcrawler)
// Updated by Bernardus Jansen (bajansen)

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
// header( "Content-Type: text/html; charset=iso-8859-1" );

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
    '24BitColour'          => '24 bits kleuren',
    '32BitColour'          => '32 bits kleuren',
    '8BitGrey'             => '8 bits grijstinten',
    'Action'               => 'Actie',
    'Actual'               => 'Origineel',
    'AddNewControl'        => 'Nieuwe controle toevoegen',
    'AddNewMonitor'        => 'Nieuwe monitor toevoegen',
    'AddNewServer'         => 'Add New Server',         // Added - 2018-08-30
    'AddNewStorage'        => 'Add New Storage',        // Added - 2018-08-30
    'AddNewUser'           => 'Nieuwe gebruiker toevoegen',
    'AddNewZone'           => 'Nieuw gebied toevoegen',
    'Alarm'                => 'Alarm',
    'AlarmBrFrames'        => 'Alarm-<br/>frames',
    'AlarmFrame'           => 'Alarmframe',
    'AlarmFrameCount'      => 'Aantal alarmframes',
    'AlarmLimits'          => 'Alarmlimieten',
    'AlarmMaximumFPS'      => 'Alarm Maximum FPS',
    'AlarmPx'              => 'Alarm Px',
    'AlarmRGBUnset'        => 'U moet een RGB alarmkleur kiezen',
    'AlarmRefImageBlendPct'=> 'Alarm Reference Image Blend %ge',
    'Alert'                => 'Alert',
    'All'                  => 'Alle',
    'AnalysisFPS'          => 'Analyse FPS',
    'AnalysisUpdateDelay'  => 'Analyse Update Vertraging',
    'Apply'                => 'Toepassen',
    'ApplyingStateChange'  => 'Statusverandering wordt uitgevoerd',
    'ArchArchived'         => 'Alleen gearchiveerd',
    'ArchUnarchived'       => 'Alleen ongearchiveerd',
    'Archive'              => 'Archiveren',
    'Archived'             => 'Gearchiveerd',
    'Area'                 => 'Gebied',
    'AreaUnits'            => 'Gebied (px/%)',
    'AttrAlarmFrames'      => 'Alarmframes',
    'AttrArchiveStatus'    => 'Archiefstatus',
    'AttrAvgScore'         => 'Gem. score',
    'AttrCause'            => 'Oorzaak',
    'AttrDiskBlocks'       => 'Disk Blocks',
    'AttrDiskPercent'      => 'Disk Percent',
    'AttrDiskSpace'        => 'Disk Space',             // Added - 2018-08-30
    'AttrDuration'         => 'Duur',
    'AttrEndDate'          => 'End Date',               // Added - 2018-08-30
    'AttrEndDateTime'      => 'End Date/Time',          // Added - 2018-08-30
    'AttrEndTime'          => 'End Time',               // Added - 2018-08-30
    'AttrEndWeekday'       => 'End Weekday',            // Added - 2018-08-30
    'AttrFilterServer'     => 'Server Filter is Running On', // Added - 2018-08-30
    'AttrFrames'           => 'Frames',
    'AttrId'               => 'Id',
    'AttrMaxScore'         => 'Max. Score',
    'AttrMonitorId'        => 'Monitor Id',
    'AttrMonitorName'      => 'Monitor Naam',
    'AttrMonitorServer'    => 'Server Monitor is Running On', // Added - 2018-08-30
    'AttrName'             => 'Naam',
    'AttrNotes'            => 'Notities',
    'AttrStartDate'        => 'Start Date',             // Added - 2018-08-30
    'AttrStartDateTime'    => 'Start Date/Time',        // Added - 2018-08-30
    'AttrStartTime'        => 'Start Time',             // Added - 2018-08-30
    'AttrStartWeekday'     => 'Start Weekday',          // Added - 2018-08-30
    'AttrStateId'          => 'Run State',              // Added - 2018-08-30
    'AttrStorageArea'      => 'Storage Area',           // Added - 2018-08-30
    'AttrStorageServer'    => 'Server Hosting Storage', // Added - 2018-08-30
    'AttrSystemLoad'       => 'Systembelasting',
    'AttrTotalScore'       => 'Totale Score',
    'Auto'                 => 'Auto',
    'AutoStopTimeout'      => 'Auto Stop Timeout',
    'Available'            => 'Beschikbaar',
    'AvgBrScore'           => 'Gem.<br/>score',
    'Background'           => 'Achtergrond',
    'BackgroundFilter'     => 'Voer filter uit op achtergrond',
    'BadAlarmFrameCount'   => 'Aantal alarmframes moet een getal zijn van 1 of meer',
    'BadAlarmMaxFPS'       => 'Alarm Maximum FPS moet een positieve waarde zijn',
    'BadAnalysisFPS'       => 'Analyse FPS moet een positieve waarde zijn',
    'BadAnalysisUpdateDelay'=> 'Analyse updatevertraging moet een getal van nul of groter zijn',
    'BadChannel'           => 'Kanaal moet een getal zijn van 1 of meer',
    'BadColours'           => 'Doelkleur moet een geldige waarde zijn',
    'BadDevice'            => 'Apparaat moet een geldige waarde zijn',
    'BadFPSReportInterval' => 'FPS rapport interval buffer aantal moet een getal groter dan nul zijn',
    'BadFormat'            => 'Formaat moet een getal van nul of groter zijn',
    'BadFrameSkip'         => 'Frame skip aantal moet een getal van nul of groter zijn',
    'BadHeight'            => 'Hoogte moet een geldige waarde zijn',
    'BadHost'              => 'Host moet een juist adres of hostname zijn, laat http:// weg',
    'BadImageBufferCount'  => 'Buffergrootte moet een getal van 10 of groter zijn',
    'BadLabelX'            => 'Label X-coördinaat moet een getal van nul of groter zijn',
    'BadLabelY'            => 'Label Y-coördinaat moet een getal van nul of groter zijn',
    'BadMaxFPS'            => 'Maximum FPS moet een positieve waarde zijn',
    'BadMotionFrameSkip'   => 'Motion Frame skip count dient een getal van nul of groter te zijn',
    'BadNameChars'         => 'Namen mogen alleen letters en cijfers bevatten plus spaties, streepjes, en liggende streepjes',
    'BadPalette'           => 'Palet moet een geldige waarde zijn',
    'BadPath'              => 'Pad  moet een geldige waarde zijn',
    'BadPort'              => 'Poort moet een geldige nummer zijn',
    'BadPostEventCount'    => 'Aantal post-gebeurtenisframes moet een getal van nul of groter zijn',
    'BadPreEventCount'     => 'Aantal pre-gebeurtenisframes moet een getal zijn van minimaal nul en minder dan de buffergrootte',
    'BadRefBlendPerc'      => 'Reference blend percentage moet een geldige waarde van nul of groter zijn',
    'BadSectionLength'     => 'Sectielengte moet een getal van 30 of groter zijn',
    'BadSignalCheckColour' => 'Signaalcontrolekleur moet een geldige RGB waarde zijn',
    'BadSourceType'        => 'Source Type \"Web Site\" requires the Function to be set to \"Monitor\"', // Added - 2018-08-30
    'BadStreamReplayBuffer'=> 'Stream replay buffer moet een geldige waarde van nul of groter zijn',
    'BadWarmupCount'       => 'Opwarm frames moet een geldig getal van nul of groter zijn',
    'BadWebColour'         => 'Webkleur moet een geldige webkleurwaarde bevatten',
    'BadWebSitePath'       => 'Please enter a complete website url, including the http:// or https:// prefix.', // Added - 2018-08-30
    'BadWidth'             => 'Breedte moet een geldige waarde zijn',
    'Bandwidth'            => 'Bandbreedte',
    'BandwidthHead'        => 'bandbreedte',	// This is the end of the bandwidth status on the top of the console, different in many language due to phrasing
    'BlobPx'               => 'Blob px',
    'BlobSizes'            => 'Blobgrootte',
    'Blobs'                => 'Blobs',
    'Brightness'           => 'Helderheid',
    'Buffer'               => 'Buffer',
    'Buffers'              => 'Buffers',
    'CSSDescription'       => 'Wijzig de standaard CSS voor deze computer',
    'CanAutoFocus'         => 'Can Auto Focus',
    'CanAutoGain'          => 'Can Auto Gain',
    'CanAutoIris'          => 'Can Auto Iris',
    'CanAutoWhite'         => 'Can Auto White Bal.',
    'CanAutoZoom'          => 'Can Auto Zoom',
    'CanFocus'             => 'Can Focus',
    'CanFocusAbs'          => 'Can Focus Absoluut',
    'CanFocusCon'          => 'Can Focus Continue',
    'CanFocusRel'          => 'Can Focus Relatief',
    'CanGain'              => 'Can Gain ',
    'CanGainAbs'           => 'Can Gain Absoluut',
    'CanGainCon'           => 'Can Gain Continue',
    'CanGainRel'           => 'Can Gain Relatief',
    'CanIris'              => 'Can Iris',
    'CanIrisAbs'           => 'Can Iris Absoluut',
    'CanIrisCon'           => 'Can Iris Continue',
    'CanIrisRel'           => 'Can Iris Relatief',
    'CanMove'              => 'Can Move',
    'CanMoveAbs'           => 'Can Move Absoluut',
    'CanMoveCon'           => 'Can Move Continue',
    'CanMoveDiag'          => 'Can Move Diagonaal',
    'CanMoveMap'           => 'Can Move Mapped',
    'CanMoveRel'           => 'Can Move Relatief',
    'CanPan'               => 'Can Pan' ,
    'CanReset'             => 'Can Reset',
	'CanReboot'             => 'Can Reboot',
    'CanSetPresets'        => 'Can Set Presets',
    'CanSleep'             => 'Can Sleep',
    'CanTilt'              => 'Can Tilt',
    'CanWake'              => 'Can Wake',
    'CanWhite'             => 'Can White Balance',
    'CanWhiteAbs'          => 'Can White Bal. Absoluut',
    'CanWhiteBal'          => 'Can White Bal.',
    'CanWhiteCon'          => 'Can White Bal. Continue',
    'CanWhiteRel'          => 'Can White Bal. Relatief',
    'CanZoom'              => 'Can Zoom',
    'CanZoomAbs'           => 'Can Zoom Absoluut',
    'CanZoomCon'           => 'Can Zoom Continue',
    'CanZoomRel'           => 'Can Zoom Relatief',
    'Cancel'               => 'Annuleren',
    'CancelForcedAlarm'    => 'Geforceerd alarm annuleren',
    'CaptureHeight'        => 'Hoogte van opname',
    'CaptureMethod'        => 'Opnamemethode',
    'CapturePalette'       => 'Kleurpalet opname',
    'CaptureResolution'    => 'Opnameresolutie',
    'CaptureWidth'         => 'Breedte van opname',
    'Cause'                => 'Oorzaak',
    'CheckMethod'          => 'Alarmcontrolemethode',
    'ChooseDetectedCamera' => 'Kies gedetecteerde Camera',
    'ChooseFilter'         => 'Kies filter',
    'ChooseLogFormat'      => 'Kies een logformaat',
    'ChooseLogSelection'   => 'Kies een logselectie',
    'ChoosePreset'         => 'Kies voorkeur',
    'Clear'                => 'Legen',
    'CloneMonitor'         => 'Clone',                  // Added - 2018-08-30
    'Close'                => 'Sluiten',
    'Colour'               => 'Kleur',
    'Command'              => 'Commando',
    'Component'            => 'Component',
    'ConcurrentFilter'     => 'Run filter concurrently', // Added - 2018-08-30
    'Config'               => 'Configuratie',
    'ConfiguredFor'        => 'Geconfigureerd voor',
    'ConfirmDeleteEvents'  => 'Weet u zeker dat u deze gebeurtenissen wilt verwijderen?',
    'ConfirmPassword'      => 'Bevestig wachtwoord',
    'ConjAnd'              => 'en',
    'ConjOr'               => 'of',
    'Console'              => 'Console',
    'ContactAdmin'         => 'Neem a.u.b. contact op met uw beheerder voor details.',
    'Continue'             => 'Doorgaan',
    'Contrast'             => 'Contrast',
    'Control'              => 'Bestuur',
    'ControlAddress'       => 'Bestuuradres',
    'ControlCap'           => 'Bestuurmogelijkheid',
    'ControlCaps'          => 'Bestuurmogelijkheden',
    'ControlDevice'        => 'Bestuurapparaat',
    'ControlType'          => 'Bestuurtype',
    'Controllable'         => 'Bestuurbaar',
    'Current'              => 'Huidig',
    'Cycle'                => 'Cyclus',
    'CycleWatch'           => 'Observeer cyclus',
    'DateTime'             => 'Datum/Tijd',
    'Day'                  => 'Dag',
    'Debug'                => 'Debug',
    'DefaultRate'          => 'Standaard Radius',
    'DefaultScale'         => 'Standaard Schaal',
    'DefaultView'          => 'Standaard scherm',
    'Deinterlacing'        => 'Deinterlacing',          // Added - 2015-04-18
    'Delay'                => 'Vertraging',
    'Delete'               => 'Verwijder',
    'DeleteAndNext'        => 'verwijder &amp; volgende',
    'DeleteAndPrev'        => 'verwijder &amp; vorige',
    'DeleteSavedFilter'    => 'verwijder opgeslagen filter',
    'Description'          => 'Omschrijving',
    'DetectedCameras'      => 'Gedetecteerde camera\'s',
    'DetectedProfiles'     => 'Gedetecteerde profielen',
    'Device'               => 'Apparaat',
    'DeviceChannel'        => 'Apparaatkanaal',
    'DeviceFormat'         => 'Apparaatformaat',
    'DeviceNumber'         => 'Apparaatnummer',
    'DevicePath'           => 'Apparaatpad',
    'Devices'              => 'Apparaten',
    'Dimensions'           => 'Afmetingen',
    'DisableAlarms'        => 'Alarmen uitschakelen',
    'Disk'                 => 'Schijf',
    'Display'              => 'Weergave',
    'Displaying'           => 'Weergaven',
    'DoNativeMotionDetection'=> 'Do Native Motion Detection', // Added - 2015-04-18
    'Donate'               => 'Geef a.u.b. een donatie',
    'DonateAlready'        => 'Nee, ik heb al gedoneerd',
    'DonateEnticement'     => 'U gebruikt ZoneMinder nu voor een geruime tijd, hopelijk vindt u het een nuttige toevoeging voor uw huis- of werkplekbeveiliging. Natuurlijk is en blijft ZoneMinder gratis en open source software, maar het kost geld om te ontwikkelen, ondersteunen, en te onderhouden. Wij vragen u dan ook om er over na te denken een donatie te doen om zo de ontwikkeling van ZoneMinder te ondersteunen. Natuurlijk bent u hier vrij in, en elke donatie hoe klein dan ook wordt erg gewaardeerd. <br><br> Als u wilt doneren geef dat hieronder dan aan of ga naar https://zoneminder.com/donate/ in uw browser.<br><br>Bedankt voor het gebruiken van ZoneMinder en vergeet niet om ons forum op ZoneMinder.com te bezoeken voor ondersteuning of suggesties waarmee uw ZoneMinder beleving nog beter wordt.',
    'DonateRemindDay'      => 'Nu niet, herinner mij over 1 dag hieraan',
    'DonateRemindHour'     => 'Nu niet, herinner mij over een uur hieraan',
    'DonateRemindMonth'    => 'Nu niet, herinner mij over een maand hieraan',
    'DonateRemindNever'    => 'Nee, ik wil niet doneren',
    'DonateRemindWeek'     => 'Nu niet, herinner mij over een week hieraan',
    'DonateYes'            => 'Ja, ik wil nu doneren',
    'Download'             => 'Downloaden',
    'DownloadVideo'        => 'Download Video',         // Added - 2018-08-30
    'DuplicateMonitorName' => 'Kopieer monitornaam',
    'Duration'             => 'Duur',
    'Edit'                 => 'Bewerken',
    'EditLayout'           => 'Edit Layout',            // Added - 2018-08-30
    'Email'                => 'Email',
    'EnableAlarms'         => 'Alarmen inschakelen',
    'Enabled'              => 'Ingeschakeld',
    'EnterNewFilterName'   => 'Voer nieuwe filternaam in',
    'Error'                => 'Fout',
    'ErrorBrackets'        => 'Fout, controleer of je evenveel openings- als afsluitingsbrackets hebt gebruikt',
    'ErrorValidValue'      => 'Fout, Controleer of alle termen een geldige waarde hebben',
    'Etc'                  => 'etc',
    'Event'                => 'Gebeurtenis',
    'EventFilter'          => 'Gebeurtenisfilter',
    'EventId'              => 'Gebeurtenis Id',
    'EventName'            => 'Gebeurtenisnaam',
    'EventPrefix'          => 'Gebeurtenisprefix',
    'Events'               => 'Gebeurtenissen',
    'Exclude'              => 'Uitsluiten',
    'Execute'              => 'Uitvoeren',
    'Exif'                 => 'Embed EXIF data into image', // Added - 2018-08-30
    'Export'               => 'Exporteren',
    'ExportDetails'        => 'Exporteer gebeurtenisdetails',
    'ExportFailed'         => 'Exporteren mislukt',
    'ExportFormat'         => 'Formaat exporteerbestand',
    'ExportFormatTar'      => 'Tar',
    'ExportFormatZip'      => 'Zip',
    'ExportFrames'         => 'Exporteer framedetails',
    'ExportImageFiles'     => 'Exporteer fotobestanden',
    'ExportLog'            => 'Exporteer log',
    'ExportMiscFiles'      => 'Exporteer andere bestanden (wanneer aanwezig)',
    'ExportOptions'        => 'Exporteeropties',
    'ExportSucceeded'      => 'Exporteren geslaagd',
    'ExportVideoFiles'     => 'Exporteer videobestanden (wanneer aanwezig)',
    'Exporting'            => 'Exporteren',
    'FPS'                  => 'fps',
    'FPSReportInterval'    => 'FPS rapportage interval',
    'FTP'                  => 'FTP',
    'Far'                  => 'Ver',
    'FastForward'          => 'Doorspoelen',
    'Feed'                 => 'toevoer',
    'Ffmpeg'               => 'Ffmpeg',
    'File'                 => 'Bestand',
    'Filter'               => 'Filter',
    'FilterArchiveEvents'  => 'Archiveer alle overeenkomsten',
    'FilterDeleteEvents'   => 'Verwijder alle overeenkomsten',
    'FilterEmailEvents'    => 'Email de details van alle overeenkomsten',
    'FilterExecuteEvents'  => 'Voer opdrachten uit op alle overeenkomsten',
    'FilterLog'            => 'Filterlog',
    'FilterMessageEvents'  => 'Bericht de details van alle overeenkomsten',
    'FilterMoveEvents'     => 'Move all matches',       // Added - 2018-08-30
    'FilterPx'             => 'Filter px',
    'FilterUnset'          => 'Je moet de filterhoogte en -breedte opgeven',
    'FilterUpdateDiskSpace'=> 'Update used disk space', // Added - 2018-08-30
    'FilterUploadEvents'   => 'Verstuur alle overeenkomsten',
    'FilterVideoEvents'    => 'Maak video voor alle overeenkomsten',
    'Filters'              => 'Filters',
    'First'                => 'Eerste',
    'FlippedHori'          => 'Horizontaal gedraaid',
    'FlippedVert'          => 'Verticaal gedraaid',
    'FnMocord'              => 'Mocord',            // Added 2013.08.16.
    'FnModect'              => 'Modect',            // Added 2013.08.16.
    'FnMonitor'             => 'Monitor',            // Added 2013.08.16.
    'FnNodect'              => 'Nodect',            // Added 2013.08.16.
    'FnNone'                => 'None',            // Added 2013.08.16.
    'FnRecord'              => 'Record',            // Added 2013.08.16.
    'Focus'                => 'Focus',
    'ForceAlarm'           => 'Forceer alarm',
    'Format'               => 'Formaat',
    'Frame'                => 'Frame',
    'FrameId'              => 'Frame id',
    'FrameRate'            => 'Framerate',
    'FrameSkip'            => 'Frame overgeslagen',
    'Frames'               => 'Frames',
    'Func'                 => 'Func',
    'Function'             => 'Functie',
    'Gain'                 => 'Gain',
    'General'              => 'Algemeen',
    'GenerateDownload'     => 'Generate Download',      // Added - 2018-08-30
    'GenerateVideo'        => 'Genereer Video',
    'GeneratingVideo'      => 'Video wordt gegenereerd',
    'GoToZoneMinder'       => 'Ga naar ZoneMinder.com',
    'Grey'                 => 'Grijs',
    'Group'                => 'Groep',
    'Groups'               => 'Groepen',
    'HasFocusSpeed'        => 'Heeft Focus Sneldheid',
    'HasGainSpeed'         => 'Heeft Gain Snelheid',
    'HasHomePreset'        => 'Heeft start Voorkeuren',
    'HasIrisSpeed'         => 'Heeft Iris Snelheid',
    'HasPanSpeed'          => 'Heeft Pan Snelheid',
    'HasPresets'           => 'Heeft Voorkeuren',
    'HasTiltSpeed'         => 'Heeft Tiltsnelheid',
    'HasTurboPan'          => 'Heeft Turbo Pan',
    'HasTurboTilt'         => 'Heeft Turbo Tilt',
    'HasWhiteSpeed'        => 'Heeft White Bal. Snelheid',
    'HasZoomSpeed'         => 'Heeft Zoomsnelheid',
    'High'                 => 'Hoog',
    'HighBW'               => 'Hoog&nbsp;B/W',
    'Home'                 => 'Home',
    'Hostname'             => 'Hostname',               // Added - 2018-08-30
    'Hour'                 => 'Uur',
    'Hue'                  => 'Hue',
    'Id'                   => 'Id',
    'Idle'                 => 'Inactief',
    'Ignore'               => 'Negeren',
    'Image'                => 'Beeld',
    'ImageBufferSize'      => 'Beeldbuffergrootte (frames)',
    'Images'               => 'Beelden',
    'In'                   => 'In',
    'Include'              => 'voeg in',
    'Inverted'             => 'Geïnverteerd',
    'Iris'                 => 'Iris',
    'KeyString'            => 'Sleutel waarde',
    'Label'                => 'Label',
    'Language'             => 'Taal',
    'Last'                 => 'Laatste',
    'Layout'               => 'Layout',
    'Level'                => 'Niveau',
    'Libvlc'               => 'Libvlc',
    'LimitResultsPost'     => 'resultaten;', // This is used at the end of the phrase 'Limit to first N results only'
    'LimitResultsPre'      => 'beperk tot eerste', // This is used at the beginning of the phrase 'Limit to first N results only'
    'Line'                 => 'Lijn',
    'LinkedMonitors'       => 'Gekoppelde monitoren',
    'List'                 => 'Lijst',
    'ListMatches'          => 'List Matches',           // Added - 2018-08-30
    'Load'                 => 'Systeemlast',
    'Local'                => 'Lokaal',
    'Log'                  => 'Log',
    'LoggedInAs'           => 'Aangemeld als',
    'Logging'              => 'Logging',                // Added - 2011-06-16
    'LoggingIn'            => 'Aanmelden..',
    'Login'                => 'Aanmelden',
    'Logout'               => 'Afmelden',
    'Logs'                 => 'Logs',
    'Low'                  => 'Laag',
    'LowBW'                => 'Laag&nbsp;B/W',
    'Main'                 => 'Main',
    'Man'                  => 'Man',
    'Manual'               => 'Handmatig',
    'Mark'                 => 'Markeren',
    'Max'                  => 'Max',
    'MaxBandwidth'         => 'Max Bandbreedte',
    'MaxBrScore'           => 'Max.<br/>score',
    'MaxFocusRange'        => 'Max Focus Bereik',
    'MaxFocusSpeed'        => 'Max Focus Snelheid',
    'MaxFocusStep'         => 'Max Focus Stap',
    'MaxGainRange'         => 'Max Gain Bereik',
    'MaxGainSpeed'         => 'Max Gain Snelheid',
    'MaxGainStep'          => 'Max Gain Stap',
    'MaxIrisRange'         => 'Max Iris Bereik',
    'MaxIrisSpeed'         => 'Max Iris Snelheid',
    'MaxIrisStep'          => 'Max Iris Stap',
    'MaxPanRange'          => 'Max Pan Bereik',
    'MaxPanSpeed'          => 'Max Pan Snelheid',
    'MaxPanStep'           => 'Max Pan Stap',
    'MaxTiltRange'         => 'Max Tilt Bereik',
    'MaxTiltSpeed'         => 'Max Tilt Snelheid',
    'MaxTiltStep'          => 'Max Tilt Stap',
    'MaxWhiteRange'        => 'Max White Bal. Bereik',
    'MaxWhiteSpeed'        => 'Max White Bal. Snelheid',
    'MaxWhiteStep'         => 'Max White Bal. Stap',
    'MaxZoomRange'         => 'Max Zoom Bereik',
    'MaxZoomSpeed'         => 'Max Zoom Snelheid',
    'MaxZoomStep'          => 'Max Zoom Stap',
    'MaximumFPS'           => 'Maximum FPS',
    'Medium'               => 'Gemiddeld',
    'MediumBW'             => 'Gemiddelde&nbsp;B/W',
    'Message'              => 'Bericht',
    'MinAlarmAreaLtMax'    => 'Minimum alarmgebied moet kleiner zijn dan het maximum',
    'MinAlarmAreaUnset'    => 'Specificeer het minimaal aantal alarmpixels',
    'MinBlobAreaLtMax'     => 'Minimum blobgebied moet kleiner zijn dan maximum blobgebied',
    'MinBlobAreaUnset'     => 'Specificeer het minimaal aantal blobpixels',
    'MinBlobLtMinFilter'   => 'Minimum blobgebied moet kleiner of gelijk zijn aan het minimale filtergebied',
    'MinBlobsLtMax'        => 'Minimum aantal blobs moet kleiner zijn dan maximum aantal blobs',
    'MinBlobsUnset'        => 'Specificeer het minimaal aantal blobs',
    'MinFilterAreaLtMax'   => 'Minimum filtergebied moet minder dan het maximum zijn',
    'MinFilterAreaUnset'   => 'Specificeer het minimaal aantal filterpixels',
    'MinFilterLtMinAlarm'  => 'Minimum filtergebied moet kleiner of gelijk zijn aan het minimale alarmgebied',
    'MinFocusRange'        => 'Min Focus Bereik',
    'MinFocusSpeed'        => 'Min Focus Snelheid',
    'MinFocusStep'         => 'Min Focus Step',
    'MinGainRange'         => 'Min Gain Bereik',
    'MinGainSpeed'         => 'Min Gain Snelheid',
    'MinGainStep'          => 'Min Gain Step',
    'MinIrisRange'         => 'Min Iris Bereik',
    'MinIrisSpeed'         => 'Min Iris Snelheid',
    'MinIrisStep'          => 'Min Iris Step',
    'MinPanRange'          => 'Min Draai Bereik',
    'MinPanSpeed'          => 'Min Draai Snelheid',
    'MinPanStep'           => 'Min Draai Step',
    'MinPixelThresLtMax'   => 'Minimum pixel kleurdiepte moet kleiner zijn dan maximum pixel bereikwaarde',
    'MinPixelThresUnset'   => 'Specificeer een minimale pixel bereikwaarde',
    'MinTiltRange'         => 'Min Tilt Bereik',
    'MinTiltSpeed'         => 'Min Tilt Snelheid',
    'MinTiltStep'          => 'Min Tilt Step',
    'MinWhiteRange'        => 'Min White Bal. Bereik',
    'MinWhiteSpeed'        => 'Min White Bal. Snelheid',
    'MinWhiteStep'         => 'Min White Bal. Step',
    'MinZoomRange'         => 'Min Zoom Bereik',
    'MinZoomSpeed'         => 'Min Zoom Snelheid',
    'MinZoomStep'          => 'Min Zoom Step',
    'Misc'                 => 'Etc.',
    'Mode'                 => 'Modus',
    'Monitor'              => 'Monitor',
    'MonitorIds'           => 'Monitor&nbsp;Ids',
    'MonitorPreset'        => 'Monitor Preset',
    'MonitorPresetIntro'   => 'Selecteer een voorinstelling uit de lijst.<br><br>let op, dit overschrijft de reeds ingevoerde waarden voor deze monitor!<br><br>',
    'MonitorProbe'         => 'Monitor Probe',          // Added - 2009-03-31
    'MonitorProbeIntro'    => 'Deze lijst toont gedeteerde analoge en netwerk cameras en of deze al in gebruik of beschikbaar zijn.<br/><br/>Selecteer de gewenste waarde uit de lijst hieronder.<br/><br/>Let op dat mogelijk niet alle cameras hier worden weergegeven en dat alle ingevoerde waarden voor de huidige monitor zullen worden overschreven.<br/><br/>',
    'Monitors'             => 'Monitoren',
    'Montage'              => 'Montage',
    'MontageReview'        => 'Montage Review',         // Added - 2018-08-30
    'Month'                => 'Maand',
    'More'                 => 'Meer',
    'MotionFrameSkip'      => 'Motion Frame Skip',
    'Move'                 => 'Verplaats',
    'Mtg2widgrd'           => '2-wide grid',              // Added 2013.08.15.
    'Mtg3widgrd'           => '3-wide grid',              // Added 2013.08.15.
    'Mtg3widgrx'           => '3-wide grid, scaled, enlarge on alarm',              // Added 2013.08.15.
    'Mtg4widgrd'           => '4-wide grid',              // Added 2013.08.15.
    'MtgDefault'           => 'Standaard',              // Added 2013.08.15.
    'MustBeGe'             => 'Moet groter zijn of gelijk aan',
    'MustBeLe'             => 'Moet kleiner zijn of gelijk aan',
    'MustConfirmPassword'  => 'Bevestig uw wachtwoord',
    'MustSupplyPassword'   => 'Geef een wachtwoord op',
    'MustSupplyUsername'   => 'Geef een gebruikersnaam op',
    'Name'                 => 'Naam',
    'Near'                 => 'Dichtbij',
    'Network'              => 'Netwerk',
    'New'                  => 'Nieuw',
    'NewGroup'             => 'Nieuwe groep',
    'NewLabel'             => 'Nieuw label',
    'NewPassword'          => 'Nieuw wachtwoord',
    'NewState'             => 'Nieuwe status',
    'NewUser'              => 'Nieuwe gebruiker',
    'Next'                 => 'Volgende',
    'No'                   => 'Nee',
    'NoDetectedCameras'    => 'Geen cameras gedetecteerd',
    'NoDetectedProfiles'   => 'No Detected Profiles',   // Added - 2018-08-30
    'NoFramesRecorded'     => 'Er zijn geen beelden opgenomen voor deze gebeurtenis',
    'NoGroup'              => 'Geen Groep',
    'NoSavedFilters'       => 'Geen Opgeslagen Filters',
    'NoStatisticsRecorded' => 'Er zijn geen statistieken opgenomen voor deze gebeurtenis',
    'None'                 => 'Geen',
    'NoneAvailable'        => 'Geen beschikbaar',
    'Normal'               => 'Normaal',
    'Notes'                => 'Notities',
    'NumPresets'           => 'Num Voorkeuren',
    'Off'                  => 'Uit',
    'On'                   => 'Aan',
    'OnvifCredentialsIntro'=> 'Geef een gebruikersnaam en wachtwoord op voor de geselecteerde camera.<br/>Als er geen gebruiker bestaat vor de camera zal de hier opgegeven gebruiker met het aangegeven wachtwoord worden aangemaakt.<br/><br/>',
    'OnvifProbe'           => 'ONVIF',
    'OnvifProbeIntro'      => 'De lijst hieronder geeft gedetecteerde ONVIF camera\'s aan en of deze al worden gebruikt of beschikbaar zijn.<br/><br/>Selecteer de gewenste camera uit de lijst.<br/><br/>Let op dat het kan zijn dat niet alle camera\'s zijn gedetecteerd en dat het kiezen van een camera alle reeds ingestelde waarden voor de huidige monitor zal overschrijven.<br/><br/>',
    'OpEq'                 => 'gelijk aan',
    'OpGt'                 => 'groter dan',
    'OpGtEq'               => 'groter dan of gelijk aan',
    'OpIn'                 => 'in set',
    'OpIs'                 => 'is',                     // Added - 2018-08-30
    'OpIsNot'              => 'is not',                 // Added - 2018-08-30
    'OpLt'                 => 'kleiner dan',
    'OpLtEq'               => 'kleiner dan of gelijk aan',
    'OpMatches'            => 'Komt overeen met',
    'OpNe'                 => 'niet gelijk aan',
    'OpNotIn'              => 'niet in set',
    'OpNotMatches'         => 'Komt niet overeen met',
    'Open'                 => 'Open',
    'OptionHelp'           => 'OptieHelp',
    'OptionRestartWarning' => 'Deze veranderingen worden niet\ndoorgevoerd als het systeem loopt.\nVergeet niet ZoneMinder te herstarten\nwanneer u klaar bent.',
    'OptionalEncoderParam' => 'Optional Encoder Parameters', // Added - 2018-08-30
    'Options'              => 'Opties',
    'OrEnterNewName'       => 'of voer een nieuwe naam in',
    'Order'                => 'Sorteren',
    'Orientation'          => 'Orientatie',
    'Out'                  => 'Uit',
    'OverwriteExisting'    => 'Overschrijf bestaande',
    'Paged'                => 'Paged',
    'Pan'                  => 'Pan',
    'PanLeft'              => 'Pan Links',
    'PanRight'             => 'Pan Rechts',
    'PanTilt'              => 'Pan/Tilt',
    'Parameter'            => 'Parameter',
    'Password'             => 'Wachtwoord',
    'PasswordsDifferent'   => 'Het nieuwe en bevestigde wachtwoord zijn verschillend',
    'Paths'                => 'Paden',
    'Pause'                => 'Pause',
    'Phone'                => 'Telefoon',
    'PhoneBW'              => 'Telefoon&nbsp;B/W',
    'Pid'                  => 'PID',
    'PixelDiff'            => 'Pixel Diff',
    'Pixels'               => 'pixels',
    'Play'                 => 'Afspelen',
    'PlayAll'              => 'Alles afspelen',
    'PleaseWait'           => 'Wacht a.u.b.',
    'Plugins'              => 'Plugins',
    'Point'                => 'Punt',
    'PostEventImageBuffer' => 'Post-gebeurtenis framebuffer',
    'PreEventImageBuffer'  => 'Pre-gebeurtenis framebuffer',
    'PreserveAspect'       => 'Beeldverhouding behouden',
    'Preset'               => 'Voorkeur',
    'Presets'              => 'Voorkeuren',
    'Prev'                 => 'Vorige',
    'Probe'                => 'Scan',                  // Added - 2009-03-31
    'ProfileProbe'         => 'Stream Probe',           // Added - 2015-04-18
    'ProfileProbeIntro'    => 'The list below shows the existing stream profiles of the selected camera .<br/><br/>Select the desired entry from the list below.<br/><br/>Please note that ZoneMinder cannot configure additional profiles and that choosing a camera here may overwrite any values you already have configured for the current monitor.<br/><br/>', // Added - 2015-04-18
    'Progress'             => 'Voortgang',
    'Protocol'             => 'Protocol',
    'RTSPDescribe'         => 'Use RTSP Response Media URL', // Added - 2018-08-30
    'RTSPTransport'        => 'RTSP Transport Protocol', // Added - 2018-08-30
    'Rate'                 => 'Snelheid',
    'Real'                 => 'Echte',
    'RecaptchaWarning'     => 'Your reCaptcha secret key is invalid. Please correct it, or reCaptcha will not work', // Added - 2018-08-30
    'Record'               => 'Record',
    'RecordAudio'          => 'Whether to store the audio stream when saving an event.', // Added - 2018-08-30
    'RefImageBlendPct'     => 'Referentie beeld blend percentage',
    'Refresh'              => 'Verversen',
    'Remote'               => 'Remote',
    'RemoteHostName'       => 'Remote Host Naam',
    'RemoteHostPath'       => 'Remote Host Pad',
    'RemoteHostPort'       => 'Remote Host Poort',
    'RemoteHostSubPath'    => 'Remote Host SubPad',    // Added - 2009-02-08
    'RemoteImageColours'   => 'Remote foto kleuren',
    'RemoteMethod'         => 'Remote Methode',          // Added - 2009-02-08
    'RemoteProtocol'       => 'Remote Protocol',        // Added - 2009-02-08
    'Rename'               => 'Hernoem',
    'Replay'               => 'Opnieuw',
    'ReplayAll'            => 'Alle Gebeurtenissen',
    'ReplayGapless'        => 'Opvolgende Gebeurtenissen',
    'ReplaySingle'         => 'Enkele Gebeurtenis',
    'ReportEventAudit'     => 'Audit Events Report',    // Added - 2018-08-30
    'Reset'                => 'Resetten',
    'ResetEventCounts'     => 'Gebeurtenisteller resetten',
    'Restart'              => 'Herstart',
    'Restarting'           => 'Herstarten',
    'RestrictedCameraIds'  => 'Verboden Camera Ids',
    'RestrictedMonitors'   => 'Beperkte Monitoren',
    'ReturnDelay'          => 'Return Delay',
    'ReturnLocation'       => 'Return Locatie',
    'Rewind'               => 'Terugspoelen',
    'RotateLeft'           => 'Draai linksom',
    'RotateRight'          => 'Draai rechtsom',
    'RunLocalUpdate'       => 'Gebruik zmupdate.pl om bij te werken',
    'RunMode'              => 'Uitvoermodus',
    'RunState'             => 'Uitvoerstatus',
    'Running'              => 'Werkend',
    'Save'                 => 'Opslaan',
    'SaveAs'               => 'Opslaan als',
    'SaveFilter'           => 'Filter opslaan',
    'SaveJPEGs'            => 'Save JPEGs',             // Added - 2018-08-30
    'Scale'                => 'Schaal',
    'Score'                => 'Score',
    'Secs'                 => 'Sec.',
    'Sectionlength'        => 'Sectielengte',
    'Select'               => 'Selecteer',
    'SelectFormat'         => 'Selecteer Formaat',
    'SelectLog'            => 'Selecteer Log',
    'SelectMonitors'       => 'Selecteer Monitoren',
    'SelfIntersecting'     => 'Polygonranden moeten niet overlappen',
    'Set'                  => 'Instellen',
    'SetNewBandwidth'      => 'Nieuwe Bandbreedte instellen',
    'SetPreset'            => 'Voorkeur instellen',
    'Settings'             => 'Instellingen',
    'ShowFilterWindow'     => 'Toon Filtervenster',
    'ShowTimeline'         => 'Toon Tijdlijn',
    'SignalCheckColour'    => 'Signaalcontrolekleur',
    'SignalCheckPoints'    => 'Signal Check Points',    // Added - 2018-08-30
    'Size'                 => 'Groote',
    'SkinDescription'      => 'Wijzig standaarduiterlijk voor deze computer',
    'Sleep'                => 'Slaap',
    'SortAsc'              => 'Opl.',
    'SortBy'               => 'Sorteer op',
    'SortDesc'             => 'Afl.',
    'Source'               => 'Bron',
    'SourceColours'        => 'Bronkleuren',
    'SourcePath'           => 'Bronpad',
    'SourceType'           => 'Brontype',
    'Speed'                => 'Snelheid',
    'SpeedHigh'            => 'Hoge snelheid',
    'SpeedLow'             => 'Lage snelheid',
    'SpeedMedium'          => 'Gemiddelde snelheid',
    'SpeedTurbo'           => 'Turbo snelheid',
    'Start'                => 'Start',
    'State'                => 'Status',
    'Stats'                => 'Stats',
    'Status'               => 'Status',
    'StatusConnected'      => 'Capturing',              // Added - 2018-08-30
    'StatusNotRunning'     => 'Not Running',            // Added - 2018-08-30
    'StatusRunning'        => 'Not Capturing',          // Added - 2018-08-30
    'StatusUnknown'        => 'Unknown',                // Added - 2018-08-30
    'Step'                 => 'Stap',
    'StepBack'             => 'Stap Terug',
    'StepForward'          => 'Stap Vooruit',
    'StepLarge'            => 'Grote stap',
    'StepMedium'           => 'Gemiddelde stap',
    'StepNone'             => 'Geen stap',
    'StepSmall'            => 'Kleine stap',
    'Stills'               => 'Beelden',
    'Stop'                 => 'Stoppen',
    'Stopped'              => 'Gestopt',
    'StorageArea'          => 'Storage Area',           // Added - 2018-08-30
    'StorageScheme'        => 'Scheme',                 // Added - 2018-08-30
    'Stream'               => 'Stream',
    'StreamReplayBuffer'   => 'Stream Replay beeldbuffer',
    'Submit'               => 'Verzenden',
    'System'               => 'Systeem',
    'SystemLog'            => 'Systeemlog',
    'TargetColorspace'     => 'Target colorspace',      // Added - 2015-04-18
    'Tele'                 => 'Tele',
    'Thumbnail'            => 'Thumbnail',
    'Tilt'                 => 'Tilt',
    'Time'                 => 'Tijd',
    'TimeDelta'            => 'Tijd Delta',
    'TimeStamp'            => 'Tijdstempel',
    'Timeline'             => 'Tijdlijn',
    'TimelineTip1'         => 'Beweeg de muis over de grafiek om een beeld en gebeurtenisdetails te bekijken.',
    'TimelineTip2'         => 'Klik op de gekleurde delen van de grafiek of de afbeelding om de gebeurtenis te bekijken.',
    'TimelineTip3'         => 'Klik op de achtergrond om in te zoomen naar een smaller tijdsbestek rond de positie van de muis.',
    'TimelineTip4'         => 'Gebruik de knoppen hieronder om uit te zoomen of voor- en achteruit te navigeren.',
    'Timestamp'            => 'Tijdstempel',
    'TimestampLabelFormat' => 'Formaat tijdstempel',
    'TimestampLabelSize'   => 'Font Size',              // Added - 2018-08-30
    'TimestampLabelX'      => 'Tijdstempel X-positie',
    'TimestampLabelY'      => 'Tijdstempel Y-positie',
    'Today'                => 'Vandaag',
    'Tools'                => 'Gereedschappen',
    'Total'                => 'Totaal',
    'TotalBrScore'         => 'Totaal-<br/>Score',
    'TrackDelay'           => 'Track Vertraging',
    'TrackMotion'          => 'Track Beweging',
    'Triggers'             => 'Triggers',
    'TurboPanSpeed'        => 'Turbo Pan Snelheid',
    'TurboTiltSpeed'       => 'Turbo Tilt Snelheid',
    'Type'                 => 'Type',
    'Unarchive'            => 'Dearchiveren',
    'Undefined'            => 'Ongedefinieerd',
    'Units'                => 'Eenheden',
    'Unknown'              => 'Onbekend',
    'Update'               => 'Bijwerken',
    'UpdateAvailable'      => 'Er is een nieuwe versie voor ZoneMinder beschikbaar',
    'UpdateNotNecessary'   => 'Geen update noodzakelijk',
    'Updated'              => 'Bijwerken voltooid',
    'Upload'               => 'Uploaden',
    'UseFilter'            => 'Gebruik Filter',
    'UseFilterExprsPost'   => '&nbsp;filter&nbsp;expressies', // This is used at the end of the phrase 'use N filter expressions'
    'UseFilterExprsPre'    => 'Gebruik&nbsp;', // This is used at the beginning of the phrase 'use N filter expressions'
    'UsedPlugins'          => 'Gebruikte plugins',
    'User'                 => 'Gebruiker',
    'Username'             => 'Gebruikersnaam',
    'Users'                => 'Gebruikers',
    'V4L'                  => 'V4L',                    // Added - 2015-04-18
    'V4LCapturesPerFrame'  => 'Captures Per Frame',     // Added - 2015-04-18
    'V4LMultiBuffer'       => 'Multi Buffering',        // Added - 2015-04-18
    'Value'                => 'Waarde',
    'Version'              => 'Versie',
    'VersionIgnore'        => 'Negeer deze versie',
    'VersionRemindDay'     => 'Herinner mij na 1 dag',
    'VersionRemindHour'    => 'Herinner mij na 1 uur',
    'VersionRemindNever'   => 'Herinner mij niet aan nieuwe versies',
    'VersionRemindWeek'    => 'Herinner mij na 1 week',
    'Video'                => 'Video',
    'VideoFormat'          => 'Videoformaat',
    'VideoGenFailed'       => 'Videogeneratie mislukt!',
    'VideoGenFiles'        => 'Bestaande videobestanden',
    'VideoGenNoFiles'      => 'Geen videobestanden gevonden',
    'VideoGenParms'        => 'Videogeneratie Parameters',
    'VideoGenSucceeded'    => 'Videogeneratie voltooid!',
    'VideoSize'            => 'Videogrootte',
    'VideoWriter'          => 'Video Writer',           // Added - 2018-08-30
    'View'                 => 'Bekijk',
    'ViewAll'              => 'Bekijk Alles',
    'ViewEvent'            => 'Bekijk Gebeurtenis',
    'ViewPaged'            => 'Bekijk Pagina',
    'Wake'                 => 'Wakker',
    'WarmupFrames'         => 'Opwarm frames',
    'Watch'                => 'Observeer',
    'Web'                  => 'Web',
    'WebColour'            => 'Webkleur',
    'WebSiteUrl'           => 'Website URL',            // Added - 2018-08-30
    'Week'                 => 'Week',
    'White'                => 'Wit',
    'WhiteBalance'         => 'Witbalans',
    'Wide'                 => 'Breed',
    'X'                    => 'X',
    'X10'                  => 'X10',
    'X10ActivationString'  => 'X10 Activatie Waarde',
    'X10InputAlarmString'  => 'X10 Input Alarm Waarde',
    'X10OutputAlarmString' => 'X10 Output Alarm Waarde',
    'Y'                    => 'Y',
    'Yes'                  => 'Ja',
    'YouNoPerms'           => 'U heeft niet de rechten om toegang te krijgen tot deze bronnen.',
    'Zone'                 => 'Zone',
    'ZoneAlarmColour'      => 'Alarm Kleur (Rood/Groen/Blauw)',
    'ZoneArea'             => 'Zone Gebied',
    'ZoneExtendAlarmFrames' => 'Extend Alarm Frame Count',
    'ZoneFilterSize'       => 'Filter Hoogte/Breedte (pixels)',
    'ZoneMinMaxAlarmArea'  => 'Min/Max alarmgebied',
    'ZoneMinMaxBlobArea'   => 'Min/Max Blobgebied',
    'ZoneMinMaxBlobs'      => 'Min/Max Blobs',
    'ZoneMinMaxFiltArea'   => 'Min/Max Gefilterd gebied',
    'ZoneMinMaxPixelThres' => 'Min/Max Pixel drempelwaarde (0-255)',
    'ZoneMinderLog'        => 'ZoneMinder Log',
    'ZoneOverloadFrames'   => 'Negeer aantal overload frames',
    'Zones'                => 'Zones',
    'Zoom'                 => 'Zoom',
    'ZoomIn'               => 'Zoom In',
    'ZoomOut'              => 'Zoom Uit',
);

// Complex replacements with formatting and/or placements, must be passed through sprintf
$CLANG = array(
    'CurrentLogin'         => 'huidige login is \'%1$s\'',
    'EventCount'           => '%1$s %2$s', // Als voorbeeld '37 gebeurtenissen' (from Vlang below)
    'LastEvents'           => 'Laatste %1$s %2$s', // Als voorbeeld 'Laatste 37 gebeurtenissen' (from Vlang below)
    'LatestRelease'        => 'de laatste release is v%1$s, jij hebt v%2$s.',
    'MonitorCount'         => '%1$s %2$s', // Als voorbeeld '4 Monitoren' (from Vlang below)
    'MonitorFunction'      => 'Monitor %1$s Functie',
    'RunningRecentVer'     => 'U draait al de meest recente versie van ZoneMinder, v%s.',
    'VersionMismatch'      => 'Versie verschil, systeem is versie %1$s, database is %2$s.', // Added - 2011-05-25
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
    'Event'                => array( 0=>'gebeurtenissen', 1=>'gebeurtenis', 2=>'gebeurtenissen' ),
    'Monitor'              => array( 0=>'Monitoren', 1=>'Monitor', 2=>'Monitoren' ),
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
