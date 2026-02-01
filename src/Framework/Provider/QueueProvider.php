<?php
declare(strict_types=1);
namespace PhpMVC\Framework\Provider;

use PhpMVC\Framework\Queue\Factory;
use PhpMVC\Framework\Queue\Driver\DatabaseDriver;
use PhpMVC\Framework\Support\DriverProvider;
use PhpMVC\Framework\Support\DriverFactory;

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