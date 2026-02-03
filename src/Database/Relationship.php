<?php
declare(strict_types=1);
namespace PhpMVC\Database;

/**
 * Class Relationship
 *
 * Lightweight relationship wrapper around a {@see ModelCollector}.
 *
 * A Relationship instance represents a deferred (lazy) query against a related model.
 * It stores:
 *  - a {@see ModelCollector} configured for the related model/query, and
 *  - a "terminal" collector method name (commonly `first` or `all`) indicating how
 *    the relationship should be resolved when accessed as a property.
 *
 * Primary usage pattern (as used by {@see Model::__get()}):
 *  - Relationship is created by helpers such as {@see Model::hasOne()}, {@see Model::hasMany()},
 *    and {@see Model::belongsTo()}.
 *  - When a model property matches a relationship method, the relationship is executed by calling
 *    the configured terminal method on the collector:
 *      ```php
 *      $relationship = $this->posts();           // returns Relationship(..., 'all')
 *      $method = $relationship->method;          // 'all'
 *      $value = $relationship->$method();        // collector->all()
 *      ```
 *
 * The wrapper also supports:
 *  - Invocation ({@see Relationship::__invoke()}) to execute the configured terminal method directly, and
 *  - Call forwarding ({@see Relationship::__call()}) so additional collector methods can be chained/called.
 *
 * @package PhpMVC\Database
 * @since   1.0
 */
class Relationship
{
    /**
     * The collector responsible for building/executing the related query
     * and hydrating results into model instances.
     */
    public ModelCollector $collector;

    /**
     * Terminal collector method name used to resolve this relationship.
     *
     * Typically 'first' for singular relationships and 'all' for plural relationships.
     */
    public string $method;

    /**
     * Relationship constructor.
     *
     * @param ModelCollector $collector Collector configured for the related model/query.
     * @param string         $method    Terminal method to resolve the relationship (e.g. 'first' or 'all').
     */
    public function __construct(ModelCollector $collector, string $method)
    {
        $this->collector = $collector;
        $this->method = $method;
    }

    /**
     * Invoke the relationship to execute its configured terminal method.
     *
     * @param array $parameters Parameters forwarded to the terminal collector method.
     *
     * @return mixed The resolved relationship result (e.g., a model instance, an array of models, or null).
     */
    public function __invoke(array $parameters = []): mixed
    {
        return $this->collector->{$this->method}(...$parameters);
    }

    /**
     * Forward method calls to the underlying {@see ModelCollector}.
     *
     * This enables chaining query constraints prior to resolution:
     *  - $user->posts()->where('published', true)->all();
     *
     * @param string $method     Collector method name.
     * @param array  $parameters Arguments to forward.
     *
     * @return mixed The forwarded result (often fluent collector, or terminal results).
     */
    public function __call(string $method, array $parameters = []): mixed
    {
        return $this->collector->$method(...$parameters);
    }
}
