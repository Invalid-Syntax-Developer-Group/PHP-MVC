<?php
declare(strict_types=1);
namespace PhpMVC\Provider;

use PhpMVC\Database\Factory;
use PhpMVC\Support\DriverFactory;
use PhpMVC\Support\DriverProvider;
use PhpMVC\Database\Connection\MysqlConnection;

class DatabaseProvider extends DriverProvider
{
    protected function name(): string
    {
        return 'database';
    }

    protected function factory(): DriverFactory
    {
        return new Factory();
    }

    protected function drivers(): array
    {
        return [
            /*'sqlite' => function($config) {
                return new SqliteConnection($config);
            },*/
            'mysql' => function($config) {
                return new MysqlConnection($config);
            },
        ];
    }
}