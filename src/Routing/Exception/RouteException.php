<?php
declare(strict_types=1);
namespace PhpMVC\Routing\Exception;

use RuntimeException;

/**
 * Class RouteException
 *
 * Domain-specific exception used by the routing subsystem to signal
 * routing-related errors that are not tied to lower-level PHP/runtime
 * failures.
 *
 * Typical use cases include:
 *  - Invalid or malformed route definitions
 *  - Errors during route resolution or dispatch
 *  - Unsupported routing configurations
 *
 * This exception intentionally extends {@see RuntimeException} so it can be
 * thrown at runtime when routing logic encounters an unrecoverable condition,
 * while still allowing consumers to catch routing errors separately from
 * generic runtime failures.
 *
 * @package PhpMVC\Routing\Exception
 */
final class RouteException extends RuntimeException
{
}
