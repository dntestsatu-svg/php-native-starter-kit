<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Http\Middlewares;

use Mugiew\StarterKit\Core\MiddlewareInterface;
use Mugiew\StarterKit\Core\Request;
use Mugiew\StarterKit\Core\Response;
use Mugiew\StarterKit\Services\Auth\AuthService;

final class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly AuthService $auth,
    ) {}

    #[\Override]
    public function process(Request $request, callable $next): Response
    {
        if (!$this->auth->check()) {
            flash('error', 'Please log in to continue.');
            return Response::redirect('/login');
        }

        return $next($request);
    }
}
