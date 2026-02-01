<?php
declare(strict_types=1);
namespace PhpMVC\Framework\Provider;

use PhpMVC\Framework\Logging\Factory;
use PhpMVC\Framework\Logging\Driver\StreamDriver;
use PhpMVC\Framework\Support\DriverFactory;
use PhpMVC\Framework\Support\DriverProvider;

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