<?php
App::uses('AppModel', 'Model');
/**
 * Role Model
 *
 */
class Role extends AppModel {

    public $validate = array(
        'Name' => array(
            'required' => array(
                'rule' => array('notBlank'),
                'required' => 'create',
                'message' => 'A role name is required'
            ),
            'unique' => array(
                'rule' => array('isUnique'),
                'message' => 'A role with that name already exists'
            )
        )
    );

/**
 * Use table
 *
 * @var mixed False or table name
 */
  public $useTable = 'User_Roles';

/**
 * Primary key field
 *
 * @var string
 */
  public $primaryKey = 'Id';

/**
 * Display field
 *
 * @var string
 */
  public $displayField = 'Name';


  //The Associations below have been created with all possible keys, those that are not needed can be removed

/**
 * hasMany associations
 *
 * @var array
 */
  public $hasMany = array(
    'RoleGroupPermission' => array(
      'className' => 'RoleGroupPermission',
      'foreignKey' => 'RoleId',
      'dependent' => true,
    ),
    'RoleMonitorPermission' => array(
      'className' => 'RoleMonitorPermission',
      'foreignKey' => 'RoleId',
      'dependent' => true,
    ),
    'User' => array(
      'className' => 'User',
      'foreignKey' => 'RoleId',
      'dependent' => false,
      # Associated records skip User::beforeFind(), so name the columns here
      # to keep Password and TokenMinExpiry out of role responses.
      'fields' => array('Id', 'Username', 'Name', 'Email', 'Phone', 'Language', 'Enabled', 'RoleId'),
    ),
  );

}
