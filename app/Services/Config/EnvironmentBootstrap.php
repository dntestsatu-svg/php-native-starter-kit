<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Services\Config;

use Mugiew\StarterKit\Services\Cache\MemcachedStore;

final readonly class EnvironmentBootstrap
{
    /**
     * @param array<string, string> $env
     */
    public function __construct(
        public array $env,
        public ?MemcachedStore $cache,
    ) {}
}
