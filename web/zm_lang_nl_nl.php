<?php
//
// ZoneMinder web Dutch language file, $Date$, $Revision$
// Copyright (C) 2003  Philip Coombes
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
$zmSlangActual               = 'Aktueel';
$zmSlangAddNewMonitor        = 'Voeg een nieuwe monitor toe';
$zmSlangAddNewUser           = 'Voeg een nieuwe gebruiker toe';
$zmSlangAddNewZone           = 'Voeg een nieuwe zone toe';
$zmSlangAlarm                = 'Alarm';
$zmSlangAlarmBrFrames        = 'Alarm<br/>Frames';
$zmSlangAlarmFrame           = 'Alarm Frame';
$zmSlangAlarmLimits          = 'Alarm Limieten';
$zmSlangAlarmPx              = 'Alarm Px';
$zmSlangAlert                = 'Waarschuwing';
$zmSlangAll                  = 'Alle';
$zmSlangApply                = 'Voer uit';
$zmSlangApplyingStateChange  = 'Status verandering aan het uitvoeren';
$zmSlangArchArchived         = 'Alleen gearchiveerd';
$zmSlangArchive              = 'Archief';
$zmSlangArchUnarchived       = 'Alleen ongearchiveerd';
$zmSlangAttrAlarmFrames      = 'Alarm frames';
$zmSlangAttrArchiveStatus    = 'Archief status';
$zmSlangAttrAvgScore         = 'Gem. score';
$zmSlangAttrDate             = 'Datum';
$zmSlangAttrDateTime         = 'Datum/tijd';
$zmSlangAttrDuration         = 'Duur';
$zmSlangAttrFrames           = 'Frames';
$zmSlangAttrMaxScore         = 'Max. Score';
$zmSlangAttrMontage          = 'Montage';
$zmSlangAttrTime             = 'Tiid';
$zmSlangAttrTotalScore       = 'Totale Score';
$zmSlangAttrWeekday          = 'Weekdag';
$zmSlangAutoArchiveEvents    = 'archiveer automatisch alle overeenkomende gebeurtenissen';
$zmSlangAutoDeleteEvents     = 'verwijder automatisch alle overeenkomende gebeurtenissen';
$zmSlangAutoEmailEvents      = 'Mail automatisch details van alle overeenkomende gebeurtenissen';
$zmSlangAutoMessageEvents    = 'Bericht automatisch alle details van alle overeenkomende gebeurtenissen';
$zmSlangAutoUploadEvents     = 'Upload automatisch alle overeenkomende gebeurtenissen';
$zmSlangAvgBrScore           = 'Gem.<br/>score';
$zmSlangBandwidth            = 'Bandbreedte';
$zmSlangBlobPx               = 'Blob px';
$zmSlangBlobs                = 'Blobs';
$zmSlangBlobSizes            = 'Blob grootte';
$zmSlangBrightness           = 'Helderheid';
$zmSlangBuffers              = 'Buffers';
$zmSlangCancel               = 'Cancel';
$zmSlangCancelForcedAlarm    = 'Cancel&nbsp;geforceerd&nbsp;alarm';
$zmSlangCaptureHeight        = 'Capture hoogte';
$zmSlangCapturePalette       = 'Capture pallet';
$zmSlangCaptureWidth         = 'Capture breedte';
$zmSlangCheckAll             = 'Controleer alles';
$zmSlangChooseFilter         = 'Kies filter';
$zmSlangClose                = 'Sluit';
$zmSlangColour               = 'Kleur';
$zmSlangConfiguredFor        = 'Geconfigureerd voor';
$zmSlangConfirmPassword      = 'Bevestig wachtwoord';
$zmSlangConjAnd              = 'en';
$zmSlangConjOr               = 'of';
$zmSlangConsole              = 'Console';
$zmSlangContactAdmin         = 'Neem A.U.B. contact op met je beheerder voor details.';
$zmSlangContrast             = 'Contrast';
$zmSlangCycleWatch           = 'Observeer cyclus';
$zmSlangDay                  = 'Dag';
$zmSlangDeleteAndNext        = 'verwijder &amp; volgende';
$zmSlangDeleteAndPrev        = 'verwijder &amp; vorige';
$zmSlangDelete               = 'verwijder';
$zmSlangDeleteSavedFilter    = 'verwijder opgeslagen filter';
$zmSlangDescription          = 'Omschrijving';
$zmSlangDeviceChannel        = 'Apparaat kanaal';
$zmSlangDeviceFormat         = 'Apparaat formaat (0=PAL,1=NTSC etc)';
$zmSlangDeviceNumber         = 'apparaat nummer (/dev/video?)';
$zmSlangDimensions           = 'Afmetingen';
$zmSlangDuration             = 'Duur';
$zmSlangEdit                 = 'Bewerk';
$zmSlangEmail                = 'Email';
$zmSlangEnabled              = 'Uitgeschakeld';
$zmSlangEnterNewFilterName   = 'Voer nieuwe filter naam in';
$zmSlangErrorBrackets        = 'Error, controleer of je even veel openings als afsluiting brackets hebt gebruikt';
$zmSlangError                = 'Error';
$zmSlangErrorValidValue      = 'Error, Controleer of alle termen een geldige waarde hebben';
$zmSlangEtc                  = 'etc';
$zmSlangEvent                = 'Gebeurtenis';
$zmSlangEventFilter          = 'Gebeurtenis filter';
$zmSlangEvents               = 'Gebeurtenissen';
$zmSlangExclude              = 'Sluit uit';
$zmSlangFeed                 = 'toevoer';
$zmSlangFilterPx             = 'Filter px';
$zmSlangFirst                = 'Eerste';
$zmSlangForceAlarm           = 'Forceeer&nbsp;alarm';
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
$zmSlangGenerateVideo        = 'Genereer Video';
$zmSlangGeneratingVideo      = 'Genereren Video';
$zmSlangGrey                 = 'Grijs';
$zmSlangHighBW               = 'Hoog&nbsp;B/W';
$zmSlangHigh                 = 'Hoog';
$zmSlangHour                 = 'Uur';
$zmSlangHue                  = 'Hue';
$zmSlangId                   = 'Id';
$zmSlangIdle                 = 'Ongebruikt';
$zmSlangIgnore               = 'Negeer';
$zmSlangImageBufferSize      = 'Image buffer grootte (frames)';
$zmSlangImage                = 'Image';
$zmSlangInclude              = 'voeg in';
$zmSlangInverted             = 'omgedraaid';
$zmSlangLanguage             = 'Taal';
$zmSlangLast                 = 'Laatste';
$zmSlangLocal                = 'Lokaal';
$zmSlangLoggedInAs           = 'Ingelogd als';
$zmSlangLoggingIn            = 'In loggen';
$zmSlangLogin                = 'Login';
$zmSlangLogout               = 'Logout';
$zmSlangLowBW                = 'Laag&nbsp;B/W';
$zmSlangLow                  = 'Laag';
$zmSlangMark                 = 'Markeer';
$zmSlangMaxBrScore           = 'Max.<br/>score';
$zmSlangMaximumFPS           = 'Maximum FPS';
$zmSlangMax                  = 'Max';
$zmSlangMediumBW             = 'Medium&nbsp;B/W';
$zmSlangMedium               = 'Medium';
$zmSlangMinAlarmGeMinBlob    = 'Minimum alarm pixels moet groter zijn of gelijk aan het minimum aantal blob pixels';
$zmSlangMinAlarmGeMinFilter  = 'Minimum alarm pixels moet groter zijn of gelijk aan het minimum aantal filter pixels';
$zmSlangMisc                 = 'Misc';
$zmSlangMonitorIds           = 'Monitor&nbsp;Ids';
$zmSlangMonitor              = 'Monitor';
$zmSlangMonitors             = 'Monitoren';
$zmSlangMontage              = 'Montage';
$zmSlangMonth                = 'Maand';
$zmSlangMustBeGe             = 'Moet groter zijn of gelijk aan';
$zmSlangMustBeLe             = 'Moet kleiner zijn of gelijk aan';
$zmSlangMustConfirmPassword  = 'Je moet je wachtwoord bevestigen';
$zmSlangMustSupplyPassword   = 'Je moet een wachtwoord geven';
$zmSlangMustSupplyUsername   = 'Je moet een gebruikersnaam geven';
$zmSlangName                 = 'Naam';
$zmSlangNetwork              = 'Netwerk';
$zmSlangNew                  = 'Nieuw';
$zmSlangNewPassword          = 'Nieuw Wachtwoord';
$zmSlangNewState             = 'Nieuwe Status';
$zmSlangNewUser              = 'Nieuwe gebruiker';
$zmSlangNext                 = 'Volgende';
$zmSlangNoFramesRecorded     = 'Er zijn geen frames opgenomen voor deze gebeurtenis';
$zmSlangNoneAvailable        = 'geen beschikbaar';
$zmSlangNone                 = 'Geen';
$zmSlangNo                   = 'Nee';
$zmSlangNormal               = 'Normaal';
$zmSlangNoSavedFilters       = 'GeenOpgeslagenFilters';
$zmSlangNoStatisticsRecorded = 'er zijn geen statistieken opgenomen voor dit event/frame';
$zmSlangOpEq                 = 'gelijk aan';
$zmSlangOpGtEq               = 'groter dan of gelijk aan';
$zmSlangOpGt                 = 'groter dan';
$zmSlangOpLtEq               = 'kleiner dan of gelijk aan';
$zmSlangOpLt                 = 'kleiner dan';
$zmSlangOpNe                 = 'niet gelijk aan';
$zmSlangOptionHelp           = 'OptieHelp';
$zmSlangOptionRestartWarning = 'Deze veranderingen passen niet aan\nals het systeem loopt. Als je\nKlaar bent met veranderen vergeet dan niet dat\nje ZoneMinder herstart.';
$zmSlangOptions              = 'Opties';
$zmSlangOrEnterNewName       = 'of voer een nieuwe naam in';
$zmSlangOrientation          = 'Orientatie';
$zmSlangOverwriteExisting    = 'Overschrijf bestaande';
$zmSlangPaged                = 'Paged';
$zmSlangParameter            = 'Parameter';
$zmSlangPassword             = 'Wachtwoord';
$zmSlangPasswordsDifferent   = 'Het nieuwe en bevestigde wachtwoord zijn verschillend';
$zmSlangPaths                = 'Paden';
$zmSlangPhoneBW              = 'Telefoon&nbsp;B/W';
$zmSlangPixels               = 'pixels';
$zmSlangPleaseWait           = 'wacht A.U.B.';
$zmSlangPostEventImageBuffer = 'Post gebeurtenis Image Buffer';
$zmSlangPreEventImageBuffer  = 'Pre gebeurtenis Image Buffer<';
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
$zmSlangRestarting           = 'herstarten';
$zmSlangRestart              = 'herstart';
$zmSlangRestrictedCameraIds  = 'Verboden Camera Ids';
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
$zmSlangServerLoad           = 'Server belasting';
$zmSlangSetLearnPrefs        = 'Set Learn Prefs'; // This can be ignored for now
$zmSlangSetNewBandwidth      = 'Set Nieuwe Bandbreedte';
$zmSlangSettings             = 'Instellingen';
$zmSlangShowFilterWindow     = 'ToonFilterWindow';
$zmSlangSource               = 'Bron';
$zmSlangSourceType           = 'Bron Type';
$zmSlangStart                = 'Start';
$zmSlangState                = 'Status';
$zmSlangStats                = 'Stats';
$zmSlangStatus               = 'Status';
$zmSlangStills               = 'Plaatjes';
$zmSlangStopped              = 'gestopt';
$zmSlangStop                 = 'Stop';
$zmSlangStream               = 'Stroom';
$zmSlangSystem               = 'Systeem';
$zmSlangTimeDelta            = 'Tijd Delta';
$zmSlangTimestampLabelFormat = 'Tiidstempel Label Format';
$zmSlangTimestampLabelX      = 'Tiidstempel Label X';
$zmSlangTimestampLabelY      = 'Tiidstempel Label Y';
$zmSlangTimestamp            = 'Tiidstempel';
$zmSlangTimeStamp            = 'Tiidstempel';
$zmSlangTime                 = 'Tijd';
$zmSlangTools                = 'Gereedschappen';
$zmSlangTotalBrScore         = 'Totaal<br/>Score';
$zmSlangTriggers             = 'Triggers';
$zmSlangType                 = 'Type';
$zmSlangUnarchive            = 'Dearchiveer';
$zmSlangUnits                = 'Eenheden';
$zmSlangUnknown              = 'Onbekend';
$zmSlangUseFilterExprsPost   = '&nbsp;filter&nbsp;expressies'; // This is used at the end of the phrase 'use N filter expressions'
$zmSlangUseFilterExprsPre    = 'Gebruik&nbsp;'; // This is used at the beginning of the phrase 'use N filter expressions'
$zmSlangUseFilter            = 'Gebruik Filter';
$zmSlangUsername             = 'Gebruikersnaam';
$zmSlangUsers                = 'Gebruikers';
$zmSlangUser                 = 'Gebruiker';
$zmSlangValue                = 'Waarde';
$zmSlangVideoGenFailed       = 'Video Generatie mislukt!';
$zmSlangVideoGenParms        = 'Video Generatie Parameters';
$zmSlangVideoSize            = 'Video grootte';
$zmSlangVideo                = 'Video';
$zmSlangViewAll              = 'Bekijk Alles';
$zmSlangViewPaged            = 'Bekijk Paged';
$zmSlangView                 = 'Bekijk';
$zmSlangWarmupFrames         = 'Warmup Frames';
$zmSlangWatch                = 'Observeer';
$zmSlangWeb                  = 'Web';
$zmSlangWeek                 = 'Week';
$zmSlangX10ActivationString  = 'X10 Activatie String';
$zmSlangX10InputAlarmString  = 'X10 Input Alarm String';
$zmSlangX10OutputAlarmString = 'X10 Output Alarm String';
$zmSlangX10                  = 'X10';
$zmSlangYes                  = 'Ja';
$zmSlangYouNoPerms           = 'Je hebt niet de rechten om toegang te krijgen tot deze bronnen.';
$zmSlangZoneAlarmColour      = 'Alarm Kleur (RGB)';
$zmSlangZoneAlarmThreshold   = 'Alarm Drempel (0>=?<=255)';
$zmSlangZoneFilterHeight     = 'Filter Hoogte (pixels)';
$zmSlangZoneFilterWidth      = 'Filter Breedte (pixels)';
$zmSlangZoneMaxAlarmedArea   = 'Maximum Gealarmeerd gebied';
$zmSlangZoneMaxBlobArea      = 'Maximum Blob gebied';
$zmSlangZoneMaxBlobs         = 'Maximum Blobs';
$zmSlangZoneMaxFilteredArea  = 'Maximum gefilterd gebied';
$zmSlangZoneMaxX             = 'Maximum X (rechts)';
$zmSlangZoneMaxY             = 'Maximum Y (onder)';
$zmSlangZoneMinAlarmedArea   = 'Minimum Gealarmeerd gebied';
$zmSlangZoneMinBlobArea      = 'Minimum Blob gebied';
$zmSlangZoneMinBlobs         = 'Minimum Blobs';
$zmSlangZoneMinFilteredArea  = 'Minimum gefilterd gebied';
$zmSlangZoneMinX             = 'Minimum X (links)';
$zmSlangZoneMinY             = 'Minimum Y (boven)';
$zmSlangZones                = 'Zones';
$zmSlangZone                 = 'Zone';

// Complex replacements with formatting and/or placements, must be passed through sprintf
$zmClangCurrentLogin         = 'huidige login is \'%1$s\'';
$zmClangEventCount           = '%1$s %2$s'; // Als voorbeeld '37 gebeurtenissen' (from Vlang below)
$zmClangLastEvents           = 'Last %1$s %2$s'; // Als voorbeeld 'Laatste 37 gebeurtenissen' (from Vlang below)
$zmClangMonitorCount         = '%1$s %2$s'; // Als voorbeeld '4 Monitoren' (from Vlang below)
$zmClangMonitorFunction      = 'Monitor %1$s Functie';

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
