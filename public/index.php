<?php
/**
 * iFixAdmin — front controller.
 * Rotas no formato /recurso/acao (ex: /clients/create, /orders/view?id=1).
 * Rotas públicas: /login.
 */

require __DIR__ . '/../app/config/config.php';
require APP_PATH . '/config/database.php';
require APP_PATH . '/helpers/functions.php';
require APP_PATH . '/middleware/auth.php';

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$segments = array_values(array_filter(explode('/', $path)));

$resource = $segments[0] ?? 'dashboard';
$action   = $segments[1] ?? 'index';

if (($segments[0] ?? '') === 'knowledge' && isset($segments[1], $segments[2])) {
    $action = $segments[1] . '_' . $segments[2];
}

// Toda requisição POST exige token CSRF válido.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
}

$routes = [
    'login'      => 'auth',
    'logout'     => 'auth',
    'portal'     => 'portal',
    'dashboard'  => 'dashboard',
    'clients'    => 'clients',
    'devices'    => 'devices',
    'orders'     => 'orders',
    'inventory'  => 'inventory',
    'financial'  => 'financial',
    'schematics' => 'schematics',
    'trello'     => 'trello',
    'digital-services' => 'digital_services',
    'knowledge'  => 'knowledge',
];

// Rotas acessíveis sem login.
$publicResources = ['login', 'logout', 'portal'];

if (!isset($routes[$resource])) {
    http_response_code(404);
    require_login();
    render('errors/404', ['title' => 'Página não encontrada']);
}

if ($resource === 'login') {
    $action = 'login';
} elseif ($resource === 'logout') {
    $action = 'logout';
} elseif (!in_array($resource, $publicResources, true)) {
    require_login();
}

require APP_PATH . '/controllers/' . $routes[$resource] . '.php';

$handler = $routes[$resource] . '_' . str_replace('-', '_', $action);
if (!function_exists($handler)) {
    http_response_code(404);
    render('errors/404', ['title' => 'Página não encontrada']);
}

try {
    $handler();
} catch (PDOException $exception) {
    error_log('[iFixAdmin] ' . $exception->getMessage());
    http_response_code(500);
    render('errors/500', ['title' => 'Erro interno']);
}
