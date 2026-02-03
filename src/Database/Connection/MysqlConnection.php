<?php
declare(strict_types=1);
namespace PhpMVC\Database\Connection;

use Pdo;
use InvalidArgumentException;
use PDOException;
use PhpMVC\Database\Connection\Connection;
use PhpMVC\Database\Exception\ConnectionException;
use PhpMVC\Database\QueryBuilder\MysqlQueryBuilder;

/**
 * Class MysqlConnection
 *
 * MySQL-specific database connection implementation.
 *
 * This class provides a concrete {@see Connection} backed by PDO using the
 * MySQL driver. It is responsible for:
 *  - Validating and consuming MySQL connection configuration
 *  - Initializing and exposing a PDO instance
 *  - Providing a MySQL-specific {@see MysqlQueryBuilder}
 *  - Exposing basic schema inspection utilities
 *
 * @package PhpMVC\Database\Connection
 */
final class MysqlConnection extends Connection
{
    /**
     * Active PDO connection instance.
     *
     * @var PDO
     */
    private Pdo $pdo;

    /**
     * Name of the connected database.
     *
     * @var string
     */
    private string $database;

    /**
     * MysqlConnection constructor.
     *
     * Expects a configuration array containing:
     *  - host (string)
     *  - port (int|string)
     *  - database (string)
     *  - username (string)
     *  - password (string)
     *
     * @param array $config Database connection configuration.
     *
     * @throws ConnectionException If required configuration values are missing.
     */
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
            throw new ConnectionException('Connection incorrectly configured');
        }

        $this->database = $database;

        try {
            $this->pdo = new Pdo("mysql:host={$host};port={$port};dbname={$database}", $username, $password);
        } catch (PDOException $e) {
            throw new ConnectionException('Failed to establish database connection', (int)$e->getCode(), $e);
        }
    }

    /**
     * Retrieve the underlying PDO connection.
     *
     * @return PDO Active PDO instance.
     */
    public function pdo(): Pdo
    {
        return $this->pdo;
    }
    
    /**
     * Create a new MySQL query builder for this connection.
     *
     * @return MysqlQueryBuilder Query builder bound to this connection.
     */
    public function query(): MysqlQueryBuilder
    {
        return new MysqlQueryBuilder($this);
    }

    /**
     * Retrieve a list of all tables in the current database.
     *
     * @return array<string> List of table names.
     */
    public function getTables(): array
    {
        $statement = $this->pdo->prepare('SHOW TABLES');
        $statement->execute();

        $results = $statement->fetchAll(PDO::FETCH_NUM);
        $results = array_map(fn($result) => $result[0], $results);

        return $results;
    }

    /**
     * Determine whether a given table exists in the database.
     *
     * @param string $name Table name to check.
     *
     * @return bool True if the table exists; otherwise false.
     */
    public function hasTable(string $name): bool
    {
        $tables = $this->getTables();
        return in_array($name, $tables);
    }
}
