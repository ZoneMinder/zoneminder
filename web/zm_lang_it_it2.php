<?php
//
// ZoneMinder web Italian language file, $Date$, $Revision$
// Copyright (C) 2003, 2004, 2005  Philip Coombes
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

// ZoneMinder Italian Translation by Tolmino Muccitelli - Sicurezza Informatica: info@tolmino.it

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
$zmSlang24BitColour          = 'colori a 24 bit';
$zmSlang8BitGrey             = 'scala di grigio a 8 bit';
$zmSlangAction               = 'Action';
$zmSlangActual               = 'Attuale';
$zmSlangAddNewControl        = 'Add New Control';
$zmSlangAddNewMonitor        = 'Aggiungi un nuovo Monitor';
$zmSlangAddNewUser           = 'Aggiungi un nuovo Utente';
$zmSlangAddNewZone           = 'Aggiungi una nuova Zona';
$zmSlangAlarm                = 'Allarme';
$zmSlangAlarmBrFrames        = 'Immagini <br/>in Allarme';
$zmSlangAlarmFrameCount      = 'Alarm Frame Count';
$zmSlangAlarmFrame           = 'immagine Allarme';
$zmSlangAlarmLimits          = 'Limiti Allarme';
$zmSlangAlarmPx              = 'Px Allarme';
$zmSlangAlarmRGBUnset        = 'You must set an alarm RGB colour';
$zmSlangAlert                = 'Attenzione';
$zmSlangAll                  = 'Tutto';
$zmSlangApply                = 'Applica';
$zmSlangApplyingStateChange  = 'Sto applicando il cambiamento di Stato';
$zmSlangArchArchived         = 'Archiviato';
$zmSlangArchive              = 'Archivio';
$zmSlangArchived             = 'Archived';
$zmSlangArchUnarchived       = 'Non archiviato';
$zmSlangArea                 = 'Area';
$zmSlangAreaUnits            = 'Area (px/%)';
$zmSlangAttrAlarmFrames      = 'Immagini in Allarme';
$zmSlangAttrArchiveStatus    = 'Stato Archivio';
$zmSlangAttrAvgScore         = 'Punteggio di Avg.';
$zmSlangAttrCause            = 'Cause';
$zmSlangAttrDate             = 'Data';
$zmSlangAttrDateTime         = 'Data/Ora';
$zmSlangAttrDiskBlocks       = 'Blocchi del disco';
$zmSlangAttrDiskPercent      = 'Percentuale del disco';
$zmSlangAttrDuration         = 'Durata';
$zmSlangAttrFrames           = 'Immagini';
$zmSlangAttrId               = 'Id';
$zmSlangAttrMaxScore         = 'Max. Punteggio';
$zmSlangAttrMonitorId        = 'Identificativo Monitor';
$zmSlangAttrMonitorName      = 'Nome Monitor';
$zmSlangAttrName             = 'Nome';
$zmSlangAttrNotes            = 'Notes';
$zmSlangAttrTime             = 'Tempo';
$zmSlangAttrTotalScore       = 'Punteggio Totale';
$zmSlangAttrWeekday          = 'Giorno della Settimana';
$zmSlangAutoArchiveAbbr      = 'Archive';
$zmSlangAutoArchiveEvents    = 'Archivia automaticamente tutti i matches';
$zmSlangAuto                 = 'Auto';
$zmSlangAutoDeleteAbbr       = 'Delete';
$zmSlangAutoDeleteEvents     = 'Cancella automaticamente tutti i matches';
$zmSlangAutoEmailAbbr        = 'Email';
$zmSlangAutoEmailEvents      = 'Spedisci automaticamente emails con i dettagli di tutti i matches';
$zmSlangAutoExecuteAbbr      = 'Execute';
$zmSlangAutoExecuteEvents    = 'Esegui comando automaticamente su tutti i';
$zmSlangAutoMessageAbbr      = 'Message';
$zmSlangAutoMessageEvents    = 'Dettagli del messaggio automatico di tutti i matches';
$zmSlangAutoStopTimeout      = 'Auto Stop Timeout';
$zmSlangAutoUploadAbbr       = 'Upload';
$zmSlangAutoUploadEvents     = 'Upload automatico di tutti i matches';
$zmSlangAutoVideoAbbr        = 'Video';
$zmSlangAutoVideoEvents      = 'Automatically create video for all matches';
$zmSlangAvgBrScore           = 'Punteggio Avg.<br/>';
$zmSlangBadNameChars         = 'Il nome possono contenere solo caratteri alfanumerici piu - e _';
$zmSlangBadNameChars         = 'Names may only contain alphanumeric characters plus hyphen and underscore';
$zmSlangBandwidth            = 'Banda Passante';
$zmSlangBlobPx               = 'Blob Px';
$zmSlangBlobs                = 'Blobs';
$zmSlangBlobSizes            = 'Grandezza Blob';
$zmSlangBrightness           = 'Luminosita';
$zmSlangBuffers              = 'Buffers';
$zmSlangCanAutoFocus         = 'Can Auto Focus';
$zmSlangCanAutoGain          = 'Can Auto Gain';
$zmSlangCanAutoIris          = 'Can Auto Iris';
$zmSlangCanAutoWhite         = 'Can Auto White Bal.';
$zmSlangCanAutoZoom          = 'Can Auto Zoom';
$zmSlangCancel               = 'Cancella';
$zmSlangCancelForcedAlarm    = 'Cancella&nbsp;Allarme&nbsp;Forzato';
$zmSlangCanFocusAbs          = 'Can Focus Absolute';
$zmSlangCanFocus             = 'Can Focus';
$zmSlangCanFocusCon          = 'Can Focus Continuous';
$zmSlangCanFocusRel          = 'Can Focus Relative';
$zmSlangCanGainAbs           = 'Can Gain Absolute';
$zmSlangCanGain              = 'Can Gain ';
$zmSlangCanGainCon           = 'Can Gain Continuous';
$zmSlangCanGainRel           = 'Can Gain Relative';
$zmSlangCanIrisAbs           = 'Can Iris Absolute';
$zmSlangCanIris              = 'Can Iris';
$zmSlangCanIrisCon           = 'Can Iris Continuous';
$zmSlangCanIrisRel           = 'Can Iris Relative';
$zmSlangCanMoveAbs           = 'Can Move Absolute';
$zmSlangCanMove              = 'Can Move';
$zmSlangCanMoveCon           = 'Can Move Continuous';
$zmSlangCanMoveDiag          = 'Can Move Diagonally';
$zmSlangCanMoveMap           = 'Can Move Mapped';
$zmSlangCanMoveRel           = 'Can Move Relative';
$zmSlangCanPan               = 'Can Pan' ;
$zmSlangCanReset             = 'Can Reset';
$zmSlangCanSetPresets        = 'Can Set Presets';
$zmSlangCanSleep             = 'Can Sleep';
$zmSlangCanTilt              = 'Can Tilt';
$zmSlangCanWake              = 'Can Wake';
$zmSlangCanWhiteAbs          = 'Can White Bal. Absolute';
$zmSlangCanWhiteBal          = 'Can White Bal.';
$zmSlangCanWhite             = 'Can White Balance';
$zmSlangCanWhiteCon          = 'Can White Bal. Continuous';
$zmSlangCanWhiteRel          = 'Can White Bal. Relative';
$zmSlangCanZoomAbs           = 'Can Zoom Absolute';
$zmSlangCanZoom              = 'Can Zoom';
$zmSlangCanZoomCon           = 'Can Zoom Continuous';
$zmSlangCanZoomRel           = 'Can Zoom Relative';
$zmSlangCaptureHeight        = 'Altezza immagine catturata';
$zmSlangCapturePalette       = 'Palette immagine catturata';
$zmSlangCaptureWidth         = 'Larghezza immagine catturata';
$zmSlangCause                = 'Cause';
$zmSlangCheckMethod          = 'Metodo di controllo Alarme';
$zmSlangChooseFilter         = 'Scegli il Filtro';
$zmSlangChoosePreset         = 'Choose Preset';
$zmSlangClose                = 'Chiudi';
$zmSlangColour               = 'Colore';
$zmSlangCommand              = 'Command';
$zmSlangConfig               = 'Configurazione';
$zmSlangConfiguredFor        = 'Configurato per';
$zmSlangConfirmPassword      = 'Conferma Password';
$zmSlangConjAnd              = 'e';
$zmSlangConjOr               = 'o';
$zmSlangConsole              = 'Console';
$zmSlangContactAdmin         = 'Chiama il tecnico per maggiori chiarimenti.';
$zmSlangContinue             = 'Continue';
$zmSlangContrast             = 'Contrasto';
$zmSlangControlAddress       = 'Control Address';
$zmSlangControlCap           = 'Control Capability';
$zmSlangControlCaps          = 'Control Capabilities';
$zmSlangControl              = 'Control';
$zmSlangControlDevice        = 'Control Device';
$zmSlangControllable         = 'Controllable';
$zmSlangControlType          = 'Control Type';
$zmSlangCycle                = 'Cycle';
$zmSlangCycleWatch           = 'Guarda Ciclicamente';
$zmSlangDay                  = 'Giorno';
$zmSlangDefaultRate          = 'Default Rate';
$zmSlangDefaultScale         = 'Default Scale';
$zmSlangDeleteAndNext        = 'Cancella il Prossimo';
$zmSlangDeleteAndPrev        = 'Cancella il Precedente';
$zmSlangDelete               = 'Cancella';
$zmSlangDeleteSavedFilter    = 'Cancella il Filtro salvato';
$zmSlangDescription          = 'Descrizione';
$zmSlangDeviceChannel        = 'Canale del Dispositivo';
$zmSlangDeviceFormat         = 'Formato Video (0=PAL,1=NTSC etc)';
$zmSlangDeviceNumber         = 'Numero Dispositivo (/dev/video?)';
$zmSlangDevicePath           = 'Device Path';
$zmSlangDimensions           = 'Dimensioni';
$zmSlangDisableAlarms        = 'Disable Alarms';
$zmSlangDisk                 = 'Hard Disk';
$zmSlangDonateAlready        = 'No, I\'ve already donated';
$zmSlangDonateEnticement     = 'You\'ve been running ZoneMinder for a while now and hopefully are finding it a useful addition to your home or workplace security. Although ZoneMinder is, and will remain, free and open source, it costs money to develop and support. If you would like to help support future development and new features then please consider donating. Donating is, of course, optional but very much appreciated and you can donate as much or as little as you like.<br><br>If you would like to donate please select the option below or go to http://www.zoneminder.com/donate.html in your browser.<br><br>Thank you for using ZoneMinder and don\'t forget to visit the forums on ZoneMinder.com for support or suggestions about how to make your ZoneMinder experience even better.';
$zmSlangDonate               = 'Please Donate';
$zmSlangDonateRemindDay      = 'Not yet, remind again in 1 day';
$zmSlangDonateRemindHour     = 'Not yet, remind again in 1 hour';
$zmSlangDonateRemindMonth    = 'Not yet, remind again in 1 month';
$zmSlangDonateRemindNever    = 'No, I don\'t want to donate, never remind';
$zmSlangDonateRemindWeek     = 'Not yet, remind again in 1 week';
$zmSlangDonateYes            = 'Yes, I\'d like to donate now';
$zmSlangDownload             = 'Download';
$zmSlangDuration             = 'Durata';
$zmSlangEdit                 = 'Edita';
$zmSlangEmail                = 'Email';
$zmSlangEnableAlarms         = 'Enable Alarms';
$zmSlangEnabled              = 'Abilitato';
$zmSlangEnterNewFilterName   = 'Inserisci il nome del filtro';
$zmSlangErrorBrackets        = 'Errore, controlla di avere in ugual numero i brachets aperti e chiusi';
$zmSlangError                = 'Errore';
$zmSlangErrorValidValue      = 'Errore, controlla di aver inserito valori validi';
$zmSlangEtc                  = 'ecc.';
$zmSlangEvent                = 'Evento';
$zmSlangEventFilter          = 'Filtro Eventi';
$zmSlangEventId              = 'Event Id';
$zmSlangEventName            = 'Event Name';
$zmSlangEventPrefix          = 'Event Prefix';
$zmSlangEvents               = 'Eventi';
$zmSlangExclude              = 'Escludi';
$zmSlangExportDetails        = 'Export Event Details';
$zmSlangExport               = 'Export';
$zmSlangExportFailed         = 'Export Failed';
$zmSlangExportFormat         = 'Export File Format';
$zmSlangExportFormatTar      = 'Tar';
$zmSlangExportFormatZip      = 'Zip';
$zmSlangExportFrames         = 'Export Frame Details';
$zmSlangExportImageFiles     = 'Export Image Files';
$zmSlangExporting            = 'Exporting';
$zmSlangExportMiscFiles      = 'Export Other Files (if present)';
$zmSlangExportOptions        = 'Export Options';
$zmSlangExportVideoFiles     = 'Export Video Files (if present)';
$zmSlangFar                  = 'Far';
$zmSlangFeed                 = 'Feed';
$zmSlangFileColours          = 'File Colours';
$zmSlangFile                 = 'File';
$zmSlangFilePath             = 'File Path';
$zmSlangFilterPx             = 'Filter Px';
$zmSlangFilters              = 'Filters';
$zmSlangFilterUnset          = 'You must specify a filter width and height';
$zmSlangFirst                = 'Primo';
$zmSlangFlippedHori          = 'Flipped Horizontally';
$zmSlangFlippedVert          = 'Flipped Vertically';
$zmSlangFocus                = 'Focus';
$zmSlangForceAlarm           = 'Forza&nbsp;Allarme';
$zmSlangFormat               = 'Format';
$zmSlangFPS                  = 'fps';
$zmSlangFPSReportInterval    = 'Intervallo di riporto FPS';
$zmSlangFrameId              = 'Id immagine';
$zmSlangFrame                = 'Immagine';
$zmSlangFrameRate            = 'Frame Rate';
$zmSlangFrames               = 'Immagini';
$zmSlangFrameSkip            = 'Immagini saltate';
$zmSlangFTP                  = 'FTP';
$zmSlangFunc                 = 'Funziona';
$zmSlangFunction             = 'Funzione';
$zmSlangGain                 = 'Gain';
$zmSlangGeneral              = 'General';
$zmSlangGenerateVideo        = 'Genera il Video';
$zmSlangGeneratingVideo      = 'Sto Generando il Video';
$zmSlangGoToZoneMinder       = 'Vai a: ZoneMinder.com';
$zmSlangGrey                 = 'Grigio';
$zmSlangGroups               = 'Groups';
$zmSlangHasFocusSpeed        = 'Has Focus Speed';
$zmSlangHasGainSpeed         = 'Has Gain Speed';
$zmSlangHasHomePreset        = 'Has Home Preset';
$zmSlangHasIrisSpeed         = 'Has Iris Speed';
$zmSlangHasPanSpeed          = 'Has Pan Speed';
$zmSlangHasPresets           = 'Has Presets';
$zmSlangHasTiltSpeed         = 'Has Tilt Speed';
$zmSlangHasTurboPan          = 'Has Turbo Pan';
$zmSlangHasTurboTilt         = 'Has Turbo Tilt';
$zmSlangHasWhiteSpeed        = 'Has White Bal. Speed';
$zmSlangHasZoomSpeed         = 'Has Zoom Speed';
$zmSlangHigh                 = 'Alta';
$zmSlangHighBW               = 'High&nbsp;B/W';
$zmSlangHome                 = 'Home';
$zmSlangHour                 = 'Ora';
$zmSlangHue                  = 'Hue';
$zmSlangId                   = 'Id';
$zmSlangIdle                 = 'Idle';
$zmSlangIgnore               = 'Ignora';
$zmSlangImageBufferSize      = 'Grandezza Buffer Immagine (frames)';
$zmSlangImage                = 'Immagine';
$zmSlangInclude              = 'Includi';
$zmSlangIn                   = 'In';
$zmSlangInverted             = 'Invertito';
$zmSlangIris                 = 'Iris';
$zmSlangLanguage             = 'Linguaggio';
$zmSlangLast                 = 'Ultimo';
$zmSlangLimitResultsPost     = 'risultati;'; // This is used at the end of the phrase 'Limit to first N results only'
$zmSlangLimitResultsPre      = 'Limitato ai primi'; // This is used at the beginning of the phrase 'Limit to first N results only'
$zmSlangList                 = 'List';
$zmSlangLoad                 = 'Carica';
$zmSlangLocal                = 'Locale';
$zmSlangLoggedInAs           = 'Nome utente:';
$zmSlangLoggingIn            = 'Logging In';
$zmSlangLogin                = 'Login';
$zmSlangLogout               = 'Logout';
$zmSlangLow                  = 'Bassa';
$zmSlangLowBW                = 'Low&nbsp;B/W';
$zmSlangMain                 = 'Main';
$zmSlangMan                  = 'Man';
$zmSlangManual               = 'Manual';
$zmSlangMark                 = 'Contrassegna';
$zmSlangMaxBandwidth         = 'Max Bandwidth';
$zmSlangMaxBrScore           = 'Punteggio<br/>Massimo';
$zmSlangMaxFocusRange        = 'Max Focus Range';
$zmSlangMaxFocusSpeed        = 'Max Focus Speed';
$zmSlangMaxFocusStep         = 'Max Focus Step';
$zmSlangMaxGainRange         = 'Max Gain Range';
$zmSlangMaxGainSpeed         = 'Max Gain Speed';
$zmSlangMaxGainStep          = 'Max Gain Step';
$zmSlangMaximumFPS           = 'Massimi FPS';
$zmSlangMaxIrisRange         = 'Max Iris Range';
$zmSlangMaxIrisSpeed         = 'Max Iris Speed';
$zmSlangMaxIrisStep          = 'Max Iris Step';
$zmSlangMax                  = 'Massima';
$zmSlangMaxPanRange          = 'Max Pan Range';
$zmSlangMaxPanSpeed          = 'Max Pan Speed';
$zmSlangMaxPanStep           = 'Max Pan Step';
$zmSlangMaxTiltRange         = 'Max Tilt Range';
$zmSlangMaxTiltSpeed         = 'Max Tilt Speed';
$zmSlangMaxTiltStep          = 'Max Tilt Step';
$zmSlangMaxWhiteRange        = 'Max White Bal. Range';
$zmSlangMaxWhiteSpeed        = 'Max White Bal. Speed';
$zmSlangMaxWhiteStep         = 'Max White Bal. Step';
$zmSlangMaxZoomRange         = 'Max Zoom Range';
$zmSlangMaxZoomSpeed         = 'Max Zoom Speed';
$zmSlangMaxZoomStep          = 'Max Zoom Step';
$zmSlangMediumBW             = 'Medium&nbsp;B/W';
$zmSlangMedium               = 'Media';
$zmSlangMinAlarmAreaLtMax    = 'Minimum alarm area should be less than maximum';
$zmSlangMinAlarmAreaUnset    = 'You must specify the minimum alarm pixel count';
$zmSlangMinBlobAreaLtMax     = 'Area di blob minima che deve essere minore dell area massima di blob';
$zmSlangMinBlobAreaUnset     = 'You must specify the minimum blob pixel count';
$zmSlangMinBlobLtMinFilter   = 'Minimum blob area should be less than or equal to minimum filter area';
$zmSlangMinBlobsLtMax        = 'Numero minimo di blobs che devono essere minori al numero massimo di blobs';
$zmSlangMinBlobsUnset        = 'You must specify the minimum blob count';
$zmSlangMinFilterAreaLtMax   = 'Minimum filter area should be less than maximum';
$zmSlangMinFilterAreaUnset   = 'You must specify the minimum filter pixel count';
$zmSlangMinFilterLtMinAlarm  = 'Minimum filter area should be less than or equal to minimum alarm area';
$zmSlangMinFocusRange        = 'Min Focus Range';
$zmSlangMinFocusSpeed        = 'Min Focus Speed';
$zmSlangMinFocusStep         = 'Min Focus Step';
$zmSlangMinGainRange         = 'Min Gain Range';
$zmSlangMinGainSpeed         = 'Min Gain Speed';
$zmSlangMinGainStep          = 'Min Gain Step';
$zmSlangMinIrisRange         = 'Min Iris Range';
$zmSlangMinIrisSpeed         = 'Min Iris Speed';
$zmSlangMinIrisStep          = 'Min Iris Step';
$zmSlangMinPanRange          = 'Min Pan Range';
$zmSlangMinPanSpeed          = 'Min Pan Speed';
$zmSlangMinPanStep           = 'Min Pan Step';
$zmSlangMinPixelThresLtMax   = 'Soglia minima di pixel che devono essere minori della soglia massima di pixel';
$zmSlangMinPixelThresUnset   = 'You must specify a minimum pixel threshold';
$zmSlangMinTiltRange         = 'Min Tilt Range';
$zmSlangMinTiltSpeed         = 'Min Tilt Speed';
$zmSlangMinTiltStep          = 'Min Tilt Step';
$zmSlangMinWhiteRange        = 'Min White Bal. Range';
$zmSlangMinWhiteSpeed        = 'Min White Bal. Speed';
$zmSlangMinWhiteStep         = 'Min White Bal. Step';
$zmSlangMinZoomRange         = 'Min Zoom Range';
$zmSlangMinZoomSpeed         = 'Min Zoom Speed';
$zmSlangMinZoomStep          = 'Min Zoom Step';
$zmSlangMisc                 = 'Misc';
$zmSlangMonitorIds           = 'Monitor&nbsp;Ids';
$zmSlangMonitor              = 'Monitor';
$zmSlangMonitorPresetIntro   = 'Select an appropriate preset from the list below.<br><br>Please note that this may overwrite any values you already have configured for this monitor.<br><br>';
$zmSlangMonitorPreset        = 'Monitor Preset';
$zmSlangMonitors             = 'Monitors';
$zmSlangMontage              = 'Montaggio';
$zmSlangMonth                = 'Mese';
$zmSlangMove                 = 'Move';
$zmSlangMustBeGe             = 'deve essere maggiore o uguale di';
$zmSlangMustBeLe             = 'deve essere minore o uguale di';
$zmSlangMustConfirmPassword  = 'Devi confermare la password';
$zmSlangMustSupplyPassword   = 'Devi inserire la password';
$zmSlangMustSupplyUsername   = 'Devi inserire il nome-Utente';
$zmSlangName                 = 'Nome';
$zmSlangNear                 = 'Near';
$zmSlangNetwork              = 'Network';
$zmSlangNewGroup             = 'New Group';
$zmSlangNew                  = 'Nuovo';
$zmSlangNewPassword          = 'Nuova Password';
$zmSlangNewState             = 'Nuovo Stato';
$zmSlangNewUser              = 'Nuovo Utente';
$zmSlangNext                 = 'Prossimo';
$zmSlangNoFramesRecorded     = 'Non ci sono frames registrati per questo evento';
$zmSlangNoGroup              = 'No Group';
$zmSlangNoneAvailable        = 'Non trovato';
$zmSlangNone                 = 'Niente';
$zmSlangNo                   = 'No';
$zmSlangNormal               = 'Normale';
$zmSlangNoSavedFilters       = 'No_Filtri_Salvati';
$zmSlangNoStatisticsRecorded = 'Non ci sono statistiche registrate per questo event/frame';
$zmSlangNotes                = 'Notes';
$zmSlangNumPresets           = 'Num Presets';
$zmSlangOpen                 = 'Open';
$zmSlangOpEq                 = 'uguale a';
$zmSlangOpGtEq               = 'maggiore o uguale di';
$zmSlangOpGt                 = 'maggiore di';
$zmSlangOpIn                 = 'settato';
$zmSlangOpLtEq               = 'minore o uguale di';
$zmSlangOpLt                 = 'minore di';
$zmSlangOpMatches            = 'matches';
$zmSlangOpNe                 = 'non uguale a';
$zmSlangOpNotIn              = 'non settato';
$zmSlangOpNotMatches         = 'non corrisponde';
$zmSlangOptionHelp           = 'Optioni di aiuto';
$zmSlangOptionRestartWarning = 'Questi cambiamenti non avranno effetto\nfintanto che il sistema funziona. Quando avrai\nfinito di apportare modifiche, ricordati di\nriavviare ZoneMinder.';
$zmSlangOptions              = 'Optioni';
$zmSlangOrder                = 'Order';
$zmSlangOrEnterNewName       = 'o inserisci il nuovo nome';
$zmSlangOrientation          = 'Orientazione';
$zmSlangOut                  = 'Out';
$zmSlangOverwriteExisting    = 'Sovrascrivi quello esistente';
$zmSlangPaged                = 'Impaginato';
$zmSlangPanLeft              = 'Pan Left';
$zmSlangPan                  = 'Pan';
$zmSlangPanRight             = 'Pan Right';
$zmSlangPanTilt              = 'Pan/Tilt';
$zmSlangParameter            = 'Parametri';
$zmSlangPassword             = 'Password';
$zmSlangPasswordsDifferent   = 'Le password inserite sono differenti';
$zmSlangPaths                = 'Paths';
$zmSlangPhoneBW              = 'Phone&nbsp;B/W';
$zmSlangPhone                = 'Phone';
$zmSlangPixels               = 'pixels';
$zmSlangPlayAll              = 'Play All';
$zmSlangPleaseWait           = 'ATTENDI';
$zmSlangPoint                = 'Point';
$zmSlangPostEventImageBuffer = 'Buffer delle immagini dopo gli Eventi';
$zmSlangPreEventImageBuffer  = 'Buffer delle immagini prima degli Eventi';
$zmSlangPreset               = 'Preset';
$zmSlangPresets              = 'Presets';
$zmSlangPrev                 = 'Precedente';
$zmSlangRate                 = 'Rate';
$zmSlangReal                 = 'Reale';
$zmSlangRecord               = 'Record';
$zmSlangRefImageBlendPct     = 'Blend dell Immagine di riferimento %ge';
$zmSlangRefresh              = 'Aggiorna';
$zmSlangRemoteHostName       = 'Nome dell Host Remoto';
$zmSlangRemoteHostPath       = 'Path dell Host Remoto';
$zmSlangRemoteHostPort       = 'Porta dell Host Remoto';
$zmSlangRemoteImageColours   = 'Colori dell Immagine Remota';
$zmSlangRemote               = 'Remoto';
$zmSlangRename               = 'Rinomina';
$zmSlangReplay               = 'Replay';
$zmSlangResetEventCounts     = 'Resetta il contatore Eventi';
$zmSlangReset                = 'Reset';
$zmSlangRestarting           = 'Sto Ripartendo';
$zmSlangRestart              = 'Riparti';
$zmSlangRestrictedCameraIds  = 'Restricted Camera Ids';
$zmSlangReturnDelay          = 'Return Delay';
$zmSlangReturnLocation       = 'Return Location';
$zmSlangRotateLeft           = 'Ruota a sinistra';
$zmSlangRotateRight          = 'Ruota a destra';
$zmSlangRunMode              = 'Modo di funzionamento';
$zmSlangRunning              = 'Sistema ATTIVO';
$zmSlangRunState             = 'Stato di Funzionamento';
$zmSlangSaveAs               = 'Salva come';
$zmSlangSaveFilter           = 'Salva il Filtro';
$zmSlangSave                 = 'Salva';
$zmSlangScale                = 'Scala';
$zmSlangScore                = 'Punteggio';
$zmSlangSecs                 = 'Secondi';
$zmSlangSectionlength        = 'Lunghezza della Sezione';
$zmSlangSelect               = 'Select';
$zmSlangSelfIntersecting     = 'Polygon edges must not intersect';
$zmSlangSetLearnPrefs        = 'Seleziona le preferenze di autoapprendimento'; // This can be ignored for now
$zmSlangSetNewBandwidth      = 'Seleziona la nuova BandaPassante';
$zmSlangSetPreset            = 'Set Preset';
$zmSlangSet                  = 'Set';
$zmSlangSettings             = 'Settings';
$zmSlangShowFilterWindow     = 'MostraFinestraFiltri';
$zmSlangShowTimeline         = 'Show Timeline';
$zmSlangSize                 = 'Size';
$zmSlangSleep                = 'Sleep';
$zmSlangSortAsc              = 'Asc';
$zmSlangSortBy               = 'Ordina per';
$zmSlangSortDesc             = 'Desc';
$zmSlangSource               = 'Ingresso';
$zmSlangSourceType           = 'Tipo di ingresso';
$zmSlangSpeedHigh            = 'High Speed';
$zmSlangSpeedLow             = 'Low Speed';
$zmSlangSpeedMedium          = 'Medium Speed';
$zmSlangSpeed                = 'Speed';
$zmSlangSpeedTurbo           = 'Turbo Speed';
$zmSlangStart                = 'Start';
$zmSlangState                = 'Stato';
$zmSlangStats                = 'Stati';
$zmSlangStatus               = 'Stato';
$zmSlangStepLarge            = 'Large Step';
$zmSlangStepMedium           = 'Medium Step';
$zmSlangStepNone             = 'No Step';
$zmSlangStepSmall            = 'Small Step';
$zmSlangStep                 = 'Step';
$zmSlangStills               = 'Fermo-immagine';
$zmSlangStopped              = 'Sistema Stoppato';
$zmSlangStop                 = 'Stop';
$zmSlangStream               = 'Stream';
$zmSlangSubmit               = 'Submit';
$zmSlangSystem               = 'Sistema';
$zmSlangTele                 = 'Tele';
$zmSlangThumbnail            = 'Thumbnail';
$zmSlangTilt                 = 'Tilt';
$zmSlangTimeDelta            = 'Tempo di Delta';
$zmSlangTimeline             = 'Timeline';
$zmSlangTime                 = 'Ora';
$zmSlangTimestampLabelFormat = 'Formato etichetta Timestamp';
$zmSlangTimestampLabelX      = 'Etichetta Timestamp X';
$zmSlangTimestampLabelY      = 'Etichetta Timestamp Y';
$zmSlangTimestamp            = 'Timestamp';
$zmSlangTimeStamp            = 'Time Stamp';
$zmSlangToday                = 'Today';
$zmSlangTools                = 'Tools';
$zmSlangTotalBrScore         = 'Punteggio<br/>Totale';
$zmSlangTrackDelay           = 'Track Delay';
$zmSlangTrackMotion          = 'Track Motion';
$zmSlangTriggers             = 'Triggers';
$zmSlangTurboPanSpeed        = 'Turbo Pan Speed';
$zmSlangTurboTiltSpeed       = 'Turbo Tilt Speed';
$zmSlangType                 = 'Tipo';
$zmSlangUnarchive            = 'Non_archiviato';
$zmSlangUnits                = 'Unità';
$zmSlangUnknown              = 'Sconosciuto';
$zmSlangUpdateAvailable      = 'Una nuova versione di ZoneMinder è disponibile.';
$zmSlangUpdateNotNecessary   = 'Non è necessario aggiornare Zoneminder.';
$zmSlangUpdate               = 'Update';
$zmSlangUseFilterExprsPost   = '&nbsp;filtri&nbsp;espressioni'; // This is used at the end of the phrase 'use N filter expressions'
$zmSlangUseFilterExprsPre    = 'Usa&nbsp;'; // This is used at the beginning of the phrase 'use N filter expressions'
$zmSlangUseFilter            = 'Usa Filtro';
$zmSlangUsername             = 'Username';
$zmSlangUsers                = 'Utenti';
$zmSlangUser                 = 'Utente';
$zmSlangValue                = 'Valore';
$zmSlangVersionIgnore        = 'Ignora questa versione';
$zmSlangVersionRemindDay     = 'Ricordamelo tra 1 gg';
$zmSlangVersionRemindHour    = 'Ricordamelo tra 1 ora';
$zmSlangVersionRemindNever   = 'Non avvisarmi più per nuove versioni';
$zmSlangVersionRemindWeek    = 'Ricordamelo tra 1 settimana';
$zmSlangVersion              = 'Versione';
$zmSlangVideoFormat          = 'Video Format';
$zmSlangVideoGenFailed       = 'Creazione Video Fallita!';
$zmSlangVideoGenFiles        = 'Existing Video Files';
$zmSlangVideoGenNoFiles      = 'No Video Files Found';
$zmSlangVideoGenParms        = 'Parametri per la Creazione del Video';
$zmSlangVideoGenSucceeded    = 'Video Generation Succeeded!';
$zmSlangVideoSize            = 'Dimensioni del Video';
$zmSlangVideo                = 'Video';
$zmSlangViewAll              = 'Vedi tutto';
$zmSlangViewEvent            = 'View Event';
$zmSlangViewPaged            = 'Vedi impaginato';
$zmSlangView                 = 'Vedi';
$zmSlangWake                 = 'Wake';
$zmSlangWarmupFrames         = 'Frames di attenzione';
$zmSlangWatch                = 'Guarda';
$zmSlangWebColour            = 'Web Colour';
$zmSlangWeb                  = 'Web';
$zmSlangWeek                 = 'Settimana';
$zmSlangWhiteBalance         = 'White Balance';
$zmSlangWhite                = 'White';
$zmSlangWide                 = 'Wide';
$zmSlangX10ActivationString  = 'Stringa di Attivazione X10';
$zmSlangX10InputAlarmString  = 'Stringa di ingresso Allarme X10';
$zmSlangX10OutputAlarmString = 'Stringa di uscita Allarme X10';
$zmSlangX10                  = 'X10';
$zmSlangX                    = 'X';
$zmSlangYes                  = 'SI';
$zmSlangYouNoPerms           = 'Non hai i permessi per accedere a questa risorsa.';
$zmSlangY                    = 'Y';
$zmSlangZoneAlarmColour      = 'Colore Allarme (Red/Green/Blue)';
$zmSlangZoneArea             = 'Zone Area';
$zmSlangZoneFilterSize       = 'Filter Width/Height (pixels)';
$zmSlangZoneMinMaxAlarmArea  = 'Min/Max Alarmed Area';
$zmSlangZoneMinMaxBlobArea   = 'Min/Max Blob Area';
$zmSlangZoneMinMaxBlobs      = 'Min/Max Blobs';
$zmSlangZoneMinMaxFiltArea   = 'Min/Max Filtered Area';
$zmSlangZoneMinMaxPixelThres = 'Min/Max Pixel Threshold (0-255)';
$zmSlangZones                = 'Zone';
$zmSlangZone                 = 'Zona';
$zmSlangZoomIn               = 'Zoom In';
$zmSlangZoomOut              = 'Zoom Out';
$zmSlangZoom                 = 'Zoom';


