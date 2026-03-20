<?php

declare(strict_types=1);

use Mugiew\StarterKit\Core\Application;
use Mugiew\StarterKit\Core\Container;
use Mugiew\StarterKit\Core\Router;
use Mugiew\StarterKit\Core\View;
use Mugiew\StarterKit\Http\Middlewares\CsrfMiddleware;
use Mugiew\StarterKit\Services\Cache\MemcachedStore;
use Mugiew\StarterKit\Services\Config\ConfigRepository;
use Mugiew\StarterKit\Services\Config\EnvironmentLoader;
use Mugiew\StarterKit\Services\Database\DatabaseManager;
use Mugiew\StarterKit\Services\Redis\RedisManager;
use Mugiew\StarterKit\Services\Security\CsrfManager;
use Mugiew\StarterKit\Services\Session\SessionManager;
use Predis\Client;

$basePath = dirname(__DIR__);

require_once $basePath . '/vendor/autoload.php';
require_once $basePath . '/helper/functions.php';

$environmentLoader = new EnvironmentLoader($basePath);
$environment = $environmentLoader->load();
$configRepository = new ConfigRepository($environment->env, $environment->cache);
$config = $configRepository->all();

date_default_timezone_set((string) ($config['app']['timezone'] ?? 'UTC'));

$container = new Container();
$container->instance('env', $environment->env);
$container->instance('config', $config);

$container->singleton(MemcachedStore::class, static fn (Container $container): ?MemcachedStore => MemcachedStore::create($config['memcached'], true));
$container->singleton(RedisManager::class, static fn (Container $container): RedisManager => new RedisManager($config['redis']));
$container->singleton(Client::class, static fn (Container $container): Client => $container->get(RedisManager::class)->client());
$container->singleton(DatabaseManager::class, static fn (Container $container): DatabaseManager => new DatabaseManager($config['database']));
$container->singleton(\PDO::class, static fn (Container $container): \PDO => $container->get(DatabaseManager::class)->connection());
$container->singleton(View::class, static fn (Container $container): View => new View($basePath . '/app/Views'));
$container->singleton(SessionManager::class, static fn (Container $container): SessionManager => new SessionManager($container->get(Client::class), $config['session']));
$container->singleton(CsrfManager::class, static fn (Container $container): CsrfManager => new CsrfManager($container->get(Client::class), $config['security']['csrf']));
$container->singleton(CsrfMiddleware::class, static fn (Container $container): CsrfMiddleware => new CsrfMiddleware(
    $container->get(CsrfManager::class),
    $container->get(View::class),
    $config['security']['csrf']
));

$router = new Router();
$registerRoutes = require $basePath . '/config/routes.php';
$registerRoutes($router);

$app = new Application($container, $router);

if (($config['security']['csrf']['enabled'] ?? true) === true) {
    $app->addMiddleware($container->get(CsrfMiddleware::class));
}

$container->get(SessionManager::class)->start();
$GLOBALS['app'] = $app;

return $app;
