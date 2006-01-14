<?php
// 
// ZoneMinder web Romanian language file, $Date$
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
// ZoneMinder Romanian translation by Alex Ciobanu
//
// I have used decimal entity reference for Romanian special characters 
// (i.e. i with circumflex, s with cedilla, etc) so anybody can view this
// translation correctly no matter of the browser (local) settings.
// This translation lacks some words, terms and expressions because i do not
// know the correct Romanian equivalents for them.
// Please fell free to modify this file to make it better and get some credit
// for doing this (add your name here).

// Acest program este liber; îl puteţi redistribui şi/sau modifica
// în conformitate cu termenii Licenţei Publice Generale GNU (GPL)
// aşa cum este publicată de Free Software Foundation; fie versiunea 2
// a Licenţei, fie (la latitudinea dumneavoastră) orice versiune ulterioară.
//
// Acest program este distribuit cu speranţa că va fi util, dar FĂRĂ NICI O GARANŢIE,
// fără garanţie implicită de vandabilitate şi conformitate unui anumit scop.
// Citiţi Licenţa Publică Generală GNU pentru detalii. O traducere 
// neoficială în limba română poate fi obţinută de aici: www.roedu.net/gplro.html
//

//
setlocale( LC_ALL, 'ro_RO' ); 
//
// Simple String Replacements
$zmSlang24BitColour          = 'Color &#226;n 24 bi&#355;i';
$zmSlang8BitGrey             = 'Scal&#259 gri &#226;n 8 bi&#355;i';
$zmSlangAction               = 'Action';
$zmSlangActual               = 'Real';
$zmSlangAddNewControl        = 'Adaug&#259; control nou';
$zmSlangAddNewMonitor        = 'Adaug&#259; monitor';
$zmSlangAddNewUser           = 'Adaug&#259; utilizator';
$zmSlangAddNewZone           = 'Adaug&#259; zon&#259;';
$zmSlangAlarm                = 'Alarma';
$zmSlangAlarmBrFrames        = 'Alarm<br/>Frames';
$zmSlangAlarmFrame           = 'Cadru alarma';
$zmSlangAlarmFrameCount      = 'Nr. cadru alarma';
$zmSlangAlarmLimits          = 'Alarm Limits';
$zmSlangAlarmPx              = 'Alarm Px';
$zmSlangAlarmRGBUnset        = 'You must set an alarm RGB colour';
$zmSlangAlert                = 'Alert';
$zmSlangAll                  = 'Toate';
$zmSlangApply                = 'Accept';
$zmSlangApplyingStateChange  = 'Aplic schimbarea de stare';
$zmSlangArchArchived         = 'Numai arhivate';
$zmSlangArchive              = 'Arhive';
$zmSlangArchived             = 'Archived';
$zmSlangArchUnarchived       = 'Numai nearhivate';
$zmSlangArea                 = 'Area';
$zmSlangAreaUnits            = 'Area (px/%)';
$zmSlangAttrAlarmFrames      = 'Cadre alarma';
$zmSlangAttrArchiveStatus    = 'Stare arhiva';
$zmSlangAttrAvgScore         = 'Cota medie';
$zmSlangAttrCause            = 'Cauza';
$zmSlangAttrDate             = 'Data';
$zmSlangAttrDateTime         = 'Data/Timp';
$zmSlangAttrDiskBlocks       = 'Disk Blocks';
$zmSlangAttrDiskPercent      = 'Procentaj disc';
$zmSlangAttrDuration         = 'Durata';
$zmSlangAttrFrames           = 'Cadre';
$zmSlangAttrId               = 'Nr.';
$zmSlangAttrMaxScore         = 'Cota max';
$zmSlangAttrMonitorId        = 'Monitor nr.';
$zmSlangAttrMonitorName      = 'Nume monitor';
$zmSlangAttrName             = 'Nume';
$zmSlangAttrNotes            = 'Notes';
$zmSlangAttrTime             = 'Time';
$zmSlangAttrTotalScore       = 'Cota total';
$zmSlangAttrWeekday          = 'Zi s&#259;pt.';
$zmSlangAutoArchiveAbbr      = 'Archive';
$zmSlangAutoArchiveEvents    = 'Arhiveaz&#259; automat toate rezultatele';
$zmSlangAuto                 = 'Auto';
$zmSlangAutoDeleteAbbr       = 'Delete';
$zmSlangAutoDeleteEvents     = '&#350;terge automat toate rezultatele';
$zmSlangAutoEmailAbbr        = 'Email';
$zmSlangAutoEmailEvents      = 'Trimite automat email ale tuturor rezultatelor';
$zmSlangAutoExecuteAbbr      = 'Execute';
$zmSlangAutoExecuteEvents    = 'Execut&#259; automat comanda pentru toate rezultatele';
$zmSlangAutoMessageAbbr      = 'Message';
$zmSlangAutoMessageEvents    = 'Trimite automat mesaj pentru toate rezultatele';
$zmSlangAutoStopTimeout      = 'Auto Stop Timeout';
$zmSlangAutoUploadAbbr       = 'Upload';
$zmSlangAutoUploadEvents     = '&#206;ncarc&#259; automat toate rezultatele';
$zmSlangAutoVideoAbbr        = 'Video';
$zmSlangAutoVideoEvents      = 'Automatically create video for all matches';
$zmSlangAvgBrScore           = 'Cota<br/>medie';
$zmSlangBadNameChars         = 'Denumirea poate contine doar caractere alfanumerice, cratima si underline.';
$zmSlangBandwidth            = 'La&#355;ime de band&#259;';
$zmSlangBlobPx               = 'Blob Px';
$zmSlangBlobs                = 'Blobs';
$zmSlangBlobSizes            = 'Blob Sizes';
$zmSlangBrightness           = 'Luminozitate';
$zmSlangBuffers              = 'Zon&#259;&nbsp;tampon';
$zmSlangCanAutoFocus         = 'Focalizare automat&#259;';
$zmSlangCanAutoGain          = 'Can Auto Gain';
$zmSlangCanAutoIris          = 'Can Auto Iris';
$zmSlangCanAutoWhite         = 'Balans alb automat';
$zmSlangCanAutoZoom          = 'Are auto zoom';
$zmSlangCancelForcedAlarm    = 'Renunta&nbsp;Fortat&nbsp;Alarma';
$zmSlangCancel               = 'Renun&#355;';
$zmSlangCanFocusAbs          = 'Focalizare absolut&#259;';
$zmSlangCanFocusCon          = 'Focalizare continu&#259;';
$zmSlangCanFocus             = 'Focalizare';
$zmSlangCanFocusRel          = 'Focalizare relativ&#259;';
$zmSlangCanGainAbs           = 'Can Gain Absolute';
$zmSlangCanGain              = 'Can Gain ';
$zmSlangCanGainCon           = 'Can Gain Continuous';
$zmSlangCanGainRel           = 'Can Gain Relative';
$zmSlangCanIrisAbs           = 'Can Iris Absolute';
$zmSlangCanIris              = 'Can Iris';
$zmSlangCanIrisCon           = 'Can Iris Continuous';
$zmSlangCanIrisRel           = 'Can Iris Relative';
$zmSlangCanMoveAbs           = 'Mi&#351;care absolut&#259;';
$zmSlangCanMoveCon           = 'Mi&#351;care continu&#259;';
$zmSlangCanMoveDiag          = 'Mi&#351;care diagonal&#259;';
$zmSlangCanMove              = 'Dinamic';
$zmSlangCanMoveMap           = 'Can Move Mapped';
$zmSlangCanMoveRel           = 'Mi&#351;care relativ&#259;';
$zmSlangCanPan               = 'Rotativ' ;
$zmSlangCanReset             = 'Can Reset';
$zmSlangCanSetPresets        = 'Can Set Presets';
$zmSlangCanSleep             = 'Can Sleep';
$zmSlangCanTilt              = 'Se poate &#238;nclina';
$zmSlangCanWake              = 'Can Wake';
$zmSlangCanWhiteAbs          = 'Balans alb absolut';
$zmSlangCanWhite             = 'Balans alb';
$zmSlangCanWhiteBal          = 'Balans alb';
$zmSlangCanWhiteCon          = 'Balans alb continuu';
$zmSlangCanWhiteRel          = 'Balans alb relativ';
$zmSlangCanZoomAbs           = 'Zoom Absolut';
$zmSlangCanZoomCon           = 'Zoom Continuu';
$zmSlangCanZoomRel           = 'Zoom Relativ';
$zmSlangCanZoom              = 'Zoom';
$zmSlangCaptureHeight        = '&#206n&#259;l&#355;ime captur&#259;';
$zmSlangCapturePalette       = 'Palet&#259; captur&#259;';
$zmSlangCaptureWidth         = 'L&#259;&#355;ime captur&#259;';
$zmSlangCause                = 'Cauza';
$zmSlangCheckMethod          = 'Alarm Check Method';
$zmSlangChooseFilter         = 'Alege filtru';
$zmSlangChoosePreset         = 'Choose Preset';
$zmSlangClose                = '&#206;nchide';
$zmSlangColour               = 'Culoare';
$zmSlangCommand              = 'Comanda';
$zmSlangConfig               = 'Config';
$zmSlangConfiguredFor        = 'Configurat pentru';
$zmSlangConfirmPassword      = 'Confirm parola';
$zmSlangConjAnd              = '&#351;i';
$zmSlangConjOr               = 'sau';
$zmSlangConsole              = 'Consola';
$zmSlangContactAdmin         = 'Va rugam contactati administratorul pentru detalii.';
$zmSlangContinue             = 'Continua&#259;';
$zmSlangContrast             = 'Contrast';
$zmSlangControlAddress       = 'Adres&#259; control';
$zmSlangControlCap           = 'Posibilitate control';
$zmSlangControlCaps          = 'Posibilit&#259;&#355;i control';
$zmSlangControl              = 'Control';
$zmSlangControlDevice        = 'Dispozitiv control';
$zmSlangControllable         = 'Controlabil';
$zmSlangControlType          = 'Tip control';
$zmSlangCycle                = 'Ciclu';
$zmSlangCycleWatch           = 'Vizual. ciclu';
$zmSlangDay                  = 'Zi';
$zmSlangDebug                = 'Debug';
$zmSlangDefaultRate          = 'Default Rate';
$zmSlangDefaultScale         = 'Default Scale';
$zmSlangDelete               = '&#350;terge';
$zmSlangDeleteAndNext        = '&#350;terge &amp; Urm&#259;tor';
$zmSlangDeleteAndPrev        = '&#350;terge &amp; Precedent';
$zmSlangDeleteSavedFilter    = '&#350;terge filtrul salvat';
$zmSlangDescription          = 'Descriere';
$zmSlangDeviceChannel        = 'Canal dispozitiv';
$zmSlangDeviceFormat         = 'Format dispozitiv(0=PAL,1=NTSC)';
$zmSlangDeviceNumber         = 'Num&#259;r dispozitiv (/dev/video?)';
$zmSlangDevicePath           = 'Device Path';
$zmSlangDimensions           = 'Dimensiuni';
$zmSlangDisableAlarms        = 'Disable Alarms';
$zmSlangDisk                 = 'Disc';
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
$zmSlangDuration             = 'Durata';
$zmSlangEdit                 = 'Modific';
$zmSlangEmail                = 'Email';
$zmSlangEnableAlarms         = 'Enable Alarms';
$zmSlangEnabled              = 'Activ';
$zmSlangEnterNewFilterName   = 'Introduceti denumire filtru';
$zmSlangErrorBrackets        = 'Eroare, va rugam asigurati-va ca toate parantezele se inchid';
$zmSlangError                = 'Eroare';
$zmSlangErrorValidValue      = 'Eroare, va rugam verificati validitatea numelor termenilor';
$zmSlangEtc                  = 'etc';
$zmSlangEvent                = 'Eveniment';
$zmSlangEventFilter          = 'Filtru eveniment';
$zmSlangEventId              = 'Nr. eveniment';
$zmSlangEventName            = 'Nume eveniment';
$zmSlangEventPrefix          = 'Prefix eveniment';
$zmSlangEvents               = 'Evenim.';
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
$zmSlangFirst                = 'First';
$zmSlangFlippedHori          = 'Flipped Horizontally';
$zmSlangFlippedVert          = 'Flipped Vertically';
$zmSlangFocus                = 'Focalizare';
$zmSlangForceAlarm           = 'Alarm&#259;&nbsp;for&#355;at&#259;';
$zmSlangFormat               = 'Format';
$zmSlangFPS                  = 'FPS';
$zmSlangFPSReportInterval    = 'Interval raport FPS';
$zmSlangFrame                = 'Cadru';
$zmSlangFrameId              = 'Nr. cadru';
$zmSlangFrameRate            = 'Frecv. cadre';
$zmSlangFrames               = 'Cadre';
$zmSlangFrameSkip            = 'Omite cadre';
$zmSlangFTP                  = 'FTP';
$zmSlangFunc                 = 'Func';
$zmSlangFunction             = 'Func&#355;ie';
$zmSlangGain                 = 'Gain';
$zmSlangGeneral              = 'General';
$zmSlangGenerateVideo        = 'Genereaz&#259; video';
$zmSlangGeneratingVideo      = 'Generez video';
$zmSlangGoToZoneMinder       = 'Du-te la ZoneMinder.com';
$zmSlangGrey                 = 'Gri';
$zmSlangGroups               = 'Grupuri';
$zmSlangHasFocusSpeed        = 'Vitez&#259; focalizare';
$zmSlangHasGainSpeed         = 'Has Gain Speed';
$zmSlangHasHomePreset        = 'Has Home Preset';
$zmSlangHasIrisSpeed         = 'Has Iris Speed';
$zmSlangHasPanSpeed          = 'Vitez&#259; rotire';
$zmSlangHasPresets           = 'Are Preset&#259;ri';
$zmSlangHasTiltSpeed         = 'Vitez&#259; &#238;nclinare';
$zmSlangHasTurboPan          = 'Rotire turbo';
$zmSlangHasTurboTilt         = '&#206;nclinare turbo';
$zmSlangHasWhiteSpeed        = 'Vitez&#259; balans alb';
$zmSlangHasZoomSpeed         = 'Vitez&#259; zoom';
$zmSlangHighBW               = 'B/W&nbsp;mare';
$zmSlangHigh                 = 'Mare';
$zmSlangHome                 = 'Home';
$zmSlangHour                 = 'Ora';
$zmSlangHue                  = 'Nuan&#355;&#259;';
$zmSlangIdle                 = 'Oprit';
$zmSlangId                   = 'Nr.';
$zmSlangIgnore               = 'Ignor';
$zmSlangImageBufferSize      = 'Zon&#259; tampon imagine (cadre)';
$zmSlangImage                = 'Imagine';
$zmSlangInclude              = 'Includ';
$zmSlangIn                   = 'In';
$zmSlangInverted             = 'Invers&#259;';
$zmSlangIris                 = 'Iris';
$zmSlangLanguage             = 'Limb&#259;';
$zmSlangLast                 = 'Ultim';
$zmSlangLimitResultsPost     = 'rezultate';
$zmSlangLimitResultsPre      = 'Limiteaz&#259; la primele';
$zmSlangList                 = 'List';
$zmSlangLoad                 = 'Load';
$zmSlangLocal                = 'Local';
$zmSlangLoggedInAs           = 'E&#351;ti conectat ca';
$zmSlangLoggingIn            = 'Logare';
$zmSlangLogin                = 'Login';
$zmSlangLogout               = 'Ie&#351;ire';
$zmSlangLowBW                = 'B/W&nbsp;redus';
$zmSlangLow                  = 'Redusa';
$zmSlangMain                 = 'Main';
$zmSlangMan                  = 'Man';
$zmSlangManual               = 'Manual';
$zmSlangMark                 = 'Select';
$zmSlangMaxBandwidth         = 'Max Bandwidth';
$zmSlangMaxBrScore           = 'Cota<br/>max';
$zmSlangMaxFocusRange        = 'Raza focalizare max';
$zmSlangMaxFocusSpeed        = 'Vitez&#259; focalizare max';
$zmSlangMaxFocusStep         = 'Pas focalizare max';
$zmSlangMaxGainRange         = 'Max Gain Range';
$zmSlangMaxGainSpeed         = 'Max Gain Speed';
$zmSlangMaxGainStep          = 'Max Gain Step';
$zmSlangMaximumFPS           = 'FPS max';
$zmSlangMaxIrisRange         = 'Max Iris Range';
$zmSlangMaxIrisSpeed         = 'Max Iris Speed';
$zmSlangMaxIrisStep          = 'Max Iris Step';
$zmSlangMax                  = 'Max';
$zmSlangMaxPanRange          = 'Raza max de rotire';
$zmSlangMaxPanSpeed          = 'Vitez&#259; rotire max';
$zmSlangMaxPanStep           = 'Pas rotire max';
$zmSlangMaxTiltRange         = 'Raza &#238;nclinare max';
$zmSlangMaxTiltSpeed         = 'Vitez&#239; &#238;nclinare max';
$zmSlangMaxTiltStep          = 'Pas &#238;nclinare max';
$zmSlangMaxWhiteRange        = 'Raza balans alb max';
$zmSlangMaxWhiteSpeed        = 'Vitez&#259; balans alb man';
$zmSlangMaxWhiteStep         = 'Pas balans alb max';
$zmSlangMaxZoomRange         = 'Raza zoom max';
$zmSlangMaxZoomSpeed         = 'Vitez&#259; zoom max';
$zmSlangMaxZoomStep          = 'Pas zoom max';
$zmSlangMediumBW             = 'B/W&nbsp;mediu';
$zmSlangMedium               = 'Medie';
$zmSlangMinAlarmAreaLtMax    = 'Minimum alarm area should be less than maximum';
$zmSlangMinAlarmAreaUnset    = 'You must specify the minimum alarm pixel count';
$zmSlangMinBlobAreaLtMax     = 'Minimum blob area should be less than maximum';
$zmSlangMinBlobAreaUnset     = 'You must specify the minimum blob pixel count';
$zmSlangMinBlobLtMinFilter   = 'Minimum blob area should be less than or equal to minimum filter area';
$zmSlangMinBlobsLtMax        = 'Minimum blobs should be less than maximum';
$zmSlangMinBlobsUnset        = 'You must specify the minimum blob count';
$zmSlangMinFilterAreaLtMax   = 'Minimum filter area should be less than maximum';
$zmSlangMinFilterAreaUnset   = 'You must specify the minimum filter pixel count';
$zmSlangMinFilterLtMinAlarm  = 'Minimum filter area should be less than or equal to minimum alarm area';
$zmSlangMinFocusRange        = 'Raza focalizare min';
$zmSlangMinFocusSpeed        = 'Vitez&#259; focalizare min';
$zmSlangMinFocusStep         = 'Pas focalizare min';
$zmSlangMinGainRange         = 'Min Gain Range';
$zmSlangMinGainSpeed         = 'Min Gain Speed';
$zmSlangMinGainStep          = 'Min Gain Step';
$zmSlangMinIrisRange         = 'Min Iris Range';
$zmSlangMinIrisSpeed         = 'Min Iris Speed';
$zmSlangMinIrisStep          = 'Min Iris Step';
$zmSlangMinPanRange          = 'Raza min de rotire';
$zmSlangMinPanSpeed          = 'Vitez&#259; rotire min';
$zmSlangMinPanStep           = 'Pas rotire min';
$zmSlangMinPixelThresLtMax   = 'Minimum pixel threshold should be less than maximum';
$zmSlangMinPixelThresUnset   = 'You must specify a minimum pixel threshold';
$zmSlangMinTiltRange         = 'Raza &#238;nclinare min';
$zmSlangMinTiltSpeed         = 'Vitez&#239; &#238;nclinare min';
$zmSlangMinTiltStep          = 'Pas &#238;nclinare min';
$zmSlangMinWhiteRange        = 'Raza balans alb min';
$zmSlangMinWhiteSpeed        = 'Vitez&#259; balans alb min';
$zmSlangMinWhiteStep         = 'Pas balans alb min';
$zmSlangMinZoomRange         = 'Raza zoom min';
$zmSlangMinZoomSpeed         = 'Vitez&#259; zoom min';
$zmSlangMinZoomStep          = 'Pas zoom min';
$zmSlangMisc                 = 'Divers';
$zmSlangMonitorIds           = 'Nr.&nbsp;Monitor';
$zmSlangMonitor              = 'Monitor';
$zmSlangMonitorPresetIntro   = 'Select an appropriate preset from the list below.<br><br>Please note that this may overwrite any values you already have configured for this monitor.<br><br>';
$zmSlangMonitorPreset        = 'Monitor Preset';
$zmSlangMonitors             = 'Monitoare';
$zmSlangMontage              = 'Montage';
$zmSlangMonth                = 'Luna';
$zmSlangMove                 = 'Mi&#351;care';
$zmSlangMustBeGe             = 'trebuie sa fie mai mare sau egal cu';
$zmSlangMustBeLe             = 'trebuie sa fie mai mic sau egal cu';
$zmSlangMustConfirmPassword  = 'Trebuie sa confirmati parola';
$zmSlangMustSupplyPassword   = 'Trebuie sa introduceti parola'; 
$zmSlangMustSupplyUsername   = 'Trebuie sa introduceti utilizator'; 
$zmSlangName                 = 'Denumire';
$zmSlangNear                 = 'Near';
$zmSlangNetwork              = 'Re&#355;ea';
$zmSlangNewGroup             = 'Grup nou';
$zmSlangNew                  = 'Nou';
$zmSlangNewPassword          = 'Parola nou&#259;';
$zmSlangNewState             = 'Stare nou&#259;';
$zmSlangNewUser              = 'Utilizator nou';
$zmSlangNext                 = 'Urmator';
$zmSlangNoFramesRecorded     = 'Nu exista cadre inregistrate pentru acest eveniment.';
$zmSlangNoGroup              = 'No Group';
$zmSlangNoneAvailable        = 'Indisponibil';
$zmSlangNone                 = 'Nimic';
$zmSlangNo                   = 'Nu';
$zmSlangNormal               = 'Normal';
$zmSlangNoSavedFilters       = 'LipsaFiltruSalvat';
$zmSlangNoStatisticsRecorded = 'Nu exista statistici pentru acest eveniment/cadru.';
$zmSlangNotes                = 'Notes';
$zmSlangNumPresets           = 'Num Presets';
$zmSlangOpen                 = 'Deschide';
$zmSlangOpEq                 = 'egal cu';
$zmSlangOpGtEq               = 'mai mare sau egal cu';
$zmSlangOpGt                 = 'mai mare ca';
$zmSlangOpIn                 = 'in set';
$zmSlangOpLtEq               = 'mai mic sau egal cu';
$zmSlangOpLt                 = 'mai mic dec&#226;t';
$zmSlangOpMatches            = 'matches';
$zmSlangOpNe                 = 'diferit de';
$zmSlangOpNotIn              = 'not in set';
$zmSlangOpNotMatches         = 'nu se potriveste';
$zmSlangOptionHelp           = 'OptionHelp';
$zmSlangOptionRestartWarning = 'Aceste schimbari nu se aplica in timpul rularii.\n Dupa ce ati terminat setarile va rugam reporniti ZoneMinder.';
$zmSlangOptions              = 'Op&#355;iuni';
$zmSlangOrder                = 'Order';
$zmSlangOrEnterNewName       = 'sau denumire nou&#259;';
$zmSlangOrientation          = 'Orientare';
$zmSlangOut                  = 'Out';
$zmSlangOverwriteExisting    = 'Suprascrie existent';
$zmSlangPaged                = 'Paginat';
$zmSlangPanLeft              = 'Pan Left';
$zmSlangPanRight             = 'Pan Right';
$zmSlangPan                  = 'Rotire';
$zmSlangPanTilt              = 'Rotire/&#206;nclinare';
$zmSlangParameter            = 'Parametru';
$zmSlangPassword             = 'Parol&#259;';
$zmSlangPasswordsDifferent   = 'Cele dou&#259; parole difer&#259;.';
$zmSlangPaths                = 'Cale';
$zmSlangPhoneBW              = 'Phone&nbsp;B/W';
$zmSlangPhone                = 'Phone';
$zmSlangPixels               = 'Pixeli';
$zmSlangPlayAll              = 'Play All';
$zmSlangPleaseWait           = 'V&#259; rug&#259;m a&#351;tepta&#355;i';
$zmSlangPoint                = 'Point';
$zmSlangPostEventImageBuffer = 'Zona tampon post eveniment';
$zmSlangPreEventImageBuffer  = 'Zona tampon pre eveniment';
$zmSlangPreset               = 'Presetare';
$zmSlangPresets              = 'Preset&#259;ri';
$zmSlangPrev                 = 'Prev';
$zmSlangRate                 = 'Rate';
$zmSlangReal                 = 'Real';
$zmSlangRecord               = '&#206;nregistrare';
$zmSlangRefImageBlendPct     = 'Combinare imagine referinta(%)';
$zmSlangRefresh              = 'Actualizeaz&#259;';
$zmSlangRemoteHostName       = 'Remote Host Name';
$zmSlangRemoteHostPath       = 'Remote Host Path';
$zmSlangRemoteHostPort       = 'Remote Host Port';
$zmSlangRemoteImageColours   = 'Remote Image Colours';
$zmSlangRemote               = 'Remote';
$zmSlangRename               = 'Rename';
$zmSlangReplay               = 'Replay';
$zmSlangResetEventCounts     = 'Reset Event Counts';
$zmSlangReset                = 'Reset';
$zmSlangRestarting           = 'Repornesc';
$zmSlangRestart              = 'Reporne&#351;te';
$zmSlangRestrictedCameraIds  = 'Restricted Camera Ids';
$zmSlangReturnDelay          = 'Return Delay';
$zmSlangReturnLocation       = 'Return Location';
$zmSlangRotateLeft           = 'Rotire st&#226;nga';
$zmSlangRotateRight          = 'Rotire dreapta';
$zmSlangRunMode              = 'Mod rulare';
$zmSlangRunning              = 'Ruleaz&#259;';
$zmSlangRunState             = 'Stare de rulare';
$zmSlangSaveAs               = 'Salveaz&#259; ca';
$zmSlangSaveFilter           = 'Salveaz&#259; filtru';
$zmSlangSave                 = 'Salvez';
$zmSlangScale                = 'Scara';
$zmSlangScore                = 'Cota';
$zmSlangSecs                 = 'Sec';
$zmSlangSectionlength        = 'Lungime sec&#355;iune';
$zmSlangSelect               = 'Select';
$zmSlangSelfIntersecting     = 'Polygon edges must not intersect';
$zmSlangSetLearnPrefs        = 'Set Learn Prefs'; // I'm ignoring this... for now.
$zmSlangSetNewBandwidth      = 'Setare la&#355;ime de band&#259; nou&#259;';
$zmSlangSetPreset            = 'Set Preset';
$zmSlangSet                  = 'Set';
$zmSlangSettings             = 'Set&#259;ri';
$zmSlangShowFilterWindow     = 'Fereastra filtre';
$zmSlangShowTimeline         = 'Show Timeline';
$zmSlangSize                 = 'Size';
$zmSlangSleep                = 'Sleep';
$zmSlangSortAsc              = 'Cres';
$zmSlangSortBy               = 'Sorteaz&#259; dup&#259;';
$zmSlangSortDesc             = 'Desc';
$zmSlangSource               = 'Sursa';
$zmSlangSourceType           = 'Tipul sursei';
$zmSlangSpeedHigh            = 'Vitez&#259; mare';
$zmSlangSpeedLow             = 'Vitez&#259; mic&#259;';
$zmSlangSpeedMedium          = 'Vitez&#259; medie';
$zmSlangSpeedTurbo           = 'Vitez&#259; turbo';
$zmSlangSpeed                = 'Vitez&#259;';
$zmSlangStart                = 'Porne&#351;te';
$zmSlangState                = 'Stare';
$zmSlangStats                = 'Statistici';
$zmSlangStatus               = 'Stare';
$zmSlangStepLarge            = 'Large Step';
$zmSlangStepMedium           = 'Medium Step';
$zmSlangStepNone             = 'No Step';
$zmSlangStepSmall            = 'Small Step';
$zmSlangStep                 = 'Step';
$zmSlangStills               = 'Statice';
$zmSlangStop                 = 'Opre&#351;te';
$zmSlangStopped              = 'Oprit';
$zmSlangStream               = 'Flux';
$zmSlangSubmit               = 'Trimite';
$zmSlangSystem               = 'Sistem';
$zmSlangTele                 = 'Tele';
$zmSlangThumbnail            = 'Miniatur&#259;';
$zmSlangTilt                 = '&#206;nclinare';
$zmSlangTimeDelta            = 'Time Delta';
$zmSlangTimeline             = 'Timeline';
$zmSlangTimestamp            = 'Format&nbsp;timp';
$zmSlangTimeStamp            = 'Format timp';
$zmSlangTimestampLabelFormat = 'Format eticheta format timp';
$zmSlangTimestampLabelX      = 'Format timp eticheta X';
$zmSlangTimestampLabelY      = 'Format timp eticheta Y';
$zmSlangTime                 = 'Timp';
$zmSlangToday                = 'Azi';
$zmSlangTools                = 'Unelte';
$zmSlangTotalBrScore         = 'Cota<br/>total';
$zmSlangTrackDelay           = 'Track Delay';
$zmSlangTrackMotion          = 'Track Motion';
$zmSlangTriggers             = 'Declan&#351;ator';
$zmSlangTurboPanSpeed        = 'Vitez&#259; rotire turbo';
$zmSlangTurboTiltSpeed       = 'Vitez&#259; &#238;nclinare turbo';
$zmSlangType                 = 'Tip';
$zmSlangUnarchive            = 'Dezarhivez';
$zmSlangUnits                = 'Unit&#259;&#355;i';
$zmSlangUnknown              = 'Necunoscut';
$zmSlangUpdateAvailable      = 'Sunt disponibile actualiz&#259;ri ZoneMinder.';
$zmSlangUpdateNotNecessary   = 'Actulizarea nu este necesar&#259;.';
$zmSlangUpdate               = 'Update';
$zmSlangUseFilterExprsPost   = '&nbsp;expresii&nbsp;de&nbsp;filtrare '; 
$zmSlangUseFilterExprsPre    = 'Folose&#351;te&nbsp;'; 
$zmSlangUseFilter            = 'Folose&#351;te filtru';
$zmSlangUsername             = 'Nume';
$zmSlangUsers                = 'Utilizatori';
$zmSlangUser                 = 'Utilizator';
$zmSlangValue                = 'Valoare';
$zmSlangVersionIgnore        = 'Ignor&#259; aceast&#259; versiune';
$zmSlangVersionRemindDay     = 'Aminte&#351;te-mi peste 1 zi';
$zmSlangVersionRemindHour    = 'Aminte&#351;te-mi peste 1 or&#259;';
$zmSlangVersionRemindNever   = 'Nu aminti despre versiuni noi';
$zmSlangVersionRemindWeek    = 'Aminte&#351;te-mi peste 1 s&#259;pt&#259;m&#226;n&#259;';
$zmSlangVersion              = 'Versiune';
$zmSlangVideoFormat          = 'Video Format';
$zmSlangVideoGenFailed       = 'Generare video esuata!';
$zmSlangVideoGenFiles        = 'Existing Video Files';
$zmSlangVideoGenNoFiles      = 'No Video Files Found';
$zmSlangVideoGenParms        = 'Parametrii generare video';
$zmSlangVideoGenSucceeded    = 'Video Generation Succeeded!';
$zmSlangVideoSize            = 'M&#259;rime video';
$zmSlangVideo                = 'Video';
$zmSlangViewAll              = 'Vizual. tot';
$zmSlangViewEvent            = 'View Event';
$zmSlangViewPaged            = 'Vizual. paginat';
$zmSlangView                 = 'Vizual';
$zmSlangWake                 = 'Wake';
$zmSlangWarmupFrames         = 'Warmup Frames';
$zmSlangWatch                = 'Watch';
$zmSlangWebColour            = 'Web Colour';
$zmSlangWeb                  = 'Web';
$zmSlangWeek                 = 'S&#259;pt.';
$zmSlangWhite                = 'Alb';
$zmSlangWhiteBalance         = 'Balans alb';
$zmSlangWide                 = 'Wide';
$zmSlangX10ActivationString  = 'String activare X10';
$zmSlangX10InputAlarmString  = 'X10 Input Alarm String';
$zmSlangX10OutputAlarmString = 'X10 Output Alarm String';
$zmSlangX10                  = 'X10';
$zmSlangX                    = 'X';
$zmSlangYes                  = 'Da';
$zmSlangYouNoPerms           = 'Nu aveti permisiunile necesare pentru accesarea acestei resurse.';
$zmSlangY                    = 'Y';
$zmSlangZoneAlarmColour      = 'Alarm Colour (Red/Green/Blue)';
$zmSlangZoneArea             = 'Zone Area';
$zmSlangZoneFilterSize       = 'Filter Width/Height (pixels)';
$zmSlangZoneMinMaxAlarmArea  = 'Min/Max Alarmed Area';
$zmSlangZoneMinMaxBlobArea   = 'Min/Max Blob Area';
$zmSlangZoneMinMaxBlobs      = 'Min/Max Blobs';
$zmSlangZoneMinMaxFiltArea   = 'Min/Max Filtered Area';
$zmSlangZoneMinMaxPixelThres = 'Min/Max Pixel Threshold (0-255)';
$zmSlangZones                = 'Zona';
$zmSlangZone                 = 'Zone';
$zmSlangZoomIn               = 'Zoom In';
$zmSlangZoomOut              = 'Zoom Out';
$zmSlangZoom                 = 'Zoom';