// Complex replacements with formatting and/or placements, must be passed through sprintf
$zmClangCurrentLogin         = 'Utente loggato è \'%1$s\'';
$zmClangEventCount           = '%1$s %2$s'; // For example '37 Events' (from Vlang below)
$zmClangLastEvents           = 'Ultimi %1$s %2$s'; // For example 'Last 37 Events' (from Vlang below)
$zmClangLatestRelease        = 'L ultima versione è v%1$s, tu hai v%2$s.';
$zmClangMonitorCount         = '%1$s %2$s'; // For example '4 Monitors' (from Vlang below)
$zmClangMonitorFunction      = 'Monitor %1$s Attivo';
$zmClangRunningRecentVer     = 'Stai lavorando con la più recente versione di ZoneMinder, v%s.';

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
$zmVlangEvent                = array( 0=>'Eventi', 1=>'Evento', 2=>'Eventi' );
$zmVlangMonitor              = array( 0=>'Monitors', 1=>'Monitor', 2=>'Monitors' );

// You will need to choose or write a function that can correlate the plurality string arrays
// with variable counts. This is used to conjugate the Vlang arrays above with a number passed
// in to generate the correct noun form.
//
// In languages such as English this is fairly simple
// Note this still has to be used with printf etc to get the right formating
function zmVlang( $lang_var_array, $count )
{
	krsort( $lang_var_array );
	foreach ( $lang_var_array as $key=>$value )
	{
		if ( abs($count) >= $key )
		{
			return( $value );
		}
	}
	die( 'Errore, sono incapace di correlare le stringhe del file-linguaggio' );
}

