<?php
//
// ZoneMinder web language file
// Copyright (C) 2001-2008 Philip Coombes
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
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
//

function translate($name) {
  global $SLANG;
  // The isset is more performant
  if ( isset($SLANG[$name]) || array_key_exists($name, $SLANG) )
    return $SLANG[$name];
  else
    return $name;
}

function loadLanguage($prefix='') {
  global $user;

  if ( $prefix )
    $prefix = $prefix.'/';

  if ( isset($user['Language']) and $user['Language'] ) {
    $userLangFile = $prefix.'lang/'.$user['Language'].'.php';

    if ( file_exists($userLangFile) ) {
      return $userLangFile;
    } else {
      ZM\Warning("User language file $userLangFile does not exist.");
    }
  }

  $systemLangFile = $prefix.'lang/'.ZM_LANG_DEFAULT.'.php';
  if ( file_exists($systemLangFile) ) {
    return $systemLangFile;
  } else {
    ZM\Warning("System language file $systemLangFile does not exist.");
  }

  $fallbackLangFile = $prefix.'lang/en_gb.php';
  if ( file_exists($fallbackLangFile) ) {
    return $fallbackLangFile;
  } else {
    ZM\Error("Default language file $fallbackLangFile does not exist.");
  }
  return false;
}

if ( $langFile = loadLanguage() ) {
  require_once($langFile);
  require_once('lang/default.php');
  foreach ($DLANG as $key => $value) {
    if ( ! (isset($SLANG[$key]) || array_key_exists($key, $SLANG)) )
      $SLANG[$key] = $DLANG[$key];
  }
}

//
// Date and time formats fallback, if not set up by the language file already
//
defined('DATE_FMT_CONSOLE_LONG') or define('DATE_FMT_CONSOLE_LONG', 'D jS M, g:ia');		// This is the main console date/time, date() or strftime() format
defined('DATE_FMT_CONSOLE_SHORT') or define('DATE_FMT_CONSOLE_SHORT', '%H:%M');		// This is the xHTML console date/time, date() or strftime() format

defined('STRF_FMT_DATETIME') or define('STRF_FMT_DATETIME', '%c');	// Strftime locale aware format for dates with times
defined('STRF_FMT_DATE') or define('STRF_FMT_DATE', '%x');					// Strftime locale aware format for dates without times
defined('STRF_FMT_TIME') or define('STRF_FMT_TIME', '%X');					// Strftime locale aware format for times without dates

defined('STRF_FMT_DATETIME_SHORT') or define('STRF_FMT_DATETIME_SHORT', '%y/%m/%d %H:%M:%S');	// Strftime shorter format for dates with time, not locale aware
defined('STRF_FMT_DATETIME_SHORTER') or define('STRF_FMT_DATETIME_SHORTER','%m/%d %H:%M:%S');// Strftime shorter format for dates with time, not locale aware, used where space is tight

?>
