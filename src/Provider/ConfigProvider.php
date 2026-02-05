<?php
declare(strict_types=1);
namespace PhpMVC\Provider;

use PhpMVC\Core\Application;
use PhpMVC\Support\Config;

/**
 * Class ConfigProvider
 *
 * Service provider responsible for registering the application-wide
 * configuration service within the dependency container.
 *
 * This provider binds the `config` alias to a {@see Config} instance,
 * allowing configuration values to be resolved anywhere in the
 * application via the container.
 *
 * Typical usage:
 * ```
 * $config = app('config');
 * ```
 *
 * @package PhpMVC\Provider
 * @since 1.0
 */
final class ConfigProvider
{
    /**
     * Register the configuration service with the application container.
     *
     * Binds the `config` alias to a lazily-resolved {@see Config} instance.
     *
     * @param Application $app The application container instance.
     *
     * @return void
     */
    public function bind(Application $app): void
    {
        $app->bind('config', function($app) {
            return new Config();
        });
    }
}
