<?php
declare(strict_types=1);
namespace PhpMVC\Database\QueryBuilder;

use PhpMVC\Database\Connection\Connection;
use PhpMVC\Database\Connection\MysqlConnection;

/**
 * Class MysqlQueryBuilder
 *
 * MySQL-specific query builder implementation.
 *
 * This class currently acts as a thin, type-safe specialization of the base
 * {@see QueryBuilder}, ensuring the builder is constructed with a
 * {@see MysqlConnection}. This allows MySQL dialect enhancements to be added
 * later (e.g., `ON DUPLICATE KEY UPDATE`, `INSERT IGNORE`, JSON operators,
 * full-text search helpers, locking reads, etc.) without changing the public
 * query builder contract.
 *
 * @package PhpMVC\Database\QueryBuilder
 * @since   1.0
 */
final class MysqlQueryBuilder extends QueryBuilder
{
    /**
     * Connection bound to this query builder.
     *
     * @var Connection
     */
    protected Connection $connection;

    /**
     * MysqlQueryBuilder constructor.
     *
     * @param MysqlConnection $connection Active MySQL connection instance.
     */
    public function __construct(MysqlConnection $connection)
    {
        $this->connection = $connection;
    }
}
