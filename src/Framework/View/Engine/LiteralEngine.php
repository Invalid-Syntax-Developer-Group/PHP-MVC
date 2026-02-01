<?php
declare(strict_types=1);
namespace PhpMVC\Framework\View\Engine;

use PhpMVC\Framework\View\View;

final class LiteralEngine implements Engine
{
    use HasManager;

    public function render(View $view): string
    {
        return file_get_contents($view->path);
    }
}