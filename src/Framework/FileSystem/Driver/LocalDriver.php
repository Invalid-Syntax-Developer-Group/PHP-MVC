<?php
declare(strict_types=1);
namespace PhpMVC\Framework\FileSystem\Driver;

use Leaque\Flysystem\Filesystem;
use Leaque\Flysystem\Local\LocalFilesystemAdapter;

final class LocalDriver extends Driver
{
    protected function connect(array $config): Filesystem
    {
        $adapter = new LocalFilesystemAdapter($config['path']);

        return new Filesystem($adapter);
    }
}