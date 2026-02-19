<?php
App::uses('AppModel', 'Model');
/**
 * RoleMonitorPermission Model
 *
 */
class RoleMonitorPermission extends AppModel {

/**
 * Use table
 *
 * @var mixed False or table name
 */
  public $useTable = 'Role_Monitors_Permissions';

/**
 * Primary key field
 *
 * @var string
 */
  public $primaryKey = 'Id';

/**
 * belongsTo associations
 *
 * @var array
 */
  public $belongsTo = array(
    'Role' => array(
      'className' => 'Role',
      'foreignKey' => 'RoleId',
    ),
    'Monitor' => array(
      'className' => 'Monitor',
      'foreignKey' => 'MonitorId',
    ),
  );

}
