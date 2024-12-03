<?php
//
// ZoneMinder web Norwegian Bokmaal language file, $Date$, $Revision$
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

// ZoneMinder Norwegian Translation by Aleksander Korneliussen

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
header( "Content-Type: text/html; charset=utf-8" );

// Simple String Replacements
$SLANG = array(
    'SystemLog'             => 'Systemlogg',
    'DateTime'              => 'Dato/Tid',
    'Pid'                   => 'PID',
    '24BitColour'           => '24-bit farge',
    '32BitColour'           => '32-bit farge',
    '8BitGrey'              => '8-bit gråskala',
    'AddNewControl'         => 'Opprett ny kontroll',
    'AddNewMonitor'         => 'Legg til',
    'AddMonitorDisabled'    => 'Du har ikke rettigheter for å lage ny monitor',
    'AddNewServer'          => 'Legg til Server',
    'AddNewStorage'         => 'Legg til Lagring',
    'AddNewUser'            => 'Legg til ny Bruker',
    'AddNewZone'            => 'Legg til ny Sone',
    'AlarmBrFrames'         => 'Alarm<br/>Frames',
    'AlarmFrame'            => 'Alarm Frame',
    'AlarmFrameCount'       => 'Alarm Frame Antall',
    'AlarmLimits'           => 'Alarm Grenser',
    'AlarmMaximumFPS'       => 'Alarm Maksimum FPS',
    'AlarmPx'               => 'Alarm Px',
    'AlarmRefImageBlendPct' => 'Alarm referanse blender %ge',
    'AlarmRGBUnset'         => 'Du må sette en alarm RGB-farge',
    'AllTokensRevoked'      => 'Alle tokens opphevet',
    'AnalysisFPS'           => 'Analyse FPS',
    'AnalysisUpdateDelay'   => 'Analyse Oppdateringsforsinkelse',
    'APIEnabled'            => 'API Aktivert',
    'ApplyingStateChange'   => 'Bruker statusendringer',
    'ArchArchived'          => 'Kun Arkiverte',
    'ArchUnarchived'        => 'Kun Uarkiverte',
    'AreaUnits'             => 'Område (px/%)',
    'AttrAlarmFrames'       => 'Alarm Frames',
    'AttrAlarmedZone'       => 'Alarmert Sone',
    'AttrArchiveStatus'     => 'Arkiv Status',
    'AttrAvgScore'          => 'Gjs. Score',
    'AttrCause'             => 'Årsak',
    'AttrStartDate'         => 'Startdato',
    'AttrEndDate'           => 'Sluttdato',
    'AttrStartDateTime'     => 'Start Dato/Tid',
    'AttrEndDateTime'       => 'Slutt Dato/Tid',
    'AttrEventDiskSpace'    => 'Eventer Diskplass',
    'AttrDiskSpace'         => 'Filsystem Diskplass',
    'AttrDiskBlocks'        => 'Disk Blokker',
    'AttrDiskPercent'       => 'Disk Prosent',
    'AttrDuration'          => 'Lengde',
    'AttrFrames'            => 'Frames',
    'AttrId'                => 'Id',
    'AttrMaxScore'          => 'Maks. Score',
    'AttrMonitorId'         => 'Monitor Id',
    'AttrMonitorName'       => 'Monitor Navn',
    'AttrSecondaryStorageArea' => 'Sekundært lagringsområde',
    'AttrStorageArea'       => 'Lagringsområde',
    'AttrFilterServer'      => 'Server Filter kjører på',
    'AttrMonitorServer'     => 'Server Monitor kjører på',
    'AttrStorageServer'     => 'Server Hosting Lagring',
    'AttrStateId'           => 'Status',
    'AttrName'              => 'Navn',
    'AttrNotes'             => 'Merknader',
    'AttrSystemLoad'        => 'Systembelastning',
    'AttrStartTime'         => 'Starttid',
    'AttrEndTime'           => 'Sluttid',
    'AttrTotalScore'        => 'Total Score',
    'AttrStartWeekday'      => 'Start Ukedag',
    'AttrEndWeekday'        => 'Slutt ukedag',
    'AutoStopTimeout'       => 'Auto Stopp Tidsavbrudd',
    'AvgBrScore'            => 'Gjs.<br/>Score',
    'BackgroundFilter'      => 'Kjør filter i bakgrunnen',
    'BadAlarmFrameCount'    => 'Alarm frame antall må være et tall høyere enn én',
    'BadAlarmMaxFPS'        => 'Alarm Maks FPS må være et positivt tall eller desimaltall',
    'BadAnalysisFPS'        => 'Analyse FPS må være et positivt tall eller desimaltall',
    'BadAnalysisUpdateDelay'=> 'Analyse oppdateringsforsinkelse må settes til et tall som er 0 eller høyere',
    'BadChannel'            => 'Kanal må settes til et tall som er 0 eller høyere',
    'BadDevice'             => 'Enhet må settes til en gyldig verdi',
    'BadEncoderParameters'  => 'Encoder virker ikke optimalt uten en verdi for CRF. Se hjelp.',
    'BadFormat'             => 'Format må settes til en gyldig verdi',
    'BadFPSReportInterval'  => 'FPS rapportintervall bufferantall må være et tall som er 0 eller høyere',
    'BadFrameSkip'          => 'Rammehopp må være et tall som er 0 eller høyere',
    'BadMotionFrameSkip'    => 'Bevegelse rammehopp må være et tall som er 0 eller høyere',
    'BadHeight'             => 'Høyde må settes til en gyldig verdi',
    'BadHost'               => 'Host må settes til en gyldig IP-adresse eller hostnavn, ikke inkluder http://',
    'BadImageBufferCount'   => 'Bilde bufferstørrelse må settes til et tall som er 2 eller høyere',
    'BadLabelX'             => 'Etikett X koordinater må settes til et tall som er 0 eller høyere',
    'BadLabelY'             => 'Etikett Y koordinater må settes til et tall som er 0 eller høyere',
    'BadMaxFPS'             => 'Maks FPS må være et positivt tall eller desimaltall',
    'BadNameChars'          => 'Navn kan kun inneholde alfanumeriske tegn inkluder mellomrom, bindestrek og understrek',
    'BadPalette'            => 'Palett må settes til en gyldig verdi',
    'BadColours'            => 'Målfarge må settes til en gyldig verdi',
    'BadPassthrough'        => 'Opptak -> Passthrough fungerer bare med FFMPEG Monitorer.',
    'BadPath'               => 'Kilde -> Sti må settes til en gyldig verdi',
    'BadPathNotEncoded'     => 'Kilde -> Sti må settes til en gyldig verdi. Vi har oppdaget ugyldige tegn !*\'()$ ,#[] som må vøre URL-prosentkodet.',
    'BadPort'               => 'Kilde -> Port må settes til et gyldig tall',
    'BadPostEventCount'     => 'Etter-event bildeantall må være et tall som er 0 eller høyere',
    'BadPreEventCount'      => 'Før-event bildeantall må være minimum 0, og mindre enn bilde bufferstørrelse',
    'BadPreEventCountMaxImageBufferCount'      => 'Maks Bilde Bufferantall bør være større enn Før-event bildeantall for å tilfredsstilles',
    'BadRefBlendPerc'       => 'Referanse blenderprosent må være et positivt tall',
    'BadNoSaveJPEGsOrVideoWriter' => 'SaveJPEGs og VideoWriter er begge deaktivert. Ingenting vil bli tatt opp!',
    'BadSectionLength'      => 'Seksjonslengde må være et heltall som er 30 eller høyere',
    'BadSignalCheckColour'  => 'Signal sjekkfarge må være en gyldig RGB fargestreng',
    'BadStreamReplayBuffer' => 'Strøm avspillingsbuffer må være et tall som er 0 eller høyere',
    'BadSourceType'         => 'Kildetype \"Web Site\" krever at Funksjon er satt til \"Monitor\"',
    'BadWarmupCount'        => 'Oppvarmingsframes må være et tall som er 0 eller høyere',
    'BadWebColour'          => 'Webfarge må være en gyldig webfargestreng',
    'BadWebSitePath'        => 'Vennligst fyll ut en komplett nettsteds-URL, inkludert http:// eller https:// prefiks.',
    'BadWidth'              => 'Bredde må settes til en gyldig verdi',
    'BandwidthHead'         => 'Båndbredde',	// This is the end of the bandwidth status on the top of the console, different in many language due to phrasing
    'BlobPx'                => 'Blob Px',
    'BlobSizes'             => 'Blob Størrelser',
    'CanAutoFocus'          => 'Kan Auto Fokus',
    'CanAutoGain'           => 'Kan Autoøke',
    'CanAutoIris'           => 'Kan Auto Iris',
    'CanAutoWhite'          => 'Kan Auto Hvitbalanse.',
    'CanAutoZoom'           => 'Kan Auto Zoom',
    'CancelForcedAlarm'     => 'Avbryt Tvungen Alarm',
    'CanFocusAbs'           => 'Kan Fokus Absolutt',
    'CanFocus'              => 'Kan Fokus',
    'CanFocusCon'           => 'Kan Fokus Kontinuerlig',
    'CanFocusRel'           => 'Kan Fokus Relativ',
    'CanGainAbs'            => 'Kan øke absolutt',
    'CanGain'               => 'Kan øke ',
    'CanGainCon'            => 'Kan øke kontinuerlig',
    'CanGainRel'            => 'Kan øke relativt',
    'CanIrisAbs'            => 'Kan Iris Absolutt',
    'CanIris'               => 'Kan Iris',
    'CanIrisCon'            => 'Kan Iris Kontinuerlig',
    'CanIrisRel'            => 'Kan Iris Relativt',
    'CanMoveAbs'            => 'Kan Bevege Absolutt',
    'CanMove'               => 'Kan Bevege',
    'CanMoveCon'            => 'Kan Bevege Kontinuerlig',
    'CanMoveDiag'           => 'Kan Bevege Diagonalt',
    'CanMoveMap'            => 'Kan Bevege Kart',
    'CanMoveRel'            => 'Kan Bevege Relativt',
    'CanPan'                => 'Kan Panorere' ,
    'CanReset'              => 'Kan Tilbakestilles',
    'CanReboot'             => 'Kan Omstartes',
    'CanSetPresets'         => 'Kan Sette Preset',
    'CanSleep'              => 'Kan Hvilke',
    'CanTilt'               => 'Kan Tilte',
    'CanWake'               => 'Kan Vekkes',
    'CanWhiteAbs'           => 'Kan Hvitbal. Absolutt',
    'CanWhiteBal'           => 'Kan Hvitbal.',
    'CanWhite'              => 'Kan Hvitbalanse',
    'CanWhiteCon'           => 'Kan Hvitbal. Kontinuerlig',
    'CanWhiteRel'           => 'Kan Hvitbal. Relativ',
    'CanZoomAbs'            => 'Kan Zoom Absolutt',
    'CanZoom'               => 'Kan Zoom',
    'CanZoomCon'            => 'Kan Zoom Kontinuerlig',
    'CanZoomRel'            => 'Kan Zoom Relativ',
    'CaptureHeight'         => 'Opptakshøyde',
    'CaptureMethod'         => 'Opptaksmetode',
    'CaptureResolution'     => 'Oppptak Oppløsning',
    'CapturePalette'        => 'Opptakspalett',
    'CaptureWidth'          => 'Opptaksbredde',
    'CheckMethod'           => 'Alarm Sjekkmetode',
    'ChooseDetectedCamera'  => 'Velg Oppdaget Kamera',
    'ChooseDetectedProfile' => 'Velg Oppdaget Profil',
    'ChooseFilter'          => 'Velg Filter',
    'ChooseLogFormat'       => 'Velg et loggformat',
    'ChooseLogSelection'    => 'Velg et loggvalg',
    'ChoosePreset'          => 'Velg Forvalg',
    'CloneMonitor'          => 'Klone',
    'ConcurrentFilter'      => 'Kjør filter samtidig',
    'ConfigOptions'         => 'KonfigValg',
    'ConfigType'            => 'Konfig Type',
    'ConfiguredFor'         => 'Konfigurert for',
    'ConfigURL'             => 'Konfig Base URL',
    'ConfirmDeleteControl'  => 'Advarsel, sletting av kontroll vil tilbakestille alle monitorer som bruker den til ukontrollerbar.<br><br>Er du sikker på at du vil slette?',
    'ConfirmDeleteDevices'  => 'Er du sikker på at du ønsker å slette valgte enheter?',
    'ConfirmDeleteEvents'   => 'Er du sikker på at du ønsker å slette valgte hendelser?',
    'ConfirmDeleteLayout'   => 'Er du sikker på at du ønsker å slette valgte layout?',
    'ConfirmDeleteTitle'    => 'Bekreft Sletting',
    'ConfirmPassword'       => 'Bekreft Passord',
    'ConfirmUnarchiveEvents'=> 'Er du sikker på at du vil fjerne valgte hendelser fra arkiv?',
    'ConjAnd'               => 'og',
    'ConjOr'                => 'eller',
    'ContactAdmin'          => 'Vennligst kontakt administrator for informasjon.',
    'ControlAddress'        => 'Kontrolladresse',
    'ControlCap'            => 'Kontrollmulighet',
    'ControlCaps'           => 'Kontrollmuligheter',
    'ControlDevice'         => 'Kontrollenhet',
    'Controllable'          => 'Kontrollerabr',
    'ControlType'           => 'Kontrolltype',
//    'CycleWatch'            => 'Cycle Watch',
    'DefaultRate'           => 'Standard Sats',
    'DefaultScale'          => 'Standard Skala',
    'DefaultCodec'          => 'Standard Metode For Vis Hendelser',
    'DefaultView'           => 'Standard Visning',
    'RTSPDescribe'          => 'Bruk RTSP Response Media URL',
    'DeleteAndNext'         => 'Slett &amp; Neste',
    'DeleteAndPrev'         => 'Slett &amp; Forrige',
    'DeleteSavedFilter'     => 'Slett lagret filter',
    'DetectedCameras'       => 'Oppdaget Kameraer',
    'DetectedProfiles'      => 'Oppdaget Profiler',
    'DeviceChannel'         => 'Enhet Kanal',
    'DeviceFormat'          => 'Enhet Format',
    'DeviceNumber'          => 'Enhet Nummer',
    'DevicePath'            => 'Enhet Sti',
    'DisableAlarms'         => 'Deaktiver Alarmer',
    'DonateAlready'         => 'Nei, jeg har allerede donert',
    'DonateEnticement'      => 'Du har brukt Zoneminder en stund nå, og forhåpentligvis er dette et bra tillegg for sikkerheten hjemme eller på din arbeidsplass. Selv om Zoneminder er gratis, koster det penger å utvikle og vedlikeholde. Om du ønsker å støtte fremtidig utvikling og nye funksjoner, vennligst vurder å donere. Donasjoner er, selvfølgelig, frivillig men høyst verdsatt og du kan donere så mye eller lite du ønsker.<br/><br/>Hvis du ønsker å donere, vennligst velg under, eller gå til <a href="https://zoneminder.com/donate/" target="_blank">https://zoneminder.com/donate/</a> i nettleseren din.<br/><br/>Takk for at du bruker ZoneMinder og ikke glem å besøke forumet på <a href="https://forums.zoneminder.com">ZoneMinder.com</a> for støtte, eller forslag til hvordan din ZoneMinder-opplevelse kan bli enda bedre.',
    'Donate'                => 'Vennligst Donér',
    'DonateRemindDay'       => 'Ikke enda, påminn meg om 1 dag',
    'DonateRemindHour'      => 'Ikke enda, påminn meg om 1 time',
    'DonateRemindMonth'     => 'Ikke enda, påminn meg om 1 måned',
    'DonateRemindNever'     => 'Nei, jeg ønsker ikke å donere. Ikke påminn meg',
    'DonateRemindWeek'      => 'Ikke enda, påminn meg om 1 uke',
    'DonateYes'             => 'Ja, jeg ønsker å donere nå',
    'DoNativeMotionDetection'=> 'Gjør native bevegelsesdeteksjon',
    'DuplicateMonitorName'  => 'Dupliser Monitornavn',
    'DuplicateRTSPStreamName' =>  'Dupliser RTSP Strøm Navn',
    'EditControl'           => 'Endre Kontroll',
    'EditLayout'            => 'Endre layout',
    'EnableAlarms'          => 'Aktiver Alarmer',
    'EnterNewFilterName'    => 'Oppgi nytt filternavn',
    'ErrorBrackets'         => 'Feil, vennligst sørg for at du har et likt antall åpne og lukkebraketter',
    'ErrorValidValue'       => 'Feil, vennligst sørg for at alle termer har en gyldig verdi',
    'Etc'                   => 'etc',
    'EventFilter'           => 'Hendelse Filter',
    'EventId'               => 'Hendelse Id',
    'EventName'             => 'Hendelse Navn',
    'EventPrefix'           => 'Hendelse Prefix',
    'ExportCompress'        => 'Bruk kompresjon',
    'ExportDetails'         => 'Eksporter Hendelsesdetaljer',
    'ExportMatches'         => 'Eksporter treff',
    'Exif'                  => 'Bak inn EXIF-data i bilde',
    'DownloadVideo'         => 'Last ned Video',
    'GenerateDownload'      => 'Generer nedlasting',
    'EventsLoading'         => 'Laster hendelser',
    'ExistsInFileSystem'    => 'Eksisterer i Filsystem',
    'ExportFailed'          => 'Eksportering Feilet',
    'ExportFormat'          => 'Eksporter Filformat',
    'ExportFormatTar'       => 'Tar',
    'ExportFormatZip'       => 'Zip',
    'ExportFrames'          => 'Eksporter Rammedetaljer',
    'ExportImageFiles'      => 'Eksporter Bildefiler',
    'ExportLog'             => 'Eksporter Logg',
    'Exporting'             => 'Eksportering',
    'ExportMiscFiles'       => 'Eksporter Andre Filer (Dersom tilgjengelig)',
    'ExportOptions'         => 'Eksporter Valg',
    'ExportSucceeded'       => 'Eksportering Vellykket',
    'ExportVideoFiles'      => 'Eksporter Videofiler (Dersom tilgjengelig)',
    'FastForward'           => 'Spol Fremover',
    'FilterArchiveEvents'   => 'Arkiver Alle Treff',
    'FilterUnarchiveEvents' => 'Uarkiver alle treff',
    'FilterUpdateDiskSpace' => 'Oppdater brukt diskplass',
    'FilterDeleteEvents'    => 'Slett alle treff',
    'FilterCopyEvents'      => 'Kopier alle treff',
    'FilterLockRows'        => 'Lås Rader',
    'FilterMoveEvents'      => 'Flytt alle treff',
    'FilterEmailEvents'     => 'Send detaljer for alle treff på epost',
    'FilterEmailTo'    			=> 'Epost Til',
    'FilterEmailSubject'	  => 'Epost Emne',
    'FilterEmailBody'   	  => 'Epost Innhold',
    'FilterExecuteEvents'   => 'Utfør kommando for alle treff',
    'FilterLog'             => 'Filtrer logg',
    'FilterMessageEvents'   => 'Meldingsdetaljer for alle treff',
    'FilterPx'              => 'Filter Px',
    'FilterUnset'           => 'Du må spesifisere filter-bredde og høyde',
    'FilterUploadEvents'    => 'Last opp alle treff',
    'FilterUser'            => 'Kjør filter som bruker',
    'FilterVideoEvents'     => 'Opprett video for alle treff',
    'FlippedHori'           => 'Snudd Horisontalt',
    'FlippedVert'           => 'Snudd Vertikalt',
    'ForceAlarm'            => 'Tving Alarm',
    'FPS'                   => 'fps',
    'FPSReportInterval'     => 'FPS Rapporteringsintervall',
    'FrameId'               => 'Bilde Id',
    'FrameRate'             => 'Bildeskala',
    'FrameSkip'             => 'Bilde hopp',
    'MotionFrameSkip'       => 'Bevegelsesbilde hopp',
    'GenerateVideo'         => 'Generer Video',
    'GeneratingVideo'       => 'Genererer Video',
    'GetCurrentLocation'    => 'Hent Nåværende Posisjon',
    'GoToZoneMinder'        => 'Gå til ZoneMinder.com',
//    'HasFocusSpeed'         => 'Has Focus Speed',
//    'HasGainSpeed'          => 'Has Gain Speed',
//    'HasHomePreset'         => 'Has Home Preset',
//    'HasIrisSpeed'          => 'Has Iris Speed',
//    'HasPanSpeed'           => 'Has Pan Speed',
//    'HasPresets'            => 'Has Presets',
//    'HasTiltSpeed'          => 'Has Tilt Speed',
//    'HasTurboPan'           => 'Has Turbo Pan',
//    'HasTurboTilt'          => 'Has Turbo Tilt',
//    'HasWhiteSpeed'         => 'Has White Bal. Speed',
//    'HasZoomSpeed'          => 'Has Zoom Speed',
    'HighBW'                => 'Høy&nbsp;B/W',
    'ImageBufferSize'       => 'Bilde Bufferstørrelse (rammer)',
    'MaxImageBufferCount'   => 'Maks Bilde Bufferstørrelse (rammer)',
    'InvalidateTokens'      => 'Ugyldiggjør alle genererte tokens',
    'KeyString'             => 'Nøkkelstreng',
    'LimitResultsPost'      => 'resultatene', // This is used at the end of the phrase 'Limit to first N results only'
    'LimitResultsPre'       => 'Begrens til kun de første', // This is used at the beginning of the phrase 'Limit to first N results only'
    'LinkedMonitors'        => 'Linkede Monitorer',
    'ListMatches'           => 'List Treff',
    'LoggedInAs'            => 'Logget inn som',
    'LoggingIn'             => 'Logger Inn',
    'LowBW'                 => 'Lav&nbsp;B/W',
    'MaxBandwidth'          => 'Maks Båndbredde',
    'MaxBrScore'            => 'Maks<br/>Score',
    'MaxFocusRange'         => 'Maks Fokusområde',
    'MaxFocusSpeed'         => 'Maks Fokushastighet',
    'MaxFocusStep'          => 'Maks Fokussteg',
    'MaxGainRange'          => 'Maks Økningsområde',
    'MaxGainSpeed'          => 'Maks Økningshastighet',
    'MaxGainStep'           => 'Maks Økningssteg',
    'MaximumFPS'            => 'Maksimum FPS',
//    'MaxIrisRange'          => 'Max Iris Range',
//    'MaxIrisSpeed'          => 'Max Iris Speed',
//    'MaxIrisStep'           => 'Max Iris Step',
    'MaxPanRange'           => 'Maks Panoreringsområde',
    'MaxPanSpeed'           => 'Maks Panoreringshastighet',
    'MaxPanStep'            => 'Maks Panoreringssteg',
    'MaxTiltRange'          => 'Maks Tiltområde',
    'MaxTiltSpeed'          => 'Maks Tilthastighet',
    'MaxTiltStep'           => 'Maks Tiltsteg',
    'MaxWhiteRange'         => 'Maks Hvitbal.område',
    'MaxWhiteSpeed'         => 'Maks Hvitbal.hastighet',
    'MaxWhiteStep'          => 'Maks Hvitbal.steg',
    'MaxZoomRange'          => 'Maks Zoom Område',
    'MaxZoomSpeed'          => 'Maks Zoom Hastighet',
    'MaxZoomStep'           => 'Maks Zoom Steg',
    'MediumBW'              => 'Medium&nbsp;B/W',
    'MetaConfig'            => 'Meta Konfig',
    'MinAlarmAreaLtMax'     => 'Minimum alarmområde bør være mindre enn maksimum',
    'MinAlarmAreaUnset'     => 'Du må spesifisere minimum alarm pikselantall',
    'MinBlobAreaLtMax'      => 'Minimum blobområde må være mindre enn maksimum',
    'MinBlobAreaUnset'      => 'Du må spesifisere minimum blob pikselantall',
    'MinBlobLtMinFilter'    => 'Minimum blobområde bør være mindre enn, eller lik, minimum filterområde',
    'MinBlobsLtMax'         => 'Minimum blob bør være mindre enn maksimum',
    'MinBlobsUnset'         => 'Du må spesifisere minimum blobantall',
    'MinFilterAreaLtMax'    => 'Minimum filterområde bør være mindre enn maksimum',
    'MinFilterAreaUnset'    => 'Du må spesifisere minimum filter pikselantall',
    'MinFilterLtMinAlarm'   => 'Minimum filterområde bør være mindre enn, eller lik, minimum alarmområde',
    'MinFocusRange'         => 'Min. Fokusområde',
    'MinFocusSpeed'         => 'Min. Fokushastighet',
    'MinFocusStep'          => 'Min. Fokussteg',
    'MinGainRange'          => 'Min. Økningsområde',
    'MinGainSpeed'          => 'Min. Økningshastighet',
    'MinGainStep'           => 'Min. Økningssteg',
//    'MinIrisRange'          => 'Min Iris Range',
//    'MinIrisSpeed'          => 'Min Iris Speed',
//    'MinIrisStep'           => 'Min Iris Step',
    'MinPanRange'           => 'Min. Panoreringsområde',
    'MinPanSpeed'           => 'Min. Panoreringshastighet',
    'MinPanStep'            => 'Min. Panoreringssteg',
    'MinPixelThresLtMax'    => 'Minimum pikselgrense bør være mindre enn maksimum',
    'MinPixelThresUnset'    => 'Du må spesifisere minimum pikselgrense',
    'MinSectionlength'      => 'Minimum seksjonslengde',
    'MinTiltRange'          => 'Min. Tiltområde',
    'MinTiltSpeed'          => 'Min. Tilthastighet',
    'MinTiltStep'           => 'Min. Tiltsteg',
    'MinWhiteRange'         => 'Min. Hvitbal.Område',
    'MinWhiteSpeed'         => 'Min. Hvitbal. Hastighet',
    'MinWhiteStep'          => 'Min. Hvitbal. Steg',
    'MinZoomRange'          => 'Min. Zoom Område',
    'MinZoomSpeed'          => 'Min. Zoom Hastighet',
    'MinZoomStep'           => 'Min. Zoom Steg',
    'ModectDuringPTZ'       => 'Utfør bevegelsesdeteksjon under PTZ bevegelse',
    'MonitorIds'            => 'Monitor&nbsp;Ider',
    'MonitorPresetIntro'    => 'Velg en egnet forhåndsinstilling fra listen under.<br/><br/>Merk at dette kan overskrive andre verdier du har konfigurert for monitoren.<br/><br/>',
    'MonitorPreset'         => 'Monitor Preset',
    'MonitorProbeIntro'     => 'Listen under viser oppdagete analoge og nettverkskameraer og om de er klar for bruk eller allerede brukt.<br/><br/>Velg fra listen under.<br/><br/>Merk at ikke alle kameraer kan oppdages, og at å velge et kamera her kan overskrive verdier du allerede har konfogurert for monitoren.<br/><br/>',
    'MonitorProbe'          => 'Monitor Probe',
    'MontageReview'         => 'Montasjegjennomgang',
    'MtgDefault'            => 'Standard',              // Added 2013.08.15.
    'Mtg2widgrd'            => '2-bred grid',              // Added 2013.08.15.
    'Mtg3widgrd'            => '3-bred grid',              // Added 2013.08.15.
    'Mtg4widgrd'            => '4-bred grid',              // Added 2013.08.15.
    'Mtg3widgrx'            => '3-bred grid, skalert, forstørr ved alarm',              // Added 2013.08.15.
    'MustBeGe'              => 'må være større enn, eller lik',
    'MustBeLe'              => 'må være mindre enn, eller lik',
    'MustConfirmPassword'   => 'Du må bekrefte passordet',
    'MustSupplyPassword'    => 'Du må oppgi et passord',
    'MustSupplyUsername'    => 'Du må oppgi et brukernavn',
    'NewGroup'              => 'Ny Gruppe',
    'NewLabel'              => 'Nyt Merke',
    'NewPassword'           => 'Nytt Passord',
//    'NewState'              => 'New State',
    'NewUser'               => 'Ny Bruker',
    'NextMonitor'           => 'Neste Monitor',
    'NoDetectedCameras'     => 'Ingen Oppdagete Kamera',
    'NoDetectedProfiles'    => 'Ingen Oppdagete Profiler',
    'NoFramesRecorded'      => 'Det er ingen bilder lagret for denne hendelsen',
    'NoGroup'               => 'Ingen Gruppe',
    'NoneAvailable'         => 'Ingen tilgjengelig',
    'NoSavedFilters'        => 'Ingen Lagrede Filtre',
    'NoStatisticsRecorded'  => 'Det er ingen statistikk lagret for denne hendelsen/bilde',
//    'NumPresets'            => 'Num Presets',
    'OnvifProbe'            => 'ONVIF',
    'OnvifProbeIntro'       => 'Listen under viser oppdagete ONVIF-kameraer og om de er klar for bruk eller allerede brukt.<br/><br/>Velg fra listen under.<br/><br/>Merk at ikke alle kameraer kan oppdages, og at å velge et kamera her kan overskrive verdier du allerede har konfogurert for monitoren.<br/><br/>',
    'OnvifCredentialsIntro' => 'Vennligst oppgi brukernavn og passord for det valgte kameraet.<br/>Dersom ingen bruker er opprettet for kameraet vil brukernavnet oppgitt her bli opprettet samme med det oppgitte passordet.<br/><br/>',
    'OpEq'                  => 'lik som',
    'OpGtEq'                => 'større enn eller lik som',
    'OpGt'                  => 'større enn',
//    'OpIn'                  => 'in set',
    'OpLtEq'                => 'mindre enn eller lik som',
    'OpLt'                  => 'mindre enn',
    'OpMatches'             => 'treff',
    'OpNe'                  => 'ikke lik',
    'OpNotIn'               => 'not in set',
    'OpNotMatches'          => 'er ikke lik',
    'OpIs'                  => 'er',
    'OpIsNot'               => 'er ikke',
    'OpLike'                => 'inneholder',
    'OpNotLike'             => 'inneholder ikke',
    'OptionalEncoderParam'  => 'Valgfrie Encoder Parametre',
    'OptionHelp'            => 'Valg Hjelp',
    'OptionRestartWarning'  => 'These changes may not come into effect fully\nwhile the system is running. When you have\nfinished making your changes please ensure that\nyou restart ZoneMinder.',
    'Options'               => 'Options',
    'Order'                 => 'Order',
    'OrEnterNewName'        => 'or enter new name',
    'OverwriteExisting'     => 'Overwrite Existing',
    'PanLeft'               => 'Pan Left',
    'PanRight'              => 'Pan Right',
    'PanTilt'               => 'Pan/Tilt',
    'ParentGroup'           => 'Parent Group',
    'PasswordsDifferent'    => 'The new and confirm passwords are different',
    'PathToIndex'           => 'Path To Index',
    'PathToZMS'             => 'Path To ZMS',
    'PathToApi'             => 'Path To Api',
    'PauseCycle'            => 'Pause Cycle',
    'PhoneBW'               => 'Phone&nbsp;B/W',
    'PixelDiff'             => 'Pixel Diff',
    'Pixels'                => 'pixels',
    'PlayAll'               => 'Play All',
    'PlayCycle'             => 'Play Cycle',
    'PleaseWait'            => 'Please Wait',
    'PostEventImageBuffer'  => 'Post Event Image Count',
    'PreEventImageBuffer'   => 'Pre Event Image Count',
    'PreserveAspect'        => 'Preserve Aspect Ratio',
    'PreviousMonitor'       => 'Previous Monitor',
    'PrivacyAbout'          => 'About',
    'PrivacyAboutText'      => 'Since 2002, ZoneMinder has been the premier free and open-source Video Management System (VMS) solution for Linux platforms. ZoneMinder is supported by the community and is managed by those who choose to volunteer their spare time to the project. The best way to improve ZoneMinder is to get involved.',
    'PrivacyContact'        => 'Contact',
    'PrivacyContactText'    => 'Please contact us <a href="https://zoneminder.com/contact/">here</a> for any questions regarding our privacy policy or to have your information removed.<br><br>For support, there are three primary ways to engage with the community:<ul><li>The ZoneMinder <a href="https://forums.zoneminder.com/">user forum</a></li><li>The ZoneMinder <a href="https://zoneminder-chat.herokuapp.com/">Slack channel</a></li><li>The ZoneMinder <a href="https://github.com/ZoneMinder/zoneminder/issues">Github forum</a></li></ul><p>Our Github forum is only for bug reporting. Please use our user forum or slack channel for all other questions or comments.</p>',
    'PrivacyCookies'        => 'Cookies',
    'PrivacyCookiesText'    => 'Whether you use a web browser or a mobile app to communicate with the ZoneMinder server, a ZMSESSID cookie is created on the client to uniquely identify a session with the ZoneMinder server. ZmCSS and zmSkin cookies are created to remember your style and skin choices.',
    'PrivacyTelemetry'      => 'Telemetry',
    'PrivacyTelemetryText'  => 'Because ZoneMinder is open-source, anyone can install it without registering. This makes it difficult to  answer questions such as: how many systems are out there, what is the largest system out there, what kind of systems are out there, or where are these systems located? Knowing the answers to these questions, helps users who ask us these questions, and it helps us set priorities based on the majority user base.',
    'PrivacyTelemetryList'  => 'The ZoneMinder Telemetry daemon collects the following data about your system:
    <ul>
      <li>A unique identifier (UUID)</li>
      <li>City based location is gathered by querying <a href="https://ipinfo.io/geo">ipinfo.io</a>. City, region, country, latitude, and longitude parameters are saved. The latitude and longitude coordinates are accurate down to the city or town level only!</li>
      <li>Current time</li>
      <li>Total number of monitors</li>
      <li>Total number of events</li>
      <li>System architecture</li>
      <li>Operating system kernel, distro, and distro version</li>
      <li>Version of ZoneMinder</li>
      <li>Total amount of memory</li>
      <li>Number of cpu cores</li>
    </ul>',
    'PrivacyMonitorList'    => 'The following configuration parameters from each monitor are collected:
   <ul>
    <li>Id</li>
    <li>Name</li>
    <li>Manufacturer</li>
    <li>Model</li>
    <li>Type</li>
    <li>Function</li>
    <li>Width</li>
    <li>Height</li>
    <li>Colours</li>
    <li>MaxFPS</li>
    <li>AlarmMaxFPS</li>
   </ul>',
    'PrivacyConclusionText' => 'We are <u>NOT</u> collecting any image specific data from your cameras. We don’t know what your cameras are watching. This data will not be sold or used for any purpose not stated herein. By clicking accept, you agree to send us this data to help make ZoneMinder a better product. By clicking decline, you can still freely use ZoneMinder and all its features.',
    'Probe'                 => 'Probe',
    'ProfileProbe'          => 'Stream Probe',
    'ProfileProbeIntro'     => 'The list below shows the existing stream profiles of the selected camera .<br/><br/>Select the desired entry from the list below.<br/><br/>Please note that ZoneMinder cannot configure additional profiles and that choosing a camera here may overwrite any values you already have configured for the current monitor.<br/><br/>',
    'RecaptchaWarning'      => 'Your reCaptcha secret key is invalid. Please correct it, or reCaptcha will not work', // added Sep 24 2015 - PP
    'RecordAudio'		       	=> 'Whether to store the audio stream when saving an event.',
    'RefImageBlendPct'      => 'Reference Image Blend %ge',
    'RemoteHostName'        => 'Host Name',
    'RemoteHostPath'        => 'Path',
    'RemoteHostSubPath'     => 'SubPath',
    'RemoteHostPort'        => 'Port',
    'RemoteImageColours'    => 'Image Colours',
    'RemoteMethod'          => 'Method',
    'RemoteProtocol'        => 'Protocol',
    'ReplayAll'             => 'All Events',
    'ReplayGapless'         => 'Gapless Events',
    'ReplaySingle'          => 'Single Event',
    'ReportEventAudit'      => 'Audit Events Report',
    'ResetEventCounts'      => 'Reset Event Counts',
    'RestrictedCameraIds'   => 'Restricted Camera Ids',
    'RestrictedMonitors'    => 'Restricted Monitors',
    'ReturnDelay'           => 'Return Delay',
    'ReturnLocation'        => 'Return Location',
    'RevokeAllTokens'       => 'Revoke All Tokens',
    'RotateLeft'            => 'Rotate Left',
    'RotateRight'           => 'Rotate Right',
    'RTSPTransport'         => 'RTSP Transport Protocol',
    'RunAudit'              => 'Run Audit Process',
    'RunLocalUpdate'        => 'Please run zmupdate.pl to update',
    'RunMode'               => 'Run Mode',
    'RunState'              => 'Run State',
    'RunStats'              => 'Run Stats Process',
    'RunTrigger'            => 'Run Trigger Process',
    'RunEventNotification'  => 'Run Event Notification Process',
    'SaveAs'                => 'Save as',
    'SaveFilter'            => 'Save Filter',
    'SaveJPEGs'             => 'Save JPEGs',
    'Sectionlength'         => 'Section length',
    'SelectMonitors'        => 'Select Monitors',
    'SelectFormat'          => 'Select Format',
    'SelectLog'             => 'Select Log',
    'SelfIntersecting'      => 'Polygon edges must not intersect',
    'SetNewBandwidth'       => 'Set New Bandwidth',
    'SetPreset'             => 'Set Preset',
    'ShowFilterWindow'      => 'Show Filter Window',
    'ShowTimeline'          => 'Show Timeline',
    'SignalCheckColour'     => 'Signal Check Colour',
    'SignalCheckPoints'     => 'Signal Check Points',
    'SkinDescription'       => 'Change the skin for this session',
    'CSSDescription'        => 'Change the css for this session',
    'SortAsc'               => 'Asc',
    'SortBy'                => 'Sort by',
    'SortDesc'              => 'Desc',
    'SourceColours'         => 'Source Colours',
    'SourcePath'            => 'Source Path',
    'SourceType'            => 'Source Type',
    'SpeedHigh'             => 'High Speed',
    'SpeedLow'              => 'Low Speed',
    'SpeedMedium'           => 'Medium Speed',
    'SpeedTurbo'            => 'Turbo Speed',
    'StatusUnknown'         => 'Unknown',
    'StatusConnected'       => 'Capturing',
    'StatusNotRunning'      => 'Not Running',
    'StatusRunning'         => 'Not Capturing',
    'StepBack'              => 'Step Back',
    'StepForward'           => 'Step Forward',
    'StepLarge'             => 'Large Step',
    'StepMedium'            => 'Medium Step',
    'StepNone'              => 'No Step',
    'StepSmall'             => 'Small Step',
    'StorageArea'           => 'Storage Area',
    'StorageDoDelete'       => 'Do Deletes',
    'StorageScheme'         => 'Scheme',
    'StreamReplayBuffer'    => 'Stream Replay Image Buffer',
    'TargetColorspace'      => 'Target colorspace',
    'TimeDelta'             => 'Time Delta',
    'TimelineTip1'          => 'Pass your mouse over the graph to view a snapshot image and event details.',              // Added 2013.08.15.
    'TimelineTip2'          => 'Click on the coloured sections of the graph, or the image, to view the event.',              // Added 2013.08.15.
    'TimelineTip3'          => 'Click on the background to zoom in to a smaller time period based around your click.',              // Added 2013.08.15.
    'TimelineTip4'          => 'Use the controls below to zoom out or navigate back and forward through the time range.',              // Added 2013.08.15.
    'TimestampLabelFormat'  => 'Timestamp Label Format',
    'TimestampLabelX'       => 'Timestamp Label X',
    'TimestampLabelY'       => 'Timestamp Label Y',
    'TimestampLabelSize'    => 'Font Size',
    'TimeStamp'             => 'Time Stamp',
    'TooManyEventsForTimeline' => 'Too many events for Timeline. Reduce the number of monitors or reduce the visible range of the Timeline',
    'TotalBrScore'          => 'Total<br/>Score',
    'TrackDelay'            => 'Track Delay',
    'TrackMotion'           => 'Track Motion',
    'TurboPanSpeed'         => 'Turbo Pan Speed',
    'TurboTiltSpeed'        => 'Turbo Tilt Speed',
    'TZUnset'               => 'Unset - use value in php.ini',
    'UpdateAvailable'       => 'An update to ZoneMinder is available.',
    'UpdateNotNecessary'    => 'No update is necessary.',
    'UsedPlugins'	          => 'Used Plugins',
    'UseFilterExprsPost'    => '&nbsp;filter&nbsp;expressions', // This is used at the end of the phrase 'use N filter expressions'
    'UseFilterExprsPre'     => 'Use&nbsp;', // This is used at the beginning of the phrase 'use N filter expressions'
    'UseFilter'             => 'Use Filter',
    'VersionIgnore'         => 'Ignore this version',
    'VersionRemindDay'      => 'Remind again in 1 day',
    'VersionRemindHour'     => 'Remind again in 1 hour',
    'VersionRemindNever'    => 'Don\'t remind about new versions',
    'VersionRemindWeek'     => 'Remind again in 1 week',
    'VersionRemindMonth'    => 'Remind again in 1 month',
    'ViewMatches'           => 'View Matches',
    'VideoFormat'           => 'Video Format',
    'VideoGenFailed'        => 'Video Generation Failed!',
    'VideoGenFiles'         => 'Existing Video Files',
    'VideoGenNoFiles'       => 'No Video Files Found',
    'VideoGenParms'         => 'Video Generation Parameters',
    'VideoGenSucceeded'     => 'Video Generation Succeeded!',
    'VideoSize'             => 'Video Size',
    'VideoWriter'           => 'Video Writer',
    'ViewAll'               => 'View All',
    'ViewEvent'             => 'View Event',
    'ViewPaged'             => 'View Paged',
    'V4LCapturesPerFrame'  	=> 'Captures Per Frame',
    'V4LMultiBuffer'		    => 'Multi Buffering',
    'WarmupFrames'          => 'Warmup Frames',
    'WebColour'             => 'Web Colour',
    'WebSiteUrl'            => 'Website URL',
    'WhiteBalance'          => 'White Balance',
    'X10ActivationString'   => 'X10 Activation String',
    'X10InputAlarmString'   => 'X10 Input Alarm String',
    'X10OutputAlarmString'  => 'X10 Output Alarm String',
    'YouNoPerms'            => 'You do not have permissions to access this resource.',
    'ZoneAlarmColour'       => 'Alarm Colour (Red/Green/Blue)',
    'ZoneArea'              => 'Zone Area',
    'ZoneFilterSize'        => 'Filter Width/Height (pixels)',
    'ZoneMinderLog'         => 'ZoneMinder Log',
    'ZoneMinMaxAlarmArea'   => 'Min/Max Alarmed Area',
    'ZoneMinMaxBlobArea'    => 'Min/Max Blob Area',
    'ZoneMinMaxBlobs'       => 'Min/Max Blobs',
    'ZoneMinMaxFiltArea'    => 'Min/Max Filtered Area',
    'ZoneMinMaxPixelThres'  => 'Min/Max Pixel Threshold (0-255)',
    'ZoneOverloadFrames'    => 'Overload Frame Ignore Count',
    'ZoneExtendAlarmFrames' => 'Extend Alarm Frame Count',
    'ZoomIn'                => 'Zoom In',
    'ZoomOut'               => 'Zoom Out',
// language names translation
    'es_la' => 'Spanish Latam',
    'es_CR' => 'Spanish Costa Rica',
    'es_ar' => 'Spanish Argentina',
    'es_es' => 'Spanish Spain',
    'en_gb' => 'British English',
    'en_us' => 'Us English',
    'fr_fr' => 'French',
    'cs_cz' => 'Czech',
    'zh_cn' => 'Simplified Chinese',
    'zh_tw' => 'Traditional Chinese',
    'de_de' => 'German',
    'it_it' => 'Italian',
    'ja_jp' => 'Japanese',
    'hu_hu' => 'Hungarian',
    'pl_pl' => 'Polish',
    'pt_br' => 'Portuguese Brazil',
    'ru_ru' => 'Russian',
    'nl_nl' => 'Dutch',
    'se_se' => 'Sami',
    'et_ee' => 'Estonian',
    'he_il' => 'Hebrew',
    'dk_dk' => 'Danish',
    'ro_ro' => 'Romanian',

);

