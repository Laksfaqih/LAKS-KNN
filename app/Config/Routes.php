<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 **/
$routes->get('/', 'Login::index');
$routes->group('login', [], function ($routes) {
    $routes->get('/', 'Login::index');
    $routes->post('auth', 'Login::auth');
});
$routes->get('logout', 'Login::logout', ['filter' => 'auth']);

$routes->group('dashboard', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'Dashboard::index');
});

$routes->group('data-latih', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'DataLatih::index');
    $routes->get('create', 'DataLatih::create');
    $routes->post('import', 'DataLatih::import');
    $routes->post('/', 'DataLatih::store');
    $routes->get('(:num)/edit', 'DataLatih::edit/$1');
    $routes->post('(:num)', 'DataLatih::update/$1');
    $routes->post('(:num)/delete', 'DataLatih::delete/$1');
});
