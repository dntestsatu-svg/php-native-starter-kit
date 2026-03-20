<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Core;

use Mugiew\StarterKit\Http\Requests\FormRequest;
use Mugiew\StarterKit\Http\Requests\Validation\RequestValidationException;
use ReflectionFunction;
use ReflectionMethod;
use RuntimeException;
use Throwable;

final class Router
{
    /**
     * @var array<string, array<string, array{
     *     action: callable|array{0: class-string, 1: string},
     *     middleware: array<int, MiddlewareInterface|class-string<MiddlewareInterface>>
     * }>>
     */
    private array $routes = [];

    /**
     * @param callable|array{0: class-string, 1: string} $action
     * @param array<int, MiddlewareInterface|class-string<MiddlewareInterface>> $middlewares
     */
    public function add(string $method, string $uri, callable|array $action, array $middlewares = []): self
    {
        $method = strtoupper($method);
        $uri = $this->normalizePath($uri);
        $this->routes[$method][$uri] = [
            'action' => $action,
            'middleware' => $middlewares,
        ];

        return $this;
    }

    /**
     * @param callable|array{0: class-string, 1: string} $action
     * @param array<int, MiddlewareInterface|class-string<MiddlewareInterface>> $middlewares
     */
    public function get(string $uri, callable|array $action, array $middlewares = []): self
    {
        return $this->add('GET', $uri, $action, $middlewares);
    }

    /**
     * @param callable|array{0: class-string, 1: string} $action
     * @param array<int, MiddlewareInterface|class-string<MiddlewareInterface>> $middlewares
     */
    public function post(string $uri, callable|array $action, array $middlewares = []): self
    {
        return $this->add('POST', $uri, $action, $middlewares);
    }

    /**
     * @param callable|array{0: class-string, 1: string} $action
     * @param array<int, MiddlewareInterface|class-string<MiddlewareInterface>> $middlewares
     */
    public function put(string $uri, callable|array $action, array $middlewares = []): self
    {
        return $this->add('PUT', $uri, $action, $middlewares);
    }

    /**
     * @param callable|array{0: class-string, 1: string} $action
     * @param array<int, MiddlewareInterface|class-string<MiddlewareInterface>> $middlewares
     */
    public function patch(string $uri, callable|array $action, array $middlewares = []): self
    {
        return $this->add('PATCH', $uri, $action, $middlewares);
    }

    /**
     * @param callable|array{0: class-string, 1: string} $action
     * @param array<int, MiddlewareInterface|class-string<MiddlewareInterface>> $middlewares
     */
    public function delete(string $uri, callable|array $action, array $middlewares = []): self
    {
        return $this->add('DELETE', $uri, $action, $middlewares);
    }

    public function dispatch(Request $request, Container $container): Response
    {
        $method = $request->method();
        $path = $request->path();
        $route = $this->routes[$method][$path] ?? null;

        if ($route === null) {
            return $this->notFound($container);
        }

        try {
            $runner = fn (Request $resolvedRequest): Response => $this->normalizeResponse(
                $this->invokeAction($route['action'], $resolvedRequest, $container)
            );

            $pipeline = array_reduce(
                array_reverse($route['middleware']),
                /**
                 * @param callable(Request): Response $next
                 * @param MiddlewareInterface|class-string<MiddlewareInterface> $middlewareEntry
                 * @return callable(Request): Response
                 */
                static function (callable $next, MiddlewareInterface|string $middlewareEntry) use ($container): callable {
                    $middleware = is_string($middlewareEntry) ? $container->get($middlewareEntry) : $middlewareEntry;

                    if (!$middleware instanceof MiddlewareInterface) {
                        throw new RuntimeException('Route middleware must implement MiddlewareInterface.');
                    }

                    return static fn (Request $resolvedRequest): Response => $middleware->process($resolvedRequest, $next);
                },
                $runner
            );

            return $pipeline($request);
        } catch (RequestValidationException $exception) {
            flash('error', $exception->getMessage());
            return Response::redirect($exception->redirectPath());
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

                if (is_subclass_of($name, FormRequest::class)) {
                    /** @var class-string<FormRequest> $name */
                    $arguments[] = $name::fromRequest($request);
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

    private function normalizeResponse(mixed $result): Response
    {
        if ($result instanceof Response) {
            return $result;
        }

        if (is_array($result)) {
            return Response::json($result);
        }

        return Response::html((string) $result);
    }
}
