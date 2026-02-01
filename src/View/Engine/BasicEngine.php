<?php
declare(strict_types=1);
namespace PhpMVC\View\Engine;

use PhpMVC\View\Engine\HasManager;
use PhpMVC\View\View;

/**
 * Class BasicEngine
 *
 * Minimal, string-replacement–based view rendering engine.
 *
 * This engine implements the {@see Engine} contract and provides a very
 * lightweight templating mechanism intended for simple views or legacy
 * templates where full template engines (Twig, Blade, etc.) would be
 * unnecessary overhead.
 *
 * Rendering model:
 *  - Loads the raw template contents from {@see View::$path}
 *  - Iterates over {@see View::$data}
 *  - Replaces placeholders of the form `{key}` with their corresponding values
 *
 * Example:
 *  Template:
 *      <h1>{title}</h1>
 *
 *  View data:
 *      ['title' => 'Hello World']
 *
 *  Result:
 *      <h1>Hello World</h1>
 *
 * @package PhpMVC\View\Engine
 * @since   1.0
 */
final class BasicEngine implements Engine
{
    use HasManager;

    /**
     * Render a view into a string.
     *
     * Loads the view template from disk and performs placeholder replacement
     * using the view's data array.
     *
     * Placeholders are expected in the form `{key}` and are replaced using
     * simple string substitution.
     *
     * @param View $view View instance containing the template path and data.
     *
     * @return string Rendered view contents.
     */
    public function render(View $view): string
    {
        $contents = file_get_contents($view->path);

        foreach ($view->data as $key => $value) {
            $contents = str_replace(
                '{'.$key.'}', $value, $contents
            );
        }

        return $contents;
    }
}