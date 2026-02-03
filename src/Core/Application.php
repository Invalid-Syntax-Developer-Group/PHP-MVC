<?php
declare(strict_types=1);
namespace PhpMVC\Core;

use PhpMVC\Routing\Router;
use PhpMVC\Http\Response;

/**
 * Class Application
 *
 * Primary framework application container and runtime orchestrator.
 *
 * This class extends the base {@see Container} to provide:
 *  - A singleton application instance (service locator + DI container)
 *  - Environment configuration loading via dotenv
 *  - Provider binding (service registration) via a providers config file
 *  - Request dispatching through the {@see Router}
 *  - Normalization of dispatch results into a {@see Response}
 *
 * Lifecycle:
 *  1) {@see Application::getInstance()} obtains the singleton application instance
 *  2) {@see Application::prepare()} loads environment configuration and binds providers
 *  3) {@see Application::run()} dispatches the request and returns a {@see Response}
 *
 * Configuration conventions:
 *  - Base path is resolved from the container key `paths.base`
 *  - Providers are loaded from: <basePath>/config/providers.php
 *  - Routes are loaded from:     <basePath>/routes.php
 *
 * @package PhpMVC\Core
 * @since   1.0
 */
class Application extends Container
{
    /**
     * @var self|null Singleton application instance.
     */
    private static ?self $instance = null;

    /**
     * Application constructor.
     *
     * Private to enforce singleton usage via {@see Application::getInstance()}.
     */
    private function __construct() {}

    /**
     * Prevent cloning of the singleton instance.
     */
    private function __clone() {}
    
    /**
     * Retrieve the singleton application instance.
     *
     * Lazily creates the instance on first access.
     *
     * @return static The singleton Application instance.
     */
    public static function getInstance(): static
    {
        if (!static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Prepare the application for execution.
     *
     * Loads environment variables and binds configured providers.
     * Providers typically register container bindings for core services
     * such as the router, response, view manager, cache, etc.
     *
     * @return static Fluent return for chaining.
     */
    public function prepare(): static
    {
        $basePath = $this->resolve('paths.base');

        $this->configure($basePath);
        $this->bindProviders($basePath);

        return $this;
    }

    /**
     * Run the application and return the resulting HTTP response.
     *
     * @return Response The response produced by routing/dispatch.
     */
    public function run(): Response
    {
        return $this->dispatch($this->resolve('paths.base'));
    }

    /**
     * Load environment configuration using dotenv.
     *
     * @param string $basePath Application base path used to locate the .env file.
     *
     * @return void
     */
    private function configure(string $basePath): void
    {
        if (!class_exists('Dotenv\\Dotenv')) {
            return;
        }

        $dotenvClass = 'Dotenv\\Dotenv';
        $dotenv = $dotenvClass::createImmutable($basePath);
        $dotenv->load();
    }

    /**
     * Bind all configured providers.
     *
     * Providers are defined in `<basePath>/config/providers.php` and are expected to be
     * class names. If a provider instance implements a `bind()` method, it will be
     * invoked with the application container.
     *
     * @param string $basePath Application base path.
     *
     * @return void
     */
    private function bindProviders(string $basePath)
    {
        $providers = require "{$basePath}/src/config/providers.php";

        foreach ($providers as $provider) {
            $instance = new $provider;

            if (method_exists($instance, 'bind')) {
                $instance->bind($this);
            }
        }
    }

    /**
     * Dispatch the current request and normalize the result to a Response.
     *
     * Ensures a {@see Router} is bound in the container. If not, it is created,
     * configured using `<basePath>/routes.php`, and bound as a singleton.
     *
     * The router dispatch result is expected to be a {@see Response}; however, if
     * a non-Response value is returned (e.g., string/array), it is wrapped using
     * the container's 'response' binding and set as the response content.
     *
     * @param string $basePath Application base path.
     *
     * @return Response Response produced by the router or normalized wrapper.
     */
    private function dispatch(string $basePath): Response
    {
        if (!$this->has(Router::class)) {
            $router = new Router();

            $routes = require "{$basePath}/src/routes.php";
            $routes($router);

            $this->bind(Router::class, fn() => $router);
        }

        $response = $this->resolve(Router::class)->dispatch();

        if (!$response instanceof Response) {
            $response = $this->resolve('response')->content($response);
        }

        return $response;
    }
}
