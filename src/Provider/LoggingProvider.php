<?php
declare(strict_types=1);
namespace PhpMVC\Provider;

use PhpMVC\Logging\Factory;
use PhpMVC\Logging\Driver\StreamDriver;
use PhpMVC\Support\DriverFactory;
use PhpMVC\Support\DriverProvider;

/**
 * Class LoggingProvider
 *
 * Service provider responsible for registering logging drivers
 * with the application container.
 *
 * This provider extends {@see DriverProvider} and exposes the
 * logging subsystem under the `logging` service name. It uses
 * a driver factory to resolve concrete logging drivers based on
 * configuration.
 *
 * Currently supported drivers:
 *  - `stream` : {@see StreamDriver} (Monolog-backed stream logging)
 *
 * Additional logging drivers (e.g. daily files, syslog, remote
 * logging services) can be added by extending the {@see LoggingProvider::drivers()}
 * method.
 *
 * @package PhpMVC\Provider
 */
final class LoggingProvider extends DriverProvider
{
    /**
     * Get the container binding name for the logging service.
     *
     * This value is used as the alias when resolving the logging
     * factory or active logger from the container.
     *
     * @return string The service name (`logging`).
     */
    protected function name(): string
    {
        return 'logging';
    }

    /**
     * Create the logging driver factory.
     *
     * The factory is responsible for instantiating concrete
     * logging drivers based on runtime configuration.
     *
     * @return DriverFactory The logging driver factory instance.
     */
    protected function factory(): DriverFactory
    {
        return new Factory();
    }

    /**
     * Register supported logging drivers.
     *
     * Each driver is mapped by alias to a factory closure that
     * returns a concrete {@see Driver}
     * implementation.
     *
     * Supported drivers:
     *  - `stream` : {@see StreamDriver} (Monolog-backed stream logging)
     *
     * @return array<string, callable> Driver alias to factory mapping.
     */
    protected function drivers(): array
    {
        return [
            'stream' => function($config) {
                return new StreamDriver($config);
            },
        ];
    }
}
