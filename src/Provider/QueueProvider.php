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
     * @inheritDoc
     */
    protected function name(): string
    {
        return 'queue';
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
            'database' => function($config) {
                return new DatabaseDriver($config);
            },
        ];
    }
}
