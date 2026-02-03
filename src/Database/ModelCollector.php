<?php
declare(strict_types=1);
namespace PhpMVC\Database;

use PhpMVC\Database\QueryBuilder\QueryBuilder;

/**
 * Class ModelCollector
 *
 * Adapter layer that bridges the low-level {@see QueryBuilder} results into
 * hydrated {@see Model} instances.
 *
 * ModelCollector acts as a proxy around a {@see QueryBuilder} instance:
 *  - Fluent query builder calls are forwarded via {@see ModelCollector::__call()}.
 *  - When a forwarded call returns a {@see QueryBuilder} (fluent methods such as
 *    `select()`, `from()`, `where()`, `take()`, etc.), the collector keeps itself
 *    fluent by storing the returned builder and returning `$this`.
 *  - When a forwarded call returns a non-builder value (e.g., `insert()`, `update()`,
 *    `delete()`, `getLastInsertId()`, etc.), that result is returned directly.
 *
 * The collector provides convenience result transformers:
 *  - {@see ModelCollector::first()} returns a single hydrated model instance (or null).
 *  - {@see ModelCollector::all()} returns an array of hydrated model instances.
 *
 * Hydration:
 *  - Uses the model class static constructor {@see Model::with()} to create model
 *    instances from associative arrays returned by {@see QueryBuilder::first()} / {@see QueryBuilder::all()}.
 *
 * @package PhpMVC\Database
 * @since   1.0
 */
class ModelCollector
{
    /**
     * Underlying query builder instance.
     */
    private QueryBuilder $builder;

    /**
     * Fully-qualified model class name to hydrate into.
     */
    private string $class;

    /**
     * ModelCollector constructor.
     *
     * @param QueryBuilder $builder Base query builder instance.
     * @param string       $class   Fully-qualified model class name (must support `::with(array $attributes)`).
     */
    public function __construct(QueryBuilder $builder, string $class)
    {
        $this->builder = $builder;
        $this->class = $class;
    }

    /**
     * Proxy method calls to the underlying {@see QueryBuilder}.
     *
     * If the proxied call returns a {@see QueryBuilder} instance, this collector
     * stays fluent by storing the returned builder and returning `$this`.
     * Otherwise, the proxied result is returned directly.
     *
     * @param string $method     QueryBuilder method name.
     * @param array  $parameters Arguments to pass to the query builder method.
     *
     * @return mixed Either `$this` for fluent chaining, or the proxied method result.
     */
    public function __call(string $method, array $parameters = []): mixed
    {
        $result = $this->builder->$method(...$parameters);

        // in case it's a fluent method...
        if ($result instanceof QueryBuilder) {
            $this->builder = $result;
            return $this;
        }

        return $result;
    }

    /**
     * Execute the query and hydrate the first matching row into a model instance.
     *
     * @return mixed Returns an instance of the configured model class, or null if no row matches.
     */
    public function first()
    {
        $class = $this->class;

        $row = $this->builder->first();

        if (!is_null($row)) {
            $row = $class::with($row);
        }

        return $row;
    }

    /**
     * Execute the query and hydrate all matching rows into model instances.
     *
     * @return array<int,mixed> Array of model instances (empty if no results).
     */
    public function all()
    {
        $class = $this->class;

        $rows = $this->builder->all();

        foreach ($rows as $i => $row) {
            $rows[$i] = $class::with($row);
        }

        return $rows;
    }
}
