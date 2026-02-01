<?php
declare(strict_types=1);
namespace PhpMVC\Framework\Database\QueryBuilder;

use PhpMVC\Framework\Database\Connection\Connection;
use PhpMVC\Framework\Database\Connection\MysqlConnection;

final class MysqlQueryBuilder extends QueryBuilder
{
    protected Connection $connection;

    public function __construct(MysqlConnection $connection)
    {
        $this->connection = $connection;
    }
}