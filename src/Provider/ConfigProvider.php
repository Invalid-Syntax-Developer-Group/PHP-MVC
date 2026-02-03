<?php
declare(strict_types=1);
namespace PhpMVC\Provider;

use PhpMVC\Core\Application;
use PhpMVC\Support\Config;

final class ConfigProvider
{
    public function bind(Application $app): void
    {
        $app->bind('config', function($app) {
            return new Config();
        });
    }
}