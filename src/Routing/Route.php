<?php
declare(strict_types=1);
namespace PhpMVC\Routing;

use Exception;

/**
 * Class Route
 *
 * Represents a single routable endpoint in the application, consisting of:
 *  - an HTTP method (GET/POST/etc.),
 *  - a path pattern (static or parameterized),
 *  - a handler (callable or [class, method] tuple),
 *  - an optional route name,
 *  - and any parameters extracted from the request path when matched.
 *
 * Path patterns:
 *  - Static:      `/users`
 *  - Parameter:   `/users/{id}/`
 *  - Optional:    `/users/{id?}/`
 *
 * Parameter parsing is performed by {@see Route::matches()}:
 *  - `{param}`  captures one or more non-slash characters (required segment).
 *  - `{param?}` captures zero or more non-slash characters (optional segment).
 *  - Captured parameters are stored in {@see Route::parameters()} as an associative array.
 *
 * Handler dispatch:
 *  - If handler is an array: `[$classOrInstance, 'method']`
 *      - When `$classOrInstance` is a string, it is instantiated (`new $classOrInstance`)
 *        before calling via the application container: `app()->call(...)`.
 *  - If handler is a callable/closure: called via `app()->call($handler)`.
 *
 * Note:
 *  - This class expects a global `app()` helper that returns an object implementing `call(...)`
 *    with dependency resolution (e.g., a container).
 *
 * @package PhpMVC\Routing
 * @since 1.1
 */
class Route
{
    /**
     * HTTP method to match (e.g. GET, POST).
     *
     * @var string
     */
    protected string $method;

    /**
     * Route path pattern (may include parameter placeholders).
     *
     * @var string
     */
    protected string $path;

    /**
     * Handler definition for this route.
     *
     * Common shapes:
     *  - callable/Closure
     *  - array{0: object|string, 1: string}  (controller + method)
     *
     * @var mixed
     */
    protected $handler;

    /**
     * Parameters extracted from the request URI when {@see matches()} succeeds.
     *
     * @var array<string, mixed>
     */
    protected array $parameters = [];

    /**
     * Optional route name, used by {@see Router::route()} for URL generation.
     *
     * @var string|null
     */
    protected ?string $name = null;

    /**
     * Route constructor.
     *
     * @param string $method  HTTP method for the route (e.g. GET, POST).
     * @param string $path    Path pattern (static or parameterized).
     * @param mixed  $handler Handler (callable or [class, method] tuple).
     */
    public function __construct(string $method, string $path, $handler)
    {
        $this->method = $method;
        $this->path = $path;
        $this->handler = $handler;
    }

    /**
     * Get the HTTP method for this route.
     *
     * @return string HTTP method (e.g. GET, POST).
     */
    public function method(): string
    {
        return $this->method;
    }

    /**
     * Get the route path pattern.
     *
     * @return string The defined path pattern.
     */
    public function path(): string
    {
        return $this->path;
    }

    /**
     * Get the parameters captured during the last successful match.
     *
     * @return array<string, mixed> Associative parameter map.
     */
    public function parameters(): array
    {
        return $this->parameters;
    }

    /**
     * Get or set the route name.
     *
     * When `$name` is provided, sets the route name and returns `$this` for chaining.
     * When `$name` is null, returns the current route name (or null if not set).
     *
     * @param string|null $name Optional name to set.
     *
     * @return static|string|null Fluent self when setting; otherwise the current name.
     */
    public function name(?string $name = null)
    {
        if ($name) {
            $this->name = $name;
            return $this;
        }

        return $this->name;
    }

