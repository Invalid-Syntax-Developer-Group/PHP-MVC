<?php
declare(strict_types=1);
namespace PhpMVC\Database\QueryBuilder;

use Pdo;
use PdoStatement;
use PhpMVC\Database\Connection\Connection;
use PhpMVC\Database\Exception\QueryException;

/**
 * Abstract Class QueryBuilder
 *
 * Base query builder implementation used to compose and execute parameterized
 * SQL statements against a {@see Connection}.
 *
 * This builder provides a fluent interface for common CRUD operations:
 *  - SELECT with optional WHERE clauses and LIMIT/OFFSET
 *  - INSERT with named placeholders
 *  - UPDATE with named placeholders and WHERE clauses
 *  - DELETE with WHERE clauses
 *
 * Execution model:
 *  - Builder methods configure internal state (type, table, columns, wheres, etc.)
 *  - {@see QueryBuilder::prepare()} compiles SQL based on the current query type
 *  - Methods like
 *      - {@see QueryBuilder::all()},
 *      - {@see QueryBuilder::first()},
 *      - {@see QueryBuilder::insert()},
 *      - {@see QueryBuilder::update()},
 *      - {@see QueryBuilder::delete()}
 * 
 *    prepare and execute statements with named parameters
 *
 * Parameter binding:
 *  - WHERE clauses are compiled into named placeholders using the column name (e.g. `:id`)
 *  - Where values are built via {@see QueryBuilder::getWhereValues()} and passed into execute()
 *  - Boolean where values are coerced to integers (0/1)
 *
 * Notes:
 *  - Concrete builders (e.g. {@see MysqlQueryBuilder}) may extend this class
 *    to add dialect-specific behavior or additional clauses.
 *  - The caller must set the table via {@see QueryBuilder::from()} for non-dialect defaults.
 *
 * @package PhpMVC\Database\QueryBuilder
 * @since   1.0
 */
abstract class QueryBuilder
{
    /**
     * Connection used for preparing and executing queries.
     */
    protected Connection $connection;

    /**
     * Current query type: select|insert|update|delete.
     */
    protected string $type;

    /**
     * Selected/target columns for the query.
     *
     * @var array<int,string>
     */
    protected array $columns;

    /**
     * Target table for the query.
     */
    protected string $table;

    /**
     * Limit for select queries.
     */
    protected int $limit;

    /**
     * Offset for select queries.
     */
    protected int $offset;

    /**
     * Values used by insert/update queries.
     *
     * @var array<string,mixed>
     */
    protected array $values;

    /**
     * Accumulated where clauses.
     *
     * Each where is a tuple: [column, comparator, value]
     *
     * @var array<int,array{0:string,1:mixed,2:mixed}>
     */
    protected array $wheres = [];

    /**
     * QueryBuilder constructor.
     *
     * @param Connection $connection Database connection instance.
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Execute the query and return all matching rows.
     *
     * If no query type has been set, defaults to a SELECT query.
     *
     * @return array<int,array<string,mixed>> Result rows as associative arrays.
     */
    public function all(): array
    {
        if (!isset($this->type)) {
            $this->select();
        }

        $statement = $this->prepare();
        $statement->execute($this->getWhereValues());

        return $statement->fetchAll(Pdo::FETCH_ASSOC);
    }

    /**
     * Compile and prepare the current query into a PDOStatement.
     *
     * @return PDOStatement Prepared statement ready for execution.
     *
     * @throws QueryException If the query type is not recognized or no SQL is produced.
     */
    public function prepare(): PdoStatement
    {
        $query = '';

        if ($this->type === 'select') {
            $query = $this->compileSelect($query);
            $query = $this->compileWheres($query);
            $query = $this->compileLimit($query);
        }

        if ($this->type === 'insert') {
            $query = $this->compileInsert($query);
        }

        if ($this->type === 'update') {
            $query = $this->compileUpdate($query);
            $query = $this->compileWheres($query);
        }

        if ($this->type === 'delete') {
            $query = $this->compileDelete($query);
            $query = $this->compileWheres($query);
        }

        if (empty($query)) {
            throw new QueryException('Unrecognised query type');
        }

        return $this->connection->pdo()->prepare($query);
    }

    /**
     * Execute the query and return the first row or null.
     *
     * If no query type has been set, defaults to a SELECT query.
     *
     * @return array<string,mixed>|null First matching row as an associative array, or null.
     */
    public function first(): ?array
    {
        if (!isset($this->type)) {
            $this->select();
        }

        $statement = $this->take(1)->prepare();
        $statement->execute($this->getWhereValues());

        $result = $statement->fetchAll(Pdo::FETCH_ASSOC);

        if (count($result) === 1) {
            return $result[0];
        }

        return null;
    }

    /**
     * Set LIMIT/OFFSET for select queries.
     *
     * @param int $limit  Maximum number of rows to return.
     * @param int $offset Number of rows to skip (default: 0).
     *
     * @return static Fluent return for chaining.
     */
    public function take(int $limit, int $offset = 0): static
    {
        $this->limit = $limit;
        $this->offset = $offset;

        return $this;
    }

    /**
     * Set the target table for the query.
     *
     * @param string $table Table name.
     *
     * @return static Fluent return for chaining.
     */
    public function from(string $table): static
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Begin a SELECT query.
     *
     * @param mixed $columns Column list or '*' (default).
     *
     * @return static Fluent return for chaining.
     */
    public function select(mixed $columns = '*'): static
    {
        if (is_string($columns)) {
            $columns = [$columns];
        }

        $this->type = 'select';
        $this->columns = $columns;

        return $this;
    }

