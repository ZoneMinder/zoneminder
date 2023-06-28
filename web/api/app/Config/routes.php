<?php
/**
 * Routes configuration
 *
 * In this file, you set up routes to your controllers and their actions.
 * Routes are very important mechanism that allows you to freely connect
 * different URLs to chosen controllers and their actions (functions).
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       app.Config
 * @since         CakePHP(tm) v 0.2.9
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Load the API / REST routes
 */
	Router::mapResources('configs');
	Router::mapResources('controls');
	Router::mapResources('events');
	Router::mapResources('event_data');
	Router::mapResources('frames');
  Router::mapResources('groups');	
	Router::mapResources('host');
	Router::mapResources('logs');
	Router::mapResources('manufacturers');
	Router::mapResources('models');
	Router::mapResources('cameramodels');
	Router::mapResources('monitors');
	Router::mapResources('servers');
	Router::mapResources('server_stats');
	Router::mapResources('snapshots');
	Router::mapResources('states');
	Router::mapResources('users');
	Router::mapResources('user_preference');
	Router::mapResources('zonepresets');
	Router::mapResources('zones');

	Router::parseExtensions();

/**
 * Here, we are connecting '/' (base path) to controller called 'Pages',
 * its action called 'display', and we pass a param to select the view file
 * to use (in this case, /app/View/Pages/home.ctp)...
 */
	Router::connect('/', array('controller' => 'pages', 'action' => 'display', 'home'));
/**
 * ...and connect the rest of 'Pages' controller's URLs.
 */
	Router::connect('/pages/*', array('controller' => 'pages', 'action' => 'display'));

/**
 * Load all plugin routes. See the CakePlugin documentation on
 * how to customize the loading of plugin routes.
 */
	CakePlugin::routes();

/**
 * Load the CakePHP default routes. Only remove this if you do not want to use
 * the built-in default routes.
 */
	require CAKE . 'Config' . DS . 'routes.php';
