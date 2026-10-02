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

    // Monitoring matrix (pengganti modul Monitoring/pipeline lama).
    $routes->group('monitoring', static function ($routes) {
        $routes->get('', 'MonitoringMatrix::index');
        $routes->get('workflow', 'MonitoringMatrix::workflow');
        $routes->post('check-all', 'MonitoringMatrix::checkAll');
        $routes->post('check-table/(:num)', 'MonitoringMatrix::checkTable/$1');
        $routes->get('history/(:num)/(:num)', 'MonitoringMatrix::history/$1/$2');
    });

    // Registry watched tables.
    $routes->group('watched-tables', static function ($routes) {
        $routes->get('', 'WatchedTableRegistry::index');
        $routes->get('new', 'WatchedTableRegistry::create');
        $routes->get('columns', 'WatchedTableRegistry::columns');
        $routes->post('', 'WatchedTableRegistry::store');
        $routes->get('edit/(:num)', 'WatchedTableRegistry::edit/$1');
        $routes->post('update/(:num)', 'WatchedTableRegistry::update/$1');
        $routes->post('toggle/(:num)', 'WatchedTableRegistry::toggle/$1');
        $routes->post('delete/(:num)', 'WatchedTableRegistry::delete/$1');
    });

    $routes->get('logs', 'SyncLogs::index');
    $routes->post('logout', 'Auth::logout');
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
