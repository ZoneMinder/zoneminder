<?php
// 
// ZoneMinder web Romanian language file, $Date$
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
// ZoneMinder Romanian translation by Alex Ciobanu
//
// I have used decimal entity reference for Romanian special characters 
// (i.e. i with circumflex, s with cedilla, etc) so anybody can view this
// translation correctly no matter of the browser (local) settings.
// This translation lacks some words, terms and expressions because i do not
// know the correct Romanian equivalents for them.
// Please fell free to modify this file to make it better and get some credit
// for doing this (add your name here).

// Acest program este liber; îl puteţi redistribui şi/sau modifica
// în conformitate cu termenii Licenţei Publice Generale GNU (GPL)
// aşa cum este publicată de Free Software Foundation; fie versiunea 2
// a Licenţei, fie (la latitudinea dumneavoastră) orice versiune ulterioară.
//
// Acest program este distribuit cu speranţa că va fi util, dar FĂRĂ NICI O GARANŢIE,
// fără garanţie implicită de vandabilitate şi conformitate unui anumit scop.
// Citiţi Licenţa Publică Generală GNU pentru detalii. O traducere 
// neoficială în limba română poate fi obţinută de aici: www.roedu.net/gplro.html
//

//
setlocale( LC_ALL, 'ro_RO' ); 
//
// Simple String Replacements
$SLANG = array(
    '24BitColour'          => 'Color &#226;n 24 bi&#355;i',
    '32BitColour'          => 'Color &#226;n 32 bi&#355;i',          // Added - 2011-06-15
    '8BitGrey'             => 'Scal&#259 gri &#226;n 8 bi&#355;i',
    'Action'               => 'Action',
    'Actual'               => 'Real',
    'AddNewControl'        => 'Adaug&#259; control nou',
    'AddNewMonitor'        => 'Adaug&#259; monitor',
    'AddNewServer'         => 'Add New Server',         // Added - 2018-08-30
    'AddNewStorage'        => 'Add New Storage',        // Added - 2018-08-30
    'AddNewUser'           => 'Adaug&#259; utilizator',
    'AddNewZone'           => 'Adaug&#259; zon&#259;',
    'Alarm'                => 'Alarma',
    'AlarmBrFrames'        => 'Alarm<br/>Frames',
    'AlarmFrame'           => 'Cadru alarma',
    'AlarmFrameCount'      => 'Nr. cadru alarma',
    'AlarmLimits'          => 'Alarm Limits',
    'AlarmMaximumFPS'      => 'Alarm Maximum FPS',
    'AlarmPx'              => 'Alarm Px',
    'AlarmRGBUnset'        => 'You must set an alarm RGB colour',
    'AlarmRefImageBlendPct'=> 'Alarm Reference Image Blend %ge', // Added - 2015-04-18
    'Alert'                => 'Alert',
    'All'                  => 'Toate',
    'AnalysisFPS'          => 'Analysis FPS',           // Added - 2015-07-22
    'AnalysisUpdateDelay'  => 'Analysis Update Delay',  // Added - 2015-07-23
    'Apply'                => 'Accept',
    'ApplyingStateChange'  => 'Aplic schimbarea de stare',
    'ArchArchived'         => 'Numai arhivate',
    'ArchUnarchived'       => 'Numai nearhivate',
    'Archive'              => 'Arhive',
    'Archived'             => 'Archived',
    'Area'                 => 'Area',
    'AreaUnits'            => 'Area (px/%)',
    'AttrAlarmFrames'      => 'Cadre alarma',
    'AttrArchiveStatus'    => 'Stare arhiva',
    'AttrAvgScore'         => 'Cota medie',
    'AttrCause'            => 'Cauza',
    'AttrDiskBlocks'       => 'Disk Blocks',
    'AttrDiskPercent'      => 'Procentaj disc',
    'AttrDiskSpace'        => 'Disk Space',             // Added - 2018-08-30
    'AttrDuration'         => 'Durata',
    'AttrEndDate'          => 'End Date',               // Added - 2018-08-30
    'AttrEndDateTime'      => 'End Date/Time',          // Added - 2018-08-30
    'AttrEndTime'          => 'End Time',               // Added - 2018-08-30
    'AttrEndWeekday'       => 'End Weekday',            // Added - 2018-08-30
    'AttrFilterServer'     => 'Server Filter is Running On', // Added - 2018-08-30
    'AttrFrames'           => 'Cadre',
    'AttrId'               => 'Nr.',
    'AttrMaxScore'         => 'Cota max',
    'AttrMonitorId'        => 'Monitor nr.',
    'AttrMonitorName'      => 'Nume monitor',
    'AttrMonitorServer'    => 'Server Monitor is Running On', // Added - 2018-08-30
    'AttrName'             => 'Nume',
    'AttrNotes'            => 'Notes',
    'AttrStartDate'        => 'Start Date',             // Added - 2018-08-30
    'AttrStartDateTime'    => 'Start Date/Time',        // Added - 2018-08-30
    'AttrStartTime'        => 'Start Time',             // Added - 2018-08-30
    'AttrStartWeekday'     => 'Start Weekday',          // Added - 2018-08-30
    'AttrStateId'          => 'Run State',              // Added - 2018-08-30
    'AttrStorageArea'      => 'Storage Area',           // Added - 2018-08-30
    'AttrStorageServer'    => 'Server Hosting Storage', // Added - 2018-08-30
    'AttrSystemLoad'       => 'System Load',
    'AttrTotalScore'       => 'Cota total',
    'Auto'                 => 'Auto',
    'AutoStopTimeout'      => 'Auto Stop Timeout',
    'Available'            => 'Available',              // Added - 2009-03-31
    'AvgBrScore'           => 'Cota<br/>medie',
    'Background'           => 'Background',
    'BackgroundFilter'     => 'Run filter in background',
    'BadAlarmFrameCount'   => 'Alarm frame count must be an integer of one or more',
    'BadAlarmMaxFPS'       => 'Alarm Maximum FPS must be a positive integer or floating point value',
    'BadAnalysisFPS'       => 'Analysis FPS must be a positive integer or floating point value', // Added - 2015-07-22
    'BadAnalysisUpdateDelay'=> 'Analysis update delay must be set to an integer of zero or more', // Added - 2015-07-23
    'BadChannel'           => 'Channel must be set to an integer of zero or more',
    'BadColours'           => 'Target colour must be set to a valid value', // Added - 2011-06-15
    'BadDevice'            => 'Device must be set to a valid value',
    'BadFPSReportInterval' => 'FPS report interval buffer count must be an integer of 0 or more',
    'BadFormat'            => 'Format must be set to an integer of zero or more',
    'BadFrameSkip'         => 'Frame skip count must be an integer of zero or more',
    'BadHeight'            => 'Height must be set to a valid value',
    'BadHost'              => 'Host must be set to a valid ip address or hostname, do not include http://',
    'BadImageBufferCount'  => 'Image buffer size must be an integer of 10 or more',
    'BadLabelX'            => 'Label X co-ordinate must be set to an integer of zero or more',
    'BadLabelY'            => 'Label Y co-ordinate must be set to an integer of zero or more',
    'BadMaxFPS'            => 'Maximum FPS must be a positive integer or floating point value',
    'BadMotionFrameSkip'   => 'Motion Frame skip count must be an integer of zero or more',
    'BadNameChars'         => 'Denumirea poate contine doar caractere alfanumerice, cratima si underline.',
    'BadPalette'           => 'Palette must be set to a valid value', // Added - 2009-03-31
    'BadPath'              => 'Path must be set to a valid value',
    'BadPort'              => 'Port must be set to a valid number',
    'BadPostEventCount'    => 'Post event image count must be an integer of zero or more',
    'BadPreEventCount'     => 'Pre event image count must be at least zero, and less than image buffer size',
    'BadRefBlendPerc'      => 'Reference blend percentage must be a positive integer',
    'BadSectionLength'     => 'Section length must be an integer of 30 or more',
    'BadSignalCheckColour' => 'Signal check colour must be a valid RGB colour string',
    'BadSourceType'        => 'Source Type \"Web Site\" requires the Function to be set to \"Monitor\"', // Added - 2018-08-30
    'BadStreamReplayBuffer'=> 'Stream replay buffer must be an integer of zero or more',
    'BadWarmupCount'       => 'Warmup frames must be an integer of zero or more',
    'BadWebColour'         => 'Web colour must be a valid web colour string',
    'BadWebSitePath'       => 'Please enter a complete website url, including the http:// or https:// prefix.', // Added - 2018-08-30
    'BadWidth'             => 'Width must be set to a valid value',
    'Bandwidth'            => 'La&#355;ime de band&#259;',
    'BandwidthHead'         => 'Bandwidth',	// This is the end of the bandwidth status on the top of the console, different in many language due to phrasing
    'BlobPx'               => 'Blob Px',
    'BlobSizes'            => 'Blob Sizes',
    'Blobs'                => 'Blobs',
    'Brightness'           => 'Luminozitate',
    'Buffer'               => 'Buffer',                 // Added - 2015-04-18
    'Buffers'              => 'Zon&#259;&nbsp;tampon',
    'CSSDescription'       => 'Change the default css for this computer', // Added - 2015-04-18
    'CanAutoFocus'         => 'Focalizare automat&#259;',
    'CanAutoGain'          => 'Can Auto Gain',
    'CanAutoIris'          => 'Can Auto Iris',
    'CanAutoWhite'         => 'Balans alb automat',
    'CanAutoZoom'          => 'Are auto zoom',
    'CanFocus'             => 'Focalizare',
    'CanFocusAbs'          => 'Focalizare absolut&#259;',
    'CanFocusCon'          => 'Focalizare continu&#259;',
    'CanFocusRel'          => 'Focalizare relativ&#259;',
    'CanGain'              => 'Can Gain ',
    'CanGainAbs'           => 'Can Gain Absolute',
    'CanGainCon'           => 'Can Gain Continuous',
    'CanGainRel'           => 'Can Gain Relative',
    'CanIris'              => 'Can Iris',
    'CanIrisAbs'           => 'Can Iris Absolute',
    'CanIrisCon'           => 'Can Iris Continuous',
    'CanIrisRel'           => 'Can Iris Relative',
    'CanMove'              => 'Dinamic',
    'CanMoveAbs'           => 'Mi&#351;care absolut&#259;',
    'CanMoveCon'           => 'Mi&#351;care continu&#259;',
    'CanMoveDiag'          => 'Mi&#351;care diagonal&#259;',
    'CanMoveMap'           => 'Can Move Mapped',
    'CanMoveRel'           => 'Mi&#351;care relativ&#259;',
    'CanPan'               => 'Rotativ' ,
    'CanReset'             => 'Can Reset',
	'CanReboot'             => 'Can Reboot',
    'CanSetPresets'        => 'Can Set Presets',
    'CanSleep'             => 'Can Sleep',
    'CanTilt'              => 'Se poate &#238;nclina',
    'CanWake'              => 'Can Wake',
    'CanWhite'             => 'Balans alb',
    'CanWhiteAbs'          => 'Balans alb absolut',
    'CanWhiteBal'          => 'Balans alb',
    'CanWhiteCon'          => 'Balans alb continuu',
    'CanWhiteRel'          => 'Balans alb relativ',
    'CanZoom'              => 'Zoom',
    'CanZoomAbs'           => 'Zoom Absolut',
    'CanZoomCon'           => 'Zoom Continuu',
    'CanZoomRel'           => 'Zoom Relativ',
    'Cancel'               => 'Renun&#355;',
    'CancelForcedAlarm'    => 'Renunta Fortat Alarma',
    'CaptureHeight'        => '&#206n&#259;l&#355;ime captur&#259;',
    'CaptureMethod'        => 'Capture Method',         // Added - 2009-02-08
    'CapturePalette'       => 'Palet&#259; captur&#259;',
    'CaptureResolution'    => 'Capture Resolution',     // Added - 2015-04-18
    'CaptureWidth'         => 'L&#259;&#355;ime captur&#259;',
    'Cause'                => 'Cauza',
    'CheckMethod'          => 'Alarm Check Method',
    'ChooseDetectedCamera' => 'Choose Detected Camera', // Added - 2009-03-31
    'ChooseFilter'         => 'Alege filtru',
    'ChooseLogFormat'      => 'Choose a log format',    // Added - 2011-06-17
    'ChooseLogSelection'   => 'Choose a log selection', // Added - 2011-06-17
    'ChoosePreset'         => 'Choose Preset',
    'Clear'                => 'Clear',                  // Added - 2011-06-16
    'CloneMonitor'         => 'Clone',                  // Added - 2018-08-30
    'Close'                => '&#206;nchide',
    'Colour'               => 'Culoare',
    'Command'              => 'Comanda',
    'Component'            => 'Component',              // Added - 2011-06-16
    'ConcurrentFilter'     => 'Run filter concurrently', // Added - 2018-08-30
    'Config'               => 'Config',
    'ConfiguredFor'        => 'Configurat pentru',
    'ConfirmDeleteEvents'  => 'Are you sure you wish to delete the selected events?',
    'ConfirmPassword'      => 'Confirm parola',
    'ConjAnd'              => '&#351;i',
    'ConjOr'               => 'sau',
    'Console'              => 'Consola',
    'ContactAdmin'         => 'Va rugam contactati administratorul pentru detalii.',
    'Continue'             => 'Continua&#259;',
    'Contrast'             => 'Contrast',
    'Control'              => 'Control',
    'ControlAddress'       => 'Adres&#259; control',
    'ControlCap'           => 'Posibilitate control',
    'ControlCaps'          => 'Posibilit&#259;&#355;i control',
    'ControlDevice'        => 'Dispozitiv control',
    'ControlType'          => 'Tip control',
    'Controllable'         => 'Controlabil',
    'Current'              => 'Current',                // Added - 2015-04-18
    'Cycle'                => 'Ciclu',
    'CycleWatch'           => 'Vizual. ciclu',
    'DateTime'             => 'Date/Time',              // Added - 2011-06-16
    'Day'                  => 'Zi',
    'Debug'                => 'Debug',
    'DefaultRate'          => 'Default Rate',
    'DefaultScale'         => 'Default Scale',
    'DefaultView'          => 'Default View',
    'Deinterlacing'        => 'Deinterlacing',          // Added - 2015-04-18
    'Delay'                => 'Delay',                  // Added - 2015-04-18
    'Delete'               => '&#350;terge',
    'DeleteAndNext'        => '&#350;terge &amp; Urm&#259;tor',
    'DeleteAndPrev'        => '&#350;terge &amp; Precedent',
    'DeleteSavedFilter'    => '&#350;terge filtrul salvat',
    'Description'          => 'Descriere',
    'DetectedCameras'      => 'Detected Cameras',       // Added - 2009-03-31
    'DetectedProfiles'     => 'Detected Profiles',      // Added - 2015-04-18
    'Device'               => 'Device',                 // Added - 2009-02-08
    'DeviceChannel'        => 'Canal dispozitiv',
    'DeviceFormat'         => 'Format dispozitiv',
    'DeviceNumber'         => 'Num&#259;r dispozitiv',
    'DevicePath'           => 'Device Path',
    'Devices'              => 'Devices',
    'Dimensions'           => 'Dimensiuni',
    'DisableAlarms'        => 'Disable Alarms',
    'Disk'                 => 'Disc',
    'Display'              => 'Display',                // Added - 2011-01-30
    'Displaying'           => 'Displaying',             // Added - 2011-06-16
    'DoNativeMotionDetection'=> 'Do Native Motion Detection',
    'Donate'               => 'Please Donate',
    'DonateAlready'        => 'No, I\'ve already donated',
    'DonateEnticement'     => 'You\'ve been running ZoneMinder for a while now and hopefully are finding it a useful addition to your home or workplace security. Although ZoneMinder is, and will remain, free and open source, it costs money to develop and support. If you would like to help support future development and new features then please consider donating. Donating is, of course, optional but very much appreciated and you can donate as much or as little as you like.<br><br>If you would like to donate please select the option below or go to https://zoneminder.com/donate/ in your browser.<br><br>Thank you for using ZoneMinder and don\'t forget to visit the forums on ZoneMinder.com for support or suggestions about how to make your ZoneMinder experience even better.',
    'DonateRemindDay'      => 'Not yet, remind again in 1 day',
    'DonateRemindHour'     => 'Not yet, remind again in 1 hour',
    'DonateRemindMonth'    => 'Not yet, remind again in 1 month',
    'DonateRemindNever'    => 'No, I don\'t want to donate, never remind',
    'DonateRemindWeek'     => 'Not yet, remind again in 1 week',
    'DonateYes'            => 'Yes, I\'d like to donate now',
    'Download'             => 'Download',
    'DownloadVideo'        => 'Download Video',         // Added - 2018-08-30
    'DuplicateMonitorName' => 'Duplicate Monitor Name', // Added - 2009-03-31
    'Duration'             => 'Durata',
    'Edit'                 => 'Modific',
    'EditLayout'           => 'Edit Layout',            // Added - 2018-08-30
    'Email'                => 'Email',
    'EnableAlarms'         => 'Enable Alarms',
    'Enabled'              => 'Activ',
    'EnterNewFilterName'   => 'Introduceti denumire filtru',
    'Error'                => 'Eroare',
    'ErrorBrackets'        => 'Eroare, va rugam asigurati-va ca toate parantezele se inchid',
    'ErrorValidValue'      => 'Eroare, va rugam verificati validitatea numelor termenilor',
    'Etc'                  => 'etc',
    'Event'                => 'Eveniment',
    'EventFilter'          => 'Filtru eveniment',
    'EventId'              => 'Nr. eveniment',
    'EventName'            => 'Nume eveniment',
    'EventPrefix'          => 'Prefix eveniment',
    'Events'               => 'Evenim.',
    'Exclude'              => 'Exclude',
    'Execute'              => 'Execute',
    'Exif'                 => 'Embed EXIF data into image', // Added - 2018-08-30
    'Export'               => 'Export',
    'ExportDetails'        => 'Export Event Details',
    'ExportFailed'         => 'Export Failed',
    'ExportFormat'         => 'Export File Format',
    'ExportFormatTar'      => 'Tar',
    'ExportFormatZip'      => 'Zip',
    'ExportFrames'         => 'Export Frame Details',
    'ExportImageFiles'     => 'Export Image Files',
    'ExportLog'            => 'Export Log',             // Added - 2011-06-17
    'ExportMiscFiles'      => 'Export Other Files (if present)',
    'ExportOptions'        => 'Export Options',
    'ExportSucceeded'      => 'Export Succeeded',       // Added - 2009-02-08
    'ExportVideoFiles'     => 'Export Video Files (if present)',
    'Exporting'            => 'Exporting',
    'FPS'                  => 'FPS',
    'FPSReportInterval'    => 'Interval raport FPS',
    'FTP'                  => 'FTP',
    'Far'                  => 'Far',
    'FastForward'          => 'Fast Forward',
    'Feed'                 => 'Feed',
    'Ffmpeg'               => 'Ffmpeg',                 // Added - 2009-02-08
    'File'                 => 'File',
    'Filter'               => 'Filter',                 // Added - 2015-04-18
    'FilterArchiveEvents'  => 'Arhiveaz&#259; toate rezultatele',
    'FilterDeleteEvents'   => '&#350;terge toate rezultatele',
    'FilterEmailEvents'    => 'Trimite email ale tuturor rezultatelor',
    'FilterExecuteEvents'  => 'Execut&#259; comanda pentru toate rezultatele',
    'FilterLog'            => 'Filter log',             // Added - 2015-04-18
    'FilterMessageEvents'  => 'Trimite mesaj pentru toate rezultatele',
    'FilterMoveEvents'     => 'Move all matches',       // Added - 2018-08-30
    'FilterPx'             => 'Filter Px',
    'FilterUnset'          => 'You must specify a filter width and height',
    'FilterUpdateDiskSpace'=> 'Update used disk space', // Added - 2018-08-30
    'FilterUploadEvents'   => '&#206;ncarc&#259; toate rezultatele',
    'FilterVideoEvents'    => 'Create video for all matches',
    'Filters'              => 'Filters',
    'First'                => 'First',
    'FlippedHori'          => 'Flipped Horizontally',
    'FlippedVert'          => 'Flipped Vertically',
    'FnMocord'              => 'Mocord',            // Added 2013.08.16.
    'FnModect'              => 'Modect',            // Added 2013.08.16.
    'FnMonitor'             => 'Monitor',            // Added 2013.08.16.
    'FnNodect'              => 'Nodect',            // Added 2013.08.16.
    'FnNone'                => 'None',            // Added 2013.08.16.
    'FnRecord'              => 'Record',            // Added 2013.08.16.
    'Focus'                => 'Focalizare',
    'ForceAlarm'           => 'Alarm&#259; for&#355;at&#259;',
    'Format'               => 'Format',
    'Frame'                => 'Cadru',
    'FrameId'              => 'Nr. cadru',
    'FrameRate'            => 'Frecv. cadre',
    'FrameSkip'            => 'Omite cadre',
    'Frames'               => 'Cadre',
    'Func'                 => 'Func',
    'Function'             => 'Func&#355;ie',
    'Gain'                 => 'Gain',
    'General'              => 'General',
    'GenerateDownload'     => 'Generate Download',      // Added - 2018-08-30
    'GenerateVideo'        => 'Genereaz&#259; video',
    'GeneratingVideo'      => 'Generez video',
    'GoToZoneMinder'       => 'Du-te la ZoneMinder.com',
    'Grey'                 => 'Gri',
    'Group'                => 'Group',
    'Groups'               => 'Grupuri',
    'HasFocusSpeed'        => 'Vitez&#259; focalizare',
    'HasGainSpeed'         => 'Has Gain Speed',
    'HasHomePreset'        => 'Has Home Preset',
    'HasIrisSpeed'         => 'Has Iris Speed',
    'HasPanSpeed'          => 'Vitez&#259; rotire',
    'HasPresets'           => 'Are Preset&#259;ri',
    'HasTiltSpeed'         => 'Vitez&#259; &#238;nclinare',
    'HasTurboPan'          => 'Rotire turbo',
    'HasTurboTilt'         => '&#206;nclinare turbo',
    'HasWhiteSpeed'        => 'Vitez&#259; balans alb',
    'HasZoomSpeed'         => 'Vitez&#259; zoom',
    'High'                 => 'Mare',
    'HighBW'               => 'B/W&nbsp;mare',
    'Home'                 => 'Home',
    'Hostname'             => 'Hostname',               // Added - 2018-08-30
    'Hour'                 => 'Ora',
    'Hue'                  => 'Nuan&#355;&#259;',
    'Id'                   => 'Nr.',
    'Idle'                 => 'Oprit',
    'Ignore'               => 'Ignor',
    'Image'                => 'Imagine',
    'ImageBufferSize'      => 'Zon&#259; tampon imagine (cadre)',
    'Images'               => 'Images',
    'In'                   => 'In',
    'Include'              => 'Includ',
    'Inverted'             => 'Invers&#259;',
    'Iris'                 => 'Iris',
    'KeyString'            => 'Key String',
    'Label'                => 'Label',
    'Language'             => 'Limb&#259;',
    'Last'                 => 'Ultim',
    'Layout'               => 'Layout',                 // Added - 2009-02-08
    'Level'                => 'Level',                  // Added - 2011-06-16
    'Libvlc'               => 'Libvlc',
    'LimitResultsPost'     => 'rezultate',
    'LimitResultsPre'      => 'Limiteaz&#259; la primele',
    'Line'                 => 'Line',                   // Added - 2011-06-16
    'LinkedMonitors'       => 'Linked Monitors',
    'List'                 => 'List',
    'ListMatches'          => 'List Matches',           // Added - 2018-08-30
    'Load'                 => 'Load',
    'Local'                => 'Local',
    'Log'                  => 'Log',                    // Added - 2011-06-16
    'LoggedInAs'           => 'E&#351;ti conectat ca',
    'Logging'              => 'Logging',                // Added - 2011-06-16
    'LoggingIn'            => 'Logare',
    'Login'                => 'Login',
    'Logout'               => 'Ie&#351;ire',
    'Logs'                 => 'Logs',                   // Added - 2011-06-17
    'Low'                  => 'Redusa',
    'LowBW'                => 'B/W&nbsp;redus',
    'Main'                 => 'Main',
    'Man'                  => 'Man',
    'Manual'               => 'Manual',
    'Mark'                 => 'Select',
    'Max'                  => 'Max',
    'MaxBandwidth'         => 'Max Bandwidth',
    'MaxBrScore'           => 'Cota<br/>max',
    'MaxFocusRange'        => 'Raza focalizare max',
    'MaxFocusSpeed'        => 'Vitez&#259; focalizare max',
    'MaxFocusStep'         => 'Pas focalizare max',
    'MaxGainRange'         => 'Max Gain Range',
    'MaxGainSpeed'         => 'Max Gain Speed',
    'MaxGainStep'          => 'Max Gain Step',
    'MaxIrisRange'         => 'Max Iris Range',
    'MaxIrisSpeed'         => 'Max Iris Speed',
    'MaxIrisStep'          => 'Max Iris Step',
    'MaxPanRange'          => 'Raza max de rotire',
    'MaxPanSpeed'          => 'Vitez&#259; rotire max',
    'MaxPanStep'           => 'Pas rotire max',
    'MaxTiltRange'         => 'Raza &#238;nclinare max',
    'MaxTiltSpeed'         => 'Vitez&#239; &#238;nclinare max',
    'MaxTiltStep'          => 'Pas &#238;nclinare max',
    'MaxWhiteRange'        => 'Raza balans alb max',
    'MaxWhiteSpeed'        => 'Vitez&#259; balans alb man',
    'MaxWhiteStep'         => 'Pas balans alb max',
    'MaxZoomRange'         => 'Raza zoom max',
    'MaxZoomSpeed'         => 'Vitez&#259; zoom max',
    'MaxZoomStep'          => 'Pas zoom max',
    'MaximumFPS'           => 'FPS max',
    'Medium'               => 'Medie',
    'MediumBW'             => 'B/W&nbsp;mediu',
    'Message'              => 'Message',                // Added - 2011-06-16
    'MinAlarmAreaLtMax'    => 'Minimum alarm area should be less than maximum',
    'MinAlarmAreaUnset'    => 'You must specify the minimum alarm pixel count',
    'MinBlobAreaLtMax'     => 'Minimum blob area should be less than maximum',
    'MinBlobAreaUnset'     => 'You must specify the minimum blob pixel count',
    'MinBlobLtMinFilter'   => 'Minimum blob area should be less than or equal to minimum filter area',
    'MinBlobsLtMax'        => 'Minimum blobs should be less than maximum',
    'MinBlobsUnset'        => 'You must specify the minimum blob count',
    'MinFilterAreaLtMax'   => 'Minimum filter area should be less than maximum',
    'MinFilterAreaUnset'   => 'You must specify the minimum filter pixel count',
    'MinFilterLtMinAlarm'  => 'Minimum filter area should be less than or equal to minimum alarm area',
    'MinFocusRange'        => 'Raza focalizare min',
    'MinFocusSpeed'        => 'Vitez&#259; focalizare min',
    'MinFocusStep'         => 'Pas focalizare min',
    'MinGainRange'         => 'Min Gain Range',
    'MinGainSpeed'         => 'Min Gain Speed',
    'MinGainStep'          => 'Min Gain Step',
    'MinIrisRange'         => 'Min Iris Range',
    'MinIrisSpeed'         => 'Min Iris Speed',
    'MinIrisStep'          => 'Min Iris Step',
    'MinPanRange'          => 'Raza min de rotire',
    'MinPanSpeed'          => 'Vitez&#259; rotire min',
    'MinPanStep'           => 'Pas rotire min',
    'MinPixelThresLtMax'   => 'Minimum pixel threshold should be less than maximum',
    'MinPixelThresUnset'   => 'You must specify a minimum pixel threshold',
    'MinTiltRange'         => 'Raza &#238;nclinare min',
    'MinTiltSpeed'         => 'Vitez&#239; &#238;nclinare min',
    'MinTiltStep'          => 'Pas &#238;nclinare min',
    'MinWhiteRange'        => 'Raza balans alb min',
    'MinWhiteSpeed'        => 'Vitez&#259; balans alb min',
    'MinWhiteStep'         => 'Pas balans alb min',
    'MinZoomRange'         => 'Raza zoom min',
    'MinZoomSpeed'         => 'Vitez&#259; zoom min',
    'MinZoomStep'          => 'Pas zoom min',
    'Misc'                 => 'Divers',
    'Mode'                 => 'Mode',                   // Added - 2015-04-18
    'Monitor'              => 'Monitor',
    'MonitorIds'           => 'Nr.&nbsp;Monitor',
    'MonitorPreset'        => 'Monitor Preset',
    'MonitorPresetIntro'   => 'Select an appropriate preset from the list below.<br><br>Please note that this may overwrite any values you already have configured for this monitor.<br><br>',
    'MonitorProbe'         => 'Monitor Probe',          // Added - 2009-03-31
    'MonitorProbeIntro'    => 'The list below shows detected analog and network cameras and whether they are already being used or available for selection.<br/><br/>Select the desired entry from the list below.<br/><br/>Please note that not all cameras may be detected and that choosing a camera here may overwrite any values you already have configured for the current monitor.<br/><br/>', // Added - 2009-03-31
    'Monitors'             => 'Monitoare',
    'Montage'              => 'Montage',
    'MontageReview'        => 'Montage Review',         // Added - 2018-08-30
    'Month'                => 'Luna',
    'More'                 => 'More',                   // Added - 2011-06-16
    'MotionFrameSkip'      => 'Motion Frame Skip',
    'Move'                 => 'Mi&#351;care',
    'Mtg2widgrd'           => '2-wide grid',              // Added 2013.08.15.
    'Mtg3widgrd'           => '3-wide grid',              // Added 2013.08.15.
    'Mtg3widgrx'           => '3-wide grid, scaled, enlarge on alarm',              // Added 2013.08.15.
    'Mtg4widgrd'           => '4-wide grid',              // Added 2013.08.15.
    'MtgDefault'           => 'Default',              // Added 2013.08.15.
    'MustBeGe'             => 'trebuie sa fie mai mare sau egal cu',
    'MustBeLe'             => 'trebuie sa fie mai mic sau egal cu',
    'MustConfirmPassword'  => 'Trebuie sa confirmati parola',
    'MustSupplyPassword'   => 'Trebuie sa introduceti parola', 
    'MustSupplyUsername'   => 'Trebuie sa introduceti utilizator', 
    'Name'                 => 'Denumire',
    'Near'                 => 'Near',
    'Network'              => 'Re&#355;ea',
    'New'                  => 'Nou',
    'NewGroup'             => 'Grup nou',
    'NewLabel'             => 'New Label',
    'NewPassword'          => 'Parola nou&#259;',
    'NewState'             => 'Stare nou&#259;',
    'NewUser'              => 'Utilizator nou',
    'Next'                 => 'Urmator',
    'No'                   => 'Nu',
    'NoDetectedCameras'    => 'No Detected Cameras',    // Added - 2009-03-31
    'NoDetectedProfiles'   => 'No Detected Profiles',   // Added - 2018-08-30
    'NoFramesRecorded'     => 'Nu exista cadre inregistrate pentru acest eveniment.',
    'NoGroup'              => 'No Group',
    'NoSavedFilters'       => 'LipsaFiltruSalvat',
    'NoStatisticsRecorded' => 'Nu exista statistici pentru acest eveniment/cadru.',
    'None'                 => 'Nimic',
    'NoneAvailable'        => 'Indisponibil',
    'Normal'               => 'Normal',
    'Notes'                => 'Notes',
    'NumPresets'           => 'Num Presets',
    'Off'                  => 'Off',
    'On'                   => 'On',
    'OnvifCredentialsIntro'=> 'Please supply user name and password for the selected camera.<br/>If no user has been created for the camera then the user given here will be created with the given password.<br/><br/>', // Added - 2015-04-18
    'OnvifProbe'           => 'ONVIF',                  // Added - 2015-04-18
    'OnvifProbeIntro'      => 'The list below shows detected ONVIF cameras and whether they are already being used or available for selection.<br/><br/>Select the desired entry from the list below.<br/><br/>Please note that not all cameras may be detected and that choosing a camera here may overwrite any values you already have configured for the current monitor.<br/><br/>', // Added - 2015-04-18
    'OpEq'                 => 'egal cu',
    'OpGt'                 => 'mai mare ca',
    'OpGtEq'               => 'mai mare sau egal cu',
    'OpIn'                 => 'in set',
    'OpIs'                 => 'is',                     // Added - 2018-08-30
    'OpIsNot'              => 'is not',                 // Added - 2018-08-30
    'OpLt'                 => 'mai mic dec&#226;t',
    'OpLtEq'               => 'mai mic sau egal cu',
    'OpMatches'            => 'matches',
    'OpNe'                 => 'diferit de',
    'OpNotIn'              => 'not in set',
    'OpNotMatches'         => 'nu se potriveste',
    'Open'                 => 'Deschide',
    'OptionHelp'           => 'OptionHelp',
    'OptionRestartWarning' => 'Aceste schimbari nu se aplica in timpul rularii.\n Dupa ce ati terminat setarile va rugam reporniti ZoneMinder.',
    'OptionalEncoderParam' => 'Optional Encoder Parameters', // Added - 2018-08-30
    'Options'              => 'Op&#355;iuni',
    'OrEnterNewName'       => 'sau denumire nou&#259;',
    'Order'                => 'Order',
    'Orientation'          => 'Orientare',
    'Out'                  => 'Out',
    'OverwriteExisting'    => 'Suprascrie existent',
    'Paged'                => 'Paginat',
    'Pan'                  => 'Rotire',
    'PanLeft'              => 'Pan Left',
    'PanRight'             => 'Pan Right',
    'PanTilt'              => 'Rotire/&#206;nclinare',
    'Parameter'            => 'Parametru',
    'Password'             => 'Parol&#259;',
    'PasswordsDifferent'   => 'Cele dou&#259; parole difer&#259;.',
    'Paths'                => 'Cale',
    'Pause'                => 'Pause',
    'Phone'                => 'Phone',
    'PhoneBW'              => 'Phone&nbsp;B/W',
    'Pid'                  => 'PID',                    // Added - 2011-06-16
    'PixelDiff'            => 'Pixel Diff',
    'Pixels'               => 'Pixeli',
    'Play'                 => 'Play',
    'PlayAll'              => 'Play All',
    'PleaseWait'           => 'V&#259; rug&#259;m a&#351;tepta&#355;i',
    'Plugins'              => 'Plugins',
    'Point'                => 'Point',
    'PostEventImageBuffer' => 'Zona tampon post eveniment',
    'PreEventImageBuffer'  => 'Zona tampon pre eveniment',
    'PreserveAspect'       => 'Preserve Aspect Ratio',
    'Preset'               => 'Presetare',
    'Presets'              => 'Preset&#259;ri',
    'Prev'                 => 'Prev',
    'Probe'                => 'Probe',                  // Added - 2009-03-31
    'ProfileProbe'         => 'Stream Probe',           // Added - 2015-04-18
    'ProfileProbeIntro'    => 'The list below shows the existing stream profiles of the selected camera .<br/><br/>Select the desired entry from the list below.<br/><br/>Please note that ZoneMinder cannot configure additional profiles and that choosing a camera here may overwrite any values you already have configured for the current monitor.<br/><br/>', // Added - 2015-04-18
    'Progress'             => 'Progress',               // Added - 2015-04-18
    'Protocol'             => 'Protocol',
    'RTSPDescribe'         => 'Use RTSP Response Media URL', // Added - 2018-08-30
    'RTSPTransport'        => 'RTSP Transport Protocol', // Added - 2018-08-30
    'Rate'                 => 'Rate',
    'Real'                 => 'Real',
    'RecaptchaWarning'     => 'Your reCaptcha secret key is invalid. Please correct it, or reCaptcha will not work', // Added - 2018-08-30
    'Record'               => '&#206;nregistrare',
    'RecordAudio'          => 'Whether to store the audio stream when saving an event.', // Added - 2018-08-30
    'RefImageBlendPct'     => 'Combinare imagine referinta(%)',
    'Refresh'              => 'Actualizeaz&#259;',
    'Remote'               => 'Remote',
    'RemoteHostName'       => 'Remote Host Name',
    'RemoteHostPath'       => 'Remote Host Path',
    'RemoteHostPort'       => 'Remote Host Port',
    'RemoteHostSubPath'    => 'Remote Host SubPath',    // Added - 2009-02-08
    'RemoteImageColours'   => 'Remote Image Colours',
    'RemoteMethod'         => 'Remote Method',          // Added - 2009-02-08
    'RemoteProtocol'       => 'Remote Protocol',        // Added - 2009-02-08
    'Rename'               => 'Rename',
    'Replay'               => 'Replay',
    'ReplayAll'            => 'All Events',
    'ReplayGapless'        => 'Gapless Events',
    'ReplaySingle'         => 'Single Event',
    'ReportEventAudit'     => 'Audit Events Report',    // Added - 2018-08-30
    'Reset'                => 'Reset',
    'ResetEventCounts'     => 'Reset Event Counts',
    'Restart'              => 'Reporne&#351;te',
    'Restarting'           => 'Repornesc',
    'RestrictedCameraIds'  => 'Restricted Camera Ids',
    'RestrictedMonitors'   => 'Restricted Monitors',
    'ReturnDelay'          => 'Return Delay',
    'ReturnLocation'       => 'Return Location',
    'Rewind'               => 'Rewind',
    'RotateLeft'           => 'Rotire st&#226;nga',
    'RotateRight'          => 'Rotire dreapta',
    'RunLocalUpdate'       => 'Please run zmupdate.pl to update', // Added - 2011-05-25
    'RunMode'              => 'Mod rulare',
    'RunState'             => 'Stare de rulare',
    'Running'              => 'Ruleaz&#259;',
    'Save'                 => 'Salvez',
    'SaveAs'               => 'Salveaz&#259; ca',
    'SaveFilter'           => 'Salveaz&#259; filtru',
    'SaveJPEGs'            => 'Save JPEGs',             // Added - 2018-08-30
    'Scale'                => 'Scara',
    'Score'                => 'Cota',
    'Secs'                 => 'Sec',
    'Sectionlength'        => 'Lungime sec&#355;iune',
    'Select'               => 'Select',
    'SelectFormat'         => 'Select Format',          // Added - 2011-06-17
    'SelectLog'            => 'Select Log',             // Added - 2011-06-17
    'SelectMonitors'       => 'Select Monitors',
    'SelfIntersecting'     => 'Polygon edges must not intersect',
    'Set'                  => 'Set',
    'SetNewBandwidth'      => 'Setare la&#355;ime de band&#259; nou&#259;',
    'SetPreset'            => 'Set Preset',
    'Settings'             => 'Set&#259;ri',
    'ShowFilterWindow'     => 'Fereastra filtre',
    'ShowTimeline'         => 'Show Timeline',
    'SignalCheckColour'    => 'Signal Check Colour',
    'SignalCheckPoints'    => 'Signal Check Points',    // Added - 2018-08-30
    'Size'                 => 'Size',
    'SkinDescription'      => 'Change the default skin for this computer', // Added - 2011-01-30
    'Sleep'                => 'Sleep',
    'SortAsc'              => 'Cres',
    'SortBy'               => 'Sorteaz&#259; dup&#259;',
    'SortDesc'             => 'Desc',
    'Source'               => 'Sursa',
    'SourceColours'        => 'Source Colours',         // Added - 2009-02-08
    'SourcePath'           => 'Source Path',            // Added - 2009-02-08
    'SourceType'           => 'Tipul sursei',
    'Speed'                => 'Vitez&#259;',
    'SpeedHigh'            => 'Vitez&#259; mare',
    'SpeedLow'             => 'Vitez&#259; mic&#259;',
    'SpeedMedium'          => 'Vitez&#259; medie',
    'SpeedTurbo'           => 'Vitez&#259; turbo',
    'Start'                => 'Porne&#351;te',
    'State'                => 'Stare',
    'Stats'                => 'Statistici',
    'Status'               => 'Stare',
    'StatusConnected'      => 'Capturing',              // Added - 2018-08-30
    'StatusNotRunning'     => 'Not Running',            // Added - 2018-08-30
    'StatusRunning'        => 'Not Capturing',          // Added - 2018-08-30
    'StatusUnknown'        => 'Unknown',                // Added - 2018-08-30
    'Step'                 => 'Step',
    'StepBack'             => 'Step Back',
    'StepForward'          => 'Step Forward',
    'StepLarge'            => 'Large Step',
    'StepMedium'           => 'Medium Step',
    'StepNone'             => 'No Step',
    'StepSmall'            => 'Small Step',
    'Stills'               => 'Statice',
    'Stop'                 => 'Opre&#351;te',
    'Stopped'              => 'Oprit',
    'StorageArea'          => 'Storage Area',           // Added - 2018-08-30
    'StorageScheme'        => 'Scheme',                 // Added - 2018-08-30
    'Stream'               => 'Flux',
    'StreamReplayBuffer'   => 'Stream Replay Image Buffer',
    'Submit'               => 'Trimite',
    'System'               => 'Sistem',
    'SystemLog'            => 'System Log',             // Added - 2011-06-16
    'TargetColorspace'     => 'Target colorspace',      // Added - 2015-04-18
    'Tele'                 => 'Tele',
    'Thumbnail'            => 'Miniatur&#259;',
    'Tilt'                 => '&#206;nclinare',
    'Time'                 => 'Timp',
    'TimeDelta'            => 'Time Delta',
    'TimeStamp'            => 'Format timp',
    'Timeline'             => 'Timeline',
    'TimelineTip1'          => 'Pass your mouse over the graph to view a snapshot image and event details.',              // Added 2013.08.15.
    'TimelineTip2'          => 'Click on the coloured sections of the graph, or the image, to view the event.',              // Added 2013.08.15.
    'TimelineTip3'          => 'Click on the background to zoom in to a smaller time period based around your click.',              // Added 2013.08.15.
    'TimelineTip4'          => 'Use the controls below to zoom out or navigate back and forward through the time range.',              // Added 2013.08.15.
    'Timestamp'            => 'Format&nbsp;timp',
    'TimestampLabelFormat' => 'Format eticheta format timp',
    'TimestampLabelSize'   => 'Font Size',              // Added - 2018-08-30
    'TimestampLabelX'      => 'Format timp eticheta X',
    'TimestampLabelY'      => 'Format timp eticheta Y',
    'Today'                => 'Azi',
    'Tools'                => 'Unelte',
    'Total'                => 'Total',                  // Added - 2011-06-16
    'TotalBrScore'         => 'Cota<br/>total',
    'TrackDelay'           => 'Track Delay',
    'TrackMotion'          => 'Track Motion',
    'Triggers'             => 'Declan&#351;ator',
    'TurboPanSpeed'        => 'Vitez&#259; rotire turbo',
    'TurboTiltSpeed'       => 'Vitez&#259; &#238;nclinare turbo',
    'Type'                 => 'Tip',
    'Unarchive'            => 'Dezarhivez',
    'Undefined'            => 'Undefined',              // Added - 2009-02-08
    'Units'                => 'Unit&#259;&#355;i',
    'Unknown'              => 'Necunoscut',
    'Update'               => 'Update',
    'UpdateAvailable'      => 'Sunt disponibile actualiz&#259;ri ZoneMinder.',
    'UpdateNotNecessary'   => 'Actulizarea nu este necesar&#259;.',
    'Updated'              => 'Updated',                // Added - 2011-06-16
    'Upload'               => 'Upload',                 // Added - 2011-08-23
    'UseFilter'            => 'Folose&#351;te filtru',
    'UseFilterExprsPost'   => '&nbsp;expresii&nbsp;de&nbsp;filtrare ', 
    'UseFilterExprsPre'    => 'Folose&#351;te&nbsp;', 
    'UsedPlugins'	   => 'Used Plugins',
    'User'                 => 'Utilizator',
    'Username'             => 'Nume',
    'Users'                => 'Utilizatori',
    'V4L'                  => 'V4L',                    // Added - 2015-04-18
    'V4LCapturesPerFrame'  => 'Captures Per Frame',     // Added - 2015-04-18
    'V4LMultiBuffer'       => 'Multi Buffering',        // Added - 2015-04-18
    'Value'                => 'Valoare',
    'Version'              => 'Versiune',
    'VersionIgnore'        => 'Ignor&#259; aceast&#259; versiune',
    'VersionRemindDay'     => 'Aminte&#351;te-mi peste 1 zi',
    'VersionRemindHour'    => 'Aminte&#351;te-mi peste 1 or&#259;',
    'VersionRemindNever'   => 'Nu aminti despre versiuni noi',
    'VersionRemindWeek'    => 'Aminte&#351;te-mi peste 1 s&#259;pt&#259;m&#226;n&#259;',
    'Video'                => 'Video',
    'VideoFormat'          => 'Video Format',
    'VideoGenFailed'       => 'Generare video esuata!',
    'VideoGenFiles'        => 'Existing Video Files',
    'VideoGenNoFiles'      => 'No Video Files Found',
    'VideoGenParms'        => 'Parametrii generare video',
    'VideoGenSucceeded'    => 'Video Generation Succeeded!',
    'VideoSize'            => 'M&#259;rime video',
    'VideoWriter'          => 'Video Writer',           // Added - 2018-08-30
    'View'                 => 'Vizual',
    'ViewAll'              => 'Vizual. tot',
    'ViewEvent'            => 'View Event',
    'ViewPaged'            => 'Vizual. paginat',
    'Wake'                 => 'Wake',
    'WarmupFrames'         => 'Warmup Frames',
    'Watch'                => 'Watch',
    'Web'                  => 'Web',
    'WebColour'            => 'Web Colour',
    'WebSiteUrl'           => 'Website URL',            // Added - 2018-08-30
    'Week'                 => 'S&#259;pt.',
    'White'                => 'Alb',
    'WhiteBalance'         => 'Balans alb',
    'Wide'                 => 'Wide',
    'X'                    => 'X',
    'X10'                  => 'X10',
    'X10ActivationString'  => 'String activare X10',
    'X10InputAlarmString'  => 'X10 Input Alarm String',
    'X10OutputAlarmString' => 'X10 Output Alarm String',
    'Y'                    => 'Y',
    'Yes'                  => 'Da',
    'YouNoPerms'           => 'Nu aveti permisiunile necesare pentru accesarea acestei resurse.',
    'Zone'                 => 'Zone',
    'ZoneAlarmColour'      => 'Alarm Colour (Red/Green/Blue)',
    'ZoneArea'             => 'Zone Area',
    'ZoneExtendAlarmFrames' => 'Extend Alarm Frame Count',
    'ZoneFilterSize'       => 'Filter Width/Height (pixels)',
    'ZoneMinMaxAlarmArea'  => 'Min/Max Alarmed Area',
    'ZoneMinMaxBlobArea'   => 'Min/Max Blob Area',
    'ZoneMinMaxBlobs'      => 'Min/Max Blobs',
    'ZoneMinMaxFiltArea'   => 'Min/Max Filtered Area',
    'ZoneMinMaxPixelThres' => 'Min/Max Pixel Threshold (0-255)',
    'ZoneMinderLog'        => 'ZoneMinder Log',         // Added - 2011-06-17
    'ZoneOverloadFrames'   => 'Overload Frame Ignore Count',
    'Zones'                => 'Zona',
    'Zoom'                 => 'Zoom',
    'ZoomIn'               => 'Zoom In',
    'ZoomOut'              => 'Zoom Out',
);

