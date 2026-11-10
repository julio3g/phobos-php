<?php

declare(strict_types=1);

use Phobos\Controllers\AuthController;
use Phobos\Controllers\ClientController;
use Phobos\Controllers\DashboardController;
use Phobos\Controllers\MaterialController;
use Phobos\Controllers\OrderController;
use Phobos\Controllers\ProductController;
use Phobos\Core\Env;
use Phobos\Core\Router;
use Phobos\Core\Session;

$root = dirname(__DIR__);
require $root . '/src/autoload.php';
require $root . '/src/Support/helpers.php';

Env::load($root . '/.env');
Session::start();

// cabeçalhos de segurança básicos
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

$router = new Router();

$router->add('GET', '/login', [AuthController::class, 'showLogin']);
$router->add('POST', '/login', [AuthController::class, 'login']);
$router->add('POST', '/logout', [AuthController::class, 'logout']);
$router->add('GET', '/', [DashboardController::class, 'index']);

// CRUD de cada recurso: /recurso, /recurso/create, /recurso/{id}, /recurso/{id}/edit ...
foreach ([
    'clients' => ClientController::class,
    'materials' => MaterialController::class,
    'products' => ProductController::class,
    'orders' => OrderController::class,
] as $path => $controller) {
    $router->add('GET', "/$path", [$controller, 'index']);
    $router->add('GET', "/$path/create", [$controller, 'create']);
    $router->add('POST', "/$path", [$controller, 'store']);
    $router->add('GET', "/$path/{id}", [$controller, 'show']);
    $router->add('GET', "/$path/{id}/edit", [$controller, 'edit']);
    $router->add('POST', "/$path/{id}", [$controller, 'update']);
    $router->add('POST', "/$path/{id}/delete", [$controller, 'destroy']);
}
$router->add('POST', '/materials/{id}/archive', [MaterialController::class, 'archive']);
$router->add('POST', '/products/{id}/archive', [ProductController::class, 'archive']);
$router->add('POST', '/products/{id}/recalculate', [ProductController::class, 'recalculate']);
$router->add('POST', '/orders/{id}/status', [OrderController::class, 'changeStatus']);
$router->add('POST', '/orders/{id}/payment', [OrderController::class, 'registerPayment']);

try {
    $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
} catch (Throwable $e) {
    error_log((string) $e);
    http_response_code(500);
    echo '<h1>Erro interno</h1><p>Algo deu errado. Tente novamente.</p>';
    if (Env::get('APP_DEBUG') === 'true') {
        echo '<pre>' . htmlspecialchars((string) $e) . '</pre>';
    }
}
