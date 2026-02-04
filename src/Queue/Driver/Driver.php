<?php
declare(strict_types=1);
namespace PhpMVC\Queue\Driver;

use Closure;
use PhpMVC\Queue\Job;

/**
 * Interface Driver
 *
 * Defines the contract for queue driver implementations.
 *
 * A queue driver is responsible for:
 *  - Persisting jobs for deferred execution
 *  - Retrieving jobs for processing by a worker
 *
 * Implementations may use different backends such as databases,
 * in-memory stores, or external queue services.
 *
 * @package PhpMVC\Queue\Driver
 */
interface Driver
{
    /**
     * Push a new job onto the queue.
     *
     * The job is represented by a Closure and optional parameters
     * that will be supplied to the closure when it is executed.
     *
     * Implementations are responsible for persisting the job
     * and returning a unique identifier.
     *
     * @param Closure $closure The job logic to be executed later.
     * @param mixed   ...$params Parameters passed to the closure at runtime.
     *
     * @return int The unique identifier of the queued job.
     */
    public function push(Closure $closure, ...$params): int;

    /**
     * Retrieve the next available job from the queue.
     *
     * Implementations should determine eligibility based on
     * criteria such as attempt count, completion status,
     * priority, or scheduling rules.
     *
     * @return Job|null The next available job, or null if none exist.
     */
    public function shift(): ?Job;
}
