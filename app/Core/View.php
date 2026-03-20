<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Core;

use RuntimeException;

final class View
{
    public function __construct(
        private readonly string $basePath,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $view, array $data = []): string
    {
        $path = $this->basePath . DIRECTORY_SEPARATOR . str_replace('.', DIRECTORY_SEPARATOR, $view) . '.php';

        if (!is_file($path)) {
            throw new RuntimeException(sprintf('View "%s" not found at "%s".', $view, $path));
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $path;
        return (string) ob_get_clean();
    }
}
