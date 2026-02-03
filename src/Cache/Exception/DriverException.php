<?php
declare(strict_types=1);
namespace PhpMVC\Cache\Exception;

use RuntimeException;

/**
 * Class DriverException
 *
 * Domain-specific exception used to signal cache driver–related errors.
 *
 * This exception is thrown when:
 *  - A cache driver type is missing from configuration
 *  - An unrecognised or unsupported cache driver is requested
 *  - A cache driver cannot be instantiated due to invalid configuration
 *
 * It extends {@see RuntimeException} to indicate errors that occur during
 * application execution rather than programmer mistakes.
 *
 * Intended usage:
 *  - Thrown by cache factories and driver resolution logic
 *  - Caught by higher-level application or bootstrap code to handle
 *    cache configuration failures gracefully
 *
 * @package PhpMVC\Cache\Exception
 * @since   1.0
 */
final class DriverException extends RuntimeException
{
}
