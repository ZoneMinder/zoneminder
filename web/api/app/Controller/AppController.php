<?php
/**
 * Application level Controller
 *
 * This file is application-wide controller file. You can put all
 * application-wide controller-related methods here.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       app.Controller
 * @since         CakePHP(tm) v 0.2.9
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
App::uses('Controller', 'Controller');
App::uses('CrudControllerTrait', 'Crud.Lib');

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @package           app.Controller
 * @link              https://book.cakephp.org/2.0/en/controllers.html#the-app-controller
 */
class AppController extends Controller {
  use CrudControllerTrait;

  public $components = [
    'RequestHandler',
    'Crud.Crud' => [
      # No Crud actions are mapped. CrudControllerTrait would otherwise answer any action a
      # controller does not define (view, add, edit, keyvalue, ...) with a generic handler
      # that applies none of the controller's permission or per-monitor checks, e.g.
      # zones/view/<id> returned any zone. A controller must implement each action itself.
      'actions' => [],
      'listeners' => ['Api', 'ApiTransformation']
    #],
    #'DebugKit.Toolbar' => [
    #  'bootstrap' => true, 'routes' => true
    ]
  ];

  // Global beforeFilter function
  //Zoneminder sets the username session variable
  // to the logged in user. If this variable is set
  // then you are logged in
  // its pretty simple to extend this to also check
  // for role and deny API access in future 
  // Also checking to do this only if ZM_OPT_USE_AUTH is on
  public function beforeFilter() {
    if ( ! ZM_OPT_USE_API ) {
      throw new UnauthorizedException(__('API Disabled'));
      return; 
    } 

    # For use throughout the app. If not logged in, this will be null.
    global $user;
    require_once __DIR__ .'/../../../includes/auth.php';
    # This will auto-login if username=&password= are set, or auth=
    zm_authenticate_request();
   
    if ( ZM_OPT_USE_AUTH ) {
      if ( ZM_OPT_USE_LEGACY_API_AUTH or !strcasecmp($this->params->action, 'login') ) {
        # This is here because historically we allowed user=&pass= in the api. web-ui auth uses username=&password=
        $username = $this->request->query('user') ? $this->request->query('user') : $this->request->data('user');
        $password = $this->request->query('pass') ? $this->request->query('pass') : $this->request->data('pass');
        if ( $username and $password ) {
          $ret = validateUser($username, $password);
          $user = $ret[0];
          $retstatus = $ret[1];
          if ( !$user ) {
            throw new UnauthorizedException(__($retstatus));
            return;
          } 
          ZM\Debug("Login successful for user \"$username\"");
        }
      }

      if ( ZM_OPT_USE_LEGACY_API_AUTH ) {
        require_once __DIR__ .'/../../../includes/session.php';
        $stateful = $this->request->query('stateful') ? $this->request->query('stateful') : $this->request->data('stateful');
        if ( $stateful ) {
          // zm_session_start() already populates $_SESSION['remoteAddr'] from
          // getRemoteAddr() (X-Forwarded-For via a trusted proxy), matching what
          // getAuthUser() uses for validation. Don't overwrite it with bare
          // REMOTE_ADDR here — that bound the hash to the proxy IP and broke
          // validation behind a reverse proxy.
          zm_session_start();
          if ($user) {
            // A stateful login issues the session cookie, so store the session even
            // though this request arrived without one.
            zm_session_persist();
            $_SESSION['username'] = $user->Username();
            if ( ZM_AUTH_RELAY == 'plain' ) {
              // Need to save this in session, can't use the value in User because it is hashed
              $_SESSION['password'] = $_REQUEST['password'];
            }
            generateAuthHash(ZM_AUTH_HASH_IPS);
          }
          session_write_close();
        } else if ( isset($_COOKIE['ZMSESSID']) and !$user ) {
          # Have a cookie set, try to load user by session
          if ( ! is_session_started() )
            zm_session_start();

          ZM\Debug(print_r($_SESSION, true));
          $user = userFromSession();
          session_write_close();
        }
      }

      # NON LEGACY, token based access
      $token = $this->request->query('token') ? $this->request->query('token') : $this->request->data('token');
      if ( $token ) {
        // if you pass a token to login, we should only allow
        // refresh tokens to regenerate new access and refresh tokens
        if ( !strcasecmp($this->params->action, 'login') ) {
          $only_allow_token_type = 'refresh';
        } else {
          // for any other methods, don't allow refresh tokens
          // they are supposed to be infrequently used for security
          // purposes
          $only_allow_token_type = 'access';
        }
        $ret = validateToken($token, $only_allow_token_type, true);
        $user = $ret[0];
        $retstatus = $ret[1];
        if ( !$user ) {
          throw new UnauthorizedException(__($retstatus));
          return;
        } 
      } # end if token

      if ( $user and ( $user->APIEnabled() != 1 ) ) {
        ZM\Error('API disabled for: '.$user->Username());
        throw new UnauthorizedException(__('API disabled for: '.$user->Username()));
        $user = null;
      }

      // We need to reject methods that are not authenticated
      // besides login and logout
      if ( strcasecmp($this->params->action, 'logout') ) {
        if ( !( $user and $user->Username() ) ) {
          throw new UnauthorizedException(__('Not Authenticated'));
          return;
        } else if ( !( $user and $user->Enabled() ) ) {
          throw new UnauthorizedException(__('User is not enabled'));
          return;
        }
      } # end if ! login or logout

    } # end if ZM_OPT_AUTH
    // make sure populated user object has APIs enabled

    if (isset($_SERVER['HTTP_ORIGIN'])) {
      global $Servers;
      if ( sizeof($Servers) < 1 ) {
        # Only need CORSHeaders in the event that there are multiple servers in use.
        # ICON: Might not be true. multi-port?
        if ( ZM_MIN_STREAMING_PORT ) {
          ZM\Debug('Setting default Access-Control-Allow-Origin from ' . $_SERVER['HTTP_ORIGIN']);
          $this->response->header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
          $this->response->header('Access-Control-Allow-Credentials: true');
          $this->response->header('Access-Control-Allow-Headers: x-requested-with,x-request');
        }
        return;
      }
      foreach ($Servers as $Server) {
        if (
          preg_match('/^(https?:\/\/)?'.preg_quote($Server->Hostname(),'/').'/i', $_SERVER['HTTP_ORIGIN'])
          or
          preg_match('/^(https?:\/\/)?'.preg_quote($Server->Name(),'/').'/i', $_SERVER['HTTP_ORIGIN'])
        ) {
          ZM\Debug('Setting Access-Control-Allow-Origin from '.$_SERVER['HTTP_ORIGIN']);
          $this->response->header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
          $this->response->header('Access-Control-Allow-Credentials: true');
          $this->response->header('Access-Control-Allow-Headers: x-requested-with,x-request');
          break;
        }
      }
    }

  } # end function beforeFilter()

