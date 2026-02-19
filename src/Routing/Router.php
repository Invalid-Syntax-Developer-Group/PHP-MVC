<?php
declare(strict_types=1);
namespace PhpMVC\Routing;

use Exception;
use Throwable;
use PhpMVC\Routing\Route;
use PhpMVC\Routing\Exception\RouteException;

/**
 * Class Router
 *
 * Central HTTP router responsible for:
 *  - registering routes (method + path + handler),
 *  - dispatching the current request to a matched {@see Route},
 *  - handling common error cases (404/400/500),
 *  - generating URLs for named routes with parameter substitution.
 *
 * Routing model:
 *  - Routes are stored as {@see Route} instances created via {@see add()}.
 *  - Incoming requests are matched by delegating to {@see Route::matches()}.
 *  - When a route matches, {@see Route::dispatch()} is executed and its return
 *    value is returned from {@see Router::dispatch()}.
 *
 * Error handling model:
 *  - Custom error handlers may be registered per status code via {@see Router::errorHandler()}.
 *  - Defaults are lazily defined for 400 (not allowed), 404 (not found), and 500 (server error).
 *  - Exceptions thrown during route dispatch are optionally forwarded to a configured
 *    exception handler (`config('handlers.exceptions')`) that can render/handle throwables.
 *    If it cannot handle the throwable, a generic 500 error handler is dispatched.
 *
 * Named route generation:
 *  - {@see Router::route()} looks up a {@see Route} by name and performs placeholder substitution:
 *      - Required placeholders: `{id}`
 *      - Optional placeholders: `{id?}`
 *    Any unreplaced placeholders are stripped from the final path.
 *
 * @package PhpMVC\Routing
 * @since 1.2
 */
class Router
{
    /**
     * Registered routes.
     *
     * @var array<int, Route>
     */
    protected array $routes = [];

    /**
     * Error handlers keyed by HTTP status code.
     *
     * @var array<int, callable>
     */
    protected array $errorHandler = [];

    /**
     * The current matched route (if any).
     *
     * @var Route|null
     */
    protected ?Route $current = null;

    /**
     * Register a new route.
     *
     * Creates a {@see Route} instance and appends it to the internal route list.
     * The returned {@see Route} can typically be further configured (e.g., named,
     * middleware, constraints), depending on the implementation of {@see Route}.
     *
     * @param string $method  HTTP method (e.g. GET, POST, PUT, DELETE).
     * @param string $path    Route path pattern as understood by {@see Route::matches()}.
     * @param mixed  $handler Route handler (callable/array/controller-string/etc.),
     *                        interpreted by {@see Route::dispatch()}.
     *
     * @return Route The newly created route instance.
     */
    public function add(string $method, string $path, $handler): Route
    {
        $route = $this->routes[] = new Route($method, $path, $handler);
        return $route;
    }

    /**
     * Register an error handler callback for a given HTTP status code.
     *
     * The handler will be invoked by {@see dispatchNotAllowed()}, {@see dispatchNotFound()},
     * or {@see dispatchError()} depending on the case, if configured.
     *
     * @param int      $code    HTTP status code (e.g. 404, 500).
     * @param callable $handler Callable that returns a response payload/string.
     *
     * @return void
     */
    public function errorHandler(int $code, callable $handler): void
    {
        $this->errorHandler[$code] = $handler;
    }

    /**
     * Dispatch the current HTTP request.
     *
     * Flow:
     *  1) Determine request method/path from `$_SERVER`.
     *  2) Attempt to find a matching route.
     *  3) If matched, dispatch the route and return its response.
     *     - If route dispatch throws, attempt to delegate to a configured exception
     *       handler (`config('handlers.exceptions')`), else dispatch 500.
     *  4) If no route matched but the path exists in the registered route paths,
     *     dispatch "not allowed" (400).
     *  5) Otherwise dispatch "not found" (404).
     *
     * @return mixed The result of the route handler or error handler.
     */
    public function dispatch(): mixed
    {
        $paths = $this->paths();

        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $requestPath = $this->normalize((string)($_SERVER['REQUEST_URI'] ?? '/'));

        $matching = $this->match($requestMethod, $requestPath);

        if ($matching) {
            $this->current = $matching;

            if (property_exists($this->current, 'requiresAuth') && $this->current->requiresAuth) {
                $this->authenticate($requestMethod, $requestPath);
            }

            try { return $matching->dispatch(); }
            catch (Throwable $e) {
                $result = null;

                if ($handler = config('handlers.exceptions')) {
                    $instance = new $handler();

                    if ($result = $instance->showThrowable($e)) {
                        return $result;
                    }
                }

                return $this->dispatchError();
            }
        }
        
        if (in_array($requestPath, $paths)) {
            return $this->dispatchNotAllowed();
        }
        
        return $this->dispatchNotFound();
    }

    /**
     * Get the currently matched route, if dispatch has matched one.
     *
     * @return Route|null The current route or null if nothing matched yet.
     */
    public function current(): ?Route
    {
        return $this->current;
    }

    /**
     * Dispatch a "not allowed" response (default HTTP 400).
     *
     * If no handler is registered for status code 400, a default handler returning
     * the string "not allowed" is lazily set and invoked.
     *
     * @return mixed Result of the 400 error handler.
     */
    public function dispatchNotAllowed(): mixed
    {
        $this->errorHandler[400] ??= fn() => 'not allowed';
        return $this->errorHandler[400]();
    }

