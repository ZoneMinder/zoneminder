<?php
/**
 * Application level Controller
 *
 * This file is application-wide controller file. You can put all
 * application-wide controller-related methods here.
 *
 * PHP 5
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
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('Controller', 'Controller');

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
	public $helpers = array(
		'Session',
		'Js',
		'Html' => array('className' => 'BoostCake.BoostCakeHtml'),
		'Form' => array('className' => 'BoostCake.BoostCakeForm'),
		'Paginator' => array('className' => 'BoostCake.BoostCakePaginator'),
	);
  public $components = array('Cookie', 'Session', 'RequestHandler');

  public function beforeFilter() {
    parent::beforeFilter();
	$this->loadModel('Config');
	$this->loadModel('AppModel');
    $this->Cookie->name = 'ZoneMinder';
    if (!$this->Cookie->read('zmBandwidth')) {
      $this->Cookie->write('zmBandwidth', 'low', false);
    }
  $this->set('zmBandwidth', $this->Cookie->read('zmBandwidth'));


	$configFile =  "/usr/local/etc/zm.conf";
	$lines = file($configFile);
	foreach ($lines as $linenum => $line) {
		if ( preg_match( '/^\s*([^=\s]+)\s*=\s*(.+?)\s*$/', $line, $matches )) {
			Configure::write($matches[1], $matches[2]);
		}
	}

	$options = $this->Config->find('list', array('fields' => array('Name', 'Value')));
	foreach ($options as $key => $value) {
		Configure::write($key, $value);
    }
    Configure::write('SCALE_BASE', 100);
	if ($this->AppModel->daemonStatus()) {
		$this->set('daemonStatus', ('<span class="alert alert-success">Running</span>'));
	} else {
		$this->set('daemonStatus', ('<span class="alert alert-danger">Stopped</span>'));
	}

    $this->set('systemLoad', $this->AppModel->getSystemLoad());
    $this->set('diskSpace', $this->AppModel->getDiskSpace());
    $this->set('zmVersion', Configure::read('ZM_VERSION'));
  }

  function extractNamedParams($mandatory, $optional = array()) {
    $params = $this->params['named'];

    if(empty($params)) {
      return false;
    }

    $mandatory = array_flip($mandatory);
    $all_named_keys = array_merge($mandatory, $optional);
    $valid = array_intersect_key($params, $all_named_keys);
    $output = array_merge($optional, $valid);
    $diff = array_diff_key($all_named_keys, $output);

    if (empty($diff)) {
      return $output;
    } else {
      return false;
    }

  }
}
