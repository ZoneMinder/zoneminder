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
    'OptionRestartWarning'  => 'Disse endringene trer kanskje ikke i kraft fullstendig\nmens systemet kjører. Når du har fullført\nfullført endringene, forsikre deg om at du\nstarter Zoneminder på nytt.',
    'Options'               => 'Valg',
    'Order'                 => 'Rekkefølge',
    'OrEnterNewName'        => 'eller oppgi nytt navn',
    'OverwriteExisting'     => 'Overskriv Eksisterende',
    'PanLeft'               => 'Pan Venstre',
    'PanRight'              => 'Pan Høyre',
    'PanTilt'               => 'Pan/Tilt',
    'ParentGroup'           => 'Overordnet Gruppe',
    'PasswordsDifferent'    => 'Nytt og bekreftet passord er ikke lik',
    'PathToIndex'           => 'Sti Til Index',
    'PathToZMS'             => 'Sti Til ZMS',
    'PathToApi'             => 'Sti Til Api',
    'PauseCycle'            => 'Pause Syklus',
    'PhoneBW'               => 'Mobil&nbsp;Båndbr.',
    'PixelDiff'             => 'Pixel Diff',
    'Pixels'                => 'pixler',
    'PlayAll'               => 'Spill Alle',
    'PlayCycle'             => 'Spill Syklus',
    'PleaseWait'            => 'Vennligst Vent',
    'PostEventImageBuffer'  => 'Etter Hendelse Bildeantall',
    'PreEventImageBuffer'   => 'Før Hendelse Bildeantall',
    'PreserveAspect'        => 'Bevar sideforhold',
    'PreviousMonitor'       => 'Forrige Monitor',
    'PrivacyAbout'          => 'Om',
    'PrivacyAboutText'      => 'Siden 2002 har ZoneMinder vært den ledende gratis og åpen kildekode Video Management System (VMS)-løsningen for Linux-plattformer. ZoneMinder støttes av fellesskapet og administreres av de som velger å bruke fritiden sin til prosjektet. Den beste måten å forbedre ZoneMinder på er å engasjere seg.',
    'PrivacyContact'        => 'Kontakt',
    'PrivacyContactText'    => 'Vennligst kontakt oss <a href="https://zoneminder.com/contact/">her</a> for spørsmål knyttet til personvernerklæringen vår, eller for å fjerne din informasjon.<br><br>For støtte er det tre hovedmåter å bidra til fellesskapet:<ul><li>ZoneMinder <a href="https://forums.zoneminder.com/">brukerforum</a></li><li>ZoneMinder <a href="https://zoneminder-chat.herokuapp.com/">Slack-kanal</a></li><li>ZoneMinder <a href="https://github.com/ZoneMinder/zoneminder/issues">Github forum</a></li></ul><p>Vårt Github Forum er kun for rapportering av feil. For andre spørsmål og kommentarer skal brukerforumet eller slack-kanalen brukes.</p>',
    'PrivacyCookies'        => 'Cookies',
    'PrivacyCookiesText'    => 'Enten du bruker en nettleser eller mobilapp for å kommunisere med Zoneminder-serveren, vil en ZMSESSID cookie bli opprettet på klienten for å unikt kunne identifisere en økt. ZmCSS og zmSkin cookies brukes for å huske dine valg for utseende.',
    'PrivacyTelemetry'      => 'Telemetri',
    'PrivacyTelemetryText'  => 'Siden Zoneminder er åpen kildekode, kan alle installere dette uten å registrere seg. Dette gjør det vanskelig å svare på spørsmål som: hvor mange system er aktive, hva er det største systemet som er installert, hvilke type systemer er aktive, og hvor er disse systemene lokalisert? Å vite svaret på disse spørsmålene hjelper brukerne som spør disse spørsmålene og det hjelper oss med å prioritere basert på brukermassen.',
    'PrivacyTelemetryList'  => 'Zoneminder Telemetri Daemon samler følgende informasjon om systemet ditt:
    <ul>
      <li>En unik identifikator (UUID)</li>
      <li>Informasjon om byer er samlet inn via en spørring mot <a href="https://ipinfo.io/geo">ipinfo.io</a>. By, fylke, land, lengde og breddegrad lagres. Lengde og breddegrad er kun nøyaktig ned til by eller fylke!</li>
      <li>Nåværende tid</li>
      <li>Totalt antallmonitorer</li>
      <li>Totalt antall hendelser</li>
      <li>Systemarkitektur</li>
      <li>Operativsystem kernel, distro, og distro versjon</li>
      <li>Versjonen til ZoneMinder</li>
      <li>Totalt minne</li>
      <li>Antall CPU kjerner</li>
    </ul>',
    'PrivacyMonitorList'    => 'Følgende paramterte for hver monitor er samlet inn:
   <ul>
    <li>Id</li>
    <li>Navn</li>
    <li>Produsent</li>
    <li>Modell</li>
    <li>Type</li>
    <li>Funksjon</li>
    <li>Bredde</li>
    <li>Høyde</li>
    <li>Farger</li>
    <li>Maks FPS</li>
    <li>Alarm Maks FPS</li>
   </ul>',
    'PrivacyConclusionText' => 'Vi samler <u>IKKE</u> inn bildespesifikke data fra dine kameraer. Vi vet ikke hva dine kamera ser. Disse dataene vil ikke bli solgt eller brukt til formål som ikke er spesifisert her. Ved å klikke aksepter, godtar du at data sendes for å bidra til å gjøre Zoneminder til et bedre produkt. Ved å klikke avvis, kan du fortsatt bruke Zoneminder og dens funksjoner.',
    'Probe'                 => 'Probe',
    'ProfileProbe'          => 'Stream Probe',
    'ProfileProbeIntro'     => 'Listen under viser eksisterende strømme-profiler for det valgte kameraet.<br/><br/>Velg fra listen under.<br/><br/>Merk at Zoneminder ikke kan konfigurere flere profiler, og at å velge et kamera her kan overskrive verdier du allerede har konfigurert for gjeldende monitor.<br/><br/>',
    'RecaptchaWarning'      => 'Din reCaptcha nøkkel er ugyldig. Vennligst korriger dette, ellers vil ikke reCaptchafungere', // added Sep 24 2015 - PP
    'RecordAudio'		       	=> 'Skal lydstrømmen lagres når man lagrer en hendelse?',
    'RefImageBlendPct'      => 'Referansebilde Blender-prosent',
    'RemoteHostName'        => 'Tjenernavn',
    'RemoteHostPath'        => 'Sti',
    'RemoteHostSubPath'     => 'Understi',
    'RemoteHostPort'        => 'Port',
    'RemoteImageColours'    => 'Bildefarger',
    'RemoteMethod'          => 'Metode',
    'RemoteProtocol'        => 'Protokoll',
    'ReplayAll'             => 'Alle Hendelser',
    'ReplayGapless'         => 'Sømløse Hendelser',
    'ReplaySingle'          => 'Singel Hendelse',
    'ReportEventAudit'      => 'Gjennomgå Hendelser',
    'ResetEventCounts'      => 'Tilbakestill Hendelsestall',
    'RestrictedCameraIds'   => 'Begrensede Kamera IDer',
    'RestrictedMonitors'    => 'Begrensede Monitorer',
    'ReturnDelay'           => 'Returforsinkelse',
    'ReturnLocation'        => 'Returplassering',
    'RevokeAllTokens'       => 'Opphev alle tokens',
    'RotateLeft'            => 'Roter Venstre',
    'RotateRight'           => 'Roter Høyre',
    'RTSPTransport'         => 'RTSP Transport Protokoll',
    'RunAudit'              => 'Kjør Hendelsesprosess',
    'RunLocalUpdate'        => 'Vennligst kjør zmupdate.pl for å oppdatere',
    'RunMode'               => 'Kjør Modus',
    'RunState'              => 'Kjør Status',
    'RunStats'              => 'Kjør Statistikkprosess',
    'RunTrigger'            => 'Kjør Utløserprosess',
    'RunEventNotification'  => 'Kjør Hendelse Varslingsprosess',
    'SaveAs'                => 'Lagre som',
    'SaveFilter'            => 'Lagre Filter',
    'SaveJPEGs'             => 'Lagre JPEGs',
    'Sectionlength'         => 'Seksjonslengde',
    'SelectMonitors'        => 'Velg Monitorer',
    'SelectFormat'          => 'Velg Format',
    'SelectLog'             => 'Velg Logg',
    'SelfIntersecting'      => 'Polygon-kanter må ikke krysse hverandre',
    'SetNewBandwidth'       => 'Sett Ny Båndbredde',
    'SetPreset'             => 'Sett Forhåndsvalg',
    'ShowFilterWindow'      => 'Vis Filtervindu',
    'ShowTimeline'          => 'Vis Tidslinje',
    'SignalCheckColour'     => 'Signalsjekk Farge',
    'SignalCheckPoints'     => 'Signalsjekk Punkter',
    'SkinDescription'       => 'Endre utseende for denne økten',
    'CSSDescription'        => 'Endre CSS for denne økten',
    'SortAsc'               => 'Stigende',
    'SortBy'                => 'Sorter etter',
    'SortDesc'              => 'Synkende',
    'SourceColours'         => 'Kilde Farger',
    'SourcePath'            => 'Kilde Sti',
    'SourceType'            => 'Kilde Type',
    'SpeedHigh'             => 'Høy Hastighet',
    'SpeedLow'              => 'Lav Hastighet',
    'SpeedMedium'           => 'Medium Hastighet',
    'SpeedTurbo'            => 'Turbo Hastighet',
    'StatusUnknown'         => 'Ukjent',
    'StatusConnected'       => 'Tar Opp',
    'StatusNotRunning'      => 'Kjører ikke',
    'StatusRunning'         => 'Tar Ikke Oppg',
    'StepBack'              => 'Steg Tilbake',
    'StepForward'           => 'Steg Frem',
    'StepLarge'             => 'Stort Steg',
    'StepMedium'            => 'Medium Steg',
    'StepNone'              => 'Ingen Steg',
    'StepSmall'             => 'Lite Steg',
    'StorageArea'           => 'Lagringsområde',
    'StorageDoDelete'       => 'Utfør Sletting',
    'StorageScheme'         => 'Skjema',
    'StreamReplayBuffer'    => 'Strøm Reprise Bildebuffer',
    'TargetColorspace'      => 'Mål fargerom',
    'TimeDelta'             => 'Tid Delta',
    'TimelineTip1'          => 'Hold musepekeren over grafen for å vise bilde og hendelsesdetaljer.',              // Added 2013.08.15.
    'TimelineTip2'          => 'Klikk på den fargede delen av en grafe, eller bildet for å vise hendelsen.',              // Added 2013.08.15.
    'TimelineTip3'          => 'Klikk på bakgrunnen for å zoome til en mindre tidsperiode, basert på hvor du klikker.',              // Added 2013.08.15.
    'TimelineTip4'          => 'Bruk kontrollene under for å zoome ut, eller navigere frem og tilbake i tidsområdet.',              // Added 2013.08.15.
    'TimestampLabelFormat'  => 'Tidsstempel Format',
    'TimestampLabelX'       => 'Tidsstempel Merke X',
    'TimestampLabelY'       => 'Tidsstempel Merke Y',
    'TimestampLabelSize'    => 'Tekststørrelse',
    'TimeStamp'             => 'Tidsstempel',
    'TooManyEventsForTimeline' => 'For mange hendelser for tidslinje. Reduser antall monitorer eller det synlige området for tidslinjen',
    'TotalBrScore'          => 'Total<br/>Score',
    'TrackDelay'            => 'Spor Forsinkelse',
    'TrackMotion'           => 'Spor Bevegelse',
    'TurboPanSpeed'         => 'Turbo Pan Hastighet',
    'TurboTiltSpeed'        => 'Turbo Tilt Hastighet',
    'TZUnset'               => 'Unset - bruk verdi i php.ini',
    'UpdateAvailable'       => 'En oppdatering for Zoneminder er tilgjengelig.',
    'UpdateNotNecessary'    => 'Ingen oppdatering nødvendig.',
    'UsedPlugins'	          => 'Brukte Plugins',
    'UseFilterExprsPost'    => '&nbsp;filter&nbsp;uttrykk', // This is used at the end of the phrase 'use N filter expressions'
    'UseFilterExprsPre'     => 'Bruk&nbsp;', // This is used at the beginning of the phrase 'use N filter expressions'
    'UseFilter'             => 'Bruk Filter',
    'VersionIgnore'         => 'Ignorer denne versjonen',
    'VersionRemindDay'      => 'Påminn meg om 1 dag',
    'VersionRemindHour'     => 'Påminn meg om 1 1 time',
    'VersionRemindNever'    => 'Ikke påminn meg om nye versjoner',
    'VersionRemindWeek'     => 'Påminn meg om 1 1 uke',
    'VersionRemindMonth'    => 'Påminn meg om 1 1 måned',
    'ViewMatches'           => 'Vis Treff',
    'VideoFormat'           => 'Videoformat',
    'VideoGenFailed'        => 'Videogenerering feilet!',
    'VideoGenFiles'         => 'Eksisterende Videofiler',
    'VideoGenNoFiles'       => 'Ingen Videofiler Funnet',
    'VideoGenParms'         => 'Videogenerering Parametre',
    'VideoGenSucceeded'     => 'Videogenerering Vellykket!',
    'VideoSize'             => 'Videostørrelse',
    'VideoWriter'           => 'Videoskriver',
    'ViewAll'               => 'Vis Alle',
    'ViewEvent'             => 'Vis Hendelse',
    'ViewPaged'             => 'Vis sider',
    'V4LCapturesPerFrame'  	=> 'Oppdaging Per Ramme',
    'V4LMultiBuffer'		    => 'Multibuffer',
    'WarmupFrames'          => 'Oppvarmingsrammer',
    'WebColour'             => 'Webfarge',
    'WebSiteUrl'            => 'Nettsted URL',
    'WhiteBalance'          => 'Hvitbalanse',
    'X10ActivationString'   => 'X10 Aktiveringsstreng',
    'X10InputAlarmString'   => 'X10 Input Alarmstreng',
    'X10OutputAlarmString'  => 'X10 Utput Alarmstreng',
    'YouNoPerms'            => 'Du har ikke tillatelse til å vise denne ressursen.',
    'ZoneAlarmColour'       => 'Alarmfarge (Rød/Grønn/Blå)',
    'ZoneArea'              => 'Soneområde',
    'ZoneFilterSize'        => 'Filter Bredde/Høyde (pixler)',
    'ZoneMinderLog'         => 'ZoneMinder Logg',
    'ZoneMinMaxAlarmArea'   => 'Min/Maks Alarmområde',
    'ZoneMinMaxBlobArea'    => 'Min/Maks Blobområde',
    'ZoneMinMaxBlobs'       => 'Min/Maks Blober',
    'ZoneMinMaxFiltArea'    => 'Min/Maks Filterområde',
    'ZoneMinMaxPixelThres'  => 'Min/Maks Pixel Grense (0-255)',
    'ZoneOverloadFrames'    => 'Ignorer Rammer Etter Overbelastning',
    'ZoneExtendAlarmFrames' => 'Utvidet Alarmramme Antall',
    'ZoomIn'                => 'Zoom Inn',
    'ZoomOut'               => 'Zoom Ut',
