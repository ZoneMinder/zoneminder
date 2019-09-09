<?php
/**
 * Application level Controller
 *
 * This file is application-wide controller file. You can put all
 * application-wide controller-related methods here.
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Controller
 * @since         CakePHP(tm) v 0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
App::uses('Controller', 'Controller');
App::uses('CrudControllerTrait', 'Crud.Lib');

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @package   app.Controller
 * @link    http://book.cakephp.org/2.0/en/controllers.html#the-app-controller
 */
class AppController extends Controller {
  use CrudControllerTrait;

  public $components = [
    'RequestHandler',
    'Crud.Crud' => [
      'actions' => [
        'index' => 'Crud.Index',
        'add'   => 'Crud.Add',
        'edit'  => 'Crud.Edit',
        'view'  => 'Crud.View',
        'keyvalue' => 'Crud.List',
        'category' => 'Crud.Category'
      ],
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
   
    if ( ZM_OPT_USE_AUTH ) {
      # This will auto-login if username=&password= are set, or auth=
      require_once __DIR__ .'/../../../includes/auth.php';

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
          ZM\Info("Login successful for user \"$username\"");
        }
      }

      if ( ZM_OPT_USE_LEGACY_API_AUTH ) {
        require_once __DIR__ .'/../../../includes/session.php';
        $stateful = $this->request->query('stateful') ? $this->request->query('stateful') : $this->request->data('stateful');
        if ( $stateful ) {
          zm_session_start();
          $_SESSION['remoteAddr'] = $_SERVER['REMOTE_ADDR']; // To help prevent session hijacking
          $_SESSION['username'] = $user['Username'];
          if ( ZM_AUTH_RELAY == 'plain' ) {
            // Need to save this in session, can't use the value in User because it is hashed
            $_SESSION['password'] = $_REQUEST['password'];
          }
          generateAuthHash(ZM_AUTH_HASH_IPS);
          session_write_close();
        } else if ( $_COOKIE['ZMSESSID'] and !$user ) {
          # Have a cookie set, try to load user by session
          if ( ! is_session_started() )
            zm_session_start();

          ZM\Logger::Debug(print_r($_SESSION, true));
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

      if ( $user and ( $user['APIEnabled'] != 1 ) ) {
        ZM\Error('API disabled for: '.$user['Username']);
        throw new UnauthorizedException(__('API disabled for: '.$user['Username']));
        $user = null;
      }

      // We need to reject methods that are not authenticated
      // besides login and logout
      if ( strcasecmp($this->params->action, 'logout') ) {
        if ( !( $user and $user['Username'] ) ) {
          throw new UnauthorizedException(__('Not Authenticated'));
          return;
        } else if ( !( $user and $user['Enabled'] ) ) {
          throw new UnauthorizedException(__('User is not enabled'));
          return;
        }
      } # end if ! login or logout

    } # end if ZM_OPT_AUTH
    // make sure populated user object has APIs enabled
  } # end function beforeFilter()
}
