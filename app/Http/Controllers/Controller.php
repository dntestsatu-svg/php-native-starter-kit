<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Http\Controllers;

use Mugiew\StarterKit\Core\Response;
use Mugiew\StarterKit\Core\View;
use Mugiew\StarterKit\Services\Security\CsrfManager;

abstract class Controller
{
    public function __construct(
        protected readonly View $view,
        protected readonly CsrfManager $csrf,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    protected function render(string $view, array $data = [], int $status = 200): Response
    {
        return Response::html($this->view->render($view, $data), $status);
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function json(array $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }
}
