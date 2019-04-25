<?php
//
// ZoneMinder web UK French language file, $Date$, $Revision$
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

// Set date and time format (example: sam. 18 avril, 19h02)
setlocale(LC_ALL, "fr_FR.UTF-8");
define("DATE_FMT_CONSOLE_LONG", "%a %d %b, %Hh%M");
define( "STRF_FMT_DATETIME_SHORT", "%d/%m/%y %H:%M:%S" );
define( "STRF_FMT_DATETIME_SHORTER", "%d/%m %H:%M:%S" );

// Simple String Replacements
$SLANG = array(
    '24BitColour'          => 'Couleur 24 bits',
    '32BitColour'          => 'Couleur 32 bits',          // Added - 2011-06-15
    '8BitGrey'             => 'Gris 8 bits',
    'Action'               => 'Action',
    'Actual'               => 'Réel',
    'AddNewControl'        => 'Ajouter contrôle',
    'AddNewMonitor'        => 'Ajouter caméra',
    'AddNewServer'         => 'Add New Server',         // Added - 2018-08-30
    'AddNewStorage'        => 'Add New Storage',        // Added - 2018-08-30
    'AddNewUser'           => 'Ajouter utilisateur',
    'AddNewZone'           => 'Ajouter zone',
    'Alarm'                => 'Alarme',
    'AlarmBrFrames'        => 'Images<br/>alarme',
    'AlarmFrame'           => 'Image alarme',
    'AlarmFrameCount'      => 'Nb d\'image(s) en alarme',
    'AlarmLimits'          => 'Limites alarme',
    'AlarmMaximumFPS'      => 'i/s maximum pendant alarme',
    'AlarmPx'              => 'Px alarme',
    'AlarmRGBUnset'        => 'Vous devez définir une couleur RVB pour l\'alarme',
    'AlarmRefImageBlendPct'=> '% fusion image référence en alarme', // Added - 2015-04-18
    'Alert'                => 'Alerte',
    'All'                  => 'Tous',
    'AnalysisFPS'          => 'i/s à traiter en analyse',           // Added - 2015-07-22
    'AnalysisUpdateDelay'  => 'Délai mise à jour analyse',  // Added - 2015-07-23
    'Apply'                => 'Appliquer',
    'ApplyingStateChange'  => 'Appl. chgt état',
    'ArchArchived'         => 'Archivé seul.',
    'ArchUnarchived'       => 'Non-arch. seul.',
    'Archive'              => 'Archiver',
    'Archived'             => 'Archivés',
    'Area'                 => 'Surface',
    'AreaUnits'            => 'Surface (px/%)',
    'AttrAlarmFrames'      => 'Images alarme',
    'AttrArchiveStatus'    => 'Etat Archive',
    'AttrAvgScore'         => 'Score moy.',
    'AttrCause'            => 'Cause',
    'AttrDiskBlocks'       => 'Blocs disque',
    'AttrDiskPercent'      => '% disque',
    'AttrDiskSpace'        => 'Disk Space',             // Added - 2018-08-30
    'AttrDuration'         => 'Durée',
    'AttrEndDate'          => 'End Date',               // Added - 2018-08-30
    'AttrEndDateTime'      => 'End Date/Time',          // Added - 2018-08-30
    'AttrEndTime'          => 'End Time',               // Added - 2018-08-30
    'AttrEndWeekday'       => 'End Weekday',            // Added - 2018-08-30
    'AttrFilterServer'     => 'Server Filter is Running On', // Added - 2018-08-30
    'AttrFrames'           => 'Images',
    'AttrId'               => 'Id',
    'AttrMaxScore'         => 'Score max.',
    'AttrMonitorId'        => 'N°',
    'AttrMonitorName'      => 'Nom caméra',
    'AttrMonitorServer'    => 'Server Monitor is Running On', // Added - 2018-08-30
    'AttrName'             => 'Nom',
    'AttrNotes'            => 'Notes',
    'AttrStartDate'        => 'Start Date',             // Added - 2018-08-30
    'AttrStartDateTime'    => 'Start Date/Time',        // Added - 2018-08-30
    'AttrStartTime'        => 'Start Time',             // Added - 2018-08-30
    'AttrStartWeekday'     => 'Start Weekday',          // Added - 2018-08-30
    'AttrStateId'          => 'Run State',              // Added - 2018-08-30
    'AttrStorageArea'      => 'Storage Area',           // Added - 2018-08-30
    'AttrStorageServer'    => 'Server Hosting Storage', // Added - 2018-08-30
    'AttrSystemLoad'       => 'Charge système',
    'AttrTotalScore'       => 'Score total',
    'Auto'                 => 'Auto',
    'AutoStopTimeout'      => 'Temporisation arrêt',
    'Available'            => 'Disponibles',              // Added - 2009-03-31
    'AvgBrScore'           => 'Score<br/>moy.',
    'Background'           => 'Arrière-plan',
    'BackgroundFilter'     => 'Lancer les filtres en arrière-plan',
    'BadAlarmFrameCount'   => 'Le nombre d\'images en alarme doit être un entier supérieur ou égal à 1',
    'BadAlarmMaxFPS'       => 'Le nombre maximum d\'i/s en alarme doit être un entier ou un nombre à virgule flottante supérieur à 0',
    'BadAnalysisFPS'       => 'Le nombre d\'i/s à traiter en analyse doit être un entier ou un nombre à virgule flottante supérieur à 0', // Added - 2015-07-22
    'BadAnalysisUpdateDelay'=> 'Le délai de mise à jour analyse doit être un nombre entier supérieur ou égal à 0', // Added - 2015-07-23
    'BadChannel'           => 'Le canal doit être un nombre entier supérieur ou égal à 0',
    'BadColours'           => 'La valeur de la couleur cible est invalide', // Added - 2011-06-15
    'BadDevice'            => 'Le chemin de l\'équipement être défini',
    'BadFPSReportInterval' => 'L\'intervalle de rafraîchissement  de l\'information i/s doit être un entier supérieur ou égal à 0',
    'BadFormat'            => 'Le format doit être un nombre entier supérieur ou égal à 0',
    'BadFrameSkip'         => 'Le nombre d\'images à sauter doit être un entier supérieur ou égal à 0',
    'BadHeight'            => 'La valeur de la hauteur est invalide',
    'BadHost'              => 'Le nom d\'hôte doit être une adresse ip ou un nom dns valide sans le préfixe http://',
    'BadImageBufferCount'  => 'La taille du tampon d \'images doit être un entier supérieur ou égal à 10',
    'BadLabelX'            => 'La coordonnée X pour l\'horodatage doit être un entier supérieur ou égal à 0',
    'BadLabelY'            => 'La coordonnée Y pour l\'horodatage doit être un entier supérieur ou égal à 0',
    'BadMaxFPS'            => 'Le nombre maximum d\'i/s doit être un entier ou un nombre à virgule flottante supérieur à 0',
    'BadMotionFrameSkip'   => 'Le nombre d\'images à sauter en cas de détection doit être un entier supérieur ou égal à 0',
    'BadNameChars'         => 'Les noms ne peuvent contenir que des lettres, chiffres, les espaces, trait d\'union ou souligné',
    'BadPalette'           => 'La palette doit être définie', // Added - 2009-03-31
    'BadPath'              => 'Le chemin doit être défini',
    'BadPort'              => 'Le port doit être un nombre entier supérieur ou égal à 0',
    'BadPostEventCount'    => 'Le nombre d\'images post-événements doit être un entier supérieur ou égal à 0',
    'BadPreEventCount'     => 'Le nombre d\'images pré-événements doit être un entier supérieur ou égal à 0 et doit rester inférieur à la taille du tampon d\'images',
    'BadRefBlendPerc'      => 'Le pourcentage de fusion de l\'image de référence doit être un entier supérieur à 0 et inférieur à 100',
    'BadSectionLength'     => 'La longueur de la section doit être un entier supérieur ou égal à 30',
    'BadSignalCheckColour' => 'La chaîne de caractères pour la couleur d\'état du signal est invalide',
    'BadSourceType'        => 'Source Type \"Web Site\" requires the Function to be set to \"Monitor\"', // Added - 2018-08-30
    'BadStreamReplayBuffer'=> 'Le tampon d\'images pour la relecture doit être un entier supérieur ou égal à 0',
    'BadWarmupCount'       => 'Le nombre d\'images tests doit être un entier supérieur ou égal à 0',
    'BadWebColour'         => 'La chaîne de caractères pour la couleur web est invalide',
    'BadWebSitePath'       => 'Please enter a complete website url, including the http:// or https:// prefix.', // Added - 2018-08-30
    'BadWidth'             => 'La valeur de la largeur est invalide',
    'Bandwidth'            => 'Débit',
    'BandwidthHead'        => 'Débit',	// This is the end of the bandwidth status on the top of the console, different in many language due to phrasing
    'BlobPx'               => 'Pix. forme',
    'BlobSizes'            => 'Tailles de forme',
    'Blobs'                => 'Formes',
    'Brightness'           => 'Luminosité;',
    'Buffer'               => 'Tampon',                 // Added - 2015-04-18
    'Buffers'              => 'Tampons',
    'CSSDescription'       => 'Remplacer la feuille de style CSS par défaut', // Added - 2015-04-18
    'CanAutoFocus'         => 'Automatique',
    'CanAutoGain'          => 'Automatique',
    'CanAutoIris'          => 'Automatique',
    'CanAutoWhite'         => 'Automatique',
    'CanAutoZoom'          => 'Automatique',
    'CanFocus'             => 'Focus',
    'CanFocusAbs'          => 'Absolu',
    'CanFocusCon'          => 'Continu',
    'CanFocusRel'          => 'Relatif',
    'CanGain'              => 'Gain',
    'CanGainAbs'           => 'Absolu',
    'CanGainCon'           => 'Continu',
    'CanGainRel'           => 'Relatif',
    'CanIris'              => 'Iris',
    'CanIrisAbs'           => 'Absolu',
    'CanIrisCon'           => 'Continu',
    'CanIrisRel'           => 'Relatif',
    'CanMove'              => 'Déplacer',
    'CanMoveAbs'           => 'Absolu',
    'CanMoveCon'           => 'Continu',
    'CanMoveDiag'          => 'Diagonale',
    'CanMoveMap'           => 'Sur plan',
    'CanMoveRel'           => 'Relatif',
    'CanPan'               => 'Panoramique' ,
    'CanReset'             => 'RàZ',
	'CanReboot'             => 'Can Reboot',
    'CanSetPresets'        => 'Stockage prépos.',
    'CanSleep'             => 'Veille',
    'CanTilt'              => 'Inclinaison',
    'CanWake'              => 'Réveil',
    'CanWhite'             => 'Bal. des blancs',
    'CanWhiteAbs'          => 'Absolu',
    'CanWhiteBal'          => 'Bal. des blancs',
    'CanWhiteCon'          => 'Continu',
    'CanWhiteRel'          => 'Relatif',
    'CanZoom'              => 'Zoom',
    'CanZoomAbs'           => 'Absolu',
    'CanZoomCon'           => 'Continu',
    'CanZoomRel'           => 'Relatif',
    'Cancel'               => 'Annuler',
    'CancelForcedAlarm'    => 'Annuler alarme forcée',
    'CaptureHeight'        => 'Hauteur',
    'CaptureMethod'        => 'Méthode',         // Added - 2009-02-08
    'CapturePalette'       => 'Palette',
    'CaptureResolution'    => 'Résolution',     // Added - 2015-04-18
    'CaptureWidth'         => 'Largeur',
    'Cause'                => 'Cause',
    'CheckMethod'          => 'Méthode vérif. alarme',
    'ChooseDetectedCamera' => 'Choisir', // Added - 2009-03-31
    'ChooseFilter'         => 'Choisir filtre',
    'ChooseLogFormat'      => 'Choisir un format de journal',    // Added - 2011-06-17
    'ChooseLogSelection'   => 'Choisir une sélection de journaux', // Added - 2011-06-17
    'ChoosePreset'         => 'Choisir préréglage',
    'Clear'                => 'Effacer',                  // Added - 2011-06-16
    'CloneMonitor'         => 'Clone',                  // Added - 2018-08-30
    'Close'                => 'Fermer',
    'Colour'               => 'Couleur',
    'Command'              => 'Commande',
    'Component'            => 'Composant',              // Added - 2011-06-16
    'ConcurrentFilter'     => 'Run filter concurrently', // Added - 2018-08-30
    'Config'               => 'Config',
    'ConfiguredFor'        => 'Configuré pour',
    'ConfirmDeleteEvents'  => 'Etes-vous sûr de vouloir effacer le(s) événement(s) sélectionné(s)?',
    'ConfirmPassword'      => 'Répéter mot de passe',
    'ConjAnd'              => 'et',
    'ConjOr'               => 'ou',
    'Console'              => 'Console',
    'ContactAdmin'         => 'Contactez votre administrateur SVP',
    'Continue'             => 'Continuer',
    'Contrast'             => 'Contraste',
    'Control'              => 'Contrôle',
    'ControlAddress'       => 'Adresse',
    'ControlCap'           => 'Capacité de contrôle',
    'ControlCaps'          => 'Capacités de contrôle',
    'ControlDevice'        => 'Equipement',
    'ControlType'          => 'Type',
    'Controllable'         => 'Controlâble',
    'Current'              => 'En cours',                // Added - 2015-04-18
    'Cycle'                => 'Cycle',
    'CycleWatch'           => 'Vision de cycle',
    'DateTime'             => 'Date/Heure',              // Added - 2011-06-16
    'Day'                  => 'Aujourd\'hui',
    'Debug'                => 'Debug',
    'DefaultRate'          => 'Vitesse par défaut',
    'DefaultScale'         => 'Echelle par défaut',
    'DefaultView'          => 'Vue par défaut',
    'Deinterlacing'        => 'Désentrelacement',          // Added - 2015-04-18
    'Delay'                => 'Délai',                  // Added - 2015-04-18
    'Delete'               => 'Effacer',
    'DeleteAndNext'        => 'Eff. &amp; suiv.',
    'DeleteAndPrev'        => 'Eff. &amp; prec.',
    'DeleteSavedFilter'    => 'Eff. filtre sauvé',
    'Description'          => 'Description',
    'DetectedCameras'      => 'Caméra(s) détectée(s)',       // Added - 2009-03-31
    'DetectedProfiles'     => 'Profil(s) détecté(s)',      // Added - 2015-04-18
    'Device'               => 'Equipement',                 // Added - 2009-02-08
    'DeviceChannel'        => 'Canal',
    'DeviceFormat'         => 'Format vidéo',
    'DeviceNumber'         => 'Numéro caméra',
    'DevicePath'           => 'Chemin de l\'équipement',
    'Devices'              => 'Caméras',
    'Dimensions'           => 'Dimensions',
    'DisableAlarms'        => 'Désactiver les alarmes',
    'Disk'                 => 'Stockage',
    'Display'              => 'Affichage',                // Added - 2011-01-30
    'Displaying'           => 'Affichés',             // Added - 2011-06-16
    'DoNativeMotionDetection'=> 'Réaliser détection native',
    'Donate'               => 'Veuillez faire un don',
    'DonateAlready'        => 'Non, j\'ai déjà donné',
    'DonateEnticement'     => 'Vous utilisez ZoneMinder depuis quelque temps et nous espérons que vous trouvez cette solution utile. Bien que ZoneMinder est, et restera, une solution libre et ouverte (open source), son développement et son maintien nécessitent des moyens financiers. Si vous voulez aider au développement et à l\'ajout de fonctionnalités, veuillez considérer la possibilité d\'effectuer un don. Les dons sont bien sûr optionnels mais grandement appréciés et vous pouvez donner le montant que vous désirez.<br><br>Si vous voulez effectuer un don, veuillez sélectionner l\'option ci-dessous ou veuillez vous rendre sur https://zoneminder.com/donate/ à l\'aide de votre navigateur internet.<br><br>Merci d\'utiliser ZoneMinder et n\'oubliez pas de visiter les forums sur ZoneMinder.com pour le support ou des suggestions pour rendre votre expérience de ZoneMinder encore meilleure.',
    'DonateRemindDay'      => 'Pas encore, me rappeler dans 1 jour',
    'DonateRemindHour'     => 'Pas encore, me rappeler dans 1 heure',
    'DonateRemindMonth'    => 'Pas encore, me rappeler dans 1 mois',
    'DonateRemindNever'    => 'Non, je ne veux pas faire de don, ne me rappeler pas',
    'DonateRemindWeek'     => 'Pas encore, me rappeler dans 1 semaine',
    'DonateYes'            => 'Oui, je souhaiterais faire un don maintenant',
    'Download'             => 'Télécharger',
    'DownloadVideo'        => 'Download Video',         // Added - 2018-08-30
    'DuplicateMonitorName' => 'Dupliquer le nom de la caméra', // Added - 2009-03-31
    'Duration'             => 'Durée',
    'Edit'                 => 'Editer',
    'EditLayout'           => 'Edit Layout',            // Added - 2018-08-30
    'Email'                => 'Email',
    'EnableAlarms'         => 'Activer les alarmes',
    'Enabled'              => 'Activé',
    'EnterNewFilterName'   => 'Entrer nom nouv. filtre',
    'Error'                => 'Erreur',
    'ErrorBrackets'        => 'Erreur, vérifiez que toutes les parenthèses ouvertes sont fermées',
    'ErrorValidValue'      => 'Erreur, vérifiez que tous les termes ont une valeur valide',
    'Etc'                  => 'etc',
    'Event'                => 'Evénement',
    'EventFilter'          => 'Filtre événement',
    'EventId'              => 'Id',
    'EventName'            => 'Nom',
    'EventPrefix'          => 'Préfixe',
    'Events'               => 'Evénements',
    'Exclude'              => 'Exclure',
    'Execute'              => 'Exécuter',
    'Exif'                 => 'Embed EXIF data into image', // Added - 2018-08-30
    'Export'               => 'Exporter',
    'ExportDetails'        => 'Exporter détails événements',
    'ExportFailed'         => 'Exportation échouée',
    'ExportFormat'         => 'Format',
    'ExportFormatTar'      => 'Tar',
    'ExportFormatZip'      => 'Zip',
    'ExportFrames'         => 'Exporter détails image',
    'ExportImageFiles'     => 'Exporter fichiers images',
    'ExportLog'            => 'Exporter fichiers journaux',             // Added - 2011-06-17
    'ExportMiscFiles'      => 'Exporter autres fichiers',
    'ExportOptions'        => 'Options d\'exportation',
    'ExportSucceeded'      => 'Exportation réussie',       // Added - 2009-02-08
    'ExportVideoFiles'     => 'Exporter fichiers vidéo',
    'Exporting'            => 'Exportation',
    'FPS'                  => 'i/s',
    'FPSReportInterval'    => 'Interv. de rafraîch. i/s',
    'FTP'                  => 'FTP',
    'Far'                  => 'Loin',
    'FastForward'          => 'Avance rapide',
    'Feed'                 => 'Flux',
    'Ffmpeg'               => 'Ffmpeg',                 // Added - 2009-02-08
    'File'                 => 'Fichier',
    'Filter'               => 'Filtre',                 // Added - 2015-04-18
    'FilterArchiveEvents'  => 'Archiver',
    'FilterDeleteEvents'   => 'Effacer',
    'FilterEmailEvents'    => 'Envoyer les détails par email',
    'FilterExecuteEvents'  => 'Exécuter une commande',
    'FilterLog'            => 'Filtre',             // Added - 2015-04-18
    'FilterMessageEvents'  => 'Envoyer les détails par message',
    'FilterMoveEvents'     => 'Move all matches',       // Added - 2018-08-30
    'FilterPx'             => 'Filtre Px',
    'FilterUnset'          => 'Vous devez spécifier une largeur et une hauteur de filtre',
    'FilterUpdateDiskSpace'=> 'Update used disk space', // Added - 2018-08-30
    'FilterUploadEvents'   => 'Transférer',
    'FilterVideoEvents'    => 'Créer vidéo',
    'Filters'              => 'Filtres',
    'First'                => 'Prem.',
    'FlippedHori'          => 'Inversé horizontalement',
    'FlippedVert'          => 'Inversé verticalement',
    'FnMocord'              => 'Mocord',            // Added 2013.08.16.
    'FnModect'              => 'Modect',            // Added 2013.08.16.
    'FnMonitor'             => 'Monitor',            // Added 2013.08.16.
    'FnNodect'              => 'Nodect',            // Added 2013.08.16.
    'FnNone'                => 'Aucun',            // Added 2013.08.16.
    'FnRecord'              => 'Record',            // Added 2013.08.16.
    'Focus'                => 'Focus',
    'ForceAlarm'           => 'Forcer alarme',
    'Format'               => 'Format',
    'Frame'                => 'Image',
    'FrameId'              => 'N°',
    'FrameRate'            => 'Cadence image',
    'FrameSkip'            => 'Saut image',
    'Frames'               => 'Images',
    'Func'                 => 'Fct',
    'Function'             => 'Mode',
    'Gain'                 => 'Gain',
    'General'              => 'Général',
    'GenerateDownload'     => 'Generate Download',      // Added - 2018-08-30
    'GenerateVideo'        => 'Générer vidéo',
    'GeneratingVideo'      => 'Génération vidéo',
    'GoToZoneMinder'       => 'Aller sur ZoneMinder.com',
    'Grey'                 => 'Gris',
    'Group'                => 'Groupe',
    'Groups'               => 'Groupes',
    'HasFocusSpeed'        => 'Vitesse',
    'HasGainSpeed'         => 'Vitesse gain',
    'HasHomePreset'        => 'Position par défaut',
    'HasIrisSpeed'         => 'Vitesse',
    'HasPanSpeed'          => 'Vitesse',
    'HasPresets'           => 'Prépositions',
    'HasTiltSpeed'         => 'Vitesse',
    'HasTurboPan'          => 'Turbo',
    'HasTurboTilt'         => 'Incl. turbo',
    'HasWhiteSpeed'        => 'Vitesse',
    'HasZoomSpeed'         => 'Vitesse',
    'High'                 => 'Haut',
    'HighBW'               => 'Haut débit',
    'Home'                 => 'Maison',
    'Hostname'             => 'Hostname',               // Added - 2018-08-30
    'Hour'                 => 'Heure',
    'Hue'                  => 'Teinte',
    'Id'                   => 'N°',
    'Idle'                 => 'Vide',
    'Ignore'               => 'Ignorer',
    'Image'                => 'Image',
    'ImageBufferSize'      => 'Taille tampon image',
    'Images'               => 'Images',
    'In'                   => 'Dans',
    'Include'              => 'Inclure',
    'Inverted'             => 'Inversé',
    'Iris'                 => 'Iris',
    'KeyString'            => 'Chaîne clé',
    'Label'                => 'Etiquette',
    'Language'             => 'Langue',
    'Last'                 => 'Dernier',
    'Layout'               => 'Disposition',                 // Added - 2009-02-08
    'Level'                => 'Niveau',                  // Added - 2011-06-16
    'Libvlc'               => 'Libvlc',
    'LimitResultsPost'     => 'résultat(s) seulement', // This is used at the end of the phrase 'Limit to first N results only'
    'LimitResultsPre'      => 'Limiter au(x) premier(s)', // This is used at the beginning of the phrase 'Limit to first N results only'
    'Line'                 => 'Ligne',                   // Added - 2011-06-16
    'LinkedMonitors'       => 'Caméra(s) liée(s)',
    'List'                 => 'Liste',
    'ListMatches'          => 'List Matches',           // Added - 2018-08-30
    'Load'                 => 'Charge',
    'Local'                => 'Local',
    'Log'                  => 'Journal',                    // Added - 2011-06-16
    'LoggedInAs'           => 'Connecté en tant que',
    'Logging'              => 'Journalisation',                // Added - 2011-06-16
    'LoggingIn'            => 'Connexion',
    'Login'                => 'Connexion',
    'Logout'               => 'Déconnexion',
    'Logs'                 => 'Journaux',                   // Added - 2011-06-17
    'Low'                  => 'Bas',
    'LowBW'                => 'Bas débit',
    'Main'                 => 'Principal',
    'Man'                  => 'Man',
    'Manual'               => 'Manuel',
    'Mark'                 => 'Sélectionner',
    'Max'                  => 'Max',
    'MaxBandwidth'         => 'Débit max',
    'MaxBrScore'           => 'Score<br/>max',
    'MaxFocusRange'        => 'Plage max',
    'MaxFocusSpeed'        => 'Vitesse max',
    'MaxFocusStep'         => 'Pas max',
    'MaxGainRange'         => 'Plage gain max',
    'MaxGainSpeed'         => 'Vitesse gain max',
    'MaxGainStep'          => 'Pas gain max',
    'MaxIrisRange'         => 'Plage max',
    'MaxIrisSpeed'         => 'Vitesse max',
    'MaxIrisStep'          => 'Pas max',
    'MaxPanRange'          => 'Plage max',
    'MaxPanSpeed'          => 'Vitesse max',
    'MaxPanStep'           => 'Pas max',
    'MaxTiltRange'         => 'Plage max',
    'MaxTiltSpeed'         => 'Vitesse max',
    'MaxTiltStep'          => 'Pas max',
    'MaxWhiteRange'        => 'Plage max',
    'MaxWhiteSpeed'        => 'Vitesse max',
    'MaxWhiteStep'         => 'Pas max',
    'MaxZoomRange'         => 'Plage max',
    'MaxZoomSpeed'         => 'Vitesse max',
    'MaxZoomStep'          => 'Pas max',
    'MaximumFPS'           => 'i/s maximum',
    'Medium'               => 'Moyen',
    'MediumBW'             => 'Moy. débit',
    'Message'              => 'Message',                // Added - 2011-06-16
    'MinAlarmAreaLtMax'    => 'La surface minimum en alarme doit être inférieure au maximum',
    'MinAlarmAreaUnset'    => 'Vous devez spécifier la surface minimum en alarme (nb de pixels)',
    'MinBlobAreaLtMax'     => 'La surface minimum des formes doit être inférieure au maximum',
    'MinBlobAreaUnset'     => 'Vous devez spécifier la surface minimum des formes (nb de pixels)',
    'MinBlobLtMinFilter'   => 'La surface minimum des formes doit être inférieure à la surface minimum filtrée',
    'MinBlobsLtMax'        => 'Le nombre minimum de formes doit être inférieur au maximum',
    'MinBlobsUnset'        => 'Vous devez spécifier le nombre minimum de formes',
    'MinFilterAreaLtMax'   => 'La surface minimum filtrée doit être inférieure au maximum',
    'MinFilterAreaUnset'   => 'Vous devez spécifier la surface minimum filtrée (nb de pixels)',
    'MinFilterLtMinAlarm'  => 'La surface minimum filtrée doit être inférieure à la surface minimum en alarme',
    'MinFocusRange'        => 'Plage min',
    'MinFocusSpeed'        => 'Vitesse min',
    'MinFocusStep'         => 'Pas min',
    'MinGainRange'         => 'Plage gain min',
    'MinGainSpeed'         => 'Vitesse gain min',
    'MinGainStep'          => 'Pas gain min',
    'MinIrisRange'         => 'Plage min',
    'MinIrisSpeed'         => 'Vitesse min',
    'MinIrisStep'          => 'Pas min',
    'MinPanRange'          => 'Plage min',
    'MinPanSpeed'          => 'Vitesse min',
    'MinPanStep'           => 'Pas min',
    'MinPixelThresLtMax'   => 'Le seuil minimum de pixels doit être inférieur au maximum',
    'MinPixelThresUnset'   => 'Vous devez spécifier le seuil minimum de pixels',
    'MinTiltRange'         => 'Plage min',
    'MinTiltSpeed'         => 'Vitesse min',
    'MinTiltStep'          => 'Pas min',
    'MinWhiteRange'        => 'Plage min',
    'MinWhiteSpeed'        => 'Vitesse min',
    'MinWhiteStep'         => 'Pas min',
    'MinZoomRange'         => 'Plage min',
    'MinZoomSpeed'         => 'Vitesse min',
    'MinZoomStep'          => 'Pas min',
    'Misc'                 => 'Divers',
    'Mode'                 => 'Mode',                   // Added - 2015-04-18
    'Monitor'              => 'Caméra',
    'MonitorIds'           => 'N°&nbsp;caméra',
    'MonitorPreset'        => 'Préréglage caméra',
    'MonitorPresetIntro'   => 'Sélectionnez un préréglage dans la liste ci-dessous.<br><br>Veuillez noter que la sauvegarde entraînera l\'écrasement des paramètres déjà configurés pour la caméra en cours.<br><br>',
    'MonitorProbe'         => 'Autodétection caméras',          // Added - 2009-03-31
    'MonitorProbeIntro'    => 'La liste ci-dessous montre les caméras détectées localement ou sur le réseau, qu\'elles soient déjà configurées ou non.<br/><br/>Sélectionnez la caméra désirée dans la liste.<br/><br/>Veuillez noter que toutes les caméras ne sont pas forcément détectées et que la sauvegarde entraînera l\'écrasement des paramètres déjà configurés pour la caméra en cours.<br/><br/>', // Added - 2009-03-31
    'Monitors'             => 'Caméras',
    'Montage'              => 'Montage',
    'MontageReview'        => 'Montage Review',         // Added - 2018-08-30
    'Month'                => 'Mois',
    'More'                 => 'Plus',                   // Added - 2011-06-16
    'MotionFrameSkip'      => 'Saut image en alarme',
    'Move'                 => 'Déplacement',
    'Mtg2widgrd'           => '2 colonnes',              // Added 2013.08.15.
    'Mtg3widgrd'           => '3 colonnes',              // Added 2013.08.15.
    'Mtg3widgrx'           => '3 colonnes, échelle auto, élargir sur alarme',              // Added 2013.08.15.
    'Mtg4widgrd'           => '4 colonnes',              // Added 2013.08.15.
    'MtgDefault'           => 'Défaut',              // Added 2013.08.15.
    'MustBeGe'             => 'doit être sup. ou égal à',
    'MustBeLe'             => 'doit être inf. ou égal à',
    'MustConfirmPassword'  => 'Confirmez le mot de passe',
    'MustSupplyPassword'   => 'Entrez un mot de passe',
    'MustSupplyUsername'   => 'Entrez un nom d\'utilisateur',
    'Name'                 => 'Nom',
    'Near'                 => 'Près',
    'Network'              => 'Réseau',
    'New'                  => 'Nouv.',
    'NewGroup'             => 'Nouv. groupe',
    'NewLabel'             => 'Nouv. label',
    'NewPassword'          => 'Mot de passe',
    'NewState'             => 'Nouv. état',
    'NewUser'              => 'Nouv. utilisateur',
    'Next'                 => 'Suivant',
    'No'                   => 'Non',
    'NoDetectedCameras'    => 'Pas de caméras détectées',    // Added - 2009-03-31
    'NoDetectedProfiles'   => 'No Detected Profiles',   // Added - 2018-08-30
    'NoFramesRecorded'     => 'Pas d\'images enregistrées pour cet événement',
    'NoGroup'              => 'Pas de groupe',
    'NoSavedFilters'       => 'Pas de filtres sauvegardés',
    'NoStatisticsRecorded' => 'Pas de statistiques disponibles pour cet événmnt/imag.',
    'None'                 => 'Aucun',
    'NoneAvailable'        => 'Aucun disponible',
    'Normal'               => 'Normal',
    'Notes'                => 'Notes',
    'NumPresets'           => 'Nombre',
    'Off'                  => 'Désactiver',
    'On'                   => 'Activer',
    'OnvifCredentialsIntro'=> 'Veuillez fournir un nom d\'utilisateur et un mot de passe pour la caméra sélectionnée.<br/>Si aucun utilisateur n\'a été créé pour la caméra alors l\'utilisateur saisi sera créé avec le mot de passe associé.<br/><br/>', // Added - 2015-04-18
    'OnvifProbe'           => 'ONVIF',                  // Added - 2015-04-18
    'OnvifProbeIntro'      => 'La liste ci-dessous montre les caméras ONVIF détectées et si elles sont déjà utilisées ou disponibles.<br/><br/>Sélectionnez la caméra souhaitée dans la liste ci-dessous.<br/><br/>Veuillez noter que toutes les caméras ne sont pas forcément détectées et que la sauvegarde entraînera l\'écrasement des paramètres déjà configurés pour la caméra en cours.<br/><br/>', // Added - 2015-04-18
    'OpEq'                 => 'égal à',
    'OpGt'                 => 'sup. à',
    'OpGtEq'               => 'plus grand ou égal à',
    'OpIn'                 => 'en lot',
    'OpIs'                 => 'is',                     // Added - 2018-08-30
    'OpIsNot'              => 'is not',                 // Added - 2018-08-30
    'OpLt'                 => 'inf. à',
    'OpLtEq'               => 'inf. ou égal à',
    'OpMatches'            => 'correspond',
    'OpNe'                 => 'diff. de',
    'OpNotIn'              => 'pas en lot',
    'OpNotMatches'         => 'ne correspond pas',
    'Open'                 => 'Ouvrir',
    'OptionHelp'           => 'Aide',
    'OptionRestartWarning' => 'Ces changements peuvent nécessiter un redémarrage de ZoneMinder pour être pleinement opérationnels.',
    'OptionalEncoderParam' => 'Optional Encoder Parameters', // Added - 2018-08-30
    'Options'              => 'Options',
    'OrEnterNewName'       => 'ou entrez nouv. nom',
    'Order'                => 'Ordre',
    'Orientation'          => 'Orientation',
    'Out'                  => 'Arrière',
    'OverwriteExisting'    => 'Ecraser l\'existant',
    'Paged'                => 'Paginée',
    'Pan'                  => 'Panoramique',
    'PanLeft'              => 'Pano. gauche',
    'PanRight'             => 'Pano. droite',
    'PanTilt'              => 'Pano. / Incl.',
    'Parameter'            => 'Paramètre',
    'Password'             => 'Mot de passe',
    'PasswordsDifferent'   => 'Les 2 mots de passe sont différents',
    'Paths'                => 'Chemins',
    'Pause'                => 'Pause',
    'Phone'                => 'Téléphone',
    'PhoneBW'              => 'Débit tél.',
    'Pid'                  => 'PID',                    // Added - 2011-06-16
    'PixelDiff'            => 'Diff. pixel',
    'Pixels'               => 'nb pixels',
    'Play'                 => 'Lire',
    'PlayAll'              => 'Tout lire',
    'PleaseWait'           => 'Attendez',
    'Plugins'              => 'Greffons',
    'Point'                => 'Point',
    'PostEventImageBuffer' => 'Nb d\'image(s) post-événement',
    'PreEventImageBuffer'  => 'Nb d\'image(s) pré-événement',
    'PreserveAspect'       => 'Préserver les proportions',
    'Preset'               => 'Préréglage',
    'Presets'              => 'Préréglages',
    'Prev'                 => 'Précédent',
    'Probe'                => 'Autodétection',                  // Added - 2009-03-31
    'ProfileProbe'         => 'Détection de flux',           // Added - 2015-04-18
    'ProfileProbeIntro'    => 'La liste ci-dessous montre les profils de flux existants pour la caméra sélectionnée.<br/><br/>Sélectionnez le profil désiré dans la liste ci-dessous.<br/><br/>Veuillez noter que ZoneMinder ne peut pas configurer de profils additionels et que la sauvegarde entraînera l\'écrasement des paramètres déjà configurés pour la caméra en cours.<br/><br/>', // Added - 2015-04-18
    'Progress'             => 'Progression',               // Added - 2015-04-18
    'Protocol'             => 'Protocole',
    'RTSPDescribe'         => 'Use RTSP Response Media URL', // Added - 2018-08-30
    'RTSPTransport'        => 'RTSP Transport Protocol', // Added - 2018-08-30
    'Rate'                 => 'Vitesse',
    'Real'                 => 'Réel',
    'RecaptchaWarning'     => 'Your reCaptcha secret key is invalid. Please correct it, or reCaptcha will not work', // Added - 2018-08-30
    'Record'               => 'Enregistrer',
    'RecordAudio'          => 'Whether to store the audio stream when saving an event.', // Added - 2018-08-30
    'RefImageBlendPct'     => '% fusion image référence',
    'Refresh'              => 'Rafraîchir',
    'Remote'               => 'Distant',
    'RemoteHostName'       => 'Nom d\'hôte',
    'RemoteHostPath'       => 'Chemin',
    'RemoteHostPort'       => 'Port',
    'RemoteHostSubPath'    => 'Sous-chemin',    // Added - 2009-02-08
    'RemoteImageColours'   => 'Nombre de couleurs',
    'RemoteMethod'         => 'Méthode',          // Added - 2009-02-08
    'RemoteProtocol'       => 'Protocole',        // Added - 2009-02-08
    'Rename'               => 'Renommer',
    'Replay'               => 'Relire',
    'ReplayAll'            => 'Tous les événements',
    'ReplayGapless'        => 'Rejouer sans blancs',
    'ReplaySingle'         => 'Rejouer seul',
    'ReportEventAudit'     => 'Audit Events Report',    // Added - 2018-08-30
    'Reset'                => 'RàZ',
    'ResetEventCounts'     => 'RàZ compteur évts',
    'Restart'              => 'Redémarrer',
    'Restarting'           => 'Redémarrage',
    'RestrictedCameraIds'  => 'N°',
    'RestrictedMonitors'   => 'Caméra(s) uniquement visible(s)',
    'ReturnDelay'          => 'Délai de retour',
    'ReturnLocation'       => 'Position de retour',
    'Rewind'               => 'Reculer',
    'RotateLeft'           => 'Rotation g.',
    'RotateRight'          => 'Rotation d.',
    'RunLocalUpdate'       => 'Veuillez éxecuter zmupdate.pl pour mettre à jour', // Added - 2011-05-25
    'RunMode'              => 'Mode de lancement',
    'RunState'             => 'Changer d\'état',
    'Running'              => 'En marche',
    'Save'                 => 'Sauvegarder',
    'SaveAs'               => 'Sauvegarder sous',
    'SaveFilter'           => 'Sauvegarder filtre',
    'SaveJPEGs'            => 'Save JPEGs',             // Added - 2018-08-30
    'Scale'                => 'Echelle',
    'Score'                => 'Score',
    'Secs'                 => 'Secs',
    'Sectionlength'        => 'Longueur section',
    'Select'               => 'Sélectionner',
    'SelectFormat'         => 'Sélectionner format',          // Added - 2011-06-17
    'SelectLog'            => 'Sélectionner journal',             // Added - 2011-06-17
    'SelectMonitors'       => 'Sélectionner caméras',
    'SelfIntersecting'     => 'Les bords du polygone ne doivent pas se croiser',
    'Set'                  => 'Définir',
    'SetNewBandwidth'      => 'Régler le débit',
    'SetPreset'            => 'Définir préréglage',
    'Settings'             => 'Réglages',
    'ShowFilterWindow'     => 'Filtres',
    'ShowTimeline'         => 'Afficher chronologie',
    'SignalCheckColour'    => 'Couleur vérif. signal',
    'SignalCheckPoints'    => 'Signal Check Points',    // Added - 2018-08-30
    'Size'                 => 'Taille',
    'SkinDescription'      => 'Remplacer le skin par défaut', // Added - 2011-01-30
    'Sleep'                => 'Veille',
    'SortAsc'              => 'Asc',
    'SortBy'               => 'Trier par',
    'SortDesc'             => 'Desc',
    'Source'               => 'Source',
    'SourceColours'        => 'Couleurs',         // Added - 2009-02-08
    'SourcePath'           => 'Chemin',            // Added - 2009-02-08
    'SourceType'           => 'Type de source',
    'Speed'                => 'Vitesse',
    'SpeedHigh'            => 'Rapide',
    'SpeedLow'             => 'Lent',
    'SpeedMedium'          => 'Moyen',
    'SpeedTurbo'           => 'Turbo',
    'Start'                => 'Démarrer',
    'State'                => 'Etat',
    'Stats'                => 'Stats',
    'Status'               => 'Statut',
    'StatusConnected'      => 'Capturing',              // Added - 2018-08-30
    'StatusNotRunning'     => 'Not Running',            // Added - 2018-08-30
    'StatusRunning'        => 'Not Capturing',          // Added - 2018-08-30
    'StatusUnknown'        => 'Unknown',                // Added - 2018-08-30
    'Step'                 => 'Pas',
    'StepBack'             => 'Reculer',
    'StepForward'          => 'Avancer',
    'StepLarge'            => 'Pas large',
    'StepMedium'           => 'Pas moyen',
    'StepNone'             => 'Pas nul',
    'StepSmall'            => 'Pas faible',
    'Stills'               => 'Photos',
    'Stop'                 => 'Arrêter',
    'Stopped'              => 'Arrêté',
    'StorageArea'          => 'Storage Area',           // Added - 2018-08-30
    'StorageScheme'        => 'Scheme',                 // Added - 2018-08-30
    'Stream'               => 'Flux',
    'StreamReplayBuffer'   => 'Nb d\'image(s) pour relecture',
    'Submit'               => 'Soumettre',
    'System'               => 'Système',
    'SystemLog'            => 'Journal système',             // Added - 2011-06-16
    'TargetColorspace'     => 'Espace de couleur cible',      // Added - 2015-04-18
    'Tele'                 => 'Télé',
    'Thumbnail'            => 'Miniature',
    'Tilt'                 => 'Incliner',
    'Time'                 => 'Heure',
    'TimeDelta'            => 'Temps',
    'TimeStamp'            => 'Horodatage',
    'Timeline'             => 'Chronologie',
    'TimelineTip1'         => 'Passez votre souris sur le graphique pour visualiser un aperçu de l\'image et les détails de l\'événement.',              // Added 2013.08.15.
    'TimelineTip2'         => 'Cliquez sur les sections colorées du graphique ou sur l\'image pour voir l\'événement.',              // Added 2013.08.15.
    'TimelineTip3'         => 'Cliquez sur le fond pour zoomer sur une plage de temps plus réduite autour de votre clic.',              // Added 2013.08.15.
    'TimelineTip4'         => 'Utilisez les contrôles ci-dessous pour faire un zoom arrière ou naviguer en arrière et avancer sur l\'intervalle de temps.',              // Added 2013.08.15.
    'Timestamp'            => 'Horodatage',
    'TimestampLabelFormat' => 'Format',
    'TimestampLabelSize'   => 'Taille de police',
    'TimestampLabelX'      => 'Coordonnée X',
    'TimestampLabelY'      => 'Coordonnée Y',
    'Today'                => 'Aujourd\'hui',
    'Tools'                => 'Outils',
    'Total'                => 'Total',                  // Added - 2011-06-16
    'TotalBrScore'         => 'Score<br/>total',
    'TrackDelay'           => 'Délai suivi',
    'TrackMotion'          => 'Suivre détection',
    'Triggers'             => 'Déclenchements',
    'TurboPanSpeed'        => 'Vitesse turbo',
    'TurboTiltSpeed'       => 'Vitesse turbo',
    'Type'                 => 'Type',
    'Unarchive'            => 'Désarchiver',
    'Undefined'            => 'Indéfini',              // Added - 2009-02-08
    'Units'                => 'Unité',
    'Unknown'              => 'Inconnu',
    'Update'               => 'Mettre à jour',
    'UpdateAvailable'      => 'Mise à jour dispo.',
    'UpdateNotNecessary'   => 'Pas de mise à jour dispo.',
    'Updated'              => 'Mis à jour',                // Added - 2011-06-16
    'Upload'               => 'Transférer',                 // Added - 2011-08-23
    'UseFilter'            => 'Utiliser filtre',
    'UseFilterExprsPost'   => '&nbsp;filtre&nbsp;expressions', // This is used at the end of the phrase 'use N filter expressions'
    'UseFilterExprsPre'    => 'Utiliser&nbsp;', // This is used at the beginning of the phrase 'use N filter expressions'
    'UsedPlugins'	   => 'Filtres utilisés',
    'User'                 => 'Utilisateur',
    'Username'             => 'Nom',
    'Users'                => 'Utilisateurs',
    'V4L'                  => 'V4L',
    'V4LCapturesPerFrame'  => 'Nb captures par image',
    'V4LMultiBuffer'       => 'Mise en tampon multiple',
    'Value'                => 'Valeur',
    'Version'              => 'Version',
    'VersionIgnore'        => 'Ignorer cette version',
    'VersionRemindDay'     => 'Me rappeler dans 1 jour',
    'VersionRemindHour'    => 'Me rappeler dans 1 heure',
    'VersionRemindNever'   => 'Ne pas avertir des nvelles versions',
    'VersionRemindWeek'    => 'Me rappeler dans 1 sem.',
    'Video'                => 'Vidéo',
    'VideoFormat'          => 'Format de la vidéo',
    'VideoGenFailed'       => 'Echec génération vidéo !',
    'VideoGenFiles'        => 'Fichiers vidéo existants',
    'VideoGenNoFiles'      => 'Aucun fichier vidéo trouvé',
    'VideoGenParms'        => 'Paramètres génération vidéo',
    'VideoGenSucceeded'    => 'Vidéo générée avec succès !',
    'VideoSize'            => 'Taille vidéo',
    'VideoWriter'          => 'Video Writer',           // Added - 2018-08-30
    'View'                 => 'Voir',
    'ViewAll'              => 'Tout voir',
    'ViewEvent'            => 'Voir événement',
    'ViewPaged'            => 'Vue paginée',
    'Wake'                 => 'Réveiller',
    'WarmupFrames'         => 'Nb d\'image(s) tests',
    'Watch'                => 'Regarder',
    'Web'                  => 'Web',
    'WebColour'            => 'Couleur web',
    'WebSiteUrl'           => 'Website URL',            // Added - 2018-08-30
    'Week'                 => 'Semaine',
    'White'                => 'Blanc',
    'WhiteBalance'         => 'Balance des blancs',
    'Wide'                 => 'Large',
    'X'                    => 'X',
    'X10'                  => 'X10',
    'X10ActivationString'  => 'X10:chaîne activation',
    'X10InputAlarmString'  => 'X10:chaîne alarme entrée',
    'X10OutputAlarmString' => 'X10:chaîne alarme sortie',
    'Y'                    => 'Y',
    'Yes'                  => 'Oui',
    'YouNoPerms'           => 'Permissions nécessaires pour cette ressource.',
    'Zone'                 => 'Zone',
    'ZoneAlarmColour'      => 'Couleur alarme (Rouge/Vert/Bleu)',
    'ZoneArea'             => 'Surface de la zone',
    'ZoneExtendAlarmFrames' => 'Nb image(s) pour extension alarme',
    'ZoneFilterSize'       => 'Largeur/hauteur surface filtrée (nb pixels)',
    'ZoneMinMaxAlarmArea'  => 'Surface en alarme min/max (nb pixels)',
    'ZoneMinMaxBlobArea'   => 'Surface des formes min/max (nb pixels)',
    'ZoneMinMaxBlobs'      => 'Nombre de formes min/max',
    'ZoneMinMaxFiltArea'   => 'Surface filtrée min/max (nb pixels)',
    'ZoneMinMaxPixelThres' => 'Seuil pixels min/max (0-255)',
    'ZoneMinderLog'        => 'Journal de ZoneMinder',         // Added - 2011-06-17
    'ZoneOverloadFrames'   => 'Nb image(s) ignorée(s) après dépass. seuil',
    'Zones'                => 'Zones',
    'Zoom'                 => 'Zoom',
    'ZoomIn'               => 'Zoom avant',
    'ZoomOut'              => 'Zoom arrière',
);

