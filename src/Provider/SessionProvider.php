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
     * @inheritDoc
     */
    protected function name(): string
    {
        return 'session';
    }

    /**
     * @inheritDoc
     */
    protected function factory(): DriverFactory
    {
        return new Factory();
    }

    /**
     * @inheritDoc
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