// language names translation
    'es_la' => 'Spansk Latam',
    'es_CR' => 'Spansk Costa Rica',
    'es_ar' => 'Argentinsk',
    'es_es' => 'Spansk',
    'en_gb' => 'Britisk Engelsk',
    'en_us' => 'Us Engelsk',
    'fr_fr' => 'Fransk',
    'cs_cz' => 'Tsjekkisk',
    'zh_cn' => 'Forenklet Kinesisk',
    'zh_tw' => 'Tradisjonell Kinesisk',
    'de_de' => 'Tysk',
    'it_it' => 'Italiensk',
    'ja_jp' => 'Japansk',
    'hu_hu' => 'Ungarsk',
    'pl_pl' => 'Polsk',
    'pt_br' => 'Portugisisk Brasil',
    'ru_ru' => 'Russisk',
    'nl_nl' => 'Nederlandsk',
    'se_se' => 'Samisk',
    'et_ee' => 'Estisk',
    'he_il' => 'Hebraisk',
    'dk_dk' => 'Dansk',
    'ro_ro' => 'Rumensk',
    'no_nb' => 'Norsk',

);

// Complex replacements with formatting and/or placements, must be passed through sprintf
$CLANG = array(
    'CurrentLogin'          => 'Gjeldende innlogging er \'%1$s\'',
    'EventCount'            => '%1$s %2$s', // For example '37 Events' (from Vlang below)
    'LastEvents'            => 'Siste %1$s %2$s', // For example 'Last 37 Events' (from Vlang below)
    'LatestRelease'         => 'Siste versjon er v%1$s, you have v%2$s.',
    'MonitorCount'          => '%1$s %2$s', // For example '4 Monitors' (from Vlang below)
    'MonitorFunction'       => 'Monitor %1$s Funksjon',
    'RunningRecentVer'      => 'Du kjører den siste versjonen av ZoneMinder, v%s.',
    'VersionMismatch'       => 'Versjon samsvarer ikke, system er versjon %1$s, database er %2$s.',
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
    'Event'                 => array( 0=>'Hendelser', 1=>'Hendelse', 2=>'Hendelser' ),
    'Monitor'               => array( 0=>'Monitorer', 1=>'Monitor', 2=>'Monitorer' ),
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
    'Hjelp' => '
      Parametre i dette felter blir formidlet til FFmpeg. Flere parametre kan skilles med ,~~
      Eksempler (ikke skriv anførselstegn)~~~~
      "allowed_media_types=video" Sett dataype til å spørre kamera (lyd, bilde, data)~~~~
      "reorder_queue_size=nnn" Sett  antall pakker å buffre for håndtering av lagrede pakker
    '
	),
  'OPTIONS_ENCODER_PARAMETERS' => array(
    'Hjelp' => '
    Parametre blir formidlet til encoding codec. navn=verdi skilt med enten , eller ny linje.~~
    Eksempel for å endre kvalitet, bruk crf valg.  1 er best, 51 er verst 23 er standard.~~
~~
    crf=23~~
    ~~
    Du må kanskje endre movflags verdi for å større forskjellig oppførsel. Enkelte har problemer med å vise video grunnet frag_keyframe valget, men det valget er ment for å tillate visning av uferdige hendelser. Se 
    [https://ffmpeg.org/ffmpeg-formats.html](https://ffmpeg.org/ffmpeg-formats.html)
    for mer informasjon.  ZoneMinder\'s standard er frag_keyframe,empty_moov~~
    ',
  ),
  'OPTIONS_DECODERHWACCELNAME' => array(
    'Hjelp' => '
    Dette er tilsvarende til ffmpeg -hwaccel kommandolinje valget. Med intel graphics støtte, bruk "vaapi".  For NVIDIA cuda støtte bruk "cuda". For å sjekke støtten, kjør ffmpeg -hwaccels i kommandolinjen.'
    ),
  'OPTIONS_DECODERHWACCELDEVICE' => array(
    'Hjelp' => '
    Dette er tilsvarende til ffmpeg -hwaccel_device kommandolinje valg.  Du trenger kun å spesifisere dette dersom du har flere GPU\'er. En typisk verdi for Intel VAAPI vil være /dev/dri/renderD128.'
    ),
    'OPTIONS_RTSPTrans' => array(
      'Hjelp' => '
        Dette setter RTSP Transportprotokoll for FFmpeg.~~
        TCP - Bruk TCP (interleaving within the RTSP control channel) som transportprotokoll.~~
        UDP - Bruk UDP som transportprotokoll. Kameraer med høyere oppløsning opplever ofte \'tilgrising\' av bilde ved bruk av UDP, dersom dette oppleves, bruk TCP~~
        UDP Multicast - Bruk UDP Multicast som transportprotokoll~~
        HTTP - Bruk HTTP tunnellering som transportprotokoll, som er nyttig for bruk gjennom proxy.~~
      '
	),
	'OPTIONS_LIBVLC' => array(
    'Hjelp' => '
      Parametre i dette feltet formidles til libVLC. Flere parametre kan skilles med ,~~
      Eksempel (ikke skriv anførselstegn)~~~~
      "--rtp-client-port=nnn" Setter lokal port for rtp data~~~~
      "--verbose=2" Setter verbositet for libVLC
      '
	),
	'OPTIONS_EXIF' => array(
		'Hjelp' => 'Aktiver dette valget for å bake inn EXIT-data i hver JPEG ramme.'
	),
	'OPTIONS_RTSPDESCRIBE' => array(
    'Hjelp' => '
    Noen ganger, under første RTSP handshake, vil kameraet sende en oppdatert media URL.
      Aktiver denne for å be Zoneminder om å bruke denne URL\'en. Deaktiver valget for å ignorere
      verdien fra kameraet og bruk verdign oppgitt i monitorkonfigurasjonen~~~~
      Generelt sett bør denne være aktivert. Men, det finnes tilfeller hvor kameraet kan oppgi
      feil URL, for eksempel dersom strømmen går gjennom en brannmur
    '
  ),
	'OPTIONS_MAXFPS' => array(
    'Hjelp' => '
      Dette feltet har enkelse begrensninger for bruk med ikke-lokale enheter.~~
      Dersom man ikke følger begrensningene kan man oppleve forsinkelser i live video, hakking
      eller uoppdagete hendelser~~
      For strømming med IP kameraet, ikke bruk dette feltet for å redusere bildeoppdateringsfrekvensen. Sette bildeoppdateringsfrekvens
      i kameraet. Tidligere var anbefalingen å sette denne verdien høyere enn frekvensen i kameraet men
      dette er ikke lenger nødvendig, eller en god ide.
      Noen, som regel eldre, IP kamera støtter stillbilde modus. Da vil Zoneminder aktivt spørre kameraet
      etter nye bilder. I dette tilfellet er det trygt å bruke dette feltet.
      '
	),
	'OPTIONS_ALARMMAXFPS' => array(
    'Hjelp' => '
    Dette feltet har enkelse begrensninger for bruk med ikke-lokale enheter.~~
    Dersom man ikke følger begrensningene kan man oppleve forsinkelser i live video, hakking
    eller uoppdagete hendelser~~
    Denne instillingen tillater å overskrive Maksimum FPS verdi dersom tilfellet inntreffer. Som ved Maksimum FPS 
    instilling, å la denne stå tom tilsvarer ingen begrensing.
    '
	),
	'OPTIONS_LINKED_MONITORS' => array(
    'Hjelp' => '
      Dette feltet lar deg velge andre monitorer i systemet som kan fungere som
      en utløser for denne monitoren. Dersom du har et kamera som dekker ett område
      kan du tvinge alle kameraer til å ta opp video så lenge det kameraet oppdager
      bevegelse eller andre hendelser. Klikk på ‘Velg’ for å velge linkede monitorer. 
      Vær veldig forsikrig slik at du ikke lager en sirkulær avhengighet, hvor du ender opp med 
      utløsere som jobber mot hverandre. For å fjerne en monitor kan du holde inne Ctrl og klikke.
      '
	),
  'OPTIONS_CAPTURING' => array(
    'Hjelp' => 'Når skal det tas opp:~~~~
Ingen: Ikke kjør en prosess, ikke ta opp.~~~~
Forespørsel: En ZMC prosess vil kjøre, men vil vente på forespørsel (Live visning, miniatyrbilde eller RTSP servertilkobling) før tilkobling til kamera.~~~~
Alltid: En ZMC prosess vil kjøre og umiddelbart koble til og forbli tilkoblet.~~~~
',
  ),
  'OPTIONS_RTSPSERVER' => array(
    'Hjelp' => '
     ZM har sin egen RTSP server som kan re-strømme RTSP eller forsøke å konvertere
     monitorstrømmen til RTSP. Dette er nyttig om du ønsker å bruke ZM Tjenermaskinens
     ressurser i stedet for å ha flere klienter som henter en strøm fra kameraet.~~~~
     MERK:~~
     Valg > Nettverk > MIN_RTSP_PORT er konfigurerbar.
     ',
    ),
  'OPTIONS_RTSPSTREAMNAME' => array(
     'Hjelp' => '
     Hvis RTSPServer er aktivert, vil dette bli endepunktet den er tilgjengelig på.
     For eksempel, hvis dette er monitor ID 6, MIN_RTSP_PORT=20000 og RTSPServerName
     er satt til "mitt_kamera", når du strømmen på rtsp://ZM_HOST:20006/mitt_kamera
     ',
    ),
  'FUNCTION_ANALYSIS_ENABLED' => array(
    'Hjelp' => '
      Når skal det utføres bevegelsesdeteksjon på den lagrede videoen.
      Denne instillingen setter en standardstatus når prosessen starter.
      Den kan deretter slås på/av gjennom eksterne utløsere som zmtrigger, ZMU eller webgrensesnitt.
      Når deaktivert, vil ingen bevegelsesoppdagelse eller monitor-sjekk utføres
      og ingen hendelser vil opprettes.
      '
  ),
  'FUNCTION_DECODING' => array(
    'Hjelp' => '
      Når der ikke utføres bevegelsesdeteksjon og man benytter H264Passthrough uten lagring av JPEGS, kan vi
      velge å ikke decode H264/H265 pakkene. Dette vil drastisk redusere CPU-bruken.~~~~
      Alltid: hver ramme vil bli dekodet, live visning og miniatyrbilder er tilgjengelig~~~~
      Forespørsel: utfører kun decoding når noen ser på.~~~~
      KeyFrames: Kun keyframes vil bli decodet, så visnings bildeoppdateringsfrekvens vil være lav, anhengig av keyframe-intervall satt i kameraet.~~~~
      Ingen: Ingen rammer vil bli decodet, live visning og miniatyrbilder er ikke tilgjengelig~~~~
'
  ),
  'FUNCTION_RTSP2WEB_ENABLED' => array(
    'Hjelp' => '
      Forsøk å bruke RTSP2Web strømmeserver for H264/H265 live visning. Eksperimentell, men tillater
      vesentlig bedre ytelse.'
  ),
  'FUNCTION_RTSP2WEB_TYPE' => array(
    'Hjelp' => '
      RTSP2Web støtter MSE (Media Source Extensions), HLS (HTTP Live Streaming), og WebRTC.
      Alle har sine fordeler, men WebRTC er muligens den med best ytelse, men også den som er mest kresen rundt kodeker.'
  ),
  'FUNCTION_JANUS_ENABLED' => array(
    'Hjelp' => '
      Tillater å bruke Janus strømmeserver for H264/H265 live visning. Eksperimentell, men tillater
      vesentlig bedre ytelse.'
  ),
  'FUNCTION_JANUS_AUDIO_ENABLED' => array(
    'Hjelp' => '
      Forsøk å aktivere lyd i Janus strømmen. Har ingen effekt for kamera uten støtte for lyd, 
      men kan forhindre en strøm i fra å spille av dersom kameraet sender et lydformat som ikke er støttet av nettleseren.'
  ),
  'FUNCTION_JANUS_PROFILE_OVERRIDE' => array(
    'Hjelp' => '
      Manuell setting av Profil-ID, som kan tvinge en nettleser til å forsøke å spille en gitt strøm. Forsøk "42e01f"
      for en universell støttet verdi, eller la stå blank for å bruke Profil-IDIen oppgitt av kilden.'
  ),
  'FUNCTION_JANUS_USE_RTSP_RESTREAM' => array(
    'Hjelp' => '
      Hvis kameraet ikke fungerer under Janus uten tillegsinstillinger, aktiver denne for å bruke ZoneMinder
      RTSP restream som Janus kilde.'
  ),
  'FUNCTION_JANUS_RTSP_SESSION_TIMEOUT' => array(
    'Hjelp' => '
    Overstyr eller sett et utløpsintervall i sekunder for RTSP økten. Nyttig dersom du ser mange 401
    Unauthorized responser i Janus logger. Sett til 0 for å bruke intervaller (hvis sendt) fra kilden.'
  ),
  'ImageBufferCount' => array(
    'Hjelp' => '
    Antall råbilder tilgjengelig i /dev/shm. For øyeblikker bør denne settes i området 3-5. Brukt for live visning.'
  ),
  'MaxImageBufferCount' => array(
    'Hjelp' => '
    Maks antall videopakker som holder i pakkekøen.
    Pakkekøen vil normalt håndteres av seg selv, og tar vare på Før Hendelse rammeantall eller alle siden forrige keyframe dersom
    man bruker passthrough modus. Du kan sette en maksimum for å forhindre en monitor fra å benytte for mye RAM, men hendelsene
    har kanskje ikke alle rammene de skal ha dersom keyframe intervallet er høyere enn denne verdien.
    Du vil se feil i feilloggen om dette, så påse at keyframe intervaller er lavt eller at du har nok RAM.
  '
  ),
// Help for soap_wsa issue with chinesse cameras
   'OPTIONS_SOAP_wsa' => array(
    'Hjelp' => '
     Deaktiver dette valget dersom du får feilen ~~~ Couldnt do Renew Error 12 ActionNotSupported
     <env:Text>The device do NOT support this feature</env:Text> ~~~ når du forsøker å aktivere/bruke ONVIF ~~det kan
     hjelpe for å få det til å virke... det er bevist å virke på kinesiske kamera som ikke implementerer ONVIF fullstendig.
    '
   ),

//    'LANG_DEFAULT' => array(
//        'Prompt' => "This is a new prompt for this option",
//        'Help' => "This is some new help for this option which will be displayed in the window when the ? is clicked"
//    ),
);

?>