// Complex replacements with formatting and/or placements, must be passed through sprintf
$zmClangCurrentLogin         = 'E&#351;ti logat ca \'%1$s\'';
$zmClangEventCount           = '%1$s %2$s'; // For example '37 Events' (from Vlang below)
$zmClangLastEvents           = 'Ultimele %1$s %2$s'; // For example 'Last 37 Events' (from Vlang below)
$zmClangLatestRelease        = 'Ultima versiune este v%1$s, momentan rula&#355;i v%2$s.';
$zmClangMonitorCount         = '%1$s %2$s'; // For example '4 Monitors' (from Vlang below)
$zmClangMonitorFunction      = 'Func&#355;iile monitorului %1$s ';
$zmClangRunningRecentVer     = 'Rula&#355;i ultima versiune de ZoneMinder, v%s.';

// Variable arrays expressing plurality
$zmVlangEvent                = array( 0=>'Evenimente', 1=>'Eveniment', 2=>'Evenimente' );
$zmVlangMonitor              = array( 0=>'Monitoare', 1=>'Monitor', 2=>'Monitoare' );

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

// OPTIONS

// Beginning of System tab
$zmOlangPromptLANG_DEFAULT = "Limba implicit&#259; folosit&#259;";
$zmOlangHelpLANG_DEFAULT = "ZoneMinder permite folosirea &#238;n interfa&#355;a web a altei limbi dec&#226;t Engleza dac&#259; fi&#351;ierul necesar a fost creat &#351;i exist&#259;. Aceast&#259; op&#355;iune v&#259; permite s&#259; schimba&#355;i limba implicit&#259;, Engleza Britanica, cu o alt&#259; limb&#259;.";
$zmOlangPromptOPT_USE_AUTH = "Autentific&#259; utilizatorii la ZoneMinder";
$zmOlangHelpOPT_USE_AUTH = "Zoneminder poate rula &#238;n dou&#259; moduri. Cel mai simplu este cel f&#259;r&#259; autentificare, &#238;n care oricine poate accesa ZoneMinder av&#226;nd acces la toate op&#355;iunile. Acest mod este fiabil dac&#259; accesul la server-ul web este limitat prin alte modalit&#259;t&#355;i. Al doilea mod permite ad&#259;ugarea de utilizatori cu diverse permisiuni. Utilizatorii trebuie s&#259; se autentifice la ZoneMinder &#351;i sunt limita&#355;i de permisiunile definite.";
$zmOlangPromptAUTH_RELAY = "Metoda folosit&#259; pentru autentificare";
$zmOlangHelpAUTH_RELAY = "&#206;n cazul &#238;n care ZoneMinder ruleaz&#259; &#238;n mod autentificat trebuie s&#259; transmit&#259; informa&#355;iile utilizatorilor la paginile web aferente. Acest lucru este realizat &#238;n dou&#259; moduri. Primul este s&#259; foloseasc&#259; un string care nu con&#355;ine detalii directe despre utilizator &#351;i parol&#259;; cel de-al doilea este s&#259; transmit&#259; utilizatorul &#351;i parola direct. Aceast&#259; metod&#259; nu este recomandat&#259; numai dac&#259; nu ave&#355;i libr&#259;riile md5 disponibile pe sistemul dvs. sau ave&#355;i un sistem complet izolat, f&#259;r&#259; acces extern.";
$zmOlangPromptAUTH_HASH_SECRET = "Secret folosit pentru codarea informa&#355;iilor de autentificare";
$zmOlangHelpAUTH_HASH_SECRET = "C&#226;nd ZoneMinder ruleaz&#259; &#238;n mod de autentificare codat (hashed), necesit&#259; generarea string-urilor de codare care con&#355;in informa&#355;ii criptate ca utilizatorii &#351;i parolele. De&#351;i acest string este destul de sigur, ad&#259;ugarea unui string aleator &#238;mbun&#259;t&#259;&#355;e&#351;te securitatea.";
$zmOlangPromptOPT_FAST_DELETE = "La &#351;tergerea evenimentelor &#351;terge numai informa&#355;iile din baza de date";
$zmOlangHelpOPT_FAST_DELETE = "&#206;n mod normal un eveniment creat ca rezultat al unei alarme este compus din unul sau mai multe tabele &#238;n baza de date plus fi&#351;ierele asociate. C&#226;nd &#351;terge&#355;i evenimente din broswer poate dura mult dac&#259; &#351;terge&#355;i mai multe evenimente concomitent. Este recomandat s&#259; activa&#355;i aceast&#259; op&#355;iune, care va &#351;terge doar informa&#355;iile din baza de date. Evenimentele nu vor mai ap&#259;rea la vizualizare, &#351;i vor fi &#351;terse de daemon-ul zmaudit mai t&#226;rziu.";
$zmOlangPromptSHM_KEY = "Cheie memorie comuna, modifica&#355;i numai &#238;n cazul conflictelor cu alte aplica&#355;ii";
$zmOlangHelpSHM_KEY = "ZoneMinder folose&#351;te memorie comun&#259; pentru a face comunicarea &#238;ntre module mai rapid&#259;. Pentru a identifica zona corect&#259; ce trebuie folosit&#259; sunt utilizate chei de memorie comun&#259;. Aceast&#259; op&#355;iune controleaz&#259; valoarea cheii.";
$zmOlangPromptFILTER_RELOAD_DELAY = "La c&#226;te secunde sunt re&#238;nc&#259;rcate filtrele &#238;n zmfilter.pl";
$zmOlangHelpFILTER_RELOAD_DELAY = "ZoneMinder v&#259; permite s&#259; salva&#355;i filtrele &#238;n baza de date put&#226;nd astfel s&#259; sterge&#355;i sau s&#259; upload-a&#355;i evenimentele corespunz&#259;toare anumitor criterii. Daemon-ul zmfilter &#238;ncarc&#259; aceste evenimente, &#351;terge sau upload-eaz&#259;. Aceast&#259; op&#355;iune determin&#259; c&#226;t de des filtrele vor fi re&#238;nc&#259;rcate. Dac&#259; nu schimba&#355;i des filtrele aceasta poate avea valori mari.";
$zmOlangPromptMAX_RESTART_DELAY = "La c&#226;t timp (&#238;n secunde) daemon-ul va &#238;ncerca repornire.";
$zmOlangHelpMAX_RESTART_DELAY = "zmdc (daemon-ul de control zm) controleaz&#259; toate procesele care sunt pornite sau oprite &#351;i va &#238;ncerca reponire la orice eroare. Dac&#259; sunt multe erori trebuie introdus un timp de &#238;nt&#226;rziere &#238;ntre reporniri. Dac&#259; sunt erori &#238;n continuare aceast&#259; valoare cre&#351;te pentru a &#238;mpierdica blocarea sistemului datorat&#259; repornirilor. Aceast&#259; op&#355;iune controleaz&#259; valoarea de &#238;nt&#226;rziere.";
$zmOlangPromptWATCH_CHECK_INTERVAL = "C&#226;t de des verific dac&#259; daemonii de captur&#259; nu s-au blocat.";
$zmOlangHelpWATCH_CHECK_INTERVAL = "Daemon-ul zmwatch verific&#259; daemonii de captur&#259; pentru a verifica dac&#259; sunt bloca&#355;i (rareori se produce o desincronizare care blocheaz&#259; daemonii). Aceast&#259; op&#355;iune determin&#259; c&#226;t de des sunt verifica&#355;i daemonii.";
$zmOlangPromptWATCH_MAX_DELAY = "Durata maxim&#259; de am&#226;nare, de la ultima imagine capturat&#259;, inainte de a reporni daemonii de captur&#259;";
$zmOlangHelpWATCH_MAX_DELAY = "Aceast&#259; op&#355;iune determin&#259; durata maxim&#259; de am&#226;nare, de la ultimul cadru capturat, pe care o ve&#355;i permite. Daemon-ul va fi repornit dac&#259; nu a &#238;nregistrat nici o imagine dup&#259; aceast&#259; perioad&#259;, totu&#351;i repornirea poate dura mai mult, &#238;n conjunc&#355;ie cu intervalul de verificat de mai sus.";
$zmOlangPromptRECORD_EVENT_STATS = "&#206;nregistrez informa&#355;ii despre evenimente. Dezactiva&#355;i dac&#259; ZoneMinder devine lent.";
$zmOlangHelpRECORD_EVENT_STATS = "Aceast&#259; versiune de ZoneMinder &#238;nregistreaz&#259; informa&#355;ii despre evenimente &#238;n tabelul Stats. Aceasta v&#259; poate ajuta s&#259; determina&#355;i set&#259;rile optime pentru zonele definite, totu&#351;i aceast&#259; op&#355;iune poate fi &#238;n&#351;elatoare. &#206;n versiunile viitoare op&#355;iunea va fi mai exact&#259;, mai ales &#238;n cazul unui num&#259;r mare de evenimente. Op&#355;iunea implicit&#259; (da) permite stocarea acestor informa&#355;ii dar dac&#259; vre&#355;i performan&#355;&#259; pute&#355;i dezactiva aceast&#259; op&#355;iune, caz &#238;n care informa&#355;iile despre evenimente nu vor fi salvate.";
$zmOlangPromptRECORD_DIAG_IMAGES = "&#206;nregistrare imagini intermediare de diagnosticare, foarte lent";
$zmOlangHelpRECORD_DIAG_IMAGES = "Pe l&#226;ng&#259; faptul c&#259; se pot &#238;nregistra statisticile evenimentelor se pot deasemenea &#238;nregistra imagini intermediare de diagnosticare care afi&#351;eaz&#259; rezultatele diferitelor verific&#259;ri care au loc c&#226;nd se &#238;ncearc&#259; determinarea unei posibile alarme. Aceste imagini sunt generate pentru fiecare cadru, zon&#259; &#351;i alarm&#259;, deci impactul asupra performan&#355;ei va fi foarte mare. Activa&#355;i aceast&#259; op&#355;iune doar pentru depanare sau analiz&#259; &#351;i nu uita&#355;i s&#259; o dezactiva&#355;i.";
$zmOlangPromptCREATE_ANALYSIS_IMAGES = "Creaz&#259; imagini analizate cu marcaje ale mi&#351;c&#259;rii";
$zmOlangHelpCREATE_ANALYSIS_IMAGES = "Implicit, &#238;n cazul unei alarme, ZoneMinder &#238;nregistreaz&#259; at&#226;t imaginile neprelucrate c&#226;t &#351;i cele ce au fost analizate &#351;i au zone marcate unde a fost detectat&#259; mi&#351;care. Acest lucru poate fi foarte folositor la configurarea zonelor sau &#238;n analiza evenimentelor. Acest parametru permite oprirea &#238;nregistr&#259;rii imaginilor cu zone de mi&#351;care marcate.";
$zmOlangPromptOPT_FRAME_SERVER = "Daemon-ul de analiz&#259; va scrie imaginile pe disc";
$zmOlangHelpOPT_FRAME_SERVER = "&#206;n unele cazuri este posibil ca viteza de scriere a unui HDD sa fie at&#226;t de mic&#259; &#238;ncat s&#259; cauzeze &#238;ncetinirea daemon-ului de analiz&#259; &#238;n special &#238;n timpul evenimentelor cu multe cadre. Activarea acestei op&#355;iuni porne&#351;te daemon-ul de cadre (zmf) care va 'primi' imaginile de la daemon-ul de analiz&#259; &#351;i le va scrie pe disc. Dac&#259; aceast&#259; transmisie e&#351;ueaz&#259; sau apar alte erori, func&#355;ia de scriere va reveni daemon-ului de analiz&#259;.";
$zmOlangPromptFRAME_SOCKET_SIZE = "Specifica&#355;i dimensiunea memoriei tampon";
$zmOlangHelpFRAME_SOCKET_SIZE = "Pentru imaginile de dimensiuni mari capturate este posibil ca scrierea lor pe disc s&#259; e&#351;ueze deoarece cantitatea de informa&#355;ie scris&#259; este mai mare dec&#226;t memoria tampon alocat&#259;. De&#351;i imaginile sunt scrise apoi de c&#259;tre daemon-ul de analiz&#259;, se distruge obiectul daemon-ului de cadre. Pute&#355;i folosi aceast&#259; op&#355;iune pentru a specifica o memorie tampon de dimensiuni mai mari. Va trebui sa modifica&#355;i dimensiunea socket-ului tampon maxim folosind 'sysctl' (sau in /proc/sys/net/core/wmem_max) pentru a permite setarea acestei noi valori. Alternativa este s&#259; schimba&#355;i m&#259;rimea implicit&#259; a memorie tampon a sistemului, caz &#238;n care modificarea acestei valori nu mai este necesar&#259;.";
$zmOlangPromptOPT_CONTROL = "Suport camere controlabile (rotire/&#238;nclinare/zoom)";
$zmOlangHelpOPT_CONTROL = "ZoneMinder include suport limitat pentru camere controlabile. Sunt incluse c&#226;teva protocoale mostr&#259; &#351;i pot fi ad&#259;ugate cu u&#351;urin&#355;&#259; &#351;i altele. Dac&#259; vre&#355;i s&#259; controla&#355;i camerele prin intermediul ZoneMinder selecta&#355;i aceast&#259; op&#355;iune.";
$zmOlangPromptCHECK_FOR_UPDATES = "Verific versiuni noi la zoneminder.com";
$zmOlangHelpCHECK_FOR_UPDATES = "&#206;ncep&#226;nd cu versiunea 1.17.0, versiuni noi sunt a&#351;teptate frecvent. ZoneMinder poate compara versiunea instalat&#259; cu cea mai recent&#259; de pe zoneminder.com. Aceste verific&#259;ri sunt f&#259;cute cam o dat&#259; pe sapt&#259;m&#226;n&#259; &#351;i nu sunt transmise nici un fel de informa&#355;ii despre sistemul dvs. &#238;n afar&#259; de versiunea de zoneminder pe care o rula&#355;i. Dac&#259; nu dori&#355;i s&#259; face&#355;i verific&#259;ri de versiune sau nu ave&#355;i conexiune la internet dezactiva&#355;i aceast&#259; op&#355;iune.";
// End of System tab

