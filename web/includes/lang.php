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

  if ($prefix)
    $prefix = $prefix.'/';

  if (isset($user['Language']) and $user['Language']) {
    # Languages can only have letters, numbers and underscore
    $userLangFile = $prefix.'lang/'.preg_replace('/[^[:alnum:]_]+/', '', $user['Language']).'.php';

    if (file_exists($userLangFile)) {
      return $userLangFile;
    } else {
      ZM\Warning("User language file $userLangFile does not exist.");
    }
  }

  $systemLangFile = $prefix.'lang/'.preg_replace('/[^[:alnum:]_]+/', '', ZM_LANG_DEFAULT).'.php';
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
?>
