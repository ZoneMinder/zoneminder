<?php

App::uses('CakeEvent', 'Event');
App::uses('Controller', 'Controller');
App::uses('CrudSubject', 'Crud.Controller/Crud');
App::uses('ScaffoldListener', 'Crud.Controller/Crud/Listener');

require_once CAKE . DS . 'Test' . DS . 'Case' . DS . 'Model' . DS . 'models.php';

/**
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 */
class ScaffoldListenerTest extends CakeTestCase {

/**
 * fixtures property
 *
 * @var array
 */
	public $fixtures = array(
		'core.article',
		'core.user',
		'core.comment',
		'core.join_thing',
		'core.tag',
		'core.attachment'
	);

/**
 * Data used for beforeRenderProvider to setup
 * the tests and environments
 *
 * @var array
 */
	protected $_beforeRenderTests = array(
		// Index (Article)
		array(
			'model' => 'Article',
			'action' => 'index',
			'controller' => 'ArticlesController',
			'expected' => array(
				'title_for_layout' => 'Scaffold :: Index :: ',
				'modelClass' => 'Article',
				'primaryKey' => 'id',
				'displayField' => 'title',
				'singularVar' => 'article',
				'pluralVar' => 'articlesController',
				'singularHumanName' => 'Article',
				'pluralHumanName' => 'Articles Controller',
				'scaffoldFields' => array(
					'id', 'user_id', 'title', 'body', 'published', 'created', 'updated'
				),
				'associations' => array(
					'belongsTo' => array(
						'User' => array(
							'primaryKey' => 'id',
							'displayField' => 'id',
							'foreignKey' => 'user_id',
							'plugin' => null,
							'controller' => 'users'
						),
					),
					'hasMany' => array(
						'Comment' => array(
							'primaryKey' => 'id',
							'displayField' => 'id',
							'foreignKey' => 'article_id',
							'plugin' => null,
							'controller' => 'comments'
						)
					),
					'hasAndBelongsToMany' => array(
						'Tag' => array(
							'primaryKey' => 'id',
							'displayField' => 'id',
							'foreignKey' => 'article_id',
							'plugin' => null,
							'controller' => 'tags',
							'with' => 'ArticlesTag',
						)
					)
				)
			)
		),
		// Add (Article)
		array(
			'model' => 'Article',
			'action' => 'add',
			'controller' => 'ArticlesController',
			'expected' => array(
				'title_for_layout' => 'Scaffold :: Add :: ',
				'modelClass' => 'Article',
				'primaryKey' => 'id',
				'displayField' => 'title',
				'singularVar' => 'article',
				'pluralVar' => 'articlesController',
				'singularHumanName' => 'Article',
				'pluralHumanName' => 'Articles Controller',
				'scaffoldFields' => array(
					'id', 'user_id', 'title', 'body', 'published', 'created', 'updated'
				),
				'associations' => array(
					'belongsTo' => array(
						'User' => array(
							'primaryKey' => 'id',
							'displayField' => 'id',
							'foreignKey' => 'user_id',
							'plugin' => null,
							'controller' => 'users'
						),
					),
					'hasMany' => array(
						'Comment' => array(
							'primaryKey' => 'id',
							'displayField' => 'id',
							'foreignKey' => 'article_id',
							'plugin' => null,
							'controller' => 'comments'
						)
					),
					'hasAndBelongsToMany' => array(
						'Tag' => array(
							'primaryKey' => 'id',
							'displayField' => 'id',
							'foreignKey' => 'article_id',
							'plugin' => null,
							'controller' => 'tags',
							'with' => 'ArticlesTag',
						)
					)
				)
			)
		),
		// Edit (Article)
		array(
			'model' => 'Article',
			'action' => 'edit',
			'controller' => 'ArticlesController',
			'expected' => array(
				'title_for_layout' => 'Scaffold :: Edit :: ',
				'modelClass' => 'Article',
				'primaryKey' => 'id',
				'displayField' => 'title',
				'singularVar' => 'article',
				'pluralVar' => 'articlesController',
				'singularHumanName' => 'Article',
				'pluralHumanName' => 'Articles Controller',
				'scaffoldFields' => array(
					'id', 'user_id', 'title', 'body', 'published', 'created', 'updated'
				),
				'associations' => array(
					'belongsTo' => array(
						'User' => array(
							'primaryKey' => 'id',
							'displayField' => 'id',
							'foreignKey' => 'user_id',
							'plugin' => null,
							'controller' => 'users'
						),
					),
					'hasMany' => array(
						'Comment' => array(
							'primaryKey' => 'id',
							'displayField' => 'id',
							'foreignKey' => 'article_id',
							'plugin' => null,
							'controller' => 'comments'
						)
					),
					'hasAndBelongsToMany' => array(
						'Tag' => array(
							'primaryKey' => 'id',
							'displayField' => 'id',
							'foreignKey' => 'article_id',
							'plugin' => null,
							'controller' => 'tags',
							'with' => 'ArticlesTag',
						)
					)
				)
			)
		),
		// Index (User)
		array(
			'model' => 'User',
			'action' => 'index',
			'controller' => 'UsersController',
			'expected' => array(
				'title_for_layout' => 'Scaffold :: Index :: ',
				'modelClass' => 'User',
				'primaryKey' => 'id',
				'displayField' => 'id',
				'singularVar' => 'user',
				'pluralVar' => 'usersController',
				'singularHumanName' => 'User',
				'pluralHumanName' => 'Users Controller',
				'scaffoldFields' => array(
					'id', 'user', 'password', 'created', 'updated'
				),
				'associations' => array(
				)
			)
		),
		// Index (Comment)
		array(
			'model' => 'Comment',
			'action' => 'index',
			'controller' => 'CommentsController',
			'expected' => array(
				'title_for_layout' => 'Scaffold :: Index :: ',
				'modelClass' => 'Comment',
				'primaryKey' => 'id',
				'displayField' => 'id',
				'singularVar' => 'comment',
				'pluralVar' => 'commentsController',
				'singularHumanName' => 'Comment',
				'pluralHumanName' => 'Comments Controller',
				'scaffoldFields' => array(
					'id', 'article_id', 'user_id', 'comment', 'published', 'created', 'updated'
				),
				'associations' => array(
					'belongsTo' => array(
						'User' => array(
							'primaryKey' => 'id',
							'displayField' => 'id',
							'foreignKey' => 'user_id',
							'plugin' => null,
							'controller' => 'users'
						),
						'Article' => array(
							'primaryKey' => 'id',
							'displayField' => 'title',
							'foreignKey' => 'article_id',
							'plugin' => null,
							'controller' => 'articles'
						)
					),
					'hasOne' => array(
						'Attachment' => array(
							'primaryKey' => 'id',
							'displayField' => 'id',
							'foreignKey' => 'comment_id',
							'plugin' => null,
							'controller' => 'attachments'
						)
					)
				)
			)
		)
	);

/**
 * Data provider for testBeforeRender
 *
 * Setup the required classes and their
 * relations
 *
 * @return array
 */
	public function beforeRenderProvider() {
		$data = array();

		foreach ($this->_beforeRenderTests as $test) {
			$Request = new CakeRequest(null, false);
			$Request->action = $test['action'];

			$Controller = new Controller($Request);
			$Controller->name = $test['controller'];
			$Controller->modelClass = $test['model'];

			$Model = new $test['model']();

			$Subject = new CrudSubject();
			$Subject->model = $Model;
			$Subject->request = $Request;
			$Subject->controller = $Controller;

			$Event = new CakeEvent('Crud.beforeRender', $Subject);

			$Listener = $this->getMock('ScaffoldListener', null, array($Subject));

			$data[] = array($Listener, $Event, $test['expected']);
		}

		return $data;
	}

/**
 * test that the proper names and variable values are set by Scaffold
 *
 * @dataProvider beforeRenderProvider
 * @param CrudListener $Listener
 * @param CakeEvent $Event
 * @param array $expected
 * @return void
 */
	public function testBeforeRender($Listener, $Event, $expected) {
		$Listener->beforeRender($Event);
		$this->assertEquals($expected, $Event->subject->controller->viewVars);
	}

/**
 * Test that implementedEvents return the correct events
 *
 * @return void
 */
	public function testImplementedEvents() {
		$Subject = new CrudSubject();
		$Listener = $this->getMock('ScaffoldListener', null, array($Subject));

		$expected = array(
			'Crud.beforeRender' => 'beforeRender',
			'Crud.beforeFind' => 'beforeFind',
			'Crud.beforePaginate' => 'beforePaginate'
		);

		$result = $Listener->implementedEvents();
		$this->assertEquals($expected, $result);
	}

}
