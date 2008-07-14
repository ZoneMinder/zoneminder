<?php
//
// ZoneMinder web German language file, $Date$, $Revision$
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

// ZoneMinder german Translation by Robert Schumann (rs at core82 dot de)

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
    '24BitColour'          => '24-Bit-Farbe',
    '8BitGrey'             => '8-Bit-Grau',
    'Action'               => 'Aktion',
    'Actual'               => 'Original',
    'AddNewControl'        => 'Neues Kontrollelement hinzuf&uuml;gen',
    'AddNewMonitor'        => 'Neuer Monitor',
    'AddNewUser'           => 'Neuer Benutzer',
    'AddNewZone'           => 'Neue Zone',
    'Alarm'                => 'Alarm',
    'AlarmBrFrames'        => 'Alarm-<br />Bilder',
    'AlarmFrame'           => 'Alarm-Bilder',
    'AlarmFrameCount'      => 'Alarm-Bildanzahl',
    'AlarmLimits'          => 'Alarm-Limits',
    'AlarmMaximumFPS'      => 'Alarm-Maximum-FPS',
    'AlarmPx'              => 'Alarm-Pixel',
    'AlarmRGBUnset'        => 'Sie m&uuml;ssen eine RGB-Alarmfarbe setzen',
    'Alert'                => 'Alarm',
    'All'                  => 'Alle',
    'ApplyingStateChange'  => 'Aktiviere neuen Status',
    'Apply'                => 'OK',
    'ArchArchived'         => 'Nur Archivierte',
    'Archive'              => 'Archivieren',
    'Archived'             => 'Archivierte',
    'ArchUnarchived'       => 'Nur Nichtarchivierte',
    'Area'                 => 'Bereich',
    'AreaUnits'            => 'Bereich (px/%)',
    'AttrAlarmFrames'      => 'Alarmbilder',
    'AttrArchiveStatus'    => 'Archivstatus',
    'AttrAvgScore'         => 'Mittlere Punktzahl',
    'AttrCause'            => 'Grund',
    'AttrDate'             => 'Datum',
    'AttrDateTime'         => 'Datum/Zeit',
    'AttrDiskBlocks'       => 'Disk-Bloecke',
    'AttrDiskPercent'      => 'Disk-Prozent',
    'AttrDuration'         => 'Dauer',
    'AttrFrames'           => 'Bilder',
    'AttrId'               => 'ID',
    'AttrMaxScore'         => 'Maximale Punktzahl',
    'AttrMonitorId'        => 'Monitor-ID',
    'AttrMonitorName'      => 'Monitorname',
    'AttrName'             => 'Name',
    'AttrNotes'            => 'Bemerkungen',
    'AttrSystemLoad'       => 'Systemlast',
    'AttrTime'             => 'Zeit',
    'AttrTotalScore'       => 'Totale Punktzahl',
    'AttrWeekday'          => 'Wochentag',
    'Auto'                 => 'Auto',
    'AutoStopTimeout'      => 'Auto-Stopp-Zeit&uuml;berschreitung',
    'AvgBrScore'           => 'Mittlere<br/>Punktzahl',
    'Background'           => 'Hintergrund',
    'BackgroundFilter'     => 'Filter im Hintergrund laufen lassen',
    'BadAlarmFrameCount'   => 'Die Bildanzahl muss ganzzahlig 1 oder gr&ouml;&szlig;er sein',
    'BadAlarmMaxFPS'       => 'Alarm-Maximum-FPS muss eine positive Ganzzahl oder eine Gleitkommazahl sein',
    'BadChannel'           => 'Der Kanal muss ganzzahlig 0 oder gr&ouml;&szlig;er sein',
    'BadDevice'            => 'Das Ger&auml;t muss eine g&uuml;ltige Systemresource sein',
    'BadFormat'            => 'Das Format muss ganzzahlig 0 oder gr&ouml;&szlig;er sein',
    'BadFPSReportInterval' => 'Der FPS-Intervall-Puffer-Z&auml;hler muss ganzzahlig 100 oder gr&ouml;&szlig;er sein',
    'BadFrameSkip'         => 'Der Auslassz&auml;hler f&uuml;r Frames muss ganzzahlig 0 oder gr&ouml;&szlig;er sein',
    'BadHeight'            => 'Die H&ouml;he muss auf einen g&uuml;ltigen Wert eingestellt sein',
    'BadHost'              => 'Der Host muss auf eine g&uuml;ltige IP-Adresse oder einen Hostnamen (ohne http://) eingestellt sein',
    'BadImageBufferCount'  => 'Die Gr&ouml;&szlig;e des Bildpuffers muss ganzzahlig 10 oder gr&ouml;&szlig;er sein',
    'BadLabelX'            => 'Die x-Koordinate der Bezeichnung muss ganzzahlig 0 oder gr&ouml;&szlig;er sein',
    'BadLabelY'            => 'Die y-Koordinate der Bezeichnung muss ganzzahlig 0 oder gr&ouml;&szlig;er sein',
    'BadMaxFPS'            => 'Maximum-FPS muss eine positive Ganzzahl oder eine Gleitkommazahl sein',
    'BadNameChars'         => 'Namen d&uuml;rfen nur aus Buchstaben, Zahlen und Trenn- oder Unterstrichen bestehen',
    'BadPath'              => 'Der Pfad muss auf einen g&uuml;ltigen Wert eingestellt sein',
    'BadPort'              => 'Der Port muss auf eine g&uuml;ltige Zahl eingestellt sein',
    'BadPostEventCount'    => 'Der Z&auml;hler f&uuml;r die Ereignisfolgebilder muss ganzzahlig 0 oder gr&ouml;&szlig;er sein',
    'BadPreEventCount'     => 'Der Z&auml;hler f&uuml;r die Ereignisvorlaufbilder muss mindestens ganzzahlig 0 und kleiner als die Bildpuffergr&ouml;&szlig;e sein',
    'BadRefBlendPerc'      => 'Der Referenz-Blenden-Prozentwert muss ganzzahlig 0 oder gr&ouml;&szlig;er sein',
    'BadSectionLength'     => 'Die Bereichsl&auml;nge muss ganzzahlig 0 oder gr&ouml;&szlig;er sein',
    'BadSignalCheckColour' => 'Die Signalpr&uuml;ffarbe muss auf einen g&uuml;ltigen Farbwert eingestellt sein',
    'BadStreamReplayBuffer'=> 'Der Wiedergabestrompuffer tream replay buffer must be an integer of zero or more',
    'BadWarmupCount'       => 'Die Anzahl der Vorwärmbilder muss ganzzahlig 0 oder gr&ouml;&szlig;er sein',
    'BadWebColour'         => 'Die Webfarbe muss auf einen g&uuml;ltigen Farbwert eingestellt sein',
    'BadWidth'             => 'Die Breite muss auf einen g&uuml;ltigen Wert eingestellt sein',
    'Bandwidth'            => 'Bandbreite',
    'BlobPx'               => 'Blob-Pixel',
    'Blobs'                => 'Blobs',
    'BlobSizes'            => 'Blobgr&ouml;&szlig;e',
    'Brightness'           => 'Helligkeit',
    'Buffers'              => 'Puffer',
    'CanAutoFocus'         => 'Kann Autofokus',
    'CanAutoGain'          => 'Kann Auto-Verst&auml;rkung',
    'CanAutoIris'          => 'Kann Auto-Iris',
    'CanAutoWhite'         => 'Kann Auto-Wei&szlig;-Abgleich',
    'CanAutoZoom'          => 'Kann Auto-Zoom',
    'Cancel'               => 'Abbruch',
    'CancelForcedAlarm'    => 'Abbruch des unbedingten Alarms',
    'CanFocusAbs'          => 'Kann absoluten Fokus',
    'CanFocusCon'          => 'Kann kontinuierlichen Fokus',
    'CanFocus'             => 'Kann&nbsp;Fokus',
    'CanFocusRel'          => 'Kann relativen Fokus',
    'CanGainAbs'           => 'Kann absolute Verst&auml;rkung',
    'CanGainCon'           => 'Kann kontinuierliche Verst&auml;rkung',
    'CanGain'              => 'Kann Verst&auml;rkung',
    'CanGainRel'           => 'Kann relative Verst&auml;kung',
    'CanIrisAbs'           => 'Kann absolute Iris',
    'CanIrisCon'           => 'Kann kontinuierliche Iris',
    'CanIris'              => 'Kann&nbsp;Iris',
    'CanIrisRel'           => 'Kann relative Iris',
    'CanMoveAbs'           => 'Kann absolute Bewegung',
    'CanMoveCon'           => 'Kann kontinuierliche Bewegung',
    'CanMoveDiag'          => 'Kann diagonale Bewegung',
    'CanMove'              => 'Kann&nbsp;Bewegung',
    'CanMoveMap'           => 'Kann Mapped-Bewegung',
    'CanMoveRel'           => 'Kann relative Bewegung',
    'CanPan'               => 'Kann&nbsp;Pan' ,
    'CanReset'             => 'Kann&nbsp;Reset',
    'CanSetPresets'        => 'Kann Voreinstellungen setzen',
    'CanSleep'             => 'Kann&nbsp;Sleep',
    'CanTilt'              => 'Kann&nbsp;Neigung',
    'CanWake'              => 'Kann&nbsp;Wake',
    'CanWhiteAbs'          => 'Kann absoluten Wei&szlig;-Abgleich',
    'CanWhiteBal'          => 'Kann Wei&szlig;-Abgleich',
    'CanWhiteCon'          => 'Kann kontinuierlichen Wei&szlig;-Abgleich',
    'CanWhite'             => 'Kann Wei&szlig;-Abgleich',
    'CanWhiteRel'          => 'Kann relativen Wei&szlig;-Abgleich',
    'CanZoomAbs'           => 'Kann absoluten Zoom',
    'CanZoomCon'           => 'Kann kontinuierlichen Zoom',
    'CanZoom'              => 'Kann&nbsp;Zoom',
    'CanZoomRel'           => 'Kann relativen Zoom',
    'CaptureHeight'        => 'Erfasse H&ouml;he',
    'CapturePalette'       => 'Erfasse Farbpalette',
    'CaptureWidth'         => 'Erfasse Breite',
    'Cause'                => 'Grund',
    'CheckMethod'          => 'Alarm-Pr&uuml;fmethode',
    'ChooseFilter'         => 'Filterauswahl',
    'ChoosePreset'         => 'Voreinstellung ausw&auml;hlen',
    'Close'                => 'Schlie&szlig;en',
    'Colour'               => 'Farbe',
    'Command'              => 'Kommando',
    'Config'               => 'Konfig.',
    'ConfiguredFor'        => 'Konfiguriert f&uuml;r',
    'ConfirmDeleteEvents'  => 'Sind Sie sicher, dass Sie die ausgew&auml;hlten Ereignisse l&ouml;schen wollen?',
    'ConfirmPassword'      => 'Passwortbest&auml;tigung',
    'ConjAnd'              => 'und',
    'ConjOr'               => 'oder',
    'Console'              => 'Konsole',
    'ContactAdmin'         => 'Bitte kontaktieren Sie den Administrator f&uuml;r weitere Details',
    'Continue'             => 'Weiter',
    'Contrast'             => 'Kontrast',
    'ControlAddress'       => 'Kontrolladresse',
    'ControlCap'           => 'Kontrollm&ouml;glichkeit',
    'ControlCaps'          => 'Kontrollm&ouml;glichkeiten',
    'ControlDevice'        => 'Kontrollger&auml;t',
    'Control'              => 'Kontrolle',
    'Controllable'         => 'Kontrollierbar',
    'ControlType'          => 'Kontrolltyp',
    'CycleWatch'           => 'Zeitzyklus',
    'Cycle'                => 'Zyklus',
    'Day'                  => 'Tag',
    'Debug'                => 'Debug',
    'DefaultRate'          => 'Standardrate',
    'DefaultScale'         => 'Standardskalierung',
    'DefaultView'          => 'Standardansicht',
    'DeleteAndNext'        => 'L&ouml;schen &amp; N&auml;chstes',
    'DeleteAndPrev'        => 'L&ouml;schen &amp; Vorheriges',
    'Delete'               => 'L&ouml;schen',
    'DeleteSavedFilter'    => 'L&ouml;sche gespeichertes Filter',
    'Description'          => 'Beschreibung',
    'DeviceChannel'        => 'Ger&auml;tekanal',
    'DeviceFormat'         => 'Ger&auml;teformat',
    'DeviceNumber'         => 'Ger&auml;tenummer',
    'DevicePath'           => 'Ger&auml;tepfad',
    'Devices'              => 'Ger&auml;te',
    'Dimensions'           => 'Abmessungen',
    'DisableAlarms'        => 'Alarme abschalten',
    'Disk'                 => 'Disk',
    'DonateAlready'        => 'Nein, ich habe schon gespendet',
    'DonateEnticement'     => 'Sie benutzen ZoneMinder nun schon eine Weile und es ist hoffentlich eine n&uuml;tzliche Applikation zur Verbesserung Ihrer Heim- oder Arbeitssicherheit. Obwohl ZoneMinder eine freie Open-Source-Software ist und bleiben wird, entstehen Kosten bei der Entwicklung und dem Support.<br><br>Falls Sie ZoneMinder für Weiterentwicklung in der Zukunft unterst&uuml;tzen m&ouml;chten, denken Sie bitte über eine Spende f&uuml;r das Projekt unter der Webadresse http://www.zoneminder.com/donate.html oder &uuml;ber nachfolgend stehende Option nach. Spenden sind, wie der Name schon sagt, immer freiwillig. Dem Projekt helfen kleine genauso wie gr&ouml;&szlig;ere Spenden sehr weiter und ein herzlicher Dank ist jedem Spender sicher.<br><br>Vielen Dank daf&uuml;r, dass sie ZoneMinder benutzen. Vergessen Sie nicht die Foren unter ZoneMinder.com, um Support zu erhalten und Ihre Erfahrung mit ZoneMinder zu verbessern!',
    'Donate'               => 'Bitte spenden Sie.',
    'DonateRemindDay'      => 'Noch nicht, erinnere mich in einem Tag noch mal.',
    'DonateRemindHour'     => 'Noch nicht, erinnere mich in einer Stunde noch mal.',
    'DonateRemindMonth'    => 'Noch nicht, erinnere mich in einem Monat noch mal.',
    'DonateRemindNever'    => 'Nein, ich m&ouml;chte nicht spenden, niemals erinnern.',
    'DonateRemindWeek'     => 'Noch nicht, erinnere mich in einer Woche noch mal.',
    'DonateYes'            => 'Ja, ich m&ouml;chte jetzt spenden.',
    'Download'             => 'Download',
    'Duration'             => 'Dauer',
    'Edit'                 => 'Bearbeiten',
    'Email'                => 'E-Mail',
    'EnableAlarms'         => 'Alarme aktivieren',
    'Enabled'              => 'Aktiviert',
    'EnterNewFilterName'   => 'Neuen Filternamen eingeben',
    'ErrorBrackets'        => 'Fehler. Bitte nur gleiche Anzahl offener und geschlossener Klammern.',
    'Error'                => 'Fehler',
    'ErrorValidValue'      => 'Fehler. Bitte alle Werte auf richtige Eingabe pr&uuml;fen',
    'Etc'                  => 'etc.',
    'Event'                => 'Ereignis',
    'EventFilter'          => 'Ereignisfilter',
    'EventId'              => 'Ereignis-ID',
    'EventName'            => 'Ereignisname',
    'EventPrefix'          => 'Ereignis-Pr&auml;fix',
    'Events'               => 'Ereignisse',
    'Exclude'              => 'Ausschluss;',
    'Execute'              => 'Ausf&uuml;hren',
    'ExportDetails'        => 'Exportiere Ereignis-Details',
    'Export'               => 'Exportieren',
    'ExportFailed'         => 'Exportieren fehlgeschlagen',
    'ExportFormat'         => 'Exportiere Dateiformat',
    'ExportFormatTar'      => 'TAR (Bandarchiv)',
    'ExportFormatZip'      => 'ZIP (Komprimiert)',
    'ExportFrames'         => 'Exportiere Bilddetails',
    'ExportImageFiles'     => 'Exportiere Bilddateien',
    'Exporting'            => 'Exportiere',
    'ExportMiscFiles'      => 'Exportiere andere Dateien (falls vorhanden)',
    'ExportOptions'        => 'Exportierungsoptionen',
    'ExportVideoFiles'     => 'Exportiere Videodateien (falls vorhanden)',
    'Far'                  => 'Weit',
    'FastForward'          => 'Schnell vorw&auml;rts',
    'Feed'                 => 'Eingabe',
    'FileColours'          => 'Dateifarben',
    'File'                 => 'Datei',
    'FilePath'             => 'Dateipfad',
    'FilterArchiveEvents'  => 'Archivierung aller Treffer',
    'FilterDeleteEvents'   => 'L&ouml;schen aller Treffer',
    'FilterEmailEvents'    => 'Detaillierte E-Mail zu allen Treffern',
    'FilterExecuteEvents'  => 'Ausf&uuml;hren bei allen Treffern',
    'FilterMessageEvents'  => 'Detaillierte Nachricht zu allen Treffern',
    'FilterPx'             => 'Filter-Pixel',
    'Filters'              => 'Filter',
    'FilterUnset'          => 'Sie m&uuml;ssen eine Breite und H&ouml;he f&uuml;r das Filter angeben',
    'FilterUploadEvents'   => 'Hochladen aller Treffer',
    'FilterVideoEvents'    => 'Video f&uuml;r alle Treffer erstellen',
    'First'                => 'Erstes',
    'FlippedHori'          => 'Horizontal gespiegelt',
    'FlippedVert'          => 'Vertikal gespiegelt',
    'Focus'                => 'Fokus',
    'ForceAlarm'           => 'Unbedingter Alarm',
    'Format'               => 'Format',
    'FPS'                  => 'fps',
    'FPSReportInterval'    => 'fps-Meldeintervall',
    'Frame'                => 'Bild',
    'FrameId'              => 'Bild-ID',
    'FrameRate'            => 'Abspielgeschwindigkeit',
    'Frames'               => 'Bilder',
    'FrameSkip'            => 'Bilder auslassen',
    'FTP'                  => 'FTP',
    'Func'                 => 'Fkt.',
    'Function'             => 'Funktion',
    'Gain'                 => 'Verst&auml;rkung',
    'General'              => 'Allgemeines',
    'GenerateVideo'        => 'Erzeuge Video',
    'GeneratingVideo'      => 'Erzeuge Video...',
    'GoToZoneMinder'       => 'Gehe zu ZoneMinder.com',
    'Grey'                 => 'Grau',
    'Group'                => 'Gruppe',
    'Groups'               => 'Gruppen',
    'HasFocusSpeed'        => 'Hat Fokus-Geschwindigkeit',
    'HasGainSpeed'         => 'Hat Verst&auml;kungs-Geschwindigkeit',
    'HasHomePreset'        => 'Hat Standardvoreinstellungen',
    'HasIrisSpeed'         => 'Hat Irisgeschwindigkeit',
    'HasPanSpeed'          => 'Hat Pan-Geschwindigkeit',
    'HasPresets'           => 'Hat Voreinstellungen',
    'HasTiltSpeed'         => 'Hat Neigungsgeschwindigkeit',
    'HasTurboPan'          => 'Hat Turbo-Pan',
    'HasTurboTilt'         => 'Hat Turbo-Neigung',
    'HasWhiteSpeed'        => 'Hat Wei&szlig;-Abgleichgeschwindigkeit',
    'HasZoomSpeed'         => 'Hat Zoom-Geschwindigkeit',
    'HighBW'               => 'Hohe&nbsp;B/W',
    'High'                 => 'hohe',
    'Home'                 => 'Home',
    'Hour'                 => 'Stunde',
    'Hue'                  => 'Farbton',
    'Id'                   => 'ID',
    'Idle'                 => 'Leerlauf',
    'Ignore'               => 'Ignoriere',
    'Image'                => 'Bild',
    'ImageBufferSize'      => 'Bildpuffergr&ouml;&szlig;e',
    'Images'               => 'Bilder',
    'Include'              => 'Einschluss',
    'In'                   => 'In',
    'Inverted'             => 'Invertiert',
    'Iris'                 => 'Iris',
    'KeyString'            => 'Schl&uuml;sselwort',
    'Label'                => 'Bezeichnung',
    'Language'             => 'Sprache',
    'Last'                 => 'Letztes',
    'LimitResultsPost'     => 'Ergebnisse;', // This is used at the end of the phrase 'Limit to first N results only'
    'LimitResultsPre'      => 'Begrenze nur auf die ersten', // This is used at the beginning of the phrase 'Limit to first N results only'
    'LinkedMonitors'       => 'Verbundene Monitore',
    'List'                 => 'Liste',
    'Load'                 => 'Last',
    'Local'                => 'Lokal',
    'LoggedInAs'           => 'Angemeldet als',
    'LoggingIn'            => 'Anmelden',
    'Login'                => 'Anmeldung',
    'Logout'               => 'Abmelden',
    'LowBW'                => 'Niedrige&nbsp;B/W',
    'Low'                  => 'niedrige',
    'Main'                 => 'Haupt',
    'Man'                  => 'Man',
    'Manual'               => 'Manual',
    'Mark'                 => 'Markieren',
    'MaxBandwidth'         => 'Maximale Bandbreite',
    'MaxBrScore'           => 'Maximale<br />Punktzahl',
    'MaxFocusRange'        => 'Maximaler Fokusbereich',
    'MaxFocusSpeed'        => 'Maximale Fokusgeschwindigkeit',
    'MaxFocusStep'         => 'Maximale Fokusstufe',
    'MaxGainRange'         => 'Maximaler Verst&auml;rkungsbereich',
    'MaxGainSpeed'         => 'Maximale Verst&auml;rkungsgeschwindigkeit',
    'MaxGainStep'          => 'Maximale Verst&auml;rkungsstufe',
    'MaximumFPS'           => 'Maximale FPS',
    'MaxIrisRange'         => 'Maximaler Irisbereich',
    'MaxIrisSpeed'         => 'Maximale Irisgeschwindigkeit',
    'MaxIrisStep'          => 'Maximale Irisstufe',
    'Max'                  => 'Max',
    'MaxPanRange'          => 'Maximaler Pan-Bereich',
    'MaxPanSpeed'          => 'Maximale Pan-Geschw.',
    'MaxPanStep'           => 'Maximale Pan-Stufe',
    'MaxTiltRange'         => 'Maximaler Neig.-Bereich',
    'MaxTiltSpeed'         => 'Maximale Neig.-Geschw.',
    'MaxTiltStep'          => 'Maximale Neig.-Stufe',
    'MaxWhiteRange'        => 'Maximaler Wei&szlig;-Abgl.bereich',
    'MaxWhiteSpeed'        => 'Maximale Wei&szlig;-Abgl.geschw.',
    'MaxWhiteStep'         => 'Maximale Wei&szlig;-Abgl.stufe',
    'MaxZoomRange'         => 'Maximaler Zoom-Bereich',
    'MaxZoomSpeed'         => 'Maximale Zoom-Geschw.',
    'MaxZoomStep'          => 'Maximale Zoom-Stufe',
    'MediumBW'             => 'Mittlere&nbsp;B/W',
    'Medium'               => 'mittlere',
    'MinAlarmAreaLtMax'    => 'Der minimale Alarmbereich sollte kleiner sein als der maximale',
    'MinAlarmAreaUnset'    => 'Sie m&uuml;ssen einen Minimumwert an Alarmfl&auml;chenpixeln angeben',
    'MinBlobAreaLtMax'     => 'Die minimale Blob-Fl&auml;che muss kleiner sein als die maximale',
    'MinBlobAreaUnset'     => 'Sie m&uuml;ssen einen Minimumwert an Blobfl&auml;chenpixeln angeben',
    'MinBlobLtMinFilter'   => 'Die minimale Blob-Fl&auml;che sollte kleiner oder gleich der minimalen Filterfl&auml;che sein',
    'MinBlobsLtMax'        => 'Die minimalen Blobs m&uuml;ssen kleiner sein als die maximalen',
    'MinBlobsUnset'        => 'Sie m&uuml;ssen einen Minimumwert an Blobs angeben',
    'MinFilterAreaLtMax'   => 'Die minimale Filterfl&auml;che sollte kleiner sein als die maximale',
    'MinFilterAreaUnset'   => 'Sie m&uuml;ssen einen Minimumwert an Filterpixeln angeben',
    'MinFilterLtMinAlarm'  => 'Die minimale Filterfl&auml;che sollte kleiner oder gleich der minimalen Alarmfl&auml;che sein',
    'MinFocusRange'        => 'Min. Fokusbereich',
    'MinFocusSpeed'        => 'Min. Fokusgeschw.',
    'MinFocusStep'         => 'Min. Fokusstufe',
    'MinGainRange'         => 'Min. Verst&auml;rkungsbereich',
    'MinGainSpeed'         => 'Min. Verst&auml;rkungsgeschwindigkeit',
    'MinGainStep'          => 'Min. Verst&auml;rkungsstufe',
    'MinIrisRange'         => 'Min. Irisbereich',
    'MinIrisSpeed'         => 'Min. Irisgeschwindigkeit',
    'MinIrisStep'          => 'Min. Irisstufe',
    'MinPanRange'          => 'Min. Pan-Bereich',
    'MinPanSpeed'          => 'Min. Pan-Geschwindigkeit',
    'MinPanStep'           => 'Min. Pan-Stufe',
    'MinPixelThresLtMax'   => 'Der minimale Pixelschwellwert muss kleiner sein als der maximale',
    'MinPixelThresUnset'   => 'Sie m&uuml;ssen einen minimalen Pixel-Schwellenwert angeben',
    'MinTiltRange'         => 'Min. Neigungsbereich',
    'MinTiltSpeed'         => 'Min. Neigungsgeschwindigkeit',
    'MinTiltStep'          => 'Min. Neigungsstufe',
    'MinWhiteRange'        => 'Min. Wei&szlig;-Abgleichbereich',
    'MinWhiteSpeed'        => 'Min. Wei&szlig;-Abgleichgeschwindigkeit',
    'MinWhiteStep'         => 'Min. Wei&szlig;-Abgleichstufe',
    'MinZoomRange'         => 'Min. Zoom-Bereich',
    'MinZoomSpeed'         => 'Min. Zoom-Geschwindigkeit',
    'MinZoomStep'          => 'Min. Zoom-Stufe',
    'Misc'                 => 'Verschiedenes',
    'MonitorIds'           => 'Monitor-ID',
    'Monitor'              => 'Monitor',
    'MonitorPresetIntro'   => 'W&auml;hlen Sie eine geeignete Voreinstellung aus der folgenden Liste.<br><br>Bitte beachten Sie, dass dies m&ouml;gliche Einstellungen von Ihnen am Monitor &uuml;berschreiben kann.<br><br>',
    'MonitorPreset'        => 'Monitor-Voreinstellung',
    'Monitors'             => 'Monitore',
    'Montage'              => 'Montage',
    'Month'                => 'Monat',
    'Move'                 => 'Bewegung',
    'MustBeGe'             => 'muss groesser oder gleich sein wie',
    'MustBeLe'             => 'muss kleiner oder gleich sein wie',
    'MustConfirmPassword'  => 'Sie m&uuml;ssen das Passwort best&auml;tigen.',
    'MustSupplyPassword'   => 'Sie m&uuml;ssen ein Passwort vergeben.',
    'MustSupplyUsername'   => 'Sie m&uuml;ssen einen Usernamen vergeben.',
    'Name'                 => 'Name',
    'Near'                 => 'Nah',
    'Network'              => 'Netzwerk',
    'NewGroup'             => 'Neue Gruppe',
    'NewLabel'             => 'Neuer Bezeichner',
    'New'                  => 'Neu',
    'NewPassword'          => 'Neues Passwort',
    'NewState'             => 'Neuer Status',
    'NewUser'              => 'Neuer Benutzer',
    'Next'                 => 'N&auml;chstes',
    'NoFramesRecorded'     => 'Es gibt keine Aufnahmen von diesem Ereignis.',
    'NoGroup'              => 'Keine Gruppe',
    'NoneAvailable'        => 'Nichts verf&uuml;gbar',
    'No'                   => 'Nein',
    'None'                 => 'ohne',
    'Normal'               => 'Normal',
    'NoSavedFilters'       => 'Keine gespeicherten Filter',
    'NoStatisticsRecorded' => 'Keine Statistik f&uuml;r dieses Ereignis/diese Bilder',
    'Notes'                => 'Bemerkungen',
    'NumPresets'           => 'Nummerierte Voreinstellungen',
    'Off'                  => 'Aus',
    'On'                   => 'An',
    'Open'                 => '&Ouml;ffnen',
    'OpEq'                 => 'gleich zu',
    'OpGtEq'               => 'groesser oder gleich wie',
    'OpGt'                 => 'groesser als',
    'OpIn'                 => 'in Satz',
    'OpLtEq'               => 'kleiner oder gleich wie',
    'OpLt'                 => 'kleiner als',
    'OpMatches'            => 'zutreffend',
    'OpNe'                 => 'nicht gleich',
    'OpNotIn'              => 'nicht im Satz',
    'OpNotMatches'         => 'nicht zutreffend',
    'OptionHelp'           => 'Hilfe',
    'OptionRestartWarning' => 'Ver&auml;nderungen werden erst nach einem Neustart des Programms aktiv.\nF&uuml;r eine sofortige &Auml;nderung starten Sie das Programm bitte neu.',
    'Options'              => 'Optionen',
    'Order'                => 'Reihenfolge',
    'OrEnterNewName'       => 'oder neuen Namen eingeben',
    'Orientation'          => 'Ausrichtung',
    'Out'                  => 'Aus',
    'OverwriteExisting'    => '&Uuml;berschreibe bestehende',
    'Paged'                => 'Seitennummeriert',
    'PanLeft'              => 'Pan-Left',
    'Pan'                  => 'Pan',
    'PanRight'             => 'Pan-Right',
    'PanTilt'              => 'Pan/Neigung',
    'Parameter'            => 'Parameter',
    'Password'             => 'Passwort',
    'PasswordsDifferent'   => 'Die Passw&ouml;rter sind unterschiedlich',
    'Paths'                => 'Pfade',
    'Pause'                => 'Pause',
    'PhoneBW'              => 'Tel.&nbsp;B/W',
    'Phone'                => 'Telefon',
    'PixelDiff'            => 'Pixel-Differenz',
    'Pixels'               => 'Pixel',
    'PlayAll'              => 'Alle zeigen',
    'Play'                 => 'Abspielen',
    'PleaseWait'           => 'Bitte warten',
    'Point'                => 'Punkt',
    'PostEventImageBuffer' => 'Nachereignispuffer',
    'PreEventImageBuffer'  => 'Vorereignispuffer',
    'PreserveAspect'       => 'Seitenverh&auml;ltnis beibehalten',
    'Presets'              => 'Voreinstellungen',
    'Preset'               => 'Voreinstellung',
    'Prev'                 => 'Vorheriges',
    'Protocol'             => 'Protokoll',
    'Rate'                 => 'Abspielgeschwindigkeit',
    'Real'                 => 'Real',
    'Record'               => 'Aufnahme',
    'RefImageBlendPct'     => 'Referenz-Bildblende',
    'Refresh'              => 'Aktualisieren',
    'Remote'               => 'Entfernt',
    'RemoteHostName'       => 'Entfernter Hostname',
    'RemoteHostPath'       => 'Entfernter Hostpfad',
    'RemoteHostPort'       => 'Entfernter Hostport',
    'RemoteImageColours'   => 'Entfernte Bildfarbe',
    'Rename'               => 'Umbenennen',
    'ReplayAll'            => 'Alle Ereignisse',
    'ReplayGapless'        => 'L&uuml;ckenlose Ereignisse',
    'Replay'               => 'Wiederholung',
    'ReplaySingle'         => 'Einzelereignis',
    'Replay'               => 'Wiederholung',
    'ResetEventCounts'     => 'L&ouml;sche Ereignispunktzahl',
    'Reset'                => 'Zur&uuml;cksetzen',
    'Restarting'           => 'Neustarten',
    'Restart'              => 'Neustart',
    'RestrictedCameraIds'  => 'Verbotene Kamera-ID',
    'RestrictedMonitors'   => 'Eingeschr&auml;nkte Monitore',
    'ReturnDelay'          => 'R&uuml;ckkehr-Verz&ouml;gerung',
    'ReturnLocation'       => 'R&uuml;ckkehrpunkt',
    'Rewind'               => 'Zur&uuml;ckspulen',
    'RotateLeft'           => 'Drehung links',
    'RotateRight'          => 'Drehung rechts',
    'RunMode'              => 'Betriebsmodus',
    'Running'              => 'In Betrieb',
    'RunState'             => 'Laufender Status',
    'SaveAs'               => 'Speichere als',
    'SaveFilter'           => 'Speichere Filter',
    'Save'                 => 'OK',
    'Scale'                => 'Skalierung',
    'Score'                => 'Punktzahl',
    'Secs'                 => 'Sekunden',
    'Sectionlength'        => 'Sektionsl&auml;nge',
    'SelectMonitors'       => 'W&auml;hle Monitore',
    'Select'               => 'Auswahl',
    'SelfIntersecting'     => 'Die Polygonr&auml;nder d&uuml;rfen sich nicht &uuml;berschneiden.',
    'SetNewBandwidth'      => 'Setze neue Bandbreite',
    'SetPreset'            => 'Setze Voreinstellung',
    'Set'                  => 'Setze',
    'Settings'             => 'Einstellungen',
    'ShowFilterWindow'     => 'Zeige Filterfenster',
    'ShowTimeline'         => 'Zeige Zeitlinie',
    'SignalCheckColour'    => 'Farbe des Signalchecks',
    'Size'                 => 'Gr&ouml;&szlig;e',
    'Sleep'                => 'Schlaf',
    'SortAsc'              => 'aufsteigend',
    'SortBy'               => 'Sortieren nach',
    'SortDesc'             => 'absteigend',
    'Source'               => 'Quelle',
    'SourceType'           => 'Quellentyp',
    'Speed'                => 'Geschwindigkeit',
    'SpeedHigh'            => 'Hohe Geschwindigkeit',
    'SpeedLow'             => 'Niedrige Geschwindigkeit',
    'SpeedMedium'          => 'Mittlere Geschwindigkeit',
    'SpeedTurbo'           => 'Turbo-Geschwindigkeit',
    'Start'                => 'Start',
    'State'                => 'Status',
    'Stats'                => 'Status',
    'Status'               => 'Status',
    'StepBack'             => 'Einen Schritt r&uuml;ckw&auml;rts',
    'StepForward'          => 'Einen Schritt vorw&auml;rts',
    'StepLarge'            => 'Gro&szlig;e Stufe',
    'StepMedium'           => 'Mittlere Stufe',
    'StepNone'             => 'Keine Stufe',
    'StepSmall'            => 'Kleine Stufe',
    'Step'                 => 'Stufe',
    'Stills'               => 'Bilder',
    'Stopped'              => 'Gestoppt',
    'Stop'                 => 'Stop',
    'StreamReplayBuffer'   => 'Stream-Wiedergabe-Bildpuffer',
    'Stream'               => 'Stream',
    'Submit'               => 'Absenden',
    'System'               => 'System',
    'Tele'                 => 'Tele',
    'Thumbnail'            => 'Miniatur',
    'Tilt'                 => 'Neigung',
    'TimeDelta'            => 'Zeitdifferenz',
    'Timeline'             => 'Zeitlinie',
    'TimestampLabelFormat' => 'Format des Zeitstempels',
    'TimestampLabelX'      => 'Zeitstempel-X',
    'TimestampLabelY'      => 'Zeitstempel-Y',
    'Timestamp'            => 'Zeitstempel',
    'TimeStamp'            => 'Zeitstempel',
    'Time'                 => 'Zeit',
    'Today'                => 'Heute',
    'Tools'                => 'Werkzeuge',
    'TotalBrScore'         => 'Totale<br/>Punktzahl',
    'TrackDelay'           => 'Nachf&uuml;hrungsverz&ouml;gerung',
    'TrackMotion'          => 'Bewegungs-Nachf&uuml;hrung',
    'Triggers'             => 'Ausl&ouml;ser',
    'TurboPanSpeed'        => 'Turbo-Pan-Geschwindigkeit',
    'TurboTiltSpeed'       => 'Turbo-Neigungsgeschwindigkeit',
    'Type'                 => 'Typ',
    'Unarchive'            => 'Aus Archiv entfernen',
    'Units'                => 'Einheiten',
    'Unknown'              => 'Unbekannt',
    'UpdateAvailable'      => 'Eine Aktualisierung f&uuml;r ZoneMinder ist verf&uuml;gbar.',
    'UpdateNotNecessary'   => 'Es ist keine Aktualisierung verf&uuml;gbar.',
    'Update'               => 'Aktualisieren',
    'UseFilter'            => 'Benutze Filter',
    'UseFilterExprsPost'   => '&nbsp;Filter&nbsp;Ausdr&uuml;cke', // This is used at the end of the phrase 'use N filter expressions'
    'UseFilterExprsPre'    => 'Benutze&nbsp;', // This is used at the beginning of the phrase 'use N filter expressions'
    'User'                 => 'Benutzer',
    'Username'             => 'Benutzername',
    'Users'                => 'Benutzer',
    'Value'                => 'Wert',
    'VersionIgnore'        => 'Ignoriere diese Version',
    'VersionRemindDay'     => 'Erinnere mich wieder in 1 Tag.',
    'VersionRemindHour'    => 'Erinnere mich wieder in 1 Stunde.',
    'VersionRemindNever'   => 'Informiere mich nicht mehr &uuml;ber neue Versionen.',
    'VersionRemindWeek'    => 'Erinnere mich wieder in 1 Woche.',
    'Version'              => 'Version',
    'VideoFormat'          => 'Videoformat',
    'VideoGenFailed'       => 'Videoerzeugung fehlgeschlagen!',
    'VideoGenFiles'        => 'Existierende Videodateien',
    'VideoGenNoFiles'      => 'Keine Videodateien gefunden.',
    'VideoGenParms'        => 'Parameter der Videoerzeugung',
    'VideoGenSucceeded'    => 'Videoerzeugung erfolgreich!',
    'VideoSize'            => 'Videogr&ouml;&szlig;e',
    'Video'                => 'Video',
    'ViewAll'              => 'Alles ansehen',
    'View'                 => 'Ansicht',
    'ViewEvent'            => 'Zeige Ereignis',
    'ViewPaged'            => 'Seitenansicht',
    'Wake'                 => 'Aufwachen',
    'WarmupFrames'         => 'Aufw&auml;rmbilder',
    'Watch'                => 'Beobachte',
    'WebColour'            => 'Webfarbe',
    'Web'                  => 'Web',
    'Week'                 => 'Woche',
    'WhiteBalance'         => 'Wei&szlig;-Abgleich',
    'White'                => 'Wei&szlig;',
    'Wide'                 => 'Weit',
    'X10ActivationString'  => 'X10-Aktivierungswert',
    'X10InputAlarmString'  => 'X10-Eingabe-Alarmwert',
    'X10OutputAlarmString' => 'X10-Ausgabe-Alarmwert',
    'X10'                  => 'X10',
    'X'                    => 'X',
    'Yes'                  => 'Ja',
    'YouNoPerms'           => 'Keine Erlaubnis zum Zugang dieser Resource.',
    'Y'                    => 'Y',
    'ZoneAlarmColour'      => 'Alarmfarbe (Rot/Gr&uuml;n/Blau)',
    'ZoneArea'             => 'Zone Area',
    'ZoneFilterSize'       => 'Filter-Breite/-H&ouml;he (Pixel)',
    'ZoneMinMaxAlarmArea'  => 'Min./max. Alarmfl&auml;che',
    'ZoneMinMaxBlobArea'   => 'Min./max. Blobfl&auml;che',
    'ZoneMinMaxBlobs'      => 'Min./max. Blobs',
    'ZoneMinMaxFiltArea'   => 'Min./max. Filterfl&auml;che',
    'ZoneMinMaxPixelThres' => 'Min./max. Pixelschwellwert',
    'ZoneOverloadFrames'   => 'Bildauslassrate bei System&uuml;berlastung',
    'Zones'                => 'Zonen',
    'Zone'                 => 'Zone',
    'ZoomIn'               => 'Hineinzoomen',
    'ZoomOut'              => 'Herauszoomen',
    'Zoom'                 => 'Zoom',
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
//    'LANG_DEFAULT' => array(
//        'Prompt' => "This is a new prompt for this option",
//        'Help' => "This is some new help for this option which will be displayed in the popup window when the ? is clicked"
//    ),
);

?>
