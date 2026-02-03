<?php
declare(strict_types=1);
namespace PhpMVC\Cache\Driver;

/**
 * Interface Driver
 *
 * Defines the contract for cache drivers used by the PhpMVC caching system.
 * Implementations are responsible for storing, retrieving, and invalidating
 * cached values according to a time-based expiration policy.
 *
 * All drivers must support:
 *  - Existence checks with TTL awareness
 *  - Retrieval with a default fallback
 *  - Storing values with optional expiration
 *  - Removing individual cache entries
 *  - Flushing all cached entries
 *
 * Implementations may store data in memory, the filesystem, or other backends
 * (e.g., Redis, APCu), as long as they adhere to this interface.
 *
 * @package PhpMVC\Cache\Driver
 * @since   1.0
 */
interface Driver
{
    /**
     * Determine if a cache entry exists and is not expired.
     *
     * Implementations must return false if the key does not exist
     * or if the stored entry has exceeded its expiration time.
     *
     * @param string $key Cache key.
     *
     * @return bool True if the key exists and is valid; otherwise false.
     */
    public function has(string $key): bool;

    /**
     * Determine if a cache entry exists and is not expired.
     *
     * Implementations must return false if the key does not exist
     * or if the stored entry has exceeded its expiration time.
     *
     * @param string $key Cache key.
     *
     * @return bool True if the key exists and is valid; otherwise false.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Store a value in the cache.
     *
     * If `$seconds` is null, the driver should fall back to its
     * configured default TTL.
     *
     * @param string   $key     Cache key.
     * @param mixed    $value   Value to store.
     * @param int|null $seconds Time-to-live in seconds, or null to use default.
     *
     * @return static Fluent return for chaining.
     */
    public function put(string $key, mixed $value, ?int $seconds = null): static;

    /**
     * Remove a cached value by key.
     *
     * If the key does not exist, implementations should fail silently.
     *
     * @param string $key Cache key.
     *
     * @return static Fluent return for chaining.
     */
    public function forget(string $key): static;

    /**
     * Flush all cached values.
     *
     * This should remove all entries managed by the driver,
     * regardless of expiration state.
     *
     * @return static Fluent return for chaining.
     */
    public function flush(): static;
}
