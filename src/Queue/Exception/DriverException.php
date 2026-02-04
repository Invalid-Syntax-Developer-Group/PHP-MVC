<?php
declare(strict_types=1);
namespace PhpMVC\Queue\Exception;

use RuntimeException;

/**
 * Class DriverException
 *
 * Exception thrown when a queue driver cannot be resolved,
 * configured, or instantiated correctly.
 *
 * This exception is typically raised by the queue {@see \PhpMVC\Queue\Factory}
 * when:
 *  - A required driver configuration key (such as `type`) is missing
 *  - An unrecognised or unsupported driver type is requested
 *
 * @package PhpMVC\Queue\Exception
 */
final class DriverException extends RuntimeException
{
}
