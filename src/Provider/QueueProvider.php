<?php
declare(strict_types=1);
namespace PhpMVC\Provider;

use PhpMVC\Queue\Factory;
use PhpMVC\Queue\Driver\DatabaseDriver;
use PhpMVC\Support\DriverProvider;
use PhpMVC\Support\DriverFactory;

/**
 * Class QueueProvider
 *
 * Service provider responsible for registering queue drivers
 * with the application container.
 *
 * This provider extends {@see DriverProvider} and exposes the
 * queue subsystem under the `queue` service name. It relies on
 * a driver factory to resolve concrete queue drivers based on
 * application configuration.
 *
 * Currently supported drivers:
 *  - `database` : {@see DatabaseDriver} (database-backed job queue)
 *
 * Additional queue drivers (e.g. Redis, SQS, Beanstalkd) can be
 * added by extending the {@see QueueProvider::drivers()} method.
 *
 * @package PhpMVC\Provider
 */
final class QueueProvider extends DriverProvider
{
    /**
     * Get the container binding name for the queue service.
     *
     * This value is used as the alias when resolving the queue
     * factory or active queue driver from the container.
     *
     * @return string The service name (`queue`).
     */
    protected function name(): string
    {
        return 'queue';
    }

    /**
     * Create the queue driver factory.
     *
     * The factory is responsible for instantiating concrete
     * queue drivers based on runtime configuration.
     *
     * @return DriverFactory The queue driver factory instance.
     */
    protected function factory(): DriverFactory
    {
        return new Factory();
    }

    /**
     * Register supported queue drivers.
     *
     * Each driver is mapped by alias to a factory closure that
     * returns a concrete queue driver implementation.
     *
     * Supported drivers:
     *  - `database` : {@see DatabaseDriver} (database-backed job queue)
     *
     * @return array<string, callable> Driver alias to factory mapping.
     */
    protected function drivers(): array
    {
        return [
            'database' => function($config) {
                return new DatabaseDriver($config);
            },
        ];
    }
}
