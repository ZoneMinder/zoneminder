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
 * Reading configuration requires the same System permission as the web ui's
 * Options page. Without it any API-enabled account could read the effective
 * configuration, secrets included.
 */
  private function requireSystemView() {
    global $user;
    if ($user and ($user['System'] == 'None')) {
      throw new UnauthorizedException(__('Insufficient privileges'));
    }
  }

/**
 * Values that never leave the API, whatever the caller's permission: rows
 * flagged Private (ZM_AUTH_HASH_SECRET, enough to forge authentication for any
 * user, and the reCaptcha keys), and the database credentials, which come from
 * zm.conf ($configvals) and have no row to flag.
 */
  private function redactSecret($config) {
    $secret_names = array('ZM_DB_HOST', 'ZM_DB_NAME', 'ZM_DB_USER', 'ZM_DB_PASS', 'ZM_DB_TYPE');
    $name = isset($config['Name']) ? $config['Name'] : '';
    if (!empty($config['Private']) or in_array($name, $secret_names, true)) {
      $config['Value'] = '';
    }
    return $config;
  }

/**
 * resolves the issue of not returning all config parameters
 * refer https://github.com/ZoneMinder/ZoneMinder/issues/953
 * index method
 *
 * @return void
 */      
  public function index() {
    $this->requireSystemView();
    global $configvals;
    $this->Config->recursive = 0;
    $configs = $this->Config->find('all');
    foreach ( $configvals as $k=>$v ) {
      $configs[] = array( 'Config'=>array('Name'=>$k, 'Value'=>$v ) );
    }
    foreach ($configs as &$config) {
      $config['Config'] = $this->redactSecret($config['Config']);
    }
    unset($config);
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
		$this->requireSystemView();
		if (!$this->Config->exists($id)) {
			throw new NotFoundException(__('Invalid config'));
		}
		$options = array('conditions' => array('Config.' . $this->Config->primaryKey => $id));
		$config = $this->Config->find('first', $options);
		$config['Config'] = $this->redactSecret($config['Config']);
		$this->set(array(
			'config' => $config,
			'_serialize' => array('config')
		));
	}

	public function viewByName($name = null) {
		$this->requireSystemView();
		$config = $this->Config->findByName($name, array('Name', 'Value', 'Private'));

		if ( !$config ) {
      global $configvals;
      if ( isset($configvals[$name]) and $configvals[$name] ) {
        $config = array( 'Config'=>array('Name'=>$name, 'Value'=>$configvals[$name]) );
      } else {
        throw new NotFoundException(__('Invalid config'));
      }
    }
		// The response keeps its shape: just the Value.
		$redacted = $this->redactSecret($config['Config']);
		$config['Config'] = array('Value' => $redacted['Value']);

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
		$this->requireSystemView();
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

