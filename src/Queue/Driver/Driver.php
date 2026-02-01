<?php
declare(strict_types=1);
namespace PhpMVC\Queue\Driver;

use Closure;
use PhpMVC\Queue\Job;

interface Driver
{
    public function push(Closure $closure, ...$params): int;
    public function shift(): ?Job;
}