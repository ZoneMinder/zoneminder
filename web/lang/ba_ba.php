<?php
//
// ZoneMinder web UK English language file, $Date$, $Revision$
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

// ZoneMinder Bosnian Translation by Damir Merdan (merdan.damir@gmail.com)

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
    'SystemLog'             => 'Dnevnik',
    'DateTime'              => 'Datum/Vrijeme',
    'Component'             => 'Komponenta',
    'Pid'                   => 'PID',
    'Level'                 => 'Nivo',
    'Message'               => 'Poruka',
    'Line'                  => 'Linija',
    'More'                  => 'Više',
    'Clear'                 => 'Očisti',
    '24BitColour'           => '24 bitne boje',
    '32BitColour'           => '32 bitne boje',
    '8BitGrey'              => '8 bit siva nijansa',
    'Action'                => 'Action',
    'Actual'                => 'Stvarno',
    'AddNewControl'         => 'Dodaj kontrolu',
    'AddNewMonitor'         => 'Dodaj monitor',
    'AddNewServer'          => 'Dodaj novi server',
    'AddNewStorage'         => 'Dodaj novi disk',
    'AddNewUser'            => 'Dodaj novog korisnika',
    'AddNewZone'            => 'Dodaj novu zonu',
    'Alarm'                 => 'Alarm',
    'AlarmBrFrames'         => 'Alarm<br/>Sličice',
    'AlarmFrame'            => 'Alarm sličica',
    'AlarmFrameCount'       => 'Brzina snimanja alarma (u frejmovima)',
    'AlarmLimits'           => 'Alarm limiti',
    'AlarmMaximumFPS'       => 'Alarm Max SPS',
    'AlarmPx'               => 'Alarm Px',
    'AlarmRefImageBlendPct' => 'Alarm Reference Image Blend %ge',
    'AlarmRGBUnset'         => 'Morate postaviti RGB boju za alarm',
    'Alert'                 => 'Uzbuna',
    'All'                   => 'Sve',
    'AnalysisFPS'           => 'Analiza frejmova',
    'AnalysisUpdateDelay'   => 'Analysis Update Delay',
    'Apply'                 => 'Primjeni',
    'ApplyingStateChange'   => 'Primjenjujem promjenu stanja',
    'ArchArchived'          => 'Samo arhivirano',
    'Archive'               => 'Arhiva',
    'Archived'              => 'Ahivirano',
    'ArchUnarchived'        => 'Samo nearhivirano',
    'Area'                  => 'Oblast',
    'AreaUnits'             => 'Oblast (px/%)',
    'AttrAlarmFrames'       => 'Alarm frejmovi',
    'AttrArchiveStatus'     => 'Status arhive',
    'AttrAvgScore'          => 'Prosj. score',
    'AttrCause'             => 'Uzrok',
    'AttrStartDate'         => 'Pocetni datum',
    'AttrEndDate'           => 'Krajnji datum',
    'AttrStartDateTime'     => 'Pocetni Datum/Vrijeme',
    'AttrEndDateTime'       => 'Krajnji Datum/Vrijeme',
    'AttrDiskSpace'         => 'Disk prostor',
    'AttrDiskBlocks'        => 'Disk blokovi',
    'AttrDiskPercent'       => 'Disk procentualno',
    'AttrDuration'          => 'Trajanje',
    'AttrFrames'            => 'Frejmovi',
    'AttrId'                => 'Id',
    'AttrMaxScore'          => 'Max. Score',
    'AttrMonitorId'         => 'ID Kamere',
    'AttrMonitorName'       => 'Naziv Kamere',
    'AttrStorageArea'       => 'Storage Area',
    'AttrFilterServer'      => 'Server Filter je pokrenut na',
    'AttrMonitorServer'     => 'Server Monitor je pokrenut na',
    'AttrStorageServer'     => 'Server Hosting Storage',
    'AttrStateId'           => 'Status',
    'AttrName'              => 'Naziv',
    'AttrNotes'             => 'Bilješke',
    'AttrSystemLoad'        => 'Opterećenje sistema',
    'AttrStartTime'         => 'Vrijeme početka',
    'AttrEndTime'           => 'Vrijeme završetka',
    'AttrTotalScore'        => 'Ukupan score',
    'AttrStartWeekday'      => 'Početni dan',
    'AttrEndWeekday'        => 'Krajnji dan',
    'Auto'                  => 'Automatski',
    'AutoStopTimeout'       => 'Auto Stop Timeout',
    'Available'             => 'Dostupno',
    'AvgBrScore'            => 'Avg.<br/>Score',
    'Available'             => 'Dostupno',
    'Background'            => 'Pozadina',
    'BackgroundFilter'      => 'Pokreni filter u pozadini',
    'BadAlarmFrameCount'    => 'Brojač alarm frejmova mora biti tipa integer počevši od jedan ili više',
    'BadAlarmMaxFPS'        => 'Max FPS za alarm mora biti pozitivan cjeli broj ili broj sa pomičnim zarezom',
    'BadAnalysisFPS'        => 'Broj frejmova za analitiku mora pozitivan cjeli broj ili broj sa pomičnim zarezom',
    'BadAnalysisUpdateDelay'=> 'Vrijeme zadrške analitike mora biti broj od nula ili više',
    'BadChannel'            => 'Kanal mora biti postavljen na cjeli broj nula ili više',
    'BadDevice'             => 'Uredaj mora biti postavljen na validnu vrijednost',
    'BadFormat'             => 'Format mora biti postavljen na validnu vrijenost',
    'BadFPSReportInterval'  => 'FPS report interval buffer count must be an integer of 0 or more',
    'BadFrameSkip'          => 'Frame skip count must be an integer of zero or more',
    'BadMotionFrameSkip'    => 'Motion Frame skip count must be an integer of zero or more',
    'BadHeight'             => 'Height must be set to a valid value',
    'BadHost'               => 'Host must be set to a valid ip address or hostname, do not include http://',
    'BadImageBufferCount'   => 'Image buffer size must be an integer of 10 or more',
    'BadLabelX'             => 'Label X co-ordinate must be set to an integer of zero or more',
    'BadLabelY'             => 'Label Y co-ordinate must be set to an integer of zero or more',
    'BadMaxFPS'             => 'Maximum FPS must be a positive integer or floating point value',
    'BadNameChars'          => 'Names may only contain alphanumeric characters plus spaces, hyphen and underscore',
    'BadPalette'            => 'Palette must be set to a valid value',
    'BadColours'            => 'Target colour must be set to a valid value',
    'BadPath'               => 'Path must be set to a valid value',
    'BadPort'               => 'Port must be set to a valid number',
    'BadPostEventCount'     => 'Post event image count must be an integer of zero or more',
    'BadPreEventCount'      => 'Pre event image count must be at least zero, and less than image buffer size',
    'BadRefBlendPerc'       => 'Reference blend percentage must be a positive integer',
    'BadSectionLength'      => 'Section length must be an integer of 30 or more',
    'BadSignalCheckColour'  => 'Signal check colour must be a valid RGB colour string',
    'BadStreamReplayBuffer' => 'Stream replay buffer must be an integer of zero or more',
    'BadSourceType'         => 'Source Type \"Web Site\" requires the Function to be set to \"Monitor\"',
    'BadWarmupCount'        => 'Warmup frames must be an integer of zero or more',
    'BadWebColour'          => 'Web colour must be a valid web colour string',
    'BadWebSitePath'        => 'Please enter a complete website url, including the http:// or https:// prefix.',
    'BadWidth'              => 'Width must be set to a valid value',
    'Bandwidth'             => 'Propusnost',
    'BandwidthHead'         => 'propusnost',	// This is the end of the bandwidth status on the top of the console, different in many language due to phrasing
    'BlobPx'                => 'Blob Px',
    'Blobs'                 => 'Blobs',
    'BlobSizes'             => 'Blob velicine',
    'Brightness'            => 'Svjetloća',
    'Buffer'                => 'Bufer',
    'Buffers'               => 'Buferi',
    'CanAutoFocus'          => 'Podržava Auto fokusiranje',
    'CanAutoGain'           => 'Podržava Auto pojačanje',
    'CanAutoIris'           => 'Podržava Auto blenda',
    'CanAutoWhite'          => 'Podržava Auto balans bijel.',
    'CanAutoZoom'           => 'Podržava Auto zum',
    'Cancel'                => 'otkaži',
    'CancelForcedAlarm'     => 'Otkaži prisilni alarm',
    'CanFocusAbs'           => 'Podržava Abs fokus',
    'CanFocus'              => 'Podržava Fokus',
    'CanFocusCon'           => 'Podržava Kontinuirani fokus',
    'CanFocusRel'           => 'Podržava Relativni fokus',
    'CanGainAbs'            => 'Podržava Aps. pojačanje',
    'CanGain'               => 'Podržava Pojačanje ',
    'CanGainCon'            => 'Podržava Kontinuirano pojačanje',
    'CanGainRel'            => 'Podržava Relativno pojačanje',
    'CanIrisAbs'            => 'Podržava Aps. blenda',
    'CanIris'               => 'Podržava Blenda',
    'CanIrisCon'            => 'Podržava kontinuirana blenda',
    'CanIrisRel'            => 'Podržava Relativna blenda',
    'CanMoveAbs'            => 'Podržava Aps. kretanje',
    'CanMove'               => 'Podržava Kretanje',
    'CanMoveCon'            => 'Podržava Kontinuirano kretanje',
    'CanMoveDiag'           => 'Podržava Dijagonalno kretanje',
    'CanMoveMap'            => 'Podržava Mapirano kretanje',
    'CanMoveRel'            => 'Podržava Relativno kretanje',
    'CanPan'                => 'Podržava Pomak' ,
    'CanReset'              => 'PodržavaReset',
	'CanReboot'             => 'Can Reboot',
    'CanSetPresets'         => 'Podržava presetove',
    'CanSleep'              => 'Podržava Sleep',
    'CanTilt'               => 'Podržava nagib',
    'CanWake'               => 'Podržava Wake',
    'CanWhiteAbs'           => 'Podržava Aps. balans bijele boje',
    'CanWhiteBal'           => 'Podržava balans bijel.',
    'CanWhite'              => 'Podržava bijelu',
    'CanWhiteCon'           => 'Podržava kont. balans bijele boje',
    'CanWhiteRel'           => 'Podržava relativ. balans bijele boje',
    'CanZoomAbs'            => 'Podržava Aps. zoom',
    'CanZoom'               => 'Podržava Zoom',
    'CanZoomCon'            => 'Podržava kontinuirani Zoom',
    'CanZoomRel'            => 'Podržava Relativni zoom',
    'CaptureHeight'         => 'Visina slike',
    'CaptureMethod'         => 'Metoda snimanja',
    'CaptureResolution'     => 'Snimi rezoluciju',
    'CapturePalette'        => 'Paleta boja',
    'CaptureWidth'          => 'Širina slike',
    'Cause'                 => 'Uzrok',
    'CheckMethod'           => 'Metoda provjere alarma',
    'ChooseDetectedCamera'  => 'Odaberi otkrivenu kameru',
    'ChooseFilter'          => 'Odaberi filter',
    'ChooseLogFormat'       => 'Odaberi dugi format',
    'ChooseLogSelection'    => 'Odaberi dugu selekciju',
    'ChoosePreset'          => 'Odaberi preset',
    'CloneMonitor'          => 'Kloniraj',
    'Close'                 => 'Zatvori',
    'Colour'                => 'Bojs',
    'Command'               => 'Komanda',
    'ConcurrentFilter'      => 'Istovremeno pokreni filter',
    'Config'                => 'Postavke',
    'ConfiguredFor'         => 'Podešeno za',
    'ConfirmDeleteEvents'   => 'Sigurni ste da želite izbrisati odabrane događaje?',
    'ConfirmPassword'       => 'Potvrdi lozinku',
    'ConjAnd'               => 'i',
    'ConjOr'                => 'ili',
    'Console'               => 'Konzola',
    'ContactAdmin'          => 'Molimo konkatirajte svog administratora za detalje.',
    'Continue'              => 'Nastavi',
    'Contrast'              => 'Kontrast',
    'ControlAddress'        => 'Kontrolna adresa',
    'ControlCap'            => 'Control Capability',
    'ControlCaps'           => 'Control Capabilities',
    'Control'               => 'PTZ kontole',
    'ControlDevice'         => 'Kontroliši uređaj',
    'Controllable'          => 'Moguće kontrolisati',
    'ControlType'           => 'Tipa kontrole',
    'Current'               => 'Tekuće',
    'Cycle'                 => 'Kruži',
    'CycleWatch'            => 'Kružni prikaz',
    'Day'                   => 'Dan',
    'Debug'                 => 'Debug',
    'DefaultRate'           => 'Podrazumjevana stopa',
    'DefaultScale'          => 'Podrazumjevani razmjer',
    'DefaultView'           => 'Podrazumjevani prikaz',
    'Deinterlacing'         => 'Deinterlacing',
    'RTSPDescribe'         => 'Use RTSP Response Media URL',
    'Delay'                 => 'Zadrška',
    'DeleteAndNext'         => 'Izbriši &amp; Sljedeće',
    'DeleteAndPrev'         => 'Izbriši &amp; Preth',
    'Delete'                => 'Izbriši',
    'DeleteSavedFilter'     => 'Izbriši spremljeni filter',
    'Description'           => 'Opis',
    'DetectedCameras'       => 'Detektovane kamere:',
    'DetectedProfiles'      => 'Otkriveni profili',
    'DeviceChannel'         => 'Kanal',
    'DeviceFormat'          => 'Sistem boja',
    'DeviceNumber'          => 'Broj uređaja',
    'DevicePath'            => 'Putanja uređaja',
    'Device'                => 'Uređaj',
    'Devices'               => 'Uređaji',
    'Dimensions'            => 'Dimenzije',
    'DisableAlarms'         => 'Onemogući alarme',
    'Disk'                  => 'Disk',
    'Display'               => 'Prikaz',
    'Displaying'            => 'Prikazujem',
    'DonateAlready'         => 'Ne, već sam napravio donaciju.',
    'DonateEnticement'      => 'You\'ve been running ZoneMinder for a while now and hopefully are finding it a useful addition to your home or workplace security. Although ZoneMinder is, and will remain, free and open source, it costs money to develop and support. If you would like to help support future development and new features then please consider donating. Donating is, of course, optional but very much appreciated and you can donate as much or as little as you like.<br/><br/>If you would like to donate please select the option below or go to https://zoneminder.com/donate/ in your browser.<br/><br/>Thank you for using ZoneMinder and don\'t forget to visit the forums on ZoneMinder.com for support or suggestions about how to make your ZoneMinder experience even better.',
    'Donate'                => 'Molimo donirajte',
    'DonateRemindDay'       => 'Ne još, podsjetime za 1 dan',
    'DonateRemindHour'      => 'Ne još, podsjetime za 1 sat',
    'DonateRemindMonth'     => 'Ne još, podsjeti me za jedan mjesec',
    'DonateRemindNever'     => 'Ne, ne želim donirati, nemoj me više podsjećati.',
    'DonateRemindWeek'      => 'Ne još, podsjeti me za sedam dana.',
    'DonateYes'             => 'Da, želim da doniram sada.',
    'DoNativeMotionDetection'=> 'Nativna detekcija pokreta',
    'Download'              => 'Preuzmi',
    'DuplicateMonitorName'  => 'Dupliciraj ime monitora',
    'Duration'              => 'Trajanje',
    'Edit'                  => 'Uredi',
    'EditLayout'            => 'Uredi raspored',
    'Email'                 => 'Email',
    'EnableAlarms'          => 'Omogući alarme',
    'Enabled'               => 'Omogućeno',
    'EnterNewFilterName'    => 'Unesi novo ime za filter',
    'ErrorBrackets'         => 'Greška, provjerite da li imate jednak broj otvorenih i zatvorenih zagrada.',
    'Error'                 => 'Greška',
    'ErrorValidValue'       => 'Greška, osigurajte se da svi pojmovi imaju valide vrijednosti',
    'Etc'                   => 'itd',
    'Event'                 => 'Događaj',
    'EventFilter'           => 'Filter događaja',
    'EventId'               => 'ID događaja',
    'EventName'             => 'Naziv događaja',
    'EventPrefix'           => 'Prefiks događaja',
    'Events'                => 'Događaji',
    'Exclude'               => 'Isključi',
    'Execute'               => 'Izvrši',
    'ExportDetails'         => 'Izvezi detalje o događaju',
    'Exif'                  => 'Umetni EXIF podatke u sliku',
    'Export'                => 'Izvezi',
    'DownloadVideo'         => 'Preuzmi video',
    'GenerateDownload'      => 'Generiši preuzimanje',
    'ExportFailed'          => 'Izvoz nije uspio',
    'ExportFormat'          => 'Format za izvoz',
    'ExportFormatTar'       => 'Tar',
    'ExportFormatZip'       => 'Zip',
    'ExportFrames'          => 'Izvezi detalje frejma',
    'ExportImageFiles'      => 'Izvezi slike',
    'ExportLog'             => 'Izvezi zapisnik',
    'Exporting'             => 'Izvozim',
    'ExportMiscFiles'       => 'Izvezi druge fajlove (ukoliko postoje)',
    'ExportOptions'         => 'Opcije izvoženja',
    'ExportSucceeded'       => 'Izvoz uspio',
    'ExportVideoFiles'      => 'Izvezi video fileove (ukoliko postoje)',
    'Far'                   => 'Far',
    'FastForward'           => 'Naprijed',
    'Feed'                  => 'Feed',
    'Ffmpeg'                => 'Ffmpeg',
    'File'                  => 'File',
    'FilterArchiveEvents'   => 'Arhiviraj pronađeno',
    'FilterUpdateDiskSpace' => 'Ažuriraj korišteni prostor na disku',
    'FilterDeleteEvents'    => 'Izbriši sve pronađeno',
    'FilterMoveEvents'      => 'Premjesti pronađeno',
    'FilterEmailEvents'     => 'Pošalji detalje mailom',
    'FilterExecuteEvents'   => 'Izvrši sljededeću komandu',
    'FilterLog'             => 'Filtriraj zapis',
    'FilterMessageEvents'   => 'Message details of all matches',
    'FilterPx'              => 'Filter Px',
    'Filter'                => 'Filter',
    'Filters'               => 'Filteri',
    'FilterUnset'           => 'Morate navesti širinu i visinu filtera',
    'FilterUploadEvents'    => 'Učitaj sve događaje',
    'FilterVideoEvents'     => 'Napravi video',
    'First'                 => 'Prvi',
    'FlippedHori'           => 'Zaokrenuto horizontalno',
    'FlippedVert'           => 'Zaokrenuto vertikalno',
    'FnNone'                => 'nijedan',            // Added 2013.08.16.
    'FnMonitor'             => 'Monitor',            // Added 2013.08.16.
    'FnModect'              => 'Modect',            // Added 2013.08.16.
    'FnRecord'              => 'Record',            // Added 2013.08.16.
    'FnMocord'              => 'Mocord',            // Added 2013.08.16.
    'FnNodect'              => 'Nodect',            // Added 2013.08.16.
    'Focus'                 => 'Fokus',
    'ForceAlarm'            => 'Prisilni alarm',
    'Format'                => 'Format',
    'FPS'                   => 'fps',
    'FPSReportInterval'     => 'FPS Report Interval',
    'Frame'                 => 'Frame',
    'FrameId'               => 'Frame Id',
    'FrameRate'             => 'Frame Rate',
    'Frames'                => 'Frejmovi',
    'FrameSkip'             => 'Preskoči frejm',
    'MotionFrameSkip'       => 'Motion Frame Skip',
    'FTP'                   => 'FTP',
    'Func'                  => 'Func',
    'Function'              => 'Funkcija',
    'Gain'                  => 'Pojačanje',
    'General'               => 'Opšte',
    'GenerateVideo'         => 'Generiši video',
    'GeneratingVideo'       => 'Generiši video',
    'GoToZoneMinder'        => 'Idi na ZoneMinder.com',
    'Grey'                  => 'Siva',
    'Group'                 => 'Grupa',
    'Groups'                => 'Grupe',
    'HasFocusSpeed'         => 'Posjeduje brzo fokusiranja',
    'HasGainSpeed'          => 'Posjeduje brzo pojačanja',
    'HasHomePreset'         => 'Has Home Preset',
    'HasIrisSpeed'          => 'Posjeduje brzu blendu',
    'HasPanSpeed'           => 'Posjeduje brzi pomak',
    'HasPresets'            => 'Posjeduje pre-setove',
    'HasTiltSpeed'          => 'Posjeduje brzi nagiba',
    'HasTurboPan'           => 'Posjeduje turbo pomak',
    'HasTurboTilt'          => 'Posjeduje turbo nagib',
    'HasWhiteSpeed'         => 'Posjeduje brzo podeš.bijele',
    'HasZoomSpeed'          => 'Posjeduje brzi zoom',
    'HighBW'                => 'High&nbsp;B/W',
    'High'                  => 'veliku',
    'Home'                  => 'Početna',
    'Hostname'				=> 'Hostname',
    'Hour'                  => 'Sat',
    'Hue'                   => 'Nijansa',
    'Id'                    => 'Id',
    'Idle'                  => 'Na čekanju',
    'Ignore'                => 'Zanemari',
    'ImageBufferSize'       => 'Veličina slikovnog bufera (u frejmovima)',
    'Image'                 => 'Slika',
    'Images'                => 'Slike',
    'Include'               => 'Uključi',
    'In'                    => 'U',
    'Inverted'              => 'Invertirano',
    'Iris'                  => 'Blenda',
    'KeyString'             => 'Key String',
    'Label'                 => 'Oznaka',
    'Language'              => 'Jezik',
    'Last'                  => 'Zadnje',
    'Layout'                => 'Raspored',
    'Libvlc'                => 'Libvlc',
    'LimitResultsPost'      => 'results only', // This is used at the end of the phrase 'Limit to first N results only'
    'LimitResultsPre'       => 'Limit to first', // This is used at the beginning of the phrase 'Limit to first N results only'
    'LinkedMonitors'        => 'Povezani monitori',
    'List'                  => 'Popis',
    'ListMatches'           => 'Prikaži pronađeno',
    'Load'                  => 'Opterećenje',
    'Local'                 => 'Lokalno',
    'Log'                   => 'Zapis',
    'Logs'                  => 'Zapisi',
    'Logging'               => 'Dnevnik događaja',
    'LoggedInAs'            => 'Prijavljen kao',
    'LoggingIn'             => 'Prijavljujem',
    'Login'                 => 'prijava',
    'Logout'                => 'odjava',
    'LowBW'                 => 'Low&nbsp;B/W',
    'Low'                   => 'nisku',
    'Main'                  => 'Glavno',
    'Man'                   => 'Man',
    'Manual'                => 'Ručno',
    'Mark'                  => 'Označi',
    'MaxBandwidth'          => 'Max propusnost',
    'MaxBrScore'            => 'Max.<br/>Score',
    'MaxFocusRange'         => 'Max raspon fokusa',
    'MaxFocusSpeed'         => 'Max brzina fokusa',
    'MaxFocusStep'          => 'Max korak fokusa',
    'MaxGainRange'          => 'Max raspon pojačanja',
    'MaxGainSpeed'          => 'Max brzina pojačanja',
    'MaxGainStep'           => 'Max korak pojačanja',
    'MaximumFPS'            => 'Maximum FPS',
    'MaxIrisRange'          => 'Max raspon blende',
    'MaxIrisSpeed'          => 'Max brzina blende',
    'MaxIrisStep'           => 'Max korak blende',
    'Max'                   => 'Max',
    'MaxPanRange'           => 'Max raspon pomaka',
    'MaxPanSpeed'           => 'Max brzina pomaka',
    'MaxPanStep'            => 'Max korak pomaka',
    'MaxTiltRange'          => 'Max raspon nagiba',
    'MaxTiltSpeed'          => 'Max brzina nagiba',
    'MaxTiltStep'           => 'Max korak nagiba',
    'MaxWhiteRange'         => 'Max raspon bijele',
    'MaxWhiteSpeed'         => 'Max brzina bijele',
    'MaxWhiteStep'          => 'Max korak bijele',
    'MaxZoomRange'          => 'Max raspon zumiranja',
    'MaxZoomSpeed'          => 'Max brzina zumiranja',
    'MaxZoomStep'           => 'Max korak zumiranja',
    'MediumBW'              => 'Medium&nbsp;B/W',
    'Medium'                => 'srednju',
    'MinAlarmAreaLtMax'     => 'Min područje alarma mora biti manje od maksimalnog',
    'MinAlarmAreaUnset'     => 'Morate zadati minimalni broj alarm piksela',
    'MinBlobAreaLtMax'      => 'Min blob područje mora biti manje od maksimalnog',
    'MinBlobAreaUnset'      => 'Morate zadati minimalni broj blob piksela',
    'MinBlobLtMinFilter'    => 'Min blob oblast mora biti manja ili jednaka minimalnoj oblasti filtera',
    'MinBlobsLtMax'         => 'Min blob mora biti manji od maksimalne',
    'MinBlobsUnset'         => 'morate zadati minimalni broj blob-ova',
    'MinFilterAreaLtMax'    => 'Minimalna oblast filtera mora biti manja od maksimalne',
    'MinFilterAreaUnset'    => 'Morate zadati minimalni broj filter piksela',
    'MinFilterLtMinAlarm'   => 'Min oblast filtera mora biti manja ili jednaka minimalnoj oblasti alarmne oblasti',
    'MinFocusRange'         => 'Min raspon fokusiranja',
    'MinFocusSpeed'         => 'Min brzina fokusiranja',
    'MinFocusStep'          => 'Min korak fokusiranja',
    'MinGainRange'          => 'Min raspon pojačanja',
    'MinGainSpeed'          => 'Min brzina pojačanja',
    'MinGainStep'           => 'Min korak pojačanja',
    'MinIrisRange'          => 'Min raspon blende',
    'MinIrisSpeed'          => 'Min brzina blende',
    'MinIrisStep'           => 'Min korak blende',
    'MinPanRange'           => 'Min raspon pomaka',
    'MinPanSpeed'           => 'Min brzina pomaka',
    'MinPanStep'            => 'Min korak pomaka',
    'MinPixelThresLtMax'    => 'Min prag piksela mora biti manji od maksimalnog',
    'MinPixelThresUnset'    => 'Morate zadati minimalni prag piksela',
    'MinTiltRange'          => 'Min Tilt Range',
    'MinTiltSpeed'          => 'Min Tilt Speed',
    'MinTiltStep'           => 'Min Tilt Step',
    'MinWhiteRange'         => 'Min raspon bijelog balansa',
    'MinWhiteSpeed'         => 'Min brzina bijelog balansa',
    'MinWhiteStep'          => 'Min White Bal. Step',
    'MinZoomRange'          => 'Min raspon zumiranja',
    'MinZoomSpeed'          => 'Min brzina zumiranja',
    'MinZoomStep'           => 'Min korak zumiranja',
    'Misc'                  => 'Razno',
    'Mode'                  => 'Modus',
    'MonitorIds'            => 'Monitor&nbsp;Ids',
    'Monitor'               => 'Monitor',
    'MonitorPresetIntro'    => 'Odaberite odgovarajuće pre-setove sa popisa.<br/><br/>Imajte u vidu da ovo može prepisati bilo koju vrijednost koja postoji za odabrane monitore.<br/><br/>',
    'MonitorPreset'         => 'Monitor Preset',
    'MonitorProbeIntro'     => 'Donji popis prikazuje otkrivene analogne i mrežne kamere, te da li se iste već koriste i da li su dostupne.<br/><br/>Odaberite željenu kameru sa donjeg popisa.<br/><br/>Imajte u vidu da ovo može prepisati bilo koju vrijednost koja postoji za odabrane monitore.<br/><br/>',
    'MonitorProbe'          => 'Detektuj kameru',
    'Monitors'              => 'Monitori',
    'Montage'               => 'Montage',
    'MontageReview'         => 'Montage pregled',
    'Month'                 => 'Mjesec',
    'Move'                  => 'Pomjeri',
    'MtgDefault'            => 'Podrazumjevano',              // Added 2013.08.15.
    'Mtg2widgrd'            => '2-struka rešetka',              // Added 2013.08.15.
    'Mtg3widgrd'            => '3-struka rešetka',              // Added 2013.08.15.
    'Mtg4widgrd'            => '4-struka rešetka',              // Added 2013.08.15.
    'Mtg3widgrx'            => '3-wide grid, scaled, enlarge on alarm',              // Added 2013.08.15.
    'MustBeGe'              => 'mora biti veće ili jednako',
    'MustBeLe'              => 'mora biti manje ili jednako',
    'MustConfirmPassword'   => 'Morate potvrditi lozinku',
    'MustSupplyPassword'    => 'Morate unjeti lozinku',
    'MustSupplyUsername'    => 'Morate unjeti korisničko ime',
    'Name'                  => 'Ime',
    'Near'                  => 'Blizu',
    'Network'               => 'Mreža',
    'NewGroup'              => 'Nova grupa',
    'NewLabel'              => 'Nova oznaka',
    'New'                   => 'Novo',
    'NewPassword'           => 'Nova lozinka',
    'NewState'              => 'Novi radni modus',
    'NewUser'               => 'Novi korisnik',
    'Next'                  => 'Sljedeće',
    'NoDetectedCameras'     => 'Nema otkrivenih kamera',
    'NoDetectedProfiles'    => 'Nema otkrivenih profila',
    'NoFramesRecorded'      => 'Nije ništa snimljeno za ovaj događaj',
    'NoGroup'               => 'Nema grupe',
    'NoneAvailable'         => 'Nijedno dostupno',
    'None'                  => 'Nijedno',
    'No'                    => 'Ne',
    'Normal'                => 'Normalno',
    'NoSavedFilters'        => 'NemaSnimljenihFiltera',
    'NoStatisticsRecorded'  => 'Nema snimljenih statistika za ovaj događaj',
    'Notes'                 => 'Bilješke',
    'NumPresets'            => 'Num Presets',
    'Off'                   => 'Isključeno',
    'On'                    => 'Uključeno',
    'OnvifProbe'            => 'ONVIF detekcija',
    'OnvifProbeIntro'       => 'The list below shows detected ONVIF cameras and whether they are already being used or available for selection.<br/><br/>Select the desired entry from the list below.<br/><br/>Please note that not all cameras may be detected and that choosing a camera here may overwrite any values you already have configured for the current monitor.<br/><br/>',
    'OnvifCredentialsIntro' => 'Please supply user name and password for the selected camera.<br/>If no user has been created for the camera then the user given here will be created with the given password.<br/><br/>',
    'Open'                  => 'Otvori',
    'OpEq'                  => 'jednako',
    'OpGtEq'                => 'veće ili jednako od',
    'OpGt'                  => 'veće ',
    'OpIn'                  => 'in set',
    'OpLtEq'                => 'manje ili jednako od',
    'OpLt'                  => 'manje od',
    'OpMatches'             => 'matches',
    'OpNe'                  => 'nije jednako',
    'OpNotIn'               => 'nije u ',
    'OpNotMatches'          => 'ne poklapa se',
    'OpIs'                  => 'je',
    'OpIsNot'               => 'nije',
    'OptionalEncoderParam'  => 'Opcionalni parametri enkodera',
    'OptionHelp'            => 'Option Help',
    'OptionRestartWarning'  => 'These changes may not come into effect fully\nwhile the system is running. When you have\nfinished making your changes please ensure that\nyou restart ZoneMinder.',
    'Options'               => 'Opcije',
    'Order'                 => 'Redosljed',
    'OrEnterNewName'        => 'ili unesi novo ime',
    'Orientation'           => 'Orijentacija',
    'Out'                   => 'Izlaz',
    'OverwriteExisting'     => 'Prepiši preko postojećeg',
    'Paged'                 => 'stranično',
    'PanLeft'               => 'Pomak lijevo',
    'Pan'                   => 'Pomak',
    'PanRight'              => 'Pomak desno',
    'PanTilt'               => 'Pomak/Nagib',
    'Parameter'             => 'Parametar',
    'Password'              => 'Lozinka',
    'PasswordsDifferent'    => 'Nova i potvrđena lozinka se razlikuju',
    'Paths'                 => 'Putanje',
    'Pause'                 => 'Pauza',
    'PhoneBW'               => 'Telefon&nbsp;B/W',
    'Phone'                 => 'Telefon',
    'PixelDiff'             => 'Piksel razli.',
    'Pixels'                => 'pikseli',
    'PlayAll'               => 'play all',
    'Play'                  => 'Play',
    'Plugins'               => 'Plugini',
    'PleaseWait'            => 'Molim čekati',
    'Point'                 => 'Point',
    'PostEventImageBuffer'  => 'Br. frejmova poslije događaja',
    'PreEventImageBuffer'   => 'Br. frejmova prije događaja',
    'PreserveAspect'        => 'Zadrži omjer',
    'Preset'                => 'Preset',
    'Presets'               => 'Presets',
    'Prev'                  => 'Preth',
    'Privacy'               => 'Privatnost',
    'PrivacyAbout'          => 'O',
    'PrivacyAboutText'      => 'Since 2002, ZoneMinder has been the premier free and open-source Video Management System (VMS) solution for Linux platforms. ZoneMinder is supported by the community and is managed by those who choose to volunteer their spare time to the project. The best way to improve ZoneMinder is to get involved.',
    'PrivacyContact'        => 'Konakt',
    'PrivacyContactText'    => 'Please contact us <a href="https://zoneminder.com/contact/">here</a> for any questions regarding our privacy policy or to have your information removed.<br><br>For support, there are three primary ways to engage with the community:<ul><li>The ZoneMinder <a href="https://forums.zoneminder.com/">user forum</a></li><li>The ZoneMinder <a href="https://zoneminder-chat.herokuapp.com/">Slack channel</a></li><li>The ZoneMinder <a href="https://github.com/ZoneMinder/zoneminder/issues">Github forum</a></li></ul><p>Our Github forum is only for bug reporting. Please use our user forum or slack channel for all other questions or comments.</p>',
    'PrivacyCookies'        => 'Kolačići',
    'PrivacyCookiesText'    => 'Whether you use a web browser or a mobile app to communicate with the ZoneMinder server, a ZMSESSID cookie is created on the client to uniquely identify a session with the ZoneMinder server. ZmCSS and zmSkin cookies are created to remember your style and skin choices.',
    'PrivacyTelemetry'      => 'Telemetry',
    'PrivacyTelemetryText'  => 'Because ZoneMinder is open-source, anyone can install it without registering. This makes it difficult to  answer questions such as: how many systems are out there, what is the largest system out there, what kind of systems are out there, or where are these systems located? Knowing the answers to these questions, helps users who ask us these questions, and it helps us set priorities based on the majority user base.',
    'PrivacyTelemetryList'  => 'The ZoneMinder Telemetry daemon collects the following data about your system:<ul><li>A unique identifier (UUID) <li>City based location is gathered by querying <a href="https://ipinfo.io/geo">ipinfo.io</a>. City, region, country, latitude, and longitude parameters are saved. The latitude and longitude coordinates are accurate down to the city or town level only!<li>Current time<li>Total number of monitors<li>Total number of events<li>System architecture<li>Operating system kernel, distro, and distro version<li>Version of ZoneMinder<li>Total amount of memory<li>Number of cpu cores</ul>',
    'PrivacyMonitorList'    => 'The following configuration parameters from each monitor are collected:<ul><li>Id<li>Name<li>Type<li>Function<li>Width<li>Height<li>Colours<li>MaxFPS<li>AlarmMaxFPS</ul>',
    'PrivacyConclusionText' => 'We are <u>NOT</u> collecting any image specific data from your cameras. We don�t know what your cameras are watching. This data will not be sold or used for any purpose not stated herein. By clicking accept, you agree to send us this data to help make ZoneMinder a better product. By clicking decline, you can still freely use ZoneMinder and all its features.',
    'Probe'                 => 'Detektuj kameru',
    'ProfileProbe'          => 'Stream proba',
    'ProfileProbeIntro'     => 'The list below shows the existing stream profiles of the selected camera .<br/><br/>Select the desired entry from the list below.<br/><br/>Please note that ZoneMinder cannot configure additional profiles and that choosing a camera here may overwrite any values you already have configured for the current monitor.<br/><br/>',
    'Progress'              => 'Napredak',
    'Protocol'              => 'Protkol',
    'Rate'                  => 'Stopa',
    'RecaptchaWarning'      => 'Your reCaptcha secret key is invalid. Please correct it, or reCaptcha will not work', // added Sep 24 2015 - PP
	'RecordAudio'			=> 'Whether to store the audio stream when saving an event.',
    'Real'                  => 'Stvarno',
    'Record'                => 'Snimaj',
    'RefImageBlendPct'      => 'Reference Image Blend %ge',
    'Refresh'               => 'Osvježi',
    'RemoteHostName'        => 'Naziv uređaja',
    'RemoteHostPath'        => 'Putanja',
    'RemoteHostSubPath'     => 'Pod-putanja',
    'RemoteHostPort'        => 'Port',
    'RemoteImageColours'    => 'Boje slike',
    'RemoteMethod'          => 'Metoda',
    'RemoteProtocol'        => 'Protokol',
    'Remote'                => 'Udaljeno',
    'Rename'                => 'Preimenuj',
    'ReplayAll'             => 'Svi događaji',
    'ReplayGapless'         => 'Gapless Events',
    'Replay'                => 'Ponovo odigraj',
    'ReplaySingle'          => 'Jedan događaj',
    'ReportEventAudit'      => 'Audit Events Report',
    'ResetEventCounts'      => 'Resetiraj događaje',
    'Reset'                 => 'Reset',
    'Restarting'            => 'Restartiram',
    'Restart'               => 'Restaruj',
    'RestrictedCameraIds'   => 'Restricted Camera Ids',
    'RestrictedMonitors'    => 'Ograničeni monitori',
    'ReturnDelay'           => 'Vrati kašnjenje',
    'ReturnLocation'        => 'Vrati lokaciju',
    'Rewind'                => 'Premotaj',
    'RotateLeft'            => 'Rotoraj ulijevo',
    'RotateRight'           => 'Rotiraj udesno',
    'RTSPTransport'         => 'RTSP Transport Protocol',
    'RunAudit'              => 'Run Audit Process',
    'RunLocalUpdate'        => 'Pokrenite zmupdate.pl za ažuriranje',
    'RunMode'               => 'Modus rada',
    'Running'               => 'Pokrenuto',
    'RunState'              => 'Radni modus',
    'RunStats'              => 'Pokreni stats proces',
    'RunTrigger'            => 'Pokreni triger proces',
    'SaveAs'                => 'Spremi kao',
    'SaveFilter'            => 'Spremi Filter',
    'SaveJPEGs'             => 'Spremi JPEGs',
    'Save'                  => 'Spremi',
    'Scale'                 => 'Razmjer',
    'Score'                 => 'Zbir',
    'Secs'                  => 'Secs',
    'Sectionlength'         => 'Odaberi dužinu',
    'SelectMonitors'        => 'SOdaberi monitore',
    'Select'                => 'Odaberi',
    'SelectFormat'          => 'Odaberi format',
    'SelectLog'             => 'Odaberi zapis',
    'SelfIntersecting'      => 'Polygon edges must not intersect',
    'SetNewBandwidth'       => 'Postavi propusnost na',
    'SetPreset'             => 'Postavi pozicije',
    'Set'                   => 'Postavi',
    'Settings'              => 'Postavke',
    'ShowFilterWindow'      => 'Prikaži prozor za filter',
    'ShowTimeline'          => 'Prikaži vremensku liniju',
    'SignalCheckColour'     => 'Signal Check Colour',
    'SignalCheckPoints'     => 'Signal Check Points',
    'Size'                  => 'Veličina',
    'SkinDescription'       => 'Izmjeni izgled za ovu sesiju',
    'CSSDescription'        => 'Izmjeni css za ovu sesiju',
    'Sleep'                 => 'Sleep',
    'SortAsc'               => 'Rastuće',
    'SortBy'                => 'Sortiraj po',
    'SortDesc'              => 'Padajuće',
    'Source'                => 'Izvor',
    'SourceColours'         => 'Source Colours',
    'SourcePath'            => 'Putanja izvora ',
    'SourceType'            => 'Izvor videa',
    'SpeedHigh'             => 'Velika brzina',
    'SpeedLow'              => 'Niska brzina',
    'SpeedMedium'           => 'Srednja brzina',
    'Speed'                 => 'brzina',
    'SpeedTurbo'            => 'Turbo brzina',
    'Start'                 => 'Start',
    'State'                 => 'Stanje',
    'Stats'                 => 'Statistka',
    'Status'                => 'Status',
    'StatusUnknown'         => 'Nepoznato',
    'StatusConnected'       => 'Snimam',
    'StatusNotRunning'      => 'Nije pokrenuto',
    'StatusRunning'         => 'Ne snima',
    'StepBack'              => 'Korak nazad',
    'StepForward'           => 'Korak naprijed',
    'StepLarge'             => 'Veliki korak',
    'StepMedium'            => 'Srednji korak',
    'StepNone'              => 'Bez koraka',
    'StepSmall'             => 'Mali korak',
    'Step'                  => 'Korak',
    'Stills'                => 'Stills',
    'Stopped'               => 'Zaustavljeno',
    'Stop'                  => 'Zaustavi',
    'StorageArea'           => 'Storage Area',
    'StorageDoDelete'       => 'Brisanja',
    'StorageScheme'         => 'Šema',
    'StreamReplayBuffer'    => 'Stream Replay Image Buffer',
    'Stream'                => 'Stream',
    'Submit'                => 'Pošalji',
    'System'                => 'Sistem',
    'TargetColorspace'      => 'Rezolucija boja',
    'Tele'                  => 'Udaljeno',
    'Thumbnail'             => 'Sličica',
    'Tilt'                  => 'Tilt',
    'TimeDelta'             => 'Vremenska razlika',
    'Timeline'              => 'Vremenska linija',
    'TimelineTip1'          => 'Pass your mouse over the graph to view a snapshot image and event details.',              // Added 2013.08.15.
    'TimelineTip2'          => 'Click on the coloured sections of the graph, or the image, to view the event.',              // Added 2013.08.15.
    'TimelineTip3'          => 'Click on the background to zoom in to a smaller time period based around your click.',              // Added 2013.08.15.
    'TimelineTip4'          => 'Use the controls below to zoom out or navigate back and forward through the time range.',              // Added 2013.08.15.
    'TimestampLabelFormat'  => 'Timestamp format oznake',
    'TimestampLabelX'       => 'Timestamp oznaka X',
    'TimestampLabelY'       => 'Timestamp oznaka Y',
    'TimestampLabelSize'    => 'Veličina fonta',
    'Timestamp'             => 'Timestamp',
    'TimeStamp'             => 'Vremenski pečat',
    'Time'                  => 'Vrijeme',
    'Today'                 => 'Danas',
    'Tools'                 => 'Alati',
    'Total'                 => 'Ukupno',
    'TotalBrScore'          => 'Total<br/>Score',
    'TrackDelay'            => 'Kašnjenje',
    'TrackMotion'           => 'Prati pokret',
    'Triggers'              => 'Okidači',
    'TurboPanSpeed'         => 'Turbo Pan brzina',
    'TurboTiltSpeed'        => 'Turbo Tilt brzina',
    'Type'                  => 'Tip',
    'Unarchive'             => 'Dearhiviraj',
    'Undefined'             => 'Nedefinisano',
    'Units'                 => 'Mjere',
    'Unknown'               => 'Nepoznato',
    'UpdateAvailable'       => 'Dostupno je novo ažurranje za Zoneminder .',
    'UpdateNotNecessary'    => 'Ažuriranje nije potrebno.',
    'Update'                => 'Ažuiriaj',
    'Upload'                => 'Upload',
    'Updated'               => 'Ažurirano',
    'UsedPlugins'	   => 'Korišteni plugini ',
    'UseFilterExprsPost'    => '&nbsp;filter&nbsp;expressions', // This is used at the end of the phrase 'use N filter expressions'
    'UseFilterExprsPre'     => 'Use&nbsp;', // This is used at the beginning of the phrase 'use N filter expressions'
    'UseFilter'             => 'Koristi filter',
    'Username'              => 'Korisničko ime',
    'Users'                 => 'Korisnici',
    'User'                  => 'Korisnik',
    'Value'                 => 'Vrijednost',
    'VersionIgnore'         => 'Ignoriši ovu verziju',
    'VersionRemindDay'      => 'Podsjeti me za jedan dan',
    'VersionRemindHour'     => 'Podsjeti me za jedan sat',
    'VersionRemindNever'    => 'Ne podsjecaj me na nove verzije',
    'VersionRemindWeek'     => 'Podsjeti me za sedam dana',
    'Version'               => 'Verzija',
    'VideoFormat'           => 'Video Format',
    'VideoGenFailed'        => 'Generisanje videa nije uspjelo!',
    'VideoGenFiles'         => 'Postojece video datoteke',
    'VideoGenNoFiles'       => 'Video datoteke nisu pronadjene',
    'VideoGenParms'         => 'Parametri za generisanje videa',
    'VideoGenSucceeded'     => 'Generisanje videa uspjelo!',
    'VideoSize'             => 'Velicina videa',
    'VideoWriter'           => 'Video pisac',
    'Video'                 => 'Video',
    'ViewAll'               => 'Pregledaj sve',
    'ViewEvent'             => 'Pregled događaja',
    'ViewPaged'             => 'Stanični pregled',
    'View'                  => 'Pregled',
	'V4L'					=> 'V4L',
	'V4LCapturesPerFrame'	=> 'Snimci po frejmu',
	'V4LMultiBuffer'		=> 'Višestr. bafer',
    'Wake'                  => 'Budi',
    'WarmupFrames'          => 'Warmup frejmovi',
    'Watch'                 => 'Gledaj',
    'WebColour'             => 'Web boja',
    'Web'                   => 'Web',
    'WebSiteUrl'            => 'URL web stranice',
    'Week'                  => 'Sedmica',
    'WhiteBalance'          => 'Balans bijele',
    'White'                 => 'Bijelo',
    'Wide'                  => 'Široko',
    'X10ActivationString'   => 'X10 znakovni niz za aktiviranje',
    'X10InputAlarmString'   => 'X10 ulazni znakovni niz za alarm',
    'X10OutputAlarmString'  => 'X10 izlazni znakovni niz za alarm',
    'X10'                   => 'X10',
    'X'                     => 'X',
    'Yes'                   => 'Da',
    'YouNoPerms'            => 'Nemate potrebne dozvole za pristup ovom resursu.',
    'Y'                     => 'Y',
    'ZoneAlarmColour'       => 'Boja alarma (Red/Green/Blue)',
    'ZoneArea'              => 'Oblast zone',
    'ZoneFilterSize'        => 'Filter Width/Height (pixels)',
    'ZoneMinderLog'         => 'ZoneMinder zapisnik',
    'ZoneMinMaxAlarmArea'   => 'Min/Max alarmirana oblast',
    'ZoneMinMaxBlobArea'    => 'Min/Max blob oblast',
    'ZoneMinMaxBlobs'       => 'Min/Max Blobovi',
    'ZoneMinMaxFiltArea'    => 'Min/Max filtrirane oblasti',
    'ZoneMinMaxPixelThres'  => 'Min/Max Pixel Threshold (0-255)',
    'ZoneOverloadFrames'    => 'Overload Frame Ignore Count',
    'ZoneExtendAlarmFrames' => 'Extend Alarm Frame Count',
    'Zones'                 => 'Zone',
    'Zone'                  => 'Zona',
    'ZoomIn'                => 'Zoom In',
    'ZoomOut'               => 'Zoom Out',
    'Zoom'                  => 'Zumiranje',
);

