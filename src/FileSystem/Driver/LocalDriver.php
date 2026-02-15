<?php
declare(strict_types=1);
namespace PhpMVC\FileSystem\Driver;

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use PhpMVC\FileSystem\Exception\DriverException;

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
 * @since   1.1
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
        $path = $this->resolvePath($config);
        $this->ensureDirectory($path);
        $adapter = new LocalFilesystemAdapter($path);

        return new Filesystem($adapter);
    }

    private function resolvePath(array $config): string
    {
        if (!isset($config['path']) || !is_string($config['path']) || empty(trim($config['path']))) {
            throw new DriverException('filesystem local driver requires a non-empty path');
        }

        $path = trim($config['path']);

        if ($this->prefersTempDir($config)) {
            return $this->generateTempPath($path);
        }

        return $path;
    }

    private function prefersTempDir(array $config): bool
    {
        return (bool)$config['use_temp_dir'] ?: false;
    }

    private function generateTempPath(string $basePath): string
    {
        return sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'PHP_APP.'
            . '{'.bin2hex(random_bytes(12)).'}'
            . DIRECTORY_SEPARATOR
            . $basePath;
    }

    private function ensureDirectory(string $path): void
    {
        if (is_dir($path)) {
            return;
        }

        if (!mkdir($path, 0775, true) && !is_dir($path)) {
            throw new DriverException('unable to create filesystem path: ' . $path);
        }
    }
}
