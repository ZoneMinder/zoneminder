<?php

App::uses('CakeEvent', 'Event');
App::uses('DebugTimer', 'DebugKit.Lib');
App::uses('CrudListener', 'Crud.Controller/Crud');

/**
 * Implements timings for DebugKit / Crud
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 */
class DebugKitListener extends CrudListener {

/**
 * List of events implemented by this class
 *
 * @return array
 */
	public function implementedEvents() {
		return array(
			'Crud.startup' => array('callable' => 'startup'),
			'Crud.beforeHandle' => array('callable' => 'beforeHandle', 'priority' => 1),
			'Crud.beforeRender' => array('callable' => 'beforeRender', 'priority' => 5000),

			'Crud.beforePaginate' => array('callable' => 'beforePaginate', 'priority' => 1),
			'Crud.afterPaginate' => array('callable' => 'afterPaginate', 'priority' => 5000),

			'Crud.beforeSave' => array('callable' => 'beforeSave', 'priority' => 1),
			'Crud.afterSave' => array('callable' => 'afterSave', 'priority' => 5000),

			'Crud.beforeFind' => array('callable' => 'beforeFind', 'priority' => 1),
			'Crud.afterFind' => array('callable' => 'afterFind', 'priority' => 5000),

			'Crud.beforeDelete' => array('callable' => 'beforeDelete', 'priority' => 1),
			'Crud.afterDelete' => array('callable' => 'afterDelete', 'priority' => 5000),
		);
	}

/**
 * Start timer for Crud.beforeHandle
 *
 * And enable event logging. The Crud.startup event will not itself have been logged
 *
 * @param CakeEvent $event
 * @return void
 */
	public function startup(CakeEvent $event) {
		$this->_crud()->config('eventLogging', true);
		$this->_crud()->logEvent('Crud.startup');
	}

/**
 * Start timer for Crud.init
 *
 * And enable event logging. The Crud.initialize event will not itself have been logged
 *
 * @param CakeEvent $event
 * @return void
 */
	public function beforeHandle(CakeEvent $event) {
		parent::beforeHandle($event);

		DebugTimer::start('Event: Crud.beforeHandle');
	}

/**
 * Stop timer for Crud.init
 *
 * @param CakeEvent $event
 * @return void
 */
	public function beforeRender(CakeEvent $event) {
		DebugTimer::stop('Event: Crud.beforeHandle');
	}

/**
 * Start timer for Crud.Paginate
 *
 * @param CakeEvent $event
 * @return void
 */
	public function beforePaginate(CakeEvent $event) {
		DebugTimer::start('Event: Crud.Paginate');
	}

/**
 * Stop timer for Crud.Paginate
 *
 * @param CakeEvent $event
 * @return void
 */
	public function afterPaginate(CakeEvent $event) {
		DebugTimer::stop('Event: Crud.Paginate');
	}

/**
 * Start timer for Crud.Save
 *
 * @param CakeEvent $event
 * @return void
 */
	public function beforeSave(CakeEvent $event) {
		DebugTimer::start('Event: Crud.Save');
	}

/**
 * Stop timer for Crud.Save
 *
 * @param CakeEvent $event
 * @return void
 */
	public function afterSave(CakeEvent $event) {
		DebugTimer::stop('Event: Crud.Save');
	}

/**
 * Start timer for Crud.Find
 *
 * @param CakeEvent $event
 * @return void
 */
	public function beforeFind(CakeEvent $event) {
		DebugTimer::start('Event: Crud.Find');
	}

/**
 * Stop timer for Crud.Find
 *
 * @param CakeEvent $event
 * @return void
 */
	public function afterFind(CakeEvent $event) {
		DebugTimer::stop('Event: Crud.Find');
	}

/**
 * Start timer for Crud.Delete
 *
 * @param CakeEvent $event
 * @return void
 */
	public function beforeDelete(CakeEvent $event) {
		DebugTimer::start('Event: Crud.Delete');
	}

/**
 * Stop timer for Crud.Delete
 *
 * @param CakeEvent $event
 * @return void
 */
	public function afterDelete(CakeEvent $event) {
		DebugTimer::stop('Event: Crud.Delete');
	}

}
