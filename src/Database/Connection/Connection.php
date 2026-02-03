<?php
declare(strict_types=1);
namespace PhpMVC\Database\Connection;

use Pdo;
use PhpMVC\Database\QueryBuilder\QueryBuilder;

/**
 * Abstract Class Connection
 *
 * Defines the base contract for all database connection implementations
 * within the PhpMVC framework.
 *
 * Concrete connection classes (e.g. MySQL, SQLite, PostgreSQL) must extend
 * this class and provide:
 *  - A configured {@see PDO} instance
 *  - A {@see QueryBuilder} bound to the connection
 *  - Schema inspection capabilities (table listing and existence checks)
 *
 * This abstraction allows higher-level components (models, repositories,
 * migrations) to interact with the database without being tightly coupled
 * to a specific driver or engine.
 *
 * @package PhpMVC\Database\Connection
 */
abstract class Connection
{
    /**
     * Retrieve the underlying PDO connection.
     *
     * Implementations must return a fully initialized and configured
     * {@see PDO} instance ready for querying.
     *
     * @return PDO Active PDO connection instance.
     */
    abstract public function pdo(): Pdo;

    /**
     * Create a new query builder instance for this connection.
     *
     * The returned {@see QueryBuilder} should be preconfigured to use
     * this connection's PDO instance.
     *
     * @return QueryBuilder Query builder bound to this connection.
     */
    abstract public function query(): QueryBuilder;

    /**
     * Retrieve a list of tables available in the current database/schema.
     *
     * Implementations may return table names as strings or structured
     * metadata depending on the underlying driver.
     *
     * @return array List of database table names.
     */
    abstract public function getTables(): array;

    /**
     * Determine whether a given table exists in the database.
     *
     * @param string $name Table name to check.
     *
     * @return bool True if the table exists; otherwise false.
     */
    abstract public function hasTable(string $name): bool;
}
