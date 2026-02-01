<?php
declare(strict_types=1);
namespace PhpMVC\View\Engine;

use PhpMVC\View\Manager;

/**
 * Trait HasManager
 *
 * Provides view engines with access to the central {@see Manager} instance.
 *
 * This trait is intended to be used by classes implementing a view
 * {@see Engine}. It enables the engine to receive and store a reference
 * to the view manager, allowing coordination with other engines, shared
 * configuration, or lifecycle management handled by the manager.
 *
 * Design notes:
 *  - The manager is injected via {@see setManager()} (setter injection)
 *  - The fluent return allows chaining during engine registration
 *  - The trait does not enforce how the manager is used; it merely
 *    provides consistent storage and access
 *
 * Typical usage:
 *  - Assigned by the view system when registering or activating an engine
 *  - Used by engines that need contextual awareness of the rendering system
 *
 * @package PhpMVC\View\Engine
 * @since   1.0
 */
trait HasManager
{
    /**
     * @var Manager Reference to the view manager.
     */
    protected Manager $manager;

    /**
     * Assign the view manager instance to the engine.
     *
     * @param Manager $manager View manager instance.
     *
     * @return static Fluent return for chaining.
     */
    public function setManager(Manager $manager): static
    {
        $this->manager = $manager;
        return $this;
    }
}