<?php

App::uses('AppShell', 'Console/Command');
App::uses('CrudSubject', 'Crud.Controller/Crud');
App::uses('TranslationsListener', 'Crud.Controller/Crud/Listener');

/**
 * TranslationsShell
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 *
 */
class TranslationsShell extends AppShell {

/**
 * The array of raw stings to be written to the output file
 *
 * @var array
 */
	public $lines = array();

/**
 * The path to write the output file to
 *
 * @var string
 */
	protected $_path = '';

/**
 * Gets the option parser instance and configures it.
 * By overriding this method you can configure the ConsoleOptionParser before returning it.
 *
 * @return ConsoleOptionParser
 * @link http://book.cakephp.org/2.0/en/console-and-shells.html#Shell::getOptionParser
 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();
		return $parser
			->addSubCommand('generate', array(
				'help' => 'Generate the translation strings for CRUD component usage'
			));
	}

/**
 * Create or update the file containing the translation strings for CRUD component usage
 *
 * @return void
 */
	public function generate() {
		$controllers = $this->_getControllers($this->args);
		if (!$controllers) {
			$this->out('<warning>No controllers found to be processed</warning>');
			return;
		}

		$this->hr();
		$this->out(sprintf('Processing translation strings for controllers: %s.', implode($controllers, ', ')));
		$this->out('');

		$path = $this->path();

		if (file_exists($path)) {
			$this->lines = array_map('rtrim', file($path));
		} else {
			$this->lines[] = '<?php';
		}

		foreach ($controllers as $name) {
			$this->_processController($name);
		}

		return $this->_writeFile();
	}

/**
 * Add a doc block to the lines property with the passed message appropriately formatted
 * If the doc block already exists - return false
 *
 * @param string $message
 * @return boolean Success
 */
	protected function _addDocBlock($message) {
		$message = " * $message";

		if (in_array($message, $this->lines)) {
			return false;
		}

		$this->lines[] = '';
		$this->lines[] = '/**';
		$this->lines[] = $message;
		$this->lines[] = ' */';
		return true;
	}

/**
 * If no arguments are passed to the cli call, return all App controllers
 * Otherwise, assume the arguments are a list of file paths to plugin model dirs or an individual plugin model
 *
 * @param array $args File paths to controllers to process
 * @return array
 */
	protected function _getControllers($args = array()) {
		$objectType = 'Controller';
		$controllers = array();

		if ($args) {
			foreach ($args as $arg) {
				$plugin = $controller = null;
				preg_match('@Plugin/([^/]+)@', $arg, $match);

				if ($match) {
					$plugin = $match[1];
				}

				preg_match('@Controller/([^/]+)@', $arg, $match);
				if ($match) {
					$controller = $match[1];
				}

				if (!$plugin && !$controller) {
					$this->out("<info>Skipping argument:</info> $arg", 1, Shell::VERBOSE);
					continue;
				}

				if ($plugin) {
					if ($controller) {
						$controllers[] = $plugin . '.' . $controller;
					} else {
						$pluginControllers = App::objects("$plugin.Controller");
						foreach ($pluginControllers as &$c) {
							$c = "$plugin.$c";
						}
						$controllers = array_merge($controllers, $pluginControllers);
					}
				} else {
					$controllers[] = $controller;
				}
			}
		} else {
			$controllers = App::objects('Controller');
		}

		foreach ($controllers as $i => &$controller) {
			$controller = preg_replace('/Controller(\.php)?$/', '', $controller);

			if (preg_match('/^(?:(\w+)\.\1)?App$/', $controller)) {
				unset($controllers[$i]);
			}
		}

		return array_values($controllers);
	}

/**
 * Set or retrieve the path to write the output file to
 * Defaults to APP/Config/i18n_crud.php
 *
 * @param string $path
 * @return string
 */
	public function path($path = null) {
		if ($path) {
			$this->_path = $path;
		} elseif (!$this->_path) {
			$this->_path = APP . 'Config' . DS . 'i18n_crud.php';
		}
		return $this->_path;
	}

/**
 * Get controller instance
 *
 * @param string $name Controller name
 * @param string $plugin Plugin name
 * @return Controller
 * @codeCoverageIgnore
 */
	protected function _loadController($name, $plugin) {
		$className = $name . 'Controller';
		$prefix = rtrim($plugin, '.');

		App::uses($className, $plugin . 'Controller');

		if (!class_exists($className)) {
			$this->out("<info>Skipping:</info> $className, class could not be loaded", 1, Shell::VERBOSE);
			return;
		}

		$request = new CakeRequest();
		$Controller = new $className($request);
		$Controller->constructClasses();
		$Controller->startupProcess();

		if (!$Controller->uses) {
			$this->out("<info>Skipping:</info> $className, doesn't use any models", 1, Shell::VERBOSE);
			return;
		}

		if (!isset($Controller->Crud)) {
			$this->out("<info>Skipping:</info> $className, doesn't use Crud component", 1, Shell::VERBOSE);
			return;
		}

		return $Controller;
	}

/**
 * For the given controller name, initialize the crud component and process each action.
 * Create a listener for the setFlash event to log the flash message details.
 *
 * @param string $name Controller name
 */
	protected function _processController($name) {
		list($plugin, $name) = pluginSplit($name, true);
		$prefix = rtrim($plugin, '.');

		$Controller = $this->_loadController($name, $plugin);

		if (!$Controller) {
			return;
		}

		$this->_addDocBlock("$name CRUD Component translations");

		$actions = array_keys($Controller->Crud->config('actions'));
		foreach ($actions as $actionName) {
			$this->_processAction($actionName, $Controller);
		}
	}

/**
 * Process a single crud action. Initialize the action object, and trigger each
 * flash message.
 *
 * @param string $actionName crud action name
 * @param Controller $Controller instance
 */
	protected function _processAction($actionName, $Controller) {
		try {
			$Controller->Crud->action($actionName);
			$Controller->Crud->trigger('beforeHandle');
		} catch(Exception $e) {
			return;
		}

		$action = $Controller->Crud->action($actionName);

		$messages = (array)$Controller->Crud->config('messages') + (array)$action->config('messages');
		if (!$messages) {
			return;
		}

		foreach (array_keys($messages) as $type) {
			if ($type === 'domain') {
				continue;
			}
			$message = $action->message($type);
			$this->_processMessage($message, $action, $Controller->Crud);
		}
	}

/**
 * Generates translation statement string and adds to lines property
 *
 * @param mixed $message
 * @param mixed $action
 * @param mixed $crud
 */
	protected function _processMessage($message, $action, $crud) {
		$text = $message['params']['original'];
		if (!$text) {
			return;
		}

		$domain = $action->config('messages.domain');
		if (!$domain) {
			$domain = $crud->config('messages.domain') ?: 'crud';
		}

		$string = "__d('$domain', '$text');";

		if (in_array($string, $this->lines)) {
			$this->out('<info>Skipping:</info> ' . $text, 1, Shell::VERBOSE);
		} else {
			$this->out('<success>Adding:</success> ' . $text);
			$this->lines[] = $string;
		}
	}

/**
 * Take the lines property, populated by the generate method - and write it
 * out to the output file path
 *
 * @return string the file path written to
 */
	protected function _writeFile() {
		$path = $this->path();

		$lines = implode($this->lines, "\n") . "\n";
		$file = new File($path, true, 0644);
		$file->write($lines);

		$this->out(str_replace('APP', '', $path) . ' updated');
		$this->hr();

		return $path;
	}
}
