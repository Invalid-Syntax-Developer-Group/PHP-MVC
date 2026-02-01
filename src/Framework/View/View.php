<?php
declare(strict_types=1);
namespace PhpMVC\Framework\View;

use PhpMVC\View\Engine\Engine;

class View
{
    public function __construct(
        protected Engine $engine,
        public string $path,
        public array $data = [],
    ) {}

    public function __toString()
    {
        return $this->engine->render($this);
    }
}