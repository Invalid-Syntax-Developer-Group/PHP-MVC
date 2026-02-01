<?php
declare(strict_types=1);
namespace PhpMVC\Framework\View\Engine;

use PhpMVC\Framework\View\Manager;
use PhpMVC\Framework\View\View;

/**
 * Interface Engine
 *
 * Contract for view rendering engines within the PhpMVC framework.
 *
 * An engine is responsible for transforming a {@see View} instance into
 * a rendered string output. Different implementations may support
 * different templating strategies (e.g. basic string replacement,
 * PHP-based templates, or third-party engines).
 *
 * Engine lifecycle:
 *  - The engine is registered with a {@see Manager}
 *  - The manager injects itself via {@see setManager()}
 *  - The engine renders views on demand via {@see render()}
 *
 * @package PhpMVC\View\Engine
 * @since   1.0
 */
interface Engine
{
    /**
     * Render a view into its final string representation.
     *
     * Implementations are responsible for reading the view template,
     * applying the view data, and returning the rendered output.
     *
     * @param View $view View instance containing template path and data.
     *
     * @return string Rendered view contents.
     */
    public function render(View $view): string;

    /**
     * Assign the view manager instance to the engine.
     *
     * This allows the engine to interact with or query the manager
     * for shared configuration, other engines, or rendering context.
     *
     * @param Manager $manager View manager instance.
     *
     * @return static Fluent return for chaining.
     */
    public function setManager(Manager $manager): static;
}