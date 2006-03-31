<?php
//
// ZoneMinder web Swedish language file, $Date$, $Revision$
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

// ZoneMinder Swedish Translation by Mikael Carlsson

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
$zmSlang24BitColour          = '24 bitars färg';
$zmSlang8BitGrey             = '8 bit gråskala';
$zmSlangAction               = 'Action';
$zmSlangActual               = 'Verklig';
$zmSlangAddNewControl        = 'Ny kontroll';
$zmSlangAddNewMonitor        = 'Ny bevakare';
$zmSlangAddNewUser           = 'Ny användare';
$zmSlangAddNewZone           = 'Ny zon';
$zmSlangAlarmBrFrames        = 'Larm<br/>ramar';
$zmSlangAlarmFrameCount      = 'Larmramsräknare';
$zmSlangAlarmFrame           = 'Larmram';
$zmSlangAlarm                = 'Larm';
$zmSlangAlarmLimits          = 'Larmgränser';
$zmSlangAlarmMaximumFPS      = 'Alarm Maximum FPS';
$zmSlangAlarmPx              = 'Larm Pix';
$zmSlangAlarmRGBUnset        = 'Du måste sätta en lam RGB färg';
$zmSlangAlert                = 'Varning';
$zmSlangAll                  = 'Alla';
$zmSlangApplyingStateChange  = 'Aktivera statusändring';
$zmSlangApply                = 'Lägg till';
$zmSlangArchArchived         = 'Arkivera endast';
$zmSlangArchive              = 'Arkiv';
$zmSlangArchived             = 'Arkiverad';
$zmSlangArchUnarchived       = 'Unarchived Only';
$zmSlangArea                 = 'Area';
$zmSlangAreaUnits            = 'Area (px/%)';
$zmSlangAttrAlarmFrames      = 'Larmramar';
$zmSlangAttrArchiveStatus    = 'Arkivstatus';
$zmSlangAttrAvgScore         = 'Ung. värde';
$zmSlangAttrCause            = 'Orsak';
$zmSlangAttrDate             = 'Datum';
$zmSlangAttrDateTime         = 'Datum/Tid';
$zmSlangAttrDiskBlocks       = 'Diskblock';
$zmSlangAttrDiskPercent      = 'Diskprocent';
$zmSlangAttrDuration         = 'Längd';
$zmSlangAttrFrames           = 'Ramar';
$zmSlangAttrId               = 'Id';
$zmSlangAttrMaxScore         = 'Max. värde';
$zmSlangAttrMonitorId        = 'Bevakningsid';
$zmSlangAttrMonitorName      = 'Bevakningsnamn';
$zmSlangAttrName             = 'Namn';
$zmSlangAttrNotes            = 'Not';
$zmSlangAttrTime             = 'Tid';
$zmSlangAttrTotalScore       = 'Totalvärde';
$zmSlangAttrWeekday          = 'Veckodag';
$zmSlangAutoArchiveAbbr      = 'Arkivera';
$zmSlangAutoArchiveEvents    = 'Arkivera alla träffar automatiskt';
$zmSlangAuto                 = 'Auto';
$zmSlangAutoDeleteAbbr       = 'Radera';
$zmSlangAutoDeleteEvents     = 'Radera alla träffar automatiskt';
$zmSlangAutoEmailAbbr        = 'E-post';
$zmSlangAutoEmailEvents      = 'Skicka e-post med detaljer om alla träffar auyomatiskt';
$zmSlangAutoExecuteAbbr      = 'Utför';
$zmSlangAutoExecuteEvents    = 'Exekvera kommando på alla träffar automatiskt';
$zmSlangAutoMessageAbbr      = 'Meddelande';
$zmSlangAutoMessageEvents    = 'Meddela detaljer om alla träffar automatiskt';
$zmSlangAutoStopTimeout      = 'Auto Stop Timeout';
$zmSlangAutoUploadAbbr       = 'Ladda upp';
$zmSlangAutoUploadEvents     = 'Ladda upp alla träffar automatiskt';
$zmSlangAutoVideoAbbr        = 'Video';
$zmSlangAutoVideoEvents      = 'Skapa video för alla träffar automatiskt';
$zmSlangAvgBrScore           = 'Ung.<br/>träff';
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
$zmSlangBadNameChars         = 'Namn kan endast innehålla alfanumeriska tecken, bindestreck och understreck';
$zmSlangBadPath              = 'Path must be set to a valid value';
$zmSlangBadPort              = 'Port must be set to a valid number';
$zmSlangBadPostEventCount    = 'Post event image buffer must be an integer of zero or more';
$zmSlangBadPreEventCount     = 'Pre event image buffer must be at least zero, and less than image buffer size';
$zmSlangBadRefBlendPerc      = 'Reference blendpercentage must be a positive integer';
$zmSlangBadSectionLength     = 'Section length must be an integer of 30 or more';
$zmSlangBadWarmupCount       = 'Warmup frames must be an integer of zero or more';
$zmSlangBadWebColour         = 'Web colour must be a valid web colour string';
$zmSlangBadWidth             = 'Width must be set to a valid value';
$zmSlangBandwidth            = 'Bandbredd';
$zmSlangBlobPx               = 'Blob Px';
$zmSlangBlobs                = 'Blobbar';
$zmSlangBlobSizes            = 'Blobstorlek';
$zmSlangBrightness           = 'Ljusstyrka';
$zmSlangBuffers              = 'Buffrar';
$zmSlangCanAutoFocus         = 'Har autofokus';
$zmSlangCanAutoGain          = 'Har autonivå';
$zmSlangCanAutoIris          = 'Har autoiris';
$zmSlangCanAutoWhite         = 'Har autovitbalans.';
$zmSlangCanAutoZoom          = 'Har autozoom';
$zmSlangCancel               = 'Ångra';
$zmSlangCancelForcedAlarm    = 'Ångra&nbsp;tvingande&nbsp;larm';
$zmSlangCanFocusAbs          = 'Har absolut fokus';
$zmSlangCanFocusCon          = 'har kontinuerlig fokus';
$zmSlangCanFocus             = 'Har fokus';
$zmSlangCanFocusRel          = 'Har relativ fokus';
$zmSlangCanGainAbs           = 'Har absolut nivå';
$zmSlangCanGainCon           = 'Har kontinuerlig nivå';
$zmSlangCanGain              = 'Har nivå';
$zmSlangCanGainRel           = 'Har relativ nivå';
$zmSlangCanIrisAbs           = 'Har absolut iris';
$zmSlangCanIrisCon           = 'Har kontinuerlig iris';
$zmSlangCanIris              = 'Har iris';
$zmSlangCanIrisRel           = 'Har relativ iris';
$zmSlangCanMoveAbs           = 'Har absolut förflyttning';
$zmSlangCanMoveCon           = 'Har kontinuerlig förflyttning';
$zmSlangCanMoveDiag          = 'Har diagonal förflyttning';
$zmSlangCanMove              = 'Har förflyttning';
$zmSlangCanMoveMap           = 'Har mappad förflyttning';
$zmSlangCanMoveRel           = 'Har relativ förflyttning';
$zmSlangCanPan               = 'Har panorering';
$zmSlangCanReset             = 'Har återställning';
$zmSlangCanSetPresets        = 'Har förinställningar';
$zmSlangCanSleep             = 'Kan vila';
$zmSlangCanTilt              = 'Kan tilta';
$zmSlangCanWake              = 'Kan vakna';
$zmSlangCanWhiteAbs          = 'Har absolut vitbalans';
$zmSlangCanWhiteBal          = 'Kan vitbalans';
$zmSlangCanWhiteCon          = 'Kan kontinuerligt vitbalansera';
$zmSlangCanWhite             = 'Kan vitbalansera';
$zmSlangCanWhiteRel          = 'Kan relativt vitbalansera';
$zmSlangCanZoomAbs           = 'Kan zooma absolut';
$zmSlangCanZoomCon           = 'Kan zooma kontinuerligt';
$zmSlangCanZoom              = 'Kan zooma';
$zmSlangCanZoomRel           = 'Kan zooma realativt';
$zmSlangCaptureHeight        = 'Fångsthöjd';
$zmSlangCapturePalette       = 'Fångstpalett';
$zmSlangCaptureWidth         = 'Fångstbredd';
$zmSlangCause                = 'Orsak';
$zmSlangCheckMethod          = 'Larmkontrollmetod';
$zmSlangChooseFilter         = 'Välj filter';
$zmSlangChoosePreset         = 'Välj standard';
$zmSlangClose                = 'Stäng';
$zmSlangColour               = 'Färg';
$zmSlangCommand              = 'Kommando';
$zmSlangConfig               = 'Konfigurera';
$zmSlangConfiguredFor        = 'Konfigurerad för';
$zmSlangConfirmDeleteEvents  = 'Are you sure you wish to delete the selected events?';
$zmSlangConfirmPassword      = 'Bekräfta lösenord';
$zmSlangConjAnd              = 'och';
$zmSlangConjOr               = 'eller';
$zmSlangConsole              = 'Konsoll';
$zmSlangContactAdmin         = 'Kontakta din administratör för detaljer.';
$zmSlangContinue             = 'Fortsätt';
$zmSlangContrast             = 'Kontrast';
$zmSlangControlAddress       = 'Kontrolladress';
$zmSlangControlCap           = 'Control Capability';
$zmSlangControlCaps          = 'Control Capabilities';
$zmSlangControlDevice        = 'Kontrollenhet';
$zmSlangControl              = 'Kontroll';
$zmSlangControllable         = 'Kontrollerbar';
$zmSlangControlType          = 'Kontrolltyp';
$zmSlangCycle                = 'Cycle';
$zmSlangCycleWatch           = 'Cycle Watch';
$zmSlangDay                  = 'Dag';
$zmSlangDebug                = 'Avlusa';
$zmSlangDefaultRate          = 'Standard hastighet';
$zmSlangDefaultScale         = 'Standardskala';
$zmSlangDeleteAndNext        = 'Radera &amp; Nästa';
$zmSlangDeleteAndPrev        = 'Radera &amp; Föreg.';
$zmSlangDelete               = 'Radera';
$zmSlangDeleteSavedFilter    = 'Radera sparade filter';
$zmSlangDescription          = 'Beskrivning';
$zmSlangDeviceChannel        = 'Enhetskanal';
$zmSlangDeviceFormat         = 'Enhetsformat (0=PAL,1=NTSC etc)';
$zmSlangDeviceNumber         = 'Enhetsnummer (/dev/video?)';
$zmSlangDevicePath           = 'Enhetssökväg';
$zmSlangDimensions           = 'Dimensioner';
$zmSlangDisableAlarms        = 'Avaktivera larm';
$zmSlangDisk                 = 'Disk';
$zmSlangDonateAlready        = 'Nej, Jag har redan donerat';
$zmSlangDonateEnticement     = 'Du har kört ZoneMinder ett tag nu och förhoppningsvis har du sett att det fungerar bra hemma eller på ditt företag. Även om ZoneMinder är, och kommer att vara, fri programvara och öppen kallkod, så kostar det pengar att utveckla och underhålla. Om du vill hjälpa till med framtida utveckling och nya funktioner så var vanlig och bidrag med en slant. Bidragen är naturligtvis en option men mycket uppskattade och du kan bidra med precis hur mycket du vill.<br><br>Om du vill ge ett bidrag väljer du nedan eller surfar till http://www.zoneminder.com/donate.html.<br><br>Tack för att du använder ZoneMinder, glöm inte att besöka forumen på ZoneMinder.com för support och förslag om hur du får din ZoneMinder att fungera lite bättre.';
$zmSlangDonateRemindDay      = 'Inte än, påminn om 1 dag';
$zmSlangDonateRemindHour     = 'Inte än, påminn om en 1 timme';
$zmSlangDonateRemindMonth    = 'Inte än, påminn om 1 månad';
$zmSlangDonateRemindNever    = 'Nej, Jag vill inte donera, påminn mig inte mer';
$zmSlangDonateRemindWeek     = 'Inte än, påminn om 1 vecka';
$zmSlangDonate               = 'Var vänlig och donera';
$zmSlangDonateYes            = 'Ja, jag vill gärna donera nu';
$zmSlangDownload             = 'Ladda ner';
$zmSlangDuration             = 'Längd';
$zmSlangEdit                 = 'Redigera';
$zmSlangEmail                = 'E-post';
$zmSlangEnableAlarms         = 'Aktivera larm';
$zmSlangEnabled              = 'Aktiverad';
$zmSlangEnterNewFilterName   = 'Mata in nytt filternamn';
$zmSlangErrorBrackets        = 'Fel, kontrollera att du har samma antal vänster som höger-hakar';
$zmSlangError                = 'Fel';
$zmSlangErrorValidValue      = 'Fel, kontrollera att alla terms har giltligt värde';
$zmSlangEtc                  = 'etc';
$zmSlangEventFilter          = 'Händelsefilter';
$zmSlangEvent                = 'Händelse';
$zmSlangEventId              = 'Händelse nr';
$zmSlangEventName            = 'Händelsenamn';
$zmSlangEventPrefix          = 'Händelseprefix';
$zmSlangEvents               = 'Händelser';
$zmSlangExclude              = 'Exkludera';
$zmSlangExportDetails        = 'Exportera händelsedetaljer';
$zmSlangExport               = 'Exportera';
$zmSlangExportFailed         = 'Exporten misslyckades';
$zmSlangExportFormat         = 'Exportera fileformat';
$zmSlangExportFormatTar      = 'Tar';
$zmSlangExportFormatZip      = 'Zip';
$zmSlangExportFrames         = 'Exportera ramdetaljer';
$zmSlangExportImageFiles     = 'Exportera bildfiler';
$zmSlangExporting            = 'Exporterar';
$zmSlangExportMiscFiles      = 'Exportera andra filer (om dom finns)';
$zmSlangExportOptions        = 'Konfiguera export';
$zmSlangExportVideoFiles     = 'Exportera videofiler (om dom finns)';
$zmSlangFar                  = 'Far';
$zmSlangFeed                 = 'Matning';
$zmSlangFileColours          = 'Filfärg';
$zmSlangFile                 = 'Fil';
$zmSlangFilePath             = 'Sökvag';
$zmSlangFilterPx             = 'Filter Px';
$zmSlangFilters              = 'Filter';
$zmSlangFilterUnset          = 'Du måste specificera filtrets bredd och höjd';
$zmSlangFirst                = 'Först';
$zmSlangFlippedHori          = 'Vänd horisontellt';
$zmSlangFlippedVert          = 'Vänd vertikalt';
$zmSlangFocus                = 'Fokus';
$zmSlangForceAlarm           = 'Tvinga&nbsp;larm';
$zmSlangFormat               = 'Format';
$zmSlangFPS                  = 'fps';
$zmSlangFPSReportInterval    = 'FPS rapportintervall';
$zmSlangFrameId              = 'Ram id';
$zmSlangFrame                = 'Ram';
$zmSlangFrameRate            = 'Ram hastighet';
$zmSlangFrameSkip            = 'Hoppa över ram';
$zmSlangFrames               = 'Ramar';
$zmSlangFTP                  = 'FTP';
$zmSlangFunc                 = 'Funk';
$zmSlangFunction             = 'Funktion';
$zmSlangGain                 = 'Nivå';
$zmSlangGeneral              = 'Generell';
$zmSlangGenerateVideo        = 'Skapa video';
$zmSlangGeneratingVideo      = 'Skapar video';
$zmSlangGoToZoneMinder       = 'Gå till ZoneMinder.com';
$zmSlangGrey                 = 'Grå';
$zmSlangGroup                = 'Group';
$zmSlangGroups               = 'Grupper';
$zmSlangHasFocusSpeed        = 'Har focushastighet';
$zmSlangHasGainSpeed         = 'Har nivåhastighet';
$zmSlangHasHomePreset        = 'Har normalinställning';
$zmSlangHasIrisSpeed         = 'Har irishastighet';
$zmSlangHasPanSpeed          = 'Har panoramahastighet';
$zmSlangHasPresets           = 'Har förinställningar';
$zmSlangHasTiltSpeed         = 'Har tilthastighet';
$zmSlangHasTurboPan          = 'Har turbopanorering';
$zmSlangHasTurboTilt         = 'Har turbotilt';
$zmSlangHasWhiteSpeed        = 'Har vitbalanshastighet';
$zmSlangHasZoomSpeed         = 'Har Zoomhastighet';
$zmSlangHighBW               = 'Hög&nbsp;B/W';
$zmSlangHigh                 = 'Hög';
$zmSlangHome                 = 'Hem';
$zmSlangHour                 = 'Timme';
$zmSlangHue                  = 'Hue';
$zmSlangIdle                 = 'Vila';
$zmSlangId                   = 'nr';
$zmSlangIgnore               = 'Ignorera';
$zmSlangImage                = 'Bild';
$zmSlangImageBufferSize      = 'Bildbufferstorlek (ramar)';
$zmSlangInclude              = 'Inkludera';
$zmSlangIn                   = 'I';
$zmSlangInverted             = 'Inverterad';
$zmSlangIris                 = 'Iris';
$zmSlangLanguage             = 'Språk';
$zmSlangLast                 = 'Sist';
$zmSlangLimitResultsPost     = 'resultaten;'; // This is used at the end of the phrase 'Limit to first N results only'
$zmSlangLimitResultsPre      = 'Begränsa till första'; // This is used at the beginning of the phrase 'Limit to first N results only'
$zmSlangLinkedMonitors       = 'Linked Monitors';
$zmSlangList                 = 'Lista';
$zmSlangLoad                 = 'Belastning';
$zmSlangLocal                = 'Lokal';
$zmSlangLoggedInAs           = 'Inloggad som';
$zmSlangLoggingIn            = 'Loggar in';
$zmSlangLogin                = 'Logga in';
$zmSlangLogout               = 'Logga ut';
$zmSlangLowBW                = 'Låg&nbsp;B/W';
$zmSlangLow                  = 'Låg';
$zmSlangMain                 = 'Huvudmeny';
$zmSlangMan                  = 'Man';
$zmSlangManual               = 'Manuell';
$zmSlangMark                 = 'Markera';
$zmSlangMaxBandwidth         = 'Max bandbredd';
$zmSlangMaxBrScore           = 'Max.<br/>Score';
$zmSlangMaxFocusRange        = 'Max fokusområde';
$zmSlangMaxFocusSpeed        = 'Max fokushastighet';
$zmSlangMaxFocusStep         = 'Max fokussteg';
$zmSlangMaxGainRange         = 'Max nivåområde';
$zmSlangMaxGainSpeed         = 'Max nivåhastighet';
$zmSlangMaxGainStep          = 'Max nivåsteg';
$zmSlangMaximumFPS           = 'Max FPS';
$zmSlangMaxIrisRange         = 'Max irsiområde';
$zmSlangMaxIrisSpeed         = 'Max irishastighet';
$zmSlangMaxIrisStep          = 'Max irissteg';
$zmSlangMax                  = 'Max';
$zmSlangMaxPanRange          = 'Max panoramaområde';
$zmSlangMaxPanSpeed          = 'Max panoramahastighet';
$zmSlangMaxPanStep           = 'Max panoramasteg';
$zmSlangMaxTiltRange         = 'Max tiltområde';
$zmSlangMaxTiltSpeed         = 'Max tilthastighet';
$zmSlangMaxTiltStep          = 'Max tiltsteg';
$zmSlangMaxWhiteRange        = 'Max vitbalansområde';
$zmSlangMaxWhiteSpeed        = 'Max vitbalanshastighet';
$zmSlangMaxWhiteStep         = 'Max vitbalanssteg';
$zmSlangMaxZoomRange         = 'Max zoomområde';
$zmSlangMaxZoomSpeed         = 'Max zoomhastighet';
$zmSlangMaxZoomStep          = 'Max zoomsteg';
$zmSlangMediumBW             = 'Mellan&nbsp;B/W';
$zmSlangMedium               = 'Mellan';
$zmSlangMinAlarmAreaLtMax    = 'Minsta larmarean skall vara mindre än största';
$zmSlangMinAlarmAreaUnset    = 'Du måste ange minsta antal larmpixlar';
$zmSlangMinBlobAreaLtMax     = 'Minsta blobarean skall vara mindre än högsta';
$zmSlangMinBlobAreaUnset     = 'Du måste ange minsta antalet blobpixlar';
$zmSlangMinBlobLtMinFilter   = 'Minsta blobarean skall vara mindre än eller lika med minsta filterarean';
$zmSlangMinBlobsLtMax        = 'Minsta antalet blobbar skall vara mindre än största';
$zmSlangMinBlobsUnset        = 'Du måste ange minsta antalet blobbar';
$zmSlangMinFilterAreaLtMax   = 'Minsta filterarean skall vara mindre än högsta';
$zmSlangMinFilterAreaUnset   = 'Du måste ange minsta antal filterpixlar';
$zmSlangMinFilterLtMinAlarm  = 'Minsta filterarean skall vara mindre än eller lika med minsta larmarean';
$zmSlangMinFocusRange        = 'Min fokusområde';
$zmSlangMinFocusSpeed        = 'Min fokushastighet';
$zmSlangMinFocusStep         = 'Min fokussteg';
$zmSlangMinGainRange         = 'Min nivåområde';
$zmSlangMinGainSpeed         = 'Min nivåhastighet';
$zmSlangMinGainStep          = 'Min nivåsteg';
$zmSlangMinIrisRange         = 'Min irisområde';
$zmSlangMinIrisSpeed         = 'Min irishastighet';
$zmSlangMinIrisStep          = 'Min irissteg';
$zmSlangMinPanRange          = 'Min panoramaområde';
$zmSlangMinPanSpeed          = 'Min panoramahastighet';
$zmSlangMinPanStep           = 'Min panoramasteg';
$zmSlangMinPixelThresLtMax   = 'Minsta pixel threshold skall vara mindre än högsta';
$zmSlangMinPixelThresUnset   = 'Du måste ange minsta pixel threshold';
$zmSlangMinTiltRange         = 'Min tiltområde';
$zmSlangMinTiltSpeed         = 'Min tilthastighet';
$zmSlangMinTiltStep          = 'Min tiltsteg';
$zmSlangMinWhiteRange        = 'Min vitbalansområde';
$zmSlangMinWhiteSpeed        = 'Min vitbalanshastighet';
$zmSlangMinWhiteStep         = 'Min vitbalanssteg';
$zmSlangMinZoomRange         = 'Min zoomområde';
$zmSlangMinZoomSpeed         = 'Min zoomhastighet';
$zmSlangMinZoomStep          = 'Min zoomsteg';
$zmSlangMisc                 = 'Övrigt';
$zmSlangMonitor              = 'Bevakning';
$zmSlangMonitorIds           = 'Bevaknings&nbsp;nr';
$zmSlangMonitorPreset        = 'Förinställd bevakning';
$zmSlangMonitorPresetIntro   = 'Välj en förinställning från listan.<br><br>Var medveten om att detta kan skriva över inställningar du redan gjort för denna bevakare.<br><br>';
$zmSlangMonitors             = 'Bevakare';
$zmSlangMontage              = 'Montera';
$zmSlangMonth                = 'Månad';
$zmSlangMove                 = 'Flytta';
$zmSlangMustBeGe             = 'måste vara större än eller lika med';
$zmSlangMustBeLe             = 'måste vara mindre än eller lika med';
$zmSlangMustConfirmPassword  = 'Du måste bekräfta lösenordet';
$zmSlangMustSupplyPassword   = 'Du måste ange ett lösenord';
$zmSlangMustSupplyUsername   = 'Du måste ange ett användarnamn';
$zmSlangName                 = 'Namn';
$zmSlangNear                 = 'Nära';
$zmSlangNetwork              = 'Nätverk';
$zmSlangNewGroup             = 'Ny grupp';
$zmSlangNew                  = 'Ny';
$zmSlangNewPassword          = 'Nytt lösenord';
$zmSlangNewState             = 'Nytt läge';
$zmSlangNewUser              = 'Ny användare';
$zmSlangNext                 = 'Nästa';
$zmSlangNoFramesRecorded     = 'Det finns inga ramar inspelade för denna händelse';
$zmSlangNoGroup              = 'Ingen grupp';
$zmSlangNoneAvailable        = 'Ingen tillgänglig';
$zmSlangNone                 = 'Ingen';
$zmSlangNo                   = 'Nej';
$zmSlangNormal               = 'Normal';
$zmSlangNoSavedFilters       = 'Inga sparade filter';
$zmSlangNoStatisticsRecorded = 'Det finns ingen statistik inspelad för denna händelse/ram';
$zmSlangNotes                = 'Not.';
$zmSlangNumPresets           = 'Antal förinställningar';
$zmSlangOpen                 = 'Öppna';
$zmSlangOpEq                 = 'lika med';
$zmSlangOpGtEq               = 'större än eller lika med';
$zmSlangOpGt                 = 'större än';
$zmSlangOpIn                 = 'in set';
$zmSlangOpLtEq               = 'mindre än eller lika med';
$zmSlangOpLt                 = 'mindre än';
$zmSlangOpMatches            = 'matchar';
$zmSlangOpNe                 = 'inte lika med';
$zmSlangOpNotIn              = 'inte i set';
$zmSlangOpNotMatches         = 'matchar inte';
$zmSlangOptionHelp           = 'Optionhjälp';
$zmSlangOptionRestartWarning = 'Dessa ändringar kommer inte att vara implementerade\nnär systemet körs. När du är klar starta om\n ZoneMinder.';
$zmSlangOptions              = 'Alternativ';
$zmSlangOrder                = 'Sortera';
$zmSlangOrEnterNewName       = 'eller skriv in nytt namn';
$zmSlangOrientation          = 'Orientation';
$zmSlangOut                  = 'Ut';
$zmSlangOverwriteExisting    = 'Skriv över';
$zmSlangPaged                = 'Paged';
$zmSlangPanLeft              = 'Panorera vänster';
$zmSlangPan                  = 'Panorera';
$zmSlangPanRight             = 'Panorera höger';
$zmSlangPanTilt              = 'Pan/Tilt';
$zmSlangParameter            = 'Parameter';
$zmSlangPassword             = 'Lösenord';
$zmSlangPasswordsDifferent   = 'Lösenorden skiljer sig åt';
$zmSlangPaths                = 'Sökvägar';
$zmSlangPhoneBW              = 'Mobil&nbsp;B/W';
$zmSlangPhone                = 'Mobil';
$zmSlangPixelDiff            = 'Pixel Diff';
$zmSlangPixels               = 'bildpunkter';
$zmSlangPlayAll              = 'Visa alla';
$zmSlangPleaseWait           = 'Vänta...';
$zmSlangPoint                = 'Punkt';
$zmSlangPostEventImageBuffer = 'Post Event Image Buffer';
$zmSlangPreEventImageBuffer  = 'Pre Event Image Buffer';
$zmSlangPreset               = 'Förinställning';
$zmSlangPresets              = 'Förinställningar';
$zmSlangPrev                 = 'Föreg.';
$zmSlangRate                 = 'Hastighet';
$zmSlangReal                 = 'Verklig';
$zmSlangRecord               = 'Spela in';
$zmSlangRefImageBlendPct     = 'Reference Image Blend %ge';
$zmSlangRefresh              = 'Uppdatera';
$zmSlangRemote               = 'Fjärr';
$zmSlangRemoteHostName       = 'Fjärrnamn';
$zmSlangRemoteHostPath       = 'Fjärrsökväg';
$zmSlangRemoteHostPort       = 'Fjärrport';
$zmSlangRemoteImageColours   = 'Fjärrbildfärger';
$zmSlangRename               = 'Byt namn';
$zmSlangReplay               = 'Repris';
$zmSlangReset                = 'Återställ';
$zmSlangResetEventCounts     = 'Återställ händelseräknare';
$zmSlangRestart              = 'Återstart';
$zmSlangRestarting           = 'Återstartar';
$zmSlangRestrictedCameraIds  = 'Begränsade kameranr.';
$zmSlangRestrictedMonitors   = 'Restricted Monitors';
$zmSlangReturnDelay          = 'Fördröjd retur';
$zmSlangReturnLocation       = 'Återvänd till position';
$zmSlangRotateLeft           = 'Rotera vänster';
$zmSlangRotateRight          = 'Rotera höger';
$zmSlangRunMode              = 'Körläge';
$zmSlangRunning              = 'Körs';
$zmSlangRunState             = 'Körläge';
$zmSlangSaveAs               = 'Spara som';
$zmSlangSaveFilter           = 'Spara filter';
$zmSlangSave                 = 'Spara';
$zmSlangScale                = 'Skala';
$zmSlangScore                = 'Resultat';
$zmSlangSecs                 = 'Sek';
$zmSlangSectionlength        = 'Sektionslängd';
$zmSlangSelectMonitors       = 'Select Monitors';
$zmSlangSelect               = 'Välj';
$zmSlangSelfIntersecting     = 'Polygonändarna får inte överlappa';
$zmSlangSetLearnPrefs        = 'Set Learn Prefs'; // This can be ignored for now
$zmSlangSetNewBandwidth      = 'Ställ in ny bandbredd';
$zmSlangSetPreset            = 'Ställ in förinställning';
$zmSlangSet                  = 'Ställ in';
$zmSlangSettings             = 'Inställningar';
$zmSlangShowFilterWindow     = 'Visa fönsterfilter';
$zmSlangShowTimeline         = 'Visa tidslinje';
$zmSlangSize                 = 'Storlek';
$zmSlangSleep                = 'Vila';
$zmSlangSortAsc              = 'Stigande';
$zmSlangSortBy               = 'Sortera';
$zmSlangSortDesc             = 'Fallande';
$zmSlangSource               = 'Källa';
$zmSlangSourceType           = 'Källtyp';
$zmSlangSpeed                = 'Hastighet';
$zmSlangSpeedHigh            = 'Höghastighet';
$zmSlangSpeedLow             = 'Låghastighet';
$zmSlangSpeedMedium          = 'Normalhastighet';
$zmSlangSpeedTurbo           = 'Turbohastighet';
$zmSlangStart                = 'Start';
$zmSlangState                = 'Läge';
$zmSlangStats                = 'Statistik';
$zmSlangStatus               = 'Status';
$zmSlangStepLarge            = 'Stora steg';
$zmSlangStepMedium           = 'Normalsteg';
$zmSlangStepNone             = 'Inga steg';
$zmSlangStepSmall            = 'Små steg';
$zmSlangStep                 = 'Steg';
$zmSlangStills               = 'Stillbilder';
$zmSlangStopped              = 'Stoppad';
$zmSlangStop                 = 'Stopp';
$zmSlangStream               = 'Strömmande';
$zmSlangSubmit               = 'Skicka';
$zmSlangSystem               = 'System';
$zmSlangTele                 = 'Tele';
$zmSlangThumbnail            = 'Miniatyrer';
$zmSlangTilt                 = 'Tilt';
$zmSlangTimeDelta            = 'tidsdelta';
$zmSlangTimeline             = 'Tidslinje';
$zmSlangTimestampLabelFormat = 'Format på tidsstämpel';
$zmSlangTimestampLabelX      = 'Värde på tidsstämpel X';
$zmSlangTimestampLabelY      = 'Värde på tidsstämpel Y';
$zmSlangTimestamp            = 'Tidsstämpel';
$zmSlangTimeStamp            = 'Tidsstämpel';
$zmSlangTime                 = 'Tid';
$zmSlangToday                = 'Idag';
$zmSlangTools                = 'Verktyg';
$zmSlangTotalBrScore         = 'Total<br/>Score';
$zmSlangTrackDelay           = 'Spårfördröjning';
$zmSlangTrackMotion          = 'Spåra rörelse';
$zmSlangTriggers             = 'Triggers';
$zmSlangTurboPanSpeed        = 'Turbo panoramahastighet';
$zmSlangTurboTiltSpeed       = 'Turbo tilthastighet';
$zmSlangType                 = 'Typ';
$zmSlangUnarchive            = 'Packa upp';
$zmSlangUnits                = 'Enheter';
$zmSlangUnknown              = 'Okänd';
$zmSlangUpdateAvailable      = 'En uppdatering till ZoneMinder finns tillgänglig.';
$zmSlangUpdateNotNecessary   = 'Ingen uppdatering behövs.';
$zmSlangUpdate               = 'Uppdatera';
$zmSlangUseFilter            = 'Använd filter';
$zmSlangUseFilterExprsPost   = '&nbsp;filter&nbsp;expressions'; // This is used at the end of the phrase 'use N filter expressions'
$zmSlangUseFilterExprsPre    = 'Använd&nbsp;'; // This is used at the beginning of the phrase 'use N filter expressions'
$zmSlangUser                 = 'Användare';
$zmSlangUsername             = 'Användarnamn';
$zmSlangUsers                = 'Användare';
$zmSlangValue                = 'Värde';
$zmSlangVersionIgnore        = 'Ignorera denna version';
$zmSlangVersionRemindDay     = 'Påminn om 1 dag';
$zmSlangVersionRemindHour    = 'Påminn om 1 timme';
$zmSlangVersionRemindNever   = 'Påminn inte om nya versioner';
$zmSlangVersionRemindWeek    = 'Påminn om en 1 vecka';
$zmSlangVersion              = 'Version';
$zmSlangVideoFormat          = 'Videoformat';
$zmSlangVideoGenFailed       = 'Videogenereringen misslyckades!';
$zmSlangVideoGenFiles        = 'Befintliga videofiler';
$zmSlangVideoGenNoFiles      = 'Inga videofiler';
$zmSlangVideoGenParms        = 'Inställningar för videogenerering';
$zmSlangVideoGenSucceeded    = 'Videogenereringen lyckades!';
$zmSlangVideoSize            = 'Videostorlek';
$zmSlangVideo                = 'Video';
$zmSlangViewAll              = 'Visa alla';
$zmSlangViewEvent            = 'Visa händelse';
$zmSlangViewPaged            = 'Visa Paged';
$zmSlangView                 = 'Visa';
$zmSlangWake                 = 'Vakna';
$zmSlangWarmupFrames         = 'Värm upp ramar';
$zmSlangWatch                = 'Se';
$zmSlangWebColour            = 'Webbfärg';
$zmSlangWeb                  = 'Webb';
$zmSlangWeek                 = 'Vecka';
$zmSlangWhiteBalance         = 'Vitbalans';
$zmSlangWhite                = 'Vit';
$zmSlangWide                 = 'Vid';
$zmSlangX10ActivationString  = 'X10 aktiveringssträng';
$zmSlangX10InputAlarmString  = 'X10 larmingångssträng';
$zmSlangX10OutputAlarmString = 'X10 larmutgångssträng';
$zmSlangX10                  = 'X10';
$zmSlangX                    = 'X';
$zmSlangYes                  = 'Ja';
$zmSlangY                    = 'J';
$zmSlangYouNoPerms           = 'Du har inte tillstånd till denna resurs.';
$zmSlangZoneAlarmColour      = 'Larmfärg (Röd/Grön/Blå)';
$zmSlangZoneArea             = 'Zonarea';
$zmSlangZoneFilterSize       = 'Filterbredd/höjd (pixlar)';
$zmSlangZoneMinMaxAlarmArea  = 'Min/Max larmarea';
$zmSlangZoneMinMaxBlobArea   = 'Min/Max blobbarea';
$zmSlangZoneMinMaxBlobs      = 'Min/Max blobbar';
$zmSlangZoneMinMaxFiltArea   = 'Min/Max filterarea';
$zmSlangZoneMinMaxPixelThres = 'Min/Max pixel Threshold (0-255)';
$zmSlangZones                = 'Zoner';
$zmSlangZone                 = 'Zon';
$zmSlangZoomIn               = 'Zooma in';
$zmSlangZoomOut              = 'Zooma ut';
$zmSlangZoom                 = 'Zoom';

