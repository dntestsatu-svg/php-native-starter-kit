<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Http\Middlewares;

use Mugiew\StarterKit\Core\MiddlewareInterface;
use Mugiew\StarterKit\Core\Request;
use Mugiew\StarterKit\Core\Response;
use Mugiew\StarterKit\Core\View;
use Mugiew\StarterKit\Services\Security\CsrfManager;

final class CsrfMiddleware implements MiddlewareInterface
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private readonly CsrfManager $csrf,
        private readonly View $view,
        private readonly array $config,
    ) {}

    public function process(Request $request, callable $next): Response
    {
        if ($this->isSafeMethod($request->method()) || $this->isExcludedPath($request->path())) {
            return $next($request);
        }

        if ($this->csrf->verify($request)) {
            return $next($request);
        }

        return Response::html($this->view->render('errors.419'), 419);
    }

    private function isSafeMethod(string $method): bool
    {
        return in_array($method, ['GET', 'HEAD', 'OPTIONS'], true);
    }

    private function isExcludedPath(string $path): bool
    {
        $except = $this->config['except'] ?? [];

        if (!is_array($except) || $except === []) {
            return false;
        }

        foreach ($except as $pattern) {
            if (!is_string($pattern) || $pattern === '') {
                continue;
            }

            if ($pattern === $path) {
                return true;
            }

            if (str_ends_with($pattern, '*')) {
                $prefix = rtrim(substr($pattern, 0, -1), '/');

                if ($prefix === '' || str_starts_with($path, $prefix)) {
                    return true;
                }
            }
        }

        return false;
    }
}
