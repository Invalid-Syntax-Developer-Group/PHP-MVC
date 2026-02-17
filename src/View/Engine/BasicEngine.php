<?php
declare(strict_types=1);
namespace PhpMVC\View\Engine;

use RuntimeException;
use PhpMVC\View\Traits\HasManager;
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
        if ($contents === false) {
            throw new RuntimeException("Failed to load view template: {$view->path}");
        }

        $replacements = [];
        foreach ($view->data as $key => $value) {
            if (!is_string($value) || !preg_match('/^[A-Za-z0-9_]+$/', $key)) {
                continue; // Skip non-string values and keys with invalid characters
            }

            $replacements['{'.$key.'}'] = (string)$value;
        }

        if (!empty($replacements)) {
            $contents = strtr($contents, $replacements);
        }

        return $contents;
    }
}
