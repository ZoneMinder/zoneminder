<?php
//
// ZoneMinder main web interface file, $Date$, $Revision$
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

error_reporting(E_ALL);

$debug = false;
if ( $debug ) {
  // Use these for debugging, though not both at once!
  phpinfo(INFO_VARIABLES);
  //error_reporting( E_ALL );
}

// Use new style autoglobals where possible
if ( version_compare(phpversion(), '4.1.0', '<') ) {
  $_SESSION = &$HTTP_SESSION_VARS;
  $_SERVER = &$HTTP_SERVER_VARS;
}

// Useful debugging lines for mobile devices
if ( false ) {
  ob_start();
  phpinfo(INFO_VARIABLES);
  $fp = fopen('/tmp/env.html', 'w+');
  fwrite($fp, ob_get_contents());
  fclose($fp);
  ob_end_clean();
}

global $dateFormatter;
global $dateTimeFormatter;
global $timeFormatter;
$dateFormatter = new IntlDateFormatter(null, IntlDateFormatter::SHORT, IntlDateFormatter::NONE);
$dateTimeFormatter = new IntlDateFormatter(null, IntlDateFormatter::SHORT, IntlDateFormatter::LONG);
$timeFormatter = new IntlDateFormatter(null, IntlDateFormatter::NONE, IntlDateFormatter::LONG);
require_once('includes/config.php');
if (ZM_LOCALE_DEFAULT) {
  $dateFormatter = new IntlDateFormatter(ZM_LOCALE_DEFAULT, IntlDateFormatter::SHORT, IntlDateFormatter::NONE);
  $dateTimeFormatter = new IntlDateFormatter(ZM_LOCALE_DEFAULT, IntlDateFormatter::SHORT, IntlDateFormatter::LONG);
  $timeFormatter = new IntlDateFormatter(ZM_LOCALE_DEFAULT, IntlDateFormatter::NONE, IntlDateFormatter::LONG);
}
if (ZM_DATE_FORMAT_PATTERN) {
  $dateFormatter->setPatter(ZM_DATE_FORMAT_PATTERN);
}
if (ZM_DATETIME_FORMAT_PATTERN) {
  $dateTimeFormatter->setPattern(ZM_DATETIME_FORMAT_PATTERN);
}
if (ZM_TIME_FORMAT_PATTERN) {
  $timeFormatter->setPattern(ZM_TIME_FORMAT_PATTERN);
}
require_once('includes/session.php');
require_once('includes/logger.php');
require_once('includes/Server.php');

// Useful debugging lines for mobile devices
if ( 0 and ZM\Logger::fetch()->debugOn() ) {
  ob_start();
  phpinfo(INFO_VARIABLES);
  ZM\Debug(ob_get_contents());
  ob_end_clean();
}

global $Servers;
$Servers = ZM\Server::find();

