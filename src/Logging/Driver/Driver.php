<?php
declare(strict_types=1);
namespace PhpMVC\Logging\Driver;

interface Driver
{
    public function info(string $message): static;
    public function warning(string $message): static;
    public function error(string $message): static;
}