<?php
App::uses('Router', 'Routing');
App::uses('CakeRequest', 'Network');
App::uses('CakeResponse', 'Network');
App::uses('Controller', 'Controller');

App::uses('CakeEventManager', 'Event');

App::uses('ComponentCollection', 'Controller');
App::uses('Component', 'Controller');
App::uses('CrudComponent', 'Crud.Controller/Component');
App::uses('CrudListener', 'Crud.Controller/Crud');
App::uses('CrudControllerTestCase', 'Crud.Test/Support');

App::uses('Model', 'Model');

App::uses('Validation', 'Utility');

/**
 * TestCrudEventManager
 *
 * This manager class is used to replace the CakeEventManger instance.
 * As such, it becomes a global listener and is used to keep a log of
 * all events fired during the test
 */
class TestCrudEventManager extends CakeEventManager {

	protected $_log = array();

	public function dispatch($event) {
		$this->_log[] = array(
			'name' => $event->name(),
			'subject' => $event->subject()
		);
		parent::dispatch($event);
	}

	public function getLog($params = array()) {
		$params += array('clear' => true, 'format' => 'names');

		$log = $this->_log;

		if ($params['format'] === 'names') {
			$return = array();
			foreach ($log as $entry) {
				$return[] = $entry['name'];
			}
			$log = $return;
		}

		if ($params['clear']) {
			$this->_log = array();
		}

		return $log;
	}

}

class CrudExamplesController extends Controller {

	public $uses = array('CrudExample');

	public $modelClass = 'CrudExample';

	public static $componentsArray = array(
		'Session',
		'Crud.Crud' => array(
			'actions' => array(
				'index',
				'add',
				'edit',
				'delete',
				'view'
			)
		)
	);

	public $paginate = array(
		'limit' => 1000
	);

/**
 * Make it possible to dynamically define the components array during tests
 *
 * @param CakeRequest $request
 * @param CakeResponse $response
 * @return void
 */
	public function __construct($request = null, $response = null) {
		$this->components = self::$componentsArray;

		return parent::__construct($request, $response);
	}

/**
 * add
 *
 * Used in the testAddActionTranslatedBaseline test
 *
 * @return void
 */
	public function add() {
		return $this->Crud->execute();
	}

/**
 * Test that it should render 'search.ctp'
 *
 * @return void
 */
	public function search() {
		return $this->Crud->execute('index');
	}

/**
 * Test that it should render 'index'
 *
 * @return void
 */
	public function index() {
		return $this->Crud->execute('index');
	}

}

/**
 * TestCrudComponent
 *
 * Expose protected methods so we can test them in isolation
 */
class TestCrudComponent extends CrudComponent {

/**
 * test visibility wrapper - access protected _modelName property
 */
	public function getModelName() {
		return $this->_modelName;
	}

/**
 * test visibility wrapper - call protected method _setModelProperties
 */
	public function setModelProperties() {
		return parent::_setModelProperties();
	}

/**
 * test visibility wrapper - allow on the fly change of action name
 */
	public function setAction($name) {
		$this->_action = $name;
	}

}

class TestListener extends CrudListener {

	public $callCount = 0;

	public function setup() {
		$this->callCount += 1;
	}

}

/**
 * CrudComponentTestCase
 */