// Beginning of Paths tab
$zmOlangPromptDIR_EVENTS = "Directorul &#238;n care sunt stocate evenimentele";
$zmOlangHelpDIR_EVENTS = "Acesta este subdirectorul &#238;n care sunt salvate imaginile generate de evenimente &#351;i alte fi&#351;iere. Implicit este un subdirector al directorului r&#259;d&#259;cina zoneminder; dac&#259; spa&#355;iul nu v&#259; permite pute&#355;i s&#259; stoca&#355;i imaginile pe alt&#259; parti&#355;ie, caz &#238;n care ar trebui s&#259; face&#355;i un link la subdirectorul implicit.";
$zmOlangPromptDIR_IMAGES = "Directorul &#238;n care sunt stocate imaginile";
$zmOlangHelpDIR_IMAGES = "ZoneMinder genereaz&#259; multe imagini, majoritate asociate cu evenimente. &#206;n acest director vor fi stocate imaginile neasociate evenimentelor.";
$zmOlangPromptDIR_SOUNDS = "Directorul cu sunetele care pot fi folosite de ZoneMinder";
$zmOlangHelpDIR_SOUNDS = "ZoneMinder poate rula un sunet atunci c&#226;nd este detectat&#259; o alarm&#259;. Acesta este directorul &#238;n care este stocat sunetul care va fi rulat.";
$zmOlangPromptPATH_ZMS = "Calea web la serverul video zms";
$zmOlangHelpPATH_ZMS = "Serverul video este necesat pentru a trimite imagini la browser-ul dvs. Va fi instalat &#238;n calea cgi-bin specificat&#259; la instalare. Aceast&#259; op&#355;iune determin&#259; calea web la server. &#206;n mod normal serverul video ruleaz&#259; &#238;n mod parser-header. Dac&#259; ave&#355;i probleme cu aceast&#259; setare pute&#355;i trece &#238;n modul non-parsed-header &#238;nlocuind 'zms' cu 'nph-zms'.";
$zmOlangPromptPATH_SOCKS = "Calea socket-urilor Unix care sunt folosite de ZoneMinder ";
$zmOlangHelpPATH_SOCKS = "&#206;n general ZoneMinder folose&#351;te socket-urilor Unix. Astfel se reduce nevoia de a asigna porturi &#351;i &#238;mpiedic&#259; eventualele conflicte cu aplica&#355;ii externe. Fiecare socket Unix necesit&#259; un fi&#351;ier cu extensia .sock. Aceast&#259; op&#355;iune indic&#259; unde vor fi stocare fi&#351;ierele .sock.";
$zmOlangPromptPATH_LOGS = "Calea la logurile generate de daemonii ZoneMinder";
$zmOlangHelpPATH_LOGS = "Majoritatea daemon-ilor ZoneMinder genereaz&#259; log-uri care v&#259; pot ajuta. Acesta este directorul &#238;n care vor fi stocate log-urile. Log-urile pot fi &#351;terse dac&#259; nu sunt necesare.";
// End of Paths tab

