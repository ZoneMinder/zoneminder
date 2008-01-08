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

// ZoneMinder IT modified by Nicola Murino (23/09/2007) 
// ZoneMinder IT modified by Alessio Chemeri (18/01/2006) (based on the translation done by
// Davide Morelli  
// Tolmino Muccitelli - Sicurezza Informatica: info@tolmino.it
// Nicola Murino - IT Consultant: nicola.murino@gmail.com

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
//header( "Content-Type: text/html; charset=iso-8859-1" );

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
//setlocale( LC_ALL, 'it_IT.UTF-8' ); Date and time formatting 4.3.0 and after

// Simple String Replacements
$zmSlang24BitColour          = 'colori a 24 bit';
$zmSlang8BitGrey             = '8 bit scala di grigio';
$zmSlangAction               = 'Azione';
$zmSlangActual               = 'Attuale';
$zmSlangAddNewControl        = 'Aggiungi nuovo Controllo';
$zmSlangAddNewMonitor        = 'Aggiungi nuovo Monitor';
$zmSlangAddNewUser           = 'Aggiungi nuovo Utente';
$zmSlangAddNewZone           = 'Aggiungi nuova Zona';
$zmSlangAlarm                = 'Allarme';
$zmSlangAlarmBrFrames        = 'Immagini<br/>Allarme';
$zmSlangAlarmFrameCount      = 'Allarme Conta frame';
$zmSlangAlarmFrame           = 'Immagine Allarme';
$zmSlangAlarmLimits          = 'Limiti Allarme';
$zmSlangAlarmMaximumFPS      = 'FPS massimi durante l\'allarme';
$zmSlangAlarmPx              = 'Pixel Allarme';
$zmSlangAlarmRGBUnset        = 'Devi settare un colore RGB di allarme';
$zmSlangAlert                = 'Attenzione';
$zmSlangAll                  = 'Tutto';
$zmSlangApply                = 'Applica';
$zmSlangApplyingStateChange  = 'Sto applicando le modifiche';
$zmSlangArchArchived         = 'Archiviato';
$zmSlangArchive              = 'Archivio';
$zmSlangArchived             = 'Archiviato';
$zmSlangArchUnarchived       = 'Non archiviare';
$zmSlangArea                 = 'Area';
$zmSlangAreaUnits            = 'Area (px/%)';
$zmSlangAttrAlarmFrames      = 'Immagini in Allarme';
$zmSlangAttrArchiveStatus    = 'Stato Archivio';
$zmSlangAttrAvgScore         = 'Punteggio medio';
$zmSlangAttrCause            = 'Causa';
$zmSlangAttrDate             = 'Data';
$zmSlangAttrDateTime         = 'Data/Ora';
$zmSlangAttrDiskBlocks       = 'Blocchi del Disco';
$zmSlangAttrDiskPercent      = 'Percentuale del Disco';
$zmSlangAttrDuration         = 'Durata';
$zmSlangAttrFrames           = 'Immagini';
$zmSlangAttrId               = 'Id';
$zmSlangAttrMaxScore         = 'Punteggio massimo';
$zmSlangAttrMonitorId        = 'Id Monitor';
$zmSlangAttrMonitorName      = 'Nome Monitor';
$zmSlangAttrName             = 'Nome';
$zmSlangAttrNotes            = 'Note';
$zmSlangAttrSystemLoad       = 'Carico del Sistema';
$zmSlangAttrSystemLoad       = 'System Load';
$zmSlangAttrTime             = 'Ora';
$zmSlangAttrTotalScore       = 'Punteggio totale';
$zmSlangAttrWeekday          = 'Giorno della settimana';
$zmSlangAuto                 = 'Auto';
$zmSlangAutoStopTimeout      = 'Auto Stop Timeout';
$zmSlangAvgBrScore           = 'Punteggio<br/>medio';
$zmSlangBackground           = 'Background';
$zmSlangBackgroundFilter     = 'Esegui filtro in background';
$zmSlangBadAlarmFrameCount   = 'Il numero di frame di un allarme deve essere un numero intero superiore a uno';
$zmSlangBadAlarmMaxFPS       = 'Il numero massimo di FPS dell\'allarme deve essere un numero intero positivo o un valore in virgola mobile';
$zmSlangBadChannel           = 'Il canale deve essere settato con un numero intero uguale o maggiore di zero';
$zmSlangBadDevice            = 'Il dispositivo deve essere impostato con un valore valido';
$zmSlangBadFormat            = 'Il formato deve essere impostato con un numero intero come 0 o maggiore';
$zmSlangBadFPSReportInterval = 'L\'intervallo di FPS per i report deve essere un numero intero superiore a 100';
$zmSlangBadFrameSkip         = 'Il numero di Frame da scartare deve essere un intero uguale a 0 o superiore';
$zmSlangBadHeight            = 'L\'altezza deve essere impostata con un valore valido';
$zmSlangBadHost              = 'L\'host deve essere impostato con un indirizzo ip valido o con un hostname, non includendo http://';
$zmSlangBadImageBufferCount  = 'La dimensione del buffer dell\'immagine deve essere impostata con un numero intero pari a 10 o maggiore';
$zmSlangBadLabelX            = 'L\'etichetta della coordinata X deve essere un numero intero pari a 0 o maggiore';
$zmSlangBadLabelY            = 'L\'etichetta della coordinata Y deve essere un numero intero pari a 0 o maggiore';
$zmSlangBadMaxFPS            = 'I frame per secondo (FPS) massimi devono essere un numero intero positivo o un valore in virgola mobile';
$zmSlangBadNameChars         = 'I nomi possono contenere solo caratteri alfanumerici pi&ugrave; i caratteri - e _';
$zmSlangBadPath              = 'Il percorso deve essere impostato con un valore valido';
$zmSlangBadPort              = 'La porta deve essere settata con un valore valido';
$zmSlangBadPostEventCount    = 'Il buffer d\'immagine successivo ad un evento deve essere un numero maggiore o uguale a zero';
$zmSlangBadPreEventCount     = 'Il buffer d\'immagine antecedente ad un evento deve essere minimo 0 e comunque minore della dimensione del buffer d\'immagine';
$zmSlangBadRefBlendPerc      = 'La percentuale di miscela di riferimento deve essere un intero positivo';
$zmSlangBadSectionLength     = 'La lunghezza della sezione deve essere un numero intero pari a 30 o maggiore';
$zmSlangBadSignalCheckColour = 'Il colore del controllo di segnale deve essere una stringa RGB valida';
$zmSlangBadSignalCheckColour = 'Signal check colour must be a valid RGB colour string';
$zmSlangBadStreamReplayBuffer= 'Stream replay buffer must be an integer of zero or more';
$zmSlangBadWarmupCount       = 'Il numero di frame di allarme deve essere un numero intero maggiore o uguale a zero';
$zmSlangBadWebColour         = 'L\'identificativo del colore deve essere una stringa valida'; 
$zmSlangBadWidth             = 'La larghezza deve essere impostata con un valore valido';
$zmSlangBandwidth            = 'Banda';
$zmSlangBlobPx               = 'Blob Px';
$zmSlangBlobs                = 'Blobs';
$zmSlangBlobSizes            = 'Dimensioni Blob';
$zmSlangBrightness           = 'Luminosit&agrave;';
$zmSlangBuffers              = 'Buffers';
$zmSlangCanAutoFocus         = 'Puo\' Auto Focus';
$zmSlangCanAutoGain          = 'Puo\' Auto Gains';
$zmSlangCanAutoIris          = 'Puo\' Auto Iris';
$zmSlangCanAutoWhite         = 'Puo\' Auto bil bianco';
$zmSlangCanAutoZoom          = 'Puo\' Auto Zoom';
$zmSlangCancel               = 'Annulla';
$zmSlangCancelForcedAlarm    = 'Annulla Allarme Forzato';
$zmSlangCanFocusAbs          = 'Puo\' Fuoco Assoluto';
$zmSlangCanFocusCon          = 'Puo\' Fuoco Continuo ';
$zmSlangCanFocus             = 'Puo\' Fuoco';
$zmSlangCanFocusRel          = 'Puo\' Fuoco Relativo';
$zmSlangCanGainAbs           = 'Puo\' Gain Assoluto';
$zmSlangCanGainCon           = 'Puo\' Gain Continuo  ';
$zmSlangCanGain              = 'Puo\' Gain ';
$zmSlangCanGainRel           = 'Puo\' Gain Relativo';
$zmSlangCanIrisAbs           = 'Puo\' Iris Assoluto';
$zmSlangCanIrisCon           = 'Puo\' Iris Continuo  ';
$zmSlangCanIris              = 'Puo\' Iris';
$zmSlangCanIrisRel           = 'Puo\' Iris Relativo';
$zmSlangCanMoveAbs           = 'Puo\' Mov. Assoluto';
$zmSlangCanMoveCon           = 'Puo\' Mov. Continuo  ';
$zmSlangCanMoveDiag          = 'Puo\' Mov. Diagonale ';
$zmSlangCanMoveMap           = 'Puo\' Mov Mappato';
$zmSlangCanMove              = 'Puo\' Mov.';
$zmSlangCanMoveRel           = 'Puo\' Mov. Relativo';
$zmSlangCanPan               = 'Puo\' Pan' ;
$zmSlangCanReset             = 'Puo\' Reset';
$zmSlangCanSetPresets        = 'Puo\' impostare preset';
$zmSlangCanSleep             = 'Puo\' andare in sleep';
$zmSlangCanTilt              = 'Puo\' Tilt';
$zmSlangCanWake              = 'Puo\' essere riattivato';
$zmSlangCanWhiteAbs          = 'Puo\' bilanciare il bianco assoluto';
$zmSlangCanWhiteBal          = 'Puo\' bilanciare il bianco';
$zmSlangCanWhiteCon          = 'Puo\' bilanciare il bianco Continuo';
$zmSlangCanWhite             = 'Puo\' bilanciare il bianco';
$zmSlangCanWhiteRel          = 'Puo\' bilanciare il bianco Relativo';
$zmSlangCanZoomAbs           = 'Puo\' Zoom Assoluto';
$zmSlangCanZoomCon           = 'Puo\' Zoom Continuo';
$zmSlangCanZoom              = 'Puo\' Zoom';
$zmSlangCanZoomRel           = 'Puo\' Zoom Relativo';
$zmSlangCaptureHeight        = 'Altezza img catturata';
$zmSlangCapturePalette       = 'Paletta img Catturata';
$zmSlangCaptureWidth         = 'Larghezza img Catturata';
$zmSlangCause                = 'Causa';
$zmSlangCheckMethod          = 'Metodo di Controllo Allarme';
$zmSlangChooseFilter         = 'Scegli Filtro';
$zmSlangChoosePreset         = 'Scegli Preset';
$zmSlangClose                = 'Chiudi';
$zmSlangColour               = 'Colori';
$zmSlangCommand              = 'Comando';
$zmSlangConfig               = 'Configura';
$zmSlangConfiguredFor        = 'Configurato per';
$zmSlangConfirmDeleteEvents  = 'Sei sicuro di voler cancellare gli eventi selezionati';
$zmSlangConfirmPassword      = 'Conferma Password';
$zmSlangConjAnd              = 'e';
$zmSlangConjOr               = 'o';
$zmSlangConsole              = 'Console';
$zmSlangContactAdmin         = 'Contatta il tuo amministratore per dettagli.';
$zmSlangContinue             = 'Continuo';
$zmSlangContrast             = 'Contrasto';
$zmSlangControlAddress       = 'Indirizzo di controllo';
$zmSlangControlCap           = 'Capacita\' di controllo';
$zmSlangControlCaps          = 'Capacita\' di controllo';
$zmSlangControl              = 'Controllo';
$zmSlangControlDevice        = 'Dispositivo di controllo';
$zmSlangControllable         = 'Controllabile';
$zmSlangControlType          = 'Tipo Controllo';
$zmSlangCycle                = 'Cicla';
$zmSlangCycleWatch           = 'Vista Ciclica';
$zmSlangDay                  = 'Giorno';
$zmSlangDebug                = 'Debug';
$zmSlangDefaultRate          = 'Default Rate';
$zmSlangDefaultScale         = 'Scala di default';
$zmSlangDefaultView          = 'Visualizzazione di default';
$zmSlangDeleteAndNext        = 'Elimina &amp; Prossimo';
$zmSlangDeleteAndPrev        = 'Elimina &amp; Precedente';
$zmSlangDelete               = 'Elimina';
$zmSlangDeleteSavedFilter    = 'Elimina il filtro salvato';
$zmSlangDescription          = 'Descrizione';
$zmSlangDeviceChannel        = 'Canale Periferica';
$zmSlangDeviceFormat         = 'Formato';
$zmSlangDeviceNumber         = 'Numero Periferica';
$zmSlangDevicePath           = 'Percorso Dispositivo';
$zmSlangDevices              = 'Dispositivi';
$zmSlangDimensions           = 'Dimensioni';
$zmSlangDisableAlarms        = 'Disabil Allarme';
$zmSlangDisk                 = 'Utilizzo Disco';
$zmSlangDonateAlready        = 'No, ho gia donato...    ';
$zmSlangDonate               = 'Donate,per favore';
$zmSlangDonateEnticement     = 'Stai usando ZoneMinder da un po\' di tempo e spero che tu lo stia trovando utile per la sicurezza di casa tua o del tuo posto di lavoro..Anche se ZoneMinder e\' distribuito liberamente come software libero,costa soldi sia svilupparlo che supportarlo. Se preferisci che questo software continui ad avere supporto e sviluppo in futuro allora considera l\idea di fare una piccola donazione. Donare e\' ovviamente opzionale, ma apprezzato e puoi donare quanto vuoi,quel poco o tanto che tu desideri.<br><br>Se hai voglia per cortesia seleziona l\'opzione sotto o punta il tuo browser a http://www.zoneminder.com/donate.html .<br><br>Grazie per usare ZoneMinder e non dimenticare di visitare il forum in ZoneMinder.com se cerchi supporto o hai suggerimenti riguardo a come rendere migliore Zoneminder.';
$zmSlangDonateRemindDay      = 'Non ancora, ricordamelo ancora tra 1 giorno';
$zmSlangDonateRemindHour     = 'Non ancora, ricordamelo ancora tra 1 ora';
$zmSlangDonateRemindMonth    = 'Non ancora, ricordamelo ancora tra 1 mese';
$zmSlangDonateRemindNever    = 'No, io non voglio donare, non lo faro\' mai';
$zmSlangDonateRemindWeek     = 'Non ancora, ricordamelo ancora tra 1 settimana';
$zmSlangDonateYes            = 'Si,mi piacerebbe donare qualcosa ora';
$zmSlangDownload             = 'Download';
$zmSlangDuration             = 'Durata';
$zmSlangEdit                 = 'Modifica';
$zmSlangEmail                = 'Email';
$zmSlangEnableAlarms         = 'Abilita Allarmi';
$zmSlangEnabled              = 'Attivo';
$zmSlangEnterNewFilterName   = 'Inserisci il nome del nuovo filtro';
$zmSlangErrorBrackets        = 'Errore, controlla di avere un ugual numero di parentesti aperte e chiuse.';
$zmSlangError                = 'Errore';
$zmSlangErrorValidValue      = 'Errore, controlla che tutti i termini abbiano un valore valido';
$zmSlangEtc                  = 'ecc.';
$zmSlangEvent                = 'Evento';
$zmSlangEventFilter          = 'Filtro Eventi';
$zmSlangEventId              = 'Id Evento';
$zmSlangEventName            = 'Nome Evento';
$zmSlangEventPrefix          = 'Prefisso Evento';
$zmSlangEvents               = 'Eventi';
$zmSlangExclude              = 'Escludi';
$zmSlangExecute              = 'Esegui';
$zmSlangExportDetails        = 'Esp. dettagli eventi';
$zmSlangExport               = 'Esporta';
$zmSlangExportFailed         = 'Esp. Fallita ';
$zmSlangExportFormat         = 'Formato File Esp. ';
$zmSlangExportFormatTar      = 'Tar';
$zmSlangExportFormatZip      = 'Zip';
$zmSlangExportFrames         = 'Dettagli frame espo.';
$zmSlangExportImageFiles     = 'Esporta le immagini';
$zmSlangExporting            = 'In corso.';
$zmSlangExportMiscFiles      = 'Esporto Altri file (se presenti)';
$zmSlangExportOptions        = 'Opzioni Esportazione';
$zmSlangExportVideoFiles     = 'Esporto File Video (se presenti)';
$zmSlangFar                  = 'Lontano';
$zmSlangFastForward          = 'Avanti Veloce';
$zmSlangFastForward          = 'Fast Forward';
$zmSlangFeed                 = 'Feed';
$zmSlangFileColours          = 'Colori File';
$zmSlangFile                 = 'File';
$zmSlangFilePath             = 'Percorso File';
$zmSlangFilterArchiveEvents  = 'Archivia gli eventi';
$zmSlangFilterDeleteEvents   = 'Elimina gli eventi';
$zmSlangFilterEmailEvents    = 'Invia dettagli via email';
$zmSlangFilterExecuteEvents  = 'Esegui un comando';
$zmSlangFilterMessageEvents  = 'Invia dettagli tramite messaggio';
$zmSlangFilterPx             = 'Px Filtro';
$zmSlangFilters              = 'Filtri';
$zmSlangFilterUnset          = 'Devi specificare altezza e larghezza per il filtro';
$zmSlangFilterUploadEvents   = 'Fai upload eventi (FTP)';
$zmSlangFilterVideoEvents    = 'Crea video per tutte le corrispondenze';
$zmSlangFirst                = 'Primo';
$zmSlangFlippedHori          = 'ribaltato orizzontale';
$zmSlangFlippedVert          = 'ribaltato verticale';
$zmSlangFocus                = 'Focus';
$zmSlangForceAlarm           = 'Forza Allarme';
$zmSlangFormat               = 'Formato';
$zmSlangFPS                  = 'fps';
$zmSlangFPSReportInterval    = 'Intervallo Report FPS';
$zmSlangFrameId              = 'Id Immagine';
$zmSlangFrame                = 'Immagini';
$zmSlangFrameRate            = 'Immagini al secondo';
$zmSlangFrames               = 'Immagini';
$zmSlangFrameSkip            = 'Immagini saltate';
$zmSlangFTP                  = 'FTP';
$zmSlangFunc                 = 'Funz';
$zmSlangFunction             = 'Funzione';
$zmSlangGain                 = 'Gain';
$zmSlangGeneral              = 'Generale';
$zmSlangGenerateVideo        = 'Genera Video';
$zmSlangGeneratingVideo      = 'Sto generando il Video';
$zmSlangGoToZoneMinder       = 'Vai su zoneminder.com';
$zmSlangGrey                 = 'Grigio';
$zmSlangGroup                = 'Gruppo';
$zmSlangGroups               = 'Gruppi';
$zmSlangHasFocusSpeed        = 'Ha velocita\' di focus';
$zmSlangHasGainSpeed         = 'Ha velocita\' di guadagno';
$zmSlangHasHomePreset        = 'Ha posizioni di present';
$zmSlangHasIrisSpeed         = 'Ha velocota\' di iris';
$zmSlangHasPanSpeed          = 'Ha velocita\' di Pan';
$zmSlangHasPresets           = 'Ha preset';
$zmSlangHasTiltSpeed         = 'Ha velocita\' di Tilt';
$zmSlangHasTurboPan          = 'Ha il Turbo Pan';
$zmSlangHasTurboTilt         = 'Ha il Turbo Tilt';
$zmSlangHasWhiteSpeed        = 'Ha velocita\' di bilanciamento del bianco';
$zmSlangHasZoomSpeed         = 'Ha velocita\' di zoom';
$zmSlangHigh                 = 'Alta';
$zmSlangHighBW               = 'Banda&nbsp;Alta';
$zmSlangHome                 = 'Home';
$zmSlangHour                 = 'Ora';
$zmSlangHue                  = 'Tinta';
$zmSlangId                   = 'Id';
$zmSlangIdle                 = 'Inattivo';
$zmSlangIgnore               = 'Ignora';
$zmSlangImageBufferSize      = 'Grandezza Buffer Immagine (frames)';
$zmSlangImage                = 'Immagine';
$zmSlangImages               = 'Immagini';
$zmSlangInclude              = 'Includi';
$zmSlangIn                   = 'In';
$zmSlangInverted             = 'Invertito';
$zmSlangIris                 = 'Iris';
$zmSlangKeyString            = 'Stringa Chiave';
$zmSlangLabel                = 'Etichetta';
$zmSlangLanguage             = 'Linguaggio';
$zmSlangLast                 = 'Ultimo';
$zmSlangLimitResultsPost     = 'risultati;'; // This is used at the end of the phrase 'Limit to first N results only'
$zmSlangLimitResultsPre      = 'Limita ai primi'; // This is used at the beginning of the phrase 'Limit to first N results only'
$zmSlangLinkedMonitors       = 'Monitor Collegati';
$zmSlangList                 = 'Lista';
$zmSlangLoad                 = 'Carico Sistema';
$zmSlangLocal                = 'Locale';
$zmSlangLoggedInAs           = 'Collegato come:';
$zmSlangLoggingIn            = 'Mi Sto Collegando';
$zmSlangLogin                = 'Login';
$zmSlangLogout               = 'Logout';
$zmSlangLow                  = 'Bassa';
$zmSlangLowBW                = 'Banda&nbsp;Bassa';
$zmSlangMain                 = 'Principale';
$zmSlangMan                  = 'Man';
$zmSlangManual               = 'Manuale';
$zmSlangMark                 = 'Seleziona';
$zmSlangMaxBandwidth         = 'Banda Massima';
$zmSlangMaxBrScore           = 'Punteggio<br/>Massimo';
$zmSlangMaxFocusRange        = 'Massimo range del focus';
$zmSlangMaxFocusSpeed        = 'Massima velocita\' del focus';
$zmSlangMaxFocusStep         = 'Massimo step del focus';
$zmSlangMaxGainRange         = 'Massimo range del guadagno';
$zmSlangMaxGainSpeed         = 'Massima velocita\' del guadagno';
$zmSlangMaxGainStep          = 'Massimo step del guadagno';
$zmSlangMaximumFPS           = 'Massimi FPS';
$zmSlangMaxIrisRange         = 'Massima range dell\'Iris';
$zmSlangMaxIrisSpeed         = 'Massima velocita\' dell\'Iris';
$zmSlangMaxIrisStep          = 'Massimo step dell\'Iris';
$zmSlangMax                  = 'Massima';
$zmSlangMaxPanRange          = 'Massimo range del pan';
$zmSlangMaxPanSpeed          = 'Massima velocita\' del tilt';
$zmSlangMaxPanStep           = 'Massimo step del pan';
$zmSlangMaxTiltRange         = 'Massimo range del tilt';
$zmSlangMaxTiltSpeed         = 'Massima velocita\' del tilt';
$zmSlangMaxTiltStep          = 'Massimo passo del tilt';
$zmSlangMaxWhiteRange        = 'Massimo range del bilanciamento del bianco';
$zmSlangMaxWhiteSpeed        = 'Massima velocita\' del bilanciamento del bianco';
$zmSlangMaxWhiteStep         = 'Massimo Step del bilanciamento del bianco';
$zmSlangMaxZoomRange         = 'Massimo range dello zoom';
$zmSlangMaxZoomSpeed         = 'Massima velocita\' dello zoom';
$zmSlangMaxZoomStep          = 'Massimo step dello zoom';
$zmSlangMediumBW             = 'Banda&nbsp;Media';
$zmSlangMedium               = 'Media';
$zmSlangMinAlarmAreaLtMax    = 'L\'area minima dell\'allarme deve essere minore di quella massima';
$zmSlangMinAlarmAreaUnset    = 'Devi specificare il numero minimo di pixel per l\'allarme';
$zmSlangMinAlarmGeMinBlob    = 'I pixel minimi dell\'allarme devono essere grandi almeno quanto i pixel minimi del blob';
$zmSlangMinAlarmGeMinFilter  = 'I pixel minimi dell\'allarme devono essere grandi almeno quanto i pixel minimi del filtro';
$zmSlangMinAlarmPixelsLtMax  = 'I pixel minimi dell\'allarme devono essere minori dei pixel massimi dell\'allarme';
$zmSlangMinBlobAreaLtMax     = 'L\'area di blob minima deve essere minore dell\'area di blob massima';
$zmSlangMinBlobAreaUnset     = 'Devi specificare il numero minimo di pixel per il blob';
$zmSlangMinBlobLtMinFilter   = 'L\'area minima di blob deve essere minore o uguale dell\'area minima del filtro';
$zmSlangMinBlobsLtMax        = 'I blob minimi devono essere minori dei blob massimi';
$zmSlangMinBlobsUnset        = 'Devi specificare il numero minimo di blob';
$zmSlangMinFilterAreaLtMax   = 'L\'area minima del filtro deve essere minore di quella massima';
$zmSlangMinFilterAreaUnset   = 'Devi specificare il numero minimo di pixel per il filtro';
$zmSlangMinFilterLtMinAlarm  = 'L\'area minima di filtro deve essere minore o uguale dell\area minima di allarme';
$zmSlangMinFilterPixelsLtMax = 'I pixel minimi del filtro devono essere minori di pixel massimi del filtro';
$zmSlangMinFocusRange        = 'Range minimo del Focus';
$zmSlangMinFocusSpeed        = 'Velocita\' minima del Focus';
$zmSlangMinFocusStep         = 'Minimo step del Focus';
$zmSlangMinGainRange         = 'Minimo range del Guadagno';
$zmSlangMinGainSpeed         = 'Velocita\' minima del Guadagno';
$zmSlangMinGainStep          = 'Step minimo del guadagno';
$zmSlangMinIrisRange         = 'Range minimo dell\'Iris';
$zmSlangMinIrisSpeed         = 'Velocita\' minima dell\'Iris';
$zmSlangMinIrisStep          = 'Step minimo dell\'Iris';
$zmSlangMinPanRange          = 'Range minimo del pan';
$zmSlangMinPanSpeed          = 'Velocita\' minima del Pan';
$zmSlangMinPanStep           = 'Step minimo del Pan';
$zmSlangMinPixelThresLtMax   = 'I pixel minimi della soglia devono essere minori dei pixel massimi della soglia';
$zmSlangMinTiltRange         = 'Range minimo del Tilt';
$zmSlangMinTiltSpeed         = 'Velocita\' minima del Tilt';
$zmSlangMinTiltStep          = 'Step minimo del Tilt';
$zmSlangMinWhiteRange        = 'Range minimo del bilanciamento del bianco';
$zmSlangMinWhiteSpeed        = 'Velocita\' minima del bialnciamento del bianco';
$zmSlangMinWhiteStep         = 'Minimo step del bilanciamento del bianco';
$zmSlangMinZoomRange         = 'Range minimo dello zoom';
$zmSlangMinZoomSpeed         = 'Velocita\' minima dello zoom';
$zmSlangMinZoomStep          = 'Step minimo dello zoom';
$zmSlangMisc                 = 'Altro';
$zmSlangMonitorIds           = 'Monitor&nbsp;Ids';
$zmSlangMonitor              = 'Monitor';
$zmSlangMonitorPresetIntro   = 'Selezionare un appropriato pre settaggio dalla lista riportata qui sotto.<br><br>Per favore notare che questo potrebbe sovrascrivere ogni valore che hai già configurato su questo monitor.<br><br>';
$zmSlangMonitorPreset        = 'Monitor Presenti';
$zmSlangMonitors             = 'Monitors';
$zmSlangMontage              = 'Montaggio';
$zmSlangMonth                = 'Mese';
$zmSlangMove                 = 'Sposta';
$zmSlangMustBeGe             = 'deve essere superiore a';
$zmSlangMustBeLe             = 'deve essere inferiore o pari a';
$zmSlangMustConfirmPassword  = 'Devi confermare la password';
$zmSlangMustSupplyPassword   = 'Devi inserire una password';
$zmSlangMustSupplyUsername   = 'Devi specificare un nome utente';
$zmSlangName                 = 'Nome';
$zmSlangNear                 = 'Vicino';
$zmSlangNetwork              = 'Rete';
$zmSlangNewGroup             = 'Nuovo Gruppo';
$zmSlangNewLabel             = 'Nuova Etichetta';
$zmSlangNew                  = 'Nuovo';
$zmSlangNewPassword          = 'Nuova Password';
$zmSlangNewState             = 'Nuovo Stato';
$zmSlangNewUser              = 'Nuovo Utente';
$zmSlangNext                 = 'Prossimo';
$zmSlangNoFramesRecorded     = 'Non ci sono immagini salvate per questo evento';
$zmSlangNoGroups             = 'Nessun Gruppo e\' stato definito';
$zmSlangNoneAvailable        = 'Nessuno disponibile';
$zmSlangNone                 = 'Nessuno';
$zmSlangNo                   = 'No';
$zmSlangNormal               = 'Normale';
$zmSlangNoSavedFilters       = 'NessunFiltroSalvato';
$zmSlangNoStatisticsRecorded = 'Non ci sono statistiche salvate per questo evento/immagine';
$zmSlangNotes                = 'Note';
$zmSlangNumPresets           = 'Num Presets';
$zmSlangOff                  = 'Off';
$zmSlangOn                   = 'On';
$zmSlangOpen                 = 'Apri';
$zmSlangOpEq                 = 'uguale a';
$zmSlangOpGtEq               = 'maggiore o uguale a';
$zmSlangOpGt                 = 'maggiore di';
$zmSlangOpIn                 = 'impostato';
$zmSlangOpLtEq               = 'minore o uguale a';
$zmSlangOpLt                 = 'minore di';
$zmSlangOpMatches            = 'corrisponde';
$zmSlangOpNe                 = 'diverso da';
$zmSlangOpNotIn              = 'non impostato';
$zmSlangOpNotMatches         = 'non corrisponde';
$zmSlangOptionHelp           = 'Opzioni di Aiuto';
$zmSlangOptionRestartWarning = 'Queste modifiche potrebbero essere attive solo dopo un riavvio del sistema. Riavviare ZoneMinder.';
$zmSlangOptions              = 'Opzioni';
$zmSlangOrder                = 'Ordine';
$zmSlangOrEnterNewName       = 'o inserisci un nuovo nome';
$zmSlangOrientation          = 'Orientamento';
$zmSlangOut                  = 'Out';
$zmSlangOverwriteExisting    = 'Sovrascrivi';
$zmSlangPaged                = 'Con paginazione';
$zmSlangPanLeft              = 'Pan Sinistra';
$zmSlangPan                  = 'Pan';
$zmSlangPanRight             = 'Pan Destra';
$zmSlangPanTilt              = 'Pan/Tilt';
$zmSlangParameter            = 'Parametri';
$zmSlangPassword             = 'Password';
$zmSlangPasswordsDifferent   = 'Le password non coincidono';
$zmSlangPaths                = 'Percorsi';
$zmSlangPause                = 'Pausa';
$zmSlangPause                = 'Pause';
$zmSlangPhoneBW              = 'Banda&nbsp;Tel';
$zmSlangPhone                = 'Telefono';
$zmSlangPixelDiff            = 'Pixel Diff';
$zmSlangPixels               = 'pixels';
$zmSlangPlayAll              = 'Vedi tutti';
$zmSlangPlay                 = 'Play';
$zmSlangPleaseWait           = 'Attendere prego';
$zmSlangPoint                = 'Punto';
$zmSlangPostEventImageBuffer = 'Buffer di immagini Dopo Evento';
$zmSlangPreEventImageBuffer  = 'Buffer di immagini Pre Evento';
$zmSlangPreserveAspect       = 'Preserve Aspect Ratio';
$zmSlangPreset               = 'Preset';
$zmSlangPresets              = 'Presets';
$zmSlangPrev                 = 'Prec';
$zmSlangProtocol             = 'Protocol';
$zmSlangRate                 = 'Velocita\'';
$zmSlangReal                 = 'Reale';
$zmSlangRecord               = 'Registra';
$zmSlangRefImageBlendPct     = 'Riferimento Miscela Immagine percentuale';
$zmSlangRefresh              = 'Aggiorna';
$zmSlangRemoteHostName       = 'Nome dell\'Host Remoto';
$zmSlangRemoteHostPath       = 'Percorso dell\'Host Remoto';
$zmSlangRemoteHostPort       = 'Porta dell\'Host Remoto';
$zmSlangRemoteImageColours   = 'Colori delle immagini Remote';
$zmSlangRemote               = 'Remoto';
$zmSlangRename               = 'Rinomina';
$zmSlangReplayAll            = 'All Events';
$zmSlangReplayGapless        = 'Gapless Events';
$zmSlangReplay               = 'Replay';
$zmSlangReplaySingle         = 'Single Event';
$zmSlangResetEventCounts     = 'Resetta Contatore Eventi';
$zmSlangReset                = 'Resetta';
$zmSlangRestarting           = 'Sto riavviando';
$zmSlangRestart              = 'Riavvia';
$zmSlangRestrictedCameraIds  = 'Camera Ids Riservati';
$zmSlangRestrictedMonitors   = 'Monitor limitati';
$zmSlangReturnDelay          = 'Ritardo del ritorno';
$zmSlangReturnLocation       = 'Posizione del ritorno';
$zmSlangRewind               = 'Rewind';
$zmSlangRewind               = 'Riavvolgi';
$zmSlangRotateLeft           = 'Ruota a Sinista';
$zmSlangRotateRight          = 'Ruota a Destra';
$zmSlangRunMode              = 'Modalita funzionamento';
$zmSlangRunning              = 'Avviato';
$zmSlangRunState             = 'Stato Avviato';
$zmSlangSaveAs               = 'Salva come';
$zmSlangSaveFilter           = 'salva Filtro';
$zmSlangSave                 = 'Salva';
$zmSlangScale                = 'Scala';
$zmSlangScore                = 'Punteggio';
$zmSlangSecs                 = 'Secs';
$zmSlangSectionlength        = 'Lunghezza Sezione';
$zmSlangSelectMonitors       = 'Monitor Selezionati';
$zmSlangSelect               = 'Seleziona';
$zmSlangSelfIntersecting     = 'I vertici del poligono non devono intersecarsi';
$zmSlangSet                  = 'Imposta';
$zmSlangSetLearnPrefs        = 'Seleziona le preferenze di autoapprendimento'; // This can be ignored for now
$zmSlangSetNewBandwidth      = 'Imposta nuova Banda';
$zmSlangSetPreset            = 'Imposta Preset';
$zmSlangSettings             = 'Impostazioni';
$zmSlangShowFilterWindow     = 'MostraFinestraFiltri';
$zmSlangShowTimeline         = 'Mostra linea temporale';
$zmSlangSignalCheckColour    = 'Colore del controllo di segnale';
$zmSlangSignalCheckColour    = 'Signal Check Colour';
$zmSlangSize                 = 'grandezza';
$zmSlangSleep                = 'Sleep';
$zmSlangSortAsc              = 'Cresc';
$zmSlangSortBy               = 'Ordina per';
$zmSlangSortDesc             = 'Decr';
$zmSlangSource               = 'Sorgente';
$zmSlangSourceType           = 'Tipo Sorgente';
$zmSlangSpeedHigh            = 'Alta Velocita\'';
$zmSlangSpeedLow             = 'Bassa Velocita\'';
$zmSlangSpeedMedium          = 'Media Velocita\'';
$zmSlangSpeedTurbo           = 'Turbo Velocita\'';
$zmSlangSpeed                = 'Velocita\'';
$zmSlangStart                = 'Avvia';
$zmSlangState                = 'Stato';
$zmSlangStats                = 'Statistiche';
$zmSlangStatus               = 'Stato';
$zmSlangStepBack             = 'Step Back';
$zmSlangStepForward          = 'Step Forward';
$zmSlangStepLarge            = 'Lungo passo';
$zmSlangStepMedium           = 'Medio passo';
$zmSlangStepNone             = 'No passo';
$zmSlangStep                 = 'Passo';
$zmSlangStepSmall            = 'Piccolo passo';
$zmSlangStills               = 'Foto';
$zmSlangStopped              = 'Fermo-immagine';
$zmSlangStop                 = 'Stop';
$zmSlangStream               = 'Flusso';
$zmSlangStreamReplayBuffer   = 'Stream Replay Image Buffer';
$zmSlangSubmit               = 'Accetta';
$zmSlangSystem               = 'Sistema';
$zmSlangTele                 = 'Tele';
$zmSlangThumbnail            = 'Anteprima';
$zmSlangTilt                 = 'Tilt';
$zmSlangTimeDelta            = 'Tempo di Delta';
$zmSlangTimeline             = 'Linea Temporale';
$zmSlangTime                 = 'Ora';
$zmSlangTimestampLabelFormat = 'Formato etichetta timestamp';
$zmSlangTimestampLabelX      = 'coordinata X etichetta';
$zmSlangTimestampLabelY      = 'coordinata Y etichetta';
$zmSlangTimestamp            = 'Timestamp';
$zmSlangTimeStamp            = 'Time Stamp';
$zmSlangToday                = 'Oggi ';
$zmSlangTools                = 'Strumenti';
$zmSlangTotalBrScore         = 'Punteggio<br/>Totale';
$zmSlangTrackDelay           = 'Track Delay';
$zmSlangTrackMotion          = 'Track Motion';
$zmSlangTriggers             = 'Triggers';
$zmSlangTurboPanSpeed        = 'Velocita\' Turbo Pan';
$zmSlangTurboTiltSpeed       = 'Velocita\' Turbo Tilt';
$zmSlangType                 = 'Tipo';
$zmSlangUnarchive            = 'Togli dall\'archivio';
$zmSlangUnits                = 'Unit&agrave;';
$zmSlangUnknown              = 'Sconosciuto';
$zmSlangUpdate               = 'Aggiorna';
$zmSlangUpdateAvailable      = 'Un aggiornamento di ZoneMinder &egrave; disponibilie.';
$zmSlangUpdateNotNecessary   = 'Nessun aggiornamento necessario.';
$zmSlangUseFilterExprsPost   = '&nbsp;espressioni&nbsp;filtri'; // This is used at the end of the phrase 'use N filter expressions'
$zmSlangUseFilterExprsPre    = 'Usa&nbsp;'; // This is used at the beginning of the phrase 'use N filter expressions'
$zmSlangUseFilter            = 'Usa Filtro';
$zmSlangUsername             = 'Nome Utente';
$zmSlangUsers                = 'Utenti';
$zmSlangUser                 = 'Utente';
$zmSlangValue                = 'Valore';
$zmSlangVersionIgnore        = 'Ignora questa versione';
$zmSlangVersionRemindDay     = 'Ricordami ancora tra un giorno';
$zmSlangVersionRemindHour    = 'Ricordami ancora tra un\'ora';
$zmSlangVersionRemindNever   = 'Non ricordarmi di nuove versioni';
$zmSlangVersionRemindWeek    = 'Ricordami ancora tra una settimana';
$zmSlangVersion              = 'Versione';
$zmSlangVideoFormat          = 'Formato Video';
$zmSlangVideoGenFailed       = 'Generazione Video Fallita!';
$zmSlangVideoGenFiles        = 'File Video Esistenti';
$zmSlangVideoGenNoFiles      = 'Non ho trovato file ';
$zmSlangVideoGenParms        = 'Parametri Generazione Video';
$zmSlangVideoGenSucceeded    = 'Successo: Generato Video  !';
$zmSlangVideoSize            = 'Dimensioni Video';
$zmSlangVideo                = 'Video';
$zmSlangViewAll              = 'Vedi Tutto';
$zmSlangViewEvent            = 'Vedi Evento';
$zmSlangViewPaged            = 'Vedi con paginazione';
$zmSlangView                 = 'vedi';
$zmSlangWake                 = 'Riattiva';
$zmSlangWarmupFrames         = 'Immagini Allerta';
$zmSlangWatch                = 'Guarda';
$zmSlangWebColour            = 'Colore Web';
$zmSlangWeb                  = 'Web';
$zmSlangWeek                 = 'Settimana';
$zmSlangWhiteBalance         = 'Bil. Bianco  ';
$zmSlangWhite                = 'Bianco';
$zmSlangWide                 = 'Larghezza';
$zmSlangX10ActivationString  = 'Stringa attivazione X10';
$zmSlangX10InputAlarmString  = 'Stringa allarme input X10';
$zmSlangX10OutputAlarmString = 'Stringa allarme output X10';
$zmSlangX10                  = 'X10';
$zmSlangX                    = 'X';
$zmSlangYes                  = 'Si';
$zmSlangYouNoPerms           = 'Non hai i permessi per accedere a questa risorsa.';
$zmSlangY                    = 'Y';
$zmSlangZoneAlarmColour      = 'Colore Allarme (RGB)';
$zmSlangZoneArea             = 'Zone Area';
$zmSlangZoneFilterHeight     = 'Altezza Filtro (pixels)';
$zmSlangZoneFilterSize       = 'Larghezza/Altezza Filtro (pixels)';
$zmSlangZoneFilterWidth      = 'Larghezza Filtro (pixels)';
$zmSlangZoneMaxAlarmedArea   = 'Massima Area Allarmata';
$zmSlangZoneMaxBlobArea      = 'Massima Area Blob';
$zmSlangZoneMaxBlobs         = 'Numero Massimo di Blobs';
$zmSlangZoneMaxFilteredArea  = 'Massima Area Filtrata';
$zmSlangZoneMaxPixelThres    = 'Pixel Massimi di Soglia (0-255)';
$zmSlangZoneMaxX             = 'X Massimo (destra)';
$zmSlangZoneMaxY             = 'Y Massimo (basso)';
$zmSlangZoneMinAlarmedArea   = 'Minima Area Allarmata';
$zmSlangZoneMinBlobArea      = 'Minima Area Blob';
$zmSlangZoneMinBlobs         = 'Blob Minimi';
$zmSlangZoneMinFilteredArea  = 'Minima Area Filtrata';
$zmSlangZoneMinMaxAlarmArea  = 'Min/Max Area Allarmata';
$zmSlangZoneMinMaxBlobArea   = 'Min/Max Area di Blob';
$zmSlangZoneMinMaxBlobs      = 'Min/Max Blobs';
$zmSlangZoneMinMaxFiltArea   = 'Min/Max Area Filtrata';
$zmSlangZoneMinMaxPixelThres = 'Min/Max Soglia Pixel (0-255)';
$zmSlangZoneMinPixelThres    = 'Pixel Minimi di Soglia (0-255)';
$zmSlangZoneMinX             = 'X Minimo (sinistra)';
$zmSlangZoneMinY             = 'Y Minimo (alto)';
$zmSlangZoneOverloadFrames   = 'Overload Frame Ignore Count';
$zmSlangZones                = 'Zone';
$zmSlangZone                 = 'Zona';
$zmSlangZoomIn               = 'Ingrandisci';
$zmSlangZoomOut              = 'Rimpicciolisci';
$zmSlangZoom                 = 'Zoom';

// Complex replacements with formatting and/or placements, must be passed through sprintf
$zmClangCurrentLogin         = 'Login attuale: \'%1$s\'';
$zmClangEventCount           = '%1$s %2$s'; // For example '37 Events' (from Vlang below)
$zmClangLastEvents           = 'Ultimi %1$s %2$s'; // For example 'Last 37 Events' (from Vlang below)
$zmClangLatestRelease        = 'L\'ultima release v%1$s, tu hai v%2$s.';
$zmClangMonitorCount         = '%1$s %2$s'; // For example '4 Monitors' (from Vlang below)
$zmClangMonitorFunction      = 'Funzione Monitor %1$s';
$zmClangRunningRecentVer     = 'Stai usando la versione pi&ugrave; aggiornata di ZoneMinder, v%s.';

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
$zmVlangMonitor              = array( 0=>'Monitor', 1=>'Monitor', 2=>'Monitor' );

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
	die( 'Errore, sono incapace di correlare le stringhe del file-linguaggio');
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
