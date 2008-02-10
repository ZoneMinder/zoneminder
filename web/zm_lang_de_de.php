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
$zmSlang24BitColour          = '24-Bit-Farbe';
$zmSlang8BitGrey             = '8-Bit-Grau';
$zmSlangAction               = 'Aktion';
$zmSlangActual               = 'Original';
$zmSlangAddNewControl        = 'Neues Kontrollelement hinzuf&uuml;gen';
$zmSlangAddNewMonitor        = 'Neuer Monitor';
$zmSlangAddNewUser           = 'Neuer Benutzer';
$zmSlangAddNewZone           = 'Neue Zone';
$zmSlangAlarm                = 'Alarm';
$zmSlangAlarmBrFrames        = 'Alarm-<br />Bilder';
$zmSlangAlarmFrame           = 'Alarm-Bilder';
$zmSlangAlarmFrameCount      = 'Alarm-Bildanzahl';
$zmSlangAlarmLimits          = 'Alarm-Limits';
$zmSlangAlarmMaximumFPS      = 'Alarm-Maximum-FPS';
$zmSlangAlarmPx              = 'Alarm-Pixel';
$zmSlangAlarmRGBUnset        = 'Sie m&uuml;ssen eine RGB-Alarmfarbe setzen';
$zmSlangAlert                = 'Alarm';
$zmSlangAll                  = 'Alle';
$zmSlangApplyingStateChange  = 'Aktiviere neuen Status';
$zmSlangApply                = 'OK';
$zmSlangArchArchived         = 'Nur Archivierte';
$zmSlangArchive              = 'Archivieren';
$zmSlangArchived             = 'Archivierte';
$zmSlangArchUnarchived       = 'Nur Nichtarchivierte';
$zmSlangArea                 = 'Bereich';
$zmSlangAreaUnits            = 'Bereich (px/%)';
$zmSlangAttrAlarmFrames      = 'Alarmbilder';
$zmSlangAttrArchiveStatus    = 'Archivstatus';
$zmSlangAttrAvgScore         = 'Mittlere Punktzahl';
$zmSlangAttrCause            = 'Grund';
$zmSlangAttrDate             = 'Datum';
$zmSlangAttrDateTime         = 'Datum/Zeit';
$zmSlangAttrDiskBlocks       = 'Disk-Bloecke';
$zmSlangAttrDiskPercent      = 'Disk-Prozent';
$zmSlangAttrDuration         = 'Dauer';
$zmSlangAttrFrames           = 'Bilder';
$zmSlangAttrId               = 'ID';
$zmSlangAttrMaxScore         = 'Maximale Punktzahl';
$zmSlangAttrMonitorId        = 'Monitor-ID';
$zmSlangAttrMonitorName      = 'Monitorname';
$zmSlangAttrName             = 'Name';
$zmSlangAttrNotes            = 'Bemerkungen';
$zmSlangAttrSystemLoad       = 'Systemlast';
$zmSlangAttrTime             = 'Zeit';
$zmSlangAttrTotalScore       = 'Totale Punktzahl';
$zmSlangAttrWeekday          = 'Wochentag';
$zmSlangAuto                 = 'Auto';
$zmSlangAutoStopTimeout      = 'Auto-Stopp-Zeit&uuml;berschreitung';
$zmSlangAvgBrScore           = 'Mittlere<br/>Punktzahl';
$zmSlangBackground           = 'Hintergrund';
$zmSlangBackgroundFilter     = 'Filter im Hintergrund laufen lassen';
$zmSlangBadAlarmFrameCount   = 'Die Bildanzahl muss ganzzahlig 1 oder gr&ouml;&szlig;er sein';
$zmSlangBadAlarmMaxFPS       = 'Alarm-Maximum-FPS muss eine positive Ganzzahl oder eine Gleitkommazahl sein';
$zmSlangBadChannel           = 'Der Kanal muss ganzzahlig 0 oder gr&ouml;&szlig;er sein';
$zmSlangBadDevice            = 'Das Ger&auml;t muss eine g&uuml;ltige Systemresource sein';
$zmSlangBadFormat            = 'Das Format muss ganzzahlig 0 oder gr&ouml;&szlig;er sein';
$zmSlangBadFPSReportInterval = 'Der FPS-Intervall-Puffer-Z&auml;hler muss ganzzahlig 100 oder gr&ouml;&szlig;er sein';
$zmSlangBadFrameSkip         = 'Der Auslassz&auml;hler f&uuml;r Frames muss ganzzahlig 0 oder gr&ouml;&szlig;er sein';
$zmSlangBadHeight            = 'Die H&ouml;he muss auf einen g&uuml;ltigen Wert eingestellt sein';
$zmSlangBadHost              = 'Der Host muss auf eine g&uuml;ltige IP-Adresse oder einen Hostnamen (ohne http://) eingestellt sein';
$zmSlangBadImageBufferCount  = 'Die Gr&ouml;&szlig;e des Bildpuffers muss ganzzahlig 10 oder gr&ouml;&szlig;er sein';
$zmSlangBadLabelX            = 'Die x-Koordinate der Bezeichnung muss ganzzahlig 0 oder gr&ouml;&szlig;er sein';
$zmSlangBadLabelY            = 'Die y-Koordinate der Bezeichnung muss ganzzahlig 0 oder gr&ouml;&szlig;er sein';
$zmSlangBadMaxFPS            = 'Maximum-FPS muss eine positive Ganzzahl oder eine Gleitkommazahl sein';
$zmSlangBadNameChars         = 'Namen d&uuml;rfen nur aus Buchstaben, Zahlen und Trenn- oder Unterstrichen bestehen';
$zmSlangBadPath              = 'Der Pfad muss auf einen g&uuml;ltigen Wert eingestellt sein';
$zmSlangBadPort              = 'Der Port muss auf eine g&uuml;ltige Zahl eingestellt sein';
$zmSlangBadPostEventCount    = 'Der Z&auml;hler f&uuml;r die Ereignisfolgebilder muss ganzzahlig 0 oder gr&ouml;&szlig;er sein';
$zmSlangBadPreEventCount     = 'Der Z&auml;hler f&uuml;r die Ereignisvorlaufbilder muss mindestens ganzzahlig 0 und kleiner als die Bildpuffergr&ouml;&szlig;e sein';
$zmSlangBadRefBlendPerc      = 'Der Referenz-Blenden-Prozentwert muss ganzzahlig 0 oder gr&ouml;&szlig;er sein';
$zmSlangBadSectionLength     = 'Die Bereichsl&auml;nge muss ganzzahlig 0 oder gr&ouml;&szlig;er sein';
$zmSlangBadSignalCheckColour = 'Die Signalpr&uuml;ffarbe muss auf einen g&uuml;ltigen Farbwert eingestellt sein';
$zmSlangBadStreamReplayBuffer= 'Der Wiedergabestrompuffer tream replay buffer must be an integer of zero or more';
$zmSlangBadWarmupCount       = 'Die Anzahl der Vorwärmbilder muss ganzzahlig 0 oder gr&ouml;&szlig;er sein';
$zmSlangBadWebColour         = 'Die Webfarbe muss auf einen g&uuml;ltigen Farbwert eingestellt sein';
$zmSlangBadWidth             = 'Die Breite muss auf einen g&uuml;ltigen Wert eingestellt sein';
$zmSlangBandwidth            = 'Bandbreite';
$zmSlangBlobPx               = 'Blob-Pixel';
$zmSlangBlobs                = 'Blobs';
$zmSlangBlobSizes            = 'Blobgr&ouml;&szlig;e';
$zmSlangBrightness           = 'Helligkeit';
$zmSlangBuffers              = 'Puffer';
$zmSlangCanAutoFocus         = 'Kann Autofokus';
$zmSlangCanAutoGain          = 'Kann Auto-Verst&auml;rkung';
$zmSlangCanAutoIris          = 'Kann Auto-Iris';
$zmSlangCanAutoWhite         = 'Kann Auto-Wei&szlig;-Abgleich';
$zmSlangCanAutoZoom          = 'Kann Auto-Zoom';
$zmSlangCancel               = 'Abbruch';
$zmSlangCancelForcedAlarm    = 'Abbruch des unbedingten Alarms';
$zmSlangCanFocusAbs          = 'Kann absoluten Fokus';
$zmSlangCanFocusCon          = 'Kann kontinuierlichen Fokus';
$zmSlangCanFocus             = 'Kann&nbsp;Fokus';
$zmSlangCanFocusRel          = 'Kann relativen Fokus';
$zmSlangCanGainAbs           = 'Kann absolute Verst&auml;rkung';
$zmSlangCanGainCon           = 'Kann kontinuierliche Verst&auml;rkung';
$zmSlangCanGain              = 'Kann Verst&auml;rkung';
$zmSlangCanGainRel           = 'Kann relative Verst&auml;kung';
$zmSlangCanIrisAbs           = 'Kann absolute Iris';
$zmSlangCanIrisCon           = 'Kann kontinuierliche Iris';
$zmSlangCanIris              = 'Kann&nbsp;Iris';
$zmSlangCanIrisRel           = 'Kann relative Iris';
$zmSlangCanMoveAbs           = 'Kann absolute Bewegung';
$zmSlangCanMoveCon           = 'Kann kontinuierliche Bewegung';
$zmSlangCanMoveDiag          = 'Kann diagonale Bewegung';
$zmSlangCanMove              = 'Kann&nbsp;Bewegung';
$zmSlangCanMoveMap           = 'Kann Mapped-Bewegung';
$zmSlangCanMoveRel           = 'Kann relative Bewegung';
$zmSlangCanPan               = 'Kann&nbsp;Pan' ;
$zmSlangCanReset             = 'Kann&nbsp;Reset';
$zmSlangCanSetPresets        = 'Kann Voreinstellungen setzen';
$zmSlangCanSleep             = 'Kann&nbsp;Sleep';
$zmSlangCanTilt              = 'Kann&nbsp;Neigung';
$zmSlangCanWake              = 'Kann&nbsp;Wake';
$zmSlangCanWhiteAbs          = 'Kann absoluten Wei&szlig;-Abgleich';
$zmSlangCanWhiteBal          = 'Kann Wei&szlig;-Abgleich';
$zmSlangCanWhiteCon          = 'Kann kontinuierlichen Wei&szlig;-Abgleich';
$zmSlangCanWhite             = 'Kann Wei&szlig;-Abgleich';
$zmSlangCanWhiteRel          = 'Kann relativen Wei&szlig;-Abgleich';
$zmSlangCanZoomAbs           = 'Kann absoluten Zoom';
$zmSlangCanZoomCon           = 'Kann kontinuierlichen Zoom';
$zmSlangCanZoom              = 'Kann&nbsp;Zoom';
$zmSlangCanZoomRel           = 'Kann relativen Zoom';
$zmSlangCaptureHeight        = 'Erfasse H&ouml;he';
$zmSlangCapturePalette       = 'Erfasse Farbpalette';
$zmSlangCaptureWidth         = 'Erfasse Breite';
$zmSlangCause                = 'Grund';
$zmSlangCheckMethod          = 'Alarm-Pr&uuml;fmethode';
$zmSlangChooseFilter         = 'Filterauswahl';
$zmSlangChoosePreset         = 'Voreinstellung ausw&auml;hlen';
$zmSlangClose                = 'Schlie&szlig;en';
$zmSlangColour               = 'Farbe';
$zmSlangCommand              = 'Kommando';
$zmSlangConfig               = 'Konfig.';
$zmSlangConfiguredFor        = 'Konfiguriert f&uuml;r';
$zmSlangConfirmDeleteEvents  = 'Sind Sie sicher, dass Sie die ausgew&auml;hlten Ereignisse l&ouml;schen wollen?';
$zmSlangConfirmPassword      = 'Passwortbest&auml;tigung';
$zmSlangConjAnd              = 'und';
$zmSlangConjOr               = 'oder';
$zmSlangConsole              = 'Konsole';
$zmSlangContactAdmin         = 'Bitte kontaktieren Sie den Administrator f&uuml;r weitere Details';
$zmSlangContinue             = 'Weiter';
$zmSlangContrast             = 'Kontrast';
$zmSlangControlAddress       = 'Kontrolladresse';
$zmSlangControlCap           = 'Kontrollm&ouml;glichkeit';
$zmSlangControlCaps          = 'Kontrollm&ouml;glichkeiten';
$zmSlangControlDevice        = 'Kontrollger&auml;t';
$zmSlangControl              = 'Kontrolle';
$zmSlangControllable         = 'Kontrollierbar';
$zmSlangControlType          = 'Kontrolltyp';
$zmSlangCycleWatch           = 'Zeitzyklus';
$zmSlangCycle                = 'Zyklus';
$zmSlangDay                  = 'Tag';
$zmSlangDebug                = 'Debug';
$zmSlangDefaultRate          = 'Standardrate';
$zmSlangDefaultScale         = 'Standardskalierung';
$zmSlangDefaultView          = 'Standardansicht';
$zmSlangDeleteAndNext        = 'L&ouml;schen &amp; N&auml;chstes';
$zmSlangDeleteAndPrev        = 'L&ouml;schen &amp; Vorheriges';
$zmSlangDelete               = 'L&ouml;schen';
$zmSlangDeleteSavedFilter    = 'L&ouml;sche gespeichertes Filter';
$zmSlangDescription          = 'Beschreibung';
$zmSlangDeviceChannel        = 'Ger&auml;tekanal';
$zmSlangDeviceFormat         = 'Ger&auml;teformat';
$zmSlangDeviceNumber         = 'Ger&auml;tenummer';
$zmSlangDevicePath           = 'Ger&auml;tepfad';
$zmSlangDevices              = 'Ger&auml;te';
$zmSlangDimensions           = 'Abmessungen';
$zmSlangDisableAlarms        = 'Alarme abschalten';
$zmSlangDisk                 = 'Disk';
$zmSlangDonateAlready        = 'Nein, ich habe schon gespendet';
$zmSlangDonateEnticement     = 'Sie benutzen ZoneMinder nun schon eine Weile und es ist hoffentlich eine n&uuml;tzliche Applikation zur Verbesserung Ihrer Heim- oder Arbeitssicherheit. Obwohl ZoneMinder eine freie Open-Source-Software ist und bleiben wird, entstehen Kosten bei der Entwicklung und dem Support.<br><br>Falls Sie ZoneMinder für Weiterentwicklung in der Zukunft unterst&uuml;tzen m&ouml;chten, denken Sie bitte über eine Spende f&uuml;r das Projekt unter der Webadresse http://www.zoneminder.com/donate.html oder &uuml;ber nachfolgend stehende Option nach. Spenden sind, wie der Name schon sagt, immer freiwillig. Dem Projekt helfen kleine genauso wie gr&ouml;&szlig;ere Spenden sehr weiter und ein herzlicher Dank ist jedem Spender sicher.<br><br>Vielen Dank daf&uuml;r, dass sie ZoneMinder benutzen. Vergessen Sie nicht die Foren unter ZoneMinder.com, um Support zu erhalten und Ihre Erfahrung mit ZoneMinder zu verbessern!';
$zmSlangDonate               = 'Bitte spenden Sie.';
$zmSlangDonateRemindDay      = 'Noch nicht, erinnere mich in einem Tag noch mal.';
$zmSlangDonateRemindHour     = 'Noch nicht, erinnere mich in einer Stunde noch mal.';
$zmSlangDonateRemindMonth    = 'Noch nicht, erinnere mich in einem Monat noch mal.';
$zmSlangDonateRemindNever    = 'Nein, ich m&ouml;chte nicht spenden, niemals erinnern.';
$zmSlangDonateRemindWeek     = 'Noch nicht, erinnere mich in einer Woche noch mal.';
$zmSlangDonateYes            = 'Ja, ich m&ouml;chte jetzt spenden.';
$zmSlangDownload             = 'Download';
$zmSlangDuration             = 'Dauer';
$zmSlangEdit                 = 'Bearbeiten';
$zmSlangEmail                = 'E-Mail';
$zmSlangEnableAlarms         = 'Alarme aktivieren';
$zmSlangEnabled              = 'Aktiviert';
$zmSlangEnterNewFilterName   = 'Neuen Filternamen eingeben';
$zmSlangErrorBrackets        = 'Fehler. Bitte nur gleiche Anzahl offener und geschlossener Klammern.';
$zmSlangError                = 'Fehler';
$zmSlangErrorValidValue      = 'Fehler. Bitte alle Werte auf richtige Eingabe pr&uuml;fen';
$zmSlangEtc                  = 'etc.';
$zmSlangEvent                = 'Ereignis';
$zmSlangEventFilter          = 'Ereignisfilter';
$zmSlangEventId              = 'Ereignis-ID';
$zmSlangEventName            = 'Ereignisname';
$zmSlangEventPrefix          = 'Ereignis-Pr&auml;fix';
$zmSlangEvents               = 'Ereignisse';
$zmSlangExclude              = 'Ausschluss;';
$zmSlangExecute              = 'Ausf&uuml;hren';
$zmSlangExportDetails        = 'Exportiere Ereignis-Details';
$zmSlangExport               = 'Exportieren';
$zmSlangExportFailed         = 'Exportieren fehlgeschlagen';
$zmSlangExportFormat         = 'Exportiere Dateiformat';
$zmSlangExportFormatTar      = 'TAR (Bandarchiv)';
$zmSlangExportFormatZip      = 'ZIP (Komprimiert)';
$zmSlangExportFrames         = 'Exportiere Bilddetails';
$zmSlangExportImageFiles     = 'Exportiere Bilddateien';
$zmSlangExporting            = 'Exportiere';
$zmSlangExportMiscFiles      = 'Exportiere andere Dateien (falls vorhanden)';
$zmSlangExportOptions        = 'Exportierungsoptionen';
$zmSlangExportVideoFiles     = 'Exportiere Videodateien (falls vorhanden)';
$zmSlangFar                  = 'Weit';
$zmSlangFastForward          = 'Schnell vorw&auml;rts';
$zmSlangFeed                 = 'Eingabe';
$zmSlangFileColours          = 'Dateifarben';
$zmSlangFile                 = 'Datei';
$zmSlangFilePath             = 'Dateipfad';
$zmSlangFilterArchiveEvents  = 'Archivierung aller Treffer';
$zmSlangFilterDeleteEvents   = 'L&ouml;schen aller Treffer';
$zmSlangFilterEmailEvents    = 'Detaillierte E-Mail zu allen Treffern';
$zmSlangFilterExecuteEvents  = 'Ausf&uuml;hren bei allen Treffern';
$zmSlangFilterMessageEvents  = 'Detaillierte Nachricht zu allen Treffern';
$zmSlangFilterPx             = 'Filter-Pixel';
$zmSlangFilters              = 'Filter';
$zmSlangFilterUnset          = 'Sie m&uuml;ssen eine Breite und H&ouml;he f&uuml;r das Filter angeben';
$zmSlangFilterUploadEvents   = 'Hochladen aller Treffer';
$zmSlangFilterVideoEvents    = 'Video f&uuml;r alle Treffer erstellen';
$zmSlangFirst                = 'Erstes';
$zmSlangFlippedHori          = 'Horizontal gespiegelt';
$zmSlangFlippedVert          = 'Vertikal gespiegelt';
$zmSlangFocus                = 'Fokus';
$zmSlangForceAlarm           = 'Unbedingter Alarm';
$zmSlangFormat               = 'Format';
$zmSlangFPS                  = 'fps';
$zmSlangFPSReportInterval    = 'fps-Meldeintervall';
$zmSlangFrame                = 'Bild';
$zmSlangFrameId              = 'Bild-ID';
$zmSlangFrameRate            = 'Abspielgeschwindigkeit';
$zmSlangFrames               = 'Bilder';
$zmSlangFrameSkip            = 'Bilder auslassen';
$zmSlangFTP                  = 'FTP';
$zmSlangFunc                 = 'Fkt.';
$zmSlangFunction             = 'Funktion';
$zmSlangGain                 = 'Verst&auml;rkung';
$zmSlangGeneral              = 'Allgemeines';
$zmSlangGenerateVideo        = 'Erzeuge Video';
$zmSlangGeneratingVideo      = 'Erzeuge Video...';
$zmSlangGoToZoneMinder       = 'Gehe zu ZoneMinder.com';
$zmSlangGrey                 = 'Grau';
$zmSlangGroup                = 'Gruppe';
$zmSlangGroups               = 'Gruppen';
$zmSlangHasFocusSpeed        = 'Hat Fokus-Geschwindigkeit';
$zmSlangHasGainSpeed         = 'Hat Verst&auml;kungs-Geschwindigkeit';
$zmSlangHasHomePreset        = 'Hat Standardvoreinstellungen';
$zmSlangHasIrisSpeed         = 'Hat Irisgeschwindigkeit';
$zmSlangHasPanSpeed          = 'Hat Pan-Geschwindigkeit';
$zmSlangHasPresets           = 'Hat Voreinstellungen';
$zmSlangHasTiltSpeed         = 'Hat Neigungsgeschwindigkeit';
$zmSlangHasTurboPan          = 'Hat Turbo-Pan';
$zmSlangHasTurboTilt         = 'Hat Turbo-Neigung';
$zmSlangHasWhiteSpeed        = 'Hat Wei&szlig;-Abgleichgeschwindigkeit';
$zmSlangHasZoomSpeed         = 'Hat Zoom-Geschwindigkeit';
$zmSlangHighBW               = 'Hohe&nbsp;B/W';
$zmSlangHigh                 = 'hohe';
$zmSlangHome                 = 'Home';
$zmSlangHour                 = 'Stunde';
$zmSlangHue                  = 'Farbton';
$zmSlangId                   = 'ID';
$zmSlangIdle                 = 'Leerlauf';
$zmSlangIgnore               = 'Ignoriere';
$zmSlangImage                = 'Bild';
$zmSlangImageBufferSize      = 'Bildpuffergr&ouml;&szlig;e';
$zmSlangImages               = 'Bilder';
$zmSlangInclude              = 'Einschluss';
$zmSlangIn                   = 'In';
$zmSlangInverted             = 'Invertiert';
$zmSlangIris                 = 'Iris';
$zmSlangKeyString            = 'Schl&uuml;sselwort';
$zmSlangLabel                = 'Bezeichnung';
$zmSlangLanguage             = 'Sprache';
$zmSlangLast                 = 'Letztes';
$zmSlangLimitResultsPost     = 'Ergebnisse;'; // This is used at the end of the phrase 'Limit to first N results only'
$zmSlangLimitResultsPre      = 'Begrenze nur auf die ersten'; // This is used at the beginning of the phrase 'Limit to first N results only'
$zmSlangLinkedMonitors       = 'Verbundene Monitore';
$zmSlangList                 = 'Liste';
$zmSlangLoad                 = 'Last';
$zmSlangLocal                = 'Lokal';
$zmSlangLoggedInAs           = 'Angemeldet als';
$zmSlangLoggingIn            = 'Anmelden';
$zmSlangLogin                = 'Anmeldung';
$zmSlangLogout               = 'Abmelden';
$zmSlangLowBW                = 'Niedrige&nbsp;B/W';
$zmSlangLow                  = 'niedrige';
$zmSlangMain                 = 'Haupt';
$zmSlangMan                  = 'Man';
$zmSlangManual               = 'Manual';
$zmSlangMark                 = 'Markieren';
$zmSlangMaxBandwidth         = 'Maximale Bandbreite';
$zmSlangMaxBrScore           = 'Maximale<br />Punktzahl';
$zmSlangMaxFocusRange        = 'Maximaler Fokusbereich';
$zmSlangMaxFocusSpeed        = 'Maximale Fokusgeschwindigkeit';
$zmSlangMaxFocusStep         = 'Maximale Fokusstufe';
$zmSlangMaxGainRange         = 'Maximaler Verst&auml;rkungsbereich';
$zmSlangMaxGainSpeed         = 'Maximale Verst&auml;rkungsgeschwindigkeit';
$zmSlangMaxGainStep          = 'Maximale Verst&auml;rkungsstufe';
$zmSlangMaximumFPS           = 'Maximale FPS';
$zmSlangMaxIrisRange         = 'Maximaler Irisbereich';
$zmSlangMaxIrisSpeed         = 'Maximale Irisgeschwindigkeit';
$zmSlangMaxIrisStep          = 'Maximale Irisstufe';
$zmSlangMax                  = 'Max';
$zmSlangMaxPanRange          = 'Maximaler Pan-Bereich';
$zmSlangMaxPanSpeed          = 'Maximale Pan-Geschw.';
$zmSlangMaxPanStep           = 'Maximale Pan-Stufe';
$zmSlangMaxTiltRange         = 'Maximaler Neig.-Bereich';
$zmSlangMaxTiltSpeed         = 'Maximale Neig.-Geschw.';
$zmSlangMaxTiltStep          = 'Maximale Neig.-Stufe';
$zmSlangMaxWhiteRange        = 'Maximaler Wei&szlig;-Abgl.bereich';
$zmSlangMaxWhiteSpeed        = 'Maximale Wei&szlig;-Abgl.geschw.';
$zmSlangMaxWhiteStep         = 'Maximale Wei&szlig;-Abgl.stufe';
$zmSlangMaxZoomRange         = 'Maximaler Zoom-Bereich';
$zmSlangMaxZoomSpeed         = 'Maximale Zoom-Geschw.';
$zmSlangMaxZoomStep          = 'Maximale Zoom-Stufe';
$zmSlangMediumBW             = 'Mittlere&nbsp;B/W';
$zmSlangMedium               = 'mittlere';
$zmSlangMinAlarmAreaLtMax    = 'Der minimale Alarmbereich sollte kleiner sein als der maximale';
$zmSlangMinAlarmAreaUnset    = 'Sie m&uuml;ssen einen Minimumwert an Alarmfl&auml;chenpixeln angeben';
$zmSlangMinBlobAreaLtMax     = 'Die minimale Blob-Fl&auml;che muss kleiner sein als die maximale';
$zmSlangMinBlobAreaUnset     = 'Sie m&uuml;ssen einen Minimumwert an Blobfl&auml;chenpixeln angeben';
$zmSlangMinBlobLtMinFilter   = 'Die minimale Blob-Fl&auml;che sollte kleiner oder gleich der minimalen Filterfl&auml;che sein';
$zmSlangMinBlobsLtMax        = 'Die minimalen Blobs m&uuml;ssen kleiner sein als die maximalen';
$zmSlangMinBlobsUnset        = 'Sie m&uuml;ssen einen Minimumwert an Blobs angeben';
$zmSlangMinFilterAreaLtMax   = 'Die minimale Filterfl&auml;che sollte kleiner sein als die maximale';
$zmSlangMinFilterAreaUnset   = 'Sie m&uuml;ssen einen Minimumwert an Filterpixeln angeben';
$zmSlangMinFilterLtMinAlarm  = 'Die minimale Filterfl&auml;che sollte kleiner oder gleich der minimalen Alarmfl&auml;che sein';
$zmSlangMinFocusRange        = 'Min. Fokusbereich';
$zmSlangMinFocusSpeed        = 'Min. Fokusgeschw.';
$zmSlangMinFocusStep         = 'Min. Fokusstufe';
$zmSlangMinGainRange         = 'Min. Verst&auml;rkungsbereich';
$zmSlangMinGainSpeed         = 'Min. Verst&auml;rkungsgeschwindigkeit';
$zmSlangMinGainStep          = 'Min. Verst&auml;rkungsstufe';
$zmSlangMinIrisRange         = 'Min. Irisbereich';
$zmSlangMinIrisSpeed         = 'Min. Irisgeschwindigkeit';
$zmSlangMinIrisStep          = 'Min. Irisstufe';
$zmSlangMinPanRange          = 'Min. Pan-Bereich';
$zmSlangMinPanSpeed          = 'Min. Pan-Geschwindigkeit';
$zmSlangMinPanStep           = 'Min. Pan-Stufe';
$zmSlangMinPixelThresLtMax   = 'Der minimale Pixelschwellwert muss kleiner sein als der maximale';
$zmSlangMinPixelThresUnset   = 'Sie m&uuml;ssen einen minimalen Pixel-Schwellenwert angeben';
$zmSlangMinTiltRange         = 'Min. Neigungsbereich';
$zmSlangMinTiltSpeed         = 'Min. Neigungsgeschwindigkeit';
$zmSlangMinTiltStep          = 'Min. Neigungsstufe';
$zmSlangMinWhiteRange        = 'Min. Wei&szlig;-Abgleichbereich';
$zmSlangMinWhiteSpeed        = 'Min. Wei&szlig;-Abgleichgeschwindigkeit';
$zmSlangMinWhiteStep         = 'Min. Wei&szlig;-Abgleichstufe';
$zmSlangMinZoomRange         = 'Min. Zoom-Bereich';
$zmSlangMinZoomSpeed         = 'Min. Zoom-Geschwindigkeit';
$zmSlangMinZoomStep          = 'Min. Zoom-Stufe';
$zmSlangMisc                 = 'Verschiedenes';
$zmSlangMonitorIds           = 'Monitor-ID';
$zmSlangMonitor              = 'Monitor';
$zmSlangMonitorPresetIntro   = 'W&auml;hlen Sie eine geeignete Voreinstellung aus der folgenden Liste.<br><br>Bitte beachten Sie, dass dies m&ouml;gliche Einstellungen von Ihnen am Monitor &uuml;berschreiben kann.<br><br>';
$zmSlangMonitorPreset        = 'Monitor-Voreinstellung';
$zmSlangMonitors             = 'Monitore';
$zmSlangMontage              = 'Montage';
$zmSlangMonth                = 'Monat';
$zmSlangMove                 = 'Bewegung';
$zmSlangMustBeGe             = 'muss groesser oder gleich sein wie';
$zmSlangMustBeLe             = 'muss kleiner oder gleich sein wie';
$zmSlangMustConfirmPassword  = 'Sie m&uuml;ssen das Passwort best&auml;tigen.';
$zmSlangMustSupplyPassword   = 'Sie m&uuml;ssen ein Passwort vergeben.';
$zmSlangMustSupplyUsername   = 'Sie m&uuml;ssen einen Usernamen vergeben.';
$zmSlangName                 = 'Name';
$zmSlangNear                 = 'Nah';
$zmSlangNetwork              = 'Netzwerk';
$zmSlangNewGroup             = 'Neue Gruppe';
$zmSlangNewLabel             = 'Neuer Bezeichner';
$zmSlangNew                  = 'Neu';
$zmSlangNewPassword          = 'Neues Passwort';
$zmSlangNewState             = 'Neuer Status';
$zmSlangNewUser              = 'Neuer Benutzer';
$zmSlangNext                 = 'N&auml;chstes';
$zmSlangNoFramesRecorded     = 'Es gibt keine Aufnahmen von diesem Ereignis.';
$zmSlangNoGroup              = 'Keine Gruppe';
$zmSlangNoneAvailable        = 'Nichts verf&uuml;gbar';
$zmSlangNo                   = 'Nein';
$zmSlangNone                 = 'ohne';
$zmSlangNormal               = 'Normal';
$zmSlangNoSavedFilters       = 'Keine gespeicherten Filter';
$zmSlangNoStatisticsRecorded = 'Keine Statistik f&uuml;r dieses Ereignis/diese Bilder';
$zmSlangNotes                = 'Bemerkungen';
$zmSlangNumPresets           = 'Nummerierte Voreinstellungen';
$zmSlangOff                  = 'Aus';
$zmSlangOn                   = 'An';
$zmSlangOpen                 = '&Ouml;ffnen';
$zmSlangOpEq                 = 'gleich zu';
$zmSlangOpGtEq               = 'groesser oder gleich wie';
$zmSlangOpGt                 = 'groesser als';
$zmSlangOpIn                 = 'in Satz';
$zmSlangOpLtEq               = 'kleiner oder gleich wie';
$zmSlangOpLt                 = 'kleiner als';
$zmSlangOpMatches            = 'zutreffend';
$zmSlangOpNe                 = 'nicht gleich';
$zmSlangOpNotIn              = 'nicht im Satz';
$zmSlangOpNotMatches         = 'nicht zutreffend';
$zmSlangOptionHelp           = 'Hilfe';
$zmSlangOptionRestartWarning = 'Ver&auml;nderungen werden erst nach einem Neustart des Programms aktiv.\nF&uuml;r eine sofortige &Auml;nderung starten Sie das Programm bitte neu.';
$zmSlangOptions              = 'Optionen';
$zmSlangOrder                = 'Reihenfolge';
$zmSlangOrEnterNewName       = 'oder neuen Namen eingeben';
$zmSlangOrientation          = 'Ausrichtung';
$zmSlangOut                  = 'Aus';
$zmSlangOverwriteExisting    = '&Uuml;berschreibe bestehende';
$zmSlangPaged                = 'Seitennummeriert';
$zmSlangPanLeft              = 'Pan-Left';
$zmSlangPan                  = 'Pan';
$zmSlangPanRight             = 'Pan-Right';
$zmSlangPanTilt              = 'Pan/Neigung';
$zmSlangParameter            = 'Parameter';
$zmSlangPassword             = 'Passwort';
$zmSlangPasswordsDifferent   = 'Die Passw&ouml;rter sind unterschiedlich';
$zmSlangPaths                = 'Pfade';
$zmSlangPause                = 'Pause';
$zmSlangPhoneBW              = 'Tel.&nbsp;B/W';
$zmSlangPhone                = 'Telefon';
$zmSlangPixelDiff            = 'Pixel-Differenz';
$zmSlangPixels               = 'Pixel';
$zmSlangPlayAll              = 'Alle zeigen';
$zmSlangPlay                 = 'Abspielen';
$zmSlangPleaseWait           = 'Bitte warten';
$zmSlangPoint                = 'Punkt';
$zmSlangPostEventImageBuffer = 'Nachereignispuffer';
$zmSlangPreEventImageBuffer  = 'Vorereignispuffer';
$zmSlangPreserveAspect       = 'Seitenverh&auml;ltnis beibehalten';
$zmSlangPresets              = 'Voreinstellungen';
$zmSlangPreset               = 'Voreinstellung';
$zmSlangPrev                 = 'Vorheriges';
$zmSlangProtocol             = 'Protokoll';
$zmSlangRate                 = 'Abspielgeschwindigkeit';
$zmSlangReal                 = 'Real';
$zmSlangRecord               = 'Aufnahme';
$zmSlangRefImageBlendPct     = 'Referenz-Bildblende';
$zmSlangRefresh              = 'Aktualisieren';
$zmSlangRemote               = 'Entfernt';
$zmSlangRemoteHostName       = 'Entfernter Hostname';
$zmSlangRemoteHostPath       = 'Entfernter Hostpfad';
$zmSlangRemoteHostPort       = 'Entfernter Hostport';
$zmSlangRemoteImageColours   = 'Entfernte Bildfarbe';
$zmSlangRename               = 'Umbenennen';
$zmSlangReplayAll            = 'Alle Ereignisse';
$zmSlangReplayGapless        = 'L&uuml;ckenlose Ereignisse';
$zmSlangReplay               = 'Wiederholung';
$zmSlangReplaySingle         = 'Einzelereignis';
$zmSlangReplay               = 'Wiederholung';
$zmSlangResetEventCounts     = 'L&ouml;sche Ereignispunktzahl';
$zmSlangReset                = 'Zur&uuml;cksetzen';
$zmSlangRestarting           = 'Neustarten';
$zmSlangRestart              = 'Neustart';
$zmSlangRestrictedCameraIds  = 'Verbotene Kamera-ID';
$zmSlangRestrictedMonitors   = 'Eingeschr&auml;nkte Monitore';
$zmSlangReturnDelay          = 'R&uuml;ckkehr-Verz&ouml;gerung';
$zmSlangReturnLocation       = 'R&uuml;ckkehrpunkt';
$zmSlangRewind               = 'Zur&uuml;ckspulen';
$zmSlangRotateLeft           = 'Drehung links';
$zmSlangRotateRight          = 'Drehung rechts';
$zmSlangRunMode              = 'Betriebsmodus';
$zmSlangRunning              = 'In Betrieb';
$zmSlangRunState             = 'Laufender Status';
$zmSlangSaveAs               = 'Speichere als';
$zmSlangSaveFilter           = 'Speichere Filter';
$zmSlangSave                 = 'OK';
$zmSlangScale                = 'Skalierung';
$zmSlangScore                = 'Punktzahl';
$zmSlangSecs                 = 'Sekunden';
$zmSlangSectionlength        = 'Sektionsl&auml;nge';
$zmSlangSelectMonitors       = 'W&auml;hle Monitore';
$zmSlangSelect               = 'Auswahl';
$zmSlangSelfIntersecting     = 'Die Polygonr&auml;nder d&uuml;rfen sich nicht &uuml;berschneiden.';
$zmSlangSetLearnPrefs        = 'Setze Lernmerkmale'; // This can be ignored for now
$zmSlangSetNewBandwidth      = 'Setze neue Bandbreite';
$zmSlangSetPreset            = 'Setze Voreinstellung';
$zmSlangSet                  = 'Setze';
$zmSlangSettings             = 'Einstellungen';
$zmSlangShowFilterWindow     = 'Zeige Filterfenster';
$zmSlangShowTimeline         = 'Zeige Zeitlinie';
$zmSlangSignalCheckColour    = 'Farbe des Signalchecks';
$zmSlangSize                 = 'Gr&ouml;&szlig;e';
$zmSlangSleep                = 'Schlaf';
$zmSlangSortAsc              = 'aufsteigend';
$zmSlangSortBy               = 'Sortieren nach';
$zmSlangSortDesc             = 'absteigend';
$zmSlangSource               = 'Quelle';
$zmSlangSourceType           = 'Quellentyp';
$zmSlangSpeed                = 'Geschwindigkeit';
$zmSlangSpeedHigh            = 'Hohe Geschwindigkeit';
$zmSlangSpeedLow             = 'Niedrige Geschwindigkeit';
$zmSlangSpeedMedium          = 'Mittlere Geschwindigkeit';
$zmSlangSpeedTurbo           = 'Turbo-Geschwindigkeit';
$zmSlangStart                = 'Start';
$zmSlangState                = 'Status';
$zmSlangStats                = 'Status';
$zmSlangStatus               = 'Status';
$zmSlangStepBack             = 'Einen Schritt r&uuml;ckw&auml;rts';
$zmSlangStepForward          = 'Einen Schritt vorw&auml;rts';
$zmSlangStepLarge            = 'Gro&szlig;e Stufe';
$zmSlangStepMedium           = 'Mittlere Stufe';
$zmSlangStepNone             = 'Keine Stufe';
$zmSlangStepSmall            = 'Kleine Stufe';
$zmSlangStep                 = 'Stufe';
$zmSlangStills               = 'Bilder';
$zmSlangStopped              = 'Gestoppt';
$zmSlangStop                 = 'Stop';
$zmSlangStreamReplayBuffer   = 'Stream-Wiedergabe-Bildpuffer';
$zmSlangStream               = 'Stream';
$zmSlangSubmit               = 'Absenden';
$zmSlangSystem               = 'System';
$zmSlangTele                 = 'Tele';
$zmSlangThumbnail            = 'Miniatur';
$zmSlangTilt                 = 'Neigung';
$zmSlangTimeDelta            = 'Zeitdifferenz';
$zmSlangTimeline             = 'Zeitlinie';
$zmSlangTimestampLabelFormat = 'Format des Zeitstempels';
$zmSlangTimestampLabelX      = 'Zeitstempel-X';
$zmSlangTimestampLabelY      = 'Zeitstempel-Y';
$zmSlangTimestamp            = 'Zeitstempel';
$zmSlangTimeStamp            = 'Zeitstempel';
$zmSlangTime                 = 'Zeit';
$zmSlangToday                = 'Heute';
$zmSlangTools                = 'Werkzeuge';
$zmSlangTotalBrScore         = 'Totale<br/>Punktzahl';
$zmSlangTrackDelay           = 'Nachf&uuml;hrungsverz&ouml;gerung';
$zmSlangTrackMotion          = 'Bewegungs-Nachf&uuml;hrung';
$zmSlangTriggers             = 'Ausl&ouml;ser';
$zmSlangTurboPanSpeed        = 'Turbo-Pan-Geschwindigkeit';
$zmSlangTurboTiltSpeed       = 'Turbo-Neigungsgeschwindigkeit';
$zmSlangType                 = 'Typ';
$zmSlangUnarchive            = 'Aus Archiv entfernen';
$zmSlangUnits                = 'Einheiten';
$zmSlangUnknown              = 'Unbekannt';
$zmSlangUpdateAvailable      = 'Eine Aktualisierung f&uuml;r ZoneMinder ist verf&uuml;gbar.';
$zmSlangUpdateNotNecessary   = 'Es ist keine Aktualisierung verf&uuml;gbar.';
$zmSlangUpdate               = 'Aktualisieren';
$zmSlangUseFilter            = 'Benutze Filter';
$zmSlangUseFilterExprsPost   = '&nbsp;Filter&nbsp;Ausdr&uuml;cke'; // This is used at the end of the phrase 'use N filter expressions'
$zmSlangUseFilterExprsPre    = 'Benutze&nbsp;'; // This is used at the beginning of the phrase 'use N filter expressions'
$zmSlangUser                 = 'Benutzer';
$zmSlangUsername             = 'Benutzername';
$zmSlangUsers                = 'Benutzer';
$zmSlangValue                = 'Wert';
$zmSlangVersionIgnore        = 'Ignoriere diese Version';
$zmSlangVersionRemindDay     = 'Erinnere mich wieder in 1 Tag.';
$zmSlangVersionRemindHour    = 'Erinnere mich wieder in 1 Stunde.';
$zmSlangVersionRemindNever   = 'Informiere mich nicht mehr &uuml;ber neue Versionen.';
$zmSlangVersionRemindWeek    = 'Erinnere mich wieder in 1 Woche.';
$zmSlangVersion              = 'Version';
$zmSlangVideoFormat          = 'Videoformat';
$zmSlangVideoGenFailed       = 'Videoerzeugung fehlgeschlagen!';
$zmSlangVideoGenFiles        = 'Existierende Videodateien';
$zmSlangVideoGenNoFiles      = 'Keine Videodateien gefunden.';
$zmSlangVideoGenParms        = 'Parameter der Videoerzeugung';
$zmSlangVideoGenSucceeded    = 'Videoerzeugung erfolgreich!';
$zmSlangVideoSize            = 'Videogr&ouml;&szlig;e';
$zmSlangVideo                = 'Video';
$zmSlangViewAll              = 'Alles ansehen';
$zmSlangView                 = 'Ansicht';
$zmSlangViewEvent            = 'Zeige Ereignis';
$zmSlangViewPaged            = 'Seitenansicht';
$zmSlangWake                 = 'Aufwachen';
$zmSlangWarmupFrames         = 'Aufw&auml;rmbilder';
$zmSlangWatch                = 'Beobachte';
$zmSlangWebColour            = 'Webfarbe';
$zmSlangWeb                  = 'Web';
$zmSlangWeek                 = 'Woche';
$zmSlangWhiteBalance         = 'Wei&szlig;-Abgleich';
$zmSlangWhite                = 'Wei&szlig;';
$zmSlangWide                 = 'Weit';
$zmSlangX10ActivationString  = 'X10-Aktivierungswert';
$zmSlangX10InputAlarmString  = 'X10-Eingabe-Alarmwert';
$zmSlangX10OutputAlarmString = 'X10-Ausgabe-Alarmwert';
$zmSlangX10                  = 'X10';
$zmSlangX                    = 'X';
$zmSlangYes                  = 'Ja';
$zmSlangYouNoPerms           = 'Keine Erlaubnis zum Zugang dieser Resource.';
$zmSlangY                    = 'Y';
$zmSlangZoneAlarmColour      = 'Alarmfarbe (Rot/Gr&uuml;n/Blau)';
$zmSlangZoneArea             = 'Zone Area';
$zmSlangZoneFilterSize       = 'Filter-Breite/-H&ouml;he (Pixel)';
$zmSlangZoneMinMaxAlarmArea  = 'Min./max. Alarmfl&auml;che';
$zmSlangZoneMinMaxBlobArea   = 'Min./max. Blobfl&auml;che';
$zmSlangZoneMinMaxBlobs      = 'Min./max. Blobs';
$zmSlangZoneMinMaxFiltArea   = 'Min./max. Filterfl&auml;che';
$zmSlangZoneMinMaxPixelThres = 'Min./max. Pixelschwellwert';
$zmSlangZoneOverloadFrames   = 'Bildauslassrate bei System&uuml;berlastung';
$zmSlangZones                = 'Zonen';
$zmSlangZone                 = 'Zone';
$zmSlangZoomIn               = 'Hineinzoomen';
$zmSlangZoomOut              = 'Herauszoomen';
$zmSlangZoom                 = 'Zoom';

// Complex replacements with formatting and/or placements, must be passed through sprintf
$zmClangCurrentLogin         = 'Momentan angemeldet ist \'%1$s\'';
$zmClangEventCount           = '%1$s %2$s'; // For example '37 Events' (from Vlang below)
$zmClangLastEvents           = 'Letzte %1$s %2$s'; // For example 'Last 37 Events' (from Vlang below)
$zmClangLatestRelease        = 'Die letzte Version ist v%1$s, Sie haben v%2$s.';
$zmClangMonitorCount         = '%1$s %2$s'; // For example '4 Monitors' (from Vlang below)
$zmClangMonitorFunction      = 'Monitor %1$s Funktion';
$zmClangRunningRecentVer     = 'Sie benutzen die aktuellste Version von Zoneminder, v%s.';

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
$zmVlangEvent                = array( 0=>'Ereignisse', 1=>'Ereignis;', 2=>'Ereignisse' );
$zmVlangMonitor              = array( 0=>'Monitore', 1=>'Monitor', 2=>'Monitore' );

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
// $zmOlangPromptLANG_DEFAULT = "This is a new prompt for this option";
// $zmOlangHelpLANG_DEFAULT = "This is some new help for this option which will be displayed in the popup window when the ? is clicked";
//

?>
