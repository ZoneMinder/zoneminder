<?php
//
// ZoneMinder web German language file, $Date$, $Revision$
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

// ZoneMinder german Translation by Robert Schumann (rs at core82 dot de)
// ZoneMinder german Translation by Sebastian Kaminski (github @seeebek)
// german Translation update by seebaer1976

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
header( "Content-Type: text/html; charset=utf-8" );

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
    '24BitColour'          => '24-Bit-Farbe',
    '32BitColour'          => '32-Bit-Farbe',          // Added - 2011-06-15
    '8BitGrey'             => '8-Bit-Grau',
    'Action'               => 'Aktion',
    'Actual'               => 'Original',
    'AddNewControl'        => 'Neues Steuerelement hinzufügen',
    'AddNewMonitor'        => 'Neuer Monitor',
    'AddNewServer'         => 'Add New Server',         // Added - 2018-08-30
    'AddNewStorage'        => 'Add New Storage',        // Added - 2018-08-30
    'AddNewUser'           => 'Neuer Benutzer',
    'AddNewZone'           => 'Neue Zone',
    'Alarm'                => 'Alarm',
    'AlarmBrFrames'        => 'Alarm-<br />Bilder',
    'AlarmFrame'           => 'Alarm-Bilder',
    'AlarmFrameCount'      => 'Alarm-Bildanzahl',
    'AlarmLimits'          => 'Alarm-Limits',
    'AlarmMaximumFPS'      => 'Alarm-Maximum-FPS',
    'AlarmPx'              => 'Alarm-Pixel',
    'AlarmRGBUnset'        => 'Sie müssen eine RGB-Alarmfarbe setzen',
    'AlarmRefImageBlendPct'=> 'Alarm Reference Image Blend %ge', // Added - 2015-04-18
    'Alert'                => 'Alarm',
    'All'                  => 'Alle',
    'AnalysisFPS'          => 'Analysis FPS',           // Added - 2015-07-22
    'AnalysisUpdateDelay'  => 'Analysis Update Delay',  // Added - 2015-07-23
    'Apply'                => 'Anwenden',
    'ApplyingStateChange'  => 'Aktiviere neuen Status',
    'ArchArchived'         => 'Nur Archivierte',
    'ArchUnarchived'       => 'Nur Nichtarchivierte',
    'Archive'              => 'Archivieren',
    'Archived'             => 'Archivierte',
    'Area'                 => 'Bereich',
    'AreaUnits'            => 'Bereich (px/%)',
    'AttrAlarmFrames'      => 'Alarmbilder',
    'AttrArchiveStatus'    => 'Archivstatus',
    'AttrAvgScore'         => 'Mittlere Wertung',
    'AttrCause'            => 'Grund',
    'AttrDiskBlocks'       => 'Disk-Blöcke',
    'AttrDiskPercent'      => 'Disk-Prozent',
    'AttrDiskSpace'        => 'Disk Space',             // Added - 2018-08-30
    'AttrDuration'         => 'Dauer',
    'AttrEndDate'          => 'End Date',               // Added - 2018-08-30
    'AttrEndDateTime'      => 'End Date/Time',          // Added - 2018-08-30
    'AttrEndTime'          => 'End Time',               // Added - 2018-08-30
    'AttrEndWeekday'       => 'End Weekday',            // Added - 2018-08-30
    'AttrFilterServer'     => 'Server Filter is Running On', // Added - 2018-08-30
    'AttrFrames'           => 'Bilder',
    'AttrId'               => 'ID',
    'AttrMaxScore'         => 'Maximale Wertung',
    'AttrMonitorId'        => 'Monitor-ID',
    'AttrMonitorName'      => 'Monitorname',
    'AttrMonitorServer'    => 'Server Monitor is Running On', // Added - 2018-08-30
    'AttrName'             => 'Name',
    'AttrNotes'            => 'Bemerkungen',
    'AttrStartDate'        => 'Start Date',             // Added - 2018-08-30
    'AttrStartDateTime'    => 'Start Date/Time',        // Added - 2018-08-30
    'AttrStartTime'        => 'Start Time',             // Added - 2018-08-30
    'AttrStartWeekday'     => 'Start Weekday',          // Added - 2018-08-30
    'AttrStateId'          => 'Run State',              // Added - 2018-08-30
    'AttrStorageArea'      => 'Storage Area',           // Added - 2018-08-30
    'AttrStorageServer'    => 'Server Hosting Storage', // Added - 2018-08-30
    'AttrSystemLoad'       => 'Systemlast',
    'AttrTotalScore'       => 'Gesamtwertung',
    'Auto'                 => 'Auto',
    'AutoStopTimeout'      => 'Auto-Stopp-Zeitüberschreitung',
    'Available'            => 'Verfügbar',              // Added - 2009-03-31
    'AvgBrScore'           => 'Mittlere<br/>Wertung',
    'Background'           => 'Hintergrund',
    'BackgroundFilter'     => 'Filter im Hintergrund laufen lassen',
    'BadAlarmFrameCount'   => 'Die Bildanzahl muss ganzzahlig 1 oder größer sein',
    'BadAlarmMaxFPS'       => 'Alarm-Maximum-FPS muss eine positive Ganzzahl oder eine Gleitkommazahl sein',
    'BadAnalysisFPS'       => 'Analysis FPS must be a positive integer or floating point value', // Added - 2015-07-22
    'BadAnalysisUpdateDelay'=> 'Analysis update delay must be set to an integer of zero or more', // Added - 2015-07-23
    'BadChannel'           => 'Der Kanal muss ganzzahlig 0 oder größer sein',
    'BadColours'           => 'Zielfarbe muss auf einen gültigen Wert gesetzt werden', // Added - 2011-06-15
    'BadDevice'            => 'Das Gerät muss eine gültige Systemresource sein',
    'BadFPSReportInterval' => 'Der FPS-Intervall-Puffer-Zähler muss ganzzahlig 0 oder größer sein',
    'BadFormat'            => 'Das Format muss ganzzahlig 0 oder größer sein',
    'BadFrameSkip'         => 'Der Auslasszähler für Frames muss ganzzahlig 0 oder größer sein',
    'BadHeight'            => 'Die Höhe muss auf einen gültigen Wert eingestellt sein',
    'BadHost'              => 'Der Host muss auf eine gültige IP-Adresse oder einen Hostnamen (ohne http://) eingestellt sein',
    'BadImageBufferCount'  => 'Die Größe des Bildpuffers muss ganzzahlig 10 oder größer sein',
    'BadLabelX'            => 'Die x-Koordinate der Bezeichnung muss ganzzahlig 0 oder größer sein',
    'BadLabelY'            => 'Die y-Koordinate der Bezeichnung muss ganzzahlig 0 oder größer sein',
    'BadMaxFPS'            => 'Maximum-FPS muss eine positive Ganzzahl oder eine Gleitkommazahl sein',
    'BadMotionFrameSkip'   => 'Bewegungsrahmen Skip-Zählung muß eine ganze Zahl von null oder mehr betragen,',
    'BadNameChars'         => 'Namen dürfen nur aus Buchstaben, Zahlen, Leerzeichen und Trenn- oder Unterstrichen bestehen',
    'BadPalette'           => 'Palette muss auf einen gültigen Wert gesetzt sein', // Added - 2009-03-31
    'BadPath'              => 'Der Pfad muss auf einen gültigen Wert eingestellt sein',
    'BadPort'              => 'Der Port muss auf eine gültige Zahl eingestellt sein',
    'BadPostEventCount'    => 'Der Zähler für die Ereignisfolgebilder muss ganzzahlig 0 oder größer sein',
    'BadPreEventCount'     => 'Der Zähler für die Ereignisvorlaufbilder muss mindestens ganzzahlig 0 und kleiner als die Bildpuffergröße sein',
    'BadRefBlendPerc'      => 'Der Referenz-Blenden-Prozentwert muss ganzzahlig 0 oder größer sein',
    'BadSectionLength'     => 'Die Bereichslänge muss ganzzahlig 0 oder größer sein',
    'BadSignalCheckColour' => 'Die Signalprüffarbe muss auf einen gültigen Farbwert eingestellt sein',
    'BadSourceType'        => 'Source Type \"Web Site\" requires the Function to be set to \"Monitor\"', // Added - 2018-08-30
    'BadStreamReplayBuffer'=> 'Der Wiedergabestrompuffer muss eine ganze Zahl von null oder mehr betragen',
    'BadWarmupCount'       => 'Die Anzahl der Vorwärmbilder muss ganzzahlig 0 oder größer sein',
    'BadWebColour'         => 'Die Webfarbe muss auf einen gültigen Farbwert eingestellt sein',
    'BadWebSitePath'       => 'Please enter a complete website url, including the http:// or https:// prefix.', // Added - 2018-08-30
    'BadWidth'             => 'Die Breite muss auf einen gültigen Wert eingestellt sein',
    'Bandwidth'            => 'Bandbreite',
    'BandwidthHead'        => 'Bandbreite',	// This is the end of the bandwidth status on the top of the console, different in many language due to phrasing
    'BlobPx'               => 'Blob-Pixel',
    'BlobSizes'            => 'Blobgröße',
    'Blobs'                => 'Blobs',
    'Brightness'           => 'Helligkeit',
    'Buffer'               => 'Puffer',                 // Added - 2015-04-18
    'Buffers'              => 'Puffer',
    'CSSDescription'       => 'Ändere das standardmäßige CSS für diesen Computer.', // Added - 2015-04-18
    'CanAutoFocus'         => 'Kann Autofokus',
    'CanAutoGain'          => 'Kann Auto-Verstärkung',
    'CanAutoIris'          => 'Kann Auto-Blende',
    'CanAutoWhite'         => 'Kann Auto-Weiß-Abgleich',
    'CanAutoZoom'          => 'Kann Auto-Zoom',
    'CanFocus'             => 'Kann Fokus',
    'CanFocusAbs'          => 'Kann absoluten Fokus',
    'CanFocusCon'          => 'Kann kontinuierlichen Fokus',
    'CanFocusRel'          => 'Kann relativen Fokus',
    'CanGain'              => 'Kann Verstärkung',
    'CanGainAbs'           => 'Kann absolute Verstärkung',
    'CanGainCon'           => 'Kann kontinuierliche Verstärkung',
    'CanGainRel'           => 'Kann relative Verstärkung',
    'CanIris'              => 'Kann Blende',
    'CanIrisAbs'           => 'Kann absolute Blende',
    'CanIrisCon'           => 'Kann kontinuierliche Blende',
    'CanIrisRel'           => 'Kann relative Blende',
    'CanMove'              => 'Kann sich Bewegung',
    'CanMoveAbs'           => 'Kann absolute Bewegung',
    'CanMoveCon'           => 'Kann kontinuierliche Bewegung',
    'CanMoveDiag'          => 'Kann diagonale Bewegung',
    'CanMoveMap'           => 'Kann Mapped-Bewegung',
    'CanMoveRel'           => 'Kann relative Bewegung',
    'CanPan'               => 'Kann Pan' ,
    'CanReset'             => 'Kann Reset',
	'CanReboot'             => 'Can Reboot',
    'CanSetPresets'        => 'Kann Voreinstellungen setzen',
    'CanSleep'             => 'Kann Sleep',
    'CanTilt'              => 'Kann Neigung',
    'CanWake'              => 'Kann Wake',
    'CanWhite'             => 'Kann Weiß-Abgleich',
    'CanWhiteAbs'          => 'Kann absoluten Weiß-Abgleich',
    'CanWhiteBal'          => 'Kann Weiß-Abgleich-Balance',
    'CanWhiteCon'          => 'Kann kontinuierlichen Weiß-Abgleich',
    'CanWhiteRel'          => 'Kann relativen Weiß-Abgleich',
    'CanZoom'              => 'Kann Zoom',
    'CanZoomAbs'           => 'Kann absoluten Zoom',
    'CanZoomCon'           => 'Kann kontinuierlichen Zoom',
    'CanZoomRel'           => 'Kann relativen Zoom',
    'Cancel'               => 'Abbruch',
    'CancelForcedAlarm'    => 'Abbruch des unbedingten Alarms',
    'CaptureHeight'        => 'Erfassungshöhe',
    'CaptureMethod'        => 'Erfassungsmethode',         // Added - 2009-02-08
    'CapturePalette'       => 'Erfassungsfarbpalette',
    'CaptureResolution'    => 'Aufnahmeauflösung',     // Added - 2015-04-18
    'CaptureWidth'         => 'Erfassungsbreite',
    'Cause'                => 'Grund',
    'CheckMethod'          => 'Alarm-Prüfmethode',
    'ChooseDetectedCamera' => 'Erkannte Kamera wählen', // Added - 2009-03-31
    'ChooseFilter'         => 'Filterauswahl',
    'ChooseLogFormat'      => 'Log-Format wählen',    // Added - 2011-06-17
    'ChooseLogSelection'   => 'Log-Auswahl', // Added - 2011-06-17
    'ChoosePreset'         => 'Voreinstellung auswählen',
    'Clear'                => 'Leeren',                  // Added - 2011-06-16
    'CloneMonitor'         => 'Clone',                  // Added - 2018-08-30
    'Close'                => 'Schließen',
    'Colour'               => 'Farbe',
    'Command'              => 'Kommando',
    'Component'            => 'Komponente',              // Added - 2011-06-16
    'ConcurrentFilter'     => 'Run filter concurrently', // Added - 2018-08-30
    'Config'               => 'Konfig.',
    'ConfiguredFor'        => 'Konfiguriert für',
    'ConfirmDeleteEvents'  => 'Sind Sie sicher, dass Sie die ausgewählten Ereignisse löschen wollen?',
    'ConfirmPassword'      => 'Passwortbestätigung',
    'ConjAnd'              => 'und',
    'ConjOr'               => 'oder',
    'Console'              => 'Konsole',
    'ContactAdmin'         => 'Bitte kontaktieren Sie den Administrator für weitere Details',
    'Continue'             => 'Weiter',
    'Contrast'             => 'Kontrast',
    'Control'              => 'Steuerung',
    'ControlAddress'       => 'Steueradresse',
    'ControlCap'           => 'Steuermöglichkeit',
    'ControlCaps'          => 'Steuermöglichkeiten',
    'ControlDevice'        => 'Steuergerät',
    'ControlType'          => 'Steuertyp',
    'Controllable'         => 'steuerbar',
    'Current'              => 'Aktuell',                // Added - 2015-04-18
    'Cycle'                => 'Zyklus',
    'CycleWatch'           => 'Zeitzyklus',
    'DateTime'             => 'Datum/Zeit',              // Added - 2011-06-16
    'Day'                  => 'Tag',
    'Debug'                => 'Debug',
    'DefaultRate'          => 'Standardrate',
    'DefaultScale'         => 'Standardskalierung',
    'DefaultView'          => 'Standardansicht',
    'Deinterlacing'        => 'Zeilenentflechtung',        // Added - 2015-04-18
    'Delay'                => 'Verzögerung',                  // Added - 2015-04-18
    'Delete'               => 'Löschen',
    'DeleteAndNext'        => 'Löschen & Nächstes',
    'DeleteAndPrev'        => 'Löschen & Vorheriges',
    'DeleteSavedFilter'    => 'Lösche gespeichertes Filter',
    'Description'          => 'Beschreibung',
    'DetectedCameras'      => 'Erkannte Kameras',       // Added - 2009-03-31
    'DetectedProfiles'     => 'Erkannte Profile',      // Added - 2015-04-18
    'Device'               => 'Gerät',                 // Added - 2009-02-08
    'DeviceChannel'        => 'Gerätekanal',
    'DeviceFormat'         => 'Geräteformat',
    'DeviceNumber'         => 'Gerätenummer',
    'DevicePath'           => 'Gerätepfad',
    'Devices'              => 'Geräte',
    'Dimensions'           => 'Abmessungen',
    'DisableAlarms'        => 'Alarme abschalten',
    'Disk'                 => 'Platte',
    'Display'              => 'Anzeige',                // Added - 2011-01-30
    'Displaying'           => 'Gezeigt',             // Added - 2011-06-16
    'DoNativeMotionDetection'=> 'Do Native Motion Detection',
    'Donate'               => 'Bitte spenden Sie.',
    'DonateAlready'        => 'Nein, ich habe schon gespendet',
    'DonateEnticement'     => 'Sie benutzen ZoneMinder nun schon eine Weile und es ist hoffentlich eine nützliche Applikation zur Verbesserung Ihrer Heim- oder Arbeitssicherheit. Obwohl ZoneMinder eine freie Open-Source-Software ist und bleiben wird, entstehen Kosten bei der Entwicklung und dem Support.<br><br>Falls Sie ZoneMinder für Weiterentwicklung in der Zukunft unterstützen möchten, denken Sie bitte über eine Spende für das Projekt unter der Webadresse https://zoneminder.com/donate/ oder über nachfolgend stehende Option nach. Spenden sind, wie der Name schon sagt, immer freiwillig. Dem Projekt helfen kleine genauso wie größere Spenden sehr weiter und ein herzlicher Dank ist jedem Spender sicher.<br><br>Vielen Dank dafür, dass sie ZoneMinder benutzen. Vergessen Sie nicht die Foren unter ZoneMinder.com, um Support zu erhalten und Ihre Erfahrung mit ZoneMinder zu verbessern!',
    'DonateRemindDay'      => 'Noch nicht, erinnere mich in einem Tag noch mal.',
    'DonateRemindHour'     => 'Noch nicht, erinnere mich in einer Stunde noch mal.',
    'DonateRemindMonth'    => 'Noch nicht, erinnere mich in einem Monat noch mal.',
    'DonateRemindNever'    => 'Nein, ich möchte nicht spenden, niemals erinnern.',
    'DonateRemindWeek'     => 'Noch nicht, erinnere mich in einer Woche noch mal.',
    'DonateYes'            => 'Ja, ich möchte jetzt spenden.',
    'Download'             => 'Download',
    'DownloadVideo'        => 'Download Video',         // Added - 2018-08-30
    'DuplicateMonitorName' => 'Monitornamen Duplizieren', // Added - 2009-03-31
    'Duration'             => 'Dauer',
    'Edit'                 => 'Bearbeiten',
    'EditLayout'           => 'Edit Layout',            // Added - 2018-08-30
    'Email'                => 'E-Mail',
    'EnableAlarms'         => 'Alarme aktivieren',
    'Enabled'              => 'Aktiviert',
    'EnterNewFilterName'   => 'Neuen Filternamen eingeben',
    'Error'                => 'Fehler',
    'ErrorBrackets'        => 'Fehler. Bitte nur gleiche Anzahl offener und geschlossener Klammern.',
    'ErrorValidValue'      => 'Fehler. Bitte alle Werte auf richtige Eingabe prüfen',
    'Etc'                  => 'etc.',
    'Event'                => 'Ereignis',
    'EventFilter'          => 'Ereignisfilter',
    'EventId'              => 'Ereignis-ID',
    'EventName'            => 'Ereignisname',
    'EventPrefix'          => 'Ereignis-Präfix',
    'Events'               => 'Ereignisse',
    'Exclude'              => 'Ausschluss;',
    'Execute'              => 'Ausführen',
    'Exif'                 => 'Embed EXIF data into image', // Added - 2018-08-30
    'Export'               => 'Exportieren',
    'ExportDetails'        => 'Exportiere Ereignis-Details',
    'ExportFailed'         => 'Exportieren fehlgeschlagen',
    'ExportFormat'         => 'Exportiere Dateiformat',
    'ExportFormatTar'      => 'TAR (Bandarchiv)',
    'ExportFormatZip'      => 'ZIP (Komprimiert)',
    'ExportFrames'         => 'Exportiere Bilddetails',
    'ExportImageFiles'     => 'Exportiere Bilddateien',
    'ExportLog'            => 'Log Exportieren',             // Added - 2011-06-17
    'ExportMiscFiles'      => 'Exportiere andere Dateien (falls vorhanden)',
    'ExportOptions'        => 'Exportoptionen',
    'ExportSucceeded'      => 'Export Erfolgreich',       // Added - 2009-02-08
    'ExportVideoFiles'     => 'Exportiere Videodateien (falls vorhanden)',
    'Exporting'            => 'Exportiere',
    'FPS'                  => 'FPS',
    'FPSReportInterval'    => 'FPS-Meldeintervall',
    'FTP'                  => 'FTP',
    'Far'                  => 'Weit',
    'FastForward'          => 'Schnell vorwärts',
    'Feed'                 => 'Eingabe',
    'Ffmpeg'               => 'Ffmpeg',                 // Added - 2009-02-08
    'File'                 => 'Datei',
    'Filter'               => 'Filter',                 // Added - 2015-04-18
    'FilterArchiveEvents'  => 'Archivierung aller Treffer',
    'FilterDeleteEvents'   => 'Löschen aller Treffer',
    'FilterEmailEvents'    => 'Detaillierte E-Mail zu allen Treffern',
    'FilterExecuteEvents'  => 'Ausführen bei allen Treffern',
    'FilterLog'            => 'Log filtern',             // Added - 2015-04-18
    'FilterMessageEvents'  => 'Detaillierte Nachricht zu allen Treffern',
    'FilterMoveEvents'     => 'Move all matches',       // Added - 2018-08-30
    'FilterPx'             => 'Filter-Pixel',
    'FilterUnset'          => 'Sie müssen eine Breite und Höhe für das Filter angeben',
    'FilterUpdateDiskSpace'=> 'Update used disk space', // Added - 2018-08-30
    'FilterUploadEvents'   => 'Hochladen aller Treffer',
    'FilterVideoEvents'    => 'Video für alle Treffer erstellen',
    'Filters'              => 'Filter',
    'First'                => 'Erstes',
    'FlippedHori'          => 'Horizontal gespiegelt',
    'FlippedVert'          => 'Vertikal gespiegelt',
    'FnMocord'              => 'Mocord',            // Added 2013.08.16.
    'FnModect'              => 'Modect',            // Added 2013.08.16.
    'FnMonitor'             => 'Monitor',            // Added 2013.08.16.
    'FnNodect'              => 'Nodect',            // Added 2013.08.16.
    'FnNone'                => 'Keine',            // Added 2013.08.16.
    'FnRecord'              => 'Record',            // Added 2013.08.16.
    'Focus'                => 'Fokus',
    'ForceAlarm'           => 'Alarm erzwingen',
    'Format'               => 'Format',
    'Frame'                => 'Bild',
    'FrameId'              => 'Bild-ID',
    'FrameRate'            => 'Abspielgeschwindigkeit',
    'FrameSkip'            => 'Bilder auslassen',
    'Frames'               => 'Bilder',
    'Func'                 => 'Fkt.',
    'Function'             => 'Funktion',
    'Gain'                 => 'Verstärkung',
    'General'              => 'Allgemeines',
    'GenerateDownload'     => 'Generate Download',      // Added - 2018-08-30
    'GenerateVideo'        => 'Erzeuge Video',
    'GeneratingVideo'      => 'Erzeuge Video...',
    'GoToZoneMinder'       => 'Besuche ZoneMinder.com',
    'Grey'                 => 'Grau',
    'Group'                => 'Gruppe',
    'Groups'               => 'Gruppen',
    'HasFocusSpeed'        => 'Hat Fokus-Geschwindigkeit',
    'HasGainSpeed'         => 'Hat Verstärkungs-Geschwindigkeit',
    'HasHomePreset'        => 'Hat Standardvoreinstellungen',
    'HasIrisSpeed'         => 'Hat Blendengeschwindigkeit',
    'HasPanSpeed'          => 'Hat Pan-Geschwindigkeit',
    'HasPresets'           => 'Hat Voreinstellungen',
    'HasTiltSpeed'         => 'Hat Neigungsgeschwindigkeit',
    'HasTurboPan'          => 'Hat Turbo-Pan',
    'HasTurboTilt'         => 'Hat Turbo-Neigung',
    'HasWhiteSpeed'        => 'Hat Weiß-Abgleichgeschwindigkeit',
    'HasZoomSpeed'         => 'Hat Zoom-Geschwindigkeit',
    'High'                 => 'hohe',
    'HighBW'               => 'Hohe B/W',
    'Home'                 => 'Home',
    'Hostname'             => 'Hostname',               // Added - 2018-08-30
    'Hour'                 => 'Stunde',
    'Hue'                  => 'Farbton',
    'Id'                   => 'ID',
    'Idle'                 => 'Leerlauf',
    'Ignore'               => 'Ignoriere',
    'Image'                => 'Bild',
    'ImageBufferSize'      => 'Bildpuffergröße',
    'Images'               => 'Bilder',
    'In'                   => 'In',
    'Include'              => 'Einschluss',
    'Inverted'             => 'Invertiert',
    'Iris'                 => 'Blende',
    'KeyString'            => 'Schlüsselwort',
    'Label'                => 'Bezeichnung',
    'Language'             => 'Sprache',
    'Last'                 => 'Letztes',
    'Layout'               => 'Layout',                 // Added - 2009-02-08
    'Level'                => 'Stufe',                  // Added - 2011-06-16
    'Libvlc'               => 'Libvlc',
    'LimitResultsPost'     => 'Ergebnisse;', // This is used at the end of the phrase 'Limit to first N results only'
    'LimitResultsPre'      => 'Begrenze nur auf die ersten', // This is used at the beginning of the phrase 'Limit to first N results only'
    'Line'                 => 'Zeile',                   // Added - 2011-06-16
    'LinkedMonitors'       => 'Verbundene Monitore',
    'List'                 => 'Liste',
    'ListMatches'          => 'List Matches',           // Added - 2018-08-30
    'Load'                 => 'Last',
    'Local'                => 'Lokal',
    'Log'                  => 'Log',                    // Added - 2011-06-16
    'LoggedInAs'           => 'Angemeldet als',
    'Logging'              => 'Logging',                // Added - 2011-06-16
    'LoggingIn'            => 'Anmelden',
    'Login'                => 'Anmeldung',
    'Logout'               => 'Abmelden',
    'Logs'                 => 'Logs',                   // Added - 2011-06-17
    'Low'                  => 'niedrige',
    'LowBW'                => 'Niedrige B/W',
    'Main'                 => 'Haupt',
    'Man'                  => 'Man',
    'Manual'               => 'Manual',
    'Mark'                 => 'Markieren',
    'Max'                  => 'Max',
    'MaxBandwidth'         => 'Maximale Bandbreite',
    'MaxBrScore'           => 'Maximale<br />Wertung',
    'MaxFocusRange'        => 'Maximaler Fokusbereich',
    'MaxFocusSpeed'        => 'Maximale Fokusgeschwindigkeit',
    'MaxFocusStep'         => 'Maximale Fokusstufe',
    'MaxGainRange'         => 'Maximaler Verstärkungsbereich',
    'MaxGainSpeed'         => 'Maximale Verstärkungsgeschwindigkeit',
    'MaxGainStep'          => 'Maximale Verstärkungsstufe',
    'MaxIrisRange'         => 'Maximaler Blendenbereich',
    'MaxIrisSpeed'         => 'Maximale Blendengeschwindigkeit',
    'MaxIrisStep'          => 'Maximale Blendenstufe',
    'MaxPanRange'          => 'Maximaler Pan-Bereich',
    'MaxPanSpeed'          => 'Maximale Pan-Geschw.',
    'MaxPanStep'           => 'Maximale Pan-Stufe',
    'MaxTiltRange'         => 'Maximaler Neig.-Bereich',
    'MaxTiltSpeed'         => 'Maximale Neig.-Geschw.',
    'MaxTiltStep'          => 'Maximale Neig.-Stufe',
    'MaxWhiteRange'        => 'Maximaler Weiß-Abgl.bereich',
    'MaxWhiteSpeed'        => 'Maximale Weiß-Abgl.geschw.',
    'MaxWhiteStep'         => 'Maximale Weiß-Abgl.stufe',
    'MaxZoomRange'         => 'Maximaler Zoom-Bereich',
    'MaxZoomSpeed'         => 'Maximale Zoom-Geschw.',
    'MaxZoomStep'          => 'Maximale Zoom-Stufe',
    'MaximumFPS'           => 'Maximale FPS',
    'Medium'               => 'mittlere',
    'MediumBW'             => 'Mittlere B/W',
    'Message'              => 'Nachricht',                // Added - 2011-06-16
    'MinAlarmAreaLtMax'    => 'Der minimale Alarmbereich sollte kleiner sein als der maximale',
    'MinAlarmAreaUnset'    => 'Sie müssen einen Minimumwert an Alarmflächenpixeln angeben',
    'MinBlobAreaLtMax'     => 'Die minimale Blob-Fläche muss kleiner sein als die maximale',
    'MinBlobAreaUnset'     => 'Sie müssen einen Minimumwert an Blobflächenpixeln angeben',
    'MinBlobLtMinFilter'   => 'Die minimale Blob-Fläche sollte kleiner oder gleich der minimalen Filterfläche sein',
    'MinBlobsLtMax'        => 'Die minimalen Blobs müssen kleiner sein als die maximalen',
    'MinBlobsUnset'        => 'Sie müssen einen Minimumwert an Blobs angeben',
    'MinFilterAreaLtMax'   => 'Die minimale Filterfläche sollte kleiner sein als die maximale',
    'MinFilterAreaUnset'   => 'Sie müssen einen Minimumwert an Filterpixeln angeben',
    'MinFilterLtMinAlarm'  => 'Die minimale Filterfläche sollte kleiner oder gleich der minimalen Alarmfläche sein',
    'MinFocusRange'        => 'Min. Fokusbereich',
    'MinFocusSpeed'        => 'Min. Fokusgeschw.',
    'MinFocusStep'         => 'Min. Fokusstufe',
    'MinGainRange'         => 'Min. Verstärkungsbereich',
    'MinGainSpeed'         => 'Min. Verstärkungsgeschwindigkeit',
    'MinGainStep'          => 'Min. Verstärkungsstufe',
    'MinIrisRange'         => 'Min. Blendenbereich',
    'MinIrisSpeed'         => 'Min. Blendengeschwindigkeit',
    'MinIrisStep'          => 'Min. Blendenstufe',
    'MinPanRange'          => 'Min. Pan-Bereich',
    'MinPanSpeed'          => 'Min. Pan-Geschwindigkeit',
    'MinPanStep'           => 'Min. Pan-Stufe',
    'MinPixelThresLtMax'   => 'Der minimale Pixelschwellwert muss kleiner sein als der maximale',
    'MinPixelThresUnset'   => 'Sie müssen einen minimalen Pixel-Schwellenwert angeben',
    'MinTiltRange'         => 'Min. Neigungsbereich',
    'MinTiltSpeed'         => 'Min. Neigungsgeschwindigkeit',
    'MinTiltStep'          => 'Min. Neigungsstufe',
    'MinWhiteRange'        => 'Min. Weiß-Abgleichbereich',
    'MinWhiteSpeed'        => 'Min. Weiß-Abgleichgeschwindigkeit',
    'MinWhiteStep'         => 'Min. Weiß-Abgleichstufe',
    'MinZoomRange'         => 'Min. Zoom-Bereich',
    'MinZoomSpeed'         => 'Min. Zoom-Geschwindigkeit',
    'MinZoomStep'          => 'Min. Zoom-Stufe',
    'Misc'                 => 'Verschiedenes',
    'Mode'                 => 'Modus',                   // Added - 2015-04-18
    'Monitor'              => 'Monitor',
    'MonitorIds'           => 'Monitor-ID',
    'MonitorPreset'        => 'Monitor-Voreinstellung',
    'MonitorPresetIntro'   => 'Wählen Sie eine geeignete Voreinstellung aus der folgenden Liste.<br><br>Bitte beachten Sie, dass dies mögliche Einstellungen von Ihnen am Monitor überschreiben kann.<br><br>',
    'MonitorProbe'         => 'Kamerasuche',          // Added - 2009-03-31
    'MonitorProbeIntro'    => 'Die nachfolgende Liste zeigt erkannte Analog- und Netzwerkkameras, ob sie bereits genutzt werden und ob sie zur Auswahl verfügbar sind.<br/><br/>Wähle den gewünschten Eintrag aus der folgenden Liste.<br/><br/>Bitte Beachten: Nicht alle  Kameras können erkannt werden. Die Auswahl einer Kamera kann bereits eingetragene Werte im aktuellen Monitor überschreiben.<br/><br/>', // Added - 2009-03-31
    'Monitors'             => 'Monitore',
    'Montage'              => 'Montage',
    'MontageReview'        => 'Montage Review',         // Added - 2018-08-30
    'Month'                => 'Monat',
    'More'                 => 'Mehr',                   // Added - 2011-06-16
    'MotionFrameSkip'      => 'Motion Frame Skip',
    'Move'                 => 'Bewegung',
    'Mtg2widgrd'           => '2 Spalten',              // Added 2013.08.15.
    'Mtg3widgrd'           => '3 Spalten',              // Added 2013.08.15.
    'Mtg3widgrx'           => '3 Spalten, skaliert, vergr. bei Alarm',              // Added 2013.08.15.
    'Mtg4widgrd'           => '4 Spalten',              // Added 2013.08.15.
    'MtgDefault'           => 'Standard',              // Added 2013.08.15.
    'MustBeGe'             => 'muss größer oder gleich sein wie',
    'MustBeLe'             => 'muss kleiner oder gleich sein wie',
    'MustConfirmPassword'  => 'Sie müssen das Passwort bestätigen.',
    'MustSupplyPassword'   => 'Sie müssen ein Passwort vergeben.',
    'MustSupplyUsername'   => 'Sie müssen einen Usernamen vergeben.',
    'Name'                 => 'Name',
    'Near'                 => 'Nah',
    'Network'              => 'Netz',
    'New'                  => 'Neu',
    'NewGroup'             => 'Neue Gruppe',
    'NewLabel'             => 'Neuer Bezeichner',
    'NewPassword'          => 'Neues Passwort',
    'NewState'             => 'Neuer Status',
    'NewUser'              => 'Neuer Benutzer',
    'Next'                 => 'Nächstes',
    'No'                   => 'Nein',
    'NoDetectedCameras'    => 'Keine Kameras erkannt',    // Added - 2009-03-31
    'NoDetectedProfiles'   => 'No Detected Profiles',   // Added - 2018-08-30
    'NoFramesRecorded'     => 'Es gibt keine Aufnahmen von diesem Ereignis.',
    'NoGroup'              => 'Keine Gruppe',
    'NoSavedFilters'       => 'Keine gespeicherten Filter',
    'NoStatisticsRecorded' => 'Keine Statistik für dieses Ereignis/diese Bilder',
    'None'                 => 'ohne',
    'NoneAvailable'        => 'Nichts verfügbar',
    'Normal'               => 'Normal',
    'Notes'                => 'Bemerkungen',
    'NumPresets'           => 'Nummerierte Voreinstellungen',
    'Off'                  => 'Aus',
    'On'                   => 'An',
    'OnvifCredentialsIntro'=> 'Bitte den Benutzernamen und das Passwort für die gewählte Kamera eintragen.<br/>Der hier eingetragene Benutzer wird erstellt mitsamt des Passworts, falls kein Benutzer für diese Kamera erstellt wurde.<br/><br/>', // Added - 2015-04-18
    'OnvifProbe'           => 'ONVIF',                  // Added - 2015-04-18
    'OnvifProbeIntro'      => 'Die folgende Liste zeigt erkannte ONVIF Kameras, ob sie bereits genutzt werden und ob sie zur Auswahl verfügbar sind.<br/><br/>Wähle den gewünschten Eintrag aus der folgenden Liste.<br/><br/>Bitte Beachten: Nicht alle  Kameras können erkannt werden. Die Auswahl einer Kamera kann bereits eingetragene Werte im aktuellen Monitor überschreiben.<br/><br/>', // Added - 2015-04-18
    'OpEq'                 => 'gleich zu',
    'OpGt'                 => 'groesser als',
    'OpGtEq'               => 'groesser oder gleich wie',
    'OpIn'                 => 'in Satz',
    'OpIs'                 => 'is',                     // Added - 2018-08-30
    'OpIsNot'              => 'is not',                 // Added - 2018-08-30
    'OpLt'                 => 'kleiner als',
    'OpLtEq'               => 'kleiner oder gleich wie',
    'OpMatches'            => 'zutreffend',
    'OpNe'                 => 'nicht gleich',
    'OpNotIn'              => 'nicht im Satz',
    'OpNotMatches'         => 'nicht zutreffend',
    'Open'                 => 'öffnen',
    'OptionHelp'           => 'Hilfe',
    'OptionRestartWarning' => 'Veränderungen werden erst nach einem Neustart des Programms aktiv.\nFür eine sofortige änderung starten Sie das Programm bitte neu.',
    'OptionalEncoderParam' => 'Optional Encoder Parameters', // Added - 2018-08-30
    'Options'              => 'Optionen',
    'OrEnterNewName'       => 'oder neuen Namen eingeben',
    'Order'                => 'Reihenfolge',
    'Orientation'          => 'Ausrichtung',
    'Out'                  => 'Aus',
    'OverwriteExisting'    => 'überschreibe bestehende',
    'Paged'                => 'Seitennummeriert',
    'Pan'                  => 'Pan',
    'PanLeft'              => 'Pan-Links',
    'PanRight'             => 'Pan-Rechts',
    'PanTilt'              => 'Pan/Neigung',
    'Parameter'            => 'Parameter',
    'Password'             => 'Passwort',
    'PasswordsDifferent'   => 'Die Passwörter sind unterschiedlich',
    'Paths'                => 'Pfade',
    'Pause'                => 'Pause',
    'Phone'                => 'Telefon',
    'PhoneBW'              => 'Tel. B/W',
    'Pid'                  => 'PID',                    // Added - 2011-06-16
    'PixelDiff'            => 'Pixel-Differenz',
    'Pixels'               => 'Pixel',
    'Play'                 => 'Abspielen',
    'PlayAll'              => 'Alle zeigen',
    'PleaseWait'           => 'Bitte warten',
    'Plugins'              => 'Plugins',
    'Point'                => 'Punkt',
    'PostEventImageBuffer' => 'Nachereignispuffer',
    'PreEventImageBuffer'  => 'Vorereignispuffer',
    'PreserveAspect'       => 'Seitenverhältnis beibehalten',
    'Preset'               => 'Voreinstellung',
    'Presets'              => 'Voreinstellungen',
    'Prev'                 => 'Vorheriges',
    'Probe'                => 'Suchen',                  // Added - 2009-03-31
    'ProfileProbe'         => 'Streamsonde',           // Added - 2015-04-18
    'ProfileProbeIntro'    => 'Die folgende Liste zeigt die verfügbaren Streamingprofile der ausgewählten Kamera.<br/><br/>Wähle den gewünschten Eintrag aus der folgenden Liste.<br/><br/>Bitte Beachten: Zoneminder kann keine zusätzlichen Profile konfigurieren. Die Auswahl einer Kamera kann bereits eingetragene Werte im aktuellen Monitor überschreiben.<br/><br/>', // Added - 2015-04-18
    'Progress'             => 'Fortschritt',               // Added - 2015-04-18
    'Protocol'             => 'Protokoll',
    'RTSPDescribe'         => 'Use RTSP Response Media URL', // Added - 2018-08-30
    'RTSPTransport'        => 'RTSP Transport Protocol', // Added - 2018-08-30
    'Rate'                 => 'Abspielgeschwindigkeit',
    'Real'                 => 'Real',
    'RecaptchaWarning'     => 'Your reCaptcha secret key is invalid. Please correct it, or reCaptcha will not work', // Added - 2018-08-30
    'Record'               => 'Aufnahme',
    'RecordAudio'          => 'Whether to store the audio stream when saving an event.', // Added - 2018-08-30
    'RefImageBlendPct'     => 'Referenz-Bildblende',
    'Refresh'              => 'Aktualisieren',
    'Remote'               => 'Remote',
    'RemoteHostName'       => 'Remote Hostname',
    'RemoteHostPath'       => 'Remote Hostpfad',
    'RemoteHostPort'       => 'Remote Hostport',
    'RemoteHostSubPath'    => 'Remote Hostunterpfad',    // Added - 2009-02-08
    'RemoteImageColours'   => 'Remote Bildfarbe',
    'RemoteMethod'         => 'Remote Methode',          // Added - 2009-02-08
    'RemoteProtocol'       => 'Remote Protokol',        // Added - 2009-02-08
    'Rename'               => 'Umbenennen',
    'Replay'               => 'Wiederholung',
    'ReplayAll'            => 'Alle Ereignisse',
    'ReplayGapless'        => 'Lückenlose Ereignisse',
    'ReplaySingle'         => 'Einzelereignis',
    'ReportEventAudit'     => 'Audit Events Report',    // Added - 2018-08-30
    'Reset'                => 'Zurücksetzen',
    'ResetEventCounts'     => 'Lösche Ereignispunktzahl',
    'Restart'              => 'Neustart',
    'Restarting'           => 'Neustarten',
    'RestrictedCameraIds'  => 'Verbotene Kamera-ID',
    'RestrictedMonitors'   => 'Eingeschränkte Monitore',
    'ReturnDelay'          => 'Rückkehr-Verzögerung',
    'ReturnLocation'       => 'Rückkehrpunkt',
    'Rewind'               => 'Zurückspulen',
    'RotateLeft'           => 'Drehung links',
    'RotateRight'          => 'Drehung rechts',
    'RunLocalUpdate'       => 'Für Update "zmupdate.pl" ausführen', // Added - 2011-05-25
    'RunMode'              => 'Betriebsmodus',
    'RunState'             => 'Laufender Status',
    'Running'              => 'In Betrieb',
    'Save'                 => 'Speichern',
    'SaveAs'               => 'Speichere als',
    'SaveFilter'           => 'Speichere Filter',
    'SaveJPEGs'            => 'Save JPEGs',             // Added - 2018-08-30
    'Scale'                => 'Skalierung',
    'Score'                => 'Wertung',
    'Secs'                 => 'Sekunden',
    'Sectionlength'        => 'Sektionslänge',
    'Select'               => 'Auswahl',
    'SelectFormat'         => 'Format auswählen',          // Added - 2011-06-17
    'SelectLog'            => 'Log auswählen',             // Added - 2011-06-17
    'SelectMonitors'       => 'Wähle Monitore',
    'SelfIntersecting'     => 'Die Polygonränder dürfen sich nicht überschneiden.',
    'Set'                  => 'Setze',
    'SetNewBandwidth'      => 'Setze neue Bandbreite',
    'SetPreset'            => 'Setze Voreinstellung',
    'Settings'             => 'Einstellungen',
    'ShowFilterWindow'     => 'Zeige Filterfenster',
    'ShowTimeline'         => 'Zeige Zeitstrahl',
    'SignalCheckColour'    => 'Farbe des Signalchecks',
    'SignalCheckPoints'    => 'Signal Check Points',    // Added - 2018-08-30
    'Size'                 => 'Größe',
    'SkinDescription'      => 'Wähle den standard Skin für diesen Computer.', // Added - 2011-01-30
    'Sleep'                => 'Schlaf',
    'SortAsc'              => 'aufsteigend',
    'SortBy'               => 'Sortieren nach',
    'SortDesc'             => 'absteigend',
    'Source'               => 'Quelle',
    'SourceColours'        => 'Quellenfarben',         // Added - 2009-02-08
    'SourcePath'           => 'Quellenpfad',            // Added - 2009-02-08
    'SourceType'           => 'Quellentyp',
    'Speed'                => 'Geschwindigkeit',
    'SpeedHigh'            => 'Hohe Geschwindigkeit',
    'SpeedLow'             => 'Niedrige Geschwindigkeit',
    'SpeedMedium'          => 'Mittlere Geschwindigkeit',
    'SpeedTurbo'           => 'Turbo-Geschwindigkeit',
    'Start'                => 'Start',
    'State'                => 'Status',
    'Stats'                => 'Statistik',
    'Status'               => 'Status',
    'StatusConnected'      => 'Capturing',              // Added - 2018-08-30
    'StatusNotRunning'     => 'Not Running',            // Added - 2018-08-30
    'StatusRunning'        => 'Not Capturing',          // Added - 2018-08-30
    'StatusUnknown'        => 'Unknown',                // Added - 2018-08-30
    'Step'                 => 'Stufe',
    'StepBack'             => 'Einen Schritt rückwärts',
    'StepForward'          => 'Einen Schritt vorwärts',
    'StepLarge'            => 'Großer Schritt',
    'StepMedium'           => 'Mittlere Schhritt',
    'StepNone'             => 'Keine Schritt',
    'StepSmall'            => 'Kleiner Schritt',
    'Stills'               => 'Standbilder',
    'Stop'                 => 'Stop',
    'Stopped'              => 'Gestoppt',
    'StorageArea'          => 'Storage Area',           // Added - 2018-08-30
    'StorageScheme'        => 'Scheme',                 // Added - 2018-08-30
    'Stream'               => 'Stream',
    'StreamReplayBuffer'   => 'Stream-Wiedergabe-Bildpuffer',
    'Submit'               => 'Absenden',
    'System'               => 'System',
    'SystemLog'            => 'System-Log',             // Added - 2011-06-16
    'TargetColorspace'     => 'Zielfarbbereich',      // Added - 2015-04-18
    'Tele'                 => 'Tele',
    'Thumbnail'            => 'Miniaturbild',
    'Tilt'                 => 'Neigung',
    'Time'                 => 'Zeit',
    'TimeDelta'            => 'Zeitdifferenz',
    'TimeStamp'            => 'Zeitstempel',
    'Timeline'             => 'Zeitstrahl',
    'TimelineTip1'         => 'Fahren Sie mit der Maus über die Grafik, um eine Momentaufnahme der Bild- und Ereignisdetails zusehen.',              // Added 2013.08.15.
    'TimelineTip2'         => 'Klicken Sie auf den farbig markierten Bereichen der Grafik oder das Bild, um das Ereignis zu sehen.',              // Added 2013.08.15.
    'TimelineTip3'         => 'Klicken Sie auf den Hintergrund, um in einen kleineren Zeitraum zu vergrößern.',              // Added 2013.08.15.
    'TimelineTip4'         => 'Verwenden Sie die Steuerelemente unten, um zu Zoomen oder navigieren Sie vorwärts und rückwärts durch die Zeit.',              // Added 2013.08.15.
    'Timestamp'            => 'Zeitstempel',
    'TimestampLabelFormat' => 'Format des Zeitstempels',
    'TimestampLabelSize'   => 'Schriftgröße',
    'TimestampLabelX'      => 'Zeitstempel-X',
    'TimestampLabelY'      => 'Zeitstempel-Y',
    'Today'                => 'Heute',
    'Tools'                => 'Werkzeuge',
    'Total'                => 'Insgesamt',                 // Added - 2011-06-16
    'TotalBrScore'         => 'Gesamt-<br/>wertung',
    'TrackDelay'           => 'Nachführungsverzögerung',
    'TrackMotion'          => 'Bewegungs-Nachführung',
    'Triggers'             => 'Auslöser',
    'TurboPanSpeed'        => 'Turbo-Pan-Geschwindigkeit',
    'TurboTiltSpeed'       => 'Turbo-Neigungsgeschwindigkeit',
    'Type'                 => 'Typ',
    'Unarchive'            => 'Aus Archiv entfernen',
    'Undefined'            => 'Undefiniert',              // Added - 2009-02-08
    'Units'                => 'Einheiten',
    'Unknown'              => 'Unbekannt',
    'Update'               => 'Aktualisieren',
    'UpdateAvailable'      => 'Eine Aktualisierung für ZoneMinder ist verfügbar.',
    'UpdateNotNecessary'   => 'Es ist keine Aktualisierung verfügbar.',
    'Updated'              => 'Aktualisiert',                // Added - 2011-06-16
    'Upload'               => 'Hochladen',                 // Added - 2011-08-23
    'UseFilter'            => 'Benutze Filter',
    'UseFilterExprsPost'   => ' Filter Ausdrücke', // This is used at the end of the phrase 'use N filter expressions'
    'UseFilterExprsPre'    => 'Benutze ', // This is used at the beginning of the phrase 'use N filter expressions'
    'UsedPlugins'	   => 'Used Plugins',
    'User'                 => 'Benutzer',
    'Username'             => 'Benutzername',
    'Users'                => 'Benutzer',
    'V4L'                  => 'V4L',                    // Added - 2015-04-18
    'V4LCapturesPerFrame'  => 'Aufnahmen pro Bild',     // Added - 2015-04-18
    'V4LMultiBuffer'       => 'Multi Buffering',        // Added - 2015-04-18
    'Value'                => 'Wert',
    'Version'              => 'Version',
    'VersionIgnore'        => 'Ignoriere diese Version',
    'VersionRemindDay'     => 'Erinnere mich wieder in 1 Tag.',
    'VersionRemindHour'    => 'Erinnere mich wieder in 1 Stunde.',
    'VersionRemindNever'   => 'Informiere mich nicht mehr über neue Versionen.',
    'VersionRemindWeek'    => 'Erinnere mich wieder in 1 Woche.',
    'Video'                => 'Video',
    'VideoFormat'          => 'Videoformat',
    'VideoGenFailed'       => 'Videoerzeugung fehlgeschlagen!',
    'VideoGenFiles'        => 'Existierende Videodateien',
    'VideoGenNoFiles'      => 'Keine Videodateien gefunden.',
    'VideoGenParms'        => 'Parameter der Videoerzeugung',
    'VideoGenSucceeded'    => 'Videoerzeugung erfolgreich!',
    'VideoSize'            => 'Videogröße',
    'VideoWriter'          => 'Video Writer',           // Added - 2018-08-30
    'View'                 => 'Ansicht',
    'ViewAll'              => 'Alles ansehen',
    'ViewEvent'            => 'Zeige Ereignis',
    'ViewPaged'            => 'Seitenansicht',
    'Wake'                 => 'Aufwachen',
    'WarmupFrames'         => 'Aufwärmbilder',
    'Watch'                => 'Beobachte',
    'Web'                  => 'Web',
    'WebColour'            => 'Webfarbe',
    'WebSiteUrl'           => 'Website URL',            // Added - 2018-08-30
    'Week'                 => 'Woche',
    'White'                => 'Weiß',
    'WhiteBalance'         => 'Weiß-Abgleich',
    'Wide'                 => 'Weit',
    'X'                    => 'X',
    'X10'                  => 'X10',
    'X10ActivationString'  => 'X10-Aktivierungswert',
    'X10InputAlarmString'  => 'X10-Eingabe-Alarmwert',
    'X10OutputAlarmString' => 'X10-Ausgabe-Alarmwert',
    'Y'                    => 'Y',
    'Yes'                  => 'Ja',
    'YouNoPerms'           => 'Keine Erlaubnis zum Zugang dieser Resource.',
    'Zone'                 => 'Zone',
    'ZoneAlarmColour'      => 'Alarmfarbe (Rot/Grün/Blau)',
    'ZoneArea'             => 'Zone Area',
    'ZoneExtendAlarmFrames' => 'Alarmstatus nach Ende für Frames aufrechterhalten',
    'ZoneFilterSize'       => 'Filter-Breite/-Höhe (Pixel)',
    'ZoneMinMaxAlarmArea'  => 'Min./max. Alarmfläche',
    'ZoneMinMaxBlobArea'   => 'Min./max. Blobfläche',
    'ZoneMinMaxBlobs'      => 'Min./max. Blobs',
    'ZoneMinMaxFiltArea'   => 'Min./max. Filterfläche',
    'ZoneMinMaxPixelThres' => 'Min./max. Pixelschwellwert',
    'ZoneMinderLog'        => 'ZoneMinder Log',         // Added - 2011-06-17
    'ZoneOverloadFrames'   => 'Bildauslassrate bei Systemüberlastung',
    'Zones'                => 'Zonen',
    'Zoom'                 => 'Zoom',
    'ZoomIn'               => 'Hineinzoomen',
    'ZoomOut'              => 'Herauszoomen',
);

// Complex replacements with formatting and/or placements, must be passed through sprintf
$CLANG = array(
    'CurrentLogin'         => 'Momentan angemeldet ist \'%1$s\'',
    'EventCount'           => '%1$s %2$s', // For example '37 Events' (from Vlang below)
    'LastEvents'           => 'Letzte %1$s %2$s', // For example 'Last 37 Events' (from Vlang below)
    'LatestRelease'        => 'Die letzte Version ist v%1$s, Sie haben v%2$s.',
    'MonitorCount'         => '%1$s %2$s', // For example '4 Monitors' (from Vlang below)
    'MonitorFunction'      => 'Monitor %1$s Funktion',
    'RunningRecentVer'     => 'Sie benutzen die aktuellste Version von Zoneminder, v%s.',
    'VersionMismatch'      => 'Versionskonflikt, System-Version ist %1$s , Datenbank-Version ist %2$s.', // Added - 2011-05-25
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
    'Event'                => array( 0=>'Ereignisse', 1=>'Ereignis;', 2=>'Ereignisse' ),
    'Monitor'              => array( 0=>'Monitore', 1=>'Monitor', 2=>'Monitore' ),
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
