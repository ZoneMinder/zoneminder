<?php

App::uses('TranslationsShell', 'Crud.Console/Command');
App::uses('CakeRequest', 'Network');
App::uses('ConsoleInput', 'Console');
App::uses('ConsoleOutput', 'Console');
App::uses('Controller', 'Controller');

/**
 * TranslationsShellTest
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 */
class TranslationsShellTest extends CakeTestCase {

/**
 * setup test
 *
 * @return void
 */
	public function setUp() {
		$this->out = $this->getMock('ConsoleOutput', array(), array(), '', false);
		$this->in = $this->getMock('ConsoleInput', array(), array(), '', false);

		$this->Shell = $this->getMock(
			'TranslationsShell',
			array('in', 'out', 'hr', 'err', '_stop', '_getControllers', '_loadController'),
			array($this->out, $this->out, $this->in)
		);

		parent::setUp();
	}

/**
 * tearDown
 *
 * @return void
 */
	public function tearDown() {
		parent::tearDown();
		CakePlugin::unload('TestPlugin');
	}

/**
 * testGenerateTranslations
 *
 * With no controllers, nothing's going to happen
 *
 * @return void
 */
	public function testGenerateTranslations() {
		$method = new ReflectionMethod('TranslationsShell', '_processController');
		$method->setAccessible(true);
		$method->invoke($this->Shell, false);

		$expected = array();
		$this->assertSame($expected, $this->Shell->lines);
	}

/**
 * testGenerateTranslationsForAModel
 *
 * @return void
 */
	public function testGenerateTranslationsForAModel() {
		$controller = new Controller(new CakeRequest());
		$controller->Example = new StdClass(); // dummy
		$controller->Example->name = 'Example';
		$controller->modelClass = 'Example';
		$controller->components = array(
			'Crud.Crud' => array(
				'actions' => array(
					'index', 'add', 'edit', 'view', 'delete'
				)
			)
		);
		$controller->constructClasses();
		$controller->startupProcess();

		$this->Shell
			->expects($this->once())
			->method('_loadController')
			->will($this->returnValue($controller));

		$method = new ReflectionMethod('TranslationsShell', '_processController');
		$method->setAccessible(true);
		$method->invoke($this->Shell, 'Example');

		$expected = array(
			"",
			"/**",
			" * Example CRUD Component translations",
			" */",
			"__d('crud', 'Invalid id');",
			"__d('crud', 'Not found');",
			"__d('crud', 'Method not allowed. This action permits only {methods}');",
			"__d('crud', 'Successfully created example');",
			"__d('crud', 'Could not create example');",
			"__d('crud', 'Successfully updated example');",
			"__d('crud', 'Could not update example');",
			"__d('crud', 'Successfully deleted example');",
			"__d('crud', 'Could not delete example');"
		);
		$this->assertSame($expected, $this->Shell->lines);
	}

/**
 * testGenerateTranslationsForAModel
 *
 * @return void
 */
	public function testGenerateTranslationsForAModelActionDomain() {
		$controller = new Controller(new CakeRequest());
		$controller->Example = new StdClass(); // dummy
		$controller->Example->name = 'Example';
		$controller->modelClass = 'Example';
		$controller->components = array(
			'Crud.Crud' => array(
				'actions' => array(
					'index', 'add', 'edit', 'view', 'delete'
				)
			)
		);
		$controller->constructClasses();
		$controller->startupProcess();
		$controller->Crud->config('messages.domain', 'my');

		$this->Shell
			->expects($this->once())
			->method('_loadController')
			->will($this->returnValue($controller));

		$method = new ReflectionMethod('TranslationsShell', '_processController');
		$method->setAccessible(true);
		$method->invoke($this->Shell, 'Example');

		$expected = array(
			"",
			"/**",
			" * Example CRUD Component translations",
			" */",
			"__d('my', 'Invalid id');",
			"__d('my', 'Not found');",
			"__d('my', 'Method not allowed. This action permits only {methods}');",
			"__d('my', 'Successfully created example');",
			"__d('my', 'Could not create example');",
			"__d('my', 'Successfully updated example');",
			"__d('my', 'Could not update example');",
			"__d('my', 'Successfully deleted example');",
			"__d('my', 'Could not delete example');"
		);
		$this->assertSame($expected, $this->Shell->lines);
	}

