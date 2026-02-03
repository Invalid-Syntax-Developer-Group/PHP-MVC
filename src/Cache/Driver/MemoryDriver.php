<?php
declare(strict_types=1);
namespace PhpMVC\Cache\Driver;

/**
 * Class MemoryDriver
 *
 * In-memory cache driver implementing the {@see Driver} contract.
 *
 * This driver stores cached values entirely in PHP memory for the lifetime
 * of the current request (or process, depending on execution model).
 * It is intended for fast, ephemeral caching where persistence across
 * requests is not required.
 *
 * Characteristics:
 *  - Extremely fast (array-based lookup)
 *  - No filesystem or external service dependency
 *  - Cache is lost at the end of the request / process
 *  - Suitable for testing, development, or short-lived computed values
 *
 * Expiration model:
 *  - Each cached entry stores an absolute expiration timestamp
 *  - Expired entries are treated as missing
 *
 * Configuration:
 *  - Expects `$config['seconds']` to define the default TTL when none is
 *    provided to {@see put()}
 *
 * @package PhpMVC\Cache\Driver
 * @since   1.0
 */
class MemoryDriver implements Driver
{
    /**
     * @var array<string,mixed> Driver configuration options.
     */
    private array $config = [];

    /**
     * @var array<string,array{value:mixed,expires:int}> Cached values in memory.
     */
    private array $cached = [];

    /**
     * MemoryDriver constructor.
     *
     * @param array<string,mixed> $config Driver configuration (expects 'seconds' default TTL).
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Determine whether a cache entry exists and has not expired.
     *
     * @param string $key Cache key.
     *
     * @return bool True if the key exists and is not expired; otherwise false.
     */
    public function has(string $key): bool
    {
        return isset($this->cached[$key]) && $this->cached[$key]['expires'] > time();
    }

    /**
     * Retrieve a cached value by key.
     *
     * Returns the stored value if present and not expired; otherwise
     * returns the provided default value.
     *
     * @param string $key     Cache key.
     * @param mixed  $default Default value if the key is missing or expired.
     *
     * @return mixed Cached value or default.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if ($this->has($key)) {
            return $this->cached[$key]['value'];
        }

        return $default;
    }

    /**
     * Store a value in the cache with an optional TTL.
     *
     * If `$seconds` is not provided, the driver uses the default TTL
     * defined in the configuration.
     *
     * @param string   $key     Cache key.
     * @param mixed    $value   Value to store.
     * @param int|null $seconds Time-to-live in seconds; defaults to config value.
     *
     * @return static Fluent return for chaining.
     */
    public function put(string $key, mixed $value, ?int $seconds = null): static
    {
        if (!is_int($seconds)) {
            $seconds = (int) $this->config['seconds'];
        }

        $this->cached[$key] = [
            'value' => $value,
            'expires' => time() + $seconds,
        ];

        return $this;
    }

    /**
     * Remove a single cache entry by key.
     *
     * @param string $key Cache key.
     *
     * @return static Fluent return for chaining.
     */
    public function forget(string $key): static
    {
        unset($this->cached[$key]);
        return $this;
    }

    /**
     * Clear all cached entries.
     *
     * @return static Fluent return for chaining.
     */
    public function flush(): static
    {
        $this->cached = [];
        return $this;
    }
}
