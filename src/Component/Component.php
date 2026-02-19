<?php
declare(strict_types=1);
namespace PhpMVC\Component;

use Throwable;

abstract class Component {
    protected array $props = [];

    public function __construct(array $props = []) {
        $this->props = $props;
    }

    public function __toString(): string {
        try {
            return $this->render();
        }
        catch (Throwable $e) {
            return '';
        }
    }

    abstract public function render(): string;

    public static function make(array $props = []): string {
        return (new static($props))->render();
    }
}
