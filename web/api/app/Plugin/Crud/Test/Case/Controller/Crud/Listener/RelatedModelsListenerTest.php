<?php

App::uses('Model', 'Model');
App::uses('Controller', 'Controller');
App::uses('TreeBehavior', 'Model/Behavior');
App::uses('CrudAction', 'Crud.Controller/Crud');
App::uses('RelatedModelsListener', 'Crud.Controller/Crud/Listener');
App::uses('CrudSubject', 'Crud.Controller/Crud');
App::uses('CrudTestCase', 'Crud.Test/Support');

/**
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 */
class RelatedModelListenerTest extends CrudTestCase {

/**
 * testModels
 *
 * @covers RelatedModelsListener::models
 * @return void
 */
	public function testModels() {
		$Action = $this
			->getMockBuilder('CrudAction')
			->disableOriginalConstructor()
			->setMethods(array('_handle'))
			->getMock();
		$Action->config('relatedModels', array('Post', 'User'));

		$Listener = $this
			->getMockBuilder('RelatedModelsListener')
			->disableOriginalConstructor()
			->setMethods(array('_action'))
			->getMock();

		$Listener
			->expects($this->once())
			->method('_action')
			->with(null)
			->will($this->returnValue($Action));

		$result = $Listener->models();
		$expected = array('Post', 'User');
		$this->assertEquals($expected, $result);
	}

/**
 * testModelsEmpty
 *
 * Test behavior when 'relatedModels' is empty
 *
 * @covers RelatedModelsListener::models
 * @return void
 */
	public function testModelsEmpty() {
		$Action = $this
			->getMockBuilder('CrudAction')
			->disableOriginalConstructor()
			->setMethods(array('_handle'))
			->getMock();
		$Action->config('relatedModels', null);

		$Listener = $this
			->getMockBuilder('RelatedModelsListener')
			->disableOriginalConstructor()
			->setMethods(array('_action'))
			->getMock();

		$Listener
			->expects($this->once())
			->method('_action')
			->with(null)
			->will($this->returnValue($Action));

		$result = $Listener->models();
		$expected = array();
		$this->assertEquals($expected, $result);
	}

/**
 * testModelsEmpty
 *
 * Test behavior when 'relatedModels' is a string
 *
 * @covers RelatedModelsListener::models
 * @return void
 */
	public function testModelsString() {
		$Action = $this
			->getMockBuilder('CrudAction')
			->disableOriginalConstructor()
			->setMethods(array('_handle'))
			->getMock();
		$Action->config('relatedModels', 'Post');

		$Listener = $this
			->getMockBuilder('RelatedModelsListener')
			->disableOriginalConstructor()
			->setMethods(array('_action'))
			->getMock();

		$Listener
			->expects($this->once())
			->method('_action')
			->with(null)
			->will($this->returnValue($Action));

		$result = $Listener->models();
		$expected = array('Post');
		$this->assertEquals($expected, $result);
	}

/**
 * testModelsTrue
 *
 * Test behavior when 'relatedModels' is true
 *
 * @covers RelatedModelsListener::models
 * @return void
 */
	public function testModelsTrue() {
		$Model = $this
			->getMockBuilder('Model')
			->disableOriginalConstructor()
			->setMethods(array('getAssociated'))
			->getMock();

		$Action = $this
			->getMockBuilder('CrudAction')
			->disableOriginalConstructor()
			->setMethods(array('_handle'))
			->getMock();
		$Action->config('relatedModels', true);

		$Listener = $this
			->getMockBuilder('RelatedModelsListener')
			->disableOriginalConstructor()
			->setMethods(array('_action', '_model'))
			->getMock();

		$Listener
			->expects($this->once())
			->method('_action')
			->with(null)
			->will($this->returnValue($Action));
		$Listener
			->expects($this->once())
			->method('_model')
			->with()
			->will($this->returnValue($Model));
		$Model
			->expects($this->at(0))
			->method('getAssociated')
			->with('belongsTo')
			->will($this->returnValue(array('Post')));
		$Model
			->expects($this->at(1))
			->method('getAssociated')
			->with('hasAndBelongsToMany')
			->will($this->returnValue(array('Tag')));

		$result = $Listener->models();
		$expected = array('Post', 'Tag');
		$this->assertEquals($expected, $result);
	}

/**
 * test_findRelatedItems
 *
 * Test behavior for a model without a TreeBehavior
 *
 * @covers RelatedModelsListener::_findRelatedItems
 * @return void
 */
	public function test_findRelatedItems() {
		$Model = $this
			->getMockBuilder('Model')
			->disableOriginalConstructor()
			->setMethods(array('find'))
			->getMock();

		$Listener = $this
			->getMockBuilder('RelatedModelsListener')
			->disableOriginalConstructor()
			->setMethods(array('_hasTreeBehavior'))
			->getMock();

		$query = array(
			'conditions' => array('Model.is_active' => true)
		);

		$data = array(
			array('Model' => array('id' => 1))
		);

		$Listener
			->expects($this->once())
			->method('_hasTreeBehavior')
			->with($Model)
			->will($this->returnValue(false));
		$Model
			->expects($this->once())
			->method('find')
			->with('list', $query)
			->will($this->returnValue($data));

		$this->setReflectionClassInstance($Listener);
		$result = $this->callProtectedMethod('_findRelatedItems', array($Model, $query), $Listener);
		$expected = $data;
		$this->assertEquals($expected, $result);
	}

/**
 * test_findRelatedItemsTreeBehavior
 *
 * Test behavior for a model with TreeBehavior
 *
 * @covers RelatedModelsListener::_findRelatedItems
 * @return void
 */
	public function test_findRelatedItemsTreeBehavior() {
		$Model = $this
			->getMockBuilder('Model')
			->disableOriginalConstructor()
			->setMethods(array('generateTreeList'))
			->getMock();

		$Listener = $this
			->getMockBuilder('RelatedModelsListener')
			->disableOriginalConstructor()
			->setMethods(array('_hasTreeBehavior'))
			->getMock();

		$query = array(
			'conditions' => array(),
			'keyPath' => 'id',
			'valuePath' => 'name',
			'spacer' => '_',
			'recursive' => -1
		);

		$data = array(
			array('Model' => array('id' => 1))
		);

		$Listener
			->expects($this->once())
			->method('_hasTreeBehavior')
			->with($Model)
			->will($this->returnValue(true));
		$Model
			->expects($this->once())
			->method('generateTreeList')
			->with(array(), 'id', 'name', '_', -1)
			->will($this->returnValue($data));

		$this->setReflectionClassInstance($Listener);
		$result = $this->callProtectedMethod('_findRelatedItems', array($Model, $query), $Listener);
		$expected = $data;
		$this->assertEquals($expected, $result);
	}

/**
 * test_getAssociationType
 *
 * @covers RelatedModelsListener::_getAssociationType
 * @return void
 */
	public function test_getAssociationType() {
		$Model = $this
			->getMockBuilder('Model')
			->disableOriginalConstructor()
			->setMethods(array('getAssociated'))
			->getMock();

		$Listener = $this
			->getMockBuilder('RelatedModelsListener')
			->disableOriginalConstructor()
			->setMethods(array('_model'))
			->getMock();
		$Listener
			->expects($this->any())
			->method('_model')
			->with()
			->will($this->returnValue($Model));
		$Model
			->expects($this->any())
			->method('getAssociated')
			->with()
			->will($this->returnValue(array('Post' => 'belongsTo', 'Tag' => 'hasMany')));

		$this->setReflectionClassInstance($Listener);
		$result = $this->callProtectedMethod('_getAssociationType', array('Post'), $Listener);
		$expected = 'belongsTo';
		$this->assertEquals($expected, $result);

		$result = $this->callProtectedMethod('_getAssociationType', array('Tag'), $Listener);
		$expected = 'hasMany';
		$this->assertEquals($expected, $result);

		$result = $this->callProtectedMethod('_getAssociationType', array('Comment'), $Listener);
		$expected = null;
		$this->assertEquals($expected, $result);
	}

/**
 * test_getModelInstance
 *
 * Test that the associated model exist in the Primary Model
 *
 * @covers RelatedModelsListener::_getModelInstance
 * @return void
 */
	public function test_getModelInstance() {
		$Model = $this
			->getMockBuilder('Model')
			->disableOriginalConstructor()
			->getMock();
		$Model->Post = 'PostModel';

		$Listener = $this
			->getMockBuilder('RelatedModelsListener')
			->disableOriginalConstructor()
			->setMethods(array('_model', '_controller'))
			->getMock();
		$Listener
			->expects($this->once())
			->method('_model')
			->with()
			->will($this->returnValue($Model));
		$Listener
			->expects($this->never())
			->method('_controller');

		$this->setReflectionClassInstance($Listener);
		$result = $this->callProtectedMethod('_getModelInstance', array('Post'), $Listener);
		$expected = 'PostModel';
		$this->assertEquals($expected, $result);
	}

/**
 * test_getModelInstanceThroughController
 *
 * Get the model from the controller
 *
 * @covers RelatedModelsListener::_getModelInstance
 * @return void
 */
	public function test_getModelInstanceThroughController() {
		$Model = $this
			->getMockBuilder('Model')
			->disableOriginalConstructor()
			->getMock();

		$PostModel = $this
			->getMockBuilder('Model')
			->disableOriginalConstructor()
			->getMock();

		$Controller = $this
			->getMockBuilder('Controller')
			->disableOriginalConstructor()
			->setMethods(array('food'))
			->getMock();
		$Controller->Post = $PostModel;

		$Listener = $this
			->getMockBuilder('RelatedModelsListener')
			->disableOriginalConstructor()
			->setMethods(array('_model', '_controller', '_classRegistryInit'))
			->getMock();
		$Listener
			->expects($this->once())
			->method('_model')
			->with()
			->will($this->returnValue($Model));
		$Listener
			->expects($this->once())
			->method('_controller')
			->with()
			->will($this->returnValue($Controller));
		$Listener
			->expects($this->never())
			->method('_classRegistryInit');

		$this->setReflectionClassInstance($Listener);
		$result = $this->callProtectedMethod('_getModelInstance', array('Post'), $Listener);
		$expected = $PostModel;
		$this->assertEquals($expected, $result);
	}

/**
 * test_getModelInstanceThroughModelAssociation
 *
 * Get the model through ClassRegistry from associated
 * model className
 *
 * @covers RelatedModelsListener::_getModelInstance
 * @return void
 */
	public function test_getModelInstanceThroughModelAssociation() {
		$Model = $this
			->getMockBuilder('Model')
			->disableOriginalConstructor()
			->getMock();
		$Model->belongsTo = array(
			'Post' => array(
				'className' => 'MyPlugin.Post'
			)
		);

		$PostModel = $this
			->getMockBuilder('Model')
			->disableOriginalConstructor()
			->getMock();

		$Controller = $this
			->getMockBuilder('Controller')
			->disableOriginalConstructor()
			->setMethods(array('food'))
			->getMock();

		$Listener = $this
			->getMockBuilder('RelatedModelsListener')
			->disableOriginalConstructor()
			->setMethods(array('_model', '_controller', '_classRegistryInit'))
			->getMock();
		$Listener
			->expects($this->once())
			->method('_model')
			->with()
			->will($this->returnValue($Model));
		$Listener
			->expects($this->once())
			->method('_controller')
			->with()
			->will($this->returnValue($Controller));
		$Listener
			->expects($this->once())
			->method('_classRegistryInit')
			->with('MyPlugin.Post')
			->will($this->returnValue($PostModel));

		$this->setReflectionClassInstance($Listener);
		$result = $this->callProtectedMethod('_getModelInstance', array('Post', 'belongsTo'), $Listener);
		$expected = $PostModel;
		$this->assertEquals($expected, $result);
	}

/**
 * test_getModelInstanceThroughClassRegistry
 *
 * Get the model directly from ClassRegistry
 *
 * @covers RelatedModelsListener::_getModelInstance
 * @return void
 */
	public function test_getModelInstanceThroughClassRegistry() {
		$Model = $this
			->getMockBuilder('Model')
			->disableOriginalConstructor()
			->getMock();

		$PostModel = $this
			->getMockBuilder('Model')
			->disableOriginalConstructor()
			->getMock();

		$Controller = $this
			->getMockBuilder('Controller')
			->disableOriginalConstructor()
			->setMethods(array('food'))
			->getMock();

		$Listener = $this
			->getMockBuilder('RelatedModelsListener')
			->disableOriginalConstructor()
			->setMethods(array('_model', '_controller', '_classRegistryInit'))
			->getMock();
		$Listener
			->expects($this->once())
			->method('_model')
			->with()
			->will($this->returnValue($Model));
		$Listener
			->expects($this->once())
			->method('_controller')
			->with()
			->will($this->returnValue($Controller));
		$Listener
			->expects($this->once())
			->method('_classRegistryInit')
			->with('Post')
			->will($this->returnValue($PostModel));

		$this->setReflectionClassInstance($Listener);
		$result = $this->callProtectedMethod('_getModelInstance', array('Post'), $Listener);
		$expected = $PostModel;
		$this->assertEquals($expected, $result);
	}

/**
 * test_getBaseQuery
 *
 * Test a belongsTo relation
 *
 * @covers RelatedModelsListener::_getBaseQuery
 * @return void
 */
	public function test_getBaseQuery() {
		$Model = $this
			->getMockBuilder('Model')
			->disableOriginalConstructor()
			->getMock();
		$Model->belongsTo = array('Post' => array('conditions' => array('is_active' => true)));

		$Associated = $this
			->getMockBuilder('Model')
			->disableOriginalConstructor()
			->getMock();
		$Associated->alias = 'Post';

		$Listener = $this
			->getMockBuilder('RelatedModelsListener')
			->disableOriginalConstructor()
			->setMethods(array('_model', '_hasTreeBehavior'))
			->getMock();
		$Listener
			->expects($this->once())
			->method('_model')
			->with()
			->will($this->returnValue($Model));
		$Listener
			->expects($this->once())
			->method('_hasTreeBehavior')
			->with($Associated)
			->will($this->returnValue(false));

		$this->setReflectionClassInstance($Listener);
		$result = $this->callProtectedMethod('_getBaseQuery', array($Associated, 'belongsTo'), $Listener);
		$expected = array('conditions' => array(array('is_active' => true)));
		$this->assertEquals($expected, $result);
	}

/**
 * test_getBaseQueryHasMany
 *
 * Test a hasMany relation that no conditions
 * will be added by default
 *
 * @covers RelatedModelsListener::_getBaseQuery
 * @return void
 */
	public function test_getBaseQueryHasMany() {
		$Model = $this
			->getMockBuilder('Model')
			->disableOriginalConstructor()
			->getMock();

		$Associated = $this
			->getMockBuilder('Model')
			->disableOriginalConstructor()
			->getMock();
		$Associated->alias = 'Post';

		$Listener = $this
			->getMockBuilder('RelatedModelsListener')
			->disableOriginalConstructor()
			->setMethods(array('_model', '_hasTreeBehavior'))
			->getMock();
		$Listener
			->expects($this->never())
			->method('_model');
		$Listener
			->expects($this->once())
			->method('_hasTreeBehavior')
			->with($Associated)
			->will($this->returnValue(false));

		$this->setReflectionClassInstance($Listener);
		$result = $this->callProtectedMethod('_getBaseQuery', array($Associated, 'hasMany'), $Listener);
		$expected = array();
		$this->assertEquals($expected, $result);
	}

/**
 * test_getBaseQueryTreeBehavior
 *
 * Test a relation where associated model has
 * TreeBehavior bound
 *
 * @covers RelatedModelsListener::_getBaseQuery
 * @return void
 */
	public function test_getBaseQueryTreeBehavior() {
		$Model = $this
			->getMockBuilder('Model')
			->disableOriginalConstructor()
			->getMock();

		$Associated = $this
			->getMockBuilder('Model')
			->disableOriginalConstructor()
			->getMock();
		$Associated->alias = 'Post';

		$Behavior = $this
			->getMockBuilder('TreeBehavior')
			->disableOriginalConstructor()
			->getMock();
		$Behavior->settings['Post'] = array(
			'recursive' => -1,
			'scope' => array('is_active' => true)
		);

		$Listener = $this
			->getMockBuilder('RelatedModelsListener')
			->disableOriginalConstructor()
			->setMethods(array('_model', '_hasTreeBehavior', '_getTreeBehavior'))
			->getMock();
		$Listener
			->expects($this->never())
			->method('_model');
		$Listener
			->expects($this->once())
			->method('_hasTreeBehavior')
			->with($Associated)
			->will($this->returnValue(true));
		$Listener
			->expects($this->once())
			->method('_getTreeBehavior')
			->with($Associated)
			->will($this->returnValue($Behavior));

		$this->setReflectionClassInstance($Listener);
		$result = $this->callProtectedMethod('_getBaseQuery', array($Associated), $Listener);
		$expected = array(
			'keyPath' => null,
			'valuePath' => null,
			'spacer' => '_',
			'recursive' => -1,
			'conditions' => array(array('is_active' => true))
		);
		$this->assertEquals($expected, $result);
	}

/**
 * testPublishRelatedModels
 *
 * @covers RelatedModelsListener::publishRelatedModels
 * @return void
 */
	public function testPublishRelatedModels() {
		$Controller = $this
			->getMockBuilder('Controller')
			->disableOriginalConstructor()
			->setMethods(array('set'))
			->getMock();

		$Post = $this
			->getMockBuilder('Model')
			->disableOriginalConstructor()
			->getMock();
		$Post->alias = 'Post';

		$postQuery = array('conditions' => array('is_active' => true));

		$i = 0;

		$Listener = $this
			->getMockBuilder('RelatedModelsListener')
			->disableOriginalConstructor()
			->setMethods(array(
				'models', '_controller', '_getAssociationType', '_getModelInstance',
				'_getBaseQuery', '_trigger', '_findRelatedItems'
			))
			->getMock();
		$Listener
			->expects($this->at($i++))
			->method('models')
			->with(null)
			->will($this->returnValue(array('Post')));
		$Listener
			->expects($this->at($i++))
			->method('_controller')
			->with()
			->will($this->returnValue($Controller));
		$Listener
			->expects($this->at($i++))
			->method('_getAssociationType')
			->with('Post')
			->will($this->returnValue('belongsTo'));
		$Listener
			->expects($this->at($i++))
			->method('_getModelInstance')
			->with('Post', 'belongsTo')
			->will($this->returnValue($Post));
		$Listener
			->expects($this->at($i++))
			->method('_getBaseQuery')
			->with($Post, 'belongsTo')
			->will($this->returnValue($postQuery));
		$Listener
			->expects($this->at($i++))
			->method('_trigger')
			->with('beforeRelatedModel', array('modelName' => 'Post', 'query' => $postQuery, 'viewVar' => 'posts', 'associationType' => 'belongsTo', 'associatedModel' => $Post))
			->will($this->returnValue(new CrudSubject(array('query' => $postQuery + array('_callback' => true)))));
		$Listener
			->expects($this->at($i++))
			->method('_findRelatedItems')
			->with($Post, $postQuery + array('_callback' => true))
			->will($this->returnValue(array(1, 2, 3)));
		$Listener
			->expects($this->at($i++))
			->method('_trigger')
			->with('afterRelatedModel', array('modelName' => 'Post', 'items' => array(1, 2, 3), 'viewVar' => 'posts', 'associationType' => 'belongsTo', 'associatedModel' => $Post))
			->will($this->returnValue(new CrudSubject(array('items' => array(1, 2, 3), 'viewVar' => 'posts'))));
		$Controller
			->expects($this->once())
			->method('set')
			->with('posts', array(1, 2, 3));

		$Listener->publishRelatedModels();
	}

/**
 * testPublishRelatedModelsNoModels
 *
 * Test that nothing happens if the related models
 * array is empty
 *
 * @covers RelatedModelsListener::publishRelatedModels
 * @return void
 */
	public function testPublishRelatedModelsNoModels() {
		$Listener = $this
			->getMockBuilder('RelatedModelsListener')
			->disableOriginalConstructor()
			->setMethods(array('models', '_controller'))
			->getMock();
		$Listener
			->expects($this->once())
			->method('models')
			->with(null)
			->will($this->returnValue(false));
		$Listener
			->expects($this->never())
			->method('_controller');

		$Listener->publishRelatedModels();
	}

/**
 * testPublishRelatedModelsViewVarExists
 *
 * Test that nothing will be done if the related models
 * viewVar already exists in Controller::$viewVars
 *
 * @covers RelatedModelsListener::publishRelatedModels
 * @return void
 */
	public function testPublishRelatedModelsViewVarExists() {
		$Controller = $this
			->getMockBuilder('Controller')
			->disableOriginalConstructor()
			->setMethods(array('set'))
			->getMock();
		$Controller->viewVars['posts'] = array(1, 2, 3);

		$Post = $this
			->getMockBuilder('Model')
			->disableOriginalConstructor()
			->getMock();
		$Post->alias = 'Post';

		$postQuery = array('conditions' => array('is_active' => true));

		$i = 0;

		$Listener = $this
			->getMockBuilder('RelatedModelsListener')
			->disableOriginalConstructor()
			->setMethods(array(
				'models', '_controller', '_getAssociationType', '_getModelInstance',
				'_getBaseQuery', '_trigger', '_findRelatedItems'
			))
			->getMock();
		$Listener
			->expects($this->at($i++))
			->method('models')
			->with(null)
			->will($this->returnValue(array('Post')));
		$Listener
			->expects($this->at($i++))
			->method('_controller')
			->with()
			->will($this->returnValue($Controller));
		$Listener
			->expects($this->at($i++))
			->method('_getAssociationType')
			->with('Post')
			->will($this->returnValue('belongsTo'));
		$Listener
			->expects($this->at($i++))
			->method('_getModelInstance')
			->with('Post', 'belongsTo')
			->will($this->returnValue($Post));
		$Listener
			->expects($this->never())
			->method('_getBaseQuery');
		$Listener
			->expects($this->never())
			->method('_trigger');
		$Listener
			->expects($this->never())
			->method('_findRelatedItems');
		$Controller
			->expects($this->never())
			->method('set');

		$Listener->publishRelatedModels();
	}

}
