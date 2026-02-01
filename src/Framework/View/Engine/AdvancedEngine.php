<?php
declare(strict_types=1);
namespace PhpMVC\Framework\View\Engine;

use PhpMVC\Framework\View\Engine\HasManager;
use PhpMVC\Framework\View\View;

/**
 * Class AdvancedEngine
 *
 * Compiling view engine that transforms a lightweight directive-based
 * template syntax into executable PHP, caches the compiled output, and
 * supports layout inheritance via an `@extends(...)` directive.
 *
 * Core features:
 *  - Compilation + caching:
 *      - Compiles source templates into cached PHP files under:
 *        storage/framework/views
 *      - Cache file names are based on md5($view->path)
 *      - Recompiles when the source template has a newer modification time
 *  - Template directives (compiled to PHP):
 *      - @extends(...)
 *      - @if(...), @endif
 *      - @foreach(...), @endforeach
 *      - @<macro>(...) → $this-><macro>(...) (forwarded via __call to Manager macros)
 *      - {{ ... }}     → escaped output via $this->escape(...)
 *      - {!! ... !!}   → raw output
 *  - Layouts:
 *      - When @extends is encountered, the engine registers a layout for the
 *        compiled template and later renders it, injecting `contents` into the
 *        layout’s view data.
 *
 * Integration:
 *  - Uses {@see HasManager} to access the view manager macro system.
 *  - Relies on a global/helper `view()` function to render layouts.
 *  - Expects an `escape()` macro/implementation for safe `{{ ... }}` output.
 *
 * @package PhpMVC\Framework\View\Engine
 * @since   1.0
 */
final class AdvancedEngine implements Engine
{
    use HasManager;

    /**
     * @var array<string,string> Map of compiled template path => layout template identifier.
     */
    protected $layouts = [];

    /**
     * Forward unknown method calls to the view manager macro system.
     *
     * This enables compiled directives like `@something(...)` to be translated into
     * `$this->something(...)` and resolved dynamically via macros registered on the manager.
     *
     * @param string $name   Macro name.
     * @param mixed  $values Macro arguments.
     *
     * @return mixed Macro return value.
     */
    public function __call(string $name, $values)
    {
        return $this->manager->useMacro($name, ...$values);
    }

    /**
     * Render a view into a string.
     *
     * Rendering pipeline:
     *  1) Compute a deterministic cache key for the view path
     *  2) Ensure a cache file exists in the compiled views directory
     *  3) Compile and write the cached PHP file if the cache is missing or stale
     *  4) Extract view data variables into the local scope
     *  5) Include the cached PHP under output buffering to capture output
     *  6) If a layout was registered for this compiled view, render the layout
     *     and inject `contents` into the view data
     *
     * @param View $view View instance containing template path and data.
     *
     * @return string Rendered view output.
     */
    public function render(View $view): string
    {
        $hash = md5($view->path);
        $folder = __DIR__ . '/../../../storage/framework/views';

        if (!is_file("{$folder}/{$hash}.php")) {
            touch("{$folder}/{$hash}.php");
        }

        $cached = realpath("{$folder}/{$hash}.php");

        if (!file_exists($hash) || filemtime($view->path) > filemtime($hash)) {
            $content = $this->compile(file_get_contents($view->path));
            file_put_contents($cached, $content);
        }

        extract($view->data);

        ob_start();
        include($cached);
        $contents = ob_get_contents();
        ob_end_clean();

        if ($layout = $this->layouts[$cached] ?? null) {
            $contentsWithLayout = view($layout, array_merge(
                $view->data,
                ['contents' => $contents],
            ));

            return $contentsWithLayout;
        }

        return $contents;
    }

    /**
     * Compile a template string into executable PHP.
     *
     * Supported transformations:
     *  - `@extends(expr)`     → `<?php $this->extends(expr); ?>`
     *  - `@if(expr)`          → `<?php if(expr): ?>`
     *  - `@endif`             → `<?php endif; ?>`
     *  - `@foreach(expr)`     → `<?php foreach(expr): ?>`
     *  - `@endforeach`        → `<?php endforeach; ?>`
     *  - `@name(expr)`        → `<?php $this->name(expr); ?>` (macro call)
     *  - `{{ expr }}`         → `<?php echo $this->escape(expr); ?>`
     *  - `{!! expr !!}`       → `<?php echo expr; ?>`
     *
     * @param string $template Raw template contents.
     *
     * @return string Compiled PHP template contents.
     */
    protected function compile(string $template): string
    {
        // replace `@extends` with `$this->extends`
        $template = preg_replace_callback('#@extends\(((?<=\().*(?=\)))\)#', function($matches) {
            return '<?php $this->extends(' . $matches[1] . '); ?>';
        }, $template);

        // replace `@if` with `if(...):`
        $template = preg_replace_callback('#@if\(((?<=\().*(?=\)))\)#', function($matches) {
            return '<?php if(' . $matches[1] . '): ?>';
        }, $template);

        // replace `@endif` with `endif;`
        $template = preg_replace_callback('#@endif#', function($matches) {
            return '<?php endif; ?>';
        }, $template);

        // replace `@foreach` with `foreach(...):`
        $template = preg_replace_callback('#@foreach\(((?<=\().*(?=\)))\)#', function($matches) {
            return '<?php foreach(' . $matches[1] . '): ?>';
        }, $template);

        // replace `@endforeach` with `endforeach;`
        $template = preg_replace_callback('#@endforeach#', function($matches) {
            return '<?php endforeach; ?>';
        }, $template);

        // replace `@[anything](...)` with `$this->[anything](...)`
        $template = preg_replace_callback('#\s+@([^(]+)\(((?<=\().*(?=\)))\)#', function($matches) {
            return '<?php $this->' . $matches[1] . '(' . $matches[2] . '); ?>';
        }, $template);

         // replace `{{ ... }}` with `echo $this->escape(...)`
        $template = preg_replace_callback('#\{\{([^}]*)\}\}#', function($matches) {
            return '<?php echo $this->escape(' . $matches[1] . '); ?>';
        }, $template);

        // replace `{!! ... !!}` with `echo ...`
        $template = preg_replace_callback('#\{!!([^}]+)!!\}#', function($matches) {
            return '<?php echo ' . $matches[1] . '; ?>';
        }, $template);

        return $template;
    }

    /**
     * Register a layout template for the currently executing compiled template.
     *
     * This method is invoked from compiled templates when `@extends(...)` is used.
     * The engine uses a backtrace to determine which compiled template file invoked
     * the call and stores a mapping of compiled-file-path => layout template name.
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