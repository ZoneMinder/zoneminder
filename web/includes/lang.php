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
  if (( isset($SLANG[$name]) || array_key_exists($name, $SLANG) )) {
    return $SLANG[$name];
  } else {
    $lcfirstName = mb_lcfirst($name);
    if ( $lcfirstName !== $name and ( isset($SLANG[$lcfirstName]) || array_key_exists($lcfirstName, $SLANG) ) ) {
      # We found a lowercase word, but since we didn't find anything in the previous step, the final word must be uppercase.
      return mb_ucfirst($SLANG[$lcfirstName]);
    } else {
      $ucfirstName = mb_ucfirst($name);
      if ( $ucfirstName !== $name and ( isset($SLANG[$ucfirstName]) || array_key_exists($ucfirstName, $SLANG) ) ) {
        # We found a word in uppercase, but since we didn't find anything in the previous steps, the final word must be in lowercase.
        return mb_lcfirst($SLANG[$ucfirstName]);
      } else {
        return $name;
      }
    }
  }
}

function loadLanguage($prefix='') {
  global $user;

  if ($prefix)
    $prefix = $prefix.'/';

  if ($user and $user->Language()) {
    # Languages can only have letters, numbers and underscore
    $userLangFile = $prefix.'lang/'.preg_replace('/[^[:alnum:]_]+/', '', $user->Language()).'.php';

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
  if (false === strpos($langFile, 'en_gb.php')) {
    $fallbackLangFile = 'lang/en_gb.php';
    if ( file_exists($fallbackLangFile) ) {
      $SLANG_USER = (isset($SLANG) && is_array($SLANG)) ? $SLANG : array();
      $VLANG_USER = (isset($VLANG) && is_array($VLANG)) ? $VLANG : array();
      $OLANG_USER = (isset($OLANG) && is_array($OLANG)) ? $OLANG : array();
      $CLANG_USER = (isset($CLANG) && is_array($CLANG)) ? $CLANG : array();
      require_once($fallbackLangFile);
      $SLANG = (isset($SLANG) && is_array($SLANG)) ? $SLANG : array();
      $VLANG = (isset($VLANG) && is_array($VLANG)) ? $VLANG : array();
      $OLANG = (isset($OLANG) && is_array($OLANG)) ? $OLANG : array();
      $CLANG = (isset($CLANG) && is_array($CLANG)) ? $CLANG : array();
      $SLANG_ = array_replace_recursive($SLANG, $SLANG_USER);
      $VLANG_ = array_replace_recursive($VLANG, $VLANG_USER);
      $OLANG_ = array_replace_recursive($OLANG, $OLANG_USER);
      $CLANG_ = array_replace_recursive($CLANG, $CLANG_USER);
      $SLANG = $SLANG_;
      $VLANG = $VLANG_;
      $OLANG = $OLANG_;
      $CLANG = $CLANG_;
      unset($SLANG_USER, $VLANG_USER, $OLANG_USER, $CLANG_USER, $SLANG_, $VLANG_, $OLANG_, $CLANG_);
    }
  }
  require_once('lang/default.php');
  foreach ($DLANG as $key => $value) {
    if ( ! (isset($SLANG[$key]) || array_key_exists($key, $SLANG)) )
      $SLANG[$key] = $DLANG[$key];
  }
}
?>
