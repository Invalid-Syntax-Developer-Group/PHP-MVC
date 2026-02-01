<?php
declare(strict_types=1);
namespace PhpMVC\Framework\Provider;

use PhpMVC\Framework\Application;
use PhpMVC\Framework\View\Manager;
use PhpMVC\Framework\View\Engine\AdvancedEngine;
use PhpMVC\Framework\View\Engine\BasicEngine;
use PhpMVC\Framework\View\Engine\LiteralEngine;
use PhpMVC\Framework\View\Engine\PhpEngine;

final class ViewProvider
{
    public function bind(Application $app): void
    {
        $app->bind('view', function($app) {
            $manager = new Manager();
    
            $this->bindPaths($app, $manager);
            $this->bindMacros($app, $manager);
            $this->bindEngines($app, $manager);
    
            return $manager;
        });
    }

    private function bindPaths(Application $app, Manager $manager): void
    {
        $manager->addPath($app->resolve('paths.base') . '/resources/views');
        $manager->addPath($app->resolve('paths.base') . '/resources/images');
    }

    private function bindMacros(Application $app, Manager $manager): void
    {
        $manager->addMacro('escape', fn($value) => htmlspecialchars($value, ENT_QUOTES));
        $manager->addMacro('includes', fn(...$params) => print view(...$params));
    }

    private function bindEngines(Application $app, Manager $manager): void
    {
        $app->bind('view.engine.basic', fn() => new BasicEngine());
        $app->bind('view.engine.advanced', fn() => new AdvancedEngine());
        $app->bind('view.engine.php', fn() => new PhpEngine());
        $app->bind('view.engine.literal', fn() => new LiteralEngine());

        $manager->addEngine('basic.php', $app->resolve('view.engine.basic'));
        $manager->addEngine('advanced.php', $app->resolve('view.engine.advanced'));
        $manager->addEngine('php', $app->resolve('view.engine.php'));
        $manager->addEngine('svg', $app->resolve('view.engine.literal'));
    }
}