// Beginning of Config tab
$zmOlangPromptTIMESTAMP_ON_CAPTURE = "Adaug&#259; ora pe imaginile capturate";
$zmOlangHelpTIMESTAMP_ON_CAPTURE = "ZoneMinder poate ad&#259;uga ora pe imagini &#238;n dou&#259; feluri. Metoda implicit&#259;, c&#226;nd aceast&#259; op&#355;iune este activ&#259;, face ca fiecarei imagini s&#259; i se aplice ora imediat ce a fost capturat&#259;. A doua metod&#259; nu adaug&#259; ora pe imagini numai c&#226;nd sunt salvate ca parte a unui eveniment sau accesate prin web. Ora va avea acela&#351;i format &#238;n oricare dintre cele dou&#259; cazuri. Folosind prima metod&#259; v&#259; asigura&#355;i c&#259; imaginile au ora tiparit&#259; pe ele indiferent de alte circumstan&#355;e dar va ad&#259;uga ora pe toate imaginile, chiar &#351;i pe cele care nu au fost vizualizate sau salvate. A doua metod&#259; necesit&#259; ca imaginile ce urmeaz&#259; a fi salvate s&#259; fie copiate, &#238;nainte de a fi salvate, altfel cele dou&#259; ore ad&#259;ugate pe imagini pot fi diferite. Ora este &#238;ntotdeauna salvat&#259; la aceeasi rezolu&#355;ie, deci imaginile vor putea fi identificate dup&#259; ora la care au fost capturate.";
$zmOlangPromptLOCAL_BGR_INVERT = "Schimb&#259; BGR in RGB";
$zmOlangHelpLOCAL_BGR_INVERT = "Unele camere &#351;i pl&#259;ci de captur&#259; &#238;nregistreaz&#259; imaginile &#238;n ordinea BGR (Albastru-Verde-Ro&#351;u) chiar dac&#259; paleta de culori spune RGB (Ro&#351;u-Verde-Albastru). Dac&#259; observa&#355;i culori ciudate pe imaginile capturate &#238;ncerca&#355;i s&#259; modifica&#355;i aceast&#259; op&#355;iune. Not&#259;: aceast&#259; op&#355;iune este aplicabil&#259; numai pentru camerele locale nu &#351;i pentru cele din re&#355;ea.";
$zmOlangPromptY_IMAGE_DELTAS = "Calcul diferen&#355;ial al imaginilor folosind canalul Y";
$zmOlangHelpY_IMAGE_DELTAS = "Atunci c&#226;nd ZoneMinder &#238;ncearc&#259; s&#259; stabileasc&#259; diferen&#355;ele dintre dou&#259; imagini color genereaz&#259; o imagine &#238;n scal&#259; de gri 'delta'. Pentru a face acest lucru determin&#259; diferen&#355;ele dintre componentele RGB &#351;i calculeaz&#259; o scal&#259; de gri corespunz&#259;toare. Dac&#259; aceast&#259; op&#355;iune este activ&#259; atunci calculul se va face prin conversia fiec&#259;rui pixel din imagine &#238;ntr-o valoare luminoas&#259; (Y din YUV) &#351;i g&#259;sirea diferen&#355;elor. Dac&#259; aceast&#259; op&#355;iune nu este activ&#259; atunci diferen&#355;a rezultat&#259; este determinat&#259; ca media diferen&#355;elor fiec&#259;rei culori. Folosind valoare Y &#351;ansele de acurate&#355;e sunt mult mai mari iar procesul este cu 15% mai rapid.";
$zmOlangPromptFAST_IMAGE_BLENDS = "Folosirea unui algoritm rapid pentru combinarea imaginilor";
$zmOlangHelpFAST_IMAGE_BLENDS = "&#206;n majoritatea modurilor de rulare ZoneMinder trebuie s&#259; combine imaginile capturate cu imagini de referin&#355;&#259; deja stocate pentru a le actualiza pentru urm&#259;toarea imagine. Procentajul de combinare controleaz&#259; c&#226;t de mult afecteaz&#259; noua imagine capturat&#259; imaginea de referin&#355;&#259;. Pentru acest proces sunt disponibile dou&#259; metode. Dac&#259; aceast&#259; op&#355;iune este setat&#259; atunci un calcul de baz&#259; este aplicat care, de&#351;i rapid &#351;i exact, poate reduce raza de pixeli din imaginea de referin&#355;&#259;. Dac&#259; ave&#355;i &#351;i o valoare mic&#259; ca minim de diferen&#355;&#259; dintre pixeli, pot ap&#259;rea alarme false. Alternativa este s&#259; dezactiva&#355;i aceast&#259; op&#355;iune, caz &#238;n care vor fi stocate un set de valori temporare care vor elimina erorile. De&#351;i dezactivarea va avea ca rezultat acurate&#355;e mai mare, poate fi de 6 ori mai lent&#259;. Aceast&#259; op&#355;iune  ar trebui dezactivat&#259; doar &#238;n cazul &#238;n care ave&#355;i probleme cu metoda implicit&#259;.";
$zmOlangPromptCOLOUR_JPEG_FILES = "Aplic&#259; culori fi&#351;ierelor JPEG capturate &#238;n scal&#259; de gri";
$zmOlangHelpCOLOUR_JPEG_FILES = "Camerele alb/negru pot aplica scal&#259; de gri fi&#351;ierelor jpeg capturate. Aceste camere economisesc spa&#355;iu &#238;n compara&#355;ie cu cele color. Totu&#351;i unele unelte, cum ar fi ffmpeg &#351;i mpeg_encode, ori nu func&#355;ioneaz&#259; cu aceste set&#259;ri ori trebuie s&#259; transforme imaginile. Activ&#226;nd aceast&#259; op&#355;iune ocupa&#355;i mai mult spa&#355;iu pe disc dar crea&#355;i fi&#351;ierele MPEG mult mai repede.";
$zmOlangPromptJPEG_FILE_QUALITY = "Seteaz&#259; calitatea JPEG pentru imaginile statice (1-100)";
$zmOlangHelpJPEG_FILE_QUALITY = "Atunci c&#226;nd ZoneMinder detecteaz&#259; un eveniment va salva fi&#351;ierele asociate. Aceste fi&#351;iere sunt &#238;n format JPEG &#351;i pot fi v&#259;zute sau difuzate mai departe. Aceast&#259; op&#355;iune specific&#259; calitatea la care vor fi salvate imaginile. Un num&#259;r mare &#238;nseamn&#259; calitate mai bun&#259; dar compresie mai mic&#259;, deci va ocupa spa&#355;iu mai mult pe disc &#351;i va dura mai mult timp s&#259; o &#238;nc&#259;rca&#355;i. Un num&#259;r mai mic &#238;nseamn&#259; spa&#355;iu mai pu&#355;in ocupat, vizualizare mai rapid&#259; dar calitate redus&#259;.";
$zmOlangPromptJPEG_IMAGE_QUALITY = "Seteaz&#259; calitatea JPEG pentru imaginile 'live'(video) (1-100)";
$zmOlangHelpJPEG_IMAGE_QUALITY = "C&#226;nd vizualiza&#355;i un stream 'live' al unui monitor Zoneminder va lua o imagine din buffer &#351;i o va encoda &#238;nainte de a o trimite. Aceast&#259; op&#355;iune specific&#259; ce calitate va fi folosit&#259; pentru encodarea imaginilor. Un num&#259;r mare &#238;nseamn&#259; calitatea bun&#259; dar compresie redus&#259; deci va dura mai mult vizualizarea &#238;n cazul conexiunilor lente. Din contr&#259;, un num&#259;r mic &#238;nseamna vitez&#259; mare de vizualizare dar calitatate redus&#259;. Aceast&#259; op&#355;iune nu se aplic&#259; &#238;n cazul imaginilor statice care vor fi salvate la calitatea specificat&#259; &#238;n op&#355;iune precedent&#259;.";
$zmOlangPromptBLEND_ALARMED_IMAGES = "Combinare imagini de alarm&#259; pentru actualizarea imaginii de referin&#355;&#259;";
$zmOlangHelpBLEND_ALARMED_IMAGES = "Pentru a detecta o alarm&#259; ZoneMinder compar&#259; o imagine cu o imagine de referin&#355;&#259; care este alc&#259;tuit&#259; dintr-o suit&#259; de imagini anterioare. Aceast&#259; op&#355;iune determin&#259; dac&#259; imaginile care cauzeaz&#259; un eveniment vor fi incluse &#238;n acest proces. Activ&#226;nd aceast&#259; op&#355;iune poate cre&#351;te precizia alarmelor dar poate cauza probleme &#238;n cazul schimb&#259;rilor dese de luminozitate, caz &#238;n care alarmele vor persista. O cale mai bun&#259; pentru precizie este sa micsora&#355;i procentajul de combinare de referin&#355;&#259; pentru monitoarele &#238;n cauz&#259;.";
$zmOlangPromptNO_MAX_FPS_ON_ALARM = "Ignor&#259; valoarea FPS Maxim &#238;n cazul unei alarme";
$zmOlangHelpNO_MAX_FPS_ON_ALARM = "C&#226;nd configura&#355;i monitoarele pute&#355;i specifica o valoare maxim&#259; pentru rata de capturare, exprimat&#259; &#238;n cadre pe secund&#259;. Aceasta poate fi folosit&#259; pentru a limita capacit&#259;&#355;ile video, de la&#355;ime de band&#259; sau pentru a reduce supra&#238;nc&#259;rcarea procesorului. Aceast&#259; op&#355;iune 'v-a comunica' ZoneMinder-ului s&#259; ignore aceste limit&#259;ri la apari&#355;ia unei alarme &#351;i s&#259; &#238;ncerce captura c&#226;t mai rapid posibil.";
$zmOlangPromptOPT_ADAPTIVE_SKIP = "Analiza eficient&#259; prin omitere de cadre";
$zmOlangHelpOPT_ADAPTIVE_SKIP = "&#206;n versiuni precedente ale ZoneMinder daemon-ul de analiz&#259; procesa ultimul cadru capturat pentru 'a &#355;ine pasul' cu daemon-ul de captur&#259;. Acest lucru are ca efect secundar lipsa unei buca&#355;i din secven&#355;a de alarm&#259; deoarece toate cadrele precedente alarmei trebuie scrise pe disc &#351;i &#238;n baza de date &#238;nainte de a trece la urm&#259;torul cadru, duc&#226;nd la &#238;nt&#226;rzieri &#238;ntre cadre. Set&#226;nd aceast&#259; op&#355;iune este activat un nou algoritm adaptiv &#238;n care daemon-ul de analiz&#259; &#238;ncearc&#259; procesarea c&#226;t mai multor cadre posibile omi&#355;&#226;nd cadre doar &#238;n cazul &#238;n care daemon-ul de captur&#259; amenin&#355;&#259; suprascrierea cadrelor procesate. Aceast&#259; omitere este variabil&#259; &#238;n func&#355;ie de spa&#355;iul liber &#351;i de memoria tampon. Activarea acestei op&#355;iuni v&#259; ofer&#259; acoperirea mai eficient&#259; a &#238;nceputului alarmelor. Aceast&#259; op&#355;iune poate avea efect de &#238;ncetinire a daemon-ului de analiz&#259; fa&#355;&#259; de daemon-ul de captur&#259; &#238;n timpul evenimentelor &#351;i pentru anumite frecven&#355;e rapide de captur&#259; este posibil ca acest algoritm s&#259; fie cople&#351;it neav&#226;nd timp s&#259; reac&#355;ioneze la construc&#355;ia rapid&#259; a cadrelor, a&#351;adar pot ap&#259;rea blocaje.";
$zmOlangPromptSTRICT_VIDEO_CONFIG = "Permite erorilor &#238;n set&#259;rile video s&#259; fie fatale";
$zmOlangHelpSTRICT_VIDEO_CONFIG = "Unele dispozitive video pot anun&#355;a erori c&#226;nd de fapt ac&#355;iunea a avut succes. Dezactiv&#226;nd aceast&#259; op&#355;iune va permite anun&#355;area de erori &#238;n continuare dar nu va opri daemon-ul de captur&#259;. Aceast&#259; op&#355;iune va avea ca efect ignorarea tuturor erorilor inclusiv cele autentice care poate cauza oprirea capturii video. Folosi&#355;i aceast&#259; op&#355;iune cu aten&#355;ie.";
$zmOlangPromptFORCED_ALARM_SCORE = "Valoarea pentru alarmele for&#355;ate";
$zmOlangHelpFORCED_ALARM_SCORE = "Utilitarul 'zmu' poate fi folosit pentru a for&#355;a o alarm&#259; mai degrab&#259; dec&#226;t bazarea pe algoritmii de detectare a mi&#351;c&#259;rii. Aceast&#259; op&#355;iune determin&#259; ce valoare vor avea alarmele for&#355;ate pentru a fi distinctive fa&#355;&#259; de cele normale. Valoare trebuie s&#259; fie 255 sau mai pu&#355;in.";
$zmOlangPromptBULK_FRAME_INTERVAL = "C&#226;t de des va fi scris un cadru 'masiv' &#238;n baza de date";
$zmOlangHelpBULK_FRAME_INTERVAL = "Tradi&#355;ional ZoneMinder introduce o valoare &#238;n tabelul Frames din baza de date pentru fiecare cadru capturat &#351;i salvat. Aceast&#259; ac&#355;iune func&#355;ioneaz&#259; bine &#238;n cazul &#238;n care ZoneMinder ruleaz&#259; detect&#226;nd mi&#351;care dar &#238;n modurile 'Record' sau 'Mocord' rezult&#259; un num&#259;r imens de cadre care ocup&#259; mult spa&#355;iu &#238;n baza de date &#351;i pe disc. Aplic&#226;nd acestei op&#355;iuni o valoare diferit&#259; de zero va permite ZoneMinder-ului s&#259; grupeze toate cadrele care nu &#355;in de o alarm&#259; &#238;ntr-un cadru 'masiv' care va salva spa&#355;iu &#351;i bandwidth. Singurul dezavantaj al acestei op&#355;iuni este ca informa&#355;iile temporale pentru cadrele individuale sunt pierdute dar &#238;n cazul frecven&#355;ei video constante acest lucru este nesemnificativ. Aceast&#259; setare este ignorat&#259; &#238;n modul Modect iar cadre individuale sunt &#238;nregistrate la apari&#355;ia unei alarme &#238;n modul Mocord.";
$zmOlangPromptEVENT_IMAGE_DIGITS = "C&#226;te cifre sunt folosite pentru numerotarea imaginilor";
$zmOlangHelpEVENT_IMAGE_DIGITS = "Imaginile capturate sunt stocate pe disc cu un index numeric. Implicit acest index are trei cifre deci numele &#238;ncep cu 001, 002, etc. Aceast&#259; setare func&#355;ioneaz&#259; &#238;n majoritatea cazurilor deoarece evenimente cu peste 999 de cadre sunt rar capturate. Oricum dac&#259; ave&#355;i evenimente foarte lungi pute&#355;i m&#259;ri aceast&#259; valoare pentru a asigura sortarea corect&#259; a imaginilor. Aten&#355;ie, cre&#351;terea valorii pe un sistem care ruleaz&#259; poate avea ca efect reorganizarea incorect&#259; a evenimentelor. Descre&#351;terea acestei valorii nu ar trebui s&#259; aib&#259; efecte negative.";
// End of Config tab

