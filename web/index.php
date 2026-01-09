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

require_once('includes/config.php');
require_once('includes/logger.php'); // already included in config

// Useful debugging lines for mobile devices
if ( 0 and ZM\Logger::fetch()->debugOn() ) {
  ob_start();
  phpinfo(INFO_VARIABLES);
  ZM\Debug(ob_get_contents());
  ob_end_clean();
}
ZM\Debug(print_r($_REQUEST, true));

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

require_once('includes/functions.php');
if ( $_SERVER['REQUEST_METHOD'] == 'OPTIONS' ) {
  ZM\Debug('OPTIONS Method, only doing CORS');
  # Add Cross domain access headers
  CORSHeaders();
  return;
}

require_once('includes/session.php');
zm_session_start();

if ( defined('ZM_FORCE_SKIN_DEFAULT') ) {
  $skin = ZM_FORCE_SKIN_DEFAULT;
} else if ( isset($_GET['skin']) ) {
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
  if (!in_array($skin, $skins)) {
    ZM\Error("Invalid skin '$skin' setting to ".$skins[0]);
    $skin = $skins[0];
  }
}
global $css;
if (defined('ZM_FORCE_CSS_DEFAULT')) {
  $css = ZM_FORCE_CSS_DEFAULT;
} else if ( isset($_GET['css']) ) {
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
      if (isset($_COOKIE['zmCSS'])) {
        unset($_COOKIE['zmCSS']);
        setcookie('zmCSS', '', time() - 3600);
      }
    } else {
      $css = '';
    }
  } else {
    ZM\Error("No css options found at skins/$skin/css");
    $css = '';
  }
}

global $navbar_type;
$navbar_type = isset($_SESSION['navbar_type']) ? $_SESSION['navbar_type'] : '';
$valid_navbar_types = ['normal'=>1, 'collapsed'=>1, 'left'=>1];

if (isset($_REQUEST['navbar_type'])) {
  if (isset($valid_navbar_types[$_REQUEST['navbar_type']])) {
    $navbar_type = $_REQUEST['navbar_type'];
  } else {
    ZM\Error('Invalid navbar_type '.$_REQUEST['navbar_type'].' specified');
  }
}

# Cookie overrides session
if (isset($_COOKIE['navbar_type'])) {
  if (isset($valid_navbar_types[$_COOKIE['navbar_type']])) {
    $navbar_type = $_COOKIE['navbar_type'];
  } else {
    ZM\Error('Invalid navbar_type '.$_COOKIE['navbar_type'].' specified');
  }
}

if (!$navbar_type and defined('ZM_WEB_NAVBAR_TYPE')) {
  if (isset($valid_navbar_types[ZM_WEB_NAVBAR_TYPE])) {
    $navbar_type = ZM_WEB_NAVBAR_TYPE;
  } else {
    ZM\Error('Invalid navbar_type '.ZM_WEB_NAVBAR_TYPE. ' in options');
  }
}

if (defined('ZM_FORCE_NAVBAR_TYPE')) {
  if (isset($valid_navbar_types[ZM_FORCE_NAVBAR_TYPE])) {
    $navbar_type = ZM_FORCE_NAVBAR_TYPE;
  } else {
    ZM\Error('Invalid navbar_type '.ZM_FORCE_NAVBAR_TYPE. ' forced');
  }
}

if (!isset($valid_navbar_types[$navbar_type])) {
  $navbar_type = 'normal';
}

define('ZM_BASE_PATH', dirname($_SERVER['REQUEST_URI']));
define('ZM_SKIN_PATH', "skins/$skin");
define('ZM_SKIN_NAME', $skin);

$skinBase = array(); // To allow for inheritance of skins
if (!file_exists(ZM_SKIN_PATH))
  ZM\Fatal("Invalid skin '$skin'");
$skinBase[] = $skin;

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
$_SESSION['navbar_type'] = $navbar_type;

# Add Cross domain access headers
CORSHeaders();

