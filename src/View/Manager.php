<?php
declare(strict_types=1);
namespace PhpMVC\View;

use Closure;
use Exception;
use PhpMVC\View\Engine\Engine;;
use PhpMVC\View\View;

/**
 * Class Manager
 *
 * Central view manager responsible for locating templates, selecting an
 * appropriate rendering engine, and providing a macro system for engines
 * that support directive-style extensibility.
 *
 * Responsibilities:
 *  - Maintain a list of template search paths
 *  - Maintain a registry of view engines keyed by file extension
 *  - Resolve a template into a {@see View} instance using registered engines/paths
 *  - Provide a macro registry that engines can invoke at runtime
 *
 * Template resolution:
 *  - Engines are iterated in the order they were registered
 *  - For each engine extension, each configured path is checked for:
 *      <path>/<template>.<extension>
 *  - The first match is returned as a {@see View} bound to that engine
 *
 * Macro system:
 *  - Macros are registered by name and stored as closures
 *  - When invoked, the macro closure is bound to the Manager instance,
 *    allowing macros to access Manager state as `$this`
 *
 * @package PhpMVC\View
 * @since   1.0
 */
class Manager
{
    /**
     * @var string[] Template base paths used for template discovery.
     */
    protected array $paths = [];

    /**
     * @var array<string,Engine> Registered engines keyed by extension (e.g. 'php', 'html').
     */
    protected array $engines = [];

    /**
     * @var array<string,Closure> Registered macros keyed by macro name.
     */
    protected array $macros = [];

    /**
     * Add a template search path.
     *
     * Paths are checked during template resolution in the order they are added.
     *
     * @param string $path Base directory containing view templates.
     *
     * @return static Fluent return for chaining.
     */
    public function addPath(string $path): static
    {
        array_push($this->paths, $path);
        return $this;
    }

    /**
     * Register a view rendering engine for a given file extension.
     *
     * The engine is stored in the registry and receives the Manager instance
     * via {@see Engine::setManager()} for macro support and shared context.
     *
     * @param string $extension File extension (without dot) to associate with the engine.
     * @param Engine $engine    Rendering engine implementation.
     *
     * @return static Fluent return for chaining.
     */
    public function addEngine(string $extension, Engine $engine): static
    {
        $this->engines[$extension] = $engine;
        $this->engines[$extension]->setManager($this);
        return $this;
    }

    /**
     * Resolve and render a template with the given data.
     *
     * The template is located by searching registered paths and engines.
     * If found, a {@see View} instance is returned, bound to the appropriate
     * engine and template file.
     *
     * Template names can use either dot (.) or slash (/) notation for
     * directory separators. Additionally, an engine selector syntax is
     * supported: "template.engine" will attempt to use the engine associated
     * with the "engine" extension when resolving "template".
     *
     * @param string $template Template name to resolve.
     * @param array  $data     Data to bind to the view.
     *
     * @return View Resolved view instance.
     *
     * @throws Exception If the template cannot be resolved.
     */
    public function render(string $template, array $data = []): View
    {
        $template = str_replace('\\', '/', $template);

        if (str_contains($template, '..')) {
            throw new Exception("Invalid template '{$template}'");
        }

        // Try both notations (some parts of the framework turn dots into slashes)
        $candidates = [$template];

        if (str_contains($template, '.')) {
            $candidates[] = str_replace('.', '/', $template);
        }
        if (str_contains($template, '/')) {
            $candidates[] = str_replace('/', '.', $template);
        }

        $candidates = array_values(array_unique($candidates));

        foreach ($candidates as $candidate) {
            $candidate = trim($candidate, "/ \t\n\r\0\x0B.");

            $engines = $this->engines;
            $templateBase = $candidate;

            // Engine selector syntax: "layout.advanced" means:
            // - template base: "layout"
            // - engine extension: "advanced.php" (or "advanced")
            if (str_contains($candidate, '.') && !str_contains($candidate, '/')) {
                $pos = strrpos($candidate, '.');
                $base = substr($candidate, 0, $pos);
                $selector = substr($candidate, $pos + 1);

                $preferredExtensions = [];
                if (array_key_exists($selector, $this->engines)) {
                    $preferredExtensions[] = $selector;
                }
                if (array_key_exists($selector . '.php', $this->engines)) {
                    $preferredExtensions[] = $selector . '.php';
                }

                $preferredExtensions = array_values(array_unique($preferredExtensions));

                if (!empty($base) && count($preferredExtensions) > 0) {
                    $templateBase = $base;
                    $engines = array_intersect_key($this->engines, array_flip($preferredExtensions));
                }
            }

            foreach ($engines as $extension => $engine) {
                foreach ($this->paths as $path) {
                    $file = "{$path}/{$templateBase}.{$extension}";

                    if (is_file($file)) {
                        return new View($engine, realpath($file), $data);
                    }
                }
            }
        }

        throw new Exception("Could not resolve '{$template}'");
    }

    /**
     * Register a macro callable by name.
     *
     * Macros are intended for use by directive-capable engines (e.g. AdvancedEngine),
     * enabling templates to call `$this->macroName(...)` at render time.
     *
     * @param string  $name    Macro name.
     * @param Closure $closure Macro implementation.
     *
     * @return static Fluent return for chaining.
     */
    public function addMacro(string $name, Closure $closure): static
    {
        $this->macros[$name] = $closure;
        return $this;
    }

    /**
     * Invoke a registered macro by name.
     *
     * The stored macro closure is bound to the Manager instance, allowing macros
     * to access Manager state via `$this` when executed.
     *
     * @param string $name   Macro name.
     * @param mixed  $values Macro arguments.
     *
     * @return mixed Macro return value.
     *
     * @throws Exception If the macro does not exist.
     */
    public function useMacro(string $name, ...$values): mixed
    {
        if (isset($this->macros[$name])) {
            $bound = $this->macros[$name]->bindTo($this);
            return $bound(...$values);
        }

        throw new Exception("Macro isn't defined: '{$name}'");
    }
}
