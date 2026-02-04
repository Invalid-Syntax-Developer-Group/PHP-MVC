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
     * @inheritDoc
     */
    public function addDriver(string $alias, Closure $driver): static
    {
        $this->drivers[$alias] = $driver;
        return $this;
    }

    /**
     * @inheritDoc
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
