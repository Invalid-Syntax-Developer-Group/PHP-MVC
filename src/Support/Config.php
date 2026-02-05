<?php
declare(strict_types=1);
namespace PhpMVC\Support;

use PhpMVC\Core\Application;

/**
 * Class Config
 *
 * Lightweight configuration repository that loads PHP config files
 * on demand and supports dot-notation access to nested values.
 *
 * Configuration files are expected to live under:
 *   \<base\>/config/{file}.php
 *
 * Files are loaded lazily and cached in-memory for the lifetime
 * of the request to avoid repeated filesystem access.
 *
 * Example usage:
 *  - config('database.connections.mysql.host')
 *  - config('app.debug', false)
 *
 * @package PhpMVC\Support
 */
class Config
{
    /**
     * Cached configuration files that have already been loaded.
     *
     * The array key represents the config file name (without extension),
     * and the value is the returned configuration array.
     *
     * @var array<string, array>
     */
    private array $loaded = [];

    /**
     * Retrieve a configuration value using dot notation.
     *
     * The first segment of the key represents the config file name.
     * Remaining segments are resolved as nested array keys.
     *
     * If the file has not been loaded yet, it will be required
     * and cached automatically.
     *
     * @param string $key     Dot-notated configuration key.
     * @param mixed  $default Default value if the key cannot be resolved.
     *
     * @return mixed The resolved configuration value or the default.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $file = array_shift($segments);

        if (!isset($this->loaded[$file])) {
            $base = Application::getInstance()->resolve('paths.base');
            $separator = DIRECTORY_SEPARATOR;

            $this->loaded[$file] = (array) require "{$base}{$separator}config{$separator}{$file}.php";
        }

        if ($value = $this->withDots($this->loaded[$file], $segments)) {
            return $value;
        }

        return $default;
    }

    /**
     * Resolve nested array values using dot-style segments.
     *
     * Traverses the given array using the provided segments in order.
     * Returns null if any segment cannot be resolved.
     *
     * @param array $array    Base configuration array.
     * @param array $segments Remaining key segments.
     *
     * @return mixed|null The resolved value or null if not found.
     */
    private function withDots(array $array, array $segments): mixed
    {
        $current = $array;

        foreach ($segments as $segment) {
            if (!isset($current[$segment])) {
                return null;
            }

            $current = $current[$segment];
        }

        return $current;
    }
}
