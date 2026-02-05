<?php
declare(strict_types=1);
namespace PhpMVC\Provider;

use PhpMVC\Core\Application;
use PhpMVC\Http\Response;

/**
 * Class ResponseProvider
 *
 * Service provider responsible for registering the HTTP response
 * object in the application container.
 *
 * This provider binds a shared `response` service that returns
 * a fresh {@see Response} instance. The response object is used
 * throughout the framework to build and send HTTP responses,
 * including HTML output, JSON payloads, redirects, headers,
 * and status codes.
 *
 * @package PhpMVC\Provider
 * @since 1.0
 */
final class ResponseProvider
{
    /**
     * Bind the response service into the application container.
     *
     * Registers a `response` alias that resolves to a new
     * {@see Response} instance when requested.
     *
     * @param Application $app The application container instance.
     *
     * @return void
     */
    public function bind(Application $app): void
    {
        $app->bind('response', function($app) {
            return new Response();
        });
    }
}
