<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Core;

use ReflectionFunction;
use ReflectionMethod;
use RuntimeException;
use Throwable;

final class Router
{
    /**
     * @var array<string, array<string, callable|array{0: class-string, 1: string}>>
     */
    private array $routes = [];

    /**
     * @param callable|array{0: class-string, 1: string} $action
     */
    public function add(string $method, string $uri, callable|array $action): self
    {
        $method = strtoupper($method);
        $uri = $this->normalizePath($uri);
        $this->routes[$method][$uri] = $action;

        return $this;
    }

    /**
     * @param callable|array{0: class-string, 1: string} $action
     */
    public function get(string $uri, callable|array $action): self
    {
        return $this->add('GET', $uri, $action);
    }

    /**
     * @param callable|array{0: class-string, 1: string} $action
     */
    public function post(string $uri, callable|array $action): self
    {
        return $this->add('POST', $uri, $action);
    }

    /**
     * @param callable|array{0: class-string, 1: string} $action
     */
    public function put(string $uri, callable|array $action): self
    {
        return $this->add('PUT', $uri, $action);
    }

    /**
     * @param callable|array{0: class-string, 1: string} $action
     */
    public function patch(string $uri, callable|array $action): self
    {
        return $this->add('PATCH', $uri, $action);
    }

    /**
     * @param callable|array{0: class-string, 1: string} $action
     */
    public function delete(string $uri, callable|array $action): self
    {
        return $this->add('DELETE', $uri, $action);
    }

    public function dispatch(Request $request, Container $container): Response
    {
        $method = $request->method();
        $path = $request->path();
        $action = $this->routes[$method][$path] ?? null;

        if ($action === null) {
            return $this->notFound($container);
        }

        try {
            $result = $this->invokeAction($action, $request, $container);
        } catch (Throwable $exception) {
            $message = 'Internal Server Error';

            if ($container->has('config')) {
                /** @var array<string, mixed> $config */
                $config = $container->get('config');
                $debug = (bool) (($config['app']['debug'] ?? false) === true);

                if ($debug) {
                    $message = $exception->getMessage();
                }
            }

            return Response::json([
                'status' => 'error',
                'message' => $message,
            ], 500);
        }

        if ($result instanceof Response) {
            return $result;
        }

        if (is_array($result)) {
            return Response::json($result);
        }

        return Response::html((string) $result);
    }

    private function normalizePath(string $path): string
    {
        $trimmed = '/' . trim($path, '/');

        return $trimmed === '//' ? '/' : $trimmed;
    }

    /**
     * @param callable|array{0: class-string, 1: string} $action
     */
    private function invokeAction(callable|array $action, Request $request, Container $container): mixed
    {
        if (is_array($action) && isset($action[0], $action[1]) && is_string($action[0])) {
            $instance = $container->get($action[0]);
            $callable = [$instance, $action[1]];
            $reflection = new ReflectionMethod($instance, $action[1]);
        } else {
            $callable = $action;
            $reflection = new ReflectionFunction($action);
        }

        $arguments = [];

        foreach ($reflection->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type !== null && !$type->isBuiltin()) {
                $name = $type->getName();

                if ($name === Request::class) {
                    $arguments[] = $request;
                    continue;
                }

                if ($name === Container::class) {
                    $arguments[] = $container;
                    continue;
                }

                $arguments[] = $container->get($name);
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }

            throw new RuntimeException(
                sprintf('Unable to resolve route action parameter "$%s".', $parameter->getName())
            );
        }

        return $callable(...$arguments);
    }

    private function notFound(Container $container): Response
    {
        if ($container->has(View::class)) {
            $view = $container->get(View::class);
            return Response::html($view->render('errors.404'), 404);
        }

        return Response::html('404 Not Found', 404);
    }
}
