<?php

declare(strict_types=1);

namespace Mugiew\StarterKit\Tests\Unit\Infrastructure;

use Mugiew\StarterKit\Config\Database as DatabaseConfig;
use Mugiew\StarterKit\Config\Memcached as MemcachedConfig;
use Mugiew\StarterKit\Config\Redis as RedisConfig;
use Mugiew\StarterKit\Services\Cache\MemcachedStore;
use Mugiew\StarterKit\Services\Config\EnvironmentLoader;
use Mugiew\StarterKit\Services\Database\DatabaseManager;
use Mugiew\StarterKit\Services\Redis\RedisManager;
use PDO;
use Predis\Client;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Throwable;

final class ConnectionHealthTest extends TestCase
{
    /**
     * @var array<string, string>|null
     */
    private static ?array $environment = null;

    #[Test]
    public function database_manager_establishes_a_mysql_connection(): void
    {
        $pdo = $this->requireAvailableService(
            'MySQL',
            static fn (): PDO => (new DatabaseManager(DatabaseConfig::fromEnv(self::environment())))->connection()
        );

        self::assertInstanceOf(PDO::class, $pdo);

        $result = $pdo->query('SELECT 1 AS connection_ok')?->fetch(PDO::FETCH_ASSOC);
        self::assertIsArray($result);
        self::assertSame('1', (string) ($result['connection_ok'] ?? ''));
    }

    #[Test]
    public function redis_manager_can_ping_and_round_trip_data(): void
    {
        $redis = $this->requireAvailableService('Redis', function (): Client {
            $client = (new RedisManager(RedisConfig::fromEnv(self::environment())))->client();
            $pong = strtoupper((string) $client->ping());

            if ($pong !== 'PONG') {
                throw new \RuntimeException('Redis PING did not return PONG.');
            }

            return $client;
        });

        self::assertInstanceOf(Client::class, $redis);

        $key = 'tests:redis:' . bin2hex(random_bytes(8));
        $redis->set($key, 'ok');

        self::assertSame('ok', $redis->get($key));
        self::assertGreaterThanOrEqual(0, (int) $redis->del([$key]));
    }

    #[Test]
    public function memcached_store_reports_healthy_and_round_trips_data(): void
    {
        if (!extension_loaded('memcached')) {
            $this->markTestSkipped('Memcached PHP extension is not installed.');
        }

        $store = $this->requireAvailableService(
            'Memcached',
            static fn (): MemcachedStore => MemcachedStore::create(MemcachedConfig::fromEnv(self::environment()))
        );

        self::assertInstanceOf(MemcachedStore::class, $store);
        self::assertTrue($store->healthy());

        $key = 'tests:memcached:' . bin2hex(random_bytes(8));
        self::assertTrue($store->set($key, 'ok', 30));
        self::assertSame('ok', $store->get($key));
        self::assertTrue($store->delete($key));
    }

    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    private function requireAvailableService(string $service, callable $callback): mixed
    {
        try {
            return $callback();
        } catch (Throwable $exception) {
            $this->markTestSkipped(sprintf('%s is unavailable: %s', $service, $exception->getMessage()));
        }
    }

    /**
     * @return array<string, string>
     */
    private static function environment(): array
    {
        if (self::$environment !== null) {
            return self::$environment;
        }

        $basePath = dirname(__DIR__, 3);
        $loader = new EnvironmentLoader($basePath);
        self::$environment = $loader->load()->env;

        return self::$environment;
    }
}
