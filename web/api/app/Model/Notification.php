<?php
App::uses('AppModel', 'Model');

class Notification extends AppModel {

  public $useTable = 'Notifications';
  public $primaryKey = 'Id';
  public $displayField = 'Token';

  public $validate = array(
    'Token' => array(
      'notBlank' => array(
        'rule' => array('notBlank'),
        'message' => 'Token is required',
      ),
    ),
    'Platform' => array(
      'inList' => array(
        'rule' => array('inList', array('android', 'ios', 'web')),
        'message' => 'Platform must be android, ios, or web',
      ),
    ),
    'PushState' => array(
      'inList' => array(
        'rule' => array('inList', array('enabled', 'disabled')),
        'message' => 'PushState must be enabled or disabled',
        'allowEmpty' => true,
      ),
    ),
  );

}
