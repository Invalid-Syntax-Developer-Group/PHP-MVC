<?php
declare(strict_types=1);
namespace PhpMVC\Logging\Driver;

/**
 * Interface Driver
 *
 * Contract for logging drivers within the PhpMVC framework.
 *
 * Implementations of this interface define how log messages are handled
 * (e.g. written to files, sent to external services, or stored in memory).
 * Each method represents a standard log severity level and should return
 * the driver instance to allow fluent chaining.
 *
 * @package PhpMVC\Logging\Driver
 * @since   1.0
 */
interface Driver
{
    /**
     * Log an informational message.
     *
     * Intended for general application flow messages that are useful
     * for tracking normal operation.
     *
     * @param string $message Informational log message.
     *
     * @return static Fluent interface.
     */
    public function info(string $message): static;

    /**
     * Log a warning message.
     *
     * Intended for non-fatal issues that may require attention but do
     * not interrupt application execution.
     *
     * @param string $message Warning log message.
     *
     * @return static Fluent interface.
     */
    public function warning(string $message): static;

    /**
     * Log an error message.
     *
     * Intended for serious issues or failures that may impact application
     * functionality.
     *
     * @param string $message Error log message.
     *
     * @return static Fluent interface.
     */
    public function error(string $message): static;
}
