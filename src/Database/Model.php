<?php
declare(strict_types=1);
namespace PhpMVC\Database;

use Exception;
use ReflectionClass;
use PhpMVC\Database\Connection\Connection;
use PhpMVC\Database\Connection\MysqlConnection;
use PhpMVC\Database\Exception\ConnectionException;
use PhpMVC\Database\Attributes\TableName;
use PhpMVC\Database\Relationship;

/**
 * Abstract Class Model
 *
 * Base Active Record–style model for the PhpMVC database layer.
 *
 * This class provides:
 *  - Attribute storage with "dirty" tracking for persistence
 *  - Magic accessors/mutators via `get{Attr}Attribute()` / `set{Attr}Attribute()`
 *  - Optional runtime value casting via `$casts`
 *  - Relationship helpers (hasOne/hasMany/belongsTo) with lazy resolution
 *  - A fluent query entry point via {@see Model::query()} and static proxying through {@see Model::__callStatic()}
 *
 * Table resolution:
 *  - If `$table` is explicitly set, it will be used.
 *  - Otherwise, the model class may define a {@see TableName} attribute:
 *      ```php
 *      #[TableName('users')]
 *      final class User extends Model {}
 *      ```
 *  - If neither is present, {@see Model::getTable()} throws.
 *
 * Connection resolution:
 *  - A connection can be injected via {@see Model::setConnection()}.
 *  - If not set, {@see Model::getConnection()} falls back to `app('database')`.
 *
 * Attribute access behavior ({@see Model::__get()} order of precedence):
 *  1) If a method exists matching the property name, it is treated as a relationship
 *     definition returning a {@see Relationship}; the relationship is executed and used
 *     as the value (e.g. `$user->posts` calls `$user->posts()` and returns results).
 *  2) If a getter exists (`get{Property}Attribute`), it is invoked with the raw attribute value.
 *  3) If the raw attribute exists in `$attributes`, it is used.
 *  4) If `$casts[$property]` is a callable, it is applied to the resolved value.
 *
 * Attribute set behavior ({@see Model::__set()}):
 *  - Marks the property as dirty.
 *  - If a setter exists (`set{Property}Attribute`), it is invoked and stored.
 *  - Otherwise the value is stored directly.
 *
 * Persistence behavior:
 *  - {@see Model::save()} performs an UPDATE when `id` exists, otherwise INSERT.
 *  - INSERT populates `id` using the query builder last insert id.
 *  - Only dirty attributes are written.
 *
 * @package PhpMVC\Database
 * @since   1.0
 */
abstract class Model
{
    /**
     * Database connection used by this model instance.
     */
    protected Connection $connection;

    /**
     * Explicit table name override.
     */
    protected string $table;

    /**
     * Model attribute store.
     *
     * @var array<string,mixed>
     */
    protected array $attributes = [];

    /**
     * List of attributes modified since hydration or last save.
     *
     * @var array<int,string>
     */
    protected array $dirty = [];

    /**
     * Value casting callbacks by attribute name.
     *
     * Example:
     *  ```php
     * protected array $casts = [
     *      'is_active' => fn ($v) => (bool) $v,
     *      'created_at' => fn ($v) => new DateTimeImmutable($v),
     *  ];
     * ```
     *
     * @var array<string,callable>
     */
    protected array $casts = [];

    /**
     * Proxy static method calls to the query builder / collector.
     *
     * Example:
     *  User::where('email', 'a@b.com')->first();
     *
     * @param string $method     Method name being called.
     * @param array  $parameters Method parameters.
     *
     * @return mixed Result of the proxied call.
     */
    public static function __callStatic(string $method, array $parameters = []): mixed
    {
        return static::query()->$method(...$parameters);
    }

    /**
     * Magic getter for attributes, relationships, and accessors.
     *
     * @param string $property Attribute/relationship name.
     *
     * @return mixed Resolved value.
     */
    public function __get(string $property): mixed
    {
        $getter = 'get' . ucfirst($property) . 'Attribute';

        $value = null;

        if (method_exists($this, $property)) {
            $relationship = $this->$property();
            $method = $relationship->method;

            $value = $relationship->$method();
        }

        if (method_exists($this, $getter)) {
            $value = $this->$getter($this->attributes[$property] ?? null);
        }

        if (isset($this->attributes[$property])) {
            $value = $this->attributes[$property];
        }

        if (isset($this->casts[$property]) && is_callable($this->casts[$property])) {
            $value = $this->casts[$property]($value);
        }

        return $value;
    }

    /**
     * Magic setter for attributes and mutators.
     *
     * @param string $property Attribute name.
     * @param mixed  $value    Value to assign.
     *
     * @return void
     */
    public function __set(string $property, $value)
    {
        $setter = 'set' . ucfirst($property) . 'Attribute';

        array_push($this->dirty, $property);

        if (method_exists($this, $setter)) {
            $this->attributes[$property] = $this->$setter($value);
            return;
        }

        $this->attributes[$property] = $value;
    }

