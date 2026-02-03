<?php
declare(strict_types=1);
namespace PhpMVC\Logging\Exception;

use RuntimeException;

/**
 * Class DriverException
 *
 * Exception thrown when a logging driver encounters an invalid configuration,
 * unsupported driver type, or an unrecoverable runtime error.
 *
 * This exception serves as a domain-specific wrapper around {@see RuntimeException}
 * to allow callers to distinguish logging-related failures from other runtime
 * exceptions within the framework.
 *
 * @package PhpMVC\Logging\Exception
 * @since   1.0
 */
class DriverException extends RuntimeException
{
}
