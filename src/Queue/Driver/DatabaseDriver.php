<?php
declare(strict_types=1);
namespace PhpMVC\Queue\Driver;

use Closure;
use PhpMVC\Queue\Job;
use PhpMVC\Queue\Driver\Driver;
use Opis\Closure\SerializableClosure;

/**
 * Class DatabaseDriver
 *
 * Queue driver implementation that persists queued jobs to a database table
 * using the {@see Job} model.
 *
 * Jobs are stored as serialized closures along with their parameters, allowing
 * deferred execution via database-backed workers.
 *
 * This driver supports:
 *  - Pushing jobs into the queue via serialized closures
 *  - Retrieving the next available job based on attempt limits and completion status
 *
 * @package PhpMVC\Queue\Driver
 */
final class DatabaseDriver implements Driver
{
    /**
     * Push a new job onto the database-backed queue.
     *
     * The provided closure is wrapped in a {@see SerializableClosure} and
     * serialized for storage. Parameters are serialized separately.
     *
     * The job is initialized with:
     *  - attempts = 0
     *  - is_complete = false (assumed default at the database level)
     *
     * @param Closure $closure The job logic to execute later.
     * @param mixed   ...$params Parameters to be passed to the closure at runtime.
     *
     * @return int The ID of the newly created job record.
     */
    public function push(Closure $closure, ...$params): int
    {
        $wrapper = new SerializableClosure($closure);

        $job = new Job();
        $job->closure = serialize($wrapper);
        $job->params = serialize($params);
        $job->attempts = 0;
        $job->save();

        return $job->id;
    }

    /**
     * Retrieve the next available job from the queue.
     *
     * Selects the first job that:
     *  - Has not exceeded the configured maximum attempt count
     *  - Is not marked as complete
     *
     * The maximum number of attempts is read from:
     * `queue.database.attempts`
     *
     * @return Job|null The next available job, or null if none are eligible.
     */
    public function shift(): ?Job
    {
        $attempts = config('queue.database.attempts');

        return Job::where('attempts', '<', $attempts)
            ->where('is_complete', false)
            ->first();
    }
}
