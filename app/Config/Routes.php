<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 **/
$routes->get('/', 'Login::index');
$routes->group('login', [], function ($routes) {
    $routes->get('/', 'Login::index');
    $routes->post('auth', 'Login::Auth');
});

$routes->group('dashboard', [], function ($routes) {
    $routes->get('/', 'Dashboard::index');
    $routes->get('dashboard', 'Dashboard::index');
});
