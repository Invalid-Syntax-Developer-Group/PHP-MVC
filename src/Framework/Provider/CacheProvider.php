<?php
declare(strict_types=1);
namespace PhpMVC\Framework\Provider;

use PhpMVC\Framework\Cache\Factory;
use PhpMVC\Framework\Cache\Driver\FileDriver;
use PhpMVC\Framework\Cache\Driver\MemcacheDriver;
use PhpMVC\Framework\Cache\Driver\MemoryDriver;
use PhpMVC\Framework\Support\DriverProvider;
use PhpMVC\Framework\Support\DriverFactory;

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
            'memcache' => function($config) {
                return new MemcacheDriver($config);
            },
            'memory' => function($config) {
                return new MemoryDriver($config);
            },
        ];
    }
}