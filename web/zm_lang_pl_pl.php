<?php
//
// ZoneMinder web Polish language file, $Date$, $Revision$
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

// ZoneMinder Polish Translation by Robert Krysztof

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
header( "Content-Type: text/html; charset=iso-8859-2" );

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
// setlocale( 'LC_ALL', 'pl_PL' ); // All locale settings pre-4.3.0
setlocale( LC_ALL, 'pl_PL' );   // All locale settings 4.3.0 and after
// setlocale( LC_CTYPE, 'pl_PL' ); // Character class settings 4.3.0 and after
// setlocale( LC_TIME, 'pl_PL' );  // Date and time formatting 4.3.0 and after

// Simple String Replacements
$zmSlang24BitColour          = 'Kolor (24 bity)';
$zmSlang8BitGrey             = 'Cz/b (8 bitów)';
$zmSlangAction               = 'Action';
$zmSlangActual               = 'Aktualny';
$zmSlangAddNewControl        = 'Add New Control';
$zmSlangAddNewMonitor        = 'Dodaj nowy monitor';
$zmSlangAddNewUser           = 'Dodaj u¿ytkownika';
$zmSlangAddNewZone           = 'Dodaj now± strefê';
$zmSlangAlarm                = 'Alarm';
$zmSlangAlarmBrFrames        = 'Ramki<br/>alarmowe';
$zmSlangAlarmFrameCount      = 'Alarm Frame Count';
$zmSlangAlarmFrame           = 'Ramka alarmowa';
$zmSlangAlarmLimits          = 'Ograniczenia alarmu';
$zmSlangAlarmMaximumFPS      = 'Alarm Maximum FPS';
$zmSlangAlarmPx              = 'Alarm Px';
$zmSlangAlarmRGBUnset        = 'You must set an alarm RGB colour';
$zmSlangAlert                = 'Gotowosc';
$zmSlangAll                  = 'Wszystko';
$zmSlangApplyingStateChange  = 'Zmieniam stan pracy';
$zmSlangApply                = 'Zastosuj';
$zmSlangArchArchived         = 'Tylko zarchiwizowane';
$zmSlangArchive              = 'Archiwum';
$zmSlangArchived             = 'Archived';
$zmSlangArchUnarchived       = 'Tylko niezarchiwizowane';
$zmSlangArea                 = 'Area';
$zmSlangAreaUnits            = 'Area (px/%)';
$zmSlangAttrAlarmFrames      = 'Ramki alarmowe';
$zmSlangAttrArchiveStatus    = 'Status archiwum';
$zmSlangAttrAvgScore         = '¦red. wynik';
$zmSlangAttrCause            = 'Cause';
$zmSlangAttrDate             = 'Data';
$zmSlangAttrDateTime         = 'Data/Czas';
$zmSlangAttrDiskBlocks       = 'Dysk Bloki';
$zmSlangAttrDiskPercent      = 'Dysk Procent';
$zmSlangAttrDuration         = 'Czas trwania';
$zmSlangAttrFrames           = 'Ramek';
$zmSlangAttrId               = 'Id';
$zmSlangAttrMaxScore         = 'Maks. wynik';
$zmSlangAttrMonitorId        = 'Nr monitora';
$zmSlangAttrMonitorName      = 'Nazwa monitora';
$zmSlangAttrMontage          = 'Monta¿';
$zmSlangAttrName             = 'Nazwa';
$zmSlangAttrNotes            = 'Notes';
$zmSlangAttrTime             = 'Czas';
$zmSlangAttrTotalScore       = 'Ca³kowity wynik';
$zmSlangAttrWeekday          = 'Dzieñ roboczy';
$zmSlangArchiveEvents        = 'Archiwizuj wszystkie pasuj±ce';
$zmSlangAuto                 = 'Auto';
$zmSlangAutoStopTimeout      = 'Auto Stop Timeout';
$zmSlangAvgBrScore           = '¦red.<br/>wynik';
$zmSlangBackground           = 'Background';
$zmSlangBackgroundFilter     = 'Run filter in background';
$zmSlangBadAlarmFrameCount   = 'Alarm frame count must be an integer of one or more';
$zmSlangBadAlarmMaxFPS       = 'Alarm Maximum FPS must be a positive integer or floating point value';
$zmSlangBadChannel           = 'Channel must be set to an integer of zero or more';
$zmSlangBadDevice            = 'Device must be set to a valid value';
$zmSlangBadFormat            = 'Format must be set to an integer of zero or more';
$zmSlangBadFPSReportInterval = 'FPS report interval buffer count must be an integer of 100 or more';
$zmSlangBadFrameSkip         = 'Frame skip count must be an integer of zero or more';
$zmSlangBadHeight            = 'Height must be set to a valid value';
$zmSlangBadHost              = 'Host must be set to a valid ip address or hostname, do not include http://';
$zmSlangBadImageBufferCount  = 'Image buffer size must be an integer of 10 or more';
$zmSlangBadLabelX            = 'Label X co-ordinate must be set to an integer of zero or more';
$zmSlangBadLabelY            = 'Label Y co-ordinate must be set to an integer of zero or more';
$zmSlangBadMaxFPS            = 'Maximum FPS must be a positive integer or floating point value';
$zmSlangBadNameChars         = 'Nazwy mog± zawieraæ tylko litery, cyfry oraz my¶lnik i podkre¶lenie';
$zmSlangBadPath              = 'Path must be set to a valid value';
$zmSlangBadPort              = 'Port must be set to a valid number';
$zmSlangBadPostEventCount    = 'Post event image buffer must be an integer of zero or more';
$zmSlangBadPreEventCount     = 'Pre event image buffer must be at least zero, and less than image buffer size';
$zmSlangBadRefBlendPerc      = 'Reference blendpercentage must be a positive integer';
$zmSlangBadSectionLength     = 'Section length must be an integer of 30 or more';
$zmSlangBadWarmupCount       = 'Warmup frames must be an integer of zero or more';
$zmSlangBadWebColour         = 'Web colour must be a valid web colour string';
$zmSlangBadWidth             = 'Width must be set to a valid value';
$zmSlangBandwidth            = 'przepustowo¶æ';
$zmSlangBlobPx               = 'Plamka Px';
$zmSlangBlobSizes            = 'Rozmiary plamek';
$zmSlangBlobs                = 'Plamki';
$zmSlangBrightness           = 'Jaskrawo¶æ';
$zmSlangBuffers              = 'Bufory';
$zmSlangCanAutoFocus         = 'Can Auto Focus';
$zmSlangCanAutoGain          = 'Can Auto Gain';
$zmSlangCanAutoIris          = 'Can Auto Iris';
$zmSlangCanAutoWhite         = 'Can Auto White Bal.';
$zmSlangCanAutoZoom          = 'Can Auto Zoom';
$zmSlangCancel               = 'Anuluj';
$zmSlangCancelForcedAlarm    = 'Anuluj&nbsp;wymuszony&nbsp;alarm';
$zmSlangCanFocusAbs          = 'Can Focus Absolute';
$zmSlangCanFocus             = 'Can Focus';
$zmSlangCanFocusCon          = 'Can Focus Continuous';
$zmSlangCanFocusRel          = 'Can Focus Relative';
$zmSlangCanGainAbs           = 'Can Gain Absolute';
$zmSlangCanGain              = 'Can Gain ';
$zmSlangCanGainCon           = 'Can Gain Continuous';
$zmSlangCanGainRel           = 'Can Gain Relative';
$zmSlangCanIrisAbs           = 'Can Iris Absolute';
$zmSlangCanIris              = 'Can Iris';
$zmSlangCanIrisCon           = 'Can Iris Continuous';
$zmSlangCanIrisRel           = 'Can Iris Relative';
$zmSlangCanMoveAbs           = 'Can Move Absolute';
$zmSlangCanMove              = 'Can Move';
$zmSlangCanMoveCon           = 'Can Move Continuous';
$zmSlangCanMoveDiag          = 'Can Move Diagonally';
$zmSlangCanMoveMap           = 'Can Move Mapped';
$zmSlangCanMoveRel           = 'Can Move Relative';
$zmSlangCanPan               = 'Can Pan' ;
$zmSlangCanReset             = 'Can Reset';
$zmSlangCanSetPresets        = 'Can Set Presets';
$zmSlangCanSleep             = 'Can Sleep';
$zmSlangCanTilt              = 'Can Tilt';
$zmSlangCanWake              = 'Can Wake';
$zmSlangCanWhiteAbs          = 'Can White Bal. Absolute';
$zmSlangCanWhiteBal          = 'Can White Bal.';
$zmSlangCanWhite             = 'Can White Balance';
$zmSlangCanWhiteCon          = 'Can White Bal. Continuous';
$zmSlangCanWhiteRel          = 'Can White Bal. Relative';
$zmSlangCanZoomAbs           = 'Can Zoom Absolute';
$zmSlangCanZoom              = 'Can Zoom';
$zmSlangCanZoomCon           = 'Can Zoom Continuous';
$zmSlangCanZoomRel           = 'Can Zoom Relative';
$zmSlangCaptureHeight        = 'Wysoko¶æ obrazu';
$zmSlangCapturePalette       = 'Paleta kolorów obrazu';
$zmSlangCaptureWidth         = 'Szeroko¶æ obrazu';
$zmSlangCause                = 'Cause';
$zmSlangCheckMethod          = 'Metoda sprawdzenia alarmu';
$zmSlangChooseFilter         = 'Wybierz filtr';
$zmSlangChoosePreset         = 'Choose Preset';
$zmSlangClose                = 'Zamknij';
$zmSlangColour               = 'Nasycenie';
$zmSlangCommand              = 'Command';
$zmSlangConfig               = 'Konfiguracja';
$zmSlangConfiguredFor        = 'Ustawiona';
$zmSlangConfirmDeleteEvents  = 'Are you sure you wish to delete the selected events?';
$zmSlangConfirmPassword      = 'Potwierd¼ has³o';
$zmSlangConjAnd              = 'i';
$zmSlangConjOr               = 'lub';
$zmSlangConsole              = 'Konsola';
$zmSlangContactAdmin         = 'Skontaktuj siê z Twoim adminstratorem w sprawie szczegó³ów.';
$zmSlangContinue             = 'Continue';
$zmSlangContrast             = 'Kontrast';
$zmSlangControlAddress       = 'Control Address';
$zmSlangControlCap           = 'Control Capability';
$zmSlangControlCaps          = 'Control Capabilities';
$zmSlangControl              = 'Control';
$zmSlangControlDevice        = 'Control Device';
$zmSlangControllable         = 'Controllable';
$zmSlangControlType          = 'Control Type';
$zmSlangCycle                = 'Cycle';
$zmSlangCycleWatch           = 'Cykl podgl±du';
$zmSlangDay                  = 'Dzieñ';
$zmSlangDebug                = 'Debug';
$zmSlangDefaultRate          = 'Default Rate';
$zmSlangDefaultScale         = 'Default Scale';
$zmSlangDefaultView          = 'Default View';
$zmSlangDeleteAndNext        = 'Usuñ &amp; nastêpny';
$zmSlangDeleteAndPrev        = 'Usuñ &amp; poprzedni';
$zmSlangDeleteEvents         = 'Usuwaj wszystkie pasuj±ce';
$zmSlangDeleteSavedFilter    = 'Usuñ zapisany filtr';
$zmSlangDelete               = 'Usuñ';
$zmSlangDescription          = 'Opis';
$zmSlangDeviceChannel        = 'Numer wej¶cia w urz±dzeniu';
$zmSlangDeviceFormat         = 'System TV';
$zmSlangDeviceNumber         = 'Numer urz±dzenia';
$zmSlangDevicePath           = 'Device Path';
$zmSlangDevices              = 'Devices';
$zmSlangDimensions           = 'Rozmiary';
$zmSlangDisableAlarms        = 'Disable Alarms';
$zmSlangDisk                 = 'Dysk';
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
$zmSlangDuration             = 'Czas trwania';
$zmSlangEdit                 = 'Edycja';
$zmSlangEmail                = 'Email';
$zmSlangEmailEvents          = 'Wysy³aj poczt± wszystkie pasuj±ce';
$zmSlangEnableAlarms         = 'Enable Alarms';
$zmSlangEnabled              = 'Zezwolono';
$zmSlangEnterNewFilterName   = 'Wpisz now± nazwê filtra';
$zmSlangError                = 'B³±d';
$zmSlangErrorBrackets        = 'B³±d, proszê sprawdziæ ilo¶æ nawiasów otwieraj±cych i zamykaj±cych';
$zmSlangErrorValidValue      = 'B³±d, proszê sprawdziæ czy wszystkie warunki maj± poprawne warto¶ci';
$zmSlangEtc                  = 'itp';
$zmSlangEventFilter          = 'Filtr zdarzeñ';
$zmSlangEventId              = 'Id zdarzenia';
$zmSlangEventName            = 'Event Name';
$zmSlangEventPrefix          = 'Event Prefix';
$zmSlangEvents               = 'Zdarzenia';
$zmSlangEvent                = 'Zdarzenie';
$zmSlangExclude              = 'Wyklucz';
$zmSlangExecuteEvents        = 'Wywo³uj komendê na wszystkie pasuj±ce';
$zmSlangExecute              = 'Execute';
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
$zmSlangFar                  = 'Far';
$zmSlangFeed                 = 'Dostarcz';
$zmSlangFileColours          = 'File Colours';
$zmSlangFile                 = 'File';
$zmSlangFilePath             = 'File Path';
$zmSlangFilterPx             = 'Filtr Px';
$zmSlangFilters              = 'Filters';
$zmSlangFilterUnset          = 'You must specify a filter width and height';
$zmSlangFirst                = 'Pierwszy';
$zmSlangFlippedHori          = 'Flipped Horizontally';
$zmSlangFlippedVert          = 'Flipped Vertically';
$zmSlangFocus                = 'Focus';
$zmSlangForceAlarm           = 'Wymu¶&nbsp;alarm';
$zmSlangFormat               = 'Format';
$zmSlangFPS                  = 'fps';
$zmSlangFPSReportInterval    = 'Raport (ramek/s)';
$zmSlangFrameId              = 'Nr ramki';
$zmSlangFrame                = 'Ramka';
$zmSlangFrameRate            = 'Tempo ramek';
$zmSlangFrameSkip            = 'Pomiñ ramkê';
$zmSlangFrames               = 'Ramek';
$zmSlangFTP                  = 'FTP';
$zmSlangFunc                 = 'Funkcja';
$zmSlangFunction             = 'Funkcja';
$zmSlangGain                 = 'Gain';
$zmSlangGeneral              = 'General';
$zmSlangGenerateVideo        = 'Generowanie Video';
$zmSlangGeneratingVideo      = 'Generujê Video';
$zmSlangGoToZoneMinder       = 'Przejd¼ na ZoneMinder.com';
$zmSlangGrey                 = 'Cz/b';
$zmSlangGroup                = 'Group';
$zmSlangGroups               = 'Groups';
$zmSlangHasFocusSpeed        = 'Has Focus Speed';
$zmSlangHasGainSpeed         = 'Has Gain Speed';
$zmSlangHasHomePreset        = 'Has Home Preset';
$zmSlangHasIrisSpeed         = 'Has Iris Speed';
$zmSlangHasPanSpeed          = 'Has Pan Speed';
$zmSlangHasPresets           = 'Has Presets';
$zmSlangHasTiltSpeed         = 'Has Tilt Speed';
$zmSlangHasTurboPan          = 'Has Turbo Pan';
$zmSlangHasTurboTilt         = 'Has Turbo Tilt';
$zmSlangHasWhiteSpeed        = 'Has White Bal. Speed';
$zmSlangHasZoomSpeed         = 'Has Zoom Speed';
$zmSlangHighBW               = 'Wys.&nbsp;prz.';
$zmSlangHigh                 = 'wysoka';
$zmSlangHome                 = 'Home';
$zmSlangHour                 = 'Godzina';
$zmSlangHue                  = 'Odcieñ';
$zmSlangIdle                 = 'Bezczynny';
$zmSlangId                   = 'Nr';
$zmSlangIgnore               = 'Ignoruj';
$zmSlangImageBufferSize      = 'Rozmiar bufora obrazu (ramek)';
$zmSlangImage                = 'Obraz';
$zmSlangImages               = 'Images';
$zmSlangInclude              = 'Do³±cz';
$zmSlangIn                   = 'In';
$zmSlangInverted             = 'Odwrócony';
$zmSlangIris                 = 'Iris';
$zmSlangKeyString            = 'Key String';
$zmSlangLabel                = 'Label';
$zmSlangLanguage             = 'Jêzyk';
$zmSlangLast                 = 'Ostatni';
$zmSlangLimitResultsPost     = 'wyników;'; // This is used at the end of the phrase 'Limit to first N results only'
$zmSlangLimitResultsPre      = 'Ogranicz do pocz±tkowych'; // This is used at the beginning of the phrase 'Limit to first N results only'
$zmSlangLinkedMonitors       = 'Linked Monitors';
$zmSlangList                 = 'List';
$zmSlangLoad                 = 'Obc.';
$zmSlangLocal                = 'Lokalny';
$zmSlangLoggedInAs           = 'Zalogowany jako';
$zmSlangLoggingIn            = 'Logowanie';
$zmSlangLogin                = 'Login';
$zmSlangLogout               = 'Wyloguj';
$zmSlangLowBW                = 'Nis.&nbsp;prz.';
$zmSlangLow                  = 'niska';
$zmSlangMain                 = 'Main';
$zmSlangMan                  = 'Man';
$zmSlangManual               = 'Manual';
$zmSlangMark                 = 'Znacznik';
$zmSlangMaxBandwidth         = 'Max Bandwidth';
$zmSlangMaxBrScore           = 'Maks.<br/>wynik';
$zmSlangMaxFocusRange        = 'Max Focus Range';
$zmSlangMaxFocusSpeed        = 'Max Focus Speed';
$zmSlangMaxFocusStep         = 'Max Focus Step';
$zmSlangMaxGainRange         = 'Max Gain Range';
$zmSlangMaxGainSpeed         = 'Max Gain Speed';
$zmSlangMaxGainStep          = 'Max Gain Step';
$zmSlangMaximumFPS           = 'Maks. FPS';
$zmSlangMaxIrisRange         = 'Max Iris Range';
$zmSlangMaxIrisSpeed         = 'Max Iris Speed';
$zmSlangMaxIrisStep          = 'Max Iris Step';
$zmSlangMax                  = 'Maks.';
$zmSlangMaxPanRange          = 'Max Pan Range';
$zmSlangMaxPanSpeed          = 'Max Pan Speed';
$zmSlangMaxPanStep           = 'Max Pan Step';
$zmSlangMaxTiltRange         = 'Max Tilt Range';
$zmSlangMaxTiltSpeed         = 'Max Tilt Speed';
$zmSlangMaxTiltStep          = 'Max Tilt Step';
$zmSlangMaxWhiteRange        = 'Max White Bal. Range';
$zmSlangMaxWhiteSpeed        = 'Max White Bal. Speed';
$zmSlangMaxWhiteStep         = 'Max White Bal. Step';
$zmSlangMaxZoomRange         = 'Max Zoom Range';
$zmSlangMaxZoomSpeed         = 'Max Zoom Speed';
$zmSlangMaxZoomStep          = 'Max Zoom Step';
$zmSlangMediumBW             = '¦red.&nbsp;prz.';
$zmSlangMedium               = '¶rednia';
$zmSlangMessageEvents        = 'Wy¶wietlaj komunikat na wszystkie pasuj±ce';
$zmSlangMinAlarmAreaLtMax    = 'Minimum alarm area should be less than maximum';
$zmSlangMinAlarmAreaUnset    = 'You must specify the minimum alarm pixel count';
$zmSlangMinBlobAreaLtMax     = 'Minimalny obszar plamki powinien byæ mniejszy od maksymalnego obszaru plamki';
$zmSlangMinBlobAreaUnset     = 'You must specify the minimum blob pixel count';
$zmSlangMinBlobLtMinFilter   = 'Minimum blob area should be less than or equal to minimum filter area';
$zmSlangMinBlobsLtMax        = 'Najmniejsze plamki powinny byæ mniejsze od najwiêkszych plamek' ;
$zmSlangMinBlobsUnset        = 'You must specify the minimum blob count';
$zmSlangMinFilterAreaLtMax   = 'Minimum filter area should be less than maximum';
$zmSlangMinFilterAreaUnset   = 'You must specify the minimum filter pixel count';
$zmSlangMinFilterLtMinAlarm  = 'Minimum filter area should be less than or equal to minimum alarm area';
$zmSlangMinFocusRange        = 'Min Focus Range';
$zmSlangMinFocusSpeed        = 'Min Focus Speed';
$zmSlangMinFocusStep         = 'Min Focus Step';
$zmSlangMinGainRange         = 'Min Gain Range';
$zmSlangMinGainSpeed         = 'Min Gain Speed';
$zmSlangMinGainStep          = 'Min Gain Step';
$zmSlangMinIrisRange         = 'Min Iris Range';
$zmSlangMinIrisSpeed         = 'Min Iris Speed';
$zmSlangMinIrisStep          = 'Min Iris Step';
$zmSlangMinPanRange          = 'Min Pan Range';
$zmSlangMinPanSpeed          = 'Min Pan Speed';
$zmSlangMinPanStep           = 'Min Pan Step';
$zmSlangMinPixelThresLtMax   = 'Najmniejsze progi pikseli powinny byæ mniejsze od najwiêkszych progów pikseli';
$zmSlangMinPixelThresUnset   = 'You must specify a minimum pixel threshold';
$zmSlangMinTiltRange         = 'Min Tilt Range';
$zmSlangMinTiltSpeed         = 'Min Tilt Speed';
$zmSlangMinTiltStep          = 'Min Tilt Step';
$zmSlangMinWhiteRange        = 'Min White Bal. Range';
$zmSlangMinWhiteSpeed        = 'Min White Bal. Speed';
$zmSlangMinWhiteStep         = 'Min White Bal. Step';
$zmSlangMinZoomRange         = 'Min Zoom Range';
$zmSlangMinZoomSpeed         = 'Min Zoom Speed';
$zmSlangMinZoomStep          = 'Min Zoom Step';
$zmSlangMisc                 = 'Inne';
$zmSlangMonitorIds           = 'Numery&nbsp;monitorów';
$zmSlangMonitor              = 'Monitor';
$zmSlangMonitorPresetIntro   = 'Select an appropriate preset from the list below.<br><br>Please note that this may overwrite any values you already have configured for this monitor.<br><br>';
$zmSlangMonitorPreset        = 'Monitor Preset';
$zmSlangMonitors             = 'Monitory';
$zmSlangMontage              = 'Monta¿';
$zmSlangMonth                = 'Miesi±c';
$zmSlangMove                 = 'Move';
$zmSlangMustBeGe             = 'musi byæ wiêksze lub równe od';
$zmSlangMustBeLe             = 'musi byæ mniejsze lub równe od';
$zmSlangMustConfirmPassword  = 'Musisz potwierdziæ has³o';
$zmSlangMustSupplyPassword   = 'Musisz podaæ has³o';
$zmSlangMustSupplyUsername   = 'Musisz podaæ nazwê u¿ytkownika';
$zmSlangName                 = 'Nazwa';
$zmSlangNear                 = 'Near';
$zmSlangNetwork              = 'Sieæ';
$zmSlangNewGroup             = 'New Group';
$zmSlangNewLabel             = 'New Label';
$zmSlangNew                  = 'Nowy';
$zmSlangNewPassword          = 'Nowe has³o';
$zmSlangNewState             = 'Nowy stan';
$zmSlangNewUser              = 'nowy';
$zmSlangNext                 = 'Nastêpny';
$zmSlangNoFramesRecorded     = 'Brak zapisanych ramek dla tego zdarzenia';
$zmSlangNoGroup              = 'No Group';
$zmSlangNoneAvailable        = 'Niedostêpne';
$zmSlangNone                 = 'Brak';
$zmSlangNo                   = 'Nie';
$zmSlangNormal               = 'Normalny';
$zmSlangNoSavedFilters       = 'BrakZapisanychFiltrów';
$zmSlangNoStatisticsRecorded = 'Brak zapisanych statystyk dla tego zdarzenia/ramki';
$zmSlangNotes                = 'Notes';
$zmSlangNumPresets           = 'Num Presets';
$zmSlangOff                  = 'Off';
$zmSlangOn                   = 'On';
$zmSlangOpen                 = 'Open';
$zmSlangOpEq                 = 'równy';
$zmSlangOpGtEq               = 'wiêksze lub równe od';
$zmSlangOpGt                 = 'wiêksze od';
$zmSlangOpIn                 = 'w zestawie';
$zmSlangOpLtEq               = 'mniejsze lub równe od';
$zmSlangOpLt                 = 'mniejsze od';
$zmSlangOpMatches            = 'pasuj±ce';
$zmSlangOpNe                 = 'ró¿ne od';
$zmSlangOpNotIn              = 'brak w zestawie';
$zmSlangOpNotMatches         = 'nie pasuj±ce';
$zmSlangOptionHelp           = 'OpcjePomoc';
$zmSlangOptionRestartWarning = 'Te zmiany nie przynios± natychmiastowego efektu\ndopóki system pracuje. Kiedy zakoñczysz robiæ zmiany\nproszê koniecznie zrestartowaæ ZoneMinder.';
$zmSlangOptions              = 'Opcje';
$zmSlangOrder                = 'Order';
$zmSlangOrEnterNewName       = 'lub wpisz now± nazwê';
$zmSlangOrientation          = 'Orientacja';
$zmSlangOut                  = 'Out';
$zmSlangOverwriteExisting    = 'Nadpisz istniej±ce';
$zmSlangPaged                = 'Stronicowane';
$zmSlangPanLeft              = 'Pan Left';
$zmSlangPan                  = 'Pan';
$zmSlangPanRight             = 'Pan Right';
$zmSlangPanTilt              = 'Pan/Tilt';
$zmSlangParameter            = 'Parametr';
$zmSlangPassword             = 'Has³o';
$zmSlangPasswordsDifferent   = 'Has³a: nowe i potwierdzone s± ró¿ne!';
$zmSlangPaths                = '¦cie¿ki';
$zmSlangPhoneBW              = 'Tel.&nbsp;prz.';
$zmSlangPhone                = 'Phone';
$zmSlangPixelDiff            = 'Pixel Diff';
$zmSlangPixels               = 'pikseli';
$zmSlangPlayAll              = 'Play All';
$zmSlangPleaseWait           = 'Proszê czekaæ';
$zmSlangPoint                = 'Point';
$zmSlangPostEventImageBuffer = 'Bufor obrazów po zdarzeniu';
$zmSlangPreEventImageBuffer  = 'Bufor obrazów przed zdarzeniem';
$zmSlangPreset               = 'Preset';
$zmSlangPresets              = 'Presets';
$zmSlangPrev                 = 'Poprzedni';
$zmSlangRate                 = 'Tempo';
$zmSlangReal                 = 'Rzeczywiste';
$zmSlangRecord               = 'Zapis';
$zmSlangRefImageBlendPct     = 'Miks z obrazem odniesienia';
$zmSlangRefresh              = 'Od¶wie¿';
$zmSlangRemoteHostName       = 'Nazwa zdalnego hosta';
$zmSlangRemoteHostPath       = 'Scie¿ka zdalnego hosta';
$zmSlangRemoteHostPort       = 'Port zdalnego hosta';
$zmSlangRemoteImageColours   = 'Kolory zdalnego obrazu';
$zmSlangRemote               = 'Zdalny';
$zmSlangRename               = 'Zmieñ nazwê';
$zmSlangReplay               = 'Powtórka';
$zmSlangResetEventCounts     = 'Kasuj licznik zdarzeñ';
$zmSlangReset                = 'Reset';
$zmSlangRestarting           = 'Restartujê';
$zmSlangRestart              = 'Restart';
$zmSlangRestrictedCameraIds  = 'Numery kamer';
$zmSlangRestrictedMonitors   = 'Restricted Monitors';
$zmSlangReturnDelay          = 'Return Delay';
$zmSlangReturnLocation       = 'Return Location';
$zmSlangRotateLeft           = 'Obróæ w lewo';
$zmSlangRotateRight          = 'Obróæ w prawo';
$zmSlangRunMode              = 'Tryb pracy';
$zmSlangRunning              = 'Pracuje';
$zmSlangRunState             = 'Stan pracy';
$zmSlangSaveAs               = 'Zapisz jako';
$zmSlangSaveFilter           = 'Zapisz filtr';
$zmSlangSave                 = 'Zapisz';
$zmSlangScale                = 'Skala';
$zmSlangScore                = 'Wynik';
$zmSlangSecs                 = 'Sekund';
$zmSlangSectionlength        = 'D³ugo¶æ sekcji';
$zmSlangSelectMonitors       = 'Select Monitors';
$zmSlangSelect               = 'Select';
$zmSlangSelfIntersecting     = 'Polygon edges must not intersect';
$zmSlangSetLearnPrefs        = 'Ustaw preferencje nauki'; // This can be ignored for now
$zmSlangSetNewBandwidth      = 'Ustaw now± przepustowo¶æ';
$zmSlangSetPreset            = 'Set Preset';
$zmSlangSet                  = 'Set';
$zmSlangSettings             = 'Ustawienia';
$zmSlangShowFilterWindow     = 'Poka¿OknoFiltru';
$zmSlangShowTimeline         = 'Show Timeline';
$zmSlangSize                 = 'Size';
$zmSlangSleep                = 'Sleep';
$zmSlangSortAsc              = 'Nara.';
$zmSlangSortBy               = 'Sortuj';
$zmSlangSortDesc             = 'Opad.';
$zmSlangSource               = '¬ród³o';
$zmSlangSourceType           = 'Typ ¼ród³a';
$zmSlangSpeedHigh            = 'High Speed';
$zmSlangSpeedLow             = 'Low Speed';
$zmSlangSpeedMedium          = 'Medium Speed';
$zmSlangSpeed                = 'Speed';
$zmSlangSpeedTurbo           = 'Turbo Speed';
$zmSlangStart                = 'Start';
$zmSlangState                = 'Stan';
$zmSlangStats                = 'Statystyki';
$zmSlangStatus               = 'Status';
$zmSlangStepLarge            = 'Large Step';
$zmSlangStepMedium           = 'Medium Step';
$zmSlangStepNone             = 'No Step';
$zmSlangStepSmall            = 'Small Step';
$zmSlangStep                 = 'Step';
$zmSlangStills               = 'Nieruchome';
$zmSlangStopped              = 'Zatrzymany';
$zmSlangStop                 = 'Stop';
$zmSlangStream               = 'Ruchomy';
$zmSlangSubmit               = 'Submit';
$zmSlangSystem               = 'System';
$zmSlangTele                 = 'Tele';
$zmSlangThumbnail            = 'Thumbnail';
$zmSlangTilt                 = 'Tilt';
$zmSlangTime                 = 'Czas';
$zmSlangTimeDelta            = 'Ró¿nica czasu';
$zmSlangTimeline             = 'Timeline';
$zmSlangTimestamp            = 'Czas';
$zmSlangTimestampLabelFormat = 'Format etykiety czasu';
$zmSlangTimestampLabelX      = 'Wsp. X etykiety czasu';
$zmSlangTimestampLabelY      = 'Wsp. Y etykiety czasu';
$zmSlangTimeStamp            = 'Pieczêæ czasu';
$zmSlangToday                = 'Today';
$zmSlangTools                = 'Narzêdzia';
$zmSlangTotalBrScore         = 'Ca³kowity<br/>wynik';
$zmSlangTrackDelay           = 'Track Delay';
$zmSlangTrackMotion          = 'Track Motion';
$zmSlangTriggers             = 'Wyzwalacze';
$zmSlangTurboPanSpeed        = 'Turbo Pan Speed';
$zmSlangTurboTiltSpeed       = 'Turbo Tilt Speed';
$zmSlangType                 = 'Typ';
$zmSlangUnarchive            = 'Nie archiwizuj';
$zmSlangUnits                = 'Jednostki';
$zmSlangUnknown              = 'Nieznany';
$zmSlangUpdateAvailable      = 'Jest dostêpne uaktualnienie ZoneMinder ';
$zmSlangUpdateNotNecessary   = 'Nie jest wymagane uaktualnienie';
$zmSlangUpdate               = 'Update';
$zmSlangUploadEvents         = 'Wysy³aj wszystkie pasuj±ce';
$zmSlangUseFilterExprsPost   = '&nbsp;wyra¿enie&nbsp;filtru'; // This is used at the end of the phrase 'use N filter expressions'
$zmSlangUseFilterExprsPre    = 'U¿yj&nbsp;'; // This is used at the beginning of the phrase 'use N filter expressions'
$zmSlangUseFilter            = 'U¿yj filtru';
$zmSlangUsername             = 'Nazwa u¿ytkownika';
$zmSlangUsers                = 'U¿ytkownicy';
$zmSlangUser                 = 'U¿ytkownik';
$zmSlangValue                = 'Warto¶æ';
$zmSlangVersionIgnore        = 'Zignoruj t± wersjê';
$zmSlangVersionRemindDay     = 'Przypomnij po 1 dniu';
$zmSlangVersionRemindHour    = 'Przypomnij po 1 godzinie';
$zmSlangVersionRemindNever   = 'Nie przypominaj o nowych wersjach';
$zmSlangVersionRemindWeek    = 'Przypomnij po 1 tygodniu';
$zmSlangVersion              = 'Wersja';
$zmSlangVideoEvents          = 'Create video for all matches';
$zmSlangVideoFormat          = 'Video Format';
$zmSlangVideoGenFailed       = 'Generowanie filmu Video nie powiod³o siê!';
$zmSlangVideoGenFiles        = 'Existing Video Files';
$zmSlangVideoGenNoFiles      = 'No Video Files Found';
$zmSlangVideoGenParms        = 'Parametery generowania filmu Video';
$zmSlangVideoGenSucceeded    = 'Video Generation Succeeded!';
$zmSlangVideoSize            = 'Rozmiar filmu Video';
$zmSlangVideo                = 'Video';
$zmSlangViewAll              = 'Poka¿ wszystko';
$zmSlangViewEvent            = 'View Event';
$zmSlangViewPaged            = 'Poka¿ stronami';
$zmSlangView                 = 'Podgl±d';
$zmSlangWake                 = 'Wake';
$zmSlangWarmupFrames         = 'Ignorowane ramki';
$zmSlangWatch                = 'podgl±d';
$zmSlangWebColour            = 'Web Colour';
$zmSlangWeb                  = 'Web';
$zmSlangWeek                 = 'Tydzieñ';
$zmSlangWhiteBalance         = 'White Balance';
$zmSlangWhite                = 'White';
$zmSlangWide                 = 'Wide';
$zmSlangX10ActivationString  = 'X10: ³añcuch aktywuj±cy';
$zmSlangX10InputAlarmString  = 'X10: ³añcuch wej¶cia alarmu';
$zmSlangX10OutputAlarmString = 'X10: ³añcuch wyj¶cia alarmu';
$zmSlangX10                  = 'X10';
$zmSlangX                    = 'X';
$zmSlangYes                  = 'Tak';
$zmSlangYouNoPerms           = 'Nie masz uprawnieñ na dostêp do tego zasobu.';
$zmSlangY                    = 'Y';
$zmSlangZoneAlarmColour      = 'Kolor alarmu (Red/Green/Blue)';
$zmSlangZoneAlarmThreshold   = 'Próg alarmu (0-255)';
$zmSlangZoneArea             = 'Zone Area';
$zmSlangZoneFilterSize       = 'Filter Width/Height (pixels)';
$zmSlangZoneMinMaxAlarmArea  = 'Min/Max Alarmed Area';
$zmSlangZoneMinMaxBlobArea   = 'Min/Max Blob Area';
$zmSlangZoneMinMaxBlobs      = 'Min/Max Blobs';
$zmSlangZoneMinMaxFiltArea   = 'Min/Max Filtered Area';
$zmSlangZoneMinMaxPixelThres = 'Min/Max Pixel Threshold (0-255)';
$zmSlangZones                = 'Strefy';
$zmSlangZone                 = 'Strefa';
$zmSlangZoomIn               = 'Zoom In';
$zmSlangZoomOut              = 'Zoom Out';
$zmSlangZoom                 = 'Zoom';


