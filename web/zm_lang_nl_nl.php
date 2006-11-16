<?php
//
// ZoneMinder web Dutch language file, $Date$, $Revision$
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

// ZoneMinder Dutch Translation by Koen Veen

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
$zmSlang24BitColour          = '24 bit kleuren';
$zmSlang8BitGrey             = '8 bit grijstinten';
$zmSlangAction               = 'Action';
$zmSlangActual               = 'Aktueel';
$zmSlangAddNewControl        = 'Add New Control';
$zmSlangAddNewMonitor        = 'Voeg een nieuwe monitor toe';
$zmSlangAddNewUser           = 'Voeg een nieuwe gebruiker toe';
$zmSlangAddNewZone           = 'Voeg een nieuwe zone toe';
$zmSlangAlarm                = 'Alarm';
$zmSlangAlarmBrFrames        = 'Alarm<br/>Frames';
$zmSlangAlarmFrame           = 'Alarm Frame';
$zmSlangAlarmFrameCount      = 'Alarm Frame Count';
$zmSlangAlarmLimits          = 'Alarm Limieten';
$zmSlangAlarmMaximumFPS      = 'Alarm Maximum FPS';
$zmSlangAlarmPx              = 'Alarm Px';
$zmSlangAlarmRGBUnset        = 'You must set an alarm RGB colour';
$zmSlangAlert                = 'Waarschuwing';
$zmSlangAll                  = 'Alle';
$zmSlangApplyingStateChange  = 'Status verandering aan het uitvoeren';
$zmSlangApply                = 'Voer uit';
$zmSlangArchArchived         = 'Alleen gearchiveerd';
$zmSlangArchive              = 'Archief';
$zmSlangArchived             = 'Archived';
$zmSlangArchUnarchived       = 'Alleen ongearchiveerd';
$zmSlangArea                 = 'Area';
$zmSlangAreaUnits            = 'Area (px/%)';
$zmSlangAttrAlarmFrames      = 'Alarm frames';
$zmSlangAttrArchiveStatus    = 'Archief status';
$zmSlangAttrAvgScore         = 'Gem. score';
$zmSlangAttrCause            = 'Cause';
$zmSlangAttrDate             = 'Datum';
$zmSlangAttrDateTime         = 'Datum/tijd';
$zmSlangAttrDiskBlocks       = 'Disk Blocks';
$zmSlangAttrDiskPercent      = 'Disk Percent';
$zmSlangAttrDuration         = 'Duur';
$zmSlangAttrFrames           = 'Frames';
$zmSlangAttrId               = 'Id';
$zmSlangAttrMaxScore         = 'Max. Score';
$zmSlangAttrMonitorId        = 'Monitor Id';
$zmSlangAttrMonitorName      = 'Monitor Naam';
$zmSlangAttrMontage          = 'Montage';
$zmSlangAttrName             = 'Name';
$zmSlangAttrNotes            = 'Notes';
$zmSlangAttrTime             = 'Tijd';
$zmSlangAttrTotalScore       = 'Totale Score';
$zmSlangAttrWeekday          = 'Weekdag';
$zmSlangArchiveEvents        = 'Archiveer alle overeenkomsten';
$zmSlangAuto                 = 'Auto';
$zmSlangDeleteEvents         = 'Verwijder alle overeenkomsten';
$zmSlangEmailEvents          = 'Email de details van alle overeenkomsten';
$zmSlangExecuteEvents        = 'Voer opdrachten op alle overeenkomsten uit';
$zmSlangMessageEvents        = 'Bericht de details van alle overeenkomsten';
$zmSlangAutoStopTimeout      = 'Auto Stop Timeout';
$zmSlangUploadEvents         = 'Upload alle overeenkomsten';
$zmSlangVideoEvents          = 'Create video for all matches';
$zmSlangAvgBrScore           = 'Gem.<br/>score';
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
$zmSlangBadNameChars         = 'Namen mogen alleen alpha numerieke karakters bevatten plus hyphens en underscores';
$zmSlangBadPath              = 'Path must be set to a valid value';
$zmSlangBadPort              = 'Port must be set to a valid number';
$zmSlangBadPostEventCount    = 'Post event image buffer must be an integer of zero or more';
$zmSlangBadPreEventCount     = 'Pre event image buffer must be at least zero, and less than image buffer size';
$zmSlangBadRefBlendPerc      = 'Reference blendpercentage must be a positive integer';
$zmSlangBadSectionLength     = 'Section length must be an integer of 30 or more';
$zmSlangBadWarmupCount       = 'Warmup frames must be an integer of zero or more';
$zmSlangBadWebColour         = 'Web colour must be a valid web colour string';
$zmSlangBadWidth             = 'Width must be set to a valid value';
$zmSlangBandwidth            = 'Bandbreedte';
$zmSlangBlobPx               = 'Blob px';
$zmSlangBlobs                = 'Blobs';
$zmSlangBlobSizes            = 'Blob grootte';
$zmSlangBrightness           = 'Helderheid';
$zmSlangBuffers              = 'Buffers';
$zmSlangCanAutoFocus         = 'Can Auto Focus';
$zmSlangCanAutoGain          = 'Can Auto Gain';
$zmSlangCanAutoIris          = 'Can Auto Iris';
$zmSlangCanAutoWhite         = 'Can Auto White Bal.';
$zmSlangCanAutoZoom          = 'Can Auto Zoom';
$zmSlangCancel               = 'Cancel';
$zmSlangCancelForcedAlarm    = 'Cancel&nbsp;geforceerd&nbsp;alarm';
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
$zmSlangCaptureHeight        = 'Capture hoogte';
$zmSlangCapturePalette       = 'Capture pallet';
$zmSlangCaptureWidth         = 'Capture breedte';
$zmSlangCause                = 'Cause';
$zmSlangCheckMethod          = 'Alarm Check Methode';
$zmSlangChooseFilter         = 'Kies filter';
$zmSlangChoosePreset         = 'Choose Preset';
$zmSlangClose                = 'Sluit';
$zmSlangColour               = 'Kleur';
$zmSlangCommand              = 'Command';
$zmSlangConfig               = 'Config';
$zmSlangConfiguredFor        = 'Geconfigureerd voor';
$zmSlangConfirmDeleteEvents  = 'Are you sure you wish to delete the selected events?';
$zmSlangConfirmPassword      = 'Bevestig wachtwoord';
$zmSlangConjAnd              = 'en';
$zmSlangConjOr               = 'of';
$zmSlangConsole              = 'Console';
$zmSlangContactAdmin         = 'Neem A.U.B. contact op met je beheerder voor details.';
$zmSlangContinue             = 'Continue';
$zmSlangContrast             = 'Contrast';
$zmSlangControlAddress       = 'Control Address';
$zmSlangControlCap           = 'Control Capability';
$zmSlangControlCaps          = 'Control Capabilities';
$zmSlangControl              = 'Control';
$zmSlangControlDevice        = 'Control Device';
$zmSlangControllable         = 'Controllable';
$zmSlangControlType          = 'Control Type';
$zmSlangCycle                = 'Cycle';
$zmSlangCycleWatch           = 'Observeer cyclus';
$zmSlangDay                  = 'Dag';
$zmSlangDebug                = 'Debug';
$zmSlangDefaultRate          = 'Default Rate';
$zmSlangDefaultScale         = 'Default Scale';
$zmSlangDefaultView          = 'Default View';
$zmSlangDeleteAndNext        = 'verwijder &amp; volgende';
$zmSlangDeleteAndPrev        = 'verwijder &amp; vorige';
$zmSlangDeleteSavedFilter    = 'verwijder opgeslagen filter';
$zmSlangDelete               = 'verwijder';
$zmSlangDescription          = 'Omschrijving';
$zmSlangDeviceChannel        = 'Apparaat kanaal';
$zmSlangDeviceFormat         = 'Apparaat formaat';
$zmSlangDeviceNumber         = 'apparaat nummer';
$zmSlangDevicePath           = 'Device Path';
$zmSlangDevices              = 'Devices';
$zmSlangDimensions           = 'Afmetingen';
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
$zmSlangDuration             = 'Duur';
$zmSlangEdit                 = 'Bewerk';
$zmSlangEmail                = 'Email';
$zmSlangEnableAlarms         = 'Enable Alarms';
$zmSlangEnabled              = 'Uitgeschakeld';
$zmSlangEnterNewFilterName   = 'Voer nieuwe filter naam in';
$zmSlangErrorBrackets        = 'Error, controleer of je even veel openings als afsluiting brackets hebt gebruikt';
$zmSlangError                = 'Error';
$zmSlangErrorValidValue      = 'Error, Controleer of alle termen een geldige waarde hebben';
$zmSlangEtc                  = 'etc';
$zmSlangEventFilter          = 'Gebeurtenis filter';
$zmSlangEvent                = 'Gebeurtenis';
$zmSlangEventId              = 'Event Id';
$zmSlangEventName            = 'Event Name';
$zmSlangEventPrefix          = 'Event Prefix';
$zmSlangEvents               = 'Gebeurtenissen';
$zmSlangExclude              = 'Sluit uit';
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
$zmSlangFeed                 = 'toevoer';
$zmSlangFileColours          = 'File Colours';
$zmSlangFile                 = 'File';
$zmSlangFilePath             = 'File Path';
$zmSlangFilterPx             = 'Filter px';
$zmSlangFilters              = 'Filters';
$zmSlangFilterUnset          = 'You must specify a filter width and height';
$zmSlangFirst                = 'Eerste';
$zmSlangFlippedHori          = 'Flipped Horizontally';
$zmSlangFlippedVert          = 'Flipped Vertically';
$zmSlangFocus                = 'Focus';
$zmSlangForceAlarm           = 'Forceeer&nbsp;alarm';
$zmSlangFormat               = 'Format';
$zmSlangFPS                  = 'fps';
$zmSlangFPSReportInterval    = 'FPS rapport interval';
$zmSlangFrame                = 'Frame';
$zmSlangFrameId              = 'Frame id';
$zmSlangFrameRate            = 'Frame rate';
$zmSlangFrames               = 'Frames';
$zmSlangFrameSkip            = 'Frame overgeslagen';
$zmSlangFTP                  = 'FTP';
$zmSlangFunc                 = 'Func';
$zmSlangFunction             = 'Functie';
$zmSlangGain                 = 'Gain';
$zmSlangGeneral              = 'General';
$zmSlangGenerateVideo        = 'Genereer Video';
$zmSlangGeneratingVideo      = 'Genereren Video';
$zmSlangGoToZoneMinder       = 'ga naar ZoneMinder.com';
$zmSlangGrey                 = 'Grijs';
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
$zmSlangHighBW               = 'Hoog&nbsp;B/W';
$zmSlangHigh                 = 'Hoog';
$zmSlangHome                 = 'Home';
$zmSlangHour                 = 'Uur';
$zmSlangHue                  = 'Hue';
$zmSlangId                   = 'Id';
$zmSlangIdle                 = 'Ongebruikt';
$zmSlangIgnore               = 'Negeer';
$zmSlangImageBufferSize      = 'Image buffer grootte (frames)';
$zmSlangImage                = 'Image';
$zmSlangImages               = 'Images';
$zmSlangInclude              = 'voeg in';
$zmSlangIn                   = 'In';
$zmSlangInverted             = 'omgedraaid';
$zmSlangIris                 = 'Iris';
$zmSlangKeyString            = 'Key String';
$zmSlangLabel                = 'Label';
$zmSlangLanguage             = 'Taal';
$zmSlangLast                 = 'Laatste';
$zmSlangLimitResultsPost     = 'resultaten;'; // This is used at the end of the phrase 'Limit to first N results only'
$zmSlangLimitResultsPre      = 'beperk tot eerste'; // This is used at the beginning of the phrase 'Limit to first N results only'
$zmSlangLinkedMonitors       = 'Linked Monitors';
$zmSlangList                 = 'List';
$zmSlangLoad                 = 'Load';
$zmSlangLocal                = 'Lokaal';
$zmSlangLoggedInAs           = 'Ingelogd als';
$zmSlangLoggingIn            = 'In loggen';
$zmSlangLogin                = 'Login';
$zmSlangLogout               = 'Logout';
$zmSlangLowBW                = 'Laag&nbsp;B/W';
$zmSlangLow                  = 'Laag';
$zmSlangMain                 = 'Main';
$zmSlangMan                  = 'Man';
$zmSlangManual               = 'Manual';
$zmSlangMark                 = 'Markeer';
$zmSlangMaxBandwidth         = 'Max Bandwidth';
$zmSlangMaxBrScore           = 'Max.<br/>score';
$zmSlangMaxFocusRange        = 'Max Focus Range';
$zmSlangMaxFocusSpeed        = 'Max Focus Speed';
$zmSlangMaxFocusStep         = 'Max Focus Step';
$zmSlangMaxGainRange         = 'Max Gain Range';
$zmSlangMaxGainSpeed         = 'Max Gain Speed';
$zmSlangMaxGainStep          = 'Max Gain Step';
$zmSlangMaximumFPS           = 'Maximum FPS';
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
$zmSlangMediumBW             = 'Medium&nbsp;B/W';
$zmSlangMedium               = 'Medium';
$zmSlangMinAlarmAreaLtMax    = 'Minimum alarm area should be less than maximum';
$zmSlangMinAlarmAreaUnset    = 'You must specify the minimum alarm pixel count';
$zmSlangMinBlobAreaLtMax     = 'minimum blob gebied moet kleiner zijn dan maximum blob gebied';
$zmSlangMinBlobAreaUnset     = 'You must specify the minimum blob pixel count';
$zmSlangMinBlobLtMinFilter   = 'Minimum blob area should be less than or equal to minimum filter area';
$zmSlangMinBlobsLtMax        = 'minimum blobs moet kleiner zijn dan maximum blobs';
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
$zmSlangMinPixelThresLtMax   = 'minimum pixel kleurdiepte moet kleiner zijn dan maximum pixel threshold';
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
$zmSlangMonitors             = 'Monitoren';
$zmSlangMontage              = 'Montage';
$zmSlangMonth                = 'Maand';
$zmSlangMove                 = 'Move';
$zmSlangMustBeGe             = 'Moet groter zijn of gelijk aan';
$zmSlangMustBeLe             = 'Moet kleiner zijn of gelijk aan';
$zmSlangMustConfirmPassword  = 'Je moet je wachtwoord bevestigen';
$zmSlangMustSupplyPassword   = 'Je moet een wachtwoord geven';
$zmSlangMustSupplyUsername   = 'Je moet een gebruikersnaam geven';
$zmSlangName                 = 'Naam';
$zmSlangNear                 = 'Near';
$zmSlangNetwork              = 'Netwerk';
$zmSlangNewGroup             = 'New Group';
$zmSlangNewLabel             = 'New Label';
$zmSlangNew                  = 'Nieuw';
$zmSlangNewPassword          = 'Nieuw Wachtwoord';
$zmSlangNewState             = 'Nieuwe Status';
$zmSlangNewUser              = 'Nieuwe gebruiker';
$zmSlangNext                 = 'Volgende';
$zmSlangNoFramesRecorded     = 'Er zijn geen frames opgenomen voor deze gebeurtenis';
$zmSlangNoGroup              = 'No Group';
$zmSlangNoneAvailable        = 'geen beschikbaar';
$zmSlangNo                   = 'Nee';
$zmSlangNone                 = 'Geen';
$zmSlangNormal               = 'Normaal';
$zmSlangNoSavedFilters       = 'GeenOpgeslagenFilters';
$zmSlangNoStatisticsRecorded = 'er zijn geen statistieken opgenomen voor dit event/frame';
$zmSlangNotes                = 'Notes';
$zmSlangNumPresets           = 'Num Presets';
$zmSlangOff                  = 'Off';
$zmSlangOn                   = 'On';
$zmSlangOpen                 = 'Open';
$zmSlangOpEq                 = 'gelijk aan';
$zmSlangOpGtEq               = 'groter dan of gelijk aan';
$zmSlangOpGt                 = 'groter dan';
$zmSlangOpIn                 = 'in set';
$zmSlangOpLtEq               = 'kleiner dan of gelijk aan';
$zmSlangOpLt                 = 'kleiner dan';
$zmSlangOpMatches            = 'Komt overeen';
$zmSlangOpNe                 = 'niet gelijk aan';
$zmSlangOpNotIn              = 'niet in set';
$zmSlangOpNotMatches         = 'Komt niet overeen';
$zmSlangOptionHelp           = 'OptieHelp';
$zmSlangOptionRestartWarning = 'Deze veranderingen passen niet aan\nals het systeem loopt. Als je\nKlaar bent met veranderen vergeet dan niet dat\nje ZoneMinder herstart.';
$zmSlangOptions              = 'Opties';
$zmSlangOrder                = 'Order';
$zmSlangOrEnterNewName       = 'of voer een nieuwe naam in';
$zmSlangOrientation          = 'Orientatie';
$zmSlangOut                  = 'Out';
$zmSlangOverwriteExisting    = 'Overschrijf bestaande';
$zmSlangPaged                = 'Paged';
$zmSlangPanLeft              = 'Pan Left';
$zmSlangPan                  = 'Pan';
$zmSlangPanRight             = 'Pan Right';
$zmSlangPanTilt              = 'Pan/Tilt';
$zmSlangParameter            = 'Parameter';
$zmSlangPasswordsDifferent   = 'Het nieuwe en bevestigde wachtwoord zijn verschillend';
$zmSlangPassword             = 'Wachtwoord';
$zmSlangPaths                = 'Paden';
$zmSlangPhoneBW              = 'Telefoon&nbsp;B/W';
$zmSlangPhone                = 'Phone';
$zmSlangPixelDiff            = 'Pixel Diff';
$zmSlangPixels               = 'pixels';
$zmSlangPlayAll              = 'Play All';
$zmSlangPleaseWait           = 'wacht A.U.B.';
$zmSlangPoint                = 'Point';
$zmSlangPostEventImageBuffer = 'Post gebeurtenis Image Buffer';
$zmSlangPreEventImageBuffer  = 'Pre gebeurtenis Image Buffer';
$zmSlangPreset               = 'Preset';
$zmSlangPresets              = 'Presets';
$zmSlangPrev                 = 'vorige';
$zmSlangRate                 = 'Waardering';
$zmSlangReal                 = 'Echte';
$zmSlangRecord               = 'Record';
$zmSlangRefImageBlendPct     = 'Referentie Image Blend %ge';
$zmSlangRefresh              = 'Ververs';
$zmSlangRemoteHostName       = 'Remote Host Naam';
$zmSlangRemoteHostPath       = 'Remote Host Pad';
$zmSlangRemoteHostPort       = 'Remote Host Poort';
$zmSlangRemoteImageColours   = 'Remote Image kleuren';
$zmSlangRemote               = 'Remote';
$zmSlangRename               = 'Hernoem';
$zmSlangReplay               = 'Herhaal';
$zmSlangResetEventCounts     = 'Reset gebeurtenis teller';
$zmSlangReset                = 'Reset';
$zmSlangRestart              = 'herstart';
$zmSlangRestarting           = 'herstarten';
$zmSlangRestrictedCameraIds  = 'Verboden Camera Ids';
$zmSlangRestrictedMonitors   = 'Restricted Monitors';
$zmSlangReturnDelay          = 'Return Delay';
$zmSlangReturnLocation       = 'Return Location';
$zmSlangRotateLeft           = 'Draai linksom';
$zmSlangRotateRight          = 'Draai rechtsom';
$zmSlangRunMode              = 'Run Mode';
$zmSlangRunning              = 'Running';
$zmSlangRunState             = 'Run Status';
$zmSlangSaveAs               = 'opslaan als';
$zmSlangSaveFilter           = 'opslaan Filter';
$zmSlangSave                 = 'Opslaan';
$zmSlangScale                = 'Schaal';
$zmSlangScore                = 'Score';
$zmSlangSecs                 = 'Secs';
$zmSlangSectionlength        = 'Sectie lengte';
$zmSlangSelectMonitors       = 'Select Monitors';
$zmSlangSelect               = 'Select';
$zmSlangSelfIntersecting     = 'Polygon edges must not intersect';
$zmSlangSetLearnPrefs        = 'Set Learn Prefs'; // This can be ignored for now
$zmSlangSetNewBandwidth      = 'Zet Nieuwe Bandbreedte';
$zmSlangSetPreset            = 'Set Preset';
$zmSlangSet                  = 'Set';
$zmSlangSettings             = 'Instellingen';
$zmSlangShowFilterWindow     = 'ToonFilterWindow';
$zmSlangShowTimeline         = 'Show Timeline';
$zmSlangSize                 = 'Size';
$zmSlangSleep                = 'Sleep';
$zmSlangSortAsc              = 'Opl.';
$zmSlangSortBy               = 'Sorteer op';
$zmSlangSortDesc             = 'afl.';
$zmSlangSource               = 'Bron';
$zmSlangSourceType           = 'Bron Type';
$zmSlangSpeedHigh            = 'High Speed';
$zmSlangSpeedLow             = 'Low Speed';
$zmSlangSpeedMedium          = 'Medium Speed';
$zmSlangSpeed                = 'Speed';
$zmSlangSpeedTurbo           = 'Turbo Speed';
$zmSlangStart                = 'Start';
$zmSlangState                = 'Status';
$zmSlangStats                = 'Stats';
$zmSlangStatus               = 'Status';
$zmSlangStepLarge            = 'Large Step';
$zmSlangStepMedium           = 'Medium Step';
$zmSlangStepNone             = 'No Step';
$zmSlangStepSmall            = 'Small Step';
$zmSlangStep                 = 'Step';
$zmSlangStills               = 'Plaatjes';
$zmSlangStopped              = 'gestopt';
$zmSlangStop                 = 'Stop';
$zmSlangStream               = 'Stroom';
$zmSlangSubmit               = 'Submit';
$zmSlangSystem               = 'Systeem';
$zmSlangTele                 = 'Tele';
$zmSlangThumbnail            = 'Thumbnail';
$zmSlangTilt                 = 'Tilt';
$zmSlangTimeDelta            = 'Tijd Delta';
$zmSlangTimeline             = 'Timeline';
$zmSlangTimestampLabelFormat = 'Tijdstempel Label Format';
$zmSlangTimestampLabelX      = 'Tijdstempel Label X';
$zmSlangTimestampLabelY      = 'Tijdstempel Label Y';
$zmSlangTimestamp            = 'Tijdstempel';
$zmSlangTimeStamp            = 'Tijdstempel';
$zmSlangTime                 = 'Tijd';
$zmSlangToday                = 'Today';
$zmSlangTools                = 'Gereedschappen';
$zmSlangTotalBrScore         = 'Totaal<br/>Score';
$zmSlangTrackDelay           = 'Track Delay';
$zmSlangTrackMotion          = 'Track Motion';
$zmSlangTriggers             = 'Triggers';
$zmSlangTurboPanSpeed        = 'Turbo Pan Speed';
$zmSlangTurboTiltSpeed       = 'Turbo Tilt Speed';
$zmSlangType                 = 'Type';
$zmSlangUnarchive            = 'Dearchiveer';
$zmSlangUnits                = 'Eenheden';
$zmSlangUnknown              = 'Onbekend';
$zmSlangUpdateAvailable      = 'een update voor ZoneMinder is beschikbaar';
$zmSlangUpdateNotNecessary   = 'geen update noodzakelijk';
$zmSlangUpdate               = 'Update';
$zmSlangUseFilterExprsPost   = '&nbsp;filter&nbsp;expressies'; // This is used at the end of the phrase 'use N filter expressions'
$zmSlangUseFilterExprsPre    = 'Gebruik&nbsp;'; // This is used at the beginning of the phrase 'use N filter expressions'
$zmSlangUseFilter            = 'Gebruik Filter';
$zmSlangUser                 = 'Gebruiker';
$zmSlangUsername             = 'Gebruikersnaam';
$zmSlangUsers                = 'Gebruikers';
$zmSlangValue                = 'Waarde';
$zmSlangVersionIgnore        = 'negeer deze versie';
$zmSlangVersionRemindDay     = 'herinner me na 1 dag';
$zmSlangVersionRemindHour    = 'herinner me na 1 uur';
$zmSlangVersionRemindNever   = 'herinner me niet aan nieuwe versies';
$zmSlangVersionRemindWeek    = 'herinner me na 1 week';
$zmSlangVersion              = 'Versie';
$zmSlangVideoFormat          = 'Video Format';
$zmSlangVideoGenFailed       = 'Video Generatie mislukt!';
$zmSlangVideoGenFiles        = 'Existing Video Files';
$zmSlangVideoGenNoFiles      = 'No Video Files Found';
$zmSlangVideoGenParms        = 'Video Generatie Parameters';
$zmSlangVideoGenSucceeded    = 'Video Generation Succeeded!';
$zmSlangVideoSize            = 'Video grootte';
$zmSlangVideo                = 'Video';
$zmSlangViewAll              = 'Bekijk Alles';
$zmSlangView                 = 'Bekijk';
$zmSlangViewEvent            = 'View Event';
$zmSlangViewPaged            = 'Bekijk Paged';
$zmSlangWake                 = 'Wake';
$zmSlangWarmupFrames         = 'Warmup Frames';
$zmSlangWatch                = 'Observeer';
$zmSlangWebColour            = 'Web Colour';
$zmSlangWeb                  = 'Web';
$zmSlangWeek                 = 'Week';
$zmSlangWhiteBalance         = 'White Balance';
$zmSlangWhite                = 'White';
$zmSlangWide                 = 'Wide';
$zmSlangX10ActivationString  = 'X10 Activatie String';
$zmSlangX10InputAlarmString  = 'X10 Input Alarm String';
$zmSlangX10OutputAlarmString = 'X10 Output Alarm String';
$zmSlangX10                  = 'X10';
$zmSlangX                    = 'X';
$zmSlangYes                  = 'Ja';
$zmSlangYouNoPerms           = 'Je hebt niet de rechten om toegang te krijgen tot deze bronnen.';
$zmSlangY                    = 'Y';
$zmSlangZoneAlarmColour      = 'Alarm Kleur (Red/Green/Blue)';
$zmSlangZoneAlarmThreshold   = 'Alarm Drempel (0-255)';
$zmSlangZoneArea             = 'Zone Area';
$zmSlangZoneFilterSize       = 'Filter Width/Height (pixels)';
$zmSlangZoneMinMaxAlarmArea  = 'Min/Max Alarmed Area';
$zmSlangZoneMinMaxBlobArea   = 'Min/Max Blob Area';
$zmSlangZoneMinMaxBlobs      = 'Min/Max Blobs';
$zmSlangZoneMinMaxFiltArea   = 'Min/Max Filtered Area';
$zmSlangZoneMinMaxPixelThres = 'Min/Max Pixel Threshold (0-255)';
$zmSlangZones                = 'Zones';
$zmSlangZone                 = 'Zone';
$zmSlangZoomIn               = 'Zoom In';
$zmSlangZoomOut              = 'Zoom Out';
$zmSlangZoom                 = 'Zoom';


// Complex replacements with formatting and/or placements, must be passed through sprintf
$zmClangCurrentLogin         = 'huidige login is \'%1$s\'';
$zmClangEventCount           = '%1$s %2$s'; // Als voorbeeld '37 gebeurtenissen' (from Vlang below)
$zmClangLastEvents           = 'Last %1$s %2$s'; // Als voorbeeld 'Laatste 37 gebeurtenissen' (from Vlang below)
$zmClangLatestRelease        = 'de laatste release is v%1$s, jij hebt v%2$s.';
$zmClangMonitorCount         = '%1$s %2$s'; // Als voorbeeld '4 Monitoren' (from Vlang below)
$zmClangMonitorFunction      = 'Monitor %1$s Functie';
$zmClangRunningRecentVer     = 'Je draait al met de laatste versie van ZoneMinder, v%s.';

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
$zmVlangEvent                = array( 0=>'gebeurtenissen', 1=>'gebeurtenis', 2=>'gebeurtenissen' );
$zmVlangMonitor              = array( 0=>'Monitoren', 1=>'Monitor', 2=>'Monitoren' );

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
