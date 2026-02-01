<?php
declare(strict_types=1);
namespace PhpMVC\Database;

use Closure;
use PhpMVC\Database\Connection\Connection;
use PhpMVC\Database\Exception\ConnectionException;
use PhpMVC\Support\DriverFactory;

final class Factory implements DriverFactory
{
    protected array $drivers;

    public function addDriver(string $alias, Closure $driver): static
    {
        $this->drivers[$alias] = $driver;
        return $this;
    }

    public function connect(array $config): Connection
    {
        if (!isset($config['type'])) {
            throw new ConnectionException('type is not defined');
        }

        $type = $config['type'];

        if (isset($this->drivers[$type])) {
            return $this->drivers[$type]($config);
        }

        throw new ConnectionException('unrecognised type');
    }
}