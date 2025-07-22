<?php
App::uses('AppController', 'Controller');
/**
 * Tags Controller
 *
 * @property Tag $Tag
 * @property PaginatorComponent $Paginator
 */
class TagsController extends AppController {
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
    $canView = (!$user) || ($user->Events() != 'None');
    if ( !$canView ) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
      return;
    }
  }

/**
 * index method
 *
 * @return void
 */
	public function index() {
		$this->Tag->recursive = -1;

    if ( $this->request->params['named'] ) {
      $this->FilterComponent = $this->Components->load('Filter');
      $conditions = $this->FilterComponent->buildFilter($this->request->params['named']);
    } else {
      $conditions = array();
    }

    $find_array = array(
      'conditions' => &$conditions,
      'contain'    => array('Event'),
      'joins'      => array(
        array(
          'table' => 'Events_Tags',
          'type'  => 'left',
          'conditions' => array(
            'Events_Tags.TagId = Tag.Id',
          ),
        ),
      ),
      'tag' => '`Tag`.`Id`',
    );

		$tags = $this->Tag->find('all', $find_array);
		$this->set(array(
			'tags' => $tags,
			'_serialize' => array('tags')
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
		$this->Tag->recursive = -1;
		if (!$this->Tag->exists($id)) {
			throw new NotFoundException(__('Invalid tag'));
		}
		$options = array('conditions' => array('Tag.' . $this->Tag->primaryKey => $id));
		$tag = $this->Tag->find('first', $options);
		$this->set(array(
			'tag' => $tag,
			'_serialize' => array('tag')
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
      $canEdit = (!$user) || ($user->Tags() == 'Edit');
      if ( !$canEdit ) {
        throw new UnauthorizedException(__('Insufficient Privileges'));
        return;
      }

			$this->Tag->create();

      if ( $this->request->data['Tag']['EventIds'] and ! isset($this->request->data['Event']) ) {
        $this->request->data['Event'] = explode(',', $this->request->data['Tag']['EventIds']);
        unset($this->request->data['Tag']['EventIds']);
      }
      if ( $this->Tag->saveAssociated($this->request->data, array('atomic'=>true)) ) {
        return $this->flash(
          __('The tag has been saved.'),
          array('action' => 'index')
        );
      } else {
        ZM\Error("Failed to save Tag");
        debug($this->Tag->invalidFields());
      }
    } # end if post
    $monitors = $this->Tag->Event->find('list');
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
		if ( !$this->Tag->exists($id) ) {
			throw new NotFoundException(__('Invalid tag'));
		}
		if ( $this->request->is(array('post', 'put'))) {
      global $user;
      # We already tested for auth in appController,
      # so we just need to test for specific permission
      $canEdit = (!$user) || ($user->Tags() == 'Edit');
      if ( !$canEdit ) {
        throw new UnauthorizedException(__('Insufficient Privileges'));
        return;
      }
      $this->Tag->id = $id;
			if ( $this->Tag->save($this->request->data) ) {
        $message = 'Saved';
      } else {
        $message = 'Error';
        // if there is a validation message, use it
        if ( !$this->tag->validates() ) {
          $message .= ': '.$this->Tag->validationErrors;
        }
			}
		} # end if post/put

		$tag = $this->Tag->findById($id);
		$this->set(array(
			'message' => $message,
			'tag' => $tag,
			'_serialize' => array('tag')
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
		$this->Tag->id = $id;
		if ( !$this->Tag->exists() ) {
			throw new NotFoundException(__('Invalid tag'));
		}
		$this->request->allowMethod('post', 'delete');

    global $user;
    # We already tested for auth in appController,
    # so we just need to test for specific permission
    $canEdit = (!$user) || ($user->Tags() == 'Edit');
    if ( !$canEdit ) {
      throw new UnauthorizedException(__('Insufficient Privileges'));
      return;
    }

		if ( $this->Tag->delete() ) {
      return $this->flash(
        __('The tag has been deleted.'),
        array('action' => 'index')
      );
		} else {
      return $this->flash(
        __('The tag could not be deleted. Please, try again.'),
        array('action' => 'index')
      );
		}
  } // end function delete
  
  // returns monitor associations
  public function associations() {
    $this->Tag->recursive = -1;
    $tags = $this->Tag->find('all', array(
                                        'contain'=> array(
                                          'Event' => array(
                                            'fields'=>array('Id','Name')
                                          )
                                        )
                                      )
                                );
            $this->set(array(
                    'tags' => $tags,
                    '_serialize' => array('tags')
            ));
  } // end associations

} // end class TagController
