<?php
App::uses('AppController', 'Controller');

class EncoderTemplatesController extends AppController {

  public $components = array('Paginator', 'RequestHandler');

  public function beforeFilter() {
    parent::beforeFilter();
  }

  public function index() {
    $this->EncoderTemplate->recursive = -1;
    $conditions = array();
    if (!empty($this->request->query['Encoder'])) {
      $conditions['EncoderTemplate.Encoder'] = $this->request->query['Encoder'];
    }
    $templates = $this->EncoderTemplate->find('all', array(
      'conditions' => $conditions,
      'order' => array('EncoderTemplate.Encoder' => 'ASC', 'EncoderTemplate.Name' => 'ASC'),
    ));
    $this->set(array(
      'encoderTemplates' => $templates,
      '_serialize' => array('encoderTemplates'),
    ));
  }

  public function view($id = null) {
    $this->EncoderTemplate->recursive = -1;
    if (!$this->EncoderTemplate->exists($id)) {
      throw new NotFoundException(__('Invalid encoder template'));
    }
    $template = $this->EncoderTemplate->find('first', array(
      'conditions' => array('EncoderTemplate.Id' => $id),
    ));
    $this->set(array(
      'encoderTemplate' => $template,
      '_serialize' => array('encoderTemplate'),
    ));
  }

  public function add() {
    if ($this->request->is('post')) {
      global $user;
      $canEdit = (!$user) || ($user->System() == 'Edit');
      if (!$canEdit) {
        throw new UnauthorizedException(__('Insufficient privileges'));
      }
      $this->EncoderTemplate->create();
      if ($this->EncoderTemplate->save($this->request->data)) {
        $this->set(array(
          'message' => 'Saved',
          'id'      => $this->EncoderTemplate->id,
          '_serialize' => array('message', 'id'),
        ));
      } else {
        $this->response->statusCode(422);
        $this->set(array(
          'message' => 'Error',
          'errors'  => $this->EncoderTemplate->validationErrors,
          '_serialize' => array('message', 'errors'),
        ));
      }
    }
  }

  public function edit($id = null) {
    global $user;
    $canEdit = (!$user) || ($user->System() == 'Edit');
    if (!$canEdit) {
      throw new UnauthorizedException(__('Insufficient privileges'));
    }
    if (!$this->EncoderTemplate->exists($id)) {
      throw new NotFoundException(__('Invalid encoder template'));
    }
    $this->EncoderTemplate->id = $id;
    if ($this->EncoderTemplate->save($this->request->data)) {
      $this->set(array(
        'message' => 'Saved',
        '_serialize' => array('message'),
      ));
    } else {
      $this->response->statusCode(422);
      $this->set(array(
        'message' => 'Error',
        'errors'  => $this->EncoderTemplate->validationErrors,
        '_serialize' => array('message', 'errors'),
      ));
    }
  }

  public function delete($id = null) {
    global $user;
    $canEdit = (!$user) || ($user->System() == 'Edit');
    if (!$canEdit) {
      throw new UnauthorizedException(__('Insufficient privileges'));
    }
    $this->EncoderTemplate->id = $id;
    if (!$this->EncoderTemplate->exists()) {
      throw new NotFoundException(__('Invalid encoder template'));
    }
    $this->request->allowMethod('post', 'delete');
    if ($this->EncoderTemplate->delete()) {
      $this->set(array(
        'message' => 'Deleted',
        '_serialize' => array('message'),
      ));
    } else {
      $this->response->statusCode(500);
      $this->set(array(
        'message' => 'Error',
        '_serialize' => array('message'),
      ));
    }
  }
}
