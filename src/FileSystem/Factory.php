<?php
declare(strict_types=1);
namespace PhpMVC\FileSystem;

use Closure;
use PhpMVC\FileSystem\Driver\Driver;
use PhpMVC\FileSystem\Exception\DriverException;
use PhpMVC\Support\DriverFactory;

/**
 * Class Factory
 *
 * Filesystem driver factory responsible for registering and instantiating
 * filesystem drivers based on configuration.
 *
 * This factory follows a simple driver-resolution pattern:
 *  - Drivers are registered via {@see Factory::addDriver()} using a string alias.
 *  - A driver is instantiated via {@see Factory::connect()} by resolving the
 *    `type` key in the provided configuration array.
 *
 * Each registered driver is defined as a {@see Closure} that receives the
 * configuration array and returns an instance of {@see Driver}.
 *
 * @package PhpMVC\FileSystem
 * @since   1.0
 */
final class Factory implements DriverFactory
{
    /**
     * Registered filesystem drivers.
     *
     * The array key represents the driver alias (e.g. "local"),
     * and the value is a Closure that returns a {@see Driver} instance.
     *
     * @var array<string,Closure>
     */
    protected array $drivers;

    /**
     * Register a filesystem driver.
     *
     * @param string  $alias  Driver alias used to resolve the driver.
     * @param Closure $driver Closure that returns a {@see Driver} instance.
     *
     * @return static Fluent interface.
     */
    public function addDriver(string $alias, Closure $driver): static
    {
        $this->drivers[$alias] = $driver;
        return $this;
    }

    /**
     * Create and return a filesystem driver instance.
     *
     * The provided configuration array must contain a `type` key that
     * corresponds to a registered driver alias.
     *
     * @param array $config Driver configuration array.
     *
     * @throws DriverException If `type` is missing or the driver is unrecognised.
     *
     * @return Driver Resolved filesystem driver instance.
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