    /**
     * Execute an INSERT query.
     *
     * @param array<int,string>     $columns Columns to insert.
     * @param array<string,mixed>   $values  Values keyed by column name.
     *
     * @return bool True on successful execution; otherwise false.
     */
    public function insert(array $columns, array $values): bool
    {
        $this->type = 'insert';
        $this->columns = $columns;
        $this->values = $values;

        $statement = $this->prepare();

        return $statement->execute($values);
    }

    /**
     * Add a WHERE clause to the query.
     *
     * Supports two call styles:
     *  - where('id', 5)              => column '=' comparatorValue
     *  - where('id', '>=', 5)        => column comparator value
     *
     * @param string $column      Column name.
     * @param mixed  $comparator  Comparator or value when using 2-arg form.
     * @param mixed  $value       Value when using 3-arg form (default: null).
     *
     * @return static Fluent return for chaining.
     */
    public function where(string $column, mixed $comparator, mixed $value = null): static
    {
        if (is_null($value) && !is_null($comparator)) {
            array_push($this->wheres, [$column, '=', $comparator]);
        } else {
            array_push($this->wheres, [$column, $comparator, $value]);
        }

        return $this;
    }

    /**
     * Execute an UPDATE query.
     *
     * @param array<int,string>     $columns Columns to update.
     * @param array<string,mixed>   $values  Values keyed by column name.
     *
     * @return bool True on successful execution; otherwise false.
     */
    public function update(array $columns, array $values): bool
    {
        $this->type = 'update';
        $this->columns = $columns;
        $this->values = $values;

        $statement = $this->prepare();

        return $statement->execute($this->getWhereValues() + $values);
    }

    /**
     * Get the last insert id from the underlying PDO connection.
     *
     * @return string Last insert id.
     */
    public function getLastInsertId(): string
    {
        return $this->connection->pdo()->lastInsertId();
    }

    /**
     * Execute a DELETE query.
     *
     * @return bool True on successful execution; otherwise false.
     */
    public function delete(): bool
    {
        $this->type = 'delete';

        $statement = $this->prepare();

        return $statement->execute($this->getWhereValues());
    }

    /**
     * Build an array of values used by WHERE parameter bindings.
     *
     * Where parameter names are derived from the column names. Boolean values are
     * coerced to integers (0/1) to improve compatibility with PDO drivers.
     *
     * @return array<string,mixed> Named parameter values keyed by column.
     */
    protected function getWhereValues(): array
    {
        $values = [];

        if (count($this->wheres) === 0) {
            return $values;
        }

        foreach ($this->wheres as $where) {
            if (is_bool($where[2])) {
                $values[$where[0]] = (int) $where[2];
                continue;
            }

            $values[$where[0]] = $where[2];
        }

        return $values;
    }

    /**
     * Compile a SELECT clause.
     *
     * @param string $query Current query string.
     *
     * @return string Updated query string.
     */
    protected function compileSelect(string $query): string
    {
        $joinedColumns = join(', ', $this->columns);

        $query .= " SELECT {$joinedColumns} FROM {$this->table}";

        return $query;
    }

    /**
     * Compile LIMIT/OFFSET clauses for select queries.
     *
     * @param string $query Current query string.
     *
     * @return string Updated query string.
     */
    protected function compileLimit(string $query): string
    {
        if (isset($this->limit)) {
            $query .= " LIMIT {$this->limit}";
        }

        if (isset($this->offset)) {
            $query .= " OFFSET {$this->offset}";
        }

        return $query;
    }

    /**
     * Compile WHERE clauses for the query.
     *
     * Multiple where clauses are joined using `AND`.
     * Each where is compiled into a named placeholder based on the column name.
     *
     * @param string $query Current query string.
     *
     * @return string Updated query string.
     */
    protected function compileWheres(string $query): string
    {
        if (count($this->wheres) === 0) {
            return $query;
        }

        $query .= ' WHERE';

        foreach ($this->wheres as $i => $where) {
            if ($i > 0) {
                $query .= ' AND ';
            }

            [$column, $comparator, $value] = $where;

            $query .= " {$column} {$comparator} :{$column}";
        }

        return $query;
    }

    /**
     * Compile an INSERT statement.
     *
     * @param string $query Current query string.
     *
     * @return string Updated query string.
     */
    protected function compileInsert(string $query): string
    {
        $joinedColumns = join(', ', $this->columns);
        $joinedPlaceholders = join(', ', array_map(fn($column) => ":{$column}", $this->columns));

        $query .= " INSERT INTO {$this->table} ({$joinedColumns}) VALUES ({$joinedPlaceholders})";

        return $query;
    }

    /**
     * Compile an UPDATE statement.
     *
     * @param string $query Current query string.
     *
     * @return string Updated query string.
     */
    protected function compileUpdate(string $query): string
    {
        $joinedColumns = '';

        foreach ($this->columns as $i => $column) {
            if ($i > 0) {
                $joinedColumns .= ', ';
            }

            $joinedColumns = " {$column} = :{$column}";
        }

        $query .= " UPDATE {$this->table} SET {$joinedColumns}";

        return $query;
    }

    /**
     * Compile a DELETE statement.
     *
     * @param string $query Current query string.
     *
     * @return string Updated query string.
     */
    protected function compileDelete(string $query): string
    {
        $query .= " DELETE FROM {$this->table}";
        return $query;
    }
}
