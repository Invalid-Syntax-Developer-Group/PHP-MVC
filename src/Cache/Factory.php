<?php
declare(strict_types=1);
namespace PhpMVC\Cache;

use Closure;
use PhpMVC\Cache\Driver\Driver;
use PhpMVC\Cache\Exception\DriverException;
use PhpMVC\Support\DriverFactory;

/**
 * Class Factory
 *
 * Cache driver factory responsible for registering cache driver constructors
 * and creating concrete cache driver instances from configuration.
 *
 * This factory implements the {@see DriverFactory} contract and provides a
 * simple plugin-style mechanism:
 *  - Register one or more cache drivers by alias (e.g. "file", "redis", "memcached")
 *  - Instantiate a driver using a configuration array that includes a "type" key
 *
 * Registration model:
 *  - Drivers are stored as closures keyed by alias.
 *  - Each driver closure receives the driver configuration array and must
 *    return an instance implementing {@see Driver}.
 *
 * Connection model:
 *  - {@see Factory::connect()} reads `$config['type']` and resolves a matching registered driver.
 *  - Throws {@see DriverException} when the type is missing or unrecognised.
 *
 * @package PhpMVC\Cache
 * @version 1.0
 * @since   2025-09-04
 */
class Factory implements DriverFactory
{
    /**
     * @var array<string,Closure> Registered driver constructors keyed by alias.
     */
    protected array $drivers;

    /**
     * Register a cache driver constructor under an alias.
     *
     * The provided closure should accept a configuration array and return
     * an instance implementing {@see Driver}.
     *
     * Example:
     *  $factory->addDriver('file', fn(array $cfg) => new FileDriver($cfg));
     *
     * @param string  $alias  Driver alias used as the config 'type' selector.
     * @param Closure $driver Driver constructor closure: fn(array $config): Driver
     *
     * @return static Fluent return for chaining.
     */
    public function addDriver(string $alias, Closure $driver): static
    {
        $this->drivers[$alias] = $driver;
        return $this;
    }

    /**
     * Create and return a cache driver instance from configuration.
     *
     * Expected config shape:
     *  - type: string (required) Alias of the registered driver.
     *  - ... additional driver-specific keys
     *
     * @param array<string,mixed> $config Driver configuration.
     *
     * @return Driver Concrete driver instance.
     *
     * @throws DriverException If 'type' is missing or does not match a registered driver.
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