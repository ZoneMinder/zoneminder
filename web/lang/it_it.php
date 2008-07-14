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
$SLANG = array(
    '24BitColour'          => 'colori a 24 bit',
    '8BitGrey'             => '8 bit scala di grigio',
    'Action'               => 'Azione',
    'Actual'               => 'Attuale',
    'AddNewControl'        => 'Aggiungi nuovo Controllo',
    'AddNewMonitor'        => 'Aggiungi nuovo Monitor',
    'AddNewUser'           => 'Aggiungi nuovo Utente',
    'AddNewZone'           => 'Aggiungi nuova Zona',
    'Alarm'                => 'Allarme',
    'AlarmBrFrames'        => 'Immagini<br/>Allarme',
    'AlarmFrameCount'      => 'Allarme Conta frame',
    'AlarmFrame'           => 'Immagine Allarme',
    'AlarmLimits'          => 'Limiti Allarme',
    'AlarmMaximumFPS'      => 'FPS massimi durante l\'allarme',
    'AlarmPx'              => 'Pixel Allarme',
    'AlarmRGBUnset'        => 'Devi settare un colore RGB di allarme',
    'Alert'                => 'Attenzione',
    'All'                  => 'Tutto',
    'Apply'                => 'Applica',
    'ApplyingStateChange'  => 'Sto applicando le modifiche',
    'ArchArchived'         => 'Archiviato',
    'Archive'              => 'Archivio',
    'Archived'             => 'Archiviato',
    'ArchUnarchived'       => 'Non archiviare',
    'Area'                 => 'Area',
    'AreaUnits'            => 'Area (px/%)',
    'AttrAlarmFrames'      => 'Immagini in Allarme',
    'AttrArchiveStatus'    => 'Stato Archivio',
    'AttrAvgScore'         => 'Punteggio medio',
    'AttrCause'            => 'Causa',
    'AttrDate'             => 'Data',
    'AttrDateTime'         => 'Data/Ora',
    'AttrDiskBlocks'       => 'Blocchi del Disco',
    'AttrDiskPercent'      => 'Percentuale del Disco',
    'AttrDuration'         => 'Durata',
    'AttrFrames'           => 'Immagini',
    'AttrId'               => 'Id',
    'AttrMaxScore'         => 'Punteggio massimo',
    'AttrMonitorId'        => 'Id Monitor',
    'AttrMonitorName'      => 'Nome Monitor',
    'AttrName'             => 'Nome',
    'AttrNotes'            => 'Note',
    'AttrSystemLoad'       => 'Carico del Sistema',
    'AttrSystemLoad'       => 'System Load',
    'AttrTime'             => 'Ora',
    'AttrTotalScore'       => 'Punteggio totale',
    'AttrWeekday'          => 'Giorno della settimana',
    'Auto'                 => 'Auto',
    'AutoStopTimeout'      => 'Auto Stop Timeout',
    'AvgBrScore'           => 'Punteggio<br/>medio',
    'Background'           => 'Background',
    'BackgroundFilter'     => 'Esegui filtro in background',
    'BadAlarmFrameCount'   => 'Il numero di frame di un allarme deve essere un numero intero superiore a uno',
    'BadAlarmMaxFPS'       => 'Il numero massimo di FPS dell\'allarme deve essere un numero intero positivo o un valore in virgola mobile',
    'BadChannel'           => 'Il canale deve essere settato con un numero intero uguale o maggiore di zero',
    'BadDevice'            => 'Il dispositivo deve essere impostato con un valore valido',
    'BadFormat'            => 'Il formato deve essere impostato con un numero intero come 0 o maggiore',
    'BadFPSReportInterval' => 'L\'intervallo di FPS per i report deve essere un numero intero superiore a 100',
    'BadFrameSkip'         => 'Il numero di Frame da scartare deve essere un intero uguale a 0 o superiore',
    'BadHeight'            => 'L\'altezza deve essere impostata con un valore valido',
    'BadHost'              => 'L\'host deve essere impostato con un indirizzo ip valido o con un hostname, non includendo http://',
    'BadImageBufferCount'  => 'La dimensione del buffer dell\'immagine deve essere impostata con un numero intero pari a 10 o maggiore',
    'BadLabelX'            => 'L\'etichetta della coordinata X deve essere un numero intero pari a 0 o maggiore',
    'BadLabelY'            => 'L\'etichetta della coordinata Y deve essere un numero intero pari a 0 o maggiore',
    'BadMaxFPS'            => 'I frame per secondo (FPS) massimi devono essere un numero intero positivo o un valore in virgola mobile',
    'BadNameChars'         => 'I nomi possono contenere solo caratteri alfanumerici pi&ugrave; i caratteri - e _',
    'BadPath'              => 'Il percorso deve essere impostato con un valore valido',
    'BadPort'              => 'La porta deve essere settata con un valore valido',
    'BadPostEventCount'    => 'Il buffer d\'immagine successivo ad un evento deve essere un numero maggiore o uguale a zero',
    'BadPreEventCount'     => 'Il buffer d\'immagine antecedente ad un evento deve essere minimo 0 e comunque minore della dimensione del buffer d\'immagine',
    'BadRefBlendPerc'      => 'La percentuale di miscela di riferimento deve essere un intero positivo',
    'BadSectionLength'     => 'La lunghezza della sezione deve essere un numero intero pari a 30 o maggiore',
    'BadSignalCheckColour' => 'Il colore del controllo di segnale deve essere una stringa RGB valida',
    'BadSignalCheckColour' => 'Signal check colour must be a valid RGB colour string',
    'BadStreamReplayBuffer'=> 'Stream replay buffer must be an integer of zero or more',
    'BadWarmupCount'       => 'Il numero di frame di allarme deve essere un numero intero maggiore o uguale a zero',
    'BadWebColour'         => 'L\'identificativo del colore deve essere una stringa valida', 
    'BadWidth'             => 'La larghezza deve essere impostata con un valore valido',
    'Bandwidth'            => 'Banda',
    'BlobPx'               => 'Blob Px',
    'Blobs'                => 'Blobs',
    'BlobSizes'            => 'Dimensioni Blob',
    'Brightness'           => 'Luminosit&agrave;',
    'Buffers'              => 'Buffers',
    'CanAutoFocus'         => 'Puo\' Auto Focus',
    'CanAutoGain'          => 'Puo\' Auto Gains',
    'CanAutoIris'          => 'Puo\' Auto Iris',
    'CanAutoWhite'         => 'Puo\' Auto bil bianco',
    'CanAutoZoom'          => 'Puo\' Auto Zoom',
    'Cancel'               => 'Annulla',
    'CancelForcedAlarm'    => 'Annulla Allarme Forzato',
    'CanFocusAbs'          => 'Puo\' Fuoco Assoluto',
    'CanFocusCon'          => 'Puo\' Fuoco Continuo ',
    'CanFocus'             => 'Puo\' Fuoco',
    'CanFocusRel'          => 'Puo\' Fuoco Relativo',
    'CanGainAbs'           => 'Puo\' Gain Assoluto',
    'CanGainCon'           => 'Puo\' Gain Continuo  ',
    'CanGain'              => 'Puo\' Gain ',
    'CanGainRel'           => 'Puo\' Gain Relativo',
    'CanIrisAbs'           => 'Puo\' Iris Assoluto',
    'CanIrisCon'           => 'Puo\' Iris Continuo  ',
    'CanIris'              => 'Puo\' Iris',
    'CanIrisRel'           => 'Puo\' Iris Relativo',
    'CanMoveAbs'           => 'Puo\' Mov. Assoluto',
    'CanMoveCon'           => 'Puo\' Mov. Continuo  ',
    'CanMoveDiag'          => 'Puo\' Mov. Diagonale ',
    'CanMoveMap'           => 'Puo\' Mov Mappato',
    'CanMove'              => 'Puo\' Mov.',
    'CanMoveRel'           => 'Puo\' Mov. Relativo',
    'CanPan'               => 'Puo\' Pan' ,
    'CanReset'             => 'Puo\' Reset',
    'CanSetPresets'        => 'Puo\' impostare preset',
    'CanSleep'             => 'Puo\' andare in sleep',
    'CanTilt'              => 'Puo\' Tilt',
    'CanWake'              => 'Puo\' essere riattivato',
    'CanWhiteAbs'          => 'Puo\' bilanciare il bianco assoluto',
    'CanWhiteBal'          => 'Puo\' bilanciare il bianco',
    'CanWhiteCon'          => 'Puo\' bilanciare il bianco Continuo',
    'CanWhite'             => 'Puo\' bilanciare il bianco',
    'CanWhiteRel'          => 'Puo\' bilanciare il bianco Relativo',
    'CanZoomAbs'           => 'Puo\' Zoom Assoluto',
    'CanZoomCon'           => 'Puo\' Zoom Continuo',
    'CanZoom'              => 'Puo\' Zoom',
    'CanZoomRel'           => 'Puo\' Zoom Relativo',
    'CaptureHeight'        => 'Altezza img catturata',
    'CapturePalette'       => 'Paletta img Catturata',
    'CaptureWidth'         => 'Larghezza img Catturata',
    'Cause'                => 'Causa',
    'CheckMethod'          => 'Metodo di Controllo Allarme',
    'ChooseFilter'         => 'Scegli Filtro',
    'ChoosePreset'         => 'Scegli Preset',
    'Close'                => 'Chiudi',
    'Colour'               => 'Colori',
    'Command'              => 'Comando',
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
    'ControlAddress'       => 'Indirizzo di controllo',
    'ControlCap'           => 'Capacita\' di controllo',
    'ControlCaps'          => 'Capacita\' di controllo',
    'Control'              => 'Controllo',
    'ControlDevice'        => 'Dispositivo di controllo',
    'Controllable'         => 'Controllabile',
    'ControlType'          => 'Tipo Controllo',
    'Cycle'                => 'Cicla',
    'CycleWatch'           => 'Vista Ciclica',
    'Day'                  => 'Giorno',
    'Debug'                => 'Debug',
    'DefaultRate'          => 'Default Rate',
    'DefaultScale'         => 'Scala di default',
    'DefaultView'          => 'Visualizzazione di default',
    'DeleteAndNext'        => 'Elimina &amp; Prossimo',
    'DeleteAndPrev'        => 'Elimina &amp; Precedente',
    'Delete'               => 'Elimina',
    'DeleteSavedFilter'    => 'Elimina il filtro salvato',
    'Description'          => 'Descrizione',
    'DeviceChannel'        => 'Canale Periferica',
    'DeviceFormat'         => 'Formato',
    'DeviceNumber'         => 'Numero Periferica',
    'DevicePath'           => 'Percorso Dispositivo',
    'Devices'              => 'Dispositivi',
    'Dimensions'           => 'Dimensioni',
    'DisableAlarms'        => 'Disabil Allarme',
    'Disk'                 => 'Utilizzo Disco',
    'DonateAlready'        => 'No, ho gia donato...    ',
    'Donate'               => 'Donate,per favore',
    'DonateEnticement'     => 'Stai usando ZoneMinder da un po\' di tempo e spero che tu lo stia trovando utile per la sicurezza di casa tua o del tuo posto di lavoro..Anche se ZoneMinder e\' distribuito liberamente come software libero,costa soldi sia svilupparlo che supportarlo. Se preferisci che questo software continui ad avere supporto e sviluppo in futuro allora considera l\idea di fare una piccola donazione. Donare e\' ovviamente opzionale, ma apprezzato e puoi donare quanto vuoi,quel poco o tanto che tu desideri.<br><br>Se hai voglia per cortesia seleziona l\'opzione sotto o punta il tuo browser a http://www.zoneminder.com/donate.html .<br><br>Grazie per usare ZoneMinder e non dimenticare di visitare il forum in ZoneMinder.com se cerchi supporto o hai suggerimenti riguardo a come rendere migliore Zoneminder.',
    'DonateRemindDay'      => 'Non ancora, ricordamelo ancora tra 1 giorno',
    'DonateRemindHour'     => 'Non ancora, ricordamelo ancora tra 1 ora',
    'DonateRemindMonth'    => 'Non ancora, ricordamelo ancora tra 1 mese',
    'DonateRemindNever'    => 'No, io non voglio donare, non lo faro\' mai',
    'DonateRemindWeek'     => 'Non ancora, ricordamelo ancora tra 1 settimana',
    'DonateYes'            => 'Si,mi piacerebbe donare qualcosa ora',
    'Download'             => 'Download',
    'Duration'             => 'Durata',
    'Edit'                 => 'Modifica',
    'Email'                => 'Email',
    'EnableAlarms'         => 'Abilita Allarmi',
    'Enabled'              => 'Attivo',
    'EnterNewFilterName'   => 'Inserisci il nome del nuovo filtro',
    'ErrorBrackets'        => 'Errore, controlla di avere un ugual numero di parentesti aperte e chiuse.',
    'Error'                => 'Errore',
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
    'ExportDetails'        => 'Esp. dettagli eventi',
    'Export'               => 'Esporta',
    'ExportFailed'         => 'Esp. Fallita ',
    'ExportFormat'         => 'Formato File Esp. ',
    'ExportFormatTar'      => 'Tar',
    'ExportFormatZip'      => 'Zip',
    'ExportFrames'         => 'Dettagli frame espo.',
    'ExportImageFiles'     => 'Esporta le immagini',
    'Exporting'            => 'In corso.',
    'ExportMiscFiles'      => 'Esporto Altri file (se presenti)',
    'ExportOptions'        => 'Opzioni Esportazione',
    'ExportVideoFiles'     => 'Esporto File Video (se presenti)',
    'Far'                  => 'Lontano',
    'FastForward'          => 'Avanti Veloce',
    'FastForward'          => 'Fast Forward',
    'Feed'                 => 'Feed',
    'FileColours'          => 'Colori File',
    'File'                 => 'File',
    'FilePath'             => 'Percorso File',
    'FilterArchiveEvents'  => 'Archivia gli eventi',
    'FilterDeleteEvents'   => 'Elimina gli eventi',
    'FilterEmailEvents'    => 'Invia dettagli via email',
    'FilterExecuteEvents'  => 'Esegui un comando',
    'FilterMessageEvents'  => 'Invia dettagli tramite messaggio',
    'FilterPx'             => 'Px Filtro',
    'Filters'              => 'Filtri',
    'FilterUnset'          => 'Devi specificare altezza e larghezza per il filtro',
    'FilterUploadEvents'   => 'Fai upload eventi (FTP)',
    'FilterVideoEvents'    => 'Crea video per tutte le corrispondenze',
    'First'                => 'Primo',
    'FlippedHori'          => 'ribaltato orizzontale',
    'FlippedVert'          => 'ribaltato verticale',
    'Focus'                => 'Focus',
    'ForceAlarm'           => 'Forza Allarme',
    'Format'               => 'Formato',
    'FPS'                  => 'fps',
    'FPSReportInterval'    => 'Intervallo Report FPS',
    'FrameId'              => 'Id Immagine',
    'Frame'                => 'Immagini',
    'FrameRate'            => 'Immagini al secondo',
    'Frames'               => 'Immagini',
    'FrameSkip'            => 'Immagini saltate',
    'FTP'                  => 'FTP',
    'Func'                 => 'Funz',
    'Function'             => 'Funzione',
    'Gain'                 => 'Gain',
    'General'              => 'Generale',
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
    'Hour'                 => 'Ora',
    'Hue'                  => 'Tinta',
    'Id'                   => 'Id',
    'Idle'                 => 'Inattivo',
    'Ignore'               => 'Ignora',
    'ImageBufferSize'      => 'Grandezza Buffer Immagine (frames)',
    'Image'                => 'Immagine',
    'Images'               => 'Immagini',
    'Include'              => 'Includi',
    'In'                   => 'In',
    'Inverted'             => 'Invertito',
    'Iris'                 => 'Iris',
    'KeyString'            => 'Stringa Chiave',
    'Label'                => 'Etichetta',
    'Language'             => 'Linguaggio',
    'Last'                 => 'Ultimo',
    'LimitResultsPost'     => 'risultati;', // This is used at the end of the phrase 'Limit to first N results only'
    'LimitResultsPre'      => 'Limita ai primi', // This is used at the beginning of the phrase 'Limit to first N results only'
    'LinkedMonitors'       => 'Monitor Collegati',
    'List'                 => 'Lista',
    'Load'                 => 'Carico Sistema',
    'Local'                => 'Locale',
    'LoggedInAs'           => 'Collegato come:',
    'LoggingIn'            => 'Mi Sto Collegando',
    'Login'                => 'Login',
    'Logout'               => 'Logout',
    'Low'                  => 'Bassa',
    'LowBW'                => 'Banda&nbsp;Bassa',
    'Main'                 => 'Principale',
    'Man'                  => 'Man',
    'Manual'               => 'Manuale',
    'Mark'                 => 'Seleziona',
    'MaxBandwidth'         => 'Banda Massima',
    'MaxBrScore'           => 'Punteggio<br/>Massimo',
    'MaxFocusRange'        => 'Massimo range del focus',
    'MaxFocusSpeed'        => 'Massima velocita\' del focus',
    'MaxFocusStep'         => 'Massimo step del focus',
    'MaxGainRange'         => 'Massimo range del guadagno',
    'MaxGainSpeed'         => 'Massima velocita\' del guadagno',
    'MaxGainStep'          => 'Massimo step del guadagno',
    'MaximumFPS'           => 'Massimi FPS',
    'MaxIrisRange'         => 'Massima range dell\'Iris',
    'MaxIrisSpeed'         => 'Massima velocita\' dell\'Iris',
    'MaxIrisStep'          => 'Massimo step dell\'Iris',
    'Max'                  => 'Massima',
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
    'MediumBW'             => 'Banda&nbsp;Media',
    'Medium'               => 'Media',
    'MinAlarmAreaLtMax'    => 'L\'area minima dell\'allarme deve essere minore di quella massima',
    'MinAlarmAreaUnset'    => 'Devi specificare il numero minimo di pixel per l\'allarme',
    'MinAlarmGeMinBlob'    => 'I pixel minimi dell\'allarme devono essere grandi almeno quanto i pixel minimi del blob',
    'MinAlarmGeMinFilter'  => 'I pixel minimi dell\'allarme devono essere grandi almeno quanto i pixel minimi del filtro',
    'MinAlarmPixelsLtMax'  => 'I pixel minimi dell\'allarme devono essere minori dei pixel massimi dell\'allarme',
    'MinBlobAreaLtMax'     => 'L\'area di blob minima deve essere minore dell\'area di blob massima',
    'MinBlobAreaUnset'     => 'Devi specificare il numero minimo di pixel per il blob',
    'MinBlobLtMinFilter'   => 'L\'area minima di blob deve essere minore o uguale dell\'area minima del filtro',
    'MinBlobsLtMax'        => 'I blob minimi devono essere minori dei blob massimi',
    'MinBlobsUnset'        => 'Devi specificare il numero minimo di blob',
    'MinFilterAreaLtMax'   => 'L\'area minima del filtro deve essere minore di quella massima',
    'MinFilterAreaUnset'   => 'Devi specificare il numero minimo di pixel per il filtro',
    'MinFilterLtMinAlarm'  => 'L\'area minima di filtro deve essere minore o uguale dell\area minima di allarme',
    'MinFilterPixelsLtMax' => 'I pixel minimi del filtro devono essere minori di pixel massimi del filtro',
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
    'MonitorIds'           => 'Monitor&nbsp;Ids',
    'Monitor'              => 'Monitor',
    'MonitorPresetIntro'   => 'Selezionare un appropriato pre settaggio dalla lista riportata qui sotto.<br><br>Per favore notare che questo potrebbe sovrascrivere ogni valore che hai già configurato su questo monitor.<br><br>',
    'MonitorPreset'        => 'Monitor Presenti',
    'Monitors'             => 'Monitors',
    'Montage'              => 'Montaggio',
    'Month'                => 'Mese',
    'Move'                 => 'Sposta',
    'MustBeGe'             => 'deve essere superiore a',
    'MustBeLe'             => 'deve essere inferiore o pari a',
    'MustConfirmPassword'  => 'Devi confermare la password',
    'MustSupplyPassword'   => 'Devi inserire una password',
    'MustSupplyUsername'   => 'Devi specificare un nome utente',
    'Name'                 => 'Nome',
    'Near'                 => 'Vicino',
    'Network'              => 'Rete',
    'NewGroup'             => 'Nuovo Gruppo',
    'NewLabel'             => 'Nuova Etichetta',
    'New'                  => 'Nuovo',
    'NewPassword'          => 'Nuova Password',
    'NewState'             => 'Nuovo Stato',
    'NewUser'              => 'Nuovo Utente',
    'Next'                 => 'Prossimo',
    'NoFramesRecorded'     => 'Non ci sono immagini salvate per questo evento',
    'NoGroups'             => 'Nessun Gruppo e\' stato definito',
    'NoneAvailable'        => 'Nessuno disponibile',
    'None'                 => 'Nessuno',
    'No'                   => 'No',
    'Normal'               => 'Normale',
    'NoSavedFilters'       => 'NessunFiltroSalvato',
    'NoStatisticsRecorded' => 'Non ci sono statistiche salvate per questo evento/immagine',
    'Notes'                => 'Note',
    'NumPresets'           => 'Num Presets',
    'Off'                  => 'Off',
    'On'                   => 'On',
    'Open'                 => 'Apri',
    'OpEq'                 => 'uguale a',
    'OpGtEq'               => 'maggiore o uguale a',
    'OpGt'                 => 'maggiore di',
    'OpIn'                 => 'impostato',
    'OpLtEq'               => 'minore o uguale a',
    'OpLt'                 => 'minore di',
    'OpMatches'            => 'corrisponde',
    'OpNe'                 => 'diverso da',
    'OpNotIn'              => 'non impostato',
    'OpNotMatches'         => 'non corrisponde',
    'OptionHelp'           => 'Opzioni di Aiuto',
    'OptionRestartWarning' => 'Queste modifiche potrebbero essere attive solo dopo un riavvio del sistema. Riavviare ZoneMinder.',
    'Options'              => 'Opzioni',
    'Order'                => 'Ordine',
    'OrEnterNewName'       => 'o inserisci un nuovo nome',
    'Orientation'          => 'Orientamento',
    'Out'                  => 'Out',
    'OverwriteExisting'    => 'Sovrascrivi',
    'Paged'                => 'Con paginazione',
    'PanLeft'              => 'Pan Sinistra',
    'Pan'                  => 'Pan',
    'PanRight'             => 'Pan Destra',
    'PanTilt'              => 'Pan/Tilt',
    'Parameter'            => 'Parametri',
    'Password'             => 'Password',
    'PasswordsDifferent'   => 'Le password non coincidono',
    'Paths'                => 'Percorsi',
    'Pause'                => 'Pausa',
    'Pause'                => 'Pause',
    'PhoneBW'              => 'Banda&nbsp;Tel',
    'Phone'                => 'Telefono',
    'PixelDiff'            => 'Pixel Diff',
    'Pixels'               => 'pixels',
    'PlayAll'              => 'Vedi tutti',
    'Play'                 => 'Play',
    'PleaseWait'           => 'Attendere prego',
    'Point'                => 'Punto',
    'PostEventImageBuffer' => 'Buffer di immagini Dopo Evento',
    'PreEventImageBuffer'  => 'Buffer di immagini Pre Evento',
    'PreserveAspect'       => 'Preserve Aspect Ratio',
    'Preset'               => 'Preset',
    'Presets'              => 'Presets',
    'Prev'                 => 'Prec',
    'Protocol'             => 'Protocol',
    'Rate'                 => 'Velocita\'',
    'Real'                 => 'Reale',
    'Record'               => 'Registra',
    'RefImageBlendPct'     => 'Riferimento Miscela Immagine percentuale',
    'Refresh'              => 'Aggiorna',
    'RemoteHostName'       => 'Nome dell\'Host Remoto',
    'RemoteHostPath'       => 'Percorso dell\'Host Remoto',
    'RemoteHostPort'       => 'Porta dell\'Host Remoto',
    'RemoteImageColours'   => 'Colori delle immagini Remote',
    'Remote'               => 'Remoto',
    'Rename'               => 'Rinomina',
    'ReplayAll'            => 'All Events',
    'ReplayGapless'        => 'Gapless Events',
    'Replay'               => 'Replay',
    'ReplaySingle'         => 'Single Event',
    'ResetEventCounts'     => 'Resetta Contatore Eventi',
    'Reset'                => 'Resetta',
    'Restarting'           => 'Sto riavviando',
    'Restart'              => 'Riavvia',
    'RestrictedCameraIds'  => 'Camera Ids Riservati',
    'RestrictedMonitors'   => 'Monitor limitati',
    'ReturnDelay'          => 'Ritardo del ritorno',
    'ReturnLocation'       => 'Posizione del ritorno',
    'Rewind'               => 'Rewind',
    'Rewind'               => 'Riavvolgi',
    'RotateLeft'           => 'Ruota a Sinista',
    'RotateRight'          => 'Ruota a Destra',
    'RunMode'              => 'Modalita funzionamento',
    'Running'              => 'Avviato',
    'RunState'             => 'Stato Avviato',
    'SaveAs'               => 'Salva come',
    'SaveFilter'           => 'salva Filtro',
    'Save'                 => 'Salva',
    'Scale'                => 'Scala',
    'Score'                => 'Punteggio',
    'Secs'                 => 'Secs',
    'Sectionlength'        => 'Lunghezza Sezione',
    'SelectMonitors'       => 'Monitor Selezionati',
    'Select'               => 'Seleziona',
    'SelfIntersecting'     => 'I vertici del poligono non devono intersecarsi',
    'Set'                  => 'Imposta',
    'SetLearnPrefs'        => 'Seleziona le preferenze di autoapprendimento', // This can be ignored for now
    'SetNewBandwidth'      => 'Imposta nuova Banda',
    'SetPreset'            => 'Imposta Preset',
    'Settings'             => 'Impostazioni',
    'ShowFilterWindow'     => 'MostraFinestraFiltri',
    'ShowTimeline'         => 'Mostra linea temporale',
    'SignalCheckColour'    => 'Colore del controllo di segnale',
    'SignalCheckColour'    => 'Signal Check Colour',
    'Size'                 => 'grandezza',
    'Sleep'                => 'Sleep',
    'SortAsc'              => 'Cresc',
    'SortBy'               => 'Ordina per',
    'SortDesc'             => 'Decr',
    'Source'               => 'Sorgente',
    'SourceType'           => 'Tipo Sorgente',
    'SpeedHigh'            => 'Alta Velocita\'',
    'SpeedLow'             => 'Bassa Velocita\'',
    'SpeedMedium'          => 'Media Velocita\'',
    'SpeedTurbo'           => 'Turbo Velocita\'',
    'Speed'                => 'Velocita\'',
    'Start'                => 'Avvia',
    'State'                => 'Stato',
    'Stats'                => 'Statistiche',
    'Status'               => 'Stato',
    'StepBack'             => 'Step Back',
    'StepForward'          => 'Step Forward',
    'StepLarge'            => 'Lungo passo',
    'StepMedium'           => 'Medio passo',
    'StepNone'             => 'No passo',
    'Step'                 => 'Passo',
    'StepSmall'            => 'Piccolo passo',
    'Stills'               => 'Foto',
    'Stopped'              => 'Fermo-immagine',
    'Stop'                 => 'Stop',
    'Stream'               => 'Flusso',
    'StreamReplayBuffer'   => 'Stream Replay Image Buffer',
    'Submit'               => 'Accetta',
    'System'               => 'Sistema',
    'Tele'                 => 'Tele',
    'Thumbnail'            => 'Anteprima',
    'Tilt'                 => 'Tilt',
    'TimeDelta'            => 'Tempo di Delta',
    'Timeline'             => 'Linea Temporale',
    'Time'                 => 'Ora',
    'TimestampLabelFormat' => 'Formato etichetta timestamp',
    'TimestampLabelX'      => 'coordinata X etichetta',
    'TimestampLabelY'      => 'coordinata Y etichetta',
    'Timestamp'            => 'Timestamp',
    'TimeStamp'            => 'Time Stamp',
    'Today'                => 'Oggi ',
    'Tools'                => 'Strumenti',
    'TotalBrScore'         => 'Punteggio<br/>Totale',
    'TrackDelay'           => 'Track Delay',
    'TrackMotion'          => 'Track Motion',
    'Triggers'             => 'Triggers',
    'TurboPanSpeed'        => 'Velocita\' Turbo Pan',
    'TurboTiltSpeed'       => 'Velocita\' Turbo Tilt',
    'Type'                 => 'Tipo',
    'Unarchive'            => 'Togli dall\'archivio',
    'Units'                => 'Unit&agrave;',
    'Unknown'              => 'Sconosciuto',
    'Update'               => 'Aggiorna',
    'UpdateAvailable'      => 'Un aggiornamento di ZoneMinder &egrave; disponibilie.',
    'UpdateNotNecessary'   => 'Nessun aggiornamento necessario.',
    'UseFilterExprsPost'   => '&nbsp;espressioni&nbsp;filtri', // This is used at the end of the phrase 'use N filter expressions'
    'UseFilterExprsPre'    => 'Usa&nbsp;', // This is used at the beginning of the phrase 'use N filter expressions'
    'UseFilter'            => 'Usa Filtro',
    'Username'             => 'Nome Utente',
    'Users'                => 'Utenti',
    'User'                 => 'Utente',
    'Value'                => 'Valore',
    'VersionIgnore'        => 'Ignora questa versione',
    'VersionRemindDay'     => 'Ricordami ancora tra un giorno',
    'VersionRemindHour'    => 'Ricordami ancora tra un\'ora',
    'VersionRemindNever'   => 'Non ricordarmi di nuove versioni',
    'VersionRemindWeek'    => 'Ricordami ancora tra una settimana',
    'Version'              => 'Versione',
    'VideoFormat'          => 'Formato Video',
    'VideoGenFailed'       => 'Generazione Video Fallita!',
    'VideoGenFiles'        => 'File Video Esistenti',
    'VideoGenNoFiles'      => 'Non ho trovato file ',
    'VideoGenParms'        => 'Parametri Generazione Video',
    'VideoGenSucceeded'    => 'Successo: Generato Video  !',
    'VideoSize'            => 'Dimensioni Video',
    'Video'                => 'Video',
    'ViewAll'              => 'Vedi Tutto',
    'ViewEvent'            => 'Vedi Evento',
    'ViewPaged'            => 'Vedi con paginazione',
    'View'                 => 'vedi',
    'Wake'                 => 'Riattiva',
    'WarmupFrames'         => 'Immagini Allerta',
    'Watch'                => 'Guarda',
    'WebColour'            => 'Colore Web',
    'Web'                  => 'Web',
    'Week'                 => 'Settimana',
    'WhiteBalance'         => 'Bil. Bianco  ',
    'White'                => 'Bianco',
    'Wide'                 => 'Larghezza',
    'X10ActivationString'  => 'Stringa attivazione X10',
    'X10InputAlarmString'  => 'Stringa allarme input X10',
    'X10OutputAlarmString' => 'Stringa allarme output X10',
    'X10'                  => 'X10',
    'X'                    => 'X',
    'Yes'                  => 'Si',
    'YouNoPerms'           => 'Non hai i permessi per accedere a questa risorsa.',
    'Y'                    => 'Y',
    'ZoneAlarmColour'      => 'Colore Allarme (RGB)',
    'ZoneArea'             => 'Zone Area',
    'ZoneFilterHeight'     => 'Altezza Filtro (pixels)',
    'ZoneFilterSize'       => 'Larghezza/Altezza Filtro (pixels)',
    'ZoneFilterWidth'      => 'Larghezza Filtro (pixels)',
    'ZoneMaxAlarmedArea'   => 'Massima Area Allarmata',
    'ZoneMaxBlobArea'      => 'Massima Area Blob',
    'ZoneMaxBlobs'         => 'Numero Massimo di Blobs',
    'ZoneMaxFilteredArea'  => 'Massima Area Filtrata',
    'ZoneMaxPixelThres'    => 'Pixel Massimi di Soglia (0-255)',
    'ZoneMaxX'             => 'X Massimo (destra)',
    'ZoneMaxY'             => 'Y Massimo (basso)',
    'ZoneMinAlarmedArea'   => 'Minima Area Allarmata',
    'ZoneMinBlobArea'      => 'Minima Area Blob',
    'ZoneMinBlobs'         => 'Blob Minimi',
    'ZoneMinFilteredArea'  => 'Minima Area Filtrata',
    'ZoneMinMaxAlarmArea'  => 'Min/Max Area Allarmata',
    'ZoneMinMaxBlobArea'   => 'Min/Max Area di Blob',
    'ZoneMinMaxBlobs'      => 'Min/Max Blobs',
    'ZoneMinMaxFiltArea'   => 'Min/Max Area Filtrata',
    'ZoneMinMaxPixelThres' => 'Min/Max Soglia Pixel (0-255)',
    'ZoneMinPixelThres'    => 'Pixel Minimi di Soglia (0-255)',
    'ZoneMinX'             => 'X Minimo (sinistra)',
    'ZoneMinY'             => 'Y Minimo (alto)',
    'ZoneOverloadFrames'   => 'Overload Frame Ignore Count',
    'Zones'                => 'Zone',
    'Zone'                 => 'Zona',
    'ZoomIn'               => 'Ingrandisci',
    'ZoomOut'              => 'Rimpicciolisci',
    'Zoom'                 => 'Zoom',
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
//    'LANG_DEFAULT' => array(
//        'Prompt' => "This is a new prompt for this option",
//        'Help' => "This is some new help for this option which will be displayed in the popup window when the ? is clicked"
//    ),
);

?>