# Globals
# Running is global but only do the daemonCheck if it is actually needed
$running = null;
$action = null;
$error_message = null;
$redirect = null;
$view = isset($_REQUEST['view']) ? detaintPath($_REQUEST['view']) : null;
$user = null;
$request = isset($_REQUEST['request']) ? detaintPath($_REQUEST['request']) : null;

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

if (isset($_POST['action'])) {
  # Actions can only be performed on POST because we don't check csrf on GETs.
  $action = detaintPath($_POST['action']);
} else if (isset($_REQUEST['action']) and $_REQUEST['action'] and empty($_REQUEST['request'])) {
  ZM\Error('actions can no longer be performed without POST.');
}

# The only variable we really need to set is action. The others are informal.
isset($view) || $view = NULL;
isset($request) || $request = NULL;
isset($action) || $action = NULL;

if ( (!$view and !$request) or ($view == 'console') ) {
  check_timezone();
}

ZM\Debug("View: $view Request: $request Action: $action User: " . ( isset($user) ? $user->Username() : 'none' ));
if (
  ZM_ENABLE_CSRF_MAGIC &&
  ( $action != 'login' ) &&
  ( $view != 'view_video' ) && // only video no html
  ( $view != 'image' ) && // view=image doesn't return html, just image data.
  (!$request or ($request == 'modal')) && // requests are ajax and can only return json.
  //( $view != 'frames' ) &&  // big html can overflow ob
  ( $view != 'archive' ) // returns data
  && ( (!isset($_SERVER['CONTENT_TYPE']) or ($_SERVER['CONTENT_TYPE'] != 'application/csp-report')) )
) {
  require_once('includes/csrf/csrf-magic.php');
  #ZM\Debug("Calling csrf_check with the following values: \$request = \"$request\", \$view = \"$view\", \$action = \"$action\"");
  csrf_check();
}

# If I put this here, it protects all views and popups, but it has to go after actions.php because actions.php does the actual logging in.
if ( ZM_OPT_USE_AUTH and (!isset($user)) and ($view != 'login') and ($view != 'none') ) {
  if ($request) {
    # requests only return json
    header('HTTP/1.1 401 Unauthorized');
    exit;
  }
  $view = 'none';
  $postLoginQuery = $_SERVER['QUERY_STRING'];
  $redirect = '?view=login&postLoginQuery=' . urlencode($postLoginQuery);
  zm_session_start();
  $_SESSION['postLoginQuery'] = $postLoginQuery;
  session_write_close();
  ZM\Debug("Redirecting to $redirect");
  header('Location: '.$redirect);
  return;
} else if ( ZM_SHOW_PRIVACY && ($view != 'privacy') && ($view != 'options') && (!$request) && canEdit('System') ) {
  $view = 'none';
  $redirect = '?view=privacy';
  $request = null;
}

# Need to include actions because it does auth
if ( $action and $view and !$request ) {
  if ( file_exists('includes/actions/'.$view.'.php') ) {
    require_once('includes/actions/'.$view.'.php');
  } else {
    ZM\Debug("No includes/actions/$view.php for action $action");
  }
}
if ($error_message) ZM\Warning($error_message);

if ( isset($_REQUEST['redirect']) ) {
  $redirect = '?view='.detaintPath($_REQUEST['redirect']);
}

if ($redirect) {
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

if (!$view) {
  $view = getHomeView();
  ZM\Debug("Empty view, defaulting to home view".$view);
  header('Location: ?view='.$view);
  return;
}

# Add CSP Headers
$cspNonce = bin2hex(zm_random_bytes(16));

if ( $includeFiles = getSkinIncludes('views/'.$view.'.php', true, true) ) {
  ob_start();
  CSPHeaders($view, $cspNonce);
  foreach ( $includeFiles as $includeFile ) {
    if (!file_exists($includeFile)) {
      ZM\Error("View '$view' does not exist, redirecting to console");
      header('Location: ?view=console');
      return;
    }
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
} # end if include files for view
// If the view is missing or the view still returned error with the user logged in,
// then it is not recoverable.
if ( !$includeFiles || $view == 'error' ) {
  foreach ( getSkinIncludes('views/error.php', true, true) as $includeFile )
    require_once $includeFile;
}
?>
