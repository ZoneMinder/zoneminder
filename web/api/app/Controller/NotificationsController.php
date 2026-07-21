<?php
App::uses('AppController', 'Controller');

class NotificationsController extends AppController {

  public $components = array('RequestHandler');

  private function _isAdmin() {
    global $user;
    return (!$user) || ($user->System() == 'Edit');
  }

  private function _userId() {
    global $user;
    return $user ? $user->Id() : null;
  }

  public function beforeFilter() {
    parent::beforeFilter();
    // Any authenticated user can manage their own notifications.
    // Per-row ownership checks are enforced in each action.
    // When auth is disabled ($user is null), allow all access.
  }

  public function index() {
    $conditions = array();
    if (!$this->_isAdmin()) {
      $conditions['Notification.UserId'] = $this->_userId();
    }
    $notifications = $this->Notification->find('all', array(
      'conditions' => $conditions,
      'recursive' => -1,
    ));
    $this->set(array(
      'notifications' => $notifications,
      '_serialize' => array('notifications'),
    ));
  }

  public function view($id = null) {
    $this->Notification->id = $id;
    if (!$this->Notification->exists()) {
      throw new NotFoundException(__('Invalid notification'));
    }
    $notification = $this->Notification->find('first', array(
      'conditions' => array('Notification.Id' => $id),
      'recursive' => -1,
    ));
    if (!$this->_isAdmin() && $notification['Notification']['UserId'] != $this->_userId()) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
    }
    $this->set(array(
      'notification' => $notification,
      '_serialize' => array('notification'),
    ));
  }

  public function add() {
    if (!$this->request->is('post')) {
      throw new BadRequestException(__('POST required'));
    }

    $data = $this->request->data;
    if (isset($data['Notification'])) {
      $data = $data['Notification'];
    }

    if (!$this->_isAdmin() || !isset($data['UserId'])) {
      $data['UserId'] = $this->_userId();
    }

    if (!isset($data['CreatedOn'])) {
      $data['CreatedOn'] = date('Y-m-d H:i:s');
    }

    if (isset($data['Token'])) {
      $existing = $this->Notification->find('first', array(
        'conditions' => array('Notification.Token' => $data['Token']),
        'recursive' => -1,
      ));
      if ($existing) {
        if (!$this->_isAdmin() && $existing['Notification']['UserId'] != $this->_userId()) {
          throw new UnauthorizedException(__('Token belongs to another user'));
        }
        $this->Notification->id = $existing['Notification']['Id'];
        unset($data['CreatedOn']);
      } else {
        $this->Notification->create();
      }
    } else {
      $this->Notification->create();
    }

    if ($this->Notification->save(array('Notification' => $data))) {
      $notification = $this->Notification->find('first', array(
        'conditions' => array('Notification.Id' => $this->Notification->id),
        'recursive' => -1,
      ));
      $this->set(array(
        'notification' => $notification,
        '_serialize' => array('notification'),
      ));
    } else {
      $this->response->statusCode(400);
      $this->set(array(
        'message' => __('Could not save notification'),
        'errors' => $this->Notification->validationErrors,
        '_serialize' => array('message', 'errors'),
      ));
    }
  }

  public function edit($id = null) {
    $this->Notification->id = $id;
    if (!$this->Notification->exists()) {
      throw new NotFoundException(__('Invalid notification'));
    }
    $this->request->allowMethod('post', 'put');

    $existing = $this->Notification->find('first', array(
      'conditions' => array('Notification.Id' => $id),
      'recursive' => -1,
    ));
    if (!$this->_isAdmin() && $existing['Notification']['UserId'] != $this->_userId()) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
    }

    $data = $this->request->data;
    if (isset($data['Notification'])) {
      $data = $data['Notification'];
    }
    if (!$this->_isAdmin()) {
      unset($data['UserId']);
    }
    if ($this->Notification->save(array('Notification' => $data))) {
      $notification = $this->Notification->find('first', array(
        'conditions' => array('Notification.Id' => $id),
        'recursive' => -1,
      ));
      $this->set(array(
        'notification' => $notification,
        '_serialize' => array('notification'),
      ));
    } else {
      $this->response->statusCode(400);
      $this->set(array(
        'message' => __('Could not save notification'),
        'errors' => $this->Notification->validationErrors,
        '_serialize' => array('message', 'errors'),
      ));
    }
  }

  public function delete($id = null) {
    $this->Notification->id = $id;
    if (!$this->Notification->exists()) {
      throw new NotFoundException(__('Invalid notification'));
    }
    $this->request->allowMethod('post', 'delete');

    $existing = $this->Notification->find('first', array(
      'conditions' => array('Notification.Id' => $id),
      'recursive' => -1,
    ));
    if (!$this->_isAdmin() && $existing['Notification']['UserId'] != $this->_userId()) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
    }

    if ($this->Notification->delete()) {
      $this->set(array(
        'message' => __('Notification deleted'),
        '_serialize' => array('message'),
      ));
    } else {
      $this->response->statusCode(400);
      $this->set(array(
        'message' => __('Could not delete notification'),
        '_serialize' => array('message'),
      ));
    }
  }

}
