<?php

namespace Config;

// Create a new instance of our RouteCollection class.
$routes = Services::routes();

// Load the system's routing file first, so that the app and ENVIRONMENT
// can override as needed.
if (file_exists(SYSTEMPATH . 'Config/Routes.php')) {
    require SYSTEMPATH . 'Config/Routes.php';
}

/*
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Dashboard');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
$routes->setAutoRoute(false);

/*
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */

// Authentication routes remain public.
$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::attempt');

// All application and mutation routes require an authenticated session.
$routes->group('', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'Dashboard::index');
    $routes->get('dashboard', 'Dashboard::index');
    $routes->get('monitoring', 'Monitoring::index');
    $routes->get('monitoring/(:segment)/(:segment)', 'Monitoring::showTable/$1/$2');
    $routes->get('monitoring/(:segment)', 'Monitoring::show/$1');
    $routes->get('tasks', 'TaskRegistry::index');
    $routes->get('tasks/new', 'TaskRegistry::create');
    $routes->post('tasks', 'TaskRegistry::store');
    $routes->get('tasks/edit/(:num)', 'TaskRegistry::edit/$1');
    $routes->post('tasks/update/(:num)', 'TaskRegistry::update/$1');
    $routes->post('tasks/delete/(:num)', 'TaskRegistry::delete/$1');
    $routes->post('tasks/toggle/(:num)', 'TaskRegistry::toggle/$1');
    $routes->get('logs', 'SyncLogs::index');
    $routes->post('logout', 'Auth::logout');
    $routes->post('api/sync/run/(:segment)', 'Api\\SyncController::run/$1');
});

/*
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 *
 * There will often be times that you need additional routing and you
 * need it to be able to override any defaults in this file. Environment
 * based routes is one such time. require() additional route files here
 * to make that happen.
 *
 * You will have access to the $routes object within that file without
 * needing to reload it.
 */
if (file_exists(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
