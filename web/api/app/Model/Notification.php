<?php
App::uses('AppModel', 'Model');

class Notification extends AppModel {

  public $useTable = 'Notifications';
  public $primaryKey = 'Id';
  public $displayField = 'Token';

  public $belongsTo = array(
    'User' => array(
      'className' => 'User',
      'foreignKey' => 'UserId',
    ),
  );

  public $validate = array(
    'Token' => array(
      'notBlank' => array(
        'rule' => array('notBlank'),
        'message' => 'Token is required',
        'required' => 'create',
      ),
      'maxLength' => array(
        'rule' => array('maxLength', 512),
        'message' => 'Token must be at most 512 characters',
      ),
      'isUnique' => array(
        'rule' => 'isUnique',
        'message' => 'Token is already registered',
      ),
    ),
    'Platform' => array(
      'inList' => array(
        'rule' => array('inList', array('android', 'ios', 'web')),
        'message' => 'Platform must be android, ios, or web',
        'required' => 'create',
      ),
    ),
    'Interval' => array(
      'naturalNumber' => array(
        'rule' => array('naturalNumber', true),
        'message' => 'Interval must be a non-negative integer',
        'allowEmpty' => true,
      ),
    ),
    'BadgeCount' => array(
      'numeric' => array(
        'rule' => array('numeric'),
        'message' => 'BadgeCount must be a number',
        'allowEmpty' => true,
      ),
    ),
    'PushState' => array(
      'inList' => array(
        'rule' => array('inList', array('enabled', 'disabled')),
        'message' => 'PushState must be enabled or disabled',
        'required' => false,
      ),
    ),
  );

}
