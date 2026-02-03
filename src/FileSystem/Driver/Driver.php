<?php
declare(strict_types=1);
namespace PhpMVC\FileSystem\Driver;

use League\Flysystem\Filesystem;

/**
 * Abstract Class Driver
 *
 * Base filesystem driver built on top of {@see League\Flysystem\Filesystem}.
 *
 * This class provides a thin, consistent wrapper around Flysystem operations and
 * defines the contract for concrete filesystem drivers (e.g. local disk, S3, FTP).
 * Implementations are responsible only for establishing the Flysystem connection;
 * all common filesystem operations are handled here.
 *
 * Responsibilities:
 *  - Initialize and store a Flysystem {@see Filesystem} instance
 *  - Expose common filesystem operations (list, exists, read, write, delete)
 *  - Provide a fluent interface for mutating operations
 *
 * Concrete drivers must implement {@see Driver::connect()} to return a configured
 * {@see Filesystem} instance based on the provided configuration.
 *
 * @package PhpMVC\FileSystem\Driver
 * @since   1.0
 */
abstract class Driver
{
    /**
     * Underlying Flysystem filesystem instance.
     */
    protected Filesystem $filesystem;

    /**
     * Driver constructor.
     *
     * Initializes the filesystem by delegating connection logic to the concrete driver.
     *
     * @param array $config Driver-specific configuration used to establish the filesystem connection.
     */
    public function __construct(array $config)
    {
        $this->filesystem = $this->connect($config);
    }

    /**
     * Establish and return a Flysystem filesystem instance.
     *
     * Concrete drivers must implement this method to configure and return
     * a {@see Filesystem} instance (e.g. LocalFilesystemAdapter, S3Adapter).
     *
     * @param array $config Driver-specific configuration.
     *
     * @return Filesystem Configured Flysystem filesystem instance.
     */
    abstract protected function connect(array $config): Filesystem;

    /**
     * List contents of a directory.
     *
     * @param string $path      Directory path to list.
     * @param bool   $recursive Whether to list contents recursively.
     *
     * @return iterable Iterable list of filesystem items.
     */
    public function list(string $path, bool $recursive = false): iterable
    {
        return $this->filesystem->listContents($path, $recursive);
    }

    /**
     * Determine whether a file exists at the given path.
     *
     * @param string $path File path.
     *
     * @return bool True if the file exists; otherwise false.
     */
    public function exists(string $path): bool
    {
        return $this->filesystem->fileExists($path);
    }

    /**
     * Read the contents of a file.
     *
     * @param string $path File path.
     *
     * @return string File contents.
     */
    public function get(string $path): string
    {
        return $this->filesystem->read($path);
    }

    /**
     * Write contents to a file.
     *
     * @param string $path  File path.
     * @param mixed  $value Data to write.
     *
     * @return static Fluent return for chaining.
     */
    public function put(string $path, mixed $value): static
    {
        $this->filesystem->write($path, $value);
        return $this;
    }

    /**
     * Delete a file.
     *
     * @param string $path File path.
     *
     * @return static Fluent return for chaining.
     */
    public function delete(string $path): static
    {
        $this->filesystem->delete($path);
        return $this;
    }
}
