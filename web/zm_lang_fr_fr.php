<?php
//
// ZoneMinder web UK French language file, $Date$, $Revision$
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

// ZoneMinder French Translation by Jerome Hanoteau

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
$zmSlang24BitColour          = 'Couleur 24 bit';
$zmSlang8BitGrey             = 'Gris 8 bit';
$zmSlangActual               = 'Réel';
$zmSlangAddNewMonitor        = 'Aj. nouv. écran';
$zmSlangAddNewUser           = 'Aj. nouv. util.';
$zmSlangAddNewZone           = 'Aj. nouv. zone';
$zmSlangAlarm                = 'Alarme';
$zmSlangAlarmBrFrames        = 'Images<br/>alarme';
$zmSlangAlarmFrame           = 'Image alarme';
$zmSlangAlarmLimits          = 'Limites alarme';
$zmSlangAlarmPx              = 'Px Alarme';
$zmSlangAlert                = 'Alerte';
$zmSlangAll                  = 'Tous';
$zmSlangApply                = 'Appliquer';
$zmSlangApplyingStateChange  = 'Appl. chgt état';
$zmSlangArchArchived         = 'Archivé seul.';
$zmSlangArchive              = 'Archiver';
$zmSlangArchUnarchived       = 'Non-arch. seul.';
$zmSlangAttrAlarmFrames      = 'Images alarme';
$zmSlangAttrArchiveStatus    = 'Etat Archive';
$zmSlangAttrAvgScore         = 'Score moy.';
$zmSlangAttrDate             = 'Date';
$zmSlangAttrDateTime         = 'Date/temps';
$zmSlangAttrDuration         = 'Durée';
$zmSlangAttrFrames           = 'Images';
$zmSlangAttrMaxScore         = 'Score max.';
$zmSlangAttrMonitorId        = 'N° écran';
$zmSlangAttrMonitorName      = 'Nom écran';
$zmSlangAttrTime             = 'Temps';
$zmSlangAttrTotalScore       = 'Score total';
$zmSlangAttrWeekday          = 'Semaine';
$zmSlangAutoArchiveEvents    = 'Archiver auto. ts les évènements correspondants';
$zmSlangAutoDeleteEvents     = 'Effacer auto. ts les évènements correspondants';
$zmSlangAutoEmailEvents      = 'Envoyer auto. un courriel avec évènements pertinents';
$zmSlangAutoMessageEvents    = 'Automatically message details of all matching events';
$zmSlangAutoUploadEvents     = 'Télécharg. auto. ls évèments pertinents vrs serveur';
$zmSlangAvgBrScore           = 'Score<br/>moy.';
$zmSlangBadMonitorChars      = 'Les noms d\'écrans ne peuvent contenir que des lettres, chiffres, trait d\'union ou souligné';
$zmSlangBandwidth            = 'Bande-pass.';
$zmSlangBlobPx               = 'Px forme';
$zmSlangBlobs                = 'Formes';
$zmSlangBlobSizes            = 'Taille forme';
$zmSlangBrightness           = 'Luminosité;';
$zmSlangBuffers              = 'Tampons';
$zmSlangCancel               = 'Annul.';
$zmSlangCancelForcedAlarm    = 'Annul.&nbsp;Forc&eacute;&nbsp;Alarme';
$zmSlangCaptureHeight        = 'Haut. capture';
$zmSlangCapturePalette       = 'palette capture';
$zmSlangCaptureWidth         = 'Larg. capture';
$zmSlangCheckAll             = 'Vérif. ts';
$zmSlangCheckMethod          = 'Méthode vérif. alarme';
$zmSlangChooseFilter         = 'Choisir filtre';
$zmSlangClose                = 'Fermer';
$zmSlangColour               = 'Couleur';
$zmSlangConfiguredFor        = 'Configuré pour';
$zmSlangConfirmPassword      = 'Confirmer mt de pass.';
$zmSlangConjAnd              = 'et';
$zmSlangConjOr               = 'ou';
$zmSlangConsole              = 'Console';
$zmSlangContactAdmin         = 'Contactez votre administrateur SVP';
$zmSlangContrast             = 'Contraste';
$zmSlangCycleWatch           = 'Cycle vision';
$zmSlangDay                  = 'Jour';
$zmSlangDeleteAndNext        = 'Eff. &amp; suiv.';
$zmSlangDeleteAndPrev        = 'Eff. &amp; prec.';
$zmSlangDelete               = 'Eff.';
$zmSlangDeleteSavedFilter    = 'Eff. filtre sauvé';
$zmSlangDescription          = 'Description';
$zmSlangDeviceChannel        = 'Canal caméra';
$zmSlangDeviceFormat         = 'Format caméra (0=PAL,1=NTSC etc)';
$zmSlangDeviceNumber         = 'Numéro caméra (/dev/video?)';
$zmSlangDimensions           = 'Dimensions';
$zmSlangDuration             = 'Durée';
$zmSlangEdit                 = 'Editer';
$zmSlangEmail                = 'Courriel';
$zmSlangEnabled              = 'Activé';
$zmSlangEnterNewFilterName   = 'Entrer nom nouv. filtre';
$zmSlangErrorBrackets        = 'Erreur, vérifiez que toutes les parenthèses ouvertes sont fermées';
$zmSlangError                = 'Erreur';
$zmSlangErrorValidValue      = 'Erreur, vérifiez que tous les termes ont une valeur valide';
$zmSlangEtc                  = 'etc';
$zmSlangEvent                = 'Evènt';
$zmSlangEventFilter          = 'Filtre evènt';
$zmSlangEvents               = 'Evènts';
$zmSlangExclude              = 'Exclure';
$zmSlangFeed                 = 'Feed';
$zmSlangFilterPx             = 'Filter Px';
$zmSlangFirst                = 'Prem.';
$zmSlangForceAlarm           = 'Force&nbsp;Alarme';
$zmSlangFPS                  = 'i/s';
$zmSlangFPSReportInterval    = 'FPS Report Interval';
$zmSlangFrameId              = 'N° image';
$zmSlangFrame                = 'Image';
$zmSlangFrameRate            = 'Débit image';
$zmSlangFrames               = 'images';
$zmSlangFrameSkip            = 'Saut image';
$zmSlangFTP                  = 'FTP';
$zmSlangFunc                 = 'Fct';
$zmSlangFunction             = 'Fonction';
$zmSlangGenerateVideo        = 'Générer Vidéo';
$zmSlangGeneratingVideo      = 'Génération Vidéo';
$zmSlangGoToZoneMinder       = 'Aller sur ZoneMinder.com';
$zmSlangGrey                 = 'Gris';
$zmSlangHighBW               = 'Haut&nbsp;N/B';
$zmSlangHigh                 = 'Haut';
$zmSlangHour                 = 'Heure';
$zmSlangHue                  = 'Teinte';
$zmSlangIdle                 = 'Vide';
$zmSlangId                   = 'N°';
$zmSlangIgnore               = 'Ignorer';
$zmSlangImageBufferSize      = 'Taille tampon image';
$zmSlangImage                = 'Image';
$zmSlangInclude              = 'Inclure';
$zmSlangInverted             = 'Inversé';
$zmSlangLanguage             = 'Langue';
$zmSlangLast                 = 'Dernier';
$zmSlangLocal                = 'Local';
$zmSlangLoggedInAs           = 'Connecté cô';
$zmSlangLoggingIn            = 'Connexion';
$zmSlangLogin                = 'Login';
$zmSlangLogout               = 'Déconnexion';
$zmSlangLow                  = 'Bas';
$zmSlangLowBW                = 'Basse&nbsp;N/B';
$zmSlangMark                 = 'Marque';
$zmSlangMaxBrScore           = 'Score<br/>max';
$zmSlangMaximumFPS           = 'i/s maximum';
$zmSlangMax                  = 'Max';
$zmSlangMediumBW             = 'Moy.&nbsp;N/B';
$zmSlangMedium               = 'Medium';
$zmSlangMinAlarmGeMinBlob    = 'Minimum alarm pixels should be greater than or equal to minimum blob pixels';
$zmSlangMinAlarmGeMinFilter  = 'Minimum alarm pixels should be greater than or equal to minimum filter pixels';
$zmSlangMinAlarmPixelsLtMax  = 'Pixels alarme min. doit ê < pixels alarme max.';
$zmSlangMinBlobAreaLtMax     = 'Aire blob min. doit ê < aire blob maximum';
$zmSlangMinBlobsLtMax        = 'Blobs min. doit ê < blobs max.';
$zmSlangMinFilterPixelsLtMax = 'Pixels filtre min. doit ê < pixels filtre max';
$zmSlangMinPixelThresLtMax   = 'Seuil pixel min. doit ê < seuil pixel max.';
$zmSlangMisc                 = 'Div.';
$zmSlangMonitor              = 'Ecran';
$zmSlangMonitorIds           = 'N°&nbsp;écran';
$zmSlangMonitors             = 'Ecrans';
$zmSlangMontage              = 'Montage';
$zmSlangMonth                = 'Mois';
$zmSlangMustBeGe             = 'doit être sup. ou égal à';
$zmSlangMustBeLe             = 'doit être inf. ou égal à';
$zmSlangMustConfirmPassword  = 'Confirmez le mot de passe';
$zmSlangMustSupplyPassword   = 'Entrez un mot de passe';
$zmSlangMustSupplyUsername   = 'Entrez un nom d\'utilisateur';
$zmSlangName                 = 'Nom';
$zmSlangNetwork              = 'Réseau';
$zmSlangNew                  = 'Nouv.';
$zmSlangNewPassword          = 'Nouv. mt de passe';
$zmSlangNewState             = 'Nv état';
$zmSlangNewUser              = 'Nv util.';
$zmSlangNext                 = 'Suiv.';
$zmSlangNoFramesRecorded     = 'Pas d\'image enregistrée pour cet évènement';
$zmSlangNone                 = 'Aucun';
$zmSlangNoneAvailable        = 'Aucun disponible';
$zmSlangNo                   = 'Non';
$zmSlangNormal               = 'Normal';
$zmSlangNoSavedFilters       = 'Pasfiltressauv';
$zmSlangNoStatisticsRecorded = 'Pas de statistiques disponibles pour cet évènmnt/imag.';
$zmSlangOpEq                 = 'égal à';
$zmSlangOpGtEq               = 'plus grand ou égal à';
$zmSlangOpGt                 = 'sup. à';
$zmSlangOpIn                 = 'en lot';
$zmSlangOpLtEq               = 'inf. ou égal à';
$zmSlangOpLt                 = 'inf. à';
$zmSlangOpMatches            = 'correspond';
$zmSlangOpNe                 = 'diff. de';
$zmSlangOpNotIn              = 'pas en lot';
$zmSlangOpNotMatches         = 'ne correspond pas';
$zmSlangOptionHelp           = 'OptionAide';
$zmSlangOptionRestartWarning = 'These changes may not come into effect fully\nwhile the system is running. When you have\nfinished making your changes please ensure that\nyou restart ZoneMinder.';
$zmSlangOptions              = 'Options';
$zmSlangOrEnterNewName       = 'ou entrez nv nom';
$zmSlangOrientation          = 'Orientation';
$zmSlangOverwriteExisting    = 'Ecraser l\'existant';
$zmSlangPaged                = 'Paged';
$zmSlangParameter            = 'Paramètre';
$zmSlangPassword             = 'Mt de passe';
$zmSlangPasswordsDifferent   = 'Les 2 mots de passe sont différents';
$zmSlangPaths                = 'Paths';
$zmSlangPhoneBW              = 'Phone&nbsp;B/W';
$zmSlangPixels               = 'pixels';
$zmSlangPleaseWait           = 'Attendez';
$zmSlangPostEventImageBuffer = 'Post Event Image Buffer';
$zmSlangPreEventImageBuffer  = 'Pre Event Image Buffer<';
$zmSlangPrev                 = 'Prec.';
$zmSlangRate                 = 'Débit';
$zmSlangReal                 = 'Réel';
$zmSlangRecord               = 'Enreg.';
$zmSlangRefImageBlendPct     = 'Reference Image Blend %ge';
$zmSlangRefresh              = 'Rafraîchir';
$zmSlangRemoteHostName       = 'Remote Host Name';
$zmSlangRemoteHostPath       = 'Remote Host Path';
$zmSlangRemoteHostPort       = 'Remote Host Port';
$zmSlangRemoteImageColours   = 'Remote Image Colours';
$zmSlangRemote               = 'Remote';
$zmSlangRename               = 'Renommer';
$zmSlangReplay               = 'Ralenti';
$zmSlangResetEventCounts     = 'Rem. à 0 comptage des évts';
$zmSlangRestarting           = 'Redémarrage';
$zmSlangRestart              = 'Redémarrer';
$zmSlangRestrictedCameraIds  = 'N° caméras confid.';
$zmSlangRotateLeft           = 'Rotation g.';
$zmSlangRotateRight          = 'Rotation d.';
$zmSlangRunMode              = 'Run Mode';
$zmSlangRunning              = 'Ca tourne';
$zmSlangRunState             = 'Run State';
$zmSlangSaveAs               = 'Enr. ss';
$zmSlangSave                 = 'Enr.';
$zmSlangSaveFilter           = 'Save Filter';
$zmSlangScale                = 'Echelle';
$zmSlangScore                = 'Score';
$zmSlangSecs                 = 'Secs';
$zmSlangSectionlength        = 'Longueur section';
$zmSlangServerLoad           = 'Charge Serveur';
$zmSlangSetLearnPrefs        = 'Régler préf. apprises'; // This can be ignored for now
$zmSlangSetNewBandwidth      = 'Régler la bande passante';
$zmSlangSettings             = 'Réglages';
$zmSlangShowFilterWindow     = 'Montrerfen.filtre';
$zmSlangSource               = 'Source';
$zmSlangSourceType           = 'Source Type';
$zmSlangStart                = 'Démarrer';
$zmSlangState                = 'Etat';
$zmSlangStats                = 'Stats';
$zmSlangStatus               = 'Statut';
$zmSlangStills               = 'Photos';
$zmSlangStopped              = 'Arrêté';
$zmSlangStop                 = 'Stop';
$zmSlangStream               = 'Flux';
$zmSlangSystem               = 'Système';
$zmSlangTimeDelta            = 'Time Delta';
$zmSlangTimestampLabelFormat = 'Timestamp Label Format';
$zmSlangTimestampLabelX      = 'Timestamp Label X';
$zmSlangTimestampLabelY      = 'Timestamp Label Y';
$zmSlangTimestamp            = 'Timestamp';
$zmSlangTimeStamp            = 'Time Stamp';
$zmSlangTime                 = 'Temps';
$zmSlangTools                = 'Outils';
$zmSlangTotalBrScore         = 'Score<br/>total';
$zmSlangTriggers             = 'Déclenchements';
$zmSlangType                 = 'Type';
$zmSlangUnarchive            = 'Désarchiv.';
$zmSlangUnits                = 'Unités';
$zmSlangUnknown              = 'Inconnu';
$zmSlangUpdateAvailable      = 'Mise à jour de ZM dispo.';
$zmSlangUpdateNotNecessary   = 'Pas de mise à jour dispo.';
$zmSlangUseFilterExprsPost   = '&nbsp;filter&nbsp;expressions'; // This is used at the end of the phrase 'use N filter expressions'
$zmSlangUseFilterExprsPre    = 'Util.&nbsp;'; // This is used at the beginning of the phrase 'use N filter expressions'
$zmSlangUseFilter            = 'Util. Filtre';
$zmSlangUsername             = 'nom util.';
$zmSlangUsers                = 'Utils';
$zmSlangUser                 = 'Util.';
$zmSlangValue                = 'Valeur';
$zmSlangVersionIgnore        = 'Ignorer cette version';
$zmSlangVersionRemindDay     = 'Me rappeler ds 1 j.';
$zmSlangVersionRemindHour    = 'Me rappleler dans 1 h.';
$zmSlangVersionRemindNever   = 'Ne pas avertir des nvelles versions';
$zmSlangVersionRemindWeek    = 'Me rappeler ds 1 sem.';
$zmSlangVersion              = 'Version';
$zmSlangVideoGenFailed       = 'Echec génération vidéo!';
$zmSlangVideoGenParms        = 'Paramètres génération vidéo';
$zmSlangVideoSize            = 'taille vidéo';
$zmSlangVideo                = 'Vidéo';
$zmSlangViewAll              = 'Voir tt';
$zmSlangViewPaged            = 'Vue recherchée';
$zmSlangView                 = 'Voir';
$zmSlangWarmupFrames         = 'Images test';
$zmSlangWatch                = 'Regarder';
$zmSlangWeb                  = 'Web';
$zmSlangWeek                 = 'Semaine';
$zmSlangX10ActivationString  = 'X10:chaîne activation';
$zmSlangX10InputAlarmString  = 'X10:chaîne alarme entrée';
$zmSlangX10OutputAlarmString = 'X10:chaîne alarme sortie';
$zmSlangX10                  = 'X10';
$zmSlangYes                  = 'Oui';
$zmSlangYouNoPerms           = 'Permissions nécessaires pour cette ressource.';
$zmSlangZoneAlarmColour      = 'Couleur alarme (RGB)';
$zmSlangZoneAlarmThreshold   = 'Seuil alarme (0>=?<=255)';
$zmSlangZoneFilterHeight     = 'Haut. filtre (pixels)';
$zmSlangZoneFilterWidth      = 'Larg. filtre (pixels)';
$zmSlangZoneMaxAlarmedArea   = 'Aire max. d\'alarme';
$zmSlangZoneMaxBlobArea      = 'Maximum Blob Area';
$zmSlangZoneMaxBlobs         = 'Maximum Blobs';
$zmSlangZoneMaxFilteredArea  = 'Aire filtrée max.';
$zmSlangZoneMaxPixelThres    = 'Seuil pixel max. (0>=?<=255)';
$zmSlangZoneMaxX             = 'X Maximum (dr.)';
$zmSlangZoneMaxY             = 'Y Maximum (bas)';
$zmSlangZoneMinAlarmedArea   = 'Aire min. d\'alarme';
$zmSlangZoneMinBlobArea      = 'Minimum Blob Area';
$zmSlangZoneMinBlobs         = 'Minimum Blobs';
$zmSlangZoneMinFilteredArea  = 'Aire filtrée minimum';
$zmSlangZoneMinPixelThres    = 'Seuil pixel min. (0>=?<=255)';
$zmSlangZoneMinX             = 'X Minimum (gau.)';
$zmSlangZoneMinY             = 'Y Minimum (som.)';
$zmSlangZones                = 'Zones';
$zmSlangZone                 = 'Zone';

// Complex replacements with formatting and/or placements, must be passed through sprintf
$zmClangCurrentLogin         = 'Util. Actuel: \'%1$s\'';
$zmClangEventCount           = '%1$s %2$s'; // par ex. '37 évènts' (voir Vlang ci-dessous)
$zmClangLastEvents           = '%1$s derniers %2$s'; // par ex. '37 derniers  évènts' (voir Vlang ci-dessous)
$zmClangLatestRelease        = 'La dernière version est v%1$s, vous avez v%2$s.';
$zmClangMonitorCount         = '%1$s %2$s'; // par exemple '4 écrans' (voir Vlang ci-dessous)
$zmClangMonitorFunction      = 'Ecran %1$s Fonction';
$zmClangRunningRecentVer     = 'Vs avez la dernière version de ZoneMinder, v%s.';

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
$zmVlangEvent                = array( 0=>'évènts', 1=>'évènt', 2=>'évènts' );
$zmVlangMonitor              = array( 0=>'écrans', 1=>'écran', 2=>'écrans' );

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
