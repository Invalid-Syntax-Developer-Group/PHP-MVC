<?php
declare(strict_types=1);
namespace PhpMVC\Database\Exception;

use PDOException;

/**
 * Class ConnectionException
 *
 * Specialized exception type for database connection–related errors.
 *
 * This exception extends {@see PDOException} and is intended to represent
 * failures occurring during database connection initialization or low-level
 * PDO connection handling (e.g. invalid credentials, unreachable host,
 * misconfigured DSN).
 *
 * @package PhpMVC\Database\Exception
 */
final class ConnectionException extends PDOException
{
}
