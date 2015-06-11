<?php

App::uses('CrudListener', 'Crud.Controller/Crud');

/**
 * When loaded Crud API Pagination Listener will include
 * pagination information in the response
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 */
class ApiPaginationListener extends CrudListener {

/**
 * Returns a list of all events that will fire in the controller during its lifecycle.
 * You can override this function to add you own listener callbacks
 *
 * We attach at priority 10 so normal bound events can run before us
 *
 * @return array
 */
	public function implementedEvents() {
		return array(
			'Crud.beforeRender' => array('callable' => 'beforeRender', 'priority' => 75)
		);
	}

/**
 * Appends the pagination information to the JSON or XML output
 *
 * @param CakeEvent $event
 * @return void
 */
	public function beforeRender(CakeEvent $event) {
		$request = $this->_request();
		if (!$request->is('api')) {
			return;
		}

		$_pagination = $request->paging;
		if (empty($_pagination) || !array_key_exists($event->subject->modelClass, $_pagination)) {
			return;
		}

		$_pagination = $_pagination[$event->subject->modelClass];

		$pagination = array(
			'page_count' => $_pagination['pageCount'],
			'current_page' => $_pagination['page'],
			'has_next_page' => $_pagination['nextPage'],
			'has_prev_page' => $_pagination['prevPage'],
			'count' => $_pagination['count'],
			'limit' => $_pagination['limit']
		);

		$this->_action()->config('serialize.pagination', 'pagination');
		$this->_controller()->set('pagination', $pagination);
	}
}
