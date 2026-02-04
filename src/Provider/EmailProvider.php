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
     * @inheritDoc
     */
    protected function name(): string
    {
        return 'email';
    }

    /**
     * @inheritDoc
     */
    protected function factory(): DriverFactory
    {
        return new Factory();
    }

    /**
     * @inheritDoc
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
