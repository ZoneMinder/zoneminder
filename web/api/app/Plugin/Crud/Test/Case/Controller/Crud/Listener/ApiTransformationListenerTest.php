<?php

App::uses('CrudTestCase', 'Crud.Test/Support');
App::uses('Controller', 'Controller');
App::uses('Model', 'Model');
App::uses('CakeRequest', 'Network');
App::uses('IndexCrudAction', 'Crud.Controller/Crud/Action');
App::uses('ApiTransformationListener', 'Crud.Controller/Crud/Listener');

/**
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 */
class ApiTransformationListenerTest extends CrudTestCase {

/**
 * staticExample
 *
 * Used to test static calls
 *
 * @param string $value
 * @return string
 */
	public static function staticExample($value) {
		return Inflector::slug($value);
	}

/**
 * testImplementedEventsApiOnlyIsApi
 *
 * @return void
 */
	public function testImplementedEvents() {
		$listener = $this
			->getMockBuilder('ApiTransformationListener')
			->setMethods(array('_request'))
			->disableOriginalConstructor()
			->getMock();

		$expected = array('Crud.beforeRender' => array('callable' => 'beforeRender', 'priority' => 200));
		$result = $listener->implementedEvents();
		$this->assertSame($expected, $result);
	}

/**
 * testBeforeRenderNoApiRequest
 *
 * @return void
 */
	public function testBeforeRenderNoApiRequest() {
		$listener = $this
			->getMockBuilder('ApiTransformationListener')
			->setMethods(array('_request'))
			->disableOriginalConstructor()
			->getMock();

		$request = $this
			->getMockBuilder('CakeRequest')
			->setMethods(array('is'))
			->disableOriginalConstructor()
			->getMock();

		$request
			->expects($this->once())
			->method('is')
			->with('api')
			->will($this->returnValue(false));

		$listener
			->expects($this->once())
			->method('_request')
			->will($this->returnValue($request));

		$listener
			->expects($this->never())
			->method('_controller');

		$listener
			->expects($this->never())
			->method('_action');

		$listener
			->expects($this->never())
			->method('_model');

		$listener
			->expects($this->never())
			->method('_setMethods');

		$listener
			->expects($this->never())
			->method('_changeNesting');

		$listener
			->expects($this->never())
			->method('_recurse');

		$this->assertTrue($listener->beforeRender());
	}

/**
 * testBeforeRenderWithNestingChange
 *
 * @return void
 */
	public function testBeforeRenderWithNestingChange() {
		$i = 0;
		$listener = $this
			->getMockBuilder('ApiTransformationListener')
			->setMethods(array('_request', '_controller', '_action', '_model', '_changeNesting', '_recurse', '_setMethods'))
			->disableOriginalConstructor()
			->getMock();

		$request = $this
			->getMockBuilder('CakeRequest')
			->setMethods(array('is'))
			->disableOriginalConstructor()
			->getMock();

		$request
			->expects($this->once())
			->method('is')
			->with('api')
			->will($this->returnValue(true));

		$controller = $this
			->getMockBuilder('Controller')
			->setMethods(array('set'))
			->disableOriginalConstructor()
			->getMock();

		$action = $this
			->getMockBuilder('IndexAction')
			->setMethods(array('viewVar'))
			->disableOriginalConstructor()
			->getMock();

		$model = $this
			->getMockBuilder('Model')
			->disableOriginalConstructor()
			->getMock();

		$listener
			->expects($this->at($i++))
			->method('_request')
			->will($this->returnValue($request));

		$listener
			->expects($this->at($i++))
			->method('_controller')
			->will($this->returnValue($controller));

		$listener
			->expects($this->at($i++))
			->method('_action')
			->will($this->returnValue($action));

		$listener
			->expects($this->at($i++))
			->method('_setMethods');

		$listener
			->expects($this->at($i++))
			->method('_model')
			->will($this->returnValue($model));

		$settings = array(
			'changeNesting' => true,
			'changeKeys' => true,
			'changeTime' => true,
			'castNumbers' => true,
			'keyMethods' => array(),
			'valueMethods' => array(),
			'replaceMap' => array()
		);

		$this->setReflectionClassInstance($listener);
		$this->setProtectedProperty('_settings', $settings, $listener);

		$success = true;
		$users = array(
			0 => array(
				'User' => array('id' => 5, 'name' => 'FriendsOfCake'),
				'Profile' => array('id' => 987, 'twitter' => '@FriendsOfCake')
			),
			1 => array(
				'User' => array('id' => 45, 'name' => 'CakePHP'),
				'Profile' => array('id' => 123, 'twitter' => '@cakephp')
			),
		);

		$controller->viewVars = compact('success', 'users');

		$action
			->expects($this->once())
			->method('viewVar')
			->will($this->returnValue('users'));

		$model->alias = 'User';

		$result = array();

		$nested = array(
			'id' => 5,
			'name' => 'FriendsOfCake',
			'Profile' => array('id' => 987, 'twitter' => '@FriendsOfCake')
		);
		$result[] = $nested;

		$listener
			->expects($this->at($i++))
			->method('_changeNesting')
			->with($this->identicalTo($users[0]), 'User')
			->will($this->returnValue($nested));

		$listener
			->expects($this->at($i++))
			->method('_recurse')
			->with($nested);

		$nested = array(
			'id' => 45,
			'name' => 'CakePHP',
			'Profile' => array('id' => 123, 'twitter' => '@cakephp')
		);
		$result[] = $nested;

		$listener
			->expects($this->at($i++))
			->method('_changeNesting')
			->with($this->identicalTo($users[1]), 'User')
			->will($this->returnValue($nested));

		$listener
			->expects($this->at($i++))
			->method('_recurse')
			->with($nested);

		$listener
			->expects($this->at($i++))
			->method('_controller')
			->will($this->returnValue($controller));

		$controller
			->expects($this->once())
			->method('set')
			->with('users', $result);

		$this->assertTrue($listener->beforeRender());
	}

/**
 * testBeforeRenderWithoutNestingChange
 *
 * @return void
 */
	public function testBeforeRenderWithoutNestingChange() {
		$i = 0;
		$listener = $this
			->getMockBuilder('ApiTransformationListener')
			->setMethods(array('_request', '_controller', '_action', '_model', '_changeNesting', '_recurse', '_setMethods'))
			->disableOriginalConstructor()
			->getMock();

		$request = $this
			->getMockBuilder('CakeRequest')
			->setMethods(array('is'))
			->disableOriginalConstructor()
			->getMock();

		$request
			->expects($this->once())
			->method('is')
			->with('api')
			->will($this->returnValue(true));

		$controller = $this
			->getMockBuilder('Controller')
			->setMethods(array('set'))
			->disableOriginalConstructor()
			->getMock();

		$action = $this
			->getMockBuilder('IndexAction')
			->setMethods(array('viewVar'))
			->disableOriginalConstructor()
			->getMock();

		$model = $this
			->getMockBuilder('Model')
			->disableOriginalConstructor()
			->getMock();

		$listener
			->expects($this->at($i++))
			->method('_request')
			->will($this->returnValue($request));

		$listener
			->expects($this->at($i++))
			->method('_controller')
			->will($this->returnValue($controller));

		$listener
			->expects($this->at($i++))
			->method('_action')
			->will($this->returnValue($action));

		$listener
			->expects($this->at($i++))
			->method('_setMethods');

		$listener
			->expects($this->at($i++))
			->method('_model')
			->will($this->returnValue($model));

		$settings = array(
			'changeNesting' => false,
			'changeKeys' => true,
			'changeTime' => true,
			'castNumbers' => true,
			'keyMethods' => array(),
			'valueMethods' => array(),
			'replaceMap' => array()
		);

		$this->setReflectionClassInstance($listener);
		$this->setProtectedProperty('_settings', $settings, $listener);

		$success = true;
		$users = array(
			0 => array(
				'User' => array('id' => 5, 'name' => 'FriendsOfCake'),
				'Profile' => array('id' => 987, 'twitter' => '@FriendsOfCake')
			),
			1 => array(
				'User' => array('id' => 45, 'name' => 'CakePHP'),
				'Profile' => array('id' => 123, 'twitter' => '@cakephp')
			)
		);

		$controller->viewVars = compact('success', 'users');

		$action
			->expects($this->once())
			->method('viewVar')
			->will($this->returnValue('users'));

		$model->alias = 'User';

		$listener
			->expects($this->never())
			->method('_changeNesting');

		$listener
			->expects($this->at($i++))
			->method('_recurse')
			->with($this->identicalTo($users[0]));

		$listener
			->expects($this->at($i++))
			->method('_recurse')
			->with($this->identicalTo($users[1]));

		$listener
			->expects($this->at($i++))
			->method('_controller')
			->will($this->returnValue($controller));

		$controller
			->expects($this->once())
			->method('set')
			->with('users', $users);

		$this->assertTrue($listener->beforeRender());
	}

/**
 * testBeforeRenderWithFindFirstAndNestingChange
 *
 * @return void
 */
	public function testBeforeRenderWithFindFirstAndNestingChange() {
		$i = 0;
		$listener = $this
			->getMockBuilder('ApiTransformationListener')
			->setMethods(array('_request', '_controller', '_action', '_model', '_changeNesting', '_recurse', '_setMethods'))
			->disableOriginalConstructor()
			->getMock();

		$request = $this
			->getMockBuilder('CakeRequest')
			->setMethods(array('is'))
			->disableOriginalConstructor()
			->getMock();

		$request
			->expects($this->once())
			->method('is')
			->with('api')
			->will($this->returnValue(true));

		$controller = $this
			->getMockBuilder('Controller')
			->setMethods(array('set'))
			->disableOriginalConstructor()
			->getMock();

		$action = $this
			->getMockBuilder('IndexAction')
			->setMethods(array('viewVar'))
			->disableOriginalConstructor()
			->getMock();

		$model = $this
			->getMockBuilder('Model')
			->disableOriginalConstructor()
			->getMock();

		$listener
			->expects($this->at($i++))
			->method('_request')
			->will($this->returnValue($request));

		$listener
			->expects($this->at($i++))
			->method('_controller')
			->will($this->returnValue($controller));

		$listener
			->expects($this->at($i++))
			->method('_action')
			->will($this->returnValue($action));

		$listener
			->expects($this->at($i++))
			->method('_setMethods');

		$listener
			->expects($this->at($i++))
			->method('_model')
			->will($this->returnValue($model));

		$settings = array(
			'changeNesting' => true,
			'changeKeys' => true,
			'changeTime' => true,
			'castNumbers' => true,
			'keyMethods' => array(),
			'valueMethods' => array(),
			'replaceMap' => array()
		);

		$this->setReflectionClassInstance($listener);
		$this->setProtectedProperty('_settings', $settings, $listener);

		$success = true;
		$user = array(
			'User' => array(
				'id' => 5,
				'name' => 'FriendsOfCake'
			),
			'Profile' => array(
				'id' => 987,
				'twitter' => '@FriendsOfCake'
			)
		);

		$controller->viewVars = compact('success', 'user');

		$action
			->expects($this->once())
			->method('viewVar')
			->will($this->returnValue('user'));

		$model->alias = 'User';

		$nested = array(
			'id' => 5,
			'name' => 'FriendsOfCake',
			'Profile' => array('id' => 987, 'twitter' => '@FriendsOfCake')
		);

		$listener
			->expects($this->at($i++))
			->method('_changeNesting')
			->with($this->identicalTo($user), 'User')
			->will($this->returnValue($nested));

		$listener
			->expects($this->at($i++))
			->method('_recurse')
			->with($nested);

		$listener
			->expects($this->at($i++))
			->method('_controller')
			->will($this->returnValue($controller));

		$controller
			->expects($this->once())
			->method('set')
			->with('user', $nested);

		$this->assertTrue($listener->beforeRender());
	}

/**
 * testBeforeRenderWithoutViewVar
 *
 * @return void
 */
	public function testBeforeRenderWithoutViewVar() {
		$i = 0;
		$listener = $this
			->getMockBuilder('ApiTransformationListener')
			->setMethods(array('_request', '_controller', '_action', '_model', '_changeNesting', '_recurse', '_setMethods'))
			->disableOriginalConstructor()
			->getMock();

		$request = $this
			->getMockBuilder('CakeRequest')
			->setMethods(array('is'))
			->disableOriginalConstructor()
			->getMock();

		$request
			->expects($this->once())
			->method('is')
			->with('api')
			->will($this->returnValue(true));

		$controller = $this
			->getMockBuilder('Controller')
			->setMethods(array('set'))
			->disableOriginalConstructor()
			->getMock();

		$action = $this
			->getMockBuilder('IndexAction')
			->setMethods(array('viewVar'))
			->disableOriginalConstructor()
			->getMock();

		$model = $this
			->getMockBuilder('Model')
			->disableOriginalConstructor()
			->getMock();

		$listener
			->expects($this->at($i++))
			->method('_request')
			->will($this->returnValue($request));

		$listener
			->expects($this->at($i++))
			->method('_controller')
			->will($this->returnValue($controller));

		$listener
			->expects($this->at($i++))
			->method('_action')
			->will($this->returnValue($action));

		$listener
			->expects($this->never())
			->method('_model')
			->will($this->returnValue($model));

		$settings = array(
			'changeNesting' => true,
			'changeKeys' => true,
			'changeTime' => true,
			'castNumbers' => true,
			'keyMethods' => array(),
			'valueMethods' => array(),
			'replaceMap' => array()
		);

		$this->setReflectionClassInstance($listener);
		$this->setProtectedProperty('_settings', $settings, $listener);

		$success = true;

		$controller->viewVars = compact('success');

		$action
			->expects($this->once())
			->method('viewVar')
			->will($this->returnValue('users'));

		$model->alias = 'User';

		$listener
			->expects($this->never())
			->method('_changeNesting');

		$listener
			->expects($this->never())
			->method('_recurse');

		$controller
			->expects($this->never())
			->method('set');

		$this->assertTrue($listener->beforeRender());
	}

/**
 * testBeforeRenderWithEmptyViewVar
 *
 * @return void
 */
	public function testBeforeRenderWithEmptyViewVar() {
		$i = 0;
		$listener = $this
			->getMockBuilder('ApiTransformationListener')
			->setMethods(array('_request', '_controller', '_action', '_model', '_changeNesting', '_recurse', '_setMethods'))
			->disableOriginalConstructor()
			->getMock();

		$request = $this
			->getMockBuilder('CakeRequest')
			->setMethods(array('is'))
			->disableOriginalConstructor()
			->getMock();

		$request
			->expects($this->once())
			->method('is')
			->with('api')
			->will($this->returnValue(true));

		$controller = $this
			->getMockBuilder('Controller')
			->setMethods(array('set'))
			->disableOriginalConstructor()
			->getMock();

		$action = $this
			->getMockBuilder('IndexAction')
			->setMethods(array('viewVar'))
			->disableOriginalConstructor()
			->getMock();

		$model = $this
			->getMockBuilder('Model')
			->disableOriginalConstructor()
			->getMock();

		$listener
			->expects($this->at($i++))
			->method('_request')
			->will($this->returnValue($request));

		$listener
			->expects($this->at($i++))
			->method('_controller')
			->will($this->returnValue($controller));

		$listener
			->expects($this->at($i++))
			->method('_action')
			->will($this->returnValue($action));

		$listener
			->expects($this->never())
			->method('_model')
			->will($this->returnValue($model));

		$settings = array(
			'changeNesting' => true,
			'changeKeys' => true,
			'changeTime' => true,
			'castNumbers' => true,
			'keyMethods' => array(),
			'valueMethods' => array(),
			'replaceMap' => array()
		);

		$this->setReflectionClassInstance($listener);
		$this->setProtectedProperty('_settings', $settings, $listener);

		$success = true;
		$users = array();

		$controller->viewVars = compact('success', 'users');

		$action
			->expects($this->once())
			->method('viewVar')
			->will($this->returnValue('users'));

		$model->alias = 'User';

		$listener
			->expects($this->never())
			->method('_changeNesting');

		$listener
			->expects($this->never())
			->method('_recurse');

		$controller
			->expects($this->never())
			->method('set');

		$this->assertTrue($listener->beforeRender());
	}

/**
 * testSetMethods
 *
 * @return void
 */
	public function testSetMethods() {
		$listener = $this
			->getMockBuilder('ApiTransformationListener')
			->disableOriginalConstructor()
			->getMock();

		$this->setReflectionClassInstance($listener);

		$closure = function($variable) {
			return $variable;
		};

		$settings = array(
			'changeNesting' => true,
			'changeKeys' => true,
			'changeTime' => true,
			'castNumbers' => true,
			'keyMethods' => array(),
			'valueMethods' => array($closure),
			'replaceMap' => array()
		);

		$this->setProtectedProperty('_settings', $settings, $listener);
		$this->callProtectedMethod('_setMethods', array(), $listener);
		$result = $this->getProtectedProperty('_settings', $listener);

		$expected = array('_replaceKeys');
		$this->assertSame($expected, $result['keyMethods']);

		$expected = array('_castNumbers', '_changeDateToUnix', $closure);
		$this->assertSame($expected, $result['valueMethods']);

		$settings = array(
			'changeNesting' => true,
			'changeKeys' => false,
			'changeTime' => false,
			'castNumbers' => true,
			'keyMethods' => array(),
			'valueMethods' => array($closure),
			'replaceMap' => array()
		);

		$this->setProtectedProperty('_settings', $settings, $listener);
		$this->callProtectedMethod('_setMethods', array(), $listener);
		$result = $this->getProtectedProperty('_settings', $listener);

		$expected = array();
		$this->assertSame($expected, $result['keyMethods']);

		$expected = array('_castNumbers', $closure);
		$this->assertSame($expected, $result['valueMethods']);
	}

/**
 * testRecurseWithKeysAndCasts
 *
 * @return void
 */
	public function testRecurseWithKeysAndCasts() {
		$listener = $this
			->getMockBuilder('ApiTransformationListener')
			->disableOriginalConstructor()
			->getMock();

		$settings = array(
			'changeNesting' => true,
			'changeKeys' => true,
			'changeTime' => true,
			'castNumbers' => true,
			'keyMethods' => array('_replaceKeys'),
			'valueMethods' => array('_castNumbers', '_changeDateToUnix'),
			'replaceMap' => array('User' => 'user', 'Profile' => 'profile')
		);

		$this->setReflectionClassInstance($listener);
		$this->setProtectedProperty('_settings', $settings, $listener);

		$data = array(
			'User' => array('id' => '5', 'name' => 'FriendsOfCake'),
			'Profile' => array('id' => '987', 'twitter' => '@FriendsOfCake')
		);

		$expected = array(
			'user' => array('id' => 5, 'name' => 'FriendsOfCake'),
			'profile' => array('id' => 987, 'twitter' => '@FriendsOfCake')
		);

		$this->callProtectedMethod('_recurse', array(&$data), $listener);

		$this->assertSame($expected, $data);
	}

/**
 * testRecurseNoCasts
 *
 * @return void
 */
	public function testRecurseNoCasts() {
		$listener = $this
			->getMockBuilder('ApiTransformationListener')
			->disableOriginalConstructor()
			->getMock();

		$settings = array(
			'changeNesting' => true,
			'changeKeys' => true,
			'changeTime' => false,
			'castNumbers' => false,
			'keyMethods' => array('_replaceKeys'),
			'valueMethods' => array(),
			'replaceMap' => array('User' => 'user', 'Profile' => 'profile')
		);

		$this->setReflectionClassInstance($listener);
		$this->setProtectedProperty('_settings', $settings, $listener);

		$data = array(
			'User' => array('id' => '5', 'name' => 'FriendsOfCake'),
			'Profile' => array('id' => '987', 'twitter' => '@FriendsOfCake')
		);

		$expected = array(
			'user' => array('id' => '5', 'name' => 'FriendsOfCake'),
			'profile' => array('id' => '987', 'twitter' => '@FriendsOfCake')
		);

		$this->callProtectedMethod('_recurse', array(&$data), $listener);

		$this->assertSame($expected, $data);
	}

/**
 * testRecurseNoCastsMapWithoutMatch
 *
 * @return void
 */
	public function testRecurseNoCastsMapWithoutMatch() {
		$listener = $this
			->getMockBuilder('ApiTransformationListener')
			->disableOriginalConstructor()
			->getMock();

		$settings = array(
			'changeNesting' => true,
			'changeKeys' => true,
			'changeTime' => false,
			'castNumbers' => false,
			'keyMethods' => array('_replaceKeys'),
			'valueMethods' => array(),
			'replaceMap' => array('CakePHP' => 'FriendsOfCake')
		);

		$this->setReflectionClassInstance($listener);
		$this->setProtectedProperty('_settings', $settings, $listener);

		$data = array(
			'User' => array('id' => '5', 'name' => 'FriendsOfCake'),
			'Comment' => array()
		);

		$expected = array(
			'User' => array('id' => '5', 'name' => 'FriendsOfCake'),
			'Comment' => array()
		);

		$this->callProtectedMethod('_recurse', array(&$data), $listener);

		$this->assertSame($expected, $data);
	}

/**
 * testRecurseNoCastsHasMany
 *
 * @return void
 */
	public function testRecurseNoCastsEmptyHasMany() {
		$listener = $this
			->getMockBuilder('ApiTransformationListener')
			->setMethods(array('_model'))
			->disableOriginalConstructor()
			->getMock();

		$user = $this
			->getMockBuilder('Model')
			->disableOriginalConstructor()
			->setMethods(array('find'))
			->getMock();
		$user->name = $user->alias = 'User';

		$comment = $this
			->getMockBuilder('Model')
			->disableOriginalConstructor()
			->setMethods(array('find'))
			->getMock();
		$comment->name = $comment->alias = 'Comment';

		$user->hasMany = array('Comment' => array('className' => 'Comment', 'foreign_key' => 'user_id'));
		$user->Comment = $comment;

		$listener
			->expects($this->once())
			->method('_model')
			->will($this->returnValue($user));

		$settings = array(
			'changeNesting' => true,
			'changeKeys' => true,
			'changeTime' => false,
			'castNumbers' => false,
			'keyMethods' => array('_replaceKeys'),
			'valueMethods' => array(),
			'replaceMap' => array()
		);

		$this->setReflectionClassInstance($listener);
		$this->setProtectedProperty('_settings', $settings, $listener);

		$data = array(
			'User' => array('id' => '5', 'name' => 'FriendsOfCake'),
			'Comment' => array()
		);

		$expected = array(
			'user' => array('id' => '5', 'name' => 'FriendsOfCake'),
			'comments' => array()
		);

		$this->callProtectedMethod('_recurse', array(&$data), $listener);

		$this->assertSame($expected, $data);
	}

/**
 * testRecurseNoKeys
 *
 * @return void
 */
	public function testRecurseNoKeys() {
		$listener = $this
			->getMockBuilder('ApiTransformationListener')
			->disableOriginalConstructor()
			->getMock();

		$settings = array(
			'changeNesting' => true,
			'changeKeys' => false,
			'changeTime' => true,
			'castNumbers' => true,
			'keyMethods' => array(),
			'valueMethods' => array('_castNumbers', '_changeDateToUnix'),
			'replaceMap' => array()
		);

		$this->setReflectionClassInstance($listener);
		$this->setProtectedProperty('_settings', $settings, $listener);

		$data = array(
			'User' => array('id' => '5', 'name' => 'FriendsOfCake', 'created' => '2013-08-26 11:24:54'),
			'Profile' => array('id' => '987', 'twitter' => '@FriendsOfCake')
		);

		$expected = array(
			'User' => array(
				'id' => 5,
				'name' => 'FriendsOfCake',
				'created' => strtotime('2013-08-26 11:24:54')
			),
			'Profile' => array(
				'id' => 987,
				'twitter' => '@FriendsOfCake'
			)
		);

		$this->callProtectedMethod('_recurse', array(&$data), $listener);

		$this->assertSame($expected, $data);
	}

/**
 * testRecurseNoKeysAndNoCasts
 *
 * @return void
 */
	public function testRecurseNoKeysAndNoCasts() {
		$listener = $this
			->getMockBuilder('ApiTransformationListener')
			->disableOriginalConstructor()
			->getMock();

		$settings = array(
			'changeNesting' => true,
			'changeKeys' => false,
			'changeTime' => false,
			'castNumbers' => false,
			'keyMethods' => array(),
			'valueMethods' => array(),
			'replaceMap' => array()
		);

		$this->setReflectionClassInstance($listener);
		$this->setProtectedProperty('_settings', $settings, $listener);

		$data = array(
			'User' => array('id' => '5', 'name' => 'FriendsOfCake'),
			'Profile' => array('id' => '987', 'twitter' => '@FriendsOfCake')
		);

		$expected = $data;

		$this->callProtectedMethod('_recurse', array(&$data), $listener);

		$this->assertSame($expected, $data);
	}

/**
 * testRecurseWithGlobalFunction
 *
 * @return void
 */
	public function testRecurseWithGlobalFunction() {
		$listener = $this
			->getMockBuilder('ApiTransformationListener')
			->disableOriginalConstructor()
			->getMock();

		$settings = array(
			'changeNesting' => true,
			'changeKeys' => false,
			'changeTime' => false,
			'castNumbers' => false,
			'keyMethods' => array(),
			'valueMethods' => array(function($value, $key) {
				return strtoupper($value);
			}),
			'replaceMap' => array()
		);

		$this->setReflectionClassInstance($listener);
		$this->setProtectedProperty('_settings', $settings, $listener);

		$data = array(
			'User' => array('id' => '5', 'name' => 'FriendsOfCake'),
			'Profile' => array('id' => '987', 'twitter' => '@FriendsOfCake')
		);

		$expected = array(
			'User' => array('id' => '5', 'name' => 'FRIENDSOFCAKE'),
			'Profile' => array('id' => '987', 'twitter' => '@FRIENDSOFCAKE')
		);

		$this->callProtectedMethod('_recurse', array(&$data), $listener);

		$this->assertSame($expected, $data);
	}

/**
 * testRecurseWithStaticMethod
 *
 * @return void
 */
	public function testRecurseWithStaticMethod() {
		$listener = $this
			->getMockBuilder('ApiTransformationListener')
			->disableOriginalConstructor()
			->getMock();

		$settings = array(
			'changeNesting' => true,
			'changeKeys' => false,
			'changeTime' => false,
			'castNumbers' => false,
			'keyMethods' => array(),
			'valueMethods' => array('ApiTransformationListenerTest::staticExample'),
			'replaceMap' => array()
		);

		$this->setReflectionClassInstance($listener);
		$this->setProtectedProperty('_settings', $settings, $listener);

		$data = array(
			'User' => array('id' => '5', 'name' => 'Friends Of Cake'),
			'Profile' => array('id' => '987', 'twitter' => '@FriendsOfCake')
		);

		$expected = array(
			'User' => array('id' => '5', 'name' => 'Friends_Of_Cake'),
			'Profile' => array('id' => '987', 'twitter' => 'FriendsOfCake')
		);

		$this->callProtectedMethod('_recurse', array(&$data), $listener);

		$this->assertSame($expected, $data);
	}

/**
 * testRecurseWithClosure
 *
 * @return void
 */
	public function testRecurseWithClosure() {
		$listener = $this
			->getMockBuilder('ApiTransformationListener')
			->disableOriginalConstructor()
			->getMock();

		$uppercase = function($variable) {
			if (!is_string($variable)) {
				return $variable;
			}
			return strtoupper($variable);
		};

		$settings = array(
			'changeNesting' => true,
			'changeKeys' => false,
			'changeTime' => false,
			'castNumbers' => false,
			'keyMethods' => array(),
			'valueMethods' => array($uppercase),
			'replaceMap' => array()
		);

		$this->setReflectionClassInstance($listener);
		$this->setProtectedProperty('_settings', $settings, $listener);

		$data = array(
			'User' => array('id' => '5', 'name' => 'FriendsOfCake'),
			'Profile' => array('id' => '987', 'twitter' => '@FriendsOfCake')
		);

		$expected = array(
			'User' => array('id' => '5', 'name' => 'FRIENDSOFCAKE'),
			'Profile' => array('id' => '987', 'twitter' => '@FRIENDSOFCAKE')
		);

		$this->callProtectedMethod('_recurse', array(&$data), $listener);

		$this->assertSame($expected, $data);
	}