if (
  (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')
  or
  (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) and ($_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'))
) {
  $protocol = 'https';
} else {
  $protocol = 'http';
}
define('ZM_BASE_PROTOCOL', $protocol);

// Absolute URL's are unnecessary and break compatibility with reverse proxies 
// define( "ZM_BASE_URL", $protocol.'://'.$_SERVER['HTTP_HOST'] );

// Use relative URL's instead
define('ZM_BASE_URL', '');

require_once('includes/functions.php');
if ( $_SERVER['REQUEST_METHOD'] == 'OPTIONS' ) {
  ZM\Debug('OPTIONS Method, only doing CORS');
  # Add Cross domain access headers
  CORSHeaders();
  return;
}

if ( isset($_GET['skin']) ) {
  $skin = $_GET['skin'];
} else if ( isset($_COOKIE['zmSkin']) ) {
  $skin = $_COOKIE['zmSkin'];
} else if ( defined('ZM_SKIN_DEFAULT') ) {
  $skin = ZM_SKIN_DEFAULT;
} else {
  $skin = 'classic';
}

if (!is_dir('skins/'.$skin) ) {
  $skins = array_map('basename', glob('skins/*', GLOB_ONLYDIR));

  if ( !in_array($skin, $skins) ) {
    ZM\Error("Invalid skin '$skin' setting to ".$skins[0]);
    $skin = $skins[0];
  }
}
global $css;
if ( isset($_GET['css']) ) {
  $css = $_GET['css'];
} else if ( isset($_COOKIE['zmCSS']) ) {
  $css = $_COOKIE['zmCSS'];
} else if ( defined('ZM_CSS_DEFAULT') ) {
  $css = ZM_CSS_DEFAULT;
} else {
  $css = 'classic';
}

if (!is_dir("skins/$skin/css/$css")) {
  $css_skins = array_map('basename', glob('skins/'.$skin.'/css/*', GLOB_ONLYDIR));
  if (count($css_skins)) {
    if (!in_array($css, $css_skins)) {
      ZM\Error("Invalid skin css '$css' setting to " . $css_skins[0]);
      $css = $css_skins[0];
    } else {
      $css = '';
    }
  } else {
    ZM\Error("No css options found at skins/$skin/css");
    $css = '';
  }
}

define('ZM_BASE_PATH', dirname($_SERVER['REQUEST_URI']));
define('ZM_SKIN_PATH', "skins/$skin");
define('ZM_SKIN_NAME', $skin);

$skinBase = array(); // To allow for inheritance of skins
if (!file_exists(ZM_SKIN_PATH))
  ZM\Fatal("Invalid skin '$skin'");
$skinBase[] = $skin;

zm_session_start();

if (
  !isset($_SESSION['skin']) ||
  isset($_REQUEST['skin']) ||
  !isset($_COOKIE['zmSkin']) ||
  ($_COOKIE['zmSkin'] != $skin)
) {
  $_SESSION['skin'] = $skin;
	zm_setcookie('zmSkin', $skin);
}

if (
  !isset($_SESSION['css']) ||
  isset($_REQUEST['css']) ||
  !isset($_COOKIE['zmCSS']) ||
  ($_COOKIE['zmCSS'] != $css)
) {
  $_SESSION['css'] = $css;
  zm_setcookie('zmCSS', $css);
}

# Running is global but only do the daemonCheck if it is actually needed
$running = null;

# Add Cross domain access headers
CORSHeaders();

// Check for valid content dirs
if ( !is_writable(ZM_DIR_EVENTS) ) {
  ZM\Warning("Cannot write to event folder ".ZM_DIR_EVENTS.". Check that it exists and is owned by the web account user.");
}

# Globals
$action = null;
$error_message = null;
$redirect = null;
$view = null;
$user = null;
if ( isset($_REQUEST['view']) )
  $view = detaintPath($_REQUEST['view']);


$request = null;
if ( isset($_REQUEST['request']) )
  $request = detaintPath($_REQUEST['request']);

require_once('includes/auth.php');

# Only one request can open the session file at a time, so let's close the session here to improve concurrency.
# Any file/page that sets session variables must re-open it.
session_write_close();

require_once('includes/Storage.php');
require_once('includes/Event.php');
require_once('includes/Group.php');
require_once('includes/Monitor.php');

// lang references $user[Language] so must come after auth
require_once('includes/lang.php');

foreach ( getSkinIncludes('skin.php') as $includeFile ) {
  require_once $includeFile;
}

if ( isset($_REQUEST['action']) )
  $action = detaintPath($_REQUEST['action']);

# The only variable we really need to set is action. The others are informal.
isset($view) || $view = NULL;
isset($request) || $request = NULL;
isset($action) || $action = NULL;

if ( (!$view and !$request) or ($view == 'console') ) {
  // Verify the system, php, and mysql timezones all match
  #if ( ZM_TIMEZONE )
  #date_default_timezone_set(ZM_TIMEZONE);
  check_timezone();
}

ZM\Debug("View: $view Request: $request Action: $action User: " . ( isset($user) ? $user['Username'] : 'none' ));
if (
  ZM_ENABLE_CSRF_MAGIC &&
  ( $action != 'login' ) &&
  ( $view != 'view_video' ) && // only video no html
  ( $view != 'image' ) && // view=image doesn't return html, just image data.
  ( $request != 'control' ) && 
  //( $view != 'frames' ) &&  // big html can overflow ob
  ( $view != 'archive' ) // returns data
  && ( (!isset($_SERVER['CONTENT_TYPE']) or ($_SERVER['CONTENT_TYPE'] != 'application/csp-report')) )
) {
  require_once('includes/csrf/csrf-magic.php');
  #ZM\Debug("Calling csrf_check with the following values: \$request = \"$request\", \$view = \"$view\", \$action = \"$action\"");
  csrf_check();
}

# Need to include actions because it does auth
if ( $action and !$request ) {
  if ( file_exists('includes/actions/'.$view.'.php') ) {
    ZM\Debug("Including includes/actions/$view.php");
    require_once('includes/actions/'.$view.'.php');
  } else {
    ZM\Warning("No includes/actions/$view.php for action $action");
  }
}

# If I put this here, it protects all views and popups, but it has to go after actions.php because actions.php does the actual logging in.
if ( ZM_OPT_USE_AUTH and (!isset($user)) and ($view != 'login') and ($view != 'none') ) {
  if ($request) {
    # requests only return json
    header('HTTP/1.1 401 Unauthorized');
    exit;
  }
  $view = 'none';
  $redirect = ZM_BASE_URL.$_SERVER['PHP_SELF'].'?view=login';
  zm_session_start();
  $_SESSION['postLoginQuery'] = $_SERVER['QUERY_STRING'];
  session_write_close();
} else if ( ZM_SHOW_PRIVACY && ($view != 'privacy') && ($view != 'options') && (!$request) && canEdit('System') ) {
  $view = 'none';
  $redirect = ZM_BASE_URL.$_SERVER['PHP_SELF'].'?view=privacy';
  $request = null;
}

if ( isset($_REQUEST['redirect']) )
  $redirect = '?view='.detaintPath($_REQUEST['redirect']);

if ( $redirect ) {
  ZM\Debug("Redirecting to $redirect");
  header('Location: '.$redirect);
  return;
}

if ( $request ) {
  foreach ( getSkinIncludes('ajax/'.$request.'.php', true, true) as $includeFile ) {
    if ( !file_exists($includeFile) )
      ZM\Fatal("Request '$request' does not exist");
    require_once $includeFile;
  }
  return;
}

# Add CSP Headers
$cspNonce = bin2hex(zm_random_bytes(16));
if ( $includeFiles = getSkinIncludes('views/'.$view.'.php', true, true) ) {
  ob_start();
  CSPHeaders($view, $cspNonce);
  foreach ( $includeFiles as $includeFile ) {
    if ( !file_exists($includeFile) )
      ZM\Fatal("View '$view' does not exist");
    require_once $includeFile;
  }
  // If the view overrides $view to 'error', and the user is not logged in, then the
  // issue is probably resolvable by logging in, so provide the opportunity to do so.
  // The login view should handle redirecting to the correct location afterward.
  if ( $view == 'error' && !isset($user) ) {
    $view = 'login';
    foreach ( getSkinIncludes('views/login.php', true, true) as $includeFile )
      require_once $includeFile;
  }
  while (ob_get_level() > 0) ob_end_flush();
}
// If the view is missing or the view still returned error with the user logged in,
// then it is not recoverable.
if ( !$includeFiles || $view == 'error' ) {
  foreach ( getSkinIncludes('views/error.php', true, true) as $includeFile )
    require_once $includeFile;
}
?>