// Complex replacements with formatting and/or placements, must be passed through sprintf
$zmClangCurrentLogin         = 'Aktualny login \'%1$s\'';
$zmClangEventCount           = '%1$s %2$s';
$zmClangLastEvents           = 'Ostatnie %1$s %2$s';
$zmClangLatestRelease        = 'Najnowsza wersja to v%1$s, Ty posiadasz v%2$s.';
$zmClangMonitorCount         = '%1$s %2$s';
$zmClangMonitorFunction      = 'Monitor %1$s Funkcja';
$zmClangRunningRecentVer     = 'Uruchomi³e¶ najnowsz± wersjê ZoneMinder, v%s.';

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
$zmVlangEvent                = array( 0=>'Zdarzeñ', 1=>'Zdarzenie', 2=>'Zdarzenia');
$zmVlangMonitor              = array( 0=>'Monitorów', 1=>'Monitor', 2=>'Monitory');

// You will need to choose or write a function that can correlate the plurality string arrays
// with variable counts. This is used to conjugate the Vlang arrays above with a number passed
// in to generate the correct noun form.

// This is an version that could be used in the Polish language
// 
function zmVlang( $lang_var_array, $count )
{
 	$secondlastdigit = substr( $count, -2, 1 );
 	$lastdigit = substr( $count, -1, 1 );
 	if ( $count == 1 )
	{
		return( $lang_var_array[1] );
	}
 	if (($secondlastdigit == 0)|( $secondlastdigit == 1))
 	{
 		return( $lang_var_array[0] );
 	}
 	if ( $secondlastdigit >= 2)
	{
 		switch ( $lastdigit )
 		{
 			case 0 :
	 		case 1 :
 			case 5 :
 			case 6 :
	 		case 7 :
 			case 8 :
 			case 9 :
	 		{
 				return( $lang_var_array[0] );
 				break;
	 		}
 			case 2 :
 			case 3 :
	 		case 4 :
 			{
 				return( $lang_var_array[2] );
	 			break;
 			}
	 	}
	}
 	die( 'B£¡D! zmVlang nie mo¿e skorelowac ³añcucha!' );
}

// This is an example of how the function is used in the code which you can uncomment and 
// use to test your custom function.
// $monitors = 12; // Choose any number
// echo $monitors." ";
// echo zmVlang( $zmVlangMonitor, $monitors);

// In this section you can override the default prompt and help texts for the options area
// These overrides are in the form of $zmOlangPrompt<option> and $zmOlangHelp<option>
// where <option> represents the option name minus the initial ZM_
// So for example, to override the help text for ZM_LANG_DEFAULT do
// $zmOlangPromptLANG_DEFAULT = "This is a new prompt for this option";
// $zmOlangHelpLANG_DEFAULT = "This is some new help for this option which will be displayed in the popup window when the ? is clicked";
//

?>