    /**
     * Determine whether this route matches a given request method and path.
     *
     * Matching strategy:
     *  1) Fast-path: exact method + exact path match.
     *  2) Parameterized match:
     *      - Normalizes the route pattern and request path (leading/trailing slashes).
     *      - Converts parameter placeholders to a regex and collects parameter names.
     *      - Runs a regex match and, if successful, stores parameters in `$this->parameters`.
     *
     * Placeholder rules:
     *  - `{name}`  => required segment; captures `([^/]+)`
     *  - `{name?}` => optional segment; captures `([^/]*)` and allows the slash to be optional
     *
     * Captured values:
     *  - Empty optional captures are normalized to `null`.
     *
     * Side effects:
     *  - On successful parameterized match, `$this->parameters` is populated.
     *
     * @param string $method Incoming HTTP method.
     * @param string $path   Incoming request path/URI.
     *
     * @return bool True if the route matches; otherwise false.
     */
    public function matches(string $method, string $path): bool
    {
        if ($this->method === $method && $this->path === $path) {
            return true;
        }

        $parameterNames = [];

        $pattern = $this->normalisePath($this->path);

        $pattern = preg_replace_callback('#{([^}]+)}/#', function (array $found) use (&$parameterNames) {
            array_push($parameterNames, rtrim($found[1], '?'));

            if (str_ends_with($found[1], '?')) {
                return '([^/]*)(?:/?)';    
            }

            return '([^/]+)/';
        }, $pattern);

        if (!str_contains($pattern, '+') && !str_contains($pattern, '*')) {
            return false;
        }

        preg_match_all("#{$pattern}#", $this->normalisePath($path), $matches);

        $parameterValues = [];

        if (count($matches[1]) > 0) {
            foreach ($matches[1] as $value) {
                if ($value) {
                    array_push($parameterValues, $value);
                    continue;
                }

                array_push($parameterValues, null);
            }

            $emptyValues = array_fill(0, count($parameterNames), false);
            $parameterValues += $emptyValues;

            $this->parameters = array_combine($parameterNames, $parameterValues);

            return true;
        }

        return false;
    }

    /**
     * Dispatch the route handler through the application container.
     *
     * Handler forms:
     *  - Array handler: `[$classOrInstance, 'method']`
     *      - If `$classOrInstance` is a string, a new instance is created and invoked.
     *      - If it is already an object, it is invoked directly.
     *  - Callable handler: invoked directly.
     *
     * This method delegates the actual invocation to `app()->call(...)`,
     * allowing the container to resolve any dependencies required by the handler.
     *
     * @return mixed The handler result.
     */
    public function dispatch(): mixed
    {
        if (is_array($this->handler)) {
            [$class, $method] = $this->handler;

            if (is_string($class)) {
                return app()->call([new $class, $method]);
            }

            return app()->call([$class, $method]);
        }

        return app()->call($this->handler);
    }

    /**
     * Enforce authentication for this route based on session configuration.
     *
     * Authentication flow:
     *  1) Checks if authentication is enabled in config.
     *  2) Skips authentication for configured path prefixes.
     *  3) Verifies presence of session cookie or incoming SID parameter.
     *  4) Validates required session key (e.g. user_id).
     *  5) Validates CSRF token from session against request data.
     *
     * On failure at any step, redirects to the configured login page with a return URL.
     *
     * @return static Fluent self for chaining.
     *
     * @throws Exception If session is not enabled or CSRF validation fails.
     */
    public function secure(): static
    {
        $this->authenticate();
        return $this;
    }

    /**
     * Normalize a path to a consistent form for matching.
     *
     * Normalization rules:
     *  - Trims leading/trailing slashes.
     *  - Ensures the path starts and ends with a single slash.
     *  - Collapses multiple slashes into one.
     *
     * @param string $path Raw path string.
     *
     * @return string Normalized path.
     */
    private function normalisePath(string $path): string
    {
        $path = trim($path, '/');
        $path = "/{$path}/";
        $path = preg_replace('/[\/]{2,}/', '/', $path);

        return $path;
    }

