<?php
/**
 * Enable Crud to catch MissingActionException and attempt to generate response
 * using Crud.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 */
trait CrudControllerTrait {

/**
 * List of components that are capable of dispatching an action that is
 * not already implemented
 *
 * @var string
 */
	public $dispatchComponents = array();

/**
 * Dispatches the controller action. Checks that the action exists and isn't private.
 *
 * If CakePHP raises MissingActionException we attempt to execute Crud
 *
 * @param CakeRequest $request
 * @return mixed The resulting response.
 * @throws PrivateActionException When actions are not public or prefixed by _
 * @throws MissingActionException When actions are not defined and scaffolding and CRUD is not enabled.
 */
	public function invokeAction(CakeRequest $request) {
		try {
			return parent::invokeAction($request);
		} catch (MissingActionException $e) {
			if (!empty($this->dispatchComponents)) {
				foreach ($this->dispatchComponents as $component => $enabled) {
					if (empty($enabled)) {
						continue;
					}

					// Skip if isActionMapped isn't defined in the Component
					if (!method_exists($this->{$component}, 'isActionMapped')) {
						continue;
					}

					// Skip if the action isn't mapped
					if (!$this->{$component}->isActionMapped($request->action)) {
						continue;
					}

					// Skip if execute isn't defined in the Component
					if (!method_exists($this->{$component}, 'execute')) {
						continue;
					}

					// Execute the callback, should return CakeResponse object
					return $this->{$component}->execute();
				}
			}

			// No additional callbacks, re-throw the normal CakePHP exception
			throw $e;
		}
	}

}
