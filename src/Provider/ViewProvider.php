<?php
declare(strict_types=1);
namespace PhpMVC\Provider;

use PhpMVC\Core\Application;
use PhpMVC\View\Manager;
use PhpMVC\View\Engine\AdvancedEngine;
use PhpMVC\View\Engine\BasicEngine;
use PhpMVC\View\Engine\LiteralEngine;
use PhpMVC\View\Engine\PhpEngine;

/**
 * Class ViewProvider
 *
 * Service provider responsible for registering the view subsystem
 * and its template engines with the application container.
 *
 * This provider binds a shared {@see Manager} instance under the
 * `view` alias and configures:
 *  - Template search paths
 *  - View macros (helpers callable from engines)
 *  - Rendering engines keyed by file extension
 *
 * The {@see Manager} then resolves templates by searching all
 * configured paths and attempting each registered engine based
 * on supported extensions.
 *
 * @package PhpMVC\Provider
 */
final class ViewProvider
{
    /**
     * Register the view manager with the application container.
     *
     * Binds a `view` service that resolves to a {@see Manager}
     * configured with paths, macros, and engines.
     *
     * @param Application $app The application container instance.
     *
     * @return void
     */
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

    /**
     * Register template search paths.
     *
     * These paths are used by {@see Manager::render()} when resolving
     * a template name into a real file path.
     *
     * Paths registered:
     *  - {base}/src/views
     *  - {base}/public/images (used for literal rendering, e.g. SVG)
     *
     * @param Application $app     The application container.
     * @param Manager     $manager The view manager instance.
     *
     * @return void
     */
    private function bindPaths(Application $app, Manager $manager): void
    {
        $manager->addPath($app->resolve('paths.base') . '/views');
        $manager->addPath($app->resolve('paths.base') . '/public/images');
    }

    /**
     * Register view macros (template helpers).
     *
     * Macros are callable helpers made available to engines that use
     * the manager (e.g. {@see AdvancedEngine} and {@see PhpEngine})
     * via magic calls routed to {@see Manager::useMacro()}.
     *
     * Macros registered:
     *  - escape   : HTML-escapes a value (ENT_QUOTES)
     *  - includes : renders/includes another view by calling `view(...)`
     *
     * @param Application $app     The application container.
     * @param Manager     $manager The view manager instance.
     *
     * @return void
     */
    private function bindMacros(Application $app, Manager $manager): void
    {
        $manager->addMacro('escape', fn($value) => htmlspecialchars($value, ENT_QUOTES));
        $manager->addMacro('includes', fn(...$params) => print view(...$params));
        $manager->addMacro('component', fn(...$params) => print component(...$params));
    }

    /**
     * Register view engines and associate them with extensions.
     *
     * Engines are bound into the container to allow swapping,
     * decorating, or reconfiguration. They are then registered
     * with the {@see Manager} by extension, which controls which
     * files are considered renderable and in what order they are
     * attempted.
     *
     * Engine bindings:
     *  - view.engine.basic    : {@see BasicEngine}
     *  - view.engine.advanced : {@see AdvancedEngine}
     *  - view.engine.php      : {@see PhpEngine}
     *  - view.engine.literal  : {@see LiteralEngine}
     *
     * Extension mappings:
     *  - basic.php    -> BasicEngine
     *  - advanced.php -> AdvancedEngine
     *  - php          -> PhpEngine
     *  - svg          -> LiteralEngine
     *
     * @param Application $app     The application container.
     * @param Manager     $manager The view manager instance.
     *
     * @return void
     */
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
