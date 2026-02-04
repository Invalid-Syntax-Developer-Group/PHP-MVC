<?php
declare(strict_types=1);
namespace PhpMVC\Email;

use Closure;
use PhpMVC\Email\Driver\Driver;
use PhpMVC\Email\Exception\DriverException;
use PhpMVC\Support\DriverFactory;

/**
 * Class Factory
 *
 * Email driver factory responsible for registering and instantiating
 * concrete email driver implementations.
 *
 * This factory follows a simple driver-registry pattern:
 *  - Drivers are registered under a string alias (e.g. "mail", "smtp", "sendmail")
 *  - Each driver is defined as a {@see Closure} that receives a configuration
 *    array and returns an object implementing {@see Driver}
 *
 * The factory is typically used during application bootstrap to register
 * available email drivers, and later to resolve a concrete driver based on
 * runtime configuration.
 *
 * Configuration model:
 *  - The `$config` array passed to {@see Factory::connect()} must include a `type` key
 *    whose value matches a registered driver alias
 *  - Any additional configuration values are passed through to the driver
 *    constructor via the registered closure
 *
 * Error handling:
 *  - Throws {@see DriverException} if the `type` key is missing
 *  - Throws {@see DriverException} if the requested driver type is not registered
 *
 * @package PhpMVC\Email
 * @since   1.0
 */
final class Factory implements DriverFactory
{
    /**
     * @var array<string,Closure> Registered email driver constructors.
     */
    protected array $drivers = [];

    /**
     * Register an email driver constructor under an alias.
     *
     * The provided closure should accept a configuration array and return
     * an instance implementing {@see Driver}.
     *
     * Example:
     * ```
     * $factory->addDriver('mail', fn(array $cfg) => new MailDriver($cfg));
     * ```
     *
     * @param string  $alias  Driver alias used as the configuration selector.
     * @param Closure $driver Driver constructor closure.
     *
     * @return static Fluent return for chaining.
     */
    public function addDriver(string $alias, Closure $driver): static
    {
        $this->drivers[$alias] = $driver;
        return $this;
    }

    /**
     * Create and return an email driver instance from configuration.
     *
     * Expected configuration structure:
     *  - type: string (required) Registered driver alias
     *  - ...  driver-specific configuration values
     *
     * @param array<string,mixed> $config Email driver configuration.
     *
     * @return Driver Concrete email driver instance.
     *
     * @throws DriverException If the type is missing or unrecognised.
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
