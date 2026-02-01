<?php
declare(strict_types=1);
namespace PhpMVC\Provider;

use PhpMVC\Queue\Factory;
use PhpMVC\Queue\Driver\DatabaseDriver;
use PhpMVC\Support\DriverProvider;
use PhpMVC\Support\DriverFactory;

final class QueueProvider extends DriverProvider
{
    protected function name(): string
    {
        return 'queue';
    }

    protected function factory(): DriverFactory
    {
        return new Factory();
    }

    protected function drivers(): array
    {
        return [
            'database' => function($config) {
                return new DatabaseDriver($config);
            },
        ];
    }
}