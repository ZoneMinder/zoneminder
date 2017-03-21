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
 * @package		app.Controller
 * @link		http://book.cakephp.org/2.0/en/controllers.html#the-app-controller
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
		$this->loadModel('Config');
		
    $options = array('conditions' => array('Config.' . $this->Config->primaryKey => 'ZM_OPT_USE_API'));
    $config = $this->Config->find('first', $options);
    $zmOptApi = $config['Config']['Value'];

		if ($zmOptApi !='1') {
      throw new UnauthorizedException(__('API Disabled'));
      return; 
		}
		
    $options = array('conditions' => array('Config.' . $this->Config->primaryKey => 'ZM_OPT_USE_AUTH'));
    $config = $this->Config->find('first', $options);
    $zmOptAuth = $config['Config']['Value'];

    if ( $zmOptAuth=='1' ) {
      $this->loadModel('User');
      if ( isset($_REQUEST['user']) and isset($_REQUEST['pass']) ) {
        $user = $this->User->find('first', array ('conditions' => array (
                'User.Username' => $_REQUEST['user'],
                'User.Password' => $_REQUEST['pass'],
                )) );
        if ( ! $user ) {
          throw new UnauthorizedException(__('User not found'));
          return;
        } else {
          $this->Session->Write( 'user.Username', $user['User']['Username'] );
          $this->Session->Write( 'user.Enabled', $user['User']['Enabled'] );
        }
      }

      if ( isset($_REQUEST['auth']) ) {
        require_once "../../../includes/functions.php";

        // Define some defines required by getAuthUser in functions.php
        $defines = array('ZM_AUTH_HASH_IPS', 'ZM_AUTH_HASH_SECRET', 'ZM_AUTH_RELAY', 'ZM_OPT_USE_AUTH');
        $configQuery = array(
                             'conditions' => array('OR' => array('Name' => $defines)),
                             'fields' => array('Name', 'Value')
                             );
        $config = $this->Config->find('list', $configQuery);

        foreach ($defines as $define) {
          define($define, $config[$define]);
        }

        $user = getAuthUser($_REQUEST['auth']);
        if ( ! $user ) {
          throw new UnauthorizedException(__('User not found'));
          return;
        } else {
          $this->Session->Write( 'user.Username', $user['Username'] );
          $this->Session->Write( 'user.Enabled', $user['Enabled'] );
        }
      }

      if( ! $this->Session->Read('user.Username') ) {
        throw new UnauthorizedException(__('Not Authenticated'));
        return;
      } else if ( ! $this->Session->Read('user.Enabled') ) {
        throw new UnauthorizedException(__('User is not enabled'));
        return;
      }

      $options = array ('conditions' => array ('User.Username' => $this->Session->Read('user.Username')));
      $userMonitors = $this->User->find('first', $options);
      $this->Session->Write('allowedMonitors',$userMonitors['User']['MonitorIds']);
      $this->Session->Write('streamPermission',$userMonitors['User']['Stream']);
      $this->Session->Write('eventPermission',$userMonitors['User']['Events']);
      $this->Session->Write('controlPermission',$userMonitors['User']['Control']);
      $this->Session->Write('systemPermission',$userMonitors['User']['System']);
      $this->Session->Write('monitorPermission',$userMonitors['User']['Monitors']);
    }
    else // if auth is not on, you can do everything
    {
      //$userMonitors = $this->User->find('first', $options);
      $this->Session->Write('allowedMonitors','');
      $this->Session->Write('streamPermission','View');
      $this->Session->Write('eventPermission','Edit');
      $this->Session->Write('controlPermission','Edit');
      $this->Session->Write('systemPermission','Edit');
      $this->Session->Write('monitorPermission','Edit');
    }
		
		
  } # end function beforeFilter()

}
