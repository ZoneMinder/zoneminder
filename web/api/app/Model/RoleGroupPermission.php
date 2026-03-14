<?php
App::uses('AppModel', 'Model');
/**
 * RoleGroupPermission Model
 *
 */
class RoleGroupPermission extends AppModel {

/**
 * Use table
 *
 * @var mixed False or table name
 */
  public $useTable = 'Role_Groups_Permissions';

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
    'Group' => array(
      'className' => 'Group',
      'foreignKey' => 'GroupId',
    ),
  );

}
