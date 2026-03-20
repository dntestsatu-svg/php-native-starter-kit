<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Http\Controllers;

use Mugiew\StarterKit\Core\Request;
use Mugiew\StarterKit\Core\Response;
use Mugiew\StarterKit\Services\Database\DatabaseManager;
use Throwable;

final class HomeController extends Controller
{
    public function __construct(
        \Mugiew\StarterKit\Core\View $view,
        \Mugiew\StarterKit\Services\Security\CsrfManager $csrf,
        private readonly DatabaseManager $database,
    ) {
        parent::__construct($view, $csrf);
    }

    public function index(): Response
    {
        return $this->render('home');
    }

    public function submit(Request $request): Response
    {
        $name = trim((string) $request->input('name', ''));

        if ($name === '') {
            return $this->render('home', [
                'error' => 'Please enter your name before submitting.',
            ], 422);
        }

        return $this->render('home', [
            'name' => $name,
            'success' => sprintf('Hello %s, the MVC starter kit is working with CSRF protection.', $name),
        ]);
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
