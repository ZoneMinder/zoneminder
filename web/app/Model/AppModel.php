<?php
/**
 * Application model for Cake.
 *
 * This file is application-wide model file. You can put all
 * application-wide model-related methods here.
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
 * @package       app.Model
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

App::uses('Model', 'Model');

/**
 * Application model for Cake.
 *
 * Add your application-wide methods in the class below, your models
 * will inherit them.
 *
 * @package       app.Model
 */
class AppModel extends Model {

	public function daemonStatus() {
		$zm_path_bin = Configure::read('ZM_PATH_BIN');
		$string = $zm_path_bin."/zmdc.pl status";
		$daemon_status = shell_exec ( $string );
		return $daemon_status;
  }

  public function reScale( $dimension, $dummy) {
    $scale_base = Configure::read('SCALE_BASE');
    for ( $i = 1; $i < func_num_args(); $i++ ) {
      $scale = func_get_arg( $i );
      if ( !empty($scale) && $scale != $scale_base ) {
        $dimension = (int)(($dimension*$scale)/$scale_base);
      }
    }
    return( $dimension );
  }

  public function getEventPath( $event ){
    if (Configure::read('ZM_USE_DEEP_STORAGE')) {
      $eventPath = $event['MonitorId'].'/'.strftime("%y/%m/%d/%H/%M/%S", strtotime($event['StartTime']));
    } else {
      $eventPath = $event['MonitorId'].'/'.$event['Id'];
    }
    return($eventPath);
  }

}
