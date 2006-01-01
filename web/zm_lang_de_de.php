<?php
//
// ZoneMinder web German language file, $Date$, $Revision$
// Copyright (C) 2003, 2004, 2005  Philip Coombes
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

// ZoneMinder <your language> Translation by <your name>

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
$zmSlang24BitColour          = '24-bit Farbe';
$zmSlang8BitGrey             = '8-bit Grau';
$zmSlangAction               = 'Action';
$zmSlangActual               = 'Aktuell';
$zmSlangAddNewControl        = 'Add New Control';
$zmSlangAddNewMonitor        = 'Neuer Monitor';
$zmSlangAddNewUser           = 'Neuer Benutzer';
$zmSlangAddNewZone           = 'Neue Zone';
$zmSlangAlarm                = 'Alarm';
$zmSlangAlarmBrFrames        = 'Alarm<br />Bilder';
$zmSlangAlarmFrame           = 'Alarm Bilder';
$zmSlangAlarmFrameCount      = 'Alarm Bilderanzahl';
$zmSlangAlarmLimits          = 'Alarm Limits';
$zmSlangAlarmPx              = 'Alarm Pixel';
$zmSlangAlarmRGBUnset        = 'You must set an alarm RGB colour';
$zmSlangAlert                = 'Alarm';
$zmSlangAll                  = 'Alles';
$zmSlangApplyingStateChange  = 'Aktiviere neuen Status';
$zmSlangApply                = 'OK';
$zmSlangArchArchived         = 'Nur archivierte';
$zmSlangArchive              = 'Archiv';
$zmSlangArchived             = 'Archived';
$zmSlangArchUnarchived       = 'Nur nichtarchivierte';
$zmSlangArea                 = 'Area';
$zmSlangAreaUnits            = 'Area (px/%)';
$zmSlangAttrAlarmFrames      = 'Alarm Bilder';
$zmSlangAttrArchiveStatus    = 'Archiv Status';
$zmSlangAttrAvgScore         = 'Mitt. Z&auml;hler';
$zmSlangAttrCause            = 'Grund';
$zmSlangAttrDate             = 'Datum';
$zmSlangAttrDateTime         = 'Datum/Zeit';
$zmSlangAttrDiskBlocks       = 'Disk Bl&ouml;cke';
$zmSlangAttrDiskPercent      = 'Disk Prozent';
$zmSlangAttrDuration         = 'Dauer';
$zmSlangAttrFrames           = 'Bilder';
$zmSlangAttrId               = 'Id';
$zmSlangAttrMaxScore         = 'Max. Z&auml;hler';
$zmSlangAttrMonitorId        = 'Monitor Id';
$zmSlangAttrMonitorName      = 'Monitor Name';
$zmSlangAttrName             = 'Name';
$zmSlangAttrNotes            = 'Notes';
$zmSlangAttrTime             = 'Zeit';
$zmSlangAttrTotalScore       = 'Total Z&auml;hler';
$zmSlangAttrWeekday          = 'Wochentag';
$zmSlangAutoArchiveAbbr      = 'Archive';
$zmSlangAutoArchiveEvents    = 'Auto-Archivierung aller Treffer';
$zmSlangAuto                 = 'Auto';
$zmSlangAutoDeleteAbbr       = 'Delete';
$zmSlangAutoDeleteEvents     = 'Automatisches L&ouml;schen aller Treffer';
$zmSlangAutoEmailAbbr        = 'Email';
$zmSlangAutoEmailEvents      = 'Automatische detaillierte eMail aller Treffer';
$zmSlangAutoExecuteAbbr      = 'Execute';
$zmSlangAutoExecuteEvents    = 'Automatisches Ausf&uuml;hren bei allen Treffern';
$zmSlangAutoMessageAbbr      = 'Message';
$zmSlangAutoMessageEvents    = 'Automatische, detaillierte Nachricht aller Treffer';
$zmSlangAutoStopTimeout      = 'Auto Stop Timeout';
$zmSlangAutoUploadAbbr       = 'Upload';
$zmSlangAutoUploadEvents     = 'Automatischesd Hochladen aller Treffer';
$zmSlangAutoVideoAbbr        = 'Video';
$zmSlangAutoVideoEvents      = 'Automatically create video for all matches';
$zmSlangAvgBrScore           = 'Mitt.<br/>Z&auml;hler';
$zmSlangBadNameChars         = 'Namen d&uuml;rfen nur aus Buchstaben, Zahlen und Trenn- oder Unterstrich bestehen';
$zmSlangBandwidth            = 'Bandbreite';
$zmSlangBlobPx               = 'Blob Px';
$zmSlangBlobs                = 'Blobs';
$zmSlangBlobSizes            = 'Blobgr&ouml;&szlig;e';
$zmSlangBrightness           = 'Helligkeit';
$zmSlangBuffers              = 'Puffer';
$zmSlangCanAutoFocus         = 'Kann Autofokus';
$zmSlangCanAutoGain          = 'Kann Auto Verst&auml;rk.';
$zmSlangCanAutoIris          = 'Kann Auto Iris';
$zmSlangCanAutoWhite         = 'Kann Auto Wei&szlig;-Abgl.';
$zmSlangCanAutoZoom          = 'Kann Auto Zoom';
$zmSlangCancel               = 'Abbruch';
$zmSlangCancelForcedAlarm    = 'Abbruch Unbedingter Alarm';
$zmSlangCanFocusAbs          = 'Kann Abs. Fokus';
$zmSlangCanFocusCon          = 'Kann Kont. Fokus';
$zmSlangCanFocus             = 'Kann&nbsp;Fokus';
$zmSlangCanFocusRel          = 'Kann Rel. Fokus';
$zmSlangCanGainAbs           = 'Kann Abs. Verst&auml;rkung';
$zmSlangCanGainCon           = 'Kann Kont. Verst&auml;rkung';
$zmSlangCanGain              = 'Kann Verst&auml;rkung';
$zmSlangCanGainRel           = 'Kann Rel. Verst&auml;kung';
$zmSlangCanIrisAbs           = 'Kann Abs. Iris';
$zmSlangCanIrisCon           = 'Kann Kont. Iris';
$zmSlangCanIris              = 'Kann&nbsp;Iris';
$zmSlangCanIrisRel           = 'Kann Rel. Iris';
$zmSlangCanMoveAbs           = 'Kann Abs. Beweg.';
$zmSlangCanMoveCon           = 'Kann Kont. Beweg.';
$zmSlangCanMoveDiag          = 'Kann Diag. Beweg.';
$zmSlangCanMove              = 'Kann&nbsp;Beweg.';
$zmSlangCanMoveMap           = 'Kann Mapped Beweg.';
$zmSlangCanMoveRel           = 'Kann Rel. Beweg.';
$zmSlangCanPan               = 'Kann&nbsp;Pan' ;
$zmSlangCanReset             = 'Kann&nbsp;Reset';
$zmSlangCanSetPresets        = 'Kann Setze Voreinstell.';
$zmSlangCanSleep             = 'Kann&nbsp;Sleep';
$zmSlangCanTilt              = 'Kann&nbsp;Neig.';
$zmSlangCanWake              = 'Kann&nbsp;Wake';
$zmSlangCanWhiteAbs          = 'Kann Abs. Wei&szlig;-Abgl.';
$zmSlangCanWhiteBal          = 'Kann Wei&szlig;-Abgl.';
$zmSlangCanWhiteCon          = 'Kann Kont. Wei&szlig;-Abgl.';
$zmSlangCanWhite             = 'Kann Wei&szlig;-Abgleich';
$zmSlangCanWhiteRel          = 'Kann Rel. Wei&szlig;-Abgl.';
$zmSlangCanZoomAbs           = 'Kann Abs. Zoom';
$zmSlangCanZoomCon           = 'Kann Kont. Zoom';
$zmSlangCanZoom              = 'Kann&nbsp;Zoom';
$zmSlangCanZoomRel           = 'Kann Rel. Zoom';
$zmSlangCaptureHeight        = 'Capture H&ouml;he';
$zmSlangCapturePalette       = 'Capture Farbpalette';
$zmSlangCaptureWidth         = 'Capture Breite';
$zmSlangCause                = 'Grund';
$zmSlangCheckAll             = 'Mark. alle';
$zmSlangCheckMethod          = 'Alarm Pr&uumlfmethode';
$zmSlangChooseFilter         = 'Filterauswahl';
$zmSlangChoosePreset         = 'Choose Preset';
$zmSlangClose                = 'Schlie&szlig;en';
$zmSlangColour               = 'Farbe';
$zmSlangCommand              = 'Kommando';
$zmSlangConfig               = 'Konfig.';
$zmSlangConfiguredFor        = 'Konfiguriert f&uuml;r';
$zmSlangConfirmPassword      = 'Passwortbest&auml;tigung';
$zmSlangConjAnd              = 'und';
$zmSlangConjOr               = 'oder';
$zmSlangConsole              = 'Konsole';
$zmSlangContactAdmin         = 'Bitte den Administrator f&uuml;r Details ansprechen.';
$zmSlangContinue             = 'Weiter';
$zmSlangContrast             = 'Kontrast';
$zmSlangControlAddress       = 'Kontroll Adresse';
$zmSlangControlCap           = 'Kontrollm&ouml;glichkeit';
$zmSlangControlCaps          = 'Kontrollm&ouml;glichkeiten';
$zmSlangControlDevice        = 'Kontrollger&auml;t';
$zmSlangControl              = 'Kontrolle';
$zmSlangControllable         = 'Kontollierbar';
$zmSlangControlType          = 'Kontrolltyp';
$zmSlangCycleWatch           = 'Zeitzyklus';
$zmSlangCycle                = 'Zyklus';
$zmSlangDay                  = 'Tag';
$zmSlangDefaultRate          = 'Default Rate';
$zmSlangDefaultScale         = 'Default Scale';
$zmSlangDeleteAndNext        = 'L&ouml;schen &amp; N&auml;chstes';
$zmSlangDeleteAndPrev        = 'L&ouml;schen &amp; Vorheriges';
$zmSlangDelete               = 'L&ouml;schen';
$zmSlangDeleteSavedFilter    = 'L&ouml;sche gespeichertes Filter';
$zmSlangDescription          = 'Beschreibung';
$zmSlangDeviceChannel        = 'Ger&auml;tekanal';
$zmSlangDeviceFormat         = 'Ger&auml;teformat (0=PAL,1=NTSC etc)';
$zmSlangDeviceNumber         = 'Ger&auml;tenummer (/dev/video?)';
$zmSlangDevicePath           = 'Device Path';
$zmSlangDimensions           = 'Abma&szlig;e';
$zmSlangDisableAlarms        = 'Disable Alarms';
$zmSlangDisk                 = 'Disk';
$zmSlangDonateAlready        = 'No, I\'ve already donated';
$zmSlangDonateEnticement     = 'You\'ve been running ZoneMinder for a while now and hopefully are finding it a useful addition to your home or workplace security. Although ZoneMinder is, and will remain, free and open source, it costs money to develop and support. If you would like to help support future development and new features then please consider donating. Donating is, of course, optional but very much appreciated and you can donate as much or as little as you like.<br><br>If you would like to donate please select the option below or go to http://www.zoneminder.com/donate.html in your browser.<br><br>Thank you for using ZoneMinder and don\'t forget to visit the forums on ZoneMinder.com for support or suggestions about how to make your ZoneMinder experience even better.';
$zmSlangDonate               = 'Please Donate';
$zmSlangDonateRemindDay      = 'Not yet, remind again in 1 day';
$zmSlangDonateRemindHour     = 'Not yet, remind again in 1 hour';
$zmSlangDonateRemindMonth    = 'Not yet, remind again in 1 month';
$zmSlangDonateRemindNever    = 'No, I don\'t want to donate, never remind';
$zmSlangDonateRemindWeek     = 'Not yet, remind again in 1 week';
$zmSlangDonateYes            = 'Yes, I\'d like to donate now';
$zmSlangDownload             = 'Download';
$zmSlangDuration             = 'Dauer';
$zmSlangEdit                 = 'Bearbeiten';
$zmSlangEmail                = 'eMail';
$zmSlangEnableAlarms         = 'Enable Alarms';
$zmSlangEnabled              = 'Aktiviert';
$zmSlangEnterNewFilterName   = 'Neuen Filtername eingeben';
$zmSlangErrorBrackets        = 'Fehler. Bitte nur gleiche Anzahl offener und geschlossener Klammern.';
$zmSlangError                = 'Fehler';
$zmSlangErrorValidValue      = 'Fehler. Bitte alle Werte auf richtige Eingabe pr&uuml;fen';
$zmSlangEtc                  = 'etc';
$zmSlangEvent                = 'Ereigni&szlig;';
$zmSlangEventFilter          = 'Ereigni&szlig;filter';
$zmSlangEventId              = 'Ereigni&szlig; Id';
$zmSlangEventName            = 'Ereigni&szlig;name';
$zmSlangEventPrefix          = 'Ereigni&szlig; Prefix';
$zmSlangEvents               = 'Ereignisse';
$zmSlangExclude              = 'Ausschlu&szlig;';
$zmSlangExportDetails        = 'Export Event Details';
$zmSlangExport               = 'Export';
$zmSlangExportFailed         = 'Export Failed';
$zmSlangExportFormat         = 'Export File Format';
$zmSlangExportFormatTar      = 'Tar';
$zmSlangExportFormatZip      = 'Zip';
$zmSlangExportFrames         = 'Export Frame Details';
$zmSlangExportImageFiles     = 'Export Image Files';
$zmSlangExporting            = 'Exporting';
$zmSlangExportMiscFiles      = 'Export Other Files (if present)';
$zmSlangExportOptions        = 'Export Options';
$zmSlangExportVideoFiles     = 'Export Video Files (if present)';
$zmSlangFar                  = 'Weit';
$zmSlangFeed                 = 'Feed';
$zmSlangFileColours          = 'File Colours';
$zmSlangFile                 = 'File';
$zmSlangFilePath             = 'File Path';
$zmSlangFilterPx             = 'Filter Px';
$zmSlangFilters              = 'Filters';
$zmSlangFilterUnset          = 'You must specify a filter width and height';
$zmSlangFirst                = 'Erstes';
$zmSlangFlippedHori          = 'Flipped Horizontally';
$zmSlangFlippedVert          = 'Flipped Vertically';
$zmSlangFocus                = 'Fokus';
$zmSlangForceAlarm           = 'Unbedingter&nbsp;Alarm';
$zmSlangFormat               = 'Format';
$zmSlangFPS                  = 'fps';
$zmSlangFPSReportInterval    = 'FPS Meldeinterval';
$zmSlangFrame                = 'Bild';
$zmSlangFrameId              = 'Bild Id';
$zmSlangFrameRate            = 'Bildrate';
$zmSlangFrames               = 'Bilder';
$zmSlangFrameSkip            = 'Bilder auslassen';
$zmSlangFTP                  = 'FTP';
$zmSlangFunc                 = 'Funktion';
$zmSlangFunction             = 'Funktion';
$zmSlangGain                 = 'Verst&auml;rkung';
$zmSlangGeneral              = 'General';
$zmSlangGenerateVideo        = 'Erzeuge Video';
$zmSlangGeneratingVideo      = 'Erzeuge Video';
$zmSlangGoToZoneMinder       = 'Gehe zu ZoneMinder.com';
$zmSlangGrey                 = 'Grau';
$zmSlangGroups               = 'Gruppen';
$zmSlangHasFocusSpeed        = 'Hat Fokus-Geschw.';
$zmSlangHasGainSpeed         = 'Hat Verst&auml;k. Speed';
$zmSlangHasHomePreset        = 'Hat Standard Voreinstell.';
$zmSlangHasIrisSpeed         = 'Hat Irisgeschw.';
$zmSlangHasPanSpeed          = 'Hat Pan-Geschw.';
$zmSlangHasPresets           = 'Hat Voreinstell.';
$zmSlangHasTiltSpeed         = 'Hat Neig.-Geschw.';
$zmSlangHasTurboPan          = 'Hat Turbo Pan';
$zmSlangHasTurboTilt         = 'Hat Turbo Neig.';
$zmSlangHasWhiteSpeed        = 'Hat Wei&szlig;-Abgl.geschw.';
$zmSlangHasZoomSpeed         = 'Hat Zoom-Geschw.';
$zmSlangHighBW               = 'Hohe&nbsp;B/W';
$zmSlangHigh                 = 'Hohe';
$zmSlangHome                 = 'Home';
$zmSlangHour                 = 'Stunde';
$zmSlangHue                  = 'Farbton';
$zmSlangId                   = 'Id';
$zmSlangIdle                 = 'Leerlauf';
$zmSlangIgnore               = 'Ignoriere';
$zmSlangImage                = 'Bild';
$zmSlangImageBufferSize      = 'Bildpuffergr&ouml;&szlig;e (Bilder)';
$zmSlangInclude              = 'Einschlu&szlig;';
$zmSlangIn                   = 'In';
$zmSlangInverted             = 'Invertiert';
$zmSlangIris                 = 'Iris';
$zmSlangLanguage             = 'Sprache';
$zmSlangLast                 = 'Letztes';
$zmSlangLimitResultsPost     = 'Ergebnisse;'; // This is used at the end of the phrase 'Limit to first N results only'
$zmSlangLimitResultsPre      = 'Begrenze nur auf die ersten'; // This is used at the beginning of the phrase 'Limit to first N results only'
$zmSlangList                 = 'List';
$zmSlangLoad                 = 'Last';
$zmSlangLocal                = 'Lokal';
$zmSlangLoggedInAs           = 'Angemeldet als';
$zmSlangLoggingIn            = 'Anmelden';
$zmSlangLogin                = 'Anmeldung';
$zmSlangLogout               = 'Abmelden';
$zmSlangLowBW                = 'Niedr.&nbsp;B/W';
$zmSlangLow                  = 'Niedrige';
$zmSlangMain                 = 'Haupt';
$zmSlangMan                  = 'Man';
$zmSlangManual               = 'Manual';
$zmSlangMark                 = 'Mark.';
$zmSlangMaxBandwidth         = 'Max Bandwidth';
$zmSlangMaxBrScore           = 'Max.<br />Z&auml;hler';
$zmSlangMaxFocusRange        = 'Max Fokusbereich';
$zmSlangMaxFocusSpeed        = 'Max Fokusgeschw.';
$zmSlangMaxFocusStep         = 'Max Fokusstufe';
$zmSlangMaxGainRange         = 'Max Verst&auml;k.bereich';
$zmSlangMaxGainSpeed         = 'Max Verst&auml;k.geschw.';
$zmSlangMaxGainStep          = 'Max Verst&auml;k.stufe';
$zmSlangMaximumFPS           = 'Maximal FPS';
$zmSlangMaxIrisRange         = 'Max Irisbereich';
$zmSlangMaxIrisSpeed         = 'Max Irisgeschw.';
$zmSlangMaxIrisStep          = 'Max Irisstufe';
$zmSlangMax                  = 'Max';
$zmSlangMaxPanRange          = 'Max Pan-Bereich';
$zmSlangMaxPanSpeed          = 'Max Pan-Geschw.';
$zmSlangMaxPanStep           = 'Max Pan-Stufe';
$zmSlangMaxTiltRange         = 'Max Neig.-Bereich';
$zmSlangMaxTiltSpeed         = 'Max Neig.-Geschw.';
$zmSlangMaxTiltStep          = 'Max Neig.-Stufe';
$zmSlangMaxWhiteRange        = 'Max Wei&szlig;-Abgl.bereich';
$zmSlangMaxWhiteSpeed        = 'Max Wei&szlig;-Abgl.geschw.';
$zmSlangMaxWhiteStep         = 'Max Wei&szlig;-Abgl.stufe';
$zmSlangMaxZoomRange         = 'Max Zoom-Bereich';
$zmSlangMaxZoomSpeed         = 'Max Zoom-Geschw.';
$zmSlangMaxZoomStep          = 'Max Zoom-Stufe';
$zmSlangMediumBW             = 'Mitt.&nbsp;B/W';
$zmSlangMedium               = 'Mittlere';
$zmSlangMinAlarmAreaLtMax    = 'Minimum alarm area should be less than maximum';
$zmSlangMinAlarmAreaUnset    = 'You must specify the minimum alarm pixel count';
$zmSlangMinAlarmGeMinBlob    = 'Minimale Alarmpixelanzahl muss gr&ouml;&szlig;er oder gleich der minimum Areapixel sein';
$zmSlangMinAlarmGeMinFilter  = 'Minimale Alarmpixelanzahl muss gr&ouml;&szlig;er oder gleich der minimum Filterpixel sein';
$zmSlangMinAlarmPixelsLtMax  = 'Minimale Alarmpixelanzahl muss kleiner als maximale Alarmpixelanzahl sein';
$zmSlangMinBlobAreaLtMax     = 'Minimale Blob-Fl&auml;che muss kleiner sein als maximale';
$zmSlangMinBlobAreaUnset     = 'You must specify the minimum blob pixel count';
$zmSlangMinBlobLtMinFilter   = 'Minimum blob area should be less than or equal to minimum filter area';
$zmSlangMinBlobsLtMax        = 'Minimal Blobs muss kleiner als maximal sein';
$zmSlangMinBlobsUnset        = 'You must specify the minimum blob count';
$zmSlangMinFilterAreaLtMax   = 'Minimum filter area should be less than maximum';
$zmSlangMinFilterAreaUnset   = 'You must specify the minimum filter pixel count';
$zmSlangMinFilterLtMinAlarm  = 'Minimum filter area should be less than or equal to minimum alarm area';
$zmSlangMinFilterPixelsLtMax = 'Minimale Filterpixelanzahl muss kleiner als maximale Filterpixelanzahl sein';
$zmSlangMinFocusRange        = 'Min Fokusbereich';
$zmSlangMinFocusSpeed        = 'Min Fokusgeschw.';
$zmSlangMinFocusStep         = 'Min Fokusstufe';
$zmSlangMinGainRange         = 'Min Verst&auml;rk.bereich';
$zmSlangMinGainSpeed         = 'Min Verst&auml;rk.geschw.';
$zmSlangMinGainStep          = 'Min Verst&auml;rk.stufe';
$zmSlangMinIrisRange         = 'Min Irisbereich';
$zmSlangMinIrisSpeed         = 'Min Irisgeschwindigkeit';
$zmSlangMinIrisStep          = 'Min Irisstufe';
$zmSlangMinPanRange          = 'Min Pan-Bereich';
$zmSlangMinPanSpeed          = 'Min Pan-Geschw.';
$zmSlangMinPanStep           = 'Min Pan-Stufe';
$zmSlangMinPixelThresLtMax   = 'Minimaler Pixelschwellwert muss kleiner als maximaler sein';
$zmSlangMinPixelThresUnset   = 'You must specify a minimum pixel threshold';
$zmSlangMinTiltRange         = 'Min Neig.-Bereich';
$zmSlangMinTiltSpeed         = 'Min Neig.-Geschw.';
$zmSlangMinTiltStep          = 'Min Neig.-Stufe';
$zmSlangMinWhiteRange        = 'Min Wei&szlig;-Abgl.bereich';
$zmSlangMinWhiteSpeed        = 'Min Wei&szlig;-Abgl.geschw.';
$zmSlangMinWhiteStep         = 'Min Wei&szlig;-Abgl.stufe';
$zmSlangMinZoomRange         = 'Min Zoom-Bereich';
$zmSlangMinZoomSpeed         = 'Min Zoom-Geschw.';
$zmSlangMinZoomStep          = 'Min Zoom-Stufe';
$zmSlangMisc                 = 'Verschied.';
$zmSlangMonitorIds           = 'Monitor&nbsp;Id';
$zmSlangMonitor              = 'Monitor';
$zmSlangMonitorPresetIntro   = 'Select an appropriate preset from the list below.<br><br>Please note that this may overwrite any values you already have configured for this monitor.<br><br>';
$zmSlangMonitorPreset        = 'Monitor Preset';
$zmSlangMonitors             = 'Monitore';
$zmSlangMontage              = 'Montage';
$zmSlangMonth                = 'Monat';
$zmSlangMove                 = 'Beweg.';
$zmSlangMustBeGe             = 'muss gr&ouml;&szlig;er oder gleich sein als';
$zmSlangMustBeLe             = 'muss kleiner oder gleich sein als';
$zmSlangMustConfirmPassword  = 'Sie m&uuml;ssen das Passwort best&auml;tigen';
$zmSlangMustSupplyPassword   = 'Sie m&uuml;ssen ein Passwort vergeben';
$zmSlangMustSupplyUsername   = 'Sie m&uuml;ssen einen Usernamen vergeben';
$zmSlangName                 = 'Name';
$zmSlangNear                 = 'Nah';
$zmSlangNetwork              = 'Netzwerk';
$zmSlangNewGroup             = 'Neue Gruppe';
$zmSlangNew                  = 'Neu';
$zmSlangNewPassword          = 'Neues Passwort';
$zmSlangNewState             = 'Neuer Status';
$zmSlangNewUser              = 'Neuer Benutzer';
$zmSlangNext                 = 'N&auml;chstes';
$zmSlangNoFramesRecorded     = 'Es gibt keine Aufnahmen von diesem Ereigni&szlig;';
$zmSlangNoGroup              = 'No Group';
$zmSlangNoGroups             = 'Keine Gruppen definiert';
$zmSlangNoneAvailable        = 'Nichts verf&uuml;gbar';
$zmSlangNo                   = 'Nein';
$zmSlangNone                 = 'ohne';
$zmSlangNormal               = 'Normal';
$zmSlangNoSavedFilters       = 'Keine gespeicherten Filter';
$zmSlangNoStatisticsRecorded = 'Keine Statistik f&uuml;r dieses Ereigni&szlig;/Bilder';
$zmSlangNotes                = 'Notes';
$zmSlangNumPresets           = 'Num. Voreinstell.';
$zmSlangOpen                 = '&Ouml;ffnen';
$zmSlangOpEq                 = 'gleich zu';
$zmSlangOpGtEq               = 'gr&ouml;&szlig;er oder gleich wie';
$zmSlangOpGt                 = 'gr&ouml;&szlig;er als';
$zmSlangOpIn                 = 'in Satz';
$zmSlangOpLtEq               = 'kleiner oder gleich wie';
$zmSlangOpLt                 = 'kleiner als';
$zmSlangOpMatches            = 'zutreffen';
$zmSlangOpNe                 = 'nicht gleich';
$zmSlangOpNotIn              = 'nicht im Satz';
$zmSlangOpNotMatches         = 'nicht zutreffend';
$zmSlangOptionHelp           = 'OptionHilfe';
$zmSlangOptionRestartWarning = 'Ver&auml;nderungen werden erst bei Neustart des Programms aktiv.\nF&uuml;r eine sofortige &Auml;nderung starten Sie das Programm bitte neu.';
$zmSlangOptions              = 'Optionen';
$zmSlangOrder                = 'Order';
$zmSlangOrEnterNewName       = 'oder neuen Name eingeben';
$zmSlangOrientation          = 'Ausrichtung';
$zmSlangOut                  = 'Out';
$zmSlangOverwriteExisting    = '&Uuml;berschreibe bestehende';
$zmSlangPaged                = 'Paged';
$zmSlangPanLeft              = 'Pan Left';
$zmSlangPan                  = 'Pan';
$zmSlangPanRight             = 'Pan Right';
$zmSlangPanTilt              = 'Pan/Neigung';
$zmSlangParameter            = 'Parameter';
$zmSlangPassword             = 'Passwort';
$zmSlangPasswordsDifferent   = 'Die Passw&ouml;rter sind unterschiedlich';
$zmSlangPaths                = 'Pfade';
$zmSlangPhoneBW              = 'Tel.&nbsp;B/W';
$zmSlangPhone                = 'Telephon';
$zmSlangPixels               = 'Punkte';
$zmSlangPlayAll              = 'Alle zeigen';
$zmSlangPleaseWait           = 'Bitte warten';
$zmSlangPoint                = 'Point';
$zmSlangPostEventImageBuffer = 'Nachereigni&szlig;puffer';
$zmSlangPreEventImageBuffer  = 'Vorereigni&szlig;puffer';
$zmSlangPresets              = 'Vorein.';
$zmSlangPreset               = 'Voreinstell.';
$zmSlangPrev                 = 'Vorheriges';
$zmSlangRate                 = 'Rate';
$zmSlangReal                 = 'Real';
$zmSlangRecord               = 'Aufnahme';
$zmSlangRefImageBlendPct     = 'Referenz Bild Blend %ge';
$zmSlangRefresh              = 'Refresh';
$zmSlangRemote               = 'Entfernt';
$zmSlangRemoteHostName       = 'Entfernter Host Name';
$zmSlangRemoteHostPath       = 'Entfernter Host Pfad';
$zmSlangRemoteHostPort       = 'Entfernter Host Port';
$zmSlangRemoteImageColours   = 'Entfernter Bildfarbe';
$zmSlangRename               = 'Umbenennen';
$zmSlangReplay               = 'Wiederholung';
$zmSlangResetEventCounts     = 'L&ouml;sche Ereigni&szlig;z&auml;hler';
$zmSlangReset                = 'Reset';
$zmSlangRestarting           = 'Neustarten';
$zmSlangRestart              = 'Neustart';
$zmSlangRestrictedCameraIds  = 'Verbotene Kamera Id';
$zmSlangReturnDelay          = 'R&uuml;ckkehr Verz&ouml;g.';
$zmSlangReturnLocation       = 'R&uuml;ckkehrpunkt';
$zmSlangRotateLeft           = 'Drehung Links';
$zmSlangRotateRight          = 'Drehung Rechts';
$zmSlangRunMode              = 'Betriebsmodus';
$zmSlangRunning              = 'In Betrieb';
$zmSlangRunState             = 'Laufender Status';
$zmSlangSaveAs               = 'Speichere als';
$zmSlangSaveFilter           = 'Speichere Filter';
$zmSlangSave                 = 'OK';
$zmSlangScale                = 'Skalierung';
$zmSlangScore                = 'Z&auml;hler';
$zmSlangSecs                 = 'Sekunden';
$zmSlangSectionlength        = 'Sektionsl&auml;nge';
$zmSlangSelect               = 'Selektiere';
$zmSlangSelfIntersecting     = 'Polygon edges must not intersect';
$zmSlangSetLearnPrefs        = 'Setze Lernmerkmale'; // This can be ignored for now
$zmSlangSetNewBandwidth      = 'Setze neue Bandbreite';
$zmSlangSetPreset            = 'Setze Voreinstellung';
$zmSlangSet                  = 'Setze';
$zmSlangSettings             = 'Einstellungen';
$zmSlangShowFilterWindow     = 'ZeigeFilterFenster';
$zmSlangShowTimeline         = 'Show Timeline';
$zmSlangSize                 = 'Size';
$zmSlangSleep                = 'Sleep';
$zmSlangSortAsc              = 'Asc';
$zmSlangSortBy               = 'Sort. nach';
$zmSlangSortDesc             = 'Beschr.';
$zmSlangSource               = 'Quelle';
$zmSlangSourceType           = 'Quellentyp';
$zmSlangSpeed                = 'Geschwindigkeit';
$zmSlangSpeedHigh            = 'Hohe Geschw.';
$zmSlangSpeedLow             = 'Niedrige Geschw.';
$zmSlangSpeedMedium          = 'Nittlere Geschw.';
$zmSlangSpeedTurbo           = 'Turbo Geschw.';
$zmSlangStart                = 'Start';
$zmSlangState                = 'Status';
$zmSlangStats                = 'Status';
$zmSlangStatus               = 'Status';
$zmSlangStepLarge            = 'Gro&szlig;e Stufe';
$zmSlangStepMedium           = 'Mittlere Stufe';
$zmSlangStepNone             = 'Keine Stufe';
$zmSlangStepSmall            = 'Kleine Stufe';
$zmSlangStep                 = 'Stufe';
$zmSlangStills               = 'Bilder';
$zmSlangStopped              = 'Gestoppt';
$zmSlangStop                 = 'Stop';
$zmSlangStream               = 'Stream';
$zmSlangSubmit               = 'Submit';
$zmSlangSystem               = 'System';
$zmSlangTele                 = 'Tele';
$zmSlangThumbnail            = 'Miniatur';
$zmSlangTilt                 = 'Neig.';
$zmSlangTimeDelta            = 'Zeitdifferenz';
$zmSlangTimeline             = 'Timeline';
$zmSlangTimestampLabelFormat = 'Format des Zeitstempel';
$zmSlangTimestampLabelX      = 'Zeitstempel X';
$zmSlangTimestampLabelY      = 'Zeitstempel Y';
$zmSlangTimestamp            = 'Zeitstempel';
$zmSlangTimeStamp            = 'Zeit Stempel';
$zmSlangTime                 = 'Zeit';
$zmSlangToday                = 'Heute';
$zmSlangTools                = 'Tools';
$zmSlangTotalBrScore         = 'Total<br/>Z&auml;hler';
$zmSlangTrackDelay           = 'Verz&ouml;g. Nachf&uuml;hrung';
$zmSlangTrackMotion          = 'Beweg.-Nachf&uuml;hrung';
$zmSlangTriggers             = 'Ausl&ouml;ser';
$zmSlangTurboPanSpeed        = 'Turbo Pan-Geschw.';
$zmSlangTurboTiltSpeed       = 'Turbo Neig.-Geschw.';
$zmSlangType                 = 'Typ';
$zmSlangUnarchive            = 'Nichtarchiviert';
$zmSlangUnits                = 'Einheiten';
$zmSlangUnknown              = 'Unbekannt';
$zmSlangUpdateAvailable      = 'Ein Update fuer ZoneMinder ist verf&uuml;gbar';
$zmSlangUpdateNotNecessary   = 'Es ist kein Update verf&uuml;gbar';
$zmSlangUpdate               = 'Update';
$zmSlangUseFilter            = 'Benutze Filter';
$zmSlangUseFilterExprsPost   = '&nbsp;Filter&nbsp;Ausdr&uuml;cke'; // This is used at the end of the phrase 'use N filter expressions'
$zmSlangUseFilterExprsPre    = 'Benutze&nbsp;'; // This is used at the beginning of the phrase 'use N filter expressions'
$zmSlangUser                 = 'Benutzer';
$zmSlangUsername             = 'Ben.-name';
$zmSlangUsers                = 'Benutzer';
$zmSlangValue                = 'Wert';
$zmSlangVersionIgnore        = 'Ignoriere diese Version';
$zmSlangVersionRemindDay     = 'Erinnere mich wieder in 1 Tag';
$zmSlangVersionRemindHour    = 'Erinnere mich wieder in 1 Stunde';
$zmSlangVersionRemindNever   = 'Informiere mich nicht mehr &uuml;ber neue Versionen';
$zmSlangVersionRemindWeek    = 'Erinnere mich wieder in 1 Woche';
$zmSlangVersion              = 'Version';
$zmSlangVideoFormat          = 'Video Format';
$zmSlangVideoGenFailed       = 'Videoerzeugung fehlgeschlagen!';
$zmSlangVideoGenFiles        = 'Existing Video Files';
$zmSlangVideoGenNoFiles      = 'No Video Files Found';
$zmSlangVideoGenParms        = 'Videoerzeugung Parameter';
$zmSlangVideoGenSucceeded    = 'Video Generation Succeeded!';
$zmSlangVideoSize            = 'Videogr&ouml;&szlig;e';
$zmSlangVideo                = 'Video';
$zmSlangViewAll              = 'Alles Ansehen';
$zmSlangView                 = 'Ansicht';
$zmSlangViewEvent            = 'View Event';
$zmSlangViewPaged            = 'Seitenansicht';
$zmSlangWake                 = 'Wake';
$zmSlangWarmupFrames         = 'Aufw&auml;rmbilder';
$zmSlangWatch                = 'Beobachte';
$zmSlangWebColour            = 'Web Colour';
$zmSlangWeb                  = 'Web';
$zmSlangWeek                 = 'Woche';
$zmSlangWhiteBalance         = 'Wei&szlig;-Abgleich';
$zmSlangWhite                = 'Wei&szlig;';
$zmSlangWide                 = 'Weit';
$zmSlangX10ActivationString  = 'X10 Aktivierungswert';
$zmSlangX10InputAlarmString  = 'X10 Eingabe Alarmwert';
$zmSlangX10OutputAlarmString = 'X10 Ausgabe Alarmwert';
$zmSlangX10                  = 'X10';
$zmSlangX                    = 'X';
$zmSlangYes                  = 'Ja';
$zmSlangYouNoPerms           = 'Keine Erlaubniss zum Zugang dieser Resource.';
$zmSlangY                    = 'Y';
$zmSlangZoneAlarmColour      = 'Alarm Farbe (Red/Green/Blue)';
$zmSlangZoneAlarmThreshold   = 'Alarm Schwellwert (0-255)';
$zmSlangZoneArea             = 'Zone Area';
$zmSlangZoneFilterHeight     = 'Filter H&ouml;he (Pixel)';
$zmSlangZoneFilterSize       = 'Filter Width/Height (pixels)';
$zmSlangZoneFilterWidth      = 'Filter Breite (Pixel)';
$zmSlangZoneMaxAlarmedArea   = 'Maximal &uuml;berwachte Fl&auml;che';
$zmSlangZoneMaxBlobArea      = 'Maximale Gebietsfl&auml;che';
$zmSlangZoneMaxBlobs         = 'Maximale Gebietsanzahl';
$zmSlangZoneMaxFilteredArea  = 'Maximal gefilterte Fl&auml;che';
$zmSlangZoneMaxPixelThres    = 'Maximaler Pixelschwellwert (0-255)';
$zmSlangZoneMaxX             = 'Maximum X (rechts)';
$zmSlangZoneMaxY             = 'Maximum Y (unten)';
$zmSlangZoneMinAlarmedArea   = 'Minimal &uuml;berwachte Fl&auml;che';
$zmSlangZoneMinBlobArea      = 'Minimale Gebietsfl&auml;che';
$zmSlangZoneMinBlobs         = 'Minimale Gebietsanzahl';
$zmSlangZoneMinFilteredArea  = 'Minimal gefilterte Fl&auml;che';
$zmSlangZoneMinMaxAlarmArea  = 'Min/Max Alarmed Area';
$zmSlangZoneMinMaxBlobArea   = 'Min/Max Blob Area';
$zmSlangZoneMinMaxBlobs      = 'Min/Max Blobs';
$zmSlangZoneMinMaxFiltArea   = 'Min/Max Filtered Area';
$zmSlangZoneMinMaxPixelThres = 'Min/Max Pixel Threshold (0-255)';
$zmSlangZoneMinPixelThres    = 'Minimaler Pixelschwellwert (0-255)';
$zmSlangZoneMinX             = 'Minimum X (links)';
$zmSlangZoneMinY             = 'Minimum Y (oben)';
$zmSlangZones                = 'Zonen';
$zmSlangZone                 = 'Zone';
$zmSlangZoomIn               = 'Zoom In';
$zmSlangZoomOut              = 'Zoom Out';
$zmSlangZoom                 = 'Zoom';


// Complex replacements with formatting and/or placements, must be passed through sprintf
$zmClangCurrentLogin         = 'Momentan angemeldet ist \'%1$s\'';
$zmClangEventCount           = '%1$s %2$s'; // For example '37 Events' (from Vlang below)
$zmClangLastEvents           = 'Letzte %1$s %2$s'; // For example 'Last 37 Events' (from Vlang below)
$zmClangLatestRelease        = 'Die letzte Version ist v%1$s,Sie haben v%2$s.';
$zmClangMonitorCount         = '%1$s %2$s'; // For example '4 Monitors' (from Vlang below)
$zmClangMonitorFunction      = 'Monitor %1$s Funktion';
$zmClangRunningRecentVer     = 'Sie benutzen die meist verbreitete Version von Zoneminder, v%s.';

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
$zmVlangEvent                = array( 0=>'Ereignisse', 1=>'Ereigni&szlig;', 2=>'Ereignisse' );
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
