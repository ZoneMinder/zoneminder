<?php
//
// ZoneMinder web Italian language file, $Date$, $Revision$
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

// Simple String Replacements
$SLANG = array(
    '24BitColour'          => 'colori a 24 bit',
    '32BitColour'          => 'colori a 32 bit',          // Added - 2011-06-15
    '8BitGrey'             => '8 bit scala di grigio',
    'Action'               => 'Azione',
    'Actual'               => 'Attuale',
    'AddNewControl'        => 'Aggiungi nuovo Controllo',
    'AddNewMonitor'        => 'Aggiungi nuovo Monitor',
    'AddNewServer'         => 'Aggiungi nuovo Server',         // Added - 2018-08-30
    'AddNewStorage'        => 'Aggiungi nuovo Archivio',        // Added - 2018-08-30
    'AddNewUser'           => 'Aggiungi nuovo Utente',
    'AddNewZone'           => 'Aggiungi nuova Zona',
    'Alarm'                => 'Allarme',
    'AlarmBrFrames'        => 'Immagini<br/>Allarme',
    'AlarmFrame'           => 'Immagine Allarme',
    'AlarmFrameCount'      => 'Allarme Conta frame',
    'AlarmLimits'          => 'Limiti Allarme',
    'AlarmMaximumFPS'      => 'FPS massimi durante l\'allarme',
    'AlarmPx'              => 'Pixel Allarme',
    'AlarmRGBUnset'        => 'Devi settare un colore RGB di allarme',
    'AlarmRefImageBlendPct'=> 'Riferimento Allarme - Fusione Immagine %', // Added - 2015-04-18
    'Alert'                => 'Attenzione',
    'All'                  => 'Tutto',
    'AnalysisFPS'          => 'Analisi FPS',           // Added - 2015-07-22
    'AnalysisUpdateDelay'  => 'Intervallo aggiornamento analisi',  // Added - 2015-07-23
    'Apply'                => 'Applica',
    'ApplyingStateChange'  => 'Sto applicando le modifiche',
    'ArchArchived'         => 'Archiviato',
    'ArchUnarchived'       => 'Non Archiviato',
    'Archive'              => 'Archivio',
    'Archived'             => 'Archiviato',
    'Area'                 => 'Area',
    'AreaUnits'            => 'Area (px/%)',
    'AttrAlarmFrames'      => 'Immagini Allarme',
    'AttrArchiveStatus'    => 'Stato Archivio',
    'AttrAvgScore'         => 'Punteggio Medio',
    'AttrCause'            => 'Causa',
    'AttrDiskBlocks'       => 'Blocchi disco',
    'AttrDiskPercent'      => 'Percentuale disco',
    'AttrDiskSpace'        => 'Spazio disco',             // Added - 2018-08-30
    'AttrDuration'         => 'Durata',
    'AttrEndDate'          => 'End Date',               // Added - 2018-08-30
    'AttrEndDateTime'      => 'End Date/Time',          // Added - 2018-08-30
    'AttrEndTime'          => 'End Time',               // Added - 2018-08-30
    'AttrEndWeekday'       => 'End Weekday',            // Added - 2018-08-30
    'AttrFilterServer'     => 'Filtro attivo su Server', // Added - 2018-08-30
    'AttrFrames'           => 'Immagini',
    'AttrId'               => 'Id',
    'AttrMaxScore'         => 'Punteggio Massimo',
    'AttrMonitorId'        => 'Id Monitor',
    'AttrMonitorName'      => 'Nome Monitor',
    'AttrMonitorServer'    => 'Monitor attivo su Server', // Added - 2018-08-30
    'AttrName'             => 'Nome',
    'AttrNotes'            => 'Note',
    'AttrStartDate'        => 'Inizio - Data',             // Added - 2018-08-30
    'AttrStartDateTime'    => 'Inizio - Data/orario',        // Added - 2018-08-30
    'AttrStartTime'        => 'Inizio - Orario',             // Added - 2018-08-30
    'AttrStartWeekday'     => 'Inizio - Giorno della settimana',          // Added - 2018-08-30
    'AttrStateId'          => 'Stato Esecuzione',              // Added - 2018-08-30
    'AttrStorageArea'      => 'Area Archiviazione',           // Added - 2018-08-30
    'AttrStorageServer'    => 'Server Archiviazione remota', // Added - 2018-08-30
    'AttrSystemLoad'       => 'Carico Sistema',
    'AttrTotalScore'       => 'Punteggio Totale',
    'Auto'                 => 'Auto',
    'AutoStopTimeout'      => 'Auto Stop Timeout',
    'Available'            => 'Disponibile',              // Added - 2009-03-31
    'AvgBrScore'           => 'Punteggio<br/>medio',
    'Background'           => 'Sfondo',
    'BackgroundFilter'     => 'Esegui filtro in background',
    'BadAlarmFrameCount'   => 'Il numero di immagini per secondo (FPS) di un allarme deve essere un numero intero superiore a uno',
    'BadAlarmMaxFPS'       => 'Il numero massimo di immagini per secondo (FPS) dell\'allarme deve essere un numero intero positivo o un valore in virgola mobile',
    'BadAnalysisFPS'       => 'Il numero di immagini per secondo (FPS) di analisi deve essere un numero intero positivo o un valore in virgola mobile', // Added - 2015-07-22
    'BadAnalysisUpdateDelay'=> 'Il ritardo di aggiornamento dell\'analisi deve essere impostato su un numero intero pari a zero o superiore', // Added - 2015-07-23
    'BadChannel'           => 'Il canale deve essere settato con un numero intero uguale o maggiore di zero',
    'BadColours'           => 'Il colore target deve essere impostato su un valore valido', // Added - 2011-06-15
    'BadDevice'            => 'Il dispositivo deve essere impostato con un valore valido',
    'BadFPSReportInterval' => 'L\'intervallo di FPS per i report deve essere un numero intero superiore a 0',
    'BadFormat'            => 'Il formato deve essere impostato con un numero intero come 0 o maggiore',
    'BadFrameSkip'         => 'Il numero di Frame da scartare deve essere un intero uguale a 0 o superiore',
    'BadHeight'            => 'L\'altezza deve essere impostata con un valore valido',
    'BadHost'              => 'L\'host deve essere impostato con un indirizzo ip valido o con un hostname, non includendo http://',
    'BadImageBufferCount'  => 'La dimensione del buffer dell\'immagine deve essere impostata con un numero intero pari a 2 o maggiore',
    'BadLabelX'            => 'L\'etichetta della coordinata X deve essere un numero intero pari a 0 o maggiore',
    'BadLabelY'            => 'L\'etichetta della coordinata Y deve essere un numero intero pari a 0 o maggiore',
    'BadMaxFPS'            => 'I frame per secondo (FPS) massimi devono essere un numero intero positivo o un valore in virgola mobile',
    'BadMotionFrameSkip'   => 'Il conteggio dei salti di Motion Frame deve essere un numero intero pari a zero o superiore',
    'BadNameChars'         => 'I nomi possono contenere solo caratteri alfanumerici pi&ugrave; i caratteri - e _',
    'BadPalette'           => 'La tavolozza dei colori deve essere impostata ad un valore valido', // Added - 2009-03-31
    'BadPath'              => 'Il percorso deve essere impostato con un valore valido',
    'BadPort'              => 'La porta deve essere settata con un valore valido',
    'BadPostEventCount'    => 'Il buffer d\'immagine successivo ad un evento deve essere un numero maggiore o uguale a zero',
    'BadPreEventCount'     => 'Il buffer d\'immagine antecedente ad un evento deve essere minimo 0 e comunque minore della dimensione del buffer d\'immagine',
    'BadRefBlendPerc'      => 'La percentuale di miscela di riferimento deve essere un intero positivo',
    'BadSectionLength'     => 'La lunghezza della sezione deve essere un numero intero pari a 30 o maggiore',
    'BadSignalCheckColour' => 'Il colore di controllo del segnale deve essere una stringa di colore RGB valida',
    'BadSourceType'        => 'Il tipo di origine \"Sito Web\" richiede che la funzione sia impostata su \"Monitor\"', // Added - 2018-08-30
    'BadStreamReplayBuffer'=> 'Il buffer di riproduzione dello stream deve essere un numero intero pari a zero o superiore',
    'BadWarmupCount'       => 'Il numero di frame di allarme deve essere un numero intero maggiore o uguale a zero',
    'BadWebColour'         => 'L\'identificativo del colore deve essere una stringa valida', 
    'BadWebSitePath'       => 'Inserisci un URL completo del sito Web, incluso il prefisso http: // o https: //.', // Added - 2018-08-30
    'BadWidth'             => 'La larghezza deve essere impostata con un valore valido',
    'Bandwidth'            => 'Banda',
    'BandwidthHead'         => 'Larghezza di banda',	// This is the end of the bandwidth status on the top of the console, different in many language due to phrasing
    'BlobPx'               => 'Blob Px',
    'BlobSizes'            => 'Dimensioni Blob',
    'Blobs'                => 'Blobs',
    'Brightness'           => 'Luminosità',
    'Buffer'               => 'Buffer',                 // Added - 2015-04-18
    'Buffers'              => 'Buffers',
    'CSSDescription'       => 'Modificare il css predefinito per questo computer', // Added - 2015-04-18
    'CanAutoFocus'         => 'Può impostare Auto Focus',
    'CanAutoGain'          => 'Può impostare Auto Gains',
    'CanAutoIris'          => 'Può impostare Auto Iris',
    'CanAutoWhite'         => 'Può impostare Auto bil bianco',
    'CanAutoZoom'          => 'Può impostare Auto Zoom',
    'CanFocus'             => 'Può impostare Fuoco',
    'CanFocusAbs'          => 'Può impostare Fuoco Assoluto',
    'CanFocusCon'          => 'Può impostare Fuoco Continuo',
    'CanFocusRel'          => 'Può impostare Fuoco Relativo',
    'CanGain'              => 'Può impostare Guadagno',
    'CanGainAbs'           => 'Può impostare Guadagno Assoluto',
    'CanGainCon'           => 'Può impostare Guadagno Continuo  ',
    'CanGainRel'           => 'Può impostare Guadagno Relativo',
    'CanIris'              => 'Può impostare Iride',
    'CanIrisAbs'           => 'Può impostare Iride Assoluto',
    'CanIrisCon'           => 'Può impostare Iride Continuo',
    'CanIrisRel'           => 'Può impostare Iride Relativo',
    'CanMove'              => 'Può impostare Movimento',
    'CanMoveAbs'           => 'Può impostare Movimento Assoluto',
    'CanMoveCon'           => 'Può impostare Movimento Continuo',
    'CanMoveDiag'          => 'Può impostare Movimento Diagonale',
    'CanMoveMap'           => 'Può impostare Movimento Mappato',
    'CanMoveRel'           => 'Può impostare Movimento Relativo',
    'CanPan'               => 'Può impostare Panoramica' ,
    'CanReset'             => 'Può effettuare Reset',
    'CanReboot'            => 'Può Riavviare',
    'CanSetPresets'        => 'Può impostare Preset',
    'CanSleep'             => 'Può sospendere',
    'CanTilt'              => 'Può inclinare',
    'CanWake'              => 'Può riattivare',
    'CanWhite'             => 'Può bilanciare il bianco',
    'CanWhiteAbs'          => 'Può bilanciare il bianco assoluto',
    'CanWhiteBal'          => 'Può bilanciare il bianco',
    'CanWhiteCon'          => 'Può bilanciare il bianco Continuo',
    'CanWhiteRel'          => 'Può bilanciare il bianco Relativo',
    'CanZoom'              => 'Può impostare Zoom',
    'CanZoomAbs'           => 'Può impostare Zoom Assoluto',
    'CanZoomCon'           => 'Può impostare Zoom Continuo',
    'CanZoomRel'           => 'Può impostare Zoom Relativo',
    'Cancel'               => 'Annulla',
    'CancelForcedAlarm'    => 'Annulla Allarme Forzato',
    'CaptureHeight'        => 'Altezza Cattura Immagine',
    'CaptureMethod'        => 'Metodo Cattura Immagine',         // Added - 2009-02-08
    'CapturePalette'       => 'Tavolozza Cattura Immagine',
    'CaptureResolution'    => 'Risoluzione Cattura Immagine',     // Added - 2015-04-18
    'CaptureWidth'         => 'Larghezza Cattura Immagine',
    'Cause'                => 'Causa',
    'CheckMethod'          => 'Metodo di Controllo Allarme',
    'ChooseDetectedCamera' => 'Scegli telecamera rilevata', // Added - 2009-03-31
    'ChooseFilter'         => 'Scegli Filtro',
    'ChooseLogFormat'      => 'Scegli un formato di registro',    // Added - 2011-06-17
    'ChooseLogSelection'   => 'Scegli una selezione del registro', // Added - 2011-06-17
    'ChoosePreset'         => 'Scegli Preset',
    'Clear'                => 'Pulisci',                  // Added - 2011-06-16
    'CloneMonitor'         => 'Clona',                  // Added - 2018-08-30
    'Close'                => 'Chiudi',
    'Colour'               => 'Colori',
    'Command'              => 'Comando',
    'Component'            => 'Component',              // Added - 2011-06-16
    'ConcurrentFilter'     => 'Esegui filtro contemporaneamente', // Added - 2018-08-30
    'Config'               => 'Configura',
    'ConfiguredFor'        => 'Configurato per',
    'ConfirmDeleteEvents'  => 'Sei sicuro di voler cancellare gli eventi selezionati',
    'ConfirmPassword'      => 'Conferma Password',
    'ConjAnd'              => 'e',
    'ConjOr'               => 'o',
    'Console'              => 'Console',
    'ContactAdmin'         => 'Contatta il tuo amministratore per dettagli.',
    'Continue'             => 'Continuo',
    'Contrast'             => 'Contrasto',
    'Control'              => 'Controllo',
    'ControlAddress'       => 'Indirizzo di controllo',
    'ControlCap'           => 'Capacità di controllo',
    'ControlCaps'          => 'Capacità di controllo',
    'ControlDevice'        => 'Dispositivo di controllo',
    'ControlType'          => 'Tipo Controllo',
    'Controllable'         => 'Controllabile',
    'Current'              => 'Corrente',                // Added - 2015-04-18
    'Cycle'                => 'Cicla',
    'CycleWatch'           => 'Vista Ciclica',
    'DateTime'             => 'Data/Orario',              // Added - 2011-06-16
    'Day'                  => 'Giorno',
    'Debug'                => 'Debug',
    'DefaultRate'          => 'Rateo predefinito',
    'DefaultScale'         => 'Scala di default',
    'DefaultView'          => 'Visualizzazione predefinita',
    'Deinterlacing'        => 'Deinterlacciamento',          // Added - 2015-04-18
    'Delay'                => 'Ritardo',                  // Added - 2015-04-18
    'Delete'               => 'Elimina',
    'DeleteAndNext'        => 'Elimina e Prossimo',
    'DeleteAndPrev'        => 'Elimina e Precedente',
    'DeleteSavedFilter'    => 'Elimina il filtro salvato',
    'Description'          => 'Descrizione',
    'DetectedCameras'      => 'Telecamere Rilevate',       // Added - 2009-03-31
    'DetectedProfiles'     => 'Profili Rilevati',      // Added - 2015-04-18
    'Device'               => 'Periferica',                 // Added - 2009-02-08
    'DeviceChannel'        => 'Canale Periferica',
    'DeviceFormat'         => 'Formato',
    'DeviceNumber'         => 'Numero Periferica',
    'DevicePath'           => 'Percorso Dispositivo',
    'Devices'              => 'Dispositivi',
    'Dimensions'           => 'Dimensioni',
    'DisableAlarms'        => 'Disabilita Allarme',
    'Disk'                 => 'Utilizzo Disco',
    'Display'              => 'Mostra',                // Added - 2011-01-30
    'Displaying'           => 'Visualizzazione',             // Added - 2011-06-16
    'DoNativeMotionDetection'=> 'Attiva Motion Detection Nativo',
    'Donate'               => 'Donate,per favore',
    'DonateAlready'        => 'No, ho gia donato...    ',
    'DonateEnticement'     => 'Stai usando ZoneMinder da un pò di tempo e spero che tu lo stia trovando utile per la sicurezza di casa tua o del tuo posto di lavoro..Anche se ZoneMinder e\' distribuito liberamente come software libero,costa soldi sia svilupparlo che supportarlo. Se preferisci che questo software continui ad avere supporto e sviluppo in futuro allora considera l\idea di fare una piccola donazione. Donare e\' ovviamente opzionale, ma apprezzato e puoi donare quanto vuoi,quel poco o tanto che tu desideri.<br><br>Se hai voglia per cortesia seleziona l\'opzione sotto o punta il tuo browser a https://zoneminder.com/donate/ .<br><br>Grazie per usare ZoneMinder e non dimenticare di visitare il forum in ZoneMinder.com se cerchi supporto o hai suggerimenti riguardo a come rendere migliore Zoneminder.',
    'DonateRemindDay'      => 'Non ancora, ricordamelo ancora tra 1 giorno',
    'DonateRemindHour'     => 'Non ancora, ricordamelo ancora tra 1 ora',
    'DonateRemindMonth'    => 'Non ancora, ricordamelo ancora tra 1 mese',
    'DonateRemindNever'    => 'No, io non voglio donare, non lo farò mai',
    'DonateRemindWeek'     => 'Non ancora, ricordamelo ancora tra 1 settimana',
    'DonateYes'            => 'Si,mi piacerebbe donare qualcosa ora',
    'Download'             => 'Scarica',
    'DownloadVideo'        => 'Scarica video',         // Added - 2018-08-30
    'DuplicateMonitorName' => 'Il nome del monitor è già presente', // Added - 2009-03-31
    'Duration'             => 'Durata',
    'Edit'                 => 'Modifica',
    'EditLayout'           => 'Modifica Layout',            // Added - 2018-08-30
    'Email'                => 'Email',
    'EnableAlarms'         => 'Abilita Allarmi',
    'Enabled'              => 'Attivo',
    'EnterNewFilterName'   => 'Inserisci il nome del nuovo filtro',
    'Error'                => 'Errore',
    'ErrorBrackets'        => 'Errore, controlla di avere un ugual numero di parentesti aperte e chiuse.',
    'ErrorValidValue'      => 'Errore, controlla che tutti i termini abbiano un valore valido',
    'Etc'                  => 'ecc.',
    'Event'                => 'Evento',
    'EventFilter'          => 'Filtro Eventi',
    'EventId'              => 'Id Evento',
    'EventName'            => 'Nome Evento',
    'EventPrefix'          => 'Prefisso Evento',
    'Events'               => 'Eventi',
    'Exclude'              => 'Escludi',
    'Execute'              => 'Esegui',
    'Exif'                 => 'Includi dati EXIF nell\'immagine', // Added - 2018-08-30
    'Export'               => 'Esporta',
    'ExportDetails'        => 'Esporta dettagli eventi',
    'ExportFailed'         => 'Esportazione Fallita ',
    'ExportFormat'         => 'Formato File Esportazione',
    'ExportFormatTar'      => 'Tar',
    'ExportFormatZip'      => 'Zip',
    'ExportFrames'         => 'Esporta dettagli immagini',
    'ExportImageFiles'     => 'Esporta le immagini',
    'ExportLog'            => 'Esporta Log',             // Added - 2011-06-17
    'ExportMiscFiles'      => 'Esporta Altri file (se presenti)',
    'ExportOptions'        => 'Opzioni Esportazione',
    'ExportSucceeded'      => 'Esportazione completata con successo',       // Added - 2009-02-08
    'ExportVideoFiles'     => 'Esporta File Video (se presenti)',
    'Exporting'            => 'In corso',
    'FPS'                  => 'fps',
    'FPSReportInterval'    => 'Intervallo Report FPS',
    'FTP'                  => 'FTP',
    'Far'                  => 'Lontano',
    'FastForward'          => 'Avanzamento veloce',
    'Feed'                 => 'Feed',
    'Ffmpeg'               => 'Ffmpeg',                 // Added - 2009-02-08
    'File'                 => 'File',
    'Filter'               => 'Filtro',                 // Added - 2015-04-18
    'FilterArchiveEvents'  => 'Archivia gli eventi',
    'FilterDeleteEvents'   => 'Elimina gli eventi',
    'FilterEmailEvents'    => 'Invia dettagli via email',
    'FilterExecuteEvents'  => 'Esegui un comando',
    'FilterLog'            => 'Filtra log',             // Added - 2015-04-18
    'FilterMessageEvents'  => 'Invia dettagli tramite messaggio',
    'FilterMoveEvents'     => 'Sposta tutti gli eventi',       // Added - 2018-08-30
    'FilterPx'             => 'Px Filtro',
    'FilterUnset'          => 'Devi specificare altezza e larghezza per il filtro',
    'FilterUpdateDiskSpace'=> 'Aggiorna spazio disco utilizzato', // Added - 2018-08-30
    'FilterUploadEvents'   => 'Fai upload eventi (FTP)',
    'FilterVideoEvents'    => 'Crea video per tutte le corrispondenze',
    'Filters'              => 'Filtri',
    'First'                => 'Primo',
    'FlippedHori'          => 'ribaltato orizzontale',
    'FlippedVert'          => 'ribaltato verticale',
    'FnMocord'              => 'Mocord - Registrazione continua (con evidenziazione eventi)',            // Added 2013.08.16.
    'FnModect'              => 'Modect - MOtion DEteCTtion (registrazione su rilevamento movimento)',            // Added 2013.08.16.
    'FnMonitor'             => 'Monitor - Visualizza Live',            // Added 2013.08.16.
    'FnNodect'              => 'Nodect - No DEteCTtion (registrazione su evento esterno)',            // Added 2013.08.16.
    'FnNone'                => 'None - Nessuno (Monitor disabilitato)',            // Added 2013.08.16.
    'FnRecord'              => 'Record - Registrazione continua',            // Added 2013.08.16.
    'Focus'                => 'Focus',
    'ForceAlarm'           => 'Forza Allarme',
    'Format'               => 'Formato',
    'Frame'                => 'Immagini',
    'FrameId'              => 'Id Immagine',
    'FrameRate'            => 'Immagini al secondo',
    'FrameSkip'            => 'Immagini saltate',
    'Frames'               => 'Immagini',
    'Func'                 => 'Funz',
    'Function'             => 'Funzione',
    'Gain'                 => 'Guadagno',
    'General'              => 'Generale',
    'GenerateDownload'     => 'Genera download',      // Added - 2018-08-30
    'GenerateVideo'        => 'Genera video',
    'GeneratingVideo'      => 'Sto generando il video',
    'GoToZoneMinder'       => 'Vai su zoneminder.com',
    'Grey'                 => 'Grigio',
    'Group'                => 'Gruppo',
    'Groups'               => 'Gruppi',
    'HasFocusSpeed'        => 'Ha velocità di focus',
    'HasGainSpeed'         => 'Ha velocità di guadagno',
    'HasHomePreset'        => 'Ha posizioni di present',
    'HasIrisSpeed'         => 'Ha velocità di iris',
    'HasPanSpeed'          => 'Ha velocità di Pan',
    'HasPresets'           => 'Ha preset',
    'HasTiltSpeed'         => 'Ha velocità di Tilt',
    'HasTurboPan'          => 'Ha il Turbo Pan',
    'HasTurboTilt'         => 'Ha il Turbo Tilt',
    'HasWhiteSpeed'        => 'Ha velocità di bilanciamento del bianco',
    'HasZoomSpeed'         => 'Ha velocità di zoom',
    'High'                 => 'Alta',
    'HighBW'               => 'Banda Alta',
    'Hight'                => 'Altezza',
    'Home'                 => 'Home',
    'Hostname'             => 'Nome Host',               // Added - 2018-08-30
    'Hour'                 => 'Ora',
    'Hue'                  => 'Tinta',
    'Id'                   => 'Id',
    'Idle'                 => 'Inattivo',
    'Ignore'               => 'Ignora',
    'Image'                => 'Immagine',
    'ImageBufferSize'      => 'Grandezza Buffer Immagine (frames)',
    'Images'               => 'Immagini',
    'In'                   => 'In',
    'Include'              => 'Includi',
    'Inverted'             => 'Invertito',
    'Iris'                 => 'Iride',
    'KeyString'            => 'Stringa Chiave',
    'Label'                => 'Etichetta',
    'Language'             => 'Linguaggio',
    'Last'                 => 'Ultimo',
    'Layout'               => 'Layout',                 // Added - 2009-02-08
    'Level'                => 'Livello',                  // Added - 2011-06-16
    'Libvlc'               => 'Libvlc',
    'LimitResultsPost'     => 'risultati;', // This is used at the end of the phrase 'Limit to first N results only'
    'LimitResultsPre'      => 'Limita ai primi', // This is used at the beginning of the phrase 'Limit to first N results only'
    'Line'                 => 'Line',                   // Added - 2011-06-16
    'LinkedMonitors'       => 'Monitor Collegati',
    'List'                 => 'Lista',
    'ListMatches'          => 'Elenca le corrispondenze',           // Added - 2018-08-30
    'Load'                 => 'Carico Sistema',
    'Local'                => 'Locale',
    'Log'                  => 'Log',                    // Added - 2011-06-16
    'LoggedInAs'           => 'Collegato come:',
    'Logging'              => 'Logging',                // Added - 2011-06-16
    'LoggingIn'            => 'Mi Sto Collegando',
    'Login'                => 'Login',
    'Logout'               => 'Logout',
    'Logs'                 => 'Logs',                   // Added - 2011-06-17
    'Low'                  => 'Bassa',
    'LowBW'                => 'Banda Bassa',
    'Main'                 => 'Principale',
    'Man'                  => 'Man',
    'Manual'               => 'Manuale',
    'Mark'                 => 'Seleziona',
    'Max'                  => 'Massima',
    'MaxBandwidth'         => 'Banda Massima',
    'MaxBrScore'           => 'Punteggio Massimo',
    'MaxFocusRange'        => 'Massimo range del focus',
    'MaxFocusSpeed'        => 'Massima velocità del focus',
    'MaxFocusStep'         => 'Massimo step del focus',
    'MaxGainRange'         => 'Massimo range del guadagno',
    'MaxGainSpeed'         => 'Massima velocità del guadagno',
    'MaxGainStep'          => 'Massimo step del guadagno',
    'MaxIrisRange'         => 'Massima range dell\'Iride',
    'MaxIrisSpeed'         => 'Massima velocità dell\'Iride',
    'MaxIrisStep'          => 'Massimo step dell\'Iride',
    'MaxPanRange'          => 'Massimo range del pan',
    'MaxPanSpeed'          => 'Massima velocità del tilt',
    'MaxPanStep'           => 'Massimo step del pan',
    'MaxTiltRange'         => 'Massimo range del tilt',
    'MaxTiltSpeed'         => 'Massima velocità del tilt',
    'MaxTiltStep'          => 'Massimo passo del tilt',
    'MaxWhiteRange'        => 'Massimo range del bilanciamento del bianco',
    'MaxWhiteSpeed'        => 'Massima velocità del bilanciamento del bianco',
    'MaxWhiteStep'         => 'Massimo Step del bilanciamento del bianco',
    'MaxZoomRange'         => 'Massimo range dello zoom',
    'MaxZoomSpeed'         => 'Massima velocità dello zoom',
    'MaxZoomStep'          => 'Massimo step dello zoom',
    'MaximumFPS'           => 'Massimi FPS',
    'Medium'               => 'Media',
    'MediumBW'             => 'Larghezza Banda Media',
    'Message'              => 'Messaggio',                // Added - 2011-06-16
    'MinAlarmAreaLtMax'    => 'L\'area minima dell\'allarme deve essere minore di quella massima',
    'MinAlarmAreaUnset'    => 'Devi specificare il numero minimo di pixel per l\'allarme',
    'MinBlobAreaLtMax'     => 'L\'area di blob minima deve essere minore dell\'area di blob massima',
    'MinBlobAreaUnset'     => 'Devi specificare il numero minimo di pixel per il blob',
    'MinBlobLtMinFilter'   => 'L\'area minima di blob deve essere minore o uguale dell\'area minima del filtro',
    'MinBlobsLtMax'        => 'I blob minimi devono essere minori dei blob massimi',
    'MinBlobsUnset'        => 'Devi specificare il numero minimo di blob',
    'MinFilterAreaLtMax'   => 'L\'area minima del filtro deve essere minore di quella massima',
    'MinFilterAreaUnset'   => 'Devi specificare il numero minimo di pixel per il filtro',
    'MinFilterLtMinAlarm'  => 'L\'area minima di filtro deve essere minore o uguale dell\area minima di allarme',
    'MinFocusRange'        => 'Range minimo del Focus',
    'MinFocusSpeed'        => 'Velocità minima del Focus',
    'MinFocusStep'         => 'Minimo step del Focus',
    'MinGainRange'         => 'Minimo range del Guadagno',
    'MinGainSpeed'         => 'Velocità minima del Guadagno',
    'MinGainStep'          => 'Step minimo del guadagno',
    'MinIrisRange'         => 'Range minimo dell\'Iride',
    'MinIrisSpeed'         => 'Velocità minima dell\'Iride',
    'MinIrisStep'          => 'Step minimo dell\'Iride',
    'MinPanRange'          => 'Range minimo del Pan',
    'MinPanSpeed'          => 'Velocità minima del Pan',
    'MinPanStep'           => 'Step minimo del Pan',
    'MinPixelThresLtMax'   => 'I pixel minimi della soglia devono essere minori dei pixel massimi della soglia',
    'MinPixelThresUnset'   => 'Devi specificare una soglia minima di pixel', // Added - 2009-02-08
    'MinTiltRange'         => 'Range minimo del Tilt',
    'MinTiltSpeed'         => 'Velocità minima del Tilt',
    'MinTiltStep'          => 'Step minimo del Tilt',
    'MinWhiteRange'        => 'Range minimo del bilanciamento del bianco',
    'MinWhiteSpeed'        => 'Velocità minima del bialnciamento del bianco',
    'MinWhiteStep'         => 'Minimo step del bilanciamento del bianco',
    'MinZoomRange'         => 'Range minimo dello zoom',
    'MinZoomSpeed'         => 'Velocità minima dello zoom',
    'MinZoomStep'          => 'Step minimo dello zoom',
    'Misc'                 => 'Altro',
    'Mode'                 => 'Modalità',                   // Added - 2015-04-18
    'Monitor'              => 'Monitor',
    'MonitorIds'           => 'Monitor Ids',
    'MonitorPreset'        => 'Monitor Preset',
    'MonitorPresetIntro'   => 'Selezionare un preset appropriato dalla lista riportata qui sotto.<br><br>Notare che questo potrebbe sovrascrivere ogni valore che hai già configurato su questo monitor.<br><br>',
    'MonitorProbe'         => 'Prova Monitor',          // Added - 2009-03-31
    'MonitorProbeIntro'    => 'L\'elenco seguente mostra le telecamere analogiche e di rete rilevate e se sono già in uso o disponibili per la selezione.<br/><br/>Selezionare la voce desiderata dall\'elenco seguente.<br/><br/>Si noti che non tutte le telecamere possono essere rilevate e che la scelta di una telecamera qui potrebbe sovrascrivere tutti i valori già configurati per il monitor corrente.<br/><br/>', // Added - 2009-03-31
    'Monitors'             => 'Monitors',
    'Montage'              => 'Montaggio',
    'MontageReview'        => 'Revisione del montaggio',         // Added - 2018-08-30
    'Month'                => 'Mese',
    'More'                 => 'Più',                   // Added - 2011-06-16
    'MotionFrameSkip'      => 'Salta/scarta fotogramma',
    'Move'                 => 'Sposta',
    'Mtg2widgrd'           => 'Griglia 2 colonne',              // Added 2013.08.15.
    'Mtg3widgrd'           => 'Griglia 3 colonne',              // Added 2013.08.15.
    'Mtg3widgrx'           => 'Griglia 3 colonne, scalata, ingrandita su allarme',              // Added 2013.08.15.
    'Mtg4widgrd'           => 'Griglia 4 colonne',              // Added 2013.08.15.
    'MtgDefault'           => 'Predefinito',              // Added 2013.08.15.
    'MustBeGe'             => 'deve essere superiore a',
    'MustBeLe'             => 'deve essere inferiore o pari a',
    'MustConfirmPassword'  => 'Devi confermare la password',
    'MustSupplyPassword'   => 'Devi inserire una password',
    'MustSupplyUsername'   => 'Devi specificare un nome utente',
    'Name'                 => 'Nome',
    'Near'                 => 'Vicino',
    'Network'              => 'Rete',
    'New'                  => 'Nuovo',
    'NewGroup'             => 'Nuovo Gruppo',
    'NewLabel'             => 'Nuova Etichetta',
    'NewPassword'          => 'Nuova Password',
    'NewState'             => 'Nuovo Stato',
    'NewUser'              => 'Nuovo Utente',
    'Next'                 => 'Prossimo',
    'No'                   => 'No',
    'NoDetectedCameras'    => 'Nessuna telecamera rilevata',    // Added - 2009-03-31
    'NoDetectedProfiles'   => 'Nessun profilo rilevato',   // Added - 2018-08-30
    'NoFramesRecorded'     => 'Non ci sono immagini salvate per questo evento',
    'NoGroup'              => 'Nessun gruppo',               // Added - 2009-02-08
    'NoSavedFilters'       => 'Nessun filtro salvato',
    'NoStatisticsRecorded' => 'Non ci sono statistiche salvate per questo evento/immagine',
    'None'                 => 'Nessuno',
    'NoneAvailable'        => 'Nessuno disponibile',
    'Normal'               => 'Normale',
    'Notes'                => 'Note',
    'NumPresets'           => 'Num redefiniti',
    'Off'                  => 'Off',
    'On'                   => 'On',
    'OnvifCredentialsIntro'=> 'Fornire nome utente e password per la telecamera selezionata.<br/>Se non è stato creato alcun utente per la videocamera, l\'utente qui indicato verrà creato con la password specificata.<br/><br/>', // Added - 2015-04-18
    'OnvifProbe'           => 'ONVIF',                  // Added - 2015-04-18
    'OnvifProbeIntro'      => 'L\'elenco seguente mostra le telecamere ONVIF rilevate e se sono già in uso o disponibili per la selezione. Selezionare la voce desiderata dall\'elenco seguente. Si noti che non tutte le telecamere potrebbero essere rilevate e che la scelta di una telecamera qui può sovrascrivere tutti i valori già configurati per il monitor corrente.', // Added - 2015-04-18
    'OpEq'                 => 'uguale a',
    'OpGt'                 => 'maggiore di',
    'OpGtEq'               => 'maggiore o uguale a',
    'OpIn'                 => 'impostato',
    'OpIs'                 => 'è',                     // Added - 2018-08-30
    'OpIsNot'              => 'non è',                 // Added - 2018-08-30
    'OpLt'                 => 'minore di',
    'OpLtEq'               => 'minore o uguale a',
    'OpMatches'            => 'corrisponde',
    'OpNe'                 => 'diverso da',
    'OpNotIn'              => 'non impostato',
    'OpNotMatches'         => 'non corrisponde',
    'Open'                 => 'Apri',
    'OptionHelp'           => 'Opzioni di Aiuto',
    'OptionRestartWarning' => 'Queste modifiche potrebbero essere attive solo dopo un riavvio del sistema. Riavviare ZoneMinder.',
    'OptionalEncoderParam' => 'Parametri Encoder opzionali', // Added - 2018-08-30
    'Options'              => 'Opzioni',
    'OrEnterNewName'       => 'o inserisci un nuovo nome',
    'Order'                => 'Ordine',
    'Orientation'          => 'Orientamento',
    'Out'                  => 'Out',
    'OverwriteExisting'    => 'Sovrascrivi',
    'Paged'                => 'Con paginazione',
    'Pan'                  => 'Pan',
    'PanLeft'              => 'Pan Sinistra',
    'PanRight'             => 'Pan Destra',
    'PanTilt'              => 'Pan/Tilt',
    'Parameter'            => 'Parametri',
    'Password'             => 'Password',
    'PasswordsDifferent'   => 'Le password non coincidono',
    'Paths'                => 'Percorsi',
    'Pause'                => 'Pause',
    'Phone'                => 'Telefono',
    'PhoneBW'              => 'Banda Tel',
    'Pid'                  => 'PID',                    // Added - 2011-06-16
    'PixelDiff'            => 'Pixel Diff',
    'Pixels'               => 'pixels',
    'Play'                 => 'Play',
    'PlayAll'              => 'Vedi tutti',
    'PleaseWait'           => 'Attendere prego',
    'Plugins'              => 'Plugins',
    'Point'                => 'Punto',
    'PostEventImageBuffer' => 'Buffer immagini Dopo Evento',
    'PreEventImageBuffer'  => 'Buffer immagini Pre Evento',
    'PreserveAspect'       => 'Preserve Aspect Ratio',
    'Preset'               => 'Preset',
    'Presets'              => 'Predefiniti',
    'Prev'                 => 'Prec',
    'Probe'                => 'Prova la telecamera',                  // Added - 2009-03-31
    'ProfileProbe'         => 'Prova lo stream',           // Added - 2015-04-18
    'ProfileProbeIntro'    => 'L\'elenco seguente mostra i profili di streaming esistenti della telecamera selezionata.<br/><br/>Selezionare la voce desiderata dall\'elenco seguente.<br/><br/>Si noti che ZoneMinder non è in grado di configurare profili aggiuntivi e che la scelta di una telecamera qui può sovrascrivere qualsiasi valore già configurato per il monitor corrente.<br/><br/>', // Added - 2015-04-18
    'Progress'             => 'Progresso',               // Added - 2015-04-18
    'Protocol'             => 'Protocollo',
    'RTSPDescribe'         => 'Usa URL multimediale di risposta RTSP', // Added - 2018-08-30
    'RTSPTransport'        => 'RTSP Transport Protocol', // Added - 2018-08-30
    'Rate'                 => 'Velocità',
    'Real'                 => 'Reale',
    'RecaptchaWarning'     => 'La chiave segreta reCaptcha non è valida. Correggila o reCaptcha non funzionerà', // Added - 2018-08-30
    'Record'               => 'Registra',
    'RecordAudio'          => 'Memorizza flusso audio quando salva un evento.', // Added - 2018-08-30
    'RefImageBlendPct'     => 'Riferimento Miscela Immagine percentuale',
    'Refresh'              => 'Aggiorna',
    'Remote'               => 'Remoto',
    'RemoteHostName'       => 'Nome dell\'Host Remoto',
    'RemoteHostPath'       => 'Percorso dell\'Host Remoto',
    'RemoteHostPort'       => 'Porta dell\'Host Remoto',
    'RemoteHostSubPath'    => 'Percorso secondario dell\'Host remoto',    // Added - 2009-02-08
    'RemoteImageColours'   => 'Colori delle immagini Remote',
    'RemoteMethod'         => 'Metodo Remoto',          // Added - 2009-02-08
    'RemoteProtocol'       => 'Protocollo Remoto',        // Added - 2009-02-08
    'Rename'               => 'Rinomina',
    'Replay'               => 'Riproduci',
    'ReplayAll'            => 'Tutti gli Eventi',
    'ReplayGapless'        => 'Eventi continui',
    'ReplaySingle'         => 'Evento singolo',
    'ReportEventAudit'     => 'Controllo Eventi',    // Added - 2018-08-30
    'Reset'                => 'Reset',
    'ResetEventCounts'     => 'Reset Contatore Eventi',
    'Restart'              => 'Riavvia',
    'Restarting'           => 'Sto riavviando',
    'RestrictedCameraIds'  => 'Camera Ids Riservati',
    'RestrictedMonitors'   => 'Monitor limitati',
    'ReturnDelay'          => 'Ritardo del ritorno',
    'ReturnLocation'       => 'Posizione del ritorno',
    'Rewind'               => 'Riavvolgi',
    'RotateLeft'           => 'Ruota a Sinista',
    'RotateRight'          => 'Ruota a Destra',
    'RunLocalUpdate'       => 'Eseguire zmupdate.pl per l\'aggiornamento', // Added - 2011-05-25
    'RunMode'              => 'Modalità funzionamento',
    'RunState'             => 'Stato di funzionamento',
    'Running'              => 'Attivo',
    'Save'                 => 'Salva',
    'SaveAs'               => 'Salva come',
    'SaveFilter'           => 'Salva Filtro',
    'SaveJPEGs'            => 'Salva JPEGs',             // Added - 2018-08-30
    'Scale'                => 'Scala',
    'Score'                => 'Punteggio',
    'Secs'                 => 'Secs',
    'Sectionlength'        => 'Lunghezza Sezione',
    'Select'               => 'Seleziona',
    'SelectFormat'         => 'Seleziona Formato',          // Added - 2011-06-17
    'SelectLog'            => 'Seleziona Log',             // Added - 2011-06-17
    'SelectMonitors'       => 'Monitor Selezionati',
    'SelfIntersecting'     => 'I vertici del poligono non devono intersecarsi',
    'Set'                  => 'Imposta',
    'SetNewBandwidth'      => 'Imposta Nuova Banda',
    'SetPreset'            => 'Imposta Predefiniti',
    'Settings'             => 'Impostazioni',
    'ShowFilterWindow'     => 'Mostra Finestra Filtri',
    'ShowTimeline'         => 'Mostra linea temporale',
	'Show Zones'           => 'Visualizza Zone',
    'SignalCheckColour'    => 'Colore controllo segnale',
    'SignalCheckPoints'    => 'Punti di controllo segnale',    // Added - 2018-08-30
    'Size'                 => 'grandezza',
    'SkinDescription'      => 'Cambia la skin predefinita per questo computer', // Added - 2011-01-30
    'Sleep'                => 'Sospendi',
    'SortAsc'              => 'Crescente',
    'SortBy'               => 'Ordina per',
    'SortDesc'             => 'Decrescente',
    'Source'               => 'Sorgente',
    'SourceColours'        => 'Colori della Sorgente',         // Added - 2009-02-08
    'SourcePath'           => 'Percorso della Sorgente',            // Added - 2009-02-08
    'SourceType'           => 'Tipo Sorgente',
    'Speed'                => 'Velocità',
    'SpeedHigh'            => 'Alta Velocità',
    'SpeedLow'             => 'Bassa Velocità',
    'SpeedMedium'          => 'Media Velocità',
    'SpeedTurbo'           => 'Turbo Velocità',
    'Start'                => 'Avvia',
    'State'                => 'Stato',
    'Stats'                => 'Statistiche',
    'Status'               => 'Stato',
    'StatusConnected'      => 'Registrazione in corso',              // Added - 2018-08-30
    'StatusNotRunning'     => 'Non in esecuzione',            // Added - 2018-08-30
    'StatusRunning'        => 'Registrazione in pausa',          // Added - 2018-08-30
    'StatusUnknown'        => 'Sconosciuto',                // Added - 2018-08-30
    'Step'                 => 'Passo',
    'StepBack'             => 'Passo indietro',
    'StepForward'          => 'Passo avanti',
    'StepLarge'            => 'Passo lungo',
    'StepMedium'           => 'Passo medio',
    'StepNone'             => 'Nessun passo',
    'StepSmall'            => 'Passo piccolo',
    'Stills'               => 'Immagini fisse',
    'Stop'                 => 'Stop',
    'Stopped'              => 'Inattivo',
    'StorageArea'          => 'Area Archiviazione',           // Added - 2018-08-30
    'StorageScheme'        => 'Schema Archiviazione',                 // Added - 2018-08-30
    'Stream'               => 'Stream',
    'StreamReplayBuffer'   => 'Buffer immagini riproduzione stream',
    'Submit'               => 'Accetta',
    'System'               => 'Sistema',
    'SystemLog'            => 'Log di sistema',             // Added - 2011-06-16
    'TargetColorspace'     => 'Spazio dei colori obiettivo',      // Added - 2015-04-18
    'Tele'                 => 'Tele',
    'Thumbnail'            => 'Anteprima',
    'Tilt'                 => 'Tilt (Inclinazione)',
    'Time'                 => 'Ora',
    'TimeDelta'            => 'Differenza orario',
    'TimeStamp'            => 'Sovraimpressione data/orario',
    'Timeline'             => 'Linea Temporale',
    'TimelineTip1'          => 'Passa il mouse sul grafico per visualizzare un\'immagine dell\'istantanea e i dettagli dell\'evento.',              // Added 2013.08.15.
    'TimelineTip2'          => 'Fai clic sulle sezioni colorate del grafico o sull\'immagine per visualizzare l\'evento.',              // Added 2013.08.15.
    'TimelineTip3'          => 'Fare clic sullo sfondo per ingrandire un periodo di tempo più piccolo basato sul clic.',              // Added 2013.08.15.
    'TimelineTip4'          => 'Utilizzare i controlli seguenti per ridurre o spostarsi avanti e indietro nell\'intervallo di tempo.',              // Added 2013.08.15.
    'Timestamp'            => 'Sovraimpressione data/orario',
    'TimestampLabelFormat' => 'Formato etichetta Sovraimpressione data/orario',
    'TimestampLabelSize'   => 'Dimensione carattere',              // Added - 2018-08-30
    'TimestampLabelX'      => 'coordinata X etichetta',
    'TimestampLabelY'      => 'coordinata Y etichetta',
    'Today'                => 'Oggi ',
    'Tools'                => 'Strumenti',
    'Total'                => 'Totale',                  // Added - 2011-06-16
    'TotalBrScore'         => 'Punteggio Totale',
    'TrackDelay'           => 'Ritardo traccia',
    'TrackMotion'          => 'Segui movimento',
    'Triggers'             => 'Inneschi/Interruttori',
    'TurboPanSpeed'        => 'Velocità Turbo Pan',
    'TurboTiltSpeed'       => 'Velocità Turbo Tilt',
    'Type'                 => 'Tipo',
    'Unarchive'            => 'Togli dall\'archivio',
    'Undefined'            => 'Non specificato',              // Added - 2009-02-08
    'Units'                => 'Unità',
    'Unknown'              => 'Sconosciuto',
    'Update'               => 'Aggiorna',
    'UpdateAvailable'      => 'Un aggiornamento di ZoneMinder è disponibilie.',
    'UpdateNotNecessary'   => 'Nessun aggiornamento necessario.',
    'Updated'              => 'Aggiornato',                // Added - 2011-06-16
    'Upload'               => 'Carica',                 // Added - 2011-08-23
    'UseFilter'            => 'Usa Filtro',
    'UseFilterExprsPost'   => ' espressioni filtri', // This is used at the end of the phrase 'use N filter expressions'
    'UseFilterExprsPre'    => 'Usa&nbsp;', // This is used at the beginning of the phrase 'use N filter expressions'
    'UsedPlugins'		   => 'Plugins in uso',
    'User'                 => 'Utente',
    'Username'             => 'Nome Utente',
    'Users'                => 'Utenti',
    'V4L'                  => 'V4L',                    // Added - 2015-04-18
    'V4LCapturesPerFrame'  => 'Rilevamenti per immagine',     // Added - 2015-04-18
    'V4LMultiBuffer'       => 'Multi Buffering',        // Added - 2015-04-18
    'Value'                => 'Valore',
    'Version'              => 'Versione',
    'VersionIgnore'        => 'Ignora questa versione',
    'VersionRemindDay'     => 'Ricordami ancora tra un giorno',
    'VersionRemindHour'    => 'Ricordami ancora tra un\'ora',
    'VersionRemindNever'   => 'Non ricordarmi di nuove versioni',
    'VersionRemindWeek'    => 'Ricordami ancora tra una settimana',
    'Video'                => 'Video',
    'VideoFormat'          => 'Formato Video',
    'VideoGenFailed'       => 'Generazione Video Fallita!',
    'VideoGenFiles'        => 'File Video Esistenti',
    'VideoGenNoFiles'      => 'Non ho trovato file ',
    'VideoGenParms'        => 'Parametri Generazione Video',
    'VideoGenSucceeded'    => 'Successo: Video Generato!',
    'VideoSize'            => 'Dimensioni Video',
    'VideoWriter'          => 'Scrittore Video',           // Added - 2018-08-30
    'View'                 => 'Vedi',
    'ViewAll'              => 'Vedi Tutto',
    'ViewEvent'            => 'Vedi Evento',
    'ViewPaged'            => 'Vedi con paginazione',
    'Wake'                 => 'Riattiva',
    'WarmupFrames'         => 'Immagini Allerta',
    'Watch'                => 'Guarda',
    'Web'                  => 'Web',
    'WebColour'            => 'Colore Web',
    'WebSiteUrl'           => 'Website URL',            // Added - 2018-08-30
    'Week'                 => 'Settimana',
    'White'                => 'Bianco',
    'WhiteBalance'         => 'Bilanciamento del Bianco',
    'Wide'                 => 'Larghezza',
    'Width'                => 'Larghezza',
    'X'                    => 'X',
    'X10'                  => 'X10',
    'X10ActivationString'  => 'Stringa attivazione X10',
    'X10InputAlarmString'  => 'Stringa allarme input X10',
    'X10OutputAlarmString' => 'Stringa allarme output X10',
    'Y'                    => 'S',
    'Yes'                  => 'Si',
    'YouNoPerms'           => 'Non hai i permessi per accedere a questa risorsa.',
    'Zone'                 => 'Zona',
    'ZoneAlarmColour'      => 'Colore Allarme (RGB)',
    'ZoneArea'             => 'Zone Area',
    'ZoneExtendAlarmFrames' => 'Estendi conteggio immagini allarme',
    'ZoneFilterSize'       => 'Larghezza/Altezza Filtro (pixels)',
    'ZoneMinMaxAlarmArea'  => 'Min/Max Area Allarmata',
    'ZoneMinMaxBlobArea'   => 'Min/Max Area di Blob',
    'ZoneMinMaxBlobs'      => 'Min/Max Blobs',
    'ZoneMinMaxFiltArea'   => 'Min/Max Area Filtrata',
    'ZoneMinMaxPixelThres' => 'Min/Max Soglia Pixel (0-255)',
    'ZoneMinderLog'        => 'ZoneMinder Log',         // Added - 2011-06-17
    'ZoneOverloadFrames'   => 'Sovraccarico - contatore immagini ignorate',
    'Zones'                => 'Zone',
    'Zoom'                 => 'Zoom',
    'ZoomIn'               => 'Ingrandisci',
    'ZoomOut'              => 'Rimpicciolisci',
);