class CrudComponentTest extends CrudControllerTestCase {

/**
 * fixtures
 *
 * Use the core posts fixture to have something to work on.
 * What fixture is used is almost irrelevant, was chosen as it is simple
 */
	public $fixtures = array(
		'core.post', 'core.author', 'core.tag', 'core.comment', 'core.flag_tree',
		'plugin.crud.posts_tag', 'core.cake_session'
	);

/**
 * setUp
 *
 * Setup the classes the crud component needs to be testable
 */
	public function setUp() {
		require_once ('models.php');

		parent::setUp();

		CakeEventManager::instance(new TestCrudEventManager());

		ConnectionManager::getDataSource('test')->getLog();

		$this->model = new CrudExample();

		$this->controller = $this->getMock(
			'CrudExamplesController',
			array('header', 'redirect', 'render', '_stop'),
			array(),
			'',
			false
		);
		$this->controller->name = 'CrudExamples';

		$this->request = $this->getMock('CakeRequest', array('is', 'method'));
		$this->request->expects($this->any())->method('is')->will($this->returnValue(true));

		$response = new CakeResponse();
		$this->controller->__construct($this->request, $response);
		$this->controller->methods = array();

		$this->Collection = new ComponentCollection();
		$this->Collection->init($this->controller);
		$this->controller->Components = $this->Collection;

		$settings = array(
			'actions' => array(
				'index',
				'add',
				'edit',
				'view',
				'delete'
			)
		);

		$this->Crud = new TestCrudComponent($this->Collection, $settings);
		$this->Crud->initialize($this->controller);
		$this->controller->Crud = $this->Crud;
	}

/**
 * tearDown method
 */
	public function tearDown() {
		unset(
			$this->model,
			$this->request,
			$this->controller,
			$this->Crud,
			$this->Collection
		);

		parent::tearDown();
	}

/**
 * Test config normalization
 *
 * @return void
 */
	public function testConfigNormalization() {
		$Collection = $this->getMock('ComponentCollection');

		$settings = array(
			'actions' => array(
				'index',
				'admin_index',
				'add' => 'Crud.Add',
				'view' => array('viewVar' => 'beers'),
				'edit' => array('viewVar' => 'beers', 'className' => 'MyPlugin.MyEdit'),
				'foo' => 'index'
			),
			'listeners' => array(
				'Related' => 'Related',
				'Mylistener' => 'MyPlugin.Mylistener'
			)
		);
		$Crud = $this->getMock('CrudComponent', array('_loadListeners', 'trigger'), array($Collection, $settings));
		$Crud
			->expects($this->once())
			->method('_loadListeners');
		$Crud
			->expects($this->once())
			->method('trigger');
		$Crud->initialize($this->controller);

		$expected = array(
			'index' => array('className' => 'Crud.Index'),
			'admin_index' => array('className' => 'Crud.Index'),
			'add' => array('className' => 'Crud.Add'),
			'view' => array('viewVar' => 'beers', 'className' => 'Crud.View'),
			'edit' => array('viewVar' => 'beers', 'className' => 'MyPlugin.MyEdit'),
			'foo' => array('className' => 'Crud.Index')
		);
		$this->assertEquals($expected, $Crud->settings['actions']);

		$expected = array(
			'Related' => array('className' => 'Crud.Related'),
			'Mylistener' => array('className' => 'MyPlugin.Mylistener'),
			'RelatedModels' => array('className' => 'Crud.RelatedModels'),
		);
		$this->assertEquals($expected, $Crud->settings['listeners']);
	}

/**
 * Test deprecated `executeAction` calls `execute` correctly
 *
 */
	public function testExecuteActionToExecute() {
		$Collection = $this->getMock('ComponentCollection');
		$settings = array('actions' => array('index'));

		$Crud = $this->getMock('CrudComponent', array('execute'), array($Collection, $settings));
		$Crud
			->expects($this->once())
			->method('execute')
			->with('index', array('foo' => 'bar'));

		$Crud->executeAction('index', array('foo' => 'bar'));
	}

/**
 * testEnable
 *
 */
	public function testEnable() {
		$this->Crud->mapAction('puppies', 'view', false);
		$this->Crud->enable('puppies');

		$result = $this->Crud->isActionMapped('puppies');
		$this->assertTrue($result);
	}

/**
 * testDisableAction
 *
 */
	public function testDisableAction() {
		$this->Crud->disable('view');

		$result = $this->Crud->isActionMapped('view');
		$this->assertFalse($result);
	}

/**
 * testMapAction
 *
 */
	public function testMapAction() {
		$this->Crud->mapAction('puppies', 'view');

		$result = $this->Crud->isActionMapped('puppies');
		$this->assertTrue($result);

		$this->Crud->mapAction('kittens', array(
			'className' => 'Crud.index',
			'relatedModels' => false
		));

		$result = $this->Crud->isActionMapped('kittens');
		$this->assertTrue($result);

		$expected = array(
			'className' => 'Crud.index',
			'relatedModels' => false
		);
		$this->assertEquals($expected, $this->Crud->config('actions.kittens'));
	}

/**
 * testView
 *
 */
	public function testView() {
		$this->request
			->expects($this->once())
			->method('method')
			->will($this->returnValue('GET'));

		$this->controller
			->expects($this->once())
			->method('render');

		$this->Crud->view('view', 'cupcakes');
		$this->Crud->execute('view', array(1));
	}

/**
 * testIsActionMappedYes
 *
 */
	public function testIsActionMappedYes() {
		$result = $this->Crud->isActionMapped('index');
		$this->assertTrue($result);

		$this->controller->action = 'edit';
		$this->Crud->initialize($this->controller);
		$result = $this->Crud->isActionMapped();
		$this->assertTrue($result);
	}

/**
 * testIsActionMappedNo
 *
 */
	public function testIsActionMappedNo() {
		$result = $this->Crud->isActionMapped('puppies');
		$this->assertFalse($result);

		$this->controller->action = 'rainbows';
		$this->Crud->initialize($this->controller);
		$result = $this->Crud->isActionMapped();
		$this->assertFalse($result);
	}

/**
 * Tests on method registers an event
 *
 */
	public function testOn() {
		$this->Crud->on('event', 'fakeCallback');

		$return = $this->controller->getEventManager()->listeners('Crud.event');

		$expected = array(
			array(
				'callable' => 'fakeCallback',
				'passParams' => false
			)
		);
		$this->assertSame($expected, $return);
	}

/**
 * tests on method registers an event with extra params
 *
 */
	public function testOnWithPriPriorityy() {
		$this->Crud->on('event', 'fakeCallback');
		$this->Crud->on('event', 'fakeHighPriority', array('priority' => 1));
		$this->Crud->on('event', 'fakeLowPriority', array('priority' => 99999));

		$return = $this->controller->getEventManager()->listeners('Crud.event');

		$expected = array(
			array(
				'callable' => 'fakeHighPriority',
				'passParams' => false
			),
			array(
				'callable' => 'fakeCallback',
				'passParams' => false
			),
			array(
				'callable' => 'fakeLowPriority',
				'passParams' => false
			)
		);
		$this->assertSame($expected, $return);
	}

/**
 * Test if crud complains about unmapped actions
 *
 * @expectedException CakeException
 * @return void
 */
	public function testCrudWillComplainAboutUnmappedAction() {
		$this->Crud->execute('show_all');
	}

/**
 * Test if view with array yields the expected result
 *
 * @return void
 */
	public function testViewWithArrayNewAction() {
		$this->request
			->expects($this->once())
			->method('method')
			->will($this->returnValue('GET'));

		$this->request
			->expects($this->once())
			->method('method')
			->will($this->returnValue('GET'));

		$this->controller
			->expects($this->once())
			->method('render')
			->with('index');

		$this->Crud->mapAction('show_all', 'index');
		$this->Crud->view(array('show_all' => 'index', 'index' => 'overview'));

		$this->Crud->execute('show_all');
	}

/**
 * Test if view with array yields the expected result
 *
 * @return void
 */
	public function testViewWithArrayIndexAction() {
		$this->request
			->expects($this->once())
			->method('method')
			->will($this->returnValue('GET'));

		$this->controller
			->expects($this->once())
			->method('render')
			->with('overview');

		$this->Crud->mapAction('show_all', 'index');
		$this->Crud->view(array('show_all' => 'index', 'index' => 'overview'));

		$this->Crud->execute('index');
	}

/**
 * Test that having no mapped model for an action,
 * just use the modelClass from the controller
 *
 * @return void
 */
	public function testSetModelPropertiesDefault() {
		$this->Crud->setAction('index');
		$this->Crud->setModelProperties();
		$this->assertSame('CrudExample', $this->Crud->getModelName());
	}

/**
 * Test that the build in action names can't be used
 * within other plugins
 *
 * @expectedException CakeException
 * @expectedExceptionMessage The build-in CrudActions (Index, View, Add, Edit and Delete) must be loaded from the Crud plugin
 * @return void
 */
	public function testBuildInCrudActionsCantBeUsedInOtherPluginsIndex() {
		$this->Crud->mapAction('test', 'Sample.Index');
	}

/**
 * Test that the build in action names can't be used
 * within other plugins
 *
 * @expectedException CakeException
 * @expectedExceptionMessage The build-in CrudActions (Index, View, Add, Edit and Delete) must be loaded from the Crud plugin
 * @return void
 */
	public function testBuildInCrudActionsCantBeUsedInOtherPluginsView() {
		$this->Crud->mapAction('test', 'Sample.View');
	}

/**
 * Test that the build in action names can't be used
 * within other plugins
 *
 * @expectedException CakeException
 * @expectedExceptionMessage The build-in CrudActions (Index, View, Add, Edit and Delete) must be loaded from the Crud plugin
 * @return void
 */
	public function testBuildInCrudActionsCantBeUsedInOtherPluginsAdd() {
		$this->Crud->mapAction('test', 'Sample.Add');
	}

/**
 * Test that the build in action names can't be used
 * within other plugins
 *
 * @expectedException CakeException
 * @expectedExceptionMessage The build-in CrudActions (Index, View, Add, Edit and Delete) must be loaded from the Crud plugin
 * @return void
 */
	public function testBuildInCrudActionsCantBeUsedInOtherPluginsEdit() {
		$this->Crud->mapAction('test', 'Sample.Edit');
	}

/**
 * Test that the build in action names can't be used
 * within other plugins
 *
 * @expectedException CakeException
 * @expectedExceptionMessage The build-in CrudActions (Index, View, Add, Edit and Delete) must be loaded from the Crud plugin
 * @return void
 */
	public function testBuildInCrudActionsCantBeUsedInOtherPluginsDelete() {
		$this->Crud->mapAction('test', 'Sample.Delete');
	}

/**
 * Test that Providing a CrudAction name that isn't in the
 * list of build-in once, will allow you to use it inside
 * another plugin.
 *
 * It's expected that the plugin CrudSample doesn't exist,
 * the App::uses() where the warning is raised is *after*
 * the check for the above build-in class names
 *
 * @expectedException CakeException
 * @expectedExceptionMessage Plugin CrudSample could not be found.
 * @return void
 */
	public function testCustomCrudActionsCanBeUsedInPlugins() {
		$this->Crud->mapAction('test', 'CrudSample.MyDelete');
	}

/**
 * Test that having a 'search' action in the controller
 * and calling ->execute('index') will still
 * render the 'search' view
 *
 * @return void
 */
	public function testViewCanBeChangedInControllerAction() {
		$this->request
			->expects($this->once())
			->method('method')
			->will($this->returnValue('GET'));

		$this->request->action = 'search';

		$this->controller
			->expects($this->once())
			->method('render')
			->with('search');

		$this->controller->search();
	}

/**
 * Test the default configuration for CrudComponent
 *
 * @return void
 */
	public function testDefaultConfig() {
		$Collection = $this->getMock('ComponentCollection');

		$Crud = new CrudComponent($Collection);

		$result = $Crud->config();
		$expected = array(
			'actions' => array(),
			'eventPrefix' => 'Crud',
			'listeners' => array(
				'RelatedModels' => 'Crud.RelatedModels'
			),
			'messages' => array(
				'domain' => 'crud',
				'invalidId' => array(
					'code' => 400,
					'class' => 'BadRequestException',
					'text' => 'Invalid id'
				),
				'recordNotFound' => array(
					'code' => 404,
					'class' => 'NotFoundException',
					'text' => 'Not found'
				),
				'badRequestMethod' => array(
					'code' => 405,
					'class' => 'MethodNotAllowedException',
					'text' => 'Method not allowed. This action permits only {methods}'
				)
			),
			'eventLogging' => false
		);
		$this->assertEquals($expected, $result);
	}

/**
 * Test that providing configuration for a new
 * listener in the Crud setting should preserve
 * the defaults and add the new listener to the array
 *
 * @return void
 */
	public function testConstructMerging() {
		$Collection = $this->getMock('ComponentCollection');

		$config = array(
			'listeners' => array(
				'Api' => 'Crud.Api'
			)
		);

		$Crud = new CrudComponent($Collection, $config);
		$result = $Crud->config();
		$expected = array(
			'actions' => array(),
			'eventPrefix' => 'Crud',
			'listeners' => array(
				'RelatedModels' => 'Crud.RelatedModels',
				'Api' => 'Crud.Api'
			),
			'messages' => array(
				'domain' => 'crud',
				'invalidId' => array(
					'code' => 400,
					'class' => 'BadRequestException',
					'text' => 'Invalid id'
				),
				'recordNotFound' => array(
					'code' => 404,
					'class' => 'NotFoundException',
					'text' => 'Not found'
				),
				'badRequestMethod' => array(
					'code' => 405,
					'class' => 'MethodNotAllowedException',
					'text' => 'Method not allowed. This action permits only {methods}'
				)
			),
			'eventLogging' => false
		);
		$this->assertEquals($expected, $result);
	}

/**
 * Test that providing configuration for a new
 * listener in the Crud setting should preserve
 * the defaults and add the new listener to the array
 *
 * @return void
 */
	public function testConstructMerging2() {
		$Collection = $this->getMock('ComponentCollection');

		$config = array(
			'listeners' => array(
			)
		);

		$Crud = new CrudComponent($Collection, $config);
		$result = $Crud->config();
		$expected = array(
			'actions' => array(),
			'eventPrefix' => 'Crud',
			'listeners' => array(
				'RelatedModels' => 'Crud.RelatedModels'
			),
			'messages' => array(
				'domain' => 'crud',
				'invalidId' => array(
					'code' => 400,
					'class' => 'BadRequestException',
					'text' => 'Invalid id'
				),
				'recordNotFound' => array(
					'code' => 404,
					'class' => 'NotFoundException',
					'text' => 'Not found'
				),
				'badRequestMethod' => array(
					'code' => 405,
					'class' => 'MethodNotAllowedException',
					'text' => 'Method not allowed. This action permits only {methods}'
				)
			),
			'eventLogging' => false
		);
		$this->assertEquals($expected, $result);
	}

/**
 * Test that addListener works - without listener
 * default config
 *
 * @return void
 */
	public function testAddListenerWithoutDefaults() {
		$listeners = $this->Crud->config('listeners');
		$expected = array(
			'RelatedModels' => array('className' => 'Crud.RelatedModels')
		);

		$this->assertEquals($expected, $listeners);

		$this->Crud->addListener('Api', 'Crud.Api');

		$listeners = $this->Crud->config('listeners');
		$expected = array(
			'RelatedModels' => array('className' => 'Crud.RelatedModels'),
			'Api' => array('className' => 'Crud.Api')
		);
		$this->assertEquals($expected, $listeners);

		$this->assertEquals(array('className' => 'Crud.Api'), $this->Crud->defaults('listeners', 'Api'));
	}

/**
 * Test that addListener works - with listener
 * default config
 *
 * @return void
 */
	public function testAddListenerWithDefaults() {
		$listeners = $this->Crud->config('listeners');
		$expected = array(
			'RelatedModels' => array('className' => 'Crud.RelatedModels')
		);

		$this->assertEquals($expected, $listeners);

		$this->Crud->addListener('Api', 'Crud.Api', array('test' => 1));

		$listeners = $this->Crud->config('listeners');
		$expected = array(
			'RelatedModels' => array('className' => 'Crud.RelatedModels'),
			'Api' => array('className' => 'Crud.Api', 'test' => 1)
		);
		$this->assertEquals($expected, $listeners);

		$this->assertEquals(
			array('className' => 'Crud.Api', 'test' => 1),
			$this->Crud->defaults('listeners', 'Api')
		);
	}

/**
 * Test that removeListener works
 *
 * @return void
 */
	public function testRemoveListener() {
		$listeners = $this->Crud->config('listeners');
		$expected = array(
			'RelatedModels' => array('className' => 'Crud.RelatedModels')
		);

		$this->assertEquals($expected, $listeners);

		$this->Crud->removeListener('RelatedModels');

		$listeners = $this->Crud->config('listeners');
		$expected = array();
		$this->assertEquals($expected, $listeners);

		// Should now throw an exception
		$this->setExpectedException('CakeException', 'Listener "relatedModels" is not configured');
		$this->Crud->listener('relatedModels');
	}

/**
 * Test removing a listener that doesn't exist
 * should return false
 *
 * @return void
 */
	public function testRemoveListenerNoExist() {
		$expected = false;
		$result = $this->Crud->removeListener('invalid_name');
		$this->assertEquals($expected, $result);
	}

/**
 * Test that removeLister works
 *
 * Also ensure that the listener is detached from EventManager
 *
 * @return void
 */
	public function testRemoveListenerAttached() {
		$listeners = $this->Crud->config('listeners');
		$expected = array(
			'RelatedModels' => array('className' => 'Crud.RelatedModels')
		);
		$this->assertEquals($expected, $listeners);

		// Make sure the listener is attached
		$this->Crud->listener('RelatedModels');

		// Remove it (including detach)
		$this->Crud->removeListener('RelatedModels');

		$listeners = $this->Crud->config('listeners');
		$expected = array();
		$this->assertEquals($expected, $listeners);

		// Should now throw an exception
		$this->setExpectedException('CakeException', 'Listener "RelatedModels" is not configured');
		$this->Crud->listener('RelatedModels');
	}

/**
 * Test changing view var for one action works
 *
 * @return void
 */
	public function testViewVarSingleAction() {
		$this->Crud->viewVar('index', 'my_var');

		$expected = 'my_var';
		$result = $this->Crud->action('index')->viewVar();
		$this->assertEquals($expected, $result);
	}

/**
 * Test changing view var for multiple actions works
 *
 * @return void
 */
	public function testViewVarMultipleActions() {
		$this->Crud->viewVar(array('index' => 'my_var', 'view' => 'view_var'));

		$expected = 'my_var';
		$result = $this->Crud->action('index')->viewVar();
		$this->assertEquals($expected, $result);

		$expected = 'view_var';
		$result = $this->Crud->action('view')->viewVar();
		$this->assertEquals($expected, $result);
	}

/**
 * Test changing view var for multiple actions works
 *
 * @return void
 */
	public function testFindMethodMultipleActions() {
		$this->Crud->findMethod(array('index' => 'my_all', 'view' => 'my_view'));

		$expected = 'my_all';
		$result = $this->Crud->action('index')->findMethod();
		$this->assertEquals($expected, $result);

		$expected = 'my_view';
		$result = $this->Crud->action('view')->findMethod();
		$this->assertEquals($expected, $result);
	}

/**
 * Test setting defaults for one action works
 *
 * @return void
 */
	public function testDefaultsOnAction() {
		$this->Crud->defaults('actions', 'index', array('unit_test' => true));
		$config = $this->Crud->defaults('actions', 'index');

		$this->assertTrue($config['unit_test']);
	}

/**
 * Test setting defaults for multiple actions work
 *
 * @return void
 */
	public function testDefaultsMultipleActions() {
		$this->Crud->defaults('actions', array('index', 'view'), array('unit_test' => true));

		$config = $this->Crud->defaults('actions', 'index');
		$this->assertTrue($config['unit_test']);

		$config = $this->Crud->defaults('actions', 'view');
		$this->assertTrue($config['unit_test']);
	}

/**
 * Test setting defaults for one listener works
 *
 * @return void
 */
	public function testDefaultsOneListener() {
		$this->Crud->defaults('listeners', 'translations', array('unit_test' => true));
		$config = $this->Crud->defaults('listeners', 'translations');

		$this->assertTrue($config['unit_test']);
	}

/**
 * Test setting defaults for multiple actions work
 *
 * @return void
 */
	public function testDefaultsMultipleListeners() {
		$this->Crud->defaults('listeners', array('translations', 'relatedModels'), array('unit_test' => true));

		$config = $this->Crud->defaults('listeners', 'translations');
		$this->assertTrue($config['unit_test']);

		$config = $this->Crud->defaults('listeners', 'relatedModels');
		$this->assertTrue($config['unit_test']);
	}

/**
 * Test setting defaults for one listener works
 *
 * This proves that not setting 'className' doesn't break
 *
 * @return void
 */
	public function testDefaultsListenerNotAlreadyLoaded() {
		$this->Crud->defaults('listeners', 'api', array('unit_test' => true));
		$config = $this->Crud->defaults('listeners', 'api');
		$this->assertTrue($config['unit_test']);
	}

/**
 * Test adding a listener only by a name and no class works
 *
 * By only providing a name, it should default to Crud plugin
 *
 * @return void
 */
	public function testAddListenerOnlyNameNoClassName() {
		$this->Crud->addListener('api');
		$config = $this->Crud->config('listeners');
		$this->assertEquals(array('className' => 'Crud.Api'), $config['api']);
	}

/**
 * Test adding a listener only by a name and a class works
 *
 * By providing a class, it should not default to Crud plugin
 * even though it doesn't contain any plugin.
 *
 * This allow developers to put listeners in app/Controller/Crud
 *
 * @return void
 */
	public function testAddListenerOnlyNameClassName() {
		$this->Crud->addListener('api', 'Api');
		$config = $this->Crud->config('listeners');
		$this->assertEquals(array('className' => 'Api'), $config['api']);
	}

/**
 * Test adding a listener only by its name, with plugin dot syntax
 * works
 *
 * @return void
 */
	public function testAddListenerOnlyNameWithPlugin() {
		$this->Crud->addListener('MyPlugin.Api');
		$config = $this->Crud->config('listeners');
		$this->assertEquals(array('className' => 'MyPlugin.Api'), $config['api']);
	}

/**
 * Test adding a listener only by its name, with plugin dot syntax
 * works
 *
 * @return void
 */
	public function testAddListenerOnlyNameWithPluginLowercase() {
		$this->Crud->addListener('MyPlugin.api');
		$config = $this->Crud->config('listeners');
		$this->assertEquals(array('className' => 'MyPlugin.Api'), $config['api']);
	}

/**
 * Test the Crud sets model and modelClass to NULL
 * if there is no model defined in the controller
 *
 * @return void
 */
	public function testControllerWithEmptyUses() {
		$controller = new Controller(new CakeRequest());
		$this->Crud = new CrudComponent($this->Collection, array('actions' => array('index')));
		$this->Crud->initialize($controller);
		$this->controller->Crud = $this->Crud;
		$this->Crud->action('index');
		$subject = $this->Crud->trigger('sample');

		$this->assertNull($subject->model);
		$this->assertNull($subject->modelClass);
	}

/**
 * Test that it's possible to change just one sub key
 * by providing all the parents, without loosing any
 * default settings
 *
 * @return void
 */
	public function testConfigMergeWorks() {
		$this->Crud->config(array('messages' => array('invalidId' => array('code' => 500))));

		$expected = array(
			'code' => 500,
			'class' => 'BadRequestException',
			'text' => 'Invalid id'
		);
		$result = $this->Crud->config('messages.invalidId');
		$this->assertEquals($expected, $result);
	}

/**
 * Using $key and value, and specifying no merge should overwrite the value keys
 *
 * @return void
 */
	public function testConfigOverwrite() {
		$this->Crud->config('messages', array('invalidId' => array('code' => 500)), null, false);

		$expected = array(
			'domain' => 'crud',
			'invalidId' => array(
				'code' => 500
			),
			'recordNotFound' => array(
				'code' => 404,
				'class' => 'NotFoundException',
				'text' => 'Not found'
			),
			'badRequestMethod' => array(
				'code' => 405,
				'class' => 'MethodNotAllowedException',
				'text' => 'Method not allowed. This action permits only {methods}'
			)
		);
		$result = $this->Crud->config('messages');
		$this->assertEquals($expected, $result);
	}
/**
 * Passing an array, and specifying no merge should overwrite the value keys
 *
 * @return void
 */
	public function testConfigOverwriteArray() {
		$this->Crud->config(array('messages' => array('invalidId' => array('code' => 500))), null, false);

		$expected = array(
			'domain' => 'crud',
			'invalidId' => array(
				'code' => 500,
				'class' => 'BadRequestException',
				'text' => 'Invalid id'
			),
			'recordNotFound' => array(
				'code' => 404,
				'class' => 'NotFoundException',
				'text' => 'Not found'
			),
			'badRequestMethod' => array(
				'code' => 405,
				'class' => 'MethodNotAllowedException',
				'text' => 'Method not allowed. This action permits only {methods}'
			)
		);
		$result = $this->Crud->config('messages');
		$this->assertEquals($expected, $result);
	}

/**
 * Tests that is possible to set the model class to use for the action
 *
 * @return void
 */
	public function testUseModel() {
		$controller = new Controller(new CakeRequest());
		$this->Crud = new CrudComponent($this->Collection, array('actions' => array('index')));
		$this->Crud->initialize($controller);
		$this->controller->Crud = $this->Crud;
		$class = $this->getMockClass('Model');
		$this->Crud->useModel($class);
		$this->Crud->action('index');
		$subject = $this->Crud->trigger('sample');

		$this->assertInstanceOf($class, $subject->model);
		$this->assertEquals($class, $subject->modelClass);
	}

/**
 * test_loadListener
 *
 * @return void
 */
	public function test_loadListener() {
		$this->Crud->config('listeners.HasSetup', array(
			'className' => 'Test'
		));

		$this->setReflectionClassInstance($this->Crud);
		$listener = $this->callProtectedMethod('_loadListener', array('HasSetup'), $this->Crud);
		$this->assertSame(1, $listener->callCount, 'Setup should be called');
	}
}