// Complex replacements with formatting and/or placements, must be passed through sprintf
$zmClangCurrentLogin         = 'Aktuell inloggning är \'%1$s\'';
$zmClangEventCount           = '%1$s %2$s'; // For example '37 Events' (from Vlang below)
$zmClangLastEvents           = 'Senaste %1$s %2$s'; // For example 'Last 37 Events' (from Vlang below)
$zmClangLatestRelease        = 'Aktuell version är v%1$s, du har v%2$s.';
$zmClangMonitorCount         = '%1$s %2$s'; // For example '4 Monitors' (from Vlang below)
$zmClangMonitorFunction      = 'Bevakare %1$s funktion';
$zmClangRunningRecentVer     = 'Du använder den senaste versionen av ZoneMinder, v%s.';

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
$zmVlangEvent                = array( 0=>'Händelser', 1=>'Händelsen', 2=>'Händelserna' );
$zmVlangMonitor              = array( 0=>'Bevakare', 1=>'Bevakare', 2=>'Bevakare' );

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
	die( 'Fel, kan inte correlate variabel språksträng' );
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

$zmOlangPromptLANG_DEFAULT = "Välj språk för ZoneMinder";
$zmOlangHelpLANG_DEFAULT = "ZoneMinder kan använda annat språk än engelska i menyer och texter. Välj här det språk du vill använda till ZoneMinder.";


?>