	public function testRecurseKeySpecificTransform() {
		$listener = $this
			->getMockBuilder('ApiTransformationListener')
			->disableOriginalConstructor()
			->getMock();

		$callback = function($variable, $key) {
			if ($key === 'User.changeme') {
				return 'changed';
			}
			return $variable;
		};

		$settings = array(
			'changeNesting' => true,
			'changeKeys' => false,
			'changeTime' => false,
			'castNumbers' => false,
			'keyMethods' => array(),
			'valueMethods' => array($callback),
			'replaceMap' => array()
		);

		$this->setReflectionClassInstance($listener);
		$this->setProtectedProperty('_settings', $settings, $listener);

		$data = array(
			'User' => array(
				'id' => '5',
				'name' => 'FriendsOfCake',
				'changeme' => 'old'
			),
			'Profile' => array(
				'id' => '987',
				'twitter' => '@FriendsOfCake'
			)
		);

		$expected = array(
			'User' => array(
				'id' => '5',
				'name' => 'FriendsOfCake',
				'changeme' => 'changed'
			),
			'Profile' => array(
				'id' => '987',
				'twitter' => '@FriendsOfCake'
			)
		);

		$this->callProtectedMethod('_recurse', array(&$data), $listener);

		$this->assertSame($expected, $data);
	}

