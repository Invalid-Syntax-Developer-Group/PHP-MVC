<?php
declare(strict_types=1);
namespace PhpMVC\Provider;

use PhpMVC\Database\Factory;
use PhpMVC\Support\DriverFactory;
use PhpMVC\Support\DriverProvider;
use PhpMVC\Database\Connection\MysqlConnection;

/**
 * Class DatabaseProvider
 *
 * Service provider responsible for registering database connection
 * drivers with the application container.
 *
 * This provider extends {@see DriverProvider} and configures the
 * database driver factory, exposing it under the `database` service
 * name. It currently supports the following drivers:
 *  - `mysql` : {@see MysqlConnection}
 *
 * @package PhpMVC\Provider
 */
class DatabaseProvider extends DriverProvider
{
    /**
     * @inheritDoc
     */
    protected function name(): string
    {
        return 'database';
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
            /*'sqlite' => function($config) {
                return new SqliteConnection($config);
            },*/
            'mysql' => function($config) {
                return new MysqlConnection($config);
            },
        ];
    }
}
