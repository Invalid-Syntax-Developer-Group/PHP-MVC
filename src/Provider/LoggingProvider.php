<?php
declare(strict_types=1);
namespace PhpMVC\Provider;

use PhpMVC\Logging\Factory;
use PhpMVC\Logging\Driver\StreamDriver;
use PhpMVC\Support\DriverFactory;
use PhpMVC\Support\DriverProvider;

final class LoggingProvider extends DriverProvider
{
    protected function name(): string
    {
        return 'logging';
    }

    protected function factory(): DriverFactory
    {
        return new Factory();
    }

    protected function drivers(): array
    {
        return [
            'stream' => function($config) {
                return new StreamDriver($config);
            },
        ];
    }
}