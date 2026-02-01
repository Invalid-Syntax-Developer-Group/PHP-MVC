<?php
declare(strict_types=1);
namespace PhpMVC\Cache\Driver;

use PhpMVC\Application;

/**
 * Class FileDriver
 *
 * File-based cache driver that stores cached values as JSON files on disk.
 *
 * This driver implements the {@see Driver} contract and provides a simple
 * key/value cache with TTL (time-to-live) semantics:
 *  - Each cache entry is stored in an individual JSON file named by SHA-1 hash of the key
 *  - Entries include an `expires` timestamp (unix epoch seconds) and a `value`
 *  - In-memory caching is also maintained per request to reduce disk reads
 *
 * Storage location:
 *  - Resolved from the application's base path:
 *      <base>/storage/framework/cache
 *    where <base> is resolved via {@see Application::getInstance()} and
 *    the container key `paths.base`.
 *
 * Expiration model:
 *  - {@see has()} returns true only when an entry exists and has not expired
 *  - Expired entries are treated as missing (files may still exist until overwritten/forgotten/flush)
 *
 * Configuration:
 *  - Expects `$config['seconds']` to provide a default TTL in seconds when none is supplied to put()
 *
 * @package PhpMVC\Cache\Driver
 * @version 1.0
 * @since   2026-01-31
 */
class FileDriver implements Driver
{
    /**
     * @var array<string,mixed> Driver configuration.
     */
    private array $config = [];

    /**
     * @var array<string,array{value:mixed,expires:int}> In-memory cache for this request.
     */
    private array $cached = [];

    /**
     * FileDriver constructor.
     *
     * @param array<string,mixed> $config Driver configuration (expects 'seconds' default TTL).
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Determine whether a cache entry exists and is not expired.
     *
     * Reads the cached value from disk (and stores it in-memory) then validates
     * the `expires` timestamp against the current time.
     *
     * @param string $key Cache key.
     *
     * @return bool True if present and not expired; otherwise false.
     */
    public function has(string $key): bool
    {
        $data = $this->cached[$key] = $this->read($key);

        return isset($data['expires']) and $data['expires'] > time();
    }

    /**
     * Retrieve a cached value by key.
     *
     * Returns the stored value if the entry exists and is not expired;
     * otherwise returns the provided default.
     *
     * @param string $key     Cache key.
     * @param mixed  $default Default value if the key is missing/expired.
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
     * If `$seconds` is not provided, the driver falls back to `$config['seconds']`.
     * The cache payload stored on disk contains:
     *  - value   : mixed
     *  - expires : int (unix epoch seconds)
     *
     * @param string   $key     Cache key.
     * @param mixed    $value   Value to store.
     * @param int|null $seconds TTL in seconds; defaults to config seconds when null.
     *
     * @return static Fluent return for chaining.
     */
    public function put(string $key, mixed $value, ?int $seconds = null): static
    {
        if (!is_int($seconds)) {
            $seconds = (int) $this->config['seconds'];
        }

        $data = $this->cached[$key] = [
            'value' => $value,
            'expires' => time() + $seconds,
        ];

        return $this->write($key, $data);
    }

    /**
     * Remove a cache entry by key.
     *
     * Deletes the in-memory cached value and removes the corresponding
     * cache file from disk if it exists.
     *
     * @param string $key Cache key.
     *
     * @return static Fluent return for chaining.
     */
    public function forget(string $key): static
    {
        unset($this->cached[$key]);
        
        $path = $this->path($key);

        if (is_file($path)) {
            unlink($path);
        }

        return $this;
    }

    /**
     * Flush all cache entries for this driver.
     *
     * Clears the in-memory cache and removes all JSON cache files from
     * the configured cache directory.
     *
     * @return static Fluent return for chaining.
     */
    public function flush(): static
    {
        $this->cached = [];

        $base = $this->base();
        $separator = DIRECTORY_SEPARATOR;

        $files = glob("{$base}{$separator}*.json");

        foreach ($files as $file){
            if (is_file($file)) {
                unlink($file); 
            }
        }

        return $this;
    }

    /**
     * Resolve the full file path for a cache key.
     *
     * Cache file naming strategy:
     *  - sha1($key).json
     *
     * @param string $key Cache key.
     *
     * @return string Absolute path to the cache file.
     */
    private function path(string $key): string
    {
        $base = $this->base();
        $separator = DIRECTORY_SEPARATOR;
        $key = sha1($key);

        return "{$base}{$separator}{$key}.json";
    }

    /**
     * Resolve the base cache directory path.
     *
     * Uses the application container to resolve 'paths.base' and appends
     * the framework cache path segments:
     *  storage/framework/cache
     *
     * @return string Absolute cache directory path.
     */
    private function base(): string
    {
        $base = Application::getInstance()->resolve('paths.base');
        $separator = DIRECTORY_SEPARATOR;

        return "{$base}{$separator}storage{$separator}framework{$separator}cache";
    }

    /**
     * Read a cache entry from disk.
     *
     * If the cache file does not exist, returns an empty array.
     * When present, the JSON file is decoded as an associative array.
     *
     * @param string $key Cache key.
     *
     * @return array<string,mixed> Decoded cache payload or empty array if missing.
     */
    private function read(string $key): array
    {
        $path = $this->path($key);

        if (!is_file($path)) {
            return [];
        }

        return json_decode(file_get_contents($path), true);
    }

    /**
     * Persist a cache payload to disk.
     *
     * Writes the JSON-encoded payload to the key's cache file.
     *
     * @param string $key   Cache key.
     * @param mixed  $value Cache payload to encode and write.
     *
     * @return static Fluent return for chaining.
     */
    private function write(string $key, mixed $value): static
    {
        file_put_contents($this->path($key), json_encode($value));
        return $this;
    }
}