// Complex replacements with formatting and/or placements, must be passed through sprintf
$CLANG = array(
    'CurrentLogin'          => 'Current login is \'%1$s\'',
    'EventCount'            => '%1$s %2$s', // For example '37 Events' (from Vlang below)
    'LastEvents'            => 'Last %1$s %2$s', // For example 'Last 37 Events' (from Vlang below)
    'LatestRelease'         => 'The latest release is v%1$s, you have v%2$s.',
    'MonitorCount'          => '%1$s %2$s', // For example '4 Monitors' (from Vlang below)
    'MonitorFunction'       => 'Monitor %1$s Function',
    'RunningRecentVer'      => 'You are running the most recent version of ZoneMinder, v%s.',
    'VersionMismatch'       => 'Version mismatch, system is version %1$s, database is %2$s.',
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
    'Event'                 => array( 0=>'Events', 1=>'Event', 2=>'Events' ),
    'Monitor'               => array( 0=>'Monitors', 1=>'Monitor', 2=>'Monitors' ),
);
// You will need to choose or write a function that can correlate the plurality string arrays
// with variable counts. This is used to conjugate the Vlang arrays above with a number passed
// in to generate the correct noun form.
//
// In languages such as English this is fairly simple
// Note this still has to be used with printf etc to get the right formatting
function zmVlang($langVarArray, $count) {
  krsort($langVarArray);
  foreach ($langVarArray as $key=>$value) {
    if (abs($count) >= $key) {
      return $value;
    }
  }
  ZM\Error('Unable to correlate variable language string');
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
    'Help' => '
      Parameters in this field are passed on to FFmpeg. Multiple parameters can be separated by ,~~
      Examples (do not enter quotes)~~~~
      "allowed_media_types=video" Set datatype to request from cam (audio, video, data)~~~~
      "reorder_queue_size=nnn" Set number of packets to buffer for handling of reordered packets
    '
	),
  'OPTIONS_ENCODER_PARAMETERS' => array(
    'Help' => '
    Parameters passed to the encoding codec. name=value separated by either , or newline.~~
    For example to changing quality, use the crf option.  1 is best, 51 is worst 23 is default.~~
~~
    crf=23~~
    ~~
    You might want to alter the movflags value to support different behaviours. Some people have troubles viewing videos due to the frag_keyframe option, but that option is supposed to allow viewing of incomplete events. See 
    [https://ffmpeg.org/ffmpeg-formats.html](https://ffmpeg.org/ffmpeg-formats.html)
    for more information.  ZoneMinder\'s default is frag_keyframe,empty_moov~~
    ',
  ),
  'OPTIONS_DECODERHWACCELNAME' => array(
    'Help' => '
    This is equivalent to the ffmpeg -hwaccel command line option.  With intel graphics support, use "vaapi".  For NVIDIA cuda support use "cuda". To check for support, run ffmpeg -hwaccels on the command line.'
    ),
  'OPTIONS_DECODERHWACCELDEVICE' => array(
    'Help' => '
    This is equivalent to the ffmpeg -hwaccel_device command line option.  You should only have to specify this if you have multiple GPUs.  A typical value for Intel VAAPI would be /dev/dri/renderD128.'
    ),
    'OPTIONS_RTSPTrans' => array(
      'Help' => '
        This sets the RTSP Transport Protocol for FFmpeg.~~
        TCP - Use TCP (interleaving within the RTSP control channel) as transport protocol.~~
        UDP - Use UDP as transport protocol. Higher resolution cameras have experienced some \'smearing\' while using UDP, if so try TCP~~
        UDP Multicast - Use UDP Multicast as transport protocol~~
        HTTP - Use HTTP tunneling as transport protocol, which is useful for passing proxies.~~
      '
	),
	'OPTIONS_LIBVLC' => array(
    'Help' => '
      Parameters in this field are passed on to libVLC. Multiple parameters can be separated by ,~~
      Examples (do not enter quotes)~~~~
      "--rtp-client-port=nnn" Set local port to use for rtp data~~~~
      "--verbose=2" Set verbosity of libVLC
      '
	),
	'OPTIONS_EXIF' => array(
		'Help' => 'Enable this option to embed EXIF data into each jpeg frame.'
	),
	'OPTIONS_RTSPDESCRIBE' => array(
    'Help' => '
      Sometimes, during the initial RTSP handshake, the camera will send an updated media URL.
      Enable this option to tell ZoneMinder to use this URL. Disable this option to ignore the
      value from the camera and use the value as entered in the monitor configuration~~~~
      Generally this should be enabled. However, there are cases where the camera can get its
      own URL incorrect, such as when the camera is streaming through a firewall
    '
  ),
	'OPTIONS_MAXFPS' => array(
    'Help' => '
      This field has certain limitations when used for non-local devices.~~
      Failure to adhere to these limitations will cause a delay in live video, irregular frame skipping,
      and missed events~~
      For streaming IP cameras, do not use this field to reduce the frame rate. Set the frame rate in the
      camera, instead. In the past it was advised to set a value higher than the frame rate of the camera
      but this is no longer needed or a good idea.
      Some, mostly older, IP cameras support snapshot mode. In this case ZoneMinder is actively polling the camera
      for new images. In this case, it is safe to use the field.
      '
	),
	'OPTIONS_ALARMMAXFPS' => array(
    'Help' => '
    This field has certain limitations when used for non-local devices.~~
    Failure to adhere to these limitations will cause a delay in live video, irregular frame skipping,
    and missed events~
    This setting allows you to override the Maximum FPS value if this circumstance occurs. As with the Maximum FPS 
    setting, leaving this blank implies no limit.
    '
	),
	'OPTIONS_LINKED_MONITORS' => array(
    'Help' => '
      This field allows you to select other monitors on your system that act as 
      triggers for this monitor. So if you have a camera covering one aspect of 
      your property you can force all cameras to record while that camera 
      detects motion or other events. Click on ‘Select’ to choose linked monitors. 
      Be very careful not to create circular dependencies with this feature 
      because you will have infinitely persisting alarms which is almost 
      certainly not what you want! To unlink monitors you can ctrl-click.
      '
	),
  'OPTIONS_CAPTURING' => array(
    'Help' => 'When to do capturing:~~~~
None: Do not run a process, do not do capturing.  Equivalent to the old Function == None~~~~
Ondemand: A zmc process will run, but will wait for a viewer (live view, thumbnail or rstp server connection) before connecting to the camera.~~~~
Always: A zmc process will run and immediately connect and stay connected.~~~~
',
  ),
  'OPTIONS_RTSPSERVER' => array(
    'Help' => '
     ZM supplies its own RTSP server that can re-stream RTSP or attempt to convert the
     monitor stream into RTSP. This is useful if you want to use the ZM Host machines
     resources instead of having multiple clients pulling from a single camera.~~~~
     NOTE:~~
     Options > Network > MIN_RTSP_PORT is configurable.
     ',
    ),
  'OPTIONS_RTSPSTREAMNAME' => array(
     'Help' => '
     If RTSPServer is enabled, this will be the endpoint it will be available at.
     For example, if this is monitor ID 6, MIN_RTSP_PORT=20000 and RTSPServerName
     is set to "my_camera", access the stream at rtsp://ZM_HOST:20006/my_camera
     ',
    ),
  'FUNCTION_ANALYSIS_ENABLED' => array(
    'Help' => '
      When to perform motion detection on the captured video.  
      This setting sets the default state when the process starts up.
      It can then be turned on/off through external triggers zmtrigger zmu or the web ui.
      When not enabled no motion detection or linked monitor checking will be performed and 
      no events will be created.
      '
  ),
  'FUNCTION_DECODING' => array(
    'Help' => '
      When not performing motion detection and using H264Passthrough with no jpegs being saved, we can
      optionally choose to not decode the H264/H265 packets.  This will drastically reduce cpu use.~~~~
Always: every frame will be decoded, live view and thumbnails will be available.~~~~
OnDemand: only do decoding when someone is watching.~~~~
KeyFrames: Only keyframes will be decoded, so viewing frame rate will be very low, depending on the keyframe interval set in the camera.~~~~
None: No frames will be decoded, live view and thumbnails will not be available~~~~
'
  ),
  'FUNCTION_RTSP2WEB_ENABLED' => array(
    'Help' => '
      Attempt to use RTSP2Web streaming server for h264/h265 live view. Experimental, but allows
      for significantly better performance.'
  ),
  'FUNCTION_RTSP2WEB_TYPE' => array(
    'Help' => '
      RTSP2Web supports MSE (Media Source Extensions), HLS (HTTP Live Streaming), and WebRTC.
      Each has its advantages, with WebRTC probably being the most performant, but also the most picky about codecs.'
  ),
  'FUNCTION_JANUS_ENABLED' => array(
    'Help' => '
      Attempt to use Janus streaming server for h264/h265 live view. Experimental, but allows
      for significantly better performance.'
  ),
  'FUNCTION_JANUS_AUDIO_ENABLED' => array(
    'Help' => '
      Attempt to enable audio in the Janus stream. Has no effect for cameras without audio support,
      but can prevent a stream playing if your camera sends an audio format unsupported by the browser.'
  ),
  'FUNCTION_JANUS_PROFILE_OVERRIDE' => array(
    'Help' => '
      Manually set a Profile-ID, which can force a browser to try to play a given stream. Try "42e01f"
      for a universally supported value, or leave this blank to use the Profile-ID specified by the source.'
  ),
  'FUNCTION_JANUS_USE_RTSP_RESTREAM' => array(
    'Help' => '
      If your camera will not work under Janus with any other options, enable this to use the ZoneMinder
      RTSP restream as the Janus source.'
  ),
  'FUNCTION_JANUS_RTSP_SESSION_TIMEOUT' => array(
    'Help' => '
    Override or set a timeout period in seconds for the RTSP session. Useful if you see a lot of 401
    Unauthorized responses in janus logs. Set to 0 to use the timeout (if sent) from the source.'
  ),
  'ImageBufferCount' => array(
    'Help' => '
    Number of raw images available in /dev/shm. Currently should be set in the 3-5 range.  Used for live viewing.'
  ),
  'MaxImageBufferCount' => array(
    'Help' => '
    Maximum number of video packets that will be held in the packet queue.
    The packetqueue will normally manage itself, keeping Pre Event Count frames or all since last keyframe if using 
    passthrough mode. You can set a maximum to prevent the monitor from consuming too much ram, but your events might
    not have all the frames they should if your keyframe interval is larger than this value.
    You will get errors in your logs about this. So make sure your keyframe interval is low or you have enough ram.
  '
  ),
// Help for soap_wsa issue with chinesse cameras
   'OPTIONS_SOAP_wsa' => array(
    'Help' => '
     Disable it if you receive an error ~~~ Couldnt do Renew Error 12 ActionNotSupported
     <env:Text>The device do NOT support this feature</env:Text> ~~~ when trying to enable/use ONVIF ~~it may
     help to get it to work... it is confirmed to work in some chinese cameras that do not implement ONVIF entirely
    '
   ),

//    'LANG_DEFAULT' => array(
//        'Prompt' => "This is a new prompt for this option",
//        'Help' => "This is some new help for this option which will be displayed in the window when the ? is clicked"
//    ),
);

?>
