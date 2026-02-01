<?php
declare(strict_types=1);
namespace PhpMVC\Framework\View\Engine;

use PhpMVC\Framework\View\View;

/**
 * Class LiteralEngine
 *
 * Literal (no-processing) view engine that returns the raw contents of a
 * view template file.
 *
 * This engine implements the {@see Engine} contract and provides the
 * simplest possible rendering strategy:
 *  - Reads the file located at {@see View::$path}
 *  - Returns the file contents as-is
 *
 * Characteristics:
 *  - No placeholder substitution
 *  - No directives, layouts, or macros
 *  - No escaping or sanitization
 *  - Ideal for static HTML/text templates or pre-rendered output
 *
 * Notes:
 *  - The {@see HasManager} trait is included for consistency with other
 *    engines and to satisfy the {@see Engine::setManager()} contract,
 *    though this engine does not directly use the manager.
 *
 * @package PhpMVC\Framework\View\Engine
 * @since   1.0
 */
final class LiteralEngine implements Engine
{
    use HasManager;

    /**
     * Render a view by returning its raw file contents.
     *
     * @param View $view View instance containing the template path.
     *
     * @return string Raw template contents.
     */
    public function render(View $view): string
    {
        return file_get_contents($view->path);
    }
}