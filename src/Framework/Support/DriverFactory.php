<?php
declare(strict_types=1);
namespace PhpMVC\Framework\Support;

use Closure;

interface DriverFactory
{
    public function addDriver(string $alias, Closure $driver): static;
    public function connect(array $config): mixed;
}