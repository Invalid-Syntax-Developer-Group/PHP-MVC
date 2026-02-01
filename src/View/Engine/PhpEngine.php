<?php
declare(strict_types=1);
namespace PhpMVC\View\Engine;

use PhpMVC\View\Engine\HasManager;
use PhpMVC\View\View;

/**
 * Class PhpEngine
 *
 * Native PHP template engine that renders view files via `include` and supports
 * lightweight layout inheritance through an `extends()` mechanism.
 *
 * Unlike compiling engines, this engine executes the PHP template directly
 * (no intermediate compilation/caching step). Layout support is achieved by
 * allowing templates to declare a layout during execution and then wrapping
 * the captured output using a global/helper `view()` call.
 *
 * Features:
 *  - Direct PHP rendering:
 *      - Extracts {@see View::$data} into local variables for template usage
 *      - Includes the template file and captures output via output buffering
 *  - Layout inheritance:
 *      - Templates may call `$this->extends('layout.name')` to register a layout
 *      - If a layout is registered for the current template, a new view is rendered
 *        with `contents` injected into the view data
 *  - Macro forwarding:
 *      - Unknown method calls (e.g. `$this->escape(...)`) are forwarded to the
 *        view manager macro registry via {@see HasManager} and {@see Manager::useMacro()}
 *
 * Notes:
 *  - This engine expects a global/helper function `view($template, $data)` to exist
 *    for rendering layout templates.
 *  - Layout tracking is keyed by the real path of the calling template file.
 *  - This engine does not perform automatic escaping; use macros (e.g. `escape`)
 *    or sanitize upstream.
 *
 * @package PhpMVC\View\Engine
 * @since   1.0
 */
final class PhpEngine implements Engine
{
    use HasManager;

    /**
     * @var array<string,string> Map of template file path => layout template identifier.
     */
    protected array $layouts = [];

    /**
     * Forward unknown method calls to the view manager macro system.
     *
     * Enables templates to call `$this->macroName(...)` (including helpers such as
     * escape, csrf, asset, etc.) without the engine implementing those methods directly.
     *
     * @param string $name   Macro name.
     * @param mixed  $values Macro arguments.
     *
     * @return mixed Macro return value.
     */
    public function __call(string $name, $values): mixed
    {
        return $this->manager->useMacro($name, ...$values);
    }

    /**
     * Render a view into a string.
     *
     * Rendering pipeline:
     *  1) Extract view data into the local symbol table so templates can reference variables by name
     *  2) Include the template file under output buffering to capture output
     *  3) If the template registered a layout (via extends()), render the layout and inject:
     *      - all original view data
     *      - `contents` containing the captured template output
     *
     * @param View $view View instance containing template path and data.
     *
     * @return string Rendered output (optionally wrapped in a layout).
     */
    public function render(View $view): string
    {
        extract($view->data);

        ob_start();
        include($view->path);
        $contents = ob_get_contents();
        ob_end_clean();

        if ($layout = $this->layouts[$view->path] ?? null) {
            $contentsWithLayout = view($layout, array_merge(
                $view->data,
                ['contents' => $contents],
            ));

            return (string)$contentsWithLayout;
        }

        return $contents;
    }

    /**
     * Register a layout for the currently rendering template.
     *
     * Typically called inside a template file to declare that it should be
     * wrapped by a layout. The layout identifier is stored and later used
     * by {@see render()} after the template output has been captured.
     *
     * @param string $template Layout template identifier passed to `view()`.
     *
     * @return static Fluent return for chaining.
     */
    protected function extends(string $template): static
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $this->layouts[realpath($backtrace[0]['file'])] = $template;
        return $this;
    }
}