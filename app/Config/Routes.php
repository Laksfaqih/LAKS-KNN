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
    $routes->get('export', 'DataLatih::export');
    $routes->post('import', 'DataLatih::import');
    $routes->post('/', 'DataLatih::store');
    $routes->get('(:num)/edit', 'DataLatih::edit/$1');
    $routes->post('(:num)', 'DataLatih::update/$1');
    $routes->post('(:num)/delete', 'DataLatih::delete/$1');
});

$routes->group('kriteria', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'Kriteria::index');
    $routes->get('create', 'Kriteria::create');
    $routes->post('/', 'Kriteria::store');
    $routes->get('(:num)/edit', 'Kriteria::edit/$1');
    $routes->post('(:num)', 'Kriteria::update/$1');
    $routes->post('(:num)/delete', 'Kriteria::delete/$1');
});

$routes->group('nilai-kriteria', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'NilaiKriteria::index');
    $routes->get('create', 'NilaiKriteria::create');
    $routes->post('import', 'NilaiKriteria::import');
    $routes->post('/', 'NilaiKriteria::store');
    $routes->get('(:num)/edit', 'NilaiKriteria::edit/$1');
    $routes->post('(:num)', 'NilaiKriteria::update/$1');
    $routes->post('(:num)/delete', 'NilaiKriteria::delete/$1');
});

$routes->group('klasifikasi', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'Klasifikasi::index');
    $routes->post('reverse-geocode', 'Klasifikasi::reverseGeocode');
    $routes->post('/', 'Klasifikasi::store');
    $routes->get('hasil/(:num)', 'Klasifikasi::show/$1');
});

$routes->get('mapping', 'Mapping::index', ['filter' => 'auth']);
