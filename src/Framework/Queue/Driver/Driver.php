<?php
declare(strict_types=1);
namespace PhpMVC\Framework\Queue\Driver;

use Closure;
use PhpMVC\Framework\Queue\Job;

interface Driver
{
    public function push(Closure $closure, ...$params): int;
    public function shift(): ?Job;
}