// This is an version that could be used in the Russian example above
// The rules are that the first word form is used if the count ends in
// 0, 5-9 or 11-19. The second form is used then the count ends in 1
// (not including 11 as above) and the third form is used when the
// count ends in 2-4, again excluding any values ending in 12-14.
//
// function zmVlang( $lang_var_array, $count )
// {
// 	$secondlastdigit = substr( $count, -2, 1 );
// 	$lastdigit = substr( $count, -1, 1 );
// 	// or
// 	// $secondlastdigit = ($count/10)%10;
// 	// $lastdigit = $count%10;
// 
// 	// Get rid of the special cases first, the teens
// 	if ( $secondlastdigit == 1 && $lastdigit != 0 )
// 	{
// 		return( $lang_var_array[1] );
// 	}
// 	switch ( $lastdigit )
// 	{
// 		case 0 :
// 		case 5 :
// 		case 6 :
// 		case 7 :
// 		case 8 :
// 		case 9 :
// 		{
// 			return( $lang_var_array[1] );
// 			break;
// 		}
// 		case 1 :
// 		{
// 			return( $lang_var_array[2] );
// 			break;
// 		}
// 		case 2 :
// 		case 3 :
// 		case 4 :
// 		{
// 			return( $lang_var_array[3] );
// 			break;
// 		}
// 	}
// 	die( 'Error, unable to correlate variable language string' );
// }

// This is an example of how the function is used in the code which you can uncomment and 
// use to test your custom function.
//$monitors = array();
//$monitors[] = 1; // Choose any number
//echo sprintf( $zmClangMonitorCount, count($monitors), zmVlang( $zmVlangMonitor, count($monitors) ) );

// In this section you can override the default prompt and help texts for the options area
// These overrides are in the form of $zmOlangPrompt<option> and $zmOlangHelp<option>
// where <option> represents the option name minus the initial ZM_
// So for example, to override the help text for ZM_LANG_DEFAULT do
//$zmOlangPromptLANG_DEFAULT = "This is a new prompt for this option";
//$zmOlangHelpLANG_DEFAULT = "This is some new help for this option which will be displayed in the popup window when the ? is clicked";
//

?>
