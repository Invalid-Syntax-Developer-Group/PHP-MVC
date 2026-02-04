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
 */
final class CacheProvider extends DriverProvider
{
    /**
     * @inheritDoc
     */
    protected function name(): string
    {
        return 'cache';
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