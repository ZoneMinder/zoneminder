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
                'rule' => array('notBlank'),
                'required' => 'create',
                'message' => 'A username is required'
            ),
            'unique' => array(
                'rule' => array('isUnique'),
                'message' => 'This username is already in use'
            ),
            'length' => array(
                'rule' => array('maxLength', 64),
                'message' => 'A username is at most 64 characters'
            )
        ),
        'Password' => array(
            'required' => array(
                'rule' => array('notBlank'),
                'required' => 'create',
                'message' => 'A password is required'
            ),
            'acceptable' => array(
                'rule' => array('acceptablePassword')
            )
        )
    );

    # The same check as the web interface (passwordProblem() in includes/auth.php):
    # true, or the reason as the validation message.
    public function acceptablePassword($check) {
      $problem = passwordProblem(reset($check));
      return ($problem === null) ? true : passwordProblemText($problem);
    }

    function beforeFind($query) {
      if ( empty($query['fields']) ) {
        $schema = $this->schema();
        unset($schema['Password']);

        foreach (array_keys($schema) as $field) {
          $query['fields'][] = $this->alias . '.' . $field;
        }
        # A field list leaves out joined models unless they are named, so the Role went missing.
        $recursive = isset($query['recursive']) ? $query['recursive'] : $this->recursive;
        if ($recursive >= 0) {
          foreach (array_keys($this->belongsTo) as $assoc) {
            foreach (array_keys($this->{$assoc}->schema()) as $field) {
              $query['fields'][] = $assoc . '.' . $field;
            }
          }
        }
        return $query;
      }
      return parent::beforeFind($query);
    }

    public function beforeSave($options = array()) {
      if (!empty($this->data['User']['Password'])) {
        $this->data['User']['Password'] = password_hash($this->data['User']['Password'], PASSWORD_BCRYPT);
        # A password change revokes the user's existing API tokens: any token
        # issued before TokenMinExpiry is rejected by validateToken(), the same
        # mechanism the options page's revoke action uses. Without this a pre-
        # change access or refresh token keeps working after the password is
        # changed (refs GHSA-5w6w-qp25-2qqh).
        $this->data['User']['TokenMinExpiry'] = time();
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
    'Role' => array(
      'className' => 'Role',
      'foreignKey' => 'RoleId',
    ),
	);

/**
 * hasMany associations
 *
 * @var array
 */
	public $hasMany = array(
	);

}