// Complex replacements with formatting and/or placements, must be passed through sprintf
$CLANG = array(
    'CurrentLogin'          => 'Prijavljeni ste kao \'%1$s\'',
    'EventCount'            => '%1$s %2$s', // For example '37 Events' (from Vlang below)
    'LastEvents'            => 'Last %1$s %2$s', // For example 'Last 37 Events' (from Vlang below)
    'LatestRelease'         => 'Zadnja verzija servera je v%1$s, vi imate v%2$s.',
    'MonitorCount'          => '%1$s %2$s', // For example '4 Monitors' (from Vlang below)
    'MonitorFunction'       => 'Monitor %1$s Function',
    'RunningRecentVer'      => 'Koristite najnoviju verziju Zoneminder servera, v%s.',
    'VersionMismatch'       => 'Version mismatch, system is version %1$s, database is %2$s.',
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
        'OPTIONS_RTSPTrans' => array(
		'Help' => "This sets the RTSP Transport Protocol for FFmpeg.~~ ".
                          "TCP - Use TCP (interleaving within the RTSP control channel) as transport protocol.~~".
                          "UDP - Use UDP as transport protocol. Higher resolution cameras have experienced some 'smearing' while using UDP, if so try TCP~~".
                          "UDP Multicast - Use UDP Multicast as transport protocol~~".
                          "HTTP - Use HTTP tunneling as transport protocol, which is useful for passing proxies.~~"
	),
	'OPTIONS_LIBVLC' => array(
		'Help' => "Parameters in this field are passed on to libVLC. Multiple parameters can be separated by ,~~ ".
		          "Examples (do not enter quotes)~~~~".
		          "\"--rtp-client-port=nnn\" Set local port to use for rtp data~~~~". 
		          "\"--verbose=2\" Set verbosity of libVLC"
	),
	'OPTIONS_EXIF' => array(
		'Help' => "Enable this option to embed EXIF data into each jpeg frame."
	),
	'OPTIONS_RTSPDESCRIBE' => array(
		'Help' => "Sometimes, during the initial RTSP handshake, the camera will send an updated media URL. ".
		          "Enable this option to tell ZoneMinder to use this URL. Disable this option to ignore the ".
		          "value from the camera and use the value as entered in the monitor configuration~~~~". 
		          "Generally this should be enabled. However, there are cases where the camera can get its".
		          "own URL incorrect, such as when the camera is streaming through a firewall"),
	'OPTIONS_MAXFPS' => array(
		'Help' => "This field has certain limitations when used for non-local devices.~~ ".
		          "Failure to adhere to these limitations will cause a delay in live video, irregular frame skipping, ".
		          "and missed events~~".
		          "For streaming IP cameras, do not use this field to reduce the frame rate. Set the frame rate in the".
                          " camera, instead. You can, however, use a value that is slightly higher than the frame rate in the camera. ".
		          "In this case, this helps keep the cpu from being overtaxed in the event of a network problem.~~". 
		          "Some, mostly older, IP cameras support snapshot mode. In this case ZoneMinder is actively polling the camera ".
		          "for new images. In this case, it is safe to use the field."
	),
	
//    'LANG_DEFAULT' => array(
//        'Prompt' => "This is a new prompt for this option",
//        'Help' => "This is some new help for this option which will be displayed in the popup window when the ? is clicked"
//    ),
);

?>