    /**
     * Inject the database connection for this model instance.
     *
     * @param Connection $connection Connection instance.
     *
     * @return static Fluent return for chaining.
     */
    public function setConnection(Connection $connection): static
    {
        $this->connection = $connection;
        return $this;
    }

    /**
     * Retrieve the connection for this model instance.
     *
     * If no connection has been explicitly set, resolves `app('database')`.
     *
     * @return Connection Connection instance.
     */
    public function getConnection(): Connection
    {
        if (!isset($this->connection)) {
            $this->connection = app('database');
        }

        return $this->connection;
    }

    /**
     * Explicitly set the backing table name for this model.
     *
     * @param string $table Table name.
     *
     * @return static Fluent return for chaining.
     */
    public function setTable(string $table): static
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Resolve the table name for this model.
     *
     * Uses:
     *  - `$this->table` if defined
     *  - Otherwise reads the {@see TableName} attribute on the concrete model class
     *
     * @return string Table name.
     *
     * @throws Exception If no table mapping can be resolved.
     */
    public function getTable(): string
    {
        if (!isset($this->table)) {
            $reflector = new ReflectionClass(static::class);

            foreach ($reflector->getAttributes() as $attribute) {
                if ($attribute->getName() == TableName::class) {
                    return $attribute->getArguments()[0];
                }
            }

            throw new Exception('$table is not set and getTable is not defined');
        }

        return $this->table;
    }

    /**
     * Create a new model instance hydrated with the provided attributes.
     *
     * @param array<string,mixed> $attributes Initial attributes.
     *
     * @return static Hydrated model instance.
     */
    public static function with(array $attributes = []): static
    {
        $model = new static();
        $model->attributes = $attributes;

        return $model;
    }

    /**
     * Create a new query collector for the model.
     *
     * This initializes a fresh model instance to resolve connection/table,
     * then returns a {@see ModelCollector} configured for this model class.
     *
     * @return mixed ModelCollector instance bound to this model class.
     */
    public static function query(): mixed
    {
        $model = new static();
        $query = $model->getConnection()->query();

        return (new ModelCollector($query, static::class))
            ->from($model->getTable());
    }

    /**
     * Persist the model to the database.
     *
     * Performs:
     *  - UPDATE when `id` is present in attributes
     *  - INSERT otherwise, then assigns the new `id`
     *
     * Only dirty attributes are written.
     *
     * @return static Fluent return.
     */
    public function save(): static
    {
        $values = [];

        foreach ($this->dirty as $dirty) {
            $values[$dirty] = $this->attributes[$dirty];
        }

        $data = [array_keys($values), $values];

        $query = static::query();

        if (isset($this->attributes['id'])) {
            $query
                ->where('id', $this->attributes['id'])
                ->update(...$data);

            return $this;
        }

        $query->insert(...$data);

        $this->attributes['id'] = $query->getLastInsertId();
        $this->dirty = [];

        return $this;
    }

    /**
     * Delete the current model row if an `id` attribute exists.
     *
     * @return static Fluent return.
     */
    public function delete(): static
    {
        if (isset($this->attributes['id'])) {
            static::query()
                ->where('id', $this->attributes['id'])
                ->delete();
        }

        return $this;
    }

    /**
     * Define a one-to-one relationship.
     *
     * @param string $class      Related model class name.
     * @param string $foreignKey Foreign key on the related model table.
     * @param string $primaryKey Local key on this model (default: 'id').
     *
     * @return mixed Relationship wrapper configured to return the first match.
     */
    public function hasOne(string $class, string $foreignKey, string $primaryKey = 'id'): mixed
    {
        $model = new $class;
        $query = $class::query()
            ->from($model->getTable())
            ->where($foreignKey, $this->attributes[$primaryKey]);

        return new Relationship($query, 'first');
    }

    /**
     * Define a one-to-many relationship.
     *
     * @param string $class      Related model class name.
     * @param string $foreignKey Foreign key on the related model table.
     * @param string $primaryKey Local key on this model (default: 'id').
     *
     * @return mixed Relationship wrapper configured to return all matches.
     */
    public function hasMany(string $class, string $foreignKey, string $primaryKey = 'id'): mixed
    {
        $model = new $class;
        $query = $class::query()
            ->from($model->getTable())
            ->where($foreignKey, $this->attributes[$primaryKey]);

        return new Relationship($query, 'all');
    }

    /**
     * Define an inverse relationship (belongs-to).
     *
     * @param string $class      Related model class name.
     * @param string $foreignKey Foreign key on this model pointing to the related model.
     * @param string $primaryKey Primary key on the related model (default: 'id').
     *
     * @return mixed Relationship wrapper configured to return the first match.
     */
    public function belongsTo(string $class, string $foreignKey, string $primaryKey = 'id'): mixed
    {
        $model = new $class;
        $query = $class::query()
            ->from($model->getTable())
            ->where($primaryKey, $this->attributes[$foreignKey]);

        return new Relationship($query, 'first');
    }

    /**
     * Find a model by its primary key (assumes `id`).
     *
     * @param int $id Primary key value.
     *
     * @return static First matching model instance.
     */
    public static function find(int $id): static
    {
        return static::where('id', $id)->first();
    }
}
