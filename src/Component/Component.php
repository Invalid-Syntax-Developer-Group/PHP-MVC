<?php
declare(strict_types=1);
namespace PhpMVC\Component;

use Throwable;

/**
 * Class Component
 *
 * Base class for all UI components. Implements rendering contract and property management.
 *
 * @package PhpMVC\Component
 */
abstract class Component {
    /**
     * @var array<string, mixed> Immutable properties passed to the component
     */
    private array $props = [];

    /**
     * Component constructor.
     *
     * @param array<string, mixed> $props
     */
    public function __construct(array $props = []) {
        $this->props = $props;
    }

    /**
     * Get the component's properties.
     *
     * @return array<string, mixed>
     */
    public function getProps(): array {
        return $this->props;
    }

    /**
     * Render the component as a string.
     * Logs errors if rendering fails.
     *
     * @return string
     */
    public function __toString(): string {
        try {
            return $this->render();
        }
        catch (Throwable $e) {
            return '';
        }
    }

    /**
     * Render the component output.
     *
     * @return string
     */
    abstract public function render(): string;

    /**
     * Factory method to create and render a component.
     *
     * @param array<string, mixed> $props
     * @return string Rendered output
     */
    public static function make(array $props = []): string {
        return (new static($props))->render();
    }
}
