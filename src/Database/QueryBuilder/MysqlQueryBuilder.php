<?php
declare(strict_types=1);
namespace PhpMVC\Database\QueryBuilder;

use PhpMVC\Database\Connection\Connection;
use PhpMVC\Database\Connection\MysqlConnection;

final class MysqlQueryBuilder extends QueryBuilder
{
    protected Connection $connection;

    public function __construct(MysqlConnection $connection)
    {
        $this->connection = $connection;
    }
}