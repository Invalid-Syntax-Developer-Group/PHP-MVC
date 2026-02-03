<?php
declare(strict_types=1);
namespace PhpMVC\Session\Exception;

use RuntimeException;

/**
 * Class DriverException
 *
 * Exception thrown by the session subsystem when a session driver
 * encounters an invalid configuration, unsupported driver type,
 * or a runtime failure while interacting with the session storage.
 *
 * This exception provides a domain-specific error type that allows
 * session-related failures to be caught and handled separately from
 * generic runtime exceptions.
 *
 * @package PhpMVC\Session\Exception
 */
class DriverException extends RuntimeException
{
}
