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
$SLANG = array(
    '24BitColour'          => 'Couleur 24 bit',
    '8BitGrey'             => 'Gris 8 bit',
    'Action'               => 'Action',
    'Actual'               => 'Réel',
    'AddNewControl'        => 'Add New Control',
    'AddNewMonitor'        => 'Aj. nouv. écran',
    'AddNewUser'           => 'Aj. nouv. util.',
    'AddNewZone'           => 'Aj. nouv. zone',
    'Alarm'                => 'Alarme',
    'AlarmBrFrames'        => 'Images<br/>alarme',
    'AlarmFrameCount'      => 'Alarm Frame Count',
    'AlarmFrame'           => 'Image alarme',
    'AlarmLimits'          => 'Limites alarme',
    'AlarmMaximumFPS'      => 'Alarm Maximum FPS',
    'AlarmPx'              => 'Px Alarme',
    'AlarmRGBUnset'        => 'You must set an alarm RGB colour',
    'Alert'                => 'Alerte',
    'All'                  => 'Tous',
    'Apply'                => 'Appliquer',
    'ApplyingStateChange'  => 'Appl. chgt état',
    'ArchArchived'         => 'Archivé seul.',
    'Archive'              => 'Archiver',
    'Archived'             => 'Archived',
    'ArchUnarchived'       => 'Non-arch. seul.',
    'Area'                 => 'Area',
    'AreaUnits'            => 'Area (px/%)',
    'AttrAlarmFrames'      => 'Images alarme',
    'AttrArchiveStatus'    => 'Etat Archive',
    'AttrAvgScore'         => 'Score moy.',
    'AttrCause'            => 'Cause',
    'AttrDate'             => 'Date',
    'AttrDateTime'         => 'Date/temps',
    'AttrDiskBlocks'       => 'Disk Blocks',
    'AttrDiskPercent'      => 'Disk Percent',
    'AttrDuration'         => 'Durée',
    'AttrFrames'           => 'Images',
    'AttrId'               => 'Id',
    'AttrMaxScore'         => 'Score max.',
    'AttrMonitorId'        => 'N° écran',
    'AttrMonitorName'      => 'Nom écran',
    'AttrName'             => 'Name',
    'AttrNotes'            => 'Notes',
    'AttrSystemLoad'       => 'System Load',
    'AttrTime'             => 'Temps',
    'AttrTotalScore'       => 'Score total',
    'AttrWeekday'          => 'Semaine',
    'Auto'                 => 'Auto',
    'AutoStopTimeout'      => 'Auto Stop Timeout',
    'AvgBrScore'           => 'Score<br/>moy.',
    'Background'           => 'Background',
    'BackgroundFilter'     => 'Run filter in background',
    'BadAlarmFrameCount'   => 'Alarm frame count must be an integer of one or more',
    'BadAlarmMaxFPS'       => 'Alarm Maximum FPS must be a positive integer or floating point value',
    'BadChannel'           => 'Channel must be set to an integer of zero or more',
    'BadDevice'            => 'Device must be set to a valid value',
    'BadFormat'            => 'Format must be set to an integer of zero or more',
    'BadFPSReportInterval' => 'FPS report interval buffer count must be an integer of 100 or more',
    'BadFrameSkip'         => 'Frame skip count must be an integer of zero or more',
    'BadHeight'            => 'Height must be set to a valid value',
    'BadHost'              => 'Host must be set to a valid ip address or hostname, do not include http://',
    'BadImageBufferCount'  => 'Image buffer size must be an integer of 10 or more',
    'BadLabelX'            => 'Label X co-ordinate must be set to an integer of zero or more',
    'BadLabelY'            => 'Label Y co-ordinate must be set to an integer of zero or more',
    'BadMaxFPS'            => 'Maximum FPS must be a positive integer or floating point value',
    'BadNameChars'         => 'Les noms ne peuvent contenir que des lettres, chiffres, trait d\'union ou souligné',
    'BadPath'              => 'Path must be set to a valid value',
    'BadPort'              => 'Port must be set to a valid number',
    'BadPostEventCount'    => 'Post event image count must be an integer of zero or more',
    'BadPreEventCount'     => 'Pre event image count must be at least zero, and less than image buffer size',
    'BadRefBlendPerc'      => 'Reference blend percentage must be a positive integer',
    'BadSectionLength'     => 'Section length must be an integer of 30 or more',
    'BadSignalCheckColour' => 'Signal check colour must be a valid RGB colour string',
    'BadStreamReplayBuffer'=> 'Stream replay buffer must be an integer of zero or more',
    'BadWarmupCount'       => 'Warmup frames must be an integer of zero or more',
    'BadWebColour'         => 'Web colour must be a valid web colour string',
    'BadWidth'             => 'Width must be set to a valid value',
    'Bandwidth'            => 'Bande-pass.',
    'BlobPx'               => 'Px forme',
    'Blobs'                => 'Formes',
    'BlobSizes'            => 'Taille forme',
    'Brightness'           => 'Luminosité;',
    'Buffers'              => 'Tampons',
    'CanAutoFocus'         => 'Can Auto Focus',
    'CanAutoGain'          => 'Can Auto Gain',
    'CanAutoIris'          => 'Can Auto Iris',
    'CanAutoWhite'         => 'Can Auto White Bal.',
    'CanAutoZoom'          => 'Can Auto Zoom',
    'Cancel'               => 'Annul.',
    'CancelForcedAlarm'    => 'Annul. Forc&eacute; Alarme',
    'CanFocusAbs'          => 'Can Focus Absolute',
    'CanFocus'             => 'Can Focus',
    'CanFocusCon'          => 'Can Focus Continuous',
    'CanFocusRel'          => 'Can Focus Relative',
    'CanGainAbs'           => 'Can Gain Absolute',
    'CanGain'              => 'Can Gain ',
    'CanGainCon'           => 'Can Gain Continuous',
    'CanGainRel'           => 'Can Gain Relative',
    'CanIrisAbs'           => 'Can Iris Absolute',
    'CanIris'              => 'Can Iris',
    'CanIrisCon'           => 'Can Iris Continuous',
    'CanIrisRel'           => 'Can Iris Relative',
    'CanMoveAbs'           => 'Can Move Absolute',
    'CanMove'              => 'Can Move',
    'CanMoveCon'           => 'Can Move Continuous',
    'CanMoveDiag'          => 'Can Move Diagonally',
    'CanMoveMap'           => 'Can Move Mapped',
    'CanMoveRel'           => 'Can Move Relative',
    'CanPan'               => 'Can Pan' ,
    'CanReset'             => 'Can Reset',
    'CanSetPresets'        => 'Can Set Presets',
    'CanSleep'             => 'Can Sleep',
    'CanTilt'              => 'Can Tilt',
    'CanWake'              => 'Can Wake',
    'CanWhiteAbs'          => 'Can White Bal. Absolute',
    'CanWhiteBal'          => 'Can White Bal.',
    'CanWhite'             => 'Can White Balance',
    'CanWhiteCon'          => 'Can White Bal. Continuous',
    'CanWhiteRel'          => 'Can White Bal. Relative',
    'CanZoomAbs'           => 'Can Zoom Absolute',
    'CanZoom'              => 'Can Zoom',
    'CanZoomCon'           => 'Can Zoom Continuous',
    'CanZoomRel'           => 'Can Zoom Relative',
    'CaptureHeight'        => 'Haut. capture',
    'CapturePalette'       => 'palette capture',
    'CaptureWidth'         => 'Larg. capture',
    'Cause'                => 'Cause',
    'CheckMethod'          => 'Méthode vérif. alarme',
    'ChooseFilter'         => 'Choisir filtre',
    'ChoosePreset'         => 'Choose Preset',
    'Close'                => 'Fermer',
    'Colour'               => 'Couleur',
    'Command'              => 'Command',
    'Config'               => 'Config',
    'ConfiguredFor'        => 'Configuré pour',
    'ConfirmDeleteEvents'  => 'Are you sure you wish to delete the selected events?',
    'ConfirmPassword'      => 'Confirmer mt de pass.',
    'ConjAnd'              => 'et',
    'ConjOr'               => 'ou',
    'Console'              => 'Console',
    'ContactAdmin'         => 'Contactez votre administrateur SVP',
    'Continue'             => 'Continue',
    'Contrast'             => 'Contraste',
    'ControlAddress'       => 'Control Address',
    'ControlCap'           => 'Control Capability',
    'ControlCaps'          => 'Control Capabilities',
    'Control'              => 'Control',
    'ControlDevice'        => 'Control Device',
    'Controllable'         => 'Controllable',
    'ControlType'          => 'Control Type',
    'Cycle'                => 'Cycle',
    'CycleWatch'           => 'Cycle vision',
    'Day'                  => 'Jour',
    'Debug'                => 'Debug',
    'DefaultRate'          => 'Default Rate',
    'DefaultScale'         => 'Default Scale',
    'DefaultView'          => 'Default View',
    'DeleteAndNext'        => 'Eff. &amp; suiv.',
    'DeleteAndPrev'        => 'Eff. &amp; prec.',
    'Delete'               => 'Eff.',
    'DeleteSavedFilter'    => 'Eff. filtre sauvé',
    'Description'          => 'Description',
    'DeviceChannel'        => 'Canal caméra',
    'DeviceFormat'         => 'Format caméra',
    'DeviceNumber'         => 'Numéro caméra',
    'DevicePath'           => 'Device Path',
    'Devices'              => 'Devices',
    'Dimensions'           => 'Dimensions',
    'DisableAlarms'        => 'Disable Alarms',
    'Disk'                 => 'Disk',
    'DonateAlready'        => 'No, I\'ve already donated',
    'DonateEnticement'     => 'You\'ve been running ZoneMinder for a while now and hopefully are finding it a useful addition to your home or workplace security. Although ZoneMinder is, and will remain, free and open source, it costs money to develop and support. If you would like to help support future development and new features then please consider donating. Donating is, of course, optional but very much appreciated and you can donate as much or as little as you like.<br><br>If you would like to donate please select the option below or go to http://www.zoneminder.com/donate.html in your browser.<br><br>Thank you for using ZoneMinder and don\'t forget to visit the forums on ZoneMinder.com for support or suggestions about how to make your ZoneMinder experience even better.',
    'Donate'               => 'Please Donate',
    'DonateRemindDay'      => 'Not yet, remind again in 1 day',
    'DonateRemindHour'     => 'Not yet, remind again in 1 hour',
    'DonateRemindMonth'    => 'Not yet, remind again in 1 month',
    'DonateRemindNever'    => 'No, I don\'t want to donate, never remind',
    'DonateRemindWeek'     => 'Not yet, remind again in 1 week',
    'DonateYes'            => 'Yes, I\'d like to donate now',
    'Download'             => 'Download',
    'Duration'             => 'Durée',
    'Edit'                 => 'Editer',
    'Email'                => 'Courriel',
    'EnableAlarms'         => 'Enable Alarms',
    'Enabled'              => 'Activé',
    'EnterNewFilterName'   => 'Entrer nom nouv. filtre',
    'ErrorBrackets'        => 'Erreur, vérifiez que toutes les parenthèses ouvertes sont fermées',
    'Error'                => 'Erreur',
    'ErrorValidValue'      => 'Erreur, vérifiez que tous les termes ont une valeur valide',
    'Etc'                  => 'etc',
    'Event'                => 'Evènt',
    'EventFilter'          => 'Filtre evènt',
    'EventId'              => 'Event Id',
    'EventName'            => 'Event Name',
    'EventPrefix'          => 'Event Prefix',
    'Events'               => 'Evènts',
    'Exclude'              => 'Exclure',
    'Execute'              => 'Execute',
    'ExportDetails'        => 'Export Event Details',
    'Export'               => 'Export',
    'ExportFailed'         => 'Export Failed',
    'ExportFormat'         => 'Export File Format',
    'ExportFormatTar'      => 'Tar',
    'ExportFormatZip'      => 'Zip',
    'ExportFrames'         => 'Export Frame Details',
    'ExportImageFiles'     => 'Export Image Files',
    'Exporting'            => 'Exporting',
    'ExportMiscFiles'      => 'Export Other Files (if present)',
    'ExportOptions'        => 'Export Options',
    'ExportVideoFiles'     => 'Export Video Files (if present)',
    'Far'                  => 'Far',
    'FastForward'          => 'Fast Forward',
    'Feed'                 => 'Feed',
    'FileColours'          => 'File Colours',
    'File'                 => 'File',
    'FilePath'             => 'File Path',
    'FilterArchiveEvents'  => 'Archive all matches',
    'FilterDeleteEvents'   => 'Delete all matches',
    'FilterEmailEvents'    => 'Email details of all matches',
    'FilterExecuteEvents'  => 'Execute command on all matches',
    'FilterMessageEvents'  => 'Message details of all matches',
    'FilterPx'             => 'Filter Px',
    'Filters'              => 'Filters',
    'FilterUnset'          => 'You must specify a filter width and height',
    'FilterUploadEvents'   => 'Upload all matches',
    'FilterVideoEvents'    => 'Create video for all matches',
    'First'                => 'Prem.',
    'FlippedHori'          => 'Flipped Horizontally',
    'FlippedVert'          => 'Flipped Vertically',
    'Focus'                => 'Focus',
    'ForceAlarm'           => 'Force Alarme',
    'Format'               => 'Format',
    'FPS'                  => 'i/s',
    'FPSReportInterval'    => 'FPS Report Interval',
    'FrameId'              => 'N° image',
    'Frame'                => 'Image',
    'FrameRate'            => 'Débit image',
    'Frames'               => 'images',
    'FrameSkip'            => 'Saut image',
    'FTP'                  => 'FTP',
    'Func'                 => 'Fct',
    'Function'             => 'Fonction',
    'Gain'                 => 'Gain',
    'General'              => 'General',
    'GenerateVideo'        => 'Générer Vidéo',
    'GeneratingVideo'      => 'Génération Vidéo',
    'GoToZoneMinder'       => 'Aller sur ZoneMinder.com',
    'Grey'                 => 'Gris',
    'Group'                => 'Group',
    'Groups'               => 'Groups',
    'HasFocusSpeed'        => 'Has Focus Speed',
    'HasGainSpeed'         => 'Has Gain Speed',
    'HasHomePreset'        => 'Has Home Preset',
    'HasIrisSpeed'         => 'Has Iris Speed',
    'HasPanSpeed'          => 'Has Pan Speed',
    'HasPresets'           => 'Has Presets',
    'HasTiltSpeed'         => 'Has Tilt Speed',
    'HasTurboPan'          => 'Has Turbo Pan',
    'HasTurboTilt'         => 'Has Turbo Tilt',
    'HasWhiteSpeed'        => 'Has White Bal. Speed',
    'HasZoomSpeed'         => 'Has Zoom Speed',
    'HighBW'               => 'Haut&nbsp;N/B',
    'High'                 => 'Haut',
    'Home'                 => 'Home',
    'Hour'                 => 'Heure',
    'Hue'                  => 'Teinte',
    'Idle'                 => 'Vide',
    'Id'                   => 'N°',
    'Ignore'               => 'Ignorer',
    'ImageBufferSize'      => 'Taille tampon image',
    'Image'                => 'Image',
    'Images'               => 'Images',
    'Include'              => 'Inclure',
    'In'                   => 'In',
    'Inverted'             => 'Inversé',
    'Iris'                 => 'Iris',
    'KeyString'            => 'Key String',
    'Label'                => 'Label',
    'Language'             => 'Langue',
    'Last'                 => 'Dernier',
    'LimitResultsPost'     => 'results only;', // This is used at the end of the phrase 'Limit to first N results only'
    'LimitResultsPre'      => 'Limit to first', // This is used at the beginning of the phrase 'Limit to first N results only'
    'LinkedMonitors'       => 'Linked Monitors',
    'List'                 => 'List',
    'Load'                 => 'Load',
    'Local'                => 'Local',
    'LoggedInAs'           => 'Connecté cô',
    'LoggingIn'            => 'Connexion',
    'Login'                => 'Login',
    'Logout'               => 'Déconnexion',
    'Low'                  => 'Bas',
    'LowBW'                => 'Basse&nbsp;N/B',
    'Main'                 => 'Main',
    'Man'                  => 'Man',
    'Manual'               => 'Manual',
    'Mark'                 => 'Marque',
    'MaxBandwidth'         => 'Max Bandwidth',
    'MaxBrScore'           => 'Score<br/>max',
    'MaxFocusRange'        => 'Max Focus Range',
    'MaxFocusSpeed'        => 'Max Focus Speed',
    'MaxFocusStep'         => 'Max Focus Step',
    'MaxGainRange'         => 'Max Gain Range',
    'MaxGainSpeed'         => 'Max Gain Speed',
    'MaxGainStep'          => 'Max Gain Step',
    'MaximumFPS'           => 'i/s maximum',
    'MaxIrisRange'         => 'Max Iris Range',
    'MaxIrisSpeed'         => 'Max Iris Speed',
    'MaxIrisStep'          => 'Max Iris Step',
    'Max'                  => 'Max',
    'MaxPanRange'          => 'Max Pan Range',
    'MaxPanSpeed'          => 'Max Pan Speed',
    'MaxPanStep'           => 'Max Pan Step',
    'MaxTiltRange'         => 'Max Tilt Range',
    'MaxTiltSpeed'         => 'Max Tilt Speed',
    'MaxTiltStep'          => 'Max Tilt Step',
    'MaxWhiteRange'        => 'Max White Bal. Range',
    'MaxWhiteSpeed'        => 'Max White Bal. Speed',
    'MaxWhiteStep'         => 'Max White Bal. Step',
    'MaxZoomRange'         => 'Max Zoom Range',
    'MaxZoomSpeed'         => 'Max Zoom Speed',
    'MaxZoomStep'          => 'Max Zoom Step',
    'MediumBW'             => 'Moy.&nbsp;N/B',
    'Medium'               => 'Medium',
    'MinAlarmAreaLtMax'    => 'Minimum alarm area should be less than maximum',
    'MinAlarmAreaUnset'    => 'You must specify the minimum alarm pixel count',
    'MinBlobAreaLtMax'     => 'Aire blob min. doit ê < aire blob maximum',
    'MinBlobAreaUnset'     => 'You must specify the minimum blob pixel count',
    'MinBlobLtMinFilter'   => 'Minimum blob area should be less than or equal to minimum filter area',
    'MinBlobsLtMax'        => 'Blobs min. doit ê < blobs max.',
    'MinBlobsUnset'        => 'You must specify the minimum blob count',
    'MinFilterAreaLtMax'   => 'Minimum filter area should be less than maximum',
    'MinFilterAreaUnset'   => 'You must specify the minimum filter pixel count',
    'MinFilterLtMinAlarm'  => 'Minimum filter area should be less than or equal to minimum alarm area',
    'MinFocusRange'        => 'Min Focus Range',
    'MinFocusSpeed'        => 'Min Focus Speed',
    'MinFocusStep'         => 'Min Focus Step',
    'MinGainRange'         => 'Min Gain Range',
    'MinGainSpeed'         => 'Min Gain Speed',
    'MinGainStep'          => 'Min Gain Step',
    'MinIrisRange'         => 'Min Iris Range',
    'MinIrisSpeed'         => 'Min Iris Speed',
    'MinIrisStep'          => 'Min Iris Step',
    'MinPanRange'          => 'Min Pan Range',
    'MinPanSpeed'          => 'Min Pan Speed',
    'MinPanStep'           => 'Min Pan Step',
    'MinPixelThresLtMax'   => 'Seuil pixel min. doit ê < seuil pixel max.',
    'MinPixelThresUnset'   => 'You must specify a minimum pixel threshold',
    'MinTiltRange'         => 'Min Tilt Range',
    'MinTiltSpeed'         => 'Min Tilt Speed',
    'MinTiltStep'          => 'Min Tilt Step',
    'MinWhiteRange'        => 'Min White Bal. Range',
    'MinWhiteSpeed'        => 'Min White Bal. Speed',
    'MinWhiteStep'         => 'Min White Bal. Step',
    'MinZoomRange'         => 'Min Zoom Range',
    'MinZoomSpeed'         => 'Min Zoom Speed',
    'MinZoomStep'          => 'Min Zoom Step',
    'Misc'                 => 'Div.',
    'Monitor'              => 'Ecran',
    'MonitorIds'           => 'N°&nbsp;écran',
    'MonitorPresetIntro'   => 'Select an appropriate preset from the list below.<br><br>Please note that this may overwrite any values you already have configured for this monitor.<br><br>',
    'MonitorPreset'        => 'Monitor Preset',
    'Monitors'             => 'Ecrans',
    'Montage'              => 'Montage',
    'Month'                => 'Mois',
    'Move'                 => 'Move',
    'MustBeGe'             => 'doit être sup. ou égal à',
    'MustBeLe'             => 'doit être inf. ou égal à',
    'MustConfirmPassword'  => 'Confirmez le mot de passe',
    'MustSupplyPassword'   => 'Entrez un mot de passe',
    'MustSupplyUsername'   => 'Entrez un nom d\'utilisateur',
    'Name'                 => 'Nom',
    'Near'                 => 'Near',
    'Network'              => 'Réseau',
    'NewGroup'             => 'New Group',
    'NewLabel'             => 'New Label',
    'New'                  => 'Nouv.',
    'NewPassword'          => 'Nouv. mt de passe',
    'NewState'             => 'Nv état',
    'NewUser'              => 'Nv util.',
    'Next'                 => 'Suiv.',
    'NoFramesRecorded'     => 'Pas d\'image enregistrée pour cet évènement',
    'NoGroup'              => 'No Group',
    'None'                 => 'Aucun',
    'NoneAvailable'        => 'Aucun disponible',
    'No'                   => 'Non',
    'Normal'               => 'Normal',
    'NoSavedFilters'       => 'Pasfiltressauv',
    'NoStatisticsRecorded' => 'Pas de statistiques disponibles pour cet évènmnt/imag.',
    'Notes'                => 'Notes',
    'NumPresets'           => 'Num Presets',
    'Off'                  => 'Off',
    'On'                   => 'On',
    'Open'                 => 'Open',
    'OpEq'                 => 'égal à',
    'OpGtEq'               => 'plus grand ou égal à',
    'OpGt'                 => 'sup. à',
    'OpIn'                 => 'en lot',
    'OpLtEq'               => 'inf. ou égal à',
    'OpLt'                 => 'inf. à',
    'OpMatches'            => 'correspond',
    'OpNe'                 => 'diff. de',
    'OpNotIn'              => 'pas en lot',
    'OpNotMatches'         => 'ne correspond pas',
    'OptionHelp'           => 'OptionAide',
    'OptionRestartWarning' => 'These changes may not come into effect fully\nwhile the system is running. When you have\nfinished making your changes please ensure that\nyou restart ZoneMinder.',
    'Options'              => 'Options',
    'Order'                => 'Order',
    'OrEnterNewName'       => 'ou entrez nv nom',
    'Orientation'          => 'Orientation',
    'Out'                  => 'Out',
    'OverwriteExisting'    => 'Ecraser l\'existant',
    'Paged'                => 'Paged',
    'PanLeft'              => 'Pan Left',
    'Pan'                  => 'Pan',
    'PanRight'             => 'Pan Right',
    'PanTilt'              => 'Pan/Tilt',
    'Parameter'            => 'Paramètre',
    'Password'             => 'Mt de passe',
    'PasswordsDifferent'   => 'Les 2 mots de passe sont différents',
    'Paths'                => 'Paths',
    'Pause'                => 'Pause',
    'PhoneBW'              => 'Phone&nbsp;B/W',
    'Phone'                => 'Phone',
    'PixelDiff'            => 'Pixel Diff',
    'Pixels'               => 'pixels',
    'PlayAll'              => 'Play All',
    'Play'                 => 'Play',
    'PleaseWait'           => 'Attendez',
    'Point'                => 'Point',
    'PostEventImageBuffer' => 'Post Event Image Count',
    'PreEventImageBuffer'  => 'Pre Event Image Count',
    'PreserveAspect'       => 'Preserve Aspect Ratio',
    'Preset'               => 'Preset',
    'Presets'              => 'Presets',
    'Prev'                 => 'Prec.',
    'Protocol'             => 'Protocol',
    'Rate'                 => 'Débit',
    'Real'                 => 'Réel',
    'Record'               => 'Enreg.',
    'RefImageBlendPct'     => 'Reference Image Blend %ge',
    'Refresh'              => 'Rafraîchir',
    'RemoteHostName'       => 'Remote Host Name',
    'RemoteHostPath'       => 'Remote Host Path',
    'RemoteHostPort'       => 'Remote Host Port',
    'RemoteImageColours'   => 'Remote Image Colours',
    'Remote'               => 'Remote',
    'Rename'               => 'Renommer',
    'ReplayAll'            => 'All Events',
    'ReplayGapless'        => 'Gapless Events',
    'Replay'               => 'Ralenti',
    'Replay'               => 'Replay',
    'ReplaySingle'         => 'Single Event',
    'ResetEventCounts'     => 'Rem. à 0 comptage des évts',
    'Reset'                => 'Reset',
    'Restarting'           => 'Redémarrage',
    'Restart'              => 'Redémarrer',
    'RestrictedCameraIds'  => 'N° caméras confid.',
    'RestrictedMonitors'   => 'Restricted Monitors',
    'ReturnDelay'          => 'Return Delay',
    'ReturnLocation'       => 'Return Location',
    'Rewind'               => 'Rewind',
    'RotateLeft'           => 'Rotation g.',
    'RotateRight'          => 'Rotation d.',
    'RunMode'              => 'Run Mode',
    'Running'              => 'Ca tourne',
    'RunState'             => 'Run State',
    'SaveAs'               => 'Enr. ss',
    'Save'                 => 'Enr.',
    'SaveFilter'           => 'Save Filter',
    'Scale'                => 'Echelle',
    'Score'                => 'Score',
    'Secs'                 => 'Secs',
    'Sectionlength'        => 'Longueur section',
    'SelectMonitors'       => 'Select Monitors',
    'Select'               => 'Select',
    'SelfIntersecting'     => 'Polygon edges must not intersect',
    'SetNewBandwidth'      => 'Régler la bande passante',
    'SetPreset'            => 'Set Preset',
    'Set'                  => 'Set',
    'Settings'             => 'Réglages',
    'ShowFilterWindow'     => 'Montrerfen.filtre',
    'ShowTimeline'         => 'Show Timeline',
    'SignalCheckColour'    => 'Signal Check Colour',
    'Size'                 => 'Size',
    'Sleep'                => 'Sleep',
    'SortAsc'              => 'Asc',
    'SortBy'               => 'Sort by',
    'SortDesc'             => 'Desc',
    'Source'               => 'Source',
    'SourceType'           => 'Source Type',
    'SpeedHigh'            => 'High Speed',
    'SpeedLow'             => 'Low Speed',
    'SpeedMedium'          => 'Medium Speed',
    'Speed'                => 'Speed',
    'SpeedTurbo'           => 'Turbo Speed',
    'Start'                => 'Démarrer',
    'State'                => 'Etat',
    'Stats'                => 'Stats',
    'Status'               => 'Statut',
    'StepBack'             => 'Step Back',
    'StepForward'          => 'Step Forward',
    'StepLarge'            => 'Large Step',
    'StepMedium'           => 'Medium Step',
    'StepNone'             => 'No Step',
    'StepSmall'            => 'Small Step',
    'Step'                 => 'Step',
    'Stills'               => 'Photos',
    'Stopped'              => 'Arrêté',
    'Stop'                 => 'Stop',
    'Stream'               => 'Flux',
    'StreamReplayBuffer'   => 'Stream Replay Image Buffer',
    'Submit'               => 'Submit',
    'System'               => 'Système',
    'Tele'                 => 'Tele',
    'Thumbnail'            => 'Thumbnail',
    'Tilt'                 => 'Tilt',
    'TimeDelta'            => 'Time Delta',
    'Timeline'             => 'Timeline',
    'TimestampLabelFormat' => 'Timestamp Label Format',
    'TimestampLabelX'      => 'Timestamp Label X',
    'TimestampLabelY'      => 'Timestamp Label Y',
    'Timestamp'            => 'Timestamp',
    'TimeStamp'            => 'Time Stamp',
    'Time'                 => 'Temps',
    'Today'                => 'Today',
    'Tools'                => 'Outils',
    'TotalBrScore'         => 'Score<br/>total',
    'TrackDelay'           => 'Track Delay',
    'TrackMotion'          => 'Track Motion',
    'Triggers'             => 'Déclenchements',
    'TurboPanSpeed'        => 'Turbo Pan Speed',
    'TurboTiltSpeed'       => 'Turbo Tilt Speed',
    'Type'                 => 'Type',
    'Unarchive'            => 'Désarchiv.',
    'Units'                => 'Unités',
    'Unknown'              => 'Inconnu',
    'UpdateAvailable'      => 'Mise à jour de ZM dispo.',
    'UpdateNotNecessary'   => 'Pas de mise à jour dispo.',
    'Update'               => 'Update',
    'UseFilterExprsPost'   => '&nbsp;filter&nbsp;expressions', // This is used at the end of the phrase 'use N filter expressions'
    'UseFilterExprsPre'    => 'Util.&nbsp;', // This is used at the beginning of the phrase 'use N filter expressions'
    'UseFilter'            => 'Util. Filtre',
    'Username'             => 'nom util.',
    'Users'                => 'Utils',
    'User'                 => 'Util.',
    'Value'                => 'Valeur',
    'VersionIgnore'        => 'Ignorer cette version',
    'VersionRemindDay'     => 'Me rappeler ds 1 j.',
    'VersionRemindHour'    => 'Me rappleler dans 1 h.',
    'VersionRemindNever'   => 'Ne pas avertir des nvelles versions',
    'VersionRemindWeek'    => 'Me rappeler ds 1 sem.',
    'Version'              => 'Version',
    'VideoFormat'          => 'Video Format',
    'VideoGenFailed'       => 'Echec génération vidéo!',
    'VideoGenFiles'        => 'Existing Video Files',
    'VideoGenNoFiles'      => 'No Video Files Found',
    'VideoGenParms'        => 'Paramètres génération vidéo',
    'VideoGenSucceeded'    => 'Video Generation Succeeded!',
    'VideoSize'            => 'taille vidéo',
    'Video'                => 'Vidéo',
    'ViewAll'              => 'Voir tt',
    'ViewEvent'            => 'View Event',
    'ViewPaged'            => 'Vue recherchée',
    'View'                 => 'Voir',
    'Wake'                 => 'Wake',
    'WarmupFrames'         => 'Images test',
    'Watch'                => 'Regarder',
    'WebColour'            => 'Web Colour',
    'Web'                  => 'Web',
    'Week'                 => 'Semaine',
    'WhiteBalance'         => 'White Balance',
    'White'                => 'White',
    'Wide'                 => 'Wide',
    'X10ActivationString'  => 'X10:chaîne activation',
    'X10InputAlarmString'  => 'X10:chaîne alarme entrée',
    'X10OutputAlarmString' => 'X10:chaîne alarme sortie',
    'X10'                  => 'X10',
    'X'                    => 'X',
    'Yes'                  => 'Oui',
    'YouNoPerms'           => 'Permissions nécessaires pour cette ressource.',
    'Y'                    => 'Y',
    'ZoneAlarmColour'      => 'Couleur alarme (Red/Green/Blue)',
    'ZoneArea'             => 'Zone Area',
    'ZoneFilterSize'       => 'Filter Width/Height (pixels)',
    'ZoneMinMaxAlarmArea'  => 'Min/Max Alarmed Area',
    'ZoneMinMaxBlobArea'   => 'Min/Max Blob Area',
    'ZoneMinMaxBlobs'      => 'Min/Max Blobs',
    'ZoneMinMaxFiltArea'   => 'Min/Max Filtered Area',
    'ZoneMinMaxPixelThres' => 'Min/Max Pixel Threshold (0-255)',
    'ZoneOverloadFrames'   => 'Overload Frame Ignore Count',
    'Zones'                => 'Zones',
    'Zone'                 => 'Zone',
    'ZoomIn'               => 'Zoom In',
    'ZoomOut'              => 'Zoom Out',
    'Zoom'                 => 'Zoom',
);

// Complex replacements with formatting and/or placements, must be passed through sprintf
$CLANG = array(
    'CurrentLogin'         => 'Util. Actuel: \'%1$s\'',
    'EventCount'           => '%1$s %2$s', // par ex. '37 évènts' (voir Vlang ci-dessous)
    'LastEvents'           => '%1$s derniers %2$s', // par ex. '37 derniers  évènts' (voir Vlang ci-dessous)
    'LatestRelease'        => 'La dernière version est v%1$s, vous avez v%2$s.',
    'MonitorCount'         => '%1$s %2$s', // par exemple '4 écrans' (voir Vlang ci-dessous)
    'MonitorFunction'      => 'Ecran %1$s Fonction',
    'RunningRecentVer'     => 'Vs avez la dernière version de ZoneMinder, v%s.',
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
    'Event'                => array( 0=>'évènts', 1=>'évènt', 2=>'évènts' ),
    'Monitor'              => array( 0=>'écrans', 1=>'écran', 2=>'écrans' ),
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
    die( 'Error, unable to correlate variable language string' );
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
