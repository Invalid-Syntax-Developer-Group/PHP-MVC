<?php
declare(strict_types=1);
namespace PhpMVC\Support;

use PhpMVC\Core\Application;

/**
 * Abstract Class DriverProvider
 *
 * Base service provider for driver-based subsystems (cache, database, logging,
 * filesystem, queue, session, etc.).
 *
 * This class standardizes how drivers are:
 *  - Registered with a {@see DriverFactory}
 *  - Resolved from configuration
 *  - Bound into the application container
 *
 * Concrete providers only need to define:
 *  - The service name used in the container
 *  - The factory responsible for creating drivers
 *  - The available driver definitions
 *
 * Expected configuration structure:
 *
 * ```php
 * return [
 *     'default' => 'mysql',
 *     'mysql' => [ ... ],
 *     'sqlite' => [ ... ],
 * ];
 * ```
 *
 * @package PhpMVC\Support
 */
abstract class DriverProvider
{
    /**
     * Bind the driver-backed service into the application container.
     *
     * This method:
     *  - Retrieves the provider name (e.g. "database", "cache")
     *  - Instantiates the driver factory
     *  - Registers all supported drivers with the factory
     *  - Resolves the default driver using configuration
     *  - Binds the resolved driver into the container under the provider name
     *
     * @param Application $app The application container instance.
     *
     * @return void
     */
    public function bind(Application $app): void
    {
        $name = $this->name();
        $factory = $this->factory();
        $drivers = $this->drivers();

        $app->bind($name, function ($app) use ($name, $factory, $drivers) {
            foreach ($drivers as $key => $value) {
                $factory->addDriver($key, $value);
            }

            $config = config($name);

            return $factory->connect($config[$config['default']]);
        });
    }

    /**
     * Get the container binding name for the provider.
     *
     * This value is used both as:
     *  - The container key (e.g. app('database'))
     *  - The configuration namespace (e.g. config('database'))
     *
     * @return string
     */
    abstract protected function name(): string;

    /**
     * Create and return the driver factory instance.
     *
     * The factory is responsible for registering drivers and
     * instantiating the selected driver based on configuration.
     *
     * @return DriverFactory
     */
    abstract protected function factory(): DriverFactory;

    /**
     * Define the available drivers for the provider.
     *
     * Each array entry should map a driver alias to a factory Closure.
     *
     * Example:
     * ```php
     * return [
     *     'mysql' => fn(array $config) => new MysqlConnection($config),
     *     'sqlite' => fn(array $config) => new SqliteConnection($config),
     * ];
     * ```
     *
     * @return array<string, \Closure>
     */
    abstract protected function drivers(): array;
}
