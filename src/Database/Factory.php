<?php
declare(strict_types=1);
namespace PhpMVC\Database;

use Closure;
use PhpMVC\Database\Connection\Connection;
use PhpMVC\Database\Exception\ConnectionException;
use PhpMVC\Support\DriverFactory;

/**
 * Class Factory
 *
 * Database connection factory responsible for resolving and instantiating
 * concrete {@see Connection} implementations at runtime.
 *
 * This factory follows a driver-based pattern:
 *  - Connection drivers are registered under an alias (e.g. "mysql")
 *  - Each driver is defined as a {@see Closure} that receives the connection
 *    configuration array and returns a {@see Connection} instance
 *  - The {@see connect()} method selects and invokes the correct driver
 *    based on the provided configuration
 *
 * This design allows:
 *  - Pluggable database drivers
 *  - Lazy instantiation of connections
 *  - Clean separation between configuration and implementation
 *
 * Example:
 * ```
 * $factory->addDriver('mysql', fn ($config) => new MysqlConnection($config));
 * $connection = $factory->connect([
 *     'type' => 'mysql',
 *     'host' => 'localhost',
 *     // ...
 * ]);
 * ```
 *
 * @package PhpMVC\Database
 * @since   1.0
 */
final class Factory implements DriverFactory
{
    /**
     * Registered connection drivers.
     *
     * @var array<string,Closure>
     */
    protected array $drivers = [];

    /**
     * Register a database connection driver.
     *
     * The provided closure must return an instance of {@see Connection}
     * when invoked with the database configuration array.
     *
     * @param string  $alias  Driver alias (e.g. "mysql").
     * @param Closure $driver Driver factory closure.
     *
     * @return static Fluent return for chaining.
     */
    public function addDriver(string $alias, Closure $driver): static
    {
        $this->drivers[$alias] = $driver;
        return $this;
    }

    /**
     * Resolve and create a database connection.
     *
     * The configuration array must include a `type` key matching a
     * previously registered driver alias.
     *
     * @param array $config Database connection configuration.
     *
     * @return Connection Instantiated database connection.
     *
     * @throws ConnectionException If the type is missing or unrecognised.
     */
    public function connect(array $config): Connection
    {
        if (!isset($config['type'])) {
            throw new ConnectionException('type is not defined');
        }

        $type = $config['type'];

        if (isset($this->drivers[$type])) {
            return $this->drivers[$type]($config);
        }

        throw new ConnectionException('unrecognised type');
    }
}