// Complex replacements with formatting and/or placements, must be passed through sprintf
$CLANG = array(
    'CurrentLogin'         => 'E&#351;ti logat ca \'%1$s\'',
    'EventCount'           => '%1$s %2$s', // For example '37 Events' (from Vlang below)
    'LastEvents'           => 'Ultimele %1$s %2$s', // For example 'Last 37 Events' (from Vlang below)
    'LatestRelease'        => 'Ultima versiune este v%1$s, momentan rula&#355;i v%2$s.',
    'MonitorCount'         => '%1$s %2$s', // For example '4 Monitors' (from Vlang below)
    'MonitorFunction'      => 'Func&#355;iile monitorului %1$s ',
    'RunningRecentVer'     => 'Rula&#355;i ultima versiune de ZoneMinder, v%s.',
    'VersionMismatch'      => 'Version mismatch, system is version %1$s, database is %2$s.', // Added - 2011-05-25
);

// Variable arrays expressing plurality
$VLANG = array(
    'Event'                => array( 0=>'Evenimente', 1=>'Eveniment', 2=>'Evenimente' ),
    'Monitor'              => array( 0=>'Monitoare', 1=>'Monitor', 2=>'Monitoare' ),
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

// OPTIONS
$OLANG = array(
// Beginning of System tab
    'LANG_DEFAULT' => array(
        'Prompt' => "Limba implicit&#259; folosit&#259;",
        'Help' => "ZoneMinder permite folosirea &#238;n interfa&#355;a web a altei limbi dec&#226;t Engleza dac&#259; fi&#351;ierul necesar a fost creat &#351;i exist&#259;. Aceast&#259; op&#355;iune v&#259; permite s&#259; schimba&#355;i limba implicit&#259;, Engleza Britanica, cu o alt&#259; limb&#259;."
    ),
    'OPT_USE_AUTH' => array(
        'Prompt' => "Autentific&#259; utilizatorii la ZoneMinder",
        'Help' => "Zoneminder poate rula &#238;n dou&#259; moduri. Cel mai simplu este cel f&#259;r&#259; autentificare, &#238;n care oricine poate accesa ZoneMinder av&#226;nd acces la toate op&#355;iunile. Acest mod este fiabil dac&#259; accesul la server-ul web este limitat prin alte modalit&#259;t&#355;i. Al doilea mod permite ad&#259;ugarea de utilizatori cu diverse permisiuni. Utilizatorii trebuie s&#259; se autentifice la ZoneMinder &#351;i sunt limita&#355;i de permisiunile definite."
    ),
    'AUTH_RELAY' => array(
        'Prompt' => "Metoda folosit&#259; pentru autentificare",
        'Help' => "&#206;n cazul &#238;n care ZoneMinder ruleaz&#259; &#238;n mod autentificat trebuie s&#259; transmit&#259; informa&#355;iile utilizatorilor la paginile web aferente. Acest lucru este realizat &#238;n dou&#259; moduri. Primul este s&#259; foloseasc&#259; un string care nu con&#355;ine detalii directe despre utilizator &#351;i parol&#259;; cel de-al doilea este s&#259; transmit&#259; utilizatorul &#351;i parola direct. Aceast&#259; metod&#259; nu este recomandat&#259; numai dac&#259; nu ave&#355;i libr&#259;riile md5 disponibile pe sistemul dvs. sau ave&#355;i un sistem complet izolat, f&#259;r&#259; acces extern."
    ),
    'AUTH_HASH_SECRET' => array(
        'Prompt' => "Secret folosit pentru codarea informa&#355;iilor de autentificare",
        'Help' => "C&#226;nd ZoneMinder ruleaz&#259; &#238;n mod de autentificare codat (hashed), necesit&#259; generarea string-urilor de codare care con&#355;in informa&#355;ii criptate ca utilizatorii &#351;i parolele. De&#351;i acest string este destul de sigur, ad&#259;ugarea unui string aleator &#238;mbun&#259;t&#259;&#355;e&#351;te securitatea."
    ),
    'OPT_FAST_DELETE' => array(
        'Prompt' => "La &#351;tergerea evenimentelor &#351;terge numai informa&#355;iile din baza de date",
        'Help' => "&#206;n mod normal un eveniment creat ca rezultat al unei alarme este compus din unul sau mai multe tabele &#238;n baza de date plus fi&#351;ierele asociate. C&#226;nd &#351;terge&#355;i evenimente din broswer poate dura mult dac&#259; &#351;terge&#355;i mai multe evenimente concomitent. Este recomandat s&#259; activa&#355;i aceast&#259; op&#355;iune, care va &#351;terge doar informa&#355;iile din baza de date. Evenimentele nu vor mai ap&#259;rea la vizualizare, &#351;i vor fi &#351;terse de daemon-ul zmaudit mai t&#226;rziu."
    ),
    'SHM_KEY' => array(
        'Prompt' => "Cheie memorie comuna, modifica&#355;i numai &#238;n cazul conflictelor cu alte aplica&#355;ii",
        'Help' => "ZoneMinder folose&#351;te memorie comun&#259; pentru a face comunicarea &#238;ntre module mai rapid&#259;. Pentru a identifica zona corect&#259; ce trebuie folosit&#259; sunt utilizate chei de memorie comun&#259;. Aceast&#259; op&#355;iune controleaz&#259; valoarea cheii."
    ),
    'FILTER_RELOAD_DELAY' => array(
        'Prompt' => "La c&#226;te secunde sunt re&#238;nc&#259;rcate filtrele &#238;n zmfilter.pl",
        'Help' => "ZoneMinder v&#259; permite s&#259; salva&#355;i filtrele &#238;n baza de date put&#226;nd astfel s&#259; sterge&#355;i sau s&#259; upload-a&#355;i evenimentele corespunz&#259;toare anumitor criterii. Daemon-ul zmfilter &#238;ncarc&#259; aceste evenimente, &#351;terge sau upload-eaz&#259;. Aceast&#259; op&#355;iune determin&#259; c&#226;t de des filtrele vor fi re&#238;nc&#259;rcate. Dac&#259; nu schimba&#355;i des filtrele aceasta poate avea valori mari."
    ),
    'MAX_RESTART_DELAY' => array(
        'Prompt' => "La c&#226;t timp (&#238;n secunde) daemon-ul va &#238;ncerca repornire.",
        'Help' => "zmdc (daemon-ul de control zm) controleaz&#259; toate procesele care sunt pornite sau oprite &#351;i va &#238;ncerca reponire la orice eroare. Dac&#259; sunt multe erori trebuie introdus un timp de &#238;nt&#226;rziere &#238;ntre reporniri. Dac&#259; sunt erori &#238;n continuare aceast&#259; valoare cre&#351;te pentru a &#238;mpierdica blocarea sistemului datorat&#259; repornirilor. Aceast&#259; op&#355;iune controleaz&#259; valoarea de &#238;nt&#226;rziere."
    ),
    'WATCH_CHECK_INTERVAL' => array(
        'Prompt' => "C&#226;t de des verific dac&#259; daemonii de captur&#259; nu s-au blocat.",
        'Help' => "Daemon-ul zmwatch verific&#259; daemonii de captur&#259; pentru a verifica dac&#259; sunt bloca&#355;i (rareori se produce o desincronizare care blocheaz&#259; daemonii). Aceast&#259; op&#355;iune determin&#259; c&#226;t de des sunt verifica&#355;i daemonii."
    ),
    'WATCH_MAX_DELAY' => array(
        'Prompt' => "Durata maxim&#259; de am&#226;nare, de la ultima imagine capturat&#259;, inainte de a reporni daemonii de captur&#259;",
        'Help' => "Aceast&#259; op&#355;iune determin&#259; durata maxim&#259; de am&#226;nare, de la ultimul cadru capturat, pe care o ve&#355;i permite. Daemon-ul va fi repornit dac&#259; nu a &#238;nregistrat nici o imagine dup&#259; aceast&#259; perioad&#259;, totu&#351;i repornirea poate dura mai mult, &#238;n conjunc&#355;ie cu intervalul de verificat de mai sus."
    ),
    'RECORD_EVENT_STATS' => array(
        'Prompt' => "&#206;nregistrez informa&#355;ii despre evenimente. Dezactiva&#355;i dac&#259; ZoneMinder devine lent.",
        'Help' => "Aceast&#259; versiune de ZoneMinder &#238;nregistreaz&#259; informa&#355;ii despre evenimente &#238;n tabelul Stats. Aceasta v&#259; poate ajuta s&#259; determina&#355;i set&#259;rile optime pentru zonele definite, totu&#351;i aceast&#259; op&#355;iune poate fi &#238;n&#351;elatoare. &#206;n versiunile viitoare op&#355;iunea va fi mai exact&#259;, mai ales &#238;n cazul unui num&#259;r mare de evenimente. Op&#355;iunea implicit&#259; (da) permite stocarea acestor informa&#355;ii dar dac&#259; vre&#355;i performan&#355;&#259; pute&#355;i dezactiva aceast&#259; op&#355;iune, caz &#238;n care informa&#355;iile despre evenimente nu vor fi salvate."
    ),
    'RECORD_DIAG_IMAGES' => array(
        'Prompt' => "&#206;nregistrare imagini intermediare de diagnosticare, foarte lent",
        'Help' => "Pe l&#226;ng&#259; faptul c&#259; se pot &#238;nregistra statisticile evenimentelor se pot deasemenea &#238;nregistra imagini intermediare de diagnosticare care afi&#351;eaz&#259; rezultatele diferitelor verific&#259;ri care au loc c&#226;nd se &#238;ncearc&#259; determinarea unei posibile alarme. Aceste imagini sunt generate pentru fiecare cadru, zon&#259; &#351;i alarm&#259;, deci impactul asupra performan&#355;ei va fi foarte mare. Activa&#355;i aceast&#259; op&#355;iune doar pentru depanare sau analiz&#259; &#351;i nu uita&#355;i s&#259; o dezactiva&#355;i."
    ),
    'CREATE_ANALYSIS_IMAGES' => array(
        'Prompt' => "Creaz&#259; imagini analizate cu marcaje ale mi&#351;c&#259;rii",
        'Help' => "Implicit, &#238;n cazul unei alarme, ZoneMinder &#238;nregistreaz&#259; at&#226;t imaginile neprelucrate c&#226;t &#351;i cele ce au fost analizate &#351;i au zone marcate unde a fost detectat&#259; mi&#351;care. Acest lucru poate fi foarte folositor la configurarea zonelor sau &#238;n analiza evenimentelor. Acest parametru permite oprirea &#238;nregistr&#259;rii imaginilor cu zone de mi&#351;care marcate."
    ),
    'OPT_CONTROL' => array(
        'Prompt' => "Suport camere controlabile (rotire/&#238;nclinare/zoom)",
        'Help' => "ZoneMinder include suport limitat pentru camere controlabile. Sunt incluse c&#226;teva protocoale mostr&#259; &#351;i pot fi ad&#259;ugate cu u&#351;urin&#355;&#259; &#351;i altele. Dac&#259; vre&#355;i s&#259; controla&#355;i camerele prin intermediul ZoneMinder selecta&#355;i aceast&#259; op&#355;iune."
    ),
    'CHECK_FOR_UPDATES' => array(
        'Prompt' => "Verific versiuni noi la zoneminder.com",
        'Help' => "&#206;ncep&#226;nd cu versiunea 1.17.0, versiuni noi sunt a&#351;teptate frecvent. ZoneMinder poate compara versiunea instalat&#259; cu cea mai recent&#259; de pe zoneminder.com. Aceste verific&#259;ri sunt f&#259;cute cam o dat&#259; pe sapt&#259;m&#226;n&#259; &#351;i nu sunt transmise nici un fel de informa&#355;ii despre sistemul dvs. &#238;n afar&#259; de versiunea de zoneminder pe care o rula&#355;i. Dac&#259; nu dori&#355;i s&#259; face&#355;i verific&#259;ri de versiune sau nu ave&#355;i conexiune la internet dezactiva&#355;i aceast&#259; op&#355;iune."
    ),
// End of System tab

// Beginning of Paths tab
    'DIR_EVENTS' => array(
        'Prompt' => "Directorul &#238;n care sunt stocate evenimentele",
        'Help' => "Acesta este subdirectorul &#238;n care sunt salvate imaginile generate de evenimente &#351;i alte fi&#351;iere. Implicit este un subdirector al directorului r&#259;d&#259;cina zoneminder; dac&#259; spa&#355;iul nu v&#259; permite pute&#355;i s&#259; stoca&#355;i imaginile pe alt&#259; parti&#355;ie, caz &#238;n care ar trebui s&#259; face&#355;i un link la subdirectorul implicit."
    ),
    'DIR_SOUNDS' => array(
        'Prompt' => "Directorul cu sunetele care pot fi folosite de ZoneMinder",
        'Help' => "ZoneMinder poate rula un sunet atunci c&#226;nd este detectat&#259; o alarm&#259;. Acesta este directorul &#238;n care este stocat sunetul care va fi rulat."
    ),
    'PATH_ZMS' => array(
        'Prompt' => "Calea web la serverul video zms",
        'Help' => "Serverul video este necesat pentru a trimite imagini la browser-ul dvs. Va fi instalat &#238;n calea cgi-bin specificat&#259; la instalare. Aceast&#259; op&#355;iune determin&#259; calea web la server. &#206;n mod normal serverul video ruleaz&#259; &#238;n mod parser-header. Dac&#259; ave&#355;i probleme cu aceast&#259; setare pute&#355;i trece &#238;n modul non-parsed-header &#238;nlocuind 'zms' cu 'nph-zms'."
    ),
    'PATH_SOCKS' => array(
        'Prompt' => "Calea socket-urilor Unix care sunt folosite de ZoneMinder ",
        'Help' => "&#206;n general ZoneMinder folose&#351;te socket-urilor Unix. Astfel se reduce nevoia de a asigna porturi &#351;i &#238;mpiedic&#259; eventualele conflicte cu aplica&#355;ii externe. Fiecare socket Unix necesit&#259; un fi&#351;ier cu extensia .sock. Aceast&#259; op&#355;iune indic&#259; unde vor fi stocare fi&#351;ierele .sock."
    ),
    'PATH_LOGS' => array(
        'Prompt' => "Calea la logurile generate de daemonii ZoneMinder",
        'Help' => "Majoritatea daemon-ilor ZoneMinder genereaz&#259; log-uri care v&#259; pot ajuta. Acesta este directorul &#238;n care vor fi stocate log-urile. Log-urile pot fi &#351;terse dac&#259; nu sunt necesare."
    ),
// End of Paths tab

// Beginning of Config tab
    'TIMESTAMP_ON_CAPTURE' => array(
        'Prompt' => "Adaug&#259; ora pe imaginile capturate",
        'Help' => "ZoneMinder poate ad&#259;uga ora pe imagini &#238;n dou&#259; feluri. Metoda implicit&#259;, c&#226;nd aceast&#259; op&#355;iune este activ&#259;, face ca fiecarei imagini s&#259; i se aplice ora imediat ce a fost capturat&#259;. A doua metod&#259; nu adaug&#259; ora pe imagini numai c&#226;nd sunt salvate ca parte a unui eveniment sau accesate prin web. Ora va avea acela&#351;i format &#238;n oricare dintre cele dou&#259; cazuri. Folosind prima metod&#259; v&#259; asigura&#355;i c&#259; imaginile au ora tiparit&#259; pe ele indiferent de alte circumstan&#355;e dar va ad&#259;uga ora pe toate imaginile, chiar &#351;i pe cele care nu au fost vizualizate sau salvate. A doua metod&#259; necesit&#259; ca imaginile ce urmeaz&#259; a fi salvate s&#259; fie copiate, &#238;nainte de a fi salvate, altfel cele dou&#259; ore ad&#259;ugate pe imagini pot fi diferite. Ora este &#238;ntotdeauna salvat&#259; la aceeasi rezolu&#355;ie, deci imaginile vor putea fi identificate dup&#259; ora la care au fost capturate."
    ),
    'LOCAL_BGR_INVERT' => array(
        'Prompt' => "Schimb&#259; BGR in RGB",
        'Help' => "Unele camere &#351;i pl&#259;ci de captur&#259; &#238;nregistreaz&#259; imaginile &#238;n ordinea BGR (Albastru-Verde-Ro&#351;u) chiar dac&#259; paleta de culori spune RGB (Ro&#351;u-Verde-Albastru). Dac&#259; observa&#355;i culori ciudate pe imaginile capturate &#238;ncerca&#355;i s&#259; modifica&#355;i aceast&#259; op&#355;iune. Not&#259;: aceast&#259; op&#355;iune este aplicabil&#259; numai pentru camerele locale nu &#351;i pentru cele din re&#355;ea."
    ),
    'Y_IMAGE_DELTAS' => array(
        'Prompt' => "Calcul diferen&#355;ial al imaginilor folosind canalul Y",
        'Help' => "Atunci c&#226;nd ZoneMinder &#238;ncearc&#259; s&#259; stabileasc&#259; diferen&#355;ele dintre dou&#259; imagini color genereaz&#259; o imagine &#238;n scal&#259; de gri 'delta'. Pentru a face acest lucru determin&#259; diferen&#355;ele dintre componentele RGB &#351;i calculeaz&#259; o scal&#259; de gri corespunz&#259;toare. Dac&#259; aceast&#259; op&#355;iune este activ&#259; atunci calculul se va face prin conversia fiec&#259;rui pixel din imagine &#238;ntr-o valoare luminoas&#259; (Y din YUV) &#351;i g&#259;sirea diferen&#355;elor. Dac&#259; aceast&#259; op&#355;iune nu este activ&#259; atunci diferen&#355;a rezultat&#259; este determinat&#259; ca media diferen&#355;elor fiec&#259;rei culori. Folosind valoare Y &#351;ansele de acurate&#355;e sunt mult mai mari iar procesul este cu 15% mai rapid."
    ),
    'FAST_IMAGE_BLENDS' => array(
        'Prompt' => "Folosirea unui algoritm rapid pentru combinarea imaginilor",
        'Help' => "&#206;n majoritatea modurilor de rulare ZoneMinder trebuie s&#259; combine imaginile capturate cu imagini de referin&#355;&#259; deja stocate pentru a le actualiza pentru urm&#259;toarea imagine. Procentajul de combinare controleaz&#259; c&#226;t de mult afecteaz&#259; noua imagine capturat&#259; imaginea de referin&#355;&#259;. Pentru acest proces sunt disponibile dou&#259; metode. Dac&#259; aceast&#259; op&#355;iune este setat&#259; atunci un calcul de baz&#259; este aplicat care, de&#351;i rapid &#351;i exact, poate reduce raza de pixeli din imaginea de referin&#355;&#259;. Dac&#259; ave&#355;i &#351;i o valoare mic&#259; ca minim de diferen&#355;&#259; dintre pixeli, pot ap&#259;rea alarme false. Alternativa este s&#259; dezactiva&#355;i aceast&#259; op&#355;iune, caz &#238;n care vor fi stocate un set de valori temporare care vor elimina erorile. De&#351;i dezactivarea va avea ca rezultat acurate&#355;e mai mare, poate fi de 6 ori mai lent&#259;. Aceast&#259; op&#355;iune  ar trebui dezactivat&#259; doar &#238;n cazul &#238;n care ave&#355;i probleme cu metoda implicit&#259;."
    ),
    'COLOUR_JPEG_FILES' => array(
        'Prompt' => "Aplic&#259; culori fi&#351;ierelor JPEG capturate &#238;n scal&#259; de gri",
        'Help' => "Camerele alb/negru pot aplica scal&#259; de gri fi&#351;ierelor jpeg capturate. Aceste camere economisesc spa&#355;iu &#238;n compara&#355;ie cu cele color. Totu&#351;i unele unelte, cum ar fi ffmpeg &#351;i mpeg_encode, ori nu func&#355;ioneaz&#259; cu aceste set&#259;ri ori trebuie s&#259; transforme imaginile. Activ&#226;nd aceast&#259; op&#355;iune ocupa&#355;i mai mult spa&#355;iu pe disc dar crea&#355;i fi&#351;ierele MPEG mult mai repede."
    ),
    'JPEG_FILE_QUALITY' => array(
        'Prompt' => "Seteaz&#259; calitatea JPEG pentru imaginile statice (1-100)",
        'Help' => "Atunci c&#226;nd ZoneMinder detecteaz&#259; un eveniment va salva fi&#351;ierele asociate. Aceste fi&#351;iere sunt &#238;n format JPEG &#351;i pot fi v&#259;zute sau difuzate mai departe. Aceast&#259; op&#355;iune specific&#259; calitatea la care vor fi salvate imaginile. Un num&#259;r mare &#238;nseamn&#259; calitate mai bun&#259; dar compresie mai mic&#259;, deci va ocupa spa&#355;iu mai mult pe disc &#351;i va dura mai mult timp s&#259; o &#238;nc&#259;rca&#355;i. Un num&#259;r mai mic &#238;nseamn&#259; spa&#355;iu mai pu&#355;in ocupat, vizualizare mai rapid&#259; dar calitate redus&#259;."
    ),
    'JPEG_IMAGE_QUALITY' => array(
        'Prompt' => "Seteaz&#259; calitatea JPEG pentru imaginile 'live'(video) (1-100)",
        'Help' => "C&#226;nd vizualiza&#355;i un stream 'live' al unui monitor Zoneminder va lua o imagine din buffer &#351;i o va encoda &#238;nainte de a o trimite. Aceast&#259; op&#355;iune specific&#259; ce calitate va fi folosit&#259; pentru encodarea imaginilor. Un num&#259;r mare &#238;nseamn&#259; calitatea bun&#259; dar compresie redus&#259; deci va dura mai mult vizualizarea &#238;n cazul conexiunilor lente. Din contr&#259;, un num&#259;r mic &#238;nseamna vitez&#259; mare de vizualizare dar calitatate redus&#259;. Aceast&#259; op&#355;iune nu se aplic&#259; &#238;n cazul imaginilor statice care vor fi salvate la calitatea specificat&#259; &#238;n op&#355;iune precedent&#259;."
    ),
    'BLEND_ALARMED_IMAGES' => array(
        'Prompt' => "Combinare imagini de alarm&#259; pentru actualizarea imaginii de referin&#355;&#259;",
        'Help' => "Pentru a detecta o alarm&#259; ZoneMinder compar&#259; o imagine cu o imagine de referin&#355;&#259; care este alc&#259;tuit&#259; dintr-o suit&#259; de imagini anterioare. Aceast&#259; op&#355;iune determin&#259; dac&#259; imaginile care cauzeaz&#259; un eveniment vor fi incluse &#238;n acest proces. Activ&#226;nd aceast&#259; op&#355;iune poate cre&#351;te precizia alarmelor dar poate cauza probleme &#238;n cazul schimb&#259;rilor dese de luminozitate, caz &#238;n care alarmele vor persista. O cale mai bun&#259; pentru precizie este sa micsora&#355;i procentajul de combinare de referin&#355;&#259; pentru monitoarele &#238;n cauz&#259;."
    ),
    'NO_MAX_FPS_ON_ALARM' => array(
        'Prompt' => "Ignor&#259; valoarea FPS Maxim &#238;n cazul unei alarme",
        'Help' => "C&#226;nd configura&#355;i monitoarele pute&#355;i specifica o valoare maxim&#259; pentru rata de capturare, exprimat&#259; &#238;n cadre pe secund&#259;. Aceasta poate fi folosit&#259; pentru a limita capacit&#259;&#355;ile video, de la&#355;ime de band&#259; sau pentru a reduce supra&#238;nc&#259;rcarea procesorului. Aceast&#259; op&#355;iune 'v-a comunica' ZoneMinder-ului s&#259; ignore aceste limit&#259;ri la apari&#355;ia unei alarme &#351;i s&#259; &#238;ncerce captura c&#226;t mai rapid posibil."
    ),
    'OPT_ADAPTIVE_SKIP' => array(
        'Prompt' => "Analiza eficient&#259; prin omitere de cadre",
        'Help' => "&#206;n versiuni precedente ale ZoneMinder daemon-ul de analiz&#259; procesa ultimul cadru capturat pentru 'a &#355;ine pasul' cu daemon-ul de captur&#259;. Acest lucru are ca efect secundar lipsa unei buca&#355;i din secven&#355;a de alarm&#259; deoarece toate cadrele precedente alarmei trebuie scrise pe disc &#351;i &#238;n baza de date &#238;nainte de a trece la urm&#259;torul cadru, duc&#226;nd la &#238;nt&#226;rzieri &#238;ntre cadre. Set&#226;nd aceast&#259; op&#355;iune este activat un nou algoritm adaptiv &#238;n care daemon-ul de analiz&#259; &#238;ncearc&#259; procesarea c&#226;t mai multor cadre posibile omi&#355;&#226;nd cadre doar &#238;n cazul &#238;n care daemon-ul de captur&#259; amenin&#355;&#259; suprascrierea cadrelor procesate. Aceast&#259; omitere este variabil&#259; &#238;n func&#355;ie de spa&#355;iul liber &#351;i de memoria tampon. Activarea acestei op&#355;iuni v&#259; ofer&#259; acoperirea mai eficient&#259; a &#238;nceputului alarmelor. Aceast&#259; op&#355;iune poate avea efect de &#238;ncetinire a daemon-ului de analiz&#259; fa&#355;&#259; de daemon-ul de captur&#259; &#238;n timpul evenimentelor &#351;i pentru anumite frecven&#355;e rapide de captur&#259; este posibil ca acest algoritm s&#259; fie cople&#351;it neav&#226;nd timp s&#259; reac&#355;ioneze la construc&#355;ia rapid&#259; a cadrelor, a&#351;adar pot ap&#259;rea blocaje."
    ),
    'STRICT_VIDEO_CONFIG' => array(
        'Prompt' => "Permite erorilor &#238;n set&#259;rile video s&#259; fie fatale",
        'Help' => "Unele dispozitive video pot anun&#355;a erori c&#226;nd de fapt ac&#355;iunea a avut succes. Dezactiv&#226;nd aceast&#259; op&#355;iune va permite anun&#355;area de erori &#238;n continuare dar nu va opri daemon-ul de captur&#259;. Aceast&#259; op&#355;iune va avea ca efect ignorarea tuturor erorilor inclusiv cele autentice care poate cauza oprirea capturii video. Folosi&#355;i aceast&#259; op&#355;iune cu aten&#355;ie."
    ),
    'FORCED_ALARM_SCORE' => array(
        'Prompt' => "Valoarea pentru alarmele for&#355;ate",
        'Help' => "Utilitarul 'zmu' poate fi folosit pentru a for&#355;a o alarm&#259; mai degrab&#259; dec&#226;t bazarea pe algoritmii de detectare a mi&#351;c&#259;rii. Aceast&#259; op&#355;iune determin&#259; ce valoare vor avea alarmele for&#355;ate pentru a fi distinctive fa&#355;&#259; de cele normale. Valoare trebuie s&#259; fie 255 sau mai pu&#355;in."
    ),
    'BULK_FRAME_INTERVAL' => array(
        'Prompt' => "C&#226;t de des va fi scris un cadru 'masiv' &#238;n baza de date",
        'Help' => "Tradi&#355;ional ZoneMinder introduce o valoare &#238;n tabelul Frames din baza de date pentru fiecare cadru capturat &#351;i salvat. Aceast&#259; ac&#355;iune func&#355;ioneaz&#259; bine &#238;n cazul &#238;n care ZoneMinder ruleaz&#259; detect&#226;nd mi&#351;care dar &#238;n modurile 'Record' sau 'Mocord' rezult&#259; un num&#259;r imens de cadre care ocup&#259; mult spa&#355;iu &#238;n baza de date &#351;i pe disc. Aplic&#226;nd acestei op&#355;iuni o valoare diferit&#259; de zero va permite ZoneMinder-ului s&#259; grupeze toate cadrele care nu &#355;in de o alarm&#259; &#238;ntr-un cadru 'masiv' care va salva spa&#355;iu &#351;i bandwidth. Singurul dezavantaj al acestei op&#355;iuni este ca informa&#355;iile temporale pentru cadrele individuale sunt pierdute dar &#238;n cazul frecven&#355;ei video constante acest lucru este nesemnificativ. Aceast&#259; setare este ignorat&#259; &#238;n modul Modect iar cadre individuale sunt &#238;nregistrate la apari&#355;ia unei alarme &#238;n modul Mocord."
    ),
    'EVENT_IMAGE_DIGITS' => array(
        'Prompt' => "C&#226;te cifre sunt folosite pentru numerotarea imaginilor",
        'Help' => "Imaginile capturate sunt stocate pe disc cu un index numeric. Implicit acest index are trei cifre deci numele &#238;ncep cu 001, 002, etc. Aceast&#259; setare func&#355;ioneaz&#259; &#238;n majoritatea cazurilor deoarece evenimente cu peste 999 de cadre sunt rar capturate. Oricum dac&#259; ave&#355;i evenimente foarte lungi pute&#355;i m&#259;ri aceast&#259; valoare pentru a asigura sortarea corect&#259; a imaginilor. Aten&#355;ie, cre&#351;terea valorii pe un sistem care ruleaz&#259; poate avea ca efect reorganizarea incorect&#259; a evenimentelor. Descre&#351;terea acestei valorii nu ar trebui s&#259; aib&#259; efecte negative."
    ),
// End of Config tab

// Beginning of Network tab
    'OPT_REMOTE_CAMERAS' => array(
        'Prompt' => "Folosi&#355;i camere din re&#355;ea",
        'Help' => "ZoneMinder ruleaz&#259; at&#226;t cu camere locale, ex. cele ata&#351;ate fizic la computerul dvs. sau camere din re&#355;ea. Daca ve&#355;i folosi camere din re&#355;ea selecta&#355;i aceast&#259; op&#355;iune."
    ),
    'HTTP_VERSION' => array(
        'Prompt' => "Versiunea de HTTP pe care o va folosi ZoneMinder la conectare",
        'Help' => "ZoneMinder poate comunica folosit standardele HTTP/1.0 sau HTTP/1.1. Aceast&#259; op&#355;iune specific&#259; care standard va fi folosit."
    ),
    'HTTP_UA' => array(
        'Prompt' => "Cum se va identifica ZoneMinder",
        'Help' => "C&#226;nd ZoneMinder comunic&#259; cu camere din re&#355;ea se va identifica folosind acest string &#351;i versiunea. &#206;n mod normal aceast&#259; setare este suficient&#259;, totu&#351;i dac&#259; o anume camera nu va rula numai cu un anumit browser, aceast&#259; op&#355;iune se poate schimba pentru a identifica ZoneMinder ca fiind Internet Explorer, Netscape, etc."
    ),
    'HTTP_TIMEOUT' => array(
        'Prompt' => "C&#226;t a&#351;teapt&#259; ZoneMinder p&#226;n&#259; la decizia c&#259; imaginea nu poate fi desc&#259;rcat&#259; (milisecunde)",
        'Help' => "La desc&#259;rcarea imaginilor remote ZoneMinder va a&#351;tepta at&#226;t timp &#238;nainte de a decide c&#259; imaginea nu poate fi desc&#259;rcat&#259; &#351;i va re&#238;ncerca. Acest timp expirat este exprimat &#238;n milisecunde &#351;i va fi aplicat fiec&#259;rei p&#259;r&#355;i din imagine dac&#259; imaginea nu este trimis&#259; ca tot unitar."
    ),
// End of Network tab

// Beginning of Web tab
    'WEB_POPUP_ON_ALARM' => array(
        'Prompt' => "Fereastra monitorului deasupra tuturor ferestrelor la apari&#355;ia unei alarme",
        'Help' => "La vizionarea unui flux video 'live' pute&#355;i specifica dac&#259; vre&#355;i sau nu ca fereastra monitorului s&#259; sar&#259; deasupra tuturor ferestrelor &#238;n cazul apari&#355;iei unei alarme."
    ),
    'WEB_SOUND_ON_ALARM' => array(
        'Prompt' => "Redare sunet la apari&#355;ia unei alarme",
        'Help' => "La vizionarea unui flux video 'live' pute&#355;i specifica dac&#259; vre&#355;i sau nu redarea unui sunet pentru a va aten&#355;iona de apari&#355;ia unei alarme."
    ),
    'WEB_ALARM_SOUND' => array(
        'Prompt' => "Sunet de redat la alarme",
        'Help' => "Pute&#355;i specifica un fi&#351;ier audio care va fi redat &#238;n cazul unei alarme. At&#226;t timp c&#226;t browser-ul &#238;n&#355;elege formatul sunetul nu trebuie s&#259; fie de un anumit tip. Acest fi&#351;ier trebuie pus &#238;n directorul de fi&#351;iere audio."
    ),
    'WEB_COMPACT_MONTAGE' => array(
        'Prompt' => "Compactarea montajului prin omiterea detaliilor",
        'Help' => "Modul de vizualizare &#238;n montaj afi&#351;eaz&#259; toate monitoarele active &#238;ntr-o singur&#259; fereastr&#259;. Acesta include un meniu mic &#351;i informa&#355;iile de stare pentru fiecare. Acesta poate cre&#351;te traficul &#351;i poate face fereastra mai mare dec&#226;t dorit&#259;. Activarea acestei op&#355;iuni omite toate informa&#355;iile adi&#355;ionale &#351;i afi&#351;eaz&#259; imaginile."
    ),
    'WEB_MONTAGE_MAX_COLS' => array(
        'Prompt' => "Num&#259;r maxim de coloane de monitoare &#238;n vizualizare monataj",
        'Help' => "Vizualizarea montaj afi&#351;eaz&#259; imagini de la toate monitoarele. Acest parametru define&#351;te c&#226;te monitoare vor fi pozi&#355;ionate pe ecran &#238;nainte de a trece la urm&#259;torul r&#226;nd. Dac&#259; ave&#355;i ecran foarte lat &#351;i/sau imagini mici de la camere acesta poate avea valori mai mari."
    ),
    'WEB_MONTAGE_WIDTH' => array(
        'Prompt' => "L&#259;&#355;ime monitor &#238;n vizualizare montaj",
        'Help' => "&#206;n modul de vizualizare montaj pute&#355;i vizualiza toate monitoarele concomitent. Dac&#259; au dimensiuni diferite fereastra poate ap&#259;rea deformat&#259;. Setarea acestei op&#355;iuni v&#259; permite s&#259; mentine&#355;i la&#355;imea fiec&#259;rui monitor la o valoare fix&#259; fac&#226;nd fereastra mai ordonat&#259;. Las&#226;nd aceast&#259; valoare zero permite afi&#351;area fiec&#259;rui monitor &#238;n dimensiunea sa nativ&#259;."
    ),
    'WEB_MONTAGE_HEIGHT' => array(
        'Prompt' => "&#206;n&#259;l&#355;ime monitor &#238;n vizualizare montaj",
        'Help' => "&#206;n modul de vizualizare montaj pute&#355;i vizualiza toate monitoarele concomitent. Dac&#259; au dimensiuni diferite fereastra poate ap&#259;rea deformat&#259;. Setarea acestei op&#355;iuni v&#259; permite s&#259; mentine&#355;i &#238;n&#259;l&#355;imea fiec&#259;rui monitor la o valoare fix&#259; fac&#226;nd fereastra mai ordonat&#259;. Las&#226;nd aceast&#259; valoare zero permite afi&#351;area fiec&#259;rui monitor &#238;n dimensiunea sa nativ&#259;."
    ),
    'WEB_REFRESH_METHOD' => array(
        'Prompt' => "Metoda pentru actualizarea ferestrelor, alege&#355;i javascript sau http",
        'Help' => "Multe ferestre &#238;n JavaScript trebuie actulizate pentru a avea informa&#355;ii curente. Aceast&#259; op&#355;iune determin&#259; ce metod&#259; v-a fi folosit&#259; pentru actualizare. Dac&#259; alege&#355;i 'javascript' fiecare fereastr&#259; va avea o scurt&#259; instruc&#355;iune JavaScript pentru actualizare. Aceasta este cea mai compatibil&#259; metod&#259;. Dac&#259; alege&#355;i 'http' instruc&#355;iunea de actulizare va fi &#238;n antetul HTTP. Aceasta este metoda mai curat&#259; dar actuliz&#259;rile sunt &#238;ntrerupte sau revocate c&#226;nd face&#355;i click pe un link din fereastr&#259;."
    ),
    'WEB_DOUBLE_BUFFER' => array(
        'Prompt' => "Memorie tampon dubl&#259; pentru a evita p&#226;lp&#226;itul imaginilor",
        'Help' => "&#206;ncep&#226;nd cu versiunea 1.18.0 ZoneMinder poate folosi memorie tampon dubl&#259; pentru a pre&#238;nc&#259;rca imaginile &#238;nainte de a fi afi&#351;ate pe ecran. Aceast&#259; metod&#259; reduce p&#226;lp&#226;itul imaginilor. Totu&#351;i unele dispozitive nu suport&#259; combina&#355;ia JavaScript/cadre necesar&#259; pentru aceasta caz &#238;n care aceast&#259; op&#355;iune ar trebui dezactivat&#259;. &#538;in&#226;nd cont c&#259; aceast&#259; op&#355;iune folose&#351;te JavaScript va avea efect doar dac&#259; este setat&#259; &#351;i op&#355;iunea ZM_WEB_REFRESH_METHOD."
    ),
    'WEB_EVENTS_PER_PAGE' => array(
        'Prompt' => "C&#226;te evenimente sunt afi&#351;ate pe pagin&#259;",
        'Help' => "&#206;n modul de vizualizare al evenimentelor pute&#355;i afi&#351;a toate evenimentele sau numai c&#226;te o pagin&#259;. Aceast&#259; op&#355;iune controleaz&#259; c&#226;te evenimente sunt afi&#351;ate &#238;ntr-o pagin&#259;."
    ),
    'WEB_FRAMES_PER_LINE' => array(
        'Prompt' => "C&#226;te cadre sunt afi&#351;ate pe linie",
        'Help' => "La vizualizarea cadrelor evenimentelor pute&#355;i vizualizare cadrele individuale care compun un eveniment. Aceast&#259; op&#355;iune v&#259; permite s&#259; specifica&#355;i c&#226;te cadre vor fi pe fiecare linie. Rezultatul acestei op&#355;iuni &#351;i al op&#355;iunii urm&#259;toare este num&#259;rul de cadre pe pagin&#259;."
    ),
    'WEB_FRAME_LINES' => array(
        'Prompt' => "C&#226;te linii cu cadre sunt afi&#351;ate",
        'Help' => "La vizualizarea cadrelor evenimentelor pute&#355;i vizualizare cadrele individuale care compun un eveniment. Aceast&#259; op&#355;iune v&#259; permite s&#259; specifica&#355;i c&#226;te linii cu cadre vor fi afi&#351;ate. Rezultatul acestei op&#355;iuni &#351;i al op&#355;iunii precedente este num&#259;rul de cadre pe pagin&#259;."
    ),
    'WEB_LIST_THUMBS' => array(
        'Prompt' => "Afi&#351;eaza miniaturi ale imaginilor &#238;n lista evenimentelor",
        'Help' => "&#206;n mod normal &#238;n lista evenimentelor sunt afi&#351;ate doar detaliile textuale ale evenimentelor pentru a se economisi spa&#355;iu &#351;i timp. La activarea aceastei op&#355;iuni vor fi afi&#351;ate &#351;i imagini miniaturale pentru a v&#259; ajuta s&#259; indentifica&#355;i evenimentele de interes. M&#259;rimea miniaturilor este controlat&#259; de urm&#259;toarele dou&#259; op&#355;iuni."
    ),
    'WEB_LIST_THUMB_WIDTH' => array(
        'Prompt' => "L&#259;&#355;imea miniaturilor ce apar &#238;n lista evenimentelor",
        'Help' => "Aceast&#259; op&#355;iune controleaz&#259; la&#355;imea imaginilor miniaturale care apar &#238;n lista evenimentelor. Ar trebui s&#259; fie destul de mic&#259; pentru a putea fi cuprins&#259; &#238;n restul tabelului. Dac&#259; dori&#355;i pute&#355;i specifica &#238;n&#259;l&#355;imea din urm&#259;toarea op&#355;iune dar folosi&#355;i doar una din cele dou&#259; op&#355;iuni cealalt&#259; av&#226;nd valoarea zero. Dac&#259; sunt specificate at&#226;t la&#355;imea c&#226;t &#351;i &#238;n&#259;l&#355;imea va fi folosit&#259; doar l&#259;&#355;imea, &#238;n&#259;l&#355;imea fiind ignorat&#259;."
    ),
    'WEB_LIST_THUMB_HEIGHT' => array(
        'Prompt' => "&#206;n&#259;l&#355;imea miniaturilor ce apar &#238;n lista evenimentelor",
        'Help' => "Aceast&#259; op&#355;iune controleaz&#259; &#238;n&#259;l&#355;imea imaginilor miniaturale care apar &#238;n lista evenimentelor. Ar trebui s&#259; fie destul de mic&#259; pentru a putea fi cuprins&#259; &#238;n restul tabelului. Dac&#259; dori&#355;i pute&#355;i specifica l&#259;&#355;imea din op&#355;iunea precedent&#259; dar folosi&#355;i doar una din cele dou&#259; op&#355;iuni cealalt&#259; av&#226;nd valoarea zero. Dac&#259; sunt specificate at&#226;t la&#355;imea c&#226;t &#351;i &#238;n&#259;l&#355;imea va fi folosit&#259; doar l&#259;&#355;imea, &#238;n&#259;l&#355;imea fiind ignorat&#259;."
    ),
// End of Web tab

// Beginning of Video tab
    'VIDEO_STREAM_METHOD' => array(
        'Prompt' => "Ce metod&#259; va fi folosit&#259; pentru a trimite imaginile la browser, alege&#355;i 'mpeg' sau 'jpeg'",
        'Help' => "ZoneMinder poate fi configurat fie s&#259; codeze capturile &#238;n format mpeg sau &#238;ntr-o serie de imagini statice. Aceast&#259; op&#355;iune define&#351;te metoda ce va fi folosit&#259;. Dac&#259; alege&#355;i mpeg asigura&#355;i-v&#259; c&#259; ave&#355;i plugin-urile necesare pt browser-ul dvs. Op&#355;iunea jpeg ruleaz&#259; pe instal&#259;ri implicite ale browser-elor din familia Mozilla &#351;i cu un applet Java pentru Internet Explorer."
    ),
    'VIDEO_TIMED_FRAMES' => array(
        'Prompt' => "Cadrele vor avea imprimate data &#351;i ora",
        'Help' => "C&#226;nd folosi&#355;i flux video MPEG, fie pentru flux video 'live' sau pentru evenimente, ZoneMinder poate trimite imaginile &#238;n dou&#259; feluri. Dac&#259; aceast&#259; op&#355;iune este setat&#259; atunci data &#351;i ora vor fi incluse &#238;n fluxul video. Acest lucru &#238;nseamn&#259; c&#259; atunci c&#226;nd rata cadrelor variaz&#259;, cum ar fi cazul unei alarme, fluxul &#238;&#351;i va men&#355;ine sincronizarea. Dac&#259; aceast&#259; op&#355;iune nu este activat&#259; atunci este calculat&#259; o rat&#259; aproximativ&#259; a cadrelor. Aceast&#259; op&#355;iune poate fi dezactivat&#259; dac&#259; ave&#355;i probleme cu metoda dvs. preferat&#259; de streaming."
    ),
    'VIDEO_LIVE_FORMAT' => array(
        'Prompt' => "&#206;n ce format sunt rulate fluxurile video 'live'",
        'Help' => "C&#226;nd folosi&#355;i metoda MPEG ZoneMinder poate genera secven&#355;e video. Formatele suportate de browser variaz&#259; de la un sistem la altul. Aceast&#259; op&#355;iune v&#259; permite s&#259; specifica&#355;i formatul video, folosind o extensie pentru fi&#351;iere, deci trebuie s&#259; introduce&#355;i doar extensia iar restul este determinat automat. Formatul implicit 'asf' func&#355;ioneaz&#259; pe Windows folosind Windows Media Player, iar pe Linux pute&#355;i folosi gxine sau mplayer. Dac&#259; aceast&#259; op&#355;iune nu este setat&#259; atunci fluxurile video 'live' vor fi secven&#355;e de fi&#351;iere jpeg."
    ),
    'VIDEO_REPLAY_FORMAT' => array(
        'Prompt' => "&#206;n ce format sunt redate fluxurile video",
        'Help' => "Folosind metoda MPEG ZoneMinder poate revizuliza evenimentele &#238;n format video codat. Formatele suportate de browser variaz&#259; de la un sistem la altul. Aceast&#259; op&#355;iune v&#259; permite s&#259; specifica&#355;i formatul video, folosind o extensie pentru fi&#351;iere, deci trebuie s&#259; introduce&#355;i doar extensia iar restul este determinat automat. Formatul implicit 'asf' func&#355;ioneaz&#259; pe Windows folosind Windows Media Player, iar pe Linux pute&#355;i folosi gxine sau mplayer. Dac&#259; aceast&#259; op&#355;iune nu este setat&#259; atunci fluxurile video vor fi secven&#355;e de fi&#351;iere jpeg."
    ),
// End of Video tab

// Beginning or Email tab
    'OPT_EMAIL' => array(
        'Prompt' => "Trimite e-mail cu detaliile evenimentelor corespunz&#259;toare anumitor filtre",
        'Help' => "&#206;n ZoneMinder pute&#355;i crea filtre pentru evenimente care specific&#259; dac&#259; detaliile evenimentelor filtrate sub un anumit criteriu vor fi trimise prin e-mail la o adres&#259; desemnat&#259;. Astfel ve&#355;i putea fi anun&#355;at imediat ce apar evenimente. Aceast&#259; op&#355;iune specific&#259; dac&#259; aceast&#259; func&#355;ie este activ&#259;. E-mail-ul creat cu aceast&#259; op&#355;iune poate fi de orice dimensiune &#351;i nu este dedicat dispozitivelor mobile."
    ),
    'EMAIL_ADDRESS' => array(
        'Prompt' => "E-mail-ul la care vor fi trimise detaliile evenimentelor",
        'Help' => "Aceast&#259; op&#355;iune este folosit&#259; pentru a defini adresa de e-mail la care vor fi trimise evenimentele corespunz&#259;toare filtrelor setate."
    ),
    'EMAIL_TEXT' => array(
        'Prompt' => "Con&#355;inutul e-mail-ului cu detaliile evenimentelor",
        'Help' => "Aceast&#259; op&#355;iune este folosit&#259; pentru a defini con&#355;inutul e-mail-ului trimis."
    ),
    'OPT_MESSAGE' => array(
        'Prompt' => "Trimite mesaj cu detaliile evenimentelor corespunz&#259;toare anumitor filtre (pentru dispozitive mobile) ",
        'Help' => "&#206;n ZoneMinder pute&#355;i crea filtre pentru evenimente care specific&#259; dac&#259; detaliile evenimentelor filtrate sub un anumit criteriu vor fi trimise prin e-mail la o adres&#259; desemnat&#259;. Astfel ve&#355;i putea fi anun&#355;at imediat ce apar evenimente. Aceast&#259; op&#355;iune specific&#259; dac&#259; aceast&#259; func&#355;ie este activ&#259;. E-mail-ul creat de aceast&#259; op&#355;iune va fi succint &#351;i este dedicat trimiterii lui c&#259;tre un gateway SMS sau c&#259;tre un cititor de e-mail minimal cum ar fi un dispozitiv mobil."
    ),
    'MESSAGE_ADDRESS' => array(
        'Prompt' => "E-mail-ul la care vor fi trimise detaliile evenimentelor",
        'Help' => "Aceast&#259; op&#355;iune este folosit&#259; pentru a defini adresa de e-mail la care va fi trasmis mesajul."
    ),
    'MESSAGE_TEXT' => array(
        'Prompt' => "Con&#355;inutul mesajului cu detaliile evenimentelor",
        'Help' => "Aceast&#259; op&#355;iune este folosit&#259; pentru a defini con&#355;inutul mesajului trimis."
    ),
    'EMAIL_METHOD' => array(
        'Prompt' => "Metoda folosit&#259; pentru trasmiterea e-mail-urilor &#351;i mesajelor",
        'Help' => "ZoneMinder trebuie s&#259; &#351;tie cum s&#259; trimit&#259; e-mail sau mesaj. Aceast&#259; op&#355;iune specific&#259; ce metod&#259; va fi folosit&#259;. &#206;n general 'sendmail' va func&#355;iona dac&#259; este configurat corespunz&#259;tor; &#238;n caz contrat alege&#355;i 'smtp' &#351;i specifica&#355;i gazda pe care ruleaz&#259; smtp &#238;n urm&#259;toare op&#355;iune."
    ),
    'EMAIL_HOST' => array(
        'Prompt' => "Gazda serverului SMTP",
        'Help' => "Dac&#259; a&#355;i ales SMTP ca metod&#259; de transmitere a e-mail-urilor &#351;i mesajelor atunci aceast&#259; op&#355;iune va specifica serverul SMTP folosit. Setarea implicit&#259;, localhost, s-ar putea s&#259; func&#355;ioneze dac&#259; ave&#355;i sendmail, exim sau un daemon similar; pute&#355;i introduce serverul SMTP de la ISP-ul dvs., de exemplu."
    ),
    'FROM_EMAIL' => array(
        'Prompt' => "E-mail-ul expeditor al notific&#259;rilor",
        'Help' => "E-mail-urile sau mesajele trimise de ZoneMinder pot avea ca e-mail expeditor o adres&#259; desemnat&#259; pentru a v&#259; ajuta s&#259; le identifica&#355;i. Este recomandat&#259; o adres&#259; de tipul ZoneMinder@domeniu.com."
    ),
    'URL' => array(
        'Prompt' => "Adresa (URL) unde este instalat ZoneMinder",
        'Help' => "E-mail-urile sau mesajele care va vor fi trimise pot include un link la evenimente pentru acces rapid. Dac&#259; dori&#355;i s&#259; folosi&#355;i aceast&#259; caracteristic&#259; atunci introduce&#355;i adresa unde este instalat ZoneMinder, de ex. http://gazda.domeniu.com/zm.php."
    ),
// End of Email tab

// Beginning of FTP tab
    'OPT_UPLOAD' => array(
        'Prompt' => "Upload evenimente care se potrivesc filtrelor corespunz&#259;toare.",
        'Help' => "&#206;n ZoneMinder pute&#355;i creea filtre pentru evenimente care specific&#259; dac&#259; evenimentele care corespund unui anumit criteriu sa fie upload-ate pe un server remote. Aceast&#259; op&#355;iune specific&#259; dac&#259; aceast&#259; func&#355;ie s&#259; fie disponibil&#259;."
    ),
    'UPLOAD_ARCH_FORMAT' => array(
        'Prompt' => "Ce format vor avea fi&#351;ierele &#238;nc&#259;rcate, 'tar' sau 'zip'",
        'Help' => "Evenimentele upload-ate pot fi &#238;n format .tar. sau .zip. Pentru a folosi aceast&#259; op&#355;iune trebuie s&#259; ave&#355;i instalate modulele perl Archive::Tar &#351;i/sau Archive::Zip."
    ),
    'UPLOAD_ARCH_COMPRESS' => array(
        'Prompt' => "Comprimare fi&#351;iere arhiv&#259;",
        'Help' => "Arhivele create pot fi comprimate. &#238;n general imaginile sunt deja comprimate &#351;i nu salva&#355;i prea mult spa&#355;iu activ&#226;nd aceast&#259; op&#355;iune. Activa&#355;i aceast&#259; op&#355;iune numai dac&#259; ave&#355;i resurse de irosit, spa&#355;iu sau bandwidth limitat."
    ),
    'UPLOAD_ARCH_ANALYSE' => array(
        'Prompt' => "Include analiza imaginilor &#238;n fi&#351;ierele &#238;nc&#259;rcate.",
        'Help' => "Arhivele create pot con&#355;ine numai cadre capturate sau cadrele capturate &#351;i analiza imaginilor care au generat alarme. Aceast&#259; op&#355;iune controleaz&#259; ce pot con&#355;ine arhivele. Include-&#355;i analiza numai dac&#259; ave&#355;i conexiune rapid&#259; la server-ul remote sau dac&#259; ave&#355;i nevoie de detalii despre cauza alarmei."
    ),
    'UPLOAD_FTP_HOST' => array(
        'Prompt' => "Server-ul la distan&#355;&#259; unde se &#238;ncarc&#259; fisiere",
        'Help' => "Acesta este serverul &#238;ndep&#259;rtat unde dori&#355;i s&#259; &#238;nc&#259;rca&#355;i evenimentele."
    ),
    'UPLOAD_FTP_USER' => array(
        'Prompt' => "Utilizator FTP",
        'Help' => "Utilizator FTP la serverul remote"
    ),
    'UPLOAD_FTP_PASS' => array(
        'Prompt' => "Parola FTP",
        'Help' => "Parola FTP la serverul remote"
    ),
    'UPLOAD_FTP_LOC_DIR' => array(
        'Prompt' => "Directorul &#238;n care vor fi create fi&#351;ierele ce urmeaz&#259; &#238;nc&#259;rcate",
        'Help' => "Directorul local &#238;n care vor fi create fi&#351;ierele ce urmeaz&#259; &#238;nc&#259;rcate"
    ),
    'UPLOAD_FTP_REM_DIR' => array(
        'Prompt' => "Directorul remote &#238;n care se &#238;ncarc&#259;",
        'Help' => ""
    ),
    'UPLOAD_FTP_TIMEOUT' => array(
        'Prompt' => "C&#226;t timp permitem pentru transferarea fiec&#259;rui fi&#351;ier.",
        'Help' => "C&#226;t timp (&#238;n secunde) permitem pentru transferarea fiec&#259;rui fi&#351;ier."
    ),
    'UPLOAD_FTP_PASSIVE' => array(
        'Prompt' => "FTP in mod pasiv",
        'Help' => "Dac&#259; computerul dvs. este &#238;n spatele unui firewall sau proxy s-ar putea s&#259; trebuiasc&#259; s&#259; folosi&#355;i FTP &#238;n mod pasiv."
    ),
    'UPLOAD_FTP_DEBUG' => array(
        'Prompt' => "FTP &#238;n mod debugging",
        'Help' => "Dac&#259; ave&#355;i probleme cu &#238;nc&#259;rcatul activa&#355;i aceast&#259; op&#355;iune, care va include informa&#355;ii suplimentare &#238;n logul zmfilter."
    ),
// End of FTP tab

// Beginning of X10 tab
    'OPT_X10' => array(
        'Prompt' => "Interac&#355;ioneaz&#259; cu dispozitive X10",
        'Help' => "Dac&#259; ave&#355;i un dispozitiv X10 pute&#355;i seta ZoneMinder s&#259; reac&#355;ioneze la semnalele emise de dispozitivul X10 dac&#259; computerul dvs. are controller-ul necesar. Aceast&#259; op&#355;iune indic&#259; dac&#259; op&#355;iunile X10 vor fi disponibile sau nu. "
    ),
    'X10_DEVICE' => array(
        'Prompt' => "Pe ce dispozitiv (software) este conectat dispozitivul X10",
        'Help' => "Dac&#259; ave&#355;i un controller X10 conectat la computerul dvs. aceast&#259; op&#355;iune specific&#259; pe ce port este conectat, valoare implicit&#259; /dev/ttyS0 reprezint&#259; portul serial sau portul COM 1."
    ),
    'X10_HOUSE_CODE' => array(
        'Prompt' => "Cod X10 folosit",
        'Help' => "Dispozitivele X10 sunt grupate indentific&#226;ndu-le ca apar&#355;in&#226;nd unui anumit cod al casei. Aceast&#259; op&#355;iune trebuie s&#259; fie o singur&#259; liter&#259; &#238;ntre A si P."
    ),
    'X10_DB_RELOAD_INTERVAL' => array(
        'Prompt' => "C&#226;t de des (&#238;n secunde) daemon-ul X10 actualizeaz&#259; monitoare din baza de date.",
        'Help' => "Daemon-ul zmx10 verific&#259; periodic baza de date pentru a descoperi eventualele alarme. Aceast&#259; op&#355;iune determin&#259; c&#226;t de des se face verificarea."
    ),
// End of FTP tab

// Beginning of Tools tab
    'CAN_STREAM' => array(
        'Prompt' => "&#206;nlocuie&#351;te detectarea automat&#259; a capacit&#259;&#355;ilor de streaming ale browser-ului",
        'Help' => "Dac&#259; &#351;ti&#355;i c&#259; browser-ul dvs. suport&#259; streaming de imagini dar ZoneMinder nu detecteaz&#259; aceast&#259; op&#355;iune corect pute&#355;i seta aceast&#259; op&#355;iune pentru a v&#259; asigura c&#259; fluxurile sunt transmise cu sau f&#259;r&#259; folosirea Cambozola. Selec&#355;ia 'yes' v-a spune ZoneMinder-ului c&#259; broswer-ul dvs. suport&#259; streaming  nativ, 'no' &#238;nseamn&#259; c&#259; nu suport&#259; deci va fi folosit Cambozola iar 'auto' v-a l&#259;sa ZoneMinder s&#259; decid&#259;."
    ),
    'RAND_STREAM' => array(
        'Prompt' => "Adaug&#259;re string aleator pentru a preveni tamponarea fluxurilor",
        'Help' => "Unele browsere pot &#238;nregistra &#238;n memoria tampon fluxurile folosite de ZoneMinder. Pentru a preveni acest lucru se poate adaug&#259; un string aleator pentru a face fiecare invocare a fluxului aparent unic&#259;."
    ),
    'OPT_CAMBOZOLA' => array(
        'Prompt' => "Este instalat(op&#355;ional) client-ul cambozola(recomandat)",
        'Help' => "Cambozola este un Java applet care este folosit de ZoneMinder pentru a fluxurile de imagini &#238;ntr-un navigator ca Internet Explorer. Este recomandat s&#259; instala&#355;i cambozola de la http://www.charliemouse.com/code/cambozola/ Chiar dac&#259; nu e instalat ve&#355;i putea vizualiza imagini statice la o rat&#259; mic&#259; de actulizare."
    ),
    'PATH_CAMBOZOLA' => array(
        'Prompt' => "Calea web la cambozola (recomandat)",
        'Help' => "Cambozola este un Java applet care este folosit de ZoneMinder pentru a fluxurile de imagini &#238;ntr-un navigator ca Internet Explorer. Este recomandat s&#259; instala&#355;i cambozola de la http://www.charliemouse.com/code/cambozola/ Chiar dac&#259; nu e instalat ve&#355;i putea vizualiza imagini statice la o rat&#259; mic&#259; de actulizare. Seta&#355;i aceast&#259; op&#355;iune 'camboloza.jar' dac&#259; cambozola este instalat &#238;n acela&#351;i director cu fi&#351;ierele web ZoneMinder. "
    ),
    'OPT_MPEG' => array(
        'Prompt' => "Este instalat codor video mpeg (op&#355;ional)",
        'Help' => "ZoneMinder poate &#238;nregistra o serie de imagini &#238;n format MPEG. Aceast&#259; op&#355;iune v&#259; permite s&#259; specifica&#355;i dac&#259; ave&#355;i un codor mpeg instalat. Cele dou&#259; codoare suportate de ZoneMinder sunt mpeg_encode &#351;i ffmpeg, ultimul fiind cel mai rapid. Crearea de fi&#351;iere MPEG consum&#259; resursele procesorului &#351;i nu este necesar&#259; deoarece evenimentele pot fi vizualizare ca flux video."
    ),
    'PATH_FFMPEG' => array(
        'Prompt' => "Calea la codorul mpeg ffmpeg (op&#355;ional)",
        'Help' => "Aceasta este calea la codorul mpeg ffmpeg."
    ),
    'FFMPEG_OPTIONS' => array(
        'Prompt' => "Op&#355;iuni adi&#355;ionale pentru ffmpeg",
        'Help' => "Ffmpeg suport&#259; multe op&#355;iuni pentru controlul calit&#259;&#355;ii secven&#355;ei video produse. Aceast&#259; op&#355;iune v&#259; permite s&#259; specifica&#355;i propriile op&#355;iuni. Citi&#355;i documenta&#355;ia ffmpeg pentru mai multe detalii."
    ),
    'OPT_TRIGGERS' => array(
        'Prompt' => "Interac&#355;ioneaz&#259; cu declan&#351;atoare externe via socket sau fi&#351;ierele dispozitivelor",
        'Help' => "ZoneMinder poate interac&#355;iona cu sisteme externe care ac&#355;ioneaz&#259; sau revoc&#259; o alarm&#259;. Acest lucru este realizat prin intermediului script-ului zmtrigger.pl. Aceast&#259; op&#355;iune indic&#259; folosirea declan&#351;atoarelor externe, majoritatea vor alege nu aici."
    ),

// End of Tools tab

// Beginning of High Banwidth tab
    'WEB_H_REFRESH_MAIN' => array(
        'Prompt' => "C&#226;t de des (&#238;n secunde) se va actualiza fereastra principal&#259;",
        'Help' => "&#206;n fereastra principal&#259; sunt afi&#351;ate starea general&#259; &#351;i totalul evenimentelor pentru toate monitoarele. Aceast&#259; sarcin&#259; nu trebuie repetat&#259; frecvent; s-ar putea s&#259; afecteze performan&#355;a sistemului."
    ),
    'WEB_H_REFRESH_CYCLE' => array(
        'Prompt' => "C&#226;t de des (&#238;n secunde) se vor schimba imaginile &#238;n ciclul de monitorizare.",
        'Help' => "Ciclul de monitorizare este metoda de schimbare continu&#259; a imaginilor monitoarelor. Aceast&#259; op&#355;iune determin&#259; c&#226;t de des va fi actulizat cu o nou&#259; imagine."
    ),
    'WEB_H_REFRESH_IMAGE' => array(
        'Prompt' => "C&#226;t de des (&#238;n secunde) sunt actulizate imaginile statice",
        'Help' => "Imaginile 'live' ale unui monitor pot fi vizulizate &#238;n flux de imagini (video) sau imagini statice. Aceast&#259; op&#355;iune determin&#259; c&#226;t de des vor fi actualizate imaginile statice, nu are nici un efect dac&#259; este selectat&#259; metoda flux video (streaming)."
    ),
    'WEB_H_REFRESH_STATUS' => array(
        'Prompt' => "C&#226;t de des va fi actualizat cadrul de stare",
        'Help' => "Fereastra monitorului este alc&#259;tuit&#259; din mai multe cadre. Cadrul din mijloc con&#355;ine starea monitorului &#351;i trebuie actualizat&#259; destul de frecvent pentru a indica valori reale. Aceast&#259; op&#355;iune determin&#259; frecven&#355;a respectiv&#259;."
    ),
    'WEB_H_REFRESH_EVENTS' => array(
        'Prompt' => "C&#226;t de des (&#238;n secunde) este actulizat&#259; lista evenimentelor din fereastra principal&#259;",
        'Help' => "Fereastra monitorului este alc&#259;tuit&#259; din mai multe cadre. Cadrul inferior con&#355;ine o list&#259; a ultimelor evenimente pentru acces rapid. Aceast&#259; op&#355;iune determin&#259; c&#226;t de des este actulizat acest cadru."
    ),
    'WEB_H_DEFAULT_SCALE' => array(
        'Prompt' => "Care este scara implicit&#259; ce se aplica vizualiz&#259;rii 'live' sau a evenimentelor (%)",
        'Help' => "&#206;n mod normal ZoneMinder va afi&#351;a fluxurile 'live' sau evenimentele &#238;n marime nativ&#259;. Dac&#259; ave&#355;i monitoare de dimensiuni mari pute&#355;i reduce aceast&#259; m&#259;rime, iar pentru monitoare de dimensiuni mici pute&#355;i redimensiona &#238;n sens pozitiv aceast&#259; m&#259;rime. Prin intermediul acestei op&#355;iuni pute&#355;i specifica care va fi factorul implicit de scar&#259;. Este exprimat &#238;n procente deci 100 va fi dimensiune normal&#259;, 200 dimensiune dubl&#259; etc."
    ),
    'WEB_H_DEFAULT_RATE' => array(
        'Prompt' => "Viteza de redare a evenimentelor (%)",
        'Help' => "&#206;n mod normal ZoneMinder va afi&#351;a fluxurile video la viteza lor nativ&#259;. Dac&#259; ave&#355;i evenimente de lung&#259; durat&#259; este mai convenabil&#259; redarea lor la o rat&#259; mai mare. Aceast&#259; op&#355;iune v&#259; permite sa specifica&#355;i rata de redare. Este exprimat&#259; &#238;n procente deci 100 este rata normal&#259;, 200 este vitez&#259; dubl&#259;, etc."
    ),
    'WEB_H_VIDEO_BITRATE' => array(
        'Prompt' => "Rata bi&#355;ilor (bit rate) la care este codat fluxul video",
        'Help' => "La codarea secven&#355;elor video prin intermediul libr&#259;riei ffmpeg poate fi specificat&#259; o rat&#259; a bi&#355;ilor (bit rate) care corespunde, &#238;n linii mari, l&#259;&#355;imii de band&#259; disponibil&#259;. Aceast&#259; op&#355;iune corespunde calit&#259;&#355;ii secven&#355;ei video. O valoare mic&#259; v-a avea ca rezultat imagine incert&#259; iar o valoare mare v-a produce o imagine mai clar&#259;. Aceast&#259; op&#355;iune nu controleaz&#259; frecven&#355;a cadrelor, de&#351;i calitatea secven&#355;elor video este influen&#355;at&#259; at&#226;t de aceast&#259; op&#355;iune c&#226;t &#351;i de frecven&#355;a cadrelor la care este produs&#259; secven&#355;a video."
    ),
    'WEB_H_VIDEO_MAXFPS' => array(
        'Prompt' => "Frecven&#355;a maxim&#259; a cadrelor pentru fluxurile video",
        'Help' => "La folosirea fluxurilor video factorul principal de control este rata bi&#355;ilor care determin&#259; cantitatea de date care poate fi transmis&#259;. Totu&#351;i o rata mic&#259; la frecven&#355;&#259; mare a cadrelor nu va avea rezultate calitative. Aceast&#259; op&#355;iune v&#259; permite s&#259; limita&#355;i frecven&#355;a maxim&#259; a cadrelor pentru a asigura calitatea imaginii. Un avantaj adi&#355;ional este c&#259; &#238;nregistrarea la frecven&#355;e mari poate consuma multe resurse f&#259;r&#259; s&#259; ofere rezultate calitative satisf&#259;catoare, fa&#355;&#259; de &#238;nregistrarea unde se menajeaz&#259; resursele. Aceast&#259; op&#355;iune este implementat&#259; ca surplus dincolo de reduc&#355;ia binar&#259;. Deci dac&#259; ave&#355;i un dispozitiv care captureaz&#259; la 15fps &#351;i seta&#355;i aceast&#259; op&#355;iune la 10fps atunci secven&#355;a video nu este produs&#259; la 10fps, ci la 7,5fps (15/2) deoarece frecven&#355;a finala a cadrelor trebuie s&#259; fie frecven&#355;a ini&#355;iala &#238;mp&#259;r&#355;it&#259; la un num&#259;r putere a num&#259;rului 2."
    ),
    'WEB_H_IMAGE_SCALING' => array(
        'Prompt' => "Scala miniaturilor &#238;n evenimente, bandwidth vs. cpu pentru rescalare",
        'Help' =>"Valoare 1 v-a transmite la browser imaginea complet&#259; care va fi redimensionata &#238;n fereastr&#259;, valori mai mari vor mic&#351;ora imaginea &#238;nainte de a transmite o imagine miniatural&#259; la browser. Pentru la&#355;ime de band&#259; mare setare implicit&#259; 1 este de obicei cea mai rapid&#259; &#351;i nu produce imagini miniaturale externe."
    ),
// End of High Banwidth tab

// Beginning of Medium Bandwidth tab
    'WEB_M_REFRESH_MAIN' => array(
        'Prompt' => "C&#226;t de des (&#238;n secunde) se va actualiza fereastra principal&#259;",
        'Help' => "&#206;n fereastra principal&#259; sunt afi&#351;ate starea general&#259; &#351;i totalul evenimentelor pentru toate monitoarele. Aceast&#259; sarcin&#259; nu trebuie repetat&#259; frecvent; s-ar putea s&#259; afecteze performan&#355;a sistemului."
    ),
    'WEB_M_REFRESH_CYCLE' => array(
        'Prompt' => "C&#226;t de des (&#238;n secunde) se vor schimba imaginile &#238;n ciclul de monitorizare.",
        'Help' => "Ciclul de monitorizare este metoda de schimbare continu&#259; a imaginilor monitoarelor. Aceast&#259; op&#355;iune determin&#259; c&#226;t de des va fi actulizat cu o nou&#259; imagine."
    ),
    'WEB_M_REFRESH_IMAGE' => array(
        'Prompt' => "C&#226;t de des (&#238;n secunde) sunt actulizate imaginile statice",
        'Help' => "Imaginile 'live' ale unui monitor pot fi vizulizate &#238;n flux de imagini (video) sau imagini statice. Aceast&#259; op&#355;iune determin&#259; c&#226;t de des vor fi actualizate imaginile statice, nu are nici un efect dac&#259; este selectat&#259; metoda flux video (streaming)."
    ),
    'WEB_M_REFRESH_STATUS' => array(
        'Prompt' => "C&#226;t de des va fi actualizat cadrul de stare",
        'Help' => "Fereastra monitorului este alc&#259;tuit&#259; din mai multe cadre. Cadrul din mijloc con&#355;ine starea monitorului &#351;i trebuie actualizat&#259; destul de frecvent pentru a indica valori reale. Aceast&#259; op&#355;iune determin&#259; frecven&#355;a respectiv&#259;."
    ),
    'WEB_M_REFRESH_EVENTS' => array(
        'Prompt' => "C&#226;t de des (&#238;n secunde) este actulizat&#259; lista evenimentelor din fereastra principal&#259;",
        'Help' => "Fereastra monitorului este alc&#259;tuit&#259; din mai multe cadre. Cadrul inferior con&#355;ine o list&#259; a ultimelor evenimente pentru acces rapid. Aceast&#259; op&#355;iune determin&#259; c&#226;t de des este actulizat acest cadru."
    ),
    'WEB_M_DEFAULT_SCALE' => array(
        'Prompt' => "Care este scara implicit&#259; ce se aplica vizualiz&#259;rii 'live' sau a evenimentelor (%)",
        'Help' => "&#206;n mod normal ZoneMinder va afi&#351;a fluxurile 'live' sau evenimentele &#238;n marime nativ&#259;. Dac&#259; ave&#355;i monitoare de dimensiuni mari pute&#355;i reduce aceast&#259; m&#259;rime, iar pentru monitoare de dimensiuni mici pute&#355;i redimensiona &#238;n sens pozitiv aceast&#259; m&#259;rime. Prin intermediul acestei op&#355;iuni pute&#355;i specifica care va fi factorul implicit de scar&#259;. Este exprimat &#238;n procente deci 100 va fi dimensiune normal&#259;, 200 dimensiune dubl&#259; etc."
    ),
    'WEB_M_DEFAULT_RATE' => array(
        'Prompt' => "Viteza de redare a evenimentelor (%)",
        'Help' => "&#206;n mod normal ZoneMinder va afi&#351;a fluxurile video la viteza lor nativ&#259;. Dac&#259; ave&#355;i evenimente de lung&#259; durat&#259; este mai convenabil&#259; redarea lor la o rat&#259; mai mare. Aceast&#259; op&#355;iune v&#259; permite sa specifica&#355;i rata de redare. Este exprimat&#259; &#238;n procente deci 100 este rata normal&#259;, 200 este vitez&#259; dubl&#259;, etc."
    ),
    'WEB_M_VIDEO_BITRATE' => array(
        'Prompt' => "Rata bi&#355;ilor (bit rate) la care este codat fluxul video",
        'Help' => "La codarea secven&#355;elor video prin intermediul libr&#259;riei ffmpeg poate fi specificat&#259; o rat&#259; a bi&#355;ilor (bit rate) care corespunde, &#238;n linii mari, l&#259;&#355;imii de band&#259; disponibil&#259;. Aceast&#259; op&#355;iune corespunde calit&#259;&#355;ii secven&#355;ei video. O valoare mic&#259; v-a avea ca rezultat imagine incert&#259; iar o valoare mare v-a produce o imagine mai clar&#259;. Aceast&#259; op&#355;iune nu controleaz&#259; frecven&#355;a cadrelor, de&#351;i calitatea secven&#355;elor video este influen&#355;at&#259; at&#226;t de aceast&#259; op&#355;iune c&#226;t &#351;i de frecven&#355;a cadrelor la care este produs&#259; secven&#355;a video."
    ),
    'WEB_M_VIDEO_MAXFPS' => array(
        'Prompt' => "Frecven&#355;a maxim&#259; a cadrelor pentru fluxurile video",
        'Help' => "La folosirea fluxurilor video factorul principal de control este rata bi&#355;ilor care determin&#259; cantitatea de date care poate fi transmis&#259;. Totu&#351;i o rata mic&#259; la frecven&#355;&#259; mare a cadrelor nu va avea rezultate calitative. Aceast&#259; op&#355;iune v&#259; permite s&#259; limita&#355;i frecven&#355;a maxim&#259; a cadrelor pentru a asigura calitatea imaginii. Un avantaj adi&#355;ional este c&#259; &#238;nregistrarea la frecven&#355;e mari poate consuma multe resurse f&#259;r&#259; s&#259; ofere rezultate calitative satisf&#259;catoare, fa&#355;&#259; de &#238;nregistrarea unde se menajeaz&#259; resursele. Aceast&#259; op&#355;iune este implementat&#259; ca surplus dincolo de reduc&#355;ia binar&#259;. Deci dac&#259; ave&#355;i un dispozitiv care captureaz&#259; la 15fps &#351;i seta&#355;i aceast&#259; op&#355;iune la 10fps atunci secven&#355;a video nu este produs&#259; la 10fps, ci la 7,5fps (15/2) deoarece frecven&#355;a finala a cadrelor trebuie s&#259; fie frecven&#355;a ini&#355;iala &#238;mp&#259;r&#355;it&#259; la un num&#259;r putere a num&#259;rului 2."
    ),
    'WEB_M_IMAGE_SCALING' => array(
        'Prompt' => "Scala miniaturilor &#238;n evenimente, bandwidth vs. cpu pentru rescalare",
        'Help' => "Valoare 1 v-a transmite la browser imaginea complet&#259; care va fi redimensionata &#238;n fereastr&#259;, valori mai mari vor mic&#351;ora imaginea &#238;nainte de a transmite o imagine miniatural&#259; la browser. Pentru la&#355;ime de band&#259; medie setare implicit&#259; 4 este de obicei cea mai rapid&#259; dar e posibil ca &#351;i valoare 1 s&#259; fie acceptabil&#259;"
    ),
// End of Medium Bandwidth tab

// Beginning of Low Bandwidth tab
    'WEB_L_REFRESH_MAIN' => array(
        'Prompt' => "C&#226;t de des (&#238;n secunde) se va actualiza fereastra principal&#259;",
        'Help' => "&#206;n fereastra principal&#259; sunt afi&#351;ate starea general&#259; &#351;i totalul evenimentelor pentru toate monitoarele. Aceast&#259; sarcin&#259; nu trebuie repetat&#259; frecvent; s-ar putea s&#259; afecteze performan&#355;a sistemului."
    ),
    'WEB_L_REFRESH_CYCLE' => array(
        'Prompt' => "C&#226;t de des (&#238;n secunde) se vor schimba imaginile &#238;n ciclul de monitorizare.",
        'Help' => "Ciclul de monitorizare este metoda de schimbare continu&#259; a imaginilor monitoarelor. Aceast&#259; op&#355;iune determin&#259; c&#226;t de des va fi actulizat cu o nou&#259; imagine."
    ),
    'WEB_L_REFRESH_IMAGE' => array(
        'Prompt' => "C&#226;t de des (&#238;n secunde) sunt actulizate imaginile statice",
        'Help' => "Imaginile 'live' ale unui monitor pot fi vizulizate &#238;n flux de imagini (video) sau imagini statice. Aceast&#259; op&#355;iune determin&#259; c&#226;t de des vor fi actualizate imaginile statice, nu are nici un efect dac&#259; este selectat&#259; metoda flux video (streaming)."
    ),
    'WEB_L_REFRESH_STATUS' => array(
        'Prompt' => "C&#226;t de des va fi actualizat cadrul de stare",
        'Help' => "Fereastra monitorului este alc&#259;tuit&#259; din mai multe cadre. Cadrul din mijloc con&#355;ine starea monitorului &#351;i trebuie actualizat&#259; destul de frecvent pentru a indica valori reale. Aceast&#259; op&#355;iune determin&#259; frecven&#355;a respectiv&#259;."
    ),
    'WEB_L_REFRESH_EVENTS' => array(
        'Prompt' => "C&#226;t de des (&#238;n secunde) este actulizat&#259; lista evenimentelor din fereastra principal&#259;",
        'Help' => "Fereastra monitorului este alc&#259;tuit&#259; din mai multe cadre. Cadrul inferior con&#355;ine o list&#259; a ultimelor evenimente pentru acces rapid. Aceast&#259; op&#355;iune determin&#259; c&#226;t de des este actulizat acest cadru."
    ),
    'WEB_L_DEFAULT_SCALE' => array(
        'Prompt' => "Care este scara implicit&#259; ce se aplica vizualiz&#259;rii 'live' sau a evenimentelor (%)",
        'Help' => "&#206;n mod normal ZoneMinder va afi&#351;a fluxurile 'live' sau evenimentele &#238;n marime nativ&#259;. Dac&#259; ave&#355;i monitoare de dimensiuni mari pute&#355;i reduce aceast&#259; m&#259;rime, iar pentru monitoare de dimensiuni mici pute&#355;i redimensiona &#238;n sens pozitiv aceast&#259; m&#259;rime. Prin intermediul acestei op&#355;iuni pute&#355;i specifica care va fi factorul implicit de scar&#259;. Este exprimat &#238;n procente deci 100 va fi dimensiune normal&#259;, 200 dimensiune dubl&#259; etc."
    ),
    'WEB_L_DEFAULT_RATE' => array(
        'Prompt' => "Viteza de redare a evenimentelor (%)",
        'Help' => "&#206;n mod normal ZoneMinder va afi&#351;a fluxurile video la viteza lor nativ&#259;. Dac&#259; ave&#355;i evenimente de lung&#259; durat&#259; este mai convenabil&#259; redarea lor la o rat&#259; mai mare. Aceast&#259; op&#355;iune v&#259; permite sa specifica&#355;i rata de redare. Este exprimat&#259; &#238;n procente deci 100 este rata normal&#259;, 200 este vitez&#259; dubl&#259;, etc."
    ),
    'WEB_L_VIDEO_BITRATE' => array(
        'Prompt' => "Rata bi&#355;ilor (bit rate) la care este codat fluxul video",
        'Help' => "La codarea secven&#355;elor video prin intermediul libr&#259;riei ffmpeg poate fi specificat&#259; o rat&#259; a bi&#355;ilor (bit rate) care corespunde, &#238;n linii mari, l&#259;&#355;imii de band&#259; disponibil&#259;. Aceast&#259; op&#355;iune corespunde calit&#259;&#355;ii secven&#355;ei video. O valoare mic&#259; v-a avea ca rezultat imagine incert&#259; iar o valoare mare v-a produce o imagine mai clar&#259;. Aceast&#259; op&#355;iune nu controleaz&#259; frecven&#355;a cadrelor, de&#351;i calitatea secven&#355;elor video este influen&#355;at&#259; at&#226;t de aceast&#259; op&#355;iune c&#226;t &#351;i de frecven&#355;a cadrelor la care este produs&#259; secven&#355;a video."
    ),
    'WEB_L_VIDEO_MAXFPS' => array(
        'Prompt' => "Frecven&#355;a maxim&#259; a cadrelor pentru fluxurile video",
        'Help' => "La folosirea fluxurilor video factorul principal de control este rata bi&#355;ilor care determin&#259; cantitatea de date care poate fi transmis&#259;. Totu&#351;i o rata mic&#259; la frecven&#355;&#259; mare a cadrelor nu va avea rezultate calitative. Aceast&#259; op&#355;iune v&#259; permite s&#259; limita&#355;i frecven&#355;a maxim&#259; a cadrelor pentru a asigura calitatea imaginii. Un avantaj adi&#355;ional este c&#259; &#238;nregistrarea la frecven&#355;e mari poate consuma multe resurse f&#259;r&#259; s&#259; ofere rezultate calitative satisf&#259;catoare, fa&#355;&#259; de &#238;nregistrarea unde se menajeaz&#259; resursele. Aceast&#259; op&#355;iune este implementat&#259; ca surplus dincolo de reduc&#355;ia binar&#259;. Deci dac&#259; ave&#355;i un dispozitiv care captureaz&#259; la 15fps &#351;i seta&#355;i aceast&#259; op&#355;iune la 10fps atunci secven&#355;a video nu este produs&#259; la 10fps, ci la 7,5fps (15/2) deoarece frecven&#355;a finala a cadrelor trebuie s&#259; fie frecven&#355;a ini&#355;iala &#238;mp&#259;r&#355;it&#259; la un num&#259;r putere a num&#259;rului 2."
    ),
    'WEB_L_IMAGE_SCALING' => array(
        'Prompt' => "Scala miniaturilor &#238;n evenimente, bandwidth vs. cpu pentru rescalare",
        'Help' => "Valoare 1 v-a transmite la browser imaginea complet&#259; care va fi redimensionata &#238;n fereastr&#259;, valori mai mari vor mic&#351;ora imaginea &#238;nainte de a transmite o imagine miniatural&#259; la browser. Pentru la&#355;ime de band&#259; redus&#259; setare implicit&#259; 4 este de obicei cea mai rapid&#259;."
    ),
// End of Low Bandwidth tab

// Beginning of Phone Bandwidth tab
    'WEB_P_REFRESH_MAIN' => array(
        'Prompt' => "C&#226;t de des (&#238;n secunde) se va actualiza fereastra principal&#259;",
        'Help' => "&#206;n fereastra principal&#259; sunt afi&#351;ate starea general&#259; &#351;i totalul evenimentelor pentru toate monitoarele. Aceast&#259; sarcin&#259; nu trebuie repetat&#259; frecvent; s-ar putea s&#259; afecteze performan&#355;a sistemului."
    ),
    'WEB_P_REFRESH_IMAGE' => array(
        'Prompt' => "C&#226;t de des (&#238;n secunde) se vor schimba imaginile &#238;n ciclul de monitorizare.",
        'Help' => "Ciclul de monitorizare este metoda de schimbare continu&#259; a imaginilor monitoarelor. Aceast&#259; op&#355;iune determin&#259; c&#226;t de des va fi actulizat cu o nou&#259; imagine."
    ),
    
// Options on Monitor view
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


// End of Phone Bandwidth tab
//
);
?>