  # Model::save() takes the record id from a primary key in the data it is given, so an
  # edit authorized for the id in the URL could otherwise write to whichever id the request
  # body names. Drop any primary key from the request data and pin the model to $id.
  protected function pinRequestId($model, $id) {
    $alias = $model->alias;
    $key = $model->primaryKey;
    if (isset($this->request->data[$alias]) and is_array($this->request->data[$alias])) {
      unset($this->request->data[$alias][$key]);
    }
    unset($this->request->data[$key]);
    $model->id = $id;
  }

  # A find() condition limiting $field to the monitors the user may view: empty when the user
  # is not restricted, and matching nothing when the user may view no monitor at all.
  protected function viewableMonitorCondition($field) {
    global $user;
    if (!$user or !$user->unviewableMonitorIds()) return array();
    $ids = $user->viewableMonitorIds();
    return array($field => count($ids) ? $ids : array(0));
  }

  # A field from the request data, whether sent as Model[field] or bare, or null.
  protected function requestField($alias, $field) {
    $data = $this->request->data;
    if (isset($data[$alias]) and is_array($data[$alias])) $data = $data[$alias];
    return isset($data[$field]) ? $data[$field] : null;
  }

  # Throw unless the user may view monitor $monitorId.
  protected function requireMonitorView($monitorId) {
    require_once __DIR__ .'/../../../includes/Monitor.php';
    $monitor = new ZM\Monitor($monitorId);
    if (!$monitor->Id()) {
      throw new NotFoundException(__('Invalid monitor'));
    }
    if (!$monitor->canView()) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
    }
  }

  # Throw unless the user may edit monitor $monitorId.
  protected function requireMonitorEdit($monitorId) {
    require_once __DIR__ .'/../../../includes/Monitor.php';
    $monitor = new ZM\Monitor($monitorId);
    if (!$monitor->Id()) {
      throw new NotFoundException(__('Invalid monitor'));
    }
    if (!$monitor->canEdit()) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
    }
  }

  # The ids of the $assoc records a request associates with $alias, given either as
  # $alias[$idsField] (comma separated or array) or as HABTM $assoc data; null if neither.
  protected function requestAssociatedIds($alias, $idsField, $assoc) {
    $data = $this->request->data;
    if (isset($data[$alias][$idsField])) {
      $ids = $data[$alias][$idsField];
    } else if (isset($data[$assoc])) {
      $ids = (isset($data[$assoc][$assoc]) and is_array($data[$assoc][$assoc])) ? $data[$assoc][$assoc] : $data[$assoc];
    } else {
      return null;
    }
    if (!is_array($ids)) $ids = explode(',', $ids);
    return array_map(function($id) { return intval(is_array($id) ? (isset($id['Id']) ? $id['Id'] : 0) : $id); }, $ids);
  }

  # Throw unless the user may view every event in $eventIds, which includes its monitor.
  protected function requireEventsView($eventIds) {
    require_once __DIR__ .'/../../../includes/Event.php';
    foreach (array_unique($eventIds) as $eventId) {
      $event = new ZM\Event($eventId);
      if (!$event->Id()) {
        throw new NotFoundException(__('Invalid event'));
      }
      if (!$event->canView()) {
        throw new UnauthorizedException(__('Insufficient Privileges'));
      }
    }
  }

  # Drop the events on monitors the user may not view from a row's contained Event list.
  protected function filterContainedEvents($row, $key = 'Event') {
    $condition = $this->viewableMonitorCondition('MonitorId');
    if (!count($condition) or !isset($row[$key])) return $row;
    $allowed = $condition['MonitorId'];
    $row[$key] = array_values(array_filter($row[$key], function($event) use ($allowed) {
      return isset($event['MonitorId']) and in_array($event['MonitorId'], $allowed);
    }));
    return $row;
  }
}
