<?php
//
// ZoneMinder web Polish language file, $Date$, $Revision$
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
$zmSlang24BitColour          = 'Kolor (24 bity)';
$zmSlang8BitGrey             = 'Szary (8 bitów)';
$zmSlangActual               = 'Aktualny';
$zmSlangAddNewMonitor        = 'Dodaj nowy monitor';
$zmSlangAddNewUser           = 'Dodaj u¿ytkownika';
$zmSlangAddNewZone           = 'Dodaj now± strefê';
$zmSlangAlarm                = 'Alarm';
$zmSlangAlarmBrFrames        = 'Alarm<br/>ramek';
$zmSlangAlarmFrame           = 'Ramka alarmu';
$zmSlangAlarmLimits          = 'Ograniczenia alarmu';
$zmSlangAlarmPx              = 'Alarm Px';
$zmSlangAlert                = 'Alert';
$zmSlangAll                  = 'Wszystko';
$zmSlangApply                = 'Zastosuj';
$zmSlangApplyingStateChange  = 'Zastosuj zmienê stanu';
$zmSlangArchArchived         = 'Tylko zarchiwizowane';
$zmSlangArchive              = 'Archiwa';
$zmSlangArchUnarchived       = 'Tylko niezarchiwizowane';
$zmSlangAttrAlarmFrames      = 'Alarm Ramek';
$zmSlangAttrArchiveStatus    = 'Status archiwum';
$zmSlangAttrAvgScore         = '¦red. wynik';
$zmSlangAttrDate             = 'Data';
$zmSlangAttrDateTime         = 'Data/Czas';
$zmSlangAttrDuration         = 'Czas trwania';
$zmSlangAttrFrames           = 'Ramek';
$zmSlangAttrMaxScore         = 'Maks. wynik';
$zmSlangAttrMontage          = 'Monta¿';
$zmSlangAttrTime             = 'Czas';
$zmSlangAttrTotalScore       = 'Ca³kowity wynik';
$zmSlangAttrWeekday          = 'Dzieñ roboczy';
$zmSlangAutoArchiveEvents    = 'Automatycznie archiwizuj wszystkie pasuj±ce zdarzenia';
$zmSlangAutoDeleteEvents     = 'Automatycznie kasuj wszystkie pasuj±ce zdarzenia';
$zmSlangAutoEmailEvents      = 'Automatycznie wysy³aj emailem szczegó³y o pasuj±cych zdarzeniach';
$zmSlangAutoMessageEvents    = 'Automatycznie wysy³aj komunikat o pasuj±cych zdarzeniach';
$zmSlangAutoUploadEvents     = 'Automatycznie upload-uj wszystkie pasuj±ce zdarzenia';
$zmSlangAvgBrScore           = '¦red.<br/>wynik';
$zmSlangBandwidth            = 'przepustowo¶æ';
$zmSlangBlobPx               = 'Blob Px';
$zmSlangBlobs                = 'Bloby';
$zmSlangBlobSizes            = 'Rozmiary Blobów';
$zmSlangBrightness           = 'Jaskrawo¶æ';
$zmSlangBuffers              = 'Bufory';
$zmSlangCancel               = 'Anuluj';
$zmSlangCancelForcedAlarm    = 'Anuluj&nbsp;wymuszony&nbsp;alarm';
$zmSlangCaptureHeight        = 'Wysoko¶æ obrazu';
$zmSlangCapturePalette       = 'Paleta kolorów obrazu';
$zmSlangCaptureWidth         = 'Szeroko¶æ obrazu';
$zmSlangCheckAll             = 'Sprawd¼ wszystko';
$zmSlangChooseFilter         = 'Wybierz filtr';
$zmSlangClose                = 'Zamknij';
$zmSlangColour               = 'Nasycenie';
$zmSlangConfiguredFor        = 'Ustawiona';
$zmSlangConfirmPassword      = 'Potwierd¼ has³o';
$zmSlangConjAnd              = 'i';
$zmSlangConjOr               = 'lub';
$zmSlangConsole              = 'Konsola';
$zmSlangContactAdmin         = 'Skontaktuj siê z Twoim adminstratorem w sprawie szczegó³ów.';
$zmSlangContrast             = 'Kontrast';
$zmSlangCycleWatch           = 'Cykl podgl±du';
$zmSlangDay                  = 'Dzieñ';
$zmSlangDeleteAndNext        = 'Usuñ &amp; nastêpny';
$zmSlangDeleteAndPrev        = 'Usuñ &amp; poprzedni';
$zmSlangDelete               = 'Usuñ';
$zmSlangDeleteSavedFilter    = 'Usuñ zapisany filtr';
$zmSlangDescription          = 'Opis';
$zmSlangDeviceChannel        = 'Numer wej¶cia';
$zmSlangDeviceFormat         = 'System TV (0=PAL,1=NTSC itd)';
$zmSlangDeviceNumber         = 'Numer urz±dzenia (/dev/video?)';
$zmSlangDimensions           = 'Rozmiary';
$zmSlangDuration             = 'Czas trwania';
$zmSlangEdit                 = 'Edycja';
$zmSlangEmail                = 'Email';
$zmSlangEnabled              = 'Zezwolono';
$zmSlangEnterNewFilterName   = 'Wpisz now± nazwê filtra';
$zmSlangErrorBrackets        = 'B³±d, proszê sprawdziæ ilo¶æ nawiasów otwieraj±cych i zamykaj±cych';
$zmSlangError                = 'B³±d';
$zmSlangErrorValidValue      = 'B³±d, proszê sprawdziæ czy wszystkie warunki maj± poprawne warto¶ci';
$zmSlangEtc                  = 'itp';
$zmSlangEvent                = 'Zdarzenie';
$zmSlangEventFilter          = 'Filtr zdarzeñ';
$zmSlangEvents               = 'Zdarzenia';
$zmSlangExclude              = 'Wyklucz';
$zmSlangFeed                 = 'Dostarcz';
$zmSlangFilterPx             = 'Filtr Px';
$zmSlangFirst                = 'Pierwszy';
$zmSlangForceAlarm           = 'Wymu¶&nbsp;alarm';
$zmSlangFPS                  = 'fps';
$zmSlangFPSReportInterval    = 'Interwa³ raportu FPS';
$zmSlangFrame                = 'Ramka';
$zmSlangFrameId              = 'Nr ramki';
$zmSlangFrameRate            = 'Tempo ramek';
$zmSlangFrames               = 'Ramek';
$zmSlangFrameSkip            = 'Pomiñ ramkê';
$zmSlangFTP                  = 'FTP';
$zmSlangFunc                 = 'Funkcja';
$zmSlangFunction             = 'Funkcja';
$zmSlangGenerateVideo        = 'Generowanie Video';
$zmSlangGeneratingVideo      = 'Generujê Video';
$zmSlangGrey                 = 'Szary';
$zmSlangHighBW               = 'Wys.&nbsp;prz.';
$zmSlangHigh                 = 'wysoka';
$zmSlangHour                 = 'Godzina';
$zmSlangHue                  = 'Odcieñ';
$zmSlangId                   = 'Lp';
$zmSlangIdle                 = 'Bezczynny';
$zmSlangIgnore               = 'Ignoruj';
$zmSlangImageBufferSize      = 'Rozmiar bufora obrazu (ramek)';
$zmSlangImage                = 'Obraz';
$zmSlangInclude              = 'Do³±cz';
$zmSlangInverted             = 'Odwrócony';
$zmSlangLanguage             = 'Jêzyk';
$zmSlangLast                 = 'Ostatni';
$zmSlangLocal                = 'Lokalny';
$zmSlangLoggedInAs           = 'Zalogowany jako';
$zmSlangLoggingIn            = 'Logowanie';
$zmSlangLogin                = 'Login';
$zmSlangLogout               = 'Wyloguj';
$zmSlangLowBW                = 'Nis.&nbsp;prz.';
$zmSlangLow                  = 'niska';
$zmSlangMark                 = 'Zacznik';
$zmSlangMaxBrScore           = 'Maks.<br/>wynik';
$zmSlangMaximumFPS           = 'Maks. FPS';
$zmSlangMax                  = 'Maks';
$zmSlangMediumBW             = '¦red.&nbsp;prz.';
$zmSlangMedium               = '¶rednia';
$zmSlangMinAlarmGeMinBlob    = 'Minimalny rozmiar piksela alarmu musi byæ wiêkszy lub równy od najmniejszego piksela bloba';
$zmSlangMinAlarmGeMinFilter  = 'Minimalny rozmiar piksela alarmu musi byæ wiêkszy lub równy od najmniejszego piksela filtru';
$zmSlangMisc                 = 'Inne';
$zmSlangMonitorIds           = 'Numery&nbsp;monitorów';
$zmSlangMonitor              = 'Monitor';
$zmSlangMonitors             = 'Monitory';
$zmSlangMontage              = 'Monta¿';
$zmSlangMonth                = 'Miesi±c';
$zmSlangMustBeGe             = 'musi byæ wiêksze lub równe od';
$zmSlangMustBeLe             = 'musi byæ mniejsze lub równe od';
$zmSlangMustConfirmPassword  = 'Musisz potwierdziæ has³o';
$zmSlangMustSupplyPassword   = 'Musisz podaæ has³o';
$zmSlangMustSupplyUsername   = 'Musisz podaæ nazwê u¿ytkownika';
$zmSlangName                 = 'Nazwa';
$zmSlangNetwork              = 'Sieæ';
$zmSlangNew                  = 'Nowy';
$zmSlangNewPassword          = 'Nowe has³o';
$zmSlangNewState             = 'Nowy stan';
$zmSlangNewUser              = 'nowy';
$zmSlangNext                 = 'Nastêpny';
$zmSlangNoFramesRecorded     = 'Brak zapisanych ramek dla tego zdarzenia';
$zmSlangNoneAvailable        = 'Niedostêpne';
$zmSlangNone                 = 'Brak';
$zmSlangNo                   = 'Nie';
$zmSlangNormal               = 'Normalny';
$zmSlangNoSavedFilters       = 'BrakZapisanychFiltrów';
$zmSlangNoStatisticsRecorded = 'Brak zapisanych satystyk dla tego zdarzenia/ramki';
$zmSlangOpEq                 = 'równy';
$zmSlangOpGtEq               = 'wiêksze lub równe od';
$zmSlangOpGt                 = 'wiêksze od';
$zmSlangOpLtEq               = 'mniejsze lub równe od';
$zmSlangOpLt                 = 'mniejsze od';
$zmSlangOpNe                 = 'ró¿ne od';
$zmSlangOptionHelp           = 'OpcjePomoc';
$zmSlangOptionRestartWarning = 'Te zmiany mog± nie przynieæ natychmiastowego efektu\ndopóki system pracuje. Kiedy skoñczysz\nrobiæ zmiany proszê konicznie\nzrestartowaæ ZoneMinder.';
$zmSlangOptions              = 'Opcje';
$zmSlangOrEnterNewName       = 'lub wpisz now± nazwê';
$zmSlangOrientation          = 'Orientacja';
$zmSlangOverwriteExisting    = 'Nadpisz istniej±ce';
$zmSlangPaged                = 'Stronicowane';
$zmSlangParameter            = 'Parametr';
$zmSlangPassword             = 'Has³o';
$zmSlangPasswordsDifferent   = 'Has³a: nowe i potwierdzone s± ró¿ne!';
$zmSlangPaths                = '¦cie¿ki';
$zmSlangPhoneBW              = 'Tel.&nbsp;prz.';
$zmSlangPixels               = 'pikseli';
$zmSlangPleaseWait           = 'Proszê czekaæ';
$zmSlangPostEventImageBuffer = 'Bufor obrazów po zdarzeniu';
$zmSlangPreEventImageBuffer  = 'Bufor obrazów przed zdarzeniem';
$zmSlangPrev                 = 'Wstecz';
$zmSlangRate                 = 'Tempo';
$zmSlangReal                 = 'Rzeczywiste';
$zmSlangRecord               = 'Zapis';
$zmSlangRefImageBlendPct     = 'Mix z obrazem odniesienia';
$zmSlangRefresh              = 'Od¶wie¿';
$zmSlangRemoteHostName       = 'Nazwa zdalnego hosta';
$zmSlangRemoteHostPath       = 'Scie¿ka zdalnego hosta';
$zmSlangRemoteHostPort       = 'Port zdalnego hosta';
$zmSlangRemoteImageColours   = 'Kolory zdalnego obrazu';
$zmSlangRemote               = 'Zdalny';
$zmSlangRename               = 'Zmieñ nazwê';
$zmSlangReplay               = 'Powtórka';
$zmSlangResetEventCounts     = 'Kasuj licznik zdarzeñ';
$zmSlangRestarting           = 'Restartujê';
$zmSlangRestart              = 'Restart';
$zmSlangRestrictedCameraIds  = 'Numery kamer';
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
$zmSlangServerLoad           = 'Obci±¿enie serwera';
$zmSlangSetLearnPrefs        = 'Ustaw preferencje nauki'; // This can be ignored for now
$zmSlangSetNewBandwidth      = 'Ustaw now± przepustowo¶æ';
$zmSlangSettings             = 'Ustawienia';
$zmSlangShowFilterWindow     = 'Poka¿OknoFiltru';
$zmSlangSource               = '¬ród³o';
$zmSlangSourceType           = 'Typ ¼ród³a';
$zmSlangStart                = 'Start';
$zmSlangState                = 'Stan';
$zmSlangStats                = 'Statystyki';
$zmSlangStatus               = 'Status';
$zmSlangStills               = 'Nieruchome';
$zmSlangStopped              = 'Zatrzymany';
$zmSlangStop                 = 'Stop';
$zmSlangStream               = 'Ruchomy';
$zmSlangSystem               = 'System';
$zmSlangTimeDelta            = 'Ró¿nica czasu';
$zmSlangTimestampLabelFormat = 'Format etykiety czasu';
$zmSlangTimestampLabelX      = 'Etykieta czasu X';
$zmSlangTimestampLabelY      = 'Etykieta czasu Y';
$zmSlangTimestamp            = 'Czas';
$zmSlangTimeStamp            = 'Pieczêæ czasu';
$zmSlangTime                 = 'Czas';
$zmSlangTools                = 'Narzêdzia';
$zmSlangTotalBrScore         = 'Ca³kowity<br/>wynik';
$zmSlangTriggers             = 'Wyzwalacze';
$zmSlangType                 = 'Typ';
$zmSlangUnarchive            = 'Niezarchiwizowane';
$zmSlangUnits                = 'Jednostki';
$zmSlangUnknown              = 'Nieznany';
$zmSlangUseFilterExprsPost   = '&nbsp;wyra¿enie&nbsp;filtru'; // This is used at the end of the phrase 'use N filter expressions'
$zmSlangUseFilterExprsPre    = 'U¿yj&nbsp;'; // This is used at the beginning of the phrase 'use N filter expressions'
$zmSlangUseFilter            = 'U¿yj filtru';
$zmSlangUsername             = 'Nazwa u¿ytkownika';
$zmSlangUsers                = 'U¿ytkownicy';
$zmSlangUser                 = 'U¿ytkownik';
$zmSlangValue                = 'Warto¶æ';
$zmSlangVideoGenFailed       = 'Generowanie Video nie powiod³o siê!';
$zmSlangVideoGenParms        = 'Parametery generowania Video';
$zmSlangVideoSize            = 'Rozmiar Video';
$zmSlangVideo                = 'Video';
$zmSlangViewAll              = 'Zobacz wszystko';
$zmSlangViewPaged            = 'Zobacz stronami';
$zmSlangView                 = 'Podgl±d';
$zmSlangWarmupFrames         = 'Ciep³e ramki';
$zmSlangWatch                = 'podgl±d';
$zmSlangWeb                  = 'Web';
$zmSlangWeek                 = 'Tydzieñ';
$zmSlangX10ActivationString  = 'X10 String aktywuj±cy';
$zmSlangX10InputAlarmString  = 'X10 String wej¶cia alarmu';
$zmSlangX10OutputAlarmString = 'X10 String wyj¶cia alarmu';
$zmSlangX10                  = 'X10';
$zmSlangYes                  = 'Tak';
$zmSlangYouNoPerms           = 'Nie masz uprawnieñ na dostêp do tego zasobu.';
$zmSlangZoneAlarmColour      = 'Kolor alarmu (RGB)';
$zmSlangZoneAlarmThreshold   = 'Próg alarmu (0>=?<=255)';
$zmSlangZoneFilterHeight     = 'Wysoko¶æ filtru (piksele)';
$zmSlangZoneFilterWidth      = 'Szeroko¶æ filtru (piksele)';
$zmSlangZoneMaxAlarmedArea   = 'Maksymalny obszar alamowany';
$zmSlangZoneMaxBlobArea      = 'Maksymalny obszar Bloba';
$zmSlangZoneMaxBlobs         = 'Maksymalne Bloby';
$zmSlangZoneMaxFilteredArea  = 'Maksymalny obszar filtrowany';
$zmSlangZoneMaxX             = 'Maksimum X (prawo)';
$zmSlangZoneMaxY             = 'Maksimum Y (dó³)';
$zmSlangZoneMinAlarmedArea   = 'Minimalny obszar alamowany';
$zmSlangZoneMinBlobArea      = 'Minimalny obszar Bloba';
$zmSlangZoneMinBlobs         = 'Minimalne Bloby';
$zmSlangZoneMinFilteredArea  = 'Minimalny obszar filtrowany';
$zmSlangZoneMinX             = 'Minimum X (lewo)';
$zmSlangZoneMinY             = 'Minimum Y (góra)';
$zmSlangZones                = 'Strefy';
$zmSlangZone                 = 'Strefa';

// Complex replacements with formatting and/or placements, must be passed through sprintf
$zmClangCurrentLogin         = 'Aktualny login \'%1$s\'';
$zmClangEventCount           = '%1$s %2$s';
$zmClangLastEvents           = 'Ostatnie %1$s %2$s';
$zmClangMonitorCount         = '%1$s %2$s';
$zmClangMonitorFunction      = 'Monitor %1$s Funkcja';

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
$zmVlangEvent                = array( 0=>'Zdarzeñ', 1=>'Zdarzenie', 2=>'Zdarzenia' );
$zmVlangMonitor              = array( 0=>'Monitorów', 1=>'Monitor', 2=>'Monitory' );

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
