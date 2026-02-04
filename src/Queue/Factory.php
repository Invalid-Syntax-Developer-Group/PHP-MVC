<?php
declare(strict_types=1);
namespace PhpMVC\Queue;

use Closure;
use PhpMVC\Queue\Driver\Driver;
use PhpMVC\Queue\Exception\DriverException;
use PhpMVC\Support\DriverFactory;

/**
 * Class Factory
 *
 * Queue driver factory responsible for registering and instantiating
 * queue drivers based on application configuration.
 *
 * This factory follows a driver-based architecture where multiple
 * queue backends (e.g. database, redis, etc.) can be registered
 * under string aliases and resolved at runtime.
 *
 * The concrete driver selection is determined by the `type`
 * key in the provided configuration array.
 *
 * @package PhpMVC\Queue
 */
final class Factory implements DriverFactory
{
    /**
     * Registered queue drivers.
     *
     * The array maps driver aliases to factory closures that
     * return concrete {@see Driver} implementations.
     *
     * @var array<string, Closure>
     */
    protected array $drivers = [];

    /**
     * Register a queue driver.
     *
     * Associates a driver alias with a factory closure that
     * will be invoked when the driver is requested.
     *
     * @param string  $alias  Driver identifier (e.g. 'database').
     * @param Closure $driver Factory closure that returns a {@see Driver}.
     *
     * @return static
     */
    public function addDriver(string $alias, Closure $driver): static
    {
        $this->drivers[$alias] = $driver;
        return $this;
    }

    /**
     * Create and return a queue driver instance.
     *
     * The configuration array must define a `type` key which
     * determines which registered driver alias should be used.
     *
     * @param array $config Queue configuration array.
     *
     * @return Driver The resolved queue driver instance.
     *
     * @throws DriverException If the driver type is missing or unrecognised.
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