	public function testRecurseKeySpecificNestedTransform() {
		$listener = $this
			->getMockBuilder('ApiTransformationListener')
			->disableOriginalConstructor()
			->getMock();

		$callback = function($variable, $key) {
			if (preg_match('@^\d+.User\.changeme$@', $key)) {
				return 'changed';
			}
			return $variable;
		};

		$settings = array(
			'changeNesting' => true,
			'changeKeys' => false,
			'changeTime' => false,
			'castNumbers' => false,
			'keyMethods' => array(),
			'valueMethods' => array($callback),
			'replaceMap' => array()
		);

		$this->setReflectionClassInstance($listener);
		$this->setProtectedProperty('_settings', $settings, $listener);

		$data = array(
			'changeme' => 'no change',
			array(
				'changeme' => 'no change',
				'User' => array(
					'id' => '5',
					'name' => 'FriendsOfCake',
					'changeme' => 'old'
				),
				'Profile' => array(
					'id' => '987',
					'twitter' => '@FriendsOfCake'
				)
			),
			array(
				'User' => array(
					'id' => '6',
					'name' => 'FriendsOfCake',
					'changeme' => 'old two'
				),
				'Profile' => array(
					'id' => '987',
					'twitter' => '@FriendsOfCake'
				)
			)
		);

		$expected = array(
			'changeme' => 'no change',
			array(
				'changeme' => 'no change',
				'User' => array(
					'id' => '5',
					'name' => 'FriendsOfCake',
					'changeme' => 'changed'
				),
				'Profile' => array(
					'id' => '987',
					'twitter' => '@FriendsOfCake'
				)
			),
			array(
				'User' => array(
					'id' => '6',
					'name' => 'FriendsOfCake',
					'changeme' => 'changed'
				),
				'Profile' => array(
					'id' => '987',
					'twitter' => '@FriendsOfCake'
				)
			)
		);

		$this->callProtectedMethod('_recurse', array(&$data), $listener);

		$this->assertSame($expected, $data);
	}

/**
 * testGetReplaceMapFromAssociationsEndlessLoopPrevention
 *
 * @return void
 */
	public function testGetReplaceMapFromAssociationsEndlessLoopPrevention() {
		$listener = $this
			->getMockBuilder('ApiTransformationListener')
			->setMethods(array('_model'))
			->disableOriginalConstructor()
			->getMock();

		$user = $this
			->getMockBuilder('Model')
			->disableOriginalConstructor()
			->setMethods(array('find'))
			->getMock();
		$user->name = $user->alias = 'User';

		$user->belongsTo = array('User' => array('className' => 'User', 'foreignKey' => 'user_id'));
		$user->User = $user;

		$listener
			->expects($this->once())
			->method('_model')
			->will($this->returnValue($user));

		$this->setReflectionClassInstance($listener);

		$expected = array('User' => 'user');
		$result = $this->callProtectedMethod('_getReplaceMapFromAssociations', array(), $listener);

		$this->assertSame($expected, $result);
	}

/**
 * testGetReplaceMapFromAssociationsDeepSingleRecordAssociations
 *
 * @return void
 */
	public function testGetReplaceMapFromAssociationsDeepSingleRecordAssociations() {
		$listener = $this
			->getMockBuilder('ApiTransformationListener')
			->setMethods(array('_model'))
			->disableOriginalConstructor()
			->getMock();

		$user = $this
			->getMockBuilder('Model')
			->disableOriginalConstructor()
			->setMethods(array('find'))
			->getMock();
		$user->name = $user->alias = 'User';

		$group = $this
			->getMockBuilder('Model')
			->disableOriginalConstructor()
			->setMethods(array('find'))
			->getMock();
		$group->name = $group->alias = 'Group';

		$ambassador = $this
			->getMockBuilder('Model')
			->disableOriginalConstructor()
			->setMethods(array('find'))
			->getMock();
		$ambassador->name = $ambassador->alias = 'Ambassador';

		$user->belongsTo = array('Group' => array('className' => 'Group', 'foreignKey' => 'group_id'));
		$group->hasOne = array('Ambassador' => array('className' => 'Ambassador', 'foreignKey' => 'group_id'));

		$group->Ambassador = $ambassador;
		$user->Group = $group;

		$listener
			->expects($this->once())
			->method('_model')
			->will($this->returnValue($user));

		$this->setReflectionClassInstance($listener);

		$expected = array('User' => 'user', 'Group' => 'group', 'Ambassador' => 'ambassador');
		$result = $this->callProtectedMethod('_getReplaceMapFromAssociations', array(), $listener);

		$this->assertSame($expected, $result);
	}

/**
 * testChangeNesting
 *
 * @return void
 */
	public function testChangeNesting() {
		$listener = $this
			->getMockBuilder('ApiTransformationListener')
			->disableOriginalConstructor()
			->getMock();

		$settings = array(
			'changeNesting' => true,
			'changeKeys' => false,
			'changeTime' => false,
			'castNumbers' => false,
			'keyMethods' => array(),
			'valueMethods' => array(),
			'replaceMap' => array()
		);

		$this->setReflectionClassInstance($listener);
		$this->setProtectedProperty('_settings', $settings, $listener);

		$data = array(
			'User' => array('id' => '5', 'name' => 'FriendsOfCake'),
			'Profile' => array('id' => '987', 'twitter' => '@FriendsOfCake')
		);

		$expected = array(
			'id' => '5',
			'name' => 'FriendsOfCake',
			'Profile' => array('id' => '987', 'twitter' => '@FriendsOfCake')
		);

		$result = $this->callProtectedMethod('_changeNesting', array(&$data, 'User'), $listener);

		$this->assertSame($expected, $result);

		$data = array(
			'User' => array('id' => '5', 'name' => 'FriendsOfCake'),
			'Profile' => array('id' => '987', 'twitter' => '@FriendsOfCake')
		);

		$expected = array(
			'id' => '987',
			'twitter' => '@FriendsOfCake',
			'User' => array(
				'id' => '5',
				'name' => 'FriendsOfCake',
			)
		);

		$result = $this->callProtectedMethod('_changeNesting', array(&$data, 'Profile'), $listener);

		$this->assertSame($expected, $result);
	}
}
