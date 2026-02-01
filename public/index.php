<?php

declare(strict_types=1);

use App\Config\ContainerConfig;
use App\Helpers\Logger;
use App\Middleware\RequestIdMiddleware;
use App\Middleware\RequestSizeLimitMiddleware;
use App\Middleware\SecurityHeadersMiddleware;
use DI\Container;
use Dotenv\Dotenv;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

$config = require __DIR__ . '/../app/Config/config.php';

// === DI Container ===
$container = new Container();
$container->set('config', $config);
$container->set('jwt_config', $config['jwt']);
$definitions = ContainerConfig::getDefinitions();
foreach ($definitions as $id => $definition) {
    $container->set($id, $definition);
}

// === Slim App ===
AppFactory::setContainer($container);
$app = AppFactory::create();

$app->add(new RequestIdMiddleware());
$app->add(new RequestSizeLimitMiddleware($config['request_size_limit'], $config['request_size_overrides'] ?? []));
$app->addBodyParsingMiddleware();

// === Error handler (RFC7807) ===
$app->add(new \App\Middleware\ErrorMiddleware($config['debug']));

// === Security Headers + CORS (должен быть последним) ===
$app->add(new SecurityHeadersMiddleware([
    'cors' => $config['cors'],
    'csp' => [
        'script' => 'https://code.jquery.com, https://cdn.jsdelivr.net, https://cdn.datatables.net, https://cdn.tailwindcss.com, https://cdnjs.cloudflare.com',
        'style' => 'https://fonts.googleapis.com, https://cdn.jsdelivr.net, https://cdn.datatables.net, https://cdn.tailwindcss.com',
        'font' => 'https://fonts.gstatic.com',
        'connect' => 'https://cdn.datatables.net',
    ],
    'x_frame_options' => 'DENY',
]));

// === Регистрация маршрутов из конфига ===
$routesConfig = require __DIR__ . '/../app/Config/routes.php';
registerRoutes($app, $container, $routesConfig, $config);

/**
 * Регистрирует маршруты из конфига.
 */
function registerRoutes(
    \Slim\App $app,
    \DI\Container $container,
    array $config,
    array $appConfig
): void {
    // Dashboard routes
    if (isset($config['dashboard'])) {
        $dashboard = $config['dashboard'];
        $prefix = '/dashboard';

        $routeGroup = $app->group($prefix, function (\Slim\Routing\RouteCollectorProxy $g) use ($container, $dashboard, $appConfig) {
            registerRouteGroup($g, $container, $dashboard['routes'], '');
        });

        // Add middleware to group (in reverse order for proper execution)
        foreach (array_reverse($dashboard['middleware'] ?? []) as $middlewareClass) {
            $routeGroup = $routeGroup->add($container->get($middlewareClass));
        }
    }

    // API routes
    if (isset($config['api'])) {
        $api = $config['api'];
        $prefix = '/api';

        $routeGroup = $app->group($prefix, function (\Slim\Routing\RouteCollectorProxy $g) use ($container, $api, $appConfig) {
            registerRouteGroup($g, $container, $api['routes'], '');
        });

        // Add middleware to group (in reverse order)
        foreach (array_reverse($api['middleware'] ?? []) as $middlewareClass) {
            $routeGroup = $routeGroup->add($container->get($middlewareClass));
        }
    }
}

/**
 * Регистрирует группу маршрутов.
 */
function registerRouteGroup(
    \Slim\Routing\RouteCollectorProxy $group,
    \DI\Container $container,
    array $routes,
    string $prefix
): void {
    foreach ($routes as $key => $route) {
        // Nested group with middleware (e.g., protected routes inside auth)
        if (is_string($key) && $key !== '' && isset($route['middleware'], $route['routes'])) {
            $routeGroup = $group->group($key, function (\Slim\Routing\RouteCollectorProxy $g) use ($container, $route) {
                registerRouteGroup($g, $container, $route['routes'], '');
            });

            // Add middleware to nested group
            foreach (array_reverse($route['middleware'] ?? []) as $middlewareClass) {
                try {
                    $routeGroup = $routeGroup->add($container->get($middlewareClass));
                } catch (\DI\DependencyException|\DI\NotFoundException $e) {
                    Logger::error($e->getMessage());
                }
            }
            continue;
        }

        // Nested group without middleware (routes only)
        if (is_string($key) && $key !== '' && isset($route['routes']) && !isset($route['middleware'])) {
            $group->group($key, function (\Slim\Routing\RouteCollectorProxy $g) use ($container, $route) {
                registerRouteGroup($g, $container, $route['routes'], '');
            });
            continue;
        }

        // Nested routes without middleware (key can be '' or numeric)
        if (is_string($key) && isset($route[0]) && is_array($route[0])) {
            registerRouteGroup($group, $container, $route, '');
            continue;
        }

        // Regular route: ['METHOD', '/path', [Controller::class, 'method']]
        if (is_array($route) && count($route) >= 3) {
            [$method, $path, $handler] = $route;

            if ($method === 'MAP') {
                // MAP: ['MAP', ['GET', 'POST'], '/path', [Controller::class, 'method']]
                $methods = $path;
                $fullPath = $prefix . ($route[2] ?? '');
                $fullHandler = $route[3] ?? $handler;
                $group->map($methods, $fullPath, $fullHandler);
            } else {
                $fullPath = $prefix . $path;
                $group->$method($fullPath, $handler);
            }
        }
    }
}

// === Запуск ===
$app->run();
