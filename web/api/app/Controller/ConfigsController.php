<?php
App::uses('AppController', 'Controller');
/**
 * Configs Controller
 *
 * @property Config $Config
 */
class ConfigsController extends AppController {

/**
 * Components
 *
 * @var array
 */
	public $components = array('RequestHandler');

/**
 * resolves the issue of not returning all config parameters
 * refer https://github.com/ZoneMinder/ZoneMinder/issues/953
 * index method
 *
 * @return void
 */      
  public function index() {
    $this->Config->recursive = -1; // Configs have no associations anyways

    $conditions = [];
    if ( $this->request->params['named'] ) {
      $this->FilterComponent = $this->Components->load('Filter');
      $conditions = $this->FilterComponent->buildFilter($this->request->params['named']);
    }

    $configs = $this->Config->find('all', ['conditions' => &$conditions]);

    $config_by_name = [];
    foreach ($configs as $c) {
      $config_by_name[$c['Config']['Name']] = &$c['Config'];
    }

    global $zm_config;
    foreach ( $zm_config as $k=>$c ) {
      if (isset($config_by_name[$k])) {
        $config_by_name[$k]['Value'] = $c['Value'];
      } else if (!count($conditions)) {
        $configs[] = ['Config'=>$c];
      }
    }
    $this->set(array(
      'configs' => $configs,
      '_serialize' => array('configs')
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
		if (!$this->Config->exists($id)) {
			throw new NotFoundException(__('Invalid config'));
		}
		$options = array('conditions' => array('Config.' . $this->Config->primaryKey => $id));
		$config = $this->Config->find('first', $options);

    # Value might be overriden in /etc/zm/conf.d
    global $zm_config;
    $config['Config']['Value'] = $zm_config[$config['Config']['Name']]['Value'];
		$this->set(array(
			'config' => $config,
			'_serialize' => array('config')
		));
	}

	public function viewByName($name = null) {
		$config = $this->Config->findByName($name, array('fields' => 'Value'));

    global $zm_config;
		if (!$config) {
      global $zm_config;
      if ( isset($zm_config[$name]) ) {
        $config = array('Config'=>$zm_config[$name]);
      } else {
        ZM\Error(print_r($zm_config, true));
        throw new NotFoundException(__('Invalid config'));
      }
    } else if ( isset($zm_config[$name]) ) {
      $config['Config']['Value'] = $zm_config[$name]['Value'];
		}

		$this->set(array(
			'config' => $config['Config'],
			'_serialize' => array('config')
		));
	}

/**
 * edit method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function edit($id = null) {
		global $user;
		$canEdit = (!$user) || ($user['System'] == 'Edit');
		if (!$canEdit) {
			throw new UnauthorizedException(__('Insufficient privileges'));
			return;
		}

		$this->Config->id = $id;

		if (!$this->Config->exists($id)) {
			throw new NotFoundException(__('Invalid config'));
		}
		if ($this->request->is(array('post', 'put'))) {
			$options = array('conditions' => array('Config.' . $this->Config->primaryKey => $id));
			$config = $this->Config->find('first', $options);
			if (!empty($config['Config']['System'])) {
				throw new ForbiddenException(__('Cannot edit a system Config entry. Must be changed in /etc/zm/zm.conf'));
			}
			if (!empty($config['Config']['Readonly'])) {
				throw new ForbiddenException(__('Cannot edit a readonly Config entry'));
			}
			if ($this->Config->save($this->request->data)) {
				return $this->flash(__('The config has been saved.'), array('action' => 'index'));
			}
		} else {
			$options = array('conditions' => array('Config.' . $this->Config->primaryKey => $id));
			$this->request->data = $this->Config->find('first', $options);
		}
	}

/**
 * delete method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function delete($id = null) {
		global $user;
		$canEdit = (!$user) || ($user['System'] == 'Edit');
		if (!$canEdit) {
			throw new UnauthorizedException(__('Insufficient privileges'));
			return;
		}

		$this->Config->id = $id;
		if (!$this->Config->exists()) {
			throw new NotFoundException(__('Invalid config'));
		}
		$this->request->allowMethod('post', 'delete');
		if ($this->Config->delete()) {
			return $this->flash(__('The config has been deleted.'), array('action' => 'index'));
		} else {
			return $this->flash(__('The config could not be deleted. Please, try again.'), array('action' => 'index'));
		}
	}

/**
 * categories method
 *
 * return a list of distinct categories
 */

	public function categories($category = null) {
		$categories = $this->Config->find('all', array(
			'fields' => array('DISTINCT Config.Category'),
			'conditions' => array('Config.Category !=' => 'hidden'),
			'recursive' => 0
		));
		$this->set(array(
			'categories' => $categories,
			'_serialize' => array('categories')
		));
	}
}

