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
 * @since 1.0
 */
class DatabaseProvider extends DriverProvider
{
    /**
     * Get the container binding name for the database service.
     *
     * This value is used as the alias when resolving the database
     * factory or active connection from the container.
     *
     * @return string The service name (`database`).
     */
    protected function name(): string
    {
        return 'database';
    }

    /**
     * Create the database driver factory.
     *
     * The factory is responsible for instantiating concrete
     * database connection drivers based on configuration.
     *
     * @return DriverFactory The database driver factory instance.
     */
    protected function factory(): DriverFactory
    {
        return new Factory();
    }

    /**
     * Register supported database drivers.
     *
     * Each driver is mapped by alias to a factory closure that
     * returns a concrete {@see Connection}
     * implementation.
     *
     * Supported drivers:
     *  - `mysql` : {@see MysqlConnection}
     *
     * @return array<string, callable> Driver alias to factory mapping.
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
