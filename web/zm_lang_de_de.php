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
$zmSlang24BitColour          = '24 bit Farbe';
$zmSlang8BitGrey             = '8 bit Graustufe';
$zmSlangActual               = 'Aktuell';
$zmSlangAddNewMonitor        = 'Neuer Monitor';
$zmSlangAddNewUser           = 'Neuer User';
$zmSlangAddNewZone           = 'Neue Zone';
$zmSlangAlarm                = 'Alarm';
$zmSlangAlarmBrFrames        = 'Alarm<br/>Bilder';
$zmSlangAlarmFrame           = 'Alarm Bilder';
$zmSlangAlarmFrameCount      = 'Alarm Frame Count';
$zmSlangAlarmLimits          = 'Alarm Limits';
$zmSlangAlarmPx              = 'Alarm Pixel';
$zmSlangAlert                = 'Alarm';
$zmSlangAll                  = 'Alles';
$zmSlangApplyingStateChange  = 'Aktiviere neuen Status';
$zmSlangApply                = 'Zuweisen';
$zmSlangArchArchived         = 'Nur Archivierte';
$zmSlangArchive              = 'Archiv';
$zmSlangArchUnarchived       = 'Nur nichtarchivierte';
$zmSlangAttrAlarmFrames      = 'Alarm Bilder';
$zmSlangAttrArchiveStatus    = 'Archiv Status';
$zmSlangAttrAvgScore         = 'Avg. Zähler';
$zmSlangAttrCause            = 'Cause';
$zmSlangAttrDate             = 'Datum';
$zmSlangAttrDateTime         = 'Datum/Zeit';
$zmSlangAttrDiskBlocks       = 'Disk Blocks';
$zmSlangAttrDiskPercent      = 'Disk Percent';
$zmSlangAttrDuration         = 'Dauer';
$zmSlangAttrFrames           = 'Bilder';
$zmSlangAttrId               = 'Id';
$zmSlangAttrMaxScore         = 'Max. Zähler';
$zmSlangAttrMonitorId        = 'Monitor Id';
$zmSlangAttrMonitorName      = 'Monitor Name';
$zmSlangAttrName             = 'Name';
$zmSlangAttrTime             = 'Zeit';
$zmSlangAttrTotalScore       = 'Total Zähler';
$zmSlangAttrWeekday          = 'Wochentag';
$zmSlangAutoArchiveEvents    = 'Automatically archive all matches';
$zmSlangAutoDeleteEvents     = 'Automatically delete all matches';
$zmSlangAutoEmailEvents      = 'Automatically email details of all matches';
$zmSlangAutoExecuteEvents    = 'Automatically execute command on all matches';
$zmSlangAutoMessageEvents    = 'Automatically message details of all matches';
$zmSlangAutoUploadEvents     = 'Automatically upload all matches';
$zmSlangAvgBrScore           = 'Avg.<br/>Zähler';
$zmSlangBadMonitorChars      = 'Monitor Namen dürfen nur aus buchstaben,zahlen und trenn oder unterstrich bestehen ';
$zmSlangBandwidth            = 'Bandbreite';
$zmSlangBlobPx               = 'Gebiets Pixel';
$zmSlangBlobs                = 'Gebiet ';
$zmSlangBlobSizes            = 'Gebiets Größe';
$zmSlangBrightness           = 'Helligkeit';
$zmSlangBuffers              = 'Puffer';
$zmSlangCancel               = 'Abbruch';
$zmSlangCancelForcedAlarm    = 'Abbruch&nbsp;Unbedingter&nbsp;Alarm';
$zmSlangCaptureHeight        = 'Capture Höhe';
$zmSlangCapturePalette       = 'Capture FarbPalette';
$zmSlangCaptureWidth         = 'Capture Breite';
$zmSlangCause                = 'Cause';
$zmSlangCheckAll             = 'Prüfe Alles';
$zmSlangCheckMethod          = 'Alarm Check Methode';
$zmSlangChooseFilter         = 'Filterauswahl';
$zmSlangClose                = 'Schließen';
$zmSlangColour               = 'Farbe';
$zmSlangConfig               = 'Config';
$zmSlangConfiguredFor        = 'Eingerichtet für';
$zmSlangConfirmPassword      = 'Passwortbestätigung';
$zmSlangConjAnd              = 'und';
$zmSlangConjOr               = 'oder';
$zmSlangConsole              = 'Konsole';
$zmSlangContactAdmin         = 'Bitte den Administrator für Details ansprechen.';
$zmSlangContinue             = 'Continue';
$zmSlangContrast             = 'Kontrast';
$zmSlangCycle                = 'Cycle';
$zmSlangCycleWatch           = 'Zeitzyklus';
$zmSlangDay                  = 'Tag';
$zmSlangDeleteAndNext        = 'Löschen &amp; Nächstes';
$zmSlangDeleteAndPrev        = 'Löschen &amp; Voriges';
$zmSlangDelete               = 'Löschen';
$zmSlangDeleteSavedFilter    = 'Lösche gespeichertes Filter';
$zmSlangDescription          = 'Beschreibung';
$zmSlangDeviceChannel        = 'Geräte Kanal';
$zmSlangDeviceFormat         = 'Geräte Format (0=PAL,1=NTSC etc)';
$zmSlangDeviceNumber         = 'Geräte Nummer (/dev/video?)';
$zmSlangDimensions           = 'Abmaße';
$zmSlangDisk                 = 'Disk';
$zmSlangDuration             = 'Dauer';
$zmSlangEdit                 = 'Bearbeiten';
$zmSlangEmail                = 'Email';
$zmSlangEnabled              = 'Aktiviert';
$zmSlangEnterNewFilterName   = 'Neuen Filtername eingeben';
$zmSlangErrorBrackets        = 'Fehler , Bitte nur gleiche anzahl offener und geschlossener Brackets .';
$zmSlangError                = 'Fehler';
$zmSlangErrorValidValue      = 'Fehler ,Bitte alle Werte auf richtige Eingabe prüfen';
$zmSlangEtc                  = 'etc';
$zmSlangEvent                = 'Ereigniss';
$zmSlangEventFilter          = 'Ereigniss Filter';
$zmSlangEventId              = 'Event Id';
$zmSlangEventName            = 'Event Name';
$zmSlangEventPrefix          = 'Event Prefix';
$zmSlangEvents               = 'Ereignisse';
$zmSlangExclude              = 'Ausschluß';
$zmSlangFeed                 = 'Feed';
$zmSlangFilterPx             = 'Filter Px';
$zmSlangFirst                = 'Erstes';
$zmSlangForceAlarm           = 'Unbedingter&nbsp;Alarm';
$zmSlangFPS                  = 'fps';
$zmSlangFPSReportInterval    = 'FPS Report Interval';
$zmSlangFrame                = 'Bild';
$zmSlangFrameId              = 'Bild Id';
$zmSlangFrameRate            = 'Bildrate';
$zmSlangFrames               = 'Bilder';
$zmSlangFrameSkip            = 'Bilder auslassen';
$zmSlangFTP                  = 'FTP';
$zmSlangFunc                 = 'Funktion';
$zmSlangFunction             = 'Funktion';
$zmSlangGenerateVideo        = 'Erzeuge Video';
$zmSlangGeneratingVideo      = 'Erzeuge Video';
$zmSlangGoToZoneMinder       = 'Gehe zu ZoneMinder.com';
$zmSlangGrey                 = 'Grau';
$zmSlangGroups               = 'Groups';
$zmSlangHighBW               = 'Hohe&nbsp;B/W';
$zmSlangHigh                 = 'Hohe';
$zmSlangHour                 = 'Stunde';
$zmSlangHue                  = 'Farbabstufung';
$zmSlangId                   = 'Id';
$zmSlangIdle                 = 'Leerlauf';
$zmSlangIgnore               = 'Ignoriere';
$zmSlangImage                = 'Bild';
$zmSlangImageBufferSize      = 'Bildpuffergröße (bilder)';
$zmSlangInclude              = 'Einschluß';
$zmSlangInverted             = 'Invertiert';
$zmSlangLanguage             = 'Sprache';
$zmSlangLast                 = 'Letztes';
$zmSlangLimitResultsPost     = 'results only;'; // This is used at the end of the phrase 'Limit to first N results only'
$zmSlangLimitResultsPre      = 'Limit to first'; // This is used at the beginning of the phrase 'Limit to first N results only'
$zmSlangLoad                 = 'Last';
$zmSlangLocal                = 'Lokal';
$zmSlangLoggedInAs           = 'Angemeldet als';
$zmSlangLoggingIn            = 'Amnelden ';
$zmSlangLogin                = 'Anmeldung';
$zmSlangLogout               = 'Abmelden';
$zmSlangLowBW                = 'Niedrige&nbsp;B/W';
$zmSlangLow                  = 'Niedrige';
$zmSlangMark                 = 'Markiert';
$zmSlangMaxBrScore           = 'Max.<br/>Zähler';
$zmSlangMaximumFPS           = 'Maximal FPS';
$zmSlangMax                  = 'Max';
$zmSlangMediumBW             = 'Mittlere&nbsp;B/W';
$zmSlangMedium               = 'Mittlere';
$zmSlangMinAlarmGeMinBlob    = 'Minimale alarmpixelzahl muss größer oder gleich der minimum Arealpixel sein';
$zmSlangMinAlarmGeMinFilter  = 'Minimale alarmpixelzahl muss größer oder gleich der minimum Filterpixel sein';
$zmSlangMinAlarmPixelsLtMax  = 'Minimale Alarmpixelanzahl muss kleiner als maximale Alarmpixelanzahl sein';
$zmSlangMinBlobAreaLtMax     = 'Minimale Gebietsfläche muss kleiner sein als maximale Gebietsfläche';
$zmSlangMinBlobsLtMax        = 'Minimal Blobs muss kleiner als maximal Blobs sein';
$zmSlangMinFilterPixelsLtMax = 'Minimale Filterpixelanzahl muss kleiner als maximale Filterpixelanzahl sein';
$zmSlangMinPixelThresLtMax   = 'Minimaler pixelschwellwert muss kleiner als maximaler pixelschwellwert sein';
$zmSlangMisc                 = 'Misc';
$zmSlangMonitorIds           = 'Monitor&nbsp;Id';
$zmSlangMonitor              = 'Monitor';
$zmSlangMonitors             = 'Monitors';
$zmSlangMontage              = 'Montage';
$zmSlangMonth                = 'Monat';
$zmSlangMustBeGe             = 'muss größer oder gleich sein wie';
$zmSlangMustBeLe             = 'muss kleiner oder gleich sein wie';
$zmSlangMustConfirmPassword  = 'Sie müssen das Passwort bestätigen';
$zmSlangMustSupplyPassword   = 'Sie müssen ein Passwort vergeben';
$zmSlangMustSupplyUsername   = 'Sie müssen einen Usernamen vergeben';
$zmSlangName                 = 'Name';
$zmSlangNetwork              = 'Netzwerk';
$zmSlangNewGroup             = 'New Group';
$zmSlangNew                  = 'Neu';
$zmSlangNewPassword          = 'Neues Passwort';
$zmSlangNewState             = 'Neuer Status';
$zmSlangNewUser              = 'Neuer User';
$zmSlangNext                 = 'Nächstes';
$zmSlangNoFramesRecorded     = 'Es gibt keine Aufnahmen von diesem Ereigniss';
$zmSlangNoGroups             = 'No groups have been defined';
$zmSlangNoneAvailable        = 'Nichts verfügbar';
$zmSlangNo                   = 'Nein';
$zmSlangNone                 = 'Nichts';
$zmSlangNormal               = 'Normal';
$zmSlangNoSavedFilters       = 'Keine gespeicherten Filter';
$zmSlangNoStatisticsRecorded = 'Keine Statistik für dieses Ereigniss/Bilder';
$zmSlangOpEq                 = 'gleich zu';
$zmSlangOpGtEq               = 'größer oder gleich wie';
$zmSlangOpGt                 = 'größer als';
$zmSlangOpIn                 = 'in Satz';
$zmSlangOpLtEq               = 'kleiner oder gleich wie';
$zmSlangOpLt                 = 'kleiner als';
$zmSlangOpMatches            = 'zutreffend';
$zmSlangOpNe                 = 'nicht gleich wie';
$zmSlangOpNotIn              = 'nicht im Satz';
$zmSlangOpNotMatches         = 'nicht zutreffend';
$zmSlangOptionHelp           = 'OptionHilfe';
$zmSlangOptionRestartWarning = 'Veränderungen werden erst bei Neustart des Programms aktiv\nFür eine sofortige Änderungen  starten Sie das Programm bitte neu.';
$zmSlangOptions              = 'Optionen';
$zmSlangOrEnterNewName       = 'oder neuen Name eingeben';
$zmSlangOrientation          = 'Ausrichtung';
$zmSlangOverwriteExisting    = 'Überschreibe bestehende';
$zmSlangPaged                = 'Paged';
$zmSlangParameter            = 'Parameter';
$zmSlangPassword             = 'Passwort';
$zmSlangPasswordsDifferent   = 'Die Passwörter sind unterschiedlich';
$zmSlangPaths                = 'Pfade';
$zmSlangPhoneBW              = 'Telefon&nbsp;B/W';
$zmSlangPhone                = 'Phone';
$zmSlangPixels               = 'Punkte';
$zmSlangPlayAll              = 'Play All';
$zmSlangPleaseWait           = 'Bitte Warten';
$zmSlangPostEventImageBuffer = 'Post Ereigniss Bildpuffer';
$zmSlangPreEventImageBuffer  = 'Voriges Ereigniss Bildpuffer';
$zmSlangPrev                 = 'Voriges';
$zmSlangRate                 = 'Rate';
$zmSlangReal                 = 'Real';
$zmSlangRecord               = 'Aufnahme';
$zmSlangRefImageBlendPct     = 'Referenz Bild Blend %ge';
$zmSlangRefresh              = 'Refresh';
$zmSlangRemoteHostName       = 'Remote Host Name';
$zmSlangRemoteHostPath       = 'Remote Host Pfad';
$zmSlangRemoteHostPort       = 'Remote Host Port';
$zmSlangRemoteImageColours   = 'Remote Bildfarbe';
$zmSlangRemote               = 'Remote';
$zmSlangRename               = 'Umbenennen';
$zmSlangReplay               = 'Wiederholung';
$zmSlangResetEventCounts     = 'Lösche Ereignisszähler';
$zmSlangReset                = 'Reset';
$zmSlangRestarting           = 'Neustarten';
$zmSlangRestart              = 'Neustart';
$zmSlangRestrictedCameraIds  = 'Verbotene Kamera Id';
$zmSlangRotateLeft           = 'Drehung Links';
$zmSlangRotateRight          = 'Drehung Rechts';
$zmSlangRunMode              = 'Betriebsmodus';
$zmSlangRunning              = 'In Betrieb';
$zmSlangRunState             = 'Laufender Status';
$zmSlangSaveAs               = 'Speichere als';
$zmSlangSaveFilter           = 'Speichere Filter';
$zmSlangSave                 = 'Speichern';
$zmSlangScale                = 'Skaliere';
$zmSlangScore                = 'Zähler';
$zmSlangSecs                 = 'Sekunden';
$zmSlangSectionlength        = 'Sektions Länge';
$zmSlangSelect               = 'Select';
$zmSlangSetLearnPrefs        = 'Setze Lernmerkmale'; // This can be ignored for now
$zmSlangSetNewBandwidth      = 'Setze Neue Bandbreite';
$zmSlangSettings             = 'Einstellungen';
$zmSlangShowFilterWindow     = 'ZeigeFilterFenster';
$zmSlangSortAsc              = 'Asc';
$zmSlangSortBy               = 'Sort by';
$zmSlangSortDesc             = 'Desc';
$zmSlangSource               = 'Quelle';
$zmSlangSourceType           = 'Quellen Typ';
$zmSlangStart                = 'Start';
$zmSlangState                = 'Status';
$zmSlangStats                = 'Status';
$zmSlangStatus               = 'Status';
$zmSlangStills               = 'Standbilder';
$zmSlangStopped              = 'Gestoppt';
$zmSlangStop                 = 'Stop';
$zmSlangStream               = 'Stream';
$zmSlangSubmit               = 'Submit';
$zmSlangSystem               = 'System';
$zmSlangThumbnail            = 'Thumbnail';
$zmSlangTimeDelta            = 'Zeitdifferenz';
$zmSlangTimestampLabelFormat = 'Zeitstempel Marke Format';
$zmSlangTimestampLabelX      = 'Zeitstempel Marke X';
$zmSlangTimestampLabelY      = 'Zeitstempel Marke Y';
$zmSlangTimestamp            = 'Zeitstempel';
$zmSlangTimeStamp            = 'Zeit Stempel';
$zmSlangTime                 = 'Zeit';
$zmSlangToday                = 'Today';
$zmSlangTools                = 'Tools';
$zmSlangTotalBrScore         = 'Total<br/>Zähler';
$zmSlangTriggers             = 'Auslöser';
$zmSlangType                 = 'Typ';
$zmSlangUnarchive            = 'Nichtarchiviert';
$zmSlangUnits                = 'Einheiten';
$zmSlangUnknown              = 'Unbekannt';
$zmSlangUpdateAvailable      = 'Ein Update fuer ZoneMinder ist verfuehbar';
$zmSlangUpdateNotNecessary   = 'Es ist kein Update verfuegbar';
$zmSlangUseFilter            = 'Benutze  Filter';
$zmSlangUseFilterExprsPost   = '&nbsp;Filter&nbsp;Ausdrücke'; // This is used at the end of the phrase 'use N filter expressions'
$zmSlangUseFilterExprsPre    = 'Benutze&nbsp;'; // This is used at the beginning of the phrase 'use N filter expressions'
$zmSlangUser                 = 'Benutzer';
$zmSlangUsername             = 'Benutzername';
$zmSlangUsers                = 'Benutzer';
$zmSlangValue                = 'Wert';
$zmSlangVersionIgnore        = 'Ignoriere diese Version';
$zmSlangVersionRemindDay     = 'Erinnere mich wieder in 1 Tag';
$zmSlangVersionRemindHour    = 'Erinnere mich wieder in 1 Stunde';
$zmSlangVersionRemindNever   = 'Informiere mich nicht mehr ueber neue Versionen';
$zmSlangVersionRemindWeek    = 'Erinnere mich wieder in 1 Woche';
$zmSlangVersion              = 'Version';
$zmSlangVideoGenFailed       = 'Videoerzeugung fehlgeschlagen!';
$zmSlangVideoGenParms        = 'Videoerzeugung Parameter';
$zmSlangVideoSize            = 'Video Größe';
$zmSlangVideo                = 'Video';
$zmSlangViewAll              = 'Alles Anschauen';
$zmSlangView                 = 'Anschauen';
$zmSlangViewPaged            = 'Seitenansicht';
$zmSlangWarmupFrames         = 'Aufwärm Bilder';
$zmSlangWatch                = 'Beobachte';
$zmSlangWeb                  = 'Web';
$zmSlangWeek                 = 'Woche';
$zmSlangX10ActivationString  = 'X10 Aktivierungs Wert';
$zmSlangX10InputAlarmString  = 'X10 Eingabe Alarm Wert';
$zmSlangX10OutputAlarmString = 'X10 Ausgabe Alarm Wert';
$zmSlangX10                  = 'X10';
$zmSlangYes                  = 'Ja';
$zmSlangYouNoPerms           = 'Keine Erlaubniss zum Zugang dieser Resource.';
$zmSlangZoneAlarmColour      = 'Alarm Farbe (RGB)';
$zmSlangZoneAlarmThreshold   = 'Alarm Schwellwert (0>=?<=255)';
$zmSlangZoneFilterHeight     = 'Filter Höhe (pixels)';
$zmSlangZoneFilterWidth      = 'Filter Breite (pixels)';
$zmSlangZoneMaxAlarmedArea   = 'Maximal überwachte Fläche';
$zmSlangZoneMaxBlobArea      = 'Maximale Gebietsfläche';
$zmSlangZoneMaxBlobs         = 'Maximale Gebietsanzahl';
$zmSlangZoneMaxFilteredArea  = 'Maximal gefilterte Fläche';
$zmSlangZoneMaxPixelThres    = 'Maximaler Pixelschwellwert(0>=?<=255)';
$zmSlangZoneMaxX             = 'Maximum X (rechts)';
$zmSlangZoneMaxY             = 'Maximum Y (unten)';
$zmSlangZoneMinAlarmedArea   = 'Minimal überwachte Fläche';
$zmSlangZoneMinBlobArea      = 'Minimale Gebietsfläche';
$zmSlangZoneMinBlobs         = 'Minimale Gebietsanzahl';
$zmSlangZoneMinFilteredArea  = 'Minimal gefilterte Fläche';
$zmSlangZoneMinPixelThres    = 'Minimaler Pixelschwellwert (0>=?<=255)';
$zmSlangZoneMinX             = 'Minimum X (links)';
$zmSlangZoneMinY             = 'Minimum Y (oben)';
$zmSlangZones                = 'Zonen';
$zmSlangZone                 = 'Zone';

// Complex replacements with formatting and/or placements, must be passed through sprintf
$zmClangCurrentLogin         = 'Momentan angemeldet ist \'%1$s\'';
$zmClangEventCount           = '%1$s %2$s'; // For example '37 Events' (from Vlang below)
$zmClangLastEvents           = 'Letzen %1$s %2$s'; // For example 'Last 37 Events' (from Vlang below)
$zmClangLatestRelease        = 'Die letzte version ist v%1$s,Sie haben v%2$s.';
$zmClangMonitorCount         = '%1$s %2$s'; // For example '4 Monitors' (from Vlang below)
$zmClangMonitorFunction      = 'Monitor %1$s Function';
$zmClangRunningRecentVer     = 'Sie benutzem die meist verbreitete version von Zoneminder, v%s.';

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
$zmVlangEvent                = array( 0=>'Ereigniss', 1=>'Ereigisse', 2=>'Ereignisse' );
$zmVlangMonitor              = array( 0=>'Monitors', 1=>'Monitor', 2=>'Monitors' );

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
