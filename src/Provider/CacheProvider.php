<?php
declare(strict_types=1);
namespace PhpMVC\Provider;

use PhpMVC\Cache\Factory;
use PhpMVC\Cache\Driver\FileDriver;
//use PhpMVC\Cache\Driver\MemcacheDriver;
use PhpMVC\Cache\Driver\MemoryDriver;
use PhpMVC\Support\DriverProvider;
use PhpMVC\Support\DriverFactory;

/**
 * Class CacheProvider
 *
 * Service provider responsible for registering the cache subsystem
 * with the application via the {@see DriverProvider} abstraction.
 *
 * This provider:
 *  - Registers a cache factory under the `cache` service name
 *  - Defines the supported cache drivers and their instantiation logic
 *  - Allows cache drivers to be selected via configuration (`type`)
 *
 * Supported drivers:
 *  - file   : Persistent filesystem-backed cache ({@see FileDriver})
 *  - memory : In-memory cache for request-lifetime storage ({@see MemoryDriver})
 * 
 * @package PhpMVC\Provider
 * @since 1.0
 */
final class CacheProvider extends DriverProvider
{
    /**
     * Return the container service name for this provider.
     *
     * This name is used when resolving the cache factory
     * from the application container (e.g. `app('cache')`).
     *
     * @return string The cache service identifier.
     */
    protected function name(): string
    {
        return 'cache';
    }

    /**
     * Create and return the cache driver factory.
     *
     * The factory is responsible for instantiating cache drivers
     * based on the provided configuration array.
     *
     * @return DriverFactory The cache driver factory instance.
     */
    protected function factory(): DriverFactory
    {
        return new Factory();
    }

    /**
     * Define the available cache drivers.
     *
     * Each driver is mapped to a closure that receives a configuration
     * array and returns a concrete cache driver instance.
     *
     * Driver keys correspond to the `type` value in cache configuration.
     *
     * @return array<string, callable> Array of driver factories keyed by type.
     */
    protected function drivers(): array
    {
        return [
            'file' => function($config) {
                return new FileDriver($config);
            },
            /*'memcache' => function($config) {
                return new MemcacheDriver($config);
            },*/
            'memory' => function($config) {
                return new MemoryDriver($config);
            },
        ];
    }
}