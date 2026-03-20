<?php

declare(strict_types=1);

use Mugiew\StarterKit\Core\Router;
use Mugiew\StarterKit\Http\Controllers\HomeController;

return static function (Router $router): void {
    $router->get('/', [HomeController::class, 'index']);
    $router->post('/demo/submit', [HomeController::class, 'submit']);
    $router->get('/health', [HomeController::class, 'health']);
};
