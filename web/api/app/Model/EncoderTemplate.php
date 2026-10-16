<?php
App::uses('AppModel', 'Model');

class EncoderTemplate extends AppModel {
  public $useTable = 'EncoderTemplates';
  public $primaryKey = 'Id';
  public $displayField = 'Name';
  public $recursive = -1;

  public $validate = array(
    'Encoder' => array(
      'notBlank' => array(
        'rule' => array('notBlank'),
        'message' => 'Encoder is required',
      ),
    ),
    'Name' => array(
      'notBlank' => array(
        'rule' => array('notBlank'),
        'message' => 'Name is required',
        'last' => true,
      ),
      'unique' => array(
        'rule' => array('isUniqueByEncoder'),
        'message' => 'A template with that name already exists for this encoder',
      ),
    ),
  );

  // Custom validator: Name must be unique within the row's Encoder.
  public function isUniqueByEncoder($field) {
    $name = $field['Name'];
    $encoder = isset($this->data[$this->alias]['Encoder']) ? $this->data[$this->alias]['Encoder'] : null;
    if (!$encoder) return true; // notBlank validator on Encoder will fail separately
    $conditions = array(
      'EncoderTemplate.Encoder' => $encoder,
      'EncoderTemplate.Name' => $name,
    );
    if (!empty($this->id)) {
      $conditions['EncoderTemplate.Id !='] = $this->id;
    }
    return $this->find('count', array('conditions' => $conditions)) === 0;
  }
}
