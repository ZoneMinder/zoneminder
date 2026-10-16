<?php
App::uses('AppController', 'Controller');
/**
 * Groups Controller
 *
 * @property Group $Group
 * @property PaginatorComponent $Paginator
 */
class GroupsController extends AppController {
/**
 * Components
 *
 * @var array
 */
	public $components = array('Paginator', 'RequestHandler');

  public function beforeFilter() {
    parent::beforeFilter();
    global $user;
    # We already tested for auth in appController, so we just need to test for specific permission
    $canView = (!$user) || ($user->Groups() != 'None');
    if ( !$canView ) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
      return;
    }
    require_once __DIR__ .'/../../../includes/Group.php';
  }

  # Group membership decides per-monitor access, so require edit on every monitor moved.
  private function requireMembershipEdit($monitorIds) {
    if (!ZM\Group::canEditMembership(array_map('intval', $monitorIds))) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
    }
  }

  # Drop monitors the user may not view from each group's Monitor list.
  private function filterGroupMonitors($groups) {
    $condition = $this->viewableMonitorCondition('Monitor.Id');
    if (!count($condition)) return $groups;
    $allowed = $condition['Monitor.Id'];
    foreach ($groups as &$group) {
      if (!isset($group['Monitor'])) continue;
      $group['Monitor'] = array_values(array_filter($group['Monitor'], function($m) use ($allowed) {
        return in_array($m['Id'], $allowed);
      }));
    }
    return $groups;
  }

/**
 * index method
 *
 * @return void
 */
	public function index() {
		$this->Group->recursive = 0;

    if ( $this->request->params['named'] ) {
      $this->FilterComponent = $this->Components->load('Filter');
      $conditions = $this->FilterComponent->buildFilter($this->request->params['named']);
    } else {
      $conditions = array();
    }

    $find_array = array(
      'conditions' => &$conditions,
      'contain'    => array('Monitor'),
      'joins'      => array(
        array(
          'table' => 'Groups_Monitors',
          'type'  => 'left',
          'conditions' => array(
            'Groups_Monitors.GroupId = Group.Id',
          ),
        ),
      ),
      'group' => '`Group`.`Id`',
    );

		$groups = $this->filterGroupMonitors($this->Group->find('all', $find_array));
		$this->set(array(
			'groups' => $groups,
			'_serialize' => array('groups')
		));
	}

/**
 * view method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function view($id = null) {
		$this->Group->recursive = -1;
		if (!$this->Group->exists($id)) {
			throw new NotFoundException(__('Invalid group'));
		}
		$options = array('conditions' => array('Group.' . $this->Group->primaryKey => $id));
		$group = $this->Group->find('first', $options);
		$this->set(array(
			'group' => $group,
			'_serialize' => array('group')
		));
	}

/**
 * add method
 *
 * @return void
 */
	public function add() {
		if ( $this->request->is('post') ) {

      global $user;
      # We already tested for auth in appController,
      # so we just need to test for specific permission
      $canEdit = (!$user) || ($user->Groups() == 'Edit');
      if ( !$canEdit ) {
        throw new UnauthorizedException(__('Insufficient Privileges'));
        return;
      }

      $monitorIds = $this->requestAssociatedIds('Group', 'MonitorIds', 'Monitor');
      if ($monitorIds) $this->requireMembershipEdit($monitorIds);
      $this->pinRequestId($this->Group, null);
			$this->Group->create();

      if ( $this->request->data['Group']['MonitorIds'] and ! isset($this->request->data['Monitor']) ) {
        $this->request->data['Monitor'] = explode(',', $this->request->data['Group']['MonitorIds']);
        unset($this->request->data['Group']['MonitorIds']);
      }
      if ( $this->Group->saveAssociated($this->request->data, array('atomic'=>true)) ) {
        return $this->flash(
          __('The group has been saved.'),
          array('action' => 'index')
        );
      } else {
        ZM\Error("Failed to save Group");
        debug($this->Group->invalidFields());
      }
    } # end if post
    $monitors = $this->Group->Monitor->find('list');
		$this->set(compact('monitors'));
	} # end add

/**
 * edit method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function edit( $id = null ) {
		if ( !$this->Group->exists($id) ) {
			throw new NotFoundException(__('Invalid group'));
		}
		if ( $this->request->is(array('post', 'put'))) {
      global $user;
      # We already tested for auth in appController,
      # so we just need to test for specific permission
      $canEdit = (!$user) || ($user->Groups() == 'Edit');
      if ( !$canEdit ) {
        throw new UnauthorizedException(__('Insufficient Privileges'));
        return;
      }
      $this->pinRequestId($this->Group, $id);
      $group = new ZM\Group($id);
      $moved = array();
      $newIds = $this->requestAssociatedIds('Group', 'MonitorIds', 'Monitor');
      if ($newIds !== null) {
        $oldIds = dbFetchAll('SELECT `MonitorId` FROM `Groups_Monitors` WHERE `GroupId`=?', 'MonitorId', array($id));
        $moved = array_merge(array_diff($oldIds, $newIds), array_diff($newIds, $oldIds));
      }
      $parentId = $this->requestField('Group', 'ParentId');
      # Re-parenting moves every monitor of this group and its children between ancestors.
      if (($parentId !== null) and ($parentId != $group->ParentId())) {
        $moved = array_merge($moved, $group->MonitorIds());
      }
      $this->requireMembershipEdit($moved);
			if ( $this->Group->save($this->request->data) ) {
        $message = 'Saved';
      } else {
        $message = 'Error';
        // if there is a validation message, use it
        if ( !$this->group->validates() ) {
          $message .= ': '.$this->Group->validationErrors;
        }
			}
		} # end if post/put

		$group = $this->Group->findById($id);
		$this->set(array(
			'message' => $message,
			'group' => $group,
			'_serialize' => array('group')
		));
	}

/**
 * delete method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function delete($id = null) {
		$this->Group->id = $id;
		if ( !$this->Group->exists() ) {
			throw new NotFoundException(__('Invalid group'));
		}
		$this->request->allowMethod('post', 'delete');

    global $user;
    # We already tested for auth in appController,
    # so we just need to test for specific permission
    $canEdit = (!$user) || ($user->Groups() == 'Edit');
    if ( !$canEdit ) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
      return;
    }
    # Deleting a group removes its monitors from it, which can lift a deny on them.
    $group = new ZM\Group($id);
    $this->requireMembershipEdit($group->MonitorIds());

		if ( $this->Group->delete() ) {
      return $this->flash(
        __('The group has been deleted.'),
        array('action' => 'index')
      );
		} else {
      return $this->flash(
        __('The group could not be deleted. Please, try again.'),
        array('action' => 'index')
      );
		}
  } // end function delete
  
  // returns monitor associations
  public function associations() {
    $this->Group->recursive = -1;
    $groups = $this->Group->find('all', array(
                                        'contain'=> array(
                                          'Monitor' => array(
                                            'fields'=>array('Id','Name')
                                          )
                                        )
                                      )
                                );
            $groups = $this->filterGroupMonitors($groups);
            $this->set(array(
                    'groups' => $groups,
                    '_serialize' => array('groups')
            ));
  } // end associations

} // end class GroupController