// Beginning of Network tab
$zmOlangPromptOPT_REMOTE_CAMERAS = "Folosi&#355;i camere din re&#355;ea";
$zmOlangHelpOPT_REMOTE_CAMERAS = "ZoneMinder ruleaz&#259; at&#226;t cu camere locale, ex. cele ata&#351;ate fizic la computerul dvs. sau camere din re&#355;ea. Daca ve&#355;i folosi camere din re&#355;ea selecta&#355;i aceast&#259; op&#355;iune.";
$zmOlangPromptHTTP_VERSION = "Versiunea de HTTP pe care o va folosi ZoneMinder la conectare";
$zmOlangHelpHTTP_VERSION = "ZoneMinder poate comunica folosit standardele HTTP/1.0 sau HTTP/1.1. Aceast&#259; op&#355;iune specific&#259; care standard va fi folosit.";
$zmOlangPromptHTTP_UA = "Cum se va identifica ZoneMinder";
$zmOlangHelpHTTP_UA = "C&#226;nd ZoneMinder comunic&#259; cu camere din re&#355;ea se va identifica folosind acest string &#351;i versiunea. &#206;n mod normal aceast&#259; setare este suficient&#259;, totu&#351;i dac&#259; o anume camera nu va rula numai cu un anumit browser, aceast&#259; op&#355;iune se poate schimba pentru a identifica ZoneMinder ca fiind Internet Explorer, Netscape, etc.";
$zmOlangPromptHTTP_TIMEOUT = "C&#226;t a&#351;teapt&#259; ZoneMinder p&#226;n&#259; la decizia c&#259; imaginea nu poate fi desc&#259;rcat&#259; (milisecunde)";
$zmOlangHelpHTTP_TIMEOUT = "La desc&#259;rcarea imaginilor remote ZoneMinder va a&#351;tepta at&#226;t timp &#238;nainte de a decide c&#259; imaginea nu poate fi desc&#259;rcat&#259; &#351;i va re&#238;ncerca. Acest timp expirat este exprimat &#238;n milisecunde &#351;i va fi aplicat fiec&#259;rei p&#259;r&#355;i din imagine dac&#259; imaginea nu este trimis&#259; ca tot unitar.";
// End of Network tab

// Beginning of Web tab
$zmOlangPromptWEB_POPUP_ON_ALARM = "Fereastra monitorului deasupra tuturor ferestrelor la apari&#355;ia unei alarme";
$zmOlangHelpWEB_POPUP_ON_ALARM = "La vizionarea unui flux video 'live' pute&#355;i specifica dac&#259; vre&#355;i sau nu ca fereastra monitorului s&#259; sar&#259; deasupra tuturor ferestrelor &#238;n cazul apari&#355;iei unei alarme.";
$zmOlangPromptWEB_SOUND_ON_ALARM = "Redare sunet la apari&#355;ia unei alarme";
$zmOlangHelpWEB_SOUND_ON_ALARM = "La vizionarea unui flux video 'live' pute&#355;i specifica dac&#259; vre&#355;i sau nu redarea unui sunet pentru a va aten&#355;iona de apari&#355;ia unei alarme.";
$zmOlangPromptWEB_ALARM_SOUND = "Sunet de redat la alarme";
$zmOlangHelpWEB_ALARM_SOUND = "Pute&#355;i specifica un fi&#351;ier audio care va fi redat &#238;n cazul unei alarme. At&#226;t timp c&#226;t browser-ul &#238;n&#355;elege formatul sunetul nu trebuie s&#259; fie de un anumit tip. Acest fi&#351;ier trebuie pus &#238;n directorul de fi&#351;iere audio.";
$zmOlangPromptWEB_COMPACT_MONTAGE = "Compactarea montajului prin omiterea detaliilor";
$zmOlangHelpWEB_COMPACT_MONTAGE = "Modul de vizualizare &#238;n montaj afi&#351;eaz&#259; toate monitoarele active &#238;ntr-o singur&#259; fereastr&#259;. Acesta include un meniu mic &#351;i informa&#355;iile de stare pentru fiecare. Acesta poate cre&#351;te traficul &#351;i poate face fereastra mai mare dec&#226;t dorit&#259;. Activarea acestei op&#355;iuni omite toate informa&#355;iile adi&#355;ionale &#351;i afi&#351;eaz&#259; imaginile.";
$zmOlangPromptWEB_MONTAGE_MAX_COLS = "Num&#259;r maxim de coloane de monitoare &#238;n vizualizare monataj";
$zmOlangHelpWEB_MONTAGE_MAX_COLS = "Vizualizarea montaj afi&#351;eaz&#259; imagini de la toate monitoarele. Acest parametru define&#351;te c&#226;te monitoare vor fi pozi&#355;ionate pe ecran &#238;nainte de a trece la urm&#259;torul r&#226;nd. Dac&#259; ave&#355;i ecran foarte lat &#351;i/sau imagini mici de la camere acesta poate avea valori mai mari.";
$zmOlangPromptWEB_MONTAGE_WIDTH = "L&#259;&#355;ime monitor &#238;n vizualizare montaj";
$zmOlangHelpWEB_MONTAGE_WIDTH = "&#206;n modul de vizualizare montaj pute&#355;i vizualiza toate monitoarele concomitent. Dac&#259; au dimensiuni diferite fereastra poate ap&#259;rea deformat&#259;. Setarea acestei op&#355;iuni v&#259; permite s&#259; mentine&#355;i la&#355;imea fiec&#259;rui monitor la o valoare fix&#259; fac&#226;nd fereastra mai ordonat&#259;. Las&#226;nd aceast&#259; valoare zero permite afi&#351;area fiec&#259;rui monitor &#238;n dimensiunea sa nativ&#259;.";
$zmOlangPromptWEB_MONTAGE_HEIGHT = "&#206;n&#259;l&#355;ime monitor &#238;n vizualizare montaj";
$zmOlangHelpWEB_MONTAGE_HEIGHT = "&#206;n modul de vizualizare montaj pute&#355;i vizualiza toate monitoarele concomitent. Dac&#259; au dimensiuni diferite fereastra poate ap&#259;rea deformat&#259;. Setarea acestei op&#355;iuni v&#259; permite s&#259; mentine&#355;i &#238;n&#259;l&#355;imea fiec&#259;rui monitor la o valoare fix&#259; fac&#226;nd fereastra mai ordonat&#259;. Las&#226;nd aceast&#259; valoare zero permite afi&#351;area fiec&#259;rui monitor &#238;n dimensiunea sa nativ&#259;.";
$zmOlangPromptWEB_REFRESH_METHOD = "Metoda pentru actualizarea ferestrelor, alege&#355;i javascript sau http";
$zmOlangHelpWEB_REFRESH_METHOD = "Multe ferestre &#238;n JavaScript trebuie actulizate pentru a avea informa&#355;ii curente. Aceast&#259; op&#355;iune determin&#259; ce metod&#259; v-a fi folosit&#259; pentru actualizare. Dac&#259; alege&#355;i 'javascript' fiecare fereastr&#259; va avea o scurt&#259; instruc&#355;iune JavaScript pentru actualizare. Aceasta este cea mai compatibil&#259; metod&#259;. Dac&#259; alege&#355;i 'http' instruc&#355;iunea de actulizare va fi &#238;n antetul HTTP. Aceasta este metoda mai curat&#259; dar actuliz&#259;rile sunt &#238;ntrerupte sau revocate c&#226;nd face&#355;i click pe un link din fereastr&#259;.";
$zmOlangPromptWEB_DOUBLE_BUFFER = "Memorie tampon dubl&#259; pentru a evita p&#226;lp&#226;itul imaginilor";
$zmOlangHelpWEB_DOUBLE_BUFFER = "&#206;ncep&#226;nd cu versiunea 1.18.0 ZoneMinder poate folosi memorie tampon dubl&#259; pentru a pre&#238;nc&#259;rca imaginile &#238;nainte de a fi afi&#351;ate pe ecran. Aceast&#259; metod&#259; reduce p&#226;lp&#226;itul imaginilor. Totu&#351;i unele dispozitive nu suport&#259; combina&#355;ia JavaScript/cadre necesar&#259; pentru aceasta caz &#238;n care aceast&#259; op&#355;iune ar trebui dezactivat&#259;. &#538;in&#226;nd cont c&#259; aceast&#259; op&#355;iune folose&#351;te JavaScript va avea efect doar dac&#259; este setat&#259; &#351;i op&#355;iunea ZM_WEB_REFRESH_METHOD.";
$zmOlangPromptWEB_EVENTS_PER_PAGE = "C&#226;te evenimente sunt afi&#351;ate pe pagin&#259;";
$zmOlangHelpWEB_EVENTS_PER_PAGE = "&#206;n modul de vizualizare al evenimentelor pute&#355;i afi&#351;a toate evenimentele sau numai c&#226;te o pagin&#259;. Aceast&#259; op&#355;iune controleaz&#259; c&#226;te evenimente sunt afi&#351;ate &#238;ntr-o pagin&#259;.";
$zmOlangPromptWEB_FRAMES_PER_LINE = "C&#226;te cadre sunt afi&#351;ate pe linie";
$zmOlangHelpWEB_FRAMES_PER_LINE = "La vizualizarea cadrelor evenimentelor pute&#355;i vizualizare cadrele individuale care compun un eveniment. Aceast&#259; op&#355;iune v&#259; permite s&#259; specifica&#355;i c&#226;te cadre vor fi pe fiecare linie. Rezultatul acestei op&#355;iuni &#351;i al op&#355;iunii urm&#259;toare este num&#259;rul de cadre pe pagin&#259;.";
$zmOlangPromptWEB_FRAME_LINES = "C&#226;te linii cu cadre sunt afi&#351;ate";
$zmOlangHelpWEB_FRAME_LINES = "La vizualizarea cadrelor evenimentelor pute&#355;i vizualizare cadrele individuale care compun un eveniment. Aceast&#259; op&#355;iune v&#259; permite s&#259; specifica&#355;i c&#226;te linii cu cadre vor fi afi&#351;ate. Rezultatul acestei op&#355;iuni &#351;i al op&#355;iunii precedente este num&#259;rul de cadre pe pagin&#259;.";
$zmOlangPromptWEB_LIST_THUMBS = "Afi&#351;eaza miniaturi ale imaginilor &#238;n lista evenimentelor";
$zmOlangHelpWEB_LIST_THUMBS = "&#206;n mod normal &#238;n lista evenimentelor sunt afi&#351;ate doar detaliile textuale ale evenimentelor pentru a se economisi spa&#355;iu &#351;i timp. La activarea aceastei op&#355;iuni vor fi afi&#351;ate &#351;i imagini miniaturale pentru a v&#259; ajuta s&#259; indentifica&#355;i evenimentele de interes. M&#259;rimea miniaturilor este controlat&#259; de urm&#259;toarele dou&#259; op&#355;iuni.";
$zmOlangPromptWEB_LIST_THUMB_WIDTH = "L&#259;&#355;imea miniaturilor ce apar &#238;n lista evenimentelor";
$zmOlangHelpWEB_LIST_THUMB_WIDTH = "Aceast&#259; op&#355;iune controleaz&#259; la&#355;imea imaginilor miniaturale care apar &#238;n lista evenimentelor. Ar trebui s&#259; fie destul de mic&#259; pentru a putea fi cuprins&#259; &#238;n restul tabelului. Dac&#259; dori&#355;i pute&#355;i specifica &#238;n&#259;l&#355;imea din urm&#259;toarea op&#355;iune dar folosi&#355;i doar una din cele dou&#259; op&#355;iuni cealalt&#259; av&#226;nd valoarea zero. Dac&#259; sunt specificate at&#226;t la&#355;imea c&#226;t &#351;i &#238;n&#259;l&#355;imea va fi folosit&#259; doar l&#259;&#355;imea, &#238;n&#259;l&#355;imea fiind ignorat&#259;.";
$zmOlangPromptWEB_LIST_THUMB_HEIGHT = "&#206;n&#259;l&#355;imea miniaturilor ce apar &#238;n lista evenimentelor";
$zmOlangHelpWEB_LIST_THUMB_HEIGHT = "Aceast&#259; op&#355;iune controleaz&#259; &#238;n&#259;l&#355;imea imaginilor miniaturale care apar &#238;n lista evenimentelor. Ar trebui s&#259; fie destul de mic&#259; pentru a putea fi cuprins&#259; &#238;n restul tabelului. Dac&#259; dori&#355;i pute&#355;i specifica l&#259;&#355;imea din op&#355;iunea precedent&#259; dar folosi&#355;i doar una din cele dou&#259; op&#355;iuni cealalt&#259; av&#226;nd valoarea zero. Dac&#259; sunt specificate at&#226;t la&#355;imea c&#226;t &#351;i &#238;n&#259;l&#355;imea va fi folosit&#259; doar l&#259;&#355;imea, &#238;n&#259;l&#355;imea fiind ignorat&#259;.";
// End of Web tab

