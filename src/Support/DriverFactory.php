<?php
declare(strict_types=1);
namespace PhpMVC\Support;

use Closure;

/**
 * Interface DriverFactory
 *
 * Contract for factories responsible for registering and instantiating
 * driver implementations based on runtime configuration.
 *
 * Driver factories follow a simple pattern:
 *  - Drivers are registered with a string alias and a factory Closure.
 *  - A configuration array determines which driver is instantiated.
 *
 * Implementations typically expect a `type` key in the configuration
 * array that maps to a registered driver alias.
 *
 * This interface is used across multiple framework subsystems
 * (cache, database, logging, filesystem, queue, etc.) to provide a
 * consistent and extensible driver resolution mechanism.
 *
 * @package PhpMVC\Support
 */
interface DriverFactory
{
    /**
     * Register a driver factory under a given alias.
     *
     * The provided Closure should accept a configuration array
     * and return a fully constructed driver instance.
     *
     * Example:
     *  $factory->addDriver('mysql', fn(array $config) => new MysqlConnection($config));
     *
     * @param string  $alias  Identifier used to select the driver (e.g. "mysql", "file").
     * @param Closure $driver Factory closure responsible for creating the driver instance.
     *
     * @return static
     */
    public function addDriver(string $alias, Closure $driver): static;

    /**
     * Instantiate and return a driver based on the provided configuration.
     *
     * Implementations typically inspect a `type` key within the configuration
     * array to determine which registered driver alias should be used.
     *
     * @param array $config Driver configuration array.
     *
     * @return mixed The resolved driver instance.
     *
     * @throws \RuntimeException If the driver type is missing or unrecognised.
     */
    public function connect(array $config): mixed;
}