	public function testGenerateFile() {
		$controller = new Controller(new CakeRequest());
		$controller->Example = new StdClass(); // dummy
		$controller->Example->name = 'Example';
		$controller->modelClass = 'Example';
		$controller->components = array(
			'Crud.Crud' => array(
				'actions' => array(
					'index', 'add', 'edit', 'view', 'delete'
				)
			)
		);
		$controller->constructClasses();
		$controller->startupProcess();

		$this->Shell
			->expects($this->once())
			->method('_loadController')
			->will($this->returnValue($controller));

		$this->Shell
			->expects($this->once())
			->method('_getControllers')
			->will($this->returnValue(array('Example')));

		$path = TMP . 'crud_translations_shell_test.php';
		if (file_exists($path)) {
			unlink($path);
		}
		$this->Shell->path($path);
		$this->Shell->generate();

		$this->assertFileExists($path);

		$contents = file_get_contents($path);
		$expected = <<<END
<?php

/**
 * Example CRUD Component translations
 */
__d('crud', 'Invalid id');
__d('crud', 'Not found');
__d('crud', 'Method not allowed. This action permits only {methods}');
__d('crud', 'Successfully created example');
__d('crud', 'Could not create example');
__d('crud', 'Successfully updated example');
__d('crud', 'Could not update example');
__d('crud', 'Successfully deleted example');
__d('crud', 'Could not delete example');
END;

		$this->assertTextEquals(trim($expected), trim($contents));

		unlink($path);
	}

/**
 * testGenerateFileFileExists
 *
 * Running the shell should only add missing translations,
 * Without removing or corrupting existing translations.
 *
 * @return void
 */
	public function testGenerateFileFileExists() {
		$expected = <<<END
<?php

/**
 * Example CRUD Component translations
 */
__d('crud', 'Some other translation');
__d('crud', 'Invalid id');
__d('crud', 'Not found');
__d('crud', 'Method not allowed. This action permits only {methods}');
__d('crud', 'Successfully created example');
__d('crud', 'Could not create example');
__d('crud', 'Successfully updated example');
__d('crud', 'Could not update example');
__d('crud', 'Successfully deleted example');
END;

		$path = TMP . 'crud_translations_shell_test.php';
		file_put_contents($path, $expected);

		$controller = new Controller(new CakeRequest());
		$controller->Example = new StdClass(); // dummy
		$controller->Example->name = 'Example';
		$controller->modelClass = 'Example';
		$controller->components = array(
			'Crud.Crud' => array(
				'actions' => array(
					'index', 'add', 'edit', 'view', 'delete'
				)
			)
		);
		$controller->constructClasses();
		$controller->startupProcess();

		$this->Shell
			->expects($this->once())
			->method('_loadController')
			->will($this->returnValue($controller));

		$this->Shell
			->expects($this->once())
			->method('_getControllers')
			->will($this->returnValue(array('Example')));

		$this->Shell->path($path);
		$this->Shell->generate();

		$this->assertFileExists($path);

		$onlyNewTranslation = "\n__d('crud', 'Could not delete example');";
		$expected .= $onlyNewTranslation;
		$contents = file_get_contents($path);

		$this->assertTextEquals(trim($expected), trim($contents), "Only expected one translation to be added");

		unlink($path);
	}

/**
 * testGetControllersDefault
 *
 * Verify that it returns a list of controller names without the Controller suffix
 * When called with no args, should return the app controller names
 *
 * @return void
 */
	public function testGetControllersDefault() {
		$class = new ReflectionClass('TranslationsShell');
		$method = $class->getMethod('_getControllers');
		$method->setAccessible(true);

		$this->Shell = $this->getMock(
			'TranslationsShell',
			array('in', 'out', 'hr', 'err', '_stop', '_loadController'),
			array($this->out, $this->out, $this->in)
		);

		$path = CAKE . 'Test' . DS . 'test_app' . DS . 'Controller' . DS;
		App::build(array(
			'Controller' => array($path)
		), App::RESET);

		$expected = array(
			'Pages',
			'TestAppsError',
			'TestConfigs',
			'TestsApps',
			'TestsAppsPosts'
		);
		if (!file_exists($path . 'TestConfigsController.php')) {
			unset($expected[2]);
			$expected = array_values($expected);
		}
		$controllers = $method->invoke($this->Shell);
		$this->assertSame($expected, $controllers);
	}

/**
 * testGetControllersJunk
 *
 * @return void
 */
	public function testGetControllersJunk() {
		$class = new ReflectionClass('TranslationsShell');
		$method = $class->getMethod('_getControllers');
		$method->setAccessible(true);

		$this->Shell = $this->getMock(
			'TranslationsShell',
			array('in', 'out', 'hr', 'err', '_stop', '_loadController'),
			array($this->out, $this->out, $this->in)
		);

		$args = array(
			'not a path'
		);

		$expected = array();
		$controllers = $method->invoke($this->Shell, $args);
		$this->assertSame($expected, $controllers);
	}

/**
 * testGetControllersAppNamed
 *
 * If the file paths to app controllers are passed - should be honored
 *
 * @return void
 */
	public function testGetControllersAppNamed() {
		$class = new ReflectionClass('TranslationsShell');
		$method = $class->getMethod('_getControllers');
		$method->setAccessible(true);

		$this->Shell = $this->getMock(
			'TranslationsShell',
			array('in', 'out', 'hr', 'err', '_stop', '_loadController'),
			array($this->out, $this->out, $this->in)
		);

		$args = array(
			'Controller/ThisController.php',
			'Controller/ThatController.php',
			'Controller/OtherController.php',
		);

		$expected = array(
			'This',
			'That',
			'Other'
		);
		$controllers = $method->invoke($this->Shell, $args);
		$this->assertSame($expected, $controllers);
	}

/**
 * testGetControllersPlugin
 *
 * Passing the path to a plugin should process all controllers in that plugin
 *
 * @return void
 */
	public function testGetControllersPlugin() {
		$class = new ReflectionClass('TranslationsShell');
		$method = $class->getMethod('_getControllers');
		$method->setAccessible(true);

		$this->Shell = $this->getMock(
			'TranslationsShell',
			array('in', 'out', 'hr', 'err', '_stop', '_loadController'),
			array($this->out, $this->out, $this->in)
		);

		$this->Shell->args = array(
			CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS . 'TestPlugin'
		);

		$path = CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS;
		App::build(array(
			'Plugin' => array($path)
		), App::RESET);
		CakePlugin::load('TestPlugin');

		$expected = array(
			'TestPlugin.TestPlugin',
			'TestPlugin.Tests'
		);
		$controllers = $method->invoke($this->Shell, array($path . 'TestPlugin'));
		$this->assertSame($expected, $controllers);
	}

/**
 * testGetControllersPluginNamed
 *
 * Passing the path to a single plugin controller should return that controller
 *
 * @return void
 */
	public function testGetControllersPluginNamed() {
		$class = new ReflectionClass('TranslationsShell');
		$method = $class->getMethod('_getControllers');
		$method->setAccessible(true);

		$this->Shell = $this->getMock(
			'TranslationsShell',
			array('in', 'out', 'hr', 'err', '_stop', '_loadController'),
			array($this->out, $this->out, $this->in)
		);

		$this->Shell->args = array(
			CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS . 'TestPlugin'
		);

		$path = CAKE . 'Test' . DS . 'test_app' . DS . 'Plugin' . DS;
		App::build(array(
			'Plugin' => array($path)
		), App::RESET);
		CakePlugin::load('TestPlugin');

		$expected = array(
			'TestPlugin.TestPlugin'
		);

		$path .= 'TestPlugin/Controller/TestPluginController.php';
		$controllers = $method->invoke($this->Shell, array($path));
		$this->assertSame($expected, $controllers);
	}
}
