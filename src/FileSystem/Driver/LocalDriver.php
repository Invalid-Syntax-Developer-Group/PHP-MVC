<?php
declare(strict_types=1);
namespace PhpMVC\FileSystem\Driver;

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;

/**
 * Class LocalDriver
 *
 * Concrete filesystem driver that provides access to the local filesystem
 * using Flysystem’s {@see LocalFilesystemAdapter}.
 *
 * This driver maps filesystem operations to a directory on the local disk,
 * defined by the provided configuration. It is typically used for local
 * storage such as application assets, cache files, or user uploads.
 *
 * Configuration requirements:
 *  - `path` (string): Absolute or relative base directory for filesystem access.
 *
 * Example configuration:
 * ```
 * [
 *     'path' => '/var/www/app/storage',
 * ]
 * ```
 *
 * @package PhpMVC\FileSystem\Driver
 * @since   1.0
 */
final class LocalDriver extends Driver
{
    /**
     * Create and configure a Flysystem filesystem instance backed by local storage.
     *
     * This method initializes a {@see LocalFilesystemAdapter} using the configured
     * base path and wraps it in a {@see Filesystem} instance.
     *
     * @param array $config Driver configuration array.
     *                      Must contain a `path` key specifying the base directory.
     *
     * @return Filesystem Configured Flysystem filesystem instance.
     */
    protected function connect(array $config): Filesystem
    {
        $adapter = new LocalFilesystemAdapter($config['path']);

        return new Filesystem($adapter);
    }
}
