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
    if ($this->request->params['named']) {
      $this->FilterComponent = $this->Components->load('Filter');
      $conditions = $this->FilterComponent->buildFilter($this->request->params['named'], array_keys($this->EncoderTemplate->schema()));
    }
    if (!empty($this->request->query['Encoder'])) {
      // A name or a list of names; a list inside the list would be written into the SQL as the word Array.
      foreach ((array)$this->request->query['Encoder'] as $name) {
        if (is_array($name)) {
          throw new BadRequestException(__('Encoder must be a name or a list of names'));
        }
      }
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
    if (!$this->request->is('post')) {
      throw new BadRequestException(__('Invalid method. Should be post'));
    }
    if ($this->request->is('post')) {
      global $user;
      $canEdit = (!$user) || ($user->System() == 'Edit');
      if (!$canEdit) {
        throw new UnauthorizedException(__('Insufficient privileges'));
      }
      $this->pinRequestId($this->EncoderTemplate, null);
      $this->EncoderTemplate->create();
      if ($this->EncoderTemplate->save($this->request->data)) {
        $this->set(array(
          'message' => 'Saved',
          'id'      => $this->EncoderTemplate->id,
          '_serialize' => array('message', 'id'),
        ));
      } else {
        $this->response->httpCodes(array(422 => 'Unprocessable Entity'));
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
    $this->request->allowMethod('post', 'put');
    $this->pinRequestId($this->EncoderTemplate, $id);
    if ($this->EncoderTemplate->save($this->request->data)) {
      $this->set(array(
        'message' => 'Saved',
        '_serialize' => array('message'),
      ));
    } else {
      $this->response->httpCodes(array(422 => 'Unprocessable Entity'));
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
