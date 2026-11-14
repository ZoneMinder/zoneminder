<?php
/**
 * Application model for CakePHP.
 *
 * This file is application-wide model file. You can put all
 * application-wide model-related methods here.
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
 * @package       app.Model
 * @since         CakePHP(tm) v 0.2.9
 * @license       https://opensource.org/licenses/mit-license.php MIT License
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

  // Check numbers, dates and enums against the schema so bad input is a validation error, not a 500.
  public function beforeValidate($options = array()) {
    if (empty($this->data[$this->alias])) return true;
    foreach ($this->schema() as $field => $column) {
      if (!isset($this->data[$this->alias][$field])) continue;
      $value = $this->data[$this->alias][$field];
      if (!is_scalar($value) or $value === '') continue;
      $value = (string)$value;
      $type = $column['type'];
      if (in_array($type, array('integer', 'biginteger', 'tinyinteger', 'smallinteger', 'float', 'decimal'))) {
        if (!is_numeric($value)) $this->invalidate($field, $field.' must be a number');
      } else if (in_array($type, array('datetime', 'timestamp', 'date'))) {
        if (!preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})(?:[ T]\d{1,2}:\d{1,2}(?::\d{1,2}(?:\.\d+)?)?)?$/', $value, $m)
            or !checkdate((int)$m[2], (int)$m[3], (int)$m[1])) {
          $this->invalidate($field, $field.' must be a date like 2026-01-31 13:45:00');
        }
      } else if (strpos($type, 'enum(') === 0) {
        preg_match_all("/'((?:[^']|'')*)'/", $type, $m);
        $allowed = array_map(function($v) { return str_replace("''", "'", $v); }, $m[1]);
        if (!in_array(strtolower($value), array_map('strtolower', $allowed), true)) {
          $this->invalidate($field, $field.' must be one of: '.implode(', ', $allowed));
        }
      }
    }
    return true;
  }
}