    /**
     * Authenticate the request based on session data and configuration.
     *
     * Authentication flow:
     *  1) Checks if authentication is enabled in config.
     *  2) Skips authentication for configured path prefixes.
     *  3) Verifies presence of session cookie or incoming SID parameter.
     *  4) Validates required session key (e.g. user_id).
     *  5) Validates CSRF token from session against request data.
     *
     * On failure at any step, redirects to the configured login page with a return URL.
     *
     * @throws Exception If session is not enabled or CSRF validation fails.
     */
    private function authenticate(): void
    {
        $authentication = (array)config('session.auth', []);
        if ((!$authentication['enabled'] ?? false)) return;

        $requestUri = (string)filter_input(INPUT_SERVER, 'REQUEST_URI', FILTER_UNSAFE_RAW);
        foreach ((array)($authentication['skip_paths'] ?? []) as $prefix) {
            $prefix = (string)$prefix;
            if (!empty($prefix) && str_starts_with($requestUri, $prefix)) {
                return;
            }
        }

        // Ensure the session exists (and cookie params are applied) before we decide to redirect.
        // Resolving the session driver will start PHP's session if needed.
        $sessionDriver = session();
        if (!$sessionDriver) {
            throw new Exception('Session is not enabled');
        }

        $cookieName = session_name();
        if (empty($cookieName)) {
            $cookieName = (string)config('session.default.name', 'PHPSESSID');
        }

        $hasCookie = isset($_COOKIE[$cookieName]) && is_string($_COOKIE[$cookieName]) && !empty($_COOKIE[$cookieName]);

        $hasIncomingSid = false;
        $incomingParams = (array)config('session.incoming_sid_params', []);
        foreach ($incomingParams as $paramName) {
            $candidate = filter_input(INPUT_GET, (string)$paramName, FILTER_UNSAFE_RAW);
            if (is_string($candidate) && !empty($candidate)) {
                $hasIncomingSid = true;
                break;
            }
        }

        // If this request did not present a session cookie, force the auth flow.
        // Allow an incoming sid parameter to prevent loops on the return-trip.
        if (!$hasCookie && !$hasIncomingSid) {
            $this->redirectToLogin($authentication, $requestUri);
        }

        $requiredKey = (string)($authentication['required_key'] ?? 'user_id');
        if (!$sessionDriver->has($requiredKey) || empty($sessionDriver->get($requiredKey))) {
            $this->redirectToLogin($authentication, $requestUri);
        }

        $token = (string)config('session.auth.csrf_identifier', 'token');
        if (!$sessionDriver->has($token) || !hash_equals($sessionDriver->get($token), $_POST[$token])) {
            throw new Exception('Could not authenticate the request');
        }
    }

    /**
     * Redirect the user to the login page with a return URL.
     *
     * Constructs the login URL based on configuration and the current request URI,
     * then performs an HTTP redirect.
     *
     * @param array  $auth       Authentication configuration array.
     * @param string $requestUri The original request URI to return to after login.
     */
    private function redirectToLogin(array $auth, string $requestUri): void
    {
        $scheme = (string)filter_input(INPUT_SERVER, 'REQUEST_SCHEME', FILTER_SANITIZE_URL);
        if (!empty($scheme)) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        }

        $httpHost = (string)filter_input(INPUT_SERVER, 'HTTP_HOST', FILTER_SANITIZE_URL);
        $returnUrl = $scheme . '://' . $httpHost . $requestUri;

        $domain = '';
        if (!empty($httpHost) && $httpHost !== 'localhost' && filter_var($httpHost, FILTER_VALIDATE_IP) === false) {
            $parts = explode('.', $httpHost);
            if (count($parts) >= 2) {
                $domain = '.' . implode('.', array_slice($parts, -2));
            }
        }

        $subDomain    = (string)($auth['subdomain'] ?? '');
        $path         = (string)($auth['login_path'] ?? '');
        $returnParam  = (string)($auth['return_param'] ?? 'return');

        $loginHost   = (!empty($domain)) ? ($subDomain . $domain) : $httpHost;
        $location    = $scheme . '://' . $loginHost . $path . '?' . rawurlencode($returnParam) . '=' . rawurlencode($returnUrl);

        redirect($location)->send();
        exit;
    }
}
