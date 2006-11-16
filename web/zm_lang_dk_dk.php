<?php
//
// ZoneMinder web Danish language file, $Date$, $Revision$
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

// ZoneMinder Danish Translation by Tom Stage

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
header( "Content-Type: text/html; charset=windows-1252" );

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
$zmSlang24BitColour          = '24 bit farve';
$zmSlang8BitGrey             = '8 bit greyscale';
$zmSlangAction               = 'Action';
$zmSlangActual               = 'Aktuel';
$zmSlangAddNewControl        = 'Tilføj Ny kontrol';
$zmSlangAddNewMonitor        = 'Tilføj Ny Monitor';
$zmSlangAddNewUser           = 'Tilføj Ny Bruger';
$zmSlangAddNewZone           = 'Tilføj Ny Zone';
$zmSlangAlarm                = 'Alarm';
$zmSlangAlarmBrFrames        = 'Alarm<br/>Billeder';
$zmSlangAlarmFrame           = 'Alarm Billede';
$zmSlangAlarmFrameCount      = 'Alarm Billede Tæller';
$zmSlangAlarmLimits          = 'Alarm Begrændsing';
$zmSlangAlarmMaximumFPS      = 'Alarm Maximum FPS';
$zmSlangAlarmPx              = 'Alarm Px';
$zmSlangAlarmRGBUnset        = 'You must set an alarm RGB colour';
$zmSlangAlert                = 'Alarm';
$zmSlangAll                  = 'Alle';
$zmSlangApply                = 'Aktiver';
$zmSlangApplyingStateChange  = 'Aktivere State Ændring';
$zmSlangArchArchived         = 'Kun Arkiverede';
$zmSlangArchive              = 'Arkiver';
$zmSlangArchived             = 'Archived';
$zmSlangArchUnarchived       = 'Kun Ikke Arkiverede';
$zmSlangArea                 = 'Area';
$zmSlangAreaUnits            = 'Area (px/%)';
$zmSlangAttrAlarmFrames      = 'Alarm Billeder';
$zmSlangAttrArchiveStatus    = 'Arkiverings Status';
$zmSlangAttrAvgScore         = 'Avg. Skore';
$zmSlangAttrCause            = 'Årsag';
$zmSlangAttrDate             = 'Dato';
$zmSlangAttrDateTime         = 'Dato/Tid';
$zmSlangAttrDiskBlocks       = 'Disk Blocks';
$zmSlangAttrDiskPercent      = 'Disk Procent';
$zmSlangAttrDuration         = 'Forløb';
$zmSlangAttrFrames           = 'Billeder';
$zmSlangAttrId               = 'Id';
$zmSlangAttrMaxScore         = 'Max. Skore';
$zmSlangAttrMonitorId        = 'Monitor Id';
$zmSlangAttrMonitorName      = 'Monitor Navn';
$zmSlangAttrName             = 'Navn';
$zmSlangAttrNotes            = 'Notes';
$zmSlangAttrTime             = 'Tid';
$zmSlangAttrTotalScore       = 'Total Skore';
$zmSlangAttrWeekday          = 'Uge Dag';
$zmSlangAutoArchiveEvents    = 'Automatisk arkiver alle matchende';
$zmSlangAuto                 = 'Auto';
$zmSlangAutoDeleteEvents     = 'Automatisk slet alle matchende';
$zmSlangAutoEmailEvents      = 'Automatisk email detalier af alle matchende';
$zmSlangAutoExecuteEvents    = 'Automatisk kør kommando på alle matchende';
$zmSlangAutoMessageEvents    = 'Send Automatisk detalier af alle matchende';
$zmSlangAutoStopTimeout      = 'Auto Stop Timeout';
$zmSlangAutoUploadEvents     = 'Upload Automatisk alle matchende';
$zmSlangAutoVideoEvents      = 'Automatically create video for all matches';
$zmSlangAvgBrScore           = 'Avg.<br/>Skore';
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
$zmSlangBadNameChars         = 'Navne må kun indeholde alphanumeric karaktere plus hyphen og underscore';
$zmSlangBadPath              = 'Path must be set to a valid value';
$zmSlangBadPort              = 'Port must be set to a valid number';
$zmSlangBadPostEventCount    = 'Post event image buffer must be an integer of zero or more';
$zmSlangBadPreEventCount     = 'Pre event image buffer must be at least zero, and less than image buffer size';
$zmSlangBadRefBlendPerc      = 'Reference blendpercentage must be a positive integer';
$zmSlangBadSectionLength     = 'Section length must be an integer of 30 or more';
$zmSlangBadWarmupCount       = 'Warmup frames must be an integer of zero or more';
$zmSlangBadWebColour         = 'Web colour must be a valid web colour string';
$zmSlangBadWidth             = 'Width must be set to a valid value';
$zmSlangBandwidth            = 'Båndbrede';
$zmSlangBlobPx               = 'Blob Px';
$zmSlangBlobs                = 'Blobs';
$zmSlangBlobSizes            = 'Blob Størelse';
$zmSlangBrightness           = 'Brightness';
$zmSlangBuffers              = 'Buffere';
$zmSlangCanAutoFocus         = 'Kan Auto Focus';
$zmSlangCanAutoGain          = 'Kan Auto Gain';
$zmSlangCanAutoIris          = 'Kan Auto Iris';
$zmSlangCanAutoWhite         = 'Kan Auto White Bal.';
$zmSlangCanAutoZoom          = 'Kan Auto Zoom';
$zmSlangCancelForcedAlarm    = 'Fortryd&nbsp;Forced&nbsp;Alarm';
$zmSlangCancel               = 'Fortryd';
$zmSlangCanFocusAbs          = 'Kan Focus Absolut';
$zmSlangCanFocusCon          = 'Kan Focus Kontinuerligt';
$zmSlangCanFocus             = 'Kan Focus';
$zmSlangCanFocusRel          = 'Kan Focus Relativt';
$zmSlangCanGainAbs           = 'Kan Gain Absolut';
$zmSlangCanGainCon           = 'Kan Gain Kontinuerligt';
$zmSlangCanGain              = 'Kan Gain ';
$zmSlangCanGainRel           = 'Kan Gain Relativt';
$zmSlangCanIrisAbs           = 'Kan Iris Absolut';
$zmSlangCanIrisCon           = 'Kan Iris Kontinuerligt';
$zmSlangCanIris              = 'Kan Iris';
$zmSlangCanIrisRel           = 'Kan Iris Relativt';
$zmSlangCanMoveAbs           = 'Kan Bevæge Absolut';
$zmSlangCanMoveCon           = 'Kan Bevæge Kontinuerligt';
$zmSlangCanMoveDiag          = 'Kan Bevæge Diagonalt';
$zmSlangCanMove              = 'Kan Bevæge';
$zmSlangCanMoveMap           = 'Kan Bevæge Mapped';
$zmSlangCanMoveRel           = 'Kan Bevæge Relativt';
$zmSlangCanPan               = 'Kan Pan' ;
$zmSlangCanReset             = 'Kan Reset';
$zmSlangCanSetPresets        = 'Kan Set Presets';
$zmSlangCanSleep             = 'Kan Sove';
$zmSlangCanTilt              = 'Kan Tilt';
$zmSlangCanWake              = 'Kan Vågne';
$zmSlangCanWhiteAbs          = 'Kan White Bal. Absolut';
$zmSlangCanWhiteBal          = 'Kan White Bal.';
$zmSlangCanWhiteCon          = 'Kan White Bal. Kontinuerligt';
$zmSlangCanWhite             = 'Kan White Balance';
$zmSlangCanWhiteRel          = 'Kan White Bal. Relativt';
$zmSlangCanZoomAbs           = 'Kan Zoom Absolut';
$zmSlangCanZoomCon           = 'Kan Zoom Kontinuerligt';
$zmSlangCanZoom              = 'Kan Zoom';
$zmSlangCanZoomRel           = 'Kan Zoom Relativt';
$zmSlangCaptureHeight        = 'Capture Height';
$zmSlangCapturePalette       = 'Capture Palette';
$zmSlangCaptureWidth         = 'Capture Width';
$zmSlangCause                = 'Årsag';
$zmSlangCheckMethod          = 'Alarm Check Methode';
$zmSlangChooseFilter         = 'Vælg Filter';
$zmSlangChoosePreset         = 'Choose Preset';
$zmSlangClose                = 'Luk';
$zmSlangColour               = 'Farve';
$zmSlangCommand              = 'Kommando';
$zmSlangConfig               = 'konfig';
$zmSlangConfiguredFor        = 'Konfigureret for';
$zmSlangConfirmDeleteEvents  = 'Are you sure you wish to delete the selected events?';
$zmSlangConfirmPassword      = 'Verifiser Password';
$zmSlangConjAnd              = 'og';
$zmSlangConjOr               = 'eller';
$zmSlangConsole              = 'Konsol';
$zmSlangContactAdmin         = 'Kontakt Din adminstrator for detalier.';
$zmSlangContinue             = 'Fortsæt';
$zmSlangContrast             = 'Kontrast';
$zmSlangControlAddress       = 'Kontrol Addresse';
$zmSlangControlCap           = 'Kontrol Capability';
$zmSlangControlCaps          = 'Kontrol Capabilities';
$zmSlangControlDevice        = 'Kontrol Enhed';
$zmSlangControl              = 'Kontrol';
$zmSlangControllable         = 'Controllable';
$zmSlangControlType          = 'Kontrol Type';
$zmSlangCycle                = 'Cycle';
$zmSlangCycleWatch           = 'Cycle Watch';
$zmSlangDay                  = 'Dag';
$zmSlangDebug                = 'Debug';
$zmSlangDefaultRate          = 'Default Rate';
$zmSlangDefaultScale         = 'Default Scale';
$zmSlangDeleteAndNext        = 'Slet &amp; Næste';
$zmSlangDeleteAndPrev        = 'Slet &amp; Forrige';
$zmSlangDeleteSavedFilter    = 'Slet Gemte filter';
$zmSlangDelete               = 'Slet';
$zmSlangDescription          = 'Beskrivelse';
$zmSlangDeviceChannel        = 'Enheds Kanal';
$zmSlangDeviceFormat         = 'Enheds Format';
$zmSlangDeviceNumber         = 'Enheds Nummer';
$zmSlangDevicePath           = 'Device Path';
$zmSlangDimensions           = 'Dimentioner';
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
$zmSlangDuration             = 'Forløb';
$zmSlangEdit                 = 'Rediger';
$zmSlangEmail                = 'Email';
$zmSlangEnableAlarms         = 'Enable Alarms';
$zmSlangEnabled              = 'Aktiv';
$zmSlangEnterNewFilterName   = 'Skriv Nyt filter navn';
$zmSlangErrorBrackets        = 'Fejl, check at du har lige antal af Åbnings og Lukkende brackets';
$zmSlangError                = 'Fejl';
$zmSlangErrorValidValue      = 'Fejl, check at alle terms har en valid værdig';
$zmSlangEtc                  = 'etc';
$zmSlangEvent                = 'Event';
$zmSlangEventFilter          = 'Event Filter';
$zmSlangEventId              = 'Event Id';
$zmSlangEventName            = 'Event Navn';
$zmSlangEventPrefix          = 'Event Prefix';
$zmSlangEvents               = 'Events';
$zmSlangExclude              = 'Exclude';
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
$zmSlangFeed                 = 'Feed';
$zmSlangFileColours          = 'File Colours';
$zmSlangFile                 = 'File';
$zmSlangFilePath             = 'File Path';
$zmSlangFilterPx             = 'Filter Px';
$zmSlangFilters              = 'Filters';
$zmSlangFilterUnset          = 'You must specify a filter width and height';
$zmSlangFirst                = 'Første';
$zmSlangFlippedHori          = 'Flipped Horizontally';
$zmSlangFlippedVert          = 'Flipped Vertically';
$zmSlangFocus                = 'Fokus';
$zmSlangForceAlarm           = 'Tving&nbsp;Alarm';
$zmSlangFormat               = 'Format';
$zmSlangFPS                  = 'fps';
$zmSlangFPSReportInterval    = 'FPS Raport Interval';
$zmSlangFrame                = 'Billede';
$zmSlangFrameId              = 'Billede Id';
$zmSlangFrameRate            = 'Billede Rate';
$zmSlangFrames               = 'Billede';
$zmSlangFrameSkip            = 'Billede Skip';
$zmSlangFTP                  = 'FTP';
$zmSlangFunc                 = 'Func';
$zmSlangFunction             = 'Funktion';
$zmSlangGain                 = 'Gain';
$zmSlangGeneral              = 'General';
$zmSlangGenerateVideo        = 'Generer Video';
$zmSlangGeneratingVideo      = 'Generere Video';
$zmSlangGoToZoneMinder       = 'Gå til ZoneMinder.com';
$zmSlangGrey                 = 'Grå';
$zmSlangGroup                = 'Group';
$zmSlangGroups               = 'Grupper';
$zmSlangHasFocusSpeed        = 'Har Fokus Hastighed';
$zmSlangHasGainSpeed         = 'Har Gain Hastighed';
$zmSlangHasHomePreset        = 'Har Hjem Preset';
$zmSlangHasIrisSpeed         = 'Har Iris Hastighed';
$zmSlangHasPanSpeed          = 'Har Pan Hastighed';
$zmSlangHasPresets           = 'Har Presets';
$zmSlangHasTiltSpeed         = 'Har Tilt Hastighed';
$zmSlangHasTurboPan          = 'Har Turbo Pan';
$zmSlangHasTurboTilt         = 'Har Turbo Tilt';
$zmSlangHasWhiteSpeed        = 'Har White Bal. Hastighed';
$zmSlangHasZoomSpeed         = 'Har Zoom Hastighed';
$zmSlangHighBW               = 'Høj&nbsp;B/B';
$zmSlangHigh                 = 'Høj';
$zmSlangHome                 = 'Hjem';
$zmSlangHour                 = 'Time';
$zmSlangHue                  = 'Hue';
$zmSlangId                   = 'Id';
$zmSlangIdle                 = 'Idle';
$zmSlangIgnore               = 'Ignorer';
$zmSlangImage                = 'Billede';
$zmSlangImageBufferSize      = 'Billede Buffer Størelse (Billeder)';
$zmSlangImages               = 'Images';
$zmSlangInclude              = 'Inkluder';
$zmSlangIn                   = 'Ind';
$zmSlangInverted             = 'Inverteret';
$zmSlangIris                 = 'Iris';
$zmSlangLanguage             = 'Sprog';
$zmSlangLast                 = 'Sidste';
$zmSlangLimitResultsPost     = 'results only;'; // This is used at the end of the phrase 'Limit to first N results only'
$zmSlangLimitResultsPre      = 'Limit to first'; // This is used at the beginning of the phrase 'Limit to first N results only'
$zmSlangLinkedMonitors       = 'Linked Monitors';
$zmSlangList                 = 'List';
$zmSlangLoad                 = 'Load';
$zmSlangLocal                = 'Lokal';
$zmSlangLoggedInAs           = 'Logget Ind Som';
$zmSlangLoggingIn            = 'Logger Ind';
$zmSlangLogin                = 'Logind';
$zmSlangLogout               = 'Logud';
$zmSlangLowBW                = 'Lav&nbsp;B/B';
$zmSlangLow                  = 'Lav';
$zmSlangMain                 = 'Main';
$zmSlangMan                  = 'Man';
$zmSlangManual               = 'Manual';
$zmSlangMark                 = 'Marker';
$zmSlangMaxBandwidth         = 'Max Bandwidth';
$zmSlangMaxBrScore           = 'Max.<br/>Skore';
$zmSlangMaxFocusRange        = 'Max Focus Range';
$zmSlangMaxFocusSpeed        = 'Max Focus Speed';
$zmSlangMaxFocusStep         = 'Max Focus Step';
$zmSlangMaxGainRange         = 'Max Gain Range';
$zmSlangMaxGainSpeed         = 'Max Gain Speed';
$zmSlangMaxGainStep          = 'Max Gain Step';
$zmSlangMaximumFPS           = 'Maximale FPS';
$zmSlangMaxIrisRange         = 'Max Iris Range';
$zmSlangMaxIrisSpeed         = 'Max Iris Speed';
$zmSlangMaxIrisStep          = 'Max Iris Step';
$zmSlangMax                  = 'Max';
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
$zmSlangMediumBW             = 'Medium&nbsp;B/B';
$zmSlangMedium               = 'Medium';
$zmSlangMinAlarmAreaLtMax    = 'Minimum alarm area should be less than maximum';
$zmSlangMinAlarmAreaUnset    = 'You must specify the minimum alarm pixel count';
$zmSlangMinBlobAreaLtMax     = 'Minimum blob område bør være mindre end maximum';
$zmSlangMinBlobAreaUnset     = 'You must specify the minimum blob pixel count';
$zmSlangMinBlobLtMinFilter   = 'Minimum blob area should be less than or equal to minimum filter area';
$zmSlangMinBlobsLtMax        = 'Minimum blobs bør være mindre end maximum';
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
$zmSlangMinPixelThresLtMax   = 'Minimum pixel threshold bør være mindre end maximum';
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
$zmSlangMisc                 = 'Misc';
$zmSlangMonitorIds           = 'Monitor&nbsp;Ids';
$zmSlangMonitor              = 'Monitor';
$zmSlangMonitorPresetIntro   = 'Select an appropriate preset from the list below.<br><br>Please note that this may overwrite any values you already have configured for this monitor.<br><br>';
$zmSlangMonitorPreset        = 'Monitor Preset';
$zmSlangMonitors             = 'Monitore';
$zmSlangMontage              = 'Montage';
$zmSlangMonth                = 'Måned';
$zmSlangMove                 = 'Flyt';
$zmSlangMustBeGe             = 'skal være støre end eller ligmed';
$zmSlangMustBeLe             = 'Skal være mindre end eller ligmed';
$zmSlangMustConfirmPassword  = 'Du skal konfimere password';
$zmSlangMustSupplyPassword   = 'Du skal angive et password';
$zmSlangMustSupplyUsername   = 'Du skal opgive et username';
$zmSlangName                 = 'Navn';
$zmSlangNear                 = 'Near';
$zmSlangNetwork              = 'Netværk';
$zmSlangNewGroup             = 'Ny Gruppe';
$zmSlangNew                  = 'Ny';
$zmSlangNewPassword          = 'Nyt Password';
$zmSlangNewState             = 'Ny State';
$zmSlangNewUser              = 'Ny User';
$zmSlangNext                 = 'Næste';
$zmSlangNoFramesRecorded     = 'Der er ingen billeder optaget for denne event';
$zmSlangNoGroup              = 'No Group';
$zmSlangNoneAvailable        = 'Ingen Tilstede';
$zmSlangNone                 = 'Ingen';
$zmSlangNo                   = 'Nej';
$zmSlangNormal               = 'Normal';
$zmSlangNoSavedFilters       = 'NoSavedFilters';
$zmSlangNoStatisticsRecorded = 'Der er ingen statestikker optaget for denne event/frame';
$zmSlangNotes                = 'Notes';
$zmSlangNumPresets           = 'Num Presets';
$zmSlangOpen                 = 'Åben';
$zmSlangOpEq                 = 'ligmed';
$zmSlangOpGtEq               = 'støre end eller ligmed';
$zmSlangOpGt                 = 'støre end';
$zmSlangOpIn                 = 'i sættet';
$zmSlangOpLtEq               = 'mindre end eller ligmed';
$zmSlangOpLt                 = 'mindre end';
$zmSlangOpMatches            = 'matches';
$zmSlangOpNe                 = 'ikke ligmed';
$zmSlangOpNotIn              = 'ikke i sættet';
$zmSlangOpNotMatches         = 'does not match';
$zmSlangOptionHelp           = 'OptionHelp';
$zmSlangOptionRestartWarning = 'Disse ændringer træder ikke i fuld effect\nmens systemt køre. Når du har\nafsluttet ændringer bedes du\ngenstarte ZoneMinder.';
$zmSlangOptions              = 'Indstillinger';
$zmSlangOrder                = 'Order';
$zmSlangOrEnterNewName       = 'eller skriv nyt navn';
$zmSlangOrientation          = 'Orientation';
$zmSlangOut                  = 'Ud';
$zmSlangOverwriteExisting    = 'Overskriv Eksisterende';
$zmSlangPaged                = 'Paged';
$zmSlangPanLeft              = 'Pan Left';
$zmSlangPan                  = 'Pan';
$zmSlangPanRight             = 'Pan Right';
$zmSlangPanTilt              = 'Pan/Tilt';
$zmSlangParameter            = 'Parameter';
$zmSlangPassword             = 'Password';
$zmSlangPasswordsDifferent   = 'Det nye og konfimerede passwords er forskellige';
$zmSlangPaths                = 'Stiger';
$zmSlangPhoneBW              = 'Telefon&nbsp;B/B';
$zmSlangPhone                = 'Telefon';
$zmSlangPixelDiff            = 'Pixel Diff';
$zmSlangPixels               = 'pixels';
$zmSlangPlayAll              = 'Afspil Alle';
$zmSlangPleaseWait           = 'Vent venligst';
$zmSlangPoint                = 'Point';
$zmSlangPostEventImageBuffer = 'Efter Event Billed Buffer';
$zmSlangPreEventImageBuffer  = 'Før Event Billed Buffer';
$zmSlangPreset               = 'Preset';
$zmSlangPresets              = 'Presets';
$zmSlangPrev                 = 'Prev';
$zmSlangRate                 = 'Rate';
$zmSlangReal                 = 'Real';
$zmSlangRecord               = 'Optag';
$zmSlangRefImageBlendPct     = 'Reference Billede Blend %ge';
$zmSlangRefresh              = 'Opdater';
$zmSlangRemoteHostName       = 'Remote Host Navn';
$zmSlangRemoteHostPath       = 'Remote Host Stig';
$zmSlangRemoteHostPort       = 'Remote Host Port';
$zmSlangRemoteImageColours   = 'Remote Image Farver';
$zmSlangRemote               = 'Remote';
$zmSlangRename               = 'Omdøb';
$zmSlangReplay               = 'Spil Igen';
$zmSlangResetEventCounts     = 'Reset Event Counts';
$zmSlangReset                = 'Nulstil';
$zmSlangRestart              = 'Genstart';
$zmSlangRestarting           = 'Genstarter';
$zmSlangRestrictedCameraIds  = 'Begranset Kamera Ids';
$zmSlangRestrictedMonitors   = 'Restricted Monitors';
$zmSlangReturnDelay          = 'Return Delay';
$zmSlangReturnLocation       = 'Return Location';
$zmSlangRotateLeft           = 'Rotate Left';
$zmSlangRotateRight          = 'Rotate Right';
$zmSlangRunMode              = 'Kørsels Mode';
$zmSlangRunning              = 'Køre';
$zmSlangRunState             = 'Run State';
$zmSlangSaveAs               = 'Gem Som';
$zmSlangSaveFilter           = 'Gem Filter';
$zmSlangSave                 = 'Gem';
$zmSlangScale                = 'Scale';
$zmSlangScore                = 'Skore';
$zmSlangSecs                 = 'Sekunder';
$zmSlangSectionlength        = 'Sektion længde';
$zmSlangSelectMonitors       = 'Select Monitors';
$zmSlangSelect               = 'Vælg';
$zmSlangSelfIntersecting     = 'Polygon edges must not intersect';
$zmSlangSetLearnPrefs        = 'Set Learn Prefs'; // This can be ignored for now
$zmSlangSetNewBandwidth      = 'Sæt Ny Båndbrede';
$zmSlangSetPreset            = 'Sæt Preset';
$zmSlangSet                  = 'Sæt';
$zmSlangSettings             = 'Indstillinger';
$zmSlangShowFilterWindow     = 'VisFilterVindue';
$zmSlangShowTimeline         = 'Show Timeline';
$zmSlangSize                 = 'Size';
$zmSlangSleep                = 'Sov';
$zmSlangSortAsc              = 'Asc';
$zmSlangSortBy               = 'Sorter efter';
$zmSlangSortDesc             = 'Desc';
$zmSlangSource               = 'Enhed';
$zmSlangSourceType           = 'Enheds Type';
$zmSlangSpeed                = 'Hastighed';
$zmSlangSpeedHigh            = 'Høj Hastighed';
$zmSlangSpeedLow             = 'Lav Hastighed';
$zmSlangSpeedMedium          = 'Medium Hastighed';
$zmSlangSpeedTurbo           = 'Turbo Hastighed';
$zmSlangStart                = 'Start';
$zmSlangState                = 'State';
$zmSlangStats                = 'Stats';
$zmSlangStatus               = 'Status';
$zmSlangStepLarge            = 'Large Step';
$zmSlangStepMedium           = 'Medium Step';
$zmSlangStepNone             = 'No Step';
$zmSlangStepSmall            = 'Small Step';
$zmSlangStep                 = 'Step';
$zmSlangStills               = 'Stills';
$zmSlangStopped              = 'Stoppet';
$zmSlangStop                 = 'Stop';
$zmSlangStream               = 'Stream';
$zmSlangSubmit               = 'Submit';
$zmSlangSystem               = 'System';
$zmSlangTele                 = 'Tele';
$zmSlangThumbnail            = 'Thumbnail';
$zmSlangTilt                 = 'Tilt';
$zmSlangTimeDelta            = 'Time Delta';
$zmSlangTimeline             = 'Timeline';
$zmSlangTimestampLabelFormat = 'Tidsstempel Mærkning´s Format';
$zmSlangTimestampLabelX      = 'Tidsstempel Mærkning X';
$zmSlangTimestampLabelY      = 'Tidsstempel Mærkning Y';
$zmSlangTimestamp            = 'Tidsstempel';
$zmSlangTimeStamp            = 'Tids Stempel';
$zmSlangTime                 = 'Tid';
$zmSlangToday                = 'Idag';
$zmSlangTools                = 'Tools';
$zmSlangTotalBrScore         = 'Total<br/>Skore';
$zmSlangTrackDelay           = 'Track Delay';
$zmSlangTrackMotion          = 'Track Motion';
$zmSlangTriggers             = 'Triggers';
$zmSlangTurboPanSpeed        = 'Turbo Pan Hastighed';
$zmSlangTurboTiltSpeed       = 'Turbo Tilt Hastighed';
$zmSlangType                 = 'Type';
$zmSlangUnarchive            = 'Unarchive';
$zmSlangUnits                = 'Units';
$zmSlangUnknown              = 'Unknown';
$zmSlangUpdateAvailable      = 'En updatering til ZoneMinder er tilstede.';
$zmSlangUpdateNotNecessary   = 'Ingen updatering er nødvendig.';
$zmSlangUpdate               = 'Update';
$zmSlangUseFilter            = 'Brug Filter';
$zmSlangUseFilterExprsPost   = '&nbsp;filter&nbsp;expressions'; // This is used at the end of the phrase 'use N filter expressions'
$zmSlangUseFilterExprsPre    = 'Brug&nbsp;'; // This is used at the beginning of the phrase 'use N filter expressions'
$zmSlangUser                 = 'Bruger';
$zmSlangUsername             = 'Bruger Navn';
$zmSlangUsers                = 'Brugere';
$zmSlangValue                = 'Værdig';
$zmSlangVersionIgnore        = 'Ignorer denne version';
$zmSlangVersionRemindDay     = 'Påmind igen om 1 dag';
$zmSlangVersionRemindHour    = 'Påmind igen om 1 time';
$zmSlangVersionRemindNever   = 'Mind ikke om nye versioner';
$zmSlangVersionRemindWeek    = 'Påmind igen om 1 uge';
$zmSlangVersion              = 'Version';
$zmSlangVideoFormat          = 'Video Format';
$zmSlangVideoGenFailed       = 'Video Generering Fejlede!';
$zmSlangVideoGenFiles        = 'Existing Video Files';
$zmSlangVideoGenNoFiles      = 'No Video Files Found';
$zmSlangVideoGenParms        = 'Video Generaring Parametre';
$zmSlangVideoGenSucceeded    = 'Video Generation Succeeded!';
$zmSlangVideoSize            = 'Video Størelse';
$zmSlangVideo                = 'Video';
$zmSlangViewAll              = 'Vis Alle';
$zmSlangViewEvent            = 'View Event';
$zmSlangViewPaged            = 'View Paged';
$zmSlangView                 = 'Vis';
$zmSlangWake                 = 'Wake';
$zmSlangWarmupFrames         = 'Varmop Billeder';
$zmSlangWatch                = 'Se';
$zmSlangWebColour            = 'Web Colour';
$zmSlangWeb                  = 'Web';
$zmSlangWeek                 = 'Uge';
$zmSlangWhiteBalance         = 'White Balance';
$zmSlangWhite                = 'White';
$zmSlangWide                 = 'Wide';
$zmSlangX10ActivationString  = 'X10 Activerings Streng';
$zmSlangX10InputAlarmString  = 'X10 Input Alarm Streng';
$zmSlangX10OutputAlarmString = 'X10 Output Alarm Streng';
$zmSlangX10                  = 'X10';
$zmSlangX                    = 'X';
$zmSlangYes                  = 'Ja';
$zmSlangYouNoPerms           = 'Du har ikke adgang til denne resourse.';
$zmSlangY                    = 'Y';
$zmSlangZoneAlarmColour      = 'Alarm Farve (Red/Green/Blue)';
$zmSlangZoneArea             = 'Zone Area';
$zmSlangZoneFilterSize       = 'Filter Width/Height (pixels)';
$zmSlangZoneMinMaxAlarmArea  = 'Min/Max Alarmed Area';
$zmSlangZoneMinMaxBlobArea   = 'Min/Max Blob Area';
$zmSlangZoneMinMaxBlobs      = 'Min/Max Blobs';
$zmSlangZoneMinMaxFiltArea   = 'Min/Max Filtered Area';
$zmSlangZoneMinMaxPixelThres = 'Min/Max Pixel Threshold (0-255)';
$zmSlangZones                = 'Zoner';
$zmSlangZone                 = 'Zone';
$zmSlangZoomIn               = 'Zoom In';
$zmSlangZoomOut              = 'Zoom Out';
$zmSlangZoom                 = 'Zoom';

// Complex replacements with formatting and/or placements, must be passed through sprintf
$zmClangCurrentLogin         = 'Nuværende login er \'%1$s\'';
$zmClangEventCount           = '%1$s %2$s'; // For example '37 Events' (from Vlang below)
$zmClangLastEvents           = 'Sidste %1$s %2$s'; // For example 'Last 37 Events' (from Vlang below)
$zmClangLatestRelease        = 'Den Seneste version er v%1$s, du har v%2$s.';
$zmClangMonitorCount         = '%1$s %2$s'; // For example '4 Monitors' (from Vlang below)
$zmClangMonitorFunction      = 'Monitor %1$s Function';
$zmClangRunningRecentVer     = 'Du Køre med seneste version af ZoneMinder, v%s.';

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
$zmVlangEvent                = array( 0=>'Events', 1=>'Event', 2=>'Events' );
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
//$zmOlangPromptLANG_DEFAULT = "This is a new prompt for this option";
//$zmOlangHelpLANG_DEFAULT = "This is some new help for this option which will be displayed in the popup window when the ? is clicked";
//

?>
