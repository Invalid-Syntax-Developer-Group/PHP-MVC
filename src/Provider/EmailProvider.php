<?php
declare(strict_types=1);
namespace PhpMVC\Provider;

use PhpMVC\Email\Factory;
use PhpMVC\Email\Driver\PhpMailerDriver;
use PhpMVC\Email\Driver\PostmarkDriver;
use PhpMVC\Support\DriverFactory;
use PhpMVC\Support\DriverProvider;

/**
 * Class EmailProvider
 *
 * Service provider responsible for registering email drivers
 * with the application container.
 *
 * Supported drivers:
 *  - `phpmailer` : {@see PhpMailerDriver}
 *  - `postmark`  : {@see PostmarkDriver}
 *
 * @package PhpMVC\Provider
 */
final class EmailProvider extends DriverProvider
{
    /**
     * Get the container binding name for the email service.
     *
     * This value is used as the key when resolving the service
     * from the application container (e.g. `app('email')`).
     *
     * @return string The service name.
     */
    protected function name(): string
    {
        return 'email';
    }

    /**
     * Create the driver factory instance.
     *
     * The factory is responsible for registering available drivers
     * and returning the appropriate driver based on configuration.
     *
     * @return DriverFactory The email driver factory.
     */
    protected function factory(): DriverFactory
    {
        return new Factory();
    }

    /**
     * Define the available email drivers.
     *
     * Each driver is registered under a string alias and resolved
     * lazily via a closure that receives the driver configuration.
     *
     * @return array<string, callable> Map of driver aliases to factory closures.
     */
    protected function drivers(): array
    {
        return [
            'phpmailer' => function($config) {
                return new PhpMailerDriver($config);
            },
            'postmark' => function($config) {
                return new PostmarkDriver($config);
            },
        ];
    }
}
