<?php
declare(strict_types=1);
namespace PhpMVC\Framework\FileSystem;

use Closure;
use PhpMVC\Framework\FileSystem\Driver\Driver;
use PhpMVC\Framework\FileSystem\Exception\DriverException;
use PhpMVC\Framework\Support\DriverFactory;

final class Factory implements DriverFactory
{
    protected array $drivers;

    public function addDriver(string $alias, Closure $driver): static
    {
        $this->drivers[$alias] = $driver;
        return $this;
    }

    public function connect(array $config): Driver
    {
        if (!isset($config['type'])) {
            throw new DriverException('type is not defined');
        }

        $type = $config['type'];

        if (isset($this->drivers[$type])) {
            return $this->drivers[$type]($config);
        }

        throw new DriverException('unrecognised type');
    }
}