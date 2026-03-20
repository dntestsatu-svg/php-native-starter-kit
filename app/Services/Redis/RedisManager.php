<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Services\Redis;

use Predis\Client;

final class RedisManager
{
    private ?Client $client = null;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private readonly array $config,
    ) {}

    public function client(): Client
    {
        if ($this->client !== null) {
            return $this->client;
        }

        $parameters = [
            'scheme' => $this->config['scheme'] ?? 'tcp',
            'host' => $this->config['host'] ?? '127.0.0.1',
            'port' => $this->config['port'] ?? 6379,
            'database' => $this->config['database'] ?? 0,
            'timeout' => $this->config['timeout'] ?? 5.0,
        ];

        if (!empty($this->config['password'])) {
            $parameters['password'] = $this->config['password'];
        }

        $this->client = new Client($parameters);

        return $this->client;
    }
}
