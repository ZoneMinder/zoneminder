<?php
//
// ZoneMinder web Swedish language file, $Date$, $Revision$
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

// ZoneMinder Swedish Translation by Mikael Carlsson
// Updated 2008-12 by Mikael Carlsson

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
    '24BitColour'          => '24 bitars färg',
    '32BitColour'          => '32 bitars färg',          // Added - 2011-06-15
    '8BitGrey'             => '8 bit gråskala',
    'Action'               => 'Action',
    'Actual'               => 'Verklig',
    'AddNewControl'        => 'Ny kontroll',
    'AddNewMonitor'        => 'Ny bevakare',
    'AddNewServer'         => 'Add New Server',         // Added - 2018-08-30
    'AddNewStorage'        => 'Add New Storage',        // Added - 2018-08-30
    'AddNewUser'           => 'Ny användare',
    'AddNewZone'           => 'Ny zon',
    'Alarm'                => 'Larm',
    'AlarmBrFrames'        => 'Larm<br/>ramar',
    'AlarmFrame'           => 'Larmram',
    'AlarmFrameCount'      => 'Larmramsräknare',
    'AlarmLimits'          => 'Larmgränser',
    'AlarmMaximumFPS'      => 'Max. ramar/s för larm',
    'AlarmPx'              => 'Larmpunkter',
    'AlarmRGBUnset'        => 'Du måste sätta en färg för RGB-larm',
    'AlarmRefImageBlendPct'=> 'Alarm Reference Image Blend %ge', // Added - 2015-04-18
    'Alert'                => 'Varning',
    'All'                  => 'Alla',
    'AnalysisFPS'          => 'Analysis FPS',           // Added - 2015-07-22
    'AnalysisUpdateDelay'  => 'Analysis Update Delay',  // Added - 2015-07-23
    'Apply'                => 'Lägg till',
    'ApplyingStateChange'  => 'Aktivera statusändring',
    'ArchArchived'         => 'Arkivera endast',
    'ArchUnarchived'       => 'Endast ej arkiverade',
    'Archive'              => 'Arkiv',
    'Archived'             => 'Arkiverad',
    'Area'                 => 'Område',
    'AreaUnits'            => 'Område (px/%)',
    'AttrAlarmFrames'      => 'Larmramar',
    'AttrArchiveStatus'    => 'Arkivstatus',
    'AttrAvgScore'         => 'Ung. värde',
    'AttrCause'            => 'Orsak',
    'AttrDiskBlocks'       => 'Diskblock',
    'AttrDiskPercent'      => 'Diskprocent',
    'AttrDiskSpace'        => 'Disk Space',             // Added - 2018-08-30
    'AttrDuration'         => 'Längd',
    'AttrEndDate'          => 'End Date',               // Added - 2018-08-30
    'AttrEndDateTime'      => 'End Date/Time',          // Added - 2018-08-30
    'AttrEndTime'          => 'End Time',               // Added - 2018-08-30
    'AttrEndWeekday'       => 'End Weekday',            // Added - 2018-08-30
    'AttrFilterServer'     => 'Server Filter is Running On', // Added - 2018-08-30
    'AttrFrames'           => 'Ramar',
    'AttrId'               => 'Id',
    'AttrMaxScore'         => 'Max. värde',
    'AttrMonitorId'        => 'Bevakningsid',
    'AttrMonitorName'      => 'Bevakningsnamn',
    'AttrMonitorServer'    => 'Server Monitor is Running On', // Added - 2018-08-30
    'AttrName'             => 'Namn',
    'AttrNotes'            => 'Notering',
    'AttrStartDate'        => 'Start Date',             // Added - 2018-08-30
    'AttrStartDateTime'    => 'Start Date/Time',        // Added - 2018-08-30
    'AttrStartTime'        => 'Start Time',             // Added - 2018-08-30
    'AttrStartWeekday'     => 'Start Weekday',          // Added - 2018-08-30
    'AttrStateId'          => 'Run State',              // Added - 2018-08-30
    'AttrStorageArea'      => 'Storage Area',           // Added - 2018-08-30
    'AttrStorageServer'    => 'Server Hosting Storage', // Added - 2018-08-30
    'AttrSystemLoad'       => 'Systemlast',
    'AttrTotalScore'       => 'Totalvärde',
    'Auto'                 => 'Automatik',
    'AutoStopTimeout'      => 'Tidsutlösning för automatstop',
    'Available'            => 'Available',              // Added - 2009-03-31
    'AvgBrScore'           => 'Ung.<br/>träff',
    'Background'           => 'Bakgrund',
    'BackgroundFilter'     => 'Kör filter i bakgrunden',
    'BadAlarmFrameCount'   => 'Ramantalet för larm måste vara ett heltal, minsta värdet är 1',
    'BadAlarmMaxFPS'       => 'Larm för bilder/s måste vara ett positivt heltal eller ett flyttal',
    'BadAnalysisFPS'       => 'Analysis FPS must be a positive integer or floating point value', // Added - 2015-07-22
    'BadAnalysisUpdateDelay'=> 'Analysis update delay must be set to an integer of zero or more', // Added - 2015-07-23
    'BadChannel'           => 'Kanalen måste vara ett heltal, noll eller högre',
    'BadColours'           => 'Target colour must be set to a valid value', // Added - 2011-06-15
    'BadDevice'            => 'Enheten måste sättas till ett giltigt värde',
    'BadFPSReportInterval' => 'Buffern för ramintervallrapporten måste vara ett heltal på minst 0 eller högre',
    'BadFormat'            => 'Formatet måste vara ett heltal, noll eller högre',
    'BadFrameSkip'         => 'Värdet för ramöverhopp måste vara ett heltal på 0 eller högre',
    'BadHeight'            => 'Höjden måste sättas till ett giltigt värde',
    'BadHost'              => 'Detta fält ska innehålla en giltig ip-adress eller värdnamn, inkludera inte http://',
    'BadImageBufferCount'  => 'Bufferstorleken för avbilden måste vara ett heltal på minst 10 eller högre',
    'BadLabelX'            => 'Etiketten för X koordinaten måste sättas till ett heltal, 0 eller högre',
    'BadLabelY'            => 'Etiketten för Y koordinaten måste sättas till ett heltal, 0 eller högre',
    'BadMaxFPS'            => 'Max. ramar/s måste vara ett positivt heltal eller ett flyttal',
    'BadMotionFrameSkip'   => 'Motion Frame skip count must be an integer of zero or more',
    'BadNameChars'         => 'Namn kan endast innehålla alfanumeriska tecken, utrymmen, bindestreck och understreck',
    'BadPalette'           => 'Palette must be set to a valid value', // Added - 2009-03-31
    'BadPath'              => 'Sökvägen måste innehålla ett giltigt värde',
    'BadPort'              => 'Porten måste innehålla ett giltigt nummer',
    'BadPostEventCount'    => 'Räknaren för efterhändelsen måste vara ett heltal på 0 eller högre',
    'BadPreEventCount'     => 'Räknaren för för-händelsen måste vara ett heltal på 0 eller högre, och mindre än bufferstorleken på avbilden',
    'BadRefBlendPerc'      => 'Mixprocenten för referensen måste hara ett positivt heltal',
    'BadSectionLength'     => 'Sektionslängden måste vara ett heltal på minst 30 eller högre',
    'BadSignalCheckColour' => 'Kontrollfärgen på signalen måste vara en giltig RGB färgsträng',
    'BadSourceType'        => 'Source Type \"Web Site\" requires the Function to be set to \"Monitor\"', // Added - 2018-08-30
    'BadStreamReplayBuffer'=> 'Buffern för strömmande uppspelning måste vara ett heltal på 0 eller högre',
    'BadWarmupCount'       => 'Uppvärmingsramen måste vara ett heltal på 0 eller högre',
    'BadWebColour'         => 'Webbfärgen måste vara en giltig sträng för webbfärg',
    'BadWebSitePath'       => 'Please enter a complete website url, including the http:// or https:// prefix.', // Added - 2018-08-30
    'BadWidth'             => 'Bredden måste sättas til ett giltigt värde',
    'Bandwidth'            => 'Bandbredd',
    'BandwidthHead'         => 'Bandwidth',	// This is the end of the bandwidth status on the top of the console, different in many language due to phrasing
    'BlobPx'               => 'Blob Px',
    'BlobSizes'            => 'Blobstorlek',
    'Blobs'                => 'Blobbar',
    'Brightness'           => 'Ljusstyrka',
    'Buffer'               => 'Buffer',                 // Added - 2015-04-18
    'Buffers'              => 'Buffrar',
    'CSSDescription'       => 'Change the default css for this computer', // Added - 2015-04-18
    'CanAutoFocus'         => 'Har autofokus',
    'CanAutoGain'          => 'Har autonivå',
    'CanAutoIris'          => 'Har autoiris',
    'CanAutoWhite'         => 'Har autovitbalans.',
    'CanAutoZoom'          => 'Har autozoom',
    'CanFocus'             => 'Har fokus',
    'CanFocusAbs'          => 'Har absolut fokus',
    'CanFocusCon'          => 'Har kontinuerlig fokus',
    'CanFocusRel'          => 'Har relativ fokus',
    'CanGain'              => 'Har nivå',
    'CanGainAbs'           => 'Har absolut nivå',
    'CanGainCon'           => 'Har kontinuerlig nivå',
    'CanGainRel'           => 'Har relativ nivå',
    'CanIris'              => 'Har iris',
    'CanIrisAbs'           => 'Har absolut iris',
    'CanIrisCon'           => 'Har kontinuerlig iris',
    'CanIrisRel'           => 'Har relativ iris',
    'CanMove'              => 'Har förflyttning',
    'CanMoveAbs'           => 'Har absolut förflyttning',
    'CanMoveCon'           => 'Har kontinuerlig förflyttning',
    'CanMoveDiag'          => 'Har diagonal förflyttning',
    'CanMoveMap'           => 'Har mappad förflyttning',
    'CanMoveRel'           => 'Har relativ förflyttning',
    'CanPan'               => 'Har panorering',
    'CanReset'             => 'Har återställning',
	'CanReboot'             => 'Can Reboot',
    'CanSetPresets'        => 'Har förinställningar',
    'CanSleep'             => 'Kan vila',
    'CanTilt'              => 'Kan tilta',
    'CanWake'              => 'Kan vakna',
    'CanWhite'             => 'Kan vitbalansera',
    'CanWhiteAbs'          => 'Har absolut vitbalans',
    'CanWhiteBal'          => 'Kan vitbalans',
    'CanWhiteCon'          => 'Kan kontinuerligt vitbalansera',
    'CanWhiteRel'          => 'Kan relativt vitbalansera',
    'CanZoom'              => 'Kan zooma',
    'CanZoomAbs'           => 'Kan zooma absolut',
    'CanZoomCon'           => 'Kan zooma kontinuerligt',
    'CanZoomRel'           => 'Kan zooma realativt',
    'Cancel'               => 'Ångra',
    'CancelForcedAlarm'    => 'Ångra tvingande larm',
    'CaptureHeight'        => 'Fångsthöjd',
    'CaptureMethod'        => 'Capture Method',         // Added - 2009-02-08
    'CapturePalette'       => 'Fångstpalett',
    'CaptureResolution'    => 'Capture Resolution',     // Added - 2015-04-18
    'CaptureWidth'         => 'Fångstbredd',
    'Cause'                => 'Orsak',
    'CheckMethod'          => 'Larmkontrollmetod',
    'ChooseDetectedCamera' => 'Choose Detected Camera', // Added - 2009-03-31
    'ChooseFilter'         => 'Välj filter',
    'ChooseLogFormat'      => 'Choose a log format',    // Added - 2011-06-17
    'ChooseLogSelection'   => 'Choose a log selection', // Added - 2011-06-17
    'ChoosePreset'         => 'Välj standard',
    'Clear'                => 'Clear',                  // Added - 2011-06-16
    'CloneMonitor'         => 'Clone',                  // Added - 2018-08-30
    'Close'                => 'Stäng',
    'Colour'               => 'Färg',
    'Command'              => 'Kommando',
    'Component'            => 'Component',              // Added - 2011-06-16
    'ConcurrentFilter'     => 'Run filter concurrently', // Added - 2018-08-30
    'Config'               => 'Konfigurera',
    'ConfiguredFor'        => 'Konfigurerad för',
    'ConfirmDeleteEvents'  => 'Är du säker på att du vill ta bort dom valda händelserna?',
    'ConfirmPassword'      => 'Bekräfta lösenord',
    'ConjAnd'              => 'och',
    'ConjOr'               => 'eller',
    'Console'              => 'Konsoll',
    'ContactAdmin'         => 'Kontakta din administratör för detaljer.',
    'Continue'             => 'Fortsätt',
    'Contrast'             => 'Kontrast',
    'Control'              => 'Kontroll',
    'ControlAddress'       => 'Kontrolladress',
    'ControlCap'           => 'Kontrollförmåga',
    'ControlCaps'          => 'Kontrollförmågor',
    'ControlDevice'        => 'Kontrollenhet',
    'ControlType'          => 'Kontrolltyp',
    'Controllable'         => 'Kontrollerbar',
    'Current'              => 'Current',                // Added - 2015-04-18
    'Cycle'                => 'Period',
    'CycleWatch'           => 'Cycle Watch',
    'DateTime'             => 'Date/Time',              // Added - 2011-06-16
    'Day'                  => 'Dag',
    'Debug'                => 'Avlusa',
    'DefaultRate'          => 'Standardhastighet',
    'DefaultScale'         => 'Standardskala',
    'DefaultView'          => 'Standardvy',
    'Deinterlacing'        => 'Deinterlacing',          // Added - 2015-04-18
    'Delay'                => 'Delay',                  // Added - 2015-04-18
    'Delete'               => 'Radera',
    'DeleteAndNext'        => 'Radera &amp; Nästa',
    'DeleteAndPrev'        => 'Radera &amp; Föreg.',
    'DeleteSavedFilter'    => 'Radera sparade filter',
    'Description'          => 'Beskrivning',
    'DetectedCameras'      => 'Detected Cameras',       // Added - 2009-03-31
    'DetectedProfiles'     => 'Detected Profiles',      // Added - 2015-04-18
    'Device'               => 'Device',                 // Added - 2009-02-08
    'DeviceChannel'        => 'Enhetskanal',
    'DeviceFormat'         => 'Enhetsformat',
    'DeviceNumber'         => 'Enhetsnummer',
    'DevicePath'           => 'Enhetssökväg',
    'Devices'              => 'Enheter',
    'Dimensions'           => 'Dimensioner',
    'DisableAlarms'        => 'Avaktivera larm',
    'Disk'                 => 'Disk',
    'Display'              => 'Display',                // Added - 2011-01-30
    'Displaying'           => 'Displaying',             // Added - 2011-06-16
    'DoNativeMotionDetection'=> 'Do Native Motion Detection',
    'Donate'               => 'Var vänlig och donera',
    'DonateAlready'        => 'Nej, Jag har redan donerat',
    'DonateEnticement'     => 'Du har kört ZoneMinder ett tag nu och förhoppningsvis har du sett att det fungerar bra hemma eller på ditt företag. Även om ZoneMinder är, och kommer att vara, fri programvara och öppen kallkod, så kostar det pengar att utveckla och underhålla. Om du vill hjälpa till med framtida utveckling och nya funktioner så var vanlig och bidrag med en slant. Bidragen är naturligtvis en option men mycket uppskattade och du kan bidra med precis hur mycket du vill.<br><br>Om du vill ge ett bidrag väljer du nedan eller surfar till https://zoneminder.com/donate/.<br><br>Tack för att du använder ZoneMinder, glöm inte att besöka forumen på ZoneMinder.com för support och förslag om hur du får din ZoneMinder att fungera lite bättre.',
    'DonateRemindDay'      => 'Inte än, påminn om 1 dag',
    'DonateRemindHour'     => 'Inte än, påminn om en 1 timme',
    'DonateRemindMonth'    => 'Inte än, påminn om 1 månad',
    'DonateRemindNever'    => 'Nej, Jag vill inte donera, påminn mig inte mer',
    'DonateRemindWeek'     => 'Inte än, påminn om 1 vecka',
    'DonateYes'            => 'Ja, jag vill gärna donera nu',
    'Download'             => 'Ladda ner',
    'DownloadVideo'        => 'Download Video',         // Added - 2018-08-30
    'DuplicateMonitorName' => 'Duplicate Monitor Name', // Added - 2009-03-31
    'Duration'             => 'Längd',
    'Edit'                 => 'Redigera',
    'EditLayout'           => 'Edit Layout',            // Added - 2018-08-30
    'Email'                => 'E-post',
    'EnableAlarms'         => 'Aktivera larm',
    'Enabled'              => 'Aktiverad',
    'EnterNewFilterName'   => 'Mata in nytt filternamn',
    'Error'                => 'Fel',
    'ErrorBrackets'        => 'Fel, kontrollera att du har samma antal vänster som höger-hakar',
    'ErrorValidValue'      => 'Fel, kontrollera att alla parametrar har giltligt värde',
    'Etc'                  => 'etc',
    'Event'                => 'Händelse',
    'EventFilter'          => 'Händelsefilter',
    'EventId'              => 'Händelse nr',
    'EventName'            => 'Händelsenamn',
    'EventPrefix'          => 'Händelseprefix',
    'Events'               => 'Händelser',
    'Exclude'              => 'Exkludera',
    'Execute'              => 'Utför',
    'Exif'                 => 'Embed EXIF data into image', // Added - 2018-08-30
    'Export'               => 'Exportera',
    'ExportDetails'        => 'Exportera händelsedetaljer',
    'ExportFailed'         => 'Exporten misslyckades',
    'ExportFormat'         => 'Filformat för exporter',
    'ExportFormatTar'      => 'Tar',
    'ExportFormatZip'      => 'Zip',
    'ExportFrames'         => 'Exportera ramdetaljer',
    'ExportImageFiles'     => 'Exportera bildfiler',
    'ExportLog'            => 'Export Log',             // Added - 2011-06-17
    'ExportMiscFiles'      => 'Exportera andra filer (om dom finns)',
    'ExportOptions'        => 'Konfiguera export',
    'ExportSucceeded'      => 'Export Succeeded',       // Added - 2009-02-08
    'ExportVideoFiles'     => 'Exportera videofiler (om dom finns)',
    'Exporting'            => 'Exporterar',
    'FPS'                  => 'fps',
    'FPSReportInterval'    => 'FPS rapportintervall',
    'FTP'                  => 'FTP',
    'Far'                  => 'Far',
    'FastForward'          => 'Fast Forward',
    'Feed'                 => 'Matning',
    'Ffmpeg'               => 'Ffmpeg',                 // Added - 2009-02-08
    'File'                 => 'Fil',
    'Filter'               => 'Filter',                 // Added - 2015-04-18
    'FilterArchiveEvents'  => 'Arkivera alla träffar',
    'FilterDeleteEvents'   => 'Radera alla träffar',
    'FilterEmailEvents'    => 'Skicka e-post med detaljer om alla träffar',
    'FilterExecuteEvents'  => 'Utför kommando på alla träffar',
    'FilterLog'            => 'Filter log',             // Added - 2015-04-18
    'FilterMessageEvents'  => 'Meddela detaljer om alla träffar',
    'FilterMoveEvents'     => 'Move all matches',       // Added - 2018-08-30
    'FilterPx'             => 'Filter Px',
    'FilterUnset'          => 'Du måste specificera filtrets bredd och höjd',
    'FilterUpdateDiskSpace'=> 'Update used disk space', // Added - 2018-08-30
    'FilterUploadEvents'   => 'Ladda upp alla träffar',
    'FilterVideoEvents'    => 'Skapa video för alla träffar',
    'Filters'              => 'Filter',
    'First'                => 'Först',
    'FlippedHori'          => 'Vänd horisontellt',
    'FlippedVert'          => 'Vänd vertikalt',
    'FnMocord'              => 'Mocord',            // Added 2013.08.16.
    'FnModect'              => 'Modect',            // Added 2013.08.16.
    'FnMonitor'             => 'Monitor',            // Added 2013.08.16.
    'FnNodect'              => 'Nodect',            // Added 2013.08.16.
    'FnNone'                => 'None',            // Added 2013.08.16.
    'FnRecord'              => 'Record',            // Added 2013.08.16.
    'Focus'                => 'Fokus',
    'ForceAlarm'           => 'Tvinga larm',
    'Format'               => 'Format',
    'Frame'                => 'Ram',
    'FrameId'              => 'Ram id',
    'FrameRate'            => 'Ram hastighet',
    'FrameSkip'            => 'Hoppa över ram',
    'Frames'               => 'Ramar',
    'Func'                 => 'Funk',
    'Function'             => 'Funktion',
    'Gain'                 => 'Nivå',
    'General'              => 'Generell',
    'GenerateDownload'     => 'Generate Download',      // Added - 2018-08-30
    'GenerateVideo'        => 'Skapa video',
    'GeneratingVideo'      => 'Skapar video',
    'GoToZoneMinder'       => 'Gå till ZoneMinder.com',
    'Grey'                 => 'Grå',
    'Group'                => 'Grupp',
    'Groups'               => 'Grupper',
    'HasFocusSpeed'        => 'Har focushastighet',
    'HasGainSpeed'         => 'Har nivåhastighet',
    'HasHomePreset'        => 'Har normalinställning',
    'HasIrisSpeed'         => 'Har irishastighet',
    'HasPanSpeed'          => 'Har panoramahastighet',
    'HasPresets'           => 'Har förinställningar',
    'HasTiltSpeed'         => 'Har tilthastighet',
    'HasTurboPan'          => 'Har turbopanorering',
    'HasTurboTilt'         => 'Har turbotilt',
    'HasWhiteSpeed'        => 'Har vitbalanshastighet',
    'HasZoomSpeed'         => 'Har Zoomhastighet',
    'High'                 => 'Hög',
    'HighBW'               => 'Hög bandbredd',
    'Home'                 => 'Hem',
    'Hostname'             => 'Hostname',               // Added - 2018-08-30
    'Hour'                 => 'Timme',
    'Hue'                  => 'Hue',
    'Id'                   => 'nr',
    'Idle'                 => 'Vila',
    'Ignore'               => 'Ignorera',
    'Image'                => 'Bild',
    'ImageBufferSize'      => 'Bildbufferstorlek (ramar)',
    'Images'               => 'Images',
    'In'                   => 'I',
    'Include'              => 'Inkludera',
    'Inverted'             => 'Inverterad',
    'Iris'                 => 'Iris',
    'KeyString'            => 'Nyckelsträng',
    'Label'                => 'Etikett',
    'Language'             => 'Språk',
    'Last'                 => 'Sist',
    'Layout'               => 'Layout',                 // Added - 2009-02-08
    'Level'                => 'Level',                  // Added - 2011-06-16
    'Libvlc'               => 'Libvlc',
    'LimitResultsPost'     => 'resultaten;', // This is used at the end of the phrase 'Limit to first N results only'
    'LimitResultsPre'      => 'Begränsa till första', // This is used at the beginning of the phrase 'Limit to first N results only'
    'Line'                 => 'Line',                   // Added - 2011-06-16
    'LinkedMonitors'       => 'Länkade övervakare',
    'List'                 => 'Lista',
    'ListMatches'          => 'List Matches',           // Added - 2018-08-30
    'Load'                 => 'Belastning',
    'Local'                => 'Lokal',
    'Log'                  => 'Log',                    // Added - 2011-06-16
    'LoggedInAs'           => 'Inloggad som',
    'Logging'              => 'Logging',                // Added - 2011-06-16
    'LoggingIn'            => 'Loggar in',
    'Login'                => 'Logga in',
    'Logout'               => 'Logga ut',
    'Logs'                 => 'Logs',                   // Added - 2011-06-17
    'Low'                  => 'Låg',
    'LowBW'                => 'Låg bandbredd',
    'Main'                 => 'Huvudmeny',
    'Man'                  => 'Man',
    'Manual'               => 'Manuell',
    'Mark'                 => 'Markera',
    'Max'                  => 'Max',
    'MaxBandwidth'         => 'Max bandbredd',
    'MaxBrScore'           => 'Max.<br/>Score',
    'MaxFocusRange'        => 'Max fokusområde',
    'MaxFocusSpeed'        => 'Max fokushastighet',
    'MaxFocusStep'         => 'Max fokussteg',
    'MaxGainRange'         => 'Max nivåområde',
    'MaxGainSpeed'         => 'Max nivåhastighet',
    'MaxGainStep'          => 'Max nivåsteg',
    'MaxIrisRange'         => 'Max irsiområde',
    'MaxIrisSpeed'         => 'Max irishastighet',
    'MaxIrisStep'          => 'Max irissteg',
    'MaxPanRange'          => 'Max panoramaområde',
    'MaxPanSpeed'          => 'Max panoramahastighet',
    'MaxPanStep'           => 'Max panoramasteg',
    'MaxTiltRange'         => 'Max tiltområde',
    'MaxTiltSpeed'         => 'Max tilthastighet',
    'MaxTiltStep'          => 'Max tiltsteg',
    'MaxWhiteRange'        => 'Max vitbalansområde',
    'MaxWhiteSpeed'        => 'Max vitbalanshastighet',
    'MaxWhiteStep'         => 'Max vitbalanssteg',
    'MaxZoomRange'         => 'Max zoomområde',
    'MaxZoomSpeed'         => 'Max zoomhastighet',
    'MaxZoomStep'          => 'Max zoomsteg',
    'MaximumFPS'           => 'Max ramar/s',
    'Medium'               => 'Mellan',
    'MediumBW'             => 'Mellan bandbredd',
    'Message'              => 'Message',                // Added - 2011-06-16
    'MinAlarmAreaLtMax'    => 'Minsta larmarean skall vara mindre än största',
    'MinAlarmAreaUnset'    => 'Du måste ange minsta antal larmbildpunkter',
    'MinBlobAreaLtMax'     => 'Minsta blobarean skall vara mindre än högsta',
    'MinBlobAreaUnset'     => 'Du måste ange minsta antalet blobpixlar',
    'MinBlobLtMinFilter'   => 'Minsta blobarean skall vara mindre än eller lika med minsta filterarean',
    'MinBlobsLtMax'        => 'Minsta antalet blobbar skall vara mindre än största',
    'MinBlobsUnset'        => 'Du måste ange minsta antalet blobbar',
    'MinFilterAreaLtMax'   => 'Minsta filterarean skall vara mindre än högsta',
    'MinFilterAreaUnset'   => 'Du måste ange minsta antal filterbildpunkter',
    'MinFilterLtMinAlarm'  => 'Minsta filterarean skall vara mindre än eller lika med minsta larmarean',
    'MinFocusRange'        => 'Min fokusområde',
    'MinFocusSpeed'        => 'Min fokushastighet',
    'MinFocusStep'         => 'Min fokussteg',
    'MinGainRange'         => 'Min nivåområde',
    'MinGainSpeed'         => 'Min nivåhastighet',
    'MinGainStep'          => 'Min nivåsteg',
    'MinIrisRange'         => 'Min irisområde',
    'MinIrisSpeed'         => 'Min irishastighet',
    'MinIrisStep'          => 'Min irissteg',
    'MinPanRange'          => 'Min panoramaområde',
    'MinPanSpeed'          => 'Min panoramahastighet',
    'MinPanStep'           => 'Min panoramasteg',
    'MinPixelThresLtMax'   => 'Minsta tröskelvärde för bildpunkter ska vara mindre än högsta',
    'MinPixelThresUnset'   => 'Du måste ange minsta tröskelvärde för bildpunkter',
    'MinTiltRange'         => 'Min tiltområde',
    'MinTiltSpeed'         => 'Min tilthastighet',
    'MinTiltStep'          => 'Min tiltsteg',
    'MinWhiteRange'        => 'Min vitbalansområde',
    'MinWhiteSpeed'        => 'Min vitbalanshastighet',
    'MinWhiteStep'         => 'Min vitbalanssteg',
    'MinZoomRange'         => 'Min zoomområde',
    'MinZoomSpeed'         => 'Min zoomhastighet',
    'MinZoomStep'          => 'Min zoomsteg',
    'Misc'                 => 'Övrigt',
    'Mode'                 => 'Mode',                   // Added - 2015-04-18
    'Monitor'              => 'Bevakning',
    'MonitorIds'           => 'Bevakningsnr',
    'MonitorPreset'        => 'Förinställd bevakning',
    'MonitorPresetIntro'   => 'Välj en förinställning från listan.<br><br>Var medveten om att detta kan skriva över inställningar du redan gjort för denna bevakare.<br><br>',
    'MonitorProbe'         => 'Monitor Probe',          // Added - 2009-03-31
    'MonitorProbeIntro'    => 'The list below shows detected analog and network cameras and whether they are already being used or available for selection.<br/><br/>Select the desired entry from the list below.<br/><br/>Please note that not all cameras may be detected and that choosing a camera here may overwrite any values you already have configured for the current monitor.<br/><br/>', // Added - 2009-03-31
    'Monitors'             => 'Bevakare',
    'Montage'              => 'Montera',
    'MontageReview'        => 'Montage Review',         // Added - 2018-08-30
    'Month'                => 'Månad',
    'More'                 => 'More',                   // Added - 2011-06-16
    'MotionFrameSkip'      => 'Motion Frame Skip',
    'Move'                 => 'Flytta',
    'Mtg2widgrd'           => '2-wide grid',              // Added 2013.08.15.
    'Mtg3widgrd'           => '3-wide grid',              // Added 2013.08.15.
    'Mtg3widgrx'           => '3-wide grid, scaled, enlarge on alarm',              // Added 2013.08.15.
    'Mtg4widgrd'           => '4-wide grid',              // Added 2013.08.15.
    'MtgDefault'           => 'Default',              // Added 2013.08.15.
    'MustBeGe'             => 'måste vara större än eller lika med',
    'MustBeLe'             => 'måste vara mindre än eller lika med',
    'MustConfirmPassword'  => 'Du måste bekräfta lösenordet',
    'MustSupplyPassword'   => 'Du måste ange ett lösenord',
    'MustSupplyUsername'   => 'Du måste ange ett användarnamn',
    'Name'                 => 'Namn',
    'Near'                 => 'Nära',
    'Network'              => 'Nätverk',
    'New'                  => 'Ny',
    'NewGroup'             => 'Ny grupp',
    'NewLabel'             => 'Ny etikett',
    'NewPassword'          => 'Nytt lösenord',
    'NewState'             => 'Nytt läge',
    'NewUser'              => 'Ny användare',
    'Next'                 => 'Nästa',
    'No'                   => 'Nej',
    'NoDetectedCameras'    => 'No Detected Cameras',    // Added - 2009-03-31
    'NoDetectedProfiles'   => 'No Detected Profiles',   // Added - 2018-08-30
    'NoFramesRecorded'     => 'Det finns inga ramar inspelade för denna händelse',
    'NoGroup'              => 'Ingen grupp',
    'NoSavedFilters'       => 'Inga sparade filter',
    'NoStatisticsRecorded' => 'Det finns ingen statistik inspelad för denna händelse/ram',
    'None'                 => 'Ingen',
    'NoneAvailable'        => 'Ingen tillgänglig',
    'Normal'               => 'Normal',
    'Notes'                => 'Not.',
    'NumPresets'           => 'Antal förinställningar',
    'Off'                  => 'Av',
    'On'                   => 'På',
    'OnvifCredentialsIntro'=> 'Please supply user name and password for the selected camera.<br/>If no user has been created for the camera then the user given here will be created with the given password.<br/><br/>', // Added - 2015-04-18
    'OnvifProbe'           => 'ONVIF',                  // Added - 2015-04-18
    'OnvifProbeIntro'      => 'The list below shows detected ONVIF cameras and whether they are already being used or available for selection.<br/><br/>Select the desired entry from the list below.<br/><br/>Please note that not all cameras may be detected and that choosing a camera here may overwrite any values you already have configured for the current monitor.<br/><br/>', // Added - 2015-04-18
    'OpEq'                 => 'lika med',
    'OpGt'                 => 'större än',
    'OpGtEq'               => 'större än eller lika med',
    'OpIn'                 => 'in set',
    'OpIs'                 => 'is',                     // Added - 2018-08-30
    'OpIsNot'              => 'is not',                 // Added - 2018-08-30
    'OpLt'                 => 'mindre än',
    'OpLtEq'               => 'mindre än eller lika med',
    'OpMatches'            => 'matchar',
    'OpNe'                 => 'inte lika med',
    'OpNotIn'              => 'inte i set',
    'OpNotMatches'         => 'matchar inte',
    'Open'                 => 'Öppna',
    'OptionHelp'           => 'Optionhjälp',
    'OptionRestartWarning' => 'Dessa ändringar kommer inte att vara implementerade\nnär systemet körs. När du är klar starta om\n ZoneMinder.',
    'OptionalEncoderParam' => 'Optional Encoder Parameters', // Added - 2018-08-30
    'Options'              => 'Alternativ',
    'OrEnterNewName'       => 'eller skriv in nytt namn',
    'Order'                => 'Sortera',
    'Orientation'          => 'Orientation',
    'Out'                  => 'Ut',
    'OverwriteExisting'    => 'Skriv över',
    'Paged'                => 'Paged',
    'Pan'                  => 'Panorera',
    'PanLeft'              => 'Panorera vänster',
    'PanRight'             => 'Panorera höger',
    'PanTilt'              => 'Pan/Tilt',
    'Parameter'            => 'Parameter',
    'Password'             => 'Lösenord',
    'PasswordsDifferent'   => 'Lösenorden skiljer sig åt',
    'Paths'                => 'Sökvägar',
    'Pause'                => 'Paus',
    'Phone'                => 'Mobil',
    'PhoneBW'              => 'Mobil bandbredd',
    'Pid'                  => 'PID',                    // Added - 2011-06-16
    'PixelDiff'            => 'Skillnad i bildpunkter',
    'Pixels'               => 'bildpunkter',
    'Play'                 => 'Spela',
    'PlayAll'              => 'Visa alla',
    'PleaseWait'           => 'Vänta...',
    'Plugins'              => 'Plugins',
    'Point'                => 'Punkt',
    'PostEventImageBuffer' => 'Post Event Image Count',
    'PreEventImageBuffer'  => 'Pre Event Image Count',
    'PreserveAspect'       => 'Bevara lägesförhållande',
    'Preset'               => 'Förinställning',
    'Presets'              => 'Förinställningar',
    'Prev'                 => 'Föreg.',
    'Probe'                => 'Probe',                  // Added - 2009-03-31
    'ProfileProbe'         => 'Stream Probe',           // Added - 2015-04-18
    'ProfileProbeIntro'    => 'The list below shows the existing stream profiles of the selected camera .<br/><br/>Select the desired entry from the list below.<br/><br/>Please note that ZoneMinder cannot configure additional profiles and that choosing a camera here may overwrite any values you already have configured for the current monitor.<br/><br/>', // Added - 2015-04-18
    'Progress'             => 'Progress',               // Added - 2015-04-18
    'Protocol'             => 'Protokol',
    'RTSPDescribe'         => 'Use RTSP Response Media URL', // Added - 2018-08-30
    'RTSPTransport'        => 'RTSP Transport Protocol', // Added - 2018-08-30
    'Rate'                 => 'Hastighet',
    'Real'                 => 'Verklig',
    'RecaptchaWarning'     => 'Your reCaptcha secret key is invalid. Please correct it, or reCaptcha will not work', // Added - 2018-08-30
    'Record'               => 'Spela in',
    'RecordAudio'          => 'Whether to store the audio stream when saving an event.', // Added - 2018-08-30
    'RefImageBlendPct'     => 'Reference Image Blend %ge',
    'Refresh'              => 'Uppdatera',
    'Remote'               => 'Fjärr',
    'RemoteHostName'       => 'Fjärrnamn',
    'RemoteHostPath'       => 'Fjärrsökväg',
    'RemoteHostPort'       => 'Fjärrport',
    'RemoteHostSubPath'    => 'Remote Host SubPath',    // Added - 2009-02-08
    'RemoteImageColours'   => 'Fjärrbildfärger',
    'RemoteMethod'         => 'Remote Method',          // Added - 2009-02-08
    'RemoteProtocol'       => 'Remote Protocol',        // Added - 2009-02-08
    'Rename'               => 'Byt namn',
    'Replay'               => 'Repris',
    'ReplayAll'            => 'Alla händelser',
    'ReplayGapless'        => 'Gapless Events',
    'ReplaySingle'         => 'Ensam händelse',
    'ReportEventAudit'     => 'Audit Events Report',    // Added - 2018-08-30
    'Reset'                => 'Återställ',
    'ResetEventCounts'     => 'Återställ händelseräknare',
    'Restart'              => 'Återstart',
    'Restarting'           => 'Återstartar',
    'RestrictedCameraIds'  => 'Begränsade kameranr.',
    'RestrictedMonitors'   => 'Begränsade bevakare',
    'ReturnDelay'          => 'Fördröjd retur',
    'ReturnLocation'       => 'Återvänd till position',
    'Rewind'               => 'Backa',
    'RotateLeft'           => 'Rotera vänster',
    'RotateRight'          => 'Rotera höger',
    'RunLocalUpdate'       => 'Please run zmupdate.pl to update', // Added - 2011-05-25
    'RunMode'              => 'Körläge',
    'RunState'             => 'Körläge',
    'Running'              => 'Körs',
    'Save'                 => 'Spara',
    'SaveAs'               => 'Spara som',
    'SaveFilter'           => 'Spara filter',
    'SaveJPEGs'            => 'Save JPEGs',             // Added - 2018-08-30
    'Scale'                => 'Skala',
    'Score'                => 'Resultat',
    'Secs'                 => 'Sek',
    'Sectionlength'        => 'Sektionslängd',
    'Select'               => 'Välj',
    'SelectFormat'         => 'Select Format',          // Added - 2011-06-17
    'SelectLog'            => 'Select Log',             // Added - 2011-06-17
    'SelectMonitors'       => 'Välj bevakare',
    'SelfIntersecting'     => 'Polygonändarna får inte överlappa',
    'Set'                  => 'Ställ in',
    'SetNewBandwidth'      => 'Ställ in ny bandbredd',
    'SetPreset'            => 'Ställ in förinställning',
    'Settings'             => 'Inställningar',
    'ShowFilterWindow'     => 'Visa fönsterfilter',
    'ShowTimeline'         => 'Visa tidslinje',
    'SignalCheckColour'    => 'Signal Check Colour',
    'SignalCheckPoints'    => 'Signal Check Points',    // Added - 2018-08-30
    'Size'                 => 'Storlek',
    'SkinDescription'      => 'Change the default skin for this computer', // Added - 2011-01-30
    'Sleep'                => 'Vila',
    'SortAsc'              => 'Stigande',
    'SortBy'               => 'Sortera',
    'SortDesc'             => 'Fallande',
    'Source'               => 'Källa',
    'SourceColours'        => 'Source Colours',         // Added - 2009-02-08
    'SourcePath'           => 'Source Path',            // Added - 2009-02-08
    'SourceType'           => 'Källtyp',
    'Speed'                => 'Hastighet',
    'SpeedHigh'            => 'Höghastighet',
    'SpeedLow'             => 'Låghastighet',
    'SpeedMedium'          => 'Normalhastighet',
    'SpeedTurbo'           => 'Turbohastighet',
    'Start'                => 'Start',
    'State'                => 'Läge',
    'Stats'                => 'Statistik',
    'Status'               => 'Status',
    'StatusConnected'      => 'Capturing',              // Added - 2018-08-30
    'StatusNotRunning'     => 'Not Running',            // Added - 2018-08-30
    'StatusRunning'        => 'Not Capturing',          // Added - 2018-08-30
    'StatusUnknown'        => 'Unknown',                // Added - 2018-08-30
    'Step'                 => 'Steg',
    'StepBack'             => 'Stepga bakåt',
    'StepForward'          => 'Stega framåt',
    'StepLarge'            => 'Stora steg',
    'StepMedium'           => 'Normalsteg',
    'StepNone'             => 'Inga steg',
    'StepSmall'            => 'Små steg',
    'Stills'               => 'Stillbilder',
    'Stop'                 => 'Stopp',
    'Stopped'              => 'Stoppad',
    'StorageArea'          => 'Storage Area',           // Added - 2018-08-30
    'StorageScheme'        => 'Scheme',                 // Added - 2018-08-30
    'Stream'               => 'Strömmande',
    'StreamReplayBuffer'   => 'Buffert för strömmande uppspelning',
    'Submit'               => 'Skicka',
    'System'               => 'System',
    'SystemLog'            => 'System Log',             // Added - 2011-06-16
    'TargetColorspace'     => 'Target colorspace',      // Added - 2015-04-18
    'Tele'                 => 'Tele',
    'Thumbnail'            => 'Miniatyrer',
    'Tilt'                 => 'Tilt',
    'Time'                 => 'Tid',
    'TimeDelta'            => 'tidsdelta',
    'TimeStamp'            => 'Tidsstämpel',
    'Timeline'             => 'Tidslinje',
    'TimelineTip1'          => 'Pass your mouse over the graph to view a snapshot image and event details.',              // Added 2013.08.15.
    'TimelineTip2'          => 'Click on the coloured sections of the graph, or the image, to view the event.',              // Added 2013.08.15.
    'TimelineTip3'          => 'Click on the background to zoom in to a smaller time period based around your click.',              // Added 2013.08.15.
    'TimelineTip4'          => 'Use the controls below to zoom out or navigate back and forward through the time range.',              // Added 2013.08.15.
    'Timestamp'            => 'Tidsstämpel',
    'TimestampLabelFormat' => 'Format på tidsstämpel',
    'TimestampLabelSize'   => 'Font Size',              // Added - 2018-08-30
    'TimestampLabelX'      => 'Värde på tidsstämpel X',
    'TimestampLabelY'      => 'Värde på tidsstämpel Y',
    'Today'                => 'Idag',
    'Tools'                => 'Verktyg',
    'Total'                => 'Total',                  // Added - 2011-06-16
    'TotalBrScore'         => 'Total<br/>Score',
    'TrackDelay'           => 'Spårfördröjning',
    'TrackMotion'          => 'Spåra rörelse',
    'Triggers'             => 'Triggers',
    'TurboPanSpeed'        => 'Turbo panoramahastighet',
    'TurboTiltSpeed'       => 'Turbo tilthastighet',
    'Type'                 => 'Typ',
    'Unarchive'            => 'Packa upp',
    'Undefined'            => 'Undefined',              // Added - 2009-02-08
    'Units'                => 'Enheter',
    'Unknown'              => 'Okänd',
    'Update'               => 'Uppdatera',
    'UpdateAvailable'      => 'En uppdatering till ZoneMinder finns tillgänglig.',
    'UpdateNotNecessary'   => 'Ingen uppdatering behövs.',
    'Updated'              => 'Updated',                // Added - 2011-06-16
    'Upload'               => 'Upload',                 // Added - 2011-08-23
    'UseFilter'            => 'Använd filter',
    'UseFilterExprsPost'   => '&nbsp;filter&nbsp;expressions', // This is used at the end of the phrase 'use N filter expressions'
    'UseFilterExprsPre'    => 'Använd&nbsp;', // This is used at the beginning of the phrase 'use N filter expressions'
    'UsedPlugins'	   => 'Used Plugins',
    'User'                 => 'Användare',
    'Username'             => 'Användarnamn',
    'Users'                => 'Användare',
    'V4L'                  => 'V4L',                    // Added - 2015-04-18
    'V4LCapturesPerFrame'  => 'Captures Per Frame',     // Added - 2015-04-18
    'V4LMultiBuffer'       => 'Multi Buffering',        // Added - 2015-04-18
    'Value'                => 'Värde',
    'Version'              => 'Version',
    'VersionIgnore'        => 'Ignorera denna version',
    'VersionRemindDay'     => 'Påminn om 1 dag',
    'VersionRemindHour'    => 'Påminn om 1 timme',
    'VersionRemindNever'   => 'Påminn inte om nya versioner',
    'VersionRemindWeek'    => 'Påminn om en 1 vecka',
    'Video'                => 'Video',
    'VideoFormat'          => 'Videoformat',
    'VideoGenFailed'       => 'Videogenereringen misslyckades!',
    'VideoGenFiles'        => 'Befintliga videofiler',
    'VideoGenNoFiles'      => 'Inga videofiler',
    'VideoGenParms'        => 'Inställningar för videogenerering',
    'VideoGenSucceeded'    => 'Videogenereringen lyckades!',
    'VideoSize'            => 'Videostorlek',
    'VideoWriter'          => 'Video Writer',           // Added - 2018-08-30
    'View'                 => 'Visa',
    'ViewAll'              => 'Visa alla',
    'ViewEvent'            => 'Visa händelse',
    'ViewPaged'            => 'Visa Paged',
    'Wake'                 => 'Vakna',
    'WarmupFrames'         => 'Värm upp ramar',
    'Watch'                => 'Se',
    'Web'                  => 'Webb',
    'WebColour'            => 'Webbfärg',
    'WebSiteUrl'           => 'Website URL',            // Added - 2018-08-30
    'Week'                 => 'Vecka',
    'White'                => 'Vit',
    'WhiteBalance'         => 'Vitbalans',
    'Wide'                 => 'Vid',
    'X'                    => 'X',
    'X10'                  => 'X10',
    'X10ActivationString'  => 'X10 aktiveringssträng',
    'X10InputAlarmString'  => 'X10 larmingångssträng',
    'X10OutputAlarmString' => 'X10 larmutgångssträng',
    'Y'                    => 'J',
    'Yes'                  => 'Ja',
    'YouNoPerms'           => 'Du har inte tillstånd till denna resurs.',
    'Zone'                 => 'Zon',
    'ZoneAlarmColour'      => 'Larmfärg (Röd/Grön/Blå)',
    'ZoneArea'             => 'Zonarea',
    'ZoneExtendAlarmFrames' => 'Extend Alarm Frame Count',
    'ZoneFilterSize'       => 'Filterbredd/höjd (pixlar)',
    'ZoneMinMaxAlarmArea'  => 'Min/Max larmarea',
    'ZoneMinMaxBlobArea'   => 'Min/Max blobbarea',
    'ZoneMinMaxBlobs'      => 'Min/Max blobbar',
    'ZoneMinMaxFiltArea'   => 'Min/Max filterarea',
    'ZoneMinMaxPixelThres' => 'Min/Max pixel Threshold (0-255)',
    'ZoneMinderLog'        => 'ZoneMinder Log',         // Added - 2011-06-17
    'ZoneOverloadFrames'   => 'Overload Frame Ignore Count',
    'Zones'                => 'Zoner',
    'Zoom'                 => 'Zoom',
    'ZoomIn'               => 'Zooma in',
    'ZoomOut'              => 'Zooma ut',
);

