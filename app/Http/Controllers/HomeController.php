<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Http\Controllers;

use Mugiew\StarterKit\Core\Response;
use Mugiew\StarterKit\Services\Auth\AuthService;
use Mugiew\StarterKit\Services\Database\DatabaseManager;
use Throwable;

final class HomeController extends Controller
{
    public function __construct(
        \Mugiew\StarterKit\Core\View $view,
        \Mugiew\StarterKit\Services\Security\CsrfManager $csrf,
        private readonly DatabaseManager $database,
        private readonly AuthService $auth,
    ) {
        parent::__construct($view, $csrf);
    }

    public function index(): Response
    {
        return $this->auth->check()
            ? $this->redirect('/dashboard')
            : $this->render('home');
    }

    public function health(): Response
    {
        $databaseStatus = 'ok';

        try {
            $this->database->connection()->query('SELECT 1');
        } catch (Throwable $exception) {
            $databaseStatus = 'error: ' . $exception->getMessage();
        }

        return $this->json([
            'status' => 'ok',
            'php' => PHP_VERSION,
            'database' => $databaseStatus,
            'session_driver' => 'redis',
            'csrf_storage' => 'redis',
            'config_cache' => 'memcached',
        ]);
    }
}
