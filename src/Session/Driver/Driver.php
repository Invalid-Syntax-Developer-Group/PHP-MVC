<?php
declare(strict_types=1);
namespace PhpMVC\Session\Driver;

/**
 * Interface Driver
 *
 * Contract for session storage drivers used by the framework.
 *
 * Implementations of this interface provide a uniform API for interacting
 * with session data regardless of the underlying storage mechanism
 * (e.g. native PHP sessions, database-backed sessions, cache-backed sessions).
 *
 * All methods are designed to be chainable where appropriate to allow
 * fluent usage within application code.
 *
 * @package PhpMVC\Session\Driver
 */
interface Driver
{
    /**
     * Determine if a session value exists for the given key.
     *
     * @param string $key The session key to check.
     *
     * @return bool True if the key exists in the session; otherwise false.
     */
    public function has(string $key): bool;

    /**
     * Retrieve a value from the session.
     *
     * If the key does not exist, the provided default value is returned.
     *
     * @param string $key     The session key to retrieve.
     * @param mixed  $default The value to return if the key does not exist.
     *
     * @return mixed The stored session value or the default.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Store a value in the session.
     *
     * Implementations should persist the value according to the
     * underlying session mechanism.
     *
     * @param string $key   The session key.
     * @param mixed  $value The value to store.
     *
     * @return static Returns the driver instance for fluent chaining.
     */
    public function put(string $key, mixed $value): static;

    /**
     * Remove a value from the session.
     *
     * @param string $key The session key to remove.
     *
     * @return static Returns the driver instance for fluent chaining.
     */
    public function forget(string $key): static;

    /**
     * Clear all session data.
     *
     * Implementations should remove all stored session values
     * for the current session context.
     *
     * @return static Returns the driver instance for fluent chaining.
     */
    public function flush(): static;
}
