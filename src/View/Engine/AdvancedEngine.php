<?php
declare(strict_types=1);
namespace PhpMVC\View\Engine;

use RuntimeException;
use PhpMVC\View\View;
use PhpMVC\View\Traits\HasManager;

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
 * @package PhpMVC\View\Engine
 * @since   1.0
 */
final class AdvancedEngine implements Engine
{
    use HasManager;

    /**
     * @var array<string,string> Map of compiled template path => layout template identifier.
     */
    protected array $layouts = [];

    /**
     * @var string|null Resolved storage base path for current runtime.
     */
    protected ?string $storageBase = null;

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
    public function __call(string $name, $values): mixed
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
        $base = $this->resolveStorageBase();
        $folder = rtrim($base, '/\\') . DIRECTORY_SEPARATOR . 'views';

        if (!is_dir($folder)) mkdir($folder, 0755, true);

        $cached = "{$folder}/{$hash}.php";

        if (!is_file($cached)
        || filesize($cached) === 0
        || filemtime($view->path) > filemtime($cached)) {
            $source = file_get_contents($view->path);
            if ($source === false) {
                throw new RuntimeException("Failed to read view source: {$view->path}");
            }

            $content = $this->compile($source);

            $bytesWritten = file_put_contents($cached, $content, LOCK_EX);
            if ($bytesWritten === false) {
                throw new RuntimeException("Failed to write compiled view to cache: {$cached}");
            }
        }

        extract($view->data);

        ob_start();
        include($cached);
        $buffer = ob_get_contents();
        $contents = $buffer === false ? '' : $buffer;
        ob_end_clean();

        if ($layout = $this->layouts[$cached] ?? null) {
            $contentsWithLayout = view($layout, array_merge(
                $view->data,
                ['contents' => $contents],
            ));

            return (string)$contentsWithLayout;
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

    /**
     * Resolve the base storage path for compiled views.
     *
     * This method determines the appropriate base directory for storing compiled view
     * files based on the filesystem configuration. It checks for a configured default
     * filesystem driver and its associated path, with support for using a temporary
     * directory if specified. If no valid configuration is found, it falls back to
     * using `basePath()/storage` as the default location.
     *
     * @return string Resolved base path for compiled view storage.
     */
    private function resolveStorageBase(): string
    {
        if ($this->storageBase !== null) {
            return $this->storageBase;
        }

        $defaultDriver = (string)config('filesystem.default', '');
        if (empty($defaultDriver)) {
            $this->storageBase = rtrim(basePath() . '/storage', '/\\');
            return $this->storageBase;
        }

        $driverConfig = (array)config("filesystem.{$defaultDriver}", []);
        $configuredPath = (string)($driverConfig['path'] ?? 'storage');
        $pathSegment = trim($configuredPath, '/\\');
        if (empty($pathSegment)) $pathSegment = 'storage';

        $useTempDir = (bool)($driverConfig['use_temp_dir'] ?? false);
        if ($useTempDir) {
            $this->storageBase = rtrim(sys_get_temp_dir(), '/\\')
                . DIRECTORY_SEPARATOR
                . '{' . uniqid('PHP.APP.', true) . '}'
                . DIRECTORY_SEPARATOR
                . $pathSegment;

            return $this->storageBase;
        }

        $isWindowsAbsolute = (bool)preg_match('/^[A-Za-z]:[\\\\\/]/', $configuredPath)
            || str_starts_with($configuredPath, '\\\\');

        if ($isWindowsAbsolute) {
            $this->storageBase = rtrim($configuredPath, '/\\');
            return $this->storageBase;
        }

        $this->storageBase = rtrim(basePath(), '/\\') . DIRECTORY_SEPARATOR . $pathSegment;
        return $this->storageBase;
    }
}
