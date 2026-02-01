<?php
declare(strict_types=1);
namespace PhpMVC\Framework\Provider;

use PhpMVC\Framework\Application;
use PhpMVC\Framework\Support\Config;

final class ConfigProvider
{
    public function bind(Application $app): void
    {
        $app->bind('config', function($app) {
            return new Config();
        });
    }
}