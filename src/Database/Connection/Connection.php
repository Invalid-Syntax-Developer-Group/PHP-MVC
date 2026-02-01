<?php
declare(strict_types=1);
namespace PhpMVC\Database\Connection;

use Pdo;
use PhpMVC\Database\QueryBuilder\QueryBuilder;

abstract class Connection
{
    /**
     * Get the underlying Pdo instance for this connection
     */
    abstract public function pdo(): Pdo;

    /**
     * Start a new query on this connection
     */
    abstract public function query(): QueryBuilder;

    /**
     * Return a  list of table names on this connection
     */
    abstract public function getTables(): array;

    /**
     * Find out if a table exists on this connection
     */
    abstract public function hasTable(string $name): bool;
}