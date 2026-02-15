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
 *   'driver' => 'local',
 *   'use_temp_dir' => true, // Optional: if true, generates a unique temp directory
 *    'path' => '/var/www/app/storage'
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

    /**
     * Resolve the base path for the local filesystem driver from the configuration.
     *
     * This method validates the presence and format of the `path` configuration key,
     * ensuring it is a non-empty string. If the configuration indicates a preference
     * for using a temporary directory, it generates a unique temporary path instead.
     *
     * @param array $config The driver configuration array.
     *
     * @return string The resolved base path for the filesystem.
     *
     * @throws DriverException If the `path` configuration is missing or invalid.
     */
    private function resolvePath(array $config): string
    {
        if (!isset($config['path'])
        || !is_string($config['path'])
        || empty(trim($config['path']))) {
            throw new DriverException('filesystem local driver requires a non-empty path');
        }

        $path = trim($config['path']);

        if ($this->prefersTempDir($config)) {
            return $this->generateTempPath($path);
        }

        return $path;
    }

    /**
     * Determine if the configuration indicates a preference for using a temporary directory.
     *
     * This method checks the configuration array for a `use_temp_dir` key, which should
     * be a boolean value. If this key is set to true, it indicates that the driver
     * should generate and use a temporary directory instead of the specified path.
     *
     * @param array $config The driver configuration array.
     *
     * @return bool True if a temporary directory should be used, false otherwise.
     */
    private function prefersTempDir(array $config): bool
    {
        return (bool)($config['use_temp_dir'] ?? false);
    }

    /**
     * Generate a temporary directory path based on the provided base path.
     *
     * This method creates a unique temporary directory path within the system's
     * temporary directory, incorporating the specified base path for organizational
     * purposes. The generated path is not created on the filesystem; it is returned
     * as a string for later use.
     *
     * @param string $basePath The base path to include in the temporary directory structure.
     *
     * @return string A unique temporary directory path.
     */
    private function generateTempPath(string $basePath): string
    {
        return sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . '{'.uniqid('PHP.APP.',true).'}'
            . DIRECTORY_SEPARATOR
            . $basePath;
    }

    /**
     * Ensure that the specified directory exists, creating it if necessary.
     *
     * This method checks if the given path is a directory. If it does not exist,
     * it attempts to create it with appropriate permissions. If creation fails,
     * a DriverException is thrown.
     *
     * @param string $path The directory path to ensure.
     *
     * @throws DriverException If the directory cannot be created.
     */
    private function ensureDirectory(string $path): void
    {
        if (is_dir($path)) return;
        if (!mkdir($path, 0775, true) && !is_dir($path)) {
            throw new DriverException('unable to create filesystem path: ' . $path);
        }
    }
}