// Beginning of Video tab
$zmOlangPromptVIDEO_STREAM_METHOD = "Ce metod&#259; va fi folosit&#259; pentru a trimite imaginile la browser, alege&#355;i 'mpeg' sau 'jpeg'";
$zmOlangHelpVIDEO_STREAM_METHOD = "ZoneMinder poate fi configurat fie s&#259; codeze capturile &#238;n format mpeg sau &#238;ntr-o serie de imagini statice. Aceast&#259; op&#355;iune define&#351;te metoda ce va fi folosit&#259;. Dac&#259; alege&#355;i mpeg asigura&#355;i-v&#259; c&#259; ave&#355;i plugin-urile necesare pt browser-ul dvs. Op&#355;iunea jpeg ruleaz&#259; pe instal&#259;ri implicite ale browser-elor din familia Mozilla &#351;i cu un applet Java pentru Internet Explorer.";
$zmOlangPromptVIDEO_TIMED_FRAMES = "Cadrele vor avea imprimate data &#351;i ora";
$zmOlangHelpVIDEO_TIMED_FRAMES = "C&#226;nd folosi&#355;i flux video MPEG, fie pentru flux video 'live' sau pentru evenimente, ZoneMinder poate trimite imaginile &#238;n dou&#259; feluri. Dac&#259; aceast&#259; op&#355;iune este setat&#259; atunci data &#351;i ora vor fi incluse &#238;n fluxul video. Acest lucru &#238;nseamn&#259; c&#259; atunci c&#226;nd rata cadrelor variaz&#259;, cum ar fi cazul unei alarme, fluxul &#238;&#351;i va men&#355;ine sincronizarea. Dac&#259; aceast&#259; op&#355;iune nu este activat&#259; atunci este calculat&#259; o rat&#259; aproximativ&#259; a cadrelor. Aceast&#259; op&#355;iune poate fi dezactivat&#259; dac&#259; ave&#355;i probleme cu metoda dvs. preferat&#259; de streaming.";
$zmOlangPromptVIDEO_LIVE_FORMAT = "&#206;n ce format sunt rulate fluxurile video 'live'";
$zmOlangHelpVIDEO_LIVE_FORMAT = "C&#226;nd folosi&#355;i metoda MPEG ZoneMinder poate genera secven&#355;e video. Formatele suportate de browser variaz&#259; de la un sistem la altul. Aceast&#259; op&#355;iune v&#259; permite s&#259; specifica&#355;i formatul video, folosind o extensie pentru fi&#351;iere, deci trebuie s&#259; introduce&#355;i doar extensia iar restul este determinat automat. Formatul implicit 'asf' func&#355;ioneaz&#259; pe Windows folosind Windows Media Player, iar pe Linux pute&#355;i folosi gxine sau mplayer. Dac&#259; aceast&#259; op&#355;iune nu este setat&#259; atunci fluxurile video 'live' vor fi secven&#355;e de fi&#351;iere jpeg.";
$zmOlangPromptVIDEO_REPLAY_FORMAT = "&#206;n ce format sunt redate fluxurile video";
$zmOlangHelpVIDEO_REPLAY_FORMAT = "Folosind metoda MPEG ZoneMinder poate revizuliza evenimentele &#238;n format video codat. Formatele suportate de browser variaz&#259; de la un sistem la altul. Aceast&#259; op&#355;iune v&#259; permite s&#259; specifica&#355;i formatul video, folosind o extensie pentru fi&#351;iere, deci trebuie s&#259; introduce&#355;i doar extensia iar restul este determinat automat. Formatul implicit 'asf' func&#355;ioneaz&#259; pe Windows folosind Windows Media Player, iar pe Linux pute&#355;i folosi gxine sau mplayer. Dac&#259; aceast&#259; op&#355;iune nu este setat&#259; atunci fluxurile video vor fi secven&#355;e de fi&#351;iere jpeg.";
// End of Video tab

// Beginning or Email tab
$zmOlangPromptOPT_EMAIL = "Trimite e-mail cu detaliile evenimentelor corespunz&#259;toare anumitor filtre";
$zmOlangHelpOPT_EMAIL = "&#206;n ZoneMinder pute&#355;i crea filtre pentru evenimente care specific&#259; dac&#259; detaliile evenimentelor filtrate sub un anumit criteriu vor fi trimise prin e-mail la o adres&#259; desemnat&#259;. Astfel ve&#355;i putea fi anun&#355;at imediat ce apar evenimente. Aceast&#259; op&#355;iune specific&#259; dac&#259; aceast&#259; func&#355;ie este activ&#259;. E-mail-ul creat cu aceast&#259; op&#355;iune poate fi de orice dimensiune &#351;i nu este dedicat dispozitivelor mobile.";
$zmOlangPromptEMAIL_ADDRESS = "E-mail-ul la care vor fi trimise detaliile evenimentelor";
$zmOlangHelpEMAIL_ADDRESS = "Aceast&#259; op&#355;iune este folosit&#259; pentru a defini adresa de e-mail la care vor fi trimise evenimentele corespunz&#259;toare filtrelor setate.";
$zmOlangPromptEMAIL_TEXT = "Con&#355;inutul e-mail-ului cu detaliile evenimentelor";
$zmOlangHelpEMAIL_TEXT = "Aceast&#259; op&#355;iune este folosit&#259; pentru a defini con&#355;inutul e-mail-ului trimis.";
$zmOlangPromptOPT_MESSAGE = "Trimite mesaj cu detaliile evenimentelor corespunz&#259;toare anumitor filtre (pentru dispozitive mobile) ";
$zmOlangHelpOPT_MESSAGE = "&#206;n ZoneMinder pute&#355;i crea filtre pentru evenimente care specific&#259; dac&#259; detaliile evenimentelor filtrate sub un anumit criteriu vor fi trimise prin e-mail la o adres&#259; desemnat&#259;. Astfel ve&#355;i putea fi anun&#355;at imediat ce apar evenimente. Aceast&#259; op&#355;iune specific&#259; dac&#259; aceast&#259; func&#355;ie este activ&#259;. E-mail-ul creat de aceast&#259; op&#355;iune va fi succint &#351;i este dedicat trimiterii lui c&#259;tre un gateway SMS sau c&#259;tre un cititor de e-mail minimal cum ar fi un dispozitiv mobil.";
$zmOlangPromptMESSAGE_ADDRESS = "E-mail-ul la care vor fi trimise detaliile evenimentelor";
$zmOlangHelptMESSAGE_ADDRESS = "Aceast&#259; op&#355;iune este folosit&#259; pentru a defini adresa de e-mail la care va fi trasmis mesajul.";
$zmOlangPromptMESSAGE_TEXT = "Con&#355;inutul mesajului cu detaliile evenimentelor";
$zmOlangHelpMESSAGE_TEXT = "Aceast&#259; op&#355;iune este folosit&#259; pentru a defini con&#355;inutul mesajului trimis.";
$zmOlangPromptEMAIL_METHOD = "Metoda folosit&#259; pentru trasmiterea e-mail-urilor &#351;i mesajelor";
$zmOlangHelpEMAIL_METHOD = "ZoneMinder trebuie s&#259; &#351;tie cum s&#259; trimit&#259; e-mail sau mesaj. Aceast&#259; op&#355;iune specific&#259; ce metod&#259; va fi folosit&#259;. &#206;n general 'sendmail' va func&#355;iona dac&#259; este configurat corespunz&#259;tor; &#238;n caz contrat alege&#355;i 'smtp' &#351;i specifica&#355;i gazda pe care ruleaz&#259; smtp &#238;n urm&#259;toare op&#355;iune.";
$zmOlangPromptEMAIL_HOST = "Gazda serverului SMTP";
$zmOlangHelpEMAIL_HOST = "Dac&#259; a&#355;i ales SMTP ca metod&#259; de transmitere a e-mail-urilor &#351;i mesajelor atunci aceast&#259; op&#355;iune va specifica serverul SMTP folosit. Setarea implicit&#259;, localhost, s-ar putea s&#259; func&#355;ioneze dac&#259; ave&#355;i sendmail, exim sau un daemon similar; pute&#355;i introduce serverul SMTP de la ISP-ul dvs., de exemplu.";
$zmOlangPromptFROM_EMAIL = "E-mail-ul expeditor al notific&#259;rilor";
$zmOlangHelpFROM_EMAIL = "E-mail-urile sau mesajele trimise de ZoneMinder pot avea ca e-mail expeditor o adres&#259; desemnat&#259; pentru a v&#259; ajuta s&#259; le identifica&#355;i. Este recomandat&#259; o adres&#259; de tipul ZoneMinder@domeniu.com.";
$zmOlangPromptURL = "Adresa (URL) unde este instalat ZoneMinder";
$zmOlangHelpURL = "E-mail-urile sau mesajele care va vor fi trimise pot include un link la evenimente pentru acces rapid. Dac&#259; dori&#355;i s&#259; folosi&#355;i aceast&#259; caracteristic&#259; atunci introduce&#355;i adresa unde este instalat ZoneMinder, de ex. http://gazda.domeniu.com/zm.php.";
// End of Email tab

// Beginning of FTP tab
$zmOlangPromptOPT_UPLOAD = "Upload evenimente care se potrivesc filtrelor corespunz&#259;toare.";
$zmOlangHelpOPT_UPLOAD = "&#206;n ZoneMinder pute&#355;i creea filtre pentru evenimente care specific&#259; dac&#259; evenimentele care corespund unui anumit criteriu sa fie upload-ate pe un server remote. Aceast&#259; op&#355;iune specific&#259; dac&#259; aceast&#259; func&#355;ie s&#259; fie disponibil&#259;.";
$zmOlangPromptUPLOAD_ARCH_FORMAT = "Ce format vor avea fi&#351;ierele &#238;nc&#259;rcate, 'tar' sau 'zip'";
$zmOlangHelpUPLOAD_ARCH_FORMAT = "Evenimentele upload-ate pot fi &#238;n format .tar. sau .zip. Pentru a folosi aceast&#259; op&#355;iune trebuie s&#259; ave&#355;i instalate modulele perl Archive::Tar &#351;i/sau Archive::Zip.";
$zmOlangPromptUPLOAD_ARCH_COMPRESS = "Comprimare fi&#351;iere arhiv&#259;";
$zmOlangHelpUPLOAD_ARCH_COMPRESS = "Arhivele create pot fi comprimate. &#238;n general imaginile sunt deja comprimate &#351;i nu salva&#355;i prea mult spa&#355;iu activ&#226;nd aceast&#259; op&#355;iune. Activa&#355;i aceast&#259; op&#355;iune numai dac&#259; ave&#355;i resurse de irosit, spa&#355;iu sau bandwidth limitat.";
$zmOlangPromptUPLOAD_ARCH_ANALYSE = "Include analiza imaginilor &#238;n fi&#351;ierele &#238;nc&#259;rcate.";
$zmOlangHelpUPLOAD_ARCH_ANALYSE = "Arhivele create pot con&#355;ine numai cadre capturate sau cadrele capturate &#351;i analiza imaginilor care au generat alarme. Aceast&#259; op&#355;iune controleaz&#259; ce pot con&#355;ine arhivele. Include-&#355;i analiza numai dac&#259; ave&#355;i conexiune rapid&#259; la server-ul remote sau dac&#259; ave&#355;i nevoie de detalii despre cauza alarmei.";
$zmOlangPromptUPLOAD_FTP_HOST = "Server-ul la distan&#355;&#259; unde se &#238;ncarc&#259; fisiere";
$zmOlangHelpUPLOAD_FTP_HOST = "Acesta este serverul &#238;ndep&#259;rtat unde dori&#355;i s&#259; &#238;nc&#259;rca&#355;i evenimentele.";
$zmOlangPromptUPLOAD_FTP_USER = "Utilizator FTP";
$zmOlangHelpUPLOAD_FTP_USER = "Utilizator FTP la serverul remote";
$zmOlangPromptUPLOAD_FTP_PASS = "Parola FTP";
$zmOlangHelpUPLOAD_FTP_PASS = "Parola FTP la serverul remote";
$zmOlangPromptUPLOAD_FTP_LOC_DIR = "Directorul &#238;n care vor fi create fi&#351;ierele ce urmeaz&#259; &#238;nc&#259;rcate";
$zmOlangHelpUPLOAD_FTP_LOC_DIR = "Directorul local &#238;n care vor fi create fi&#351;ierele ce urmeaz&#259; &#238;nc&#259;rcate";
$zmOlangPromptUPLOAD_FTP_REM_DIR = "Directorul remote &#238;n care se &#238;ncarc&#259;";
$zmOlangHelpUPLOAD_FTP_REM_DIR = "";
$zmOlangPromptUPLOAD_FTP_TIMEOUT = "C&#226;t timp permitem pentru transferarea fiec&#259;rui fi&#351;ier.";
$zmOlangHelpUPLOAD_FTP_TIMEOUT = "C&#226;t timp (&#238;n secunde) permitem pentru transferarea fiec&#259;rui fi&#351;ier.";
$zmOlangPromptUPLOAD_FTP_PASSIVE = "FTP in mod pasiv";
$zmOlangHelpUPLOAD_FTP_PASSIVE = "Dac&#259; computerul dvs. este &#238;n spatele unui firewall sau proxy s-ar putea s&#259; trebuiasc&#259; s&#259; folosi&#355;i FTP &#238;n mod pasiv.";
$zmOlangPromptUPLOAD_FTP_DEBUG = "FTP &#238;n mod debugging";
$zmOlangHelpUPLOAD_FTP_DEBUG = "Dac&#259; ave&#355;i probleme cu &#238;nc&#259;rcatul activa&#355;i aceast&#259; op&#355;iune, care va include informa&#355;ii suplimentare &#238;n logul zmfilter.";
// End of FTP tab

