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
$SLANG = array(
    '24BitColour'          => 'colori a 24 bit',
    '32BitColour'          => 'colori a 32 bit',          // Added - 2011-06-15
    '8BitGrey'             => '8 bit scala di grigio',
    'Action'               => 'Azione',
    'Actual'               => 'Attuale',
    'AddNewControl'        => 'Aggiungi nuovo Controllo',
    'AddNewMonitor'        => 'Aggiungi nuovo Monitor',
    'AddNewServer'         => 'Add New Server',         // Added - 2018-08-30
    'AddNewStorage'        => 'Add New Storage',        // Added - 2018-08-30
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
    'AlarmRefImageBlendPct'=> 'Alarm Reference Image Blend %ge', // Added - 2015-04-18
    'Alert'                => 'Attenzione',
    'All'                  => 'Tutto',
    'AnalysisFPS'          => 'Analysis FPS',           // Added - 2015-07-22
    'AnalysisUpdateDelay'  => 'Analysis Update Delay',  // Added - 2015-07-23
    'Apply'                => 'Applica',
    'ApplyingStateChange'  => 'Sto applicando le modifiche',
    'ArchArchived'         => 'Archiviato',
    'ArchUnarchived'       => 'Non archiviare',
    'Archive'              => 'Archivio',
    'Archived'             => 'Archiviato',
    'Area'                 => 'Area',
    'AreaUnits'            => 'Area (px/%)',
    'AttrAlarmFrames'      => 'Immagini in Allarme',
    'AttrArchiveStatus'    => 'Stato Archivio',
    'AttrAvgScore'         => 'Punteggio medio',
    'AttrCause'            => 'Causa',
    'AttrDiskBlocks'       => 'Blocchi del Disco',
    'AttrDiskPercent'      => 'Percentuale del Disco',
    'AttrDiskSpace'        => 'Disk Space',             // Added - 2018-08-30
    'AttrDuration'         => 'Durata',
    'AttrEndDate'          => 'End Date',               // Added - 2018-08-30
    'AttrEndDateTime'      => 'End Date/Time',          // Added - 2018-08-30
    'AttrEndTime'          => 'End Time',               // Added - 2018-08-30
    'AttrEndWeekday'       => 'End Weekday',            // Added - 2018-08-30
    'AttrFilterServer'     => 'Server Filter is Running On', // Added - 2018-08-30
    'AttrFrames'           => 'Immagini',
    'AttrId'               => 'Id',
    'AttrMaxScore'         => 'Punteggio massimo',
    'AttrMonitorId'        => 'Id Monitor',
    'AttrMonitorName'      => 'Nome Monitor',
    'AttrMonitorServer'    => 'Server Monitor is Running On', // Added - 2018-08-30
    'AttrName'             => 'Nome',
    'AttrNotes'            => 'Note',
    'AttrStartDate'        => 'Start Date',             // Added - 2018-08-30
    'AttrStartDateTime'    => 'Start Date/Time',        // Added - 2018-08-30
    'AttrStartTime'        => 'Start Time',             // Added - 2018-08-30
    'AttrStartWeekday'     => 'Start Weekday',          // Added - 2018-08-30
    'AttrStateId'          => 'Run State',              // Added - 2018-08-30
    'AttrStorageArea'      => 'Storage Area',           // Added - 2018-08-30
    'AttrStorageServer'    => 'Server Hosting Storage', // Added - 2018-08-30
    'AttrSystemLoad'       => 'System Load',
    'AttrTotalScore'       => 'Punteggio totale',
    'Auto'                 => 'Auto',
    'AutoStopTimeout'      => 'Auto Stop Timeout',
    'Available'            => 'Disponibile',              // Added - 2009-03-31
    'AvgBrScore'           => 'Punteggio<br/>medio',
    'Background'           => 'Background',
    'BackgroundFilter'     => 'Esegui filtro in background',
    'BadAlarmFrameCount'   => 'Il numero di frame di un allarme deve essere un numero intero superiore a uno',
    'BadAlarmMaxFPS'       => 'Il numero massimo di FPS dell\'allarme deve essere un numero intero positivo o un valore in virgola mobile',
    'BadAnalysisFPS'       => 'Analysis FPS must be a positive integer or floating point value', // Added - 2015-07-22
    'BadAnalysisUpdateDelay'=> 'Analysis update delay must be set to an integer of zero or more', // Added - 2015-07-23
    'BadChannel'           => 'Il canale deve essere settato con un numero intero uguale o maggiore di zero',
    'BadColours'           => 'Target colour must be set to a valid value', // Added - 2011-06-15
    'BadDevice'            => 'Il dispositivo deve essere impostato con un valore valido',
    'BadFPSReportInterval' => 'L\'intervallo di FPS per i report deve essere un numero intero superiore a 0',
    'BadFormat'            => 'Il formato deve essere impostato con un numero intero come 0 o maggiore',
    'BadFrameSkip'         => 'Il numero di Frame da scartare deve essere un intero uguale a 0 o superiore',
    'BadHeight'            => 'L\'altezza deve essere impostata con un valore valido',
    'BadHost'              => 'L\'host deve essere impostato con un indirizzo ip valido o con un hostname, non includendo http://',
    'BadImageBufferCount'  => 'La dimensione del buffer dell\'immagine deve essere impostata con un numero intero pari a 10 o maggiore',
    'BadLabelX'            => 'L\'etichetta della coordinata X deve essere un numero intero pari a 0 o maggiore',
    'BadLabelY'            => 'L\'etichetta della coordinata Y deve essere un numero intero pari a 0 o maggiore',
    'BadMaxFPS'            => 'I frame per secondo (FPS) massimi devono essere un numero intero positivo o un valore in virgola mobile',
    'BadMotionFrameSkip'   => 'Motion Frame skip count must be an integer of zero or more',
    'BadNameChars'         => 'I nomi possono contenere solo caratteri alfanumerici pi&ugrave; i caratteri - e _',
    'BadPalette'           => 'La palette dei colori deve essere impostata ad un valore valido', // Added - 2009-03-31
    'BadPath'              => 'Il percorso deve essere impostato con un valore valido',
    'BadPort'              => 'La porta deve essere settata con un valore valido',
    'BadPostEventCount'    => 'Il buffer d\'immagine successivo ad un evento deve essere un numero maggiore o uguale a zero',
    'BadPreEventCount'     => 'Il buffer d\'immagine antecedente ad un evento deve essere minimo 0 e comunque minore della dimensione del buffer d\'immagine',
    'BadRefBlendPerc'      => 'La percentuale di miscela di riferimento deve essere un intero positivo',
    'BadSectionLength'     => 'La lunghezza della sezione deve essere un numero intero pari a 30 o maggiore',
    'BadSignalCheckColour' => 'Signal check colour must be a valid RGB colour string',
    'BadSourceType'        => 'Source Type \"Web Site\" requires the Function to be set to \"Monitor\"', // Added - 2018-08-30
    'BadStreamReplayBuffer'=> 'Stream replay buffer must be an integer of zero or more',
    'BadWarmupCount'       => 'Il numero di frame di allarme deve essere un numero intero maggiore o uguale a zero',
    'BadWebColour'         => 'L\'identificativo del colore deve essere una stringa valida', 
    'BadWebSitePath'       => 'Please enter a complete website url, including the http:// or https:// prefix.', // Added - 2018-08-30
    'BadWidth'             => 'La larghezza deve essere impostata con un valore valido',
    'Bandwidth'            => 'Banda',
    'BandwidthHead'         => 'Bandwidth',	// This is the end of the bandwidth status on the top of the console, different in many language due to phrasing
    'BlobPx'               => 'Blob Px',
    'BlobSizes'            => 'Dimensioni Blob',
    'Blobs'                => 'Blobs',
    'Brightness'           => 'Luminosit&agrave;',
    'Buffer'               => 'Buffer',                 // Added - 2015-04-18
    'Buffers'              => 'Buffers',
    'CSSDescription'       => 'Change the default css for this computer', // Added - 2015-04-18
    'CanAutoFocus'         => 'Puo\' Auto Focus',
    'CanAutoGain'          => 'Puo\' Auto Gains',
    'CanAutoIris'          => 'Puo\' Auto Iris',
    'CanAutoWhite'         => 'Puo\' Auto bil bianco',
    'CanAutoZoom'          => 'Puo\' Auto Zoom',
    'CanFocus'             => 'Puo\' Fuoco',
    'CanFocusAbs'          => 'Puo\' Fuoco Assoluto',
    'CanFocusCon'          => 'Puo\' Fuoco Continuo ',
    'CanFocusRel'          => 'Puo\' Fuoco Relativo',
    'CanGain'              => 'Puo\' Gain ',
    'CanGainAbs'           => 'Puo\' Gain Assoluto',
    'CanGainCon'           => 'Puo\' Gain Continuo  ',
    'CanGainRel'           => 'Puo\' Gain Relativo',
    'CanIris'              => 'Puo\' Iris',
    'CanIrisAbs'           => 'Puo\' Iris Assoluto',
    'CanIrisCon'           => 'Puo\' Iris Continuo  ',
    'CanIrisRel'           => 'Puo\' Iris Relativo',
    'CanMove'              => 'Puo\' Mov.',
    'CanMoveAbs'           => 'Puo\' Mov. Assoluto',
    'CanMoveCon'           => 'Puo\' Mov. Continuo  ',
    'CanMoveDiag'          => 'Puo\' Mov. Diagonale ',
    'CanMoveMap'           => 'Puo\' Mov Mappato',
    'CanMoveRel'           => 'Puo\' Mov. Relativo',
    'CanPan'               => 'Puo\' Pan' ,
    'CanReset'             => 'Puo\' Reset',
	'CanReboot'             => 'Can Reboot',
    'CanSetPresets'        => 'Puo\' impostare preset',
    'CanSleep'             => 'Puo\' andare in sleep',
    'CanTilt'              => 'Puo\' Tilt',
    'CanWake'              => 'Puo\' essere riattivato',
    'CanWhite'             => 'Puo\' bilanciare il bianco',
    'CanWhiteAbs'          => 'Puo\' bilanciare il bianco assoluto',
    'CanWhiteBal'          => 'Puo\' bilanciare il bianco',
    'CanWhiteCon'          => 'Puo\' bilanciare il bianco Continuo',
    'CanWhiteRel'          => 'Puo\' bilanciare il bianco Relativo',
    'CanZoom'              => 'Puo\' Zoom',
    'CanZoomAbs'           => 'Puo\' Zoom Assoluto',
    'CanZoomCon'           => 'Puo\' Zoom Continuo',
    'CanZoomRel'           => 'Puo\' Zoom Relativo',
    'Cancel'               => 'Annulla',
    'CancelForcedAlarm'    => 'Annulla Allarme Forzato',
    'CaptureHeight'        => 'Altezza img catturata',
    'CaptureMethod'        => 'Metodo di cattura',         // Added - 2009-02-08
    'CapturePalette'       => 'Paletta img Catturata',
    'CaptureResolution'    => 'Capture Resolution',     // Added - 2015-04-18
    'CaptureWidth'         => 'Larghezza img Catturata',
    'Cause'                => 'Causa',
    'CheckMethod'          => 'Metodo di Controllo Allarme',
    'ChooseDetectedCamera' => 'Scegli telecamera rilevata', // Added - 2009-03-31
    'ChooseFilter'         => 'Scegli Filtro',
    'ChooseLogFormat'      => 'Choose a log format',    // Added - 2011-06-17
    'ChooseLogSelection'   => 'Choose a log selection', // Added - 2011-06-17
    'ChoosePreset'         => 'Scegli Preset',
    'Clear'                => 'Clear',                  // Added - 2011-06-16
    'CloneMonitor'         => 'Clone',                  // Added - 2018-08-30
    'Close'                => 'Chiudi',
    'Colour'               => 'Colori',
    'Command'              => 'Comando',
    'Component'            => 'Component',              // Added - 2011-06-16
    'ConcurrentFilter'     => 'Run filter concurrently', // Added - 2018-08-30
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
    'ControlCap'           => 'Capacita\' di controllo',
    'ControlCaps'          => 'Capacita\' di controllo',
    'ControlDevice'        => 'Dispositivo di controllo',
    'ControlType'          => 'Tipo Controllo',
    'Controllable'         => 'Controllabile',
    'Current'              => 'Current',                // Added - 2015-04-18
    'Cycle'                => 'Cicla',
    'CycleWatch'           => 'Vista Ciclica',
    'DateTime'             => 'Date/Time',              // Added - 2011-06-16
    'Day'                  => 'Giorno',
    'Debug'                => 'Debug',
    'DefaultRate'          => 'Default Rate',
    'DefaultScale'         => 'Scala di default',
    'DefaultView'          => 'Visualizzazione di default',
    'Deinterlacing'        => 'Deinterlacing',          // Added - 2015-04-18
    'Delay'                => 'Delay',                  // Added - 2015-04-18
    'Delete'               => 'Elimina',
    'DeleteAndNext'        => 'Elimina &amp; Prossimo',
    'DeleteAndPrev'        => 'Elimina &amp; Precedente',
    'DeleteSavedFilter'    => 'Elimina il filtro salvato',
    'Description'          => 'Descrizione',
    'DetectedCameras'      => 'Telecamere Rilevate',       // Added - 2009-03-31
    'DetectedProfiles'     => 'Detected Profiles',      // Added - 2015-04-18
    'Device'               => 'Periferica',                 // Added - 2009-02-08
    'DeviceChannel'        => 'Canale Periferica',
    'DeviceFormat'         => 'Formato',
    'DeviceNumber'         => 'Numero Periferica',
    'DevicePath'           => 'Percorso Dispositivo',
    'Devices'              => 'Dispositivi',
    'Dimensions'           => 'Dimensioni',
    'DisableAlarms'        => 'Disabil Allarme',
    'Disk'                 => 'Utilizzo Disco',
    'Display'              => 'Display',                // Added - 2011-01-30
    'Displaying'           => 'Displaying',             // Added - 2011-06-16
    'DoNativeMotionDetection'=> 'Do Native Motion Detection',
    'Donate'               => 'Donate,per favore',
    'DonateAlready'        => 'No, ho gia donato...    ',
    'DonateEnticement'     => 'Stai usando ZoneMinder da un po\' di tempo e spero che tu lo stia trovando utile per la sicurezza di casa tua o del tuo posto di lavoro..Anche se ZoneMinder e\' distribuito liberamente come software libero,costa soldi sia svilupparlo che supportarlo. Se preferisci che questo software continui ad avere supporto e sviluppo in futuro allora considera l\idea di fare una piccola donazione. Donare e\' ovviamente opzionale, ma apprezzato e puoi donare quanto vuoi,quel poco o tanto che tu desideri.<br><br>Se hai voglia per cortesia seleziona l\'opzione sotto o punta il tuo browser a https://zoneminder.com/donate/ .<br><br>Grazie per usare ZoneMinder e non dimenticare di visitare il forum in ZoneMinder.com se cerchi supporto o hai suggerimenti riguardo a come rendere migliore Zoneminder.',
    'DonateRemindDay'      => 'Non ancora, ricordamelo ancora tra 1 giorno',
    'DonateRemindHour'     => 'Non ancora, ricordamelo ancora tra 1 ora',
    'DonateRemindMonth'    => 'Non ancora, ricordamelo ancora tra 1 mese',
    'DonateRemindNever'    => 'No, io non voglio donare, non lo faro\' mai',
    'DonateRemindWeek'     => 'Non ancora, ricordamelo ancora tra 1 settimana',
    'DonateYes'            => 'Si,mi piacerebbe donare qualcosa ora',
    'Download'             => 'Download',
    'DownloadVideo'        => 'Download Video',         // Added - 2018-08-30
    'DuplicateMonitorName' => 'Il nome del monitor e\' gia\' presente', // Added - 2009-03-31
    'Duration'             => 'Durata',
    'Edit'                 => 'Modifica',
    'EditLayout'           => 'Edit Layout',            // Added - 2018-08-30
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
    'Exif'                 => 'Embed EXIF data into image', // Added - 2018-08-30
    'Export'               => 'Esporta',
    'ExportDetails'        => 'Esp. dettagli eventi',
    'ExportFailed'         => 'Esp. Fallita ',
    'ExportFormat'         => 'Formato File Esp. ',
    'ExportFormatTar'      => 'Tar',
    'ExportFormatZip'      => 'Zip',
    'ExportFrames'         => 'Dettagli frame espo.',
    'ExportImageFiles'     => 'Esporta le immagini',
    'ExportLog'            => 'Export Log',             // Added - 2011-06-17
    'ExportMiscFiles'      => 'Esporto Altri file (se presenti)',
    'ExportOptions'        => 'Opzioni Esportazione',
    'ExportSucceeded'      => 'Export completata con successo',       // Added - 2009-02-08
    'ExportVideoFiles'     => 'Esporto File Video (se presenti)',
    'Exporting'            => 'In corso.',
    'FPS'                  => 'fps',
    'FPSReportInterval'    => 'Intervallo Report FPS',
    'FTP'                  => 'FTP',
    'Far'                  => 'Lontano',
    'FastForward'          => 'Fast Forward',
    'Feed'                 => 'Feed',
    'Ffmpeg'               => 'Ffmpeg',                 // Added - 2009-02-08
    'File'                 => 'File',
    'Filter'               => 'Filter',                 // Added - 2015-04-18
    'FilterArchiveEvents'  => 'Archivia gli eventi',
    'FilterDeleteEvents'   => 'Elimina gli eventi',
    'FilterEmailEvents'    => 'Invia dettagli via email',
    'FilterExecuteEvents'  => 'Esegui un comando',
    'FilterLog'            => 'Filter log',             // Added - 2015-04-18
    'FilterMessageEvents'  => 'Invia dettagli tramite messaggio',
    'FilterMoveEvents'     => 'Move all matches',       // Added - 2018-08-30
    'FilterPx'             => 'Px Filtro',
    'FilterUnset'          => 'Devi specificare altezza e larghezza per il filtro',
    'FilterUpdateDiskSpace'=> 'Update used disk space', // Added - 2018-08-30
    'FilterUploadEvents'   => 'Fai upload eventi (FTP)',
    'FilterVideoEvents'    => 'Crea video per tutte le corrispondenze',
    'Filters'              => 'Filtri',
    'First'                => 'Primo',
    'FlippedHori'          => 'ribaltato orizzontale',
    'FlippedVert'          => 'ribaltato verticale',
    'FnMocord'              => 'Mocord',            // Added 2013.08.16.
    'FnModect'              => 'Modect',            // Added 2013.08.16.
    'FnMonitor'             => 'Monitor',            // Added 2013.08.16.
    'FnNodect'              => 'Nodect',            // Added 2013.08.16.
    'FnNone'                => 'None',            // Added 2013.08.16.
    'FnRecord'              => 'Record',            // Added 2013.08.16.
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
    'Gain'                 => 'Gain',
    'General'              => 'Generale',
    'GenerateDownload'     => 'Generate Download',      // Added - 2018-08-30
    'GenerateVideo'        => 'Genera Video',
    'GeneratingVideo'      => 'Sto generando il Video',
    'GoToZoneMinder'       => 'Vai su zoneminder.com',
    'Grey'                 => 'Grigio',
    'Group'                => 'Gruppo',
    'Groups'               => 'Gruppi',
    'HasFocusSpeed'        => 'Ha velocita\' di focus',
    'HasGainSpeed'         => 'Ha velocita\' di guadagno',
    'HasHomePreset'        => 'Ha posizioni di present',
    'HasIrisSpeed'         => 'Ha velocota\' di iris',
    'HasPanSpeed'          => 'Ha velocita\' di Pan',
    'HasPresets'           => 'Ha preset',
    'HasTiltSpeed'         => 'Ha velocita\' di Tilt',
    'HasTurboPan'          => 'Ha il Turbo Pan',
    'HasTurboTilt'         => 'Ha il Turbo Tilt',
    'HasWhiteSpeed'        => 'Ha velocita\' di bilanciamento del bianco',
    'HasZoomSpeed'         => 'Ha velocita\' di zoom',
    'High'                 => 'Alta',
    'HighBW'               => 'Banda&nbsp;Alta',
    'Home'                 => 'Home',
    'Hostname'             => 'Hostname',               // Added - 2018-08-30
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
    'Iris'                 => 'Iris',
    'KeyString'            => 'Stringa Chiave',
    'Label'                => 'Etichetta',
    'Language'             => 'Linguaggio',
    'Last'                 => 'Ultimo',
    'Layout'               => 'Layout',                 // Added - 2009-02-08
    'Level'                => 'Level',                  // Added - 2011-06-16
    'Libvlc'               => 'Libvlc',
    'LimitResultsPost'     => 'risultati;', // This is used at the end of the phrase 'Limit to first N results only'
    'LimitResultsPre'      => 'Limita ai primi', // This is used at the beginning of the phrase 'Limit to first N results only'
    'Line'                 => 'Line',                   // Added - 2011-06-16
    'LinkedMonitors'       => 'Monitor Collegati',
    'List'                 => 'Lista',
    'ListMatches'          => 'List Matches',           // Added - 2018-08-30
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
    'LowBW'                => 'Banda&nbsp;Bassa',
    'Main'                 => 'Principale',
    'Man'                  => 'Man',
    'Manual'               => 'Manuale',
    'Mark'                 => 'Seleziona',
    'Max'                  => 'Massima',
    'MaxBandwidth'         => 'Banda Massima',
    'MaxBrScore'           => 'Punteggio<br/>Massimo',
    'MaxFocusRange'        => 'Massimo range del focus',
    'MaxFocusSpeed'        => 'Massima velocita\' del focus',
    'MaxFocusStep'         => 'Massimo step del focus',
    'MaxGainRange'         => 'Massimo range del guadagno',
    'MaxGainSpeed'         => 'Massima velocita\' del guadagno',
    'MaxGainStep'          => 'Massimo step del guadagno',
    'MaxIrisRange'         => 'Massima range dell\'Iris',
    'MaxIrisSpeed'         => 'Massima velocita\' dell\'Iris',
    'MaxIrisStep'          => 'Massimo step dell\'Iris',
    'MaxPanRange'          => 'Massimo range del pan',
    'MaxPanSpeed'          => 'Massima velocita\' del tilt',
    'MaxPanStep'           => 'Massimo step del pan',
    'MaxTiltRange'         => 'Massimo range del tilt',
    'MaxTiltSpeed'         => 'Massima velocita\' del tilt',
    'MaxTiltStep'          => 'Massimo passo del tilt',
    'MaxWhiteRange'        => 'Massimo range del bilanciamento del bianco',
    'MaxWhiteSpeed'        => 'Massima velocita\' del bilanciamento del bianco',
    'MaxWhiteStep'         => 'Massimo Step del bilanciamento del bianco',
    'MaxZoomRange'         => 'Massimo range dello zoom',
    'MaxZoomSpeed'         => 'Massima velocita\' dello zoom',
    'MaxZoomStep'          => 'Massimo step dello zoom',
    'MaximumFPS'           => 'Massimi FPS',
    'Medium'               => 'Media',
    'MediumBW'             => 'Banda&nbsp;Media',
    'Message'              => 'Message',                // Added - 2011-06-16
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
    'MinFocusSpeed'        => 'Velocita\' minima del Focus',
    'MinFocusStep'         => 'Minimo step del Focus',
    'MinGainRange'         => 'Minimo range del Guadagno',
    'MinGainSpeed'         => 'Velocita\' minima del Guadagno',
    'MinGainStep'          => 'Step minimo del guadagno',
    'MinIrisRange'         => 'Range minimo dell\'Iris',
    'MinIrisSpeed'         => 'Velocita\' minima dell\'Iris',
    'MinIrisStep'          => 'Step minimo dell\'Iris',
    'MinPanRange'          => 'Range minimo del pan',
    'MinPanSpeed'          => 'Velocita\' minima del Pan',
    'MinPanStep'           => 'Step minimo del Pan',
    'MinPixelThresLtMax'   => 'I pixel minimi della soglia devono essere minori dei pixel massimi della soglia',
    'MinPixelThresUnset'   => 'Devi specificare una soglia minima di pixel', // Added - 2009-02-08
    'MinTiltRange'         => 'Range minimo del Tilt',
    'MinTiltSpeed'         => 'Velocita\' minima del Tilt',
    'MinTiltStep'          => 'Step minimo del Tilt',
    'MinWhiteRange'        => 'Range minimo del bilanciamento del bianco',
    'MinWhiteSpeed'        => 'Velocita\' minima del bialnciamento del bianco',
    'MinWhiteStep'         => 'Minimo step del bilanciamento del bianco',
    'MinZoomRange'         => 'Range minimo dello zoom',
    'MinZoomSpeed'         => 'Velocita\' minima dello zoom',
    'MinZoomStep'          => 'Step minimo dello zoom',
    'Misc'                 => 'Altro',
    'Mode'                 => 'Mode',                   // Added - 2015-04-18
    'Monitor'              => 'Monitor',
    'MonitorIds'           => 'Monitor&nbsp;Ids',
    'MonitorPreset'        => 'Monitor Presenti',
    'MonitorPresetIntro'   => 'Selezionare un appropriato pre settaggio dalla lista riportata qui sotto.<br><br>Per favore notare che questo potrebbe sovrascrivere ogni valore che hai gi&agrave;  configurato su questo monitor.<br><br>',
    'MonitorProbe'         => 'Monitor Probe',          // Added - 2009-03-31
    'MonitorProbeIntro'    => 'The list below shows detected analog and network cameras and whether they are already being used or available for selection.<br/><br/>Select the desired entry from the list below.<br/><br/>Please note that not all cameras may be detected and that choosing a camera here may overwrite any values you already have configured for the current monitor.<br/><br/>', // Added - 2009-03-31
    'Monitors'             => 'Monitors',
    'Montage'              => 'Montaggio',
    'MontageReview'        => 'Montage Review',         // Added - 2018-08-30
    'Month'                => 'Mese',
    'More'                 => 'More',                   // Added - 2011-06-16
    'MotionFrameSkip'      => 'Motion Frame Skip',
    'Move'                 => 'Sposta',
    'Mtg2widgrd'           => '2-wide grid',              // Added 2013.08.15.
    'Mtg3widgrd'           => '3-wide grid',              // Added 2013.08.15.
    'Mtg3widgrx'           => '3-wide grid, scaled, enlarge on alarm',              // Added 2013.08.15.
    'Mtg4widgrd'           => '4-wide grid',              // Added 2013.08.15.
    'MtgDefault'           => 'Default',              // Added 2013.08.15.
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
    'NoDetectedProfiles'   => 'No Detected Profiles',   // Added - 2018-08-30
    'NoFramesRecorded'     => 'Non ci sono immagini salvate per questo evento',
    'NoGroup'              => 'Nessun gruppo',               // Added - 2009-02-08
    'NoSavedFilters'       => 'NessunFiltroSalvato',
    'NoStatisticsRecorded' => 'Non ci sono statistiche salvate per questo evento/immagine',
    'None'                 => 'Nessuno',
    'NoneAvailable'        => 'Nessuno disponibile',
    'Normal'               => 'Normale',
    'Notes'                => 'Note',
    'NumPresets'           => 'Num Presets',
    'Off'                  => 'Off',
    'On'                   => 'On',
    'OnvifCredentialsIntro'=> 'Please supply user name and password for the selected camera.<br/>If no user has been created for the camera then the user given here will be created with the given password.<br/><br/>', // Added - 2015-04-18
    'OnvifProbe'           => 'ONVIF',                  // Added - 2015-04-18
    'OnvifProbeIntro'      => 'The list below shows detected ONVIF cameras and whether they are already being used or available for selection.<br/><br/>Select the desired entry from the list below.<br/><br/>Please note that not all cameras may be detected and that choosing a camera here may overwrite any values you already have configured for the current monitor.<br/><br/>', // Added - 2015-04-18
    'OpEq'                 => 'uguale a',
    'OpGt'                 => 'maggiore di',
    'OpGtEq'               => 'maggiore o uguale a',
    'OpIn'                 => 'impostato',
    'OpIs'                 => 'is',                     // Added - 2018-08-30
    'OpIsNot'              => 'is not',                 // Added - 2018-08-30
    'OpLt'                 => 'minore di',
    'OpLtEq'               => 'minore o uguale a',
    'OpMatches'            => 'corrisponde',
    'OpNe'                 => 'diverso da',
    'OpNotIn'              => 'non impostato',
    'OpNotMatches'         => 'non corrisponde',
    'Open'                 => 'Apri',
    'OptionHelp'           => 'Opzioni di Aiuto',
    'OptionRestartWarning' => 'Queste modifiche potrebbero essere attive solo dopo un riavvio del sistema. Riavviare ZoneMinder.',
    'OptionalEncoderParam' => 'Optional Encoder Parameters', // Added - 2018-08-30
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
    'PhoneBW'              => 'Banda&nbsp;Tel',
    'Pid'                  => 'PID',                    // Added - 2011-06-16
    'PixelDiff'            => 'Pixel Diff',
    'Pixels'               => 'pixels',
    'Play'                 => 'Play',
    'PlayAll'              => 'Vedi tutti',
    'PleaseWait'           => 'Attendere prego',
    'Plugins'              => 'Plugins',
    'Point'                => 'Punto',
    'PostEventImageBuffer' => 'Buffer di immagini Dopo Evento',
    'PreEventImageBuffer'  => 'Buffer di immagini Pre Evento',
    'PreserveAspect'       => 'Preserve Aspect Ratio',
    'Preset'               => 'Preset',
    'Presets'              => 'Presets',
    'Prev'                 => 'Prec',
    'Probe'                => 'Prova la telecamera',                  // Added - 2009-03-31
    'ProfileProbe'         => 'Stream Probe',           // Added - 2015-04-18
    'ProfileProbeIntro'    => 'The list below shows the existing stream profiles of the selected camera .<br/><br/>Select the desired entry from the list below.<br/><br/>Please note that ZoneMinder cannot configure additional profiles and that choosing a camera here may overwrite any values you already have configured for the current monitor.<br/><br/>', // Added - 2015-04-18
    'Progress'             => 'Progress',               // Added - 2015-04-18
    'Protocol'             => 'Protocol',
    'RTSPDescribe'         => 'Use RTSP Response Media URL', // Added - 2018-08-30
    'RTSPTransport'        => 'RTSP Transport Protocol', // Added - 2018-08-30
    'Rate'                 => 'Velocita\'',
    'Real'                 => 'Reale',
    'RecaptchaWarning'     => 'Your reCaptcha secret key is invalid. Please correct it, or reCaptcha will not work', // Added - 2018-08-30
    'Record'               => 'Registra',
    'RecordAudio'          => 'Whether to store the audio stream when saving an event.', // Added - 2018-08-30
    'RefImageBlendPct'     => 'Riferimento Miscela Immagine percentuale',
    'Refresh'              => 'Aggiorna',
    'Remote'               => 'Remoto',
    'RemoteHostName'       => 'Nome dell\'Host Remoto',
    'RemoteHostPath'       => 'Percorso dell\'Host Remoto',
    'RemoteHostPort'       => 'Porta dell\'Host Remoto',
    'RemoteHostSubPath'    => 'Remote Host SubPath',    // Added - 2009-02-08
    'RemoteImageColours'   => 'Colori delle immagini Remote',
    'RemoteMethod'         => 'Metodo Remoto',          // Added - 2009-02-08
    'RemoteProtocol'       => 'Protocollo Remoto',        // Added - 2009-02-08
    'Rename'               => 'Rinomina',
    'Replay'               => 'Replay',
    'ReplayAll'            => 'All Events',
    'ReplayGapless'        => 'Gapless Events',
    'ReplaySingle'         => 'Single Event',
    'ReportEventAudit'     => 'Audit Events Report',    // Added - 2018-08-30
    'Reset'                => 'Resetta',
    'ResetEventCounts'     => 'Resetta Contatore Eventi',
    'Restart'              => 'Riavvia',
    'Restarting'           => 'Sto riavviando',
    'RestrictedCameraIds'  => 'Camera Ids Riservati',
    'RestrictedMonitors'   => 'Monitor limitati',
    'ReturnDelay'          => 'Ritardo del ritorno',
    'ReturnLocation'       => 'Posizione del ritorno',
    'Rewind'               => 'Riavvolgi',
    'RotateLeft'           => 'Ruota a Sinista',
    'RotateRight'          => 'Ruota a Destra',
    'RunLocalUpdate'       => 'Please run zmupdate.pl to update', // Added - 2011-05-25
    'RunMode'              => 'Modalita\' funzionamento',
    'RunState'             => 'Stato di funzionamento',
    'Running'              => 'Attivo',
    'Save'                 => 'Salva',
    'SaveAs'               => 'Salva come',
    'SaveFilter'           => 'salva Filtro',
    'SaveJPEGs'            => 'Save JPEGs',             // Added - 2018-08-30
    'Scale'                => 'Scala',
    'Score'                => 'Punteggio',
    'Secs'                 => 'Secs',
    'Sectionlength'        => 'Lunghezza Sezione',
    'Select'               => 'Seleziona',
    'SelectFormat'         => 'Select Format',          // Added - 2011-06-17
    'SelectLog'            => 'Select Log',             // Added - 2011-06-17
    'SelectMonitors'       => 'Monitor Selezionati',
    'SelfIntersecting'     => 'I vertici del poligono non devono intersecarsi',
    'Set'                  => 'Imposta',
    'SetNewBandwidth'      => 'Imposta nuova Banda',
    'SetPreset'            => 'Imposta Preset',
    'Settings'             => 'Impostazioni',
    'ShowFilterWindow'     => 'MostraFinestraFiltri',
    'ShowTimeline'         => 'Mostra linea temporale',
    'SignalCheckColour'    => 'Signal Check Colour',
    'SignalCheckPoints'    => 'Signal Check Points',    // Added - 2018-08-30
    'Size'                 => 'grandezza',
    'SkinDescription'      => 'Change the default skin for this computer', // Added - 2011-01-30
    'Sleep'                => 'Sleep',
    'SortAsc'              => 'Cresc',
    'SortBy'               => 'Ordina per',
    'SortDesc'             => 'Decr',
    'Source'               => 'Sorgente',
    'SourceColours'        => 'Colori della Sorgente',         // Added - 2009-02-08
    'SourcePath'           => 'Percorso della Sorgente',            // Added - 2009-02-08
    'SourceType'           => 'Tipo Sorgente',
    'Speed'                => 'Velocita\'',
    'SpeedHigh'            => 'Alta Velocita\'',
    'SpeedLow'             => 'Bassa Velocita\'',
    'SpeedMedium'          => 'Media Velocita\'',
    'SpeedTurbo'           => 'Turbo Velocita\'',
    'Start'                => 'Avvia',
    'State'                => 'Stato',
    'Stats'                => 'Statistiche',
    'Status'               => 'Stato',
    'StatusConnected'      => 'Capturing',              // Added - 2018-08-30
    'StatusNotRunning'     => 'Not Running',            // Added - 2018-08-30
    'StatusRunning'        => 'Not Capturing',          // Added - 2018-08-30
    'StatusUnknown'        => 'Unknown',                // Added - 2018-08-30
    'Step'                 => 'Passo',
    'StepBack'             => 'Step Back',
    'StepForward'          => 'Step Forward',
    'StepLarge'            => 'Lungo passo',
    'StepMedium'           => 'Medio passo',
    'StepNone'             => 'No passo',
    'StepSmall'            => 'Piccolo passo',
    'Stills'               => 'Foto',
    'Stop'                 => 'Stop',
    'Stopped'              => 'Inattivo',
    'StorageArea'          => 'Storage Area',           // Added - 2018-08-30
    'StorageScheme'        => 'Scheme',                 // Added - 2018-08-30
    'Stream'               => 'Flusso',
    'StreamReplayBuffer'   => 'Stream Replay Image Buffer',
    'Submit'               => 'Accetta',
    'System'               => 'Sistema',
    'SystemLog'            => 'System Log',             // Added - 2011-06-16
    'TargetColorspace'     => 'Target colorspace',      // Added - 2015-04-18
    'Tele'                 => 'Tele',
    'Thumbnail'            => 'Anteprima',
    'Tilt'                 => 'Tilt',
    'Time'                 => 'Ora',
    'TimeDelta'            => 'Tempo di Delta',
    'TimeStamp'            => 'Time Stamp',
    'Timeline'             => 'Linea Temporale',
    'TimelineTip1'          => 'Pass your mouse over the graph to view a snapshot image and event details.',              // Added 2013.08.15.
    'TimelineTip2'          => 'Click on the coloured sections of the graph, or the image, to view the event.',              // Added 2013.08.15.
    'TimelineTip3'          => 'Click on the background to zoom in to a smaller time period based around your click.',              // Added 2013.08.15.
    'TimelineTip4'          => 'Use the controls below to zoom out or navigate back and forward through the time range.',              // Added 2013.08.15.
    'Timestamp'            => 'Timestamp',
    'TimestampLabelFormat' => 'Formato etichetta timestamp',
    'TimestampLabelSize'   => 'Font Size',              // Added - 2018-08-30
    'TimestampLabelX'      => 'coordinata X etichetta',
    'TimestampLabelY'      => 'coordinata Y etichetta',
    'Today'                => 'Oggi ',
    'Tools'                => 'Strumenti',
    'Total'                => 'Total',                  // Added - 2011-06-16
    'TotalBrScore'         => 'Punteggio<br/>Totale',
    'TrackDelay'           => 'Track Delay',
    'TrackMotion'          => 'Track Motion',
    'Triggers'             => 'Triggers',
    'TurboPanSpeed'        => 'Velocita\' Turbo Pan',
    'TurboTiltSpeed'       => 'Velocita\' Turbo Tilt',
    'Type'                 => 'Tipo',
    'Unarchive'            => 'Togli dall\'archivio',
    'Undefined'            => 'Non specificato',              // Added - 2009-02-08
    'Units'                => 'Unit&agrave;',
    'Unknown'              => 'Sconosciuto',
    'Update'               => 'Aggiorna',
    'UpdateAvailable'      => 'Un aggiornamento di ZoneMinder &egrave; disponibilie.',
    'UpdateNotNecessary'   => 'Nessun aggiornamento necessario.',
    'Updated'              => 'Updated',                // Added - 2011-06-16
    'Upload'               => 'Upload',                 // Added - 2011-08-23
    'UseFilter'            => 'Usa Filtro',
    'UseFilterExprsPost'   => '&nbsp;espressioni&nbsp;filtri', // This is used at the end of the phrase 'use N filter expressions'
    'UseFilterExprsPre'    => 'Usa&nbsp;', // This is used at the beginning of the phrase 'use N filter expressions'
    'UsedPlugins'	   => 'Used Plugins',
    'User'                 => 'Utente',
    'Username'             => 'Nome Utente',
    'Users'                => 'Utenti',
    'V4L'                  => 'V4L',                    // Added - 2015-04-18
    'V4LCapturesPerFrame'  => 'Captures Per Frame',     // Added - 2015-04-18
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
    'VideoGenSucceeded'    => 'Successo: Generato Video  !',
    'VideoSize'            => 'Dimensioni Video',
    'VideoWriter'          => 'Video Writer',           // Added - 2018-08-30
    'View'                 => 'vedi',
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
    'WhiteBalance'         => 'Bil. Bianco  ',
    'Wide'                 => 'Larghezza',
    'X'                    => 'X',
    'X10'                  => 'X10',
    'X10ActivationString'  => 'Stringa attivazione X10',
    'X10InputAlarmString'  => 'Stringa allarme input X10',
    'X10OutputAlarmString' => 'Stringa allarme output X10',
    'Y'                    => 'Y',
    'Yes'                  => 'Si',
    'YouNoPerms'           => 'Non hai i permessi per accedere a questa risorsa.',
    'Zone'                 => 'Zona',
    'ZoneAlarmColour'      => 'Colore Allarme (RGB)',
    'ZoneArea'             => 'Zone Area',
    'ZoneExtendAlarmFrames' => 'Extend Alarm Frame Count',
    'ZoneFilterSize'       => 'Larghezza/Altezza Filtro (pixels)',
    'ZoneMinMaxAlarmArea'  => 'Min/Max Area Allarmata',
    'ZoneMinMaxBlobArea'   => 'Min/Max Area di Blob',
    'ZoneMinMaxBlobs'      => 'Min/Max Blobs',
    'ZoneMinMaxFiltArea'   => 'Min/Max Area Filtrata',
    'ZoneMinMaxPixelThres' => 'Min/Max Soglia Pixel (0-255)',
    'ZoneMinderLog'        => 'ZoneMinder Log',         // Added - 2011-06-17
    'ZoneOverloadFrames'   => 'Overload Frame Ignore Count',
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
    die( 'Errore, sono incapace di correlare le stringhe del file-linguaggio');
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
