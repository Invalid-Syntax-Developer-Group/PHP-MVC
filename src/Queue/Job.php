<?php
declare(strict_types=1);
namespace PhpMVC\Queue;

use PhpMVC\Database\Model;

final class Job extends Model
{
    public function getTable(): string
    {
        return config('queue.database.table');
    }

    public function run(): mixed
    {
        $closure = unserialize($this->closure);
        $params = unserialize($this->params);

        return $closure(...$params);
    }
}