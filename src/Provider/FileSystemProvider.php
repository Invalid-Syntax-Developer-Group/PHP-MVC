<?php
declare(strict_types=1);
namespace PhpMVC\Provider;

use PhpMVC\Support\DriverProvider;
use PhpMVC\Support\DriverFactory;
use PhpMVC\FileSystem\Factory;
use PhpMVC\FileSystem\Driver\LocalDriver;

/**
 * Class FileSystemProvider
 *
 * Service provider responsible for registering filesystem drivers
 * with the application container.
 *
 * This provider integrates the filesystem subsystem using the
 * {@see DriverProvider} abstraction, allowing different storage
 * backends to be swapped via configuration.
 *
 * Currently supported drivers:
 *  - `local` : {@see LocalDriver} (local disk via Flysystem)
 *
 * The resolved filesystem driver can be accessed from the container
 * using the `filesystem` binding.
 *
 * @package PhpMVC\Provider
 * @since 1.0
 */
final class FileSystemProvider extends DriverProvider
{
    /**
     * Get the container binding name for the filesystem service.
     *
     * @return string The service name used in configuration and resolution.
     */
    protected function name(): string
    {
        return 'filesystem';
    }

    /**
     * Create the filesystem driver factory.
     *
     * @return DriverFactory The filesystem driver factory instance.
     */
    protected function factory(): DriverFactory
    {
        return new Factory();
    }

    /**
     * Define the available filesystem drivers.
     *
     * Each driver is registered under an alias and mapped to a
     * factory closure that receives its configuration array.
     *
     * @return array<string, callable> Array of driver aliases to factories.
     */
    protected function drivers(): array
    {
        return [
            'local' => function($config) {
                return new LocalDriver($config);
            },
        ];
    }
}
