<?php
//
// ZoneMinder web Dutch language file, $Date$, $Revision$
// Copyright (C) 2003, 2004, 2005, 2006  Philip Coombes
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

// ZoneMinder Dutch Translation by Koen Veen

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
    '24BitColour'          => '24 bit kleuren',
    '8BitGrey'             => '8 bit grijstinten',
    'Action'               => 'Action',
    'Actual'               => 'Aktueel',
    'AddNewControl'        => 'Add New Control',
    'AddNewMonitor'        => 'Voeg een nieuwe monitor toe',
    'AddNewUser'           => 'Voeg een nieuwe gebruiker toe',
    'AddNewZone'           => 'Voeg een nieuwe zone toe',
    'Alarm'                => 'Alarm',
    'AlarmBrFrames'        => 'Alarm<br/>Frames',
    'AlarmFrame'           => 'Alarm Frame',
    'AlarmFrameCount'      => 'Alarm Frame Count',
    'AlarmLimits'          => 'Alarm Limieten',
    'AlarmMaximumFPS'      => 'Alarm Maximum FPS',
    'AlarmPx'              => 'Alarm Px',
    'AlarmRGBUnset'        => 'You must set an alarm RGB colour',
    'Alert'                => 'Waarschuwing',
    'All'                  => 'Alle',
    'ApplyingStateChange'  => 'Status verandering aan het uitvoeren',
    'Apply'                => 'Voer uit',
    'ArchArchived'         => 'Alleen gearchiveerd',
    'Archive'              => 'Archief',
    'Archived'             => 'Archived',
    'ArchUnarchived'       => 'Alleen ongearchiveerd',
    'Area'                 => 'Area',
    'AreaUnits'            => 'Area (px/%)',
    'AttrAlarmFrames'      => 'Alarm frames',
    'AttrArchiveStatus'    => 'Archief status',
    'AttrAvgScore'         => 'Gem. score',
    'AttrCause'            => 'Cause',
    'AttrDate'             => 'Datum',
    'AttrDateTime'         => 'Datum/tijd',
    'AttrDiskBlocks'       => 'Disk Blocks',
    'AttrDiskPercent'      => 'Disk Percent',
    'AttrDuration'         => 'Duur',
    'AttrFrames'           => 'Frames',
    'AttrId'               => 'Id',
    'AttrMaxScore'         => 'Max. Score',
    'AttrMonitorId'        => 'Monitor Id',
    'AttrMonitorName'      => 'Monitor Naam',
    'AttrMontage'          => 'Montage',
    'AttrName'             => 'Name',
    'AttrNotes'            => 'Notes',
    'AttrSystemLoad'       => 'System Load',
    'AttrTime'             => 'Tijd',
    'AttrTotalScore'       => 'Totale Score',
    'AttrWeekday'          => 'Weekdag',
    'Auto'                 => 'Auto',
    'AutoStopTimeout'      => 'Auto Stop Timeout',
    'AvgBrScore'           => 'Gem.<br/>score',
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
    'BadNameChars'         => 'Namen mogen alleen alpha numerieke karakters bevatten plus hyphens en underscores',
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
    'Bandwidth'            => 'Bandbreedte',
    'BlobPx'               => 'Blob px',
    'Blobs'                => 'Blobs',
    'BlobSizes'            => 'Blob grootte',
    'Brightness'           => 'Helderheid',
    'Buffers'              => 'Buffers',
    'CanAutoFocus'         => 'Can Auto Focus',
    'CanAutoGain'          => 'Can Auto Gain',
    'CanAutoIris'          => 'Can Auto Iris',
    'CanAutoWhite'         => 'Can Auto White Bal.',
    'CanAutoZoom'          => 'Can Auto Zoom',
    'Cancel'               => 'Cancel',
    'CancelForcedAlarm'    => 'Cancel geforceerd alarm',
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
    'CaptureHeight'        => 'Capture hoogte',
    'CapturePalette'       => 'Capture pallet',
    'CaptureWidth'         => 'Capture breedte',
    'Cause'                => 'Cause',
    'CheckMethod'          => 'Alarm Check Methode',
    'ChooseFilter'         => 'Kies filter',
    'ChoosePreset'         => 'Choose Preset',
    'Close'                => 'Sluit',
    'Colour'               => 'Kleur',
    'Command'              => 'Command',
    'Config'               => 'Config',
    'ConfiguredFor'        => 'Geconfigureerd voor',
    'ConfirmDeleteEvents'  => 'Are you sure you wish to delete the selected events?',
    'ConfirmPassword'      => 'Bevestig wachtwoord',
    'ConjAnd'              => 'en',
    'ConjOr'               => 'of',
    'Console'              => 'Console',
    'ContactAdmin'         => 'Neem A.U.B. contact op met je beheerder voor details.',
    'Continue'             => 'Continue',
    'Contrast'             => 'Contrast',
    'ControlAddress'       => 'Control Address',
    'ControlCap'           => 'Control Capability',
    'ControlCaps'          => 'Control Capabilities',
    'Control'              => 'Control',
    'ControlDevice'        => 'Control Device',
    'Controllable'         => 'Controllable',
    'ControlType'          => 'Control Type',
    'Cycle'                => 'Cycle',
    'CycleWatch'           => 'Observeer cyclus',
    'Day'                  => 'Dag',
    'Debug'                => 'Debug',
    'DefaultRate'          => 'Default Rate',
    'DefaultScale'         => 'Default Scale',
    'DefaultView'          => 'Default View',
    'DeleteAndNext'        => 'verwijder &amp; volgende',
    'DeleteAndPrev'        => 'verwijder &amp; vorige',
    'DeleteSavedFilter'    => 'verwijder opgeslagen filter',
    'Delete'               => 'verwijder',
    'Description'          => 'Omschrijving',
    'DeviceChannel'        => 'Apparaat kanaal',
    'DeviceFormat'         => 'Apparaat formaat',
    'DeviceNumber'         => 'apparaat nummer',
    'DevicePath'           => 'Device Path',
    'Devices'              => 'Devices',
    'Dimensions'           => 'Afmetingen',
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
    'Duration'             => 'Duur',
    'Edit'                 => 'Bewerk',
    'Email'                => 'Email',
    'EnableAlarms'         => 'Enable Alarms',
    'Enabled'              => 'Uitgeschakeld',
    'EnterNewFilterName'   => 'Voer nieuwe filter naam in',
    'ErrorBrackets'        => 'Error, controleer of je even veel openings als afsluiting brackets hebt gebruikt',
    'Error'                => 'Error',
    'ErrorValidValue'      => 'Error, Controleer of alle termen een geldige waarde hebben',
    'Etc'                  => 'etc',
    'EventFilter'          => 'Gebeurtenis filter',
    'Event'                => 'Gebeurtenis',
    'EventId'              => 'Event Id',
    'EventName'            => 'Event Name',
    'EventPrefix'          => 'Event Prefix',
    'Events'               => 'Gebeurtenissen',
    'Exclude'              => 'Sluit uit',
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
    'Feed'                 => 'toevoer',
    'FileColours'          => 'File Colours',
    'File'                 => 'File',
    'FilePath'             => 'File Path',
    'FilterArchiveEvents'  => 'Archiveer alle overeenkomsten',
    'FilterDeleteEvents'   => 'Verwijder alle overeenkomsten',
    'FilterEmailEvents'    => 'Email de details van alle overeenkomsten',
    'FilterExecuteEvents'  => 'Voer opdrachten op alle overeenkomsten uit',
    'FilterMessageEvents'  => 'Bericht de details van alle overeenkomsten',
    'FilterPx'             => 'Filter px',
    'Filters'              => 'Filters',
    'FilterUnset'          => 'You must specify a filter width and height',
    'FilterUploadEvents'   => 'Upload alle overeenkomsten',
    'FilterVideoEvents'    => 'Create video for all matches',
    'First'                => 'Eerste',
    'FlippedHori'          => 'Flipped Horizontally',
    'FlippedVert'          => 'Flipped Vertically',
    'Focus'                => 'Focus',
    'ForceAlarm'           => 'Forceeer alarm',
    'Format'               => 'Format',
    'FPS'                  => 'fps',
    'FPSReportInterval'    => 'FPS rapport interval',
    'Frame'                => 'Frame',
    'FrameId'              => 'Frame id',
    'FrameRate'            => 'Frame rate',
    'Frames'               => 'Frames',
    'FrameSkip'            => 'Frame overgeslagen',
    'FTP'                  => 'FTP',
    'Func'                 => 'Func',
    'Function'             => 'Functie',
    'Gain'                 => 'Gain',
    'General'              => 'General',
    'GenerateVideo'        => 'Genereer Video',
    'GeneratingVideo'      => 'Genereren Video',
    'GoToZoneMinder'       => 'ga naar ZoneMinder.com',
    'Grey'                 => 'Grijs',
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
    'HighBW'               => 'Hoog&nbsp;B/W',
    'High'                 => 'Hoog',
    'Home'                 => 'Home',
    'Hour'                 => 'Uur',
    'Hue'                  => 'Hue',
    'Id'                   => 'Id',
    'Idle'                 => 'Ongebruikt',
    'Ignore'               => 'Negeer',
    'ImageBufferSize'      => 'Image buffer grootte (frames)',
    'Image'                => 'Image',
    'Images'               => 'Images',
    'Include'              => 'voeg in',
    'In'                   => 'In',
    'Inverted'             => 'omgedraaid',
    'Iris'                 => 'Iris',
    'KeyString'            => 'Key String',
    'Label'                => 'Label',
    'Language'             => 'Taal',
    'Last'                 => 'Laatste',
    'LimitResultsPost'     => 'resultaten;', // This is used at the end of the phrase 'Limit to first N results only'
    'LimitResultsPre'      => 'beperk tot eerste', // This is used at the beginning of the phrase 'Limit to first N results only'
    'LinkedMonitors'       => 'Linked Monitors',
    'List'                 => 'List',
    'Load'                 => 'Load',
    'Local'                => 'Lokaal',
    'LoggedInAs'           => 'Ingelogd als',
    'LoggingIn'            => 'In loggen',
    'Login'                => 'Login',
    'Logout'               => 'Logout',
    'LowBW'                => 'Laag&nbsp;B/W',
    'Low'                  => 'Laag',
    'Main'                 => 'Main',
    'Man'                  => 'Man',
    'Manual'               => 'Manual',
    'Mark'                 => 'Markeer',
    'MaxBandwidth'         => 'Max Bandwidth',
    'MaxBrScore'           => 'Max.<br/>score',
    'MaxFocusRange'        => 'Max Focus Range',
    'MaxFocusSpeed'        => 'Max Focus Speed',
    'MaxFocusStep'         => 'Max Focus Step',
    'MaxGainRange'         => 'Max Gain Range',
    'MaxGainSpeed'         => 'Max Gain Speed',
    'MaxGainStep'          => 'Max Gain Step',
    'MaximumFPS'           => 'Maximum FPS',
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
    'MediumBW'             => 'Medium&nbsp;B/W',
    'Medium'               => 'Medium',
    'MinAlarmAreaLtMax'    => 'Minimum alarm area should be less than maximum',
    'MinAlarmAreaUnset'    => 'You must specify the minimum alarm pixel count',
    'MinBlobAreaLtMax'     => 'minimum blob gebied moet kleiner zijn dan maximum blob gebied',
    'MinBlobAreaUnset'     => 'You must specify the minimum blob pixel count',
    'MinBlobLtMinFilter'   => 'Minimum blob area should be less than or equal to minimum filter area',
    'MinBlobsLtMax'        => 'minimum blobs moet kleiner zijn dan maximum blobs',
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
    'MinPixelThresLtMax'   => 'minimum pixel kleurdiepte moet kleiner zijn dan maximum pixel threshold',
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
    'Misc'                 => 'Misc',
    'MonitorIds'           => 'Monitor&nbsp;Ids',
    'Monitor'              => 'Monitor',
    'MonitorPresetIntro'   => 'Select an appropriate preset from the list below.<br><br>Please note that this may overwrite any values you already have configured for this monitor.<br><br>',
    'MonitorPreset'        => 'Monitor Preset',
    'Monitors'             => 'Monitoren',
    'Montage'              => 'Montage',
    'Month'                => 'Maand',
    'Move'                 => 'Move',
    'MustBeGe'             => 'Moet groter zijn of gelijk aan',
    'MustBeLe'             => 'Moet kleiner zijn of gelijk aan',
    'MustConfirmPassword'  => 'Je moet je wachtwoord bevestigen',
    'MustSupplyPassword'   => 'Je moet een wachtwoord geven',
    'MustSupplyUsername'   => 'Je moet een gebruikersnaam geven',
    'Name'                 => 'Naam',
    'Near'                 => 'Near',
    'Network'              => 'Netwerk',
    'NewGroup'             => 'New Group',
    'NewLabel'             => 'New Label',
    'New'                  => 'Nieuw',
    'NewPassword'          => 'Nieuw Wachtwoord',
    'NewState'             => 'Nieuwe Status',
    'NewUser'              => 'Nieuwe gebruiker',
    'Next'                 => 'Volgende',
    'NoFramesRecorded'     => 'Er zijn geen frames opgenomen voor deze gebeurtenis',
    'NoGroup'              => 'No Group',
    'NoneAvailable'        => 'geen beschikbaar',
    'No'                   => 'Nee',
    'None'                 => 'Geen',
    'Normal'               => 'Normaal',
    'NoSavedFilters'       => 'GeenOpgeslagenFilters',
    'NoStatisticsRecorded' => 'er zijn geen statistieken opgenomen voor dit event/frame',
    'Notes'                => 'Notes',
    'NumPresets'           => 'Num Presets',
    'Off'                  => 'Off',
    'On'                   => 'On',
    'Open'                 => 'Open',
    'OpEq'                 => 'gelijk aan',
    'OpGtEq'               => 'groter dan of gelijk aan',
    'OpGt'                 => 'groter dan',
    'OpIn'                 => 'in set',
    'OpLtEq'               => 'kleiner dan of gelijk aan',
    'OpLt'                 => 'kleiner dan',
    'OpMatches'            => 'Komt overeen',
    'OpNe'                 => 'niet gelijk aan',
    'OpNotIn'              => 'niet in set',
    'OpNotMatches'         => 'Komt niet overeen',
    'OptionHelp'           => 'OptieHelp',
    'OptionRestartWarning' => 'Deze veranderingen passen niet aan\nals het systeem loopt. Als je\nKlaar bent met veranderen vergeet dan niet dat\nje ZoneMinder herstart.',
    'Options'              => 'Opties',
    'Order'                => 'Order',
    'OrEnterNewName'       => 'of voer een nieuwe naam in',
    'Orientation'          => 'Orientatie',
    'Out'                  => 'Out',
    'OverwriteExisting'    => 'Overschrijf bestaande',
    'Paged'                => 'Paged',
    'PanLeft'              => 'Pan Left',
    'Pan'                  => 'Pan',
    'PanRight'             => 'Pan Right',
    'PanTilt'              => 'Pan/Tilt',
    'Parameter'            => 'Parameter',
    'PasswordsDifferent'   => 'Het nieuwe en bevestigde wachtwoord zijn verschillend',
    'Password'             => 'Wachtwoord',
    'Paths'                => 'Paden',
    'Pause'                => 'Pause',
    'PhoneBW'              => 'Telefoon&nbsp;B/W',
    'Phone'                => 'Phone',
    'PixelDiff'            => 'Pixel Diff',
    'Pixels'               => 'pixels',
    'PlayAll'              => 'Play All',
    'Play'                 => 'Play',
    'PleaseWait'           => 'wacht A.U.B.',
    'Point'                => 'Point',
    'PostEventImageBuffer' => 'Post gebeurtenis Image Buffer',
    'PreEventImageBuffer'  => 'Pre gebeurtenis Image Buffer',
    'PreserveAspect'       => 'Preserve Aspect Ratio',
    'Preset'               => 'Preset',
    'Presets'              => 'Presets',
    'Prev'                 => 'vorige',
    'Protocol'             => 'Protocol',
    'Rate'                 => 'Waardering',
    'Real'                 => 'Echte',
    'Record'               => 'Record',
    'RefImageBlendPct'     => 'Referentie Image Blend %ge',
    'Refresh'              => 'Ververs',
    'RemoteHostName'       => 'Remote Host Naam',
    'RemoteHostPath'       => 'Remote Host Pad',
    'RemoteHostPort'       => 'Remote Host Poort',
    'RemoteImageColours'   => 'Remote Image kleuren',
    'Remote'               => 'Remote',
    'Rename'               => 'Hernoem',
    'ReplayAll'            => 'All Events',
    'ReplayGapless'        => 'Gapless Events',
    'Replay'               => 'Herhaal',
    'Replay'               => 'Replay',
    'ReplaySingle'         => 'Single Event',
    'ResetEventCounts'     => 'Reset gebeurtenis teller',
    'Reset'                => 'Reset',
    'Restart'              => 'herstart',
    'Restarting'           => 'herstarten',
    'RestrictedCameraIds'  => 'Verboden Camera Ids',
    'RestrictedMonitors'   => 'Restricted Monitors',
    'ReturnDelay'          => 'Return Delay',
    'ReturnLocation'       => 'Return Location',
    'Rewind'               => 'Rewind',
    'RotateLeft'           => 'Draai linksom',
    'RotateRight'          => 'Draai rechtsom',
    'RunMode'              => 'Run Mode',
    'Running'              => 'Running',
    'RunState'             => 'Run Status',
    'SaveAs'               => 'opslaan als',
    'SaveFilter'           => 'opslaan Filter',
    'Save'                 => 'Opslaan',
    'Scale'                => 'Schaal',
    'Score'                => 'Score',
    'Secs'                 => 'Secs',
    'Sectionlength'        => 'Sectie lengte',
    'SelectMonitors'       => 'Select Monitors',
    'Select'               => 'Select',
    'SelfIntersecting'     => 'Polygon edges must not intersect',
    'SetNewBandwidth'      => 'Zet Nieuwe Bandbreedte',
    'SetPreset'            => 'Set Preset',
    'Set'                  => 'Set',
    'Settings'             => 'Instellingen',
    'ShowFilterWindow'     => 'ToonFilterWindow',
    'ShowTimeline'         => 'Show Timeline',
    'SignalCheckColour'    => 'Signal Check Colour',
    'Size'                 => 'Size',
    'Sleep'                => 'Sleep',
    'SortAsc'              => 'Opl.',
    'SortBy'               => 'Sorteer op',
    'SortDesc'             => 'afl.',
    'Source'               => 'Bron',
    'SourceType'           => 'Bron Type',
    'SpeedHigh'            => 'High Speed',
    'SpeedLow'             => 'Low Speed',
    'SpeedMedium'          => 'Medium Speed',
    'Speed'                => 'Speed',
    'SpeedTurbo'           => 'Turbo Speed',
    'Start'                => 'Start',
    'State'                => 'Status',
    'Stats'                => 'Stats',
    'Status'               => 'Status',
    'StepBack'             => 'Step Back',
    'StepForward'          => 'Step Forward',
    'StepLarge'            => 'Large Step',
    'StepMedium'           => 'Medium Step',
    'StepNone'             => 'No Step',
    'StepSmall'            => 'Small Step',
    'Step'                 => 'Step',
    'Stills'               => 'Plaatjes',
    'Stopped'              => 'gestopt',
    'Stop'                 => 'Stop',
    'StreamReplayBuffer'   => 'Stream Replay Image Buffer',
    'Stream'               => 'Stroom',
    'Submit'               => 'Submit',
    'System'               => 'Systeem',
    'Tele'                 => 'Tele',
    'Thumbnail'            => 'Thumbnail',
    'Tilt'                 => 'Tilt',
    'TimeDelta'            => 'Tijd Delta',
    'Timeline'             => 'Timeline',
    'TimestampLabelFormat' => 'Tijdstempel Label Format',
    'TimestampLabelX'      => 'Tijdstempel Label X',
    'TimestampLabelY'      => 'Tijdstempel Label Y',
    'Timestamp'            => 'Tijdstempel',
    'TimeStamp'            => 'Tijdstempel',
    'Time'                 => 'Tijd',
    'Today'                => 'Today',
    'Tools'                => 'Gereedschappen',
    'TotalBrScore'         => 'Totaal<br/>Score',
    'TrackDelay'           => 'Track Delay',
    'TrackMotion'          => 'Track Motion',
    'Triggers'             => 'Triggers',
    'TurboPanSpeed'        => 'Turbo Pan Speed',
    'TurboTiltSpeed'       => 'Turbo Tilt Speed',
    'Type'                 => 'Type',
    'Unarchive'            => 'Dearchiveer',
    'Units'                => 'Eenheden',
    'Unknown'              => 'Onbekend',
    'UpdateAvailable'      => 'een update voor ZoneMinder is beschikbaar',
    'UpdateNotNecessary'   => 'geen update noodzakelijk',
    'Update'               => 'Update',
    'UseFilterExprsPost'   => '&nbsp;filter&nbsp;expressies', // This is used at the end of the phrase 'use N filter expressions'
    'UseFilterExprsPre'    => 'Gebruik&nbsp;', // This is used at the beginning of the phrase 'use N filter expressions'
    'UseFilter'            => 'Gebruik Filter',
    'User'                 => 'Gebruiker',
    'Username'             => 'Gebruikersnaam',
    'Users'                => 'Gebruikers',
    'Value'                => 'Waarde',
    'VersionIgnore'        => 'negeer deze versie',
    'VersionRemindDay'     => 'herinner me na 1 dag',
    'VersionRemindHour'    => 'herinner me na 1 uur',
    'VersionRemindNever'   => 'herinner me niet aan nieuwe versies',
    'VersionRemindWeek'    => 'herinner me na 1 week',
    'Version'              => 'Versie',
    'VideoFormat'          => 'Video Format',
    'VideoGenFailed'       => 'Video Generatie mislukt!',
    'VideoGenFiles'        => 'Existing Video Files',
    'VideoGenNoFiles'      => 'No Video Files Found',
    'VideoGenParms'        => 'Video Generatie Parameters',
    'VideoGenSucceeded'    => 'Video Generation Succeeded!',
    'VideoSize'            => 'Video grootte',
    'Video'                => 'Video',
    'ViewAll'              => 'Bekijk Alles',
    'View'                 => 'Bekijk',
    'ViewEvent'            => 'View Event',
    'ViewPaged'            => 'Bekijk Paged',
    'Wake'                 => 'Wake',
    'WarmupFrames'         => 'Warmup Frames',
    'Watch'                => 'Observeer',
    'WebColour'            => 'Web Colour',
    'Web'                  => 'Web',
    'Week'                 => 'Week',
    'WhiteBalance'         => 'White Balance',
    'White'                => 'White',
    'Wide'                 => 'Wide',
    'X10ActivationString'  => 'X10 Activatie String',
    'X10InputAlarmString'  => 'X10 Input Alarm String',
    'X10OutputAlarmString' => 'X10 Output Alarm String',
    'X10'                  => 'X10',
    'X'                    => 'X',
    'Yes'                  => 'Ja',
    'YouNoPerms'           => 'Je hebt niet de rechten om toegang te krijgen tot deze bronnen.',
    'Y'                    => 'Y',
    'ZoneAlarmColour'      => 'Alarm Kleur (Red/Green/Blue)',
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
    'CurrentLogin'         => 'huidige login is \'%1$s\'',
    'EventCount'           => '%1$s %2$s', // Als voorbeeld '37 gebeurtenissen' (from Vlang below)
    'LastEvents'           => 'Last %1$s %2$s', // Als voorbeeld 'Laatste 37 gebeurtenissen' (from Vlang below)
    'LatestRelease'        => 'de laatste release is v%1$s, jij hebt v%2$s.',
    'MonitorCount'         => '%1$s %2$s', // Als voorbeeld '4 Monitoren' (from Vlang below)
    'MonitorFunction'      => 'Monitor %1$s Functie',
    'RunningRecentVer'     => 'Je draait al met de laatste versie van ZoneMinder, v%s.',
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
    'Event'                => array( 0=>'gebeurtenissen', 1=>'gebeurtenis', 2=>'gebeurtenissen' ),
    'Monitor'              => array( 0=>'Monitoren', 1=>'Monitor', 2=>'Monitoren' ),
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
