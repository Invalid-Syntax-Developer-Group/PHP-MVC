<?php
declare(strict_types=1);
namespace PhpMVC\Framework\View;

use PhpMVC\Framework\View\Engine\Engine;

/**
 * Class View
 *
 * Immutable view model representing a renderable template and its data.
 *
 * This class acts as a lightweight data carrier between controllers and
 * view engines. It encapsulates:
 *  - The view engine responsible for rendering
 *  - The template path on disk
 *  - The data to be injected into the template
 *
 * Rendering is deferred until the view is cast to a string, allowing
 * views to be passed around, returned, or composed before final output.
 *
 * Design notes:
 *  - Rendering responsibility is delegated entirely to the injected
 *    {@see Engine} implementation
 *  - The view itself contains no rendering logic
 *  - Public properties allow engines direct access without getters
 *
 * Typical usage:
 * ```php
 * $view = new View($engine, 'views/home.html', [
 *      'title' => 'Welcome',
 *      'content' => 'Hello world'
 *  ]);
 *
 *  echo $view; // triggers rendering
 * ```
 *
 * @package PhpMVC\Framework\View
 * @since   1.0
 */
final class View
{
    /**
     * View constructor.
     *
     * @param Engine $engine View engine used to render the template.
     * @param string $path   Absolute or relative path to the template file.
     * @param array  $data   Key/value data made available to the template.
     */
    public function __construct(
        protected Engine $engine,
        public string $path,
        public array $data = [],
    ) {}

    /**
     * Render the view to a string.
     *
     * This magic method allows the view instance to be directly echoed
     * or concatenated. Rendering is delegated to the configured
     * {@see Engine}.
     *
     * @return string Rendered view output.
     */
    public function __toString(): string
    {
        return $this->engine->render($this);
    }
}