// Complex replacements with formatting and/or placements, must be passed through sprintf
$CLANG = array(
    'CurrentLogin'         => 'Aktuell inloggning är \'%1$s\'',
    'EventCount'           => '%1$s %2$s', // For example '37 Events' (from Vlang below)
    'LastEvents'           => 'Senaste %1$s %2$s', // For example 'Last 37 Events' (from Vlang below)
    'LatestRelease'        => 'Aktuell version är v%1$s, du har v%2$s.',
    'MonitorCount'         => '%1$s %2$s', // For example '4 Monitors' (from Vlang below)
    'MonitorFunction'      => 'Bevakare %1$s funktion',
    'RunningRecentVer'     => 'Du använder den senaste versionen av ZoneMinder, v%s.',
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
    'Event'                => array( 0=>'Händelser', 1=>'Händelsen', 2=>'Händelserna' ),
    'Monitor'              => array( 0=>'Bevakare', 1=>'Bevakare', 2=>'Bevakare' ),
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
    die( 'Fel, kan inte relatera variabel språksträng' );
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
$OLANG = array(
    'LANG_DEFAULT' => array(
        'Prompt' => "Välj språk för ZoneMinder",
        'Help' => "ZoneMinder kan använda annat språk än engelska i menyer och texter. Välj här det språk du vill använda till ZoneMinder."
    ),
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
	
);

?>
