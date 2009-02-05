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
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
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
    '8BitGrey'             => '8 bit gråskala',
    'Action'               => 'Action',
    'Actual'               => 'Verklig',
    'AddNewControl'        => 'Ny kontroll',
    'AddNewMonitor'        => 'Ny bevakare',
    'AddNewUser'           => 'Ny användare',
    'AddNewZone'           => 'Ny zon',
    'AlarmBrFrames'        => 'Larm<br/>ramar',
    'AlarmFrameCount'      => 'Larmramsräknare',
    'AlarmFrame'           => 'Larmram',
    'Alarm'                => 'Larm',
    'AlarmLimits'          => 'Larmgränser',
    'AlarmMaximumFPS'      => 'Max. ramar/s för larm',
    'AlarmPx'              => 'Larmpunkter',
    'AlarmRGBUnset'        => 'Du måste sätta en färg för RGB-larm',
    'Alert'                => 'Varning',
    'All'                  => 'Alla',
    'ApplyingStateChange'  => 'Aktivera statusändring',
    'Apply'                => 'Lägg till',
    'ArchArchived'         => 'Arkivera endast',
    'Archive'              => 'Arkiv',
    'Archived'             => 'Arkiverad',
    'ArchUnarchived'       => 'Endast ej arkiverade',
    'Area'                 => 'Område',
    'AreaUnits'            => 'Område (px/%)',
    'AttrAlarmFrames'      => 'Larmramar',
    'AttrArchiveStatus'    => 'Arkivstatus',
    'AttrAvgScore'         => 'Ung. värde',
    'AttrCause'            => 'Orsak',
    'AttrDate'             => 'Datum',
    'AttrDateTime'         => 'Datum/Tid',
    'AttrDiskBlocks'       => 'Diskblock',
    'AttrDiskPercent'      => 'Diskprocent',
    'AttrDuration'         => 'Längd',
    'AttrFrames'           => 'Ramar',
    'AttrId'               => 'Id',
    'AttrMaxScore'         => 'Max. värde',
    'AttrMonitorId'        => 'Bevakningsid',
    'AttrMonitorName'      => 'Bevakningsnamn',
    'AttrName'             => 'Namn',
    'AttrNotes'            => 'Notering',
    'AttrSystemLoad'       => 'Systemlast',
    'AttrTime'             => 'Tid',
    'AttrTotalScore'       => 'Totalvärde',
    'AttrWeekday'          => 'Veckodag',
    'Auto'                 => 'Automatik',
    'AutoStopTimeout'      => 'Tidsutlösning för automatstop',
    'AvgBrScore'           => 'Ung.<br/>träff',
    'Background'           => 'Bakgrund',
    'BackgroundFilter'     => 'Kör filter i bakgrunden',
    'BadAlarmFrameCount'   => 'Ramantalet för larm måste vara ett heltal, minsta värdet är 1',
    'BadAlarmMaxFPS'       => 'Larm för bilder/s måste vara ett positivt heltal eller ett flyttal',
    'BadChannel'           => 'Kanalen måste vara ett heltal, noll eller högre',
    'BadDevice'            => 'Enheten måste sättas till ett giltigt värde',
    'BadFormat'            => 'Formatet måste vara ett heltal, noll eller högre',
    'BadFPSReportInterval' => 'Buffern för ramintervallrapporten måste vara ett heltal på minst 100 eller högre',
    'BadFrameSkip'         => 'Värdet för ramöverhopp måste vara ett heltal på 0 eller högre',
    'BadHeight'            => 'Höjden måste sättas till ett giltigt värde',
    'BadHost'              => 'Detta fält ska innehålla en giltig ip-adress eller värdnamn, inkludera inte http://',
    'BadImageBufferCount'  => 'Bufferstorleken för avbilden måste vara ett heltal på minst 10 eller högre',
    'BadLabelX'            => 'Etiketten för X koordinaten måste sättas till ett heltal, 0 eller högre',
    'BadLabelY'            => 'Etiketten för Y koordinaten måste sättas till ett heltal, 0 eller högre',
    'BadMaxFPS'            => 'Max. ramar/s måste vara ett positivt heltal eller ett flyttal',
    'BadNameChars'         => 'Namn kan endast innehålla alfanumeriska tecken, bindestreck och understreck',
    'BadPath'              => 'Sökvägen måste innehålla ett giltigt värde',
    'BadPort'              => 'Porten måste innehålla ett giltigt nummer',
    'BadPostEventCount'    => 'Räknaren för efterhändelsen måste vara ett heltal på 0 eller högre',
    'BadPreEventCount'     => 'Räknaren för för-händelsen måste vara ett heltal på 0 eller högre, och mindre än bufferstorleken på avbilden',
    'BadRefBlendPerc'      => 'Mixprocenten för referensen måste hara ett positivt heltal',
    'BadSectionLength'     => 'Sektionslängden måste vara ett heltal på minst 30 eller högre',
    'BadSignalCheckColour' => 'Kontrollfärgen på signalen måste vara en giltig RGB färgsträng',
    'BadStreamReplayBuffer'=> 'Buffern för strömmande uppspelning måste vara ett heltal på 0 eller högre',
    'BadWarmupCount'       => 'Uppvärmingsramen måste vara ett heltal på 0 eller högre',
    'BadWebColour'         => 'Webbfärgen måste vara en giltig sträng för webbfärg',
    'BadWidth'             => 'Bredden måste sättas til ett giltigt värde',
    'Bandwidth'            => 'Bandbredd',
    'BlobPx'               => 'Blob Px',
    'Blobs'                => 'Blobbar',
    'BlobSizes'            => 'Blobstorlek',
    'Brightness'           => 'Ljusstyrka',
    'Buffers'              => 'Buffrar',
    'CanAutoFocus'         => 'Har autofokus',
    'CanAutoGain'          => 'Har autonivå',
    'CanAutoIris'          => 'Har autoiris',
    'CanAutoWhite'         => 'Har autovitbalans.',
    'CanAutoZoom'          => 'Har autozoom',
    'CancelForcedAlarm'    => 'Ångra tvingande larm',
    'Cancel'               => 'Ångra',
    'CanFocusAbs'          => 'Har absolut fokus',
    'CanFocusCon'          => 'Har kontinuerlig fokus',
    'CanFocus'             => 'Har fokus',
    'CanFocusRel'          => 'Har relativ fokus',
    'CanGainAbs'           => 'Har absolut nivå',
    'CanGainCon'           => 'Har kontinuerlig nivå',
    'CanGain'              => 'Har nivå',
    'CanGainRel'           => 'Har relativ nivå',
    'CanIrisAbs'           => 'Har absolut iris',
    'CanIrisCon'           => 'Har kontinuerlig iris',
    'CanIris'              => 'Har iris',
    'CanIrisRel'           => 'Har relativ iris',
    'CanMoveAbs'           => 'Har absolut förflyttning',
    'CanMoveCon'           => 'Har kontinuerlig förflyttning',
    'CanMoveDiag'          => 'Har diagonal förflyttning',
    'CanMove'              => 'Har förflyttning',
    'CanMoveMap'           => 'Har mappad förflyttning',
    'CanMoveRel'           => 'Har relativ förflyttning',
    'CanPan'               => 'Har panorering',
    'CanReset'             => 'Har återställning',
    'CanSetPresets'        => 'Har förinställningar',
    'CanSleep'             => 'Kan vila',
    'CanTilt'              => 'Kan tilta',
    'CanWake'              => 'Kan vakna',
    'CanWhiteAbs'          => 'Har absolut vitbalans',
    'CanWhiteBal'          => 'Kan vitbalans',
    'CanWhiteCon'          => 'Kan kontinuerligt vitbalansera',
    'CanWhite'             => 'Kan vitbalansera',
    'CanWhiteRel'          => 'Kan relativt vitbalansera',
    'CanZoomAbs'           => 'Kan zooma absolut',
    'CanZoomCon'           => 'Kan zooma kontinuerligt',
    'CanZoom'              => 'Kan zooma',
    'CanZoomRel'           => 'Kan zooma realativt',
    'CaptureHeight'        => 'Fångsthöjd',
    'CapturePalette'       => 'Fångstpalett',
    'CaptureWidth'         => 'Fångstbredd',
    'Cause'                => 'Orsak',
    'CheckMethod'          => 'Larmkontrollmetod',
    'ChooseFilter'         => 'Välj filter',
    'ChoosePreset'         => 'Välj standard',
    'Close'                => 'Stäng',
    'Colour'               => 'Färg',
    'Command'              => 'Kommando',
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
    'ControlAddress'       => 'Kontrolladress',
    'ControlCap'           => 'Kontrollförmåga',
    'ControlCaps'          => 'Kontrollförmågor',
    'ControlDevice'        => 'Kontrollenhet',
    'Control'              => 'Kontroll',
    'Controllable'         => 'Kontrollerbar',
    'ControlType'          => 'Kontrolltyp',
    'Cycle'                => 'Period',
    'CycleWatch'           => 'Cycle Watch',
    'Day'                  => 'Dag',
    'Debug'                => 'Avlusa',
    'DefaultRate'          => 'Standardhastighet',
    'DefaultScale'         => 'Standardskala',
    'DefaultView'          => 'Standardvy',
    'DeleteAndNext'        => 'Radera &amp; Nästa',
    'DeleteAndPrev'        => 'Radera &amp; Föreg.',
    'Delete'               => 'Radera',
    'DeleteSavedFilter'    => 'Radera sparade filter',
    'Description'          => 'Beskrivning',
    'DeviceChannel'        => 'Enhetskanal',
    'DeviceFormat'         => 'Enhetsformat',
    'DeviceNumber'         => 'Enhetsnummer',
    'DevicePath'           => 'Enhetssökväg',
    'Devices'              => 'Enheter',
    'Dimensions'           => 'Dimensioner',
    'DisableAlarms'        => 'Avaktivera larm',
    'Disk'                 => 'Disk',
    'DonateAlready'        => 'Nej, Jag har redan donerat',
    'DonateEnticement'     => 'Du har kört ZoneMinder ett tag nu och förhoppningsvis har du sett att det fungerar bra hemma eller på ditt företag. Även om ZoneMinder är, och kommer att vara, fri programvara och öppen kallkod, så kostar det pengar att utveckla och underhålla. Om du vill hjälpa till med framtida utveckling och nya funktioner så var vanlig och bidrag med en slant. Bidragen är naturligtvis en option men mycket uppskattade och du kan bidra med precis hur mycket du vill.<br><br>Om du vill ge ett bidrag väljer du nedan eller surfar till http://www.zoneminder.com/donate.html.<br><br>Tack för att du använder ZoneMinder, glöm inte att besöka forumen på ZoneMinder.com för support och förslag om hur du får din ZoneMinder att fungera lite bättre.',
    'DonateRemindDay'      => 'Inte än, påminn om 1 dag',
    'DonateRemindHour'     => 'Inte än, påminn om en 1 timme',
    'DonateRemindMonth'    => 'Inte än, påminn om 1 månad',
    'DonateRemindNever'    => 'Nej, Jag vill inte donera, påminn mig inte mer',
    'DonateRemindWeek'     => 'Inte än, påminn om 1 vecka',
    'Donate'               => 'Var vänlig och donera',
    'DonateYes'            => 'Ja, jag vill gärna donera nu',
    'Download'             => 'Ladda ner',
    'Duration'             => 'Längd',
    'Edit'                 => 'Redigera',
    'Email'                => 'E-post',
    'EnableAlarms'         => 'Aktivera larm',
    'Enabled'              => 'Aktiverad',
    'EnterNewFilterName'   => 'Mata in nytt filternamn',
    'ErrorBrackets'        => 'Fel, kontrollera att du har samma antal vänster som höger-hakar',
    'Error'                => 'Fel',
    'ErrorValidValue'      => 'Fel, kontrollera att alla parametrar har giltligt värde',
    'Etc'                  => 'etc',
    'EventFilter'          => 'Händelsefilter',
    'Event'                => 'Händelse',
    'EventId'              => 'Händelse nr',
    'EventName'            => 'Händelsenamn',
    'EventPrefix'          => 'Händelseprefix',
    'Events'               => 'Händelser',
    'Exclude'              => 'Exkludera',
    'Execute'              => 'Utför',
    'ExportDetails'        => 'Exportera händelsedetaljer',
    'Export'               => 'Exportera',
    'ExportFailed'         => 'Exporten misslyckades',
    'ExportFormat'         => 'Filformat för exporter',
    'ExportFormatTar'      => 'Tar',
    'ExportFormatZip'      => 'Zip',
    'ExportFrames'         => 'Exportera ramdetaljer',
    'ExportImageFiles'     => 'Exportera bildfiler',
    'Exporting'            => 'Exporterar',
    'ExportMiscFiles'      => 'Exportera andra filer (om dom finns)',
    'ExportOptions'        => 'Konfiguera export',
    'ExportVideoFiles'     => 'Exportera videofiler (om dom finns)',
    'Far'                  => 'Far',
    'FastForward'          => 'Fast Forward',
    'Feed'                 => 'Matning',
    'FileColours'          => 'Filfärg',
    'File'                 => 'Fil',
    'FilePath'             => 'Sökvag',
    'FilterArchiveEvents'  => 'Arkivera alla träffar',
    'FilterDeleteEvents'   => 'Radera alla träffar',
    'FilterEmailEvents'    => 'Skicka e-post med detaljer om alla träffar',
    'FilterExecuteEvents'  => 'Utför kommando på alla träffar',
    'FilterMessageEvents'  => 'Meddela detaljer om alla träffar',
    'FilterPx'             => 'Filter Px',
    'Filters'              => 'Filter',
    'FilterUnset'          => 'Du måste specificera filtrets bredd och höjd',
    'FilterUploadEvents'   => 'Ladda upp alla träffar',
    'FilterVideoEvents'    => 'Skapa video för alla träffar',
    'First'                => 'Först',
    'FlippedHori'          => 'Vänd horisontellt',
    'FlippedVert'          => 'Vänd vertikalt',
    'Focus'                => 'Fokus',
    'ForceAlarm'           => 'Tvinga larm',
    'Format'               => 'Format',
    'FPS'                  => 'fps',
    'FPSReportInterval'    => 'FPS rapportintervall',
    'FrameId'              => 'Ram id',
    'Frame'                => 'Ram',
    'FrameRate'            => 'Ram hastighet',
    'FrameSkip'            => 'Hoppa över ram',
    'Frames'               => 'Ramar',
    'FTP'                  => 'FTP',
    'Func'                 => 'Funk',
    'Function'             => 'Funktion',
    'Gain'                 => 'Nivå',
    'General'              => 'Generell',
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
    'HighBW'               => 'Hög bandbredd',
    'High'                 => 'Hög',
    'Home'                 => 'Hem',
    'Hour'                 => 'Timme',
    'Hue'                  => 'Hue',
    'Idle'                 => 'Vila',
    'Id'                   => 'nr',
    'Ignore'               => 'Ignorera',
    'Image'                => 'Bild',
    'ImageBufferSize'      => 'Bildbufferstorlek (ramar)',
    'Images'               => 'Images',
    'Include'              => 'Inkludera',
    'In'                   => 'I',
    'Inverted'             => 'Inverterad',
    'Iris'                 => 'Iris',
    'KeyString'            => 'Nyckelsträng',
    'Label'                => 'Etikett',
    'Language'             => 'Språk',
    'Last'                 => 'Sist',
    'LimitResultsPost'     => 'resultaten;', // This is used at the end of the phrase 'Limit to first N results only'
    'LimitResultsPre'      => 'Begränsa till första', // This is used at the beginning of the phrase 'Limit to first N results only'
    'LinkedMonitors'       => 'Länkade övervakare',
    'List'                 => 'Lista',
    'Load'                 => 'Belastning',
    'Local'                => 'Lokal',
    'LoggedInAs'           => 'Inloggad som',
    'LoggingIn'            => 'Loggar in',
    'Login'                => 'Logga in',
    'Logout'               => 'Logga ut',
    'LowBW'                => 'Låg bandbredd',
    'Low'                  => 'Låg',
    'Main'                 => 'Huvudmeny',
    'Man'                  => 'Man',
    'Manual'               => 'Manuell',
    'Mark'                 => 'Markera',
    'MaxBandwidth'         => 'Max bandbredd',
    'MaxBrScore'           => 'Max.<br/>Score',
    'MaxFocusRange'        => 'Max fokusområde',
    'MaxFocusSpeed'        => 'Max fokushastighet',
    'MaxFocusStep'         => 'Max fokussteg',
    'MaxGainRange'         => 'Max nivåområde',
    'MaxGainSpeed'         => 'Max nivåhastighet',
    'MaxGainStep'          => 'Max nivåsteg',
    'MaximumFPS'           => 'Max ramar/s',
    'MaxIrisRange'         => 'Max irsiområde',
    'MaxIrisSpeed'         => 'Max irishastighet',
    'MaxIrisStep'          => 'Max irissteg',
    'Max'                  => 'Max',
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
    'MediumBW'             => 'Mellan bandbredd',
    'Medium'               => 'Mellan',
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
    'Monitor'              => 'Bevakning',
    'MonitorIds'           => 'Bevakningsnr',
    'MonitorPreset'        => 'Förinställd bevakning',
    'MonitorPresetIntro'   => 'Välj en förinställning från listan.<br><br>Var medveten om att detta kan skriva över inställningar du redan gjort för denna bevakare.<br><br>',
    'Monitors'             => 'Bevakare',
    'Montage'              => 'Montera',
    'Month'                => 'Månad',
    'Move'                 => 'Flytta',
    'MustBeGe'             => 'måste vara större än eller lika med',
    'MustBeLe'             => 'måste vara mindre än eller lika med',
    'MustConfirmPassword'  => 'Du måste bekräfta lösenordet',
    'MustSupplyPassword'   => 'Du måste ange ett lösenord',
    'MustSupplyUsername'   => 'Du måste ange ett användarnamn',
    'Name'                 => 'Namn',
    'Near'                 => 'Nära',
    'Network'              => 'Nätverk',
    'NewGroup'             => 'Ny grupp',
    'NewLabel'             => 'Ny etikett',
    'New'                  => 'Ny',
    'NewPassword'          => 'Nytt lösenord',
    'NewState'             => 'Nytt läge',
    'NewUser'              => 'Ny användare',
    'Next'                 => 'Nästa',
    'NoFramesRecorded'     => 'Det finns inga ramar inspelade för denna händelse',
    'NoGroup'              => 'Ingen grupp',
    'NoneAvailable'        => 'Ingen tillgänglig',
    'None'                 => 'Ingen',
    'No'                   => 'Nej',
    'Normal'               => 'Normal',
    'NoSavedFilters'       => 'Inga sparade filter',
    'NoStatisticsRecorded' => 'Det finns ingen statistik inspelad för denna händelse/ram',
    'Notes'                => 'Not.',
    'NumPresets'           => 'Antal förinställningar',
    'Off'                  => 'Av',
    'On'                   => 'På',
    'Open'                 => 'Öppna',
    'OpEq'                 => 'lika med',
    'OpGtEq'               => 'större än eller lika med',
    'OpGt'                 => 'större än',
    'OpIn'                 => 'in set',
    'OpLtEq'               => 'mindre än eller lika med',
    'OpLt'                 => 'mindre än',
    'OpMatches'            => 'matchar',
    'OpNe'                 => 'inte lika med',
    'OpNotIn'              => 'inte i set',
    'OpNotMatches'         => 'matchar inte',
    'OptionHelp'           => 'Optionhjälp',
    'OptionRestartWarning' => 'Dessa ändringar kommer inte att vara implementerade\nnär systemet körs. När du är klar starta om\n ZoneMinder.',
    'Options'              => 'Alternativ',
    'Order'                => 'Sortera',
    'OrEnterNewName'       => 'eller skriv in nytt namn',
    'Orientation'          => 'Orientation',
    'Out'                  => 'Ut',
    'OverwriteExisting'    => 'Skriv över',
    'Paged'                => 'Paged',
    'PanLeft'              => 'Panorera vänster',
    'Pan'                  => 'Panorera',
    'PanRight'             => 'Panorera höger',
    'PanTilt'              => 'Pan/Tilt',
    'Parameter'            => 'Parameter',
    'Password'             => 'Lösenord',
    'PasswordsDifferent'   => 'Lösenorden skiljer sig åt',
    'Paths'                => 'Sökvägar',
    'Pause'                => 'Paus',
    'PhoneBW'              => 'Mobil bandbredd',
    'Phone'                => 'Mobil',
    'PixelDiff'            => 'Skillnad i bildpunkter',
    'Pixels'               => 'bildpunkter',
    'PlayAll'              => 'Visa alla',
    'Play'                 => 'Spela',
    'PleaseWait'           => 'Vänta...',
    'Point'                => 'Punkt',
    'PostEventImageBuffer' => 'Post Event Image Count',
    'PreEventImageBuffer'  => 'Pre Event Image Count',
    'PreserveAspect'       => 'Bevara lägesförhållande',
    'Preset'               => 'Förinställning',
    'Presets'              => 'Förinställningar',
    'Prev'                 => 'Föreg.',
    'Protocol'             => 'Protokol',
    'Rate'                 => 'Hastighet',
    'Real'                 => 'Verklig',
    'Record'               => 'Spela in',
    'RefImageBlendPct'     => 'Reference Image Blend %ge',
    'Refresh'              => 'Uppdatera',
    'Remote'               => 'Fjärr',
    'RemoteHostName'       => 'Fjärrnamn',
    'RemoteHostPath'       => 'Fjärrsökväg',
    'RemoteHostPort'       => 'Fjärrport',
    'RemoteImageColours'   => 'Fjärrbildfärger',
    'Rename'               => 'Byt namn',
    'ReplayAll'            => 'Alla händelser',
    'ReplayGapless'        => 'Gapless Events',
    'Replay'               => 'Repris',
    'ReplaySingle'         => 'Ensam händelse',
    'ResetEventCounts'     => 'Återställ händelseräknare',
    'Reset'                => 'Återställ',
    'Restarting'           => 'Återstartar',
    'Restart'              => 'Återstart',
    'RestrictedCameraIds'  => 'Begränsade kameranr.',
    'RestrictedMonitors'   => 'Begränsade bevakare',
    'ReturnDelay'          => 'Fördröjd retur',
    'ReturnLocation'       => 'Återvänd till position',
    'Rewind'               => 'Backa',
    'RotateLeft'           => 'Rotera vänster',
    'RotateRight'          => 'Rotera höger',
    'RunMode'              => 'Körläge',
    'Running'              => 'Körs',
    'RunState'             => 'Körläge',
    'SaveAs'               => 'Spara som',
    'SaveFilter'           => 'Spara filter',
    'Save'                 => 'Spara',
    'Scale'                => 'Skala',
    'Score'                => 'Resultat',
    'Secs'                 => 'Sek',
    'Sectionlength'        => 'Sektionslängd',
    'SelectMonitors'       => 'Välj bevakare',
    'Select'               => 'Välj',
    'SelfIntersecting'     => 'Polygonändarna får inte överlappa',
    'SetNewBandwidth'      => 'Ställ in ny bandbredd',
    'SetPreset'            => 'Ställ in förinställning',
    'Set'                  => 'Ställ in',
    'Settings'             => 'Inställningar',
    'ShowFilterWindow'     => 'Visa fönsterfilter',
    'ShowTimeline'         => 'Visa tidslinje',
    'SignalCheckColour'    => 'Signal Check Colour',
    'Size'                 => 'Storlek',
    'Sleep'                => 'Vila',
    'SortAsc'              => 'Stigande',
    'SortBy'               => 'Sortera',
    'SortDesc'             => 'Fallande',
    'Source'               => 'Källa',
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
    'StepBack'             => 'Stepga bakåt',
    'StepForward'          => 'Stega framåt',
    'StepLarge'            => 'Stora steg',
    'StepMedium'           => 'Normalsteg',
    'StepNone'             => 'Inga steg',
    'StepSmall'            => 'Små steg',
    'Step'                 => 'Steg',
    'Stills'               => 'Stillbilder',
    'Stopped'              => 'Stoppad',
    'Stop'                 => 'Stopp',
    'StreamReplayBuffer'   => 'Buffert för strömmande uppspelning',
    'Stream'               => 'Strömmande',
    'Submit'               => 'Skicka',
    'System'               => 'System',
    'Tele'                 => 'Tele',
    'Thumbnail'            => 'Miniatyrer',
    'Tilt'                 => 'Tilt',
    'TimeDelta'            => 'tidsdelta',
    'Timeline'             => 'Tidslinje',
    'TimestampLabelFormat' => 'Format på tidsstämpel',
    'TimestampLabelX'      => 'Värde på tidsstämpel X',
    'TimestampLabelY'      => 'Värde på tidsstämpel Y',
    'Timestamp'            => 'Tidsstämpel',
    'TimeStamp'            => 'Tidsstämpel',
    'Time'                 => 'Tid',
    'Today'                => 'Idag',
    'Tools'                => 'Verktyg',
    'TotalBrScore'         => 'Total<br/>Score',
    'TrackDelay'           => 'Spårfördröjning',
    'TrackMotion'          => 'Spåra rörelse',
    'Triggers'             => 'Triggers',
    'TurboPanSpeed'        => 'Turbo panoramahastighet',
    'TurboTiltSpeed'       => 'Turbo tilthastighet',
    'Type'                 => 'Typ',
    'Unarchive'            => 'Packa upp',
    'Units'                => 'Enheter',
    'Unknown'              => 'Okänd',
    'UpdateAvailable'      => 'En uppdatering till ZoneMinder finns tillgänglig.',
    'UpdateNotNecessary'   => 'Ingen uppdatering behövs.',
    'Update'               => 'Uppdatera',
    'UseFilter'            => 'Använd filter',
    'UseFilterExprsPost'   => '&nbsp;filter&nbsp;expressions', // This is used at the end of the phrase 'use N filter expressions'
    'UseFilterExprsPre'    => 'Använd&nbsp;', // This is used at the beginning of the phrase 'use N filter expressions'
    'User'                 => 'Användare',
    'Username'             => 'Användarnamn',
    'Users'                => 'Användare',
    'Value'                => 'Värde',
    'VersionIgnore'        => 'Ignorera denna version',
    'VersionRemindDay'     => 'Påminn om 1 dag',
    'VersionRemindHour'    => 'Påminn om 1 timme',
    'VersionRemindNever'   => 'Påminn inte om nya versioner',
    'VersionRemindWeek'    => 'Påminn om en 1 vecka',
    'Version'              => 'Version',
    'VideoFormat'          => 'Videoformat',
    'VideoGenFailed'       => 'Videogenereringen misslyckades!',
    'VideoGenFiles'        => 'Befintliga videofiler',
    'VideoGenNoFiles'      => 'Inga videofiler',
    'VideoGenParms'        => 'Inställningar för videogenerering',
    'VideoGenSucceeded'    => 'Videogenereringen lyckades!',
    'VideoSize'            => 'Videostorlek',
    'Video'                => 'Video',
    'ViewAll'              => 'Visa alla',
    'ViewEvent'            => 'Visa händelse',
    'ViewPaged'            => 'Visa Paged',
    'View'                 => 'Visa',
    'Wake'                 => 'Vakna',
    'WarmupFrames'         => 'Värm upp ramar',
    'Watch'                => 'Se',
    'WebColour'            => 'Webbfärg',
    'Web'                  => 'Webb',
    'Week'                 => 'Vecka',
    'WhiteBalance'         => 'Vitbalans',
    'White'                => 'Vit',
    'Wide'                 => 'Vid',
    'X10ActivationString'  => 'X10 aktiveringssträng',
    'X10InputAlarmString'  => 'X10 larmingångssträng',
    'X10OutputAlarmString' => 'X10 larmutgångssträng',
    'X10'                  => 'X10',
    'X'                    => 'X',
    'Yes'                  => 'Ja',
    'Y'                    => 'J',
    'YouNoPerms'           => 'Du har inte tillstånd till denna resurs.',
    'ZoneAlarmColour'      => 'Larmfärg (Röd/Grön/Blå)',
    'ZoneArea'             => 'Zonarea',
    'ZoneFilterSize'       => 'Filterbredd/höjd (pixlar)',
    'ZoneMinMaxAlarmArea'  => 'Min/Max larmarea',
    'ZoneMinMaxBlobArea'   => 'Min/Max blobbarea',
    'ZoneMinMaxBlobs'      => 'Min/Max blobbar',
    'ZoneMinMaxFiltArea'   => 'Min/Max filterarea',
    'ZoneMinMaxPixelThres' => 'Min/Max pixel Threshold (0-255)',
    'ZoneOverloadFrames'   => 'Overload Frame Ignore Count',
    'Zones'                => 'Zoner',
    'Zone'                 => 'Zon',
    'ZoomIn'               => 'Zooma in',
    'ZoomOut'              => 'Zooma ut',
    'Zoom'                 => 'Zoom',
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
);

?>