// Complex replacements with formatting and/or placements, must be passed through sprintf
$CLANG = array(
    'CurrentLogin'         => 'Utilisateur actuel: \'%1$s\'',
    'EventCount'           => '%1$s %2$s', // par ex. '37 évènts' (voir Vlang ci-dessous)
    'LastEvents'           => '%1$s derniers %2$s', // par ex. '37 derniers  évènts' (voir Vlang ci-dessous)
    'LatestRelease'        => 'La dernière version est v%1$s, vous avez v%2$s.',
    'MonitorCount'         => '%1$s %2$s', // par exemple '4 caméras' (voir Vlang ci-dessous)
    'MonitorFunction'      => 'Caméra %1$s Fonction',
    'RunningRecentVer'     => 'Vous avez la dernière version de ZoneMinder, v%s.',
    'VersionMismatch'      => 'Discordance entre version système (%1$s) et base de données (%2$s).', // Added - 2011-05-25
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
    'Event'                => array( 0=>'événements', 1=>'événement', 2=>'événements' ),
    'Monitor'              => array( 0=>'caméras', 1=>'caméra', 2=>'caméras' ),
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
    die( 'Erreur, impossible de corréler la chaîne de caractères' );
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
	
    'LANG_DEFAULT' => array(
        'Prompt' => "Langage par défaut pour l'interface web",
        'Help' => "ZoneMinder est exploitable dans votre langue si le fichier de traduction approprié est disponible sur votre système. Cette option permet de changer la langue anglaise par défaut par la langue de votre choix dans la liste."
    ),
);

?>
