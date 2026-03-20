<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Core;

final class Application
{
    /**
     * @var array<int, MiddlewareInterface>
     */
    private array $middlewares = [];

    public function __construct(
        private readonly Container $container,
        private readonly Router $router,
    ) {}

    public function addMiddleware(MiddlewareInterface $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    public function container(): Container
    {
        return $this->container;
    }

    public function run(): never
    {
        $response = $this->handle(Request::capture());
        $response->send();
        exit(0);
    }

    public function handle(Request $request): Response
    {
        $kernel = fn (Request $resolvedRequest): Response => $this->router->dispatch($resolvedRequest, $this->container);

        $pipeline = array_reduce(
            array_reverse($this->middlewares),
            /**
             * @param callable(Request): Response $next
             * @return callable(Request): Response
             */
            static fn (callable $next, MiddlewareInterface $middleware): callable => static fn (Request $resolvedRequest): Response => $middleware->process($resolvedRequest, $next),
            $kernel
        );

        return $pipeline($request);
    }
}
