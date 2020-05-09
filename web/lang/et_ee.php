<?php
//
// ZoneMinder web US Estonian language file, $Date$, $Revision$
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

// $Date: 2009-03-31
// $Revision: 2829
// ZoneMinder estonian Translation by Seston seston@gmail.com 
// Who would care assistance to help to translate texts, and all this at all. Alone is somehow boring business.... 
//Kes viitsiks aidata tõlkida abitekste ja üldse kõike seda.Üksi on kuidagi igav ettevõtmine....

// ZoneMinder Estonian Translation by Hannes hanzese@gmail.com
// I bother because zoneminder is cool.....    Mina viitsin, sest ZoneMinder on lahe.....

//Notes for Translators
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
//require_once( 'zm_lang_en_GB.php' );

// You may need to change the character set here, if your web server does not already
// do this by default, uncomment this if required.
//
// Example
// header( "Content-Type: text/html; charset=utf-8' );

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
    '24BitColour'           => '24 bit värvid',
    '32BitColour'           => '32 bit värvid',          // Added - 2011-06-15
    '8BitGrey'              => '8 bit mustvalge',
    'Action'                => 'Action',
    'Actual'                => 'Aktuaalne',
    'AddNewControl'         => 'Lisa uus Kontroll',
    'AddNewMonitor'         => 'Lisa uus Monitor',
    'AddNewServer'         => 'Add New Server',         // Added - 2018-08-30
    'AddNewStorage'        => 'Add New Storage',        // Added - 2018-08-30
    'AddNewUser'            => 'Lisa uus Kasutaja',
    'AddNewZone'            => 'Lisa uus Tsoon',
    'Alarm'                 => 'Alarm',
    'AlarmBrFrames'         => 'Alarmi<br/>kaadrid',
    'AlarmFrame'            => 'Alarmi kaader',
    'AlarmFrameCount'       => 'Alarmi kaadri hulk',
    'AlarmLimits'           => 'Alarmi limiidid',
    'AlarmMaximumFPS'       => 'Alarmi Maksimaalne FPS',
    'AlarmPx'               => 'Alarm Px',
    'AlarmRGBUnset'         => 'Sa pead panema alarmi RGB värvi',
    'AlarmRefImageBlendPct'=> 'Alarm Reference Image Blend %ge', // Added - 2015-04-18
    'Alert'                 => 'Hoiatus',
    'All'                   => 'All',
    'AnalysisFPS'          => 'Analysis FPS',           // Added - 2015-07-22
    'AnalysisUpdateDelay'  => 'Analysis Update Delay',  // Added - 2015-07-23
    'Apply'                 => 'Apply',
    'ApplyingStateChange'   => 'Applying State Change',
    'ArchArchived'          => 'Arhiveeritud Ainult',
    'ArchUnarchived'        => 'Arhiveerimatta Ainult',
    'Archive'               => 'Arhiiv',
    'Archived'              => 'Arhiveeritud',
    'Area'                  => 'Ala',
    'AreaUnits'             => 'Ala (px/%)',
    'AttrAlarmFrames'       => 'Alarmi kaadrid',
    'AttrArchiveStatus'     => 'Arhiivi Staatus',
    'AttrAvgScore'          => 'Keskm. Skoor',
    'AttrCause'             => 'Põhjus',
    'AttrDiskBlocks'        => 'Ketta Blokk',
    'AttrDiskPercent'       => 'Ketta Protsent',
    'AttrDiskSpace'        => 'Disk Space',             // Added - 2018-08-30
    'AttrDuration'          => 'Kestvus',
    'AttrEndDate'          => 'End Date',               // Added - 2018-08-30
    'AttrEndDateTime'      => 'End Date/Time',          // Added - 2018-08-30
    'AttrEndTime'          => 'End Time',               // Added - 2018-08-30
    'AttrEndWeekday'       => 'End Weekday',            // Added - 2018-08-30
    'AttrFilterServer'     => 'Server Filter is Running On', // Added - 2018-08-30
    'AttrFrames'            => 'Kaadrid',
    'AttrId'                => 'Id',
    'AttrMaxScore'          => 'Maks. Skoor',
    'AttrMonitorId'         => 'Monitori Id',
    'AttrMonitorName'       => 'Monitori Nimi',
    'AttrMonitorServer'    => 'Server Monitor is Running On', // Added - 2018-08-30
    'AttrName'              => 'Nimi',
    'AttrNotes'             => 'Märkmed',
    'AttrStartDate'        => 'Start Date',             // Added - 2018-08-30
    'AttrStartDateTime'    => 'Start Date/Time',        // Added - 2018-08-30
    'AttrStartTime'        => 'Start Time',             // Added - 2018-08-30
    'AttrStartWeekday'     => 'Start Weekday',          // Added - 2018-08-30
    'AttrStateId'          => 'Run State',              // Added - 2018-08-30
    'AttrStorageArea'      => 'Storage Area',           // Added - 2018-08-30
    'AttrStorageServer'    => 'Server Hosting Storage', // Added - 2018-08-30
    'AttrSystemLoad'        => 'Süsteemi Koormus',
    'AttrTotalScore'        => 'Skoor Kokku',
    'Auto'                  => 'Auto',
    'AutoStopTimeout'       => 'Auto Stop Ajalimiit',
    'Available'             => 'Saadaval',
    'AvgBrScore'            => 'Keskm.<br/>Skoor',
    'Background'            => 'Taust',
    'BackgroundFilter'      => 'Käivita filter taustal',
    'BadAlarmFrameCount'    => 'Alarmi kaadri hulga ühik peab olema integer. Kas üks või rohkem',
    'BadAlarmMaxFPS'        => 'Alarmi maksimaalne FPS peab olema positiivne integer või floating point väärtus',
    'BadAnalysisFPS'       => 'Analysis FPS must be a positive integer or floating point value', // Added - 2015-07-22
    'BadAnalysisUpdateDelay'=> 'Analysis update delay must be set to an integer of zero or more', // Added - 2015-07-23
    'BadChannel'            => 'Kanal peab olema integer, null või rohkem',
    'BadColours'            => 'Sihtmärgi värv peab olema pandud õige väärtus', // Added - 2011-06-15
    'BadDevice'             => 'Seadmel peab olema õige väärtus',
    'BadFPSReportInterval'  => 'FPS raporteerimise intervall puhvri hulk peab olema integer, null või rohkem',
    'BadFormat'             => 'Formaadiks peab olema pandud õige väärtus',
    'BadFrameSkip'          => 'Kaadri vahelejätmise hulk peab olema integer, null või rohkem',
    'BadHeight'             => 'Kõrguseks peab olema valitud õige väärtus',
    'BadHost'               => 'Host ipeab olema õige. Ip aadress või hostinimi, ei tohi sisaldada http://',
    'BadImageBufferCount'   => 'Pildi puhvri suurus peab olema integer, 10 või rohkem',
    'BadLabelX'             => 'Label X co-ordinate must be set to an integer of zero or more',
    'BadLabelY'             => 'Label Y co-ordinate must be set to an integer of zero or more',
    'BadMaxFPS'             => 'Maximum FPS must be a positive integer or floating point value',
    'BadMotionFrameSkip'    => 'Liikumise kaadri vahelejätmise hulk peab olema integer, null või rohkem',
    'BadNameChars'          => 'Names may only contain alphanumeric characters plus hyphen and underscore',
    'BadPalette'            => 'Palette must be set to a valid value',
    'BadPath'               => 'Path must be set to a valid value',
    'BadPort'               => 'Port must be set to a valid number',
    'BadPostEventCount'     => 'Post event image count must be an integer of zero or more',
    'BadPreEventCount'      => 'Pre event image count must be at least zero, and less than image buffer size',
    'BadRefBlendPerc'       => 'Reference blend percentage must be a positive integer',
    'BadSectionLength'      => 'Section length must be an integer of 30 or more',
    'BadSignalCheckColour'  => 'Signal check colour must be a valid RGB colour string',
    'BadSourceType'        => 'Source Type \"Web Site\" requires the Function to be set to \"Monitor\"', // Added - 2018-08-30
    'BadStreamReplayBuffer' => 'Stream replay buffer must be an integer of zero or more',
    'BadWarmupCount'        => 'Warmup frames must be an integer of zero or more',
    'BadWebColour'          => 'Web colour must be a valid web colour string',
    'BadWebSitePath'       => 'Please enter a complete website url, including the http:// or https:// prefix.', // Added - 2018-08-30
    'BadWidth'              => 'Width must be set to a valid value',
    'Bandwidth'             => 'Ribalaius',
    'BandwidthHead'         => 'Ribalaius',	// This is the end of the bandwidth status on the top of the console, different in many language due to phrasing
    'BlobPx'                => 'Blob Px',
    'BlobSizes'             => 'Blob Sizes',
    'Blobs'                 => 'Blobs',
    'Brightness'            => 'Heledus',
    'Buffer'               => 'Buffer',                 // Added - 2015-04-18
    'Buffers'               => 'Puhver',
    'CSSDescription'       => 'Change the default css for this computer', // Added - 2015-04-18
    'CanAutoFocus'          => 'Can Auto Focus',
    'CanAutoGain'           => 'Can Auto Gain',
    'CanAutoIris'           => 'Can Auto Iris',
    'CanAutoWhite'          => 'Can Auto White Bal.',
    'CanAutoZoom'           => 'Can Auto Zoom',
    'CanFocus'              => 'Can Focus',
    'CanFocusAbs'           => 'Can Focus Absolute',
    'CanFocusCon'           => 'Can Focus Continuous',
    'CanFocusRel'           => 'Can Focus Relative',
    'CanGain'               => 'Can Gain ',
    'CanGainAbs'            => 'Can Gain Absolute',
    'CanGainCon'            => 'Can Gain Continuous',
    'CanGainRel'            => 'Can Gain Relative',
    'CanIris'               => 'Can Iris',
    'CanIrisAbs'            => 'Can Iris Absolute',
    'CanIrisCon'            => 'Can Iris Continuous',
    'CanIrisRel'            => 'Can Iris Relative',
    'CanMove'               => 'Can Move',
    'CanMoveAbs'            => 'Can Move Absolute',
    'CanMoveCon'            => 'Can Move Continuous',
    'CanMoveDiag'           => 'Can Move Diagonally',
    'CanMoveMap'            => 'Can Move Mapped',
    'CanMoveRel'            => 'Can Move Relative',
    'CanPan'                => 'Can Pan' ,
    'CanReset'              => 'Can Reset',
	'CanReboot'             => 'Can Reboot',
    'CanSetPresets'         => 'Can Set Presets',
    'CanSleep'              => 'Can Sleep',
    'CanTilt'               => 'Can Tilt',
    'CanWake'               => 'Can Wake',
    'CanWhite'              => 'Can White Balance',
    'CanWhiteAbs'           => 'Can White Bal. Absolute',
    'CanWhiteBal'           => 'Can White Bal.',
    'CanWhiteCon'           => 'Can White Bal. Continuous',
    'CanWhiteRel'           => 'Can White Bal. Relative',
    'CanZoom'               => 'Can Zoom',
    'CanZoomAbs'            => 'Can Zoom Absolute',
    'CanZoomCon'            => 'Can Zoom Continuous',
    'CanZoomRel'            => 'Can Zoom Relative',
    'Cancel'                => 'Cancel',
    'CancelForcedAlarm'     => 'Cancel Forced Alarm',
    'CaptureHeight'         => 'Capture Height',
    'CaptureMethod'         => 'Capture Method',
    'CapturePalette'        => 'Capture Palette',
    'CaptureResolution'    => 'Capture Resolution',     // Added - 2015-04-18
    'CaptureWidth'          => 'Capture Width',
    'Cause'                 => 'Cause',
    'CheckMethod'           => 'Alarm Check Method',
    'ChooseDetectedCamera'  => 'Vali tuvastatud kaamera',
    'ChooseFilter'          => 'Vali Filter',
    'ChooseLogFormat'       => 'Choose a log format',    // Added - 2011-06-17
    'ChooseLogSelection'    => 'Choose a log selection', // Added - 2011-06-17
    'ChoosePreset'          => 'Choose Preset',
    'Clear'                 => 'Clear',                  // Added - 2011-06-16
    'CloneMonitor'         => 'Clone',                  // Added - 2018-08-30
    'Close'                 => 'Sule',
    'Colour'                => 'Värv',
    'Command'               => 'Käsk',
    'Component'             => 'Komponent',              // Added - 2011-06-16
    'ConcurrentFilter'     => 'Run filter concurrently', // Added - 2018-08-30
    'Config'                => 'Seadistus',
    'ConfiguredFor'         => 'Seadistatud',
    'ConfirmDeleteEvents'   => 'Oled sa kindel kustamaks valitud sündmused?',
    'ConfirmPassword'       => 'Kinnita salasõna',
    'ConjAnd'               => 'ja',
    'ConjOr'                => 'või',
    'Console'               => 'Konsool',
    'ContactAdmin'          => 'Võta ühendust adminniga.',
    'Continue'              => 'Jätka',
    'Contrast'              => 'Kontrast',
    'Control'               => 'Control',
    'ControlAddress'        => 'Control Address',
    'ControlCap'            => 'Control Capability',
    'ControlCaps'           => 'Control Capabilities',
    'ControlDevice'         => 'Control Device',
    'ControlType'           => 'Control Type',
    'Controllable'          => 'Controllable',
    'Current'              => 'Current',                // Added - 2015-04-18
    'Cycle'                 => 'Cycle',
    'CycleWatch'            => 'Cycle Watch',
    'DateTime'              => 'Kuupäev/Aeg',              // Added - 2011-06-16
    'Day'                   => 'Päevas',
    'Debug'                 => 'Debug',
    'DefaultRate'           => 'Default Kiirus',
    'DefaultScale'          => 'Default Suurus',
    'DefaultView'           => 'Default Vaade',
    'Deinterlacing'        => 'Deinterlacing',          // Added - 2015-04-18
    'Delay'                => 'Delay',                  // Added - 2015-04-18
    'Delete'                => 'Kustuta',
    'DeleteAndNext'         => 'Kustuta &amp; Järgmine',
    'DeleteAndPrev'         => 'Kustuta &amp; Eelmine',
    'DeleteSavedFilter'     => 'Kustuta salvestatud filter',
    'Description'           => 'Kirjeldus',
    'DetectedCameras'       => 'Tuvastatud kaamerad',
    'DetectedProfiles'     => 'Detected Profiles',      // Added - 2015-04-18
    'Device'                => 'Seade',
    'DeviceChannel'         => 'Seadme Kanal',
    'DeviceFormat'          => 'Seadme Formaat',
    'DeviceNumber'          => 'Seadme Number',
    'DevicePath'            => 'Seadme Path',
    'Devices'               => 'Seadmed',
    'Dimensions'            => 'Mõõdud',
    'DisableAlarms'         => 'Keela alarmid',
    'Disk'                  => 'Ketas',
    'Display'               => 'Ekraan',                // Added - 2011-03-02
    'Displaying'            => 'Väljapanek',             // Added - 2011-06-16
    'DoNativeMotionDetection'=> 'Do Native Motion Detection', // Added - 2015-04-18
    'Donate'                => 'Palun Anneta',
    'DonateAlready'         => 'EI, Ma olen juba annetanud',
    'DonateEnticement'      => 'Sa oled juba kasutanud ZoneMinderit juba mõnda aega. Nüüd kus sa oled leidnud, et see on kasulik lisa sinu kodule  või sinu töökohale. Kuigi ZoneMinder on, jääb alatiseks, vabaks ja avatud lähtekoodiks, siiski selle arendamiseks kulub aega ja raha. Kui sa soovid meid aidata, siis toeta meid tuleviku arendusteks ja uute lisade loomiseks. Palun mõelge annetuse peale. Donating is, of course, optional but very much appreciated and you can donate as much or as little as you like.<br/><br/>If you would like to donate please select the option below or go to https://zoneminder.com/donate/ in your browser.<br/><br/>Thank you for using ZoneMinder and don\'t forget to visit the forums on ZoneMinder.com for support or suggestions about how to make your ZoneMinder experience even better.',
    'DonateRemindDay'       => 'Ei veel, tuleta meelde ühe päeva pärast',
    'DonateRemindHour'      => 'Ei veel, tuleta meelde ühe tunni pärast',
    'DonateRemindMonth'     => 'Ei veel, tuleta meelde ühe kuu pärast',
    'DonateRemindNever'     => 'EI, Ma ei taha annetada, Vahet pole',
    'DonateRemindWeek'      => 'EI veel, tuleta meelde nädala pärast',
    'DonateYes'             => 'Jah, Ma soovin annetada',
    'Download'              => 'Lae alla',
    'DownloadVideo'        => 'Download Video',         // Added - 2018-08-30
    'DuplicateMonitorName'  => 'Dubleeri Monitori Nimi',
    'Duration'              => 'Kestvus',
    'Edit'                  => 'Muuda',
    'EditLayout'           => 'Edit Layout',            // Added - 2018-08-30
    'Email'                 => 'Email',
    'EnableAlarms'          => 'Luba Alarmid',
    'Enabled'               => 'Lubatud',
    'EnterNewFilterName'    => 'Sisest uue filtri nimi',
    'Error'                 => 'Viga',
    'ErrorBrackets'         => 'Viga, please check you have an equal number of opening and closing brackets',
    'ErrorValidValue'       => 'Viga, please check that all terms have a valid value',
    'Etc'                   => 'etc',
    'Event'                 => 'Sündmus',
    'EventFilter'           => 'Sündmuste filter',
    'EventId'               => 'Sündmuse Id',
    'EventName'             => 'Sündmuse nimi',
    'EventPrefix'           => 'Sündmuse Prefix',
    'Events'                => 'Sündmuseid',
    'Exclude'               => 'Jäta välja',
    'Execute'               => 'Käivita',
    'Exif'                 => 'Embed EXIF data into image', // Added - 2018-08-30
    'Export'                => 'Eksport',
    'ExportDetails'         => 'Ekspordi Sündmuste Detailid',
    'ExportFailed'          => 'Eksportimine Ebaõnnestus',
    'ExportFormat'          => 'Ekspordi Faili Formaat',
    'ExportFormatTar'       => 'Tar',
    'ExportFormatZip'       => 'Zip',
    'ExportFrames'          => 'Ekspordi Kaadri Detailid',
    'ExportImageFiles'      => 'Ekspordi Pildi Failid',
    'ExportLog'            => 'Ekspordi Logi',             // Added - 2011-06-17
    'ExportMiscFiles'       => 'Ekspordi Teisi Faile (kui neid on)',
    'ExportOptions'         => 'Ekspordi Valikud',
    'ExportSucceeded'       => 'Eksportimine Õnnestus',
    'ExportVideoFiles'      => 'Export Video Files (kui neid on)',
    'Exporting'             => 'Eksportimine',
    'FPS'                   => 'fps',
    'FPSReportInterval'     => 'FPS Raporteerimise Intervall',
    'FTP'                   => 'FTP',
    'Far'                   => 'Far',
    'FastForward'           => 'Fast Forward',
    'Feed'                  => 'Feed',
    'Ffmpeg'                => 'Ffmpeg',
    'File'                  => 'Fail',
    'Filter'               => 'Filter',                 // Added - 2015-04-18
    'FilterArchiveEvents'   => 'Archive all matches',
    'FilterDeleteEvents'    => 'Delete all matches',
    'FilterEmailEvents'     => 'Email details of all matches',
    'FilterExecuteEvents'   => 'Execute command on all matches',
    'FilterLog'            => 'Filter log',             // Added - 2015-04-18
    'FilterMessageEvents'   => 'Message details of all matches',
    'FilterMoveEvents'     => 'Move all matches',       // Added - 2018-08-30
    'FilterPx'              => 'Filter Px',
    'FilterUnset'           => 'You must specify a filter width and height',
    'FilterUpdateDiskSpace'=> 'Update used disk space', // Added - 2018-08-30
    'FilterUploadEvents'    => 'Upload all matches',
    'FilterVideoEvents'     => 'Create video for all matches',
    'Filters'               => 'Filtrid',
    'First'                 => 'Esimene',
    'FlippedHori'           => 'Flipped Horizontally',
    'FlippedVert'           => 'Flipped Vertically',
    'FnMocord'              => 'Mocord',            // Added 2013.08.16.
    'FnModect'              => 'Modect',            // Added 2013.08.16.
    'FnMonitor'             => 'Monitor',            // Added 2013.08.16.
    'FnNodect'              => 'Nodect',            // Added 2013.08.16.
    'FnNone'                => 'None',            // Added 2013.08.16.
    'FnRecord'              => 'Record',            // Added 2013.08.16.
    'Focus'                 => 'Fookus',
    'ForceAlarm'            => 'Force Alarm',
    'Format'                => 'Format',
    'Frame'                 => 'Kaader',
    'FrameId'               => 'Frame Id',
    'FrameRate'             => 'Kaadri Sagedus',
    'FrameSkip'             => 'Frame Skip',
    'Frames'                => 'Kaadrid',
    'Func'                  => 'Func',
    'Function'              => 'Funktsioon',
    'Gain'                  => 'Gain',
    'General'               => 'Peamine',
    'GenerateDownload'     => 'Generate Download',      // Added - 2018-08-30
    'GenerateVideo'         => 'Genereeri Video',
    'GeneratingVideo'       => 'Genereerin Videot',
    'GoToZoneMinder'        => 'Mine ZoneMinder.com',
    'Grey'                  => 'Grey',
    'Group'                 => 'Grupp',
    'Groups'                => 'Grupid',
    'HasFocusSpeed'         => 'Has Focus Speed',
    'HasGainSpeed'          => 'Has Gain Speed',
    'HasHomePreset'         => 'Has Home Preset',
    'HasIrisSpeed'          => 'Has Iris Speed',
    'HasPanSpeed'           => 'Has Pan Speed',
    'HasPresets'            => 'Has Presets',
    'HasTiltSpeed'          => 'Has Tilt Speed',
    'HasTurboPan'           => 'Has Turbo Pan',
    'HasTurboTilt'          => 'Has Turbo Tilt',
    'HasWhiteSpeed'         => 'Has White Bal. Speed',
    'HasZoomSpeed'          => 'Has Zoom Speed',
    'High'                  => 'Suurim',
    'HighBW'                => 'High&nbsp;B/W',
    'Home'                  => 'Koju',
    'Hostname'             => 'Hostname',               // Added - 2018-08-30
    'Hour'                  => 'Tunnis',
    'Hue'                   => 'Hue',
    'Id'                    => 'Id',
    'Idle'                  => 'Idle',
    'Ignore'                => 'Ignoreeri',
    'Image'                 => 'Pilt',
    'ImageBufferSize'       => 'Image Buffer Size (frames)',
    'Images'                => 'Pildid',
    'In'                    => 'In',
    'Include'               => 'Include',
    'Inverted'              => 'Inverted',
    'Iris'                  => 'Iris',
    'KeyString'             => 'Key String',
    'Label'                 => 'Label',
    'Language'              => 'Keel',
    'Last'                  => 'Viimane',
    'Layout'                => 'Layout',
    'Level'                 => 'Level',                  // Added - 2011-06-16
    'Libvlc'                => 'Libvlc',
    'LimitResultsPost'      => 'results only', // This is used at the end of the phrase 'Limit to first N results only'
    'LimitResultsPre'       => 'Limit to first', // This is used at the beginning of the phrase 'Limit to first N results only'
    'Line'                  => 'Line',                   // Added - 2011-06-16
    'LinkedMonitors'        => 'Lingitud monitorid',
    'List'                  => 'List',
    'ListMatches'          => 'List Matches',           // Added - 2018-08-30
    'Load'                  => 'Koormus',
    'Local'                 => 'Local',
    'Log'                   => 'Logi',                    // Added - 2011-06-16
    'LoggedInAs'            => 'Sisse logitud',
    'Logging'               => 'Logimine',                // Added - 2011-06-16
    'LoggingIn'             => 'Login sisse',
    'Login'                 => 'Login',
    'Logout'                => 'Logi välja',
    'Logs'                  => 'Logid',                   // Added - 2011-06-17
    'Low'                   => 'Madal',
    'LowBW'                 => 'Low&nbsp;B/W',
    'Main'                  => 'Pea',
    'Man'                   => 'Man',
    'Manual'                => 'Juhend',
    'Mark'                  => 'Märgi',
    'Max'                   => 'Max',
    'MaxBandwidth'          => 'Max Ribalaius',
    'MaxBrScore'            => 'Max.<br/>Score',
    'MaxFocusRange'         => 'Max Focus Range',
    'MaxFocusSpeed'         => 'Max Focus Speed',
    'MaxFocusStep'          => 'Max Focus Step',
    'MaxGainRange'          => 'Max Gain Range',
    'MaxGainSpeed'          => 'Max Gain Speed',
    'MaxGainStep'           => 'Max Gain Step',
    'MaxIrisRange'          => 'Max Iris Range',
    'MaxIrisSpeed'          => 'Max Iris Speed',
    'MaxIrisStep'           => 'Max Iris Step',
    'MaxPanRange'           => 'Max Pan Range',
    'MaxPanSpeed'           => 'Max Pan Speed',
    'MaxPanStep'            => 'Max Pan Step',
    'MaxTiltRange'          => 'Max Tilt Range',
    'MaxTiltSpeed'          => 'Max Tilt Speed',
    'MaxTiltStep'           => 'Max Tilt Step',
    'MaxWhiteRange'         => 'Max White Bal. Range',
    'MaxWhiteSpeed'         => 'Max White Bal. Speed',
    'MaxWhiteStep'          => 'Max White Bal. Step',
    'MaxZoomRange'          => 'Max Zoom Range',
    'MaxZoomSpeed'          => 'Max Zoom Speed',
    'MaxZoomStep'           => 'Max Zoom Step',
    'MaximumFPS'            => 'Maksimaalne FPS',
    'Medium'                => 'Keskmine',
    'MediumBW'              => 'Medium&nbsp;B/W',
    'Message'               => 'Message',                // Added - 2011-06-16
    'MinAlarmAreaLtMax'     => 'Minimum alarm area should be less than maximum',
    'MinAlarmAreaUnset'     => 'You must specify the minimum alarm pixel count',
    'MinBlobAreaLtMax'      => 'Minimum blob area should be less than maximum',
    'MinBlobAreaUnset'      => 'You must specify the minimum blob pixel count',
    'MinBlobLtMinFilter'    => 'Minimum blob area should be less than or equal to minimum filter area',
    'MinBlobsLtMax'         => 'Minimum blobs should be less than maximum',
    'MinBlobsUnset'         => 'You must specify the minimum blob count',
    'MinFilterAreaLtMax'    => 'Minimum filter area should be less than maximum',
    'MinFilterAreaUnset'    => 'You must specify the minimum filter pixel count',
    'MinFilterLtMinAlarm'   => 'Minimum filter area should be less than or equal to minimum alarm area',
    'MinFocusRange'         => 'Min Focus Range',
    'MinFocusSpeed'         => 'Min Focus Speed',
    'MinFocusStep'          => 'Min Focus Step',
    'MinGainRange'          => 'Min Gain Range',
    'MinGainSpeed'          => 'Min Gain Speed',
    'MinGainStep'           => 'Min Gain Step',
    'MinIrisRange'          => 'Min Iris Range',
    'MinIrisSpeed'          => 'Min Iris Speed',
    'MinIrisStep'           => 'Min Iris Step',
    'MinPanRange'           => 'Min Pan Range',
    'MinPanSpeed'           => 'Min Pan Speed',
    'MinPanStep'            => 'Min Pan Step',
    'MinPixelThresLtMax'    => 'Minimum pixel threshold should be less than maximum',
    'MinPixelThresUnset'    => 'You must specify a minimum pixel threshold',
    'MinTiltRange'          => 'Min Tilt Range',
    'MinTiltSpeed'          => 'Min Tilt Speed',
    'MinTiltStep'           => 'Min Tilt Step',
    'MinWhiteRange'         => 'Min White Bal. Range',
    'MinWhiteSpeed'         => 'Min White Bal. Speed',
    'MinWhiteStep'          => 'Min White Bal. Step',
    'MinZoomRange'          => 'Min Zoom Range',
    'MinZoomSpeed'          => 'Min Zoom Speed',
    'MinZoomStep'           => 'Min Zoom Step',
    'Misc'                  => 'Misc',
    'Mode'                 => 'Mode',                   // Added - 2015-04-18
    'Monitor'               => 'Monitor',
    'MonitorIds'            => 'Monitor&nbsp;Ids',
    'MonitorPreset'         => 'Monitor Preset',
    'MonitorPresetIntro'    => 'Select an appropriate preset from the list below.<br/><br/>Please note that this may overwrite any values you already have configured for the current monitor.<br/><br/>',
    'MonitorProbe'          => 'Monitor Probe',
    'MonitorProbeIntro'     => 'The list below shows detected analog and network cameras and whether they are already being used or available for selection.<br/><br/>Select the desired entry from the list below.<br/><br/>Please note that not all cameras may be detected and that choosing a camera here may overwrite any values you already have configured for the current monitor.<br/><br/>',
    'Monitors'              => 'Monitors',
    'Montage'               => 'Montage',
    'MontageReview'        => 'Montage Review',         // Added - 2018-08-30
    'Month'                 => 'Kuus',
    'More'                  => 'Veel',                   // Added - 2011-06-16
    'MotionFrameSkip'       => 'Motion Frame Skip',
    'Move'                  => 'Liiguta',
    'Mtg2widgrd'            => '2-pildi ruudustik',              // Added 2013.08.15.
    'Mtg3widgrd'            => '3-pildi ruudustik',              // Added 2013.08.15.
    'Mtg3widgrx'            => '3-pildi ruudustik, skaleeritud, suurenda kui on alarm',              // Added 2013.08.15.
    'Mtg4widgrd'            => '4-pildi ruudustik',              // Added 2013.08.15.
    'MtgDefault'            => 'Default',              // Added 2013.08.15.
    'MustBeGe'              => 'peab olema suurem kui või võrdne ',
    'MustBeLe'              => 'peab olema väiksem kui või võrdne',
    'MustConfirmPassword'   => 'Sa pead kinnitama parooli',
    'MustSupplyPassword'    => 'Sa pead panema parooli',
    'MustSupplyUsername'    => 'Sa pead panema kasutaja nime',
    'Name'                  => 'Sündmus',
    'Near'                  => 'Lähedal',
    'Network'               => 'Võrk',
    'New'                   => 'Uus',
    'NewGroup'              => 'Uus Krupp',
    'NewLabel'              => 'Uus Nimi',
    'NewPassword'           => 'Uus Parool',
    'NewState'              => 'Uus Olek',
    'NewUser'               => 'Uus Kasutaja',
    'Next'                  => 'Järgmine',
    'No'                    => 'Ei',
    'NoDetectedCameras'     => 'Ei leidnud kaameraid',
    'NoDetectedProfiles'   => 'No Detected Profiles',   // Added - 2018-08-30
    'NoFramesRecorded'      => 'Ei ole kaadreid salvetatud selles sündmuses',
    'NoGroup'               => 'Ei krupp',
    'NoSavedFilters'        => 'EiSalvestatudFiltreid',
    'NoStatisticsRecorded'  => 'Ei ole statistikat salvestatud selle sündmuse/kaadri kohta',
    'None'                  => 'None',
    'NoneAvailable'         => 'None available',
    'Normal'                => 'Normaalne',
    'Notes'                 => 'Märkmed',
    'NumPresets'            => 'Num Presets',
    'Off'                   => 'Väljas',
    'On'                    => 'Sees',
    'OnvifCredentialsIntro'=> 'Please supply user name and password for the selected camera.<br/>If no user has been created for the camera then the user given here will be created with the given password.<br/><br/>', // Added - 2015-04-18
    'OnvifProbe'           => 'ONVIF',                  // Added - 2015-04-18
    'OnvifProbeIntro'      => 'The list below shows detected ONVIF cameras and whether they are already being used or available for selection.<br/><br/>Select the desired entry from the list below.<br/><br/>Please note that not all cameras may be detected and that choosing a camera here may overwrite any values you already have configured for the current monitor.<br/><br/>', // Added - 2015-04-18
    'OpEq'                  => 'Võrdne',
    'OpGt'                  => 'Suurem kui',
    'OpGtEq'                => 'suurem kui või võrdne',
    'OpIn'                  => 'in set',
    'OpIs'                 => 'is',                     // Added - 2018-08-30
    'OpIsNot'              => 'is not',                 // Added - 2018-08-30
    'OpLt'                  => 'vähem kui',
    'OpLtEq'                => 'vähem kui või võrdne',
    'OpMatches'             => 'klapib',
    'OpNe'                  => 'ei võrdne',
    'OpNotIn'               => 'not in set',
    'OpNotMatches'          => 'ei klapi',
    'Open'                  => 'Ava',
    'OptionHelp'            => 'Valik Aita',
    'OptionRestartWarning'  => 'These changes may not come into effect fully\nwhile the system is running. When you have\nfinished making your changes please ensure that\nyou restart ZoneMinder.',
    'OptionalEncoderParam' => 'Optional Encoder Parameters', // Added - 2018-08-30
    'Options'               => 'Seaded',
    'OrEnterNewName'        => 'või sisesta uus nimi',
    'Order'                 => 'Järjekord',
    'Orientation'           => 'Orientatsioon',
    'Out'                   => 'Out',
    'OverwriteExisting'     => 'Kirjuta üle',
    'Paged'                 => 'Paged',
    'Pan'                   => 'Pan',
    'PanLeft'               => 'Pan Left',
    'PanRight'              => 'Pan Right',
    'PanTilt'               => 'Pan/Tilt',
    'Parameter'             => 'Parameter',
    'Password'              => 'Password',
    'PasswordsDifferent'    => 'The new and confirm passwords are different',
    'Paths'                 => 'Paths',
    'Pause'                 => 'Pause',
    'Phone'                 => 'Telefon',
    'PhoneBW'               => 'Phone&nbsp;B/W',
    'Pid'                   => 'PID',                    // Added - 2011-06-16
    'PixelDiff'             => 'Pixel Diff',
    'Pixels'                => 'pikslid',
    'Play'                  => 'Play',
    'PlayAll'               => 'Play Kõike',
    'PleaseWait'            => 'Palun Oota',
    'Plugins'               => 'Pluginad',
    'Point'                 => 'Punkt',
    'PostEventImageBuffer'  => 'Post Event Image Count',
    'PreEventImageBuffer'   => 'Pre Event Image Count',
    'PreserveAspect'        => 'Preserve Aspect Ratio',
    'Preset'                => 'Eelseatud',
    'Presets'               => 'Eelseaded',
    'Prev'                  => 'Prev',
    'Probe'                 => 'Probe',
    'ProfileProbe'         => 'Stream Probe',           // Added - 2015-04-18
    'ProfileProbeIntro'    => 'The list below shows the existing stream profiles of the selected camera .<br/><br/>Select the desired entry from the list below.<br/><br/>Please note that ZoneMinder cannot configure additional profiles and that choosing a camera here may overwrite any values you already have configured for the current monitor.<br/><br/>', // Added - 2015-04-18
    'Progress'             => 'Progress',               // Added - 2015-04-18
    'Protocol'              => 'Protocol',
    'RTSPDescribe'         => 'Use RTSP Response Media URL', // Added - 2018-08-30
    'RTSPTransport'        => 'RTSP Transport Protocol', // Added - 2018-08-30
    'Rate'                  => 'Rate',
    'Real'                  => 'Reaaalne',
    'RecaptchaWarning'     => 'Your reCaptcha secret key is invalid. Please correct it, or reCaptcha will not work', // Added - 2018-08-30
    'Record'                => 'Salvesta',
    'RecordAudio'          => 'Whether to store the audio stream when saving an event.', // Added - 2018-08-30
    'RefImageBlendPct'      => 'Reference Image Blend %ge',
    'Refresh'               => 'Värskenda',
    'Remote'                => 'Remote',
    'RemoteHostName'        => 'Remote Host Name',
    'RemoteHostPath'        => 'Remote Host Path',
    'RemoteHostPort'        => 'Remote Host Port',
    'RemoteHostSubPath'     => 'Remote Host SubPath',
    'RemoteImageColours'    => 'Remote Image Colours',
    'RemoteMethod'          => 'Remote Method',
    'RemoteProtocol'        => 'Remote Protocol',
    'Rename'                => 'Nimeta ümber',
    'Replay'                => 'Kordus esitus',
    'ReplayAll'             => 'Kõik sündmused',
    'ReplayGapless'         => 'Lünkadeta sündmused',
    'ReplaySingle'          => 'Üksik sündmus',
    'ReportEventAudit'     => 'Audit Events Report',    // Added - 2018-08-30
    'Reset'                 => 'Reset',
    'ResetEventCounts'      => 'Reset Event Counts',
    'Restart'               => 'Taaskäivita',
    'Restarting'            => 'Restarting',
    'RestrictedCameraIds'   => 'Restricted Camera Ids',
    'RestrictedMonitors'    => 'Restricted Monitors',
    'ReturnDelay'           => 'Return Delay',
    'ReturnLocation'        => 'Return Location',
    'Rewind'                => 'Rewind',
    'RotateLeft'            => 'Pööra vasakule',
    'RotateRight'           => 'Pööra paremale',
    'RunLocalUpdate'        => 'Please run zmupdate.pl to update', // Added - 2011-05-25
    'RunMode'               => 'Käimis resiim',
    'RunState'              => 'Käimis olek',
    'Running'               => 'Töötab',
    'Save'                  => 'Salvesta',
    'SaveAs'                => 'Salvesta kui',
    'SaveFilter'            => 'Salvesta Filter',
    'SaveJPEGs'            => 'Save JPEGs',             // Added - 2018-08-30
    'Scale'                 => 'Skaala',
    'Score'                 => 'Skoor',
    'Secs'                  => 'Secs',
    'Sectionlength'         => 'Section length',
    'Select'                => 'Selekteeri',
    'SelectFormat'          => 'Selekteeri Formaat',          // Added - 2011-06-17
    'SelectLog'             => 'Selekteeri logi',             // Added - 2011-06-17
    'SelectMonitors'        => 'Selekteeri Monitorid',
    'SelfIntersecting'      => 'Polygon edges must not intersect',
    'Set'                   => 'Säti',
    'SetNewBandwidth'       => 'Vali uus riba laius',
    'SetPreset'             => 'Set Preset',
    'Settings'              => 'Sätted',
    'ShowFilterWindow'      => 'Näita Filtri Akent',
    'ShowTimeline'          => 'Näita Timeline',
    'SignalCheckColour'     => 'Signaali Kontroll Värv',
    'SignalCheckPoints'    => 'Signal Check Points',    // Added - 2018-08-30
    'Size'                  => 'Suurus',
    'SkinDescription'       => 'Vaheta veebilehe välimus selles arvutis', // Added - 2011-03-02
    'Sleep'                 => 'Maga',
    'SortAsc'               => 'Kasvav',
    'SortBy'                => 'Sorteeri',
    'SortDesc'              => 'Kahanev',
    'Source'                => 'Allikas',
    'SourceColours'         => 'Allika Värvid',
    'SourcePath'            => 'Allika Path',
    'SourceType'            => 'Allika tüüp',
    'Speed'                 => 'Kiirus',
    'SpeedHigh'             => 'Kiire Kiirus',
    'SpeedLow'              => 'Madal Kiirus',
    'SpeedMedium'           => 'Keskmine Kiirus',
    'SpeedTurbo'            => 'Turbo Kiirus',
    'Start'                 => 'Start',
    'State'                 => 'Olek',
    'Stats'                 => 'Statistika',
    'Status'                => 'Staatus',
    'StatusConnected'      => 'Capturing',              // Added - 2018-08-30
    'StatusNotRunning'     => 'Not Running',            // Added - 2018-08-30
    'StatusRunning'        => 'Not Capturing',          // Added - 2018-08-30
    'StatusUnknown'        => 'Unknown',                // Added - 2018-08-30
    'Step'                  => 'Samm',
    'StepBack'              => 'Samm tagasi',
    'StepForward'           => 'Samm edasi',
    'StepLarge'             => 'Suur Samm',
    'StepMedium'            => 'Keskmine Samm',
    'StepNone'              => 'Ei Samm',
    'StepSmall'             => 'Väike Samm',
    'Stills'                => 'Stills',
    'Stop'                  => 'Stop',
    'Stopped'               => 'Stopitud',
    'StorageArea'          => 'Storage Area',           // Added - 2018-08-30
    'StorageScheme'        => 'Scheme',                 // Added - 2018-08-30
    'Stream'                => 'Striim',
    'StreamReplayBuffer'    => 'Striimi Replay Pildi Puhver',
    'Submit'                => 'Submit',
    'System'                => 'Süsteem',
    'SystemLog'             => 'Süsteemi Logi',             // Added - 2011-06-16
    'TargetColorspace'     => 'Target colorspace',      // Added - 2015-04-18
    'Tele'                  => 'Tele',
    'Thumbnail'             => 'Thumbnail',
    'Tilt'                  => 'Tilt',
    'Time'                  => 'Time',
    'TimeDelta'             => 'Time Delta',
    'TimeStamp'             => 'Time Stamp',
    'Timeline'              => 'Timeline',
    'TimelineTip1'          => 'Liiguta hiir üle graafiku et näha pildi ja sündmuse detaile.',              // Added 2013.08.15.
    'TimelineTip2'          => 'Click on the coloured sections of the graph, or the image, to view the event.',              // Added 2013.08.15.
    'TimelineTip3'          => 'Click on the background to zoom in to a smaller time period based around your click.',              // Added 2013.08.15.
    'TimelineTip4'          => 'Use the controls below to zoom out or navigate back and forward through the time range.',              // Added 2013.08.15.
    'Timestamp'             => 'Timestamp',
    'TimestampLabelFormat'  => 'Timestamp Label Format',
    'TimestampLabelSize'   => 'Font Size',              // Added - 2018-08-30
    'TimestampLabelX'       => 'Timestamp Label X',
    'TimestampLabelY'       => 'Timestamp Label Y',
    'Today'                 => 'Täna',
    'Tools'                 => 'Tööriistad',
    'Total'                 => 'Summa',                  // Added - 2011-06-16
    'TotalBrScore'          => 'Summa<br/>Skoor',
    'TrackDelay'            => 'Jälgimise Viide',
    'TrackMotion'           => 'Jälgi Liikumist',
    'Triggers'              => 'Trigerid',
    'TurboPanSpeed'         => 'Turbo Pan Speed',
    'TurboTiltSpeed'        => 'Turbo Tilt Speed',
    'Type'                  => 'Tüüp',
    'Unarchive'             => 'Eemalda Arhiivist',
    'Undefined'             => 'Defineerimatta',
    'Units'                 => 'Ühikud',
    'Unknown'               => 'Tundmatu',
    'Update'                => 'Uuenda',
    'UpdateAvailable'       => 'Uuendus ZoneMinder-ile saadaval.',
    'UpdateNotNecessary'    => 'Uuendus ei ole vajalik.',
    'Updated'               => 'Uuendatud',                // Added - 2011-06-16
    'Upload'                => 'Üles laadimine',                 // Added - 2011-08-23
    'UseFilter'             => 'Kasuta Filtrit',
    'UseFilterExprsPost'    => '&nbsp;filter&nbsp;expressions', // This is used at the end of the phrase 'use N filter expressions'
    'UseFilterExprsPre'     => 'Use&nbsp;', // This is used at the beginning of the phrase 'use N filter expressions'
    'UsedPlugins'          => 'Used Plugins',           // Added - 2015-04-18
    'User'                  => 'Kasutaja',
    'Username'              => 'Kasutajanimi',
    'Users'                 => 'Kasutajad',
    'V4L'                  => 'V4L',                    // Added - 2015-04-18
    'V4LCapturesPerFrame'  => 'Captures Per Frame',     // Added - 2015-04-18
    'V4LMultiBuffer'       => 'Multi Buffering',        // Added - 2015-04-18
    'Value'                 => 'Väärtus',
    'Version'               => 'Versioon',
    'VersionIgnore'         => 'Ignoreeri See Versioon',
    'VersionRemindDay'      => 'Meenuta uuesti päeva pärast',
    'VersionRemindHour'     => 'Meenuta uuesti tunni pärast',
    'VersionRemindNever'    => 'Ära Meenuta Uuest Versioonist',
    'VersionRemindWeek'     => 'Meenuta uuesti nädalapärast',
    'Video'                 => 'Video',
    'VideoFormat'           => 'Video Formaat',
    'VideoGenFailed'        => 'Video Genereerimine Ebaõnnestus!!!',
    'VideoGenFiles'         => 'Existing Video Files',
    'VideoGenNoFiles'       => 'Ei Leitud Video Faile',
    'VideoGenParms'         => 'Video Genereerimise Parameetrid',
    'VideoGenSucceeded'     => 'Video Genereerimine Õnnestus!!!',
    'VideoSize'             => 'Video Suurus',
    'VideoWriter'          => 'Video Writer',           // Added - 2018-08-30
    'View'                  => 'Vaata',
    'ViewAll'               => 'View All',
    'ViewEvent'             => 'Vaata Sündmust',
    'ViewPaged'             => 'View Paged',
    'Wake'                  => 'Wake',
    'WarmupFrames'          => 'Warmup Frames',
    'Watch'                 => 'Vaata',
    'Web'                   => 'Veeb',
    'WebColour'             => 'Veebi värv',
    'WebSiteUrl'           => 'Website URL',            // Added - 2018-08-30
    'Week'                  => 'Nädalas',
    'White'                 => 'White',
    'WhiteBalance'          => 'White Balance',
    'Wide'                  => 'Wide',
    'X'                     => 'X',
    'X10'                   => 'X10',
    'X10ActivationString'   => 'X10 Activation String',
    'X10InputAlarmString'   => 'X10 Input Alarm String',
    'X10OutputAlarmString'  => 'X10 Output Alarm String',
    'Y'                     => 'J',
    'Yes'                   => 'Jah',
    'YouNoPerms'            => 'Sul ei ole õigusi kasutada seda ressurssi.',
    'Zone'                  => 'Tsoon',
    'ZoneAlarmColour'       => 'Alarmi Värv (Red"Punane"/Green"Roheline"/Blue"Sinine")',
    'ZoneArea'              => 'Tsooni Ala',
    'ZoneExtendAlarmFrames' => 'Extend Alarm Frame Count',
    'ZoneFilterSize'        => 'Filter Width/Height (pixels)',
    'ZoneMinMaxAlarmArea'   => 'Min/Max Alarmed Area',
    'ZoneMinMaxBlobArea'    => 'Min/Max Blob Area',
    'ZoneMinMaxBlobs'       => 'Min/Max Blobs',
    'ZoneMinMaxFiltArea'    => 'Min/Max Filtered Area',
    'ZoneMinMaxPixelThres'  => 'Min/Max Pixel Threshold (0-255)',
    'ZoneMinderLog'         => 'ZoneMinder Log',         // Added - 2011-06-17
    'ZoneOverloadFrames'    => 'Overload Frame Ignore Count',
    'Zones'                 => 'Tsoone',
    'Zoom'                  => 'Suurenda',
    'ZoomIn'                => 'Suurenda lähemale',
    'ZoomOut'               => 'Suurenda kaugemale',
);

