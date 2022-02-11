<?php
App::uses('AppModel', 'Model');
/**
 * User Model
 *
 */
class User extends AppModel {

    public $validate = array(
        'Username' => array(
            'required' => array(
                'rule' => array('notEmpty'),
                'message' => 'A username is required'
            )
        ),
        'Password' => array(
            'required' => array(
                'rule' => array('notEmpty'),
                'message' => 'A password is required'
            )
        )
    );

    function beforeFind($query) {
      if ( empty($query['fields']) ) {
        $schema = $this->schema();
        unset($schema['Password']);

        foreach (array_keys($schema) as $field) {
          $query['fields'][] = $this->alias . '.' . $field;
        }
        return $query;
      }
      return parent::beforeFind($query);
    }

    public function beforeSave($options = array()) {
      if (!empty($this->data['User']['Password'])) {
        $this->data['User']['Password'] = password_hash($this->data['User']['Password'], PASSWORD_BCRYPT);
      }
      return true;
    }  # end function beforeSave

/**
 * Use table
 *
 * @var mixed False or table name
 */
	public $useTable = 'Users';

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
	public $displayField = 'Username';


	//The Associations below have been created with all possible keys, those that are not needed can be removed

/**
 * belongsTo associations
 *
 * @var array
 */
	public $belongsTo = array(
	);

/**
 * hasMany associations
 *
 * @var array
 */
	public $hasMany = array(
	);

}
