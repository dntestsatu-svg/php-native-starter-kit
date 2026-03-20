<?php

declare(strict_types=1);

use Mugiew\StarterKit\Core\Router;
use Mugiew\StarterKit\Http\Controllers\AuthController;
use Mugiew\StarterKit\Http\Controllers\DashboardController;
use Mugiew\StarterKit\Http\Controllers\HomeController;
use Mugiew\StarterKit\Http\Middlewares\AuthMiddleware;
use Mugiew\StarterKit\Http\Middlewares\GuestMiddleware;
use Mugiew\StarterKit\Http\Middlewares\RateLimiterMiddleware;

return static function (Router $router): void {
    $router->get('/', [HomeController::class, 'index']);
    $router->get('/health', [HomeController::class, 'health']);

    $router->get('/login', [AuthController::class, 'showLogin'], [GuestMiddleware::class]);
    $router->post('/login', [AuthController::class, 'login'], [GuestMiddleware::class, RateLimiterMiddleware::class]);
    $router->get('/register', [AuthController::class, 'showRegister'], [GuestMiddleware::class]);
    $router->post('/register', [AuthController::class, 'register'], [GuestMiddleware::class, RateLimiterMiddleware::class]);

    $router->post('/logout', [AuthController::class, 'logout'], [AuthMiddleware::class]);
    $router->get('/dashboard', [DashboardController::class, 'index'], [AuthMiddleware::class]);
    $router->get('/dashboard/users/create', [DashboardController::class, 'showCreate'], [AuthMiddleware::class]);
    $router->post('/dashboard/users', [DashboardController::class, 'store'], [AuthMiddleware::class]);
    $router->get('/dashboard/users/edit', [DashboardController::class, 'showEdit'], [AuthMiddleware::class]);
    $router->post('/dashboard/users/update', [DashboardController::class, 'update'], [AuthMiddleware::class]);
    $router->post('/dashboard/users/delete', [DashboardController::class, 'destroy'], [AuthMiddleware::class]);
};
