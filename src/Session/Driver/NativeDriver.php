<?php
declare(strict_types=1);
namespace PhpMVC\Session\Driver;

/**
 * Class NativeDriver
 *
 * Native PHP session driver implementation.
 *
 * This driver uses PHP's built-in `$_SESSION` superglobal as the
 * underlying storage mechanism and applies a configurable key
 * prefix to avoid collisions with other session consumers.
 *
 * The driver ensures a session is started upon construction if one
 * is not already active.
 *
 * @package PhpMVC\Session\Driver
 */
class NativeDriver implements Driver
{
    /**
     * Driver configuration options.
     *
    * Expected keys:
    *  - prefix : string key prefix applied to all session entries
    *  - name   : optional session name
    *  - cookie : optional array of cookie settings
    *            (lifetime, path, domain, secure, httponly, samesite)
     *
     * @var array
     */
    private array $config = [];

    /**
     * NativeDriver constructor.
     *
     * Initializes the session with the provided configuration.
     * Starts the session if not already active.
     *
     * @param array $config Driver configuration options.
     */
    public function __construct(array $config)
    {
        $this->config = $config;

        if (session_status() !== PHP_SESSION_ACTIVE) {
            if (!empty($this->config['name'])) {
                session_name((string) $this->config['name']);
            }

            $cookieOptions = $this->resolveCookieOptions();
            if ($cookieOptions !== null) {
                session_set_cookie_params($cookieOptions);
            }
            session_start();
        }
    }

    /**
     * Determine if a session value exists for the given key.
     *
     * The configured prefix is automatically applied when resolving
     * the session key.
     *
     * @param string $key Session key (without prefix).
     *
     * @return bool True if the prefixed key exists in the session.
     */
    public function has(string $key): bool
    {
        $prefix = $this->config['prefix'];
        $sessionKey = !empty($prefix) ? "{$prefix}{$key}" : $key;
        return isset($_SESSION[$sessionKey]);
    }

    /**
     * Retrieve a value from the session.
     *
     * If the key does not exist, the provided default value is returned.
     * The configured prefix is automatically applied.
     *
     * @param string $key     Session key (without prefix).
     * @param mixed  $default Default value if the key is not found.
     *
     * @return mixed The stored session value or the default.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $prefix = $this->config['prefix'];
        $sessionKey = !empty($prefix) ? "{$prefix}{$key}" : $key;

        if (isset($_SESSION[$sessionKey])) {
            return $_SESSION[$sessionKey];
        }

        return $default;
    }

    /**
     * Store a value in the session.
     *
     * The configured prefix is applied to the session key.
     *
     * @param string $key   Session key (without prefix).
     * @param mixed  $value Value to store.
     *
     * @return static Returns the driver instance for fluent chaining.
     */
    public function put(string $key, mixed $value): static
    {
        $prefix = $this->config['prefix'];
        $sessionKey = !empty($prefix) ? "{$prefix}{$key}" : $key;
        $_SESSION[$sessionKey] = $value;
        return $this;
    }

    /**
     * Remove a value from the session.
     *
     * The configured prefix is applied to the session key.
     *
     * @param string $key Session key (without prefix).
     *
     * @return static Returns the driver instance for fluent chaining.
     */
    public function forget(string $key): static
    {
        $prefix = $this->config['prefix'];
        $sessionKey = !empty($prefix) ? "{$prefix}{$key}" : $key;
        unset($_SESSION[$sessionKey]);
        return $this;
    }

    /**
     * Remove all session values managed by this driver.
     *
     * Only session keys that begin with the configured prefix
     * are removed, leaving unrelated session data untouched.
     *
     * @return static Returns the driver instance for fluent chaining.
     */
    public function flush(): static
    {
        $prefix = $this->config['prefix'];

        foreach (array_keys($_SESSION) as $key) {
            if (str_starts_with($key, $prefix)) {
                unset($_SESSION[$key]);
            }
        }

        return $this;
    }

    /**
     * Build session cookie options from configuration.
     *
     * @return array|null Cookie options for session_set_cookie_params or null if none configured.
     */
    private function resolveCookieOptions(): ?array
    {
        $cookieConfig = $this->config['cookie'] ?? null;

        if (!is_array($cookieConfig) || $cookieConfig === []) {
            return null;
        }

        $defaults = session_get_cookie_params();

        $options = [
            'lifetime' => $defaults['lifetime'] ?? 0,
            'path' => $defaults['path'] ?? '/',
            'domain' => $defaults['domain'] ?? '',
            'secure' => $defaults['secure'] ?? false,
            'httponly' => $defaults['httponly'] ?? false,
        ];

        if (array_key_exists('samesite', $defaults)) {
            $options['samesite'] = $defaults['samesite'];
        }

        if (array_key_exists('lifetime', $cookieConfig)) {
            $options['lifetime'] = (int)$cookieConfig['lifetime'];
        }

        if (array_key_exists('path', $cookieConfig)) {
            $options['path'] = (string)$cookieConfig['path'];
        }

        if (array_key_exists('domain', $cookieConfig)) {
            $options['domain'] = (string)$cookieConfig['domain'];
        }

        if (array_key_exists('secure', $cookieConfig)) {
            $options['secure'] = (bool)$cookieConfig['secure'];
        }

        if (array_key_exists('httponly', $cookieConfig)) {
            $options['httponly'] = (bool)$cookieConfig['httponly'];
        }

        if (array_key_exists('samesite', $cookieConfig)) {
            $options['samesite'] = (string)$cookieConfig['samesite'];
        }

        return $options;
    }
}
