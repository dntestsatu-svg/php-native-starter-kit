<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Core;

final class Request
{
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $body
     * @param array<string, mixed> $server
     * @param array<string, mixed> $cookies
     * @param array<string, mixed> $files
     * @param array<string, string> $headers
     */
    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $query,
        private readonly array $body,
        private readonly array $server,
        private readonly array $cookies,
        private readonly array $files,
        private readonly array $headers,
    ) {}

    public static function capture(): self
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $path = self::normalizePath((string) ($_SERVER['REQUEST_URI'] ?? '/'));
        $query = $_GET;
        $body = self::parseBody($method);
        $headers = self::collectHeaders($_SERVER);

        return new self($method, $path, $query, $body, $_SERVER, $_COOKIE, $_FILES, $headers);
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    /**
     * @return array<string, mixed>
     */
    public function query(): array
    {
        return $this->query;
    }

    /**
     * @return array<string, mixed>
     */
    public function body(): array
    {
        return $this->body;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    public function input(string $key, mixed $default = null): mixed
    {
        $data = $this->all();

        return array_key_exists($key, $data) ? $data[$key] : $default;
    }

    public function header(string $name, ?string $default = null): ?string
    {
        $normalized = strtoupper(str_replace('-', '_', $name));

        return $this->headers[$normalized] ?? $default;
    }

    public function isMethod(string $method): bool
    {
        return strtoupper($method) === $this->method;
    }

    /**
     * @return array<string, mixed>
     */
    public function server(): array
    {
        return $this->server;
    }

    private static function normalizePath(string $requestUri): string
    {
        $path = (string) parse_url($requestUri, PHP_URL_PATH);
        $path = '/' . trim($path, '/');

        return $path === '//' ? '/' : $path;
    }

    /**
     * @return array<string, mixed>
     */
    private static function parseBody(string $method): array
    {
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return [];
        }

        if (!empty($_POST)) {
            return $_POST;
        }

        $rawBody = file_get_contents('php://input');

        if ($rawBody === false || $rawBody === '') {
            return [];
        }

        $contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? '');

        if (str_contains(strtolower($contentType), 'application/json')) {
            $decoded = json_decode($rawBody, true);
            return is_array($decoded) ? $decoded : [];
        }

        parse_str($rawBody, $parsed);

        return is_array($parsed) ? $parsed : [];
    }

    /**
     * @param array<string, mixed> $server
     * @return array<string, string>
     */
    private static function collectHeaders(array $server): array
    {
        $headers = [];

        if (function_exists('getallheaders')) {
            /** @var array<string, string> $rawHeaders */
            $rawHeaders = getallheaders();
            foreach ($rawHeaders as $name => $value) {
                $headers[strtoupper(str_replace('-', '_', $name))] = $value;
            }
        }

        foreach ($server as $key => $value) {
            if (!is_string($key) || !str_starts_with($key, 'HTTP_')) {
                continue;
            }

            if (!is_scalar($value)) {
                continue;
            }

            $headerKey = substr($key, 5);
            $headers[$headerKey] = (string) $value;
        }

        if (isset($server['CONTENT_TYPE']) && is_scalar($server['CONTENT_TYPE'])) {
            $headers['CONTENT_TYPE'] = (string) $server['CONTENT_TYPE'];
        }

        if (isset($server['CONTENT_LENGTH']) && is_scalar($server['CONTENT_LENGTH'])) {
            $headers['CONTENT_LENGTH'] = (string) $server['CONTENT_LENGTH'];
        }

        return $headers;
    }
}
