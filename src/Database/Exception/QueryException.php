<?php
declare(strict_types=1);
namespace PhpMVC\Database\Exception;

use PDOException;

/**
 * Class QueryException
 *
 * Specialized exception type for database query–related errors.
 *
 * This exception extends {@see PDOException} and represents failures that
 * occur while preparing, executing, or fetching results from database
 * queries (e.g. syntax errors, missing tables/columns, constraint violations).
 *
 * @package PhpMVC\Database\Exception
 */
final class QueryException extends PDOException
{
}
