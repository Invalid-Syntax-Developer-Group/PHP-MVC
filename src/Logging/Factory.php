<?php
declare(strict_types=1);
namespace PhpMVC\Logging;

use Closure;
use PhpMVC\Logging\Driver\Driver;
use PhpMVC\Logging\Exception\DriverException;
use PhpMVC\Support\DriverFactory;

/**
 * Class Factory
 *
 * Logging driver factory responsible for registering and instantiating
 * logging drivers based on runtime configuration.
 *
 * This factory follows a simple driver-resolution pattern:
 *  - Drivers are registered under a string alias via {@see Factory::addDriver()}.
 *  - A configuration array containing a `type` key determines which driver
 *    will be instantiated when {@see Factory::connect()} is called.
 *
 * The resolved driver **must** implement {@see PhpMVC\Logging\Driver\Driver}.
 *
 * @package PhpMVC\Logging
 * @implements DriverFactory
 */
class Factory implements DriverFactory
{
    /**
     * Registered logging drivers.
     *
     * The array key is the driver alias, and the value is a factory closure
     * responsible for creating the driver instance.
     *
     * @var array<string,Closure>
     */
    protected array $drivers = [];

    /**
     * Register a logging driver factory.
     *
     * The provided closure will be invoked with the configuration array
     * when the driver is requested via {@see connect()}.
     *
     * @param string  $alias  Driver identifier (e.g. "stream", "daily").
     * @param Closure $driver Closure that returns a {@see Driver} instance.
     *
     * @return static
     */
    public function addDriver(string $alias, Closure $driver): static
    {
        $this->drivers[$alias] = $driver;
        return $this;
    }

    /**
     * Resolve and instantiate a logging driver.
     *
     * The configuration array **must** contain a `type` key matching a
     * previously registered driver alias.
     *
     * @param array $config Driver configuration data.
     *
     * @throws DriverException If the `type` key is missing or unrecognised.
     *
     * @return Driver Instantiated logging driver.
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
