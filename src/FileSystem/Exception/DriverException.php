<?php
declare(strict_types=1);
namespace PhpMVC\FileSystem\Exception;

use RuntimeException;

/**
 * Class DriverException
 *
 * Thrown when a filesystem driver encounters an unrecoverable error,
 * such as invalid configuration, unsupported driver types, or failures
 * during initialization or execution.
 *
 * This exception acts as a domain-specific wrapper around {@see RuntimeException}
 * to allow filesystem-related errors to be caught and handled separately
 * from other runtime failures.
 *
 * @package PhpMVC\FileSystem\Exception
 * @since   1.0
 */
final class DriverException extends RuntimeException
{
}
