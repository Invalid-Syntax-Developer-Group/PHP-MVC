<?php
declare(strict_types=1);
namespace PhpMVC\Database\Connection;

use Pdo;
use InvalidArgumentException;
use PhpMVC\Database\Connection\Connection;
use PhpMVC\Database\QueryBuilder\MysqlQueryBuilder;

final class MysqlConnection extends Connection
{
    private Pdo $pdo;
    private string $database;

    public function __construct(array $config)
    {
        [
            'host' => $host,
            'port' => $port,
            'database' => $database,
            'username' => $username,
            'password' => $password,
        ] = $config;

        if (empty($host) || empty($database) || empty($username)) {
            throw new InvalidArgumentException('Connection incorrectly configured');
        }

        $this->database = $database;

        $this->pdo = new Pdo("mysql:host={$host};port={$port};dbname={$database}", $username, $password);
    }

    public function pdo(): Pdo
    {
        return $this->pdo;
    }
    
    public function query(): MysqlQueryBuilder
    {
        return new MysqlQueryBuilder($this);
    }

    public function getTables(): array
    {
        $statement = $this->pdo->prepare('SHOW TABLES');
        $statement->execute();

        $results = $statement->fetchAll(PDO::FETCH_NUM);
        $results = array_map(fn($result) => $result[0], $results);

        return $results;
    }

    public function hasTable(string $name): bool
    {
        $tables = $this->getTables();
        return in_array($name, $tables);
    }
}