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
 * Reading configuration requires the same System permission the web ui's
 * Options page requires. Without it any enabled, API-enabled account could read
 * the effective configuration, which includes secrets (GHSA-m896-3fc6-2jf5).
 * Mutation already required System Edit; this makes reads consistent with it.
 *
 * @throws UnauthorizedException
 * @return void
 */
  private function requireSystemView() {
    global $user;
    if ($user and ($user->System() == 'None')) {
      throw new UnauthorizedException(__('Insufficient privileges'));
    }
  }

/**
 * Values that must never leave the API, whatever the caller's permission.
 *
 * The Private flag marks the secrets that live in the Config table, such as
 * ZM_AUTH_HASH_SECRET, which is enough to forge an authentication hash for any
 * user. The database credentials are read from zm.conf and conf.d rather than
 * the table, so they have no row and no flag to carry, and are listed by name.
 * Nothing outside the server has any use for either, so they are withheld from
 * administrators too.
 *
 * The Private flag is read from $zm_config, which config.php populates from the
 * table, so callers need not select it and the response keeps its shape.
 *
 * @param array $config a Config array, optionally carrying Name and Private
 * @param string $name the config name, for callers whose row omits it
 * @return array the same array, with any secret Value emptied
 */
  private function redactSecret($config, $name = null) {
    $secret_names = array('ZM_DB_HOST', 'ZM_DB_NAME', 'ZM_DB_USER', 'ZM_DB_PASS', 'ZM_DB_TYPE');
    if ($name === null) $name = isset($config['Name']) ? $config['Name'] : '';

    global $zm_config;
    $private = !empty($config['Private'])
      || (isset($zm_config[$name]) && !empty($zm_config[$name]['Private']));

    if ($private or in_array($name, $secret_names, true)) {
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

    # Value might be overriden in /etc/zm/conf.d
    global $zm_config;
    $config['Config']['Value'] = $zm_config[$config['Config']['Name']]['Value'];
    $config['Config'] = $this->redactSecret($config['Config']);
		$this->set(array(
			'config' => $config,
			'_serialize' => array('config')
		));
	}

	public function viewByName($name = null) {
		$this->requireSystemView();
		$config = $this->Config->findByName($name, array('fields' => 'Value'));

    global $zm_config;
		if (!$config) {
      if ( isset($zm_config[$name]) ) {
        $config = array('Config'=>$zm_config[$name]);
      } else {
        # Logging $zm_config here copied the whole effective configuration,
        # secrets included, into the log and anything collecting it.
        ZM\Debug('No such config '.$name);
        throw new NotFoundException(__('Invalid config'));
      }
    } else if ( isset($zm_config[$name]) ) {
      $config['Config']['Value'] = $zm_config[$name]['Value'];
		}

		$this->set(array(
			'config' => $this->redactSecret($config['Config'], $name),
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
		$canEdit = (!$user) || ($user->System() == 'Edit');
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
		$canEdit = (!$user) || ($user->System() == 'Edit');
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

