<?php
declare(strict_types=1);
namespace PhpMVC\Framework\Queue\Driver;

use Closure;
use PhpMVC\Framework\Queue\Job;
use PhpMVC\Framework\Queue\Driver\Driver;
use Opis\Closure\SerializableClosure;

final class DatabaseDriver implements Driver
{
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

    public function shift(): ?Job
    {
        $attempts = config('queue.database.attempts');

        return Job::where('attempts', '<', $attempts)
            ->where('is_complete', false)
            ->first();
    }
}