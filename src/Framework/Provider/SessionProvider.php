<?php
declare(strict_types=1);
namespace PhpMVC\Framework\Provider;

use PhpMVC\Framework\Session\Factory;
use PhpMVC\Framework\Session\Driver\NativeDriver;
use PhpMVC\Framework\Support\DriverProvider;
use PhpMVC\Framework\Support\DriverFactory;

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