    /**
     * Dispatch a "not found" response (HTTP 404).
     *
     * If no handler is registered for status code 404, a default handler returning
     * the string "not found" is lazily set and invoked.
     *
     * @return mixed Result of the 404 error handler.
     */
    public function dispatchNotFound(): mixed
    {
        $this->errorHandler[404] ??= fn() => 'not found';
        return $this->errorHandler[404]();
    }

    /**
     * Dispatch a generic server error response (HTTP 500).
     *
     * If no handler is registered for status code 500, a default handler returning
     * the string "server error" is lazily set and invoked.
     *
     * @return mixed Result of the 500 error handler.
     */
    public function dispatchError(): mixed
    {
        $this->errorHandler[500] ??= fn() => 'server error';
        return $this->errorHandler[500]();
    }

    /**
     * Generate a URL path for a named route.
     *
     * Performs placeholder replacement using `$parameters`:
     *  - For each key `k`:
     *      - `{k}`  is replaced with the provided value
     *      - `{k?}` is replaced with the provided value
     *  - Any remaining placeholders `{...}` are removed.
     *
     * @param string               $name       The route name ({@see Route::name()}).
     * @param array<string, mixed> $parameters Placeholder values keyed by placeholder name.
     *
     * @return string The resolved path.
     *
     * @throws Exception If no route exists with the provided name.
     */
    public function route(string $name, array $parameters = []): string
    {
        foreach ($this->routes as $route) {
            if ($route->name() === $name) {
                $finds = [];
                $replaces = [];

                foreach ($parameters as $key => $value) {
                    $finds[] = "{{$key}}";
                    $replaces[] = $value;
                    $finds[] = "{{$key}?}";
                    $replaces[] = $value;
                }

                $path = $route->path();
                $path = str_replace($finds, $replaces, $path);
                $path = preg_replace('#{[^}]+}#', '', $path);

                return $path;
            }
        }

        throw new RouteException('No route with that name');
    }

    /**
     * Collect all registered route paths.
     *
     * @return array<int, string> List of route path patterns.
     */
    private function paths(): array
    {
        $paths = [];
        foreach ($this->routes as $route) {
            $paths[] = $route->path();
        }

        return $paths;
    }

    /**
     * Find the first route that matches the given method and path.
     *
     * Matching is delegated to {@see Route::matches()}.
     *
     * @param string $method HTTP request method.
     * @param string $path   HTTP request URI/path.
     *
     * @return Route|null The matching route or null if no match.
     */
    private function match(string $method, string $path): ?Route
    {
        foreach ($this->routes as $route) {
            if ($route->matches($method, $path)) {
                return $route;
            }
        }

        return null;
    }

    /**
     * Normalize an incoming request URI to a routable path.
     *
     * - Removes query strings/fragments.
     * - Collapses duplicate slashes.
     * - Strips deployment base path derived from SCRIPT_NAME
     *   (e.g. "/cs-data-issues") so routes can remain defined from root.
     *
     * @param string $requestUri Raw REQUEST_URI value.
     *
     * @return string Normalized path beginning with '/'.
     */
    private function normalize(string $requestUri): string
    {
        $path = parse_url($requestUri, PHP_URL_PATH);
        $path = is_string($path) && !empty($path) ? $path : '/';

        $path = '/' . ltrim($path, '/');
        $path = preg_replace('#/+#', '/', $path) ?: '/';

        $scriptName = (string)($_SERVER['SCRIPT_NAME'] ?? '');
        $basePath = str_replace('\\', '/', dirname($scriptName));

        if ($basePath === '.' || $basePath === '/' || $basePath === '\\') {
            $basePath = '';
        } else {
            $basePath = '/' . trim($basePath, '/');
        }

        if (!empty($basePath)) {
            if ($path === $basePath) return '/';

            if (str_starts_with($path, $basePath . '/')) {
                $path = substr($path, strlen($basePath));
                $path = empty($path) ? '/' : $path;
            }
        }

        return $path;
    }

    /**
     * Authenticate the request if the current route requires authentication.
     *
     * For non-GET/HEAD/OPTIONS requests, checks for a CSRF token in the POST data
     * and validates it against the session. If validation fails, attempts to redirect
     * to a login page defined by the route or configuration, or throws an exception
     * if redirection is not supported.
     *
     * @param string $requestMethod HTTP method of the incoming request.
     * @param string $requestPath   Path of the incoming request.
     *
     * @return void
     *
     * @throws RouteException If authentication fails and redirection is not supported.
     */
    private function authenticate(string $requestMethod, string $requestPath)
    {
        $requestMethodUpper = strtoupper($requestMethod);
        $requiresCsrf = !in_array($requestMethodUpper, ['GET', 'HEAD', 'OPTIONS'], true);

        if ($requiresCsrf) {
            $session = session();
            $default = (string)config('session.default', 'native');
            $config  = (array)config("session.{$default}", []);
            $token   = (string)($config['authentication']['csrf'] ?? 'token');

            if (!isset($_POST[$token])
            || !$session->has($token)
            || !hash_equals((string)$session->get($token), (string)($_POST[$token] ?? ''))) {
                if (method_exists($this->current, 'redirectToLogin')) {
                    return $this->current->redirectToLogin($requestPath);
                } else {
                    throw new RouteException(
                        sprintf('Current route of class %s does not support redirectToLogin()',
                        is_object($this->current) ? get_class($this->current) : gettype($this->current)
                    ));
                }
            }
        }
    }
}
