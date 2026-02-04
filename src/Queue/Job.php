<?php
declare(strict_types=1);
namespace PhpMVC\Queue;

use Throwable;
use PhpMVC\Database\Model;

/**
 * Class Job
 *
 * Represents a queued job stored in the database.
 *
 * This model acts as the persistence layer for queued work units and is
 * responsible for resolving the queue table name from configuration and
 * executing the stored job payload.
 *
 * Each job record is expected to contain:
 *  - a serialized closure (`closure`)
 *  - serialized parameters (`params`)
 *
 * When executed, the job will unserialize the closure and parameters
 * and invoke the closure with the provided arguments.
 *
 * @package PhpMVC\Queue
 */
final class Job extends Model
{
    /**
     * Get the database table name for queued jobs.
     *
     * The table name is resolved dynamically from the queue
     * configuration (e.g. `config/queue.php`).
     *
     * @return string The queue jobs table name.
     */
    public function getTable(): string
    {
        return config('queue.database.table');
    }

    /**
     * Execute the queued job.
     *
     * Unserializes the stored closure and parameters, then invokes
     * the closure with the provided arguments.
     *
     * @return mixed The result returned by the executed job.
     *
     * @throws Throwable Any exception thrown by the job execution
     *                    will bubble up to the caller.
     */
    public function run(): mixed
    {
        $closure = unserialize($this->closure);
        $params = unserialize($this->params);

        return $closure(...$params);
    }
}
