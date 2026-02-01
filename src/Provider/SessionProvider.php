<?php
declare(strict_types=1);
namespace PhpMVC\Provider;

use PhpMVC\Session\Factory;
use PhpMVC\Session\Driver\NativeDriver;
use PhpMVC\Support\DriverProvider;
use PhpMVC\Support\DriverFactory;

final class SessionProvider extends DriverProvider
{
    protected function name(): string
    {
        return 'session';
    }

    protected function factory(): DriverFactory
    {
        return new Factory();
    }

    protected function drivers(): array
    {
        return [
            'native' => function($config) {
                return new NativeDriver($config);
            },
        ];
    }
}