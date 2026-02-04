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
     * @inheritDoc
     */
    protected function name(): string
    {
        return 'logging';
    }

    /**
     * @inheritDoc
     */
    protected function factory(): DriverFactory
    {
        return new Factory();
    }

    /**
     * @inheritDoc
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
