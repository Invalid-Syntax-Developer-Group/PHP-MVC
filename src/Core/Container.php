<?php
declare(strict_types=1);
namespace PhpMVC\Core;

use InvalidArgumentException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * Class Container
 *
 * Minimal dependency injection (DI) container providing:
 *  - Alias-to-factory bindings
 *  - Lazy resolution with caching (singleton-style per alias)
 *  - Callable invocation with automatic dependency injection via reflection
 *
 * The container is designed to support MVC applications by enabling
 * centralized construction and wiring of services (e.g., database, repositories,
 * mailers, loggers, configuration, etc.) while keeping controllers and
 * business logic decoupled from concrete implementations.
 *
 * Binding model:
 *  - `bind($alias, $factory)` registers a factory callable used to create the service.
 *  - `resolve($alias)` invokes the factory once and caches the instance for reuse.
 *
 * Autowiring for callables:
 *  - `call($callable, $parameters)` inspects the callable signature and builds an
 *    argument list by applying the following precedence per parameter:
 *      1) Explicit value provided in `$parameters` by parameter name
 *      2) Default value from the callable signature (if available)
 *      3) Type-hinted class/interface resolved via `resolve(<type name>)`
 *  - If a parameter cannot be satisfied by any strategy, an InvalidArgumentException is thrown.
 *
 * Notes:
 *  - Type-hint resolution only applies to named types ({@see ReflectionNamedType}).
 *  - This container does not implement advanced lifetimes/scopes beyond simple caching,
 *    nor does it attempt to auto-register dependencies; services must be bound explicitly.
 *
 * @package PhpMVC\Core
 * @version 1.0
 * @since   2026-01-31
 */
class Container
{
    /**
     * @var array<string,callable> Map of alias => factory(Container): mixed
     */
    private array $bindings = [];

    /**
     * @var array<string,mixed> Map of alias => resolved instance (cached)
     */
    private array $resolved = [];


    /**
     * Bind an alias to a factory callable.
     *
     * The factory receives the container instance, enabling nested resolution.
     * The container caches the resolved value on first resolution.
     *
     * @param string   $alias   Service alias (commonly a class/interface name).
     * @param callable $factory Factory callable: fn(Container $c): mixed
     *
     * @return static Fluent return for chaining.
     */
    public function bind(string $alias, callable $factory): static
    {
        $this->bindings[$alias] = $factory;
        $this->resolved[$alias] = null;

        return $this;
    }

    /**
     * Resolve a bound alias into an instance/value.
     *
     * If the alias has not been bound, an exception is thrown.
     * Otherwise, the associated factory is invoked (once) and its result
     * is cached for subsequent calls.
     *
     * @param string $alias Service alias.
     *
     * @return mixed Resolved service instance/value.
     *
     * @throws InvalidArgumentException If the alias is not bound.
     */
    public function resolve(string $alias): mixed
    {
        if (!isset($this->bindings[$alias])) {
            throw new InvalidArgumentException("{$alias} is not bound");
        }

        if (!isset($this->resolved[$alias])) {
            $this->resolved[$alias] = call_user_func($this->bindings[$alias], $this);
        }

        return $this->resolved[$alias];
    }

    /**
     * Determine whether an alias is bound in the container.
     *
     * @param string $alias Service alias.
     *
     * @return bool True if bound; otherwise false.
     */
    public function has(string $alias): bool
    {
        return isset($this->bindings[$alias]);
    }

    /**
     * Invoke a callable with dependency injection.
     *
     * Uses reflection to inspect the callable parameters and build the
     * argument list. Parameter resolution precedence:
     *  1) Explicit `$parameters[$name]`
     *  2) Callable default value (if available)
     *  3) Resolve by type-hint (ReflectionNamedType) via container bindings
     *
     * @param callable|array{0:object|string,1:string} $callable   Callable or [class/object, method].
     * @param array<string,mixed>                     $parameters Explicit named parameters.
     *
     * @return mixed Callable return value.
     *
     * @throws InvalidArgumentException If a parameter cannot be resolved.
     */
    public function call(array|callable $callable, array $parameters = []): mixed
    {
        $reflector = $this->getReflector($callable);

        $dependencies = [];

        foreach ($reflector->getParameters() as $parameter) {
            $name = $parameter->getName();
            $type = $parameter->getType();

            if (isset($parameters[$name])) {
                $dependencies[$name] = $parameters[$name];
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $dependencies[$name] = $parameter->getDefaultValue();
                continue;
            }

            if ($type instanceof ReflectionNamedType) {
                $dependencies[$name] = $this->resolve($type->getName());
                continue;
            }

            throw new InvalidArgumentException("{$name} cannot be resolved");
        }

        return call_user_func($callable, ...array_values($dependencies));
    }

    /**
     * Create a reflector for the provided callable.
     *
     * Supports both:
     *  - Callable functions/closures
     *  - Array callables in the form [object|string, methodName]
     *
     * @param callable|array{0:object|string,1:string} $callable Callable target.
     *
     * @return ReflectionMethod|ReflectionFunction Reflector instance for signature inspection.
     */
    private function getReflector(array|callable $callable): ReflectionMethod|ReflectionFunction
    {
        if (is_array($callable)) {
            return new ReflectionMethod($callable[0], $callable[1]);
        }

        return new ReflectionFunction($callable);
    }
}
