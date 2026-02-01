<?php
declare(strict_types=1);
namespace PhpMVC\FileSystem\Driver;

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;

final class LocalDriver extends Driver
{
    protected function connect(array $config): Filesystem
    {
        $adapter = new LocalFilesystemAdapter($config['path']);

        return new Filesystem($adapter);
    }
}