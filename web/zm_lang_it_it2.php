<?php
//
// ZoneMinder web Italian language file, $Date$, $Revision$
// Copyright (C) 2003  Philip Coombes
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
$zmSlangActual               = 'Attuale';
$zmSlangAddNewMonitor        = 'Aggiungi un nuovo Monitor';
$zmSlangAddNewUser           = 'Aggiungi un nuovo Utente';
$zmSlangAddNewZone           = 'Aggiungi una nuova Zona';
$zmSlangAlarm                = 'Allarme';
$zmSlangAlarmBrFrames        = 'Allarme<br/>Frames';
$zmSlangAlarmFrame           = 'Allarme Frame';
$zmSlangAlarmLimits          = 'Limiti Allarme';
$zmSlangAlarmPx              = 'Allarme Px';
$zmSlangAlert                = 'Alert';
$zmSlangAll                  = 'All';
$zmSlangApply                = 'Applica';
$zmSlangApplyingStateChange  = 'Sto applicando il cambiamento di Stato';
$zmSlangArchArchived         = 'Archiviato';
$zmSlangArchive              = 'Archivio';
$zmSlangArchUnarchived       = 'Non archiviato';
$zmSlangAttrAlarmFrames      = 'Frame in Allarme';
$zmSlangAttrArchiveStatus    = 'Stato Archivio';
$zmSlangAttrAvgScore         = 'Punteggio di Avg.';
$zmSlangAttrDate             = 'Data';
$zmSlangAttrDateTime         = 'Data/Ora';
$zmSlangAttrDiskBlocks       = 'Disk Blocks';
$zmSlangAttrDiskPercent      = 'Disk Percent';
$zmSlangAttrDuration         = 'Durata';
$zmSlangAttrFrames           = 'Frames';
$zmSlangAttrId               = 'Id';
$zmSlangAttrMaxScore         = 'Max. Punteggio';
$zmSlangAttrMonitorId        = 'Identificativo Monitor';
$zmSlangAttrMonitorName      = 'Nome Monitor';
$zmSlangAttrName             = 'Name';
$zmSlangAttrTime             = 'Tempo';
$zmSlangAttrTotalScore       = 'Punteggio Totale';
$zmSlangAttrWeekday          = 'Giorno della Settimana';
$zmSlangAutoArchiveEvents    = 'Automatically archive all matches';
$zmSlangAutoDeleteEvents     = 'Automatically delete all matches';
$zmSlangAutoEmailEvents      = 'Automatically email details of all matches';
$zmSlangAutoExecuteEvents    = 'Automatically execute command on all matches';
$zmSlangAutoMessageEvents    = 'Automatically message details of all matches';
$zmSlangAutoUploadEvents     = 'Automatically upload all matches';
$zmSlangAvgBrScore           = 'Punteggio Avg.<br/>';
$zmSlangBadMonitorChars      = 'Il nome dei Monitor possono contenere solo caratteri alfanumerici piu - e _';
$zmSlangBandwidth            = 'BandaPassante';
$zmSlangBlobPx               = 'Blob Px';
$zmSlangBlobs                = 'Blobs';
$zmSlangBlobSizes            = 'Grandezza Blob';
$zmSlangBrightness           = 'Luminosita';
$zmSlangBuffers              = 'Buffers';
$zmSlangCancel               = 'Cancella';
$zmSlangCancelForcedAlarm    = 'Cancella&nbsp;Allarme&nbsp;Forzato';
$zmSlangCaptureHeight        = 'Altezza immagine catturata';
$zmSlangCapturePalette       = 'Palette immagine catturata';
$zmSlangCaptureWidth         = 'Larghezza immagine catturata';
$zmSlangCheckAll             = 'Controlla Tutto';
$zmSlangCheckMethod          = 'Metodo di controllo Alarme';
$zmSlangChooseFilter         = 'Scegli il Filtro';
$zmSlangClose                = 'Chiudi';
$zmSlangColour               = 'Colore';
$zmSlangConfig               = 'Configurazione';
$zmSlangConfiguredFor        = 'Configurato per';
$zmSlangConfirmPassword      = 'Conferma Password';
$zmSlangConjAnd              = 'e';
$zmSlangConjOr               = 'o';
$zmSlangConsole              = 'Console';
$zmSlangContactAdmin         = 'Chiama Tolmino per maggiori chiarimenti.';
$zmSlangContrast             = 'Contrasto';
$zmSlangCycleWatch           = 'Guarda Ciclicamente';
$zmSlangDay                  = 'Giorno';
$zmSlangDeleteAndNext        = 'Cancella il Prossimo';
$zmSlangDeleteAndPrev        = 'Cancella il Precedente';
$zmSlangDelete               = 'Cancella';
$zmSlangDeleteSavedFilter    = 'Cancella il Filtro salvato';
$zmSlangDescription          = 'Descrizione';
$zmSlangDeviceChannel        = 'Canale del Dispositivo';
$zmSlangDeviceFormat         = 'Formato Video (0=PAL,1=NTSC etc)';
$zmSlangDeviceNumber         = 'Numero Dispositivo (/dev/video?)';
$zmSlangDimensions           = 'Dimensioni';
$zmSlangDisk                 = 'Hard Disk';
$zmSlangDuration             = 'Durata';
$zmSlangEdit                 = 'Edita';
$zmSlangEmail                = 'Email';
$zmSlangEnabled              = 'Abilitato';
$zmSlangEnterNewFilterName   = 'Inserisci il nome del filtro';
$zmSlangErrorBrackets        = 'Errore, controlla di avere in ugual numero i brachets aperti e chiusi';
$zmSlangError                = 'Errore';
$zmSlangErrorValidValue      = 'Errore, controlla di aver inserito valori validi';
$zmSlangEtc                  = 'ecc.';
$zmSlangEvent                = 'Evento';
$zmSlangEventFilter          = 'Filtro Eventi';
$zmSlangEventId              = 'Event Id';
$zmSlangEvents               = 'Eventi';
$zmSlangExclude              = 'Escludi';
$zmSlangFeed                 = 'Feed';
$zmSlangFilterPx             = 'Filter Px';
$zmSlangFirst                = 'Primo';
$zmSlangForceAlarm           = 'Forza&nbsp;Allarme';
$zmSlangFPS                  = 'fps';
$zmSlangFPSReportInterval    = 'Intervallo di riporto FPS';
$zmSlangFrame                = 'Frame';
$zmSlangFrameId              = 'Frame Id';
$zmSlangFrameRate            = 'Frame Rate';
$zmSlangFrames               = 'Frames';
$zmSlangFrameSkip            = 'Frames saltati';
$zmSlangFTP                  = 'FTP';
$zmSlangFunc                 = 'Funziona';
$zmSlangFunction             = 'Funzione';
$zmSlangGenerateVideo        = 'Genera il Video';
$zmSlangGeneratingVideo      = 'Sto Generando il Video';
$zmSlangGoToZoneMinder       = 'Vai a: ZoneMinder.com';
$zmSlangGrey                 = 'Grigio';
$zmSlangHigh                 = 'Alta';
$zmSlangHighBW               = 'High&nbsp;B/W';
$zmSlangHour                 = 'Ora';
$zmSlangHue                  = 'Hue';
$zmSlangId                   = 'Id';
$zmSlangIdle                 = 'Idle';
$zmSlangIgnore               = 'Ignora';
$zmSlangImageBufferSize      = 'Grandezza Buffer Immagine (frames)';
$zmSlangImage                = 'Immagine';
$zmSlangInclude              = 'Includi';
$zmSlangInverted             = 'Invertito';
$zmSlangLanguage             = 'Linguaggio';
$zmSlangLast                 = 'Ultimo';
$zmSlangLimitResultsPost     = 'results only;'; // This is used at the end of the phrase 'Limit to first N results only'
$zmSlangLimitResultsPre      = 'Limit to first'; // This is used at the beginning of the phrase 'Limit to first N results only'
$zmSlangLoad                 = 'Carica';
$zmSlangLocal                = 'Locale';
$zmSlangLoggedInAs           = 'Nome utente:';
$zmSlangLoggingIn            = 'Logging In';
$zmSlangLogin                = 'Login';
$zmSlangLogout               = 'Logout';
$zmSlangLow                  = 'Bassa';
$zmSlangLowBW                = 'Low&nbsp;B/W';
$zmSlangMark                 = 'Contrassegna';
$zmSlangMaxBrScore           = 'Punteggio<br/>Massimo';
$zmSlangMaximumFPS           = 'Massimi FPS';
$zmSlangMax                  = 'Massima';
$zmSlangMediumBW             = 'Medium&nbsp;B/W';
$zmSlangMedium               = 'Media';
$zmSlangMinAlarmGeMinBlob    = 'Numero minimo di alarm-pixels che devono essere maggiori o uguali al numero minimo dei blob-pixels';
$zmSlangMinAlarmGeMinFilter  = 'Numero minimo di alarm-pixels che devono essere maggiori o uguali al numero minimo dei filter-pixels';
$zmSlangMinAlarmPixelsLtMax  = 'Numero minimo di alarm-pixels che devono essere minori del numero massimo dei alarm-pixels';
$zmSlangMinBlobAreaLtMax     = 'Area di blob minima che deve essere minore dell area massima di blob';
$zmSlangMinBlobsLtMax        = 'Numero minimo di blobs che devono essere minori al numero massimo di blobs';
$zmSlangMinFilterPixelsLtMax = 'Numero minimo di filter-pixels che deve essere minore del numero massimo dei filter-pixels';
$zmSlangMinPixelThresLtMax   = 'Soglia minima di pixel che devono essere minori della soglia massima di pixel';
$zmSlangMisc                 = 'Misc';
$zmSlangMonitorIds           = 'Monitor&nbsp;Ids';
$zmSlangMonitor              = 'Monitor';
$zmSlangMonitors             = 'Monitors';
$zmSlangMontage              = 'Montaggio';
$zmSlangMonth                = 'Mese';
$zmSlangMustBeGe             = 'deve essere maggiore o uguale di';
$zmSlangMustBeLe             = 'deve essere minore o uguale di';
$zmSlangMustConfirmPassword  = 'Devi confermare la password';
$zmSlangMustSupplyPassword   = 'Devi inserire la password';
$zmSlangMustSupplyUsername   = 'Devi inserire il nome-Utente';
$zmSlangName                 = 'Nome';
$zmSlangNetwork              = 'Network';
$zmSlangNew                  = 'Nuovo';
$zmSlangNewPassword          = 'Nuova Password';
$zmSlangNewState             = 'Nuovo Stato';
$zmSlangNewUser              = 'Nuovo Utente';
$zmSlangNext                 = 'Prossimo';
$zmSlangNoFramesRecorded     = 'Non ci sono frames registrati per questo evento';
$zmSlangNoneAvailable        = 'Non trovato';
$zmSlangNone                 = 'Niente';
$zmSlangNo                   = 'No';
$zmSlangNormal               = 'Normale';
$zmSlangNoSavedFilters       = 'No_Filtri_Salvati';
$zmSlangNoStatisticsRecorded = 'Non ci sono statistiche registrate per questo event/frame';
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
$zmSlangOrEnterNewName       = 'o inserisci il nuovo nome';
$zmSlangOrientation          = 'Orientazione';
$zmSlangOverwriteExisting    = 'Sovrascrivi quello esistente';
$zmSlangPaged                = 'Impaginato';
$zmSlangParameter            = 'Parametri';
$zmSlangPassword             = 'Password';
$zmSlangPasswordsDifferent   = 'Le password inserite sono differenti';
$zmSlangPaths                = 'Paths';
$zmSlangPhoneBW              = 'Phone&nbsp;B/W';
$zmSlangPixels               = 'pixels';
$zmSlangPleaseWait           = 'ATTENDI';
$zmSlangPostEventImageBuffer = 'Buffer delle immagini dopo gli Eventi';
$zmSlangPreEventImageBuffer  = 'Buffer delle immagini prima degli Eventi';
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
$zmSlangRestarting           = 'Sto Ripartendo';
$zmSlangRestart              = 'Riparti';
$zmSlangRestrictedCameraIds  = 'Restricted Camera Ids';
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
$zmSlangSetLearnPrefs        = 'Seleziona le preferenze di autoapprendimento'; // This can be ignored for now
$zmSlangSetNewBandwidth      = 'Seleziona la nuova BandaPassante';
$zmSlangSettings             = 'Settings';
$zmSlangShowFilterWindow     = 'MostraFinestraFiltri';
$zmSlangSortAsc              = 'Asc';
$zmSlangSortBy               = 'Sort by';
$zmSlangSortDesc             = 'Desc';
$zmSlangSource               = 'Ingresso';
$zmSlangSourceType           = 'Tipo di ingresso';
$zmSlangStart                = 'Start';
$zmSlangState                = 'Stato';
$zmSlangStats                = 'Stati';
$zmSlangStatus               = 'Stato';
$zmSlangStills               = 'Fermo-immagine';
$zmSlangStopped              = 'Sistema Stoppato';
$zmSlangStop                 = 'Stop';
$zmSlangStream               = 'Stream';
$zmSlangSystem               = 'Sistema';
$zmSlangTimeDelta            = 'Tempo di Delta';
$zmSlangTime                 = 'Ora';
$zmSlangTimestampLabelFormat = 'Formato etichetta Timestamp';
$zmSlangTimestampLabelX      = 'Etichetta Timestamp X';
$zmSlangTimestampLabelY      = 'Etichetta Timestamp Y';
$zmSlangTimestamp            = 'Timestamp';
$zmSlangTimeStamp            = 'Time Stamp';
$zmSlangTools                = 'Tools';
$zmSlangTotalBrScore         = 'Punteggio<br/>Totale';
$zmSlangTriggers             = 'Triggers';
$zmSlangType                 = 'Tipo';
$zmSlangUnarchive            = 'Non_archiviato';
$zmSlangUnits                = 'Unità';
$zmSlangUnknown              = 'Sconosciuto';
$zmSlangUpdateAvailable      = 'Una nuova versione di ZoneMinder è disponibile.';
$zmSlangUpdateNotNecessary   = 'Non è necessario aggiornare Zoneminder.';
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
$zmSlangVideoGenFailed       = 'Creazione Video Fallita!';
$zmSlangVideoGenParms        = 'Parametri per la Creazione del Video';
$zmSlangVideoSize            = 'Dimensioni del Video';
$zmSlangVideo                = 'Video';
$zmSlangViewAll              = 'Vedi tutto';
$zmSlangViewPaged            = 'Vedi impaginato';
$zmSlangView                 = 'Vedi';
$zmSlangWarmupFrames         = 'Frames di attenzione';
$zmSlangWatch                = 'Guarda';
$zmSlangWeb                  = 'Web';
$zmSlangWeek                 = 'Settimana';
$zmSlangX10ActivationString  = 'Stringa di Attivazione X10';
$zmSlangX10InputAlarmString  = 'Stringa di ingresso Allarme X10';
$zmSlangX10OutputAlarmString = 'Stringa di uscita Allarme X10';
$zmSlangX10                  = 'X10';
$zmSlangYes                  = 'SI';
$zmSlangYouNoPerms           = 'Non hai i permessi per accedere a questa risorsa.';
$zmSlangZoneAlarmColour      = 'Colore Allarme (RGB)';
$zmSlangZoneFilterHeight     = 'Filtro Altezza (pixels)';
$zmSlangZoneFilterWidth      = 'Filtro Larghezza (pixels)';
$zmSlangZoneMaxAlarmedArea   = 'Area Massima di Allarme';
$zmSlangZoneMaxBlobArea      = 'Area MAssima di Blob';
$zmSlangZoneMaxBlobs         = 'Numero massimo di Blobs';
$zmSlangZoneMaxFilteredArea  = 'Area massima Filtrata';
$zmSlangZoneMaxPixelThres    = 'Soglia massima di Pixel (0>=?<=255)';
$zmSlangZoneMaxX             = 'Massimo X (destra)';
$zmSlangZoneMaxY             = 'Massimo Y (sotto)';
$zmSlangZoneMinAlarmedArea   = 'Area massima di Allarme';
$zmSlangZoneMinBlobArea      = 'Area minima di Blob';
$zmSlangZoneMinBlobs         = 'Numero minimo di Blobs';
$zmSlangZoneMinFilteredArea  = 'Area minima Filtrata';
$zmSlangZoneMinPixelThres    = 'Soglia minima di Pixel (0>=?<=255)';
$zmSlangZoneMinX             = 'Minimo X (sinistra)';
$zmSlangZoneMinY             = 'Minimo Y (alto)';
$zmSlangZones                = 'Zone';
$zmSlangZone                 = 'Zona';

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
