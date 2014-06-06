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
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//

// ZoneMinder Dutch Translation by Alco (a.k. nightcrawler)

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
    '8BitGrey'             => '8 bits grijstinten',
    'Action'               => 'Actie',
    'Actual'               => 'Aktueel',
    'AddNewControl'        => 'Nieuwe controle toevoegen',
    'AddNewMonitor'        => 'Nieuwe monitor toevoegen',
    'AddNewUser'           => 'Nieuwe gebruiker toevoegen',
    'AddNewZone'           => 'Nieuw gebied toevoegen',
    'Alarm'                => 'Alarm',
    'AlarmBrFrames'        => 'Alarm<br/>Frames',
    'AlarmFrame'           => 'Alarm Frame',
    'AlarmFrameCount'      => 'Alarm Frame Aantal',
    'AlarmLimits'          => 'Alarm Limieten',
    'AlarmMaximumFPS'      => 'Alarm Maximum FPS',
    'AlarmPx'              => 'Alarm Px',
    'AlarmRGBUnset'        => 'U moet een RGB alarm kleur keizen',
    'Alert'                => 'Waarschuwing',
    'All'                  => 'Alle',
    'Apply'                => 'Voer uit',
    'ApplyingStateChange'  => 'Status verandering aan het uitvoeren',
    'ArchArchived'         => 'Alleen gearchiveerd',
    'ArchUnarchived'       => 'Alleen ongearchiveerd',
    'Archive'              => 'Archief',
    'Archived'             => 'Archived',
    'Area'                 => 'Gebied',
    'AreaUnits'            => 'Gebied (px/%)',
    'AttrAlarmFrames'      => 'Alarm frames',
    'AttrArchiveStatus'    => 'Archief status',
    'AttrAvgScore'         => 'Gem. score',
    'AttrCause'            => 'Oorzaak',
    'AttrDate'             => 'Datum',
    'AttrDateTime'         => 'Datum/tijd',
    'AttrDiskBlocks'       => 'Disk Blocks',
    'AttrDiskPercent'      => 'Disk Percent',
    'AttrDuration'         => 'Duur',
    'AttrFrames'           => 'Frames',
    'AttrId'               => 'Id',
    'AttrMaxScore'         => 'Max. Score',
    'AttrMonitorId'        => 'Monitor Id',
    'AttrMonitorName'      => 'Monitor Naam',
    'AttrName'             => 'Naam',
    'AttrNotes'            => 'Notities',
    'AttrSystemLoad'       => 'System Belasting',
    'AttrTime'             => 'Tijd',
    'AttrTotalScore'       => 'Totale Score',
    'AttrWeekday'          => 'Weekdag',
    'Auto'                 => 'Auto',
    'AutoStopTimeout'      => 'Auto Stop Timeout',
    'Available'            => 'Beschikbaar',              // Added - 2009-03-31
    'AvgBrScore'           => 'Gem.<br/>score',
    'Background'           => 'Achtergrond',
    'BackgroundFilter'     => 'Run filter in achtergrond',
    'BadAlarmFrameCount'   => 'Alarm frame moet een getal zijn van 1 of meer',
    'BadAlarmMaxFPS'       => 'Alarm Maximum FPS moet een positiev getal zijn of een floating point waarde',
    'BadChannel'           => 'Kanaal moet een getal zijn van 1 of meer',
    'BadDevice'            => 'Apparaat moet een bestaande waarde krijgen',
    'BadFPSReportInterval' => 'FPS rapport interval buffer en aantal moet een nummer groter dan nul zijn',
    'BadFormat'            => 'Formaat moet een nummer nul of groter zijn',
    'BadFrameSkip'         => 'Frame skip aantal moet een nummer nul of groter zijn',
    'BadMotionFrameSkip'   => 'Motion Frame skip count must be an integer of zero or more',
    'BadHeight'            => 'Hoogte moet een geldige waarde zijn',
    'BadHost'              => 'Host moet een juiste address or hostname zijn, laat http:// weg ',
    'BadImageBufferCount'  => 'Foto buffer groote moet een nummer 10 of groter zijn',
    'BadLabelX'            => 'Label X co-ordinate moet een nummer nul of groter zijn',
    'BadLabelY'            => 'Label Y co-ordinate moet een nummer nul of groter zijn',
    'BadMaxFPS'            => 'Maximum FPS moet een positieve integer of floating point waarde zijn',
    'BadNameChars'         => 'Namen mogen alleen alpha numerieke karakters bevatten plus hyphens en underscores',
    'BadPalette'           => 'Palette moet een geldige waarde zijn', // Added - 2009-03-31
    'BadPath'              => 'Pad  moet een geldige waarde zijn',
    'BadPort'              => 'Port moet een geldige nummer zijn',
    'BadPostEventCount'    => 'Post gebeurtenis foto aantal moet een geldige waarde van nul of groter zijn',
    'BadPreEventCount'     => 'Pre gebeurtenis aantal moe minimaal nul en lager dan de buffert grote',
    'BadRefBlendPerc'      => 'Reference blend percentage moet een geldige waarde van nul of groter zijn',
    'BadSectionLength'     => 'Selectie lengte moet een integer van 30 of meer zijn',
    'BadSignalCheckColour' => 'Signaal controle kleur moet een geldige RGB waarde zijn',
    'BadStreamReplayBuffer'=> 'Stream replay buffer moet een geldige waarde van nul of groter zijn',
    'BadWarmupCount'       => 'Warmop frames moet een geldige waarde van nul of groter zijn',
    'BadWebColour'         => 'Web kleur moeten een geldige webkleurwaarde bevatten',
    'BadWidth'             => 'Breedte moet ingevuld worden',
    'Bandwidth'            => 'Bandbreedte',
    'BandwidthHead'        => 'Bandwidth',	// This is the end of the bandwidth status on the top of the console, different in many language due to phrasing
    'BlobPx'               => 'Blob px',
    'BlobSizes'            => 'Blob grootte',
    'Blobs'                => 'Blobs',
    'Brightness'           => 'Helderheid',
    'Buffers'              => 'Buffers',
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
    'Cancel'               => 'Afbreken',
    'CancelForcedAlarm'    => 'Afbreken geforceerd alarm',
    'CaptureHeight'        => 'Opname hoogte',
    'CaptureMethod'        => 'Opname Methode',         // Added - 2009-02-08
    'CapturePalette'       => 'Opname pallet',
    'CaptureWidth'         => 'Opname breedte',
    'Cause'                => 'Oorzaak',
    'CheckMethod'          => 'Alarm controle Methode',
    'ChooseDetectedCamera' => 'Kies gedetecteerde Camera', // Added - 2009-03-31
    'ChooseFilter'         => 'Kies filter',
    'ChooseLogFormat'      => 'Kies en log formaat',    // Added - 2011-06-17
    'ChooseLogSelection'   => 'Kies een log selectie', // Added - 2011-06-17
    'ChoosePreset'         => 'Kies voorkeur',
    'Clear'                => 'Leeg',                  // Added - 2011-06-16
    'Close'                => 'Sluit',
    'Colour'               => 'Kleur',
    'Command'              => 'Commando',
    'Component'            => 'Component',              // Added - 2011-06-16
    'Config'               => 'Configuratie',
    'ConfiguredFor'        => 'Geconfigureerd voor',
    'ConfirmDeleteEvents'  => 'Weet uw zeker dat uw deze gebeurtenissen wil verwijderen?',
    'ConfirmPassword'      => 'Bevestig wachtwoord',
    'ConjAnd'              => 'en',
    'ConjOr'               => 'of',
    'Console'              => 'Console',
    'ContactAdmin'         => 'Neem A.U.B. contact op met uw beheerder voor details.',
    'Continue'             => 'Continue',
    'Contrast'             => 'Contrast',
    'Control'              => 'Bestuur',
    'ControlAddress'       => 'Bestuur adres',
    'ControlCap'           => 'Bestuur mogelijkheid',
    'ControlCaps'          => 'Bestuur mogelijkheden',
    'ControlDevice'        => 'Bestuur apparaat',
    'ControlType'          => 'Bestuur Type',
    'Controllable'         => 'Bestuurbaar',
    'Cycle'                => 'Cyclus',
    'CycleWatch'           => 'Observeer cyclus',
    'DateTime'             => 'Datum/Tijd',              // Added - 2011-06-16
    'Day'                  => 'Dag',
    'Debug'                => 'Debug',
    'DefaultRate'          => 'Standaard Radius',
    'DefaultScale'         => 'Standaard Schaal',
    'DefaultView'          => 'Standaard scherm',
    'Delete'               => 'verwijder',
    'DeleteAndNext'        => 'verwijder &amp; volgende',
    'DeleteAndPrev'        => 'verwijder &amp; vorige',
    'DeleteSavedFilter'    => 'verwijder opgeslagen filter',
    'Description'          => 'Omschrijving',
    'DetectedCameras'      => 'Gedetecteerde Cameras',       // Added - 2009-03-31
    'Device'               => 'Apparaat',                 // Added - 2009-02-08
    'DeviceChannel'        => 'Apparaat kanaal',
    'DeviceFormat'         => 'Apparaat formaat',
    'DeviceNumber'         => 'Apparaat nummer',
    'DevicePath'           => 'Apparaat pad',
    'Devices'              => 'Apparaten',
    'Dimensions'           => 'Afmetingen',
    'DisableAlarms'        => 'Alarmen uitschakelen',
    'Disk'                 => 'Schijf',
    'Display'              => 'Weergave',                // Added - 2011-01-30
    'Displaying'           => 'Weergaven',             // Added - 2011-06-16
    'Donate'               => 'A.U.B geef ons een donatie',
    'DonateAlready'        => 'Nee, ik heb al gedoneerd',
    'DonateEnticement'     => 'U gebruikt Zoneminder nu voor een geruime tijd, hopelijk vindt je het een nuttige toevoeging voor u huis of werkplek beveiliging. Natuurlijk is en blijft Zoneminder gratis en open source software. Maar het kost geld om te ontwikkelen en support te onderhouden. Ik vraag u dan ook om er over na te denken om een donatie te doen om zo de ontwikkeling en support te ondersteunen. Natuurlijk bent u hier vrij in, en elke donatie hoe klein dan ook wordt erg gewaardeerd. <br><br> Als u wilt donderen geef dat hier onder dan aan of ga naar http://www.zoneminder.com/dontate.html in uw browser.<br><br>Bedankt voor het gebruiken van Zoneminder en vergeet niet om ons forum op ZoneMinder.com te bezoeken voor ondersteuning of suggesties waarmee u ZoneMinder beleving nog beter wordt.',
    'DonateRemindDay'      => 'Nu niet, herinner mij over 1 dag hieraan',
    'DonateRemindHour'     => 'Nu niet, herinner mij over een uur hieraan',
    'DonateRemindMonth'    => 'Nu niet, herinner mij over een maand hieraan',
    'DonateRemindNever'    => 'Nee, ik hiervoor wil niet doneren',
    'DonateRemindWeek'     => 'Nu niet, herinner mij over een week hieraan',
    'DonateYes'            => 'Ja, ik wil nu doneren',
    'Download'             => 'Download',
    'DuplicateMonitorName' => 'Duplicaat Monitor Naam', // Added - 2009-03-31
    'Duration'             => 'Duur',
    'Edit'                 => 'Bewerk',
    'Email'                => 'Email',
    'EnableAlarms'         => 'Enable Alarms',
    'Enabled'              => 'Ingeschakeld',
    'EnterNewFilterName'   => 'Voer nieuwe filter naam in',
    'Error'                => 'Fout',
    'ErrorBrackets'        => 'Fout, controleer of je even veel openings als afsluiting brackets hebt gebruikt',
    'ErrorValidValue'      => 'Fout, Controleer of alle termen een geldige waarde hebben',
    'Etc'                  => 'etc',
    'Event'                => 'Gebeurtenis',
    'EventFilter'          => 'Gebeurtenis filter',
    'EventId'              => 'Gebeurtenis Id',
    'EventName'            => 'Gebeurtenis Name',
    'EventPrefix'          => 'Gebeurtenis Prefix',
    'Events'               => 'Gebeurtenissen',
    'Exclude'              => 'Sluit uit',
    'Execute'              => 'Execute',
    'Export'               => 'Exporteer',
    'ExportDetails'        => 'Exporteer Gebeurtenis Details',
    'ExportFailed'         => 'Exporteer gefaald',
    'ExportFormat'         => 'Exporteer File Formaat',
    'ExportFormatTar'      => 'Tar',
    'ExportFormatZip'      => 'Zip',
    'ExportFrames'         => 'Exporteer Frame Details',
    'ExportImageFiles'     => 'Exporteer foto bestanden',
    'ExportLog'            => 'Exporteer Log',             // Added - 2011-06-17
    'ExportMiscFiles'      => 'Exporteer andere bestanden (wanneer aanwezig)',
    'ExportOptions'        => 'Exporteer Opties',
    'ExportSucceeded'      => 'Exporteren geslaagd',       // Added - 2009-02-08
    'ExportVideoFiles'     => 'Exporteer Video bestanden (wanneer aanwezig)',
    'Exporting'            => 'Exporteerd',
    'FPS'                  => 'fps',
    'FPSReportInterval'    => 'FPS rapportage interval',
    'FTP'                  => 'FTP',
    'Far'                  => 'Far',
    'FastForward'          => 'Snel vooruit',
    'Feed'                 => 'toevoer',
    'Ffmpeg'               => 'Ffmpeg',                 // Added - 2009-02-08
    'File'                 => 'Bestand',
    'FilterArchiveEvents'  => 'Archiveer alle overeenkomsten',
    'FilterDeleteEvents'   => 'Verwijder alle overeenkomsten',
    'FilterEmailEvents'    => 'Email de details van alle overeenkomsten',
    'FilterExecuteEvents'  => 'Voer opdrachten op alle overeenkomsten uit',
    'FilterMessageEvents'  => 'Bericht de details van alle overeenkomsten',
    'FilterPx'             => 'Filter px',
    'FilterUnset'          => 'Je moet de filter hoogte en breedte opgeven',
    'FilterUploadEvents'   => 'Verstuur alle overeenkomsten',
    'FilterVideoEvents'    => 'Maak video voor alle matches',
    'Filters'              => 'Filters',
    'First'                => 'Eerste',
    'FlippedHori'          => 'Horizontaal gedraait',
    'FlippedVert'          => 'Vertikaal gedraait',
    'FnNone'                => 'None',            // Added 2013.08.16.
    'FnMonitor'             => 'Monitor',            // Added 2013.08.16.
    'FnModect'              => 'Modect',            // Added 2013.08.16.
    'FnRecord'              => 'Record',            // Added 2013.08.16.
    'FnMocord'              => 'Mocord',            // Added 2013.08.16.
    'FnNodect'              => 'Nodect',            // Added 2013.08.16.
    'Focus'                => 'Focus',
    'ForceAlarm'           => 'Forceeer alarm',
    'Format'               => 'Formaat',
    'Frame'                => 'Frame',
    'FrameId'              => 'Frame id',
    'FrameRate'            => 'Frame rate',
    'FrameSkip'            => 'Frame overgeslagen',
    'MotionFrameSkip'      => 'Motion Frame Skip',
    'Frames'               => 'Frames',
    'Func'                 => 'Func',
    'Function'             => 'Functie',
    'Gain'                 => 'Gain',
    'General'              => 'Generiek',
    'GenerateVideo'        => 'Genereer Video',
    'GeneratingVideo'      => 'Genereren Video',
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
    'HasTiltSpeed'         => 'Heeft Tilt Snelheid',
    'HasTurboPan'          => 'Heeft Turbo Pan',
    'HasTurboTilt'         => 'Heeft Turbo Tilt',
    'HasWhiteSpeed'        => 'Heeft White Bal. Snelheid',
    'HasZoomSpeed'         => 'Heeft Zoom Snelheid',
    'High'                 => 'Hoog',
    'HighBW'               => 'Hoog&nbsp;B/W',
    'Home'                 => 'Home',
    'Hour'                 => 'Uur',
    'Hue'                  => 'Hue',
    'Id'                   => 'Id',
    'Idle'                 => 'Ongebruikt',
    'Ignore'               => 'Negeer',
    'Image'                => 'Foto',
    'ImageBufferSize'      => 'Foto buffer grootte (frames)',
    'Images'               => 'Fotos',
    'In'                   => 'In',
    'Include'              => 'voeg in',
    'Inverted'             => 'Omgedraaid',
    'Iris'                 => 'Iris',
    'KeyString'            => 'Sleutel waarde',
    'Label'                => 'Label',
    'Language'             => 'Taal',
    'Last'                 => 'Laatste',
    'Layout'               => 'Layout',                 // Added - 2009-02-08
    'Level'                => 'Nivo',                  // Added - 2011-06-16
    'Libvlc'               => 'Libvlc',
    'LimitResultsPost'     => 'resultaten;', // This is used at the end of the phrase 'Limit to first N results only'
    'LimitResultsPre'      => 'beperk tot eerste', // This is used at the beginning of the phrase 'Limit to first N results only'
    'Line'                 => 'Lijn',                   // Added - 2011-06-16
    'LinkedMonitors'       => 'Gekoppelde monitoren',
    'List'                 => 'Lijst',
    'Load'                 => 'Belasting',
    'Local'                => 'Lokaal',
    'Log'                  => 'Log',                    // Added - 2011-06-16
    'LoggedInAs'           => 'Aangemeld als',
    'Logging'              => 'Logging',                // Added - 2011-06-16
    'LoggingIn'            => 'Aanmelden..',
    'Login'                => 'Aanmelden',
    'Logout'               => 'Afmelden',
    'Logs'                 => 'Logs',                   // Added - 2011-06-17
    'Low'                  => 'Laag',
    'LowBW'                => 'Laag&nbsp;B/W',
    'Main'                 => 'Main',
    'Man'                  => 'Man',
    'Manual'               => 'Handmatig',
    'Mark'                 => 'Markeer',
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
    'Medium'               => 'Medium',
    'MediumBW'             => 'Medium&nbsp;B/W',
    'Message'              => 'Message',                // Added - 2011-06-16
    'MinAlarmAreaLtMax'    => 'Minimum alarm moet kleiner dan het maximum',
    'MinAlarmAreaUnset'    => 'Specificeer het minimaal aantal alarm pixels',
    'MinBlobAreaLtMax'     => 'minimum blob gebied moet kleiner zijn dan maximum blob gebied',
    'MinBlobAreaUnset'     => 'Specificeer het minimaal aantal blob pixels',
    'MinBlobLtMinFilter'   => 'Minimum blob gebied moet kleiner of gelijk aan het minimale filter gebied zijn',
    'MinBlobsLtMax'        => 'Minimum blobs moet kleiner zijn dan maximum blobs',
    'MinBlobsUnset'        => 'Specificeer het minimaal blob aantal',
    'MinFilterAreaLtMax'   => 'Minimum filter gebied moet minder dan het maximum zijn',
    'MinFilterAreaUnset'   => 'Specificeer het minimaal aantal filter pixels',
    'MinFilterLtMinAlarm'  => 'Minimum filter gebied moet kleiner of gelijk aan het minimale alarm gebied zijn',
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
    'Misc'                 => 'Misc',
    'Monitor'              => 'Monitor',
    'MonitorIds'           => 'Monitor&nbsp;Ids',
    'MonitorPreset'        => 'Monitor Preset',
    'MonitorPresetIntro'   => 'Selecteer een preset uit de lijst.<br><br>let op dit overschrijft de reeds ingevoerde waarden voor deze monitor!<br><br>',
    'MonitorProbe'         => 'Monitor Probe',          // Added - 2009-03-31
    'MonitorProbeIntro'    => 'Deze lijst toont gedeteerde analoge en netwerk cameras en of deze al ingebruik of beschikbaar zijn.<br/><br/>Selecteer de gewenste waarde uit de lijst hier beneden.<br/><br/>Let er op dat het mogelijk is dat niet alle cameras hier worden weer gegeven, en dat alle ingevoerde waarden voor de huidige monitor worden overschreven.<br/><br/>', // Added - 2009-03-31
    'Monitors'             => 'Monitoren',
    'Montage'              => 'Montage',
    'Month'                => 'Maand',
    'More'                 => 'Meer',                   // Added - 2011-06-16
    'Move'                 => 'Verplaats',
    'MtgDefault'           => 'Default',              // Added 2013.08.15.
    'Mtg2widgrd'           => '2-wide grid',              // Added 2013.08.15.
    'Mtg3widgrd'           => '3-wide grid',              // Added 2013.08.15.
    'Mtg4widgrd'           => '4-wide grid',              // Added 2013.08.15.
    'Mtg3widgrx'           => '3-wide grid, scaled, enlarge on alarm',              // Added 2013.08.15.
    'MustBeGe'             => 'Moet groter zijn of gelijk aan',
    'MustBeLe'             => 'Moet kleiner zijn of gelijk aan',
    'MustConfirmPassword'  => 'Je moet je wachtwoord bevestigen',
    'MustSupplyPassword'   => 'Je moet een wachtwoord geven',
    'MustSupplyUsername'   => 'Je moet een gebruikersnaam geven',
    'Name'                 => 'Naam',
    'Near'                 => 'Dichtbij',
    'Network'              => 'Netwerk',
    'New'                  => 'Nieuw',
    'NewGroup'             => 'Niew Groep',
    'NewLabel'             => 'Niew Label',
    'NewPassword'          => 'Nieuw wachtwoord',
    'NewState'             => 'Nieuwe status',
    'NewUser'              => 'Nieuwe gebruiker',
    'Next'                 => 'Volgende',
    'No'                   => 'Nee',
    'NoDetectedCameras'    => 'Geen cameras gedeteceerd',    // Added - 2009-03-31
    'NoFramesRecorded'     => 'Er zijn geen frames opgenomen voor deze gebeurtenis',
    'NoGroup'              => 'Geeb Groep',
    'NoSavedFilters'       => 'Geen Opgeslagen Filters',
    'NoStatisticsRecorded' => 'Er zijn geen statistieken opgenomen voor deze gebeurenis',
    'None'                 => 'Geen',
    'NoneAvailable'        => 'Geen beschikbaar',
    'Normal'               => 'Normaal',
    'Notes'                => 'Notities',
    'NumPresets'           => 'Num Voorkeuren',
    'Off'                  => 'Uit',
    'On'                   => 'Aan',
    'OpEq'                 => 'gelijk aan',
    'OpGt'                 => 'groter dan',
    'OpGtEq'               => 'groter dan of gelijk aan',
    'OpIn'                 => 'in set',
    'OpLt'                 => 'kleiner dan',
    'OpLtEq'               => 'kleiner dan of gelijk aan',
    'OpMatches'            => 'Komt overeen',
    'OpNe'                 => 'niet gelijk aan',
    'OpNotIn'              => 'niet in set',
    'OpNotMatches'         => 'Komt niet overeen',
    'Open'                 => 'Open',
    'OptionHelp'           => 'OptieHelp',
    'OptionRestartWarning' => 'Deze veranderingen passen niet aan\nals het systeem loopt. Als je\nKlaar bent met veranderen vergeet dan niet dat\nje ZoneMinder herstart.',
    'Options'              => 'Opties',
    'OrEnterNewName'       => 'of voer een nieuwe naam in',
    'Order'                => 'Sorteer',
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
    'Pid'                  => 'PID',                    // Added - 2011-06-16
    'PixelDiff'            => 'Pixel Diff',
    'Pixels'               => 'pixels',
    'Play'                 => 'Speel',
    'PlayAll'              => 'Speel Alles',
    'PleaseWait'           => 'Wacht A.U.B.',
    'Point'                => 'Punt',
    'PostEventImageBuffer' => 'Post gebeurtenis foto Buffer',
    'PreEventImageBuffer'  => 'Pre gebeurtenis foto Buffer',
    'PreserveAspect'       => 'Beeld verhouding bewaren',
    'Preset'               => 'Voorkeur',
    'Presets'              => 'Voorkeuren',
    'Prev'                 => 'Vorige',
    'Probe'                => 'Scan',                  // Added - 2009-03-31
    'Protocol'             => 'Protocol',
    'Rate'                 => 'Waardering',
    'Real'                 => 'Echte',
    'Record'               => 'Record',
    'RefImageBlendPct'     => 'Referentie foto Blend %ge',
    'Refresh'              => 'Ververs',
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
    'Reset'                => 'Herstel',
    'ResetEventCounts'     => 'Herstel gebeurtenis teller',
    'Restart'              => 'Herstart',
    'Restarting'           => 'Herstarten',
    'RestrictedCameraIds'  => 'Verboden Camera Ids',
    'RestrictedMonitors'   => 'Beperkte Monitoren',
    'ReturnDelay'          => 'Return Delay',
    'ReturnLocation'       => 'Return Locatie',
    'Rewind'               => 'Rewind',
    'RotateLeft'           => 'Draai linksom',
    'RotateRight'          => 'Draai rechtsom',
    'RunLocalUpdate'       => 'Gebruik zmupdate.pl om up te daten', // Added - 2011-05-25
    'RunMode'              => 'Draai Modus',
    'RunState'             => 'Draai Status',
    'Running'              => 'Werkend',
    'Save'                 => 'Opslaan',
    'SaveAs'               => 'Opslaan als',
    'SaveFilter'           => 'Opslaan Filter',
    'Scale'                => 'Schaal',
    'Score'                => 'Score',
    'Secs'                 => 'Secs',
    'Sectionlength'        => 'Sectie lengte',
    'Select'               => 'Selecteer',
    'SelectFormat'         => 'Selecteer Formaat',          // Added - 2011-06-17
    'SelectLog'            => 'Selecteer Log',             // Added - 2011-06-17
    'SelectMonitors'       => 'Selecteer Monitoren',
    'SelfIntersecting'     => 'Polygon randen moeten niet overlappen',
    'Set'                  => 'Zet',
    'SetNewBandwidth'      => 'Zet Nieuwe Bandbreedte',
    'SetPreset'            => 'Zet Preset',
    'Settings'             => 'Instellingen',
    'ShowFilterWindow'     => 'Toon Filter Venster',
    'ShowTimeline'         => 'Toon Tijdslijn',
    'SignalCheckColour'    => 'Signaal controle kleur',
    'Size'                 => 'Groote',
    'SkinDescription'      => 'Wijzig standaard uiterlijk voor deze computer', // Added - 2011-01-30
    'Sleep'                => 'Slaap',
    'SortAsc'              => 'Opl.',
    'SortBy'               => 'Sorteer op',
    'SortDesc'             => 'Afl.',
    'Source'               => 'Bron',
    'SourceColours'        => 'Bron Colours',         // Added - 2009-02-08
    'SourcePath'           => 'Bron Path',            // Added - 2009-02-08
    'SourceType'           => 'Bron Type',
    'Speed'                => 'Snelheid',
    'SpeedHigh'            => 'Hoge Snelheid',
    'SpeedLow'             => 'Lage Snelheid',
    'SpeedMedium'          => 'Medium Snelheid',
    'SpeedTurbo'           => 'Turbo Snelheid',
    'Start'                => 'Start',
    'State'                => 'Status',
    'Stats'                => 'Stats',
    'Status'               => 'Status',
    'Step'                 => 'Stap',
    'StepBack'             => 'Stap Terug',
    'StepForward'          => 'Stap Vooruit',
    'StepLarge'            => 'Groten stap',
    'StepMedium'           => 'Medium Stap',
    'StepNone'             => 'Geen Stap',
    'StepSmall'            => 'Smalle Stap',
    'Stills'               => 'Plaatjes',
    'Stop'                 => 'Stop',
    'Stopped'              => 'Gestopt',
    'Stream'               => 'Stream',
    'StreamReplayBuffer'   => 'Stream Replay foto Buffer',
    'Submit'               => 'Verzenden',
    'System'               => 'Systeem',
    'SystemLog'            => 'Systeem Log',             // Added - 2011-06-16
    'Tele'                 => 'Tele',
    'Thumbnail'            => 'Thumbnail',
    'Tilt'                 => 'Tilt',
    'Time'                 => 'Tijd',
    'TimeDelta'            => 'Tijd Delta',
    'TimeStamp'            => 'Tijdstempel',
    'Timeline'             => 'Tijdslijn',
    'TimelineTip1'         => 'Pass your mouse over the graph to view a snapshot image and event details.',              // Added 2013.08.15.
    'TimelineTip2'         => 'Click on the coloured sections of the graph, or the image, to view the event.',              // Added 2013.08.15.
    'TimelineTip3'         => 'Click on the background to zoom in to a smaller time period based around your click.',              // Added 2013.08.15.
    'TimelineTip4'         => 'Use the controls below to zoom out or navigate back and forward through the time range.',              // Added 2013.08.15.
    'Timestamp'            => 'Tijdstempel',
    'TimestampLabelFormat' => 'Tijdstempel Label Format',
    'TimestampLabelX'      => 'Tijdstempel Label X',
    'TimestampLabelY'      => 'Tijdstempel Label Y',
    'Today'                => 'Vandaag',
    'Tools'                => 'Gereedschappen',
    'Total'                => 'Totaal',                  // Added - 2011-06-16
    'TotalBrScore'         => 'Totaal<br/>Score',
    'TrackDelay'           => 'Track Vertraging',
    'TrackMotion'          => 'Track Beweging',
    'Triggers'             => 'Triggers',
    'TurboPanSpeed'        => 'Turbo Pan Snelheid',
    'TurboTiltSpeed'       => 'Turbo Tilt Snelheid',
    'Type'                 => 'Type',
    'Unarchive'            => 'Dearchiveer',
    'Undefined'            => 'Ongedefineerd',              // Added - 2009-02-08
    'Units'                => 'Eenheden',
    'Unknown'              => 'Onbekend',
    'Update'               => 'Ververs',
    'UpdateAvailable'      => 'Een update voor ZoneMinder is beschikbaar',
    'UpdateNotNecessary'   => 'Geen update noodzakelijk',
    'Updated'              => 'Ververst',                // Added - 2011-06-16
    'UseFilter'            => 'Gebruik Filter',
    'UseFilterExprsPost'   => '&nbsp;filter&nbsp;expressies', // This is used at the end of the phrase 'use N filter expressions'
    'UseFilterExprsPre'    => 'Gebruik&nbsp;', // This is used at the beginning of the phrase 'use N filter expressions'
    'User'                 => 'Gebruiker',
    'Username'             => 'Gebruikersnaam',
    'Users'                => 'Gebruikers',
    'Value'                => 'Waarde',
    'Version'              => 'Versie',
    'VersionIgnore'        => 'Negeer deze versie',
    'VersionRemindDay'     => 'Herinner mij na 1 dag',
    'VersionRemindHour'    => 'Herinner mij na 1 uur',
    'VersionRemindNever'   => 'Herinner mij niet aan nieuwe versies',
    'VersionRemindWeek'    => 'Herinner mij na 1 week',
    'Video'                => 'Video',
    'VideoFormat'          => 'Video Formaat',
    'VideoGenFailed'       => 'Video Generatie mislukt!',
    'VideoGenFiles'        => 'Bestaande video bestanden',
    'VideoGenNoFiles'      => 'Geen video bestanden gevonden',
    'VideoGenParms'        => 'Video Generatie Parameters',
    'VideoGenSucceeded'    => 'Video Generatie voltooid!',
    'VideoSize'            => 'Video grootte',
    'View'                 => 'Bekijk',
    'ViewAll'              => 'Bekijk Alles',
    'ViewEvent'            => 'Bekijk Gebeurtenis',
    'ViewPaged'            => 'Bekijk Pagina',
    'Wake'                 => 'Wakker',
    'WarmupFrames'         => 'Warmop Frames',
    'Watch'                => 'Observeer',
    'Web'                  => 'Web',
    'WebColour'            => 'Web Kleur',
    'Week'                 => 'Week',
    'White'                => 'Wit',
    'WhiteBalance'         => 'Wit Balance',
    'Wide'                 => 'Wijd',
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
    'ZoneFilterSize'       => 'Filter Hoogte/Breedte (pixels)',
    'ZoneMinMaxAlarmArea'  => 'Min/Max Alarmeer Gebied',
    'ZoneMinMaxBlobArea'   => 'Min/Max Blob Gebied',
    'ZoneMinMaxBlobs'      => 'Min/Max Blobs',
    'ZoneMinMaxFiltArea'   => 'Min/Max Gefilterd Gebied',
    'ZoneMinMaxPixelThres' => 'Min/Max Pixel Threshold (0-255)',
    'ZoneMinderLog'        => 'ZoneMinder Log',         // Added - 2011-06-17
    'ZoneOverloadFrames'   => 'Overload Frame negeer aantal',
    'ZoneExtendAlarmFrames' => 'Extend Alarm Frame Count',
    'Zones'                => 'Zones',
    'Zoom'                 => 'Zoom',
    'ZoomIn'               => 'Zoom In',
    'ZoomOut'              => 'Zoom Uit',
);

// Complex replacements with formatting and/or placements, must be passed through sprintf
$CLANG = array(
    'CurrentLogin'         => 'huidige login is \'%1$s\'',
    'EventCount'           => '%1$s %2$s', // Als voorbeeld '37 gebeurtenissen' (from Vlang below)
    'LastEvents'           => 'Last %1$s %2$s', // Als voorbeeld 'Laatste 37 gebeurtenissen' (from Vlang below)
    'LatestRelease'        => 'de laatste release is v%1$s, jij hebt v%2$s.',
    'MonitorCount'         => '%1$s %2$s', // Als voorbeeld '4 Monitoren' (from Vlang below)
    'MonitorFunction'      => 'Monitor %1$s Functie',
    'RunningRecentVer'     => 'U draait al met de laatste versie van ZoneMinder, v%s.',
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
