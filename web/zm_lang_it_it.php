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

// ZoneMinder Italian Translation by Davide Morelli

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
$zmSlang24BitColour          = '24 bit colori';
$zmSlang8BitGrey             = '8 bit toni di grigio';
$zmSlangActual               = 'Attuale';
$zmSlangAddNewMonitor        = 'Aggiungi Monitor';
$zmSlangAddNewUser           = 'Aggiungi Utente';
$zmSlangAddNewZone           = 'Aggiungi Zona';
$zmSlangAlarm                = 'Allarme';
$zmSlangAlarmBrFrames        = 'Immagini<br/>Allarme';
$zmSlangAlarmFrame           = 'Immagine Allarme';
$zmSlangAlarmLimits          = 'Limiti Allarme';
$zmSlangAlarmPx              = 'Px Allarme';
$zmSlangAlert                = 'Atenzione';
$zmSlangAll                  = 'Tutto';
$zmSlangApply                = 'Applica';
$zmSlangApplyingStateChange  = 'Sto applicando le modifiche';
$zmSlangArchArchived         = 'Archivia solo';
$zmSlangArchive              = 'Archivia';
$zmSlangArchUnarchived       = 'Non archiviare';
$zmSlangAttrAlarmFrames      = 'Immagini Allarme';
$zmSlangAttrArchiveStatus    = 'Stato Archivio';
$zmSlangAttrAvgScore         = 'Punteggio medio';
$zmSlangAttrDate             = 'Data';
$zmSlangAttrDateTime         = 'Data/Ora';
$zmSlangAttrDiskBlocks       = 'Disk Blocks';
$zmSlangAttrDiskPercent      = 'Disk Percent';
$zmSlangAttrDuration         = 'Durata';
$zmSlangAttrFrames           = 'Immagini';
$zmSlangAttrId               = 'Id';
$zmSlangAttrMaxScore         = 'Punteggio massimo';
$zmSlangAttrMonitorId        = 'Monitor Id';
$zmSlangAttrMonitorName      = 'Nome Monitor';
$zmSlangAttrName             = 'Name';
$zmSlangAttrTime             = 'Ora';
$zmSlangAttrTotalScore       = 'Punteggio totale';
$zmSlangAttrWeekday          = 'Giorno della settimana';
$zmSlangAutoArchiveEvents    = 'Automatically archive all matches';
$zmSlangAutoDeleteEvents     = 'Automatically delete all matches';
$zmSlangAutoEmailEvents      = 'Automatically email details of all matches';
$zmSlangAutoExecuteEvents    = 'Automatically execute command on all matches';
$zmSlangAutoMessageEvents    = 'Automatically message details of all matches';
$zmSlangAutoUploadEvents     = 'Automatically upload all matches';
$zmSlangAvgBrScore           = 'Punteggio<br/>medio';
$zmSlangBadMonitorChars      = 'I nomi dei Monitor possono contenere solo caratteri alfanumerici pi&ugrave; i caratteri - e _';
$zmSlangBandwidth            = 'Banda';
$zmSlangBlobPx               = 'Blob Px';
$zmSlangBlobs                = 'Blobs';
$zmSlangBlobSizes            = 'Dimensioni Blob';
$zmSlangBrightness           = 'Luminosit&agrave;';
$zmSlangBuffers              = 'Buffers';
$zmSlangCancel               = 'Annulla';
$zmSlangCancelForcedAlarm    = 'Annulla&nbsp;Allarme&nbsp;Forzato';
$zmSlangCaptureHeight        = 'Altezza di Cattura';
$zmSlangCapturePalette       = 'Paletta di Cattura';
$zmSlangCaptureWidth         = 'Larghezza di Cattura';
$zmSlangCheckAll             = 'Seleziona Tutto';
$zmSlangCheckMethod          = 'Metodo di Controllo Allarme';
$zmSlangChooseFilter         = 'Scegli Filtro';
$zmSlangClose                = 'Chiudi';
$zmSlangColour               = 'Colori';
$zmSlangConfig               = 'Configura';
$zmSlangConfiguredFor        = 'Configurato per';
$zmSlangConfirmPassword      = 'Conferma Password';
$zmSlangConjAnd              = 'e';
$zmSlangConjOr               = 'o';
$zmSlangConsole              = 'Console';
$zmSlangContactAdmin         = 'Contatta il tuo amministratore per dettagli.';
$zmSlangContrast             = 'Contrasto';
$zmSlangCycleWatch           = 'Vista Ciclica';
$zmSlangDay                  = 'Giorno';
$zmSlangDeleteAndNext        = 'Elimina &amp; Prossimo';
$zmSlangDeleteAndPrev        = 'Elimina &amp; Precedente';
$zmSlangDelete               = 'Elimina';
$zmSlangDeleteSavedFilter    = 'Elimina il filtro salvato';
$zmSlangDescription          = 'Descrizione';
$zmSlangDeviceChannel        = 'Canale Periferica';
$zmSlangDeviceFormat         = 'Formato (0=PAL,1=NTSC etc)';
$zmSlangDeviceNumber         = 'Numero Periferica (/dev/video?)';
$zmSlangDimensions           = 'Dimensioni';
$zmSlangDisk                 = 'Disco';
$zmSlangDuration             = 'Durata';
$zmSlangEdit                 = 'Modifica';
$zmSlangEmail                = 'Email';
$zmSlangEnabled              = 'Attivo';
$zmSlangEnterNewFilterName   = 'Inserisci il nome del nuovo filtro';
$zmSlangErrorBrackets        = 'Errore, controlla di avere un numero bilanciato di parentesti aperte e chiuse.';
$zmSlangError                = 'Errore';
$zmSlangErrorValidValue      = 'Errore, controlla che tutti i termini abbiano un valore valido';
$zmSlangEtc                  = 'etc';
$zmSlangEvent                = 'Evento';
$zmSlangEventFilter          = 'Filtro Eventi';
$zmSlangEventId              = 'Event Id';
$zmSlangEvents               = 'Eventi';
$zmSlangExclude              = 'Escludi';
$zmSlangFeed                 = 'Feed';
$zmSlangFilterPx             = 'Px Filtro';
$zmSlangFirst                = 'Primo';
$zmSlangForceAlarm           = 'Forza&nbsp;Allarme';
$zmSlangFPS                  = 'fps';
$zmSlangFPSReportInterval    = 'Intervallo Report FPS';
$zmSlangFrameId              = 'Id Immagine';
$zmSlangFrame                = 'Immagine';
$zmSlangFrameRate            = 'Immagini al secondo';
$zmSlangFrames               = 'Immagini';
$zmSlangFrameSkip            = 'Salta Immagine';
$zmSlangFTP                  = 'FTP';
$zmSlangFunc                 = 'Funz';
$zmSlangFunction             = 'Funzione';
$zmSlangGenerateVideo        = 'Genera Video';
$zmSlangGeneratingVideo      = 'Sto generando il Video';
$zmSlangGoToZoneMinder       = 'Vai su ZoneMinder.com';
$zmSlangGrey                 = 'Grigio';
$zmSlangHigh                 = 'Alta';
$zmSlangHighBW               = 'Banda&nbsp;Alta';
$zmSlangHour                 = 'Ora';
$zmSlangHue                  = 'Tinta';
$zmSlangId                   = 'Id';
$zmSlangIdle                 = 'Inattivo';
$zmSlangIgnore               = 'Ignora';
$zmSlangImageBufferSize      = 'Dimensione del Buffer Immagine (quante immagini)';
$zmSlangImage                = 'Immagine';
$zmSlangInclude              = 'Includi';
$zmSlangInverted             = 'Invertito';
$zmSlangLanguage             = 'Linguaggio';
$zmSlangLast                 = 'Ultimo';
$zmSlangLimitResultsPost     = 'results only;'; // This is used at the end of the phrase 'Limit to first N results only'
$zmSlangLimitResultsPre      = 'Limit to first'; // This is used at the beginning of the phrase 'Limit to first N results only'
$zmSlangLoad                 = 'Carica';
$zmSlangLocal                = 'Locale';
$zmSlangLoggedInAs           = 'Loggato come';
$zmSlangLoggingIn            = 'Mi Sto loggando';
$zmSlangLogin                = 'Login';
$zmSlangLogout               = 'Logout';
$zmSlangLow                  = 'Bassa';
$zmSlangLowBW                = 'Banda&nbsp;Bassa';
$zmSlangMark                 = 'Seleziona';
$zmSlangMaxBrScore           = 'Punteggio<br/>Massimo';
$zmSlangMaximumFPS           = 'Max FPS';
$zmSlangMax                  = 'Max';
$zmSlangMediumBW             = 'Banda&nbsp;Media';
$zmSlangMedium               = 'Media';
$zmSlangMinAlarmGeMinBlob    = 'I pixel minimi dell\'allarme devono essere grandi almeno quanto i pixel minimi del blob';
$zmSlangMinAlarmGeMinFilter  = 'I pixel minimi dell\'allarme devono essere grandi almeno quanto i pixel minimi del filtro';
$zmSlangMinAlarmPixelsLtMax  = 'I pixel minimi dell\'allarme devono essere minori dei pixel massimi dell\'allarme';
$zmSlangMinBlobAreaLtMax     = 'L\'area di blob minima deve essere minore dell\'area di blob massima';
$zmSlangMinBlobsLtMax        = 'I blob minini devono essere minori dei blob massimi';
$zmSlangMinFilterPixelsLtMax = 'I pixel minimi del filtro devono essere minori di pixel massimi del filtro';
$zmSlangMinPixelThresLtMax   = 'I pixel minimi della soglia devono essere minori del pixel massimi della soglia';
$zmSlangMisc                 = 'Altro';
$zmSlangMonitorIds           = 'Monitor&nbsp;Ids';
$zmSlangMonitor              = 'Monitor';
$zmSlangMonitors             = 'Monitors';
$zmSlangMontage              = 'Montaggio';
$zmSlangMonth                = 'Mese';
$zmSlangMustBeGe             = 'deve essere superiore a';
$zmSlangMustBeLe             = 'deve essere inferiore o pari a';
$zmSlangMustConfirmPassword  = 'Devi confermare la password';
$zmSlangMustSupplyPassword   = 'Devi inserire una password';
$zmSlangMustSupplyUsername   = 'devi specificare un nome utente';
$zmSlangName                 = 'Nome';
$zmSlangNetwork              = 'Rete';
$zmSlangNew                  = 'Nuovo';
$zmSlangNewPassword          = 'Nuova Password';
$zmSlangNewState             = 'Nuovo Stato';
$zmSlangNewUser              = 'Nuovo Utente';
$zmSlangNext                 = 'Prossimo';
$zmSlangNoFramesRecorded     = 'Non ci sono immagini salvate per questo evento';
$zmSlangNoneAvailable        = 'Nessuno disponibile';
$zmSlangNone                 = 'Nessuno';
$zmSlangNo                   = 'No';
$zmSlangNormal               = 'Normale';
$zmSlangNoSavedFilters       = 'NoSavedFilters';
$zmSlangNoStatisticsRecorded = 'Non ci sno statistiche salvate per questo evento/immagine';
$zmSlangOpEq                 = 'uguale a';
$zmSlangOpGtEq               = 'maggiore o uguale a';
$zmSlangOpGt                 = 'maggiore di';
$zmSlangOpIn                 = 'in set';
$zmSlangOpLtEq               = 'minore o uguale a';
$zmSlangOpLt                 = 'minore di';
$zmSlangOpMatches            = 'corrisponde';
$zmSlangOpNe                 = 'diverso da';
$zmSlangOpNotIn              = 'non in set';
$zmSlangOpNotMatches         = 'non corrisponde';
$zmSlangOptionHelp           = 'OptionHelp';
$zmSlangOptionRestartWarning = 'Queste modifiche potrebbero essere attive solo dopo \nun riavvio del sistema. Riavviare ZoneMinder.';
$zmSlangOptions              = 'Opzioni';
$zmSlangOrEnterNewName       = 'o inserisci un nuovo nome';
$zmSlangOrientation          = 'Orientamento';
$zmSlangOverwriteExisting    = 'Sovrascrivi';
$zmSlangPaged                = 'Con paginazione';
$zmSlangParameter            = 'Parametri';
$zmSlangPassword             = 'Password';
$zmSlangPasswordsDifferent   = 'Le password non coincidono';
$zmSlangPaths                = 'Percorsi';
$zmSlangPhoneBW              = 'Banda&nbsp;Tel';
$zmSlangPixels               = 'pixels';
$zmSlangPleaseWait           = 'Attendere prego';
$zmSlangPostEventImageBuffer = 'Buffer di immagini Dopo Evento';
$zmSlangPreEventImageBuffer  = 'Buffer di immagini Pre Evento<';
$zmSlangPrev                 = 'Prec';
$zmSlangRate                 = 'Rate';
$zmSlangReal                 = 'Reale';
$zmSlangRecord               = 'Record';
$zmSlangRefImageBlendPct     = 'Riferimento Miscela Immagine percentuale';
$zmSlangRefresh              = 'Aggiorna';
$zmSlangRemoteHostName       = 'Nome dell\'Host Remoto';
$zmSlangRemoteHostPath       = 'Percorso dell\'Host Remoto';
$zmSlangRemoteHostPort       = 'Porta dell\'Host Remoto';
$zmSlangRemoteImageColours   = 'Colori delle immagini Remote';
$zmSlangRemote               = 'Remoto';
$zmSlangRename               = 'Rinomina';
$zmSlangReplay               = 'Replay';
$zmSlangResetEventCounts     = 'Resetta Contatore Eventi';
$zmSlangRestarting           = 'Sto riavviando';
$zmSlangRestart              = 'Riavvia';
$zmSlangRestrictedCameraIds  = 'Camera Ids Riservati';
$zmSlangRotateLeft           = 'Ruota a Sinista';
$zmSlangRotateRight          = 'Ruota a Destra';
$zmSlangRunMode              = 'Modalità Run';
$zmSlangRunning              = 'Avviato';
$zmSlangRunState             = 'Stato Avviato';
$zmSlangSaveAs               = 'Salva come';
$zmSlangSaveFilter           = 'salva Filtro';
$zmSlangSave                 = 'Salva';
$zmSlangScale                = 'Scala';
$zmSlangScore                = 'Punti';
$zmSlangSecs                 = 'Secs';
$zmSlangSectionlength        = 'Lunghezza Sezione';
$zmSlangSetLearnPrefs        = 'Set Learn Prefs'; // This can be ignored for now
$zmSlangSetNewBandwidth      = 'Imposta nuova Banda';
$zmSlangSettings             = 'Impostazioni';
$zmSlangShowFilterWindow     = 'ShowFilterWindow';
$zmSlangSortAsc              = 'Asc';
$zmSlangSortBy               = 'Sort by';
$zmSlangSortDesc             = 'Desc';
$zmSlangSource               = 'Sorgente';
$zmSlangSourceType           = 'Tipo Sorgente';
$zmSlangStart                = 'Avvia';
$zmSlangState                = 'Stato';
$zmSlangStats                = 'Statistiche';
$zmSlangStatus               = 'Stato';
$zmSlangStills               = 'Foto';
$zmSlangStopped              = 'Arrestato';
$zmSlangStop                 = 'Stop';
$zmSlangStream               = 'Flusso';
$zmSlangSystem               = 'Sistema';
$zmSlangTimeDelta            = 'Time Delta';
$zmSlangTime                 = 'Ora';
$zmSlangTimestampLabelFormat = 'Formato etichetta timestamp';
$zmSlangTimestampLabelX      = 'coordinata X etichetta';
$zmSlangTimestampLabelY      = 'coordinata Y etichetta';
$zmSlangTimestamp            = 'Timestamp';
$zmSlangTimeStamp            = 'Time Stamp';
$zmSlangTools                = 'Tools';
$zmSlangTotalBrScore         = 'Punteggio<br/>Totale';
$zmSlangTriggers             = 'Triggers';
$zmSlangType                 = 'Tipo';
$zmSlangUnarchive            = 'Togli dall\'archivio';
$zmSlangUnits                = 'Unit&agrave;';
$zmSlangUnknown              = 'Sconosciuto';
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
$zmSlangVideoGenFailed       = 'Generazione Video Fallita!';
$zmSlangVideoGenParms        = 'Parametri Generazione Video';
$zmSlangVideoSize            = 'Dimensioni Video';
$zmSlangVideo                = 'Video';
$zmSlangViewAll              = 'Vedi Tutte';
$zmSlangViewPaged            = 'Vedi con paginazione';
$zmSlangView                 = 'vedi';
$zmSlangWarmupFrames         = 'Immagini Warmup';
$zmSlangWatch                = 'Guarda';
$zmSlangWeb                  = 'Web';
$zmSlangWeek                 = 'Settimana';
$zmSlangX10ActivationString  = 'Stringa attivazione X10';
$zmSlangX10InputAlarmString  = 'Stringa allarme input X10';
$zmSlangX10OutputAlarmString = 'Stringa allarme output X10';
$zmSlangX10                  = 'X10';
$zmSlangYes                  = 'Si';
$zmSlangYouNoPerms           = 'Non hai i permessi per accedere a questa risorsa.';
$zmSlangZoneAlarmColour      = 'Colore Allarme (RGB)';
$zmSlangZoneFilterHeight     = 'Altezza Filtro (pixels)';
$zmSlangZoneFilterWidth      = 'Larghezza Filtro (pixels)';
$zmSlangZoneMaxAlarmedArea   = 'Massima Area Allarmata';
$zmSlangZoneMaxBlobArea      = 'Massima Area Blob';
$zmSlangZoneMaxBlobs         = 'Blob Massimi';
$zmSlangZoneMaxFilteredArea  = 'Massima Area Fitlrata';
$zmSlangZoneMaxPixelThres    = 'Pixel Massimi di Soglia (0>=?<=255)';
$zmSlangZoneMaxX             = 'X Massimo (destra)';
$zmSlangZoneMaxY             = 'Y Massimo (basso)';
$zmSlangZoneMinAlarmedArea   = 'Minima Area Allarmata';
$zmSlangZoneMinBlobArea      = 'Minima Area Blob';
$zmSlangZoneMinBlobs         = 'Blob Minimi';
$zmSlangZoneMinFilteredArea  = 'Minima Area Filtrata';
$zmSlangZoneMinPixelThres    = 'Pixel Minimi di Soglia (0>=?<=255)';
$zmSlangZoneMinX             = 'X Minimo (sinistra)';
$zmSlangZoneMinY             = 'Y Minimo (alto)';
$zmSlangZones                = 'Zone';
$zmSlangZone                 = 'Zona';

// Complex replacements with formatting and/or placements, must be passed through sprintf
$zmClangCurrentLogin         = 'Login attuale: \'%1$s\'';
$zmClangEventCount           = '%1$s %2$s'; // For example '37 Events' (from Vlang below)
$zmClangLastEvents           = 'Ultimi %1$s %2$s'; // For example 'Last 37 Events' (from Vlang below)
$zmClangLatestRelease        = 'L\'ultima release v%1$s, tu hai v%2$s.';
$zmClangMonitorCount         = '%1$s %2$s'; // For example '4 Monitors' (from Vlang below)
$zmClangMonitorFunction      = 'Monitor %1$s Function';
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
	die( 'Error, unable to correlate variable language string' );
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
