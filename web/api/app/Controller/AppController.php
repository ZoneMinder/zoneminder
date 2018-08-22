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
    'Session', //  We are going to use SessionHelper to check PHP session vars
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
    $user = $this->Session->read('user');
    
    if ( ZM_OPT_USE_AUTH ) {
      require_once '../../../includes/auth.php';

      $mUser = $this->request->query('user') ? $this->request->query('user') : $this->request->data('user');
      $mPassword = $this->request->query('pass') ? $this->request->query('pass') : $this->request->data('pass');
      $mAuth = $this->request->query('auth') ? $this->request->query('auth') : $this->request->data('auth');

      if ( $mUser and $mPassword ) {
        $user = userLogin($mUser, $mPassword);
        if ( !$user ) {
          throw new UnauthorizedException(__('User not found or incorrect password'));
          return;
        }
      } else if ( $mAuth ) {
        $user = getAuthUser($mAuth);
        if ( !$user ) {
          throw new UnauthorizedException(__('Invalid Auth Key'));
          return;
        }
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
   
  } # end function beforeFilter()
}
