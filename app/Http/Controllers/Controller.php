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
    protected function render(
        string $view,
        array $data = [],
        int $status = 200,
        string $layout = 'layouts.app'
    ): Response
    {
        $content = $this->view->render($view, $data);
        $payload = array_merge($data, ['content' => $content]);

        return Response::html($this->view->render($layout, $payload), $status);
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function json(array $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    protected function redirect(string $path, int $status = 302): Response
    {
        return Response::redirect($path, $status);
    }
}