// Beginning of X10 tab
$zmOlangPromptOPT_X10 = "Interac&#355;ioneaz&#259; cu dispozitive X10";
$zmOlangHelpOPT_X10 = "Dac&#259; ave&#355;i un dispozitiv X10 pute&#355;i seta ZoneMinder s&#259; reac&#355;ioneze la semnalele emise de dispozitivul X10 dac&#259; computerul dvs. are controller-ul necesar. Aceast&#259; op&#355;iune indic&#259; dac&#259; op&#355;iunile X10 vor fi disponibile sau nu. ";
$zmOlangPromptX10_DEVICE = "Pe ce dispozitiv (software) este conectat dispozitivul X10";
$zmOlangHelpX10_DEVICE = "Dac&#259; ave&#355;i un controller X10 conectat la computerul dvs. aceast&#259; op&#355;iune specific&#259; pe ce port este conectat, valoare implicit&#259; /dev/ttyS0 reprezint&#259; portul serial sau portul COM 1.";
$zmOlangPromptX10_HOUSE_CODE = "Cod X10 folosit";
$zmOlangHelpX10_HOUSE_CODE = "Dispozitivele X10 sunt grupate indentific&#226;ndu-le ca apar&#355;in&#226;nd unui anumit cod al casei. Aceast&#259; op&#355;iune trebuie s&#259; fie o singur&#259; liter&#259; &#238;ntre A si P.";
$zmOlangPromptX10_DB_RELOAD_INTERVAL = "C&#226;t de des (&#238;n secunde) daemon-ul X10 actualizeaz&#259; monitoare din baza de date.";
$zmOlangHelpX10_DB_RELOAD_INTERVAL = "Daemon-ul zmx10 verific&#259; periodic baza de date pentru a descoperi eventualele alarme. Aceast&#259; op&#355;iune determin&#259; c&#226;t de des se face verificarea.";
// End of FTP tab

// Beginning of Tools tab
$zmOlangPromptCAN_STREAM = "&#206;nlocuie&#351;te detectarea automat&#259; a capacit&#259;&#355;ilor de streaming ale browser-ului";
$zmOlangHelpCAN_STREAM = "Dac&#259; &#351;ti&#355;i c&#259; browser-ul dvs. suport&#259; streaming de imagini dar ZoneMinder nu detecteaz&#259; aceast&#259; op&#355;iune corect pute&#355;i seta aceast&#259; op&#355;iune pentru a v&#259; asigura c&#259; fluxurile sunt transmise cu sau f&#259;r&#259; folosirea Cambozola. Selec&#355;ia 'yes' v-a spune ZoneMinder-ului c&#259; broswer-ul dvs. suport&#259; streaming  nativ, 'no' &#238;nseamn&#259; c&#259; nu suport&#259; deci va fi folosit Cambozola iar 'auto' v-a l&#259;sa ZoneMinder s&#259; decid&#259;.";
$zmOlangPromptRAND_STREAM = "Adaug&#259;re string aleator pentru a preveni tamponarea fluxurilor";
$zmOlangHelpRAND_STREAM = "Unele browsere pot &#238;nregistra &#238;n memoria tampon fluxurile folosite de ZoneMinder. Pentru a preveni acest lucru se poate adaug&#259; un string aleator pentru a face fiecare invocare a fluxului aparent unic&#259;.";
$zmOlangPromptOPT_CAMBOZOLA = "Este instalat(op&#355;ional) client-ul cambozola(recomandat)";
$zmOlangHelpOPT_CAMBOZOLA = "Cambozola este un Java applet care este folosit de ZoneMinder pentru a fluxurile de imagini &#238;ntr-un navigator ca Internet Explorer. Este recomandat s&#259; instala&#355;i cambozola de la http://www.charliemouse.com/code/cambozola/ Chiar dac&#259; nu e instalat ve&#355;i putea vizualiza imagini statice la o rat&#259; mic&#259; de actulizare.";
$zmOlangPromptPATH_CAMBOZOLA = "Calea web la cambozola (recomandat)";
$zmOlangHelpPATH_CAMBOZOLA = "Cambozola este un Java applet care este folosit de ZoneMinder pentru a fluxurile de imagini &#238;ntr-un navigator ca Internet Explorer. Este recomandat s&#259; instala&#355;i cambozola de la http://www.charliemouse.com/code/cambozola/ Chiar dac&#259; nu e instalat ve&#355;i putea vizualiza imagini statice la o rat&#259; mic&#259; de actulizare. Seta&#355;i aceast&#259; op&#355;iune 'camboloza.jar' dac&#259; cambozola este instalat &#238;n acela&#351;i director cu fi&#351;ierele web ZoneMinder. ";
$zmOlangPromptOPT_MPEG = "Este instalat codor video mpeg (op&#355;ional)";
$zmOlangHelpOPT_MPEG = "ZoneMinder poate &#238;nregistra o serie de imagini &#238;n format MPEG. Aceast&#259; op&#355;iune v&#259; permite s&#259; specifica&#355;i dac&#259; ave&#355;i un codor mpeg instalat. Cele dou&#259; codoare suportate de ZoneMinder sunt mpeg_encode &#351;i ffmpeg, ultimul fiind cel mai rapid. Crearea de fi&#351;iere MPEG consum&#259; resursele procesorului &#351;i nu este necesar&#259; deoarece evenimentele pot fi vizualizare ca flux video.";
$zmOlangPromptPATH_MPEG_ENCODE = "Calea la codorul mpeg Berkeley (op&#355;ional)";
$zmOlangHelpPATH_MPEG_ENCODE = "Aceasta este calea la codorul mpeg Berkeley (op&#355;ional).";
$zmOlangPromptPATH_FFMPEG = "Calea la codorul mpeg ffmpeg (op&#355;ional)";
$zmOlangHelpPATH_FFMPEG = "Aceasta este calea la codorul mpeg ffmpeg.";
$zmOlangPromptFFMPEG_OPTIONS = "Op&#355;iuni adi&#355;ionale pentru ffmpeg";
$zmOlangHelpFFMPEG_OPTIONS = "Ffmpeg suport&#259; multe op&#355;iuni pentru controlul calit&#259;&#355;ii secven&#355;ei video produse. Aceast&#259; op&#355;iune v&#259; permite s&#259; specifica&#355;i propriile op&#355;iuni. Citi&#355;i documenta&#355;ia ffmpeg pentru mai multe detalii.";
$zmOlangPromptOPT_NETPBM = "Sunt instalate utilitarele Netpbm (op&#355;ional)";
$zmOlangHelpOPT_NETPBM = "&#206;n cazul la&#355;imii de band&#259; redus&#259; ZoneMinder va miniaturiza imaginile &#238;nainte de a le direc&#355;iona spre browser pentru a reduce traficul. Pentru aceasta folose&#351;te pachetul Netpbm; aceast&#259; op&#355;iune ar trebuie s&#259; direc&#355;ioneze ZoneMinder spre binarele pachetului. Dac&#259; nu ave&#355;i pachetul Netpbm instalat imaginilor vor fi &#238;ntotdeauna trimise la scar&#259; real&#259; &#351;i redimensionate &#238;n browser.";
$zmOlangPromptPATH_NETPBM = "Cale la utilitarele Netpbm (op&#355;ional)";
$zmOlangHelpPATH_NETPBM = "Calea la utilitarele Netpbm (op&#355;ional)";
$zmOlangPromptOPT_TRIGGERS = "Interac&#355;ioneaz&#259; cu declan&#351;atoare externe via socket sau fi&#351;ierele dispozitivelor";
$zmOlangHelpOPT_TRIGGERS = "ZoneMinder poate interac&#355;iona cu sisteme externe care ac&#355;ioneaz&#259; sau revoc&#259; o alarm&#259;. Acest lucru este realizat prin intermediului script-ului zmtrigger.pl. Aceast&#259; op&#355;iune indic&#259; folosirea declan&#351;atoarelor externe, majoritatea vor alege nu aici.";

// End of Tools tab

// Beginning of High Banwidth tab
$zmOlangPromptWEB_H_REFRESH_MAIN = "C&#226;t de des (&#238;n secunde) se va actualiza fereastra principal&#259;";
$zmOlangHelpWEB_H_REFRESH_MAIN = "&#206;n fereastra principal&#259; sunt afi&#351;ate starea general&#259; &#351;i totalul evenimentelor pentru toate monitoarele. Aceast&#259; sarcin&#259; nu trebuie repetat&#259; frecvent; s-ar putea s&#259; afecteze performan&#355;a sistemului.";
$zmOlangPromptWEB_H_REFRESH_CYCLE = "C&#226;t de des (&#238;n secunde) se vor schimba imaginile &#238;n ciclul de monitorizare.";
$zmOlangHelpWEB_H_REFRESH_CYCLE = "Ciclul de monitorizare este metoda de schimbare continu&#259; a imaginilor monitoarelor. Aceast&#259; op&#355;iune determin&#259; c&#226;t de des va fi actulizat cu o nou&#259; imagine.";
$zmOlangPromptWEB_H_REFRESH_IMAGE = "C&#226;t de des (&#238;n secunde) sunt actulizate imaginile statice";
$zmOlangHelpWEB_H_REFRESH_IMAGE = "Imaginile 'live' ale unui monitor pot fi vizulizate &#238;n flux de imagini (video) sau imagini statice. Aceast&#259; op&#355;iune determin&#259; c&#226;t de des vor fi actualizate imaginile statice, nu are nici un efect dac&#259; este selectat&#259; metoda flux video (streaming).";
$zmOlangPromptWEB_H_REFRESH_STATUS = "C&#226;t de des va fi actualizat cadrul de stare";
$zmOlangHelpWEB_H_REFRESH_STATUS = "Fereastra monitorului este alc&#259;tuit&#259; din mai multe cadre. Cadrul din mijloc con&#355;ine starea monitorului &#351;i trebuie actualizat&#259; destul de frecvent pentru a indica valori reale. Aceast&#259; op&#355;iune determin&#259; frecven&#355;a respectiv&#259;.";
$zmOlangPromptWEB_H_REFRESH_EVENTS = "C&#226;t de des (&#238;n secunde) este actulizat&#259; lista evenimentelor din fereastra principal&#259;";
$zmOlangHelpWEB_H_REFRESH_EVENTS = "Fereastra monitorului este alc&#259;tuit&#259; din mai multe cadre. Cadrul inferior con&#355;ine o list&#259; a ultimelor evenimente pentru acces rapid. Aceast&#259; op&#355;iune determin&#259; c&#226;t de des este actulizat acest cadru.";
$zmOlangPromptWEB_H_DEFAULT_SCALE = "Care este scara implicit&#259; ce se aplica vizualiz&#259;rii 'live' sau a evenimentelor (%)";
$zmOlangHelpWEB_H_DEFAULT_SCALE = "&#206;n mod normal ZoneMinder va afi&#351;a fluxurile 'live' sau evenimentele &#238;n marime nativ&#259;. Dac&#259; ave&#355;i monitoare de dimensiuni mari pute&#355;i reduce aceast&#259; m&#259;rime, iar pentru monitoare de dimensiuni mici pute&#355;i redimensiona &#238;n sens pozitiv aceast&#259; m&#259;rime. Prin intermediul acestei op&#355;iuni pute&#355;i specifica care va fi factorul implicit de scar&#259;. Este exprimat &#238;n procente deci 100 va fi dimensiune normal&#259;, 200 dimensiune dubl&#259; etc.";
$zmOlangPromptWEB_H_DEFAULT_RATE = "Viteza de redare a evenimentelor (%)";
$zmOlangHelpWEB_H_DEFAULT_RATE = "&#206;n mod normal ZoneMinder va afi&#351;a fluxurile video la viteza lor nativ&#259;. Dac&#259; ave&#355;i evenimente de lung&#259; durat&#259; este mai convenabil&#259; redarea lor la o rat&#259; mai mare. Aceast&#259; op&#355;iune v&#259; permite sa specifica&#355;i rata de redare. Este exprimat&#259; &#238;n procente deci 100 este rata normal&#259;, 200 este vitez&#259; dubl&#259;, etc.";
$zmOlangPromptWEB_H_VIDEO_BITRATE = "Rata bi&#355;ilor (bit rate) la care este codat fluxul video";
$zmOlangHelpWEB_H_VIDEO_BITRATE = "La codarea secven&#355;elor video prin intermediul libr&#259;riei ffmpeg poate fi specificat&#259; o rat&#259; a bi&#355;ilor (bit rate) care corespunde, &#238;n linii mari, l&#259;&#355;imii de band&#259; disponibil&#259;. Aceast&#259; op&#355;iune corespunde calit&#259;&#355;ii secven&#355;ei video. O valoare mic&#259; v-a avea ca rezultat imagine incert&#259; iar o valoare mare v-a produce o imagine mai clar&#259;. Aceast&#259; op&#355;iune nu controleaz&#259; frecven&#355;a cadrelor, de&#351;i calitatea secven&#355;elor video este influen&#355;at&#259; at&#226;t de aceast&#259; op&#355;iune c&#226;t &#351;i de frecven&#355;a cadrelor la care este produs&#259; secven&#355;a video.";
$zmOlangPromptWEB_H_VIDEO_MAXFPS = "Frecven&#355;a maxim&#259; a cadrelor pentru fluxurile video";
$zmOlangHelpWEB_H_VIDEO_MAXFPS = "La folosirea fluxurilor video factorul principal de control este rata bi&#355;ilor care determin&#259; cantitatea de date care poate fi transmis&#259;. Totu&#351;i o rata mic&#259; la frecven&#355;&#259; mare a cadrelor nu va avea rezultate calitative. Aceast&#259; op&#355;iune v&#259; permite s&#259; limita&#355;i frecven&#355;a maxim&#259; a cadrelor pentru a asigura calitatea imaginii. Un avantaj adi&#355;ional este c&#259; &#238;nregistrarea la frecven&#355;e mari poate consuma multe resurse f&#259;r&#259; s&#259; ofere rezultate calitative satisf&#259;catoare, fa&#355;&#259; de &#238;nregistrarea unde se menajeaz&#259; resursele. Aceast&#259; op&#355;iune este implementat&#259; ca surplus dincolo de reduc&#355;ia binar&#259;. Deci dac&#259; ave&#355;i un dispozitiv care captureaz&#259; la 15fps &#351;i seta&#355;i aceast&#259; op&#355;iune la 10fps atunci secven&#355;a video nu este produs&#259; la 10fps, ci la 7,5fps (15/2) deoarece frecven&#355;a finala a cadrelor trebuie s&#259; fie frecven&#355;a ini&#355;iala &#238;mp&#259;r&#355;it&#259; la un num&#259;r putere a num&#259;rului 2.";
$zmOlangPromptWEB_H_IMAGE_SCALING = "Scala miniaturilor &#238;n evenimente, bandwidth vs. cpu pentru rescalare";
$zmOlangHelpWEB_H_IMAGE_SCALING ="Valoare 1 v-a transmite la browser imaginea complet&#259; care va fi redimensionata &#238;n fereastr&#259;, valori mai mari vor mic&#351;ora imaginea &#238;nainte de a transmite o imagine miniatural&#259; la browser. Pentru la&#355;ime de band&#259; mare setare implicit&#259; 1 este de obicei cea mai rapid&#259; &#351;i nu produce imagini miniaturale externe.";
// End of High Banwidth tab

