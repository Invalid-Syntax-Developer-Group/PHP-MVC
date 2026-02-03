<?php
declare(strict_types=1);
namespace PhpMVC\Provider;

use PhpMVC\Session\Factory;
use PhpMVC\Session\Driver\NativeDriver;
use PhpMVC\Support\DriverProvider;
use PhpMVC\Support\DriverFactory;

/**
 * Class SessionProvider
 *
 * Service provider responsible for registering and configuring
 * the session subsystem within the application container.
 *
 * This provider exposes a `session` service backed by a
 * {@see PhpMVC\Session\Factory} and registers one or more
 * session drivers. The resolved driver is selected based on
 * application configuration and is responsible for managing
 * session lifecycle, storage, and persistence.
 *
 * @package PhpMVC\Provider
 */
final class SessionProvider extends DriverProvider
{
    /**
     * Get the container binding name for the session service.
     *
     * This value is used as the alias when resolving the session
     * driver from the application container.
     *
     * @return string The service name used for session resolution.
     */
    protected function name(): string
    {
        return 'session';
    }

    /**
     * Create the session driver factory.
     *
     * The factory is responsible for instantiating session drivers
     * based on configuration (e.g. driver type).
     *
     * @return DriverFactory The session driver factory instance.
     */
    protected function factory(): DriverFactory
    {
        return new Factory();
    }

    /**
     * Register available session drivers.
     *
     * Defines a map of driver aliases to factory closures. Each
     * closure receives the driver configuration array and must
     * return a fully constructed session driver instance.
     *
     * Supported drivers:
     *  - native : PHP native session handler implementation
     *
     * @return array<string, callable> Driver alias to factory mappings.
     */
    protected function drivers(): array
    {
        return [
            'native' => function($config) {
                return new NativeDriver($config);
            },
        ];
    }
}