// Complex replacements with formatting and/or placements, must be passed through sprintf
$CLANG = array(
    'CurrentLogin'          => 'Current login is \'%1$s\'',
    'EventCount'            => '%1$s %2$s', // For example '37 Events' (from Vlang below)
    'LastEvents'            => 'Last %1$s %2$s', // For example 'Last 37 Events' (from Vlang below)
    'LatestRelease'         => 'The latest release is v%1$s, you have v%2$s.',
    'MonitorCount'          => '%1$s %2$s', // For example '4 Monitors' (from Vlang below)
    'MonitorFunction'       => 'Monitor %1$s Function',
    'RunningRecentVer'      => 'You are running the most recent version of ZoneMinder, v%s.',
    'VersionMismatch'       => 'Version mismatch, system is version %1$s, database is %2$s.', // Added - 2011-05-25
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
// 'Potato' => array( 1=>'Potati', 2=>'Potaton', 3=>'Potaten' ),
//
// and the zmVlang function decides that the first form is used for counts ending in
// 0, 5-9 or 11-19 and the second form when ending in 1 etc.
//

// Variable arrays expressing plurality, see the zmVlang description above
$VLANG = array(
    'Event'                 => array( 0=>'Events', 1=>'Event', 2=>'Events' ),
    'Monitor'               => array( 0=>'Monitors', 1=>'Monitor', 2=>'Monitors' ),
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
//echo sprintf( $CLANG['MonitorCount'], count($monitors), zmVlang( $VLANG['VlangMonitor'], count($monitors) ) );

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
