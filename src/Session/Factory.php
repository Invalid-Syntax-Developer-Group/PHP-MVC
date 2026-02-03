<?php
declare(strict_types=1);
namespace PhpMVC\Session;

use Closure;
use PhpMVC\Session\Driver\Driver;
use PhpMVC\Session\Exception\DriverException;
use PhpMVC\Support\DriverFactory;

/**
 * Class Factory
 *
 * Session driver factory responsible for creating concrete session
 * driver instances based on configuration.
 *
 * This factory follows the {@see DriverFactory} contract and allows
 * session drivers to be registered under string aliases and resolved
 * dynamically at runtime using a configuration array.
 *
 * The configuration array **must** contain a `type` key matching one
 * of the registered driver aliases.
 *
 * @package PhpMVC\Session
 */
class Factory implements DriverFactory
{
    /**
     * Registered session driver resolvers.
     *
     * Each entry maps a driver alias to a closure that accepts a
     * configuration array and returns a {@see Driver} implementation.
     *
     * @var array<string, Closure>
     */
    protected array $drivers = [];

    /**
     * Register a session driver resolver.
     *
     * @param string  $alias   Driver alias (e.g. "native").
     * @param Closure $driver  Closure that returns a Driver instance.
     *
     * @return static Returns the factory instance for fluent chaining.
     */
    public function addDriver(string $alias, Closure $driver): static
    {
        $this->drivers[$alias] = $driver;
        return $this;
    }

    /**
     * Create and return a session driver instance.
     *
     * The provided configuration array must contain a `type` key
     * corresponding to a previously registered driver alias.
     *
     * @param array $config Session driver configuration.
     *
     * @return Driver The resolved session driver instance.
     *
     * @throws DriverException If no type is defined or the type is unrecognised.
     */
    public function connect(array $config): Driver
    {
        if (!isset($config['type'])) {
            throw new DriverException('type is not defined');
        }

        $type = $config['type'];

        if (isset($this->drivers[$type])) {
            return $this->drivers[$type]($config);
        }

        throw new DriverException('unrecognised type');
    }
}