// Complex replacements with formatting and/or placements, must be passed through sprintf
$CLANG = array(
    'CurrentLogin'         => 'Login attuale: \'%1$s\'',
    'EventCount'           => '%1$s %2$s', // For example '37 Events' (from Vlang below)
    'LastEvents'           => 'Ultimi %1$s %2$s', // For example 'Last 37 Events' (from Vlang below)
    'LatestRelease'        => 'L\'ultima release v%1$s, tu hai v%2$s.',
    'MonitorCount'         => '%1$s %2$s', // For example '4 Monitors' (from Vlang below)
    'MonitorFunction'      => 'Funzione Monitor %1$s',
    'RunningRecentVer'     => 'Stai usando la versione pi&ugrave; aggiornata di ZoneMinder, v%s.',
    'VersionMismatch'      => 'Versioni non corrispondenti: versione sistema %1$s, versione database %2$s.', // Added - 2011-05-25
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
    'Event'                => array( 0=>'Eventi', 1=>'Evento', 2=>'Eventi' ),
    'Monitor'              => array( 0=>'Monitor', 1=>'Monitor', 2=>'Monitor' ),
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
    die( 'Errore, non sono in grado di correlare le stringhe del file-linguaggio');
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
		'Help' => "I parametri in questo campo vengono passati a FFmpeg. Più parametri possono essere separati da ,~~ ".
		          "Esempi (non inserire virgolette)~~~~".
		          "\"allowed_media_types=video\" Imposta il tipo di dati da richiedere dalla telecamera (audio, video, data)~~~~".
		          "\"reorder_queue_size=nnn\" Imposta il numero di pacchetti nel buffer per la gestione dei pacchetti riordinati~~~~".
		          "\"loglevel=debug\" Imposta la verbosità di FFmpeg (quiet, panic, fatal, error, warning, info, verbose, debug)"
	),
	'OPTIONS_LIBVLC' => array(
		'Help' => "I parametri in questo campo vengono passati a libVLC. Più parametri possono essere separati da ,~~ ".
		          "Esempi (non inserire virgolette)~~~~".
		          "\"--rtp-client-port=nnn\" Imposta la porta locale da utilizzare per i dati RTP~~~~". 
		          "\"--verbose=2\" Imposta la verbosità di of libVLC"
	),

	
//    'LANG_DEFAULT' => array(
//        'Prompt' => "This is a new prompt for this option",
//        'Help' => "This is some new help for this option which will be displayed in the window when the ? is clicked"
//    ),
);

?>
