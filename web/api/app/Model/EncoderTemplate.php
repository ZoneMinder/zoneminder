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
        'required' => 'create',
        'last' => true,
      ),
      'unique' => array(
        'rule' => array('isUniqueByEncoder'),
        'message' => 'A template with that name already exists for this encoder',
      ),
    ),
    'Params' => array(
      'notBlank' => array(
        'rule' => array('notBlank'),
        'message' => 'Params is required',
        'required' => 'create',
        'allowEmpty' => true,
      ),
    ),
    'Name' => array(
      'notBlank' => array(
        'rule' => array('notBlank'),
        'message' => 'Name is required',
        'required' => 'create',
        'last' => true,
      ),
      'unique' => array(
        'rule' => array('isUniqueByEncoder'),
        'message' => 'A template with that name already exists for this encoder',
      ),
    ),
  );

  // Custom validator: Name must be unique within the row's Encoder.
  // An edit may send only one of Encoder and Name, so the other comes from the stored row.
  public function isUniqueByEncoder($field) {
    $row = array();
    foreach (array('Encoder', 'Name') as $f) {
      $row[$f] = array_key_exists($f, $this->data[$this->alias])
        ? $this->data[$this->alias][$f]
        : (!empty($this->id) ? $this->field($f) : null);
    }
    if (!$row['Encoder']) return true; // notBlank validator on Encoder will fail separately
    if ($row['Name'] === null or $row['Name'] === '') return true;
    $conditions = array(
      'EncoderTemplate.Encoder' => $row['Encoder'],
      'EncoderTemplate.Name' => $row['Name'],
    );
    if (!empty($this->id)) {
      $conditions['EncoderTemplate.Id !='] = $this->id;
    }
    return $this->find('count', array('conditions' => $conditions)) === 0;
  }
}