// Beginning of Medium Bandwidth tab
$zmOlangPromptWEB_M_REFRESH_MAIN = "C&#226;t de des (&#238;n secunde) se va actualiza fereastra principal&#259;";
$zmOlangHelpWEB_M_REFRESH_MAIN = "&#206;n fereastra principal&#259; sunt afi&#351;ate starea general&#259; &#351;i totalul evenimentelor pentru toate monitoarele. Aceast&#259; sarcin&#259; nu trebuie repetat&#259; frecvent; s-ar putea s&#259; afecteze performan&#355;a sistemului.";
$zmOlangPromptWEB_M_REFRESH_CYCLE = "C&#226;t de des (&#238;n secunde) se vor schimba imaginile &#238;n ciclul de monitorizare.";
$zmOlangHelpWEB_M_REFRESH_CYCLE = "Ciclul de monitorizare este metoda de schimbare continu&#259; a imaginilor monitoarelor. Aceast&#259; op&#355;iune determin&#259; c&#226;t de des va fi actulizat cu o nou&#259; imagine.";
$zmOlangPromptWEB_M_REFRESH_IMAGE = "C&#226;t de des (&#238;n secunde) sunt actulizate imaginile statice";
$zmOlangHelpWEB_M_REFRESH_IMAGE = "Imaginile 'live' ale unui monitor pot fi vizulizate &#238;n flux de imagini (video) sau imagini statice. Aceast&#259; op&#355;iune determin&#259; c&#226;t de des vor fi actualizate imaginile statice, nu are nici un efect dac&#259; este selectat&#259; metoda flux video (streaming).";
$zmOlangPromptWEB_M_REFRESH_STATUS = "C&#226;t de des va fi actualizat cadrul de stare";
$zmOlangHelpWEB_M_REFRESH_STATUS = "Fereastra monitorului este alc&#259;tuit&#259; din mai multe cadre. Cadrul din mijloc con&#355;ine starea monitorului &#351;i trebuie actualizat&#259; destul de frecvent pentru a indica valori reale. Aceast&#259; op&#355;iune determin&#259; frecven&#355;a respectiv&#259;.";
$zmOlangPromptWEB_M_REFRESH_EVENTS = "C&#226;t de des (&#238;n secunde) este actulizat&#259; lista evenimentelor din fereastra principal&#259;";
$zmOlangHelpWEB_M_REFRESH_EVENTS = "Fereastra monitorului este alc&#259;tuit&#259; din mai multe cadre. Cadrul inferior con&#355;ine o list&#259; a ultimelor evenimente pentru acces rapid. Aceast&#259; op&#355;iune determin&#259; c&#226;t de des este actulizat acest cadru.";
$zmOlangPromptWEB_M_DEFAULT_SCALE = "Care este scara implicit&#259; ce se aplica vizualiz&#259;rii 'live' sau a evenimentelor (%)";
$zmOlangHelpWEB_M_DEFAULT_SCALE = "&#206;n mod normal ZoneMinder va afi&#351;a fluxurile 'live' sau evenimentele &#238;n marime nativ&#259;. Dac&#259; ave&#355;i monitoare de dimensiuni mari pute&#355;i reduce aceast&#259; m&#259;rime, iar pentru monitoare de dimensiuni mici pute&#355;i redimensiona &#238;n sens pozitiv aceast&#259; m&#259;rime. Prin intermediul acestei op&#355;iuni pute&#355;i specifica care va fi factorul implicit de scar&#259;. Este exprimat &#238;n procente deci 100 va fi dimensiune normal&#259;, 200 dimensiune dubl&#259; etc.";
$zmOlangPromptWEB_M_DEFAULT_RATE = "Viteza de redare a evenimentelor (%)";
$zmOlangHelpWEB_M_DEFAULT_RATE = "&#206;n mod normal ZoneMinder va afi&#351;a fluxurile video la viteza lor nativ&#259;. Dac&#259; ave&#355;i evenimente de lung&#259; durat&#259; este mai convenabil&#259; redarea lor la o rat&#259; mai mare. Aceast&#259; op&#355;iune v&#259; permite sa specifica&#355;i rata de redare. Este exprimat&#259; &#238;n procente deci 100 este rata normal&#259;, 200 este vitez&#259; dubl&#259;, etc.";
$zmOlangPromptWEB_M_VIDEO_BITRATE = "Rata bi&#355;ilor (bit rate) la care este codat fluxul video";
$zmOlangHelpWEB_M_VIDEO_BITRATE = "La codarea secven&#355;elor video prin intermediul libr&#259;riei ffmpeg poate fi specificat&#259; o rat&#259; a bi&#355;ilor (bit rate) care corespunde, &#238;n linii mari, l&#259;&#355;imii de band&#259; disponibil&#259;. Aceast&#259; op&#355;iune corespunde calit&#259;&#355;ii secven&#355;ei video. O valoare mic&#259; v-a avea ca rezultat imagine incert&#259; iar o valoare mare v-a produce o imagine mai clar&#259;. Aceast&#259; op&#355;iune nu controleaz&#259; frecven&#355;a cadrelor, de&#351;i calitatea secven&#355;elor video este influen&#355;at&#259; at&#226;t de aceast&#259; op&#355;iune c&#226;t &#351;i de frecven&#355;a cadrelor la care este produs&#259; secven&#355;a video.";
$zmOlangPromptWEB_M_VIDEO_MAXFPS = "Frecven&#355;a maxim&#259; a cadrelor pentru fluxurile video";
$zmOlangHelpWEB_M_VIDEO_MAXFPS = "La folosirea fluxurilor video factorul principal de control este rata bi&#355;ilor care determin&#259; cantitatea de date care poate fi transmis&#259;. Totu&#351;i o rata mic&#259; la frecven&#355;&#259; mare a cadrelor nu va avea rezultate calitative. Aceast&#259; op&#355;iune v&#259; permite s&#259; limita&#355;i frecven&#355;a maxim&#259; a cadrelor pentru a asigura calitatea imaginii. Un avantaj adi&#355;ional este c&#259; &#238;nregistrarea la frecven&#355;e mari poate consuma multe resurse f&#259;r&#259; s&#259; ofere rezultate calitative satisf&#259;catoare, fa&#355;&#259; de &#238;nregistrarea unde se menajeaz&#259; resursele. Aceast&#259; op&#355;iune este implementat&#259; ca surplus dincolo de reduc&#355;ia binar&#259;. Deci dac&#259; ave&#355;i un dispozitiv care captureaz&#259; la 15fps &#351;i seta&#355;i aceast&#259; op&#355;iune la 10fps atunci secven&#355;a video nu este produs&#259; la 10fps, ci la 7,5fps (15/2) deoarece frecven&#355;a finala a cadrelor trebuie s&#259; fie frecven&#355;a ini&#355;iala &#238;mp&#259;r&#355;it&#259; la un num&#259;r putere a num&#259;rului 2.";
$zmOlangPromptWEB_M_IMAGE_SCALING = "Scala miniaturilor &#238;n evenimente, bandwidth vs. cpu pentru rescalare";
$zmOlangHelpWEB_M_IMAGE_SCALING = "Valoare 1 v-a transmite la browser imaginea complet&#259; care va fi redimensionata &#238;n fereastr&#259;, valori mai mari vor mic&#351;ora imaginea &#238;nainte de a transmite o imagine miniatural&#259; la browser. Pentru la&#355;ime de band&#259; medie setare implicit&#259; 4 este de obicei cea mai rapid&#259; dar e posibil ca &#351;i valoare 1 s&#259; fie acceptabil&#259;";
// End of Medium Bandwidth tab

// Beginning of Low Bandwidth tab
$zmOlangPromptWEB_L_REFRESH_MAIN = "C&#226;t de des (&#238;n secunde) se va actualiza fereastra principal&#259;";
$zmOlangHelpWEB_L_REFRESH_MAIN = "&#206;n fereastra principal&#259; sunt afi&#351;ate starea general&#259; &#351;i totalul evenimentelor pentru toate monitoarele. Aceast&#259; sarcin&#259; nu trebuie repetat&#259; frecvent; s-ar putea s&#259; afecteze performan&#355;a sistemului.";
$zmOlangPromptWEB_L_REFRESH_CYCLE = "C&#226;t de des (&#238;n secunde) se vor schimba imaginile &#238;n ciclul de monitorizare.";
$zmOlangHelpWEB_L_REFRESH_CYCLE = "Ciclul de monitorizare este metoda de schimbare continu&#259; a imaginilor monitoarelor. Aceast&#259; op&#355;iune determin&#259; c&#226;t de des va fi actulizat cu o nou&#259; imagine.";
$zmOlangPromptWEB_L_REFRESH_IMAGE = "C&#226;t de des (&#238;n secunde) sunt actulizate imaginile statice";
$zmOlangHelpWEB_L_REFRESH_IMAGE = "Imaginile 'live' ale unui monitor pot fi vizulizate &#238;n flux de imagini (video) sau imagini statice. Aceast&#259; op&#355;iune determin&#259; c&#226;t de des vor fi actualizate imaginile statice, nu are nici un efect dac&#259; este selectat&#259; metoda flux video (streaming).";
$zmOlangPromptWEB_L_REFRESH_STATUS = "C&#226;t de des va fi actualizat cadrul de stare";
$zmOlangHelpWEB_L_REFRESH_STATUS = "Fereastra monitorului este alc&#259;tuit&#259; din mai multe cadre. Cadrul din mijloc con&#355;ine starea monitorului &#351;i trebuie actualizat&#259; destul de frecvent pentru a indica valori reale. Aceast&#259; op&#355;iune determin&#259; frecven&#355;a respectiv&#259;.";
$zmOlangPromptWEB_L_REFRESH_EVENTS = "C&#226;t de des (&#238;n secunde) este actulizat&#259; lista evenimentelor din fereastra principal&#259;";
$zmOlangHelpWEB_L_REFRESH_EVENTS = "Fereastra monitorului este alc&#259;tuit&#259; din mai multe cadre. Cadrul inferior con&#355;ine o list&#259; a ultimelor evenimente pentru acces rapid. Aceast&#259; op&#355;iune determin&#259; c&#226;t de des este actulizat acest cadru.";
$zmOlangPromptWEB_L_DEFAULT_SCALE = "Care este scara implicit&#259; ce se aplica vizualiz&#259;rii 'live' sau a evenimentelor (%)";
$zmOlangHelpWEB_L_DEFAULT_SCALE = "&#206;n mod normal ZoneMinder va afi&#351;a fluxurile 'live' sau evenimentele &#238;n marime nativ&#259;. Dac&#259; ave&#355;i monitoare de dimensiuni mari pute&#355;i reduce aceast&#259; m&#259;rime, iar pentru monitoare de dimensiuni mici pute&#355;i redimensiona &#238;n sens pozitiv aceast&#259; m&#259;rime. Prin intermediul acestei op&#355;iuni pute&#355;i specifica care va fi factorul implicit de scar&#259;. Este exprimat &#238;n procente deci 100 va fi dimensiune normal&#259;, 200 dimensiune dubl&#259; etc.";
$zmOlangPromptWEB_L_DEFAULT_RATE = "Viteza de redare a evenimentelor (%)";
$zmOlangHelpWEB_L_DEFAULT_RATE = "&#206;n mod normal ZoneMinder va afi&#351;a fluxurile video la viteza lor nativ&#259;. Dac&#259; ave&#355;i evenimente de lung&#259; durat&#259; este mai convenabil&#259; redarea lor la o rat&#259; mai mare. Aceast&#259; op&#355;iune v&#259; permite sa specifica&#355;i rata de redare. Este exprimat&#259; &#238;n procente deci 100 este rata normal&#259;, 200 este vitez&#259; dubl&#259;, etc.";
$zmOlangPromptWEB_L_VIDEO_BITRATE = "Rata bi&#355;ilor (bit rate) la care este codat fluxul video";
$zmOlangHelpWEB_L_VIDEO_BITRATE = "La codarea secven&#355;elor video prin intermediul libr&#259;riei ffmpeg poate fi specificat&#259; o rat&#259; a bi&#355;ilor (bit rate) care corespunde, &#238;n linii mari, l&#259;&#355;imii de band&#259; disponibil&#259;. Aceast&#259; op&#355;iune corespunde calit&#259;&#355;ii secven&#355;ei video. O valoare mic&#259; v-a avea ca rezultat imagine incert&#259; iar o valoare mare v-a produce o imagine mai clar&#259;. Aceast&#259; op&#355;iune nu controleaz&#259; frecven&#355;a cadrelor, de&#351;i calitatea secven&#355;elor video este influen&#355;at&#259; at&#226;t de aceast&#259; op&#355;iune c&#226;t &#351;i de frecven&#355;a cadrelor la care este produs&#259; secven&#355;a video.";
$zmOlangPromptWEB_L_VIDEO_MAXFPS = "Frecven&#355;a maxim&#259; a cadrelor pentru fluxurile video";
$zmOlangHelpWEB_L_VIDEO_MAXFPS = "La folosirea fluxurilor video factorul principal de control este rata bi&#355;ilor care determin&#259; cantitatea de date care poate fi transmis&#259;. Totu&#351;i o rata mic&#259; la frecven&#355;&#259; mare a cadrelor nu va avea rezultate calitative. Aceast&#259; op&#355;iune v&#259; permite s&#259; limita&#355;i frecven&#355;a maxim&#259; a cadrelor pentru a asigura calitatea imaginii. Un avantaj adi&#355;ional este c&#259; &#238;nregistrarea la frecven&#355;e mari poate consuma multe resurse f&#259;r&#259; s&#259; ofere rezultate calitative satisf&#259;catoare, fa&#355;&#259; de &#238;nregistrarea unde se menajeaz&#259; resursele. Aceast&#259; op&#355;iune este implementat&#259; ca surplus dincolo de reduc&#355;ia binar&#259;. Deci dac&#259; ave&#355;i un dispozitiv care captureaz&#259; la 15fps &#351;i seta&#355;i aceast&#259; op&#355;iune la 10fps atunci secven&#355;a video nu este produs&#259; la 10fps, ci la 7,5fps (15/2) deoarece frecven&#355;a finala a cadrelor trebuie s&#259; fie frecven&#355;a ini&#355;iala &#238;mp&#259;r&#355;it&#259; la un num&#259;r putere a num&#259;rului 2.";
$zmOlangPromptWEB_L_IMAGE_SCALING = "Scala miniaturilor &#238;n evenimente, bandwidth vs. cpu pentru rescalare";
$zmOlangHelpWEB_L_IMAGE_SCALING = "Valoare 1 v-a transmite la browser imaginea complet&#259; care va fi redimensionata &#238;n fereastr&#259;, valori mai mari vor mic&#351;ora imaginea &#238;nainte de a transmite o imagine miniatural&#259; la browser. Pentru la&#355;ime de band&#259; redus&#259; setare implicit&#259; 4 este de obicei cea mai rapid&#259;.";
// End of Low Bandwidth tab

// Beginning of Phone Bandwidth tab
$zmOlangPromptWEB_P_REFRESH_MAIN = "C&#226;t de des (&#238;n secunde) se va actualiza fereastra principal&#259;";
$zmOlangHelpWEB_P_REFRESH_MAIN = "&#206;n fereastra principal&#259; sunt afi&#351;ate starea general&#259; &#351;i totalul evenimentelor pentru toate monitoarele. Aceast&#259; sarcin&#259; nu trebuie repetat&#259; frecvent; s-ar putea s&#259; afecteze performan&#355;a sistemului.";
$zmOlangPromptWEB_P_REFRESH_IMAGE = "C&#226;t de des (&#238;n secunde) se vor schimba imaginile &#238;n ciclul de monitorizare.";
$zmOlangHelpWEB_P_REFRESH_IMAGE = "Ciclul de monitorizare este metoda de schimbare continu&#259; a imaginilor monitoarelor. Aceast&#259; op&#355;iune determin&#259; c&#226;t de des va fi actulizat cu o nou&#259; imagine.";
// End of Phone Bandwidth tab
//
?>
