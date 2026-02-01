<?php
declare(strict_types=1);
namespace PhpMVC\Provider;

use PhpMVC\Cache\Factory;
use PhpMVC\Cache\Driver\FileDriver;
//use PhpMVC\Cache\Driver\MemcacheDriver;
use PhpMVC\Cache\Driver\MemoryDriver;
use PhpMVC\Support\DriverProvider;
use PhpMVC\Support\DriverFactory;

final class CacheProvider extends DriverProvider
{
    protected function name(): string
    {
        return 'cache';
    }

    protected function factory(): DriverFactory
    {
        return new Factory();
    }

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